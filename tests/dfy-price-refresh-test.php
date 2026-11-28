<?php

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['bds_test_filters'] = array();

function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function add_filter( $hook, $callback, $priority ) { $GLOBALS['bds_test_filters'][ $hook ][ $priority ] = $callback; }
function remove_filter( $hook, $callback, $priority ) { unset( $GLOBALS['bds_test_filters'][ $hook ][ $priority ] ); }
function add_action() {}
function bds_fp_filter_content( $content ) {
	return '<main>' . $content . '<section class="bds-fp-section" id="bds-fp-business-autopilot">'
		. '<article><h3>Complete Management + Promotion Starter</h3><p class="bds-fp-price">$599/month total</p></article>'
		. '<article><h3>Complete Management + Promotion Growth</h3><p class="bds-fp-price">$699/month total</p></article>'
		. '<article><h3>Complete Management + Promotion Pro</h3><p class="bds-fp-price">$849/month total</p></article>'
		. '</section><section id="after">After</section></main>';
}

require dirname( __DIR__ ) . '/deploy/zzzzzzzz-bds-dfy-price-refresh-mu.php';

$html = bds_dfy_price_refresh_filter( '<p>Before</p>' );
$checks = array(
	'version marker'          => false !== strpos( $html, 'data-bds-dfy-price-refresh="1.0.0"' ),
	'approved prices'         => false !== strpos( $html, '$449/month total' ) && false !== strpos( $html, '$499/month total' ) && false !== strpos( $html, '$599/month total' ),
	'old prices absent'       => false === strpos( $html, '$699/month total' ) && false === strpos( $html, '$849/month total' ),
	'base content preserved'  => false !== strpos( $html, '<p>Before</p>' ),
	'following section kept'  => false !== strpos( $html, '<section id="after">After</section>' ),
	'no checkout surface'     => false === strpos( $html, 'add-to-cart' ) && false === strpos( $html, 'checkout' ),
	'no panel identifiers'    => 0 === preg_match( '/8751|7128|6818|10118|6367|6118|10144|5922|10119/', $html ),
	'filter remains singular' => isset( $GLOBALS['bds_test_filters']['the_content'][ PHP_INT_MAX ] ) && 'bds_dfy_price_refresh_filter' === $GLOBALS['bds_test_filters']['the_content'][ PHP_INT_MAX ],
);
foreach ( $checks as $label => $ok ) {
	echo ( $ok ? 'PASS: ' : 'FAIL: ' ) . $label . PHP_EOL;
	if ( ! $ok ) { exit( 1 ); }
}
echo 'Done-for-You price refresh tests passed.' . PHP_EOL;
