<?php
/**
 * Plugin Name: BrandDad Verified Provider Cost Ledger
 * Description: Append-only verified provider-cost records and privacy-limited contribution reporting for WooCommerce administrators.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_PROVIDER_COST_LEDGER_VERSION', '0.1.0' );

function bds_provider_cost_register_type() {
	register_post_type(
		'bds_provider_cost',
		array(
			'label' => 'Verified provider costs', 'public' => false,
			'show_ui' => current_user_can( 'manage_woocommerce' ), 'show_in_rest' => false,
			'supports' => array(), 'capability_type' => 'post', 'map_meta_cap' => true,
		)
	);
}
add_action( 'init', 'bds_provider_cost_register_type' );

function bds_provider_cost_allowed_providers() {
	$providers = array( 'formwise', 'stripe', 'woocommerce', 'hosting', 'email', 'whatsapp', 'social-publisher', 'seo-provider', 'video-renderer', 'music-fulfillment' );
	$providers = apply_filters( 'bds_provider_cost_allowed_providers', $providers );
	return array_values( array_unique( array_filter( array_map( 'sanitize_key', is_array( $providers ) ? $providers : array() ) ) ) );
}

function bds_provider_cost_allowed_bases() {
	return array( 'provider_reported', 'processor_reported', 'invoice_reconciled', 'contract_unit_reconciled' );
}

function bds_provider_cost_catalog_has( $product_slug ) {
	if ( ! function_exists( 'bds_ai_commercial_catalog' ) ) {
		return false;
	}
	$catalog = bds_ai_commercial_catalog();
	return is_array( $catalog ) && isset( $catalog[ $product_slug ] );
}

function bds_provider_cost_event_key( $provider, $product_slug, $currency, $amount_minor, $occurred_at, $idempotency ) {
	$material = implode( '|', array( $provider, $product_slug, $currency, absint( $amount_minor ), absint( $occurred_at ), $idempotency ) );
	return hash_hmac( 'sha256', $material, wp_salt( 'auth' ) );
}

/**
 * Record one verified provider cost. This is an internal server API, not a REST write route.
 *
 * Required keys: provider, product_slug, basis, currency, amount_minor,
 * occurred_at (Unix timestamp), idempotency.
 *
 * @param array<string,mixed> $event Verified adapter data.
 * @return int Stored post id, existing id for a replay, or 0 when rejected.
 */
