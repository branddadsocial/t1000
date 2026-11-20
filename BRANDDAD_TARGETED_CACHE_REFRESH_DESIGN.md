# BrandDad Directory targeted cache refresh design

Date: 2026-11-20

## Decision

Deploy a temporary, fixed-target MU helper named `000-bds-directory-targeted-cache-refresh-mu.php`. It runs before the existing `aaa-bd-eco-early-html.php` reader, moves only four known generated HTML objects into a recoverable backup directory, records a bounded JSON receipt, and becomes inert. Clean anonymous requests then fall through to WordPress and the existing reviewed output-buffer writer recreates those exact objects from current application output.

This is narrower and more recoverable than deleting `wp-content/cache`, changing customer data, or disabling the cache system.

## Data classification and retention

- Inputs: four hard-coded SHA-256 cache filenames derived from the reviewed host/path/query/generation keys. There is no request, cookie, query, form, database, or customer input.
- Moved data: public generated Directory HTML only. No credentials, sessions, payment data, private uploads, database records, or customer-only content is in scope.
- Receipt: UTC time, helper version, moved basenames, and any fixed-target errors. It contains no PII or secrets.
- Recovery: each source is renamed to the same filesystem under a fixed hidden backup directory with `.bak` appended. Rename is atomic on the same filesystem. No unlink, wildcard, recursive delete, shell command, or cross-filesystem copy is permitted.
- Retention: keep the four backups through clean-URL verification, then remove the temporary helper and backups only under a separately reviewed cleanup action.

## Fixed scope

Allowed host: `directory.branddad.social` or `www.directory.branddad.social` only.

Allowed directory: exactly `WP_CONTENT_DIR/cache/bd-eco-html`.

Allowed basenames:

- `0c909b5d9e8254b5786c5d9cc9ab26379041978219341b04a087d2490b8c6d88.html` — homepage.
- `96bb172455b147b9efb76f0614dd83a536ffec45109657f947801161fc529ca1.html` — Events.
- `7f5574c62b542f39889fc4fa77404b0e8cf856136ec7769783a0427bb4a3cc18.html` — Jobs.
- `e494c1a4c485d5230cfc3d47acaec1df5067c6ff11c370ba2439a11f0c3d23f4.html` — Classifieds.

The helper refuses symlink sources, refuses unexpected basenames, refuses a cache path outside `WP_CONTENT_DIR/cache/bd-eco-html`, and does not follow request-provided paths.

## Data flow and trust boundaries

`[Public request] -> [WordPress MU loader] -> [fixed-target refresh helper] -> [generated HTML cache directory] -> [recoverable backup directory]`

The public request supplies no operative data; it only causes WordPress to load MU plugins. All targets are compile-time constants. The filesystem boundary is constrained to one resolved cache directory. The helper runs before the early reader so a moved object cannot be served again. Existing WordPress rendering and cache-writing code remains authoritative.

## STRIDE review

- Spoofing: no caller identity is trusted or used. A public request cannot select a host, path, target, or destination beyond the server's exact host check and hard-coded list.
- Tampering: only exact regular files matching fixed SHA-256 basenames can be renamed. Symlinks and unexpected paths are refused. Existing backups are never overwritten.
- Repudiation: a JSON receipt records the helper version, UTC timestamp, moved basenames, and bounded errors. Production verification records response headers and rendered markers.
- Information disclosure: the receipt contains no content, credentials, customer identifiers, or absolute secrets. Backups contain already-public HTML and use non-`.html` `.bak` names.
- Denial of service: at most four `is_file`/`is_link` checks and four same-filesystem renames occur once. A completion receipt makes later loads inert.
- Elevation of privilege: there is no endpoint, role change, database write, capability grant, dynamic include, or shell execution.

## Abuse twin and negative space

- Intended use: retire four stale public cache objects so current Directory output is generated.
- Abuse twin: an attacker repeatedly requests the site hoping to move arbitrary files. Mitigation: the caller controls no path or filename, the fixed receipt prevents replay, and only four regular files in the exact cache directory are eligible.
- Assumptions: the deployed `WP_CONTENT_DIR`, cache generation, and filenames match the reviewed production source. If they do not, the helper performs no destructive fallback and reports bounded errors.
- Worst single failure: a target move succeeds but regeneration fails. The original remains recoverable in the backup directory, and the site falls through to ordinary WordPress rather than losing content.

## Verification and rollback

1. PHP syntax and deterministic filesystem contract tests pass locally.
2. `rafter-code-review`, `rafter secrets`, and `rafter run --mode fast` review the exact diff. If the remote scan remains quota-blocked, production deployment remains blocked by project policy.
3. Deploy the exact reviewed file before `aaa-bd-eco-early-html.php`.
4. Request the four clean URLs once each; verify HTTP 200, one Directory Concierge, no Sales Help widget, no specialty demo refund modal, and one H1.
5. Verify cache age resets and the receipt names no unexpected file.
6. Roll back by restoring an exact `.bak` to its original basename only if current rendering fails. Never restore all cache objects blindly.

## Residual risk

The helper cannot repair a provider cache outside the WordPress filesystem. If the response remains unchanged after exact objects move and regenerate, stop and require StartHost's hostname-bounded purge. Do not escalate to broad deletion.

## 2026-11-20 review evidence

- PHP syntax passed for the helper and its test; the deterministic filesystem contract passed.
- Access control: there is no route or user-selected operation. The exact host is normalized and allowlisted at `deploy/000-bds-directory-targeted-cache-refresh-mu.php:40-44,68-71`.
- Path traversal: the cache root must resolve to the exact normalized `WP_CONTENT_DIR/cache/bd-eco-html` path at lines 73-83; backup containment is rechecked at lines 85-89; only exact compile-time basenames matching a 64-hex allowlist pass lines 50-53 and 105-109.
- Deletion/integrity: no unlink, recursive delete, wildcard, shell, SQL, HTTP fetch, or dynamic include exists. Lines 113-130 refuse symlinks and use a same-filesystem rename to a fixed `.bak` destination. Existing backups are never overwritten.
- Logging/disclosure: the receipt at lines 134-149 contains fixed basenames, version, and UTC time only; filesystem warnings are suppressed in favor of bounded error codes.
- Idempotency and scope: `tests/targeted-cache-refresh-test.php:41-60` proves the exact target list, preservation of an unrelated object, four recoverable backups, an inert second run, and wrong-host refusal.
- OWASP N/A: no authentication/session handling, cryptography, SQL, HTML rendering, outbound network call, dependency change, user deserialization, or SSRF surface exists in this diff.
- `rafter secrets` found no secrets in the helper or test. `rafter run --mode fast` was attempted on the exact branch and rejected because the monthly free quota remains 16/16. Under the project security gate, production deployment is therefore blocked; paid Plus was not used.
- Authenticated StartHost File Manager inventory independently exposed the exact Events, Jobs, and Classifieds SHA-256 basenames in the production `wp-content/cache/bd-eco-html` directory. This confirms the three fixed specialty targets match real production objects. The homepage object had already naturally regenerated and its former basename was not used as evidence for the remaining specialty refresh.
