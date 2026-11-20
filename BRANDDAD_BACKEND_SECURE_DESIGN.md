# BrandDad shared backend secure design

Date: 2026-11-19
Scope: WordPress/WooCommerce subscription provisioning, FormWise execution, Directory free-AI allowances, cross-site account linking, sales events, and background jobs.
Baseline: OWASP ASVS L2, NIST SSDF, PCI scope minimized by Stripe/Woo tokenization, GDPR/CCPA data-minimization and deletion obligations.

## Existing systems retained

- Human identity remains the existing WordPress account and secure cookie session. No second password database or homegrown token format is introduced.
- WooCommerce plus Subscriptions for WooCommerce remains the authoritative billing state. FormWise plans are access grants only, never a second charge for the same product.
- Stripe handles card data. BrandDad does not store PAN, CVC, or raw payment credentials.
- Directorist remains authoritative for listing ownership and publication state.
- FormWise remains the AI execution and end-user plan system. Provider keys stay in server configuration, never WordPress options, HTML, JavaScript, logs, or URLs.

## Identity and authorization decisions

- Humans authenticate with existing WordPress sessions. REST endpoints require `is_user_logged_in()` plus a WordPress REST nonce.
- Authorization is RBAC plus resource ownership: administrators use existing capabilities; customers may act only on their own WordPress user, subscriptions, listings, jobs, and results.
- Tenant and owner identity always come from the authenticated session and authoritative records, never from a request-supplied user or tenant id.
- Cross-site linking uses a short-lived, single-use, HMAC-signed exchange token bound to source host, destination host, WordPress user id, normalized email hash, purpose, issued-at, expiry, and nonce. Tokens are never reusable sessions.
- Service calls use separate least-privilege FormWise keys for provisioning and execution. Missing credentials are a hard failure, not an anonymous fallback.
- Logout and password reset remain WordPress responsibilities. Account-link tokens expire in five minutes and are marked used before destination provisioning.

## Data classification and retention

- Identifiers: WordPress user id, email, listing id, order/subscription id, FormWise end-user id. Stored only where needed for reconciliation.
- Credentials: FormWise bearer keys, Stripe/Woo secrets, cross-site signing secret. Server configuration only; never committed or logged.
- PII/content: business profile inputs and AI prompts/results. Jobs store bounded sanitized text, output, status, timestamps, provider request id, and cost/usage metadata. No card data.
- Audit: actor id, action, resource id, request id, status, timestamp. Logs omit prompt bodies, authorization headers, cookies, and raw provider responses.
- Execution jobs and results default to 180-day retention; audit summaries default to 365 days. Customer deletion must remove live job content and schedule backup expiry without deleting legally required accounting records.
- WordPress database-at-rest and backup encryption remain hosting controls. TLS 1.2+ is required for every external provider request.

## API design

- Versioned namespace: `/wp-json/branddad/v1`.
- Customer resources are scoped under the authenticated user; request bodies cannot set owner, tenant, plan, paid status, allowance, or provider credential fields.
- All write endpoints require REST nonce, explicit field allowlists, bounded strings/arrays, stable error codes, and per-user plus per-IP rate limits.
- Expensive AI execution requires an idempotency key scoped to user + tool + request body for 24 hours. A replay returns the original job/result and never spends credits twice.
- Provider timeouts are bounded, redirects disabled, response size limited, and provider error text is not returned verbatim to customers.
- Webhooks verify the provider signature before parsing, reject stale/replayed events, and persist the event id before side effects. Subscription handlers reconcile from Woo's authoritative order/subscription state rather than trusting webhook fields.
- Errors use 401 for unauthenticated requests, 404 for another customer's resource, 409 for idempotency conflicts, 422 for invalid inputs, 429 for limits, and 503 for disabled providers. Production responses do not contain stack traces or file paths.

## AI execution boundary

