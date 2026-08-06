# t1000 — BrandDad Depository

Source depository for BrandDad network sites and deployable WordPress fixes.

## Sites

| Site | URL |
|------|-----|
| BrandDad Social | https://branddad.social |
| BrandDad.co | https://branddad.co |
| HostTech | https://hosttech.net |
| BrandDad Directory | https://directory.branddad.social |
| Affiliates | https://affiliates.branddad.social |

Details: [`docs/sites/README.md`](docs/sites/README.md)

## Repository layout

```
wp-content/
  plugins/bds-directory-connect/   # Directory home connect (categories, search, Popular in)
  themes/                          # Public CSS/screenshot snapshots from live sites
docs/
  sites/                           # Site inventory
  branddad-social-company-profile.md
```

## Directory home connect

Plugin: [`wp-content/plugins/bds-directory-connect`](wp-content/plugins/bds-directory-connect)

Install on **directory.branddad.social** to fix:

- Dead category tabs (Automotive, Beauty & Personal Care, …)
- Duplicate / disconnected AI search vs Directorist search bar
- Hardcoded **Popular in** cities and stale category counts → live, auto-updating

## Notes

PHP theme source is not publicly fetchable from production. Snapshot CSS/screenshots are included under `wp-content/themes/`. Drop the full local BrandDad Social project into this repo to complete the depository.
