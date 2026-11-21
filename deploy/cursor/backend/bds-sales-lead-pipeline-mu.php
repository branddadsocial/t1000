<?php
/**
 * Plugin Name: BrandDad Sales Lead Pipeline
 * Description: Consent-aware, rate-limited BrandDad acquisition lead capture and verified lifecycle stages.
 * Version: 0.1.2
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_SALES_LEAD_PIPELINE_VERSION', '0.1.2' );

function bds_lead_pipeline_enabled() {
	return defined( 'BDS_LEAD_CAPTURE_ENABLED' ) && true === BDS_LEAD_CAPTURE_ENABLED;
}

function bds_lead_stages() {
	return array( 'new', 'interested', 'qualified', 'registered', 'trial_started', 'checkout_started', 'paid', 'onboarding', 'active', 'at_risk', 'cancelled', 'unsubscribed', 'anonymized' );
}

function bds_lead_register_type() {
	register_post_type(
		'bds_sales_lead',
		array(
			'label'         => 'BrandDad sales leads',
			'public'        => false,
			'show_ui'       => current_user_can( 'manage_woocommerce' ),
			'show_in_rest'  => false,
			'supports'      => array( 'author' ),
			'capability_type' => 'post',
			'map_meta_cap'  => true,
		)
	);
}
add_action( 'init', 'bds_lead_register_type' );

function bds_lead_email_hash( $email ) {
	$email = strtolower( sanitize_email( $email ) );
	return is_email( $email ) ? hash_hmac( 'sha256', $email, wp_salt( 'auth' ) ) : '';
}

function bds_lead_find_by_email( $email ) {
	$hash = bds_lead_email_hash( $email );
	if ( ! $hash ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'bds_sales_lead',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bds_lead_email_hash',
			'meta_value'     => $hash,
		)
	);
	return $ids ? absint( $ids[0] ) : 0;
}

function bds_lead_find_by_user( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'bds_sales_lead',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bds_lead_user_id',
			'meta_value'     => $user_id,
		)
	);
	return $ids ? absint( $ids[0] ) : 0;
}

function bds_lead_find( $user_id, $email = '' ) {
	return bds_lead_find_by_user( $user_id ) ?: bds_lead_find_by_email( $email );
}

/** Follow-up mail is stored only after explicit consent and a verified email. */
function bds_lead_email_followup_granted( $email_consent, $email_verified ) {
	return true === $email_consent && true === $email_verified;
}

function bds_lead_origin_allowed( WP_REST_Request $request ) {
	$origin = trim( (string) $request->get_header( 'Origin' ) );
	if ( '' === $origin ) {
		$origin = trim( (string) $request->get_header( 'Referer' ) );
	}
	$scheme = strtolower( (string) wp_parse_url( $origin, PHP_URL_SCHEME ) );
	$host   = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );
	return 'https' === $scheme && in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true );
}

function bds_lead_rate_limit() {
	$lock = 'bds_lead_rate_lock';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) < time() - 30 ) {
			delete_option( $lock );
		}
		if ( ! add_option( $lock, time(), '', false ) ) {
			return false;
		}
	}
	try {
		$ip      = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$client  = 'bds_lead_client_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
		$global  = 'bds_lead_global_' . gmdate( 'Ymd' );
		$current = absint( get_transient( $client ) );
		$total   = absint( get_transient( $global ) );
		$daily_limit = defined( 'BDS_LEAD_CAPTURE_DAILY_LIMIT' ) ? absint( BDS_LEAD_CAPTURE_DAILY_LIMIT ) : 2000;
		$daily_limit = min( 10000, max( 100, $daily_limit ) );
		if ( $current >= 5 || $total >= $daily_limit ) {
			return false;
		}
		set_transient( $client, $current + 1, 10 * MINUTE_IN_SECONDS );
		set_transient( $global, $total + 1, DAY_IN_SECONDS + HOUR_IN_SECONDS );
		return true;
	} finally {
		delete_option( $lock );
	}
}

