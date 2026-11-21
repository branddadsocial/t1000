<?php
/**
 * Plugin Name: BrandDad Directory Featured Ads
 * Description: Placement inventory, complimentary trials, partnership grants, and fair rotation. Paid reservations stay inactive until WooCommerce confirms payment. No automatic charges.
 * Version: 0.2.29
 * Author: BrandDad
 *
 * Deploy: wp-content/mu-plugins/bds-directory-featured-ads-mu.php
 * Secure design:
 * - Humans use WordPress cookies. Admin grants need manage_options + nonce.
 * - Public featured inquiry: nonce, honeypot, 5/hour hashed IP, allowlisted level, length-capped text. No URL fetch, no file upload, no card data. Price is never taken from POST.
 * - Paid activation still requires Woo is_paid() plus an allowlisted SKU. listing_id is an int on a published at_biz_dir. Client-supplied totals are ignored.
 * - Empty-lane CTA and /featured-advertising/ do not charge. Virtual path is an exact allowlist.
 * - GET listing is digits-only then a published at_biz_dir check. Fail closed to empty. Value is escaped into the form. No outbound fetch.
 * - GET lane is an allowlisted key (local|regional|national|global). Unknown values do not preselect.
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

function bds_feat_render_lane( $title, $level, array $viewer, $title_es = '' ) {
	$levels = bds_feat_levels();
	$cap    = (int) $levels[ $level ]['cap'];
	$cards  = array();
	$sw     = function_exists( 'bds_lang_swap_attrs' );
	foreach ( bds_feat_grants() as $grant ) {
		if ( $level !== ( $grant['level'] ?? '' ) ) {
			continue;
		}
		$cards[] = bds_feat_listing_card( $grant );
	}
	$picked = bds_feat_select( $cards, $viewer, $cap );
	$html   = '<section class="bds-feat-lane"><h2' . ( $sw && '' !== $title_es ? bds_lang_swap_attrs( $title, $title_es ) : '' ) . '>' . esc_html( $title ) . '</h2>';
	if ( empty( $picked['shown'] ) ) {
		$copy = bds_feat_empty_copy( $level );
		$buy  = esc_url( home_url( '/featured-advertising/' ) );
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'No featured businesses are showing here yet.', 'Aún no hay negocios destacados aquí.' );
		} else {
			$html .= '<p>' . esc_html__( 'No featured businesses are showing here yet.', 'branddad' ) . '</p>';
		}
		if ( '' !== $copy['price'] ) {
			$en = sprintf( '%1$s is $%2$s per month after the listing is claimed. Request a placement — checkout opens only when that product is already for sale.', $copy['label'], $copy['price'] );
			$es = sprintf( '%1$s es $%2$s al mes después de reclamar el listado. Pida un espacio — el checkout abre solo si ese producto ya está a la venta.', $copy['label'], $copy['price'] );
			if ( function_exists( 'bds_lang_pair' ) ) {
				$html .= bds_lang_pair( $en, $es );
			} else {
				$html .= '<p>' . esc_html( $en ) . '</p>';
			}
		}
		$html .= '<a class="bds-feat-buy" href="' . $buy . '"' . ( $sw ? bds_lang_swap_attrs( $copy['cta'], 'Salir destacado' ) : '' ) . '>' . esc_html( $copy['cta'] ) . '</a></section>';
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
	return bds_feat_render_lane( __( 'Featured Worldwide', 'branddad' ), 'global', $viewer, 'Destacados en todo el mundo' )
		. bds_feat_render_lane( __( 'Featured Near You', 'branddad' ), 'local', $viewer, 'Destacados cerca de usted' );
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
	echo '<style id="bds-feat-css">.bds-feat-home,.bds-feat-buy-page{max-width:1100px;margin:0 auto;padding:16px 20px 32px;color:#0b172a}.bds-feat-lane{margin:20px 0;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff}.bds-feat-buy,.bds-feat-buy-page a.bds-feat-buy{display:inline-flex;align-items:center;justify-content:center;min-height:48px;margin:8px 8px 0 0;padding:0 16px;border-radius:10px;background:#0668d8;color:#fff;font-weight:800;text-decoration:none}.bds-feat-buy--quiet{background:#071a33}.bds-feat-buy-page form p{display:flex;flex-direction:column;gap:6px;max-width:640px}.bds-feat-buy-page input,.bds-feat-buy-page select,.bds-feat-buy-page textarea{min-height:44px;padding:8px;border:1px solid #dbe5ef;border-radius:8px}.bds-feat-hp{position:absolute;left:-9999px}#bds-feat-claim-ctas,#bds-feat-listing-ctas,#bds-feat-plans-ctas,#bds-feat-door-ctas{margin:24px auto;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff;max-width:1100px;color:#0b172a}.bds-feat-buy:focus-visible,.bds-feat-buy-page a:focus-visible,#bds-feat-door-ctas a:focus-visible{outline:3px solid #0668d8;outline-offset:2px}</style>';
}
add_action( 'wp_head', 'bds_feat_styles', 40 );

/** Leftover live copy. Does not start a charge. */
function bds_feat_rewrite_subscribe_now( $html ) {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return $html;
	}
	$html = (string) $html;
	if ( false === strpos( $html, 'Subscribe now' ) && false === strpos( $html, 'Suscribirse ahora' ) ) {
		return $html;
	}
	$out = preg_replace( '#data-bds-copy-en="Subscribe now"#', 'data-bds-copy-en="Subscribe monthly"', $html );
	$out = preg_replace( '#data-bds-copy-es="Suscribirse ahora"#', 'data-bds-copy-es="Suscribirse cada mes"', is_string( $out ) ? $out : $html );
	$out = preg_replace( '#(<a\b[^>]*>)Subscribe now</a>#', '$1Subscribe monthly</a>', is_string( $out ) ? $out : $html );
	return is_string( $out ) ? $out : $html;
}
add_filter( 'the_content', 'bds_feat_rewrite_subscribe_now', 1100 );

