# BrandDad Codex progress

Updated: 2026-11-19
Branch: `codex/final-backend-20261119`
Worktree: `C:\Users\iamtd\Documents\Codex\branddad-codex-final-20261119`

## Completed and retained

- Preserved Cursor branch `cursor/branddad-revenue-20261118` and both dirty existing worktrees; no production or Cursor-owned file was overwritten.
- Reused the verified AI commercial catalog, Woo product mapping, Subscriptions for WooCommerce lifecycle bridge 0.4.2, and Directory free-AI ledger 0.1.0.
- Verified the FormWise organization, owner role, published app/agent/flow UUIDs, tier plans, and restricted customer portal.
- Added `bds-ai-execution-mu.php`: authenticated REST jobs, explicit workflow schemas, subscription-cycle and Directory-free metering, idempotency, verified async queueing, fixed provider origin, bounded results, and failure-safe allowance release.
- Added `bds-account-link-mu.php`: five-minute single-use HMAC account handoff between the two owned WordPress hosts, POST-only token transfer, replay protection, fail-closed source email verification, account reuse, and audit mapping.
- Added `bds-sales-assistant-api-mu.php`: default-off public BrandDad salesperson proxy, verified agent UUID, deterministic catalog offers, model-link stripping, owned-origin checks, client-bound conversation state, and serialized per-client/global cost limits.
- Completed the Rafter/OWASP manual security walk and local secret scan; the remote fast scan is recorded as quota-blocked.
- Added security design, contracts, handoff, audit, entitlement, product, test, blocker, and production-checklist documentation.

## Implemented but not yet deployed

- AI execution REST API and metering.
- Cross-site account linking.
- Public sales-assistant proxy.
- Subscription bridge and free-AI ledger copied into the clean integration branch.

## Next unblocked backend work

1. Add consent-based follow-up worker wired to the existing `bds_follow_consent_v1` list, with unsubscribe and stop conditions.
2. Add onboarding state API and first-task orchestration after safe deployment of execution jobs.
3. Add WordPress staging fixtures for REST permission/ownership, provider failure, idempotent replay, and HPOS subscription discovery.
4. Add signed Stripe/Woo webhook reconciliation only if the installed gateway does not already provide authoritative Woo transitions.
5. Implement Featured payment-event adapter after the four Woo SKUs exist.

## Production status

Not deployed. No purchase, subscription change, provider run, email, WhatsApp message, social post, listing publication, or customer billing modification was performed by this branch.
