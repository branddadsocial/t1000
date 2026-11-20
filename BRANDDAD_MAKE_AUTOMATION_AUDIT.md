# BrandDad Make automation audit

Date: 2026-11-20

## Scope and method

Read-only inspection of the connected Make account through the official connector. No scenario was created, edited, activated, deactivated, run, replayed, or deleted. No webhook was called and no connection was changed.

Connected environment:

- Zone: `us1.make.com`
- Organization: `My Organization`
- Team: `My Team`
- Scenario inventory returned 11 scenarios and did not report pagination.

## Account-wide status

- No scenario reports `status=active`.
- Nine scenarios report `paused`; one staging lead scenario and one old LinkedIn scenario also expose `isActive=true`, but their effective status remains paused and the LinkedIn next-run timestamp is already in the past.
- Facebook Auto-Comment reports `status=error` and its Facebook connection is `expiring`.
- All scenarios report zero stored incomplete executions.
- The connector's execution-history endpoint returned `INVALID_ARGUMENT` for scenarios with listed historical usage, so recent success/failure history was not available in this pass. Structural state is therefore verified; recent runtime success is not.

## Revenue-critical BrandDad scenarios

### BrandDad Video Autopost — STAGING

- Effective status: paused.
- Trigger: instant custom webhook.
- Modules: one webhook trigger only.
- Connections: none.
- Finding: this is an intake shell, not a video generator, renderer, scheduler, or social publisher. It cannot fulfill automated video posting as currently built.

### BrandDad FormWise Lead Capture — STAGING v2

- Effective status: paused, while the inventory also exposes `isActive=true`.
- Trigger: instant custom webhook.
- Modules: receive a consented FormWise lead, then add a deduplicated datastore record.
- Finding: this can queue a consented lead when operational, but it contains no email/WhatsApp notification, qualification, follow-up, appointment, checkout, or CRM-delivery module in the structural read.

### BrandDad FormWise Lead Capture — STAGING

- Effective status: paused.
- Same two-module webhook-to-datastore shape as v2.
- Finding: duplicate staging implementation. Preserve for comparison until v2 is validated, then retire only with explicit deletion approval.

## Existing social scenarios

### LinkedIn Viral Poster Bot

- Effective status: paused.
- Schedule: Monday/Wednesday/Friday at 09:10.
- Flow: variables -> tone/topic -> OpenAI completion -> LinkedIn post, with an ignore error handler.
- OpenAI and LinkedIn connections report healthy.
- Finding: structurally capable of posting, but it is not currently running and uses a legacy OpenAI completion module.

### 0Dev LinkedIn News Poster Bot

- Effective status: paused; inventory also says `isActive=true` and shows a past next execution.
- Schedule: RSS polling Tuesday/Thursday at 14:10.
- Flow: RSS article -> validation -> tone/role -> OpenAI completion -> LinkedIn post, with an ignore error handler.
- OpenAI and LinkedIn connections report healthy.
- Finding: conflicting state and a stale next-run timestamp require Make-side runtime reconciliation before any activation. The ignore handler can hide publishing failures from business reporting.

### Facebook Auto-Comment Bot

- Effective status: error.
- Flow: watch Facebook comments -> OpenAI reply -> create Facebook comment.
- Facebook connection reports expiring; OpenAI reports healthy.
- Finding: not production-ready. It also performs public representational actions and must not be reactivated without consent, response safety, rate limits, and verified current platform permissions.

### Other legacy scenarios

- Instagram Auto-Comment, Discord Event Planner, Competitor Analysis, Reddit Viral Bot, and SEO Keywords Bot are paused.
- Several older scenario detail reads returned `INVALID_ARGUMENT`; no activation or runtime capability is claimed for them.

## Missing automation lanes

No operational Make scenario was found for:

- AI Business Essentials onboarding or recurring delivery.
- Woo/Stripe subscription provisioning, renewal, failure, cancellation, or refund handling.
- Directory free-AI activation or Featured advertising reconciliation.
- FormWise paid-plan provisioning.
- Customer monthly value reports.
- Website monitoring alerts.
- SEO/autoblog publishing.
- Cross-platform social scheduling beyond the two paused LinkedIn drafts.
- WhatsApp Business messaging.
- Trial reminders, abandoned checkout, failed-payment recovery, or consented lead nurture.
- Partnero conversion/refund synchronization.
- Video creation, rendering, approval, scheduling, or publishing.

Absence from Make does not prove absence from WordPress or another provider. It proves only that Make is not currently the operating automation layer for these lanes.

## Safe launch disposition

