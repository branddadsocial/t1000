# BrandDad backend security review

## 2026-11-20 production-package release gate

The exact 2026-11-20 Social and Directory production packages were re-walked using the Rafter web-application, API, and LLM checklists. This is a release review of unchanged, previously tested source rather than a new feature diff.

- Access control/BOLA/BFLA: customer routes derive identity from `get_current_user_id()` and register explicit permission callbacks; AI job reads recheck the private post author (`deploy/bds-ai-execution-mu.php:356`), inquiry updates return 404 unless the private record belongs to the current user (`deploy/bds-customer-inquiries-mu.php:237`), and administrative lead/finance/report functions require `manage_woocommerce` (`deploy/bds-sales-lead-pipeline-mu.php:375`, `deploy/bds-provider-cost-ledger-mu.php:136`).
- Input/property/resource controls: onboarding, AI, lead, inquiry, and sales-chat bodies are capped before parsing; fields are allowlisted and sanitized; public lead/inquiry/sales flows have bounded client/global rate controls and return 429 when exhausted (`deploy/bds-onboarding-api-mu.php:16`, `deploy/bds-ai-execution-mu.php:138`, `deploy/bds-sales-lead-pipeline-mu.php:94`, `deploy/bds-customer-inquiries-mu.php:54`, `deploy/bds-sales-assistant-api-mu.php:54`).
- Crypto/replay: account and conversation tokens use HMAC-SHA-256 plus constant-time comparison and bounded expiry (`deploy/bds-account-link-mu.php:89`, `deploy/bds-sales-assistant-api-mu.php:90`); cross-site membership messages bind timestamp, body hash, and idempotency key and reject replay (`deploy/bds-directory-membership-bridge-mu.php:138`). MD5 occurrences are limited to deterministic internal option/transient key names, not signatures, passwords, or bearer tokens.
- SSRF/third-party consumption: monitoring resolves and revalidates public DNS, rejects unsafe targets, follows zero redirects, and caps response size (`deploy/bds-website-monitoring-mu.php:37`, `deploy/bds-website-monitoring-mu.php:72`, `deploy/bds-website-monitoring-mu.php:261`); FormWise, membership, and provisioning requests use fixed provider/owned endpoints, server-only credentials, zero redirects, response ceilings, stable error handling, and default-off feature flags (`deploy/bds-ai-execution-mu.php:557`, `deploy/bds-directory-membership-bridge-mu.php:102`, `deploy/bds-ai-subscriptions-mu.php:100`).
- LLM handling/agency/cost: AI inputs use tool-specific schemas; outputs are bounded and sanitized before storage/return; the public sales assistant strips provider-generated links and supplies checkout URLs only from the authoritative catalog; credits are reserved before execution and released on failure; no model output can charge a card, send a message, publish content, change authorization, or invoke a destructive tool (`deploy/bds-ai-execution-mu.php:161`, `deploy/bds-ai-execution-mu.php:300`, `deploy/bds-ai-execution-mu.php:496`, `deploy/bds-sales-assistant-api-mu.php:108`).
- Destructive/data-retention surface: production routines use exact ownership/type checks and bounded metadata anonymization; the release archive contains no staging acceptance helper or temporary repair utility. No dependency or executable installer was added.

`rafter secrets .` found no secrets. Required remote `rafter run` was attempted against the release branch and remains blocked by the existing 16/16 fast-scan quota; paid Plus was not used. Production deployment therefore still requires the quota-reset scan (or an explicitly approved equivalent), exact-file/database backups, default-off server configuration, and protected deployment authorization.

Updated: 2026-11-20
Scope: the backend MU plugins added to `codex/final-backend-20261119`.

### Release checksum contract review

- The test reads only two fixed repository-owned manifests and fixed `deploy/` paths; it accepts no request, environment, network, archive, or user-controlled path input.
- Manifest rows must match one strict uppercase SHA-256 plus PHP-basename grammar, filenames must be unique, and every hash is recomputed from the reviewed source bytes.
- The staging-only reporting helper is required in the staging manifest and explicitly forbidden from the production source manifest, reducing accidental production exposure.
- The test performs no write, extraction, subprocess, dynamic import, dependency install, deserialization, or external request. The current full suite and local secret scan pass.
- Required remote `rafter run` was attempted and remained quota-blocked at 16/16. Paid Plus was not used.

### Evidence-stage monthly reporting review

