<?php
/**
 * Plugin Name: BrandDad AI Subscriptions (Staged)
 * Description: Draft AI pricing, onboarding guidance, and fail-closed entitlement readiness checks.
 * Version: 0.4.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_SUBSCRIPTIONS_VERSION', '0.4.1' );

/**
 * Approved offer configuration. Product ids are resolved by slug to preserve existing products.
 * FormWise allowances are native credits. Usage can vary by workflow.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_ai_subscription_catalog() {
	if ( function_exists( 'bds_ai_commercial_catalog' ) ) {
		$commercial = bds_ai_commercial_catalog();
		$catalog    = array();
		foreach ( $commercial as $slug => $offer ) {
			$catalog[ $slug ] = array(
				'name'             => $offer['name'],
				'monthly'          => $offer['monthly'],
				'setup'            => $offer['setup'] ?? '0',
				'credits'          => absint( $offer['customer_credits'] ?? 0 ),
				'audience'         => in_array( $offer['category'], array( 'ai_studio', 'flagship', 'specialized' ), true ) ? 'customer' : ( 'custom' === $offer['category'] ? 'custom' : 'installed' ),
				'formwise_plan_id' => $offer['formwise_plan_id'] ?? '',
				'launch_state'     => $offer['launch_state'],
			);
		}
		return $catalog;
	}
	return array(
		'branddad-ai-studio-starter' => array( 'name' => 'BrandDad AI — Starter', 'monthly' => '14.99', 'credits' => 50, 'audience' => 'customer', 'formwise_plan_id' => '6aeeb1bb-f28a-4577-a874-6d7b514fecb4' ),
		'branddad-ai-studio-growth'  => array( 'name' => 'BrandDad AI — Growth', 'monthly' => '29.99', 'credits' => 150, 'audience' => 'customer', 'formwise_plan_id' => '333b8f03-fa63-4afe-bb3f-dc530c4d4dc6' ),
		'branddad-ai-studio-pro'     => array( 'name' => 'BrandDad AI — Pro', 'monthly' => '49.99', 'credits' => 400, 'audience' => 'customer', 'formwise_plan_id' => 'd3bd5e69-63b6-4eb3-80fc-f1cce851e4db' ),
		'branddad-ai-sales-basic'    => array( 'name' => 'BrandDad AI Sales Assistant — Basic', 'monthly' => '39.00', 'setup' => '99.00', 'credits' => 0, 'audience' => 'installed' ),
		'branddad-ai-sales-plus'     => array( 'name' => 'BrandDad AI Sales Assistant — Plus', 'monthly' => '79.00', 'setup' => '199.00', 'credits' => 0, 'audience' => 'installed' ),
		'branddad-custom-automation' => array( 'name' => 'BrandDad Custom Business Automation', 'monthly' => '149.00', 'setup' => '499.00', 'credits' => 0, 'audience' => 'custom' ),
	);
}

/**
 * Fail-closed readiness. Access must never be granted from ordinary completed orders.
 *
 * @return array<string,bool>
 */
