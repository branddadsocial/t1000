<?php
/**
 * Plugin Name: BrandDad Staging Featured Billing Acceptance
 * Description: One-time, no-gateway acceptance of the production Directory Featured billing adapter.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_FEATURED_ACCEPTANCE_VERSION', '0.1.0' );
define( 'BDS_STAGING_FEATURED_ACCEPTANCE_REPORT', 'bds_staging_featured_billing_report_v1' );
define( 'BDS_STAGING_FEATURED_ACCEPTANCE_MARKER', 'bds-stage-featured-billing-v1' );

function bds_staging_featured_acceptance_is_host() {
	return 'staging-branddad-social.stackstaging.com' === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_featured_acceptance_pending() {
	return bds_staging_featured_acceptance_is_host() && ! get_option( BDS_STAGING_FEATURED_ACCEPTANCE_REPORT, false );
}

$GLOBALS['bds_staging_featured_acceptance'] = array(
	'active'     => false,
	'listing_id' => 0,
	'owner_id'   => 0,
	'capacity'   => true,
);

/* Request-scoped Directory ownership seam for isolated Social staging only. */
if ( bds_staging_featured_acceptance_pending() && ! function_exists( 'bds_dir_free_ai_listing_eligible' ) ) {
	define( 'BDS_STAGING_FEATURED_OWNSHIP_SEAM', true );
	function bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) {
		$state = $GLOBALS['bds_staging_featured_acceptance'];
		return ! empty( $state['active'] )
			&& absint( $listing_id ) === absint( $state['listing_id'] )
			&& absint( $user_id ) === absint( $state['owner_id'] )
			&& BDS_STAGING_FEATURED_ACCEPTANCE_MARKER === (string) get_post_meta( absint( $listing_id ), '_bds_staging_featured_acceptance', true );
	}
}

/* Isolated grant store used only when Cursor's Directory inventory is absent. */
if ( bds_staging_featured_acceptance_pending() && ! function_exists( 'bds_feat_grants' ) ) {
	define( 'BDS_STAGING_FEATURED_GRANT_SEAM', true );
	function bds_feat_grants() {
		$value = get_option( 'bds_staging_featured_acceptance_grants_v1', array() );
		return is_array( $value ) ? $value : array();
	}
	function bds_feat_save_grants( $grants ) {
		update_option( 'bds_staging_featured_acceptance_grants_v1', array_values( is_array( $grants ) ? $grants : array() ), false );
	}
	function bds_feat_has_capacity( $grants, $level ) {
		unset( $grants, $level );
		return ! empty( $GLOBALS['bds_staging_featured_acceptance']['capacity'] );
	}
}

function bds_staging_featured_acceptance_geo( $geo, $listing_id ) {
	$state = $GLOBALS['bds_staging_featured_acceptance'];
	if ( ! empty( $state['active'] ) && absint( $listing_id ) === absint( $state['listing_id'] ) ) {
		return array( 'country' => 'MX', 'lat' => 20.6296, 'lng' => -87.0739 );
	}
	return $geo;
}
add_filter( 'bds_featured_listing_geo', 'bds_staging_featured_acceptance_geo', 1, 2 );

