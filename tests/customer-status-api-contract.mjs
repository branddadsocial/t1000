import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-customer-status-api-mu.php', 'utf8');
const exec = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');

const checks = [
  ['API is versioned', /BDS_CUSTOMER_STATUS_API_VERSION', '0\.3\.0'/.test(source)],
  ['both routes require authenticated sessions', (source.match(/'permission_callback' => 'bds_customer_status_permission'/g) || []).length === 2],
  ['status is scoped to current user', /bds_customer_status_response\( get_current_user_id\(\) \)/.test(source)],
  ['provider ids and ledger keys are not returned', !/'plan_id'\s*=>/.test(source) && !/ledger_key/.test(source)],
  ['usage excludes expired reservations', /expires_at[\s\S]*> \$now/.test(exec)],
  ['usage reports allowance used pending and remaining', /'allowance'[\s\S]*'used'[\s\S]*'pending'[\s\S]*'remaining'/.test(exec)],
  ['report period is strictly validated', /\^\(20\\d\{2\}\)-\(0\[1-9\]\|1\[0-2\]\)\$/.test(source)],
  ['report window is bounded to 24 months', /modify\( '-23 months' \)/.test(source)],
  ['job query is customer-owned and bounded', /'author'\s*=>\s*absint\( \$user_id \)[\s\S]*'posts_per_page'\s*=>\s*500/.test(source)],
  ['credits count completed work only', /if \( 'completed' !== \$status \)[\s\S]*continue[\s\S]*\$credits \+=/.test(source)],
  ['provider cost is not fabricated', /'provider_cost'\s*=>\s*'unavailable'/.test(source)],
  ['unmeasured outcomes are explicitly unavailable', /'revenue_generated'/.test(source) && /'search_rankings'/.test(source)],
  ['verified monitoring can extend the value report without fabrication', /bds_monitoring_customer_summary/.test(source) && /'status' => 'unavailable'/.test(source)],
  ['actual customer inquiries replace unavailable lead counts when connected', /bds_inquiries_monthly_summary/.test(source) && /\$unavailable\[\] = 'leads_captured'/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} customer status API contract check(s) failed.`);
  process.exit(1);
}
console.log('\nCustomer status API contract passed.');
