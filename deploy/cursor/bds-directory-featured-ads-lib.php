<?php
/**
 * Pure placement rules for BrandDad Directory featured advertising.
 *
 * No WordPress, no billing, and no public classification labels live here.
 * The MU plugin decides what may be stored or shown.
 */

if ( ! function_exists( 'bds_feat_levels' ) ) {
	/**
	 * Placement products. Prices are targets until a paid Woo order is confirmed.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function bds_feat_levels() {
		return array(
			'local'    => array( 'label' => 'Local Featured', 'price' => '9.99', 'radius_km' => 25, 'cap' => 8 ),
			'regional' => array( 'label' => 'Regional Featured', 'price' => '19.99', 'radius_km' => 100, 'cap' => 6 ),
			'national' => array( 'label' => 'National Featured', 'price' => '39.99', 'radius_km' => null, 'cap' => 4 ),
			'global'   => array( 'label' => 'Global Homepage Featured', 'price' => '99.99', 'radius_km' => null, 'cap' => 3 ),
		);
	}
}

if ( ! function_exists( 'bds_feat_valid_lane' ) ) {
	/**
	 * Allowlisted featured lane from a query string. Unknown values fail closed to empty.
	 *
	 * @param mixed $lane Raw GET value.
	 * @return string
	 */
	function bds_feat_valid_lane( $lane ) {
		$lane = strtolower( (string) $lane );
		$lane = preg_replace( '/[^a-z0-9_\-]/', '', $lane );
		return isset( bds_feat_levels()[ $lane ] ) ? $lane : '';
	}
}

if ( ! function_exists( 'bds_feat_rank' ) ) {
	/** Paid first, complimentary trial and partnership together, then free, then BrandDad promo. */
	function bds_feat_rank( $kind ) {
		$kind = (string) $kind;
		if ( 'paid' === $kind ) {
			return 1;
		}
		if ( 'trial' === $kind || 'partnership' === $kind ) {
			return 2;
		}
		if ( 'free' === $kind ) {
			return 3;
		}
		return 4;
	}
}

if ( ! function_exists( 'bds_feat_haversine_km' ) ) {
	function bds_feat_haversine_km( $lat1, $lng1, $lat2, $lng2 ) {
		$earth = 6371.0;
		$d_lat = deg2rad( (float) $lat2 - (float) $lat1 );
		$d_lng = deg2rad( (float) $lng2 - (float) $lng1 );
		$a     = sin( $d_lat / 2 ) * sin( $d_lat / 2 )
			+ cos( deg2rad( (float) $lat1 ) ) * cos( deg2rad( (float) $lat2 ) ) * sin( $d_lng / 2 ) * sin( $d_lng / 2 );
		return $earth * ( 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) ) );
	}
}

if ( ! function_exists( 'bds_feat_same_country' ) ) {
	/** ISO-2 country match. Empty or three-letter codes fail closed. */
	function bds_feat_same_country( $card_country, $viewer_country ) {
		$card_country   = strtoupper( (string) $card_country );
		$viewer_country = strtoupper( (string) $viewer_country );
		return (bool) preg_match( '/^[A-Z]{2}$/', $card_country ) && $card_country === $viewer_country;
	}
}

if ( ! function_exists( 'bds_feat_in_scope' ) ) {
	/**
	 * @param array<string,mixed> $card
	 * @param array<string,mixed> $viewer country, lat, lng, category.
	 */
	function bds_feat_in_scope( array $card, array $viewer ) {
		$levels = bds_feat_levels();
		$level  = (string) ( $card['level'] ?? '' );
		if ( ! isset( $levels[ $level ] ) ) {
			return false;
		}
		if ( 'global' === $level ) {
			return true;
		}
		$card_country   = strtoupper( (string) ( $card['country'] ?? '' ) );
		$viewer_country = strtoupper( (string) ( $viewer['country'] ?? '' ) );
		if ( ! bds_feat_same_country( $card_country, $viewer_country ) ) {
			return false;
		}
		if ( 'national' === $level ) {
			return true;
		}
		if ( ! isset( $card['lat'], $card['lng'], $viewer['lat'], $viewer['lng'] ) ) {
			return false;
		}
		$radius = $card['radius_km'] ?? $levels[ $level ]['radius_km'];
		if ( null === $radius ) {
			return false;
		}
		return bds_feat_haversine_km( $viewer['lat'], $viewer['lng'], $card['lat'], $card['lng'] ) <= (float) $radius;
	}
}

