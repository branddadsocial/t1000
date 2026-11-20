<?php
/**
 * Plugin Name: BrandDad Hosted Staging Business Essentials Fixture
 * Description: One-time, fail-closed creation of the isolated staging Business Essentials subscription fixture.
 * Version: 0.1.0
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		$expected_host = 'staging-branddad-social.stackstaging.com';
		$actual_host   = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

		if ( ! hash_equals( $expected_host, $actual_host ) ) {
			return;
		}

		$report_key = 'bds_stage_business_essentials_fixture_v1';
		$report     = get_option( $report_key, array() );
		if ( is_array( $report ) && ! empty( $report['complete'] ) ) {
			return;
		}

		if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product' ) ) {
			update_option( $report_key, array( 'complete' => false, 'status' => 'woocommerce_unavailable', 'updated' => gmdate( 'c' ) ), false );
			return;
		}

		$slug     = 'ai-business-essentials';
		$existing = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $existing instanceof WP_Post && 'yes' !== (string) get_post_meta( $existing->ID, '_bds_staging_fixture', true ) ) {
			update_option( $report_key, array( 'complete' => false, 'status' => 'unmarked_product_collision', 'updated' => gmdate( 'c' ) ), false );
			return;
		}

		$product = $existing instanceof WP_Post ? wc_get_product( $existing->ID ) : new WC_Product_Simple();
		if ( ! $product instanceof WC_Product_Simple ) {
			update_option( $report_key, array( 'complete' => false, 'status' => 'invalid_product_type', 'updated' => gmdate( 'c' ) ), false );
			return;
		}

		$product->set_name( 'AI Business Essentials' );
		$product->set_slug( $slug );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_description( 'Staging-only test product for the BrandDad AI Business Essentials subscription lifecycle.' );
		$product->set_short_description( 'Isolated staging fixture. Not a production purchase.' );
		$product->set_regular_price( '29.99' );
		$product->set_price( '29.99' );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_tax_status( 'none' );
		$product_id = $product->save();

		if ( ! $product_id ) {
			update_option( $report_key, array( 'complete' => false, 'status' => 'product_save_failed', 'updated' => gmdate( 'c' ) ), false );
			return;
		}

		$metadata = array(
			'_bds_staging_fixture'                         => 'yes',
			'_bds_staging_fixture_version'                 => '1',
			'_wps_sfw_product'                             => 'yes',
			'wps_sfw_subscription_number'                  => '1',
			'wps_sfw_subscription_interval'                => 'month',
			'wps_sfw_subscription_expiry_number'           => '',
			'wps_sfw_subscription_expiry_interval'         => '',
			'wps_sfw_subscription_initial_signup_price'    => '0',
			'wps_sfw_subscription_free_trial_number'       => '7',
			'wps_sfw_subscription_free_trial_interval'     => 'day',
			'_bds_ai_offer'                                => 'yes',
			'_bds_ai_monthly_price'                        => '29.99',
			'_bds_ai_setup_fee'                            => '0',
			'_bds_ai_formwise_credits'                     => '75',
			'_bds_ai_audience'                             => 'customer',
		);

		foreach ( $metadata as $key => $value ) {
			update_post_meta( $product_id, $key, $value );
		}

		$saved_product = wc_get_product( $product_id );
		$verified      = $saved_product instanceof WC_Product_Simple
			&& $slug === (string) get_post_field( 'post_name', $product_id )
			&& 'publish' === $saved_product->get_status()
			&& 'hidden' === $saved_product->get_catalog_visibility()
			&& '29.99' === (string) $saved_product->get_regular_price()
			&& $saved_product->is_virtual()
			&& $saved_product->is_sold_individually();

		foreach ( $metadata as $key => $value ) {
			if ( (string) $value !== (string) get_post_meta( $product_id, $key, true ) ) {
				$verified = false;
				break;
			}
		}

		if ( ! $verified ) {
			update_option( $report_key, array( 'complete' => false, 'status' => 'verification_failed', 'product_id' => absint( $product_id ), 'updated' => gmdate( 'c' ) ), false );
			return;
		}

		update_option(
			$report_key,
			array(
				'complete'   => true,
				'status'     => $existing instanceof WP_Post ? 'updated' : 'created',
				'product_id' => absint( $product_id ),
				'slug'       => $slug,
				'updated'    => gmdate( 'c' ),
			),
			false
		);
	},
	30
);
