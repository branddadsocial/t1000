# AI Website Improvement Plan — secure design

## Scope and trust boundaries

The existing WooCommerce product `branddad-smartsites-website-optimizer` becomes a $19.99 one-time AI plan; the previous `website-speed-optimization` route redirects to it. A paid, unrefunded line item owned by the current WordPress customer grants exactly one execution of the existing published `website_plan` FormWise flow. WooCommerce remains authoritative for payment; WordPress owns identity, entitlement, metering, and results; FormWise receives only three bounded business inputs.

The product does not silently edit a website. The optional implementation request records intent only. A later implementation adapter must require a secure site connection, an exact change allowlist, explicit customer approval, verification, and rollback. Passwords, private keys, payment details, and hosting credentials are excluded from the plan form and FormWise prompt.

## Authentication and authorization

- Human identity uses the existing WordPress/WooCommerce session; no new token or password system is introduced.
- Checkout requires an account for exact catalog-allowlisted one-time AI products.
- Entitlement is derived server-side from processing/completed orders whose `customer_id` equals the session user.
- Job reads use the existing owner check and return 404 for a different customer's job.
- Product, workflow UUID, provider origin, price, order id, line item id, and credit cost are never selected by the browser.

## Input, API, and LLM boundaries

- The form uses a nonce, an enum allowlist, two required strings, browser limits, and the existing server-side 32 KB structured-input and 64 KB raw-request limits.
- A server-generated UUID supplies the body-bound idempotency key.
- The provider origin and published flow UUID are fixed server-side; redirects are disabled, response size and time are bounded, and uncertain network results are not retried automatically.
- User text is untrusted prompt content. The published flow treats it as facts to analyze, not instructions to browse, execute, quote, or request credentials.
- Model output is stripped, limited to 12,000 characters, stored in a private job, and HTML-escaped on display. It cannot execute code or make website changes.

## Storage and retention

- The order item stores an immutable product slug. A private custom post stores bounded inputs/output and server-side metering state. No raw credential is accepted or stored.
- The existing WordPress database, backup, and deletion policy applies. Logs must not contain request bodies, authorization headers, or provider credentials.
- A reversible product-catalog backup stores only public product fields, not customer data.

## STRIDE findings and mitigations

- **Spoofing:** session identity plus Woo order ownership; no caller-supplied user id.
- **Tampering:** nonce, allowlisted schema, body-bound idempotency, exact product slug, and pre-execution payment revalidation.
- **Repudiation:** immutable order-line identity, private job status, timestamps, and provider request id when returned.
- **Information disclosure:** owner-scoped job lookup, generic errors, bounded/escaped output, no credentials in prompts or UI.
- **Denial of service/cost abuse:** sold individually, one-use reservation, account requirement, request/response limits, queueing, and no retry on uncertain provider outcome.
- **Elevation of privilege:** no model tools or write adapter; implementation intent cannot grant access or perform a change.

## Residual risk and release gate

- Model advice can be incomplete; the UI identifies it as a plan and makes no ranking, traffic, sales, or score guarantee.
- This release does not claim automatic repair. Any future write adapter requires a separate secure-design review, supported WordPress connection, granular authorization, pre-change backup, deterministic verification, and rollback acceptance.
- Production checkout may be enabled only after PHP/contracts/security scan pass and the live page shows the correct title, price, account requirement, and add-to-cart path. No real card is charged for validation.

## 2026-11-27 catalog-deduplication follow-up

### Data flow and trust boundaries

Anonymous browser → CDN/WAF → WordPress/WooCommerce catalog query → public product cards. The legacy indexed URL continues through the existing same-origin 301 to the canonical Woo product. No customer, order, payment, session, credential, or third-party data crosses a new boundary.

### Decisions

- Keep the internal recommendation key `website-speed-optimization` for backward compatibility, but emit only the canonical product URL `branddad-smartsites-website-optimizer` in customer-facing recommendations.
- Preserve legacy product `53082` and historical orders. Do not delete it, rewrite order items, or reuse its ID.
- Hide only the exact legacy product slug from catalog/search visibility and make only that exact legacy product non-purchasable. The canonical product remains the sole buyable product.
- Retain the existing same-origin permanent redirect so old bookmarks and indexed links resolve safely. No caller-controlled redirect target is accepted.
- Back up the legacy product's public catalog visibility before the one-time migration. Fail closed if canonical and legacy products are missing or unexpectedly resolve to the same ID.

### STRIDE and abuse review

- **Spoofing/elevation:** no identity or authorization change; the public catalog stays public and checkout authorization remains in WooCommerce.
- **Tampering:** exact sanitized-slug comparisons and distinct server-resolved product IDs prevent a request from selecting another product.
- **Repudiation:** migration version, reversible visibility backup, and the unchanged product/order IDs provide an audit trail.
- **Disclosure:** no new response fields, secrets, PII, order data, or internal errors are exposed.
- **Denial of service:** constant-time exact-product checks only; no new query loops, external calls, or request-sized work.
- **Abuse twin:** an attacker requests `?add-to-cart=53082` to bypass the canonical offer. Exact legacy-product non-purchasability rejects that path while canonical product `53645` remains available.

Residual risk: the redirect remains managed by the existing Redirection component rather than this plugin. Release acceptance therefore requires a live 301 check, one canonical Shop card, one canonical add-to-cart ID, and no change to historical records.
