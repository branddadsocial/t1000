# BrandDad AI Video Studio — Implementation Record

## Implemented in 0.1.0

- Separate staged MU plugin that extends the existing AI Studio without replacing its Brand Kits, drafts, calendar, FormWise app, subscriptions, or social publisher.
- Feature flags default off for the Studio, render provider, uploads, and publishing.
- Tenant-scoped project storage with opaque UUIDs, owner predicates, bounded inputs, approval-only mode, and immutable approved versions.
- Scene editing with strict field allowlists and limits.
- Deterministic 15/30/60/90-second template-video quotes. Premium generated footage is excluded from the default quote.
- Idempotent render dispatch adapter, hourly user/IP limits, provider-cost safety bound, and fail-closed behavior when no approved adapter is configured.
- Signed render callbacks with five-minute timestamp tolerance, delivery replay protection, provider job matching, bounded payloads, and generic errors.
- Usage reservation, settlement/refund, and state-transition audit tables.
- Approval state machine: draft or changes requested → render → preview ready → approved.
- AI Studio navigation entry and an administrator readiness/status screen.
- Responsive administrator project workspace for project creation, scene editing, cost estimates, status, and monthly usage. It renders API data with DOM text nodes rather than HTML injection.
- Atomic per-tenant monthly credit ceiling with a database advisory lock, transactional reservations, refunds on failed dispatch, and a read-only usage endpoint. The default safety cap is 100 credits and can be connected to subscription entitlements through `bds_video_monthly_credit_cap`.
- Immutable approved projects with an explicit clone-to-new-version workflow. The new draft preserves the approved source and scenes while retaining a parent-project relationship.
- Tenant-scoped render history showing only bounded job status, credits, costs, timestamps, and generic error codes; provider identifiers remain server-side.
- Static security-control regression test.
- Unpublished FormWise flow `BrandDad AI Video Director — Staged` (`c4a5d2bc-6201-4aaa-b3d6-a9056467b617`) with a nine-question intake and one structured JSON planning node. The node uses `gpt-5.6-luna`, has browsing, scraping, organization tools, artifacts, and deep research disabled, and always requires human review.

## REST surface

- `GET|POST /wp-json/bds-video/v1/projects`
- `GET|PATCH /wp-json/bds-video/v1/projects/{uuid}`
- `POST /wp-json/bds-video/v1/projects/{uuid}/render-quote`
- `POST /wp-json/bds-video/v1/projects/{uuid}/renders`
- `POST /wp-json/bds-video/v1/projects/{uuid}/approve`
- `GET /wp-json/bds-video/v1/projects/{uuid}/jobs`
- `POST /wp-json/bds-video/v1/projects/{uuid}/versions`
- `GET /wp-json/bds-video/v1/usage`
- `POST /wp-json/bds-video/v1/webhooks/render`

Cookie-authenticated routes require the WordPress REST nonce and derive tenant/owner from the session. The webhook is the only anonymous route and requires the configured HMAC secret.

## Required configuration before any staging execution

```php
define( 'BDS_VIDEO_STUDIO_ENABLED', true );
// Keep these false until their separate tests and approvals pass.
define( 'BDS_VIDEO_PROVIDER_ENABLED', false );
define( 'BDS_VIDEO_UPLOADS_ENABLED', false );
define( 'BDS_VIDEO_PUBLISHING_ENABLED', false );
```

Do not add provider credentials to this repository. The render adapter must hook `bds_video_dispatch_render` and return a provider job identifier only after it has obtained a provider quote within the server-approved bound.

## Remaining phased work

1. Complete the existing AI subscription sandbox lifecycle and responsive launch preview before enabling the Studio.
2. Select and contract the render/storage/audio providers; add scoped secrets and signed webhook configuration.
3. Implement signed multipart uploads, quarantine/scanning, media metadata, separate-origin delivery, and retention deletion.
4. Add the customer React/JS scene-board interface, connect the staged FormWise script/scene workflow after a credit-approved draft test, then add narration, captions, preview playback, and export records.
5. Add Auto-Clip transcription, highlight analysis, reframing, and clip selection.
6. Extend the existing official OAuth publishing queue for video exports; keep Approval Mode mandatory.
7. Add analytics ingestion, cost reconciliation, lifecycle deletion, provider failover, and Full Autopilot release gates.

## Verification

- `node tests/video-studio-static.mjs` passes.
- `git diff --check` passes for the staged files.
- `rafter secrets` reports no detected secret in the new plugin.
- Rafter remote scan `c97c50f9-cbfa-464a-a7c8-51b5bc3eb9e0` failed remotely and is not represented as a passing SAST/SCA result.
- PHP CLI is not installed in this workspace. The plugin passes a PHP AST parse with the temporary `php-parser` 3.2.5 package, but a native `php -l` check is still required before deployment.
- The FormWise flow is saved as an unpublished draft and was not run, so no FormWise credits were consumed by testing.
- No file was uploaded to production, no feature flag was enabled, no provider was called, and no charge or publication occurred.
