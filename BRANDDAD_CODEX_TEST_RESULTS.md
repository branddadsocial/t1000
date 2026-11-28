# BrandDad Codex test results

## 2026-11-26 AI Website Improvement Plan

- Local verification: 42/42 Node contract files passed, 42/42 deployment PHP files linted, the focused frontend renderer passed 15/15 checks, and both repositories passed `git diff --check` apart from Git's expected CRLF notices.
- Rafter local secret scanning found no secret. Required remote `rafter run` was attempted and rejected because the free fast-scan allowance is already 16/16; paid Plus was not used.
- Manual Rafter web/API/LLM review found no unresolved critical issue: identity is the existing session; order and job ownership are enforced server-side; one-time products are exact catalog allowlists; the form uses a nonce and bounded schema; the provider origin/workflow are fixed; redirects are disabled; output is bounded, stripped, and escaped; and the model has no website-write tool. See `BRANDDAD_AI_WEBSITE_PLAN_SECURE_DESIGN.md`.
- Production deployment and live no-charge checkout smoke remain to be recorded below; no card was charged and no FormWise credit was spent by these local tests.

## 2026-11-21 Directory response-integrity recovery acceptance

- StartHost replacement evidence: upload `1/1` completed for `branddad-directory-experience.php`; read-only hosted source inspection shows `<?php`, plugin version `0.68.15`, and the fixed Directory Suite URL inside the constant rather than before the PHP boundary.
- REST recovery: `GET /wp-json/wp/v2/pages?slug=add-listing&context=view&_fields=id,slug,link,content,status` returned HTTP 200, 844 bytes, valid JSON, no raw Suite prefix, and no superseded assistant ID.
- Public recovery sweep: Home (559,590 bytes), All Categories (474,858), Restaurants (443,349), canonical Events (438,140), canonical Jobs (436,769), Add Listing (504,405), `/events/` (436,768), and `/jobs/` (435,482) all returned HTTP 200 with full HTML rather than the 73-byte Suite URL.
- Assistant reference checks: all eight sampled public surfaces contained Suite `32733848-06ea-47ad-bff9-4a95b9a0e3c1`; none contained the superseded standalone assistant signature.
- Local recovery gate: PHP lint, Directory navigation/home contracts, `git diff --check`, and file-specific local secret scanning passed. Remote `rafter run --mode fast` remains blocked by the existing 16/16 free-scan quota; paid Plus was not used.

## 2026-11-21 FormWise domain-restricted Suite production acceptance

- Provider setting read-back: Sales Suite allowlists `https://branddad.social`; Directory Suite allowlists `https://directory.branddad.social`; both contain exactly one intended published Agent and have session context off.
- Live StartHost save: Social Sales Assistant MU plugin version `0.4.2`; Directory Experience version `0.68.15`. Exact pre-change files were preserved locally for rollback.
- Social browser smoke: the homepage launcher opened `https://ai.branddad.social/toolsets/e49a28ba-e73c-4c4e-b945-92018cd47034` inside the BrandDad AI Sales Assistant dialog.
- Cache-bypass reference sweep: Social Home, Directory Home, All Categories, Restaurants, canonical Events results, and canonical Jobs results returned HTTP 200, contained their intended new Suite ID, and contained no superseded standalone Agent ID.
- Deterministic checks: both edited PHP files pass `php -l`; Directory navigation and home-conversion contracts pass; `git diff --check` passes; file-specific `rafter secrets` reports no detected secrets.
- Manual OWASP review: A01/A02/A03/A04/A07/A08/A09/A10 and LLM01-LLM10 are N/A to the changed behavior because the diff only replaces fixed HTTPS embed constants and versions. A05 retains `esc_url`, `strict-origin-when-cross-origin`, the existing Social iframe sandbox, `noopener noreferrer`, and clipboard-only iframe permission. A06 has no dependency change. No model output, retrieval path, prompt, tool, user input, customer data, credential, authentication, payment, network fetcher, SQL, shell, deletion, or serialization path changed.
- Required remote `rafter run --mode fast` was attempted against the Social remote and rejected because all 16/16 free scans are used. Paid Plus was not run.
- The initial post-migration alias result was invalidated by the later-discovered one-line hosted-file corruption. After restoring the complete `0.68.15` plugin, `/events/`, `/jobs/`, and `/add-listing/` all return full HTTP 200 HTML; canonical discovery URLs remain `/all-listings/?directory_type=events` and `/all-listings/?directory_type=jobs-careers`.

## 2026-11-20 FormWise domain-restricted Suite preparation

- Live builder inspection: standalone Agent Share dialogs expose link/embed controls but no authorized-URL setting; Suite Settings expose `Restrict to authorized URLs`, an allowlist input, and session-context control.
- Suite composition read-back: Sales Suite contains only BrandDad Sales Assistant; Directory Suite contains only BrandDad Directory Concierge. Both report `Published`, `1 product`, `Restricted, no sites yet`, and session context off.
- White-label direct-link smoke: both new `https://ai.branddad.social/toolsets/...` URLs returned HTTP 200 before WordPress migration.
- No **Add** permission action, iframe-source change, Agent publication, customer assignment, plan assignment, or live WordPress deployment was performed in this preparation step.

## 2026-11-20 FormWise assistant reconciliation

- Sales Assistant draft/live comparison after repair: `changed=[]`; no publication was needed. Final Airbnb offer tests on the live-matching draft: 3/3 passed. Outputs state `$9.99` one time, one review case per purchase, customer-provided facts, editable policy-based message, no BrandDad submission, Airbnb-controlled decision, and no removal guarantee.
- Directory Concierge live/draft comparison: `changed=[]`; the repaired unpublished draft now exactly matches live v2, so no publication was performed.
- Directory Concierge focused tests: 3/3 passed for exact assistant identity, rejection of BrandDad Social service questions, and asking for city/region/country before directory recommendations.
- Live Directory assistant-reference sweep: 6/6 sampled public surfaces returned HTTP 200 and contained the current Concierge ID; 0/6 contained the superseded Assistant ID/name or the Social Sales Assistant ID.

## 2026-11-20 live route and conversion-link reconciliation

- WordPress REST index: all 15 expected BrandDad route roots present.
- Anonymous protected-route smoke: 11/11 customer/admin GET routes returned HTTP 401 `rest_forbidden`; zero returned data or `rest_no_route`.
- Public catalog smoke: `GET /wp-json/branddad/v1/sales/catalog` returned HTTP 200 with the canonical `$29.99` Business Essentials offer and product URL.
- Social Home/Services internal-destination audit: 61/61 unique non-mutating public URLs returned HTTP 200 under four-request throttling; account, checkout, cart, add-to-cart, mail, phone, script, and fragment actions were deliberately excluded.
- FormWise share audit: all three published public assistants currently report `embedding=anywhere`, `allowedSites=[]`, and direct public links. No agent setting or publication state was changed during this read-only audit.

## 2026-11-20 Airbnb production copy reconciliation

- Live `Services`: HTTP 200, launch version `0.5.25`, one-Airbnb-review section present, legacy multi-platform section absent, and no active cart action.
- Live product: HTTP 200; visible short and long descriptions contain the one-review scope and `$9.99 one time`; no visible quote copy and no active cart action.
- Source `0.5.26`: `php -l`, `tests/launch-funnel-test.php`, `tests/launch-heading-test.php`, `tests/brand-contrast-test.php`, and `git diff --check` pass. The funnel test proves exactly one description meta tag, fixed scope/price text, removal of the legacy multi-platform quote text, and preservation of the remaining response body.
- `rafter secrets deploy/bds-ai-launch-pages-mu.php`: no secrets detected (Betterleaks version warning retained). `rafter run --mode fast`: blocked by the existing 16/16 quota; paid Plus not used.
- Manual Rafter/CWE review: the exact-product gate is server-derived; the HTML callback accepts no request values, replaces at most one description tag, escapes the fixed description, and performs no database, filesystem, auth, credential, payment, network, or deletion action.
- StartHost production upload: pass; File Manager reported `1/1 Completed` for the exact `bds-ai-launch-pages-mu.php` `0.5.26` file.
- Post-upload live acceptance: two cache-bypass requests returned HTTP 200; the product has exactly one correct description meta tag, visible one-case/$9.99 copy, no visible legacy quote/multi-platform copy, and no Woo Add to Cart control. The Services HTML stamps launch version `0.5.26`.

## 2026-11-20 Airbnb one-review product

- `node tests/airbnb-review-case-contract.mjs`: pass; 21 focused entitlement, schema, privacy, refund, queue-race, catalog, and copy assertions.
- Full Node contract sweep: 40/40 files pass.
- Deployment PHP syntax sweep: 45/45 files pass.
- Standalone PHP compatibility sweep: 4/4 files pass.
- `git diff --check`: pass (line-ending conversion warnings only).
- `rafter secrets .`: pass, no secrets detected; Betterleaks version-mismatch warning recorded.
- Manual Rafter code review: web access control, API BOLA/BFLA/BOPLA/resource controls, LLM prompt injection/output handling/agency/cost reviewed against `deploy/bds-ai-execution-mu.php`. Evidence: authenticated route/body cap at lines 152–156; explicit input allowlist at 176–205; customer-owned paid order lookup at 369–409; locked reservation/job binding at 414–463; payment/refund recheck at 466–483; owner-only job response at 543–558; HMAC-bound idempotency at 580–596; private author-owned job creation at 641–679; fixed provider origin/disabled redirects/bounded response at 766–789; stripped/bounded output at 707–716. The FormWise prompt has no credentials or action tools and explicitly treats form input as untrusted.
- `rafter run --mode fast`: attempted, blocked by account fast-scan limit 16/16. No paid Plus scan run.
- StartHost production upload: pass; the File Manager reported `3/3` and `Completed` for the catalog, execution, and launch-page modules.
- Live Services smoke: HTTP 200; contains `One Airbnb review. One simple payment.` and no longer contains the old `Google, Airbnb, and Trustpilot` block.
- Live one-review product smoke: HTTP 200; contains `$9.99 one time for one Airbnb review case.` and the staged-checkout notice; contains neither `Add to cart` nor the legacy `$699.00` price.
- Live protected route smoke: `/wp-json/branddad/v1/ai/tools` returns HTTP 401 `rest_forbidden` anonymously, proving the route is loaded and fail-closed rather than missing.

## 2026-11-20 release-integrity refresh

- Reconciled the root source checksum manifest against every listed `deploy/` artifact and corrected four stale hashes: trial, customer reporting, Directory membership bridge, and lifecycle events.
- Added a separate two-file staging checksum manifest for the customer-status module and the staging-only reporting acceptance helper. The helper is deliberately absent from the production manifest.
- `tests/release-checksums-contract.mjs` validates strict manifest syntax, unique filenames, exact SHA-256 source matches, the exact reporting-stage staging pair, and production exclusion of the acceptance helper.
- Full local suite: 38/38 Node contract files, 43/43 recursive deployment PHP syntax checks, and `git diff --check` passed.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted again and remains blocked by the 16/16 fast-scan quota; paid Plus was not used.
- The immutable 2026-11-19 ZIP archives are now explicitly documented as older than four reviewed source revisions and are not the current deployment source of truth.

## 2026-11-20 evidence-stage monthly customer reporting

- `tests/customer-status-api-contract.mjs`: passed all 17 assertions, including exact stage keys, completed-job-only generation, and unavailable later stages.
- `tests/staging-report-stages-acceptance-contract.mjs`: 12/12 assertions passed for exact-host/read-only fixture verification and bounded boolean evidence.
- Full local suite: 37/37 Node contract files, 43/43 deployment PHP syntax checks, and `git diff --check` passed.
- Manual ASVS L2 web/API review: no new input/route/permission/query; existing authenticated author scope and period/row bounds; fixed server projection; no content/identifier disclosure; evidence labels prevent publication/business-impact overclaiming; additive compatibility only.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted status: passed on 2026-11-20. The two files matched the reviewed staging upload set, the one-time report returned HTTP 200 with `complete=true`, all 10 allowlisted checks true, and no error codes.
- Hosted isolation/smoke: staging homepage HTTP 200 with `noindex`; account status, value report, AI tools, onboarding, and inquiry routes deny anonymous callers with HTTP 401; production exposes no reporting-stage acceptance route (HTTP 404).

## 2026-11-20 hosted lifecycle truncation stress attempt and recovery

- `tests/staging-lifecycle-truncation-acceptance-contract.mjs`: 28/28 assertions passed after the hosted recovery hardening.
- Manual ASVS L2 web/API review: route-only mutation; fixed 5,000 ceiling; bounded batches/lock; exact non-admin fixture; private non-financial schema; production aggregate reuse; persistent state; collision denial; bounded public progress; fixed object-type recovery; over-target/range/duplicate/gap denial; full author/user/type/object/date/HMAC verification; guarded hard delete; and fail-closed incomplete reporting.
- `rafter secrets .`: no secrets detected. Required remote `rafter run` remains blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted stress did not reach 5,000 because shared-host request execution exceeded the safe window. Creation was disabled and the helper reconciled 1,560 exact rows, including timeout-orphaned rows not yet present in persisted progress.
- Hosted recovery final report: HTTP 200, `complete=false`, `error_codes=["host_execution_ceiling"]`, with `cleanup_complete=true`, `summary_restored=true`, and `state_removed=true`. No synthetic row remains according to the exact restored production aggregate evidence.
- Post-recovery smoke: staging homepage HTTP 200 with `noindex`; production evidence route HTTP 404.
- Result: safe recovery passed; the 5,000-event `truncated=true` assertion did not run and must not be described as passed.

## 2026-11-20 hosted lifecycle UTC-boundary acceptance

- `tests/staging-lifecycle-boundary-acceptance-contract.mjs`: 17/17 assertions passed.
- Full local suite: 35/35 Node contract files, 41/41 deployment PHP syntax checks, and `git diff --check` passed.
- Manual ASVS L2 web/API review: canonical-host and one-time controls; no caller input; exact marker-owned non-admin fixture; current UTC immutable timestamps; four private non-financial events; production aggregate reuse; exact +2 monthly delta; unchanged financial aggregates; HMAC-bound exact cleanup; restored summary/current-user context; bounded boolean-only report; no network, payment, provider, message, or production surface.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted report: HTTP 200, `complete=true`, 15/15 checks true, no error codes. Start `00:00:00` and end `23:59:59` UTC events counted; the immediately prior and next seconds did not; financial aggregates remained identical; all four events were removed; the complete summary was restored.
- Post-run smoke: staging homepage HTTP 200 with `noindex`; production acceptance route HTTP 404.
- Acceptance boundary: this proves current-month UTC inclusivity, not local-time presentation/DST, arbitrary historical periods, the separate 5,000-row truncation flag, Stripe settlement/webhooks, or accounting reconciliation.

## 2026-11-20 staging launch-catalog fixtures preparation

- `tests/staging-launch-products-fixture-contract.mjs`: 22/22 assertions passed.
- Full local suite: 34/34 Node contract files, 40/40 PHP syntax checks, and `git diff --check` passed.
- Manual ASVS L2 web/API review: canonical staging isolation; literal four-product scope; authoritative launch-state/price/credit cross-check; all-slug preflight; exact marker/type collision denial; ownership marker saved with creation; hidden/virtual/monthly/zero-setup/zero-trial state; full read-back verification; bounded report; no external call, user, order, subscription, payment, message, or deletion.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted report: HTTP 200, `complete=true`, 4/4 product checks true, no error codes.
- Product smoke: AI Studio Starter, AI Local SEO, AI Lead Assistant, and AI Social Growth pages each return HTTP 200, display an approved launch price, and retain `noindex`. Production fixture route is HTTP 404; staging sales chat remains default-off at HTTP 503.
- No real gateway, order, subscription, customer, provider call, message, production product, or customer agreement was created or changed.

## 2026-11-20 sales-assistant grounding/cost acceptance preparation

- `tests/staging-sales-assistant-acceptance-contract.mjs`: 39/39 assertions passed.
- Full local suite: 33/33 Node contract files, 39/39 PHP syntax checks, and `git diff --check` passed.
- Manual ASVS L2 web/API and OWASP LLM review: exact staging/report gating; dummy request-only key; complete provider interception; owned-origin/body/message controls; HMAC client-bound session; fixed agent/HTTPS egress; safe HTTP bounds; server-owned offer links; hostile HTML/URL stripping; no tools/actions; per-client/global caps; exact state cleanup; boolean-only report; and production default-off.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Acceptance boundary: fixed synthetic questions and a local provider-shaped response are used, so no FormWise credit, real customer, lead, order, payment, email, or production data is involved.
- Hosted helper 0.1.0: HTTP 200, `complete=true`, 31/31 checks true, no error codes. It proved exact provider request bounds, fail-closed missing-product links, hostile HTML/URL removal, HMAC session controls, continuation, daily/client caps, zero unexpected requests, and cleanup.
- Hosted helper 0.1.1 after launch-fixture creation: HTTP 200, `complete=true`, 31/31 checks true, no error codes. Local SEO, Lead Assistant, Social Growth, and Business Essentials recommendations each had their expected server-owned staging product link; model-supplied links remained stripped and all prior session/cost/cleanup assertions remained true.
- Post-run staging smoke: homepage/product/catalog HTTP 200, homepage `noindex` retained, and public chat returned to HTTP 503 default-off. Production acceptance route returned HTTP 404.
- No real FormWise request/credit, user, lead, job, order, payment, email, or production mutation occurred. Real provider cost, semantic answer quality, frontend rendering, human escalation, and production enablement remain outside this local-provider-shaped test.

