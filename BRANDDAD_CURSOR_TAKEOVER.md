# BrandDad Cursor takeover tracker

Authoritative Cursor takeover of remaining Social + Directory, including Codex backend already in this tree. Do not wait for Codex. Do not start a new project. Do not overwrite newer live files with older local copies.

Last cycle 2026-11-21 ~3:25pm America/Cancun. Cycle: inspect → implement → test → save → continue.

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
| FormWise execution/provisioning keys | **Owner blocker.** Never invent. Flags stay off. Local execution **0.2.4**: `process_job` is `execution_not_configured` without `BDS_FORMWISE_EXECUTION_KEY`. Stored `_bds_inputs` are re-allowlisted before any FormWise POST (`remote_id` / `origin` / `credit_cost` dropped; forced fields win). Trial cannot call `listing_studio` or `linkedin_profile`. Public `/ai/tools` omits provider ids. `bds_ai_exec_create_access` ignores client `access_mode`; a foreign listing id cannot spend Directory free AI. Subscriptions **0.5.2**: unpaid or mismatched orders are not the current paid order. |
| Trial API | Live `GET /wp-json/branddad/v1/trial` **401** anonymous. Local mapping tests: 7-day terms only, paid wins over trial, trial does not grant Directory/Featured/member price/Travel. Trial MU **0.1.2**. |
| Entitlements | Social `BDS_AI_ENTITLEMENTS_ENABLED` already on; FormWise keys absent so lifecycle stays fail-closed. Local `bds_ai_exec_choose_access` tests: paid wins, empty facts grant nothing. Follow-up worker **0.1.2** stays off (`BDS_FOLLOWUP_ENABLED` unset): no `wp_mail`, no hourly schedule. Flags not flipped here. |
| Featured checkout | Products live. Adapter live. Local **0.2.21** uses **Subscribe monthly**, leftover Subscribe now / See plans rewrites, listing prompt, events/jobs/classifieds/travel/gifts doors, classifieds alias, faq/sitemap → help. Live `/featured-advertising/` still says Subscribe now until File Manager deploy. Stripe present with cart. No test charge. |
| Webhooks | Woo/WPS hooks already in trial + subscriptions + featured adapter. Directory membership HMAC **0.1.3** is fail-closed: empty key disables, timestamp >300s and body tamper refuse, `hash_equals`. Consent checkbox must be checked (`1`/`yes`/`true`/`on`); unchecked never provisions. Tests use a dummy local HMAC, not a Stripe or FormWise secret. Shop-manager Tools **Billing inspect** counts active Woo webhooks as a boolean. Stripe Dashboard delivery is owner inspect. |
| Account-link | Local **0.1.3**. Empty or short `BDS_ACCOUNT_LINK_SECRET` keeps `secret_ready` false. `create_token` is `link_unavailable`; start maps that to HTTP **503**. The handoff form is hidden. Tests do not invent a secret. |
| Lifecycle is_paid | Local **0.4.2**. `payment_confirmed` records only when Woo `is_paid()` is true. Featured also needs an allowlisted lane. Refunds record only after a matching `payment_confirmed`. Trial stays `trial_started`, not paid. No `wp_mail`. Tests do not charge. |
| Onboarding website SSRF | Local **0.1.1**. Stored `website_url` is http/https only, no credentials, ports 80/443, no localhost/.local/.internal, no literal IPs. The function never fetches. Invalid URLs are 422. |
| Subscription order map | Local **0.5.2**. `bds_ai_order_matches_subscription` requires the parent order or an authoritative renewal for that subscription. Unpaid or mismatched ids return 0 from `bds_ai_paid_order_id_or_zero`. Tests do not charge. |
| Customer status API | Local **0.3.1**. Status/value use the signed-in session user, never a request user id. Email verification defaults false. Credits are not converted into a provider dollar amount. Revenue, rankings, and unpublished outcomes stay `unavailable`. |
| Provider-cost ledger | Local **0.1.2**. Internal record API only (no REST POST). Guessed providers, products missing from the catalog, estimated bases, zero amounts, and timestamps outside the 7-year window return 0. Admin contribution is `manage_woocommerce` GET. Tests do not charge. |
| Directory free AI | Local **0.1.1**. Eligibility is published `at_biz_dir` + claim claimed/approved/verified + author === claimed_by === user. Execution stays closed without `BDS_DIRECTORY_FREE_AI_EXECUTION_ENABLED` and a FormWise key (neither set). Free AI does not grant Featured or Travel. Live-copy MU under `deploy/cursor/` was not edited. |
| Featured geo | Local 25km **and** same ISO-2 country; regional 100km **and** same ISO-2 country (not radius-only). National ISO country. Global worldwide. Missing country/lat fails closed for non-global. Adapter **0.1.2**. Featured MU stamp stays **0.2.21**. Prices stay 9.99 / 19.99 / 39.99 / 99.99. |
| Featured grant | Adapter **0.1.2**. Allowlisted SKUs only. `product_ready` requires the catalog monthly, `_wps_sfw_product=yes`, and interval 1 month. `is_paid()` false, inactive sub, unlinked order, unowned listing, missing geo, or a stale next-payment date cannot activate. Legacy `bds_feat_activate_paid_order` hooks stay removed. Tests do not charge. |
| Sales catalog contract | Local catalog **0.3.4**. Live Social catalog **0.4.0** — do not upload local over live. Anonymous `/sales/catalog` contract: Essentials $29.99 for_sale when published. Staged/quote-only (complete packages, DFY, music) have no checkout URL. Assistant stays closed without FormWise keys. |
| Lead / inquiry / monitor | Flags still off (503). Lead **0.1.2** follow-up only after consent **and** verified email. Inquiries **0.1.2** honeypot `company_website` is 422 and never fetched; privacy_ack + contact_consent required. Monitor **0.1.1** SSRF: http/https, no credentials, ports 80/443 only, literal IP hosts refused, DNS A/AAAA must all be public, `wp_safe_remote_get` timeout 10 / redirection 0 / 64KB. |
| Sales chat | Chat is 503 `assistant_unavailable` unless `BDS_SALES_ASSISTANT_ENABLED` and a non-empty `BDS_FORMWISE_EXECUTION_KEY`. Catalog GET stays public. Model links are stripped. No FormWise key invented. |
| Airbnb one-review | Slug `branddad-ai-review-dispute-assistant` is not in the commercial catalog. Launch `bds_ai_launch_product_url` is empty while unpublished. Homepage copy stays gated (“Secure checkout is in final testing”). Launch **0.5.26** and catalog **0.3.4** were not edited. |
| GitHub wp-cron schedule | Rechecked 2026-11-21 ~3:15pm America/Cancun: **two** schedule runs, both success. `44905829871` at 2026-11-21T12:49:47Z (7:49am America/Cancun) and `44943159190` at 2026-11-21T19:51:22Z (2:51pm America/Cancun). About 7 hours apart. Not a 5-minute cadence. Spawn MU stays. **Do not add StartHost cron.** |

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
| Sales help / conversion / lang | Sales-help **0.1.7** answers Featured, music, and gift cards. It does not render on checkout, cart, My Account, account-hub, or studio-boards (those already have a next step). Conversion **0.1.2** counts hub and door clicks. Lang-switch **0.1.4** uses `#0668d8` and 44px taps. Follow **0.1.5** has Featured/music/gift copy plus `#0668d8` / 44px / focus-visible (does not send). |
| Events / jobs / classifieds | Live `/events/` and `/jobs/` already door. `/classifieds/` was 404; local aliases to `all-listings/?directory_type=classifieds`. Gift and flights `directory_type` queries now map to marketplace/travel copy. Featured MU stamp stays **0.2.21**. |
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
- Featured geo: missing ISO country fails non-global billing; local/regional also need a real lat/lng **and** the same ISO-2 country as the viewer (radius alone is not enough).
- Catalog: staged/quote_only have no checkout URL. Anonymous sales catalog is Essentials. Local 0.3.4 must not overwrite live 0.4.0.

