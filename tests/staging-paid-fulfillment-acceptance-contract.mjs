import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-paid-fulfillment-acceptance-mu.php', 'utf8');
const checks = [
  ['exact staging host is pinned', /BDS_STAGING_PAID_TASK_HOST', 'staging-branddad-social\.stackstaging\.com'/.test(source)],
  ['one-time acceptance uses the exact press release product', /branddad-ai-press-release-pack[\s\S]*Press Release Submission/.test(source)],
  ['unpaid order is checked before paid transition', /unpaid_order_created[\s\S]*unpaid_order_created_no_task[\s\S]*bds_staging_paid_task_mark_paid/.test(source)],
  ['duplicate payment and completed hooks are replayed', /woocommerce_payment_complete[\s\S]*woocommerce_payment_complete[\s\S]*woocommerce_order_status_completed/.test(source)],
  ['task acceptance requires exactly one note', /confirmed_payment_created_one_task[\s\S]*1 === bds_staging_paid_task_note_count[\s\S]*duplicate_payment_created_no_second_task[\s\S]*1 === bds_staging_paid_task_note_count/.test(source)],
  ['quote acceptance uses an admin-created order', /done-for-you-ai-management[\s\S]*'admin'[\s\S]*accepted_quote_admin_order_created/.test(source)],
  ['no real payment gateway is invoked', /bds_staging_synthetic_no_gateway/.test(source) && !/process_payment\s*\(|->payment_complete\s*\(|WC_Gateway|stripe/i.test(source)],
  ['fixtures are marked before cleanup', /_bds_staging_paid_task[\s\S]*BDS_STAGING_PAID_TASK_MARKER[\s\S]*delete\( true \)/.test(source)],
  ['idempotency claim is removed only for marked staging order cleanup', /BDS_STAGING_PAID_TASK_MARKER[\s\S]*delete_option\( bds_submit_staff_task_claim_key/.test(source)],
  ['public report exposes booleans and fixed product but no order id', /array_intersect_key\( array_map\( 'boolval'[\s\S]*'product'\s*=> 'branddad-ai-press-release-pack'/.test(source)],
  ['report route is exact-host gated and GET only', /bds_staging_paid_task_register_route[\s\S]*bds_staging_paid_task_is_host[\s\S]*'methods'\s*=> 'GET'/.test(source)],
  ['no request input, user creation, mail, or external network exists', !/WP_REST_Request|\$_(?:GET|POST|REQUEST|COOKIE|FILES)|wp_create_user|wp_insert_user|wp_mail\(|wp_remote_|curl_/i.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures++; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} staging paid-fulfillment contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging paid-fulfillment contract passed (${checks.length} assertions).`);
