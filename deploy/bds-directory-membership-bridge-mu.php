<?php
/**
 * Plugin Name: BrandDad Directory Membership Bridge
 * Description: Consented, signed, idempotent free Directory account provisioning from BrandDad Social checkout.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_DIRECTORY_MEMBERSHIP_BRIDGE_VERSION', '0.1.0' );

function bds_directory_membership_secret() {
	$secret = defined( 'BDS_ACCOUNT_LINK_SECRET' ) ? trim( (string) BDS_ACCOUNT_LINK_SECRET ) : '';
	return strlen( $secret ) >= 32 ? $secret : '';
}

/** Derive a protocol-specific key instead of reusing the account-link key directly. */
function bds_directory_membership_hmac_key() {
	$secret = bds_directory_membership_secret();
	return $secret ? hash_hmac( 'sha256', 'branddad-directory-membership-v1', $secret, true ) : '';
}

function bds_directory_membership_host() {
	return strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

/** Capture only an explicit checkbox from an eligible Essentials checkout. */
function bds_directory_membership_capture_consent( $order, $data ) {
	if ( ! $order instanceof WC_Order || ! function_exists( 'bds_ai_plan_from_order' ) ) {
		return;
	}
	$plan = bds_ai_plan_from_order( $order );
	if ( ! $plan || 'branddad-ai-business-essentials' !== ( $plan['slug'] ?? '' ) ) {
		return;
	}
	$value = isset( $_POST['bds_directory_membership_consent'] ) ? sanitize_key( wp_unslash( $_POST['bds_directory_membership_consent'] ) ) : '';
	if ( in_array( $value, array( '1', 'yes', 'true', 'on' ), true ) ) {
		$order->update_meta_data( '_bds_directory_membership_consent', 'yes' );
		$order->update_meta_data( '_bds_directory_membership_consent_at', gmdate( DATE_ATOM ) );
	}
}
add_action( 'woocommerce_checkout_create_order', 'bds_directory_membership_capture_consent', 20, 2 );

/** Queue only after the authoritative subscription activation has occurred. */
function bds_directory_membership_queue( $order_id, $subscription_id ) {
	if ( 'branddad.social' !== bds_directory_membership_host() || '' === bds_directory_membership_secret() ) {
		return;
	}
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order || 'yes' !== (string) $order->get_meta( '_bds_directory_membership_consent', true ) ) {
		return;
	}
	$event_id = hash_hmac( 'sha256', 'free-directory|' . absint( $order_id ) . '|' . absint( $subscription_id ), bds_directory_membership_hmac_key() );
	$order->update_meta_data( '_bds_directory_membership_event_id', $event_id );
	$order->save();
	$args = array( absint( $order_id ) );
	if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_single_action' ) ) {
		if ( ! as_has_scheduled_action( 'bds_directory_membership_provision', $args, 'branddad-directory' ) ) {
			as_schedule_single_action( time() + 5, 'bds_directory_membership_provision', $args, 'branddad-directory' );
		}
	} elseif ( ! wp_next_scheduled( 'bds_directory_membership_provision', $args ) ) {
		wp_schedule_single_event( time() + 5, 'bds_directory_membership_provision', $args );
	}
}
add_action( 'wps_sfw_order_status_changed', 'bds_directory_membership_queue', 50, 2 );

