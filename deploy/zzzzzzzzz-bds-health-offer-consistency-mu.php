<?php
/**
 * Plugin Name: BrandDad Health Offer Consistency
 * Description: Keeps cached website-check recommendations aligned with the current AI Website Improvement Plan.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_HEALTH_OFFER_CONSISTENCY_VERSION' ) ) {
	return;
}

define( 'BDS_HEALTH_OFFER_CONSISTENCY_VERSION', '1.0.0' );

/**
 * Determine whether a recommendation is the retired speed-optimization offer.
 *
 * @param mixed $recommendation Recommendation payload.
 * @return bool
 */
function bds_health_offer_consistency_is_legacy( $recommendation ) {
	if ( ! is_array( $recommendation ) ) {
		return false;
	}

	$key   = isset( $recommendation['key'] ) ? strtolower( trim( (string) $recommendation['key'] ) ) : '';
	$title = isset( $recommendation['title'] ) ? strtolower( trim( (string) $recommendation['title'] ) ) : '';
	$url   = isset( $recommendation['url'] ) ? strtolower( trim( (string) $recommendation['url'] ) ) : '';

	return 'website-speed-optimization' === $key
		|| 'website speed optimization' === $title
		|| false !== strpos( $url, '/product/website-speed-optimization/' );
}

/**
 * Normalize the one retired recommendation after the existing scanner/cache runs.
 *
 * @param mixed $response REST response.
 * @param mixed $server   REST server.
 * @param mixed $request  REST request.
 * @return mixed
 */
function bds_health_offer_consistency_response( $response, $server, $request ) {
	unset( $server );

	if ( ! $response instanceof WP_REST_Response || ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
		return $response;
	}
	if ( '/bds-health/v1/check' !== (string) $request->get_route() ) {
		return $response;
	}

	$data = $response->get_data();
	if ( ! is_array( $data ) || empty( $data['recommendations'] ) || ! is_array( $data['recommendations'] ) ) {
		return $response;
	}

	$changed = false;
	foreach ( $data['recommendations'] as $index => $recommendation ) {
		if ( ! bds_health_offer_consistency_is_legacy( $recommendation ) ) {
			continue;
		}

		$preserved = is_array( $recommendation ) ? $recommendation : array();
		$data['recommendations'][ $index ] = array_merge(
			$preserved,
			array(
				'key'    => 'website-speed-optimization',
				'title'  => 'AI Website Improvement Plan',
				'url'    => 'https://branddad.social/product/branddad-smartsites-website-optimizer/',
				'price'  => 19.99,
				'period' => '',
				'blurb'  => 'Receive a clear, prioritized AI improvement plan. Supported website changes require a secure connection and your approval before BrandDad applies and verifies them.',
			)
		);
		$changed = true;
	}

	if ( $changed ) {
		$response->set_data( $data );
	}

	return $response;
}
add_filter( 'rest_post_dispatch', 'bds_health_offer_consistency_response', 999, 3 );

