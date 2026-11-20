<?php
/**
 * Plugin Name: BrandDad Staging Sales Assistant Acceptance
 * Description: One-time, exact-host grounding, session, sanitization, and cost-cap acceptance.
 * Version: 0.1.1
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_STAGING_SALES_ACCEPTANCE_VERSION', '0.1.1' );
define( 'BDS_STAGING_SALES_ACCEPTANCE_HOST', 'staging-branddad-social.stackstaging.com' );
define( 'BDS_STAGING_SALES_ACCEPTANCE_REPORT', 'bds_staging_sales_assistant_report_v2' );
define( 'BDS_STAGING_SALES_ACCEPTANCE_IP', '192.0.2.77' );
define( 'BDS_STAGING_SALES_ACCEPTANCE_KEY', 'staging-sales-acceptance-only-not-a-provider-credential' );

function bds_staging_sales_acceptance_is_host() {
	return BDS_STAGING_SALES_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function bds_staging_sales_acceptance_pending() {
	return bds_staging_sales_acceptance_is_host() && ! get_option( BDS_STAGING_SALES_ACCEPTANCE_REPORT, false );
}

if ( bds_staging_sales_acceptance_pending() ) {
	if ( ! defined( 'BDS_SALES_ASSISTANT_ENABLED' ) ) {
		define( 'BDS_SALES_ASSISTANT_ENABLED', true );
	}
	if ( ! defined( 'BDS_SALES_ASSISTANT_DAILY_LIMIT' ) ) {
		define( 'BDS_SALES_ASSISTANT_DAILY_LIMIT', 3 );
	}
	if ( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ) ) {
		define( 'BDS_FORMWISE_EXECUTION_KEY', BDS_STAGING_SALES_ACCEPTANCE_KEY );
	}
}

$GLOBALS['bds_staging_sales_acceptance_http'] = array(
	'active'       => false,
	'calls'        => 0,
	'unexpected'   => 0,
	'continuations'=> 0,
);

/** Block every real sales-agent request while the dummy credential exists. */
function bds_staging_sales_acceptance_http( $preempt, $args, $url ) {
	if ( ! bds_staging_sales_acceptance_pending() ) {
		return $preempt;
	}
	$expected = 'https://builder.formwise.ai/api/v1/agents/' . rawurlencode( BDS_SALES_ASSISTANT_AGENT_ID ) . '/conversation';
	if ( 0 !== strpos( (string) $url, 'https://builder.formwise.ai/' ) ) {
		return $preempt;
	}
	$state =& $GLOBALS['bds_staging_sales_acceptance_http'];
	if ( empty( $state['active'] ) || $expected !== (string) $url ) {
		$state['unexpected']++;
		return new WP_Error( 'staging_sales_provider_blocked' );
	}
	$headers = is_array( $args['headers'] ?? null ) ? $args['headers'] : array();
	$body    = json_decode( (string) ( $args['body'] ?? '' ), true );
	$message = is_array( $body ) ? (string) ( $body['message'] ?? '' ) : '';
	$valid   = 'Bearer ' . BDS_STAGING_SALES_ACCEPTANCE_KEY === (string) ( $headers['Authorization'] ?? '' )
		&& 'application/json' === (string) ( $headers['Content-Type'] ?? '' )
		&& 30 === absint( $args['timeout'] ?? 0 )
		&& 0 === absint( $args['redirection'] ?? 1 )
		&& 65536 === absint( $args['limit_response_size'] ?? 0 )
		&& false !== strpos( $message, 'server-verified offers and prices' )
		&& false !== strpos( $message, 'never invent a discount' )
		&& false !== strpos( $message, 'Customer question:' )
		&& false === strpos( $message, '"url"' );
	if ( ! $valid ) {
		$state['unexpected']++;
		return new WP_Error( 'staging_sales_provider_shape_invalid' );
	}
	$state['calls']++;
	if ( ! empty( $body['conversation_id'] ) ) {
		if ( 'staging-sales-conversation' !== (string) $body['conversation_id'] ) {
			$state['unexpected']++;
			return new WP_Error( 'staging_sales_conversation_invalid' );
		}
		$state['continuations']++;
	}
	return array(
		'headers'  => array( 'content-type' => 'application/json' ),
		'body'     => wp_json_encode(
			array(
				'reply'          => '<b>Simple help</b> — do not use https://evil.example/fake-checkout or http://phish.example.',
				'conversationId' => 'staging-sales-conversation',
			)
		),
		'response' => array( 'code' => 200, 'message' => 'OK' ),
		'cookies'  => array(),
		'filename' => null,
	);
}
add_filter( 'pre_http_request', 'bds_staging_sales_acceptance_http', 1, 3 );

