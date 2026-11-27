<?php
/**
 * Plugin Name: BrandDad Directory Facet Browse
 * Description: One faceted Browse Listings system — city ↔ category/service with AND filters, real published counts, no duplicate city pickers, no 404 on city-gate.
 * Version: 1.1.4
 * Author: BrandDad
 *
 * Deploy MU: directory.branddad.social/wp-content/mu-plugins/bds-directory-facet-browse-mu.php
 *
 * Secure design (GET facets / SQL):
 * - Inputs: directory_type, in_loc, in_cat, q, ref — sanitize_title / sanitize_text_field / absint only.
 * - SQL: $wpdb->prepare; published at_biz_dir/atbdp_listing only; no user SQL fragments.
 * - Public read-only facets; no auth/session writes. Partnero ref preserved via bds_po_url.
 * - No new public write endpoints in this MU.
 *
 * v1.1.4 — Restore the faceted-results H1 after the Directory's global heading
 *            cleanup runs, and demote the hidden legacy archive title.
 * v1.1.3 — The primary faceted-results title is the page H1 so location and
 *          category result pages have one clear accessible/SEO heading.
 * v1.1.2 — Typed browse uses mapped categories as source of truth (Austin hotels
 *          carried food-beverage type tax/meta). Type-switch chips keep the city.
 * v1.1.1 — Type match is taxonomy or _directory_type only (category-OR leaked
 *          gyms/hotels onto Food). Narrow chips require a mapped type. Hide
 *          all-directory sponsored fill on typed browse.
 * v1.1.0 — Type+city stay AND: city chips keep directory_type; Food & Beverage →
 *          Austin shows only that type (not the whole city). City-gate no longer
 *          asks “what are you looking for?”. Hide leftover Directorist chrome.
 * v1.0.11 — Browse search row: label out of the grid (visible SR text was shoving
 *          the field right and stretching Search). Button letter-spacing reset.
 * v1.0.10 — Public inventory: WhatsApp OR Travelpayouts OR (US location + official
 *          website). America may appear without WhatsApp until claim / add-listing.
 *          Mexico and other markets still need a real listing WhatsApp. Guest Message
 *          chips stay hidden until WA exists (front-compose / wa-verify).
 * v1.0.9 — Type doors match city chips: a listing counts in a type via taxonomy,
 *          _directory_type meta, *or* a category mapped to that type (not AND-meta
 *          which hid Mexico restaurants from Food while cities still showed them).
 *          Public inventory count is the same eligible SQL (WA or Travelpayouts).
 * v1.0.6 — post__in from eligible IDs + WHERE-detect listing queries; city alias
 *          matches long Google location names (13445 ≠ mx-roo-playa leaf).
 * v1.0.5 — Eligible public listing requires a listing WhatsApp (or Travelpayouts deal).
 * v1.0.4 — Archive listing WP_Query gets the same type×city×cat AND as facet SQL
 * (EXISTS on {$wpdb->posts}); numeric in_loc expands to alias city family when a
 * directory_type is set (13445 vs mx-roo-playa-del-carmen).
 *
 * Replaces archive duplicate bars from home-browse 2.2.x (compact bar + city-gate) with one chrome.
 * Leaves homepage #bds-home-browse alone.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_FACET_BROWSE_VER' ) ) {
	return;
}
define( 'BDS_FACET_BROWSE_VER', '1.1.4' );

add_action( 'init', 'bds_facet_browse_takeover', 25 );
add_action( 'pre_get_posts', 'bds_facet_browse_empty_listings_only', 9 );
add_filter( 'atbdp_listing_search_query_argument', 'bds_facet_browse_directorist_args', 6 );
add_filter( 'atbdp_listing_search_query_argument', 'bds_facet_browse_apply_and_filters', 25 );
add_filter( 'directorist_listings_query_arguments', 'bds_facet_browse_apply_and_filters', 25 );
add_filter( 'directorist_all_listings_query_arguments', 'bds_facet_browse_apply_and_filters', 25 );
add_filter( 'atbdp_all_listings_query_arguments', 'bds_facet_browse_apply_and_filters', 25 );
add_action( 'pre_get_posts', 'bds_facet_browse_apply_wp_query', 99 );
add_filter( 'posts_clauses', 'bds_facet_browse_posts_clauses', 5, 2 );
add_filter( 'posts_clauses', 'bds_facet_browse_posts_clauses', 99, 2 );
add_filter( 'the_posts', 'bds_facet_browse_the_posts', 20, 2 );
add_action( 'template_redirect', 'bds_facet_browse_canonical_loc', 3 );
add_action( 'template_redirect', 'bds_facet_browse_prevent_404', 0 );
add_filter( 'body_class', 'bds_facet_browse_body_class', 30 );
add_action( 'wp_footer', 'bds_facet_browse_render', 16 );
add_action( 'save_post_at_biz_dir', 'bds_facet_browse_bust', 31 );
add_action( 'save_post_atbdp_listing', 'bds_facet_browse_bust', 31 );
add_action( 'transition_post_status', 'bds_facet_browse_on_status', 21, 3 );
add_action( 'edited_at_biz_dir-category', 'bds_facet_browse_bust', 21 );
add_action( 'edited_at_biz_dir-location', 'bds_facet_browse_bust', 21 );
add_action( 'created_at_biz_dir-category', 'bds_facet_browse_bust', 21 );
add_action( 'created_at_biz_dir-location', 'bds_facet_browse_bust', 21 );
add_action( 'delete_at_biz_dir-location', 'bds_facet_browse_bust', 21 );
add_action( 'edited_atbdp_listing_types', 'bds_facet_browse_bust', 21 );

/**
 * Drop legacy duplicate archive city UI + the query that 404'd the page.
 *
 * @return void
 */
function bds_facet_browse_takeover() {
	if ( ! function_exists( 'bds_home_browse_render_compact_bar' ) ) {
		return;
	}
	remove_action( 'wp_footer', 'bds_home_browse_render_compact_bar', 17 );
	remove_action( 'pre_get_posts', 'bds_home_browse_empty_mixed_query', 8 );
	remove_filter( 'atbdp_listing_search_query_argument', 'bds_home_browse_directorist_args', 5 );
}

/**
 * @return void
 */
function bds_facet_browse_bust() {
	if ( defined( 'BDS_MC_IMPORT_RUNNING' ) && BDS_MC_IMPORT_RUNNING ) {
		$last = (int) get_transient( 'bds_fb_bust_throttle' );
		if ( $last && ( time() - $last ) < 180 ) {
			return;
		}
		set_transient( 'bds_fb_bust_throttle', time(), 10 * MINUTE_IN_SECONDS );
	}
	global $wpdb;
	if ( ! $wpdb ) {
		return;
	}
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bds_fb_%' OR option_name LIKE '_transient_timeout_bds_fb_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * @param string  $new_status New.
 * @param string  $old_status Old.
 * @param WP_Post $post Post.
 * @return void
 */
function bds_facet_browse_on_status( $new_status, $old_status, $post ) {
	if ( ! $post || ! in_array( $post->post_type, array( 'at_biz_dir', 'atbdp_listing' ), true ) ) {
		return;
	}
	if ( $new_status === $old_status ) {
		return;
	}
	bds_facet_browse_bust();
}

/**
 * @return bool
 */
function bds_facet_browse_is_archive() {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'bds_home_browse_is_archive' ) ) {
		return (bool) bds_home_browse_is_archive();
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( (string) parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ) : '';
	return (bool) preg_match( '#/(single-category|single-location|all-listings|search-result|search-results|all-categories|location)(/|$)#i', $path );
}

/**
 * @return bool
 */
function bds_facet_browse_needs_city_gate() {
	if ( bds_facet_browse_loc_term() instanceof WP_Term ) {
		return false;
	}
	if ( function_exists( 'bds_home_browse_needs_city_gate' ) ) {
		return (bool) bds_home_browse_needs_city_gate();
	}
	return false;
}

/**
 * @return bool
 */
function bds_facet_browse_has_city() {
	if ( function_exists( 'bds_home_browse_has_city_scope' ) ) {
		return (bool) bds_home_browse_has_city_scope();
	}
	return false;
}

/**
 * @return string
 */
function bds_facet_browse_po( $url ) {
	$url = (string) $url;
	if ( function_exists( 'bds_po_url' ) ) {
		return (string) bds_po_url( $url );
	}
	if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( (string) $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $ref !== '' && false === strpos( $url, 'ref=' ) ) {
			$url = add_query_arg( 'ref', $ref, $url );
		}
	}
	return $url;
}

/**
 * Canonical browse URL that never drops type / city / category.
 *
 * @param string $type_slug Type slug.
 * @param int    $loc_id    Location term ID.
 * @param int    $cat_id    Category term ID.
 * @param string $q         Search text.
 * @return string
 */
function bds_facet_browse_filter_url( $type_slug, $loc_id = 0, $cat_id = 0, $q = '' ) {
	$type_slug = sanitize_title( (string) $type_slug );
	$loc_id    = absint( $loc_id );
	$cat_id    = absint( $cat_id );
	$q         = is_string( $q ) ? trim( $q ) : '';
	$args      = array();
	if ( $type_slug !== '' ) {
		$args['directory_type'] = $type_slug;
	}
	if ( $loc_id > 0 ) {
		$args['in_loc'] = $loc_id;
	}
	if ( $cat_id > 0 ) {
		$args['in_cat'] = $cat_id;
	}
	if ( $q !== '' ) {
		$args['q'] = $q;
	}
	$base = home_url( '/all-listings/' );
	if ( $cat_id > 0 || $q !== '' ) {
		$base = home_url( '/search-result/' );
	}
	if ( empty( $args ) ) {
		return bds_facet_browse_po( $base );
	}
	return bds_facet_browse_po( add_query_arg( $args, $base ) );
}

/**
 * Active directory type slug from request (empty = all types).
 *
 * @return string
 */
