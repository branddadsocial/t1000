# BrandDad Cursor handoff

Updated: 2026-11-19

## Do not rebuild

- Public Social homepage/services conversion surfaces, checkout interface, account/studio pages, Directory discovery/frontends, free-listing UI, Featured display/rotation, and customer dashboard presentation remain Cursor-owned.
- Keep the current public pricing and existing-customer exceptions. Do not flatten Directory Pro variants or activate quote-only products.

## New backend interfaces available for integration

- Tool picker: `GET /wp-json/branddad/v1/ai/tools`.
- Start AI job: `POST /wp-json/branddad/v1/ai/jobs` with REST nonce and `Idempotency-Key`.
- Poll AI job: `GET /wp-json/branddad/v1/ai/jobs/{id}`.
- Deterministic sales offers: `GET /wp-json/branddad/v1/sales/catalog`.
- Public assistant: `POST /wp-json/branddad/v1/sales/chat`; render `offers[].url`, never infer a link from `reply`.
- Onboarding state: `GET /wp-json/branddad/v1/onboarding`.
- Save onboarding: `POST /wp-json/branddad/v1/onboarding` with the returned `version` and a `profile` object. On 409, reload before resubmitting.
- Trial state: `GET /wp-json/branddad/v1/trial`; do not calculate trial dates in JavaScript.
- Unified customer state: `GET /wp-json/branddad/v1/account/status`.
- Monthly evidence report: `GET /wp-json/branddad/v1/reports/value?period=YYYY-MM`.
- Admin lifecycle aggregate: `GET /wp-json/branddad/v1/admin/analytics/summary?period=YYYY-MM`; requires `manage_woocommerce`. Present its gross/refund/net fields as confirmed transaction events, never as MRR, profit, or settled cash.
- Cross-site button: call `bds_account_link_form( 'directory.branddad.social', '/account-hub/' )` on Social, and the inverse host on Directory. The form appears only for signed-in users after the shared secret is configured and source email ownership is verified.
- On the Business Essentials checkout, show a clear optional free-Directory-membership consent checkbox and submit it as `bds_directory_membership_consent=1`. Do not preselect it. The backend will create/reuse only a private Directory account after activation; listing creation remains a separate customer action.
- For each Featured product link, preserve the selected claimed listing as `?listing={id}` (or hidden `_bds_feat_listing_id`). Add the `bds_featured_listing_geo` filter returning the listing's real ISO-3166 alpha-2 country and, for Local/Regional, real latitude/longitude. Do not infer geography from the purchaser's billing address.
- Remove the obsolete $6.99 activation requirement and related new-customer copy from the basic Directory registration/listing/claim flow. Free basic access must not pass through a paid product. Preserve historical orders, existing customer agreements, and paid benefits; this is not authorization to delete or rewrite old accounting records.

## Expected frontend states

- AI jobs: pending, running, awaiting_customer_approval, completed, completed_metering_review, failed, cancelled.
- Show `credit_cost` before submission.
- Treat 403 as an upgrade/sign-in state, 429 as allowance exhausted, 503 as temporarily unavailable, and 409 as retry/poll the existing request.
- Do not show provider names, provider UUIDs, raw error bodies, or provider conversation ids.
- Do not claim a website audit is automatic: the currently connected website tool is an improvement plan based on customer-supplied observations.
- Do not claim social, WhatsApp, SEO, or video publishing until those adapters have separate production evidence.

## Required deployment configuration

- `BDS_FORMWISE_PROVISIONING_KEY` (existing server-only provisioning key).
- `BDS_FORMWISE_EXECUTION_KEY` (separate least-privilege execution key).
- `BDS_AI_ENTITLEMENTS_ENABLED` only during/after lifecycle acceptance.
- `BDS_ACCOUNT_LINK_SECRET` identical on both hosts, 32+ random bytes, outside Git.
- `BDS_SALES_ASSISTANT_ENABLED` remains false until abuse/cost test passes.
- Optional `BDS_SALES_ASSISTANT_DAILY_LIMIT` (default 100 provider calls/day).
- `BDS_FOLLOWUP_ENABLED` remains false until sender/domain/staging approval.
- `BDS_FOLLOWUP_FROM_EMAIL` must be a verified BrandDad-domain sender.

## Required email-verification bridge

The account-link plugin fails closed. On each host, the existing verified-email workflow must return true only after it has proved ownership:

```php
add_filter( 'bds_account_link_email_verified', function ( $verified, $user ) {
	return 'yes' === get_user_meta( $user->ID, '_your_verified_email_meta', true );
}, 10, 2 );
```

Replace the example meta key with the authoritative key from the existing registration system. Do not return true merely because an email string exists on the WordPress user.

## Files to deploy together after staging

- `deploy/bds-ai-subscriptions-mu.php`
- `deploy/bds-directory-free-ai-mu.php` on Directory
- `deploy/bds-ai-execution-mu.php`
- `deploy/bds-ai-trial-mu.php` on Social.
- `deploy/bds-customer-status-api-mu.php` on each customer-dashboard host.
- `deploy/bds-directory-membership-bridge-mu.php` on both hosts; it self-restricts sender/receiver behavior by exact host.
- `deploy/bds-featured-billing-adapter-mu.php` on Directory together with Cursor's Featured MU. It removes the older paid activation hooks and replaces them with ownership-, subscription-, geography-, renewal-, failure-, cancellation-, and refund-aware lifecycle handling.
- `deploy/bds-account-link-mu.php` on both hosts
- `deploy/bds-sales-assistant-api-mu.php` on Social
- `deploy/bds-onboarding-api-mu.php` on each dashboard host that exposes onboarding.
- `deploy/bds-consent-followup-worker-mu.php` only beside Cursor's existing consent plugin and only after mail acceptance testing.
- `deploy/bds-lifecycle-events-mu.php` on Social with the subscription/trial/execution/onboarding plugins; install on Directory too only if that host is expected to record local registration/link events. It stores private allowlisted events and exposes only the admin aggregate.

Do not deploy this branch over newer Cursor public-page files. Merge only the backend files and documented helper calls.
