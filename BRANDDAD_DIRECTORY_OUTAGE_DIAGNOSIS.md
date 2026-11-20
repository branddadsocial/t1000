# BrandDad Directory Intermittent Database Outage Diagnosis

Date: 2026-11-20

## Executive status

BrandDad Directory experienced a verified intermittent production outage. Multiple sequential requests to the homepage, public REST index, Add Listing, All Categories, and All Listings returned HTTP 500 with WordPress's `Error establishing a database connection` response. BrandDad Social remained HTTP 200 during the same window.

The Directory later recovered without a BrandDad-side change. A final low-rate recheck returned HTTP 200 for both the Directory homepage and `/wp-json/`. This recovery does not prove stability because the hosting access log shows successful and failed Directory requests interleaved within seconds.

## Read-only hosting evidence

- The Directory MySQL database still exists in StartHost/StackCP.
- Database usage is approximately 131 MB of a 1 GB allowance, so this is not a storage-quota exhaustion condition.
- The separate Social database also exists and Social remained available.
- Access logs show Directory page and REST requests alternating between HTTP 200 and HTTP 500 during the incident window.
- A WordPress cron request also returned HTTP 200 during the same interval, reinforcing that the failure was intermittent rather than a deleted site or permanently invalid database configuration.
- Error-log filtering found no matching `mysqli` or `connections` diagnostic string. The panel's Auto Diagnostics page exposed no actionable result without initiating a new diagnostic action.

## Most supportable conclusion

The verified evidence is consistent with intermittent database availability or connection capacity at the hosting layer. It does **not** prove a specific root cause such as exhausted MySQL connections, a crashed database server, corrupted tables, or invalid WordPress credentials. No password, database, table, plugin, file, or customer record was changed during diagnosis.

## Launch gate

Do not classify the Directory as stably production-ready until:

1. StartHost reviews the incident window and confirms the database service is healthy.
2. Repeated low-rate homepage and REST checks remain successful over an observation window.
3. The existing traffic/load pattern is reviewed because the access log shows substantial concurrent page, AJAX, REST, and cron activity.
4. Any proposed database repair, password rotation, service restart, plugin disablement, or cache purge is separately backed up, scoped, approved, and verified.

## Safe next action

Provide StartHost with the incident time, affected host, alternating HTTP 200/500 evidence, and the WordPress database-connection response. Ask for the MySQL server's connection, saturation, restart, and error telemetry for the Directory database host. Sending or updating a support ticket is a representational external action and remains confirmation-gated.
