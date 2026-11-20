# BrandDad Codex blockers

Updated: 2026-11-19

## External or owner-controlled

- Production SSH key exchange/authentication is not working from this PC. Previous File Manager deployment succeeded, but a reliable protected deployment path and rollback must be restored.
- A separate least-privilege `flows:execute`/`apps:execute` FormWise key must be present as `BDS_FORMWISE_EXECUTION_KEY` on the server. Do not reuse a broad key in browser code or repository files.
- `BDS_ACCOUNT_LINK_SECRET` must be the same 32+ byte secret on both owned WordPress installs and must be distributed outside Git.
- The existing registration/email-verification system must set the `bds_account_link_email_verified` filter to true only for a verified source email. Account handoff intentionally fails closed until this is connected.
- Sales-assistant execution remains disabled until `BDS_SALES_ASSISTANT_ENABLED` is explicitly enabled after cost and abuse tests.
- New Directory Featured SKUs (`bds-feat-local`, `bds-feat-regional`, `bds-feat-national`, `bds-feat-global`) do not exist; billing integration cannot be production-tested without them.
- Complete AI packages and music products remain quote-only until delivery cost/licensing/fulfillment approval.
- Make is reported over its free operation allowance until the provider reset; do not promise Make-backed execution.
- Social publishing, WhatsApp, video rendering, and music distribution need verified provider credentials/permissions and cost ceilings. No unauthorized provider purchase was made.
- Rafter fast scan quota is exhausted (16/16) until 2026-11-23. The required scan was attempted and blocked; local secret scanning and the full manual review completed. Paid Plus was not used.

## Test environment

- No isolated WordPress staging database with Woo/HPOS/Directorist/FormWise server constants is available in this worktree.
- Provider contract tests are static/local; no new FormWise execution was spent in this pass.
- A complete live AI Business Essentials trial/purchase/cancel test still requires the exact intended billing identity and a safe transaction window.

## Not blockers to local development

- Cursor public conversion surfaces are already live and documented; Codex can continue APIs independently.
- FormWise published assets and tier plan IDs are confirmed through the connected organization.
