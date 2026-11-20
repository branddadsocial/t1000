<?php
/**
 * Plugin Name: BrandDad Customer Onboarding API
 * Description: Customer-owned onboarding state and first-task guidance for BrandDad dashboards.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_ONBOARDING_API_VERSION', '0.1.0' );

function bds_onboarding_permission() {
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 16384 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	return is_user_logged_in();
}

function bds_onboarding_defaults() {
	return array(
		'version'    => 0,
		'profile'    => array(
			'business_name' => '',
			'website_url'   => '',
			'industry'      => '',
			'service_area'  => '',
			'primary_goal'  => '',
			'brand_voice'   => '',
		),
		'updated_at' => '',
	);
}

function bds_onboarding_state( $user_id ) {
	$stored = get_user_meta( absint( $user_id ), '_bds_onboarding_v1', true );
	$stored = is_array( $stored ) ? $stored : array();
	$state  = bds_onboarding_defaults();
	$state['version'] = absint( $stored['version'] ?? 0 );
	$state['updated_at'] = sanitize_text_field( $stored['updated_at'] ?? '' );
	$stored_profile = isset( $stored['profile'] ) && is_array( $stored['profile'] ) ? $stored['profile'] : array();
	foreach ( array_keys( $state['profile'] ) as $key ) {
		$state['profile'][ $key ] = isset( $stored_profile[ $key ] ) ? (string) $stored_profile[ $key ] : '';
	}
	return $state;
}

/** Validate a stored website reference. This function never fetches the URL. */
function bds_onboarding_public_website_url( $value ) {
	$value = esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) );
	if ( '' === $value || strlen( $value ) > 500 ) {
		return '';
	}
	$parts = wp_parse_url( $value );
	if ( ! is_array( $parts ) || ! in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), array( 'http', 'https' ), true ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return '';
	}
	$host = strtolower( rtrim( (string) $parts['host'], '.' ) );
	if ( 'localhost' === $host || '.local' === substr( $host, -6 ) || '.internal' === substr( $host, -9 ) ) {
		return '';
	}
	if ( filter_var( $host, FILTER_VALIDATE_IP ) && ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
		return '';
	}
	return $value;
}

/** Strictly allowlist fields that can be changed by the customer. */
function bds_onboarding_validate_profile( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	$strings = array(
		'business_name' => 120,
		'industry'      => 120,
		'service_area'  => 200,
	);
	foreach ( $strings as $key => $max ) {
		if ( array_key_exists( $key, $input ) ) {
			$value = sanitize_text_field( (string) $input[ $key ] );
			if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value ) ) > $max ) {
				return new WP_Error( 'invalid_profile', 'An onboarding field is too long.', array( 'status' => 422, 'field' => $key ) );
			}
			$out[ $key ] = $value;
		}
	}
	if ( array_key_exists( 'website_url', $input ) ) {
		$value = trim( (string) $input['website_url'] );
		$url   = '' === $value ? '' : bds_onboarding_public_website_url( $value );
		if ( '' !== $value && '' === $url ) {
			return new WP_Error( 'invalid_website', 'Enter a public HTTP or HTTPS website URL.', array( 'status' => 422, 'field' => 'website_url' ) );
		}
		$out['website_url'] = $url;
	}
	$enums = array(
		'primary_goal' => array( 'leads', 'social', 'local_seo', 'website', 'reviews', 'listing' ),
		'brand_voice'  => array( 'clear', 'friendly', 'confident', 'calm' ),
	);
	foreach ( $enums as $key => $allowed ) {
		if ( array_key_exists( $key, $input ) ) {
			$value = sanitize_key( (string) $input[ $key ] );
			if ( '' !== $value && ! in_array( $value, $allowed, true ) ) {
				return new WP_Error( 'invalid_profile', 'An onboarding field has an unsupported value.', array( 'status' => 422, 'field' => $key ) );
			}
			$out[ $key ] = $value;
		}
	}
	return $out;
}

function bds_onboarding_recommended_tool( $goal ) {
	$map = array(
		'leads'     => 'assistant_request',
		'social'    => 'social_draft',
		'local_seo' => 'local_seo_report',
		'website'   => 'website_plan',
		'reviews'   => 'review_response',
		'listing'   => 'listing_studio',
	);
	return (string) ( $map[ sanitize_key( $goal ) ] ?? 'assistant_request' );
}

