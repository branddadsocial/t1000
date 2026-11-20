import fs from 'node:fs';
import process from 'node:process';

const trial = fs.readFileSync('deploy/bds-ai-trial-mu.php', 'utf8');
const exec = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');
const onboarding = fs.readFileSync('deploy/bds-onboarding-api-mu.php', 'utf8');

const checks = [
  ['trial version is explicit', /BDS_AI_TRIAL_VERSION', '0\.1\.0'/.test(trial)],
  ['only the approved Essentials product can create trial access', /BDS_AI_TRIAL_PRODUCT_SLUG', 'branddad-ai-business-essentials'/.test(trial)],
  ['trial has a bounded 25-credit allowance', /BDS_AI_TRIAL_CREDITS', 25/.test(trial)],
  ['trial uses the plugin authoritative misspelled end metadata', /wps_susbcription_trial_end/.test(trial)],
  ['trial requires exact seven-day terms copied to subscription', /7 === \$number && 'day' === \$interval/.test(trial)],
  ['trial requires active subscription status and valid dates', /'active' === \$status && \$start > 0 && \$end > \$start && \$now < \$end/.test(trial)],
  ['trial order and customer ownership are cross-checked', /\$user_id !== absint\( \$order->get_user_id\(\) \)/.test(trial)],
  ['repeat activation is denied without deleting history', /_bds_ai_trial_denied/.test(trial) && /_bds_ai_trial_history_v1/.test(trial)],
  ['repeat add-to-cart is blocked for signed-in customers', /woocommerce_add_to_cart_validation/.test(trial) && /'eligible' !== \$state\['status'\]/.test(trial)],
  ['AI checkout requires a linked account', /woocommerce_checkout_registration_required/.test(trial)],
  ['trial API requires authenticated WordPress session', /return is_user_logged_in\(\)/.test(trial)],
  ['paid access is preferred over trial access', /bds_ai_exec_active_paid_plan[\s\S]*bds_ai_trial_plan/.test(exec)],
  ['trial ledger cannot collide with paid renewal ledger', /\? '_trial' : ''/.test(exec)],
  ['trial tool allowlist is enforced server-side', /trial_tool_unavailable/.test(exec)],
  ['jobs preserve the resolved access mode', /_bds_access_mode', \$resolved_mode/.test(exec)],
  ['onboarding reports trial rather than paid', /paid_plan\['access_mode'\]/.test(onboarding)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} AI trial contract check(s) failed.`);
  process.exit(1);
}
console.log('\nAI trial contract passed.');
