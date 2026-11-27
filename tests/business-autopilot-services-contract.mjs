import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const source = fs.readFileSync(path.join(root, 'deploy', 'zzz-bds-frontend-polish-mu.php'), 'utf8');

const checks = {
  release_version: source.includes('Version: 1.0.13') && source.includes("BDS_FRONTEND_POLISH_VERSION', '1.0.13'"),
  base_without_engagement: source.includes('Done-for-You Complete Management') && source.includes('$399/month') && source.includes('No BDS Network engagement is included or required.'),
  package_family_visible: source.includes('Complete Management + Essential Visibility') && source.includes('Complete Management + Growth Visibility') && source.includes('Complete Management + Maximum Visibility'),
  approved_prices: source.includes('$599/month total') && source.includes('$699/month total') && source.includes('$849/month total'),
  post_caps_clear: source.includes('up to 12 eligible posts') && source.includes('up to 20 eligible posts') && source.includes('up to 30 approved posts total'),
  featured_plan_clear: source.includes("'eyebrow' => 'Best value'") && source.includes("'label'   => 'Choose Growth Visibility'"),
  regions_clear: source.includes('<strong>US Focused</strong>') && source.includes('<strong>Worldwide</strong>'),
  linkedin_included: source.includes('LinkedIn content support') && source.includes('support LinkedIn'),
  operating_scope_clear: /social content moving[\s\S]*customer conversations[\s\S]*lead[s]?/.test(source),
  engagement_optional: source.includes('Optional BDS Network visibility upgrades') && source.includes('You do not have to buy BDS Network engagement.'),
  honest_metrics: source.includes('campaign-assisted reach') && source.includes('Pending or unverified activity is not counted as completed.'),
  no_outcome_guarantee: source.includes('Platform placement, reach, followers, leads, and sales are not guaranteed.'),
  supplier_details_private: !/\b(8751|7128|6818|10320|10118|6367|6118|10144|5922|10119)\b/.test(source),
  contact_not_checkout: source.includes("$package['url'] = $contact") && !source.includes('add-to-cart'),
};

let failed = 0;
for (const [name, passed] of Object.entries(checks)) {
  console.log(`${name}=${passed ? 'PASS' : 'FAIL'}`);
  if (!passed) failed += 1;
}

if (failed) process.exit(1);
