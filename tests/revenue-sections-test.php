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

list( $live, $ask, $quote ) = bds_rev_split_offer_cards();
rev_expect( false !== strpos( $live, 'Subscribe monthly' ), 'live cards use Subscribe monthly' );
rev_expect( false === strpos( $live, 'Subscribe now' ), 'live cards no longer say Subscribe now' );
rev_expect( false !== strpos( $live, '/product/ai-social-growth/' ), 'live Social Growth goes to the published product' );
rev_expect( false !== strpos( $live, 'ref=house' ), 'live Subscribe link keeps the affiliate ref' );
rev_expect( false === strpos( $live, 'Ask about this plan' ), 'live grid does not mix inquiry buttons' );
rev_expect( false !== strpos( $ask, 'Ask about this plan' ), 'closed plans stay in the inquiry grid' );
rev_expect( false !== strpos( $ask, 'ai-business-monitoring' ), 'monitoring stays inquiry' );
rev_expect( false === strpos( $ask, 'complete-ai-starter' ), 'Complete AI is not mixed into the inquiry grid' );
rev_expect( false !== strpos( $quote, 'Request a scoped quote' ), 'Complete AI cards request a scoped quote' );
rev_expect( false !== strpos( $quote, 'Complete AI Starter' ), 'Complete AI Starter is in the quote grid' );
rev_expect( false !== strpos( $quote, 'Done-for-You AI Management' ), 'Done-for-You is in the quote grid' );
rev_expect( false === strpos( $quote, '$79.99' ), 'Complete AI Starter has no dollar price' );
rev_expect( false === strpos( $quote, '$149.99' ), 'Complete AI Growth has no dollar price' );
rev_expect( false === strpos( $quote, '$249.99' ), 'Complete AI Pro has no dollar price' );
rev_expect( false === strpos( $quote, '$399' ), 'Done-for-You has no dollar price' );
rev_expect( false === strpos( $quote, 'Ask about this plan' ), 'quote cards do not say Ask about this plan' );
$pos_live  = strpos( bds_rev_markup(), 'Plans you can buy now' );
$pos_quote = strpos( bds_rev_markup(), 'Complete AI packages — scoped quote' );
$pos_ask   = strpos( bds_rev_markup(), 'Upcoming plans' );
rev_expect( false !== $pos_live && false !== $pos_ask && $pos_live < $pos_ask, 'buyable plans render before inquiry' );
rev_expect( false !== $pos_quote && $pos_live < $pos_quote && $pos_quote < $pos_ask, 'scoped-quote packages sit between buyable and inquiry' );
rev_expect( false !== strpos( bds_rev_markup(), '/featured-advertising/' ), 'Directory featured door is present' );

