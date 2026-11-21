<?php
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function is_admin() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function is_front_page() { return false; }
function is_page() { return false; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return $s; }
function esc_html__( $s ) { return $s; }
function esc_html_e( $s ) { echo esc_html( $s ); }
function __( $s ) { return $s; }
function home_url( $path = '' ) { return 'https://branddad.social' . $path; }
function wp_json_encode( $s ) { return json_encode( $s ); }

require dirname( __DIR__ ) . '/deploy/bds-ai-launch-pages-mu.php';

$html = '<h1 class="entry-title">Home</h1><div class="bdai-launch" data-version="0.5.24"><h1 id="bdai-title">Your business has enough expenses. Getting help shouldn\'t cost a fortune.</h1></div>';
$out  = bds_ai_launch_strip_theme_home_heading( $html );
if ( false !== strpos( $out, '<h1 class="entry-title">Home</h1>' ) ) {
	fwrite( STDERR, "theme Home heading stayed an H1\n" );
	exit( 1 );
}
if ( false === strpos( $out, 'id="bdai-title"' ) ) {
	fwrite( STDERR, "launch headline was removed\n" );
	exit( 1 );
}
if ( false === strpos( $out, '<p class="entry-title" hidden>Home</p>' ) ) {
	fwrite( STDERR, "theme Home heading was not demoted\n" );
	exit( 1 );
}
$svc = '<h1 class="entry-title">Services</h1><div class="bdai-launch" data-version="0.5.24"><h1 id="bdai-title">Choose the help your business needs—without agency confusion.</h1></div>';
$svc_out = bds_ai_launch_strip_theme_home_heading( $svc );
if ( false !== strpos( $svc_out, '<h1 class="entry-title">Services</h1>' ) ) {
	fwrite( STDERR, "theme Services heading stayed an H1\n" );
	exit( 1 );
}
if ( false === strpos( $svc_out, '<p class="entry-title" hidden>Services</p>' ) ) {
	fwrite( STDERR, "theme Services heading was not demoted\n" );
	exit( 1 );
}
echo "launch heading ok\n";
