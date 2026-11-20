<?php
/**
 * Plugin Name: BrandDad Staging Lifecycle Truncation Acceptance
 * Description: Bounded, resumable exact-host verification of the 5,000-event analytics truncation flag.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_TRUNCATION_VERSION', '0.1.0' );
define( 'BDS_STAGING_TRUNCATION_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_TRUNCATION_REPORT', 'bds_staging_lifecycle_truncation_report_v1' );
define( 'BDS_STAGING_TRUNCATION_STATE', 'bds_staging_lifecycle_truncation_state_v1' );
define( 'BDS_STAGING_TRUNCATION_MARKER', 'bds-stage-truncation-v1' );
define( 'BDS_STAGING_TRUNCATION_TARGET', 5000 );
define( 'BDS_STAGING_TRUNCATION_BATCH', 20 );
define( 'BDS_STAGING_TRUNCATION_OBJECT_BASE', 940000 );
define( 'BDS_STAGING_TRUNCATION_CONTINUE_CREATION', false );

function bds_staging_truncation_is_host() {
	return BDS_STAGING_TRUNCATION_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_truncation_summary( $period ) {
	$request = new WP_REST_Request( 'GET', '/branddad/v1/admin/analytics/summary' );
	$request->set_param( 'period', sanitize_text_field( (string) $period ) );
	$response = bds_lifecycle_admin_summary( $request );
	$data = is_wp_error( $response ) ? array() : ( $response instanceof WP_REST_Response ? $response->get_data() : $response );
	if ( ! is_array( $data ) ) {
		return array();
	}
	return array(
		'period'                       => sanitize_text_field( (string) ( $data['period'] ?? '' ) ),
		'events'                       => array_map( 'absint', (array) ( $data['events'] ?? array() ) ),
		'unique_customers_with_events' => absint( $data['unique_customers_with_events'] ?? 0 ),
		'confirmed_gross_minor'        => array_map( 'absint', (array) ( $data['confirmed_gross_minor'] ?? array() ) ),
		'refunds_minor'                => array_map( 'absint', (array) ( $data['refunds_minor'] ?? array() ) ),
		'net_confirmed_minor'          => array_map( 'intval', (array) ( $data['net_confirmed_minor'] ?? array() ) ),
		'truncated'                    => true === ( $data['truncated'] ?? false ),
	);
}

function bds_staging_truncation_event_key( $user_id, $offset ) {
	return bds_lifecycle_event_key( 'ai_task_completed', $user_id, 'staging_truncation', BDS_STAGING_TRUNCATION_OBJECT_BASE + $offset, BDS_STAGING_TRUNCATION_MARKER . '-' . $offset );
}

function bds_staging_truncation_event_id( $user_id, $offset ) {
	$ids = get_posts(
		array(
			'post_type'      => 'bds_lifecycle_evt',
			'post_status'    => 'private',
			'posts_per_page' => 2,
			'fields'         => 'ids',
			'meta_key'       => '_bds_event_key',
			'meta_value'     => bds_staging_truncation_event_key( $user_id, $offset ),
		)
	);
	return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
}

function bds_staging_truncation_create_event( $user_id, $offset, $gmt ) {
	$gmt   = sanitize_text_field( (string) $gmt );
	$local = get_date_from_gmt( $gmt, 'Y-m-d H:i:s' );
	$event_id = wp_insert_post(
		array(
			'post_type'     => 'bds_lifecycle_evt',
			'post_status'   => 'private',
			'post_author'   => absint( $user_id ),
			'post_title'    => 'BrandDad event ai_task_completed',
			'post_date_gmt' => $gmt,
			'post_date'     => $local,
		),
		true
	);
	if ( is_wp_error( $event_id ) || ! $event_id ) {
		return 0;
	}
	$meta = array(
		'_bds_event_key'         => bds_staging_truncation_event_key( $user_id, $offset ),
		'_bds_event_type'        => 'ai_task_completed',
		'_bds_event_user'        => absint( $user_id ),
		'_bds_event_object_type' => 'staging_truncation',
		'_bds_event_object_id'   => BDS_STAGING_TRUNCATION_OBJECT_BASE + $offset,
		'_bds_event_tool'        => 'website_audit',
		'_bds_event_access_mode' => 'staging',
		'_bds_event_stage'       => 'completed',
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $event_id, $key, $value );
	}
	clean_post_cache( absint( $event_id ) );
	return absint( $event_id );
}

function bds_staging_truncation_event_owned( $event_id, $user_id, $offset, $gmt ) {
	$post = get_post( absint( $event_id ) );
	return $post instanceof WP_Post
		&& 'bds_lifecycle_evt' === $post->post_type
		&& 'private' === $post->post_status
		&& absint( $post->post_author ) === absint( $user_id )
		&& sanitize_text_field( (string) $gmt ) === $post->post_date_gmt
		&& absint( get_post_meta( $event_id, '_bds_event_user', true ) ) === absint( $user_id )
		&& 'ai_task_completed' === (string) get_post_meta( $event_id, '_bds_event_type', true )
		&& 'staging_truncation' === (string) get_post_meta( $event_id, '_bds_event_object_type', true )
		&& BDS_STAGING_TRUNCATION_OBJECT_BASE + $offset === absint( get_post_meta( $event_id, '_bds_event_object_id', true ) )
		&& hash_equals( bds_staging_truncation_event_key( $user_id, $offset ), (string) get_post_meta( $event_id, '_bds_event_key', true ) );
}

function bds_staging_truncation_delete_event( $event_id, $user_id, $offset, $gmt ) {
	if ( ! bds_staging_truncation_event_owned( $event_id, $user_id, $offset, $gmt ) ) {
		return false;
	}
	return (bool) wp_delete_post( absint( $event_id ), true );
}

function bds_staging_truncation_reconcile_events( $user_id, $gmt ) {
	$ids = get_posts(
		array(
			'post_type'      => 'bds_lifecycle_evt',
			'post_status'    => 'private',
			'posts_per_page' => BDS_STAGING_TRUNCATION_TARGET + 1,
			'fields'         => 'ids',
			'meta_key'       => '_bds_event_object_type',
			'meta_value'     => 'staging_truncation',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	if ( count( $ids ) > BDS_STAGING_TRUNCATION_TARGET ) {
		return new WP_Error( 'recovery_over_target' );
	}
	$map = array();
	foreach ( $ids as $event_id ) {
		$object_id = absint( get_post_meta( $event_id, '_bds_event_object_id', true ) );
		$offset = $object_id - BDS_STAGING_TRUNCATION_OBJECT_BASE;
		if ( $offset < 0 || $offset >= BDS_STAGING_TRUNCATION_TARGET || isset( $map[ $offset ] ) ) {
			return new WP_Error( 'recovery_range_or_duplicate' );
		}
		if ( ! bds_staging_truncation_event_owned( $event_id, $user_id, $offset, $gmt ) ) {
			return new WP_Error( 'recovery_ownership_failed' );
		}
		$map[ $offset ] = absint( $event_id );
	}
	ksort( $map, SORT_NUMERIC );
	$expected = 0;
	foreach ( array_keys( $map ) as $offset ) {
		if ( $expected !== absint( $offset ) ) {
			return new WP_Error( 'recovery_gap' );
		}
		$expected++;
	}
	return array_values( $map );
}

function bds_staging_truncation_allowed_checks() {
	return array(
		'dependencies_loaded', 'fixture_owner_verified', 'baseline_not_truncated', 'collision_free',
		'events_owned', 'target_created', 'ids_unique', 'truncation_flag_true',
		'cleanup_complete', 'summary_restored', 'state_removed',
	);
}

function bds_staging_truncation_safe_checks( $checks ) {
	$safe = array();
	foreach ( bds_staging_truncation_allowed_checks() as $key ) {
		$safe[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	return $safe;
}

function bds_staging_truncation_safe_errors( $errors ) {
	$safe = array();
	foreach ( array_slice( array_unique( (array) $errors ), 0, 8 ) as $error ) {
		$safe[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	return $safe;
}

function bds_staging_truncation_save_report( $checks, $errors ) {
	$safe_checks = bds_staging_truncation_safe_checks( $checks );
	$complete = count( bds_staging_truncation_allowed_checks() ) === count( array_filter( $safe_checks ) ) && empty( $errors );
	update_option(
		BDS_STAGING_TRUNCATION_REPORT,
		array(
			'version'      => BDS_STAGING_TRUNCATION_VERSION,
			'complete'     => $complete,
			'checks'       => $safe_checks,
			'error_codes'  => bds_staging_truncation_safe_errors( $errors ),
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_truncation_initial_state() {
	$checks = array();
	$checks['dependencies_loaded'] = function_exists( 'bds_lifecycle_admin_summary' ) && function_exists( 'bds_lifecycle_event_key' );
	if ( ! $checks['dependencies_loaded'] ) {
		throw new RuntimeException( 'missing_dependency' );
	}
	$user_id = absint( username_exists( 'bds_stage_acceptance_a' ) );
	$checks['fixture_owner_verified'] = $user_id && 'a' === (string) get_user_meta( $user_id, '_bds_staging_auth_fixture', true ) && ! user_can( $user_id, 'manage_options' );
	if ( ! $checks['fixture_owner_verified'] ) {
		throw new RuntimeException( 'fixture_owner_unavailable' );
	}
	$period = gmdate( 'Y-m' );
	$before = bds_staging_truncation_summary( $period );
	$checks['baseline_not_truncated'] = ! empty( $before ) && empty( $before['truncated'] );
	if ( ! $checks['baseline_not_truncated'] ) {
		throw new RuntimeException( 'baseline_unavailable' );
	}
	$checks['collision_free'] = true;
	$checks['events_owned'] = true;
	return array(
		'version' => BDS_STAGING_TRUNCATION_VERSION,
		'phase' => 'create',
		'user_id' => $user_id,
		'period' => $period,
		'fixed_gmt' => $period . '-15 12:00:00',
		'next' => 0,
		'ids' => array(),
		'before' => $before,
		'checks' => $checks,
		'errors' => array(),
		'updated_at' => gmdate( DATE_ATOM ),
	);
}

function bds_staging_truncation_run() {
	if ( ! bds_staging_truncation_is_host() || get_option( BDS_STAGING_TRUNCATION_REPORT, false ) ) {
		return;
	}
	$lock = 'bds_staging_lifecycle_truncation_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 90 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}
	$state = get_option( BDS_STAGING_TRUNCATION_STATE, array() );
	try {
		if ( ! is_array( $state ) || BDS_STAGING_TRUNCATION_VERSION !== ( $state['version'] ?? '' ) ) {
			$state = bds_staging_truncation_initial_state();
		}
		if ( 'blocked' === ( $state['phase'] ?? '' ) ) {
			return;
		}
		$user_id = absint( $state['user_id'] ?? 0 );
		$gmt = sanitize_text_field( (string) ( $state['fixed_gmt'] ?? '' ) );
		if ( ! BDS_STAGING_TRUNCATION_CONTINUE_CREATION && 'create' === ( $state['phase'] ?? '' ) ) {
			$reconciled = bds_staging_truncation_reconcile_events( $user_id, $gmt );
			if ( is_wp_error( $reconciled ) || count( $reconciled ) < absint( $state['next'] ?? 0 ) ) {
				throw new RuntimeException( is_wp_error( $reconciled ) ? $reconciled->get_error_code() : 'recovery_missing_events' );
			}
			$state['ids'] = $reconciled;
			$state['next'] = count( $reconciled );
			$state['errors'][] = 'host_execution_ceiling';
			$state['phase'] = 'cleanup';
		}
		if ( 'create' === ( $state['phase'] ?? '' ) ) {
			$limit = min( BDS_STAGING_TRUNCATION_TARGET, absint( $state['next'] ?? 0 ) + BDS_STAGING_TRUNCATION_BATCH );
			while ( absint( $state['next'] ?? 0 ) < $limit ) {
				$offset = absint( $state['next'] );
				if ( bds_staging_truncation_event_id( $user_id, $offset ) ) {
					$state['checks']['collision_free'] = false;
					throw new RuntimeException( 'event_collision' );
				}
				$event_id = bds_staging_truncation_create_event( $user_id, $offset, $gmt );
				if ( ! $event_id ) {
					throw new RuntimeException( 'event_create_failed' );
				}
				$state['ids'][] = absint( $event_id );
				$owned = bds_staging_truncation_event_owned( $event_id, $user_id, $offset, $gmt );
				$state['checks']['events_owned'] = ! empty( $state['checks']['events_owned'] ) && $owned;
				if ( ! $owned ) {
					throw new RuntimeException( 'event_ownership_failed' );
				}
				$state['next'] = $offset + 1;
			}
			if ( BDS_STAGING_TRUNCATION_TARGET === absint( $state['next'] ) ) {
				$state['checks']['target_created'] = BDS_STAGING_TRUNCATION_TARGET === count( $state['ids'] );
				$state['checks']['ids_unique'] = count( $state['ids'] ) === count( array_unique( array_map( 'absint', $state['ids'] ) ) );
				$state['phase'] = 'verify';
			}
		} elseif ( 'verify' === ( $state['phase'] ?? '' ) ) {
			$after = bds_staging_truncation_summary( $state['period'] ?? '' );
			$state['checks']['truncation_flag_true'] = ! empty( $after ) && ! empty( $after['truncated'] );
			if ( ! $state['checks']['truncation_flag_true'] ) {
				throw new RuntimeException( 'truncation_flag_missing' );
			}
			$state['phase'] = 'cleanup';
		} elseif ( 'cleanup' === ( $state['phase'] ?? '' ) ) {
			$removed = 0;
			while ( ! empty( $state['ids'] ) && $removed < BDS_STAGING_TRUNCATION_BATCH ) {
				$offset = count( $state['ids'] ) - 1;
				$event_id = absint( $state['ids'][ $offset ] );
				if ( ! bds_staging_truncation_delete_event( $event_id, $user_id, $offset, $gmt ) ) {
					throw new RuntimeException( 'cleanup_guard_failed' );
				}
				array_pop( $state['ids'] );
				$removed++;
			}
			if ( empty( $state['ids'] ) ) {
				$state['checks']['cleanup_complete'] = true;
				$restored = bds_staging_truncation_summary( $state['period'] ?? '' );
				$state['checks']['summary_restored'] = ! empty( $restored ) && ( $state['before'] ?? array() ) === $restored;
				if ( ! $state['checks']['summary_restored'] ) {
					throw new RuntimeException( 'summary_not_restored' );
				}
				delete_option( BDS_STAGING_TRUNCATION_STATE );
				$state['checks']['state_removed'] = false === get_option( BDS_STAGING_TRUNCATION_STATE, false );
				bds_staging_truncation_save_report( $state['checks'], $state['errors'] ?? array() );
				return;
			}
		}
		$state['updated_at'] = gmdate( DATE_ATOM );
		update_option( BDS_STAGING_TRUNCATION_STATE, $state, false );
	} catch ( Throwable $exception ) {
		if ( ! is_array( $state ) ) {
			$state = array( 'version' => BDS_STAGING_TRUNCATION_VERSION, 'checks' => array(), 'errors' => array(), 'ids' => array(), 'next' => 0 );
		}
		$state['phase'] = 'blocked';
		$state['errors'][] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
		$state['updated_at'] = gmdate( DATE_ATOM );
		update_option( BDS_STAGING_TRUNCATION_STATE, $state, false );
	} finally {
		delete_option( $lock );
	}
}

function bds_staging_truncation_report() {
	bds_staging_truncation_run();
	$report = get_option( BDS_STAGING_TRUNCATION_REPORT, false );
	if ( is_array( $report ) ) {
		return rest_ensure_response( $report );
	}
	$state = get_option( BDS_STAGING_TRUNCATION_STATE, array() );
	$created = is_array( $state ) ? min( BDS_STAGING_TRUNCATION_TARGET, absint( $state['next'] ?? 0 ) ) : 0;
	$remaining = is_array( $state ) ? min( BDS_STAGING_TRUNCATION_TARGET, count( (array) ( $state['ids'] ?? array() ) ) ) : 0;
	return rest_ensure_response(
		array(
			'version' => BDS_STAGING_TRUNCATION_VERSION,
			'complete' => false,
			'phase' => sanitize_key( (string) ( $state['phase'] ?? 'pending' ) ),
			'target' => BDS_STAGING_TRUNCATION_TARGET,
			'batch' => BDS_STAGING_TRUNCATION_BATCH,
			'created' => $created,
			'remaining' => $remaining,
			'checks' => bds_staging_truncation_safe_checks( is_array( $state ) ? ( $state['checks'] ?? array() ) : array() ),
			'error_codes' => bds_staging_truncation_safe_errors( is_array( $state ) ? ( $state['errors'] ?? array() ) : array() ),
		)
	);
}

function bds_staging_truncation_register_route() {
	if ( bds_staging_truncation_is_host() ) {
		register_rest_route( 'branddad/v1', '/staging/lifecycle-truncation-acceptance', array( 'methods' => 'GET', 'callback' => 'bds_staging_truncation_report', 'permission_callback' => '__return_true' ) );
	}
}
add_action( 'rest_api_init', 'bds_staging_truncation_register_route' );
