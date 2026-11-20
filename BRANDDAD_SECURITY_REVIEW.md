# BrandDad backend security review

Updated: 2026-11-19
Scope: the backend MU plugins added to `codex/final-backend-20261119`.

Release packaging verification: the Social and Directory archives were built from the reviewed `deploy/` sources, and every packaged PHP file matched its source SHA-256. The packaging batch added documentation and archives only; it did not alter backend execution code or relax any default-off gate.

### Verified provider-cost ledger batch

- Web/API access control: storage is private and excluded from default REST (`deploy/bds-provider-cost-ledger-mu.php:19-20`); the only route is GET-only and requires `manage_woocommerce` (`:134`, `:244`). There is no browser/customer cost-write route.
- Input/integrity: the internal recorder requires a fixed provider, authoritative catalog product, verified basis, three-letter currency, positive capped integer minor units, bounded timestamp, and bounded idempotency reference (`:59-83`). Unknown or estimated data fails closed.
- Replay/tampering: event identity and stored source reference use HMAC-SHA256 with the WordPress auth salt, and writes use a stale-recoverable lock plus existing-record lookup (`:47`, `:84-100`, `:121`). Raw references are not stored.
- Privacy/tenancy: cost records contain no customer id, PII, prompt/result, credential, or provider body. The aggregate returns only grouped accounting data to Woo administrators.
- Resource consumption: cost and lifecycle queries are capped at 5,000 records and the period is limited to the current 24-month reporting window (`:139-154`, `:168`, `:185`). There is no outbound provider call or customer-controlled URL.
- Misrepresentation control: the response explicitly reports incomplete cost coverage and states that recorded contribution is not final profit, settlement, or tax reporting (`:236-238`).
- LLM categories are N/A for this plugin: it does not send prompts, retrieve RAG context, execute model output, or invoke tools.
- Residual risk: compromised privileged server code can call the internal action with false but syntactically valid costs. Accounting/provider invoice reconciliation and append-only external log shipping remain production controls.

The follow-up worker and onboarding API added in the second batch use the same secure-design baseline. Follow-up has an explicit default-off kill switch, fresh consent checks before send, a one-message limit, bounded retry on definite local failure, an uncertain-delivery review state, conversion stop, hashed opaque unsubscribe tokens, opt-out persistence checks, public unsubscribe rate limiting, and opt-out propagation into the lead pipeline. Onboarding is authenticated, owner-scoped, field-allowlisted, body-bounded, optimistic-concurrency protected, and never fetches the stored website URL. The third batch adds a separate bounded trial ledger, exact subscription-derived expiry, a signed/idempotent consent-only cross-host membership event, and customer-owned status/reporting DTOs. The acquisition pipeline is default-off, origin/rate/body/field bounded, deduplicated by HMAC email, private, stage-authoritative, and retention-limited.

## Rafter gate

- `rafter secrets .`: passed; no hardcoded secrets detected. The CLI reported a Betterleaks output-version warning, but completed with exit 0.
- `rafter run`: attempted in required fast mode; blocked because the account has used 16/16 fast scans until 2026-11-23. Paid Plus was not run because it requires explicit approval.
- Project-specific Rafter docs: none configured.
- The exact provider-cost batch was scanned again: local and MCP secret scans returned clean; remote fast SAST/SCA remains blocked by the same 16/16 quota.

## Findings corrected during review

