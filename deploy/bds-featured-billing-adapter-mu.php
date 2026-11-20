<?php
/**
 * Plugin Name: BrandDad Featured Billing Adapter
 * Description: Subscription-safe Woo billing lifecycle for Cursor-owned Directory Featured placement records.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_FEATURED_BILLING_VERSION', '0.1.0' );

function bds_featured_billing_skus() {
	return array(
		'bds-feat-local'    => array( 'level' => 'local', 'monthly' => '9.99' ),
		'bds-feat-regional' => array( 'level' => 'regional', 'monthly' => '19.99' ),
		'bds-feat-national' => array( 'level' => 'national', 'monthly' => '39.99' ),
		'bds-feat-global'   => array( 'level' => 'global', 'monthly' => '99.99' ),
	);
}

function bds_featured_billing_product_level( $product ) {
	$sku = $product && method_exists( $product, 'get_sku' ) ? sanitize_key( (string) $product->get_sku() ) : '';
	return (string) ( bds_featured_billing_skus()[ $sku ]['level'] ?? '' );
}

function bds_featured_billing_product_ready( $product ) {
	$sku     = $product && method_exists( $product, 'get_sku' ) ? sanitize_key( (string) $product->get_sku() ) : '';
	$offer   = bds_featured_billing_skus()[ $sku ] ?? array();
	$price   = $product && method_exists( $product, 'get_price' ) ? wc_format_decimal( $product->get_price(), 2 ) : '';
	$enabled = $product && function_exists( 'wps_sfw_get_meta_data' ) ? (string) wps_sfw_get_meta_data( $product->get_id(), '_wps_sfw_product', true ) : '';
	$number  = $product && function_exists( 'wps_sfw_get_meta_data' ) ? absint( wps_sfw_get_meta_data( $product->get_id(), 'wps_sfw_subscription_number', true ) ) : 0;
	$unit    = $product && function_exists( 'wps_sfw_get_meta_data' ) ? sanitize_key( (string) wps_sfw_get_meta_data( $product->get_id(), 'wps_sfw_subscription_interval', true ) ) : '';
	return $offer && 'yes' === $enabled && 1 === $number && 'month' === $unit && wc_format_decimal( $offer['monthly'], 2 ) === $price;
}

/** The Cursor activation omitted ownership, geography, renewals and revocation. */
function bds_featured_billing_disable_legacy_activation() {
	remove_action( 'woocommerce_order_status_processing', 'bds_feat_activate_paid_order', 20 );
	remove_action( 'woocommerce_order_status_completed', 'bds_feat_activate_paid_order', 20 );
}
add_action( 'plugins_loaded', 'bds_featured_billing_disable_legacy_activation', 100 );

function bds_featured_billing_requested_listing() {
	$raw = isset( $_POST['_bds_feat_listing_id'] ) ? wp_unslash( $_POST['_bds_feat_listing_id'] ) : ( isset( $_GET['listing'] ) ? wp_unslash( $_GET['listing'] ) : '' );
	return absint( $raw );
}

function bds_featured_billing_listing_owned( $listing_id, $user_id ) {
	return function_exists( 'bds_dir_free_ai_listing_eligible' )
		&& true === bds_dir_free_ai_listing_eligible( absint( $listing_id ), absint( $user_id ) );
}

/** Require a signed-in verified owner before a Featured SKU can enter the cart. */
function bds_featured_billing_add_to_cart_validation( $valid, $product_id, $quantity, $variation_id = 0 ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $variation_id ? $variation_id : $product_id ) : false;
	if ( ! bds_featured_billing_product_level( $product ) ) {
		return $valid;
	}
	if ( ! bds_featured_billing_product_ready( $product ) ) {
		wc_add_notice( __( 'This Featured plan is not configured with the approved monthly price and billing interval.', 'branddad' ), 'error' );
		return false;
	}
	$listing_id = bds_featured_billing_requested_listing();
	if ( ! is_user_logged_in() || ! $listing_id || ! bds_featured_billing_listing_owned( $listing_id, get_current_user_id() ) ) {
		wc_add_notice( __( 'Sign in with the verified owner account and choose its claimed business listing before buying Featured placement.', 'branddad' ), 'error' );
		return false;
	}
	return $valid;
}
add_filter( 'woocommerce_add_to_cart_validation', 'bds_featured_billing_add_to_cart_validation', 60, 4 );

