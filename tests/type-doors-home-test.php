<?php
/**
 * Homepage type doors: consumer-first buckets. Events stays even when empty.
 */
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
require 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-type-doors-mu.php';

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . " expected=" . var_export( $expected, true ) . " actual=" . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

assert_same( '1.3.8', BDS_DTD_VER, 'type doors version' );

$rows = array(
	array( 'slug' => 'food-beverage', 'count' => 78 ),
	array( 'slug' => 'events', 'count' => 0 ),
	array( 'slug' => 'hotels-stays', 'count' => 40 ),
	array( 'slug' => 'health-body', 'count' => 38 ),
	array( 'slug' => 'travel', 'count' => 29 ),
	array( 'slug' => 'entertainment-nightlife', 'count' => 17 ),
	array( 'slug' => 'beauty-personal-care', 'count' => 17 ),
	array( 'slug' => 'real-estate-directory', 'count' => 20 ),
	array( 'slug' => 'local-services', 'count' => 6 ),
	array( 'slug' => 'automotive', 'count' => 8 ),
	array( 'slug' => 'pets-animals', 'count' => 1 ),
	array( 'slug' => 'shopping-directory', 'count' => 0 ),
	array( 'slug' => 'classifieds', 'count' => 0 ),
	array( 'slug' => 'jobs-careers', 'count' => 0 ),
	array( 'slug' => 'digital-services', 'count' => 2 ),
);
list( $primary, $nearby, $more ) = bds_dtd_home_buckets( $rows );
$p_slugs = array_map(
	static function ( $r ) {
		return $r['slug'];
	},
	$primary
);
$n_slugs = array_map(
	static function ( $r ) {
		return $r['slug'];
	},
	$nearby
);
$m_slugs = array_map(
	static function ( $r ) {
		return $r['slug'];
	},
	$more
);
assert_same(
	array( 'food-beverage', 'events', 'hotels-stays', 'health-body', 'travel', 'entertainment-nightlife', 'beauty-personal-care', 'real-estate-directory' ),
	$p_slugs,
	'primary consumer row'
);
assert_same( true, in_array( 'events', $p_slugs, true ), 'events stays in the first row even when empty' );
assert_same( array( 'local-services', 'automotive', 'pets-animals' ), $n_slugs, 'nearby only when live' );
assert_same( true, in_array( 'shopping-directory', $m_slugs, true ), 'empty shopping is not in the nearby row' );
assert_same( true, in_array( 'classifieds', $m_slugs, true ), 'classifieds stay in more' );
assert_same( true, in_array( 'digital-services', $m_slugs, true ), 'digital stays in more' );
assert_same( false, in_array( 'classifieds', $p_slugs, true ), 'classifieds are not primary' );

$src = file_get_contents( 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-type-doors-mu.php' );
assert_same( true, false !== strpos( $src, "'/flights'" ) && false !== strpos( $src, "'/hotels'" ) && false !== strpos( $src, "'/jobs'" ), 'flights hotels and jobs stop 404ing' );
assert_same( true, false !== strpos( $src, 'What are you looking for?' ), 'homepage asks what the visitor wants' );
assert_same( true, false !== strpos( $src, 'bds_lang_pair' ) && false !== strpos( $src, 'Comida, eventos' ), 'Spanish type-door lead is tagged for the switcher' );
assert_same( true, false === strpos( strtolower( $src ), 'bhgift' ), 'no supplier names' );

echo "type doors home ok\n";