function bds_onboarding_directory_access( $user_id ) {
	if ( ! function_exists( 'bds_dir_free_ai_listing_eligible' ) ) {
		return 0;
	}
	$listings = get_posts(
		array(
			'post_type'      => 'at_biz_dir',
			'post_status'    => 'publish',
			'author'         => absint( $user_id ),
			'posts_per_page' => 20,
			'fields'         => 'ids',
		)
	);
	foreach ( $listings as $listing_id ) {
		if ( bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) ) {
			return absint( $listing_id );
		}
	}
	return 0;
}

function bds_onboarding_first_completed_job( $user_id ) {
	$jobs = get_posts(
		array(
			'post_type'      => 'bds_ai_job',
			'post_status'    => 'private',
			'author'         => absint( $user_id ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bds_status',
			'meta_value'     => 'completed',
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	return $jobs ? absint( $jobs[0] ) : 0;
}

/** Return a customer-safe view model; no provider ids, credentials, or other users. */
function bds_onboarding_response( $user_id ) {
	$state       = bds_onboarding_state( $user_id );
	$profile     = $state['profile'];
	$paid_plan   = function_exists( 'bds_ai_exec_active_plan' ) ? bds_ai_exec_active_plan( $user_id ) : array();
	$listing_id  = bds_onboarding_directory_access( $user_id );
	$first_job   = bds_onboarding_first_completed_job( $user_id );
	$profile_ok  = '' !== (string) $profile['business_name'] && '' !== (string) $profile['industry'] && '' !== (string) $profile['primary_goal'];
	$access_mode = $paid_plan ? sanitize_key( (string) ( $paid_plan['access_mode'] ?? 'paid' ) ) : ( $listing_id ? 'directory_free' : 'none' );
	$recommended = bds_onboarding_recommended_tool( $profile['primary_goal'] );
	$free_tools  = array( 'social_draft', 'local_seo_report', 'review_response', 'assistant_request' );
	if ( 'directory_free' === $access_mode && ! in_array( $recommended, $free_tools, true ) ) {
		$recommended = 'assistant_request';
	}
	return array(
		'version'          => absint( $state['version'] ),
		'profile'          => $profile,
		'updated_at'       => sanitize_text_field( $state['updated_at'] ),
		'access'           => array(
			'mode'       => $access_mode,
			'plan_slug'  => sanitize_key( $paid_plan['slug'] ?? '' ),
			'listing_id' => $listing_id,
		),
		'steps'            => array(
			'profile'    => $profile_ok ? 'complete' : 'required',
			'access'     => 'none' === $access_mode ? 'required' : 'complete',
			'first_task' => $first_job ? 'complete' : 'required',
		),
		'first_job_id'     => $first_job,
		'recommended_tool' => $recommended,
		'complete'         => $profile_ok && 'none' !== $access_mode && 0 !== $first_job,
	);
}

function bds_onboarding_get() {
	return rest_ensure_response( bds_onboarding_response( get_current_user_id() ) );
}

function bds_onboarding_update( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$lock_key = 'bds_onboarding_lock_' . absint( $user_id );
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		if ( absint( get_option( $lock_key, 0 ) ) < time() - 30 ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return new WP_Error( 'onboarding_busy', 'Your onboarding profile is already being updated.', array( 'status' => 409 ) );
		}
	}
	try {
		$current = bds_onboarding_state( $user_id );
		$version = absint( $request->get_param( 'version' ) );
		if ( $version !== absint( $current['version'] ) ) {
			return new WP_Error( 'version_conflict', 'Your onboarding profile changed. Reload and try again.', array( 'status' => 409, 'current_version' => absint( $current['version'] ) ) );
		}
		$changes = bds_onboarding_validate_profile( $request->get_param( 'profile' ) );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		if ( ! $changes ) {
			return new WP_Error( 'empty_update', 'No supported onboarding fields were provided.', array( 'status' => 422 ) );
		}
		$current['profile'] = array_merge( $current['profile'], $changes );
		$current['version'] = absint( $current['version'] ) + 1;
		$current['updated_at'] = gmdate( DATE_ATOM );
		update_user_meta( $user_id, '_bds_onboarding_v1', $current );
		return rest_ensure_response( bds_onboarding_response( $user_id ) );
	} finally {
		delete_option( $lock_key );
	}
}

function bds_onboarding_register_routes() {
	register_rest_route( 'branddad/v1', '/onboarding', array( 'methods' => 'GET', 'callback' => 'bds_onboarding_get', 'permission_callback' => 'bds_onboarding_permission' ) );
	register_rest_route( 'branddad/v1', '/onboarding', array( 'methods' => 'POST', 'callback' => 'bds_onboarding_update', 'permission_callback' => 'bds_onboarding_permission' ) );
}
add_action( 'rest_api_init', 'bds_onboarding_register_routes' );