function bds_featured_billing_cart_item_data( $data, $product_id, $variation_id ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $variation_id ? $variation_id : $product_id ) : false;
	if ( bds_featured_billing_product_level( $product ) ) {
		$listing_id = bds_featured_billing_requested_listing();
		if ( $listing_id && is_user_logged_in() && bds_featured_billing_listing_owned( $listing_id, get_current_user_id() ) ) {
			$data['_bds_feat_listing_id'] = $listing_id;
			$data['_bds_feat_owner_id']   = get_current_user_id();
			$data['unique_key']           = hash( 'sha256', $listing_id . '|' . $product->get_id() );
		}
	}
	return $data;
}
add_filter( 'woocommerce_add_cart_item_data', 'bds_featured_billing_cart_item_data', 20, 3 );

function bds_featured_billing_order_item( $item, $cart_item_key, $values, $order ) {
	if ( ! empty( $values['_bds_feat_listing_id'] ) && ! empty( $values['_bds_feat_owner_id'] ) ) {
		$item->add_meta_data( '_bds_feat_listing_id', absint( $values['_bds_feat_listing_id'] ), true );
		$item->add_meta_data( '_bds_feat_owner_id', absint( $values['_bds_feat_owner_id'] ), true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'bds_featured_billing_order_item', 20, 4 );

function bds_featured_billing_registration_required( $required ) {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( bds_featured_billing_product_level( $item['data'] ?? false ) ) {
				return true;
			}
		}
	}
	return $required;
}
add_filter( 'woocommerce_checkout_registration_required', 'bds_featured_billing_registration_required', 30 );

/** Resolve one exact allowlisted Featured item from an authoritative order. */
function bds_featured_billing_order_context( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return array();
	}
	$found = array();
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$product = $item->get_product();
		$level   = bds_featured_billing_product_level( $product );
		if ( ! $level ) {
			continue;
		}
		$found[] = array(
			'level'      => $level,
			'monthly'    => (string) ( bds_featured_billing_skus()[ sanitize_key( (string) $product->get_sku() ) ]['monthly'] ?? '' ),
			'quantity'   => absint( $item->get_quantity() ),
			'subtotal'   => wc_format_decimal( $item->get_subtotal(), 2 ),
			'listing_id' => absint( $item->get_meta( '_bds_feat_listing_id', true ) ),
			'owner_id'   => absint( $item->get_meta( '_bds_feat_owner_id', true ) ),
		);
	}
	return 1 === count( $found ) ? $found[0] : array();
}

/** Cursor supplies actual listing geography; absence keeps non-global billing closed. */
function bds_featured_billing_geo( $listing_id, $level ) {
	$geo = apply_filters( 'bds_featured_listing_geo', array(), absint( $listing_id ) );
	$geo = is_array( $geo ) ? $geo : array();
	$country = strtoupper( sanitize_text_field( (string) ( $geo['country'] ?? '' ) ) );
	$lat = isset( $geo['lat'] ) && is_numeric( $geo['lat'] ) ? (float) $geo['lat'] : null;
	$lng = isset( $geo['lng'] ) && is_numeric( $geo['lng'] ) ? (float) $geo['lng'] : null;
	if ( 'global' !== $level && ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
		return array();
	}
	if ( in_array( $level, array( 'local', 'regional' ), true ) && ( null === $lat || null === $lng || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) ) {
		return array();
	}
	return array( 'country' => $country, 'lat' => $lat, 'lng' => $lng );
}