$strip = bds_rev_home_strip();
rev_expect( false !== strpos( $strip, 'id="bds-rev-home-strip"' ), 'homepage strip has a stable id' );
rev_expect( false !== strpos( $strip, 'Subscribe to' ), 'homepage strip sells live checkout' );
rev_expect( false !== strpos( $strip, '/product/ai-social-growth/' ), 'homepage strip uses the published Social Growth URL' );
rev_expect( false !== strpos( $strip, '/featured-advertising/' ), 'homepage strip sells featured' );
rev_expect( false !== strpos( $strip, 'List my business free' ), 'homepage strip uses the free-listing label' );
rev_expect( false !== strpos( $strip, 'Featured checkout is open on Directory' ), 'homepage strip says Featured checkout is live' );
rev_expect( false === strpos( $strip, 'Featured is a Directory request' ), 'homepage strip does not say Featured Woo is missing' );
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
rev_expect( home_url( '/#bdai-faq-title' ) === bds_rev_alias_dest( 'faqs' ), 'dead Social faqs URLs open the homepage FAQ' );
rev_expect( '' === bds_rev_alias_dest( 'faq' ), 'the live Social /faq/ contact redirect is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'work-with-us' ), 'the live Social /work-with-us/ hire page is not stolen' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'careers' ), 'dead Social careers URLs open contact' );
rev_expect( home_url( '/#bdai-faq-title' ) === bds_rev_alias_dest( 'how-it-works' ), 'dead Social how-it-works URLs open the homepage FAQ' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'support' ), 'dead Social support URLs open contact' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'help-center' ), 'dead Social help-center URLs open contact' );
rev_expect( '' === bds_rev_alias_dest( 'help' ), 'the live Social /help/ SEO article is not redirected' );
rev_expect( home_url( '/about-us/' ) === bds_rev_alias_dest( 'team' ), 'dead Social team URLs open about' );
rev_expect( 'https://directory.branddad.social/all-listings/?directory_type=jobs-careers' === bds_rev_alias_dest( 'jobs' ), 'dead Social jobs URLs open Directory jobs' );
rev_expect( 'https://directory.branddad.social/all-listings/?directory_type=classifieds' === bds_rev_alias_dest( 'classifieds' ), 'dead Social classifieds URLs open Directory classifieds' );
rev_expect( 'https://directory.branddad.social/featured-advertising/' === bds_rev_alias_dest( 'get-featured' ), 'dead Social get-featured URLs open Featured' );
rev_expect( 'https://directory.branddad.social/featured-advertising/' === bds_rev_alias_dest( 'advertise-with-us' ), 'dead Social advertise-with-us URLs open Featured' );
rev_expect( home_url( '/product/ai-business-essentials/' ) === bds_rev_alias_dest( 'trial' ), 'dead Social trial URLs open Essentials' );
rev_expect( home_url( '/product/ai-business-essentials/' ) === bds_rev_alias_dest( 'start-trial' ), 'dead Social start-trial URLs open Essentials' );
rev_expect( home_url( '/product/ai-business-essentials/' ) === bds_rev_alias_dest( 'essentials' ), 'dead Social essentials URLs open Essentials' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'get-started' ), 'dead Social get-started URLs open services' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'subscribe' ), 'dead Social subscribe URLs open services' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'membership' ), 'dead Social membership URLs open services' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'plans' ), 'dead Social plans URLs open services' );
rev_expect( home_url( '/my-account/' ) === bds_rev_alias_dest( 'signin' ), 'dead Social signin URLs open my-account' );
rev_expect( '' === bds_rev_alias_dest( 'login' ), 'the live Social /login/ my-account redirect is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'sign-in' ), 'the live Social /sign-in/ my-account redirect is not stolen' );
rev_expect( 'https://directory.branddad.social/registration/' === bds_rev_alias_dest( 'create-account' ), 'dead Social create-account URLs open Directory registration' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'quote' ), 'dead Social quote URLs open contact' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'inquiry' ), 'dead Social inquiry URLs open contact' );
rev_expect( home_url( '/press/' ) === bds_rev_alias_dest( 'press-kit' ), 'dead Social press-kit URLs open press' );
rev_expect( home_url( '/terms-conditions/' ) === bds_rev_alias_dest( 'legal' ), 'dead Social legal URLs open terms' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'upgrade' ), 'dead Social upgrade URLs open services' );
rev_expect( home_url( '/my-account/' ) === bds_rev_alias_dest( 'billing' ), 'dead Social billing URLs open my-account' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'donate' ), 'dead Social donate URLs open contact' );
rev_expect( home_url( '/refund_returns/' ) === bds_rev_alias_dest( 'shipping' ), 'dead Social shipping URLs open refunds' );
rev_expect( '' === bds_rev_alias_dest( 'returns' ), 'the live Social /returns/ refund page is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'register' ), 'the live Social /register/ Directory signup is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'join' ), 'the live Social /join/ marketing page is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'account' ), 'the live Social /account/ hub redirect is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'dashboard' ), 'the live Social /dashboard/ admin redirect is not stolen' );
rev_expect( 'https://directory.branddad.social/registration/' === bds_rev_alias_dest( 'sign-up' ), 'dead Social sign-up URLs open Directory registration' );
rev_expect( home_url( '/shop/' ) === bds_rev_alias_dest( 'store' ), 'dead Social store URLs open the shop' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'dfy' ), 'offer=done-for-you-ai' ), 'dead Social dfy URLs open the DFY inquiry' );
rev_expect( '' === bds_rev_alias_dest( 'start' ), 'live Social /start/ campaign page is not redirected' );
rev_expect( home_url( '/contact-us/' ) === bds_rev_alias_dest( 'partners' ), 'dead Social partners URLs open contact' );
rev_expect( 'https://affiliates.branddad.social/program' === bds_rev_alias_dest( 'affiliates' ), 'dead Social affiliates URLs open the live affiliate program' );
rev_expect( 'https://directory.branddad.social/registration/' === bds_rev_alias_dest( 'signup' ), 'dead Social signup URLs open Directory registration' );
rev_expect( 'https://directory.branddad.social/claim-your-business-listing-branddad-directory/' === bds_rev_alias_dest( 'claim' ), 'dead Social claim URLs open Directory claim' );
rev_expect( in_array( 'directory.branddad.social', bds_rev_allowed_redirect_hosts( array( 'branddad.social' ) ), true ), 'Social leftover doors may open Directory' );
rev_expect( in_array( 'affiliates.branddad.social', bds_rev_allowed_redirect_hosts( array( 'branddad.social' ) ), true ), 'Social leftover affiliate URLs may open the affiliate host' );
rev_expect( '' === bds_rev_alias_dest( 'contact-us' ), 'the live contact page is not redirected' );
rev_expect( false === bds_rev_path_needs_guide( 'shop' ), 'the shop archive uses the live plan strip' );
rev_expect( false === bds_rev_path_needs_guide( 'pricing' ), 'the missing pricing page is not a destination' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'pricing' ), 'pricing opens the services catalog' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'music' ), '#branddad-music' ), 'music opens the brief' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'ai-packages' ), 'services' ), 'package aliases open services' );
rev_expect( false !== strpos( bds_rev_alias_dest( 'complete-ai-starter' ), 'offer=complete-ai-starter' ), 'staged packages open an inquiry' );
rev_expect( '' === bds_rev_alias_dest( 'seo' ), 'the live Social /seo/ article redirect is not stolen' );
rev_expect( '' === bds_rev_alias_dest( 'local-seo' ), 'the live Social /local-seo/ product redirect is not stolen' );
rev_expect( bds_rev_services_url() === bds_rev_alias_dest( 'ai-seo' ), 'dead Social ai-seo URLs open the services catalog' );
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
list( $live_es, $ask_es, $quote_es ) = bds_rev_split_offer_cards();
rev_expect( false !== strpos( $live_es, 'Suscribirse cada mes' ) && false !== strpos( $live_es, 'data-bds-copy-es=' ), 'live Subscribe monthly Spanish is tagged' );
rev_expect( false !== strpos( $ask_es, 'Preguntar por este plan' ) && false !== strpos( $ask_es, 'data-bds-copy-es=' ), 'inquiry Ask about this plan Spanish is tagged' );
rev_expect( false !== strpos( $quote_es, 'Pedir una cotización' ) && false !== strpos( $quote_es, 'data-bds-copy-es=' ), 'quote Request a scoped quote Spanish is tagged' );
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
rev_expect( false !== strpos( $quote_note, 'Request a scoped quote' ), 'Done-for-You contact note asks for a scoped quote' );
rev_expect( false === strpos( $quote_note, '$399' ), 'Done-for-You contact note has no dollar price' );
rev_expect( false === strpos( $quote_note, 'Subscribe monthly' ), 'quote-only contact note does not invent checkout' );
rev_expect( false === strpos( strtolower( $quote_note ), 'no card' ), 'quote-only contact note does not say no card' );
$starter_note = bds_rev_offer_note_html( 'complete-ai-starter' );
rev_expect( false !== strpos( $starter_note, 'Request a scoped quote' ), 'Complete AI Starter contact note asks for a scoped quote' );
rev_expect( false === strpos( $starter_note, '$79.99' ), 'Complete AI Starter contact note has no dollar price' );
rev_expect( is_array( bds_rev_offer_by_slug( 'done-for-you-ai-management' ) ), 'launch DFY slug still opens the quote note' );
$legacy_pkg = '<section id="branddad-complete-packages"><p>These bundles show the proposed workload and price. Self-serve checkout stays closed until shared entitlements and fulfillment are verified. Request a quote if you want BrandDad to scope one now.</p>'
	. '<article class="bdai-card" id="complete-ai-starter"><h3>Complete AI Starter</h3><p class="bdai-price"><strong>$79.99</strong><span>/month</span></p><a href="https://branddad.social/contact-us/?offer=complete-ai-starter">Ask before checkout</a></article>'
	. '<article class="bdai-card" id="done-for-you-ai-management"><h3>Done-for-You AI Management</h3><p class="bdai-price"><strong>from $399.00</strong><span>/month</span></p><p>Starts at $399 per month after a written scope. This is not an instant checkout product.</p></article>'
	. '</section>';
