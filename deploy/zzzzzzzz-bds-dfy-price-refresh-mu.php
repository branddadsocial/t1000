<?php
/**
 * Plugin Name: BrandDad Done-for-You Price Refresh
 * Description: Applies the approved lower quote-first promotion totals when host opcode cache retains an older Services renderer.
 * Version: 1.0.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_PRICE_REFRESH_VERSION', '1.0.0' );

/**
 * Render the canonical page, then repair only the three exact package prices
 * inside the trusted Done-for-You section. This is presentation only.
 */
function bds_dfy_price_refresh_filter( $content ) {
	$rendered = function_exists( 'bds_fp_filter_content' ) ? bds_fp_filter_content( $content ) : $content;
	if ( ! is_string( $rendered ) || false === strpos( $rendered, 'id="bds-fp-business-autopilot"' ) ) {
		return $rendered;
	}

	$pattern = '~<section\b[^>]*\bid=(["\'])bds-fp-business-autopilot\1[^>]*>.*?</section>~s';
	$updated = preg_replace_callback(
		$pattern,
		static function ( $matches ) {
			$section = (string) $matches[0];
			$prices  = array(
				'Complete Management \+ Promotion Starter' => '$449/month total',
				'Complete Management \+ Promotion Growth'  => '$499/month total',
				'Complete Management \+ Promotion Pro'     => '$599/month total',
			);
			foreach ( $prices as $name => $price ) {
				$section = preg_replace_callback(
					'~(' . $name . '.*?<p class="bds-fp-price">)\$[0-9]+/month total(</p>)~s',
					static function ( $price_matches ) use ( $price ) {
						return $price_matches[1] . $price . $price_matches[2];
					},
					$section,
					1
				);
			}
			if ( false === strpos( $section, 'data-bds-dfy-price-refresh=' ) ) {
				$section = preg_replace(
					'~^<section\b~',
					'<section data-bds-dfy-price-refresh="' . esc_attr( BDS_DFY_PRICE_REFRESH_VERSION ) . '"',
					$section,
					1
				);
			}
			return is_string( $section ) ? $section : (string) $matches[0];
		},
		$rendered,
		1
	);

	return is_string( $updated ) ? $updated : $rendered;
}

/** Keep one final Services renderer even when an older MU file is cached. */
function bds_dfy_price_refresh_install() {
	remove_filter( 'the_content', 'bds_fp_filter_content', PHP_INT_MAX );
	remove_filter( 'the_content', 'bds_dfy_packages_filter', PHP_INT_MAX );
	remove_filter( 'the_content', 'bds_dfy_official_promotion_filter', PHP_INT_MAX );
	remove_filter( 'the_content', 'bds_dfy_price_refresh_filter', PHP_INT_MAX );
	add_filter( 'the_content', 'bds_dfy_price_refresh_filter', PHP_INT_MAX );
}

bds_dfy_price_refresh_install();
add_action( 'wp', 'bds_dfy_price_refresh_install', PHP_INT_MAX );
