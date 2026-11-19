<?php
/**
 * Plugin Name: BrandDad Directory Featured Ads
 * Description: Placement inventory, complimentary trials, partnership grants, and fair rotation. Paid reservations stay inactive until WooCommerce confirms payment. No automatic charges.
 * Version: 0.2.3
 * Author: BrandDad
 *
 * Deploy: wp-content/mu-plugins/bds-directory-featured-ads-mu.php
 * Secure design:
 * - Humans use WordPress cookies. Admin grants need manage_options + nonce.
 * - Public featured inquiry: nonce, honeypot, 5/hour hashed IP, allowlisted level, length-capped text. No URL fetch, no file upload, no card data. Price is never taken from POST.
 * - Paid activation still requires Woo is_paid() plus an allowlisted SKU. listing_id is an int on a published at_biz_dir. Client-supplied totals are ignored.
 * - Empty-lane CTA and /featured-advertising/ do not charge. Virtual path is an exact allowlist.
 * - GET listing is digits-only then a published at_biz_dir check. Fail closed to empty. Value is escaped into the form. No outbound fetch.
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
		$copy = bds_feat_empty_copy( $level );
		$buy  = esc_url( home_url( '/featured-advertising/' ) );
		$html .= '<p>' . esc_html__( 'No featured businesses are showing here yet.', 'branddad' ) . '</p>';
		if ( '' !== $copy['price'] ) {
			$html .= '<p>' . esc_html(
				sprintf(
					/* translators: 1: placement name, 2: monthly price */
					__( '%1$s is $%2$s per month after the listing is claimed. Request a placement — checkout opens only when that product is already for sale.', 'branddad' ),
					$copy['label'],
					$copy['price']
				)
			) . '</p>';
		}
		$html .= '<a class="bds-feat-buy" href="' . $buy . '">' . esc_html( $copy['cta'] ) . '</a></section>';
		return $html;
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

function bds_feat_styles() {
	if ( ! bds_feat_is_directory() ) {
		return;
	}
	echo '<style id="bds-feat-css">.bds-feat-home,.bds-feat-buy-page{max-width:1100px;margin:0 auto;padding:16px 20px 32px;color:#0b172a}.bds-feat-lane{margin:20px 0;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff}.bds-feat-buy,.bds-feat-buy-page a.bds-feat-buy{display:inline-flex;align-items:center;justify-content:center;min-height:48px;margin:8px 8px 0 0;padding:0 16px;border-radius:10px;background:#0878ed;color:#fff;font-weight:800;text-decoration:none}.bds-feat-buy--quiet{background:#071a33}.bds-feat-buy-page form p{display:flex;flex-direction:column;gap:6px;max-width:640px}.bds-feat-buy-page input,.bds-feat-buy-page select,.bds-feat-buy-page textarea{min-height:44px;padding:8px;border:1px solid #dbe5ef;border-radius:8px}.bds-feat-hp{position:absolute;left:-9999px}#bds-feat-claim-ctas,#bds-feat-listing-ctas,#bds-feat-plans-ctas,#bds-feat-door-ctas{margin:24px auto;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff;max-width:1100px;color:#0b172a}</style>';
}
add_action( 'wp_head', 'bds_feat_styles', 40 );

function bds_feat_product_url( $level ) {
	$sku = bds_feat_product_sku( $level );
	if ( '' === $sku ) {
		return '';
	}
	if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
		$pid = (int) wc_get_product_id_by_sku( $sku );
		if ( $pid > 0 && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $pid );
			if ( $product && method_exists( $product, 'is_purchasable' ) && $product->is_purchasable() && 'publish' === get_post_status( $pid ) ) {
				return get_permalink( $pid );
			}
		}
	}
	$post = get_page_by_path( 'featured-' . sanitize_title( $level ), OBJECT, 'product' );
	if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
		return get_permalink( $post );
	}
	return '';
}