$legacy_out = bds_rev_rewrite_quote_packages( $legacy_pkg );
rev_expect( false === strpos( $legacy_out, '$79.99' ), 'leftover Complete AI Starter price is stripped' );
rev_expect( false === strpos( $legacy_out, '$399' ), 'leftover Done-for-You price is stripped' );
rev_expect( false !== strpos( $legacy_out, 'On request' ), 'leftover Complete AI cards say On request' );
rev_expect( false !== strpos( $legacy_out, 'Request a scoped quote' ), 'leftover Ask before checkout becomes Request a scoped quote' );
rev_expect( false === strpos( $legacy_out, 'Ask before checkout' ), 'leftover Ask before checkout is removed' );
rev_expect( false !== strpos( $legacy_out, 'Request a scoped quote. Self-serve checkout stays closed' ), 'leftover package intro drops the printed price' );
$live_pkg = '<section id="branddad-complete-packages"><p>These bundles show the proposed workload and price. Self-serve checkout stays closed until shared entitlements and fulfillment are verified. Request a quote if you want BrandDad to scope one now.</p>'
	. '<article class="bdai-card" id="complete-ai-starter"><h3>Complete AI Starter</h3><p class="bdai-price"><strong>$79.99</strong><span data-bds-copy-en="/month" data-bds-copy-es="/mes">/month</span></p><a data-bds-copy-en="Request a scoped quote" href="/contact-us/?offer=complete-ai-starter">Request a scoped quote</a></article>'
	. '<article class="bdai-card" id="done-for-you-ai-management"><h3>Done-for-You AI Management</h3><p class="bdai-price"><strong>from $399.00</strong><span>/month</span></p><p>Starts at $399 per month after a written scope. This is not an instant checkout product.</p><li>Price can increase with channels, locations, and usage</li></article>'
	. '</section><section id="bds-rev-quote-packages"><h2>Complete AI packages — scoped quote</h2></section>'
	. '<article id="music-original-single"><p class="bdai-price"><strong>$79.00</strong></p></article>'
	. '<p>AI Business Essentials is $29.99 a month. That price is for that plan. Bigger plans cost more, and a basic Directory listing is free.</p>';
