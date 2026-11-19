# BrandDad API contracts

Cursor did not add a public JSON API in this pass. Shared behavior Codex still owns:

## Commercial checkout

- Function: `bds_ai_commercial_checkout_url( $slug )`.
- Returns a permalink only when the catalog `launch_state` is `launch_candidate` or `live_legacy` and a published Woo product exists.
- Cursor treats an empty string as "not for sale".

## Featured placement record

Stored in option `bds_directory_featured_grants_v1`. Codex billing should not write this option until payment is confirmed.

```json
{
  "id": "g_…",
  "listing_id": 0,
  "kind": "paid|trial|partnership",
  "level": "local|regional|national|global",
  "status": "pending_payment|active|paused|revoked|expired",
  "starts": "ISO-8601",
  "ends": "ISO-8601 or empty",
  "country": "ISO-3166 alpha-2 or empty",
  "order_id": 0,
  "notes": "admin only"
}
```

- `paid` becomes `active` only when `wc_get_order( order_id )->is_paid()` is true.
- `trial` is 14 days, Local only, once per listing, with no payment method.
- Radius defaults: local 25 km, regional 100 km. A grant may set `radius_km`.
- Caps: local 8, regional 6, national 4, global 3. Overflow waits.

## Music brief

- POST `admin-post.php` action `bds_music_brief`.
- Result is a private `bds_music_brief` post. It is not an order and not a payment.

## Still required from Codex

- Entitlement checks for Complete AI Starter, Growth, and Pro.
- Metered free Directory AI execution.
- SEO, social publishing, WhatsApp, and video execution.
- Partnero commission calculation. Cursor did not change it.

## Saved businesses

- WordPress user meta `_bds_saved_listing_ids` on the current user only.
- POST `admin-post.php` action `bds_hub_save_listing` with the existing nonce.
- `save_action` is `save` or `remove`. The listing must be a published `at_biz_dir` post.
- Guests are refused. The list stops at 50 ids. No other user's list is read.

## Featured events

- POST `admin-post.php` action `bds_feat_event` on Directory only.
- Fields: `grant_id`, `type` (`impression` or `click`), `event_id`, and a token signed for that exact event.
- The server adds 1 to the matching counter. A `count` or `total` field is rejected. A repeated event id is rejected. An inactive grant is rejected.
- The response is 204, 403, 404, or 409. It does not return a total.
