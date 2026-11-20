import fs from 'node:fs';
import path from 'node:path';

const inquiries = fs.readFileSync(path.resolve('deploy/bds-customer-inquiries-mu.php'), 'utf8');
const helper = fs.readFileSync(path.resolve('deploy/bds-staging-customer-inquiries-acceptance-mu.php'), 'utf8');

const checks = [
  ['inquiry version advances', /BDS_CUSTOMER_INQUIRIES_VERSION', '0\.1\.1'/m.test(inquiries)],
  ['plan candidate filter is internal', /apply_filters\( 'bds_inquiries_plan_candidate'/m.test(inquiries)],
  ['filtered plan is revalidated as an array', /\$plan = is_array\( \$plan \) \? \$plan : array\(\)/m.test(inquiries)],
  ['filtered plan slug remains allowlisted', /in_array\( \$slug, \$allowed, true \)/m.test(inquiries)],
  ['filtered plan mode must remain paid', /'paid' === \$mode/m.test(inquiries)],
  ['helper is pinned to canonical staging host', /BDS_STAGING_INQUIRIES_HOST', 'staging-branddad-social\.stackstaging\.com'/m.test(helper)],
  ['host comes from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/m.test(helper)],
  ['feature is enabled only before exact-host report exists', /bds_staging_inquiries_is_host\(\)[\s\S]*! get_option\( BDS_STAGING_INQUIRIES_REPORT_OPTION[\s\S]*define\( 'BDS_CUSTOMER_INQUIRIES_ENABLED', true \)/m.test(helper)],
  ['synthetic plan is limited to marked fixture B', /'b' !== \(string\) get_user_meta[\s\S]*_bds_staging_auth_fixture/m.test(helper)],
  ['synthetic plan refuses administrators', /user_can\( \$user_id, 'manage_options' \)/m.test(helper)],
  ['synthetic plan uses an inquiry allowlist slug', /'slug'\s*=>\s*'ai-lead-assistant'/m.test(helper)],
  ['synthetic plan is explicitly paid', /'access_mode'\s*=>\s*'paid'/m.test(helper)],
  ['automatic run is exact-host and one-time gated', /if \( ! bds_staging_inquiries_is_host\(\) \|\| get_option\( BDS_STAGING_INQUIRIES_REPORT_OPTION/m.test(helper)],
  ['run lock has stale recovery', /bds_staging_inquiries_acceptance_lock_v1[\s\S]*time\(\) - 300/m.test(helper)],
  ['both fixture markers are verified', /_bds_staging_auth_fixture[\s\S]*'a'[\s\S]*_bds_staging_auth_fixture[\s\S]*'b'/m.test(helper)],
  ['fixtures cannot be administrators', /! user_can\( \$user_a, 'manage_options' \)[\s\S]*! user_can\( \$user_b, 'manage_options' \)/m.test(helper)],
  ['anonymous denial is exercised', /wp_set_current_user\( 0 \)[\s\S]*false === bds_inquiries_private_permission\(\)/m.test(helper)],
  ['synthetic plan is revalidated by production code', /\$plan = bds_inquiries_plan\( \$user_b \)[\s\S]*ai-lead-assistant[\s\S]*paid/m.test(helper)],
  ['form reference is random opaque and tested', /form_enabled_with_opaque_reference[\s\S]*\^\[a-z0-9\]\{32\}\$/m.test(helper)],
  ['ambiguous reference resolution is tested', /update_user_meta\( \$user_a, '_bds_inquiry_form_ref'[\s\S]*ambiguous_form_reference_rejected/m.test(helper)],
  ['invalid origin is tested', /https:\/\/untrusted\.example[\s\S]*origin_not_allowed/m.test(helper)],
  ['body size limit is tested', /CONTENT_LENGTH'\] = 12289[\s\S]*request_too_large/m.test(helper)],
  ['only owned BrandDad origin is used for valid submission', /set_header\( 'Origin', 'https:\/\/branddad\.social' \)/m.test(helper)],
  ['honeypot privacy and contact consent failures are tested', /company_website[\s\S]*privacy_ack[\s\S]*contact_consent[\s\S]*honeypot_rejected[\s\S]*privacy_required[\s\S]*contact_consent_required/m.test(helper)],
  ['idempotency requirement is tested', /Idempotency-Key[\s\S]*invalid_idempotency_key/m.test(helper)],
  ['fake contact data uses reserved invalid domain', /visitor@example\.invalid/m.test(helper)],
  ['test IP uses documentation-only range', /203\.0\.113\.222/m.test(helper)],
  ['created record must be private and fixture-owned', /valid_inquiry_private_owned[\s\S]*post_author[\s\S]*private/m.test(helper)],
  ['public response is checked for contact and internal-id leakage', /public_response_safe[\s\S]*visitor@example\.invalid[\s\S]*555[\s\S]*private staging inquiry[\s\S]*"id"/m.test(helper)],
  ['idempotent replay must reuse one record', /replay_reuses_reference[\s\S]*replay_single_record/m.test(helper)],
  ['owner list inclusion is tested', /owner_list_includes_inquiry[\s\S]*in_array\( \$inquiry_id, \$owner_ids, true \)/m.test(helper)],
  ['cross-account list and update denial are tested', /cross_account_list_excludes_inquiry[\s\S]*cross_account_update_is_404[\s\S]*inquiry_not_found/m.test(helper)],
  ['active update lock is tested', /active_update_lock_rejected[\s\S]*inquiry_busy/m.test(helper)],
  ['version conflict and status allowlist are tested', /version_conflict_rejected[\s\S]*version_conflict[\s\S]*invalid_status_rejected[\s\S]*invalid_status/m.test(helper)],
  ['valid status update increments version', /valid_status_update[\s\S]*qualified[\s\S]*2 ===/m.test(helper)],
  ['stale update lock recovery is tested', /time\(\) - 61[\s\S]*stale_update_lock_recovered/m.test(helper)],
  ['monthly and value report deltas are exact', /monthly_report_delta_correct[\s\S]*value_report_delta_correct[\s\S]*customer_inquiries/m.test(helper)],
  ['old closed fixture is retention-tested', /366 \* DAY_IN_SECONDS[\s\S]*_bds_inquiry_status', 'closed'[\s\S]*bds_inquiries_retention_run\(\)[\s\S]*retention_anonymizes_closed_fixture/m.test(helper)],
  ['recent inquiry PII must remain present until cleanup', /recent_inquiry_pii_preserved[\s\S]*visitor@example\.invalid/m.test(helper)],
  ['first five and sixth rate decisions are tested', /for \( \$index = 0; \$index < 5; \$index\+\+ \)[\s\S]*first_five_rate_checks_allowed[\s\S]*sixth_rate_check_rejected[\s\S]*rate_limited/m.test(helper)],
  ['rate counters are snapshotted and restored', /original_client_rate[\s\S]*original_global_rate[\s\S]*rate_counters_restored/m.test(helper)],
  ['fixture metadata is snapshotted', /bds_staging_inquiries_snapshot_user_meta/m.test(helper)],
  ['fixture metadata is restored', /bds_staging_inquiries_restore_user_meta[\s\S]*fixture_metadata_restored/m.test(helper)],
  ['cleanup only deletes marked private inquiry fixtures owned by fixture B', /bds_staging_inquiries_cleanup_posts[\s\S]*bds_customer_inq[\s\S]*post_author[\s\S]*_bds_staging_inquiry_fixture[\s\S]*wp_delete_post\( \$post_id, true \)/m.test(helper)],
  ['cleanup runs in finally', /finally[\s\S]*bds_staging_inquiries_cleanup_posts[\s\S]*bds_staging_inquiries_restore_user_meta[\s\S]*delete_transient[\s\S]*delete_option\( \$run_lock \)/m.test(helper)],
  ['server state and current user are restored', /original_remote[\s\S]*original_content_length[\s\S]*wp_set_current_user\( \$original_user \)/m.test(helper)],
  ['report requires all 34 checks', /34 === count\( \$checks \)[\s\S]*34 === count\( array_filter\( \$checks \) \)/m.test(helper)],
  ['report exposes allowlisted booleans only', /\$allowlist = array\([\s\S]*\$safe_checks\[ \$key \]/m.test(helper)],
  ['error codes are bounded', /array_slice\( \(array\) \$errors, 0, 10 \)[\s\S]*substr[\s\S]*0, 80/m.test(helper)],
  ['public report is exact-host gated', /function bds_staging_inquiries_register_route\(\)[\s\S]*! bds_staging_inquiries_is_host\(\)/m.test(helper)],
  ['public report is read-only', /'methods'\s*=>\s*'GET'/m.test(helper)],
  ['helper accepts no browser request input', !/get_param|\$_GET|\$_POST|php:\/\/input/.test(helper)],
  ['helper creates no user admin order subscription or payment', !/wp_create_user|wp_insert_user|set_role|add_cap|wc_create_order|payment_complete/.test(helper)],
  ['helper sends no email or message', !/wp_mail|send_message|messages\.create/.test(helper)],
  ['helper invokes no third-party provider', !/formwise|api\.openai|stripe\.com/.test(helper)],
  ['helper only hard-deletes exact marked test inquiry posts', (helper.match(/wp_delete_post/g) || []).length === 1],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging customer-inquiries acceptance contract checks`);
