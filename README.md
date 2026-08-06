# t1000

BrandDad network fixes and deployables.

## Do not change branddad.social chrome

Keep the modern Social look (`bds-site-footer`, Dark/Light toggle, current header). Directory fixes must not restore the old Elementor footer or remove the Dark button.

## Directory home connect

Plugin: [`wp-content/plugins/bds-directory-connect`](wp-content/plugins/bds-directory-connect)

Install on **directory.branddad.social** only (hard host-guarded) to fix:

- Dead category tabs (Automotive, Beauty & Personal Care, …)
- Local chips pointing at empty `/single-category/` pages → `/search-result/?in_cat=…`
- Duplicate / disconnected AI search vs Directorist search bar
- Hardcoded **Popular in** cities and stale category counts → live, auto-updating
