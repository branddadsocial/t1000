import fs from 'node:fs';
import path from 'node:path';

const source = fs.readFileSync(path.resolve('deploy/bds-provider-cost-ledger-mu.php'), 'utf8');
const checks = [
  ['private cost post type', /register_post_type\([\s\S]*'bds_provider_cost'[\s\S]*'public'\s*=>\s*false[\s\S]*'show_in_rest'\s*=>\s*false/],
  ['no public write route', !/register_rest_route\([^;]*methods[^;]*(POST|CREATABLE)/s.test(source)],
  ['admin aggregate requires Woo capability', /current_user_can\(\s*'manage_woocommerce'\s*\)/],
  ['only verified bases are accepted', /provider_reported[\s\S]*processor_reported[\s\S]*invoice_reconciled[\s\S]*contract_unit_reconciled/],
  ['estimated basis is not accepted', !/['"]estimated['"]/.test(source)],
  ['authoritative catalog membership required', /bds_provider_cost_catalog_has\( \$product \)/],
  ['currency is bounded', /\^\[A-Z\]\{3\}\$/],
  ['negative integers are rejected before normalization', /is_int\( \$amount_raw \) && \$amount_raw >= 0/],
  ['fractional and signed strings are rejected before normalization', /is_string\( \$amount_raw \) && ctype_digit\( \$amount_raw \)/],
  ['negative timestamps are rejected before normalization', /is_int\( \$occurred_raw \) && \$occurred_raw >= 0/],
  ['amount is positive and capped', /\$amount < 1 \|\| \$amount > 100000000/],
  ['idempotency is HMAC protected', /hash_hmac\( 'sha256'[\s\S]*wp_salt\( 'auth' \)/],
  ['stale lock can recover', /bds_cost_lock_[\s\S]*\$now - 60/],
  ['raw source reference is not stored', !/update_post_meta\([^;]*idempotency/.test(source)],
  ['source reference hash is stored', /_bds_cost_reference_hash/],
  ['report is capped', (source.match(/'posts_per_page'\s*=>\s*5000/g) || []).length >= 2],
  ['period is bounded to 24 months', /-23 months/],
  ['report does not claim final profit', /It is not final profit/],
  ['cost coverage is explicit', /partial_unless_reconciled[\s\S]*no_verified_costs/],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}
if (failed) process.exit(1);
console.log(`PASS: ${checks.length} provider cost ledger contract checks`);
