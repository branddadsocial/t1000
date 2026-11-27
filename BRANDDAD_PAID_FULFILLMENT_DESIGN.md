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
