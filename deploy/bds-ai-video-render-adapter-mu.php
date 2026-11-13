<?php
/**
 * Plugin Name: BrandDad AI Video Render Adapter (Staged)
 * Description: Signed, allowlisted dispatch from the staged AI Video Studio to a BrandDad-owned render worker.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read and validate the single operator-configured worker endpoint.
 *
 * Customers never supply this value. The separate allowed-host constant prevents
 * a compromised option or accidental environment edit from becoming an SSRF sink.
 */
function bds_video_render_adapter_endpoint() {
	if ( ! defined( 'BDS_VIDEO_RENDER_ENDPOINT' ) || ! defined( 'BDS_VIDEO_RENDER_ALLOWED_HOST' ) ) {
		return '';
	}
	$endpoint = trim( (string) BDS_VIDEO_RENDER_ENDPOINT );
	$allowed  = strtolower( trim( (string) BDS_VIDEO_RENDER_ALLOWED_HOST ) );
	$parts    = wp_parse_url( $endpoint );
	if ( ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) {
		return '';
	}
	$host = strtolower( (string) ( $parts['host'] ?? '' ) );
	if ( '' === $host || $host !== $allowed || filter_var( $host, FILTER_VALIDATE_IP ) ) {
		return '';
	}
	if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) || ! empty( $parts['query'] ) || ! empty( $parts['fragment'] ) ) {
		return '';
	}
	if ( '/v1/render' !== (string) ( $parts['path'] ?? '' ) ) {
		return '';
	}
	return $endpoint;
}

function bds_video_render_adapter_ready() {
	return function_exists( 'bds_video_flag' )
		&& bds_video_flag( 'BDS_VIDEO_PROVIDER_ENABLED' )
		&& '' !== bds_video_render_adapter_endpoint()
		&& defined( 'BDS_VIDEO_RENDER_DISPATCH_SECRET' )
		&& strlen( (string) BDS_VIDEO_RENDER_DISPATCH_SECRET ) >= 32;
}

/** Sanitize and bound text without requiring the optional mbstring extension. */
function bds_video_render_adapter_text( $value, $max_length ) {
	$value      = sanitize_textarea_field( (string) $value );
	$max_length = max( 0, absint( $max_length ) );
	return function_exists( 'mb_substr' )
		? mb_substr( $value, 0, $max_length )
		: substr( $value, 0, $max_length );
}

/** Keep only the scene fields the worker contract supports. */
function bds_video_render_adapter_scenes( $project ) {
	$decoded = json_decode( (string) ( $project['scenes_json'] ?? '' ), true );
	if ( ! is_array( $decoded ) || count( $decoded ) > 50 ) {
		return array();
	}
	$scenes = array();
	foreach ( $decoded as $scene ) {
		if ( ! is_array( $scene ) ) {
			continue;
		}
		$duration = min( 90, max( 1, absint( $scene['durationSeconds'] ?? 1 ) ) );
		$scenes[] = array(
			'durationSeconds' => $duration,
			'onScreenText'    => bds_video_render_adapter_text( $scene['onScreenText'] ?? '', 500 ),
			'narration'       => bds_video_render_adapter_text( $scene['narration'] ?? '', 1200 ),
			'visualDirection' => bds_video_render_adapter_text( $scene['visualDirection'] ?? '', 1200 ),
		);
	}
	return $scenes;
}

/**
 * Dispatch an already-created, credit-reserved render job.
 *
 * The caller treats null as a failed adapter and refunds the reservation. This
 * function therefore fails closed on every configuration, transport, parsing,
 * or response-validation problem.
 */
function bds_video_render_adapter_dispatch( $current, $job, $project, $quote ) {
	if ( null !== $current ) {
		return $current;
	}
	if ( ! bds_video_render_adapter_ready() || ! is_array( $job ) || ! is_array( $project ) || ! is_array( $quote ) ) {
		return null;
	}
	$job_id     = (string) ( $job['public_id'] ?? '' );
	$project_id = (string) ( $project['public_id'] ?? '' );
	if ( ! wp_is_uuid( $job_id ) || ! wp_is_uuid( $project_id ) ) {
		return null;
	}
	$duration = (int) ( $project['duration_preset'] ?? 0 );
	if ( ! in_array( $duration, array( 15, 30, 60, 90 ), true ) ) {
		return null;
	}
	$aspect = (string) ( $project['aspect_ratio'] ?? '' );
	if ( ! in_array( $aspect, array( '9:16', '16:9', '1:1', '4:5' ), true ) ) {
		return null;
	}
	$callback = rest_url( 'bds-video/v1/webhooks/render' );
	if ( 'https' !== strtolower( (string) wp_parse_url( $callback, PHP_URL_SCHEME ) ) ) {
		return null;
	}
	$payload = array(
		'jobId'            => $job_id,
		'projectId'        => $project_id,
		'projectVersion'   => min( 100000, max( 1, absint( $project['version_no'] ?? 1 ) ) ),
		'template'         => 'branddad_motion_v1',
		'durationSeconds'  => $duration,
		'aspectRatio'      => $aspect,
		'language'         => strtolower( substr( sanitize_text_field( (string) ( $project['language'] ?? 'en' ) ), 0, 12 ) ),
		'sourceSummary'    => bds_video_render_adapter_text( $project['source_summary'] ?? '', 5000 ),
		'scenes'           => bds_video_render_adapter_scenes( $project ),
		'callbackUrl'      => $callback,
		'maxProviderCost'  => round( max( 0, min( 1000, (float) ( $quote['estimatedCost'] ?? 0 ) ) ), 4 ),
		'reservedCredits'  => min( 100000, max( 0, absint( $quote['reservedCredits'] ?? 0 ) ) ),
	);
	$body = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $body ) || strlen( $body ) > 100000 ) {
		return null;
	}
	$timestamp = (string) time();
	$delivery  = wp_generate_uuid4();
	$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, (string) BDS_VIDEO_RENDER_DISPATCH_SECRET );
	$response  = wp_safe_remote_post(
		bds_video_render_adapter_endpoint(),
		array(
			'timeout'     => 8,
			'redirection' => 0,
			'headers'     => array(
				'Content-Type'    => 'application/json',
				'X-BDS-Timestamp' => $timestamp,
				'X-BDS-Delivery'  => $delivery,
				'X-BDS-Signature' => $signature,
			),
			'body'        => $body,
			'data_format' => 'body',
		)
	);
	if ( is_wp_error( $response ) || 202 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}
	$response_body = (string) wp_remote_retrieve_body( $response );
	if ( strlen( $response_body ) > 16384 ) {
		return null;
	}
	$decoded = json_decode( $response_body, true );
	$provider_job_id = is_array( $decoded ) ? (string) ( $decoded['providerJobId'] ?? '' ) : '';
	if ( ! preg_match( '/\A[A-Za-z0-9._:-]{8,100}\z/', $provider_job_id ) ) {
		return null;
	}
	return array( 'providerJobId' => $provider_job_id );
}
add_filter( 'bds_video_dispatch_render', 'bds_video_render_adapter_dispatch', 10, 4 );
