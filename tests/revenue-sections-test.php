<?php
/**
 * Stubs enough WordPress for the revenue MU option helper and offer allowlist.
 */
define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function is_admin() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function is_front_page() { return false; }
function is_page() { return false; }
function home_url( $path = '' ) { return 'https://branddad.social' . $path; }
function admin_url( $path = '' ) { return 'https://branddad.social/wp-admin/' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return $s; }
function esc_html__( $s ) { return $s; }
function esc_html_e( $s ) { echo esc_html( $s ); }
function __( $s ) { return $s; }
function wp_nonce_field() {}
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', trim( (string) $s, '-' ) ) ); }
function wp_unslash( $s ) { return $s; }

require dirname( __DIR__ ) . '/deploy/cursor/bds-revenue-sections-mu.php';

$failed = 0;
function rev_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$html = bds_rev_music_form( bds_rev_music_packages() );
rev_expect( false !== strpos( $html, 'value="original-single"' ), 'array packages still render option values' );
rev_expect( false !== strpos( $html, 'Original Single' ), 'array packages still render option labels' );
rev_expect( false === strpos( $html, '>Array<' ), 'package select is not the word Array' );

$html2 = bds_rev_music_form();
rev_expect( false !== strpos( $html2, 'value="ep-3"' ), 'empty args still render the catalog' );

$hit = bds_rev_offer_by_slug( 'ai-business-monitoring' );
rev_expect( is_array( $hit ) && 'AI Business Monitoring' === $hit['name'], 'known offer slug resolves' );
rev_expect( null === bds_rev_offer_by_slug( 'not-a-real-offer' ), 'unknown offer slug is rejected' );
rev_expect( null === bds_rev_offer_by_slug( '<script>x</script>' ), 'markup slug is rejected' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} revenue section tests failed\n" );
	exit( 1 );
}
echo "revenue section tests passed\n";
