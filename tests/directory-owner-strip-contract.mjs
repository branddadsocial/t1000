import fs from 'node:fs';

const source = fs.readFileSync('deploy/zzzzzzzzzzz-bds-directory-owner-strip-mu.php', 'utf8');
const checks = [
  ['version is explicit', /BDS_DIRECTORY_OWNER_STRIP_VERSION', '1\.1\.0'/.test(source)],
  ['scope is limited to Directory hosts', /directory\.branddad\.social[\s\S]*www\.directory\.branddad\.social/.test(source)],
  ['existing owner notice is targeted', /#bds-nav-fix-wallet/.test(source)],
  ['desktop notice uses compact grid', /grid-template-columns:auto minmax\(0,1fr\) auto/.test(source)],
  ['mobile notice collapses to one column', /@media\(max-width:720px\)[\s\S]*grid-template-columns:1fr/.test(source)],
  ['long explanation is visually clamped on mobile', /-webkit-line-clamp:2/.test(source)],
  ['owner links remain touch friendly', /min-height:32px/.test(source)],
  ['related cards lose only their invalid IDs', /article\.theme-related-blog-each\[id\][\s\S]*removeAttribute\('id'\)/.test(source)],
  ['no balance or account behavior is reimplemented', !/get_user_meta|update_user_meta|register_rest_route|wp_remote_|fetch\(|XMLHttpRequest/.test(source)],
  ['no commerce or provider behavior', !/checkout|payment|order_status|JustAnotherPanel|JAP|service[_ -]?id/i.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) process.exit(1);
console.log('Directory owner utility strip contract passed.');

