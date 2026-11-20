<?php
/**
 * Plugin Name: BrandDad Lifecycle Events
 * Description: Idempotent, privacy-limited customer lifecycle and confirmed-revenue event ledger.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_LIFECYCLE_EVENTS_VERSION', '0.1.0' );

function bds_lifecycle_event_types() {
	return array( 'registered', 'trial_started', 'payment_confirmed', 'payment_refunded', 'access_revoked', 'ai_task_completed', 'directory_member_linked', 'onboarding_completed' );
}

function bds_lifecycle_register_type() {
	register_post_type(
		'bds_lifecycle_evt',
		array(
			'label' => 'Lifecycle events', 'public' => false,
			'show_ui' => current_user_can( 'manage_woocommerce' ), 'show_in_rest' => false,
			'supports' => array( 'author' ), 'capability_type' => 'post', 'map_meta_cap' => true,
		)
	);
}
add_action( 'init', 'bds_lifecycle_register_type' );

function bds_lifecycle_event_key( $type, $user_id, $object_type, $object_id, $idempotency ) {
	return hash_hmac( 'sha256', $type . '|' . absint( $user_id ) . '|' . sanitize_key( (string) $object_type ) . '|' . absint( $object_id ) . '|' . sanitize_text_field( (string) $idempotency ), wp_salt( 'auth' ) );
}

function bds_lifecycle_has_event( $type, $user_id, $object_type, $object_id, $idempotency ) {
	$key = bds_lifecycle_event_key( $type, $user_id, $object_type, $object_id, $idempotency );
	return (bool) get_posts( array( 'post_type' => 'bds_lifecycle_evt', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_bds_event_key', 'meta_value' => $key ) );
}

/** Internal-only recorder. No email, chat content, IP address, or provider body is stored. */
function bds_lifecycle_record( $type, $user_id, $object_type, $object_id, $data, $idempotency ) {
	$type = sanitize_key( (string) $type );
	if ( ! in_array( $type, bds_lifecycle_event_types(), true ) ) {
		return 0;
	}
	$user_id = absint( $user_id );
	$object_type = sanitize_key( (string) $object_type );
	$object_id = absint( $object_id );
	$idempotency = sanitize_text_field( (string) $idempotency );
	if ( strlen( $idempotency ) < 8 || strlen( $idempotency ) > 200 ) {
		return 0;
	}
	$event_key = bds_lifecycle_event_key( $type, $user_id, $object_type, $object_id, $idempotency );
	$lock_key  = 'bds_evt_lock_' . substr( $event_key, 0, 32 );
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		if ( absint( get_option( $lock_key, 0 ) ) < time() - 60 ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return 0;
		}
	}
	try {
		$existing = get_posts( array( 'post_type' => 'bds_lifecycle_evt', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_bds_event_key', 'meta_value' => $event_key ) );
		if ( $existing ) {
			return absint( $existing[0] );
		}
		$post_id = wp_insert_post( array( 'post_type' => 'bds_lifecycle_evt', 'post_status' => 'private', 'post_author' => $user_id, 'post_title' => 'BrandDad event ' . $type ), true );
		if ( is_wp_error( $post_id ) ) {
			return 0;
		}
		update_post_meta( $post_id, '_bds_event_key', $event_key );
		update_post_meta( $post_id, '_bds_event_type', $type );
		update_post_meta( $post_id, '_bds_event_user', $user_id );
		update_post_meta( $post_id, '_bds_event_object_type', $object_type );
		update_post_meta( $post_id, '_bds_event_object_id', $object_id );
		$data = is_array( $data ) ? $data : array();
		$currency = strtoupper( sanitize_text_field( (string) ( $data['currency'] ?? '' ) ) );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {
			$currency = '';
		}
		$allowed = array(
			'product_slug' => sanitize_key( (string) ( $data['product_slug'] ?? '' ) ),
			'stage' => sanitize_key( (string) ( $data['stage'] ?? '' ) ),
			'currency' => $currency,
			'amount_minor' => max( 0, absint( $data['amount_minor'] ?? 0 ) ),
			'tool' => sanitize_key( (string) ( $data['tool'] ?? '' ) ),
			'access_mode' => sanitize_key( (string) ( $data['access_mode'] ?? '' ) ),
		);
		foreach ( $allowed as $key => $value ) {
			if ( '' !== $value && 0 !== $value ) {
				update_post_meta( $post_id, '_bds_event_' . $key, $value );
			}
		}
		return absint( $post_id );
	} finally {
		delete_option( $lock_key );
	}
}

function bds_lifecycle_minor_units( $amount ) {
	$decimal = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $amount, 2 ) : number_format( (float) $amount, 2, '.', '' );
	return max( 0, (int) round( (float) $decimal * 100 ) );
}