## Secure design (account-link empty secret)

- Identity: a signed-in WordPress user with a verified email, handing off between `branddad.social` and `directory.branddad.social` only.
- AuthN: HMAC over a five-minute purpose-bound token. Empty or short `BDS_ACCOUNT_LINK_SECRET` disables minting (`link_unavailable` → HTTP 503) and verification. Email verification filter defaults false.
- AuthZ: issuer and audience must be the allowlisted host pair and must differ. Replay id is single-use. Destination return paths are allowlisted.
- Secrets: tests do not define the secret. Form is not printed when the secret is missing.

## Secure design (lifecycle is_paid mapping)

- Identity: Woo customer on the paid order; refunds attach to that order after a confirmed payment event.
- AuthN: payment truth is Woo `is_paid()`, not a browser event or client total.
- AuthZ: unpaid orders do not write `payment_confirmed`. Featured needs an allowlisted lane. Refunds require a prior confirmed payment and a `WC_Order_Refund`. Trial is `trial_started` only.
- Secrets: no Stripe or FormWise keys. No mail.

## Secure design (Featured SKU / is_paid grant)

- Identity: signed-in Directory listing owner at add-to-cart; grant customer_id is that owner, not a client-supplied user id.
- AuthN: WordPress session at cart. Payment truth is Woo `is_paid()` after the live gateway. Tests never create an order or charge.
- AuthZ: allowlisted SKU (`bds-feat-*`) plus `product_ready` (catalog monthly, `_wps_sfw_product=yes`, interval 1 month). Grant also needs a linked active subscription, order user = listing owner, owned listing, geo, and a next-payment date in the future. Unpaid or inactive returns `unpaid_or_inactive` and writes no grant.
- Listing id: `absint` from POST/GET. Ownership is the Directory eligibility helper, not the request.
- Legacy: `bds_feat_activate_paid_order` stays unhooked so processing/completed cannot grant without the adapter gates.
- Secrets: no Stripe or FormWise keys in source. No flag flip.

