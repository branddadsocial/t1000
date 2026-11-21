<?php
/**
 * Plugin Name: BrandDad Staging Airbnb Case Acceptance
 * Description: One-time, exact-host acceptance for the one-purchase/one-Airbnb-case delivery path.
 * Version: 0.2.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_STAGING_AIRBNB_ACCEPTANCE_VERSION', '0.2.0' );
define( 'BDS_STAGING_AIRBNB_ACCEPTANCE_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER', 'bds-staging-airbnb-case-v2' );
define( 'BDS_STAGING_AIRBNB_ACCEPTANCE_REPORT', 'bds_staging_airbnb_case_report_v2' );
define( 'BDS_STAGING_AIRBNB_ACCEPTANCE_KEY', 'staging-acceptance-only-not-a-provider-credential' );

$GLOBALS['bds_staging_airbnb_acceptance_active'] = false;
$GLOBALS['bds_staging_airbnb_acceptance_force_failure'] = false;

function bds_staging_airbnb_acceptance_is_host() {
	return BDS_STAGING_AIRBNB_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_airbnb_acceptance_pending() {
	return bds_staging_airbnb_acceptance_is_host() && ! get_option( BDS_STAGING_AIRBNB_ACCEPTANCE_REPORT, false );
}

/**
 * Let production execution code enter its HTTP lane without copying a real
 * provider credential into staging. Every matching request is intercepted
 * below before the network stack can send it.
 */
if ( bds_staging_airbnb_acceptance_pending() && ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ) ) {
	define( 'BDS_FORMWISE_EXECUTION_KEY', BDS_STAGING_AIRBNB_ACCEPTANCE_KEY );
}

/** Suppress mail only while marked synthetic fixtures are being exercised. */
function bds_staging_airbnb_acceptance_block_mail( $return ) {
	return bds_staging_airbnb_acceptance_pending() && ! empty( $GLOBALS['bds_staging_airbnb_acceptance_active'] ) ? false : $return;
}
add_filter( 'pre_wp_mail', 'bds_staging_airbnb_acceptance_block_mail', 1, 1 );

/** Simulate one exact FormWise request; unrelated HTTP is never intercepted. */
function bds_staging_airbnb_acceptance_force_provider_failure( $pre, $args, $url ) {
	$expected = 'https://builder.formwise.ai/api/v1/flows/86c87c94-2054-4641-87e1-2a70a0a9b1d4/run';
	if ( ! bds_staging_airbnb_acceptance_pending()
		|| empty( $GLOBALS['bds_staging_airbnb_acceptance_active'] )
		|| $expected !== (string) $url ) {
		return $pre;
	}
	if ( ! empty( $GLOBALS['bds_staging_airbnb_acceptance_force_failure'] ) ) {
		return new WP_Error( 'bds_staging_forced_provider_failure', 'Synthetic staging provider failure.' );
	}
	$headers = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
	$auth    = isset( $headers['Authorization'] ) ? (string) $headers['Authorization'] : '';
	$body    = json_decode( (string) ( $args['body'] ?? '' ), true );
	if ( ! hash_equals( 'Bearer ' . BDS_STAGING_AIRBNB_ACCEPTANCE_KEY, $auth )
		|| ! is_array( $body )
		|| ! isset( $body['inputs'] )
		|| ! is_array( $body['inputs'] ) ) {
		return new WP_Error( 'bds_staging_provider_contract_mismatch', 'Synthetic staging provider contract mismatch.' );
	}
	return array(
		'headers'  => array( 'x-request-id' => 'bds-staging-airbnb-acceptance-v2' ),
		'body'     => wp_json_encode(
			array(
				'output' => 'Synthetic acceptance result: prepare a factual Airbnb review dispute using only verified evidence. Airbnb makes the final decision.',
			)
		),
		'response' => array( 'code' => 200, 'message' => 'OK' ),
		'cookies'  => array(),
		'filename' => null,
	);
}
add_filter( 'pre_http_request', 'bds_staging_airbnb_acceptance_force_provider_failure', 5, 3 );