function bds_feat_buy_markup() {
	$levels = bds_feat_levels();
	$status = isset( $_GET['bds_feat'] ) ? sanitize_key( wp_unslash( $_GET['bds_feat'] ) ) : '';
	$note   = 'saved' === $status
		? __( 'Your featured request was saved. BrandDad will confirm the listing and open checkout only when that product is already for sale.', 'branddad' )
		: '';
	$list   = esc_url( home_url( '/add-listing/' ) );
	$plans  = esc_url( home_url( '/select-your-plan/' ) );
	$claim  = esc_url( home_url( '/claim-your-business-listing-branddad-directory/' ) );
	$html   = '<div class="bds-feat-buy-page" id="bds-featured-buy">';
	$html  .= '<h1>' . esc_html__( 'Get featured on BrandDad Directory', 'branddad' ) . '</h1>';
	$html  .= '<p>' . esc_html__( 'Claim or create your listing, then request a paid lane. Featured ads rotate with paid first. This page does not invent traffic or rankings.', 'branddad' ) . '</p>';
	if ( '' !== $note ) {
		$html .= '<p role="status">' . esc_html( $note ) . '</p>';
	}
	$html .= '<p><a class="bds-feat-buy" href="' . $list . '">' . esc_html__( 'List my business', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . $claim . '">' . esc_html__( 'Claim a listing', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . $plans . '">' . esc_html__( 'See membership plans', 'branddad' ) . '</a></p>';
	$html .= '<div class="bds-feat-grid">';
	foreach ( $levels as $slug => $row ) {
		$url   = bds_feat_product_url( $slug );
		$html .= '<article class="bds-feat-lane"><h2>' . esc_html( $row['label'] ) . '</h2>';
		$html .= '<p class="bds-feat-price">$' . esc_html( $row['price'] ) . ' / ' . esc_html__( 'month', 'branddad' ) . '</p>';
		if ( '' !== $url ) {
			$html .= '<a class="bds-feat-buy" href="' . esc_url( $url ) . '">' . esc_html__( 'Subscribe now', 'branddad' ) . '</a>';
		} else {
			$html .= '<p>' . esc_html__( 'Checkout is not open for this lane yet. Send a request below.', 'branddad' ) . '</p>';
		}
		$html .= '</article>';
	}
	$html .= '</div>';
	$html .= '<h2>' . esc_html__( 'Request a featured placement', 'branddad' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Use this if checkout is closed. We match the request to a claimed listing before any charge.', 'branddad' ) . '</p>';
	$html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$html .= wp_nonce_field( 'bds_feat_request', 'bds_feat_request_nonce', true, false );
	$html .= '<input type="hidden" name="action" value="bds_feat_request" />';
	$html .= '<p class="bds-feat-hp" aria-hidden="true"><label>Company website <input type="text" name="bds_company_url" tabindex="-1" autocomplete="off" /></label></p>';
	$html .= '<p><label>' . esc_html__( 'Placement', 'branddad' ) . '<select name="level" required>';
	foreach ( $levels as $slug => $row ) {
		$html .= '<option value="' . esc_attr( $slug ) . '">' . esc_html( $row['label'] . ' — $' . $row['price'] . '/month' ) . '</option>';
	}
	$html .= '</select></label></p>';
	$listing_id  = bds_feat_requested_listing_id();
	$listing_val = $listing_id > 0 ? (string) $listing_id : '';
	$html .= '<p><label>' . esc_html__( 'Listing ID or Directory URL', 'branddad' ) . '<input name="listing_ref" maxlength="160" required value="' . esc_attr( $listing_val ) . '" /></label></p>';
	$html .= '<p><label>' . esc_html__( 'Your name', 'branddad' ) . '<input name="customer_name" maxlength="80" required /></label></p>';
	$html .= '<p><label>' . esc_html__( 'Email', 'branddad' ) . '<input name="email" type="email" maxlength="120" required /></label></p>';
	$html .= '<p><label>' . esc_html__( 'What should customers see?', 'branddad' ) . '<textarea name="note" maxlength="400"></textarea></label></p>';
	$html .= '<p><button class="bds-feat-buy" type="submit">' . esc_html__( 'Send the featured request', 'branddad' ) . '</button></p></form></div>';
	return $html;
}

function bds_feat_buy_path() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return 'featured-advertising' === trim( $path, '/' );
}

function bds_feat_intercept_buy_page() {
	if ( ! bds_feat_is_directory() || is_admin() || ! bds_feat_buy_path() ) {
		return;
	}
	$page = get_page_by_path( 'featured-advertising' );
	if ( $page && 'publish' === get_post_status( $page ) ) {
		return;
	}
	global $wp_query;
	$wp_query->is_404  = false;
	$wp_query->is_page = true;
	status_header( 200 );
	nocache_headers();
	add_filter(
		'pre_get_document_title',
		static function () {
			return __( 'Get featured on BrandDad Directory', 'branddad' );
		},
		20
	);
	get_header();
	echo bds_feat_buy_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	get_footer();
	exit;
}
add_action( 'wp', 'bds_feat_clear_buy_404', 0 );
add_action( 'template_redirect', 'bds_feat_intercept_buy_page', 0 );

function bds_feat_clear_buy_404() {
	if ( ! bds_feat_is_directory() || is_admin() || ! bds_feat_buy_path() ) {
		return;
	}
	global $wp_query;
	$wp_query->is_404 = false;
	status_header( 200 );
}
add_shortcode( 'bds_featured_buy', 'bds_feat_buy_markup' );

function bds_feat_claim_guide_ctas( $content ) {
	if ( ! bds_feat_is_directory() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post || 'claim-your-business-listing-branddad-directory' !== $post->post_name ) {
		return $content;
	}
	if ( false !== strpos( $content, 'id="bds-feat-claim-ctas"' ) ) {
		return $content;
	}
	$band  = '<div id="bds-feat-claim-ctas"><h2>' . esc_html__( 'Claim, list, or get featured', 'branddad' ) . '</h2>';
	$band .= '<p>' . esc_html__( 'Open your listing, use Claim this listing, or start a new listing. Featured placement is a separate paid lane after the listing is claimed.', 'branddad' ) . '</p>';
	$band .= '<p><a class="bds-feat-buy" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Find my listing', 'branddad' ) . '</a>';
	$band .= '<a class="bds-feat-buy" href="' . esc_url( home_url( '/add-listing/' ) ) . '">' . esc_html__( 'List my business', 'branddad' ) . '</a>';
	$band .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( home_url( '/featured-advertising/' ) ) . '">' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$band .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( home_url( '/select-your-plan/' ) ) . '">' . esc_html__( 'See membership plans', 'branddad' ) . '</a></p></div>';
	return $content . $band;
}
add_filter( 'the_content', 'bds_feat_claim_guide_ctas', 30 );

function bds_feat_requested_listing_id() {
	$raw = isset( $_GET['listing'] ) ? wp_unslash( $_GET['listing'] ) : '';
	$id  = bds_feat_parse_listing_id( $raw );
	if ( $id < 1 ) {
		return 0;
	}
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		return 0;
	}
	return $id;
}

