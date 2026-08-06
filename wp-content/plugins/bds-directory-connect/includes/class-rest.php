<?php
/**
 * REST: live browse payload for the home page.
 *
 * @package BDS_Directory_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BDS_DC_Rest {

	/**
	 * Register routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register /bds-connect/v1/browse
	 */
	public static function register() {
		register_rest_route(
			'bds-connect/v1',
			'/browse',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'browse' ),
			)
		);
	}

	/**
	 * Return browse JSON.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function browse( $request ) {
		$force = (bool) $request->get_param( 'fresh' );
		$data  = BDS_DC_Browse_Data::get( $force );
		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', 'public, max-age=300' );
		return $response;
	}
}