## Secure design (FormWise execution + follow-up mail)

- Identity: logged-in WordPress customer for AI jobs; follow-up recipients are consent-list rows with a verified email.
- AuthN: WP cookie + REST nonce on `/ai/jobs`. FormWise is a service principal with a host-held bearer. Absence of `BDS_FORMWISE_EXECUTION_KEY` is a closed gate (`execution_not_configured`), not a silent allow.
- AuthZ: only allowlisted tool keys and array inputs may reach the provider. Browser cannot pick remote_id, origin, or credit cost. Provisioning DELETE requires `bds_ai_subscription_readiness()` (recurring engine + provisioning key + entitlements flag).
- Follow-up: `BDS_FOLLOWUP_ENABLED` plus a verified From address gate **send and schedule**. Unset flag means no `wp_mail` and no hourly event. Consent must still be explicit and fresh before any later send.
- Secrets: tests do not define FormWise keys, From addresses, or enable flags. No Stripe keys in source. Outbound FormWise uses `wp_safe_remote_post` with redirection 0 only after the key gate.

## Secure design (lead / inquiry / monitor SSRF)

- Identity: public lead/inquiry posts from BrandDad HTTPS origins; monitoring is a logged-in paid subscriber with `authorization_confirmed`.
- AuthN: WordPress cookie on private inquiry/monitor routes. Public capture has no session; origin allowlist + rate limit + flag.
- AuthZ: flags `BDS_LEAD_CAPTURE_ENABLED`, `BDS_CUSTOMER_INQUIRIES_ENABLED`, `BDS_WEBSITE_MONITORING_ENABLED` gate **access** (503), not only UI. Inquiry list/update is author-scoped. Monitor fetch is paid-plan allowlist only.
- Secrets: no FormWise or Stripe keys in source. Tests do not define enable flags or invent keys.
- SSRF: monitor URL is `esc_url_raw` http/https, no userinfo, ports 80/443, literal IPs refused, DNS must resolve only public A/AAAA, then `wp_safe_remote_get` with redirection 0. Lead and inquiry never call `wp_remote_*`. Honeypot website is rejected and not fetched. Metadata `169.254.169.254` is refused.
- Consent: inquiry needs privacy_ack **and** contact_consent. Lead email follow-up is stored only when consent **and** verified email are both true.
- Sales chat: FormWise is not called when the key is missing; first line of chat is 503. Output URLs are stripped.
- Airbnb one-review: unpublished product has no buy URL. Catalog does not list the slug.

