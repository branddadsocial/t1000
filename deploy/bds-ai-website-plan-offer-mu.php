<?php
/**
 * Plugin Name: BrandDad AI Website Improvement Offer
 * Description: Makes the existing website-improvement product a one-purchase/one-plan AI service and gives customers a secure account workspace.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_WEBSITE_PLAN_OFFER_VERSION', '0.1.0' );
define( 'BDS_AI_WEBSITE_PLAN_PRODUCT_SLUG', 'website-speed-optimization' );
define( 'BDS_AI_WEBSITE_PLAN_PRICE', '19.99' );

/** Keep this commercial change on BrandDad Social only. */
function bds_ai_website_plan_is_social() {
	$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

/** Resolve only the existing exact-slug product. */
function bds_ai_website_plan_product_id() {
	$post = get_page_by_path( BDS_AI_WEBSITE_PLAN_PRODUCT_SLUG, OBJECT, 'product' );
	return $post instanceof WP_Post ? absint( $post->ID ) : 0;
}

function bds_ai_website_plan_is_product( $product ) {
	return $product instanceof WC_Product && BDS_AI_WEBSITE_PLAN_PRODUCT_SLUG === sanitize_title( $product->get_slug() );
}

/**
 * Apply the approved offer once and retain a reversible copy of the old catalog fields.
 * Historical orders are never rewritten.
 */
function bds_ai_website_plan_migrate_product() {
	if ( ! bds_ai_website_plan_is_social() || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	if ( BDS_AI_WEBSITE_PLAN_OFFER_VERSION === get_option( 'bds_ai_website_plan_offer_version' ) ) {
		return;
	}
	$product_id = bds_ai_website_plan_product_id();
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! bds_ai_website_plan_is_product( $product ) ) {
		update_option( 'bds_ai_website_plan_offer_last_error', 'exact_product_not_found', false );
		return;
	}

	if ( ! get_option( 'bds_ai_website_plan_offer_backup' ) ) {
		update_option(
			'bds_ai_website_plan_offer_backup',
			array(
				'captured_at'       => gmdate( DATE_ATOM ),
				'product_id'        => $product_id,
				'name'              => $product->get_name(),
				'short_description' => $product->get_short_description(),
				'description'       => $product->get_description(),
				'regular_price'     => $product->get_regular_price(),
				'price'             => $product->get_price(),
				'status'            => $product->get_status(),
				'catalog_visibility'=> $product->get_catalog_visibility(),
				'virtual'           => $product->get_virtual(),
				'sold_individually' => $product->get_sold_individually(),
			),
			false
		);
	}

	$product->set_name( 'AI Website Improvement Plan' );
	$product->set_short_description( 'Pay once. Tell BrandDad what is wrong and which page matters most. Receive a clear, prioritized AI improvement plan you can understand and use.' );
	$product->set_description(
		'<h2>Know what to fix before you spend more</h2>' .
		'<p>Answer three short questions about your website. BrandDad AI reviews the details you provide and creates a practical plan for speed, repairs, conversions, or ongoing care.</p>' .
		'<h3>What you receive</h3>' .
		'<ul><li>The most important issue explained in plain language.</li><li>A prioritized action plan.</li><li>How to verify each improvement.</li><li>A rollback note for supported changes.</li></ul>' .
		'<h3>Want BrandDad to apply supported fixes?</h3>' .
		'<p>You can request implementation after the plan is ready. We require a secure website connection and your approval before changing anything. Hosting upgrades, paid services, custom code, and work outside the supported WordPress controls are quoted separately.</p>' .
		'<p><strong>No ranking, traffic, sales, or speed score is guaranteed.</strong></p>'
	);
	$product->set_regular_price( BDS_AI_WEBSITE_PLAN_PRICE );
	$product->set_sale_price( '' );
	$product->set_price( BDS_AI_WEBSITE_PLAN_PRICE );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_virtual( true );
	$product->set_sold_individually( true );
	$product->set_stock_status( 'instock' );
	$product->save();

	update_option( 'bds_ai_website_plan_offer_version', BDS_AI_WEBSITE_PLAN_OFFER_VERSION, false );
	delete_option( 'bds_ai_website_plan_offer_last_error' );
}
add_action( 'init', 'bds_ai_website_plan_migrate_product', 40 );

/** Override older quote-only presentation for this exact product only. */
function bds_ai_website_plan_is_purchasable( $purchasable, $product ) {
	return bds_ai_website_plan_is_social() && bds_ai_website_plan_is_product( $product ) ? true : $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'bds_ai_website_plan_is_purchasable', PHP_INT_MAX, 2 );

function bds_ai_website_plan_loop_button( $html, $product ) {
	if ( ! bds_ai_website_plan_is_social() || ! bds_ai_website_plan_is_product( $product ) ) {
		return $html;
	}
	return sprintf(
		'<a class="button product_type_simple add_to_cart_button ajax_add_to_cart" href="%1$s" data-product_id="%2$d" data-quantity="1" rel="nofollow">%3$s</a>',
		esc_url( $product->add_to_cart_url() ),
		absint( $product->get_id() ),
		esc_html__( 'Buy AI plan', 'branddad' )
	);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'bds_ai_website_plan_loop_button', PHP_INT_MAX, 2 );

function bds_ai_website_plan_add_to_cart_text( $text, $product ) {
	return bds_ai_website_plan_is_social() && bds_ai_website_plan_is_product( $product ) ? 'Buy AI plan' : $text;
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'bds_ai_website_plan_add_to_cart_text', PHP_INT_MAX, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', 'bds_ai_website_plan_add_to_cart_text', PHP_INT_MAX, 2 );

/** Customer workspace endpoint. */
function bds_ai_website_plan_endpoint() {
	add_rewrite_endpoint( 'website-plan', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'bds_ai_website_plan_endpoint', 5 );

function bds_ai_website_plan_maybe_flush_rewrite() {
	if ( ! bds_ai_website_plan_is_social() || BDS_AI_WEBSITE_PLAN_OFFER_VERSION === get_option( 'bds_ai_website_plan_rewrite_version' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'bds_ai_website_plan_rewrite_version', BDS_AI_WEBSITE_PLAN_OFFER_VERSION, false );
}
add_action( 'init', 'bds_ai_website_plan_maybe_flush_rewrite', 60 );

function bds_ai_website_plan_account_menu( $items ) {
	if ( ! bds_ai_website_plan_is_social() || ! is_array( $items ) ) {
		return $items;
	}
	$logout = isset( $items['customer-logout'] ) ? $items['customer-logout'] : null;
	unset( $items['customer-logout'] );
	$items['website-plan'] = 'My Website Plan';
	if ( null !== $logout ) {
		$items['customer-logout'] = $logout;
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'bds_ai_website_plan_account_menu', 30 );

/** Submit through the existing authenticated, metered, idempotent execution handler. */
function bds_ai_website_plan_submit() {
	if ( ! bds_ai_website_plan_is_social() || ! is_user_logged_in() || 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		return;
	}
	if ( empty( $_POST['bds_ai_website_plan_action'] ) ) {
		return;
	}
	if ( ! isset( $_POST['_bds_ai_website_plan_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_bds_ai_website_plan_nonce'] ) ), 'bds_ai_website_plan_submit' ) ) {
		wp_safe_redirect( add_query_arg( 'website_plan_error', 'invalid_request', wc_get_account_endpoint_url( 'website-plan' ) ) );
		exit;
	}
	if ( ! function_exists( 'bds_ai_exec_create_job' ) ) {
		wp_safe_redirect( add_query_arg( 'website_plan_error', 'temporarily_unavailable', wc_get_account_endpoint_url( 'website-plan' ) ) );
		exit;
	}

	$allowed_help = array( 'Repair', 'Speed', 'Conversion', 'Ongoing care' );
	$help         = sanitize_text_field( wp_unslash( $_POST['website_help'] ?? '' ) );
	$page_goal    = sanitize_textarea_field( wp_unslash( $_POST['page_goal'] ?? '' ) );
	$observed     = sanitize_textarea_field( wp_unslash( $_POST['observed'] ?? '' ) );
	if ( ! in_array( $help, $allowed_help, true ) || '' === $page_goal || '' === $observed ) {
		wp_safe_redirect( add_query_arg( 'website_plan_error', 'missing_details', wc_get_account_endpoint_url( 'website-plan' ) ) );
		exit;
	}

	$request = new WP_REST_Request( 'POST', '/branddad/v1/ai/jobs' );
	$request->set_header( 'Idempotency-Key', 'webplan-' . wp_generate_uuid4() );
	$request->set_param( 'tool', 'website_plan' );
	$request->set_param(
		'inputs',
		array(
			'what_kind_of_website_help_is_needed_'          => $help,
			'what_public_page_and_goal_should_we_focus_on_' => $page_goal,
			'what_did_you_observe_'                          => $observed,
		)
	);
	$response = bds_ai_exec_create_job( $request );
	if ( is_wp_error( $response ) ) {
		$code = in_array( $response->get_error_code(), array( 'case_purchase_required', 'case_already_used', 'request_in_progress' ), true ) ? $response->get_error_code() : 'temporarily_unavailable';
		wp_safe_redirect( add_query_arg( 'website_plan_error', $code, wc_get_account_endpoint_url( 'website-plan' ) ) );
		exit;
	}
	$data   = $response instanceof WP_REST_Response ? $response->get_data() : $response;
	$job_id = is_array( $data ) ? absint( $data['id'] ?? 0 ) : 0;
	if ( $job_id && ! empty( $_POST['request_implementation'] ) ) {
		update_post_meta( $job_id, '_bds_customer_requested_implementation', 1 );
	}
	wp_safe_redirect( add_query_arg( 'website_plan_job', $job_id, wc_get_account_endpoint_url( 'website-plan' ) ) );
	exit;
}
add_action( 'template_redirect', 'bds_ai_website_plan_submit', 5 );

function bds_ai_website_plan_account_content() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$errors = array(
		'invalid_request'         => 'Please refresh the page and try again.',
		'missing_details'         => 'Please answer all three questions.',
		'case_purchase_required'  => 'Buy the AI Website Improvement Plan before starting your one included plan.',
		'case_already_used'       => 'That purchase already has a plan in progress or completed.',
		'request_in_progress'     => 'Your request is already being created. Please wait a moment.',
		'temporarily_unavailable' => 'The AI plan is temporarily unavailable. Your purchase has not been used.',
	);
	$error_key = sanitize_key( wp_unslash( $_GET['website_plan_error'] ?? '' ) );
	if ( isset( $errors[ $error_key ] ) ) {
		echo '<div class="woocommerce-error" role="alert">' . esc_html( $errors[ $error_key ] ) . '</div>';
	}

	$job_id = absint( wp_unslash( $_GET['website_plan_job'] ?? 0 ) );
	if ( $job_id && function_exists( 'bds_ai_exec_job_response' ) ) {
		$job = bds_ai_exec_job_response( $job_id );
		if ( ! is_wp_error( $job ) ) {
			echo '<section class="bds-ai-plan-result"><h2>' . esc_html__( 'Your website plan', 'branddad' ) . '</h2>';
			echo '<p><strong>' . esc_html__( 'Status:', 'branddad' ) . '</strong> ' . esc_html( ucwords( str_replace( '_', ' ', (string) $job['status'] ) ) ) . '</p>';
			if ( ! empty( $job['result'] ) ) {
				echo '<div class="bds-ai-plan-output">' . nl2br( esc_html( (string) $job['result'] ) ) . '</div>';
				echo '<p><strong>' . esc_html__( 'Next step:', 'branddad' ) . '</strong> ' . esc_html__( 'Review the plan. If you requested implementation, BrandDad will ask for a secure connection and exact approval before supported changes are made.', 'branddad' ) . '</p>';
			} elseif ( 'failed' === $job['status'] ) {
				echo '<p>' . esc_html__( 'The plan could not be completed. Your one-time purchase was released so you can try again.', 'branddad' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'Your plan is being prepared. Refresh this page in a moment.', 'branddad' ) . '</p>';
			}
			echo '</section>';
		}
	}

	echo '<section class="bds-ai-plan-form"><h2>' . esc_html__( 'Create my website improvement plan', 'branddad' ) . '</h2>';
	echo '<p>' . esc_html__( 'One purchase includes one plan. Do not paste passwords, private keys, or payment information.', 'branddad' ) . '</p>';
	echo '<form method="post">';
	wp_nonce_field( 'bds_ai_website_plan_submit', '_bds_ai_website_plan_nonce' );
	echo '<input type="hidden" name="bds_ai_website_plan_action" value="1">';
	echo '<p><label for="bds-website-help"><strong>' . esc_html__( 'What kind of help do you need?', 'branddad' ) . '</strong></label><select id="bds-website-help" name="website_help" required><option value="">' . esc_html__( 'Choose one', 'branddad' ) . '</option><option>Repair</option><option>Speed</option><option>Conversion</option><option>Ongoing care</option></select></p>';
	echo '<p><label for="bds-page-goal"><strong>' . esc_html__( 'Which public page and goal matter most?', 'branddad' ) . '</strong></label><textarea id="bds-page-goal" name="page_goal" maxlength="2000" required></textarea></p>';
	echo '<p><label for="bds-observed"><strong>' . esc_html__( 'What did you notice or what did the website checker find?', 'branddad' ) . '</strong></label><textarea id="bds-observed" name="observed" maxlength="4000" required></textarea></p>';
	echo '<p><label><input type="checkbox" name="request_implementation" value="1"> ' . esc_html__( 'After the plan, I want BrandDad to explain which supported fixes can be applied. I understand no change happens until I connect the site securely and approve the exact work.', 'branddad' ) . '</label></p>';
	echo '<p><button class="button" type="submit">' . esc_html__( 'Create my AI plan', 'branddad' ) . '</button></p></form></section>';
}
add_action( 'woocommerce_account_website-plan_endpoint', 'bds_ai_website_plan_account_content' );

/** Lead a paid customer directly to the workspace without auto-running or spending credits. */
function bds_ai_website_plan_thankyou( $order_id ) {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( absint( $order_id ) );
	if ( ! $order instanceof WC_Order || absint( $order->get_customer_id() ) !== get_current_user_id() ) {
		return;
	}
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;
		$slug    = $item instanceof WC_Order_Item_Product ? sanitize_title( (string) $item->get_meta( '_bds_ai_case_product_slug', true ) ) : '';
		if ( BDS_AI_WEBSITE_PLAN_PRODUCT_SLUG === $slug || bds_ai_website_plan_is_product( $product ) ) {
			echo '<p><a class="button" href="' . esc_url( wc_get_account_endpoint_url( 'website-plan' ) ) . '">' . esc_html__( 'Start my AI website plan', 'branddad' ) . '</a></p>';
			break;
		}
	}
}
add_action( 'woocommerce_thankyou', 'bds_ai_website_plan_thankyou', 20 );