function bds_staging_sales_acceptance_save_report( $complete, $checks, $errors ) {
	$allowed = array(
		'dependencies_loaded',
		'fixture_product_verified',
		'assistant_enabled_for_run',
		'owned_origin_allowed',
		'unowned_origin_denied',
		'oversized_request_denied',
		'catalog_grounded',
		'catalog_checkout_authoritative',
		'seo_offer_ranked_first',
		'seo_offer_saleable',
		'fallback_offer_saleable',
		'first_provider_shape_valid',
		'hostile_reply_links_removed',
		'hostile_reply_html_removed',
		'session_token_valid',
		'session_token_tamper_denied',
		'session_token_expiry_denied',
		'session_token_client_bound',
		'conversation_continues_server_side',
		'second_reply_grounded',
		'third_reply_grounded',
		'daily_cap_denies_fourth',
		'daily_cap_stops_provider_call',
		'client_minute_cap_denies',
		'exact_provider_call_count',
		'provider_traffic_fully_intercepted',
		'rate_state_restored',
		'conversation_state_cleaned',
		'server_state_restored',
		'original_context_restored',
		'no_real_provider_or_customer_mutation',
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
		BDS_STAGING_SALES_ACCEPTANCE_REPORT,
		array(
			'version'      => BDS_STAGING_SALES_ACCEPTANCE_VERSION,
			'complete'     => true === $complete,
			'checks'       => $safe_checks,
			'error_codes'  => $safe_errors,
			'completed_at' => gmdate( DATE_ATOM ),
		),
		false
	);
}

function bds_staging_sales_acceptance_data( $response ) {
	if ( is_wp_error( $response ) ) {
		return array();
	}
	$data = $response instanceof WP_REST_Response ? $response->get_data() : $response;
	return is_array( $data ) ? $data : array();
}

function bds_staging_sales_acceptance_offer( $offers, $slug ) {
	foreach ( (array) $offers as $offer ) {
		if ( is_array( $offer ) && sanitize_key( (string) ( $offer['slug'] ?? '' ) ) === sanitize_key( $slug ) ) {
			return $offer;
		}
	}
	return array();
}

function bds_staging_sales_acceptance_request( $message, $session = '' ) {
	$request = new WP_REST_Request( 'POST', '/branddad/v1/sales/chat' );
	$request->set_body_params( array( 'message' => (string) $message, 'session' => (string) $session ) );
	return bds_sales_assistant_chat( $request );
}

