<?php
/**
 * Plugin Name: BrandDad Customer Surfaces
 * Description: Account hub, membership status, free-AI allowance display, studio boards, and the Partnero affiliate door. Does not charge, invent balances, or publish.
 * Version: 0.1.0
 * Author: BrandDad
 *
 * Secure design: every account block uses the current WordPress user. Music briefs are limited to that user's posts. Affiliate money stays in Partnero. Studio buttons do not call providers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/bds-customer-hub-lib.php';

function bds_hub_is_allowed_host() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true );
}

function bds_hub_styles() {
	if ( ! bds_hub_is_allowed_host() ) {
		return;
	}
	echo '<style id="bds-hub-css">.bds-hub{max-width:980px;margin:24px auto;padding:8px 16px;color:#0b172a}.bds-hub h2{font-size:clamp(26px,4vw,36px);line-height:1.15}.bds-hub section{margin:28px 0;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff}.bds-hub a,.bds-hub button{display:inline-flex;align-items:center;min-height:44px;margin:8px 8px 0 0;padding:0 14px;border-radius:10px;background:#0878ed;color:#fff;font-weight:800;text-decoration:none}.bds-hub button[disabled]{background:#e8eef5;color:#526070;border:0}.bds-hub li{margin:6px 0}</style>';
}
add_action( 'wp_head', 'bds_hub_styles', 40 );

function bds_hub_membership_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	if ( ! is_user_logged_in() ) {
		$register = function_exists( 'bds_mt_upgrade_url' ) ? bds_mt_upgrade_url() : home_url( '/registration/' );
		return '<section id="bds-membership-status"><h2>Directory membership</h2><p>Sign in to see the plan already on your account.</p><a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">Sign in</a> <a href="' . esc_url( $register ) . '">See plans</a></section>';
	}
	$tier = function_exists( 'bds_mt_user_tier' ) ? (string) bds_mt_user_tier() : 'free';
	$caps = function_exists( 'bds_mt_caps_for' ) ? bds_mt_caps_for( $tier ) : array();
	$view = bds_hub_membership_view( $tier, is_array( $caps ) ? $caps : array() );
	$html = '<section id="bds-membership-status"><h2>Directory membership</h2><p>Current record: ' . esc_html( $view['label'] ) . '.</p>';
	if ( $view['included'] ) {
		$html .= '<ul>';
		foreach ( $view['included'] as $line ) {
			$html .= '<li>' . esc_html( $line ) . '</li>';
		}
		$html .= '</ul>';
	} else {
		$html .= '<p>This tier record does not list extra publishing or discount tools.</p>';
	}
	if ( $view['upgrade'] && function_exists( 'bds_mt_upgrade_url' ) ) {
		$html .= '<a href="' . esc_url( bds_mt_upgrade_url() ) . '">Review upgrade options</a>';
	}
	$html .= '<p>Cancellation and card changes stay in the existing billing account. This screen does not change them.</p></section>';
	return $html;
}

function bds_hub_compare_html() {
	if ( ! bds_hub_is_allowed_host() || ! function_exists( 'bds_mt_entitlements' ) ) {
		return '';
	}
	$rows = bds_hub_membership_compare( bds_mt_entitlements() );
	if ( ! $rows ) {
		return '';
	}
	$html = '<section id="bds-membership-compare"><h2>Plan comparison</h2><p>These rows come from the existing tier record. This screen does not change a price.</p><ul>';
	foreach ( $rows as $row ) {
		$detail = $row['included'] ? implode( ', ', $row['included'] ) : 'no extra tools in this tier record';
		$html  .= '<li><strong>' . esc_html( $row['label'] ) . '</strong>: ' . esc_html( $detail ) . '</li>';
	}
	return $html . '</ul></section>';
}

function bds_hub_saved_ids() {
	$ids = get_user_meta( get_current_user_id(), '_bds_saved_listing_ids', true );
	return bds_hub_saved_list( is_array( $ids ) ? $ids : array() );
}

function bds_hub_saved_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	if ( ! is_user_logged_in() ) {
		return '<section id="bds-saved"><h2>Saved businesses</h2><p>Sign in to save a business to this account. Guests do not get a saved list.</p></section>';
	}
	$html = '<section id="bds-saved"><h2>Saved businesses</h2>';
	$ids  = bds_hub_saved_ids();
	if ( ! $ids ) {
		return $html . '<p>No businesses are saved on this account.</p></section>';
	}
	$html .= '<ul>';
	foreach ( $ids as $listing_id ) {
		$post = get_post( $listing_id );
		if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
			continue;
		}
		$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	return $html . '</ul></section>';
}

function bds_hub_free_ai_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	if ( ! is_user_logged_in() ) {
		return '<section id="bds-free-ai"><h2>Free Directory AI</h2><p>Sign in with the account that claimed the business. No allowance is shown for a guest.</p></section>';
	}
	$status = null;
	if ( function_exists( 'bds_dir_free_ai_status' ) ) {
		$listings = get_posts(
			array(
				'post_type'      => 'at_biz_dir',
				'author'         => get_current_user_id(),
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'fields'         => 'ids',
			)
		);
		foreach ( $listings as $listing_id ) {
			$candidate = bds_dir_free_ai_status( (int) $listing_id, get_current_user_id() );
			if ( is_array( $candidate ) ) {
				$status = $candidate;
				break;
			}
		}
	}
	$view = bds_hub_free_ai_view( $status );
	$html = '<section id="bds-free-ai"><h2>Free Directory AI</h2>';
	if ( 'unavailable' === $view['state'] ) {
		$html .= '<p>' . esc_html( $view['lines'][0] ) . ' Tool runs stay off until the shared meter is connected.</p></section>';
		return $html;
	}
	$html .= '<p>Period ' . esc_html( $view['period'] ) . '. These counts come from the allowance ledger. This screen does not run the tools.</p><ul>';
	foreach ( $view['lines'] as $line ) {
		$html .= '<li>' . esc_html( $line ) . '</li>';
	}
	$html .= '</ul><p>Upgrade prompts use the existing plan page. Nothing here starts a subscription.</p></section>';
	return $html;
}

function bds_hub_studio_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$html = '<section id="bds-studios"><h2>SEO, Social, and LinkedIn</h2>';
	foreach ( bds_hub_studio_cards() as $card ) {
		$html .= '<article id="bds-studio-' . esc_attr( $card['id'] ) . '"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['detail'] ) . '</p><button type="button" disabled>Not connected</button></article>';
	}
	$html .= '</section>';
	return $html;
}

function bds_hub_affiliate_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$portal = defined( 'BDS_AFF_PORTAL' ) ? BDS_AFF_PORTAL : 'https://affiliates.branddad.social/';
	// The live Partnero content filter rewrites the host and then leaves any path
	// glued to ?ref=. The portal root is the URL that rewrite keeps intact.
	// Do not call bds_po_url here; that filter adds the ref once.
	return '<section id="bds-affiliate"><h2>Affiliate program</h2><p>' . esc_html( bds_hub_affiliate_copy() ) . '</p><a href="' . esc_url( $portal ) . '">Open the portal</a></section>';
}

function bds_hub_music_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$rows = array();
	if ( is_user_logged_in() && post_type_exists( 'bds_music_brief' ) ) {
		$posts = get_posts(
			array(
				'post_type'      => 'bds_music_brief',
				'author'         => get_current_user_id(),
				'post_status'    => array( 'private', 'draft' ),
				'posts_per_page' => 20,
			)
		);
		foreach ( $posts as $post ) {
			if ( (int) $post->post_author !== get_current_user_id() ) {
				continue;
			}
			$rows[] = array( 'title' => get_the_title( $post ) );
		}
	}
	$view = bds_hub_music_rows( $rows );
	$html = '<section id="bds-music-orders"><h2>Music briefs</h2>';
	if ( ! $view ) {
		$html .= '<p>No brief is saved on this account. A brief does not take payment or start production.</p></section>';
		return $html;
	}
	$html .= '<ul>';
	foreach ( $view as $row ) {
		$html .= '<li>' . esc_html( $row['title'] . ' — ' . $row['status'] ) . '</li>';
	}
	$html .= '</ul></section>';
	return $html;
}

function bds_hub_account_shortcode() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	return '<div class="bds-hub" id="bds-account-hub">' . bds_hub_membership_html() . bds_hub_compare_html() . bds_hub_saved_html() . bds_hub_free_ai_html() . bds_hub_studio_html() . bds_hub_affiliate_html() . bds_hub_music_html() . '</div>';
}
add_shortcode( 'bds_account_hub', 'bds_hub_account_shortcode' );
add_shortcode( 'bds_membership_status', 'bds_hub_membership_html' );
add_shortcode( 'bds_affiliate_landing', 'bds_hub_affiliate_html' );
add_shortcode( 'bds_studio_boards', 'bds_hub_studio_html' );

function bds_hub_register_pages() {
	if ( ! bds_hub_is_allowed_host() || ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$pages = array(
		'account-hub'       => array( 'Account', '[bds_account_hub]' ),
		'affiliate-program' => array( 'Affiliate program', '[bds_affiliate_landing]' ),
		'studio-boards'     => array( 'Studios', '[bds_studio_boards]' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_name'    => $slug,
				'post_title'   => $page[0],
				'post_content' => $page[1],
			)
		);
	}
}
add_action( 'admin_init', 'bds_hub_register_pages' );

function bds_hub_save_button() {
	if ( ! bds_hub_is_allowed_host() || ! is_singular( 'at_biz_dir' ) ) {
		return;
	}
	$listing_id = get_queried_object_id();
	if ( $listing_id < 1 ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		echo '<p id="bds-save-listing">Sign in to save this business on your account.</p>';
		return;
	}
	echo '<form id="bds-save-listing" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'bds_hub_save_listing' );
	echo '<input type="hidden" name="action" value="bds_hub_save_listing" />';
	echo '<input type="hidden" name="listing_id" value="' . (int) $listing_id . '" />';
	$saved = in_array( $listing_id, bds_hub_saved_ids(), true );
	echo '<input type="hidden" name="save_action" value="' . esc_attr( $saved ? 'remove' : 'save' ) . '" />';
	echo '<button type="submit">' . esc_html( $saved ? 'Remove from saved' : 'Save this business' ) . '</button></form>';
}
add_action( 'wp_footer', 'bds_hub_save_button', 30 );

function bds_hub_handle_save() {
	if ( ! is_user_logged_in() || ! bds_hub_is_allowed_host() ) {
		wp_die( esc_html__( 'Sign in to save a business.', 'branddad' ), 403 );
	}
	check_admin_referer( 'bds_hub_save_listing' );
	$listing_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
	$action     = isset( $_POST['save_action'] ) ? sanitize_key( wp_unslash( $_POST['save_action'] ) ) : '';
	$post       = get_post( $listing_id );
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
		exit;
	}
	$result = bds_hub_saved_change( bds_hub_saved_ids(), $listing_id, $action );
	if ( ! empty( $result['ok'] ) ) {
		update_user_meta( get_current_user_id(), '_bds_saved_listing_ids', $result['ids'] );
	}
	wp_safe_redirect( get_permalink( $post ) );
	exit;
}
add_action( 'admin_post_bds_hub_save_listing', 'bds_hub_handle_save' );
