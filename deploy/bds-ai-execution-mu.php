<?php
/**
 * Plugin Name: BrandDad AI Execution API
 * Description: Authenticated, metered, fail-closed execution of allowlisted BrandDad FormWise tools.
 * Version: 0.3.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_EXECUTION_VERSION', '0.3.0' );

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
			'trial'       => true,
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
			'trial'       => true,
			'forced'      => array( 'what_would_you_like_to_create_' => 'Customer review response' ),
			'fields'      => array(
				'provide_the_source_details_'       => array( 'type' => 'string', 'required' => true, 'max' => 4000 ),
				'who_is_the_intended_audience_'     => array( 'type' => 'string', 'required' => true, 'max' => 1000 ),
				'what_tone_should_the_copy_use_'    => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Clear and professional', 'Friendly and conversational', 'Confident and persuasive', 'Calm and empathetic' ) ),
				'what_action_should_the_reader_take_' => array( 'type' => 'string', 'required' => false, 'max' => 1000 ),
			),
		),
		'airbnb_review_appeal' => array(
			'label'       => 'Airbnb review appeal — one case',
			'kind'        => 'flow',
			'remote_id'   => '86c87c94-2054-4641-87e1-2a70a0a9b1d4',
			'credit_cost' => 1,
			'one_time_product_slug' => 'branddad-ai-review-dispute-assistant',
			'forced'      => array( 'which_review_platform_' => 'Airbnb' ),
			'fields'      => array(
				'what_help_do_you_need_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Prepare a first removal request', 'Prepare an appeal after a rejection', 'Prepare a public response', 'Help me decide' ) ),
				'what_have_you_already_done_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Nothing yet', 'Reported it and waiting', 'Removal request was rejected', 'Appeal was already submitted' ) ),
				'what_does_the_review_say_' => array( 'type' => 'string', 'required' => true, 'max' => 3000 ),
				'what_facts_and_evidence_can_you_verify_' => array( 'type' => 'string', 'required' => true, 'max' => 3000 ),
				'are_you_authorized_' => array( 'type' => 'enum', 'required' => true, 'options' => array( 'Yes, I am authorized' ) ),
			),
		),
		'local_seo_report' => array(
			'label'       => 'Local SEO action plan',
			'kind'        => 'flow',
			'remote_id'   => '2e9921a0-5996-4bc9-a053-49fab3a7fc58',
			'credit_cost' => 5,
			'free_key'    => 'local_seo_report',
			'trial'       => true,
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
			'trial'       => true,
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
			'trial'       => true,
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
			'trial'       => ! empty( $tool['trial'] ),
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
function bds_ai_exec_active_paid_plan( $user_id ) {
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

/** Prefer a paid entitlement; otherwise accept one bounded active trial. */
function bds_ai_exec_active_plan( $user_id ) {
	$paid = bds_ai_exec_active_paid_plan( $user_id );
	if ( $paid ) {
		$paid['access_mode'] = 'paid';
		return $paid;
	}
	return function_exists( 'bds_ai_trial_plan' ) ? bds_ai_trial_plan( $user_id ) : array();
}

function bds_ai_exec_period() {
	return gmdate( 'Y-m' );
}

function bds_ai_exec_paid_ledger_key( $user_id, $plan ) {
	$suffix = 'trial' === ( $plan['access_mode'] ?? '' ) ? '_trial' : '';
	return 'bds_ai_paid_' . absint( $user_id ) . '_' . absint( $plan['subscription_id'] ?? 0 ) . '_' . max( 1, absint( $plan['allowance_cycle'] ?? 1 ) ) . $suffix;
}

