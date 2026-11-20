# BrandDad entitlements

## Authoritative rules

- Woo/WPS subscription status is authoritative for paid BrandDad Social access.
- FormWise plan ids are remote access mirrors, not billing state.
- Directorist claim ownership and listing publication state are authoritative for Directory free-AI eligibility.
- Partnero remains authoritative for affiliate balances/commissions.
- Cursor Featured rotation consumes paid/trial/partnership grant records; Codex billing may activate a paid grant only after `wc_get_order( order_id )->is_paid()`.

## Paid AI

An eligible request needs an active `wps_subscriptions` record for the authenticated WordPress user and a recognized `_bds_ai_formwise_plan_id`. Monthly customer credits are reserved atomically before provider work and committed only after a usable result is stored. Failed work releases the reservation.

## Seven-day Business Essentials trial

- Same product/entitlement family as paid Business Essentials.
- Trial dates and billing terms come from Woo checkout/subscription data.
- Trial does not grant Directory Pro/Elite, Featured, Travel search, or an invented 10% discount.
- Expiration removes trial-only access but preserves the WordPress account, free Directory account/listing, customer-created records, and eligible free-AI allowances.

## Directory free AI

Per verified business per UTC month: one website-audit allowance reserved for a future real audit adapter, five social drafts, one local SEO action plan, five review responses, and ten assistant requests. Current execution API exposes only tools that map to verified workflows; it does not mislabel a customer-supplied website plan as a completed audit.

## Stacking

Each execution chooses exactly one source: `paid` or `directory_free`. There is no automatic fallback or double deduction. Separate purchased subscriptions remain separate unless a future approved migration explicitly combines them.

## Cancellation and failure

- Cancellation/expiry/on-hold revokes the exact FormWise plan through the allowlisted subscription bridge.
- Renewal success provisions/reconciles once; failed renewal removes access according to subscription state.
- Historical orders, outputs, and audit records are preserved according to retention policy.
