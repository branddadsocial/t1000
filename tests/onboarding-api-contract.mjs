import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-onboarding-api-mu.php', 'utf8');
const checks = [
  ['version is pinned', /BDS_ONBOARDING_API_VERSION', '0\.1\.0'/],
  ['routes require WordPress authentication', (source.match(/permission_callback' => 'bds_onboarding_permission'/g) || []).length === 2],
  ['request body is bounded', /CONTENT_LENGTH[\s\S]*> 16384/],
  ['state is scoped to the current user', /get_current_user_id\(\)[\s\S]*_bds_onboarding_v1/],
  ['profile fields are explicitly allowlisted', /business_name[\s\S]*website_url[\s\S]*industry[\s\S]*service_area[\s\S]*primary_goal[\s\S]*brand_voice/],
  ['website reference blocks local and private IP targets', /localhost[\s\S]*\.local[\s\S]*FILTER_FLAG_NO_PRIV_RANGE[\s\S]*FILTER_FLAG_NO_RES_RANGE/],
  ['website is stored only and never fetched', !/wp_(safe_)?remote_(get|post|request)/.test(source)],
  ['updates use optimistic version checks', /version_conflict[\s\S]*current_version/],
  ['concurrent updates use an atomic lock', /add_option\( \$lock_key, time\(\), '', false \)[\s\S]*delete_option\( \$lock_key \)/],
  ['paid access comes from the authoritative active plan', /bds_ai_exec_active_plan/],
  ['free access rechecks verified Directory ownership', /bds_dir_free_ai_listing_eligible/],
  ['first task requires a completed customer-owned job', /'author'\s*=>\s*absint\( \$user_id \)[\s\S]*'meta_value'\s*=>\s*'completed'/],
  ['recommendations map only to allowlisted execution tools', /'social'\s*=>\s*'social_draft'[\s\S]*'listing'\s*=>\s*'listing_studio'/],
  ['free Directory access cannot recommend paid-only tools', /\$free_tools[\s\S]*'directory_free' === \$access_mode[\s\S]*\$recommended = 'assistant_request'/],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} onboarding API contract check(s) failed.`);
  process.exit(1);
}
console.log('\nOnboarding API contract passed.');
