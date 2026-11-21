# BrandDad Cursor takeover tracker

Authoritative Cursor takeover of remaining Social + Directory, including Codex backend already in this tree. Do not wait for Codex. Do not start a new project. Do not overwrite newer live files with older local copies.

Last cycle 2026-11-21 ~9:45am America/Cancun. Cycle: inspect → implement → test → save → continue.

## Hard rules

- Keep GitHub wp-cron workflow. Do not add StartHost cron.
- Keep Social spawn MU `bds-wp-cron-spawn-mu.php` 0.1.0.
- Work Chrome stays off-screen (`-2400`).
- Preserve existing PHP suites. Fail-closed secrets. No live charges without authorization.
- Mail / lead / FormWise / monitor flags stay off until owner gates pass.
- Local commercial catalog is 0.3.4. Live Social catalog is newer (0.4.0). Do not upload the local catalog over live.

## Trees vs handoff

| Tree | Role |
|---|---|
| `branddad-cursor-revenue` (this worktree, branch `cursor-slim-020`) | Cursor conversion + Codex backend copies under `deploy/cursor` and `deploy/cursor/backend` |
| Live Social `mu-plugins` | Launch 0.5.26, spawn 0.1.0, account-link 0.1.2, Codex REST `branddad/v1` (21 routes) |
| Live Directory `mu-plugins` | Featured adapter 0.1.0, account-link 0.1.2, Codex REST subset (account/onboarding/analytics/free-membership) |
| `branddad-seoestore` | Dirty Codex checkout. Do not edit. Do not deploy its older catalog over live. |

Handoff docs (`BRANDDAD_CODEX_HANDOFF.md`, `BRANDDAD_CURSOR_BLOCKERS.md`) still say Codex owns billing/FormWise. Takeover supersedes the wait: Cursor implements fail-closed backend already in tree. Owner still holds secrets and reconnect clicks.

## Track B — revenue (highest)

| Item | Status |
|---|---|
| Stripe inspect (no reconnect) | Social Essentials product + Directory checkout-with-cart both load `wc-stripe` and `pk_live` (length 107, value not printed). No reconnect click. No charge. |
| Stripe reconnect | **Owner blocker.** Dashboard-only if a webhook signing secret or account link is stale. |
| FormWise execution/provisioning keys | **Owner blocker.** Never invent. Flags stay off. |
| Trial API | Live `GET /wp-json/branddad/v1/trial` **401** anonymous. Provisioning is WP Swings 7-day terms after a real checkout. |
| Entitlements | Social `BDS_AI_ENTITLEMENTS_ENABLED` already on; FormWise keys absent so lifecycle stays fail-closed. |
| Featured checkout | Products live. Adapter live. Local 0.2.14 uses **Subscribe monthly** plus a fail-closed listing prompt. Live `/featured-advertising/` still says Subscribe now until File Manager deploy. Stripe present with cart. No test charge. |
| Webhooks | Woo/WPS hooks already in trial + subscriptions + featured adapter. Shop-manager Tools **Billing inspect** (local bridge 0.1.3) counts active Woo webhooks as a boolean. Stripe Dashboard delivery list is owner inspect. |
| GitHub wp-cron schedule | Only schedule run remains `44905829871` success 2026-11-21T12:49:47Z (7:49am America/Cancun). Four earlier `workflow_dispatch` successes. Not a 5-minute cadence. Spawn MU stays. Do not add StartHost cron. |

## Track A — CX (independent)

| Item | Status |
|---|---|
| Launch trial copy | Live 0.5.26. Buttons “Start 7-day trial” / “Try it free for 7 days.” |
| Hub trial + account API wiring | Local bridge **0.1.4** (live still 0.1.0 until FM). Surfaces **0.2.7** trial band + shop-manager ops inspect. Live `/account-hub/` already prints the hub. |
| Featured listing attach | Local 0.2.15 listing prompt + **List my business free** on buy/claim. Live `/featured-advertising/` still Subscribe now until FM. |
| Revenue live CTAs | Local revenue **0.2.23**. Help gets the next-step band. Live `/services/` still mixes Subscribe now until FM. |
| Checkout fail-closed copy | Local bridge **0.1.4** `#bds-checkout-failclosed`. Does not say “no card”. Trial does not grant Directory. |
| `$6.99` | QA rewrite “not required” plus stored Select-your-plan Activation card. Do not flatten stored prices. |

## Owner blockers (not Cursor, not Codex code)

1. FormWise keys (execution + provisioning). Keep `BDS_FOLLOWUP_*`, lead, inquiry, monitor, sales-assistant flags off until keys exist.
2. Stripe reconnect / webhook signing secret in Woo or Stripe Dashboard if renewals fail. Inspect-only from Cursor.
3. SSH restore from this PC.
4. **File Manager session.** Parked Chrome hwnd 197924 is on Client Panel login (`https://cp.clientpanel.us/login?...`). No credentials were typed. Deploy of 0.1.4 / 0.2.7 / 0.2.15 / 0.2.23 / sales-help 0.1.4 waits on a signed-in File Manager tab. Do not start a new Chrome window.
5. StartHost Scheduled Tasks still “Cron failed to add.” Do not enable while GitHub + spawn MU exist.
6. Live test charge authorization. None given.

## Secure design (billing inspect)

- Identity: humans who already have WordPress `manage_woocommerce`.
- AuthN: existing WP cookie. No new login, JWT, or token.
- AuthZ: capability check on the Tools callback and on the hub band. Missing cap prints nothing.
- Secrets: snapshot is booleans from `defined()` / gateway `enabled`. Values are never echoed. Webhook URLs are not listed.
- No writes, no REST, no charge, no flag flip.

## This pass

- FM still login (hwnd 197924 at `-2400`). Credentials not typed. Catalog 0.3.4 and launch 0.5.26 not touched.
- Live public pages: help has sales-help + guide-next. `/account-hub/` has the hub. Registration/add-listing have `#bds-free-steps`. Claim has `#bds-feat-claim-ctas`. Featured buy still Subscribe now. `$6.99` leftover is the “not required” rewrite. Checkout “no card” is a Stripe JS error string, not a customer claim.
- Track A: bridge **0.1.4** fail-closed checkout note; featured **0.2.15** List my business free on buy/claim; sales-help **0.1.4** trial does not grant Directory; revenue **0.2.23** help path gets the next-step band.
- Suites: codex-bridge, featured-rotation, revenue-sections, sales-help passed.
- Flags off. No charge. Not pushed.
