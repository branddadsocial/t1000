<?php
/**
 * Plugin Name: BrandDad Directory Targeted Cache Refresh
 * Description: Recoverably retires four fixed stale Directory HTML cache objects once.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_TARGETED_CACHE_REFRESH_VERSION', '0.1.0' );
define( 'BDS_TARGETED_CACHE_REFRESH_BACKUP', '.bds-targeted-refresh-20261120-v1' );

/**
 * @param string $path Filesystem path.
 * @return string
 */
function bds_targeted_cache_refresh_normalize_path( $path ) {
	$path = str_replace( '\\', '/', (string) $path );
	return rtrim( $path, '/' );
}

/**
 * @return array<int,string>
 */
function bds_targeted_cache_refresh_targets() {
	return array(
		'0c909b5d9e8254b5786c5d9cc9ab26379041978219341b04a087d2490b8c6d88.html',
		'96bb172455b147b9efb76f0614dd83a536ffec45109657f947801161fc529ca1.html',
		'7f5574c62b542f39889fc4fa77404b0e8cf856136ec7769783a0427bb4a3cc18.html',
		'e494c1a4c485d5230cfc3d47acaec1df5067c6ff11c370ba2439a11f0c3d23f4.html',
	);
}

/**
 * @return string
 */
function bds_targeted_cache_refresh_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/[\r\n\x00\t].*/', '', $host );
	$host = preg_replace( '/:\d+$/', '', (string) $host );
	return is_string( $host ) ? $host : '';
}

/**
 * @param string $name Basename only.
 * @return bool
 */
function bds_targeted_cache_refresh_target_allowed( $name ) {
	return 1 === preg_match( '/^[a-f0-9]{64}\.html$/', (string) $name )
		&& in_array( (string) $name, bds_targeted_cache_refresh_targets(), true );
}

/**
 * @return array<string,mixed>
 */
function bds_targeted_cache_refresh_run() {
	$result = array(
		'version'           => BDS_TARGETED_CACHE_REFRESH_VERSION,
		'moved'             => array(),
		'already_backed_up' => array(),
		'missing'           => array(),
		'errors'            => array(),
		'complete'          => false,
	);

	if ( ! in_array( bds_targeted_cache_refresh_host(), array( 'directory.branddad.social', 'www.directory.branddad.social' ), true ) ) {
		$result['errors'][] = 'host_refused';
		return $result;
	}

	$expected_dir = bds_targeted_cache_refresh_normalize_path( WP_CONTENT_DIR . '/cache/bd-eco-html' );
	$real_dir     = realpath( $expected_dir );
	if ( false === $real_dir || ! is_dir( $real_dir ) ) {
		$result['errors'][] = 'cache_dir_missing';
		return $result;
	}
	$cache_dir = bds_targeted_cache_refresh_normalize_path( $real_dir );
	if ( $cache_dir !== $expected_dir ) {
		$result['errors'][] = 'cache_dir_refused';
		return $result;
	}

	$backup_dir = $cache_dir . '/' . BDS_TARGETED_CACHE_REFRESH_BACKUP;
	if ( bds_targeted_cache_refresh_normalize_path( dirname( $backup_dir ) ) !== $cache_dir ) {
		$result['errors'][] = 'backup_dir_refused';
		return $result;
	}
	if ( ! is_dir( $backup_dir ) && ! wp_mkdir_p( $backup_dir ) ) {
		$result['errors'][] = 'backup_dir_failed';
		return $result;
	}
	if ( is_link( $backup_dir ) ) {
		$result['errors'][] = 'backup_symlink_refused';
		return $result;
	}

	$receipt = $backup_dir . '/complete.json';
	if ( is_file( $receipt ) && ! is_link( $receipt ) ) {
		$result['complete'] = true;
		return $result;
	}

	foreach ( bds_targeted_cache_refresh_targets() as $name ) {
		if ( ! bds_targeted_cache_refresh_target_allowed( $name ) || basename( $name ) !== $name ) {
			$result['errors'][] = 'target_refused';
			continue;
		}

		$source      = $cache_dir . '/' . $name;
		$destination = $backup_dir . '/' . $name . '.bak';
		if ( is_link( $source ) || is_link( $destination ) ) {
			$result['errors'][] = 'symlink_refused:' . $name;
			continue;
		}
		if ( is_file( $destination ) ) {
			$result['already_backed_up'][] = $name;
			continue;
		}
		if ( ! is_file( $source ) ) {
			$result['missing'][] = $name;
			continue;
		}
		if ( ! @rename( $source, $destination ) ) { // Fixed same-filesystem paths; bounded error is recorded below.
			$result['errors'][] = 'rename_failed:' . $name;
			continue;
		}
		$result['moved'][] = $name;
	}

	$handled = count( $result['moved'] ) + count( $result['already_backed_up'] );
	if ( empty( $result['errors'] ) && $handled > 0 ) {
		$payload = array(
			'version'           => BDS_TARGETED_CACHE_REFRESH_VERSION,
			'at_utc'            => gmdate( 'c' ),
			'moved'             => $result['moved'],
			'already_backed_up' => $result['already_backed_up'],
			'missing'           => $result['missing'],
		);
		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload, JSON_PRETTY_PRINT ) : json_encode( $payload, JSON_PRETTY_PRINT );
		if ( ! is_string( $json ) || false === @file_put_contents( $receipt, $json . "\n", LOCK_EX ) ) { // Receipt contains fixed basenames only.
			$result['errors'][] = 'receipt_failed';
			return $result;
		}
		$result['complete'] = true;
	}

	return $result;
}

$GLOBALS['bds_targeted_cache_refresh_result'] = bds_targeted_cache_refresh_run();
