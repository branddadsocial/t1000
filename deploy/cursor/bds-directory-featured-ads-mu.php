<?php
/**
 * Plugin Name: BrandDad Directory Featured Ads
 * Description: Placement inventory, complimentary trials, partnership grants, and fair rotation. Paid reservations stay inactive until WooCommerce confirms payment. No automatic charges.
 * Version: 0.1.0
 * Author: BrandDad
 *
 * Deploy: wp-content/mu-plugins/bds-directory-featured-ads-mu.php
 * Secure design: WordPress auth cookies and manage_options for grants. Public cards expose only Sponsored for paid placements. Private notes stay in the option and the admin screen. Tracking tokens are HMAC'd with wp_salt and expire in 15 minutes. Music and billing stay outside this plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/bds-directory-featured-ads-lib.php';

const BDS_FEAT_OPTION = 'bds_directory_featured_grants_v1';
const BDS_FEAT_AUDIT  = 'bds_directory_featured_audit_v1';
const BDS_FEAT_SWITCH = 'bds_directory_featured_enabled';

function bds_feat_is_directory() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return 'directory.branddad.social' === $host;
}

function bds_feat_grants() {
	$grants = get_option( BDS_FEAT_OPTION, array() );
	return is_array( $grants ) ? $grants : array();
}

function bds_feat_save_grants( array $grants ) {
	update_option( BDS_FEAT_OPTION, $grants, false );
}

function bds_feat_audit( $action, $grant_id, array $extra = array() ) {
	$log   = get_option( BDS_FEAT_AUDIT, array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array(
		'time'     => gmdate( 'c' ),
		'user_id'  => get_current_user_id(),
		'action'   => sanitize_key( $action ),
		'grant_id' => sanitize_text_field( (string) $grant_id ),
		'extra'    => array_map( 'sanitize_text_field', array_map( 'strval', $extra ) ),
	);
	if ( count( $log ) > 500 ) {
		$log = array_slice( $log, -500 );
	}
	update_option( BDS_FEAT_AUDIT, $log, false );
}

function bds_feat_expire_grant( array $grant ) {
	if ( empty( $grant['ends'] ) || 'active' !== ( $grant['status'] ?? '' ) ) {
		return $grant;
	}
	if ( strtotime( (string) $grant['ends'] ) < time() ) {
		$grant['status'] = 'expired';
	}
	return $grant;
}

function bds_feat_listing_card( array $grant ) {
	$grant   = bds_feat_expire_grant( $grant );
	$listing = (int) ( $grant['listing_id'] ?? 0 );
	$post    = $listing > 0 ? get_post( $listing ) : null;
	return array(
		'id'          => (string) ( $grant['id'] ?? '' ),
		'kind'        => (string) ( $grant['kind'] ?? '' ),
		'level'       => (string) ( $grant['level'] ?? '' ),
		'active'      => 'active' === ( $grant['status'] ?? '' ) && '1' === (string) get_option( BDS_FEAT_SWITCH, '1' ),
		'country'     => (string) ( $grant['country'] ?? '' ),
		'lat'         => isset( $grant['lat'] ) && '' !== $grant['lat'] ? (float) $grant['lat'] : null,
		'lng'         => isset( $grant['lng'] ) && '' !== $grant['lng'] ? (float) $grant['lng'] : null,
		'radius_km'   => isset( $grant['radius_km'] ) && '' !== $grant['radius_km'] ? (float) $grant['radius_km'] : null,
		'impressions' => (int) ( $grant['impressions'] ?? 0 ),
		'title'       => $post instanceof WP_Post ? get_the_title( $post ) : '',
		'url'         => $post instanceof WP_Post ? get_permalink( $post ) : '',
	);
}

function bds_feat_viewer_from_request() {
	$country = isset( $_GET['bds_country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['bds_country'] ) ) ) : '';
	$lat     = null;
	$lng     = null;
	if ( isset( $_GET['bds_lat'], $_GET['bds_lng'] ) ) {
		$lat = (float) wp_unslash( $_GET['bds_lat'] );
		$lng = (float) wp_unslash( $_GET['bds_lng'] );
		if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
			$lat = null;
			$lng = null;
		}
	}
	return array(
		'country' => preg_match( '/^[A-Z]{2}$/', $country ) ? $country : '',
		'lat'     => $lat,
		'lng'     => $lng,
	);
}

function bds_feat_sign_token( $grant_id ) {
	$exp     = time() + 15 * MINUTE_IN_SECONDS;
	$payload = $grant_id . '|' . $exp;
	return $payload . '|' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
}

function bds_feat_token_ok( $token, $grant_id ) {
	$parts = explode( '|', (string) $token );
	if ( 3 !== count( $parts ) || ! hash_equals( (string) $grant_id, $parts[0] ) ) {
		return false;
	}
	if ( (int) $parts[1] < time() ) {
		return false;
	}
	$payload = $parts[0] . '|' . $parts[1];
	return hash_equals( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ), $parts[2] );
}

function bds_feat_sign_event_token( $grant_id, $type, $event_id ) {
	$exp     = time() + 15 * MINUTE_IN_SECONDS;
	$payload = $grant_id . '|' . $type . '|' . $event_id . '|' . $exp;
	return $payload . '|' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
}

function bds_feat_event_token_ok( $token, $grant_id, $type, $event_id ) {
	$parts = explode( '|', (string) $token );
	if ( 5 !== count( $parts ) ) {
		return false;
	}
	if ( ! hash_equals( (string) $grant_id, $parts[0] ) || ! hash_equals( (string) $type, $parts[1] ) || ! hash_equals( (string) $event_id, $parts[2] ) ) {
		return false;
	}
	if ( (int) $parts[3] < time() ) {
		return false;
	}
	$payload = $parts[0] . '|' . $parts[1] . '|' . $parts[2] . '|' . $parts[3];
	return hash_equals( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ), $parts[4] );
}

function bds_feat_render_lane( $title, $level, array $viewer ) {
	$levels = bds_feat_levels();
	$cap    = (int) $levels[ $level ]['cap'];
	$cards  = array();
	foreach ( bds_feat_grants() as $grant ) {
		if ( $level !== ( $grant['level'] ?? '' ) ) {
			continue;
		}
		$cards[] = bds_feat_listing_card( $grant );
	}
	$picked = bds_feat_select( $cards, $viewer, $cap );
	$html   = '<section class="bds-feat-lane"><h2>' . esc_html( $title ) . '</h2>';
	if ( empty( $picked['shown'] ) ) {
		return $html . '<p>' . esc_html__( 'No featured businesses are reserved for this view yet.', 'branddad' ) . '</p></section>';
	}
	$html .= '<div class="bds-feat-grid">';
	foreach ( $picked['shown'] as $card ) {
		if ( '' === $card['url'] || '' === $card['title'] ) {
			continue;
		}
		$disclosure = bds_feat_public_disclosure( $card['kind'] );
		$seen_id    = 'i' . substr( hash( 'sha256', $card['id'] . '|' . wp_rand() ), 0, 16 );
		$click_id   = 'c' . substr( $seen_id, 1 );
		$html      .= '<article class="bds-feat-card" data-grant="' . esc_attr( $card['id'] ) . '" data-seen="' . esc_attr( $seen_id ) . '" data-seen-token="' . esc_attr( bds_feat_sign_event_token( $card['id'], 'impression', $seen_id ) ) . '" data-click="' . esc_attr( $click_id ) . '" data-click-token="' . esc_attr( bds_feat_sign_event_token( $card['id'], 'click', $click_id ) ) . '"><h3><a href="' . esc_url( $card['url'] ) . '">' . esc_html( $card['title'] ) . '</a></h3>';
		if ( '' !== $disclosure ) {
			$html .= '<p class="bds-feat-sponsored">' . esc_html( $disclosure ) . '</p>';
		}
		$html .= '</article>';
	}
	$html .= '</div></section>';
	return $html;
}

function bds_feat_shortcode() {
	if ( ! bds_feat_is_directory() ) {
		return '';
	}
	$viewer = bds_feat_viewer_from_request();
	return bds_feat_render_lane( __( 'Featured Worldwide', 'branddad' ), 'global', $viewer )
		. bds_feat_render_lane( __( 'Featured Near You', 'branddad' ), 'local', $viewer );
}
add_shortcode( 'bds_featured_lanes', 'bds_feat_shortcode' );

function bds_feat_print_home_lanes() {
	if ( ! bds_feat_is_directory() || ! is_front_page() ) {
		return;
	}
	echo '<div id="bds-featured-lanes" class="bds-feat-home">' . bds_feat_shortcode() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_feat_print_home_lanes', 20 );

function bds_feat_listing_eligible_for_trial( $listing_id ) {
	$listing_id = (int) $listing_id;
	$post       = get_post( $listing_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return array( 'ok' => false, 'reason' => 'listing_missing' );
	}
	$claimed  = (int) get_post_meta( $listing_id, '_bds_claimed_by', true ) > 0;
	$verified = '1' === (string) get_post_meta( $listing_id, '_bds_verified', true );
	$used     = '1' === (string) get_post_meta( $listing_id, '_bds_feat_trial_used', true );
	$active   = false;
	foreach ( bds_feat_grants() as $grant ) {
		if ( (int) ( $grant['listing_id'] ?? 0 ) === $listing_id && 'active' === ( $grant['status'] ?? '' ) ) {
			$active = true;
		}
	}
	return bds_feat_trial_decision(
		array(
			'verified'     => $verified,
			'claimed'      => $claimed,
			'trial_used'   => $used,
			'grant_active' => $active,
		)
	);
}

function bds_feat_order_is_paid( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return false;
	}
	$order = wc_get_order( (int) $order_id );
	return $order && method_exists( $order, 'is_paid' ) && $order->is_paid();
}

function bds_feat_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$levels = bds_feat_levels();
	echo '<div class="wrap"><h1>Directory featured placements</h1>';
	echo '<p>Paid placements stay off until WooCommerce reports the order paid. Trials do not collect a payment method.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'bds_feat_grant' );
	echo '<input type="hidden" name="action" value="bds_feat_grant" />';
	echo '<p><label>Listing ID <input name="listing_id" type="number" min="1" required /></label></p>';
	echo '<p><label>Kind <select name="kind"><option value="trial">14-day local trial</option><option value="partnership">Partnership</option><option value="paid">Paid reservation</option></select></label></p>';
	echo '<p><label>Level <select name="level">';
	foreach ( $levels as $key => $level ) {
		echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $level['label'] . ' $' . $level['price'] ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>Days <input name="days" type="number" min="1" max="3650" value="14" /></label></p>';
	echo '<p><label>Country <input name="country" maxlength="2" /></label></p>';
	echo '<p><label>Latitude <input name="lat" type="text" inputmode="decimal" /></label></p>';
	echo '<p><label>Longitude <input name="lng" type="text" inputmode="decimal" /></label></p>';
	echo '<p><label>Order ID for paid <input name="order_id" type="number" min="1" /></label></p>';
	echo '<p><label>Private note <input name="notes" maxlength="240" /></label></p>';
	submit_button( 'Save placement' );
	echo '</form><h2>Current grants</h2><ul>';
	foreach ( bds_feat_grants() as $grant ) {
		$grant = bds_feat_expire_grant( $grant );
		echo '<li>' . esc_html( ( $grant['id'] ?? '' ) . ' listing ' . ( $grant['listing_id'] ?? '' ) . ' ' . ( $grant['kind'] ?? '' ) . ' ' . ( $grant['level'] ?? '' ) . ' ' . ( $grant['status'] ?? '' ) ) . '</li>';
	}
	echo '</ul>';
	echo '<h2>Change a placement</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'bds_feat_status' );
	echo '<input type="hidden" name="action" value="bds_feat_status" />';
	echo '<p><label>Grant ID <input name="grant_id" required /></label></p>';
	echo '<p><label>Status <select name="status"><option value="paused">Pause</option><option value="active">Resume</option><option value="revoked">Revoke</option></select></label></p>';
	submit_button( 'Update placement' );
	echo '</form></div>';
}

function bds_feat_admin_menu() {
	if ( ! bds_feat_is_directory() ) {
		return;
	}
	add_management_page( 'Featured placements', 'Featured placements', 'manage_options', 'bds-featured', 'bds_feat_admin_page' );
}
add_action( 'admin_menu', 'bds_feat_admin_menu' );

function bds_feat_handle_grant() {
	if ( ! current_user_can( 'manage_options' ) || ! bds_feat_is_directory() ) {
		wp_die( esc_html__( 'You cannot change placements.', 'branddad' ), 403 );
	}
	check_admin_referer( 'bds_feat_grant' );
	$levels     = bds_feat_levels();
	$kind       = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : '';
	$level      = isset( $_POST['level'] ) ? sanitize_key( wp_unslash( $_POST['level'] ) ) : '';
	$listing_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
	$days       = isset( $_POST['days'] ) ? (int) $_POST['days'] : 14;
	$country    = isset( $_POST['country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) : '';
	$notes      = isset( $_POST['notes'] ) ? sanitize_text_field( wp_unslash( $_POST['notes'] ) ) : '';
	$order_id   = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
	if ( ! isset( $levels[ $level ] ) || ! in_array( $kind, array( 'trial', 'partnership', 'paid' ), true ) || $listing_id <= 0 ) {
		wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=invalid' ) );
		exit;
	}
	$status = 'active';
	if ( 'paid' === $kind ) {
		$status = bds_feat_order_is_paid( $order_id ) ? 'active' : 'pending_payment';
	}
	if ( 'trial' === $kind ) {
		$decision = bds_feat_listing_eligible_for_trial( $listing_id );
		if ( empty( $decision['ok'] ) ) {
			wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=' . rawurlencode( (string) $decision['reason'] ) ) );
			exit;
		}
		$level = 'local';
		$days  = 14;
		update_post_meta( $listing_id, '_bds_feat_trial_used', '1' );
	}
	if ( ! bds_feat_has_capacity( bds_feat_grants(), $level ) ) {
		wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=capacity' ) );
		exit;
	}
	$lat_raw = isset( $_POST['lat'] ) ? trim( (string) wp_unslash( $_POST['lat'] ) ) : '';
	$lng_raw = isset( $_POST['lng'] ) ? trim( (string) wp_unslash( $_POST['lng'] ) ) : '';
	$lat     = is_numeric( $lat_raw ) ? (float) $lat_raw : null;
	$lng     = is_numeric( $lng_raw ) ? (float) $lng_raw : null;
	if ( null !== $lat && ( $lat < -90 || $lat > 90 ) ) {
		$lat = null;
	}
	if ( null !== $lng && ( $lng < -180 || $lng > 180 ) ) {
		$lng = null;
	}
	$grant = array(
		'id'         => 'g_' . wp_generate_password( 12, false, false ),
		'listing_id' => $listing_id,
		'kind'       => $kind,
		'level'      => $level,
		'status'     => $status,
		'starts'     => gmdate( 'c' ),
		'ends'       => $days > 0 ? gmdate( 'c', time() + $days * DAY_IN_SECONDS ) : '',
		'country'    => preg_match( '/^[A-Z]{2}$/', $country ) ? $country : '',
		'lat'        => $lat,
		'lng'        => $lng,
		'notes'      => $notes,
		'order_id'   => $order_id,
		'actor'      => get_current_user_id(),
	);
	$grants   = bds_feat_grants();
	$grants[] = $grant;
	bds_feat_save_grants( $grants );
	bds_feat_audit( 'create', $grant['id'], array( 'kind' => $kind, 'status' => $status ) );
	wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=saved' ) );
	exit;
}
add_action( 'admin_post_bds_feat_grant', 'bds_feat_handle_grant' );

function bds_feat_handle_status() {
	if ( ! current_user_can( 'manage_options' ) || ! bds_feat_is_directory() ) {
		wp_die( esc_html__( 'You cannot change placements.', 'branddad' ), 403 );
	}
	check_admin_referer( 'bds_feat_status' );
	$grant_id = isset( $_POST['grant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['grant_id'] ) ) : '';
	$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	if ( ! in_array( $status, array( 'active', 'paused', 'revoked' ), true ) ) {
		wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=invalid' ) );
		exit;
	}
	$grants = bds_feat_grants();
	$found  = false;
	foreach ( $grants as $index => $grant ) {
		if ( (string) ( $grant['id'] ?? '' ) !== $grant_id ) {
			continue;
		}
		if ( 'active' === $status && 'paid' === ( $grant['kind'] ?? '' ) && ! bds_feat_order_is_paid( (int) ( $grant['order_id'] ?? 0 ) ) ) {
			wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=payment_unconfirmed' ) );
			exit;
		}
		$grants[ $index ]['status'] = $status;
		$found                      = true;
		break;
	}
	if ( ! $found ) {
		wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=missing' ) );
		exit;
	}
	bds_feat_save_grants( $grants );
	bds_feat_audit( 'status', $grant_id, array( 'status' => $status ) );
	wp_safe_redirect( admin_url( 'tools.php?page=bds-featured&bds_feat=saved' ) );
	exit;
}
add_action( 'admin_post_bds_feat_status', 'bds_feat_handle_status' );

function bds_feat_handle_event() {
	if ( ! bds_feat_is_directory() ) {
		status_header( 404 );
		exit;
	}
	$grant_id = isset( $_POST['grant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['grant_id'] ) ) : '';
	$token    = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
	$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$event_id = isset( $_POST['event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) : '';
	if ( ! bds_feat_event_token_ok( $token, $grant_id, $type, $event_id ) ) {
		status_header( 403 );
		exit;
	}
	$grants = bds_feat_grants();
	foreach ( $grants as $index => $grant ) {
		if ( (string) ( $grant['id'] ?? '' ) !== $grant_id ) {
			continue;
		}
		$result = bds_feat_apply_event(
			$grant,
			array(
				'type'     => $type,
				'event_id' => $event_id,
			)
		);
		if ( empty( $result['ok'] ) ) {
			status_header( 409 );
			exit;
		}
		$grants[ $index ] = $result['grant'];
		bds_feat_save_grants( $grants );
		bds_feat_audit( 'event', $grant_id, array( 'type' => $type ) );
		status_header( 204 );
		exit;
	}
	status_header( 404 );
	exit;
}
add_action( 'admin_post_bds_feat_event', 'bds_feat_handle_event' );
add_action( 'admin_post_nopriv_bds_feat_event', 'bds_feat_handle_event' );

function bds_feat_event_script() {
	if ( ! bds_feat_is_directory() || ! is_front_page() ) {
		return;
	}
	$endpoint = admin_url( 'admin-post.php' );
	echo '<script id="bds-feat-events">document.querySelectorAll(".bds-feat-card").forEach(function(card){function send(type){var body=new URLSearchParams();body.set("action","bds_feat_event");body.set("grant_id",card.dataset.grant||"");body.set("type",type);if(type==="click"){body.set("token",card.dataset.clickToken||"");body.set("event_id",card.dataset.click||"");}else{body.set("token",card.dataset.seenToken||"");body.set("event_id",card.dataset.seen||"");}if(navigator.sendBeacon){navigator.sendBeacon(' . wp_json_encode( $endpoint ) . ',body);}else{fetch(' . wp_json_encode( $endpoint ) . ',{method:"POST",body:body,credentials:"same-origin",keepalive:true});}}send("impression");var link=card.querySelector("a");if(link){link.addEventListener("click",function(){send("click");});}});</script>';
}
add_action( 'wp_footer', 'bds_feat_event_script', 30 );
