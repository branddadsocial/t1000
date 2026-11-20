# BrandDad shared API contracts

Base namespace: `/wp-json/branddad/v1`
Authentication: existing WordPress cookie session. Authenticated write calls require the normal WordPress REST nonce (`X-WP-Nonce`).

## GET `/ai/tools`

Purpose: show the signed-in customer only the server-approved tool schemas and customer-credit cost.

Response: `{ "tools": [{ "key", "label", "credit_cost", "fields", "free" }] }`.

Errors: 401 unauthenticated. No provider ids or credentials are returned.

## POST `/ai/jobs`

Purpose: reserve allowance and enqueue one FormWise execution.

Headers: `Idempotency-Key` of 16–64 safe characters.

Body: `{ "tool": "social_draft", "inputs": { ... }, "access_mode": "paid|directory_free", "listing_id": 123 }`.

Rules: `listing_id` is used only for `directory_free`; ownership is rechecked against Directorist. Paid plan identity and allowance cycle are read from active WPS subscription metadata. Unknown fields are discarded. The browser cannot choose a provider id, credit cost, plan id, owner, allowance cycle, or origin.

Response: HTTP 202 with `{ id, tool, status, credit_cost, result, error_code, created_at, updated_at }` and `Location` header. An idempotent replay returns the existing job.

Errors: 403 no entitlement, 404 unknown tool, 409 in progress, 413 request too large, 422 invalid input/idempotency key, 429 allowance exhausted, 503 provider disabled or job scheduling failed. A scheduling failure releases the reservation.

## GET `/ai/jobs/{id}`

Purpose: poll one job. A customer can read only a job whose `post_author` matches the authenticated WordPress user. Another customer's id returns 404.

## GET `/sales/catalog`

Public deterministic offer data from `bds_ai_commercial_catalog()` and `bds_ai_commercial_checkout_url()`. Empty checkout URL means not for sale.

## POST `/sales/chat`

Public, rate-limited BrandDad Sales Assistant proxy. Body: `{ "message": "...", "session": "optional signed token" }`. Browser origins are restricted to owned BrandDad hosts, request bodies are capped, and continuation tokens are bound to the requesting client.

Response: `{ "reply", "offers", "session", "expires" }`. Model-supplied URLs are removed. Every clickable offer URL comes from the authoritative server catalog. Execution defaults off and has per-IP plus global daily cost limits.

## Account handoff

Source POST `wp-admin/admin-post.php` action `bds_account_link_start` with WordPress nonce, verified source-email ownership, exact destination host, and allowlisted return path. Source returns an auto-submitted POST to destination action `bds_account_link_consume`. Token is HMAC-SHA256, purpose/issuer/audience/email-hash bound, five-minute, and single-use. It is never placed in a URL. The source registration system must explicitly connect its verified-email state through the fail-closed `bds_account_link_email_verified` filter.

## Existing Cursor contracts retained

- `bds_ai_commercial_checkout_url( $slug )`: empty means unavailable.
- `bds_dir_free_ai_status( $listing_id, $user_id )`: authoritative free-AI ledger or `WP_Error`.
- `bds_mt_user_tier()`: existing Directory membership view model.
- Featured grants remain inactive until a Woo order `is_paid()` and the SKU is allowlisted.
