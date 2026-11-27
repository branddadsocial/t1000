# Frontend final-fixes security design

## Scope

- Social Services CSS removes viewport-width breakout math that caused horizontal overflow.
- Directory homepage compatibility adds a final DOM heading guard after legacy footer scripts.
- No authentication, payments, storage, network fetches, uploads, database writes, or dependencies are added.

## Data flow and trust boundaries

`[WordPress/plugin-rendered HTML] -> [browser DOM] -> [heading guard] -> [accessible document outline]`

The only boundary is server-rendered WordPress HTML entering the browser DOM. The guard selects fixed IDs/classes and moves existing DOM nodes. It does not use `innerHTML`, evaluate strings, fetch URLs, inspect request parameters, or send data.

## Input and output controls

- Input shape: one optional `#bdd-title` element and one optional `h1.bds-wah__title` element.
- Unknown/missing elements: fail closed with a no-op.
- Size: the guard does not copy strings; it moves the existing bounded DOM children.
- Output: the known hidden legacy heading becomes H2 and the visible `#bdd-title` becomes H1.
- No executable context receives untrusted bytes.

## STRIDE review

- Spoofing: N/A; no identity or session handling.
- Tampering: selection is restricted to fixed selectors on the exact Directory root renderer.
- Repudiation: N/A; no state-changing business action.
- Information disclosure: no data leaves the document.
- Denial of service: two bounded selector lookups and two bounded attribute loops; no observers or retries.
- Elevation of privilege: no privileged endpoint, capability, or persistence.

Abuse twin: a malicious content block attempts to inject markup into the hero; the guard never reparses HTML and only moves already-parsed child nodes, so it does not create a new injection path.

## Residual risk

Legacy scripts may change again and run after this guard. The compatibility renderer intentionally writes the guard after `wp_footer()` so it is last among current synchronous scripts; live DOM verification remains required after deployment.

## Manual code review

- A01 access control: N/A; the change exposes no route or privileged action.
- A02 cryptography: N/A; no credentials, tokens, cryptography, or persistence. Staged-file secret scan passed.
- A03 injection: `deploy/zzz-bds-directory-home-compat-mu.php:73-103` uses fixed selectors, `createElement`, allowlisted attributes, and child-node moves; it never uses `innerHTML`, `eval`, or string-to-markup parsing.
- A04 insecure design: the guard is fail-closed when `#bdd-title` is absent and is emitted only by the exact-root Directory compatibility renderer at `deploy/zzz-bds-directory-home-compat-mu.php:134-145`.
- A05 misconfiguration: no production configuration is changed by the source diff.
- A06 dependencies: no dependency or manifest change.
- A07 authentication: N/A; no authentication flow.
- A08 integrity: the two deploy files remain covered by focused PHP behavior tests before release.
- A09 logging: N/A; no security or business event is created.
- A10 SSRF: N/A; no outbound request or user-controlled URL.

Review result: no new exploitable security issue found in the staged diff. Deployment and live verification remain separate release gates.
