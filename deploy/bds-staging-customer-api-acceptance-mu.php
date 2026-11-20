<?php
/**
 * Plugin Name: BrandDad Staging Customer API Acceptance
 * Description: One-time, exact-host onboarding validation/locking and customer value-report isolation acceptance.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_CUSTOMER_API_VERSION', '0.1.0' );
define( 'BDS_STAGING_CUSTOMER_API_HOST', 'staging-branddad-social.stackstaging.com' );

function bds_staging_customer_api_is_host() {
	return BDS_STAGING_CUSTOMER_API_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_customer_api_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_users_verified',
		'localhost_website_rejected',
		'private_ip_website_rejected',
		'oversized_field_rejected',
		'unsupported_enum_rejected',
		'unknown_only_update_rejected',
		'active_lock_rejected',
		'stale_lock_recovered',
		'version_incremented_once',
		'cross_account_profile_unchanged',
		'customer_completed_delta_correct',
		'customer_failed_delta_correct',
		'completed_credits_only',
		'cross_account_job_excluded',
		'status_response_safe',
		'invalid_report_period_rejected',
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
		'bds_staging_customer_api_report_v1',
		array(
			'version'      => BDS_STAGING_CUSTOMER_API_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_customer_api_request( $version, $profile ) {
	$request = new WP_REST_Request( 'POST', '/branddad/v1/onboarding' );
	$request->set_param( 'version', absint( $version ) );
	$request->set_param( 'profile', $profile );
	return $request;
}

function bds_staging_customer_api_job( $user_id, $status, $tool, $cost, $title ) {
	$job_id = wp_insert_post(
		array(
			'post_type'   => 'bds_ai_job',
			'post_status' => 'private',
			'post_title'  => $title,
			'post_author' => absint( $user_id ),
		),
		true
	);
	if ( is_wp_error( $job_id ) ) {
		return $job_id;
	}
	update_post_meta( $job_id, '_bds_staging_customer_api_fixture', '1' );
	update_post_meta( $job_id, '_bds_status', sanitize_key( $status ) );
	update_post_meta( $job_id, '_bds_tool', sanitize_key( $tool ) );
	update_post_meta( $job_id, '_bds_credit_cost', absint( $cost ) );
	return absint( $job_id );
}

function bds_staging_customer_api_run() {
	if ( ! bds_staging_customer_api_is_host() || get_option( 'bds_staging_customer_api_report_v1', false ) ) {
		return;
	}
	$run_lock = 'bds_staging_customer_api_lock_v1';
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
		$required = array( 'bds_onboarding_state', 'bds_onboarding_update', 'bds_onboarding_response', 'bds_customer_value_report', 'bds_customer_value_period', 'bds_customer_status_response' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_customer_api_save_report( false, $checks, $errors );
			return;
		}

		$user_a = username_exists( 'bds_stage_acceptance_a' );
		$user_b = username_exists( 'bds_stage_acceptance_b' );
		$checks['fixture_users_verified'] = $user_a && $user_b
			&& 'a' === (string) get_user_meta( $user_a, '_bds_staging_auth_fixture', true )
			&& 'b' === (string) get_user_meta( $user_b, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_a, 'manage_options' ) && ! user_can( $user_b, 'manage_options' );
		if ( ! $checks['fixture_users_verified'] ) {
			$errors[] = 'fixture_users_unavailable';
			bds_staging_customer_api_save_report( false, $checks, $errors );
			return;
		}

		wp_set_current_user( $user_b );
		$state_b = bds_onboarding_state( $user_b );
		$version = absint( $state_b['version'] );
		$localhost = bds_onboarding_update( bds_staging_customer_api_request( $version, array( 'website_url' => 'http://localhost/private' ) ) );
		$private_ip = bds_onboarding_update( bds_staging_customer_api_request( $version, array( 'website_url' => 'http://127.0.0.1/private' ) ) );
		$oversized = bds_onboarding_update( bds_staging_customer_api_request( $version, array( 'business_name' => str_repeat( 'A', 121 ) ) ) );
		$bad_enum = bds_onboarding_update( bds_staging_customer_api_request( $version, array( 'primary_goal' => 'unsupported' ) ) );
		$unknown = bds_onboarding_update( bds_staging_customer_api_request( $version, array( 'not_allowlisted' => 'ignored' ) ) );
		$checks['localhost_website_rejected'] = is_wp_error( $localhost ) && 'invalid_website' === $localhost->get_error_code();
		$checks['private_ip_website_rejected'] = is_wp_error( $private_ip ) && 'invalid_website' === $private_ip->get_error_code();
		$checks['oversized_field_rejected'] = is_wp_error( $oversized ) && 'invalid_profile' === $oversized->get_error_code();
		$checks['unsupported_enum_rejected'] = is_wp_error( $bad_enum ) && 'invalid_profile' === $bad_enum->get_error_code();
		$checks['unknown_only_update_rejected'] = is_wp_error( $unknown ) && 'empty_update' === $unknown->get_error_code();

		$profile = array(
			'business_name' => 'Staging Acceptance Beta',
			'website_url'   => 'https://example.com/',
			'industry'      => 'Testing',
			'service_area'  => 'Cancun',
			'primary_goal'  => 'website',
			'brand_voice'   => 'friendly',
		);
		$onboarding_lock = 'bds_onboarding_lock_' . absint( $user_b );
		update_option( $onboarding_lock, time(), false );
		$busy = bds_onboarding_update( bds_staging_customer_api_request( $version, $profile ) );
		$checks['active_lock_rejected'] = is_wp_error( $busy ) && 'onboarding_busy' === $busy->get_error_code();
		update_option( $onboarding_lock, time() - 31, false );
		$recovered = bds_onboarding_update( bds_staging_customer_api_request( $version, $profile ) );
		$recovered_data = $recovered instanceof WP_REST_Response ? $recovered->get_data() : array();
		$checks['stale_lock_recovered'] = 'Staging Acceptance Beta' === (string) ( $recovered_data['profile']['business_name'] ?? '' ) && 'https://example.com/' === (string) ( $recovered_data['profile']['website_url'] ?? '' );
		$checks['version_incremented_once'] = $version + 1 === absint( $recovered_data['version'] ?? 0 );
		$state_a = bds_onboarding_state( $user_a );
		$checks['cross_account_profile_unchanged'] = 'Staging Acceptance Alpha' === (string) ( $state_a['profile']['business_name'] ?? '' );

		$period = gmdate( 'Y-m' );
		$before = bds_customer_value_report( $user_b, $period );
		$before_completed = absint( $before['ai_work']['tasks']['completed'] ?? 0 );
		$before_failed = absint( $before['ai_work']['tasks']['failed'] ?? 0 );
		$before_credits = absint( $before['ai_work']['credits_committed'] ?? 0 );
		$job_completed = bds_staging_customer_api_job( $user_b, 'completed', 'website_plan', 5, 'Staging customer report completed' );
		$job_failed = bds_staging_customer_api_job( $user_b, 'failed', 'website_plan', 5, 'Staging customer report failed' );
		$job_other = bds_staging_customer_api_job( $user_a, 'completed', 'website_plan', 5, 'Staging cross-account report fixture' );
		if ( is_wp_error( $job_completed ) || is_wp_error( $job_failed ) || is_wp_error( $job_other ) ) {
			$errors[] = 'fixture_job_create_failed';
		} else {
			$after = bds_customer_value_report( $user_b, $period );
			$checks['customer_completed_delta_correct'] = 1 === absint( $after['ai_work']['tasks']['completed'] ?? 0 ) - $before_completed;
			$checks['customer_failed_delta_correct'] = 1 === absint( $after['ai_work']['tasks']['failed'] ?? 0 ) - $before_failed;
			$checks['completed_credits_only'] = 5 === absint( $after['ai_work']['credits_committed'] ?? 0 ) - $before_credits;
			$checks['cross_account_job_excluded'] = 1 === absint( $after['ai_work']['completed_by_tool']['website_plan']['completed'] ?? 0 ) - absint( $before['ai_work']['completed_by_tool']['website_plan']['completed'] ?? 0 );
		}

		$status = bds_customer_status_response( $user_b );
		$status_json = wp_json_encode( $status );
		$checks['status_response_safe'] = absint( $status['account']['user_id'] ?? 0 ) === absint( $user_b )
			&& 'none' === (string) ( $status['access']['mode'] ?? '' )
			&& false === strpos( $status_json, 'formwise' )
			&& false === strpos( $status_json, 'reservation' )
			&& false === strpos( $status_json, 'ledger' );
		$invalid_period = bds_customer_value_period( '1999-99' );
		$checks['invalid_report_period_rejected'] = is_wp_error( $invalid_period ) && 'invalid_period' === $invalid_period->get_error_code();

		$complete = 17 === count( $checks ) && 17 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_customer_api_save_report( $complete, $checks, $errors );
	} finally {
		wp_set_current_user( $original_user );
		delete_option( $run_lock );
	}
}
add_action( 'init', 'bds_staging_customer_api_run', 102 );

function bds_staging_customer_api_report() {
	$report = get_option( 'bds_staging_customer_api_report_v1', array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_customer_api_register_route() {
	if ( ! bds_staging_customer_api_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/customer-api-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_customer_api_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_customer_api_register_route' );
