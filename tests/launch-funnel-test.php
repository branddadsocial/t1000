<?php
/**
 * Homepage/services cards must show a price and a real next step.
 */
define( 'ABSPATH', __DIR__ );
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

class WP_Post {
	public $ID;
	public $post_status = 'publish';
	public $post_name   = '';
}

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function is_admin() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function is_front_page() { return false; }
function is_page() { return false; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return $s; }
function esc_html__( $s ) { return $s; }
function esc_html_e( $s ) { echo esc_html( $s ); }
function __( $s ) { return $s; }
function home_url( $path = '' ) { return 'https://branddad.social' . $path; }
function wp_json_encode( $s ) { return json_encode( $s ); }
function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', trim( (string) $s, '-' ) ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function number_format_i18n( $n ) { return (string) $n; }
function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = $args[1];
	} else {
		$params = array( $args[0] => $args[1] );
		$url    = $args[2];
	}
	$q   = http_build_query( $params );
	$sep = ( false === strpos( (string) $url, '?' ) ) ? '?' : '&';
	return $url . $sep . $q;
}
function bds_po_url( $url ) {
	$url = (string) $url;
	if ( '' === $url || false !== strpos( $url, 'ref=' ) ) {
		return $url;
	}
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'ref=house';
}

function bds_funnel_live_slugs() {
	return array(
		'ai-business-essentials',
		'ai-lead-assistant',
		'ai-social-growth',
		'ai-local-seo',
		'branddad-ai-studio-starter',
		'branddad-ai-studio-growth',
		'branddad-ai-studio-pro',
		'branddad-ai-review-dispute-assistant',
		'branddad-social-media-growth-studio',
		'real-influencers-engagements-ig-fb-tiktok-youtube-more',
		'press-release-services',
		'instagram-boost-services-real-people-sharing-your-post-on-their-story-appear-on-discovery',
		'imdb-profile-creation-services',
		'nyc-times-square-billboard-video-ads',
	);
}

function get_page_by_path( $path, $output = null, $type = 'page' ) {
	$path = sanitize_title( $path );
	if ( ! in_array( $path, bds_funnel_live_slugs(), true ) ) {
		return null;
	}
	$post              = new WP_Post();
	$post->ID          = abs( crc32( $path ) ) ?: 1;
	$post->post_status = 'publish';
	$post->post_name   = $path;
	return $post;
}

function get_permalink( $post ) {
	$name = is_object( $post ) ? $post->post_name : '';
	return 'https://branddad.social/product/' . $name . '/';
}

class BdsFakeProduct {
	public $price;
	public $variable;
	public function __construct( $price, $variable = false ) {
		$this->price    = $price;
		$this->variable = $variable;
	}
	public function is_type( $t ) {
		return $this->variable && 'variable' === $t;
	}
	public function get_variation_price( $which, $display ) {
		return $this->price;
	}
	public function get_price() {
		return $this->price;
	}
}

function wc_get_product( $id ) {
	return new BdsFakeProduct( '239.99', true );
}

require dirname( __DIR__ ) . '/deploy/bds-ai-commercial-catalog-mu.php';
require dirname( __DIR__ ) . '/deploy/bds-ai-launch-pages-mu.php';

$failed = 0;
function funnel_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

funnel_expect( '0.5.26' === BDS_AI_LAUNCH_PAGES_VERSION, 'launch version is 0.5.26' );

