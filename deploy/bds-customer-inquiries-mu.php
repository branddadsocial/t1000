<?php
/**
 * Plugin Name: BrandDad Customer Inquiries
 * Description: Private, owner-scoped inquiry capture for eligible BrandDad customers.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_CUSTOMER_INQUIRIES_VERSION', '0.1.0' );

function bds_inquiries_enabled() {
	return defined( 'BDS_CUSTOMER_INQUIRIES_ENABLED' ) && true === BDS_CUSTOMER_INQUIRIES_ENABLED;
}

function bds_inquiries_statuses() {
	return array( 'new', 'contacted', 'qualified', 'quoted', 'in_progress', 'won', 'lost', 'closed' );
}

function bds_inquiries_register_type() {
	register_post_type( 'bds_customer_inq', array( 'label' => 'Customer inquiries', 'public' => false, 'show_ui' => false, 'show_in_rest' => false, 'supports' => array( 'author' ), 'capability_type' => 'post', 'map_meta_cap' => true ) );
}
add_action( 'init', 'bds_inquiries_register_type' );

function bds_inquiries_plan( $user_id ) {
	$plan = function_exists( 'bds_ai_exec_active_plan' ) ? bds_ai_exec_active_plan( absint( $user_id ) ) : array();
	$slug = sanitize_key( (string) ( $plan['slug'] ?? '' ) );
	$mode = sanitize_key( (string) ( $plan['access_mode'] ?? '' ) );
	$allowed = array( 'ai-lead-assistant', 'ai-business-essentials', 'ai-business-essentials-pro', 'complete-ai-starter', 'complete-ai-growth', 'complete-ai-pro' );
	return 'paid' === $mode && in_array( $slug, $allowed, true ) ? $plan : array();
}

function bds_inquiries_private_permission() {
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 12288 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	return is_user_logged_in();
}

function bds_inquiries_owned_origin( WP_REST_Request $request ) {
	$origin = trim( (string) $request->get_header( 'Origin' ) );
	if ( '' === $origin ) {
		$origin = trim( (string) $request->get_header( 'Referer' ) );
	}
	return 'https' === strtolower( (string) wp_parse_url( $origin, PHP_URL_SCHEME ) )
		&& in_array( strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) ), array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true );
}

function bds_inquiries_rate_limit() {
	$key = 'bds_inq_rate_lock';
	if ( ! add_option( $key, time(), '', false ) ) {
		if ( absint( get_option( $key, 0 ) ) < time() - 30 ) {
			delete_option( $key );
		}
		if ( ! add_option( $key, time(), '', false ) ) {
			return false;
		}
	}
	try {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$client_key = 'bds_inq_client_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
		$global_key = 'bds_inq_global_' . gmdate( 'Ymd' );
		$client = absint( get_transient( $client_key ) );
		$global = absint( get_transient( $global_key ) );
		$limit = defined( 'BDS_CUSTOMER_INQUIRIES_DAILY_LIMIT' ) ? absint( BDS_CUSTOMER_INQUIRIES_DAILY_LIMIT ) : 2000;
		$limit = min( 10000, max( 100, $limit ) );
		if ( $client >= 5 || $global >= $limit ) {
			return false;
		}
		set_transient( $client_key, $client + 1, HOUR_IN_SECONDS );
		set_transient( $global_key, $global + 1, DAY_IN_SECONDS + HOUR_IN_SECONDS );
		return true;
	} finally {
		delete_option( $key );
	}
}

function bds_inquiries_public_permission( WP_REST_Request $request ) {
	if ( ! bds_inquiries_enabled() ) {
		return new WP_Error( 'inquiries_disabled', 'Inquiry capture is temporarily unavailable.', array( 'status' => 503 ) );
	}
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 12288 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	if ( ! bds_inquiries_owned_origin( $request ) ) {
		return new WP_Error( 'origin_not_allowed', 'This request origin is not allowed.', array( 'status' => 403 ) );
	}
	return bds_inquiries_rate_limit() ? true : new WP_Error( 'rate_limited', 'Please wait before submitting another inquiry.', array( 'status' => 429 ) );
}

function bds_inquiries_form_state( $user_id ) {
	$state = get_user_meta( absint( $user_id ), '_bds_inquiry_form_v1', true );
	$state = is_array( $state ) ? $state : array();
	return array(
		'enabled'    => 'yes' === (string) ( $state['enabled'] ?? '' ),
		'reference'  => sanitize_text_field( (string) ( $state['reference'] ?? '' ) ),
		'created_at' => sanitize_text_field( (string) ( $state['created_at'] ?? '' ) ),
		'available'  => bds_inquiries_enabled() && ! empty( bds_inquiries_plan( $user_id ) ),
	);
}

function bds_inquiries_form_get() {
	return rest_ensure_response( bds_inquiries_form_state( get_current_user_id() ) );
}

function bds_inquiries_form_save( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! bds_inquiries_enabled() || ! bds_inquiries_plan( $user_id ) ) {
		return new WP_Error( 'inquiries_unavailable', 'Inquiry capture is not included in the active plan.', array( 'status' => 403 ) );
	}
	$enabled = rest_sanitize_boolean( $request->get_param( 'enabled' ) );
	$state   = bds_inquiries_form_state( $user_id );
	$ref     = $state['reference'];
	if ( ! preg_match( '/^[a-z0-9]{32}$/', $ref ) ) {
		$ref = strtolower( wp_generate_password( 32, false, false ) );
	}
	$stored = array( 'enabled' => $enabled ? 'yes' : 'no', 'reference' => $ref, 'created_at' => $state['created_at'] ?: gmdate( DATE_ATOM ) );
	update_user_meta( $user_id, '_bds_inquiry_form_v1', $stored );
	update_user_meta( $user_id, '_bds_inquiry_form_ref', $ref );
	return rest_ensure_response( bds_inquiries_form_state( $user_id ) );
}

function bds_inquiries_owner_from_ref( $reference ) {
	$reference = sanitize_text_field( (string) $reference );
	if ( ! preg_match( '/^[a-z0-9]{32}$/', $reference ) ) {
		return 0;
	}
	$users = get_users( array( 'fields' => 'ID', 'number' => 2, 'meta_key' => '_bds_inquiry_form_ref', 'meta_value' => $reference ) );
	return 1 === count( $users ) ? absint( $users[0] ) : 0;
}

function bds_inquiries_lock( $scope, $stale_after = 60 ) {
	$key = 'bds_inq_' . substr( hash( 'sha256', (string) $scope ), 0, 32 );
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	if ( absint( get_option( $key, 0 ) ) < time() - absint( $stale_after ) ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

function bds_inquiries_submit( WP_REST_Request $request ) {
	if ( '' !== trim( sanitize_text_field( (string) $request->get_param( 'company_website' ) ) ) ) {
		return new WP_Error( 'invalid_inquiry', 'The inquiry could not be accepted.', array( 'status' => 422 ) );
	}
	$owner_id = bds_inquiries_owner_from_ref( $request->get_param( 'form_reference' ) );
	$state    = $owner_id ? bds_inquiries_form_state( $owner_id ) : array();
	if ( ! $owner_id || empty( $state['enabled'] ) || empty( bds_inquiries_plan( $owner_id ) ) ) {
		return new WP_Error( 'form_unavailable', 'This inquiry form is unavailable.', array( 'status' => 404 ) );
	}
	$name    = sanitize_text_field( (string) $request->get_param( 'name' ) );
	$email   = strtolower( sanitize_email( $request->get_param( 'email' ) ) );
	$phone   = sanitize_text_field( (string) $request->get_param( 'phone' ) );
	$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
	$privacy = rest_sanitize_boolean( $request->get_param( 'privacy_ack' ) );
	$contact = rest_sanitize_boolean( $request->get_param( 'contact_consent' ) );
	if ( ! $privacy || ! $contact || strlen( $name ) > 120 || strlen( $phone ) > 40 || strlen( $message ) < 5 || strlen( $message ) > 2000 || ( ! is_email( $email ) && '' === $phone ) ) {
		return new WP_Error( 'invalid_inquiry', 'Provide contact details, a short message, and accept the privacy notice.', array( 'status' => 422 ) );
	}
	$idempotency = sanitize_text_field( (string) $request->get_header( 'Idempotency-Key' ) );
	if ( ! preg_match( '/^[A-Za-z0-9._-]{16,64}$/', $idempotency ) ) {
		return new WP_Error( 'invalid_idempotency_key', 'A valid submission idempotency key is required.', array( 'status' => 422 ) );
	}
	$event_key = hash_hmac( 'sha256', $owner_id . '|' . $idempotency . '|' . hash( 'sha256', $request->get_body() ), wp_salt( 'auth' ) );
	$lock = bds_inquiries_lock( 'submit|' . $event_key );
	if ( ! $lock ) {
		return new WP_Error( 'inquiry_in_progress', 'This inquiry is already being submitted.', array( 'status' => 409 ) );
	}
	try {
		$existing = get_posts( array( 'post_type' => 'bds_customer_inq', 'post_status' => 'private', 'author' => $owner_id, 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_bds_inquiry_event_key', 'meta_value' => $event_key ) );
		if ( $existing ) {
			return new WP_REST_Response( array( 'status' => 'received', 'reference' => sanitize_text_field( (string) get_post_meta( $existing[0], '_bds_inquiry_reference', true ) ) ), 200 );
		}
		$reference = strtolower( wp_generate_password( 20, false, false ) );
		$inquiry_id = wp_insert_post( array( 'post_type' => 'bds_customer_inq', 'post_status' => 'private', 'post_author' => $owner_id, 'post_title' => 'BrandDad inquiry ' . $reference ), true );
		if ( is_wp_error( $inquiry_id ) ) {
			return new WP_Error( 'inquiry_save_failed', 'The inquiry could not be saved.', array( 'status' => 503 ) );
		}
		update_post_meta( $inquiry_id, '_bds_inquiry_event_key', $event_key );
		update_post_meta( $inquiry_id, '_bds_inquiry_reference', $reference );
		update_post_meta( $inquiry_id, '_bds_inquiry_name', $name );
		update_post_meta( $inquiry_id, '_bds_inquiry_email', is_email( $email ) ? $email : '' );
		update_post_meta( $inquiry_id, '_bds_inquiry_phone', $phone );
		update_post_meta( $inquiry_id, '_bds_inquiry_message', $message );
		update_post_meta( $inquiry_id, '_bds_inquiry_status', 'new' );
		update_post_meta( $inquiry_id, '_bds_inquiry_version', 1 );
		update_post_meta( $inquiry_id, '_bds_inquiry_privacy_ack_at', gmdate( DATE_ATOM ) );
		update_post_meta( $inquiry_id, '_bds_inquiry_contact_consent_at', gmdate( DATE_ATOM ) );
		do_action( 'bds_customer_inquiry_created', $inquiry_id, $owner_id );
		return new WP_REST_Response( array( 'status' => 'received', 'reference' => $reference ), 201 );
	} finally {
		delete_option( $lock );
	}
}

function bds_inquiries_item( $inquiry_id ) {
	return array(
		'id'        => absint( $inquiry_id ),
		'reference' => sanitize_text_field( (string) get_post_meta( $inquiry_id, '_bds_inquiry_reference', true ) ),
		'name'      => sanitize_text_field( (string) get_post_meta( $inquiry_id, '_bds_inquiry_name', true ) ),
		'email'     => sanitize_email( get_post_meta( $inquiry_id, '_bds_inquiry_email', true ) ),
		'phone'     => sanitize_text_field( (string) get_post_meta( $inquiry_id, '_bds_inquiry_phone', true ) ),
		'message'   => sanitize_textarea_field( (string) get_post_meta( $inquiry_id, '_bds_inquiry_message', true ) ),
		'status'    => sanitize_key( (string) get_post_meta( $inquiry_id, '_bds_inquiry_status', true ) ),
		'version'   => absint( get_post_meta( $inquiry_id, '_bds_inquiry_version', true ) ),
		'created_at'=> get_post_time( DATE_ATOM, true, $inquiry_id ),
		'updated_at'=> get_post_modified_time( DATE_ATOM, true, $inquiry_id ),
	);
}

function bds_inquiries_list( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$page    = min( 1000, max( 1, absint( $request->get_param( 'page' ) ?: 1 ) ) );
	$status  = sanitize_key( (string) $request->get_param( 'status' ) );
	if ( $status && ! in_array( $status, bds_inquiries_statuses(), true ) ) {
		return new WP_Error( 'invalid_status', 'The inquiry status is invalid.', array( 'status' => 422 ) );
	}
	$args = array( 'post_type' => 'bds_customer_inq', 'post_status' => 'private', 'author' => $user_id, 'posts_per_page' => 50, 'paged' => $page, 'orderby' => 'date', 'order' => 'DESC' );
	if ( $status ) {
		$args['meta_key'] = '_bds_inquiry_status';
		$args['meta_value'] = $status;
	}
	$query = new WP_Query( $args );
	return rest_ensure_response( array( 'items' => array_map( static fn( $post ) => bds_inquiries_item( $post->ID ), $query->posts ), 'page' => $page, 'pages' => absint( $query->max_num_pages ), 'total' => absint( $query->found_posts ) ) );
}

function bds_inquiries_update( WP_REST_Request $request ) {
	$inquiry_id = absint( $request['id'] );
	$post       = get_post( $inquiry_id );
	if ( ! $post || 'bds_customer_inq' !== $post->post_type || 'private' !== $post->post_status || absint( $post->post_author ) !== get_current_user_id() ) {
		return new WP_Error( 'inquiry_not_found', 'Inquiry not found.', array( 'status' => 404 ) );
	}
	$lock = bds_inquiries_lock( 'update|' . $inquiry_id );
	if ( ! $lock ) {
		return new WP_Error( 'inquiry_busy', 'This inquiry is already being updated.', array( 'status' => 409 ) );
	}
	try {
		$version = absint( $request->get_param( 'version' ) );
		$current = absint( get_post_meta( $inquiry_id, '_bds_inquiry_version', true ) );
		$status  = sanitize_key( (string) $request->get_param( 'status' ) );
		if ( $version !== $current ) {
			return new WP_Error( 'version_conflict', 'This inquiry changed. Reload and try again.', array( 'status' => 409, 'current_version' => $current ) );
		}
		if ( ! in_array( $status, bds_inquiries_statuses(), true ) ) {
			return new WP_Error( 'invalid_status', 'The inquiry status is invalid.', array( 'status' => 422 ) );
		}
		update_post_meta( $inquiry_id, '_bds_inquiry_status', $status );
		update_post_meta( $inquiry_id, '_bds_inquiry_version', $current + 1 );
		wp_update_post( array( 'ID' => $inquiry_id ) );
		do_action( 'bds_customer_inquiry_status_changed', $inquiry_id, get_current_user_id(), $status, $current + 1 );
		return rest_ensure_response( bds_inquiries_item( $inquiry_id ) );
	} finally {
		delete_option( $lock );
	}
}

function bds_inquiries_monthly_summary( $user_id, $period ) {
	$start = $period . '-01 00:00:00';
	$end   = gmdate( 'Y-m-t 23:59:59', strtotime( $start ) );
	$ids   = get_posts( array( 'post_type' => 'bds_customer_inq', 'post_status' => 'private', 'author' => absint( $user_id ), 'posts_per_page' => 500, 'fields' => 'ids', 'date_query' => array( array( 'after' => $start, 'before' => $end, 'inclusive' => true, 'column' => 'post_date_gmt' ) ) ) );
	$statuses = array_fill_keys( bds_inquiries_statuses(), 0 );
	foreach ( $ids as $id ) {
		$status = sanitize_key( (string) get_post_meta( $id, '_bds_inquiry_status', true ) );
		if ( isset( $statuses[ $status ] ) ) {
			$statuses[ $status ]++;
		}
	}
	return array( 'captured' => count( $ids ), 'by_current_status' => $statuses, 'truncated' => count( $ids ) >= 500 );
}

function bds_inquiries_retention_days() {
	$days = defined( 'BDS_CUSTOMER_INQUIRY_RETENTION_DAYS' ) ? absint( BDS_CUSTOMER_INQUIRY_RETENTION_DAYS ) : 365;
	return min( 730, max( 90, $days ) );
}

/** Anonymize stale closed/lost inquiries without deleting aggregate workflow evidence. */
function bds_inquiries_retention_run() {
	$ids = get_posts(
		array(
			'post_type'      => 'bds_customer_inq',
			'post_status'    => 'private',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'date_query'     => array( array( 'before' => gmdate( 'Y-m-d H:i:s', time() - ( bds_inquiries_retention_days() * DAY_IN_SECONDS ) ), 'column' => 'post_modified_gmt' ) ),
			'meta_query'     => array(
				array( 'key' => '_bds_inquiry_status', 'value' => array( 'lost', 'closed' ), 'compare' => 'IN' ),
				array( 'key' => '_bds_inquiry_anonymized_at', 'compare' => 'NOT EXISTS' ),
			),
		)
	);
	foreach ( $ids as $inquiry_id ) {
		foreach ( array( '_bds_inquiry_name', '_bds_inquiry_email', '_bds_inquiry_phone', '_bds_inquiry_message', '_bds_inquiry_contact_consent_at' ) as $key ) {
			delete_post_meta( $inquiry_id, $key );
		}
		update_post_meta( $inquiry_id, '_bds_inquiry_anonymized_at', gmdate( DATE_ATOM ) );
	}
	if ( 100 === count( $ids ) && ! wp_next_scheduled( 'bds_inquiries_retention_continue' ) ) {
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'bds_inquiries_retention_continue' );
	}
}
add_action( 'bds_inquiries_retention_run', 'bds_inquiries_retention_run' );
add_action( 'bds_inquiries_retention_continue', 'bds_inquiries_retention_run' );

