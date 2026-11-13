# BrandDad Video Autopost — Secure Design

## Scope and data flow

Internal BrandDad operators approve a completed video project in WordPress. WordPress sends a signed allowlisted payload (`jobId`, approved version, captions, destination class, and idempotency key) to the BrandDad-owned worker. The worker resolves only the server-configured BrandDad Social workspace and connected-account IDs, uploads the approved MP4 to Marky, and creates a private `NEW` review draft. Marky retains the social OAuth tokens and is never exposed in the customer-facing product. Provider post IDs and status return to the existing tenant-scoped audit/job store. Customer autopilot is out of scope.

Trust boundaries: WordPress admin/session -> BrandDad domain layer -> signed BrandDad worker -> fixed Marky API -> official platform OAuth connections -> Instagram/Facebook/TikTok/YouTube. Media storage and WordPress remain separate stores. No social access token or Marky API key is stored in WordPress or exposed to FormWise.

## Authentication and authorization

- Humans continue to use WordPress server-side sessions; only `manage_options` operators can approve BrandDad-owned autopost jobs.
- Marky and each publishing platform use managed OAuth connections. Platform tokens stay in Marky and must be revocable; the scoped Marky API key stays only in the worker secret manager and no key is committed to code.
- The WordPress domain layer enforces BrandDad tenant ownership, approved status, eligible destination, and immutable approved version for every publish request.
- The automation acts as a named BrandDad service principal; audit records name both the approving operator and automation.
- Missing or invalid service credentials fail closed. No tenant ID or account ID is accepted from a public query parameter.

## API and webhook controls

- Versioned payload schema with an explicit field allowlist; unknown fields are rejected.
- Opaque job IDs and destination IDs; account IDs are server-side configuration, not caller-controlled values.
- One idempotency key per `(approvedVersion, platform, scheduledTime)` retained through the retry window to prevent duplicate posts.
- HMAC/timestamp verification and replay rejection on BrandDad-controlled callbacks. Marky requests use the fixed `https://api.mymarky.ai/api` origin, pinned API version, strict response bounds, and server-configured workspace/integration identifiers.
- Per-operator, per-tenant, and per-endpoint quotas; bounded retries with exponential backoff and dead-letter/manual review.
- Errors disclose stable codes only. Media URLs must be HTTPS and restricted to BrandDad-controlled storage hosts.

## Deployment and secrets

- Public edge: existing TLS/WAF. The worker can egress only to `api.mymarky.ai`, approved media storage, and the BrandDad WordPress callback host.
- OAuth tokens remain in Marky/provider vaults. The worker secret manager contains only a scoped Marky API key; WordPress stores only connection references and post IDs.
- Autopost feature flags default off. Approval Mode may create `NEW` Marky drafts only. Scheduling/publishing is a separate explicit transition and production activation follows one successful private/unlisted post per platform plus an operator-visible rollback switch.
- Structured audit logs contain no tokens or full webhook bodies. Retain post/audit metadata; media retention follows BrandDad policy.

## STRIDE findings and mitigations

- Spoofing: managed OAuth, signed callbacks, admin capability checks, and explicit service identity.
- Tampering: TLS, payload signatures, immutable approved versions, and append-only audit events.
- Repudiation: actor + automation + resource + request ID + provider post ID are logged.
- Disclosure: no credentials in WordPress, URLs, FormWise, prompts, or logs; responses redact provider details.
- Denial of service/cost abuse: quotas, schedule caps, media limits, timeouts, bounded retries, and a kill switch.
- Elevation/cross-tenant access: tenant comes from the authenticated session and is required on every job lookup.

## Abuse twins and residual risk

- Schedule one video -> attacker schedules thousands: quotas, approval state, per-platform idempotency, and kill switch.
- Publish a BrandDad video -> attacker supplies arbitrary media URL: storage-host allowlist and content-type/size validation.
- Retry a failed post -> duplicate public posts: durable idempotency key and provider post lookup before retry.
- AI creates captions -> prompt-injected webpage requests unauthorized posting: AI output is data only; it cannot choose accounts or bypass approval.

Residual risks: provider/API outages, platform policy changes, and imperfect token revocation latency. Accept only with private test posts, monitoring, and manual fallback. Full customer autopilot and paid rendering remain disabled pending cost and entitlement approval.