function bds_facet_browse_type_slug() {
	if ( empty( $_GET['directory_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	return sanitize_title( wp_unslash( (string) $_GET['directory_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * @param string $text Name or slug.
 * @return string
 */
function bds_facet_browse_norm_city( $text ) {
	$text = strtolower( html_entity_decode( trim( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$text = preg_replace( '/[^a-z0-9]+/', ' ', $text );
	return trim( (string) $text );
}

/**
 * Canonical location slug from city-search aliases (Playa/PDC → mx-roo-playa-del-carmen).
 *
 * @param WP_Term $term Location term.
 * @return string
 */
function bds_facet_browse_loc_alias_slug( $term ) {
	if ( ! ( $term instanceof WP_Term ) || ! function_exists( 'bds_city_search_location_aliases' ) ) {
		return '';
	}
	$aliases = bds_city_search_location_aliases();
	$name    = bds_facet_browse_norm_city( $term->name );
	$slug    = strtolower( (string) $term->slug );
	$hay     = $name . ' ' . str_replace( '-', ' ', $slug );
	if ( isset( $aliases[ $name ] ) ) {
		return (string) $aliases[ $name ];
	}
	if ( isset( $aliases[ $slug ] ) ) {
		return (string) $aliases[ $slug ];
	}
	uksort(
		$aliases,
		static function ( $a, $b ) {
			return strlen( (string) $b ) <=> strlen( (string) $a );
		}
	);
	foreach ( $aliases as $alias => $canon ) {
		$alias = bds_facet_browse_norm_city( (string) $alias );
		if ( $alias === '' || strlen( $alias ) < 4 ) {
			continue;
		}
		if ( $name === $alias || $slug === $alias ) {
			return (string) $canon;
		}
		if ( preg_match( '/\b' . preg_quote( $alias, '/' ) . '\b/', $hay ) ) {
			return (string) $canon;
		}
		$words = array_filter( explode( ' ', $alias ) );
		if ( count( $words ) >= 2 ) {
			$all = true;
			foreach ( $words as $w ) {
				if ( strlen( $w ) < 3 || ! preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/', $hay ) ) {
					$all = false;
					break;
				}
			}
			if ( $all ) {
				return (string) $canon;
			}
		}
	}
	return '';
}

/**
 * Location term IDs for AND filters.
 * Untyped: requested term + children (do not merge city aliases — preserves city hubs).
 * Typed: also include alias canonical family so numeric duplicates like 13445 still hit food inventory.
 *
 * @param int    $loc_id    Location term ID.
 * @param string $type_slug Directory type slug.
 * @return int[]
 */
function bds_facet_browse_expand_loc_ids( $loc_id, $type_slug = '' ) {
	$loc_id = absint( $loc_id );
	if ( $loc_id < 1 ) {
		return array();
	}
	static $memo = array();
	$key         = $loc_id . '|' . $type_slug;
	if ( isset( $memo[ $key ] ) ) {
		return $memo[ $key ];
	}
	$ids  = array( $loc_id );
	$kids = get_term_children( $loc_id, 'at_biz_dir-location' );
	if ( ! is_wp_error( $kids ) && is_array( $kids ) ) {
		foreach ( $kids as $kid ) {
			$ids[] = absint( $kid );
		}
	}
	if ( $type_slug !== '' ) {
		$term = get_term( $loc_id, 'at_biz_dir-location' );
		if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
			$canon_slug = bds_facet_browse_loc_alias_slug( $term );
			if ( $canon_slug !== '' ) {
				$canon = get_term_by( 'slug', $canon_slug, 'at_biz_dir-location' );
				if ( $canon instanceof WP_Term ) {
					$ids[] = absint( $canon->term_id );
					$ckids = get_term_children( (int) $canon->term_id, 'at_biz_dir-location' );
					if ( ! is_wp_error( $ckids ) && is_array( $ckids ) ) {
						foreach ( $ckids as $kid ) {
							$ids[] = absint( $kid );
						}
					}
				}
			}
		}
	}
	$ids          = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	$memo[ $key ] = $ids;
	return $ids;
}

/**
 * Category term + descendants (Beauty must include Hair Salon listings).
 *
 * @param int $cat_id Category term ID.
 * @return int[]
 */
function bds_facet_browse_expand_cat_ids( $cat_id ) {
	$cat_id = absint( $cat_id );
	if ( $cat_id < 1 ) {
		return array();
	}
	static $memo = array();
	if ( isset( $memo[ $cat_id ] ) ) {
		return $memo[ $cat_id ];
	}
	$ids  = array( $cat_id );
	$kids = get_term_children( $cat_id, 'at_biz_dir-category' );
	if ( ! is_wp_error( $kids ) && is_array( $kids ) ) {
		foreach ( $kids as $kid ) {
			$ids[] = absint( $kid );
		}
	}
	$ids              = array_values( array_unique( array_filter( $ids ) ) );
	$memo[ $cat_id ] = $ids;
	return $ids;
}

/**
 * Category term IDs that belong on a directory type door (mapped slug + descendants).
 *
 * @param string $type_slug Type slug (food-beverage, hotels-stays, …).
 * @return int[]
 */
function bds_facet_browse_type_category_ids( $type_slug ) {
	$type_slug = sanitize_title( (string) $type_slug );
	if ( $type_slug === '' ) {
		return array();
	}
	static $memo = array();
	if ( isset( $memo[ $type_slug ] ) ) {
		return $memo[ $type_slug ];
	}
	$ids = array();
	$map = function_exists( 'bds_city_search_category_directory_map' ) ? bds_city_search_category_directory_map() : array();
	if ( ! is_array( $map ) ) {
		$map = array();
	}
	$slugs = array();
	foreach ( $map as $cat_slug => $door ) {
		if ( sanitize_title( (string) $door ) === $type_slug ) {
			$slugs[] = sanitize_title( (string) $cat_slug );
		}
	}
	// Only treat the type slug as a category when it is an explicit mapped cat
	// (hotels-stays). Auto-adding food-beverage as a parent pulled a junk tree
	// and leaked gyms / hotels / real estate onto the Food door.
	if ( isset( $map[ $type_slug ] ) && sanitize_title( (string) $map[ $type_slug ] ) === $type_slug ) {
		$slugs[] = $type_slug;
	}
	$slugs = array_values( array_unique( array_filter( $slugs ) ) );
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'at_biz_dir-category' );
		if ( ! ( $term instanceof WP_Term ) ) {
			continue;
		}
		foreach ( bds_facet_browse_expand_cat_ids( (int) $term->term_id ) as $cid ) {
			$cid = absint( $cid );
			if ( $cid > 0 ) {
				$ids[ $cid ] = true;
			}
		}
	}
	$memo[ $type_slug ] = array_map( 'absint', array_keys( $ids ) );
	return $memo[ $type_slug ];
}

/**
 * @return array<int,array{parent:int,slug:string,name:string}>
 */
function bds_facet_browse_cat_meta() {
	static $map = null;
	if ( is_array( $map ) ) {
		return $map;
	}
	$map   = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'at_biz_dir-category',
			'hide_empty' => false,
			'number'     => 0,
		)
	);
	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return $map;
	}
	foreach ( $terms as $term ) {
		if ( ! ( $term instanceof WP_Term ) ) {
			continue;
		}
		$map[ (int) $term->term_id ] = array(
			'parent' => (int) $term->parent,
			'slug'   => (string) $term->slug,
			'name'   => html_entity_decode( (string) $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}
	return $map;
}

/**
 * Walk to the root category.
 *
 * @param int   $term_id Term ID.
 * @param array $meta    Cat meta map.
 * @return int
 */
function bds_facet_browse_cat_root_id( $term_id, $meta ) {
	$cur   = absint( $term_id );
	$guard = 0;
	while ( $cur > 0 && $guard++ < 24 ) {
		$parent = isset( $meta[ $cur ]['parent'] ) ? (int) $meta[ $cur ]['parent'] : 0;
		if ( $parent < 1 ) {
			return $cur;
		}
		$cur = $parent;
	}
	return absint( $term_id );
}

/**
 * Direct child of $parent_id that contains $term_id.
 *
 * @param int   $term_id   Assigned term.
 * @param int   $parent_id Selected parent.
 * @param array $meta      Cat meta map.
 * @return int
 */
function bds_facet_browse_cat_child_bucket( $term_id, $parent_id, $meta ) {
	$term_id   = absint( $term_id );
	$parent_id = absint( $parent_id );
	if ( $term_id === $parent_id ) {
		return 0;
	}
	$cur   = $term_id;
	$guard = 0;
	while ( $cur > 0 && $guard++ < 24 ) {
		$parent = isset( $meta[ $cur ]['parent'] ) ? (int) $meta[ $cur ]['parent'] : 0;
		if ( $parent === $parent_id ) {
			return $cur;
		}
		if ( $parent < 1 ) {
			return 0;
		}
		$cur = $parent;
	}
	return 0;
}

/**
 * Prefer the location term that actually has inventory for the active type
 * (Directorist often has duplicate Playa / mx-roo-playa leaves).
 *
 * @param WP_Term $term Location term.
 * @return WP_Term
 */
function bds_facet_browse_prefer_inventory_loc( $term ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		return $term;
	}
	$type = bds_facet_browse_type_slug();
	if ( $type === '' ) {
		return $term;
	}
	$n = bds_facet_browse_result_count( $type, (int) $term->term_id, 0, '' );
	if ( $n > 0 ) {
		$canon_slug = bds_facet_browse_loc_alias_slug( $term );
		if ( $canon_slug !== '' && $term->slug !== $canon_slug ) {
			$canon = get_term_by( 'slug', $canon_slug, 'at_biz_dir-location' );
			if ( $canon instanceof WP_Term ) {
				$n_canon = bds_facet_browse_result_count( $type, (int) $canon->term_id, 0, '' );
				if ( $n_canon >= $n ) {
					return $canon;
				}
			}
		}
		return $term;
	}
	$cities = bds_facet_browse_city_facets( $type, 0, '' );
	$want   = bds_facet_browse_norm_city( $term->name );
	$slug   = strtolower( $term->slug );
	$alias  = bds_facet_browse_loc_alias_slug( $term );
	foreach ( $cities as $c ) {
		$label = bds_facet_browse_norm_city( (string) $c['label'] );
		$cslug = strtolower( (string) $c['slug'] );
		if ( $alias !== '' && $cslug === $alias ) {
			$alt = get_term( (int) $c['id'], 'at_biz_dir-location' );
			if ( $alt instanceof WP_Term && ! is_wp_error( $alt ) ) {
				return $alt;
			}
		}
		$overlap = ( $want !== '' && $label !== '' && ( false !== strpos( $label, $want ) || false !== strpos( $want, $label ) ) );
		if ( $label === $want || $cslug === $slug || $overlap || ( $slug !== '' && ( false !== strpos( $cslug, $slug ) || false !== strpos( $slug, $cslug ) ) ) ) {
			$alt = get_term( (int) $c['id'], 'at_biz_dir-location' );
			if ( $alt instanceof WP_Term && ! is_wp_error( $alt ) ) {
				return $alt;
			}
		}
	}
	if ( $alias !== '' ) {
		$alt = get_term_by( 'slug', $alias, 'at_biz_dir-location' );
		if ( $alt instanceof WP_Term ) {
			return $alt;
		}
	}
	return $term;
}

/**
 * Walk tax_query for a taxonomy name.
 *
 * @param mixed  $tax Tax query.
 * @param string $taxonomy Taxonomy.
 * @return bool
 */
function bds_facet_browse_tax_has( $tax, $taxonomy ) {
	if ( ! is_array( $tax ) ) {
		return false;
	}
	foreach ( $tax as $key => $clause ) {
		if ( 'relation' === $key ) {
			continue;
		}
		if ( is_array( $clause ) ) {
			if ( isset( $clause['taxonomy'] ) && $clause['taxonomy'] === $taxonomy ) {
				return true;
			}
			if ( bds_facet_browse_tax_has( $clause, $taxonomy ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Directorist / WP listing loop on a browse archive (not the Page document).
 *
 * @param WP_Query $query Query.
 * @return bool
 */
function bds_facet_browse_is_listing_query( $query ) {
	if ( ! ( $query instanceof WP_Query ) ) {
		return false;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	$skip = array( 'page', 'post', 'product', 'product_variation', 'nav_menu_item', 'revision', 'attachment', 'elementor_library', 'wp_template', 'wp_template_part' );
	$pt   = $query->get( 'post_type' );
	if ( is_string( $pt ) && $pt !== '' && $pt !== 'any' && in_array( $pt, $skip, true ) ) {
		return false;
	}
	if ( is_array( $pt ) && $pt && ! array_intersect( $pt, array( 'at_biz_dir', 'atbdp_listing' ) ) && array_intersect( $pt, $skip ) ) {
		return false;
	}
	$types = array( 'at_biz_dir', 'atbdp_listing' );
	if ( is_array( $pt ) && array_intersect( $pt, $types ) ) {
		return true;
	}
	if ( is_string( $pt ) && in_array( $pt, $types, true ) ) {
		return true;
	}
	if ( $query->get( 'atbdp_listings' ) || $query->get( 'atbdp_listing' ) || $query->get( 'bds_facet_and' ) ) {
		return true;
	}
	$tax = $query->get( 'tax_query' );
	if ( bds_facet_browse_tax_has( $tax, 'at_biz_dir-location' ) || bds_facet_browse_tax_has( $tax, 'at_biz_dir-category' ) || bds_facet_browse_tax_has( $tax, 'atbdp_listing_types' ) ) {
		return true;
	}
	// Directorist sometimes omits post_type on the archive listing WP_Query.
	if ( ( $pt === '' || $pt === 'any' || false === $pt ) && ! $query->is_main_query() ) {
		$ppp = (int) $query->get( 'posts_per_page' );
		if ( $ppp > 0 && $ppp <= 100 ) {
			return true;
		}
	}
	return false;
}

/**
 * @return WP_Term|null
 */
function bds_facet_browse_loc_term() {
	$resolved = null;
	if ( is_tax( 'at_biz_dir-location' ) ) {
		$t = get_queried_object();
		$resolved = ( $t instanceof WP_Term ) ? $t : null;
	}
	if ( ! $resolved ) {
		foreach ( array( 'in_loc', 'location' ) as $key ) {
			if ( empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				continue;
			}
			$raw = sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $raw === '' ) {
				continue;
			}
			if ( function_exists( 'bds_city_search_resolve_location' ) ) {
				$t = bds_city_search_resolve_location( $raw );
				if ( $t instanceof WP_Term ) {
					$resolved = $t;
					break;
				}
			}
			if ( ctype_digit( $raw ) ) {
				$t = get_term( (int) $raw, 'at_biz_dir-location' );
				if ( $t instanceof WP_Term && ! is_wp_error( $t ) ) {
					$resolved = $t;
					break;
				}
			}
			$t = get_term_by( 'slug', sanitize_title( $raw ), 'at_biz_dir-location' );
			if ( $t instanceof WP_Term ) {
				$resolved = $t;
				break;
			}
		}
	}
	if ( ! $resolved ) {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
		if ( preg_match( '#/single-location/([^/]+)/?#i', $path, $m ) ) {
			$t = get_term_by( 'slug', sanitize_title( $m[1] ), 'at_biz_dir-location' );
			$resolved = ( $t instanceof WP_Term ) ? $t : null;
		}
	}
	return $resolved ? bds_facet_browse_prefer_inventory_loc( $resolved ) : null;
}

/**
 * @return WP_Term|null
 */
function bds_facet_browse_cat_term() {
	if ( function_exists( 'bds_home_browse_gate_category_term' ) ) {
		$t = bds_home_browse_gate_category_term();
		if ( $t instanceof WP_Term ) {
			return $t;
		}
	}
	if ( is_tax( 'at_biz_dir-category' ) ) {
		$t = get_queried_object();
		return ( $t instanceof WP_Term ) ? $t : null;
	}
	return null;
}

/**
 * @return string
 */
function bds_facet_browse_q() {
	if ( empty( $_GET['q'] ) && empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$raw = ! empty( $_GET['q'] ) ? $_GET['q'] : $_GET['s']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return sanitize_text_field( wp_unslash( (string) $raw ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Public browse eligibility — same policy as wa-verify publish:
 * Travelpayouts deal, real listing WhatsApp, or US + official website (no WA yet).
 * Phone is not a substitute. BrandDad support digits are excluded.
 * Mexico / non-US still need WhatsApp to count here.
 *
 * @param string     $alias Posts table alias.
 * @param array|null $args  Prepare args (support LIKE placeholder when array).
 * @return string SQL fragment starting with AND (no user input).
 */
function bds_facet_browse_whatsapp_exists_sql( $alias, &$args = null ) {
	global $wpdb;
	$alias = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $alias );
	if ( $alias === '' ) {
		$alias = 'p';
	}
	$pm      = $wpdb->postmeta;
	$tr      = $wpdb->term_relationships;
	$tt      = $wpdb->term_taxonomy;
	$t       = $wpdb->terms;
	$support = '13124287008';
	$like    = is_array( $args ) ? '%s' : ( "'%" . $support . "%'" );
	if ( is_array( $args ) ) {
		$args[] = '%' . $wpdb->esc_like( $support ) . '%';
	}
	return " AND (
		EXISTS (
			SELECT 1 FROM {$pm} bds_pm_tp
			WHERE bds_pm_tp.post_id = {$alias}.ID AND bds_pm_tp.meta_key = '_bds_travelpayouts' AND bds_pm_tp.meta_value <> ''
		)
		OR EXISTS (
			SELECT 1 FROM {$pm} bds_pm_wa
			WHERE bds_pm_wa.post_id = {$alias}.ID
			  AND bds_pm_wa.meta_key IN ('_whatsapp','_bds_whatsapp','whatsapp')
			  AND bds_pm_wa.meta_value <> ''
			  AND bds_pm_wa.meta_value NOT LIKE {$like}
		)
		OR (
			EXISTS (
				SELECT 1 FROM {$pm} bds_pm_web
				WHERE bds_pm_web.post_id = {$alias}.ID
				  AND bds_pm_web.meta_key IN ('_website','website')
				  AND bds_pm_web.meta_value <> ''
				  AND (
					bds_pm_web.meta_value LIKE 'http://%'
					OR bds_pm_web.meta_value LIKE 'https://%'
				  )
			)
			AND (
				EXISTS (
					SELECT 1 FROM {$tr} bds_tr_us
					INNER JOIN {$tt} bds_tt_us ON bds_tt_us.term_taxonomy_id = bds_tr_us.term_taxonomy_id
						AND bds_tt_us.taxonomy IN ('at_biz_dir-location','location')
					INNER JOIN {$t} bds_t_us ON bds_t_us.term_id = bds_tt_us.term_id
					WHERE bds_tr_us.object_id = {$alias}.ID
					  AND (
						bds_t_us.slug LIKE 'us-%'
						OR bds_t_us.slug IN ('united-states','usa')
					  )
				)
				OR EXISTS (
					SELECT 1 FROM {$pm} bds_pm_addr
					WHERE bds_pm_addr.post_id = {$alias}.ID
					  AND bds_pm_addr.meta_key = '_address'
					  AND LOWER(bds_pm_addr.meta_value) LIKE '%united states%'
				)
			)
			AND (
				EXISTS (
					SELECT 1 FROM {$pm} bds_pm_th
					WHERE bds_pm_th.post_id = {$alias}.ID
					  AND bds_pm_th.meta_key = '_thumbnail_id'
					  AND bds_pm_th.meta_value+0 > 0
				)
				OR EXISTS (
					SELECT 1 FROM {$pm} bds_pm_prv
					WHERE bds_pm_prv.post_id = {$alias}.ID
					  AND bds_pm_prv.meta_key = '_listing_prv_img'
					  AND bds_pm_prv.meta_value <> ''
				)
			)
		)
	) ";
}

/**
 * Eligible public listing SQL pieces (published Directorist listings only).
 *
 * @return array{from:string,joins:string,where:string,args:array}
 */
function bds_facet_browse_eligible_join( $type_slug = '', $loc_id = 0, $cat_id = 0, $q = '' ) {
	global $wpdb;
	$types   = array( 'at_biz_dir', 'atbdp_listing' );
	$type_in = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	$from       = " FROM {$wpdb->posts} p ";
	$joins      = '';
	$join_args  = array();
	$where_args = $types;
	$where      = " WHERE p.post_status = 'publish' AND p.post_type IN ($type_in) "
		. bds_facet_browse_whatsapp_exists_sql( 'p', $where_args );

	if ( $type_slug !== '' ) {
		$type_cat_ids = bds_facet_browse_type_category_ids( $type_slug );
		if ( $type_cat_ids ) {
			$in     = implode( ',', array_fill( 0, count( $type_cat_ids ), '%d' ) );
			$joins .= " INNER JOIN {$wpdb->term_relationships} tr_typecat ON tr_typecat.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} tt_typecat ON tt_typecat.term_taxonomy_id = tr_typecat.term_taxonomy_id AND tt_typecat.taxonomy = 'at_biz_dir-category'
				AND tt_typecat.term_id IN ($in) ";
			foreach ( $type_cat_ids as $tcid ) {
				$join_args[] = $tcid;
			}
		} else {
			$type_term = get_term_by( 'slug', $type_slug, 'atbdp_listing_types' );
			$type_id   = ( $type_term instanceof WP_Term ) ? (int) $type_term->term_id : 0;
			$type_or   = array();
			$type_or[] = "EXISTS (
					SELECT 1 FROM {$wpdb->term_relationships} tr_type
					INNER JOIN {$wpdb->term_taxonomy} tt_type ON tt_type.term_taxonomy_id = tr_type.term_taxonomy_id AND tt_type.taxonomy = 'atbdp_listing_types'
					INNER JOIN {$wpdb->terms} t_type ON t_type.term_id = tt_type.term_id
					WHERE tr_type.object_id = p.ID AND t_type.slug = %s
				)";
			$where_args[] = $type_slug;
			if ( $type_id > 0 ) {
				$type_or[] = "EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} pm_type
						WHERE pm_type.post_id = p.ID AND pm_type.meta_key = '_directory_type'
						AND ( pm_type.meta_value = %s OR pm_type.meta_value = %s OR pm_type.meta_value = %d )
					)";
				$where_args[] = $type_slug;
				$where_args[] = (string) $type_id;
				$where_args[] = $type_id;
			}
			$where .= ' AND ( ' . implode( ' OR ', $type_or ) . ' ) ';
		}
	}

	if ( $loc_id > 0 ) {
		$loc_ids = bds_facet_browse_expand_loc_ids( (int) $loc_id, $type_slug );
		if ( ! $loc_ids ) {
			$loc_ids = array( (int) $loc_id );
		}
		$in     = implode( ',', array_fill( 0, count( $loc_ids ), '%d' ) );
		$joins .= " INNER JOIN {$wpdb->term_relationships} tr_loc ON tr_loc.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt_loc ON tt_loc.term_taxonomy_id = tr_loc.term_taxonomy_id AND tt_loc.taxonomy = 'at_biz_dir-location'
			AND tt_loc.term_id IN ($in) ";
		foreach ( $loc_ids as $lid ) {
			$join_args[] = $lid;
		}
	}

	if ( $cat_id > 0 ) {
		$cat_ids = bds_facet_browse_expand_cat_ids( (int) $cat_id );
		if ( ! $cat_ids ) {
			$cat_ids = array( (int) $cat_id );
		}
		$in     = implode( ',', array_fill( 0, count( $cat_ids ), '%d' ) );
		$joins .= " INNER JOIN {$wpdb->term_relationships} tr_cat ON tr_cat.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt_cat ON tt_cat.term_taxonomy_id = tr_cat.term_taxonomy_id AND tt_cat.taxonomy = 'at_biz_dir-category'
			AND tt_cat.term_id IN ($in) ";
		foreach ( $cat_ids as $cid ) {
			$join_args[] = $cid;
		}
	}

	if ( $q !== '' ) {
		$like   = '%' . $wpdb->esc_like( $q ) . '%';
		$where .= ' AND ( p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_excerpt LIKE %s ) ';
		$where_args[] = $like;
		$where_args[] = $like;
		$where_args[] = $like;
	}

	return array(
		'from'  => $from,
		'joins' => $joins,
		'where' => $where,
		'args'  => array_merge( $join_args, $where_args ),
	);
}

/**
 * City facets for current AND context (type + category + q). Zero-count cities omitted.
 *
 * @return array<int,array{id:int,slug:string,label:string,count:int,url:string}>
 */
function bds_facet_browse_city_facets( $type_slug = '', $cat_id = 0, $q = '' ) {
	$cache_key = 'bds_fb_cities_' . md5( BDS_FACET_BROWSE_VER . '|' . $type_slug . '|' . $cat_id . '|' . $q );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	if ( ! $wpdb ) {
		return array();
	}
	$join = bds_facet_browse_eligible_join( $type_slug, 0, $cat_id, $q );
	$sql  = "
		SELECT tl.term_id, tl.slug, tl.name, ttl.parent, COUNT(DISTINCT p.ID) AS cnt
		{$join['from']}
		{$join['joins']}
		INNER JOIN {$wpdb->term_relationships} trl ON trl.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} ttl ON ttl.term_taxonomy_id = trl.term_taxonomy_id AND ttl.taxonomy = 'at_biz_dir-location'
		INNER JOIN {$wpdb->terms} tl ON tl.term_id = ttl.term_id
		{$join['where']}
		GROUP BY tl.term_id, tl.slug, tl.name, ttl.parent
		HAVING cnt > 0
		ORDER BY cnt DESC, tl.name ASC
		LIMIT 48
	";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $join['args'] ), ARRAY_A );
	$out  = array();
	if ( ! is_array( $rows ) ) {
		set_transient( $cache_key, $out, 15 * MINUTE_IN_SECONDS );
		return $out;
	}
	$parent_ids = array();
	foreach ( $rows as $row ) {
		$parent_ids[ (int) $row['parent'] ] = true;
	}
	foreach ( $rows as $row ) {
		$id = (int) $row['term_id'];
		if ( isset( $parent_ids[ $id ] ) ) {
			continue; // drop country/state rollups
		}
		$out[] = array(
			'id'    => $id,
			'slug'  => (string) $row['slug'],
			'label' => html_entity_decode( (string) $row['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'count' => (int) $row['cnt'],
			'url'   => bds_facet_browse_filter_url( $type_slug, $id, $cat_id, $q ),
		);
	}
	set_transient( $cache_key, $out, 15 * MINUTE_IN_SECONDS );
	return $out;
}

/**
 * Category / child-service facets for current AND context. Zero omitted.
 *
 * @return array<int,array{id:int,slug:string,label:string,count:int,url:string,parent:int}>
 */
function bds_facet_browse_cat_facets( $type_slug = '', $loc_id = 0, $parent_cat_id = 0, $q = '' ) {
	$cache_key = 'bds_fb_cats_' . md5( BDS_FACET_BROWSE_VER . '|' . $type_slug . '|' . $loc_id . '|' . $parent_cat_id . '|' . $q );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	if ( ! $wpdb ) {
		return array();
	}
	$meta = bds_facet_browse_cat_meta();
	$join = bds_facet_browse_eligible_join( $type_slug, $loc_id, 0, $q );
	$sql  = "
		SELECT p.ID AS listing_id, ttc.term_id
		{$join['from']}
		{$join['joins']}
		INNER JOIN {$wpdb->term_relationships} trc ON trc.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} ttc ON ttc.term_taxonomy_id = trc.term_taxonomy_id AND ttc.taxonomy = 'at_biz_dir-category'
		{$join['where']}
	";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $join['args'] ), ARRAY_A );
	$sets = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$lid = (int) $row['listing_id'];
			$tid = (int) $row['term_id'];
			if ( $lid < 1 || $tid < 1 ) {
				continue;
			}
			if ( $parent_cat_id > 0 ) {
				$bucket = bds_facet_browse_cat_child_bucket( $tid, (int) $parent_cat_id, $meta );
			} else {
				$bucket = bds_facet_browse_cat_root_id( $tid, $meta );
			}
			if ( $bucket < 1 ) {
				continue;
			}
			if ( ! isset( $sets[ $bucket ] ) ) {
				$sets[ $bucket ] = array();
			}
			$sets[ $bucket ][ $lid ] = true;
		}
	}
	$out  = array();
	foreach ( $sets as $id => $lids ) {
		$id    = (int) $id;
		$count = count( $lids );
		if ( $count < 1 || ! isset( $meta[ $id ] ) ) {
			continue;
		}
		if ( $type_slug !== '' && function_exists( 'bds_city_search_default_directory_type' ) ) {
			$term = get_term( $id, 'at_biz_dir-category' );
			if ( $term instanceof WP_Term ) {
				$mapped = bds_city_search_default_directory_type( $term );
				if ( $mapped !== $type_slug ) {
					continue;
				}
			}
		}
		$out[] = array(
			'id'     => $id,
			'slug'   => (string) $meta[ $id ]['slug'],
			'label'  => (string) $meta[ $id ]['name'],
			'count'  => $count,
			'parent' => (int) $meta[ $id ]['parent'],
			'url'    => bds_facet_browse_filter_url( $type_slug, $loc_id, $id, $q ),
		);
	}
	usort(
		$out,
		static function ( $a, $b ) {
			if ( $a['count'] === $b['count'] ) {
				return strcasecmp( $a['label'], $b['label'] );
			}
			return $b['count'] <=> $a['count'];
		}
	);
	set_transient( $cache_key, $out, 15 * MINUTE_IN_SECONDS );
	return $out;
}

/**
 * Exact eligible listing count for current filters (must match result set).
 *
 * @return int
 */
function bds_facet_browse_result_count( $type_slug = '', $loc_id = 0, $cat_id = 0, $q = '' ) {
	$cache_key = 'bds_fb_n_' . md5( BDS_FACET_BROWSE_VER . '|' . $type_slug . '|' . $loc_id . '|' . $cat_id . '|' . $q );
	$cached    = get_transient( $cache_key );
	if ( is_numeric( $cached ) ) {
		return (int) $cached;
	}
	global $wpdb;
	if ( ! $wpdb ) {
		return 0;
	}
	$join = bds_facet_browse_eligible_join( $type_slug, $loc_id, $cat_id, $q );
	$sql  = 'SELECT COUNT(DISTINCT p.ID) ' . $join['from'] . $join['joins'] . $join['where'];
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$n = (int) $wpdb->get_var( $wpdb->prepare( $sql, $join['args'] ) );
	set_transient( $cache_key, $n, 15 * MINUTE_IN_SECONDS );
	return $n;
}

/**
 * Unique public listings (published + WhatsApp or Travelpayouts). Same number the
 * homepage Ask line, type doors (untyped), and city chips must share.
 *
 * @return int
 */
function bds_facet_browse_public_inventory_count() {
	return (int) bds_facet_browse_result_count( '', 0, 0, '' );
}

/**
 * Eligible listing IDs for the current AND (capped). Used as post__in so Directorist
 * cannot render a different set than the facet count.
 *
 * @return int[]
 */
function bds_facet_browse_eligible_ids( $type_slug = '', $loc_id = 0, $cat_id = 0, $q = '' ) {
	$cache_key = 'bds_fb_ids_' . md5( BDS_FACET_BROWSE_VER . '|' . $type_slug . '|' . $loc_id . '|' . $cat_id . '|' . $q );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return array_map( 'absint', $cached );
	}
	global $wpdb;
	if ( ! $wpdb ) {
		return array();
	}
	$join = bds_facet_browse_eligible_join( $type_slug, $loc_id, $cat_id, $q );
	$sql  = 'SELECT DISTINCT p.ID ' . $join['from'] . $join['joins'] . $join['where'] . ' ORDER BY p.post_date DESC LIMIT 2000';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$ids = $wpdb->get_col( $wpdb->prepare( $sql, $join['args'] ) );
	$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
	set_transient( $cache_key, $ids, 15 * MINUTE_IN_SECONDS );
	return $ids;
}

/**
 * @return bool
 */
function bds_facet_browse_sql_looks_like_listings( $clauses ) {
	if ( ! is_array( $clauses ) ) {
		return false;
	}
	$blob = ( isset( $clauses['where'] ) ? (string) $clauses['where'] : '' ) . ' ' . ( isset( $clauses['join'] ) ? (string) $clauses['join'] : '' );
	return (bool) preg_match( '/post_type[^A-Za-z0-9_].{0,80}(at_biz_dir|atbdp_listing)/i', $blob );
}

/**
 * Hide worldwide listing dumps when city is required — but NEVER 404 the page document.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function bds_facet_browse_empty_listings_only( $query ) {
	if ( ! $query instanceof WP_Query || is_admin() || ! bds_facet_browse_needs_city_gate() ) {
		return;
	}
	// Critical fix: do not empty the main Page query (that produced "Oops! page can't be found").
	if ( $query->is_main_query() ) {
		$pt = $query->get( 'post_type' );
		if ( $pt === '' || $pt === 'page' || $pt === 'any' ) {
			return;
		}
		if ( is_array( $pt ) && ! array_intersect( $pt, array( 'at_biz_dir', 'atbdp_listing' ) ) ) {
			return;
		}
		if ( is_string( $pt ) && ! in_array( $pt, array( 'at_biz_dir', 'atbdp_listing' ), true ) ) {
			return;
		}
	}
	$types      = array( 'at_biz_dir', 'atbdp_listing' );
	$pt         = $query->get( 'post_type' );
	$is_listing = false;
	if ( is_array( $pt ) ) {
		$is_listing = (bool) array_intersect( $pt, $types );
	} elseif ( is_string( $pt ) && $pt !== '' ) {
		$is_listing = in_array( $pt, $types, true );
	}
	if ( ! $is_listing && ! ( $query->is_main_query() && ( $query->is_tax( 'at_biz_dir-category' ) || $query->is_tax( 'at_biz_dir-location' ) ) ) ) {
		return;
	}
	$query->set( 'bds_city_gate', 1 );
	$query->set( 'bds_facet_gate', 1 );
	$query->set( 'post__in', array( 0 ) );
	$query->set( 'posts_per_page', 1 );
	$query->set( 'no_found_rows', true );
}

/**
 * @param array $args Directorist args.
 * @return array
 */
function bds_facet_browse_directorist_args( $args ) {
	if ( ! is_array( $args ) || ! bds_facet_browse_needs_city_gate() ) {
		return $args;
	}
	$args['post__in']       = array( 0 );
	$args['posts_per_page'] = 1;
	$args['bds_facet_gate'] = 1;
	return $args;
}

/**
 * Force AND tax filters on Directorist listing queries (type + city + category).
 *
 * @param array $args Query args.
 * @return array
 */
function bds_facet_browse_apply_and_filters( $args ) {
	if ( ! is_array( $args ) || ! bds_facet_browse_is_archive() || bds_facet_browse_needs_city_gate() ) {
		return $args;
	}
	$type = bds_facet_browse_type_slug();
	$loc  = bds_facet_browse_loc_term();
	$cat  = bds_facet_browse_cat_term();
	$tax  = isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ? $args['tax_query'] : array();
	if ( ! isset( $tax['relation'] ) ) {
		$tax['relation'] = 'AND';
	}
	if ( $type !== '' && taxonomy_exists( 'atbdp_listing_types' ) ) {
		$tax[] = array(
			'taxonomy' => 'atbdp_listing_types',
			'field'    => 'slug',
			'terms'    => array( $type ),
		);
		$args['directory_type'] = $type;
	}
	if ( $loc instanceof WP_Term ) {
		$terms = bds_facet_browse_expand_loc_ids( (int) $loc->term_id, $type );
		if ( ! $terms ) {
			$terms = array( (int) $loc->term_id );
		}
		$tax[] = array(
			'taxonomy'         => 'at_biz_dir-location',
			'field'            => 'term_id',
			'terms'            => $terms,
			'include_children' => true,
			'operator'         => 'IN',
		);
		$args['in_loc'] = (string) (int) $loc->term_id;
	}
	if ( $cat instanceof WP_Term ) {
		$tax[] = array(
			'taxonomy'         => 'at_biz_dir-category',
			'field'            => 'term_id',
			'terms'            => array( (int) $cat->term_id ),
			'include_children' => true,
		);
		$args['in_cat'] = (string) (int) $cat->term_id;
	}
	$q = bds_facet_browse_q();
	if ( $q !== '' ) {
		$args['s'] = $q;
	}
	$args['tax_query']      = $tax;
	$args['bds_facet_and']  = 1;
	return $args;
}

/**
 * Mirror AND filters onto WP_Query listing loops on browse archives.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function bds_facet_browse_apply_wp_query( $query ) {
	if ( ! $query instanceof WP_Query || is_admin() || ! bds_facet_browse_is_archive() || bds_facet_browse_needs_city_gate() ) {
		return;
	}
	if ( ! bds_facet_browse_is_listing_query( $query ) ) {
		$pt = $query->get( 'post_type' );
		$skip = array( 'page', 'post', 'product', 'product_variation', 'nav_menu_item', 'revision', 'attachment', 'elementor_library' );
		if ( $query->is_main_query() ) {
			return;
		}
		if ( is_string( $pt ) && $pt !== '' && $pt !== 'any' && in_array( $pt, $skip, true ) ) {
			return;
		}
		if ( is_array( $pt ) && $pt && ! array_intersect( $pt, array( 'at_biz_dir', 'atbdp_listing' ) ) ) {
			return;
		}
	}
	$patched = bds_facet_browse_apply_and_filters(
		array(
			'tax_query' => $query->get( 'tax_query' ),
			's'         => $query->get( 's' ),
		)
	);
	if ( ! empty( $patched['tax_query'] ) ) {
		$query->set( 'tax_query', $patched['tax_query'] );
	}
	if ( ! empty( $patched['s'] ) ) {
		$query->set( 's', $patched['s'] );
	}
	$type   = bds_facet_browse_type_slug();
	$loc    = bds_facet_browse_loc_term();
	$cat    = bds_facet_browse_cat_term();
	$q      = bds_facet_browse_q();
	$loc_id = ( $loc instanceof WP_Term ) ? (int) $loc->term_id : 0;
	$cat_id = ( $cat instanceof WP_Term ) ? (int) $cat->term_id : 0;
	if ( $type !== '' || $loc_id > 0 || $cat_id > 0 || $q !== '' ) {
		$ids = bds_facet_browse_eligible_ids( $type, $loc_id, $cat_id, $q );
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		$query->set( 'post_type', array( 'at_biz_dir', 'atbdp_listing' ) );
	}
	$query->set( 'bds_facet_and', 1 );
}

/**
 * Same AND as facet SQL on the query that actually renders cards.
 *
 * @param array    $clauses Clauses.
 * @param WP_Query $query   Query.
 * @return array
 */
function bds_facet_browse_posts_clauses( $clauses, $query ) {
	if ( ! is_array( $clauses ) || ! ( $query instanceof WP_Query ) ) {
		return $clauses;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $clauses;
	}
	$browse = bds_facet_browse_is_archive();
	if ( ! $browse && wp_doing_ajax() ) {
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';
		$path = strtolower( (string) parse_url( $ref, PHP_URL_PATH ) );
		$browse = (bool) preg_match( '#/(single-category|single-location|all-listings|search-result|search-results|all-categories|location)(/|$)#i', $path );
	}
	if ( ! $browse || bds_facet_browse_needs_city_gate() ) {
		return $clauses;
	}
	if ( $query->get( 'bds_facet_sql' ) ) {
		return $clauses;
	}
	if ( ! bds_facet_browse_is_listing_query( $query ) && ! bds_facet_browse_sql_looks_like_listings( $clauses ) ) {
		return $clauses;
	}
	$type = bds_facet_browse_type_slug();
	$loc  = bds_facet_browse_loc_term();
	$cat  = bds_facet_browse_cat_term();
	$q    = bds_facet_browse_q();
	global $wpdb;
	$alias = $wpdb->posts;
	$extra = bds_facet_browse_whatsapp_exists_sql( $alias );

	if ( $type !== '' ) {
		$type_cat_ids = bds_facet_browse_type_category_ids( $type );
		if ( $type_cat_ids ) {
			$in     = implode( ',', array_fill( 0, count( $type_cat_ids ), '%d' ) );
			$sql    = " AND EXISTS (
				SELECT 1 FROM {$wpdb->term_relationships} bds_tr_typecat
				INNER JOIN {$wpdb->term_taxonomy} bds_tt_typecat ON bds_tt_typecat.term_taxonomy_id = bds_tr_typecat.term_taxonomy_id AND bds_tt_typecat.taxonomy = 'at_biz_dir-category'
				AND bds_tt_typecat.term_id IN ($in)
				WHERE bds_tr_typecat.object_id = {$alias}.ID
			) ";
			$extra .= $wpdb->prepare( $sql, ...$type_cat_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$type_term = get_term_by( 'slug', $type, 'atbdp_listing_types' );
			$type_id   = ( $type_term instanceof WP_Term ) ? (int) $type_term->term_id : 0;
			$type_or   = array();
			$type_or[] = $wpdb->prepare(
				"EXISTS (
					SELECT 1 FROM {$wpdb->term_relationships} bds_tr_type
					INNER JOIN {$wpdb->term_taxonomy} bds_tt_type ON bds_tt_type.term_taxonomy_id = bds_tr_type.term_taxonomy_id AND bds_tt_type.taxonomy = 'atbdp_listing_types'
					INNER JOIN {$wpdb->terms} bds_t_type ON bds_t_type.term_id = bds_tt_type.term_id
					WHERE bds_tr_type.object_id = {$alias}.ID AND bds_t_type.slug = %s
				)",
				$type
			);
			if ( $type_id > 0 ) {
				$type_or[] = $wpdb->prepare(
					"EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} bds_pm_type
						WHERE bds_pm_type.post_id = {$alias}.ID AND bds_pm_type.meta_key = '_directory_type'
						AND ( bds_pm_type.meta_value = %s OR bds_pm_type.meta_value = %s OR bds_pm_type.meta_value = %d )
					)",
					$type,
					(string) $type_id,
					$type_id
				);
			}
			$extra .= ' AND ( ' . implode( ' OR ', $type_or ) . ' ) ';
		}
	}

	if ( $loc instanceof WP_Term ) {
		$loc_ids = bds_facet_browse_expand_loc_ids( (int) $loc->term_id, $type );
		if ( $loc_ids ) {
			$in     = implode( ',', array_fill( 0, count( $loc_ids ), '%d' ) );
			$sql    = " AND EXISTS (
				SELECT 1 FROM {$wpdb->term_relationships} bds_tr_loc
				INNER JOIN {$wpdb->term_taxonomy} bds_tt_loc ON bds_tt_loc.term_taxonomy_id = bds_tr_loc.term_taxonomy_id AND bds_tt_loc.taxonomy = 'at_biz_dir-location'
				AND bds_tt_loc.term_id IN ($in)
				WHERE bds_tr_loc.object_id = {$alias}.ID
			) ";
			$extra .= $wpdb->prepare( $sql, ...$loc_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	if ( $cat instanceof WP_Term ) {
		$cat_id = absint( $cat->term_id );
		$cat_ids = array( $cat_id );
		$ckids   = get_term_children( $cat_id, 'at_biz_dir-category' );
		if ( ! is_wp_error( $ckids ) && is_array( $ckids ) ) {
			foreach ( $ckids as $kid ) {
				$cat_ids[] = absint( $kid );
			}
		}
		$cat_ids = array_values( array_unique( array_filter( $cat_ids ) ) );
		$in      = implode( ',', array_fill( 0, count( $cat_ids ), '%d' ) );
		$sql     = " AND EXISTS (
			SELECT 1 FROM {$wpdb->term_relationships} bds_tr_cat
			INNER JOIN {$wpdb->term_taxonomy} bds_tt_cat ON bds_tt_cat.term_taxonomy_id = bds_tr_cat.term_taxonomy_id AND bds_tt_cat.taxonomy = 'at_biz_dir-category'
			AND bds_tt_cat.term_id IN ($in)
			WHERE bds_tr_cat.object_id = {$alias}.ID
		) ";
		$extra .= $wpdb->prepare( $sql, ...$cat_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	if ( $q !== '' ) {
		$like   = '%' . $wpdb->esc_like( $q ) . '%';
		$extra .= $wpdb->prepare(
			" AND ( {$alias}.post_title LIKE %s OR {$alias}.post_content LIKE %s OR {$alias}.post_excerpt LIKE %s ) ",
			$like,
			$like,
			$like
		);
	}

	if ( $extra !== '' ) {
		$clauses['where'] .= $extra;
		$query->set( 'bds_facet_sql', 1 );
	}
	return $clauses;
}

/**
 * Last-resort AND: if Directorist still returned ineligible cards, replace with eligible IDs.
 *
 * @param WP_Post[] $posts Posts.
 * @param WP_Query  $query Query.
 * @return WP_Post[]
 */
function bds_facet_browse_the_posts( $posts, $query ) {
	if ( ! is_array( $posts ) || ! ( $query instanceof WP_Query ) ) {
		return $posts;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $posts;
	}
	if ( ! bds_facet_browse_is_archive() || bds_facet_browse_needs_city_gate() ) {
		return $posts;
	}
	if ( $query->get( 'bds_facet_posts_done' ) ) {
		return $posts;
	}
	$has_listing = false;
	foreach ( $posts as $p ) {
		if ( $p instanceof WP_Post && in_array( $p->post_type, array( 'at_biz_dir', 'atbdp_listing' ), true ) ) {
			$has_listing = true;
			break;
		}
	}
	if ( ! $has_listing && ! bds_facet_browse_is_listing_query( $query ) ) {
		return $posts;
	}
	$type   = bds_facet_browse_type_slug();
	$loc    = bds_facet_browse_loc_term();
	$cat    = bds_facet_browse_cat_term();
	$q      = bds_facet_browse_q();
	$loc_id = ( $loc instanceof WP_Term ) ? (int) $loc->term_id : 0;
	$cat_id = ( $cat instanceof WP_Term ) ? (int) $cat->term_id : 0;
	if ( $type === '' && $loc_id < 1 && $cat_id < 1 && $q === '' ) {
		return $posts;
	}
	$ids = bds_facet_browse_eligible_ids( $type, $loc_id, $cat_id, $q );
	$ok  = array_fill_keys( $ids, true );
	$keep = array();
	foreach ( $posts as $p ) {
		if ( $p instanceof WP_Post && isset( $ok[ (int) $p->ID ] ) ) {
			$keep[] = $p;
		}
	}
	$n   = count( $ids );
	$ppp = (int) $query->get( 'posts_per_page' );
	if ( $ppp < 1 ) {
		$ppp = 12;
	}
	$paged = (int) $query->get( 'paged' );
	if ( $paged < 1 ) {
		$paged = max( 1, (int) get_query_var( 'paged' ) );
	}
	if ( count( $keep ) !== count( $posts ) || ( $n > 0 && ! $keep ) ) {
		$slice = array_slice( $ids, ( $paged - 1 ) * $ppp, $ppp );
		if ( $slice ) {
			$query->set( 'bds_facet_posts_done', 1 );
			$keep = get_posts(
				array(
					'post_type'              => array( 'at_biz_dir', 'atbdp_listing' ),
					'post_status'            => 'publish',
					'post__in'               => $slice,
					'orderby'                => 'post__in',
					'posts_per_page'         => count( $slice ),
					'suppress_filters'       => true,
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
		} else {
			$keep = array();
		}
	}
	$query->found_posts   = $n;
	$query->max_num_pages = $ppp > 0 ? (int) ceil( $n / $ppp ) : 0;
	return $keep;
}

/**
 * When type is set and numeric in_loc is a duplicate city with 0 type-inventory, hop to the alias leaf.
 * Untyped city hubs are not redirected.
 *
 * @return void
 */
function bds_facet_browse_canonical_loc() {
	if ( is_admin() || ! bds_facet_browse_is_archive() || bds_facet_browse_needs_city_gate() ) {
		return;
	}
	if ( bds_facet_browse_type_slug() === '' ) {
		return;
	}
	if ( empty( $_GET['in_loc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$raw = sanitize_text_field( wp_unslash( (string) $_GET['in_loc'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! ctype_digit( $raw ) ) {
		return;
	}
	$loc = bds_facet_browse_loc_term();
	if ( ! ( $loc instanceof WP_Term ) ) {
		return;
	}
	$req_id = absint( $raw );
	if ( $req_id < 1 || (int) $loc->term_id === $req_id ) {
		return;
	}
	$target = add_query_arg( 'in_loc', (string) (int) $loc->term_id );
	$target = bds_facet_browse_po( $target );
	if ( $target === '' ) {
		return;
	}
	nocache_headers();
	wp_safe_redirect( $target, 302 );
	exit;
}

/**
 * City-gate must never surface as HTTP 404.
 *
 * @return void
 */
function bds_facet_browse_prevent_404() {
	if ( ! bds_facet_browse_needs_city_gate() ) {
		return;
	}
	global $wp_query;
	if ( ! $wp_query || ! $wp_query->is_404() ) {
		return;
	}
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	status_header( 200 );
	nocache_headers();
}

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function bds_facet_browse_body_class( $classes ) {
	if ( ! bds_facet_browse_is_archive() ) {
		return $classes;
	}
	$classes[] = 'bds-facet-browse';
	if ( bds_facet_browse_needs_city_gate() ) {
		$classes[] = 'bds-city-gate';
		$classes[] = 'bds-facet-gate';
	}
	if ( bds_facet_browse_has_city() ) {
		$classes[] = 'bds-facet-has-city';
	}
	if ( bds_facet_browse_type_slug() !== '' ) {
		$classes[] = 'bds-facet-has-type';
	}
	return $classes;
}

/**
 * Build clear URL removing one filter key.
 *
 * @param string               $remove Key to remove: type|city|cat|q|all.
 * @param array<string,string> $state  Current state.
 * @return string
 */
function bds_facet_browse_clear_url( $remove, $state ) {
	if ( $remove === 'all' ) {
		return bds_facet_browse_filter_url( '', 0, 0, '' );
	}
	return bds_facet_browse_filter_url(
		( $remove !== 'type' ) ? (string) $state['type'] : '',
		( $remove !== 'city' ) ? (int) $state['loc_id'] : 0,
		( $remove !== 'cat' ) ? (int) $state['cat_id'] : 0,
		( $remove !== 'q' ) ? (string) $state['q'] : ''
	);
}

/**
 * @return void
 */
function bds_facet_browse_render() {
	if ( ! bds_facet_browse_is_archive() ) {
		return;
	}
	$type = bds_facet_browse_type_slug();
	$loc  = bds_facet_browse_loc_term();
	$cat  = bds_facet_browse_cat_term();
	$q    = bds_facet_browse_q();
	$loc_id = ( $loc instanceof WP_Term ) ? (int) $loc->term_id : 0;
	$cat_id = ( $cat instanceof WP_Term ) ? (int) $cat->term_id : 0;
	$gate   = bds_facet_browse_needs_city_gate();
	$digital = function_exists( 'bds_home_browse_is_digital_category' ) && bds_home_browse_is_digital_category();

	$state = array(
		'type'   => $type,
		'loc_id' => $loc_id,
		'cat_id' => $cat_id,
		'q'      => $q,
	);

	$result_n = bds_facet_browse_result_count( $type, $loc_id, $cat_id, $q );
	$elig_ids = ( $type !== '' || $loc_id > 0 || $cat_id > 0 || $q !== '' )
		? bds_facet_browse_eligible_ids( $type, $loc_id, $cat_id, $q )
		: array();
	$cities   = array();
	$cats     = array();
	$services = array();

	if ( $gate || $loc_id < 1 ) {
		if ( ! $digital ) {
			$cities = bds_facet_browse_city_facets( $type, $cat_id, $q );
		}
	}
	// City-gate after a type door: only ask for a city. Do not re-ask
	// “what are you looking for?” with every other category.
	if ( ! $gate && ( $type !== '' || $loc_id > 0 || $digital || $cat_id > 0 ) ) {
		$cats = bds_facet_browse_cat_facets( $type, $loc_id, 0, $q );
	}
	if ( $cat_id > 0 ) {
		$services = bds_facet_browse_cat_facets( $type, $loc_id, $cat_id, $q );
	}

	$type_label = '';
	if ( $type !== '' ) {
		$tt = get_term_by( 'slug', $type, 'atbdp_listing_types' );
		if ( $tt instanceof WP_Term ) {
			$type_label = preg_replace( '/\s+directory$/i', '', html_entity_decode( $tt->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		} else {
			$type_label = ucwords( str_replace( '-', ' ', $type ) );
		}
	}

	$heading = 'Browse businesses';
	if ( $loc instanceof WP_Term && $type_label !== '' ) {
		$heading = $type_label . ' in ' . $loc->name;
	} elseif ( $loc instanceof WP_Term && $cat instanceof WP_Term ) {
		$heading = $cat->name . ' in ' . $loc->name;
	} elseif ( $loc instanceof WP_Term ) {
		$heading = 'Businesses in ' . $loc->name;
	} elseif ( $cat instanceof WP_Term ) {
		$heading = $cat->name . ' — pick a city';
	} elseif ( $type_label !== '' ) {
		$heading = $type_label . ' — pick a city';
	}

	$search_action = bds_facet_browse_po( home_url( '/search-result/' ) );
	ob_start();
	?>
<section id="bds-facet-browse" class="bds-facet" data-bds-facet="<?php echo esc_attr( BDS_FACET_BROWSE_VER ); ?>" data-bds-n="<?php echo esc_attr( (string) (int) $result_n ); ?>" data-bds-ids="<?php echo esc_attr( implode( ',', array_map( 'strval', array_slice( $elig_ids, 0, 400 ) ) ) ); ?>" aria-label="Browse listings">
	<div class="bds-facet__inner">
		<p class="bds-facet__eyebrow">Browse listings</p>
		<h1 class="bds-facet__title"><?php echo esc_html( $heading ); ?></h1>
		<?php if ( $gate ) : ?>
		<p class="bds-facet__hint">Choose a city first. Counts show real published businesses you can open next<?php echo $type_label !== '' ? ' in ' . esc_html( $type_label ) : ''; ?>.</p>
		<?php elseif ( $loc_id > 0 ) : ?>
		<p class="bds-facet__hint"><?php echo esc_html( (string) $result_n ); ?> matching <?php echo esc_html( $result_n === 1 ? 'business' : 'businesses' ); ?> with your filters.</p>
		<?php endif; ?>

		<?php if ( $type !== '' || $loc_id > 0 || $cat_id > 0 || $q !== '' ) : ?>
		<div class="bds-facet__active" aria-label="Active filters">
			<?php if ( $type_label !== '' ) : ?>
			<a class="bds-facet__chip is-on" href="<?php echo esc_url( bds_facet_browse_clear_url( 'type', $state ) ); ?>"><?php echo esc_html( $type_label ); ?> <span aria-hidden="true">×</span></a>
			<?php endif; ?>
			<?php if ( $loc instanceof WP_Term ) : ?>
			<a class="bds-facet__chip is-on" href="<?php echo esc_url( bds_facet_browse_clear_url( 'city', $state ) ); ?>"><?php echo esc_html( $loc->name ); ?> <span aria-hidden="true">×</span></a>
			<?php endif; ?>
			<?php if ( $cat instanceof WP_Term ) : ?>
			<a class="bds-facet__chip is-on" href="<?php echo esc_url( bds_facet_browse_clear_url( 'cat', $state ) ); ?>"><?php echo esc_html( $cat->name ); ?> <span aria-hidden="true">×</span></a>
			<?php endif; ?>
			<?php if ( $q !== '' ) : ?>
			<a class="bds-facet__chip is-on" href="<?php echo esc_url( bds_facet_browse_clear_url( 'q', $state ) ); ?>">“<?php echo esc_html( $q ); ?>” <span aria-hidden="true">×</span></a>
			<?php endif; ?>
			<a class="bds-facet__clear" href="<?php echo esc_url( bds_facet_browse_clear_url( 'all', $state ) ); ?>">Clear all</a>
		</div>
		<?php endif; ?>

		<form class="bds-facet__search" method="get" action="<?php echo esc_url( $search_action ); ?>" role="search">
			<label class="screen-reader-text" for="bds-facet-q">Search businesses, categories or services</label>
			<input id="bds-facet-q" type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search businesses, categories or services" autocomplete="off" aria-label="Search businesses, categories or services" />
			<?php if ( $type !== '' ) : ?><input type="hidden" name="directory_type" value="<?php echo esc_attr( $type ); ?>" /><?php endif; ?>
			<?php if ( $loc_id > 0 ) : ?><input type="hidden" name="in_loc" value="<?php echo esc_attr( (string) $loc_id ); ?>" /><?php endif; ?>
			<?php if ( $cat_id > 0 ) : ?><input type="hidden" name="in_cat" value="<?php echo esc_attr( (string) $cat_id ); ?>" /><?php endif; ?>
			<?php if ( ! empty( $_GET['ref'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<input type="hidden" name="ref" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( (string) $_GET['ref'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
			<?php endif; ?>
			<button type="submit">Search</button>
		</form>

		<?php if ( $cities ) : ?>
		<div class="bds-facet__block">
			<p class="bds-facet__label"><?php echo $type_label !== '' ? esc_html( $type_label . ' available in' ) : ( $cat instanceof WP_Term ? esc_html( $cat->name . ' available in' ) : 'Available locations' ); ?></p>
			<div class="bds-facet__row">
				<?php foreach ( array_slice( $cities, 0, 8 ) as $c ) : ?>
				<a class="bds-facet__pill" href="<?php echo esc_url( $c['url'] ); ?>">
					<span><?php echo esc_html( $c['label'] ); ?></span>
					<span class="bds-facet__n"><?php echo esc_html( (string) (int) $c['count'] ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $cities ) > 8 ) : ?>
			<details class="bds-facet__more">
				<summary>View more locations</summary>
				<div class="bds-facet__row">
					<?php foreach ( array_slice( $cities, 8 ) as $c ) : ?>
					<a class="bds-facet__pill" href="<?php echo esc_url( $c['url'] ); ?>">
						<span><?php echo esc_html( $c['label'] ); ?></span>
						<span class="bds-facet__n"><?php echo esc_html( (string) (int) $c['count'] ); ?></span>
					</a>
					<?php endforeach; ?>
				</div>
			</details>
			<?php endif; ?>
		</div>
		<?php elseif ( $gate && ! $digital ) : ?>
		<div class="bds-facet__empty">
			<p><strong>No businesses match these filters yet.</strong></p>
			<p>Try another directory type, clear filters, or add your business.</p>
			<p class="bds-facet__empty-actions">
				<a href="<?php echo esc_url( bds_facet_browse_clear_url( 'all', $state ) ); ?>">Clear filters</a>
				<a href="<?php echo esc_url( bds_facet_browse_po( home_url( '/add-listing/' ) ) ); ?>">Add your business</a>
			</p>
		</div>
		<?php endif; ?>

		<?php if ( $cats ) : ?>
		<div class="bds-facet__block">
			<p class="bds-facet__label"><?php echo $type_label !== '' ? esc_html( 'Narrow ' . $type_label ) : 'What are you looking for?'; ?></p>
			<div class="bds-facet__row">
				<?php foreach ( array_slice( $cats, 0, 12 ) as $c ) : ?>
				<a class="bds-facet__pill<?php echo ( $cat_id === (int) $c['id'] ) ? ' is-on' : ''; ?>" href="<?php echo esc_url( $c['url'] ); ?>">
					<span><?php echo esc_html( $c['label'] ); ?></span>
					<span class="bds-facet__n"><?php echo esc_html( (string) (int) $c['count'] ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $cats ) > 12 ) : ?>
			<details class="bds-facet__more">
				<summary>View all categories (<?php echo esc_html( (string) count( $cats ) ); ?>)</summary>
				<div class="bds-facet__row">
					<?php foreach ( array_slice( $cats, 12 ) as $c ) : ?>
					<a class="bds-facet__pill<?php echo ( $cat_id === (int) $c['id'] ) ? ' is-on' : ''; ?>" href="<?php echo esc_url( $c['url'] ); ?>">
						<span><?php echo esc_html( $c['label'] ); ?></span>
						<span class="bds-facet__n"><?php echo esc_html( (string) (int) $c['count'] ); ?></span>
					</a>
					<?php endforeach; ?>
				</div>
			</details>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $services ) : ?>
		<div class="bds-facet__block">
			<p class="bds-facet__label"><?php echo $cat instanceof WP_Term ? esc_html( $cat->name . ' services' ) : 'Services'; ?></p>
			<div class="bds-facet__row">
				<?php foreach ( $services as $c ) : ?>
				<a class="bds-facet__pill" href="<?php echo esc_url( $c['url'] ); ?>">
					<span><?php echo esc_html( $c['label'] ); ?></span>
					<span class="bds-facet__n"><?php echo esc_html( (string) (int) $c['count'] ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( ! $gate && $result_n < 1 && ( $loc_id > 0 || $cat_id > 0 || $q !== '' ) ) : ?>
		<div class="bds-facet__empty">
			<p><strong>No businesses match these filters yet.</strong></p>
			<p class="bds-facet__empty-actions">
				<?php if ( $cat_id > 0 ) : ?><a href="<?php echo esc_url( bds_facet_browse_clear_url( 'cat', $state ) ); ?>">Remove category</a><?php endif; ?>
				<?php if ( $loc_id > 0 ) : ?><a href="<?php echo esc_url( bds_facet_browse_clear_url( 'city', $state ) ); ?>">Remove city</a><?php endif; ?>
				<a href="<?php echo esc_url( bds_facet_browse_clear_url( 'all', $state ) ); ?>">Clear filters</a>
				<a href="<?php echo esc_url( bds_facet_browse_po( home_url( '/add-listing/' ) ) ); ?>">Add your business</a>
			</p>
		</div>
		<?php endif; ?>
	</div>
</section>
<style id="bds-facet-browse-css">
.bds-facet{max-width:1180px;margin:0 auto 18px;padding:0 16px;font-family:Syne,Manrope,system-ui,sans-serif}
.bds-facet__inner{padding:18px 18px 16px;border:1px solid rgba(18,32,28,.12);border-radius:18px;background:linear-gradient(165deg,#f7fffb,#fff 55%,#f8fafc);box-shadow:0 10px 28px rgba(12,26,22,.06)}
.bds-facet__eyebrow{margin:0 0 4px;font:700 11px/1.2 Manrope,sans-serif;letter-spacing:.06em;text-transform:uppercase;color:#0f9f6e}
.bds-facet__title{margin:0 0 6px;font:800 1.35rem/1.2 Syne,sans-serif;color:#12201c}
.bds-facet__hint{margin:0 0 12px;font:500 13.5px/1.45 Manrope,sans-serif;color:#4d635c;max-width:56ch}
.bds-facet__active{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0 0 12px}
.bds-facet__chip{display:inline-flex;align-items:center;gap:6px;min-height:34px;padding:6px 12px;border-radius:999px;border:1px solid rgba(15,118,110,.35);background:#ecfdf5;color:#115e59!important;text-decoration:none;font:700 13px/1.2 Syne,sans-serif}
.bds-facet__chip span{opacity:.7}
.bds-facet__clear{font:700 12.5px/1 Manrope,sans-serif;color:#0f766e!important;margin-left:4px}
.bds-facet__search{position:relative;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;margin:0 0 14px;align-items:stretch}
.bds-facet__search>.screen-reader-text,
.bds-facet__search input[type=hidden]{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;border:0!important;padding:0!important;margin:0!important}
.bds-facet__search input[type=search]{min-height:44px;width:100%;min-width:0;grid-column:1;grid-row:1;border:1px solid rgba(18,32,28,.16);border-radius:12px;padding:10px 14px;font:600 14px/1.2 Manrope,sans-serif}
.bds-facet__search button{min-height:44px;grid-column:2;grid-row:1;border:0;border-radius:12px;padding:0 18px;background:linear-gradient(135deg,#0f9f6e,#0b7a55);color:#fff;font:800 14px/1 Syne,sans-serif;letter-spacing:0;text-transform:none;white-space:nowrap;cursor:pointer}
.bds-facet__block{margin:0 0 12px}
.bds-facet__label{margin:0 0 8px;font:800 12px/1.2 Syne,sans-serif;letter-spacing:.04em;text-transform:uppercase;color:#334155}
.bds-facet__row{display:flex;flex-wrap:wrap;gap:8px}
.bds-facet__pill{display:inline-flex;align-items:center;justify-content:space-between;gap:10px;min-height:40px;padding:8px 14px;border-radius:999px;border:1px solid rgba(18,32,28,.14);background:#fff;color:#12201c!important;text-decoration:none;font:700 13px/1.2 Syne,sans-serif}
.bds-facet__pill:hover,.bds-facet__pill.is-on{border-color:#0f9f6e;color:#0b7a55!important}
.bds-facet__n{font:800 11px/1 Manrope,sans-serif;padding:3px 7px;border-radius:999px;background:#ccfbf1;color:#115e59}
.bds-facet__more{margin-top:8px}
.bds-facet__more summary{cursor:pointer;font:700 13px/1.2 Manrope,sans-serif;color:#0f766e}
.bds-facet__empty{margin-top:8px;padding:14px 16px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa}
.bds-facet__empty p{margin:0 0 8px;color:#7c2d12;font:500 14px/1.4 Manrope,sans-serif}
.bds-facet__empty-actions{display:flex;flex-wrap:wrap;gap:10px 14px}
.bds-facet__empty-actions a{font:700 13px/1.2 Syne,sans-serif;color:#0f766e!important}
/* One browser: hide legacy duplicate city bars + Directorist search that competed with Ask/facet */
#bds-browse-city-bar,#bds-city-gate{display:none!important}
body:has(#bds-facet-browse) .directorist-categories,
body:has(#bds-facet-browse) .directorist-listing-category,
body:has(#bds-facet-browse) .directorist-all-categories,
body:has(#bds-facet-browse) .atbdp-categories{display:none!important}
body.bds-facet-gate .directorist-search-contents,
body.bds-facet-gate .directorist-search-form,
body.bds-facet-gate form.directorist-advanced-filter,
body.bds-facet-gate .directorist-archive-contents .directorist-listing,
body.bds-facet-gate .directorist-archive-list-view,
body.bds-facet-gate .directorist-archive-grid-view,
body.bds-facet-gate .directorist-pagination,
body.bds-facet-gate .directorist-listings-header,
body.bds-facet-gate #bds-browse-sort,
body.bds-facet-gate .error-404,
body.bds-facet-gate .not-found,
body.bds-facet-gate .page-header .page-title,
body.bds-facet-gate h1.page-title,
body.bds-facet-gate .directorist-archive-notfound,
body.bds-facet-gate .directorist-listing-no-result,
body.bds-facet-gate .entry-title,
body.bds-facet-gate .directorist-alert--warning{display:none!important}
body.bds-facet-browse .directorist-search-form:not(.bds-facet__search),
body.bds-facet-browse .directorist-search-contents,
body.bds-facet-browse .directorist-advanced-filter,
body.bds-facet-browse .directorist-search-form-wrap,
body.bds-facet-browse .directorist-archive-title,
body.bds-facet-browse .directorist-listings-header,
body.bds-facet-browse .directorist-listing-header,
body.bds-facet-browse .directorist-listing-no-result,
body.bds-facet-browse .directorist-archive-notfound,
body.bds-facet-browse .directorist-alert--warning,
body.bds-facet-browse .directorist-type-nav:not(.bds-dtd-browse),
body.bds-facet-has-type .bds-placement-stack,
body.bds-facet-has-type .bds-bid-strip{display:none!important}
body.bds-facet-gate.bds-facet-has-type .bds-dtd-browse{display:none!important}
body.bds-facet-browse #bds-ai-hero-entry,
body.bds-facet-browse .bds-ai-hero-entry,
body.bds-facet-browse #bds-ai-local-pack,
body.bds-facet-browse .bds-ai-local-pack,
body.bds-facet-browse .bds-hbrowse,
body.bds-facet-browse [data-bds-ask]{display:none!important}
body.bds-facet-gate .directorist-content-active .directorist-listing-single{display:none!important}
@media (max-width:780px){
  .bds-facet__pill{width:100%;justify-content:space-between}
  .bds-facet__search{grid-template-columns:1fr}
}
</style>
<script id="bds-facet-browse-js">(function(){
  function ensurePrimaryHeading(){
    var panel=document.getElementById('bds-facet-browse');
    if(!panel) return;
    var title=panel.querySelector('.bds-facet__title');
    if(!title) return;
    if(title.tagName!=='H1'){
      var restored=document.createElement('h1');
      restored.className='bds-facet__title';
      restored.textContent=title.textContent||'';
      if(title.parentNode) title.parentNode.replaceChild(restored,title);
      title=restored;
    }
    title.hidden=false;
    title.removeAttribute('hidden');
    title.removeAttribute('aria-hidden');
    title.style.removeProperty('display');
    document.querySelectorAll('h1.directorist-archive-title').forEach(function(legacy){
      var secondary=document.createElement('h2');
      secondary.className=(legacy.className||'')+' bds-facet__legacy-title';
      secondary.textContent=legacy.textContent||'';
      if(legacy.parentNode) legacy.parentNode.replaceChild(secondary,legacy);
    });
  }
  function cardId(el){
    var id=el.getAttribute('data-id')||el.getAttribute('data-post-id')||el.getAttribute('listing_id')||'';
    if(id) return String(id);
    var html=el.outerHTML||'';
    var lm=html.match(/listing_id=["'](\d+)/);
    if(lm) return lm[1];
    var m=(el.id||'').match(/(\d+)/);
    if(m) return m[1];
    var art=el.closest('article');
    if(art){
      var am=(art.className||'').match(/post-(\d+)/);
      if(am) return am[1];
      if(art.id){ var im=art.id.match(/(\d+)/); if(im) return im[1]; }
    }
    return '';
  }
  function hideIneligible(){
    var panel=document.getElementById('bds-facet-browse');
    if(!panel) return;
    var raw=panel.getAttribute('data-bds-ids')||'';
    var n=parseInt(panel.getAttribute('data-bds-n')||'-1',10);
    if(n<0) return;
    var allow={};
    raw.split(',').forEach(function(x){ if(x) allow[x]=1; });
    var cards=document.querySelectorAll('.directorist-listing-single, article.atbd_single_listing');
    if(!cards.length) return;
    if(n===0){
      cards.forEach(function(el){ el.style.display='none'; });
      return;
    }
    if(!raw) return;
    cards.forEach(function(el){
      var id=cardId(el);
      if(id && !allow[id]) el.style.display='none';
    });
  }
  function place(){
    var panel=document.getElementById('bds-facet-browse');
    if(!panel||panel.getAttribute('data-placed')) return;
    var host=document.querySelector('.bds-dtd-browse')||document.querySelector('.directorist-archive-contents, .directorist-archive-title, .site-main, main, #content');
    if(host&&host.parentNode){
      if(host.classList&&host.classList.contains('bds-dtd-browse')) host.parentNode.insertBefore(panel, host.nextSibling);
      else host.parentNode.insertBefore(panel, host);
      panel.setAttribute('data-placed','1');
    }
    ensurePrimaryHeading();
    hideIneligible();
    var type='', loc='';
    try{
      var here=new URL(location.href);
      type=here.searchParams.get('directory_type')||'';
      loc=here.searchParams.get('in_loc')||'';
    }catch(e){}
    if(type){
      panel.querySelectorAll('a[href]').forEach(function(a){
        try{
          var u=new URL(a.href, location.origin);
          if(!u.searchParams.get('directory_type')){
            u.searchParams.set('directory_type', type);
            a.href=u.toString();
          }
        }catch(err){}
      });
    }
    if(loc){
      document.querySelectorAll('.bds-dtd-browse a[href]').forEach(function(a){
        try{
          var u=new URL(a.href, location.origin);
          if(!u.searchParams.get('in_loc')){
            u.searchParams.set('in_loc', loc);
            a.href=u.toString();
          }
        }catch(err){}
      });
    }
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', place);
  else place();
  setTimeout(place, 300);
  setTimeout(ensurePrimaryHeading, 500);
  setTimeout(hideIneligible, 400);
  setTimeout(place, 1200);
  setTimeout(ensurePrimaryHeading, 1500);
})();</script>
	<?php
	echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


