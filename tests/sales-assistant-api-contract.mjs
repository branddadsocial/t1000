import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-sales-assistant-api-mu.php', 'utf8');

const checks = [
  ['version is pinned', /BDS_SALES_ASSISTANT_API_VERSION', '0\.1\.1'/],
  ['verified sales agent UUID is fixed server-side', /BDS_SALES_ASSISTANT_AGENT_ID', '35ab0cc3-e2c5-456c-b4ef-2d1853ffd29c'/],
  ['public execution defaults off', /defined\( 'BDS_SALES_ASSISTANT_ENABLED' \)[\s\S]*true === BDS_SALES_ASSISTANT_ENABLED/],
  ['server execution key is required', /BDS_FORMWISE_EXECUTION_KEY/],
  ['rate limit is per hashed IP and capped at ten per minute', /hash_hmac\( 'sha256', \$ip[\s\S]*\$count >= 10[\s\S]*MINUTE_IN_SECONDS/],
  ['global daily provider-cost cap defaults to 100', /BDS_SALES_ASSISTANT_DAILY_LIMIT[\s\S]*:\s*100[\s\S]*\$daily_count >= \$daily_limit/],
  ['message length is capped at 2000', /mb_strlen\( \$message \)[\s\S]*> 2000/],
  ['raw request size is capped', /CONTENT_LENGTH[\s\S]*> 16384/],
  ['browser origins are restricted to owned sites', /function bds_sales_assistant_origin_allowed[\s\S]*branddad\.social[\s\S]*directory\.branddad\.social/],
  ['conversation token is HMAC signed and purpose bound', /'purpose' => 'sales_chat'[\s\S]*hash_hmac\( 'sha256', \$body[\s\S]*hash_equals/],
  ['conversation token is bound to the requesting client', /'client' => substr\( bds_sales_assistant_client_key\(\)[\s\S]*hash_equals\( substr\( bds_sales_assistant_client_key\(\)/],
  ['provider conversation id is stored server-side', /set_transient\( 'bds_sales_conv_'/],
  ['model cannot provide checkout links', /function bds_sales_assistant_strip_links[\s\S]*preg_replace\( '~https\?:\/\//],
  ['offers and links come from the authoritative catalog', /bds_ai_commercial_catalog[\s\S]*bds_ai_commercial_checkout_url/],
  ['provider origin and agent are fixed', /https:\/\/builder\.formwise\.ai\/api\/v1\/agents\/.*BDS_SALES_ASSISTANT_AGENT_ID/],
  ['redirects are disabled', /'redirection'\s*=>\s*0/],
  ['provider response is bounded', /'limit_response_size'\s*=>\s*65536/],
  ['public routes expose only catalog and chat', (source.match(/register_rest_route\( 'branddad\/v1'/g) || []).length === 2],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} sales-assistant contract check(s) failed.`);
  process.exit(1);
}
console.log('\nSales-assistant API contract passed.');
