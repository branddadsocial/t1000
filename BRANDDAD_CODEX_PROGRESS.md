# BrandDad Codex progress

Updated: 2026-11-19

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
- This correction is local and reviewed; hosted staging and production still run the previously packaged modules until a separate approved deployment step.

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
- Customer inquiry form/list/status APIs and actual lead reporting (capture remains disabled until Cursor form/privacy and staging acceptance).
- Cross-site account linking.
- Public sales-assistant proxy.
- Subscription bridge and free-AI ledger copied into the clean integration branch.

## Next unblocked backend work

1. Add WordPress staging fixtures for provider failure, idempotent replay, trial conversion, Directory bridge replay, browser-session nonce/onboarding concurrency, lifecycle-event deduplication/refunds, unsubscribe, and HPOS subscription discovery.
2. Connect Cursor's lead form to the new capture contract and exercise privacy acknowledgment, optional consent, deduplication, stage progression, unsubscribe, and retention in staging; enable capture only after acceptance.
3. Exercise one authorized public website through configure, async/manual check, DNS/redirect/error, plan-loss, URL-change, daily batch, and value-report paths on WordPress staging; then enable monitoring server-side.
4. Add trial reminder and monitor-alert delivery only after a verified communications provider and sender are available.
5. Connect Cursor's hosted inquiry form and customer inbox to the new endpoints; test spam/rates, cross-account denial, status conflicts, consent, retention, and real counts before enabling.
6. Add signed Stripe/Woo webhook reconciliation only if the installed gateway does not already provide authoritative Woo transitions.
7. Create/approve the four Featured Woo SKUs, connect Cursor listing selection/geography, then run the adapter's staging lifecycle test.
8. Extend customer value reports when real publishing adapters provide verified customer outcomes.

## Production status

Not deployed. Follow-up delivery is compiled default-off. No purchase, subscription change, provider run, email, WhatsApp message, social post, listing publication, or customer billing modification was performed by this branch.