## 2026-11-20 hosted Woo refund-hook acceptance preparation

- `tests/staging-woo-refund-acceptance-contract.mjs`: 41/41 assertions passed.
- Full local suite: 32/32 Node contract files, 38/38 deployment PHP syntax checks, and `git diff --check` passed.
- Manual ASVS L2 web/API review: canonical-home-host and one-time controls; no caller input; fixed marker-owned fixture identity/product; pending order marked before completed transition; no-gateway payment method; real Woo refund objects with gateway refund/restock disabled; request-local email suppression; lifecycle key idempotency; exact aggregate deltas; boolean-only report; and exact guarded cleanup.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota. Paid Plus was not used.
- Acceptance boundary: this synthetic test cannot prove Stripe webhook delivery, bank settlement, real card refunds, UTC month boundaries, 5,000-row truncation, or accounting reconciliation.
- Hosted report: HTTP 200, `complete=true`, 30/30 checks true, no error codes. Woo created a $5.00 partial refund and $24.99 remaining refund, emitted the real refund hook, reused each event on replay, reached refunded order status, produced exact +2999 gross/+2999 refunds/zero net aggregate deltas, suppressed mail, left the fixture lead unchanged, and removed all request-created event/refund/order records.
- Post-run smoke: staging homepage/product/sales catalog HTTP 200, homepage `noindex` retained, account and finance routes HTTP 401 anonymously, and production evidence route HTTP 404.
- No real card, gateway transaction, Stripe webhook, provider execution, message, customer record, or production mutation occurred.

## 2026-11-19 synthetic subscription-lifecycle acceptance preparation

- `tests/staging-subscription-lifecycle-acceptance-contract.mjs`: 44/44 assertions passed.
- `tests/ai-subscription-lifecycle-contract.mjs` and `tests/ai-trial-contract.mjs`: passed.
- Full local suite: 31/31 Node contract files, 3/3 PHP compatibility suites, 31/31 deployment PHP syntax checks, and `git diff --check` passed.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Manual L2 review: the route is canonical-host/one-time gated and parameter-free; fixture identity/product/order/subscription are server-derived and marker checked; the acceptance credential is dummy and every fixed FormWise request is locally intercepted; reports expose only allowlisted booleans; retry/event/user-state cleanup is exact; deletion requires ids created in the request plus exact markers; no SQL, shell, file path, deserialization, real gateway, message, account creation, or external provider request exists.
- Hosted helper 0.1.0 report: HTTP 200, `complete=false`; 32/36 checks true. Exact cleanup, state restoration, retry removal, event cleanup, provider isolation, and no-gateway checks all passed. Failures identified immediate WP Swings status-meta callbacks plus a real stale-parent reactivation defect; no customer/provider/payment/production mutation occurred.
- Adapter 0.5.1 now makes the legacy active-status callback use the newest authoritative paid order. Helper 0.1.1 accounts for immediate status-meta revocation callbacks and tests the customer-facing active-plan resolver. Focused and full local suites pass again.
- Hosted helper 0.1.1 report: HTTP 200, `complete=true`, 36/36 checks true, no error codes. Synthetic fixture events/records, trial history, scheduled retries, and current-user context all restored; provider isolation and no-gateway checks passed.
- Post-run staging smoke: homepage/product HTTP 200 with `noindex`; catalog HTTP 200; account, report, AI, trial, onboarding, monitoring, inquiry, analytics, and finance routes HTTP 401 anonymously. Production acceptance route HTTP 404.
- Stripe checkout/webhook, real FormWise provisioning, browser REST nonce, email, and production behavior remain outside this synthetic test.

## 2026-11-19 hosted website-monitoring acceptance

- `tests/website-monitoring-contract.mjs`: 22/22 assertions passed.
- `tests/staging-website-monitoring-acceptance-contract.mjs`: 54/54 assertions passed.
- `php -l` passed for all 26 deployment PHP files. Full local suite: 26/26 Node contract files, three PHP compatibility suites, and `git diff --check` passed.
- Manual Rafter review confirmed that the internal plan candidate is revalidated through paid-mode and plan allowlists; the browser cannot choose the destination; local/private/IP/credential/port targets fail closed; each production fetch rechecks DNS under a stale-safe owner lock; safe HTTP has zero redirects, a 10-second timeout, and a 64 KiB cap; only measurements/hash are stored; and all customer routes require a WordPress session.
- The exact-host helper accepts no browser input, enables the feature for only one request, suppresses recurring scheduling, limits its synthetic plan to the marked non-admin fixture, fetches only BrandDad staging, returns boolean evidence, and restores fixture metadata in `finally`.
- `rafter secrets .`: no secrets detected. Required `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted report: `complete=true`; 27/27 checks true; no error codes.
- Verified anonymous denial, plan revalidation, SSRF target rejection, owned-host DNS/fetch, bounded response metadata, hash-only retention, exact report counters, active/stale locks, network failure, visible 3xx without following, URL/plan shutdown, manual rate limiting, cross-account isolation, fixture restoration, and no recurring schedule.
- Post-run staging smoke: homepage/product/catalog 200, noindex retained, and anonymous account/value/AI/onboarding/monitoring/admin-finance routes 401.
- Production evidence route returned HTTP 404. No real customer entitlement, message, provider call, recurring job, payment, or production mutation occurred.

## 2026-11-19 hosted provider-cost acceptance

- `tests/provider-cost-ledger-contract.mjs`: 19/19 assertions passed.
- `tests/staging-provider-cost-acceptance-contract.mjs`: 42/42 assertions passed.
- `php -l` passed for the corrected ledger, the new acceptance helper, and all 25 deployment PHP files.
- Full local suite: 25/25 Node contract files, three PHP compatibility suites, all deployment PHP syntax checks, and `git diff --check` passed.
- Manual Rafter review confirmed that the accounting writer has no public REST route; the admin aggregate remains GET-only and `manage_woocommerce` gated; the staging evidence route is exact-host, request-input-free, and boolean-only; invalid signed/fractional/oversized/future values fail closed; the fixed fixture reuses the normal HMAC and lock path; and no customer/payment/provider/message mutation exists.
- `rafter secrets .`: no secrets detected. The CLI emitted its known Betterleaks version warning and exited successfully.
- Required `rafter run` fast scan was attempted and blocked by the 16/16 quota. Paid Plus was not used.
- Hosted report: `complete=true`; 24/24 checks true; no error codes.
- Verified non-admin denial; all malformed accounting inputs fail closed; one fixed synthetic private row is stored; replay reuses that row; raw reference and untrusted metadata are absent; provider/product/total cost deltas are +137 minor units; contribution delta is -137; coverage remains explicitly partial; and invalid periods are rejected.
- Post-run staging smoke: homepage 200/noindex, product 200/noindex, public sales catalog 200, and anonymous account/value/AI/onboarding/admin-finance routes 401.
- Production evidence route returned HTTP 404. No real provider cost, payment, refund, customer charge, message, provider run, entitlement, or production mutation occurred.

## 2026-11-19 hosted customer API acceptance

- `php -l deploy/bds-staging-customer-api-acceptance-mu.php`: passed.
- `tests/staging-customer-api-acceptance-contract.mjs`: 30/30 assertions passed.
- Full local suite: 24/24 Node contract files, three PHP compatibility suites, four PHP syntax checks, and `git diff --check` passed.
- Local Rafter secret scans found no secrets. Manual review confirmed exact-host scope, marker-owned non-admin fixtures, boolean-only evidence, stored-only public URL behavior, and no browser input, exposed credential, user/admin/order/subscription/payment creation, network call, message, provider run, or post/user deletion.
- Required remote `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted report: `complete=true`; 17/17 checks true; no error codes.
- Verified localhost/private-IP/oversized/unsupported/unknown-only rejection, active-lock conflict, stale-lock recovery, exact one-version advancement, profile isolation, completed/failed job deltas, completed-only credits, cross-account exclusion, safe status response, and invalid-period rejection.
- Post-run staging smoke: homepage 200/noindex, sales catalog 200, product 200, and protected anonymous AI/account/value/onboarding routes 401.
- Production evidence route returned HTTP 404. No real customer, payment, message, provider run, entitlement, or production mutation occurred.

## 2026-11-19 hosted lifecycle acceptance

- `php -l deploy/bds-staging-lifecycle-acceptance-mu.php`: passed.
- `tests/staging-lifecycle-acceptance-contract.mjs`: 26/26 assertions passed.
- Full local suite: 23/23 Node contract files, three PHP compatibility suites, five PHP syntax checks, and `git diff --check` passed.
- Local Rafter secret scans found no secrets. Manual review confirmed exact-host scope, non-admin fixture reuse, boolean-only evidence, privacy-safe metadata assertions, and no user/admin/order/subscription/refund creation, network call, message, deletion, provider execution, or production mutation.
- Required remote `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted report: `complete=true`; 12/12 checks true; no error codes.
- Verified subscriber analytics denial, unknown-event rejection, idempotent duplicate reuse with one private row, privacy stripping, exact bounded financial metadata, aggregate deltas of +2999 gross/+500 refunds/+2499 net, and invalid-period rejection.
- Production evidence route returned HTTP 404. No real Woo order/payment/refund, message, provider execution, or production mutation occurred.

## 2026-11-19 hosted AI provider-failure acceptance

- `php -l deploy/bds-staging-ai-failure-acceptance-mu.php`: passed.
- `tests/staging-ai-failure-acceptance-contract.mjs`: 25/25 assertions passed.
- Full local suite: 22/22 Node contract files, three PHP compatibility suites, three PHP syntax checks, and `git diff --check` passed.
- Local Rafter secret scans found no secrets. Manual review confirmed exact-host scope, marked non-admin owner, configured-provider refusal, boolean-only evidence, and no request input, exposed credential, user/admin/order/payment creation, direct network call, message, or deletion.
- Required remote `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted report: `complete=true`; 9/9 checks true; no error codes.
- Verified a five-credit reservation exists before the worker, missing configuration fails closed as `execution_not_configured`, the reservation is released, used credits remain zero, and replay does not double-deduct.
- Post-run staging smoke: homepage 200/noindex, sales catalog 200, product 200, and protected anonymous AI/account/onboarding/admin routes 401.
- Production evidence route returned HTTP 404. No FormWise request/credit spend, payment, message, or production mutation occurred.

## 2026-11-19 authenticated staging acceptance

- `php -l deploy/bds-staging-auth-acceptance-mu.php`: passed.
- `tests/staging-auth-acceptance-contract.mjs`: 23/23 assertions passed.
- Full local suite: 21/21 Node contract files, three PHP compatibility suites, two PHP syntax checks, and `git diff --check` passed.
- Local Rafter secret scans of the helper and contract found no secrets. Manual authorization review found no public credential, administrator role, request parameter, outbound request, payment/provider/message side effect, or deletion path.
- Required remote `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- Hosted one-time report: `complete=true`; all nine allowlisted checks true; no error codes.
- Verified two fixture identities have subscriber-only capabilities, anonymous permission callbacks deny access, and authenticated subscriber callbacks allow access.
- Verified onboarding owner update, stale-version conflict, cross-account profile isolation, AI-job owner read, and cross-account `not_found`/HTTP 404 behavior.
- Post-run staging smoke: homepage 200/noindex, sales catalog 200, Business Essentials product 200, and anonymous AI/account/onboarding/value routes 401.
- Production probe of the staging-only evidence route returned HTTP 404. No payment, provider execution, message, administrator, or production mutation occurred.

## 2026-11-19 FormWise published-contract audit

- Read-only FormWise evidence: BrandDad Social organization, owner role, published assets, restricted portals, and commercial tier plans were available.
- Backend-to-provider identity: all six referenced Flow/App UUIDs match the current published assets.
- Backend-to-provider input contract: all referenced field keys, required states, and enum options match the current published Flow schemas.
- `tests/formwise-published-contract.mjs`: passed all 56 assertions covering asset ids, field keys, enum values, endpoint construction, payload construction, and server-only bearer authentication.
- Full local suite: 20/20 Node contract files, three PHP compatibility suites, AI execution PHP syntax, and `git diff --check` passed.
- `rafter secrets tests/formwise-published-contract.mjs`: no secrets detected. Manual review found no dynamic input, outbound request, credential value, mutation, or executable command in the regression test.
- Required remote `rafter run` was attempted and blocked by the 16/16 fast-scan quota; paid Plus was not used.
- No credit-consuming FormWise execution was performed; provider response parsing and live WordPress queue completion remain explicitly untested.

## 2026-11-19 hosted staging bootstrap

- Created `staging.branddad.social` at `public_html/staging.branddad.social` with a separate one-click WordPress database.
- Completed the WordPress installation with a staging-only administrator and indexing discouraged; the public HTML contains a `noindex` directive.
- Started a full StartHost files-and-databases recovery backup; the control panel still showed the backup job processing at the last observation.
- Uploaded the reviewed Social release archive and verified all 16 expected MU modules are present directly in the staging `wp-content/mu-plugins/` directory.
- `http://staging.branddad.social/` returned HTTP 200; the valid-TLS temporary hostname now serves the installed WordPress site.
- `https://staging.branddad.social/` failed certificate validation because StartHost cannot issue its free wildcard certificate while authoritative nameservers are external.
- `https://staging-branddad-social.stackstaging.com/` and `/wp-json/` returned HTTP 200 with valid TLS.
- The REST index advertises `branddad/v1`; its namespace contains 21 expected routes.
- `/sales/catalog` returned HTTP 200 with the fail-safe Business Essentials fallback. Its purchase URL remains empty because WooCommerce/product dependencies are intentionally absent from the clean staging copy.
- Anonymous requests to AI tools, customer status, value reports, onboarding, monitoring, inquiry inbox, analytics, finance, and trial endpoints returned HTTP 401.
- Production Social and Directory files were not changed.

## 2026-11-19 release-package verification

- 17/17 deploy PHP files passed `php -l`.
- 15/15 Node contract suites passed.
- 3/3 compatibility PHP suites passed.
- 35/35 local validation commands passed; zero failures.
- Social archive contains the intended 15 MU modules plus manifest and checksum file.
- Directory archive contains the intended 7 MU modules plus manifest and checksum file.
- Every packaged PHP file has the same SHA-256 as its reviewed `deploy/` source.
- `rafter secrets .` passed with no detected secrets; the CLI still reports its known Betterleaks output-version warning.
- `rafter run --mode fast` was attempted again and remains blocked by the 16/16 account quota until reset. No paid Plus scan was used.
- No live provider execution, payment, email, public-post edit, or production file change occurred during this verification.

## 2026-11-19 provider-cost batch

- 18/18 deploy PHP files passed `php -l`.
- 16/16 Node contract suites passed, including 17 provider-cost ledger security/behavior assertions.
- 3/3 compatibility PHP suites passed.
- 37/37 local validation commands passed; zero failures.
- Social release archive now contains 16 MU modules plus manifest/checksum files; Directory contains 7 MU modules plus manifest/checksum files.
- Every packaged PHP file matches its reviewed `deploy/` source SHA-256.
- Social archive SHA-256: `BB40EB4C379EAA9235E8D0DE3991773FD208FB0562206F28D619B404C7FE82A1`.
- Directory archive SHA-256: `1951A5F97522E0F407EE4C18E569279752E534B75966F5A9F2C92C87DE91F193`.
- Local `rafter secrets .` passed and MCP Rafter secret scan returned no findings.
- Required `rafter run --mode fast` was attempted and remains blocked by the 16/16 quota; paid Plus was not used.
- WordPress staging still must prove admin denial, replay, lock recovery, period boundaries, truncation, malformed stored rows, refunds, multi-currency totals, and incomplete-coverage labeling before production activation.

## 2026-11-19 read-only live health smoke

