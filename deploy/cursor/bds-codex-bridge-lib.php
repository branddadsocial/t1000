<?php
/**
 * Pure helpers for Cursor ↔ Codex conversion contracts.
 *
 * No WordPress, no checkout, no network. Geography and email proof fail closed.
 */

if ( ! function_exists( 'bds_codex_featured_skus' ) ) {
	/**
	 * Allowlisted Featured SKUs. Prices are the live targets; this helper does not create products.
	 *
	 * @return array<string,array{level:string,monthly:string,name:string}>
	 */
	function bds_codex_featured_skus() {
		return array(
			'bds-feat-local'    => array( 'level' => 'local', 'monthly' => '9.99', 'name' => 'Local Featured' ),
			'bds-feat-regional' => array( 'level' => 'regional', 'monthly' => '19.99', 'name' => 'Regional Featured' ),
			'bds-feat-national' => array( 'level' => 'national', 'monthly' => '39.99', 'name' => 'National Featured' ),
			'bds-feat-global'   => array( 'level' => 'global', 'monthly' => '99.99', 'name' => 'Global Homepage Featured' ),
		);
	}
}

if ( ! function_exists( 'bds_codex_consent_accepted' ) ) {
	/**
	 * Optional Directory membership checkbox. Unchecked and unknown values are false.
	 *
	 * @param mixed $value Raw POST value.
	 */
	function bds_codex_consent_accepted( $value ) {
		$raw = strtolower( trim( (string) $value ) );
		return in_array( $raw, array( '1', 'yes', 'true', 'on' ), true );
	}
}

if ( ! function_exists( 'bds_codex_listing_geo_from_meta' ) ) {
	/**
	 * Authoritative listing geography. Billing address is never an input.
	 *
	 * @param array<string,mixed> $meta Listing post meta.
	 * @return array{country:string,lat:?float,lng:?float}
	 */
	function bds_codex_listing_geo_from_meta( array $meta ) {
		$country = '';
		foreach ( array( 'country', '_country', 'address_country', 'iso2' ) as $key ) {
			if ( ! isset( $meta[ $key ] ) ) {
				continue;
			}
			$raw = $meta[ $key ];
			if ( is_array( $raw ) ) {
				$raw = isset( $raw[0] ) ? $raw[0] : '';
			}
			$try = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $raw ) );
			if ( preg_match( '/^[A-Z]{2}$/', $try ) ) {
				$country = $try;
				break;
			}
		}
		$lat = bds_codex_coord_from_meta( $meta, array( 'manual_lat', '_manual_lat', 'latitude', 'map_lat' ), -90, 90 );
		$lng = bds_codex_coord_from_meta( $meta, array( 'manual_lng', '_manual_lng', 'longitude', 'map_lng' ), -180, 180 );
		return array(
			'country' => $country,
			'lat'     => $lat,
			'lng'     => $lng,
		);
	}
}

if ( ! function_exists( 'bds_codex_coord_from_meta' ) ) {
	/**
	 * @param array<string,mixed> $meta
	 * @param array<int,string>   $keys
	 * @return float|null
	 */
	function bds_codex_coord_from_meta( array $meta, array $keys, $min, $max ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $meta[ $key ] ) ) {
				continue;
			}
			$raw = $meta[ $key ];
			if ( is_array( $raw ) ) {
				$raw = isset( $raw[0] ) ? $raw[0] : '';
			}
			if ( ! is_numeric( $raw ) ) {
				continue;
			}
			$num = (float) $raw;
			if ( $num >= (float) $min && $num <= (float) $max ) {
				return $num;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'bds_codex_featured_url_with_listing' ) ) {
	/**
	 * Paid Featured links must carry the claimed listing. Unknown ids stay off the URL.
	 *
	 * @param string $url
	 * @param mixed  $listing_id
	 */
	function bds_codex_featured_url_with_listing( $url, $listing_id ) {
		$url = (string) $url;
		$id  = is_numeric( $listing_id ) ? (int) $listing_id : 0;
		if ( '' === $url || $id < 1 ) {
			return $url;
		}
		$join = false !== strpos( $url, '?' ) ? '&' : '?';
		return $url . $join . 'listing=' . $id;
	}
}

