# BrandDad Cursor progress

Verified 2026-11-18. This is not a production launch.

## Repository

- One GitHub repository: `branddadsocial/t1000`.
- Social and Directory are separate WordPress sites (`branddad.social`, `directory.branddad.social`) whose plugins live in that repo.
- Cursor branch: `cursor/branddad-revenue-20261118`.
- Worktree: `C:\Users\iamtd\Documents\Codex\branddad-cursor-revenue`.
- Base commit: `5796559` on `recovery-social-20260910`.
- The dirty Codex checkout at `Documents\Codex\branddad-seoestore` was not edited.

## Live sites checked

Checked again on 2026-11-19 after the file-manager upload. Responses below are the uncached Cloudflare `DYNAMIC` copies.

- `https://branddad.social/` returns 200 and contains `branddad-complete-packages`, `bds-hub-css`, and `bds-music-brief`. It does not contain `bds-revenue-sections` or `bds-account-hub`. The launch markup already includes the music block, so the revenue plugin adds the music brief on the homepage.
- `https://branddad.social/services/` returns 200 and contains `bds-revenue-sections`, `bds-rev-audit`, and `bds-hub-css`.
- `https://directory.branddad.social/` returns 200. The cache-busted copy contains `bds-featured-lanes`, “Featured Worldwide”, and `bds-hub-css`. An earlier full-size cached copy of the same URL did not.
- `https://branddad.social/account-hub/` (page 53773), `https://branddad.social/studio-boards/` (page 53775), and `https://branddad.social/affiliate-program/` (page 53779) return 200. Each renders the deployed shortcode (`[bds_account_hub]`, `[bds_studio_boards]`, `[bds_affiliate_landing]`).
- `https://directory.branddad.social/account-hub/` (page 17596), `https://directory.branddad.social/studio-boards/` (page 17598), and `https://directory.branddad.social/affiliate-program/` return 200 and render the same shortcodes. Rechecked 2026-11-19.
- `https://branddad.social/affiliate/` still returns 301, `x-redirect-by: WordPress`, to the Partnero portal. The draft that the registrar had created at slug `affiliate` was trashed and was not published. Directory `/affiliate/` previously returned 404; after the new page it returns 301 to `/affiliate-program/`.
- Affiliate shortcode CTAs on both `/affiliate-program/` pages now emit the Partnero portal root (`https://affiliates.branddad.social/`) plus the existing house `?ref=`. They do not glue `/program` or `/signup` onto that query. Partnero itself then lands signed-out visitors on `/program`. Rechecked 2026-11-19 after the Directory featured-MU parse-error repair.

## Uploaded 2026-11-19

SSH to `branddad.social@ssh.gb.stackcp.com` closed during key exchange. The signed-in file manager then accepted the files into `wp-content/mu-plugins`. No WooCommerce or Stripe product was created. The $6.99 activation and existing Pro prices were not changed. No paid Featured grant was activated.

Social, `public_html/branddad.social/wp-content/mu-plugins/`, upload queue 5/5 Completed:

- `deploy/bds-ai-commercial-catalog-mu.php` — catalog version 0.3.0. Complete packages and music stay `quote_only`.
- `deploy/bds-ai-launch-pages-mu.php` — launch page version 0.5.0. Public CSS is printing, so the existing launch flag is already on.
- `deploy/cursor/bds-revenue-sections-mu.php`
- `deploy/cursor/bds-customer-surfaces-mu.php`
- `deploy/cursor/bds-customer-hub-lib.php`

Directory, `public_html/directory.branddad.social/wp-content/mu-plugins/`:

- `deploy/cursor/bds-directory-featured-ads-lib.php` — SHA `1fb61cc6…` matches local.
- `deploy/cursor/bds-directory-featured-ads-mu.php` — SHA `88dbf2f3…` matches local after a 2026-11-19 parse-error patch (`(float)` cast, `empty()` paren, sponsored markup). Directory `/`, `/account-hub/`, `/studio-boards/`, and `/affiliate-program/` returned 200 after the patch.
- `deploy/cursor/bds-customer-surfaces-mu.php` — SHA `5ec0ebff…` matches local. Portal-root affiliate CTA.
- `deploy/cursor/bds-customer-hub-lib.php` — SHA `99d99224…` matches local.
- `deploy/cursor/bds-directory-free-ai-mu.php` — uploaded 2026-11-19. SHA `9c299ce8…` matches local. Ledger + guest/owner display only. Tool execution stays off.

Featured-ad files were not uploaded to Social (Directory-gated).

## Feature status