function bds_lifecycle_user_registered( $user_id ) {
	bds_lifecycle_record( 'registered', $user_id, 'user', $user_id, array( 'stage' => 'registered' ), 'user-register-' . absint( $user_id ) );
}
add_action( 'user_register', 'bds_lifecycle_user_registered', 30, 1 );

function bds_lifecycle_trial_started( $user_id, $subscription_id ) {
	bds_lifecycle_record( 'trial_started', $user_id, 'subscription', $subscription_id, array( 'product_slug' => 'branddad-ai-business-essentials', 'stage' => 'trial_started' ), 'trial-' . absint( $subscription_id ) );
}
add_action( 'bds_ai_trial_activated', 'bds_lifecycle_trial_started', 10, 2 );

function bds_lifecycle_access_provisioned( $order_id, $subscription_id, $plan ) {
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order || ! $order->is_paid() ) {
		return;
	}
	$amount_minor = bds_lifecycle_minor_units( $order->get_total() );
	bds_lifecycle_record( 'payment_confirmed', $order->get_user_id(), 'order', $order->get_id(), array( 'product_slug' => sanitize_key( (string) ( $plan['slug'] ?? '' ) ), 'stage' => 'paid', 'currency' => $order->get_currency(), 'amount_minor' => $amount_minor ), 'paid-order-' . $order->get_id() );
}
add_action( 'bds_ai_access_provisioned', 'bds_lifecycle_access_provisioned', 10, 3 );

function bds_lifecycle_featured_provisioned( $order_id, $subscription_id, $context ) {
	$order = wc_get_order( absint( $order_id ) );
	$level = sanitize_key( (string) ( is_array( $context ) ? ( $context['level'] ?? '' ) : '' ) );
	if ( ! $order || ! $order->is_paid() || ! in_array( $level, array( 'local', 'regional', 'national', 'global' ), true ) ) {
		return;
	}
	bds_lifecycle_record(
		'payment_confirmed',
		$order->get_user_id(),
		'order',
		$order->get_id(),
		array(
			'product_slug' => 'directory-featured-' . $level,
			'stage'        => 'paid',
			'currency'     => $order->get_currency(),
			'amount_minor' => bds_lifecycle_minor_units( $order->get_total() ),
		),
		'paid-order-' . $order->get_id()
	);
}
add_action( 'bds_featured_access_provisioned', 'bds_lifecycle_featured_provisioned', 10, 3 );

/** Record each WooCommerce refund once; the original order and records remain intact. */
function bds_lifecycle_payment_refunded( $order_id, $refund_id ) {
	$order  = wc_get_order( absint( $order_id ) );
	$refund = wc_get_order( absint( $refund_id ) );
	if ( ! $order || ! $refund || ! is_a( $refund, 'WC_Order_Refund' ) ) {
		return;
	}
	if ( ! bds_lifecycle_has_event( 'payment_confirmed', $order->get_user_id(), 'order', $order->get_id(), 'paid-order-' . $order->get_id() ) ) {
		return;
	}
	$amount = method_exists( $refund, 'get_amount' ) ? $refund->get_amount() : abs( (float) $refund->get_total() );
	bds_lifecycle_record(
		'payment_refunded',
		$order->get_user_id(),
		'refund',
		$refund->get_id(),
		array(
			'stage'        => 'refunded',
			'currency'     => $order->get_currency(),
			'amount_minor' => bds_lifecycle_minor_units( $amount ),
		),
		'refund-' . $refund->get_id()
	);
}
add_action( 'woocommerce_order_refunded', 'bds_lifecycle_payment_refunded', 30, 2 );

function bds_lifecycle_access_revoked( $subscription_id, $status ) {
	$user_id = function_exists( 'bds_ai_sfw_meta' ) ? absint( bds_ai_sfw_meta( $subscription_id, 'wps_customer_id' ) ) : 0;
	bds_lifecycle_record( 'access_revoked', $user_id, 'subscription', $subscription_id, array( 'stage' => sanitize_key( $status ) ), 'revoke-' . absint( $subscription_id ) . '-' . sanitize_key( $status ) );
}
add_action( 'bds_ai_access_revoked', 'bds_lifecycle_access_revoked', 10, 2 );

function bds_lifecycle_ai_completed( $job_id, $user_id, $tool, $mode ) {
	bds_lifecycle_record( 'ai_task_completed', $user_id, 'ai_job', $job_id, array( 'tool' => $tool, 'access_mode' => $mode, 'stage' => 'active' ), 'ai-job-' . absint( $job_id ) );
}
add_action( 'bds_ai_job_completed', 'bds_lifecycle_ai_completed', 10, 4 );

function bds_lifecycle_directory_linked( $user_id, $event_id ) {
	bds_lifecycle_record( 'directory_member_linked', $user_id, 'user', $user_id, array( 'stage' => 'registered' ), 'directory-' . sanitize_text_field( $event_id ) );
}
add_action( 'bds_directory_member_linked', 'bds_lifecycle_directory_linked', 10, 2 );

