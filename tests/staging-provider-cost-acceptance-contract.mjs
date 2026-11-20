import fs from 'node:fs';
import path from 'node:path';

const source = fs.readFileSync(path.resolve('deploy/bds-staging-provider-cost-acceptance-mu.php'), 'utf8');
const checks = [
  ['canonical staging host is fixed', /BDS_STAGING_PROVIDER_COST_HOST', 'staging-branddad-social\.stackstaging\.com'/],
  ['hostname check uses configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/],
  ['automatic run is exact-host gated', /if \( ! bds_staging_provider_cost_is_host\(\) \|\| get_option/],
  ['one-time report gate exists', /bds_staging_provider_cost_report_v1/],
  ['stale run lock recovers', /bds_staging_provider_cost_acceptance_lock_v1[\s\S]*time\(\) - 300/],
  ['fixture account marker is required', /_bds_staging_auth_fixture/],
  ['fixture cannot manage WooCommerce', /! user_can\( \$user_id, 'manage_woocommerce' \)/],
  ['non-admin denial is checked', /false === bds_provider_cost_admin_permission\(\)/],
  ['unknown provider is rejected', /invalid_provider_rejected[\s\S]*unknown-provider/],
  ['unknown product is rejected', /invalid_product_rejected[\s\S]*unknown-product/],
  ['estimated basis is rejected', /invalid_basis_rejected[\s\S]*estimated/],
  ['bad currency is rejected', /invalid_currency_rejected[\s\S]*'US'/],
  ['negative integer amount is rejected', /negative_amount_rejected[\s\S]*-137/],
  ['fractional amount is rejected', /fractional_amount_rejected[\s\S]*1\.37/],
  ['excessive amount is rejected', /excessive_amount_rejected[\s\S]*100000001/],
  ['negative timestamp is rejected', /negative_timestamp_rejected[\s\S]*'occurred_at'\s*=>\s*-1/],
  ['future timestamp is rejected', /future_timestamp_rejected[\s\S]*HOUR_IN_SECONDS/],
  ['short idempotency is rejected', /short_idempotency_rejected[\s\S]*'short'/],
  ['valid fixture uses an authoritative catalog product', /'product_slug'\s*=>\s*'ai-business-essentials'/],
  ['valid fixture has fixed provider', /'provider'\s*=>\s*'formwise'/],
  ['valid fixture has fixed minor-unit cost', /'amount_minor'\s*=>\s*137/],
  ['replay is checked for same record', /duplicate_cost_reused[\s\S]*\$cost_a === \$cost_b/],
  ['single stored row is checked', /duplicate_cost_single_row[\s\S]*1 === count\( \$matching \)/],
  ['raw reference absence is checked', /_bds_cost_idempotency/],
  ['reference hash format is checked', /\^\[a-f0-9\]\{64\}\$/],
  ['untrusted metadata absence is checked', /_bds_cost_email[\s\S]*_bds_cost_message/],
  ['provider aggregate delta is exact', /provider_aggregate_delta_correct[\s\S]*137 === \$provider_after - \$provider_before/],
  ['product aggregate delta is exact', /product_aggregate_delta_correct[\s\S]*137 === \$product_after - \$product_before/],
  ['total cost delta is exact', /cost_aggregate_delta_correct[\s\S]*137 === \$cost_after - \$cost_before/],
  ['contribution delta is exact', /contribution_delta_correct[\s\S]*-137 === \$contribution_after - \$contribution_before/],
  ['incomplete coverage label is required', /partial_unless_reconciled[\s\S]*not final profit/],
  ['invalid period is tested', /invalid_period_rejected[\s\S]*invalid_period/],
  ['evidence route is read-only', /'methods'\s*=>\s*'GET'/],
  ['evidence route is exact-host gated', /function bds_staging_provider_cost_register_route\(\)[\s\S]*! bds_staging_provider_cost_is_host\(\)/],
  ['evidence response is allowlisted', /\$allowlist = array\([\s\S]*\$safe_checks\[ \$key \]/],
  ['error codes are bounded', /array_slice\( \(array\) \$errors, 0, 10 \)[\s\S]*substr[\s\S]*0, 80/],
  ['no user creation', !/wp_create_user|wp_insert_user|register_new_user/.test(source)],
  ['no capability grant', !/add_cap|set_role/.test(source)],
  ['no payment mutation', !/wc_create_order|WC_Order|payment_complete|refund_payment/.test(source)],
  ['no outbound network call', !/wp_remote_(get|post|request)|curl_exec/.test(source)],
  ['no email or message sending', !/wp_mail|send_message|messages\.create/.test(source)],
  ['no destructive post or user deletion', !/wp_delete_post|wp_delete_user/.test(source)],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} staging provider-cost acceptance contract checks`);
