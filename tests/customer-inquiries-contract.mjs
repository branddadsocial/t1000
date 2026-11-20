import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-customer-inquiries-mu.php', 'utf8');
const report = fs.readFileSync('deploy/bds-customer-status-api-mu.php', 'utf8');
const lifecycle = fs.readFileSync('deploy/bds-lifecycle-events-mu.php', 'utf8');

const checks = [
  ['inquiry service is versioned', /BDS_CUSTOMER_INQUIRIES_VERSION', '0\.1\.0'/.test(source)],
  ['public capture defaults off', /BDS_CUSTOMER_INQUIRIES_ENABLED[\s\S]*true === BDS_CUSTOMER_INQUIRIES_ENABLED/.test(source)],
  ['records are private and excluded from REST', /register_post_type\([\s\S]*'public' => false[\s\S]*'show_in_rest' => false/.test(source)],
  ['form availability requires active paid allowlisted plan', /'paid' === \$mode[\s\S]*in_array\( \$slug, \$allowed/.test(source)],
  ['public forms use random opaque references', /wp_generate_password\( 32, false, false \)/.test(source) && /\^\[a-z0-9\]\{32\}\$/.test(source)],
  ['form reference resolves exactly one owner', /'number' => 2[\s\S]*1 === count\( \$users \)/.test(source)],
  ['submission is restricted to owned HTTPS form origins', /'https' ===[\s\S]*branddad\.social[\s\S]*directory\.branddad\.social/.test(source)],
  ['public body and client/global rates are bounded', /CONTENT_LENGTH[\s\S]*> 12288/.test(source) && /\$client >= 5[\s\S]*\$global >= \$limit/.test(source)],
  ['rate keys use HMAC IP without storing IP on inquiry', /bds_inq_client_[\s\S]*hash_hmac\( 'sha256'/.test(source) && !/_bds_inquiry_ip/.test(source)],
  ['honeypot privacy contact consent message and contact method are required', /company_website/.test(source) && /! \$privacy \|\| ! \$contact/.test(source) && /! is_email\( \$email \) && '' === \$phone/.test(source)],
  ['contact fields and message are bounded', /strlen\( \$name \) > 120/.test(source) && /strlen\( \$phone \) > 40/.test(source) && /strlen\( \$message \) > 2000/.test(source)],
  ['public caller cannot choose owner or status', /'post_author' => \$owner_id/.test(source) && /_bds_inquiry_status', 'new'/.test(source) && !/get_param\( 'owner/.test(source)],
  ['public response exposes opaque reference not internal id', /array\( 'status' => 'received', 'reference' => \$reference \)/.test(source)],
  ['submission requires a body-bound idempotency key and stale-safe lock', /Idempotency-Key/.test(source) && /hash\( 'sha256', \$request->get_body\(\) \)/.test(source) && /submit\|' \. \$event_key/.test(source)],
  ['idempotent replay returns the existing public reference', /_bds_inquiry_event_key[\s\S]*\$existing[\s\S]*get_post_meta\( \$existing\[0\], '_bds_inquiry_reference'/.test(source)],
  ['customer list is author-scoped and paginated', /'author' => \$user_id[\s\S]*'posts_per_page' => 50/.test(source)],
  ['cross-account item access returns 404', /post_author \) !== get_current_user_id\(\)[\s\S]*inquiry_not_found/.test(source)],
  ['status updates use optimistic versions and allowlists', /version_conflict/.test(source) && /bds_inquiries_statuses\(\)/.test(source)],
  ['status version check and write share a stale-safe lock', /update\|' \. \$inquiry_id[\s\S]*try \{[\s\S]*_bds_inquiry_version/.test(source)],
  ['monthly inquiry evidence is customer-owned and bounded', /function bds_inquiries_monthly_summary[\s\S]*'author' => absint\( \$user_id \)[\s\S]*posts_per_page' => 500/.test(source)],
  ['customer report uses real inquiry counts conditionally', /bds_inquiries_monthly_summary/.test(report) && /leads_captured/.test(report)],
  ['closed and lost inquiry PII has bounded retention', /BDS_CUSTOMER_INQUIRY_RETENTION_DAYS/.test(source) && /array\( 'lost', 'closed' \)/.test(source) && /delete_post_meta/.test(source)],
  ['inquiry lifecycle evidence excludes submitted content', /customer_inquiry_created/.test(lifecycle) && !/_bds_event_(?:email|phone|message)/.test(lifecycle)],
  ['inquiry service performs no outbound HTTP or messaging', !/wp_(?:safe_)?remote_(?:get|post|request)/.test(source) && !/wp_mail\(/.test(source)],
  ['all routes have explicit permission callbacks', (source.match(/'permission_callback' => 'bds_inquiries_(?:private|public)_permission'/g) || []).length === 5],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} customer inquiry contract check(s) failed.`);
  process.exit(1);
}
console.log('\nCustomer inquiry contract passed.');