/** Leftover live copy. Does not start a charge. */
function bds_feat_rewrite_see_plans( $html ) {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return $html;
	}
	$html = (string) $html;
	if ( false === strpos( $html, 'See plans' ) && false === strpos( $html, 'Ver planes' ) ) {
		return $html;
	}
	$out = preg_replace(
		'#data-bds-copy-en="See plans"(\s+data-bds-copy-es=")Ver planes"#',
		'data-bds-copy-en="See membership plans"$1Ver planes de membresía"',
		$html
	);
	$out = preg_replace( '#(<a\b[^>]*href="[^"]*(?:select-your-plan|registration|\#bds-mt-plans)[^"]*"[^>]*)>See plans</a>#', '$1>See membership plans</a>', is_string( $out ) ? $out : $html );
	return is_string( $out ) ? $out : $html;
}
add_filter( 'the_content', 'bds_feat_rewrite_see_plans', 1101 );

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
				return apply_filters( 'bds_feat_product_permalink', get_permalink( $pid ), $level );
			}
		}
	}
	$post = get_page_by_path( 'featured-' . sanitize_title( $level ), OBJECT, 'product' );
	if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
		return apply_filters( 'bds_feat_product_permalink', get_permalink( $post ), $level );
	}
	return apply_filters( 'bds_feat_product_permalink', '', $level );
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
	$html  .= '<h1' . ( function_exists( 'bds_lang_swap_attrs' ) ? bds_lang_swap_attrs( 'Get featured on BrandDad Directory', 'Salga destacado en el directorio BrandDad' ) : '' ) . '>' . esc_html__( 'Get featured on BrandDad Directory', 'branddad' ) . '</h1>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Claim or create your listing, then request a paid lane. Featured ads rotate with paid first. This page does not invent traffic or rankings.', 'Reclame o cree su listado y pida un espacio de pago. Los destacados rotan, con pago primero. Esta página no inventa tráfico ni rankings.' );
	} else {
		$html .= '<p>' . esc_html__( 'Claim or create your listing, then request a paid lane. Featured ads rotate with paid first. This page does not invent traffic or rankings.', 'branddad' ) . '</p>';
	}
	$sw = function_exists( 'bds_lang_swap_attrs' );
	if ( '' !== $note ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( $note, 'Recibimos su solicitud de destacado. BrandDad confirmará el listado y abrirá checkout solo si ese producto ya está a la venta.' );
		} else {
			$html .= '<p role="status">' . esc_html( $note ) . '</p>';
		}
	}
	$listing_prompt = bds_feat_listing_checkout_prompt( bds_feat_requested_listing_id() );
	if ( function_exists( 'bds_lang_pair' ) ) {
		$prompt_es = ! empty( $listing_prompt['attached'] )
			? 'Suscribirse cada mes se vincula a ese listado. El carril permanece inactivo hasta que WooCommerce confirme el pago.'
			: 'Abra Destacados desde un listado publicado para que Suscribirse cada mes se vincule. Un plan sin listado permanece inactivo.';
		$html     .= bds_lang_pair( $listing_prompt['line'], $prompt_es );
	} else {
		$html .= '<p id="bds-feat-listing-note">' . esc_html( $listing_prompt['line'] ) . '</p>';
	}
	$html .= '<p><a class="bds-feat-buy" href="' . $list . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . $claim . '"' . ( $sw ? bds_lang_swap_attrs( 'Claim a listing', 'Reclamar un listado' ) : '' ) . '>' . esc_html__( 'Claim a listing', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . $plans . '"' . ( $sw ? bds_lang_swap_attrs( 'See membership plans', 'Ver planes de membresía' ) : '' ) . '>' . esc_html__( 'See membership plans', 'branddad' ) . '</a></p>';
	$html .= '<div class="bds-feat-grid">';
	foreach ( $levels as $slug => $row ) {
		$url   = bds_feat_product_url( $slug );
		$html .= '<article class="bds-feat-lane"><h2>' . esc_html( $row['label'] ) . '</h2>';
		$html .= '<p class="bds-feat-price">$' . esc_html( $row['price'] ) . ' / <span' . ( $sw ? bds_lang_swap_attrs( 'month', 'mes' ) : '' ) . '>' . esc_html__( 'month', 'branddad' ) . '</span></p>';
		if ( '' !== $url ) {
			$html .= '<a class="bds-feat-buy" href="' . esc_url( $url ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Subscribe monthly', 'Suscribirse cada mes' ) : '' ) . '>' . esc_html__( 'Subscribe monthly', 'branddad' ) . '</a>';
		} elseif ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'Checkout is not open for this lane yet. Send a request below.', 'El checkout de este carril aún no está abierto. Envíe una solicitud abajo.' );
		} else {
			$html .= '<p>' . esc_html__( 'Checkout is not open for this lane yet. Send a request below.', 'branddad' ) . '</p>';
		}
		$html .= '</article>';
	}
	$html .= '</div>';
	$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'Request a featured placement', 'Pedir un espacio destacado' ) : '' ) . '>' . esc_html__( 'Request a featured placement', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Use this if checkout is closed. We match the request to a claimed listing before any charge.', 'Úselo si el checkout está cerrado. Emparejamos la solicitud con un listado reclamado antes de cualquier cargo.' );
	} else {
		$html .= '<p>' . esc_html__( 'Use this if checkout is closed. We match the request to a claimed listing before any charge.', 'branddad' ) . '</p>';
	}
	$html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$html .= wp_nonce_field( 'bds_feat_request', 'bds_feat_request_nonce', true, false );
	$html .= '<input type="hidden" name="action" value="bds_feat_request" />';
	$html .= '<p class="bds-feat-hp" aria-hidden="true"><label>Company website <input type="text" name="bds_company_url" tabindex="-1" autocomplete="off" /></label></p>';
	$html .= '<p><label><span' . ( $sw ? bds_lang_swap_attrs( 'Placement', 'Colocación' ) : '' ) . '>' . esc_html__( 'Placement', 'branddad' ) . '</span><select name="level" required>';
	$want = bds_feat_valid_lane( isset( $_GET['lane'] ) ? wp_unslash( $_GET['lane'] ) : '' );
	foreach ( $levels as $slug => $row ) {
		$sel   = ( $slug === $want ) ? ' selected="selected"' : '';
		$html .= '<option value="' . esc_attr( $slug ) . '"' . $sel . '>' . esc_html( $row['label'] . ' — $' . $row['price'] . '/month' ) . '</option>';
	}
	$html .= '</select></label></p>';
	$listing_id  = bds_feat_requested_listing_id();
	$listing_val = $listing_id > 0 ? (string) $listing_id : '';
	$html .= '<p><label><span' . ( $sw ? bds_lang_swap_attrs( 'Listing ID or Directory URL', 'ID de listado o URL del directorio' ) : '' ) . '>' . esc_html__( 'Listing ID or Directory URL', 'branddad' ) . '</span><input name="listing_ref" maxlength="160" required value="' . esc_attr( $listing_val ) . '" /></label></p>';
	$html .= '<p><label><span' . ( $sw ? bds_lang_swap_attrs( 'Your name', 'Su nombre' ) : '' ) . '>' . esc_html__( 'Your name', 'branddad' ) . '</span><input name="customer_name" maxlength="80" required /></label></p>';
	$html .= '<p><label><span' . ( $sw ? bds_lang_swap_attrs( 'Email', 'Correo' ) : '' ) . '>' . esc_html__( 'Email', 'branddad' ) . '</span><input name="email" type="email" maxlength="120" required /></label></p>';
	$html .= '<p><label><span' . ( $sw ? bds_lang_swap_attrs( 'What should customers see?', '¿Qué deben ver los clientes?' ) : '' ) . '>' . esc_html__( 'What should customers see?', 'branddad' ) . '</span><textarea name="note" maxlength="400"></textarea></label></p>';
	$html .= '<p><button class="bds-feat-buy" type="submit"' . ( $sw ? bds_lang_swap_attrs( 'Send the featured request', 'Enviar la solicitud de destacado' ) : '' ) . '>' . esc_html__( 'Send the featured request', 'branddad' ) . '</button></p></form></div>';
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
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$band  = '<div id="bds-feat-claim-ctas"><h2' . ( $sw ? bds_lang_swap_attrs( 'Claim, list, or get featured', 'Reclame, liste o salga destacado' ) : '' ) . '>' . esc_html__( 'Claim, list, or get featured', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$band .= bds_lang_pair( 'Open your listing, use Claim this listing, or start a new listing. Featured placement is a separate paid lane after the listing is claimed.', 'Abra su listado, use Reclamar este listado o cree uno nuevo. Featured es un carril de pago aparte después de reclamar.' );
	} else {
		$band .= '<p>' . esc_html__( 'Open your listing, use Claim this listing, or start a new listing. Featured placement is a separate paid lane after the listing is claimed.', 'branddad' ) . '</p>';
	}
	$band .= '<p><a class="bds-feat-buy" href="' . esc_url( home_url( '/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Find my listing', 'Encontrar mi listado' ) : '' ) . '>' . esc_html__( 'Find my listing', 'branddad' ) . '</a>';
	$band .= '<a class="bds-feat-buy" href="' . esc_url( home_url( '/add-listing/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a>';
	$band .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( home_url( '/featured-advertising/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Get featured', 'Salir destacado' ) : '' ) . '>' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$band .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( home_url( '/select-your-plan/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'See membership plans', 'Ver planes de membresía' ) : '' ) . '>' . esc_html__( 'See membership plans', 'branddad' ) . '</a></p></div>';
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
	$sw      = function_exists( 'bds_lang_swap_attrs' );
	$html    = '<div class="bds-feat-claim" id="bds-feat-listing-ctas">';
	$html   .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'Claim or get featured', 'Reclame o salga destacado' ) : '' ) . '>' . esc_html__( 'Claim or get featured', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'If this is your business, claim the listing. Featured placement is a paid lane after that. This does not transfer ownership by itself.', 'Si este es su negocio, reclame el listado. Featured es un carril de pago después. Esto no transfiere la propiedad por sí solo.' );
	} else {
		$html .= '<p>' . esc_html__( 'If this is your business, claim the listing. Featured placement is a paid lane after that. This does not transfer ownership by itself.', 'branddad' ) . '</p>';
	}
	$html   .= '<p><a class="bds-feat-buy" href="' . esc_url( $feat ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Get featured', 'Salir destacado' ) : '' ) . '>' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$html   .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( $claim ) . '"' . ( $sw ? bds_lang_swap_attrs( 'How to claim', 'Cómo reclamar' ) : '' ) . '>' . esc_html__( 'How to claim', 'branddad' ) . '</a></p></div>';
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
		__( 'A basic listing is free. Featured placement is a separate monthly option and does not replace Pro or Elite.', 'branddad' ),
		'También liste o salga destacado',
		'Un listado básico es gratis. Featured es una opción mensual aparte y no reemplaza Pro o Elite.'
	);
}