if ( ! function_exists( 'bds_feat_select' ) ) {
	/**
	 * Fill slots without dropping a higher priority for a lower one.
	 * Extra paid cards wait; they are not replaced by free cards.
	 *
	 * @param array<int,array<string,mixed>> $cards
	 * @param array<string,mixed>            $viewer
	 * @return array{shown:array<int,array<string,mixed>>,waitlist:array<int,array<string,mixed>>}
	 */
	function bds_feat_select( array $cards, array $viewer, $limit ) {
		$limit   = max( 0, (int) $limit );
		$eligible = array();
		foreach ( $cards as $card ) {
			if ( empty( $card['active'] ) || ! bds_feat_in_scope( $card, $viewer ) ) {
				continue;
			}
			$card['rank'] = bds_feat_rank( $card['kind'] ?? 'promo' );
			$eligible[]   = $card;
		}
		usort(
			$eligible,
			static function ( $a, $b ) {
				if ( $a['rank'] !== $b['rank'] ) {
					return $a['rank'] <=> $b['rank'];
				}
				$imp = ( (int) ( $a['impressions'] ?? 0 ) ) <=> ( (int) ( $b['impressions'] ?? 0 ) );
				if ( 0 !== $imp ) {
					return $imp;
				}
				return strcmp( (string) ( $a['id'] ?? '' ), (string) ( $b['id'] ?? '' ) );
			}
		);
		return array(
			'shown'    => array_slice( $eligible, 0, $limit ),
			'waitlist' => array_slice( $eligible, $limit ),
		);
	}
}

if ( ! function_exists( 'bds_feat_public_disclosure' ) ) {
	/** Only paid placements are disclosed as sponsored. Internal kinds stay off the card. */
	function bds_feat_public_disclosure( $kind ) {
		return 'paid' === (string) $kind ? 'Sponsored' : '';
	}
}

if ( ! function_exists( 'bds_feat_has_capacity' ) ) {
	/**
	 * Pending, paused, and active rows reserve a slot. Expired and revoked rows do not.
	 *
	 * @param array<int,array<string,mixed>> $grants
	 */
	function bds_feat_has_capacity( array $grants, $level ) {
		$levels = bds_feat_levels();
		$level  = (string) $level;
		if ( ! isset( $levels[ $level ] ) ) {
			return false;
		}
		$used = 0;
		foreach ( $grants as $grant ) {
			if ( $level !== (string) ( $grant['level'] ?? '' ) ) {
				continue;
			}
			if ( in_array( (string) ( $grant['status'] ?? '' ), array( 'active', 'pending_payment', 'paused' ), true ) ) {
				++$used;
			}
		}
		return $used < (int) $levels[ $level ]['cap'];
	}
}

if ( ! function_exists( 'bds_feat_trial_decision' ) ) {
	/**
	 * One complimentary 14-day Local trial. No payment method and no later charge.
	 *
	 * @param array<string,mixed> $business
	 * @return array{ok:bool,reason:string}
	 */
	function bds_feat_trial_decision( array $business ) {
		if ( empty( $business['verified'] ) || empty( $business['claimed'] ) ) {
			return array( 'ok' => false, 'reason' => 'verified_claimed_required' );
		}
		if ( ! empty( $business['paid_active'] ) || ! empty( $business['grant_active'] ) ) {
			return array( 'ok' => false, 'reason' => 'placement_already_active' );
		}
		if ( ! empty( $business['trial_used'] ) ) {
			return array( 'ok' => false, 'reason' => 'trial_already_used' );
		}
		return array( 'ok' => true, 'reason' => 'eligible' );
	}
}

if ( ! function_exists( 'bds_feat_apply_event' ) ) {
	/**
	 * Record one real impression or click. A client-supplied total is rejected.
	 *
	 * @param array<string,mixed> $grant
	 * @param array<string,mixed> $event type, event_id. count and total are forbidden.
	 * @return array{ok:bool,grant:array<string,mixed>,reason:string}
	 */
	function bds_feat_apply_event( array $grant, array $event ) {
		$type = (string) ( $event['type'] ?? '' );
		$id   = (string) ( $event['event_id'] ?? '' );
		if ( 'active' !== (string) ( $grant['status'] ?? '' ) ) {
			return array( 'ok' => false, 'grant' => $grant, 'reason' => 'inactive' );
		}
		if ( ! in_array( $type, array( 'impression', 'click' ), true ) ) {
			return array( 'ok' => false, 'grant' => $grant, 'reason' => 'bad_type' );
		}
		if ( isset( $event['count'] ) || isset( $event['total'] ) ) {
			return array( 'ok' => false, 'grant' => $grant, 'reason' => 'absolute_rejected' );
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{8,64}$/', $id ) ) {
			return array( 'ok' => false, 'grant' => $grant, 'reason' => 'bad_event' );
		}
		$seen = isset( $grant['seen_events'] ) && is_array( $grant['seen_events'] ) ? $grant['seen_events'] : array();
		$key  = $type . ':' . $id;
		if ( in_array( $key, $seen, true ) ) {
			return array( 'ok' => false, 'grant' => $grant, 'reason' => 'duplicate' );
		}
		$field           = 'impression' === $type ? 'impressions' : 'clicks';
		$grant[ $field ] = (int) ( $grant[ $field ] ?? 0 ) + 1;
		$seen[]          = $key;
		if ( count( $seen ) > 200 ) {
			$seen = array_slice( $seen, -200 );
		}
		$grant['seen_events'] = array_values( $seen );
		return array( 'ok' => true, 'grant' => $grant, 'reason' => 'recorded' );
	}
}

