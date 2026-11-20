# BrandDad integration audit

## Existing Partnero bridge preserved

- Reused system: `bds-partnero-ref-bridge-mu.php` version 1.2.1.
- Matching reviewed source hashes were found in the prior SEO/store and Rafter worktrees: `9C1BA3C8E3D42F432ADDD9B56398733E7AE10FB7C0145DA577740B46D9D23CE9`.
- Existing deployment evidence records the bridge on Directory, Social, BrandDad.co, and HostTech, with live HTML markers/ref propagation verified at the time of that deployment.
- This bridge preserves referral attribution only. It does not prove Partnero commission creation, recurring conversion events, refund adjustments, cancellation adjustments, payout terms, or current API authentication. Those lifecycle items remain blocked pending verified Partnero contract/API access and safe tests.
- Decision: preserve the existing bridge; do not create a duplicate referral-cookie system.

## Provider cost accounting

- Added a private, append-only verified-cost ledger and admin-only contribution aggregate.
- No provider cost is fabricated from customer credits. Adapters must supply a provider/processor-reported, invoice-reconciled, or contract-unit-reconciled amount.
- Until adapters are connected and invoices reconciled, the API reports incomplete coverage rather than claiming profit.

Updated: 2026-11-19

## FormWise published-contract verification — 2026-11-19

- Read-only workspace inspection reconfirmed the BrandDad Social organization, owner role, published AI Studio App, published execution Flows, restricted portals, and seven commercial tier plans.
- The six FormWise assets used by `bds-ai-execution-mu.php` match the published UUIDs: Copy and Reviews, SEO Action Planner, Website Improvement Planner, Directory Listing Studio, LinkedIn Profile Analyzer, and BrandDad AI Studio.
- Every WordPress allowlisted Flow question key, required flag, and enum option matches the current published FormWise schema. The app request remains a single validated `message`.
- The WordPress seven-day trial remains intentionally separate from FormWise plan billing: FormWise's Business Essentials plan has no provider-side trial, while WordPress enforces the bounded 25-credit trial ledger and expiration.
- Added `tests/formwise-published-contract.mjs` to fail if a referenced UUID, question key, enum value, endpoint shape, payload shape, or server-side bearer-auth contract drifts.
- No FormWise Agent, Flow, App, plan, group, portal, or end-user was changed. No credit-consuming execution was run during this read-only verification.

| System | Verified state | Status |
|---|---|---|
| GitHub | One repo `branddadsocial/t1000`; Cursor and Codex branches/worktrees identified | Operational, dirty worktrees preserved |
| Hosting | Separate WordPress roots on StackCP/StartHost; prior File Manager deploy; SSH currently unreliable | Deployment blocker |
| WordPress | Social and Directory separate installs; existing cookie identity on each | Operational, cross-site link staged |
| WooCommerce | Live products/orders preserved | Operational |
| Subscriptions for WooCommerce | Version previously verified; lifecycle hooks and metadata audited | Bridge implemented; E2E acceptance required |
| Stripe | Live checkout observed previously; card data remains with gateway | No account changes made; safe E2E required |
| WP Swings trial | Official installed-plugin source confirms `wps_schedule_start`, `wps_susbcription_trial_end`, `wps_next_payment_date`, and seven/day subscription metadata | Local backend enforces exact terms, separate trial credits, one customer history, and renewal-only paid provisioning; staging transaction required |
| Social-to-Directory free membership | Existing account-link shared secret is the approved trust root | New protocol-specific HMAC bridge is implemented default-fail-closed; checkout consent UI and cross-host staging replay test remain |
| FormWise | BrandDad Social org, owner access, API scopes, published app/agents/flows, plans, restricted portal verified | Operational; separate execution key required on server |
| Directorist | Existing listing ownership/publication source | Operational; Cursor owns listing/search UX |
| Directory Featured billing | Cursor owns grant inventory/rotation; four intended SKUs are absent | Codex lifecycle adapter implemented fail-closed; Cursor selection/geography + SKUs + staging payment remain |
| Lifecycle analytics | Private WordPress event ledger with admin-only aggregate | Implemented locally; duplicate/refund/capability staging tests remain |
| BrandDad acquisition leads | Private deduplicated lead records, verified lifecycle hooks, separate consent, retention | Implemented default-off; Cursor form/privacy and WordPress staging acceptance remain |
| Website monitoring | Paid-entitlement worker, public-DNS/SSRF controls, safe HTTP, queues, actual counters | Implemented default-off; real-host DNS/queue/fetch and notification acceptance remain |
| Customer inquiries | Paid owner form reference, private inbox/status, tenant isolation, actual counts | Implemented default-off; Cursor hosted form/inbox and WordPress staging acceptance remain |
| Partnero | Existing affiliate portal retained | Event adapter/balance API not verified in this pass |
| Make | Prior documentation reports free-plan operations exhausted until reset | Blocked for new automation runs |
| AutoTranslator | Present historically; latest Directory direction removed public translator clutter | Needs dedicated production verification |
| Warpindex | Existing integration referenced | Authentication/current operation not reverified |
| Vexp | Previously healthy on `branddad-seoestore`, repository limit reached | Development aid only |
| Rafter | Existing scans; secure-design gate applied to this branch | Code review/scan required before release |
| MyMarky/social publishers | No verified production authorization in this pass | Not operationally claimed |
| WhatsApp | Public 312 number/links exist; official Business API automation not verified | Manual contact only |
| Video providers | Staged planning work only | Not production-ready |
| Music providers/distribution | Commission workflow concept only | Not production-ready |

No installed plugin or subscription was treated as proof of API readiness.
