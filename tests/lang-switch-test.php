<?php
/**
 * EN/ES switcher: allowlisted language, tagged copy, no Google Translate.
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
function is_ssl() { return true; }
function wp_unslash( $s ) { return $s; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_js( $s ) { return (string) $s; }
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

function assert_true( $cond, $message ) {
	if ( ! $cond ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

$_SERVER['HTTP_HOST'] = 'branddad.social';
unset( $_GET['bd_lang'], $_COOKIE['bd_lang'] );

require dirname( __DIR__ ) . '/deploy/cursor/bds-lang-switch-mu.php';

assert_true( '0.1.2' === BDS_LANG_SWITCH_VER, 'language switch version' );
assert_true( 'en' === bds_lang_sanitize( '<script>' ), 'unknown input becomes English' );
assert_true( 'en' === bds_lang_sanitize( 'fr' ), 'French is not a public language' );
assert_true( 'es' === bds_lang_sanitize( 'ES' ), 'Spanish is allowlisted' );
assert_true( 'en' === bds_lang_current(), 'default language is English' );
$_GET['bd_lang'] = 'es';
assert_true( 'es' === bds_lang_current(), 'GET bd_lang=es is Spanish' );
$_GET['bd_lang'] = 'xx';
assert_true( 'en' === bds_lang_current(), 'unknown GET stays English' );
unset( $_GET['bd_lang'] );

$pair = bds_lang_pair( 'Hello', 'Hola', 'p', 'lead' );
assert_true( false !== strpos( $pair, 'data-bds-copy="en"' ) && false !== strpos( $pair, 'data-bds-copy="es"' ), 'pair tags both languages' );
assert_true( false !== strpos( $pair, 'Hello' ) && false !== strpos( $pair, 'Hola' ), 'pair keeps both sentences' );
$swap = bds_lang_swap_attrs( 'Find a business. See what it offers. Contact it.', 'Encuentre un negocio. Vea qué ofrece. Contáctelo.' );
assert_true( false !== strpos( $swap, 'data-bds-copy-en=' ) && false !== strpos( $swap, 'data-bds-copy-es=' ), 'swap keeps one node' );

$src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-lang-switch-mu.php' );
assert_true( false === strpos( $src, 'googtrans' ), 'the switcher does not use Google Translate' );
assert_true( false === strpos( $src, 'bd-lc-root' ), 'the switcher does not reuse the killed widget id' );
assert_true( false !== strpos( $src, 'id="bds-lang-switch"' ), 'the switcher uses a live id' );
assert_true( false !== strpos( $src, 'location.search' ), 'cached pages read the language from the browser, not from PHP' );
assert_true( false !== strpos( $src, 'remove_query_arg' ), 'bd_lang is stripped so shared cache stays English' );

ob_start();
bds_lang_switch_markup();
$switch = ob_get_clean();
assert_true( false !== strpos( $switch, 'id="bds-lang-switch"' ), 'the control is printed' );
assert_true( false !== strpos( $switch, 'data-bds-lang-pick="en"' ) && false !== strpos( $switch, 'data-bds-lang-pick="es"' ), 'EN and ES can be chosen' );
assert_true( false !== strpos( $switch, 'data-bds-lang-pick="en" aria-pressed="true"' ), 'cached HTML defaults to English' );

require dirname( __DIR__ ) . '/deploy/bds-ai-commercial-catalog-mu.php';
require dirname( __DIR__ ) . '/deploy/bds-ai-launch-pages-mu.php';
$home = bds_ai_launch_markup( 'home' );
assert_true( false !== strpos( $home, 'data-version="0.5.26"' ), 'home stamps the bilingual launch version' );
assert_true( false !== strpos( $home, 'Business help from $29.99/month' ), 'English price line stays in the HTML' );
assert_true( false !== strpos( $home, 'La ayuda empieza en $29.99 al mes' ), 'Spanish price line is present for the switcher' );
assert_true( false !== strpos( $home, 'data-bds-copy="es"' ), 'Spanish launch copy is tagged' );
assert_true( 1 === substr_count( strtolower( $home ), '<h1' ), 'the launch hero keeps one H1' );
assert_true( false !== strpos( $home, 'Try it free for 7 days' ), 'the trial CTA stays' );
assert_true( false !== strpos( $home, 'Preguntas antes de elegir' ), 'FAQ heading Spanish is tagged' );
assert_true( false !== strpos( $home, 'Listarlo es gratis' ), 'directory CTA Spanish is tagged' );
$faqs = bds_ai_launch_plain_faqs();
assert_true( isset( $faqs[0][2], $faqs[0][3] ) && count( $faqs ) >= 12, 'FAQ rows include Spanish answers' );
assert_true( false !== strpos( $home, 'Pruébelo en Essentials — $29.99/mes' ), 'help grid Spanish CTA is tagged' );
assert_true( false !== strpos( $home, 'Salga destacado — $9.99/mes' ), 'featured price CTA Spanish is tagged' );
assert_true( false !== strpos( $home, 'No necesita aprender un software complicado.' ), 'process heading Spanish is tagged' );
assert_true( false !== strpos( $home, 'Suscribirse cada mes' ), 'studio Subscribe monthly Spanish is tagged' );
assert_true( false !== strpos( $home, 'Comprar este servicio' ), 'specialty buy CTA Spanish is tagged' );
assert_true( false !== strpos( $home, 'Ayuda de IA asequible' ) && false !== strpos( $home, 'data-bds-copy="es"' ), 'Essentials summary Spanish is tagged' );
assert_true( false !== strpos( $home, 'Paquete propuesto' ) && false !== strpos( $home, 'data-bds-copy="es"' ), 'complete package summary Spanish is tagged' );
assert_true( false !== strpos( $home, 'Música original por encargo' ) && false !== strpos( $home, 'data-bds-copy="es"' ), 'music original single Spanish is tagged' );
$svc_es = bds_ai_launch_markup( 'services' );
assert_true( false !== strpos( $svc_es, 'Monitoreo simple de disponibilidad' ) && false !== strpos( $svc_es, 'data-bds-copy="es"' ), 'monitoring summary Spanish is tagged' );
assert_true( false !== strpos( $svc_es, 'Borradores profesionales de respuesta' ), 'review assistant Spanish is tagged' );

assert_true( 'Salir destacado' === bds_lang_converting_menu_es( 'Get Featured' ), 'Get Featured menu has Spanish' );
assert_true( 'Listar mi negocio gratis' === bds_lang_converting_menu_es( 'List my business free' ), 'free listing menu has Spanish' );
assert_true( '' === bds_lang_converting_menu_es( 'Learn' ), 'non-converting menu stays English' );
$feat_item        = (object) array( 'title' => 'Get Featured' );
$feat_atts        = bds_lang_menu_link_attrs( array( 'href' => '/featured-advertising/' ), $feat_item );
assert_true( 'Get Featured' === ( $feat_atts['data-bds-copy-en'] ?? '' ) && 'Salir destacado' === ( $feat_atts['data-bds-copy-es'] ?? '' ), 'menu link swap attrs land on the anchor' );

$_SERVER['HTTP_HOST'] = 'hosttech.net';
assert_true( false === bds_lang_switch_host(), 'HostTech stays English-only' );
$hosttech_atts = bds_lang_menu_link_attrs( array( 'href' => '/' ), (object) array( 'title' => 'Get Featured' ) );
assert_true( empty( $hosttech_atts['data-bds-copy-es'] ), 'HostTech menus do not get Spanish attrs' );

echo "lang switch ok\n";
