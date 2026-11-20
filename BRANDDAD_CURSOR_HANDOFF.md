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
- Cross-site button: call `bds_account_link_form( 'directory.branddad.social', '/account-hub/' )` on Social, and the inverse host on Directory. The form appears only for signed-in users after the shared secret is configured and source email ownership is verified.

## Expected frontend states

- AI jobs: pending, running, completed, completed_metering_review, failed.
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
- `deploy/bds-account-link-mu.php` on both hosts
- `deploy/bds-sales-assistant-api-mu.php` on Social

Do not deploy this branch over newer Cursor public-page files. Merge only the backend files and documented helper calls.