/** Read a customer-safe allowance summary without exposing reservation tokens. */
function bds_ai_exec_plan_usage( $user_id, $plan ) {
	$allowance = absint( $plan['credits'] ?? 0 );
	$key       = bds_ai_exec_paid_ledger_key( $user_id, $plan );
	$ledger    = get_option( $key, array() );
	$ledger    = is_array( $ledger ) ? $ledger : array();
	$used      = absint( $ledger['used'] ?? 0 );
	$pending   = 0;
	$now       = time();
	foreach ( (array) ( $ledger['pending'] ?? array() ) as $row ) {
		if ( absint( $row['expires_at'] ?? 0 ) > $now ) {
			$pending += absint( $row['cost'] ?? 0 );
		}
	}
	return array(
		'allowance' => $allowance,
		'used'      => min( $allowance, $used ),
		'pending'   => min( $allowance, $pending ),
		'remaining' => max( 0, $allowance - $used - $pending ),
	);
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

/** Find an unused, paid one-case WooCommerce line owned by this customer. */
function bds_ai_exec_case_entitlement( $user_id, $product_slug ) {
	if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_product' ) ) {
		return new WP_Error( 'case_checkout_unavailable', 'Case access is not configured yet.', array( 'status' => 503 ) );
	}
	$orders = wc_get_orders(
		array(
			'customer_id' => absint( $user_id ),
			'status'      => array( 'wc-processing', 'wc-completed' ),
			'limit'       => 100,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'return'      => 'objects',
		)
	);
	foreach ( is_array( $orders ) ? $orders : array() as $order ) {
		if ( ! $order instanceof WC_Order || absint( $order->get_customer_id() ) !== absint( $user_id ) ) {
			continue;
		}
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			$product       = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;
			$recorded_slug = $item instanceof WC_Order_Item_Product ? sanitize_title( (string) $item->get_meta( '_bds_ai_case_product_slug', true ) ) : '';
			$current_slug  = $product instanceof WC_Product ? sanitize_title( $product->get_slug() ) : '';
			if ( sanitize_title( $product_slug ) !== $recorded_slug && sanitize_title( $product_slug ) !== $current_slug ) {
				continue;
			}
			$remaining_quantity = max( 0, absint( $item->get_quantity() ) + (int) $order->get_qty_refunded_for_item( $item_id ) );
			if ( $remaining_quantity < 1 ) {
				continue;
			}
			$key    = 'bds_ai_case_' . absint( $order->get_id() ) . '_' . absint( $item_id );
			$ledger = get_option( $key, array() );
			$ledger = is_array( $ledger ) ? $ledger : array();
			$pending_expires = absint( $ledger['pending_expires'] ?? 0 );
			$pending_job_id  = absint( $ledger['pending_job_id'] ?? 0 );
			$pending_status  = $pending_job_id ? sanitize_key( get_post_meta( $pending_job_id, '_bds_status', true ) ) : '';
			$active_job      = $pending_job_id && in_array( $pending_status, array( 'pending', 'running', 'completed', 'completed_metering_review' ), true );
			if ( empty( $ledger['used'] ) && ! $active_job && ( ! $pending_expires || $pending_expires <= time() || 'failed' === $pending_status ) ) {
				return array( 'order_id' => absint( $order->get_id() ), 'item_id' => absint( $item_id ), 'key' => $key );
			}
		}
	}
	return new WP_Error( 'case_purchase_required', 'Buy one Airbnb review case, or finish the case you already started.', array( 'status' => 403 ) );
}

/** Reserve one purchased case before scheduling provider work. */
function bds_ai_exec_case_reserve( $user_id, $entitlement ) {
	$key  = sanitize_key( (string) ( $entitlement['key'] ?? '' ) );
	$lock = bds_ai_exec_lock( 'case|' . $key );
	if ( ! $key || ! $lock ) {
		return new WP_Error( 'busy', 'Another case request is being processed.', array( 'status' => 409 ) );
	}
	try {
		$ledger = get_option( $key, array() );
		$ledger = is_array( $ledger ) ? $ledger : array();
		if ( ! empty( $ledger['used'] ) || absint( $ledger['pending_expires'] ?? 0 ) > time() ) {
			return new WP_Error( 'case_already_used', 'This purchase already has a case in progress or completed.', array( 'status' => 409 ) );
		}
		$token = wp_generate_uuid4();
		update_option(
			$key,
			array(
				'used'            => false,
				'pending_token'   => $token,
				'pending_expires' => time() + 1200,
				'pending_job_id'  => 0,
				'user_id'         => absint( $user_id ),
			),
			false
		);
		return $token;
	} finally {
		bds_ai_exec_unlock( $lock );
	}
}

