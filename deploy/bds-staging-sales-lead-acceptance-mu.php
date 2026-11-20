<?php
/**
 * Plugin Name: BrandDad Staging Sales Lead Acceptance
 * Description: One-time, exact-host lead validation, consent, deduplication, lifecycle, retention, and cleanup acceptance.
 * Version: 0.1.1
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_LEADS_VERSION', '0.1.1' );
define( 'BDS_STAGING_LEADS_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_LEADS_REPORT_OPTION', 'bds_staging_sales_leads_report_v2' );

function bds_staging_leads_is_host() {
	return BDS_STAGING_LEADS_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

if ( bds_staging_leads_is_host() && ! get_option( BDS_STAGING_LEADS_REPORT_OPTION, false ) && ! defined( 'BDS_LEAD_CAPTURE_ENABLED' ) ) {
	define( 'BDS_LEAD_CAPTURE_ENABLED', true );
}

function bds_staging_leads_verified_email( $verified, $user ) {
	if ( ! bds_staging_leads_is_host() || get_option( BDS_STAGING_LEADS_REPORT_OPTION, false ) || ! $user instanceof WP_User ) {
		return $verified;
	}
	return 'b' === (string) get_user_meta( $user->ID, '_bds_staging_auth_fixture', true ) && ! user_can( $user->ID, 'manage_options' ) ? true : $verified;
}
add_filter( 'bds_lead_email_verified', 'bds_staging_leads_verified_email', 10, 2 );

function bds_staging_leads_retention_candidates( $ids ) {
	return isset( $GLOBALS['bds_staging_lead_retention_ids'] ) && is_array( $GLOBALS['bds_staging_lead_retention_ids'] ) ? $GLOBALS['bds_staging_lead_retention_ids'] : $ids;
}

function bds_staging_leads_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_users_verified',
		'capture_enabled',
		'fixture_collision_absent',
		'subscriber_admin_denied',
		'invalid_origin_rejected',
		'oversized_body_rejected',
		'owned_origin_allowed',
		'invalid_email_rejected',
		'privacy_required',
		'invalid_source_rejected',
		'field_bounds_enforced',
		'unverified_capture_created',
		'anonymous_lead_private',
		'unverified_consent_not_queued',
		'public_response_safe',
		'recommendation_catalog_owned',
		'duplicate_reuses_reference',
		'duplicate_single_record',
		'signed_email_mismatch_rejected',
		'fixture_email_verified_filter',
		'consent_lead_private',
		'verified_consent_mirrored',
		'lifecycle_monotonic',
		'unsubscribe_propagates',
		'retention_anonymizes_unsubscribed',
		'suppression_preserved',
		'anonymous_retention_anonymizes',
		'first_five_rate_checks_allowed',
		'sixth_rate_check_rejected',
		'fixture_posts_deleted',
		'consent_rows_restored',
		'rate_counters_restored',
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
		BDS_STAGING_LEADS_REPORT_OPTION,
		array(
			'version'      => BDS_STAGING_LEADS_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_leads_request( $overrides = array() ) {
	$payload = array_merge(
		array(
			'email'         => 'staging-lead@example.invalid',
			'business_type' => 'Local service business',
			'primary_need'  => 'Need help creating social content',
			'source'        => 'audit',
			'privacy_ack'   => true,
			'email_consent' => true,
		),
		$overrides
	);
	$request = new WP_REST_Request( 'POST', '/branddad/v1/sales/leads' );
	foreach ( $payload as $key => $value ) {
		$request->set_param( $key, $value );
	}
	$request->set_body( wp_json_encode( $payload ) );
	$request->set_header( 'Origin', 'https://branddad.social' );
	return $request;
}

function bds_staging_leads_find_by_reference( $reference ) {
	$ids = get_posts(
		array(
			'post_type'      => 'bds_sales_lead',
			'post_status'    => 'private',
			'posts_per_page' => 2,
			'fields'         => 'ids',
			'meta_key'       => '_bds_lead_reference',
			'meta_value'     => sanitize_text_field( (string) $reference ),
		)
	);
	return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
}

function bds_staging_leads_consent_snapshot( $email ) {
	$email = strtolower( sanitize_email( $email ) );
	$all   = get_option( 'bds_follow_consent_v1', array() );
	$all   = is_array( $all ) ? $all : array();
	$key   = 'lead_' . substr( bds_lead_email_hash( $email ), 0, 32 );
	foreach ( $all as $candidate_key => $row ) {
		if ( is_array( $row ) && hash_equals( strtolower( sanitize_email( $row['email'] ?? '' ) ), $email ) ) {
			$key = (string) $candidate_key;
			break;
		}
	}
	return array( 'key' => $key, 'exists' => array_key_exists( $key, $all ), 'value' => $all[ $key ] ?? null );
}

function bds_staging_leads_restore_consent_rows( $snapshots ) {
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
		foreach ( (array) $snapshots as $snapshot ) {
			$key = (string) ( $snapshot['key'] ?? '' );
			if ( 1 !== preg_match( '/^[a-zA-Z0-9_-]{1,80}$/', $key ) ) {
				return false;
			}
			if ( ! empty( $snapshot['exists'] ) ) {
				$all[ $key ] = $snapshot['value'];
			} else {
				unset( $all[ $key ] );
			}
		}
		return update_option( 'bds_follow_consent_v1', $all, false ) || $all === get_option( 'bds_follow_consent_v1', array() );
	} finally {
		delete_option( $lock );
	}
}

function bds_staging_leads_consent_rows_match( $snapshots ) {
	$all = get_option( 'bds_follow_consent_v1', array() );
	$all = is_array( $all ) ? $all : array();
	foreach ( (array) $snapshots as $snapshot ) {
		$key = (string) ( $snapshot['key'] ?? '' );
		if ( (bool) array_key_exists( $key, $all ) !== (bool) ( $snapshot['exists'] ?? false ) ) {
			return false;
		}
		if ( ! empty( $snapshot['exists'] ) && $all[ $key ] !== $snapshot['value'] ) {
			return false;
		}
	}
	return true;
}

function bds_staging_leads_cleanup_posts( $post_authors ) {
	$clean = true;
	foreach ( (array) $post_authors as $post_id => $expected_author ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			continue;
		}
		if ( 'bds_sales_lead' !== $post->post_type || absint( $post->post_author ) !== absint( $expected_author ) || '1' !== (string) get_post_meta( $post_id, '_bds_staging_lead_fixture', true ) ) {
			$clean = false;
			continue;
		}
		if ( ! wp_delete_post( $post_id, true ) ) {
			$clean = false;
		}
	}
	return $clean;
}

function bds_staging_leads_restore_rate( $key, $value, $ttl ) {
	delete_transient( $key );
	if ( false !== $value ) {
		set_transient( $key, $value, $ttl );
	}
}

function bds_staging_leads_run() {
	if ( ! bds_staging_leads_is_host() || get_option( BDS_STAGING_LEADS_REPORT_OPTION, false ) ) {
		return;
	}
	$run_lock = 'bds_staging_sales_leads_acceptance_lock_v1';
	if ( ! add_option( $run_lock, time(), '', false ) ) {
		if ( absint( get_option( $run_lock, 0 ) ) >= time() - 300 ) {
			return;
		}
		delete_option( $run_lock );
		if ( ! add_option( $run_lock, time(), '', false ) ) {
			return;
		}
	}

	$original_user           = get_current_user_id();
	$original_remote         = $_SERVER['REMOTE_ADDR'] ?? null;
	$original_content_length = $_SERVER['CONTENT_LENGTH'] ?? null;
	$fake_email              = 'staging-lead@example.invalid';
	$consent_email           = 'staging-consent@example.invalid';
	$test_ip                 = '203.0.113.223';
	$client_key              = 'bds_lead_client_' . substr( hash_hmac( 'sha256', $test_ip, wp_salt( 'nonce' ) ), 0, 32 );
	$global_key              = 'bds_lead_global_' . gmdate( 'Ymd' );
	$original_client_rate    = get_transient( $client_key );
	$original_global_rate    = get_transient( $global_key );
	$consent_snapshots       = array();
	$fixture_posts           = array();
	$posts_cleaned           = false;
	$consent_restored        = false;
	$rates_restored          = false;
	$checks                  = array();
	$errors                  = array();
	$user_a                  = 0;
	$user_b                  = 0;

	try {
		$required = array( 'bds_lead_pipeline_enabled', 'bds_lead_capture_permission', 'bds_lead_capture', 'bds_lead_find_by_email', 'bds_lead_find_by_user', 'bds_lead_set_stage', 'bds_lead_unsubscribed', 'bds_lead_retention_run', 'bds_lead_admin_permission', 'bds_lead_email_hash' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_leads_save_report( false, $checks, $errors );
			return;
		}

		$user_a = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$user_b = absint( username_exists( 'bds_stage_acceptance_b' ) );
		$user_b_data = get_userdata( $user_b );
		$checks['fixture_users_verified'] = $user_a && $user_b && $user_b_data instanceof WP_User
			&& 'a' === (string) get_user_meta( $user_a, '_bds_staging_auth_fixture', true )
			&& 'b' === (string) get_user_meta( $user_b, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_a, 'manage_options' ) && ! user_can( $user_b, 'manage_options' );
		if ( ! $checks['fixture_users_verified'] ) {
			$errors[] = 'fixture_users_unavailable';
			bds_staging_leads_save_report( false, $checks, $errors );
			return;
		}
		$checks['capture_enabled'] = bds_lead_pipeline_enabled();
		$checks['fixture_collision_absent'] = 0 === bds_lead_find_by_email( $fake_email )
			&& 0 === bds_lead_find_by_email( $consent_email );
		if ( ! $checks['capture_enabled'] || ! $checks['fixture_collision_absent'] ) {
			$errors[] = 'fixture_collision_or_capture_disabled';
			bds_staging_leads_save_report( false, $checks, $errors );
			return;
		}

		$consent_snapshots = array( bds_staging_leads_consent_snapshot( $fake_email ), bds_staging_leads_consent_snapshot( $consent_email ) );
		$_SERVER['REMOTE_ADDR'] = $test_ip;
		delete_transient( $client_key );
		delete_transient( $global_key );

		wp_set_current_user( $user_b );
		$checks['subscriber_admin_denied'] = false === bds_lead_admin_permission();
		wp_set_current_user( 0 );

		$invalid_origin = bds_staging_leads_request();
		$invalid_origin->set_header( 'Origin', 'https://untrusted.example' );
		$origin_result = bds_lead_capture_permission( $invalid_origin );
		$checks['invalid_origin_rejected'] = is_wp_error( $origin_result ) && 'origin_not_allowed' === $origin_result->get_error_code();

		$_SERVER['CONTENT_LENGTH'] = 8193;
		$oversized = bds_lead_capture_permission( bds_staging_leads_request() );
		$checks['oversized_body_rejected'] = is_wp_error( $oversized ) && 'request_too_large' === $oversized->get_error_code();
		unset( $_SERVER['CONTENT_LENGTH'] );

		$valid_request = bds_staging_leads_request();
		$checks['owned_origin_allowed'] = true === bds_lead_capture_permission( $valid_request );
		$bad_email = bds_lead_capture( bds_staging_leads_request( array( 'email' => 'not-an-email' ) ) );
		$bad_privacy = bds_lead_capture( bds_staging_leads_request( array( 'privacy_ack' => false ) ) );
		$bad_source = bds_lead_capture( bds_staging_leads_request( array( 'source' => 'unknown-source' ) ) );
		$bad_field = bds_lead_capture( bds_staging_leads_request( array( 'business_type' => str_repeat( 'x', 121 ) ) ) );
		$checks['invalid_email_rejected'] = is_wp_error( $bad_email ) && 'invalid_lead' === $bad_email->get_error_code();
		$checks['privacy_required'] = is_wp_error( $bad_privacy ) && 'invalid_lead' === $bad_privacy->get_error_code();
		$checks['invalid_source_rejected'] = is_wp_error( $bad_source ) && 'invalid_lead' === $bad_source->get_error_code();
		$checks['field_bounds_enforced'] = is_wp_error( $bad_field ) && 'invalid_lead' === $bad_field->get_error_code();

		$created = bds_lead_capture( $valid_request );
		$created_data = $created instanceof WP_REST_Response ? $created->get_data() : array();
		$anonymous_reference = (string) ( $created_data['reference'] ?? '' );
		$anonymous_id = bds_staging_leads_find_by_reference( $anonymous_reference );
		if ( $anonymous_id ) {
			update_post_meta( $anonymous_id, '_bds_staging_lead_fixture', '1' );
			$fixture_posts[ $anonymous_id ] = 0;
		}
		$checks['unverified_capture_created'] = 201 === ( $created instanceof WP_REST_Response ? $created->get_status() : 0 ) && 'interested' === (string) ( $created_data['status'] ?? '' );
		$checks['anonymous_lead_private'] = $anonymous_id > 0 && 'private' === get_post_status( $anonymous_id ) && 0 === absint( get_post_field( 'post_author', $anonymous_id ) );
		$checks['unverified_consent_not_queued'] = empty( $created_data['email_followup'] ) && ! empty( $created_data['email_confirmation_required'] ) && 'yes' !== (string) get_post_meta( $anonymous_id, '_bds_lead_email_consent', true );
		$public_json = wp_json_encode( $created_data );
		$checks['public_response_safe'] = 1 === preg_match( '/^[a-z0-9]{20}$/', $anonymous_reference ) && false === strpos( $public_json, $fake_email ) && false === strpos( $public_json, 'lead_id' );
		$checks['recommendation_catalog_owned'] = 'ai-social-growth' === (string) ( $created_data['recommendation']['slug'] ?? '' ) && isset( $created_data['recommendation']['name'], $created_data['recommendation']['summary'], $created_data['recommendation']['url'] );

		$duplicate = bds_lead_capture( bds_staging_leads_request( array( 'primary_need' => 'Need more social content for my business' ) ) );
		$duplicate_data = $duplicate instanceof WP_REST_Response ? $duplicate->get_data() : array();
		$duplicate_ids = get_posts( array( 'post_type' => 'bds_sales_lead', 'post_status' => 'private', 'posts_per_page' => 3, 'fields' => 'ids', 'meta_key' => '_bds_lead_email_hash', 'meta_value' => bds_lead_email_hash( $fake_email ) ) );
		$checks['duplicate_reuses_reference'] = 200 === ( $duplicate instanceof WP_REST_Response ? $duplicate->get_status() : 0 ) && $anonymous_reference === (string) ( $duplicate_data['reference'] ?? '' );
		$checks['duplicate_single_record'] = 1 === count( $duplicate_ids ) && absint( $duplicate_ids[0] ?? 0 ) === $anonymous_id;

		wp_set_current_user( $user_b );
		$mismatch = bds_lead_capture( bds_staging_leads_request( array( 'email' => $fake_email ) ) );
		$checks['signed_email_mismatch_rejected'] = is_wp_error( $mismatch ) && 'email_mismatch' === $mismatch->get_error_code();
		$checks['fixture_email_verified_filter'] = true === apply_filters( 'bds_lead_email_verified', false, $user_b_data );

		wp_set_current_user( 0 );
		$consent_request = bds_staging_leads_request( array( 'email' => $consent_email, 'primary_need' => 'Need more customer leads', 'source' => 'trial', 'email_consent' => false ) );
		$consent_response = bds_lead_capture( $consent_request );
		$consent_data = $consent_response instanceof WP_REST_Response ? $consent_response->get_data() : array();
		$consent_reference = (string) ( $consent_data['reference'] ?? '' );
		$consent_id = bds_staging_leads_find_by_reference( $consent_reference );
		if ( $consent_id ) {
			update_post_meta( $consent_id, '_bds_staging_lead_fixture', '1' );
			$fixture_posts[ $consent_id ] = 0;
		}
		$checks['consent_lead_private'] = 201 === ( $consent_response instanceof WP_REST_Response ? $consent_response->get_status() : 0 ) && $consent_id > 0 && 'private' === get_post_status( $consent_id ) && 0 === absint( get_post_field( 'post_author', $consent_id ) );
		$mirrored = $consent_id ? bds_lead_mirror_email_consent( $consent_id, $consent_email, 'trial' ) : false;
		$all_consent = get_option( 'bds_follow_consent_v1', array() );
		$consent_snapshot = $consent_snapshots[1];
		$consent_row = is_array( $all_consent ) && is_array( $all_consent[ $consent_snapshot['key'] ] ?? null ) ? $all_consent[ $consent_snapshot['key'] ] : array();
		$checks['verified_consent_mirrored'] = $mirrored && $consent_email === (string) ( $consent_row['email'] ?? '' ) && 1 === absint( $consent_row['consent'] ?? 0 ) && $consent_id === absint( $consent_row['lead_id'] ?? 0 );

		$lifecycle_ok = true;
		foreach ( array( 'qualified', 'registered', 'trial_started', 'checkout_started', 'paid', 'onboarding', 'active' ) as $stage ) {
			$lifecycle_ok = bds_lead_set_stage( $consent_id, $stage, 'staging' ) && $lifecycle_ok;
		}
		$history = get_post_meta( $consent_id, '_bds_lead_stage_history', true );
		$checks['lifecycle_monotonic'] = $lifecycle_ok && 'active' === (string) get_post_meta( $consent_id, '_bds_lead_stage', true ) && 8 === absint( get_post_meta( $consent_id, '_bds_lead_stage_version', true ) ) && is_array( $history ) && 8 === count( $history );

		bds_lead_unsubscribed( $consent_id );
		$checks['unsubscribe_propagates'] = 'unsubscribed' === (string) get_post_meta( $consent_id, '_bds_lead_stage', true ) && 'no' === (string) get_post_meta( $consent_id, '_bds_lead_email_consent', true ) && '' !== (string) get_post_meta( $consent_id, '_bds_lead_unsubscribed_at', true );
		update_post_meta( $consent_id, '_bds_lead_last_activity_ts', time() - ( 366 * DAY_IN_SECONDS ) );
		update_post_meta( $anonymous_id, '_bds_lead_last_activity_ts', time() - ( 366 * DAY_IN_SECONDS ) );
		$GLOBALS['bds_staging_lead_retention_ids'] = array( $consent_id, $anonymous_id );
		add_filter( 'bds_lead_retention_candidate_ids', 'bds_staging_leads_retention_candidates' );
		bds_lead_retention_run();
		remove_filter( 'bds_lead_retention_candidate_ids', 'bds_staging_leads_retention_candidates' );
		unset( $GLOBALS['bds_staging_lead_retention_ids'] );
		$checks['retention_anonymizes_unsubscribed'] = 'anonymized' === (string) get_post_meta( $consent_id, '_bds_lead_stage', true ) && '' === (string) get_post_meta( $consent_id, '_bds_lead_email', true ) && '' === (string) get_post_meta( $consent_id, '_bds_lead_primary_need', true ) && '' !== (string) get_post_meta( $consent_id, '_bds_lead_email_hash', true );
		$all_consent = get_option( 'bds_follow_consent_v1', array() );
		$suppression_row = is_array( $all_consent ) && is_array( $all_consent[ $consent_snapshot['key'] ] ?? null ) ? $all_consent[ $consent_snapshot['key'] ] : array();
		$checks['suppression_preserved'] = '' === (string) ( $suppression_row['email'] ?? 'missing' ) && 0 === absint( $suppression_row['consent'] ?? 1 ) && 'unsubscribed' === (string) ( $suppression_row['status'] ?? '' );
		$checks['anonymous_retention_anonymizes'] = 'anonymized' === (string) get_post_meta( $anonymous_id, '_bds_lead_stage', true ) && '' === (string) get_post_meta( $anonymous_id, '_bds_lead_email', true ) && '' === (string) get_post_meta( $anonymous_id, '_bds_lead_email_hash', true );

		wp_set_current_user( 0 );
		delete_transient( $client_key );
		delete_transient( $global_key );
		$rate_results = array();
		for ( $index = 0; $index < 5; $index++ ) {
			$rate_results[] = bds_lead_capture_permission( bds_staging_leads_request() );
		}
		$rate_six = bds_lead_capture_permission( bds_staging_leads_request() );
		$checks['first_five_rate_checks_allowed'] = 5 === count( array_filter( $rate_results, static fn( $result ) => true === $result ) );
		$checks['sixth_rate_check_rejected'] = is_wp_error( $rate_six ) && 'rate_limited' === $rate_six->get_error_code();

		$posts_cleaned = bds_staging_leads_cleanup_posts( $fixture_posts );
		$checks['fixture_posts_deleted'] = $posts_cleaned && 0 === count( array_filter( array_keys( $fixture_posts ), static fn( $id ) => (bool) get_post( $id ) ) );
		$consent_restored = bds_staging_leads_restore_consent_rows( $consent_snapshots );
		$checks['consent_rows_restored'] = $consent_restored && bds_staging_leads_consent_rows_match( $consent_snapshots );
		bds_staging_leads_restore_rate( $client_key, $original_client_rate, 10 * MINUTE_IN_SECONDS );
		bds_staging_leads_restore_rate( $global_key, $original_global_rate, DAY_IN_SECONDS + HOUR_IN_SECONDS );
		$rates_restored = true;
		$checks['rate_counters_restored'] = get_transient( $client_key ) === $original_client_rate && get_transient( $global_key ) === $original_global_rate;

		if ( null === $original_remote ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $original_remote;
		}
		if ( null === $original_content_length ) {
			unset( $_SERVER['CONTENT_LENGTH'] );
		} else {
			$_SERVER['CONTENT_LENGTH'] = $original_content_length;
		}
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === $original_user
			&& ( $_SERVER['REMOTE_ADDR'] ?? null ) === $original_remote
			&& ( $_SERVER['CONTENT_LENGTH'] ?? null ) === $original_content_length;

		$complete = 34 === count( $checks ) && 34 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_leads_save_report( $complete, $checks, $errors );
	} finally {
		remove_filter( 'bds_lead_retention_candidate_ids', 'bds_staging_leads_retention_candidates' );
		unset( $GLOBALS['bds_staging_lead_retention_ids'] );
		if ( ! $posts_cleaned ) {
			bds_staging_leads_cleanup_posts( $fixture_posts );
		}
		if ( ! $consent_restored && $consent_snapshots ) {
			bds_staging_leads_restore_consent_rows( $consent_snapshots );
		}
		if ( ! $rates_restored ) {
			bds_staging_leads_restore_rate( $client_key, $original_client_rate, 10 * MINUTE_IN_SECONDS );
			bds_staging_leads_restore_rate( $global_key, $original_global_rate, DAY_IN_SECONDS + HOUR_IN_SECONDS );
		}
		if ( null === $original_remote ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $original_remote;
		}
		if ( null === $original_content_length ) {
			unset( $_SERVER['CONTENT_LENGTH'] );
		} else {
			$_SERVER['CONTENT_LENGTH'] = $original_content_length;
		}
		wp_set_current_user( $original_user );
		delete_option( $run_lock );
	}
}
add_action( 'init', 'bds_staging_leads_run', 106 );

function bds_staging_leads_report() {
	$report = get_option( BDS_STAGING_LEADS_REPORT_OPTION, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_leads_register_route() {
	if ( ! bds_staging_leads_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/sales-lead-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_leads_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_leads_register_route' );
