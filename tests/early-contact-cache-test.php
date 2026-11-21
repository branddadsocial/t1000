<?php
define( 'ABSPATH', __DIR__ );
function is_admin() { return true; }
require 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/aaa-bd-eco-early-html.php';

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

assert_same( false, bd_eco_early_html_path_ok( '/contact-us/' ), 'contact-us is not early-cached' );
assert_same( false, bd_eco_early_html_path_ok( '/contact/' ), 'contact alias is not early-cached' );
assert_same( true, bd_eco_early_html_path_ok( '/help/' ), 'help can stay early-cached' );
assert_same( true, bd_eco_early_html_path_ok( '/' ), 'home can stay early-cached' );
$src = file_get_contents( 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bd-ecosystem-perf-mu.php' );
assert_same( '1.1.61', BD_ECO_EARLY_HTML_VER, 'early html version' );
assert_same( 'prod1119convh1', BD_ECO_HTML_CACHE_GEN, 'homepage cache generation' );
assert_same( true, false !== strpos( $src, '1.1.64' ), 'perf writer version' );
$fat = '<html><head><style id="bqucss-used-css-inline-css">' . str_repeat( 'x', 200000 ) . '</style></head><body><h1 class="bds-wah__title">Find a business. See what it offers. Contact it.</h1></body></html>';
$slim = bd_eco_early_html_strip_bqucss( $fat );
assert_same( true, false === strpos( $slim, 'bqucss-used-css-inline-css' ), 'unused CSS dumps are stripped without PCRE' );
assert_same( true, false !== strpos( $slim, 'Find a business. See what it offers. Contact it.' ), 'the conversion heading stays after the CSS dump is stripped' );
assert_same( true, false === strpos( $src, 'select-your-plan|help|contact-us|claim-your-business-listing-branddad-directory' ), 'perf no longer caches contact-us' );
$ok_html = '<html><body><h1 class="bds-wah__title" data-bds-copy-en="Find a business. See what it offers. Contact it." data-bds-copy-es="Encuentre un negocio. Vea qué ofrece. Contáctelo.">Find a business. See what it offers. Contact it.</h1><p data-bds-copy="es">Listarlo es gratis. Lo revisamos antes de publicarlo.</p><div id="bds-lang-switch"></div><div data-bds-front-compose="1.5.35"></div></body></html>';
assert_same( true, bd_eco_early_html_home_cache_ok( $ok_html ), 'tagged Spanish homepage HTML can be cached' );
$mix = str_replace( 'data-bds-copy="es"', '', str_replace( 'data-bds-copy-es="Encuentre un negocio. Vea qué ofrece. Contáctelo."', '', $ok_html ) );
assert_same( false, bd_eco_early_html_home_cache_ok( $mix ), 'untagged leftover Spanish is refused' );
$old = str_replace( '1.5.35', '1.5.34', $ok_html );
assert_same( false, bd_eco_early_html_home_cache_ok( $old ), 'compose below 1.5.35 is refused' );
$nosw = str_replace( 'id="bds-lang-switch"', 'id="bd-lc-root"', $ok_html );
assert_same( false, bd_eco_early_html_home_cache_ok( $nosw ), 'homepage cache without the live switcher is refused' );
echo "early contact cache ok\n";
