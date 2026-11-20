<?php
/**
 * Plugin Name: BrandDad Staging Follow-up Consent Acceptance
 * Description: One-time, exact-host consent eligibility, token, opt-out, rate, and restoration acceptance without email delivery.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_FOLLOWUP_VERSION', '0.1.0' );
define( 'BDS_STAGING_FOLLOWUP_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_FOLLOWUP_REPORT_OPTION', 'bds_staging_followup_report_v1' );

function bds_staging_followup_is_host() {
	return BDS_STAGING_FOLLOWUP_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_followup_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'worker_default_off',
		'fixture_key_available',
		'source_allowlist',
		'fresh_consent_eligible',
		'stale_consent_rejected',
		'no_consent_rejected',
		'bad_status_rejected',
		'bad_email_rejected',
		'token_shape',
		'token_hash_only_storage',
		'token_lookup',
		'invalid_token_rejected',
		'unsubscribe_url_safe',
		'listing_destination_fixed',
		'saved_compare_and_set_rejected',
		'status_compare_and_set_rejected',
		'exact_update_succeeds',
		'opt_out_persisted',
		'opt_out_reactivation_blocked',
		'disabled_worker_noop',
		'first_thirty_rate_checks_allowed',
		'thirty_first_rate_check_rejected',
		'fixture_row_restored',
		'rate_counter_restored',
		'original_context_restored',
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
		BDS_STAGING_FOLLOWUP_REPORT_OPTION,
		array(
			'version'      => BDS_STAGING_FOLLOWUP_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_followup_restore_row( $key, $exists, $value ) {
	$lock = 'bds_lead_consent_lock';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) < time() - 60 ) {
			delete_option( $lock );
		}
		if ( ! add_option( $lock, time(), '', false ) ) {
			return false;
		}
	}
	try {
		$all = get_option( 'bds_follow_consent_v1', array() );
		$all = is_array( $all ) ? $all : array();
		if ( $exists ) {
			$all[ $key ] = $value;
		} else {
			unset( $all[ $key ] );
		}
		return update_option( 'bds_follow_consent_v1', $all, false ) || $all === get_option( 'bds_follow_consent_v1', array() );
	} finally {
		delete_option( $lock );
	}
}

function bds_staging_followup_row_matches( $key, $exists, $value ) {
	$all = get_option( 'bds_follow_consent_v1', array() );
	$all = is_array( $all ) ? $all : array();
	if ( (bool) array_key_exists( $key, $all ) !== (bool) $exists ) {
		return false;
	}
	return ! $exists || $all[ $key ] === $value;
}

function bds_staging_followup_restore_rate( $key, $value ) {
	delete_transient( $key );
	if ( false !== $value ) {
		set_transient( $key, $value, HOUR_IN_SECONDS );
	}
}

function bds_staging_followup_run() {
	if ( ! bds_staging_followup_is_host() || get_option( BDS_STAGING_FOLLOWUP_REPORT_OPTION, false ) ) {
		return;
	}
	$run_lock = 'bds_staging_followup_acceptance_lock_v1';
	if ( ! add_option( $run_lock, time(), '', false ) ) {
		if ( absint( get_option( $run_lock, 0 ) ) >= time() - 300 ) {
			return;
		}
		delete_option( $run_lock );
		if ( ! add_option( $run_lock, time(), '', false ) ) {
			return;
		}
	}

	$key                     = 'staging_follow_acceptance_v1';
	$email                   = 'staging-followup@example.invalid';
	$original_user           = get_current_user_id();
	$original_remote         = $_SERVER['REMOTE_ADDR'] ?? null;
	$test_ip                 = '203.0.113.224';
	$rate_key                = 'bds_follow_unsub_' . substr( hash_hmac( 'sha256', $test_ip, wp_salt( 'nonce' ) ), 0, 24 );
	$original_rate           = get_transient( $rate_key );
	$all                     = get_option( 'bds_follow_consent_v1', array() );
	$all                     = is_array( $all ) ? $all : array();
	$row_existed             = array_key_exists( $key, $all );
	$original_row            = $all[ $key ] ?? null;
	$row_restored            = false;
	$rate_restored           = false;
	$checks                  = array();
	$errors                  = array();

	try {
		$required = array( 'bds_follow_worker_ready', 'bds_follow_worker_source', 'bds_follow_worker_row_eligible', 'bds_follow_worker_ensure_unsubscribe_token', 'bds_follow_worker_find_unsubscribe', 'bds_follow_worker_unsubscribe_url', 'bds_follow_worker_destination_url', 'bds_follow_worker_update_row', 'bds_follow_worker_run', 'bds_follow_worker_unsubscribe_rate_limit' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_followup_save_report( false, $checks, $errors );
			return;
		}

		$checks['worker_default_off'] = false === bds_follow_worker_ready();
		$checks['fixture_key_available'] = ! $row_existed;
		if ( ! $checks['worker_default_off'] || ! $checks['fixture_key_available'] ) {
			$errors[] = 'worker_enabled_or_fixture_collision';
			bds_staging_followup_save_report( false, $checks, $errors );
			return;
		}

		$saved = gmdate( DATE_ATOM );
		$fixture_row = array(
			'email'   => $email,
			'consent' => 1,
			'status'  => 'interested',
			'source'  => 'audit',
			'saved'   => $saved,
			'lead_id' => 0,
		);
		$all[ $key ] = $fixture_row;
		if ( ! update_option( 'bds_follow_consent_v1', $all, false ) && $all !== get_option( 'bds_follow_consent_v1', array() ) ) {
			$errors[] = 'fixture_row_save_failed';
			bds_staging_followup_save_report( false, $checks, $errors );
			return;
		}

		$checks['source_allowlist'] = 'trial' === bds_follow_worker_source( 'trial' ) && 'help' === bds_follow_worker_source( 'not-allowed' );
		$checks['fresh_consent_eligible'] = bds_follow_worker_row_eligible( $fixture_row, time() );
		$stale_row = array_merge( $fixture_row, array( 'saved' => gmdate( DATE_ATOM, time() - ( 31 * DAY_IN_SECONDS ) ) ) );
		$checks['stale_consent_rejected'] = false === bds_follow_worker_row_eligible( $stale_row, time() );
		$checks['no_consent_rejected'] = false === bds_follow_worker_row_eligible( array_merge( $fixture_row, array( 'consent' => 0 ) ), time() );
		$checks['bad_status_rejected'] = false === bds_follow_worker_row_eligible( array_merge( $fixture_row, array( 'status' => 'sent' ) ), time() );
		$checks['bad_email_rejected'] = false === bds_follow_worker_row_eligible( array_merge( $fixture_row, array( 'email' => 'not-an-email' ) ), time() );

		$token_row = $fixture_row;
		$token = bds_follow_worker_ensure_unsubscribe_token( $token_row );
		$checks['token_shape'] = 1 === preg_match( '/^[a-f0-9]{48}$/', $token ) && 64 === strlen( (string) ( $token_row['unsubscribe_hash'] ?? '' ) );
		$stored = bds_follow_worker_update_row( $key, array( 'unsubscribe_hash' => $token_row['unsubscribe_hash'] ), array( 'unsubscribe_token_pending' ), $saved, 'interested' );
		$current = get_option( 'bds_follow_consent_v1', array() );
		$current_row = is_array( $current ) && is_array( $current[ $key ] ?? null ) ? $current[ $key ] : array();
		$checks['token_hash_only_storage'] = $stored && hash( 'sha256', $token ) === (string) ( $current_row['unsubscribe_hash'] ?? '' ) && ! isset( $current_row['unsubscribe_token_pending'] ) && false === strpos( wp_json_encode( $current_row ), $token );
		$checks['token_lookup'] = $key === bds_follow_worker_find_unsubscribe( $token, $current );
		$checks['invalid_token_rejected'] = '' === bds_follow_worker_find_unsubscribe( str_repeat( '0', 48 ), $current ) && '' === bds_follow_worker_find_unsubscribe( 'bad', $current );

		$unsubscribe_url = bds_follow_worker_unsubscribe_url( $token );
		$checks['unsubscribe_url_safe'] = 'https' === strtolower( (string) wp_parse_url( $unsubscribe_url, PHP_URL_SCHEME ) )
			&& BDS_STAGING_FOLLOWUP_HOST === strtolower( (string) wp_parse_url( $unsubscribe_url, PHP_URL_HOST ) )
			&& false !== strpos( $unsubscribe_url, 'action=bds_follow_unsubscribe' )
			&& false !== strpos( $unsubscribe_url, rawurlencode( $token ) );
		$checks['listing_destination_fixed'] = 'https://directory.branddad.social/add-listing/' === bds_follow_worker_destination_url( 'listing' );

		$before_cas = get_option( 'bds_follow_consent_v1', array() );
		$checks['saved_compare_and_set_rejected'] = false === bds_follow_worker_update_row( $key, array( 'source' => 'trial' ), array(), 'wrong-saved', 'interested' ) && $before_cas === get_option( 'bds_follow_consent_v1', array() );
		$checks['status_compare_and_set_rejected'] = false === bds_follow_worker_update_row( $key, array( 'source' => 'trial' ), array(), $saved, 'sending' ) && $before_cas === get_option( 'bds_follow_consent_v1', array() );
		$checks['exact_update_succeeds'] = bds_follow_worker_update_row( $key, array( 'source' => 'trial' ), array(), $saved, 'interested' );

		$opted_out = bds_follow_worker_update_row(
			$key,
			array( 'status' => 'unsubscribed', 'consent' => 0, 'unsubscribed_at' => gmdate( DATE_ATOM ) ),
			array( 'unsubscribe_hash', 'unsubscribe_token_pending', 'next_attempt' ),
			$saved,
			'interested'
		);
		$current = get_option( 'bds_follow_consent_v1', array() );
		$opted_row = is_array( $current ) && is_array( $current[ $key ] ?? null ) ? $current[ $key ] : array();
		$checks['opt_out_persisted'] = $opted_out && 'unsubscribed' === (string) ( $opted_row['status'] ?? '' ) && 0 === absint( $opted_row['consent'] ?? 1 ) && '' !== (string) ( $opted_row['unsubscribed_at'] ?? '' ) && ! isset( $opted_row['unsubscribe_hash'] );
		$checks['opt_out_reactivation_blocked'] = false === bds_follow_worker_update_row( $key, array( 'status' => 'interested', 'consent' => 1 ) ) && $current === get_option( 'bds_follow_consent_v1', array() );
		$before_worker = get_option( 'bds_follow_consent_v1', array() );
		bds_follow_worker_run();
		$checks['disabled_worker_noop'] = $before_worker === get_option( 'bds_follow_consent_v1', array() );

		$_SERVER['REMOTE_ADDR'] = $test_ip;
		delete_transient( $rate_key );
		$rate_results = array();
		for ( $index = 0; $index < 30; $index++ ) {
			$rate_results[] = bds_follow_worker_unsubscribe_rate_limit();
		}
		$rate_thirty_first = bds_follow_worker_unsubscribe_rate_limit();
		$checks['first_thirty_rate_checks_allowed'] = 30 === count( array_filter( $rate_results, static fn( $result ) => true === $result ) );
		$checks['thirty_first_rate_check_rejected'] = false === $rate_thirty_first;

		$row_restored = bds_staging_followup_restore_row( $key, $row_existed, $original_row );
		$checks['fixture_row_restored'] = $row_restored && bds_staging_followup_row_matches( $key, $row_existed, $original_row );
		bds_staging_followup_restore_rate( $rate_key, $original_rate );
		$rate_restored = true;
		$checks['rate_counter_restored'] = get_transient( $rate_key ) === $original_rate;
		if ( null === $original_remote ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $original_remote;
		}
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === $original_user && ( $_SERVER['REMOTE_ADDR'] ?? null ) === $original_remote;

		$complete = 26 === count( $checks ) && 26 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_followup_save_report( $complete, $checks, $errors );
	} finally {
		if ( ! $row_restored ) {
			bds_staging_followup_restore_row( $key, $row_existed, $original_row );
		}
		if ( ! $rate_restored ) {
			bds_staging_followup_restore_rate( $rate_key, $original_rate );
		}
		if ( null === $original_remote ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $original_remote;
		}
		wp_set_current_user( $original_user );
		delete_option( $run_lock );
	}
}
add_action( 'init', 'bds_staging_followup_run', 107 );

function bds_staging_followup_report() {
	$report = get_option( BDS_STAGING_FOLLOWUP_REPORT_OPTION, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_followup_register_route() {
	if ( ! bds_staging_followup_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/followup-consent-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_followup_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_followup_register_route' );
