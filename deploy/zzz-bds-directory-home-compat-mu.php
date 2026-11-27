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
	 * The legacy composer currently owns the final homepage document and also
	 * carries the replacement experience markup. Removing its hooks leaves a
	 * successful HTTP response with an empty body. Keep it active until the
	 * replacement has an independent renderer; the look-lock layer removes the
	 * obsolete visual fragments after the complete document exists.
	 */
	return;
}
add_action( 'template_redirect', 'bds_directory_home_compat_yield_to_replacement', -10000 );
