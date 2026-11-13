# BrandDad AI Video Studio — Secure Design

Status: staged implementation. No public rendering, upload, billing, or publishing is enabled by this document or its companion plugin.

## Data flow and trust boundaries

`Customer browser -> WordPress TLS edge -> WordPress REST/domain layer -> tenant-scoped WordPress tables`

`WordPress -> allowlisted render/audio provider -> signed callback -> WordPress job state`

`Browser -> short-lived signed object-storage upload -> isolated media origin -> render provider`

`Approved export -> existing BrandDad social queue -> official platform API`

Boundaries are browser/edge, edge/domain, application/database, application/provider, provider/webhook, media origin/consumer, and publishing adapter/platform. Untrusted prompts, URLs, files, transcripts, provider responses, and webhook payloads are validated at every crossing.

## Authentication and authorization

- Humans use the existing WordPress authenticated session and nonce. The plugin does not add passwords or JWTs.
- Provider callbacks act as named services and require HMAC-SHA256 over timestamp plus raw body. Missing or invalid identity is rejected.
- Authorization is resource ownership plus capability: `tenant_key` and `owner_user_id` must both match values derived from the current server-side session; administrators may inspect status but do not silently impersonate customers.
- Tenant, owner, credits, cost, approval state, provider identifiers, and publication state are never client-settable.
- Opaque UUIDv4 public identifiers are used. Unknown and unauthorized resources both return the same not-found response.
- Publishing remains a separate explicit approval transition. An AI agent cannot grant approval or enable a provider.

## Data storage and retention

- Identifiers: WordPress user ID, tenant key, project/job UUID. Retain while the account/project exists.
- Content: prompts, scripts, scene JSON, captions, media metadata. Default retention is project lifetime; rendered previews expire after 30 days unless retained by the customer.
- Credentials: provider, storage, webhook, and OAuth secrets remain server-side constants/secret-manager values and are never stored in project tables or returned to clients.
- Consent: voice/likeness/content-ownership assertions are retained with actor and timestamp for the project lifetime plus 13 months.
- Audit/usage: state transitions and cost ledger entries contain IDs and amounts, not prompt bodies or credentials; retain 13 months.
- Failed-job diagnostics contain stable error codes and provider request IDs only; retain 30 days.
- Tenant isolation uses mandatory `tenant_key` plus `owner_user_id` predicates on every domain query. Raw SQL helpers require both values.
- Media uses random object keys and a separate origin. Client filenames are display metadata only, never filesystem paths.
- Production storage must use provider-managed encryption at rest and TLS 1.2+. Provider keys must be scoped and independently rotatable.

## API and ingestion rules

- Namespace: `/wp-json/bds-video/v1`; feature flag defaults off.
- Every write uses explicit field allowlists, bounded strings/arrays, strict enums, and an idempotency key scoped to tenant and actor for 24 hours.
- Rate limits apply per IP, per user, and per expensive endpoint. Credit quotas are the final cost boundary.
- Error responses use stable codes and do not expose SQL, paths, stack traces, secrets, or resource existence.
- Inbound webhooks allow only documented fields, enforce a five-minute timestamp window, verify the exact raw body signature, and reject replayed delivery IDs.
- Future URL ingestion must permit only public HTTPS destinations, resolve and reject loopback/private/link-local/metadata addresses, disable or revalidate redirects, cap response bytes, and use short timeouts.
- Future uploads must check declared MIME, extension, and content signature; cap per-file, file-count, daily bytes, and duration; quarantine/scan before use; re-encode images/video; and never serve active SVG/HTML from the application origin.
- No PHP `unserialize`, executable YAML, XML external entities, arbitrary sort columns, or raw untrusted HTML is permitted.

## Deployment and dependencies

- WordPress is the control plane only. CPU-heavy rendering is not executed on shared hosting.
- Egress is limited to explicitly configured provider hosts. Provider adapters validate their fixed base URL; customers cannot supply webhook or render endpoints.
- Flags default off: Studio, provider dispatch, uploads, and publishing are enabled separately.
- Staging uses synthetic data and the same authorization rules as production.
- No new runtime package is introduced in the foundation. WordPress core JSON, HTTP, nonce, UUID, and database primitives are used.
- A future worker/container must run non-root with a read-only root filesystem, resource limits, pinned images, workload identity, separate secrets, and allowlisted egress.
- Production deployment requires code review, Rafter SAST/SCA/secrets, signed release evidence, backup, and rollback instructions.

## STRIDE and abuse cases

