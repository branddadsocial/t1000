<?php
namespace BrandDad\DirectoryExperience {
	function is_public_redesign_enabled() {
		return (bool) $GLOBALS['replacement_enabled'];
	}
}

namespace {
	define( 'ABSPATH', __DIR__ );
	$GLOBALS['replacement_enabled'] = true;
	$GLOBALS['compat_hooks'] = array();
	$GLOBALS['compat_removals'] = array();

	function add_action( $hook, $callback, $priority ) {
		$GLOBALS['compat_hooks'][ $hook ][ $priority ] = $callback;
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

	$callback = $GLOBALS['compat_hooks']['template_redirect'][-10000] ?? null;
	if ( ! is_callable( $callback ) ) {
		fwrite( STDERR, "Compatibility hook was not registered.\n" );
		exit( 1 );
	}

	call_user_func( $callback );
	$semantic = array( 'action', 'template_redirect', 'bds_dfc_semantic_buffer_start', -9999 );
	if ( ! in_array( $semantic, $GLOBALS['compat_removals'], true ) || count( $GLOBALS['compat_removals'] ) !== 16 ) {
		fwrite( STDERR, "Expected legacy homepage hooks were not removed.\n" );
		exit( 1 );
	}

	$GLOBALS['replacement_enabled'] = false;
	$GLOBALS['compat_removals'] = array();
	call_user_func( $callback );
	if ( count( $GLOBALS['compat_removals'] ) !== 0 ) {
		fwrite( STDERR, "Legacy fallback hooks were removed while replacement was disabled.\n" );
		exit( 1 );
	}

	echo "directory homepage compatibility test passed\n";
}
