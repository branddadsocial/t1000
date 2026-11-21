<?php
/**
 * Plugin Name: BrandDad Directory Free Listing
 * Description: A signed-in free account may publish one basic listing. Paid plans, prices, and caps stay as they are.
 * Version: 0.1.5
 * Author: BrandDad
 *
 * Deploy: directory.branddad.social wp-content/mu-plugins/bds-directory-free-listing-mu.php
 * Requires membership tiers 1.4.0, which calls apply_filters( 'bds_mt_user_can_list' ).
 *
 * Secure design:
 * - Identity is the existing WordPress session. This file adds no login, token, or agent credential.
 * - Guests stay blocked. Tier "free" with zero published listings may list. Activation, Pro, Elite, and staff keep the previous allow.
 * - This does not change plan caps, stored prices, the 10% member price, Featured, or Travel search.
 * - Free owners have one published listing, including when a moderator approves it.
 * - A per-owner database lock serializes the quota check and publication write.
 * - A listing id in the query is not trusted here. The desk guard allows an edit only when post_author is the session user.
 * - No charge is created. The older $6.99 Activation product stays optional.
 * - The next-steps footer reads the request path only to choose add-listing or registration. Any other path prints nothing. The sentences are fixed and the path is not written back to the page.
 *
 * @package BrandDad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a free account may pass the listing gate.
 *
 * @param bool   $already      True when a paid tier or staff already may list.
 * @param string $tier         Membership tier. Guests are "guest".
 * @param int    $user_id      Session user. Zero is a guest.
 * @param int    $published    Published listings this user already authors, excluding the one being saved.
 * @return bool
 */
function bds_free_list_allow( $already, $tier, $user_id, $published ) {
	if ( $already ) {
		return true;
	}
	if ( (int) $user_id < 1 || 'free' !== (string) $tier ) {
		return false;
	}
	return (int) $published < 1;
}

/**
 * Published listings this user already authors.
 *
 * @param int $user_id    User.
 * @param int $exclude_id Listing being saved.
 * @return int
 */
function bds_free_list_published_count( $user_id, $exclude_id = 0 ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return 0;
	}
	if ( ! function_exists( 'get_posts' ) ) {
		return 1;
	}
	$args = array(
		'post_type'        => 'at_biz_dir',
		'author'           => $user_id,
		'post_status'      => 'publish',
		'posts_per_page'   => 1,
		'fields'           => 'ids',
		'no_found_rows'    => true,
		'suppress_filters' => true,
		'cache_results'    => false,
	);
	if ( (int) $exclude_id > 0 ) {
		$args['post__not_in'] = array( (int) $exclude_id );
	}
	$ids = get_posts( $args );
	global $wpdb;
	return is_array( $ids ) && empty( $wpdb->last_error ) ? count( $ids ) : 1;
}

/**
 * @param bool $can     Existing paid or staff result.
 * @param int  $user_id User.
 * @return bool
 */
function bds_free_list_filter( $can, $user_id ) {
	$tier = function_exists( 'bds_mt_user_tier' ) ? (string) bds_mt_user_tier( $user_id ) : 'guest';
	return bds_free_list_allow( (bool) $can, $tier, (int) $user_id, bds_free_list_published_count( (int) $user_id ) );
}
add_filter( 'bds_mt_user_can_list', 'bds_free_list_filter', 10, 2 );

/**
 * Apply the free owner's quota before the published status is stored.
 * Locks are held through request shutdown, including nested WordPress callbacks.
 *
 * @param array $data    WordPress post data.
 * @param array $postarr Original post arguments, including an existing ID.
 * @return array
 */
function bds_free_list_before_publish( $data, $postarr ) {
	static $locks = array();
	if ( 'at_biz_dir' !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
		return $data;
	}
	$user_id = (int) ( $data['post_author'] ?? 0 );
	if ( $user_id < 1 || ! function_exists( 'bds_mt_user_tier' ) ) {
		$data['post_status'] = 'pending';
		return $data;
	}
	if ( 'free' !== bds_mt_user_tier( $user_id ) ) {
		return $data;
	}
	global $wpdb;
	if ( ! isset( $locks[ $user_id ] ) ) {
		$lock_name = 'bds_free_listing_' . $user_id;
		$locks[ $user_id ] = 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 5 ) );
		if ( $locks[ $user_id ] ) {
			register_shutdown_function(
				static function () use ( $wpdb, $lock_name ) {
					$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
				}
			);
		}
	}
	if ( ! $locks[ $user_id ] || bds_free_list_published_count( $user_id, (int) ( $postarr['ID'] ?? 0 ) ) >= 1 ) {
		$data['post_status'] = 'pending';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'bds_free_list_before_publish', PHP_INT_MAX, 2 );

