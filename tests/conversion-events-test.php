<?php
/**
 * Conversion event names and the plain homepage answers.
 */
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
require_once __DIR__ . '/../deploy/cursor/bds-conversion-events-mu.php';

function assert_true( $cond, $message ) {
	if ( ! $cond ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

assert_true( bds_conv_allow( 'page_view' ), 'page view is counted' );
assert_true( bds_conv_allow( 'primary_cta_click' ), 'a main click is counted' );
assert_true( bds_conv_allow( 'sales_assistant_engaged' ), 'the helper click is counted' );
assert_true( bds_conv_allow( 'signup_started' ), 'signup start is counted' );
assert_true( ! bds_conv_allow( 'payment_completed' ), 'a browser cannot mark a payment complete' );
assert_true( ! bds_conv_allow( 'anything-else' ), 'unknown names are dropped' );

$launch = file_get_contents( __DIR__ . '/../deploy/bds-ai-launch-pages-mu.php' );
assert_true( false !== strpos( $launch, 'Is this really $29.99 a month?' ), 'the price question is on the page' );
assert_true( false !== strpos( $launch, 'A basic listing is free' ), 'the free listing answer is on the page' );
assert_true( false === strpos( strtolower( $launch ), 'no card' ), 'the page does not say no card' );
assert_true( false === strpos( $launch, '0.5.4' ), 'the old launch version is gone' );
assert_true( false !== strpos( $launch, 'List my business free' ), 'the Directory button uses the free-listing label' );
assert_true( false !== strpos( $launch, 'Start 7-day trial' ), 'the Essentials card names the real trial' );
assert_true( false !== strpos( $launch, 'Buy this service' ), 'a published specialty service shows its buy path' );
assert_true( false !== strpos( $launch, 'La ayuda empieza en $29.99 al mes' ) && false !== strpos( $launch, 'bds_lang_pair' ), 'Spanish homepage line is tagged for the switcher' );
$catalog = file_get_contents( __DIR__ . '/../deploy/bds-ai-commercial-catalog-mu.php' );
assert_true( false !== strpos( $catalog, 'Start 7-day trial' ), 'Essentials checkout button names the real trial' );
assert_true( false !== strpos( $catalog, 'Subscribe monthly' ), 'plans without a trial still say Subscribe monthly' );
$conv = file_get_contents( __DIR__ . '/../deploy/cursor/bds-conversion-events-mu.php' );
assert_true( false !== strpos( $conv, '0.1.3' ), 'conversion events keep the extra click names' );
assert_true( false !== strpos( $conv, 'bds-wah__btn' ), 'Directory hero clicks count' );
assert_true( false !== strpos( $conv, 'bds-dtd__btn--list' ), 'directory-door list clicks count' );
assert_true( false !== strpos( $conv, '/claim' ), 'claim clicks count as signup start' );
assert_true( false !== strpos( $conv, 'bds-feat-door-ctas' ), 'Directory door clicks count' );
assert_true( false !== strpos( $conv, 'bds-hub' ), 'account hub clicks count' );
assert_true( true === bds_conv_path_ok( '' ), 'the homepage may count clicks' );
assert_true( true === bds_conv_path_ok( 'services' ), 'services may count clicks' );
assert_true( true === bds_conv_path_ok( 'featured-advertising' ), 'Featured may count clicks' );
assert_true( false === bds_conv_path_ok( 'checkout' ), 'checkout does not get a second click counter' );
assert_true( false === bds_conv_path_ok( 'checkout/order-pay/12' ), 'order-pay does not get a second click counter' );
assert_true( false === bds_conv_path_ok( 'cart' ), 'cart does not get a second click counter' );
assert_true( false === bds_conv_path_ok( 'my-account/orders' ), 'My Account does not get a second click counter' );
assert_true( false === bds_conv_path_ok( 'account-hub' ), 'account hub does not get a second click counter' );
assert_true( false === bds_conv_path_ok( 'studio-boards' ), 'studio boards do not get a second click counter' );

echo "conversion-events ok\n";
