<?php
/**
 * Plugin Name: BrandDad Done-for-You Quote Guard
 * Description: Keeps Done-for-You and retired growth products quote-first without changing historical commerce records.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_QUOTE_GUARD_VERSION', '0.1.0' );

/** Exact, server-owned public slugs that must not start a new self-serve purchase. */
function bds_dfy_quote_guard_slugs() {
	return array(
		'done-for-you-ai-management',
		'bds-ig-growth-us-starter',
		'bds-ig-growth-us-growth',
		'bds-ig-growth-us-pro',
		'bds-ig-growth-ww-starter',
		'bds-ig-growth-ww-growth',
		'bds-ig-growth-ww-pro',
	);
}

function bds_dfy_quote_guard_product_slug( $product_or_id ) {
	$product = is_object( $product_or_id ) ? $product_or_id : ( function_exists( 'wc_get_product' ) ? wc_get_product( absint( $product_or_id ) ) : null );
	if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_slug' ) ) ) {
		return '';
	}
	return sanitize_title( (string) $product->get_slug() );
}

function bds_dfy_quote_guard_is_slug( $slug ) {
	return in_array( sanitize_title( (string) $slug ), bds_dfy_quote_guard_slugs(), true );
}

/** Fail closed for new public cart additions; existing commerce records are untouched. */
function bds_dfy_quote_guard_cart_validation( $passed, $product_id, $quantity = 1, $variation_id = 0 ) {
	$product_slug   = bds_dfy_quote_guard_product_slug( absint( $product_id ) );
	$variation_slug = absint( $variation_id ) ? bds_dfy_quote_guard_product_slug( absint( $variation_id ) ) : '';
	if ( ! bds_dfy_quote_guard_is_slug( $product_slug ) && ! bds_dfy_quote_guard_is_slug( $variation_slug ) ) {
		return $passed;
	}
	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( __( 'This managed service starts with a written scope. Contact BrandDad and we will prepare the correct order after you approve it.', 'branddad' ), 'notice' );
	}
	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'bds_dfy_quote_guard_cart_validation', PHP_INT_MAX, 4 );

/**
 * Block checkout for a guarded product that was already present in a stale cart.
 * The cart is not changed; the customer receives the same written-scope route.
 */
function bds_dfy_quote_guard_check_cart() {
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart || ! is_callable( array( WC()->cart, 'get_cart' ) ) ) {
		return;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		$product_slug   = bds_dfy_quote_guard_product_slug( isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0 );
		$variation_slug = bds_dfy_quote_guard_product_slug( isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0 );
		if ( bds_dfy_quote_guard_is_slug( $product_slug ) || bds_dfy_quote_guard_is_slug( $variation_slug ) ) {
			$message    = __( 'This managed service starts with a written scope. Contact BrandDad and we will prepare the correct order after you approve it.', 'branddad' );
			$has_notice = function_exists( 'wc_has_notice' ) && wc_has_notice( $message, 'error' );
			if ( function_exists( 'wc_add_notice' ) && ! $has_notice ) {
				wc_add_notice( $message, 'error' );
			}
			return;
		}
	}
}
add_action( 'woocommerce_check_cart_items', 'bds_dfy_quote_guard_check_cart', PHP_INT_MAX );

/** Replace loop purchase controls with the current qualification path. */
function bds_dfy_quote_guard_loop_link( $html, $product, $args = array() ) {
	if ( ! bds_dfy_quote_guard_is_slug( bds_dfy_quote_guard_product_slug( $product ) ) ) {
		return $html;
	}
	$url = home_url( '/contact-us/' );
	return '<a class="button bds-dfy-quote-link" href="' . esc_url( $url ) . '">' . esc_html__( 'Request a written scope', 'branddad' ) . '</a>';
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'bds_dfy_quote_guard_loop_link', PHP_INT_MAX, 3 );

function bds_dfy_quote_guard_is_main_product() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return false;
	}
	return 'done-for-you-ai-management' === sanitize_title( (string) get_post_field( 'post_name', get_queried_object_id() ) );
}

