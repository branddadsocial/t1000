# BrandDad Codex progress

Updated: 2026-11-20

## 2026-11-20 release-integrity refresh

- Audited the prepared 2026-11-19 archives and found four source files that changed after packaging. The older archives remain immutable for history but are explicitly marked stale for those files.
- Refreshed `SHA256SUMS.txt` to the current reviewed `deploy/` sources and added `STAGING_SHA256SUMS.txt` for the exact reporting-stage upload pair.
- Added an automated release checksum contract so later source changes cannot silently leave the source manifest stale.
- Validation passes 38/38 Node contract files, 43/43 recursive PHP syntax checks, `git diff --check`, and local secret scanning. Remote Rafter fast scanning remains quota-blocked.
- This improves deployment safety but does not replace the pending hosted upload or hosted 10/10 reporting-stage acceptance.

## 2026-11-20 evidence-stage monthly customer reporting

- Added backward-compatible `delivery_outcomes` to the authenticated monthly value report with stable `generated`, `approved`, `submitted`, `published`, and `measured` stages.
- `generated` counts only customer-owned AI jobs already committed as `completed` and labels the unit `completed_ai_jobs`. Approval, submission, publication, and outcome measurement remain zero/`unavailable` until authoritative customer/provider/analytics events exist.
- Existing job-status/tool/credit, monitoring, inquiry, cost, truncation, and unavailable-metric fields are preserved. The route, permissions, author scope, 24-month UTC window, and 500-job cap are unchanged.
- Added an exact-host, read-only staging helper for the two marker-owned non-admin fixtures. It verifies per-fixture completed-count consistency, exact stage keys/semantics, and existing-field compatibility without creating or changing records.
- Secure design and ASVS L2 web/API review are recorded. Focused contracts pass; the full local suite passes 37/37 Node contract files, 43/43 PHP syntax checks, and `git diff --check`. Local secret scanning is clean; remote Rafter remains quota-blocked at 16/16.
- Re-authenticated StartHost File Manager, uploaded the exact checksum-verified customer-status module and staging-only acceptance helper to the isolated staging MU directory, and observed both uploads complete.
- Hosted acceptance returned `complete=true` with all 10 checks true and no errors: dependencies/fixtures verified; report bounds; exact stage keys; generated/completed alignment and unit; unavailable/zero later stages; and preservation of existing fields.
- Post-deployment smoke kept staging HTTP 200 with `noindex`, protected customer routes HTTP 401 anonymously, and the production acceptance route HTTP 404. Production remains unchanged.

## 2026-11-20 hosted lifecycle truncation stress attempt and recovery

- Added a canonical-host, route-driven, persistent state machine intended to seed 5,000 private non-financial lifecycle-schema rows, verify the production aggregate's truncation flag, and delete only its exact rows in reverse batches.
- Local secure design and ASVS L2 web/API review cover fixed server scope, HMAC ownership, collision denial, route-only execution, resumability, bounded output, exact cleanup, and recovery after hard PHP timeout. The focused contract now passes 28 assertions.
- The shared host exceeded its safe request window before target. Creation was disabled rather than continuing load. Recovery queried only the dedicated `staging_truncation` object type, capped discovery at 5,001, verified exact fixture author/user/type/object/date/HMAC key and contiguous offsets, and found 1,560 rows—including rows written before a timed-out request could persist its state.
- All 1,560 synthetic rows were removed in guarded 20-row batches. Hosted final evidence reports `cleanup_complete=true`, `summary_restored=true`, and `state_removed=true`; the staging homepage remains HTTP 200 with `noindex`, and production has no route (HTTP 404).
- The final report intentionally remains `complete=false` with `host_execution_ceiling`; `target_created` and `truncation_flag_true` are false. No customer, payment, order, subscription, provider, message, production record, or pre-existing lifecycle row was created, changed, or deleted.
- The 5,000-row truncation proof now requires a higher-capacity isolated environment or WP-CLI/database-fixture tooling. It is not a production-readiness pass and remains unchecked.

## 2026-11-20 hosted lifecycle UTC-boundary acceptance

- Added a canonical-host, one-time staging harness that creates four private, non-financial lifecycle events for the existing marker-owned non-admin fixture at the exact prior-second, month-start, month-end, and next-second UTC timestamps.
- The harness reuses the production `bds_lifecycle_admin_summary()` instead of duplicating its query. It requires exactly the start/end events to increase the current-month count by two, while gross, refund, and net currency aggregates remain unchanged.
- Every GMT/local date is read back. All event keys are collision-checked before mutation, and cleanup requires the request-created id plus the exact fixture user, type, object identity, and HMAC-derived key. The original lifecycle summary and current-user context must be restored before completion.
- Secure design and ASVS L2 web/API review are recorded. The new contract passes 17 assertions; the full local suite passes 35/35 Node contract files, 41/41 PHP syntax checks, and `git diff --check`. Local secret scanning is clean; required remote `rafter run` remains quota-blocked at 16/16 and paid Plus was not used.
- Uploaded the reviewed helper to isolated staging. Hosted evidence returned HTTP 200, `complete=true`, all 15 runtime checks true, and no errors. The staging homepage remained HTTP 200 with `noindex`; production exposed no acceptance route (HTTP 404).
- The four synthetic events were deleted and the complete original monthly event/customer/financial summary was restored. No customer, order, subscription, payment, refund, gateway, email, provider call, or production record was created or changed.
- This closes inclusive current-month UTC boundary acceptance. The separate 5,000-event truncation stress gate, Stripe settlement/webhook evidence, and accounting reconciliation remain open.

## 2026-11-20 staging launch-catalog fixtures preparation

- Added an exact-host, fail-closed staging fixture for AI Studio Starter ($14.99/50 credits), AI Local SEO ($19.99/60), AI Lead Assistant ($19.99/80), and AI Social Growth ($29.99/150).
- The helper cross-checks every price, allowance, and `launch_candidate` state against the authoritative catalog and preflights all four slugs before the first write. Only exact marked simple-product fixtures can be reused; any unmarked collision aborts the run.
- Products are catalog-hidden, virtual, sold individually, monthly, no-expiry, zero-setup, zero-trial staging records. The helper creates no customer/order/subscription/payment/message/provider call and contains no deletion primitive.
- Secure design and ASVS L2 web/API review are recorded. The fixture contract passes 22 assertions; the full local suite passes 34/34 Node contract files, 40/40 PHP syntax checks, and `git diff --check`. Local secret scanning is clean; required remote `rafter run` remains quota-blocked at 16/16 and paid Plus was not used.
- Uploaded the reviewed fixture helper to isolated staging. Hosted evidence returned `complete=true` with all 4 product checks true and no errors. Each product page returns HTTP 200 with the expected price and `noindex`; production exposes no fixture route (HTTP 404), and public sales chat remains default-off (HTTP 503).
- The four launch-candidate slugs now resolve to deterministic hidden staging products for link/checkout integration testing. No gateway, order, subscription, customer, provider, message, production product, or existing agreement changed.

## 2026-11-20 sales-assistant grounding/cost acceptance preparation

