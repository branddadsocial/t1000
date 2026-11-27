# BrandDad AI Website Improvement — secure design

## Scope and trust boundaries

The paid $19.99 product produces an AI-assisted website diagnosis and prioritized improvement plan. It does not silently edit a customer's website. A separate implementation step may apply an allowlisted, reversible change only after the authenticated site owner connects WordPress and explicitly approves the proposed change.

Data flow: customer browser -> BrandDad WordPress session -> BrandDad job record -> FormWise planning flow -> BrandDad approval screen -> BrandDad execution adapter -> customer's WordPress REST API. Every arrow is a trust boundary. FormWise may plan work, but it never receives reusable WordPress administrator passwords and cannot call arbitrary tools.

## Authentication and authorization

- Humans use the existing WordPress/WooCommerce session. The domain layer verifies that the current user owns the order and the connected site; an order ID from the request is never sufficient.
- WordPress connections use the official application-password or OAuth mechanism where available. The credential is scoped to one site and one customer and can be revoked without affecting the BrandDad login.
- An agent acts as a service principal on behalf of the approving user. Audit entries name the user, order, connection, proposal, and executed action.
- The default is fail closed: no verified ownership, active entitlement, connected site, or explicit approval means no execution.

## Data and secrets

- Store site URL, order/customer identifiers, proposal, approval status, execution status, before/after evidence, and a redacted audit trail.
- Store no card data and no raw WordPress passwords. Connection secrets belong in the existing server-side secret store, encrypted at rest and never returned to browser JavaScript, FormWise prompts, logs, or URLs.
- Logs must redact authorization headers, tokens, customer content, and query strings. Retain job evidence only as long as needed for support/accounting; revoked credentials are deleted immediately.

## API and ingestion controls

- Endpoints are customer-scoped (`/me/website-improvement-jobs/...`) and enforce resource ownership in the domain layer as well as authentication middleware.
- Request fields are allowlisted and size-limited. State-changing calls require a WordPress nonce and an idempotency key so retries cannot apply the same change twice.
- Customer URLs are untrusted. The scanner and connector allow only HTTPS, re-resolve and deny private/link-local/loopback/metadata IPs on every redirect, cap redirects, response size, and timeout, and never evaluate fetched content.
- Execution actions are an allowlist of reversible WordPress operations. Arbitrary PHP, shell commands, plugin/theme installation, database queries, file writes, and user-supplied callback URLs are forbidden.

## Execution, verification, and rollback

- Phase 1 (included at $19.99): public-signal scan, FormWise plan, risks, acceptance checks, and owner choices.
- Phase 2 (only after connection and approval): snapshot the exact setting/content being changed; apply one allowlisted action; verify the acceptance check; automatically restore the snapshot when verification fails.
- Hosting/CDN changes, custom code, paid plugins, destructive operations, or changes without a reliable rollback are escalated to a separately quoted human-reviewed service.
- Rate limits apply per IP, user, business, and endpoint. Customer/plan quotas cap scans, LLM runs, and executions.

## STRIDE findings and mitigations

- Spoofing: existing sessions alone are insufficient; every job also checks order ownership and site connection ownership.
- Tampering: proposals are immutable once approved; execution records store a proposal digest and before/after evidence.
- Repudiation: append-only audit events record actor, action, resource, time, request ID, outcome, and rollback.
- Information disclosure: no credentials in FormWise, logs, URLs, or browser responses; customer context is tenant-scoped.
- Denial of service: timeouts, response-size caps, rate limits, quotas, bounded retries, and a recoverable failed state.
- Elevation of privilege: no arbitrary tools; server-side action allowlist; credentials scoped to the specific customer site.

Abuse twin: a buyer tries to point the scanner at an internal IP or use a proposed "optimization" to execute arbitrary code. The SSRF controls reject the target, and the execution allowlist rejects any action outside the approved reversible set.

Residual risk: third-party WordPress plugins may behave unpredictably even for ordinary settings changes. The initial implementation therefore supports only changes with deterministic reads, writes, verification, and rollback; everything else remains advisory or human-reviewed.