function bds_ai_subscription_readiness() {
	$recurring = ( defined( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION' ) || defined( 'WPS_SFW_VERSION' ) ) && function_exists( 'wps_sfw_get_meta_data' );
	$api_key   = defined( 'BDS_FORMWISE_PROVISIONING_KEY' ) && '' !== trim( (string) BDS_FORMWISE_PROVISIONING_KEY );
	$enabled   = defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ) && true === BDS_AI_ENTITLEMENTS_ENABLED;
	return array(
		'recurring_engine' => $recurring,
		'formwise_key'     => $api_key,
		'enabled'          => $enabled,
		'ready'            => $recurring && $api_key && $enabled,
	);
}

/**
 * Revocation must remain available when new grants are disabled.
 *
 * @return bool
 */
function bds_ai_subscription_revocation_ready() {
	$state = bds_ai_subscription_readiness();
	return $state['recurring_engine'] && $state['formwise_key'];
}

/**
 * Return the exact FormWise AI Studio tiers that must remain mutually exclusive.
 *
 * @return string[]
 */
function bds_ai_formwise_tier_plan_ids() {
	$plan_ids = array();
	foreach ( bds_ai_subscription_catalog() as $plan ) {
		$plan_id = sanitize_text_field( (string) ( $plan['formwise_plan_id'] ?? '' ) );
		if ( wp_is_uuid( $plan_id ) ) {
			$plan_ids[] = $plan_id;
		}
	}
	return array_values( array_unique( $plan_ids ) );
}

/**
 * Remove one allowlisted FormWise tier. A missing remote tier is already removed.
 *
 * @param string $user_id FormWise end-user UUID.
 * @param string $plan_id FormWise tier-plan UUID.
 * @return bool
 */
function bds_ai_formwise_remove_tier( $user_id, $plan_id ) {
	$user_id = sanitize_text_field( (string) $user_id );
	$plan_id = sanitize_text_field( (string) $plan_id );
	if ( ! bds_ai_subscription_revocation_ready() || ! wp_is_uuid( $user_id ) || ! wp_is_uuid( $plan_id ) || ! in_array( $plan_id, bds_ai_formwise_tier_plan_ids(), true ) ) {
		return false;
	}

	$response = wp_remote_request(
		'https://builder.formwise.ai/api/v1/end-users/' . rawurlencode( $user_id ) . '/tier-plans/' . rawurlencode( $plan_id ),
		array(
			'method'              => 'DELETE',
			'timeout'             => 15,
			'redirection'         => 0,
			'limit_response_size' => 16384,
			'headers'             => array( 'Authorization' => 'Bearer ' . trim( (string) BDS_FORMWISE_PROVISIONING_KEY ) ),
		)
	);
	$code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
	return in_array( $code, array( 200, 204, 404 ), true );
}

/**
 * Admin notice keeps an accidental half-launch visible and fail-closed.
 */
function bds_ai_subscription_admin_notice() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$state = bds_ai_subscription_readiness();
	if ( $state['ready'] ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'BrandDad AI subscriptions are staged only. Automatic customer access is disabled until recurring billing, the secure AI provisioning credential, and the explicit enable flag are all present.', 'branddad' ) . '</p></div>';
}
add_action( 'admin_notices', 'bds_ai_subscription_admin_notice' );

/**
 * Private preview pricing comparison. It does not expose purchase links while staged.
 *
 * @return string
 */
function bds_ai_pricing_shortcode() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	$plans = bds_ai_subscription_catalog();
	ob_start();
	?>
	<section class="bds-ai-pricing" aria-labelledby="bds-ai-pricing-title">
		<h2 id="bds-ai-pricing-title"><?php esc_html_e( 'BrandDad AI plans — private preview', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'Draft pricing for review. Billing and public checkout are not active.', 'branddad' ); ?></p>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
		<?php foreach ( $plans as $slug => $plan ) : ?>
			<article style="border:1px solid #d9d9e3;border-radius:16px;padding:1.25rem;background:#fff">
				<h3><?php echo esc_html( $plan['name'] ); ?></h3>
				<?php if ( ! empty( $plan['setup'] ) ) : ?><p><strong><?php echo esc_html( '$' . $plan['setup'] ); ?></strong> <?php esc_html_e( 'setup', 'branddad' ); ?></p><?php endif; ?>
				<p><strong><?php echo esc_html( '$' . $plan['monthly'] ); ?></strong> <?php esc_html_e( '/ month', 'branddad' ); ?></p>
				<?php if ( ! empty( $plan['credits'] ) ) : ?><p><?php echo esc_html( sprintf( __( '%d BrandDad AI credits per month', 'branddad' ), $plan['credits'] ) ); ?></p><?php endif; ?>
				<p><code><?php echo esc_html( $slug ); ?></code></p>
			</article>
		<?php endforeach; ?>
		</div>
		<p><?php esc_html_e( 'Credit usage varies by AI tool.', 'branddad' ); ?></p>
		<p><?php esc_html_e( 'AI Sales Assistant and automation scopes are bounded. Additional workflows, integrations, and high-volume usage require a custom quote.', 'branddad' ); ?></p>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'bds_ai_pricing_preview', 'bds_ai_pricing_shortcode' );

