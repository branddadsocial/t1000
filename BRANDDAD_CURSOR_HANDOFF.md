# BrandDad Cursor handoff

## Admin contribution reporting

The backend now includes `deploy/bds-provider-cost-ledger-mu.php` for Social. Cursor may render `GET /wp-json/branddad/v1/admin/finance/contribution?period=YYYY-MM` only inside a `manage_woocommerce` administration surface. Preserve the response labels: this is recorded contribution from confirmed revenue and verified stored variable costs, not final profit. Do not infer missing provider costs or show a green margin as complete when `cost_coverage_state` is `partial_unless_reconciled` or `no_verified_costs`.

Provider adapters must record costs server-side through the documented internal function/action; no browser form should call a cost-write route because none exists.

Updated: 2026-11-20

## Do not rebuild

- Public Social homepage/services conversion surfaces, checkout interface, account/studio pages, Directory discovery/frontends, free-listing UI, Featured display/rotation, and customer dashboard presentation remain Cursor-owned.
- Keep the current public pricing and existing-customer exceptions. Do not flatten Directory Pro variants or activate quote-only products.

## New backend interfaces available for integration

- Tool picker: `GET /wp-json/branddad/v1/ai/tools`.
- Start AI job: `POST /wp-json/branddad/v1/ai/jobs` with REST nonce and `Idempotency-Key`.
- Poll AI job: `GET /wp-json/branddad/v1/ai/jobs/{id}`.
- Deterministic sales offers: `GET /wp-json/branddad/v1/sales/catalog`.
- Public assistant: `POST /wp-json/branddad/v1/sales/chat`; render `offers[].url`, never infer a link from `reply`.
- Public lead capture: `POST /wp-json/branddad/v1/sales/leads`. Send `email`, bounded `business_type`/`primary_need`, allowlisted `source`, mandatory `privacy_ack=true`, and optional `email_consent=true`. Privacy acceptance and email marketing consent must be separate; do not preselect email consent. A signed-in user must submit the account email. If `email_confirmation_required` is true, do not claim a confirmation was sent; ask the customer to verify/sign in and explicitly opt in again. Render only the server recommendation/link returned.
- Admin lead list: `GET /wp-json/branddad/v1/admin/leads?page=1&stage=interested`; requires `manage_woocommerce`. Do not expose this response on a customer dashboard.
- Website monitor state: `GET /wp-json/branddad/v1/monitor/site`. Configure with `POST` body `{ "enabled": true, "authorization_confirmed": true }`; the displayed destination must be the stored onboarding URL and the checkbox must clearly say the customer owns, manages, or has permission to monitor it. Do not add a separate arbitrary URL field.
- Manual monitor run: `POST /wp-json/branddad/v1/monitor/site/check`; show 429 as “already requested this hour,” 422 as “add/authorize your website,” and `needs_reconnect` after the onboarding URL changes. Do not promise alerts until the sender adapter is verified.
- Inquiry form control: `GET/POST /wp-json/branddad/v1/inquiries/form`. Use the returned opaque reference in a BrandDad-hosted public form; do not treat it as an account secret or expose the private inbox.
- Public inquiry submit: `POST /wp-json/branddad/v1/inquiries/submit` with a new 16–64 character `Idempotency-Key` per submission (reuse it only for an exact retry), the documented honeypot, privacy acknowledgment, separate contact consent, bounded contact fields/message, and form reference. Do not claim the business was notified until a notification adapter is verified.
- Customer inbox: `GET /wp-json/branddad/v1/inquiries?page=1&status=new`; update with `POST /wp-json/branddad/v1/inquiries/{id}` and the current `version`. On 409 reload; another customer's id intentionally looks like 404.
- Onboarding state: `GET /wp-json/branddad/v1/onboarding`.
- Save onboarding: `POST /wp-json/branddad/v1/onboarding` with the returned `version` and a `profile` object. On 409, reload before resubmitting.
- Trial state: `GET /wp-json/branddad/v1/trial`; do not calculate trial dates in JavaScript.
- Business Essentials product identity is exactly `ai-business-essentials`. Use that slug for product links and checkout presentation; do not use the obsolete/nonexistent `branddad-ai-business-essentials` alias.
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
- `BDS_LEAD_CAPTURE_ENABLED` remains false until the public form/privacy copy and staging security/retention tests pass.
- Optional `BDS_LEAD_CAPTURE_DAILY_LIMIT` (bounded to 100–10,000; default 2,000) and `BDS_LEAD_RETENTION_DAYS` (bounded to 30–365; default 180).
- `BDS_WEBSITE_MONITORING_ENABLED` remains false until a real-host DNS/queue/fetch acceptance test passes.
- `BDS_CUSTOMER_INQUIRIES_ENABLED` remains false until hosted-form, isolation, consent, spam/rate, retention, and report acceptance passes. Optional `BDS_CUSTOMER_INQUIRIES_DAILY_LIMIT` is bounded to 100–10,000 (default 2,000); `BDS_CUSTOMER_INQUIRY_RETENTION_DAYS` is bounded to 90–730 (default 365).

## Required email-verification bridge

The account-link plugin fails closed. On each host, the existing verified-email workflow must return true only after it has proved ownership:

```php
add_filter( 'bds_account_link_email_verified', function ( $verified, $user ) {
	return 'yes' === get_user_meta( $user->ID, '_your_verified_email_meta', true );
}, 10, 2 );
```

Replace the example meta key with the authoritative key from the existing registration system. Do not return true merely because an email string exists on the WordPress user.

Use that same authoritative proof for lead consent; it must fail closed:

```php
add_filter( 'bds_lead_email_verified', function ( $verified, $user ) {
	return 'yes' === get_user_meta( $user->ID, '_your_verified_email_meta', true );
}, 10, 2 );
```

