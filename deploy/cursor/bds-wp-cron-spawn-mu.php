<?php
/**
 * Plugin Name: BrandDad Social wp-cron spawn
 * Description: Non-blocking public HTTPS ping of Social wp-cron.php from site traffic. Does not enable StartHost cron or Codex flags.
 * Version: 0.1.0
 * Author: BrandDad
 *
 * Secure design (ingestion / deployment / SSRF):
 * - URL is never user-supplied. Built from home_url('/wp-cron.php') then allowlisted.
 * - Hosts: branddad.social and www.branddad.social only. Directory is refused.
 * - Scheme https, path exactly /wp-cron.php, no query, no fragment, no userinfo, port empty or 443.
 * - wp_remote_get: blocking false, timeout 0.01, redirection 0, sslverify true, empty cookies.
 * - No new public endpoint. Existing wp-cron.php stays query-string free and secret-free.
 * - Five-minute transient lock. Skip cron/ajax/rest/cli/install. Social host lock.
 * - Does not change DISABLE_WP_CRON. Loopback spawn_cron stays off; this uses public HTTPS like GitHub dispatch.
 */

if ( ! function_exists( 'bds_wp_cron_spawn_hosts' ) ) {
	function bds_wp_cron_spawn_hosts() {
		return array( 'branddad.social', 'www.branddad.social' );
	}
}

if ( ! function_exists( 'bds_wp_cron_spawn_url_ok' ) ) {
	function bds_wp_cron_spawn_url_ok( $url ) {
		if ( ! is_string( $url ) || strlen( $url ) > 180 ) {
			return false;
		}
		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return false;
		}
		$scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';
		$host   = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
		$path   = isset( $parts['path'] ) ? (string) $parts['path'] : '';
		$port   = isset( $parts['port'] ) ? (int) $parts['port'] : 0;
		if ( 'https' !== $scheme ) {
			return false;
		}
		if ( ! in_array( $host, bds_wp_cron_spawn_hosts(), true ) ) {
			return false;
		}
		if ( $port && 443 !== $port ) {
			return false;
		}
		if ( '/wp-cron.php' !== $path ) {
			return false;
		}
		if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
			return false;
		}
		if ( ! empty( $parts['query'] ) || ! empty( $parts['fragment'] ) ) {
			return false;
		}
		return true;
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

define( 'BDS_WP_CRON_SPAWN_VERSION', '0.1.0' );

function bds_wp_cron_spawn_is_social() {
	$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	return in_array( $host, bds_wp_cron_spawn_hosts(), true );
}

function bds_wp_cron_spawn_should_run() {
	if ( ! bds_wp_cron_spawn_is_social() ) {
		return false;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return false;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return false;
	}
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return false;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}
	if ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( (string) $_SERVER['SCRIPT_NAME'], 'wp-cron.php' ) ) {
		return false;
	}
	if ( get_transient( 'bds_wp_cron_spawn_lock' ) ) {
		return false;
	}
	return true;
}

function bds_wp_cron_spawn_ping() {
	if ( ! bds_wp_cron_spawn_should_run() ) {
		return;
	}
	$url = home_url( '/wp-cron.php' );
	if ( ! bds_wp_cron_spawn_url_ok( $url ) ) {
		return;
	}
	set_transient( 'bds_wp_cron_spawn_lock', '1', 5 * MINUTE_IN_SECONDS );
	wp_remote_get(
		$url,
		array(
			'timeout'     => 0.01,
			'blocking'    => false,
			'sslverify'   => true,
			'redirection' => 0,
			'cookies'     => array(),
			'headers'     => array( 'Cache-Control' => 'no-cache' ),
			'user-agent'  => 'BrandDadCronSpawn/' . BDS_WP_CRON_SPAWN_VERSION,
		)
	);
}
add_action( 'shutdown', 'bds_wp_cron_spawn_ping', 1 );
