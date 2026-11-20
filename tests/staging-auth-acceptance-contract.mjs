import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-staging-auth-acceptance-mu.php', 'utf8');

const checks = [
  ['helper is versioned', /BDS_STAGING_AUTH_ACCEPTANCE_VERSION', '0\.1\.0'/],
  ['helper is pinned to exact staging home host', /BDS_STAGING_AUTH_ACCEPTANCE_HOST', 'staging-branddad-social\.stackstaging\.com'/],
  ['host decision uses home_url not request Host', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/],
  ['helper has a one-time persisted report', /bds_staging_auth_acceptance_report_v1/],
  ['run lock has stale recovery', /time\(\) - 300[\s\S]*delete_option\( \$lock \)/],
  ['fixture users use invalid non-delivery email domain', /@example\.invalid/],
  ['fixture users receive random passwords', /wp_generate_password\( 48, true, true \)/],
  ['fixture users are subscribers only', /'role'\s*=>\s*'subscriber'/],
  ['fixture collisions fail closed without slot marker', /\$slot === \(string\) get_user_meta\( \$id, '_bds_staging_auth_fixture', true \)[\s\S]*fixture_user_collision/],
  ['anonymous permission denial is tested', /anonymous_permissions_denied[\s\S]*false === bds_onboarding_permission\(\)[\s\S]*false === bds_ai_exec_permission\(\)/],
  ['authenticated permission acceptance is tested', /authenticated_permissions_allowed[\s\S]*true === bds_onboarding_permission\(\)[\s\S]*true === bds_ai_exec_permission\(\)/],
  ['optimistic version replay is denied', /onboarding_stale_version_denied[\s\S]*version_conflict/],
  ['onboarding cross-account isolation is tested', /onboarding_cross_account_isolated/],
  ['AI job ownership is tested', /ai_job_owner_read[\s\S]*ai_job_cross_account_denied/],
  ['cross-account job denial requires 404', /'not_found'[\s\S]*404 === absint/],
  ['original user context is restored', /finally[\s\S]*wp_set_current_user\( \$original_user \)/],
  ['report allowlists boolean checks', /\$allowed_checks = array\([\s\S]*\$safe_checks/],
  ['report contains no ids emails passwords or result text', !/'user_id'|'email'|'password'|'Staging acceptance result'\s*=>/.test(source)],
  ['public route is exact-host gated', /register_route[\s\S]*! bds_staging_auth_acceptance_is_host\(\)[\s\S]*register_rest_route/],
  ['helper performs no outbound request', !/wp_(safe_)?remote_|curl_|file_get_contents\s*\(/.test(source)],
  ['helper performs no deletion', !/wp_delete_user|wp_delete_post|DELETE FROM|unlink\s*\(/.test(source)],
  ['helper accepts no request parameters', !/\$_GET|\$_POST|\$_REQUEST|get_param|get_header/.test(source)],
  ['helper creates no admin or shop role', !/'administrator'|'shop_manager'/.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging auth acceptance contract check(s) failed.`);
  process.exit(1);
}

console.log('\nStaging auth acceptance contract passed.');
