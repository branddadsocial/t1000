<?php
/**
 * Free-listing policy. Stubs the two WordPress hooks the file registers.
 */
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
require_once __DIR__ . '/../deploy/cursor/bds-directory-free-listing-mu.php';

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

assert_same( false, bds_free_list_allow( false, 'guest', 0, 0 ), 'guest stays blocked' );
assert_same( false, bds_free_list_allow( false, 'free', 0, 0 ), 'a free tier with no user stays blocked' );
assert_same( true, bds_free_list_allow( false, 'free', 5, 0 ), 'first free listing is allowed' );
assert_same( false, bds_free_list_allow( false, 'free', 5, 1 ), 'second free listing is blocked' );
assert_same( true, bds_free_list_allow( true, 'free', 5, 3 ), 'an existing paid allow is kept' );
assert_same( false, bds_free_list_allow( false, 'pro', 5, 0 ), 'pro is not granted by this policy' );
$steps = bds_free_list_steps_html( '/add-listing/' );
assert_same( true, false !== strpos( $steps, 'What happens next' ), 'add-listing shows the next steps' );
assert_same( true, false !== strpos( $steps, 'real website' ), 'add-listing asks for a real website' );
$join = bds_free_list_steps_html( 'registration' );
assert_same( true, false !== strpos( $join, 'A basic listing is free' ), 'registration says the listing is free' );
assert_same( true, false !== strpos( $join, 'Verify WhatsApp' ), 'registration tells the owner to verify WhatsApp' );
assert_same( true, false !== strpos( $join, 'min-height:44px' ), 'the signup screen buttons meet the phone tap size' );
assert_same( '', bds_free_list_steps_html( 'select-your-plan' ), 'plan pages do not get the free-listing steps' );
assert_same( '', bds_free_list_steps_html( 'https://evil.example/add-listing' ), 'a full URL is not treated as the listing form' );

echo "free-listing ok\n";
