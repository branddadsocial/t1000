import fs from 'node:fs';
import path from 'node:path';

const worker = fs.readFileSync(path.resolve('deploy/bds-consent-followup-worker-mu.php'), 'utf8');
const helper = fs.readFileSync(path.resolve('deploy/bds-staging-followup-consent-acceptance-mu.php'), 'utf8');

const checks = [
  ['follow-up worker remains versioned', /BDS_CONSENT_FOLLOWUP_VERSION', '0\.1\.1'/m.test(worker)],
  ['delivery remains default off and sender gated', /BDS_FOLLOWUP_ENABLED[\s\S]*BDS_FOLLOWUP_FROM_EMAIL[\s\S]*is_email/m.test(worker)],
  ['helper is exact-host pinned', /BDS_STAGING_FOLLOWUP_HOST', 'staging-branddad-social\.stackstaging\.com'/m.test(helper)],
  ['host comes from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/m.test(helper)],
  ['automatic run is exact-host and one-time gated', /if \( ! bds_staging_followup_is_host\(\) \|\| get_option\( BDS_STAGING_FOLLOWUP_REPORT_OPTION/m.test(helper)],
  ['run lock has stale recovery', /bds_staging_followup_acceptance_lock_v1[\s\S]*time\(\) - 300/m.test(helper)],
  ['fixture uses reserved invalid email', /staging-followup@example\.invalid/m.test(helper)],
  ['test IP uses documentation-only range', /203\.0\.113\.224/m.test(helper)],
  ['fixture row key collision fails closed', /fixture_key_available[\s\S]*! \$row_existed[\s\S]*worker_enabled_or_fixture_collision/m.test(helper)],
  ['worker default-off state is asserted', /worker_default_off[\s\S]*false === bds_follow_worker_ready\(\)/m.test(helper)],
  ['source allowlist is exercised', /source_allowlist[\s\S]*trial[\s\S]*help[\s\S]*not-allowed/m.test(helper)],
  ['fresh consent eligibility is exercised', /fresh_consent_eligible[\s\S]*bds_follow_worker_row_eligible\( \$fixture_row/m.test(helper)],
  ['stale consent rejection is exercised', /31 \* DAY_IN_SECONDS[\s\S]*stale_consent_rejected/m.test(helper)],
  ['no-consent status and email rejection are exercised', /no_consent_rejected[\s\S]*bad_status_rejected[\s\S]*bad_email_rejected/m.test(helper)],
  ['token generation uses production helper', /bds_follow_worker_ensure_unsubscribe_token\( \$token_row \)/m.test(helper)],
  ['token shape and hash length are asserted', /token_shape[\s\S]*\^\[a-f0-9\]\{48\}\$[\s\S]*64 === strlen/m.test(helper)],
  ['stored fixture contains hash but not raw token', /token_hash_only_storage[\s\S]*hash\( 'sha256', \$token \)[\s\S]*unsubscribe_token_pending[\s\S]*strpos/m.test(helper)],
  ['valid and invalid token lookup are exercised', /token_lookup[\s\S]*bds_follow_worker_find_unsubscribe[\s\S]*invalid_token_rejected/m.test(helper)],
  ['unsubscribe URL is constrained to HTTPS staging admin-post action', /unsubscribe_url_safe[\s\S]*PHP_URL_SCHEME[\s\S]*BDS_STAGING_FOLLOWUP_HOST[\s\S]*action=bds_follow_unsubscribe/m.test(helper)],
  ['Directory listing destination remains fixed', /listing_destination_fixed[\s\S]*https:\/\/directory\.branddad\.social\/add-listing\//m.test(helper)],
  ['saved compare-and-set rejection preserves state', /saved_compare_and_set_rejected[\s\S]*wrong-saved[\s\S]*\$before_cas === get_option/m.test(helper)],
  ['status compare-and-set rejection preserves state', /status_compare_and_set_rejected[\s\S]*sending[\s\S]*\$before_cas === get_option/m.test(helper)],
  ['exact compare-and-set update is exercised', /exact_update_succeeds[\s\S]*source' => 'trial'[\s\S]*\$saved[\s\S]*interested/m.test(helper)],
  ['opt-out must persist consent zero and remove token fields', /opt_out_persisted[\s\S]*unsubscribed[\s\S]*consent' => 0[\s\S]*unsubscribe_hash[\s\S]*unsubscribe_token_pending/m.test(helper)],
  ['opt-out reactivation is rejected', /opt_out_reactivation_blocked[\s\S]*status' => 'interested'[\s\S]*consent' => 1/m.test(helper)],
  ['disabled worker no-op is asserted', /before_worker[\s\S]*bds_follow_worker_run\(\)[\s\S]*disabled_worker_noop/m.test(helper)],
  ['first thirty and thirty-first rate decisions are exercised', /for \( \$index = 0; \$index < 30; \$index\+\+ \)[\s\S]*first_thirty_rate_checks_allowed[\s\S]*thirty_first_rate_check_rejected/m.test(helper)],
  ['exact test row is snapshotted and restored', /original_row[\s\S]*bds_staging_followup_restore_row[\s\S]*fixture_row_restored/m.test(helper)],
  ['isolated rate counter is snapshotted and restored', /original_rate[\s\S]*bds_staging_followup_restore_rate[\s\S]*rate_counter_restored/m.test(helper)],
  ['row and rate restoration run in finally', /finally[\s\S]*bds_staging_followup_restore_row[\s\S]*bds_staging_followup_restore_rate[\s\S]*delete_option\( \$run_lock \)/m.test(helper)],
  ['server and current-user context are restored', /original_context_restored[\s\S]*original_remote[\s\S]*wp_set_current_user\( \$original_user \)/m.test(helper)],
  ['report requires all 26 checks', /26 === count\( \$checks \)[\s\S]*26 === count\( array_filter\( \$checks \) \)/m.test(helper)],
  ['report exposes allowlisted booleans only', /\$allowlist = array\([\s\S]*\$safe_checks\[ \$key \]/m.test(helper)],
  ['error codes are bounded', /array_slice\( \(array\) \$errors, 0, 10 \)[\s\S]*substr[\s\S]*0, 80/m.test(helper)],
  ['public report is exact-host gated and GET only', /bds_staging_followup_register_route[\s\S]*! bds_staging_followup_is_host\(\)[\s\S]*'methods'\s*=>\s*'GET'/m.test(helper)],
  ['helper accepts no browser request input', !/get_param|\$_GET|\$_POST|php:\/\/input/.test(helper)],
  ['helper creates or deletes no user lead order subscription or payment', !/wp_create_user|wp_insert_user|wp_insert_post|wp_delete_post|wp_delete_user|wc_create_order|payment_complete/.test(helper)],
  ['helper never sends email or a message', !/wp_mail|send_message|messages\.create/.test(helper)],
  ['helper invokes no third-party provider or outbound HTTP', !/formwise|api\.openai|stripe\.com|wp_(?:safe_)?remote_(?:get|post|request)/.test(helper)],
  ['helper does not invoke public unsubscribe handler', !/bds_follow_worker_unsubscribe\s*\(/.test(helper)],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging follow-up consent acceptance contract checks`);