1. Keep every legacy/experimental scenario paused until its business owner, provider permissions, consent model, current modules, cost limits, and failure reporting are revalidated.
2. Treat Video Autopost as an unimplemented staging shell, not a sellable or internal autopost capability.
3. Treat lead-capture v2 as a datastore intake prototype. It needs verified notification/delivery, consent retention, dedupe acceptance, and run-history evidence before activation.
4. Do not reactivate comment/posting bots merely because connections report healthy; public posting is a representational action and requires an approved content/automation policy.
5. Prefer the reviewed WordPress backend for authoritative subscription lifecycle state. Make may orchestrate provider work only after signed/idempotent event contracts and reconciliation are documented.
6. Before any production activation, obtain execution-history evidence, run a controlled staging event, verify retries/deduplication, set cost caps, and establish a visible stop/rollback operator.

## Lead-capture v2 secure design gate

This section is the required pre-change security design for scenario `5029212` (`BrandDad FormWise Lead Capture — STAGING v2`). The scenario must remain paused while this design is implemented and validated.

### Trust boundaries and data classification

- The public Make webhook is an untrusted Internet boundary. A label such as "FormWise" does not authenticate the sender.
- `name` and `email` are personal identifiers.
- `business`, `website`, `service_interest`, `business_problem`, and `preferred_contact` are customer-supplied business or message content.
- `follow_up_consent`, `environment`, and `source` are security and policy metadata; they must not be inferred from missing values.
- `received_at` and `delivery_status` are operational audit fields.
- Make execution logs, datastore records, and notification messages are separate disclosure surfaces.

### Fail-closed staging contract

- Accept a record only when `follow_up_consent`, after trimming and case normalization, is exactly `true` or `yes`.
- Accept a record only when `environment`, after trimming and case normalization, is exactly `staging`.
- Require a non-empty email value before the datastore module. Format, length, schema, and payload-size validation must ultimately occur in the BrandDad server-side ingress layer before Make.
- Reject missing, ambiguous, or unknown consent values. Rejection must not create a datastore record or send a notification.
- Do not inspect, replay, process, or delete the two payloads already queued on the paused webhook without explicit authorization.
- Keep the scenario paused after the filter change. A controlled synthetic staging request is required before activation.

### Deduplication, minimization, and retention

- The current `md5(lower(trim(email)))` key provides only deterministic grouping; it is not authentication, encryption, or a privacy-preserving secret. It can also overwrite prior state.
- Production should use an opaque, sender-generated idempotency key bound to the validated event. Replay protection should include a timestamp or nonce in the signed BrandDad ingress contract.
- Store only fields needed to contact or qualify a consenting lead. Do not add transcript, payment, credential, or unrelated browsing data.
- The current datastore has no verified automatic expiration. The staging target is a maximum 30-day retention period, but the workflow must remain non-production until cleanup and deletion verification exist.
- Do not configure an owner notification destination by inference. The previously supplied Stripe email is not automatically an approved lead-notification address.

### Threat model and controls

Data flow: untrusted sender -> public Make webhook -> consent/environment filter -> staging datastore -> future approved owner notification.