- `https://branddad.social/`: HTTP 200, TLS verification result 0.
- `https://branddad.social/services/`: HTTP 200, TLS verification result 0.
- `https://branddad.social/wp-json/`: HTTP 200, TLS verification result 0.
- `https://directory.branddad.social/`: HTTP 200, TLS verification result 0.
- `https://directory.branddad.social/wp-json/`: HTTP 200, TLS verification result 0.
- Neither public REST index currently advertises a `/branddad/v1` route. This is positive evidence that the new backend package has not been deployed; local readiness must not be described as live functionality.

Updated: 2026-11-19

## 2026-11-19 hosted staging Business Essentials fixture verification

- Staging sales catalog: HTTP 200; canonical offer slug `ai-business-essentials`, monthly `29.99`, staging URL present, `for_sale=true`.
- Staging product: HTTP 200, `noindex`, $29.99, staging/not-production disclosure, `Subscribe monthly` action.
- Woo Store API disposable cart: product id 15, simple/virtual item, hidden catalog visibility, sold individually, quantity maximum 1, 2999 minor-unit price, no shipping, no errors.
- Woo Store API payment methods: empty. Stripe checkout-session extension remained `uninitialized`; no payment was attempted.
- Protected anonymous REST checks: HTTP 401 for AI tools, account status, trial, onboarding, and admin analytics.
- Staging home and public REST/catalog checks: HTTP 200.
- Helper behavior statically and locally verifies exact monthly/trial/BrandDad metadata by read-back before completion. Independent database-row inspection remains unverified because the hosting phpMyAdmin SSO did not open the selected staging database session.
- No order, customer account, subscription, payment, message, FormWise execution, or production mutation occurred.

## 2026-11-19 staging Business Essentials fixture preparation

- `php -l deploy/bds-staging-business-essentials-fixture-mu.php`: passed.
- `tests/staging-business-essentials-fixture-contract.mjs`: 19/19 assertions passed.
- Full Node contract suite after adding the fixture contract: 19/19 passed.
- PHP compatibility suites: 3/3 passed.
- `rafter secrets deploy/bds-staging-business-essentials-fixture-mu.php`: passed with no detected secrets.
- Required `rafter run` fast: attempted and blocked by the 16/16 quota. Paid Plus was not used.
- Fixture deployment/execution is not yet claimed in this section; no product, order, subscription, payment, or production state was changed by the local preparation.

## 2026-11-19 Business Essentials product identity verification

- Read-only public product evidence: `https://branddad.social/product/ai-business-essentials/` returned HTTP 200; the obsolete prefixed slug returned HTTP 404.
- Added `tests/business-essentials-product-identity-contract.mjs`; all seven assertions passed.
- Updated trial, Directory membership bridge, and lifecycle contract version/identity assertions.
- Full Node contract suite: 18/18 passed.
- PHP compatibility suites: 3/3 passed.
- Affected PHP syntax checks: 3/3 passed.
- `git diff --check`: passed.
- Rafter local secret scans for all three affected PHP modules: passed with no detected secrets.
- Required `rafter run` fast: attempted and quota-blocked at 16/16. Paid Plus was not used.
- No staging/production write, checkout, charge, subscription mutation, Directory account creation, or provider execution occurred in this correction batch.

## 2026-11-19 hosted staging commerce dependency verification

- Hosted staging home: HTTP 200 over the valid StartHost temporary TLS hostname, with `noindex` present.
- WordPress REST index: HTTP 200.
- BrandDad namespace: HTTP 200 with 21 expected routes.
- Public sales catalog (`/branddad/v1/sales/catalog`): HTTP 200 with one fallback offer; `url` is empty and `for_sale` is false because no staging product fixture/mapping exists.
- Anonymous protected routes returned HTTP 401 for AI tools, customer status, value reports, analytics, finance, onboarding, monitoring, inquiry inbox/form, and trial state.
- WooCommerce namespaces (`wc/v3`, Store API), WooCommerce Stripe REST routes, and Subscriptions for WooCommerce (`wsp-route/v1`) are present after one-time activation.
- One transient HTTP 500 was observed during the activation request itself. Immediate and repeated post-activation requests returned HTTP 200; no continuing 500 was reproduced.
- All 17 Node contract suites passed (17/17), including 12 staging-activator assertions covering the exact host, literal plugin allowlist, path validation, one-time guard, active-state verification, non-autoloaded report, and absence of request/network/deletion surfaces.
- PHP compatibility suites passed: revenue sections, Featured rotation, and customer hub (3/3).
- `php -l deploy/bds-staging-dependency-activator-mu.php` passed.
- `git diff --check` passed.
- `rafter secrets deploy/bds-staging-dependency-activator-mu.php` passed with no detected secrets.
- `rafter run deploy/bds-staging-dependency-activator-mu.php` was attempted but blocked by the 16/16 fast-scan quota. Paid Plus was not used.
- No checkout, charge, refund, subscription mutation, FormWise execution, customer message, or production deployment was performed.

## Passed locally

- Combined validation: 15 checks, 0 failures after security hardening.
- PHP lint: `deploy/bds-ai-subscriptions-mu.php`.
- PHP lint: `deploy/bds-directory-free-ai-mu.php`.
- PHP lint: `deploy/bds-ai-execution-mu.php`.
- PHP lint: `deploy/bds-account-link-mu.php`.
- PHP lint: `deploy/bds-sales-assistant-api-mu.php`.
- `node tests/ai-subscription-lifecycle-contract.mjs`.
- `node tests/directory-free-ai-contract.mjs`.
- `node tests/ai-execution-contract.mjs`.
- `node tests/account-link-contract.mjs`.
- `node tests/sales-assistant-api-contract.mjs`.
- PHP lint: `deploy/bds-consent-followup-worker-mu.php`.
- PHP lint: `deploy/bds-onboarding-api-mu.php`.
- `node tests/consent-followup-worker-contract.mjs`.
- `node tests/onboarding-api-contract.mjs`.
- Combined validation after this batch: 19 checks, 0 failures.
- PHP lint: `deploy/bds-ai-trial-mu.php`.
- PHP lint: `deploy/bds-customer-status-api-mu.php`.
- PHP lint: `deploy/bds-directory-membership-bridge-mu.php`.
- `node tests/ai-trial-contract.mjs`.
- `node tests/customer-status-api-contract.mjs`.
- `node tests/directory-membership-bridge-contract.mjs`.
- Final local batch: 12 deploy PHP lint commands, 10 Node contract suites, and 3 legacy PHP suites passed (25 validation commands, 0 failures).
- PHP lint: `deploy/bds-featured-billing-adapter-mu.php`.
- `node tests/featured-billing-adapter-contract.mjs`.
- Current full suite: 13 deploy PHP lint commands, 11 Node contract suites, and 3 legacy PHP suites passed (27 validation commands, 0 failures).
- PHP lint: `deploy/bds-lifecycle-events-mu.php` and all five event-emitting integrations.
- `node tests/lifecycle-events-contract.mjs` verifies HMAC idempotency, stale-lock recovery, privacy allowlists, committed-work-only AI events, persistent-link ordering, stable onboarding completion, refunds, admin authorization, bounded periods/queries, and gross/refund/net reporting.
- Current full suite after lifecycle analytics: 14 deploy PHP lint commands, 12 Node contract suites, and 3 legacy PHP suites passed (29 validation commands, 0 failures).
- PHP lint: `deploy/bds-sales-lead-pipeline-mu.php`, follow-up worker 0.1.1, and lifecycle ledger 0.2.0.
- `node tests/sales-lead-pipeline-contract.mjs` verifies default-off capture, private records, owned origins, request/rate/field bounds, HMAC deduplication, server-owned stages, Woo/trial/onboarding/revocation hooks, separate consent, suppression-safe unsubscribe, admin authorization/pagination, retention/anonymization, internal lifecycle events, and absence of outbound send/fetch behavior.
- Current full suite after the lead pipeline: 15 deploy PHP lint commands, 13 Node contract suites, and 3 legacy PHP suites passed (31 validation commands, 0 failures).
- PHP lint: `deploy/bds-website-monitoring-mu.php`, customer status API 0.2.0, and lifecycle ledger 0.3.0.
- `node tests/website-monitoring-contract.mjs` verifies default-off paid entitlement, onboarding-only destination, customer authorization, public A/AAAA resolution, URL credential/IP/port rejection, per-check DNS validation, safe bounded no-redirect fetch, no body retention or messaging, manual rate limits, stale locks, plan/URL shutdown, bounded scheduler continuation, 24-period evidence, report integration, and authenticated routes.
- Current full suite after website monitoring: 16 deploy PHP lint commands, 14 Node contract suites, and 3 legacy PHP suites passed (33 validation commands, 0 failures).
- PHP lint: `deploy/bds-customer-inquiries-mu.php`, customer status API 0.3.0, and lifecycle ledger 0.4.0.
- `node tests/customer-inquiries-contract.mjs` verifies default-off/private records, paid eligibility, opaque unique owner references, owned origins, body/rate/honeypot bounds, privacy/contact consent, server-owned owner/status, opaque public responses, author-scoped pagination, cross-account 404, optimistic status versions, actual monthly counts, bounded retention, PII-free lifecycle evidence, and no outbound send/fetch.
- Current full suite after customer inquiries: 17 deploy PHP lint commands, 15 Node contract suites, and 3 legacy PHP suites passed (35 validation commands, 0 failures).
- Subscription bridge 0.5.0 contract adds latest-renewal reconciliation, same-cycle credit protection, legacy marker migration, stale-lock recovery, and bounded continuation checks.
- `rafter secrets .`: no hardcoded secrets detected (exit 0; Betterleaks output-version warning noted).

## Required security scan

- `rafter run` fast was attempted. It was blocked before scanning because the account has used 16/16 fast scans; quota resets 2026-11-23.
- Paid Plus was not run because it consumes credits and requires explicit approval.
- Manual OWASP web/API/LLM evidence and corrected findings are recorded in `BRANDDAD_SECURITY_REVIEW.md`.

## Verified connected state

- FormWise organization: BrandDad Social (`branddad-social`), owner role.
- Published App: BrandDad AI Studio.
- Published Agent: BrandDad Sales Assistant.
- Published execution flows include Copy and Reviews, SEO Action Planner, Website Improvement Planner, Directory Listing Studio, Directory Finder, LinkedIn Profile Analyzer, Review Dispute Assistant, Campaign Brief Builder, and Marketing Strategy Builder.
- Tier plans confirmed: Starter 50/$14.99, Growth 150/$29.99, Pro 400/$49.99, Business Essentials 75/$29.99, AI Lead Assistant 80/$19.99, AI Local SEO 60/$19.99, AI Social Growth 150/$29.99.

## Not yet tested

- Live WordPress route execution, cron/Action Scheduler processing, and FormWise response parsing with a new paid provider run.
- Cross-host account creation and cookie handoff on staging.
- Email-verification filter integration on both hosts.
- Real trial purchase, provisioning, renewal, failed renewal, paid-through cancellation, refund, and billing portal.
- Real Featured Woo purchase and placement handoff.
- Follow-up sending, social publishing, WhatsApp, SEO publishing, music delivery, and video generation.
- Real mail transport acceptance, one-click unsubscribe, retry, crash/uncertain-delivery review, and converted-customer stop behavior.
- Browser-session REST nonce and Directory-free access remain untested. Hosted staging now proves cross-account denial, optimistic version conflict, active/stale lock behavior, safe status output, and completed/failed job reporting isolation.
- Real Woo partial/full refund hooks, UTC month-boundary behavior, and bounded 5,000-event truncation remain untested. Hosted staging now proves event deduplication, privacy allowlisting, subscriber capability denial, and gross/refund/net aggregate deltas.
- Lead capture owned-origin/rate behavior, concurrent deduplication, verified stage hook ordering, admin authorization, opt-out propagation, and scheduled anonymization in WordPress staging.
- Website-monitor configuration authorization, public/private DNS cases, safe real fetch, redirect/timeout behavior, concurrent/manual scheduling, plan loss, profile URL change, batch cursor, and actual value-report counters on WordPress staging.
- Customer inquiry backend staging now proves owned-origin/body/rate behavior, opaque-reference ambiguity rejection, consent validation, idempotent replay, private ownership, cross-account denial, active/stale locks, optimistic versions, status workflow, retention anonymization, actual report deltas, and fixture/state cleanup. Cursor's real browser REST nonce, final customer-facing copy, and approved notification delivery remain untested.

## 2026-11-19 customer-inquiry staging acceptance preparation

- `node tests/customer-inquiries-contract.mjs`: passed 26 assertions, including internal plan-candidate revalidation.
- `node tests/staging-customer-inquiries-acceptance-contract.mjs`: passed 56 assertions covering exact-host/one-time execution, fixture identity, owned origin, size/rate limits, consent, idempotency, private ownership, cross-account 404, locks/versions, reports, retention, narrowly marked cleanup, state restoration, and no messaging/provider/payment surface.
- Full suite: 27/27 Node contract files passed.
- PHP compatibility: 3/3 passed.
- PHP syntax: 27/27 deployment modules passed.
- `git diff --check`: passed.
- `rafter secrets .`: passed with no detected secrets.
- `rafter run` fast: attempted and quota-blocked at 16/16; no paid Plus scan was used.

## 2026-11-20 Make lead-capture staging filter

- Pre-edit provider read: scenario `5029212` was `paused`, unlocked, had zero incomplete executions, and contained only the custom webhook followed by the staging datastore module.
- Atomic Make edit: `filter_set` succeeded on datastore module `2` with two OR branches. Each branch requires the exact `staging` environment and an existing trimmed email; one accepts normalized consent `true`, the other accepts normalized consent `yes`.
- Post-edit provider read: the filter name and all six conditions match the intended contract; scenario status remains `paused`; no connection or activation was added.
- Negative side-effect check: no webhook call, queued-payload inspection, datastore write, notification, external follow-up, payment, order, subscription, customer, or production WordPress change occurred.
- Manual OWASP API review: input is still untrusted; explicit policy gating now fails closed, but authentication, schema/length limits, rate limiting, replay protection, retention cleanup, and approved notification delivery remain blockers.
- `git diff --check` passed. `rafter secrets .` found no secrets. Required remote `rafter run` was attempted and blocked by the 16/16 fast-scan allowance; paid Plus was not used.
- Scheduler recurrence recheck: workflow `425477211` remains active on default branch `main`, but GitHub still lists only manual run `44830790644`; no automatic `event=schedule` run exists yet.
- Existing authoritative-system check: `node tests/sales-lead-pipeline-contract.mjs` passed all 29 security/lifecycle assertions and `php -l deploy/bds-sales-lead-pipeline-mu.php` reported no syntax errors. This supports keeping WordPress as the lead source of truth and Make as a future downstream lane only.
- Retirement provider read-back: scenarios `5029212` and `5028724` each contain only module `3` (`scenario-service:StartSubscenario`), report on-demand kind and schedule, remain paused, and have zero connections and zero incomplete executions. Webhook inspection now returns `INVALID_ARGUMENT`, which is expected because the webhook modules no longer exist.
- Queue/privacy result: two queued items on v2 and three on the older prototype were removed with their unauthenticated hooks without body inspection, execution, datastore insertion, email, or follow-up. Data store `99354` reports `total=0`.
- Control-plane failure checks: an unsupported generic hook rotation was refused atomically; a rate-limited scheduling save was refused before a later bounded retry succeeded. Provider read-back verifies no partial configuration remained.
- GitHub schedule registration reset: the official API accepted disable then enable for workflow `425477211`; final state is `active`. This is configuration evidence only until an automatic run appears.
- Security review: the retired Make shells expose no public webhook, accept no input, have no connection, and cannot write the datastore. The authoritative WordPress route retains its tested origin, size, schema, rate, privacy, role, retention, and unsubscribe controls. `git diff --check` passed and `rafter secrets .` found no secrets. Required remote `rafter run` was attempted again and remains quota-blocked at 16/16; no paid Plus scan was used.
- Scheduler reset observation: after the API disable/enable read back `state=active`, no `event=schedule` run appeared at or after the next two `*/5` boundaries. The run list still contains only successful `workflow_dispatch` runs `44830790644` and `44838570524`. No third manual production request was sent.
- Hosted runtime acceptance: version 0.1.0, `complete=true`, 34/34 allowlisted checks true, no error codes.
- Hosted cleanup evidence: fixture posts deleted, form metadata restored, and isolated rate counters restored all returned true.
- Hosted smoke: staging homepage/product HTTP 200 with `noindex`; sales catalog HTTP 200; account status, value report, AI tools, onboarding, monitor configuration, inquiry form/inbox, analytics, and finance contribution returned HTTP 401 anonymously; the production acceptance route returned HTTP 404.

## 2026-11-19 sales-lead staging acceptance preparation

