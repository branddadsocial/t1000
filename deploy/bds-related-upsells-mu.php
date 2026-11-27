<?php
/**
 * Plugin Name: BrandDad Related Products + Upsells
 * Description: Smart related / upsell / cart cross-sell for BrandDad Woo sites. Lane-aware (gift BoxCoin carts, DMM NOWPayments carts, managed marketing). Tasteful copy. Cache-friendly.
 * Version: 1.1.1
 *
 * 1.1.1 — Social: drop leftover Growth Studio quote SKU (h2/alt/aria duplicate of
 *         priced AI Social Growth) from related/upsell. Other unpriced quote cards stay.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_REL_UPSELL_VER', '1.1.1' );

/** Transient TTL for related/upsell ID lists (seconds). */
if ( ! defined( 'BDS_REL_UPSELL_CACHE_TTL' ) ) {
	define( 'BDS_REL_UPSELL_CACHE_TTL', 6 * HOUR_IN_SECONDS );
}

/* --------------------------------------------------------------------------
 * Lane helpers (gift / DMM / open)
 * -------------------------------------------------------------------------- */

/**
 * @param int $post_id Product ID.
 * @return bool
 */
function bds_rel_is_dmm_product( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}
	if ( function_exists( 'bds_dmm_is_dmm_product' ) ) {
		return (bool) bds_dmm_is_dmm_product( $post_id );
	}
	if ( get_post_meta( $post_id, '_bds_dmm_nowpayments_only', true ) === '1' ) {
		return true;
	}
	if ( (string) get_post_meta( $post_id, '_bds_dmm_service_key', true ) !== '' ) {
		return true;
	}
	$parent = (int) wp_get_post_parent_id( $post_id );
	if ( $parent > 0 && $parent !== $post_id ) {
		return bds_rel_is_dmm_product( $parent );
	}
	return false;
}

/**
 * @param int $post_id Product ID.
 * @return bool
 */
function bds_rel_is_gift_product( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}
	if ( bds_rel_is_dmm_product( $post_id ) ) {
		return false;
	}
	if ( function_exists( 'bds_bge_is_gift_product' ) ) {
		return (bool) bds_bge_is_gift_product( $post_id );
	}
	if ( function_exists( 'bds_gcg_is_gift_product' ) && bds_gcg_is_gift_product( $post_id ) ) {
		return true;
	}
	if ( get_post_meta( $post_id, '_bds_crypto_only', true ) === '1' || get_post_meta( $post_id, '_bds_boxcoin_gift_escrow', true ) === '1' ) {
		return true;
	}
	if ( taxonomy_exists( 'product_cat' ) && has_term( array( 'discount-gift-cards', 'gift-cards', 'save-money-cards' ), 'product_cat', $post_id ) ) {
		return true;
	}
	$slug = (string) get_post_field( 'post_name', $post_id );
	return $slug !== '' && ( strpos( $slug, 'smc-' ) === 0 || strpos( $slug, 'gift-card' ) !== false || strpos( $slug, 'giftcard' ) !== false );
}

/**
 * Payment lane for a product: gift | dmm | open.
 *
 * @param int $post_id Product ID.
 * @return string
 */
function bds_rel_product_lane( $post_id ) {
	if ( bds_rel_is_gift_product( $post_id ) ) {
		return 'gift';
	}
	if ( bds_rel_is_dmm_product( $post_id ) ) {
		return 'dmm';
	}
	return 'open';
}

/**
 * Cart lane flags for upsell safety.
 *
 * @return array{gift:bool,dmm:bool,open:bool,lane:string,mixed_locked:bool}
 */