- Spoofing/tampering: the webhook is currently unsigned. Production requires authenticated server-to-server forwarding (for example, an HMAC-signed event verified by BrandDad's backend) rather than trusting a public webhook payload.
- Replay/duplication: use an opaque idempotency key, a bounded replay window, and explicit duplicate handling.
- Disclosure: minimize stored fields, restrict Make team access, avoid secrets in payloads, and implement verified retention cleanup.
- Denial of service/cost: enforce request-size limits, rate limits, and cost caps before forwarding to Make.
- Privilege/cross-account risk: use a dedicated least-privilege connection and keep staging and production event namespaces separate.
- Automated outreach: consent to store or notify about a lead is not permission for unrestricted email, WhatsApp, or other follow-up. Channel-specific consent and stop conditions remain mandatory.

### Residual blockers before production

1. The public webhook is unsigned and does not have verified schema, length, or rate-limit enforcement.
2. Two unknown payloads are queued and intentionally untouched.
3. Automatic retention cleanup is not implemented or tested.
4. The owner notification destination and channel are not approved.
5. No controlled staging execution has demonstrated accepted, rejected, duplicate, and failed-delivery paths.

### Implemented staging safeguard — 2026-11-20

- Scenario `5029212` was re-read immediately before the edit and remained `paused`.
- A filter was added before datastore module `2`. Its two allowed branches require all of the following: normalized consent equals `true` or `yes`; normalized environment equals `staging`; and the trimmed email value exists.
- The saved scenario was re-read after the edit. Make reports the filter exactly as specified, no module issues, no third-party connections, and effective status `paused`.
- The existing datastore mapping, MD5-based dedupe key, queued webhook payloads, schedule, and scenario activation state were not changed.
- No webhook was called, no queued payload was inspected or processed, and no lead notification was sent.

Security review disposition:

- API object/function authorization is not applicable to the Make filter itself because it exposes no object-addressed management endpoint to customers; the Make team connection remains the control plane.
- Authentication remains insufficient at the public data-ingress boundary: the filter enforces policy fields but cannot prove the sender is FormWise or BrandDad.
- Resource-consumption and sensitive-flow abuse remain unresolved until BrandDad's authenticated ingress adds size, rate, schema, and replay limits.
- Third-party input is treated as untrusted and is prevented from reaching storage unless it passes the explicit staging/consent/email gate; field format and length validation remain outstanding.
- `rafter secrets .` found no repository secrets. The paired remote `rafter run` could not execute because the account's fast-scan allowance is exhausted (`16/16`); no paid Plus scan was used.

This is a useful staging hardening step, not production approval.

### Authoritative lead-system boundary

The reviewed WordPress `bds-sales-lead-pipeline-mu.php` is the intended authoritative lead store and lifecycle engine. Its current contract covers default-off activation, owned HTTPS origins, an 8 KB request cap, per-client/global rate limits, required privacy acknowledgment, HMAC email deduplication, private storage, bounded retention, unsubscribe suppression, and authoritative trial/checkout/payment/onboarding hooks. The focused contract and PHP syntax checks pass.

Make should therefore receive only a minimal, authenticated, idempotent downstream event after the BrandDad backend has accepted and stored a lead. It must not become a second customer-facing lead database or independently infer consent, paid state, or lifecycle stage. The present direct public webhook-to-datastore prototype does not yet meet that boundary.

## Direct-Make intake retirement design — 2026-11-20

Decision: retire both direct public Make intake prototypes and keep WordPress as the sole authoritative lead ingress and retention system. This removes the unsafe duplicate data path instead of trying to retrofit authentication into a generic public webhook that cannot validate an HMAC natively.

### Identity and authorization

- Human visitors submit only to the default-off `branddad/v1/sales/leads` WordPress route. That route accepts only owned HTTPS origins, requires privacy acknowledgment, rate-limits callers, validates an allowlisted schema, and never accepts a caller-selected lifecycle stage.
- WordPress admin access to captured leads remains protected by `manage_woocommerce`; Make is not an authorization authority.
- No shared bearer token, signing secret, customer credential, or Make URL is added to browser code, the repository, logs, or notification content.

### Data flow and storage

Target flow: visitor -> BrandDad WordPress validation -> private WordPress lead record -> admin-only BrandDad inbox. A future Make lane may receive a minimal authenticated/idempotent event only after a supported verification mechanism exists.

- Email is an identifier/PII field; business type and need are customer content; consent and lifecycle stage are policy/audit fields.
- WordPress retention is bounded to 30–365 days (180 by default), removes stale unconverted contact data in batches, and preserves unsubscribe suppression without retaining the address.
- The Make datastore is empty and will not become a second PII store. No email notification address is inferred from a Stripe or billing address; the verified destination is the existing admin-only BrandDad lead inbox.

### Retiring the exposed hooks

- Exact targets: scenarios `5029212` and `5028724`, trigger modules `1`, current hook ids `2868332` and `2868258`.
- Provider read-back shows both scenarios paused, zero incomplete executions, empty delivery history, and five total queued payloads (two on v2, three on the older prototype). Their contents remain uninspected and unauthenticated.
- Replace each old exposed webhook/data-store flow with a no-input on-demand starter, rename the scenario as retired, and keep it paused. This invalidates the old public URLs and intentionally discards their five unverified queued payloads rather than processing or copying possible PII.
- Preserve the empty datastore and scenario records for audit/recovery; do not activate, execute, notify, or send follow-up.

### Threat model and residual risk

- Spoofing/tampering/replay: old generic URLs accept unauthenticated Internet traffic, so rotation removes them from service rather than trusting their queues.
- Disclosure: no queued body is opened; no data is copied to email, documentation, or WordPress; provider-side deletion behavior is verified by post-rotation inspection.
- Denial/cost: retired scenarios remain paused and no execution credits are consumed.
- Elevation/repudiation: Make cannot set authoritative lifecycle or consent; WordPress records remain the audited source.
- Worst-case compromise of an unshared replacement URL can only queue data on a paused retired prototype. It cannot write WordPress, send mail, publish, bill, or activate a customer.
- Residual risk is limited to Make retaining provider-level metadata according to its own retention policy; no production customer flow depends on it.

### Retirement verification

- Scenario `5029212` is now `RETIRED — BrandDad FormWise Lead Capture STAGING v2`; scenario `5028724` is now `RETIRED — BrandDad FormWise Lead Capture STAGING`.
- Each contains exactly one `scenario-service:StartSubscenario` module labeled `Retired direct intake — no public trigger`, reports `kind=on-demand`, `scheduling.type=on-demand`, `status=paused`, zero connections, and zero incomplete executions.
- Webhook trigger inspection now returns `INVALID_ARGUMENT` for both because neither scenario has a webhook trigger. The previous hook URLs are no longer present in scenario read-back.
- Data store `99354` still contains zero records. No queued body was read, copied, executed, or delivered.
- The first attempted generic hook-rotation patch was rejected atomically with `INVALID_ARGUMENT`; the first schedule correction was rate-limited and also made no change. The supported on-demand replacement and later schedule saves then succeeded and were verified by provider read-back.
- No Make execution credit, customer message, provider notification, payment, WordPress record, or production content changed.