/**
 * Hold a second published listing for a free account.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 * @return void
 */
function bds_free_list_hold_second_publish( $new_status, $old_status, $post ) {
	static $holding = false;
	if ( $holding || 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type ) {
		return;
	}
	$user_id = (int) $post->post_author;
	if ( $user_id < 1 || ! function_exists( 'bds_mt_user_tier' ) || 'free' !== bds_mt_user_tier( $user_id ) ) {
		return;
	}
	if ( bds_free_list_published_count( $user_id, (int) $post->ID ) < 1 ) {
		return;
	}
	$holding = true;
	wp_update_post(
		array(
			'ID'          => (int) $post->ID,
			'post_status' => 'pending',
		)
	);
	$holding = false;
}
add_action( 'transition_post_status', 'bds_free_list_hold_second_publish', 10, 3 );

/**
 * Three next steps on the two pages where a new owner starts.
 * The path must be exactly add-listing or registration. The markup is fixed.
 *
 * @param string $path Request path.
 * @return string
 */
function bds_free_list_steps_html( $path ) {
	$path  = strtolower( trim( (string) $path, '/' ) );
	$style = '<style>#bds-free-steps{max-width:720px;margin:18px auto;padding:18px 20px;box-sizing:border-box}#bds-free-steps a{display:inline-flex;align-items:center;min-height:44px;padding:10px 14px;margin:4px 8px 0 0}#bds-free-steps ol{padding-left:1.2rem}@media (max-width:480px){#bds-free-steps{padding:16px}#bds-free-steps a{display:flex;width:100%;margin:8px 0;justify-content:center;box-sizing:border-box}}</style>';
	$h2 = function_exists( 'bds_lang_swap_attrs' ) ? bds_lang_swap_attrs( 'What happens next', 'Qué sigue' ) : '';
	if ( 'add-listing' === $path ) {
		$lis = function_exists( 'bds_lang_pair' )
			? bds_lang_pair( 'Add the business name, a photo, and a real website. In Mexico, add the business WhatsApp.', 'Agregue el nombre, una foto y un sitio real. En México, agregue el WhatsApp del negocio.', 'li' )
			. bds_lang_pair( 'We review the listing. It is not public until that review is done.', 'Revisamos el listado. No es público hasta terminar esa revisión.', 'li' )
			. bds_lang_pair( 'People can then find it and use the contact button on the listing.', 'La gente podrá encontrarlo y usar el contacto del listado.', 'li' )
			: '<li>Add the business name, a photo, and a real website. In Mexico, add the business WhatsApp.</li><li>We review the listing. It is not public until that review is done.</li><li>People can then find it and use the contact button on the listing.</li>';
		$see = function_exists( 'bds_lang_swap_attrs' ) ? bds_lang_swap_attrs( 'See plans', 'Ver planes' ) : '';
		return $style . '<section id="bds-free-steps" aria-label="What happens next"><h2' . $h2 . '>What happens next</h2><ol>'
			. $lis
			. '</ol>'
			. '<p><a href="https://directory.branddad.social/select-your-plan/"' . $see . '>See plans</a></p></section>';
	}
	if ( 'registration' === $path ) {
		$lis = function_exists( 'bds_lang_pair' )
			? bds_lang_pair( 'Create the free account. Browsing stays free.', 'Cree la cuenta gratis. Buscar sigue siendo gratis.', 'li' )
			. bds_lang_pair( 'Verify WhatsApp, then add one business. A basic listing is free.', 'Verifique WhatsApp y agregue un negocio. Un listado básico es gratis.', 'li' )
			. bds_lang_pair( 'We review it before it goes live. Pro, Elite, and Featured stay optional.', 'Lo revisamos antes de publicarlo. Pro, Elite y Featured siguen opcionales.', 'li' )
			: '<li>Create the free account. Browsing stays free.</li><li>Verify WhatsApp, then add one business. A basic listing is free.</li><li>We review it before it goes live. Pro, Elite, and Featured stay optional.</li>';
		$list = function_exists( 'bds_lang_swap_attrs' ) ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '';
		return $style . '<section id="bds-free-steps" aria-label="What happens next"><h2' . $h2 . '>What happens next</h2><ol>'
			. $lis
			. '</ol>'
			. '<p><a href="https://directory.branddad.social/add-listing/"' . $list . '>List my business free</a></p></section>';
	}
	return '';
}

/**
 * Print the next steps. The request path is used only to pick the fixed copy.
 *
 * @return void
 */
function bds_free_list_steps_footer() {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) parse_url( $uri, PHP_URL_PATH );
	$html = bds_free_list_steps_html( $path );
	if ( '' === $html ) {
		return;
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from an allowlisted path
}
add_action( 'wp_footer', 'bds_free_list_steps_footer', 28 );
