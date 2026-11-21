# BDS Directory Connect

Fixes on **directory.branddad.social**:

1. **Category tabs** (Automotive, Beauty & Personal Care, …) — no longer dead `href="#"`. Each tab goes to `/search-result/?directory_type={slug}`.
2. **Search bar under those tabs** — typing a need and hitting Search opens **Ask BrandDad**. The extra “Find what’s near you — Ask BrandDad” hero chip is hidden so there is one search path.
3. **Popular in** — built from live `at_biz_dir-location` term counts (all cities with listings, not just Chicago / Playa). Counts refresh when listings are added/updated (transient cache, 1 hour, flushed on save).
4. **Local / digital chips** — counts come from live `at_biz_dir-category` terms. Links use `/search-result/?in_cat=…` (not broken `/single-category/` archives that show “0 Items Found”).

## Install

1. Upload `bds-directory-connect/` to `wp-content/plugins/` on the Directory site  
   (or zip the folder → Plugins → Add New → Upload).
2. Activate **BDS Directory Connect**.
3. Clear page cache / BerqWP / CDN for the homepage.
4. Optional: disable or remove the old hardcoded **bds-home-browse v1.1.1** snippet/mu-plugin so only this plugin renders the section (this plugin also removes the old `#bds-home-browse` node in the browser if both are present).

## Verify

- Home → tap **Automotive** → lands on search results for that directory type.
- Home → type “coffee near me” in the Directorist search → Ask BrandDad panel opens.
- Home → **Popular in** shows every city that has listings, with matching counts.
- Add a listing in a new city → within an hour (or after saving any listing) Popular in updates.

## API

`GET /wp-json/bds-connect/v1/browse` — JSON payload used by the home UI (`?fresh=1` bypasses cache).