function bds_feat_listing_cta_html() {
	static $printed = false;
	if ( $printed || ! bds_feat_is_directory() || is_admin() ) {
		return '';
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		$post = get_post();
	}
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		return '';
	}
	$id = (int) $post->ID;
	if ( $id < 1 ) {
		return '';
	}
	$printed = true;
	$feat    = add_query_arg( 'listing', $id, home_url( '/featured-advertising/' ) );
	$claim   = home_url( '/claim-your-business-listing-branddad-directory/' );
	$html    = '<div class="bds-feat-claim" id="bds-feat-listing-ctas">';
	$html   .= '<h2>' . esc_html__( 'Claim or get featured', 'branddad' ) . '</h2>';
	$html   .= '<p>' . esc_html__( 'If this is your business, claim the listing. Featured placement is a paid lane after that. This does not transfer ownership by itself.', 'branddad' ) . '</p>';
	$html   .= '<p><a class="bds-feat-buy" href="' . esc_url( $feat ) . '">' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$html   .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( $claim ) . '">' . esc_html__( 'How to claim', 'branddad' ) . '</a></p></div>';
	return $html;
}

function bds_feat_listing_cta_content( $content ) {
	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$html = bds_feat_listing_cta_html();
	if ( '' === $html ) {
		return $content;
	}
	return $content . $html;
}
add_filter( 'the_content', 'bds_feat_listing_cta_content', 32 );

