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
