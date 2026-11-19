# BrandDad Codex handoff

## Secure design for this pass

- Humans use the existing WordPress cookie session. Grants require `manage_options` plus a nonce. Absence of that capability is a 403.
- Music briefs are public ingestion with a nonce, honeypot, five submissions per hour per hashed IP, and an allowlisted package. No file upload and no outbound fetch.
- Briefs are private posts. They contain name, email, and business story. They do not contain card data. Retention follows normal WordPress private-post deletion by an administrator.
- Paid featured inventory cannot be activated from a client-supplied price. The server checks Woo `is_paid()` or leaves the grant pending.
- Saved businesses use the existing WordPress cookie. The handler writes only `get_current_user_id()` meta, checks the save nonce, and accepts a published Directory listing. A guest is a 403. The pure list logic caps the account at 50 ids.
- Featured events are humans or their browser, not a second account system. Each token is HMAC-SHA256 with `wp_salt( 'auth' )`, expires in 15 minutes, and is bound to one grant, one event type, and one event id. A mismatched token is 403. The counter function refuses a client total and a duplicate event. Absence of a valid token does not increment.
- Audit rows store user ID, action, grant ID, and time in a WordPress option capped at 500. That log is not an append-only external store.
- Threat notes: a stolen admin session can create partnership grants; a forged impression cannot be posted because no public counter exists; a bot can submit five briefs an hour; payment fraud would have to satisfy Woo, which this plugin does not bypass.

## Vexp and Rafter

- Vexp 2.6.3 is connected and indexed `branddad-seoestore` (1776 files, indexed 2026-11-13). Repo limit is reached.
- Rafter site monitors exist for `branddad.social`, `directory.branddad.social`, `branddad.co`, and `hosttech.net`.
- Latest stored scans: Social 2026-11-07 succeeded with 0 critical and 2 warnings. Directory 2026-09-22 succeeded with 0 critical and 2 warnings. Individual finding bodies were not returned by `sites_get`.

## What Cursor changed

See `BRANDDAD_CURSOR_PROGRESS.md`. No production deploy. No customer billing change.

## What Codex still owns

FormWise execution, subscription provisioning, Stripe/Woo lifecycle, shared metering, SEO execution, social publishing, WhatsApp, video providers, and security-critical workers. Cursor did not build replacements.

The account hub calls `bds_mt_user_tier()` and `bds_dir_free_ai_status()` when those functions are loaded. It does not invent a balance when they are absent. Studio boards are disabled until Codex exposes an authorized job API. Affiliate amounts stay in Partnero at `https://affiliates.branddad.social/`.
