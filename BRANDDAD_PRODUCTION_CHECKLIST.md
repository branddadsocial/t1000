# BrandDad production checklist

## Source and coordination

- [x] Locate repo, domains, branches, worktrees, and Cursor ownership.
- [x] Create isolated Codex branch/worktree.
- [x] Preserve existing dirty worktrees and customer agreements.
- [x] Document API and Cursor handoff.

## Security and code quality

- [x] Complete secure-design/threat-model pass.
- [x] Keep secrets out of Git/frontend/logs.
- [x] Add explicit tool/provider/host allowlists and bounded inputs/outputs.
- [x] Add local PHP lint and contract tests.
- [x] Complete Rafter code-review walk, local secret scan, and document fast-scan quota blocker.
- [ ] Run `rafter run` fast after quota resets and reconcile findings.
- [ ] Test cross-account denial, CSRF/nonce, replay, rate-limit, and provider-outage cases in WordPress.
- [x] Add a verified-only provider-cost ledger with no public write route and explicit incomplete-coverage reporting.
- [x] Reject negative and fractional provider costs before WordPress integer normalization.
- [x] Exercise provider-cost validation, replay, privacy, admin denial, and contribution deltas in isolated WordPress staging.
- [ ] Reconcile provider/processor/invoice costs and prove contribution totals against accounting records before displaying a margin as complete.

## Deployment

- [x] Verify the protected File Manager path and both live WordPress roots read-only.
- [x] Create an isolated hosted staging document root and separate database.
- [x] Upload the checksum-reviewed Social package to staging without changing production files.
- [x] Complete the isolated WordPress administrator setup and verify staging REST activation.
- [x] Verify public catalog access and anonymous denial on private customer/admin routes.
- [x] Safely clone and activate WooCommerce, WooCommerce Stripe Gateway, and Subscriptions for WooCommerce on isolated staging.
- [x] Prepare and review an exact-host staging-only Business Essentials product fixture with fixed price/trial metadata and no payment/customer mutation.
- [x] Create the approved staging-only Business Essentials Woo product fixture and verify its canonical catalog mapping, hidden visibility, disposable cart behavior, and absence of payment methods.
- [ ] Capture authenticated checkout/database evidence for exact seven-day trial metadata, then configure Stripe test mode before payment lifecycle acceptance testing.
- [x] Run the reviewed synthetic subscription-lifecycle harness on isolated staging; 36/36 trial/grant/replay/renewal/revocation/cancellation/failure/cleanup checks pass.
- [x] Create and verify hidden staging-only fixtures/mappings for AI Studio Starter, AI Local SEO, AI Lead Assistant, and AI Social Growth.
- [ ] Install or safely clone Directorist on staging before Directory ownership/claim/free-AI acceptance testing.
- [x] Use the valid-TLS StartHost temporary staging hostname for acceptance tests; custom-hostname TLS remains an optional DNS/CDN follow-up.
- [x] Execute the approved protected File Manager staging deployment procedure with exact folder selection, reviewed local files, completed upload evidence, hosted acceptance, and production-route isolation. SSH remains a reliability improvement.
- [x] Document exact per-host artifacts, fail-closed switches, checksums/rollback requirements, and acceptance evidence in `BRANDDAD_DEPLOYMENT_MANIFEST.md`.
- [ ] Back up exact MU files and database tables/options before deployment.
- [ ] Configure server-only provisioning key, execution key, and account-link secret.
- [ ] Connect and test the fail-closed source email-verification filter on both hosts.
- [x] Deploy reviewed backend changes to isolated staging/QA first without overwriting production or Cursor-owned public files.
- [ ] Verify rollback by restoring prior MU files.
- [x] Confirm current Social/Directory homepages and REST indexes return HTTP 200 with valid TLS before deployment.
- [x] Confirm the intended `/branddad/v1` routes appear on hosted staging with the documented anonymous authorization behavior.
- [ ] Complete authenticated ownership/capability/nonce behavior and provider/payment lifecycle acceptance on hosted staging before any production deployment.

