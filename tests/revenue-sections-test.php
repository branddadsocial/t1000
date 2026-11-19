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
function add_query_arg( $key, $val, $url ) { return $url . '?' . $key . '=' . rawurlencode( (string) $val ); }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function bds_ai_commercial_checkout_url( $slug ) {
	return 'ai-social-growth' === $slug ? 'https://branddad.social/product/ai-social-growth/' : '';
}

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
rev_expect( is_array( bds_rev_offer_by_slug( 'featured-local' ) ), 'featured inquiry slug is allowlisted' );

list( $live, $ask ) = bds_rev_split_offer_cards();
rev_expect( false !== strpos( $live, 'Subscribe now' ), 'live cards use Subscribe now' );
rev_expect( false !== strpos( $live, '/product/ai-social-growth/' ), 'live Social Growth goes to the published product' );
rev_expect( false === strpos( $live, 'Ask about this plan' ), 'live grid does not mix inquiry buttons' );
rev_expect( false !== strpos( $ask, 'Ask about this plan' ), 'closed plans stay in the inquiry grid' );
rev_expect( false !== strpos( $ask, 'ai-business-monitoring' ), 'monitoring stays inquiry' );
$pos_live = strpos( bds_rev_markup(), 'Plans you can buy now' );
$pos_ask  = strpos( bds_rev_markup(), 'Upcoming plans' );
rev_expect( false !== $pos_live && false !== $pos_ask && $pos_live < $pos_ask, 'buyable plans render before inquiry' );
rev_expect( false !== strpos( bds_rev_markup(), '/featured-advertising/' ), 'Directory featured door is present' );

$strip = bds_rev_home_strip();
rev_expect( false !== strpos( $strip, 'id="bds-rev-home-strip"' ), 'homepage strip has a stable id' );
rev_expect( false !== strpos( $strip, 'Subscribe to' ), 'homepage strip sells live checkout' );
rev_expect( false !== strpos( $strip, '/product/ai-social-growth/' ), 'homepage strip uses the published Social Growth URL' );
rev_expect( false !== strpos( $strip, '/featured-advertising/' ), 'homepage strip sells featured' );
rev_expect( false === strpos( $strip, 'Ask about this plan' ), 'homepage strip does not mix inquiry CTAs' );

$live_note = bds_rev_offer_note_html( 'ai-social-growth' );
rev_expect( false !== strpos( $live_note, 'Subscribe now' ), 'contact note sells a live plan' );
rev_expect( false !== strpos( $live_note, '/product/ai-social-growth/' ), 'contact note uses the published product URL' );
rev_expect( false === strpos( $live_note, 'does not start checkout' ), 'live contact note is not inquiry-only' );
$ask_note = bds_rev_offer_note_html( 'ai-business-monitoring' );
rev_expect( false !== strpos( $ask_note, 'does not start checkout' ), 'staged contact note stays inquiry' );
rev_expect( false === strpos( $ask_note, 'Subscribe now' ), 'staged contact note does not invent checkout' );
rev_expect( '' === bds_rev_offer_note_html( '<script>x</script>' ), 'markup offer notes print nothing' );
rev_expect(
	'https://directory.branddad.social/featured-advertising/' === bds_rev_menu_featured_url( 'Get Featured', 'https://directory.branddad.social/' ),
	'header Get Featured is sent to the Featured buy page'
);
rev_expect(
	'https://directory.branddad.social/get-featured/' === bds_rev_menu_featured_url( 'Get Featured', 'https://directory.branddad.social/get-featured/' ),
	'Verified pitch /get-featured/ is left alone'
);
rev_expect(
	'https://directory.branddad.social/' === bds_rev_menu_featured_url( 'Directory', 'https://directory.branddad.social/' ),
	'Directory browse label is not rewritten'
);

if ( $failed ) {
	fwrite( STDERR, "{$failed} revenue section tests failed\n" );
	exit( 1 );
}
echo "revenue section tests passed\n";