- `node tests/sales-lead-pipeline-contract.mjs`: passed 29 assertions, including the bounded restrict-only retention candidate-id seam.
- `node tests/staging-sales-lead-acceptance-contract.mjs`: passed 50 assertions covering exact-host execution, fixture collision refusal, origins/body/rates, validation, verified/unverified consent, deduplication, private records, response privacy, recommendation ownership, account mismatch, lifecycle, unsubscribe suppression, fixture-only retention, exact-row consent restoration, narrow deletion, and absence of messaging/provider/payment surfaces.
- Full suite: 28/28 Node contract files passed.
- PHP compatibility: 3/3 passed.
- PHP syntax: 28/28 deployment modules passed.
- `git diff --check`: passed.
- `rafter secrets .`: passed with no detected secrets.
- `rafter run` fast: attempted and quota-blocked at 16/16; no paid Plus scan was used.
- Hosted run v0.1.0 failed closed on a pre-existing fixture-user lead collision before mutation. The v0.1.1 helper was revised to leave that record untouched and use a second anonymous marked fixture for consent/lifecycle acceptance.
- Corrected hosted runtime: version 0.1.1, `complete=true`, 34/34 allowlisted checks true, no error codes.
- Hosted cleanup/restoration: fixture post deletion, exact consent-row restoration, isolated rate-counter restoration, and original server/user context all returned true.
- Hosted smoke: staging home/product HTTP 200 with `noindex`; catalog HTTP 200; protected lead/account/report/AI/onboarding/finance routes HTTP 401 anonymously; public capture returned to HTTP 503 `lead_capture_disabled`; production acceptance route HTTP 404.

## 2026-11-19 follow-up consent staging preparation

- `node tests/staging-followup-consent-acceptance-contract.mjs`: passed 40 assertions covering exact-host/one-time behavior, default-off worker, fixture collision refusal, eligibility, token/hash privacy, safe URLs, compare-and-set updates, opt-out persistence, reactivation blocking, disabled no-op, rate bounds, exact restoration, and no send/provider/payment surface.
- Full suite: 29/29 Node contract files passed.
- PHP compatibility: 3/3 passed.
- PHP syntax: 29/29 deployment modules passed.
- `git diff --check`: passed.
- `rafter secrets .`: passed with no detected secrets.
- `rafter run` fast: attempted and quota-blocked at 16/16; no paid Plus scan was used.
- Hosted runtime acceptance: version 0.1.0, `complete=true`, 26/26 allowlisted checks true, no error codes.
- Hosted restoration: exact fake consent row, isolated unsubscribe-rate transient, server IP value, and current-user context all returned true.
- Hosted smoke: staging home/product HTTP 200 with `noindex`; catalog HTTP 200; protected lead/account/report/AI routes HTTP 401 anonymously; lead capture HTTP 503 `lead_capture_disabled`; production acceptance route HTTP 404.
- Real mail transport and the public unsubscribe HTTP page remain deliberately outside this no-email test.

## 2026-11-20 live public/mobile and checkout evidence

- Live Social HTTP smoke: homepage, Services, website audit, AI Business Essentials, AI Lead Assistant, AI Social Growth, and AI Local SEO returned HTTP 200.
- Live product evidence: AI Business Essentials is product `53760` at `$29.99/month` with a disclosed seven-day trial; the four priority product pages expose product images and add-to-cart controls.
- Live checkout UI: `$0.00` due today, subscription renewal line, Stripe card method, terms checkbox, and Place order control rendered. No order/payment was submitted, so this is checkout-interface evidence only.
- Directory 390px mobile: compact header, mobile menu control, Playa del Carmen-first nearby listings, one non-overlapping Directory Concierge launcher, contained dialog, light-on-dark logo, and organized footer passed visual/DOM inspection.
- Restaurant picker: after its bounded asynchronous load, country options included Mexico, United Arab Emirates, and United States and the initial business list remained hidden. The live `0.68.13` client still contains a false `items.length === 100` pagination assumption while the server enforces 12 records/page.
- Directory Experience `0.68.14`: `php -l` passed; `node --check assets/category-results.js` passed; homepage conversion, navigation, and ticketing contract suites passed; `rafter secrets .` passed; manual OWASP/ASVS review found no release blocker. `rafter run` could not run for the remote-less source tree, and the shared fast-scan quota remains exhausted.
- Package verification: 24 archive entries, 91,143 bytes, SHA-256 `3FBA1E17727221EAA6D7FAFF17CDDBC6353852DD51CF5CBFEB3CA29461223158`; re-extracted PHP/JavaScript syntax and behavior markers passed.
- Clean-homepage cache repair: HTTP 200, `x-bd-eco-cache: html-early`, exactly one `index, follow` robots tag, exactly one clean homepage canonical, and no `noindex` passed after the confirmed repair.
- Live 390px restaurant funnel: Mexico -> Quintana Roo -> Playa del Carmen -> All Restaurants navigated to `in_cat=191`, `in_loc=13445`, and `directory_type=food-beverage`; the results surface showed 36 matches and the correct Food & Beverage / Playa del Carmen / Restaurants active filters. The visible result cards were Playa del Carmen businesses.
- Directory route/link sweep: all 45 category links exposed by the homepage returned HTTP 200. The priority listings/events/jobs/classifieds/categories/locations/plans/claim/sign-in routes returned HTTP 200; add-listing and dashboard used the expected unauthenticated redirects.
- Directory responsive matrix: 320, 390, 768, 1024, and 1440 pixels had no horizontal overflow. Mobile navigation was visible through 768 pixels; desktop navigation was visible at 1024 and 1440 pixels.
- Social responsive matrix: 320, 390, 768, 1024, and 1440 pixels had no horizontal overflow and no broken images. The compact menu was visible at 320/390/768/1024 and the desktop navigation at 1440. Exactly one fixed `Ask BrandDad AI` launcher was detected at every width.
- Social priority links: the Business Essentials trial, Lead Assistant, Social Growth, Local SEO, free website check, and Services destinations were present, visible, and correct. The broader same-ecosystem homepage link sweep checked 93 unique Social/Directory destinations; all 93 returned HTTP 200.
- Directory location timing: after a single approximately 18-second cold `all-locations` response, warmed and parallel clean requests returned roughly 1.3-2.0 second TTFB and 1.65-2.1 second total time. The slow cold response was not reproducible.
- Final local revalidation: all 38 current Node contract files passed, all 3 PHP compatibility tests passed, and all 43 deployment PHP files passed `php -l`. The prepared Directory `0.68.14` archive remained 91,143 bytes with SHA-256 `3FBA1E17727221EAA6D7FAFF17CDDBC6353852DD51CF5CBFEB3CA29461223158`.
- Live `0.68.14` activation: WordPress compared current `0.68.13` with uploaded `0.68.14`, replaced the current plugin after explicit confirmation, and reported success. Public assets subsequently advertised `0.68.14`; the live category script contained the corrected total-page loop and did not contain the legacy 100-item stop condition.
- Post-release 390px acceptance: the restaurant picker resolved Mexico -> Quintana Roo -> Playa del Carmen -> All Restaurants, navigated to `in_cat=191`, `in_loc=13445`, and `directory_type=food-beverage`, showed 36 matching businesses, rendered Playa del Carmen card locations, retained one Directory Concierge launcher, and had no horizontal overflow.
- Free-listing public audit: anonymous `/add-listing/` redirected to registration; the registration page had no visible $6.99 requirement and stated that one basic listing is free; the plan page's Basic/Free listing action targeted `/add-listing/` and described the old Activation price as optional. `free-listing-test.php`, `directory-contact-test.php`, and `sales-help-test.php` passed. A new non-admin account/submission was not created in this read-only audit.
- Not yet evidenced: real Stripe payment, provisioning, renewal, refund, cancellation, or billing portal.

## 2026-11-19 hosted account-link cryptographic staging acceptance

- `node tests/account-link-contract.mjs`: passed 22 assertions, including normalization and fixed-host revalidation of the internal test seam.
- `node tests/staging-account-link-acceptance-contract.mjs`: passed 43 assertions covering exact-host/one-time behavior, acceptance-only secret, fixture verification, signed claims, five-minute lifetime, audience, tamper/time/purpose/email/issuer failures, malformed/oversized input, atomic replay, existing-user resolution, conflicting-session detection, safe form output, audit privacy/restoration, and absence of cookie/account/network/payment surfaces.
- Full suite: 30/30 Node contract files passed.
- PHP compatibility: 3/3 passed.
- PHP syntax: 30/30 deployment modules passed.
- `git diff --check`: passed.
- `rafter secrets .`: passed with no detected secrets.
- `rafter run` fast: attempted and quota-blocked at 16/16; no paid Plus scan was used.
- Hosted runtime acceptance: version 0.1.0, `complete=true`, 30/30 allowlisted checks true, and no error codes.
- Hosted restoration: replay claim cleanup, prior audit option restoration, and original user/server context restoration all returned true; the helper made no cross-host request.
- Post-run staging smoke: homepage and Business Essentials product HTTP 200 with `noindex`; public sales catalog HTTP 200; account status, value report, AI tools, onboarding, monitoring, inquiry form/inbox, analytics, and finance routes HTTP 401 anonymously.
- Production acceptance route returned HTTP 404. No account, auth cookie, user metadata, redirect, message, provider call, order, subscription, payment, or production mutation occurred.
- Real cross-host consume/cookie/session remains a later two-host test requiring isolated Directory staging and a shared production-like secret.

## 2026-11-20 final five-batch verification

- Live route smoke: priority Social and Directory public routes returned HTTP 200. The clean Playa del Carmen category result completed in under one second during the final pass. The location index also returned HTTP 200, with variable generation time from roughly 1.3 seconds warm to 18 seconds on one cold cache-bypass request.
- Live Stripe read-only audit: gateway enabled; live account connected; payments enabled; payouts enabled; webhook enabled; test mode off and unavailable until a test account is connected; no live webhook received since monitoring began on 2026-11-08; no transaction submitted.
- Stripe methods read-only audit: card, Klarna, Afterpay, Cash App Pay, Apple Pay/Google Pay, Link, and Amazon Pay selected; saved cards enabled; delayed capture disabled; debug logging disabled. Optimized Checkout Suite is unavailable and recommends reconnecting the account or contacting Stripe support.
- FormWise inventory audit: runs are available; three published BrandDad sales/directory agents, one published AI Studio app, 13 published operational flows, two restricted portals, and eight approved tier plans are present. Video Director is unpublished. No credit-spending execution was run.
- Safety result: no payment, account creation, message, social post, provider run, settings mutation, or customer-data mutation occurred. The temporary homepage repair file was not deleted because deletion requires a separate action-time confirmation.

## 2026-11-20 live scheduler evidence

- WooCommerce Action Scheduler inventory: 4,990 total, 771 complete, 4,166 failed, 53 pending, and 52 past due.
- The Action Scheduler UI reports that no additional queues will begin and WordPress Site Health reports `action_scheduler_run_queue` as a failed scheduled event.
- Past-due pages were reviewed without mutation. They include WooCommerce cache/session/log/sales/import maintenance, Stripe cache cleanup, recurring Action Scheduler maintenance, and WP Swings subscription renewal/expiration hooks.
- The authenticated StartHost Scheduled Tasks page reports: `You currently don't have any scheduled tasks.`
- StartHost's active PHP selection is PHP 8.4 and its interpreter list maps PHP 8.4 to `/usr/bin/php84`.
- The exact proposed command and five-minute schedule are documented in `BRANDDAD_SCHEDULER_RECOVERY_DESIGN.md`. No command was tested or saved, no cron batch was triggered, and no payment/subscription/customer data was changed.
- StartHost accepted a combined files-and-databases backup request and displayed a queued job plus two success notifications. Completion could not be independently confirmed because the control-panel session expired on refresh; do not treat the backup as recoverable until its completed row/download is visible after reauthentication.
- The hosting session became available again and the same backup row remained visible with status `Backup job started` and `Please wait...`; it is a verified live job, not a missing or terminal job.
- Pending-action search for `wps_sfw` returned exactly two items: `wps_sfw_expired_renewal_subscription` and `wps_sfw_create_renewal_order_schedule`. Both are recurring every 30 minutes, overdue since 2026-11-13, and show no action arguments. No Run, Cancel, bulk action, or queue execution control was used.
- The StartHost scheduler form now visibly contains `/usr/bin/php84 -f /home/sites/41a/c/c0a8249376/public_html/branddad.social/wp-cron.php` and `Every 5 minutes`, with other cadence fields at `(Any)`. Save Task remains untouched and the task list remains empty.
- StartHost subsequently reported `Backup job completed` for the combined `Databases, Files` snapshot and exposed its Download action. A final DOM read confirmed command `/usr/bin/php84 -f /home/sites/41a/c/c0a8249376/public_html/branddad.social/wp-cron.php`, minutes `*/5`, every other cadence field `(Any)`, blank email, and zero saved tasks.
- Re-reviewed both deployed guard sources. During `DOING_CRON`/WP-CLI the emergency guard does not apply zero-batch filters, and the hard guard explicitly applies batch size 5, concurrency 1, and a 20-second time limit. On ordinary requests, both guards continue blocking queue draining and async workers.
- The exact scheduler save received action-time approval and was attempted once. StartHost returned `Cron failed to add`; the task list remained empty and the form reset. The queue baseline did not change, so no cron execution or financial/customer side effect occurred.
- The package overview reports `Malware Detected On This Package`. StartHost/20i documents that scheduled tasks are disabled on malware-flagged packages. The visible report is limited to five generated Directory cache HTML false positives and one historical quarantined one-shot already outside the web root.
- Fresh package scan `24662257` completed at 2026-11-20 17:01:32 UTC with return code 1. It reported six generated Directory cache HTML objects and the known quarantined maintenance file; the package restriction therefore remains active. During the scan, `https://branddad.social/`, `/services/`, `/product/ai-business-essentials/`, `/checkout/`, and `https://directory.branddad.social/` each returned HTTP 200.
- Read-only inspection of all six reported cache objects returned HTTP 200 and canonical pages for a claim guide, plan page, two products, one gift-card product, and one listing. The HTML contained no executable `eval(`, `document.write`, `atob(`, `base64_decode`, `fromCharCode`, or `unescape(` patterns. This evidence supports the existing false-positive classification without deleting or altering production files.
- Post-blocker availability recheck: Social homepage, Services, Business Essentials, Directory homepage, all categories, and all listings returned HTTP 200. The first all-listings request was a 31-second cold outlier; three immediate follow-ups returned in 647-765 ms with `x-bd-eco-cache: html-early` and `x-bd-dir-cdn: early-1.1.61`, so availability is intact but the known cold-bootstrap performance watch remains.
- Authenticated Action Scheduler recheck after the failed host-scheduler save remained exactly 4,990 total / 771 complete / 4,166 failed / 53 pending / 52 past due. The UI still reports zero queues and no additional queue will begin. This confirms that no alternate runner processed the overdue work and that the StartHost false-positive clearance is still the authoritative scheduler unblock.

## 2026-11-20 post-ticket independent revalidation

- Re-ran the complete local backend suite from the clean integration branch: all 38 Node contract files passed, all 43 deployment PHP files passed `php -l`, and all 3 PHP compatibility tests passed.
- Rechecked twelve public priority surfaces with redirect following and a 45-second ceiling. Social homepage, Services, website check, Business Essentials, Lead Assistant, Social Growth, Local SEO, and the empty-cart checkout path all returned HTTP 200; the empty checkout redirected to the cart as expected. Directory homepage, categories, listings, and the restaurant category returned HTTP 200.
- Observed total request times were roughly 0.91-1.55 seconds on the Social/Directory cached priority routes, 2.39 seconds on the restaurant category, and 3.34 seconds on all categories. No public outage was observed.
- This pass made no payment, account, subscription, provider, message, listing, or customer-data change. It does not supersede the unresolved StartHost scheduled-task blocker or substitute for real payment/provider acceptance.

## 2026-11-20 production-package refresh

- Built `branddad-social-backend-2026-11-20.zip` with 16 production MU plugins; archive SHA-256 `52507864CBAD16F45391E8FEC7A0C4F352E0450CE7748C41F48D06EF3957DC05`.
- Built `branddad-directory-backend-2026-11-20.zip` with 7 production MU plugins; archive SHA-256 `2DC0C499C0C6276B269B045437CDF5DCBA48F5B1F2C69056CFE4384D07394B93`.
- Expanded both archives into isolated temporary verification folders. All package-local checksums matched, all 23 packaged PHP files passed syntax checks, and neither archive contained a staging helper, acceptance helper, or temporary repair utility.
- The tracked production-source checksum contract passed, `git diff --check` passed, and `rafter secrets .` found no secrets. The Betterleaks-version warning remains informational and does not report a secret.

## 2026-11-20 live saleable-catalog reconciliation

