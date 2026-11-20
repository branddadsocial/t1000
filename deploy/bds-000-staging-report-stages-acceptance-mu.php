<?php
/**
 * Plugin Name: BrandDad Staging Report Stages Acceptance
 * Description: One-time read-only verification of evidence-stage monthly customer reporting.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_REPORT_STAGES_VERSION', '0.1.0' );
define( 'BDS_STAGING_REPORT_STAGES_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_REPORT_STAGES_OPTION', 'bds_staging_report_stages_acceptance_v1' );

function bds_staging_report_stages_is_host() {
	return BDS_STAGING_REPORT_STAGES_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_report_stages_save( $checks, $errors ) {
	$allowed = array(
		'dependency_loaded', 'fixtures_verified', 'reports_bounded', 'stage_keys_exact',
		'generated_measured', 'generated_matches_completed', 'generated_unit_exact',
		'later_stages_unavailable', 'later_stage_counts_zero', 'existing_fields_preserved',
	);
	$safe_checks = array();
	foreach ( $allowed as $key ) {
		$safe_checks[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 8 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option(
		BDS_STAGING_REPORT_STAGES_OPTION,
		array(
			'version' => BDS_STAGING_REPORT_STAGES_VERSION,
			'complete' => 10 === count( array_filter( $safe_checks ) ) && empty( $safe_errors ),
			'checks' => $safe_checks,
			'error_codes' => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_report_stages_run() {
	if ( ! bds_staging_report_stages_is_host() || get_option( BDS_STAGING_REPORT_STAGES_OPTION, false ) ) {
		return;
	}
	$lock = 'bds_staging_report_stages_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 300 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}
	$checks = array();
	$errors = array();
	try {
		$checks['dependency_loaded'] = function_exists( 'bds_customer_value_report' );
		if ( ! $checks['dependency_loaded'] ) {
			throw new RuntimeException( 'missing_dependency' );
		}
		$user_a = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$user_b = absint( username_exists( 'bds_stage_acceptance_b' ) );
		$checks['fixtures_verified'] = $user_a && $user_b
			&& 'a' === (string) get_user_meta( $user_a, '_bds_staging_auth_fixture', true )
			&& 'b' === (string) get_user_meta( $user_b, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_a, 'manage_options' ) && ! user_can( $user_b, 'manage_options' );
		if ( ! $checks['fixtures_verified'] ) {
			throw new RuntimeException( 'fixtures_unavailable' );
		}
		$reports = array( bds_customer_value_report( $user_a, gmdate( 'Y-m' ) ), bds_customer_value_report( $user_b, gmdate( 'Y-m' ) ) );
		$stages = array( 'generated', 'approved', 'submitted', 'published', 'measured' );
		$checks['reports_bounded'] = true;
		$checks['stage_keys_exact'] = true;
		$checks['generated_measured'] = true;
		$checks['generated_matches_completed'] = true;
		$checks['generated_unit_exact'] = true;
		$checks['later_stages_unavailable'] = true;
		$checks['later_stage_counts_zero'] = true;
		$checks['existing_fields_preserved'] = true;
		foreach ( $reports as $report ) {
			$checks['reports_bounded'] = $checks['reports_bounded'] && is_array( $report ) && gmdate( 'Y-m' ) === ( $report['period'] ?? '' ) && isset( $report['ai_work']['truncated'] );
			$delivery = is_array( $report['delivery_outcomes'] ?? null ) ? $report['delivery_outcomes'] : array();
			$checks['stage_keys_exact'] = $checks['stage_keys_exact'] && $stages === array_keys( $delivery );
			$checks['generated_measured'] = $checks['generated_measured'] && 'measured' === ( $delivery['generated']['status'] ?? '' );
			$checks['generated_matches_completed'] = $checks['generated_matches_completed'] && absint( $report['ai_work']['tasks']['completed'] ?? 0 ) === absint( $delivery['generated']['count'] ?? -1 );
			$checks['generated_unit_exact'] = $checks['generated_unit_exact'] && 'completed_ai_jobs' === ( $delivery['generated']['unit'] ?? '' );
			foreach ( array( 'approved', 'submitted', 'published', 'measured' ) as $stage ) {
				$checks['later_stages_unavailable'] = $checks['later_stages_unavailable'] && 'unavailable' === ( $delivery[ $stage ]['status'] ?? '' );
				$checks['later_stage_counts_zero'] = $checks['later_stage_counts_zero'] && 0 === absint( $delivery[ $stage ]['count'] ?? -1 );
			}
			$checks['existing_fields_preserved'] = $checks['existing_fields_preserved'] && isset( $report['ai_work'], $report['cost_tracking'], $report['website_monitoring'], $report['customer_inquiries'], $report['unavailable_metrics'] );
		}
	} catch ( Throwable $exception ) {
		$errors[] = $exception->getMessage();
	} finally {
		delete_option( $lock );
	}
	bds_staging_report_stages_save( $checks, $errors );
}
add_action( 'init', 'bds_staging_report_stages_run', 130 );

function bds_staging_report_stages_get() {
	$report = get_option( BDS_STAGING_REPORT_STAGES_OPTION, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_report_stages_route() {
	if ( bds_staging_report_stages_is_host() ) {
		register_rest_route( 'branddad/v1', '/staging/report-stages-acceptance', array( 'methods' => 'GET', 'callback' => 'bds_staging_report_stages_get', 'permission_callback' => '__return_true' ) );
	}
}
add_action( 'rest_api_init', 'bds_staging_report_stages_route' );
