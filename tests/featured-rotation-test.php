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
expect( 'local' === bds_feat_valid_lane( 'local' ), 'known featured lane is kept' );
expect( 'global' === bds_feat_valid_lane( 'GLOBAL' ), 'featured lane is case-insensitive' );
expect( '' === bds_feat_valid_lane( 'checkout' ), 'unknown featured lane fails closed' );
expect( '' === bds_feat_valid_lane( '<script>local</script>' ), 'markup is not a featured lane' );
expect( 'bds-feat-global' === bds_feat_product_sku( 'global' ), 'sku is allowlisted from the level key' );
expect( '' === bds_feat_product_sku( 'nope' ), 'unknown level has no sku' );
expect( 17596 === bds_feat_parse_listing_id( '17596' ), 'digit listing ids are accepted' );
expect( 0 === bds_feat_parse_listing_id( 'https://directory.branddad.social/listing/x/' ), 'listing URLs are not fetched or parsed as ids' );
expect( 0 === bds_feat_parse_listing_id( '<script>1</script>' ), 'markup is not a listing id' );
expect( 0 === bds_feat_parse_listing_id( '-12' ), 'negative listing ids are rejected' );
expect( true === bds_feat_path_needs_door( 'help' ), 'the help page gets a listing door' );
expect( true === bds_feat_path_needs_door( 'blog/how-to-get-found' ), 'a Directory guide gets a listing door' );
expect( false === bds_feat_path_needs_door( '' ), 'the homepage keeps its own door' );
expect( false === bds_feat_path_needs_door( 'add-listing' ), 'add-listing keeps its own door' );
expect( false === bds_feat_path_needs_door( 'single-category/coffee-shops' ), 'category pages keep their own intro' );
expect( true === bds_feat_path_needs_door( 'events' ), 'events gets a listing door' );
expect( true === bds_feat_path_needs_door( 'jobs' ), 'jobs gets a listing door' );
expect( true === bds_feat_path_needs_door( 'classifieds' ), 'classifieds gets a listing door' );
expect( true === bds_feat_path_needs_door( 'memberships' ), 'memberships gets a listing door' );
expect( true === bds_feat_path_needs_door( 'blog' ), 'blog gets a listing door' );
expect( true === bds_feat_path_needs_door( 'about-us' ), 'about-us gets a listing door' );
expect( true === bds_feat_path_needs_door( 'terms-and-conditions' ), 'terms get a listing door' );
expect( true === bds_feat_path_needs_door( 'faq' ), 'faq leftover URLs get a listing door' );
expect( 'events' === bds_feat_directory_surface( 'events' ), 'events surface is recognized' );
expect( 'jobs' === bds_feat_directory_surface( 'jobs-careers' ), 'jobs-careers maps to jobs' );
expect( 'classifieds' === bds_feat_directory_surface( 'classifieds' ), 'classifieds surface is recognized' );
expect( 'memberships' === bds_feat_directory_surface( 'plans' ), 'plans maps to memberships' );
expect( 'blog' === bds_feat_directory_surface( 'blog' ), 'blog surface is recognized' );
expect( 'gifts' === bds_feat_directory_surface( 'gift-card-marketplace' ), 'gift marketplace maps to gifts' );
expect( 'travel' === bds_feat_directory_surface( 'flights' ), 'flights maps to travel' );
expect( 'travel' === bds_feat_listing_type_surface( 'travel' ), 'all-listings travel type is recognized' );
expect( 'travel' === bds_feat_listing_type_surface( 'flights' ), 'all-listings flights type uses travel copy' );
expect( 'hotels' === bds_feat_listing_type_surface( 'hotels-stays' ), 'all-listings hotels type is recognized' );
expect( 'gifts' === bds_feat_listing_type_surface( 'gift-cards' ), 'all-listings gift-cards type uses marketplace copy' );
expect( 'gifts' === bds_feat_listing_type_surface( 'gift-card-marketplace' ), 'all-listings gift marketplace type uses marketplace copy' );
expect( '' === bds_feat_listing_type_surface( '<script>' ), 'unsafe directory types fail closed' );
$feat_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-directory-featured-ads-mu.php' );
expect( false !== strpos( $feat_src, 'Version: 0.2.34' ), 'featured MU stamps 0.2.34' );
expect( false !== strpos( $feat_src, 'bds-feat-menu-clear' ), 'Directory header menus are laid out' );
expect( false !== strpos( $feat_src, '.bdd-mobile-menu' ), 'mobile hamburger is a separate control' );
expect( false !== strpos( $feat_src, '@media(max-width:900px)' ), 'desktop nav hides on small screens so it does not sit on the hamburger' );
expect( false !== strpos( $feat_src, 'bds-feat-home-legacy' ), 'homepage unhides the Directory Experience heading' );
expect( false !== strpos( $feat_src, 'bdd-title' ), 'the parked #bdd-title heading is restored' );
expect( false !== strpos( $feat_src, 'bds-dfc-gift' ), 'leftover homepage gift markup is removed' );
expect( false !== strpos( $feat_src, 'bds_feat_home_legacy_buffer_start' ), 'homepage leftover is stripped from HTML, not only CSS' );
$home_html = '<style>body.home #bdd-title{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}</style>'
	. '<h1 id="bdd-title">Find What You Need Near You—Fast.</h1>'
	. '<section id="bds-wa-hero" class="bds-wah"><p class="bds-wah__title" hidden>Find a business. See what it offers. Contact it.</p></section>'
	. '<section id="bds-dfc-gift" class="bds-gsl" data-bds-gsl="1.0.9"><h2 class="bds-gsl__title" id="bds-gsl-title">Brand gift cards under face value</h2></section>'
	. '<script id="bds-dfc-gift-place-js">void 0</script>'
	. '<h2 id="bdd-area-title">Start With Your Location</h2>'
	. '<a href="/gift-card-marketplace/">Shop gift cards</a>';
