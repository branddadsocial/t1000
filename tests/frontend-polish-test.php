<?php
define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );

class WP_Post {
	public $post_status = 'publish';
}

function add_filter() {}
function add_action() {}
function sanitize_title( $value ) { return strtolower( preg_replace( '/[^a-z0-9-]+/i', '-', trim( (string) $value ) ) ); }
function get_page_by_path() { return new WP_Post(); }
function get_permalink( $post ) { return 'https://branddad.social/product/example/'; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function home_url( $path = '/' ) { return 'https://branddad.social' . $path; }
function is_admin() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function is_page() { return false; }
function is_front_page() { return false; }
function shortcode_exists() { return false; }
function get_theme_mod() { return 0; }
function wp_get_attachment_image_url() { return ''; }
function wp_parse_url( $url, $part = -1 ) { return parse_url( $url, $part ); }
function bds_svc_catalog() { return array( array( 'slug' => 'website-speed-optimization', 'title' => 'AI Website Improvement Plan', 'price' => 19.99, 'type' => 'simple', 'blurb' => 'A clear plan.', 'url' => 'https://branddad.social/product/branddad-smartsites-website-optimizer/' ) ); }

$_SERVER['HTTP_HOST'] = 'branddad.social';
require dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php';

$page = bds_fp_services_content();
$checks = array(
	'single_h1'    => 1 === substr_count( strtolower( $page ), '<h1' ),
	'plain_brand'  => false === stripos( $page, 'FormWise' ),
	'has_plans'    => false !== strpos( $page, 'AI Business Essentials' ),
	'has_autopilot'=> false !== strpos( $page, 'Done-for-You Essential Engagement' )
		&& false !== strpos( $page, 'Done-for-You Complete Growth' )
		&& false !== strpos( $page, 'Done-for-You Maximum Visibility' )
		&& false !== strpos( $page, 'LinkedIn content support' ),
	'has_special'  => false !== strpos( $page, 'AI Review Dispute Assistant' ),
	'has_directory'=> false !== strpos( $page, 'List my business free' ),
	'has_mobile_cta'=> false !== strpos( $page, 'data-bds-open-assistant' ),
	'website_plan_price'=> false !== strpos( $page, '$19.99 one time' ) && false !== strpos( $page, 'Buy AI plan' ),
	'website_plan_canonical_url'=> false !== strpos( $page, 'https://branddad.social/product/branddad-smartsites-website-optimizer/' )
		&& false === strpos( $page, 'https://branddad.social/product/website-speed-optimization/' ),
	'health_renderer'=> function_exists( 'bds_fp_render_health_page' ),
	'health_css'     => false !== strpos( bds_fp_health_page_css(), 'bds-fp-health-nav' ),
	'health_menu'    => false !== strpos( bds_fp_health_page_css(), 'bds-fp-health-menu' ),
	'health_shared_nav_hidden' => false !== strpos( file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php' ), 'body.bds-fp-health-page>#bds-nav-fix-bar{display:none!important}' ),
	'logo_fallback'  => 'https://branddad.social/wp-content/uploads/2023/04/3-1-1.webp' === bds_fp_social_logo_url(),
	'price_text_fix' => false !== strpos( file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php' ), '.bds-oneshot-price>span' ),
	'shared_mobile_menu' => false !== strpos( file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php' ), '#bds-nav-fix-bar .bds-nav-fix-mobile' ),
	'bounded_services' => false === strpos( file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php' ), 'calc(50% - 50vw)' )
		&& false !== strpos( file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php' ), '.bds-fp-section--soft{margin:0;' ),
	'health_helper'  => '<script>function bdsFpHealthEsc(v){return v;}bdsFpHealthEsc("ok");</script>' === bds_fp_prepare_health_widget( '<script>function bdsHealthEsc(v){return v;}bdsHealthEsc("ok");</script>' ),
);

$_SERVER['REQUEST_URI'] = '/add-listing/';
$fixed = bds_fp_directory_text_buffer( '<h1>Registration</h1><h2>Buy, Sell Discover Locally</h2>' );
$checks['directory_copy'] = false !== strpos( $fixed, '<h1>Add Your Listing</h1>' )
	&& false !== strpos( $fixed, 'Buy, Sell &amp; Discover Locally' );

foreach ( $checks as $name => $passed ) {
	echo $name . '=' . ( $passed ? 'PASS' : 'FAIL' ) . PHP_EOL;
	if ( ! $passed ) {
		exit( 1 );
	}
}



