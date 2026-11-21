<?php
/**
 * directory_free job access is not granted from a client access_mode or a foreign listing.
 */
define( 'ABSPATH', __DIR__ );
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

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
function is_user_logged_in() {
	return false;
}
function is_admin() {
	return false;
}

require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-execution-mu.php';

$failed = 0;
function bola_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$paid = bds_ai_exec_create_access(
	array( 'access_mode' => 'paid', 'slug' => 'ai-business-essentials' ),
	'directory_free',
	88,
	7,
	true
);
bola_expect( 'paid' === $paid['mode'] && 0 === $paid['listing_id'], 'a paid plan ignores directory_free and a listing id' );

$trial = bds_ai_exec_create_access(
	array( 'access_mode' => 'trial', 'slug' => 'ai-business-essentials' ),
	'directory_free',
	88,
	7,
	true
);
bola_expect( 'trial' === $trial['mode'] && 0 === $trial['listing_id'], 'a trial plan is not spent as Directory free AI' );

$bola = bds_ai_exec_create_access( array(), 'directory_free', 88, 7, false );
bola_expect( 'none' === $bola['mode'] && 0 === $bola['listing_id'], 'directory_free plus someone else listing id grants nothing' );

$anon = bds_ai_exec_create_access( array(), 'directory_free', 12, 0, true );
bola_expect( 'none' === $anon['mode'] && 0 === $anon['listing_id'], 'an anonymous session cannot spend Directory free AI' );

$forced = bds_ai_exec_create_access( array(), 'paid', 12, 7, true );
bola_expect( 'directory_free' === $forced['mode'] && 12 === $forced['listing_id'], 'server grants directory_free from listing facts, not from a paid access_mode' );

$owned = bds_ai_exec_create_access( array(), 'directory_free', 12, 7, true );
bola_expect( 'directory_free' === $owned['mode'] && 12 === $owned['listing_id'], 'a verified owner may use Directory free AI' );

$empty = bds_ai_exec_create_access( array(), 'directory_free', 0, 7, false );
bola_expect( 'none' === $empty['mode'] && 0 === $empty['listing_id'], 'directory_free without a listing is subscription_required' );

bola_expect( '0.2.3' === BDS_AI_EXECUTION_VERSION, 'execution stamps 0.2.3' );
bola_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise execution key' );
bola_expect( ! defined( 'BDS_DIRECTORY_FREE_AI_EXECUTION_ENABLED' ), 'tests do not enable Directory free AI execution' );
bola_expect( ! defined( 'BDS_AI_ENTITLEMENTS_ENABLED' ), 'tests do not enable entitlements' );

$src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-ai-execution-mu.php' );
bola_expect( false !== strpos( $src, 'bds_ai_exec_create_access' ), 'create_job derives access through the BOLA helper' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "execution access BOLA tests passed\n";