$live_out = bds_rev_rewrite_quote_packages( $live_pkg );
rev_expect( false === strpos( $live_out, '$79.99' ), 'live Complete AI Starter price is stripped' );
rev_expect( false === strpos( $live_out, '$399' ), 'live Done-for-You price is stripped' );
rev_expect( false !== strpos( $live_out, '$79.00' ), 'music project price is left alone' );
rev_expect( false !== strpos( $live_out, '$29.99' ), 'Essentials FAQ price is left alone' );
rev_expect( false !== strpos( $live_out, 'On request' ), 'live Complete AI cards say On request' );
rev_expect( false !== strpos( $live_out, 'Quote-only after a written scope' ), 'live Done-for-You summary drops the dollar floor' );
rev_expect( false !== strpos( $live_out, 'Scope can change with channels' ), 'live Done-for-You limit no longer says Price' );
rev_expect( false === strpos( $live_out, 'id="bds-rev-quote-packages"' ), 'launch complete packages are not duplicated' );
rev_expect( false !== strpos( $live_out, 'Complete AI packages are quoted separately' ), 'FAQ does not say bigger plans cost more' );
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
$now_sw = '<a class="bds-rev-button" href="https://branddad.social/product/ai-social-growth/?ref=house" data-bds-copy-en="Subscribe now" data-bds-copy-es="Suscribirse ahora">Subscribe now</a>';
$now_sw_out = bds_rev_rewrite_subscribe_now( $now_sw );
rev_expect( false !== strpos( $now_sw_out, 'data-bds-copy-en="Subscribe monthly"' ), 'Subscribe now English swap attr is updated' );
rev_expect( false !== strpos( $now_sw_out, 'data-bds-copy-es="Suscribirse cada mes"' ), 'Subscribe now Spanish swap attr is updated' );
rev_expect( false === strpos( $now_sw_out, 'Subscribe now' ), 'lang switch cannot restore Subscribe now' );
$ess = '<a class="bds-rev-button" href="https://branddad.social/product/ai-business-essentials/?ref=house" data-bds-copy-en="Subscribe now" data-bds-copy-es="Suscribirse ahora">Subscribe now</a>';
$ess_out = bds_rev_rewrite_subscribe_now( $ess );
rev_expect( false !== strpos( $ess_out, '>Start 7-day trial</a>' ), 'Essentials leftover Subscribe now becomes Start 7-day trial' );
rev_expect( false !== strpos( $ess_out, 'data-bds-copy-en="Start 7-day trial"' ), 'Essentials English swap attr is the trial' );
rev_expect( false === strpos( $ess_out, 'Subscribe monthly' ), 'Essentials is not rewritten to Subscribe monthly' );
$see = '<a href="https://directory.branddad.social/select-your-plan/">See plans</a>';
rev_expect( false !== strpos( bds_rev_rewrite_see_plans( $see ), '>See membership plans</a>' ), 'leftover See plans on Select your plan becomes See membership plans' );
rev_expect( false === strpos( bds_rev_rewrite_see_plans( $see ), '>See plans</a>' ), 'leftover See plans label is removed' );
$see_jump = '<a class="bds-mt-compare__btn" href="#bds-mt-plans">See plans</a>';
rev_expect( false !== strpos( bds_rev_rewrite_see_plans( $see_jump ), '>See membership plans</a>' ), 'plans-page See plans jump is renamed' );
$see_sw = '<a href="https://branddad.social/registration/" data-bds-copy-en="See plans" data-bds-copy-es="Ver planes">See plans</a>';
$see_sw_out = bds_rev_rewrite_see_plans( $see_sw );
rev_expect( false !== strpos( $see_sw_out, 'data-bds-copy-en="See membership plans"' ), 'See plans English swap attr is updated' );
rev_expect( false === strpos( $see_sw_out, 'data-bds-copy-en="See plans"' ), 'lang switch cannot restore See plans' );
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
$home_conv = '<section class="bdai-hero"><div class="bdai-actions"><a class="bdai-button" href="https://branddad.social/product/ai-business-essentials/">Try it free for 7 days</a><a class="bdai-button bdai-button--light" href="#branddad-ai-solutions">See what you get</a></div></section>'
	. '<a class="bdai-text-link" href="https://branddad.social/product/ai-business-essentials/" data-bds-copy-en="Try it in Essentials — $29.99/month">Try it in Essentials — $29.99/month</a>'
	. '<a class="bdai-button bdai-button--light" href="#branddad-ai-plans" data-bds-copy-en="Compare plans">Compare plans</a>'
	. '<a class="bdai-text-link" href="https://branddad.social/contact-us/?offer=done-for-you-ai">Talk to BrandDad about done-for-you help</a>'
	. '<a class="bds-nc-secondary" href="https://directory.branddad.social/add-listing/">List your business</a>';
