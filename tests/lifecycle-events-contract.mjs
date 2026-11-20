import fs from 'node:fs';
import process from 'node:process';

const lifecycle = fs.readFileSync('deploy/bds-lifecycle-events-mu.php', 'utf8');
const trial = fs.readFileSync('deploy/bds-ai-trial-mu.php', 'utf8');
const subscriptions = fs.readFileSync('deploy/bds-ai-subscriptions-mu.php', 'utf8');
const execution = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');
const membership = fs.readFileSync('deploy/bds-directory-membership-bridge-mu.php', 'utf8');
const onboarding = fs.readFileSync('deploy/bds-onboarding-api-mu.php', 'utf8');
const featured = fs.readFileSync('deploy/bds-featured-billing-adapter-mu.php', 'utf8');

const checks = [
  ['ledger is versioned', /BDS_LIFECYCLE_EVENTS_VERSION', '0\.4\.0'/.test(lifecycle)],
  ['event writes use an HMAC idempotency key', /hash_hmac\( 'sha256'/.test(lifecycle) && /_bds_event_key/.test(lifecycle)],
  ['write lock has stale recovery', /bds_evt_lock_[\s\S]*time\(\) - 60/.test(lifecycle)],
  ['stored metadata is allowlisted and omits email IP and raw content', /\$allowed = array\(/.test(lifecycle) && !/_bds_event_(?:email|ip|message|content)/.test(lifecycle)],
  ['currency is validated before storage', /\^\[A-Z\]\{3\}\$/.test(lifecycle)],
  ['trial emits only after persistent history', /update_user_meta\([\s\S]*_bds_ai_trial_history_v1[\s\S]*do_action\( 'bds_ai_trial_activated'/.test(trial)],
  ['payment emits only after successful provisioning', /BrandDad AI access provisioned[\s\S]*do_action\( 'bds_ai_access_provisioned'/.test(subscriptions)],
  ['idempotent paid replay can repair a missing analytics event', /_bds_ai_last_event_key[\s\S]*_bds_ai_allowance_order_id[\s\S]*do_action\( 'bds_ai_access_provisioned'/.test(subscriptions)],
  ['revocation emits only after access removal is persisted', /_bds_ai_access_removed', gmdate[\s\S]*do_action\( 'bds_ai_access_revoked'/.test(subscriptions)],
  ['AI completion emits only when metering commits', /if \( ! \$committed \)[\s\S]*completed_metering_review[\s\S]*else[\s\S]*do_action\( 'bds_ai_job_completed'/.test(execution)],
  ['Directory linking emits after replay-safe result persistence', /add_option\( \$result_key[\s\S]*event_persist_failed[\s\S]*do_action\( 'bds_directory_member_linked'/.test(membership)],
  ['idempotent Directory replay can repair a missing analytics event', /! empty\( \$existing\['user_id'\] \)[\s\S]*do_action\( 'bds_directory_member_linked'/.test(membership)],
  ['onboarding emits from a completed response', /! empty\( \$response\['complete'\] \)[\s\S]*do_action\( 'bds_onboarding_completed'/.test(onboarding)],
  ['onboarding completion is idempotent per user', /onboarding-complete-' \. absint\( \$user_id \)/.test(lifecycle)],
  ['refunds are recorded by refund id', /woocommerce_order_refunded/.test(lifecycle) && /'refund-' \. \$refund->get_id\(\)/.test(lifecycle)],
  ['refunds require a matching tracked payment', /bds_lifecycle_has_event\( 'payment_confirmed'/.test(lifecycle)],
  ['Featured payment emits only after the grant is saved', /bds_feat_save_grants\( \$grants \)[\s\S]*do_action\( 'bds_featured_access_provisioned'/.test(featured)],
  ['Featured analytics accepts only approved lane levels', /array\( 'local', 'regional', 'national', 'global' \)/.test(lifecycle)],
  ['admin analytics requires WooCommerce management capability', /current_user_can\( 'manage_woocommerce' \)/.test(lifecycle)],
  ['report period is strict and bounded to 24 months', /\^\(20\\d\{2\}\)-\(0\[1-9\]\|1\[0-2\]\)\$/.test(lifecycle) && /modify\( '-23 months' \)/.test(lifecycle)],
  ['event query is bounded and exposes truncation', /'posts_per_page' => 5000/.test(lifecycle) && /'truncated' => count\( \$ids \) >= 5000/.test(lifecycle)],
  ['revenue separates gross refunds and net', /confirmed_gross_minor/.test(lifecycle) && /refunds_minor/.test(lifecycle) && /net_confirmed_minor/.test(lifecycle)],
  ['report does not claim MRR profit or bank settlement', /not MRR, profit, or a bank settlement report/.test(lifecycle)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} lifecycle event contract check(s) failed.`);
  process.exit(1);
}
console.log('\nLifecycle event contract passed.');
