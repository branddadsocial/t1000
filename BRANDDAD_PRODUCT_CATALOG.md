# BrandDad product catalog backend status

Source of truth: `deploy/bds-ai-commercial-catalog-mu.php` plus verified Woo products and existing customer subscriptions.

| Product | Intended price | Backend state |
|---|---:|---|
| AI Business Essentials | $29.99/mo, 7-day trial | Woo product and FormWise 75-credit paid plan verified; runtime trial uses a separate 25-credit ledger and grants the paid tier only after verified renewal; staging/live E2E still required |
| AI Studio Starter | $14.99/mo, 50 credits | FormWise plan verified; launch candidate after lifecycle test |
| AI Studio Growth | $29.99/mo, 150 credits | FormWise plan verified; launch candidate after lifecycle test |
| AI Studio Pro | $49.99/mo, 400 credits | FormWise plan verified; launch candidate after lifecycle test |
| AI Local SEO | $19.99/mo, 60 credits | Plan and SEO action flow verified; recommendation workflow only, not automatic site changes |
| AI Lead Assistant | $19.99/mo, 80 credits | Plan verified; BrandDad acquisition pipeline plus customer-owned inquiry form/inbox/status and real counts implemented default-off; Cursor form/inbox, staging isolation, AI summaries, and verified notifications remain |
| AI Social Growth | $29.99/mo, 150 credits | Plan and draft flow verified; publishing adapter not yet production-ready |
| AI Review Assistant | $9.99/mo | Draft workflow available; standalone plan mapping remains staged |
| Airbnb Review Appeal Assistant — One Review | $9.99 one time | Published white-labeled FormWise workflow and one-paid-order/one-case backend implemented; public checkout remains staged until payment → result → no-reuse → refund acceptance passes |
| AI Business Monitoring | $9.99/mo | SSRF-resistant daily availability worker and actual monthly counters implemented default-off; standalone plan mapping, real-host acceptance, and verified failure alerts remain staged |
| AI Website Chatbot | $29.99/mo | Blocked by observed reply cost and customer knowledge automation |
| AI Sales Assistant Plus | $39.99/mo | Blocked by observed reply cost; BrandDad's own assistant has a separate capped proxy |
| Complete AI Starter/Growth/Pro/DFY | $79.99/$149.99/$249.99/from $399 | Quote-only until real fulfillment allowances/providers pass cost tests |
| Directory Free/Pro/Elite | $0 / variable Pro / $49 | New basic registration/listing/claim/management is free with no $6.99 activation dependency; preserve historical orders, existing entitlements, paid benefits, and variable Pro prices |
| Directory Featured | $9.99/$19.99/$39.99/$99.99 | Display rules live; Woo SKUs/payment event adapter not production-tested |
| BrandDad Music | $79/$129/$249/$449/$799/from $199 | Brief intake only; payment closed until licensing/cost/delivery confirmed |

Historical products/orders remain preserved even when retired from new-customer purchase flows.