function bds_lead_capture_permission( WP_REST_Request $request ) {
	if ( ! bds_lead_pipeline_enabled() ) {
		return new WP_Error( 'lead_capture_disabled', 'Lead capture is temporarily unavailable.', array( 'status' => 503 ) );
	}
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 8192 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	if ( ! bds_lead_origin_allowed( $request ) ) {
		return new WP_Error( 'origin_not_allowed', 'This request origin is not allowed.', array( 'status' => 403 ) );
	}
	return bds_lead_rate_limit() ? true : new WP_Error( 'rate_limited', 'Please wait before trying again.', array( 'status' => 429 ) );
}

function bds_lead_stage_rank( $stage ) {
	$ranks = array( 'new' => 1, 'interested' => 2, 'qualified' => 3, 'registered' => 4, 'trial_started' => 5, 'checkout_started' => 6, 'paid' => 7, 'onboarding' => 8, 'active' => 9 );
	return absint( $ranks[ sanitize_key( $stage ) ] ?? 0 );
}

function bds_lead_transition_allowed( $current, $next ) {
	$current = sanitize_key( $current );
	$next    = sanitize_key( $next );
	if ( ! in_array( $next, bds_lead_stages(), true ) || $current === $next || 'anonymized' === $current ) {
		return false;
	}
	if ( in_array( $next, array( 'at_risk', 'cancelled', 'unsubscribed', 'anonymized' ), true ) ) {
		return true;
	}
	if ( in_array( $current, array( 'at_risk', 'cancelled', 'unsubscribed', 'anonymized' ), true ) && in_array( $next, array( 'registered', 'trial_started', 'checkout_started', 'paid', 'onboarding', 'active' ), true ) ) {
		return true;
	}
	return bds_lead_stage_rank( $next ) > bds_lead_stage_rank( $current );
}

function bds_lead_set_stage( $lead_id, $stage, $source = 'system' ) {
	$lead_id = absint( $lead_id );
	$stage   = sanitize_key( $stage );
	$current = sanitize_key( (string) get_post_meta( $lead_id, '_bds_lead_stage', true ) );
	if ( ! $lead_id || ! in_array( $stage, bds_lead_stages(), true ) || ! bds_lead_transition_allowed( $current ?: 'new', $stage ) ) {
		return false;
	}
	$version = absint( get_post_meta( $lead_id, '_bds_lead_stage_version', true ) ) + 1;
	update_post_meta( $lead_id, '_bds_lead_stage', $stage );
	update_post_meta( $lead_id, '_bds_lead_stage_version', $version );
	update_post_meta( $lead_id, '_bds_lead_last_activity', gmdate( DATE_ATOM ) );
	update_post_meta( $lead_id, '_bds_lead_last_activity_ts', time() );
	$history   = get_post_meta( $lead_id, '_bds_lead_stage_history', true );
	$history   = is_array( $history ) ? $history : array();
	$history[] = array( 'stage' => $stage, 'source' => sanitize_key( $source ), 'at' => gmdate( DATE_ATOM ) );
	update_post_meta( $lead_id, '_bds_lead_stage_history', array_slice( $history, -20 ) );
	do_action( 'bds_sales_lead_stage_changed', $lead_id, absint( get_post_meta( $lead_id, '_bds_lead_user_id', true ) ), $stage, $version );
	return true;
}

