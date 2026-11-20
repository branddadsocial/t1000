import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-woo-refund-acceptance-mu.php', 'utf8');

const checks = [
  ['helper version is explicit', /BDS_STAGING_REFUND_ACCEPTANCE_VERSION', '0\.1\.0'/.test(source)],
  ['helper is pinned to canonical TLS staging host', /BDS_STAGING_REFUND_ACCEPTANCE_HOST', 'staging-branddad-social\.stackstaging\.com'/.test(source)],
  ['host derives from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
  ['execution is one-time report gated', /refund_acceptance_pending[\s\S]*! get_option\( BDS_STAGING_REFUND_ACCEPTANCE_REPORT, false \)/.test(source)],
  ['lock is stale-safe and removed', /bds_staging_woo_refund_lock_v1[\s\S]*time\(\) - 600[\s\S]*delete_option\( \$lock \)/.test(source)],
  ['report contains only allowlisted booleans and bounded errors', /\$safe_checks\[ \$key \] = true ===[\s\S]*array_slice\( \(array\) \$errors, 0, 12 \)[\s\S]*sanitize_key/.test(source)],
  ['report does not expose ids or email', !/'order_id'\s*=>|'refund_id'\s*=>|'email'\s*=>/.test(source.match(/function bds_staging_refund_acceptance_save_report[\s\S]*?\n}/)?.[0] || '')],
  ['fixture owner is exact marked non-admin invalid account', /bds_stage_acceptance_a[\s\S]*_bds_staging_auth_fixture[\s\S]*! user_can[\s\S]*\.invalid/.test(source)],
  ['product is exact marked 29.99 virtual fixture', /ai-business-essentials[\s\S]*_bds_staging_fixture[\s\S]*29\.99[\s\S]*is_virtual[\s\S]*is_sold_individually/.test(source)],
  ['pre-existing marked order fails closed', /no_preexisting_marked_order[\s\S]*fixture_record_collision/.test(source)],
  ['fixture lead is snapshotted and compared unchanged', /fixture_lead_snapshot_captured[\s\S]*bds_staging_refund_acceptance_lead_snapshot[\s\S]*fixture_lead_unchanged/.test(source)],
  ['synthetic order uses no-gateway method', /bds_staging_synthetic_no_gateway/.test(source)],
  ['synthetic order is marked before completed transition', /'status'\s*=> 'pending'[\s\S]*_bds_staging_refund_acceptance[\s\S]*set_status\( 'completed' \)/.test(source)],
  ['synthetic order is exact USD 29.99 paid order', /set_currency\( 'USD' \)[\s\S]*set_date_paid[\s\S]*29\.99 === \(float\) \$order->get_total\(\)/.test(source)],
  ['outbound WordPress mail is suppressed only during active run', /pre_wp_mail[\s\S]*refund_acceptance_pending\(\)[\s\S]*bds_staging_refund_acceptance_active[\s\S]*outbound_mail_suppressed/.test(source)],
  ['payment evidence calls only lifecycle listener', /Call only the lifecycle listener[\s\S]*bds_lifecycle_access_provisioned/.test(source)],
  ['general access-provisioned action is not emitted', !/do_action\( 'bds_ai_access_provisioned'/.test(source)],
  ['payment event requires exact 2999 USD evidence', /payment_event_recorded[\s\S]*2999 === absint[\s\S]*'USD' ===/.test(source)],
  ['partial refund uses real Woo refund API', /wc_create_refund[\s\S]*'amount'\s*=> 5\.00/.test(source)],
  ['partial refund explicitly disables gateway and restock', /'amount'\s*=> 5\.00[\s\S]*'refund_payment'\s*=> false[\s\S]*'restock_items'\s*=> false/.test(source)],
  ['partial hook evidence is exactly 500 minor units', /partial_refund_hook_recorded[\s\S]*partial_refund_amount_exact[\s\S]*500 === absint/.test(source)],
  ['partial hook replay is tested', /do_action\( 'woocommerce_order_refunded', \$order_id, \$partial->get_id\(\) \)[\s\S]*partial_refund_replay_idempotent/.test(source)],
  ['remaining refund uses exact 24.99', /'amount'\s*=> 24\.99/.test(source)],
  ['remaining refund explicitly disables gateway and restock', /'amount'\s*=> 24\.99[\s\S]*'refund_payment'\s*=> false[\s\S]*'restock_items'\s*=> false/.test(source)],
  ['remaining hook evidence is exactly 2499 minor units', /remaining_refund_hook_recorded[\s\S]*remaining_refund_amount_exact[\s\S]*2499 === absint/.test(source)],
  ['remaining hook replay is tested', /do_action\( 'woocommerce_order_refunded', \$order_id, \$remaining->get_id\(\) \)[\s\S]*remaining_refund_replay_idempotent/.test(source)],
  ['full refund total and order status are asserted', /order_total_refunded_exact[\s\S]*29\.99[\s\S]*order_reaches_refunded_status[\s\S]*has_status\( 'refunded' \)/.test(source)],
  ['production aggregate calculator is reused', /bds_lifecycle_admin_summary\( \$request \)/.test(source)],
  ['aggregate gross refund and net deltas are exact', /aggregate_gross_delta_exact[\s\S]*2999[\s\S]*aggregate_refund_delta_exact[\s\S]*2999[\s\S]*aggregate_net_delta_zero/.test(source)],
  ['truncated aggregate fails closed', /aggregate_baseline_unavailable[\s\S]*aggregate_result_unavailable/.test(source)],
  ['exact three lifecycle events are required', /exact_event_count[\s\S]*3 === count/.test(source)],
  ['event cleanup validates user type and object', /bds_staging_refund_acceptance_delete_event[\s\S]*_bds_event_user[\s\S]*_bds_event_type[\s\S]*_bds_event_object_type[\s\S]*_bds_event_object_id/.test(source)],
  ['refund cleanup validates type parent and marker', /bds_staging_refund_acceptance_delete_refund[\s\S]*WC_Order_Refund[\s\S]*get_parent_id[\s\S]*_bds_staging_refund_acceptance/.test(source)],
  ['order cleanup validates exact marker', /BDS_STAGING_REFUND_ACCEPTANCE_MARKER !== \(string\) \$order->get_meta[\s\S]*\$order->delete\( true \)/.test(source)],
  ['current user context is restored', /wp_set_current_user\( \$original_user \)[\s\S]*original_context_restored/.test(source)],
  ['completion requires all 30 checks', /30 === count\( \$checks \) && 30 === count\( array_filter\( \$checks \) \)/.test(source)],
  ['route is exact-host gated and GET-only', /refund_acceptance_is_host[\s\S]*\/staging\/woo-refund-acceptance[\s\S]*'methods'\s*=>\s*'GET'/.test(source)],
  ['helper accepts no caller input or superglobal input', !/function bds_staging_refund_acceptance_report\(\s*WP_REST_Request|\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)\b/.test(source)],
  ['helper sends no provider or network request', !/wp_mail\(|wp_remote_|curl_|FormWise|stripe/i.test(source)],
  ['helper invokes no gateway payment methods', !/payment_complete\(|process_payment\(|WC_Gateway/.test(source)],
  ['helper creates no user or administrator', !/wp_insert_user|wp_create_user|set_role\(|administrator/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging Woo refund acceptance contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging Woo refund acceptance contract passed (${checks.length} assertions).`);
