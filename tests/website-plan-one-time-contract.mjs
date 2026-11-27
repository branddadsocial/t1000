import fs from 'node:fs';
import process from 'node:process';

const execution = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');
const offer = fs.readFileSync('deploy/bds-ai-website-plan-offer-mu.php', 'utf8');

const checks = [
  ['website plan uses the existing published flow', /'website_plan'[\s\S]*3d3deeff-cc55-43a3-b96e-4969df4296cc/.test(execution)],
  ['one purchase grants one exact website-plan job', /'website_plan'[\s\S]*'one_time_product_slug' => 'branddad-smartsites-website-optimizer'/.test(execution)],
  ['eligible paid and trial plans retain website-plan access', /'website_plan'[\s\S]*'plan_access' => true[\s\S]*'trial'\s*=>\s*true/.test(execution)],
  ['product migration is exact-slug and Social-only', /branddad\.social/.test(offer) && /BDS_AI_WEBSITE_PLAN_PRODUCT_SLUG[\s\S]*get_page_by_path/.test(offer)],
  ['approved one-time price is $19.99', /BDS_AI_WEBSITE_PLAN_PRICE', '19\.99'/.test(offer)],
  ['old product fields are retained for rollback', /bds_ai_website_plan_offer_backup[\s\S]*regular_price[\s\S]*catalog_visibility/.test(offer)],
  ['legacy catalog state is retained for rollback', /bds_ai_website_plan_legacy_catalog_backup[\s\S]*product_id[\s\S]*catalog_visibility/.test(offer)],
  ['both exact products are validated before either is changed', offer.indexOf("'legacy_product_not_distinct'") < offer.indexOf("$product->set_name")],
  ['legacy product is hidden from catalog visibility', /bds_ai_website_plan_is_legacy_product[\s\S]*set_catalog_visibility\( 'hidden' \)/.test(offer)],
  ['legacy product cannot be purchased directly', /bds_ai_website_plan_is_legacy_product\( \$product \)[\s\S]*return false/.test(offer)],
  ['legacy product is hidden from cached loop rendering', /woocommerce_product_is_visible[\s\S]*bds_ai_website_plan_product_is_visible/.test(offer)],
  ['historical orders are not rewritten', !/wc_get_orders|shop_order.*wp_update_post/.test(offer)],
  ['account form requires a nonce', /wp_verify_nonce[\s\S]*bds_ai_website_plan_submit/.test(offer)],
  ['account form uses bounded allowlisted fields', /allowed_help[\s\S]*maxlength="2000"[\s\S]*maxlength="4000"/.test(offer)],
  ['job creation reuses the authenticated execution handler', /new WP_REST_Request\( 'POST', '\/branddad\/v1\/ai\/jobs' \)[\s\S]*bds_ai_exec_create_job/.test(offer)],
  ['idempotency key is generated server-side', /Idempotency-Key', 'webplan-' \. wp_generate_uuid4\(\)/.test(offer)],
  ['customer result stays owner-scoped', /bds_ai_exec_job_response\( \$job_id \)/.test(offer)],
  ['AI output is escaped rather than rendered as HTML', /nl2br\( esc_html\( \(string\) \$job\['result'\] \) \)/.test(offer)],
  ['no password collection field exists', !/name="(?:password|api_key|secret|token)"/i.test(offer)],
  ['implementation requires connection and exact approval', /secure connection and exact approval/i.test(offer)],
  ['custom and paid infrastructure remains separately scoped', /Hosting upgrades, paid services, custom code/i.test(offer)],
  ['no outcome guarantee is made', /No ranking, traffic, sales, or speed score is guaranteed/.test(offer)],
  ['thank-you link does not auto-run provider work', /Start my AI website plan[\s\S]*woocommerce_thankyou/.test(offer) && !/bds_ai_website_plan_thankyou[\s\S]*bds_ai_exec_process_job/.test(offer)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} website-plan contract check(s) failed.`);
  process.exit(1);
}

console.log('\nWebsite-plan one-time contract passed.');
