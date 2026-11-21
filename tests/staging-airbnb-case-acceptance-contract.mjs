import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-airbnb-case-acceptance-mu.php', 'utf8');
const design = fs.readFileSync('BRANDDAD_AIRBNB_REVIEW_ASSISTANT_DESIGN.md', 'utf8');

const checks = [
  ['helper is locked to the exact isolated staging host', /BDS_STAGING_AIRBNB_ACCEPTANCE_HOST', 'staging-branddad-social\.stackstaging\.com'/],
  ['helper runs once behind a versioned report option and lock', /! get_option\( BDS_STAGING_AIRBNB_ACCEPTANCE_REPORT[\s\S]*add_option\( \$lock, BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER/],
  ['fixture identity requires the non-admin staging marker', /username_exists\( 'bds_stage_acceptance_a' \)[\s\S]*_bds_staging_auth_fixture[\s\S]*! user_can\( \$user_id, 'manage_options' \)/],
  ['mail is suppressed only while the synthetic run is active', /pre_wp_mail[\s\S]*bds_staging_airbnb_acceptance_pending\(\)[\s\S]*bds_staging_airbnb_acceptance_active/],
  ['provider fixture is exact-host and one-time only', /BDS_STAGING_AIRBNB_ACCEPTANCE_KEY[\s\S]*bds_staging_airbnb_acceptance_pending\(\)[\s\S]*BDS_FORMWISE_EXECUTION_KEY/],
  ['provider fixture validates its bearer token and request inputs', /hash_equals\( 'Bearer ' \. BDS_STAGING_AIRBNB_ACCEPTANCE_KEY[\s\S]*\$body\['inputs'\]/],
  ['provider fixture returns a bounded synthetic result without a network request', /Synthetic acceptance result[\s\S]*'response'\s*=>\s*array\( 'code' => 200/],
  ['successful job uses the exact production REST handler and schema', /new WP_REST_Request\( 'POST', '\/branddad\/v1\/ai\/jobs' \)[\s\S]*'airbnb_review_appeal'[\s\S]*'Yes, I am authorized'/],
  ['synthetic orders use a no-gateway payment method', /bds_staging_synthetic_no_gateway[\s\S]*set_status\( 'completed' \)/],
  ['immutable one-case product identity is stored on the order item', /_bds_ai_case_product_slug'[\s\S]*branddad-ai-review-dispute-assistant/],
  ['successful provider output is checked but not persisted in the public report', /bds_ai_exec_process_job\( \$primary_job_id \)[\s\S]*result_bounded_nonempty/],
  ['duplicate use is denied after the successful case', /duplicate_case_denied[\s\S]*case_purchase_required[\s\S]*case_already_used/],
  ['a full synthetic refund must block case access', /wc_create_refund[\s\S]*refund_payment' => false[\s\S]*refund_blocks_case/],
  ['forced failure intercepts only the exact FormWise flow URL', /https:\/\/builder\.formwise\.ai\/api\/v1\/flows\/86c87c94-2054-4641-87e1-2a70a0a9b1d4\/run[\s\S]*bds_staging_forced_provider_failure/],
  ['failed provider execution must release the exact case', /provider_network_uncertain[\s\S]*failed_case_released[\s\S]*failure_order->get_id/],
  ['scheduled jobs are unscheduled before fixture deletion', /as_unschedule_all_actions\( 'bds_ai_exec_process_job'[\s\S]*wp_clear_scheduled_hook/],
  ['each staged job is unscheduled before direct execution', /unschedule_job\( \$primary_job_id \);\s*bds_ai_exec_process_job\( \$primary_job_id \)[\s\S]*unschedule_job\( \$failure_job_id \);\s*\$GLOBALS\['bds_staging_airbnb_acceptance_force_failure'\]/],
  ['orders and jobs require the exact marker before destructive cleanup', /BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER !==[\s\S]*_bds_staging_airbnb_acceptance[\s\S]*wp_delete_post/],
  ['case ledgers are deleted and verified absent', /delete_option\( 'bds_ai_case_'[\s\S]*false === get_option\( 'bds_ai_case_'/],
  ['the original WordPress user is restored in finally', /finally[\s\S]*wp_set_current_user\( \$original_user \)[\s\S]*original_context_restored/],
  ['the report contains only completion, checks, errors, and timestamp', /'version'[\s\S]*'complete'[\s\S]*'checks'[\s\S]*'error_codes'[\s\S]*'completed_at'/],
  ['production checkout remains explicitly fail-closed in the design', /Production checkout stays fail-closed/i.test(design)],
  ['the design records STRIDE and abuse-twin decisions', /STRIDE capstone and abuse twins[\s\S]*refund then consume[\s\S]*provider failure burns purchase/i.test(design)],
  ['helper contains no production hostname', !/https?:\/\/branddad\.social/i.test(source)],
  ['helper contains no card, Stripe, or gateway execution call', !/stripe|payment_intent|process_payment|charge_create/i.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} staging Airbnb acceptance contract check(s) failed.`);
  process.exit(1);
}

console.log('\nStaging Airbnb case acceptance contract passed.');
