<?php
/**
 * Plugin Name: BrandDad Staging Website Monitoring Acceptance
 * Description: One-time, exact-host website-monitoring authorization, SSRF, fetch, lock, lifecycle, and tenant-isolation acceptance.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_MONITOR_VERSION', '0.1.0' );
define( 'BDS_STAGING_MONITOR_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_MONITOR_REPORT_OPTION', 'bds_staging_monitor_report_v1' );

function bds_staging_monitor_is_host() {
	return BDS_STAGING_MONITOR_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

if ( bds_staging_monitor_is_host() && ! get_option( BDS_STAGING_MONITOR_REPORT_OPTION, false ) && ! defined( 'BDS_WEBSITE_MONITORING_ENABLED' ) ) {
	define( 'BDS_WEBSITE_MONITORING_ENABLED', true );
}

function bds_staging_monitor_disable_recurring_schedule() {
	if ( bds_staging_monitor_is_host() && ! get_option( BDS_STAGING_MONITOR_REPORT_OPTION, false ) ) {
		remove_action( 'init', 'bds_monitoring_schedule' );
	}
}
add_action( 'init', 'bds_staging_monitor_disable_recurring_schedule', 0 );

function bds_staging_monitor_plan_candidate( $plan, $user_id ) {
	if ( ! bds_staging_monitor_is_host() || get_option( BDS_STAGING_MONITOR_REPORT_OPTION, false ) ) {
		return $plan;
	}
	if ( 'b' !== (string) get_user_meta( absint( $user_id ), '_bds_staging_auth_fixture', true ) || user_can( $user_id, 'manage_options' ) ) {
		return $plan;
	}
	return array(
		'subscription_id' => 990200,
		'slug'            => 'ai-business-monitoring',
		'plan_id'         => 'staging-monitor-fixture',
		'credits'         => 0,
		'allowance_cycle' => 1,
		'access_mode'     => 'paid',
	);
}
add_filter( 'bds_monitoring_plan_candidate', 'bds_staging_monitor_plan_candidate', 10, 2 );

function bds_staging_monitor_save_report( $complete, $checks, $errors = array() ) {
	$allowlist = array(
		'dependencies_loaded',
		'fixture_users_verified',
		'anonymous_permission_denied',
		'synthetic_plan_revalidated',
		'localhost_rejected',
		'ip_literal_rejected',
		'credentialed_url_rejected',
		'nonstandard_port_rejected',
		'public_dns_url_accepted',
		'authorization_required',
		'public_fetch_available',
		'response_measurements_bounded',
		'content_hash_only',
		'monthly_available_delta_correct',
		'value_report_delta_correct',
		'active_lock_blocks_duplicate',
		'stale_lock_recovers',
		'stale_lock_delta_correct',
		'network_error_fails_closed',
		'network_failure_delta_correct',
		'redirect_not_followed',
		'url_change_disables_monitor',
		'plan_loss_disables_monitor',
		'manual_check_rate_limited',
		'cross_account_report_unchanged',
		'fixture_metadata_restored',
		'recurring_schedule_not_created',
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
		BDS_STAGING_MONITOR_REPORT_OPTION,
		array(
			'version'      => BDS_STAGING_MONITOR_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_monitor_snapshot_meta( $user_id, $keys ) {
	$out = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = array(
			'exists' => metadata_exists( 'user', absint( $user_id ), $key ),
			'value'  => get_user_meta( absint( $user_id ), $key, true ),
		);
	}
	return $out;
}

function bds_staging_monitor_restore_meta( $user_id, $snapshot ) {
	foreach ( (array) $snapshot as $key => $row ) {
		if ( ! empty( $row['exists'] ) ) {
			update_user_meta( absint( $user_id ), sanitize_key( $key ), $row['value'] );
		} else {
			delete_user_meta( absint( $user_id ), sanitize_key( $key ) );
		}
	}
}

function bds_staging_monitor_meta_matches( $user_id, $snapshot ) {
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

function bds_staging_monitor_set_profile_url( $user_id, $url ) {
	$state = bds_onboarding_state( $user_id );
	$state['version'] = absint( $state['version'] ) + 1;
	$state['profile']['website_url'] = esc_url_raw( (string) $url, array( 'http', 'https' ) );
	$state['updated_at'] = gmdate( DATE_ATOM );
	update_user_meta( absint( $user_id ), '_bds_onboarding_v1', $state );
}

function bds_staging_monitor_set_ready_state( $user_id, $url ) {
	update_user_meta(
		absint( $user_id ),
		'_bds_website_monitor_v1',
		array(
			'enabled'                    => 'yes',
			'authorization_confirmed_at' => gmdate( DATE_ATOM ),
			'url'                        => esc_url_raw( (string) $url, array( 'http', 'https' ) ),
			'status'                     => 'pending',
			'http_status'                => 0,
			'latency_ms'                 => 0,
			'response_bytes'             => 0,
			'checked_at'                 => '',
			'consecutive_failures'       => 0,
			'error_code'                 => '',
		)
	);
	update_user_meta( absint( $user_id ), '_bds_monitor_enabled', 'yes' );
}

function bds_staging_monitor_force_network_error( $pre, $args, $url ) {
	$target = 'https://' . BDS_STAGING_MONITOR_HOST . '/';
	return hash_equals( $target, (string) $url ) ? new WP_Error( 'http_request_failed', 'Synthetic staging network failure.' ) : $pre;
}

function bds_staging_monitor_config_request() {
	$request = new WP_REST_Request( 'POST', '/branddad/v1/monitor/site' );
	$request->set_param( 'enabled', true );
	$request->set_param( 'authorization_confirmed', false );
	return $request;
}

function bds_staging_monitor_run() {
	if ( ! bds_staging_monitor_is_host() || get_option( BDS_STAGING_MONITOR_REPORT_OPTION, false ) ) {
		return;
	}
	$run_lock = 'bds_staging_monitor_acceptance_lock_v1';
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
	$user_a = 0;
	$user_b = 0;
	$snapshot_a = array();
	$snapshot_b = array();
	$restored = false;
	$checks = array();
	$errors = array();
	$meta_keys = array( '_bds_onboarding_v1', '_bds_website_monitor_v1', '_bds_monitor_enabled', '_bds_monitor_monthly_v1', '_bds_monitor_manual_requested' );
	try {
		$required = array( 'bds_monitoring_permission', 'bds_monitoring_plan', 'bds_monitoring_safe_url', 'bds_monitoring_configure', 'bds_monitoring_check_user', 'bds_monitoring_state', 'bds_monitoring_customer_summary', 'bds_customer_value_report' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				$errors[] = 'missing_dependency';
			}
		}
		if ( ! $checks['dependencies_loaded'] ) {
			bds_staging_monitor_save_report( false, $checks, $errors );
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
			bds_staging_monitor_save_report( false, $checks, $errors );
			return;
		}

		$snapshot_a = bds_staging_monitor_snapshot_meta( $user_a, $meta_keys );
		$snapshot_b = bds_staging_monitor_snapshot_meta( $user_b, $meta_keys );
		$base_url = 'https://' . BDS_STAGING_MONITOR_HOST . '/';
		$period = gmdate( 'Y-m' );

		wp_set_current_user( 0 );
		$checks['anonymous_permission_denied'] = false === bds_monitoring_permission();
		wp_set_current_user( $user_b );
		$plan = bds_monitoring_plan( $user_b );
		$checks['synthetic_plan_revalidated'] = 'ai-business-monitoring' === (string) ( $plan['slug'] ?? '' )
			&& 'paid' === (string) ( $plan['access_mode'] ?? '' );

		$checks['localhost_rejected'] = '' === bds_monitoring_safe_url( 'http://localhost/private' );
		$checks['ip_literal_rejected'] = '' === bds_monitoring_safe_url( 'http://127.0.0.1/private' );
		$checks['credentialed_url_rejected'] = '' === bds_monitoring_safe_url( 'https://user:pass@' . BDS_STAGING_MONITOR_HOST . '/' );
		$checks['nonstandard_port_rejected'] = '' === bds_monitoring_safe_url( 'https://' . BDS_STAGING_MONITOR_HOST . ':444/' );
		$checks['public_dns_url_accepted'] = $base_url === bds_monitoring_safe_url( $base_url );

		bds_staging_monitor_set_profile_url( $user_b, $base_url );
		$authorization = bds_monitoring_configure( bds_staging_monitor_config_request() );
		$checks['authorization_required'] = is_wp_error( $authorization ) && 'authorization_required' === $authorization->get_error_code();

		$before_month = bds_monitoring_customer_summary( $user_b, $period );
		$before_value = bds_customer_value_report( $user_b, $period );
		$other_before = bds_customer_value_report( $user_a, $period );
		bds_staging_monitor_set_ready_state( $user_b, $base_url );
		bds_monitoring_check_user( $user_b );
		$available = bds_monitoring_state( $user_b );
		$available_raw = get_user_meta( $user_b, '_bds_website_monitor_v1', true );
		$after_first = bds_monitoring_customer_summary( $user_b, $period );
		$after_first_value = bds_customer_value_report( $user_b, $period );
		$checks['public_fetch_available'] = 'available' === $available['status'] && $available['http_status'] >= 200 && $available['http_status'] < 300;
		$checks['response_measurements_bounded'] = $available['response_bytes'] > 0 && $available['response_bytes'] <= 65536 && $available['latency_ms'] <= 60000;
		$checks['content_hash_only'] = is_array( $available_raw )
			&& 1 === preg_match( '/^[a-f0-9]{64}$/', (string) ( $available_raw['content_hash'] ?? '' ) )
			&& ! isset( $available_raw['body'] ) && ! isset( $available_raw['content'] );
		$checks['monthly_available_delta_correct'] = 1 === absint( $after_first['checks'] ?? 0 ) - absint( $before_month['checks'] ?? 0 )
			&& 1 === absint( $after_first['available'] ?? 0 ) - absint( $before_month['available'] ?? 0 );
		$checks['value_report_delta_correct'] = 1 === absint( $after_first_value['website_monitoring']['checks'] ?? 0 ) - absint( $before_value['website_monitoring']['checks'] ?? 0 )
			&& 1 === absint( $after_first_value['website_monitoring']['available'] ?? 0 ) - absint( $before_value['website_monitoring']['available'] ?? 0 );

		$lock_key = 'bds_monitor_' . $user_b;
		update_option( $lock_key, time(), false );
		$locked_before = bds_monitoring_customer_summary( $user_b, $period );
		bds_monitoring_check_user( $user_b );
		$locked_after = bds_monitoring_customer_summary( $user_b, $period );
		$checks['active_lock_blocks_duplicate'] = absint( $locked_before['checks'] ?? 0 ) === absint( $locked_after['checks'] ?? 0 );

		update_option( $lock_key, time() - 121, false );
		bds_monitoring_check_user( $user_b );
		$stale_after = bds_monitoring_customer_summary( $user_b, $period );
		$checks['stale_lock_recovers'] = 'available' === (string) ( $stale_after['current']['status'] ?? '' ) && false === get_option( $lock_key, false );
		$checks['stale_lock_delta_correct'] = 1 === absint( $stale_after['checks'] ?? 0 ) - absint( $locked_after['checks'] ?? 0 );

		bds_staging_monitor_set_ready_state( $user_b, $base_url );
		$failure_before = bds_monitoring_customer_summary( $user_b, $period );
		add_filter( 'pre_http_request', 'bds_staging_monitor_force_network_error', 10, 3 );
		bds_monitoring_check_user( $user_b );
		remove_filter( 'pre_http_request', 'bds_staging_monitor_force_network_error', 10 );
		$failure_after = bds_monitoring_customer_summary( $user_b, $period );
		$checks['network_error_fails_closed'] = 'failed' === (string) ( $failure_after['current']['status'] ?? '' )
			&& 'network_failed' === (string) ( $failure_after['current']['error_code'] ?? '' )
			&& 0 === absint( $failure_after['current']['http_status'] ?? 0 );
		$checks['network_failure_delta_correct'] = 1 === absint( $failure_after['checks'] ?? 0 ) - absint( $failure_before['checks'] ?? 0 )
			&& 1 === absint( $failure_after['failures'] ?? 0 ) - absint( $failure_before['failures'] ?? 0 );

		$redirect_url = $base_url . 'wp-admin/';
		bds_staging_monitor_set_profile_url( $user_b, $redirect_url );
		bds_staging_monitor_set_ready_state( $user_b, $redirect_url );
		bds_monitoring_check_user( $user_b );
		$redirect_state = bds_monitoring_state( $user_b );
		$checks['redirect_not_followed'] = in_array( $redirect_state['http_status'], array( 301, 302, 303, 307, 308 ), true )
			&& 'available' === $redirect_state['status'];

		bds_staging_monitor_set_profile_url( $user_b, 'https://example.com/' );
		bds_staging_monitor_set_ready_state( $user_b, $base_url );
		bds_monitoring_check_user( $user_b );
		$url_change = bds_monitoring_state( $user_b );
		$checks['url_change_disables_monitor'] = ! $url_change['enabled'] && 'needs_reconnect' === $url_change['status'] && 'website_changed' === $url_change['error_code'];

		bds_staging_monitor_set_profile_url( $user_b, $base_url );
		bds_staging_monitor_set_ready_state( $user_b, $base_url );
		remove_filter( 'bds_monitoring_plan_candidate', 'bds_staging_monitor_plan_candidate', 10 );
		bds_monitoring_check_user( $user_b );
		$plan_loss = bds_monitoring_state( $user_b );
		$checks['plan_loss_disables_monitor'] = ! $plan_loss['enabled'] && 'inactive_plan' === $plan_loss['status'] && 'inactive_plan' === $plan_loss['error_code'];
		add_filter( 'bds_monitoring_plan_candidate', 'bds_staging_monitor_plan_candidate', 10, 2 );

		bds_staging_monitor_set_ready_state( $user_b, $base_url );
		update_user_meta( $user_b, '_bds_monitor_manual_requested', time() );
		$manual = bds_monitoring_manual_check();
		$checks['manual_check_rate_limited'] = is_wp_error( $manual ) && 'monitoring_rate_limited' === $manual->get_error_code();
		$other_after = bds_customer_value_report( $user_a, $period );
		$checks['cross_account_report_unchanged'] = $other_before['website_monitoring'] === $other_after['website_monitoring'];

		bds_staging_monitor_restore_meta( $user_a, $snapshot_a );
		bds_staging_monitor_restore_meta( $user_b, $snapshot_b );
		$restored = true;
		$checks['fixture_metadata_restored'] = bds_staging_monitor_meta_matches( $user_a, $snapshot_a ) && bds_staging_monitor_meta_matches( $user_b, $snapshot_b );
		$checks['recurring_schedule_not_created'] = false === wp_next_scheduled( 'bds_monitoring_batch_daily' );

		$complete = 27 === count( $checks ) && 27 === count( array_filter( $checks ) ) && empty( $errors );
		bds_staging_monitor_save_report( $complete, $checks, $errors );
	} finally {
		remove_filter( 'pre_http_request', 'bds_staging_monitor_force_network_error', 10 );
		add_filter( 'bds_monitoring_plan_candidate', 'bds_staging_monitor_plan_candidate', 10, 2 );
		if ( ! $restored ) {
			if ( $user_a && $snapshot_a ) {
				bds_staging_monitor_restore_meta( $user_a, $snapshot_a );
			}
			if ( $user_b && $snapshot_b ) {
				bds_staging_monitor_restore_meta( $user_b, $snapshot_b );
			}
		}
		if ( $user_b ) {
			delete_option( 'bds_monitor_' . $user_b );
		}
		wp_set_current_user( $original_user );
		delete_option( $run_lock );
	}
}
add_action( 'init', 'bds_staging_monitor_run', 104 );

function bds_staging_monitor_report() {
	$report = get_option( BDS_STAGING_MONITOR_REPORT_OPTION, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_monitor_register_route() {
	if ( ! bds_staging_monitor_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/website-monitoring-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_monitor_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_monitor_register_route' );
