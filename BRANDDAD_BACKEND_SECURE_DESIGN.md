# BrandDad shared backend secure design

Date: 2026-11-19
Scope: WordPress/WooCommerce subscription provisioning, FormWise execution, Directory free-AI allowances, cross-site account linking, sales events, and background jobs.
Baseline: OWASP ASVS L2, NIST SSDF, PCI scope minimized by Stripe/Woo tokenization, GDPR/CCPA data-minimization and deletion obligations.

## Staging authenticated-ownership acceptance harness — 2026-11-19

- Scope is one isolated host: `staging-branddad-social.stackstaging.com`, derived from configured `home_url()` rather than a caller-controlled Host header.
- The harness creates at most two marker-owned subscriber fixtures with random undisclosed passwords and `.invalid` email addresses. It never creates or elevates an administrator.
- Deterministic usernames are accepted only when the existing account already carries the harness marker; any unmarked collision fails closed.
- It exercises anonymous denial, authenticated permission acceptance, onboarding optimistic concurrency, onboarding tenant isolation, AI-job owner access, and cross-account 404 behavior.
- The only public evidence endpoint is exact-host gated and returns allowlisted booleans, bounded error codes, version, completion status, and timestamp. It exposes no ids, email, password, nonce, cookie, submitted profile, or job result.
- Execution is one-time with a non-autoloaded result and stale-recoverable lock. It performs no outbound request, payment, provider execution, message, deletion, or production mutation.
- Fixture cleanup is intentionally separate because deletion requires its own target verification and approval. The fixtures are inert subscribers/private records and cannot authenticate because their random passwords are never disclosed.

## Staging AI failure acceptance harness — 2026-11-19

- Scope is the same exact configured staging home host; caller-controlled Host headers and request parameters are not trusted.
- It reuses only the marker-owned, non-admin subscriber fixture created by the authenticated-ownership harness. It creates no user, administrator, order, subscription, or payment.
- The harness refuses to run if a FormWise execution key is configured. This guarantees the test cannot spend provider credits or transmit data.
- It reserves five credits in a fixture-only ledger, creates one private fixture job, invokes the production worker, and requires fail-closed `execution_not_configured` status.
- Acceptance requires the pending reservation to be released, used credits to remain zero, and a second worker invocation to leave both values unchanged.
- Public evidence is exact-host gated and boolean-only. No user id, job id, reservation token, inputs, credentials, or result content is returned.
- The helper performs no direct outbound request, message, deletion, or production mutation. Its fixture records are isolated from real customers.

## Staging lifecycle acceptance harness — 2026-11-19

- Scope is exact-host staging and the pre-existing marker-owned subscriber; it creates no user, administrator, order, subscription, payment, or refund object.
- It verifies that a subscriber cannot access the admin analytics route and that unknown event types are rejected.
- Duplicate lifecycle writes use the same HMAC identity and must return the same private event id with exactly one stored row.
- Test-only payment/refund lifecycle events use `staging_fixture` object types and assert aggregate deltas rather than absolute totals, so pre-existing staging events cannot hide a failure.
- Privacy acceptance submits deliberately untrusted email/message keys and requires them to be absent from stored metadata. Only the production recorder's allowlisted fields may persist.
- Public evidence is exact-host gated and boolean-only; ids, currency, amounts, metadata, and submitted values are not returned.
- The harness performs no outbound request, message, deletion, provider execution, or production mutation.

## Staging customer API acceptance harness — 2026-11-19

- The helper is pinned to the configured staging home host and reuses the two marker-owned, non-admin subscribers. It accepts no browser request input.
- Onboarding acceptance covers localhost/private-IP URL rejection, field-length and enum enforcement, unknown-field rejection, active-lock conflict, stale-lock recovery, exact version advancement, and tenant profile isolation.
- The accepted public URL is stored only; neither the helper nor onboarding performs a fetch.
- Value-report acceptance snapshots one customer's current totals, creates one completed and one failed private fixture job for that customer plus one completed job for the other fixture customer, and asserts deltas. Only the customer-owned completed job may add five committed credits.
- Status acceptance requires the authenticated customer's own id, no entitlement on this fixture, and absence of FormWise ids, reservation tokens, or ledger keys.
- Public evidence is exact-host gated and boolean-only. The helper exposes no user/job id, profile, URL, internal totals, credential, or job content.
- It creates no user, administrator, order, subscription, payment, network call, message, provider execution, or production mutation.

