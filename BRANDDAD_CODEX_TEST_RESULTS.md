# BrandDad Codex test results

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
