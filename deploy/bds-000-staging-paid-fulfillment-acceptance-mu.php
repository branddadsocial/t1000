<?php
/**
 * Plugin Name: BrandDad Staging Paid Fulfillment Acceptance
 * Description: One-time, exact-host acceptance for unpaid suppression and paid task idempotency.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_PAID_TASK_VERSION', '0.1.0' );
define( 'BDS_STAGING_PAID_TASK_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_PAID_TASK_REPORT', 'bds_staging_paid_task_report_v1' );
define( 'BDS_STAGING_PAID_TASK_MARKER', 'paid-task-acceptance-v1' );

function bds_staging_paid_task_is_host() {
	return BDS_STAGING_PAID_TASK_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_paid_task_checks() {
	return array_fill_keys(
		array(
			'exact_staging_host',
			'dependencies_loaded',
			'press_release_product_ready',
			'unpaid_order_created',
			'unpaid_order_created_no_task',
			'paid_state_recorded_without_gateway',
			'confirmed_payment_created_one_task',
			'duplicate_payment_created_no_second_task',
			'quote_product_ready',
			'accepted_quote_admin_order_created',
			'accepted_quote_paid_task_created_once',
			'no_real_gateway_or_payment',
			'fixture_records_cleaned',
		),
		false
	);
}

function bds_staging_paid_task_save_report( $checks, $errors ) {
	$checks = array_intersect_key( array_map( 'boolval', (array) $checks ), bds_staging_paid_task_checks() );
	$errors = array_map(
		static function ( $error ) {
			return substr( sanitize_key( (string) $error ), 0, 80 );
		},
		array_slice( (array) $errors, 0, 12 )
	);
	update_option(
		BDS_STAGING_PAID_TASK_REPORT,
		array(
			'version'      => BDS_STAGING_PAID_TASK_VERSION,
			'product'      => 'ai-press-release-pack',
			'complete'     => count( $checks ) === count( array_filter( $checks ) ) && empty( $errors ),
			'checks'       => $checks,
			'error_codes'  => array_values( array_unique( array_filter( $errors ) ) ),
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

/** Return an existing product or create a private, marked staging-only fixture. */
function bds_staging_paid_task_product( $slug, $name, $price, &$created ) {
	$created = false;
	$post    = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $post instanceof WP_Post ) {
		$product = wc_get_product( $post->ID );
		return $product instanceof WC_Product ? $product : null;
	}
	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_slug( $slug );
	$product->set_status( 'private' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_virtual( true );
	$product->set_sold_individually( true );
	$product->set_regular_price( $price );
	$product->set_price( $price );
	$product->update_meta_data( '_bds_staging_paid_task', BDS_STAGING_PAID_TASK_MARKER );
	$product_id = $product->save();
	if ( ! $product_id ) {
		return null;
	}
	$created = true;
	return wc_get_product( $product_id );
}

/** Create a pending synthetic order. No gateway method is invoked. */
function bds_staging_paid_task_order( $product, $slug, $created_via ) {
	$order = wc_create_order( array( 'created_via' => sanitize_key( $created_via ) ) );
	if ( ! $order instanceof WC_Order ) {
		return null;
	}
	$order->set_payment_method( 'bds_staging_synthetic_no_gateway' );
	$order->set_payment_method_title( 'Staging synthetic — no gateway' );
	$item_id = $order->add_product( $product, 1 );
	$item    = $item_id ? $order->get_item( $item_id ) : null;
	if ( ! $item instanceof WC_Order_Item_Product ) {
		$order->delete( true );
		return null;
	}
	if ( 'ai-press-release-pack' === $slug ) {
		$item->add_meta_data( '_bds_submission_service_slug', $slug, true );
		$item->add_meta_data( '_bds_submission_mode', 'branddad_submits', true );
		$item->add_meta_data( '_bds_submission_status', 'awaiting_customer_details', true );
		$item->save();
	}
	$order->calculate_totals( false );
	$order->update_meta_data( '_bds_staging_paid_task', BDS_STAGING_PAID_TASK_MARKER );
	$order->set_status( 'pending' );
	$order->save();
	return $order;
}

function bds_staging_paid_task_note_count( $order_id ) {
	$count = 0;
	foreach ( wc_get_order_notes( array( 'order_id' => absint( $order_id ), 'limit' => 100 ) ) as $note ) {
		$content = is_object( $note ) && isset( $note->content ) ? (string) $note->content : '';
		if ( 0 === strpos( $content, 'BrandDad paid fulfillment task:' ) ) {
			$count++;
		}
	}
	return $count;
}

function bds_staging_paid_task_mark_paid( $order ) {
	$order->set_date_paid( time() );
	$order->set_status( 'completed' );
	$order->save();
}

function bds_staging_paid_task_cleanup_order( $order_id ) {
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order instanceof WC_Order || BDS_STAGING_PAID_TASK_MARKER !== (string) $order->get_meta( '_bds_staging_paid_task', true ) ) {
		return false;
	}
	delete_option( bds_submit_staff_task_claim_key( $order_id ) );
	$order->delete( true );
	return ! wc_get_order( absint( $order_id ) );
}

