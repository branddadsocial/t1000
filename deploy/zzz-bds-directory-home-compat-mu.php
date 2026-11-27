<?php
/**
 * Plugin Name: BrandDad Directory Homepage Compatibility
 * Description: Preserves the complete Directory homepage document while the replacement experience remains composer-backed.
 * Version: 1.1.4
 * Author: BrandDad
 */

defined( 'ABSPATH' ) || exit;

/** Return the normalized request host. */
function bds_directory_home_compat_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/[\r\n\x00\t].*/', '', $host );
	$host = preg_replace( '/:\d+$/', '', (string) $host );
	return is_string( $host ) ? $host : '';
}

/** Return the normalized URL path without allowing it to select a template. */
function bds_directory_home_compat_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = wp_parse_url( $uri, PHP_URL_PATH );
	return is_string( $path ) && '' !== $path ? $path : '/';
}

/** Determine whether the existing replacement homepage can be rendered safely. */
function bds_directory_home_compat_should_render() {
	return 'directory.branddad.social' === bds_directory_home_compat_host()
		&& '/' === bds_directory_home_compat_path()
		&& function_exists( '\\BrandDad\\DirectoryExperience\\is_public_redesign_enabled' )
		&& \BrandDad\DirectoryExperience\is_public_redesign_enabled()
		&& function_exists( '\\BrandDad\\DirectoryExperience\\register_assets' )
		&& function_exists( '\\BrandDad\\DirectoryExperience\\render_home' );
}

/** Keep legacy full-document filters from mistaking script comments for markup. */
function bds_directory_home_compat_sanitize_head( $head ) {
	if ( ! is_string( $head ) || '' === $head ) {
		return '';
	}
	return str_ireplace( '<body>', 'body', $head );
}

/** Discard only removable buffers that predate this exact-root document. */
function bds_directory_home_compat_clear_buffers() {
	while ( ob_get_level() > 0 ) {
		if ( ! @ob_end_clean() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			break;
		}
	}
}

/**
 * Render the existing replacement homepage before colliding legacy buffers run.
 *
 * This is the same document shell as the replacement plugin's front-page
 * template. It deliberately omits wp_body_open(), because the legacy homepage
 * is attached there and would duplicate the replacement experience.
 */
function bds_directory_home_compat_render() {
	if ( ! bds_directory_home_compat_should_render() ) {
		return;
	}

	bds_directory_home_compat_clear_buffers();
	status_header( 200 );
	if ( ! headers_sent() ) {
		header( 'X-BDS-Directory-Home: recovery-6' );
	}

	/*
	 * Register the protected plugin's assets, then build the replacement before
	 * wp_head(). Its renderer enqueues those assets while creating the fragment;
	 * generating it later leaves the document complete but unstyled.
	 */
	\BrandDad\DirectoryExperience\register_assets();
	$home = \BrandDad\DirectoryExperience\render_home();

	echo '<!doctype html><html ';
	language_attributes();
	echo '><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta name="viewport" content="width=device-width, initial-scale=1">';
	ob_start();
	wp_head();
	$head = ob_get_clean();
	echo bds_directory_home_compat_sanitize_head( $head ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</head><body ';
	body_class( 'branddad-directory-redesign' );
	echo '>';
	echo $home; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_footer();
	echo '</body></html>';
	exit;
}
add_action( 'template_redirect', 'bds_directory_home_compat_render', -100000 );

/**
 * Preserve legacy homepage composition until the replacement owns rendering.
 *
 * Runs on `wp` immediately before the broken yield callback. At this point
 * WordPress has resolved the main query, but the callback has not yet removed
 * the legacy homepage section hooks.
 *
 * @return void
 */
function bds_directory_home_compat_yield_to_replacement() {
	/*
	 * The replacement feature flag is currently enabled without an independent
	 * renderer. The legacy composer's own wp callback therefore removes every
	 * homepage section and leaves a successful response with an empty body.
	 * Prevent that one yield callback; keep the actual composer hooks intact so
	 * the look-lock layer receives and polishes a complete document.
	 */
	if ( function_exists( 'bds_dfc_is_home' ) && bds_dfc_is_home() ) {
		remove_action( 'wp', 'bds_dfc_yield_to_replacement_home', PHP_INT_MAX );
	}
}
add_action( 'wp', 'bds_directory_home_compat_yield_to_replacement', PHP_INT_MAX - 1 );