$home_conv_out = bds_rev_rewrite_home_conversion( $home_conv );
rev_expect( false !== strpos( $home_conv_out, 'bds-rev-hero-list' ), 'hero gets a List my business free button' );
rev_expect( false !== strpos( $home_conv_out, 'directory.branddad.social/add-listing/' ), 'hero listing door opens Directory add-listing' );
rev_expect( false !== strpos( $home_conv_out, '>Start 7-day trial</a>' ), 'help-grid Essentials row starts the 7-day trial' );
rev_expect( false === strpos( $home_conv_out, 'Try it in Essentials' ), 'help-grid no longer sells Essentials as a priced row' );
rev_expect( false !== strpos( $home_conv_out, 'href="#branddad-ai-solutions"' ), 'Compare plans opens live products' );
rev_expect( false === strpos( $home_conv_out, '#branddad-ai-plans' ), 'Compare plans does not jump to Studio credits' );
rev_expect( false !== strpos( $home_conv_out, '>Request a scoped quote</a>' ), 'Done-for-You talk link requests a scoped quote' );
rev_expect( false === strpos( $home_conv_out, 'Talk to BrandDad about done-for-you help' ), 'old Done-for-You talk CTA is removed' );
rev_expect( false !== strpos( $home_conv_out, '>List my business free</a>' ), 'network List your business becomes List my business free' );
$home_conv_again = bds_rev_rewrite_home_conversion( $home_conv_out );
rev_expect( 1 === substr_count( $home_conv_again, 'bds-rev-hero-list' ), 'hero listing door is not injected twice' );
$rev_src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-revenue-sections-mu.php' );
rev_expect( false !== strpos( $rev_src, 'Version: 0.2.44' ), 'revenue MU stamps 0.2.44' );
rev_expect( false !== strpos( $rev_src, 'bds_rev_allowed_redirect_hosts' ), 'Social leftover doors may open Directory' );
rev_expect( false === strpos( $rev_src, 'wp_get_referer' ), 'music brief redirect stays on Social home' );
rev_expect( false !== strpos( $rev_src, "home_url( '/?bds_music=saved' )" ), 'a saved brief returns to Social home' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} revenue section tests failed\n" );
	exit( 1 );
}
echo "revenue section tests passed\n";