function bds_featured_billing_lock( $subscription_id ) {
	$key = 'bds_feat_bill_' . absint( $subscription_id );
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	if ( absint( get_option( $key, 0 ) ) < time() - 60 ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

/** Activate or extend exactly one Cursor-owned grant after verified payment. */
function bds_featured_billing_sync( $order_id, $subscription_id ) {
	if ( ! function_exists( 'bds_feat_grants' ) || ! function_exists( 'bds_feat_save_grants' ) || ! function_exists( 'bds_feat_has_capacity' ) || ! function_exists( 'wps_sfw_get_meta_data' ) ) {
		return false;
	}
	$order           = wc_get_order( absint( $order_id ) );
	$subscription_id = absint( $subscription_id );
	if ( ! $order || ! $order->is_paid() || ! $subscription_id || 'active' !== sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true ) ) ) {
		return false;
	}
	$parent_id = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true ) );
	$recurring_number = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_number', true ) );
	$recurring_unit   = sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_interval', true ) );
	$renewal   = 'yes' === (string) wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
	$linked    = $order_id === $parent_id || ( $renewal && $subscription_id === absint( wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription', true ) ) );
	$context   = bds_featured_billing_order_context( $order );
	if ( ! $context && $parent_id && $order_id !== $parent_id ) {
		$parent_order = wc_get_order( $parent_id );
		$context      = bds_featured_billing_order_context( $parent_order );
	}
	$price_matches = $context && 1 === absint( $context['quantity'] ?? 0 ) && wc_format_decimal( $context['monthly'] ?? '', 2 ) === wc_format_decimal( $context['subtotal'] ?? '', 2 );
	if ( ! $linked || ! $context || ! $price_matches || 1 !== $recurring_number || 'month' !== $recurring_unit || absint( $order->get_user_id() ) !== $context['owner_id'] || ! bds_featured_billing_listing_owned( $context['listing_id'], $context['owner_id'] ) ) {
		$order->add_order_note( 'Featured placement was not activated because verified listing ownership could not be confirmed.', false, false );
		return false;
	}
	$geo = bds_featured_billing_geo( $context['listing_id'], $context['level'] );
	if ( ! $geo ) {
		$order->add_order_note( 'Featured placement needs verified listing geography before activation.', false, false );
		return false;
	}
	$period_end = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_next_payment_date', true ) );
	if ( $period_end <= current_time( 'timestamp' ) ) {
		$order->add_order_note( 'Featured placement needs an authoritative next payment date before activation.', false, false );
		return false;
	}
	$lock = bds_featured_billing_lock( $subscription_id );
	if ( ! $lock ) {
		return false;
	}
	try {
		$grants = bds_feat_grants();
		$index  = null;
		foreach ( $grants as $i => $grant ) {
			if ( 'paid' === ( $grant['kind'] ?? '' ) && $subscription_id === absint( $grant['subscription_id'] ?? 0 ) ) {
				$index = $i;
				break;
			}
		}
		$changes_lane = null !== $index && (string) ( $grants[ $index ]['level'] ?? '' ) !== $context['level'];
		if ( ( null === $index || $changes_lane ) && ! bds_feat_has_capacity( $grants, $context['level'] ) ) {
			$order->add_order_note( 'Featured placement payment is verified but the selected lane is at capacity; manual review required.', false, false );
			return false;
		}
		$grant = null === $index ? array(
			'id'          => 'g_' . wp_generate_password( 12, false, false ),
			'kind'        => 'paid',
			'starts'      => gmdate( DATE_ATOM ),
			'impressions' => 0,
			'clicks'      => 0,
		) : $grants[ $index ];
		$grant = array_merge(
			$grant,
			array(
				'listing_id'     => $context['listing_id'],
				'level'          => $context['level'],
				'status'         => 'active',
				'ends'           => date_i18n( DATE_ATOM, $period_end ),
				'country'        => $geo['country'],
				'lat'            => $geo['lat'],
				'lng'            => $geo['lng'],
				'notes'          => 'woo-subscription:' . $subscription_id,
				'order_id'       => absint( $order_id ),
				'parent_order_id'=> $parent_id,
				'subscription_id'=> $subscription_id,
				'customer_id'    => $context['owner_id'],
				'cancel_at_period_end' => false,
			)
		);
		if ( null === $index ) {
			$grants[] = $grant;
		} else {
			$grants[ $index ] = $grant;
		}
		bds_feat_save_grants( $grants );
		if ( function_exists( 'bds_feat_audit' ) ) {
			bds_feat_audit( null === $index ? 'billing_activate' : 'billing_renew', $grant['id'], array( 'subscription_id' => (string) $subscription_id, 'order_id' => (string) $order_id ) );
		}
		do_action( 'bds_featured_access_provisioned', $order_id, $subscription_id, $context );
		return true;
	} finally {
		delete_option( $lock );
	}
}

function bds_featured_billing_subscription_active( $order_id, $subscription_id ) {
	bds_featured_billing_sync( $order_id, $subscription_id );
}
add_action( 'wps_sfw_order_status_changed', 'bds_featured_billing_subscription_active', 60, 2 );

function bds_featured_billing_renewed( $renewal_order, $subscription_id ) {
	if ( $renewal_order instanceof WC_Order ) {
		bds_featured_billing_sync( $renewal_order->get_id(), absint( $subscription_id ) );
	}
}
add_action( 'wps_sfw_after_renewal_payment', 'bds_featured_billing_renewed', 60, 2 );

/** Apply lifecycle state without deleting accounting or performance history. */
function bds_featured_billing_set_status( $subscription_id, $status ) {
	if ( ! function_exists( 'bds_feat_grants' ) || ! function_exists( 'bds_feat_save_grants' ) ) {
		return;
	}
	$subscription_id = absint( $subscription_id );
	$grants = bds_feat_grants();
	$changed = false;
	foreach ( $grants as $i => $grant ) {
		if ( 'paid' !== ( $grant['kind'] ?? '' ) || $subscription_id !== absint( $grant['subscription_id'] ?? 0 ) ) {
			continue;
		}
		if ( 'cancelled' === $status && strtotime( (string) ( $grant['ends'] ?? '' ) ) > time() ) {
			$grants[ $i ]['cancel_at_period_end'] = true;
		} else {
			$grants[ $i ]['status'] = in_array( $status, array( 'on-hold', 'paused' ), true ) ? 'paused' : ( 'expired' === $status ? 'expired' : 'revoked' );
		}
		$changed = true;
	}
	if ( $changed ) {
		bds_feat_save_grants( $grants );
	}
}

function bds_featured_billing_cancelled( $subscription_id ) {
	bds_featured_billing_set_status( $subscription_id, 'cancelled' );
}
function bds_featured_billing_expired( $subscription_id ) {
	bds_featured_billing_set_status( $subscription_id, 'expired' );
}
add_action( 'wps_sfw_subscription_cancel', 'bds_featured_billing_cancelled', 60, 1 );
add_action( 'wps_sfw_expire_subscription_scheduler', 'bds_featured_billing_expired', 60, 1 );

function bds_featured_billing_order_failed( $order_id, $old_status, $new_status, $order ) {
	if ( ! in_array( sanitize_key( (string) $new_status ), array( 'failed', 'pending', 'wps_renewal' ), true ) || ! $order instanceof WC_Order ) {
		return;
	}
	$subscription_id = absint( wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription', true ) );
	if ( $subscription_id ) {
		bds_featured_billing_set_status( $subscription_id, 'on-hold' );
	}
}
add_action( 'woocommerce_order_status_changed', 'bds_featured_billing_order_failed', 70, 4 );

function bds_featured_billing_refunded( $order_id ) {
	if ( ! function_exists( 'bds_feat_grants' ) || ! function_exists( 'bds_feat_save_grants' ) ) {
		return;
	}
	$subscription_id = function_exists( 'wps_sfw_get_meta_data' ) ? absint( wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription', true ) ) : 0;
	if ( $subscription_id ) {
		bds_featured_billing_set_status( $subscription_id, 'refunded' );
		return;
	}
	$grants = bds_feat_grants();
	$changed = false;
	foreach ( $grants as $i => $grant ) {
		if ( 'paid' === ( $grant['kind'] ?? '' ) && in_array( absint( $order_id ), array( absint( $grant['order_id'] ?? 0 ), absint( $grant['parent_order_id'] ?? 0 ) ), true ) ) {
			$grants[ $i ]['status'] = 'revoked';
			$changed = true;
		}
	}
	if ( $changed ) {
		bds_feat_save_grants( $grants );
	}
}
add_action( 'woocommerce_order_refunded', 'bds_featured_billing_refunded', 60, 1 );
