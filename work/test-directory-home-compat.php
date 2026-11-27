<?php
namespace BrandDad\DirectoryExperience {
	function is_public_redesign_enabled() {
		return (bool) $GLOBALS['replacement_enabled'];
	}
	function render_home() {
		return '<main class="bdd"><h1>Find local businesses</h1></main>';
	}
	function register_assets() {}
}

namespace {
	define( 'ABSPATH', __DIR__ );
	$GLOBALS['replacement_enabled'] = true;
	$GLOBALS['compat_hooks'] = array();
	$GLOBALS['compat_removals'] = array();
	$_SERVER['HTTP_HOST'] = 'directory.branddad.social';
	$_SERVER['REQUEST_URI'] = '/?ref=test';

	function add_action( $hook, $callback, $priority ) {
		$GLOBALS['compat_hooks'][ $hook ][ $priority ] = $callback;
	}
	function wp_parse_url( $url, $component ) {
		return parse_url( $url, $component );
	}
	function remove_action( $hook, $callback, $priority ) {
		$GLOBALS['compat_removals'][] = array( 'action', $hook, $callback, $priority );
	}
	function remove_filter( $hook, $callback, $priority ) {
		$GLOBALS['compat_removals'][] = array( 'filter', $hook, $callback, $priority );
	}
	function bds_dfc_is_home() {
		return true;
	}

	require dirname( __DIR__ ) . '/deploy/zzz-bds-directory-home-compat-mu.php';

	$head_probe = '<script>/* under <body> for appended blocks */</script><style>.bdd{display:block}</style>';
	$head_clean = bds_directory_home_compat_sanitize_head( $head_probe );
	if ( false !== stripos( $head_clean, '<body>' ) || false === stripos( $head_clean, '<style>.bdd' ) ) {
		fwrite( STDERR, "Head sanitization did not preserve assets while removing the false body marker.\n" );
		exit( 1 );
	}

	ob_start();
	echo 'legacy-buffer-output';
	bds_directory_home_compat_clear_buffers();
	if ( 0 !== ob_get_level() ) {
		fwrite( STDERR, "The exact-root renderer did not isolate removable legacy buffers.\n" );
		exit( 1 );
	}

	$mobile_css = bds_directory_home_compat_mobile_css();
	if ( false === strpos( $mobile_css, '#bds-lang-switch{display:none!important}' ) ) {
		fwrite( STDERR, "The phone header does not suppress the extra language controls.\n" );
		exit( 1 );
	}
	if ( false === strpos( $mobile_css, '/wp-content/uploads/8.png' ) ) {
		fwrite( STDERR, "The Directory brand mark does not use the supplied logo asset.\n" );
		exit( 1 );
	}

	$source = file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-directory-home-compat-mu.php' );
	ob_start();
	bds_directory_home_compat_heading_guard();
	$heading_guard = ob_get_clean();
	if ( false === strpos( $heading_guard, "getElementById('bdd-title')" )
		|| false === strpos( $heading_guard, "querySelector('h1.bds-wah__title')" )
		|| false !== strpos( $heading_guard, 'innerHTML' ) ) {
		fwrite( STDERR, "The Directory heading guard is missing or re-renders HTML unsafely.\n" );
		exit( 1 );
	}
	$register_position = strpos( $source, '\\BrandDad\\DirectoryExperience\\register_assets();' );
	$render_position = strpos( $source, '$home = \\BrandDad\\DirectoryExperience\\render_home();' );
	$head_position = strpos( $source, 'wp_head();' );
	if ( false === $register_position || false === $render_position || false === $head_position || $register_position > $render_position || $render_position > $head_position ) {
		fwrite( STDERR, "The replacement must enqueue its assets before wp_head().\n" );
		exit( 1 );
	}

	if ( ! bds_directory_home_compat_should_render() ) {
		fwrite( STDERR, "Exact Directory root did not pass the renderer gate.\n" );
		exit( 1 );
	}
	$_SERVER['REQUEST_URI'] = '/all-listings/';
	if ( bds_directory_home_compat_should_render() ) {
		fwrite( STDERR, "A non-home Directory route passed the renderer gate.\n" );
		exit( 1 );
	}
	$_SERVER['REQUEST_URI'] = '/';
	$_SERVER['HTTP_HOST'] = 'branddad.social';
	if ( bds_directory_home_compat_should_render() ) {
		fwrite( STDERR, "The Social host passed the Directory renderer gate.\n" );
		exit( 1 );
	}
	$_SERVER['HTTP_HOST'] = 'directory.branddad.social';

	$callback = $GLOBALS['compat_hooks']['wp'][PHP_INT_MAX - 1] ?? null;
	if ( ! is_callable( $callback ) ) {
		fwrite( STDERR, "Compatibility hook was not registered.\n" );
		exit( 1 );
	}

	call_user_func( $callback );
	$yield = array( 'action', 'wp', 'bds_dfc_yield_to_replacement_home', PHP_INT_MAX );
	if ( ! in_array( $yield, $GLOBALS['compat_removals'], true ) || count( $GLOBALS['compat_removals'] ) !== 1 ) {
		fwrite( STDERR, "The broken replacement yield callback was not disabled.\n" );
		exit( 1 );
	}

	$GLOBALS['replacement_enabled'] = false;
	$GLOBALS['compat_removals'] = array();
	call_user_func( $callback );
	if ( ! in_array( $yield, $GLOBALS['compat_removals'], true ) || count( $GLOBALS['compat_removals'] ) !== 1 ) {
		fwrite( STDERR, "Compatibility callback did not preserve the legacy homepage renderer.\n" );
		exit( 1 );
	}

	echo "directory homepage compatibility test passed\n";
}