function bds_lead_recommendation( $need ) {
	$need = strtolower( sanitize_text_field( (string) $need ) );
	if ( false !== strpos( $need, 'lead' ) || false !== strpos( $need, 'customer' ) || false !== strpos( $need, 'inquir' ) ) {
		$slug = 'ai-lead-assistant';
	} elseif ( false !== strpos( $need, 'social' ) || false !== strpos( $need, 'content' ) ) {
		$slug = 'ai-social-growth';
	} elseif ( false !== strpos( $need, 'seo' ) || false !== strpos( $need, 'google' ) || false !== strpos( $need, 'local' ) ) {
		$slug = 'ai-local-seo';
	} else {
		$slug = 'ai-business-essentials';
	}
	$catalog = function_exists( 'bds_ai_commercial_catalog' ) ? bds_ai_commercial_catalog() : array();
	$product = is_array( $catalog[ $slug ] ?? null ) ? $catalog[ $slug ] : array();
	return array(
		'slug'    => $slug,
		'name'    => sanitize_text_field( (string) ( $product['name'] ?? '' ) ),
		'summary' => sanitize_text_field( (string) ( $product['summary'] ?? '' ) ),
		'url'     => function_exists( 'bds_ai_commercial_checkout_url' ) ? esc_url_raw( (string) bds_ai_commercial_checkout_url( $slug ) ) : '',
	);
}

function bds_lead_follow_source( $source ) {
	$source = sanitize_key( $source );
	return in_array( $source, array( 'trial', 'listing', 'audit' ), true ) ? $source : 'help';
}

/** Mirror only explicit email consent into the already deployed, default-off sender queue. */
function bds_lead_mirror_email_consent( $lead_id, $email, $source ) {
	$email = strtolower( sanitize_email( $email ) );
	if ( ! is_email( $email ) ) {
		return false;
	}
	$lock = 'bds_lead_consent_lock';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) < time() - 60 ) {
			delete_option( $lock );
		}
		if ( ! add_option( $lock, time(), '', false ) ) {
			return false;
		}
	}
	try {
		$all = get_option( 'bds_follow_consent_v1', array() );
		$all = is_array( $all ) ? $all : array();
		$key = '';
		foreach ( $all as $candidate_key => $row ) {
			if ( is_array( $row ) && hash_equals( strtolower( sanitize_email( $row['email'] ?? '' ) ), $email ) ) {
				$key = (string) $candidate_key;
				break;
			}
		}
		if ( '' === $key ) {
			if ( count( $all ) >= 1000 ) {
				return false;
			}
			$key = 'lead_' . substr( bds_lead_email_hash( $email ), 0, 32 );
		}
		$current = is_array( $all[ $key ] ?? null ) ? $all[ $key ] : array();
		if ( 'unsubscribed' === ( $current['status'] ?? '' ) || 0 === absint( $current['consent'] ?? 1 ) ) {
			return false;
		}
		$all[ $key ] = array_merge(
			$current,
			array(
				'email'   => $email,
				'consent' => 1,
				'status'  => $current['status'] ?? 'interested',
				'source'  => bds_lead_follow_source( $source ),
				'saved'   => $current['saved'] ?? gmdate( DATE_ATOM ),
				'lead_id' => absint( $lead_id ),
			)
		);
		return update_option( 'bds_follow_consent_v1', $all, false ) || $all === get_option( 'bds_follow_consent_v1', array() );
	} finally {
		delete_option( $lock );
	}
}

