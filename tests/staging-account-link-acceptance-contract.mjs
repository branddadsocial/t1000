import fs from 'node:fs';
import path from 'node:path';

const account = fs.readFileSync(path.resolve('deploy/bds-account-link-mu.php'), 'utf8');
const helper = fs.readFileSync(path.resolve('deploy/bds-staging-account-link-acceptance-mu.php'), 'utf8');

const checks = [
  ['account link version advances', /BDS_ACCOUNT_LINK_VERSION', '0\.1\.2'/m.test(account)],
  ['current-host candidate is normalized and revalidated against fixed hosts', /strtolower\( sanitize_text_field[\s\S]*bds_account_link_current_host_candidate[\s\S]*\$hosts\s*=\s*bds_account_link_hosts\(\)[\s\S]*isset\( \$hosts\[ \$candidate \] \)/m.test(account)],
  ['helper is exact-host pinned', /BDS_STAGING_ACCOUNT_LINK_HOST', 'staging-branddad-social\.stackstaging\.com'/m.test(helper)],
  ['host comes from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/m.test(helper)],
  ['test secret exists only before exact-host report', /bds_staging_account_link_is_host\(\)[\s\S]*! get_option\( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION[\s\S]*! defined\( 'BDS_ACCOUNT_LINK_SECRET' \)[\s\S]*hash\( 'sha256'/m.test(helper)],
  ['protocol host candidate comes only from request-local global', /bds_staging_account_link_current_host_candidate[\s\S]*bds_staging_account_link_protocol_host/m.test(helper)],
  ['email verification is limited to marker A non-admin', /bds_staging_account_link_email_verified[\s\S]*_bds_staging_auth_fixture[\s\S]*'a'[\s\S]*! user_can/m.test(helper)],
  ['automatic run is exact-host and one-time gated', /if \( ! bds_staging_account_link_is_host\(\) \|\| get_option\( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION/m.test(helper)],
  ['run lock has stale recovery', /bds_staging_account_link_acceptance_lock_v1[\s\S]*time\(\) - 300/m.test(helper)],
  ['both fixtures are marker verified and non-admin', /_bds_staging_auth_fixture[\s\S]*'a'[\s\S]*_bds_staging_auth_fixture[\s\S]*'b'[\s\S]*! user_can\( \$user_a[\s\S]*! user_can\( \$user_b/m.test(helper)],
  ['secret readiness and exact acceptance length are asserted', /acceptance_secret_ready[\s\S]*bds_account_link_secret_ready\(\)[\s\S]*64 === strlen/m.test(helper)],
  ['verification filter is scoped to A and refuses B', /email_verification_scoped[\s\S]*bds_account_link_email_verified\( \$a \)[\s\S]*! bds_account_link_email_verified\( \$b \)/m.test(helper)],
  ['arbitrary host candidate is rejected to actual staging host', /attacker\.example[\s\S]*arbitrary_host_candidate_rejected[\s\S]*BDS_STAGING_ACCOUNT_LINK_HOST/m.test(helper)],
  ['same-host audience is rejected', /same_host_audience_rejected[\s\S]*link_unavailable/m.test(helper)],
  ['production token creation targets Directory', /bds_account_link_create_token\( \$user_a, 'directory\.branddad\.social' \)/m.test(helper)],
  ['token shape is bounded and URL-safe', /token_shape[\s\S]*strlen\( \$token \) < 4096[\s\S]*\^\[A-Za-z0-9_-\]/m.test(helper)],
  ['payload claims are checked without report exposure', /payload_claims_bounded[\s\S]*branddad_account_link[\s\S]*wp_is_uuid[\s\S]*branddad\.social[\s\S]*directory\.branddad\.social[\s\S]*source_uid[\s\S]*email_hash/m.test(helper)],
  ['five-minute lifetime is exact', /five_minute_expiry[\s\S]*=== 300/m.test(helper)],
  ['wrong audience is rejected before valid destination verify', /wrong_audience_rejected[\s\S]*branddad\.social[\s\S]*directory\.branddad\.social[\s\S]*valid_destination_verification/m.test(helper)],
  ['signature tampering is exercised', /tampered_signature_rejected[\s\S]*substr\( \$token, 0, -1 \)/m.test(helper)],
  ['expired and future timestamps are exercised', /time\(\) - 301[\s\S]*expired_token_rejected[\s\S]*time\(\) \+ 31[\s\S]*future_token_rejected/m.test(helper)],
  ['purpose email hash and issuer tampering are exercised', /wrong_purpose_rejected[\s\S]*wrong_email_hash_rejected[\s\S]*wrong_issuer_rejected/m.test(helper)],
  ['oversized and malformed tokens are exercised', /oversized_token_rejected[\s\S]*4097[\s\S]*not_json[\s\S]*malformed_body_rejected/m.test(helper)],
  ['replay key is derived only from token jti', /bds_account_link_used_[\s\S]*hash\( 'sha256', \(string\) \$payload\['jti'\] \)/m.test(helper)],
  ['first replay claim and second denial are exercised', /first_replay_claim_succeeds[\s\S]*add_option[\s\S]*second_replay_claim_rejected[\s\S]*false === add_option/m.test(helper)],
  ['exact replay key is cleaned in normal and finally paths', /replay_claim_cleaned[\s\S]*delete_option\( \$replay_key \)[\s\S]*finally[\s\S]*delete_option\( \$replay_key \)/m.test(helper)],
  ['existing email resolves fixture A without account creation', /existing_email_resolves_fixture[\s\S]*get_user_by\( 'email'[\s\S]*\$user_a/m.test(helper)],
  ['conflicting fixture B session is detected without consume', /wp_set_current_user\( \$user_b \)[\s\S]*conflicting_session_detected/m.test(helper)],
  ['safe start form is checked for POST nonce destination and no bearer token', /start_form_safe[\s\S]*method="post[\s\S]*name="action" value="bds_account_link_start[\s\S]*directory\.branddad\.social[\s\S]*add-listing[\s\S]*name="token/m.test(helper)],
  ['unverified fixture B form is hidden', /unverified_form_hidden[\s\S]*bds_account_link_form/m.test(helper)],
  ['audit row exact keys and privacy are asserted', /audit_row_privacy_bounded[\s\S]*array\( 'action', 'user_id', 'source', 'at' \)[\s\S]*sanitize_email[\s\S]*\$token/m.test(helper)],
  ['audit option is snapshotted and restored', /audit_value[\s\S]*bds_staging_account_link_restore_audit[\s\S]*audit_option_restored/m.test(helper)],
  ['original user and host context are restored', /original_context_restored[\s\S]*wp_set_current_user\( \$original_user \)[\s\S]*BDS_STAGING_ACCOUNT_LINK_HOST/m.test(helper)],
  ['no-cross-host-request evidence is explicit and helper has no HTTP client', /no_cross_host_request'\] = true/m.test(helper) && !/wp_(?:safe_)?remote_(?:get|post|request)/.test(helper)],
  ['report requires all 30 checks', /30 === count\( \$checks \)[\s\S]*30 === count\( array_filter\( \$checks \) \)/m.test(helper)],
  ['report exposes allowlisted booleans only', /\$allowlist = array\([\s\S]*\$safe_checks\[ \$key \]/m.test(helper)],
  ['error codes are bounded', /array_slice\( \(array\) \$errors, 0, 10 \)[\s\S]*substr[\s\S]*0, 80/m.test(helper)],
  ['public report is exact-host gated and GET only', /bds_staging_account_link_register_route[\s\S]*! bds_staging_account_link_is_host\(\)[\s\S]*'methods'\s*=>\s*'GET'/m.test(helper)],
  ['helper accepts no browser request input', !/get_param|\$_GET|\$_POST|php:\/\/input/.test(helper)],
  ['helper creates no user order subscription payment or auth cookie', !/wp_insert_user|wp_create_user|wc_create_order|payment_complete|wp_set_auth_cookie/.test(helper)],
  ['helper performs no redirect message or provider call', !/wp_safe_redirect|wp_mail|send_message|formwise|api\.openai|stripe\.com/.test(helper)],
  ['helper does not invoke start or consume handlers', !/bds_account_link_(?:start|consume)\s*\(/.test(helper)],
  ['helper does not mutate user metadata', !/update_user_meta|delete_user_meta/.test(helper)],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging account-link acceptance contract checks`);
