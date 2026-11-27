<?php
namespace BrandDad\DirectoryExperience {
	function is_public_redesign_enabled() {
		return (bool) $GLOBALS['replacement_enabled'];
	}
	function render_home() {
		return '<main class="bdd"><h1>Find local businesses</h1></main>';
	}
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
	function is_front_page() {
		return true;
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

	$source = file_get_contents( dirname( __DIR__ ) . '/deploy/zzz-bds-directory-home-compat-mu.php' );
	$render_position = strpos( $source, '$home = \\BrandDad\\DirectoryExperience\\render_home();' );
	$head_position = strpos( $source, 'wp_head();' );
	if ( false === $render_position || false === $head_position || $render_position > $head_position ) {
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
