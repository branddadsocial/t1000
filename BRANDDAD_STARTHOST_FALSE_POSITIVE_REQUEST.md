# StartHost false-positive clearance request

Status: submitted after explicit action-time confirmation. StartHost Web Hosting ticket `898665003` is `Awaiting Customer Reply` against the `branddad.social` package, created 2026-11-20 17:48:42 and last updated 2026-11-20 18:10:34.

## Provider reply

StartHost said it cannot remove the flag and that the warning can be ignored when the owner knows it is a false positive. That response does not resolve the operational blocker: after the visible package banner cleared, the exact reviewed PHP 8.4 scheduled task still returned `Cron failed to add`, the task list remained empty, and no cron batch ran.

## Prepared follow-up — not sent

Sending this is a representational action and requires action-time confirmation.

> Thank you. Ignoring the warning does not resolve the operational issue. The exact PHP 8.4 scheduled task was attempted again after the visible package warning cleared, and StartHost still returned `Cron failed to add`; no scheduled task was created.
>
> Please identify the exact remaining backend restriction preventing Scheduled Tasks for this package and either release that restriction or provide the provider-supported alternative for running WordPress `wp-cron.php` every five minutes. The reviewed command is:
>
> `/usr/bin/php84 -f /home/sites/41a/c/c0a8249376/public_html/branddad.social/wp-cron.php`
>
> Please do not delete or modify production files, databases, customer data, or subscriptions. We only need the scheduler restriction resolved or a safe supported runner documented.

## Form selections

- Department: Web Hosting
- Service: `branddad.social`
- Subject: `False-positive malware flag is blocking scheduled tasks`

## Message

Hello StartHost Support,

Please review and clear the malware package flag for the `branddad.social` hosting service, or safely allowlist the verified generated-cache path/signature so Scheduled Tasks can be enabled.

Fresh scan `24662257` completed on 20 November 2026 at 17:01:32 UTC. It reports six current files under:

`/home/sites/41a/c/c0a8249376/public_html/directory.branddad.social/wp-content/cache/bd-eco-html/`

with the unofficial heuristic:

`php.wp.content.upload.wp-include.wa.me.window.function.if.typeof.document.eval.UNOFFICIAL`

The scan also reports one historical maintenance file that was already quarantined outside the public web root:

`/home/sites/41a/c/c0a8249376/malware_quarantine_oneshot_sweep_20260822T005229Z/public_html_directory.branddad.social_bds-boxcoin-multisite-enable-oneshot.php.done`

We inspected all six current HTML objects. They are ordinary generated WordPress page caches for a claim guide, plan page, products, a gift-card product, and a Directory listing. They contain legitimate WordPress asset paths, browser scripts, and BrandDad WhatsApp contact links. We found no executable `eval(`, `document.write`, `atob(`, `base64_decode`, `fromCharCode`, or `unescape(` patterns. The objects regenerate normally, so deleting individual cache files will not provide a durable fix.

The package flag currently causes StartHost Scheduled Tasks to reject the reviewed WordPress cron command with `Cron failed to add`. Please confirm whether you can:

1. clear the false-positive package flag;
2. allowlist the generated cache path or this exact unofficial signature for the package; and
3. keep normal malware protection active for all other files.

Please do not delete customer data or production files. If you require a sample or additional evidence, tell us the preferred secure method.

Thank you.

## Evidence notes

- ClamAV's own false-positive guidance says an alert may be a false positive and advises technical investigation instead of deleting a file out of hand: <https://docs.clamav.net/faq/faq-scan-alerts.html>
- The detection is explicitly `.UNOFFICIAL`, indicating a third-party signature rather than an official ClamAV signature. Sanesecurity's guidance recommends contacting the signature provider or using a local allowlist for confirmed false positives: <https://sanesecurity.org/support/false-positives/>
- A recent WordPress support case involving 20i documents the same remedy for a confirmed scanner false positive: ask 20i support to whitelist the affected file/path: <https://wordpress.org/support/topic/malware-in-signtures-csv/>

