# BrandDad Codex progress

Updated: 2026-11-19

## 2026-11-19 deployment preparation batch

- Inspected the signed-in hosting File Manager read-only and verified separate WordPress roots for Social and Directory.
- Verified Social's live backend target at `public_html/branddad.social/wp-content/mu-plugins/` and Directory's root at `public_html/directory.branddad.social/`.
- Confirmed the host has an existing `_bds_deploy_backups/` area; no isolated staging WordPress root was visible.
- Recorded the hosting panel's unresolved `6 infection(s) found` warning as a production-readiness issue rather than dismissing it.
- Added `BRANDDAD_DEPLOYMENT_MANIFEST.md` with per-host module inventories, fail-closed switches, server-only configuration, staged deployment steps, rollback, and acceptance evidence.
- No production file, billing state, provider switch, message, or customer record was changed during this inspection.

## 2026-11-19 provider-cost and preservation batch

- Audited the existing Partnero referral bridge and its prior deployment/verification evidence; preserved it instead of building a duplicate cookie/referral layer.
- Added `bds-provider-cost-ledger-mu.php`, a private append-only verified provider-cost ledger with HMAC idempotency and strict provider/basis/catalog/currency/amount/date validation.
- Added an admin-only contribution API that combines confirmed Woo lifecycle revenue/refunds with verified stored variable costs while explicitly marking incomplete cost coverage.
- Added a static contract suite covering private storage, authorization, no public write route, verified-only bases, catalog enforcement, bounds, idempotency, reporting caps, and non-misleading wording.
- No provider invoice, expense, profit, commission, or margin was invented.
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

1. Add WordPress staging fixtures for REST permission/ownership, provider failure, idempotent replay, trial conversion, Directory bridge replay, onboarding concurrency, lifecycle-event deduplication/refunds, unsubscribe, and HPOS subscription discovery.
2. Connect Cursor's lead form to the new capture contract and exercise privacy acknowledgment, optional consent, deduplication, stage progression, unsubscribe, and retention in staging; enable capture only after acceptance.
3. Exercise one authorized public website through configure, async/manual check, DNS/redirect/error, plan-loss, URL-change, daily batch, and value-report paths on WordPress staging; then enable monitoring server-side.
4. Add trial reminder and monitor-alert delivery only after a verified communications provider and sender are available.
5. Connect Cursor's hosted inquiry form and customer inbox to the new endpoints; test spam/rates, cross-account denial, status conflicts, consent, retention, and real counts before enabling.
6. Add signed Stripe/Woo webhook reconciliation only if the installed gateway does not already provide authoritative Woo transitions.
7. Create/approve the four Featured Woo SKUs, connect Cursor listing selection/geography, then run the adapter's staging lifecycle test.
8. Extend customer value reports when real publishing adapters provide verified customer outcomes.

## Production status

Not deployed. Follow-up delivery is compiled default-off. No purchase, subscription change, provider run, email, WhatsApp message, social post, listing publication, or customer billing modification was performed by this branch.