/**
 * Store explicit non-secret configuration on draft products for later reconciliation.
 */
function bds_ai_subscription_product_meta() {
	foreach ( bds_ai_subscription_catalog() as $slug => $plan ) {
		$product = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $product || 'draft' !== $product->post_status ) {
			continue;
		}
		update_post_meta( $product->ID, '_bds_ai_offer', 'yes' );
		update_post_meta( $product->ID, '_bds_ai_monthly_price', wc_format_decimal( $plan['monthly'] ) );
		update_post_meta( $product->ID, '_bds_ai_setup_fee', wc_format_decimal( $plan['setup'] ?? '0' ) );
		update_post_meta( $product->ID, '_bds_ai_formwise_credits', absint( $plan['credits'] ) );
		update_post_meta( $product->ID, '_bds_ai_audience', sanitize_key( $plan['audience'] ) );
	}
}
add_action( 'admin_init', 'bds_ai_subscription_product_meta' );

/**
 * Resolve an allowlisted customer plan from an authoritative Woo order.
 *
 * @param WC_Order $order WooCommerce order.
 * @return array<string,mixed>|null
 */
function bds_ai_plan_from_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return null;
	}
	$catalog = bds_ai_subscription_catalog();
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$parent_id = $product->get_parent_id();
		$slug      = get_post_field( 'post_name', $parent_id ? $parent_id : $product->get_id() );
		if ( isset( $catalog[ $slug ] ) && 'customer' === $catalog[ $slug ]['audience'] ) {
			return array_merge( $catalog[ $slug ], array( 'slug' => $slug ) );
		}
	}
	return null;
}

/**
 * Read WP Swings metadata through its HPOS-compatible accessor.
 *
 * @param int    $object_id Object id.
 * @param string $key       Metadata key.
 * @return mixed
 */
function bds_ai_sfw_meta( $object_id, $key ) {
	return function_exists( 'wps_sfw_get_meta_data' ) ? wps_sfw_get_meta_data( absint( $object_id ), $key, true ) : '';
}

/**
 * Confirm that the paid order is the parent order or an authoritative renewal
 * order for the subscription being provisioned. This keeps an internal hook
 * mix-up from granting one customer's plan to another order's email address.
 *
 * @param int $order_id        Paid parent/renewal order id.
 * @param int $subscription_id WP Swings subscription id.
 * @return bool
 */
function bds_ai_order_matches_subscription( $order_id, $subscription_id ) {
	$order_id        = absint( $order_id );
	$subscription_id = absint( $subscription_id );
	if ( ! $order_id || ! $subscription_id ) {
		return false;
	}

	$parent_order_id = absint( bds_ai_sfw_meta( $subscription_id, 'wps_parent_order' ) );
	if ( $parent_order_id && $order_id === $parent_order_id ) {
		return true;
	}

	$is_renewal              = 'yes' === (string) bds_ai_sfw_meta( $order_id, 'wps_sfw_renewal_order' );
	$renewal_subscription_id = absint( bds_ai_sfw_meta( $order_id, 'wps_sfw_subscription' ) );
	return $is_renewal && $subscription_id === $renewal_subscription_id;
}

/** Schedule a bounded provisioning retry for transient network/API failures. */
function bds_ai_schedule_provision_retry( $order_id, $subscription_id ) {
	$attempts = absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_provision_attempts' ) );
	if ( $attempts >= 3 ) {
		return;
	}
	wps_sfw_update_meta_data( $subscription_id, '_bds_ai_provision_attempts', $attempts + 1 );
	$args = array( absint( $order_id ), absint( $subscription_id ) );
	if ( ! wp_next_scheduled( 'bds_ai_retry_provision', $args ) ) {
		wp_schedule_single_event( time() + ( 15 * MINUTE_IN_SECONDS ), 'bds_ai_retry_provision', $args );
	}
}
add_action( 'bds_ai_retry_provision', 'bds_ai_provision_active_subscription', 10, 2 );

/**
 * Provision an active, paid WP Swings subscription in FormWise.
 *
 * @param int $order_id        Paid parent/renewal order id.
 * @param int $subscription_id WP Swings subscription id.
 * @return bool
 */
