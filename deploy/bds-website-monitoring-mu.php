<?php
/**
 * Plugin Name: BrandDad Website Monitoring
 * Description: Entitlement-checked, SSRF-resistant daily website availability checks for BrandDad customers.
 * Version: 0.1.1
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_WEBSITE_MONITORING_VERSION', '0.1.1' );

function bds_monitoring_enabled() {
	return defined( 'BDS_WEBSITE_MONITORING_ENABLED' ) && true === BDS_WEBSITE_MONITORING_ENABLED;
}

function bds_monitoring_plan( $user_id ) {
	$plan = function_exists( 'bds_ai_exec_active_plan' ) ? bds_ai_exec_active_plan( absint( $user_id ) ) : array();
	$plan = apply_filters( 'bds_monitoring_plan_candidate', is_array( $plan ) ? $plan : array(), absint( $user_id ) );
	$plan = is_array( $plan ) ? $plan : array();
	$slug = sanitize_key( (string) ( $plan['slug'] ?? '' ) );
	$mode = sanitize_key( (string) ( $plan['access_mode'] ?? 'paid' ) );
	$allowed = array( 'ai-business-monitoring', 'ai-business-essentials', 'ai-business-essentials-pro', 'complete-ai-starter', 'complete-ai-growth', 'complete-ai-pro' );
	return 'paid' === $mode && in_array( $slug, $allowed, true ) ? $plan : array();
}

function bds_monitoring_profile_url( $user_id ) {
	if ( ! function_exists( 'bds_onboarding_state' ) ) {
		return '';
	}
	$state = bds_onboarding_state( absint( $user_id ) );
	return esc_url_raw( (string) ( $state['profile']['website_url'] ?? '' ), array( 'http', 'https' ) );
}

function bds_monitoring_public_ip( $ip ) {
	return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
}

/** Resolve all available A/AAAA records and fail if any address is not public. */
function bds_monitoring_public_dns( $host ) {
	$host = strtolower( rtrim( sanitize_text_field( (string) $host ), '.' ) );
	if ( ! preg_match( '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])$/', $host ) || ! function_exists( 'dns_get_record' ) ) {
		return false;
	}
	$record_type = defined( 'DNS_AAAA' ) ? DNS_A | DNS_AAAA : DNS_A;
	$records = dns_get_record( $host, $record_type );
	if ( ! is_array( $records ) || ! $records ) {
		return false;
	}
	$ips = array();
	foreach ( $records as $record ) {
		if ( ! empty( $record['ip'] ) ) {
			$ips[] = (string) $record['ip'];
		}
		if ( ! empty( $record['ipv6'] ) ) {
			$ips[] = (string) $record['ipv6'];
		}
	}
	if ( ! $ips ) {
		return false;
	}
	foreach ( array_unique( $ips ) as $ip ) {
		if ( ! bds_monitoring_public_ip( $ip ) ) {
			return false;
		}
	}
	return true;
}

function bds_monitoring_safe_url( $url ) {
	$url = esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );
	if ( ! $url || strlen( $url ) > 500 ) {
		return '';
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return '';
	}
	$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
	$host   = strtolower( rtrim( (string) ( $parts['host'] ?? '' ), '.' ) );
	$port   = absint( $parts['port'] ?? ( 'https' === $scheme ? 443 : 80 ) );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! $host || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return '';
	}
	if ( ( 'https' === $scheme && 443 !== $port ) || ( 'http' === $scheme && 80 !== $port ) || filter_var( $host, FILTER_VALIDATE_IP ) ) {
		return '';
	}
	return bds_monitoring_public_dns( $host ) ? $url : '';
}

function bds_monitoring_state( $user_id ) {
	$stored = get_user_meta( absint( $user_id ), '_bds_website_monitor_v1', true );
	$stored = is_array( $stored ) ? $stored : array();
	return array(
		'enabled'              => 'yes' === (string) ( $stored['enabled'] ?? '' ),
		'authorized'           => ! empty( $stored['authorization_confirmed_at'] ),
		'url'                  => esc_url_raw( (string) ( $stored['url'] ?? '' ), array( 'http', 'https' ) ),
		'status'               => sanitize_key( (string) ( $stored['status'] ?? 'not_configured' ) ),
		'http_status'          => absint( $stored['http_status'] ?? 0 ),
		'latency_ms'           => absint( $stored['latency_ms'] ?? 0 ),
		'response_bytes'       => absint( $stored['response_bytes'] ?? 0 ),
		'checked_at'           => sanitize_text_field( (string) ( $stored['checked_at'] ?? '' ) ),
		'consecutive_failures' => absint( $stored['consecutive_failures'] ?? 0 ),
		'error_code'           => sanitize_key( (string) ( $stored['error_code'] ?? '' ) ),
	);
}