- Added an exact-host, one-time staging harness around the existing public BrandDad salesperson. A visibly dummy execution key enables only the acceptance request, and every request to the fixed FormWise endpoint is intercepted locally; unexpected or out-of-context provider traffic fails closed.
- The harness verifies owned-origin enforcement, raw-size denial, authoritative checkout mapping, SEO/lead/social recommendation order, server-owned product links, hostile model HTML/link stripping, signed session validity/tamper/expiry/client binding, server-side continuation, three-call daily cost cap, ten-per-minute client cap, and exact state restoration.
- The runner creates no user, lead, post, AI job, order, subscription, payment, email, or scheduled task. The report is exact-host, request-input-free, and boolean-only; production remains default-off after the one-time report is stored.
- Secure design and the ASVS L2 web/API plus OWASP LLM manual review are recorded. The new static contract passes 39 assertions; the full local suite passes 33/33 Node contract files, 39/39 PHP syntax checks, and `git diff --check`. Local secret scanning is clean; required remote `rafter run` remains quota-blocked at 16/16 and paid Plus was not used.
- Uploaded helper 0.1.0 to isolated staging. Its first hosted pass returned `complete=true` with all 31 runtime checks true while specialized products were absent, proving unavailable-offer link denial plus all origin/body/output/session/cost/state controls.
- After creating the four hidden launch-product fixtures, upgraded the helper to 0.1.1 under a fresh report key and reran it. Hosted evidence again returned `complete=true`, 31/31 checks true, and no errors—now requiring authoritative staging product links for Local SEO, Lead Assistant, Social Growth, and the Business Essentials fallback while preserving all security and cost controls.
- Post-run smoke kept staging home/product/catalog HTTP 200 with `noindex`, production exposed no acceptance route (HTTP 404), and `/sales/chat` returned to its default-off HTTP 503 state. No real FormWise credit, customer, lead, order, payment, email, or production data was used.
- This closes both fail-closed missing-product behavior and successful authoritative-link behavior under abuse caps. Public enablement still requires a least-privilege execution key plus measured real FormWise cost and answer-quality acceptance; the harness deliberately does not claim those.

## 2026-11-20 hosted Woo refund-hook acceptance preparation

- Added a canonical-host, one-time staging harness for one synthetic $29.99 Woo order, a $5.00 partial refund, and a $24.99 remaining refund. Both refunds use WooCommerce's real `wc_create_refund()` and `woocommerce_order_refunded` path with `refund_payment=false` and `restock_items=false`; no gateway, card, transaction, provider, or production system is contacted.
- The harness calls only the lifecycle listener for its synthetic payment evidence, avoiding the broader sales-lead fan-out. It snapshots the existing `.invalid` fixture lead and requires the complete metadata snapshot to remain unchanged.
- The synthetic order is marker-owned before its completed transition. WordPress mail is suppressed only while the exact synthetic request is active. Hook replays must reuse the original private lifecycle rows, and aggregate evidence must change by exactly +2999 USD gross, +2999 refunds, and zero net.
- Cleanup hard-deletes only the request-created events/refunds/order after exact user, type, object, parent, and marker checks. The public evidence route is parameter-free, exact-host, GET-only, and boolean-only.
- Rafter secure design plus the ASVS L2 web/API manual review are recorded. Local secret scanning is clean. Required remote `rafter run` was attempted and is still quota-blocked at 16/16; paid Plus was not used.
- Local validation passes the new 41-assertion refund contract, all 32 Node contract files, 38 PHP syntax checks, and `git diff --check`.
- Uploaded the reviewed helper to isolated staging. Hosted evidence returned `complete=true` with all 30 runtime checks true and no error codes: real partial/full Woo refund hooks, exact 500/2499 minor-unit rows, duplicate hook idempotency, fully refunded order state, +2999 gross/+2999 refunds/zero net deltas, no gateway or email delivery, unchanged fixture lead, and exact event/refund/order cleanup.
- Post-run smoke kept the staging homepage and Business Essentials product at HTTP 200 with `noindex`; the sales catalog remained HTTP 200; account and finance routes denied anonymous access with HTTP 401; production exposed no acceptance route (HTTP 404).
- This closes the hosted Woo partial/full refund-hook gate. Stripe refund webhooks, bank settlement, live card refunds, UTC month boundaries, the 5,000-event truncation flag, and accounting reconciliation remain outside this synthetic test.

## 2026-11-19 synthetic subscription-lifecycle acceptance preparation

- Added an exact-host, one-time staging harness that creates only marker-owned synthetic Woo orders and one WPS subscription for the existing non-admin fixture, invokes the production trial/grant/revoke/reconcile functions, and deletes the exact fixtures afterward.
- The helper sorts before the production subscription module so an acceptance-only dummy provisioning configuration can exist for one request. Every FormWise end-user request is intercepted locally; any request outside the exact acceptance context fails closed, and no real provider or gateway can run.
- Acceptance covers authoritative seven-day trial metadata/history/25-credit access; zero-dollar trial grant denial; paid renewal matching and grant; replay idempotency; per-paid-order allowance cycles; failed-latest-order no-fallback; on-hold revocation; duplicate callback idempotency; same-order reactivation without credit reset; cancellation/history preservation; provider failure/retry; exact scheduled-job cleanup; lifecycle-event cleanup; exact user-state restoration; and no real gateway.
- The Rafter secure-design and L2 web/API review walks are recorded. Exact markers protect every deletion; the report is boolean-only and exact-host gated. Local secret scanning found no secrets. Required `rafter run` was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- Local validation passes 31/31 Node contracts, 3/3 PHP compatibility tests, 31/31 deployment PHP lints, and `git diff --check`. The new helper contract contains 44 security/behavior assertions.
- The first hosted run failed safely on four assertions while all isolation/cleanup checks passed. Three were harness timing/shape expectations; the fourth exposed a real reactivation defect: the legacy active-status hook used the original parent order instead of the latest authoritative paid renewal, allowing an incorrect allowance-cycle advance after renewal.
- Corrected the production subscription adapter to use `bds_ai_subscription_current_paid_order()` for legacy active-status reconciliation, bumped it to 0.5.1, expanded its contract, and prepared acceptance helper 0.1.1 under a fresh one-time report key. The reviewed release copy and ZIP match SHA-256 `5D8BBE37A65334CA0A50EEDA5AFD9E8589118F9FBE2A59AED612DE55BB20CAAA`.
- After confirmation, uploaded adapter 0.5.1 and helper 0.1.1 to isolated staging. Hosted evidence returned `complete=true` with all 36 checks true and no error codes, including trial/paid resolution, replay, two renewal cycles, failed-latest-order no-fallback, immediate on-hold/cancellation revocation, duplicate callback idempotency, same-order reactivation without credit reset, provider failure/retry, provider isolation, and exact cleanup/restoration.
- Post-run smoke kept the staging homepage and Business Essentials product at HTTP 200 with `noindex`, public sales catalog at HTTP 200, and nine private customer/admin routes plus trial at HTTP 401 anonymously. Production exposed no acceptance route (HTTP 404).
- This synthetic test does not replace Stripe test checkout/webhooks or a real FormWise mutation; no real gateway, provider, customer, message, or production mutation occurred.

## 2026-11-19 hosted website-monitoring acceptance

- Added a revalidated internal monitoring-plan candidate hook so isolated staging can exercise the real service without creating a fake Woo order or subscription. Hook output is still restricted to paid mode and the fixed monitoring-plan allowlist.
- Added an exact-host, one-time staging harness that enables monitoring for one request, suppresses the recurring scheduler, uses only marker-owned non-admin fixtures, restores all fixture metadata, and exposes boolean-only evidence.
- Acceptance covers anonymous denial; local/private/credential/port rejection; real public-DNS fetches to BrandDad staging; bounded measurements and hash-only retention; value-report deltas; active/stale locks; simulated network failure; no-follow redirect behavior; URL-change and plan-loss shutdown; manual rate limiting; and cross-account report isolation.
- Added 54 staging security/behavior assertions and expanded the main monitoring contract to 22 checks. The complete local suite passes 26/26 Node contracts, three PHP compatibility suites, 26 deployment PHP syntax checks, and `git diff --check`.
- Rafter secure-design ingestion/threat-model and API/web-app review walks were completed. Local secret scanning found no secrets; required remote fast SAST/SCA was attempted and remains quota-blocked at 16/16. Paid Plus was not used.
- After explicit action-time confirmation, uploaded the revised monitor and exact-host helper to isolated staging and triggered the one-time run.
- Hosted evidence returned `complete=true` with all 27 checks true and no error codes: fixture/anonymous controls; revalidated plan; SSRF rejection; public DNS; authorization; real owned-host fetch; bounded measurements/hash-only storage; exact monthly/value-report deltas; lock behavior; network failure; no-follow redirect; URL/plan shutdown; manual rate limiting; tenant isolation; metadata restoration; and no recurring schedule.
- Post-run smoke kept the homepage, product, and public catalog at HTTP 200; staging remained noindex; anonymous account/value/AI/onboarding/monitoring/finance routes returned HTTP 401. The production evidence route returned HTTP 404.
- No real customer entitlement, message, provider call, recurring job, payment, or production mutation occurred. Two bounded HTTPS checks reached only BrandDad's own staging host; the synthetic network failure made no outbound call.

