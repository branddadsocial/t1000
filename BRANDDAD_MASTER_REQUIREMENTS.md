# BrandDad master requirements

Authoritative directive: 2026-11-19 BrandDad Social + BrandDad Directory final backend implementation.

## Product boundaries

- BrandDad Social: sales, subscriptions, AI Studio, ongoing business services, specialty fulfillment.
- BrandDad Directory: free registration/listing, discovery, memberships, Featured advertising, limited free AI, events/jobs/classifieds.
- Cursor owns public pages and dashboard presentation. Codex owns shared backend, payment lifecycle, entitlements, FormWise execution, workers, integrations, tests, and operational security.
- Directorist, WooCommerce, Subscriptions for WooCommerce, WordPress accounts, Stripe, FormWise, and Partnero remain authoritative in their existing domains. Do not duplicate them.

## Required customer lifecycle

Discover -> understand -> register/link -> trial or pay -> provision -> onboard -> execute real work -> report -> renew/change/cancel.

## Release rules

- Existing customer agreements and historical orders are immutable unless the owner explicitly approves a migration.
- Free Directory registration and one basic listing must not require the obsolete $6.99 activation purchase.
- AI Business Essentials is $29.99/month with a seven-day trial as represented by the verified Woo product. Trial terms must match checkout; no silent charge or invented no-card claim.
- FormWise is an access/execution provider, not a second billing authority for Woo products.
- Every external action is fail-closed, allowlisted, metered, idempotent where applicable, and auditable.
- No product becomes publicly sellable until price, checkout, provisioning, execution, usage, renewal, cancellation, and rollback tests pass.
- No mock, draft, recommendation-only, or unconnected dashboard is reported as operational.

## Required persistent evidence

See `BRANDDAD_CODEX_PROGRESS.md`, `BRANDDAD_CODEX_BLOCKERS.md`, `BRANDDAD_CODEX_TEST_RESULTS.md`, `BRANDDAD_SHARED_API_CONTRACTS.md`, `BRANDDAD_CURSOR_HANDOFF.md`, `BRANDDAD_PRODUCT_CATALOG.md`, `BRANDDAD_ENTITLEMENTS.md`, `BRANDDAD_INTEGRATION_AUDIT.md`, and `BRANDDAD_PRODUCTION_CHECKLIST.md`.

Source: the 2026-11-18 Cursor assignment. Status is what was verified, not the target.

| Requirement | Status |
|---|---|
| Social homepage positioning | Live copy already matches the headline. Extra sections are local and undeployed. |
| Free AI business audit | Live CTA points at `/check-your-website/`. |
| Services catalog and proposed prices | Local section added. Sale remains gated. |
| Complete AI packages | Local comparison copy only. Entitlements unconfirmed. |
| Directory public site | Live. Registration and claim code already exist and were not retested. |
| Membership Free / Pro / Elite and $6.99 lifetime | Existing prices untouched. Local comparison reads the existing entitlement rows. |
| Featured ads, trials, partnerships, inventory | Rules, capacity, and one-step impression/click recording are tested locally. Paid stays unpaid until Woo confirms. |
| Free Directory AI interface | Display only. Shared execution is Codex. |
| Partnero | Door stays on the portal root. No commission rate was added. The live host still has the older file. |
| Music storefront | Saved briefs display a no-payment status. Not a store. |
| Studios, SEO, chatbot, WhatsApp, video, admin analytics | SEO, Social, LinkedIn, chatbot, and video boards say they do not execute. WhatsApp and analytics stay with existing plugins and Codex. |
| Multilingual SEO | Helper refuses a second canonical and a new city-page alternate. BrandDad Global already prints the live tags, so no second emitter was added. |
| Security and performance production pass | Not claimed. Secret scan of the new folder was clean. |
