# BrandDad Codex progress

Updated: 2026-11-19
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
- Added security design, contracts, handoff, audit, entitlement, product, test, blocker, and production-checklist documentation.

## Implemented but not yet deployed

- AI execution REST API and metering.
- Seven-day trial lifecycle and customer status/reporting APIs.
- Consented Social-to-Directory free membership provisioning bridge.
- Directory Featured billing lifecycle adapter (not activatable until SKUs/geography integration exist).
- Cross-site account linking.
- Public sales-assistant proxy.
- Subscription bridge and free-AI ledger copied into the clean integration branch.

## Next unblocked backend work

1. Add WordPress staging fixtures for REST permission/ownership, provider failure, idempotent replay, trial conversion, Directory bridge replay, onboarding concurrency, unsubscribe, and HPOS subscription discovery.
2. Add trial reminder/funnel events only after a verified communications provider and sender are available.
3. Add signed Stripe/Woo webhook reconciliation only if the installed gateway does not already provide authoritative Woo transitions.
4. Create/approve the four Featured Woo SKUs, connect Cursor listing selection/geography, then run the adapter's staging lifecycle test.
5. Extend value reports when real publishing/lead adapters provide verified events.

## Production status

Not deployed. Follow-up delivery is compiled default-off. No purchase, subscription change, provider run, email, WhatsApp message, social post, listing publication, or customer billing modification was performed by this branch.
