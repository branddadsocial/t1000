import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-report-stages-acceptance-mu.php', 'utf8');
const checks = [
  ['version and canonical staging host are fixed', /REPORT_STAGES_VERSION', '0\.1\.0'/.test(source) && /staging-branddad-social\.stackstaging\.com/.test(source)],
  ['host derives from canonical home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
  ['execution is one-time and stale-lock protected', /REPORT_STAGES_OPTION[\s\S]*report_stages_lock_v1[\s\S]*time\(\) - 300/.test(source)],
  ['report is ten allowlisted booleans plus bounded errors', /\$allowed = array\(/.test(source) && /10 === count\( array_filter\( \$safe_checks \) \)/.test(source) && /array_slice\( \(array\) \$errors, 0, 8/.test(source)],
  ['only two exact marker-owned non-admin fixtures are read', /bds_stage_acceptance_a[\s\S]*bds_stage_acceptance_b[\s\S]*_bds_staging_auth_fixture[\s\S]*! user_can/.test(source)],
  ['production current-period report function is reused', /bds_customer_value_report\( \$user_a, gmdate\( 'Y-m' \) \)[\s\S]*bds_customer_value_report\( \$user_b, gmdate\( 'Y-m' \) \)/.test(source)],
  ['five stage keys are exact and ordered', /array\( 'generated', 'approved', 'submitted', 'published', 'measured' \)[\s\S]*array_keys\( \$delivery \)/.test(source)],
  ['generated count must equal completed jobs and exact unit', /generated_matches_completed[\s\S]*tasks'\]\['completed'[\s\S]*generated'\]\['count'[\s\S]*completed_ai_jobs/.test(source)],
  ['later stages must be unavailable and zero', /array\( 'approved', 'submitted', 'published', 'measured' \)[\s\S]*'unavailable'[\s\S]*0 === absint/.test(source)],
  ['existing report fields must remain present', /ai_work[\s\S]*cost_tracking[\s\S]*website_monitoring[\s\S]*customer_inquiries[\s\S]*unavailable_metrics/.test(source)],
  ['route is exact-host GET-only', /report_stages_is_host[\s\S]*report-stages-acceptance[\s\S]*'methods' => 'GET'/.test(source)],
  ['no request input writes external calls or deletion', !/WP_REST_Request \$request|\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)|wp_remote_|wp_mail\(|wp_insert_post|wp_update_post|update_post_meta|wp_delete_post|wp_insert_user|wp_create_user|wc_create_order/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures++; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} report-stage contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging report-stage contract passed (${checks.length} assertions).`);
