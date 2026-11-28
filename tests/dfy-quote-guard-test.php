<?php
define( 'ABSPATH', __DIR__ );

$dfy_actions = array();
$dfy_filters = array();
$dfy_notices = array();
$dfy_page = '';
function add_action( $hook, $callback, $priority = 10 ) { global $dfy_actions; $dfy_actions[ $hook ][ $priority ][] = $callback; }
function add_filter( $hook, $callback, $priority = 10 ) { global $dfy_filters; $dfy_filters[ $hook ][ $priority ][] = $callback; }
function sanitize_title( $value ) { return strtolower( preg_replace( '/[^a-z0-9-]+/i', '-', trim( (string) $value ) ) ); }
function absint( $value ) { return abs( (int) $value ); }
function __( $value ) { return $value; }
function esc_html__( $value ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return esc_attr( $value ); }
function esc_url( $value ) { return (string) $value; }
function home_url( $path = '/' ) { return 'https://branddad.social' . $path; }
function wc_add_notice( $message, $type ) { global $dfy_notices; $dfy_notices[] = array( $message, $type ); }
function wc_has_notice() { return false; }
function is_admin() { return false; }
function is_product() { return false; }
function is_page( $slug ) { global $dfy_page; return $slug === $dfy_page; }
function get_post_field() { return ''; }
function get_queried_object_id() { return 0; }

class BdsDfyGuardProduct {
	private $slug;
	public function __construct( $slug ) { $this->slug = $slug; }
	public function get_slug() { return $this->slug; }
}

class BdsDfyGuardCart {
	private $items;
	public function __construct( $items ) { $this->items = $items; }
	public function get_cart() { return $this->items; }
}
class BdsDfyGuardWoo {
	public $cart;
	public function __construct( $items ) { $this->cart = new BdsDfyGuardCart( $items ); }
}
$dfy_woo = null;
function WC() { global $dfy_woo; return $dfy_woo; }

function wc_get_product( $id ) {
	$map = array( 10 => 'done-for-you-ai-management', 11 => 'bds-ig-growth-us-starter', 12 => 'unrelated-product', 13 => 'variation-unrelated', 14 => 'bds-ig-growth-ww-growth', 15 => 'complete-ai-starter', 16 => 'music-original-single' );
	return isset( $map[ $id ] ) ? new BdsDfyGuardProduct( $map[ $id ] ) : null;
}

require dirname( __DIR__ ) . '/deploy/zzzzzz-bds-dfy-quote-guard-mu.php';

$failed = 0;
function dfy_guard_expect( $condition, $message ) {
	global $failed;
	if ( ! $condition ) { $failed++; fwrite( STDERR, "FAIL: {$message}\n" ); }
}

$slugs = bds_dfy_quote_guard_slugs();
dfy_guard_expect( 17 === count( $slugs ), 'exactly seventeen quote-only and retired slugs are guarded' );
dfy_guard_expect( count( $slugs ) === count( array_unique( $slugs ) ), 'guarded slugs are unique' );
dfy_guard_expect( bds_dfy_quote_guard_is_slug( 'done-for-you-ai-management' ), 'main management product is guarded' );
dfy_guard_expect( bds_dfy_quote_guard_is_slug( 'bds-ig-growth-ww-pro' ), 'retired worldwide product is guarded' );
dfy_guard_expect( bds_dfy_quote_guard_is_slug( 'complete-ai-starter' ), 'unfulfilled complete package is guarded' );
dfy_guard_expect( bds_dfy_quote_guard_is_slug( 'music-original-single' ), 'commissioned music product is guarded until scoped' );
dfy_guard_expect( ! bds_dfy_quote_guard_is_slug( 'ai-business-essentials' ), 'unrelated products remain unguarded' );
dfy_guard_expect( false === bds_dfy_quote_guard_purchasable( true, new BdsDfyGuardProduct( 'done-for-you-ai-management' ) ), 'main quote product is non-purchasable for standard and accelerated checkout' );
dfy_guard_expect( false === bds_dfy_quote_guard_purchasable( true, new BdsDfyGuardProduct( 'bds-ig-growth-us-starter' ) ), 'retired quote product is non-purchasable' );
dfy_guard_expect( false === bds_dfy_quote_guard_purchasable( true, new BdsDfyGuardProduct( 'complete-ai-starter' ) ), 'complete package cannot bypass the written-scope gate' );
dfy_guard_expect( false === bds_dfy_quote_guard_purchasable( true, new BdsDfyGuardProduct( 'music-original-single' ) ), 'commissioned music cannot bypass the written-scope gate' );
dfy_guard_expect( true === bds_dfy_quote_guard_purchasable( true, new BdsDfyGuardProduct( 'ai-business-essentials' ) ), 'unrelated products preserve purchasability' );
dfy_guard_expect( false === bds_dfy_quote_guard_purchasable( false, new BdsDfyGuardProduct( 'ai-business-essentials' ) ), 'unrelated non-purchasable state is preserved' );

