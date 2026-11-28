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
| AI Website Improvement Plan | $19.99 one time | Exact Woo product `branddad-smartsites-website-optimizer` (the previous `/product/website-speed-optimization/` route redirects to it); one paid, unrefunded line grants one run of the published white-labeled website-plan flow. Customer workspace and optional implementation request are implemented. It does not silently edit a site; a secure connection, exact approval, verification, and rollback are required before supported changes. |
| AI Business Monitoring | $9.99/mo | SSRF-resistant daily availability worker and actual monthly counters implemented default-off; standalone plan mapping, real-host acceptance, and verified failure alerts remain staged |
| AI Website Chatbot | $29.99/mo | Blocked by observed reply cost and customer knowledge automation |
| AI Sales Assistant Plus | $39.99/mo | Blocked by observed reply cost; BrandDad's own assistant has a separate capped proxy |
| Complete AI Starter/Growth/Pro | $79.99/$149.99/$249.99 | Quote-only until real fulfillment allowances/providers pass cost tests |
| Done-for-You Complete Management | $399/mo | Public qualification offer with 20 approved posts spaced through the month; no paid promotion or media spend is included or required; quote/contact only until fulfillment and billing pass |
| Complete Management + Promotion Starter/Growth/Pro | $449/$499/$599 total monthly | Adds management for 4/8/12 customer-approved official-platform or documented creator promotions, evenly spaced through the month. Advertising spend and creator fees are separate; execution remains fail-closed until authorization, lifecycle, and reporting acceptance pass |
| Directory Free/Pro/Elite | $0 / variable Pro / $49 | New basic registration/listing/claim/management is free with no $6.99 activation dependency; preserve historical orders, existing entitlements, paid benefits, and variable Pro prices |
| Directory Featured | $9.99/$19.99/$39.99/$99.99 | Display rules live; Woo SKUs/payment event adapter not production-tested |
| BrandDad Music | $79/$129/$249/$449/$799/from $199 | Brief intake only; payment closed until licensing/cost/delivery confirmed |

Historical products/orders remain preserved even when retired from new-customer purchase flows.

Website-check recommendations are AI-first where the work is genuinely analysis, planning, or writing: performance signals lead to the $19.99 AI Website Improvement Plan; SEO and local-search signals lead to the $19.99/month AI Local SEO Starter; and profile-copy gaps lead to the $14.99/month AI Studio Starter. The former $179 Website SEO Audit is retained for historical records but is no longer recommended by the checker. Account edits, submissions, outreach, repairs, and technical changes remain hands-on or approval-gated because a language model alone cannot safely perform them.
## One-time assisted submission services — approved scope (2026-11-27)

| Product | Price | Included action | Important boundary |
|---|---:|---|---|
| Airbnb Review Appeal — One Assisted Submission | $9.99 one time | BrandDad prepares one policy-based request and assists with submission | Eligible customer stays signed in and performs the final submit action; Airbnb decides |
| IMDb Submission Service — One Contribution | $29.99 one time | BrandDad submits one eligible contribution after customer approval | Verifiable public sources required; IMDb decides |
| Billboard Vendor Submission — One Inquiry | $49.99 one time | BrandDad submits one approved availability/pricing inquiry | Inventory, production, and media-buy fees are separate |
| Press Release Submission — One Submission Round | $39.99 one time | BrandDad submits one approved no-fee destination submission or small targeted media-pitch round | Paid wire and placement fees are separate; coverage is not guaranteed |

These are submission services, not approval or placement guarantees. No product includes customer password collection or automatic third-party spending.