## Revenue acceptance

- [x] Align the Business Essentials catalog, Woo order, trial, consented Directory bridge, and lifecycle identities on the exact `ai-business-essentials` slug; reject the obsolete prefixed identity.
- [x] AI Business Essentials product exists at $29.99/month with exact seven-day trial terms; live product and checkout UI verified without submitting payment.
- [x] Trial runtime uses authoritative WPS start/end/status fields, a separate 25-credit ledger, and blocks full paid provisioning before renewal.
- [ ] Cursor checkout displays optional free Directory consent and the signed bridge succeeds/replays safely in staging.
- [ ] Safe checkout creates one order/subscription and no duplicate charge.
- [ ] One active subscription grants exactly one FormWise plan.
- [ ] First authenticated AI job completes and deducts the correct customer allowance once.
- [ ] Renewal resets/reconciles exactly once.
- [x] Reconciliation code uses latest paid renewal and a per-paid-order allowance marker; same-cycle reactivation cannot reset credits.
- [~] Synthetic hosted acceptance proves failed-payment/on-hold revocation and stale-parent denial; real Stripe webhook acceptance remains pending.
- [~] Synthetic hosted acceptance proves cancellation revocation and trial-history preservation; real checkout/customer cancellation acceptance remains pending.
- [ ] Customer billing portal and invoice paths work.

## Directory acceptance

- [~] Public registration/plan copy and deployed policy confirm one free basic listing with no mandatory activation payment; focused tests pass. A fresh non-admin registration, verification, submission, moderation, and edit journey still requires action-time account-creation confirmation.
- [ ] Cross-site handoff reuses/creates the correct account and rejects replay/wrong host.
- [x] Complete one-host hosted account-link acceptance for fixed hosts, signature/claims, replay, existing-user resolution, conflicting sessions, form safety, audit privacy, exact restoration, and production isolation.
- [ ] Complete real two-host account-link POST/consume/cookie/session acceptance after isolated Directory staging and a shared production-like secret are available.
- [ ] Verified listing receives only its own free-AI ledger.
- [ ] Featured Woo SKUs exist and only paid orders activate placements.
- [x] Featured adapter rechecks claimed ownership, paid subscription linkage, geography, capacity, renewal, failure, cancellation, expiry, and refund.
- [ ] Cursor passes selected listing + authoritative geography and staging proves all four lanes.
- [ ] Partnero free-to-paid attribution/refund/cancellation events reconcile.
- [x] Preserve the existing deployed Partnero ref bridge; do not create a duplicate attribution cookie layer.
- [ ] Verify current Partnero API/event terms before emitting commission lifecycle events.

## Automation acceptance

