# StartHost Support Request — Intermittent Directory Database Failures

## Suggested subject

Intermittent MySQL connection failures on directory.branddad.social

## Suggested message

Hello StartHost Support,

Please investigate an intermittent production database incident affecting `directory.branddad.social` on 20 November 2026.

During the incident, multiple sequential requests to the Directory homepage, WordPress REST index, Add Listing, All Categories, and All Listings returned HTTP 500 with WordPress's `Error establishing a database connection` response. `branddad.social` remained HTTP 200 during the same window.

The StackCP database inventory still showed the Directory database present on `sdb-68.hosting.stackcp.net` and using approximately 131 MB of its 1 GB allowance. The hosting access log showed successful and failed Directory requests interleaved within seconds, including page, REST, AJAX, and WordPress cron traffic. The site later recovered to HTTP 200 without any database, password, WordPress, plugin, cache, or hosting-setting change by us.

Please review the server telemetry for the incident window around 20 November 2026 18:42 UTC and confirm:

1. Whether the MySQL service or database host restarted, failed over, or became unavailable.
2. Whether connection, process, CPU, memory, I/O, or account-level limits were reached.
3. Whether requests were refused, timed out, or throttled.
4. Whether any database-table or storage errors were recorded.
5. Whether this hosting package has a database connection or concurrency limit that the observed traffic exceeded.
6. What non-destructive corrective action you recommend to prevent recurrence.

Please do not reset database passwords, repair tables, delete files, disable plugins, purge customer data, or restart services without first describing the exact proposed action and its impact. We have a completed combined files-and-databases backup, but we want the root cause identified before making a production change.

Thank you.

## Submission gate

This draft has not been sent. Creating a ticket or reply represents the owner to an external provider and requires action-time confirmation.
