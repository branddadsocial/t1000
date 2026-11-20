import fs from 'node:fs';

const files = [
  'deploy/bds-ai-commercial-catalog-mu.php',
  'deploy/bds-ai-subscriptions-mu.php',
  'deploy/bds-ai-trial-mu.php',
  'deploy/bds-directory-membership-bridge-mu.php',
  'deploy/bds-lifecycle-events-mu.php',
];

const sources = Object.fromEntries(files.map((file) => [file, fs.readFileSync(file, 'utf8')]));
const combined = Object.values(sources).join('\n');

const checks = [
  ['commercial catalog owns the live product slug', /'ai-business-essentials'\s*=>\s*array/.test(sources['deploy/bds-ai-commercial-catalog-mu.php'])],
  ['trial uses the live product slug', /BDS_AI_TRIAL_PRODUCT_SLUG', 'ai-business-essentials'/.test(sources['deploy/bds-ai-trial-mu.php'])],
  ['Directory consent requires the live product slug', /'ai-business-essentials' !== \( \$plan\['slug'\] \?\? '' \)/.test(sources['deploy/bds-directory-membership-bridge-mu.php'])],
  ['Directory event source uses the same product identity', /'source'\s*=>\s*'ai-business-essentials'/.test(sources['deploy/bds-directory-membership-bridge-mu.php'])],
  ['trial analytics uses the same product identity', /'product_slug'\s*=>\s*'ai-business-essentials'/.test(sources['deploy/bds-lifecycle-events-mu.php'])],
  ['obsolete product identity is absent from deploy modules', !combined.includes('branddad-ai-business-essentials')],
  ['order plan identity is derived from Woo product post name', /get_post_field\( 'post_name',[\s\S]*isset\( \$catalog\[ \$slug \] \)/.test(sources['deploy/bds-ai-subscriptions-mu.php'])],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) {
    failed += 1;
    console.error(`FAIL: ${name}`);
  }
}

if (failed) process.exit(1);
console.log(`PASS: ${checks.length} Business Essentials product identity checks`);