- Checked all seven `launch_candidate` product slugs from the authoritative catalog: AI Studio Starter, Growth, and Pro; AI Local SEO; AI Lead Assistant; AI Social Growth; and AI Business Essentials.
- Checked all three preserved `live_legacy` product slugs: AI Sales Assistant Basic, AI Sales Assistant Plus, and Custom Business Automation.
- All ten canonical product URLs returned HTTP 200 without a cross-product redirect. Each page displayed the catalog price, a product image, and its primary add-to-cart/subscribe control. AI Business Essentials displayed its seven-day trial disclosure.
- This is public catalog/presentation evidence only. It does not prove gateway charging, subscription provisioning, renewal, refund, cancellation, or provider delivery.
- Negative sale gate: all 21 canonical slugs marked `staged` or `quote_only` returned HTTP 404 and rendered neither the requested product title nor the requested Product schema. No unfinished catalog offer was directly saleable at its reserved product URL.
- Anonymous cart handoff: AI Studio Starter/Growth/Pro, AI Local SEO, AI Lead Assistant, AI Social Growth, and AI Business Essentials each reached the canonical checkout with HTTP 200 in a separate ephemeral session. Every checkout showed the exact product name, authoritative price, and Place order control; Essentials also showed the seven-day trial disclosure. No order or payment was submitted.
- White-label audit: six representative Social/Directory assistant surfaces had no visible `FormWise` text and no `builder.formwise.ai` reference. Each used the BrandDad-owned `ai.branddad.social` host. Internal provider-derived CSS class names do not render as customer-facing copy.

## 2026-11-20 staging-versus-production API inventory

- Live Social returned HTTP 404 `rest_no_route` for every reviewed `branddad/v1` backend route, including customer status, reports, onboarding, AI jobs, inquiries, monitoring, administrative analytics/finance/leads, and the public sales catalog. This is fail-closed but proves the reviewed backend package is not deployed to production.
- Isolated Social staging returned HTTP 401 `rest_forbidden` for every customer/admin route when anonymous and HTTP 200 for the intentionally public sales catalog. Production staging-only acceptance routes are absent from live Social.
- Sixteen of seventeen isolated staging acceptance reports currently return `complete=true`, including auth, account-link, AI failure, customer API, customer inquiries, consent, product fixtures, lifecycle, provider cost, sales assistant, sales lead, subscription lifecycle, Woo refund, monitoring, reporting-stage, and lifecycle-boundary acceptance.
- The only incomplete staging report is the documented 5,000-event lifecycle truncation stress test. It reports `host_execution_ceiling`, confirms cleanup/summary restoration/state removal, and is not evidence of a customer or financial failure.

## 2026-11-20 Directory Featured billing acceptance preparation

- Added an exact-host, one-time staging helper that exercises the production Featured billing adapter with marked synthetic Woo products, orders, a WPS subscription, an isolated listing, and an isolated grant option. It invokes no gateway, provider, email, webhook, or external request.
- Acceptance covers all four approved SKU/price/level mappings, wrong-price and wrong-interval denial, exact owner and geography enforcement, first activation, replay idempotency, renewal of the same grant, failed-renewal pause, paid-through cancellation, expiry, refund revocation, and capacity denial.
- Cleanup verifies exact ids, types, markers, owner, and product identity before deletion, then restores the isolated grant option, request globals, and current user. The public evidence route exposes only 28 allowlisted booleans and bounded error codes.
- Local validation passed: all 39 Node contract files, all 44 deployment PHP syntax checks, all 3 PHP compatibility tests, the staging checksum contract, and `git diff --check`.
- `rafter secrets .` found no secrets. Required `rafter run` fast was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- Hosted `v1` failed closed before fixtures because the production Featured adapter was missing: `production_dependencies_loaded=false`, bounded error `featured_acceptance_dependencies_unavailable`, and all four cleanup/restoration checks true.
- Uploaded the exact reviewed adapter plus dependency-corrected helper `0.1.1` with a fresh immutable `v2` report key. Hosted `v2` returned HTTP 200, `complete=true`, all 28 checks true, and no error codes. It proved exact four-lane price/level readiness, wrong price/interval denial, ownership/geography, no-gateway order state, activation/replay/renewal/failure/cancellation/expiry/refund/capacity behavior, and fixture/state/context cleanup.
- A second read returned the stored complete report rather than rerunning. Staging home and Business Essentials returned HTTP 200; the production acceptance route returned HTTP 404. No gateway, provider, email, webhook, real payment, production record, or customer record was touched.

## 2026-11-20 external dependency diagnosis

- StartHost ticket `898665003` now contains a provider reply: StartHost says it cannot remove the false-positive flag and suggests ignoring it. No reply was sent. Since the exact scheduled task still returns `Cron failed to add`, this does not satisfy the scheduler launch gate.
- Woo Stripe reports payments, payouts, and webhook configuration enabled, but the connection dialog marks the account `Incomplete`, sync disabled, and explains that stored account keys exist but their origin cannot be verified. The plugin requires reconnecting the account.
- The configured endpoint is `https://branddad.social/?wc-api=wc_stripe`. A public GET returned HTTP 200 and an unsigned empty JSON POST returned HTTP 204, confirming edge/origin reachability without a valid Stripe event. No order, payment, subscription, setting, or customer record changed.

## 2026-11-20 external cron fallback research

- Reviewed current official 20i documentation confirming that the provider's Scheduled Tasks are the supported real-cron path for WordPress. This remains the preferred implementation.
- Reviewed current official Cloudflare documentation: Cron Triggers are intended for periodic work; the free plan currently allows five Cron Triggers and 100,000 requests per day; trigger changes can take up to 15 minutes to propagate; recent Cron Events are available for review.
- Reviewed current official GitHub documentation: scheduled workflows run from the default branch with a minimum five-minute interval, can be delayed or dropped during high load, and are disabled on public repositories after 60 days without activity. Standard GitHub-hosted Actions are free for public repositories. The existing `branddadsocial/t1000` repository is publicly reachable.
- Produced a fail-closed fallback design using one exact HTTPS `wp-cron.php` request per interval, no query string or secret, no third-party dependency, no same-invocation retry, and no simultaneous runner. No workflow, Worker, scheduled request, queue mutation, payment, subscription change, message, or customer-data change occurred.

## 2026-11-20 Partnero capability audit

- Verified against Partnero's current official REST API, transaction, authentication, and webhook documentation: program-scoped server Bearer authentication; unique transaction keys; create/delete/archive/restore semantics; commission calculation from program rules; HMAC-SHA256 `Signature` over the raw webhook body; five-second acknowledgement guidance; and retried delivery requiring idempotency.
- Official documentation identifies permanent transaction deletion as the commission-reversal path for full refunds/chargebacks and archive/restore as the reversible affiliate-program alternative. No documented partial-amount transaction update was found, so partial refunds remain reconciliation-only rather than an automated full reversal.
- This was read-only provider research. No Partnero login, API key, webhook, customer, transaction, commission, referral, payout, or program setting was created, read from the account, or changed.

## 2026-11-20 FormWise content and portal-access audit

- Read-back covered the organization identity/scopes, six authored agents, 15 authored flows, the AI Studio app, two portals, eight tier plans, ten user groups and their grants, four public share configurations, three knowledgebases, the complete Social sales knowledge pages, and the failed Services website source.
- No agent, flow, app, draft, test case, or website crawl was run; provider search reported `chargedCredits: 0` and no FormWise execution credit was spent.
- The Directory Concierge is correctly Directory-only and attached to the live Finder; the older Directory Assistant remains separately published without a flow. All three public agents use `ai.branddad.social`, but their share records permit direct opening and embedding anywhere. The AI Studio app is correctly restricted to `https://branddad.social` and refuses direct opening.
- The Sales knowledge is factual for the three preserved live-legacy Woo offers but was last verified November 9 and does not lead with the seven current launch candidates or AI Business Essentials. The Services source is `failed`, `searchable=false`, with `RAG processing failed`.
- Secure-design review documented RBAC boundaries, exact-provider-id selection, no public mutation path, no member/credit/execution change, idempotency, read-back, and exact admission rollback.
- Idempotent portal admission returned `created=true` for Business Essentials, AI Lead Assistant, AI Local SEO, and AI Social Growth. Post-write read-back reports `loginRequired=true`, `door=restricted`, `storefrontId=null`, and nine admitted groups including all four exact new plan groups.
- No end user, membership, plan, group grant, credit, agent/flow/app content, share setting, payment, order, or customer record changed.
- Local `rafter secrets .` reported no secrets. Required remote `rafter run` was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- Live-reference smoke: Directory homepage, All Categories, All Listings, and Restaurants each returned HTTP 200 and contained only the Concierge UUID/owned tool URL; the older Directory Assistant UUID was absent. Social homepage and Services returned HTTP 200 and contained only the Sales Assistant UUID/owned tool URL.

## 2026-11-20 Make automation audit

- Connected environment resolved to one organization/team and returned 11 scenarios without a pagination warning.
- Effective status read-back: nine paused scenarios, one error scenario, and no active-status scenario. All reported zero incomplete executions.
- Video Autopost structural read: one custom-webhook module, no connection, no generation/render/publish module.
- FormWise Lead Capture v2 structural read: custom webhook followed by a deduplicated datastore insert; no notification, qualification, follow-up, appointment, checkout, or CRM-delivery module was present.
- LinkedIn structural reads: both paused; OpenAI/LinkedIn connections report healthy. One exposes a stale past next-run timestamp despite `isActive=true` in the list.
- Facebook structural read: scenario status error; Facebook connection expiring; OpenAI connection healthy.
- Execution-history requests returned `INVALID_ARGUMENT` for scenarios with historical usage, so no recent-runtime success claim was made.
- No scenario was run, replayed, activated, deactivated, edited, created, or deleted; no webhook, connection, social post, message, lead, payment, or customer record changed.

## 2026-11-20 Directory intermittent database outage

- Sequential low-rate checks reproduced HTTP 500 on the Directory homepage, Add Listing, All Categories, All Listings, and `/wp-json/`; the response identified a WordPress database connection failure.
- Social homepage and Services remained HTTP 200 during the Directory failures.
- StartHost's read-only database inventory showed the Directory database present at approximately 131.04 MB of 1,024 MB.
- Hosting access logs showed Directory HTTP 200 and 500 responses interleaved within seconds across pages, REST routes, AJAX, and cron traffic.
- Error-log searches for `mysqli` and `connections` returned no matching diagnostic entry; no unsupported root-cause claim was made.
- A final low-rate recheck returned HTTP 200 for Directory homepage, Directory REST index, Social homepage, and Social Services. No repair action preceded recovery.
- A short follow-up observation collected three additional homepage and REST samples four seconds apart. All six requests returned HTTP 200; homepage response times were 551-788 ms and REST response times were 1,694-2,143 ms. This confirms immediate recovery but is not long enough to close the hosting-stability gate.
- A subsequent low-rate seven-route sample returned HTTP 200 for the homepage, REST index, All Categories, All Listings, Restaurants, Playa del Carmen location, and Add Listing surfaces. Observed response times were 593-2,976 ms. The broader surface is presently available, but the provider root-cause/stability gate remains open.
- A further five-cycle observation sampled the homepage, REST index, and Restaurants route ten seconds apart. All 15 requests returned HTTP 200 with no failures; the slowest response was 2,118 ms. This supplies short-window recovery evidence only and does not replace StartHost database telemetry.
- No database password, table, database, file, plugin, cache, customer record, or hosting setting was changed.

## 2026-11-20 post-incident local regression

- All 39 current Node contract files passed.
- All 38 current PHP files under `deploy/` passed `php -l`.
- All three PHP compatibility suites passed: customer hub, Featured rotation, and revenue sections.
- The release checksum contract passed.
- This proves local package consistency only; it does not close the hosting database, scheduler, Stripe reconnect, FormWise execution, or protected production-deployment gates.

## 2026-11-20 mobile launch-path recheck

- Rechecked Social Home, Social Services, the canonical Directory claim page, and the homepage music-brief section at a real 390 x 844 CSS-pixel browser viewport.
- Social Home, Social Services, and the Directory claim page each reported `innerWidth=390`, `clientWidth=390`, `documentElement.scrollWidth=390`, and `body.scrollWidth=390`; no horizontal page overflow was present.
- The only negative-position Social elements were intentional honeypot fields and the translation helper's off-canvas internals. The Directory's negative-position navigation nodes were the intentionally closed mobile drawer and did not widen the document.
- The homepage music brief was present with `display:block`, a 350px section width, and 13 form controls. It did not widen the 390px document.

- Headless screenshots initially appeared visually cropped, but DOM geometry disproved page overflow; no production CSS change was made from a screenshot-only false signal.
- Mobile DOM inspection confirmed that trial links resolve to the authoritative AI Business Essentials product, service-comparison links remain on Services, free-listing links resolve to Add Listing, and the claim journey exposes the `bdd_intent=claim` listing finder.
- The music brief uses POST to WordPress's `admin-post.php`, contains the expected 13 controls, and requires package, customer name, email, and business description. No submission was made.
- A fresh redirect-following request pass returned HTTP 200 for Business Essentials, Services, the website audit, unauthenticated Add Listing registration handoff, claim-intent listings, Directory plans, and Playa del Carmen location. Add Listing correctly ended at `/registration/?bds_form=1` for the anonymous audit session.

## 2026-11-20 narrow/mobile-width follow-up

- Rechecked Social Home, Social Services, the free website-audit page, and the canonical Directory claim page at measured 320px and 430px CSS viewports.
- Every page reported `innerWidth`, `clientWidth`, document scroll width, and body scroll width equal to the requested viewport at both sizes; no horizontal page overflow was present.
- The only off-screen interactive nodes were intentional Social honeypot inputs and the Directory skip link. No visible link, button, input, select, textarea, or form extended beyond either viewport.
- This is browser emulation/DOM evidence, not a substitute for final physical iOS, Android, and touch-interaction acceptance.
- Post-audit local regression remained green: 39/39 Node contract files, 44/44 deployment PHP syntax checks, 3/3 PHP compatibility suites, the release checksum contract, and `git diff --check` passed.
- A low-rate public availability sweep returned HTTP 200 for Social Home, Services, AI Business Essentials, the website audit, Directory Home, all categories, all listings, and Playa del Carmen. Measured completion times were 0.86-2.57 seconds in that sample.

## 2026-11-20 rendered CTA and monitored-site follow-up

- At a 390px rendered viewport, inventoried 65 visible CTA/link instances across Social Home, Social Services, and Directory Home. These reduced to 34 unique origin/path destinations after repeated query variants were consolidated.
- All 34 unique public destinations returned HTTP 200 after redirect following. `all-locations` and the jobs listing path exceeded the bounded 12-second concurrent probe once, then returned HTTP 200 in 1.58 seconds and 0.73 seconds on immediate isolated retries.
- Verified that `branddad-ai-solutions`, the Home and Services `branddad-ai-plans` targets, `branddad-service-paths`, and `bdd-current-plans` exist in the destination HTML. Anonymous Add Listing and registration CTAs correctly end at the `bds_form=1` registration handoff.
- No button was clicked, no form was submitted, and no account, order, subscription, payment, message, or external provider execution was created.
- Rafter's current monitored-site records remain: Social scan succeeded 22/22 on 2026-11-07 with 0 critical, 2 warning, and 2 informational findings; Directory scan succeeded 21/21 on 2026-09-22 with 0 critical, 2 warning, and 4 informational findings. The current Sites API/CLI exposes counts only, not warning bodies, so the detailed-warning review remains open.

## 2026-11-20 rendered accessibility baseline

- At 390px, Social Home, Services, Business Essentials, the website audit, and the Directory claim page each had one visible H1. The sampled visible images had `alt` attributes, and the sampled visible form controls had associated label/ARIA naming.
- Directory Home's only H1 exists in the DOM but has a zero-by-zero rendered box inside a hidden legacy block. The visible current local-first page starts with `Start With Your Location` as an H2.
- The same zero-size legacy DOM includes the old `Brand gift cards under face value` homepage heading. It is not visually displayed, but it remains public markup and should be removed/consolidated by the Cursor-owned frontend rather than re-enabled.
- This is a focused structure/name/label baseline, not a complete WCAG 2.2 AA audit; automated contrast, keyboard-only, screen-reader, zoom, and physical-device testing remain separate.

## 2026-11-20 key CTA color-contrast follow-up

- Computed 390px styles found no blue-on-blue or dark-on-dark combination in the audited Social/Directory primary CTA classes.
- Social primary `.bdai-button` and `.bds-rev-button` styles render 16px/800 white text on `rgb(8,120,237)` (`#0878ED`), approximately 4.27:1. Because 16px bold text is below WCAG's large-text size threshold, this misses the 4.5:1 AA normal-text target.
- Social light/ghost variants use dark `#0B172A` on white (approximately 17.95:1), and quiet variants use white on `#071A33` (approximately 17.43:1); both pass.
- Directory primary buttons and the Concierge use white on `#0668D8` (approximately 5.28:1), and Directory secondary buttons use `#10243A` on white (approximately 15.72:1); both pass.
- Hover, focus, active, disabled, forced-colors, and actual screen-reader/keyboard behavior were not changed or fully audited in this read-only batch.