if ( ! function_exists( 'bds_feat_empty_copy' ) ) {
	/**
	 * Public empty-lane sell copy. Price is the catalog target, not a charged amount.
	 *
	 * @return array{label:string,price:string,cta:string}
	 */
	function bds_feat_empty_copy( $level ) {
		$levels = bds_feat_levels();
		$level  = (string) $level;
		if ( ! isset( $levels[ $level ] ) ) {
			return array(
				'label' => '',
				'price' => '',
				'cta'   => 'Get featured',
			);
		}
		return array(
			'label' => (string) $levels[ $level ]['label'],
			'price' => (string) $levels[ $level ]['price'],
			'cta'   => 'Get featured',
		);
	}
}

if ( ! function_exists( 'bds_feat_path_needs_door' ) ) {
	/**
	 * Help, contact, about, blog, and feedback need a listing door.
	 * Home, plans, add-listing, and listing archives already have one.
	 *
	 * @param string $path Request path.
	 * @return bool
	 */
	function bds_feat_path_needs_door( $path ) {
		$path = strtolower( trim( (string) $path, '/' ) );
		if ( '' === $path ) {
			return false;
		}
		$skip = array( 'add-listing', 'select-your-plan', 'featured-advertising', 'registration', 'sign-in', 'dashboard', 'all-listings' );
		if ( in_array( $path, $skip, true ) ) {
			return false;
		}
		if ( 0 === strpos( $path, 'single-category' ) || 0 === strpos( $path, 'single-location' ) || 0 === strpos( $path, 'location/' ) ) {
			return false;
		}
		$top = strstr( $path, '/', true );
		if ( false === $top ) {
			$top = $path;
		}
		return in_array(
			$top,
			array(
				'help',
				'contact-us',
				'about',
				'blog',
				'feedback',
				'events',
				'jobs',
				'jobs-careers',
				'classifieds',
				'memberships',
				'membership',
				'plans',
				'gift-cards',
				'gift-card-marketplace',
				'travel',
				'hotels',
				'flights',
				'privacy-policy',
				'privacy-policy-2',
				'about-us',
				'terms',
				'terms-and-conditions',
				'faq',
				'sitemap',
				'how-it-works',
			),
			true
		);
	}
}

if ( ! function_exists( 'bds_feat_directory_surface' ) ) {
	/**
	 * Public Directory surfaces that need listing-door copy.
	 *
	 * @param mixed $path Request path.
	 * @return string events|jobs|classifieds|blog|
	 */
	function bds_feat_directory_surface( $path ) {
		$path = strtolower( trim( (string) $path, '/' ) );
		$top  = strstr( $path, '/', true );
		if ( false === $top ) {
			$top = $path;
		}
		$map = array(
			'events'       => 'events',
			'jobs'         => 'jobs',
			'jobs-careers' => 'jobs',
			'classifieds'  => 'classifieds',
			'blog'         => 'blog',
			'memberships'  => 'memberships',
			'membership'   => 'memberships',
			'plans'                 => 'memberships',
			'gift-cards'            => 'gifts',
			'gift-card-marketplace' => 'gifts',
			'travel'                => 'travel',
			'hotels'                => 'hotels',
			'flights'               => 'travel',
		);
		return isset( $map[ $top ] ) ? $map[ $top ] : '';
	}
}

