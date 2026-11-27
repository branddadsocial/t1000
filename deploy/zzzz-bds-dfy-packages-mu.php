<?php
/**
 * Plugin Name: BrandDad Done-for-You Packages Overlay
 * Description: Replaces only the Services-page managed-growth section while the canonical frontend file is host-locked.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_PACKAGES_VERSION', '1.0.0' );

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
			'name'    => 'Done-for-You Essential Engagement',
			'price'   => '$599/month',
			'summary' => 'Up to 12 approved posts each month, audience-growth assistance, early engagement for eligible posts, conversation help, LinkedIn content support, and a clear monthly summary.',
			'label'   => 'Choose Essential',
		),
		array(
			'name'    => 'Done-for-You Complete Growth',
			'price'   => '$699/month',
			'eyebrow' => 'Best value',
			'summary' => 'Up to 20 approved posts each month, US Focused or Worldwide audience growth, early engagement, relevant reply help, LinkedIn content support, lead follow-up, website chat, and monthly reporting.',
			'label'   => 'Choose Complete Growth',
		),
		array(
			'name'    => 'Done-for-You Maximum Visibility',
			'price'   => '$849/month',
			'summary' => 'Up to 30 approved posts each month, the highest included growth and engagement allowance, LinkedIn content support, conversation help, lead follow-up, website chat, and priority monthly review.',
			'label'   => 'Choose Maximum Visibility',
		),
	);

	$cards = '';
	foreach ( $packages as $package ) {
		$cards .= bds_dfy_package_card( $package, $contact );
	}

	return '<section class="bds-fp-section bds-fp-section--soft" id="bds-fp-business-autopilot" data-bds-dfy-packages="' . esc_attr( BDS_DFY_PACKAGES_VERSION ) . '">'
		. '<p class="bds-fp-eyebrow">Done-for-you social growth</p>'
		. '<h2>We help your posts get seen—and handle the conversations for you.</h2>'
		. '<p>Running a business is already a full-time job. BrandDad helps keep your social media active, gives eligible posts a stronger start through the BDS Network, and helps answer relevant questions using your approved business information. Choose <strong>US Focused</strong> or <strong>Worldwide</strong>. LinkedIn content and professional conversation support can be included as one of your connected channels.</p>'
		. '<div class="bds-fp-grid">' . $cards . '</div>'
		. '<article class="bds-fp-card bds-fp-autopilot-addon"><p class="bds-fp-eyebrow">Simple monthly setup</p><h3>You run the business. We help keep it visible.</h3><p>Tell us what the business does, choose the audience, and approve the important information. BrandDad handles the agreed monthly workload and shows you what was completed. Platform placement, reach, followers, leads, and sales are not guaranteed.</p><a class="bds-fp-link" href="' . esc_url( $contact ) . '">Let BrandDad run it <span aria-hidden="true">&rarr;</span></a></article>'
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

