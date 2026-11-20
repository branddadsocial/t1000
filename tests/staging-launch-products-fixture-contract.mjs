import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-staging-launch-products-fixture-mu.php', 'utf8');
const cases = [
  ['branddad-ai-studio-starter', '14.99', 50],
  ['ai-local-seo', '19.99', 60],
  ['ai-lead-assistant', '19.99', 80],
  ['ai-social-growth', '29.99', 150],
];
const checks = [
  ['version is explicit', /BDS_STAGING_LAUNCH_PRODUCTS_VERSION', '0\.1\.0'/.test(source)],
  ['canonical TLS staging host is fixed', /BDS_STAGING_LAUNCH_PRODUCTS_HOST', 'staging-branddad-social\.stackstaging\.com'/.test(source)],
  ['host derives from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
  ['all four exact products prices and credits are literals', cases.every(([slug, price, credits]) => source.includes(`'${slug}'`) && source.includes(`'price' => '${price}'`) && source.includes(`'credits' => ${credits}`))],
  ['catalog launch state price and credits must match', /launch_candidate[\s\S]*offer\['monthly'\][\s\S]*offer\['customer_credits'\]/.test(source)],
  ['every collision is preflighted before write loop', /Preflight every definition and collision before the first write[\s\S]*foreach \( \$definitions as \$slug => \$definition \)[\s\S]*foreach \( \$definitions as \$slug => \$definition \)/.test(source)],
  ['unmarked or wrong-kind collisions fail closed', /_bds_staging_fixture[\s\S]*BDS_STAGING_LAUNCH_PRODUCTS_MARKER[\s\S]*unmarked_product_collision/.test(source)],
  ['existing products must be simple products', /instanceof WC_Product_Simple/.test(source)],
  ['new product ownership markers are saved with product creation', /Persist the ownership markers in the same Woo save[\s\S]*update_meta_data\( '_bds_staging_fixture'[\s\S]*update_meta_data\( '_bds_staging_fixture_kind'[\s\S]*\$product->save\(\)/.test(source)],
  ['fixtures are hidden published virtual sold individually', /set_status\( 'publish' \)[\s\S]*set_catalog_visibility\( 'hidden' \)[\s\S]*set_virtual\( true \)[\s\S]*set_sold_individually\( true \)/.test(source)],
  ['monthly recurrence and no expiry are fixed', /wps_sfw_subscription_number[\s\S]*'1'[\s\S]*wps_sfw_subscription_interval[\s\S]*month[\s\S]*wps_sfw_subscription_expiry_number[\s\S]*''/.test(source)],
  ['setup fee and trial are zero', /wps_sfw_subscription_initial_signup_price[\s\S]*'0'[\s\S]*wps_sfw_subscription_free_trial_number[\s\S]*'0'/.test(source)],
  ['credits and customer audience are fixed', /_bds_ai_formwise_credits[\s\S]*_bds_ai_audience[\s\S]*customer/.test(source)],
  ['every product and metadata field is read back', /wc_get_product\( \$product_id \)[\s\S]*get_post_meta\( \$product_id, \$key, true \)/.test(source)],
  ['report exposes only four slug booleans and bounded errors', /array_keys\( bds_staging_launch_products_definitions\(\) \)[\s\S]*true ===[\s\S]*array_slice\( \(array\) \$errors, 0, 8 \)/.test(source)],
  ['completion requires exactly four verified fixtures', /4 === count\( \$checks \) && 4 === count\( array_filter\( \$checks \) \)/.test(source)],
  ['only a complete prior report prevents safe retry', /\$prior_report[\s\S]*! empty\( \$prior_report\['complete'\] \)/.test(source)],
  ['stale-safe lock is removed', /bds_staging_launch_products_lock_v1[\s\S]*time\(\) - 600[\s\S]*delete_option\( \$lock \)/.test(source)],
  ['route is exact-host and GET-only', /launch_products_is_host[\s\S]*\/staging\/launch-products-fixture[\s\S]*'methods'\s*=>\s*'GET'/.test(source)],
  ['no request or superglobal input is read', !/WP_REST_Request|\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)\b/.test(source)],
  ['no external request message user order or payment exists', !/wp_remote_|curl_|wp_mail\(|wp_create_user|wp_insert_user|wc_create_order|payment_complete\(|process_payment\(|stripe/i.test(source)],
  ['no product or customer deletion primitive exists', !/wp_delete_post|delete_user|WC_Order|unlink\(|rmdir\(/.test(source)],
];
let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures++; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} launch-product fixture contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging launch-product fixture contract passed (${checks.length} assertions).`);
