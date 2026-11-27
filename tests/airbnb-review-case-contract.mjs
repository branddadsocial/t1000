import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');
const catalog = fs.readFileSync('deploy/bds-ai-commercial-catalog-mu.php', 'utf8');
const launch = fs.readFileSync('deploy/bds-ai-launch-pages-mu.php', 'utf8');
const design = fs.readFileSync('BRANDDAD_AIRBNB_REVIEW_ASSISTANT_DESIGN.md', 'utf8');

const checks = [
  ['one-time tool uses the published review-dispute flow', /'airbnb_review_appeal'[\s\S]*86c87c94-2054-4641-87e1-2a70a0a9b1d4/],
  ['platform is fixed server-side to Airbnb', /'forced'\s*=>\s*array\( 'which_review_platform_' => 'Airbnb' \)/],
  ['authorization must be explicitly affirmed', /'are_you_authorized_'\s*=>\s*array\( 'type' => 'enum', 'required' => true, 'options' => array\( 'Yes, I am authorized' \)/],
  ['review and evidence inputs are each limited to 3000 characters', (source.match(/'max' => 3000/g) || []).length >= 2],
  ['entitlement comes from exact paid Woo orders', /function bds_ai_exec_case_entitlement[\s\S]*'customer_id'[\s\S]*wc-processing[\s\S]*wc-completed[\s\S]*get_slug\(\)/],
  ['refunded item quantity cannot grant a case', /get_qty_refunded_for_item[\s\S]*remaining_quantity < 1/],
  ['order line captures immutable one-case product identity', /_bds_ai_case_product_slug[\s\S]*woocommerce_checkout_create_order_line_item/],
  ['one-case checkout requires an owned account', /woocommerce_checkout_registration_required[\s\S]*bds_ai_exec_one_time_product_slugs/],
  ['one-case product is sold individually', /woocommerce_is_sold_individually[\s\S]*bds_ai_exec_one_time_product_slugs/],
  ['payment is revalidated immediately before provider execution', /function bds_ai_exec_case_order_is_paid[\s\S]*has_status\( array\( 'processing', 'completed' \)[\s\S]*case_payment_inactive/],
  ['one-case reservation is locked and expiring', /function bds_ai_exec_case_reserve[\s\S]*bds_ai_exec_lock[\s\S]*pending_expires[\s\S]*time\(\) \+ 1200/],
  ['reservation is attached to a private job before scheduling', /function bds_ai_exec_case_attach_job[\s\S]*pending_job_id[\s\S]*case_reservation_attach_failed/],
  ['failed case work releases its reservation', /'one_time_case' === \$mode[\s\S]*bds_ai_exec_case_finish\( \$user_id, \$order_id, \$order_item_id, \$reservation, false \)/],
  ['successful case work commits exactly once', /'one_time_case' === \$mode[\s\S]*bds_ai_exec_case_finish\( \$user_id, \$order_id, \$order_item_id, \$reservation, true \)/],
  ['order and item ids are derived and stored server-side', /_bds_order_id[\s\S]*_bds_order_item_id/],
  ['security design explicitly forbids removal guarantees', /does not[\s\S]*guarantee removal/i.test(design)],
  ['launch gate requires payment-through-refund acceptance', /payment → entitlement → one successful case[\s\S]*refund blocks a new case/i.test(design)],
  ['catalog separates the one-time offer from the monthly review assistant', /'ai-review-assistant'[\s\S]*'branddad-ai-review-dispute-assistant'[\s\S]*'billing' => 'one_time'/.test(catalog)],
  ['catalog target price is $9.99 and remains staged', /'branddad-ai-review-dispute-assistant'[\s\S]*'price' => '9\.99'[\s\S]*'launch_state' => 'staged'/.test(catalog)],
  ['sales copy says one review and no guarantee', /One Airbnb review\. One simple payment[\s\S]*removal is not guaranteed/i.test(launch)],
  ['legacy generic multi-platform sales copy is removed', !/Google, Airbnb, and Trustpilot/.test(launch)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} Airbnb review case contract check(s) failed.`);
  process.exit(1);
}

console.log('\nAirbnb review case contract passed.');