## 2026-11-19 hosted provider-cost acceptance

- Corrected a verified-cost input defect where a negative PHP integer could be converted to a positive value by `absint`; signed integers, fractional values, invalid timestamps, and all other malformed accounting inputs now fail closed before normalization.
- Added an exact-host, one-time staging harness for provider-cost authorization, validation, HMAC idempotency, privacy, grouped aggregation, contribution math, incomplete-coverage labeling, and invalid-period handling.
- The harness uses one fixed 137-cent synthetic staging fixture and compares deltas only. Its public evidence is boolean-only and creates no user, administrator, order, subscription, payment, refund, provider call, message, entitlement, or production mutation.
- Added 42 staging security/behavior assertions and expanded the ledger contract to 19 checks. The complete local suite passes 25/25 Node contracts, three PHP compatibility suites, 25 PHP syntax checks, and `git diff --check`.
- Rafter secure-design and API review walks were completed. Local secret scanning found no secrets; remote fast SAST/SCA was attempted and remains quota-blocked at 16/16. Paid Plus was not used.
- After explicit action-time confirmation, uploaded the corrected ledger and exact-host acceptance helper to isolated staging and triggered the one-time run.
- Hosted evidence returned `complete=true` with all 24 checks true and no error codes: non-admin denial; invalid provider/product/basis/currency/amount/timestamp/idempotency rejection; private storage; duplicate reuse/single-row storage; hashed-reference privacy; untrusted metadata stripping; exact provider/product/cost/contribution deltas; explicit incomplete coverage; and invalid-period rejection.
- Post-run smoke kept the homepage and product at HTTP 200/noindex, the public sales catalog at HTTP 200, and anonymous account/value/AI/onboarding/finance routes at HTTP 401. The production evidence route returned HTTP 404.
- No real provider expense, payment, refund, customer charge, message, provider call, entitlement, or production mutation occurred.

## 2026-11-19 hosted customer API acceptance

- Added one exact-host harness covering onboarding validation/locking and customer value-report tenant isolation in a single batch.
- It tests localhost/private-IP rejection, 121-character field rejection, unsupported enum rejection, unknown-only updates, active-lock conflict, stale-lock recovery, one-version advancement, profile isolation, completed/failed job deltas, completed-only credit counting, cross-account job exclusion, safe status output, and invalid reporting periods.
- Added 30 security/behavior assertions. The complete local suite passes 24/24 Node contracts, three PHP compatibility suites, four PHP syntax checks, and `git diff --check`.
- Local Rafter secret scans found no secrets; manual review found no exposed ids/profile/totals, browser input, credential, user/admin/order/subscription/payment creation, fetch, message, provider run, or post/user deletion. Remote fast scan remains quota-blocked at 16/16; paid Plus was not used.
- After explicit action-time confirmation, uploaded the helper to isolated staging and triggered its one-time run.
- Hosted evidence returned `complete=true` with all 17 checks true and no error codes: URL/field/enum/unknown-key validation, active-lock denial, stale-lock recovery, one-version advancement, profile isolation, completed/failed job deltas, completed-only credits, cross-account exclusion, safe status output, and invalid period rejection.
- Post-run smoke kept the homepage at HTTP 200/noindex, catalog and product at HTTP 200, and anonymous AI/account/value/onboarding routes at HTTP 401. The production evidence route returned HTTP 404.
- No real customer, payment, message, provider request, entitlement, or production mutation occurred.

## 2026-11-19 hosted lifecycle acceptance

- Added an exact-host, one-time lifecycle harness that reuses the marked non-admin staging subscriber and creates no user, administrator, Woo order, subscription, payment, or refund object.
- It proves subscriber analytics denial, unknown-event rejection, duplicate-event id reuse with one stored row, privacy stripping of unallowlisted email/message keys, exact stored financial metadata, gross/refund/net aggregate deltas, and invalid-period rejection.
- Added 26 security/behavior assertions. The complete local suite passes 23/23 Node contracts, three PHP compatibility suites, five PHP syntax checks, and `git diff --check`.
- Local Rafter secret scans found no secrets; manual review found no exposed ids/amounts/metadata, input surface, network call, message, deletion, provider execution, or production mutation. Remote fast scan remains quota-blocked at 16/16; paid Plus was not used.
- After explicit action-time confirmation, uploaded the helper with the provider-failure harness and triggered its one-time run on isolated staging.
- Hosted evidence returned `complete=true` with all 12 checks true and no error codes: dependencies, marked non-admin owner, analytics denial, unknown-type rejection, duplicate reuse/single-row storage, metadata privacy, exact financial fields, gross/refund/net deltas, and invalid-period rejection.
- The production lifecycle-evidence route returned HTTP 404. No real order, payment, refund object, message, provider execution, customer record, or production mutation was created.

## 2026-11-19 hosted AI provider-failure acceptance

- Added an exact-host, one-time staging harness that reuses the marker-owned non-admin subscriber and refuses to run if a FormWise execution key is configured.
- It reserves five fixture credits through the production ledger, creates one private pending AI job, invokes the production worker, and requires `execution_not_configured` to fail closed before any provider request.
- Acceptance requires the reservation to be released, used credits to remain zero, and a second worker invocation to leave both values unchanged.
- Added 25 security/behavior assertions. The complete local suite passes 22/22 Node contracts, three PHP compatibility suites, three PHP syntax checks, and `git diff --check`.
- Local Rafter secret scans found no secrets; manual review found no input surface, credential exposure, user/admin/order/payment creation, direct network call, message, or deletion. Remote fast scan was attempted and remains quota-blocked at 16/16; paid Plus was not used.
- Uploaded in the same confirmed staging batch and triggered one time.
- Hosted evidence returned `complete=true` with all nine checks true and no errors: provider absent, credit reserved, fail-closed status/code, reservation released, used credits unchanged, and replay without double deduction.
- Post-run staging smoke kept the homepage at HTTP 200/noindex, catalog and product at HTTP 200, and anonymous AI/account/onboarding/admin routes at HTTP 401. The production evidence route returned HTTP 404.
- No FormWise request or credit spend, real payment, message, administrator, customer entitlement, or production mutation occurred.

## 2026-11-19 authenticated staging acceptance

- Added an exact-host, one-time staging harness for authenticated authorization and ownership checks without exposing a login credential or creating an administrator.
- The harness creates at most two marker-owned subscriber fixtures with random undisclosed passwords, verifies anonymous denial and authenticated permission acceptance, exercises onboarding optimistic concurrency and tenant isolation, and verifies AI-job owner access plus cross-account 404 behavior.
- Its evidence endpoint is available only on the configured staging home host and exposes allowlisted booleans and bounded error codes—never user ids, email, password, cookie, nonce, profile data, or job content.
- Added 23 static security/behavior assertions. The complete local suite passes 21/21 Node contracts, three PHP compatibility suites, two PHP syntax checks, and `git diff --check`.
- Local Rafter secret scans found no secrets. Manual review found no privilege elevation, caller-controlled input, credential disclosure, outbound request, payment, provider execution, message, or deletion. Required remote fast scan was attempted and remains quota-blocked at 16/16; paid Plus was not used.
- After explicit action-time confirmation, uploaded the reviewed helper to the isolated staging MU directory and triggered its one-time run.
- Hosted evidence returned `complete=true` with all nine checks true and no error codes: dependencies, non-admin roles, anonymous denial, authenticated access, onboarding owner update, stale-version denial, tenant isolation, owner job read, and cross-account 404.
- Post-run smoke tests kept the staging homepage, sales catalog, and Business Essentials product at HTTP 200; anonymous AI tools, account status, onboarding, and value-report routes remained HTTP 401.
- The production staging-evidence route returned HTTP 404, confirming the helper was not deployed there. No payment, message, FormWise execution, administrator, production mutation, or exposed credential was created.