- Only published, explicitly allowlisted FormWise Flow/Agent/App UUIDs can run.
- Each tool has an input schema, eligible plan/free allowance, customer credit cost, maximum input/output sizes, and a launch flag. Unknown tools fail closed.
- Free Directory execution first verifies a published, verified, user-owned listing, then atomically reserves an allowance. Paid execution verifies an active subscription and matching entitlement.
- Allowance is committed only after a usable provider result is saved. Failed or timed-out jobs release the reservation.
- Model output is untrusted text. It is sanitized, size-limited, stored for review, and cannot directly publish, message, purchase, modify listings, or call another provider.
- Prompt injection is contained by sending only the selected tool inputs and verified business context. Customer text cannot select a workflow id, endpoint, credential, or downstream action.

## Ingestion and outbound requests

- JSON request bodies are capped at 32 KB for text tools. No file uploads are included in this phase.
- URLs, when a tool needs one, must be public HTTP(S), pass scheme/host validation, resolve away from private/link-local/loopback ranges, and be fetched only by an approved adapter with redirects disabled.
- FormWise API origin is fixed to `https://builder.formwise.ai/api/v1`; callers cannot supply an origin or path.
- Email/WhatsApp follow-up remains opt-in. Consent source, timestamp, and unsubscribe state are checked immediately before enqueue and send.

## Deployment and secrets

- Development occurs on `codex/final-backend-20261119`; Cursor's dirty worktree and production files are not overwritten.
- Production writes require a tested package, PHP lint, contract tests, Rafter code review, secret scan, backup verification, and a rollback copy of the exact prior MU plugin.
- The host currently uses WordPress MU plugins on separate Social and Directory roots. Staging or an isolated QA identity is required before live entitlement or billing changes.
- Secrets are injected through server configuration. Rotation uses a new key, verification, cutover, and immediate old-key revocation. Logs record only key display prefixes when needed.
- Scheduled work uses WP-Cron/Action Scheduler only with idempotent jobs, bounded retries, exponential backoff, and a recoverable failed-job state. Visitor requests never drain an unbounded queue.

## Data-flow and trust boundaries

`Browser -> CDN/WAF -> WordPress REST -> entitlement/meter -> job store -> FormWise API -> sanitized result store -> browser`

`Stripe/Woo gateway -> verified Woo order/subscription state -> provisioning reconciler -> FormWise end-user plan`

`Social WordPress -> signed one-time link token -> Directory WordPress -> linked local account/entitlement`

Trust boundaries are browser/edge, edge/WordPress, WordPress/database, WordPress/FormWise, Woo/gateway, and Social/Directory.

## BrandDad acquisition lead pipeline

The public lead-capture boundary accepts only an email address plus small allowlisted business/need/source fields. It requires an explicit privacy acknowledgment, records marketing consent separately, restricts browser origins to owned BrandDad hosts, caps the raw request and each field, and applies both client and global rate limits before storage. Client IP addresses are used only as HMAC rate-limit keys and are never stored on the lead. A signed-in visitor cannot submit another account's email. Marketing consent is not activated or mirrored to the sender queue unless the existing verified-email workflow explicitly proves ownership through the fail-closed `bds_lead_email_verified` filter; an unverified request is stored only as a pending request and sends nothing.

Lead records are private, non-public, excluded from REST and search, keyed for deduplication with an HMAC of normalized email, and readable through the API only by `manage_woocommerce`. Public responses use an opaque random reference rather than a post id or email. Automatic stage updates may only come from verified registration, trial, checkout, paid provisioning, onboarding, or access-revocation hooks. Browser requests cannot self-declare a paid or active stage.

Unconverted records have bounded retention and are anonymized rather than silently retained forever. Follow-up consent can be withdrawn, and no outbound message is sent by the lead API. The separate follow-up worker remains default-off and must recheck fresh consent immediately before delivery. Payment/order data remains authoritative in WooCommerce; the lead pipeline is not a billing source.

## Website availability monitoring

