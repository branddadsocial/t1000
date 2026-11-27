import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');

const checks = [
  ['version is pinned', /BDS_AI_EXECUTION_VERSION', '0\.3\.1'/],
  ['provider origin is fixed', /https:\/\/builder\.formwise\.ai\/api\/v1\/flows\//],
  ['only verified workflow UUIDs are in the catalog', /0cc9b6ff-309a-49f7-9a47-dd66c926208a[\s\S]*86c87c94-2054-4641-87e1-2a70a0a9b1d4[\s\S]*2e9921a0-5996-4bc9-a053-49fab3a7fc58[\s\S]*571f6f9c-7f7a-4b44-9e32-5d5b2fe11c6e/],
  ['provider key is server-only', /defined\( 'BDS_FORMWISE_EXECUTION_KEY' \)/],
  ['credential is never rendered', !/echo\s+[^;]*BDS_FORMWISE_EXECUTION_KEY/.test(source)],
  ['REST routes require authentication callback', (source.match(/permission_callback' => 'bds_ai_exec_permission'/g) || []).length === 3],
  ['inputs use explicit per-tool allowlists', /function bds_ai_exec_validate_inputs[\s\S]*foreach \( \$tool\['fields'\]/],
  ['request bodies are capped', /strlen\( \$encoded \) > 32768/],
  ['raw REST body size is capped before execution', /CONTENT_LENGTH[\s\S]*> 65536/],
  ['active paid access comes from server-side subscription metadata', /wps_customer_id[\s\S]*wps_subscription_status[\s\S]*'active'[\s\S]*wps_subscriptions/],
  ['plan identity comes from provisioned FormWise metadata', /_bds_ai_formwise_plan_id/],
  ['paid credits are reserved atomically', /function bds_ai_exec_paid_reserve[\s\S]*bds_ai_exec_lock/],
  ['paid ledger is tied to subscription renewal cycle', /_bds_ai_allowance_cycle[\s\S]*allowance_cycle[\s\S]*bds_ai_exec_paid_ledger_key/],
  ['free credits reserve before execution', /bds_dir_free_ai_reserve/],
  ['paid and trial access take precedence over Directory free allowances', /bds_ai_exec_active_plan\( \$user_id \)[\s\S]*'one_time_case' !== \$access_mode && 'directory_free' === \$access_mode/],
  ['failed work releases both allowance sources', /bds_dir_free_ai_release[\s\S]*bds_ai_exec_paid_finish\( \$user_id, \$plan, \$reservation, false \)/],
  ['successful work commits both allowance sources', /bds_dir_free_ai_commit[\s\S]*bds_ai_exec_paid_finish\( \$user_id, \$plan, \$reservation, true \)/],
  ['one-time products are allowlisted from the server catalog', /function bds_ai_exec_one_time_product_slugs[\s\S]*one_time_product_slug/],
  ['idempotency is required and body-bound', /Idempotency-Key[\s\S]*hash_hmac\( 'sha256', \$idempotency \. '\|' \. \$body_hash/],
  ['job ownership comes from the authenticated session', /get_current_user_id\(\) !== absint\( \$post->post_author \)/],
  ['outbound redirects are disabled', /'redirection'\s*=>\s*0/],
  ['outbound response size is capped', /'limit_response_size'\s*=>\s*131072/],
  ['network uncertainty is not auto-retried', /provider_network_uncertain/],
  ['model output is sanitized and bounded', /\$text = wp_strip_all_tags[\s\S]*mb_substr\( \$text, 0, 12000 \)[\s\S]*substr\( \$text, 0, 12000 \)/],
  ['async processing uses Action Scheduler with cron fallback', /as_enqueue_async_action[\s\S]*wp_schedule_single_event/],
  ['failed scheduling releases the reservation', /job_schedule_failed[\s\S]*bds_dir_free_ai_release[\s\S]*bds_ai_exec_paid_finish/],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} AI execution contract check(s) failed.`);
  process.exit(1);
}

console.log('\nAI execution contract passed.');