/** Bind the reservation to the private job so queue delay cannot reopen the case. */
function bds_ai_exec_case_attach_job( $user_id, $order_id, $item_id, $token, $job_id ) {
	$key  = 'bds_ai_case_' . absint( $order_id ) . '_' . absint( $item_id );
	$lock = bds_ai_exec_lock( 'case|' . $key );
	if ( ! $lock ) {
		return false;
	}
	try {
		$ledger = get_option( $key, array() );
		if ( ! is_array( $ledger ) || absint( $ledger['user_id'] ?? 0 ) !== absint( $user_id ) || ! hash_equals( (string) ( $ledger['pending_token'] ?? '' ), (string) $token ) ) {
			return false;
		}
		$ledger['pending_job_id']  = absint( $job_id );
		$ledger['pending_expires'] = time() + DAY_IN_SECONDS;
		update_option( $key, $ledger, false );
		return true;
	} finally {
		bds_ai_exec_unlock( $lock );
	}
}

/** Revalidate the exact order line immediately before spending provider credits. */
function bds_ai_exec_case_order_is_paid( $user_id, $order_id, $item_id, $product_slug ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return false;
	}
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order instanceof WC_Order || absint( $order->get_customer_id() ) !== absint( $user_id ) || ! $order->has_status( array( 'processing', 'completed' ) ) ) {
		return false;
	}
	$item = $order->get_item( absint( $item_id ) );
	if ( ! $item instanceof WC_Order_Item_Product ) {
		return false;
	}
	$product       = $item->get_product();
	$recorded_slug = sanitize_title( (string) $item->get_meta( '_bds_ai_case_product_slug', true ) );
	$current_slug  = $product instanceof WC_Product ? sanitize_title( $product->get_slug() ) : '';
	$remaining_quantity = max( 0, absint( $item->get_quantity() ) + (int) $order->get_qty_refunded_for_item( $item_id ) );
	return ( sanitize_title( $product_slug ) === $recorded_slug || sanitize_title( $product_slug ) === $current_slug )
		&& $remaining_quantity > 0;
}

/** Persist immutable one-case product identity on new order lines. */
function bds_ai_exec_case_capture_order_item( $item, $cart_item_key, $values, $order ) {
	$product = isset( $values['data'] ) && $values['data'] instanceof WC_Product ? $values['data'] : null;
	if ( $item instanceof WC_Order_Item_Product && $product instanceof WC_Product && 'branddad-ai-review-dispute-assistant' === sanitize_title( $product->get_slug() ) ) {
		$item->add_meta_data( '_bds_ai_case_product_slug', 'branddad-ai-review-dispute-assistant', true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'bds_ai_exec_case_capture_order_item', 10, 4 );

/** The one-case product requires an account so the private result has an owner. */
function bds_ai_exec_case_requires_checkout_account( $required ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $required;
	}
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
		if ( $product instanceof WC_Product && 'branddad-ai-review-dispute-assistant' === sanitize_title( $product->get_slug() ) ) {
			return true;
		}
	}
	return $required;
}
add_filter( 'woocommerce_checkout_registration_required', 'bds_ai_exec_case_requires_checkout_account', 20 );
add_filter(
	'woocommerce_is_sold_individually',
	static function ( $sold_individually, $product ) {
		return $product instanceof WC_Product && 'branddad-ai-review-dispute-assistant' === sanitize_title( $product->get_slug() ) ? true : $sold_individually;
	},
	20,
	2
);

