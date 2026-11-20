<?php
/**
 * Plugin Name: BrandDad Staging Subscription Lifecycle Acceptance
 * Description: One-time, exact-host synthetic trial, renewal, revocation, and cleanup acceptance.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_VERSION', '0.1.0' );
define( 'BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REPORT', 'bds_staging_subscription_lifecycle_report_v1' );
define( 'BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER', 'bds-stage-subscription-lifecycle-v1' );
define( 'BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REMOTE_USER', '5f45a2b1-9057-4fa1-b3c3-f1fda826a111' );

/** Use the installed WordPress home URL rather than the caller-controlled Host header. */
function bds_staging_subscription_acceptance_is_host() {
	return BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

/** The acceptance-only provider configuration exists for one exact staging request. */
function bds_staging_subscription_acceptance_pending() {
	return bds_staging_subscription_acceptance_is_host() && ! get_option( BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REPORT, false );
}

$GLOBALS['bds_staging_subscription_acceptance_http'] = array(
	'active'     => false,
	'mode'       => 'success',
	'upserts'    => 0,
	'deletes'    => 0,
	'failures'   => 0,
	'unexpected' => 0,
);

if ( bds_staging_subscription_acceptance_pending() ) {
	if ( ! defined( 'BDS_FORMWISE_PROVISIONING_KEY' ) ) {
		define( 'BDS_FORMWISE_PROVISIONING_KEY', 'staging-acceptance-only-not-a-provider-credential' );
	}
	if ( ! defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ) ) {
		define( 'BDS_AI_ENTITLEMENTS_ENABLED', true );
	}
}

/**
 * Block real FormWise traffic while the dummy acceptance credential exists.
 * During the exact acceptance context, return deterministic provider-shaped responses.
 */