- [x] Implement default-off consent-based follow-up with stop/unsubscribe and uncertain-delivery deduplication.
- [x] Exercise the exact-host no-email follow-up consent harness for eligibility, token/hash privacy, compare-and-set updates, opt-out persistence, reactivation blocking, rate bounds, exact restoration, and disabled-worker state (26/26 runtime checks passed).
- [ ] Verify sender, mail acceptance, one-click unsubscribe, conversion stop, and retries in staging before enabling.
- [x] Implement customer-owned onboarding state and first-task guidance API.
- [x] Exercise onboarding ownership isolation and optimistic version conflict in WordPress staging.
- [x] Exercise onboarding field/URL validation, active-lock denial, stale-lock recovery, and customer value-report tenant isolation in WordPress staging.
- [ ] Exercise browser-session REST nonce and paid/Directory-free onboarding access states in WordPress staging.
- [x] Sales assistant recommends only verified products and server-owned links under per-client/global cost caps in isolated hosted acceptance.
- [x] Implement private, deduplicated lead capture and verified lifecycle stage backend with separate email consent and bounded retention.
- [x] Exercise the exact-host sales-lead acceptance harness for origin/rates, validation, deduplication, consent, lifecycle, unsubscribe suppression, fixture-only retention, cleanup, state restoration, and automatic return to disabled (34/34 runtime checks passed).
- [ ] Connect Cursor's form with mandatory privacy acknowledgment and optional unselected email consent; test origin/rates/deduplication/admin denial/unsubscribe/retention before enabling.
- [ ] SEO/social/WhatsApp/video/music adapters have provider-specific permissions, costs, retries, and proof.
- [x] Implement paid-entitlement website availability checks with customer authorization, SSRF controls, bounded queues, actual counters, and no unverified alerts.
- [ ] Run authorized real-host monitor acceptance across DNS/private-IP rejection/redirect/timeout/manual/daily/plan-loss/URL-change/report cases before enabling.
- [x] Exercise the isolated exact-host monitoring harness for SSRF rejection, bounded owned-host fetch, locks, network failure, redirect, plan/URL shutdown, reporting, restoration, and tenant isolation.
- [x] Implement owner-scoped customer inquiry capture/inbox/status with explicit contact consent, spam/rate bounds, actual reports, and closed/lost retention.
- [x] Exercise the exact-host hosted inquiry acceptance harness for tenant denial, versions, rates, consent, replay, retention, reports, narrowly marked fixture cleanup, and state restoration (34/34 runtime checks passed).
- [ ] Connect Cursor's real hosted inquiry form/inbox, validate browser REST nonce and customer-facing consent copy, and verify any approved notification delivery before enabling production capture.
- [x] Monthly reports distinguish generated, approved, submitted, published, and measured outcomes; only completed AI jobs count as generated, and later stages remain explicitly unavailable until authoritative events exist.
- [x] Monthly AI report counts actual job states/completions and explicitly labels currently unavailable outcome metrics.
- [x] Implement a private idempotent lifecycle ledger and admin-only gross/refund/net event aggregate.
- [x] Exercise lifecycle duplicate reuse, subscriber admin-capability denial, privacy allowlisting, and gross/refund/net aggregate deltas in WordPress staging.
- [x] Exercise real Woo partial/full refund hooks in WordPress staging with exact lifecycle/aggregate evidence and cleanup.
- [x] Exercise inclusive current-month UTC start/end boundaries in WordPress staging with exact cleanup and unchanged financial aggregates.
- [ ] Exercise the 5,000-event truncation flag in a higher-capacity isolated WordPress environment or with WP-CLI/database-fixture tooling; the shared-host HTTP attempt was safely recovered at 1,560 rows without affecting customer or financial records.

Nothing unchecked above may be described as fully operational.

## 2026-11-20 live Directory release gate

- [x] Verify the homepage at 390px uses one contained Directory Concierge, light-on-dark logo, compact navigation, organized footer, and Playa del Carmen-first nearby listings without Dubai/Chicago contamination.
- [x] Build, syntax-check, contract-test, manually security-review, secret-scan, package, hash, and re-extract Directory Experience `0.68.14`.
- [x] Upload the reviewed `0.68.14` archive into the signed-in live plugin installer.
- [x] Receive the required action-time confirmation and replace live `0.68.13` with `0.68.14`; WordPress reported success.
- [x] Verify the installed assets advertise `0.68.14`, contain the corrected total-page loop, and no longer contain the `items.length === 100` stop condition.
- [x] At 390px, complete Mexico -> Quintana Roo -> Playa del Carmen -> restaurant type and verify the resulting URL/listings stay location-scoped (36 matches; correct active filters).
- [x] Run the approved exact-homepage cache repair and confirm the clean homepage returns one index/follow robots policy with no stale noindex.
- [x] Check all 45 Directory homepage category destinations and the priority public/protected route behaviors.
- [x] Check Directory responsive behavior at 320, 390, 768, 1024, and 1440 pixels with no horizontal overflow.
- [x] Check Social responsive behavior at 320, 390, 768, 1024, and 1440 pixels with no horizontal overflow, no broken images, and one AI launcher.
- [x] Check the six priority Social sales links and all 93 same-ecosystem homepage destinations; every destination returned HTTP 200.
- [x] Repeat the location-first restaurant journey after the live update at 390px and verify 36 Playa del Carmen matches, one Concierge launcher, and no horizontal overflow.
- [ ] After separate deletion confirmation, remove only `wp-content/mu-plugins/bds-directory-home-cache-repair-mu.php` and verify the admin repair menu disappears.

