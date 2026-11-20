import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-staging-customer-api-acceptance-mu.php', 'utf8');

const checks = [
  ['helper is versioned and exact-host pinned', /BDS_STAGING_CUSTOMER_API_VERSION', '0\.1\.0'[\s\S]*BDS_STAGING_CUSTOMER_API_HOST', 'staging-branddad-social\.stackstaging\.com'/],
  ['host comes from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/],
  ['run is one-time with stale lock recovery', /bds_staging_customer_api_report_v1[\s\S]*time\(\) - 300[\s\S]*delete_option\( \$run_lock \)/],
  ['both fixture users require slot markers and no admin', /bds_stage_acceptance_a[\s\S]*bds_stage_acceptance_b[\s\S]*'a' ===[\s\S]*'b' ===[\s\S]*! user_can\( \$user_a, 'manage_options' \)[\s\S]*! user_can\( \$user_b, 'manage_options' \)/],
  ['localhost website is rejected', /localhost_website_rejected[\s\S]*invalid_website/],
  ['private IP website is rejected', /127\.0\.0\.1[\s\S]*private_ip_website_rejected[\s\S]*invalid_website/],
  ['oversized field is rejected', /str_repeat\( 'A', 121 \)[\s\S]*oversized_field_rejected[\s\S]*invalid_profile/],
  ['unsupported enum is rejected', /'primary_goal' => 'unsupported'[\s\S]*unsupported_enum_rejected[\s\S]*invalid_profile/],
  ['unknown-only update is rejected', /not_allowlisted[\s\S]*unknown_only_update_rejected[\s\S]*empty_update/],
  ['active production lock is rejected', /update_option\( \$onboarding_lock, time\(\), false \)[\s\S]*active_lock_rejected[\s\S]*onboarding_busy/],
  ['stale production lock is recovered', /time\(\) - 31[\s\S]*stale_lock_recovered/],
  ['valid public URL is stored not fetched', /'website_url'\s*=>\s*'https:\/\/example\.com\/'[\s\S]*stale_lock_recovered/],
  ['version increments exactly once', /version_incremented_once[\s\S]*\$version \+ 1/],
  ['other customer profile is unchanged', /cross_account_profile_unchanged[\s\S]*Staging Acceptance Alpha/],
  ['report fixtures are private and marker owned', /'post_status'\s*=>\s*'private'[\s\S]*_bds_staging_customer_api_fixture/],
  ['completed and failed customer jobs are distinct', /\$job_completed[\s\S]*'completed'[\s\S]*\$job_failed[\s\S]*'failed'/],
  ['cross-account completed job is created', /\$job_other = bds_staging_customer_api_job\( \$user_a, 'completed'/],
  ['customer completed delta is one', /customer_completed_delta_correct[\s\S]*1 ===/],
  ['customer failed delta is one', /customer_failed_delta_correct[\s\S]*1 ===/],
  ['only completed credits add five', /completed_credits_only[\s\S]*5 ===/],
  ['cross-account job is excluded from tool delta', /cross_account_job_excluded[\s\S]*1 ===/],
  ['status response excludes provider internals', /status_response_safe[\s\S]*formwise[\s\S]*reservation[\s\S]*ledger/],
  ['invalid reporting month is rejected', /1999-99[\s\S]*invalid_report_period_rejected[\s\S]*invalid_period/],
  ['original user context is restored', /finally[\s\S]*wp_set_current_user\( \$original_user \)/],
  ['report is boolean allowlisted', /\$allowlist = array\([\s\S]*\$safe_checks/],
  ['public report is exact-host gated', /register_route[\s\S]*! bds_staging_customer_api_is_host\(\)[\s\S]*register_rest_route/],
  ['helper accepts no browser request input', !/\$_GET|\$_POST|\$_REQUEST|get_header/.test(source)],
  ['helper creates no user admin order subscription or payment', !/wp_insert_user|'administrator'|wc_create_order|payment_intent|stripe/.test(source)],
  ['helper performs no outbound request or message', !/wp_(safe_)?remote_|curl_|file_get_contents\s*\(|wp_mail|send_message/.test(source)],
  ['helper performs no user or post deletion', !/wp_delete_user|wp_delete_post|DELETE FROM|unlink\s*\(/.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging customer API contract check(s) failed.`);
  process.exit(1);
}

console.log('\nStaging customer API contract passed.');
