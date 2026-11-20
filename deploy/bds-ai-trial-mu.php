<?php
/**
 * Plugin Name: BrandDad AI Trial
 * Description: Fail-closed seven-day AI Business Essentials trial lifecycle and customer status API.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_TRIAL_VERSION', '0.1.0' );
define( 'BDS_AI_TRIAL_PRODUCT_SLUG', 'branddad-ai-business-essentials' );
define( 'BDS_AI_TRIAL_CREDITS', 25 );

/** Require an authenticated cookie session and WordPress REST nonce. */
function bds_ai_trial_permission() {
	return is_user_logged_in();
}

/** Return subscription ids owned by one WordPress customer. */
function bds_ai_trial_subscriptions_for_user( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || ! function_exists( 'wps_sfw_get_meta_data' ) ) {
		return array();
	}
	$args = array(
		'limit'      => 100,
		'meta_query' => array(
			array( 'key' => 'wps_customer_id', 'value' => $user_id, 'compare' => '=' ),
		),
	);
	$hpos = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	if ( $hpos && function_exists( 'wc_get_orders' ) ) {
		$args['type']   = 'wps_subscriptions';
		$args['return'] = 'ids';
		$rows = wc_get_orders( $args );
		return array_values( array_filter( array_map( 'absint', is_array( $rows ) ? $rows : array() ) ) );
	}
	$args['post_type']      = 'wps_subscriptions';
	$args['post_status']    = 'any';
	$args['posts_per_page'] = 100;
	$args['fields']         = 'ids';
	unset( $args['limit'] );
	return array_values( array_filter( array_map( 'absint', get_posts( $args ) ) ) );
}

/** Resolve the parent order and require the exact approved trial product. */
function bds_ai_trial_order_for_subscription( $subscription_id ) {
	$parent_id = absint( wps_sfw_get_meta_data( absint( $subscription_id ), 'wps_parent_order', true ) );
	$order     = $parent_id && function_exists( 'wc_get_order' ) ? wc_get_order( $parent_id ) : false;
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		if ( BDS_AI_TRIAL_PRODUCT_SLUG === get_post_field( 'post_name', $product_id ) ) {
			return $order;
		}
	}
	return false;
}

/** Trial terms are copied to the subscription by WP Swings at checkout. */
function bds_ai_trial_terms_match( $subscription_id ) {
	$number   = absint( wps_sfw_get_meta_data( absint( $subscription_id ), 'wps_sfw_subscription_free_trial_number', true ) );
	$interval = sanitize_key( (string) wps_sfw_get_meta_data( absint( $subscription_id ), 'wps_sfw_subscription_free_trial_interval', true ) );
	return 7 === $number && 'day' === $interval;
}

/** Customer-safe trial state derived only from authoritative subscription records. */
function bds_ai_trial_state( $user_id ) {
	$user_id  = absint( $user_id );
	$now      = current_time( 'timestamp' );
	$history  = get_user_meta( $user_id, '_bds_ai_trial_history_v1', true );
	$history  = is_array( $history ) ? $history : array();
	$matched  = array();
	$active   = null;

	foreach ( bds_ai_trial_subscriptions_for_user( $user_id ) as $subscription_id ) {
		$order = bds_ai_trial_order_for_subscription( $subscription_id );
		if ( ! $order || ! bds_ai_trial_terms_match( $subscription_id ) ) {
			continue;
		}
		$status = sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true ) );
		$start  = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_schedule_start', true ) );
		$end    = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_susbcription_trial_end', true ) );
		$denied = 'yes' === (string) wps_sfw_get_meta_data( $subscription_id, '_bds_ai_trial_denied', true );
		$matched[] = $subscription_id;
		if ( ! $denied && 'active' === $status && $start > 0 && $end > $start && $now < $end ) {
			$active = array( 'subscription_id' => $subscription_id, 'start' => $start, 'end' => $end );
			break;
		}
	}

	$paid = function_exists( 'bds_ai_exec_active_paid_plan' ) ? bds_ai_exec_active_paid_plan( $user_id ) : array();
	if ( $paid ) {
		$status = 'paid';
	} elseif ( $active ) {
		$status = 'active';
	} elseif ( $history || $matched ) {
		$status = 'consumed';
	} else {
		$status = 'eligible';
	}

	return array(
		'status'         => $status,
		'eligible'       => 'eligible' === $status,
		'active'         => 'active' === $status,
		'trial_days'     => 7,
		'credits'        => BDS_AI_TRIAL_CREDITS,
		'started_at'     => $active ? date_i18n( DATE_ATOM, $active['start'] ) : '',
		'expires_at'     => $active ? date_i18n( DATE_ATOM, $active['end'] ) : '',
		'days_remaining' => $active ? max( 1, (int) ceil( ( $active['end'] - $now ) / DAY_IN_SECONDS ) ) : 0,
	);
}

