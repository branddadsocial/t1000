# BrandDad Social scheduler recovery design

Updated: 2026-11-20

## Purpose

Restore reliable WordPress and WooCommerce background processing without letting ordinary public requests drain the Action Scheduler queue. This recovery covers the live `branddad.social` site only. It does not authorize a payment, renewal, refund, message, publication, or mass queue deletion.

## Verified current state

- The StartHost Scheduled Tasks page reports **no scheduled tasks**.
- The active site PHP version is **PHP 8.4** and StartHost documents its CLI interpreter as `/usr/bin/php84`.
- The site root is `/home/sites/41a/c/c0a8249376/public_html/branddad.social/`.
- Live MU guards intentionally define `DISABLE_WP_CRON`, disable the async runner, and allow only bounded cron/CLI processing.
- WooCommerce Action Scheduler reports 4,990 actions: 771 complete, 4,166 failed, 53 pending, and 52 past due.
- The failed scheduled event is `action_scheduler_run_queue`.
- Past-due hooks include ordinary WooCommerce maintenance plus WP Swings subscription hooks. One active owner subscription exists, with its next payment shown as 2026-12-13.

## Proposed host task

Create one StartHost Scheduled Task after an exact action-time confirmation:

```text
/usr/bin/php84 -f /home/sites/41a/c/c0a8249376/public_html/branddad.social/wp-cron.php
```

Schedule it every five minutes with every hour, day of month, month, and day selected. Leave the optional email field empty during the initial bounded recovery to avoid a mail flood.

The command is derived from the hosting panel's verified PHP interpreter list, active PHP version, and service root. It must not be saved until the user confirms the exact recurring production action.

## Security and operational controls

- Use the authenticated StartHost account only; do not expose a public repair endpoint.
- Do not put credentials, tokens, customer data, or query parameters in the command.
- Keep the existing hard connection guard active so cron batches remain limited to five actions, one concurrent batch, and a 20-second execution limit.
- Do not use the hosting panel's **Test Command** button before the production queue risk is accepted; it would immediately execute cron.
- Do not manually invoke renewal hooks, fabricate dates, or edit subscription records.
- Do not mass-run, mass-cancel, or mass-delete failed/pending actions.
- Do not delete historical failed rows until the running queue is stable and each no-callback hook has been classified.
- Record before/after queue counts, the scheduled task, and public HTTP health without recording PII or payment details.
- If the site degrades, disable only this exact scheduled task; the MU guards continue preventing request-driven queue execution.

## Threat model

- **Spoofing:** Require the existing authenticated hosting session; never add a public unauthenticated trigger.
- **Tampering:** Use the exact allowlisted PHP binary and exact `wp-cron.php` path; reject shell composition, redirects, environment interpolation, and extra arguments.
- **Repudiation:** Capture the task definition, timestamp, Action Scheduler counts, and verification results in the project evidence files.
- **Information disclosure:** Leave notification email blank and never include secrets or customer data in the command or evidence.
- **Denial of service:** Run every five minutes with the deployed five-action/one-worker/20-second bounds; observe the first completed interval before broader cleanup.
- **Elevation of privilege:** Make no WordPress capability, role, plugin, or filesystem permission changes.
- **Financial side effects:** Treat subscription-related hooks as financial-adjacent. Require action-time confirmation before saving the recurring task, and do not execute any checkout, refund, renewal, or billing action manually.

## Recovery sequence

1. Verify or create a recoverable database/files backup through the hosting system before queue mutation.
2. Save the exact scheduled task only after action-time confirmation.
3. Wait for one scheduled interval and verify the task remains configured.
4. Re-read Action Scheduler counts and the oldest pending/past-due rows.
5. Verify the homepage, Services page, Business Essentials product, checkout, and REST health.
6. If counts move safely, continue monitoring bounded batches. If errors or load increase, disable the exact task and investigate.
7. Classify historical no-callback failures separately; cleanup is a distinct destructive action requiring its own evidence and approval.

## Acceptance criteria

- A StartHost scheduled task exists with the exact allowlisted command and five-minute cadence.
- `action_scheduler_run_queue` no longer appears as a failed scheduled event after bounded processing has had time to run.
- Pending/past-due actions decrease without duplicate orders, duplicate subscriptions, customer billing changes, or front-end degradation.
- Public priority routes remain HTTP 200 and checkout remains available.
- Any financial lifecycle event is reconciled against the authoritative WooCommerce/Stripe/PayPal record before being called successful.

## Rollback

Disable or remove only the exact new StartHost scheduled task. Do not remove the MU guards. Restore the verified database/files backup only if evidence shows queue processing changed data incorrectly; backup restoration is a separate destructive production action.