## Secure design (onboarding website URL)

- Identity: a signed-in customer storing a website URL on their onboarding profile. The URL is stored only.
- AuthN: WordPress cookie on the onboarding REST. Public visitors cannot write a profile URL.
- AuthZ: stored URLs are http/https, no userinfo, ports 80/443, no localhost/.local/.internal, no literal IPs. A filled invalid URL is 422. Empty is allowed. The helper never fetches, so a later monitor pass cannot inherit a stored SSRF vector from onboarding.
- Secrets: no FormWise or Stripe keys. Tests do not enable flags.

## Secure design (subscription unpaid / mismatch)

- Identity: the Woo subscription owner. The current paid order is derived from Woo facts, not a client-supplied order id.
- AuthN: payment truth is Woo `is_paid()`. Tests never create an order or charge.
- AuthZ: `bds_ai_order_matches_subscription` requires the parent order or an authoritative renewal tagged to that subscription. Unpaid, mismatched, or missing ids return 0 from `bds_ai_paid_order_id_or_zero`. Provision still requires readiness, active status, and is not in a 7-day trial window unless this is a renewal.
- Secrets: tests do not define FormWise or Stripe keys. Entitlements flag stays off.

## Secure design (customer status API)

- Identity: the signed-in WordPress customer. Status and value reports name that session user only.
- AuthN: WordPress cookie. Anonymous GET is refused by `is_user_logged_in`.
- AuthZ: `bds_customer_status_subject_user_id` ignores a requested user id. Access mode is paid/trial from plan facts, else `directory_free` only when a listing id exists, else `none`. Email verification filter defaults false.
- Data: credits stay units. `provider_cost` is the string `unavailable`. Revenue, rankings, social posts, blogs, and unpublished delivery outcomes are not inferred.
- Secrets: no FormWise or Stripe keys. No outbound fetch.

## Secure design (provider-cost ledger)

- Identity: WooCommerce administrators for the contribution GET; verified adapters for the internal record API.
- AuthN: `manage_woocommerce` on `/admin/finance/contribution`. There is no REST POST for costs.
- AuthZ: record requires an allowlisted provider, a commercial-catalog product, an allowlisted basis (`provider_reported` / `processor_reported` / `invoice_reconciled` / `contract_unit_reconciled`), ISO currency, amount 1–100000000 minor units, idempotency 8–200 chars, and a timestamp in the last 7 years (not more than 300s future). Estimated or guessed rows return 0.
- Secrets: idempotency is stored as HMAC with `wp_salt('auth')`, not in the clear. Tests do not charge.

## Secure design (Directory free AI)

- Identity: the verified Directory listing owner. Listing id in a shortcode or job is re-checked against post author + claim meta, not trusted from the request.
- AuthN: WordPress cookie for the status shortcode. Anonymous output is empty.
- AuthZ: published `at_biz_dir` + claim `claimed`/`approved`/`verified` + author === claimed_by === user. Draft, other authors, pending claims, and user 0 are ineligible. Allowance remaining cannot go negative. Unlock only deletes `bds_dir_free_ai_lock_*`. Browser junk periods do not become option keys.
- Execution: `bds_dir_free_ai_execution_ready()` is false unless `BDS_DIRECTORY_FREE_AI_EXECUTION_ENABLED` and a non-empty `BDS_FORMWISE_EXECUTION_KEY`. Tests set neither. This MU does not call FormWise. Free AI does not grant Featured or Travel.
- Secrets: no keys in source. Live-copy `deploy/cursor/bds-directory-free-ai-mu.php` was not edited.

