import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-subscription-lifecycle-acceptance-mu.php', 'utf8');

const checks = [
  ['helper version is explicit', /BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_VERSION', '0\.1\.0'/.test(source)],
  ['helper is pinned to the canonical TLS staging host', /BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_HOST', 'staging-branddad-social\.stackstaging\.com'/.test(source)],
  ['host derives from configured home_url', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
  ['execution is one-time report gated', /subscription_acceptance_pending[\s\S]*! get_option\( BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REPORT, false \)/.test(source)],
  ['acceptance key exists only while exact staging run is pending', /if \( bds_staging_subscription_acceptance_pending\(\) \)[\s\S]*BDS_FORMWISE_PROVISIONING_KEY[\s\S]*BDS_AI_ENTITLEMENTS_ENABLED/.test(source)],
  ['dummy credential is visibly non-production', /staging-acceptance-only-not-a-provider-credential/.test(source)],
  ['all FormWise traffic is intercepted while dummy credential exists', /builder\.formwise\.ai\/api\/v1\/end-users[\s\S]*staging_provider_blocked/.test(source) && /add_filter\( 'pre_http_request', 'bds_staging_subscription_acceptance_http', 1, 3 \)/.test(source)],
  ['mock activates only inside the request-scoped acceptance context', /empty\( \$state\['active'\] \)[\s\S]*staging_provider_blocked/.test(source)],
  ['provider create requires exact endpoint and POST', /'POST' === \$method && \$base === \$url/.test(source)],
  ['provider request validates email tier and idempotency key', /is_email\( \$email \)[\s\S]*wp_is_uuid\( \$plan_id \)[\s\S]*Idempotency-Key/.test(source)],
  ['provider delete is limited to the fixed synthetic end user', /rawurlencode\( BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REMOTE_USER \)[\s\S]*'DELETE' === \$method/.test(source)],
  ['unexpected provider calls fail closed', /staging_provider_request_unexpected/.test(source)],
  ['report contains only allowlisted booleans and bounded error codes', /\$safe_checks\[ \$key \] = true ===[\s\S]*array_slice\( \(array\) \$errors, 0, 12 \)[\s\S]*sanitize_key/.test(source)],
  ['report does not expose internal ids or email', !/'order_id'\s*=>|'subscription_id'\s*=>|'email'\s*=>/.test(source.match(/function bds_staging_subscription_acceptance_save_report[\s\S]*?\n}/)?.[0] || '')],
  ['synthetic orders use a no-gateway method', /bds_staging_synthetic_no_gateway/.test(source)],
  ['no checkout payment or gateway execution is invoked', !/payment_complete\(|process_payment\(|WC_Gateway|stripe/i.test(source)],
  ['fixture owner must be the marked non-admin subscriber', /bds_stage_acceptance_a[\s\S]*_bds_staging_auth_fixture[\s\S]*! user_can\( \$fixture_user_id, 'manage_options' \)/.test(source)],
  ['product must be the exact marked staging fixture', /ai-business-essentials[\s\S]*_bds_staging_fixture[\s\S]*29\.99[\s\S]*is_virtual[\s\S]*is_sold_individually/.test(source)],
  ['pre-existing marked order or subscription fails closed', /no_preexisting_marked_records[\s\S]*fixture_record_collision/.test(source)],
  ['subscription belongs to fixture user and marked parent order', /wps_customer_id[\s\S]*wps_parent_order[\s\S]*_bds_staging_subscription_acceptance/.test(source)],
  ['authoritative seven-day trial metadata is installed', /wps_sfw_subscription_free_trial_number[\s\S]*=> 7[\s\S]*wps_sfw_subscription_free_trial_interval[\s\S]*=> 'day'/.test(source)],
  ['real trial activation and state functions are exercised', /bds_ai_trial_record_activation[\s\S]*bds_ai_trial_state[\s\S]*bds_ai_trial_plan/.test(source)],
  ['trial parent cannot call provider provisioning', /trial_parent_provision_denied[\s\S]*trial_provider_not_called/.test(source)],
  ['renewal relationships use the production WP Swings metadata', /wps_sfw_renewal_order[\s\S]*wps_sfw_subscription[\s\S]*wps_wsp_last_renewal_order_id/.test(source)],
  ['first grant verifies one allowance cycle', /first_paid_grant[\s\S]*first_allowance_cycle[\s\S]*_bds_ai_allowance_cycle/.test(source)],
  ['grant replay proves provider and credit idempotency', /grant_replay_idempotent[\s\S]*\$upserts_after_first === absint[\s\S]*1 === absint/.test(source)],
  ['second paid renewal advances allowance exactly once', /second_renewal_grant[\s\S]*second_allowance_cycle_once[\s\S]*2 === absint/.test(source)],
  ['failed latest renewal cannot fall back to stale paid parent', /failed_latest_order_no_fallback[\s\S]*0 === bds_ai_subscription_current_paid_order/.test(source)],
  ['failed renewal runs production revocation path', /wps_subscription_status', 'on-hold'[\s\S]*bds_ai_sfw_renewal_order_status_changed/.test(source)],
  ['duplicate revocation proves provider delete idempotency', /duplicate_revocation_idempotent[\s\S]*\$delete_after_failure === absint/.test(source)],
  ['reactivation reuses paid order without resetting credits', /same_order_reactivation[\s\S]*reactivation_no_credit_reset/.test(source)],
  ['cancellation uses production revocation and preserves trial history', /wps_subscription_status', 'cancelled'[\s\S]*cancellation_revokes[\s\S]*cancellation_preserves_trial_history/.test(source)],
  ['provider failure proves fail-closed bounded retry', /\$http_state\['mode'\] = 'failure'[\s\S]*provider_failure_fails_closed[\s\S]*provider_failure_retry_bounded/.test(source)],
  ['scheduled retries are removed only for exact fixture args', /wp_next_scheduled\( \$hook, \$args \)[\s\S]*wp_unschedule_event\( \$timestamp, \$hook, \$args \)/.test(source)],
  ['trial history is captured and restored exactly', /\$history_existed[\s\S]*\$history_before[\s\S]*update_user_meta[\s\S]*delete_user_meta/.test(source)],
  ['lifecycle cleanup is scoped to fixture user and post ids', /_bds_event_user[\s\S]*array_diff[\s\S]*wp_delete_post\( \$event_id, true \)/.test(source)],
  ['subscription deletion checks the exact fixture marker', /BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER !== \(string\) get_post_meta\( \$subscription_id[\s\S]*wp_delete_post\( \$subscription_id, true \)/.test(source)],
  ['order deletion checks exact fixture marker and created ids', /array_reverse\( \$created_order_ids \)[\s\S]*get_meta\( '_bds_staging_subscription_acceptance'[\s\S]*\$order->delete\( true \)/.test(source)],
  ['current user context is restored', /wp_set_current_user\( \$original_user \)[\s\S]*original_context_restored/.test(source)],
  ['completion requires all 36 checks', /36 === count\( \$checks \) && 36 === count\( array_filter\( \$checks \) \)/.test(source)],
  ['route is exact-host gated and GET only', /subscription_acceptance_is_host[\s\S]*\/staging\/subscription-lifecycle-acceptance[\s\S]*'methods'\s*=>\s*'GET'/.test(source)],
  ['helper accepts no request object or superglobal input', !/WP_REST_Request|\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)\b/.test(source)],
  ['helper sends no message or real provider request', !/wp_mail\(|wp_remote_post\(|wp_remote_request\(|wp_safe_remote/.test(source)],
  ['helper creates no account or administrator', !/wp_insert_user|wp_create_user|set_role\(|administrator/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging subscription lifecycle acceptance contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging subscription lifecycle acceptance contract passed (${checks.length} assertions).`);

