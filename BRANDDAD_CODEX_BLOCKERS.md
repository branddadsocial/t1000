# BrandDad Codex blockers

Updated: 2026-11-19

## External or owner-controlled

- Production SSH key exchange/authentication is not working from this PC. Previous File Manager deployment succeeded, but a reliable protected deployment path and rollback must be restored.
- A separate least-privilege `flows:execute`/`apps:execute` FormWise key must be present as `BDS_FORMWISE_EXECUTION_KEY` on the server. Do not reuse a broad key in browser code or repository files.
- `BDS_ACCOUNT_LINK_SECRET` must be the same 32+ byte secret on both owned WordPress installs and must be distributed outside Git.
- Cursor must add the optional, unselected `bds_directory_membership_consent` checkbox to Business Essentials checkout before automatic free Directory membership can run.
- Cursor's current Directory registration path still advertises/requires the obsolete $6.99 activation purchase. The authoritative requirement is free new basic registration, claim, listing creation, and management. Cursor must remove only the new-customer payment dependency and related copy while preserving every historical order, agreement, and existing paid benefit.
- The existing registration/email-verification system must set the `bds_account_link_email_verified` filter to true only for a verified source email. Account handoff intentionally fails closed until this is connected.
- The same authoritative verification source must connect `bds_lead_email_verified`; otherwise lead capture still works but all requested email marketing consent remains inactive and no lead enters the follow-up queue.
- Sales-assistant execution remains disabled until `BDS_SALES_ASSISTANT_ENABLED` is explicitly enabled after cost and abuse tests.
- Follow-up sending remains disabled until the Cursor consent plugin is present on the target host, a BrandDad-domain sender is verified, mail acceptance/unsubscribe tests pass, and `BDS_FOLLOWUP_ENABLED` is deliberately enabled.
- Public lead capture remains disabled until Cursor supplies clear privacy-acknowledgment text plus a separate optional, unselected email-consent control, the privacy/retention text is approved, and staging proves rate limits, deduplication, admin access, lifecycle hooks, unsubscribe propagation, and anonymization. Then set `BDS_LEAD_CAPTURE_ENABLED=true` server-side.
- Website monitoring remains disabled until Cursor shows the stored onboarding URL plus an explicit ownership/management-permission checkbox and WordPress staging proves public DNS resolution, private/reserved rejection, safe HTTP behavior, Action Scheduler/cron execution, URL-change shutdown, plan-loss shutdown, and actual monthly counters. Then set `BDS_WEBSITE_MONITORING_ENABLED=true` server-side. Failure alerts still require the verified sender.
- New Directory Featured SKUs (`bds-feat-local`, `bds-feat-regional`, `bds-feat-national`, `bds-feat-global`) do not exist; billing integration cannot be production-tested without them.
- Cursor's Featured purchase links must carry the selected claimed listing and provide `bds_featured_listing_geo`; the new adapter intentionally refuses non-global activation without authoritative country and Local/Regional activation without coordinates.
- Complete AI packages and music products remain quote-only until delivery cost/licensing/fulfillment approval.
- Make is reported over its free operation allowance until the provider reset; do not promise Make-backed execution.
- Social publishing, WhatsApp, video rendering, and music distribution need verified provider credentials/permissions and cost ceilings. No unauthorized provider purchase was made.
- Rafter fast scan quota is exhausted (16/16) until 2026-11-23. The required scan was attempted and blocked; local secret scanning and the full manual review completed. Paid Plus was not used.

## Test environment

- No isolated WordPress staging database with Woo/HPOS/Directorist/FormWise server constants is available in this worktree.
- WP-CLI, Docker, Composer, and a local WordPress database are not installed on this machine; real WordPress hook/REST/queue tests cannot honestly be replaced with local mocks.
- The lifecycle ledger is locally linted/contract-tested but still needs WordPress staging evidence for duplicate hooks, partial/full refunds, UTC period boundaries, capability denial, and the 5,000-event truncation flag.
- The lead pipeline is locally linted/contract-tested but still needs WordPress staging evidence for public origin/rate behavior, concurrent email deduplication, Woo hook ordering, cross-account admin denial, consent-list compatibility, scheduled retention, and recovery after locks/timeouts.
- Website monitoring is locally linted/contract-tested but still needs a real-host safe fetch plus DNS, redirect, timeout, queue, concurrency, report, and entitlement lifecycle evidence.
- Provider contract tests are static/local; no new FormWise execution was spent in this pass.
- A complete live AI Business Essentials trial/purchase/cancel test still requires the intended billing identity and a safe transaction window. The exact installed WP Swings trial fields are now verified in official source and implemented locally.

## Not blockers to local development

- Cursor public conversion surfaces are already live and documented; Codex can continue APIs independently.
- FormWise published assets and tier plan IDs are confirmed through the connected organization.