## 2026-11-20 final launch-readiness audit

- [x] Recheck priority Social and Directory public routes; all returned HTTP 200.
- [x] Verify the installed Directory Experience assets and location-scoped restaurant result after release `0.68.14`.
- [x] Audit live Stripe settings read-only: gateway, payments, payouts, and webhook show enabled; no settings were changed.
- [ ] Connect a safe Stripe test account and prove purchase, provisioning, renewal, refund, cancellation, and billing portal without altering existing customer billing.
- [ ] Investigate why no live Stripe webhook has been recorded since monitoring began on 2026-11-08 before relying on webhook-driven fulfillment.
- [x] Refresh FormWise agents, app, flows, portals, and tier plans without spending credits.
- [ ] Run one approved, controlled FormWise execution and measure result quality, metering, cost, and customer isolation before enabling public external execution.
- [~] Monitor Directory `all-locations` cold performance. HTTP 200 is stable, but one 18-second cold cache-bypass response and later 1.3-6.6 second responses indicate a cache/bootstrap optimization opportunity.

## 2026-11-20 Social scheduler recovery gate

- [x] Audit WooCommerce Action Scheduler counts, past-due hooks, and Site Health without changing state.
- [x] Verify the deployed MU guards disable request-driven cron/async execution and bound real cron/CLI batches.
- [x] Audit StartHost Scheduled Tasks and confirm that no task currently exists.
- [x] Verify active PHP 8.4, `/usr/bin/php84`, and the exact Social WordPress root.
- [x] Document the threat model, allowlisted command, five-minute cadence, acceptance criteria, and rollback in `BRANDDAD_SCHEDULER_RECOVERY_DESIGN.md`.
- [x] Complete and verify a recoverable combined files-and-databases backup; StartHost reports `Backup job completed` and exposes Download.
- [x] Identify the pending financial-adjacent work: exactly two no-argument WP Swings recurring checks are overdue; the active subscription's next payment is 2026-12-13.
- [x] Prepare the exact StartHost task form with the reviewed command and five-minute cadence without testing or saving it.
- [x] Receive exact action-time confirmation for `/usr/bin/php84 -f /home/sites/41a/c/c0a8249376/public_html/branddad.social/wp-cron.php` every five minutes.
- [!] StartHost rejected the first approved save with `Cron failed to add` because the package remains malware-flagged; no task was created and no cron batch ran. Fresh scan `24662257` completed and reproduced six generated-cache false positives plus the historical quarantined file, so the restriction remains.
- [x] Submit the exact false-positive evidence to StartHost/20i support. Ticket `898665003` is open with Web Hosting and requests non-destructive package clearance or a safe allowlist.
- [~] The visible package malware banner cleared, but a second exact approved save still returned `Cron failed to add`; obtain StartHost confirmation that the residual scheduled-task restriction has been released.
- [ ] Reauthenticate to StartHost after support confirms clearance, then re-enter and save the exact reviewed task once.
- [ ] Observe the first bounded interval; confirm pending/past-due counts decrease without duplicate financial/customer effects or site degradation.
- [ ] Recheck Site Health, Action Scheduler, public priority routes, checkout, and authoritative subscription/payment records.
- [ ] Classify historical no-callback failures; any cleanup is a separate destructive action and must not be combined with scheduler recovery.