- Cross-site account linking now fails closed until the source registration system proves email ownership through `bds_account_link_email_verified` (`deploy/bds-account-link-mu.php:35`). This prevents a source account with an unverified victim email from becoming a destination account takeover.
- A conflicting destination session no longer reopens a consumed one-time token (`deploy/bds-account-link-mu.php:181`).
- Paid AI ledgers are keyed to a verified subscription allowance cycle, and that cycle advances only after successful paid provisioning (`deploy/bds-ai-execution-mu.php:237`, `deploy/bds-ai-subscriptions-mu.php:404`).
- A failed Action Scheduler/cron enqueue now marks the job failed and releases its reservation (`deploy/bds-ai-execution-mu.php:425`).
- Sales-chat session tokens are bound to the requesting client, browser origins are restricted, and the per-client counter is updated under the global lock (`deploy/bds-sales-assistant-api-mu.php:30`, `:54`, `:84`).
- Zero-dollar trial authorizations cannot provision the full paid FormWise plan; the paid grant waits for a verified renewal after the authoritative trial end (`deploy/bds-ai-subscriptions-mu.php`).
- Trial and paid usage use distinct ledger keys, preventing renewal from inheriting trial deductions (`deploy/bds-ai-execution-mu.php`).
- Paid allowance cycles are now keyed to a new authoritative paid order, not an access reactivation generation. Reconciliation selects the newest paid renewal and refuses stale-parent fallback after a failed renewal (`deploy/bds-ai-subscriptions-mu.php`).
- The Directory membership bridge is exact-host pinned, HMAC body/timestamp signed, constant-time verified, stable-event idempotent, consent gated, retry bounded, and can create only a private subscriber (`deploy/bds-directory-membership-bridge-mu.php`).
- The Featured adapter disables the older activation callbacks and will activate only one of four fixed SKUs for the authenticated approved listing owner, an active WPS subscription, a matching paid parent/renewal order, valid real geography, and available inventory (`deploy/bds-featured-billing-adapter-mu.php`).
- The lifecycle ledger is internal-only, uses HMAC idempotency plus a stale-recoverable lock, stores only allowlisted identifiers/amounts, exposes only an aggregate to `manage_woocommerce`, counts AI work only after metering commits, and records refunds without deleting the original payment event (`deploy/bds-lifecycle-events-mu.php`).
- Public lead capture cannot choose lifecycle state, checkout links, or follow-up delivery. It requires an owned HTTPS origin and explicit privacy acknowledgment, rejects a signed-in account/email mismatch, stores marketing consent separately, and fails consent closed until the registration system proves email ownership. It rate-limits with non-stored HMAC client keys and exposes PII only to `manage_woocommerce`. Stale unconverted contact data is anonymized in bounded jobs; email delivery remains in the separately disabled consent worker (`deploy/bds-sales-lead-pipeline-mu.php`).
- Website monitoring accepts no request-controlled destination: it reads the authenticated customer's onboarding URL, requires a paid allowlisted plan and permission attestation, validates every A/AAAA address as public on configuration and each run, rejects credentials/IP literals/nonstandard ports, and uses safe HTTP with no redirects, a short timeout, and a 64 KiB cap. It stores only bounded measurements/content hash, uses stale-safe locks and batches, and sends no message (`deploy/bds-website-monitoring-mu.php`).
- Customer inquiry capture rechecks one eligible owner from an opaque form reference, requires an owned hosted-form origin, honeypot, privacy acknowledgment, contact consent, bounded fields, HMAC client/global rates, and a body-bound idempotency key under a stale-safe lock. Private inquiry records use the owner as `post_author`; list/update APIs enforce that author, lock optimistic version writes, expose cross-tenant ids as 404, and send no message. Lifecycle events contain owner/id/status only, not submitted contact/message content (`deploy/bds-customer-inquiries-mu.php`).

## Web application walk — OWASP Top 10

- A01 access control: account-link creation requires an authenticated session and nonce (`bds-account-link-mu.php:131`); AI job reads compare the post author with the current user (`bds-ai-execution-mu.php:315`).
- A02 cryptography: HMAC-SHA256 plus `hash_equals` protects account/session tokens; secrets are server constants and the secret scan is clean. WordPress owns password/session hashing and cookies.
- A03 injection: inputs use explicit allowlists and WordPress sanitizers; output is escaped or returned as bounded plain text. No shell/eval/unserialize sink exists. The only direct SQL statement interpolates the trusted WordPress options table name and contains no request data (`bds-account-link-mu.php:245`).
- A04 insecure design: account linking is purpose/audience/issuer/expiry/replay bound and now requires email verification (`bds-account-link-mu.php:35`, `:86`). Cost-bearing public chat has per-client and global limits (`bds-sales-assistant-api-mu.php:54`).
- A05 misconfiguration: every production capability defaults off until server constants and provider credentials exist. No debug route or stack trace was added.
- A06 components: no dependency or manifest change in this batch. Remote SCA remains blocked by Rafter quota.
- A07 authentication: WordPress owns login/session invalidation. The handoff creates a session only after signature, audience, expiry, replay, and email verification checks (`bds-account-link-mu.php:169`).
- A08 integrity: provisioning accepts only an active subscription, paid matching Woo order, allowlisted product, and allowlisted FormWise plan; initial free-trial authorization is explicitly excluded until renewal (`bds-ai-subscriptions-mu.php`).
- A09 logging: account links keep a bounded audit without token/email; AI jobs retain stable status/error and provider request id but not credentials (`bds-account-link-mu.php:118`, `bds-ai-execution-mu.php:527`). Production alerting is still a deployment acceptance item.
- A10 SSRF: all outbound origins and remote UUIDs are fixed server-side, safe HTTP is used for AI execution/chat, and redirects are disabled (`bds-ai-execution-mu.php:508`, `bds-sales-assistant-api-mu.php:183`). Subscription calls use fixed FormWise URLs with redirects disabled.
- Featured billing accepts a listing id only after Directorist author/claim verification, copies the verified owner into the order item, and rechecks both at activation; client SKU, price, geography, and grant status are never trusted.