if ( ! function_exists( 'bds_feat_listing_type_surface' ) ) {
	/**
	 * Directory type query on all-listings. Unknown values fail closed.
	 *
	 * @param mixed $raw directory_type query.
	 * @return string events|jobs|classifieds|travel|hotels|gifts|
	 */
	function bds_feat_listing_type_surface( $raw ) {
		$type = strtolower( preg_replace( '/[^a-z0-9\-]/', '', (string) $raw ) );
		$map  = array(
			'events'                => 'events',
			'jobs'                  => 'jobs',
			'jobs-careers'          => 'jobs',
			'classifieds'           => 'classifieds',
			'travel'                => 'travel',
			'flights'               => 'travel',
			'hotels-stays'          => 'hotels',
			'hotels'                => 'hotels',
			'gifts'                 => 'gifts',
			'gift-cards'            => 'gifts',
			'gift-card-marketplace' => 'gifts',
		);
		return isset( $map[ $type ] ) ? $map[ $type ] : '';
	}
}

if ( ! function_exists( 'bds_feat_product_sku' ) ) {
	function bds_feat_product_sku( $level ) {
		$level = (string) $level;
		return isset( bds_feat_levels()[ $level ] ) ? 'bds-feat-' . $level : '';
	}
}

if ( ! function_exists( 'bds_feat_parse_listing_id' ) ) {
	/**
	 * GET listing prefill is digits only. URLs, SQL, and scripts become 0.
	 *
	 * @param mixed $raw Untrusted request value.
	 * @return int
	 */
	function bds_feat_parse_listing_id( $raw ) {
		if ( is_int( $raw ) ) {
			return $raw > 0 ? $raw : 0;
		}
		if ( is_string( $raw ) && ctype_digit( $raw ) ) {
			$id = (int) $raw;
			return $id > 0 ? $id : 0;
		}
		return 0;
	}
}

if ( ! function_exists( 'bds_feat_listing_checkout_prompt' ) ) {
	/**
	 * Featured buy page copy. Zero listing stays inactive; no charge is implied.
	 *
	 * @param mixed $listing_id
	 * @return array{attached:bool,line:string}
	 */
	function bds_feat_listing_checkout_prompt( $listing_id ) {
		$id = (int) $listing_id;
		if ( $id > 0 ) {
			return array(
				'attached' => true,
				'line'     => 'Subscribe monthly attaches to listing ' . $id . '. The lane stays inactive until WooCommerce confirms payment.',
			);
		}
		return array(
			'attached' => false,
			'line'     => 'Open Featured from a published listing so Subscribe monthly can attach. A plan without a listing stays inactive.',
		);
	}
}

if ( ! function_exists( 'bds_feat_strip_tag_by_id' ) ) {
	/**
	 * Remove one HTML element by tag + id. Depth-counts matching close tags.
	 *
	 * @param string $html HTML.
	 * @param string $tag  Tag name.
	 * @param string $id   Element id.
	 * @return string
	 */
	function bds_feat_strip_tag_by_id( $html, $tag, $id ) {
		if ( ! is_string( $html ) || $html === '' || ! is_string( $tag ) || $tag === '' || ! is_string( $id ) || $id === '' ) {
			return is_string( $html ) ? $html : '';
		}
		if ( ! preg_match( '/^[a-z][a-z0-9-]*$/', $tag ) || ! preg_match( '/^[A-Za-z][A-Za-z0-9:_-]*$/', $id ) ) {
			return $html;
		}
		$pattern = '/<' . preg_quote( $tag, '/' ) . '\b[^>]*\bid=["\']' . preg_quote( $id, '/' ) . '["\'][^>]*>/i';
		if ( ! preg_match( $pattern, $html, $match, PREG_OFFSET_CAPTURE ) ) {
			return $html;
		}
		$open_start = (int) $match[0][1];
		$gt         = $open_start + strlen( $match[0][0] ) - 1;
		$open_needle  = '<' . $tag;
		$close_needle = '</' . $tag . '>';
		$depth        = 1;
		$i            = $gt + 1;
		$len          = strlen( $html );
		$open_len     = strlen( $open_needle );
		$close_len    = strlen( $close_needle );
		while ( $i < $len && $depth > 0 ) {
			$next_open  = stripos( $html, $open_needle, $i );
			$next_close = stripos( $html, $close_needle, $i );
			if ( false === $next_close ) {
				return $html;
			}
			$open_is_tag = false;
			if ( false !== $next_open && $next_open < $next_close ) {
				$ch = $next_open + $open_len;
				if ( $ch < $len ) {
					$b = $html[ $ch ];
					if ( ' ' === $b || '>' === $b || "\t" === $b || "\n" === $b || "\r" === $b ) {
						$open_is_tag = true;
					}
				}
			}
			if ( $open_is_tag ) {
				$depth++;
				$i = $next_open + $open_len;
				continue;
			}
			$depth--;
			$end = $next_close + $close_len;
			if ( 0 === $depth ) {
				return substr( $html, 0, $open_start ) . substr( $html, $end );
			}
			$i = $end;
		}
		return $html;
	}
}

