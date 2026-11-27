# BrandDad Assisted Submission Services — Secure Design

## Decision

The four one-time products are submission services, not draft-only packs. BrandDad prepares the work, obtains customer approval, and completes one bounded submission where the destination permits an authorized representative to submit. Airbnb remains an assisted submission because Airbnb requires an eligible account holder to make the review-removal request: the customer stays signed in, reviews the final request, and performs the final submit action while BrandDad assists.

Current approved public prices remain unchanged:

- Airbnb Review Appeal — One Assisted Submission: $9.99 one time.
- IMDb Submission Service — One Contribution: $29.99 one time.
- Billboard Vendor Submission — One Inquiry: $49.99 one time.
- Press Release Submission — One Submission Round: $39.99 one time.

No service promises removal, approval, placement, publication, coverage, reach, or sales. Paid wire-distribution charges, billboard inventory, media buys, and other third-party fees are not included.

## Data flow

1. A customer purchases one allowlisted WooCommerce product.
2. The order item records the immutable service slug, submission mode, approval requirement, and initial fulfillment status.
3. BrandDad requests only the facts and evidence needed for that service.
4. AI may organize or draft the material, but a person reviews factual claims and destination rules.
5. The customer approves the final material before external submission.
6. BrandDad submits once to the approved destination, except Airbnb, where BrandDad assists while the eligible customer remains signed in and performs the final submit action.
7. The fulfillment record stores status and a destination reference when available. It does not store customer passwords or payment-card data.

## Service boundaries

### Airbnb review appeal

- One review-removal request for one review.
- BrandDad prepares the policy-based request and assists with entry.
- The eligible customer stays signed in and approves and performs the final submission.
- Never request or store an Airbnb password, government ID, booking ID, medical information, or unrelated private information.
- Airbnb makes the final decision; removal is not guaranteed.

### IMDb contribution

- One eligible IMDb contribution using customer-supplied, verifiable public sources.
- Customer approves the facts and source links before BrandDad submits.
- Includes one evidence correction if IMDb requests clarification about the same contribution.
- IMDb makes the final decision; acceptance, a page, or a credit is not guaranteed.

### Billboard vendor inquiry

- One approved creative brief plus one inquiry to a selected screen vendor.
- Customer approves the vendor and inquiry before BrandDad submits.
- Screen inventory, production, media-buy, and placement charges are separate.
- Availability, price, and placement are not guaranteed.

### Press release submission

- One factual press release and one customer-approved submission round to one mutually approved no-fee destination or a small targeted media-pitch round.
- Paid wire distribution and placement charges are separate.
- Publication, pickup, coverage, ranking, and traffic are not guaranteed.

## Trust boundaries and controls

- Product identity comes from a hard-coded slug allowlist, never a browser-supplied price or service name.
- Fulfillment metadata is created only after WooCommerce creates an order line item for an allowlisted product.
- The implementation does not call any external submission endpoint, spend money, or send a live submission automatically.
- External submission requires a verified destination, customer approval, and a human-reviewed final payload.
- Customer evidence is treated as untrusted. Do not render it as HTML or pass it to shell commands.
- If uploads are added later, allow only PDF, JPG, and PNG; cap file count and size; scan content; store privately; authorize every download; and define deletion windows before launch.
- Avoid logging evidence bodies, passwords, authentication cookies, or sensitive identifiers. Log only the order/item, service slug, status, timestamps, and bounded destination reference.
- Retry logic must be idempotent and must not create duplicate external submissions.
- Any future automated connector must fail closed when credentials, authorization, approval, or destination support is missing.

## Failure handling

- Payment incomplete or refunded: do not begin or continue submission.
- Missing facts/evidence: set `awaiting_customer_details`; do not invent claims.
- Customer has not approved: set `awaiting_customer_approval`; do not submit.
- Destination rejects or requests evidence: record the response and request customer action; do not claim success.
- Network uncertainty after a future automated send: set `submission_uncertain`; verify externally before retrying.
- Third-party fee required: pause and obtain explicit customer authorization; do not spend automatically.

## Verification requirements

- Public cards and product pages describe submission, customer approval, and the correct limits.
- No public copy says the customer must submit alone.
- No copy claims guaranteed removal, approval, placement, publication, coverage, or sales.
- WooCommerce line items for allowlisted products receive bounded fulfillment metadata exactly once.
- Unrelated products receive no submission metadata.
- Static and security tests pass before deployment.

## Rafter review result (2026-11-27)

Review depth: ASVS L2 because the feature attaches fulfillment state to paid WooCommerce orders and may later handle customer evidence.

- A01 / V4 access control: no new route, form handler, admin action, or public mutation endpoint exists in this diff. Fulfillment metadata is written only from WooCommerce's server-side line-item callback and an exact hard-coded product allowlist (`deploy/bds-assisted-submission-services-mu.php:78-82,157-170`).
- A02 / V6 cryptography: no credentials, tokens, cryptographic material, or random identifiers are introduced. `rafter secrets .` found no secrets.
- A03 / V5 injection and XSS: public catalog values are fixed server-side and every value rendered by the new plugin is escaped for HTML (`deploy/bds-assisted-submission-services-mu.php:94-113`). The change adds no SQL, shell, deserialization, template evaluation, or file access.
- A04 / V1 insecure design: trust boundaries, approval, external-cost, retry, evidence, and failure rules are documented above. The initial state is `awaiting_customer_details`, customer approval is mandatory, and external fees default to excluded (`deploy/bds-assisted-submission-services-mu.php:165-169`).
- A05/A06/A07/A08: no production configuration, dependency, authentication, session, update-channel, or deserialization change is present.
- A09 / V7 logging: the order note contains only fixed operational instructions; it does not log request bodies, customer evidence, passwords, or destination credentials (`deploy/bds-assisted-submission-services-mu.php:174-186`).
- A10 / V9 SSRF/network: the new implementation performs no outbound request and accepts no URL. Automated destination connectors remain explicitly fail-closed until separately reviewed.
- V8 data protection: the implementation stores only service/status flags on the order item. Evidence ingestion and file upload are not implemented; their required private storage and deletion controls are documented before any future launch.
- V11 business logic: service identity, mode, initial state, approval, and fee inclusion are derived from the allowlisted purchased product, not browser input. Unrelated products receive no metadata; focused tests cover both paths.
- Automated checks: focused PHP tests and the frontend test pass. All PHP suites pass. Forty-four of forty-five Node contract suites pass; the remaining `release-checksums-contract.mjs` failure is a pre-existing checksum-manifest mismatch for clean, unrelated `deploy/bds-provider-cost-ledger-mu.php`. Remote `rafter run --mode fast` was attempted and blocked by the account limit (16/16). Paid Plus was not run.