## API walk — OWASP API Top 10

- API1 BOLA: job responses verify authenticated ownership; free-AI reservations require listing author and verified claimant to match; account status and value reports always use `get_current_user_id()`.
- API2 authentication: all AI job routes require WordPress cookie authentication; WordPress REST nonces protect cookie-authenticated requests. Public sales routes are deliberately anonymous, default off, origin restricted, and rate limited.
- API3 property authorization: request fields are allowlisted and response DTOs exclude provider credentials, raw bodies, subscription metadata, and remote conversation ids.
- API4 resource consumption: raw/request/field/response sizes, timeouts, credits, per-client limits, and global daily limits are bounded (`bds-ai-execution-mu.php:132`, `bds-sales-assistant-api-mu.php:54`).
- API5 function authorization: customer AI routes are authenticated; admin-only lifecycle actions use Woo capability plus nonce; no client role field controls privilege.
- Lifecycle analytics has no public write route; its aggregate requires `manage_woocommerce`, is limited to 24 UTC months and 5,000 events, and returns no per-customer payload.
- API6 sensitive flows: idempotency, atomic allowance reservations, replay prevention, active-subscription checks, and daily provider caps protect billable actions.
- Background subscription reconciliation is provider-key gated, locked, limited to 25 records per pass, and continues at five-minute intervals; idempotent event keys avoid repeated provider calls.
- API7 SSRF: AI/provider origins are fixed. Website monitoring is the only customer-site outbound fetch; its URL comes from authenticated onboarding state, not the monitor request, and is protected by strict scheme/host/port checks, all-address public DNS validation, WordPress safe HTTP, zero redirects, and revalidation on every run.
- API8 configuration: stable `WP_Error` responses hide provider internals; capabilities default off; no permissive CORS configuration was added.
- API9 inventory: all routes and response states are documented in `BRANDDAD_SHARED_API_CONTRACTS.md` and `BRANDDAD_CURSOR_HANDOFF.md`.
- API10 third parties: fixed FormWise endpoints use timeouts, no redirects, response-size caps, JSON/type checks, and fail-closed errors. Network-uncertain generation is not retried automatically.
- Cross-host membership provisioning is safe to retry because the stable event id is persisted on the Directory; it never creates a listing or changes an existing user's role.

## LLM walk — OWASP LLM Top 10

- LLM01 prompt injection: customer text remains untrusted. The public assistant can return text only; it cannot choose tools, prices, entitlements, or links.
- LLM02 data disclosure: only explicit allowlisted fields go to FormWise. No credentials, full request object, cross-tenant record, or provider response body is returned.
- LLM03 supply chain: only verified published FormWise app/agent/flow UUIDs are compiled into the allowlist; no plugin/model dependency was added.
- LLM04 poisoning: this batch adds no writable RAG corpus, training loop, embeddings, or customer-to-customer knowledge store.
- LLM05 output handling: model output is stripped of HTML and bounded. Public-assistant URLs are removed; clickable offers come only from the authoritative catalog (`bds-sales-assistant-api-mu.php:108`). Cursor must render `reply` and AI job `result` as text.
- LLM06 agency: no model output can charge, publish, message, modify a listing, or invoke a downstream tool. Those actions require separate deterministic adapters and approval gates.
- LLM07 prompt leakage: no secret is placed in prompts. Prompt text is behavioral only, not an authorization boundary.
- LLM08 vectors: N/A; no vector/embedding path exists in this diff.
- LLM09 overreliance: job outputs are drafts/plans and are not auto-published. The sales assistant cannot invent clickable offers or links.
- LLM10 consumption: subscription/free ledgers, per-tool costs, input/output bounds, queue locks, timeouts, per-client limits, and a global daily cap bound usage.

## Remaining security release gates

- Complete `rafter run` fast after quota resets and reconcile any finding.
- Connect the existing registration/email-verification workflow to the fail-closed account-link filter on both hosts.
- Exercise replay, wrong-audience, cross-account, CSRF/nonce, concurrent metering, provider outage, and rate-limit behavior in an isolated WordPress staging environment.
- Configure secrets outside Git and validate backup/rollback before deployment.

## 2026-11-19 hosted-staging dependency helper review

