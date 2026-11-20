<?php
/**
 * Plugin Name: BrandDad Hosted Staging Launch Products Fixture
 * Description: One-time fail-closed creation of four hidden launch-catalog subscription fixtures.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_LAUNCH_PRODUCTS_VERSION', '0.1.0' );
define( 'BDS_STAGING_LAUNCH_PRODUCTS_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_LAUNCH_PRODUCTS_REPORT', 'bds_staging_launch_products_report_v1' );
define( 'BDS_STAGING_LAUNCH_PRODUCTS_MARKER', 'launch-catalog-v1' );

function bds_staging_launch_products_is_host() {
	return BDS_STAGING_LAUNCH_PRODUCTS_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_launch_products_definitions() {
	return array(
		'branddad-ai-studio-starter' => array( 'name' => 'BrandDad AI Studio — Starter', 'price' => '14.99', 'credits' => 50 ),
		'ai-local-seo'               => array( 'name' => 'AI Local SEO', 'price' => '19.99', 'credits' => 60 ),
		'ai-lead-assistant'          => array( 'name' => 'AI Lead Assistant', 'price' => '19.99', 'credits' => 80 ),
		'ai-social-growth'           => array( 'name' => 'AI Social Growth', 'price' => '29.99', 'credits' => 150 ),
	);
}

function bds_staging_launch_products_save_report( $complete, $checks, $errors ) {
	$safe_checks = array();
	foreach ( array_keys( bds_staging_launch_products_definitions() ) as $slug ) {
		$safe_checks[ $slug ] = true === ( $checks[ $slug ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 8 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option(
		BDS_STAGING_LAUNCH_PRODUCTS_REPORT,
		array(
			'version'      => BDS_STAGING_LAUNCH_PRODUCTS_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_launch_products_metadata( $definition ) {
	return array(
		'_bds_staging_fixture'                         => 'yes',
		'_bds_staging_fixture_version'                 => '1',
		'_bds_staging_fixture_kind'                    => BDS_STAGING_LAUNCH_PRODUCTS_MARKER,
		'_wps_sfw_product'                             => 'yes',
		'wps_sfw_subscription_number'                  => '1',
		'wps_sfw_subscription_interval'                => 'month',
		'wps_sfw_subscription_expiry_number'           => '',
		'wps_sfw_subscription_expiry_interval'         => '',
		'wps_sfw_subscription_initial_signup_price'    => '0',
		'wps_sfw_subscription_free_trial_number'       => '0',
		'wps_sfw_subscription_free_trial_interval'     => 'day',
		'_bds_ai_offer'                                => 'yes',
		'_bds_ai_monthly_price'                        => (string) $definition['price'],
		'_bds_ai_setup_fee'                            => '0',
		'_bds_ai_formwise_credits'                     => (string) absint( $definition['credits'] ),
		'_bds_ai_audience'                             => 'customer',
	);
}

function bds_staging_launch_products_run() {
	$prior_report = get_option( BDS_STAGING_LAUNCH_PRODUCTS_REPORT, array() );
	if ( ! bds_staging_launch_products_is_host() || ( is_array( $prior_report ) && ! empty( $prior_report['complete'] ) ) ) {
		return;
	}
	$lock = 'bds_staging_launch_products_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 600 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}

	$checks = array();
	$errors = array();
	try {
		if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product' ) || ! function_exists( 'bds_ai_commercial_catalog' ) ) {
			throw new RuntimeException( 'dependencies_unavailable' );
		}
		$definitions = bds_staging_launch_products_definitions();
		$catalog     = bds_ai_commercial_catalog();
		$existing    = array();

		/* Preflight every definition and collision before the first write. */
		foreach ( $definitions as $slug => $definition ) {
			$offer = is_array( $catalog[ $slug ] ?? null ) ? $catalog[ $slug ] : array();
			if ( 'launch_candidate' !== (string) ( $offer['launch_state'] ?? '' )
				|| (string) $definition['price'] !== (string) ( $offer['monthly'] ?? '' )
				|| absint( $definition['credits'] ) !== absint( $offer['customer_credits'] ?? 0 ) ) {
				throw new RuntimeException( 'catalog_mismatch_' . $slug );
			}
			$post = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $post instanceof WP_Post ) {
				if ( 'yes' !== (string) get_post_meta( $post->ID, '_bds_staging_fixture', true )
					|| BDS_STAGING_LAUNCH_PRODUCTS_MARKER !== (string) get_post_meta( $post->ID, '_bds_staging_fixture_kind', true )
					|| ! ( wc_get_product( $post->ID ) instanceof WC_Product_Simple ) ) {
					throw new RuntimeException( 'unmarked_product_collision_' . $slug );
				}
				$existing[ $slug ] = $post->ID;
			}
		}

		foreach ( $definitions as $slug => $definition ) {
			$product = isset( $existing[ $slug ] ) ? wc_get_product( $existing[ $slug ] ) : new WC_Product_Simple();
			$product->set_name( $definition['name'] );
			$product->set_slug( $slug );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_description( 'Isolated staging fixture for BrandDad launch-link and subscription acceptance. Not a production purchase.' );
			$product->set_short_description( 'Hidden staging fixture. No live payment or fulfillment.' );
			$product->set_regular_price( $definition['price'] );
			$product->set_price( $definition['price'] );
			$product->set_virtual( true );
			$product->set_sold_individually( true );
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
			$product->set_tax_status( 'none' );
			/* Persist the ownership markers in the same Woo save as a newly created product. */
			$product->update_meta_data( '_bds_staging_fixture', 'yes' );
			$product->update_meta_data( '_bds_staging_fixture_version', '1' );
			$product->update_meta_data( '_bds_staging_fixture_kind', BDS_STAGING_LAUNCH_PRODUCTS_MARKER );
			$product_id = $product->save();
			if ( ! $product_id ) {
				throw new RuntimeException( 'product_save_failed_' . $slug );
			}
			$metadata = bds_staging_launch_products_metadata( $definition );
			foreach ( $metadata as $key => $value ) {
				update_post_meta( $product_id, $key, $value );
			}

			$saved = wc_get_product( $product_id );
			$valid = $saved instanceof WC_Product_Simple
				&& $slug === (string) get_post_field( 'post_name', $product_id )
				&& 'publish' === $saved->get_status()
				&& 'hidden' === $saved->get_catalog_visibility()
				&& (string) $definition['price'] === (string) $saved->get_regular_price()
				&& $saved->is_virtual()
				&& $saved->is_sold_individually();
			foreach ( $metadata as $key => $value ) {
				$valid = $valid && (string) $value === (string) get_post_meta( $product_id, $key, true );
			}
			$checks[ $slug ] = $valid;
		}
	} catch ( Throwable $exception ) {
		$errors[] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
	} finally {
		delete_option( $lock );
	}

	$complete = 4 === count( $checks ) && 4 === count( array_filter( $checks ) ) && empty( $errors );
	bds_staging_launch_products_save_report( $complete, $checks, $errors );
}
add_action( 'init', 'bds_staging_launch_products_run', 35 );

function bds_staging_launch_products_report() {
	$report = get_option( BDS_STAGING_LAUNCH_PRODUCTS_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_launch_products_register_route() {
	if ( ! bds_staging_launch_products_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/launch-products-fixture',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_launch_products_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_launch_products_register_route' );
