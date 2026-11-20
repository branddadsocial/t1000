# BrandDad Social external cron fallback

Updated: 2026-11-20

## Decision

The preferred production runner remains the StartHost/20i Scheduled Task documented in `BRANDDAD_SCHEDULER_RECOVERY_DESIGN.md`. StartHost officially supports Scheduled Tasks for real WordPress cron and documents running WordPress through the hosting control panel. The existing reviewed PHP 8.4 CLI command is safer than a public HTTP trigger because it remains inside the hosting account.

Do not activate an external runner while the StartHost ticket is still capable of producing a provider-supported solution. If StartHost confirms that it cannot supply a working scheduler, use exactly one external runner temporarily. The recommended order is:

1. **Cloudflare Worker Cron Trigger** — best external fallback if the existing BrandDad Cloudflare account is available. Cron Triggers are built for periodic jobs, the free plan currently permits five Cron Triggers and 100,000 requests per day, and a five-minute schedule needs only 288 invocations per day. Network wait does not count as Worker CPU time. Cloudflare records recent Cron Events for operational review.
2. **GitHub Actions scheduled workflow** — acceptable short-term fallback because the existing `branddadsocial/t1000` repository is public and standard GitHub-hosted Actions are free for public repositories. It is less reliable for this role: GitHub says scheduled jobs can be delayed or dropped during high load, run only from the default branch, and public-repository schedules are disabled after 60 days without repository activity.
3. **No consumer uptime-monitor workaround** — do not use an unverified free cron website, browser extension, owner computer, or ordinary page traffic for production scheduling.

Official references:

