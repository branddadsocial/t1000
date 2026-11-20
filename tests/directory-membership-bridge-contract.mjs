import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-directory-membership-bridge-mu.php', 'utf8');
const checks = [
  ['bridge is versioned', /BDS_DIRECTORY_MEMBERSHIP_BRIDGE_VERSION', '0\.1\.1'/.test(source)],
  ['bridge requires the existing 32-byte shared secret', /strlen\( \$secret \) >= 32/.test(source)],
  ['membership signatures use a protocol-specific derived key', /branddad-directory-membership-v1/.test(source) && /bds_directory_membership_hmac_key\(\)/.test(source)],
  ['only explicit checkout consent is recorded', /bds_directory_membership_consent[\s\S]*array\( '1', 'yes', 'true', 'on' \)/.test(source)],
  ['only Business Essentials is eligible', /'ai-business-essentials' !== \( \$plan\['slug'\] \?\? '' \)/.test(source)],
  ['sender is pinned to BrandDad Social', /'branddad\.social' !== bds_directory_membership_host\(\)/.test(source)],
  ['receiver is pinned to the Directory host', /'directory\.branddad\.social' !== bds_directory_membership_host\(\)/.test(source)],
  ['remote endpoint is fixed and HTTPS', /https:\/\/directory\.branddad\.social\/wp-json\/branddad\/v1\/internal\/free-membership/.test(source)],
  ['outbound redirects and response size are bounded', /'redirection'\s*=>\s*0/.test(source) && /'limit_response_size'\s*=>\s*16384/.test(source)],
  ['payload is HMAC signed over timestamp and body hash', /hash_hmac\( 'sha256', \$timestamp \. "\\n" \. hash\( 'sha256', \$body \), bds_directory_membership_hmac_key\(\) \)/.test(source)],
  ['receiver uses constant-time signature verification', /hash_equals\( \$expected, \$signature \)/.test(source)],
  ['timestamp has a five-minute replay window', /abs\( time\(\) - absint\( \$timestamp \) \) > 300/.test(source)],
  ['request size is capped before mutation', /CONTENT_LENGTH[\s\S]*> 8192/.test(source)],
  ['event id is required in header and body', /hash_equals\( \$header_event, \$event_id \)/.test(source)],
  ['event result is idempotently persisted', /bds_directory_membership_[\s\S]*get_option\( \$result_key/.test(source)],
  ['stale event locks recover after five minutes', /get_option\( \$lock_key[\s\S]*time\(\) - 300/.test(source)],
  ['result persistence is checked before user notification', /add_option\( \$result_key, \$result[\s\S]*event_persist_failed[\s\S]*wp_new_user_notification/.test(source)],
  ['existing accounts are reused by email', /get_user_by\( 'email', \$email \)/.test(source)],
  ['new accounts receive random passwords', /wp_generate_password\( 32, true, true \)/.test(source)],
  ['only a private subscriber account is created', /'role'\s*=>\s*'subscriber'/.test(source) && !/wp_insert_post/.test(source)],
  ['retries are bounded and back off', /\$attempts < 3/.test(source) && /pow\( 2, \$attempts \)/.test(source)],
  ['no membership event runs without consent', /_bds_directory_membership_consent'.*'yes'/s.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} Directory membership bridge contract check(s) failed.`);
  process.exit(1);
}
console.log('\nDirectory membership bridge contract passed.');
