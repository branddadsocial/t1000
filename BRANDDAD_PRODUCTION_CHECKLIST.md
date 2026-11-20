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
- [ ] Safe checkout creates one order/subscription and no duplicate charge.
- [ ] One active subscription grants exactly one FormWise plan.
- [ ] First authenticated AI job completes and deducts the correct customer allowance once.
- [ ] Renewal resets/reconciles exactly once.
- [ ] Failed payment/on-hold revokes according to policy.
- [ ] Cancellation prevents unauthorized renewal and preserves permitted records/free access.
- [ ] Customer billing portal and invoice paths work.

## Directory acceptance

- [ ] Free registration/basic listing has no payment dependency.
- [ ] Cross-site handoff reuses/creates the correct account and rejects replay/wrong host.
- [ ] Verified listing receives only its own free-AI ledger.
- [ ] Featured Woo SKUs exist and only paid orders activate placements.
- [ ] Partnero free-to-paid attribution/refund/cancellation events reconcile.

## Automation acceptance

- [ ] Consent-based follow-up honors stop/unsubscribe and deduplicates.
- [ ] Sales assistant recommends only verified products and links under cost caps.
- [ ] SEO/social/WhatsApp/video/music adapters have provider-specific permissions, costs, retries, and proof.
- [ ] Monthly reports distinguish generated, approved, submitted, published, and measured outcomes.

Nothing unchecked above may be described as fully operational.
