import assert from 'node:assert/strict';
import fs from 'node:fs';

const source = fs.readFileSync(new URL('../deploy/bds-services-catalog-mu.php', import.meta.url), 'utf8');

const checks = [
  ['catalog version is 1.3.0', /define\(\s*'BDS_SVC_CAT_VER',\s*'1\.3\.0'\s*\)/],
  ['website plan is the preferred performance diagnostic', /'website-speed-optimization'[\s\S]*?'recommendation_priority'\s*=>\s*100/],
  ['AI Local SEO uses the live product slug', /'ai-local-seo'[\s\S]*?'url'\s*=>\s*\$social\s*\.\s*'\/product\/ai-local-seo\/'/],
  ['AI Local SEO price is 19.99', /'ai-local-seo'[\s\S]*?'price'\s*=>\s*19\.99/],
  ['AI Local SEO is recommendation priority 100', /'ai-local-seo'[\s\S]*?'recommendation_priority'\s*=>\s*100/],
  ['AI Studio Starter uses the live product slug', /'branddad-ai-studio-starter'[\s\S]*?'url'\s*=>\s*\$social\s*\.\s*'\/product\/branddad-ai-studio-starter\/'/],
  ['AI Studio Starter price is 14.99', /'branddad-ai-studio-starter'[\s\S]*?'price'\s*=>\s*14\.99/],
  ['legacy 179 SEO diagnostic is not recommended', /'website-seo-audit'[\s\S]*?'recommendable'\s*=>\s*false/],
  ['non-recommendable entries are skipped', /isset\(\s*\$svc\['recommendable'\]\s*\)\s*&&\s*!\s*\$svc\['recommendable'\]/],
  ['AI priority sorts before hit count', /\$priority\s*=\s*\(int\)\s*\$b\['priority'\][\s\S]*?if\s*\(\s*0\s*!==\s*\$priority\s*\)[\s\S]*?\$hits\s*=/],
  ['equal-priority recommendations prefer lower price', /return\s*\(float\)\s*\$a\['price'\]\s*<=>\s*\(float\)\s*\$b\['price'\]/],
  ['public catalog strings do not expose FormWise', /=>\s*'[^']*FormWise/i, false],
  ['AI output stays customer-reviewed', /You review everything before using it\./],
  ['automatic website edits are not promised', /automatic website edits are not promised/],
];

let failures = 0;
for (const [label, pattern, expected = true] of checks) {
  const pass = pattern.test(source) === expected;
  console.log(`${pass ? 'PASS' : 'FAIL'}: ${label}`);
  if (!pass) failures += 1;
}

assert.equal(failures, 0, `${failures} AI-first service catalog checks failed`);
console.log(`\nAI-first service catalog contract passed (${checks.length} assertions).`);
