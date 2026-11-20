# BrandDad Partnero integration audit

Updated: 2026-11-20

## Verified current provider capabilities

Partnero's current official REST API uses `https://api.partnero.com/v1/` and program-scoped Bearer API keys. Partnero explicitly requires server-side calls, separate keys for staging and production, secure storage, and regular rotation. No API key was created, read, transmitted, or changed in this audit.

Official references:

- [Partnero REST API introduction](https://docs.partnero.com/api-reference/introduction)
- [Partnero API authentication](https://docs.partnero.com/api-reference/authentication)
- [Partnero transactions overview](https://docs.partnero.com/api-reference/transactions/overview)
- [Create transaction](https://docs.partnero.com/api-reference/transactions/create)
- [Delete transaction](https://docs.partnero.com/api-reference/transactions/delete)
- [Archive transaction](https://docs.partnero.com/api-reference/transactions/archive)
- [Restore archived transaction](https://docs.partnero.com/api-reference/transactions/restore)
- [Outgoing webhooks](https://docs.partnero.com/api-reference/webhooks/overview)
- [Partnero webhook event list](https://help.partnero.com/article/52-how-to-set-up-webhooks)

Confirmed functions relevant to BrandDad:

- Create customers and record transactions through the REST API.
- Use a unique transaction key, such as an order or invoice id, for tracking and refund handling.
- Partnero calculates commission from program rules; the transaction request does not directly set commission.
- Permanently delete a transaction to reverse its associated commission for a full refund or chargeback.
- Archive and restore affiliate-program transactions as a reversible alternative.
- Receive `customer.created`, `customer.updated`, `customer.deleted`, `transaction.created`, and `transaction.deleted` outgoing events, plus the documented partner and lead events.
- Verify outgoing webhook HMAC-SHA256 using the exact raw request body and the `Signature` header, with constant-time comparison.
- Return 2xx within five seconds, process asynchronously, and deduplicate because Partnero retries failed webhook deliveries.

## BrandDad event policy

The authoritative event source remains verified WooCommerce/payment lifecycle state. Partnero must never be allowed to decide whether a BrandDad payment succeeded.

1. Create a Partnero transaction only after an eligible Directory payment or renewal is authoritative and paid.
2. Use a deterministic, unique transaction key derived from the BrandDad order/renewal identity. Store the Partnero key and outcome locally before acknowledging completion so retries cannot create a second commission.
3. Apply the existing direct-referral-only eligibility policy. Do not emit commission transactions for BrandDad Social AI subscriptions unless a separately approved agreement explicitly adds them.
4. Preserve paid historical transactions when a subscription is merely canceled at period end. Cancellation stops future renewal transactions; it does not reverse legitimately earned past commissions.
5. For an authoritative full refund or chargeback, permanently delete the exact matching Partnero transaction only after a local reconciliation record proves the same order/renewal and transaction key.
6. Do not automate partial-refund commission adjustments until Partnero confirms the supported method or a native integration proves exact behavior. The current transaction reference documents create, archive, restore, and delete—not a partial amount update. Route partial refunds to a reconciliation queue instead of deleting the full commission or inventing a negative transaction.
7. Use archive/restore only for a reversible administrative hold, not as a substitute for payment truth.
8. Never run Partnero's native Woo/Stripe tracking and a custom transaction emitter for the same product without a verified deduplication design; that would risk duplicate commissions.

## Incoming Partnero webhook controls

If BrandDad subscribes to Partnero events:

- HTTPS only; fixed BrandDad endpoint.
- Validate `Signature` as HMAC-SHA256 over the exact raw body before parsing or queueing.
- Use a server-held per-program signing secret and constant-time comparison.
- Enforce a small raw-body limit and strict JSON schema; ignore unknown business actions.
- Deduplicate on webhook identity plus event/resource identity; repeated delivery must return the stored outcome.
- Respond within five seconds and perform bounded work asynchronously.
- Record only event type, Partnero object key, local correlation key, status, attempt count, and timestamps. Do not log raw bodies, signatures, customer email, API keys, or payment data.
- `transaction.created` and `transaction.deleted` are reconciliation signals only; they cannot provision access, revoke access, issue refunds, or mark invoices paid without matching authoritative Woo/payment state.

## Production gates

Before enabling any Partnero write or webhook:

1. Read the actual BrandDad Partnero program settings and confirm the approved 30% recurring rate, $50 minimum payout, monthly payout cadence, and direct-referral-only scope. This audit does not prove dashboard configuration.
2. Confirm whether native WooCommerce or Stripe tracking is already active and select one authoritative emission path.
3. Create separate least-privilege staging and production API keys through the authenticated Partnero account; this requires separate action-time approval.
4. Configure the webhook signing secret server-side and never expose it to browser JavaScript or Git.
5. Run a safe staging flow: referral -> free registration -> eligible paid Directory order -> one transaction -> renewal -> full refund -> exact commission reversal.
6. Exercise replay, wrong signature, stale/duplicate delivery, wrong product, Social-product exclusion, partial-refund hold, cancellation, and retry recovery.
7. Reconcile the result against WooCommerce, Partnero, and the local lifecycle ledger before production enablement.

## Status

Provider API and event terms are verified from current official documentation. No Partnero account, API key, webhook, customer, transaction, commission, payout, referral, or program setting was created or changed. Actual program configuration and an authenticated staging lifecycle remain open production gates.
