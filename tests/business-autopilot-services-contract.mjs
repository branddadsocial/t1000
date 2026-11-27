import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const source = fs.readFileSync(path.join(root, 'deploy', 'zzz-bds-frontend-polish-mu.php'), 'utf8');

const checks = {
  release_version: source.includes('Version: 1.0.14') && source.includes("BDS_FRONTEND_POLISH_VERSION', '1.0.14'"),
  base_without_promotion: source.includes('Done-for-You Complete Management') && source.includes('$399/month') && source.includes('No paid promotion or media spend is included or required.'),
  package_family_visible: source.includes('Complete Management + Promotion Starter') && source.includes('Complete Management + Promotion Growth') && source.includes('Complete Management + Promotion Pro'),
  approved_prices: source.includes('$599/month total') && source.includes('$699/month total') && source.includes('$849/month total'),
  promotion_caps_clear: source.includes('up to 4 customer-approved') && source.includes('up to 8 customer-approved') && source.includes('up to 12 customer-approved'),
  featured_plan_clear: source.includes("'eyebrow' => 'Best value'") && source.includes("'label'   => 'Choose Promotion Growth'"),
  linkedin_included: source.includes('LinkedIn content support') && source.includes('support LinkedIn'),
  operating_scope_clear: /social content moving[\s\S]*customer conversations[\s\S]*lead[s]?/.test(source),
  promotion_optional: source.includes('Optional official promotion management') && source.includes('Paid promotion is always optional.'),
  approved_channels_only: source.includes('supported official platforms') && source.includes('documented creator campaigns'),
  customer_costs_clear: source.includes('advertising spend and creator fees are separate'),
  honest_metrics: source.includes('organic reach and impressions') && source.includes('paid reach, impressions, clicks and spend') && source.includes('Pending or unverified activity is not counted as completed.'),
  no_outcome_guarantee: source.includes('Placement, reach, followers, leads, and sales are not guaranteed.'),
  supplier_details_private: !/\b(8751|7128|6818|10320|10118|6367|6118|10144|5922|10119)\b/.test(source),
  no_panel_branding: !source.includes('BDS Network') && !source.includes('JustAnotherPanel'),
  contact_not_checkout: source.includes("$package['url'] = $contact") && !source.includes('add-to-cart'),
};

let failed = 0;
for (const [name, passed] of Object.entries(checks)) {
  console.log(`${name}=${passed ? 'PASS' : 'FAIL'}`);
  if (!passed) failed += 1;
}

if (failed) process.exit(1);
