<?php
/**
 * Stubs enough WordPress for the revenue MU option helper and offer allowlist.
 */
define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function is_admin() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function is_front_page() { return false; }
function is_page() { return false; }
function is_product() { return false; }
function home_url( $path = '' ) { return 'https://branddad.social' . $path; }
function admin_url( $path = '' ) { return 'https://branddad.social/wp-admin/' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return $s; }
function esc_html__( $s ) { return $s; }
function esc_html_e( $s ) { echo esc_html( $s ); }
function __( $s ) { return $s; }
function wp_nonce_field() {}
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', trim( (string) $s, '-' ) ) ); }
function wp_unslash( $s ) { return $s; }
function add_query_arg( $key, $val, $url ) { return $url . '?' . $key . '=' . rawurlencode( (string) $val ); }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_json_encode( $s ) { return json_encode( $s ); }
function bds_ai_commercial_checkout_url( $slug ) {
	return 'ai-social-growth' === $slug ? 'https://branddad.social/product/ai-social-growth/' : '';
}
function bds_po_url( $url ) {
	$url = (string) $url;
	if ( false === strpos( $url, '/product/' ) ) {
		return $url;
	}
	return $url . '?ref=house';
}

require dirname( __DIR__ ) . '/deploy/cursor/bds-revenue-sections-mu.php';

$failed = 0;
function rev_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$html = bds_rev_music_form( bds_rev_music_packages() );
rev_expect( false !== strpos( $html, 'value="original-single"' ), 'array packages still render option values' );
rev_expect( false !== strpos( $html, 'Original Single' ), 'array packages still render option labels' );
rev_expect( false === strpos( $html, '>Array<' ), 'package select is not the word Array' );

$html2 = bds_rev_music_form();
rev_expect( false !== strpos( $html2, 'value="ep-3"' ), 'empty args still render the catalog' );

$hit = bds_rev_offer_by_slug( 'ai-business-monitoring' );
rev_expect( is_array( $hit ) && 'AI Business Monitoring' === $hit['name'], 'known offer slug resolves' );
rev_expect( null === bds_rev_offer_by_slug( 'not-a-real-offer' ), 'unknown offer slug is rejected' );
rev_expect( null === bds_rev_offer_by_slug( '<script>x</script>' ), 'markup slug is rejected' );
rev_expect( is_array( bds_rev_offer_by_slug( 'featured-local' ) ), 'featured inquiry slug is allowlisted' );

list( $live, $ask ) = bds_rev_split_offer_cards();
rev_expect( false !== strpos( $live, 'Subscribe monthly' ), 'live cards use Subscribe monthly' );
rev_expect( false === strpos( $live, 'Subscribe now' ), 'live cards no longer say Subscribe now' );
rev_expect( false !== strpos( $live, '/product/ai-social-growth/' ), 'live Social Growth goes to the published product' );
rev_expect( false !== strpos( $live, 'ref=house' ), 'live Subscribe link keeps the affiliate ref' );
rev_expect( false === strpos( $live, 'Ask about this plan' ), 'live grid does not mix inquiry buttons' );
rev_expect( false !== strpos( $ask, 'Ask about this plan' ), 'closed plans stay in the inquiry grid' );
rev_expect( false !== strpos( $ask, 'ai-business-monitoring' ), 'monitoring stays inquiry' );
$pos_live = strpos( bds_rev_markup(), 'Plans you can buy now' );
$pos_ask  = strpos( bds_rev_markup(), 'Upcoming plans' );
rev_expect( false !== $pos_live && false !== $pos_ask && $pos_live < $pos_ask, 'buyable plans render before inquiry' );
rev_expect( false !== strpos( bds_rev_markup(), '/featured-advertising/' ), 'Directory featured door is present' );