function bds_rel_cart_lane() {
	$out = array(
		'gift'         => false,
		'dmm'          => false,
		'open'         => false,
		'lane'         => 'empty',
		'mixed_locked' => false,
	);
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $out;
	}
	$items = WC()->cart->get_cart();
	if ( empty( $items ) ) {
		return $out;
	}
	foreach ( $items as $item ) {
		$pid = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		$vid = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;
		$id  = $vid > 0 ? $vid : $pid;
		$lane = bds_rel_product_lane( $id );
		if ( 'gift' === $lane ) {
			$out['gift'] = true;
		} elseif ( 'dmm' === $lane ) {
			$out['dmm'] = true;
		} else {
			$out['open'] = true;
		}
	}
	$locked_kinds = (int) $out['gift'] + (int) $out['dmm'];
	if ( $locked_kinds > 1 || ( $out['gift'] && $out['open'] ) || ( $out['dmm'] && $out['open'] ) ) {
		$out['mixed_locked'] = true;
	}
	if ( $out['gift'] && ! $out['dmm'] && ! $out['open'] ) {
		$out['lane'] = 'gift';
	} elseif ( $out['dmm'] && ! $out['gift'] && ! $out['open'] ) {
		$out['lane'] = 'dmm';
	} elseif ( $out['open'] && ! $out['gift'] && ! $out['dmm'] ) {
		$out['lane'] = 'open';
	} elseif ( $out['mixed_locked'] ) {
		$out['lane'] = 'mixed';
	} else {
		$out['lane'] = 'open';
	}
	return $out;
}

/**
 * Whether candidate may be suggested next to source (same payment lane).
 *
 * @param int $source_id    Source product.
 * @param int $candidate_id Candidate product.
 * @return bool
 */
function bds_rel_lanes_compatible( $source_id, $candidate_id ) {
	return bds_rel_product_lane( $source_id ) === bds_rel_product_lane( $candidate_id );
}

/* --------------------------------------------------------------------------
 * Signals: brand / face / service buckets
 * -------------------------------------------------------------------------- */

/**
 * @param int $post_id Product ID.
 * @return string Brand key (merchant host or brand slug).
 */
function bds_rel_gift_brand_key( $post_id ) {
	$post_id = (int) $post_id;
	foreach ( array( '_bds_gift_merchant', '_bdgc_brand', '_bds_brand' ) as $key ) {
		$v = strtolower( trim( (string) get_post_meta( $post_id, $key, true ) ) );
		if ( $v !== '' ) {
			return sanitize_title( $v );
		}
	}
	$slug = (string) get_post_field( 'post_name', $post_id );
	if ( preg_match( '/^smc-(.+?)-(\d+)(?:-\d+)?$/', $slug, $m ) ) {
		return sanitize_title( $m[1] );
	}
	$title = (string) get_the_title( $post_id );
	if ( preg_match( '/^(.+?)\s+\$\d+/i', $title, $m ) ) {
		return sanitize_title( $m[1] );
	}
	return '';
}

/**
 * @param int $post_id Product ID.
 * @return int Face value dollars (0 if unknown).
 */
function bds_rel_gift_face_value( $post_id ) {
	$post_id = (int) $post_id;
	foreach ( array( '_bds_gift_face_value', '_bdgc_face_value', '_bds_face_value' ) as $key ) {
		$v = get_post_meta( $post_id, $key, true );
		if ( $v !== '' && is_numeric( $v ) ) {
			return (int) round( (float) $v );
		}
	}
	$slug = (string) get_post_field( 'post_name', $post_id );
	if ( preg_match( '/-(\d+)(?:-\d+)?$/', $slug, $m ) ) {
		return (int) $m[1];
	}
	$title = (string) get_the_title( $post_id );
	if ( preg_match( '/\$(\d+)/', $title, $m ) ) {
		return (int) $m[1];
	}
	return 0;
}

/**
 * Service / intent buckets from title + slug (managed marketing + DMM).
 *
 * @param int $post_id Product ID.
 * @return string[]
 */