## 2026-11-20 public HTTPS and indexing recheck

- Plain HTTP for both `branddad.social` and `directory.branddad.social` returns HTTP 301 to the matching HTTPS origin. Redirect-following requests completed with TLS verification result `0` and HTTP 200.
- HSTS and `X-Frame-Options` headers were present on the audited final HTTPS responses.
- Social Home, Services, AI Business Essentials, Directory Home, all categories, and all listings each returned HTTP 200, exposed a canonical URL matching the audited clean path, and did not expose a production `noindex` robots meta directive.
- Both `/robots.txt` endpoints returned HTTP 200 as `text/plain`, and both `/wp-sitemap.xml` endpoints returned HTTP 200 as XML.
- A direct TLS handshake on both hosts returned the same currently valid Google Trust Services `WE1` certificate for `CN=branddad.social`, valid from 2026-10-16 02:42:52 UTC through 2027-01-14 03:40:44 UTC. Hostname verification also passed through the redirect-following HTTP checks.
- This bounded check confirms public redirect/certificate/canonical/indexing plumbing only; it does not replace a complete structured-data, hreflang, sitemap-child, CDN, automatic-renewal, or search-engine coverage audit.

## 2026-11-20 free website-audit mobile recheck

- The live page returned HTTP 200 at a measured 390px CSS viewport with `clientWidth`, document scroll width, and body scroll width all equal to 390; no horizontal page overflow was present.
- Public HTML contained no visible `FormWise` provider name, `builder.formwise.ai` reference, or old Basic $99 / Plus $199 / Custom Automation $499 pricing.
- The main audit action and current subscription actions use BrandDad blue/white styling; no green primary action was observed. A generic `green` source token was not a visible primary-control color.
- Live recommendation links resolve to Business Essentials, Lead Assistant, Local SEO, Social Growth, Services, Featured Advertising, and free Directory listing destinations.
- The visible EN/ES switch remains on the page. The large floating translator widget was not present in the audited viewport. No translation preference was changed.
- No audit, lead, email-consent, translation-vote, purchase, or Directory form was submitted.

## 2026-11-20 local frontend launch-fix verification

- Contrast sources now consistently use `#0668D8` for white-text primary actions in launch pages `0.5.24`, revenue sections `0.2.21`, customer surfaces `0.2.5`, Directory Featured `0.2.13`, and the local Codex bridge `0.1.1`. The new source contract rejects the former `#0878ED` token in those five files.
- `php -l` passed for all five affected source modules and the contrast contract. `brand-contrast-test.php`, `launch-funnel-test.php`, `lang-switch-test.php`, `launch-heading-test.php`, `revenue-sections-test.php`, `featured-rotation-test.php`, `customer-hub-test.php`, and `codex-bridge-test.php` passed.
- Directory QA repair `1.0.20` passed PHP syntax and `directory-contact-test.php`. The behavior fixture retains `<h1 id="bdd-title">Find What You Need Near You—Fast.</h1>`, demotes the retired `bds-wah__title` heading, emits the `1.0.20` marker, and verifies that the gift slider refuses non-marketplace routes and documents the homepage exclusion.
- `launch-heading-test.php`, `brand-contrast-test.php`, and the targeted `git diff --check` passed. The only diff-check message was Git's expected LF-to-CRLF working-copy warning.
- Manual OWASP review found no new access-control, injection, credential, network, payment, storage, or deletion path. Fixed selectors and a six-H1 scan cap bound the changed rewrite; the client fallback will not hide an ancestor containing the current hero.
- `rafter secrets deploy/bds-directory-qa-repair-mu.php` found no secrets. Required remote `rafter run` was attempted but blocked by the 16/16 fast-scan quota; paid Plus was not used.
- These are local source results in dirty pre-existing Cursor/recovery worktrees. They are not evidence that production changed. Deployment, cache purge, and live 320/390/430px validation remain required.
- Full Cursor revenue PHP-contract batch: 15 total, 15 passed, 0 failed. The only initial failure was the standalone sales-help harness omitting `add_filter()` before requiring the language module; adding that no-op WordPress stub made the existing test exercise complete and did not alter deployed code.

## 2026-11-20 production frontend deployment verification

- Cursor revenue PHP contracts: 15/15 passed after Sales Help `0.1.3` and Directory QA `1.0.21`.
- PHP syntax: Sales Help `0.1.3` and Directory QA `1.0.21` passed.
- Directory public-output fixtures prove nested demo-modal removal for `events`, `jobs-careers`, and `classifieds`, while `/dashboard/` and ordinary business browse remain byte-for-byte unchanged.
- `rafter secrets` found no secrets in either changed production file. Remote `rafter run --mode fast` was attempted twice and rejected only because the monthly quota is 16/16; paid Plus was not used.
- Live uncached Events: Concierge `true`, Sales Help `false`, refund modal `false`, removal marker `true`, H1 count `1`.
- Live uncached Jobs: Concierge `true`, Sales Help `false`, refund modal `false`, removal marker `true`, H1 count `1`.
- Live uncached Classifieds: Concierge `true`, Sales Help `false`, refund modal `false`, removal marker `true`, H1 count `1`.
- Live clean URLs remain stale and therefore fail the duplicate-helper/refund-modal acceptance until a supported cache refresh completes.

## 2026-11-20 post-deployment revenue and navigation smoke

- Social Home, Services, AI Business Essentials, AI Lead Assistant, AI Social Growth, AI Local SEO, and the free website-check page each returned HTTP 200.
- The four launch products displayed the approved prices and a real WooCommerce add-to-cart control: Business Essentials `$29.99` (`53760`), Lead Assistant `$19.99` (`53756`), Social Growth `$29.99` (`53757`), and Local SEO `$19.99` (`53755`). Business Essentials exposed its seven-day-trial copy. No cart, checkout, order, subscription, or payment was created.
- Directory Home, All Categories, All Listings, All Locations, Plans, Claim, Dashboard, Registration, and all four Add Listing entry points returned HTTP 200 after redirects.
- Anonymous Add Business, Post Event, Post Job, and Post Classified requests correctly ended at `/registration/?bds_form=1`; the protected Dashboard correctly ended at Sign In with a dashboard return URL.
- Exact widget selectors on fresh Directory Home, All Categories, All Listings, and Plans confirmed one Directory Concierge and no `#bds-sales-help` widget. The broader `bds-sales-help` source token remains in analytics code and is not evidence of a rendered second widget.
- Clean Directory Home and Events still select older cached HTML containing the retired helper, while fresh `nocache=1` output is correct. This narrows the remaining defect to the documented provider/origin cache layer.
- A final bounded availability pass returned HTTP 200 for Social Home, Social Services, Directory Home, the Directory REST index, and fresh Events. The uncached Events render took about 22.5 seconds while its stale clean cache returned in about 1.1 seconds, confirming a performance/cache issue rather than an outage.
- The clean Events response identified the selected object as `html-early-stale` with age `11806`. Reviewed cache code permits a browse object for 10,800 seconds plus a 3,600-second stale grace; after 14,400 seconds the early layer falls through to WordPress, whose output buffer rewrites the exact cache key on a successful 200 HTML render. No cache file was deleted or broadly purged.
- Follow-up clean-URL inspection returned HTTP 200 throughout but still found retired cached markup: Home was `html-early-stale` age `28478`, Events was `html-early-stale` age `11910`, Jobs was `html-early` age `3378`, and Classifieds was `html-early` age `4948`. Fresh `nocache=1` output remained correct, so the deployed application fix is intact and the remaining state is cache-only.
- The fixed-target refresh helper passed PHP syntax, its deterministic filesystem contract, manual OWASP/CWE review, and separate local secret scans. Mandatory remote `rafter run --mode fast` was attempted and rejected at 16/16; no production upload or cache mutation occurred.
- Natural-expiry observation proved the cache fallback: the clean Directory homepage advanced from `html-early-stale` age `28773` to the underlying `guest-home` render, and the next clean request returned `html-early` age `6`. The regenerated homepage returned HTTP 200, one H1, one Directory Concierge, and no `#bds-sales-help` widget. No production file or cache object was manually changed.
- Read-only authenticated File Manager inspection confirmed the exact reviewed Events (`96bb...ca1`), Jobs (`7f55...c18`), and Classifieds (`e494...3f4`) cache basenames exist in the production generated-HTML directory. No file was edited, renamed, deleted, or saved; a read-only editor attempt navigated away without exposing content.
- Post-helper full local regression passed on the remote-tracked branch: 39/39 Node contracts, 39/39 current top-level deployment PHP syntax checks, and 4/4 standalone PHP tests. This includes the release checksum contract and the targeted cache-refresh filesystem test.
- Version `0.1.1` narrowed the recoverable cache helper from four objects to the three production-confirmed stale specialty objects and excluded the already-healthy homepage. The targeted helper/test syntax, deterministic filesystem contract, both file-specific secret scans, `git diff --check`, and the complete 39/39 Node + 39/39 deployment PHP lint + 4/4 standalone PHP regression all passed after the scope reduction.
- The exact version `0.1.1` branch was pushed as `46d7809`; the mandatory remote fast scan was then attempted against `branddadsocial/t1000` / `codex/final-backend-20261119` and rejected solely because the account remains at 16/16 fast scans until `2026-11-23T20:03:31.846212+00:00`. No unreviewed production deployment was made and paid Plus was not used.
- GitHub's API reports the new `BrandDad Social wp-cron` workflow (`425477211`) as active on default branch `main`, with repository Actions enabled and default workflow permissions set to read. The run list was still empty after registration; this proves configuration presence only, not successful scheduling or WordPress task execution.
- A new bounded stability sample completed 15/15 successful HTTP 200 requests with no database-error body: five Social home requests averaged 1,099 ms, five Directory home requests averaged 810 ms, and five Directory REST-index requests averaged 1,763 ms. This is positive current evidence but does not erase the earlier interleaved database failures or replace host telemetry.
- Free read-only FormWise inventory confirmed the dedicated `BrandDad Directory Concierge` is published with the grounded Directory Finder flow, location-first behavior, explicit Directory-only brand boundary, and no Social sales instructions. The older `BrandDad Directory Assistant` remains separately published. `get_share_links` reports both Directory agents and the Social Sales Assistant as `embedding: anywhere` with direct links enabled; no credits or executions were used.
- Live source inspection of Directory home, Events, Jobs, and the Classifieds all-listings route found exactly one reference to Concierge agent `e056...5f46` and zero references to the older Assistant or Social Sales agent. Social home found exactly one Social Sales agent reference and zero Directory-agent references. This proves the sampled live sites are wired to the correct agent IDs, while standalone FormWise access remains a separate hardening issue.
- Verified all seven current Social offer pages used by the revised Sales Assistant draft return HTTP 200 and expose the expected public product title/price path: Business Essentials, Lead Assistant, Local SEO, Social Growth, and the three AI Studio tiers. The four primary offer pages also expose a WooCommerce purchase control. This is page-level evidence only, not a completed payment-lifecycle test.
- Ran isolated anonymous-cart acceptance for all seven current offers. Each product page resolved its exact product ID (`53760`, `53756`, `53755`, `53757`, `53591`, `53592`, `53593`), accepted one item into a new ephemeral WooCommerce session, rendered the correct item in Cart, and reached an HTTP 200 Checkout with a Place Order control. No account, order, payment, subscription, message, or persistent customer record was created.
- Business Essentials checkout specifically showed the product, `$29.99`, the seven-day trial disclosure, terms/privacy surface, and no former `$99` setup fee. This proves the anonymous pre-payment path and displayed terms only; gateway authorization, webhook delivery, provisioning, renewal, refund, cancellation, and billing-portal behavior remain untested.
- Business Essentials Checkout exposed one `stripe` payment method, loaded Stripe/Woo gateway code, included the Woo checkout nonce, required billing email, and rendered privacy/terms controls. The QA session stopped before customer details, Place Order, payment authorization, or financial mutation.
- Current FormWise model inventory reports the Sales Assistant's `gpt-5.6-luna` model at 5 credits per run. No execution was made; the 10 saved cases therefore need explicit approval for at least 50 execution credits before the draft can satisfy the provider's publish gate.
- Updated the free FormWise Sales Assistant draft to remove the former $138/$278/$648 checkout framing, old mandatory setup fees, and private-preview AI Studio statement. Added the current launch catalog, no-mandatory-setup rule for self-service offers, Business Essentials trial wording, lowest-priced-suitable routing, four current starters, and 10 revised accuracy/safety cases. `diff_agent_draft` confirms only the prompt and starters differ from live. No draft run, test-case run, publication, or credit spend occurred.

## 2026-11-20 FormWise Sales Assistant production acceptance

- The user explicitly approved the FormWise credit spend. The first saved ten-case draft suite completed 8/10: LinkedIn, automation, pricing, booking, contact-consent, refund, discount, and prompt-injection cases passed; SEO failed because its expected behavior conflicted with the intentional one-question-first flow, and the trial answer omitted the required checkout-billing reminder.
- Corrected the SEO expected behavior to require exactly one focused first-turn question, and added the trial billing reminder. The complete post-fix ten-case run produced all ten completed provider executions; its aggregate tool response was truncated, so no unsupported aggregate score is claimed. Read-only provider history confirmed the second run, and direct inspection confirmed the corrected SEO and trial outputs.
- The pre-publication diff then exposed two accidentally negated guardrails. Publication was stopped. The identity and effective system instruction were corrected, and the missing current catalog was placed directly in the prompt so trial/pricing answers do not depend on inconsistent retrieval.
- Updated the `Verified service catalog and prices` knowledge page from the legacy-first November 9 content to the verified November 20 launch catalog, exact customer-credit allowances, trial rules, limitations, current product links, quote paths, and legacy-customer boundary.
- Post-repair targeted provider tests: prompt-injection 1/1 passed; the first trial retry exposed missing authoritative retrieval and correctly blocked publication; after the prompt catalog repair, trial 1/1 and pricing 1/1 passed. The passing trial answer disclosed the eligible seven-day trial, $29.99/month price, checkout-controlled billing terms, and product URL. The passing pricing answer listed only the seven current launch products plus reviewed-scope Automation and omitted former setup fees.
- `diff_agent_draft` confirmed only the intended system instruction and four conversation starters differed from live. Published production version `6993354e-ab96-4ad2-9a54-479263de8b40` as `Current launch catalog and trial safeguards`.
- Read-back reports `published=true`, `versionCount=8`, and the production version above. The owned public link is `https://ai.branddad.social/tools/35ab0cc3-e2c5-456c-b4ef-2d1853ffd29c`; `get_share_links` confirms embedding on `https://branddad.social` is allowed. Built-in web/search/scrape/research/artifact/voice capabilities remain off, and no flow or external integration was added.
- This closes the Sales Assistant content/test/publication gate. It does not close the separate BrandDad-hosted proxy key, customer-isolation, access-hardening, tier-plan provisioning, Stripe lifecycle, Make automation, Directory stability, or Video Director gates.
- StartHost rejected email replies for ticket updates, so the complete request was resubmitted through the authenticated support portal and confirmed in ticket `898665003` as `You Said` at 22:32:40 on 2026-11-20. It includes the exact scheduler command, three cache basenames, duplicate-runner warning, database telemetry/root-cause request, and explicit prohibitions on uncoordinated restarts, credential rotation, plugin changes, broad purges, and file/database/customer-data changes.
- A fresh clean-route sample still returned the retired Sales Help widget from Events, Jobs, and the Classifieds all-listings variant; the Classifieds `nocache=1` variant returned the deployed removal marker and no rendered `#bds-sales-help`, confirming the live defect remains cache-only for that route. No production cache or customer data was changed during this observation.
- Rechecked the canonical final all-listings URLs with `nocache=1` applied after the Events/Jobs redirects: Events, Jobs, and Classifieds each returned HTTP 200 with one Concierge reference, no rendered `#bds-sales-help`, and no `dcl-claim-modal`. Their ordinary clean counterparts still render the retired helper and refund modal. This reconfirms that the deployed PHP output is correct and the remaining defect is the three stale clean-URL cache objects already supplied to StartHost.
- Added `workflow_dispatch` to the active `BrandDad Social wp-cron` workflow on `main` and ran exactly one observed production invocation (`44830790644`). GitHub completed the run successfully from 22:39:53 to 22:40:02 UTC, and its log recorded `http_status=200` for the single HTTPS call to `https://branddad.social/wp-cron.php`.
- The immediately observed Action Scheduler state improved from 4,990 total / 771 complete / 4,166 failed / 53 pending / 52 past due to 4,851 total / 655 complete / 4,166 failed / 30 pending / 12 past due. Historical failures did not increase. The changed totals reflect Action Scheduler retention/cleanup plus new WooCommerce database-update actions and must not be interpreted as 116 financial actions completing.
- The two previously overdue WP Swings subscription hooks were no longer overdue and were rescheduled for 23:10:04 UTC. WordPress Site Health no longer reported the Action Scheduler queue failure; its remaining scheduled-event warning was `wpseo-reindex`.
- Eleven and twenty-one minutes after that invocation, Action Scheduler remained at 30 pending / 12 past due with the new WooCommerce update callbacks still pending. No action was manually Run, Cancelled, or Deleted. GitHub's Actions API still showed only the manual run through 23:00:39 UTC, more than 20 minutes after activation. The manual evidence proves the endpoint can execute and process a bounded queue; recurring GitHub scheduling remains unproven until an `event=schedule` run is recorded.
- A default-branch cleanup commit at 23:00:14 UTC removed one accidental empty nested workflow path while leaving the real active workflow unchanged. The Actions API still showed no `event=schedule` run through 23:09:00 UTC, including the next post-cleanup interval; recurrence therefore remains unproven.
- The observation continued through 23:15:20 UTC, covering three five-minute boundaries after the cleanup commit, with no scheduled run recorded. GitHub's official troubleshooting guidance states that scheduled events can be delayed or dropped under Actions load and recommends a different minute offset; changing the production cron expression is deferred until the required Rafter gate can run or StartHost supplies its preferred host runner.
- A bounded Directory stability recheck initially returned HTTP 200 for Home, Jobs, and Classifieds, but the REST index and clean Events route each timed out with zero response bytes at 12 seconds. A single delayed retry of those two targets also returned zero bytes and timed out at 20 seconds. Further heavy-route probing was stopped to avoid adding load. This is new evidence for the existing host-level intermittent stability incident in ticket `898665003`, not evidence of a completed repair.
- Prepared an undeployed cron re-registration candidate on local branch `codex/cron-offset-local` at commit `58d52f0`. It is behaviorally equivalent to the successful live manual workflow except for a documented `2-59/5 * * * *` offset, which yields 12 invocations per hour with five-minute deltas including the hour wrap. Manual Rafter/CWE review confirmed a fixed HTTPS target, no caller input, no secrets, no third-party Actions, discarded response bodies, TLS 1.2+, a 40-second request timeout, zero retries, read-only repository permission, GitHub-hosted execution, and one concurrency group.
- `git diff --check` and the local secrets scan passed for the candidate. `rafter run --mode fast` was attempted and rejected because the account remains at 16/16 scans. GitHub then correctly rejected the development-branch push because the current OAuth token lacks `workflow` scope. The exact local commit remains preserved, but the candidate was not pushed, merged, or deployed and cannot be called approved or operational.
- Post-cron authoritative WooCommerce review found 29 historical orders, with the newest two still dated 2026-11-13 (orders `53768` and `53765`); no 2026-11-20 order or duplicate renewal order was created by the manual cron run. The subscription table still contains three records: one active subscription (`53767`, parent order `53765`) and two historical cancelled subscriptions. The active record remains `BrandDad AI — Starter`, `$15.89`, PayPal, with next payment `December 13, 2026`. No order, subscription, payment, refund, or customer record was edited during this review.
## 2026-11-20 — Airbnb one-case assistant acceptance