$home = bds_ai_launch_markup( 'home' );
funnel_expect( false !== strpos( $home, 'data-version="0.5.26"' ), 'home markup stamps 0.5.26' );
funnel_expect( false === strpos( $home, 'La ayuda empieza' ), 'without the switcher, homepage price line stays English' );
funnel_expect( false === strpos( $home, 'Ayuda de IA asequible' ), 'without the switcher, Essentials summary stays English' );
funnel_expect( false === strpos( $home, 'Paquete propuesto' ), 'without the switcher, package summaries stay English' );
funnel_expect( false === strpos( $home, 'Música original por encargo' ), 'without the switcher, music cards stay English' );
$svc = bds_ai_launch_markup( 'services' );
funnel_expect( false === strpos( $svc, 'Monitoreo simple de disponibilidad' ), 'without the switcher, specialized cards stay English' );
funnel_expect( false !== strpos( $home, 'Get featured — $9.99/month' ), 'local featured card has priced CTA' );
funnel_expect( false !== strpos( $home, 'Get featured — $19.99/month' ), 'regional featured card has priced CTA' );
funnel_expect( false !== strpos( $home, 'Get featured — $39.99/month' ), 'national featured card has priced CTA' );
funnel_expect( false !== strpos( $home, 'Get featured — $99.99/month' ), 'global featured card has priced CTA' );
funnel_expect( false !== strpos( $home, 'featured-advertising/?lane=local' ), 'local featured CTA carries lane' );
funnel_expect( false !== strpos( $home, 'Try it in Essentials — $29.99/month' ), 'help grid connects to Essentials price' );
funnel_expect( false !== strpos( $home, 'Start 7-day trial' ), 'home keeps the trial CTA' );
funnel_expect( false !== strpos( $home, 'id="branddad-specialty-services"' ), 'homepage shows specialty services' );
funnel_expect( false !== strpos( $home, 'From $239.99' ), 'specialty cards show the live Woo floor price' );
funnel_expect( false !== strpos( $home, 'Buy this service' ), 'specialty cards buy instead of hiding the price' );
funnel_expect( false === strpos( $home, 'See the price' ), 'homepage does not hide specialty prices behind See the price' );
funnel_expect( false !== strpos( $home, 'Subscribe monthly' ), 'studio cards say Subscribe monthly' );
funnel_expect( false === strpos( $home, 'Choose Starter' ), 'studio cards do not say Choose Starter' );
funnel_expect( false !== strpos( $home, 'offer=complete-ai-starter' ) || false !== strpos( $home, 'offer=complete-ai' ), 'complete packages inquire with offer=' );
funnel_expect( false === strpos( $home, 'subject=BrandDad' ), 'inquiry no longer uses free-text subject=' );

$legacy_meta = '<html><head><meta name="description" content="Evidence checklist for Google, Airbnb, or Trustpilot reviews. Available by quote."></head><body>Keep me</body></html>';
$fixed_meta  = bds_ai_launch_airbnb_meta_description( $legacy_meta );
funnel_expect( 1 === substr_count( $fixed_meta, '<meta name="description"' ), 'Airbnb product keeps exactly one description meta tag' );
funnel_expect( false !== strpos( $fixed_meta, 'one Airbnb review case' ), 'Airbnb product metadata states the one-case scope' );
funnel_expect( false !== strpos( $fixed_meta, '$9.99 one time' ), 'Airbnb product metadata states the one-time price' );
funnel_expect( false === strpos( $fixed_meta, 'Trustpilot' ) && false === strpos( $fixed_meta, 'Available by quote' ), 'Airbnb product metadata removes legacy multi-platform quote copy' );
funnel_expect( false !== strpos( $fixed_meta, '<body>Keep me</body>' ), 'metadata repair preserves the rest of the response' );
funnel_expect( false !== strpos( $home, 'offer=ai-linkedin-growth' ), 'LinkedIn studio inquires with offer=' );
funnel_expect( false !== strpos( $home, 'offer=ai-website-chatbot' ), 'chatbot inquires with offer=' );
$faqs = bds_ai_launch_plain_faqs();
funnel_expect( isset( $faqs[0][2], $faqs[0][3] ) && count( $faqs ) >= 12, 'FAQ rows include Spanish answers' );
funnel_expect( false === strpos( $home, 'Preguntas antes de elegir' ), 'without the switcher, FAQ Spanish stays out of HTML' );

$quote = bds_ai_launch_offer_action( 'complete-ai-starter', array( 'name' => 'Starter', 'launch_state' => 'quote_only' ) );
funnel_expect( false !== strpos( $quote[0], 'offer=complete-ai-starter' ), 'quote action uses offer slug' );
funnel_expect( false === strpos( $quote[0], 'subject=' ), 'quote action has no subject query' );

$svc = bds_ai_launch_specialty_services_markup();
funnel_expect( substr_count( $svc, 'bdai-price' ) >= 7, 'each specialty card has a price element' );
funnel_expect( substr_count( $svc, 'Buy this service' ) >= 7, 'each specialty card has a buy CTA' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
fwrite( STDOUT, "launch funnel tests passed\n" );
