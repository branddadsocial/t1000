import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const source = fs.readFileSync(path.join(root, 'deploy', 'zzz-bds-frontend-polish-mu.php'), 'utf8');

const checks = {
  release_version: source.includes('Version: 1.0.11') && source.includes("BDS_FRONTEND_POLISH_VERSION', '1.0.11'"),
  package_family_visible: source.includes('Done-for-You Essential Engagement') && source.includes('Done-for-You Complete Growth') && source.includes('Done-for-You Maximum Visibility'),
  approved_prices: source.includes('$599/month') && source.includes('$699/month') && source.includes('$849/month'),
  post_caps_clear: source.includes('Up to 12 approved posts') && source.includes('Up to 20 approved posts') && source.includes('Up to 30 approved posts'),
  featured_plan_clear: source.includes("'eyebrow' => 'Best value'") && source.includes("'label'   => 'Choose Complete Growth'"),
  regions_clear: source.includes('<strong>US Focused</strong>') && source.includes('<strong>Worldwide</strong>'),
  linkedin_included: source.includes('LinkedIn content support') && source.includes('professional conversation support'),
  operating_scope_clear: /social media active[\s\S]*answer relevant questions[\s\S]*approved business information/.test(source),
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
