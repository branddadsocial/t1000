<?php
/**
 * Lead, inquiry, and monitor fail-closed: no SSRF, no fetch of user URLs, consent required, flags off.
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
	public function get_error_data() {
		return $this->data;
	}
}
class WP_REST_Request {
	public $headers = array();
	public $params  = array();
	public function get_header( $key ) {
		return $this->headers[ strtolower( (string) $key ) ] ?? '';
	}
	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
	public function get_body() {
		return '';
	}
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
function sanitize_textarea_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function is_email( $s ) {
	return (bool) filter_var( (string) $s, FILTER_VALIDATE_EMAIL );
}
function wp_unslash( $s ) {
	return $s;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function esc_url_raw( $url, $protocols = null ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	$parts = parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) ) {
		return '';
	}
	$scheme = strtolower( (string) $parts['scheme'] );
	if ( is_array( $protocols ) && ! in_array( $scheme, $protocols, true ) ) {
		return '';
	}
	return $url;
}
function is_user_logged_in() {
	return false;
}
function current_user_can() {
	return false;
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-website-monitoring-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-sales-lead-pipeline-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-customer-inquiries-mu.php';

$failed = 0;
function ssrf_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

ssrf_expect( false === bds_monitoring_public_ip( '127.0.0.1' ), 'loopback is not a public monitor target' );
ssrf_expect( false === bds_monitoring_public_ip( '10.0.0.8' ), 'RFC1918 is not a public monitor target' );
ssrf_expect( false === bds_monitoring_public_ip( '169.254.169.254' ), 'link-local metadata is not a public monitor target' );
ssrf_expect( false === bds_monitoring_public_ip( '::1' ), 'IPv6 loopback is not a public monitor target' );
ssrf_expect( true === bds_monitoring_public_ip( '8.8.8.8' ), 'a public resolver address is recognized as public' );

ssrf_expect( '' === bds_monitoring_safe_url( 'file:///etc/passwd' ), 'file URLs are refused' );
ssrf_expect( '' === bds_monitoring_safe_url( 'javascript:alert(1)' ), 'javascript URLs are refused' );
ssrf_expect( '' === bds_monitoring_safe_url( 'http://127.0.0.1/' ), 'literal loopback hosts are refused before DNS' );
ssrf_expect( '' === bds_monitoring_safe_url( 'http://169.254.169.254/latest/meta-data/' ), 'metadata IPs are refused before DNS' );
ssrf_expect( '' === bds_monitoring_safe_url( 'https://user:pass@branddad.social/' ), 'URLs with credentials are refused' );
ssrf_expect( '' === bds_monitoring_safe_url( 'http://example.com:8080/' ), 'non-default ports are refused' );
ssrf_expect( false === bds_monitoring_enabled(), 'monitoring stays off without the enable flag' );
ssrf_expect( array() === bds_monitoring_plan( 1 ), 'monitoring without a paid plan grants nothing' );

$ok_origin = new WP_REST_Request();
$ok_origin->headers['origin'] = 'https://branddad.social/help/';
ssrf_expect( true === bds_lead_origin_allowed( $ok_origin ), 'BrandDad Social origin is allowed' );
$bad_origin = new WP_REST_Request();
$bad_origin->headers['origin'] = 'https://evil.example/';
ssrf_expect( false === bds_lead_origin_allowed( $bad_origin ), 'a foreign origin is refused' );
ssrf_expect( false === bds_inquiries_owned_origin( $bad_origin ), 'inquiry capture refuses a foreign origin' );

$disabled = bds_lead_capture_permission( $ok_origin );
ssrf_expect( is_wp_error( $disabled ) && 'lead_capture_disabled' === $disabled->get_error_code(), 'lead capture is 503 when the flag is off' );
ssrf_expect( 503 === (int) ( $disabled->get_error_data()['status'] ?? 0 ), 'lead capture disabled status is 503' );

$inq_off = bds_inquiries_public_permission( $ok_origin );
ssrf_expect( is_wp_error( $inq_off ) && 'inquiries_disabled' === $inq_off->get_error_code(), 'inquiry capture is 503 when the flag is off' );

ssrf_expect( false === bds_lead_email_followup_granted( true, false ), 'consent without a verified email does not grant follow-up' );
ssrf_expect( false === bds_lead_email_followup_granted( false, true ), 'a verified email without consent does not grant follow-up' );
ssrf_expect( true === bds_lead_email_followup_granted( true, true ), 'consent plus verified email may grant follow-up storage' );

ssrf_expect( true === bds_inquiries_honeypot_tripped( 'https://127.0.0.1/ssrf' ), 'a filled honeypot website is tripped and never fetched' );
ssrf_expect( false === bds_inquiries_honeypot_tripped( '' ), 'an empty honeypot is not tripped' );
ssrf_expect( false === bds_inquiries_consent_ok( true, false ), 'inquiry without contact consent is refused' );
ssrf_expect( false === bds_inquiries_consent_ok( false, true ), 'inquiry without privacy ack is refused' );
ssrf_expect( true === bds_inquiries_consent_ok( true, true ), 'privacy plus contact consent is accepted' );

$honeypot = new WP_REST_Request();
$honeypot->params['company_website'] = 'http://127.0.0.1/';
$tripped = bds_inquiries_submit( $honeypot );
ssrf_expect( is_wp_error( $tripped ) && 422 === (int) ( $tripped->get_error_data()['status'] ?? 0 ), 'honeypot inquiry is 422 and does not fetch the URL' );

$lead_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-sales-lead-pipeline-mu.php' );
$inq_src  = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-customer-inquiries-mu.php' );
ssrf_expect( false === strpos( $lead_src, 'wp_remote' ) && false === strpos( $lead_src, 'wp_safe_remote' ), 'lead capture does not fetch a user URL' );
ssrf_expect( false === strpos( $inq_src, 'wp_remote' ) && false === strpos( $inq_src, 'wp_safe_remote' ), 'inquiry capture does not fetch a user URL' );
ssrf_expect( false === strpos( $lead_src, 'sk_live' ) && false === strpos( $inq_src, 'pk_live' ), 'lead and inquiry plugins do not hardcode Stripe keys' );
ssrf_expect( ! defined( 'BDS_LEAD_CAPTURE_ENABLED' ), 'tests do not enable lead capture' );
ssrf_expect( ! defined( 'BDS_CUSTOMER_INQUIRIES_ENABLED' ), 'tests do not enable inquiries' );
ssrf_expect( ! defined( 'BDS_WEBSITE_MONITORING_ENABLED' ), 'tests do not enable monitoring' );
ssrf_expect( '0.1.2' === BDS_SALES_LEAD_PIPELINE_VERSION, 'lead pipeline stamps 0.1.2' );
ssrf_expect( '0.1.2' === BDS_CUSTOMER_INQUIRIES_VERSION, 'inquiries stamp 0.1.2' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "ssrf consent tests passed\n";
