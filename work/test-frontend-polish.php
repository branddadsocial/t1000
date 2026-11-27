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

$_SERVER['HTTP_HOST'] = 'branddad.social';
require dirname( __DIR__ ) . '/deploy/zzz-bds-frontend-polish-mu.php';

$page = bds_fp_services_content();
$checks = array(
	'single_h1'    => 1 === substr_count( strtolower( $page ), '<h1' ),
	'plain_brand'  => false === stripos( $page, 'FormWise' ),
	'has_plans'    => false !== strpos( $page, 'AI Business Essentials' ),
	'has_special'  => false !== strpos( $page, 'AI Review Dispute Assistant' ),
	'has_directory'=> false !== strpos( $page, 'List my business free' ),
	'has_mobile_cta'=> false !== strpos( $page, 'data-bds-open-assistant' ),
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

