import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { basename } from 'node:path';

const root = new URL('../', import.meta.url);

function sha256(path) {
  return createHash('sha256').update(readFileSync(path)).digest('hex').toUpperCase();
}

function verifyManifest(manifestName) {
  const manifestPath = new URL(manifestName, root);
  const rows = readFileSync(manifestPath, 'utf8')
    .split(/\r?\n/)
    .filter(Boolean)
    .map((line) => {
      const match = line.match(/^([A-F0-9]{64}) {2}([a-z0-9-]+\.php)$/);
      assert.ok(match, `${manifestName} has a malformed line: ${line}`);
      return { expected: match[1], file: match[2] };
    });

  assert.ok(rows.length > 0, `${manifestName} must not be empty`);
  const names = rows.map(({ file }) => file);
  assert.equal(new Set(names).size, names.length, `${manifestName} contains duplicate filenames`);

  for (const { expected, file } of rows) {
    const sourcePath = new URL(`deploy/${file}`, root);
    assert.equal(sha256(sourcePath), expected, `${basename(file)} checksum is stale`);
  }
}

verifyManifest('SHA256SUMS.txt');
verifyManifest('STAGING_SHA256SUMS.txt');

const stagingRows = readFileSync(new URL('STAGING_SHA256SUMS.txt', root), 'utf8');
assert.match(stagingRows, /bds-customer-status-api-mu\.php/);
assert.match(stagingRows, /bds-000-staging-report-stages-acceptance-mu\.php/);
assert.doesNotMatch(
  readFileSync(new URL('SHA256SUMS.txt', root), 'utf8'),
  /bds-000-staging-report-stages-acceptance-mu\.php/,
  'staging-only acceptance helper must not enter the production checksum manifest',
);

console.log('release checksum contracts passed');
