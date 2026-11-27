import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const sourcePath = path.join(root, 'deploy', 'bds-directory-facet-browse-mu.php');
const source = fs.readFileSync(sourcePath, 'utf8');

const checks = {
  plugin_version: source.includes('Version: 1.1.4'),
  runtime_version: source.includes("define( 'BDS_FACET_BROWSE_VER', '1.1.4' );"),
  one_primary_heading: source.includes('<h1 class="bds-facet__title">'),
  retired_secondary_heading: !source.includes('<h2 class="bds-facet__title">'),
  repairs_demoted_heading: source.includes("if(title.tagName!=='H1')"),
  unhides_primary_heading: source.includes("title.removeAttribute('hidden')"),
  demotes_legacy_archive_h1: source.includes("h1.directorist-archive-title"),
};

let failed = 0;
for (const [name, passed] of Object.entries(checks)) {
  console.log(`${name}=${passed ? 'PASS' : 'FAIL'}`);
  if (!passed) failed += 1;
}

if (failed) process.exit(1);