function bds_ai_provision_active_subscription( $order_id, $subscription_id ) {
	$state           = bds_ai_subscription_readiness();
	$order_id        = absint( $order_id );
	$subscription_id = absint( $subscription_id );
	if ( ! $state['ready'] || ! $order_id || ! $subscription_id ) {
		return false;
	}

	$status = sanitize_key( (string) bds_ai_sfw_meta( $subscription_id, 'wps_subscription_status' ) );
	$order  = wc_get_order( $order_id );
	if ( 'active' !== $status || ! $order || ! $order->is_paid() || ! bds_ai_order_matches_subscription( $order_id, $subscription_id ) ) {
		return false;
	}

	$plan  = bds_ai_plan_from_order( $order );
	$email = sanitize_email( $order->get_billing_email() );
	if ( ! $plan || ! is_email( $email ) || empty( $plan['formwise_plan_id'] ) ) {
		return false;
	}

	$generation = absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_generation' ) );
	$event_key  = hash( 'sha256', 'grant|' . $subscription_id . '|' . $order_id . '|' . $plan['formwise_plan_id'] . '|' . $generation );
	if ( hash_equals( (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_last_event_key' ), $event_key ) ) {
		return true;
	}

	$lock_key = 'bds_ai_lock_' . md5( (string) $subscription_id );
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		return false;
	}

	try {
		$response = wp_remote_post(
			'https://builder.formwise.ai/api/v1/end-users',
			array(
				'timeout'             => 15,
				'redirection'         => 0,
				'limit_response_size' => 65536,
				'headers' => array(
					'Authorization'   => 'Bearer ' . trim( (string) BDS_FORMWISE_PROVISIONING_KEY ),
					'Content-Type'    => 'application/json',
					'Idempotency-Key' => substr( $event_key, 0, 64 ),
				),
				'body'    => wp_json_encode(
					array(
						'email'       => strtolower( $email ),
						'tierPlanIds' => array( $plan['formwise_plan_id'] ),
						'sendEmail'   => '' === (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_formwise_user_id' ),
					)
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			bds_ai_schedule_provision_retry( $order_id, $subscription_id );
			return false;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! in_array( $code, array( 200, 201 ), true ) || empty( $data['endUser']['id'] ) || strtolower( (string) $data['endUser']['email'] ) !== strtolower( $email ) ) {
			bds_ai_schedule_provision_retry( $order_id, $subscription_id );
			return false;
		}

		$has_plan = false;
		foreach ( (array) ( $data['subscriptions'] ?? array() ) as $subscription ) {
			if ( isset( $subscription['tierPlanId'] ) && hash_equals( $plan['formwise_plan_id'], (string) $subscription['tierPlanId'] ) ) {
				$has_plan = true;
				break;
			}
		}
		if ( ! $has_plan ) {
			bds_ai_schedule_provision_retry( $order_id, $subscription_id );
			return false;
		}

		// An upgrade or downgrade replaces the prior AI Studio tier. It must not
		// silently stack customer credits from multiple mutually exclusive plans.
		$known_tiers = bds_ai_formwise_tier_plan_ids();
		foreach ( (array) ( $data['subscriptions'] ?? array() ) as $remote_subscription ) {
			$remote_plan_id = sanitize_text_field( (string) ( $remote_subscription['tierPlanId'] ?? '' ) );
			if ( $remote_plan_id && $remote_plan_id !== $plan['formwise_plan_id'] && in_array( $remote_plan_id, $known_tiers, true ) ) {
				if ( ! bds_ai_formwise_remove_tier( $data['endUser']['id'], $remote_plan_id ) ) {
					bds_ai_schedule_provision_retry( $order_id, $subscription_id );
					return false;
				}
			}
		}

		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_last_event_key', $event_key );
		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_formwise_user_id', sanitize_text_field( $data['endUser']['id'] ) );
		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_formwise_plan_id', sanitize_text_field( $plan['formwise_plan_id'] ) );
		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_provision_attempts', 0 );
		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_cancel_attempts', 0 );
		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_access_removed', '' );
		$order->add_order_note( sprintf( 'BrandDad AI access provisioned (%s).', sanitize_text_field( $plan['slug'] ) ), false, false );
		return true;
	} finally {
		delete_option( $lock_key );
	}
}

/** Initial activation/reactivation after WP Swings has persisted active state. */
function bds_ai_sfw_order_active( $order_id, $subscription_id ) {
	bds_ai_provision_active_subscription( $order_id, $subscription_id );
}
add_action( 'wps_sfw_order_status_changed', 'bds_ai_sfw_order_active', 30, 2 );

/** Successful gateway renewal. */
function bds_ai_sfw_renewal_success( $renewal_order_id ) {
	$subscription_id = absint( bds_ai_sfw_meta( $renewal_order_id, 'wps_sfw_subscription' ) );
	if ( $subscription_id ) {
		bds_ai_provision_active_subscription( $renewal_order_id, $subscription_id );
	}
}
add_action( 'wps_sfw_recurring_payment_success', 'bds_ai_sfw_renewal_success', 30, 1 );

/** Successful renewal through the scheduler path used by WP Swings 2.1.1. */
function bds_ai_sfw_after_renewal_payment( $renewal_order, $subscription_id ) {
	if ( $renewal_order instanceof WC_Order ) {
		bds_ai_provision_active_subscription( $renewal_order->get_id(), absint( $subscription_id ) );
	}
}
add_action( 'wps_sfw_after_renewal_payment', 'bds_ai_sfw_after_renewal_payment', 30, 2 );

/**
 * Remove only the exact FormWise plan previously granted for this subscription.
 *
 * @param int $subscription_id WP Swings subscription id.
 */
function bds_ai_sfw_cancel_access( $subscription_id ) {
	$subscription_id = absint( $subscription_id );
	if ( ! bds_ai_subscription_revocation_ready() || ! $subscription_id ) {
		return;
	}
	$status  = sanitize_key( (string) bds_ai_sfw_meta( $subscription_id, 'wps_subscription_status' ) );
	$user_id = sanitize_text_field( (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_formwise_user_id' ) );
	$plan_id = sanitize_text_field( (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_formwise_plan_id' ) );
	if ( ! in_array( $status, array( 'cancelled', 'expired', 'on-hold', 'paused' ), true ) || ! wp_is_uuid( $user_id ) || ! wp_is_uuid( $plan_id ) ) {
		return;
	}
	if ( '' !== (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_removed' ) ) {
		return;
	}

	$lock_key = 'bds_ai_lock_' . md5( (string) $subscription_id );
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		if ( ! wp_next_scheduled( 'bds_ai_retry_cancel', array( $subscription_id ) ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bds_ai_retry_cancel', array( $subscription_id ) );
		}
		return;
	}

	try {
		if ( '' !== (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_removed' ) ) {
			return;
		}
		if ( bds_ai_formwise_remove_tier( $user_id, $plan_id ) ) {
			$generation = absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_generation' ) ) + 1;
			wps_sfw_update_meta_data( $subscription_id, '_bds_ai_access_generation', $generation );
			wps_sfw_update_meta_data( $subscription_id, '_bds_ai_last_event_key', '' );
			wps_sfw_update_meta_data( $subscription_id, '_bds_ai_cancel_attempts', 0 );
			wps_sfw_update_meta_data( $subscription_id, '_bds_ai_access_removed', gmdate( 'c' ) );
			return;
		}
		$attempts = absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_cancel_attempts' ) );
		if ( $attempts < 3 ) {
			wps_sfw_update_meta_data( $subscription_id, '_bds_ai_cancel_attempts', $attempts + 1 );
			if ( ! wp_next_scheduled( 'bds_ai_retry_cancel', array( $subscription_id ) ) ) {
				wp_schedule_single_event( time() + ( 15 * MINUTE_IN_SECONDS ), 'bds_ai_retry_cancel', array( $subscription_id ) );
			}
		}
	} finally {
		delete_option( $lock_key );
	}
}
add_action( 'wps_sfw_subscription_cancel', 'bds_ai_sfw_cancel_access', 30, 1 );
add_action( 'wps_sfw_expire_subscription_scheduler', 'bds_ai_sfw_cancel_access', 30, 1 );
add_action( 'bds_ai_retry_cancel', 'bds_ai_sfw_cancel_access', 10, 1 );

/**
 * WP Swings 2.1.1 changes a subscription to on-hold after a renewal order
 * becomes failed or pending, but does not emit its cancellation hook.
 */
function bds_ai_sfw_renewal_order_status_changed( $order_id, $old_status, $new_status, $order ) {
	if ( ! in_array( sanitize_key( (string) $new_status ), array( 'failed', 'pending', 'wps_renewal' ), true ) ) {
		return;
	}
	if ( ! $order instanceof WC_Order || 'yes' !== (string) bds_ai_sfw_meta( $order_id, 'wps_sfw_renewal_order' ) ) {
		return;
	}
	$subscription_id = absint( bds_ai_sfw_meta( $order_id, 'wps_sfw_subscription' ) );
	if ( $subscription_id ) {
		bds_ai_sfw_cancel_access( $subscription_id );
	}
}
add_action( 'woocommerce_order_status_changed', 'bds_ai_sfw_renewal_order_status_changed', 40, 4 );

/**
 * Legacy-storage backstop for every authoritative subscription status write.
 * HPOS activation, cancellation, expiry, and failed-renewal paths are covered
 * by the dedicated hooks above.
 */
function bds_ai_sfw_status_meta_updated( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( 'wps_subscription_status' !== $meta_key || 'wps_subscriptions' !== get_post_type( $post_id ) ) {
		return;
	}
	$status = sanitize_key( (string) $meta_value );
	if ( 'active' === $status ) {
		$order_id = absint( bds_ai_sfw_meta( $post_id, 'wps_parent_order' ) );
		if ( $order_id ) {
			bds_ai_provision_active_subscription( $order_id, $post_id );
		}
		return;
	}
	if ( in_array( $status, array( 'cancelled', 'expired', 'on-hold', 'paused' ), true ) ) {
		bds_ai_sfw_cancel_access( $post_id );
	}
}
add_action( 'updated_post_meta', 'bds_ai_sfw_status_meta_updated', 40, 4 );

/** Reconcile one subscription after a verified admin reactivation redirect. */
function bds_ai_reconcile_one_subscription( $subscription_id ) {
	$subscription_id = absint( $subscription_id );
	$status          = sanitize_key( (string) bds_ai_sfw_meta( $subscription_id, 'wps_subscription_status' ) );
	if ( 'active' === $status ) {
		$order_id = absint( bds_ai_sfw_meta( $subscription_id, 'wps_parent_order' ) );
		if ( $order_id ) {
			bds_ai_provision_active_subscription( $order_id, $subscription_id );
		}
		return;
	}
	bds_ai_sfw_cancel_access( $subscription_id );
}
add_action( 'bds_ai_reconcile_one_subscription', 'bds_ai_reconcile_one_subscription', 10, 1 );

/**
 * WP Swings performs HPOS admin reactivation with a direct meta write and an
 * immediate redirect. Capture the same capability- and nonce-checked action,
 * then reconcile after the plugin has completed its update.
 */
function bds_ai_capture_admin_reactivation() {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_GET['wps_subscription_status_admin_reactivate'], $_GET['wps_subscription_id'], $_GET['wps_sfw_pause_nonce'] ) ) {
		return;
	}
	$status          = sanitize_key( wp_unslash( $_GET['wps_subscription_status_admin_reactivate'] ) );
	$subscription_id = absint( wp_unslash( $_GET['wps_subscription_id'] ) );
	$nonce           = sanitize_text_field( wp_unslash( $_GET['wps_sfw_pause_nonce'] ) );
	if ( 'on-hold' !== $status || ! $subscription_id || ! wp_verify_nonce( $nonce, (string) $subscription_id . $status ) ) {
		return;
	}
	$args = array( $subscription_id );
	if ( ! wp_next_scheduled( 'bds_ai_reconcile_one_subscription', $args ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bds_ai_reconcile_one_subscription', $args );
	}
}
add_action( 'admin_init', 'bds_ai_capture_admin_reactivation', 1 );