- Real provider verification: published FormWise flow `86c87c94-2054-4641-87e1-2a70a0a9b1d4` ran once with synthetic Airbnb facts and returned a nonempty, practical, policy-safe dispute draft. No real guest, property, account, or credential data was used.
- Hosted staging report: `https://staging-branddad-social.stackstaging.com/wp-json/branddad/v1/staging/airbnb-case-acceptance`
- Hosted result: version `0.2.0`, `complete: true`, 26/26 checks true, zero error codes, completed at `2026-11-20T23:09:13-05:00`.
- The hosted transport used a one-request synthetic provider fixture bound to the exact published-flow URL and exact bearer/request shape. It did not copy a production credential and did not make a provider network request. This deliberately separates provider-content acceptance from WordPress commerce/execution acceptance.
- Verified hosted behavior: exact staging host, dependencies, non-admin fixture owner, collision-free fixtures, product identity, no-gateway completed order, immutable order-item slug, paid entitlement, customer-owned job, bounded result parsing, one-case commit, duplicate denial, full-refund denial, forced-failure recording, reservation release, mail suppression, exact fixture cleanup, and original-user restoration.
- Local regression after the revision: 41/41 Node contract files, 40/40 `deploy/` PHP syntax checks, and 4/4 standalone PHP tests passed. `git diff --check` reported only the existing LF-to-CRLF warning for the design document.
- Rafter: local secrets scan passed with no detected secrets. Required `rafter run --mode fast` was attempted and rejected because the account is at 16/16 fast scans. Paid Plus was not used.
- No card, payment gateway, production order, customer email, or production checkout activation occurred.

## 2026-11-27 — AI website plan production acceptance

- Deployed the reviewed Social MU-plugin updates for AI execution `0.3.2`, the website-plan offer `0.1.1`, the services catalog `1.2.1`, and frontend polish `1.0.4` through the authenticated StartHost file manager. Each upload completed successfully.
- The canonical WooCommerce product now renders as **AI Website Improvement Plan** at **$19.99 one time**, with a visible **Buy AI plan** control and plain-language scope. The former **Available by quote**, **Request a scoped quote**, and **BrandDad SmartSites Website Optimizer** presentation was absent.
- The legacy `/product/website-speed-optimization/` route resolves to the canonical AI Website Improvement Plan product and displays the current title, price, and purchase control.
- A live run of the public website checker against `https://branddad.social/` returned a score and recommended **AI Website Improvement Plan — $19.99** for weak performance signals. The stale **Website Speed Optimization — $149** recommendation was absent. The recommendation explains that secure WordPress connection and customer approval are required before supported changes can be applied; hosting, CDN, paid-plugin, and custom-code work remain separately scoped.
- The Social Services page exposes frontend-polish marker `1.0.4`, the $19.99 one-time website plan, and its **Buy AI plan** link. No public `FormWise` provider name was visible on the audited Services, checker, or product pages.
- Anonymous access to `/wp-json/branddad/v1/ai/tools` returned `rest_forbidden` with HTTP status `401`, confirming the customer tool catalog remains fail-closed outside an authenticated session.
- Responsive browser acceptance passed at 390px and 320px widths. The product title, price, purchase control, logo, and compact menu remained available. The opened 320px menu exposed Services, Get Featured, Account, Contact, and List my business free with the intended destinations.
- No cart, checkout, order, payment, subscription, customer message, or FormWise execution was created during production verification.
- Post-deployment local regression passed **42/42 Node contracts** and **42/42 deployment PHP syntax checks**. File-scoped local secret scans found no secrets. Required remote `rafter run --mode fast` had already been attempted and was blocked solely by the account's 16/16 scan quota; paid Plus was not used.
- Automatic implementation remains intentionally fail-closed: purchasing the plan creates an owner-scoped AI planning job. Applying supported fixes requires a separately authorized secure site connection and explicit approval of the proposed changes; the system does not collect passwords or silently edit customer websites.

## 2026-11-27 — automatic scheduler and queue acceptance

- GitHub Actions workflow `425477211` is active on `main` and now records repeated automatic `event=schedule` successes. The latest sampled automatic run completed successfully and logged `http_status=200` for `https://branddad.social/wp-cron.php`.
- StartHost Scheduled Tasks still reports zero saved tasks. The package remains false-positive flagged and ticket `898665003` remains `Awaiting Staff Reply`; native cron and PHP-mail restoration are still provider-owned blockers.
- Live WooCommerce Action Scheduler read-back: 5,583 total / 1,398 complete / 4,166 historical failed / 19 pending. The sampled pending rows are future-dated, including the next 30-minute WP Swings checks and later daily WooCommerce maintenance; no sampled action is past due.
- Recent completed rows prove automatic processing through WP-Cron: WP Swings renewal/expiration checks completed at 13:28, 13:39, 14:02, and 14:14 UTC on 2026-11-27, and the latest WooCommerce product-attribute update completed at 14:14 UTC.
- No row action (`Run`, `Cancel`, or `Delete`) was used. No cron task, payment, order, subscription, customer, support message, or hosting setting was created or changed.

## 2026-11-27 — AI-first service recommendation regression

- Added AI-first checker routing for the already-approved BrandDad offers: performance signals → AI Website Improvement Plan `$19.99` one time; SEO/local-search signals → AI Local SEO Starter `$19.99/month`; profile-copy gaps → AI Studio Starter `$14.99/month`.
- The former `$179` Website SEO Audit is preserved but excluded from new checker recommendations. Critical site availability, HTTPS, form, and repair signals continue to route to hands-on help because those actions require authorized technical access.
- Recommendation ordering now uses explicit AI suitability first, matched issue count second, and lower price third. Results remain capped at four.
- Focused static contract: 14/14 assertions passed. Focused PHP behavior: 9/9 assertions passed. Full local regression: 43/43 Node contracts, 42/42 deployment PHP syntax checks, and 5/5 standalone PHP behavior tests passed.
- Local secret scanning found no secrets. Required remote `rafter run --mode fast` was retried against the exact remote branch and remained blocked at 16/16; paid Plus was not used.
- Production upload and live checker verification are now complete; see the production section below.

## 2026-11-27 — AI-first recommendation live verification

- Production upload evidence: StartHost File Manager reported `bds-services-catalog-mu.php` `Completed`; the deployed file row shows 36.47 KB and modified time `00:21:17 - 27/11/2026`.
- Public checker URL: `https://branddad.social/check-your-website/?bdsqa=ai-first-live`.
- Checker page loaded with the Website URL input and Run check control; visible text contained no `FormWise` provider name and no stale `$149` speed-service copy.
- Live check target: `https://branddad.social/`.
- Live result: `85 / 100`, HTTP 200/HTTPS available, performance signal detected, and **AI Website Improvement Plan — $19.99** returned as the suggested fix. The result included the secure-connection/customer-approval boundary and did not claim automatic unapproved website changes.
- Services page remained available and exposed AI Website Improvement Plan, AI Local SEO Starter, and AI Studio. No visible `FormWise` provider name was found.
- Hosting follow-up evidence: ticket `898665003` changed from `Awaiting Customer Reply` to `Awaiting Staff Reply`; the submitted 27 November entry is visibly present and the ticket was not closed.
- Required remote Rafter fast scan remains blocked by the account's 16/16 quota. File-scoped local secret scans and the full pre-deployment regression are recorded in the preceding section; paid Plus was not used.

## 2026-11-27 — website-plan duplicate-product release candidate

- Reconciled legacy Woo product `53082` with canonical product `53645` without deleting either record or changing historical orders.
- Added exact-product fail-closed purchase and visibility contracts, canonical recommendation-link coverage, and an assertion that both records are validated before either mutation begins.
- Full local release regression passed: **43/43 Node contracts**, **48/48 deployment PHP syntax checks**, **5/5 standalone PHP behavior tests**, release SHA-256 verification, and `git diff --check`.
- `rafter secrets .` found no secrets. Required `rafter run --mode fast` was attempted and rejected because the account remains at 16/16 fast scans; paid Plus was not used.
- No live file, catalog record, cart, checkout, order, payment, subscription, customer, email, or FormWise execution changed during this local validation.
## 2026-11-27 — production canonical website-plan acceptance

| Check | Result |
|---|---|
| Production plugin upload | PASS — all three files reported completed; frontend polish row updated to 34.41 KB at `09:18:42 - 27/11/2026` |
| Services deployment marker | PASS — `data-bds-frontend-polish="1.0.6"` present |
| Services plan links | PASS — 1 canonical link, 0 retired `website-speed-optimization` links |
| Services structure/branding | PASS — 1 visible H1, no visible `FormWise`, no horizontal overflow |
| Shop product deduplication | PASS — exactly 1 AI Website Improvement Plan card |
| Shop canonical purchase path | PASS — canonical product URL and exact `add-to-cart=53645` control |
| Legacy product URL | PASS — redirects to `/product/branddad-smartsites-website-optimizer/` |
| Canonical product | PASS — correct title, `$19.99`, and **Buy AI plan** |
| Legacy product 53082 | PASS — catalog visibility hidden in WooCommerce admin |
| Mobile Services 390px | PASS — one H1, canonical link only, navigation visible, no provider branding, no overflow |
| Mobile Services 320px | PASS — one H1, canonical link only, no overflow |
| Mobile canonical product | PASS — title, visible price, and purchase control; no overflow |
| Transaction safety | PASS — no cart, checkout, order, payment, subscription, or customer mutation |
| Local regression | PASS — 43/43 Node, 48/48 PHP lint, 5/5 PHP behavior, 17/17 frontend renderer |
| Rafter local secrets | PASS — no secrets in changed source files |
| Rafter remote fast | BLOCKED — account remains at 16/16; paid Plus not used |

## 2026-11-27 — Spotify stylesheet permission acceptance

| Check | Result |
|---|---|
| Production file present | PASS — `branddad-seoestore/assets/spotify.css`, 4.05 KB in StartHost File Manager |
| Root cause | CONFIRMED — stylesheet permission was `600` while adjacent public assets were `644` |
| Scoped repair | PASS — changed only `spotify.css` permission to `644` after exact confirmation |
| Public stylesheet | PASS — HTTP 200, 4,148 bytes |
| Paired JavaScript | PASS — HTTP 200, 1,032 bytes |
| Services regression | PASS — HTTP 200, 213,839 bytes |
| Content/data safety | PASS — no file-content, product, cart, checkout, order, payment, subscription, or customer mutation |

## 2026-11-27 — checker navigation and provider-label acceptance

| Check | Result |
|---|---|
| Frontend polish source | PASS — `1.0.7`, PHP lint clean, 18/18 focused assertions |
| Customer surface source | PASS — PHP lint clean for both files; customer hub test passed |
| Local secret scans | PASS — no secrets detected in the three deployment files |
| StartHost upload | PASS — 3/3 files reported `Completed` |
| Checker 390px header | PASS — exactly one visible menu control |
| Checker menu destinations | PASS — Services, Get Featured, Account, Contact, and List my business free each visible once after opening |
| Checker layout | PASS — one visible H1, zero broken images, no provider branding, no horizontal overflow |
| Account Hub white label | PASS — `AI execution key` and `AI provisioning key` visible to the shop manager; no visible `FormWise` text |
| Services regression | PASS — frontend marker `1.0.7`, one H1, zero broken images, no provider branding, no overflow |
| Public HTTP regression | PASS — Home, Services, Checker, Account Hub, canonical website plan, and Spotify CSS returned HTTP 200 |
| Transaction/provider safety | PASS — no cart, checkout, order, payment, subscription, customer, credential, AI run, or provider-credit mutation |

## 2026-11-27 — Directory facet H1 production acceptance

- Release: `bds-directory-facet-browse-mu.php` `1.1.4`, commit `79efc56`.
- Local: PHP syntax passed; focused Node contract passed 7/7; `git diff --check` found no content error.
- Security: local secret scan passed. Remote `rafter run` was attempted and rejected only because the fast-scan allowance is 16/16. Manual review confirmed untrusted page text is copied only through `textContent`; no HTML injection, endpoint, credential, authorization, payment, dependency, or data-write surface was added.
- Production upload: StartHost `Completed` 1/1; exact target restored to permission `444`.
- Playa results, 390x844: facet `1.1.4`; exactly one visible H1 `Food & Beverage in Playa del Carmen`; 12 listing cards in the loaded page; Playa del Carmen text present; `scrollWidth=clientWidth=375`; zero broken images; one `#bdd-ai`; zero visible FormWise references.
- City gate, 390x844: exactly one visible H1 `Food & Beverage — pick a city`; zero visible listing cards; `scrollWidth=clientWidth=375`; zero broken images; one `#bdd-ai`.
- No cart, checkout, order, payment, subscription, customer record, email, AI execution, or cache deletion occurred.

## 2026-11-27 — Done-for-You optional visibility tests

- `php -l` passed for the private engagement catalog, the public overlay, and canonical frontend polish.
- `dfy-engagement-catalog-test.php` passed 14/14 checks: base plan isolation, public totals, add-on prices, post caps, all supplied ids, regional primaries, manual fallbacks, fail-closed execution modes, cost ceilings, invalid mapping denial, and customer-safe metrics.
- `dfy-packages-overlay-test.php` passed 16/16 checks, including the $399 no-engagement plan, optional visibility language, honest reporting, private supplier ids, and contact-only conversion.
- `business-autopilot-services-contract.mjs`, `provider-cost-ledger-contract.mjs`, and `frontend-polish-test.php` passed.
- `git diff --check` found no content error; only pre-existing Windows line-ending notices remain.
- `rafter secrets .` found no secrets. Required remote `rafter run --mode fast` was attempted and blocked because the account remains at 16/16 scans.
- Manual OWASP review: all public copy is static and escaped; the private catalog registers no route, performs no network call, accepts no request input, and creates no payment or order. Supplier ids and costs are absent from rendered HTML. No dependency, credential, authentication, SQL, file-upload, deletion, or outbound-fetch surface was added.