/** Send one idempotent signed event from the Social host to the Directory. */
function bds_directory_membership_provision( $order_id ) {
	if ( 'branddad.social' !== bds_directory_membership_host() || '' === bds_directory_membership_secret() ) {
		return;
	}
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order || 'yes' !== (string) $order->get_meta( '_bds_directory_membership_consent', true ) || 'complete' === (string) $order->get_meta( '_bds_directory_membership_status', true ) ) {
		return;
	}
	$email    = strtolower( sanitize_email( $order->get_billing_email() ) );
	$event_id = sanitize_text_field( (string) $order->get_meta( '_bds_directory_membership_event_id', true ) );
	if ( ! is_email( $email ) || ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) ) {
		return;
	}
	$body = wp_json_encode(
		array(
			'event_id'  => $event_id,
			'email'     => $email,
			'first_name'=> sanitize_text_field( $order->get_billing_first_name() ),
			'last_name' => sanitize_text_field( $order->get_billing_last_name() ),
			'source'    => 'ai-business-essentials',
		)
	);
	if ( ! is_string( $body ) || strlen( $body ) > 4096 ) {
		return;
	}
	$timestamp = (string) time();
	$signature = hash_hmac( 'sha256', $timestamp . "\n" . hash( 'sha256', $body ), bds_directory_membership_hmac_key() );
	$response  = wp_safe_remote_post(
		'https://directory.branddad.social/wp-json/branddad/v1/internal/free-membership',
		array(
			'timeout'             => 15,
			'redirection'         => 0,
			'limit_response_size' => 16384,
			'headers'             => array(
				'Content-Type'    => 'application/json',
				'X-BDS-Timestamp' => $timestamp,
				'X-BDS-Signature' => $signature,
				'Idempotency-Key' => $event_id,
			),
			'body'                => $body,
		)
	);
	$code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
	if ( in_array( $code, array( 200, 201 ), true ) ) {
		$order->update_meta_data( '_bds_directory_membership_status', 'complete' );
		$order->update_meta_data( '_bds_directory_membership_completed_at', gmdate( DATE_ATOM ) );
		$order->add_order_note( 'Free BrandDad Directory account linked after explicit checkout consent.', false, false );
		$order->save();
		return;
	}
	$attempts = absint( $order->get_meta( '_bds_directory_membership_attempts', true ) ) + 1;
	$order->update_meta_data( '_bds_directory_membership_attempts', $attempts );
	$order->update_meta_data( '_bds_directory_membership_status', $attempts >= 3 ? 'review' : 'retrying' );
	$order->save();
	if ( $attempts < 3 ) {
		$args  = array( absint( $order_id ) );
		$delay = (int) pow( 2, $attempts ) * 15 * MINUTE_IN_SECONDS;
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $delay, 'bds_directory_membership_provision', $args, 'branddad-directory' );
		} elseif ( ! wp_next_scheduled( 'bds_directory_membership_provision', $args ) ) {
			wp_schedule_single_event( time() + $delay, 'bds_directory_membership_provision', $args );
		}
	}
}
add_action( 'bds_directory_membership_provision', 'bds_directory_membership_provision', 10, 1 );

/** Verify HMAC before WordPress performs any customer lookup or mutation. */
function bds_directory_membership_permission( WP_REST_Request $request ) {
	if ( 'directory.branddad.social' !== bds_directory_membership_host() || '' === bds_directory_membership_secret() ) {
		return new WP_Error( 'bridge_disabled', 'Membership bridge is unavailable.', array( 'status' => 503 ) );
	}
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 8192 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	$timestamp = sanitize_text_field( (string) $request->get_header( 'X-BDS-Timestamp' ) );
	$signature = sanitize_text_field( (string) $request->get_header( 'X-BDS-Signature' ) );
	$event_id  = sanitize_text_field( (string) $request->get_header( 'Idempotency-Key' ) );
	if ( ! ctype_digit( $timestamp ) || abs( time() - absint( $timestamp ) ) > 300 || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) || ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) ) {
		return new WP_Error( 'invalid_signature', 'The membership event could not be verified.', array( 'status' => 401 ) );
	}
	$expected = hash_hmac( 'sha256', $timestamp . "\n" . hash( 'sha256', $request->get_body() ), bds_directory_membership_hmac_key() );
	return hash_equals( $expected, $signature ) ? true : new WP_Error( 'invalid_signature', 'The membership event could not be verified.', array( 'status' => 401 ) );
}