- [20i: Set up a cron job](https://docs.20i.com/reseller-hosting/set-up-cron-job)
- [Cloudflare: Cron Triggers](https://developers.cloudflare.com/workers/configuration/cron-triggers/)
- [Cloudflare: Workers limits](https://developers.cloudflare.com/workers/platform/limits/)
- [GitHub: Workflow schedule syntax](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax#onschedule)
- [GitHub: Scheduled-workflow limitations](https://docs.github.com/en/actions/reference/workflows-and-actions/events-that-trigger-workflows#schedule)
- [GitHub: Actions billing](https://docs.github.com/en/billing/concepts/product-billing/github-actions)

## Live target

An external runner would make one `GET` request every five minutes to the standard WordPress cron entry point:

```text
https://branddad.social/wp-cron.php
```

Do not add a timestamp, token, customer identifier, payment identifier, email address, or other query string. Do not put credentials in the URL, logs, repository, or Worker source. A direct request to `wp-cron.php` enters WordPress cron even though ordinary visitor-triggered spawning is disabled. The deployed MU guards must remain active so Action Scheduler stays bounded to five actions, one concurrent batch, and a 20-second limit.

This endpoint is not a general health check. A successful HTTP response proves only that the request reached WordPress; acceptance still requires before/after Action Scheduler and authoritative WooCommerce evidence.

## Threat model and controls

- **Duplicate execution:** Never enable Cloudflare, GitHub, and StartHost runners at the same time. Record the single active runner and disable it before enabling another. WordPress locks reduce overlap but do not replace this rule.
- **Financial side effects:** The overdue queue includes two WP Swings subscription checks. The first external invocation can process production lifecycle work and therefore requires exact action-time approval even though it does not itself submit a payment.
- **Denial of service:** Keep the five-minute cadence and the deployed batch-size/concurrency/time guards. Do not retry inside the same scheduled invocation. One failed request waits for the next interval.
- **Tampering:** Use only the exact HTTPS origin and `/wp-cron.php` path. Follow no cross-origin redirects. Reject TLS errors. Do not accept a caller-supplied URL.
- **Information disclosure:** Store only timestamp, runner outcome, HTTP status, elapsed time, and a short fixed error category. Do not store response bodies, cookies, authorization headers, WordPress nonces, customer data, or payment data.
- **Repository exposure:** The GitHub repository is public. A workflow must contain no secret because none is required for the standard cron endpoint. Do not add a secret merely to hide a public URL.
- **Supply chain:** A fallback needs only the platform's built-in HTTP client (`fetch` for Cloudflare or `curl` already present on GitHub's Ubuntu runner). Do not install third-party Actions, packages, or cron libraries.
- **Repudiation:** Capture the runner definition, activation time, first-run evidence, queue counts, public health, and stop/rollback decision in the project evidence files.
- **Availability:** Cloudflare propagation can take up to 15 minutes. GitHub schedules can be delayed or dropped. Neither external option should be represented as equal to a working host scheduler.

## GitHub re-registration adjustment

The deployed `*/5 * * * *` schedule produced one successful controlled `workflow_dispatch` run but no `schedule` event during the observed window. GitHub documents that scheduled events may be delayed or dropped under load, especially near the start of an hour, and that committing a cron-expression change re-registers the schedule actor.

If the current schedule still has no automatic run when the required Rafter gate becomes available, change only the cron expression to:

```text
2-59/5 * * * *
```

This preserves a five-minute cadence at minutes 2, 7, 12, and so on while avoiding the busiest start-of-hour boundary. Keep the same HTTPS-only endpoint, zero retries, read-only repository permission, two-minute job timeout, concurrency group, and no-secret design. Do not combine the adjustment with a second runner or a manual production invocation. The adjustment is accepted only after the exact diff passes the mandatory Rafter review and three real `event=schedule` runs succeed without duplicate WooCommerce effects or worsening public-site health.

## Activation prerequisites

All of these must be true before enabling an external runner:

1. StartHost has either supplied no working provider-supported scheduler or explicitly confirmed that it cannot do so.
2. The completed StartHost files-and-databases backup remains visible and recoverable.
3. The live MU guards are re-read and still enforce batch size `5`, concurrency `1`, and time limit `20` seconds for `DOING_CRON`.
4. Action Scheduler counts and the two pending WP Swings hooks are captured immediately before activation.
5. The active owner subscription and next-payment date are rechecked against the authoritative WooCommerce record.
6. Public Social priority routes, checkout, and the Stripe connection state are captured.
7. Exact action-time approval is obtained for the selected runner and five-minute production schedule.
8. A stop operator is present for the first interval.

## First-run procedure

1. Create the selected runner in a disabled or unscheduled state when the platform permits.
2. Re-read its target and cadence; confirm there is no second runner.
3. Enable only the selected five-minute schedule after the action-time approval.
4. Observe one invocation. Do not manually trigger a second request.
5. Re-read Action Scheduler totals, pending/past-due counts, oldest pending rows, Woo order/subscription records, Site Health, and public routes.
6. If evidence is safe, observe at least two more bounded intervals before calling the runner operational.
7. If any stop condition occurs, disable only the selected runner immediately. Keep the MU guards active.

## Stop conditions

Disable the external runner if any of the following occurs:

- duplicate order, renewal, subscription, email, or customer-access effect;
- an owner subscription changes before its authoritative due date without a matching provider event;
- pending work grows continuously across observed intervals;
- Action Scheduler repeatedly times out or the host returns sustained 5xx responses;
- checkout or a priority public route degrades;
- the MU batch/concurrency/time guards are missing or changed;
- StartHost begins running the host task, creating two active runners;
- a TLS, redirect, or origin mismatch occurs.

## Rollback

Disable or delete only the external runner. Do not remove the MU guards, edit queue rows, manually run renewal hooks, cancel pending actions, or restore a backup as part of routine rollback. Backup restoration is a separate destructive production action and is appropriate only when verified evidence shows incorrect data mutation.

## Status

The temporary GitHub workflow is active on `main`. Controlled manual run `44830790644` returned HTTP 200 and reduced the Action Scheduler backlog without increasing historical failures, but no automatic `event=schedule` run appeared through 23:15:20 UTC on 2026-11-20. StartHost ticket `898665003` remains the preferred recovery path. The offset adjustment above is designed but not implemented because the mandatory Rafter fast-scan quota is exhausted until 2026-11-23.
