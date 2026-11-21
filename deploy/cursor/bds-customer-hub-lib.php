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
		$note    = 'This screen does not generate, schedule, or publish. Codex has not connected execution for this workspace.';
		$note_es = 'Esta pantalla no genera, programa ni publica. Codex no ha conectado la ejecución de este espacio.';
		return array(
			array(
				'id'        => 'seo',
				'title'     => 'SEO Autopilot',
				'title_es'  => 'Autopiloto SEO',
				'detail'    => 'You will connect a website, review drafts, and approve publishing here. This board is inquiry only. It does not start checkout. ' . $note,
				'detail_es' => 'Conectará un sitio, revisará borradores y aprobará la publicación aquí. Este tablero es solo consulta. No inicia checkout. ' . $note_es,
			),
			array(
				'id'        => 'social',
				'title'     => 'Social Studio',
				'title_es'  => 'Estudio social',
				'detail'    => 'The calendar will hold captions you approve before anything is sent. This board is inquiry only. It does not start checkout. ' . $note,
				'detail_es' => 'El calendario guardará textos que usted apruebe antes de enviar nada. Este tablero es solo consulta. No inicia checkout. ' . $note_es,
			),
			array(
				'id'        => 'linkedin',
				'title'     => 'LinkedIn Studio',
				'title_es'  => 'Estudio LinkedIn',
				'detail'    => 'Posts and comment drafts stay off until the account connection and platform permission exist. This board is inquiry only. It does not start checkout. ' . $note,
				'detail_es' => 'Los borradores de publicaciones y comentarios quedan apagados hasta existir la conexión y el permiso. Este tablero es solo consulta. No inicia checkout. ' . $note_es,
			),
			array(
				'id'        => 'chatbot',
				'title'     => 'Website chatbot',
				'title_es'  => 'Chat del sitio',
				'detail'    => 'Approved answers stay in draft until a site owner connects the assistant. This board is inquiry only. It does not start checkout. ' . $note,
				'detail_es' => 'Las respuestas aprobadas quedan en borrador hasta que el dueño conecte el asistente. Este tablero es solo consulta. No inicia checkout. ' . $note_es,
			),
			array(
				'id'        => 'video',
				'title'     => 'Video studio',
				'title_es'  => 'Estudio de video',
				'detail'    => 'No video is rendered or uploaded from this screen. This board is inquiry only. It does not start checkout. ' . $note,
				'detail_es' => 'Esta pantalla no renderiza ni sube video. Este tablero es solo consulta. No inicia checkout. ' . $note_es,
			),
		);
	}
}

