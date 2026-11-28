import fs from 'node:fs';

const source = fs.readFileSync('deploy/zzzzzzzzz-bds-home-bottom-polish-mu.php', 'utf8');
const checks = [
  ['version is explicit', /BDS_HOME_BOTTOM_POLISH_VERSION', '1\.1\.0'/.test(source)],
  ['styles are limited to Social home and Services', /branddad\.social[\s\S]*www\.branddad\.social[\s\S]*is_front_page[\s\S]*is_page[\s\S]*services/.test(source)],
  ['creator section uses light text on dark background', /#bds-bv-creator-strip[\s\S]*#071a33[\s\S]*color:#fff/.test(source)],
  ['creator actions wrap responsively', /#bds-bv-creator-strip>p:last-child\{display:flex;flex-wrap:wrap/.test(source)],
  ['lead form has a responsive two-column layout', /#bds-codex-lead\{[\s\S]*grid-template-columns:repeat\(2,minmax\(0,1fr\)\)/.test(source)],
  ['checkboxes keep compact dimensions', /width:20px!important;height:20px!important/.test(source)],
  ['mobile actions and form collapse to one column', /@media\(max-width:720px\)[\s\S]*grid-template-columns:1fr/.test(source)],
  ['lead endpoint behavior is not reimplemented', !/register_rest_route|fetch\(|XMLHttpRequest|wp_remote_/.test(source)],
  ['retired panel is not referenced', !/JustAnotherPanel|JAP|service[_ -]?id/i.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) process.exit(1);
console.log('Homepage bottom polish contract passed.');

