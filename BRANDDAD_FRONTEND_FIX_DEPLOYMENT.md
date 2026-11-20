# BrandDad frontend launch-fix deployment packet

Prepared: 2026-11-20

Status: reviewed production subset deployed on 2026-11-20; fresh PHP output verified. Clean Directory specialty URLs remain behind stale provider/origin cache pending a supported purge.

## Purpose

This packet groups the two remaining public presentation repairs:

1. Use passing BrandDad blue `#0668D8` for white-text primary actions.
2. Keep the current Directory Experience hero as the single visible homepage H1 and remove the retired WhatsApp/gift-homepage markup from the public response after cache refresh.

The source worktrees already contained unrelated uncommitted work. Never deploy by copying a directory or branch wholesale. Select only the reviewed files below, and compare the installed file before replacing it.

## Reviewed source inventory

| File | Version | Intended host | Local SHA-256 | Purpose |
|---|---:|---|---|---|
| `deploy/bds-ai-launch-pages-mu.php` | 0.5.24 | Social only | `2590b57bdedd86dca3ffe94a5432d9b1dcd3ba419cb73e55b6e881ca1366aff1` | Passing primary CTA blue on launch pages. This module has no host guard, so do not install it on Directory. |
| `deploy/cursor/bds-revenue-sections-mu.php` | 0.2.21 | Social only | `07eea1a9c7593163709d5335bb53f162c18a54f19b7e29e5e80ddeb221ca3660` | Passing primary CTA blue in revenue sections. |
| `deploy/cursor/bds-customer-surfaces-mu.php` | 0.2.5 | Social and Directory | `62d5b442559f05ac096ebd44901f342629e94652cdc7aa621149d89cb32471c2` | Passing account/studio action blue. |
| `deploy/cursor/bds-directory-featured-ads-mu.php` | 0.2.13 | Directory only | `4fa8f31589d8b2ea6141285f662e903f7c98ca91b2ee7462401de1860c06e90c` | Passing Featured action blue. |
| `deploy/cursor/bds-codex-bridge-mu.php` | 0.1.1 | Social and Directory | `23f89adec0495f69c89edfd0cc5b3a5d26f52a0dbab8d092a5196823a05f9464` | Passing bridge form action blue. This is an untracked local source; deploy only if installed-source comparison proves it is the same reviewed module apart from this version/color change. |
| `deploy/cursor/bds-sales-help-mu.php` | 0.1.3 | Social codebase; deployed to Directory to disable the duplicate helper there | `d397f102e24ec4d592b4c77d48c6dd6a43e2737da247e0b0b36f2539f9d28b24` | Render only on `branddad.social` and `www.branddad.social`; Directory keeps its contextual Concierge. |
| `deploy/bds-directory-qa-repair-mu.php` | 1.0.21 | Directory only | `dca6a417e52d430f24b44139de2926375a3127f5156b050fb415fffd34f1030c` | Prefer current `#bdd-title`, demote the retired hero, and strip the demo refund modal only from Events, Jobs, and Classifieds public browse output. |

## Directory source prerequisites to verify, not blindly overwrite

| File | Expected version | Local SHA-256 | Required behavior |
|---|---:|---|---|
| `bds-gift-slider-slots-mu.php` | 1.1.0 | `6f35c41c1e4dbe76ca2198dc17d780db1b9bd5ee25466b4417233da54da2d00b` | `bds_gss_render_rail()` refuses every route except `/gift-card-marketplace/`. |
| `bds-directory-front-compose-mu.php` | 1.5.35 | `83d75c9460ff15d719957998e3795bbe5fd55633a80daab42f7d5249f9264ce4` | Current source does not render the old gift inventory block. |

These prerequisite files were not changed in this batch. Read back the installed versions/hashes first. If production differs, stop and reconcile rather than overwriting a newer file.

## Preflight gate

- Verify the current hosting backup is still available and record installed copies of every target file for rollback.
- Read back each installed file/version/hash. A mismatch outside the reviewed version/color/H1 changes is a stop condition.
- Keep staged and quote-only checkout products closed.
- Do not run WP-Cron, Action Scheduler, payment lifecycle actions, FormWise executions, outbound messages, or live purchases as part of this frontend deployment.
- Do not deploy the entire dirty Cursor or recovery worktree.

## Deployment order after confirmation

1. Social contrast files, one at a time, with PHP syntax verification after each replacement.
2. Directory contrast files, one at a time, with PHP syntax verification after each replacement.
3. Directory QA repair `1.0.21` and Sales Help `0.1.3`.
4. Verify the installed gift-slider and front-compose prerequisites; replace them only if the installed source is an older reviewed version and that additional replacement is explicitly approved.
5. Purge only the generated Directory page cache through the existing bounded cache-control path. Do not recursively delete an unresolved filesystem target.

## Acceptance checks

- Social Home and Services return HTTP 200 and expose launch version `0.5.24`.
- White-text primary CTA backgrounds compute to `rgb(6,104,216)` / `#0668D8` and meet at least 4.5:1 contrast.
- Default, hover, focus, active, disabled, and forced-colors behavior remains readable.
- Directory Home returns HTTP 200 with exactly one visible H1: `Find What You Need Near You—Fast.`
- The retired `Find a business. See what it offers. Contact it.` H1 is absent as an H1.
- `Brand gift cards under face value` and the old gift-homepage search block are absent from public homepage DOM; the gift marketplace route remains available.
- Exactly one Directory Concierge launcher is present and its dialog remains contained.
- Social Home, Services, Directory Home, categories, listings, and the Playa del Carmen result path have no horizontal overflow at 320, 390, and 430 CSS pixels.
- Critical CTA destinations still return HTTP 200 after expected redirects.
- Events, Jobs, and Classifieds clean URLs contain one Directory Concierge, no `#bds-sales-help`, and no `#dcl-claim-modal` after the supported cache refresh.

## Rollback

- Restore the exact saved pre-deployment copy of only the affected file.
- Re-run PHP syntax verification.
- Purge only the affected generated page cache through the bounded cache-control path.
- Re-run the corresponding acceptance checks.
- Do not roll back unrelated Cursor, customer, payment, listing, subscription, or provider data.

## Local evidence

- All 15 PHP tests in the Cursor revenue worktree pass after repairing the standalone `sales-help-test.php` WordPress `add_filter()` stub.
- The focused contrast, launch funnel, language, heading, revenue, Featured, customer-hub, bridge, and Directory H1/gift contracts pass.
- PHP syntax and targeted diff checks pass.
- File-specific Rafter secret scan found no secrets. Manual OWASP review found no new request, auth, SQL, network, credential, payment, storage, or deletion path. Remote fast Rafter remains blocked at the 16/16 quota; paid Plus was not used.

## Production result

- Uploaded Social launch pages `0.5.24`, Social revenue sections `0.2.21`, Directory Featured `0.2.13`, Directory QA repair `1.0.21`, and Sales Help `0.1.3`.
- Fresh `nocache=1` Events, Jobs, and Classifieds output passes the specialty-page acceptance: one H1, Directory Concierge present, duplicate Sales Help absent, demo refund modal absent.
- Ordinary specialty URLs still serve older generated HTML. Three locally derived objects were uploaded after content validation, but the live clean routes did not select them; no additional keys were guessed and no broad cache directory was deleted.
- StartHost's Edge Caching page does not render its controls because its own JavaScript throws `SyntaxError: Unexpected identifier 'theme'`. Directory Speed Auto-Heal requires a WordPress-admin login in the current browser session. A supported provider purge/authenticated bounded refresh remains the final presentation step for these clean URLs.
