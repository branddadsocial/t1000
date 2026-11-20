<?php
/**
 * Plugin Name: BrandDad AI Execution API
 * Description: Authenticated, metered, fail-closed execution of allowlisted BrandDad FormWise tools.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_EXECUTION_VERSION', '0.1.0' );

/**
 * Published workflows verified in the BrandDad Social FormWise workspace.
 *
 * The browser cannot select a provider id, origin, credit cost, or plan.
 */
function bds_ai_exec_tool_catalog() {
	return array(
		'social_draft' => array(
			'label'       => 'Social post draft',
			'kind'        => 'flow',
			'remote_id'   => '0cc9b6ff-309a-49f7-9a47-dd66c926208a',
			'credit_cost' => 5,
			'free_key'    => 'social_draft',
			'forced'      => array( 'what_would_you_like_to_create_' => 'Social caption' ),
			'fields'      => array(
				'provide_the_source_details_'       => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
				'who_is_the_intended_audience_'     => array( 'type' => 'string', 'required' => true, 'max' => 1000 ),
				'what_tone_should_the_copy_use_'    => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Clear and professional', 'Friendly and conversational', 'Confident and persuasive', 'Calm and empathetic' ) ),
				'what_action_should_the_reader_take_' => array( 'type' => 'string', 'required' => false, 'max' => 1000 ),
			),
		),
		'review_response' => array(
			'label'       => 'Review response draft',
			'kind'        => 'flow',
			'remote_id'   => '0cc9b6ff-309a-49f7-9a47-dd66c926208a',
			'credit_cost' => 5,
			'free_key'    => 'review_response',
			'forced'      => array( 'what_would_you_like_to_create_' => 'Customer review response' ),
			'fields'      => array(
				'provide_the_source_details_'       => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
				'who_is_the_intended_audience_'     => array( 'type' => 'string', 'required' => true, 'max' => 1000 ),
				'what_tone_should_the_copy_use_'    => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Clear and professional', 'Friendly and conversational', 'Confident and persuasive', 'Calm and empathetic' ) ),
				'what_action_should_the_reader_take_' => array( 'type' => 'string', 'required' => false, 'max' => 1000 ),
			),
		),
		'local_seo_report' => array(
			'label'       => 'Local SEO action plan',
			'kind'        => 'flow',
			'remote_id'   => '2e9921a0-5996-4bc9-a053-49fab3a7fc58',
			'credit_cost' => 5,
			'free_key'    => 'local_seo_report',
			'forced'      => array( 'which_seo_focus_should_the_plan_use_' => 'Local SEO' ),
			'fields'      => array(
				'which_page_and_goal_are_you_working_on_' => array( 'type' => 'string', 'required' => true, 'max' => 2000 ),
				'what_did_the_website_check_observe_'       => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
				'what_search_performance_data_can_you_share_' => array( 'type' => 'string', 'required' => false, 'max' => 3000 ),
			),
		),
		'website_plan' => array(
			'label'       => 'Website improvement plan',
			'kind'        => 'flow',
			'remote_id'   => '3d3deeff-cc55-43a3-b96e-4969df4296cc',
			'credit_cost' => 5,
			'fields'      => array(
				'what_kind_of_website_help_is_needed_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Repair', 'Speed', 'Conversion', 'Ongoing care' ) ),
				'what_public_page_and_goal_should_we_focus_on_' => array( 'type' => 'string', 'required' => true, 'max' => 2000 ),
				'what_did_you_observe_' => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
			),
		),
		'assistant_request' => array(
			'label'       => 'Business assistant request',
			'kind'        => 'app',
			'remote_id'   => '571f6f9c-7f7a-4b44-9e32-5d5b2fe11c6e',
			'credit_cost' => 5,
			'free_key'    => 'assistant_request',
			'fields'      => array(
				'message' => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
			),
		),
		'listing_studio' => array(
			'label'       => 'Directory listing studio',
			'kind'        => 'flow',
			'remote_id'   => '4ada4e24-ac55-4a56-be18-113107cc8741',
			'credit_cost' => 5,
			'fields'      => array(
				'what_is_the_business_name_' => array( 'type' => 'string', 'required' => true, 'max' => 500 ),
				'what_does_the_business_do_' => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
				'where_does_the_business_operate_' => array( 'type' => 'string', 'required' => true, 'max' => 2000 ),
				'what_contact_details_and_hours_are_approved_for_the_listing_' => array( 'type' => 'string', 'required' => false, 'max' => 3000 ),
				'what_categories_or_specialties_seem_relevant_' => array( 'type' => 'string', 'required' => false, 'max' => 2000 ),
				'which_output_language_do_you_want_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'English', 'Spanish', 'English and Spanish' ) ),
				'what_factual_details_must_not_be_changed_or_invented_' => array( 'type' => 'string', 'required' => false, 'max' => 3000 ),
			),
		),
		'linkedin_profile' => array(
			'label'       => 'LinkedIn profile analysis',
			'kind'        => 'flow',
			'remote_id'   => '56b1aa4f-5598-48d0-8a42-2bc6c41d791a',
			'credit_cost' => 5,
			'fields'      => array(
				'paste_the_linkedin_profile_text_you_are_authorized_to_analyze_' => array( 'type' => 'string', 'required' => true, 'max' => 6000 ),
				'who_should_this_profile_attract_' => array( 'type' => 'string', 'required' => true, 'max' => 1000 ),
				'what_is_the_primary_linkedin_goal_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Generate qualified leads', 'Build professional authority', 'Find a role', 'Grow a relevant network', 'Promote services' ) ),
				'what_tone_should_the_profile_use_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Professional and direct', 'Warm and approachable', 'Expert and authoritative', 'Bold and energetic' ) ),
			),
		),
	);
}

