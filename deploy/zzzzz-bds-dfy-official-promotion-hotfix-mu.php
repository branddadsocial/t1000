<?php
/**
 * Plugin Name: BrandDad Official Promotion Overlay Hotfix
 * Description: Bypasses the host-cached legacy Done-for-You overlay and renders the current canonical Services page.
 * Version: 1.0.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_OFFICIAL_PROMOTION_HOTFIX_VERSION', '1.0.0' );

/** Render through the current canonical frontend implementation. */
function bds_dfy_official_promotion_filter( $content ) {
	return function_exists( 'bds_fp_filter_content' ) ? bds_fp_filter_content( $content ) : $content;
}

/**
 * The host may retain the older overlay in opcode cache after an in-place file
 * replacement. This later-loading MU plugin removes only that callback and
 * installs a distinct callback, forcing PHP to compile this new file.
 */
function bds_dfy_official_promotion_install() {
	remove_filter( 'the_content', 'bds_fp_filter_content', PHP_INT_MAX );
	remove_filter( 'the_content', 'bds_dfy_packages_filter', PHP_INT_MAX );
	remove_filter( 'the_content', 'bds_dfy_official_promotion_filter', PHP_INT_MAX );
	add_filter( 'the_content', 'bds_dfy_official_promotion_filter', PHP_INT_MAX );
}

bds_dfy_official_promotion_install();
add_action( 'wp', 'bds_dfy_official_promotion_install', PHP_INT_MAX );