/** Return the bounded trial allowance in the same shape as a paid execution plan. */
function bds_ai_trial_plan( $user_id ) {
	$state = bds_ai_trial_state( $user_id );
	if ( ! $state['active'] ) {
		return array();
	}
	foreach ( bds_ai_trial_subscriptions_for_user( $user_id ) as $subscription_id ) {
		if ( ! bds_ai_trial_order_for_subscription( $subscription_id ) || ! bds_ai_trial_terms_match( $subscription_id ) ) {
			continue;
		}
		$status = sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true ) );
		$end    = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_susbcription_trial_end', true ) );
		$denied = 'yes' === (string) wps_sfw_get_meta_data( $subscription_id, '_bds_ai_trial_denied', true );
		if ( ! $denied && 'active' === $status && $end > current_time( 'timestamp' ) ) {
			return array(
				'subscription_id' => absint( $subscription_id ),
				'slug'            => BDS_AI_TRIAL_PRODUCT_SLUG,
				'plan_id'         => 'trial-business-essentials',
				'credits'         => BDS_AI_TRIAL_CREDITS,
				'allowance_cycle' => 1,
				'access_mode'     => 'trial',
			);
		}
	}
	return array();
}

/** Persist one trial claim after WP Swings has activated the subscription. */
function bds_ai_trial_record_activation( $order_id, $subscription_id ) {
	$subscription_id = absint( $subscription_id );
	$order            = bds_ai_trial_order_for_subscription( $subscription_id );
	if ( ! $subscription_id || ! $order || absint( $order_id ) !== $order->get_id() || ! bds_ai_trial_terms_match( $subscription_id ) ) {
		return;
	}
	$user_id = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_customer_id', true ) );
	if ( ! $user_id || $user_id !== absint( $order->get_user_id() ) || 'active' !== sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true ) ) ) {
		return;
	}
	$history = get_user_meta( $user_id, '_bds_ai_trial_history_v1', true );
	if ( is_array( $history ) && ! empty( $history['subscription_id'] ) && absint( $history['subscription_id'] ) !== $subscription_id ) {
		wps_sfw_update_meta_data( $subscription_id, '_bds_ai_trial_denied', 'yes' );
		$order->add_order_note( 'Duplicate BrandDad AI trial detected; trial AI access was not granted.', false, false );
		return;
	}
	update_user_meta(
		$user_id,
		'_bds_ai_trial_history_v1',
		array(
			'subscription_id' => $subscription_id,
			'started_at'      => gmdate( DATE_ATOM ),
			'product_slug'    => BDS_AI_TRIAL_PRODUCT_SLUG,
		)
	);
}
add_action( 'wps_sfw_order_status_changed', 'bds_ai_trial_record_activation', 40, 2 );

/** One BrandDad account is required so access and cancellation remain customer-owned. */
function bds_ai_trial_registration_required( $required ) {
	return function_exists( 'bds_ai_cart_has_launch_subscription' ) && bds_ai_cart_has_launch_subscription() ? true : $required;
}
add_filter( 'woocommerce_checkout_registration_required', 'bds_ai_trial_registration_required', 20 );

/** Block another trial before a repeat customer can reach checkout. */
function bds_ai_trial_add_to_cart_validation( $valid, $product_id, $quantity, $variation_id = 0 ) {
	$target_id = $variation_id ? absint( $variation_id ) : absint( $product_id );
	$product   = $target_id && function_exists( 'wc_get_product' ) ? wc_get_product( $target_id ) : false;
	if ( ! $product ) {
		return $valid;
	}
	$base_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( BDS_AI_TRIAL_PRODUCT_SLUG !== get_post_field( 'post_name', $base_id ) || ! is_user_logged_in() ) {
		return $valid;
	}
	$state = bds_ai_trial_state( get_current_user_id() );
	if ( 'eligible' !== $state['status'] ) {
		wc_add_notice( __( 'This BrandDad account has already used or activated the AI Business Essentials trial.', 'branddad' ), 'error' );
		return false;
	}
	return $valid;
}
add_filter( 'woocommerce_add_to_cart_validation', 'bds_ai_trial_add_to_cart_validation', 50, 4 );

function bds_ai_trial_get() {
	return rest_ensure_response( bds_ai_trial_state( get_current_user_id() ) );
}

function bds_ai_trial_register_routes() {
	register_rest_route( 'branddad/v1', '/trial', array( 'methods' => 'GET', 'callback' => 'bds_ai_trial_get', 'permission_callback' => 'bds_ai_trial_permission' ) );
}
add_action( 'rest_api_init', 'bds_ai_trial_register_routes' );
