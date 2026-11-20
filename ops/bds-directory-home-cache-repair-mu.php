<?php
/**
 * Plugin Name: BrandDad Directory Home Cache Repair (Temporary)
 * Description: Admin-only, single-use repair for the canonical Directory homepage cache object.
 * Version: 1.0.0
 *
 * Security design:
 * - Reuses WordPress's authenticated administrator session and manage_options capability.
 * - Requires a same-origin POST with a WordPress nonce.
 * - Accepts no path, URL, filename, or other caller-controlled input.
 * - Calls the existing exact-path atomic refresh for the fixed canonical path `/` only.
 * - Exposes no secrets or cache paths and returns only a coarse success/error notice.
 * - Intended to be removed from production immediately after the verified repair.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BDS_DIRECTORY_HOME_CACHE_REPAIR_ACTION = 'bds_directory_home_cache_repair';

/**
 * Register a short-lived administrator-only tool page.
 *
 * @return void
 */
function bds_directory_home_cache_repair_register_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	add_management_page(
		'Directory Homepage Cache Repair',
		'Directory Cache Repair',
		'manage_options',
		'bds-directory-home-cache-repair',
		'bds_directory_home_cache_repair_render_page'
	);
}
add_action( 'admin_menu', 'bds_directory_home_cache_repair_register_page' );

/**
 * Render the confirmation form and a coarse result notice.
 *
 * @return void
 */
function bds_directory_home_cache_repair_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'branddad' ), '', array( 'response' => 403 ) );
	}
	$result = isset( $_GET['bds_cache_repair_result'] )
		? sanitize_key( wp_unslash( $_GET['bds_cache_repair_result'] ) )
		: '';
	?>
	<div class="wrap">
		<h1>Directory Homepage Cache Repair</h1>
		<?php if ( 'success' === $result ) : ?>
			<div class="notice notice-success"><p>The canonical homepage cache was rebuilt and its exact edge URL was purged.</p></div>
		<?php elseif ( 'failed' === $result ) : ?>
			<div class="notice notice-error"><p>The cache repair did not complete. No other cache path was changed.</p></div>
		<?php endif; ?>
		<p>This temporary tool rebuilds only <code>https://directory.branddad.social/</code>. It does not purge checkout, account, listing, or other page caches.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( BDS_DIRECTORY_HOME_CACHE_REPAIR_ACTION ); ?>">
			<?php wp_nonce_field( BDS_DIRECTORY_HOME_CACHE_REPAIR_ACTION ); ?>
			<?php submit_button( 'Repair Homepage Cache' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Atomically rebuild the fixed homepage object and purge only its exact edge URL.
 *
 * @return void
 */
function bds_directory_home_cache_repair_run() {
	if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		wp_die( esc_html__( 'Method not allowed.', 'branddad' ), '', array( 'response' => 405 ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'branddad' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( BDS_DIRECTORY_HOME_CACHE_REPAIR_ACTION );

	$ok = false;
	if ( function_exists( 'bd_eco_perf_refresh_dirty_path' ) ) {
		$result = bd_eco_perf_refresh_dirty_path( '/' );
		$ok     = is_array( $result ) && ! empty( $result['ok'] );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                    => 'bds-directory-home-cache-repair',
				'bds_cache_repair_result' => $ok ? 'success' : 'failed',
			),
			admin_url( 'tools.php' )
		)
	);
	exit;
}
add_action( 'admin_post_' . BDS_DIRECTORY_HOME_CACHE_REPAIR_ACTION, 'bds_directory_home_cache_repair_run' );
