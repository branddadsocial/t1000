<?php
require dirname( __DIR__ ) . '/deploy/cursor/bds-customer-hub-lib.php';

$failed = 0;
function hub_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$pro = bds_hub_membership_view(
	'pro',
	array(
		'label'                   => 'Business Pro',
		'max_connects'            => 2,
		'account_control_draft'   => true,
		'account_control_publish' => false,
		'network_discount'        => true,
	)
);
hub_expect( 'Business Pro' === $pro['label'], 'pro label comes from the existing tier record' );
hub_expect( false === $pro['upgrade'], 'pro is not pushed to upgrade' );
hub_expect( in_array( 'Save listing drafts', $pro['included'], true ), 'draft cap is shown' );
hub_expect( ! in_array( 'Publish from the account desk', $pro['included'], true ), 'unpublished cap stays hidden' );

$guest = bds_hub_membership_view( 'made-up', array() );
hub_expect( 'guest' === $guest['tier'], 'unknown tier falls back to guest' );

$empty = bds_hub_free_ai_view( null );
hub_expect( 'unavailable' === $empty['state'], 'missing ledger is unavailable' );
hub_expect( false === strpos( $empty['lines'][0], '5 remaining' ), 'missing ledger does not invent a balance' );

$ready = bds_hub_free_ai_view(
	array(
		'period' => '2026-11',
		'tools'  => array(
			array( 'label' => 'Website audit', 'remaining' => 1, 'limit' => 1 ),
		),
	)
);
hub_expect( 'ready' === $ready['state'] && 'Website audit: 1 of 1 remaining' === $ready['lines'][0], 'ledger line uses supplied counts' );

$copy = bds_hub_affiliate_copy();
hub_expect( false === strpos( $copy, '%' ), 'affiliate copy does not invent a percent' );
hub_expect( false !== strpos( $copy, 'Partnero' ), 'affiliate copy names the existing portal' );

$studios = bds_hub_studio_cards();
hub_expect( 5 === count( $studios ), 'five studio boards' );
$ids = array_column( $studios, 'id' );
hub_expect( array( 'seo', 'social', 'linkedin', 'chatbot', 'video' ) === $ids, 'studio ids include chatbot and video' );
foreach ( $studios as $card ) {
	hub_expect( false !== strpos( $card['detail'], 'does not generate' ), $card['id'] . ' does not claim execution' );
}

$music = bds_hub_music_rows(
	array(
		array( 'title' => 'Music brief music-original-single', 'status' => 'Paid and in production' ),
		array( 'title' => '  ' ),
	)
);
hub_expect( 1 === count( $music ), 'blank music titles are dropped' );
hub_expect( false !== strpos( $music[0]['status'], 'Payment not collected' ), 'music status does not claim payment' );
hub_expect( false === strpos( $music[0]['status'], 'Paid' ), 'supplied payment status is discarded' );

$compare = bds_hub_membership_compare(
	array(
		'free' => array( 'label' => 'Free account' ),
		'pro'  => array( 'label' => 'Business Pro', 'account_control_draft' => true ),
		'skip' => array( 'label' => 'Not a tier' ),
	)
);
hub_expect( array( 'free', 'pro' ) === array_column( $compare, 'tier' ), 'comparison keeps the existing ladder' );
hub_expect( false === strpos( json_encode( $compare ), '$' ), 'comparison does not add a price' );

$saved = bds_hub_saved_change( array( 4, 4, 0, 'nope' ), 9, 'save' );
hub_expect( true === $saved['ok'] && array( 4, 9 ) === $saved['ids'], 'save keeps the current list and one new id' );
$removed = bds_hub_saved_change( $saved['ids'], 4, 'remove' );
hub_expect( array( 9 ) === $removed['ids'], 'remove drops only that listing' );
$full = bds_hub_saved_change( range( 1, 50 ), 99, 'save' );
hub_expect( false === $full['ok'] && 'limit' === $full['reason'], 'saved list stops at 50' );

$seo_dup = bds_seo_emit_decision( array( 'canonical' ), '/services/' );
hub_expect( false === $seo_dup['emit'] && 'canonical_exists' === $seo_dup['reason'], 'existing canonical blocks a second tag' );
$seo_city = bds_seo_emit_decision( array(), '/austin/texas/edge-rooftop/' );
hub_expect( false === $seo_city['emit'] && 'city_page' === $seo_city['reason'], 'city URL does not get a new alternate' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "customer hub tests passed\n";