/** Run once with fixed synthetic input and exact state restoration. */
function bds_staging_sales_acceptance_run() {
	if ( ! bds_staging_sales_acceptance_pending() ) {
		return;
	}
	$lock = 'bds_staging_sales_assistant_lock_v1';
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( absint( get_option( $lock, 0 ) ) >= time() - 600 ) {
			return;
		}
		delete_option( $lock );
		if ( ! add_option( $lock, time(), '', false ) ) {
			return;
		}
	}

	$checks            = array();
	$errors            = array();
	$original_user     = get_current_user_id();
	$server_keys       = array( 'REMOTE_ADDR', 'HTTP_ORIGIN', 'CONTENT_LENGTH' );
	$server_before     = array();
	$server_existed    = array();
	$conversation_keys = array();
	$rate_key          = '';
	$rate_before       = false;
	$daily_key         = 'bds_sales_daily_' . gmdate( 'Ymd' );
	$daily_before      = get_option( $daily_key, null );
	$daily_existed     = null !== $daily_before;
	$http_state       =& $GLOBALS['bds_staging_sales_acceptance_http'];

	foreach ( $server_keys as $key ) {
		$server_existed[ $key ] = array_key_exists( $key, $_SERVER );
		$server_before[ $key ]  = $_SERVER[ $key ] ?? null;
	}

	try {
		$required = array(
			'bds_sales_assistant_ready',
			'bds_sales_assistant_origin_allowed',
			'bds_sales_assistant_rate_limit',
			'bds_sales_assistant_session_token',
			'bds_sales_assistant_verify_session',
			'bds_sales_assistant_offers',
			'bds_sales_assistant_catalog',
			'bds_sales_assistant_chat',
			'bds_ai_commercial_catalog',
			'bds_ai_commercial_checkout_url',
		);
		$checks['dependencies_loaded'] = true;
		foreach ( $required as $function ) {
			if ( ! function_exists( $function ) ) {
				$checks['dependencies_loaded'] = false;
				throw new RuntimeException( 'missing_dependency' );
			}
		}

		$product_post = get_page_by_path( 'ai-business-essentials', OBJECT, 'product' );
		$product      = $product_post instanceof WP_Post ? wc_get_product( $product_post->ID ) : false;
		$checks['fixture_product_verified'] = $product instanceof WC_Product
			&& 'yes' === (string) get_post_meta( $product->get_id(), '_bds_staging_fixture', true )
			&& '29.99' === (string) $product->get_regular_price();
		if ( ! $checks['fixture_product_verified'] ) {
			throw new RuntimeException( 'fixture_product_unavailable' );
		}

		wp_set_current_user( 0 );
		$_SERVER['REMOTE_ADDR']   = BDS_STAGING_SALES_ACCEPTANCE_IP;
		$_SERVER['HTTP_ORIGIN']   = 'https://branddad.social';
		$_SERVER['CONTENT_LENGTH']= '256';
		$rate_key = 'bds_sales_rate_' . substr( bds_sales_assistant_client_key(), 0, 32 );
		$rate_before = get_transient( $rate_key );
		delete_transient( $rate_key );
		delete_option( $daily_key );

		$checks['assistant_enabled_for_run'] = bds_sales_assistant_ready()
			&& 3 === BDS_SALES_ASSISTANT_DAILY_LIMIT
			&& BDS_STAGING_SALES_ACCEPTANCE_KEY === BDS_FORMWISE_EXECUTION_KEY;
		$checks['owned_origin_allowed'] = bds_sales_assistant_origin_allowed();
		$_SERVER['HTTP_ORIGIN'] = 'https://attacker.example';
		$checks['unowned_origin_denied'] = ! bds_sales_assistant_origin_allowed();
		$_SERVER['HTTP_ORIGIN'] = 'https://branddad.social';
		$_SERVER['CONTENT_LENGTH'] = '20000';
		$oversized = bds_staging_sales_acceptance_request( 'A fixed oversized-request test.' );
		$checks['oversized_request_denied'] = is_wp_error( $oversized ) && 'request_too_large' === $oversized->get_error_code() && 0 === absint( $http_state['calls'] );
		$_SERVER['CONTENT_LENGTH'] = '256';

		$catalog = bds_staging_sales_acceptance_data( bds_sales_assistant_catalog() );
		$catalog_offer = bds_staging_sales_acceptance_offer( $catalog['offers'] ?? array(), 'ai-business-essentials' );
		$checks['catalog_grounded'] = 1 === count( $catalog['offers'] ?? array() )
			&& '29.99' === (string) ( $catalog_offer['monthly'] ?? '' );
		$checks['catalog_checkout_authoritative'] = true === ( $catalog_offer['for_sale'] ?? false )
			&& BDS_STAGING_SALES_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( (string) ( $catalog_offer['url'] ?? '' ), PHP_URL_HOST ) );

		$http_state['active'] = true;
		$first = bds_staging_sales_acceptance_data( bds_staging_sales_acceptance_request( 'I need local SEO help for my business.' ) );
		$first_offers = is_array( $first['offers'] ?? null ) ? $first['offers'] : array();
		$seo_offer = bds_staging_sales_acceptance_offer( $first_offers, 'ai-local-seo' );
		$fallback_offer = bds_staging_sales_acceptance_offer( $first_offers, 'ai-business-essentials' );
		$checks['seo_offer_ranked_first'] = 'ai-local-seo' === (string) ( $first_offers[0]['slug'] ?? '' );
		$checks['seo_offer_saleable'] = true === ( $seo_offer['for_sale'] ?? false )
			&& BDS_STAGING_SALES_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( (string) ( $seo_offer['url'] ?? '' ), PHP_URL_HOST ) );
		$checks['fallback_offer_saleable'] = true === ( $fallback_offer['for_sale'] ?? false )
			&& BDS_STAGING_SALES_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( (string) ( $fallback_offer['url'] ?? '' ), PHP_URL_HOST ) );
		$checks['first_provider_shape_valid'] = 1 === absint( $http_state['calls'] ) && 0 === absint( $http_state['unexpected'] );
		$checks['hostile_reply_links_removed'] = '' !== (string) ( $first['reply'] ?? '' ) && false === stripos( (string) $first['reply'], 'http' );
		$checks['hostile_reply_html_removed'] = false === strpos( (string) ( $first['reply'] ?? '' ), '<' ) && false === strpos( (string) ( $first['reply'] ?? '' ), '>' );

		$session = (string) ( $first['session'] ?? '' );
		$session_id = bds_sales_assistant_verify_session( $session );
		if ( $session_id ) {
			$conversation_keys[] = 'bds_sales_conv_' . md5( $session_id );
		}
		$checks['session_token_valid'] = wp_is_uuid( $session_id );
		$tampered = substr( $session, 0, -1 ) . ( 'a' === substr( $session, -1 ) ? 'b' : 'a' );
		$checks['session_token_tamper_denied'] = '' === bds_sales_assistant_verify_session( $tampered );
		$expired = bds_sales_assistant_session_token( wp_generate_uuid4(), time() - 1 );
		$checks['session_token_expiry_denied'] = '' === bds_sales_assistant_verify_session( $expired );
		$_SERVER['REMOTE_ADDR'] = '192.0.2.78';
		$checks['session_token_client_bound'] = '' === bds_sales_assistant_verify_session( $session );
		$_SERVER['REMOTE_ADDR'] = BDS_STAGING_SALES_ACCEPTANCE_IP;

		$second = bds_staging_sales_acceptance_data( bds_staging_sales_acceptance_request( 'I also need help finding leads.', $session ) );
		$lead_offer = bds_staging_sales_acceptance_offer( $second['offers'] ?? array(), 'ai-lead-assistant' );
		$checks['conversation_continues_server_side'] = 2 === absint( $http_state['calls'] ) && 1 === absint( $http_state['continuations'] );
		$checks['second_reply_grounded'] = 'ai-lead-assistant' === (string) ( $second['offers'][0]['slug'] ?? '' )
			&& true === ( $lead_offer['for_sale'] ?? false )
			&& BDS_STAGING_SALES_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( (string) ( $lead_offer['url'] ?? '' ), PHP_URL_HOST ) )
			&& false === stripos( (string) ( $second['reply'] ?? '' ), 'http' );

		$third = bds_staging_sales_acceptance_data( bds_staging_sales_acceptance_request( 'I need social posts and content.', (string) ( $second['session'] ?? '' ) ) );
		$social_offer = bds_staging_sales_acceptance_offer( $third['offers'] ?? array(), 'ai-social-growth' );
		$checks['third_reply_grounded'] = 'ai-social-growth' === (string) ( $third['offers'][0]['slug'] ?? '' )
			&& true === ( $social_offer['for_sale'] ?? false )
			&& BDS_STAGING_SALES_ACCEPTANCE_HOST === strtolower( (string) wp_parse_url( (string) ( $social_offer['url'] ?? '' ), PHP_URL_HOST ) )
			&& false === stripos( (string) ( $third['reply'] ?? '' ), 'http' );
		$calls_before_cap = absint( $http_state['calls'] );
		$fourth = bds_staging_sales_acceptance_request( 'This fixed request must be stopped by the daily cap.', (string) ( $third['session'] ?? '' ) );
		$checks['daily_cap_denies_fourth'] = is_wp_error( $fourth ) && 'rate_limited' === $fourth->get_error_code();
		$checks['daily_cap_stops_provider_call'] = $calls_before_cap === absint( $http_state['calls'] );

		set_transient( $rate_key, 10, MINUTE_IN_SECONDS );
		$checks['client_minute_cap_denies'] = false === bds_sales_assistant_rate_limit();
		$checks['exact_provider_call_count'] = 3 === absint( $http_state['calls'] ) && 2 === absint( $http_state['continuations'] );
		$checks['provider_traffic_fully_intercepted'] = 0 === absint( $http_state['unexpected'] );
		$checks['no_real_provider_or_customer_mutation'] = 3 === absint( $http_state['calls'] )
			&& 0 === absint( $http_state['unexpected'] )
			&& 0 === get_current_user_id();
	} catch ( Throwable $exception ) {
		$errors[] = substr( sanitize_key( $exception->getMessage() ), 0, 80 );
	} finally {
		$http_state['active'] = false;
		if ( $rate_key ) {
			if ( false === $rate_before ) {
				delete_transient( $rate_key );
			} else {
				set_transient( $rate_key, $rate_before, MINUTE_IN_SECONDS );
			}
		}
		if ( $daily_existed ) {
			update_option( $daily_key, $daily_before, false );
		} else {
			delete_option( $daily_key );
		}
		$checks['rate_state_restored'] = $rate_key
			&& ( false === $rate_before ? false === get_transient( $rate_key ) : get_transient( $rate_key ) === $rate_before )
			&& ( $daily_existed ? get_option( $daily_key, null ) === $daily_before : null === get_option( $daily_key, null ) );

		$conversation_clean = true;
		foreach ( array_unique( $conversation_keys ) as $conversation_key ) {
			delete_transient( $conversation_key );
			$conversation_clean = false === get_transient( $conversation_key ) && $conversation_clean;
		}
		$checks['conversation_state_cleaned'] = $conversation_clean;

		foreach ( $server_keys as $key ) {
			if ( $server_existed[ $key ] ) {
				$_SERVER[ $key ] = $server_before[ $key ];
			} else {
				unset( $_SERVER[ $key ] );
			}
		}
		$checks['server_state_restored'] = true;
		foreach ( $server_keys as $key ) {
			$checks['server_state_restored'] = $checks['server_state_restored']
				&& ( $server_existed[ $key ] ? ( $_SERVER[ $key ] ?? null ) === $server_before[ $key ] : ! array_key_exists( $key, $_SERVER ) );
		}
		wp_set_current_user( $original_user );
		$checks['original_context_restored'] = get_current_user_id() === $original_user;
		delete_option( $lock );
	}

	$complete = 31 === count( $checks ) && 31 === count( array_filter( $checks ) ) && empty( $errors );
	bds_staging_sales_acceptance_save_report( $complete, $checks, $errors );
}
add_action( 'init', 'bds_staging_sales_acceptance_run', 120 );

function bds_staging_sales_acceptance_report() {
	$report = get_option( BDS_STAGING_SALES_ACCEPTANCE_REPORT, array() );
	return rest_ensure_response( is_array( $report ) ? $report : array() );
}

function bds_staging_sales_acceptance_register_route() {
	if ( ! bds_staging_sales_acceptance_is_host() ) {
		return;
	}
	register_rest_route(
		'branddad/v1',
		'/staging/sales-assistant-acceptance',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_staging_sales_acceptance_report',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bds_staging_sales_acceptance_register_route' );
