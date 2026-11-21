<?php
/**
 * FormWise execution stays closed without a key. Follow-up mail stays off. No charge.
 */
define( 'ABSPATH', __DIR__ );
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
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
}
class WP_Post {
	public $ID;
	public $post_type   = 'bds_ai_job';
	public $post_author = 7;
}

$GLOBALS['bds_posts']         = array();
$GLOBALS['bds_meta']          = array();
$GLOBALS['bds_remote_posts']  = 0;
$GLOBALS['bds_mail_sends']    = 0;
$GLOBALS['bds_option_writes'] = 0;
$GLOBALS['bds_scheduled']     = 0;

function add_action() {}
function add_filter() {}
function add_shortcode() {}
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
function get_post( $id ) {
	return $GLOBALS['bds_posts'][ absint( $id ) ] ?? null;
}
function get_post_meta( $id, $key, $single = true ) {
	return $GLOBALS['bds_meta'][ absint( $id ) ][ (string) $key ] ?? '';
}
function add_post_meta( $id, $key, $value, $unique = false ) {
	$id  = absint( $id );
	$key = (string) $key;
	if ( $unique && isset( $GLOBALS['bds_meta'][ $id ][ $key ] ) ) {
		return false;
	}
	$GLOBALS['bds_meta'][ $id ][ $key ] = $value;
	return true;
}
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['bds_meta'][ absint( $id ) ][ (string) $key ] = $value;
	return true;
}
function add_option( $key, $value, $unused = '', $autoload = false ) {
	$GLOBALS['bds_option_writes']++;
	return true;
}
function get_option( $key, $default = false ) {
	return $default;
}
function update_option() {
	$GLOBALS['bds_option_writes']++;
	return true;
}
function delete_option() {
	return true;
}
function wp_safe_remote_post() {
	$GLOBALS['bds_remote_posts']++;
	return new WP_Error( 'forbidden', 'FormWise must not be called without a key.' );
}
function wp_remote_request() {
	$GLOBALS['bds_remote_posts']++;
	return new WP_Error( 'forbidden', 'FormWise provisioning must not be called without a key.' );
}
function wp_mail() {
	$GLOBALS['bds_mail_sends']++;
	return true;
}
function wp_next_scheduled() {
	return false;
}
function wp_schedule_event() {
	$GLOBALS['bds_scheduled']++;
	return true;
}
function esc_url_raw( $s ) {
	return $s;
}
function wp_http_validate_url() {
	return false;
}
function admin_url( $path = '' ) {
	return 'https://branddad.social/wp-admin/' . ltrim( (string) $path, '/' );
}
function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-execution-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-consent-followup-worker-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-subscriptions-mu.php';

$failed = 0;
function fw_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

fw_expect( false === bds_ai_exec_formwise_ready(), 'execution is not ready without a FormWise key' );
fw_expect( false === bds_ai_exec_job_may_call_provider( 'social_draft', array( 'message' => 'hi' ) ), 'an allowlisted tool still cannot call FormWise without a key' );
fw_expect( false === bds_ai_exec_job_may_call_provider( 'not-a-tool', array( 'message' => 'hi' ) ), 'an unknown tool cannot call FormWise' );
fw_expect( false === bds_ai_exec_job_may_call_provider( 'social_draft', 'not-array' ), 'non-array inputs cannot call FormWise' );

$job              = new WP_Post();
$job->ID          = 41;
$job->post_type   = 'bds_ai_job';
$job->post_author = 7;
$GLOBALS['bds_posts'][41] = $job;
$GLOBALS['bds_meta'][41]  = array(
	'_bds_status'           => 'pending',
	'_bds_tool'             => 'social_draft',
	'_bds_access_mode'      => 'paid',
	'_bds_listing_id'       => 0,
	'_bds_subscription_id'  => 9,
	'_bds_allowance_cycle'  => 1,
	'_bds_reservation'      => 'res-token',
	'_bds_inputs'           => '{"provide_the_source_details_":"A cafe in Playa"}',
);

bds_ai_exec_process_job( 41 );
fw_expect( 'failed' === get_post_meta( 41, '_bds_status', true ), 'process_job fails closed without a FormWise key' );
fw_expect( 'execution_not_configured' === get_post_meta( 41, '_bds_error_code', true ), 'the error code is execution_not_configured' );
fw_expect( 0 === $GLOBALS['bds_remote_posts'], 'process_job does not POST to FormWise without a key' );
fw_expect( 'pending' !== get_post_meta( 41, '_bds_status', true ), 'a pending job does not stay runnable after the closed gate' );

fw_expect( false === bds_follow_worker_ready(), 'follow-up worker is off without the enable flag and From address' );
fw_expect( false === bds_follow_worker_may_mail(), 'follow-up may not mail while the worker is off' );
fw_expect( false === bds_follow_worker_send( 'owner@example.com', 'help', str_repeat( 'ab', 24 ) ), 'send returns false while the worker is off' );
fw_expect( 0 === $GLOBALS['bds_mail_sends'], 'wp_mail is not called while the worker is off' );
bds_follow_worker_run();
fw_expect( 0 === $GLOBALS['bds_mail_sends'], 'the worker run does not send mail while off' );
bds_follow_worker_schedule();
fw_expect( 0 === $GLOBALS['bds_scheduled'], 'the worker does not schedule hourly mail while off' );
fw_expect( false === bds_follow_worker_row_eligible( array( 'consent' => 0, 'status' => 'interested', 'email' => 'owner@example.com', 'saved' => gmdate( DATE_ATOM ) ) ), 'a row without consent is not eligible' );
fw_expect( ! defined( 'BDS_FOLLOWUP_ENABLED' ), 'tests do not enable the follow-up flag' );
fw_expect( ! defined( 'BDS_FOLLOWUP_FROM_EMAIL' ), 'tests do not invent a follow-up From address' );
fw_expect( '0.1.2' === BDS_CONSENT_FOLLOWUP_VERSION, 'follow-up worker stamps 0.1.2' );

$ready = bds_ai_subscription_readiness();
fw_expect( false === $ready['formwise_key'], 'subscription readiness has no FormWise provisioning key' );
fw_expect( false === $ready['enabled'], 'tests do not enable the entitlements flag' );
fw_expect( false === $ready['ready'], 'subscription lifecycle stays closed without the provisioning key' );
fw_expect( false === bds_ai_subscription_revocation_ready(), 'revocation stays closed without the provisioning key' );
fw_expect( false === bds_ai_formwise_remove_tier( '11111111-1111-1111-1111-111111111111', '6aeeb1bb-f28a-4577-a874-6d7b514fecb4' ), 'FormWise tiers are not deleted without the provisioning key' );
fw_expect( 0 === $GLOBALS['bds_remote_posts'], 'provisioning DELETE is not sent without the key' );
fw_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise execution key' );
fw_expect( ! defined( 'BDS_FORMWISE_PROVISIONING_KEY' ), 'tests do not invent a FormWise provisioning key' );
fw_expect( ! defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ), 'tests do not enable entitlements' );
fw_expect( '0.2.3' === BDS_AI_EXECUTION_VERSION, 'execution MU stamps 0.2.3' );

$exec_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-execution-mu.php' );
$follow_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-consent-followup-worker-mu.php' );
fw_expect( false === strpos( $exec_src, 'sk_live' ) && false === strpos( $exec_src, 'pk_live' ), 'execution does not hardcode a Stripe key' );
fw_expect( false === strpos( $follow_src, 'sk_live' ), 'follow-up worker does not hardcode a Stripe key' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "formwise follow-up fail-closed tests passed\n";