function bds_feat_listing_cta_footer() {
	$html = bds_feat_listing_cta_html();
	if ( '' !== $html ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'bds_feat_listing_cta_footer', 25 );
add_action( 'atbdp_after_single_listing', 'bds_feat_listing_cta_footer', 25 );
add_action( 'directorist_single_listing_after_content', 'bds_feat_listing_cta_footer', 25 );

function bds_feat_plans_page_ctas() {
	return bds_feat_door_band(
		__( 'Also list or get featured', 'branddad' ),
		__( 'Activation stays $6.99 lifetime. Featured placement is a separate monthly lane and does not replace membership.', 'branddad' )
	);
}

function bds_feat_request_path() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return trim( $path, '/' );
}

function bds_feat_door_band( $heading, $intro ) {
	$html  = '<div id="bds-feat-door-ctas"><h2>' . esc_html( $heading ) . '</h2>';
	$html .= '<p>' . esc_html( $intro ) . '</p>';
	$html .= '<p><a class="bds-feat-buy" href="' . esc_url( home_url( '/add-listing/' ) ) . '">' . esc_html__( 'List my business', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy" href="' . esc_url( home_url( '/featured-advertising/' ) ) . '">' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( home_url( '/claim-your-business-listing-branddad-directory/' ) ) . '">' . esc_html__( 'How to claim', 'branddad' ) . '</a></p></div>';
	return $html;
}

function bds_feat_path_ctas() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	$path = bds_feat_request_path();
	if ( is_404() ) {
		echo bds_feat_door_band(
			__( 'Find a business or get featured', 'branddad' ),
			__( 'This page is missing. List, claim, or request Featured instead. Membership prices on Select your plan are unchanged.', 'branddad' )
		);
		return;
	}
	if ( is_front_page() ) {
		echo bds_feat_door_band(
			__( 'Own a business on this Directory?', 'branddad' ),
			__( 'List or claim, then request Featured. Activation stays $6.99 lifetime. Featured is a separate monthly lane.', 'branddad' )
		);
		return;
	}
	if ( 'select-your-plan' === $path ) {
		echo bds_feat_plans_page_ctas(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	if ( 'add-listing' === $path ) {
		echo bds_feat_door_band(
			__( 'Get found, then get featured', 'branddad' ),
			__( 'A basic listing is free after the existing $6.99 lifetime activation. Featured placement is a separate monthly request.', 'branddad' )
		);
		return;
	}
	if ( is_post_type_archive( 'at_biz_dir' ) || ( is_search() && ! is_singular() ) ) {
		echo bds_feat_door_band(
			__( 'Own a business on this list?', 'branddad' ),
			__( 'Claim or create your listing, then request Featured. This does not invent rankings.', 'branddad' )
		);
	}
}
add_action( 'wp_footer', 'bds_feat_path_ctas', 24 );

function bds_feat_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'bds_feat_req_' . md5( $ip . '|' . wp_salt( 'auth' ) );
}

function bds_feat_handle_request() {
	if ( ! bds_feat_is_directory() ) {
		wp_die( esc_html__( 'This form is not available here.', 'branddad' ), 404 );
	}
	if ( ! isset( $_POST['bds_feat_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_feat_request_nonce'] ) ), 'bds_feat_request' ) ) {
		wp_die( esc_html__( 'The form expired. Reload the page and try again.', 'branddad' ), 403 );
	}
	if ( ! empty( $_POST['bds_company_url'] ) ) {
		wp_safe_redirect( home_url( '/featured-advertising/?bds_feat=saved' ) );
		exit;
	}
	$key = bds_feat_client_key();
	if ( (int) get_transient( $key ) >= 5 ) {
		wp_die( esc_html__( 'Too many requests were sent. Try again later.', 'branddad' ), 429 );
	}
	$levels = bds_feat_levels();
	$level  = isset( $_POST['level'] ) ? sanitize_key( wp_unslash( $_POST['level'] ) ) : '';
	$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$name   = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$ref    = isset( $_POST['listing_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['listing_ref'] ) ) : '';
	$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	if ( ! isset( $levels[ $level ] ) || ! is_email( $email ) || '' === $name || '' === $ref ) {
		wp_die( esc_html__( 'Check the placement, listing, name, and email.', 'branddad' ), 400 );
	}
	if ( strlen( $ref ) > 160 || strlen( $note ) > 400 ) {
		wp_die( esc_html__( 'That request is too long.', 'branddad' ), 400 );
	}
	$listing_id = ctype_digit( $ref ) ? (int) $ref : 0;
	$body       = implode(
		"\n\n",
		array(
			'Level: ' . $levels[ $level ]['label'] . ' ($' . $levels[ $level ]['price'] . '/month catalog)',
			'Name: ' . $name,
			'Email: ' . $email,
			'Listing ref: ' . $ref,
			'Listing ID parsed: ' . $listing_id,
			'Note: ' . $note,
			'Payment: not collected. Placement: not activated.',
		)
	);
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'bds_feat_request',
			'post_status'  => 'private',
			'post_title'   => 'Featured request ' . $level,
			'post_content' => $body,
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		wp_die( esc_html__( 'The request could not be saved. Try again later.', 'branddad' ), 500 );
	}
	update_post_meta( (int) $post_id, '_bds_feat_email', $email );
	update_post_meta( (int) $post_id, '_bds_feat_level', $level );
	update_post_meta( (int) $post_id, '_bds_feat_listing_ref', $ref );
	set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	wp_safe_redirect( home_url( '/featured-advertising/?bds_feat=saved' ) );
	exit;
}
add_action( 'admin_post_bds_feat_request', 'bds_feat_handle_request' );
add_action( 'admin_post_nopriv_bds_feat_request', 'bds_feat_handle_request' );

