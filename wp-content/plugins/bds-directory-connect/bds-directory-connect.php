<?php
/**
 * Plugin Name: BDS Directory Connect
 * Description: Fixes Directory home category tabs, wires hero search to Ask BrandDad, and auto-updates Popular In / category chips from live listing counts.
 * Version: 1.0.1
 * Author: BrandDad Social
 * Text Domain: bds-directory-connect
 *
 * Install on directory.branddad.social (wp-content/plugins/), activate, then clear any page cache.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_DC_VERSION', '1.0.1' );
define( 'BDS_DC_FILE', __FILE__ );
define( 'BDS_DC_DIR', plugin_dir_path( __FILE__ ) );
define( 'BDS_DC_URL', plugin_dir_url( __FILE__ ) );

require_once BDS_DC_DIR . 'includes/class-browse-data.php';
require_once BDS_DC_DIR . 'includes/class-rest.php';
require_once BDS_DC_DIR . 'includes/class-home-browse.php';

/**
 * Boot the plugin.
 */
function bds_dc_boot() {
	BDS_DC_Rest::init();
	BDS_DC_Home_Browse::init();

	add_action( 'wp_enqueue_scripts', 'bds_dc_enqueue_assets', 40 );

	// Bust browse cache whenever listings or location/category terms change.
	add_action( 'save_post_at_biz_dir', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'deleted_post', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'edited_at_biz_dir-location', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'created_at_biz_dir-location', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'delete_at_biz_dir-location', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'edited_at_biz_dir-category', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'created_at_biz_dir-category', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'delete_at_biz_dir-category', array( 'BDS_DC_Browse_Data', 'flush_cache' ) );
	add_action( 'set_object_terms', 'bds_dc_flush_on_terms', 10, 6 );
}
add_action( 'plugins_loaded', 'bds_dc_boot' );

/**
 * Flush when listing taxonomies are assigned.
 *
 * @param int    $object_id  Object ID.
 * @param array  $terms      Terms.
 * @param array  $tt_ids     Term taxonomy IDs.
 * @param string $taxonomy   Taxonomy.
 * @param bool   $append     Append.
 * @param array  $old_tt_ids Old term taxonomy IDs.
 */
function bds_dc_flush_on_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	unset( $object_id, $terms, $tt_ids, $append, $old_tt_ids );
	if ( in_array( $taxonomy, array( 'at_biz_dir-location', 'at_biz_dir-category', 'atbdp_listing_types' ), true ) ) {
		BDS_DC_Browse_Data::flush_cache();
	}
}

/**
 * Front-end assets on the Directory home (and anywhere Directorist search appears).
 */
function bds_dc_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	$browse = BDS_DC_Browse_Data::get();

	wp_register_style(
		'bds-directory-connect',
		BDS_DC_URL . 'assets/home-connect.css',
		array(),
		BDS_DC_VERSION
	);
	wp_enqueue_style( 'bds-directory-connect' );

	wp_register_script(
		'bds-directory-connect',
		BDS_DC_URL . 'assets/home-connect.js',
		array(),
		BDS_DC_VERSION,
		true
	);

	wp_localize_script(
		'bds-directory-connect',
		'BDS_DC',
		array(
			'version'     => BDS_DC_VERSION,
			'home'        => home_url( '/' ),
			'searchUrl'   => home_url( '/search-result/' ),
			'browseUrl'   => rest_url( 'bds-connect/v1/browse' ),
			'browse'      => $browse,
			'hideAiHero'  => true, // Use Directorist bar → Ask BrandDad; drop duplicate hero chip.
			'wireSearch'  => true,
			'wireTypes'   => true,
		)
	);

	wp_enqueue_script( 'bds-directory-connect' );
}
