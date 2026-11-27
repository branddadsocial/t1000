<?php
/**
 * Plugin Name: BrandDad Done-for-You Packages Overlay
 * Description: Replaces only the Services-page managed-growth section while the canonical frontend file is host-locked.
 * Version: 1.1.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_PACKAGES_VERSION', '1.1.0' );

/** Render one trusted public package card. */
function bds_dfy_package_card( array $package, $contact ) {
	$html = '<article class="bds-fp-card">';
	if ( ! empty( $package['eyebrow'] ) ) {
		$html .= '<p class="bds-fp-eyebrow">' . esc_html( $package['eyebrow'] ) . '</p>';
	}
	$html .= '<h3>' . esc_html( $package['name'] ) . '</h3>';
	$html .= '<p class="bds-fp-price">' . esc_html( $package['price'] ) . '</p>';
	$html .= '<p>' . esc_html( $package['summary'] ) . '</p>';
	$html .= '<a class="bds-fp-link" href="' . esc_url( $contact ) . '">' . esc_html( $package['label'] ) . ' <span aria-hidden="true">&rarr;</span></a>';
	return $html . '</article>';
}

/**
 * Build the approved public section.
 *
 * Security boundary: this is trusted presentation copy only. It creates no
 * order, entitlement, provider request, credential, subscription, or payment.
 * Fulfillment remains fail-closed until the server-side integration passes.
 */
function bds_dfy_packages_section() {
	$contact  = home_url( '/contact-us/' );
	$packages = array(
		array(
			'name'    => 'Complete Management + Essential Visibility',
			'price'   => '$599/month total',
			'summary' => 'Everything in Complete Management plus optional BDS Network visibility support for up to 12 eligible posts each month. Choose US Focused or Worldwide.',
			'label'   => 'Choose Essential Visibility',
		),
		array(
			'name'    => 'Complete Management + Growth Visibility',
			'price'   => '$699/month total',
			'eyebrow' => 'Best value',
			'summary' => 'Everything in Complete Management plus optional BDS Network visibility support for up to 20 eligible posts each month. Choose US Focused or Worldwide.',
			'label'   => 'Choose Growth Visibility',
		),
		array(
			'name'    => 'Complete Management + Maximum Visibility',
			'price'   => '$849/month total',
			'summary' => 'Complete Management with up to 30 approved posts total, our highest optional BDS Network visibility allowance, and a priority monthly review. Choose US Focused or Worldwide.',
			'label'   => 'Choose Maximum Visibility',
		),
	);

	$cards = '';
	foreach ( $packages as $package ) {
		$cards .= bds_dfy_package_card( $package, $contact );
	}

	return '<section class="bds-fp-section bds-fp-section--soft" id="bds-fp-business-autopilot" data-bds-dfy-packages="' . esc_attr( BDS_DFY_PACKAGES_VERSION ) . '">'
		. '<p class="bds-fp-eyebrow">Done-for-you business management</p>'
		. '<h2>Complete management first. Add extra visibility only if you want it.</h2>'
		. '<p>BrandDad can keep your social content moving, support LinkedIn, help with customer conversations, follow up with leads, and keep website chat useful. You do not have to buy BDS Network engagement.</p>'
		. '<article class="bds-fp-card bds-fp-autopilot-addon"><p class="bds-fp-eyebrow">Complete management without engagement</p><h3>Done-for-You Complete Management</h3><p class="bds-fp-price">$399/month</p><p>Up to 20 approved social posts each month, LinkedIn content support, conversation and reply help using your approved business information, lead follow-up, website chat, and a clear monthly report. No BDS Network engagement is included or required.</p><a class="bds-fp-link" href="' . esc_url( $contact ) . '">Choose Complete Management <span aria-hidden="true">&rarr;</span></a></article>'
		. '<p class="bds-fp-eyebrow" style="margin-top:32px">Optional BDS Network visibility upgrades</p><p>Keep the $399 plan by itself, or add eligible-post visibility for a <strong>US Focused</strong> or <strong>Worldwide</strong> audience. The totals below include Complete Management.</p>'
		. '<div class="bds-fp-grid">' . $cards . '</div>'
		. '<article class="bds-fp-card bds-fp-autopilot-addon"><p class="bds-fp-eyebrow">Clear monthly proof</p><h3>See what BrandDad completed and what the campaigns delivered.</h3><p>Your report separates content completed, eligible visibility campaigns confirmed, campaign-assisted reach, impressions, saves, reposts and conversations when those measurements are available, plus recorded leads. Pending or unverified activity is not counted as completed. Platform placement, reach, followers, leads, and sales are not guaranteed.</p><a class="bds-fp-link" href="' . esc_url( $contact ) . '">Ask which option fits <span aria-hidden="true">&rarr;</span></a></article>'
		. '</section>';
}

/** Replace only the existing managed-growth section after the canonical renderer runs. */
function bds_dfy_packages_filter( $content ) {
	$rendered = function_exists( 'bds_fp_filter_content' ) ? bds_fp_filter_content( $content ) : $content;
	if ( ! is_string( $rendered ) || false === strpos( $rendered, 'id="bds-fp-business-autopilot"' ) ) {
		return $rendered;
	}

	$pattern  = '~<section\b[^>]*\bid=(["\'])bds-fp-business-autopilot\1[^>]*>.*?</section>~s';
	$replaced = preg_replace_callback(
		$pattern,
		static function () {
			return bds_dfy_packages_section();
		},
		$rendered,
		1
	);
	return is_string( $replaced ) ? $replaced : $rendered;
}

/** Install the overlay after the canonical polish plugin, without duplicating output. */
function bds_dfy_packages_install() {
	remove_filter( 'the_content', 'bds_fp_filter_content', PHP_INT_MAX );
	remove_filter( 'the_content', 'bds_dfy_packages_filter', PHP_INT_MAX );
	add_filter( 'the_content', 'bds_dfy_packages_filter', PHP_INT_MAX );
}

bds_dfy_packages_install();
add_action( 'wp', 'bds_dfy_packages_install', PHP_INT_MAX );
