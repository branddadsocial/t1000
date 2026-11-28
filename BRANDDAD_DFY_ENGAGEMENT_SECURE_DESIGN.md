# Done-for-You Management and Optional Official Promotion

## Decision

BrandDad will not connect or automate third-party panel services that manufacture or conceal followers, likes, comments, saves, reposts, views, or impressions. The former BDS Network supplier catalog is retired from the active fulfillment design. No JustAnotherPanel credential, endpoint, order worker, or service identifier is used.

The customer-facing offers remain qualification-only while the official integrations are verified:

- Done-for-You Complete Management: $399/month. Twenty approved posts spaced across the month, LinkedIn content support, customer-conversation help, lead follow-up, website chat, and monthly reporting. No promotion budget is included.
- Complete Management + Promotion Starter: $449/month total. Adds management of up to four customer-approved promotional placements, spaced across the month.
- Complete Management + Promotion Growth: $499/month total. Adds management of up to eight customer-approved promotional placements, spaced across the month.
- Complete Management + Promotion Pro: $599/month total. Adds management of up to twelve customer-approved promotional placements, spaced across the month, plus priority optimization review.

Advertising spend, creator compensation, and platform charges are separate. BrandDad's displayed monthly total is the management/service fee, not a hidden media budget. Customers approve the platform, audience, creative, schedule, and separate spend before a campaign can run.

## Scheduling model

- Regular content: 20 slots distributed through each 30-day service period using equal-width time buckets.
- Promotion Starter: 4 slots, approximately weekly.
- Promotion Growth: 8 slots, approximately twice weekly.
- Promotion Pro: 12 slots, approximately three times weekly.
- The server calculates dates; a browser cannot submit a fabricated delivery schedule.
- Shorter or longer calendar months are recalculated from the actual service-period day count.
- Scheduling never bypasses platform posting limits, customer approval, or connected-account permissions.

## Data flow and trust boundaries

Current safe flow:

`[public Services page] -> [contact/qualification]`

Future authorized flow:

`[verified paid Woo line] -> [server-owned package] -> [customer authorization and approval] -> [official platform OAuth/API or approved creator agreement] -> [platform confirmation] -> [private delivery ledger] -> [customer report]`

The browser may choose only a public package. It cannot choose a provider endpoint, provider service id, quantity, cost, customer id, order id, delivery status, or platform token. All external actions remain disabled until an official platform connection or documented creator agreement exists.

The 2026-11-27 price revision is presentation and qualification only. It changes no existing order, subscription, renewal, refund, customer entitlement, Stripe price, WooCommerce product price, or automatic billing agreement. The public path remains Contact/qualification; an accepted written scope must still become an admin-created Woo order and a confirmed payment before fulfillment can begin.

## Execution controls

- No public fulfillment endpoint or outbound request is added in this change.
- Active packages use `qualification_only` or `official_api_only_after_authorization`; there is no generic supplier-panel execution mode.
- A future worker must require a verified paid Woo line, server-derived plan, active customer authorization, customer-approved creative, schedule and budget, a period-scoped idempotency key, and a unique delivery record.
- Provider tokens must be server-side, least-privilege, encrypted at rest, revocable, and excluded from logs and browser output.
- Network uncertainty must enter `needs_review`; do not retry a publish or ad-creation call until the platform state is reconciled.
- Costs must enter the existing verified provider-cost ledger. The customer report must distinguish BrandDad fees, platform spend, and creator compensation.

## Reporting policy

Reports may show only authoritative, clearly separated measurements:

- content created, approved, scheduled, and published;
- organic reach and impressions from the connected platform;
- paid reach, impressions, clicks, and spend from the official advertising account;
- approved creator deliverables from documented campaign records;
- conversations handled and leads recorded from BrandDad-owned events;
- conversion events only when an authorized measurement source records them.

Pending, estimated, failed, refunded, or unverified quantities are not completed results. Paid metrics are never described as organic. Followers, reach, leads, sales, rankings, placement, or algorithmic distribution are not guaranteed.

## STRIDE and abuse review

- **Spoofing:** future execution requires an authenticated worker, verified paid line, and the customer's authorized official platform connection.
- **Tampering:** package schedules and limits are derived server-side; browser quantities are ignored.
- **Repudiation:** delivery records must include order line, period, approval, actor, platform confirmation, timestamps, and status.
- **Information disclosure:** public output excludes tokens, account ids, audience details, raw provider bodies, and internal costs.
- **Denial of service/cost abuse:** explicit budgets, one execution per approved slot, rate limits, idempotency, and no uncertain retry.
- **Elevation of privilege:** public or customer routes cannot activate provider integrations or change internal fulfillment state.

Abuse twins:

- A customer edits a hidden field to increase promotion slots: ignore it and derive limits from the paid product.
- A webhook is replayed: deduplicate by verified event, order line, platform object, and billing period.
- A platform times out after accepting a campaign: reconcile externally before any retry.
- A paid metric is presented as organic: reject the report mapping and retain separate metric namespaces.
- A campaign lacks explicit spend approval: remain `awaiting_customer_approval`; never create or fund it.

## Launch gates

Public explanation and qualification are safe to ship. Automatic official promotion remains blocked until OAuth and authorization, platform permissions, spend caps, approval UX, idempotency, cancellation, refund, reporting, and hosted acceptance tests pass. The retired panel catalog must not be reconnected or exposed through an API.
