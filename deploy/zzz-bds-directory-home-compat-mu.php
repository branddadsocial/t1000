<?php
/**
 * Plugin Name: BrandDad Directory Homepage Compatibility
 * Description: Lets the replacement Directory homepage own the front page while preserving the legacy composer as a fallback.
 * Version: 1.0.0
 * Author: BrandDad
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable legacy homepage composition when the replacement experience is live.
 *
 * Runs immediately before the legacy semantic buffer. At this point WordPress
 * has resolved the main query and all standard plugins have loaded, so both the
 * homepage test and the replacement feature flag are authoritative.
 *
 * @return void
 */
function bds_directory_home_compat_yield_to_replacement() {
	$replacement_enabled = 'BrandDad\\DirectoryExperience\\is_public_redesign_enabled';

	if (
		! function_exists( 'bds_dfc_is_home' )
		|| ! bds_dfc_is_home()
		|| ! function_exists( $replacement_enabled )
		|| ! (bool) call_user_func( $replacement_enabled )
	) {
		return;
	}

	remove_action( 'template_redirect', 'bds_dfc_semantic_buffer_start', -9999 );
	remove_action( 'pre_get_posts', 'bds_dfc_photo_only_query', 50 );
	remove_filter( 'elementor/widget/render_content', 'bds_dfc_strip_location_widgets', 20 );
	remove_filter( 'pre_get_document_title', 'bds_dfc_home_document_title', 99999 );
	remove_filter( 'wpseo_title', 'bds_dfc_home_document_title', 99999 );
	remove_filter( 'rank_math/frontend/title', 'bds_dfc_home_document_title', 99999 );
	remove_filter( 'document_title_parts', 'bds_dfc_home_title_parts', 99999 );

	remove_action( 'wp_head', 'bds_dfc_css', 6 );
	remove_action( 'wp_head', 'bds_dfc_scroll_pin', 7 );
	remove_action( 'wp_body_open', 'bds_dfc_wa_hero', 5 );
	remove_action( 'wp_footer', 'bds_dfc_wa_hero', 18 );
	remove_action( 'wp_footer', 'bds_dfc_home_conversion_bands', 22 );
	remove_action( 'wp_footer', 'bds_dfc_health_boot', 27 );
	remove_action( 'wp_footer', 'bds_dfc_js', 25 );
	remove_action( 'wp_footer', 'bds_dfc_wa_cards_data', 25 );
	remove_action( 'wp_footer', 'bds_dfc_browse_js', 26 );
}
add_action( 'template_redirect', 'bds_directory_home_compat_yield_to_replacement', -10000 );
