<?php
/**
 * Trial and paid-access mapping. Does not enable FormWise flags or invent keys.
 */
define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function absint( $n ) {
	return abs( (int) $n );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function is_user_logged_in() {
	return false;
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-trial-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-execution-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/bds-customer-hub-lib.php';

$failed = 0;
function trial_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

trial_expect( true === bds_ai_trial_terms_are_seven_day( 7, 'day' ), 'seven days is the only matching trial interval' );
trial_expect( false === bds_ai_trial_terms_are_seven_day( 14, 'day' ), 'a 14-day interval is not the Essentials trial' );
trial_expect( false === bds_ai_trial_terms_are_seven_day( 7, 'month' ), 'a monthly interval is not the Essentials trial' );
trial_expect( false === bds_ai_trial_terms_are_seven_day( 0, 'day' ), 'zero days is not a trial' );

trial_expect( 'eligible' === bds_ai_trial_status_from_records( array(), null, array(), array() ), 'no records stay eligible' );
trial_expect( 'active' === bds_ai_trial_status_from_records( array(), array( 'end' => 1 ), array(), array() ), 'an open window is active' );
trial_expect( 'consumed' === bds_ai_trial_status_from_records( array(), null, array( 'subscription_id' => 9 ), array() ), 'history without a window is consumed' );
trial_expect( 'consumed' === bds_ai_trial_status_from_records( array(), null, array(), array( 12 ) ), 'a matched sub without a window is consumed' );
trial_expect( 'paid' === bds_ai_trial_status_from_records( array( 'slug' => 'ai-business-essentials' ), array( 'end' => 1 ), array(), array() ), 'paid Essentials wins over an open trial' );

$grants = bds_ai_trial_directory_grants();
trial_expect( false === $grants['directory_membership'], 'trial does not grant Directory membership' );
trial_expect( false === $grants['featured'], 'trial does not grant Featured' );
trial_expect( false === $grants['member_price'], 'trial does not grant the 10% member price' );
trial_expect( false === $grants['travel'], 'trial does not grant Travel search' );

$hub = bds_hub_trial_view( array( 'status' => 'active', 'active' => true, 'days_remaining' => 4 ) );
trial_expect( false !== strpos( $hub['line'], 'Directory membership is not included' ), 'hub active copy does not grant Directory' );
trial_expect( false === $hub['cta'], 'an active trial is not a second checkout CTA' );
$paid_view = bds_hub_trial_view( array( 'status' => 'paid' ) );
trial_expect( 'paid' === $paid_view['state'], 'paid Essentials is distinct from trial' );
trial_expect( false === strpos( strtolower( $paid_view['line'] ), 'directory membership is included' ), 'paid copy does not grant Directory' );

$paid = bds_ai_exec_choose_access( array( 'slug' => 'ai-business-essentials', 'credits' => 75 ), array( 'access_mode' => 'trial', 'credits' => 25 ) );
trial_expect( 'paid' === $paid['access_mode'] && 75 === $paid['credits'], 'paid entitlement wins over trial credits' );
$trial_only = bds_ai_exec_choose_access( array(), array( 'access_mode' => 'trial', 'credits' => 25, 'slug' => 'ai-business-essentials' ) );
trial_expect( 'trial' === $trial_only['access_mode'] && 25 === $trial_only['credits'], 'an active trial is used when nothing is paid' );
trial_expect( array() === bds_ai_exec_choose_access( array(), array() ), 'empty facts grant no execution plan' );

trial_expect( 25 === BDS_AI_TRIAL_CREDITS, 'trial credit bound stays 25' );
trial_expect( 'ai-business-essentials' === BDS_AI_TRIAL_PRODUCT_SLUG, 'trial is Essentials only' );
trial_expect( '0.1.2' === BDS_AI_TRIAL_VERSION, 'trial MU stamps 0.1.2' );
trial_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise execution key' );
trial_expect( ! defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ), 'tests do not enable the entitlements flag' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "trial entitlement tests passed\n";