function bds_rel_service_buckets( $post_id ) {
	$text = strtolower( (string) get_the_title( $post_id ) . ' ' . (string) get_post_field( 'post_name', $post_id ) );
	$map  = array(
		'followers'  => array( 'follower', 'followers', 'subs', 'subscriber', 'subscribers' ),
		'likes'      => array( 'like', 'likes', 'heart' ),
		'views'      => array( 'view', 'views', 'plays', 'watch' ),
		'comments'   => array( 'comment', 'comments' ),
		'engagement' => array( 'engagement', 'engage', 'viral', 'amplification' ),
		'instagram'  => array( 'instagram', 'ig-', ' ig ' ),
		'tiktok'     => array( 'tiktok', 'tik-tok' ),
		'facebook'   => array( 'facebook', 'fb ' ),
		'youtube'    => array( 'youtube', 'yt ' ),
		'linkedin'   => array( 'linkedin' ),
		'seo'        => array( 'seo', 'search', 'rank', 'backlink', 'google' ),
		'content'    => array( 'content', 'copywriting', 'blog', 'article' ),
		'pr'         => array( 'press', 'pr ', 'media coverage', 'reputation' ),
		'ads'        => array( 'ads', 'advertis', 'ppc', 'billboard' ),
		'website'    => array( 'website', 'web design', 'landing page', 'wordpress' ),
		'logo'       => array( 'logo', 'branding', 'identity' ),
		'management' => array( 'management', 'managed', 'social media management' ),
	);
	$hits = array();
	foreach ( $map as $bucket => $needles ) {
		foreach ( $needles as $n ) {
			if ( false !== strpos( $text, $n ) ) {
				$hits[] = $bucket;
				break;
			}
		}
	}
	return array_values( array_unique( $hits ) );
}

/**
 * Do not recommend retired aliases, but allow canonical Outreach offers.
 *
 * @param int $post_id Product ID.
 * @return bool
 */
function bds_rel_is_social_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( $host === '' ) {
		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}
	if ( false !== strpos( $host, 'directory.branddad' ) ) {
		return false;
	}
	return false !== strpos( $host, 'branddad.social' );
}

/**
 * Published unpriced quote rows that still leak leftover titles/alts on Social.
 * They stay unpriced; they must not appear as related duplicates of live SKUs.
 *
 * @return int[]
 */
function bds_rel_social_leftover_quote_ids() {
	return array( 53641 );
}

/**
 * @param int $post_id Product ID.
 * @return bool
 */
function bds_rel_is_retired_offer( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id > 0 && bds_rel_is_social_host() ) {
		if ( in_array( $post_id, bds_rel_social_leftover_quote_ids(), true ) ) {
			return true;
		}
		$title = (string) get_the_title( $post_id );
		if ( false !== stripos( $title, 'Social Media Growth Studio' ) ) {
			$link = function_exists( 'get_permalink' ) ? (string) get_permalink( $post_id ) : '';
			if ( false !== strpos( $link, '/product/ai-social-growth' ) ) {
				return true;
			}
		}
	}
	if ( function_exists( 'bds_hide_outreach_is_outreach_product' ) && bds_hide_outreach_is_outreach_product( $post_id ) ) {
		return true;
	}
	$slug = sanitize_title( (string) get_post_field( 'post_name', $post_id ) );
	if ( function_exists( 'bds_src_canonical_map' ) ) {
		return isset( bds_src_canonical_map()[ $slug ] );
	}
	return in_array(
		$slug,
		array(
			'linkedin-outreach-launch',
			'linkedin-outreach-networking-strategy-for-professionals',
			'done-for-you-linkedin-outreach',
			'done-for-you-linkedin-outreach-conversation-management',
			'social-media-management-video',
		),
		true
	);
}

/**
 * Complementary buckets for upsells (not just same keyword).
 *
 * @param string[] $buckets Source buckets.
 * @return string[]
 */
function bds_rel_complementary_buckets( $buckets ) {
	$pairs = array(
		'followers'  => array( 'likes', 'views', 'comments', 'engagement' ),
		'likes'      => array( 'followers', 'views', 'comments', 'engagement' ),
		'views'      => array( 'likes', 'followers', 'engagement' ),
		'comments'   => array( 'likes', 'followers', 'engagement' ),
		'engagement' => array( 'followers', 'likes', 'views' ),
		'instagram'  => array( 'tiktok', 'facebook', 'engagement', 'management' ),
		'tiktok'     => array( 'instagram', 'engagement', 'views' ),
		'facebook'   => array( 'instagram', 'engagement', 'management' ),
		'youtube'    => array( 'views', 'engagement', 'seo' ),
		'linkedin'   => array( 'seo', 'engagement', 'content', 'management' ),
		'seo'        => array( 'content', 'pr', 'linkedin', 'ads' ),
		'content'    => array( 'seo', 'pr', 'linkedin' ),
		'pr'         => array( 'seo', 'reputation', 'ads', 'content' ),
		'ads'        => array( 'seo', 'engagement', 'management' ),
		'website'    => array( 'logo', 'seo', 'content' ),
		'logo'       => array( 'website', 'management' ),
		'management' => array( 'engagement', 'seo', 'linkedin', 'instagram' ),
	);
	$out = array();
	foreach ( (array) $buckets as $b ) {
		if ( isset( $pairs[ $b ] ) ) {
			foreach ( $pairs[ $b ] as $c ) {
				$out[] = $c;
			}
		}
	}
	return array_values( array_unique( $out ) );
}

