import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-featured-billing-acceptance-mu.php', 'utf8');
const checks = [
  ['helper is versioned', /BDS_STAGING_FEATURED_ACCEPTANCE_VERSION', '0\.1\.1'/.test(source)],
  ['dependency-correction rerun uses a fresh immutable report key', /BDS_STAGING_FEATURED_ACCEPTANCE_REPORT', 'bds_staging_featured_billing_report_v2'/.test(source)],
  ['helper is bound to the exact staging home host', /staging-branddad-social\.stackstaging\.com[\s\S]*wp_parse_url\( home_url/.test(source)],
  ['helper has no request-controlled inputs', !/\$_(?:REQUEST|COOKIE|FILES|SERVER)\b|wp_unslash\s*\(\s*\$_(?:GET|POST)/.test(source)],
  ['one-time report and stale lock bound execution', /BDS_STAGING_FEATURED_ACCEPTANCE_REPORT[\s\S]*time\(\) - 600/.test(source)],
  ['ownership seam is exact listing and owner only', /absint\( \$listing_id \) === absint\( \$state\['listing_id'\] \)[\s\S]*absint\( \$user_id \) === absint\( \$state\['owner_id'\] \)/.test(source)],
  ['grant seam uses one isolated option', (source.match(/bds_staging_featured_acceptance_grants_v1/g) || []).length >= 2],
  ['four literal production offers drive fixture products', /\$offers\s*=\s*bds_featured_billing_skus\(\)/.test(source) && /4 === count\( \$products \)/.test(source)],
  ['unmarked SKU collisions fail closed', /wc_get_product_id_by_sku[\s\S]*BDS_STAGING_FEATURED_ACCEPTANCE_MARKER[\s\S]*featured_sku_collision/.test(source)],
  ['wrong price and interval are exercised through production readiness', /wrong_price_rejected[\s\S]*bds_featured_billing_product_ready[\s\S]*wrong_interval_rejected/.test(source)],
  ['synthetic orders use no gateway', /bds_staging_synthetic_no_gateway/.test(source) && !/process_payment|payment_complete\s*\(/.test(source)],
  ['production adapter performs activation and replay', (source.match(/bds_featured_billing_sync\s*\(/g) || []).length >= 6 && /replay_idempotent/.test(source)],
  ['renewal is linked with WPS authoritative metadata', /wps_sfw_renewal_order[\s\S]*wps_sfw_subscription/.test(source)],
  ['failed renewal cancellation expiry and refund are exercised', /bds_featured_billing_order_failed[\s\S]*bds_featured_billing_cancelled[\s\S]*bds_featured_billing_expired[\s\S]*bds_featured_billing_refunded/.test(source)],
  ['capacity denial proves no grant', /capacity_failure_no_grant[\s\S]*empty\( bds_feat_grants\(\) \)/.test(source)],
  ['cleanup verifies every record marker before deletion', /_bds_staging_featured_acceptance[\s\S]*wp_delete_post[\s\S]*_bds_staging_featured_acceptance[\s\S]*\$product->delete/.test(source)],
  ['isolated option and request context are restored in finally', /finally[\s\S]*\$grants_existed[\s\S]*\$_POST = \$original_post[\s\S]*\$_GET\s*=\s*\$original_get/.test(source)],
  ['public route is GET-only and boolean report backed', /staging\/featured-billing-acceptance[\s\S]*'methods'\s*=>\s*'GET'/.test(source) && /\$safe_checks\[ \$key \]\s*=\s*true ===/.test(source)],
  ['helper contains no outbound or messaging primitives', !/wp_remote_|wp_mail\s*\(|curl_|FormWise|Stripe/i.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} staging Featured billing acceptance contract check(s) failed.`);
  process.exit(1);
}
console.log('\nStaging Featured billing acceptance contract passed.');