function bds_feat_request_path() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return trim( $path, '/' );
}

function bds_feat_door_band( $heading, $intro, $heading_es = '', $intro_es = '' ) {
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$html  = '<div id="bds-feat-door-ctas"><h2' . ( $sw && '' !== $heading_es ? bds_lang_swap_attrs( $heading, $heading_es ) : '' ) . '>' . esc_html( $heading ) . '</h2>';
	if ( '' !== $intro_es && function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( $intro, $intro_es );
	} else {
		$html .= '<p>' . esc_html( $intro ) . '</p>';
	}
	$html .= '<p><a class="bds-feat-buy" href="' . esc_url( home_url( '/add-listing/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy" href="' . esc_url( home_url( '/featured-advertising/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Get featured', 'Salir destacado' ) : '' ) . '>' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$html .= '<a class="bds-feat-buy bds-feat-buy--quiet" href="' . esc_url( home_url( '/claim-your-business-listing-branddad-directory/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'How to claim', 'Cómo reclamar' ) : '' ) . '>' . esc_html__( 'How to claim', 'branddad' ) . '</a></p></div>';
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
			__( 'This page is missing. List, claim, or request Featured instead. Membership prices on Select your plan are unchanged.', 'branddad' ),
			'Encuentre un negocio o salga destacado',
			'Esta página no existe. Liste, reclame o pida Featured. Los precios de membresía en Select your plan no cambian.'
		);
		return;
	}
	if ( is_front_page() ) {
		echo bds_feat_door_band(
			__( 'Own a business on this Directory?', 'branddad' ),
			__( 'List my business free, or claim the one that is already here. Featured is a separate monthly option.', 'branddad' ),
			'¿Tiene un negocio en este directorio?',
			'Liste mi negocio gratis, o reclame el que ya está aquí. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'select-your-plan' === $path ) {
		echo bds_feat_plans_page_ctas(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	if ( 'sign-in' === $path || 'login' === $path ) {
		echo bds_feat_door_band(
			__( 'Sign in to list free', 'branddad' ),
			__( 'A basic listing is free after you sign in. Featured is a separate monthly option. This screen does not start a charge.', 'branddad' ),
			'Entre para listar gratis',
			'Un listado básico es gratis después de entrar. Featured es una opción mensual aparte. Esta pantalla no inicia un cargo.'
		);
		return;
	}
	$surface = function_exists( 'bds_feat_directory_surface' ) ? bds_feat_directory_surface( $path ) : '';
	if ( '' === $surface && function_exists( 'bds_feat_listing_type_surface' ) ) {
		$type     = isset( $_GET['directory_type'] ) ? wp_unslash( $_GET['directory_type'] ) : '';
		$surface  = bds_feat_listing_type_surface( $type );
	}
	if ( 'events' === $surface ) {
		echo bds_feat_door_band(
			__( 'Find events, or list yours free', 'branddad' ),
			__( 'BrandDad does not sell tickets here. A basic listing is free. Featured is a separate monthly option.', 'branddad' ),
			'Encuentre eventos, o liste el suyo gratis',
			'BrandDad no vende boletos aquí. Un listado básico es gratis. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'jobs' === $surface ) {
		echo bds_feat_door_band(
			__( 'Find work, or list a hiring business free', 'branddad' ),
			__( 'BrandDad does not take a hiring fee here. A basic listing is free. Featured is a separate monthly option.', 'branddad' ),
			'Encuentre trabajo, o liste un negocio que contrata gratis',
			'BrandDad no cobra una tarifa de contratación aquí. Un listado básico es gratis. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'classifieds' === $surface ) {
		echo bds_feat_door_band(
			__( 'Browse classifieds, or list free', 'branddad' ),
			__( 'A basic classified listing is free. BrandDad does not take a sale fee here. Featured is a separate monthly option.', 'branddad' ),
			'Vea clasificados, o liste gratis',
			'Un clasificado básico es gratis. BrandDad no cobra una comisión de venta aquí. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'memberships' === $surface ) {
		echo bds_feat_door_band(
			__( 'See membership plans, or list free', 'branddad' ),
			__( 'A basic listing is free. Pro, Elite, and Featured stay paid options. Membership prices on Select your plan are unchanged.', 'branddad' ),
			'Vea planes de membresía, o liste gratis',
			'Un listado básico es gratis. Pro, Elite y Featured siguen siendo opciones de pago. Los precios de membresía en Select your plan no cambian.'
		);
		return;
	}
	if ( 'travel' === $surface ) {
		echo bds_feat_door_band(
			__( 'Find travel stays, or list yours free', 'branddad' ),
			__( 'BrandDad does not sell tickets or book flights here. A basic listing is free. Featured is a separate monthly option.', 'branddad' ),
			'Encuentre hospedaje, o liste el suyo gratis',
			'BrandDad no vende boletos ni reserva vuelos aquí. Un listado básico es gratis. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'hotels' === $surface ) {
		echo bds_feat_door_band(
			__( 'Find a stay, or list yours free', 'branddad' ),
			__( 'BrandDad does not take a booking fee here. A basic listing is free. Featured is a separate monthly option.', 'branddad' ),
			'Encuentre hospedaje, o liste el suyo gratis',
			'BrandDad no cobra una tarifa de reserva aquí. Un listado básico es gratis. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'gifts' === $surface ) {
		echo bds_feat_door_band(
			__( 'Browse gift cards, or list free', 'branddad' ),
			__( 'Gift cards on this Directory are a marketplace. BrandDad does not take that payment here. A basic listing is free. Featured is a separate monthly option.', 'branddad' ),
			'Vea tarjetas de regalo, o liste gratis',
			'Las tarjetas de regalo de este directorio son un marketplace. BrandDad no cobra ese pago aquí. Un listado básico es gratis. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( 'blog' === $surface ) {
		echo bds_feat_door_band(
			__( 'Read BrandDad, or list your business free', 'branddad' ),
			__( 'A basic listing is free. Featured is a separate monthly option. This page does not sell a listing.', 'branddad' ),
			'Lea BrandDad, o liste su negocio gratis',
			'Un listado básico es gratis. Featured es una opción mensual aparte. Esta página no vende un listado.'
		);
		return;
	}
	if ( 'add-listing' === $path ) {
		echo bds_feat_door_band(
			__( 'Get found, then get featured', 'branddad' ),
			__( 'A basic listing is free. We review it before it goes live. Featured placement is a separate monthly request.', 'branddad' ),
			'Que lo encuentren, luego salga destacado',
			'Un listado básico es gratis. Lo revisamos antes de publicarlo. Featured es una solicitud mensual aparte.'
		);
		return;
	}
	if ( is_search() && ! is_singular() ) {
		echo bds_feat_door_band(
			__( 'No listing matched? List yours free', 'branddad' ),
			__( 'This search does not sell a listing. A basic listing is free. Featured is a separate monthly option.', 'branddad' ),
			'¿No hubo un listado? Liste el suyo gratis',
			'Esta búsqueda no vende un listado. Un listado básico es gratis. Featured es una opción mensual aparte.'
		);
		return;
	}
	if ( is_post_type_archive( 'at_biz_dir' ) ) {
		echo bds_feat_door_band(
			__( 'Own a business on this list?', 'branddad' ),
			__( 'List my business free, or claim the one that is already here. Featured is a separate request. This does not invent rankings.', 'branddad' ),
			'¿Tiene un negocio en esta lista?',
			'Liste mi negocio gratis, o reclame el que ya está aquí. Featured es una solicitud aparte. Esto no inventa rankings.'
		);
		return;
	}
	if ( ( function_exists( 'is_singular' ) && is_singular( 'post' ) ) || bds_feat_path_needs_door( $path ) ) {
		echo bds_feat_door_band(
			__( 'Own a business?', 'branddad' ),
			__( 'A basic listing is free. We review it before it goes live. Featured placement is a separate request.', 'branddad' ),
			'¿Tiene un negocio?',
			'Un listado básico es gratis. Lo revisamos antes de publicarlo. Featured es una solicitud aparte.'
		);
	}
}
add_action( 'wp_footer', 'bds_feat_path_ctas', 24 );

/** Send /classifieds/ to the live classifieds directory type. */
function bds_feat_classifieds_alias() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	if ( 'classifieds' !== bds_feat_request_path() ) {
		return;
	}
	wp_safe_redirect( home_url( '/all-listings/?directory_type=classifieds' ), 302 );
	exit;
}
add_action( 'template_redirect', 'bds_feat_classifieds_alias', 8 );

/** Send leftover membership URLs to the live plans page. */
function bds_feat_memberships_alias() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	if ( ! in_array( bds_feat_request_path(), array( 'memberships', 'membership', 'plans' ), true ) ) {
		return;
	}
	wp_safe_redirect( home_url( '/select-your-plan/' ), 302 );
	exit;
}
add_action( 'template_redirect', 'bds_feat_memberships_alias', 8 );

/** Send leftover FAQ, sitemap, how-it-works, and careers URLs to Directory help. Featured leftover opens the buy page. */
function bds_feat_help_alias() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	$path = bds_feat_request_path();
	if ( in_array( $path, array( 'faq', 'faqs', 'frequently-asked-questions', 'sitemap', 'site-map', 'how-it-works', 'careers', 'career', 'work-with-us', 'support', 'help-center' ), true ) ) {
		wp_safe_redirect( home_url( '/help/' ), 302 );
		exit;
	}
	if ( 'featured' === $path ) {
		wp_safe_redirect( home_url( '/featured-advertising/' ), 302 );
		exit;
	}
	if ( 'team' === $path ) {
		wp_safe_redirect( home_url( '/about-us/' ), 302 );
		exit;
	}
}
add_action( 'template_redirect', 'bds_feat_help_alias', 8 );

/**
 * More leftover Directory doors that abort until they open a live page.
 *
 * @param string $path Request path without slashes.
 * @return string
 */
function bds_feat_leftover_dest( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	if ( 'gifts' === $path || 'marketplace' === $path ) {
		return home_url( '/gift-card-marketplace/' );
	}
	if ( 'partners' === $path ) {
		return home_url( '/contact-us/' );
	}
	if ( 'cookies' === $path || 'cookie-policy' === $path ) {
		return home_url( '/privacy-policy/' );
	}
	if ( in_array( $path, array( 'list-your-business', 'add-business', 'list-business', 'submit-listing', 'post-a-job', 'post-job', 'get-started' ), true ) ) {
		return home_url( '/add-listing/' );
	}
	if ( 'advertise-with-us' === $path ) {
		return home_url( '/featured-advertising/' );
	}
	if ( 'subscribe' === $path ) {
		return home_url( '/select-your-plan/' );
	}
	if ( 'trial' === $path ) {
		return 'https://branddad.social/product/ai-business-essentials/';
	}
	return '';
}

function bds_feat_leftover_alias() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	$dest = bds_feat_leftover_dest( bds_feat_request_path() );
	if ( '' === $dest ) {
		return;
	}
	wp_safe_redirect( $dest, 302 );
	exit;
}
add_action( 'template_redirect', 'bds_feat_leftover_alias', 8 );

/**
 * Directory leftover trial URLs open the live Social Essentials product.
 *
 * @param mixed $hosts Allowed hosts.
 * @return array<int,string>
 */
function bds_feat_allowed_redirect_hosts( $hosts ) {
	$hosts = is_array( $hosts ) ? $hosts : array();
	if ( bds_feat_is_directory() ) {
		$hosts[] = 'branddad.social';
		$hosts[] = 'www.branddad.social';
	}
	return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'bds_feat_allowed_redirect_hosts' );

/** Hide the Directorist demo refund modal on public Directory pages. Account and checkout stay untouched. */
function bds_feat_hide_demo_refund() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	$path = bds_feat_request_path();
	if ( 0 === strpos( $path, 'dashboard' ) || 0 === strpos( $path, 'checkout' ) || 0 === strpos( $path, 'my-account' ) ) {
		return;
	}
	echo '<style id="bds-feat-hide-refund">.user-refund-modal,#dcl-claim-modal,textarea[name="payment_details"]{display:none!important}</style>';
}
add_action( 'wp_head', 'bds_feat_hide_demo_refund', 40 );

/**
 * Codex homepage leftover: #bdd-title was parked at 1px / -9999px, so the
 * visible journey started at an H2, and cached gift-homepage markup stayed in
 * the public DOM. Keep the current Directory Experience heading visible.
 * Gift inventory stays on /gift-card-marketplace/.
 *
 * @return void
 */
function bds_feat_home_legacy_css() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	if ( '' !== bds_feat_request_path() ) {
		return;
	}
	echo '<style id="bds-feat-home-legacy">body.home #bdd-title{position:static!important;left:auto!important;top:auto!important;width:auto!important;height:auto!important;overflow:visible!important;clip:auto!important;clip-path:none!important}body.home #bds-dfc-gift,body.home #bds-dfc-gift-place-js{display:none!important}body.home #bds-wa-hero:not(:has(#bdd-title)),body.home .bds-wah:not(:has(#bdd-title)){display:none!important}</style>';
}
add_action( 'wp_head', 'bds_feat_home_legacy_css', 41 );

/**
 * Directory compose header uses .bdd-header, but live polish CSS still
 * targets .bdd-support-header, so nav/actions/hamburger stacked. Lay the
 * real header out so desktop and mobile controls do not sit on each other.
 *
 * @return void
 */
function bds_feat_menu_overlap_css() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	echo '<style id="bds-feat-menu-clear">'
		. 'body:has(.bdd-header) .theme-mobile-menu-trigger,body:has(.bdd-header) .theme-mobile-menu-overlay,body:has(.bdd-header) .theme-mobile-menu-close{display:none!important}'
		. '.bdd-header{position:relative;z-index:5000;border-bottom:1px solid #dbe5ef;background:#fff;color:#0b172a}'
		. '.bdd-header .bdd-shell,.bdd-header .bdd-header__inner{width:min(1180px,calc(100% - 32px));min-height:72px;display:flex;align-items:center;gap:16px;margin:0 auto;position:relative}'
		. '.bdd-brand{display:inline-flex;align-items:center;gap:8px;flex:0 0 auto;color:#0b172a;text-decoration:none;font-weight:800;white-space:nowrap}'
		. '.bdd-brand__mark{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#0668d8;color:#fff;font-size:13px}'
		. '.bdd-nav{display:flex;align-items:center;justify-content:center;flex:1 1 auto;flex-wrap:wrap;gap:4px;min-width:0}'
		. '.bdd-nav>a,.bdd-nav summary{display:inline-flex;align-items:center;min-height:44px;padding:0 10px;border-radius:8px;color:#27374a;font-weight:700;text-decoration:none;cursor:pointer;list-style:none;white-space:nowrap}'
		. '.bdd-nav summary::-webkit-details-marker{display:none}'
		. '.bdd-nav details{position:relative}'
		. '.bdd-nav-menu__panel{position:absolute;top:calc(100% + 8px);left:0;z-index:6;width:230px;display:grid;gap:2px;padding:9px;border:1px solid #dbe5ef;border-radius:12px;background:#fff;box-shadow:0 16px 42px rgba(15,23,42,.14)}'
		. '.bdd-nav-menu__panel--right{right:0;left:auto}'
		. '.bdd-nav details:not([open])>.bdd-nav-menu__panel{display:none}'
		. '.bdd-nav-menu__panel a,.bdd-nav-menu__panel span{min-height:42px;display:flex;align-items:center;padding:8px 10px;border-radius:8px;color:#27374a;text-decoration:none}'
		. '.bdd-header__actions{display:flex;align-items:center;gap:8px;flex:0 0 auto;margin-left:auto;position:relative;z-index:2}'
		. '.bdd-icon-link{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:#f4f7fa;color:#0b172a;text-decoration:none}'
		. '.bdd-add-menu{position:relative}'
		. '.bdd-add-menu>summary{display:inline-flex;align-items:center;min-height:44px;padding:8px 14px;border-radius:10px;background:#0668d8;color:#fff;font-weight:800;cursor:pointer;list-style:none}'
		. '.bdd-add-menu>summary::-webkit-details-marker{display:none}'
		. '.bdd-add-menu nav{position:absolute;top:calc(100% + 8px);right:0;z-index:6;width:min(260px,calc(100vw - 24px));display:grid;gap:2px;padding:9px;border:1px solid #dbe5ef;border-radius:12px;background:#fff}'
		. '.bdd-add-menu:not([open])>nav{display:none}'
		. '.bdd-add-menu nav a{min-height:44px;display:flex;align-items:center;padding:8px 10px;color:#27374a;text-decoration:none}'
		. '.bdd-mobile-menu{display:none;position:relative}'
		. '.bdd-mobile-menu>summary{width:44px;height:44px;display:grid;place-items:center;border-radius:10px;background:#0b172a;color:#fff;cursor:pointer;list-style:none}'
		. '.bdd-mobile-menu>summary::-webkit-details-marker{display:none}'
		. '.bdd-mobile-menu>nav{position:absolute;top:calc(100% + 9px);right:0;z-index:7;width:min(320px,calc(100vw - 24px));max-height:calc(100vh - 100px);overflow:auto;display:grid;gap:3px;padding:12px;border:1px solid #dbe5ef;border-radius:14px;background:#fff}'
		. '.bdd-mobile-menu:not([open])>nav{display:none}'
		. '.bdd-mobile-menu a{min-height:44px;display:flex;align-items:center;padding:9px 11px;color:#27374a;text-decoration:none}'
		. '.bdd-header a:focus-visible,.bdd-header summary:focus-visible,.bdd-header button:focus-visible{outline:3px solid #0668d8;outline-offset:2px}'
		. '@media(max-width:900px){.bdd-nav{display:none}.bdd-add-menu{display:none}.bdd-mobile-menu{display:block}.bdd-header .bdd-shell,.bdd-header .bdd-header__inner{min-height:64px;gap:10px}}'
		. '@media(max-width:420px){.bdd-header .bdd-shell,.bdd-header .bdd-header__inner{width:min(100% - 20px,1180px);gap:8px}.bdd-icon-link{width:40px;height:40px}}'
		. '</style>';
}
add_action( 'wp_head', 'bds_feat_menu_overlap_css', 42 );

/**
 * After the homepage paints: hide the retired WhatsApp hero when the current
 * Directory Experience heading is present, and drop leftover gift markup.
 *
 * @return void
 */
function bds_feat_home_legacy_js() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	if ( '' !== bds_feat_request_path() ) {
		return;
	}
	echo '<script id="bds-feat-home-legacy-js">(function(){if(!document.body||!document.body.classList.contains("home"))return;var current=document.getElementById("bdd-title");if(current){var wrap=document.getElementById("bds-wa-hero")||document.querySelector(".bds-wah");if(wrap&&!wrap.contains(current)){wrap.hidden=true;wrap.setAttribute("aria-hidden","true");}}var gift=document.getElementById("bds-dfc-gift");if(gift)gift.remove();var giftJs=document.getElementById("bds-dfc-gift-place-js");if(giftJs)giftJs.remove();})();</script>';
}
add_action( 'wp_footer', 'bds_feat_home_legacy_js', 33 );

/**
 * Rewrite Directory homepage HTML so the parked heading and leftover gift
 * block are gone before cache, not only after CSS/JS.
 *
 * @return void
 */
function bds_feat_home_legacy_buffer_start() {
	if ( ! bds_feat_is_directory() || is_admin() ) {
		return;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( '' !== bds_feat_request_path() ) {
		return;
	}
	ob_start( 'bds_feat_rewrite_home_legacy_html' );
}
add_action( 'template_redirect', 'bds_feat_home_legacy_buffer_start', 0 );

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
