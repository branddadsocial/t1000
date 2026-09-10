<?php
/**
 * Plugin Name: BrandDad Social soft-path redirects
 * Description: Early 301s for retired vanity paths -> live product/tool URLs. Runs before Squirrly/WF301 404->home.
 * Version: 1.0.4
 * Author: BrandDad
 *
 * Deploy: branddad.social wp-content/mu-plugins/00-bds-soft-path-redirects-mu.php
 * Standing: converting doors only; no chrome redesign; Partnero ?ref= preserved.
 *
 * 1.0.4 — Route trashed duplicate page slugs to canonical live pages/products.
 * 1.0.3 — Route legacy SEO Growth and Google Review product slugs to canonical products.
 * 1.0.2 — Route legacy LinkedIn Outreach Launch product slug to the live product.
 * 1.0.1 — Strip leftover /es/* (WPML) → English path; /ai-ads/page/N → /ai-ads/.
 * 1.0.0 — Vanity product/tool soft redirects.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_SOFT_PATH_REDIRECTS_MU' ) ) {
	return;
}
define( 'BDS_SOFT_PATH_REDIRECTS_MU', true );
define( 'BDS_SOFT_PATH_REDIRECTS_VER', '1.0.4' );

/**
 * Soft vanity redirects for branddad.social.
 */
function bds_soft_path_redirects_mu() {
	if ( is_admin() ) {
		return;
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( $host !== '' && false === strpos( $host, 'branddad.social' ) ) {
		return;
	}
	if ( false !== strpos( $host, 'directory.branddad' ) ) {
		return;
	}

	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
	$path = untrailingslashit( strtolower( $path ) );

	// Leftover WPML /es/ URLs (WPML removed) — land on English equivalent, not bare home.
	if ( $path === '/es' ) {
		$to = home_url( '/' );
		if ( function_exists( 'bds_po_url' ) ) {
			$to = bds_po_url( $to );
		}
		wp_safe_redirect( $to, 301 );
		exit;
	}
	if ( 0 === strpos( $path, '/es/' ) ) {
		$rest = substr( $path, 3 ); // drop "/es"
		if ( $rest === '' || $rest === '/' ) {
			$rest = '/';
		}
		$to = home_url( $rest );
		if ( function_exists( 'bds_po_url' ) ) {
			$to = bds_po_url( $to );
		}
		wp_safe_redirect( $to, 301 );
		exit;
	}

	// AI Ads pagination stubs that Squirrly/WF301 would send to homepage.
	if ( 1 === preg_match( '#^/ai-ads/page/\d+$#', $path ) ) {
		$to = home_url( '/ai-ads/' );
		if ( function_exists( 'bds_po_url' ) ) {
			$to = bds_po_url( $to );
		}
		wp_safe_redirect( $to, 301 );
		exit;
	}

	$map = array(
		'/gift-cards'                    => 'https://directory.branddad.social/gift-card-marketplace/',
		'/affiliate'                     => 'https://affiliates.branddad.social/',
		'/ask-branddad'                  => home_url( '/services/' ),
		'/login'                         => home_url( '/my-account/' ),
		'/sign-in'                       => home_url( '/my-account/' ),
		'/register'                      => 'https://directory.branddad.social/registration/',
		'/registration'                  => 'https://directory.branddad.social/registration/',
		'/faq'                           => home_url( '/contact-us/' ),
		'/contact'                       => home_url( '/contact-us/' ),
		'/terms'                         => home_url( '/terms-conditions/' ),
		'/terms-of-service'              => home_url( '/terms-conditions/' ),
		'/refund-policy'                 => home_url( '/refund_returns/' ),
		'/refunds'                       => home_url( '/refund_returns/' ),
		'/returns'                       => home_url( '/refund_returns/' ),
		'/ai-network'                    => home_url( '/my-account/' ) . '#bds-site-seo-tools',
		'/health-check'                  => home_url( '/check-your-website/' ),
		'/creator'                       => home_url( '/creator-services/' ),
		'/consult'                       => home_url( '/contact-us/' ),
		'/consultation'                  => home_url( '/contact-us/' ),
		'/google-business-profile-setup' => home_url( '/product/gbp-setup-optimization/' ),
		'/website-audit'                 => home_url( '/product/website-seo-audit/' ),
		'/website-fix'                   => home_url( '/product/fix-my-website/' ),
		'/local-seo'                     => home_url( '/product/local-seo-management-starter/' ),
		'/local-seo-monthly'             => home_url( '/product/local-seo-management-starter/' ),
		'/social-media-management'       => home_url( '/product/social-media-management/' ),
		'/linkedin-ghostwriting'         => home_url( '/product/linkedin-viral-posts-for-professionals/' ),
		'/product/linkedin-outreach-launch' => home_url( '/product/linkedin-outreach-launch-service/' ),
		'/product/seo-growth-packages'      => home_url( '/product/comprehensive-seo-packages-rank-1-on-google/' ),
		'/product/google-review-assistance' => home_url( '/product/remove-negative-google-reviews/' ),
		'/expert-social-media-marketing'    => home_url( '/' ),
		'/about-us-3'                       => home_url( '/about-us/' ),
		'/about-us-2'                       => home_url( '/about-us/' ),
		'/contact-us-3'                     => home_url( '/contact-us/' ),
		'/contact-us-2'                     => home_url( '/contact-us/' ),
		'/facebook-2'                       => home_url( '/facebook/' ),
		'/twitter-likes'                    => home_url( '/product/facebook-growth-visibility-campaigns/' ),
	);

	if ( ! function_exists( 'bds_aam_render_route' ) ) {
		$map['/ai-ads'] = home_url( '/services/' ) . '#lane-ai';
	}

	if ( ! isset( $map[ $path ] ) ) {
		return;
	}

	$to = $map[ $path ];
	if ( function_exists( 'bds_po_url' ) ) {
		$to = bds_po_url( $to );
	} elseif ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $ref !== '' && false === strpos( $to, 'ref=' ) ) {
			$to .= ( false === strpos( $to, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
		}
	}

	if ( 0 === strpos( $to, 'https://' ) && false === strpos( $to, home_url( '/' ) ) ) {
		wp_redirect( $to, 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- sister BrandDad hosts
		exit;
	}
	wp_safe_redirect( $to, 301 );
	exit;
}
// Before Squirrly 404->home and WF301; before Unified Brand soft_path (may not hook if BDSU_LOADED early-return).
add_action( 'template_redirect', 'bds_soft_path_redirects_mu', -20 );
