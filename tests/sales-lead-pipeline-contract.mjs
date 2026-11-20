import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-sales-lead-pipeline-mu.php', 'utf8');
const follow = fs.readFileSync('deploy/bds-consent-followup-worker-mu.php', 'utf8');
const lifecycle = fs.readFileSync('deploy/bds-lifecycle-events-mu.php', 'utf8');

const checks = [
  ['pipeline is versioned', /BDS_SALES_LEAD_PIPELINE_VERSION', '0\.1\.0'/.test(source)],
  ['public capture defaults off', /BDS_LEAD_CAPTURE_ENABLED[\s\S]*true === BDS_LEAD_CAPTURE_ENABLED/.test(source)],
  ['lead post type is private and excluded from REST', /register_post_type\([\s\S]*'public'\s*=>\s*false[\s\S]*'show_in_rest'\s*=>\s*false/.test(source)],
  ['browser origins are restricted to owned HTTPS hosts', /'https' === \$scheme[\s\S]*branddad\.social[\s\S]*directory\.branddad\.social/.test(source)],
  ['raw public body is capped', /CONTENT_LENGTH[\s\S]*> 8192/.test(source)],
  ['client rate keys are HMAC hashed and not stored on leads', /bds_lead_client_[\s\S]*hash_hmac\( 'sha256'/.test(source) && !/_bds_lead_ip/.test(source)],
  ['client and configurable global rate limits are enforced under a stale-safe lock', /bds_lead_rate_lock[\s\S]*time\(\) - 30/.test(source) && /BDS_LEAD_CAPTURE_DAILY_LIMIT[\s\S]*\$current >= 5[\s\S]*\$total >= \$daily_limit/.test(source)],
  ['public capture requires privacy acknowledgement', /\$privacy_ack[\s\S]*! \$privacy_ack/.test(source)],
  ['signed-in capture cannot attach another account email', /email_mismatch[\s\S]*signed-in account/.test(source)],
  ['business and need fields are bounded', /strlen\( \$business \) > 120/.test(source) && /strlen\( \$need \) > 240/.test(source)],
  ['email deduplication uses a keyed HMAC', /function bds_lead_email_hash[\s\S]*hash_hmac\( 'sha256'/.test(source)],
  ['lead writes use an email-scoped stale-safe lock', /bds_lead_' \. substr\( \$hash[\s\S]*time\(\) - 60/.test(source)],
  ['public callers cannot submit a lead stage', /function bds_lead_capture\([\s\S]*\$stage\s*=\s*\$user_id \? 'registered' : 'interested'/.test(source)],
  ['checkout and payment stages come from authoritative Woo hooks', /woocommerce_checkout_order_processed/.test(source) && /bds_ai_access_provisioned/.test(source)],
  ['trial onboarding and revocation use verified backend hooks', /bds_ai_trial_activated/.test(source) && /bds_onboarding_completed/.test(source) && /bds_ai_access_revoked/.test(source)],
  ['email consent is separate and mirrored only when explicitly true', /! empty\( \$fields\['email_consent'\] \)[\s\S]*bds_lead_mirror_email_consent/.test(source)],
  ['marketing consent fails closed until email ownership is verified', /bds_lead_email_verified', false[\s\S]*\$email_consent && \$email_verified/.test(source)],
  ['unverified consent is recorded as requested but cannot enter sender queue', /email_consent_requested[\s\S]*email_confirmation_required/.test(source)],
  ['an existing unsubscribe cannot be silently reactivated', /'unsubscribed' === \( \$current\['status'\]/.test(source)],
  ['follow-up remains a separate default-off worker', /BDS_FOLLOWUP_ENABLED/.test(follow) && /true === BDS_FOLLOWUP_ENABLED/.test(follow)],
  ['unsubscribe is propagated back to the lead', /do_action\( 'bds_follow_unsubscribed'/.test(follow) && /add_action\( 'bds_follow_unsubscribed'/.test(source)],
  ['admin lead API requires Woo management capability', /function bds_lead_admin_permission[\s\S]*manage_woocommerce/.test(source)],
  ['admin list is paginated and bounded', /posts_per_page' => 50/.test(source) && /min\( 1000/.test(source)],
  ['retention is configurable but bounded', /BDS_LEAD_RETENTION_DAYS/.test(source) && /min\( 365, max\( 30/.test(source)],
  ['stale unconverted PII is removed in bounded batches', /posts_per_page' => 100/.test(source) && /delete_post_meta/.test(source) && /bds_lead_retention_continue/.test(source)],
  ['unsubscribe suppression survives contact anonymization', /\$preserve_suppression[\s\S]*_bds_lead_email_hash/.test(source)],
  ['lead lifecycle events are internal and idempotent', /bds_sales_lead_created/.test(lifecycle) && /lead-stage-' \. absint\( \$lead_id \)/.test(lifecycle)],
  ['lead pipeline performs no outbound HTTP or email send', !/wp_(?:safe_)?remote_(?:get|post|request)/.test(source) && !/wp_mail\(/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} sales lead pipeline contract check(s) failed.`);
  process.exit(1);
}
console.log('\nSales lead pipeline contract passed.');