if ( ! function_exists( 'bds_codex_email_verified_from_meta' ) ) {
	/**
	 * Fail closed unless a known verification proof is present. An email string is not proof.
	 *
	 * @param array<string,mixed> $meta User meta.
	 */
	function bds_codex_email_verified_from_meta( array $meta ) {
		$accepted = array( 'yes', '1', 'true' );
		foreach ( array( '_bds_email_verified', '_email_verified', 'is_email_verified' ) as $key ) {
			if ( ! isset( $meta[ $key ] ) ) {
				continue;
			}
			$raw = $meta[ $key ];
			if ( is_array( $raw ) ) {
				$raw = isset( $raw[0] ) ? $raw[0] : '';
			}
			if ( in_array( strtolower( trim( (string) $raw ) ), $accepted, true ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'bds_codex_lead_payload_ok' ) ) {
	/**
	 * Public lead form body before it is posted to Codex REST.
	 *
	 * @param array<string,mixed> $fields
	 * @return array{ok:bool,error?:string}
	 */
	function bds_codex_lead_payload_ok( array $fields ) {
		$email    = strtolower( trim( (string) ( $fields['email'] ?? '' ) ) );
		$privacy  = ! empty( $fields['privacy_ack'] );
		$business = (string) ( $fields['business_type'] ?? '' );
		$need     = (string) ( $fields['primary_need'] ?? '' );
		$source   = strtolower( preg_replace( '/[^a-z_]/', '', (string) ( $fields['source'] ?? '' ) ) );
		if ( ! $privacy ) {
			return array( 'ok' => false, 'error' => 'privacy' );
		}
		if ( strlen( $email ) > 120 || ! preg_match( '/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email ) ) {
			return array( 'ok' => false, 'error' => 'email' );
		}
		if ( strlen( $business ) > 120 || strlen( $need ) > 240 ) {
			return array( 'ok' => false, 'error' => 'length' );
		}
		if ( ! in_array( $source, array( 'help', 'trial', 'listing', 'audit', 'sales_chat' ), true ) ) {
			return array( 'ok' => false, 'error' => 'source' );
		}
		return array( 'ok' => true );
	}
}

if ( ! function_exists( 'bds_codex_form_reference_ok' ) ) {
	/**
	 * Opaque inquiry form references are 16–64 URL-safe characters.
	 *
	 * @param mixed $raw
	 */
	function bds_codex_form_reference_ok( $raw ) {
		return (bool) preg_match( '/^[A-Za-z0-9._-]{16,64}$/', (string) $raw );
	}
}

if ( ! function_exists( 'bds_codex_featured_listing_prompt' ) ) {
	/**
	 * Featured checkout needs a published listing id. Zero stays inactive.
	 *
	 * @param mixed $listing_id
	 * @return array{attached:bool,line:string}
	 */
	function bds_codex_featured_listing_prompt( $listing_id ) {
		$id = (int) $listing_id;
		if ( $id > 0 ) {
			return array(
				'attached' => true,
				'line'     => 'This Featured plan will attach to listing ' . $id . '. Payment still has to complete before a lane goes live.',
			);
		}
		return array(
			'attached' => false,
			'line'     => 'Open Featured from a published listing so checkout can attach. A plan without a listing stays inactive.',
		);
	}
}

if ( ! function_exists( 'bds_codex_checkout_failclosed_copy' ) ) {
	/**
	 * Public checkout copy. Does not claim “no card”, start a charge, or grant Directory membership.
	 *
	 * @return array{heading:string,lines:array<int,string>}
	 */
	function bds_codex_checkout_failclosed_copy() {
		return array(
			'heading' => 'Before you pay',
			'lines'   => array(
				'This checkout does not charge until you complete payment.',
				'The 7-day trial does not include Directory membership, Featured, or the 10% member price.',
				'Featured placement stays inactive until WooCommerce confirms payment.',
			),
		);
	}
}
