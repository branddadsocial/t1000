<?php
/**
 * Plugin Name: BrandDad Staging Lifecycle Acceptance
 * Description: One-time, exact-host lifecycle deduplication, privacy, capability, and aggregate acceptance.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_LIFECYCLE_VERSION', '0.1.0' );
define( 'BDS_STAGING_LIFECYCLE_HOST', 'staging-branddad-social.stackstaging.com' );

function bds_staging_lifecycle_is_host() {
	return BDS_STAGING_LIFECYCLE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_lifecycle_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_owner_verified',
		'non_admin_analytics_denied',
		'unknown_event_rejected',
		'duplicate_event_reused',
		'duplicate_event_single_row',
		'untrusted_metadata_stripped',
		'financial_metadata_bounded',
		'aggregate_gross_delta_correct',
		'aggregate_refund_delta_correct',
		'aggregate_net_delta_correct',
		'invalid_period_rejected',
	);
	$safe_checks = array();
	foreach ( $allowlist as $key ) {
		$safe_checks[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 10 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option(
		'bds_staging_lifecycle_report_v1',
		array(
			'version'      => BDS_STAGING_LIFECYCLE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_lifecycle_summary_data() {
	$request = new WP_REST_Request( 'GET', '/branddad/v1/admin/analytics/summary' );
	$request->set_param( 'period', gmdate( 'Y-m' ) );
	$response = bds_lifecycle_admin_summary( $request );
	return $response instanceof WP_REST_Response ? $response->get_data() : array();
}

function bds_staging_lifecycle_run() {
	if ( ! bds_staging_lifecycle_is_host() || get_option( 'bds_staging_lifecycle_report_v1', false ) ) {
		return;
	}
	$lock = 'bds_staging_lifecycle_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 300 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}

	$original_user = get_current_user_id();
	$checks = array();
	$errors = array();
	try {
		$required = array( 'bds_lifecycle_record', 'bds_lifecycle_event_key', 'bds_lifecycle_admin_permission', 'bds_lifecycle_admin_summary', 'bds_lifecycle_period' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_lifecycle_save_report( false, $checks, $errors );
			return;
		}

		$user_id = username_exists( 'bds_stage_acceptance_a' );
		$checks['fixture_owner_verified'] = $user_id && 'a' === (string) get_user_meta( $user_id, '_bds_staging_auth_fixture', true ) && ! user_can( $user_id, 'manage_woocommerce' );
		if ( ! $checks['fixture_owner_verified'] ) {
			$errors[] = 'fixture_owner_unavailable';
			bds_staging_lifecycle_save_report( false, $checks, $errors );
			return;
		}

		wp_set_current_user( $user_id );
		$checks['non_admin_analytics_denied'] = false === bds_lifecycle_admin_permission();
		$checks['unknown_event_rejected'] = 0 === bds_lifecycle_record( 'not_allowed', $user_id, 'staging_fixture', 990090, array(), 'staging-unknown-event' );

		$before = bds_staging_lifecycle_summary_data();
		$gross_before = absint( $before['confirmed_gross_minor']['USD'] ?? 0 );
		$refund_before = absint( $before['refunds_minor']['USD'] ?? 0 );
		$net_before = (int) ( $before['net_confirmed_minor']['USD'] ?? 0 );

		$payment_key = 'staging-lifecycle-payment-v1';
		$payment_a = bds_lifecycle_record(
			'payment_confirmed',
			$user_id,
			'staging_fixture',
			990100,
			array(
				'product_slug' => 'ai-business-essentials',
				'stage'        => 'paid',
				'currency'     => 'USD',
				'amount_minor' => 2999,
				'email'        => 'must-not-store@example.invalid',
				'message'      => 'must not store',
			),
			$payment_key
		);
		$payment_b = bds_lifecycle_record(
			'payment_confirmed',
			$user_id,
			'staging_fixture',
			990100,
			array( 'product_slug' => 'different-value-must-not-overwrite', 'currency' => 'EUR', 'amount_minor' => 999999 ),
			$payment_key
		);
		$event_key = bds_lifecycle_event_key( 'payment_confirmed', $user_id, 'staging_fixture', 990100, $payment_key );
		$matching = get_posts(
			array(
				'post_type'      => 'bds_lifecycle_evt',
				'post_status'    => 'private',
				'posts_per_page' => 3,
				'fields'         => 'ids',
				'meta_key'       => '_bds_event_key',
				'meta_value'     => $event_key,
			)
		);
		$checks['duplicate_event_reused'] = $payment_a > 0 && $payment_a === $payment_b;
		$checks['duplicate_event_single_row'] = 1 === count( $matching ) && absint( $matching[0] ?? 0 ) === absint( $payment_a );
		$checks['untrusted_metadata_stripped'] = '' === (string) get_post_meta( $payment_a, '_bds_event_email', true ) && '' === (string) get_post_meta( $payment_a, '_bds_event_message', true );
		$checks['financial_metadata_bounded'] = 'USD' === (string) get_post_meta( $payment_a, '_bds_event_currency', true ) && 2999 === absint( get_post_meta( $payment_a, '_bds_event_amount_minor', true ) );

		bds_lifecycle_record(
			'payment_refunded',
			$user_id,
			'staging_fixture',
			990101,
			array( 'stage' => 'refunded', 'currency' => 'USD', 'amount_minor' => 500 ),
			'staging-lifecycle-refund-v1'
		);
		$after = bds_staging_lifecycle_summary_data();
		$gross_after = absint( $after['confirmed_gross_minor']['USD'] ?? 0 );
		$refund_after = absint( $after['refunds_minor']['USD'] ?? 0 );
		$net_after = (int) ( $after['net_confirmed_minor']['USD'] ?? 0 );
		$checks['aggregate_gross_delta_correct'] = 2999 === $gross_after - $gross_before;
		$checks['aggregate_refund_delta_correct'] = 500 === $refund_after - $refund_before;
		$checks['aggregate_net_delta_correct'] = 2499 === $net_after - $net_before;
		$invalid_period = bds_lifecycle_period( '1999-99' );
		$checks['invalid_period_rejected'] = is_wp_error( $invalid_period ) && 'invalid_period' === $invalid_period->get_error_code();

		$complete = 12 === count( $checks ) && 12 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_lifecycle_save_report( $complete, $checks, $errors );
	} finally {
		wp_set_current_user( $original_user );
		delete_option( $lock );
	}
}
add_action( 'init', 'bds_staging_lifecycle_run', 101 );

function bds_staging_lifecycle_report() {
	$report = get_option( 'bds_staging_lifecycle_report_v1', array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_lifecycle_register_route() {
	if ( ! bds_staging_lifecycle_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/lifecycle-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_lifecycle_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_lifecycle_register_route' );
