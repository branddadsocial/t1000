<?php

$root = sys_get_temp_dir() . '/bds-targeted-cache-refresh-' . bin2hex( random_bytes( 6 ) );
define( 'ABSPATH', $root . '/wp/' );
define( 'WP_CONTENT_DIR', $root . '/wp-content' );

function wp_mkdir_p( $path ) {
	return is_dir( $path ) || mkdir( $path, 0750, true );
}

function wp_json_encode( $value, $flags = 0 ) {
	return json_encode( $value, $flags );
}

function bds_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$cache = WP_CONTENT_DIR . '/cache/bd-eco-html';
mkdir( $cache, 0750, true );
$_SERVER['HTTP_HOST'] = 'directory.branddad.social';

$expected = array(
	'0c909b5d9e8254b5786c5d9cc9ab26379041978219341b04a087d2490b8c6d88.html',
	'96bb172455b147b9efb76f0614dd83a536ffec45109657f947801161fc529ca1.html',
	'7f5574c62b542f39889fc4fa77404b0e8cf856136ec7769783a0427bb4a3cc18.html',
	'e494c1a4c485d5230cfc3d47acaec1df5067c6ff11c370ba2439a11f0c3d23f4.html',
);
foreach ( $expected as $name ) {
	file_put_contents( $cache . '/' . $name, '<!doctype html><html><body>stale</body></html>' );
}
file_put_contents( $cache . '/unrelated.html', 'preserve' );

require dirname( __DIR__ ) . '/deploy/000-bds-directory-targeted-cache-refresh-mu.php';

$result = $GLOBALS['bds_targeted_cache_refresh_result'];
bds_test_assert( true === $result['complete'], 'first run completes' );
bds_test_assert( $expected === bds_targeted_cache_refresh_targets(), 'fixed targets stay exact' );
bds_test_assert( 4 === count( $result['moved'] ), 'exactly four cache objects move' );
bds_test_assert( is_file( $cache . '/unrelated.html' ), 'unrelated cache object is preserved' );

$backup = $cache . '/' . BDS_TARGETED_CACHE_REFRESH_BACKUP;
foreach ( $expected as $name ) {
	bds_test_assert( ! file_exists( $cache . '/' . $name ), 'source moved: ' . $name );
	bds_test_assert( is_file( $backup . '/' . $name . '.bak' ), 'backup exists: ' . $name );
}
bds_test_assert( is_file( $backup . '/complete.json' ), 'bounded receipt exists' );

$second = bds_targeted_cache_refresh_run();
bds_test_assert( true === $second['complete'], 'second run is inert' );
bds_test_assert( 0 === count( $second['moved'] ), 'second run moves nothing' );

$_SERVER['HTTP_HOST'] = 'attacker.example';
unlink( $backup . '/complete.json' );
$refused = bds_targeted_cache_refresh_run();
bds_test_assert( in_array( 'host_refused', $refused['errors'], true ), 'wrong host is refused' );
bds_test_assert( is_file( $cache . '/unrelated.html' ), 'wrong host cannot affect unrelated file' );

echo "PASS targeted cache refresh\n";
