<?php
/**
 * Plugin Name: BrandDad Staging AI Failure Acceptance
 * Description: One-time, exact-host proof that unconfigured provider work fails closed and releases its credit reservation.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_AI_FAILURE_VERSION', '0.1.0' );
define( 'BDS_STAGING_AI_FAILURE_HOST', 'staging-branddad-social.stackstaging.com' );

function bds_staging_ai_failure_is_host() {
	return BDS_STAGING_AI_FAILURE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

/** Store boolean-only staging evidence. */
function bds_staging_ai_failure_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_owner_verified',
		'provider_absent',
		'credit_reserved_before_run',
		'job_failed_closed',
		'failure_code_recorded',
		'credit_reservation_released',
		'used_credits_unchanged',
		'replay_did_not_double_deduct',
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
		'bds_staging_ai_failure_report_v1',
		array(
			'version'      => BDS_STAGING_AI_FAILURE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_ai_failure_run() {
	if ( ! bds_staging_ai_failure_is_host() || get_option( 'bds_staging_ai_failure_report_v1', false ) ) {
		return;
	}
	$lock = 'bds_staging_ai_failure_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 300 ) {
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
		$required = array( 'bds_ai_exec_paid_ledger_key', 'bds_ai_exec_paid_reserve', 'bds_ai_exec_process_job' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_ai_failure_save_report( false, $checks, $errors );
			return;
		}

		$user_id = username_exists( 'bds_stage_acceptance_a' );
		$checks['fixture_owner_verified'] = $user_id && 'a' === (string) get_user_meta( $user_id, '_bds_staging_auth_fixture', true ) && ! user_can( $user_id, 'manage_options' );
		if ( ! $checks['fixture_owner_verified'] ) {
			$errors[] = 'fixture_owner_unavailable';
			bds_staging_ai_failure_save_report( false, $checks, $errors );
			return;
		}

		$provider_key = defined( 'BDS_FORMWISE_EXECUTION_KEY' ) ? trim( (string) BDS_FORMWISE_EXECUTION_KEY ) : '';
		$checks['provider_absent'] = '' === $provider_key;
		if ( ! $checks['provider_absent'] ) {
			$errors[] = 'provider_is_configured';
			bds_staging_ai_failure_save_report( false, $checks, $errors );
			return;
		}

		$plan = array(
			'subscription_id' => 990001,
			'allowance_cycle' => 1,
			'access_mode'     => 'paid',
			'credits'         => 25,
			'plan_id'         => 'staging-failure-fixture',
		);
		$reservation = bds_ai_exec_paid_reserve( $user_id, $plan, 5 );
		if ( is_wp_error( $reservation ) ) {
			$errors[] = 'reservation_failed';
			bds_staging_ai_failure_save_report( false, $checks, $errors );
			return;
		}
		$ledger_key = bds_ai_exec_paid_ledger_key( $user_id, $plan );
		$before = get_option( $ledger_key, array() );
		$checks['credit_reserved_before_run'] = isset( $before['pending'][ $reservation ] ) && 5 === absint( $before['pending'][ $reservation ]['cost'] ?? 0 ) && 0 === absint( $before['used'] ?? 0 );

		$job_id = wp_insert_post(
			array(
				'post_type'   => 'bds_ai_job',
				'post_status' => 'private',
				'post_title'  => 'Staging provider failure acceptance',
				'post_author' => absint( $user_id ),
			),
			true
		);
		if ( is_wp_error( $job_id ) ) {
			$errors[] = 'fixture_job_create_failed';
			bds_ai_exec_paid_finish( $user_id, $plan, $reservation, false );
			bds_staging_ai_failure_save_report( false, $checks, $errors );
			return;
		}
		update_post_meta( $job_id, '_bds_staging_ai_failure_fixture', '1' );
		update_post_meta( $job_id, '_bds_tool', 'social_draft' );
		update_post_meta( $job_id, '_bds_inputs', wp_json_encode( array() ) );
		update_post_meta( $job_id, '_bds_status', 'pending' );
		update_post_meta( $job_id, '_bds_credit_cost', 5 );
		update_post_meta( $job_id, '_bds_access_mode', 'paid' );
		update_post_meta( $job_id, '_bds_subscription_id', $plan['subscription_id'] );
		update_post_meta( $job_id, '_bds_allowance_cycle', $plan['allowance_cycle'] );
		update_post_meta( $job_id, '_bds_reservation', $reservation );

		bds_ai_exec_process_job( $job_id );
		$after = get_option( $ledger_key, array() );
		$checks['job_failed_closed'] = 'failed' === (string) get_post_meta( $job_id, '_bds_status', true );
		$checks['failure_code_recorded'] = 'execution_not_configured' === (string) get_post_meta( $job_id, '_bds_error_code', true );
		$checks['credit_reservation_released'] = ! isset( $after['pending'][ $reservation ] );
		$checks['used_credits_unchanged'] = 0 === absint( $after['used'] ?? 0 );

		bds_ai_exec_process_job( $job_id );
		$replayed = get_option( $ledger_key, array() );
		$checks['replay_did_not_double_deduct'] = 'failed' === (string) get_post_meta( $job_id, '_bds_status', true ) && ! isset( $replayed['pending'][ $reservation ] ) && 0 === absint( $replayed['used'] ?? 0 );

		$complete = 9 === count( $checks ) && 9 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_ai_failure_save_report( $complete, $checks, $errors );
	} finally {
		delete_option( $lock );
	}
}
add_action( 'init', 'bds_staging_ai_failure_run', 100 );

function bds_staging_ai_failure_report() {
	$report = get_option( 'bds_staging_ai_failure_report_v1', array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_ai_failure_register_route() {
	if ( ! bds_staging_ai_failure_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/ai-failure-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_ai_failure_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_ai_failure_register_route' );