function bds_staging_paid_task_run() {
	if ( ! bds_staging_paid_task_is_host() || get_option( BDS_STAGING_PAID_TASK_REPORT, false ) ) {
		return;
	}
	$lock = 'bds_staging_paid_task_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		return;
	}
	$checks          = bds_staging_paid_task_checks();
	$errors          = array();
	$order_ids       = array();
	$product_records = array();
	try {
		$checks['exact_staging_host'] = bds_staging_paid_task_is_host();
		$checks['dependencies_loaded'] = class_exists( 'WooCommerce' )
			&& function_exists( 'bds_submit_maybe_create_paid_staff_task' )
			&& function_exists( 'wc_get_order_notes' );
		if ( ! $checks['exact_staging_host'] || ! $checks['dependencies_loaded'] ) {
			throw new RuntimeException( 'dependencies_unavailable' );
		}

		$press_created = false;
		$press = bds_staging_paid_task_product( 'ai-press-release-pack', 'Press Release Submission — Staging Acceptance', '39.99', $press_created );
		$product_records[] = array( $press, $press_created );
		$checks['press_release_product_ready'] = $press instanceof WC_Product && 'ai-press-release-pack' === (string) $press->get_slug();
		if ( ! $checks['press_release_product_ready'] ) {
			throw new RuntimeException( 'press_product_unavailable' );
		}

		$press_order = bds_staging_paid_task_order( $press, 'ai-press-release-pack', 'checkout' );
		if ( ! $press_order instanceof WC_Order ) {
			throw new RuntimeException( 'press_order_unavailable' );
		}
		$order_ids[] = $press_order->get_id();
		$checks['unpaid_order_created'] = $press_order->has_status( 'pending' ) && ! $press_order->is_paid();
		bds_submit_maybe_create_paid_staff_task( $press_order );
		$checks['unpaid_order_created_no_task'] = 0 === bds_staging_paid_task_note_count( $press_order->get_id() )
			&& '' === (string) $press_order->get_meta( '_bds_submission_staff_task_note_id', true );

		bds_staging_paid_task_mark_paid( $press_order );
		$press_order = wc_get_order( $press_order->get_id() );
		$checks['paid_state_recorded_without_gateway'] = $press_order instanceof WC_Order
			&& $press_order->is_paid()
			&& 'bds_staging_synthetic_no_gateway' === (string) $press_order->get_payment_method();
		do_action( 'woocommerce_payment_complete', $press_order->get_id() );
		$checks['confirmed_payment_created_one_task'] = 1 === bds_staging_paid_task_note_count( $press_order->get_id() )
			&& 0 < absint( $press_order->get_meta( '_bds_submission_staff_task_note_id', true ) );
		do_action( 'woocommerce_payment_complete', $press_order->get_id() );
		do_action( 'woocommerce_order_status_completed', $press_order->get_id() );
		$checks['duplicate_payment_created_no_second_task'] = 1 === bds_staging_paid_task_note_count( $press_order->get_id() );

		$quote_created = false;
		$quote = bds_staging_paid_task_product( 'done-for-you-ai-management', 'Done-for-You AI Management — Staging Acceptance', '399.00', $quote_created );
		$product_records[] = array( $quote, $quote_created );
		$checks['quote_product_ready'] = $quote instanceof WC_Product && 'done-for-you-ai-management' === (string) $quote->get_slug();
		if ( ! $checks['quote_product_ready'] ) {
			throw new RuntimeException( 'quote_product_unavailable' );
		}
		$quote_order = bds_staging_paid_task_order( $quote, 'done-for-you-ai-management', 'admin' );
		if ( ! $quote_order instanceof WC_Order ) {
			throw new RuntimeException( 'quote_order_unavailable' );
		}
		$order_ids[] = $quote_order->get_id();
		$checks['accepted_quote_admin_order_created'] = 'admin' === (string) $quote_order->get_created_via() && ! $quote_order->is_paid();
		bds_staging_paid_task_mark_paid( $quote_order );
		do_action( 'woocommerce_payment_complete', $quote_order->get_id() );
		do_action( 'woocommerce_payment_complete', $quote_order->get_id() );
		$checks['accepted_quote_paid_task_created_once'] = 1 === bds_staging_paid_task_note_count( $quote_order->get_id() );
		$checks['no_real_gateway_or_payment'] = 2 === count(
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
		$clean = true;
		foreach ( $order_ids as $order_id ) {
			$clean = bds_staging_paid_task_cleanup_order( $order_id ) && $clean;
		}
		foreach ( $product_records as $record ) {
			$product = $record[0];
			$created = $record[1];
			if ( $created && $product instanceof WC_Product && BDS_STAGING_PAID_TASK_MARKER === (string) $product->get_meta( '_bds_staging_paid_task', true ) ) {
				wp_delete_post( $product->get_id(), true );
				$clean = ! get_post( $product->get_id() ) && $clean;
			}
		}
		$checks['fixture_records_cleaned'] = $clean;
		delete_option( $lock );
		bds_staging_paid_task_save_report( $checks, $errors );
	}
}
add_action( 'init', 'bds_staging_paid_task_run', 160 );

function bds_staging_paid_task_report() {
	$report = get_option( BDS_STAGING_PAID_TASK_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_paid_task_register_route() {
	if ( ! bds_staging_paid_task_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/paid-fulfillment-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_paid_task_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_paid_task_register_route' );
