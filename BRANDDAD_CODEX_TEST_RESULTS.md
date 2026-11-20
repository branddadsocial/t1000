# BrandDad Codex test results

## 2026-11-19 release-package verification

- 17/17 deploy PHP files passed `php -l`.
- 15/15 Node contract suites passed.
- 3/3 compatibility PHP suites passed.
- 35/35 local validation commands passed; zero failures.
- Social archive contains the intended 15 MU modules plus manifest and checksum file.
- Directory archive contains the intended 7 MU modules plus manifest and checksum file.
- Every packaged PHP file has the same SHA-256 as its reviewed `deploy/` source.
- `rafter secrets .` passed with no detected secrets; the CLI still reports its known Betterleaks output-version warning.
- `rafter run --mode fast` was attempted again and remains blocked by the 16/16 account quota until reset. No paid Plus scan was used.
- No live provider execution, payment, email, public-post edit, or production file change occurred during this verification.

## 2026-11-19 provider-cost batch

- 18/18 deploy PHP files passed `php -l`.
- 16/16 Node contract suites passed, including 17 provider-cost ledger security/behavior assertions.
- 3/3 compatibility PHP suites passed.
- 37/37 local validation commands passed; zero failures.
- Social release archive now contains 16 MU modules plus manifest/checksum files; Directory contains 7 MU modules plus manifest/checksum files.
- Every packaged PHP file matches its reviewed `deploy/` source SHA-256.
- Social archive SHA-256: `BB40EB4C379EAA9235E8D0DE3991773FD208FB0562206F28D619B404C7FE82A1`.
- Directory archive SHA-256: `1951A5F97522E0F407EE4C18E569279752E534B75966F5A9F2C92C87DE91F193`.
- Local `rafter secrets .` passed and MCP Rafter secret scan returned no findings.
- Required `rafter run --mode fast` was attempted and remains blocked by the 16/16 quota; paid Plus was not used.
- WordPress staging still must prove admin denial, replay, lock recovery, period boundaries, truncation, malformed stored rows, refunds, multi-currency totals, and incomplete-coverage labeling before production activation.

Updated: 2026-11-19

## Passed locally

- Combined validation: 15 checks, 0 failures after security hardening.
- PHP lint: `deploy/bds-ai-subscriptions-mu.php`.
- PHP lint: `deploy/bds-directory-free-ai-mu.php`.
- PHP lint: `deploy/bds-ai-execution-mu.php`.
- PHP lint: `deploy/bds-account-link-mu.php`.
- PHP lint: `deploy/bds-sales-assistant-api-mu.php`.
- `node tests/ai-subscription-lifecycle-contract.mjs`.
- `node tests/directory-free-ai-contract.mjs`.
- `node tests/ai-execution-contract.mjs`.
- `node tests/account-link-contract.mjs`.
- `node tests/sales-assistant-api-contract.mjs`.
- PHP lint: `deploy/bds-consent-followup-worker-mu.php`.
- PHP lint: `deploy/bds-onboarding-api-mu.php`.
- `node tests/consent-followup-worker-contract.mjs`.
- `node tests/onboarding-api-contract.mjs`.
- Combined validation after this batch: 19 checks, 0 failures.
- PHP lint: `deploy/bds-ai-trial-mu.php`.
- PHP lint: `deploy/bds-customer-status-api-mu.php`.
- PHP lint: `deploy/bds-directory-membership-bridge-mu.php`.
- `node tests/ai-trial-contract.mjs`.
- `node tests/customer-status-api-contract.mjs`.
- `node tests/directory-membership-bridge-contract.mjs`.
- Final local batch: 12 deploy PHP lint commands, 10 Node contract suites, and 3 legacy PHP suites passed (25 validation commands, 0 failures).
- PHP lint: `deploy/bds-featured-billing-adapter-mu.php`.
- `node tests/featured-billing-adapter-contract.mjs`.
- Current full suite: 13 deploy PHP lint commands, 11 Node contract suites, and 3 legacy PHP suites passed (27 validation commands, 0 failures).
- PHP lint: `deploy/bds-lifecycle-events-mu.php` and all five event-emitting integrations.
- `node tests/lifecycle-events-contract.mjs` verifies HMAC idempotency, stale-lock recovery, privacy allowlists, committed-work-only AI events, persistent-link ordering, stable onboarding completion, refunds, admin authorization, bounded periods/queries, and gross/refund/net reporting.
- Current full suite after lifecycle analytics: 14 deploy PHP lint commands, 12 Node contract suites, and 3 legacy PHP suites passed (29 validation commands, 0 failures).
- PHP lint: `deploy/bds-sales-lead-pipeline-mu.php`, follow-up worker 0.1.1, and lifecycle ledger 0.2.0.
- `node tests/sales-lead-pipeline-contract.mjs` verifies default-off capture, private records, owned origins, request/rate/field bounds, HMAC deduplication, server-owned stages, Woo/trial/onboarding/revocation hooks, separate consent, suppression-safe unsubscribe, admin authorization/pagination, retention/anonymization, internal lifecycle events, and absence of outbound send/fetch behavior.
- Current full suite after the lead pipeline: 15 deploy PHP lint commands, 13 Node contract suites, and 3 legacy PHP suites passed (31 validation commands, 0 failures).
- PHP lint: `deploy/bds-website-monitoring-mu.php`, customer status API 0.2.0, and lifecycle ledger 0.3.0.
- `node tests/website-monitoring-contract.mjs` verifies default-off paid entitlement, onboarding-only destination, customer authorization, public A/AAAA resolution, URL credential/IP/port rejection, per-check DNS validation, safe bounded no-redirect fetch, no body retention or messaging, manual rate limits, stale locks, plan/URL shutdown, bounded scheduler continuation, 24-period evidence, report integration, and authenticated routes.
- Current full suite after website monitoring: 16 deploy PHP lint commands, 14 Node contract suites, and 3 legacy PHP suites passed (33 validation commands, 0 failures).
- PHP lint: `deploy/bds-customer-inquiries-mu.php`, customer status API 0.3.0, and lifecycle ledger 0.4.0.
- `node tests/customer-inquiries-contract.mjs` verifies default-off/private records, paid eligibility, opaque unique owner references, owned origins, body/rate/honeypot bounds, privacy/contact consent, server-owned owner/status, opaque public responses, author-scoped pagination, cross-account 404, optimistic status versions, actual monthly counts, bounded retention, PII-free lifecycle evidence, and no outbound send/fetch.
- Current full suite after customer inquiries: 17 deploy PHP lint commands, 15 Node contract suites, and 3 legacy PHP suites passed (35 validation commands, 0 failures).
- Subscription bridge 0.5.0 contract adds latest-renewal reconciliation, same-cycle credit protection, legacy marker migration, stale-lock recovery, and bounded continuation checks.
- `rafter secrets .`: no hardcoded secrets detected (exit 0; Betterleaks output-version warning noted).

