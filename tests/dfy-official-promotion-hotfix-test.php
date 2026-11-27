<?php

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['bds_hotfix_filters'] = array(
	'the_content' => array(
		PHP_INT_MAX => array(
			'bds_fp_filter_content',
			'bds_dfy_packages_filter',
		),
	),
);

function remove_filter( $hook, $callback, $priority ) {
	if ( empty( $GLOBALS['bds_hotfix_filters'][ $hook ][ $priority ] ) ) {
		return;
	}
	$GLOBALS['bds_hotfix_filters'][ $hook ][ $priority ] = array_values(
		array_filter(
			$GLOBALS['bds_hotfix_filters'][ $hook ][ $priority ],
			static function ( $registered ) use ( $callback ) {
				return $registered !== $callback;
			}
		)
	);
}
function add_filter( $hook, $callback, $priority ) {
	$GLOBALS['bds_hotfix_filters'][ $hook ][ $priority ][] = $callback;
}
function add_action() {}
function bds_fp_filter_content( $content ) {
	return 'canonical:' . $content;
}

require dirname( __DIR__ ) . '/deploy/zzzzz-bds-dfy-official-promotion-hotfix-mu.php';

$registered = $GLOBALS['bds_hotfix_filters']['the_content'][ PHP_INT_MAX ];
$checks = array(
	'version'              => '1.0.0' === BDS_DFY_OFFICIAL_PROMOTION_HOTFIX_VERSION,
	'legacy removed'       => ! in_array( 'bds_dfy_packages_filter', $registered, true ),
	'canonical not direct' => ! in_array( 'bds_fp_filter_content', $registered, true ),
	'hotfix registered'    => array( 'bds_dfy_official_promotion_filter' ) === $registered,
	'canonical rendered'   => 'canonical:before' === bds_dfy_official_promotion_filter( 'before' ),
);

$failed = array_keys( array_filter( $checks, static function ( $passed ) { return ! $passed; } ) );
if ( $failed ) {
	fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

echo 'Done-for-You official promotion hotfix checks passed: ' . count( $checks ) . PHP_EOL;
