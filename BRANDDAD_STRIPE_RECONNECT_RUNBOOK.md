# BrandDad Stripe connection recovery runbook

Updated: 2026-11-20

## Current verified state

- WooCommerce Stripe is enabled on live BrandDad Social.
- Woo reports payments, payouts, and webhooks enabled.
- The live connection is marked `Incomplete` because stored Stripe account keys exist but Woo cannot verify their origin.
- Synchronization is disabled.
- The configured webhook endpoint is `https://branddad.social/?wc-api=wc_stripe`.
- No live webhook has been recorded since monitoring began on 2026-11-08 17:14:47 UTC.
- A public GET reached the endpoint with HTTP 200. An unsigned empty JSON POST reached WordPress with HTTP 204 and created no order, payment, subscription, or setting change. This proves reachability only, not signature handling.
- Test mode is unavailable until a separate test Stripe connection exists.

## Non-negotiable protections

- Do not create a replacement Stripe business account.
- Do not disconnect the existing live account unless an exact rollback is captured and reconnecting the same account is guaranteed.
- Do not submit a live charge merely to test webhooks.
- Do not change product prices, existing subscriptions, saved payment methods, payout details, statement text, enabled methods, capture mode, or unrelated Woo settings.
- Do not expose account keys or webhook secrets in chat, browser JavaScript, Git, screenshots, or logs.
- Starting OAuth or creating persistent account access requires action-time confirmation and the owner must complete any authentication/verification step.

## Preflight evidence

1. Confirm the current Woo settings page still reports the same intended live Stripe business/account identity.
2. Capture the current enabled payment methods and Stripe settings without exposing credentials.
3. Confirm a current files-and-database backup is complete and downloadable.
4. Record current Woo order/subscription counts and the existing active subscription state.
5. Record Action Scheduler counts. Scheduler recovery remains a separate prerequisite for reliable asynchronous fulfillment.
6. Confirm the webhook endpoint returns a non-cached WordPress response and is not redirected.

## Authorized reconnect sequence

1. Open WooCommerce -> Settings -> Payments -> Stripe -> Settings -> Configure connection.
2. Choose the existing Stripe account only. Do not create a new account.
3. Complete owner authentication and any provider-required verification manually.
4. Return to Woo and verify `Account` is complete, `Webhooks` configured, and synchronization enabled/healthy.
5. Verify the webhook endpoint remains exactly `https://branddad.social/?wc-api=wc_stripe`.
6. Do not click Save changes for unrelated settings.
7. Refresh the connection status and confirm no warning says the key origin is unverifiable.

## Safe acceptance after reconnect

1. Prefer a separately connected Stripe test account and test mode. Never replace or mutate the live customer account merely to obtain test mode.
2. Send one Stripe-signed test event or use the provider's supported test facility.
3. Confirm Woo records receipt once and rejects replay/invalid signature without duplicate fulfillment.
4. Run one safe test purchase only after separate financial action-time confirmation.
5. Verify exactly one order, one subscription, one entitlement, and one allowance cycle.
6. Verify failed payment, renewal, cancellation, refund, billing portal, and webhook retry behavior in the safe environment.
7. Reconcile Action Scheduler, Woo order notes, Stripe event ids, lifecycle evidence, and customer access.

## Rollback and stop conditions

Stop immediately if Woo proposes a different Stripe business/account, requests new live credentials unexpectedly, changes existing payment methods, shows duplicate webhook endpoints, or modifies an existing subscription. Preserve screenshots and logs, close without saving unrelated settings, and restore only from the verified backup if an actual mutation occurred.

The connection is not production-accepted until the same intended account is complete, a signed webhook is observed, idempotency is verified, the scheduler is operational, and a safe payment lifecycle passes without duplicate charges or fulfillment.
