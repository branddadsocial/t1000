import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-consent-followup-worker-mu.php', 'utf8');
const checks = [
  ['version is pinned', /BDS_CONSENT_FOLLOWUP_VERSION', '0\.1\.1'/],
  ['delivery defaults off', /defined\( 'BDS_FOLLOWUP_ENABLED' \)[\s\S]*true === BDS_FOLLOWUP_ENABLED/],
  ['verified sender is required', /defined\( 'BDS_FOLLOWUP_FROM_EMAIL' \)[\s\S]*is_email\( BDS_FOLLOWUP_FROM_EMAIL \)/],
  ['existing consent list is the only source', /get_option\( 'bds_follow_consent_v1'/],
  ['existing authoritative copy function is reused', /function_exists\( 'bds_follow_message' \)[\s\S]*bds_follow_message\( \$source \)/],
  ['fresh explicit consent is rechecked', /function bds_follow_worker_row_eligible[\s\S]*30 \* DAY_IN_SECONDS[\s\S]*Re-read consent immediately before the side effect/],
  ['send transition uses compare-and-set status', /\$expected_status[\s\S]*hash_equals[\s\S]*'interested'\s*\n\s*\)/],
  ['converted customers are stopped', /function bds_follow_worker_has_converted[\s\S]*bds_ai_exec_active_plan[\s\S]*'converted'/],
  ['batch and retry counts are bounded', /\$processed >= 20[\s\S]*\$attempts >= 3/],
  ['mail failure uses bounded backoff', /min\( DAY_IN_SECONDS, HOUR_IN_SECONDS \* \( 2 \*\* \$attempts \) \)/],
  ['unsubscribe tokens are random and stored hashed', /random_bytes\( 24 \)[\s\S]*hash\( 'sha256', \$token \)/],
  ['unsubscribe lookup uses constant-time comparison', /hash_equals\( \(string\) \$row\['unsubscribe_hash'\], \$hash \)/],
  ['unsubscribe attempts are rate limited', /function bds_follow_worker_unsubscribe_rate_limit[\s\S]*\$n >= 30/],
  ['one-click unsubscribe and visible confirmation are supported', /List-Unsubscribe=One-Click[\s\S]*'unsubscribed'/],
  ['successful opt-out clears consent', /'status' => 'unsubscribed', 'consent' => 0, 'unsubscribed_at'/],
  ['opt-out reports success only after persistence', /\$updated = bds_follow_worker_update_row[\s\S]*if \( ! \$updated \)[\s\S]*503/],
  ['only server-verified destinations are used', /directory\.branddad\.social\/add-listing[\s\S]*bds_ai_commercial_checkout_url\( 'ai-business-essentials' \)/],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} consent follow-up contract check(s) failed.`);
  process.exit(1);
}
console.log('\nConsent follow-up worker contract passed.');
