import fs from 'node:fs';

const source = fs.readFileSync('deploy/zzzzzzzzzz-bds-branded-product-placeholder-mu.php', 'utf8');
const checks = [
  ['version is explicit', /BDS_BRANDED_PRODUCT_PLACEHOLDER_VERSION', '1\.0\.0'/.test(source)],
  ['scope is limited to Social hosts', /branddad\.social[\s\S]*www\.branddad\.social/.test(source)],
  ['only WooCommerce placeholder hooks are changed', /woocommerce_placeholder_img_src[\s\S]*woocommerce_placeholder_img/.test(source)],
  ['existing BrandDad-owned artwork is reused', /wp-content\/uploads\/2023\/04\/3-1-1\.webp/.test(source)],
  ['fallback has accessible alt text', /BrandDad Social service/.test(source)],
  ['fallback has a dedicated class', /bds-product-placeholder/.test(source)],
  ['mobile fallback height is bounded', /max-height:260px/.test(source)],
  ['no product data or payment mutation', !/set_post_thumbnail|update_post_meta|wp_insert_post|add_to_cart|checkout|payment|order_status/.test(source)],
  ['no provider or panel behavior', !/JustAnotherPanel|JAP|service[_ -]?id|wp_remote_|fetch\(/i.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) process.exit(1);
console.log('Branded product placeholder contract passed.');