$home_out = bds_feat_rewrite_home_legacy_html( $home_html );
expect( false !== strpos( $home_out, 'id="bdd-title"' ), 'current Directory Experience heading stays' );
expect( false !== strpos( $home_out, 'position:static' ), 'parked heading CSS is unhidden' );
expect( false === strpos( $home_out, 'left:-9999px' ), 'off-screen heading CSS is gone' );
expect( false === strpos( $home_out, 'Brand gift cards under face value' ), 'homepage gift inventory heading is removed' );
expect( false === strpos( $home_out, 'id="bds-dfc-gift"' ), 'homepage gift section is removed' );
expect( false === strpos( $home_out, 'id="bds-wa-hero"' ), 'retired WhatsApp hero is removed when current heading exists' );
expect( false !== strpos( $home_out, 'gift-card-marketplace' ), 'gift marketplace link stays' );
$keep_hero = bds_feat_rewrite_home_legacy_html( '<section id="bds-wa-hero"><h1 id="bdd-title">Find What You Need Near You—Fast.</h1></section>' );
expect( false !== strpos( $keep_hero, 'id="bds-wa-hero"' ), 'WhatsApp wrapper stays if it already holds the current heading' );
expect( false === strpos( bds_feat_rewrite_home_legacy_html( '' ), 'bds-dfc-gift' ), 'empty HTML stays empty' );
expect( '<div id="x">keep</div>' === bds_feat_strip_tag_by_id( '<div id="x">keep</div>', 'section', 'missing' ), 'missing ids are left alone' );
expect( false !== strpos( $feat_src, 'Subscribe monthly</a>' ), 'leftover Subscribe now is rewritten to Subscribe monthly' );
expect( false !== strpos( $feat_src, ':focus-visible' ), 'featured buttons keep a visible focus ring' );
expect( false !== strpos( $feat_src, 'Destacados en todo el mundo' ), 'empty worldwide lane heading has Spanish' );
expect( false !== strpos( $feat_src, 'Listar mi negocio gratis' ), 'door CTAs have Spanish' );
expect( false !== strpos( $feat_src, 'Subscribe monthly' ), 'featured checkout uses Subscribe monthly' );
expect( false === strpos( $feat_src, '>Subscribe now</a>' ), 'featured checkout no longer says Subscribe now' );
expect( false !== strpos( $feat_src, 'data-bds-copy-en="Subscribe monthly"' ), 'leftover Subscribe now swap attrs are rewritten' );
expect( false !== strpos( $feat_src, '#bds-mt-plans' ), 'plans-page See plans jump is rewritten' );
expect( false !== strpos( $feat_src, 'See membership plans</a>' ), 'leftover See plans is rewritten to See membership plans' );
expect( false !== strpos( $feat_src, 'does not sell tickets' ), 'events copy does not sell tickets' );
expect( false !== strpos( $feat_src, 'does not sell tickets or book flights' ), 'travel copy does not sell tickets' );
expect( false !== strpos( $feat_src, 'does not take that payment here' ), 'gift cards stay a marketplace' );
expect( false !== strpos( $feat_src, 'Sign in to list free' ), 'sign-in still offers the free listing' );
expect( false !== strpos( $feat_src, 'This search does not sell a listing' ), 'empty search does not sell a listing' );
expect( false !== strpos( $feat_src, 'bds-feat-hide-refund' ), 'demo refund modal is hidden on public Directory pages' );
expect( false !== strpos( $feat_src, 'directory_type=classifieds' ), 'classifieds alias opens the classifieds directory type' );
expect( false !== strpos( $feat_src, 'bds_feat_memberships_alias' ), 'membership leftover URLs open Select your plan' );
expect( false !== strpos( $feat_src, "array( 'memberships', 'plans' )" ), 'live Directory /membership/ is not redirected' );
expect( false !== strpos( $feat_src, 'bds_feat_help_alias' ), 'faq and sitemap leftover URLs open Directory help' );
expect( false !== strpos( $feat_src, "'how-it-works'" ), 'how-it-works leftover URLs open Directory help' );
expect( false !== strpos( $feat_src, "'faqs'" ), 'Directory faqs leftover URLs open help' );
expect( false !== strpos( $feat_src, "'careers'" ), 'Directory careers leftover URLs open help' );
expect( false !== strpos( $feat_src, "'support'" ), 'Directory support leftover URLs open help' );
expect( false !== strpos( $feat_src, "'help-center'" ), 'Directory help-center leftover URLs open help' );
expect( false !== strpos( $feat_src, "home_url( '/about-us/' )" ), 'Directory team leftover URLs open about' );
expect( false !== strpos( $feat_src, "home_url( '/featured-advertising/' )" ), 'Directory /featured/ leftover opens the buy page' );
expect( false !== strpos( $feat_src, 'bds_feat_leftover_dest' ), 'more leftover Directory URLs have a dest map' );
expect( false !== strpos( $feat_src, "'advertise-with-us'" ), 'Directory advertise-with-us leftover opens Featured' );
expect( false !== strpos( $feat_src, "home_url( '/gift-card-marketplace/' )" ), 'Directory gifts leftover opens the gift marketplace' );
expect( false !== strpos( $feat_src, "'discount-gift-cards'" ), 'Directory discount-gift-cards leftover opens the gift marketplace' );
expect( false !== strpos( $feat_src, "home_url( '/sell-gift-card/' )" ), 'Directory sell-gift-cards leftover opens the live seller door' );
expect( false !== strpos( $feat_src, "home_url( '/all-listings/' )" ), 'Directory all-locations leftover opens listings' );
expect( false !== strpos( $feat_src, "home_url( '/all-categories/' )" ), 'Directory categories leftover opens all-categories' );
expect( false !== strpos( $feat_src, "home_url( '/add-listing/' )" ), 'Directory list-your-business leftover opens add listing' );
expect( false !== strpos( $feat_src, "home_url( '/select-your-plan/' )" ), 'Directory subscribe leftover opens Select your plan' );
expect( false !== strpos( $feat_src, "'select-plan'" ), 'Directory select-plan leftover opens Select your plan' );
expect( false !== strpos( $feat_src, "'upgrade'" ), 'Directory upgrade leftover opens Select your plan' );
expect( false !== strpos( $feat_src, "home_url( '/sign-in/' )" ), 'Directory signin leftover opens sign-in' );
expect( false !== strpos( $feat_src, "home_url( '/registration/' )" ), 'Directory create-account leftover opens registration' );
expect( false !== strpos( $feat_src, "'create-listing'" ), 'Directory create-listing leftover opens add listing' );
expect( false !== strpos( $feat_src, "home_url( '/dashboard/' )" ), 'Directory my-listings leftover opens dashboard' );
expect( false !== strpos( $feat_src, "'claim-listing'" ), 'Directory claim-listing leftover opens the live claim page' );
expect( false !== strpos( $feat_src, "'featured-ads'" ), 'Directory featured-ads leftover opens Featured' );
expect( false !== strpos( $feat_src, "home_url( '/terms-and-conditions/' )" ), 'Directory legal leftover opens terms' );
expect( false !== strpos( $feat_src, "'new-listing'" ), 'Directory new-listing leftover opens add listing' );
expect( false !== strpos( $feat_src, "'claim-business'" ), 'Directory claim-business leftover opens the live claim page' );
expect( false !== strpos( $feat_src, "'elite'" ), 'Directory elite leftover opens Select your plan' );
expect( false !== strpos( $feat_src, "'businesses'" ), 'Directory businesses leftover opens listings' );
expect( false !== strpos( $feat_src, 'https://branddad.social/product/ai-business-essentials/' ), 'Directory trial leftover opens Social Essentials' );
expect( false !== strpos( $feat_src, 'bds_feat_allowed_redirect_hosts' ), 'Directory leftover trial may open Social' );
expect( false !== strpos( $feat_src, 'Membership prices on Select your plan are unchanged' ), 'membership copy does not flatten prices' );
$no_list = bds_feat_listing_checkout_prompt( 0 );
expect( false === $no_list['attached'], 'buy page without listing stays unattached' );
expect( false !== strpos( $no_list['line'], 'stays inactive' ), 'missing listing does not claim a live lane' );
$with_list = bds_feat_listing_checkout_prompt( 44 );
expect( true === $with_list['attached'] && false !== strpos( $with_list['line'], '44' ), 'buy page names the listing id' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
fwrite( STDOUT, "featured rotation tests passed\n" );
