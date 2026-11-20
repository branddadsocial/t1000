# BrandDad backend security review

Updated: 2026-11-19
Scope: the backend MU plugins added to `codex/final-backend-20261119`.

The follow-up worker and onboarding API added in the second batch use the same secure-design baseline. Follow-up has an explicit default-off kill switch, fresh consent checks before send, a one-message limit, bounded retry on definite local failure, an uncertain-delivery review state, conversion stop, hashed opaque unsubscribe tokens, opt-out persistence checks, and public unsubscribe rate limiting. Onboarding is authenticated, owner-scoped, field-allowlisted, body-bounded, optimistic-concurrency protected, and never fetches the stored website URL. The third batch adds a separate bounded trial ledger, exact subscription-derived expiry, a signed/idempotent consent-only cross-host membership event, and customer-owned status/reporting DTOs.

## Rafter gate

- `rafter secrets .`: passed; no hardcoded secrets detected. The CLI reported a Betterleaks output-version warning, but completed with exit 0.
- `rafter run`: attempted in required fast mode; blocked because the account has used 16/16 fast scans until 2026-11-23. Paid Plus was not run because it requires explicit approval.
- Project-specific Rafter docs: none configured.

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
- API6 sensitive flows: idempotency, atomic allowance reservations, replay prevention, active-subscription checks, and daily provider caps protect billable actions.
- Background subscription reconciliation is provider-key gated, locked, limited to 25 records per pass, and continues at five-minute intervals; idempotent event keys avoid repeated provider calls.
- API7 SSRF: no request-controlled outbound URL exists.
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