- L2/V1 design: the authenticated data flow, evidence semantics, compatibility boundary, STRIDE abuse case, and residual provider/analytics dependency are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md` before implementation.
- A01/API1/API5/V4: no route, permission, id, or query changes. The existing customer report permission and author-scoped private-job query remain authoritative; the additive projection receives only the already derived status counts (`deploy/bds-customer-status-api-mu.php:76-145`).
- A03/API3/V5: no new input reaches storage or a query. Stage names, units, statuses, and notes are fixed server literals; counts use `absint()` over the existing completed-status bucket. There is no HTML, SQL, shell, path, or deserialization surface.
- A04/API4/API6/V11: the existing 500-job and 24-month caps are unchanged. Five constant-size stage objects add no data-dependent workload.
- A05/API8/API9/V14: output adds no customer identifier, content, provider body, credential, or internal record id. `generated` is labeled `completed_ai_jobs`; later stages are zero/unavailable and state the missing evidence, preventing an operational metric from being misrepresented as publication or business impact.
- A06/A08/A10/API7/API10: N/A; no dependency, external request, URL, provider action, or serializer is introduced.
- Backward compatibility: all existing `ai_work`, cost, monitoring, inquiry, and unavailable-metric fields remain intact. Consumers may adopt `delivery_outcomes` additively.

### Staging lifecycle 5,000-record truncation acceptance review

- L2/V1 design and threat model: the bounded state machine, exact-host boundary, persistent restart state, cleanup ownership, STRIDE abuse case, temporary resource cost, and limits of the evidence are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md` before implementation.
- A01/API1/API5/V4: no caller can choose the fixture, target, batch, period, event, phase, or deletion id. The exact canonical staging host and exact marker-owned non-admin fixture are server-owned (`deploy/bds-000-staging-lifecycle-truncation-acceptance-mu.php:13-27`, `:149-180`, `:293-337`).
- A02/A07/V2/V3/V6: no credential, password, login, cookie, session, or browser token is introduced. Each event uses the production HMAC event-key function and cleanup verifies it with `hash_equals()` (`deploy/bds-000-staging-lifecycle-truncation-acceptance-mu.php:48-104`).
- A03/API3/V5: the route accepts no input. Target 5,000, batch 100, object base, marker, fixture username, captured current month, and fixed midpoint timestamp are server constants. WordPress APIs perform storage; no raw SQL, shell, HTML, deserialization, or caller-controlled path exists.
- A04/API4/API6/V11: only the exact acceptance callback can run the state machine; unrelated staging traffic cannot. Creation is disabled after the hosted ceiling; cleanup batches are capped at 20 and serialized by a 90-second stale-safe lock. Persistent state and fixed recovery prevent restarts from duplicating or losing ownership of created rows. The route returns bounded progress rather than holding one unbounded HTTP request.
- A05/API8/API9/V14: state contains only aggregate baseline data, fixed phase/offset/date, request-created ids, allowlisted booleans, and bounded error codes. Public output excludes ids, user id, event keys, email, amounts, paths, and internal state details. Production cannot register the route.
- A06/A08/API10: N/A; no dependency, update channel, external API, provider, gateway, or serializer is added.
- A09/V7: completion requires 5,000 unique tracked ids, the production summary's `truncated=true`, exact deletion, and byte-equivalent restoration of the original event/customer/gross/refund/net/truncation aggregate. The test deliberately does not interpret capped financial totals as complete (`deploy/bds-000-staging-lifecycle-truncation-acceptance-mu.php:229-276`).
- A10/API7: N/A; no URL or outbound fetch exists.
- Data/deletion safety: every offset is collision-checked before creation; date and full event ownership are read back before progress advances. Cleanup works backward over ids stored by this run and requires exact private post type/status/author/date, fixture user, event type, object type/id, and HMAC key before hard deletion. A mismatch moves to a visible blocked state and retains the record; it never widens the delete query.
- Hosted abort/recovery: creation is now fail-closed after the shared host exceeded its safe request window. Discovery is fixed to the dedicated object-type metadata and capped at 5,001. Every discovered row must fall in the exact 0–4,999 offset range and pass the full ownership/date/HMAC guard; offsets must be unique and contiguous from zero. Only then may the stored id list be reconstructed for the same guarded reverse deletion. The report must remain incomplete and disclose only the bounded `host_execution_ceiling` code.

### Staging lifecycle UTC-boundary acceptance review