## Required security scan

- `rafter run` fast was attempted. It was blocked before scanning because the account has used 16/16 fast scans; quota resets 2026-11-23.
- Paid Plus was not run because it consumes credits and requires explicit approval.
- Manual OWASP web/API/LLM evidence and corrected findings are recorded in `BRANDDAD_SECURITY_REVIEW.md`.

## Verified connected state

- FormWise organization: BrandDad Social (`branddad-social`), owner role.
- Published App: BrandDad AI Studio.
- Published Agent: BrandDad Sales Assistant.
- Published execution flows include Copy and Reviews, SEO Action Planner, Website Improvement Planner, Directory Listing Studio, Directory Finder, LinkedIn Profile Analyzer, Review Dispute Assistant, Campaign Brief Builder, and Marketing Strategy Builder.
- Tier plans confirmed: Starter 50/$14.99, Growth 150/$29.99, Pro 400/$49.99, Business Essentials 75/$29.99, AI Lead Assistant 80/$19.99, AI Local SEO 60/$19.99, AI Social Growth 150/$29.99.

## Not yet tested

- Live WordPress route execution, cron/Action Scheduler processing, and FormWise response parsing with a new paid provider run.
- Cross-host account creation and cookie handoff on staging.
- Email-verification filter integration on both hosts.
- Real trial purchase, provisioning, renewal, failed renewal, paid-through cancellation, refund, and billing portal.
- Real Featured Woo purchase and placement handoff.
- Follow-up sending, social publishing, WhatsApp, SEO publishing, music delivery, and video generation.
- Real mail transport acceptance, one-click unsubscribe, retry, crash/uncertain-delivery review, and converted-customer stop behavior.
- Onboarding REST nonce, cross-account denial, optimistic version conflict, Directory-free access, and completed-job detection in WordPress staging.
- Lifecycle event deduplication under duplicate hooks, partial/full refund aggregation, admin-capability denial, month-boundary behavior, and bounded truncation in WordPress staging.
- Lead capture owned-origin/rate behavior, concurrent deduplication, verified stage hook ordering, admin authorization, opt-out propagation, and scheduled anonymization in WordPress staging.
- Website-monitor configuration authorization, public/private DNS cases, safe real fetch, redirect/timeout behavior, concurrent/manual scheduling, plan loss, profile URL change, batch cursor, and actual value-report counters on WordPress staging.
- Customer inquiry public form, spam/rate behavior, owner reference uniqueness, cross-account denial, nonce protection, concurrent version conflict, status workflow, retention anonymization, and real value-report counts on WordPress staging.
