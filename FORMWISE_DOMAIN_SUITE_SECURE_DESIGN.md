# FormWise domain-restricted Suite migration

## Scope and decision

The public Social Sales Assistant and Directory Concierge currently use standalone FormWise Agent embeds. FormWise reports those Agents as `embedding=anywhere`; the standalone Agent Share dialog exposes no URL allowlist. FormWise's supported domain allowlist exists on Suites. The migration therefore wraps each already-published Agent in a one-product Suite, keeps session context off, authorizes exactly one first-party origin per Suite, and changes only the public iframe source.

- Social Suite: `e49a28ba-e73c-4c4e-b945-92018cd47034`; intended origin `https://branddad.social`.
- Directory Suite: `32733848-06ea-47ad-bff9-4a95b9a0e3c1`; intended origin `https://directory.branddad.social`.
- No customer credentials, contact details, account identifiers, or URL-supplied session context are introduced.
- The standalone Agent URLs remain the rollback targets until the Suite embeds pass live desktop and mobile smoke tests.

## Data flow and trust boundaries

`Visitor browser -> BrandDad WordPress page -> sandboxed iframe on ai.branddad.social -> FormWise Suite -> existing published Agent -> attached BrandDad knowledge`

Trust boundaries are the visitor-to-WordPress request, WordPress-to-FormWise iframe load, and Suite-to-Agent execution. TLS is required across all boundaries. The browser page supplies no secrets or customer identity to the iframe. The Suite allowlist limits which first-party page origins may embed the Suite; it is not treated as customer authentication or a paid entitlement gate.

## Access-control and API decisions

- Domain enforcement is provider-managed in the Suite's **Restrict to authorized URLs** setting; no custom origin parser is added.
- Each Suite contains exactly one intended public Agent. The superseded Directory Assistant, private templates, QA tools, and unrelated Flows are excluded.
- Session context remains off to avoid unreviewed visitor-data transmission and cross-context leakage.
- Public chat remains anonymous and rate/cost bounded by the existing FormWise/BrandDad controls. Paid tools must continue to use server-side entitlement checks; the Suite URL is not an authorization primitive.
- Embed URLs are fixed constants. No visitor-controlled URL, redirect, outbound fetch target, token, or credential is accepted.
- The iframe retains `referrerpolicy="strict-origin-when-cross-origin"` and a minimal sandbox/permission allowlist. Camera is not granted; microphone remains absent unless a tested voice feature is deliberately approved later.

## STRIDE review

- **Spoofing:** Only the existing published Agents are included. Workspace administration remains behind FormWise authentication. The Suite URL does not assert customer identity.
- **Tampering:** The iframe source is a fixed HTTPS custom-domain URL. WordPress output escaping remains in place. Deployment uses reviewed source and package checksums.
- **Repudiation:** FormWise history records Suite creation and settings changes; Git records the iframe-source change; deployment verification records the live version and URLs.
- **Information disclosure:** Session context is off; no PII or credentials are added to URLs; privacy warnings remain visible; no provider key is exposed client-side.
- **Denial of service:** The change does not add a new execution endpoint. Existing provider quotas and the server-side proxy controls remain authoritative. A provider outage fails the iframe without weakening WordPress access controls.
- **Elevation of privilege:** Suite embedding is presentation-only. It grants no WordPress, WooCommerce, FormWise-admin, payment, or account privileges.

## Abuse cases and controls

- An unrelated website copies the iframe: the Suite domain allowlist rejects that embedding origin.
- A visitor opens the owned `ai.branddad.social` share URL directly: this may remain provider-supported public access and is acceptable because it stays on the owned white-label domain; it exposes only the same public assistant.
- An attacker tries to pass identity or plan data in the link: Suite session context is off and no such values are added by BrandDad.
- A bad deploy points both sites at one assistant: separate Suite IDs, exact-domain smoke tests, and content/identity test prompts detect the mix-up.

## Deployment and rollback

1. Add the exact authorized origin to each Suite only after action-time approval.
2. Replace each site's standalone Agent iframe URL with its corresponding Suite URL in reviewed source.
3. Run local lint, contract tests, Rafter code review, and secrets scan.
4. Deploy one site at a time. Verify launcher placement, iframe load, assistant identity, first message, mobile layout, and unauthorized-origin rejection.
5. If a smoke test fails, restore the previous fixed Agent URL and leave the Suite unpublished from the site. Do not delete Agents, Suites, history, or customer data.

## Residual risk

The Suite allowlist prevents third-party embedding but is not a confidentiality boundary: the assistant remains public on an owned white-label share domain. This matches the public-assistant use case. Stronger visitor authentication would require a separate portal/entitlement design and is intentionally out of scope for the public Sales Assistant and Directory Concierge.