/* --------------------------------------------------------------------------
 * Candidate queries (perf: small capped WP_Query + transient)
 * -------------------------------------------------------------------------- */

/**
 * @param int    $product_id Product ID.
 * @param string $kind       related|upsell|crosssell.
 * @param int    $limit      Cap.
 * @return int[]
 */
function bds_rel_cached_ids( $product_id, $kind, $limit ) {
	$product_id = (int) $product_id;
	$limit      = max( 1, min( 8, (int) $limit ) );
	$kind       = sanitize_key( $kind );
	$host       = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_key( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) : 'site';
	$cache_key  = 'bds_rel_' . md5( $host . '|' . $product_id . '|' . $kind . '|' . $limit . '|' . BDS_REL_UPSELL_VER );
	$cached     = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return array_map( 'intval', $cached );
	}
	$ids = bds_rel_compute_ids( $product_id, $kind, $limit );
	set_transient( $cache_key, $ids, (int) BDS_REL_UPSELL_CACHE_TTL );
	return $ids;
}

/**
 * @param int    $product_id Product ID.
 * @param string $kind       related|upsell|crosssell.
 * @param int    $limit      Cap.
 * @return int[]
 */
function bds_rel_compute_ids( $product_id, $kind, $limit ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}

	$lane = bds_rel_product_lane( $product_id );
	$limit = max( 1, min( 8, (int) $limit ) );

	// Manual Woo meta wins when present and lane-safe.
	$manual = array();
	if ( 'upsell' === $kind ) {
		$manual = array_map( 'intval', (array) $product->get_upsell_ids( 'edit' ) );
	} elseif ( 'crosssell' === $kind ) {
		$manual = array_map( 'intval', (array) $product->get_cross_sell_ids( 'edit' ) );
	}
	$manual = array_values(
		array_filter(
			$manual,
			static function ( $id ) use ( $product_id ) {
				$id = (int) $id;
				return $id > 0 && $id !== $product_id && 'publish' === get_post_status( $id ) && ! bds_rel_is_retired_offer( $id ) && bds_rel_lanes_compatible( $product_id, $id );
			}
		)
	);
	if ( count( $manual ) >= $limit ) {
		return array_slice( $manual, 0, $limit );
	}

	$scored = array();
	foreach ( $manual as $id ) {
		$scored[ $id ] = 1000;
	}

	$exclude = array_merge( array( $product_id ), array_keys( $scored ) );
	$cart_ids = array();
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! empty( $item['product_id'] ) ) {
				$exclude[]  = (int) $item['product_id'];
				$cart_ids[] = (int) $item['product_id'];
			}
			if ( ! empty( $item['variation_id'] ) ) {
				$exclude[] = (int) $item['variation_id'];
			}
		}
	}
	$exclude = array_values( array_unique( array_map( 'intval', $exclude ) ) );

	$tax_query = array();
	if ( 'gift' === $lane && taxonomy_exists( 'product_cat' ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( 'discount-gift-cards', 'gift-cards', 'save-money-cards' ),
		);
	}

	$meta_query = array();
	if ( 'dmm' === $lane ) {
		$meta_query['relation'] = 'OR';
		$meta_query[]           = array(
			'key'   => '_bds_dmm_nowpayments_only',
			'value' => '1',
		);
		$meta_query[]           = array(
			'key'     => '_bds_dmm_service_key',
			'compare' => 'EXISTS',
		);
	}

	$q_args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => 36,
		'post__not_in'           => $exclude,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
		'orderby'                => 'date',
		'order'                  => 'DESC',
	);
	if ( $tax_query ) {
		$q_args['tax_query'] = $tax_query;
	}
	if ( $meta_query ) {
		$q_args['meta_query'] = $meta_query;
	}

	// Same category first for open / managed.
	$cat_ids = array();
	if ( 'open' === $lane && taxonomy_exists( 'product_cat' ) ) {
		$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) ) {
			$cat_ids = array_map( 'intval', (array) $terms );
		}
	}

	$candidate_ids = array();
	if ( $cat_ids ) {
		$cat_q                 = $q_args;
		$cat_q['posts_per_page'] = 24;
		$cat_q['tax_query']      = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $cat_ids,
			),
		);
		$candidate_ids = array_map( 'intval', (array) get_posts( $cat_q ) );
	}
	if ( count( $candidate_ids ) < 24 ) {
		$more = array_map( 'intval', (array) get_posts( $q_args ) );
		$candidate_ids = array_values( array_unique( array_merge( $candidate_ids, $more ) ) );
	}

	$src_brand  = ( 'gift' === $lane ) ? bds_rel_gift_brand_key( $product_id ) : '';
	$src_face   = ( 'gift' === $lane ) ? bds_rel_gift_face_value( $product_id ) : 0;
	$src_buck   = bds_rel_service_buckets( $product_id );
	$comp_buck  = bds_rel_complementary_buckets( $src_buck );
	$is_upsell  = ( 'upsell' === $kind || 'crosssell' === $kind );

	foreach ( $candidate_ids as $cid ) {
		if ( ! bds_rel_lanes_compatible( $product_id, $cid ) ) {
			continue;
		}
		// Never recommend retired aliases. Canonical Outreach Launch/Managed stay eligible.
		if ( bds_rel_is_retired_offer( $cid ) ) {
			continue;
		}
		// Directory taste: never mix gift ↔ open marketing on related rails.
		if ( 'gift' === $lane && ! bds_rel_is_gift_product( $cid ) ) {
			continue;
		}
		if ( 'dmm' === $lane && ! bds_rel_is_dmm_product( $cid ) ) {
			continue;
		}
		if ( 'open' === $lane && ( bds_rel_is_gift_product( $cid ) || bds_rel_is_dmm_product( $cid ) ) ) {
			continue;
		}

		$score = 1;
		if ( 'gift' === $lane ) {
			$brand = bds_rel_gift_brand_key( $cid );
			$face  = bds_rel_gift_face_value( $cid );
			if ( $src_brand && $brand && $brand === $src_brand ) {
				$score += 80;
			}
			if ( $src_face > 0 && $face > 0 ) {
				$diff = abs( $src_face - $face );
				if ( 0 === $diff ) {
					$score += 40;
				} elseif ( $diff <= 25 ) {
					$score += 28;
				} elseif ( $diff <= 50 ) {
					$score += 16;
				} elseif ( $diff <= 100 ) {
					$score += 8;
				}
			}
			// Upsell: nudge a nearby higher face when same brand.
			if ( $is_upsell && $src_brand && $brand === $src_brand && $face > $src_face ) {
				$score += 12;
			}
		} else {
			$buck = bds_rel_service_buckets( $cid );
			$same = array_intersect( $src_buck, $buck );
			$comp = array_intersect( $comp_buck, $buck );
			$score += 18 * count( $same );
			if ( $is_upsell ) {
				$score += 22 * count( $comp );
			} else {
				$score += 10 * count( $comp );
			}
			if ( $cat_ids && has_term( $cat_ids, 'product_cat', $cid ) ) {
				$score += 12;
			}
		}

		if ( $score > 1 ) {
			$scored[ $cid ] = isset( $scored[ $cid ] ) ? max( $scored[ $cid ], $score ) : $score;
		}
	}

	arsort( $scored, SORT_NUMERIC );
	$ids = array_slice( array_map( 'intval', array_keys( $scored ) ), 0, $limit );

	/**
	 * Filter computed related/upsell IDs.
	 *
	 * @param int[]  $ids         Product IDs.
	 * @param int    $product_id  Source product.
	 * @param string $kind        related|upsell|crosssell.
	 */
	return (array) apply_filters( 'bds_rel_upsell_ids', $ids, $product_id, $kind );
}

