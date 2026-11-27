import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const source = fs.readFileSync(path.join(root, 'deploy', 'zzz-bds-frontend-polish-mu.php'), 'utf8');

const checks = {
  release_version: source.includes('Version: 1.0.8') && source.includes("BDS_FRONTEND_POLISH_VERSION', '1.0.8'"),
  locked_catalog_reused: source.includes('bds_ai_commercial_catalog()') && source.includes("'quote_only'"),
  package_family_visible: source.includes('BrandDad Business Autopilot'),
  managed_tier_named: source.includes('Done-for-You AI Management'),
  operating_scope_clear: /publish approved social posts[\s\S]*comments, messages, and email[\s\S]*website questions[\s\S]*capture leads/.test(source),
  engagement_addon_separate: source.includes('BrandDad Engagement Autopilot') && source.includes('separate from managing the business'),
  no_feed_guarantee: source.includes('cannot guarantee a higher feed position'),
  no_fake_engagement: source.includes('does not use fake likes or bots'),
  contact_not_checkout: source.includes("'label'   => 'Scope this package'") && !source.includes('add-to-cart'),
};

let failed = 0;
for (const [name, passed] of Object.entries(checks)) {
  console.log(`${name}=${passed ? 'PASS' : 'FAIL'}`);
  if (!passed) failed += 1;
}

if (failed) process.exit(1);