function bds_lead_upsert( $email, $user_id, $fields, $stage, $source ) {
	$email   = strtolower( sanitize_email( $email ) );
	$user_id = absint( $user_id );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', 'Enter a valid email address.', array( 'status' => 422 ) );
	}
	$hash = bds_lead_email_hash( $email );
	$lock = 'bds_lead_' . substr( $hash, 0, 32 );
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) < time() - 60 ) {
			delete_option( $lock );
		}
		if ( ! add_option( $lock, time(), '', false ) ) {
			return new WP_Error( 'lead_busy', 'This request is already being processed.', array( 'status' => 409 ) );
		}
	}
	try {
		$lead_id = bds_lead_find( $user_id, $email );
		$created = false;
		if ( ! $lead_id ) {
			$reference = strtolower( wp_generate_password( 20, false, false ) );
			$lead_id   = wp_insert_post(
				array(
					'post_type'   => 'bds_sales_lead',
					'post_status' => 'private',
					'post_author' => $user_id,
					'post_title'  => 'BrandDad lead ' . $reference,
				),
				true
			);
			if ( is_wp_error( $lead_id ) ) {
				return new WP_Error( 'lead_save_failed', 'The request could not be saved.', array( 'status' => 503 ) );
			}
			$created = true;
			update_post_meta( $lead_id, '_bds_lead_reference', $reference );
			update_post_meta( $lead_id, '_bds_lead_email_hash', $hash );
			update_post_meta( $lead_id, '_bds_lead_stage', 'new' );
			update_post_meta( $lead_id, '_bds_lead_stage_version', 0 );
			update_post_meta( $lead_id, '_bds_lead_created_at', gmdate( DATE_ATOM ) );
			do_action( 'bds_sales_lead_created', $lead_id, $user_id );
		}
		update_post_meta( $lead_id, '_bds_lead_email', $email );
		update_post_meta( $lead_id, '_bds_lead_email_hash', $hash );
		if ( $user_id ) {
			update_post_meta( $lead_id, '_bds_lead_user_id', $user_id );
		}
		foreach ( array( 'business_type', 'primary_need' ) as $key ) {
			if ( ! empty( $fields[ $key ] ) ) {
				update_post_meta( $lead_id, '_bds_lead_' . $key, sanitize_text_field( $fields[ $key ] ) );
			}
		}
		update_post_meta( $lead_id, '_bds_lead_source', sanitize_key( $source ) );
		update_post_meta( $lead_id, '_bds_lead_last_activity', gmdate( DATE_ATOM ) );
		update_post_meta( $lead_id, '_bds_lead_last_activity_ts', time() );
		if ( ! empty( $fields['privacy_ack'] ) ) {
			update_post_meta( $lead_id, '_bds_lead_privacy_ack_at', gmdate( DATE_ATOM ) );
			update_post_meta( $lead_id, '_bds_lead_record_basis', 'privacy_notice' );
		} elseif ( ! get_post_meta( $lead_id, '_bds_lead_record_basis', true ) ) {
			update_post_meta( $lead_id, '_bds_lead_record_basis', 'customer_account_event' );
		}
		if ( ! empty( $fields['email_consent'] ) ) {
			if ( bds_lead_mirror_email_consent( $lead_id, $email, $source ) ) {
				update_post_meta( $lead_id, '_bds_lead_email_consent', 'yes' );
				update_post_meta( $lead_id, '_bds_lead_email_consent_at', gmdate( DATE_ATOM ) );
			}
		}
		if ( ! empty( $fields['email_consent_requested'] ) ) {
			update_post_meta( $lead_id, '_bds_lead_email_consent_requested', 'yes' );
			update_post_meta( $lead_id, '_bds_lead_email_consent_requested_at', gmdate( DATE_ATOM ) );
		}
		bds_lead_set_stage( $lead_id, $stage, $source );
		return array( 'lead_id' => absint( $lead_id ), 'created' => $created );
	} finally {
		delete_option( $lock );
	}
}

