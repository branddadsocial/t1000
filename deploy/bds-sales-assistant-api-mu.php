<?php
/**
 * Plugin Name: BrandDad Sales Assistant API
 * Description: Rate-limited public proxy for the verified BrandDad Sales Assistant and authoritative checkout offers.
 * Version: 0.1.3
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_SALES_ASSISTANT_API_VERSION', '0.1.3' );
define( 'BDS_SALES_ASSISTANT_AGENT_ID', '35ab0cc3-e2c5-456c-b4ef-2d1853ffd29c' );

function bds_sales_assistant_ready() {
	return defined( 'BDS_SALES_ASSISTANT_ENABLED' )
		&& true === BDS_SALES_ASSISTANT_ENABLED
		&& defined( 'BDS_FORMWISE_EXECUTION_KEY' )
		&& is_string( BDS_FORMWISE_EXECUTION_KEY )
		&& '' !== trim( BDS_FORMWISE_EXECUTION_KEY );
}

function bds_sales_assistant_client_key() {
	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
}

/** Reject browser requests embedded on unowned origins. Non-browser clients have no Origin header. */
function bds_sales_assistant_origin_allowed() {
	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
	if ( '' === $origin ) {
		return true;
	}
	$host = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true );
}

function bds_sales_assistant_b64url_encode( $value ) {
	return rtrim( strtr( base64_encode( (string) $value ), '+/', '-_' ), '=' );
}

function bds_sales_assistant_b64url_decode( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
		return false;
	}
	$padding = strlen( $value ) % 4;
	if ( $padding ) {
		$value .= str_repeat( '=', 4 - $padding );
	}
	return base64_decode( strtr( $value, '-_', '+/' ), true );
}

function bds_sales_assistant_rate_limit() {
	$key   = 'bds_sales_rate_' . substr( bds_sales_assistant_client_key(), 0, 32 );
	$lock_key = 'bds_sales_daily_lock';
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		if ( absint( get_option( $lock_key, 0 ) ) < time() - 10 ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return false;
		}
	}
	try {
		$count = absint( get_transient( $key ) );
		if ( $count >= 10 ) {
			return false;
		}
		$daily_key   = 'bds_sales_daily_' . gmdate( 'Ymd' );
		$daily_count = absint( get_option( $daily_key, 0 ) );
		$daily_limit = defined( 'BDS_SALES_ASSISTANT_DAILY_LIMIT' ) ? max( 1, absint( BDS_SALES_ASSISTANT_DAILY_LIMIT ) ) : 100;
		if ( $daily_count >= $daily_limit ) {
			return false;
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		update_option( $daily_key, $daily_count + 1, false );
		return true;
	} finally {
		delete_option( $lock_key );
	}
}

function bds_sales_assistant_session_token( $session_id, $expires ) {
	$body = bds_sales_assistant_b64url_encode( wp_json_encode( array( 'sid' => $session_id, 'exp' => absint( $expires ), 'purpose' => 'sales_chat', 'client' => substr( bds_sales_assistant_client_key(), 0, 32 ) ) ) );
	$sig  = bds_sales_assistant_b64url_encode( hash_hmac( 'sha256', $body, wp_salt( 'auth' ), true ) );
	return $body . '.' . $sig;
}

function bds_sales_assistant_verify_session( $token ) {
	if ( ! is_string( $token ) || strlen( $token ) > 2048 || 1 !== substr_count( $token, '.' ) ) {
		return '';
	}
	list( $body, $provided ) = explode( '.', $token, 2 );
	$expected = bds_sales_assistant_b64url_encode( hash_hmac( 'sha256', $body, wp_salt( 'auth' ), true ) );
	if ( ! hash_equals( $expected, $provided ) ) {
		return '';
	}
	$json = bds_sales_assistant_b64url_decode( $body );
	$data = is_string( $json ) ? json_decode( $json, true ) : null;
	if ( ! is_array( $data ) || 'sales_chat' !== ( $data['purpose'] ?? '' ) || ! wp_is_uuid( (string) ( $data['sid'] ?? '' ) ) || ! hash_equals( substr( bds_sales_assistant_client_key(), 0, 32 ), (string) ( $data['client'] ?? '' ) ) || absint( $data['exp'] ?? 0 ) < time() || absint( $data['exp'] ?? 0 ) > time() + 1900 ) {
		return '';
	}
	return (string) $data['sid'];
}

