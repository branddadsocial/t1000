<?php
/**
 * Plugin Name: BrandDad Directory Member Benefits Cards
 * Description: YOUR MEMBER BENEFITS — one action each. Does not change Directory header/menu.
 * Version: 1.0.1
 *
 * Deploy: directory.branddad.social wp-content/mu-plugins/bds-directory-member-benefits-mu.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_MB_VER' ) ) {
	return;
}
define( 'BDS_MB_VER', '1.0.1' );

/**
 * @param string $url URL.
 * @return string
 */
function bds_mb_po( $url ) {
	if ( function_exists( 'bds_po_url' ) ) {
		return bds_po_url( $url );
	}
	if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $ref !== '' ) {
			$url = add_query_arg( 'ref', $ref, $url );
		}
	}
	return $url;
}

/**
 * @return string
 */
function bds_mb_css() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="bds-mb-css">
.bds-mb{margin:1.1rem 0 1.4rem;max-width:52rem}
.bds-mb__eyebrow{margin:0 0 .25rem;font:750 11px/1.2 system-ui,sans-serif;letter-spacing:.06em;text-transform:uppercase;color:#1F6FEB}
.bds-mb__title{margin:0 0 .55rem;font:800 1.2rem/1.25 system-ui,sans-serif;color:#0B1F33}
.bds-mb__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.7rem}
.bds-mb__card{display:flex;flex-direction:column;border:1px solid #D7E6F7;border-radius:14px;padding:.85rem .95rem;background:#fff;text-decoration:none!important;color:#0B1F33}
.bds-mb__card strong{display:block;margin-bottom:.3rem;font:750 15px/1.25 system-ui,sans-serif}
.bds-mb__card span{display:block;flex:1;font:400 13px/1.45 system-ui,sans-serif;color:#475569;margin-bottom:.55rem}
.bds-mb__card em{font-style:normal;font:700 13px/1.2 system-ui,sans-serif;color:#1F6FEB}
</style>';
}

/**
 * Real fulfillable actions only.
 *
 * @param string $context network|dashboard
 * @return string
 */
function bds_mb_render( $context = 'dashboard' ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$listing = home_url( '/dashboard/' );
	$smm     = home_url( '/member-growth-services/' );
	$gifts   = home_url( '/single-category/gift-cards/' );
	if ( ! get_page_by_path( 'single-category/gift-cards' ) ) {
		$gifts = home_url( '/?directory_type=gift-cards' );
	}
	$featured = home_url( '/dashboard/' );
	$co       = 'https://branddad.co/get-started/?utm_source=directory&utm_medium=member_benefits&utm_campaign=member10';
	$aff      = function_exists( 'bds_aff_login_url' ) ? bds_aff_login_url() : 'https://affiliates.branddad.social/login';

	$cards = array(
		array( 'List / claim', 'Get found. Manage listings from your Directory dashboard.', 'Open listings', $listing ),
		array( 'Social Media Growth', 'Member catalog. Crypto checkout only. Unlock with paid membership.', 'Open growth services', $smm ),
		array( 'Gift cards', 'Verified wholesale inventory. Crypto checkout only. No invented member %.', 'Shop gift cards', $gifts ),
		array( 'Feature my business', 'Featured slots come with Pro/Elite plans. Pinned / Sponsored inventory is on your listing dashboard when available.', 'Open dashboard', $featured ),
		array( 'Save 10% at BrandDad.co', 'Logged-in Directory members: Member price auto-applies on eligible logos/websites. No public coupon to share.', 'Verify member savings', $co ),
		array( 'Recommend BrandDad. Get paid.', 'Affiliate portal for links, referrals, and payouts. Rates live in the portal.', 'Open affiliate portal', $aff ),
	);

	ob_start();
	echo bds_mb_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<section class="bds-mb" data-bds-mb="' . esc_attr( BDS_MB_VER ) . '" data-ctx="' . esc_attr( $context ) . '" id="bds-member-benefits">';
	echo '<p class="bds-mb__eyebrow">Your member benefits</p>';
	echo '<h2 class="bds-mb__title">Get Found. Grow. Save. Earn.</h2>';
	echo '<div class="bds-mb__grid">';
	foreach ( $cards as $c ) {
		$url = esc_url( bds_mb_po( $c[3] ) );
		echo '<a class="bds-mb__card" href="' . $url . '" data-bds-cta="member_benefit" data-bds-cta-label="' . esc_attr( $c[0] ) . '">';
		echo '<strong>' . esc_html( $c[0] ) . '</strong>';
		echo '<span>' . esc_html( $c[1] ) . '</span>';
		echo '<em>' . esc_html( $c[2] ) . '</em>';
		echo '</a>';
	}
	echo '</div></section>';
	return (string) ob_get_clean();
}

/**
 * Directorist /dashboard/ — use /ai-network/ member benefits instead (avoid duplicate cards).
 */
