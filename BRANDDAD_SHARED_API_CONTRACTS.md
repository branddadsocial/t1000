# BrandDad shared API contracts

Base namespace: `/wp-json/branddad/v1`
Authentication: existing WordPress cookie session. Authenticated write calls require the normal WordPress REST nonce (`X-WP-Nonce`).

## GET `/ai/tools`

Purpose: show the signed-in customer only the server-approved tool schemas and customer-credit cost.

Response: `{ "tools": [{ "key", "label", "credit_cost", "fields", "free", "trial" }] }`.

Errors: 401 unauthenticated. No provider ids or credentials are returned.

## POST `/ai/jobs`

Purpose: reserve allowance and enqueue one FormWise execution.

Headers: `Idempotency-Key` of 16–64 safe characters.

Body: `{ "tool": "social_draft", "inputs": { ... }, "access_mode": "paid|directory_free", "listing_id": 123 }`. Trial access is resolved server-side; the browser cannot self-select it.

Rules: `listing_id` is used only for `directory_free`; ownership is rechecked against Directorist. Paid plan identity and allowance cycle are read from active WPS subscription metadata. Unknown fields are discarded. The browser cannot choose a provider id, credit cost, plan id, owner, allowance cycle, or origin.

Paid/trial access takes deterministic precedence over free Directory allowance. A paid allowance cycle advances only when the authoritative paid order id changes; reactivation within the same billing period does not reset credits. The reconciliation worker uses the latest renewal order and never falls back to a stale paid parent after a failed renewal.

Response: HTTP 202 with `{ id, tool, status, credit_cost, result, error_code, created_at, updated_at }` and `Location` header. An idempotent replay returns the existing job.

Errors: 403 no entitlement, 404 unknown tool, 409 in progress, 413 request too large, 422 invalid input/idempotency key, 429 allowance exhausted, 503 provider disabled or job scheduling failed. A scheduling failure releases the reservation.

## GET `/ai/jobs/{id}`

Purpose: poll one job. A customer can read only a job whose `post_author` matches the authenticated WordPress user. Another customer's id returns 404.

## GET `/sales/catalog`

Public deterministic offer data from `bds_ai_commercial_catalog()` and `bds_ai_commercial_checkout_url()`. Empty checkout URL means not for sale.

## POST `/sales/chat`

Public, rate-limited BrandDad Sales Assistant proxy. Body: `{ "message": "...", "session": "optional signed token" }`. Browser origins are restricted to owned BrandDad hosts, request bodies are capped, and continuation tokens are bound to the requesting client.

Response: `{ "reply", "offers", "session", "expires" }`. Model-supplied URLs are removed. Every clickable offer URL comes from the authoritative server catalog. Execution defaults off and has per-IP plus global daily cost limits.

## GET `/onboarding`

Authenticated customer-owned onboarding view model. Response includes `version`, allowlisted `profile`, access mode, step states, first completed job id, recommended allowlisted tool, and `complete`. It never returns provider ids, credentials, or another user's data.

## POST `/onboarding`

Authenticated optimistic update. Body: `{ "version": 0, "profile": { "business_name", "website_url", "industry", "service_area", "primary_goal", "brand_voice" } }`. Unknown fields are discarded. A stale version returns 409. The website field is stored as a reference only and is never fetched by this endpoint.

## GET `/trial`

Authenticated seven-day Business Essentials trial state. Response exposes only `status`, `eligible`, `active`, `trial_days`, bounded trial credits, dates, and days remaining. State is derived from the exact WP Swings subscription product, status, start, and `wps_susbcription_trial_end` values. It never trusts page copy or a browser-provided date. A zero-dollar authorization order cannot provision the full paid FormWise tier; the first verified renewal does that.

## GET `/account/status`

Authenticated customer-owned summary containing access mode (`paid`, `trial`, `directory_free`, or `none`), safe plan slug, Directory listing id where eligible, allowance/used/pending/remaining counts, trial state, onboarding step states, and the authoritative Woo account subscriptions URL. Provider ids and internal ledger keys are excluded.

## GET `/reports/value?period=YYYY-MM`

Authenticated evidence-based monthly report for the current customer. The optional period is limited to the current or preceding 23 UTC months. It counts actual AI job states, completed tools, and committed customer credits. Failed work is not counted as completed value. Unconnected publishing, rankings, leads, and revenue are explicitly returned as unavailable rather than inferred. Results are capped at 500 jobs with a `truncated` flag.

## POST `/internal/free-membership`

Server-to-server only on `directory.branddad.social`. BrandDad Social sends this event only after explicit `bds_directory_membership_consent` was stored on an eligible Business Essentials checkout and the WPS subscription activated. The request uses the fixed Directory HTTPS endpoint, a five-minute timestamp, body HMAC, and stable idempotency key under `BDS_ACCOUNT_LINK_SECRET`. It reuses an account by email or creates a private `subscriber`; it never creates or publishes a business listing. Cursor must show the consent checkbox and submit value `1`, `yes`, `true`, or `on`.

## Consent follow-up worker

This is a scheduled backend adapter, not a public send endpoint. It reads only Cursor's existing `bds_follow_consent_v1` option and `bds_follow_message()` copy. Delivery requires `BDS_FOLLOWUP_ENABLED === true` plus a verified `BDS_FOLLOWUP_FROM_EMAIL`; otherwise it sends nothing. It rechecks fresh consent immediately before a bounded send, stops converted/unsubscribed contacts, sends at most one message, retries definite local mail failures up to three times, and uses an opaque hashed unsubscribe token. A crash during an uncertain send enters `delivery_review` rather than sending a duplicate automatically.

## Account handoff

Source POST `wp-admin/admin-post.php` action `bds_account_link_start` with WordPress nonce, verified source-email ownership, exact destination host, and allowlisted return path. Source returns an auto-submitted POST to destination action `bds_account_link_consume`. Token is HMAC-SHA256, purpose/issuer/audience/email-hash bound, five-minute, and single-use. It is never placed in a URL. The source registration system must explicitly connect its verified-email state through the fail-closed `bds_account_link_email_verified` filter.

## Existing Cursor contracts retained

- `bds_ai_commercial_checkout_url( $slug )`: empty means unavailable.
- `bds_dir_free_ai_status( $listing_id, $user_id )`: authoritative free-AI ledger or `WP_Error`.
- `bds_mt_user_tier()`: existing Directory membership view model.
- Featured grants remain inactive until a Woo order `is_paid()` and the SKU is allowlisted.

## Directory Featured billing adapter

The adapter recognizes only `bds-feat-local`, `bds-feat-regional`, `bds-feat-national`, and `bds-feat-global`. The add-to-cart request must carry `listing={published_claimed_listing_id}` or `_bds_feat_listing_id`. The authenticated customer must be the Directorist author and approved claimant; the adapter persists both listing and owner on the order item and rechecks them at every activation/renewal.

Cursor must provide `bds_featured_listing_geo` with `{ country, lat, lng }` from authoritative listing data. Country is required for non-global lanes; valid coordinates are also required for Local/Regional. Missing geography fails closed. Activation uses an active WPS subscription, a paid matching parent/renewal order, and the authoritative next-payment date. Renewal extends the same grant, failure pauses it, cancellation preserves paid-through display until its end, expiry ends it, and refund revokes it without deleting history.
