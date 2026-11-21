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
		if ( 'national' === $level ) {
			return '' !== $card_country && $card_country === $viewer_country;
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
			'events'       => 'events',
			'jobs'         => 'jobs',
			'jobs-careers' => 'jobs',
			'classifieds'  => 'classifieds',
			'travel'       => 'travel',
			'hotels-stays' => 'hotels',
			'hotels'       => 'hotels',
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
