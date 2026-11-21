<?php
/**
 * Sales chat stays 503 without FormWise keys. Catalog may still list Essentials.
 */
define( 'ABSPATH', __DIR__ );
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code, $message = '', $data = array() ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
	public function get_error_code() {
		return $this->code;
	}
	public function get_error_data() {
		return $this->data;
	}
}
class WP_REST_Request {
	public $params = array();
	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
	public function get_header( $key ) {
		return '';
	}
}
class WP_Post {
	public $ID;
	public $post_status = 'publish';
	public $post_name   = '';
}
function add_action() {}
function add_filter() {}
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}
function absint( $n ) {
	return abs( (int) $n );
}
function sanitize_title( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', trim( (string) $s, '-' ) ) );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_text_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function sanitize_textarea_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function esc_url_raw( $s ) {
	return $s;
}
function wp_unslash( $s ) {
	return $s;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function get_page_by_path( $path, $output = null, $type = 'page' ) {
	$path = sanitize_title( $path );
	if ( 'ai-business-essentials' !== $path ) {
		return null;
	}
	$post              = new WP_Post();
	$post->ID          = 1;
	$post->post_status = 'publish';
	$post->post_name   = $path;
	return $post;
}
function get_permalink( $post ) {
	return 'https://branddad.social/product/' . ( is_object( $post ) ? $post->post_name : '' ) . '/';
}

require dirname( __DIR__ ) . '/deploy/bds-ai-commercial-catalog-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-sales-assistant-api-mu.php';

$failed = 0;
function chat_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

chat_expect( false === bds_sales_assistant_ready(), 'assistant is not ready without the enable flag or FormWise key' );
$err = bds_sales_assistant_chat( new WP_REST_Request() );
chat_expect( is_wp_error( $err ) && 'assistant_unavailable' === $err->get_error_code(), 'chat without keys is assistant_unavailable' );
chat_expect( 503 === (int) ( $err->get_error_data()['status'] ?? 0 ), 'chat without keys is HTTP 503' );

$_SERVER['HTTP_ORIGIN'] = 'https://evil.example';
chat_expect( false === bds_sales_assistant_origin_allowed(), 'a foreign Origin is refused' );
$_SERVER['HTTP_ORIGIN'] = 'https://branddad.social';
chat_expect( true === bds_sales_assistant_origin_allowed(), 'BrandDad Social Origin is allowed' );
unset( $_SERVER['HTTP_ORIGIN'] );

$stripped = bds_sales_assistant_strip_links( 'See https://evil.example/phish and buy' );
chat_expect( false === strpos( $stripped, 'http' ), 'model links are stripped before they reach the browser' );

chat_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise execution key' );
chat_expect( ! defined( 'BDS_SALES_ASSISTANT_ENABLED' ), 'tests do not enable the sales-assistant flag' );
$src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-sales-assistant-api-mu.php' );
chat_expect( false === strpos( $src, 'sk_live' ) && false === strpos( $src, 'pk_live' ), 'sales assistant does not hardcode a Stripe key' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "sales chat fail-closed tests passed\n";