/** Never trust a model-provided checkout URL. */
function bds_sales_assistant_strip_links( $text ) {
	$text = preg_replace( '~https?://[^\s<>"\']+~i', '', (string) $text );
	return trim( function_exists( 'mb_substr' ) ? mb_substr( wp_strip_all_tags( $text ), 0, 8000 ) : substr( wp_strip_all_tags( $text ), 0, 8000 ) );
}

/** Return every eligible offer with a public, currently purchasable Woo product. */
function bds_sales_assistant_offers( $message ) {
	$message = strtolower( (string) $message );
	$preferred = array();
	if ( false !== strpos( $message, 'seo' ) || false !== strpos( $message, 'google' ) || false !== strpos( $message, 'local' ) ) {
		$preferred[] = 'ai-local-seo';
	} elseif ( false !== strpos( $message, 'social' ) || false !== strpos( $message, 'post' ) || false !== strpos( $message, 'content' ) ) {
		$preferred[] = 'ai-social-growth';
	} elseif ( false !== strpos( $message, 'lead' ) || false !== strpos( $message, 'customer' ) || false !== strpos( $message, 'inquir' ) ) {
		$preferred[] = 'ai-lead-assistant';
	} elseif ( false !== strpos( $message, 'write' ) || false !== strpos( $message, 'copy' ) || false !== strpos( $message, 'review' ) ) {
		$preferred[] = 'branddad-ai-studio-starter';
	} elseif ( false !== strpos( $message, 'linkedin' ) ) {
		$preferred[] = 'ai-linkedin-growth';
	} elseif ( false !== strpos( $message, 'chatbot' ) || false !== strpos( $message, 'chat' ) ) {
		$preferred[] = 'ai-website-chatbot';
	} elseif ( false !== strpos( $message, 'music' ) ) {
		$preferred[] = 'music-original-single';
	}
	$preferred[] = 'ai-business-essentials';
	$catalog     = function_exists( 'bds_ai_commercial_catalog' ) ? bds_ai_commercial_catalog() : array();
	$seen        = array();
	$out         = array();
	$push        = static function ( $slug ) use ( &$out, &$seen, $catalog ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug || isset( $seen[ $slug ] ) || ! isset( $catalog[ $slug ] ) || ! is_array( $catalog[ $slug ] ) ) {
			return;
		}
		$url     = function_exists( 'bds_ai_commercial_checkout_url' ) ? (string) bds_ai_commercial_checkout_url( $slug ) : '';
		$post    = get_page_by_path( $slug, OBJECT, 'product' );
		$product = function_exists( 'wc_get_product' ) && $post instanceof WP_Post ? wc_get_product( $post->ID ) : null;
		if ( '' === $url || ! is_object( $product ) || ! is_callable( array( $product, 'is_purchasable' ) ) || ! $product->is_purchasable() ) {
			return;
		}
		$seen[ $slug ] = true;
		$out[]         = array(
			'slug'     => $slug,
			'name'     => sanitize_text_field( $catalog[ $slug ]['name'] ?? '' ),
			'monthly'  => sanitize_text_field( $catalog[ $slug ]['monthly'] ?? '' ),
			'summary'  => sanitize_text_field( $catalog[ $slug ]['summary'] ?? '' ),
			'url'      => esc_url_raw( $url ),
			'for_sale' => true,
		);
	};
	foreach ( $preferred as $slug ) {
		$push( $slug );
	}
	foreach ( array_keys( $catalog ) as $slug ) {
		$push( $slug );
	}
	return $out;
}

function bds_sales_assistant_catalog() {
	return rest_ensure_response( array( 'offers' => bds_sales_assistant_offers( '' ) ) );
}