function bds_staging_subscription_acceptance_http( $preempt, $args, $url ) {
	if ( ! bds_staging_subscription_acceptance_pending() ) {
		return $preempt;
	}
	$url  = (string) $url;
	$base = 'https://builder.formwise.ai/api/v1/end-users';
	if ( 0 !== strpos( $url, $base ) ) {
		return $preempt;
	}

	$state =& $GLOBALS['bds_staging_subscription_acceptance_http'];
	if ( empty( $state['active'] ) ) {
		$state['unexpected']++;
		return new WP_Error( 'staging_provider_blocked' );
	}

	$method = strtoupper( sanitize_key( (string) ( $args['method'] ?? 'GET' ) ) );
	if ( 'POST' === $method && $base === $url ) {
		if ( 'failure' === (string) $state['mode'] ) {
			$state['failures']++;
			return new WP_Error( 'staging_provider_failure' );
		}
		$body    = json_decode( (string) ( $args['body'] ?? '' ), true );
		$email   = sanitize_email( (string) ( $body['email'] ?? '' ) );
		$plan_id = sanitize_text_field( (string) ( $body['tierPlanIds'][0] ?? '' ) );
		if ( ! is_email( $email ) || ! wp_is_uuid( $plan_id ) || empty( $args['headers']['Idempotency-Key'] ) ) {
			$state['unexpected']++;
			return new WP_Error( 'staging_provider_request_invalid' );
		}
		$state['upserts']++;
		return array(
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'endUser'      => array( 'id' => BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REMOTE_USER, 'email' => strtolower( $email ) ),
					'subscriptions' => array( array( 'tierPlanId' => $plan_id ) ),
				)
			),
			'response' => array( 'code' => 201, 'message' => 'Created' ),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	$delete_prefix = $base . '/' . rawurlencode( BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REMOTE_USER ) . '/tier-plans/';
	if ( 'DELETE' === $method && 0 === strpos( $url, $delete_prefix ) ) {
		$plan_id = rawurldecode( substr( $url, strlen( $delete_prefix ) ) );
		if ( ! wp_is_uuid( $plan_id ) ) {
			$state['unexpected']++;
			return new WP_Error( 'staging_provider_delete_invalid' );
		}
		$state['deletes']++;
		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array( 'code' => 204, 'message' => 'No Content' ),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	$state['unexpected']++;
	return new WP_Error( 'staging_provider_request_unexpected' );
}
add_filter( 'pre_http_request', 'bds_staging_subscription_acceptance_http', 1, 3 );

/** Persist only allowlisted boolean evidence and bounded error codes. */
function bds_staging_subscription_acceptance_save_report( $complete, $checks, $errors ) {
	$allowed = array(
		'dependencies_loaded',
		'fixture_owner_verified',
		'fixture_product_verified',
		'no_preexisting_marked_records',
		'synthetic_parent_created',
		'subscription_created',
		'trial_terms_authoritative',
		'trial_history_recorded',
		'trial_state_active',
		'trial_plan_bounded',
		'trial_parent_provision_denied',
		'trial_provider_not_called',
		'paid_renewal_matches',
		'first_paid_grant',
		'first_allowance_cycle',
		'paid_plan_resolves',
		'grant_replay_idempotent',
		'second_renewal_grant',
		'second_allowance_cycle_once',
		'failed_latest_order_no_fallback',
		'failed_renewal_revokes',
		'revocation_generation_advanced',
		'duplicate_revocation_idempotent',
		'same_order_reactivation',
		'reactivation_no_credit_reset',
		'cancellation_revokes',
		'cancellation_preserves_trial_history',
		'provider_failure_fails_closed',
		'provider_failure_retry_bounded',
		'provider_requests_exact_hosts',
		'fixture_events_cleaned',
		'fixture_records_cleaned',
		'trial_history_restored',
		'scheduled_retry_cleaned',
		'original_context_restored',
		'no_real_gateway',
	);
	$safe_checks = array();
	foreach ( $allowed as $key ) {
		$safe_checks[ $key ] = true === ( $checks[ $key ] ?? false );
	}
	$safe_errors = array();
	foreach ( array_slice( (array) $errors, 0, 12 ) as $error ) {
		$safe_errors[] = substr( sanitize_key( (string) $error ), 0, 80 );
	}
	update_option(
		BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REPORT,
		array(
			'version'      => BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

/** Create a marked synthetic Woo order without invoking checkout or a gateway. */
function bds_staging_subscription_acceptance_order( $user_id, $product, $kind, $status = 'completed' ) {
	$order = wc_create_order(
		array(
			'customer_id' => absint( $user_id ),
			'status'      => sanitize_key( $status ),
			'created_via' => 'bds_staging_acceptance',
		)
	);
	if ( is_wp_error( $order ) || ! $order instanceof WC_Order ) {
		return new WP_Error( 'synthetic_order_create_failed' );
	}
	$user = get_userdata( absint( $user_id ) );
	$order->set_billing_email( $user ? $user->user_email : '' );
	$order->set_currency( 'USD' );
	$order->set_payment_method( 'bds_staging_synthetic_no_gateway' );
	$order->set_payment_method_title( 'Staging synthetic — no gateway or charge' );
	$order->add_product( $product, 1 );
	$order->calculate_totals( false );
	if ( in_array( sanitize_key( $status ), array( 'processing', 'completed' ), true ) ) {
		$order->set_date_paid( time() );
	}
	$order->update_meta_data( '_bds_staging_subscription_acceptance', BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER );
	$order->update_meta_data( '_bds_staging_subscription_kind', sanitize_key( $kind ) );
	$order->save();
	return $order;
}

/** Attach the authoritative WP Swings relationship to a synthetic renewal. */
function bds_staging_subscription_acceptance_mark_renewal( $order_id, $subscription_id ) {
	wps_sfw_update_meta_data( absint( $order_id ), 'wps_sfw_renewal_order', 'yes' );
	wps_sfw_update_meta_data( absint( $order_id ), 'wps_sfw_subscription', absint( $subscription_id ) );
}

/** Remove only scheduled retries for exact fixture arguments. */
function bds_staging_subscription_acceptance_unschedule( $hook, $args ) {
	$guard = 0;
	while ( $guard < 5 && ( $timestamp = wp_next_scheduled( $hook, $args ) ) ) {
		wp_unschedule_event( $timestamp, $hook, $args );
		$guard++;
	}
	return false === wp_next_scheduled( $hook, $args );
}

/** Query lifecycle evidence belonging only to the non-customer fixture user. */
function bds_staging_subscription_acceptance_event_ids( $user_id ) {
	$ids = array_values(
		array_filter(
			array_map(
				'absint',
				get_posts(
					array(
						'post_type'      => 'bds_lifecycle_evt',
						'post_status'    => 'private',
						'posts_per_page' => 100,
						'fields'         => 'ids',
						'meta_key'       => '_bds_event_user',
						'meta_value'     => absint( $user_id ),
					)
				)
			)
		)
	);
	sort( $ids, SORT_NUMERIC );
	return $ids;
}

/** Run one isolated hosted lifecycle acceptance. */
function bds_staging_subscription_acceptance_run() {
	if ( ! bds_staging_subscription_acceptance_pending() ) {
		return;
	}
	$lock = 'bds_staging_subscription_lifecycle_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 600 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}

	$checks              = array();
	$errors              = array();
	$created_order_ids   = array();
	$subscription_id     = 0;
	$original_user       = get_current_user_id();
	$history_existed     = false;
	$history_before      = null;
	$baseline_events     = array();
	$fixture_user_id     = 0;
	$scheduled_clean     = true;
	$records_clean       = true;
	$events_clean        = true;
	$http_state         =& $GLOBALS['bds_staging_subscription_acceptance_http'];

	try {
		$required = array(
			'bds_ai_subscription_readiness',
			'bds_ai_provision_active_subscription',
			'bds_ai_sfw_cancel_access',
			'bds_ai_sfw_renewal_order_status_changed',
			'bds_ai_reconcile_one_subscription',
			'bds_ai_subscription_current_paid_order',
			'bds_ai_trial_record_activation',
			'bds_ai_trial_state',
			'bds_ai_trial_plan',
			'bds_ai_exec_active_paid_plan',
			'wps_sfw_get_meta_data',
			'wps_sfw_update_meta_data',
			'wc_create_order',
		);
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				throw new RuntimeException( 'missing_dependency' );
			}
		}

		$fixture_user_id = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$checks['fixture_owner_verified'] = $fixture_user_id
			&& 'a' === (string) get_user_meta( $fixture_user_id, '_bds_staging_auth_fixture', true )
			&& ! user_can( $fixture_user_id, 'manage_options' );
		if ( ! $checks['fixture_owner_verified'] ) {
			throw new RuntimeException( 'fixture_owner_unavailable' );
		}

		$product_post = get_page_by_path( 'ai-business-essentials', OBJECT, 'product' );
		$product      = $product_post instanceof WP_Post ? wc_get_product( $product_post->ID ) : false;
		$checks['fixture_product_verified'] = $product instanceof WC_Product
			&& 'yes' === (string) get_post_meta( $product->get_id(), '_bds_staging_fixture', true )
			&& '29.99' === (string) $product->get_regular_price()
			&& $product->is_virtual()
			&& $product->is_sold_individually();
		if ( ! $checks['fixture_product_verified'] ) {
			throw new RuntimeException( 'fixture_product_unavailable' );
		}

		$existing_orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				'status'     => array_keys( wc_get_order_statuses() ),
				'meta_key'   => '_bds_staging_subscription_acceptance',
				'meta_value' => BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER,
			)
		);
		$existing_subscriptions = get_posts(
			array(
				'post_type'      => 'wps_subscriptions',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_bds_staging_subscription_acceptance',
				'meta_value'     => BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER,
			)
		);
		$checks['no_preexisting_marked_records'] = empty( $existing_orders ) && empty( $existing_subscriptions );
		if ( ! $checks['no_preexisting_marked_records'] ) {
			throw new RuntimeException( 'fixture_record_collision' );
		}

		$history_existed = metadata_exists( 'user', $fixture_user_id, '_bds_ai_trial_history_v1' );
		$history_before  = get_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1', true );
		delete_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1' );
		$baseline_events = bds_staging_subscription_acceptance_event_ids( $fixture_user_id );
		wp_set_current_user( $fixture_user_id );

		$parent_order = bds_staging_subscription_acceptance_order( $fixture_user_id, $product, 'trial_parent', 'completed' );
		if ( is_wp_error( $parent_order ) ) {
			throw new RuntimeException( 'parent_order_create_failed' );
		}
		$created_order_ids[] = $parent_order->get_id();
		$checks['synthetic_parent_created'] = $parent_order->is_paid()
			&& BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER === (string) $parent_order->get_meta( '_bds_staging_subscription_acceptance', true )
			&& 'bds_staging_synthetic_no_gateway' === (string) $parent_order->get_payment_method();

		$subscription_id = wp_insert_post(
			array(
				'post_type'   => 'wps_subscriptions',
				'post_status' => 'publish',
				'post_title'  => 'BrandDad staging synthetic subscription acceptance',
				'post_author' => $fixture_user_id,
			),
			true
		);
		if ( is_wp_error( $subscription_id ) || ! $subscription_id ) {
			throw new RuntimeException( 'subscription_create_failed' );
		}
		$subscription_id = absint( $subscription_id );
		$start_time      = current_time( 'timestamp' ) - MINUTE_IN_SECONDS;
		$trial_end       = $start_time + ( 7 * DAY_IN_SECONDS );
		$subscription_meta = array(
			'_bds_staging_subscription_acceptance'   => BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER,
			'wps_customer_id'                         => $fixture_user_id,
			'wps_parent_order'                        => $parent_order->get_id(),
			'wps_subscription_status'                 => 'active',
			'wps_schedule_start'                      => $start_time,
			'wps_susbcription_trial_end'              => $trial_end,
			'wps_sfw_subscription_free_trial_number'  => 7,
			'wps_sfw_subscription_free_trial_interval'=> 'day',
			'_bds_ai_access_generation'               => 0,
			'_bds_ai_allowance_cycle'                 => 0,
		);
		foreach ( $subscription_meta as $key => $value ) {
			wps_sfw_update_meta_data( $subscription_id, $key, $value );
		}
		$checks['subscription_created'] = BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER === (string) bds_ai_sfw_meta( $subscription_id, '_bds_staging_subscription_acceptance' )
			&& $fixture_user_id === absint( bds_ai_sfw_meta( $subscription_id, 'wps_customer_id' ) );
		$checks['trial_terms_authoritative'] = bds_ai_trial_terms_match( $subscription_id )
			&& $trial_end === absint( bds_ai_sfw_meta( $subscription_id, 'wps_susbcription_trial_end' ) );

		bds_ai_trial_record_activation( $parent_order->get_id(), $subscription_id );
		$trial_history = get_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1', true );
		$checks['trial_history_recorded'] = is_array( $trial_history ) && $subscription_id === absint( $trial_history['subscription_id'] ?? 0 );
		$trial_state = bds_ai_trial_state( $fixture_user_id );
		$trial_plan  = bds_ai_trial_plan( $fixture_user_id );
		$checks['trial_state_active'] = 'active' === (string) ( $trial_state['status'] ?? '' ) && true === ( $trial_state['active'] ?? false ) && 7 === absint( $trial_state['trial_days'] ?? 0 );
		$checks['trial_plan_bounded'] = 'trial' === (string) ( $trial_plan['access_mode'] ?? '' ) && 25 === absint( $trial_plan['credits'] ?? 0 ) && $subscription_id === absint( $trial_plan['subscription_id'] ?? 0 );

		$http_state['active'] = true;
		$upserts_before_trial = absint( $http_state['upserts'] );
		$checks['trial_parent_provision_denied'] = false === bds_ai_provision_active_subscription( $parent_order->get_id(), $subscription_id );
		$checks['trial_provider_not_called'] = $upserts_before_trial === absint( $http_state['upserts'] );

		wps_sfw_update_meta_data( $subscription_id, 'wps_susbcription_trial_end', current_time( 'timestamp' ) - MINUTE_IN_SECONDS );
		$renewal_one = bds_staging_subscription_acceptance_order( $fixture_user_id, $product, 'renewal_one', 'completed' );
		if ( is_wp_error( $renewal_one ) ) {
			throw new RuntimeException( 'renewal_one_create_failed' );
		}
		$created_order_ids[] = $renewal_one->get_id();
		bds_staging_subscription_acceptance_mark_renewal( $renewal_one->get_id(), $subscription_id );
		wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $renewal_one->get_id() );
		$checks['paid_renewal_matches'] = bds_ai_order_matches_subscription( $renewal_one->get_id(), $subscription_id );
		$checks['first_paid_grant'] = true === bds_ai_provision_active_subscription( $renewal_one->get_id(), $subscription_id )
			&& BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REMOTE_USER === (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_formwise_user_id' );
		$checks['first_allowance_cycle'] = 1 === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_cycle' ) )
			&& $renewal_one->get_id() === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_order_id' ) );
		$paid_plan = bds_ai_exec_active_paid_plan( $fixture_user_id );
		$checks['paid_plan_resolves'] = $subscription_id === absint( $paid_plan['subscription_id'] ?? 0 )
			&& 'paid' === (string) ( $paid_plan['access_mode'] ?? '' );
		$upserts_after_first = absint( $http_state['upserts'] );
		$checks['grant_replay_idempotent'] = true === bds_ai_provision_active_subscription( $renewal_one->get_id(), $subscription_id )
			&& $upserts_after_first === absint( $http_state['upserts'] )
			&& 1 === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_cycle' ) );

		$renewal_two = bds_staging_subscription_acceptance_order( $fixture_user_id, $product, 'renewal_two', 'completed' );
		if ( is_wp_error( $renewal_two ) ) {
			throw new RuntimeException( 'renewal_two_create_failed' );
		}
		$created_order_ids[] = $renewal_two->get_id();
		bds_staging_subscription_acceptance_mark_renewal( $renewal_two->get_id(), $subscription_id );
		wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $renewal_two->get_id() );
		$checks['second_renewal_grant'] = true === bds_ai_provision_active_subscription( $renewal_two->get_id(), $subscription_id );
		$checks['second_allowance_cycle_once'] = 2 === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_cycle' ) )
			&& $renewal_two->get_id() === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_order_id' ) );

		$failed_renewal = bds_staging_subscription_acceptance_order( $fixture_user_id, $product, 'failed_renewal', 'failed' );
		if ( is_wp_error( $failed_renewal ) ) {
			throw new RuntimeException( 'failed_renewal_create_failed' );
		}
		$created_order_ids[] = $failed_renewal->get_id();
		bds_staging_subscription_acceptance_mark_renewal( $failed_renewal->get_id(), $subscription_id );
		wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $failed_renewal->get_id() );
		$checks['failed_latest_order_no_fallback'] = 0 === bds_ai_subscription_current_paid_order( $subscription_id );
		wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', 'on-hold' );
		$deletes_before_failure = absint( $http_state['deletes'] );
		bds_ai_sfw_renewal_order_status_changed( $failed_renewal->get_id(), 'pending', 'failed', $failed_renewal );
		$checks['failed_renewal_revokes'] = '' !== (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_removed' )
			&& $deletes_before_failure + 1 === absint( $http_state['deletes'] );
		$checks['revocation_generation_advanced'] = 1 === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_generation' ) )
			&& '' === (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_last_event_key' );
		$delete_after_failure = absint( $http_state['deletes'] );
		bds_ai_sfw_cancel_access( $subscription_id );
		$checks['duplicate_revocation_idempotent'] = $delete_after_failure === absint( $http_state['deletes'] );

		wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', 'active' );
		wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $renewal_two->get_id() );
		$cycle_before_reactivation = absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_cycle' ) );
		bds_ai_reconcile_one_subscription( $subscription_id );
		$checks['same_order_reactivation'] = '' === (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_removed' )
			&& BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REMOTE_USER === (string) bds_ai_sfw_meta( $subscription_id, '_bds_ai_formwise_user_id' );
		$checks['reactivation_no_credit_reset'] = $cycle_before_reactivation === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_cycle' ) )
			&& $renewal_two->get_id() === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_allowance_order_id' ) );

		wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', 'cancelled' );
		$deletes_before_cancel = absint( $http_state['deletes'] );
		bds_ai_sfw_cancel_access( $subscription_id );
		$checks['cancellation_revokes'] = $deletes_before_cancel + 1 === absint( $http_state['deletes'] )
			&& 2 === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_access_generation' ) );
		$history_after_cancel = get_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1', true );
		$checks['cancellation_preserves_trial_history'] = is_array( $history_after_cancel )
			&& $subscription_id === absint( $history_after_cancel['subscription_id'] ?? 0 );

		$failure_order = bds_staging_subscription_acceptance_order( $fixture_user_id, $product, 'provider_failure', 'completed' );
		if ( is_wp_error( $failure_order ) ) {
			throw new RuntimeException( 'failure_order_create_failed' );
		}
		$created_order_ids[] = $failure_order->get_id();
		bds_staging_subscription_acceptance_mark_renewal( $failure_order->get_id(), $subscription_id );
		wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', 'active' );
		wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $failure_order->get_id() );
		$http_state['mode'] = 'failure';
		$checks['provider_failure_fails_closed'] = false === bds_ai_provision_active_subscription( $failure_order->get_id(), $subscription_id )
			&& 1 === absint( bds_ai_sfw_meta( $subscription_id, '_bds_ai_provision_attempts' ) );
		$retry_args = array( $failure_order->get_id(), $subscription_id );
		$checks['provider_failure_retry_bounded'] = false !== wp_next_scheduled( 'bds_ai_retry_provision', $retry_args )
			&& 1 === absint( $http_state['failures'] );
		$checks['no_real_gateway'] = 'bds_staging_synthetic_no_gateway' === (string) $parent_order->get_payment_method()
			&& 'bds_staging_synthetic_no_gateway' === (string) $renewal_one->get_payment_method()
			&& 'bds_staging_synthetic_no_gateway' === (string) $renewal_two->get_payment_method()
			&& 'bds_staging_synthetic_no_gateway' === (string) $failed_renewal->get_payment_method()
			&& 'bds_staging_synthetic_no_gateway' === (string) $failure_order->get_payment_method();
	} catch ( Throwable $exception ) {
		$errors[] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
	} finally {
		$http_state['active'] = false;
		$http_state['mode']   = 'success';

		if ( $subscription_id ) {
			foreach ( $created_order_ids as $order_id ) {
				$scheduled_clean = bds_staging_subscription_acceptance_unschedule( 'bds_ai_retry_provision', array( absint( $order_id ), $subscription_id ) ) && $scheduled_clean;
			}
			$scheduled_clean = bds_staging_subscription_acceptance_unschedule( 'bds_ai_retry_cancel', array( $subscription_id ) ) && $scheduled_clean;
		}

		if ( $fixture_user_id ) {
			if ( $history_existed ) {
				update_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1', $history_before );
			} else {
				delete_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1' );
			}
		}

		if ( $subscription_id ) {
			if ( BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER !== (string) get_post_meta( $subscription_id, '_bds_staging_subscription_acceptance', true ) || ! wp_delete_post( $subscription_id, true ) ) {
				$records_clean = false;
			}
		}
		foreach ( array_reverse( $created_order_ids ) as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order || BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_MARKER !== (string) $order->get_meta( '_bds_staging_subscription_acceptance', true ) || ! $order->delete( true ) ) {
				$records_clean = false;
			}
		}

		if ( $subscription_id ) {
			foreach ( $created_order_ids as $order_id ) {
				$scheduled_clean = bds_staging_subscription_acceptance_unschedule( 'bds_ai_retry_provision', array( absint( $order_id ), $subscription_id ) ) && $scheduled_clean;
			}
			$scheduled_clean = bds_staging_subscription_acceptance_unschedule( 'bds_ai_retry_cancel', array( $subscription_id ) ) && $scheduled_clean;
		}
		if ( $fixture_user_id ) {
			$new_events = array_diff( bds_staging_subscription_acceptance_event_ids( $fixture_user_id ), $baseline_events );
			foreach ( $new_events as $event_id ) {
				if ( $fixture_user_id !== absint( get_post_meta( $event_id, '_bds_event_user', true ) ) || ! wp_delete_post( $event_id, true ) ) {
					$events_clean = false;
				}
			}
		}

		wp_set_current_user( $original_user );
		$checks['provider_requests_exact_hosts'] = 0 === absint( $http_state['unexpected'] );
		$checks['fixture_events_cleaned']    = $events_clean && ( ! $fixture_user_id || bds_staging_subscription_acceptance_event_ids( $fixture_user_id ) === $baseline_events );
		$checks['fixture_records_cleaned']   = $records_clean && ( ! $subscription_id || ! get_post( $subscription_id ) );
		$checks['trial_history_restored']    = ! $fixture_user_id || ( $history_existed ? get_user_meta( $fixture_user_id, '_bds_ai_trial_history_v1', true ) === $history_before : ! metadata_exists( 'user', $fixture_user_id, '_bds_ai_trial_history_v1' ) );
		$checks['scheduled_retry_cleaned']   = $scheduled_clean;
		$checks['original_context_restored'] = get_current_user_id() === $original_user;
		delete_option( $lock );
	}

	$complete = 36 === count( $checks ) && 36 === count( array_filter( $checks ) ) && empty( $errors );
	bds_staging_subscription_acceptance_save_report( $complete, $checks, $errors );
}
add_action( 'init', 'bds_staging_subscription_acceptance_run', 120 );

function bds_staging_subscription_acceptance_report() {
	$report = get_option( BDS_STAGING_SUBSCRIPTION_ACCEPTANCE_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_subscription_acceptance_register_route() {
	if ( ! bds_staging_subscription_acceptance_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/subscription-lifecycle-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_subscription_acceptance_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_subscription_acceptance_register_route' );

