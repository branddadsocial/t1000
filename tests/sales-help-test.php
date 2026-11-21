<?php
/**
 * Sales helper answers. No WordPress.
 */
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
function is_admin() { return false; }
require_once __DIR__ . '/../deploy/cursor/bds-sales-help-mu.php';

function assert_true( $cond, $message ) {
	if ( ! $cond ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

$cards = bds_sales_help_cards();
$blob  = strtolower( wp_json_encode( $cards ) );
assert_true( false !== strpos( $blob, '29.99' ), 'essentials price is present' );
assert_true( false === strpos( $blob, '6.99' ), 'activation fee is absent' );
assert_true( false === strpos( $blob, 'no card' ), 'card claim is absent' );
assert_true( false === strpos( $blob, 'guarantee' ), 'no guarantee wording' );
assert_true( 'listing' === bds_sales_help_match( 'I own a barbershop and need people to find the shop' ), 'shop maps to listing' );
assert_true( 'find' === bds_sales_help_match( 'I am looking for a plumber' ), 'a shopper is not sent to checkout' );
assert_true( 'trial' === bds_sales_help_match( 'Can I try the 29.99 plan?' ), 'trial maps to essentials' );
assert_true( 'person' === bds_sales_help_match( 'I need a person for custom work' ), 'custom maps to a person' );
assert_true( 'featured' === bds_sales_help_match( 'How do I get featured?' ), 'featured maps to the Featured buy page' );
assert_true( 'music' === bds_sales_help_match( 'I want original music for my shop' ), 'music maps to the brief' );
assert_true( 'gift' === bds_sales_help_match( 'Where do I buy a gift card?' ), 'gift cards stay on the marketplace' );
assert_true( false !== strpos( $cards['featured']['body'], '$9.99' ), 'featured help names the Local price' );
assert_true( false !== strpos( $cards['music']['body'], 'does not take payment' ), 'music help does not take payment' );
assert_true( false === strpos( strtolower( $cards['gift']['body'] ), 'no card' ), 'gift help does not say no card' );
assert_true( 'start' === bds_sales_help_match( 'hello there' ), 'unknown stays on the start card' );
assert_true( false !== strpos( $cards['trial']['href'], '/product/ai-business-essentials/' ), 'trial link is the essentials product' );
assert_true( false !== strpos( $cards['trial']['body'], 'Directory membership is not included' ), 'trial help does not grant Directory' );
assert_true( 'Try it free for 7 days' === $cards['trial']['label'], 'trial help uses the approved CTA' );
assert_true( 'find' === bds_sales_help_match( 'buscar un plomero cerca' ), 'a Spanish shopper is not sent to checkout' );
assert_true( 'listing' === bds_sales_help_match( 'quiero listar mi negocio' ), 'a Spanish owner is sent to the free listing' );
assert_true( 'es' === bds_sales_help_lang( 'quiero listar mi negocio' ), 'Spanish questions are recognized' );
$es = bds_sales_help_card( 'listing', 'es' );
assert_true( false !== strpos( $es['body'], '29.99' ), 'the Spanish listing answer keeps the real price' );
$es_trial = bds_sales_help_card( 'trial', 'es' );
assert_true( 'Pruébelo 7 días' === $es_trial['label'], 'Spanish trial CTA matches the approved label' );
assert_true( false !== strpos( $es_trial['body'], 'membresía del directorio no está incluida' ), 'Spanish trial help does not grant Directory' );
assert_true( false !== strpos( $es['href'], '/add-listing/' ), 'the Spanish listing answer uses the free listing' );
function esc_html( $s ) { return $s; }
function esc_url( $s ) { return $s; }
$bar = bds_sales_help_markup( $cards['start'] );
assert_true( false !== strpos( $bar, '<a ' ) && strpos( $bar, '<a ' ) < strpos( $bar, '<details>' ), 'the next step is visible before the question form' );
$_SERVER['HTTP_HOST'] = 'branddad.social';
assert_true( true === bds_sales_help_on_host(), 'the helper remains available on Social' );
$_SERVER['HTTP_HOST'] = 'www.branddad.social:443';
assert_true( true === bds_sales_help_on_host(), 'the helper remains available on www Social with a port' );
$_SERVER['HTTP_HOST'] = 'directory.branddad.social';
assert_true( false === bds_sales_help_on_host(), 'Directory renders only its contextual Concierge' );
$_SERVER['HTTP_HOST'] = 'branddad.social';
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $s ) { return (string) $s; }
}
if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl() { return true; }
}
if ( ! function_exists( 'bds_lang_pair' ) ) {
	require dirname( __DIR__ ) . '/deploy/cursor/bds-lang-switch-mu.php';
}
$bar_es = bds_sales_help_markup( $cards['start'] );
assert_true( false !== strpos( $bar_es, 'Listar mi negocio gratis' ) && false !== strpos( $bar_es, 'data-bds-copy-es=' ), 'the helper CTA Spanish is tagged' );
assert_true( false !== strpos( $bar_es, 'Ayúdeme a elegir' ), 'the helper summary Spanish is tagged' );
$help_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-sales-help-mu.php' );
assert_true( false !== strpos( $help_src, 'Version: 0.1.7' ), 'sales help stamps 0.1.7' );
assert_true( false !== strpos( $help_src, '#0668d8' ), 'sales help uses the approved accessible blue' );
assert_true( false !== strpos( $help_src, ':focus-visible' ), 'sales help keeps a visible focus ring' );
assert_true( false !== strpos( $help_src, 'prefers-reduced-motion' ), 'sticky help respects reduced motion' );

echo "sales-help ok\n";

function wp_json_encode( $value ) {
	return json_encode( $value );
}