function bds_staging_airbnb_acceptance_checks_template() {
	return array_fill_keys(
		array(
			'exact_staging_host',
			'dependencies_loaded',
			'fixture_owner_verified',
			'no_preexisting_marked_records',
			'product_ready',
			'provider_transport_fixture_configured',
			'provider_network_intercepted',
			'synthetic_paid_order_created',
			'immutable_product_slug_recorded',
			'entitlement_found',
			'job_created',
			'job_owner_scoped',
			'provider_result_completed',
			'result_bounded_nonempty',
			'case_committed',
			'duplicate_case_denied',
			'refunded_order_created',
			'refund_blocks_case',
			'failure_order_created',
			'forced_failure_recorded',
			'failed_case_released',
			'no_real_gateway_or_payment',
			'outbound_mail_suppressed',
			'fixture_records_cleaned',
			'fixture_product_cleaned_or_preserved',
			'original_context_restored',
		),
		false
	);
}

function bds_staging_airbnb_acceptance_save_report( $checks, $errors ) {
	$checks = array_intersect_key( array_map( 'boolval', $checks ), bds_staging_airbnb_acceptance_checks_template() );
	$errors = array_values(
		array_filter(
			array_map(
				static function ( $error ) {
					return substr( sanitize_key( (string) $error ), 0, 96 );
				},
				(array) $errors
			)
		)
	);
	$complete = ! in_array( false, $checks, true ) && empty( $errors );
	update_option(
		BDS_STAGING_AIRBNB_ACCEPTANCE_REPORT,
		array(
			'version'      => BDS_STAGING_AIRBNB_ACCEPTANCE_VERSION,
			'complete'     => $complete,
			'checks'       => $checks,
			'error_codes'  => array_slice( array_unique( $errors ), 0, 20 ),
			'completed_at' => current_time( DATE_ATOM ),
		),
		false
	);
}

/** Find or create only the isolated staging product required for the synthetic order. */
function bds_staging_airbnb_acceptance_product( &$created ) {
	$created = false;
	$post = get_page_by_path( 'branddad-ai-review-dispute-assistant', OBJECT, 'product' );
	if ( $post instanceof WP_Post ) {
		$product = wc_get_product( $post->ID );
		return $product instanceof WC_Product ? $product : null;
	}
	$product = new WC_Product_Simple();
	$product->set_name( 'Airbnb Review Appeal Assistant — Staging Acceptance' );
	$product->set_slug( 'branddad-ai-review-dispute-assistant' );
	$product->set_status( 'private' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_virtual( true );
	$product->set_sold_individually( true );
	$product->set_regular_price( '9.99' );
	$product->set_price( '9.99' );
	$product_id = $product->save();
	if ( ! $product_id ) {
		return null;
	}
	update_post_meta( $product_id, '_bds_staging_airbnb_acceptance', BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER );
	$created = true;
	return wc_get_product( $product_id );
}

/** Create a completed synthetic order without invoking a payment gateway. */
function bds_staging_airbnb_acceptance_order( $user_id, $product, $kind ) {
	$order = wc_create_order(
		array(
			'customer_id' => absint( $user_id ),
			'created_via' => 'bds_staging_airbnb_acceptance',
		)
	);
	if ( ! $order instanceof WC_Order ) {
		return null;
	}
	$order->set_payment_method( 'bds_staging_synthetic_no_gateway' );
	$order->set_payment_method_title( 'Staging synthetic — no gateway' );
	$item_id = $order->add_product( $product, 1 );
	if ( ! $item_id ) {
		$order->delete( true );
		return null;
	}
	$item = $order->get_item( $item_id );
	if ( $item instanceof WC_Order_Item_Product ) {
		$item->add_meta_data( '_bds_ai_case_product_slug', 'branddad-ai-review-dispute-assistant', true );
		$item->add_meta_data( '_bds_staging_airbnb_acceptance', BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER, true );
		$item->save();
	}
	$order->calculate_totals( false );
	$order->update_meta_data( '_bds_staging_airbnb_acceptance', BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER );
	$order->update_meta_data( '_bds_staging_airbnb_kind', sanitize_key( $kind ) );
	$order->set_status( 'completed' );
	$order->set_date_paid( time() );
	$order->save();
	return array( 'order' => $order, 'item_id' => absint( $item_id ) );
}

function bds_staging_airbnb_acceptance_request( $suffix ) {
	$request = new WP_REST_Request( 'POST', '/branddad/v1/ai/jobs' );
	$request->set_header( 'Idempotency-Key', 'bds-stage-airbnb-' . sanitize_key( $suffix ) . '-20261120' );
	$request->set_param( 'tool', 'airbnb_review_appeal' );
	$request->set_param(
		'inputs',
		array(
			'what_help_do_you_need_'                 => 'Prepare a first removal request',
			'what_have_you_already_done_'             => 'Nothing yet',
			'what_does_the_review_say_'               => 'Synthetic test review: the guest claimed an amenity was unavailable.',
			'what_facts_and_evidence_can_you_verify_' => 'Synthetic test facts: the listing description and dated maintenance log show the amenity was available. No real guest or reservation data is included.',
			'are_you_authorized_'                     => 'Yes, I am authorized',
		)
	);
	return $request;
}

function bds_staging_airbnb_acceptance_job_id( $response ) {
	if ( $response instanceof WP_REST_Response ) {
		$data = $response->get_data();
		return absint( is_array( $data ) ? ( $data['id'] ?? 0 ) : 0 );
	}
	return 0;
}

function bds_staging_airbnb_acceptance_unschedule_job( $job_id ) {
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'bds_ai_exec_process_job', array( absint( $job_id ) ), 'branddad-ai' );
	}
	wp_clear_scheduled_hook( 'bds_ai_exec_process_job', array( absint( $job_id ) ) );
}

