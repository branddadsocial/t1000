<?php
/**
 * Airbnb one-review stays unpublished. Checkout URL is empty until a published product exists.
 */
define( 'ABSPATH', __DIR__ );
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

class WP_Post {
	public $ID;
	public $post_status = 'draft';
	public $post_name   = '';
}

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function is_admin() {
	return false;
}
function in_the_loop() {
	return true;
}
function is_main_query() {
	return true;
}
function is_front_page() {
	return false;
}
function is_page() {
	return false;
}
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $s ) {
	return $s;
}
function esc_html__( $s ) {
	return $s;
}
function esc_html_e( $s ) {
	echo esc_html( $s );
}
function __( $s ) {
	return $s;
}
function home_url( $path = '' ) {
	return 'https://branddad.social' . $path;
}
function wp_json_encode( $s ) {
	return json_encode( $s );
}
function sanitize_title( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', trim( (string) $s, '-' ) ) );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function number_format_i18n( $n ) {
	return (string) $n;
}
function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = $args[1];
	} else {
		$params = array( $args[0] => $args[1] );
		$url    = $args[2];
	}
	$q   = http_build_query( $params );
	$sep = ( false === strpos( (string) $url, '?' ) ) ? '?' : '&';
	return $url . $sep . $q;
}
function bds_po_url( $url ) {
	return $url;
}
function get_page_by_path( $path, $output = null, $type = 'page' ) {
	return null;
}
function get_permalink( $post ) {
	return '';
}
function get_post_field( $field, $id ) {
	return '';
}
function get_the_ID() {
	return 0;
}
function wc_get_product( $id ) {
	return false;
}

require dirname( __DIR__ ) . '/deploy/bds-ai-commercial-catalog-mu.php';
require dirname( __DIR__ ) . '/deploy/bds-ai-launch-pages-mu.php';

$failed = 0;
function airbnb_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$slug = 'branddad-ai-review-dispute-assistant';
airbnb_expect( null === bds_ai_commercial_offer( $slug ), 'one-review is not a commercial catalog checkout row' );
airbnb_expect( '' === bds_ai_commercial_checkout_url( $slug ), 'one-review has no catalog checkout URL' );
airbnb_expect( '' === bds_ai_launch_product_url( $slug ), 'unpublished one-review has no buy URL' );
airbnb_expect( '' === bds_ai_launch_product_page_url( $slug ), 'unpublished one-review has no product page URL' );

$home = bds_ai_launch_markup( 'home' );
airbnb_expect( false === strpos( $home, '/product/' . $slug . '/' ), 'homepage does not link checkout for unpublished one-review' );
airbnb_expect( false !== strpos( $home, 'Secure checkout is in final testing' ) || false !== strpos( $home, 'one review' ), 'homepage keeps one-review as gated copy' );
airbnb_expect( false === strpos( strtolower( $home ), 'start checkout' ), 'homepage does not start checkout for one-review' );

$launch = file_get_contents( dirname( __DIR__ ) . '/deploy/bds-ai-launch-pages-mu.php' );
airbnb_expect( false !== strpos( $launch, 'data-version="0.5.26"' ) || false !== strpos( $launch, '0.5.26' ), 'launch stamp stays 0.5.26' );
airbnb_expect( '0.5.26' === BDS_AI_LAUNCH_PAGES_VERSION, 'launch version constant stays 0.5.26' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "airbnb unpublished tests passed\n";
