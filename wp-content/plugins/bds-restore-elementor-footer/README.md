# BDS Restore Elementor Footer

Restores the **original Elementor footer** on [branddad.social](https://branddad.social).

## What went wrong

Custom `bds-chrome` styles hide the Elementor footer:

```css
body.bds-chrome .elementor-location-footer{display:none!important}
```

…and output a replacement `<footer class="bds-site-footer">` with different links/copy. Directory / HostTech blocks can also appear above the footer.

## What this plugin does

1. Shows `footer.elementor-location-footer` again (Quick About Us “Convert with BrandDad!”, original Quick Links / Service / Contact columns, Spotify & Apple icons).
2. Hides and removes `footer.bds-site-footer`.
3. Removes misplaced Directory browse / local-pack / home-blog nodes if they appear on Social.
4. Keeps the Directory member savings strip above the Elementor footer.

## Install

1. Upload to `wp-content/plugins/` on **branddad.social** (not Directory).
2. Activate **BDS Restore Elementor Footer**.
3. Clear BerqWP / CDN / page cache.

## Optional cleanup

In WP admin, disable the Code Snippet / mu-plugin that prints `bds-site-footer` and the rule hiding `.elementor-location-footer` so you do not rely on this override forever.