if ( ! function_exists( 'bds_hub_dashboard_copy' ) ) {
	/**
	 * Logged-in dashboard copy. Does not start a charge or claim execution.
	 *
	 * @return array{heading:string,lines:array<int,string>}
	 */
	function bds_hub_dashboard_copy() {
		return array(
			'heading' => 'Your BrandDad dashboard',
			'lines'   => array(
				'This screen does not start a charge.',
				'A basic Directory listing is free. Featured is a separate monthly option.',
				'SEO, Social, and LinkedIn boards stay inquiry only until Codex connects execution.',
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

if ( ! function_exists( 'bds_hub_trial_view' ) ) {
	/**
	 * Map Codex trial state onto hub copy. Missing state stays unavailable.
	 * Does not invent credits or Directory membership.
	 *
	 * @param mixed $state Return value of bds_ai_trial_state(), or null.
	 * @return array{state:string,line:string,cta:bool}
	 */
	function bds_hub_trial_view( $state ) {
		if ( ! is_array( $state ) || empty( $state['status'] ) ) {
			return array(
				'state' => 'unavailable',
				'line'  => 'Sign in to see your AI Business Essentials trial status.',
				'cta'   => true,
			);
		}
		$status = strtolower( preg_replace( '/[^a-z0-9_]/', '', (string) $state['status'] ) );
		if ( 'eligible' === $status ) {
			return array(
				'state' => 'eligible',
				'line'  => 'Start the 7-day AI Business Essentials trial. Directory membership is not included.',
				'cta'   => true,
			);
		}
		if ( ! empty( $state['active'] ) || 'active' === $status ) {
			$days = isset( $state['days_remaining'] ) ? (int) $state['days_remaining'] : 0;
			$line = 'Your 7-day trial is active';
			if ( $days > 0 ) {
				$line .= '. ' . $days . ' days remaining';
			}
			return array(
				'state' => 'active',
				'line'  => $line . '. Directory membership is not included.',
				'cta'   => false,
			);
		}
		if ( 'paid' === $status ) {
			return array(
				'state' => 'paid',
				'line'  => 'AI Business Essentials is active on this account.',
				'cta'   => false,
			);
		}
		if ( 'consumed' === $status ) {
			return array(
				'state' => 'consumed',
				'line'  => 'This account already used the 7-day trial.',
				'cta'   => false,
			);
		}
		return array(
			'state' => 'unavailable',
			'line'  => 'Trial status is not available for this account.',
			'cta'   => true,
		);
	}
}

if ( ! function_exists( 'bds_hub_ops_inspect_view' ) ) {
	/**
	 * Shop-manager billing inspect. Booleans only — never a key, webhook URL, or pk.
	 *
	 * @param array<string,mixed> $snap
	 * @return array{state:string,lines:array<int,string>}
	 */
	function bds_hub_ops_inspect_view( array $snap ) {
		$on = static function ( $key ) use ( $snap ) {
			return ! empty( $snap[ $key ] );
		};
		$lines   = array();
		$lines[] = $on( 'stripe_gateway' )
			? 'Stripe gateway is enabled on this host (inspect only; no reconnect).'
			: 'Stripe gateway is not enabled on this host.';
		$lines[] = $on( 'woo_webhooks' )
			? 'WooCommerce has at least one active webhook. Delivery secrets stay in the dashboard.'
			: 'No active WooCommerce webhooks were counted. Stripe Dashboard delivery is an owner inspect.';
		$lines[] = $on( 'formwise_exec' )
			? 'FormWise execution key is defined (value not shown).'
			: 'FormWise execution key is missing. Owner supplies it. Cursor will not invent it.';
		$lines[] = $on( 'formwise_prov' )
			? 'FormWise provisioning key is defined (value not shown).'
			: 'FormWise provisioning key is missing. Owner supplies it. Cursor will not invent it.';
		$flag_labels = array(
			'followup_flag'        => 'Follow-up mail',
			'leads_flag'           => 'Lead writes',
			'inquiries_flag'       => 'Inquiry notices',
			'monitor_flag'         => 'Monitor',
			'sales_assistant_flag' => 'Sales assistant',
		);
		$any_flag = false;
		foreach ( $flag_labels as $key => $label ) {
			if ( $on( $key ) ) {
				$any_flag = true;
				$lines[]  = $label . ' flag is on. Leave it off until FormWise keys and mail gates pass.';
			} else {
				$lines[] = $label . ' flag is off.';
			}
		}
		$lines[] = $on( 'entitlements_flag' )
			? 'Entitlements flag is on; FormWise lifecycle still fail-closes without keys.'
			: 'Entitlements flag is off.';
		$keys = $on( 'formwise_exec' ) && $on( 'formwise_prov' );
		if ( $any_flag && ! $keys ) {
			$state = 'unsafe';
		} elseif ( ! $keys ) {
			$state = 'owner_blocked';
		} elseif ( $any_flag ) {
			$state = 'flags_on';
		} else {
			$state = 'keys_present_flags_off';
		}
		return array(
			'state' => $state,
			'lines' => $lines,
		);
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
				array( 'label' => 'List my business free', 'label_es' => 'Listar mi negocio gratis', 'host' => 'directory', 'path' => '/add-listing/' ),
				array( 'label' => 'See membership plans', 'label_es' => 'Ver planes de membresía', 'host' => 'directory', 'path' => '/select-your-plan/' ),
				array( 'label' => 'Get featured', 'label_es' => 'Salir destacado', 'host' => 'directory', 'path' => '/featured-advertising/' ),
				array( 'label' => 'How to claim a listing', 'label_es' => 'Cómo reclamar un listado', 'host' => 'directory', 'path' => '/claim-your-business-listing-branddad-directory/' ),
			);
		}
		return array(
			array( 'label' => 'Try it free for 7 days', 'label_es' => 'Pruébelo 7 días', 'host' => 'social', 'path' => '/product/ai-business-essentials/' ),
			array( 'label' => 'Subscribe to AI Social Growth', 'label_es' => 'Suscribirse a AI Social Growth', 'host' => 'social', 'path' => '/product/ai-social-growth/' ),
			array( 'label' => 'See all services', 'label_es' => 'Ver todos los servicios', 'host' => 'social', 'path' => '/services/' ),
			array( 'label' => 'Run the free audit', 'label_es' => 'Hacer la auditoría gratis', 'host' => 'social', 'path' => '/check-your-website/' ),
			array( 'label' => 'List my business free', 'label_es' => 'Listar mi negocio gratis', 'host' => 'directory', 'path' => '/add-listing/' ),
			array( 'label' => 'Get featured on Directory', 'label_es' => 'Salir destacado en el directorio', 'host' => 'directory', 'path' => '/featured-advertising/' ),
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