function bds_staging_featured_acceptance_save_report( $complete, $checks, $errors ) {
	$allowed = array(
		'exact_staging_host', 'production_dependencies_loaded', 'isolated_seams_active', 'fixture_owner_verified',
		'no_preexisting_marked_records', 'fixture_listing_created', 'four_products_created', 'four_products_ready',
		'levels_and_prices_exact', 'wrong_price_rejected', 'wrong_interval_rejected', 'ownership_isolated',
		'geography_enforced', 'synthetic_parent_paid', 'subscription_created', 'activation_created_one_grant',
		'replay_idempotent', 'renewal_extended_same_grant', 'failed_renewal_paused', 'cancellation_honors_paid_through',
		'expiry_preserves_record', 'refund_revokes', 'capacity_failure_no_grant', 'no_real_gateway',
		'fixture_records_cleaned', 'fixture_products_cleaned', 'isolated_state_restored', 'original_context_restored',
	);
	$safe_checks = array();
	foreach ( $allowed as $key ) {
		$safe_checks[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 12 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option(
		BDS_STAGING_FEATURED_ACCEPTANCE_REPORT,
		array(
			'version'      => BDS_STAGING_FEATURED_ACCEPTANCE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_featured_acceptance_product( $sku, $price ) {
	$existing_id = wc_get_product_id_by_sku( $sku );
	if ( $existing_id ) {
		if ( BDS_STAGING_FEATURED_ACCEPTANCE_MARKER !== (string) get_post_meta( $existing_id, '_bds_staging_featured_acceptance', true ) ) {
			return new WP_Error( 'featured_sku_collision' );
		}
		return wc_get_product( $existing_id );
	}
	$product = new WC_Product_Simple();
	$product->set_name( 'Staging Featured ' . ucfirst( str_replace( 'bds-feat-', '', $sku ) ) );
	$product->set_slug( 'staging-' . $sku . '-acceptance' );
	$product->set_sku( $sku );
	$product->set_status( 'private' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_virtual( true );
	$product->set_sold_individually( true );
	$product->set_regular_price( $price );
	$product->set_price( $price );
	$product_id = $product->save();
	if ( ! $product_id ) {
		return new WP_Error( 'featured_product_create_failed' );
	}
	update_post_meta( $product_id, '_bds_staging_featured_acceptance', BDS_STAGING_FEATURED_ACCEPTANCE_MARKER );
	wps_sfw_update_meta_data( $product_id, '_wps_sfw_product', 'yes' );
	wps_sfw_update_meta_data( $product_id, 'wps_sfw_subscription_number', 1 );
	wps_sfw_update_meta_data( $product_id, 'wps_sfw_subscription_interval', 'month' );
	return wc_get_product( $product_id );
}

function bds_staging_featured_acceptance_order( $user_id, $product, $listing_id, $kind, $status = 'completed' ) {
	$order = wc_create_order(
		array(
			'customer_id' => absint( $user_id ),
			'status'      => sanitize_key( $status ),
			'created_via' => 'bds_staging_featured_acceptance',
		)
	);
	if ( is_wp_error( $order ) || ! $order instanceof WC_Order ) {
		return new WP_Error( 'featured_order_create_failed' );
	}
	$order->set_currency( 'USD' );
	$order->set_payment_method( 'bds_staging_synthetic_no_gateway' );
	$order->set_payment_method_title( 'Staging synthetic — no gateway or charge' );
	$item_id = $order->add_product( $product, 1 );
	$item    = $order->get_item( $item_id );
	if ( ! $item instanceof WC_Order_Item_Product ) {
		$order->delete( true );
		return new WP_Error( 'featured_order_item_failed' );
	}
	$item->add_meta_data( '_bds_feat_listing_id', absint( $listing_id ), true );
	$item->add_meta_data( '_bds_feat_owner_id', absint( $user_id ), true );
	$item->save();
	$order->calculate_totals( false );
	if ( in_array( sanitize_key( $status ), array( 'processing', 'completed' ), true ) ) {
		$order->set_date_paid( time() );
	}
	$order->update_meta_data( '_bds_staging_featured_acceptance', BDS_STAGING_FEATURED_ACCEPTANCE_MARKER );
	$order->update_meta_data( '_bds_staging_featured_kind', sanitize_key( $kind ) );
	$order->save();
	return $order;
}

function bds_staging_featured_acceptance_mark_renewal( $order_id, $subscription_id ) {
	wps_sfw_update_meta_data( absint( $order_id ), 'wps_sfw_renewal_order', 'yes' );
	wps_sfw_update_meta_data( absint( $order_id ), 'wps_sfw_subscription', absint( $subscription_id ) );
}

function bds_staging_featured_acceptance_run() {
	if ( ! bds_staging_featured_acceptance_pending() ) {
		return;
	}
	$lock = 'bds_staging_featured_acceptance_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 600 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}

	$checks              = array();
	$errors              = array();
	$created_order_ids   = array();
	$created_product_ids = array();
	$subscription_ids    = array();
	$listing_id          = 0;
	$owner_id            = 0;
	$original_user       = get_current_user_id();
	$original_post       = $_POST;
	$original_get        = $_GET;
	$grants_option       = 'bds_staging_featured_acceptance_grants_v1';
	$grants_existed      = get_option( $grants_option, null ) !== null;
	$grants_before       = get_option( $grants_option, array() );
	$records_clean       = true;
	$products_clean      = true;
	$state_restored      = true;

	try {
		$checks['exact_staging_host'] = bds_staging_featured_acceptance_is_host();
		$required = array(
			'wc_create_order', 'wc_get_product', 'wps_sfw_get_meta_data', 'wps_sfw_update_meta_data',
			'bds_featured_billing_product_ready', 'bds_featured_billing_product_level', 'bds_featured_billing_sync',
			'bds_featured_billing_set_status', 'bds_featured_billing_refunded', 'bds_featured_billing_geo',
		);
		$checks['production_dependencies_loaded'] = ! array_filter( $required, function ( $name ) { return ! function_exists( $name ); } );
		$checks['isolated_seams_active'] = defined( 'BDS_STAGING_FEATURED_OWNSHIP_SEAM' ) && BDS_STAGING_FEATURED_OWNSHIP_SEAM
			&& defined( 'BDS_STAGING_FEATURED_GRANT_SEAM' ) && BDS_STAGING_FEATURED_GRANT_SEAM;
		if ( ! $checks['production_dependencies_loaded'] || ! $checks['isolated_seams_active'] ) {
			throw new RuntimeException( 'featured_acceptance_dependencies_unavailable' );
		}

		$owner_id = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$owner    = $owner_id ? get_userdata( $owner_id ) : false;
		$checks['fixture_owner_verified'] = $owner instanceof WP_User
			&& 'a' === (string) get_user_meta( $owner_id, '_bds_staging_auth_fixture', true )
			&& ! user_can( $owner_id, 'manage_options' )
			&& str_ends_with( strtolower( (string) $owner->user_email ), '.invalid' );
		if ( ! $checks['fixture_owner_verified'] ) {
			throw new RuntimeException( 'featured_fixture_owner_unavailable' );
		}

		$existing_orders = wc_get_orders( array( 'limit' => 1, 'return' => 'ids', 'status' => array_keys( wc_get_order_statuses() ), 'meta_key' => '_bds_staging_featured_acceptance', 'meta_value' => BDS_STAGING_FEATURED_ACCEPTANCE_MARKER ) );
		$existing_posts  = get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_bds_staging_featured_acceptance', 'meta_value' => BDS_STAGING_FEATURED_ACCEPTANCE_MARKER ) );
		$checks['no_preexisting_marked_records'] = empty( $existing_orders ) && empty( $existing_posts );
		if ( ! $checks['no_preexisting_marked_records'] ) {
			throw new RuntimeException( 'featured_fixture_record_collision' );
		}

		$listing_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'private', 'post_title' => 'Staging Featured Billing Fixture', 'post_author' => $owner_id ), true );
		if ( is_wp_error( $listing_id ) || ! $listing_id ) {
			throw new RuntimeException( 'featured_fixture_listing_failed' );
		}
		$listing_id = absint( $listing_id );
		update_post_meta( $listing_id, '_bds_staging_featured_acceptance', BDS_STAGING_FEATURED_ACCEPTANCE_MARKER );
		$checks['fixture_listing_created'] = BDS_STAGING_FEATURED_ACCEPTANCE_MARKER === (string) get_post_meta( $listing_id, '_bds_staging_featured_acceptance', true );

		$GLOBALS['bds_staging_featured_acceptance'] = array( 'active' => true, 'listing_id' => $listing_id, 'owner_id' => $owner_id, 'capacity' => true );
		wp_set_current_user( $owner_id );
		$_POST = array();
		$_GET  = array();

		$offers   = bds_featured_billing_skus();
		$products = array();
		foreach ( $offers as $sku => $offer ) {
			$product = bds_staging_featured_acceptance_product( $sku, $offer['monthly'] );
			if ( is_wp_error( $product ) || ! $product instanceof WC_Product ) {
				throw new RuntimeException( 'featured_fixture_product_failed' );
			}
			$products[ $sku ]      = $product;
			$created_product_ids[] = $product->get_id();
		}
		$checks['four_products_created'] = 4 === count( $products );
		$checks['four_products_ready']   = 4 === count( array_filter( $products, 'bds_featured_billing_product_ready' ) );
		$exact = true;
		foreach ( $products as $sku => $product ) {
			$exact = $exact && (string) $offers[ $sku ]['level'] === bds_featured_billing_product_level( $product )
				&& wc_format_decimal( $offers[ $sku ]['monthly'], 2 ) === wc_format_decimal( $product->get_price(), 2 );
		}
		$checks['levels_and_prices_exact'] = $exact;

		$local = $products['bds-feat-local'];
		$local->set_regular_price( '10.00' );
		$local->set_price( '10.00' );
		$local->save();
		$checks['wrong_price_rejected'] = ! bds_featured_billing_product_ready( wc_get_product( $local->get_id() ) );
		$local->set_regular_price( '9.99' );
		$local->set_price( '9.99' );
		$local->save();
		wps_sfw_update_meta_data( $local->get_id(), 'wps_sfw_subscription_interval', 'week' );
		$checks['wrong_interval_rejected'] = ! bds_featured_billing_product_ready( wc_get_product( $local->get_id() ) );
		wps_sfw_update_meta_data( $local->get_id(), 'wps_sfw_subscription_interval', 'month' );
		$local = wc_get_product( $local->get_id() );

		$checks['ownership_isolated'] = bds_featured_billing_listing_owned( $listing_id, $owner_id )
			&& ! bds_featured_billing_listing_owned( $listing_id, $owner_id + 999999 )
			&& ! bds_featured_billing_listing_owned( $listing_id + 999999, $owner_id );
		$geo_local = bds_featured_billing_geo( $listing_id, 'local' );
		$geo_global = bds_featured_billing_geo( $listing_id, 'global' );
		$GLOBALS['bds_staging_featured_acceptance']['active'] = false;
		$geo_closed = bds_featured_billing_geo( $listing_id, 'local' );
		$GLOBALS['bds_staging_featured_acceptance']['active'] = true;
		$checks['geography_enforced'] = 'MX' === (string) ( $geo_local['country'] ?? '' )
			&& is_float( $geo_local['lat'] ?? null ) && is_array( $geo_global ) && empty( $geo_closed );

		$parent = bds_staging_featured_acceptance_order( $owner_id, $local, $listing_id, 'parent', 'completed' );
		if ( is_wp_error( $parent ) ) {
			throw new RuntimeException( 'featured_parent_order_failed' );
		}
		$created_order_ids[] = $parent->get_id();
		$checks['synthetic_parent_paid'] = $parent->is_paid() && 'bds_staging_synthetic_no_gateway' === (string) $parent->get_payment_method();

		$subscription_id = wp_insert_post( array( 'post_type' => 'wps_subscriptions', 'post_status' => 'publish', 'post_title' => 'Staging Featured Billing Acceptance', 'post_author' => $owner_id ), true );
		if ( is_wp_error( $subscription_id ) || ! $subscription_id ) {
			throw new RuntimeException( 'featured_subscription_create_failed' );
		}
		$subscription_id   = absint( $subscription_id );
		$subscription_ids[] = $subscription_id;
		$period_one = current_time( 'timestamp' ) + MONTH_IN_SECONDS;
		foreach ( array(
			'_bds_staging_featured_acceptance' => BDS_STAGING_FEATURED_ACCEPTANCE_MARKER,
			'wps_customer_id' => $owner_id, 'wps_parent_order' => $parent->get_id(), 'wps_subscription_status' => 'active',
			'wps_sfw_subscription_number' => 1, 'wps_sfw_subscription_interval' => 'month', 'wps_next_payment_date' => $period_one,
		) as $key => $value ) {
			wps_sfw_update_meta_data( $subscription_id, $key, $value );
		}
		$checks['subscription_created'] = BDS_STAGING_FEATURED_ACCEPTANCE_MARKER === (string) get_post_meta( $subscription_id, '_bds_staging_featured_acceptance', true );

		delete_option( $grants_option );
		$activated = bds_featured_billing_sync( $parent->get_id(), $subscription_id );
		$grants    = bds_feat_grants();
		$checks['activation_created_one_grant'] = true === $activated && 1 === count( $grants )
			&& 'active' === (string) ( $grants[0]['status'] ?? '' ) && $subscription_id === absint( $grants[0]['subscription_id'] ?? 0 );
		$grant_id = (string) ( $grants[0]['id'] ?? '' );
		$checks['replay_idempotent'] = true === bds_featured_billing_sync( $parent->get_id(), $subscription_id )
			&& 1 === count( bds_feat_grants() ) && $grant_id === (string) ( bds_feat_grants()[0]['id'] ?? '' );

		$renewal = bds_staging_featured_acceptance_order( $owner_id, $local, $listing_id, 'renewal', 'completed' );
		if ( is_wp_error( $renewal ) ) {
			throw new RuntimeException( 'featured_renewal_order_failed' );
		}
		$created_order_ids[] = $renewal->get_id();
		bds_staging_featured_acceptance_mark_renewal( $renewal->get_id(), $subscription_id );
		$period_two = $period_one + MONTH_IN_SECONDS;
		wps_sfw_update_meta_data( $subscription_id, 'wps_next_payment_date', $period_two );
		$renewed = bds_featured_billing_sync( $renewal->get_id(), $subscription_id );
		$grants  = bds_feat_grants();
		$checks['renewal_extended_same_grant'] = true === $renewed && 1 === count( $grants )
			&& $grant_id === (string) ( $grants[0]['id'] ?? '' ) && $renewal->get_id() === absint( $grants[0]['order_id'] ?? 0 )
			&& strtotime( (string) ( $grants[0]['ends'] ?? '' ) ) >= $period_two - MINUTE_IN_SECONDS;

		$failed = bds_staging_featured_acceptance_order( $owner_id, $local, $listing_id, 'failed', 'failed' );
		if ( is_wp_error( $failed ) ) {
			throw new RuntimeException( 'featured_failed_order_failed' );
		}
		$created_order_ids[] = $failed->get_id();
		bds_staging_featured_acceptance_mark_renewal( $failed->get_id(), $subscription_id );
		bds_featured_billing_order_failed( $failed->get_id(), 'pending', 'failed', $failed );
		$checks['failed_renewal_paused'] = 'paused' === (string) ( bds_feat_grants()[0]['status'] ?? '' );

		bds_featured_billing_sync( $renewal->get_id(), $subscription_id );
		bds_featured_billing_cancelled( $subscription_id );
		$checks['cancellation_honors_paid_through'] = true === ( bds_feat_grants()[0]['cancel_at_period_end'] ?? false )
			&& 'active' === (string) ( bds_feat_grants()[0]['status'] ?? '' );
		bds_featured_billing_expired( $subscription_id );
		$checks['expiry_preserves_record'] = 1 === count( bds_feat_grants() ) && 'expired' === (string) ( bds_feat_grants()[0]['status'] ?? '' );
		bds_featured_billing_sync( $renewal->get_id(), $subscription_id );
		bds_featured_billing_refunded( $renewal->get_id() );
		$checks['refund_revokes'] = 'revoked' === (string) ( bds_feat_grants()[0]['status'] ?? '' );

		delete_option( $grants_option );
		$capacity_subscription = wp_insert_post( array( 'post_type' => 'wps_subscriptions', 'post_status' => 'publish', 'post_title' => 'Staging Featured Capacity Acceptance', 'post_author' => $owner_id ), true );
		if ( is_wp_error( $capacity_subscription ) || ! $capacity_subscription ) {
			throw new RuntimeException( 'featured_capacity_subscription_failed' );
		}
		$capacity_subscription = absint( $capacity_subscription );
		$subscription_ids[]    = $capacity_subscription;
		foreach ( array(
			'_bds_staging_featured_acceptance' => BDS_STAGING_FEATURED_ACCEPTANCE_MARKER,
			'wps_customer_id' => $owner_id, 'wps_parent_order' => $parent->get_id(), 'wps_subscription_status' => 'active',
			'wps_sfw_subscription_number' => 1, 'wps_sfw_subscription_interval' => 'month', 'wps_next_payment_date' => $period_two,
		) as $key => $value ) {
			wps_sfw_update_meta_data( $capacity_subscription, $key, $value );
		}
		$GLOBALS['bds_staging_featured_acceptance']['capacity'] = false;
		$checks['capacity_failure_no_grant'] = false === bds_featured_billing_sync( $parent->get_id(), $capacity_subscription ) && empty( bds_feat_grants() );
		$GLOBALS['bds_staging_featured_acceptance']['capacity'] = true;
		$checks['no_real_gateway'] = 3 === count( array_filter( array( $parent, $renewal, $failed ), function ( $order ) { return 'bds_staging_synthetic_no_gateway' === (string) $order->get_payment_method(); } ) );
	} catch ( Throwable $exception ) {
		$errors[] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
	} finally {
		$GLOBALS['bds_staging_featured_acceptance']['active'] = false;
		foreach ( array_reverse( $created_order_ids ) as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order
				|| $owner_id !== absint( $order->get_user_id() )
				|| BDS_STAGING_FEATURED_ACCEPTANCE_MARKER !== (string) $order->get_meta( '_bds_staging_featured_acceptance', true )
				|| ! $order->delete( true ) ) {
				$records_clean = false;
			}
		}
		foreach ( array_reverse( $subscription_ids ) as $subscription_id ) {
			if ( 'wps_subscriptions' !== get_post_type( $subscription_id )
				|| $owner_id !== absint( get_post_field( 'post_author', $subscription_id ) )
				|| BDS_STAGING_FEATURED_ACCEPTANCE_MARKER !== (string) get_post_meta( $subscription_id, '_bds_staging_featured_acceptance', true )
				|| ! wp_delete_post( $subscription_id, true ) ) {
				$records_clean = false;
			}
		}
		if ( $listing_id && ( 'page' !== get_post_type( $listing_id )
			|| $owner_id !== absint( get_post_field( 'post_author', $listing_id ) )
			|| BDS_STAGING_FEATURED_ACCEPTANCE_MARKER !== (string) get_post_meta( $listing_id, '_bds_staging_featured_acceptance', true )
			|| ! wp_delete_post( $listing_id, true ) ) ) {
			$records_clean = false;
		}
		foreach ( array_reverse( $created_product_ids ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product
				|| ! array_key_exists( sanitize_key( (string) $product->get_sku() ), bds_featured_billing_skus() )
				|| BDS_STAGING_FEATURED_ACCEPTANCE_MARKER !== (string) get_post_meta( $product_id, '_bds_staging_featured_acceptance', true )
				|| ! $product->delete( true ) ) {
				$products_clean = false;
			}
		}
		if ( $grants_existed ) {
			update_option( $grants_option, $grants_before, false );
		} else {
			delete_option( $grants_option );
		}
		$state_restored = $grants_existed ? get_option( $grants_option, null ) === $grants_before : get_option( $grants_option, null ) === null;
		wp_set_current_user( $original_user );
		$_POST = $original_post;
		$_GET  = $original_get;
		$GLOBALS['bds_staging_featured_acceptance'] = array( 'active' => false, 'listing_id' => 0, 'owner_id' => 0, 'capacity' => true );
		$checks['fixture_records_cleaned']   = $records_clean;
		$checks['fixture_products_cleaned']  = $products_clean;
		$checks['isolated_state_restored']   = $state_restored;
		$checks['original_context_restored'] = get_current_user_id() === $original_user && $_POST === $original_post && $_GET === $original_get;
		delete_option( $lock );
	}

	$complete = 28 === count( $checks ) && 28 === count( array_filter( $checks ) ) && empty( $errors );
	bds_staging_featured_acceptance_save_report( $complete, $checks, $errors );
}
add_action( 'init', 'bds_staging_featured_acceptance_run', 130 );

function bds_staging_featured_acceptance_report() {
	$report = get_option( BDS_STAGING_FEATURED_ACCEPTANCE_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_featured_acceptance_register_route() {
	if ( ! bds_staging_featured_acceptance_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/featured-billing-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_featured_acceptance_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_featured_acceptance_register_route' );