## Verified provider-cost ledger and profitability summary (2026-11-19)

### Data flow and trust boundaries

`[approved server adapter] -> [internal WordPress action] -> [private cost CPT]`

`[authenticated Woo admin] -> [WordPress REST permission] -> [bounded aggregate] -> [admin response]`

The recorder is not a public REST write endpoint. A server adapter supplies a fixed provider, authoritative catalog product slug, three-letter currency, nonnegative minor-unit amount, verified basis, occurrence timestamp, and an idempotency reference. WordPress validates and stores only a privacy-limited cost event. The read route exposes aggregates only to `manage_woocommerce`.

### Authorization and API decisions

- No customer write or read route exists. Provider adapters call an internal PHP function/action after they have a verified cost value.
- The aggregate route is GET-only and requires `manage_woocommerce`; it accepts only a bounded `YYYY-MM` period within the lifecycle ledger's 24-month reporting window.
- The response contains totals and product/provider groupings, never customer ids, job prompts/results, emails, provider request bodies, credentials, or invoice references.
- Unknown providers, bases, currencies, products, negative values, future/old dates, and malformed idempotency references fail closed.
- The recorder uses an HMAC idempotency key plus a stale-recoverable option lock to prevent duplicate cost recognition.

### Storage classification and retention

- Stored fields are accounting/audit metadata: provider key, catalog product slug, verified basis, currency, minor-unit cost, occurrence month, and HMAC reference. No credential, PII, prompt, conversation, or raw provider payload is stored.
- Records are private WordPress posts and are not exposed through the default REST API.
- No deletion endpoint is provided. Retention follows the business's accounting retention policy; customer deletion does not require removing these non-PII aggregate cost records.
- WordPress/database backup encryption and access controls remain hosting responsibilities. Secrets stay in server configuration, outside this ledger and Git.

### STRIDE and abuse cases

- Spoofing/elevation: only installed server code records costs; only Woo admins read aggregates. No request-supplied role or customer id is trusted.
- Tampering/repudiation: normalized fields, HMAC idempotency, source-reference hashing, and append-only events make accidental replay detectable; production still needs append-only external log shipping for stronger tamper evidence.
- Disclosure: raw references and customer data are never stored or returned.
- Denial/cost abuse: there is no provider call; queries are capped at 5,000 records per store and 24 months.
- Abuse twin: a compromised adapter attempts to inflate cost or add arbitrary products. Mitigation: fixed provider/basis allowlists, authoritative catalog membership, bounded amounts, and admin-visible grouped totals. Residual risk is that compromised privileged server code can still submit false verified costs; reconciliation against provider invoices remains required.
- Incorrect-margin abuse: estimated or unknown costs are rejected. Reports explicitly label absent cost coverage and never present incomplete data as final profit.

### Isolated staging acceptance design

The provider-cost acceptance helper is restricted to the canonical Stackstaging hostname and has no request-controlled write path. On its one automatic run it uses a fixed catalog product, fixed provider, fixed USD amount, fixed verified basis, current UTC timestamp, and fixed idempotency reference. It compares aggregate deltas before and after the fixture instead of exposing raw accounting rows.

- The only public evidence route is exact-host gated and returns allowlisted booleans plus bounded error codes; it exposes no customer, post, order, invoice, idempotency reference, or financial total.
- A marked non-administrator fixture account proves the admin read permission fails closed. The helper does not grant capabilities or create users.
- Invalid provider, product, basis, currency, amount, timestamp, and idempotency values are rejected before the valid fixture is recorded.
- The valid fixture is private, replayed through the normal HMAC/idempotency path, and checked for a single stored row, hashed reference only, ignored untrusted metadata, and exact provider/product/contribution deltas.
- The helper creates no order, subscription, payment, refund, provider call, message, entitlement, customer charge, or production mutation. The fixture affects only isolated staging and is deliberately not evidence of a real provider expense or accounting reconciliation.
- Residual risk: the staging aggregate contains a synthetic cost row while the helper remains installed. It is visibly isolated by hostname and fixed idempotency; the helper must never be uploaded to production, and real margin reporting remains blocked on invoice/processor reconciliation.

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