function bds_lead_capture( WP_REST_Request $request ) {
	$email        = strtolower( sanitize_email( $request->get_param( 'email' ) ) );
	$business     = sanitize_text_field( (string) $request->get_param( 'business_type' ) );
	$need         = sanitize_text_field( (string) $request->get_param( 'primary_need' ) );
	$source       = sanitize_key( (string) $request->get_param( 'source' ) );
	$privacy_ack  = rest_sanitize_boolean( $request->get_param( 'privacy_ack' ) );
	$email_consent = rest_sanitize_boolean( $request->get_param( 'email_consent' ) );
	if ( ! is_email( $email ) || ! $privacy_ack || strlen( $business ) > 120 || strlen( $need ) > 240 || ! in_array( $source, array( 'help', 'trial', 'listing', 'audit', 'sales_chat' ), true ) ) {
		return new WP_Error( 'invalid_lead', 'Provide a valid email, accepted privacy notice, and supported request details.', array( 'status' => 422 ) );
	}
	$user_id = get_current_user_id();
	$user    = $user_id ? get_userdata( $user_id ) : false;
	if ( $user instanceof WP_User && ! hash_equals( strtolower( sanitize_email( $user->user_email ) ), $email ) ) {
		return new WP_Error( 'email_mismatch', 'Use the email address connected to your signed-in account.', array( 'status' => 403 ) );
	}
	$email_verified = $user instanceof WP_User && true === apply_filters( 'bds_lead_email_verified', false, $user );
	$stage   = $user_id ? 'registered' : 'interested';
	$result  = bds_lead_upsert(
		$email,
		$user_id,
		array(
			'business_type'          => $business,
			'primary_need'           => $need,
			'privacy_ack'            => true,
			'email_consent'          => bds_lead_email_followup_granted( $email_consent, $email_verified ),
			'email_consent_requested'=> $email_consent,
		),
		$stage,
		$source
	);
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$lead_id = absint( $result['lead_id'] );
	$response = array(
		'status'         => sanitize_key( (string) get_post_meta( $lead_id, '_bds_lead_stage', true ) ),
		'reference'      => sanitize_text_field( (string) get_post_meta( $lead_id, '_bds_lead_reference', true ) ),
		'recommendation' => bds_lead_recommendation( $need ),
		'email_followup' => 'yes' === (string) get_post_meta( $lead_id, '_bds_lead_email_consent', true ),
		'email_confirmation_required' => $email_consent && ! $email_verified,
	);
	return new WP_REST_Response( $response, ! empty( $result['created'] ) ? 201 : 200 );
}

function bds_lead_admin_permission() {
	return current_user_can( 'manage_woocommerce' );
}

function bds_lead_admin_list( WP_REST_Request $request ) {
	$page  = min( 1000, max( 1, absint( $request->get_param( 'page' ) ?: 1 ) ) );
	$stage = sanitize_key( (string) $request->get_param( 'stage' ) );
	if ( $stage && ! in_array( $stage, bds_lead_stages(), true ) ) {
		return new WP_Error( 'invalid_stage', 'The requested lead stage is invalid.', array( 'status' => 422 ) );
	}
	$args  = array( 'post_type' => 'bds_sales_lead', 'post_status' => 'private', 'posts_per_page' => 50, 'paged' => $page, 'orderby' => 'date', 'order' => 'DESC' );
	if ( $stage ) {
		$args['meta_key']   = '_bds_lead_stage';
		$args['meta_value'] = $stage;
	}
	$query = new WP_Query( $args );
	$rows  = array();
	foreach ( $query->posts as $post ) {
		$rows[] = array(
			'id'            => $post->ID,
			'reference'     => sanitize_text_field( (string) get_post_meta( $post->ID, '_bds_lead_reference', true ) ),
			'email'         => sanitize_email( get_post_meta( $post->ID, '_bds_lead_email', true ) ),
			'user_id'       => absint( get_post_meta( $post->ID, '_bds_lead_user_id', true ) ),
			'business_type' => sanitize_text_field( (string) get_post_meta( $post->ID, '_bds_lead_business_type', true ) ),
			'primary_need'  => sanitize_text_field( (string) get_post_meta( $post->ID, '_bds_lead_primary_need', true ) ),
			'source'        => sanitize_key( (string) get_post_meta( $post->ID, '_bds_lead_source', true ) ),
			'stage'         => sanitize_key( (string) get_post_meta( $post->ID, '_bds_lead_stage', true ) ),
			'email_consent' => 'yes' === (string) get_post_meta( $post->ID, '_bds_lead_email_consent', true ),
			'updated_at'    => sanitize_text_field( (string) get_post_meta( $post->ID, '_bds_lead_last_activity', true ) ),
		);
	}
	return rest_ensure_response( array( 'items' => $rows, 'page' => $page, 'pages' => absint( $query->max_num_pages ), 'total' => absint( $query->found_posts ) ) );
}