## 2026-11-27 — official promotion correction tests

- PHP lint passed for the promotion catalog, package overlay, canonical frontend polish, and their focused PHP tests.
- `dfy-engagement-catalog-test.php` passed 18/18 checks: the panel catalog is empty, the $399/$599/$699/$849 plan totals are correct, all plans retain 20 content slots, promotion tiers use 4/8/12 slots, media/creator budgets stay separate, invalid schedules fail closed, and organic/paid metrics remain separate.
- Exact 30-day promotion schedules passed: Starter `[4,12,19,27]`, Growth `[2,6,10,14,17,21,25,29]`, and Pro `[2,4,7,9,12,14,17,19,22,24,27,29]`. Content and promotion days are unique and ordered.
- `dfy-packages-overlay-test.php` passed 17/17; `business-autopilot-services-contract.mjs` passed 16/16; `frontend-polish-test.php` passed all focused checks.
- Full local suite: all 9 PHP tests passed; 44 of 45 Node contracts passed. The only failure is the pre-existing unrelated stale checksum for clean file `deploy/bds-provider-cost-ledger-mu.php` (`FDC0…` actual versus `2331…` manifest). This batch did not modify that file or release manifest.
- `git diff --check` found no content error; only expected Windows line-ending notices remain.
- Rafter local secrets scan passed. Required remote fast scan was attempted and rejected because the account is at 16/16 scans; no paid Plus scan was run.
- Manual OWASP/ASVS review passed: public strings are escaped; plan lookup is server-owned; schedule inputs are integer-normalized and reject slots greater than period days; no route, credential, token, payment mutation, provider id, outbound call, or automatic spend was added. Promotion remains qualification-only/fail-closed until official authorization and customer approval exist.
- Host-cache hotfix lint and its 5/5 focused callback-order assertions passed. Local Rafter secrets scan found no secret in the additive file. Manual review confirmed it removes only two known content-filter callbacks, installs one deterministic canonical renderer, accepts no request input, and performs no network, payment, storage, or deletion action.
- Production upload: the three corrected MU files reported 3/3 `Completed`; the additive hotfix reported 1/1 `Completed`.
- Production desktop acceptance: frontend marker `1.0.14`; one H1; no horizontal overflow; all four plan totals present; Promotion Starter/Growth/Pro, optional-promotion, and separate-cost language present; no BDS Network, legacy Visibility package, or visible FormWise text.
- Production 390px acceptance: one visible H1; five visible management/promotion cards and five visible actions; shared menu visible; zero broken images; `scrollWidth=clientWidth=375`.

## 2026-11-27 — paid fulfillment and accepted-quote acceptance

| Check | Result |
|---|---|
| Commit `5af7c07` | PASS — documentation only; no functional purchase-flow change |
| Done-for-You history | PASS — quote-only since `6c1f930`; Contact Us renderer since `9bb789d` |
| Exact staging product | SUPERSEDED — original synthetic slug did not match production; corrected acceptance target is `ai-press-release-pack` |
| Unpaid order | PASS — zero private staff tasks and zero task marker |
| Confirmed paid order | PASS — exactly one private staff task |
| Duplicate paid events | PASS — repeated payment-complete and completed-status events left exactly one task |
| Accepted quote | PASS — paid admin-created `done-for-you-ai-management` order created exactly one scoped staff task |
| Browser-created quote order | PASS — denied; public checkout cannot activate quote-only fulfillment |
| Gateway safety | PASS — staging fixture used `bds_staging_synthetic_no_gateway`; no gateway call or transaction |
| Fixture cleanup | PASS — synthetic orders, products, and claims removed |
| Local PHP behavior | PASS — `tests/assisted-submission-services-test.php` |
| All four one-time products | PASS — each creates zero unpaid tasks, one paid task naming the service, and no replay duplicate |
| Staging contract | PASS — 12/12 assertions |
| Staging report | PASS — `complete=true`, all 13 checks true, no error codes |
| Production safety | PASS — no production checkout, customer order, payment, product, or gateway mutation |
| Rafter secrets | PASS — no secrets detected |
| Rafter remote fast | BLOCKED — account remains at 16/16; paid Plus was not used |
| Manual OWASP/ASVS review | PASS — server-owned product identity; canonical Woo paid-state recheck; atomic order claim; browser-created quote orders denied; staging route exact-host and boolean-only |

## 2026-11-27 — Done-for-You public quote guard

| Check | Result |
|---|---|
| PHP syntax | PASS — quote guard and focused harness |
| Exact allowlist | PASS — one canonical quote-only slug plus six retired growth slugs; unrelated products miss |
| Direct add-to-cart | PASS — canonical, retired, parent, and variation combinations fail closed |
| Stale cart | PASS — checkout receives a blocking error; the cart itself is not deleted or changed |
| Unrelated products | PASS — original validation result and loop CTA are preserved |
| Services renderer | PASS — Complete Management remains $399/month, promotion optional, and Contact-not-checkout |
| Paid fulfillment regression | PASS — unpaid zero tasks, paid one task, duplicate events no second task |
| One-time/AI regressions | PASS — assisted submissions, AI-first Services, and website-plan contracts remain green |
| Secrets | PASS — no secrets detected |
| Remote Rafter fast | BLOCKED — 16/16 quota; paid Plus not used |
| Manual web security review | PASS — no new route/auth/SQL/dependency/outbound request; exact server-owned identity; static escaped output; fail-closed business-flow checks |
| Production state | UNCHANGED — no upload, cart, order, payment, subscription, refund, customer, or provider mutation |
| Complete release regression | PASS — 57/57 files (11 PHP, 46 Node) |
| Release integrity | PASS — obsolete inactive `bds-network` allowlist entry removed; current provider-cost module matches the existing approved SHA-256 manifest |

## 2026-11-27 — one-time service live identity acceptance

| Check | Result |
|---|---|
| Exact live slugs | PASS — Airbnb `branddad-ai-review-dispute-assistant`; IMDb `ai-imdb-submission-pack`; billboard `ai-billboard-creative-pack`; press `ai-press-release-pack` |
| Assisted Submission version | PASS — `0.2.3` active on all four product pages |
| Frontend version | PASS — `1.0.16` active on Services |
| Homepage cards | PASS — all four names, prices, and valid destinations present; no duplicate press card |
| Services cards | PASS — all four bounded submission services present |
| Product pages | PASS — one visible H1, current scope copy, and one-time purchase form preserved on each |
| Broken images | PASS — zero across homepage, Services, and the four product pages |
| Horizontal overflow | PASS — zero at the 1265px live acceptance viewport |
| Payment safety | PASS — no cart, checkout, payment, order, refund, or gateway execution used during acceptance |
| Full release regression | PASS — 58/58 test files |
| Rafter remote fast | BLOCKED — existing account quota remains 16/16; no paid Plus scan was run |
| Manual web security review | PASS — fixed server-owned identities; paid-state recheck remains authoritative; atomic task claim unchanged; output escaped or built with trusted DOM nodes; no new endpoint, SQL, auth, secret, dependency, or outbound request |

## 2026-11-27 — live Done-for-You price acceptance

| Check | Result |
|---|---|
| Production versions | PASS — catalog `0.3.0`, frontend `1.0.17`, overlay `1.3.0`, price refresh `1.0.0` |
| Public prices | PASS — $399 management, $449 Starter, $499 Growth, $599 Pro |
| Retired prices | PASS — $699 and $849 totals absent from the active section |
| Quote-first flow | PASS — four Contact links, zero add-to-cart links, and zero cart forms in the Services section |
| Product-page guard | PASS — Done-for-You product has a contact/quote action, zero Add to cart buttons, and zero cart forms |
| Existing billing safety | PASS — no order, subscription, customer, payment method, gateway, charge, refund, or historical price changed |
| White-label copy | PASS — no FormWise, JustAnotherPanel, BDS Network, supplier-panel name, or panel service ID in the active package section |
| Desktop layout | PASS — one visible H1, zero broken images, and no horizontal overflow |
| Mobile layout | PASS — 390x844 viewport, all four prices visible, all five actions usable, zero broken images, one visible H1, and no horizontal overflow |
| Local release regression | PASS — 59/59 test files |
| Rafter remote fast | BLOCKED — account remains at 16/16; paid Plus was not used |
| Manual security review | PASS — presentation-only exact-section rewrite; no endpoint, input, auth, SQL, secret, dependency, outbound request, data mutation, or payment action |

## 2026-11-28 — website-check offer and homepage-funnel acceptance

| Check | Result |
|---|---|
| Production upload | PASS — 2/2 reviewed MU plugins completed in the Social production MU-plugin directory |
| Checker recommendation | PASS — `AI Website Improvement Plan` and `$19.99` returned by the live checker |
| Retired checker offer | PASS — `Website Speed Optimization` and `$149` absent from the live result |
| Checker isolation | PASS — existing route, scanner, score, cache, rate limits, diagnostic reason, and unrelated recommendations preserved |
| Homepage creator path | PASS — navy high-contrast section with three visible, distinct actions |
| Lead form behavior | PASS — original REST endpoint unchanged; privacy acknowledgment required; optional email consent remains unchecked |
| Desktop layout | PASS — responsive grid, 20x20 checkboxes, zero broken images, and no horizontal overflow |
| Mobile layout | PASS — 390x844 test; 347px sections within viewport, three 309px CTA buttons, single-column form, full-width submit, 20x20 checkboxes, and no horizontal overflow |
| JustAnotherPanel isolation | PASS — no JAP file, configuration, service ID, credential, workflow, or live tab changed |
| Full release regression | PASS — 61/61 test files |
| Local secrets scan | PASS — no secrets detected in the two deploy files |
| Rafter remote fast | BLOCKED — account quota remains 16/16; paid Plus was not used |
| Manual web security review | PASS — exact existing route only, fixed server-owned replacement values, no request-data echo, no new endpoint/auth/payment/data write/network call/dependency, and CSS-only homepage presentation |

## 2026-11-28 — Services recommendation-form acceptance

| Check | Result |
|---|---|
| Production upload | PASS — Conversion Funnel Polish `1.1.0` completed in the Social MU-plugin directory |
| Scope | PASS — exact Social hosts; homepage or `services` page only |
| Mobile form layout | PASS — 347px card, one 309px column, full-width 309px submit, and no horizontal overflow |
| Checkbox repair | PASS — both controls reduced from 333x44 to 20x20 pixels |
| Consent behavior | PASS — privacy acknowledgment remains required; email follow-up remains optional and unchecked |
| Endpoint preservation | PASS — existing `https://branddad.social/wp-json/branddad/v1/sales/leads` endpoint unchanged |
| Images | PASS — zero broken images on final mobile acceptance |
| Host observation | RECOVERED — one transient origin-side Cloudflare 502; bounded five-second retry and final acceptance returned the complete page |
| JustAnotherPanel isolation | PASS — no JAP file, setting, credential, service ID, or workflow changed |
| Full release regression | PASS — 61/61 test files |
| Security-surface check | PASS — CSS availability and exact page scoping only; no new input, endpoint, auth, payment, data write, network call, dependency, or execution path |

## 2026-11-28 — anonymous Directory browsing acceptance

| Check | Result |
|---|---|
| Public Home | PASS — HTTP 200 without membership requirement |
| Public category wizard | PASS — HTTP 200; country, region, city, then business type |
| Local category availability | PASS — only categories with Playa del Carmen matches are offered after location selection |
| Public result list | PASS — Restaurants in Playa del Carmen returned 36 matching businesses and public View Business links |
| Public business profile | PASS — Off the Vine returned HTTP 200 with business and contact content and no login gate |
| Anonymous account content | PASS — account-credit panel absent from anonymous HTML; it remains owner-account specific |
| Mobile layout | PASS — no horizontal overflow and zero broken images in the sampled public profile flow |
| Membership policy | PASS — browsing remains public; account state is needed only for saved/account/owner actions |
| Production mutation | NONE — verification was read-only and required no code or configuration change |

## 2026-11-28 — branded product-artwork acceptance

| Check | Result |
|---|---|
| Production upload | PASS — Branded Product Placeholder `1.0.1` completed in the Social production MU-plugin directory |
| Missing-product artwork | PASS — live Done-for-You page uses the existing BrandDad Social wordmark; WooCommerce gray placeholder absent |
| Accessible label | PASS — `BrandDad Social service`; retired `Awaiting product image` label absent on the fallback |
| Assigned-art control | PASS — AI Business Essentials retains its real product image and original descriptive alt text |
| Quote-first preservation | PASS — Done-for-You remains $399 with one written-scope contact action and zero visible cart buttons |
| Priced product preservation | PASS — audited AI Business Essentials trial and press-release one-time purchase paths remain present |
| Desktop layout | PASS — one visible H1, zero broken images, and no horizontal overflow |
| Mobile layout | PASS — 390x844 test; branded fallback 333x49.6px, one visible H1, zero broken images, and no horizontal overflow |
| Commerce safety | PASS — no price, cart, checkout, payment, order, subscription, customer, or gateway mutation |
| JustAnotherPanel isolation | PASS — no JAP file, setting, credential, service ID, or workflow changed |
| Full release regression | PASS — 62/62 test files |
| Security-surface check | PASS — fixed owned artwork and presentation filters only; no input, endpoint, auth, payment/data mutation, network call, secret, dependency, or provider execution |

## 2026-11-28 — homepage duplicate-style acceptance

| Check | Result |
|---|---|
| Production version | PASS — Conversion Funnel Polish `1.2.0` |
| Duplicate cleanup | PASS — `bds-hire-css` reduced from two identical nodes to one |
| Duplicate IDs | PASS — zero after the live page completes rendering |
| Content preservation | PASS — existing freelance section and links remain; only the redundant style node is removed |
| Desktop layout | PASS — zero broken images and no horizontal overflow |
| Mobile layout | PASS — 390x844 test, one visible H1, zero broken images, and no horizontal overflow |
| Full release regression | PASS — 62/62 test files |
| Commerce/provider isolation | PASS — no product, cart, checkout, payment, order, subscription, customer, or JustAnotherPanel behavior changed |

## 2026-11-28 — mobile-menu and WooCommerce-spacing acceptance

| Check | Result |
|---|---|
| Shared menu pages | PASS — Home, Services, Shop, and AI Business Essentials |
| Shared menu destinations | PASS — Services, Get Featured, Account, Contact, and List my business free on every sampled page |
| Production version | PASS — Frontend Polish `1.0.18` |
| Desktop product spacing | PASS — responsive 51.2px primary padding; breadcrumb at 161.4px instead of 260.2px |
| Mobile product spacing | PASS — 24px primary padding; breadcrumb at 100px instead of 226px |
| Mobile Shop spacing | PASS — breadcrumb at 80px and H1 at 116.8px instead of 206px and 242.8px |
| Layout safety | PASS — zero broken images and no horizontal overflow on sampled Shop/product pages |
| Scope isolation | PASS — exact WooCommerce body classes; homepage and Services spacing unaffected |
| Full release regression | PASS — 62/62 test files |
| Commerce/provider isolation | PASS — CSS only; no product, price, trial, cart, checkout, payment, order, subscription, customer, or JustAnotherPanel behavior changed |

## 2026-11-28 — Directory owner utility-strip acceptance

| Check | Result |
|---|---|
| Production upload | PASS — Directory Owner Utility Strip `1.0.0` completed in the Directory production MU-plugin folder |
| Exact scope | PASS — Directory hosts and existing `bds-nav-fix-wallet` only |
| Mobile notice height | PASS — reduced from approximately 257px to 120.8px at 390x844 |
| Owner information | PASS — Available/Held copy retained; My listings and placement links retained |
| Touch targets | PASS — both owner links rendered at 33.3px high in the compact utility strip |
| Discovery preservation | PASS — location-first H1 and four-choice flow remain present |
| Anonymous behavior | PASS — owner notice absent; public category discovery remains available |
| Layout safety | PASS — zero broken images, zero duplicate IDs, and no horizontal overflow |
| File Manager restoration | PASS — returned to Social `wp-content/mu-plugins` after deployment |
| Full release regression | PASS — 63/63 test files |
| Account/commerce isolation | PASS — presentation only; no balance, bid, account, listing, payment, order, subscription, customer, or provider mutation |
