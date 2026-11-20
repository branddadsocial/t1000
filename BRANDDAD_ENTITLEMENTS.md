# BrandDad entitlements

## Authoritative rules

- Woo/WPS subscription status is authoritative for paid BrandDad Social access.
- FormWise plan ids are remote access mirrors, not billing state.
- Directorist claim ownership and listing publication state are authoritative for Directory free-AI eligibility.
- Partnero remains authoritative for affiliate balances/commissions.
- Cursor Featured rotation consumes paid/trial/partnership grant records; Codex billing may activate a paid grant only after `wc_get_order( order_id )->is_paid()`.
- New basic Directory registration, listing creation, claiming, and management have no payment entitlement requirement. Historical activation purchases remain accounting history and keep any already-promised benefits.

## Paid AI

An eligible request needs an active `wps_subscriptions` record for the authenticated WordPress user and a recognized `_bds_ai_formwise_plan_id`. Monthly customer credits are reserved atomically before provider work and committed only after a usable result is stored. Failed work releases the reservation.

## Seven-day Business Essentials trial

- Same product/entitlement family as paid Business Essentials.
- Trial dates and billing terms come from the exact Woo/WP Swings product and subscription data (`wps_schedule_start`, `wps_susbcription_trial_end`, seven/day terms).
- The bounded trial allowance is 25 BrandDad customer credits (five current 5-credit executions). Trial tools are server-allowlisted.
- A zero-dollar order/payment-method authorization does not receive the paid FormWise plan or paid allowance. The paid plan is provisioned after a verified recurring payment.
- One trial history is retained per WordPress customer; repeat signed-in add-to-cart and duplicate activation access are denied.
- Trial does not grant Directory Pro/Elite, Featured, Travel search, or an invented 10% discount.
- Expiration removes trial-only access but preserves the WordPress account, free Directory account/listing, customer-created records, and eligible free-AI allowances.

## Directory free AI

Per verified business per UTC month: one website-audit allowance reserved for a future real audit adapter, five social drafts, one local SEO action plan, five review responses, and ten assistant requests. Current execution API exposes only tools that map to verified workflows; it does not mislabel a customer-supplied website plan as a completed audit.

## Stacking

Each execution chooses exactly one source: `paid`, `trial`, or `directory_free`. Paid/trial access takes deterministic precedence, so an active subscriber or trial user cannot also spend the free Directory allowance. Trial and paid ledgers have distinct keys, so a renewal cannot inherit trial usage or deduct twice. There is no automatic fallback or double deduction. Separate purchased subscriptions remain separate unless a future approved migration explicitly combines them.

## Cancellation and failure

- Cancellation/expiry/on-hold revokes the exact FormWise plan through the allowlisted subscription bridge.
- Renewal success provisions/reconciles once; failed renewal removes access according to subscription state.
- Historical orders, outputs, and audit records are preserved according to retention policy.

## Website monitoring

Monitoring requires an active allowlisted paid plan and an explicitly authorized onboarding website. Trials and Directory-free access do not receive recurring monitoring. Plan loss or a changed onboarding URL disables future checks. The customer may request at most one manual check per hour; the daily worker remains bounded. Availability evidence does not represent SEO rankings, traffic, leads, or revenue.

## Customer inquiries

One hosted inquiry form is available only to active allowlisted paid plans. The public form reference identifies the receiving owner but grants no account access. Historical inquiries remain customer-readable after cancellation, while new public submissions fail when the entitlement/form is inactive. Inquiry status is customer-managed; a stored inquiry is not a confirmed sale. Closed/lost PII follows bounded retention.
