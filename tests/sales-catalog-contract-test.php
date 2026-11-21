<?php
/**
 * Sales catalog contract. Local file is 0.3.4. Live Social is 0.4.0 and must not be overwritten.
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
function is_admin() {
	return false;
}
function sanitize_title( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', trim( (string) $s, '-' ) ) );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_text_field( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function esc_url_raw( $s ) {
	return $s;
}
function absint( $n ) {
	return abs( (int) $n );
}
function home_url( $path = '' ) {
	return 'https://branddad.social' . $path;
}
function get_permalink( $post ) {
	$name = is_object( $post ) ? $post->post_name : '';
	return 'https://branddad.social/product/' . $name . '/';
}
function get_page_by_path( $path, $output = null, $type = 'page' ) {
	$path = sanitize_title( $path );
	$live = array( 'ai-business-essentials', 'ai-lead-assistant', 'ai-social-growth', 'ai-local-seo' );
	if ( ! in_array( $path, $live, true ) ) {
		return null;
	}
	$post              = new WP_Post();
	$post->ID          = abs( crc32( $path ) ) ?: 1;
	$post->post_status = 'publish';
	$post->post_name   = $path;
	return $post;
}

require dirname( __DIR__ ) . '/deploy/bds-ai-commercial-catalog-mu.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-sales-assistant-api-mu.php';

$failed = 0;
function cat_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

cat_expect( '0.3.4' === BDS_AI_COMMERCIAL_CATALOG_VERSION, 'local catalog stamp stays 0.3.4 so live 0.4.0 is not overwritten' );

$essentials = bds_ai_commercial_offer( 'ai-business-essentials' );
cat_expect( is_array( $essentials ) && '29.99' === $essentials['monthly'], 'Essentials is $29.99' );
cat_expect( 7 === (int) $essentials['trial_days'], 'Essentials keeps a 7-day trial' );
cat_expect( 'launch_candidate' === $essentials['launch_state'], 'Essentials is a launch candidate, not quote-only' );
cat_expect( false !== strpos( bds_ai_commercial_checkout_url( 'ai-business-essentials' ), '/product/ai-business-essentials/' ), 'published Essentials may open checkout' );

cat_expect( '' === bds_ai_commercial_checkout_url( 'complete-ai-starter' ), 'complete packages stay off checkout' );
cat_expect( '' === bds_ai_commercial_checkout_url( 'done-for-you-ai-management' ), 'DFY stays off checkout' );
cat_expect( '' === bds_ai_commercial_checkout_url( 'music-original-single' ), 'music stays off checkout' );
cat_expect( 'quote_only' === ( bds_ai_commercial_offer( 'complete-ai-pro' )['launch_state'] ?? '' ), 'complete AI Pro is quote-only' );
cat_expect( 'staged' === ( bds_ai_commercial_offer( 'ai-business-essentials-pro' )['launch_state'] ?? '' ), 'Essentials Pro stays staged' );
cat_expect( '' === bds_ai_commercial_checkout_url( 'ai-business-essentials-pro' ), 'staged Essentials Pro has no checkout URL' );
cat_expect( null === bds_ai_commercial_offer( '<script>x</script>' ), 'markup slugs fail closed' );

$offers = bds_sales_assistant_offers( '' );
cat_expect( 1 === count( $offers ), 'anonymous catalog lists Essentials only' );
cat_expect( 'ai-business-essentials' === $offers[0]['slug'], 'anonymous catalog slug is Essentials' );
cat_expect( '29.99' === $offers[0]['monthly'], 'anonymous catalog price is 29.99' );
cat_expect( true === $offers[0]['for_sale'], 'published Essentials is for_sale' );
cat_expect( ! isset( $offers[0]['formwise_plan_id'] ), 'catalog offers do not leak a FormWise plan id' );
$blob = strtolower( json_encode( $offers ) );
cat_expect( false === strpos( $blob, 'sk_' ) && false === strpos( $blob, 'pk_live' ), 'catalog offers do not print a Stripe key' );
cat_expect( false === strpos( $blob, 'formwise' ), 'catalog offers do not name FormWise' );

$seo = bds_sales_assistant_offers( 'I need local SEO help' );
$slugs = array_column( $seo, 'slug' );
cat_expect( in_array( 'ai-local-seo', $slugs, true ), 'an SEO question may add Local SEO' );
cat_expect( in_array( 'ai-business-essentials', $slugs, true ), 'an SEO question still includes Essentials' );

cat_expect( false === bds_sales_assistant_ready(), 'sales assistant stays closed without FormWise keys or the enable flag' );
cat_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise execution key' );
cat_expect( ! defined( 'BDS_SALES_ASSISTANT_ENABLED' ), 'tests do not enable the sales-assistant flag' );

$docs = file_get_contents( dirname( __DIR__ ) . '/BRANDDAD_CURSOR_TAKEOVER.md' );
cat_expect( false !== strpos( $docs, '0.4.0' ), 'takeover docs record live catalog 0.4.0' );
cat_expect( false !== strpos( $docs, '0.3.4' ), 'takeover docs record local catalog 0.3.4' );
cat_expect( false !== strpos( $docs, 'Do not upload the local catalog over live' ), 'takeover docs forbid overwriting live catalog' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "sales catalog contract tests passed\n";
