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

- Social `/services/` 0.2.0: heading **Plans you can buy now**, four **Subscribe now** buttons, **Get featured**, then **Upcoming plans — inquiry only**. No purchase was made.
- Directory `/featured-advertising/` returns 200 with title “Get featured on BrandDad Directory”, lane prices, and the inquiry form. Homepage empty lanes print “No featured businesses are showing here yet” plus the $9.99/$99.99 request copy.
- `/account-hub/` **Do this next** is live: Social leads with Subscribe to AI Social Growth; Directory leads with list / plans / Get featured / claim.
- Claim explainer post now ends with **Claim, list, or get featured**. Listing claim form on a card was not completed.
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