function bds_monitoring_save_state( $user_id, $changes ) {
	$current = get_user_meta( absint( $user_id ), '_bds_website_monitor_v1', true );
	$current = is_array( $current ) ? $current : array();
	$next    = array_merge( $current, is_array( $changes ) ? $changes : array() );
	return update_user_meta( absint( $user_id ), '_bds_website_monitor_v1', $next );
}

function bds_monitoring_permission() {
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 4096 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	return is_user_logged_in();
}

function bds_monitoring_response( $user_id ) {
	$state = bds_monitoring_state( $user_id );
	return array_merge(
		$state,
		array(
			'available' => bds_monitoring_enabled() && ! empty( bds_monitoring_plan( $user_id ) ),
			'source'    => 'onboarding_profile',
			'note'      => 'Availability checks do not measure SEO rankings, traffic, leads, or revenue.',
		)
	);
}

function bds_monitoring_get() {
	return rest_ensure_response( bds_monitoring_response( get_current_user_id() ) );
}

function bds_monitoring_enqueue( $user_id ) {
	$user_id = absint( $user_id );
	if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'bds_monitoring_check_user', array( $user_id ), 'branddad-monitoring' ) ) {
		return true;
	}
	if ( function_exists( 'as_enqueue_async_action' ) ) {
		return (bool) as_enqueue_async_action( 'bds_monitoring_check_user', array( $user_id ), 'branddad-monitoring', true );
	}
	if ( wp_next_scheduled( 'bds_monitoring_check_user', array( $user_id ) ) ) {
		return true;
	}
	return (bool) wp_schedule_single_event( time() + 5, 'bds_monitoring_check_user', array( $user_id ) );
}

function bds_monitoring_configure( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! bds_monitoring_enabled() || ! bds_monitoring_plan( $user_id ) ) {
		return new WP_Error( 'monitoring_unavailable', 'Website monitoring is not included in the active plan.', array( 'status' => 403 ) );
	}
	$enabled = rest_sanitize_boolean( $request->get_param( 'enabled' ) );
	if ( ! $enabled ) {
		bds_monitoring_save_state( $user_id, array( 'enabled' => 'no', 'status' => 'disabled', 'error_code' => '' ) );
		delete_user_meta( $user_id, '_bds_monitor_enabled' );
		return rest_ensure_response( bds_monitoring_response( $user_id ) );
	}
	if ( ! rest_sanitize_boolean( $request->get_param( 'authorization_confirmed' ) ) ) {
		return new WP_Error( 'authorization_required', 'Confirm that you own, manage, or have permission to monitor this website.', array( 'status' => 422 ) );
	}
	$url = bds_monitoring_safe_url( bds_monitoring_profile_url( $user_id ) );
	if ( ! $url ) {
		return new WP_Error( 'invalid_monitor_url', 'Add a public website to onboarding before enabling monitoring.', array( 'status' => 422 ) );
	}
	bds_monitoring_save_state( $user_id, array( 'enabled' => 'yes', 'url' => $url, 'authorization_confirmed_at' => gmdate( DATE_ATOM ), 'status' => 'pending', 'error_code' => '' ) );
	update_user_meta( $user_id, '_bds_monitor_enabled', 'yes' );
	if ( ! bds_monitoring_enqueue( $user_id ) ) {
		bds_monitoring_save_state( $user_id, array( 'status' => 'queue_failed', 'error_code' => 'queue_failed' ) );
		return new WP_Error( 'queue_failed', 'The first website check could not be scheduled.', array( 'status' => 503 ) );
	}
	return new WP_REST_Response( bds_monitoring_response( $user_id ), 202 );
}

