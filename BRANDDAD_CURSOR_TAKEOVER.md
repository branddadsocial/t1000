# BrandDad Cursor takeover tracker

Authoritative Cursor takeover of remaining Social + Directory, including Codex backend already in this tree. Do not wait for Codex. Do not start a new project. Do not overwrite newer live files with older local copies.

Last cycle 2026-11-21 ~10:08am America/Cancun. Cycle: inspect → implement → test → save → continue.

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
| Trial API | Live `GET /wp-json/branddad/v1/trial` **401** anonymous. Local mapping tests: 7-day terms only, paid wins over trial, trial does not grant Directory/Featured/member price/Travel. Trial MU **0.1.2**. |
| Entitlements | Social `BDS_AI_ENTITLEMENTS_ENABLED` already on; FormWise keys absent so lifecycle stays fail-closed. Local `bds_ai_exec_choose_access` tests: paid wins, empty facts grant nothing. Flags not flipped here. |
| Featured checkout | Products live. Adapter live. Local **0.2.21** uses **Subscribe monthly**, leftover Subscribe now / See plans rewrites, listing prompt, events/jobs/classifieds/travel/gifts doors, classifieds alias, faq/sitemap → help. Live `/featured-advertising/` still says Subscribe now until File Manager deploy. Stripe present with cart. No test charge. |
| Webhooks | Woo/WPS hooks already in trial + subscriptions + featured adapter. Directory membership HMAC **0.1.3** is fail-closed: empty key disables, timestamp >300s and body tamper refuse, `hash_equals`. Consent checkbox must be checked (`1`/`yes`/`true`/`on`); unchecked never provisions. Tests use a dummy local HMAC, not a Stripe or FormWise secret. Shop-manager Tools **Billing inspect** counts active Woo webhooks as a boolean. Stripe Dashboard delivery is owner inspect. |
| Featured geo | Local 25km, regional 100km, national ISO country, global worldwide. Missing country/lat fails closed for non-global billing. Adapter **0.1.2**. Prices stay 9.99 / 19.99 / 39.99 / 99.99. |
| Featured grant | Adapter **0.1.2**. Allowlisted SKUs only. `product_ready` requires the catalog monthly, `_wps_sfw_product=yes`, and interval 1 month. `is_paid()` false, inactive sub, unlinked order, unowned listing, missing geo, or a stale next-payment date cannot activate. Legacy `bds_feat_activate_paid_order` hooks stay removed. Tests do not charge. |
| Sales catalog contract | Local catalog **0.3.4**. Live Social catalog **0.4.0** — do not upload local over live. Anonymous `/sales/catalog` contract: Essentials $29.99 for_sale when published. Staged/quote-only (complete packages, DFY, music) have no checkout URL. Assistant stays closed without FormWise keys. |
| Lead / inquiry / monitor | Flags still off (503). Lead **0.1.2** follow-up only after consent **and** verified email. Inquiries **0.1.2** honeypot `company_website` is 422 and never fetched; privacy_ack + contact_consent required. Monitor **0.1.1** SSRF: http/https, no credentials, ports 80/443 only, literal IP hosts refused, DNS A/AAAA must all be public, `wp_safe_remote_get` timeout 10 / redirection 0 / 64KB. |
| Sales chat | Chat is 503 `assistant_unavailable` unless `BDS_SALES_ASSISTANT_ENABLED` and a non-empty `BDS_FORMWISE_EXECUTION_KEY`. Catalog GET stays public. Model links are stripped. No FormWise key invented. |
| Airbnb one-review | Slug `branddad-ai-review-dispute-assistant` is not in the commercial catalog. Launch `bds_ai_launch_product_url` is empty while unpublished. Homepage copy stays gated (“Secure checkout is in final testing”). Launch **0.5.26** and catalog **0.3.4** were not edited. |
| GitHub wp-cron schedule | Reconfirmed 2026-11-21 ~10:02am America/Cancun: still **one** schedule run `44905829871` success 2026-11-21T12:49:47Z (7:49am America/Cancun). Not a 5-minute cadence. Spawn MU stays. **Do not add StartHost cron.** |

## Track A — CX (independent)