/** Commit or release a purchased case reservation. */
function bds_ai_exec_case_finish( $user_id, $order_id, $item_id, $token, $commit ) {
	$key  = 'bds_ai_case_' . absint( $order_id ) . '_' . absint( $item_id );
	$lock = bds_ai_exec_lock( 'case|' . $key );
	if ( ! $lock ) {
		return false;
	}
	try {
		$ledger = get_option( $key, array() );
		if ( ! is_array( $ledger ) || absint( $ledger['user_id'] ?? 0 ) !== absint( $user_id ) || ! hash_equals( (string) ( $ledger['pending_token'] ?? '' ), (string) $token ) ) {
			return false;
		}
		$ledger['used']            = (bool) $commit;
		$ledger['used_at']         = $commit ? time() : 0;
		$ledger['pending_token']   = '';
		$ledger['pending_expires'] = 0;
		$ledger['pending_job_id']  = 0;
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
		$order_id    = 0;
		$order_item_id = 0;
		$plan        = array();
		// Paid/trial access has deterministic precedence. A customer cannot spend
		// free Directory allowances while a paid or trial allowance is active.
		if ( ! empty( $tools[ $tool_key ]['one_time_product_slug'] ) ) {
			$entitlement = bds_ai_exec_case_entitlement( $user_id, $tools[ $tool_key ]['one_time_product_slug'] );
			if ( is_wp_error( $entitlement ) ) {
				return $entitlement;
			}
			$reservation = bds_ai_exec_case_reserve( $user_id, $entitlement );
			$order_id = absint( $entitlement['order_id'] );
			$order_item_id = absint( $entitlement['item_id'] );
			$access_mode = 'one_time_case';
		} elseif ( $plan = bds_ai_exec_active_plan( $user_id ) ) {
			$access_mode = sanitize_key( (string) ( $plan['access_mode'] ?? 'paid' ) );
			if ( 'trial' === ( $plan['access_mode'] ?? '' ) && empty( $tools[ $tool_key ]['trial'] ) ) {
				return new WP_Error( 'trial_tool_unavailable', 'This tool is not included in the trial.', array( 'status' => 403 ) );
			}
			$reservation = bds_ai_exec_paid_reserve( $user_id, $plan, $tools[ $tool_key ]['credit_cost'] );
		} elseif ( 'directory_free' === $access_mode ) {
		$listing_id = absint( $request->get_param( 'listing_id' ) );
		if ( empty( $tools[ $tool_key ]['free_key'] ) || ! function_exists( 'bds_dir_free_ai_reserve' ) ) {
			return new WP_Error( 'free_access_unavailable', 'This tool is not available with free Directory access.', array( 'status' => 403 ) );
		}
		$reservation = bds_dir_free_ai_reserve( $listing_id, $user_id, $tools[ $tool_key ]['free_key'], 1 );
		} else {
			return new WP_Error( 'subscription_required', 'An active eligible subscription is required.', array( 'status' => 403 ) );
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
		if ( 'one_time_case' === $access_mode ) {
			bds_ai_exec_case_finish( $user_id, $order_id, $order_item_id, $reservation, false );
		} elseif ( 'directory_free' === $access_mode ) {
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
	$resolved_mode = in_array( $access_mode, array( 'directory_free', 'one_time_case' ), true ) ? $access_mode : sanitize_key( (string) ( $plan['access_mode'] ?? 'paid' ) );
	update_post_meta( $job_id, '_bds_access_mode', $resolved_mode );
	update_post_meta( $job_id, '_bds_listing_id', $listing_id );
	update_post_meta( $job_id, '_bds_subscription_id', absint( $plan['subscription_id'] ?? 0 ) );
	update_post_meta( $job_id, '_bds_allowance_cycle', max( 1, absint( $plan['allowance_cycle'] ?? 1 ) ) );
	update_post_meta( $job_id, '_bds_reservation', $reservation );
	update_post_meta( $job_id, '_bds_order_id', $order_id );
	update_post_meta( $job_id, '_bds_order_item_id', $order_item_id );
	update_post_meta( $job_id, '_bds_idempotency', $idem_hash );
		if ( 'one_time_case' === $access_mode && ! bds_ai_exec_case_attach_job( $user_id, $order_id, $order_item_id, $reservation, $job_id ) ) {
			update_post_meta( $job_id, '_bds_status', 'failed' );
			update_post_meta( $job_id, '_bds_error_code', 'case_reservation_attach_failed' );
			bds_ai_exec_case_finish( $user_id, $order_id, $order_item_id, $reservation, false );
			return new WP_Error( 'case_reservation_attach_failed', 'The case could not be reserved safely.', array( 'status' => 503 ) );
		}
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
			if ( 'one_time_case' === $access_mode ) {
				bds_ai_exec_case_finish( $user_id, $order_id, $order_item_id, $reservation, false );
			} elseif ( 'directory_free' === $access_mode ) {
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
	$order_id     = absint( get_post_meta( $job_id, '_bds_order_id', true ) );
	$order_item_id = absint( get_post_meta( $job_id, '_bds_order_item_id', true ) );
	$allowance_cycle = max( 1, absint( get_post_meta( $job_id, '_bds_allowance_cycle', true ) ) );
	$plan        = array( 'subscription_id' => $subscription_id, 'allowance_cycle' => $allowance_cycle );
	$plan['access_mode'] = $mode;
	$reservation = sanitize_text_field( get_post_meta( $job_id, '_bds_reservation', true ) );
	$inputs      = json_decode( (string) get_post_meta( $job_id, '_bds_inputs', true ), true );
	$failed      = static function ( $code ) use ( $job_id, $mode, $listing_id, $user_id, $order_id, $order_item_id, $reservation, $plan ) {
		update_post_meta( $job_id, '_bds_status', 'failed' );
		update_post_meta( $job_id, '_bds_error_code', sanitize_key( $code ) );
		if ( 'one_time_case' === $mode ) {
			bds_ai_exec_case_finish( $user_id, $order_id, $order_item_id, $reservation, false );
		} elseif ( 'directory_free' === $mode && function_exists( 'bds_dir_free_ai_release' ) ) {
			bds_dir_free_ai_release( $listing_id, $user_id, $reservation );
		} else {
			bds_ai_exec_paid_finish( $user_id, $plan, $reservation, false );
		}
	};
	if ( ! isset( $tools[ $tool_key ] ) || ! is_array( $inputs ) || ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ) || '' === trim( (string) BDS_FORMWISE_EXECUTION_KEY ) ) {
		$failed( 'execution_not_configured' );
		return;
	}
	if ( 'one_time_case' === $mode && ! bds_ai_exec_case_order_is_paid( $user_id, $order_id, $order_item_id, (string) ( $tools[ $tool_key ]['one_time_product_slug'] ?? '' ) ) ) {
		$failed( 'case_payment_inactive' );
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
	$committed = 'one_time_case' === $mode
		? bds_ai_exec_case_finish( $user_id, $order_id, $order_item_id, $reservation, true )
		: ( 'directory_free' === $mode && function_exists( 'bds_dir_free_ai_commit' )
			? bds_dir_free_ai_commit( $listing_id, $user_id, $reservation )
			: bds_ai_exec_paid_finish( $user_id, $plan, $reservation, true ) );
	if ( ! $committed ) {
		update_post_meta( $job_id, '_bds_status', 'completed_metering_review' );
		update_post_meta( $job_id, '_bds_error_code', 'metering_commit_failed' );
	} else {
		do_action( 'bds_ai_job_completed', $job_id, $user_id, $tool_key, $mode );
	}
}
add_action( 'bds_ai_exec_process_job', 'bds_ai_exec_process_job', 10, 1 );

function bds_ai_exec_register_routes() {
	register_rest_route( 'branddad/v1', '/ai/tools', array( 'methods' => 'GET', 'callback' => 'bds_ai_exec_list_tools', 'permission_callback' => 'bds_ai_exec_permission' ) );
	register_rest_route( 'branddad/v1', '/ai/jobs', array( 'methods' => 'POST', 'callback' => 'bds_ai_exec_create_job', 'permission_callback' => 'bds_ai_exec_permission' ) );
	register_rest_route( 'branddad/v1', '/ai/jobs/(?P<id>\\d+)', array( 'methods' => 'GET', 'callback' => 'bds_ai_exec_get_job', 'permission_callback' => 'bds_ai_exec_permission' ) );
}
add_action( 'rest_api_init', 'bds_ai_exec_register_routes' );