function bds_monitoring_manual_check() {
	$user_id = get_current_user_id();
	$state   = bds_monitoring_state( $user_id );
	if ( ! bds_monitoring_enabled() || ! bds_monitoring_plan( $user_id ) || ! $state['enabled'] ) {
		return new WP_Error( 'monitoring_unavailable', 'Enable website monitoring first.', array( 'status' => 403 ) );
	}
	$last = absint( get_user_meta( $user_id, '_bds_monitor_manual_requested', true ) );
	if ( $last > time() - HOUR_IN_SECONDS ) {
		return new WP_Error( 'monitoring_rate_limited', 'A manual check was already requested this hour.', array( 'status' => 429 ) );
	}
	update_user_meta( $user_id, '_bds_monitor_manual_requested', time() );
	if ( ! bds_monitoring_enqueue( $user_id ) ) {
		return new WP_Error( 'queue_failed', 'The website check could not be scheduled.', array( 'status' => 503 ) );
	}
	return new WP_REST_Response( bds_monitoring_response( $user_id ), 202 );
}

function bds_monitoring_lock( $user_id ) {
	$key = 'bds_monitor_' . absint( $user_id );
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	if ( absint( get_option( $key, 0 ) ) < time() - 120 ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

function bds_monitoring_record_month( $user_id, $status ) {
	$month = gmdate( 'Y-m' );
	$all   = get_user_meta( absint( $user_id ), '_bds_monitor_monthly_v1', true );
	$all   = is_array( $all ) ? $all : array();
	$row   = is_array( $all[ $month ] ?? null ) ? $all[ $month ] : array( 'checks' => 0, 'available' => 0, 'failures' => 0 );
	$row['checks'] = absint( $row['checks'] ) + 1;
	if ( 'available' === $status ) {
		$row['available'] = absint( $row['available'] ) + 1;
	} else {
		$row['failures'] = absint( $row['failures'] ) + 1;
	}
	$all[ $month ] = $row;
	ksort( $all );
	update_user_meta( $user_id, '_bds_monitor_monthly_v1', array_slice( $all, -24, null, true ) );
}

function bds_monitoring_check_user( $user_id ) {
	$user_id = absint( $user_id );
	$state   = bds_monitoring_state( $user_id );
	if ( ! bds_monitoring_enabled() || ! $state['enabled'] || ! $state['authorized'] ) {
		return;
	}
	if ( ! bds_monitoring_plan( $user_id ) ) {
		bds_monitoring_save_state( $user_id, array( 'enabled' => 'no', 'status' => 'inactive_plan', 'error_code' => 'inactive_plan' ) );
		delete_user_meta( $user_id, '_bds_monitor_enabled' );
		return;
	}
	$profile_url = bds_monitoring_profile_url( $user_id );
	if ( ! hash_equals( (string) $state['url'], (string) $profile_url ) ) {
		bds_monitoring_save_state( $user_id, array( 'enabled' => 'no', 'status' => 'needs_reconnect', 'error_code' => 'website_changed' ) );
		delete_user_meta( $user_id, '_bds_monitor_enabled' );
		return;
	}
	$lock = bds_monitoring_lock( $user_id );
	if ( ! $lock ) {
		return;
	}
	try {
		$checked_at = gmdate( DATE_ATOM );
		$url = bds_monitoring_safe_url( $state['url'] );
		if ( ! $url ) {
			bds_monitoring_save_state( $user_id, array( 'status' => 'failed', 'error_code' => 'unsafe_or_unresolved_url', 'checked_at' => $checked_at, 'consecutive_failures' => $state['consecutive_failures'] + 1 ) );
			bds_monitoring_record_month( $user_id, 'failed' );
			do_action( 'bds_website_monitor_checked', $user_id, 'failed', $checked_at );
			return;
		}
		$started  = microtime( true );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 10,
				'redirection'         => 0,
				'limit_response_size' => 65536,
				'user-agent'          => 'BrandDadWebsiteMonitor/1.0 (+https://branddad.social/)',
			)
		);
		if ( is_wp_error( $response ) ) {
			$failures = $state['consecutive_failures'] + 1;
			bds_monitoring_save_state( $user_id, array( 'status' => 'failed', 'http_status' => 0, 'latency_ms' => 0, 'response_bytes' => 0, 'checked_at' => $checked_at, 'consecutive_failures' => $failures, 'error_code' => 'network_failed' ) );
			bds_monitoring_record_month( $user_id, 'failed' );
			do_action( 'bds_website_monitor_checked', $user_id, 'failed', $checked_at );
			return;
		}
		$code    = absint( wp_remote_retrieve_response_code( $response ) );
		$body    = (string) wp_remote_retrieve_body( $response );
		$status  = $code >= 200 && $code < 400 ? 'available' : 'failed';
		$latency = min( 60000, max( 0, (int) round( ( microtime( true ) - $started ) * 1000 ) ) );
		$failures = 'available' === $status ? 0 : $state['consecutive_failures'] + 1;
		bds_monitoring_save_state(
			$user_id,
			array(
				'status'               => $status,
				'http_status'          => $code,
				'latency_ms'           => $latency,
				'response_bytes'       => strlen( $body ),
				'content_hash'         => hash( 'sha256', $body ),
				'checked_at'           => $checked_at,
				'consecutive_failures' => $failures,
				'error_code'           => 'available' === $status ? '' : 'http_error',
			)
		);
		bds_monitoring_record_month( $user_id, $status );
		do_action( 'bds_website_monitor_checked', $user_id, $status, $checked_at );
	} finally {
		delete_option( $lock );
	}
}
add_action( 'bds_monitoring_check_user', 'bds_monitoring_check_user', 10, 1 );