- Spoofing: stolen sessions are bounded by WordPress session controls; service spoofing is blocked by HMAC and secret rotation.
- Tampering: TLS, HMAC callbacks, immutable job IDs, checksums, server-owned cost fields, and append-only usage entries protect integrity.
- Repudiation: every state transition records actor, request ID, source state, target state, and UTC time.
- Information disclosure: ownership predicates, separate media origin, minimal logs, generic not-found responses, and no credential rendering limit disclosure.
- Denial of service: request bounds, quotas, rate limits, one active render per project version, provider timeouts, bounded retries, and kill switches limit resource and cost exhaustion.
- Elevation: clients cannot set tenant, owner, capability, credits, provider status, or approval; domain checks repeat route checks.
- Prompt injection: FormWise receives source text as untrusted factual material and has no publishing, credential, shell, database, or billing tools.
- Cross-tenant bleed: model inputs are assembled from one tenant/project only; provider callbacks identify the already-owned job and cannot change its tenant.

Abuse twins: mass project creation is limited by user/IP quotas; upload bombs are rejected before durable storage; URL ingestion cannot reach internal services; replayed render callbacks are rejected; duplicate publish retries use destination/version idempotency; an AI-generated unsupported claim is blocked by approval and factual-quality gates; voice cloning is unavailable without recorded consent.

Residual risks accepted for the staged foundation: WordPress row-level isolation depends on reviewed query helpers rather than database-native RLS; shared-hosting secrets may initially be constants rather than workload identity; third-party providers receive only the media required for a job. These risks block broad customer rollout until deployment hardening and provider agreements are reviewed.

## BrandDad-owned render worker contract

The staged worker adds one service-to-service endpoint, `POST /v1/render`. It is not a customer API and is not enabled on the public internet by the repository alone.

- Identity: WordPress is the sole caller. It signs `timestamp.rawBody` with HMAC-SHA256 using a dedicated dispatch secret. The worker rejects missing credentials, malformed signatures, timestamps outside five minutes, and replayed delivery UUIDs with constant-time comparison.
- Authorization: an authenticated dispatch may request only the fixed `branddad_motion_v1` template. It cannot choose a command, executable, filesystem path, provider endpoint, callback path, tenant, or credential.
- Request contract: JSON only; 100 KB maximum before parsing; unknown top-level and scene fields rejected; UUIDs, enums, text lengths, array counts, duration, cost, and credit values bounded. The standard JSON parser is used and parsed data is never evaluated or merged into prototypes.
- Callback defense: `callbackUrl` must be HTTPS, have no user info/query/fragment, match one operator-configured hostname exactly, and use `/wp-json/bds-video/v1/webhooks/render`. Redirects are not part of the callback contract.
- Replay/idempotency: delivery UUIDs are atomically claimed. The job UUID is the idempotency key; a safe repeat returns the existing opaque provider job ID without adding work. Production uses a durable atomic replay/job store; a process-local store is test-only.
- Resource controls: the edge and worker both cap request size; the worker has a per-source request limit, one bounded queue item per job UUID, generic errors, short server timeouts, and no renderer execution in the HTTP process.
- Secrets/logging: the dispatch secret comes from the deployment secret manager, never source or a request. Logs contain request/job IDs and stable result codes, never the HMAC, raw body, scene text, or secret.
- Deployment: bind to loopback behind an authenticated TLS reverse proxy by default. Production runs non-root, read-only except an explicit state/queue mount, with CPU/memory limits, default-deny ingress, allowlisted WordPress callback egress, and metadata/private-network blocks. The worker has no arbitrary outbound fetch capability.
- Dependency posture: the staged verifier uses only pinned Node runtime primitives (`http`, `crypto`, `fs`, `url`). No install-time dependency scripts or third-party packages are introduced.

Worker DFD: `WordPress job ledger -> TLS/reverse proxy -> HMAC verifier -> strict schema validator -> atomic replay/idempotency store -> bounded render queue`. A later isolated renderer consumes that queue and sends a separately signed callback to WordPress.

Worker STRIDE: service spoofing is blocked by HMAC; body tampering invalidates the signature; atomic delivery/job records provide audit and replay evidence; generic errors and minimal logs limit disclosure; byte/rate/queue bounds constrain denial; fixed template and data-only queue records prevent command or privilege escalation. The abuse twin is a stolen or replayed dispatch flooding expensive renders; timestamp/replay checks, per-source limits, job idempotency, WordPress credit reservations, and a provider kill switch contain it.

Worker residual risks: a shared HMAC secret has broader blast radius than workload identity, filesystem-backed queue durability is single-node, and application rate limiting is not a substitute for an edge/WAF limit. These remain rollout blockers until production infrastructure is selected and configured.
