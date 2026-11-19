<?php
/**
 * Pure view models for BrandDad customer screens.
 *
 * No WordPress, no checkout, and no invented balances or commissions.
 */

if ( ! function_exists( 'bds_hub_membership_view' ) ) {
	/**
	 * @param string               $tier guest|free|activation|pro|elite or unknown.
	 * @param array<string,mixed> $caps Entitlement row from the existing membership catalog.
	 * @return array{tier:string,label:string,included:array<int,string>,upgrade:bool}
	 */
	function bds_hub_membership_view( $tier, array $caps ) {
		$tier  = strtolower( (string) $tier );
		$known = array( 'guest', 'free', 'activation', 'pro', 'elite' );
		if ( ! in_array( $tier, $known, true ) ) {
			$tier = 'guest';
		}
		$label = isset( $caps['label'] ) && is_string( $caps['label'] ) && '' !== $caps['label']
			? $caps['label']
			: ucfirst( $tier );
		$map = array(
			'account_control_draft'   => 'Save listing drafts',
			'account_control_publish' => 'Publish from the account desk',
			'full_metrics'            => 'Full listing metrics',
			'network_discount'        => 'Network member discount where a live product already allows it',
			'travel_search'           => 'Travel search perk',
		);
		$included = array();
		foreach ( $map as $key => $text ) {
			if ( ! empty( $caps[ $key ] ) ) {
				$included[] = $text;
			}
		}
		if ( isset( $caps['max_connects'] ) && (int) $caps['max_connects'] > 0 ) {
			$included[] = (int) $caps['max_connects'] . ' connected listings in this tier record';
		}
		return array(
			'tier'     => $tier,
			'label'    => $label,
			'included' => $included,
			'upgrade'  => in_array( $tier, array( 'guest', 'free', 'activation' ), true ),
		);
	}
}

if ( ! function_exists( 'bds_hub_free_ai_view' ) ) {
	/**
	 * @param mixed $status Return value of bds_dir_free_ai_status(), or null when the ledger is absent.
	 * @return array{state:string,period:string,lines:array<int,string>}
	 */
	function bds_hub_free_ai_view( $status ) {
		if ( ! is_array( $status ) || empty( $status['tools'] ) || ! is_array( $status['tools'] ) ) {
			return array(
				'state'  => 'unavailable',
				'period' => '',
				'lines'  => array( 'No verified free-AI balance is available for this account.' ),
			);
		}
		$lines = array();
		foreach ( $status['tools'] as $tool ) {
			if ( ! is_array( $tool ) || ! isset( $tool['label'], $tool['remaining'], $tool['limit'] ) ) {
				continue;
			}
			$lines[] = $tool['label'] . ': ' . (int) $tool['remaining'] . ' of ' . (int) $tool['limit'] . ' remaining';
		}
		if ( ! $lines ) {
			return array(
				'state'  => 'unavailable',
				'period' => '',
				'lines'  => array( 'No verified free-AI balance is available for this account.' ),
			);
		}
		return array(
			'state'  => 'ready',
			'period' => isset( $status['period'] ) ? (string) $status['period'] : '',
			'lines'  => $lines,
		);
	}
}

if ( ! function_exists( 'bds_hub_studio_cards' ) ) {
	/**
	 * Frontends only. Execution stays with Codex and is not claimed here.
	 *
	 * @return array<int,array{id:string,title:string,detail:string}>
	 */
	function bds_hub_studio_cards() {
		$note = 'This screen does not generate, schedule, or publish. Codex has not connected execution for this workspace.';
		return array(
			array(
				'id'     => 'seo',
				'title'  => 'SEO Autopilot',
				'detail' => 'You will connect a website, review drafts, and approve publishing here. ' . $note,
			),
			array(
				'id'     => 'social',
				'title'  => 'Social Studio',
				'detail' => 'The calendar will hold captions you approve before anything is sent. ' . $note,
			),
			array(
				'id'     => 'linkedin',
				'title'  => 'LinkedIn Studio',
				'detail' => 'Posts and comment drafts stay off until the account connection and platform permission exist. ' . $note,
			),
			array(
				'id'     => 'chatbot',
				'title'  => 'Website chatbot',
				'detail' => 'Approved answers stay in draft until a site owner connects the assistant. ' . $note,
			),
			array(
				'id'     => 'video',
				'title'  => 'Video studio',
				'detail' => 'No video is rendered or uploaded from this screen. ' . $note,
			),
		);
	}
}

if ( ! function_exists( 'bds_hub_affiliate_copy' ) ) {
	/** Do not invent a commission rate. Partnero remains the source of truth. */
	function bds_hub_affiliate_copy() {
		return 'Referral links, conversions, commissions, and payouts are shown in the existing Partnero portal. This page does not calculate a rate or a balance.';
	}
}