## 2026-11-19 FormWise published-contract audit

- Re-read the connected BrandDad Social FormWise workspace without mutating it or spending execution credits.
- Reconfirmed the published workflow and App UUIDs referenced by the backend, plus the exact question keys, required states, and enum values for every customer-facing AI tool in the execution catalog.
- Confirmed the backend's fixed API-v1 Flow/App endpoints, keyed Flow payload, single-message App payload, and server-only bearer-auth shape remain aligned with FormWise's official External API contract.
- Added a regression contract covering six provider assets, 23 published enum values, all referenced question keys, endpoint construction, payload construction, and credential handling.
- No FormWise content, customer, entitlement, plan, portal, WordPress host, payment, or production record changed. A real provider execution remains pending an approved server key and credit-consuming staging acceptance.

## 2026-11-19 hosted staging Business Essentials fixture activation

- Uploaded the reviewed exact-host fixture helper to the isolated staging MU directory after explicit action-time confirmation.
- Triggered staging WordPress and verified the public catalog now returns the single canonical `ai-business-essentials` offer at $29.99 with a staging-only product URL and `for_sale=true`.
- Verified the direct product URL returns HTTP 200, remains `noindex`, contains the staging-only/not-production disclaimer, displays $29.99, uses `Subscribe monthly`, and is catalog-hidden/virtual/sold-individually in the Woo Store API.
- Added the product to a disposable anonymous staging cart and verified product id 15, quantity maximum 1, price 2999 minor units, no shipping requirement, and no available payment method. No checkout, order, account, subscription, payment intent, charge, email, or provider execution was created.
- Verified home/REST/catalog remain HTTP 200 and anonymous AI tools, account status, trial, onboarding, and admin analytics remain HTTP 401.
- The helper writes the exact one-month/seven-day metadata and reads every field back before its internal completion state. Independent phpMyAdmin row evidence was not captured because the hosting SSO did not open the selected database session; do not call the trial lifecycle independently database-proven yet.
- Production remains unchanged.

## 2026-11-19 staging Business Essentials fixture preparation

- Added a staging-only, exact-host-pinned Woo fixture helper for one hidden `ai-business-essentials` product at $29.99/month with a seven-day free trial, zero signup fee, and 75-credit customer metadata.
- The helper accepts no request input, creates no order/customer/subscription/payment, changes no gateway/provider setting, performs no network request, and refuses to modify any same-slug product that lacks its staging marker.
- Added post-save read-back verification for the product identity, status, hidden visibility, price, virtual/sold-individually settings, and every metadata field before the helper can mark itself complete.
- Added 19 static security/behavior assertions. The full local suite now passes 19/19 Node contract files plus all three PHP compatibility suites.
- PHP syntax, local Rafter secret scan, and `git diff --check` pass. Required remote fast Rafter scan was attempted and remains quota-blocked at 16/16; paid Plus was not used.
- The helper is prepared locally only. Uploading it will create a hidden product in the isolated staging database and therefore remains a separate action-time-confirmed step. Production is unchanged.

## 2026-11-19 Business Essentials identity correction

- Read-only HTTP evidence confirmed the real public Woo product is `/product/ai-business-essentials/` (HTTP 200); `/product/branddad-ai-business-essentials/` returns HTTP 404.
- Corrected the trial recognizer, consented free-Directory membership bridge, and lifecycle analytics metadata to use the same authoritative `ai-business-essentials` slug as the commercial catalog and Woo order resolver.
- Added a seven-assertion cross-module identity contract that also rejects the obsolete slug from deploy modules.
- Bumped only the affected internal module versions: trial 0.1.1, Directory membership bridge 0.1.1, and lifecycle events 0.4.1. No price, product, order, subscription, customer record, or checkout state changed.
- All 18 Node contract suites, three PHP compatibility suites, three affected PHP syntax checks, and `git diff --check` pass.
- File-specific Rafter secret scans pass. Required remote fast SAST/SCA was attempted and remains blocked at 16/16 quota; paid Plus was not used.
- The corrected modules were subsequently uploaded and smoke-tested on hosted staging. Production remains unchanged pending full acceptance.

## 2026-11-19 hosted staging commerce dependency batch

- Copied the already installed WooCommerce, WooCommerce Stripe Gateway, and Subscriptions for WooCommerce plugin code from the Social host into the isolated hosted staging install. No production plugin, setting, order, subscription, or customer record was changed.
- Added and ran a one-time staging-only MU activator that is exact-host pinned, has a literal three-plugin allowlist, accepts no request input, performs no network request, and becomes inert after recording completion.
- Verified the valid-TLS staging hostname returns HTTP 200 with `noindex`, the BrandDad namespace still exposes 21 routes, and anonymous customer/admin endpoints continue to fail closed with HTTP 401.
- Verified WooCommerce (`wc/v3` and Store API), WooCommerce Stripe REST routes, and Subscriptions for WooCommerce (`wsp-route/v1`) are active on staging.
- Verified the public BrandDad sales catalog returns HTTP 200 but remains safely non-purchasable: its single fallback offer has no purchase URL because the clean staging database has no approved Woo product fixture or product mapping yet.
- Added a 12-assertion static contract for the staging activator, then ran all 17 Node contract suites, all three PHP compatibility suites, the helper PHP syntax check, and `git diff --check`; all passed.
- Completed the file-specific local Rafter secret scan and manual OWASP review. The required remote fast scan was attempted but could not run because the account remains at its 16/16 quota; no paid Plus scan was used.
- Directorist, subscription/payment fixtures, FormWise server configuration, browser-session nonce acceptance, and real payment lifecycle acceptance remain outstanding. Production remains unchanged.

## 2026-11-19 deployment preparation batch

- Inspected the signed-in hosting File Manager read-only and verified separate WordPress roots for Social and Directory.
- Verified Social's live backend target at `public_html/branddad.social/wp-content/mu-plugins/` and Directory's root at `public_html/directory.branddad.social/`.
- Confirmed the host has an existing `_bds_deploy_backups/` area and started a fresh full files-and-databases control-panel backup before staging work.
- The owner reviewed the hosting panel's six detections and positively classified them as false positives. The flagged files were preserved; nothing was deleted or quarantined.
- Created `staging.branddad.social` with an isolated document root and a separate one-click WordPress database.
- Uploaded the checksum-reviewed Social archive to staging and placed all 16 MU modules in the staging `wp-content/mu-plugins/` directory. No production file was overwritten.
- Completed the isolated WordPress installation with a staging-only administrator and search-engine indexing discouraged.
- Verified the reviewed Social package activates cleanly on hosted WordPress: the `branddad/v1` namespace exposes 21 expected routes over the valid StartHost staging hostname.
- Verified anonymous access fails closed with HTTP 401 for AI tools, customer status, value reports, onboarding, monitoring, inquiry inbox, analytics, and finance endpoints. The public sales catalog returns HTTP 200.
- StartHost cannot issue its free wildcard certificate because `branddad.social` uses external nameservers. The host temporary staging URL has valid TLS; the custom staging hostname needs a certificate from the authoritative DNS/CDN provider before browser acceptance.
- Added `BRANDDAD_DEPLOYMENT_MANIFEST.md` with per-host module inventories, fail-closed switches, server-only configuration, staged deployment steps, rollback, and acceptance evidence.
- No production file, billing state, provider switch, message, or customer record was changed during this staging batch.

## 2026-11-19 provider-cost and preservation batch

