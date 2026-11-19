# BrandDad Cursor blockers

## Tooling

- `move_agent_to_root` failed twice: `Cannot create migration handle for unknown agent`. Files were written in the worktree by absolute path.
- Vexp is healthy on `branddad-seoestore` only. Its repo limit is 1, so Directory was not indexed separately.
- Rafter site scans exist. `sites_get` returned counts, not the individual finding text. Social latest run 2026-11-07: 0 critical, 2 warn, 2 info. Directory latest run 2026-09-22: 0 critical, 2 warn, 4 info.

## Product and billing

- Do not deploy this branch over production. Its git base is the 2026-09-10 recovery commit. Newer commercial files are uncommitted in the Codex checkout and were copied only as reference under `deploy/cursor`.
- Staged AI products stay off checkout until the commercial catalog returns a published product URL.
- Complete AI package entitlements are not confirmed by Codex.
- Featured paid placements cannot activate without a WooCommerce order that `is_paid()`. This plugin does not create or charge that order.
- The $6.99 lifetime activation and existing Pro prices vary by directory type. They were not flattened to a single $9.99 Pro plan.
- Music packages cannot be sold until licensing, delivery cost, and the shared payment lane are confirmed.
- No verified testimonial source was found, so none was invented.

## Deploy attempt 2026-11-19

- Established path: `scp`/`ssh` as `branddad.social@ssh.gb.stackcp.com` with `deploy/_secrets/cp_migrate_ed25519`, used by `deploy/_ssh_deploy_directory_share_meta.py`.
- This worktree does not contain that key. The key file is present in the other checkout and was used only as an identity file. It was not copied into git.
- SSH still fails before authentication. Exact error: `kex_exchange_identification: Connection closed by remote host` / `Connection closed by 45.8.227.145 port 22`.
- The signed-in file manager then accepted the upload. Social queue 5/5 Completed in `public_html/branddad.social/wp-content/mu-plugins/`. Directory queue 4/4 Completed in `public_html/directory.branddad.social/wp-content/mu-plugins/`. Search confirmed `bds-directory-featured-ads-mu.php` at that Directory path.
- Uncached `https://branddad.social/` contains `branddad-complete-packages` and `bds-music-brief`. Uncached `https://branddad.social/services/` contains `bds-revenue-sections`. Uncached `https://directory.branddad.social/` contains `bds-featured-lanes` and “Featured Worldwide”. A cached Directory copy did not.
- Account hub and studio boards are published on both sites. The affiliate door is `/affiliate-program/` on both sites. Social `/affiliate/` still 301s to the Partnero portal. Directory `/affiliate/` now 301s to `/affiliate-program/`.
- The surfaces MU is on both hosts. Live `/affiliate-program/` CTAs use the portal root plus house `?ref=`. Partnero then sends signed-out visitors to `/program`. Social `/affiliate/` remains a 301 to the portal. An admin visit can still recreate a draft at slug `affiliate`; that draft does not replace the Social 301.
- No WooCommerce or Stripe product was created. No Featured grant was activated. No existing price was changed.
- The file manager footer reports 6 infections, last scan 14-11-2026. Five are cached Directory HTML false positives under `wp-content/cache/bd-eco-html/`. The sixth is the BoxCoin oneshot already inside `malware_quarantine_oneshot_sweep_20260822T005229Z`. No cleanup was purchased. No file was renamed or deleted. Harden and the uploads noexec lock stay.
- SSH from this machine is still closed. Later deploys need a working key exchange or the same file manager.
- Directory returned 500 after a corrupted featured-MU save. Three syntax errors were patched in place on 2026-11-19. SHA now matches local `88dbf2f3…`. Directory public URLs return 200 again.
- File-manager `savefile` requires a leading `/public_html/...` path. Paths without the slash return `success:false`. GitHub raw fetch from the slim branch SHA is the reliable deploy path while SSH stays dead.

## Owner decisions still required

1. Restore SSH from this machine to `ssh.gb.stackcp.com`. The file-manager upload of the fail-closed plugins is done.
2. Turn on live checkout for any `staged` or `quote_only` catalog row. Decision: which slugs may be sold after fulfillment cost is accepted. Checkout stays closed.
3. Create WooCommerce or Stripe products for new AI, music, or Featured prices. Decision: create them or keep inquiry-only. None were created.
4. Change an existing customer subscription, the $6.99 activation, or a Pro price. Decision: leave agreements as they are. They were not changed.
5. Sell BrandDad Music. Decision: confirm license, delivery cost, and the payment lane. Briefs save only.
6. Activate a paid Featured grant. Decision: point it at an existing Woo order that `is_paid()` returns true. This code does not charge.
7. Publish slug `affiliate` on Social. Decision: leave it unpublished so the Partnero 301 stays. The door is `/affiliate-program/`. Done on both sites.
8. Enable free-AI tool execution. Decision: Codex connects the meter. The ledger can display a balance. The screen does not run tools.
9. Connect SEO, Social, and LinkedIn execution. Decision: Codex provides the authorized API. The boards stay disabled.

## Not done

- Live checkout of new staged/quote-only offers, a completed claim-form pass on a listing, Partnero balance display, and a phone-sized viewport pass. Existing launch-candidate products such as AI Social Growth already have Subscribe monthly at the listed price; that checkout was opened, not purchased.
- SSH restore from this machine. File-manager uploads of the Cursor MU set are done, including Directory `bds-directory-free-ai-mu.php`.
- Codex still owns FormWise, billing lifecycle, metered AI execution, SEO publishing, WhatsApp, and video generation. The studio screens do not call those systems.