/* --------------------------------------------------------------------------
 * Woo filters — related / upsells / cart cross-sells
 * -------------------------------------------------------------------------- */

/**
 * Limit counts by site/lane (Directory stays light).
 *
 * @param string $kind related|upsell|crosssell.
 * @param string $lane gift|dmm|open.
 * @return int
 */
function bds_rel_limit_for( $kind, $lane ) {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$is_dir = ( false !== strpos( $host, 'directory.branddad' ) );
	if ( 'crosssell' === $kind ) {
		return $is_dir ? 2 : 3;
	}
	if ( 'upsell' === $kind ) {
		if ( 'gift' === $lane ) {
			return $is_dir ? 3 : 4;
		}
		return $is_dir ? 3 : 4;
	}
	// related
	if ( 'gift' === $lane ) {
		return $is_dir ? 3 : 4;
	}
	return $is_dir ? 3 : 4;
}

/**
 * @param array $args Related args.
 * @return array
 */
function bds_rel_related_args( $args ) {
	$pid  = isset( $args['product'] ) ? (int) ( is_object( $args['product'] ) ? $args['product']->get_id() : $args['product'] ) : (int) get_the_ID();
	$lane = bds_rel_product_lane( $pid );
	$lim  = bds_rel_limit_for( 'related', $lane );
	$args['posts_per_page'] = $lim;
	$args['columns']        = min( 4, $lim );
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'bds_rel_related_args', 40 );