if ( ! function_exists( 'bds_feat_rewrite_home_legacy_html' ) ) {
	/**
	 * Directory homepage leftover: unpark #bdd-title, drop retired WhatsApp
	 * hero and leftover gift inventory. Gift marketplace keeps its own page.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	function bds_feat_rewrite_leftover_labels( $html ) {
		if ( ! is_string( $html ) || $html === '' ) {
			return is_string( $html ) ? $html : '';
		}
		return str_replace(
			array(
				'>See plans</a>',
				'>See plans and prices</a>',
				'>See plans &amp; prices</a>',
				'>See plans & prices</a>',
				'>See plans →</a>',
				'data-bds-copy-en="See plans"',
				'data-bds-copy-en="See plans and prices"',
				'data-bds-copy-en="See plans &amp; prices"',
				'data-bds-copy-en="See plans & prices"',
				'>List your business</a>',
				'>List My Business</a>',
				'>Subscribe now</a>',
				'data-bds-copy-en="Subscribe now"',
				'data-bds-copy-es="Suscribirse ahora"',
			),
			array(
				'>See membership plans</a>',
				'>See membership plans</a>',
				'>See membership plans</a>',
				'>See membership plans</a>',
				'>See membership plans</a>',
				'data-bds-copy-en="See membership plans"',
				'data-bds-copy-en="See membership plans"',
				'data-bds-copy-en="See membership plans"',
				'data-bds-copy-en="See membership plans"',
				'>List my business free</a>',
				'>List my business free</a>',
				'>Subscribe monthly</a>',
				'data-bds-copy-en="Subscribe monthly"',
				'data-bds-copy-es="Suscribirse cada mes"',
			),
			$html
		);
	}

	function bds_feat_rewrite_home_legacy_html( $html ) {
		if ( ! is_string( $html ) || $html === '' ) {
			return is_string( $html ) ? $html : '';
		}
		$html = bds_feat_rewrite_leftover_labels( $html );
		$rewritten = preg_replace(
			'/body\.home #bdd-title\{[^}]*left:-9999px[^}]*\}/',
			'body.home #bdd-title{position:static;left:auto;width:auto;height:auto;overflow:visible}',
			$html,
			1
		);
		$html = is_string( $rewritten ) ? $rewritten : $html;
		$html = bds_feat_strip_tag_by_id( $html, 'section', 'bds-dfc-gift' );
		$html = preg_replace( '#<script\b[^>]*\bid=(["\'])bds-dfc-gift-place-js\1[^>]*>.*?</script>#is', '', $html, 1 );
		if ( ! is_string( $html ) ) {
			return '';
		}
		$has_current = ( false !== stripos( $html, 'id="bdd-title"' ) || false !== stripos( $html, "id='bdd-title'" ) );
		if ( $has_current ) {
			$probe = bds_feat_strip_tag_by_id( $html, 'section', 'bds-wa-hero' );
			if ( false !== stripos( $probe, 'id="bdd-title"' ) || false !== stripos( $probe, "id='bdd-title'" ) ) {
				$html = $probe;
			}
		}
		$out = preg_replace(
			'#(<a\b[^>]*href="[^"]*add-listing[^"]*"[^>]*)>List My Business</a>#',
			'$1>List my business free</a>',
			$html
		);
		$html = is_string( $out ) ? $out : $html;
		$out  = preg_replace(
			'#(<a\b[^>]*)>List your business</a>#',
			'$1>List my business free</a>',
			$html
		);
		$html = is_string( $out ) ? $out : $html;
		$out  = preg_replace(
			'#(<a\b[^>]*href="[^"]*select-your-plan[^"]*"[^>]*)>See plans(?: and prices| &amp; prices| & prices)?(?:\s*(?:→|&rarr;))?</a>#u',
			'$1>See membership plans</a>',
			$html
		);
		$html = is_string( $out ) ? $out : $html;
		$html = str_replace(
			array(
				'data-bds-copy-en="See plans &amp; prices"',
				'data-bds-copy-en="See plans & prices"',
				'data-bds-copy-en="See plans and prices"',
				'data-bds-copy-es="Ver planes y precios"',
			),
			array(
				'data-bds-copy-en="See membership plans"',
				'data-bds-copy-en="See membership plans"',
				'data-bds-copy-en="See membership plans"',
				'data-bds-copy-es="Ver planes de membresía"',
			),
			$html
		);
		return $html;
	}
}