function bds_lead_link_user( $user_id ) {
	$user = get_userdata( absint( $user_id ) );
	if ( $user instanceof WP_User ) {
		$result = bds_lead_upsert( $user->user_email, $user->ID, array(), 'registered', 'registration' );
	}
}
add_action( 'user_register', 'bds_lead_link_user', 40, 1 );

function bds_lead_from_user_stage( $user_id, $stage, $source ) {
	$user = get_userdata( absint( $user_id ) );
	if ( ! $user instanceof WP_User ) {
		return;
	}
	bds_lead_upsert( $user->user_email, $user->ID, array(), $stage, $source );
}

function bds_lead_trial_started( $user_id ) {
	bds_lead_from_user_stage( $user_id, 'trial_started', 'trial' );
}
add_action( 'bds_ai_trial_activated', 'bds_lead_trial_started', 20, 1 );

function bds_lead_checkout_started( $order_id, $posted_data, $order ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( absint( $order_id ) );
	if ( ! $order || ! function_exists( 'bds_ai_plan_from_order' ) || ! bds_ai_plan_from_order( $order ) ) {
		return;
	}
	bds_lead_upsert( $order->get_billing_email(), $order->get_user_id(), array(), 'checkout_started', 'checkout' );
}
add_action( 'woocommerce_checkout_order_processed', 'bds_lead_checkout_started', 30, 3 );

function bds_lead_paid( $order_id ) {
	$order = wc_get_order( absint( $order_id ) );
	if ( $order ) {
		bds_lead_upsert( $order->get_billing_email(), $order->get_user_id(), array(), 'paid', 'payment' );
	}
}
add_action( 'bds_ai_access_provisioned', 'bds_lead_paid', 20, 1 );

function bds_lead_onboarding_completed( $user_id ) {
	$stage = function_exists( 'bds_ai_exec_active_plan' ) && bds_ai_exec_active_plan( absint( $user_id ) ) ? 'active' : 'onboarding';
	bds_lead_from_user_stage( $user_id, $stage, 'onboarding' );
}
add_action( 'bds_onboarding_completed', 'bds_lead_onboarding_completed', 20, 1 );

function bds_lead_access_revoked( $subscription_id, $status ) {
	$user_id = function_exists( 'bds_ai_sfw_meta' ) ? absint( bds_ai_sfw_meta( $subscription_id, 'wps_customer_id' ) ) : 0;
	$stage   = in_array( sanitize_key( $status ), array( 'on-hold', 'paused' ), true ) ? 'at_risk' : 'cancelled';
	bds_lead_from_user_stage( $user_id, $stage, 'subscription' );
}
add_action( 'bds_ai_access_revoked', 'bds_lead_access_revoked', 20, 2 );

function bds_lead_retention_days() {
	$days = defined( 'BDS_LEAD_RETENTION_DAYS' ) ? absint( BDS_LEAD_RETENTION_DAYS ) : 180;
	return min( 365, max( 30, $days ) );
}

function bds_lead_retire_follow_row( $lead_id, $preserve_suppression ) {
	$lock = 'bds_lead_consent_lock';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) < time() - 60 ) {
			delete_option( $lock );
		}
		if ( ! add_option( $lock, time(), '', false ) ) {
			return false;
		}
	}
	try {
		$all = get_option( 'bds_follow_consent_v1', array() );
		$all = is_array( $all ) ? $all : array();
		$changed = false;
		foreach ( $all as $key => $row ) {
			if ( ! is_array( $row ) || absint( $row['lead_id'] ?? 0 ) !== absint( $lead_id ) ) {
				continue;
			}
			if ( $preserve_suppression ) {
				$all[ $key ] = array( 'email' => '', 'consent' => 0, 'status' => 'unsubscribed', 'lead_id' => absint( $lead_id ), 'saved' => sanitize_text_field( (string) ( $row['saved'] ?? '' ) ) );
			} else {
				unset( $all[ $key ] );
			}
			$changed = true;
			break;
		}
		return ! $changed || update_option( 'bds_follow_consent_v1', $all, false ) || $all === get_option( 'bds_follow_consent_v1', array() );
	} finally {
		delete_option( $lock );
	}
}