| Area | Status |
|---|---|
| Social homepage launch copy | Uploaded. `branddad-complete-packages` is on the homepage. Cursor 0.2.1 adds `#bds-rev-home-strip` with live Subscribe links plus Get featured / List a business. Launch 0.5.1 points the Featured section at `/featured-advertising/` instead of Contact. Layout chrome was not rewritten. |
| Services catalog | Live 0.2.0. `/services/` leads with **Plans you can buy now** and **Subscribe now** for published products, then Directory/Get featured, then inquiry-only cards. Staged/quote-only stay closed. |
| Complete AI packages | Uploaded. Entitlements stay proposed. Checkout stays closed for quote-only rows. |
| Directory registration and claims | `/add-listing/` still gates guests through `/select-your-plan/` with $6.99 Activation unchanged. The claim explainer post now ends with **Claim, list, or get featured** CTAs. Listing singles now get a **Claim or get featured** band that prefills `/featured-advertising/?listing={id}`. The listing claim form itself was not completed (Codex). |
| Featured advertising rules | Live 0.2.1 on Directory. `/featured-advertising/` is a 200 buy/inquiry page. GET `listing` prefills only a published `at_biz_dir` id. Empty homepage lanes say “No featured businesses are showing here yet” plus **Get featured**. Checkout stays closed until Woo SKUs `bds-feat-*` exist and `is_paid()`. |
| Membership $6.99 lifetime / Pro / Elite | Existing catalog preserved. Not changed. Select-your-plan still shows $6.99 Activation, Pro from $9.99/month, Elite $49/month, plus a **List / Get featured** band that does not change those prices. |
| Free Directory AI | Uploaded to Directory. Account hub shows guest copy (“Sign in with the account that claimed the business”). Codex still owns tool execution. |
| Music briefs | Uploaded. Homepage `#bds-music-brief` package select now lists the six catalog options (was echoing `Array`). Briefs save only. No payment or production. |
| Account hub, membership display, free-AI display, studio boards, affiliate door | Live on both sites. Account hubs now lead with **Do this next** (Social: Subscribe to AI Social Growth; Directory: list / plans / Get featured / claim). |
| Video studio | Deferred. No licensed provider is connected. |

## Verification

- `php -l` passed on the cursor PHP files, including `bds-directory-free-ai-mu.php` and updated `bds-revenue-sections-mu.php`.
- `php tests/featured-rotation-test.php`, `php tests/customer-hub-test.php`, and `php tests/revenue-sections-test.php` passed on 2026-11-19.
- Social `/contact-us/?offer=ai-business-monitoring` prints the allowlisted inquiry note. Unknown slugs print nothing.
- Rafter secret scan of `deploy/cursor` returned no findings. `rafter run` refused: Fast scan limit 16/16 this billing period.
- Featured lanes print from the Directory front-page footer. Empty until a paid grant exists.
- SSH still closes during key exchange. Later MU writes used the signed-in file manager `savefile` API.
- The hosting file manager footer still reports 6 infections. Nothing was renamed or deleted. Harden headers remain (`x-bd-eco-harden` 1.2.4 on Social, 1.2.2 on Directory, `x-bd-eco-malware-lock: 1`).
- Account, studio, and affiliate-program URLs return 200 on both sites. Social `/affiliate/` remains a 301 to the Partnero portal.

## Infection verdicts 2026-11-19

The file manager listed six hits after the MU upload. The August note in `deploy/malware/NETWORK_HARDEN_CERT.md` does not call the scanner a false positive. It quarantined real oneshots, including Imunify `php.wp.load.unlink.unlink` on the BoxCoin oneshot. The five HTML hits below are the false positives. Nothing in the quarantine folder was opened for edit, moved, or deleted.

1. `public_html/directory.branddad.social/wp-content/cache/bd-eco-html/4ce34e5a98a8fd857d3fd9d542e866b648c6f244c8f788371236a6732fe0f2ff.html` — confirmed false positive. Signature `Malware.Expert.php.wp.content.upload.wp-include.wa.me.window.function.if.typeof.document.eval.UNOFFICIAL`. The object had rotated when searched again. A sibling in the same cache folder is a cached Directory HTML page (`<!DOCTYPE html>`, title “Explore the BrandDad Directory for Top Brands”), not PHP.
2. `public_html/directory.branddad.social/wp-content/cache/bd-eco-html/a7d9280f0ae3bb6e98547035ff841ad902359a2bd3c53d21156df0a2f10….html` — confirmed false positive. Same unofficial signature and the same page-cache directory. The full 64-character tail was not reconfirmed because the file had rotated.
3. `public_html/directory.branddad.social/wp-content/cache/bd-eco-html/db813628f80e1edc3748995c602ee79096ac49d3698b78f26b5c3d2232713945.html` — confirmed false positive. Same signature, same cache directory.
4. `public_html/directory.branddad.social/wp-content/cache/bd-eco-html/5c9eb665fd17071edf05d6fea411f8cf138fec6dcaf3a44991dab715aec9875.html` — confirmed false positive. Same signature, same cache directory.
5. `public_html/directory.branddad.social/wp-content/cache/bd-eco-html/2c0aa3cbb96333718ea69bd13bc3df4348fd53499b0d6d4f3d6afd3459e04ed5.html` — confirmed false positive. Same signature, same cache directory.
6. Scanner label `public_html/directory.branddad.social/_bds-boxcoin-multisite-enable-oneshot.php.done` — real historical oneshot, already quarantined. Current path: `/malware_quarantine_oneshot_sweep_20260822T005229Z/public_html/directory.branddad.social/_bds-boxcoin-multisite-enable-oneshot.php.done`. Signature `Malware.Expert.php.wp.load.unlink.unlink.UNOFFICIAL`. Modified 2026-08-09. It loads WordPress, gates on a query key, and self-renames. It is outside the web root. It was closed without Save. The BoxCoin plugin and the gift-escrow / webhook-relay MU plugins were left in place.
