<?php
/**
 * Plugin Name: BrandDad Staging Auth Acceptance
 * Description: One-time, exact-host authenticated ownership acceptance for the isolated Social staging site.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_AUTH_ACCEPTANCE_VERSION', '0.1.0' );
define( 'BDS_STAGING_AUTH_ACCEPTANCE_HOST', 'staging-branddad-social.stackstaging.com' );

/** The installed WordPress home URL, never the caller-controlled Host header. */
function bds_staging_auth_acceptance_is_host() {
	return BDS_STAGING_AUTH_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

/** Persist a customer-safe result with no ids, emails, credentials, or submitted content. */
function bds_staging_auth_acceptance_save_report( $complete, $checks, $errors = array() ) {
	$allowed_checks = array(
		'dependencies_loaded',
		'non_admin_fixture_users',
		'anonymous_permissions_denied',
		'authenticated_permissions_allowed',
		'onboarding_owner_update',
		'onboarding_stale_version_denied',
		'onboarding_cross_account_isolated',
		'ai_job_owner_read',
		'ai_job_cross_account_denied',
	);
	$safe_checks = array();
	foreach ( $allowed_checks as $key ) {
		$safe_checks[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 10 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option(
		'bds_staging_auth_acceptance_report_v1',
		array(
			'version'      => BDS_STAGING_AUTH_ACCEPTANCE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

/** Create or reuse only a fixture-marked subscriber. A collision fails closed. */
function bds_staging_auth_acceptance_user( $slot ) {
	$slot  = 'b' === $slot ? 'b' : 'a';
	$login = 'bds_stage_acceptance_' . $slot;
	$email = 'bds-stage-acceptance-' . $slot . '@example.invalid';
	$id    = username_exists( $login );
	if ( ! $id ) {
		$id = email_exists( $email );
	}
	if ( $id ) {
		return $slot === (string) get_user_meta( $id, '_bds_staging_auth_fixture', true ) ? absint( $id ) : new WP_Error( 'fixture_user_collision' );
	}
	$id = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 48, true, true ),
			'display_name' => 'BrandDad staging acceptance ' . strtoupper( $slot ),
			'role'         => 'subscriber',
		)
	);
	if ( is_wp_error( $id ) ) {
		return new WP_Error( 'fixture_user_create_failed' );
	}
	update_user_meta( $id, '_bds_staging_auth_fixture', $slot );
	return absint( $id );
}

/** Run once after all MU plugins have registered their types and functions. */
function bds_staging_auth_acceptance_run() {
	if ( ! bds_staging_auth_acceptance_is_host() || get_option( 'bds_staging_auth_acceptance_report_v1', false ) ) {
		return;
	}
	$lock = 'bds_staging_auth_acceptance_lock_v1';
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
		$required = array(
			'bds_onboarding_permission',
			'bds_onboarding_state',
			'bds_onboarding_update',
			'bds_onboarding_response',
			'bds_ai_exec_permission',
			'bds_ai_exec_job_response',
		);
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_auth_acceptance_save_report( false, $checks, $errors );
			return;
		}

		$user_a = bds_staging_auth_acceptance_user( 'a' );
		$user_b = bds_staging_auth_acceptance_user( 'b' );
		if ( is_wp_error( $user_a ) || is_wp_error( $user_b ) ) {
			$errors[] = is_wp_error( $user_a ) ? $user_a->get_error_code() : $user_b->get_error_code();
			bds_staging_auth_acceptance_save_report( false, $checks, $errors );
			return;
		}
		$fixture_a = new WP_User( $user_a );
		$fixture_b = new WP_User( $user_b );
		$checks['non_admin_fixture_users'] = ! $fixture_a->has_cap( 'manage_options' ) && ! $fixture_b->has_cap( 'manage_options' );

		wp_set_current_user( 0 );
		$checks['anonymous_permissions_denied'] = false === bds_onboarding_permission() && false === bds_ai_exec_permission();
		wp_set_current_user( $user_a );
		$checks['authenticated_permissions_allowed'] = true === bds_onboarding_permission() && true === bds_ai_exec_permission();

		$state_a = bds_onboarding_state( $user_a );
		$request_a = new WP_REST_Request( 'POST', '/branddad/v1/onboarding' );
		$request_a->set_param( 'version', absint( $state_a['version'] ) );
		$request_a->set_param(
			'profile',
			array(
				'business_name' => 'Staging Acceptance Alpha',
				'industry'      => 'Testing',
				'primary_goal'  => 'social',
				'brand_voice'   => 'clear',
			)
		);
		$updated_a = bds_onboarding_update( $request_a );
		$data_a = $updated_a instanceof WP_REST_Response ? $updated_a->get_data() : array();
		$checks['onboarding_owner_update'] = 'Staging Acceptance Alpha' === (string) ( $data_a['profile']['business_name'] ?? '' );
		$stale_a = bds_onboarding_update( $request_a );
		$checks['onboarding_stale_version_denied'] = is_wp_error( $stale_a ) && 'version_conflict' === $stale_a->get_error_code();

		wp_set_current_user( $user_b );
		$data_b = bds_onboarding_response( $user_b );
		$checks['onboarding_cross_account_isolated'] = 'Staging Acceptance Alpha' !== (string) ( $data_b['profile']['business_name'] ?? '' );

		$job_id = wp_insert_post(
			array(
				'post_type'   => 'bds_ai_job',
				'post_status' => 'private',
				'post_title'  => 'Staging ownership acceptance',
				'post_author' => $user_a,
			),
			true
		);
		if ( is_wp_error( $job_id ) ) {
			$errors[] = 'fixture_job_create_failed';
		} else {
			update_post_meta( $job_id, '_bds_staging_auth_fixture', '1' );
			update_post_meta( $job_id, '_bds_tool', 'social_draft' );
			update_post_meta( $job_id, '_bds_status', 'completed' );
			update_post_meta( $job_id, '_bds_credit_cost', 5 );
			update_post_meta( $job_id, '_bds_result', 'Staging acceptance result' );

			wp_set_current_user( $user_a );
			$owner_job = bds_ai_exec_job_response( $job_id );
			$checks['ai_job_owner_read'] = is_array( $owner_job ) && 'Staging acceptance result' === (string) ( $owner_job['result'] ?? '' );
			wp_set_current_user( $user_b );
			$other_job = bds_ai_exec_job_response( $job_id );
			$error_data = is_wp_error( $other_job ) ? $other_job->get_error_data() : array();
			$checks['ai_job_cross_account_denied'] = is_wp_error( $other_job ) && 'not_found' === $other_job->get_error_code() && 404 === absint( $error_data['status'] ?? 0 );
		}

		$complete = 9 === count( array_filter( $checks ) ) && 9 === count( $checks ) && empty( $errors );
		bds_staging_auth_acceptance_save_report( $complete, $checks, $errors );
	} finally {
		wp_set_current_user( $original_user );
		delete_option( $lock );
	}
}
add_action( 'init', 'bds_staging_auth_acceptance_run', 99 );

/** Public staging-only evidence contains booleans and error codes only. */
function bds_staging_auth_acceptance_report() {
	$report = get_option( 'bds_staging_auth_acceptance_report_v1', array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_auth_acceptance_register_route() {
	if ( ! bds_staging_auth_acceptance_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/auth-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_auth_acceptance_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_auth_acceptance_register_route' );
