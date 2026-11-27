# Done-for-You Management and Optional BDS Network Visibility

## Decision

BrandDad will sell a complete managed-service plan without paid network
engagement. BDS Network visibility is an optional upgrade, available as US
Focused or Worldwide. Supplier service identifiers, quantities, unit costs,
and provider credentials remain server-side and are never rendered to the
customer.

The approved public structure is:

- Done-for-You Complete Management: $399/month, no BDS Network engagement.
- Essential Visibility: +$200/month, $599/month total, up to 12 eligible posts.
- Growth Visibility: +$300/month, $699/month total, up to 20 eligible posts.
- Maximum Visibility: +$450/month, $849/month total, up to 30 eligible posts.

These prices are presentation and qualification offers. Checkout and supplier
execution stay closed until the payment lifecycle, supplier terms, API
credentials, idempotency, cancellation, refund, and provider-result tests pass.

## Data flow and trust boundaries

Current safe flow:

`[public Services page] -> [contact/qualification]`

Planned fulfillment flow:

`[verified paid Woo order] -> [server-owned plan and region mapping] -> [manual
review/approval queue] -> [provider API] -> [provider confirmation] -> [private
delivery ledger] -> [customer-safe monthly report]`

Trust boundaries are browser to WordPress, WooCommerce to the fulfillment
worker, WordPress to the provider API, provider response to the private ledger,
and the private ledger to the customer report.

The browser may choose only an approved public plan and region. It must never
submit a supplier service id, quantity, provider cost, customer id, order id,
or delivery status. The server derives those from the verified paid line item.

## API and execution decisions

- No public provider-order endpoint is added in this change.
- The catalog is inert and marked `manual_review_only`; it performs no network
  request and creates no order, entitlement, subscription, or payment.
- A future worker must require a verified paid Woo line item, server-derived
  mapping, an order-scoped idempotency key, and a unique delivery-period key.
- Automatic retry is forbidden after an uncertain provider response because a
  retry could create duplicate paid fulfillment.
- Provider credentials must be server-side, scoped, revocable, and excluded
  from logs and responses.
- Provider responses are untrusted. Future ingestion must use an allowlisted
  schema, bounded size, bounded timeout, and sanitized error codes.
- Cost records must use the existing append-only verified provider-cost ledger.

## Metrics policy

The dashboard may show only confirmed activity from an authoritative source:

- content created, approved, and published are separate stages;
- BDS Network campaigns submitted and confirmed are separate stages;
- confirmed reach, impressions, views, saves, reposts, and comments are labeled
  as campaign-assisted metrics when applicable;
- conversations handled and leads captured require BrandDad-owned event records;
- organic platform metrics and campaign-assisted metrics are never combined;
- estimated, pending, failed, refunded, or unverified quantities are not shown
  as completed results;
- followers, reach, leads, sales, rankings, and placement are not guaranteed.

This makes reports impressive through clear evidence and useful work, not by
inflating or relabeling numbers.

## STRIDE review

- **Spoofing:** future execution requires the authenticated server worker and a
  verified paid Woo line item; no browser-supplied identity or supplier id.
- **Tampering:** the server-owned catalog fixes the plan, region, service id,
  and quantities; customer input cannot override them.
- **Repudiation:** future delivery records must include the Woo order line,
  period, provider request id when available, timestamps, actor, and status.
- **Information disclosure:** public output excludes supplier ids, unit costs,
  credentials, raw provider bodies, and internal error details.
- **Denial of service/cost abuse:** one fulfillment per paid line and period,
  idempotency, a review queue, cost ceilings, rate limits, and no uncertain retry.
- **Elevation of privilege:** no customer or public route can activate or edit
  the private catalog; future administrative overrides require Woo management
  capability and an audit record.

## Abuse twins and mitigations

- Customer changes a hidden field to request the most expensive supplier job:
  ignore browser quantities and derive the mapping from the paid product.
- Replayed webhook creates duplicate fulfillment: deduplicate by verified event,
  order line, plan, and billing period before enqueueing.
- Provider times out after accepting an order: mark `needs_review`; never retry
  automatically.
- Provider reports an implausible result: keep it unverified and exclude it from
  the customer report until reconciled.
- Customer cancels or receives a refund: stop future periods and preserve the
  historical audit trail without representing undelivered work as completed.

## Residual risks and launch gates

Supplier licensing, platform-policy compliance, quality, refill behavior,
cancellation behavior, and API semantics remain unverified. The Facebook live
view service is not mapped because no service id was supplied and its cost is
unusually high. USA/UK alternatives are recorded only as manual fallbacks and
must never be ordered automatically after a primary service may have accepted a
request.

Production provider execution remains blocked until these items are verified
and tested. Public qualification and the private inert catalog are safe to ship.