function bds_provider_cost_record( $event ) {
	if ( ! is_array( $event ) ) {
		return 0;
	}
	$provider = sanitize_key( (string) ( $event['provider'] ?? '' ) );
	$product  = sanitize_key( (string) ( $event['product_slug'] ?? '' ) );
	$basis    = sanitize_key( (string) ( $event['basis'] ?? '' ) );
	$currency = strtoupper( sanitize_text_field( (string) ( $event['currency'] ?? '' ) ) );
	$amount_raw = $event['amount_minor'] ?? null;
	$occurred_raw = $event['occurred_at'] ?? null;
	$amount   = ( is_int( $amount_raw ) || ( is_string( $amount_raw ) && ctype_digit( $amount_raw ) ) ) ? absint( $amount_raw ) : 0;
	$occurred = ( is_int( $occurred_raw ) || ( is_string( $occurred_raw ) && ctype_digit( $occurred_raw ) ) ) ? absint( $occurred_raw ) : 0;
	$idem     = sanitize_text_field( (string) ( $event['idempotency'] ?? '' ) );

	if ( ! in_array( $provider, bds_provider_cost_allowed_providers(), true )
		|| ! bds_provider_cost_catalog_has( $product )
		|| ! in_array( $basis, bds_provider_cost_allowed_bases(), true )
		|| ! preg_match( '/^[A-Z]{3}$/', $currency )
		|| $amount < 1 || $amount > 100000000
		|| strlen( $idem ) < 8 || strlen( $idem ) > 200 ) {
		return 0;
	}
	$now = time();
	if ( $occurred < $now - ( 7 * YEAR_IN_SECONDS ) || $occurred > $now + 300 ) {
		return 0;
	}

	$event_key = bds_provider_cost_event_key( $provider, $product, $currency, $amount, $occurred, $idem );
	$lock_key  = 'bds_cost_lock_' . substr( $event_key, 0, 32 );
	if ( ! add_option( $lock_key, $now, '', false ) ) {
		if ( absint( get_option( $lock_key, 0 ) ) < $now - 60 ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, $now, '', false ) ) {
			return 0;
		}
	}

	try {
		$existing = get_posts( array( 'post_type' => 'bds_provider_cost', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_bds_cost_key', 'meta_value' => $event_key ) );
		if ( $existing ) {
			return absint( $existing[0] );
		}
		$gmt = gmdate( 'Y-m-d H:i:s', $occurred );
		$post_id = wp_insert_post(
			array(
				'post_type' => 'bds_provider_cost', 'post_status' => 'private',
				'post_title' => 'Verified provider cost ' . $provider,
				'post_date_gmt' => $gmt, 'post_date' => get_date_from_gmt( $gmt ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return 0;
		}
		update_post_meta( $post_id, '_bds_cost_key', $event_key );
		update_post_meta( $post_id, '_bds_cost_provider', $provider );
		update_post_meta( $post_id, '_bds_cost_product', $product );
		update_post_meta( $post_id, '_bds_cost_basis', $basis );
		update_post_meta( $post_id, '_bds_cost_currency', $currency );
		update_post_meta( $post_id, '_bds_cost_amount_minor', $amount );
		update_post_meta( $post_id, '_bds_cost_occurred_at', $occurred );
		update_post_meta( $post_id, '_bds_cost_reference_hash', hash_hmac( 'sha256', $idem, wp_salt( 'auth' ) ) );
		return absint( $post_id );
	} finally {
		delete_option( $lock_key );
	}
}

function bds_provider_cost_record_action( $event ) {
	bds_provider_cost_record( $event );
}
add_action( 'bds_verified_provider_cost', 'bds_provider_cost_record_action', 10, 1 );

function bds_provider_cost_admin_permission() {
	return current_user_can( 'manage_woocommerce' );
}

function bds_provider_cost_period( $value ) {
	if ( function_exists( 'bds_lifecycle_period' ) ) {
		return bds_lifecycle_period( $value );
	}
	$value = sanitize_text_field( (string) $value );
	if ( ! preg_match( '/^(20\d{2})-(0[1-9]|1[0-2])$/', $value ) ) {
		return new WP_Error( 'invalid_period', 'Period must use YYYY-MM.', array( 'status' => 400 ) );
	}
	$current = new DateTimeImmutable( gmdate( 'Y-m-01' ), new DateTimeZone( 'UTC' ) );
	$oldest  = $current->modify( '-23 months' );
	$date    = DateTimeImmutable::createFromFormat( '!Y-m', $value, new DateTimeZone( 'UTC' ) );
	return ( $date && $date >= $oldest && $date <= $current ) ? $value : new WP_Error( 'invalid_period', 'Period must be within the current 24-month reporting window.', array( 'status' => 400 ) );
}

function bds_provider_cost_add_amount( &$bucket, $key, $currency, $amount ) {
	if ( ! isset( $bucket[ $key ] ) ) {
		$bucket[ $key ] = array();
	}
	$bucket[ $key ][ $currency ] = absint( $bucket[ $key ][ $currency ] ?? 0 ) + absint( $amount );
}

/** Aggregate only verified stored costs and confirmed lifecycle revenue. */
function bds_provider_cost_admin_summary( WP_REST_Request $request ) {
	$period = bds_provider_cost_period( $request->get_param( 'period' ) ?: gmdate( 'Y-m' ) );
	if ( is_wp_error( $period ) ) {
		return $period;
	}
	$start = $period . '-01 00:00:00';
	$end   = gmdate( 'Y-m-t 23:59:59', strtotime( $start ) );
	$date_query = array( array( 'after' => $start, 'before' => $end, 'inclusive' => true, 'column' => 'post_date_gmt' ) );

	$cost_ids = get_posts( array( 'post_type' => 'bds_provider_cost', 'post_status' => 'private', 'posts_per_page' => 5000, 'fields' => 'ids', 'date_query' => $date_query ) );
	$costs = array();
	$cost_by_provider = array();
	$cost_by_product  = array();
	foreach ( $cost_ids as $cost_id ) {
		$currency = strtoupper( sanitize_text_field( (string) get_post_meta( $cost_id, '_bds_cost_currency', true ) ) );
		$provider = sanitize_key( (string) get_post_meta( $cost_id, '_bds_cost_provider', true ) );
		$product  = sanitize_key( (string) get_post_meta( $cost_id, '_bds_cost_product', true ) );
		$amount   = absint( get_post_meta( $cost_id, '_bds_cost_amount_minor', true ) );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) || ! in_array( $provider, bds_provider_cost_allowed_providers(), true ) || ! bds_provider_cost_catalog_has( $product ) || $amount < 1 ) {
			continue;
		}
		$costs[ $currency ] = absint( $costs[ $currency ] ?? 0 ) + $amount;
		bds_provider_cost_add_amount( $cost_by_provider, $provider, $currency, $amount );
		bds_provider_cost_add_amount( $cost_by_product, $product, $currency, $amount );
	}

	$event_ids = get_posts( array( 'post_type' => 'bds_lifecycle_evt', 'post_status' => 'private', 'posts_per_page' => 5000, 'fields' => 'ids', 'date_query' => $date_query ) );
	$gross = array();
	$refunds = array();
	$gross_by_product = array();
	foreach ( $event_ids as $event_id ) {
		$type = sanitize_key( (string) get_post_meta( $event_id, '_bds_event_type', true ) );
		if ( ! in_array( $type, array( 'payment_confirmed', 'payment_refunded' ), true ) ) {
			continue;
		}
		$currency = strtoupper( sanitize_text_field( (string) get_post_meta( $event_id, '_bds_event_currency', true ) ) );
		$amount   = absint( get_post_meta( $event_id, '_bds_event_amount_minor', true ) );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) || $amount < 1 ) {
			continue;
		}
		if ( 'payment_refunded' === $type ) {
			$refunds[ $currency ] = absint( $refunds[ $currency ] ?? 0 ) + $amount;
			continue;
		}
		$gross[ $currency ] = absint( $gross[ $currency ] ?? 0 ) + $amount;
		$product = sanitize_key( (string) get_post_meta( $event_id, '_bds_event_product_slug', true ) );
		if ( bds_provider_cost_catalog_has( $product ) ) {
			bds_provider_cost_add_amount( $gross_by_product, $product, $currency, $amount );
		}
	}

	$currencies = array_values( array_unique( array_merge( array_keys( $gross ), array_keys( $refunds ), array_keys( $costs ) ) ) );
	$summary = array();
	foreach ( $currencies as $currency ) {
		$gross_minor = absint( $gross[ $currency ] ?? 0 );
		$refund_minor = absint( $refunds[ $currency ] ?? 0 );
		$net_minor = $gross_minor - $refund_minor;
		$cost_minor = absint( $costs[ $currency ] ?? 0 );
		$contribution = $net_minor - $cost_minor;
		$summary[ $currency ] = array(
			'confirmed_gross_minor' => $gross_minor,
			'refunds_minor' => $refund_minor,
			'net_confirmed_minor' => $net_minor,
			'verified_variable_cost_minor' => $cost_minor,
			'recorded_contribution_minor' => $contribution,
			'recorded_contribution_margin_pct' => $net_minor > 0 ? round( ( $contribution / $net_minor ) * 100, 2 ) : null,
		);
	}

	return rest_ensure_response(
		array(
			'period' => $period,
			'currency_summary' => $summary,
			'verified_cost_by_provider' => $cost_by_provider,
			'verified_cost_by_product' => $cost_by_product,
			'confirmed_gross_by_product' => $gross_by_product,
			'verified_cost_entries' => count( $cost_ids ),
			'cost_coverage_state' => $cost_ids ? 'partial_unless_reconciled' : 'no_verified_costs',
			'truncated' => count( $cost_ids ) >= 5000 || count( $event_ids ) >= 5000,
			'note' => 'Recorded contribution uses confirmed WooCommerce revenue less recorded refunds and verified stored variable costs. It is not final profit, bank settlement, tax reporting, or proof that every provider, labor, affiliate, hosting, and payment cost has been reconciled.',
		)
	);
}

function bds_provider_cost_register_routes() {
	register_rest_route( 'branddad/v1', '/admin/finance/contribution', array( 'methods' => 'GET', 'callback' => 'bds_provider_cost_admin_summary', 'permission_callback' => 'bds_provider_cost_admin_permission' ) );
}
add_action( 'rest_api_init', 'bds_provider_cost_register_routes' );

