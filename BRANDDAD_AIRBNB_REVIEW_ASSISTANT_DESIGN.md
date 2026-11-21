# Airbnb Review Appeal Assistant — secure design

Status: implementation design for the one-time, one-review BrandDad Social product.

## Product boundary

The product helps an authorized Airbnb host or listing manager prepare one truthful review-removal request or permitted appeal. It provides a readiness assessment, evidence checklist, long and short copy-and-paste drafts, a public-response fallback, and next steps. Airbnb makes the final decision. BrandDad does not log in, submit the request, impersonate the owner, provide legal representation, fabricate evidence, or guarantee removal.

The proposed public price is **$9.99 one time for one Airbnb review case**. Production checkout remains fail-closed until the product price, completed-order entitlement, refund behavior, FormWise execution, and customer result page pass the acceptance test.

## Data flow and trust boundaries

1. A logged-in customer buys the exact WooCommerce product `branddad-ai-review-dispute-assistant`.
2. WooCommerce is the payment source of truth. Only a paid/completed order owned by the current WordPress user grants an unused case.
3. The customer sends a bounded, redacted case form to the BrandDad REST API with a WordPress REST nonce and an idempotency key.
4. The API fixes the platform to Airbnb, validates enums and text lengths, reserves one order-line entitlement atomically, and creates a private customer-owned AI job.
5. The background worker sends only the allowlisted case fields to the fixed published FormWise flow UUID over HTTPS with the server-held execution key.
6. The bounded text result is stored on the private job. The owner can read it through the authenticated API. A failed run releases the reservation; a successful usable result consumes the case.

Trust boundaries are browser → WordPress, WordPress → WooCommerce order data, and WordPress → FormWise. Product ids, order ownership, provider URL, flow id, platform, price, and entitlement state are never accepted from the browser.

## Authentication and authorization

- WordPress login is required for case creation and result retrieval; cookie-authenticated REST calls require the normal REST nonce.
- The order customer id must equal the current user id. Guest orders do not grant API access until securely linked through the existing account flow.
- Job ownership is `post_author`; a different customer's job returns 404, not an ownership disclosure.
- Administrators use existing WordPress/WooCommerce capabilities. There is no customer-selectable role or account id.
- The public FormWise share link is not an entitlement boundary and is not used by this purchase flow.

## Payment and entitlement rules

- One completed/processing WooCommerce order line for the exact product slug grants one case. The product is sold individually and quantity is normalized to one.
- The entitlement key is derived server-side from order id and line-item id.
- A reservation is atomic and expires if scheduling or execution fails. Only a completed usable response commits it.
- A refunded or cancelled order cannot start a new case. A completed historical result is retained for the customer but cannot be rerun after refund.
- Duplicate webhook events, browser retries, and background retries cannot create a second case.

## Inputs and privacy

- Platform is fixed to `Airbnb`.
- Allowed goal and prior-status values match the published FormWise workflow.
- Review excerpt and supporting facts are required, plain text, redacted, and limited to 3,000 characters each.
- The customer must affirm that they are authorized.
- No uploads are accepted. The form warns customers not to send passwords, booking ids, government ids, medical data, children's information, or unrelated personal data.
- Request bodies are capped. Case text is excluded from operational logs and analytics.

## Idempotency, abuse, and cost controls

- `Idempotency-Key` is required and bound to the authenticated user, tool, entitlement, and normalized request body with an HMAC using the WordPress authentication salt.
- A per-entitlement lock prevents concurrent consumption. The normal API rate limit and one in-flight case per entitlement prevent provider-cost abuse.
- Provider redirects are disabled, the origin and UUID are fixed, the response size is bounded, and unknown network outcomes are not automatically retried.
- Failed jobs do not consume the paid case. A provider success followed by an uncertain metering commit is marked for review rather than silently rerun.

## Output and storage

- Jobs use a private post type and are visible only through owner-checked API responses.
- Provider output is stripped to bounded plain text and must be escaped by the frontend.
- The result page repeats that Airbnb decides removal and that the draft must be reviewed for accuracy before use.
- Raw case inputs and outputs should be deleted or anonymized after 180 days unless a shorter operational/legal retention period is selected. Aggregate counts may remain without case text.

## Threat model

| Threat | Mitigation |
| --- | --- |
| Cross-customer case access | Authenticated owner check; server-derived author; 404 on mismatch |
| Free use or replay after one purchase | Completed-order lookup, atomic reservation, body-bound idempotency, committed entitlement |
| Refund then reuse | Current Woo order state checked before reservation; refunded/cancelled orders excluded |
| Prompt injection in review text | Inputs are data only; fixed FormWise system instruction explicitly ignores embedded instructions |
| Fabricated evidence or policy claims | Workflow forbids invention and separates confirmed, claimed, and missing facts |
| Provider-cost denial of service | One-case entitlement, request/rate limits, fixed workflow, no uncontrolled retries |
| Secret or private-data leakage | Server-only key, no uploads, redaction warning, bounded storage, no content logging |
| XSS through provider output | Strip tags at ingestion; frontend must escape plain text |
| SSRF/provider substitution | Fixed HTTPS FormWise origin and fixed published flow UUID |

## Residual risk and launch gate

Airbnb policy and final decisions are external to BrandDad. The assistant cannot guarantee eligibility or removal, and the customer can still submit inaccurate facts despite warnings. Public checkout must remain closed until a staging test proves: payment → entitlement → one successful case → readable result → no second case → refund blocks a new case → failed execution releases the reservation.
