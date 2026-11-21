<?php
/**
 * Directory membership webhook HMAC. Dummy local key only. Not a Stripe or FormWise secret.
 */
define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function home_url( $path = '' ) {
	return 'https://directory.branddad.social' . $path;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function sanitize_text_field( $s ) {
	return trim( (string) $s );
}
function absint( $n ) {
	return abs( (int) $n );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_email( $s ) {
	return filter_var( (string) $s, FILTER_SANITIZE_EMAIL );
}
function is_email( $s ) {
	return (bool) filter_var( (string) $s, FILTER_VALIDATE_EMAIL );
}
function wp_unslash( $s ) {
	return $s;
}
function wp_json_encode( $s ) {
	return json_encode( $s );
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-directory-membership-bridge-mu.php';

$failed = 0;
function sig_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$key   = 'unit-test-membership-hmac-key';
$now   = 1700000000;
$event = hash( 'sha256', 'unit-event' );
$body  = '{"event_id":"' . $event . '","email":"owner@example.com","source":"ai-business-essentials"}';
$sig   = hash_hmac( 'sha256', (string) $now . "\n" . hash( 'sha256', $body ), $key );

sig_expect( true === bds_directory_membership_verify_signature( (string) $now, $sig, $event, $body, $key, $now ), 'a matching HMAC is accepted' );
sig_expect( 'bridge_disabled' === bds_directory_membership_verify_signature( (string) $now, $sig, $event, $body, '', $now ), 'an empty key disables the bridge' );
sig_expect( 'invalid_signature' === bds_directory_membership_verify_signature( (string) ( $now - 301 ), $sig, $event, $body, $key, $now ), 'a timestamp older than 300s is rejected' );
sig_expect( 'invalid_signature' === bds_directory_membership_verify_signature( (string) $now, $sig, $event, $body . ' ', $key, $now ), 'a tampered body is rejected' );
sig_expect( 'invalid_signature' === bds_directory_membership_verify_signature( (string) $now, substr( $sig, 0, 32 ), $event, $body, $key, $now ), 'a truncated signature is rejected' );
sig_expect( 'invalid_signature' === bds_directory_membership_verify_signature( 'now', $sig, $event, $body, $key, $now ), 'a non-digit timestamp is rejected' );
sig_expect( 'invalid_signature' === bds_directory_membership_verify_signature( (string) $now, $sig, 'short', $body, $key, $now ), 'a short event id is rejected' );
sig_expect( '' === bds_directory_membership_secret(), 'missing account-link secret keeps the bridge closed' );
sig_expect( '' === bds_directory_membership_hmac_key(), 'missing secret yields no HMAC key' );
sig_expect( false === strpos( $body, 'sk_' ) && false === strpos( $body, 'pk_live' ), 'the signed body does not carry a Stripe key' );
sig_expect( false === strpos( $key, 'sk_' ) && false === strpos( $key, 'FormWise' ), 'the unit HMAC is not a live secret' );
sig_expect( '0.1.3' === BDS_DIRECTORY_MEMBERSHIP_BRIDGE_VERSION, 'membership bridge stamps 0.1.3' );
sig_expect( true === bds_directory_membership_consent_checked( '1' ), 'a checked box value is accepted' );
sig_expect( true === bds_directory_membership_consent_checked( 'yes' ), 'a yes value is accepted' );
sig_expect( false === bds_directory_membership_consent_checked( '' ), 'an unchecked box does not count as consent' );
sig_expect( false === bds_directory_membership_consent_checked( 'no' ), 'an explicit no does not count as consent' );
sig_expect( false === bds_directory_membership_may_provision( '' ), 'missing consent meta does not provision Directory' );
sig_expect( false === bds_directory_membership_may_provision( 'no' ), 'a declined checkbox does not provision Directory' );
sig_expect( true === bds_directory_membership_may_provision( 'yes' ), 'stored yes consent may provision a free Directory account' );
$src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-directory-membership-bridge-mu.php' );
sig_expect( false !== strpos( $src, 'bds_directory_membership_consent' ), 'provisioning still requires explicit checkout consent' );
sig_expect( false === strpos( $src, 'sk_live' ) && false === strpos( $src, 'pk_live' ), 'the bridge does not hardcode a Stripe key' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "webhook signature tests passed\n";