| Item | Status |
|---|---|
| Launch trial copy | Live 0.5.26. Buttons “Start 7-day trial” / “Try it free for 7 days.” |
| Hub trial + account API wiring | Local bridge **0.1.6** (live still 0.1.0 until FM). Surfaces **0.2.9** guest “See membership plans” → Directory `/select-your-plan/`. Live `/account-hub/` still says See plans until FM. |
| Featured listing attach | Local **0.2.21** listing prompt, leftover Subscribe now / See plans rewrites, events/jobs/classifieds/travel/hotels/gifts doors, sign-in and empty-search copy, classifieds alias, faq/sitemap → help, demo refund modal hidden. Live Featured buy still Subscribe now until FM. |
| Revenue live CTAs | Local revenue **0.2.29**. Shop archive prints the live plan strip. Sitemap leftover URLs open services. Leftover Subscribe now / Choose this plan / See plans rewrites. Quote-only contact copy. Privacy/terms/press/refunds next-step band. Cookie leftover URLs open privacy. Live `/services/` still mixes Subscribe now until FM. |
| Dashboards / studios | Live `/studio-boards/` on both hosts. Local copy is inquiry-only and does not start checkout. Directory `/dashboard/` gets `#bds-hub-dashboard`. |
| Account emails | Copy only in bridge **0.1.6**. New-account and reset-password additional content. Does not `wp_mail`. |
| Accessibility | `#0668d8` + `:focus-visible` on hub, featured, revenue, bridge, sales-help, follow, lang-switch, free-listing. Sticky help respects `prefers-reduced-motion`. |
| Gift / travel / legal | Gift marketplace copy is not a BrandDad charge. Travel/hotels do not sell tickets. Social privacy/terms get the next-step band. |
| Sales help / conversion / lang | Sales-help **0.1.7** answers Featured, music, and gift cards. Conversion **0.1.2** counts hub and door clicks. Lang-switch **0.1.4** uses `#0668d8` and 44px taps. Follow **0.1.5** has Featured/music/gift copy plus `#0668d8` / 44px / focus-visible (does not send). |
| Events / jobs / classifieds | Live `/events/` and `/jobs/` already door. `/classifieds/` was 404; local aliases to `all-listings/?directory_type=classifieds`. |
| Memberships / blogs | `/memberships/`, `/membership/`, `/plans/` alias to Select your plan. Directory blog door does not sell a listing. |
| Music / quote-only | Live home already has music brief and “does not take payment.” DFY contact note is quote-only. |
| Mobile contrast | Sales help **0.1.7** and lang-switch **0.1.4** use `#0668d8`, 44px taps, and focus rings. Contrast suite includes them. |
| Checkout fail-closed copy | Local bridge **0.1.6** `#bds-checkout-failclosed` plus account-email copy. Does not say “no card”. Trial does not grant Directory. |
| `$6.99` | QA rewrite “not required” plus stored Select-your-plan Activation card. Do not flatten stored prices. |

## Owner blockers (not Cursor, not Codex code)

1. FormWise keys (execution + provisioning). Keep `BDS_FOLLOWUP_*`, lead, inquiry, monitor, sales-assistant flags off until keys exist.
2. Stripe reconnect / webhook signing secret in Woo or Stripe Dashboard if renewals fail. Inspect-only from Cursor.
3. SSH restore from this PC.
4. **File Manager session.** Parked Chrome hwnd 197924 is on Client Panel login (`https://cp.clientpanel.us/login?...`). No credentials were typed. Deploy **bridge 0.1.6 / hub 0.2.9 / featured 0.2.21 / revenue 0.2.29 / sales-help 0.1.7 / follow 0.1.5 / conversion 0.1.2 / lang 0.1.4 / free-listing 0.1.6** when signed in. Do not start a new Chrome window.
5. StartHost Scheduled Tasks still “Cron failed to add.” Do not enable while GitHub + spawn MU exist.
6. Live test charge authorization. None given.

## Secure design (billing inspect)

- Identity: humans who already have WordPress `manage_woocommerce`.
- AuthN: existing WP cookie. No new login, JWT, or token.
- AuthZ: capability check on the Tools callback and on the hub band. Missing cap prints nothing.
- Secrets: snapshot is booleans from `defined()` / gateway `enabled`. Values are never echoed. Webhook URLs are not listed.
- No writes, no REST, no charge, no flag flip.

## Secure design (membership webhook + trial mapping)

