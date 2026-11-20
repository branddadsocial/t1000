<?php
/**
 * Plugin Name: BrandDad Staging Customer Inquiries Acceptance
 * Description: One-time, exact-host inquiry consent, origin, rate, idempotency, ownership, version, reporting, and retention acceptance.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_INQUIRIES_VERSION', '0.1.0' );
define( 'BDS_STAGING_INQUIRIES_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_INQUIRIES_REPORT_OPTION', 'bds_staging_inquiries_report_v1' );

function bds_staging_inquiries_is_host() {
	return BDS_STAGING_INQUIRIES_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

if ( bds_staging_inquiries_is_host() && ! get_option( BDS_STAGING_INQUIRIES_REPORT_OPTION, false ) && ! defined( 'BDS_CUSTOMER_INQUIRIES_ENABLED' ) ) {
	define( 'BDS_CUSTOMER_INQUIRIES_ENABLED', true );
}

function bds_staging_inquiries_plan_candidate( $plan, $user_id ) {
	if ( ! bds_staging_inquiries_is_host() || get_option( BDS_STAGING_INQUIRIES_REPORT_OPTION, false ) ) {
		return $plan;
	}
	if ( 'b' !== (string) get_user_meta( absint( $user_id ), '_bds_staging_auth_fixture', true ) || user_can( $user_id, 'manage_options' ) ) {
		return $plan;
	}
	return array(
		'subscription_id' => 990300,
		'slug'            => 'ai-lead-assistant',
		'plan_id'         => 'staging-inquiry-fixture',
		'credits'         => 0,
		'allowance_cycle' => 1,
		'access_mode'     => 'paid',
	);
}
add_filter( 'bds_inquiries_plan_candidate', 'bds_staging_inquiries_plan_candidate', 10, 2 );

function bds_staging_inquiries_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_users_verified',
		'anonymous_private_denied',
		'synthetic_plan_revalidated',
		'form_enabled_with_opaque_reference',
		'ambiguous_form_reference_rejected',
		'invalid_origin_rejected',
		'oversized_body_rejected',
		'owned_origin_allowed',
		'honeypot_rejected',
		'privacy_required',
		'contact_consent_required',
		'idempotency_required',
		'valid_inquiry_private_owned',
		'public_response_safe',
		'replay_reuses_reference',
		'replay_single_record',
		'owner_list_includes_inquiry',
		'cross_account_list_excludes_inquiry',
		'cross_account_update_is_404',
		'active_update_lock_rejected',
		'version_conflict_rejected',
		'invalid_status_rejected',
		'valid_status_update',
		'stale_update_lock_recovered',
		'monthly_report_delta_correct',
		'value_report_delta_correct',
		'retention_anonymizes_closed_fixture',
		'recent_inquiry_pii_preserved',
		'first_five_rate_checks_allowed',
		'sixth_rate_check_rejected',
		'fixture_posts_deleted',
		'fixture_metadata_restored',
		'rate_counters_restored',
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
		BDS_STAGING_INQUIRIES_REPORT_OPTION,
		array(
			'version'      => BDS_STAGING_INQUIRIES_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_inquiries_snapshot_user_meta( $user_id ) {
	$keys = array( '_bds_inquiry_form_v1', '_bds_inquiry_form_ref' );
	$out = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = array( 'exists' => metadata_exists( 'user', absint( $user_id ), $key ), 'value' => get_user_meta( absint( $user_id ), $key, true ) );
	}
	return $out;
}

function bds_staging_inquiries_restore_user_meta( $user_id, $snapshot ) {
	foreach ( (array) $snapshot as $key => $row ) {
		if ( ! empty( $row['exists'] ) ) {
			update_user_meta( absint( $user_id ), sanitize_key( $key ), $row['value'] );
		} else {
			delete_user_meta( absint( $user_id ), sanitize_key( $key ) );
		}
	}
}

function bds_staging_inquiries_user_meta_matches( $user_id, $snapshot ) {
	foreach ( (array) $snapshot as $key => $row ) {
		if ( (bool) metadata_exists( 'user', absint( $user_id ), $key ) !== (bool) ( $row['exists'] ?? false ) ) {
			return false;
		}
		if ( ! empty( $row['exists'] ) && get_user_meta( absint( $user_id ), $key, true ) !== $row['value'] ) {
			return false;
		}
	}
	return true;
}

function bds_staging_inquiries_request( $overrides = array(), $idempotency = 'staging-inquiry-idempotency-v1' ) {
	$payload = array_merge(
		array(
			'form_reference' => '',
			'company_website' => '',
			'name'            => 'Staging Test Visitor',
			'email'           => 'visitor@example.invalid',
			'phone'           => '+1 555 010 0200',
			'message'         => 'This is a private staging inquiry acceptance message.',
			'privacy_ack'     => true,
			'contact_consent' => true,
		),
		$overrides
	);
	$request = new WP_REST_Request( 'POST', '/branddad/v1/inquiries/submit' );
	foreach ( $payload as $key => $value ) {
		$request->set_param( $key, $value );
	}
	$request->set_body( wp_json_encode( $payload ) );
	if ( '' !== $idempotency ) {
		$request->set_header( 'Idempotency-Key', $idempotency );
	}
	$request->set_header( 'Origin', 'https://branddad.social' );
	return $request;
}

function bds_staging_inquiries_update_request( $inquiry_id, $version, $status ) {
	$request = new WP_REST_Request( 'POST', '/branddad/v1/inquiries/' . absint( $inquiry_id ) );
	$request->set_param( 'id', absint( $inquiry_id ) );
	$request->set_param( 'version', absint( $version ) );
	$request->set_param( 'status', $status );
	return $request;
}

function bds_staging_inquiries_cleanup_posts( $user_id, $post_ids ) {
	$all_deleted = true;
	foreach ( array_unique( array_filter( array_map( 'absint', (array) $post_ids ) ) ) as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			continue;
		}
		if ( 'bds_customer_inq' !== $post->post_type || absint( $post->post_author ) !== absint( $user_id ) || '1' !== (string) get_post_meta( $post_id, '_bds_staging_inquiry_fixture', true ) ) {
			$all_deleted = false;
			continue;
		}
		if ( ! wp_delete_post( $post_id, true ) ) {
			$all_deleted = false;
		}
	}
	return $all_deleted;
}

function bds_staging_inquiries_run() {
	if ( ! bds_staging_inquiries_is_host() || get_option( BDS_STAGING_INQUIRIES_REPORT_OPTION, false ) ) {
		return;
	}
	$run_lock = 'bds_staging_inquiries_acceptance_lock_v1';
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
	$original_remote = $_SERVER['REMOTE_ADDR'] ?? null;
	$original_content_length = $_SERVER['CONTENT_LENGTH'] ?? null;
	$user_a = 0;
	$user_b = 0;
	$snapshot_a = array();
	$snapshot_b = array();
	$fixture_posts = array();
	$meta_restored = false;
	$posts_cleaned = false;
	$checks = array();
	$errors = array();
	$test_ip = '203.0.113.222';
	$client_key = 'bds_inq_client_' . substr( hash_hmac( 'sha256', $test_ip, wp_salt( 'nonce' ) ), 0, 32 );
	$global_key = 'bds_inq_global_' . gmdate( 'Ymd' );
	$original_client_rate = get_transient( $client_key );
	$original_global_rate = get_transient( $global_key );
	try {
		$required = array( 'bds_inquiries_private_permission', 'bds_inquiries_public_permission', 'bds_inquiries_plan', 'bds_inquiries_form_save', 'bds_inquiries_owner_from_ref', 'bds_inquiries_submit', 'bds_inquiries_list', 'bds_inquiries_update', 'bds_inquiries_monthly_summary', 'bds_inquiries_retention_run', 'bds_customer_value_report' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_inquiries_save_report( false, $checks, $errors );
			return;
		}

		$user_a = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$user_b = absint( username_exists( 'bds_stage_acceptance_b' ) );
		$checks['fixture_users_verified'] = $user_a && $user_b
			&& 'a' === (string) get_user_meta( $user_a, '_bds_staging_auth_fixture', true )
			&& 'b' === (string) get_user_meta( $user_b, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_a, 'manage_options' ) && ! user_can( $user_b, 'manage_options' );
		if ( ! $checks['fixture_users_verified'] ) {
			$errors[] = 'fixture_users_unavailable';
			bds_staging_inquiries_save_report( false, $checks, $errors );
			return;
		}

		$snapshot_a = bds_staging_inquiries_snapshot_user_meta( $user_a );
		$snapshot_b = bds_staging_inquiries_snapshot_user_meta( $user_b );
		$_SERVER['REMOTE_ADDR'] = $test_ip;
		delete_transient( $client_key );
		delete_transient( $global_key );

		wp_set_current_user( 0 );
		$checks['anonymous_private_denied'] = false === bds_inquiries_private_permission();
		wp_set_current_user( $user_b );
		$plan = bds_inquiries_plan( $user_b );
		$checks['synthetic_plan_revalidated'] = 'ai-lead-assistant' === (string) ( $plan['slug'] ?? '' ) && 'paid' === (string) ( $plan['access_mode'] ?? '' );

		$form_request = new WP_REST_Request( 'POST', '/branddad/v1/inquiries/form' );
		$form_request->set_param( 'enabled', true );
		$form_response = bds_inquiries_form_save( $form_request );
		$form_data = $form_response instanceof WP_REST_Response ? $form_response->get_data() : array();
		$form_reference = (string) ( $form_data['reference'] ?? '' );
		$checks['form_enabled_with_opaque_reference'] = ! empty( $form_data['enabled'] ) && 1 === preg_match( '/^[a-z0-9]{32}$/', $form_reference );

		update_user_meta( $user_a, '_bds_inquiry_form_ref', $form_reference );
		$checks['ambiguous_form_reference_rejected'] = 0 === bds_inquiries_owner_from_ref( $form_reference );
		bds_staging_inquiries_restore_user_meta( $user_a, $snapshot_a );

		$invalid_origin = bds_staging_inquiries_request( array( 'form_reference' => $form_reference ), 'staging-origin-check-v1' );
		$invalid_origin->set_header( 'Origin', 'https://untrusted.example' );
		$origin_result = bds_inquiries_public_permission( $invalid_origin );
		$checks['invalid_origin_rejected'] = is_wp_error( $origin_result ) && 'origin_not_allowed' === $origin_result->get_error_code();

		$_SERVER['CONTENT_LENGTH'] = 12289;
		$oversized = bds_inquiries_public_permission( bds_staging_inquiries_request( array( 'form_reference' => $form_reference ), 'staging-size-check-v1' ) );
		$checks['oversized_body_rejected'] = is_wp_error( $oversized ) && 'request_too_large' === $oversized->get_error_code();
		unset( $_SERVER['CONTENT_LENGTH'] );

		$valid_request = bds_staging_inquiries_request( array( 'form_reference' => $form_reference ) );
		$checks['owned_origin_allowed'] = true === bds_inquiries_public_permission( $valid_request );
		$honeypot = bds_inquiries_submit( bds_staging_inquiries_request( array( 'form_reference' => $form_reference, 'company_website' => 'https://spam.example/' ), 'staging-honeypot-v1' ) );
		$privacy = bds_inquiries_submit( bds_staging_inquiries_request( array( 'form_reference' => $form_reference, 'privacy_ack' => false ), 'staging-privacy-v1' ) );
		$contact = bds_inquiries_submit( bds_staging_inquiries_request( array( 'form_reference' => $form_reference, 'contact_consent' => false ), 'staging-contact-v1' ) );
		$bad_idempotency = bds_inquiries_submit( bds_staging_inquiries_request( array( 'form_reference' => $form_reference ), '' ) );
		$checks['honeypot_rejected'] = is_wp_error( $honeypot ) && 'invalid_inquiry' === $honeypot->get_error_code();
		$checks['privacy_required'] = is_wp_error( $privacy ) && 'invalid_inquiry' === $privacy->get_error_code();
		$checks['contact_consent_required'] = is_wp_error( $contact ) && 'invalid_inquiry' === $contact->get_error_code();
		$checks['idempotency_required'] = is_wp_error( $bad_idempotency ) && 'invalid_idempotency_key' === $bad_idempotency->get_error_code();

		$period = gmdate( 'Y-m' );
		$monthly_before = bds_inquiries_monthly_summary( $user_b, $period );
		$value_before = bds_customer_value_report( $user_b, $period );
		$submitted = bds_inquiries_submit( $valid_request );
		$submitted_data = $submitted instanceof WP_REST_Response ? $submitted->get_data() : array();
		$public_reference = (string) ( $submitted_data['reference'] ?? '' );
		$created = get_posts( array( 'post_type' => 'bds_customer_inq', 'post_status' => 'private', 'author' => $user_b, 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => '_bds_inquiry_reference', 'meta_value' => $public_reference ) );
		$inquiry_id = 1 === count( $created ) ? absint( $created[0] ) : 0;
		if ( $inquiry_id ) {
			update_post_meta( $inquiry_id, '_bds_staging_inquiry_fixture', '1' );
			$fixture_posts[] = $inquiry_id;
		}
		$checks['valid_inquiry_private_owned'] = 201 === ( $submitted instanceof WP_REST_Response ? $submitted->get_status() : 0 )
			&& $inquiry_id > 0 && absint( get_post_field( 'post_author', $inquiry_id ) ) === $user_b && 'private' === get_post_status( $inquiry_id );
		$public_json = wp_json_encode( $submitted_data );
		$checks['public_response_safe'] = 1 === preg_match( '/^[a-z0-9]{20}$/', $public_reference )
			&& false === strpos( $public_json, 'visitor@example.invalid' )
			&& false === strpos( $public_json, '555' )
			&& false === strpos( $public_json, 'private staging inquiry' )
			&& false === strpos( $public_json, '"id"' );

		$replay = bds_inquiries_submit( $valid_request );
		$replay_data = $replay instanceof WP_REST_Response ? $replay->get_data() : array();
		$created_after_replay = get_posts( array( 'post_type' => 'bds_customer_inq', 'post_status' => 'private', 'author' => $user_b, 'posts_per_page' => 3, 'fields' => 'ids', 'meta_key' => '_bds_inquiry_reference', 'meta_value' => $public_reference ) );
		$checks['replay_reuses_reference'] = 200 === ( $replay instanceof WP_REST_Response ? $replay->get_status() : 0 ) && $public_reference === (string) ( $replay_data['reference'] ?? '' );
		$checks['replay_single_record'] = 1 === count( $created_after_replay ) && absint( $created_after_replay[0] ?? 0 ) === $inquiry_id;

		wp_set_current_user( $user_b );
		$owner_list_response = bds_inquiries_list( new WP_REST_Request( 'GET', '/branddad/v1/inquiries' ) );
		$owner_list = $owner_list_response instanceof WP_REST_Response ? $owner_list_response->get_data() : array();
		$owner_ids = array_map( static fn( $item ) => absint( $item['id'] ?? 0 ), (array) ( $owner_list['items'] ?? array() ) );
		$checks['owner_list_includes_inquiry'] = in_array( $inquiry_id, $owner_ids, true );
		wp_set_current_user( $user_a );
		$other_list_response = bds_inquiries_list( new WP_REST_Request( 'GET', '/branddad/v1/inquiries' ) );
		$other_list = $other_list_response instanceof WP_REST_Response ? $other_list_response->get_data() : array();
		$other_ids = array_map( static fn( $item ) => absint( $item['id'] ?? 0 ), (array) ( $other_list['items'] ?? array() ) );
		$checks['cross_account_list_excludes_inquiry'] = ! in_array( $inquiry_id, $other_ids, true );
		$cross_update = bds_inquiries_update( bds_staging_inquiries_update_request( $inquiry_id, 1, 'contacted' ) );
		$checks['cross_account_update_is_404'] = is_wp_error( $cross_update ) && 'inquiry_not_found' === $cross_update->get_error_code();

		wp_set_current_user( $user_b );
		$held_lock = bds_inquiries_lock( 'update|' . $inquiry_id );
		$busy = bds_inquiries_update( bds_staging_inquiries_update_request( $inquiry_id, 1, 'contacted' ) );
		$checks['active_update_lock_rejected'] = $held_lock && is_wp_error( $busy ) && 'inquiry_busy' === $busy->get_error_code();
		if ( $held_lock ) {
			delete_option( $held_lock );
		}
		$conflict = bds_inquiries_update( bds_staging_inquiries_update_request( $inquiry_id, 99, 'contacted' ) );
		$invalid_status = bds_inquiries_update( bds_staging_inquiries_update_request( $inquiry_id, 1, 'not-allowed' ) );
		$checks['version_conflict_rejected'] = is_wp_error( $conflict ) && 'version_conflict' === $conflict->get_error_code();
		$checks['invalid_status_rejected'] = is_wp_error( $invalid_status ) && 'invalid_status' === $invalid_status->get_error_code();
		$updated = bds_inquiries_update( bds_staging_inquiries_update_request( $inquiry_id, 1, 'qualified' ) );
		$updated_data = $updated instanceof WP_REST_Response ? $updated->get_data() : array();
		$checks['valid_status_update'] = 'qualified' === (string) ( $updated_data['status'] ?? '' ) && 2 === absint( $updated_data['version'] ?? 0 );

		$stale_lock = bds_inquiries_lock( 'update|' . $inquiry_id );
		if ( $stale_lock ) {
			update_option( $stale_lock, time() - 61, false );
		}
		$stale_update = bds_inquiries_update( bds_staging_inquiries_update_request( $inquiry_id, 2, 'quoted' ) );
		$stale_data = $stale_update instanceof WP_REST_Response ? $stale_update->get_data() : array();
		$checks['stale_update_lock_recovered'] = 'quoted' === (string) ( $stale_data['status'] ?? '' ) && 3 === absint( $stale_data['version'] ?? 0 );

		$monthly_after = bds_inquiries_monthly_summary( $user_b, $period );
		$value_after = bds_customer_value_report( $user_b, $period );
		$checks['monthly_report_delta_correct'] = 1 === absint( $monthly_after['captured'] ?? 0 ) - absint( $monthly_before['captured'] ?? 0 )
			&& 1 === absint( $monthly_after['by_current_status']['quoted'] ?? 0 ) - absint( $monthly_before['by_current_status']['quoted'] ?? 0 );
		$checks['value_report_delta_correct'] = 1 === absint( $value_after['customer_inquiries']['captured'] ?? 0 ) - absint( $value_before['customer_inquiries']['captured'] ?? 0 )
			&& 1 === absint( $value_after['customer_inquiries']['by_current_status']['quoted'] ?? 0 ) - absint( $value_before['customer_inquiries']['by_current_status']['quoted'] ?? 0 );

		$old_gmt = gmdate( 'Y-m-d H:i:s', time() - ( 366 * DAY_IN_SECONDS ) );
		$old_id = wp_insert_post(
			array(
				'post_type'         => 'bds_customer_inq',
				'post_status'       => 'private',
				'post_author'       => $user_b,
				'post_title'        => 'Staging retention fixture',
				'post_date_gmt'     => $old_gmt,
				'post_date'         => get_date_from_gmt( $old_gmt ),
				'post_modified_gmt' => $old_gmt,
				'post_modified'     => get_date_from_gmt( $old_gmt ),
			),
			true
		);
		if ( is_wp_error( $old_id ) ) {
			$errors[] = 'retention_fixture_create_failed';
		} else {
			$old_id = absint( $old_id );
			$fixture_posts[] = $old_id;
			update_post_meta( $old_id, '_bds_staging_inquiry_fixture', '1' );
			update_post_meta( $old_id, '_bds_inquiry_status', 'closed' );
			update_post_meta( $old_id, '_bds_inquiry_name', 'Old Test Visitor' );
			update_post_meta( $old_id, '_bds_inquiry_email', 'old@example.invalid' );
			update_post_meta( $old_id, '_bds_inquiry_phone', '+1 555 010 0300' );
			update_post_meta( $old_id, '_bds_inquiry_message', 'Old staging retention message.' );
			update_post_meta( $old_id, '_bds_inquiry_contact_consent_at', $old_gmt );
			bds_inquiries_retention_run();
			$checks['retention_anonymizes_closed_fixture'] = (bool) get_post( $old_id )
				&& '' === (string) get_post_meta( $old_id, '_bds_inquiry_name', true )
				&& '' === (string) get_post_meta( $old_id, '_bds_inquiry_email', true )
				&& '' === (string) get_post_meta( $old_id, '_bds_inquiry_phone', true )
				&& '' === (string) get_post_meta( $old_id, '_bds_inquiry_message', true )
				&& '' !== (string) get_post_meta( $old_id, '_bds_inquiry_anonymized_at', true );
		}
		$checks['recent_inquiry_pii_preserved'] = 'visitor@example.invalid' === (string) get_post_meta( $inquiry_id, '_bds_inquiry_email', true )
			&& '' !== (string) get_post_meta( $inquiry_id, '_bds_inquiry_message', true );

		delete_transient( $client_key );
		delete_transient( $global_key );
		$rate_results = array();
		for ( $index = 0; $index < 5; $index++ ) {
			$rate_results[] = bds_inquiries_public_permission( bds_staging_inquiries_request( array( 'form_reference' => $form_reference ), 'staging-rate-' . $index . '-key' ) );
		}
		$rate_six = bds_inquiries_public_permission( bds_staging_inquiries_request( array( 'form_reference' => $form_reference ), 'staging-rate-six-key' ) );
		$checks['first_five_rate_checks_allowed'] = 5 === count( array_filter( $rate_results, static fn( $result ) => true === $result ) );
		$checks['sixth_rate_check_rejected'] = is_wp_error( $rate_six ) && 'rate_limited' === $rate_six->get_error_code();

		$posts_cleaned = bds_staging_inquiries_cleanup_posts( $user_b, $fixture_posts );
		$checks['fixture_posts_deleted'] = $posts_cleaned && 0 === count( array_filter( $fixture_posts, static fn( $id ) => (bool) get_post( $id ) ) );
		bds_staging_inquiries_restore_user_meta( $user_a, $snapshot_a );
		bds_staging_inquiries_restore_user_meta( $user_b, $snapshot_b );
		$meta_restored = true;
		$checks['fixture_metadata_restored'] = bds_staging_inquiries_user_meta_matches( $user_a, $snapshot_a ) && bds_staging_inquiries_user_meta_matches( $user_b, $snapshot_b );
		delete_transient( $client_key );
		delete_transient( $global_key );
		if ( false !== $original_client_rate ) {
			set_transient( $client_key, $original_client_rate, HOUR_IN_SECONDS );
		}
		if ( false !== $original_global_rate ) {
			set_transient( $global_key, $original_global_rate, DAY_IN_SECONDS + HOUR_IN_SECONDS );
		}
		$checks['rate_counters_restored'] = get_transient( $client_key ) === $original_client_rate && get_transient( $global_key ) === $original_global_rate;

		$complete = 34 === count( $checks ) && 34 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_inquiries_save_report( $complete, $checks, $errors );
	} finally {
		if ( ! $posts_cleaned && $user_b ) {
			bds_staging_inquiries_cleanup_posts( $user_b, $fixture_posts );
		}
		if ( ! $meta_restored ) {
			if ( $user_a && $snapshot_a ) {
				bds_staging_inquiries_restore_user_meta( $user_a, $snapshot_a );
			}
			if ( $user_b && $snapshot_b ) {
				bds_staging_inquiries_restore_user_meta( $user_b, $snapshot_b );
			}
		}
		delete_transient( $client_key );
		delete_transient( $global_key );
		if ( false !== $original_client_rate ) {
			set_transient( $client_key, $original_client_rate, HOUR_IN_SECONDS );
		}
		if ( false !== $original_global_rate ) {
			set_transient( $global_key, $original_global_rate, DAY_IN_SECONDS + HOUR_IN_SECONDS );
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
add_action( 'init', 'bds_staging_inquiries_run', 105 );

function bds_staging_inquiries_report() {
	$report = get_option( BDS_STAGING_INQUIRIES_REPORT_OPTION, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_inquiries_register_route() {
	if ( ! bds_staging_inquiries_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/customer-inquiries-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_inquiries_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_inquiries_register_route' );
