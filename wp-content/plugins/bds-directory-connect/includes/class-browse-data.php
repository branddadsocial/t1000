<?php
/**
 * Live Popular In + category chip data from Directorist taxonomies.
 *
 * @package BDS_Directory_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BDS_DC_Browse_Data {

	const TRANSIENT = 'bds_dc_browse_v1';
	const TTL       = HOUR_IN_SECONDS;

	/**
	 * Parent location slugs to skip in Popular In (show cities, not countries/states).
	 *
	 * @var string[]
	 */
	const SKIP_LOCATION_SLUGS = array(
		'mexico',
		'mx-roo',
		'united-states',
		'us',
		'us-il',
	);

	/**
	 * Local category slugs preferred on the home “Local near you” row (order preserved when present).
	 *
	 * @var string[]
	 */
	const LOCAL_CATEGORY_SLUGS = array(
		'coffee-shops',
		'mexican-restaurants',
		'italian-restaurants',
		'american-restaurants',
		'brazilian-restaurants',
		'bars',
		'night-clubs',
		'hotels',
		'hotels-stays',
		'massage-and-spa',
		'massage-therapy',
		'muay-thai-gyms',
		'restaurants',
		'bakeries',
		'car-rentals',
		'tour-operators',
	);

	/**
	 * Digital category slugs for the “Anywhere online” row.
	 *
	 * @var string[]
	 */
	const DIGITAL_CATEGORY_SLUGS = array(
		'graphic-design-creative-services',
		'web-design-development',
		'social-media-management',
		'search-engine-optimization-seo',
		'digital-marketing',
		'hosting-domain-services',
	);

	/**
	 * Flush cached browse payload.
	 */
	public static function flush_cache() {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Get browse payload (cached).
	 *
	 * @param bool $force Force rebuild.
	 * @return array
	 */
	public static function get( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) && ! empty( $cached['generated'] ) ) {
				return $cached;
			}
		}

		$data = self::build();
		set_transient( self::TRANSIENT, $data, self::TTL );
		return $data;
	}

	/**
	 * Build fresh browse data from taxonomies.
	 *
	 * @return array
	 */
	public static function build() {
		$locations = self::locations();
		$local     = self::categories( self::LOCAL_CATEGORY_SLUGS, 14, true );
		$digital   = self::categories( self::DIGITAL_CATEGORY_SLUGS, 10, false );

		// Combo URLs: category + each popular location → search-result.
		foreach ( $local as &$chip ) {
			$chip['combo'] = array();
			foreach ( $locations as $loc ) {
				$chip['combo'][ $loc['slug'] ] = add_query_arg(
					array(
						'in_cat'         => (int) $chip['id'],
						'in_loc'         => (int) $loc['id'],
						'directory_type' => $chip['directory_type'] ? $chip['directory_type'] : '',
					),
					home_url( '/search-result/' )
				);
			}
		}
		unset( $chip );

		$types = self::directory_types();

		return array(
			'generated'     => time(),
			'locations'     => $locations,
			'local'         => $local,
			'digital'       => $digital,
			'directoryTypes'=> $types,
			'allListings'   => home_url( '/all-listings/' ),
		);
	}

	/**
	 * Leaf locations with listings, ordered by count desc.
	 *
	 * @return array<int, array>
	 */
	private static function locations() {
		if ( ! taxonomy_exists( 'at_biz_dir-location' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'at_biz_dir-location',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 40,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			if ( in_array( $term->slug, self::SKIP_LOCATION_SLUGS, true ) ) {
				continue;
			}
			// Prefer cities: skip terms that still have child locations with listings.
			$children = get_terms(
				array(
					'taxonomy'   => 'at_biz_dir-location',
					'parent'     => (int) $term->term_id,
					'hide_empty' => true,
					'number'     => 1,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
				continue;
			}

			$count = (int) $term->count;
			if ( $count < 1 ) {
				continue;
			}

			// Prefer search-result?in_loc= so Local → place always shows listings
			// (single-location templates are inconsistent / often look empty).
			$loc_link = add_query_arg(
				array( 'in_loc' => (int) $term->term_id ),
				home_url( '/search-result/' )
			);
			$term_link = get_term_link( $term );
			if ( ! is_wp_error( $term_link ) ) {
				// Keep archive as secondary target (open on second click).
				$archive = $term_link;
			} else {
				$archive = $loc_link;
			}

			$out[] = array(
				'id'      => (int) $term->term_id,
				'slug'    => $term->slug,
				'label'   => $term->name,
				'count'   => $count,
				'url'     => $loc_link,
				'archive' => $archive,
			);
		}

		// Cap Popular In row.
		return array_slice( $out, 0, 12 );
	}

	/**
	 * Category chips with live counts.
	 *
	 * @param string[] $preferred Preferred slugs (order).
	 * @param int      $limit     Max chips.
	 * @param bool     $fill      Fill with other top categories if preferred missing.
	 * @return array<int, array>
	 */
	private static function categories( array $preferred, $limit, $fill ) {
		if ( ! taxonomy_exists( 'at_biz_dir-category' ) ) {
			return array();
		}

		$by_slug = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'at_biz_dir-category',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 80,
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}
		foreach ( $terms as $term ) {
			$by_slug[ $term->slug ] = $term;
		}

		$picked = array();
		$seen   = array();
		foreach ( $preferred as $slug ) {
			if ( empty( $by_slug[ $slug ] ) ) {
				continue;
			}
			$picked[]       = self::format_category( $by_slug[ $slug ] );
			$seen[ $slug ]  = true;
			if ( count( $picked ) >= $limit ) {
				return $picked;
			}
		}

		if ( $fill ) {
			foreach ( $terms as $term ) {
				if ( isset( $seen[ $term->slug ] ) ) {
					continue;
				}
				$picked[] = self::format_category( $term );
				if ( count( $picked ) >= $limit ) {
					break;
				}
			}
		}

		return $picked;
	}

	/**
	 * Format a category term for the front end.
	 *
	 * IMPORTANT: Directorist /single-category/{slug}/ archives currently render
	 * "0 Items Found" even when listings exist. Always link Local/Digital chips
	 * through /search-result/?in_cat=… which returns real listings.
	 *
	 * @param WP_Term $term Term.
	 * @return array
	 */
	private static function format_category( $term ) {
		$dir_type = self::term_directory_type( $term );
		$args     = array(
			'in_cat' => (int) $term->term_id,
		);
		if ( $dir_type ) {
			$args['directory_type'] = $dir_type;
		}
		$link = add_query_arg( $args, home_url( '/search-result/' ) );

		return array(
			'id'             => (int) $term->term_id,
			'slug'           => $term->slug,
			'label'          => self::short_label( $term->name ),
			'count'          => (int) $term->count,
			'url'            => $link,
			'directory_type' => $dir_type,
		);
	}

	/**
	 * Shorten long Directorist category names for chips.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private static function short_label( $name ) {
		$map = array(
			'Graphic Design & Creative Services'       => 'Graphic Design',
			'Web Design & Development'                 => 'Web Design',
			'Social Media Management'                  => 'Social Media',
			'Search Engine Optimization (SEO)'         => 'SEO',
			'Hosting & Domain Services'                => 'Hosting & Domains',
			'Mexican Restaurants'                      => 'Mexican',
			'Italian Restaurants'                      => 'Italian',
			'American Restaurants'                     => 'American',
			'Brazilian Restaurants'                    => 'Brazilian',
			'Massage & Spa'                            => 'Massage & Spa',
			'Muay Thai Gyms'                           => 'Muay Thai',
		);
		return isset( $map[ $name ] ) ? $map[ $name ] : $name;
	}

	/**
	 * Resolve directory type slug for a category term.
	 *
	 * @param WP_Term $term Term.
	 * @return string
	 */
	private static function term_directory_type( $term ) {
		$keys = array( '_directory_type', 'directory_type', 'listing_type' );
		foreach ( $keys as $key ) {
			$raw = get_term_meta( $term->term_id, $key, true );
			if ( empty( $raw ) ) {
				continue;
			}
			if ( is_array( $raw ) ) {
				$raw = reset( $raw );
			}
			if ( is_numeric( $raw ) ) {
				$t = get_term( (int) $raw, 'atbdp_listing_types' );
				if ( $t && ! is_wp_error( $t ) ) {
					return $t->slug;
				}
			}
			if ( is_string( $raw ) && $raw !== '' ) {
				return sanitize_title( $raw );
			}
		}

		// Heuristic fallbacks by slug family.
		$slug = $term->slug;
		if ( preg_match( '/restaurant|baker|coffee|cafe|food/', $slug ) ) {
			return 'food-beverage';
		}
		if ( preg_match( '/bar|night-club|nightlife|club/', $slug ) ) {
			return 'entertainment-nightlife';
		}
		if ( preg_match( '/hotel|stay/', $slug ) ) {
			return 'hotels-stays';
		}
		if ( preg_match( '/massage|spa|muay|fitness|gym|health/', $slug ) ) {
			return 'health-body';
		}
		if ( preg_match( '/design|seo|social-media|hosting|digital|marketing|web-design/', $slug ) ) {
			return 'digital-services';
		}
		if ( preg_match( '/car-rental|automotive/', $slug ) ) {
			return 'automotive';
		}
		if ( preg_match( '/tour|travel/', $slug ) ) {
			return 'travel';
		}
		return '';
	}

	/**
	 * All Directorist listing types with archive URLs.
	 *
	 * @return array<int, array>
	 */
	private static function directory_types() {
		$out = array();
		if ( ! taxonomy_exists( 'atbdp_listing_types' ) ) {
			// Fall back to known home-page types if taxonomy missing in this request.
			$fallback = array(
				'automotive', 'beauty-personal-care', 'classifieds', 'digital-services',
				'education-training', 'entertainment-nightlife', 'events', 'food-beverage',
				'health-body', 'hotels-stays', 'jobs-careers', 'legal-services',
				'local-services', 'pets-animals', 'professional-services',
				'real-estate-directory', 'shopping-directory', 'travel',
			);
			foreach ( $fallback as $slug ) {
				$out[] = array(
					'slug'  => $slug,
					'label' => ucwords( str_replace( '-', ' ', $slug ) ),
					'count' => 0,
					'url'   => add_query_arg( 'directory_type', $slug, home_url( '/search-result/' ) ),
				);
			}
			return $out;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'atbdp_listing_types',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $out;
		}

		foreach ( $terms as $term ) {
			$out[] = array(
				'id'    => (int) $term->term_id,
				'slug'  => $term->slug,
				'label' => $term->name,
				'count' => (int) $term->count,
				'url'   => add_query_arg( 'directory_type', $term->slug, home_url( '/search-result/' ) ),
			);
		}
		return $out;
	}
}