dfy_guard_expect( false === bds_dfy_quote_guard_cart_validation( true, 10 ), 'main quote product cannot be added to a new public cart' );
dfy_guard_expect( false === bds_dfy_quote_guard_cart_validation( true, 11 ), 'retired growth product cannot be added to a new public cart' );
dfy_guard_expect( false === bds_dfy_quote_guard_cart_validation( true, 15 ), 'unfulfilled complete package cannot be added to a new public cart' );
dfy_guard_expect( false === bds_dfy_quote_guard_cart_validation( true, 16 ), 'commissioned music requires an approved scope before checkout' );
dfy_guard_expect( true === bds_dfy_quote_guard_cart_validation( true, 12 ), 'unrelated product retains its original validation result' );
dfy_guard_expect( false === bds_dfy_quote_guard_cart_validation( true, 10, 1, 13 ), 'unguarded variation cannot bypass a guarded parent product' );
dfy_guard_expect( false === bds_dfy_quote_guard_cart_validation( true, 12, 1, 14 ), 'guarded variation cannot bypass an unguarded parent product' );
dfy_guard_expect( 6 === count( $dfy_notices ), 'only rejected products create a customer notice' );

$dfy_woo = new BdsDfyGuardWoo( array( array( 'product_id' => 10, 'variation_id' => 0 ) ) );
bds_dfy_quote_guard_check_cart();
dfy_guard_expect( 7 === count( $dfy_notices ) && 'error' === $dfy_notices[6][1], 'stale guarded cart receives a checkout-blocking error' );
$dfy_woo = new BdsDfyGuardWoo( array( array( 'product_id' => 12, 'variation_id' => 0 ) ) );
bds_dfy_quote_guard_check_cart();
dfy_guard_expect( 7 === count( $dfy_notices ), 'unrelated stale cart is unchanged' );

$guarded_link = bds_dfy_quote_guard_loop_link( '<a>Add</a>', new BdsDfyGuardProduct( 'bds-ig-growth-us-growth' ) );
$normal_link  = bds_dfy_quote_guard_loop_link( '<a>Add</a>', new BdsDfyGuardProduct( 'ai-social-growth' ) );
dfy_guard_expect( false !== strpos( $guarded_link, '/contact-us/' ) && false !== strpos( $guarded_link, 'Request a written scope' ), 'guarded loop CTA becomes contact' );
dfy_guard_expect( '<a>Add</a>' === $normal_link, 'unrelated loop CTA is unchanged' );
dfy_guard_expect( isset( $dfy_filters['woocommerce_add_to_cart_validation'][PHP_INT_MAX] ), 'server-side cart validation is registered last' );
dfy_guard_expect( isset( $dfy_filters['woocommerce_is_purchasable'][PHP_INT_MAX] ), 'product-layer quote guard is registered last' );
dfy_guard_expect( isset( $dfy_filters['woocommerce_variation_is_purchasable'][PHP_INT_MAX] ), 'variation-layer quote guard is registered last' );
dfy_guard_expect( isset( $dfy_actions['woocommerce_check_cart_items'][PHP_INT_MAX] ), 'stale-cart checkout validation is registered last' );
dfy_guard_expect( isset( $dfy_actions['template_redirect'][1] ), 'retired public page redirect is registered early' );

$dfy_page = 'services';
ob_start();
bds_dfy_quote_guard_css();
$services_css = ob_get_clean();
dfy_guard_expect( false !== strpos( $services_css, '#bds-jap-fp' ) && false !== strpos( $services_css, 'data-bds-dfy-quote-guard="0.1.1"' ), 'services page hides only the exact retired package marker block' );
$dfy_page = 'about-us';
ob_start();
bds_dfy_quote_guard_css();
$unrelated_css = ob_get_clean();
dfy_guard_expect( '' === $unrelated_css, 'unrelated pages receive no quote-guard CSS' );

if ( $failed ) { exit( 1 ); }
echo "Done-for-You quote guard tests passed\n";