- Scope: `deploy/bds-staging-dependency-activator-mu.php`, a temporary isolated-staging bootstrap helper. It adds no route, form, request input, credential, network call, deletion, or production switch.
- A01 access control: there is no callable endpoint; execution is restricted to the exact canonical staging host with constant-time comparison (`:13-18`).
- A02 cryptography: no credential or token is handled. The file-specific Rafter secret scan passed with no findings.
- A03 injection: plugin entry points are literal strings (`:29-33`) and each is checked by WordPress `validate_plugin()` before `activate_plugin()` (`:43-52`). No user-controlled path, SQL, shell, eval, or deserialization exists.
- A04/A05 design and configuration: the helper fails closed off staging, uses a one-time completion report (`:20-25`), activates in dependency order, and verifies the final active state (`:65-82`).
- A06 components: no new dependency is downloaded by the code. It activates the exact existing plugin code copied from the live Social installation into the empty staging database. Remote Rafter fast SAST/SCA was attempted and remains quota-blocked at 16/16; paid Plus was not run.
- A07/A08 identity and integrity: WordPress's core activation primitive is used; no custom authentication or update channel is introduced. The exact host and plugin allowlists prevent production or arbitrary-plugin activation.
- A09 logging: the bounded local report contains only the staging host, plugin identifiers, status/error codes, a 240-character sanitized error message, and timestamp (`:73-82`). It contains no secret or customer data.
- A10 SSRF: N/A; no outbound request or request-controlled URL exists.
- Static contract evidence: `tests/staging-dependency-activator-contract.mjs` passed all 12 assertions covering host/plugin allowlists, validation order, one-time behavior, final-state verification, and absence of request, REST/admin endpoint, outbound HTTP, and deletion primitives.
- Residual risk: standard plugin activation hooks may create or migrate staging tables. This is accepted only for the new isolated empty staging database and must be verified before any production decision.

## 2026-11-19 Business Essentials product identity review

- Scope: align the server-owned trial, consented membership, and analytics identities with the existing Woo/catalog slug `ai-business-essentials`.
- Authorization remains order-derived: `bds_ai_plan_from_order()` reads the Woo line-item product and resolves its server-side `post_name` against the fixed catalog. No request field can choose the plan or create a trial/membership entitlement.
- The old prefixed identity is not retained as an alias. This prevents ambiguous product recognition and keeps unknown identities fail-closed.
- No price, trial length, allowance, FormWise plan UUID, payment method, gateway, product row, order, subscription, customer, or external destination changed.
- Lifecycle analytics now records the same canonical product identity, preventing split reporting for one offer.
- Cross-module static evidence: seven product-identity assertions pass, including the absence of `branddad-ai-business-essentials` from all affected deploy sources. The full 18-suite Node contract run and affected PHP syntax checks pass.
- File-specific Rafter secret scans found no secrets. Required remote fast SAST/SCA was attempted and remains quota-blocked at 16/16; no paid Plus scan was used.
- Remaining gate: deploy the corrected modules to hosted staging, create an approved staging-only product/subscription fixture, and prove trial/consent/payment lifecycle behavior before production.

## 2026-11-19 staging Business Essentials fixture review

- Scope: `deploy/bds-staging-business-essentials-fixture-mu.php`, a one-time helper for the isolated empty staging database only.
- Access/configuration: there is no callable route or form. The helper runs only when canonical `home_url()` exactly matches `staging-branddad-social.stackstaging.com`, and it becomes inert after its verified completion report.
- Integrity: every product value is literal. A same-slug product is mutable only when it already has `_bds_staging_fixture=yes`; an unmarked collision fails closed. Woo's product API creates/repairs one simple product.
- Payment safety: the helper creates no order, customer, subscription, payment intent, Stripe configuration, gateway configuration, FormWise execution, message, or external request. It does not enable any existing default-off capability.
- Test visibility: the fixture is published only so Woo/subscription checkout mechanics can be exercised, but is catalog-hidden, virtual, sold individually, and hosted on the noindex isolated staging site.
- Verification: after save, the helper reads back product identity/status/visibility/price/settings and every WPS/BrandDad metadata field. Any mismatch leaves the report incomplete.
- Static contract evidence: 19 assertions pass for host/value allowlists, collision behavior, exact monthly/trial terms, read-back, absence of input/network/payment/deletion surfaces, and non-autoloaded reporting.
- Local Rafter secret scan passed. Required remote fast SAST/SCA was attempted and remains quota-blocked at 16/16; paid Plus was not used.
- Residual risk: the temporary product URL can be guessed. Hidden visibility, staging noindex, no cloned payment credentials/settings, account-required AI checkout, and fail-closed gateway/entitlement code limit impact. A real Stripe test-mode transaction remains a separately authorized acceptance step.
