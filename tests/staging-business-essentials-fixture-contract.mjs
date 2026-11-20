import fs from 'node:fs';

const source = fs.readFileSync('deploy/bds-staging-business-essentials-fixture-mu.php', 'utf8');

const checks = [
  ['exact temporary staging host is pinned', /\$expected_host\s*=\s*'staging-branddad-social\.stackstaging\.com'/.test(source)],
  ['host comparison is constant time', /hash_equals\(\s*\$expected_host,\s*\$actual_host\s*\)/.test(source)],
  ['one-time completion guard exists', /! empty\( \$report\['complete'\] \)/.test(source)],
  ['WooCommerce availability is required', /class_exists\( 'WC_Product_Simple' \)[\s\S]*function_exists\( 'wc_get_product' \)/.test(source)],
  ['unmarked product collisions fail closed', /_bds_staging_fixture[\s\S]*unmarked_product_collision/.test(source)],
  ['product identity and monthly price are fixed', /\$slug\s*=\s*'ai-business-essentials'/.test(source) && /set_regular_price\( '29\.99' \)/.test(source)],
  ['fixture is hidden virtual and sold individually', /set_catalog_visibility\( 'hidden' \)/.test(source) && /set_virtual\( true \)/.test(source) && /set_sold_individually\( true \)/.test(source)],
  ['subscription repeats monthly', /'wps_sfw_subscription_number'\s*=>\s*'1'/.test(source) && /'wps_sfw_subscription_interval'\s*=>\s*'month'/.test(source)],
  ['trial is exactly seven days', /'wps_sfw_subscription_free_trial_number'\s*=>\s*'7'/.test(source) && /'wps_sfw_subscription_free_trial_interval'\s*=>\s*'day'/.test(source)],
  ['setup fee is zero', /'wps_sfw_subscription_initial_signup_price'\s*=>\s*'0'/.test(source)],
  ['customer allowance metadata is fixed', /'_bds_ai_formwise_credits'\s*=>\s*'75'/.test(source) && /'_bds_ai_audience'\s*=>\s*'customer'/.test(source)],
  ['saved product and every metadata field are read back', /\$saved_product = wc_get_product\( \$product_id \)/.test(source) && /get_post_meta\( \$product_id, \$key, true \)/.test(source)],
  ['verification failure remains incomplete', /'complete' => false, 'status' => 'verification_failed'/.test(source)],
  ['report option is not autoloaded', /update_option\([\s\S]*\$report_key[\s\S]*false\s*\)/.test(source)],
  ['no request input is read', !/\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)/.test(source)],
  ['no REST or admin action endpoint exists', !/register_rest_route|admin_post_|wp_ajax_/.test(source)],
  ['no outbound HTTP call exists', !/wp_remote_|curl_|file_get_contents\s*\(\s*['"]https?:/.test(source)],
  ['no order customer subscription or payment mutation exists', !/wc_create_order|WC_Order|wp_create_user|wp_insert_user|wps_subscriptions\s*\(|payment_intent|stripe/i.test(source)],
  ['no deletion primitive exists', !/unlink\s*\(|rmdir\s*\(|delete_option\s*\(|wp_delete_/.test(source)],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}

if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging Business Essentials fixture checks`);
