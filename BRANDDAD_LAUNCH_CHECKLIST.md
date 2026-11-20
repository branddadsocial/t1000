# BrandDad launch checklist

Production readiness: not ready.

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
- [ ] Retest homepage, services, Directory claim, and music brief on a phone.
- [ ] Read the two Rafter warnings on each site before the next deploy.

## Current release blockers

- [!] Directory experienced an intermittent production database-connection outage on 2026-11-20. It recovered to HTTP 200, but host telemetry and a longer stability window remain required.
- [!] Social has no working scheduled-task runner; StartHost still rejects the exact reviewed task with `Cron failed to add`.
- [!] Live Stripe is connected but reports an `Incomplete` key-origin state; reconnect and safe lifecycle acceptance remain required.
- [!] The reviewed production backend package is not deployed; live `branddad/v1` routes remain absent by design.
