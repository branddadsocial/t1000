# BrandDad FormWise content and access audit

Date: 2026-11-20
Method: connected organization read-back only; no agent, flow, app, or draft execution and no credit spend.

## Verified inventory

- Organization: `BrandDad Social` (`branddad-social`), owner access.
- Published public agents: BrandDad Directory Concierge, BrandDad Directory Assistant, and BrandDad Sales Assistant.
- Published app: BrandDad AI Studio with eight customer workflows.
- Published flows: 13 operational flows; Video Director and the isolated consent QA fixture remain unpublished.
- Portals: `BrandDad Customer AI` and `BrandDad Internal AI`, both login-required and restricted.
- Commercial plans: Starter 50/$14.99, Growth 150/$29.99, Pro 400/$49.99, Business Essentials 75/$29.99, AI Lead Assistant 80/$19.99, AI Local SEO 60/$19.99, and AI Social Growth 150/$29.99. The one-credit QA plan remains private.

## Correctly separated experiences

- The Directory Concierge is explicitly Directory-only, asks for location before category when relevant, and invokes the grounded Directory Finder for real results.
- The Directory Finder accepts need, location, and content type and is described as returning only published Directory records and public fields.
- The BrandDad Sales Assistant is explicitly Social-only, uses the verified BrandDad service knowledgebase, and points human follow-up to `https://wa.me/13124287008`, the public booking URL, or the contact page.
- Public-facing names and descriptions use BrandDad branding. The published share host is `ai.branddad.social`; no customer-facing asset name contains `FormWise`.
- The AI Studio app is restricted to embedding on `https://branddad.social` and refuses direct-link opening.

## Findings requiring correction or controlled acceptance

### 1. Duplicate public Directory assistants

Both `BrandDad Directory Concierge` and the older `BrandDad Directory Assistant` are published. The Concierge is the stronger canonical asset because it owns the live Finder flow; the older Assistant has no attached flow and tells visitors to use a separate Finder. Only one launcher was observed on the public Directory, but the duplicate asset remains directly reachable and can be embedded.

Recommended disposition: keep the Concierge as the sole public Directory assistant; retire or restrict the older Assistant after confirming no remaining page references its UUID.

### 2. Public-agent share settings are broader than BrandDad's white-label requirement

The Concierge, older Directory Assistant, and Social Sales Assistant are each configured as `embedding=anywhere` with direct links enabled. That is inconsistent with the requirement that customer-facing assistants connect only through BrandDad-controlled sites.

Recommended disposition: restrict the Directory Concierge to `https://directory.branddad.social`, restrict the Sales Assistant to `https://branddad.social`, disable direct-link opening where supported, and retire/restrict the older Directory Assistant. This requires a provider configuration change and post-change embed acceptance.

### 3. Sales knowledge contains a legacy catalog

The authoritative knowledge page was last verified on November 9, 2026. It promotes the legacy AI Sales Assistant Basic (`$99` setup plus `$39/month`), legacy Plus (`$199` setup plus `$79/month`), and Custom Automation (`$499` setup plus `$149/month`). These offers still exist as preserved `live_legacy` Woo products, so the page is not fabricated, but it does not lead with the seven current launch-candidate offers or the flagship AI Business Essentials plan.

Recommended disposition: preserve legacy customers and URLs, but revise the sales knowledge to lead with the verified public launch candidates: AI Business Essentials $29.99/month with the disclosed seven-day trial; AI Studio Starter/Growth/Pro; AI Local SEO; AI Lead Assistant; and AI Social Growth. Do not promote staged $39.99 Sales Assistant Plus until cost and fulfillment acceptance passes.

### 4. Sales behavior has a small instruction conflict

The live Sales Assistant instruction requires exactly one discovery question and waiting for the answer before a personalized recommendation. The attached operating guide says “at most two” before recommending. The agent instruction is safer and clearer.

Recommended disposition: update the operating guide to the exact one-question rule while preserving immediate answers for direct price-list, named-service, booking, support, refund, or checkout requests.

### 5. Failed website knowledge source

The source for `https://branddad.social/services` is status `failed`, not searchable, and reports `RAG processing failed`. The two manually maintained pages remain available, so current runs are not ungrounded, but automatic catalog freshness is absent.

Recommended disposition: keep the manually approved catalog authoritative until a previewed, bounded recrawl succeeds; do not spend crawl credits or replace approved knowledge without a controlled content review.

### 6. Paid-plan groups were not admitted to the customer portal — repaired

The four newer paid-plan groups already have scoped tool grants but were absent from the restricted customer portal's admission list. This would prevent correctly provisioned customers from signing in through that portal even after receiving the plan group.

Completed safe repair: admitted only Business Essentials, AI Lead Assistant, AI Local SEO, and AI Social Growth to the existing login-required customer portal. Provider read-back reports nine admitted groups and preserves `loginRequired=true`, `door=restricted`, and no storefront. This added no users, memberships, grants, credits, or executions.

### 7. Staging labels do not match published state

Several flows are published while their descriptions still say “Unpublished staging,” including Copy and Reviews and the LinkedIn Profile Analyzer. The Internal Proposal Builder also says unpublished while it is published, although its access is correctly limited to the internal team.

Recommended disposition: correct descriptions during the next tested FormWise content release so operators do not misread production state. Do not republish customer-facing flows without a controlled draft run and test-case pass.

## Cost and mutation boundary

- No agent, flow, app, draft, website crawl, or test case was run.
- No credit was charged.
- The only provider mutation after the read-only audit was the four idempotent customer-portal admissions documented above. No end user, plan, membership, execution, knowledge page, assistant, flow, app, grant, or share setting was changed.
- Any agent/content publication requires controlled draft testing because published changes affect customer answers immediately.
