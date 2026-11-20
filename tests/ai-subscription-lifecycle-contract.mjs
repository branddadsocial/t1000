import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-ai-subscriptions-mu.php', 'utf8');

const checks = [
  ['version is 0.5.1', /BDS_AI_SUBSCRIPTIONS_VERSION', '0\.5\.1'/],
  ['launch subscription identity comes from the server-side catalog', /function bds_ai_cart_has_launch_subscription[\s\S]*bds_ai_subscription_catalog\(\)[\s\S]*get_post_field\( 'post_name'/],
  ['only customer plans with FormWise tier ids enter the live lane', /'customer' === \( \$plan\['audience'\][\s\S]*wp_is_uuid\( \(string\) \( \$plan\['formwise_plan_id'\]/],
  ['launch subscription checkout is Stripe only and fails closed', /return isset\( \$gateways\['stripe'\] \) \? array\( 'stripe' => \$gateways\['stripe'\] \) : array\(\)/],
  ['live gateway filter runs after existing restoration filters', /add_filter\( 'woocommerce_available_payment_gateways', 'bds_ai_live_subscription_gateways', 999 \)/],
  ['recurring engine accepts the installed WP Swings constant', /defined\( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION' \)[\s\S]*defined\( 'WPS_SFW_VERSION' \)[\s\S]*function_exists\( 'wps_sfw_get_meta_data' \)/],
  ['grants require recurring engine, server key, and enable flag', /'ready'\s*=>\s*\$recurring\s*&&\s*\$api_key\s*&&\s*\$enabled/],
  ['revocation remains available when grants are disabled', /return \$state\['recurring_engine'\]\s*&&\s*\$state\['formwise_key'\]/],
  ['provisioning requires active status and a paid order', /'active' !== \$status\s*\|\|\s*! \$order\s*\|\|\s*! \$order->is_paid\(\)/],
  ['zero-dollar trial authorization cannot grant a full paid plan', /wps_sfw_subscription_free_trial_number[\s\S]*wps_susbcription_trial_end[\s\S]*\$trial_end > current_time\( 'timestamp' \)[\s\S]*! \$is_renewal/],
  ['provisioning verifies the order belongs to the subscription', /bds_ai_order_matches_subscription\( \$order_id, \$subscription_id \)/],
  ['parent and renewal order relationships are allowlisted', /wps_parent_order[\s\S]*wps_sfw_renewal_order[\s\S]*wps_sfw_subscription/],
  ['provisioning sends an idempotency key', /'Idempotency-Key'\s*=>\s*substr\( \$event_key, 0, 64 \)/],
  ['grant idempotency is scoped to an access generation', /_bds_ai_access_generation[\s\S]*'grant\|'[\s\S]*\$generation/],
  ['provider requests do not follow redirects', (source.match(/'redirection'\s*=>\s*0/g) || []).length >= 2],
  ['AI Studio tiers are mutually exclusive', /bds_ai_formwise_tier_plan_ids[\s\S]*remote_plan_id[\s\S]*bds_ai_formwise_remove_tier/],
  ['generic renewal hook is connected', /add_action\( 'wps_sfw_after_renewal_payment', 'bds_ai_sfw_after_renewal_payment', 30, 2 \)/],
  ['failed renewal statuses are handled', /array\( 'failed', 'pending', 'wps_renewal' \)/],
  ['on-hold access is revoked', /array\( 'cancelled', 'expired', 'on-hold', 'paused' \)/],
  ['legacy status writes have a backstop', /add_action\( 'updated_post_meta', 'bds_ai_sfw_status_meta_updated', 40, 4 \)/],
  ['HPOS admin reactivation is reconciled', /add_action\( 'admin_init', 'bds_ai_capture_admin_reactivation', 1 \)/],
  ['reactivation requires capability and nonce checks', /current_user_can\( 'manage_woocommerce' \)[\s\S]*wp_verify_nonce/],
  ['remote deletion is allowlisted', /in_array\( \$plan_id, bds_ai_formwise_tier_plan_ids\(\), true \)/],
  ['missing remote plan counts as removed', /array\( 200, 204, 404 \)/],
  ['successful revocation advances generation and clears the grant key', /_bds_ai_access_generation', \$generation[\s\S]*_bds_ai_last_event_key', ''/],
  ['duplicate revocation callbacks stop at the removal marker', /'' !== \(string\) bds_ai_sfw_meta\( \$subscription_id, '_bds_ai_access_removed' \)/],
  ['grant and revocation share one subscription lock', (source.match(/\$lock_key = 'bds_ai_lock_' \. md5\( \(string\) \$subscription_id \);/g) || []).length >= 2],
  ['successful reactivation clears the removal marker', /_bds_ai_access_removed', ''/],
  ['each new verified paid order advances the allowance cycle once', /_bds_ai_allowance_order_id[\s\S]*\$allowance_order !== \$order_id[\s\S]*_bds_ai_allowance_cycle'[\s\S]*\+ 1/],
  ['same-cycle reactivation does not reset customer credits', /if \( \$allowance_order !== \$order_id \)/],
  ['existing idempotent grants migrate the allowance-order marker safely', /_bds_ai_last_event_key'[\s\S]*! absint\( bds_ai_sfw_meta\( \$subscription_id, '_bds_ai_allowance_order_id' \) \)[\s\S]*_bds_ai_allowance_order_id', \$order_id/],
  ['reconciliation uses the latest paid renewal and never a stale parent fallback', /wps_wsp_last_renewal_order_id[\s\S]*return \$order && \$order->is_paid[\s\S]*\? \$last_renewal : 0/],
  ['legacy active-status backstop also uses the latest authoritative paid order', /if \( 'active' === \$status \)[\s\S]*bds_ai_subscription_current_paid_order\( \$post_id \)[\s\S]*bds_ai_provision_active_subscription/],
  ['reconciliation is bounded to 25 records with five-minute continuation', /bds_ai_reconciliation_subscription_ids\( \$offset, 25 \)[\s\S]*5 \* MINUTE_IN_SECONDS[\s\S]*bds_ai_reconcile_subscriptions_continue/],
  ['reconciliation lock has stale recovery', /bds_ai_reconcile_lock[\s\S]*time\(\) - 600/],
  ['reconciliation remains disabled without recurring engine and provider key', /! \$state\['recurring_engine'\] \|\| ! \$state\['formwise_key'\]/],
  ['provider credential is never rendered into frontend markup', !/echo\s+[^;]*BDS_FORMWISE_PROVISIONING_KEY/.test(source)],
];

let failures = 0;
for (const [label, test] of checks) {
  const passed = test instanceof RegExp ? test.test(source) : test;
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} subscription lifecycle contract check(s) failed.`);
  process.exit(1);
}

console.log('\nAI subscription lifecycle contract passed.');
