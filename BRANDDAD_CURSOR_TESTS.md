# BrandDad Cursor tests

## Passed locally

- `php -l deploy/cursor/bds-directory-featured-ads-lib.php`
- `php -l deploy/cursor/bds-directory-featured-ads-mu.php`
- `php -l deploy/cursor/bds-revenue-sections-mu.php`
- `php tests/featured-rotation-test.php`
  - Paid outranks trial and partnership.
  - Trial and partnership rotate by lower impression count.
  - A local card beyond 25 km is excluded.
  - Extra paid cards wait. A free card does not take a paid slot.
  - National placement requires the same country.
  - A second trial and an active paid placement are rejected.
  - Three pending global rows fill the cap. A revoked row frees a slot.
  - Public disclosure is `Sponsored` for paid and blank otherwise.

## Observed on the live sites

- Social homepage and services page returned the current AI launch content. `/services/` fail-closed: staged/quote_only rows go to `/contact-us/?offer=`, launch-candidate rows with published products go to `/product/…`. AI Social Growth product page shows Subscribe monthly at $29.99. No purchase was made.
- Directory homepage returned location search, city counts, and empty Featured Worldwide / Featured Near You lanes. Add-listing still redirects guests to `/select-your-plan/` with $6.99 Activation intact.
- `/account-hub/` on both sites renders membership comparison, guest free-AI copy, disabled studio boards, and “Open the portal”.
- `/affiliate-program/` on both sites links the portal root. Clicking it opened Partnero (`affiliates.branddad.social/program` after Partnero’s own redirect).
- Directory recovered from HTTP 500 after the featured-MU syntax patch. Rechecked home, account, studio, and affiliate-program: 200, no parse fatal.

- `php tests/customer-hub-test.php` passed on 2026-11-19.
- `php tests/revenue-sections-test.php` passed on 2026-11-19.
  - Music form accepts the package array or empty args and still prints option values.
  - Unknown and markup offer slugs return null.
  - Membership labels come from the supplied tier record.
  - Comparison rows follow the existing ladder and contain no price.
  - A missing free-AI ledger does not invent a remaining balance.
  - Affiliate copy does not state a percent.
  - SEO, Social, LinkedIn, chatbot, and video cards say they do not generate or publish.
  - Music rows discard a supplied paid status.
  - Saved listings add, remove, and stop at 50.
  - An existing canonical and a city URL both refuse a new alternate.

## Not run

- WordPress load of the new save and featured-event handlers (no paid grant to fire a real impression).
- Checkout submit, subscription renewal, listing claim form, verification, music payment, and affiliate commission.
- Phone viewport: Directory home at 390×844 shows Open navigation, search, List My Business, and Claim. Not a full device-lab pass.
- `rafter run` (quota 16/16 this period). Secrets scan of `deploy/cursor` was empty.
