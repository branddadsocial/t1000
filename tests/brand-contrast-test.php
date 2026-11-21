<?php

function contrast_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$root = dirname( __DIR__ );
$files = array(
	$root . '/deploy/bds-ai-launch-pages-mu.php',
	$root . '/deploy/cursor/bds-revenue-sections-mu.php',
	$root . '/deploy/cursor/bds-customer-surfaces-mu.php',
	$root . '/deploy/cursor/bds-directory-featured-ads-mu.php',
	$root . '/deploy/cursor/bds-codex-bridge-mu.php',
	$root . '/deploy/cursor/bds-sales-help-mu.php',
);

foreach ( $files as $file ) {
	contrast_expect( is_file( $file ), 'contrast source exists: ' . basename( $file ) );
	$source = file_get_contents( $file );
	contrast_expect( false !== $source, 'contrast source is readable: ' . basename( $file ) );
	contrast_expect( false !== stripos( $source, '#0668d8' ), 'approved accessible blue is present: ' . basename( $file ) );
	contrast_expect( false === stripos( $source, '#0878ed' ), 'lower-contrast primary blue is absent: ' . basename( $file ) );
}

echo "Brand contrast tests passed.\n";