Do not auto-activate an earlier anonymous consent request after a later registration. Once verified, show the optional consent again and resubmit only if the customer selects it.

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
- `deploy/bds-sales-lead-pipeline-mu.php` on Social beside the catalog, trial/subscription, onboarding, lifecycle, and follow-up plugins. Deploy disabled first; enable capture only after Cursor's privacy/consent form and staging acceptance.
- `deploy/bds-website-monitoring-mu.php` on Social beside subscriptions, onboarding, customer status, and lifecycle. Deploy disabled first; enable only after real-host staging acceptance.
- `deploy/bds-customer-inquiries-mu.php` on Social beside subscriptions, customer status, and lifecycle. Deploy disabled first; enable only after the hosted form and tenant-isolation staging acceptance.

Do not deploy this branch over newer Cursor public-page files. Merge only the backend files and documented helper calls.

## 2026-11-20 Airbnb one-review product handoff

- Offer: **Airbnb Review Appeal Assistant — One Review**, proposed **$9.99 one time**.
- This is separate from the $9.99/month Review Assistant. Do not present it as a subscription or done-for-you submission service.
- Product slug: `branddad-ai-review-dispute-assistant` (existing Woo product id observed as 53640; resolve by slug in code rather than hard-coding the id).
- AI job tool: `airbnb_review_appeal` through `POST /wp-json/branddad/v1/ai/jobs`, then poll the returned customer-owned job URL.
- Use the exact request contract in `BRANDDAD_SHARED_API_CONTRACTS.md`. Generate a new 16–64 character idempotency key for a new case and reuse it only for a retry of the exact same body.
- The backend fixes Airbnb and derives the paid order/line entitlement. Never ask the browser for an order id, product id, provider id, flow id, or customer id.
- UI must require login, show a redaction warning, require the authorization affirmation, escape the returned plain text, and repeat that Airbnb makes the final decision.
- The service page copy in backend launch source has been rewritten around “one review, one payment, one guided appeal.” Merge the copy into Cursor's newer public-page version rather than overwriting it.
- Checkout remains fail-closed until hosted staging proves payment, entitlement, one usable result, second-case denial, failed-run release, and refund denial. After that acceptance, set the exact Woo simple/virtual product to $9.99, sold individually, update the catalog launch state, and verify cart/checkout/result on mobile and desktop.
# 2026-11-20 public audit-page presentation note

- The free website-audit page is clean at 320px, 390px, and 430px: no horizontal overflow remains, and the 390px content audit found no old pricing, provider branding, or green primary CTA.
- The small EN/ES switch is still visible. The user previously requested removing translator clutter, while the current master requirements preserve multilingual support. Keep translation behavior but confirm whether the compact switch should remain or be visually relocated; do not restore a floating translator widget.
- No frontend change was made in this Codex batch.

## 2026-11-20 Directory homepage accessibility/legacy-DOM cleanup

- At a measured 390px viewport, Social Home, Services, Business Essentials, the website-audit page, and the Directory claim page each expose one visible H1. Their visible images have `alt` attributes, and the visible form controls sampled have associated labels/ARIA naming.
- Directory Home contains an H1 (`Find a business. See what it offers. Contact it.`), but it is inside a zero-size legacy block at 390px. The current visible page begins with `Start With Your Location` as an H2. Promote the current visible hero/area heading to the single visible H1, or otherwise expose one meaningful visible H1 without duplicating headings.
- The zero-size legacy DOM still contains older homepage sections, including `Brand gift cards under face value`, even though that section is not visible. Remove the legacy gift-card/homepage block from the public DOM while preserving any legitimate backend records and secondary access, as required by the master prompt.
- Do not re-enable the hidden legacy sections merely to expose their H1. Remove/consolidate the obsolete markup and keep the current local-first visible journey.

## 2026-11-20 CTA color-contrast follow-up

- Computed mobile styles show no blue-on-blue or dark-on-dark key CTA combination. Directory primary buttons and the Concierge use white on `#0668D8`, which measures approximately 5.28:1 and passes the 4.5:1 WCAG AA normal-text threshold.
- Social `.bdai-button` and `.bds-rev-button` use 16px/800 white text on `#0878ED`, which measures approximately 4.27:1 and misses that threshold. The light, ghost, and navy variants pass comfortably.
- Use the existing Directory blue `#0668D8` (or another approved BrandDad blue at 4.5:1 or better) for the Social primary CTA background. Preserve white text and the current light-on-dark rule. Recheck default, hover, focus, active, and disabled states after the frontend change.

## 2026-11-20 local source fixes ready for deployment review

- The contrast correction is implemented locally in the existing dirty `branddad-cursor-revenue` worktree: launch pages `0.5.24`, revenue sections `0.2.21`, customer surfaces `0.2.5`, Directory Featured `0.2.13`, and the local Codex bridge `0.1.1` use `#0668D8`. The focused source/test suite passes. Do not overwrite unrelated uncommitted Cursor work when selecting these changes.
- The Directory H1 repair is implemented locally in `branddad-seoestore/deploy/bds-directory-qa-repair-mu.php` `1.0.20`. It keeps `#bdd-title` as the preferred H1, demotes the retired WhatsApp heading, and hides only the retired wrapper when that wrapper does not contain the current hero. The Directory behavior contract passes.
- The existing gift-slider source already fails closed outside `/gift-card-marketplace/`; after deploying the corrected QA repair and current front-compose sources, purge the generated Directory HTML cache so stale hidden homepage gift markup cannot remain in public DOM.
- These changes are locally verified, not deployed. After deployment, verify one visible H1, zero legacy gift-homepage text, one Concierge launcher, white-on-`#0668D8` primary actions, focus/hover/active states, and no overflow at 320/390/430px before closing the frontend gates.