## Hosted-staging dependency activator

The clean hosted staging install needs the already licensed WooCommerce, Subscriptions for WooCommerce, and WooCommerce Stripe plugin code that was copied from the Social host. A temporary MU helper may activate only these three exact plugin entry points. It has no request parameters, REST route, form, credential, outbound request, deletion, or production deployment path.

Data flow: `staging WordPress bootstrap -> exact temporary MU helper -> exact plugin-file allowlist -> WordPress activate_plugin() -> staging active_plugins option -> bounded local activation report`.

Trust boundaries and controls:

- The helper refuses to run unless `home_url()` has the exact host `staging-branddad-social.stackstaging.com`; production and the custom staging hostname fail closed.
- Plugin entry points are hard-coded and checked with `validate_plugin()` before activation. No request, option, environment variable, or user can choose a file path.
- Activation is ordered WooCommerce, Stripe, then subscriptions, runs at most once, and stores only plugin identifiers, status codes, and bounded WordPress error messages in a staging option.
- No database, customer record, order, payment, gateway credential, or network call is copied. The source archive contains plugin code only.
- The helper intentionally remains inert after its completed report is written. Removal is a separate cleanup action after staging evidence is captured.

STRIDE findings: host spoofing is constrained by the exact canonical WordPress home host; path tampering is constrained by a literal allowlist plus `validate_plugin()`; repudiation is addressed by the bounded activation report; information disclosure is limited by omitting file-system paths and secrets; denial is bounded to one attempt; elevation is limited to WordPress's existing plugin activation primitive and a staging-only host. Residual risk is that a copied plugin may execute its normal activation migrations; this is accepted only on the isolated empty staging database and is verified before any production decision.

## AI Business Essentials product identity correction

The live WooCommerce product and the authoritative commercial catalog use the exact slug `ai-business-essentials`. Trial recognition and the consented free-Directory bridge previously checked the nonexistent slug `branddad-ai-business-essentials`, which fails closed but prevents legitimate fulfillment.

Security invariant: product identity comes only from the server-owned Woo product attached to the authoritative order. The request cannot supply or override the slug. Trial, subscription, Directory-consent, and lifecycle modules must use the same literal catalog identity; unknown or legacy-looking aliases remain rejected.

The correction changes no price, product, order, subscription, payment state, trial record, customer, or public checkout. It only aligns deterministic recognition code and analytics metadata with the existing product. Static contracts must assert the exact live slug and reject the obsolete identity across all deploy modules before staging acceptance.

## Hosted-staging Business Essentials product fixture

Purpose: create one deterministic WooCommerce product in the isolated empty staging database so the exact seven-day trial and subscription metadata can be exercised. This is test data, not a production catalog mutation.

Data flow: `staging WordPress bootstrap -> exact-host MU fixture -> Woo product API -> one hidden catalog product -> fixed WPS subscription metadata -> bounded local fixture report`.

Controls:

- Exact canonical host `staging-branddad-social.stackstaging.com`; production and the custom hostname fail closed.
- No request input, REST/admin endpoint, credential, outbound request, order, customer, subscription, payment, email, or deletion.
- Fixed product slug, name, price, monthly interval, seven-day trial, zero signup fee, 75-credit internal metadata, hidden catalog visibility, and sold-individually/virtual settings.
- An existing product with the same slug is updated only when it already carries the staging-fixture marker. Any unmarked collision fails closed without mutation.
- The helper creates or repairs one fixture, records a bounded local result, and then becomes inert. It never changes gateway settings or enables AI/provider switches.
- The staging site remains `noindex`; checkout still requires the existing account rule and the AI gateway filter. No payment acceptance is claimed until an explicit Stripe test-mode lifecycle is separately authorized and verified.

STRIDE: spoofing/tampering are constrained by the exact host and literal values; repudiation is addressed by the local fixture report and deterministic marker; disclosure is limited to non-sensitive catalog test data; denial is bounded to one product/one pass; elevation is limited to Woo's normal product API in the isolated staging database. Residual risk is public discovery of the temporary product URL, mitigated by hidden catalog visibility, staging noindex, absent production credentials, and the existing fail-closed gateway/entitlement controls.