/**
 * Replace / enrich related product IDs.
 *
 * @param int[] $related Related IDs.
 * @param int   $product_id Product ID.
 * @param array $args Args.
 * @return int[]
 */
function bds_rel_filter_related_posts( $related, $product_id, $args = array() ) {
	$product_id = (int) $product_id;
	$lane       = bds_rel_product_lane( $product_id );
	$limit      = bds_rel_limit_for( 'related', $lane );
	$upsell_lim = bds_rel_limit_for( 'upsell', $lane );
	// Exclude upsell SKUs so “Add this too” and “You may also like” don’t repeat.
	$upsells = bds_rel_cached_ids( $product_id, 'upsell', $upsell_lim );
	$pool    = bds_rel_cached_ids( $product_id, 'related', $limit + count( $upsells ) + 2 );
	$smart   = array_slice( array_values( array_diff( array_map( 'intval', (array) $pool ), array_map( 'intval', (array) $upsells ) ) ), 0, $limit );
	$smart   = array_values(
		array_filter(
			$smart,
			static function ( $id ) {
				return $id > 0 && ! bds_rel_is_retired_offer( $id );
			}
		)
	);
	if ( empty( $smart ) ) {
		// Still strip incompatible defaults.
		$related = array_values(
			array_filter(
				array_map( 'intval', (array) $related ),
				static function ( $id ) use ( $product_id, $upsells ) {
					return $id > 0 && ! in_array( $id, $upsells, true ) && ! bds_rel_is_retired_offer( $id ) && bds_rel_lanes_compatible( $product_id, $id );
				}
			)
		);
		return array_slice( $related, 0, $limit );
	}
	return array_slice( $smart, 0, $limit );
}
add_filter( 'woocommerce_related_products', 'bds_rel_filter_related_posts', 40, 3 );

/**
 * Older Woo hook name used by some themes.
 *
 * @param int[] $ids IDs.
 * @param int   $product_id Product.
 * @return int[]
 */
function bds_rel_filter_related_posts_legacy( $ids, $product_id ) {
	return bds_rel_filter_related_posts( $ids, $product_id, array() );
}
add_filter( 'woocommerce_product_related_posts', 'bds_rel_filter_related_posts_legacy', 40, 2 );

/**
 * Inject smart upsells when meta empty / incomplete.
 *
 * @param int[]      $ids Product upsell IDs.
 * @param WC_Product $product Product.
 * @return int[]
 */
