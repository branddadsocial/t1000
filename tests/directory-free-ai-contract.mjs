import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-directory-free-ai-mu.php', 'utf8');

const checks = [
  ['website audit allowance is one', /'website_audit'\s*=>\s*array\([^\n]*'limit'\s*=>\s*1/],
  ['social draft allowance is five', /'social_draft'\s*=>\s*array\([^\n]*'limit'\s*=>\s*5/],
  ['local SEO allowance is one', /'local_seo_report'\s*=>\s*array\([^\n]*'limit'\s*=>\s*1/],
  ['review response allowance is five', /'review_response'\s*=>\s*array\([^\n]*'limit'\s*=>\s*5/],
  ['allowance is keyed by listing and month', /bds_dir_free_ai_' \+?\s*\. absint\( \$listing_id \)[\s\S]*str_replace\( '-', '', \$period \)/],
  ['eligibility requires a published Directorist listing', /'at_biz_dir' !== \$post->post_type[\s\S]*'publish' !== \$post->post_status/],
  ['eligibility requires approved claim state', /in_array\( \$state, array\( 'claimed', 'approved', 'verified' \), true \)/],
  ['post author and claimed owner must match', /\$user_id === absint\( \$post->post_author \)[\s\S]*\$user_id === \$claimed_by/],
  ['claim approval hook uses three authoritative ids', /add_action\( 'bds_directory_claim_approved', 'bds_dir_free_ai_on_claim_approved', 30, 3 \)/],
  ['atomic listing lock exists', /add_option\( \$key, time\(\), '', false \)/],
  ['jobs reserve before execution', /function bds_dir_free_ai_reserve/],
  ['successful jobs commit usage', /function bds_dir_free_ai_commit/],
  ['failed jobs release without usage', /function bds_dir_free_ai_release/],
  ['pending reservations expire', /20 \* MINUTE_IN_SECONDS/],
  ['no external provisioning is active', !/wp_remote_(post|request|get)\s*\(/.test(source)],
  ['no public REST endpoint exists', !/register_rest_route\s*\(/.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} free-AI contract check(s) failed.`);
  process.exit(1);
}

console.log('\nDirectory free-AI entitlement contract passed.');
