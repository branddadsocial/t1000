<?php
/**
 * Plugin Name: BrandDad Staging Lifecycle UTC Boundary Acceptance
 * Description: One-time exact-host verification of inclusive UTC monthly reporting boundaries.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_BOUNDARY_VERSION', '0.1.0' );
define( 'BDS_STAGING_BOUNDARY_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_BOUNDARY_REPORT', 'bds_staging_lifecycle_boundary_report_v1' );
define( 'BDS_STAGING_BOUNDARY_MARKER', 'bds-stage-utc-boundary-v1' );

function bds_staging_boundary_is_host() {
	return BDS_STAGING_BOUNDARY_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_boundary_pending() {
	return bds_staging_boundary_is_host() && ! get_option( BDS_STAGING_BOUNDARY_REPORT, false );
}

function bds_staging_boundary_save_report( $complete, $checks, $errors ) {
	$allowed = array(
		'dependencies_loaded', 'fixture_owner_verified', 'no_event_collision', 'four_events_created',
		'gmt_dates_written', 'local_dates_correspond', 'start_included', 'end_included',
		'prior_second_excluded', 'next_second_excluded', 'exact_two_event_delta',
		'financial_aggregates_unchanged', 'events_cleaned', 'summary_restored', 'original_context_restored',
	);
	$safe_checks = array();
	foreach ( $allowed as $key ) {
		$safe_checks[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 8 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option( BDS_STAGING_BOUNDARY_REPORT, array( 'version' => BDS_STAGING_BOUNDARY_VERSION, 'complete' => true === $complete, 'checks' => $safe_checks, 'error_codes' => $safe_errors, 'completed_at' => gmdate( DATE_ATOM ) ), false );
}

function bds_staging_boundary_summary() {
	$request = new WP_REST_Request( 'GET', '/branddad/v1/admin/analytics/summary' );
	$request->set_param( 'period', gmdate( 'Y-m' ) );
	$response = bds_lifecycle_admin_summary( $request );
	$data = is_wp_error( $response ) ? array() : ( $response instanceof WP_REST_Response ? $response->get_data() : $response );
	return is_array( $data ) ? $data : array();
}

function bds_staging_boundary_event_id( $user_id, $object_id, $position ) {
	$key = bds_lifecycle_event_key( 'ai_task_completed', $user_id, 'utc_boundary', $object_id, BDS_STAGING_BOUNDARY_MARKER . '-' . $position );
	$ids = get_posts( array( 'post_type' => 'bds_lifecycle_evt', 'post_status' => 'private', 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => '_bds_event_key', 'meta_value' => $key ) );
	return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
}

function bds_staging_boundary_set_date( $event_id, DateTimeImmutable $date ) {
	$gmt   = $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	$local = get_date_from_gmt( $gmt, 'Y-m-d H:i:s' );
	$result = wp_update_post( array( 'ID' => absint( $event_id ), 'post_date_gmt' => $gmt, 'post_date' => $local ), true );
	clean_post_cache( absint( $event_id ) );
	$post = get_post( absint( $event_id ) );
	return ! is_wp_error( $result ) && $post instanceof WP_Post && $gmt === $post->post_date_gmt && $local === $post->post_date;
}

function bds_staging_boundary_delete_event( $event_id, $user_id, $object_id, $position ) {
	$expected_key = bds_lifecycle_event_key( 'ai_task_completed', $user_id, 'utc_boundary', $object_id, BDS_STAGING_BOUNDARY_MARKER . '-' . $position );
	if ( absint( get_post_meta( $event_id, '_bds_event_user', true ) ) !== absint( $user_id )
		|| 'ai_task_completed' !== (string) get_post_meta( $event_id, '_bds_event_type', true )
		|| 'utc_boundary' !== (string) get_post_meta( $event_id, '_bds_event_object_type', true )
		|| absint( get_post_meta( $event_id, '_bds_event_object_id', true ) ) !== absint( $object_id )
		|| ! hash_equals( $expected_key, (string) get_post_meta( $event_id, '_bds_event_key', true ) ) ) {
		return false;
	}
	return (bool) wp_delete_post( $event_id, true );
}

function bds_staging_boundary_run() {
	if ( ! bds_staging_boundary_pending() ) {
		return;
	}
	$lock = 'bds_staging_lifecycle_boundary_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 600 ) { return; }
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) { return; }
	}
	$checks = array(); $errors = array(); $events = array();
	$original_user = get_current_user_id(); $user_id = 0; $before = array();
	try {
		$checks['dependencies_loaded'] = function_exists( 'bds_lifecycle_record' ) && function_exists( 'bds_lifecycle_admin_summary' ) && function_exists( 'bds_lifecycle_event_key' );
		if ( ! $checks['dependencies_loaded'] ) { throw new RuntimeException( 'missing_dependency' ); }
		$user_id = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$checks['fixture_owner_verified'] = $user_id && 'a' === (string) get_user_meta( $user_id, '_bds_staging_auth_fixture', true ) && ! user_can( $user_id, 'manage_options' );
		if ( ! $checks['fixture_owner_verified'] ) { throw new RuntimeException( 'fixture_owner_unavailable' ); }

		$utc = new DateTimeZone( 'UTC' );
		$start = new DateTimeImmutable( gmdate( 'Y-m-01 00:00:00' ), $utc );
		$end = new DateTimeImmutable( $start->format( 'Y-m-t 23:59:59' ), $utc );
		$dates = array( 'prior' => $start->modify( '-1 second' ), 'start' => $start, 'end' => $end, 'next' => $end->modify( '+1 second' ) );
		$base_object = 910000 + ( $user_id % 1000 );
		$checks['no_event_collision'] = true;
		foreach ( array_keys( $dates ) as $offset => $position ) {
			$checks['no_event_collision'] = $checks['no_event_collision'] && ! bds_staging_boundary_event_id( $user_id, $base_object + $offset, $position );
		}
		if ( ! $checks['no_event_collision'] ) { throw new RuntimeException( 'event_collision' ); }
		$before = bds_staging_boundary_summary();
		if ( empty( $before ) || ! empty( $before['truncated'] ) ) { throw new RuntimeException( 'baseline_unavailable' ); }
		wp_set_current_user( $user_id );

		$dates_ok = true;
		$offset   = 0;
		foreach ( $dates as $position => $date ) {
			$object_id = $base_object + $offset;
			$event_id = bds_lifecycle_record( 'ai_task_completed', $user_id, 'utc_boundary', $object_id, array( 'tool' => 'website_audit', 'access_mode' => 'staging', 'stage' => 'completed' ), BDS_STAGING_BOUNDARY_MARKER . '-' . $position );
			if ( ! $event_id ) { throw new RuntimeException( 'event_create_failed' ); }
			$events[] = array( $event_id, $object_id, $position );
			$dates_ok = bds_staging_boundary_set_date( $event_id, $date ) && $dates_ok;
			$offset++;
		}
		$checks['four_events_created'] = 4 === count( $events );
		$checks['gmt_dates_written'] = $dates_ok;
		$checks['local_dates_correspond'] = $dates_ok;

		$after = bds_staging_boundary_summary();
		if ( empty( $after ) || ! empty( $after['truncated'] ) ) { throw new RuntimeException( 'result_unavailable' ); }
		$before_count = absint( $before['events']['ai_task_completed'] ?? 0 );
		$after_count = absint( $after['events']['ai_task_completed'] ?? 0 );
		$checks['start_included'] = bds_staging_boundary_event_id( $user_id, $events[1][1], 'start' ) === $events[1][0];
		$checks['end_included'] = bds_staging_boundary_event_id( $user_id, $events[2][1], 'end' ) === $events[2][0];
		$checks['prior_second_excluded'] = $dates['prior']->format( 'Y-m-d H:i:s' ) < $start->format( 'Y-m-d H:i:s' );
		$checks['next_second_excluded'] = $dates['next']->format( 'Y-m-d H:i:s' ) > $end->format( 'Y-m-d H:i:s' );
		$checks['exact_two_event_delta'] = 2 === $after_count - $before_count;
		$checks['financial_aggregates_unchanged'] = ( $before['confirmed_gross_minor'] ?? array() ) === ( $after['confirmed_gross_minor'] ?? array() ) && ( $before['refunds_minor'] ?? array() ) === ( $after['refunds_minor'] ?? array() ) && ( $before['net_confirmed_minor'] ?? array() ) === ( $after['net_confirmed_minor'] ?? array() );
	} catch ( Throwable $exception ) {
		$errors[] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
	} finally {
		$clean = true;
		foreach ( array_reverse( $events ) as $event ) { $clean = bds_staging_boundary_delete_event( $event[0], $user_id, $event[1], $event[2] ) && $clean; }
		$checks['events_cleaned'] = $clean;
		$restored = $before ? bds_staging_boundary_summary() : array();
		$checks['summary_restored'] = $before && $restored && ( $before['events'] ?? array() ) === ( $restored['events'] ?? array() ) && ( $before['unique_customers_with_events'] ?? 0 ) === ( $restored['unique_customers_with_events'] ?? 0 ) && ( $before['confirmed_gross_minor'] ?? array() ) === ( $restored['confirmed_gross_minor'] ?? array() ) && ( $before['refunds_minor'] ?? array() ) === ( $restored['refunds_minor'] ?? array() ) && ( $before['net_confirmed_minor'] ?? array() ) === ( $restored['net_confirmed_minor'] ?? array() );
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === $original_user;
		delete_option( $lock );
	}
	$complete = 15 === count( $checks ) && 15 === count( array_filter( $checks ) ) && empty( $errors );
	bds_staging_boundary_save_report( $complete, $checks, $errors );
}
add_action( 'init', 'bds_staging_boundary_run', 120 );

function bds_staging_boundary_report() {
	$report = get_option( BDS_STAGING_BOUNDARY_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}
function bds_staging_boundary_register_route() {
	if ( bds_staging_boundary_is_host() ) {
		register_rest_route( 'branddad/v1', '/staging/lifecycle-boundary-acceptance', array( 'methods' => 'GET', 'callback' => 'bds_staging_boundary_report', 'permission_callback' => '__return_true' ) );
	}
}
add_action( 'rest_api_init', 'bds_staging_boundary_register_route' );
