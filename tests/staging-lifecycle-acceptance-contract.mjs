import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-staging-lifecycle-acceptance-mu.php', 'utf8');

const checks = [
  ['helper is versioned', /BDS_STAGING_LIFECYCLE_VERSION', '0\.1\.0'/],
  ['helper is exact-host pinned from home URL', /BDS_STAGING_LIFECYCLE_HOST', 'staging-branddad-social\.stackstaging\.com'[\s\S]*wp_parse_url\( home_url/],
  ['run is one-time with stale lock recovery', /bds_staging_lifecycle_report_v1[\s\S]*time\(\) - 300[\s\S]*delete_option\( \$lock \)/],
  ['fixture owner requires marker and lacks Woo management', /username_exists\( 'bds_stage_acceptance_a' \)[\s\S]*'a' ===[\s\S]*! user_can\( \$user_id, 'manage_woocommerce' \)/],
  ['subscriber analytics denial is exercised', /non_admin_analytics_denied[\s\S]*false === bds_lifecycle_admin_permission\(\)/],
  ['unknown event rejection is exercised', /unknown_event_rejected[\s\S]*bds_lifecycle_record\( 'not_allowed'/],
  ['baseline and final aggregates use current period', /(bds_staging_lifecycle_summary_data\(\)[\s\S]*){2}/],
  ['payment event carries bounded fixture data', /payment_confirmed[\s\S]*staging_fixture[\s\S]*2999/],
  ['duplicate uses identical event identity', /(\$payment_key[\s\S]*990100[\s\S]*){2}/],
  ['duplicate must reuse one id', /duplicate_event_reused[\s\S]*\$payment_a === \$payment_b/],
  ['duplicate must leave one row', /duplicate_event_single_row[\s\S]*1 === count\( \$matching \)/],
  ['untrusted metadata is asserted absent', /untrusted_metadata_stripped[\s\S]*_bds_event_email[\s\S]*_bds_event_message/],
  ['stored financial metadata is asserted exact', /financial_metadata_bounded[\s\S]*'USD'[\s\S]*2999/],
  ['refund fixture is bounded', /payment_refunded[\s\S]*990101[\s\S]*500/],
  ['gross delta must be 2999', /aggregate_gross_delta_correct[\s\S]*2999 === \$gross_after - \$gross_before/],
  ['refund delta must be 500', /aggregate_refund_delta_correct[\s\S]*500 === \$refund_after - \$refund_before/],
  ['net delta must be 2499', /aggregate_net_delta_correct[\s\S]*2499 === \$net_after - \$net_before/],
  ['invalid period is rejected', /invalid_period_rejected[\s\S]*'invalid_period'/],
  ['original user context is restored', /finally[\s\S]*wp_set_current_user\( \$original_user \)/],
  ['report is boolean allowlisted', /\$allowlist = array\([\s\S]*\$safe_checks/],
  ['public report is exact-host gated', /register_route[\s\S]*! bds_staging_lifecycle_is_host\(\)[\s\S]*register_rest_route/],
  ['helper accepts no request input', !/\$_GET|\$_POST|\$_REQUEST|get_header/.test(source)],
  ['helper creates no user admin order subscription or refund', !/wp_insert_user|'administrator'|wc_create_order|wc_create_refund/.test(source)],
  ['helper performs no outbound request or message', !/wp_(safe_)?remote_|curl_|file_get_contents\s*\(|wp_mail|send_message/.test(source)],
  ['helper performs no record deletion', !/wp_delete_user|wp_delete_post|DELETE FROM|unlink\s*\(/.test(source)],
  ['report exposes no ids financial amounts or metadata', !/'user_id'\s*=>|'event_id'\s*=>|'amount_minor'\s*=>|'currency'\s*=>|'email'\s*=>|'message'\s*=>/.test(source.match(/function bds_staging_lifecycle_save_report[\s\S]*?\n\}/)?.[0] ?? '')],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging lifecycle contract check(s) failed.`);
  process.exit(1);
}

console.log('\nStaging lifecycle contract passed.');
