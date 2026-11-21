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
	hub_expect( false !== strpos( $card['detail'], 'inquiry only' ), $card['id'] . ' stays inquiry only' );
	hub_expect( false !== strpos( $card['detail'], 'does not start checkout' ), $card['id'] . ' does not start checkout' );
}
$dash = bds_hub_dashboard_copy();
hub_expect( 'Your BrandDad dashboard' === $dash['heading'], 'dashboard copy has a heading' );
hub_expect( false !== strpos( json_encode( $dash ), 'does not start a charge' ), 'dashboard copy does not start a charge' );
hub_expect( false !== strpos( json_encode( $dash ), 'inquiry only' ), 'dashboard copy keeps studios as inquiry' );
$surf = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-customer-surfaces-mu.php' );
hub_expect( false !== strpos( $surf, 'Version: 0.2.9' ), 'surfaces MU stamps 0.2.9' );
hub_expect( false !== strpos( $surf, 'See membership plans' ), 'guest hub opens membership plans' );
hub_expect( false !== strpos( $surf, 'select-your-plan' ), 'guest hub See membership plans goes to Select your plan' );
hub_expect( false === strpos( $surf, '>See plans</a>' ), 'guest hub no longer says See plans' );
hub_expect( false !== strpos( $surf, 'aria-disabled="true"' ), 'studio buttons stay disabled for assistive tech' );
hub_expect( false !== strpos( $surf, ':focus-visible' ), 'hub links keep a visible focus ring' );
hub_expect( false !== strpos( $surf, 'bds-hub-dashboard' ), 'directory dashboard prints the hub band' );

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

$dir_next = bds_hub_next_actions( 'directory' );
hub_expect( 'Get featured' === $dir_next[2]['label'], 'directory hub sells featured' );
hub_expect( 'Salir destacado' === $dir_next[2]['label_es'], 'directory hub featured Spanish is present' );
hub_expect( '/featured-advertising/' === $dir_next[2]['path'], 'featured path is the buy page' );
$soc_next = bds_hub_next_actions( 'social' );
hub_expect( 'Try it free for 7 days' === $soc_next[0]['label'], 'social hub uses the approved trial label' );
hub_expect( '/product/ai-business-essentials/' === $soc_next[0]['path'], 'social hub leads with the Essentials trial' );
hub_expect( '/product/ai-social-growth/' === $soc_next[1]['path'], 'Social Growth stays a live product' );
hub_expect( false === strpos( json_encode( $soc_next ), '$' ), 'hub next actions do not invent a price' );

$seo_dup = bds_seo_emit_decision( array( 'canonical' ), '/services/' );
hub_expect( false === $seo_dup['emit'] && 'canonical_exists' === $seo_dup['reason'], 'existing canonical blocks a second tag' );
$seo_city = bds_seo_emit_decision( array(), '/austin/texas/edge-rooftop/' );
hub_expect( false === $seo_city['emit'] && 'city_page' === $seo_city['reason'], 'city URL does not get a new alternate' );

$trial_off = bds_hub_trial_view( null );
hub_expect( 'unavailable' === $trial_off['state'], 'missing trial state is unavailable' );
hub_expect( false === strpos( $trial_off['line'], '25' ), 'missing trial does not invent credits' );
$trial_ok = bds_hub_trial_view( array( 'status' => 'eligible' ) );
hub_expect( 'eligible' === $trial_ok['state'] && true === $trial_ok['cta'], 'eligible trial keeps the start CTA' );
hub_expect( false !== strpos( $trial_ok['line'], 'Directory membership is not included' ), 'trial copy does not grant Directory' );
$trial_on = bds_hub_trial_view( array( 'status' => 'active', 'active' => true, 'days_remaining' => 6 ) );
hub_expect( 'active' === $trial_on['state'] && false === $trial_on['cta'], 'active trial is not a second checkout CTA' );
hub_expect( false !== strpos( $trial_on['line'], '6 days remaining' ), 'active trial shows remaining days from state' );
$trial_paid = bds_hub_trial_view( array( 'status' => 'paid' ) );
hub_expect( 'paid' === $trial_paid['state'], 'paid Essentials is distinct from trial' );

$ops_blocked = bds_hub_ops_inspect_view( array() );
hub_expect( 'owner_blocked' === $ops_blocked['state'], 'empty snapshot is owner-blocked' );
hub_expect( false === strpos( json_encode( $ops_blocked ), 'sk_' ), 'ops inspect does not invent a secret' );
hub_expect( false === strpos( json_encode( $ops_blocked ), 'pk_live' ), 'ops inspect does not print a publishable key' );
$ops_flags = bds_hub_ops_inspect_view(
	array(
		'stripe_gateway' => true,
		'followup_flag'  => true,
	)
);
hub_expect( 'unsafe' === $ops_flags['state'], 'a mail flag without FormWise keys is unsafe' );
$ops_keys = bds_hub_ops_inspect_view(
	array(
		'formwise_exec' => true,
		'formwise_prov' => true,
		'stripe_gateway' => true,
	)
);
hub_expect( 'keys_present_flags_off' === $ops_keys['state'], 'keys without flags stay gated' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "customer hub tests passed\n";
