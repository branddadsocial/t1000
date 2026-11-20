<?php
/**
 * Plugin Name: BrandDad Staging Woo Refund Acceptance
 * Description: One-time, exact-host synthetic partial/full Woo refund-hook acceptance with exact cleanup.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_REFUND_ACCEPTANCE_VERSION', '0.1.0' );
define( 'BDS_STAGING_REFUND_ACCEPTANCE_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_REFUND_ACCEPTANCE_REPORT', 'bds_staging_woo_refund_report_v1' );
define( 'BDS_STAGING_REFUND_ACCEPTANCE_MARKER', 'bds-stage-woo-refund-v1' );

$GLOBALS['bds_staging_refund_acceptance_active'] = false;

/** Suppress every outbound WordPress email only while the exact synthetic run is active. */
function bds_staging_refund_acceptance_block_mail( $return ) {
	return bds_staging_refund_acceptance_pending() && ! empty( $GLOBALS['bds_staging_refund_acceptance_active'] ) ? false : $return;
}
add_filter( 'pre_wp_mail', 'bds_staging_refund_acceptance_block_mail', 1, 1 );

/** Use the configured WordPress home URL, never the caller-controlled Host header. */
function bds_staging_refund_acceptance_is_host() {
	return BDS_STAGING_REFUND_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_refund_acceptance_pending() {
	return bds_staging_refund_acceptance_is_host() && ! get_option( BDS_STAGING_REFUND_ACCEPTANCE_REPORT, false );
}

/** Persist only allowlisted boolean evidence and bounded error codes. */
function bds_staging_refund_acceptance_save_report( $complete, $checks, $errors ) {
	$allowed = array(
		'dependencies_loaded',
		'fixture_owner_verified',
		'fixture_product_verified',
		'no_preexisting_marked_order',
		'fixture_lead_snapshot_captured',
		'synthetic_paid_order_created',
		'payment_event_recorded',
		'partial_refund_created',
		'partial_refund_hook_recorded',
		'partial_refund_amount_exact',
		'partial_refund_no_gateway',
		'partial_refund_replay_idempotent',
		'remaining_refund_created',
		'remaining_refund_hook_recorded',
		'remaining_refund_amount_exact',
		'remaining_refund_no_gateway',
		'remaining_refund_replay_idempotent',
		'order_total_refunded_exact',
		'order_reaches_refunded_status',
		'aggregate_gross_delta_exact',
		'aggregate_refund_delta_exact',
		'aggregate_net_delta_zero',
		'exact_event_count',
		'fixture_lead_unchanged',
		'fixture_events_cleaned',
		'fixture_refunds_cleaned',
		'fixture_order_cleaned',
		'original_context_restored',
		'no_real_gateway_or_payment',
		'outbound_mail_suppressed',
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
		BDS_STAGING_REFUND_ACCEPTANCE_REPORT,
		array(
			'version'      => BDS_STAGING_REFUND_ACCEPTANCE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

/** Return the exact lifecycle event id for one deterministic key, or zero. */
function bds_staging_refund_acceptance_event_id( $type, $user_id, $object_type, $object_id, $idempotency ) {
	$key = bds_lifecycle_event_key( $type, $user_id, $object_type, $object_id, $idempotency );
	$ids = get_posts(
		array(
			'post_type'      => 'bds_lifecycle_evt',
			'post_status'    => 'private',
			'posts_per_page' => 2,
			'fields'         => 'ids',
			'meta_key'       => '_bds_event_key',
			'meta_value'     => $key,
		)
	);
	return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
}

/** Read current-month aggregate evidence without bypassing production calculation logic. */
function bds_staging_refund_acceptance_summary() {
	$request = new WP_REST_Request( 'GET', '/branddad/v1/admin/analytics/summary' );
	$request->set_param( 'period', gmdate( 'Y-m' ) );
	$response = bds_lifecycle_admin_summary( $request );
	if ( is_wp_error( $response ) ) {
		return array();
	}
	$data = $response instanceof WP_REST_Response ? $response->get_data() : $response;
	return is_array( $data ) ? $data : array();
}

function bds_staging_refund_acceptance_currency_minor( $summary, $key ) {
	$values = is_array( $summary[ $key ] ?? null ) ? $summary[ $key ] : array();
	return absint( $values['USD'] ?? 0 );
}

/** Snapshot only the existing fixture lead metadata so this batch can prove no fan-out. */
function bds_staging_refund_acceptance_lead_snapshot( $user_id, $email ) {
	$lead_id = function_exists( 'bds_lead_find' ) ? absint( bds_lead_find( absint( $user_id ), sanitize_email( $email ) ) ) : 0;
	if ( ! $lead_id ) {
		return array( 'lead_id' => 0 );
	}
	return array(
		'lead_id'       => $lead_id,
		'stage'         => get_post_meta( $lead_id, '_bds_lead_stage', true ),
		'version'       => get_post_meta( $lead_id, '_bds_lead_stage_version', true ),
		'history'       => get_post_meta( $lead_id, '_bds_lead_stage_history', true ),
		'source'        => get_post_meta( $lead_id, '_bds_lead_source', true ),
		'last_activity' => get_post_meta( $lead_id, '_bds_lead_last_activity', true ),
	);
}

/** Delete only one exact marked refund that belongs to the exact marked order. */
function bds_staging_refund_acceptance_delete_refund( $refund_id, $order_id ) {
	$refund = wc_get_order( absint( $refund_id ) );
	if ( ! $refund instanceof WC_Order_Refund
		|| absint( $refund->get_parent_id() ) !== absint( $order_id )
		|| BDS_STAGING_REFUND_ACCEPTANCE_MARKER !== (string) $refund->get_meta( '_bds_staging_refund_acceptance', true ) ) {
		return false;
	}
	return (bool) $refund->delete( true );
}

/** Delete only exact lifecycle events produced for the synthetic order/refunds. */
function bds_staging_refund_acceptance_delete_event( $event_id, $user_id, $type, $object_type, $object_id ) {
	if ( ! $event_id
		|| absint( get_post_meta( $event_id, '_bds_event_user', true ) ) !== absint( $user_id )
		|| sanitize_key( (string) get_post_meta( $event_id, '_bds_event_type', true ) ) !== sanitize_key( $type )
		|| sanitize_key( (string) get_post_meta( $event_id, '_bds_event_object_type', true ) ) !== sanitize_key( $object_type )
		|| absint( get_post_meta( $event_id, '_bds_event_object_id', true ) ) !== absint( $object_id ) ) {
		return false;
	}
	return (bool) wp_delete_post( $event_id, true );
}

/** Run one isolated hosted Woo refund-hook acceptance. */
function bds_staging_refund_acceptance_run() {
	if ( ! bds_staging_refund_acceptance_pending() ) {
		return;
	}
	$lock = 'bds_staging_woo_refund_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 600 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}

	$checks          = array();
	$errors          = array();
	$original_user   = get_current_user_id();
	$fixture_user_id = 0;
	$order_id        = 0;
	$refund_ids      = array();
	$event_ids       = array();
	$lead_before     = array();
	$summary_before  = array();
	$records_clean   = true;
	$events_clean    = true;

	try {
		$required = array( 'wc_create_order', 'wc_create_refund', 'bds_lifecycle_access_provisioned', 'bds_lifecycle_payment_refunded', 'bds_lifecycle_event_key', 'bds_lifecycle_admin_summary' );
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				throw new RuntimeException( 'missing_dependency' );
			}
		}

		$fixture_user_id = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$user            = $fixture_user_id ? get_userdata( $fixture_user_id ) : false;
		$checks['fixture_owner_verified'] = $user instanceof WP_User
			&& 'a' === (string) get_user_meta( $fixture_user_id, '_bds_staging_auth_fixture', true )
			&& ! user_can( $fixture_user_id, 'manage_options' )
			&& '.invalid' === substr( strtolower( (string) $user->user_email ), -8 );
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

		$existing = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				'status'     => array_keys( wc_get_order_statuses() ),
				'meta_key'   => '_bds_staging_refund_acceptance',
				'meta_value' => BDS_STAGING_REFUND_ACCEPTANCE_MARKER,
			)
		);
		$checks['no_preexisting_marked_order'] = empty( $existing );
		if ( ! $checks['no_preexisting_marked_order'] ) {
			throw new RuntimeException( 'fixture_record_collision' );
		}

		$lead_before = bds_staging_refund_acceptance_lead_snapshot( $fixture_user_id, $user->user_email );
		$checks['fixture_lead_snapshot_captured'] = is_array( $lead_before ) && array_key_exists( 'lead_id', $lead_before );
		$summary_before = bds_staging_refund_acceptance_summary();
		if ( empty( $summary_before ) || ! empty( $summary_before['truncated'] ) ) {
			throw new RuntimeException( 'aggregate_baseline_unavailable' );
		}

		wp_set_current_user( $fixture_user_id );
		$GLOBALS['bds_staging_refund_acceptance_active'] = true;
		$order = wc_create_order(
			array(
				'customer_id' => $fixture_user_id,
				'status'      => 'pending',
				'created_via' => 'bds_staging_refund_acceptance',
			)
		);
		if ( is_wp_error( $order ) || ! $order instanceof WC_Order ) {
			throw new RuntimeException( 'synthetic_order_create_failed' );
		}
		$order_id = $order->get_id();
		$order->set_billing_email( $user->user_email );
		$order->set_currency( 'USD' );
		$order->set_payment_method( 'bds_staging_synthetic_no_gateway' );
		$order->set_payment_method_title( 'Staging synthetic — no gateway or charge' );
		$order->add_product( $product, 1 );
		$order->calculate_totals( false );
		$order->set_date_paid( time() );
		$order->update_meta_data( '_bds_staging_refund_acceptance', BDS_STAGING_REFUND_ACCEPTANCE_MARKER );
		$order->set_status( 'completed' );
		$order->save();
		$checks['synthetic_paid_order_created'] = $order->is_paid()
			&& 29.99 === (float) $order->get_total()
			&& BDS_STAGING_REFUND_ACCEPTANCE_MARKER === (string) $order->get_meta( '_bds_staging_refund_acceptance', true );

		/* Call only the lifecycle listener; do not fan out through the sales-lead action. */
		bds_lifecycle_access_provisioned( $order_id, 0, array( 'slug' => 'ai-business-essentials' ) );
		$payment_event = bds_staging_refund_acceptance_event_id( 'payment_confirmed', $fixture_user_id, 'order', $order_id, 'paid-order-' . $order_id );
		$event_ids[] = array( $payment_event, 'payment_confirmed', 'order', $order_id );
		$checks['payment_event_recorded'] = $payment_event
			&& 2999 === absint( get_post_meta( $payment_event, '_bds_event_amount_minor', true ) )
			&& 'USD' === (string) get_post_meta( $payment_event, '_bds_event_currency', true );

		$partial = wc_create_refund(
			array(
				'amount'         => 5.00,
				'reason'         => BDS_STAGING_REFUND_ACCEPTANCE_MARKER . '-partial',
				'order_id'       => $order_id,
				'refund_payment' => false,
				'restock_items'  => false,
			)
		);
		if ( is_wp_error( $partial ) || ! $partial instanceof WC_Order_Refund ) {
			throw new RuntimeException( 'partial_refund_create_failed' );
		}
		$partial->update_meta_data( '_bds_staging_refund_acceptance', BDS_STAGING_REFUND_ACCEPTANCE_MARKER );
		$partial->save();
		$refund_ids[] = $partial->get_id();
		$checks['partial_refund_created'] = $order_id === absint( $partial->get_parent_id() );
		$partial_event = bds_staging_refund_acceptance_event_id( 'payment_refunded', $fixture_user_id, 'refund', $partial->get_id(), 'refund-' . $partial->get_id() );
		$event_ids[] = array( $partial_event, 'payment_refunded', 'refund', $partial->get_id() );
		$checks['partial_refund_hook_recorded'] = (bool) $partial_event;
		$checks['partial_refund_amount_exact'] = 500 === absint( get_post_meta( $partial_event, '_bds_event_amount_minor', true ) );
		$checks['partial_refund_no_gateway'] = ! $partial->get_refunded_payment();
		do_action( 'woocommerce_order_refunded', $order_id, $partial->get_id() );
		$checks['partial_refund_replay_idempotent'] = $partial_event === bds_staging_refund_acceptance_event_id( 'payment_refunded', $fixture_user_id, 'refund', $partial->get_id(), 'refund-' . $partial->get_id() );

		$remaining = wc_create_refund(
			array(
				'amount'         => 24.99,
				'reason'         => BDS_STAGING_REFUND_ACCEPTANCE_MARKER . '-remaining',
				'order_id'       => $order_id,
				'refund_payment' => false,
				'restock_items'  => false,
			)
		);
		if ( is_wp_error( $remaining ) || ! $remaining instanceof WC_Order_Refund ) {
			throw new RuntimeException( 'remaining_refund_create_failed' );
		}
		$remaining->update_meta_data( '_bds_staging_refund_acceptance', BDS_STAGING_REFUND_ACCEPTANCE_MARKER );
		$remaining->save();
		$refund_ids[] = $remaining->get_id();
		$checks['remaining_refund_created'] = $order_id === absint( $remaining->get_parent_id() );
		$remaining_event = bds_staging_refund_acceptance_event_id( 'payment_refunded', $fixture_user_id, 'refund', $remaining->get_id(), 'refund-' . $remaining->get_id() );
		$event_ids[] = array( $remaining_event, 'payment_refunded', 'refund', $remaining->get_id() );
		$checks['remaining_refund_hook_recorded'] = (bool) $remaining_event;
		$checks['remaining_refund_amount_exact'] = 2499 === absint( get_post_meta( $remaining_event, '_bds_event_amount_minor', true ) );
		$checks['remaining_refund_no_gateway'] = ! $remaining->get_refunded_payment();
		do_action( 'woocommerce_order_refunded', $order_id, $remaining->get_id() );
		$checks['remaining_refund_replay_idempotent'] = $remaining_event === bds_staging_refund_acceptance_event_id( 'payment_refunded', $fixture_user_id, 'refund', $remaining->get_id(), 'refund-' . $remaining->get_id() );

		$order = wc_get_order( $order_id );
		$checks['order_total_refunded_exact'] = 29.99 === (float) $order->get_total_refunded();
		$checks['order_reaches_refunded_status'] = $order->has_status( 'refunded' );
		$summary_after = bds_staging_refund_acceptance_summary();
		if ( empty( $summary_after ) || ! empty( $summary_after['truncated'] ) ) {
			throw new RuntimeException( 'aggregate_result_unavailable' );
		}
		$gross_delta  = bds_staging_refund_acceptance_currency_minor( $summary_after, 'confirmed_gross_minor' ) - bds_staging_refund_acceptance_currency_minor( $summary_before, 'confirmed_gross_minor' );
		$refund_delta = bds_staging_refund_acceptance_currency_minor( $summary_after, 'refunds_minor' ) - bds_staging_refund_acceptance_currency_minor( $summary_before, 'refunds_minor' );
		$net_delta    = (int) ( $summary_after['net_confirmed_minor']['USD'] ?? 0 ) - (int) ( $summary_before['net_confirmed_minor']['USD'] ?? 0 );
		$checks['aggregate_gross_delta_exact'] = 2999 === $gross_delta;
		$checks['aggregate_refund_delta_exact'] = 2999 === $refund_delta;
		$checks['aggregate_net_delta_zero'] = 0 === $net_delta;
		$checks['exact_event_count'] = 3 === count( array_filter( array_column( $event_ids, 0 ) ) );
		$checks['no_real_gateway_or_payment'] = 'bds_staging_synthetic_no_gateway' === (string) $order->get_payment_method()
			&& ! $partial->get_refunded_payment()
			&& ! $remaining->get_refunded_payment();
		$checks['outbound_mail_suppressed'] = has_filter( 'pre_wp_mail', 'bds_staging_refund_acceptance_block_mail' )
			&& ! empty( $GLOBALS['bds_staging_refund_acceptance_active'] );
	} catch ( Throwable $exception ) {
		$errors[] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
	} finally {
		$GLOBALS['bds_staging_refund_acceptance_active'] = false;
		if ( $fixture_user_id ) {
			$user = get_userdata( $fixture_user_id );
			$lead_after = $user instanceof WP_User ? bds_staging_refund_acceptance_lead_snapshot( $fixture_user_id, $user->user_email ) : array();
			$checks['fixture_lead_unchanged'] = $lead_before === $lead_after;
		}

		foreach ( array_reverse( $event_ids ) as $event ) {
			if ( ! bds_staging_refund_acceptance_delete_event( $event[0], $fixture_user_id, $event[1], $event[2], $event[3] ) ) {
				$events_clean = false;
			}
		}
		$checks['fixture_events_cleaned'] = $events_clean;

		foreach ( array_reverse( $refund_ids ) as $refund_id ) {
			if ( ! bds_staging_refund_acceptance_delete_refund( $refund_id, $order_id ) ) {
				$records_clean = false;
			}
		}
		$checks['fixture_refunds_cleaned'] = $records_clean && empty( array_filter( array_map( 'wc_get_order', $refund_ids ) ) );

		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order
				|| BDS_STAGING_REFUND_ACCEPTANCE_MARKER !== (string) $order->get_meta( '_bds_staging_refund_acceptance', true )
				|| ! $order->delete( true ) ) {
				$records_clean = false;
			}
		}
		$checks['fixture_order_cleaned'] = $records_clean && ( ! $order_id || ! wc_get_order( $order_id ) );
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === $original_user;
		delete_option( $lock );
	}

	$complete = 30 === count( $checks ) && 30 === count( array_filter( $checks ) ) && empty( $errors );
	bds_staging_refund_acceptance_save_report( $complete, $checks, $errors );
}
add_action( 'init', 'bds_staging_refund_acceptance_run', 120 );

function bds_staging_refund_acceptance_report() {
	$report = get_option( BDS_STAGING_REFUND_ACCEPTANCE_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_refund_acceptance_register_route() {
	if ( ! bds_staging_refund_acceptance_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/woo-refund-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_refund_acceptance_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_refund_acceptance_register_route' );
