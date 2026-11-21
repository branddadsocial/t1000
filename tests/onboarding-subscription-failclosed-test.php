<?php
/**
 * Onboarding website URLs are stored fail-closed and never fetched.
 * Unpaid or mismatched orders do not map onto a subscription.
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

function add_action() {}
function add_filter() {}
function add_shortcode() {}
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
function is_user_logged_in() {
	return false;
}
function is_admin() {
	return false;
}
function wp_doing_ajax() {
	return false;
}
function wp_is_uuid( $s ) {
	return (bool) preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', (string) $s );
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

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-onboarding-api-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-subscriptions-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-execution-mu.php';

$failed = 0;
function onboard_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

onboard_expect( '' === bds_onboarding_public_website_url( 'file:///etc/passwd' ), 'file URLs are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'javascript:alert(1)' ), 'javascript URLs are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'http://127.0.0.1/' ), 'loopback IPs are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'http://10.0.0.8/' ), 'RFC1918 IPs are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'http://169.254.169.254/latest/meta-data/' ), 'metadata IPs are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'https://8.8.8.8/' ), 'literal public IPs are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'https://user:pass@branddad.social/' ), 'URLs with credentials are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'http://example.com:8080/' ), 'non-default ports are refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'http://localhost/' ), 'localhost is refused' );
onboard_expect( '' === bds_onboarding_public_website_url( 'http://intranet.internal/' ), '.internal hosts are refused' );
onboard_expect( 'https://branddad.social/' === bds_onboarding_public_website_url( 'https://branddad.social/' ), 'a public BrandDad HTTPS URL is stored' );

$bad = bds_onboarding_validate_profile( array( 'website_url' => 'http://127.0.0.1/' ) );
onboard_expect( is_wp_error( $bad ) && 'invalid_website' === $bad->get_error_code(), 'loopback onboarding websites are 422' );
onboard_expect( 422 === (int) ( $bad->get_error_data()['status'] ?? 0 ), 'invalid website status is 422' );
$ok = bds_onboarding_validate_profile( array( 'website_url' => 'https://branddad.social/' ) );
onboard_expect( is_array( $ok ) && 'https://branddad.social/' === $ok['website_url'], 'a public website may be stored' );
$empty = bds_onboarding_validate_profile( array( 'website_url' => '' ) );
onboard_expect( is_array( $empty ) && '' === $empty['website_url'], 'an empty website is allowed' );

onboard_expect( 'listing_studio' === bds_onboarding_recommended_tool( 'listing' ), 'a listing goal still maps to listing_studio' );
onboard_expect( 'listing_studio' === bds_onboarding_recommended_for_access( 'listing', 'paid' ), 'paid access may recommend listing_studio' );
onboard_expect( 'assistant_request' === bds_onboarding_recommended_for_access( 'listing', 'trial' ), 'trial does not recommend listing_studio' );
onboard_expect( 'assistant_request' === bds_onboarding_recommended_for_access( 'listing', 'directory_free' ), 'Directory free AI does not recommend listing_studio' );
onboard_expect( 'website_plan' === bds_onboarding_recommended_for_access( 'website', 'trial' ), 'trial may recommend website_plan' );
onboard_expect( 'assistant_request' === bds_onboarding_recommended_for_access( 'website', 'directory_free' ), 'Directory free AI does not recommend website_plan' );
onboard_expect( 'social_draft' === bds_onboarding_recommended_for_access( 'social', 'trial' ), 'trial may recommend social_draft' );

$onb_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-onboarding-api-mu.php' );
onboard_expect( false === strpos( $onb_src, 'wp_remote' ) && false === strpos( $onb_src, 'wp_safe_remote' ), 'onboarding does not fetch the website URL' );
onboard_expect( '0.1.2' === BDS_ONBOARDING_API_VERSION, 'onboarding stamps 0.1.2' );
onboard_expect( false !== strpos( $onb_src, 'bds_onboarding_recommended_for_access' ), 'onboarding recommends only tools the access mode can call' );

onboard_expect( false === bds_ai_order_matches_subscription_ids( 0, 9, 100, '', 0 ), 'a missing order id does not match' );
onboard_expect( false === bds_ai_order_matches_subscription_ids( 100, 0, 100, '', 0 ), 'a missing subscription id does not match' );
onboard_expect( true === bds_ai_order_matches_subscription_ids( 100, 9, 100, '', 0 ), 'the parent order matches its subscription' );
onboard_expect( false === bds_ai_order_matches_subscription_ids( 200, 9, 100, '', 0 ), 'a different order does not match the parent' );
onboard_expect( true === bds_ai_order_matches_subscription_ids( 300, 9, 100, 'yes', 9 ), 'an authoritative renewal matches its subscription' );
onboard_expect( false === bds_ai_order_matches_subscription_ids( 300, 9, 100, 'yes', 8 ), 'a renewal tagged to another subscription does not match' );
onboard_expect( false === bds_ai_order_matches_subscription_ids( 300, 9, 100, '', 9 ), 'a non-renewal stray order does not match' );

onboard_expect( 0 === bds_ai_paid_order_id_or_zero( 100, false, true ), 'an unpaid matching order is not the current paid order' );
onboard_expect( 0 === bds_ai_paid_order_id_or_zero( 100, true, false ), 'a paid mismatched order is not the current paid order' );
onboard_expect( 0 === bds_ai_paid_order_id_or_zero( 0, true, true ), 'a missing order id is not the current paid order' );
onboard_expect( 100 === bds_ai_paid_order_id_or_zero( 100, true, true ), 'a paid matching order may be the current paid order' );
onboard_expect( '0.5.2' === BDS_AI_SUBSCRIPTIONS_VERSION, 'subscriptions stamp 0.5.2' );
onboard_expect( ! defined( 'BDS_FORMWISE_PROVISIONING_KEY' ), 'tests do not invent a FormWise provisioning key' );
onboard_expect( ! defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ), 'tests do not enable entitlements' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "onboarding subscription fail-closed tests passed\n";
