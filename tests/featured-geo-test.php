<?php
/**
 * Featured geography fail-closed. No live charge and no invented listing coordinates.
 */
define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function absint( $n ) {
	return abs( (int) $n );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_text_field( $s ) {
	return trim( (string) $s );
}

require dirname( __DIR__ ) . '/deploy/cursor/bds-directory-featured-ads-lib.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-featured-billing-adapter-mu.php';

$failed = 0;
function geo_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$playa = array( 'country' => 'MX', 'lat' => 20.63, 'lng' => -87.07 );
$cancun = array( 'country' => 'MX', 'lat' => 21.16, 'lng' => -86.85 );
$austin = array( 'country' => 'US', 'lat' => 30.27, 'lng' => -97.74 );

$km = bds_feat_haversine_km( $playa['lat'], $playa['lng'], $cancun['lat'], $cancun['lng'] );
geo_expect( $km > 25 && $km < 100, 'Playa to Cancun is outside local 25km and inside regional 100km' );

$local = array( 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 20.63, 'lng' => -87.07 );
geo_expect( true === bds_feat_in_scope( $local, $playa ), 'local card is in scope at the same point' );
geo_expect( false === bds_feat_in_scope( $local, $cancun ), 'local card is out of scope beyond 25km' );
geo_expect( false === bds_feat_in_scope( $local, array( 'country' => 'MX' ) ), 'local card without viewer coordinates fails closed' );

$regional = array( 'level' => 'regional', 'active' => true, 'country' => 'MX', 'lat' => 20.63, 'lng' => -87.07 );
geo_expect( true === bds_feat_in_scope( $regional, $cancun ), 'regional card covers Cancun from Playa' );
geo_expect( false === bds_feat_in_scope( $regional, $austin ), 'regional card does not jump countries by radius alone when lat is US' );
geo_expect( false === bds_feat_same_country( 'MX', 'US' ), 'MX and US are not the same Featured country' );
geo_expect( true === bds_feat_same_country( 'mx', 'MX' ), 'ISO country compare is case-insensitive' );
geo_expect( false === bds_feat_same_country( '', 'MX' ), 'a missing card country fails closed' );
geo_expect( false === bds_feat_same_country( 'MEX', 'MEX' ), 'a three-letter country code fails closed' );

$matamoros   = array( 'country' => 'MX', 'lat' => 25.869, 'lng' => -97.504 );
$brownsville = array( 'country' => 'US', 'lat' => 25.901, 'lng' => -97.497 );
$border_km   = bds_feat_haversine_km( $matamoros['lat'], $matamoros['lng'], $brownsville['lat'], $brownsville['lng'] );
geo_expect( $border_km < 25, 'Matamoros to Brownsville is inside the regional 100km radius' );
$regional_border = array( 'level' => 'regional', 'active' => true, 'country' => 'MX', 'lat' => 25.869, 'lng' => -97.504 );
geo_expect( false === bds_feat_in_scope( $regional_border, $brownsville ), 'regional card does not cross the border inside 100km' );
geo_expect( true === bds_feat_in_scope( $regional_border, $matamoros ), 'regional card stays in scope in the same country' );
geo_expect( false === bds_feat_in_scope( array( 'level' => 'regional', 'active' => true, 'lat' => 20.63, 'lng' => -87.07 ), $cancun ), 'regional without ISO country fails closed' );
$local_border = array( 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 25.869, 'lng' => -97.504 );
geo_expect( false === bds_feat_in_scope( $local_border, $brownsville ), 'local card also requires the same ISO country' );

$national = array( 'level' => 'national', 'active' => true, 'country' => 'MX' );
geo_expect( true === bds_feat_in_scope( $national, $playa ), 'national card stays inside the viewer country' );
geo_expect( false === bds_feat_in_scope( $national, $austin ), 'national card does not show in another country' );
geo_expect( false === bds_feat_in_scope( array( 'level' => 'national', 'country' => '' ), $playa ), 'national without a country fails closed' );

$global = array( 'level' => 'global', 'active' => true );
geo_expect( true === bds_feat_in_scope( $global, $austin ), 'global card is in scope worldwide' );
geo_expect( false === bds_feat_in_scope( array( 'level' => 'nope', 'country' => 'MX' ), $playa ), 'unknown lane fails closed' );

$ok_local = bds_featured_billing_normalize_geo( $playa, 'local' );
geo_expect( 'MX' === $ok_local['country'] && null !== $ok_local['lat'], 'local billing geo keeps ISO country and coordinates' );
geo_expect( array() === bds_featured_billing_normalize_geo( array( 'country' => 'MX' ), 'local' ), 'local billing without coordinates fails closed' );
geo_expect( array() === bds_featured_billing_normalize_geo( array( 'lat' => 20.63, 'lng' => -87.07 ), 'national' ), 'national billing without ISO country fails closed' );
geo_expect( array() === bds_featured_billing_normalize_geo( array( 'country' => 'MEX', 'lat' => 20.63, 'lng' => -87.07 ), 'local' ), 'three-letter country codes fail closed' );
geo_expect( array() === bds_featured_billing_normalize_geo( array( 'country' => 'MX', 'lat' => 999, 'lng' => 0 ), 'local' ), 'out-of-range latitude fails closed' );
$global_geo = bds_featured_billing_normalize_geo( array(), 'global' );
geo_expect( isset( $global_geo['country'] ), 'global billing may activate without a country' );

$skus = bds_featured_billing_skus();
$levels = bds_feat_levels();
geo_expect( '9.99' === $skus['bds-feat-local']['monthly'] && '9.99' === $levels['local']['price'], 'local Featured stays 9.99' );
geo_expect( '19.99' === $skus['bds-feat-regional']['monthly'] && '19.99' === $levels['regional']['price'], 'regional Featured stays 19.99' );
geo_expect( '39.99' === $skus['bds-feat-national']['monthly'] && '39.99' === $levels['national']['price'], 'national Featured stays 39.99' );
geo_expect( '99.99' === $skus['bds-feat-global']['monthly'] && '99.99' === $levels['global']['price'], 'global Featured stays 99.99' );
geo_expect( '0.1.2' === BDS_FEATURED_BILLING_VERSION, 'featured billing adapter stamps 0.1.2' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "featured geo tests passed\n";
