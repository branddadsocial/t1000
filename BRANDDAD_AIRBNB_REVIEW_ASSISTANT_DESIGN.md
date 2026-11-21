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

## Hosted activation acceptance harness

The launch gate is exercised by a one-time helper that is inert everywhere except the exact isolated host `staging-branddad-social.stackstaging.com`. It runs only while its versioned report option is absent, uses the existing non-administrator `bds_stage_acceptance_a` fixture account after verifying its `_bds_staging_auth_fixture=a` marker, suppresses outbound email, never invokes a payment gateway, and deletes every marked order, refund, AI job, reservation ledger, and scheduled job it creates.

### Authentication and authorization decisions

- Human identity remains the existing WordPress session/user primitive; the harness temporarily assumes only the verified staging fixture user inside one request and restores the original user in `finally`.
- Customer resources remain ownership-scoped: generated jobs must use the fixture as `post_author`, and the normal job response path is checked while that fixture is current.
- The helper exposes only a boolean/error-code report on the exact staging host. It exposes no input, output, order id, job id, email, provider credential, or session material.
- No request field selects a user, order, product, provider, flow, price, or result. All values are fixed server-side.

### Data and retention decisions

- Synthetic case text contains no real guest, reservation, address, contact, or customer data. It is classified as test content and is deleted in the same request.
- Synthetic orders/refunds/jobs are tagged with a unique versioned marker before use and may be deleted only after that exact marker is revalidated.
- The permanent report stores only completion, named boolean checks, bounded error codes, and a completion timestamp. It stores no FormWise output or case text.
- No production FormWise credential is copied into or loaded by the staging helper. Its fixed non-secret bearer fixture exists only so the production executor reaches the exact HTTP request boundary; that request is intercepted before the network stack.

### API, ingestion, and provider decisions

- The harness calls the same `WP_REST_Request` handler used by customers with the exact production schema and idempotency rules; it does not create a privileged alternate fulfillment path.
- All case fields are fixed, bounded, non-sensitive strings accepted by the production allowlist. There are no uploads, URLs, HTML, arbitrary JSON fields, or client-controlled identifiers.
- The successful case targets the production-fixed HTTPS FormWise origin and flow UUID with the production request shape. A single exact-URL `pre_http_request` filter validates the fixed bearer fixture and `inputs` object, then returns a bounded synthetic response before any network request.
- The same exact-URL filter creates the deliberate failure case when the helper's failure flag is set. It cannot intercept unrelated requests and becomes inert as soon as the versioned report exists.
- Provider-content acceptance is separate: the published FormWise flow is run once through the authenticated organization connection using only synthetic Airbnb facts. The hosted transport fixture proves WordPress integration without weakening the staging credential boundary.

### Deployment and rollback decisions

- The helper filename begins with `bds-000-staging-` and has a second runtime host guard; accidental production upload remains inert and registers no route.
- Production checkout stays fail-closed. This harness changes neither the live price nor purchasability.
- The hosted helper performs no provider network execution, uses synthetic WooCommerce payment methods only, suppresses mail while active, unschedules its exact job arguments, and cleans marked fixtures before saving the report. The separately recorded provider-content run is the only FormWise credit spent for this acceptance batch.
- Rollback is removal of this staging-only file after the report is captured. The report route disappears with it; no production artifact depends on the helper.

### STRIDE capstone and abuse twins

- **Spoofing/elevation:** exact host plus verified non-admin fixture marker; no caller-selectable identity; normal owner checks remain active.
- **Tampering/repudiation:** exact marker validation gates cleanup; report names every required invariant and records a timestamp without mutable customer content.
- **Information disclosure:** public report is boolean-only; inputs, output, credentials, ids, and WordPress errors are reduced to allowlisted error codes.
- **Denial/cost abuse:** one saved report makes the hosted run one-time; the exact provider request is intercepted with zero hosted provider spend; the lock prevents concurrent execution; no automatic retry of uncertain provider outcomes.
- **Abuse twin—free repeated cases:** after the first completed case, a new idempotency key must still be denied for the same purchase.
- **Abuse twin—refund then consume:** a fully refunded synthetic order must not grant a case.
- **Abuse twin—provider failure burns purchase:** the forced network failure must release the reservation and leave the paid case discoverable again.

Residual risk: the hosted harness and separate real-provider run do not prove a same-request staging credential path. Production still needs its configured server-side key plus the remaining safe payment lifecycle. The provider can return a technically nonempty but low-quality answer, and Airbnb may reject a correct draft. Human copy review remains required before launch approval.
