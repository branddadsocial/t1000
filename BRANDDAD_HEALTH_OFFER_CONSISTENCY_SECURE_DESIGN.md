# BrandDad Health Offer Consistency — secure design

## Scope

Repair one stale cached recommendation returned by the existing public website-check REST route. The existing scanner, URL normalization, SSRF controls, rate limits, cache, scores, issue detection, and request parsing are not changed.

## Data flow and trust boundaries

`Browser -> WordPress REST endpoint -> existing health scanner/cache -> REST response filter -> Browser`

The request and scanned website remain untrusted. This patch reads only the already-created response and replaces one exact, server-owned legacy offer identity with fixed server-owned public catalog values.

## API and ingestion decisions

- Route scope is exact: `/bds-health/v1/check` only.
- The filter runs only for `WP_REST_Response` objects containing a `recommendations` array.
- No request field, URL, remote body, cached HTML, title, or price is copied into the replacement values.
- Matching is restricted to the historical internal key, exact legacy title, or exact legacy product path.
- The replacement title, price, URL, period, and description are fixed constants owned by BrandDad.
- Unknown recommendations and every non-health REST route pass through unchanged.
- The patch creates no write endpoint, database write, cache deletion, network request, payment action, customer entitlement, or external execution.

## STRIDE review

- **Spoofing:** no identity or authentication behavior is introduced.
- **Tampering:** only the exact retired offer identity can be normalized; unrelated response fields are preserved.
- **Repudiation:** plugin version and focused test provide an auditable release identity.
- **Information disclosure:** no new response fields, request values, secrets, internal paths, or exception details are exposed.
- **Denial of service:** one bounded pass over the existing recommendation list; the scanner already caps recommendations at four.
- **Elevation of privilege:** no privileged function or capability is added.

## Abuse twin

Legitimate use: a visitor receives the current $19.99 AI improvement-plan recommendation. Abuse attempt: a caller supplies a crafted URL or response-shaped input hoping to alter arbitrary products. Mitigation: request data is never used to choose replacement content, and only the server-generated recommendation list on the exact existing route is inspected.

## Residual risk and rollback

The response filter is a compatibility layer rather than a cache purge. If the underlying catalog changes again, this fixed mapping must be updated or removed. Rollback is removal of this single MU-plugin file; no stored data requires reversal.