if ( ! function_exists( 'bds_hub_music_rows' ) ) {
	/**
	 * @param array<int,array{title:string,status:string}> $orders
	 * @return array<int,array{title:string,status:string}>
	 */
	function bds_hub_music_rows( array $orders ) {
		$rows = array();
		foreach ( $orders as $order ) {
			$title = isset( $order['title'] ) ? trim( (string) $order['title'] ) : '';
			if ( '' === $title ) {
				continue;
			}
			$rows[] = array(
				'title'  => $title,
				'status' => 'Brief saved. Payment not collected. Production not started.',
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'bds_hub_membership_compare' ) ) {
	/**
	 * Comparison rows from an existing entitlement catalog. Prices are not added here.
	 *
	 * @param array<string,array<string,mixed>> $entitlements
	 * @return array<int,array{tier:string,label:string,included:array<int,string>}>
	 */
	function bds_hub_membership_compare( array $entitlements ) {
		$rows = array();
		foreach ( array( 'guest', 'free', 'activation', 'pro', 'elite' ) as $tier ) {
			if ( ! isset( $entitlements[ $tier ] ) || ! is_array( $entitlements[ $tier ] ) ) {
				continue;
			}
			$view   = bds_hub_membership_view( $tier, $entitlements[ $tier ] );
			$rows[] = array(
				'tier'     => $view['tier'],
				'label'    => $view['label'],
				'included' => $view['included'],
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'bds_hub_saved_list' ) ) {
	/**
	 * @param array<int,mixed> $ids
	 * @return array<int,int>
	 */
	function bds_hub_saved_list( array $ids ) {
		$clean = array();
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, $clean, true ) ) {
				$clean[] = $id;
			}
		}
		return array_slice( $clean, 0, 50 );
	}
}

if ( ! function_exists( 'bds_hub_saved_change' ) ) {
	/**
	 * Logged-in list mutation. The caller must already have proven the current user.
	 *
	 * @param array<int,mixed> $ids
	 * @return array{ok:bool,ids:array<int,int>,reason:string}
	 */
	function bds_hub_saved_change( array $ids, $listing_id, $action ) {
		$clean      = bds_hub_saved_list( $ids );
		$listing_id = (int) $listing_id;
		$action     = (string) $action;
		if ( $listing_id < 1 ) {
			return array( 'ok' => false, 'ids' => $clean, 'reason' => 'invalid_listing' );
		}
		if ( 'remove' === $action ) {
			$clean = array_values(
				array_filter(
					$clean,
					static function ( $id ) use ( $listing_id ) {
						return $id !== $listing_id;
					}
				)
			);
			return array( 'ok' => true, 'ids' => $clean, 'reason' => 'removed' );
		}
		if ( 'save' !== $action ) {
			return array( 'ok' => false, 'ids' => $clean, 'reason' => 'invalid_action' );
		}
		if ( in_array( $listing_id, $clean, true ) ) {
			return array( 'ok' => true, 'ids' => $clean, 'reason' => 'already_saved' );
		}
		if ( count( $clean ) >= 50 ) {
			return array( 'ok' => false, 'ids' => $clean, 'reason' => 'limit' );
		}
		$clean[] = $listing_id;
		return array( 'ok' => true, 'ids' => $clean, 'reason' => 'saved' );
	}
}

if ( ! function_exists( 'bds_hub_next_actions' ) ) {
	/**
	 * Next converting doors. Paths only — the MU prefixes the live host.
	 *
	 * @return array<int,array{label:string,host:string,path:string}>
	 */
	function bds_hub_next_actions( $surface ) {
		$surface = strtolower( (string) $surface );
		if ( 'directory' === $surface ) {
			return array(
				array( 'label' => 'List my business', 'host' => 'directory', 'path' => '/add-listing/' ),
				array( 'label' => 'See membership plans', 'host' => 'directory', 'path' => '/select-your-plan/' ),
				array( 'label' => 'Get featured', 'host' => 'directory', 'path' => '/featured-advertising/' ),
				array( 'label' => 'How to claim a listing', 'host' => 'directory', 'path' => '/claim-your-business-listing-branddad-directory/' ),
			);
		}
		return array(
			array( 'label' => 'Subscribe to AI Social Growth', 'host' => 'social', 'path' => '/product/ai-social-growth/' ),
			array( 'label' => 'See all services', 'host' => 'social', 'path' => '/services/' ),
			array( 'label' => 'Run the free audit', 'host' => 'social', 'path' => '/check-your-website/' ),
			array( 'label' => 'List a business on Directory', 'host' => 'directory', 'path' => '/add-listing/' ),
			array( 'label' => 'Get featured on Directory', 'host' => 'directory', 'path' => '/featured-advertising/' ),
		);
	}
}

if ( ! function_exists( 'bds_seo_emit_decision' ) ) {
	/**
	 * Refuse a second canonical, and refuse a new alternate for a Directory city URL.
	 * BrandDad Global already prints hreflang and canonical. This helper does not print tags.
	 *
	 * @param array<int,string> $existing_rels
	 * @return array{emit:bool,reason:string}
	 */
	function bds_seo_emit_decision( array $existing_rels, $candidate_path ) {
		$rels = array();
		foreach ( $existing_rels as $rel ) {
			$rels[] = strtolower( (string) $rel );
		}
		if ( in_array( 'canonical', $rels, true ) ) {
			return array( 'emit' => false, 'reason' => 'canonical_exists' );
		}
		$parts = array_values( array_filter( explode( '/', trim( (string) $candidate_path, '/' ) ) ) );
		if ( count( $parts ) >= 3 ) {
			return array( 'emit' => false, 'reason' => 'city_page' );
		}
		return array( 'emit' => true, 'reason' => 'no_existing_tag' );
	}
}