function bds_inquiries_schedule_retention() {
	if ( ! wp_next_scheduled( 'bds_inquiries_retention_run' ) ) {
		wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'bds_inquiries_retention_run' );
	}
}
add_action( 'init', 'bds_inquiries_schedule_retention' );

function bds_inquiries_register_routes() {
	register_rest_route( 'branddad/v1', '/inquiries/form', array( array( 'methods' => 'GET', 'callback' => 'bds_inquiries_form_get', 'permission_callback' => 'bds_inquiries_private_permission' ), array( 'methods' => 'POST', 'callback' => 'bds_inquiries_form_save', 'permission_callback' => 'bds_inquiries_private_permission' ) ) );
	register_rest_route( 'branddad/v1', '/inquiries/submit', array( 'methods' => 'POST', 'callback' => 'bds_inquiries_submit', 'permission_callback' => 'bds_inquiries_public_permission' ) );
	register_rest_route( 'branddad/v1', '/inquiries', array( 'methods' => 'GET', 'callback' => 'bds_inquiries_list', 'permission_callback' => 'bds_inquiries_private_permission' ) );
	register_rest_route( 'branddad/v1', '/inquiries/(?P<id>\d+)', array( 'methods' => 'POST', 'callback' => 'bds_inquiries_update', 'permission_callback' => 'bds_inquiries_private_permission' ) );
}
add_action( 'rest_api_init', 'bds_inquiries_register_routes' );