function bds_rel_filter_upsell_ids( $ids, $product ) {
	static $busy = false;
	if ( $busy || ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return $ids;
	}
	$pid  = (int) $product->get_id();
	$lane = bds_rel_product_lane( $pid );
	$lim  = bds_rel_limit_for( 'upsell', $lane );
	$safe = array_values(
		array_filter(
			array_map( 'intval', (array) $ids ),
			static function ( $id ) use ( $pid ) {
				return $id > 0 && $id !== $pid && bds_rel_lanes_compatible( $pid, $id );
			}
		)
	);
	if ( count( $safe ) >= $lim ) {
		return array_slice( $safe, 0, $lim );
	}
	$busy  = true;
	$smart = bds_rel_cached_ids( $pid, 'upsell', $lim );
	$busy  = false;
	$merged = array_values( array_unique( array_merge( $safe, $smart ) ) );
	return array_slice( $merged, 0, $lim );
}
add_filter( 'woocommerce_product_get_upsell_ids', 'bds_rel_filter_upsell_ids', 40, 2 );
add_filter( 'woocommerce_product_variation_get_upsell_ids', 'bds_rel_filter_upsell_ids', 40, 2 );

/**
 * @param int[]      $ids Cross-sell IDs.
 * @param WC_Product $product Product.
 * @return int[]
 */
function bds_rel_filter_crosssell_ids( $ids, $product ) {
	static $busy = false;
	if ( $busy || ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return $ids;
	}
	$pid  = (int) $product->get_id();
	$lane = bds_rel_product_lane( $pid );
	$lim  = bds_rel_limit_for( 'crosssell', $lane );
	$safe = array_values(
		array_filter(
			array_map( 'intval', (array) $ids ),
			static function ( $id ) use ( $pid ) {
				return $id > 0 && $id !== $pid && bds_rel_lanes_compatible( $pid, $id );
			}
		)
	);
	if ( count( $safe ) >= $lim ) {
		return array_slice( $safe, 0, $lim );
	}
	$busy  = true;
	$smart = bds_rel_cached_ids( $pid, 'crosssell', $lim );
	$busy  = false;
	$merged = array_values( array_unique( array_merge( $safe, $smart ) ) );
	return array_slice( $merged, 0, $lim );
}
add_filter( 'woocommerce_product_get_cross_sell_ids', 'bds_rel_filter_crosssell_ids', 40, 2 );
add_filter( 'woocommerce_product_variation_get_cross_sell_ids', 'bds_rel_filter_crosssell_ids', 40, 2 );

/**
 * Cart cross-sells: only same payment lane; hide on mixed locked carts.
 *
 * @param int[] $ids Cross-sell IDs.
 * @return int[]
 */
function bds_rel_cart_crosssell_ids( $ids ) {
	$cart = bds_rel_cart_lane();
	if ( 'empty' === $cart['lane'] || 'mixed' === $cart['lane'] || $cart['mixed_locked'] ) {
		return array();
	}
	$lim = bds_rel_limit_for( 'crosssell', $cart['lane'] );
	$out = array();
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		$pid = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		if ( $pid <= 0 ) {
			continue;
		}
		foreach ( bds_rel_cached_ids( $pid, 'crosssell', $lim ) as $cid ) {
			if ( bds_rel_product_lane( $cid ) !== $cart['lane'] ) {
				continue;
			}
			$out[] = (int) $cid;
		}
	}
	$out = array_values( array_unique( $out ) );
	// Strip anything already in cart.
	$in_cart = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( ! empty( $item['product_id'] ) ) {
			$in_cart[] = (int) $item['product_id'];
		}
	}
	$out = array_values( array_diff( $out, $in_cart ) );
	return array_slice( $out, 0, $lim );
}
add_filter( 'woocommerce_cart_crosssell_ids', 'bds_rel_cart_crosssell_ids', 40 );

/**
 * Do not render cart cross-sells on checkout (keeps checkout clean; cart page only).
 */
function bds_rel_maybe_remove_checkout_cross_sells() {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		remove_action( 'woocommerce_checkout_before_order_review', 'woocommerce_cross_sell_display' );
	}
}
add_action( 'wp', 'bds_rel_maybe_remove_checkout_cross_sells', 20 );

/* --------------------------------------------------------------------------
 * Customer copy (DIRECTORY_CUSTOMER_COPY.md voice)
 * -------------------------------------------------------------------------- */

/**
 * @return string
 */
function bds_rel_heading_related() {
	return __( 'You may also like', 'branddad' );
}

