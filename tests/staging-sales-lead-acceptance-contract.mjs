import fs from 'node:fs';
import path from 'node:path';

const source = fs.readFileSync(path.resolve('deploy/bds-sales-lead-pipeline-mu.php'), 'utf8');
const helper = fs.readFileSync(path.resolve('deploy/bds-staging-sales-lead-acceptance-mu.php'), 'utf8');

const checks = [
  ['production lead pipeline remains versioned', /BDS_SALES_LEAD_PIPELINE_VERSION', '0\.1\.1'/m.test(source)],
  ['production public capture remains default off', /BDS_LEAD_CAPTURE_ENABLED[\s\S]*true === BDS_LEAD_CAPTURE_ENABLED/m.test(source)],
  ['helper is exact-host pinned', /BDS_STAGING_LEADS_HOST', 'staging-branddad-social\.stackstaging\.com'/m.test(helper)],
  ['host comes from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/m.test(helper)],
  ['capture is enabled only before exact-host report exists', /bds_staging_leads_is_host\(\)[\s\S]*! get_option\( BDS_STAGING_LEADS_REPORT_OPTION[\s\S]*define\( 'BDS_LEAD_CAPTURE_ENABLED', true \)/m.test(helper)],
  ['verified-email fixture filter is limited to marker B', /bds_staging_leads_verified_email[\s\S]*_bds_staging_auth_fixture[\s\S]*'b'[\s\S]*! user_can/m.test(helper)],
  ['automatic run is exact-host and one-time gated', /if \( ! bds_staging_leads_is_host\(\) \|\| get_option\( BDS_STAGING_LEADS_REPORT_OPTION/m.test(helper)],
  ['run lock has stale recovery', /bds_staging_sales_leads_acceptance_lock_v1[\s\S]*time\(\) - 300/m.test(helper)],
  ['both fixture markers and non-admin status are verified', /_bds_staging_auth_fixture[\s\S]*'a'[\s\S]*_bds_staging_auth_fixture[\s\S]*'b'[\s\S]*! user_can\( \$user_a[\s\S]*! user_can\( \$user_b/m.test(helper)],
  ['fixture lead collisions fail closed before mutation', /fixture_collision_absent[\s\S]*bds_lead_find_by_email\( \$fake_email \)[\s\S]*bds_lead_find_by_email\( \$verified_email \)[\s\S]*bds_lead_find_by_user\( \$user_b \)/m.test(helper)],
  ['test contacts use reserved invalid domain', /staging-lead@example\.invalid/m.test(helper)],
  ['test IP uses documentation-only range', /203\.0\.113\.223/m.test(helper)],
  ['subscriber admin denial is exercised', /subscriber_admin_denied[\s\S]*false === bds_lead_admin_permission\(\)/m.test(helper)],
  ['invalid origin is exercised', /https:\/\/untrusted\.example[\s\S]*origin_not_allowed/m.test(helper)],
  ['body size rejection is exercised', /CONTENT_LENGTH'\] = 8193[\s\S]*request_too_large/m.test(helper)],
  ['valid request uses owned BrandDad origin', /set_header\( 'Origin', 'https:\/\/branddad\.social' \)/m.test(helper)],
  ['email privacy source and field validation are exercised', /not-an-email[\s\S]*privacy_ack[\s\S]*unknown-source[\s\S]*str_repeat\( 'x', 121 \)[\s\S]*invalid_email_rejected[\s\S]*privacy_required[\s\S]*invalid_source_rejected[\s\S]*field_bounds_enforced/m.test(helper)],
  ['unverified consent must not enter sender queue', /unverified_consent_not_queued[\s\S]*email_confirmation_required[\s\S]*_bds_lead_email_consent/m.test(helper)],
  ['anonymous record must be private and author zero', /anonymous_lead_private[\s\S]*private[\s\S]*post_author/m.test(helper)],
  ['public response is checked for email and internal id leakage', /public_response_safe[\s\S]*\$fake_email[\s\S]*lead_id/m.test(helper)],
  ['recommendation must come from catalog-shaped production response', /recommendation_catalog_owned[\s\S]*ai-social-growth[\s\S]*recommendation[\s\S]*name[\s\S]*summary[\s\S]*url/m.test(helper)],
  ['email-hash duplicate must reuse one reference and record', /duplicate_reuses_reference[\s\S]*duplicate_single_record[\s\S]*_bds_lead_email_hash/m.test(helper)],
  ['signed account email mismatch is rejected', /signed_email_mismatch_rejected[\s\S]*email_mismatch/m.test(helper)],
  ['verified fixture capture must create registered lead', /verified_capture_created[\s\S]*registered/m.test(helper)],
  ['signed lead ownership is verified', /signed_lead_private_owned[\s\S]*post_author[\s\S]*_bds_lead_user_id/m.test(helper)],
  ['verified consent must enter existing consent queue', /verified_consent_queued[\s\S]*email_followup[\s\S]*email_confirmation_required[\s\S]*_bds_lead_email_consent/m.test(helper)],
  ['lifecycle stages advance monotonically', /trial_started[\s\S]*checkout_started[\s\S]*paid[\s\S]*onboarding[\s\S]*active[\s\S]*lifecycle_monotonic/m.test(helper)],
  ['lifecycle history and version are asserted', /_bds_lead_stage_history[\s\S]*_bds_lead_stage_version[\s\S]*6 ===/m.test(helper)],
  ['unsubscribe propagation is exercised', /bds_lead_unsubscribed\( \$verified_id \)[\s\S]*unsubscribe_propagates[\s\S]*_bds_lead_unsubscribed_at/m.test(helper)],
  ['retention is exercised only after old fixture timestamps', /366 \* DAY_IN_SECONDS[\s\S]*bds_lead_retention_run\(\)/m.test(helper)],
  ['retention is narrowed to the two exact fixture ids', /bds_staging_lead_retention_ids[\s\S]*\$verified_id[\s\S]*\$anonymous_id[\s\S]*add_filter\( 'bds_lead_retention_candidate_ids'[\s\S]*bds_lead_retention_run\(\)[\s\S]*remove_filter\( 'bds_lead_retention_candidate_ids'/m.test(helper)],
  ['unsubscribed lead is anonymized while hash suppression remains', /retention_anonymizes_unsubscribed[\s\S]*_bds_lead_email_hash/m.test(helper)],
  ['suppression row is asserted blank and unsubscribed', /suppression_preserved[\s\S]*suppression_row[\s\S]*unsubscribed/m.test(helper)],
  ['anonymous lead retention removes email hash', /anonymous_retention_anonymizes[\s\S]*_bds_lead_email_hash/m.test(helper)],
  ['first five and sixth rate decisions are tested', /for \( \$index = 0; \$index < 5; \$index\+\+ \)[\s\S]*first_five_rate_checks_allowed[\s\S]*sixth_rate_check_rejected[\s\S]*rate_limited/m.test(helper)],
  ['rate counters are snapshotted and restored', /original_client_rate[\s\S]*original_global_rate[\s\S]*rate_counters_restored/m.test(helper)],
  ['consent snapshots are exact-email rows', /bds_staging_leads_consent_snapshot[\s\S]*hash_equals[\s\S]*array_key_exists/m.test(helper)],
  ['consent restoration validates and touches exact saved keys', /bds_staging_leads_restore_consent_rows[\s\S]*\$snapshot\['key'\][\s\S]*\^\[a-zA-Z0-9_-\][\s\S]*unset\( \$all\[ \$key \] \)/m.test(helper)],
  ['cleanup requires exact type author and fixture marker', /bds_staging_leads_cleanup_posts[\s\S]*bds_sales_lead[\s\S]*post_author[\s\S]*_bds_staging_lead_fixture[\s\S]*wp_delete_post\( \$post_id, true \)/m.test(helper)],
  ['cleanup runs in finally', /finally[\s\S]*bds_staging_leads_cleanup_posts[\s\S]*bds_staging_leads_restore_consent_rows[\s\S]*bds_staging_leads_restore_rate[\s\S]*delete_option\( \$run_lock \)/m.test(helper)],
  ['server and user context are restored and asserted', /original_context_restored[\s\S]*original_remote[\s\S]*original_content_length[\s\S]*wp_set_current_user\( \$original_user \)/m.test(helper)],
  ['report requires all 34 checks', /34 === count\( \$checks \)[\s\S]*34 === count\( array_filter\( \$checks \) \)/m.test(helper)],
  ['report exposes allowlisted booleans only', /\$allowlist = array\([\s\S]*\$safe_checks\[ \$key \]/m.test(helper)],
  ['error codes are bounded', /array_slice\( \(array\) \$errors, 0, 10 \)[\s\S]*substr[\s\S]*0, 80/m.test(helper)],
  ['public report is exact-host gated and GET only', /bds_staging_leads_register_route[\s\S]*! bds_staging_leads_is_host\(\)[\s\S]*'methods'\s*=>\s*'GET'/m.test(helper)],
  ['helper accepts no browser request input', !/get_param|\$_GET|\$_POST|php:\/\/input/.test(helper)],
  ['helper creates no user admin order subscription or payment', !/wp_create_user|wp_insert_user|set_role|add_cap|wc_create_order|payment_complete/.test(helper)],
  ['helper sends no email or message', !/wp_mail|send_message|messages\.create/.test(helper)],
  ['helper invokes no third-party provider or outbound HTTP', !/formwise|api\.openai|stripe\.com|wp_(?:safe_)?remote_(?:get|post|request)/.test(helper)],
  ['helper only hard-deletes exact marked lead fixtures', (helper.match(/wp_delete_post/g) || []).length === 1],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging sales-lead acceptance contract checks`);