- Audited the existing Partnero referral bridge and its prior deployment/verification evidence; preserved it instead of building a duplicate cookie/referral layer.
- Added `bds-provider-cost-ledger-mu.php`, a private append-only verified provider-cost ledger with HMAC idempotency and strict provider/basis/catalog/currency/amount/date validation.
- Added an admin-only contribution API that combines confirmed Woo lifecycle revenue/refunds with verified stored variable costs while explicitly marking incomplete cost coverage.
- Added a static contract suite covering private storage, authorization, no public write route, verified-only bases, catalog enforcement, bounds, idempotency, reporting caps, and non-misleading wording.
- No provider invoice, expense, profit, commission, or margin was invented.
- Completed a read-only live smoke: both homepages and REST indexes returned HTTP 200 with successful TLS verification; the Social Services page also returned 200.
- Confirmed the new `/branddad/v1` backend routes are not yet present in either live REST index. The package is reviewed and prepared, not deployed.
- Inspected the StartHost service overview read-only. It has no visible managed staging/clone feature, so validation cannot safely use the live document roots as a substitute for staging.
Branch: `codex/final-backend-20261119`
Worktree: `C:\Users\iamtd\Documents\Codex\branddad-codex-final-20261119`

## Completed and retained

- Preserved Cursor branch `cursor/branddad-revenue-20261118` and both dirty existing worktrees; no production or Cursor-owned file was overwritten.
- Reused the verified AI commercial catalog, Woo product mapping, upgraded Subscriptions for WooCommerce lifecycle bridge 0.5.0, and Directory free-AI ledger 0.1.0.
- Verified the FormWise organization, owner role, published app/agent/flow UUIDs, tier plans, and restricted customer portal.
- Added `bds-ai-execution-mu.php`: authenticated REST jobs, explicit workflow schemas, subscription-cycle and Directory-free metering, idempotency, verified async queueing, fixed provider origin, bounded results, and failure-safe allowance release.
- Added `bds-account-link-mu.php`: five-minute single-use HMAC account handoff between the two owned WordPress hosts, POST-only token transfer, replay protection, fail-closed source email verification, account reuse, and audit mapping.
- Added `bds-sales-assistant-api-mu.php`: default-off public BrandDad salesperson proxy, verified agent UUID, deterministic catalog offers, model-link stripping, owned-origin checks, client-bound conversation state, and serialized per-client/global cost limits.
- Completed the Rafter/OWASP manual security walk and local secret scan; the remote fast scan is recorded as quota-blocked.
- Added a default-off consent follow-up worker that reuses Cursor's existing consent list/copy, rechecks fresh consent, stops converted contacts, avoids uncertain duplicate delivery, and provides durable opt-out.
- Added a customer-owned onboarding REST API with strict profile fields, optimistic concurrency, paid/Directory-free access state, completed-job detection, and safe first-tool guidance.
- Added the authoritative seven-day trial backend: exact WP Swings metadata, 25-credit bounded ledger, one-trial history, paid/trial isolation, server-side trial tool allowlist, and prevention of full paid provisioning from a zero-dollar authorization order.
- Added a signed, replay-safe, consent-only Social-to-Directory free-membership bridge. It reuses/creates a private subscriber and never creates a public listing.
- Added unified customer status and evidence-based monthly value-report APIs. Reports count only actual customer-owned job records and explicitly mark unconnected outcomes/provider cost unavailable.
- Added a fail-closed Featured billing adapter around Cursor's rotation records: strict SKU allowlist, claimed-owner cart binding, exact subscription/payment linkage, real-geography requirement, capacity, renewal extension, failed-payment pause, paid-through cancellation, expiry, and refund handling.
- Added bounded daily subscription reconciliation across HPOS/legacy storage, latest-renewal selection, stale-lock recovery, and five-minute batch continuation. Reactivation no longer resets monthly credits unless a genuinely new paid order exists.
- Added an idempotent, privacy-limited lifecycle event ledger and admin analytics API. It records registration, trial activation, successfully provisioned payments, refunds, committed AI work, access revocation, Directory linking, and onboarding completion at the backend success points; reports separate gross, refunds, and net confirmed transaction events without calling them MRR or settled cash.
- Added a default-off BrandDad acquisition lead pipeline: owned-origin public capture, explicit privacy/email-consent separation, HMAC email deduplication, bounded rates and fields, deterministic catalog recommendations, verified registration/trial/checkout/payment/onboarding/revocation stages, admin pagination, opt-out propagation, and bounded anonymization of stale unconverted PII. It sends no message itself.
- Added a default-off daily website availability monitor for eligible paid plans: customer authorization attestation, onboarding-URL-only configuration, public-DNS/SSRF validation on every check, safe bounded no-redirect fetches, manual rate limits, per-customer/batch locks, Action Scheduler/cron queueing, plan-loss and URL-change shutdown, 24-month actual counters, lifecycle evidence, and customer value-report integration. It stores no response body and sends no alert.
- Added a default-off multi-tenant customer inquiry backend for eligible paid plans: one owner form reference, owned-origin hosted submission, honeypot and HMAC rate limits, mandatory privacy/contact consent, bounded fields, private owner-authored records, cross-account 404s, optimistic status workflow, real monthly lead counts, lifecycle evidence without PII, and bounded closed/lost anonymization. It sends no message.
- Added security design, contracts, handoff, audit, entitlement, product, test, blocker, and production-checklist documentation.

## Implemented but not yet deployed

