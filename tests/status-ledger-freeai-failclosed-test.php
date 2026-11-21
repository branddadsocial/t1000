<?php
/**
 * Customer status, provider-cost ledger, and Directory free AI stay fail-closed.
 * Does not enable flags, invent FormWise keys, or charge.
 */
define( 'ABSPATH', __DIR__ );
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 31536000 );
}

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
}

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_rest_route() {}
function register_post_type() {}
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
function is_user_logged_in() {
	return false;
}
function current_user_can() {
	return false;
}
function esc_url_raw( $url, $protocols = null ) {
	return (string) $url;
}

function bds_ai_commercial_catalog() {
	return array( 'ai-business-essentials' => array( 'price' => 29.99 ) );
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-customer-status-api-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-provider-cost-ledger-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-directory-free-ai-mu.php';

$failed = 0;
function status_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

status_expect( 7 === bds_customer_status_subject_user_id( 7, 99 ), 'status ignores a requested user id' );
status_expect( 0 === bds_customer_status_subject_user_id( 0, 99 ), 'an anonymous session has no status subject' );
status_expect( 'none' === bds_customer_status_access_mode( array(), 0 ), 'empty facts grant no access mode' );
status_expect( 'directory_free' === bds_customer_status_access_mode( array(), 12 ), 'a listing without a plan is directory_free, not paid' );
status_expect( 'paid' === bds_customer_status_access_mode( array( 'slug' => 'ai-business-essentials' ), 12 ), 'a paid plan wins over a listing' );
status_expect( 'trial' === bds_customer_status_access_mode( array( 'access_mode' => 'trial' ), 0 ), 'trial mode is copied from plan facts' );
status_expect( false === bds_customer_status_email_verified( 7 ), 'email verification defaults false' );

$period_bad = bds_customer_value_period( 'not-a-month' );
status_expect( is_wp_error( $period_bad ) && 'invalid_period' === $period_bad->get_error_code(), 'a malformed value period is 422' );
$period_old = bds_customer_value_period( '2019-01' );
status_expect( is_wp_error( $period_old ) && 'period_unavailable' === $period_old->get_error_code(), 'a period older than 24 months is refused' );
$period_future = bds_customer_value_period( '2099-01' );
status_expect( is_wp_error( $period_future ) && 'period_unavailable' === $period_future->get_error_code(), 'a future value period is refused' );
status_expect( gmdate( 'Y-m' ) === bds_customer_value_period( '' ), 'an empty period uses the current UTC month' );

$cost = bds_customer_value_cost_tracking( 75 );
status_expect( 75 === $cost['provider_units'] && 'unavailable' === $cost['provider_cost'], 'credits are never converted into a provider dollar amount' );
$metrics = bds_customer_value_unavailable_metrics( false );
status_expect( in_array( 'revenue_generated', $metrics, true ), 'revenue is never inferred' );
status_expect( in_array( 'search_rankings', $metrics, true ), 'rankings are never inferred' );
status_expect( in_array( 'leads_captured', $metrics, true ), 'leads stay unavailable without the inquiry helper' );

$status = bds_customer_status_response( 7 );
status_expect( 7 === $status['account']['user_id'], 'status reports the session user' );
status_expect( false === $status['account']['email_verified'], 'status does not claim a verified email' );
status_expect( 'none' === $status['access']['mode'], 'status without a plan or listing is none' );
status_expect( '' === $status['access']['plan_slug'], 'status does not invent a plan slug' );
$blob = strtolower( json_encode( $status ) );
status_expect( false === strpos( $blob, 'formwise' ) && false === strpos( $blob, 'remote_id' ), 'status does not leak provider ids' );
status_expect( '0.3.1' === BDS_CUSTOMER_STATUS_API_VERSION, 'customer status stamps 0.3.1' );

$now = 1700000000;
status_expect( true === bds_provider_cost_event_may_record( 'formwise', true, 'provider_reported', 'USD', 1999, $now, 'idem-1234', $now ), 'a verified cataloged cost may record' );
status_expect( false === bds_provider_cost_event_may_record( 'openai', true, 'provider_reported', 'USD', 1999, $now, 'idem-1234', $now ), 'a guessed provider does not record' );
status_expect( false === bds_provider_cost_event_may_record( 'formwise', false, 'provider_reported', 'USD', 1999, $now, 'idem-1234', $now ), 'a product missing from the catalog does not record' );
status_expect( false === bds_provider_cost_event_may_record( 'formwise', true, 'estimated', 'USD', 1999, $now, 'idem-1234', $now ), 'an estimated basis does not record' );
status_expect( false === bds_provider_cost_event_may_record( 'formwise', true, 'provider_reported', 'USD', 0, $now, 'idem-1234', $now ), 'a zero amount does not record' );
status_expect( false === bds_provider_cost_event_may_record( 'formwise', true, 'provider_reported', 'US', 1999, $now, 'idem-1234', $now ), 'a short currency does not record' );
status_expect( false === bds_provider_cost_event_may_record( 'formwise', true, 'provider_reported', 'USD', 1999, $now - ( 8 * YEAR_IN_SECONDS ), 'idem-1234', $now ), 'an 8-year-old cost does not record' );
status_expect( false === bds_provider_cost_event_may_record( 'formwise', true, 'provider_reported', 'USD', 1999, $now + 301, 'idem-1234', $now ), 'a future cost does not record' );
status_expect( 0 === bds_provider_cost_uint( -15 ), 'a negative amount is not a ledger uint' );
status_expect( 0 === bds_provider_cost_uint( 1.5 ), 'a float amount is not a ledger uint' );
status_expect( false === bds_provider_cost_catalog_has( 'not-a-catalog-slug' ), 'unknown slugs are not cataloged costs' );
status_expect( true === bds_provider_cost_catalog_has( 'ai-business-essentials' ), 'Essentials may be a cataloged cost product' );
status_expect( false === bds_provider_cost_admin_permission(), 'contribution is not public' );
status_expect( 0 === bds_provider_cost_record( array( 'provider' => 'formwise', 'product_slug' => 'ai-business-essentials', 'basis' => 'provider_reported', 'currency' => 'USD', 'amount_minor' => 0, 'occurred_at' => $now, 'idempotency' => 'idem-1234' ) ), 'record() returns 0 for a rejected event' );
status_expect( '0.1.2' === BDS_PROVIDER_COST_LEDGER_VERSION, 'cost ledger stamps 0.1.2' );
$cost_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-provider-cost-ledger-mu.php' );
status_expect( false === strpos( $cost_src, "'methods' => 'POST'" ) && false === strpos( $cost_src, '"methods" => "POST"' ), 'the cost ledger has no REST write route' );

status_expect( true === bds_dir_free_ai_facts_eligible( 'at_biz_dir', 'publish', 7, 'verified', 7, 7 ), 'a published verified owner is eligible' );
status_expect( false === bds_dir_free_ai_facts_eligible( 'at_biz_dir', 'draft', 7, 'verified', 7, 7 ), 'an unpublished listing is not eligible' );
status_expect( false === bds_dir_free_ai_facts_eligible( 'post', 'publish', 7, 'verified', 7, 7 ), 'a non-listing post is not eligible' );
status_expect( false === bds_dir_free_ai_facts_eligible( 'at_biz_dir', 'publish', 7, 'pending', 7, 7 ), 'an unapproved claim is not eligible' );
status_expect( false === bds_dir_free_ai_facts_eligible( 'at_biz_dir', 'publish', 7, 'verified', 8, 7 ), 'a listing claimed by someone else is not eligible' );
status_expect( false === bds_dir_free_ai_facts_eligible( 'at_biz_dir', 'publish', 8, 'verified', 7, 7 ), 'a listing authored by someone else is not eligible' );
status_expect( false === bds_dir_free_ai_facts_eligible( 'at_biz_dir', 'publish', 7, 'verified', 7, 0 ), 'an anonymous user is not eligible' );
status_expect( false === bds_dir_free_ai_execution_ready(), 'Directory free AI execution stays closed without a flag and key' );
status_expect( 0 === bds_dir_free_ai_remaining( 5, 3, 2 ), 'used plus pending cannot exceed the limit' );
status_expect( 2 === bds_dir_free_ai_remaining( 5, 3, 0 ), 'remaining subtracts used units' );
status_expect( false === bds_dir_free_ai_units_fit( 5, 5, 0, 1 ), 'an exhausted allowance does not fit another unit' );
status_expect( true === bds_dir_free_ai_units_fit( 5, 4, 0, 1 ), 'one remaining unit may be reserved' );
status_expect( false === bds_dir_free_ai_period_is_valid( '2026-13' ), 'month 13 is not a ledger period' );
status_expect( false === bds_dir_free_ai_period_is_valid( '9999-01' ), 'a non-20xx year is not a ledger period' );
status_expect( true === bds_dir_free_ai_period_is_valid( '2026-11' ), 'a real 20xx month is a ledger period' );
$weird_key = bds_dir_free_ai_ledger_key( 12, '9999-99' );
status_expect( false === strpos( $weird_key, '999999' ), 'a browser-supplied junk period does not become an option key' );
status_expect( false === bds_dir_free_ai_lock_key_is_owned( 'cron' ), 'unlock refuses an unrelated option key' );
status_expect( true === bds_dir_free_ai_lock_key_is_owned( 'bds_dir_free_ai_lock_12' ), 'unlock accepts the listing lock prefix' );
$allowances = bds_dir_free_ai_allowances();
status_expect( ! isset( $allowances['featured'] ) && ! isset( $allowances['travel'] ), 'free Directory AI does not grant Featured or Travel' );
status_expect( '0.1.1' === BDS_DIRECTORY_FREE_AI_VERSION, 'Directory free AI stamps 0.1.1' );

$free_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-directory-free-ai-mu.php' );
$status_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-customer-status-api-mu.php' );
status_expect( false === strpos( $free_src, 'wp_remote' ) && false === strpos( $free_src, 'wp_safe_remote' ), 'Directory free AI does not fetch a provider' );
status_expect( false === strpos( $status_src, 'wp_remote' ), 'customer status does not fetch a provider' );
status_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise execution key' );
status_expect( ! defined( 'BDS_DIRECTORY_FREE_AI_EXECUTION_ENABLED' ), 'tests do not enable Directory free AI execution' );
status_expect( ! defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ), 'tests do not enable entitlements' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "status ledger free-ai fail-closed tests passed\n";
