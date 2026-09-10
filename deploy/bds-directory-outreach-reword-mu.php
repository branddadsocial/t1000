<?php
/**
 * Plugin Name: BrandDad Directory Outreach Reword
 * Description: Remove customer-facing outreach positioning on Directory. Keep engagement network; never recommend LinkedIn outreach products.
 * Version: 1.0.0
 *
 * Deploy: directory.branddad.social/wp-content/mu-plugins/bds-directory-outreach-reword-mu.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_DIR_OUTREACH_REWORD_VER' ) ) {
	return;
}
define( 'BDS_DIR_OUTREACH_REWORD_VER', '1.0.1' );

/**
 * Normalize outreach-product CTAs in HTML fragments.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_dir_outreach_scrub_html( $html ) {
	$html = (string) $html;
	if ( $html === '' ) {
		return $html;
	}
	// Remove anchors to Social outreach product.
	$html = preg_replace(
		'#<a\b[^>]*href=["\'][^"\']*linkedin-outreach[^"\']*["\'][^>]*>.*?</a>#is',
		'',
		$html
	);
	$html = str_ireplace(
		array(
			'Warm introductions, not ' . 'cold' . ' outreach',
			'warm introductions, not ' . 'cold' . ' outreach',
			'cold' . ' outreach',
			'Cold' . ' outreach',
			'LinkedIn outreach',
			'linkedin outreach',
			'Done-for-You LinkedIn Outreach',
		),
		array(
			'Warm introductions through familiar faces',
			'warm introductions through familiar faces',
			'professional visibility',
			'Professional visibility',
			'LinkedIn visibility',
			'LinkedIn visibility',
			'LinkedIn Visibility Amplification',
		),
		$html
	);
	return $html;
}

add_filter( 'the_content', 'bds_dir_outreach_scrub_html', 35 );
add_filter( 'widget_text', 'bds_dir_outreach_scrub_html', 35 );
add_filter( 'woocommerce_short_description', 'bds_dir_outreach_scrub_html', 35 );

/**
 * Filter AI Finder soft services — block outreach SKUs; add Health Check + local services.
 *
 * @param array<int,array<string,string>> $services Services.
 * @param string                          $message  Message.
 * @param array<string,mixed>             $parsed   Parsed.
 * @return array<int,array<string,string>>
 */
function bds_dir_outreach_filter_ai_services( $services, $message = '', $parsed = array() ) {
	$out = array();
	foreach ( (array) $services as $svc ) {
		if ( ! is_array( $svc ) ) {
			continue;
		}
		$blob = strtolower( (string) ( $svc['id'] ?? '' ) . ' ' . (string) ( $svc['label'] ?? '' ) . ' ' . (string) ( $svc['url'] ?? '' ) );
		if ( 1 === preg_match( '/outreach|linkedin-outreach/', $blob ) ) {
			continue;
		}
		$out[] = $svc;
	}

	$m = strtolower( (string) $message . ' ' . ( isset( $parsed['category'] ) ? $parsed['category'] : '' ) );
	if ( 1 === preg_match( '/health\s*check|audit\s*my\s*(site|website)|website\s*score|seo\s*audit|google\s*business|gbp|fix\s*my\s*(site|website)|site\s*speed|local\s*seo/', $m ) ) {
		array_unshift(
			$out,
			array(
				'id'    => 'business-health-check',
				'label' => 'Free Business Health Check',
				'url'   => home_url( '/?bds_health=1' ),
				'blurb' => 'Public website signals + explainable score. Useful results first — then optional fixes with prices.',
			)
		);
	}
	if ( function_exists( 'bds_svc_catalog' ) && 1 === preg_match( '/seo|google\s*business|gbp|speed|conversion|review|directory\s*list|website\s*care|fix\s*my/', $m ) ) {
		$recs = function_exists( 'bds_svc_recommend_for_issues' )
			? bds_svc_recommend_for_issues(
				preg_match( '/speed/', $m ) ? array( 'perf_poor' )
				: ( preg_match( '/review/', $m ) ? array( 'few_reviews' )
				: ( preg_match( '/gbp|google\s*business/', $m ) ? array( 'gbp_incomplete' )
				: ( preg_match( '/conversion|cta/', $m ) ? array( 'weak_cta' )
				: array( 'seo_meta' ) ) ) )
			)
			: array();
		foreach ( array_slice( $recs, 0, 2 ) as $r ) {
			$out[] = array(
				'id'    => $r['key'],
				'label' => $r['title'],
				'url'   => $r['url'],
				'blurb' => $r['blurb'],
			);
		}
	}
	// Cap soft-sell.
	return array_slice( $out, 0, 3 );
}
add_filter( 'bds_ai_finder_services', 'bds_dir_outreach_filter_ai_services', 20, 3 );

/**
 * Account Control copy patches (late string replace on buffered fragments if filter exists).
 *
 * @param string $html HTML.
 * @return string
 */
function bds_dir_outreach_ac_copy( $html ) {
	return bds_dir_outreach_scrub_html( $html );
}
add_filter( 'bds_ac_render_html', 'bds_dir_outreach_ac_copy', 20 );

/**
 * Membership compare table — remove outreach wording if present.
 *
 * @param string $html HTML.
 * @return string
 */
add_filter( 'bds_mt_compare_html', 'bds_dir_outreach_scrub_html', 20 );

/**
 * Strip outreach product links from menus on Directory.
 *
 * @param array<int,object> $items Items.
 * @return array<int,object>
 */
function bds_dir_outreach_nav( $items ) {
	if ( ! is_array( $items ) ) {
		return $items;
	}
	$out = array();
	foreach ( $items as $item ) {
		$url   = isset( $item->url ) ? (string) $item->url : '';
		$title = isset( $item->title ) ? (string) $item->title : '';
		if ( 1 === preg_match( '/linkedin-outreach|linkedin\s+outreach/i', $url . ' ' . $title ) ) {
			continue;
		}
		$out[] = $item;
	}
	return $out;
}
add_filter( 'wp_nav_menu_objects', 'bds_dir_outreach_nav', 40 );

/**
 * Homepage Health Check teaser when ?bds_health=1
 *
 * @param string $content Content.
 * @return string
 */
function bds_dir_health_home_inject( $content ) {
	if ( is_admin() || empty( $_GET['bds_health'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $content;
	}
	if ( ! is_front_page() && ! is_home() ) {
		return $content;
	}
	if ( ! shortcode_exists( 'bds_business_health_check' ) ) {
		return $content;
	}
	return do_shortcode( '[bds_business_health_check]' ) . $content;
}
add_filter( 'the_content', 'bds_dir_health_home_inject', 5 );

/**
 * Footer fallback when Elementor/home skips the_content.
 * Skip when a Health Check widget already rendered earlier in the request.
 */
add_action(
	'wp_footer',
	static function () {
		if ( is_admin() || empty( $_GET['bds_health'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! shortcode_exists( 'bds_business_health_check' ) ) {
			return;
		}
		if ( ! empty( $GLOBALS['bds_health_widget_rendered'] ) ) {
			return;
		}
		echo '<div id="bds-health-footer-fallback" style="max-width:920px;margin:2rem auto;padding:0 1rem">';
		echo do_shortcode( '[bds_business_health_check]' );
		echo '</div>';
	},
	5
);