- AI execution REST API and metering.
- Seven-day trial lifecycle and customer status/reporting APIs.
- Consented Social-to-Directory free membership provisioning bridge.
- Directory Featured billing lifecycle adapter (not activatable until SKUs/geography integration exist).
- Lifecycle analytics ledger and admin-only monthly aggregate API.
- Sales lead capture/stage API and consent-safe retention pipeline (public capture remains disabled until frontend/privacy/staging acceptance).
- Website monitoring API/worker and evidence reporting (execution remains disabled until real-host DNS/queue/fetch staging acceptance).
- Customer inquiry form/list/status APIs and actual lead reporting (backend runtime acceptance passed 34/34; capture remains disabled until Cursor's real form/inbox, browser nonce, consent copy, and any approved notification delivery pass).
- Cross-site account linking.
- Public sales-assistant proxy.
- Subscription bridge and free-AI ledger copied into the clean integration branch.

## Next unblocked backend work

1. Add WordPress staging fixtures for request-idempotent replay, trial conversion, Directory bridge replay, browser-session nonce/onboarding concurrency, real Woo refund lifecycle, unsubscribe, and HPOS subscription discovery.
2. Connect Cursor's lead form to the new capture contract and exercise privacy acknowledgment, optional consent, deduplication, stage progression, unsubscribe, and retention in staging; enable capture only after acceptance.
3. Exercise one authorized public website through configure, async/manual check, DNS/redirect/error, plan-loss, URL-change, daily batch, and value-report paths on WordPress staging; then enable monitoring server-side.
4. Add trial reminder and monitor-alert delivery only after a verified communications provider and sender are available.
5. Connect Cursor's real hosted inquiry form and customer inbox to the accepted backend; validate browser REST nonce, final consent copy, and any approved notification delivery before enabling.
6. Add signed Stripe/Woo webhook reconciliation only if the installed gateway does not already provide authoritative Woo transitions.
7. Create/approve the four Featured Woo SKUs, connect Cursor listing selection/geography, then run the adapter's staging lifecycle test.
8. Extend customer value reports when real publishing adapters provide verified customer outcomes.

## Production status

Not deployed. Follow-up delivery is compiled default-off. No purchase, subscription change, provider run, email, WhatsApp message, social post, listing publication, or customer billing modification was performed by this branch.

## 2026-11-19 hosted account-link cryptographic acceptance

- Added a normalized, fixed-owned-host-revalidated current-host candidate seam so the Social-to-Directory protocol can be cryptographically exercised on one isolated staging host without broadening production hosts.
- Added an exact-host, one-time helper using a deterministic acceptance-only secret for one request. It validates email-verification scoping, token shape/claims/lifetime, audience switching, tamper/expiry/future/purpose/email-hash/issuer rejection, malformed/oversized rejection, replay claims, existing-account resolution, conflicting sessions, safe start forms, and privacy-bounded audit rows.
- The helper never invokes the consume handler, creates an account, sets an auth cookie, mutates user metadata, redirects, contacts the Directory, sends a message, or processes a payment.
- Replay and audit acceptance use exact option keys and restore/delete only their own values in `finally`; the test secret is unavailable after the report exists and is never exposed.
- Local validation passed: 30/30 Node contracts, 3/3 PHP compatibility tests, 30/30 deployment PHP syntax checks, and `git diff --check`.
- `rafter secrets .` passed with no detected secrets. Required `rafter run` fast was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- After explicit action-time confirmation, uploaded the revised account-link module and exact-host helper to isolated staging and triggered the one-time report.
- Hosted evidence returned `complete=true` with all 30 checks true and no error codes: dependencies/fixtures; acceptance-secret and email-verification scoping; fixed-host revalidation; signed bounded claims and five-minute expiry; audience/tamper/time/purpose/email/issuer rejection; malformed/oversized rejection; atomic replay; existing-account resolution; conflicting-session detection; form safety; audit privacy; exact replay/audit/context restoration; and no cross-host request.
- Post-run smoke kept the staging homepage and Business Essentials product at HTTP 200 with `noindex`, the public sales catalog at HTTP 200, and nine private customer/admin routes at HTTP 401 anonymously. The production acceptance route returned HTTP 404.
- No account was created, no cookie/user metadata/redirect/network request/message/provider call/order/subscription/payment occurred, and production remained unchanged. Real two-host POST/cookie/session acceptance remains blocked by isolated Directory staging availability and a shared production-like secret.

## 2026-11-19 follow-up consent acceptance preparation

- Added an exact-host, one-time staging helper for the existing default-off follow-up worker. It validates fresh/stale consent rules, source normalization, opaque token/hash handling, token lookup, safe unsubscribe URLs, compare-and-set updates, persisted opt-out, reactivation blocking, disabled-worker no-op behavior, and rate bounds.
- The helper uses one exact fake `.invalid` consent row and one documentation-range IP rate key, refuses a fixture-key collision, and restores or removes only those exact test values in `finally`.
- It never enables the worker, defines a sender, calls `wp_mail()`, invokes the public unsubscribe handler, creates a user/lead/order/payment, or calls an external provider.
- Local validation passed: 29/29 Node contracts, 3/3 PHP compatibility tests, 29/29 deployment PHP syntax checks, and `git diff --check`.
- `rafter secrets .` passed with no detected secrets. Required `rafter run` fast was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- Uploaded the exact-host no-email helper after explicit confirmation. Hosted runtime acceptance passed 26/26 checks with no error codes; the exact fake consent row, isolated rate counter, and original context were restored.
- Hosted smoke confirmed staging home/product HTTP 200 with `noindex`, protected routes HTTP 401 anonymously, lead capture remained HTTP 503 `lead_capture_disabled`, and production exposed no acceptance route. No email was sent and production remains unchanged.

## 2026-11-19 sales-lead acceptance preparation

- Added a restrictive retention candidate-id seam to the production lead pipeline. Values are normalized to at most 100 positive integers and can only add a `post__in` restriction; normal private type/status, age, and stage predicates remain authoritative.
- Added an exact-host, one-time staging acceptance helper covering owned-origin/body/rate controls, required privacy, account-email matching, verified/unverified consent, HMAC-email deduplication, private storage, safe response, deterministic recommendations, subscriber admin denial, lifecycle progression, unsubscribe suppression, retention, cleanup, and restoration.
- The helper refuses any pre-existing fixture lead collision, uses only fixed `.invalid` test contacts and a documentation-range IP, and narrows retention to its two exact marked fixture ids.
- It sends no email or message, invokes no provider/network call, creates no user/order/subscription/payment, and exposes only allowlisted boolean evidence.
- Local validation passed: 28/28 Node contracts, 3/3 PHP compatibility tests, 28/28 deployment PHP syntax checks, and `git diff --check`.
- `rafter secrets .` passed with no detected secrets. Required `rafter run` fast was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- The first hosted helper run failed closed when it detected the pre-existing fixture-user lead created by the registration hook; it made no mutation. The corrected helper uses a second anonymous marked fake lead and leaves all pre-existing user leads untouched.
- Hosted runtime acceptance then passed 34/34 checks with no error codes. Cleanup, exact consent-row restoration, isolated rate restoration, and context restoration all passed.
- Hosted smoke confirmed staging home/product HTTP 200 with `noindex`, protected routes HTTP 401 anonymously, public capture returned to HTTP 503 `lead_capture_disabled`, and the production acceptance route remained HTTP 404. Production remains unchanged.

## 2026-11-19 customer-inquiry acceptance preparation

- Hardened the inquiry entitlement seam with an internal plan-candidate filter whose result is revalidated as an array, a paid access mode, and one of the fixed inquiry plan slugs.
- Added an exact-host, one-time isolated-staging acceptance helper for consent, origin, body/rate limits, opaque form references, replay, private ownership, cross-account denial, optimistic versions, monthly evidence, and retention.
- The helper uses only fixed fake `.invalid` contact data and a documentation-range IP. It sends no message, invokes no provider, creates no account/order/subscription/payment, and exposes only allowlisted boolean evidence.
- Cleanup hard-deletes only private inquiry posts created by this helper that are marker-owned by fixture B; form metadata, rate counters, server values, and current-user context are restored in `finally`.
- Local validation passed: 27/27 Node contracts, 3/3 PHP compatibility tests, 27/27 deploy PHP syntax checks, and `git diff --check`.
- `rafter secrets .` passed with no detected secrets. Required `rafter run` fast was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- Uploaded the reviewed inquiry module and exact-host helper to isolated hosted staging after explicit confirmation. The one-time runtime report completed with 34/34 checks true and no error codes.
- Hosted smoke passed on the homepage and Business Essentials product with `noindex`, public catalog HTTP 200, nine protected customer/admin routes HTTP 401 anonymously, and the production acceptance route HTTP 404. Production remains unchanged.

## 2026-11-20 live launch-surface verification

- Verified the live Social homepage, Services page, website-audit page, and the AI Business Essentials, Lead Assistant, Social Growth, and Local SEO product pages return HTTP 200. The four product pages expose real add-to-cart controls and product images.
- Verified the live AI Business Essentials product is product `53760`, displays `$29.99/month`, and discloses a seven-day trial and automatic monthly renewal unless canceled.
- Verified the signed-in live checkout UI carries AI Business Essentials, shows `$0.00` due today, a `$29.99` renewal subtotal, calculated tax/total for the active session, Stripe card entry, terms acceptance, and the Place order control. No payment was submitted; provisioning, renewal, refund, and cancellation remain unverified.
- Verified the live Directory homepage at a 390-pixel mobile viewport uses the compact mobile header, location-first copy, Playa del Carmen-first nearby listings, one floating Directory Concierge, a contained popup dialog, a light logo on the dark launcher, and the organized footer. The local section did not mix Dubai or Chicago listings.
- Diagnosed the category picker pagination assumption: Directorist caps the REST collection at 12 records even when `per_page=100` is requested. Built reviewed Directory Experience `0.68.14`, which follows the server-reported total through the existing five-page cap instead of stopping after a short first page. All Directory PHP/JavaScript/contract checks pass; package SHA-256 is `3FBA1E17727221EAA6D7FAFF17CDDBC6353852DD51CF5CBFEB3CA29461223158`.
- Uploaded the reviewed `0.68.14` package to the signed-in WordPress plugin installer. After the required action-time confirmation, WordPress replaced live `0.68.13` and reported `Plugin updated successfully`.
- The confirmed exact-homepage cache repair completed despite the admin page timing out while returning. Independent clean-URL verification now shows HTTP 200, `x-bd-eco-cache: html-early`, one `index, follow` robots tag, one canonical to `https://directory.branddad.social/`, and no `noindex`. The temporary admin-only repair MU plugin remains installed pending separate deletion confirmation.
- Completed the 390-pixel restaurant funnel on the live site: Mexico -> Quintana Roo -> Playa del Carmen -> All Restaurants opened `/search-result/?in_cat=191&in_loc=13445&directory_type=food-beverage`, displayed 36 matches, retained Food & Beverage / Playa del Carmen / Restaurants as active filters, and rendered Playa del Carmen businesses rather than a mixed worldwide list.
- Audited all 45 public category destinations linked from the live Directory homepage; every destination returned HTTP 200.
- Rechecked the priority Directory routes for listings, events, jobs, classifieds, categories, locations, add-listing, claiming, plans, sign-in, and dashboard. Public routes returned HTTP 200 and the protected submission/dashboard routes redirected to registration or sign-in as intended.
- Exercised the Directory homepage/results at 320, 390, 768, 1024, and 1440 pixels. No tested width had horizontal overflow; mobile navigation was used through 768 pixels and desktop navigation at 1024 pixels and above.
- Exercised the Social homepage at 320, 390, 768, 1024, and 1440 pixels. No tested width had horizontal overflow or broken images; navigation switched from the compact menu to the desktop menu at the large breakpoint; and exactly one fixed `Ask BrandDad AI` launcher was visible.
- Verified the live Social homepage's priority sales links for the seven-day Business Essentials trial, Lead Assistant, Social Growth, Local SEO, the free website check, and Services. A complete same-ecosystem homepage link audit discovered 93 distinct Social/Directory destinations and all 93 returned HTTP 200.
- Repeated Directory location-page timing after one cold outlier: warmed `all-locations` requests and three parallel clean requests completed with roughly 1.3-2.0 second TTFB. The one 18-second cold response was not reproducible and is recorded as an observation rather than a launch blocker.
- Re-ran the complete current local release suite while the live update remained approval-gated: 38/38 Node contract files passed, 3/3 PHP compatibility tests passed, and all 43 deployment PHP files passed syntax validation. The reviewed Directory `0.68.14` archive still matches SHA-256 `3FBA1E17727221EAA6D7FAFF17CDDBC6353852DD51CF5CBFEB3CA29461223158` and the clean backend worktree remains unchanged.
- Independently verified the public release after installation. The live Directory stylesheet and category script advertise `0.68.14`; the downloaded live category script contains `pageNumber < totalPages` and no `items.length === 100`. At a true 390-by-844 viewport, the Playa del Carmen restaurant results rendered 36 matching businesses, visible cards carried Playa del Carmen locations, one Directory Concierge launcher was present, and the page had no horizontal overflow.
- Re-audited the anonymous free-listing journey. `/add-listing/` redirects guests to registration, the registration page states that one basic listing has no fee, and the public plan page sends the Basic/Free listing action to `/add-listing/` while labeling the older $6.99 Activation plan as optional and not required. The deployed free-listing policy source grants one listing only to an authenticated free-tier owner and holds a second publish for review; its free-listing, Directory-contact, and sales-help PHP tests all pass. A fresh non-admin account runtime journey remains intentionally unexecuted because account creation is an external side effect requiring action-time confirmation.

## 2026-11-20 final five-batch closeout

- Batch 1 — production smoke: rechecked the priority Social and Directory launch surfaces. The Social homepage, Services, website check, Business Essentials, Directory homepage, listings, categories, registration, and the clean Playa del Carmen search result returned HTTP 200. The clean location index returned HTTP 200; one cold cache-bypass request took about 18 seconds, while later clean/warm requests ranged from about 1.3 to 6.6 seconds. This is a cache/bootstrap performance observation, not a reproduced outage.
- Batch 2 — free-listing readiness: confirmed that guests are routed to registration, the public registration and plan copy describe the first basic listing as free, and the deployed policy permits one authenticated free-tier listing while holding a second publish for review. Focused free-listing, Directory-contact, and sales-help tests pass. A fresh non-admin registration, verification, listing submission, moderation, and edit journey still requires action-time approval for account creation.
- Batch 3 — payment readiness: audited the signed-in live WooCommerce Stripe configuration without changing settings or submitting a transaction. Stripe is enabled in live mode; payment, payout, and webhook status are enabled; card, Klarna, Afterpay, Cash App Pay, Apple Pay/Google Pay, Link, and Amazon Pay are selected; saved-card support is enabled; capture-later and debug logging are disabled. The connected account reports no live webhook received since monitoring began on 2026-11-08, and Stripe's Optimized Checkout Suite reports unavailable. A safe test-mode account is not connected, so purchase/provisioning/renewal/refund/cancellation acceptance remains open.
- Batch 4 — FormWise readiness: refreshed the connected organization inventory without spending execution credits. The owner organization has published Directory Concierge, Directory Assistant, and Sales Assistant agents; the BrandDad AI Studio app; 13 published business/Directory flows; two restricted login-required portals; and the approved Starter, Growth, Pro, Business Essentials, Lead Assistant, Local SEO, Social Growth, and private QA tier plans. Video Director remains staged/unpublished. Tier-plan API access is disabled, and no paid agent/flow run was performed, so public inventory is verified but real external execution, cost, and semantic quality remain gated.
- Batch 5 — cleanup and handoff: preserved the temporary exact-homepage repair MU file until the separately required deletion confirmation. The repaired public homepage remains independently verified as HTTP 200 with one index/follow robots policy, one canonical, and no stale noindex. No settings, payments, subscriptions, customer records, FormWise credits, messages, or external posts were changed during these five batches.

## 2026-11-20 live scheduler investigation

- Continued the live launch audit after the five-batch closeout and found a critical Social reliability blocker: WooCommerce Action Scheduler reports 4,990 actions, including 4,166 failed, 53 pending, and 52 past due. WordPress Site Health identifies `action_scheduler_run_queue` as a failed scheduled event.
- Confirmed that the deployed Social MU connection guards intentionally disable visitor-triggered WP-Cron and the Action Scheduler async runner. Cron/CLI processing is bounded to five actions, one concurrent batch, and a 20-second time limit, so a real host scheduler is required.
- Audited the authenticated StartHost Scheduled Tasks page read-only. It reports that the account currently has no scheduled tasks. The active site PHP is 8.4, the panel lists `/usr/bin/php84`, and the service root is `/home/sites/41a/c/c0a8249376/`.
- Derived and documented the exact recovery command `/usr/bin/php84 -f /home/sites/41a/c/c0a8249376/public_html/branddad.social/wp-cron.php` at a five-minute cadence. No task was saved and the Test Command button was not used.
- The overdue queue includes WP Swings subscription hooks. Read-only subscription review found one active owner subscription with its next payment shown as 2026-12-13. Creating the recurring production task can therefore cause financial lifecycle code to run and requires an exact action-time confirmation.
- Added `BRANDDAD_SCHEDULER_RECOVERY_DESIGN.md` with the verified state, threat model, bounded recovery sequence, acceptance criteria, and rollback. The site is not launch-ready until this queue is safely restored and observed.
- Added `BRANDDAD_EXTERNAL_CRON_FALLBACK.md` after reviewing current official 20i, Cloudflare, and GitHub documentation. The host task remains preferred; if StartHost cannot supply a runner, the documented order is one Cloudflare Cron Trigger, then one temporary GitHub Actions schedule. The design forbids simultaneous runners, credentials in URLs/logs, third-party Actions/packages, automatic retries, or activation without a current backup, queue/subscription baseline, guard recheck, stop operator, and exact action-time approval. No external runner or live invocation was created.
- Started a StartHost backup covering both files and databases. The panel reported `Successfully started backup` and showed the job waiting in the backup queue. The hosting session expired before a completed backup row could be verified, so scheduler activation remains gated on reauthentication and visible backup completion.
- Reconnected to the live backup job and verified that it remains present and active as `Backup job started`; completion is still pending. A focused Action Scheduler search found exactly two pending WP Swings recurring checks, both every 30 minutes and overdue since 2026-11-13, with no displayed action arguments. The rest of the pending queue is dominated by product-attribute lookup updates plus one future GeoIP update. This narrows the first-run risk but does not remove the confirmation requirement.
- Prepared the authenticated StartHost Scheduled Task form with the reviewed PHP 8.4 command and `Every 5 minutes`; hour, day of month, month, and weekday remain `(Any)`, and email remains blank. The form is deliberately unsaved, Test Command was not used, and the hosting panel still reports no scheduled tasks while the backup runs.
- StartHost completed the combined files-and-databases backup created at 2026-11-20 15:22:19 UTC and now exposes a Download action. The prepared scheduler form was re-read immediately afterward: exact command, `*/5` minutes, all other cadence fields `(Any)`, blank email, and no existing saved task. The final Save Task action remains confirmation-gated because it can execute overdue subscription checks.
- The owner supplied the exact action-time confirmation. The reviewed task was submitted once, but StartHost rejected it with `Cron failed to add` and created no task. No cron batch, payment, subscription, order, customer, or queue mutation occurred.
- Root cause is now authoritative: the StartHost package overview still says `Malware Detected On This Package`, and 20i/StackCP disables scheduled tasks while that package flag is active. The report contains five previously owner-reviewed false positives in Directory generated HTML cache and one already quarantined historical one-shot file outside the web root.
- Requested a fresh StartHost malware scan on 2026-11-20. Scan `24662257` completed at 17:01:32 UTC with return code 1 and reproduced the false-positive package flag: six current generated Directory cache HTML objects under `wp-content/cache/bd-eco-html/` plus the historical quarantined one-shot outside the web root. While it ran, the Social homepage, Services, AI Business Essentials, checkout, and Directory homepage each returned HTTP 200. Action Scheduler remained at the pre-save baseline of 4,990 total / 771 complete / 4,166 failed / 53 pending / 52 past due.
- Traced the recurring detections to the Directory guest HTML cache writers. The flagged public cache objects are ordinary claim, plan, product, gift-card, and listing pages; direct read-only inspection found no `eval(`, `document.write`, `atob(`, `base64_decode`, `fromCharCode`, or `unescape(` execution patterns. They share normal WordPress asset paths, inline browser code, and legitimate `wa.me` contact links that match the unofficial heuristic token family. A durable host-side false-positive clearance/allowlist is preferred over repeatedly deleting regenerated cache files.
- Prepared `BRANDDAD_STARTHOST_FALSE_POSITIVE_REQUEST.md` with the exact service, scan ID, paths, signature, technical evidence, non-destructive request, and authoritative false-positive references. The authenticated support form is open but remains unsubmitted pending the required action-time confirmation.
- Preselected `Web Hosting` and the exact `branddad.social` service in the authenticated StartHost ticket form. Subject and description remain empty and the Create Support Ticket action remains untouched pending action-time confirmation.
- After explicit action-time confirmation, submitted StartHost Web Hosting ticket `898665003`, subject `False-positive malware flag is blocking scheduled tasks`, against the exact `branddad.social` package. The ticket is `Open`, was created at 2026-11-20 17:48:42, contains scan `24662257`, all affected paths/signatures, the benign-content inspection, the recurring-cache explanation, and an explicit instruction not to delete production/customer data. The request asks StartHost to clear or safely allowlist only the confirmed false-positive surface while keeping malware protection active elsewhere.
- Rechecked the StartHost package after the ticket was filed. The package overview no longer displayed `Malware Detected On This Package`, so the already approved scheduler form was rebuilt and independently re-read with the exact PHP 8.4 command, `*/5` minutes, all other cadence fields `(Any)`, blank email, and zero saved tasks. StartHost again returned `Cron failed to add`; the panel still showed no scheduled task, and no cron batch ran. The control-panel session expired during the subsequent read-only support check. The scheduler remains an external hosting blocker until StartHost confirms the residual restriction and the exact task can be saved and observed.
- Completed an independent post-ticket verification pass while the hosting session is unavailable. The complete local backend suite remains green at 38/38 Node contracts, 43/43 deployment PHP lints, and 3/3 PHP compatibility tests. Twelve public sales and Directory priority routes returned HTTP 200, including the Social catalog/product surfaces and Directory homepage/categories/listings/restaurant surface. The empty checkout correctly redirected to the cart. No external side effect occurred, and this evidence does not narrow the outstanding scheduler/payment/provider acceptance gates.
- Built fresh production-only backend release archives from the current reviewed `deploy/` sources, replacing the stale 2026-11-19 package references. The Social archive contains 16 production MU plugins and the Directory archive contains 7; both include the deployment manifest and package-local SHA-256 manifests. Expanded-copy verification passed every checksum and PHP lint, and found no filename containing `staging`, `acceptance`, or `repair`. The archives are local deliverables under `release/2026-11-20/`; no production file was uploaded or changed.
- Reconciled all ten catalog-permitted public products against the live WooCommerce pages: seven `launch_candidate` offers and three preserved `live_legacy` offers. Every canonical product URL returned HTTP 200, displayed its authoritative catalog price, rendered a product image, and exposed the primary add-to-cart/subscribe control. AI Business Essentials also displayed its seven-day trial disclosure. No staged or quote-only offer was promoted into this saleable set.
- Audited all 21 catalog entries marked `staged` or `quote_only` against their canonical product slugs. Every requested product URL returned HTTP 404, and none rendered the requested product title or requested Product schema. Generic related-product controls on the site-wide 404 template were not misclassified as the requested offer. This confirms unfinished services are not directly purchasable through their reserved catalog slugs.
- Exercised the anonymous cart-to-checkout handoff for all seven launch-candidate products in isolated cookie sessions. Each exact product id reached the canonical checkout with HTTP 200, the correct product name and price, and a Place order control; Business Essentials also retained its seven-day trial disclosure. No account, customer, order, payment method, or transaction was created, and Place order was never submitted.
- Completed a public white-label leakage pass across the Social homepage/Services and the Directory homepage/categories/listings/restaurant surfaces. None exposed `FormWise` as visible text or referenced `builder.formwise.ai`; all six assistant surfaces used the owned `ai.branddad.social` host. Provider-derived CSS class names remain implementation-only attributes and are not customer-visible branding.
- Completed the isolated hosted-staging Directory Featured billing acceptance without changing production. The first `v1` request failed closed before fixture creation because the production adapter was absent; cleanup/restoration checks passed. After the required secure-design and structured code-review gates, the exact reviewed adapter and `v2` helper were uploaded. The corrected report passed all 28/28 checks: four exact price/level mappings, wrong price/interval rejection, ownership/geography, no-gateway parent order, activation, replay, renewal, failed-renewal pause, paid-through cancellation, expiry, refund revocation, capacity denial, and exact cleanup/restoration. Staging home and Business Essentials remained HTTP 200, and the production acceptance route remained HTTP 404. Remote Rafter fast scan is still quota-blocked.
- Read the new StartHost support reply without sending a response. Support will not remove the false-positive flag and recommends ignoring it, but the host still rejects scheduled-task creation, so the scheduler blocker remains. Read-only Stripe inspection found the account connection is specifically `Incomplete` because the plugin cannot verify the origin of stored account keys; webhooks are configured to the correct site endpoint, sync is disabled, and an authorized reconnect is required. The endpoint returned HTTP 204 to an unsigned empty diagnostic POST; no order, payment, subscription, webhook event, or setting changed.
- Completed the current Partnero provider-capability audit from official documentation. The API supports program-scoped server Bearer keys; unique transaction creation; permanent commission-reversing deletion; reversible archive/restore; and HMAC-SHA256 signed, retried outgoing webhooks. Added a fail-closed BrandDad policy: emit only authoritative paid eligible Directory orders/renewals, never Social products under the current scope, keep paid history on ordinary cancellation, reverse only exact full refunds/chargebacks, hold partial refunds for reconciliation, and never run native plus custom emitters without deduplication. No Partnero account or data changed.
