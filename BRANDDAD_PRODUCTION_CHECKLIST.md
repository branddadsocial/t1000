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
- [ ] Create any additional approved staging-only product fixtures/mappings required for the launch catalog.
- [ ] Install or safely clone Directorist on staging before Directory ownership/claim/free-AI acceptance testing.
- [ ] Provision valid TLS for `staging.branddad.social` at the authoritative DNS/CDN provider, or use the valid StartHost temporary hostname for acceptance tests.
- [ ] Restore reliable SSH or execute an approved protected File Manager deployment procedure.
- [x] Document exact per-host artifacts, fail-closed switches, checksums/rollback requirements, and acceptance evidence in `BRANDDAD_DEPLOYMENT_MANIFEST.md`.
- [ ] Back up exact MU files and database tables/options before deployment.
- [ ] Configure server-only provisioning key, execution key, and account-link secret.
- [ ] Connect and test the fail-closed source email-verification filter on both hosts.
- [ ] Deploy to staging/QA first; do not overwrite newer Cursor public files.
- [ ] Verify rollback by restoring prior MU files.
- [x] Confirm current Social/Directory homepages and REST indexes return HTTP 200 with valid TLS before deployment.
- [x] Confirm the intended `/branddad/v1` routes appear on hosted staging with the documented anonymous authorization behavior.
- [ ] Complete authenticated ownership/capability/nonce behavior and provider/payment lifecycle acceptance on hosted staging before any production deployment.

## Revenue acceptance

- [x] Align the Business Essentials catalog, Woo order, trial, consented Directory bridge, and lifecycle identities on the exact `ai-business-essentials` slug; reject the obsolete prefixed identity.
- [ ] AI Business Essentials product exists at $29.99/month with exact seven-day trial terms.
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

- [ ] Free registration/basic listing has no payment dependency.
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
- [ ] Monthly reports distinguish generated, approved, submitted, published, and measured outcomes.
- [x] Monthly AI report counts actual job states/completions and explicitly labels currently unavailable outcome metrics.
- [x] Implement a private idempotent lifecycle ledger and admin-only gross/refund/net event aggregate.
- [x] Exercise lifecycle duplicate reuse, subscriber admin-capability denial, privacy allowlisting, and gross/refund/net aggregate deltas in WordPress staging.
- [x] Exercise real Woo partial/full refund hooks in WordPress staging with exact lifecycle/aggregate evidence and cleanup.
- [ ] Exercise UTC month boundaries and the 5,000-event truncation flag in WordPress staging.

Nothing unchecked above may be described as fully operational.
