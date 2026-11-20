<?php
/**
 * Plugin Name: BrandDad Customer Status API
 * Description: Customer-owned account, entitlement, usage, and evidence-based monthly value reporting.
 * Version: 0.3.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_CUSTOMER_STATUS_API_VERSION', '0.3.0' );

function bds_customer_status_permission() {
	return is_user_logged_in();
}

/** Customer-owned account status; provider ids and internal ledger keys stay private. */
function bds_customer_status_response( $user_id ) {
	$user_id = absint( $user_id );
	$plan    = function_exists( 'bds_ai_exec_active_plan' ) ? bds_ai_exec_active_plan( $user_id ) : array();
	$usage   = $plan && function_exists( 'bds_ai_exec_plan_usage' ) ? bds_ai_exec_plan_usage( $user_id, $plan ) : array(
		'allowance' => 0,
		'used'      => 0,
		'pending'   => 0,
		'remaining' => 0,
	);
	$trial   = function_exists( 'bds_ai_trial_state' ) ? bds_ai_trial_state( $user_id ) : array(
		'status' => 'unavailable',
		'active' => false,
	);
	$listing_id = function_exists( 'bds_onboarding_directory_access' ) ? bds_onboarding_directory_access( $user_id ) : 0;
	$onboarding = function_exists( 'bds_onboarding_response' ) ? bds_onboarding_response( $user_id ) : array();

	$mode = $plan ? sanitize_key( (string) ( $plan['access_mode'] ?? 'paid' ) ) : ( $listing_id ? 'directory_free' : 'none' );
	return array(
		'account' => array(
			'user_id' => $user_id,
			'email_verified' => (bool) apply_filters( 'bds_account_link_email_verified', false, $user_id ),
		),
		'access' => array(
			'mode'       => $mode,
			'plan_slug'  => $plan ? sanitize_key( (string) ( $plan['slug'] ?? '' ) ) : '',
			'listing_id' => absint( $listing_id ),
			'usage'      => $usage,
			'trial'      => $trial,
		),
		'onboarding' => array(
			'complete' => (bool) ( $onboarding['complete'] ?? false ),
			'steps'    => (array) ( $onboarding['steps'] ?? array() ),
		),
		'billing_url' => function_exists( 'wc_get_account_endpoint_url' ) ? esc_url_raw( wc_get_account_endpoint_url( 'subscriptions' ) ) : '',
	);
}

/** Accept only the current month or one of the preceding 23 UTC months. */
function bds_customer_value_period( $requested ) {
	$requested = trim( (string) $requested );
	if ( '' === $requested ) {
		return gmdate( 'Y-m' );
	}
	if ( ! preg_match( '/^(20\d{2})-(0[1-9]|1[0-2])$/', $requested ) ) {
		return new WP_Error( 'invalid_period', 'Use a YYYY-MM reporting period.', array( 'status' => 422 ) );
	}
	$current = new DateTimeImmutable( gmdate( 'Y-m-01 00:00:00' ), new DateTimeZone( 'UTC' ) );
	$target  = DateTimeImmutable::createFromFormat( '!Y-m', $requested, new DateTimeZone( 'UTC' ) );
	$oldest  = $current->modify( '-23 months' );
	if ( ! $target || $target < $oldest || $target > $current ) {
		return new WP_Error( 'period_unavailable', 'That reporting period is outside the available 24-month window.', array( 'status' => 422 ) );
	}
	return $requested;
}

/** Aggregate actual customer-owned AI job records; no traffic or revenue is inferred. */
function bds_customer_value_report( $user_id, $period ) {
	$start = $period . '-01 00:00:00';
	$end   = gmdate( 'Y-m-t 23:59:59', strtotime( $start ) );
	$ids   = get_posts(
		array(
			'post_type'      => 'bds_ai_job',
			'post_status'    => 'private',
			'author'         => absint( $user_id ),
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'date_query'     => array(
				array(
					'after'     => $start,
					'before'    => $end,
					'inclusive' => true,
					'column'    => 'post_date_gmt',
				),
			),
		)
	);
	$statuses = array( 'pending' => 0, 'running' => 0, 'awaiting_customer_approval' => 0, 'completed' => 0, 'failed' => 0, 'cancelled' => 0 );
	$tools    = array();
	$credits  = 0;
	$catalog  = function_exists( 'bds_ai_exec_tool_catalog' ) ? bds_ai_exec_tool_catalog() : array();
	foreach ( $ids as $job_id ) {
		$status = sanitize_key( (string) get_post_meta( $job_id, '_bds_status', true ) );
		$status = isset( $statuses[ $status ] ) ? $status : 'failed';
		$statuses[ $status ]++;
		if ( 'completed' !== $status ) {
			continue;
		}
		$tool = sanitize_key( (string) get_post_meta( $job_id, '_bds_tool', true ) );
		if ( isset( $catalog[ $tool ] ) ) {
			if ( ! isset( $tools[ $tool ] ) ) {
				$tools[ $tool ] = array( 'label' => sanitize_text_field( (string) $catalog[ $tool ]['label'] ), 'completed' => 0 );
			}
			$tools[ $tool ]['completed']++;
		}
		$credits += absint( get_post_meta( $job_id, '_bds_credit_cost', true ) );
	}
	$unavailable = array( 'social_posts_published', 'blogs_published', 'search_rankings', 'revenue_generated' );
	if ( ! function_exists( 'bds_inquiries_monthly_summary' ) ) {
		$unavailable[] = 'leads_captured';
	}
	return array(
		'period' => $period,
		'generated_at' => gmdate( DATE_ATOM ),
		'ai_work' => array(
			'tasks'            => $statuses,
			'completed_by_tool' => $tools,
			'credits_committed' => $credits,
			'truncated'         => count( $ids ) >= 500,
		),
		'cost_tracking' => array(
			'provider_units' => $credits,
			'provider_cost'  => 'unavailable',
			'note'           => 'Provider cost is not reported until a verified provider cost value is returned or reconciled.',
		),
		'website_monitoring' => function_exists( 'bds_monitoring_customer_summary' ) ? bds_monitoring_customer_summary( $user_id, $period ) : array( 'status' => 'unavailable' ),
		'customer_inquiries' => function_exists( 'bds_inquiries_monthly_summary' ) ? bds_inquiries_monthly_summary( $user_id, $period ) : array( 'status' => 'unavailable' ),
		'unavailable_metrics' => $unavailable,
	);
}

function bds_customer_status_get() {
	return rest_ensure_response( bds_customer_status_response( get_current_user_id() ) );
}

function bds_customer_value_get( WP_REST_Request $request ) {
	$period = bds_customer_value_period( $request->get_param( 'period' ) );
	if ( is_wp_error( $period ) ) {
		return $period;
	}
	return rest_ensure_response( bds_customer_value_report( get_current_user_id(), $period ) );
}

function bds_customer_status_register_routes() {
	register_rest_route( 'branddad/v1', '/account/status', array( 'methods' => 'GET', 'callback' => 'bds_customer_status_get', 'permission_callback' => 'bds_customer_status_permission' ) );
	register_rest_route( 'branddad/v1', '/reports/value', array( 'methods' => 'GET', 'callback' => 'bds_customer_value_get', 'permission_callback' => 'bds_customer_status_permission' ) );
}
add_action( 'rest_api_init', 'bds_customer_status_register_routes' );
