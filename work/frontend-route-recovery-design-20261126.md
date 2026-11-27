# Frontend route recovery security design

## Scope

Recover two public, read-only page responses without changing authentication,
payments, stored customer data, or the existing website-audit REST endpoint:

- `GET /check-your-website/` on BrandDad Social.
- The Directory public homepage composition lifecycle.

## Data flow and trust boundaries

`Browser -> CDN/host -> WordPress MU loader -> fixed-path renderer -> HTML response`

The Social renderer recognizes only the existing health-check route through the
existing host/path predicate. It emits fixed BrandDad copy, escaped internal
URLs, the existing health-check shortcode, and its already-reviewed inline
assets. The user's website URL continues to cross the separate existing
`POST /wp-json/bds-health/v1/check` boundary; this recovery change does not
alter that endpoint, its validation, quotas, SSRF controls, or storage.

The Directory change adds no endpoint. It only removes one known WordPress
callback before that callback can delete the legacy homepage section hooks.

## Security decisions

- Public read-only responses require no authentication or authorization.
- No request body, database identifier, file path, or arbitrary template name is
  accepted by the recovery renderer.
- The route decision uses the exact Social host check and fixed health-check
  predicate. It does not dispatch from user-controlled input.
- Dynamic internal links are escaped with `esc_url`; document attributes and
  copy are escaped with the appropriate WordPress helpers.
- The renderer does not call the theme header/footer or start nested output
  buffers, avoiding the collision that produced empty or malformed output.
- No errors, server paths, credentials, or customer data are reflected.
- The existing POST audit endpoint retains its independent rate limiting and
  SSRF protections; no additional network fetch is introduced.

## STRIDE and abuse cases

- **Spoofing / elevation:** no identity or privileged operation exists on these
  GET responses.
- **Tampering:** fixed server-side markup and escaped URLs prevent request data
  from becoming executable HTML.
- **Repudiation:** no state-changing action is performed.
- **Information disclosure:** the response contains only public marketing copy
  and the public audit form.
- **Denial of service:** the recovery renderer performs no outbound fetch; it
  only renders the existing shortcode. The expensive scan remains POST-only and
  quota-controlled.
- **Abuse twin:** an attacker requests the page repeatedly. The response remains
  deterministic and does not trigger a scan or database write.

## Residual risk

The page intentionally depends on the existing health shortcode and REST
endpoint. A future incompatible change to either may break the form, so the
smoke test checks the form root, endpoint URL, single H1, and non-empty body.
