<?php
/**
 * Plugin Name: BDS Restore Elementor Footer
 * Description: Restores the original Elementor footer on branddad.social by hiding the custom bds-site-footer chrome replacement and showing .elementor-location-footer again.
 * Version: 1.0.0
 * Author: BrandDad Social
 * Text Domain: bds-restore-elementor-footer
 *
 * Install on branddad.social only. Clear BerqWP / page cache after activating.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_REF_VERSION', '1.0.0' );
define( 'BDS_REF_URL', plugin_dir_url( __FILE__ ) );

/**
 * Only run on the BrandDad Social marketing host (not Directory / HostTech).
 *
 * @return bool
 */
function bds_ref_is_social_host() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$host = is_string( $host ) ? strtolower( $host ) : '';
	$host = preg_replace( '/^www\./', '', $host );
	return in_array( $host, array( 'branddad.social' ), true );
}

/**
 * Front-end CSS/JS that puts the Elementor footer back.
 */
function bds_ref_enqueue() {
	if ( is_admin() || ! bds_ref_is_social_host() ) {
		return;
	}

	wp_register_style(
		'bds-restore-elementor-footer',
		BDS_REF_URL . 'assets/restore-footer.css',
		array(),
		BDS_REF_VERSION
	);
	wp_enqueue_style( 'bds-restore-elementor-footer' );

	wp_register_script(
		'bds-restore-elementor-footer',
		BDS_REF_URL . 'assets/restore-footer.js',
		array(),
		BDS_REF_VERSION,
		true
	);
	wp_enqueue_script( 'bds-restore-elementor-footer' );
}
add_action( 'wp_enqueue_scripts', 'bds_ref_enqueue', 100 );

/**
 * Late safety CSS in case chrome styles load after ours.
 */
function bds_ref_late_css() {
	if ( is_admin() || ! bds_ref_is_social_host() ) {
		return;
	}
	?>
<style id="bds-restore-elementor-footer-late">
/* Undo bds-chrome footer swap */
body.bds-chrome .elementor-location-footer,
body.branddad-unified .elementor-location-footer,
footer.elementor-location-footer,
footer[data-elementor-type="footer"]{
	display:block !important;
	visibility:visible !important;
}
footer.bds-site-footer,
.bds-site-footer{
	display:none !important;
}
/* Remove Directory / HostTech blocks mistakenly injected above Social footer */
#bd-home-blog,
.bd-home-blog,
#bds-ai-local-pack,
.bds-ai-local-pack,
.bds-hbrowse,
#bds-home-browse,
[class*="ready-to-act"],
.bdsu-ready-act{
	display:none !important;
}
</style>
	<?php
}
add_action( 'wp_head', 'bds_ref_late_css', 999 );
add_action( 'wp_footer', 'bds_ref_late_css', 1 );
