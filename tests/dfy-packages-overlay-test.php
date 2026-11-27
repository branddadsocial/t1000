<?php

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['bds_test_filters'] = array();

function home_url( $path = '' ) {
	return 'https://branddad.social' . $path;
}
function esc_url( $value ) {
	return $value;
}
function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function add_filter( $hook, $callback, $priority ) {
	$GLOBALS['bds_test_filters'][ $hook ][ $priority ] = $callback;
}
function remove_filter( $hook, $callback, $priority ) {
	unset( $GLOBALS['bds_test_filters'][ $hook ][ $priority ] );
}
function add_action() {}
function bds_fp_filter_content( $content ) {
	return '<main>' . $content . '<section class="bds-fp-section" id="bds-fp-business-autopilot"><h2>Old package section</h2></section><section id="after">After</section></main>';
}

require dirname( __DIR__ ) . '/deploy/zzzz-bds-dfy-packages-mu.php';

$html   = bds_dfy_packages_filter( '<p>Before</p>' );
$checks = array(
	'overlay version'        => false !== strpos( $html, 'data-bds-dfy-packages="1.1.0"' ),
	'base without engagement' => false !== strpos( $html, 'Done-for-You Complete Management' ) && false !== strpos( $html, '$399/month' ) && false !== strpos( $html, 'No BDS Network engagement is included or required.' ),
	'essential package'      => false !== strpos( $html, 'Complete Management + Essential Visibility' ),
	'complete package'       => false !== strpos( $html, 'Complete Management + Growth Visibility' ),
	'maximum package'        => false !== strpos( $html, 'Complete Management + Maximum Visibility' ),
	'prices'                 => false !== strpos( $html, '$599/month total' ) && false !== strpos( $html, '$699/month total' ) && false !== strpos( $html, '$849/month total' ),
	'post caps'              => false !== strpos( $html, 'up to 12 eligible posts' ) && false !== strpos( $html, 'up to 20 eligible posts' ) && false !== strpos( $html, 'up to 30 approved posts total' ),
	'regions'                => false !== strpos( $html, 'US Focused' ) && false !== strpos( $html, 'Worldwide' ),
	'linkedin'               => false !== strpos( $html, 'LinkedIn content support' ),
	'optional upgrade'       => false !== strpos( $html, 'Optional BDS Network visibility upgrades' ) && false !== strpos( $html, 'You do not have to buy BDS Network engagement.' ),
	'honest reporting'       => false !== strpos( $html, 'campaign-assisted reach' ) && false !== strpos( $html, 'Pending or unverified activity is not counted as completed.' ),
	'contact only'           => 5 === substr_count( $html, 'https://branddad.social/contact-us/' ) && false === strpos( $html, 'add-to-cart' ),
	'supplier details private' => 0 === preg_match( '/8751|7128|6818|10320|10118|6367|6118|10144|5922|10119/', $html ),
	'customer wording'       => false === stripos( $html, 'accounts' ),
	'no old section'         => false === strpos( $html, 'Old package section' ),
	'following content kept' => false !== strpos( $html, '<section id="after">After</section>' ),
);

$failed = array_keys( array_filter( $checks, static function ( $passed ) { return ! $passed; } ) );
if ( $failed ) {
	fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

echo 'Done-for-You overlay checks passed: ' . count( $checks ) . PHP_EOL;