- L2/V1 design and threat model: the exact-host data flow, trust boundary, cleanup ownership, UTC decisions, STRIDE abuse case, and limits of the evidence are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md`.
- A01/API1/API5/V4: the runner accepts no caller-selected object and only uses the exact marker-owned, non-admin `.invalid` fixture. The public route registers only on the canonical staging home host and returns no resource identifier or mutation control (`deploy/bds-000-staging-lifecycle-boundary-acceptance-mu.php:16-32`, `:82-94`, `:153-162`).
- A02/A07/V2/V3/V6: no password, credential, token, cookie, login, or session primitive is introduced. The lifecycle event key reuses the production HMAC construction and is checked with `hash_equals()` before cleanup (`deploy/bds-000-staging-lifecycle-boundary-acceptance-mu.php:45-68`).
- A03/API3/V5: there is no request input. Period, timestamps, event type, fixture username, object ids, and idempotency values are constructed server-side. Storage uses WordPress APIs; no raw SQL, HTML rendering, deserialization, shell, or caller-controlled path exists.
- A04/API4/API6/V11: one stored completion report and a stale-safe ten-minute lock bound the run. Four private non-financial events and two bounded production-aggregate calls are the maximum normal workload (`deploy/bds-000-staging-lifecycle-boundary-acceptance-mu.php:74-93`, `:99-134`).
- A05/API8/API9/V14: completion exposes only fifteen allowlisted booleans, bounded sanitized error codes, version, and timestamp. Production cannot register the route; reports contain no user id, post id, event key, amount, email, or internal path (`deploy/bds-000-staging-lifecycle-boundary-acceptance-mu.php:20-39`, `:153-162`).
- A06/A08/API10: N/A; no dependency, package, external provider, gateway, outbound request, or serialization format is added.
- A09/V7: the runner uses the production monthly summary rather than duplicating accounting logic. It requires an exact two-event delta, unchanged currency aggregates, and a byte-equivalent summary after cleanup (including unique-customer count) before completion (`deploy/bds-000-staging-lifecycle-boundary-acceptance-mu.php:120-145`).
- A10/API7: N/A; no URL or outbound fetch is accepted or performed.
- Data and cleanup safety: all four event keys are collision-checked before the first write. Deletion is limited to ids created by this request and requires exact user, event type, object type, object id, and HMAC-derived key. Cleanup runs in `finally`; the original user context is restored. No order, subscription, customer, payment, refund, gateway, email, provider execution, or production mutation is involved.

### Staging launch-catalog product fixture review

- L2/V1 design and threat model: the fixed catalog, host/database boundary, full-preflight rule, idempotent retry, no-delete policy, STRIDE abuse case, and production/checkout residual boundary are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md`.
- A01/API1/API5/V4: the runner and read-only report exist only on the canonical configured staging host, accept no caller id/input, and create no user/session/role (`deploy/bds-staging-launch-products-fixture-mu.php:16-27`, `:72-91`, `:174-191`).
- A02/A07/V2/V3/V6: N/A; no credential, token, authentication, password, cookie, or cryptographic primitive is introduced.
- A03/API3/V5: no request value reaches a product, query, or report. Slugs, names, prices, and credits are fixed literals; error codes are sanitized and bounded (`deploy/bds-staging-launch-products-fixture-mu.php:20-57`). WordPress/Woo APIs perform all storage writes; no raw SQL, shell, deserialization, HTML output, or file path exists.
- A04/API4/API6/V11: one stale-safe lock and complete-report guard bound execution to four products. Every authoritative catalog and existing-slug/type/marker check completes before the first write (`deploy/bds-staging-launch-products-fixture-mu.php:72-116`). Partial marked fixtures can be safely reverified on a later request.
- A05/API8/API9/V14: all products are published but catalog-hidden and explicitly described as non-production staging fixtures (`deploy/bds-staging-launch-products-fixture-mu.php:117-136`). Production has no report route. The report exposes four slug booleans and bounded errors, not post ids or internal paths.
- A06/A08/API10: N/A; no dependency, update channel, external API, provider, gateway, or network call is added.
- A09/V7: Git history plus the bounded report records intended fixture state without customer or payment data. Every product and metadata field is read back before completion (`deploy/bds-staging-launch-products-fixture-mu.php:142-160`).
- A10/API7: N/A; there is no server-side fetch or user-controlled URL.
- Financial/data safety: fixed monthly price/allowance must match the authoritative catalog; products have zero setup fee and zero trial, and no order/subscription/customer/payment is created. There is no deletion primitive. New products persist exact fixture ownership markers in the same Woo save before later metadata verification (`deploy/bds-staging-launch-products-fixture-mu.php:98-139`).

### Staging sales-assistant grounding and cost-cap review