/**
 * @return string
 */
function bds_rel_heading_upsells() {
	return __( 'Add this too', 'branddad' );
}

/**
 * @return string
 */
function bds_rel_heading_crosssells() {
	return __( 'Add this too', 'branddad' );
}

add_filter( 'woocommerce_product_related_products_heading', 'bds_rel_heading_related', 40 );
add_filter( 'woocommerce_product_upsells_products_heading', 'bds_rel_heading_upsells', 40 );
add_filter( 'woocommerce_product_cross_sells_products_heading', 'bds_rel_heading_crosssells', 40 );

/**
 * Lightweight CSS — no widgets, no JS.
 */
function bds_rel_front_css() {
	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	if ( ! ( function_exists( 'is_product' ) && is_product() ) && ! ( function_exists( 'is_cart' ) && is_cart() ) ) {
		return;
	}
	$css = '.related.products>h2,.upsells.products>h2,.cross-sells>h2,.related.products .woocommerce-loop-product__title{font-weight:700}'
		. '.related.products,.upsells.products,.cross-sells{margin-top:1.5rem}'
		. '.cross-sells ul.products{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}'
		. 'body.tax-product_cat .related.products{margin-top:1.25rem}';
	wp_register_style( 'bds-rel-upsell', false, array(), BDS_REL_UPSELL_VER );
	wp_enqueue_style( 'bds-rel-upsell' );
	wp_add_inline_style( 'bds-rel-upsell', $css );
}
add_action( 'wp_enqueue_scripts', 'bds_rel_front_css', 40 );

/**
 * HTML comment marker for deploy verify (product pages only).
 */
function bds_rel_marker_comment() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	echo "\n<!-- bds-related-upsells v" . esc_html( BDS_REL_UPSELL_VER ) . " -->\n";
}
add_action( 'wp_footer', 'bds_rel_marker_comment', 99 );

/**
 * Soft-align DMM related heading when that MU is present.
 *
 * @param array  $related Related rows.
 * @param int    $listing_id Listing ID.
 * @return array
 */
function bds_rel_dmm_related_passthrough( $related, $listing_id ) {
	return $related;
}
add_filter( 'bds_dmm_related_services', 'bds_rel_dmm_related_passthrough', 10, 2 );

/* --------------------------------------------------------------------------
 * Admin: optional stamp of Woo meta (Tools link) — no auto-write on front
 * -------------------------------------------------------------------------- */

/**
 * Clear related caches after product save.
 *
 * @param int $product_id Product ID.
 */
function bds_rel_clear_product_cache( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return;
	}
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	foreach ( array( 'related', 'upsell', 'crosssell' ) as $kind ) {
		for ( $lim = 1; $lim <= 8; $lim++ ) {
			delete_transient( 'bds_rel_' . md5( sanitize_key( $host ) . '|' . $product_id . '|' . $kind . '|' . $lim . '|' . BDS_REL_UPSELL_VER ) );
		}
	}
}
add_action( 'woocommerce_update_product', 'bds_rel_clear_product_cache', 50 );
add_action( 'woocommerce_new_product', 'bds_rel_clear_product_cache', 50 );

/**
 * Persist smart IDs into Woo upsell/cross-sell meta for a product (admin/oneshot).
 *
 * @param int $product_id Product ID.
 * @return array{upsell:int[],crosssell:int[]}
 */
function bds_rel_stamp_product_meta( $product_id ) {
	$product_id = (int) $product_id;
	$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
	if ( ! $product ) {
		return array( 'upsell' => array(), 'crosssell' => array() );
	}
	$lane = bds_rel_product_lane( $product_id );
	$up   = bds_rel_compute_ids( $product_id, 'upsell', bds_rel_limit_for( 'upsell', $lane ) );
	$cs   = bds_rel_compute_ids( $product_id, 'crosssell', bds_rel_limit_for( 'crosssell', $lane ) );
	update_post_meta( $product_id, '_upsell_ids', $up );
	update_post_meta( $product_id, '_crosssell_ids', $cs );
	bds_rel_clear_product_cache( $product_id );
	return array(
		'upsell'    => $up,
		'crosssell' => $cs,
	);
}
