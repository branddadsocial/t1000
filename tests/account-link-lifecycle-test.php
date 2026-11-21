<?php
/**
 * Account-link stays 503 without a secret. Lifecycle records payment only after is_paid().
 */
define( 'ABSPATH', __DIR__ );

class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code, $message = '', $data = array() ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
	public function get_error_code() {
		return $this->code;
	}
	public function get_error_message() {
		return $this->message;
	}
}
class WP_User {
	public $ID           = 7;
	public $user_email   = 'owner@example.com';
	public $display_name = 'Owner';
}

function add_action() {}
function add_filter() {}
function apply_filters( $tag, $value, ...$args ) {
	return $value;
}
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}
function absint( $n ) {
	return abs( (int) $n );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_text_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function sanitize_email( $s ) {
	return filter_var( (string) $s, FILTER_SANITIZE_EMAIL );
}
function is_email( $s ) {
	return (bool) filter_var( (string) $s, FILTER_VALIDATE_EMAIL );
}
function home_url( $path = '' ) {
	return 'https://branddad.social' . $path;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function get_user_by( $field, $value ) {
	$user = new WP_User();
	return $user;
}
function is_user_logged_in() {
	return true;
}
function wp_get_current_user() {
	return new WP_User();
}
function current_user_can() {
	return false;
}
function register_post_type() {}
function admin_url( $path = '' ) {
	return 'https://branddad.social/wp-admin/' . ltrim( (string) $path, '/' );
}
function esc_url( $s ) {
	return $s;
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function wp_nonce_field() {
	return '';
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-account-link-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-lifecycle-events-mu.php';

$failed = 0;
function link_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

link_expect( ! defined( 'BDS_ACCOUNT_LINK_SECRET' ), 'tests do not invent an account-link secret' );
link_expect( false === bds_account_link_secret_ready(), 'an empty secret keeps account-link closed' );
$token = bds_account_link_create_token( 7, 'directory.branddad.social' );
link_expect( is_wp_error( $token ) && 'link_unavailable' === $token->get_error_code(), 'create_token without a secret is link_unavailable' );
link_expect( 503 === bds_account_link_token_http_status( $token ), 'a missing secret maps to HTTP 503' );
link_expect( 200 === bds_account_link_token_http_status( 'body.sig' ), 'a non-error token is not a 503' );
$verified = bds_account_link_verify_token( 'aaa.bbb' );
link_expect( is_wp_error( $verified ), 'verify_token without a secret is refused' );
link_expect( '' === bds_account_link_form( 'directory.branddad.social' ), 'the handoff form is hidden without a secret' );
link_expect( '0.1.3' === BDS_ACCOUNT_LINK_VERSION, 'account-link stamps 0.1.3' );

link_expect( false === bds_lifecycle_paid_order_may_confirm( false ), 'an unpaid order cannot confirm revenue' );
link_expect( true === bds_lifecycle_paid_order_may_confirm( true ), 'a Woo is_paid order may confirm revenue' );
link_expect( false === bds_lifecycle_featured_paid_may_confirm( false, 'local' ), 'unpaid Featured cannot confirm revenue' );
link_expect( false === bds_lifecycle_featured_paid_may_confirm( true, 'pro' ), 'a non-Featured lane cannot confirm Featured revenue' );
link_expect( true === bds_lifecycle_featured_paid_may_confirm( true, 'global' ), 'paid Global Featured may confirm revenue' );
link_expect( false === bds_lifecycle_refund_may_record( false, true ), 'a refund without a confirmed payment is not recorded' );
link_expect( false === bds_lifecycle_refund_may_record( true, false ), 'a non-refund object is not recorded as a refund' );
link_expect( true === bds_lifecycle_refund_may_record( true, true ), 'a refund after payment_confirmed may be recorded' );
link_expect( 2999 === bds_lifecycle_minor_units( '29.99' ), 'Essentials $29.99 maps to 2999 minor units' );
link_expect( 999 === bds_lifecycle_minor_units( '9.99' ), 'Local Featured $9.99 maps to 999 minor units' );
link_expect( in_array( 'payment_confirmed', bds_lifecycle_event_types(), true ), 'payment_confirmed is a lifecycle type' );
link_expect( in_array( 'trial_started', bds_lifecycle_event_types(), true ), 'trial_started is distinct from payment_confirmed' );
link_expect( '0.4.2' === BDS_LIFECYCLE_EVENTS_VERSION, 'lifecycle events stamp 0.4.2' );

$link_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-account-link-mu.php' );
$life_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-lifecycle-events-mu.php' );
link_expect( false === strpos( $link_src, 'sk_live' ) && false === strpos( $link_src, 'pk_live' ), 'account-link does not hardcode a Stripe key' );
link_expect( false === strpos( $life_src, 'sk_live' ) && false === strpos( $life_src, 'BDS_FORMWISE' ), 'lifecycle does not hardcode Stripe or FormWise keys' );
link_expect( false === strpos( $life_src, 'wp_mail' ), 'lifecycle does not send mail' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "account-link lifecycle tests passed\n";
