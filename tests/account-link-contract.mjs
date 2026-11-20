import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-account-link-mu.php', 'utf8');

const checks = [
  ['version is pinned', /BDS_ACCOUNT_LINK_VERSION', '0\.1\.1'/],
  ['only the two owned hosts are allowlisted', /'branddad\.social'[\s\S]*'directory\.branddad\.social'/],
  ['secret must be server configured and at least 32 bytes', /defined\( 'BDS_ACCOUNT_LINK_SECRET' \)[\s\S]*strlen\( BDS_ACCOUNT_LINK_SECRET \) >= 32/],
  ['token is HMAC SHA-256 signed', /hash_hmac\( 'sha256', \$body, BDS_ACCOUNT_LINK_SECRET, true \)/],
  ['signature uses constant-time comparison', /hash_equals\( \$expected, \$provided \)/],
  ['token is purpose bound', /'purpose'\s*=>\s*'branddad_account_link'[\s\S]*'branddad_account_link' !==/],
  ['token is issuer and audience bound', /'iss'\s*=>\s*\$issuer[\s\S]*'aud'\s*=>\s*\$audience[\s\S]*\$current !==/],
  ['token expires in five minutes', /'exp'\s*=>\s*\$now \+ 300/],
  ['email hash is verified', /hash_equals\( hash\( 'sha256', strtolower\( sanitize_email/],
  ['start requires login and nonce', /function bds_account_link_start[\s\S]*is_user_logged_in[\s\S]*check_admin_referer\( 'bds_account_link_start' \)/],
  ['source email ownership is fail-closed until verified', /function bds_account_link_email_verified[\s\S]*apply_filters\( 'bds_account_link_email_verified', false[\s\S]*bds_account_link_email_verified\( \$user \)/],
  ['token crosses sites in POST body not URL', /<form id="bds-account-link" method="post"[\s\S]*name="token"/],
  ['consumer is POST-only admin-post handler', /admin_post_nopriv_bds_account_link_consume/],
  ['replay id is atomically claimed', /add_option\( \$replay_key, absint\( \$data\['exp'\] \), '', false \)/],
  ['conflicting signed-in account is refused', /is_user_logged_in\(\) && \( ! \$user instanceof WP_User \|\| get_current_user_id\(\) !== absint\( \$user->ID \) \)/],
  ['conflicting account does not reopen the consumed bearer token', !/is_user_logged_in\(\)[\s\S]{0,250}delete_option\( \$replay_key \)/.test(source)],
  ['existing email is reused before account creation', /get_user_by\( 'email', \$email \)[\s\S]*if \( ! \$user instanceof WP_User \)/],
  ['new users receive random passwords', /wp_generate_password\( 32, true, true \)/],
  ['only safe return paths are accepted', (source.match(/'\/account-hub\/', '\/my-account\/', '\/add-listing\/'/g) || []).length >= 2],
  ['account cookie is set only after verification and mapping', /update_user_meta[\s\S]*wp_set_auth_cookie/],
  ['audit log excludes email and token', !/\$rows\[\]\s*=\s*array\([\s\S]{0,500}'email'/.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} account-link contract check(s) failed.`);
  process.exit(1);
}

console.log('\nAccount-link contract passed.');
