<?php
/**
 * Plugin Name: BrandDad Hide Outreach
 * Description: Hide LinkedIn outreach products from all customer surfaces on branddad.social; 301 to visibility product. Preserve order history.
 * Version: 1.0.4
 *
 * Deploy: public_html/branddad.social/wp-content/mu-plugins/bds-hide-outreach-mu.php
 *
 * v1.0.2: never call get_posts/WP_Query from pre_get_posts (caused infinite recursion /
 * "Maximum call stack size reached" critical errors when the ID transient was cold).
 * v1.0.3: scrub "LinkedIn Outreach" hub cards / headings from public HTML output.
 * v1.0.4: allow the new published Outreach Launch and Managed products.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_HIDE_OUTREACH_VER' ) ) {
	return;
}
define( 'BDS_HIDE_OUTREACH_VER', '1.0.4' );

/**
 * Outreach slugs that are live customer-facing products.
 *
 * @return string[]
 */
function bds_hide_outreach_allowed_slugs() {
	return array(
		'linkedin-outreach-launch-service',
		'linkedin-outreach-managed',
	);
}

/**
 * Slugs to hide from every public surface.
 *
 * @return string[]
 */
function bds_hide_outreach_slugs() {
	return array(
		'linkedin-outreach-networking-strategy-for-professionals',
		'done-for-you-linkedin-outreach',
		'done-for-you-linkedin-outreach-conversation-management',
	);
}

/**
 * Canonical redirect target for retired outreach URLs.
 *
 * @return string Path under home.
 */
function bds_hide_outreach_redirect_path() {
	return '/product/linkedin-visibility-amplification-system-for-professionals/';
}

/**
 * Resolve outreach product IDs without firing WP_Query filters (safe inside pre_get_posts).
 *
 * @return int[]
 */
function bds_hide_outreach_product_ids() {
	static $guard = false;
	static $memo  = null;
	if ( is_array( $memo ) ) {
		return $memo;
	}
	$cached = get_transient( 'bds_hide_outreach_ids' );
	if ( is_array( $cached ) ) {
		$memo = array_map( 'intval', $cached );
		return $memo;
	}
	if ( $guard ) {
		return array();
	}
	$guard = true;

	global $wpdb;
	$ids = array();
	$slugs = bds_hide_outreach_slugs();
	if ( $slugs ) {
		$in = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_name IN ($in) LIMIT 20",
			$slugs
		);
		$rows = $wpdb->get_col( $sql );
		foreach ( $rows as $pid ) {
			$ids[] = (int) $pid;
		}
	}
	$meta = $wpdb->get_col(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_bds_hide_outreach' AND meta_value='1' LIMIT 20"
	);
	foreach ( $meta as $pid ) {
		$ids[] = (int) $pid;
	}
	$ids  = array_values( array_unique( array_filter( $ids ) ) );
	$memo = $ids;
	set_transient( 'bds_hide_outreach_ids', $ids, HOUR_IN_SECONDS );
	$guard = false;
	return $ids;
}

/**
 * @param int $product_id Product ID.
 * @return bool
 */
function bds_hide_outreach_is_outreach_product( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return false;
	}
	if ( in_array( $product_id, bds_hide_outreach_product_ids(), true ) ) {
		return true;
	}
	$slug = (string) get_post_field( 'post_name', $product_id );
	if ( in_array( $slug, bds_hide_outreach_allowed_slugs(), true ) ) {
		return false;
	}
	if ( in_array( $slug, bds_hide_outreach_slugs(), true ) ) {
		return true;
	}
	$title = (string) get_the_title( $product_id );
	if ( preg_match( '/linkedin\s+outreach|done[- ]for[- ]you\s+linkedin\s+outreach/i', $title ) ) {
		return true;
	}
	if ( get_post_meta( $product_id, '_bds_hide_outreach', true ) === '1' ) {
		return true;
	}
	return false;
}

/**
 * Force hidden catalog visibility for outreach SKUs (idempotent soft-hide).
 */
add_action(
	'init',
	static function () {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$flag = get_option( 'bds_hide_outreach_applied_v1', '' );
		if ( $flag === BDS_HIDE_OUTREACH_VER ) {
			return;
		}
		foreach ( bds_hide_outreach_product_ids() as $pid ) {
			$pid = (int) $pid;
			if ( $pid <= 0 ) {
				continue;
			}
			update_post_meta( $pid, '_bds_hide_outreach', '1' );
			update_post_meta( $pid, '_visibility', 'hidden' );
			update_post_meta( $pid, '_catalog_visibility', 'hidden' );
			if ( function_exists( 'wc_get_product' ) ) {
				$p = wc_get_product( $pid );
				if ( $p ) {
					$p->set_catalog_visibility( 'hidden' );
					$p->set_featured( false );
					$p->save();
				}
			}
		}
		update_option( 'bds_hide_outreach_applied_v1', BDS_HIDE_OUTREACH_VER, false );
	},
	20
);