Monitoring is available only to an authenticated customer with an active allowlisted paid entitlement. The browser cannot submit a destination URL to the monitoring endpoint; configuration reads the URL already stored in that customer's onboarding profile. Each configuration and scheduled check revalidates scheme, userinfo, port, hostname, and every available A/AAAA address. Local names, IP literals in private/reserved ranges, DNS failures, and any non-public address fail closed.

The fetch uses WordPress safe HTTP, a short timeout, zero redirects, a small response cap, and a fixed user agent. It never sends cookies, authorization, customer text, or provider credentials. The response body is not retained; only status, bounded latency, response byte count, a content hash, and timestamps are stored in customer-owned user metadata. Manual checks are rate-limited and all checks use stale-recoverable per-customer locks. The scheduler processes bounded batches with a recoverable cursor. No failure email or external side effect is sent until a separate verified notification adapter exists.

## Customer inquiry capture

Each eligible paid owner receives one opaque public form reference; it is an identifier, not an authorization credential. A public submission is accepted only on an owned BrandDad form origin while the owner's entitlement and form-enabled state are rechecked. The request requires a privacy acknowledgment, separate consent for the business to respond, a blank honeypot, bounded contact/message fields, at least one contact method, and client/global rate limits using non-stored HMAC IP keys.

Inquiry records are private posts owned by the receiving WordPress user. Customer list/read/update APIs always query or compare `post_author` to the authenticated user; another user's id returns 404. Public callers cannot set the owner or workflow status. Status updates require the current version to prevent lost writes. No submission sends email, WhatsApp, or another side effect. Customer value reports count actual stored inquiries only. Contact/message data is excluded from lifecycle aggregates and provider prompts unless a later separately approved workflow explicitly needs it.

## STRIDE findings and controls

- Spoofing: WordPress session + nonce, capability/ownership checks, signed single-use link tokens, separate service credentials.
- Tampering: webhook signatures, idempotency records, authoritative Woo reconciliation, immutable provider/request ids in audit rows.
- Repudiation: actor/action/resource/request/status timestamps for payment, entitlement, execution, consent, and account-link events.
- Information disclosure: generic errors, secret redaction, bounded customer-owned queries, no prompt or credential logging, no cross-tenant retrieval.
- Denial of service: per-IP/per-user limits, quotas, 32 KB input cap, fixed provider timeout, queue concurrency caps, cost ceilings.
- Elevation of privilege: server-derived owner/tenant/plan, explicit allowlists, no mass assignment, admin capability checks, fail-closed provider and entitlement flags.

## Abuse twins

- Customer runs one tool -> attacker retries rapidly to spend credits: idempotency, rate limits, atomic reservations, per-plan quotas.
- Customer links accounts -> attacker replays or changes a link: HMAC binding, five-minute expiry, single-use nonce, exact host allowlist.
- Gateway renews a subscription -> forged webhook grants access: signature verification plus authoritative Woo state reconciliation.
- Customer asks AI for a draft -> prompt injection asks it to publish or reveal data: execution has no publishing tool and receives only customer-owned context.
- Customer opts into follow-up -> stale consent causes unwanted mail: unsubscribe and consent state are rechecked at send time.

## Residual risks

- WordPress plugin compromise can reach multiple application records; mitigated by minimal plugins, code review, backups, hardening, and separate provider keys.
- The current FormWise organization key model is broader than ideal. Separate keys, fixed origins, exact UUID allowlists, disabled redirects, bounded requests, and kill switches are compensating controls.
- WordPress options are not an append-only audit store. Payment and subscription truth remains Woo/Stripe; external log shipping is a production-hardening follow-up.
- Shared identity across separate WordPress installs depends on a common secret and verified email mapping. Until staged tests pass, it remains disabled and both sites keep their existing logins.

## Release gates

No public execution or production billing change is enabled until: local contracts pass; a safe QA purchase provisions once; renewal resets once; failure revokes according to policy; cancellation leaves no unauthorized renewal; cross-account access tests fail correctly; provider costs are measured; and rollback is verified.