function bds_sales_assistant_chat( WP_REST_Request $request ) {
	if ( ! bds_sales_assistant_ready() ) {
		return new WP_Error( 'assistant_unavailable', 'The BrandDad assistant is temporarily unavailable.', array( 'status' => 503 ) );
	}
	if ( ! bds_sales_assistant_origin_allowed() ) {
		return new WP_Error( 'origin_not_allowed', 'This request origin is not allowed.', array( 'status' => 403 ) );
	}
	if ( isset( $_SERVER['CONTENT_LENGTH'] ) && absint( $_SERVER['CONTENT_LENGTH'] ) > 16384 ) {
		return new WP_Error( 'request_too_large', 'The request is too large.', array( 'status' => 413 ) );
	}
	if ( ! bds_sales_assistant_rate_limit() ) {
		return new WP_Error( 'rate_limited', 'Please wait a moment before sending another message.', array( 'status' => 429 ) );
	}
	$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
	if ( '' === $message || ( function_exists( 'mb_strlen' ) ? mb_strlen( $message ) : strlen( $message ) ) > 2000 ) {
		return new WP_Error( 'invalid_message', 'Enter a message of 2,000 characters or fewer.', array( 'status' => 422 ) );
	}
	$session_id = bds_sales_assistant_verify_session( sanitize_text_field( (string) $request->get_param( 'session' ) ) );
	if ( ! $session_id ) {
		$session_id = wp_generate_uuid4();
	}
	$conversation_id = sanitize_text_field( (string) get_transient( 'bds_sales_conv_' . md5( $session_id ) ) );
	$offers = bds_sales_assistant_offers( $message );
	$verified = array_map(
		static function ( $offer ) {
			return array( 'name' => $offer['name'], 'monthly' => $offer['monthly'], 'for_sale' => $offer['for_sale'] );
		},
		$offers
	);
	$provider_message = "Answer in simple language. Use only these server-verified offers and prices; never invent a discount, capability, result, urgency, or link. Recommend the lowest-priced suitable option. Verified offers: " . wp_json_encode( $verified ) . "\nCustomer question: " . $message;
	$body = array( 'message' => $provider_message );
	if ( $conversation_id ) {
		$body['conversation_id'] = $conversation_id;
	}
	$response = wp_safe_remote_post(
		'https://builder.formwise.ai/api/v1/agents/' . rawurlencode( BDS_SALES_ASSISTANT_AGENT_ID ) . '/conversation',
		array(
			'timeout'             => 30,
			'redirection'         => 0,
			'limit_response_size' => 65536,
			'headers'             => array( 'Authorization' => 'Bearer ' . trim( (string) BDS_FORMWISE_EXECUTION_KEY ), 'Content-Type' => 'application/json' ),
			'body'                => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) < 200 || wp_remote_retrieve_response_code( $response ) >= 300 ) {
		return new WP_Error( 'assistant_failed', 'The BrandDad assistant could not answer right now.', array( 'status' => 502 ) );
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$reply = is_array( $data ) && function_exists( 'bds_ai_exec_extract_output' ) ? bds_ai_exec_extract_output( $data ) : '';
	if ( '' === $reply ) {
		return new WP_Error( 'assistant_failed', 'The BrandDad assistant could not answer right now.', array( 'status' => 502 ) );
	}
	$remote_conversation = sanitize_text_field( (string) ( $data['conversation_id'] ?? $data['conversationId'] ?? '' ) );
	if ( $remote_conversation && strlen( $remote_conversation ) <= 128 ) {
		set_transient( 'bds_sales_conv_' . md5( $session_id ), $remote_conversation, 30 * MINUTE_IN_SECONDS );
	}
	$expires = time() + ( 30 * MINUTE_IN_SECONDS );
	return rest_ensure_response(
		array(
			'reply'   => bds_sales_assistant_strip_links( $reply ),
			'offers'  => $offers,
			'session' => bds_sales_assistant_session_token( $session_id, $expires ),
			'expires' => gmdate( DATE_ATOM, $expires ),
		)
	);
}

function bds_sales_assistant_register_routes() {
	register_rest_route( 'branddad/v1', '/sales/catalog', array( 'methods' => 'GET', 'callback' => 'bds_sales_assistant_catalog', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'branddad/v1', '/sales/chat', array( 'methods' => 'POST', 'callback' => 'bds_sales_assistant_chat', 'permission_callback' => '__return_true' ) );
}
add_action( 'rest_api_init', 'bds_sales_assistant_register_routes' );