$strip = bds_rev_home_strip();
rev_expect( false !== strpos( $strip, 'id="bds-rev-home-strip"' ), 'homepage strip has a stable id' );
rev_expect( false !== strpos( $strip, 'Subscribe to' ), 'homepage strip sells live checkout' );
rev_expect( false !== strpos( $strip, '/product/ai-social-growth/' ), 'homepage strip uses the published Social Growth URL' );
rev_expect( false !== strpos( $strip, '/featured-advertising/' ), 'homepage strip sells featured' );
rev_expect( false !== strpos( $strip, 'List my business free' ), 'homepage strip uses the free-listing label' );
$guide = bds_rev_guide_next();
rev_expect( false !== strpos( $guide, 'id="bds-rev-guide-next"' ), 'guides get a next step' );
rev_expect( false !== strpos( $guide, 'List my business free' ), 'the guide next step lists for free' );
rev_expect( false !== strpos( $guide, '$29.99' ), 'the guide next step names the real price' );
rev_expect( false === strpos( strtolower( $guide ), 'guarantee' ), 'the guide next step does not guarantee results' );
rev_expect( true === bds_rev_path_needs_guide( 'creator-services' ), 'creator services get a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'ai-ads' ), 'AI Ads gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'spotify-promotion' ), 'Spotify promotion gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'affiliate-program' ), 'the affiliate page gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'learning-center' ), 'the learning center gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'start-a-campaign' ), 'start a campaign gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'help' ), 'help gets a next step' );
rev_expect( false === bds_rev_path_needs_guide( 'studio-boards' ), 'studio boards keep their own next step' );
rev_expect( false === bds_rev_path_needs_guide( 'studio' ), 'studio keeps its own next step' );
rev_expect( false === bds_rev_path_needs_guide( 'account-hub' ), 'account hub keeps its own next step' );
rev_expect( true === bds_rev_path_needs_guide( 'seo' ), 'seo leftover URLs get a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'privacy-policy' ), 'privacy policy gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'terms-conditions' ), 'terms get a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'press' ), 'press gets a next step' );
rev_expect( true === bds_rev_path_needs_guide( 'refund_returns' ), 'refund returns get a next step' );
rev_expect( home_url( '/privacy-policy/' ) === bds_rev_alias_dest( 'cookie-policy' ), 'cookie leftover URLs open privacy' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'sitemap' ), 'dead sitemap URLs open the services catalog' );
rev_expect( home_url( '/help/' ) === bds_rev_alias_dest( 'faqs' ), 'dead Social faqs URLs open help' );
rev_expect( home_url( '/help/' ) === bds_rev_alias_dest( 'faq' ), 'dead Social faq URLs open help' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'careers' ), 'dead Social careers URLs open contact' );
rev_expect( home_url( '/help/' ) === bds_rev_alias_dest( 'how-it-works' ), 'dead Social how-it-works URLs open help' );
rev_expect( home_url( '/help/' ) === bds_rev_alias_dest( 'support' ), 'dead Social support URLs open help' );
rev_expect( home_url( '/about-us/' ) === bds_rev_alias_dest( 'team' ), 'dead Social team URLs open about' );
rev_expect( 'https://directory.branddad.social/all-listings/?directory_type=jobs-careers' === bds_rev_alias_dest( 'jobs' ), 'dead Social jobs URLs open Directory jobs' );
rev_expect( 'https://directory.branddad.social/all-listings/?directory_type=classifieds' === bds_rev_alias_dest( 'classifieds' ), 'dead Social classifieds URLs open Directory classifieds' );
rev_expect( 'https://directory.branddad.social/featured-advertising/' === bds_rev_alias_dest( 'get-featured' ), 'dead Social get-featured URLs open Featured' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'partners' ), 'dead Social partners URLs open contact' );
rev_expect( 'https://affiliates.branddad.social/program' === bds_rev_alias_dest( 'affiliates' ), 'dead Social affiliates URLs open the live affiliate program' );
rev_expect( 'https://directory.branddad.social/registration/' === bds_rev_alias_dest( 'signup' ), 'dead Social signup URLs open Directory registration' );
rev_expect( 'https://directory.branddad.social/claim-your-business-listing-branddad-directory/' === bds_rev_alias_dest( 'claim' ), 'dead Social claim URLs open Directory claim' );
rev_expect( in_array( 'directory.branddad.social', bds_rev_allowed_redirect_hosts( array( 'branddad.social' ) ), true ), 'Social leftover doors may open Directory' );
rev_expect( in_array( 'affiliates.branddad.social', bds_rev_allowed_redirect_hosts( array( 'branddad.social' ) ), true ), 'Social leftover affiliate URLs may open the affiliate host' );
rev_expect( '' === bds_rev_alias_dest( 'help' ), 'the live help page is not redirected' );
rev_expect( '' === bds_rev_alias_dest( 'contact-us' ), 'the live contact page is not redirected' );
rev_expect( false === bds_rev_path_needs_guide( 'shop' ), 'the shop archive uses the live plan strip' );
rev_expect( false === bds_rev_path_needs_guide( 'pricing' ), 'the missing pricing page is not a destination' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'pricing' ), 'pricing opens the services catalog' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'music' ), '#branddad-music' ), 'music opens the brief' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'ai-packages' ), 'services' ), 'package aliases open services' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'complete-ai-starter' ), 'offer=complete-ai-starter' ), 'staged packages open an inquiry' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'seo' ), 'seo leftover URLs open the services catalog' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'ai-seo-pro' ), 'offer=ai-seo-pro' ), 'staged SEO Autopilot opens an inquiry' );
rev_expect( '' === bds_rev_alias_dest( 'services' ), 'real catalog URLs are not redirected' );
rev_expect( false === bds_rev_path_needs_guide( 'my-account/orders' ), 'the account area uses the onboarding screen' );
rev_expect( true === bds_rev_path_needs_onboard( 'my-account/orders' ), 'the account area gets the onboarding screen' );
rev_expect( false === bds_rev_path_needs_onboard( 'services' ), 'the services page keeps its own catalog' );
rev_expect( false === bds_rev_path_needs_guide( 'checkout' ), 'checkout is left to the order' );
rev_expect( false === bds_rev_path_needs_guide( 'product/ai-business-essentials' ), 'a product page keeps its own explanation' );
rev_expect( false === strpos( $guide, 'Un listado básico es gratis' ), 'without the switcher, the guide next step stays English' );
$_SERVER['HTTP_HOST'] = 'branddad.social';
if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $s ) { return (string) $s; }
}
if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl() { return true; }
}
if ( ! function_exists( 'bds_lang_pair' ) ) {
	require dirname( __DIR__ ) . '/deploy/cursor/bds-lang-switch-mu.php';
}
$guide_es = bds_rev_guide_next();
rev_expect( false !== strpos( $guide_es, 'Un listado básico es gratis' ) && false !== strpos( $guide_es, 'data-bds-copy="es"' ), 'Spanish guide copy is tagged for the switcher' );
rev_expect( false !== strpos( $guide_es, 'A Directory listing is free' ), 'English guide copy stays in the HTML' );
list( $live_es, $ask_es ) = bds_rev_split_offer_cards();
rev_expect( false !== strpos( $live_es, 'Suscribirse cada mes' ) && false !== strpos( $live_es, 'data-bds-copy-es=' ), 'live Subscribe monthly Spanish is tagged' );
rev_expect( false !== strpos( $ask_es, 'Preguntar por este plan' ) && false !== strpos( $ask_es, 'data-bds-copy-es=' ), 'inquiry Ask about this plan Spanish is tagged' );
$strip_es = bds_rev_home_strip();
rev_expect( false !== strpos( $strip_es, 'Subscribe abre solo si el producto' ) && false !== strpos( $strip_es, 'data-bds-copy="es"' ), 'Spanish homepage strip is tagged' );
rev_expect( false !== strpos( $strip_es, 'Suscribirse a' ) && false !== strpos( $strip_es, 'data-bds-copy-es=' ), 'homepage Subscribe-to labels are tagged Spanish' );
$music_es = bds_rev_music_form();
rev_expect( false !== strpos( $music_es, 'Enviar el brief creativo' ) && false !== strpos( $music_es, 'data-bds-copy-es=' ), 'music brief submit Spanish is tagged' );
rev_expect( false !== strpos( $music_es, 'Descripción del negocio' ), 'music form labels keep inner-span Spanish' );
rev_expect( false === strpos( $music_es, '<label data-bds-copy-en' ), 'music labels wrapping inputs are not swapped as a whole' );
$markup_es = bds_rev_markup();
rev_expect( false !== strpos( $markup_es, 'Planes que puede comprar ahora' ) && false !== strpos( $markup_es, 'data-bds-copy-es=' ), 'revenue buy-now heading Spanish is tagged' );
rev_expect( false !== strpos( $markup_es, 'Planes próximos — solo consulta' ), 'revenue inquiry heading Spanish is tagged' );
rev_expect( false !== strpos( $markup_es, 'El plan diario principal' ) && false !== strpos( $markup_es, 'data-bds-copy="es"' ), 'Essentials who Spanish is tagged' );
$onboard_es = bds_rev_after_order_html();
rev_expect( false !== strpos( $onboard_es, 'Un listado del directorio es gratis' ) && false !== strpos( $onboard_es, 'data-bds-copy="es"' ), 'Spanish onboarding copy is tagged' );
$thanks = bds_rev_after_order_html();
rev_expect( true === bds_rev_is_order_received( 'checkout/order-received/99' ), 'the thank-you page is recognized' );
rev_expect( false === bds_rev_is_order_received( 'checkout' ), 'the payment page is not the thank-you page' );
rev_expect( false !== strpos( $thanks, 'List my business free' ), 'the thank-you page offers the free listing' );
rev_expect( false !== strpos( $thanks, 'does not start another charge' ), 'the thank-you page does not start another charge' );
rev_expect( false !== strpos( $thanks, '7-day trial' ), 'the thank-you page explains the trial' );
rev_expect( false !== strpos( $thanks, 'Directory membership is not included' ), 'onboarding does not grant Directory membership' );
rev_expect( false !== strpos( $thanks, 'min-height:44px' ), 'the onboarding buttons meet the phone tap size' );
rev_expect( false === strpos( strtolower( $thanks ), 'no card' ), 'the onboarding screen does not say no card' );
rev_expect( false === strpos( $strip, 'Ask about this plan' ), 'homepage strip does not mix inquiry CTAs' );