/** 301 outreach product URLs → visibility package (or services). */
add_action(
	'template_redirect',
	static function () {
		if ( is_admin() ) {
			return;
		}
		if ( ! empty( $_GET['bds_ops_view'] ) && current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! preg_match( '/linkedin-outreach|done-for-you-linkedin-outreach/i', $uri ) ) {
			return;
		}
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$slug = basename( untrailingslashit( strtolower( $path ) ) );
		if ( in_array( $slug, bds_hide_outreach_allowed_slugs(), true ) ) {
			return;
		}
		$target = home_url( bds_hide_outreach_redirect_path() );
		wp_safe_redirect( $target, 301 );
		exit;
	},
	1
);

add_filter( 'woocommerce_product_query_meta_query', 'bds_hide_outreach_meta_hidden', 20 );
/**
 * @param array<int,mixed> $meta_query Meta query.
 * @return array<int,mixed>
 */
function bds_hide_outreach_meta_hidden( $meta_query ) {
	if ( ! is_array( $meta_query ) ) {
		$meta_query = array();
	}
	$meta_query[] = array(
		'key'     => '_bds_hide_outreach',
		'compare' => 'NOT EXISTS',
	);
	return $meta_query;
}

add_filter( 'woocommerce_related_products', 'bds_hide_outreach_filter_ids', 50, 1 );
add_filter( 'woocommerce_product_related_posts', 'bds_hide_outreach_filter_ids', 50, 1 );
add_filter( 'woocommerce_upsell_ids', 'bds_hide_outreach_filter_ids', 50, 1 );
add_filter( 'woocommerce_crosssell_ids', 'bds_hide_outreach_filter_ids', 50, 1 );

/**
 * @param int[] $ids Product IDs.
 * @return int[]
 */
function bds_hide_outreach_filter_ids( $ids ) {
	if ( ! is_array( $ids ) ) {
		return $ids;
	}
	$block = bds_hide_outreach_product_ids();
	if ( ! $block ) {
		return $ids;
	}
	return array_values(
		array_filter(
			array_map( 'intval', $ids ),
			static function ( $id ) use ( $block ) {
				return $id > 0 && ! in_array( $id, $block, true );
			}
		)
	);
}

/** Strip outreach from shortcode / widgets product loops. */
add_action(
	'pre_get_posts',
	static function ( $q ) {
		if ( is_admin() || ! $q instanceof WP_Query || ! $q->get( 'post_type' ) ) {
			return;
		}
		$pt         = $q->get( 'post_type' );
		$is_product = ( 'product' === $pt ) || ( is_array( $pt ) && in_array( 'product', $pt, true ) );
		if ( ! $is_product ) {
			return;
		}
		$block = bds_hide_outreach_product_ids();
		if ( ! $block ) {
			return;
		}
		$not_in = (array) $q->get( 'post__not_in' );
		$q->set( 'post__not_in', array_values( array_unique( array_merge( array_map( 'intval', $not_in ), $block ) ) ) );
	},
	40
);

/**
 * Scrub menu / nav items pointing at outreach product.
 *
 * @param array<int,object> $items Menu items.
 * @return array<int,object>
 */
function bds_hide_outreach_filter_nav( $items ) {
	if ( ! is_array( $items ) ) {
		return $items;
	}
	$out = array();
	foreach ( $items as $item ) {
		$url   = isset( $item->url ) ? (string) $item->url : '';
		$title = isset( $item->title ) ? (string) $item->title : '';
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$slug  = basename( untrailingslashit( strtolower( $path ) ) );
		if ( in_array( $slug, bds_hide_outreach_allowed_slugs(), true ) ) {
			$out[] = $item;
			continue;
		}
		if ( preg_match( '/linkedin-outreach|done-for-you-linkedin-outreach/i', $url ) ) {
			continue;
		}
		$out[] = $item;
	}
	return $out;
}
add_filter( 'wp_nav_menu_objects', 'bds_hide_outreach_filter_nav', 40 );

/**
 * Strip outreach product URLs / CTA copy from HTML (Elementor + classic).
 *
 * @param string $html HTML.
 * @return string
 */
function bds_hide_outreach_scrub_html( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return $html;
	}
	$target = esc_url( home_url( bds_hide_outreach_redirect_path() ) );
	$hidden = implode( '|', array_map( 'preg_quote', bds_hide_outreach_slugs() ) );
	$html = preg_replace(
		'#href=(["\'])[^"\']*(?:' . $hidden . ')[^"\']*\1#i',
		'href=' . '"' . $target . '"',
		$html
	);
	return is_string( $html ) ? $html : '';
}
add_action(
	'template_redirect',
	static function () {
		if ( is_admin() ) {
			return;
		}
		ob_start( 'bds_hide_outreach_scrub_html' );
	},
	0
);