function bds_dfy_quote_guard_is_services_page() {
	return ! is_admin() && function_exists( 'is_page' ) && is_page( 'services' );
}

/** Current quote-first explanation rendered beside the product title. */
function bds_dfy_quote_guard_summary() {
	if ( ! bds_dfy_quote_guard_is_main_product() ) {
		return;
	}
	$contact = home_url( '/contact-us/' );
	echo '<section class="bds-dfy-quote" data-bds-dfy-quote-guard="' . esc_attr( BDS_DFY_QUOTE_GUARD_VERSION ) . '">';
	echo '<p class="bds-fp-eyebrow">Done-for-you business management</p>';
	echo '<h2>' . esc_html__( 'Start with a written scope—not instant checkout.', 'branddad' ) . '</h2>';
	echo '<p>' . esc_html__( 'Tell us which channels, conversations, website tasks, and lead follow-up you want managed. We confirm the workload, approvals, limits, and final monthly price in writing before billing.', 'branddad' ) . '</p>';
	echo '<ul><li>' . esc_html__( 'Complete Management starts at $399 per month.', 'branddad' ) . '</li><li>' . esc_html__( 'Official-platform or documented creator promotion is optional.', 'branddad' ) . '</li><li>' . esc_html__( 'Advertising spend and creator fees are separate and require approval.', 'branddad' ) . '</li><li>' . esc_html__( 'No follower, reach, lead, or sales result is guaranteed.', 'branddad' ) . '</li></ul>';
	echo '<p><a class="button bds-dfy-quote-link" href="' . esc_url( $contact ) . '">' . esc_html__( 'Contact BrandDad for a written scope', 'branddad' ) . '</a></p></section>';
}

/** Remove only Woo's standard purchase form on the exact quote-only product. */
function bds_dfy_quote_guard_single_product() {
	if ( ! bds_dfy_quote_guard_is_main_product() ) {
		return;
	}
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_single_product_summary', 'bds_dfy_quote_guard_summary', 31 );
}
add_action( 'wp', 'bds_dfy_quote_guard_single_product', PHP_INT_MAX );

/** Hide the known stale callback output while its legacy plugin remains installed. */
function bds_dfy_quote_guard_css() {
	$is_product  = bds_dfy_quote_guard_is_main_product();
	$is_services = bds_dfy_quote_guard_is_services_page();
	if ( ! $is_product && ! $is_services ) {
		return;
	}
	echo '<style id="bds-dfy-quote-guard-css" data-bds-dfy-quote-guard="' . esc_attr( BDS_DFY_QUOTE_GUARD_VERSION ) . '">#bds-jap-fp,#bds-jap-fp + p,#bds-jap-fp + p + .bds-fp-grid{display:none!important}';
	if ( $is_product ) {
		echo '.bds-dfy-quote{margin:24px 0;padding:24px;border:1px solid #dbe5ef;border-radius:18px;background:#f4f8fc;color:#10243a}.bds-dfy-quote h2{color:#10243a!important;font-size:1.6rem}.bds-dfy-quote li{margin:8px 0}.bds-dfy-quote-link{display:inline-flex!important;align-items:center;justify-content:center;min-height:48px;padding:0 18px!important;border-radius:10px!important;background:#0668d8!important;color:#fff!important;text-decoration:none!important;font-weight:800}';
	}
	echo '</style>';
}
add_action( 'wp_head', 'bds_dfy_quote_guard_css', PHP_INT_MAX );

/** Retired public product pages return to the current management section. */
function bds_dfy_quote_guard_retired_redirect() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$slug = sanitize_title( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	if ( 'done-for-you-ai-management' === $slug || ! bds_dfy_quote_guard_is_slug( $slug ) ) {
		return;
	}
	wp_safe_redirect( home_url( '/services/#bds-fp-business-autopilot' ), 302, 'BrandDad Done-for-You Quote Guard' );
	exit;
}
add_action( 'template_redirect', 'bds_dfy_quote_guard_retired_redirect', 1 );
