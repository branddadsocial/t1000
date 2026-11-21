<?php
require dirname( __DIR__ ) . '/deploy/cursor/bds-wp-cron-spawn-mu.php';

$failed = 0;
function spawn_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

spawn_expect( true === bds_wp_cron_spawn_url_ok( 'https://branddad.social/wp-cron.php' ), 'social https cron URL is allowed' );
spawn_expect( true === bds_wp_cron_spawn_url_ok( 'https://www.branddad.social/wp-cron.php' ), 'www social https cron URL is allowed' );
spawn_expect( true === bds_wp_cron_spawn_url_ok( 'https://branddad.social:443/wp-cron.php' ), 'explicit 443 is allowed' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'http://branddad.social/wp-cron.php' ), 'http is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://directory.branddad.social/wp-cron.php' ), 'directory host is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://branddad.social/wp-cron.php?doing_wp_cron=1' ), 'query string is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://branddad.social/wp-cron.php#x' ), 'fragment is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://evil.com/wp-cron.php' ), 'foreign host is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://branddad.social.evil.com/wp-cron.php' ), 'suffix host is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://127.0.0.1/wp-cron.php' ), 'loopback is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://user:pass@branddad.social/wp-cron.php' ), 'userinfo is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://branddad.social:8080/wp-cron.php' ), 'non-443 port is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( 'https://branddad.social/wp-cron.php/../wp-login.php' ), 'path escape is refused' );
spawn_expect( false === bds_wp_cron_spawn_url_ok( '' ), 'empty URL is refused' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "wp-cron spawn tests passed\n";
