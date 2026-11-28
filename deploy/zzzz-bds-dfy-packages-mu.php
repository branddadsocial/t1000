<?php
/**
 * Plugin Name: BrandDad Done-for-You Packages Overlay
 * Description: Replaces only the Services-page managed-growth section while the canonical frontend file is host-locked.
 * Version: 1.3.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_PACKAGES_VERSION', '1.3.0' );

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
			'name'    => 'Complete Management + Promotion Starter',
			'price'   => '$449/month total',
			'summary' => 'Everything in Complete Management plus a light, controlled promotion plan for up to 4 customer-approved paid or creator promotions, spaced through the month. Advertising spend and creator costs are separate.',
			'label'   => 'Choose Promotion Starter',
		),
		array(
			'name'    => 'Complete Management + Promotion Growth',
			'price'   => '$499/month total',
			'eyebrow' => 'Best value',
			'summary' => 'Everything in Complete Management plus planning and management for up to 8 customer-approved paid or creator promotions, spaced through the month. Advertising spend and creator costs are separate.',
			'label'   => 'Choose Promotion Growth',
		),
		array(
			'name'    => 'Complete Management + Promotion Pro',
			'price'   => '$599/month total',
			'summary' => 'Everything in Complete Management plus planning and management for up to 12 customer-approved paid or creator promotions, spaced through the month, with a priority monthly review. Advertising spend and creator costs are separate.',
			'label'   => 'Choose Promotion Pro',
		),
	);

	$cards = '';
	foreach ( $packages as $package ) {
		$cards .= bds_dfy_package_card( $package, $contact );
	}

	return '<section class="bds-fp-section bds-fp-section--soft" id="bds-fp-business-autopilot" data-bds-dfy-packages="' . esc_attr( BDS_DFY_PACKAGES_VERSION ) . '">'
		. '<p class="bds-fp-eyebrow">Done-for-you business management</p>'
		. '<h2>We help run the everyday work that keeps your business visible and responsive.</h2>'
		. '<p>BrandDad plans the work, keeps approved content moving, helps manage customer conversations, and shows you what was completed. Promotion is optional—not required to use Complete Management.</p>'
		. '<article class="bds-fp-card bds-fp-autopilot-addon"><p class="bds-fp-eyebrow">Complete management without paid promotion</p><h3>Done-for-You Complete Management</h3><p class="bds-fp-price">$399/month</p><p>One coordinated monthly service for the routine online work that is easiest to delay:</p><ul class="bds-fp-inclusions"><li>Monthly plan and content calendar</li><li>Up to 20 approved social posts, spaced through the month</li><li>LinkedIn content support</li><li>Approved replies to comments and direct messages on supported connected accounts</li><li>Business-email reply and lead follow-up support through an authorized inbox</li><li>Website chatbot knowledge updates and conversation support</li><li>Lead capture, qualification, follow-up, and human escalation when needed</li><li>Professional review-response assistance</li><li>Website monitoring and a basic monthly SEO check</li><li>A clear monthly activity and results report</li></ul><p>We confirm the supported channels, account connections, approval rules, and usage limits in writing before work starts. No paid promotion or media spend is included or required.</p><a class="bds-fp-link" href="' . esc_url( $contact ) . '">Choose Complete Management <span aria-hidden="true">&rarr;</span></a></article>'
		. '<p class="bds-fp-eyebrow" style="margin-top:32px">Optional official promotion management</p><p>Keep the $399 plan by itself, or let BrandDad plan and manage customer-approved advertising through supported official platforms or documented creator campaigns. The totals below include Complete Management; advertising spend and creator fees are separate and approved by you.</p>'
		. '<div class="bds-fp-grid">' . $cards . '</div>'
		. '<article class="bds-fp-card bds-fp-autopilot-addon"><p class="bds-fp-eyebrow">Clear monthly proof</p><h3>See what was organic, paid, and converted.</h3><p>Your report separates content completed, organic reach and impressions, paid reach, impressions, clicks and spend from supported official platforms, documented creator deliverables, conversations handled, and recorded leads. Pending or unverified activity is not counted as completed. Placement, reach, followers, leads, and sales are not guaranteed.</p><a class="bds-fp-link" href="' . esc_url( $contact ) . '">Ask which option fits <span aria-hidden="true">&rarr;</span></a></article>'
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