/** Remove contact data from stale, unconverted records while retaining aggregate stage evidence. */
function bds_lead_retention_run() {
	$candidate_ids = apply_filters( 'bds_lead_retention_candidate_ids', array() );
	$candidate_ids = is_array( $candidate_ids ) ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $candidate_ids ) ) ) ), 0, 100 ) : array();
	$args = array(
		'post_type'      => 'bds_sales_lead',
		'post_status'    => 'private',
		'posts_per_page' => 100,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => '_bds_lead_last_activity_ts', 'value' => time() - ( bds_lead_retention_days() * DAY_IN_SECONDS ), 'compare' => '<', 'type' => 'NUMERIC' ),
			array( 'key' => '_bds_lead_stage', 'value' => array( 'paid', 'onboarding', 'active', 'anonymized' ), 'compare' => 'NOT IN' ),
		),
	);
	if ( $candidate_ids ) {
		$args['post__in'] = $candidate_ids;
	}
	$ids = get_posts(
		$args
	);
	foreach ( $ids as $lead_id ) {
		$preserve_suppression = 'unsubscribed' === (string) get_post_meta( $lead_id, '_bds_lead_stage', true );
		if ( ! bds_lead_retire_follow_row( $lead_id, $preserve_suppression ) ) {
			continue;
		}
		$remove = array( '_bds_lead_email', '_bds_lead_user_id', '_bds_lead_business_type', '_bds_lead_primary_need', '_bds_lead_email_consent', '_bds_lead_email_consent_at', '_bds_lead_email_consent_requested', '_bds_lead_email_consent_requested_at' );
		if ( ! $preserve_suppression ) {
			$remove[] = '_bds_lead_email_hash';
		}
		foreach ( $remove as $key ) {
			delete_post_meta( $lead_id, $key );
		}
		bds_lead_set_stage( $lead_id, 'anonymized', 'retention' );
	}
	if ( 100 === count( $ids ) && ! wp_next_scheduled( 'bds_lead_retention_continue' ) ) {
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'bds_lead_retention_continue' );
	}
}
add_action( 'bds_lead_retention_run', 'bds_lead_retention_run' );
add_action( 'bds_lead_retention_continue', 'bds_lead_retention_run' );

function bds_lead_unsubscribed( $lead_id ) {
	$lead_id = absint( $lead_id );
	if ( $lead_id ) {
		update_post_meta( $lead_id, '_bds_lead_email_consent', 'no' );
		update_post_meta( $lead_id, '_bds_lead_unsubscribed_at', gmdate( DATE_ATOM ) );
		bds_lead_set_stage( $lead_id, 'unsubscribed', 'email' );
	}
}
add_action( 'bds_follow_unsubscribed', 'bds_lead_unsubscribed', 10, 1 );

function bds_lead_schedule_retention() {
	if ( ! wp_next_scheduled( 'bds_lead_retention_run' ) ) {
		wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'bds_lead_retention_run' );
	}
}
add_action( 'init', 'bds_lead_schedule_retention' );

function bds_lead_register_routes() {
	register_rest_route( 'branddad/v1', '/sales/leads', array( 'methods' => 'POST', 'callback' => 'bds_lead_capture', 'permission_callback' => 'bds_lead_capture_permission' ) );
	register_rest_route( 'branddad/v1', '/admin/leads', array( 'methods' => 'GET', 'callback' => 'bds_lead_admin_list', 'permission_callback' => 'bds_lead_admin_permission' ) );
}
add_action( 'rest_api_init', 'bds_lead_register_routes' );