$live_note = bds_rev_offer_note_html( 'ai-social-growth' );
rev_expect( false !== strpos( $live_note, 'Subscribe monthly' ), 'contact note sells a live plan' );
rev_expect( false !== strpos( $live_note, '/product/ai-social-growth/' ), 'contact note uses the published product URL' );
rev_expect( false === strpos( $live_note, 'does not start checkout' ), 'live contact note is not inquiry-only' );
$ask_note = bds_rev_offer_note_html( 'ai-business-monitoring' );
rev_expect( false !== strpos( $ask_note, 'does not start checkout' ), 'staged contact note stays inquiry' );
rev_expect( false === strpos( $ask_note, 'Subscribe monthly' ), 'staged contact note does not invent checkout' );
$quote_note = bds_rev_offer_note_html( 'done-for-you-ai' );
rev_expect( false !== strpos( $quote_note, 'quote-only' ), 'Done-for-You stays quote-only' );
rev_expect( false === strpos( $quote_note, 'Subscribe monthly' ), 'quote-only contact note does not invent checkout' );
rev_expect( false === strpos( strtolower( $quote_note ), 'no card' ), 'quote-only contact note does not say no card' );
rev_expect( '' === bds_rev_offer_note_html( '<script>x</script>' ), 'markup offer notes print nothing' );
rev_expect(
	'https://directory.branddad.social/featured-advertising/' === bds_rev_menu_featured_url( 'Get Featured', 'https://directory.branddad.social/' ),
	'header Get Featured is sent to the Featured buy page'
);
rev_expect(
	'https://directory.branddad.social/get-featured/' === bds_rev_menu_featured_url( 'Get Featured', 'https://directory.branddad.social/get-featured/' ),
	'Verified pitch /get-featured/ is left alone'
);
rev_expect(
	'https://directory.branddad.social/' === bds_rev_menu_featured_url( 'Directory', 'https://directory.branddad.social/' ),
	'Directory browse label is not rewritten'
);
rev_expect(
	'https://directory.branddad.social/featured-advertising/' === bds_rev_menu_featured_url( 'Get Featured', 'https://branddad.social/contact-us/' ),
	'header Get Featured contact link is sent to Featured'
);
rev_expect(
	'https://branddad.social/services/' === bds_rev_menu_packages_url( 'AI Packages', 'https://branddad.social/' ),
	'AI Packages leaves the homepage and opens services'
);
rev_expect(
	'https://branddad.social/services/' === bds_rev_menu_packages_url( 'AI Packages', 'https://branddad.social/services/' ),
	'AI Packages that already open services stay there'
);
rev_expect(
	'https://branddad.social/services/' === bds_rev_packages_href( 'https://branddad.social/#branddad-ai-solutions' ),
	'AI Packages homepage hash opens the services catalog'
);
rev_expect(
	'https://branddad.social/blog/' === bds_rev_packages_href( 'https://branddad.social/blog/' ),
	'other Social paths stay put'
);
$choose = '<a class="bdai-button" href="https://branddad.social/product/ai-social-growth/">Choose this plan</a>';
rev_expect( false !== strpos( bds_rev_rewrite_choose_plan( $choose ), '>Subscribe monthly</a>' ), 'live plan cards say Subscribe monthly' );
$now = '<a href="https://branddad.social/product/ai-social-growth/">Subscribe now</a>';
rev_expect( false !== strpos( bds_rev_rewrite_subscribe_now( $now ), '>Subscribe monthly</a>' ), 'Subscribe now on a product becomes Subscribe monthly' );
rev_expect( false === strpos( bds_rev_rewrite_subscribe_now( $now ), 'Subscribe now' ), 'product Subscribe now leftover is removed' );
$see = '<a href="https://directory.branddad.social/select-your-plan/">See plans</a>';
rev_expect( false !== strpos( bds_rev_rewrite_see_plans( $see ), '>See membership plans</a>' ), 'leftover See plans on Select your plan becomes See membership plans' );
rev_expect( false === strpos( bds_rev_rewrite_see_plans( $see ), '>See plans</a>' ), 'leftover See plans label is removed' );
$other = '<a href="https://branddad.social/product/branddad-custom-automation/">Choose this plan</a>';
rev_expect( $other === bds_rev_rewrite_choose_plan( $other ), 'other product labels stay on their own wording' );
$ask_html = '<p><a class="bdai-button bdai-button--light" href="https://branddad.social/contact-us/">Ask about Featured placement</a></p>';
$fixed    = bds_rev_rewrite_featured_ask( $ask_html );
rev_expect( false !== strpos( $fixed, '/featured-advertising/' ), 'Featured ask is sent to the buy page' );
rev_expect( false !== strpos( $fixed, '>Get featured</a>' ), 'Featured ask label becomes Get featured' );
rev_expect( false === strpos( $fixed, 'Ask about Featured placement' ), 'old Featured contact CTA is removed' );
$plain = '<p>Contact us</p>';
rev_expect( $plain === bds_rev_rewrite_featured_ask( $plain ), 'unrelated contact copy is left alone' );
$rel = '<a href="/contact-us/">Ask about Featured placement</a>';
rev_expect( false !== strpos( bds_rev_rewrite_featured_ask( $rel ), '/featured-advertising/' ), 'relative Featured ask is rewritten' );
$studio = '<a class="bdai-text-link" href="https://branddad.social/services/#ai-social-growth">See social plans</a>';
$studio_out = bds_rev_rewrite_conversion_ctas( $studio );
rev_expect( false !== strpos( $studio_out, '/product/ai-social-growth/' ), 'See social plans goes to the live product' );
rev_expect( false !== strpos( $studio_out, '>Subscribe to Social Growth</a>' ), 'See social plans becomes Subscribe' );
rev_expect( false === strpos( $studio_out, 'See social plans' ), 'old social hash CTA is removed' );
$seo = '<a href="https://branddad.social/services/#ai-local-seo">See SEO options</a>';
rev_expect( $seo === bds_rev_rewrite_conversion_ctas( $seo ), 'SEO hash stays until that product URL exists' );
$explain = bds_rev_product_plain( 'ai-business-essentials' );
rev_expect( false !== strpos( $explain, '$29.99 a month' ), 'Essentials explains the real price' );
rev_expect( false !== strpos( $explain, '7-day trial' ), 'Essentials keeps the real trial' );
rev_expect( false === strpos( strtolower( $explain ), 'no card' ), 'Essentials does not say no card' );
rev_expect( false === strpos( strtolower( $explain ), 'guarantee' ), 'Essentials does not guarantee results' );
rev_expect( false !== strpos( $explain, 'Ayuda de negocio por $29.99 al mes' ) && false !== strpos( $explain, 'data-bds-copy="es"' ), 'Essentials product Spanish is tagged' );
rev_expect( false !== strpos( $explain, 'Qué es esto' ), 'product section headings are tagged Spanish' );
rev_expect( '' === bds_rev_product_plain( 'complete-ai-pro' ), 'a staged package gets no product pitch' );
rev_expect( '' === bds_rev_product_plain( 'not-a-product' ), 'an unknown product gets no pitch' );
ob_start();
bds_rev_package_link_script();
$nav = ob_get_clean();
rev_expect( false !== strpos( $nav, '#menu-item-41544' ), 'Get Featured menu is corrected after header scripts' );
rev_expect( false !== strpos( $nav, 'featured-advertising' ), 'Get Featured menu points at the buy page' );
rev_expect( false === strpos( $nav, 'get-featured' ), 'the Verified pitch URL is left alone' );
$dup = '<h1 class="entry-title">About Us</h1><h1>Practical growth systems, clearly explained.</h1>';
$dup_out = bds_rev_strip_theme_entry_title_h1( $dup );
rev_expect( false === strpos( $dup_out, '<h1 class="entry-title">About Us</h1>' ), 'theme About Us is not a second H1' );
rev_expect( false !== strpos( $dup_out, '<h1>Practical growth systems, clearly explained.</h1>' ), 'the real about heading stays' );
$rev_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-revenue-sections-mu.php' );
rev_expect( false !== strpos( $rev_src, 'Version: 0.2.34' ), 'revenue MU stamps 0.2.34' );
rev_expect( false !== strpos( $rev_src, 'bds_rev_allowed_redirect_hosts' ), 'Social leftover doors may open Directory' );
rev_expect( false === strpos( $rev_src, 'wp_get_referer' ), 'music brief redirect stays on Social home' );
rev_expect( false !== strpos( $rev_src, "home_url( '/?bds_music=saved' )" ), 'a saved brief returns to Social home' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} revenue section tests failed\n" );
	exit( 1 );
}
echo "revenue section tests passed\n";