- Identity: Social checkout customer who checked Directory consent; Directory receiver is host-locked to `directory.branddad.social`.
- AuthN: HMAC-SHA256 over `timestamp + body hash` with a derived key. Empty `BDS_ACCOUNT_LINK_SECRET` disables the bridge (503). Tests use a dummy local HMAC, never a Stripe or FormWise key.
- AuthZ: signature first; then event id must match the Idempotency-Key; receiver creates/reuses a subscriber only. It does not create a listing or grant Pro/Elite/Featured. Unchecked checkout consent (`bds_directory_membership_consent_checked`) never writes yes; `may_provision` requires stored `yes`.
- Replay: timestamp must be within 300s; event id is 64 hex; option lock around persist.
- Trial mapping: 7-day Essentials only. Paid wins over trial. Missing facts grant nothing. Trial does not include Directory membership, Featured, 10% member price, or Travel.
- Featured geo: missing ISO country fails non-global billing; local/regional also need a real lat/lng.
- Catalog: staged/quote_only have no checkout URL. Anonymous sales catalog is Essentials. Local 0.3.4 must not overwrite live 0.4.0.

## Secure design (Featured SKU / is_paid grant)

- Identity: signed-in Directory listing owner at add-to-cart; grant customer_id is that owner, not a client-supplied user id.
- AuthN: WordPress session at cart. Payment truth is Woo `is_paid()` after the live gateway. Tests never create an order or charge.
- AuthZ: allowlisted SKU (`bds-feat-*`) plus `product_ready` (catalog monthly, `_wps_sfw_product=yes`, interval 1 month). Grant also needs a linked active subscription, order user = listing owner, owned listing, geo, and a next-payment date in the future. Unpaid or inactive returns `unpaid_or_inactive` and writes no grant.
- Listing id: `absint` from POST/GET. Ownership is the Directory eligibility helper, not the request.
- Legacy: `bds_feat_activate_paid_order` stays unhooked so processing/completed cannot grant without the adapter gates.
- Secrets: no Stripe or FormWise keys in source. No flag flip.

## Secure design (lead / inquiry / monitor SSRF)

- Identity: public lead/inquiry posts from BrandDad HTTPS origins; monitoring is a logged-in paid subscriber with `authorization_confirmed`.
- AuthN: WordPress cookie on private inquiry/monitor routes. Public capture has no session; origin allowlist + rate limit + flag.
- AuthZ: flags `BDS_LEAD_CAPTURE_ENABLED`, `BDS_CUSTOMER_INQUIRIES_ENABLED`, `BDS_WEBSITE_MONITORING_ENABLED` gate **access** (503), not only UI. Inquiry list/update is author-scoped. Monitor fetch is paid-plan allowlist only.
- Secrets: no FormWise or Stripe keys in source. Tests do not define enable flags or invent keys.
- SSRF: monitor URL is `esc_url_raw` http/https, no userinfo, ports 80/443, literal IPs refused, DNS must resolve only public A/AAAA, then `wp_safe_remote_get` with redirection 0. Lead and inquiry never call `wp_remote_*`. Honeypot website is rejected and not fetched. Metadata `169.254.169.254` is refused.
- Consent: inquiry needs privacy_ack **and** contact_consent. Lead email follow-up is stored only when consent **and** verified email are both true.
- Sales chat: FormWise is not called when the key is missing; first line of chat is 503. Output URLs are stripped.
- Airbnb one-review: unpublished product has no buy URL. Catalog does not list the slug.

## This pass

- FM still login (hwnd 197924 at `-2400`, title Sign in to Client Panel). Credentials not typed. Catalog 0.3.4 and launch 0.5.26 not touched. Telegram and Windows Security left alone. No extra live-copy MU. No StartHost cron added.
- Track B: Featured `product_ready` / SKU / `is_paid` grant unit tests. Adapter **0.1.2**. Next fail-closed: Directory membership consent checkbox never provisions unless stored `yes` (bridge **0.1.3**).
- Suites: featured-grant, featured-geo, webhook-signature, featured-rotation. php -l clean. rafter secrets empty on the edited adapters and grant tests. `rafter run` quota remains exhausted until 2026-11-23.
- Deploy when FM signs in (conversion MUs, unchanged): **bridge 0.1.6 / hub 0.2.9 / featured 0.2.21 / revenue 0.2.29 / sales-help 0.1.7 / follow 0.1.5 / conversion 0.1.2 / lang 0.1.4 / free-listing 0.1.6**.
- Flags off. No charge. Not pushed.