/** Idempotently create or reuse a private subscriber account; never create a listing. */
function bds_directory_membership_receive( WP_REST_Request $request ) {
	$payload  = $request->get_json_params();
	$payload  = is_array( $payload ) ? $payload : array();
	$event_id = sanitize_text_field( (string) ( $payload['event_id'] ?? '' ) );
	$email    = strtolower( sanitize_email( $payload['email'] ?? '' ) );
	$header_event = sanitize_text_field( (string) $request->get_header( 'Idempotency-Key' ) );
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) || ! hash_equals( $header_event, $event_id ) || ! is_email( $email ) || 'ai-business-essentials' !== sanitize_key( (string) ( $payload['source'] ?? '' ) ) ) {
		return new WP_Error( 'invalid_event', 'The membership event is invalid.', array( 'status' => 422 ) );
	}
	$result_key = 'bds_directory_membership_' . $event_id;
	$existing   = get_option( $result_key, array() );
	if ( is_array( $existing ) && ! empty( $existing['user_id'] ) ) {
		return rest_ensure_response( array( 'status' => 'complete', 'created' => false ) );
	}
	$lock_key = $result_key . '_lock';
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		if ( absint( get_option( $lock_key, 0 ) ) < time() - 300 ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return new WP_Error( 'event_in_progress', 'The membership event is already being processed.', array( 'status' => 409 ) );
		}
	}
	try {
		$user    = get_user_by( 'email', $email );
		$created = false;
		if ( ! $user ) {
			$base = sanitize_user( strtok( $email, '@' ), true );
			$base = $base ? $base : 'branddad-member';
			$login = $base;
			for ( $i = 0; username_exists( $login ) && $i < 10; $i++ ) {
				$login = $base . '-' . strtolower( wp_generate_password( 6, false, false ) );
			}
			if ( username_exists( $login ) ) {
				return new WP_Error( 'account_create_failed', 'The Directory account could not be created.', array( 'status' => 503 ) );
			}
			$user_id = wp_insert_user(
				array(
					'user_login' => $login,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 32, true, true ),
					'first_name' => sanitize_text_field( (string) ( $payload['first_name'] ?? '' ) ),
					'last_name'  => sanitize_text_field( (string) ( $payload['last_name'] ?? '' ) ),
					'role'       => 'subscriber',
				)
			);
			if ( is_wp_error( $user_id ) ) {
				return new WP_Error( 'account_create_failed', 'The Directory account could not be created.', array( 'status' => 503 ) );
			}
			$user    = get_user_by( 'id', $user_id );
			$created = true;
		}
		if ( ! $user instanceof WP_User || ! hash_equals( strtolower( $user->user_email ), $email ) ) {
			return new WP_Error( 'account_mismatch', 'The Directory account could not be linked.', array( 'status' => 409 ) );
		}
		update_user_meta( $user->ID, '_bds_free_directory_member', 'yes' );
		update_user_meta( $user->ID, '_bds_free_directory_source', 'ai-business-essentials' );
		$result = array( 'user_id' => $user->ID, 'created_at' => gmdate( DATE_ATOM ) );
		if ( ! add_option( $result_key, $result, '', false ) ) {
			$stored = get_option( $result_key, array() );
			if ( ! is_array( $stored ) || absint( $stored['user_id'] ?? 0 ) !== absint( $user->ID ) ) {
				return new WP_Error( 'event_persist_failed', 'The membership event could not be saved.', array( 'status' => 503 ) );
			}
		}
		if ( $created ) {
			wp_new_user_notification( $user->ID, null, 'user' );
		}
		$response = new WP_REST_Response( array( 'status' => 'complete', 'created' => $created ), $created ? 201 : 200 );
		return $response;
	} finally {
		delete_option( $lock_key );
	}
}

function bds_directory_membership_register_route() {
	register_rest_route(
		'branddad/v1',
		'/internal/free-membership',
		array(
			'methods'             => 'POST',
			'callback'            => 'bds_directory_membership_receive',
			'permission_callback' => 'bds_directory_membership_permission',
		)
	);
}
add_action( 'rest_api_init', 'bds_directory_membership_register_route' );