- L2/V1 and LLM architecture: the data flow, public/provider trust boundaries, token decisions, STRIDE abuse cases, default-off deployment, exact restoration, and residual live-provider boundary are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md`.
- A01/API1/API5/V4: the acceptance report is canonical-home-host, one-time, parameter-free, GET-only, and returns no named customer object (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:17-35`, `:369-386`). Production chat is deliberately public and contains no customer resource id (`deploy/bds-sales-assistant-api-mu.php:149-215`).
- A02/A07/API2/V2/V3/V6: the helper credential is visibly dummy and exists only while the exact report is absent (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:25-35`). Production continuation tokens use HMAC-SHA-256, constant-time comparison, UUID/purpose/expiry validation, and client binding (`deploy/bds-sales-assistant-api-mu.php:84-105`). Acceptance covers valid, tampered, expired, and different-client tokens (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:282-294`).
- A03/API3/V5 and LLM01: production caps raw body/message, sanitizes the question, and constructs a fixed instruction plus server-verified name/price/saleability fields (`deploy/bds-sales-assistant-api-mu.php:149-182`). The harness accepts no browser fields and validates that provider prompts contain the constraints and no offer URL (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:45-81`). Prompt injection can influence prose but cannot create a clickable offer or action.
- A04/API4/API6/V11 and LLM10: production uses a stale-safe serialized lock, ten-per-minute hashed-client cap, daily global cap, 30-second provider timeout, and 64 KiB response cap (`deploy/bds-sales-assistant-api-mu.php:54-82`, `:183-193`). Hosted acceptance requires exactly three calls, denies the fourth before egress, and separately proves the minute cap (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:300-313`). Residual risk: the provider agent's model-token ceiling is external configuration and still requires cost observation before enablement.
- A05/API8/API9/V14: staging enable/key/cap constants are request-process only and disappear after the report is stored. Unexpected FormWise calls fail closed; the report contains only allowlisted booleans and bounded error codes (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:25-95`, `:97-154`). Production remains default-off.
- A06/A08/LLM03: N/A; no dependency, model registry, prompt registry, deserialization, skill, or agent configuration is installed.
- A09/V7 and LLM02/LLM07: the report excludes IP, tokens, session/conversation ids, prompt, provider body, dummy key, prices, and URLs. Production sends only the public question plus verified offer name/price/saleability, not customer records or credentials (`deploy/bds-sales-assistant-api-mu.php:169-182`).
- A10/API7/API10: production egress is fixed to HTTPS FormWise, the fixed agent UUID, no redirects, and safe HTTP (`deploy/bds-sales-assistant-api-mu.php:183-193`). The harness intercepts all FormWise traffic while its dummy key exists and rejects unexpected calls (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:45-95`).
- LLM04/LLM08: N/A; no training, feedback loop, RAG corpus, vector store, or cross-tenant retrieval is present.
- LLM05/LLM06: model output is text-only, HTML-stripped, URL-stripped, bounded, and has no tool or write capability. Clickable offers are rebuilt from the authoritative catalog (`deploy/bds-sales-assistant-api-mu.php:108-146`, `:202-214`). Acceptance injects hostile HTML/phishing links and requires their removal (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:82-95`, `:278-279`).
- LLM09: the assistant is constrained to the lowest suitable verified offer and cannot make a sale, discount, or performance guarantee itself. Frontend disclosure and semantic-answer QA remain Cursor/live-provider acceptance work.
- State cleanup: only the exact documentation-IP rate transient, current-day counter, derived random conversation transient, current user, and three server globals are touched and restored (`deploy/bds-000-staging-sales-assistant-acceptance-mu.php:176-225`, `:318-357`). No post/user/order/job/lead/payment/message is created.

### Staging Woo refund-hook acceptance review

- L2/V1 architecture and threat model: the data flow, trust boundaries, STRIDE abuse cases, cleanup rules, and residual acceptance boundary are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md`.
- A01/API1/API5/V4: the evidence route returns no object and accepts no id; it is exact-home-host and GET-only (`deploy/bds-000-staging-woo-refund-acceptance-mu.php:25-31`, `:385-404`). The one-time runner selects only the marker-owned non-admin fixture and marker-owned product (`:198-231`). N/A for customer BOLA because no caller-selected object exists.
- A02/V6/V8/V9: no secret, token, card, gateway, or outbound request is introduced. The synthetic order uses the fixed no-gateway method and both refunds set `refund_payment=false` (`:251-281`, `:299-321`). WordPress mail is suppressed only while the exact runner is active (`:16-22`, `:241-261`, `:343-346`).
- A03/API3/V5: the helper accepts no caller payload. Fixed ids and amounts are normalized with WordPress/Woo primitives; the public report allowlists booleans and sanitizes and bounds error codes (`:34-85`). No HTML, SQL string, deserialization, or shell path exists in this diff.
- A04/API4/API6/V11: exact host, stored one-time report, and stale-safe lock bound the flow to one order and two refunds (`:25-31`, `:161-174`). Replayed refund hooks are asserted idempotent (`:290-321`). Remaining risk is concurrent unrelated staging accounting activity causing a safe false-negative delta, not a false pass.
- A05/API8/API9/V14: the helper is staging-only and production has no route. The public response excludes ids, email, amounts, notes, and internal metadata (`:34-85`, `:385-404`). No dependency or production configuration changes.
- A06/A08/API10: N/A; no dependency, update channel, deserialization, or third-party API was added.
- A07: N/A; no login, session, or authentication behavior is added. The route is deliberately public because it returns only one-time bounded evidence and cannot receive parameters.
- A09/V7: bounded error codes contain no PII; private lifecycle records hold only allowlisted event metadata (`:67-84`). The synthetic fixture lead is snapshotted and compared unchanged (`:121-135`, `:233-234`, `:346-350`).
- A10/API7: N/A; there is no outbound fetch or user-controlled URL.
- Destructive-operation gate: event deletion requires exact id, user, type, object type, and object id; refund deletion requires refund class, exact parent, and exact marker; order deletion requires exact marker (`:138-158`, `:352-374`). A failed guard leaves the synthetic fixture for manual inspection instead of broadening deletion.
- Verification: 41 refund contract assertions, all 32 Node contract files, 38 PHP lints, and `git diff --check` passed locally. Rafter results are recorded with the batch test evidence.

### Synthetic subscription-lifecycle acceptance review

- L2/V1 architecture and threat model: the data flow, trust boundaries, STRIDE abuse cases, cleanup rules, and residual acceptance boundary are recorded in `BRANDDAD_BACKEND_SECURE_DESIGN.md`.
- A01/API1/API5 access control: the public route exposes no object operation and registers only on the configured canonical staging home host (`deploy/bds-000-staging-subscription-lifecycle-acceptance-mu.php:18-25`, `:549-566`). The runner accepts no request parameters and requires the exact marker-owned non-admin fixture and staging product (`:298-337`).
- A02/V6 credentials: the dummy non-production key exists only while the one-time report is absent (`:36-43`). It is not returned, and local Rafter secret scanning is clean.
- A03/V5 injection: there is no direct SQL, shell, file path, unserialize, or request-superglobal path. Provider-shaped JSON is locally generated and validated for email, UUID tier, and idempotency key before use (`:49-113`).
- A04/API4/API6 business logic: a stale-safe lock and collision check bound execution to one small fixture set (`:244-340`). Trial, grant, replay, renewal, failed-payment, reactivation, cancellation, and failure/retry paths use the production functions (`:391-486`).
- A05/API8/API9 staging isolation: the helper is exact-host and report gated, production registers no route, and no dependency or production configuration changes are introduced.
- A09/V7 privacy: the report exposes 36 allowlisted booleans, bounded sanitized error codes, version, completion state, and time only (`:116-174`). It excludes ids, emails, credentials, provider bodies, order notes, and customer data.
- A10/API7/API10 outbound safety: while the dummy key exists, all FormWise end-user traffic is intercepted. Only the fixed HTTPS create/delete shapes are accepted; calls outside the request-scoped context or unexpected URL/method fail closed (`:49-114`). Production response validation remains unchanged.
- V11/V12 cleanup integrity: no gateway/payment API is called. Scheduled retries are removed only with exact hook arguments; lifecycle events are scoped to the fixture user; subscription/order deletion requires ids created in the same request plus the exact marker; prior trial history and current-user context are restored (`:177-242`, `:493-538`).
- Hosted helper 0.1.0 failed safely and restored every fixture. It exposed that `bds_ai_sfw_status_meta_updated()` selected `wps_parent_order` on active-state reentry even when a newer renewal governed billing; adapter 0.5.1 now calls `bds_ai_subscription_current_paid_order()` so a failed newest renewal yields no grant and a valid newest renewal cannot reset credits through stale-parent fallback (`deploy/bds-ai-subscriptions-mu.php:554-566`).
- Corrected hosted evidence: helper 0.1.1 completed all 36 checks with exact fixture/event/retry/user-state cleanup, no unexpected provider request, no real gateway, private-route authorization intact, and production acceptance route HTTP 404.
- Residual: real Stripe test checkout/webhooks, real FormWise mutation, browser nonce behavior, and production deployment remain separate gates.

Release packaging verification: the Social and Directory archives were built from the reviewed `deploy/` sources, and every packaged PHP file matched its source SHA-256. The packaging batch added documentation and archives only; it did not alter backend execution code or relax any default-off gate.

### Verified provider-cost ledger batch

- Web/API access control: storage is private and excluded from default REST (`deploy/bds-provider-cost-ledger-mu.php:19-20`); the only route is GET-only and requires `manage_woocommerce` (`:134`, `:244`). There is no browser/customer cost-write route.
- Input/integrity: the internal recorder requires a fixed provider, authoritative catalog product, verified basis, three-letter currency, positive capped integer minor units, bounded timestamp, and bounded idempotency reference (`:59-85`). Negative PHP integers are now rejected before `absint` normalization (`:69-72`), closing the discovered sign-conversion defect. Unknown, fractional, estimated, old, or future data fails closed.
- Replay/tampering: event identity and stored source reference use HMAC-SHA256 with the WordPress auth salt, and writes use a stale-recoverable lock plus existing-record lookup (`:47`, `:84-100`, `:121`). Raw references are not stored.
- Privacy/tenancy: cost records contain no customer id, PII, prompt/result, credential, or provider body. The aggregate returns only grouped accounting data to Woo administrators.
- Resource consumption: cost and lifecycle queries are capped at 5,000 records and the period is limited to the current 24-month reporting window (`:139-154`, `:168`, `:185`). There is no outbound provider call or customer-controlled URL.
- Misrepresentation control: the response explicitly reports incomplete cost coverage and states that recorded contribution is not final profit, settlement, or tax reporting (`:236-238`).
- LLM categories are N/A for this plugin: it does not send prompts, retrieve RAG context, execute model output, or invoke tools.
- Residual risk: compromised privileged server code can call the internal action with false but syntactically valid costs. Accounting/provider invoice reconciliation and append-only external log shipping remain production controls.
- Staging acceptance helper review: it is pinned to the configured canonical Stackstaging hostname (`deploy/bds-staging-provider-cost-acceptance-mu.php:14-18`), accepts no request input, requires the marker-owned non-admin fixture, exercises rejected values and the normal idempotent writer, and exposes only allowlisted booleans (`:20-67`, `:91-196`). Its public GET evidence route is safe only because the hostname gate prevents production registration and no ids, references, or totals are returned (`:203-221`).

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
- The monitoring integration hook is server-internal and treated as untrusted: non-array output is discarded, and slug/access mode are re-sanitized then checked against `paid` and the fixed plan allowlist (`deploy/bds-website-monitoring-mu.php:19-26`). The staging acceptance helper is canonical-host/one-run gated, limits its synthetic plan to fixture B without admin capability, disables the recurring scheduler, accepts no browser parameters, fetches only the owned staging hostname, restores fixture metadata, and exposes only allowlisted booleans (`deploy/bds-staging-website-monitoring-acceptance-mu.php:14-44`, `:49-93`, `:196-340`, `:350-365`).
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

Hosted result: the product exists only on the isolated staging hostname, is hidden from catalog browsing, noindex, virtual, sold individually, and limited to one item. A disposable Store API cart showed no available payment methods and an uninitialized Stripe checkout-session extension. No payment/order/customer/subscription mutation occurred. The helper's read-back gate covers the exact trial metadata, but independent database-row evidence remains an explicit acceptance item because the host's phpMyAdmin SSO did not open the selected staging database session.

## 2026-11-19 customer-inquiry staging acceptance review

- Scope: inquiry entitlement filter plus an exact-host staging helper that exercises public input validation and private fixture lifecycle.
- Access control: production code still requires the default-off feature flag, paid allowlisted entitlement, opaque form ownership, owned HTTPS origin, and authenticated author-scoped private APIs. The synthetic plan applies only to non-admin fixture B on the canonical isolated staging host while no report exists.
- Input/resource controls: the production permission callback enforces the 12 KiB raw-body limit, owned origin, HMAC-IP client rate, and bounded global rate. The helper verifies honeypot, privacy acknowledgment, contact consent, contact method, field limits, and body-bound idempotency.
- Tenant isolation/integrity: records are private and owner-authored; list/update are customer-scoped; cross-account update is indistinguishable 404; updates use stale-safe locks plus optimistic versions; public responses are checked for PII/internal-id leakage.
- Deletion safety: the only hard delete iterates exact ids created during this run and requires matching post type, fixture-B author, and `_bds_staging_inquiry_fixture=1`. Cleanup runs in `finally`; no broad query or user deletion exists.
- Privacy: test contacts use `.invalid`, the IP is RFC documentation space, lifecycle evidence excludes submitted content, and old closed fixture PII is anonymized before the marked record is deleted.
- Side effects: no email, WhatsApp, provider API, payment, order, subscription, public post, or production mutation is reachable from the helper.
- Evidence: 56 helper contract assertions, the complete 27-file Node suite, 3 PHP compatibility tests, 27 PHP lints, and `git diff --check` passed. Local secrets scan passed. Remote fast SAST/SCA remains quota-blocked at 16/16; no paid Plus run was used.
- Hosted result: 34/34 runtime checks passed, including narrowly marked fixture deletion, metadata/rate restoration, cross-tenant denial, replay, locks, reports, and retention. Production exposes no acceptance route. Real Cursor frontend nonce/copy/notification acceptance remains separate.

## 2026-11-19 sales-lead staging acceptance review

- Scope: public BrandDad lead input, private lead records, email-consent mirroring, lifecycle state, retention, and test-only hard deletion.
- Access/resource controls: production capture remains default-off and requires owned HTTPS origin, 8 KiB body cap, HMAC-IP client rate, bounded global rate, required privacy acknowledgment, valid email, field/source allowlists, and signed-account email equality.
- Consent/privacy: unverified requested email consent remains requested-only; only marker-owned verified fixture B enters the existing consent queue. Public response is checked for email/internal-id leakage. Test data uses `.invalid` addresses and RFC documentation IP space.
- Integrity/isolation: email HMAC plus a stale-safe per-email lock deduplicates capture; lead stage is server-owned; Woo/trial/onboarding hooks remain authoritative. The helper refuses pre-existing fixture lead collisions.
- Retention safety: the new internal candidate-id seam is normalized to at most 100 positive ids and can only add `post__in`. The helper passes exactly its two marked ids; unrelated staging leads cannot enter the test retention query.
- Deletion/restoration: hard delete requires exact created id, private lead type, expected author, and `_bds_staging_lead_fixture=1`. Exact consent rows, isolated rate counters, server values, and current-user context are restored in `finally`.
- Side effects: no email/message, provider call, outbound HTTP, user/order/subscription/payment creation, or production mutation exists in the helper.
- Evidence: 50 helper contract assertions, 29 production lead assertions, full 28-file Node suite, 3 PHP compatibility tests, 28 PHP lints, and `git diff --check` passed. Local secrets scan passed. Remote fast SAST/SCA remains quota-blocked at 16/16; no paid Plus scan was used.
- Hosted result: the initial collision guard failed closed before mutation on a pre-existing fixture-user lead. The corrected helper avoided that record and passed 34/34 checks using only two anonymous marked fixtures; cleanup and restoration passed. Public capture automatically returned to disabled. The real Cursor lead form, final consent copy, browser behavior, sender acceptance, and unsubscribe UI remain separate.

## 2026-11-19 follow-up consent staging acceptance review

- Scope: one fake consent row, unsubscribe token/hash state, compare-and-set updates, and one isolated rate transient. The delivery worker stays default-off.
- Privacy/integrity: the fixed address uses `.invalid`; the raw 192-bit token exists only in request-local memory, while the option stores its SHA-256 hash. Valid lookup uses the production constant-time comparison path.
- Consent safety: fresh consent is required; stale/no-consent/bad-status/bad-email rows fail. Opt-out persists `consent=0`, clears token fields, and cannot be reactivated by the ordinary row updater.
- Resource controls: the production 30-per-hour HMAC-IP unsubscribe limit is exercised with RFC documentation IP space and restored afterward.
- Restoration: the helper refuses any pre-existing exact fixture key, restores/removes only that key, restores only its isolated transient, and restores current-user/server context in `finally`.
- Side effects: the helper neither defines the sender nor invokes `wp_mail()`, the public admin-post handler, a provider, an order, a payment, a user, or a lead.
- Evidence: 40 helper contract assertions, full 29-file Node suite, 3 PHP compatibility tests, 29 PHP lints, and `git diff --check` passed. Local secrets scan passed. Remote fast SAST/SCA remains quota-blocked at 16/16; no paid Plus scan was used.
- Hosted result: 26/26 runtime checks passed with exact row/rate/context restoration and no errors. Worker and public lead capture remained disabled; production exposed no acceptance route. Real sender/mail acceptance, public GET confirmation, RFC 8058 POST, and the customer-facing unsubscribe page require a separately approved communications test.

## 2026-11-19 account-link cryptographic staging acceptance review

- Scope: signed account-handoff tokens, owned-host claims, replay option, safe start form, existing-account resolution, conflicting-session logic, and privacy-bounded audit rows.
- Trust boundary: a new internal current-host candidate hook is normalized and accepted only when it equals one of the two compiled BrandDad hosts. Invalid candidates fall back to the actual configured host and cannot become protocol claims.
- Secret handling: the helper's deterministic acceptance-only secret exists only on exact staging while the one-time report is absent. It is not a production credential, is not returned, and local Rafter found no credential material.
- Cryptography: production HMAC-SHA256 signing/constant-time verification is used. Acceptance covers purpose, UUID, issuer/audience, 300-second lifetime, email hash, tamper, expired/future, wrong-purpose/hash/issuer, malformed body, and size bound.
- Replay/privacy: one exact JTI-derived option is claimed twice to prove denial, then deleted. One audit row is checked for the exact action/user/source/time keys and absence of email/token, then the prior audit option is restored.
- Identity safety: only marker-owned non-admin fixture A is considered verified. Fixture B is denied. Existing-email resolution and conflicting-session detection are checked without invoking account creation, cookie, metadata, or redirect functions.
- Side effects: no cross-host request, account/user mutation, auth cookie, message, provider call, order, subscription, or payment occurs.
- Evidence: 43 helper assertions, 22 production contract assertions, full 30-file Node suite, 3 PHP compatibility tests, 30 PHP lints, and `git diff --check` passed. Remote fast SAST/SCA remains quota-blocked at 16/16; no paid Plus scan was used.
- Hosted evidence: the one-time report completed with all 30 checks true, including replay/audit/context restoration and `no_cross_host_request`; post-run private-route authorization and production isolation smokes passed.
- Residual gate: real Social-to-Directory POST, consume, cookie, and session behavior requires an isolated Directory staging host plus shared production-like secret configuration.

## 2026-11-20 Directory Featured billing staging-helper review

- Surface: Woo product/order/subscription fixtures, listing ownership, geographic placement, grant state, lifecycle callbacks, hard deletion of synthetic records, and one public staging evidence endpoint. Required secure-design and code-review gates were applied.
- Authorization and tenant isolation: the helper is exact-host and one-time gated, accepts no request identity/input, and uses only the existing marker-owned non-admin `.invalid` fixture. Ownership returns true only for the exact fixture listing/user pair while the run is active.
- Payment safety: orders use a literal no-gateway method and never invoke checkout, `payment_complete()`, a gateway, Stripe, a provider, mail, or outbound HTTP. The production adapter still requires paid order state, exact recurring metadata, owner match, geography, capacity, and authoritative next-payment date.
- Integrity and cleanup: unmarked SKU/record collisions abort. Products, orders, subscriptions, listing, and grants are marked and isolated. Cleanup requires exact created ids plus type/marker/owner checks, and restores request/user/option state in `finally`.
- Disclosure and resource controls: the route is exact-host, GET-only, request-input-free, stale-lock bounded, and exposes 28 allowlisted booleans plus bounded codes—no ids, emails, totals, grant ids, or paths.
- Evidence: all 39 Node contracts, 44 PHP lints, 3 PHP compatibility tests, staging checksums, and `git diff --check` passed. Local Rafter secrets scan found no secrets. `rafter run` was attempted but rejected at the 16/16 fast-scan quota; paid Plus was not used.
- Review disposition: no local release-blocking finding. Hosted `v1` failed closed on the missing adapter; hosted dependency-corrected `v2` then passed 28/28 checks with verified cleanup and no error codes. Real Directory inventory, Stripe test payment, and production deployment remain separate gates.

### Featured acceptance dependency-correction rerun

The hosted `v1` request failed closed because `bds-featured-billing-adapter-mu.php` was absent from isolated Social staging. The correction changes only the helper version to `0.1.1` and its immutable report option to `bds_staging_featured_billing_report_v2`; it uploads the already-reviewed production adapter separately. The route, exact-host gate, no-input contract, stale lock, fixed fixtures, no-gateway orders, marker-verified cleanup, and boolean-only DTO are unchanged (`deploy/bds-000-staging-featured-billing-acceptance-mu.php:13-15`, `:182-226`, `:389-446`).

- OWASP A01/API1/API5: no object id or function choice enters from the caller; the public GET is intentionally anonymous, exact-host-only, and exposes a fixed report. The change adds no authorization path.
- A02/A03/API3/API8: no secret, crypto, SQL, shell, deserialization, HTML rendering, or request binding is introduced. Only a server-owned option key changes; the response remains an allowlisted boolean DTO with bounded sanitized error codes.
- A04/API4/API6: the fresh key permits exactly one dependency-corrected staging run behind the existing stale-safe lock. Fixed record counts, hidden marked products, no gateway, no outbound call, and full `finally` cleanup continue bounding resource and business-flow abuse.
- A05/A07/API2/API9: the helper remains restricted to the canonical temporary staging `home_url()` host, keeps the same versioned route, accepts no credential, and shares no production data or secret. The prior `v1` failure report remains non-sensitive audit evidence.
- A06/A08/API10: no dependency, package, auto-update, third-party Action, or external API is added. The separately uploaded adapter is the exact already-reviewed repository source.
- A09: `v1` and `v2` report versions distinguish the failed dependency attempt from the corrected run without exposing record ids, email, order data, or internal paths.
- A10/API7: the helper contains no outbound fetch primitive or caller-controlled URL, so SSRF is not present.

Focused contract and both PHP syntax checks pass; `git diff --check` passes. `rafter secrets .` found no secrets (Betterleaks-version warning only). Required `rafter run` fast was attempted on the same branch and remains blocked by the 16/16 billing-period quota; no paid Plus scan was used.
## FormWise customer-portal admission repair — 2026-11-20

- **Scope:** four idempotent group-to-portal admissions in the existing FormWise organization; no application code, endpoint, authentication primitive, customer membership, group grant, plan, credit, or execution changed.
- **OWASP API1/API5:** no caller-controlled resource id or public handler exists. Exact portal/group ids were copied from authenticated provider inventory, the operation used the owner-scoped provider API, and only the four named paid-plan groups were admitted.
- **OWASP API3:** the mutation accepts only the exact provider ids; it exposes no user, email, token, portal-entry key, or private configuration in customer output.
- **OWASP API4/API6:** the provider operation is idempotent and was performed once per group. It creates no AI run or recurring work and cannot consume customer credits by itself.
- **OWASP API8/API10:** the portal remains `loginRequired=true`, `door=restricted`, and without a storefront. Immediate provider read-back verified the expected nine-group admission list. The existing grants and subscription-controlled membership remain separate authorization layers.
- **ASVS L2 V1/V4/V7/V13:** the threat model and rollback are in `BRANDDAD_BACKEND_SECURE_DESIGN.md`; RBAC remains deny-by-membership and deny-by-grant; the exact mutations and read-back are recorded; no new BrandDad API surface exists.
- **Rollback:** remove only the four exact admissions and read the portal back; no deletion or customer-data restoration is necessary.
- `rafter secrets .` reported no secrets. Required `rafter run` was attempted and remains blocked at the account's 16/16 fast-scan quota; paid Plus was not used.

## 2026-11-27 AI website-plan catalog deduplication review

- **Scope:** exact-product WooCommerce migration, public catalog visibility, purchase eligibility, and the checker recommendation URL. The change preserves both product records and all historical orders; it deletes nothing and does not create a charge, order, customer, message, or AI execution.
- **A01/A07 — access and authentication:** N/A for the new behavior. No route, login, session, role, REST endpoint, or caller-selected object was added. Both products are resolved from compile-time slugs (`deploy/bds-ai-website-plan-offer-mu.php:14-15,25-42`).
- **A02 — cryptography and secrets:** no secret, token, credential, cryptography, or randomness was added. `rafter secrets .` found no secrets.
- **A03 — injection and output handling:** no SQL, shell, deserialization, file-path input, or request data enters the migration. Product comparisons are exact sanitized-slug checks; the customer recommendation is a fixed same-origin path (`deploy/bds-ai-website-plan-offer-mu.php:36-42`; `deploy/bds-services-catalog-mu.php:82`).
- **A04 — secure design:** canonical and legacy products must both exist and resolve to distinct server-owned IDs before either is mutated (`deploy/bds-ai-website-plan-offer-mu.php:55-66`). The legacy public state is backed up, its catalog visibility is hidden, and exact legacy purchase attempts fail closed while the canonical product alone remains purchasable (`:109-150`).
- **A05 — configuration:** behavior is restricted to the exact `branddad.social`/`www.branddad.social` home host allowlist (`deploy/bds-ai-website-plan-offer-mu.php:18-22`).
- **A06/A08 — components and integrity:** no dependency or update channel changed. Both modified deployment files have refreshed SHA-256 manifest entries, and the release-checksum contract passes.
- **A09 — auditability:** the one-time version, last-error option, canonical backup, and legacy catalog backup retain a reversible record without logging PII (`deploy/bds-ai-website-plan-offer-mu.php:52-79,109-126`).
- **A10 — SSRF:** no outbound fetch, redirect target, or caller-controlled URL was introduced. The existing legacy redirect remains outside this plugin and must be verified live.
- **Evidence:** 43/43 Node contracts, 48/48 deployment PHP syntax checks, 5/5 standalone PHP behavior tests, and `git diff --check` pass. The local Rafter secret scan is clean. Required `rafter run --mode fast` was attempted and remains blocked at 16/16; paid Plus was not used.
- **Disposition:** no unresolved local release-blocking finding. Production acceptance must verify one Shop card, canonical product HTTP 200, legacy HTTP 301 to canonical, canonical product ID `53645` buyable, legacy product ID `53082` rejected, and no order/payment creation.
