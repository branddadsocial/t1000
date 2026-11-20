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

## Deployment

- [ ] Restore reliable SSH or approved protected File Manager procedure.
- [ ] Back up exact MU files and database tables/options before deployment.
- [ ] Configure server-only provisioning key, execution key, and account-link secret.
- [ ] Connect and test the fail-closed source email-verification filter on both hosts.
- [ ] Deploy to staging/QA first; do not overwrite newer Cursor public files.
- [ ] Verify rollback by restoring prior MU files.

## Revenue acceptance

- [ ] AI Business Essentials product exists at $29.99/month with exact seven-day trial terms.
- [x] Trial runtime uses authoritative WPS start/end/status fields, a separate 25-credit ledger, and blocks full paid provisioning before renewal.
- [ ] Cursor checkout displays optional free Directory consent and the signed bridge succeeds/replays safely in staging.
- [ ] Safe checkout creates one order/subscription and no duplicate charge.
- [ ] One active subscription grants exactly one FormWise plan.
- [ ] First authenticated AI job completes and deducts the correct customer allowance once.
- [ ] Renewal resets/reconciles exactly once.
- [x] Reconciliation code uses latest paid renewal and a per-paid-order allowance marker; same-cycle reactivation cannot reset credits.
- [ ] Failed payment/on-hold revokes according to policy.
- [ ] Cancellation prevents unauthorized renewal and preserves permitted records/free access.
- [ ] Customer billing portal and invoice paths work.

## Directory acceptance

- [ ] Free registration/basic listing has no payment dependency.
- [ ] Cross-site handoff reuses/creates the correct account and rejects replay/wrong host.
- [ ] Verified listing receives only its own free-AI ledger.
- [ ] Featured Woo SKUs exist and only paid orders activate placements.
- [x] Featured adapter rechecks claimed ownership, paid subscription linkage, geography, capacity, renewal, failure, cancellation, expiry, and refund.
- [ ] Cursor passes selected listing + authoritative geography and staging proves all four lanes.
- [ ] Partnero free-to-paid attribution/refund/cancellation events reconcile.

## Automation acceptance

- [x] Implement default-off consent-based follow-up with stop/unsubscribe and uncertain-delivery deduplication.
- [ ] Verify sender, mail acceptance, one-click unsubscribe, conversion stop, and retries in staging before enabling.
- [x] Implement customer-owned onboarding state and first-task guidance API.
- [ ] Exercise onboarding nonce, ownership, version conflict, and access states in WordPress staging.
- [ ] Sales assistant recommends only verified products and links under cost caps.
- [ ] SEO/social/WhatsApp/video/music adapters have provider-specific permissions, costs, retries, and proof.
- [ ] Monthly reports distinguish generated, approved, submitted, published, and measured outcomes.
- [x] Monthly AI report counts actual job states/completions and explicitly labels currently unavailable outcome metrics.

Nothing unchecked above may be described as fully operational.