function bds_lifecycle_onboarding_completed( $user_id, $version ) {
	bds_lifecycle_record( 'onboarding_completed', $user_id, 'user', $user_id, array( 'stage' => 'onboarding' ), 'onboarding-complete-' . absint( $user_id ) );
}
add_action( 'bds_onboarding_completed', 'bds_lifecycle_onboarding_completed', 10, 2 );

function bds_lifecycle_admin_permission() {
	return current_user_can( 'manage_woocommerce' );
}

function bds_lifecycle_period( $value ) {
	$value = sanitize_text_field( (string) $value );
	if ( ! preg_match( '/^(20\d{2})-(0[1-9]|1[0-2])$/', $value ) ) {
		return new WP_Error( 'invalid_period', 'Period must use YYYY-MM.', array( 'status' => 400 ) );
	}
	$requested = DateTimeImmutable::createFromFormat( '!Y-m', $value, new DateTimeZone( 'UTC' ) );
	$current   = new DateTimeImmutable( gmdate( 'Y-m-01' ), new DateTimeZone( 'UTC' ) );
	$oldest    = $current->modify( '-23 months' );
	if ( ! $requested || $requested < $oldest || $requested > $current ) {
		return new WP_Error( 'invalid_period', 'Period must be within the current 24-month reporting window.', array( 'status' => 400 ) );
	}
	return $value;
}

/** Admin aggregate contains no emails or raw event payloads. */
function bds_lifecycle_admin_summary( WP_REST_Request $request ) {
	$period = bds_lifecycle_period( $request->get_param( 'period' ) ?: gmdate( 'Y-m' ) );
	if ( is_wp_error( $period ) ) {
		return $period;
	}
	$start = $period . '-01 00:00:00';
	$end = gmdate( 'Y-m-t 23:59:59', strtotime( $start ) );
	$ids = get_posts( array( 'post_type' => 'bds_lifecycle_evt', 'post_status' => 'private', 'posts_per_page' => 5000, 'fields' => 'ids', 'date_query' => array( array( 'after' => $start, 'before' => $end, 'inclusive' => true, 'column' => 'post_date_gmt' ) ) ) );
	$counts = array_fill_keys( bds_lifecycle_event_types(), 0 );
	$customers = array();
	$gross = array();
	$refunds = array();
	foreach ( $ids as $event_id ) {
		$type = sanitize_key( (string) get_post_meta( $event_id, '_bds_event_type', true ) );
		if ( isset( $counts[ $type ] ) ) {
			$counts[ $type ]++;
		}
		$user_id = absint( get_post_meta( $event_id, '_bds_event_user', true ) );
		if ( $user_id ) {
			$customers[ $user_id ] = true;
		}
		if ( 'payment_confirmed' === $type ) {
			$currency = strtoupper( sanitize_text_field( (string) get_post_meta( $event_id, '_bds_event_currency', true ) ) );
			if ( preg_match( '/^[A-Z]{3}$/', $currency ) ) {
				$gross[ $currency ] = absint( $gross[ $currency ] ?? 0 ) + absint( get_post_meta( $event_id, '_bds_event_amount_minor', true ) );
			}
		}
		if ( 'payment_refunded' === $type ) {
			$currency = strtoupper( sanitize_text_field( (string) get_post_meta( $event_id, '_bds_event_currency', true ) ) );
			if ( preg_match( '/^[A-Z]{3}$/', $currency ) ) {
				$refunds[ $currency ] = absint( $refunds[ $currency ] ?? 0 ) + absint( get_post_meta( $event_id, '_bds_event_amount_minor', true ) );
			}
		}
	}
	$net = array();
	foreach ( array_unique( array_merge( array_keys( $gross ), array_keys( $refunds ) ) ) as $currency ) {
		$net[ $currency ] = absint( $gross[ $currency ] ?? 0 ) - absint( $refunds[ $currency ] ?? 0 );
	}
	return rest_ensure_response( array(
		'period' => $period, 'events' => $counts, 'unique_customers_with_events' => count( $customers ),
		'confirmed_gross_minor' => $gross, 'refunds_minor' => $refunds, 'net_confirmed_minor' => $net,
		'truncated' => count( $ids ) >= 5000,
		'note' => 'Amounts are confirmed WooCommerce transaction events less recorded refunds in this period. They are not MRR, profit, or a bank settlement report.',
	) );
}

function bds_lifecycle_register_routes() {
	register_rest_route( 'branddad/v1', '/admin/analytics/summary', array( 'methods' => 'GET', 'callback' => 'bds_lifecycle_admin_summary', 'permission_callback' => 'bds_lifecycle_admin_permission' ) );
}
add_action( 'rest_api_init', 'bds_lifecycle_register_routes' );