/** Register private job records. */
function bds_ai_exec_register_job_type() {
	register_post_type(
		'bds_ai_job',
		array(
			'label'           => 'AI jobs',
			'public'          => false,
			'show_ui'         => current_user_can( 'manage_options' ),
			'show_in_rest'    => false,
			'supports'        => array( 'author' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'bds_ai_exec_register_job_type' );

/** Require an authenticated WordPress cookie session and REST nonce. */
function bds_ai_exec_permission() {
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 65536 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	return is_user_logged_in();
}

/** Expose only customer-safe metadata. */
function bds_ai_exec_public_tools() {
	$out = array();
	foreach ( bds_ai_exec_tool_catalog() as $key => $tool ) {
		$out[ $key ] = array(
			'key'         => $key,
			'label'       => $tool['label'],
			'credit_cost' => absint( $tool['credit_cost'] ),
			'fields'      => $tool['fields'],
			'free'        => ! empty( $tool['free_key'] ),
		);
	}
	return $out;
}

/** Normalize a request against an explicit field allowlist. */
function bds_ai_exec_validate_inputs( $tool, $raw ) {
	$raw = is_array( $raw ) ? $raw : array();
	$out = array();
	foreach ( $tool['fields'] as $key => $rule ) {
		$value = $raw[ $key ] ?? null;
		if ( ! empty( $rule['required'] ) && ( null === $value || '' === trim( (string) $value ) ) ) {
			return new WP_Error( 'missing_input', 'A required field is missing.', array( 'status' => 422, 'field' => $key ) );
		}
		if ( null === $value || '' === $value ) {
			continue;
		}
		if ( 'enum' === $rule['type'] ) {
			$value = sanitize_text_field( (string) $value );
			if ( ! in_array( $value, $rule['options'], true ) ) {
				return new WP_Error( 'invalid_input', 'A field has an unsupported value.', array( 'status' => 422, 'field' => $key ) );
			}
		} else {
			$value = sanitize_textarea_field( (string) $value );
			$max   = absint( $rule['max'] ?? 4000 );
			if ( function_exists( 'mb_strlen' ) ? mb_strlen( $value ) > $max : strlen( $value ) > $max ) {
				return new WP_Error( 'input_too_long', 'A field is too long.', array( 'status' => 422, 'field' => $key ) );
			}
		}
		$out[ $key ] = $value;
	}
	foreach ( $tool['forced'] ?? array() as $key => $value ) {
		$out[ $key ] = $value;
	}
	$encoded = wp_json_encode( $out );
	if ( ! is_string( $encoded ) || strlen( $encoded ) > 32768 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	return $out;
}

/** Find one active, locally provisioned AI subscription owned by the user. */
function bds_ai_exec_active_plan( $user_id ) {
	if ( ! function_exists( 'wps_sfw_get_meta_data' ) ) {
		return array();
	}
	$args = array(
		'limit'      => 50,
		'meta_query' => array(
			array( 'key' => 'wps_customer_id', 'value' => absint( $user_id ), 'compare' => '=' ),
			array( 'key' => 'wps_subscription_status', 'value' => 'active', 'compare' => '=' ),
		),
	);
	$hpos = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	if ( $hpos && function_exists( 'wc_get_orders' ) ) {
		$args['type'] = 'wps_subscriptions';
		$rows = wc_get_orders( $args );
		$subscriptions = array_map( static fn( $row ) => $row instanceof WC_Order ? $row->get_id() : absint( $row ), is_array( $rows ) ? $rows : array() );
	} else {
		$args['post_type']      = 'wps_subscriptions';
		$args['post_status']    = 'any';
		$args['posts_per_page'] = 50;
		$args['fields']         = 'ids';
		unset( $args['limit'] );
		$subscriptions = get_posts( $args );
	}
	$catalog = function_exists( 'bds_ai_subscription_catalog' ) ? bds_ai_subscription_catalog() : array();
	foreach ( $subscriptions as $subscription_id ) {
		$plan_id = sanitize_text_field( (string) wps_sfw_get_meta_data( $subscription_id, '_bds_ai_formwise_plan_id', true ) );
		foreach ( $catalog as $slug => $plan ) {
			if ( $plan_id && hash_equals( (string) ( $plan['formwise_plan_id'] ?? '' ), $plan_id ) ) {
				return array(
					'subscription_id' => absint( $subscription_id ),
					'slug'            => sanitize_key( $slug ),
					'plan_id'         => $plan_id,
					'credits'         => absint( $plan['credits'] ?? 0 ),
					'allowance_cycle' => max( 1, absint( wps_sfw_get_meta_data( $subscription_id, '_bds_ai_allowance_cycle', true ) ) ),
				);
			}
		}
	}
	return array();
}

function bds_ai_exec_period() {
	return gmdate( 'Y-m' );
}

function bds_ai_exec_paid_ledger_key( $user_id, $plan ) {
	return 'bds_ai_paid_' . absint( $user_id ) . '_' . absint( $plan['subscription_id'] ?? 0 ) . '_' . max( 1, absint( $plan['allowance_cycle'] ?? 1 ) );
}

function bds_ai_exec_lock( $scope ) {
	$key = 'bds_ai_exec_lock_' . md5( (string) $scope );
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	if ( absint( get_option( $key, 0 ) ) < time() - 30 ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

function bds_ai_exec_unlock( $key ) {
	if ( is_string( $key ) && 0 === strpos( $key, 'bds_ai_exec_lock_' ) ) {
		delete_option( $key );
	}
}

/** Reserve paid customer credits before provider execution. */
function bds_ai_exec_paid_reserve( $user_id, $plan, $cost ) {
	$cost = max( 1, absint( $cost ) );
	$lock = bds_ai_exec_lock( 'paid|' . absint( $user_id ) );
	if ( ! $lock ) {
		return new WP_Error( 'busy', 'Another request is being processed.', array( 'status' => 409 ) );
	}
	try {
		$key     = bds_ai_exec_paid_ledger_key( $user_id, $plan );
		$ledger  = get_option( $key, array() );
		$ledger  = is_array( $ledger ) ? $ledger : array();
		$used    = absint( $ledger['used'] ?? 0 );
		$pending = isset( $ledger['pending'] ) && is_array( $ledger['pending'] ) ? $ledger['pending'] : array();
		$now     = time();
		foreach ( $pending as $token => $row ) {
			if ( absint( $row['expires_at'] ?? 0 ) <= $now ) {
				unset( $pending[ $token ] );
			}
		}
		$pending_total = array_sum( array_map( static fn( $row ) => absint( $row['cost'] ?? 0 ), $pending ) );
		if ( $used + $pending_total + $cost > absint( $plan['credits'] ) ) {
			return new WP_Error( 'allowance_exhausted', 'This plan has used its monthly AI allowance.', array( 'status' => 429 ) );
		}
		$token = wp_generate_uuid4();
		$pending[ $token ] = array( 'cost' => $cost, 'plan_id' => $plan['plan_id'], 'expires_at' => $now + 1200 );
		update_option( $key, array( 'used' => $used, 'pending' => $pending ), false );
		return $token;
	} finally {
		bds_ai_exec_unlock( $lock );
	}
}

function bds_ai_exec_paid_finish( $user_id, $plan, $token, $commit ) {
	$lock = bds_ai_exec_lock( 'paid|' . absint( $user_id ) );
	if ( ! $lock ) {
		return false;
	}
	try {
		$key    = bds_ai_exec_paid_ledger_key( $user_id, $plan );
		$ledger = get_option( $key, array() );
		if ( ! is_array( $ledger ) || ! isset( $ledger['pending'][ $token ] ) ) {
			return false;
		}
		if ( $commit ) {
			$ledger['used'] = absint( $ledger['used'] ?? 0 ) + absint( $ledger['pending'][ $token ]['cost'] ?? 0 );
		}
		unset( $ledger['pending'][ $token ] );
		update_option( $key, $ledger, false );
		return true;
	} finally {
		bds_ai_exec_unlock( $lock );
	}
}

/** Return only customer-owned job data. */
function bds_ai_exec_job_response( $job_id ) {
	$post = get_post( absint( $job_id ) );
	if ( ! $post instanceof WP_Post || 'bds_ai_job' !== $post->post_type || get_current_user_id() !== absint( $post->post_author ) ) {
		return new WP_Error( 'not_found', 'Job not found.', array( 'status' => 404 ) );
	}
	return array(
		'id'          => absint( $job_id ),
		'tool'        => sanitize_key( get_post_meta( $job_id, '_bds_tool', true ) ),
		'status'      => sanitize_key( get_post_meta( $job_id, '_bds_status', true ) ),
		'credit_cost' => absint( get_post_meta( $job_id, '_bds_credit_cost', true ) ),
		'result'      => (string) get_post_meta( $job_id, '_bds_result', true ),
		'error_code'  => sanitize_key( get_post_meta( $job_id, '_bds_error_code', true ) ),
		'created_at'  => get_post_time( DATE_ATOM, true, $post ),
		'updated_at'  => get_post_modified_time( DATE_ATOM, true, $post ),
	);
}

function bds_ai_exec_list_tools() {
	return rest_ensure_response( array( 'tools' => array_values( bds_ai_exec_public_tools() ) ) );
}

function bds_ai_exec_get_job( WP_REST_Request $request ) {
	return bds_ai_exec_job_response( absint( $request['id'] ) );
}

/** Create one idempotent job and reserve exactly one allowance source. */
function bds_ai_exec_create_job( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$tool_key = sanitize_key( (string) $request->get_param( 'tool' ) );
	$tools = bds_ai_exec_tool_catalog();
	if ( ! isset( $tools[ $tool_key ] ) ) {
		return new WP_Error( 'unknown_tool', 'This AI tool is not available.', array( 'status' => 404 ) );
	}
	$inputs = bds_ai_exec_validate_inputs( $tools[ $tool_key ], $request->get_param( 'inputs' ) );
	if ( is_wp_error( $inputs ) ) {
		return $inputs;
	}
	$idempotency = sanitize_text_field( (string) $request->get_header( 'Idempotency-Key' ) );
	if ( ! preg_match( '/^[A-Za-z0-9._:-]{16,64}$/', $idempotency ) ) {
		return new WP_Error( 'idempotency_required', 'A valid Idempotency-Key header is required.', array( 'status' => 422 ) );
	}
	$body_hash = hash( 'sha256', wp_json_encode( array( $user_id, $tool_key, $inputs ) ) );
	$idem_hash = hash_hmac( 'sha256', $idempotency . '|' . $body_hash, wp_salt( 'auth' ) );
	$idem_lock = bds_ai_exec_lock( 'idem|' . $user_id . '|' . $idem_hash );
	if ( ! $idem_lock ) {
		return new WP_Error( 'request_in_progress', 'This request is already being created.', array( 'status' => 409 ) );
	}
	try {
		$existing = get_posts(
			array(
				'post_type'      => 'bds_ai_job',
				'post_status'    => 'private',
				'author'         => $user_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_bds_idempotency',
				'meta_value'     => $idem_hash,
			)
		);
		if ( $existing ) {
			return rest_ensure_response( bds_ai_exec_job_response( $existing[0] ) );
		}

		$access_mode = sanitize_key( (string) $request->get_param( 'access_mode' ) );
		$reservation = '';
		$listing_id  = 0;
		$plan        = array();
		if ( 'directory_free' === $access_mode ) {
		$listing_id = absint( $request->get_param( 'listing_id' ) );
		if ( empty( $tools[ $tool_key ]['free_key'] ) || ! function_exists( 'bds_dir_free_ai_reserve' ) ) {
			return new WP_Error( 'free_access_unavailable', 'This tool is not available with free Directory access.', array( 'status' => 403 ) );
		}
		$reservation = bds_dir_free_ai_reserve( $listing_id, $user_id, $tools[ $tool_key ]['free_key'], 1 );
		} else {
		$plan = bds_ai_exec_active_plan( $user_id );
		if ( ! $plan ) {
			return new WP_Error( 'subscription_required', 'An active eligible subscription is required.', array( 'status' => 403 ) );
		}
		$reservation = bds_ai_exec_paid_reserve( $user_id, $plan, $tools[ $tool_key ]['credit_cost'] );
		}
		if ( is_wp_error( $reservation ) ) {
			return $reservation;
		}

		$job_id = wp_insert_post(
		array(
			'post_type'   => 'bds_ai_job',
			'post_status' => 'private',
			'post_author' => $user_id,
			'post_title'  => 'AI job ' . gmdate( 'Y-m-d H:i:s' ),
		),
		true
	);
		if ( is_wp_error( $job_id ) ) {
		if ( 'directory_free' === $access_mode ) {
			bds_dir_free_ai_release( $listing_id, $user_id, $reservation );
		} else {
			bds_ai_exec_paid_finish( $user_id, $plan, $reservation, false );
		}
			return new WP_Error( 'job_create_failed', 'The job could not be created.', array( 'status' => 500 ) );
		}
		update_post_meta( $job_id, '_bds_tool', $tool_key );
	update_post_meta( $job_id, '_bds_inputs', wp_json_encode( $inputs ) );
	update_post_meta( $job_id, '_bds_status', 'pending' );
	update_post_meta( $job_id, '_bds_credit_cost', absint( $tools[ $tool_key ]['credit_cost'] ) );
	update_post_meta( $job_id, '_bds_access_mode', 'directory_free' === $access_mode ? 'directory_free' : 'paid' );
	update_post_meta( $job_id, '_bds_listing_id', $listing_id );
	update_post_meta( $job_id, '_bds_subscription_id', absint( $plan['subscription_id'] ?? 0 ) );
	update_post_meta( $job_id, '_bds_allowance_cycle', max( 1, absint( $plan['allowance_cycle'] ?? 1 ) ) );
	update_post_meta( $job_id, '_bds_reservation', $reservation );
	update_post_meta( $job_id, '_bds_idempotency', $idem_hash );
		$scheduled = false;
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			$action_id = as_enqueue_async_action( 'bds_ai_exec_process_job', array( $job_id ), 'branddad-ai' );
			$scheduled = is_numeric( $action_id ) && absint( $action_id ) > 0;
		} else {
			$scheduled = true === wp_schedule_single_event( time() + 1, 'bds_ai_exec_process_job', array( $job_id ) );
		}
		if ( ! $scheduled ) {
			update_post_meta( $job_id, '_bds_status', 'failed' );
			update_post_meta( $job_id, '_bds_error_code', 'job_schedule_failed' );
			if ( 'directory_free' === $access_mode ) {
				bds_dir_free_ai_release( $listing_id, $user_id, $reservation );
			} else {
				bds_ai_exec_paid_finish( $user_id, $plan, $reservation, false );
			}
			return new WP_Error( 'job_schedule_failed', 'The job could not be scheduled.', array( 'status' => 503 ) );
		}
		$response = new WP_REST_Response( bds_ai_exec_job_response( $job_id ), 202 );
		$response->header( 'Location', rest_url( 'branddad/v1/ai/jobs/' . $job_id ) );
		return $response;
	} finally {
		bds_ai_exec_unlock( $idem_lock );
	}
}

/** Extract a bounded customer-facing string from documented provider response shapes. */
function bds_ai_exec_extract_output( $data ) {
	$candidates = array( 'output', 'result', 'reply', 'response', 'message', 'text' );
	foreach ( $candidates as $key ) {
		if ( isset( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
			$text = wp_strip_all_tags( $data[ $key ] );
			return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 12000 ) : substr( $text, 0, 12000 );
		}
	}
	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		return bds_ai_exec_extract_output( $data['data'] );
	}
	return '';
}

/** Execute a job once. Unknown network outcome is never retried automatically. */
function bds_ai_exec_process_job( $job_id ) {
	$job_id = absint( $job_id );
	$post   = get_post( $job_id );
	if ( ! $post instanceof WP_Post || 'bds_ai_job' !== $post->post_type || 'pending' !== get_post_meta( $job_id, '_bds_status', true ) ) {
		return;
	}
	if ( ! add_post_meta( $job_id, '_bds_processing_lock', time(), true ) ) {
		return;
	}
	$user_id     = absint( $post->post_author );
	$tool_key    = sanitize_key( get_post_meta( $job_id, '_bds_tool', true ) );
	$tools       = bds_ai_exec_tool_catalog();
	$mode        = sanitize_key( get_post_meta( $job_id, '_bds_access_mode', true ) );
	$listing_id  = absint( get_post_meta( $job_id, '_bds_listing_id', true ) );
	$subscription_id = absint( get_post_meta( $job_id, '_bds_subscription_id', true ) );
	$allowance_cycle = max( 1, absint( get_post_meta( $job_id, '_bds_allowance_cycle', true ) ) );
	$plan        = array( 'subscription_id' => $subscription_id, 'allowance_cycle' => $allowance_cycle );
	$reservation = sanitize_text_field( get_post_meta( $job_id, '_bds_reservation', true ) );
	$inputs      = json_decode( (string) get_post_meta( $job_id, '_bds_inputs', true ), true );
	$failed      = static function ( $code ) use ( $job_id, $mode, $listing_id, $user_id, $reservation, $plan ) {
		update_post_meta( $job_id, '_bds_status', 'failed' );
		update_post_meta( $job_id, '_bds_error_code', sanitize_key( $code ) );
		if ( 'directory_free' === $mode && function_exists( 'bds_dir_free_ai_release' ) ) {
			bds_dir_free_ai_release( $listing_id, $user_id, $reservation );
		} else {
			bds_ai_exec_paid_finish( $user_id, $plan, $reservation, false );
		}
	};
	if ( ! isset( $tools[ $tool_key ] ) || ! is_array( $inputs ) || ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ) || '' === trim( (string) BDS_FORMWISE_EXECUTION_KEY ) ) {
		$failed( 'execution_not_configured' );
		return;
	}
	update_post_meta( $job_id, '_bds_status', 'running' );
	$tool = $tools[ $tool_key ];
	if ( 'flow' === $tool['kind'] ) {
		$url  = 'https://builder.formwise.ai/api/v1/flows/' . rawurlencode( $tool['remote_id'] ) . '/run';
		$body = array( 'inputs' => $inputs );
	} else {
		$url  = 'https://builder.formwise.ai/api/v1/apps/' . rawurlencode( $tool['remote_id'] ) . '/conversation';
		$body = array( 'message' => (string) ( $inputs['message'] ?? '' ) );
	}
	$response = wp_safe_remote_post(
		$url,
		array(
			'timeout'             => 45,
			'redirection'         => 0,
			'limit_response_size' => 131072,
			'headers'             => array(
				'Authorization' => 'Bearer ' . trim( (string) BDS_FORMWISE_EXECUTION_KEY ),
				'Content-Type'  => 'application/json',
				'X-Request-Id'  => 'bds-job-' . $job_id,
			),
			'body'                => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) ) {
		$failed( 'provider_network_uncertain' );
		return;
	}
	$http_code = wp_remote_retrieve_response_code( $response );
	$request_id = sanitize_text_field( wp_remote_retrieve_header( $response, 'x-request-id' ) );
	if ( $request_id ) {
		update_post_meta( $job_id, '_bds_provider_request_id', substr( $request_id, 0, 128 ) );
	}
	$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$output = is_array( $data ) ? bds_ai_exec_extract_output( $data ) : '';
	if ( $http_code < 200 || $http_code >= 300 || '' === $output ) {
		$failed( 429 === $http_code ? 'provider_rate_limited' : 'provider_failed' );
		return;
	}
	update_post_meta( $job_id, '_bds_result', $output );
	update_post_meta( $job_id, '_bds_status', 'completed' );
	$committed = 'directory_free' === $mode && function_exists( 'bds_dir_free_ai_commit' )
		? bds_dir_free_ai_commit( $listing_id, $user_id, $reservation )
		: bds_ai_exec_paid_finish( $user_id, $plan, $reservation, true );
	if ( ! $committed ) {
		update_post_meta( $job_id, '_bds_status', 'completed_metering_review' );
		update_post_meta( $job_id, '_bds_error_code', 'metering_commit_failed' );
	}
}
add_action( 'bds_ai_exec_process_job', 'bds_ai_exec_process_job', 10, 1 );

function bds_ai_exec_register_routes() {
	register_rest_route( 'branddad/v1', '/ai/tools', array( 'methods' => 'GET', 'callback' => 'bds_ai_exec_list_tools', 'permission_callback' => 'bds_ai_exec_permission' ) );
	register_rest_route( 'branddad/v1', '/ai/jobs', array( 'methods' => 'POST', 'callback' => 'bds_ai_exec_create_job', 'permission_callback' => 'bds_ai_exec_permission' ) );
	register_rest_route( 'branddad/v1', '/ai/jobs/(?P<id>\\d+)', array( 'methods' => 'GET', 'callback' => 'bds_ai_exec_get_job', 'permission_callback' => 'bds_ai_exec_permission' ) );
}
add_action( 'rest_api_init', 'bds_ai_exec_register_routes' );
