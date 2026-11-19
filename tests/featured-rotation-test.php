<?php
require dirname( __DIR__ ) . '/deploy/cursor/bds-directory-featured-ads-lib.php';

$failed = 0;
function expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$viewer = array( 'country' => 'MX', 'lat' => 20.63, 'lng' => -87.07, 'category' => 'restaurants' );
$cards  = array(
	array( 'id' => 'free-1', 'kind' => 'free', 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 20.63, 'lng' => -87.07, 'impressions' => 0 ),
	array( 'id' => 'paid-far', 'kind' => 'paid', 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 21.20, 'lng' => -86.80, 'impressions' => 0 ),
	array( 'id' => 'paid-near', 'kind' => 'paid', 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 20.64, 'lng' => -87.07, 'impressions' => 9 ),
	array( 'id' => 'trial', 'kind' => 'trial', 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 20.62, 'lng' => -87.08, 'impressions' => 1 ),
	array( 'id' => 'partner', 'kind' => 'partnership', 'level' => 'local', 'active' => true, 'country' => 'MX', 'lat' => 20.62, 'lng' => -87.06, 'impressions' => 0 ),
	array( 'id' => 'promo', 'kind' => 'promo', 'level' => 'global', 'active' => true, 'impressions' => 0 ),
);

$local = bds_feat_select( $cards, $viewer, 3 );
$ids   = array_column( $local['shown'], 'id' );
expect( array( 'paid-near', 'partner', 'trial' ) === $ids, 'paid fills first, then equal trial/partnership by impressions' );
expect( ! in_array( 'paid-far', $ids, true ), 'local card beyond 25km stays out' );
expect( 'Sponsored' === bds_feat_public_disclosure( 'paid' ), 'paid disclosure' );
expect( '' === bds_feat_public_disclosure( 'trial' ), 'trial is not labeled internally' );

$oversold = bds_feat_select(
	array(
		array( 'id' => 'p1', 'kind' => 'paid', 'level' => 'global', 'active' => true, 'impressions' => 5 ),
		array( 'id' => 'p2', 'kind' => 'paid', 'level' => 'global', 'active' => true, 'impressions' => 1 ),
		array( 'id' => 'p3', 'kind' => 'paid', 'level' => 'global', 'active' => true, 'impressions' => 1 ),
		array( 'id' => 'free', 'kind' => 'free', 'level' => 'global', 'active' => true, 'impressions' => 0 ),
	),
	$viewer,
	2
);
expect( array( 'p2', 'p3' ) === array_column( $oversold['shown'], 'id' ), 'fair rotation keeps lower-impression paid cards' );
expect( in_array( 'p1', array_column( $oversold['waitlist'], 'id' ), true ), 'extra paid card waits' );
expect( ! in_array( 'free', array_column( $oversold['shown'], 'id' ), true ), 'free does not replace a waiting paid card' );

$national = bds_feat_select(
	array(
		array( 'id' => 'us', 'kind' => 'paid', 'level' => 'national', 'active' => true, 'country' => 'US', 'impressions' => 0 ),
		array( 'id' => 'mx', 'kind' => 'paid', 'level' => 'national', 'active' => true, 'country' => 'MX', 'impressions' => 4 ),
	),
	$viewer,
	2
);
expect( array( 'mx' ) === array_column( $national['shown'], 'id' ), 'national stays inside the viewer country' );

expect( true === bds_feat_trial_decision( array( 'verified' => 1, 'claimed' => 1 ) )['ok'], 'eligible trial' );
expect( false === bds_feat_trial_decision( array( 'verified' => 1, 'claimed' => 1, 'trial_used' => 1 ) )['ok'], 'one trial only' );
expect( false === bds_feat_trial_decision( array( 'verified' => 1, 'claimed' => 1, 'paid_active' => 1 ) )['ok'], 'paid agreement is left alone' );

$reserved = array();
for ( $i = 0; $i < 3; $i++ ) {
	$reserved[] = array( 'level' => 'global', 'status' => 'pending_payment' );
}
expect( false === bds_feat_has_capacity( $reserved, 'global' ), 'pending paid rows fill the global cap' );
$reserved[0]['status'] = 'revoked';
expect( true === bds_feat_has_capacity( $reserved, 'global' ), 'revoked rows free a slot' );

$grant = array( 'id' => 'g1', 'status' => 'active', 'impressions' => 2, 'clicks' => 0 );
$once  = bds_feat_apply_event( $grant, array( 'type' => 'impression', 'event_id' => 'event1234' ) );
expect( true === $once['ok'] && 3 === $once['grant']['impressions'], 'one real impression increments by one' );
$again = bds_feat_apply_event( $once['grant'], array( 'type' => 'impression', 'event_id' => 'event1234' ) );
expect( false === $again['ok'] && 3 === $again['grant']['impressions'], 'the same event is not counted twice' );
$fake  = bds_feat_apply_event( $grant, array( 'type' => 'impression', 'event_id' => 'event5678', 'total' => 1000 ) );
expect( false === $fake['ok'] && 2 === $fake['grant']['impressions'], 'a client total is rejected' );
$idle  = bds_feat_apply_event( array( 'status' => 'pending_payment', 'impressions' => 0 ), array( 'type' => 'click', 'event_id' => 'click1234' ) );
expect( false === $idle['ok'] && 0 === $idle['grant']['impressions'], 'an unpaid placement does not record a click' );

$empty = bds_feat_empty_copy( 'local' );
expect( 'Local Featured' === $empty['label'] && '9.99' === $empty['price'], 'empty local lane keeps the catalog price' );
expect( 'Get featured' === $empty['cta'], 'empty lane sells featured' );
expect( '' === bds_feat_empty_copy( 'nope' )['price'], 'unknown level has no price' );
expect( 'bds-feat-global' === bds_feat_product_sku( 'global' ), 'sku is allowlisted from the level key' );
expect( '' === bds_feat_product_sku( 'nope' ), 'unknown level has no sku' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
fwrite( STDOUT, "featured rotation tests passed\n" );
