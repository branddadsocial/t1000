import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-featured-billing-adapter-mu.php', 'utf8');
const checks = [
  ['adapter is versioned', /BDS_FEATURED_BILLING_VERSION', '0\.1\.0'/.test(source)],
  ['only four approved SKUs are recognized', (source.match(/'bds-feat-(?:local|regional|national|global)'/g) || []).length === 4],
  ['approved SKU prices are fixed server-side', /'monthly' => '9\.99'/.test(source) && /'monthly' => '99\.99'/.test(source)],
  ['cart refuses wrong price or non-monthly configuration', /_wps_sfw_product[\s\S]*1 === \$number[\s\S]*'month' === \$unit[\s\S]*approved monthly price/.test(source)],
  ['legacy unsafe activation hooks are removed', /remove_action\( 'woocommerce_order_status_processing', 'bds_feat_activate_paid_order'/.test(source)],
  ['cart requires signed-in verified listing owner', /is_user_logged_in\(\)[\s\S]*bds_featured_billing_listing_owned/.test(source)],
  ['listing and owner are copied into order item metadata', /woocommerce_checkout_create_order_line_item/.test(source) && /_bds_feat_owner_id/.test(source)],
  ['featured checkout requires an account', /woocommerce_checkout_registration_required/.test(source)],
  ['exactly one featured item is accepted per order', /return 1 === count\( \$found \) \? \$found\[0\] : array\(\)/.test(source)],
  ['non-global placement fails closed without country', /'global' !== \$level[\s\S]*\^\[A-Z\]\{2\}\$/.test(source)],
  ['local and regional placement require valid coordinates', /array\( 'local', 'regional' \)[\s\S]*\$lat < -90[\s\S]*\$lng > 180/.test(source)],
  ['activation requires paid order and active WPS subscription', /\$order->is_paid\(\)[\s\S]*wps_subscription_status/.test(source)],
  ['activation rechecks quantity subtotal and recurring interval', /\$price_matches[\s\S]*1 !== \$recurring_number[\s\S]*'month' !== \$recurring_unit/.test(source)],
  ['order must be parent or linked renewal', /wps_sfw_renewal_order[\s\S]*\$order_id === \$parent_id/.test(source)],
  ['customer id and listing ownership are rechecked at activation', /get_user_id\(\) \) !== \$context\['owner_id'\][\s\S]*bds_featured_billing_listing_owned/.test(source)],
  ['period end comes from authoritative next payment date', /wps_next_payment_date/.test(source) && /period_end <= current_time/.test(source)],
  ['subscription lock has stale recovery', /bds_feat_bill_[\s\S]*time\(\) - 60/.test(source)],
  ['new grants respect Cursor capacity', /bds_feat_has_capacity/.test(source)],
  ['moving an existing grant also rechecks target-lane capacity', /\$changes_lane[\s\S]*bds_feat_has_capacity/.test(source)],
  ['renewal extends the same subscription grant', /subscription_id[\s\S]*billing_renew/.test(source)],
  ['failed renewal pauses placement', /bds_featured_billing_set_status\( \$subscription_id, 'on-hold' \)[\s\S]*woocommerce_order_status_changed/.test(source)],
  ['cancellation honors paid-through end', /cancel_at_period_end/.test(source) && /strtotime/.test(source)],
  ['expiry and refund preserve records but end display', /wps_sfw_expire_subscription_scheduler/.test(source) && /woocommerce_order_refunded/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} Featured billing adapter contract check(s) failed.`);
  process.exit(1);
}
console.log('\nFeatured billing adapter contract passed.');
