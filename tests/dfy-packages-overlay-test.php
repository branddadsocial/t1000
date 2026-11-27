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
	'overlay version'        => false !== strpos( $html, 'data-bds-dfy-packages="1.2.1"' ),
	'base without promotion' => false !== strpos( $html, 'Done-for-You Complete Management' ) && false !== strpos( $html, '$399/month' ) && false !== strpos( $html, 'No paid promotion or media spend is included or required.' ),
	'starter package'        => false !== strpos( $html, 'Complete Management + Promotion Starter' ),
	'growth package'         => false !== strpos( $html, 'Complete Management + Promotion Growth' ),
	'pro package'            => false !== strpos( $html, 'Complete Management + Promotion Pro' ),
	'prices'                 => false !== strpos( $html, '$599/month total' ) && false !== strpos( $html, '$699/month total' ) && false !== strpos( $html, '$849/month total' ),
	'promotion caps'         => false !== strpos( $html, 'up to 4 customer-approved' ) && false !== strpos( $html, 'up to 8 customer-approved' ) && false !== strpos( $html, 'up to 12 customer-approved' ),
	'linkedin'               => false !== strpos( $html, 'LinkedIn content support' ),
	'complete scope'         => false !== strpos( $html, 'Monthly plan and content calendar' ) && false !== strpos( $html, 'Approved replies to comments and direct messages' ) && false !== strpos( $html, 'Business-email reply and lead follow-up support' ) && false !== strpos( $html, 'Website chatbot knowledge updates' ) && false !== strpos( $html, 'Lead capture, qualification, follow-up' ) && false !== strpos( $html, 'Professional review-response assistance' ) && false !== strpos( $html, 'Website monitoring and a basic monthly SEO check' ),
	'scope conditional'      => false !== strpos( $html, 'supported channels, account connections, approval rules, and usage limits in writing before work starts' ),
	'optional upgrade'       => false !== strpos( $html, 'Optional official promotion management' ) && false !== strpos( $html, 'Promotion is optional—not required to use Complete Management.' ),
	'official channels'      => false !== strpos( $html, 'supported official platforms' ) && false !== strpos( $html, 'documented creator campaigns' ),
	'costs separated'        => false !== strpos( $html, 'advertising spend and creator fees are separate' ),
	'honest reporting'       => false !== strpos( $html, 'organic reach and impressions' ) && false !== strpos( $html, 'paid reach, impressions, clicks and spend' ) && false !== strpos( $html, 'Pending or unverified activity is not counted as completed.' ),
	'contact only'           => 5 === substr_count( $html, 'https://branddad.social/contact-us/' ) && false === strpos( $html, 'add-to-cart' ),
	'supplier details private' => 0 === preg_match( '/8751|7128|6818|10320|10118|6367|6118|10144|5922|10119/', $html ),
	'no panel branding'      => false === stripos( $html, 'BDS Network' ) && false === stripos( $html, 'JustAnotherPanel' ),
	'no old section'         => false === strpos( $html, 'Old package section' ),
	'following content kept' => false !== strpos( $html, '<section id="after">After</section>' ),
);

$failed = array_keys( array_filter( $checks, static function ( $passed ) { return ! $passed; } ) );
if ( $failed ) {
	fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

echo 'Done-for-You overlay checks passed: ' . count( $checks ) . PHP_EOL;
