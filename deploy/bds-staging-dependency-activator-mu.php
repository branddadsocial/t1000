<?php
/**
 * Plugin Name: BrandDad Hosted Staging Dependency Activator
 * Description: One-time, fail-closed activation of existing commerce plugins on the isolated hosted staging site.
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

		$report_key = 'bds_stage_dependency_activation_v1';
		$existing   = get_option( $report_key, array() );

		if ( is_array( $existing ) && ! empty( $existing['complete'] ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugins = array(
			'woocommerce/woocommerce.php',
			'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
			'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php',
		);

		$results = array();

		foreach ( $plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				$results[ $plugin ] = array( 'status' => 'already_active' );
				continue;
			}

			$validation = validate_plugin( $plugin );
			if ( is_wp_error( $validation ) ) {
				$results[ $plugin ] = array(
					'status' => 'invalid',
					'code'   => sanitize_key( $validation->get_error_code() ),
				);
				continue;
			}

			$activation = activate_plugin( $plugin, '', false, true );
			if ( is_wp_error( $activation ) ) {
				$results[ $plugin ] = array(
					'status'  => 'failed',
					'code'    => sanitize_key( $activation->get_error_code() ),
					'message' => sanitize_text_field( wp_html_excerpt( $activation->get_error_message(), 240, '&hellip;' ) ),
				);
				continue;
			}

			$results[ $plugin ] = array( 'status' => 'activated' );
		}

		$complete = true;
		foreach ( $plugins as $plugin ) {
			if ( ! is_plugin_active( $plugin ) ) {
				$complete = false;
				break;
			}
		}

		update_option(
			$report_key,
			array(
				'complete' => $complete,
				'host'     => $actual_host,
				'results'  => $results,
				'updated'  => gmdate( 'c' ),
			),
			false
		);
	},
	0
);
