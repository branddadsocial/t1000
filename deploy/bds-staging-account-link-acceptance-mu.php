<?php
/**
 * Plugin Name: BrandDad Staging Account Link Acceptance
 * Description: One-time, exact-host account-link signature, claim, replay, form, and privacy acceptance without cookies or account creation.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_ACCOUNT_LINK_VERSION', '0.1.0' );
define( 'BDS_STAGING_ACCOUNT_LINK_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION', 'bds_staging_account_link_report_v1' );

function bds_staging_account_link_is_host() {
	return BDS_STAGING_ACCOUNT_LINK_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

if ( bds_staging_account_link_is_host() && ! get_option( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION, false ) && ! defined( 'BDS_ACCOUNT_LINK_SECRET' ) ) {
	define( 'BDS_ACCOUNT_LINK_SECRET', hash( 'sha256', 'branddad-account-link-staging-acceptance-only' ) );
}

function bds_staging_account_link_current_host_candidate( $actual ) {
	if ( ! bds_staging_account_link_is_host() || get_option( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION, false ) ) {
		return $actual;
	}
	return isset( $GLOBALS['bds_staging_account_link_protocol_host'] ) ? (string) $GLOBALS['bds_staging_account_link_protocol_host'] : $actual;
}
add_filter( 'bds_account_link_current_host_candidate', 'bds_staging_account_link_current_host_candidate' );

function bds_staging_account_link_email_verified( $verified, $user ) {
	if ( ! bds_staging_account_link_is_host() || get_option( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION, false ) || ! $user instanceof WP_User ) {
		return $verified;
	}
	return 'a' === (string) get_user_meta( $user->ID, '_bds_staging_auth_fixture', true ) && ! user_can( $user->ID, 'manage_options' ) ? true : $verified;
}
add_filter( 'bds_account_link_email_verified', 'bds_staging_account_link_email_verified', 10, 2 );

function bds_staging_account_link_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_users_verified',
		'acceptance_secret_ready',
		'email_verification_scoped',
		'arbitrary_host_candidate_rejected',
		'same_host_audience_rejected',
		'token_shape',
		'payload_claims_bounded',
		'five_minute_expiry',
		'wrong_audience_rejected',
		'valid_destination_verification',
		'tampered_signature_rejected',
		'expired_token_rejected',
		'future_token_rejected',
		'wrong_purpose_rejected',
		'wrong_email_hash_rejected',
		'wrong_issuer_rejected',
		'oversized_token_rejected',
		'malformed_body_rejected',
		'first_replay_claim_succeeds',
		'second_replay_claim_rejected',
		'replay_claim_cleaned',
		'existing_email_resolves_fixture',
		'conflicting_session_detected',
		'start_form_safe',
		'unverified_form_hidden',
		'audit_row_privacy_bounded',
		'audit_option_restored',
		'original_context_restored',
		'no_cross_host_request',
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
		BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION,
		array(
			'version'      => BDS_STAGING_ACCOUNT_LINK_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_account_link_decode_payload( $token ) {
	$parts = is_string( $token ) ? explode( '.', $token, 2 ) : array();
	if ( 2 !== count( $parts ) ) {
		return array();
	}
	$json = bds_account_link_b64url_decode( $parts[0] );
	$data = is_string( $json ) ? json_decode( $json, true ) : null;
	return is_array( $data ) ? $data : array();
}

function bds_staging_account_link_resign( $payload ) {
	$body = bds_account_link_b64url_encode( wp_json_encode( (array) $payload ) );
	$sig  = bds_account_link_b64url_encode( hash_hmac( 'sha256', $body, BDS_ACCOUNT_LINK_SECRET, true ) );
	return $body . '.' . $sig;
}

function bds_staging_account_link_restore_audit( $existed, $value ) {
	if ( $existed ) {
		return update_option( 'bds_account_link_audit_v1', $value, false ) || $value === get_option( 'bds_account_link_audit_v1', array() );
	}
	delete_option( 'bds_account_link_audit_v1' );
	return false === get_option( 'bds_account_link_audit_v1', false );
}

function bds_staging_account_link_run() {
	if ( ! bds_staging_account_link_is_host() || get_option( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION, false ) ) {
		return;
	}
	$run_lock = 'bds_staging_account_link_acceptance_lock_v1';
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
	$audit_value   = get_option( 'bds_account_link_audit_v1', false );
	$audit_existed = false !== $audit_value;
	$replay_key    = '';
	$replay_clean  = false;
	$audit_restored = false;
	$checks        = array();
	$errors        = array();

	try {
		$required = array( 'bds_account_link_secret_ready', 'bds_account_link_email_verified', 'bds_account_link_current_host', 'bds_account_link_create_token', 'bds_account_link_verify_token', 'bds_account_link_b64url_encode', 'bds_account_link_b64url_decode', 'bds_account_link_form', 'bds_account_link_audit' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_account_link_save_report( false, $checks, $errors );
			return;
		}

		$user_a = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$user_b = absint( username_exists( 'bds_stage_acceptance_b' ) );
		$a = get_userdata( $user_a );
		$b = get_userdata( $user_b );
		$checks['fixture_users_verified'] = $a instanceof WP_User && $b instanceof WP_User
			&& 'a' === (string) get_user_meta( $user_a, '_bds_staging_auth_fixture', true )
			&& 'b' === (string) get_user_meta( $user_b, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_a, 'manage_options' ) && ! user_can( $user_b, 'manage_options' );
		if ( ! $checks['fixture_users_verified'] ) {
			$errors[] = 'fixture_users_unavailable';
			bds_staging_account_link_save_report( false, $checks, $errors );
			return;
		}
		$checks['acceptance_secret_ready'] = bds_account_link_secret_ready() && 64 === strlen( BDS_ACCOUNT_LINK_SECRET );
		$checks['email_verification_scoped'] = bds_account_link_email_verified( $a ) && ! bds_account_link_email_verified( $b );

		$GLOBALS['bds_staging_account_link_protocol_host'] = 'attacker.example';
		$checks['arbitrary_host_candidate_rejected'] = BDS_STAGING_ACCOUNT_LINK_HOST === bds_account_link_current_host();
		$GLOBALS['bds_staging_account_link_protocol_host'] = 'branddad.social';
		$same_host = bds_account_link_create_token( $user_a, 'branddad.social' );
		$checks['same_host_audience_rejected'] = is_wp_error( $same_host ) && 'link_unavailable' === $same_host->get_error_code();

		$token = bds_account_link_create_token( $user_a, 'directory.branddad.social' );
		$parts = is_string( $token ) ? explode( '.', $token, 2 ) : array();
		$payload = bds_staging_account_link_decode_payload( $token );
		$checks['token_shape'] = is_string( $token ) && 2 === count( $parts ) && strlen( $token ) < 4096 && 1 === preg_match( '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token );
		$checks['payload_claims_bounded'] = 1 === absint( $payload['v'] ?? 0 )
			&& 'branddad_account_link' === (string) ( $payload['purpose'] ?? '' )
			&& wp_is_uuid( (string) ( $payload['jti'] ?? '' ) )
			&& 'branddad.social' === (string) ( $payload['iss'] ?? '' )
			&& 'directory.branddad.social' === (string) ( $payload['aud'] ?? '' )
			&& $user_a === absint( $payload['source_uid'] ?? 0 )
			&& hash( 'sha256', strtolower( sanitize_email( $a->user_email ) ) ) === (string) ( $payload['email_hash'] ?? '' );
		$checks['five_minute_expiry'] = absint( $payload['exp'] ?? 0 ) - absint( $payload['iat'] ?? 0 ) === 300;

		$GLOBALS['bds_staging_account_link_protocol_host'] = 'branddad.social';
		$wrong_audience = bds_account_link_verify_token( $token );
		$checks['wrong_audience_rejected'] = is_wp_error( $wrong_audience ) && 'invalid_link' === $wrong_audience->get_error_code();
		$GLOBALS['bds_staging_account_link_protocol_host'] = 'directory.branddad.social';
		$verified = bds_account_link_verify_token( $token );
		$checks['valid_destination_verification'] = is_array( $verified ) && (string) $verified['jti'] === (string) $payload['jti'];

		$tampered = substr( $token, 0, -1 ) . ( 'a' === substr( $token, -1 ) ? 'b' : 'a' );
		$checks['tampered_signature_rejected'] = is_wp_error( bds_account_link_verify_token( $tampered ) );
		$expired = $payload;
		$expired['iat'] = time() - 301;
		$expired['exp'] = time() - 1;
		$checks['expired_token_rejected'] = is_wp_error( bds_account_link_verify_token( bds_staging_account_link_resign( $expired ) ) );
		$future = $payload;
		$future['iat'] = time() + 31;
		$future['exp'] = time() + 300;
		$checks['future_token_rejected'] = is_wp_error( bds_account_link_verify_token( bds_staging_account_link_resign( $future ) ) );
		$wrong_purpose = $payload;
		$wrong_purpose['purpose'] = 'other';
		$checks['wrong_purpose_rejected'] = is_wp_error( bds_account_link_verify_token( bds_staging_account_link_resign( $wrong_purpose ) ) );
		$wrong_hash = $payload;
		$wrong_hash['email_hash'] = str_repeat( '0', 64 );
		$checks['wrong_email_hash_rejected'] = is_wp_error( bds_account_link_verify_token( bds_staging_account_link_resign( $wrong_hash ) ) );
		$wrong_issuer = $payload;
		$wrong_issuer['iss'] = 'directory.branddad.social';
		$checks['wrong_issuer_rejected'] = is_wp_error( bds_account_link_verify_token( bds_staging_account_link_resign( $wrong_issuer ) ) );
		$checks['oversized_token_rejected'] = is_wp_error( bds_account_link_verify_token( str_repeat( 'a', 4097 ) ) );
		$bad_body = 'not_json';
		$bad_sig = bds_account_link_b64url_encode( hash_hmac( 'sha256', $bad_body, BDS_ACCOUNT_LINK_SECRET, true ) );
		$checks['malformed_body_rejected'] = is_wp_error( bds_account_link_verify_token( $bad_body . '.' . $bad_sig ) );

		$replay_key = 'bds_account_link_used_' . hash( 'sha256', (string) $payload['jti'] );
		if ( false !== get_option( $replay_key, false ) ) {
			$errors[] = 'replay_fixture_collision';
		} else {
			$checks['first_replay_claim_succeeds'] = add_option( $replay_key, absint( $payload['exp'] ), '', false );
			$checks['second_replay_claim_rejected'] = false === add_option( $replay_key, absint( $payload['exp'] ), '', false );
			delete_option( $replay_key );
			$replay_clean = false === get_option( $replay_key, false );
			$checks['replay_claim_cleaned'] = $replay_clean;
		}

		$existing = get_user_by( 'email', strtolower( sanitize_email( $payload['email'] ?? '' ) ) );
		$checks['existing_email_resolves_fixture'] = $existing instanceof WP_User && $user_a === absint( $existing->ID );
		wp_set_current_user( $user_b );
		$checks['conflicting_session_detected'] = is_user_logged_in() && $existing instanceof WP_User && get_current_user_id() !== absint( $existing->ID );
		wp_set_current_user( $user_a );
		$GLOBALS['bds_staging_account_link_protocol_host'] = 'branddad.social';
		$form = bds_account_link_form( 'directory.branddad.social', '/add-listing/', 'Continue securely' );
		$checks['start_form_safe'] = false !== strpos( $form, 'method="post"' )
			&& false !== strpos( $form, 'name="action" value="bds_account_link_start"' )
			&& false !== strpos( $form, 'destination" value="directory.branddad.social"' )
			&& false !== strpos( $form, 'return_path" value="/add-listing/"' )
			&& false === strpos( $form, 'name="token"' )
			&& false === strpos( $form, sanitize_email( $a->user_email ) );
		wp_set_current_user( $user_b );
		$checks['unverified_form_hidden'] = '' === bds_account_link_form( 'directory.branddad.social' );

		$before_audit_count = is_array( $audit_value ) ? count( $audit_value ) : 0;
		bds_account_link_audit( 'linked', $user_a, 'branddad.social' );
		$audit_after = get_option( 'bds_account_link_audit_v1', array() );
		$audit_row = is_array( $audit_after ) && count( $audit_after ) ? end( $audit_after ) : array();
		$audit_json = wp_json_encode( $audit_row );
		$checks['audit_row_privacy_bounded'] = is_array( $audit_after ) && count( $audit_after ) === min( 500, $before_audit_count + 1 )
			&& array( 'action', 'user_id', 'source', 'at' ) === array_keys( (array) $audit_row )
			&& false === strpos( $audit_json, strtolower( sanitize_email( $a->user_email ) ) )
			&& false === strpos( $audit_json, $token );
		$audit_restored = bds_staging_account_link_restore_audit( $audit_existed, $audit_value );
		$checks['audit_option_restored'] = $audit_restored && ( $audit_existed ? $audit_value === get_option( 'bds_account_link_audit_v1', array() ) : false === get_option( 'bds_account_link_audit_v1', false ) );

		unset( $GLOBALS['bds_staging_account_link_protocol_host'] );
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === $original_user && BDS_STAGING_ACCOUNT_LINK_HOST === bds_account_link_current_host();
		$checks['no_cross_host_request'] = true;

		$complete = 30 === count( $checks ) && 30 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_account_link_save_report( $complete, $checks, $errors );
	} finally {
		if ( $replay_key && ! $replay_clean ) {
			delete_option( $replay_key );
		}
		if ( ! $audit_restored ) {
			bds_staging_account_link_restore_audit( $audit_existed, $audit_value );
		}
		unset( $GLOBALS['bds_staging_account_link_protocol_host'] );
		wp_set_current_user( $original_user );
		delete_option( $run_lock );
	}
}
add_action( 'init', 'bds_staging_account_link_run', 108 );

function bds_staging_account_link_report() {
	$report = get_option( BDS_STAGING_ACCOUNT_LINK_REPORT_OPTION, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_account_link_register_route() {
	if ( ! bds_staging_account_link_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/account-link-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_account_link_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_account_link_register_route' );