## Secure design (Featured regional country)

- Identity: Directory viewers vs a paid Featured listing's stored ISO-2 country and coordinates.
- AuthZ: `bds_feat_in_scope` requires `bds_feat_same_country` for every non-global lane, then radius for local (25km) and regional (100km). A border pair inside 100km with different ISO codes is out of scope. Empty or three-letter country codes fail closed.
- Secrets: no keys. No charge. Featured MU version stamp stays 0.2.21 so the conversion deploy list is unchanged.

## Secure design (execution job inputs)

- Ingestion: browser REST JSON → `bds_ai_exec_sanitize_provider_inputs` (catalog field schema, per-field max, 32KB encoded) → stored `_bds_inputs` JSON (`json_decode`, never `unserialize`) → `process_job` re-allowlists before FormWise. Tampered extra keys including `remote_id`, `origin`, and `credit_cost` are dropped. Forced catalog fields always win.
- AuthZ: WordPress cookie + REST nonce. Job GET is author-scoped 404. Tool URLs use catalog `remote_id` only. Trial tools require the catalog `trial` flag (`listing_studio` and `linkedin_profile` are paid-only). Directory free AI requires a catalog `free_key`.
- Public `/ai/tools` omits `remote_id`. FormWise POST is `wp_safe_remote_post` timeout 45 / redirection 0 / 128KB. Response text is tag-stripped and capped at 12k. Network errors fail closed and are not auto-retried.
- Secrets: tests do not invent `BDS_FORMWISE_EXECUTION_KEY`. Missing key stays `execution_not_configured`.

## Secure design (execution directory_free BOLA)

- Identity: the signed-in job author. Listing ownership is Directory claim facts, not the JSON body.
- AuthN: WordPress cookie on `/ai/jobs`. FormWise is still closed without `BDS_FORMWISE_EXECUTION_KEY`.
- AuthZ: `bds_ai_exec_create_access` ignores client `access_mode`. Paid/trial plan wins and clears listing_id. `directory_free` requires a non-zero session user **and** `bds_dir_free_ai_listing_eligible`. A foreign listing id resolves to mode `none` / listing 0 (`subscription_required`).
- Secrets: tests do not invent FormWise keys or enable execution flags.

## This pass

- FM still login (hwnd 197924 at `-2400`, title Sign in to Client Panel). Credentials not typed. Catalog 0.3.4 and launch 0.5.26 not touched. Telegram and Windows Security left alone. No Subscribe now rewrites piled. No StartHost cron added. Live classifieds/faq still wait on FM; not stalled.
- Track B: execution **0.2.4** re-allowlists stored job inputs before FormWise. Extra `remote_id` / `origin` / `credit_cost` dropped. Forced fields win. Trial cannot call listing_studio or linkedin_profile. Public tools omit provider ids.
- GitHub wp-cron remains **two** successful schedule runs ~7 hours apart (`44905829871` 7:49am, `44943159190` 2:51pm America/Cancun). Not a 5-minute cadence. Spawn MU stays. Do not add StartHost cron.
- Suites: formwise-followup-failclosed; execution-access-bola. rafter secrets empty. `rafter run` quota remains exhausted until 2026-11-23.
- Deploy when FM signs in (including featured lib country match and gift type map): **bridge 0.1.6 / hub 0.2.9 / featured 0.2.21 / revenue 0.2.29 / sales-help 0.1.7 / follow 0.1.5 / conversion 0.1.2 / lang 0.1.4 / free-listing 0.1.6**. Execution 0.2.4 is Codex backend on Social; upload with the conversion set only if that file is in the signed-in FM tree.
- Flags off. No charge. Not pushed.
