import fs from 'node:fs';
import path from 'node:path';

const source = fs.readFileSync(
  path.resolve('deploy/bds-staging-dependency-activator-mu.php'),
  'utf8',
);

const literalPlugins = [
  'woocommerce/woocommerce.php',
  'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
  'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php',
];

const checks = [
  ['exact temporary staging host is pinned', /\$expected_host\s*=\s*'staging-branddad-social\.stackstaging\.com'/],
  ['host comparison is constant time', /hash_equals\(\s*\$expected_host,\s*\$actual_host\s*\)/],
  ['one-time completion guard exists', /! empty\( \$existing\['complete'\] \)/],
  ['all three plugin entries are literal', literalPlugins.every((plugin) => source.includes(`'${plugin}'`))],
  ['plugin paths are validated before activation', /validate_plugin\( \$plugin \)[\s\S]*activate_plugin\( \$plugin/],
  ['activation is silent and network-wide is false', /activate_plugin\( \$plugin, '', false, true \)/],
  ['final active state is verified', /if \( ! is_plugin_active\( \$plugin \) \)/],
  ['report option is not autoloaded', /update_option\([\s\S]*\$report_key[\s\S]*false\s*\)/],
  ['no request input is read', !/\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)/.test(source)],
  ['no REST or admin action endpoint exists', !/register_rest_route|admin_post_|wp_ajax_/.test(source)],
  ['no outbound HTTP call exists', !/wp_remote_|curl_|file_get_contents\s*\(\s*['"]https?:/.test(source)],
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
console.log(`PASS: ${checks.length} staging dependency activator contract checks`);
