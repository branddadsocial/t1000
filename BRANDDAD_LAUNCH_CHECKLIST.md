# BrandDad launch checklist

Production readiness: controlled soft-launch surfaces are operational; unrestricted paid launch still requires the open acceptance gates below.

- [x] Locate the existing repository and Directory domain.
- [x] Leave the dirty Codex checkout untouched.
- [x] Confirm live Social homepage and services page load.
- [x] Confirm live Directory homepage loads.
- [x] Keep staged products off checkout.
- [x] Add featured rotation rules and a local PHP test.
- [x] Add customer hub, membership, free-AI, studio, and affiliate view models with a local PHP test.
- [x] Compare membership tiers from the existing entitlement rows without adding prices.
- [x] Save and remove a Directory listing for the signed-in user only, capped at 50.
- [x] Record one featured impression or click, and reject a client-supplied total.
- [x] Show chatbot and video boards that do not execute.
- [x] Refuse a duplicate canonical or a new city-page hreflang alternate.
- [x] Print Featured lanes from the Directory front page footer hook; the isolated hosted-staging billing adapter now passes all 28 lifecycle checks without changing production inventory.
- [x] Deploy the reviewed new MU files to the isolated staging WordPress rather than overwriting the September branch.
- [x] Reconcile every catalog-permitted public AI product URL against the published Woo catalog; all ten saleable offers returned the authoritative product, price, image, and purchase control, while all 21 staged/quote-only slugs remained unavailable.
- [x] Keep Complete AI entitlements quote-only and absent from self-service checkout until real provider, allowance, labor, and cost evidence exists.
- [ ] Confirm a real Woo order before any paid featured grant is activated.
- [ ] Confirm music license and cost before taking payment.
- [~] Retest homepage, Services, Directory claim, and music brief on mobile: measured 320px, 390px, and 430px browser/device viewports passed document-width and control-presence checks with no horizontal overflow; final physical iOS/Android touch testing remains.
- [x] Verify the critical mobile conversion destinations: trial, Services, website audit, Add Listing registration handoff, claim-intent finder, Directory plans, and Playa del Carmen location all resolve successfully; the music brief remains an unsubmitted private POST form.
- [x] Verify the wider rendered CTA surface: 65 visible CTA/link instances across Social Home, Services, and Directory Home reduced to 34 unique public paths; all resolved successfully, and all five referenced in-page anchors exist.
- [~] Read the two Rafter warnings on each site before the next deploy: current records confirm zero critical and two warning findings per site, but the Rafter Sites API/CLI returns counts only and does not expose the warning bodies. Do not close this gate until the detailed findings are available.
- [x] Frontend cleanup: the production Directory homepage compatibility release keeps the current hero as the single visible H1, suppresses the retired duplicate hero, leaves gift inventory off the homepage, and passed 390px/1440px acceptance without horizontal overflow or duplicate concierge surfaces.
- [x] Frontend contrast cleanup: the reviewed `#0668D8` primary-action treatment is deployed on the launch surfaces; desktop and 320px/390px mobile acceptance passed without provider branding, overflow, or unreadable primary actions.
- [x] Repair the blocked Social Spotify stylesheet: production permission changed from `600` to `644`; the CSS now returns HTTP 200 and the Services page remains HTTP 200.
- [x] Remove the checker-only duplicate mobile menu and verify the common five-door navigation at 390px. Frontend polish `1.0.7` is live with one menu, one H1, no broken images, and no overflow.
- [x] Keep the owner Billing Inspect white-labeled: provider-neutral AI labels are live and the signed-in surface contains no visible `FormWise` name.
- [x] Deploy the reviewed shared backend package: all 15 expected `branddad/v1` routes are registered on production; the former `rest_no_route` note was stale.
- [x] Recheck public HTTPS/indexing plumbing: both HTTP hosts 301 to their HTTPS equivalents, TLS verification succeeds, audited production pages have matching canonicals and no `noindex`, and both robots/sitemap endpoints return HTTP 200.

## Current release blockers

- [!] Directory experienced an intermittent production database-connection outage on 2026-11-20. It recovered to HTTP 200, but host telemetry and a longer stability window remain required.
- [~] Social has a working temporary single-runner fallback: repeated automatic GitHub `schedule` runs return HTTP 200 and the Woo queue has no sampled overdue pending work. StartHost still has no saved native task; do not enable a second runner before disabling the fallback.
- [~] Live Stripe was reconnected to the existing BrandDadSocial account with payments, payouts, and webhooks enabled and test mode off. A safe signed-webhook purchase/provisioning/renewal/cancellation/refund lifecycle remains required before unrestricted paid launch.
- [!] Required remote Rafter fast review remains quota-blocked at 16/16. Local secret scans and regression gates are clean; paid Plus was not used without approval.
- [!] Real provider fulfillment acceptance remains incomplete for the products that depend on external AI or publishing services. Do not describe an untested provider action as automatic fulfillment.
