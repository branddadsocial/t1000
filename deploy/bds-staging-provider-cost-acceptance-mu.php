<?php
/**
 * Plugin Name: BrandDad Staging Provider Cost Acceptance
 * Description: One-time, exact-host provider-cost validation, idempotency, privacy, authorization, and aggregate acceptance.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_PROVIDER_COST_VERSION', '0.1.0' );
define( 'BDS_STAGING_PROVIDER_COST_HOST', 'staging-branddad-social.stackstaging.com' );

function bds_staging_provider_cost_is_host() {
	return BDS_STAGING_PROVIDER_COST_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_provider_cost_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_owner_verified',
		'non_admin_finance_denied',
		'invalid_provider_rejected',
		'invalid_product_rejected',
		'invalid_basis_rejected',
		'invalid_currency_rejected',
		'negative_amount_rejected',
		'fractional_amount_rejected',
		'excessive_amount_rejected',
		'negative_timestamp_rejected',
		'future_timestamp_rejected',
		'short_idempotency_rejected',
		'valid_cost_stored',
		'duplicate_cost_reused',
		'duplicate_cost_single_row',
		'raw_reference_not_stored',
		'untrusted_metadata_stripped',
		'provider_aggregate_delta_correct',
		'product_aggregate_delta_correct',
		'cost_aggregate_delta_correct',
		'contribution_delta_correct',
		'coverage_state_explicit',
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
		'bds_staging_provider_cost_report_v1',
		array(
			'version'      => BDS_STAGING_PROVIDER_COST_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_provider_cost_summary_data() {
	$request = new WP_REST_Request( 'GET', '/branddad/v1/admin/finance/contribution' );
	$request->set_param( 'period', gmdate( 'Y-m' ) );
	$response = bds_provider_cost_admin_summary( $request );
	return $response instanceof WP_REST_Response ? $response->get_data() : array();
}

function bds_staging_provider_cost_event( $overrides = array() ) {
	return array_merge(
		array(
			'provider'      => 'formwise',
			'product_slug'  => 'ai-business-essentials',
			'basis'         => 'provider_reported',
			'currency'      => 'USD',
			'amount_minor'  => 137,
			'occurred_at'   => time(),
			'idempotency'   => 'staging-provider-cost-acceptance-v1',
			'email'         => 'must-not-store@example.invalid',
			'message'       => 'must not store',
		),
		$overrides
	);
}

function bds_staging_provider_cost_run() {
	if ( ! bds_staging_provider_cost_is_host() || get_option( 'bds_staging_provider_cost_report_v1', false ) ) {
		return;
	}
	$run_lock = 'bds_staging_provider_cost_acceptance_lock_v1';
	if ( ! add_option( $run_lock, time(), '', false ) ) {
		if ( absint( get_option( $run_lock, 0 ) ) >= time() - 300 ) {
			return;
		}
		delete_option( $run_lock );
		if ( ! add_option( $run_lock, time(), '', false ) ) {
			return;
		}
	}

	$original_user = get_current_user_id();
	$checks = array();
	$errors = array();
	try {
		$required = array( 'bds_provider_cost_record', 'bds_provider_cost_event_key', 'bds_provider_cost_admin_permission', 'bds_provider_cost_admin_summary', 'bds_provider_cost_period' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_provider_cost_save_report( false, $checks, $errors );
			return;
		}

		$user_id = username_exists( 'bds_stage_acceptance_a' );
		$checks['fixture_owner_verified'] = $user_id
			&& 'a' === (string) get_user_meta( $user_id, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_id, 'manage_woocommerce' );
		if ( ! $checks['fixture_owner_verified'] ) {
			$errors[] = 'fixture_owner_unavailable';
			bds_staging_provider_cost_save_report( false, $checks, $errors );
			return;
		}

		wp_set_current_user( $user_id );
		$checks['non_admin_finance_denied'] = false === bds_provider_cost_admin_permission();

		$checks['invalid_provider_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'provider' => 'unknown-provider', 'idempotency' => 'staging-invalid-provider-v1' ) ) );
		$checks['invalid_product_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'product_slug' => 'unknown-product', 'idempotency' => 'staging-invalid-product-v1' ) ) );
		$checks['invalid_basis_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'basis' => 'estimated', 'idempotency' => 'staging-invalid-basis-v1' ) ) );
		$checks['invalid_currency_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'currency' => 'US', 'idempotency' => 'staging-invalid-currency-v1' ) ) );
		$checks['negative_amount_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'amount_minor' => -137, 'idempotency' => 'staging-negative-amount-v1' ) ) );
		$checks['fractional_amount_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'amount_minor' => 1.37, 'idempotency' => 'staging-fractional-amount-v1' ) ) );
		$checks['excessive_amount_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'amount_minor' => 100000001, 'idempotency' => 'staging-excessive-amount-v1' ) ) );
		$checks['negative_timestamp_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'occurred_at' => -1, 'idempotency' => 'staging-negative-time-v1' ) ) );
		$checks['future_timestamp_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'occurred_at' => time() + HOUR_IN_SECONDS, 'idempotency' => 'staging-future-time-v1' ) ) );
		$checks['short_idempotency_rejected'] = 0 === bds_provider_cost_record( bds_staging_provider_cost_event( array( 'idempotency' => 'short' ) ) );

		$before = bds_staging_provider_cost_summary_data();
		$cost_before = absint( $before['currency_summary']['USD']['verified_variable_cost_minor'] ?? 0 );
		$contribution_before = (int) ( $before['currency_summary']['USD']['recorded_contribution_minor'] ?? 0 );
		$provider_before = absint( $before['verified_cost_by_provider']['formwise']['USD'] ?? 0 );
		$product_before = absint( $before['verified_cost_by_product']['ai-business-essentials']['USD'] ?? 0 );

		$event = bds_staging_provider_cost_event();
		$cost_a = bds_provider_cost_record( $event );
		$cost_b = bds_provider_cost_record( $event );
		$event_key = bds_provider_cost_event_key( 'formwise', 'ai-business-essentials', 'USD', 137, $event['occurred_at'], $event['idempotency'] );
		$matching = get_posts(
			array(
				'post_type'      => 'bds_provider_cost',
				'post_status'    => 'private',
				'posts_per_page' => 3,
				'fields'         => 'ids',
				'meta_key'       => '_bds_cost_key',
				'meta_value'     => $event_key,
			)
		);
		$checks['valid_cost_stored'] = $cost_a > 0 && 'private' === get_post_status( $cost_a );
		$checks['duplicate_cost_reused'] = $cost_a > 0 && $cost_a === $cost_b;
		$checks['duplicate_cost_single_row'] = 1 === count( $matching ) && absint( $matching[0] ?? 0 ) === absint( $cost_a );
		$reference_hash = (string) get_post_meta( $cost_a, '_bds_cost_reference_hash', true );
		$checks['raw_reference_not_stored'] = 1 === preg_match( '/^[a-f0-9]{64}$/', $reference_hash )
			&& $event['idempotency'] !== $reference_hash
			&& '' === (string) get_post_meta( $cost_a, '_bds_cost_idempotency', true );
		$checks['untrusted_metadata_stripped'] = '' === (string) get_post_meta( $cost_a, '_bds_cost_email', true )
			&& '' === (string) get_post_meta( $cost_a, '_bds_cost_message', true );

		$after = bds_staging_provider_cost_summary_data();
		$cost_after = absint( $after['currency_summary']['USD']['verified_variable_cost_minor'] ?? 0 );
		$contribution_after = (int) ( $after['currency_summary']['USD']['recorded_contribution_minor'] ?? 0 );
		$provider_after = absint( $after['verified_cost_by_provider']['formwise']['USD'] ?? 0 );
		$product_after = absint( $after['verified_cost_by_product']['ai-business-essentials']['USD'] ?? 0 );
		$checks['provider_aggregate_delta_correct'] = 137 === $provider_after - $provider_before;
		$checks['product_aggregate_delta_correct'] = 137 === $product_after - $product_before;
		$checks['cost_aggregate_delta_correct'] = 137 === $cost_after - $cost_before;
		$checks['contribution_delta_correct'] = -137 === $contribution_after - $contribution_before;
		$checks['coverage_state_explicit'] = 'partial_unless_reconciled' === (string) ( $after['cost_coverage_state'] ?? '' )
			&& false !== strpos( (string) ( $after['note'] ?? '' ), 'not final profit' );
		$invalid_period = bds_provider_cost_period( '1999-99' );
		$checks['invalid_period_rejected'] = is_wp_error( $invalid_period ) && 'invalid_period' === $invalid_period->get_error_code();

		$complete = 24 === count( $checks ) && 24 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_provider_cost_save_report( $complete, $checks, $errors );
	} finally {
		wp_set_current_user( $original_user );
		delete_option( $run_lock );
	}
}
add_action( 'init', 'bds_staging_provider_cost_run', 103 );

function bds_staging_provider_cost_report() {
	$report = get_option( 'bds_staging_provider_cost_report_v1', array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_provider_cost_register_route() {
	if ( ! bds_staging_provider_cost_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/provider-cost-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_provider_cost_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_provider_cost_register_route' );
