# BrandDad paid fulfillment boundary

Updated: 2026-11-27

## Decision

WooCommerce's persisted paid state is the only authority that may create a staff fulfillment task. Creating a cart, reaching checkout, creating a pending order, or receiving an unverified/replayed notification is insufficient.

Done-for-You AI Management remains quote-first. Public buttons lead to Contact Us. After both sides accept a written scope, staff creates a pending WooCommerce order in the WordPress admin for the exact quote-only product and sends WooCommerce's native order-payment link. The paid-task handler recognizes that product only when the order was created through `admin`; a browser-created order cannot activate quote-only fulfillment. After Woo records payment, the same paid-task boundary creates one private staff task.

One-time submission products keep their immutable service metadata on the order line at checkout, but no staff task is created until the order is paid.

## Data flow and trust boundaries

```text
Customer browser -> Woo checkout/order-pay -> payment gateway
       |                    |                       |
       | untrusted input    | BrandDad DB          | signed gateway callback
       +--------------------+-----------------------+
                            |
                   Woo canonical paid state
                            |
             paid-task idempotency claim (option)
                            |
             private Woo order note + audit metadata
                            |
                         Staff
```

Trust boundaries are browser to WordPress, gateway callback to WooCommerce, and WooCommerce paid state to staff fulfillment. BrandDad does not parse gateway payloads in this feature; the installed payment integration remains responsible for signature verification. This code rechecks `WC_Order::is_paid()` even when a paid-status hook fires.

## Security decisions

- Product/service identity comes only from allowlisted server data or the persisted Woo product slug; no request parameter supplies it.
- The quote-only product requires `created_via=admin`, so adding or guessing a public product URL cannot create a task.
- `woocommerce_checkout_order_created` is not a fulfillment trigger.
- `woocommerce_payment_complete`, `woocommerce_order_status_processing`, and `woocommerce_order_status_completed` are recovery/retry triggers; all enter the same paid-state check.
- An atomic WordPress option claim is keyed to one order. It is retained after success and prevents concurrent or duplicate payment/status notifications from creating a second task, including with Woo HPOS enabled.
- If note creation fails, the claim is released so a later verified paid event can retry. If note creation succeeds but a later metadata save is interrupted, the retained claim still prevents duplicate work.
- The private order note records the task note id, creation time, and implementation version on the order for auditability.
- No public checkout, catalog visibility, product price, payment gateway, customer order, or live record is modified by this change.

## STRIDE and abuse cases

- Spoofing/tampering: a caller cannot create a task by invoking the function with an unpaid order; Woo's persisted paid state is revalidated.
- Repudiation: the private order note and order metadata identify the paid order and task creation time.
- Information disclosure: no customer facts are exposed by the staging report; it returns only fixed product identity and boolean checks.
- Denial/duplication: the atomic claim collapses retry storms into one task.
- Elevation: quote-only fulfillment requires an admin-created order. The feature adds no endpoint or capability.
- Abuse twin — abandoned checkout: a pending order is created but never paid. Expected result: no task.
- Abuse twin — duplicate gateway webhook: paid hooks fire more than once. Expected result: one task.
- Abuse twin — guessed Done-for-You product URL: a browser-created order exists. Expected result: no quote task.

## Residual risk

The paid-task handler relies on WooCommerce and the installed payment gateway to verify payment correctly. A compromised administrator can mark an order paid; that is an existing WooCommerce administrative power, not a new capability. Quote acceptance remains a staff process rather than a public API so it cannot be automated without a separately reviewed authorization and audit design.

## Public quote-only purchase guard

The live Services page and commercial catalog identify Done-for-You management as quote-first, but an older public product-page callback can still render retired Instagram growth products and Woo purchase controls. The repair is an additive, exact-slug guard; it does not edit or delete a Woo product, order, subscription, or customer record.

```text
Public product request -> exact server-owned slug allowlist -> quote page/contact
Direct add-to-cart      -> exact server-owned slug allowlist -> reject + contact notice
Accepted written quote -> staff-created admin order -> native Woo order-pay link
Paid admin order        -> existing paid-state/idempotent staff-task boundary
```

Security decisions:

- Only `done-for-you-ai-management` and the six retired `bds-ig-growth-*` slugs are guarded. IDs and slugs are not accepted from request data.
- New cart additions are rejected server-side, including guarded parent/variation combinations. Hiding a button is presentation only and is not the security control.
- Checkout validation also rejects a guarded item already present in a stale cart without deleting or changing that cart.
- Existing orders, subscriptions, renewals, refunds, and staff-created admin orders are not mutated or cancelled.
- The canonical Done-for-You page removes the standard public add-to-cart form and renders current written-scope copy plus a Contact BrandDad action.
- Retired growth-product GET pages redirect to the current Services management section; the redirect performs no purchase or write.
- The repair adds no endpoint, credential, remote request, dependency, customer message, or payment call.

Threat/abuse cases:

- Direct `?add-to-cart=` or crafted cart request: server-side validation rejects the guarded product.
- Stale cached product link: the destination cannot be added to a new cart after the guard loads.
- Existing subscriber renewal: no cart validation path is used and no subscription record is changed.
- Guessed unrelated product: exact allowlist miss leaves the product untouched.

Residual risk: a previously cached HTML page may continue to display retired wording until that cache object expires, but the server-side cart rejection still prevents a new purchase. Cache refresh is a separate recoverable deployment step and must not delete global caches.
