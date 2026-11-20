import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-staging-ai-failure-acceptance-mu.php', 'utf8');

const checks = [
  ['helper is versioned', /BDS_STAGING_AI_FAILURE_VERSION', '0\.1\.0'/],
  ['helper is pinned to exact staging host', /BDS_STAGING_AI_FAILURE_HOST', 'staging-branddad-social\.stackstaging\.com'/],
  ['host decision uses configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/],
  ['run is one-time with stale lock recovery', /bds_staging_ai_failure_report_v1[\s\S]*time\(\) - 300[\s\S]*delete_option\( \$lock \)/],
  ['existing fixture owner requires slot marker', /username_exists\( 'bds_stage_acceptance_a' \)[\s\S]*'a' === \(string\) get_user_meta/],
  ['fixture owner cannot manage options', /! user_can\( \$user_id, 'manage_options' \)/],
  ['helper refuses to run when provider is configured', /provider_absent[\s\S]*provider_is_configured[\s\S]*return;/],
  ['credits are reserved through production function', /bds_ai_exec_paid_reserve\( \$user_id, \$plan, 5 \)/],
  ['reservation is proven before execution', /credit_reserved_before_run[\s\S]*\['pending'\]\[ \$reservation \][\s\S]*5 === absint/],
  ['job is private and fixture marked', /'post_status'\s*=>\s*'private'[\s\S]*_bds_staging_ai_failure_fixture/],
  ['job starts pending with paid access and reservation', /_bds_status', 'pending'[\s\S]*_bds_access_mode', 'paid'[\s\S]*_bds_reservation/],
  ['production worker is invoked', /bds_ai_exec_process_job\( \$job_id \)/],
  ['failure status is asserted', /job_failed_closed[\s\S]*'failed' ===/],
  ['configuration failure code is asserted', /failure_code_recorded[\s\S]*execution_not_configured/],
  ['reservation release is asserted', /credit_reservation_released[\s\S]*! isset\( \$after\['pending'\]\[ \$reservation \] \)/],
  ['used credits remain zero', /used_credits_unchanged[\s\S]*0 === absint/],
  ['worker replay is attempted', /(bds_ai_exec_process_job\( \$job_id \);[\s\S]*){2}/],
  ['replay cannot double deduct', /replay_did_not_double_deduct[\s\S]*0 === absint\( \$replayed\['used'\]/],
  ['report allowlists boolean evidence', /\$allowlist = array\([\s\S]*\$safe_checks/],
  ['report exposes no ids credentials inputs or result', !/'user_id'\s*=>|'job_id'\s*=>|'reservation'\s*=>|'inputs'\s*=>|'result'\s*=>/.test(source)],
  ['public route is exact-host gated', /register_route[\s\S]*! bds_staging_ai_failure_is_host\(\)[\s\S]*register_rest_route/],
  ['helper accepts no request parameters', !/\$_GET|\$_POST|\$_REQUEST|get_param|get_header/.test(source)],
  ['helper creates no user administrator or payment', !/wp_insert_user|'administrator'|wc_create_order|payment_intent|stripe/.test(source)],
  ['helper performs no direct outbound request', !/wp_(safe_)?remote_|curl_|file_get_contents\s*\(/.test(source)],
  ['helper performs no record deletion', !/wp_delete_user|wp_delete_post|DELETE FROM|unlink\s*\(/.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging AI failure contract check(s) failed.`);
  process.exit(1);
}

console.log('\nStaging AI failure contract passed.');
