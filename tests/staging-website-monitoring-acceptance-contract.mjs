import fs from 'node:fs';
import path from 'node:path';

const monitor = fs.readFileSync(path.resolve('deploy/bds-website-monitoring-mu.php'), 'utf8');
const helper = fs.readFileSync(path.resolve('deploy/bds-staging-website-monitoring-acceptance-mu.php'), 'utf8');
const checks = [
  ['monitor version advances', /BDS_WEBSITE_MONITORING_VERSION', '0\.1\.1'/m.test(monitor)],
  ['plan candidate filter is internal', /apply_filters\( 'bds_monitoring_plan_candidate'/m.test(monitor)],
  ['filtered plan is revalidated as an array', /\$plan = is_array\( \$plan \) \? \$plan : array\(\)/m.test(monitor)],
  ['filtered plan slug remains allowlisted', /in_array\( \$slug, \$allowed, true \)/m.test(monitor)],
  ['filtered plan mode must remain paid', /'paid' === \$mode/m.test(monitor)],
  ['helper is pinned to canonical staging host', /BDS_STAGING_MONITOR_HOST', 'staging-branddad-social\.stackstaging\.com'/m.test(helper)],
  ['host comes from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/m.test(helper)],
  ['feature is enabled only before exact-host report exists', /bds_staging_monitor_is_host\(\)[\s\S]*! get_option\( BDS_STAGING_MONITOR_REPORT_OPTION[\s\S]*define\( 'BDS_WEBSITE_MONITORING_ENABLED', true \)/m.test(helper)],
  ['recurring scheduler is removed before its hook runs', /remove_action\( 'init', 'bds_monitoring_schedule' \)[\s\S]*add_action\( 'init', 'bds_staging_monitor_disable_recurring_schedule', 0 \)/m.test(helper)],
  ['synthetic plan is limited to marked fixture B', /'b' !== \(string\) get_user_meta[\s\S]*_bds_staging_auth_fixture/m.test(helper)],
  ['synthetic plan refuses administrators', /user_can\( \$user_id, 'manage_options' \)/m.test(helper)],
  ['synthetic plan uses an allowlisted monitor slug', /'slug'\s*=>\s*'ai-business-monitoring'/m.test(helper)],
  ['synthetic plan is explicitly paid', /'access_mode'\s*=>\s*'paid'/m.test(helper)],
  ['automatic run is exact-host and one-time gated', /if \( ! bds_staging_monitor_is_host\(\) \|\| get_option\( BDS_STAGING_MONITOR_REPORT_OPTION/m.test(helper)],
  ['run lock has stale recovery', /bds_staging_monitor_acceptance_lock_v1[\s\S]*time\(\) - 300/m.test(helper)],
  ['both fixture markers are verified', /_bds_staging_auth_fixture[\s\S]*'a'[\s\S]*_bds_staging_auth_fixture[\s\S]*'b'/m.test(helper)],
  ['fixtures cannot be administrators', /! user_can\( \$user_a, 'manage_options' \)[\s\S]*! user_can\( \$user_b, 'manage_options' \)/m.test(helper)],
  ['anonymous denial is exercised', /wp_set_current_user\( 0 \)[\s\S]*false === bds_monitoring_permission\(\)/m.test(helper)],
  ['synthetic plan is revalidated by production code', /\$plan = bds_monitoring_plan\( \$user_b \)[\s\S]*ai-business-monitoring[\s\S]*paid/m.test(helper)],
  ['localhost rejection is exercised', /localhost_rejected[\s\S]*http:\/\/localhost\/private/m.test(helper)],
  ['IP-literal rejection is exercised', /ip_literal_rejected[\s\S]*127\.0\.0\.1/m.test(helper)],
  ['credentialed URL rejection is exercised', /credentialed_url_rejected[\s\S]*user:pass@/m.test(helper)],
  ['nonstandard port rejection is exercised', /nonstandard_port_rejected[\s\S]*:444/m.test(helper)],
  ['only owned staging is used for a real fetch', /\$base_url = 'https:\/\/' \. BDS_STAGING_MONITOR_HOST \. '\/'/m.test(helper)],
  ['public DNS validation is exercised', /public_dns_url_accepted[\s\S]*bds_monitoring_safe_url\( \$base_url \)/m.test(helper)],
  ['authorization requirement is exercised', /authorization_required[\s\S]*bds_monitoring_configure/m.test(helper)],
  ['real production check function is invoked', /bds_monitoring_check_user\( \$user_b \)/m.test(helper)],
  ['successful fetch requires a 2xx result', /public_fetch_available[\s\S]*http_status[\s\S]*>= 200[\s\S]*< 300/m.test(helper)],
  ['response measurements remain bounded', /response_bytes[\s\S]*<= 65536[\s\S]*latency_ms[\s\S]*<= 60000/m.test(helper)],
  ['content hash is required without body storage', /content_hash_only[\s\S]*\^\[a-f0-9\]\{64\}[\s\S]*! isset\( \$available_raw\['body'\] \)[\s\S]*! isset\( \$available_raw\['content'\] \)/m.test(helper)],
  ['monthly available delta is exact', /monthly_available_delta_correct[\s\S]*1 ===[\s\S]*checks[\s\S]*1 ===[\s\S]*available/m.test(helper)],
  ['customer value report delta is exact', /value_report_delta_correct[\s\S]*website_monitoring[\s\S]*checks[\s\S]*website_monitoring[\s\S]*available/m.test(helper)],
  ['active lock duplicate is tested', /active_lock_blocks_duplicate[\s\S]*locked_before[\s\S]*locked_after/m.test(helper)],
  ['stale lock recovery is tested', /time\(\) - 121[\s\S]*stale_lock_recovers/m.test(helper)],
  ['synthetic network error is scoped to owned staging', /bds_staging_monitor_force_network_error[\s\S]*BDS_STAGING_MONITOR_HOST[\s\S]*WP_Error/m.test(helper)],
  ['network errors must fail closed', /network_error_fails_closed[\s\S]*network_failed[\s\S]*http_status/m.test(helper)],
  ['network failure counter delta is exact', /network_failure_delta_correct[\s\S]*1 ===[\s\S]*checks[\s\S]*1 ===[\s\S]*failures/m.test(helper)],
  ['redirect response must remain 3xx', /redirect_not_followed[\s\S]*301, 302, 303, 307, 308/m.test(helper)],
  ['URL change shutdown is exercised', /url_change_disables_monitor[\s\S]*needs_reconnect[\s\S]*website_changed/m.test(helper)],
  ['plan loss shutdown removes the fixture filter', /remove_filter\( 'bds_monitoring_plan_candidate'[\s\S]*plan_loss_disables_monitor[\s\S]*inactive_plan/m.test(helper)],
  ['manual rate limit is exercised without enqueue', /_bds_monitor_manual_requested[\s\S]*bds_monitoring_manual_check\(\)[\s\S]*monitoring_rate_limited/m.test(helper)],
  ['other customer report is compared', /cross_account_report_unchanged[\s\S]*\$other_before\['website_monitoring'\] === \$other_after\['website_monitoring'\]/m.test(helper)],
  ['fixture metadata is snapshotted', /bds_staging_monitor_snapshot_meta/m.test(helper)],
  ['fixture metadata is restored', /bds_staging_monitor_restore_meta[\s\S]*fixture_metadata_restored/m.test(helper)],
  ['cleanup runs in finally', /finally[\s\S]*remove_filter\( 'pre_http_request'[\s\S]*delete_option\( 'bds_monitor_'[\s\S]*delete_option\( \$run_lock \)/m.test(helper)],
  ['report exposes allowlisted booleans only', /\$allowlist = array\([\s\S]*\$safe_checks\[ \$key \]/m.test(helper)],
  ['error codes are bounded', /array_slice\( \(array\) \$errors, 0, 10 \)[\s\S]*substr[\s\S]*0, 80/m.test(helper)],
  ['public report is exact-host gated', /function bds_staging_monitor_register_route\(\)[\s\S]*! bds_staging_monitor_is_host\(\)/m.test(helper)],
  ['public report is read-only', /'methods'\s*=>\s*'GET'/m.test(helper)],
  ['helper accepts no browser request input', !/get_param|\$_GET|\$_POST|php:\/\/input/.test(helper)],
  ['helper creates no user admin order subscription or payment', !/wp_create_user|wp_insert_user|set_role|add_cap|wc_create_order|payment_complete/.test(helper)],
  ['helper sends no email or message', !/wp_mail|send_message|messages\.create/.test(helper)],
  ['helper invokes no third-party provider', !/formwise|api\.openai|stripe\.com/.test(helper)],
  ['helper deletes no post or user', !/wp_delete_post|wp_delete_user/.test(helper)],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging website-monitoring acceptance contract checks`);
