<?php
/**
 * Plugin Name: BrandDad Directory Homepage Compatibility
 * Description: Preserves the complete Directory homepage document while the replacement experience remains composer-backed.
 * Version: 1.0.1
 * Author: BrandDad
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preserve legacy homepage composition until the replacement owns rendering.
 *
 * Runs immediately before the legacy semantic buffer. At this point WordPress
 * has resolved the main query and all standard plugins have loaded, so both the
 * homepage test and the replacement feature flag are authoritative.
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
add_action( 'template_redirect', 'bds_directory_home_compat_yield_to_replacement', -10000 );