function bds_monitoring_customer_summary( $user_id, $period ) {
	$all = get_user_meta( absint( $user_id ), '_bds_monitor_monthly_v1', true );
	$row = is_array( $all ) && is_array( $all[ $period ] ?? null ) ? $all[ $period ] : array( 'checks' => 0, 'available' => 0, 'failures' => 0 );
	return array( 'checks' => absint( $row['checks'] ?? 0 ), 'available' => absint( $row['available'] ?? 0 ), 'failures' => absint( $row['failures'] ?? 0 ), 'current' => bds_monitoring_state( $user_id ) );
}

function bds_monitoring_batch_lock() {
	$key = 'bds_monitor_batch_lock';
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	if ( absint( get_option( $key, 0 ) ) < time() - 300 ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

function bds_monitoring_run_batch() {
	if ( ! bds_monitoring_enabled() ) {
		return;
	}
	$lock = bds_monitoring_batch_lock();
	if ( ! $lock ) {
		return;
	}
	try {
		$offset = absint( get_option( 'bds_monitor_batch_offset', 0 ) );
		$users  = get_users( array( 'fields' => 'ID', 'number' => 20, 'offset' => $offset, 'meta_key' => '_bds_monitor_enabled', 'meta_value' => 'yes', 'orderby' => 'ID', 'order' => 'ASC' ) );
		foreach ( $users as $user_id ) {
			bds_monitoring_enqueue( $user_id );
		}
		if ( 20 === count( $users ) ) {
			update_option( 'bds_monitor_batch_offset', $offset + 20, false );
			if ( ! wp_next_scheduled( 'bds_monitoring_batch_continue' ) ) {
				wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'bds_monitoring_batch_continue' );
			}
		} else {
			update_option( 'bds_monitor_batch_offset', 0, false );
		}
	} finally {
		delete_option( $lock );
	}
}
add_action( 'bds_monitoring_batch_daily', 'bds_monitoring_run_batch' );
add_action( 'bds_monitoring_batch_continue', 'bds_monitoring_run_batch' );

function bds_monitoring_schedule() {
	if ( bds_monitoring_enabled() && ! wp_next_scheduled( 'bds_monitoring_batch_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bds_monitoring_batch_daily' );
	}
}
add_action( 'init', 'bds_monitoring_schedule' );

function bds_monitoring_register_routes() {
	register_rest_route( 'branddad/v1', '/monitor/site', array( array( 'methods' => 'GET', 'callback' => 'bds_monitoring_get', 'permission_callback' => 'bds_monitoring_permission' ), array( 'methods' => 'POST', 'callback' => 'bds_monitoring_configure', 'permission_callback' => 'bds_monitoring_permission' ) ) );
	register_rest_route( 'branddad/v1', '/monitor/site/check', array( 'methods' => 'POST', 'callback' => 'bds_monitoring_manual_check', 'permission_callback' => 'bds_monitoring_permission' ) );
}
add_action( 'rest_api_init', 'bds_monitoring_register_routes' );