function bds_staging_airbnb_acceptance_delete_order( $order_id ) {
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order instanceof WC_Order || BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER !== (string) $order->get_meta( '_bds_staging_airbnb_acceptance', true ) ) {
		return false;
	}
	$order->delete( true );
	return ! wc_get_order( absint( $order_id ) );
}

/** Run the isolated acceptance once, then persist a boolean-only report. */
function bds_staging_airbnb_acceptance_run() {
	if ( ! bds_staging_airbnb_acceptance_pending() ) {
		return;
	}
	$lock = 'bds_staging_airbnb_case_lock_v1';
	if ( ! add_option( $lock, BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER, '', false ) ) {
		return;
	}
	$checks          = bds_staging_airbnb_acceptance_checks_template();
	$errors          = array();
	$order_ids       = array();
	$order_item_ids  = array();
	$refund_ids      = array();
	$job_ids         = array();
	$product_id      = 0;
	$product_created = false;
	$original_user   = get_current_user_id();
	try {
		$checks['exact_staging_host'] = bds_staging_airbnb_acceptance_is_host();
		$checks['dependencies_loaded'] = class_exists( 'WooCommerce' )
			&& function_exists( 'bds_ai_exec_create_job' )
			&& function_exists( 'bds_ai_exec_process_job' )
			&& function_exists( 'bds_ai_exec_case_entitlement' );
		if ( ! $checks['exact_staging_host'] || ! $checks['dependencies_loaded'] ) {
			throw new RuntimeException( 'dependencies_unavailable' );
		}

		$user_id = absint( username_exists( 'bds_stage_acceptance_a' ) );
		$user    = $user_id ? get_userdata( $user_id ) : false;
		$checks['fixture_owner_verified'] = $user instanceof WP_User
			&& 'a' === (string) get_user_meta( $user_id, '_bds_staging_auth_fixture', true )
			&& ! user_can( $user_id, 'manage_options' );
		if ( ! $checks['fixture_owner_verified'] ) {
			throw new RuntimeException( 'fixture_owner_unavailable' );
		}

		$existing_orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'ids',
				'status'     => array_keys( wc_get_order_statuses() ),
				'meta_key'   => '_bds_staging_airbnb_acceptance',
				'meta_value' => BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER,
			)
		);
		$existing_jobs = get_posts(
			array(
				'post_type'      => 'bds_ai_job',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_bds_staging_airbnb_acceptance',
				'meta_value'     => BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER,
			)
		);
		$checks['no_preexisting_marked_records'] = empty( $existing_orders ) && empty( $existing_jobs );
		if ( ! $checks['no_preexisting_marked_records'] ) {
			throw new RuntimeException( 'fixture_record_collision' );
		}

		$product = bds_staging_airbnb_acceptance_product( $product_created );
		$product_id = $product instanceof WC_Product ? $product->get_id() : 0;
		$checks['product_ready'] = $product instanceof WC_Product
			&& 'branddad-ai-review-dispute-assistant' === sanitize_title( $product->get_slug() );
		$checks['provider_transport_fixture_configured'] = defined( 'BDS_FORMWISE_EXECUTION_KEY' )
			&& hash_equals( BDS_STAGING_AIRBNB_ACCEPTANCE_KEY, (string) BDS_FORMWISE_EXECUTION_KEY );
		$checks['provider_network_intercepted'] = has_filter( 'pre_http_request', 'bds_staging_airbnb_acceptance_force_provider_failure' );
		if ( ! $checks['product_ready'] || ! $checks['provider_transport_fixture_configured'] || ! $checks['provider_network_intercepted'] ) {
			throw new RuntimeException( 'product_or_provider_unavailable' );
		}

		wp_set_current_user( $user_id );
		$GLOBALS['bds_staging_airbnb_acceptance_active'] = true;
		$checks['outbound_mail_suppressed'] = has_filter( 'pre_wp_mail', 'bds_staging_airbnb_acceptance_block_mail' )
			&& false === bds_staging_airbnb_acceptance_block_mail( null );

		$primary = bds_staging_airbnb_acceptance_order( $user_id, $product, 'primary' );
		if ( ! is_array( $primary ) ) {
			throw new RuntimeException( 'primary_order_failed' );
		}
		$primary_order = $primary['order'];
		$order_ids[] = $primary_order->get_id();
		$order_item_ids[ $primary_order->get_id() ] = $primary['item_id'];
		$checks['synthetic_paid_order_created'] = $primary_order->has_status( 'completed' )
			&& 'bds_staging_synthetic_no_gateway' === (string) $primary_order->get_payment_method();
		$primary_item = $primary_order->get_item( $primary['item_id'] );
		$checks['immutable_product_slug_recorded'] = $primary_item instanceof WC_Order_Item_Product
			&& 'branddad-ai-review-dispute-assistant' === (string) $primary_item->get_meta( '_bds_ai_case_product_slug', true );
		$entitlement = bds_ai_exec_case_entitlement( $user_id, 'branddad-ai-review-dispute-assistant' );
		$checks['entitlement_found'] = is_array( $entitlement )
			&& absint( $entitlement['order_id'] ?? 0 ) === absint( $primary_order->get_id() )
			&& absint( $entitlement['item_id'] ?? 0 ) === absint( $primary['item_id'] );

		$primary_response = bds_ai_exec_create_job( bds_staging_airbnb_acceptance_request( 'primary' ) );
		$primary_job_id = bds_staging_airbnb_acceptance_job_id( $primary_response );
		if ( ! $primary_job_id ) {
			throw new RuntimeException( is_wp_error( $primary_response ) ? $primary_response->get_error_code() : 'primary_job_failed' );
		}
		$job_ids[] = $primary_job_id;
		update_post_meta( $primary_job_id, '_bds_staging_airbnb_acceptance', BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER );
		$checks['job_created'] = 'pending' === (string) get_post_meta( $primary_job_id, '_bds_status', true );
		$checks['job_owner_scoped'] = absint( get_post_field( 'post_author', $primary_job_id ) ) === $user_id;
		bds_staging_airbnb_acceptance_unschedule_job( $primary_job_id );
		bds_ai_exec_process_job( $primary_job_id );
		$result = (string) get_post_meta( $primary_job_id, '_bds_result', true );
		$checks['provider_result_completed'] = 'completed' === (string) get_post_meta( $primary_job_id, '_bds_status', true );
		$result_length = function_exists( 'mb_strlen' ) ? mb_strlen( $result ) : strlen( $result );
		$checks['result_bounded_nonempty'] = '' !== trim( $result ) && $result_length <= 12000;
		$primary_ledger_key = 'bds_ai_case_' . absint( $primary_order->get_id() ) . '_' . absint( $primary['item_id'] );
		$primary_ledger = get_option( $primary_ledger_key, array() );
		$checks['case_committed'] = is_array( $primary_ledger ) && ! empty( $primary_ledger['used'] );

		$duplicate = bds_ai_exec_create_job( bds_staging_airbnb_acceptance_request( 'duplicate' ) );
		$checks['duplicate_case_denied'] = is_wp_error( $duplicate )
			&& in_array( $duplicate->get_error_code(), array( 'case_purchase_required', 'case_already_used' ), true );

		$refund_case = bds_staging_airbnb_acceptance_order( $user_id, $product, 'refund' );
		if ( ! is_array( $refund_case ) ) {
			throw new RuntimeException( 'refund_order_failed' );
		}
		$refund_order = $refund_case['order'];
		$order_ids[] = $refund_order->get_id();
		$order_item_ids[ $refund_order->get_id() ] = $refund_case['item_id'];
		$refund = wc_create_refund(
			array(
				'amount'         => $refund_order->get_total(),
				'reason'         => 'Synthetic Airbnb case acceptance',
				'order_id'       => $refund_order->get_id(),
				'refund_payment' => false,
				'restock_items'  => false,
			)
		);
		if ( ! $refund instanceof WC_Order_Refund ) {
			throw new RuntimeException( 'refund_creation_failed' );
		}
		$refund->update_meta_data( '_bds_staging_airbnb_acceptance', BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER );
		$refund->save();
		$refund_ids[] = $refund->get_id();
		$refund_order = wc_get_order( $refund_order->get_id() );
		$checks['refunded_order_created'] = $refund_order instanceof WC_Order
			&& $refund_order->get_total_refunded() >= $refund_order->get_total();
		$refund_attempt = bds_ai_exec_create_job( bds_staging_airbnb_acceptance_request( 'refund' ) );
		$checks['refund_blocks_case'] = is_wp_error( $refund_attempt )
			&& 'case_purchase_required' === $refund_attempt->get_error_code();

		$failure_case = bds_staging_airbnb_acceptance_order( $user_id, $product, 'failure' );
		if ( ! is_array( $failure_case ) ) {
			throw new RuntimeException( 'failure_order_failed' );
		}
		$failure_order = $failure_case['order'];
		$order_ids[] = $failure_order->get_id();
		$order_item_ids[ $failure_order->get_id() ] = $failure_case['item_id'];
		$checks['failure_order_created'] = $failure_order->has_status( 'completed' )
			&& 'bds_staging_synthetic_no_gateway' === (string) $failure_order->get_payment_method();
		$failure_response = bds_ai_exec_create_job( bds_staging_airbnb_acceptance_request( 'failure' ) );
		$failure_job_id = bds_staging_airbnb_acceptance_job_id( $failure_response );
		if ( ! $failure_job_id ) {
			throw new RuntimeException( is_wp_error( $failure_response ) ? $failure_response->get_error_code() : 'failure_job_failed' );
		}
		$job_ids[] = $failure_job_id;
		update_post_meta( $failure_job_id, '_bds_staging_airbnb_acceptance', BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER );
		bds_staging_airbnb_acceptance_unschedule_job( $failure_job_id );
		$GLOBALS['bds_staging_airbnb_acceptance_force_failure'] = true;
		bds_ai_exec_process_job( $failure_job_id );
		$GLOBALS['bds_staging_airbnb_acceptance_force_failure'] = false;
		$checks['forced_failure_recorded'] = 'failed' === (string) get_post_meta( $failure_job_id, '_bds_status', true )
			&& 'provider_network_uncertain' === (string) get_post_meta( $failure_job_id, '_bds_error_code', true );
		$released = bds_ai_exec_case_entitlement( $user_id, 'branddad-ai-review-dispute-assistant' );
		$checks['failed_case_released'] = is_array( $released )
			&& absint( $released['order_id'] ?? 0 ) === absint( $failure_order->get_id() )
			&& absint( $released['item_id'] ?? 0 ) === absint( $failure_case['item_id'] );

		$checks['no_real_gateway_or_payment'] = 3 === count(
			array_filter(
				array_map(
					static function ( $order_id ) {
						$order = wc_get_order( $order_id );
						return $order instanceof WC_Order && 'bds_staging_synthetic_no_gateway' === (string) $order->get_payment_method();
					},
					$order_ids
				)
			)
		);
	} catch ( Throwable $exception ) {
		$errors[] = $exception->getMessage();
	} finally {
		$GLOBALS['bds_staging_airbnb_acceptance_force_failure'] = false;
		foreach ( $job_ids as $job_id ) {
			bds_staging_airbnb_acceptance_unschedule_job( $job_id );
			if ( BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER === (string) get_post_meta( $job_id, '_bds_staging_airbnb_acceptance', true ) ) {
				wp_delete_post( $job_id, true );
			}
		}
		foreach ( $refund_ids as $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( $refund instanceof WC_Order_Refund && BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER === (string) $refund->get_meta( '_bds_staging_airbnb_acceptance', true ) ) {
				$refund->delete( true );
			}
		}
		$orders_clean = true;
		foreach ( $order_ids as $order_id ) {
			$orders_clean = bds_staging_airbnb_acceptance_delete_order( $order_id ) && $orders_clean;
			if ( isset( $order_item_ids[ $order_id ] ) ) {
				delete_option( 'bds_ai_case_' . absint( $order_id ) . '_' . absint( $order_item_ids[ $order_id ] ) );
			}
		}
		$jobs_clean = empty(
			array_filter(
				array_map(
					static function ( $job_id ) {
						return get_post( $job_id );
					},
					$job_ids
				)
			)
		);
		$ledgers_clean = true;
		foreach ( $order_item_ids as $order_id => $item_id ) {
			$ledgers_clean = false === get_option( 'bds_ai_case_' . absint( $order_id ) . '_' . absint( $item_id ), false ) && $ledgers_clean;
		}
		$checks['fixture_records_cleaned'] = $orders_clean && $jobs_clean && $ledgers_clean;
		if ( $product_created && $product_id && BDS_STAGING_AIRBNB_ACCEPTANCE_MARKER === (string) get_post_meta( $product_id, '_bds_staging_airbnb_acceptance', true ) ) {
			wp_delete_post( $product_id, true );
		}
		$checks['fixture_product_cleaned_or_preserved'] = $product_created ? ! get_post( $product_id ) : ( $product_id > 0 && get_post( $product_id ) instanceof WP_Post );
		$GLOBALS['bds_staging_airbnb_acceptance_active'] = false;
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === absint( $original_user );
		delete_option( $lock );
		bds_staging_airbnb_acceptance_save_report( $checks, $errors );
	}
}
add_action( 'init', 'bds_staging_airbnb_acceptance_run', 150 );

function bds_staging_airbnb_acceptance_report() {
	$report = get_option( BDS_STAGING_AIRBNB_ACCEPTANCE_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_airbnb_acceptance_register_route() {
	if ( bds_staging_airbnb_acceptance_is_host() ) {
		register_rest_route(
			'branddad/v1',
			'/staging/airbnb-case-acceptance',
			array(
				'methods'             => 'GET',
				'callback'            => 'bds_staging_airbnb_acceptance_report',
				'permission_callback' => '__return_true',
			)
		);
	}
}
add_action( 'rest_api_init', 'bds_staging_airbnb_acceptance_register_route' );