function bds_feat_register_request_type() {
	if ( ! bds_feat_is_directory() ) {
		return;
	}
	register_post_type(
		'bds_feat_request',
		array(
			'label'           => 'Featured requests',
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
		)
	);
}
add_action( 'init', 'bds_feat_register_request_type' );

function bds_feat_sku_level( $sku ) {
	$sku = sanitize_title( (string) $sku );
	foreach ( array_keys( bds_feat_levels() ) as $level ) {
		if ( bds_feat_product_sku( $level ) === $sku ) {
			return $level;
		}
	}
	return '';
}

function bds_feat_activate_paid_order( $order_id ) {
	if ( ! bds_feat_is_directory() || ! bds_feat_order_is_paid( $order_id ) || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( (int) $order_id );
	if ( ! $order ) {
		return;
	}
	$listing_id = (int) $order->get_meta( '_bds_feat_listing_id' );
	$level      = '';
	foreach ( $order->get_items() as $item ) {
		$product = method_exists( $item, 'get_product' ) ? $item->get_product() : null;
		$sku     = $product && method_exists( $product, 'get_sku' ) ? (string) $product->get_sku() : '';
		$found   = bds_feat_sku_level( $sku );
		if ( '' !== $found ) {
			$level = $found;
			if ( $listing_id < 1 ) {
				$listing_id = (int) $item->get_meta( '_bds_feat_listing_id' );
			}
			break;
		}
	}
	if ( '' === $level || $listing_id < 1 ) {
		return;
	}
	$post = get_post( $listing_id );
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		return;
	}
	if ( ! bds_feat_has_capacity( bds_feat_grants(), $level ) ) {
		bds_feat_audit( 'capacity', 'order_' . (int) $order_id, array( 'level' => $level ) );
		return;
	}
	foreach ( bds_feat_grants() as $grant ) {
		if ( (int) ( $grant['order_id'] ?? 0 ) === (int) $order_id && 'paid' === ( $grant['kind'] ?? '' ) ) {
			return;
		}
	}
	$grant    = array(
		'id'         => 'g_' . wp_generate_password( 12, false, false ),
		'listing_id' => $listing_id,
		'kind'       => 'paid',
		'level'      => $level,
		'status'     => 'active',
		'starts'     => gmdate( 'c' ),
		'ends'       => gmdate( 'c', time() + 30 * DAY_IN_SECONDS ),
		'country'    => '',
		'lat'        => null,
		'lng'        => null,
		'notes'      => 'woo:' . (int) $order_id,
		'order_id'   => (int) $order_id,
		'actor'      => 0,
	);
	$grants   = bds_feat_grants();
	$grants[] = $grant;
	bds_feat_save_grants( $grants );
	bds_feat_audit( 'woo_activate', $grant['id'], array( 'order_id' => (string) (int) $order_id, 'level' => $level ) );
}
add_action( 'woocommerce_order_status_processing', 'bds_feat_activate_paid_order', 20, 1 );
add_action( 'woocommerce_order_status_completed', 'bds_feat_activate_paid_order', 20, 1 );
