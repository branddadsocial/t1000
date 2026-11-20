# BrandDad backend deployment manifest

Prepared: 2026-11-19

This manifest describes the reviewed Codex backend artifacts. It does not authorize a production deployment or activation of paid/external services. Deploy to an isolated WordPress staging environment first. All files target `wp-content/mu-plugins/` unless noted otherwise.

## Hosting paths verified read-only

- BrandDad Social: `public_html/branddad.social/wp-content/mu-plugins/`
- BrandDad Directory: `public_html/directory.branddad.social/wp-content/mu-plugins/`
- Existing host-level rollback area: `_bds_deploy_backups/`

Isolated Social staging is available at `public_html/staging.branddad.social/`; its valid public TLS hostname is `staging-branddad-social.stackstaging.com`. The owner has classified the earlier hosting-panel detections as false positives. Production security posture still requires routine monitoring and must not be inferred from that classification alone.

## Social package

Prepared archive: `release/2026-11-19/branddad-social-backend-2026-11-19.zip`

Install only after exact-file backups and database backup verification:

- `bds-account-link-mu.php`
- `bds-ai-commercial-catalog-mu.php`
- `bds-ai-execution-mu.php`
- `bds-ai-launch-pages-mu.php`
- `bds-ai-subscriptions-mu.php`
- `bds-ai-trial-mu.php`
- `bds-consent-followup-worker-mu.php`
- `bds-customer-inquiries-mu.php`
- `bds-customer-status-api-mu.php`
- `bds-directory-membership-bridge-mu.php`
- `bds-lifecycle-events-mu.php`
- `bds-onboarding-api-mu.php`
- `bds-provider-cost-ledger-mu.php`
- `bds-sales-assistant-api-mu.php`
- `bds-sales-lead-pipeline-mu.php`
- `bds-website-monitoring-mu.php`

## Directory package

Prepared archive: `release/2026-11-19/branddad-directory-backend-2026-11-19.zip`

Install only after exact-file backups and database backup verification:

- `bds-account-link-mu.php`
- `bds-customer-status-api-mu.php` only if the Directory dashboard consumes this API
- `bds-directory-free-ai-mu.php`
- `bds-directory-membership-bridge-mu.php`
- `bds-featured-billing-adapter-mu.php`
- `bds-lifecycle-events-mu.php` only when local Directory lifecycle events are required
- `bds-onboarding-api-mu.php` only if onboarding is exposed on Directory

The Featured adapter must be deployed together with Cursor's Directory Featured modules and only after the four WooCommerce SKUs, claimed-listing handoff, authoritative geography callback, and inventory/capacity integration exist.

## Server-only configuration

Never add these values to Git, frontend JavaScript, a public upload, or this manifest:

- `BDS_FORMWISE_EXECUTION_KEY`: least-privilege execution key.
- `BDS_FORMWISE_PROVISIONING_KEY`: least-privilege provisioning key.
- `BDS_ACCOUNT_LINK_SECRET`: identical 32+ byte secret on both owned hosts.
- `bds_account_link_email_verified`: authoritative verified-email filter on both hosts.
- `bds_lead_email_verified`: authoritative verified-email filter on Social.

Keep these switches false or undefined for the first deployment:

- `BDS_AI_ENTITLEMENTS_ENABLED`
- `BDS_AI_LAUNCH_PAGES_ENABLED`
- `BDS_SALES_ASSISTANT_ENABLED`
- `BDS_FOLLOWUP_ENABLED`
- `BDS_LEAD_CAPTURE_ENABLED`
- `BDS_WEBSITE_MONITORING_ENABLED`
- `BDS_CUSTOMER_INQUIRIES_ENABLED`

Enable one capability at a time only after its staging acceptance tests, provider permission checks, rate/cost controls, and rollback test pass.

## Reversible deployment procedure

1. Record installed versions and SHA-256 hashes of every target file.
2. Export the affected WordPress database tables/options and verify the backup opens.
3. Copy each replaced target file into a timestamped folder under `_bds_deploy_backups/` while preserving its host and relative path.
4. Upload to a temporary non-executable staging folder on the same filesystem.
5. Verify uploaded size and SHA-256 against `SHA256SUMS.txt`.
6. Move one file at a time into the target `mu-plugins` directory using an atomic same-filesystem rename where the host permits it.
7. Confirm WordPress front page, login, REST index, WooCommerce checkout, cron/Action Scheduler, and error log after every activation group.
8. Leave all default-off capabilities disabled until their individual acceptance gates pass.
9. Roll back immediately on a fatal error, unexpected entitlement, duplicate order/credit, cross-tenant access, or provider call outside the approved allowlist.

## Rollback procedure

1. Disable the newly enabled feature constant first.
2. Restore the exact prior file from the timestamped backup with an atomic same-filesystem rename.
3. Confirm file hash, WordPress availability, REST availability, and checkout health.
4. Reconcile pending jobs and lifecycle records before retrying.
5. Never delete customer, order, subscription, referral, entitlement, or audit history as part of rollback.

## Required acceptance evidence

- PHP lint and local contract suites pass on the exact packaged files.
- Local and MCP Rafter secret scans are clean.
- Remote `rafter run --mode fast` passes after quota resets.
- WordPress staging tests cover authentication, permissions, nonces, replay, rate limits, duplicate webhooks, provider outage, tenant isolation, and rollback.
- A safe test purchase proves payment, provisioning, one successful AI result, one usage deduction, renewal, cancellation, and preserved free access.

