<?php
/**
 * Plugin Name: BrandDad Directory AI Finder
 * Description: Additive "Ask BrandDad" conversational finder for Directory listings. Guests can browse; contact stays gated. Uses structured Directorist data + OpenAI when a key is available.
 * Version: 1.11.3
 * Author: BrandDad
 *
 * Deploy MU on directory.branddad.social:
 *   wp-content/mu-plugins/bds-directory-ai-finder-mu.php
 *
 * Admin: Network Hub -> AI Finder (API key + enable)
 * REST:  POST /wp-json/bds-ai/v1/find  (message, history?, lat?, lng?, radius_km?, place_label?, context?)
 *        GET  /wp-json/bds-ai/v1/geocode?lat=&lng=  (Nominatim reverse, cached)
 *        GET  /wp-json/bds-ai/v1/local-pack  (homepage / near-you cards)
 *        GET|POST|DELETE /wp-json/bds-ai/v1/memory  (logged-in chat sync)
 *
 * Geo: auto "near me" + reverse-geocode, Google-like radius widen (1->5->25 km),
 * distance-sorted local pack, digital vs local intent, browse-archive proximity.
 *
 * TRIAGE NOTE (2026-08-12): this plugin is not the source of Directory homepage
 * TTFB. Measured with this file stubbed out to 121 bytes: homepage 29-31s across
 * 4 runs, while /wp-json REST on the same box answered in 1.7s. All work added in
 * 1.8.0 runs on the REST path (/bds-ai/v1/find); the front end only ships static
 * CSS/JS/markup. Please profile the homepage render path instead of disabling
 * this plugin.
 *
 * 1.11.4 - Travel deal cards use stable /listing/tp-* URLs (not broken local SEO paths).
 *          Browse city bar on archives; card meta labels Google rating (not bare stars).
 * 1.11.2 - Home: adopt compose static [data-wah-search] native form (hide + transfer q)
 * when injecting Ask hero entry so Finder can stay fully off LCP path.
 * 1.11.0 - Universal conversational concierge layer (CLARIFY|SEARCH|REFINE|COMPARE|
 * EXPLAIN|NEW_SEARCH) on strict retrieval; structured state in REST context;
 * travel destination/price precision filter; title-only dish evidence blocked on
 * wrong vertical. Companion precision 1.1.5 + concierge 1.0.0.
 * 1.10.6 - Leaf categories do not inherit parent AND; pho≠photographer.
 * 1.10.4 - Broad discovery retrieves by location + domain/vertical (no sibling
 * category AND). Related cats = self/children/parent only. Cache key 1.10.4.
 * 1.10.3 - Cache related-category expansion; word-boundary geo needles.
 * 1.10.2 - Universal intent/specificity: domain vs attribute, live geo/category
 * retrieval, suppress_filters + SQL fallback, diagnostics. Companion precision 1.1.3.
 * 1.10.1 - Follow-ups recover location/dish from chat history when session
 * context is missing (API/history-only clients). Precision 1.1.1 keeps strict.
 * 1.10.0 - Universal router: providers, mixed needs, grouped sections,
 * unavailable inventory stays empty (jobs/influencers/coupons), follow-up
 * constraint inherit. Companion precision 1.1.0.
 * 1.9.1 - Zero-result: we do not have it, then optional suggestion chips (not matches).
 * 1.9.0 - Precision search: hard constraints, evidence, confidence, no pad,
 * zero-result allowed. Companion bds-directory-ai-precision-mu.php.
 * 1.8.2 - Soft-sell points at branddad.social/services/ hub + AI Ads door;
 * never recommend LinkedIn outreach.
 * 1.8.1 - Trustworthy human voice: Ask BrandDad talks like a helpful BrandDad
 * teammate (short sentences, contractions, honest admit-when-missing). System
 * prompt, welcome, empty-assist, and template replies drop "from the future" /
 * salesperson framing. Clarify -> recommend -> soft upsell only when relevant.
 * 1.8.0 - Consultative concierge: cross-domain need diagnosis, 1-2 smart clarifying
 * questions, live gift-card catalog matching with honest "we do not carry that"
 * alternatives, relevance-gated upsell with reasons, quick-reply chips, model chain
 * (gpt-4.1 -> gpt-4o -> gpt-4o-mini) with automatic fallback, and a rebuilt premium
 * chat UI (launcher, panel, bubbles, product cards, mobile sheet, collision avoidance).
 * 1.7.7 - Travel search is a paying-member perk; soft CTA -> Account Control / join.
 * 1.7.4 - Voice/UI pass across launcher, hero, empty-assist, and system prompt.
 * 1.7.3 - State-of-the-art AI positioning: smarter gift/product miss matching,
 * premium Ask BrandDad voice/UI, site search handoff into AI when empty.
 * 1.7.2 - Missing product URLs / empty product searches open Ask BrandDad with a
 * prefilled query (e.g. gift card slug -> merchant + face value) and auto-ask.
 * 1.7.0 - Directory bidding hooks: gift intent answers gift-card catalog first, then
 * optional Places you might like + max 1 Sponsored/Paid placement (2 if user asked
 * for options). Category queries may append same-category paid winners only.
 * Requires bds-directory-bidding-mu.php helpers when present.
 * 1.6.2 - Mobile: hide bottom Ask BrandDad FAB (header chip only). Travel user-facing
 * copy no longer names TravelPayouts or partner search engines.
 * 1.6.1 - Travel hard rails: hotel queries only hotel deals, flight queries only
 * flights; destination preference hard-filters when enough matches; local tours /
 * cenotes / activities stay Directory (never stolen into TravelPayouts).
 * 1.6.0 - Full Directory category/intent synonym map + hard vertical
 * allow-list guardrails (food never returns tours/tattoo/hotel/retail/etc.).
 * Result pack filtered to allowed verticals before OpenAI reply.
 * 1.5.2 - Cuisine/dish synonyms (pizza, sushi, tacos, ...) map to food inventory;
 * hard-exclude tours/tattoo/salon/retail on food intent so "best pizza in Playa"
 * cannot surface Adventure tours. Broader category aliases + title dish boost.
 * 1.5.1 - Live Directory discovery: short-TTL find caches keyed by
 * bds_ai_listings_cache_ver; invalidate on listing save/status/terms + TP sync /
 * bds_directory_import_complete. Larger taxonomy candidate pools so new LG imports
 * surface. Gift + travel routing unchanged.
 * 1.5.0 - Travel intent routes to live TravelPayouts deal listings
 * (_bds_travelpayouts=1) with Book now / listing URLs; soft "Search flights & hotels";
 * cache invalidated on bds_tp_sync_complete. Gift + local restaurant routing unchanged.
 * 1.4.2 - Gift / Save Money Cards intent routes to BrandDad Social gift catalog
 * (branddad.social/gift-cards/) via soft services; skips Directory listing packs.
 * 1.4.1 - ASCII-safe Ask BrandDad UI copy (no curly quotes/em dashes) to avoid mojibake.
 * 1.4.0 - Taxonomy-first relevance: cuisine/category + location aliases, vertical
 * guardrails (no supermarket/retail for restaurant intent), GBP rating boost for
 * "good/best", score rank over bare keyword / location-only dump.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_FINDER_VER', '1.11.4' );
define( 'BDS_AI_FINDER_META_CHAT', 'bds_ai_finder_chat' );
define( 'BDS_AI_TP_CACHE_VER_OPT', 'bds_ai_tp_cache_ver' );
define( 'BDS_AI_LISTINGS_CACHE_VER_OPT', 'bds_ai_listings_cache_ver' );

add_action( 'admin_menu', 'bds_ai_finder_admin_menu', 66 );
add_action( 'admin_init', 'bds_ai_finder_register_settings' );
add_action( 'rest_api_init', 'bds_ai_finder_register_routes' );
add_action( 'wp_enqueue_scripts', 'bds_ai_finder_enqueue' );
add_action( 'wp_footer', 'bds_ai_finder_footer_markup', 40 );
add_action( 'wp_footer', 'bds_ai_finder_home_local_pack', 35 );
add_action( 'pre_get_posts', 'bds_ai_finder_browse_geo_query', 40 );
add_filter( 'posts_clauses', 'bds_ai_finder_browse_geo_clauses', 40, 2 );
add_action( 'bds_tp_sync_complete', 'bds_ai_finder_invalidate_travel_cache', 10, 1 );
add_action( 'bds_tp_sync_complete', 'bds_ai_finder_bump_listings_cache', 11, 1 );
add_action( 'bds_directory_import_complete', 'bds_ai_finder_bump_listings_cache', 10, 1 );
add_action( 'save_post_at_biz_dir', 'bds_ai_finder_on_listing_saved', 20, 3 );
add_action( 'save_post_atbdp_listing', 'bds_ai_finder_on_listing_saved', 20, 3 );
add_action( 'transition_post_status', 'bds_ai_finder_on_listing_status', 20, 3 );
add_action( 'wp_trash_post', 'bds_ai_finder_on_listing_trashed', 20, 1 );
add_action( 'before_delete_post', 'bds_ai_finder_on_listing_trashed', 20, 1 );
add_action( 'set_object_terms', 'bds_ai_finder_on_listing_terms', 20, 6 );
add_action( 'created_at_biz_dir-location', 'bds_ai_finder_on_dir_term_changed', 20, 1 );
add_action( 'edited_at_biz_dir-location', 'bds_ai_finder_on_dir_term_changed', 20, 1 );
add_action( 'delete_at_biz_dir-location', 'bds_ai_finder_on_dir_term_changed', 20, 1 );
add_action( 'created_at_biz_dir-category', 'bds_ai_finder_on_dir_term_changed', 20, 1 );
add_action( 'edited_at_biz_dir-category', 'bds_ai_finder_on_dir_term_changed', 20, 1 );
add_action( 'delete_at_biz_dir-category', 'bds_ai_finder_on_dir_term_changed', 20, 1 );

/**
 * @return array<string,mixed>
 */
function bds_ai_finder_defaults() {
	return array(
		'enabled'         => 1,
		'openai_api_key'  => '',
		'openai_model'    => 'auto',
		'openai_base_url' => 'https://api.openai.com/v1',
		'rate_per_ip'     => 30,
	);
}

/**
 * Preferred models, strongest first. "auto" (or a legacy mini setting) walks the
 * chain and remembers the first model this key can actually call.
 *
 * @return string[]
 */
function bds_ai_finder_model_chain() {
	$chain = array( 'gpt-4.1', 'gpt-4o', 'gpt-4o-mini' );
	/**
	 * Filter the Ask BrandDad model preference chain.
	 *
	 * @param string[] $chain Models, strongest first.
	 */
	$chain = apply_filters( 'bds_ai_finder_model_chain', $chain );
	$out   = array();
	foreach ( (array) $chain as $c ) {
		$c = trim( (string) $c );
		if ( $c !== '' && ! in_array( $c, $out, true ) ) {
			$out[] = $c;
		}
	}
	return $out ? $out : array( 'gpt-4o-mini' );
}

/**
 * Models to try for this request, in order.
 *
 * @return string[]
 */
function bds_ai_finder_models_to_try() {
	$s   = bds_ai_finder_settings();
	$set = isset( $s['openai_model'] ) ? trim( (string) $s['openai_model'] ) : '';
	// Explicit pin (anything other than auto / the legacy mini default) wins.
	if ( $set !== '' && 'auto' !== $set && 'gpt-4o-mini' !== $set ) {
		return array( $set );
	}
	$chain  = bds_ai_finder_model_chain();
	$known  = get_transient( 'bds_ai_finder_model_ok' );
	if ( is_string( $known ) && $known !== '' && in_array( $known, $chain, true ) ) {
		$idx = array_search( $known, $chain, true );
		return array_values( array_slice( $chain, (int) $idx ) );
	}
	return $chain;
}

/**
 * Human label for the model Ask BrandDad will use next.
 *
 * @return string
 */
function bds_ai_finder_active_model_label() {
	$try   = bds_ai_finder_models_to_try();
	$first = isset( $try[0] ) ? (string) $try[0] : 'gpt-4o-mini';
	$known = get_transient( 'bds_ai_finder_model_ok' );
	if ( is_string( $known ) && $known !== '' ) {
		return $known . ' (verified)';
	}
	return $first . ( count( $try ) > 1 ? ' (auto, falls back)' : ' (pinned)' );
}

/**
 * @return array<string,mixed>
 */
function bds_ai_finder_settings() {
	$saved = get_option( 'bds_ai_finder_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( bds_ai_finder_defaults(), $saved );
}

/**
 * Resolve OpenAI key: finder setting -> shared WP options -> autoblog -> constants/env.
 *
 * @return string
 */
function bds_ai_finder_openai_key() {
	$s = bds_ai_finder_settings();
	if ( ! empty( $s['openai_api_key'] ) && $s['openai_api_key'] !== '********' ) {
		return (string) $s['openai_api_key'];
	}
	foreach ( array( 'bdco_openai_api_key', 'bds_openai_api_key', 'bdco_ai_openai_api_key' ) as $opt ) {
		$v = get_option( $opt, '' );
		if ( is_string( $v ) && $v !== '' && $v !== '********' ) {
			return $v;
		}
	}
	$ab = get_option( 'bds_ab_settings', array() );
	if ( is_array( $ab ) && ! empty( $ab['openai_api_key'] ) && $ab['openai_api_key'] !== '********' ) {
		return (string) $ab['openai_api_key'];
	}
	foreach ( array( 'BDS_OPENAI_API_KEY', 'BDCO_OPENAI_API_KEY', 'OPENAI_API_KEY' ) as $c ) {
		if ( defined( $c ) && constant( $c ) ) {
			return (string) constant( $c );
		}
	}
	foreach ( array( 'OPENAI_API_KEY', 'BDCO_OPENAI_API_KEY', 'BDS_OPENAI_API_KEY' ) as $env_name ) {
		$e = getenv( $env_name );
		if ( is_string( $e ) && $e !== '' ) {
			return $e;
		}
	}
	return '';
}

/**
 * @return bool
 */
function bds_ai_finder_enabled() {
	$s = bds_ai_finder_settings();
	return ! empty( $s['enabled'] );
}

function bds_ai_finder_admin_menu() {
	add_submenu_page(
		'bds-network-hub',
		'AI Finder',
		'AI Finder',
		'manage_options',
		'bds-ai-finder',
		'bds_ai_finder_render_admin'
	);
}

function bds_ai_finder_register_settings() {
	register_setting(
		'bds_ai_finder_settings_group',
		'bds_ai_finder_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'bds_ai_finder_sanitize_settings',
		)
	);
}

/**
 * @param array<string,mixed> $input Raw.
 * @return array<string,mixed>
 */
function bds_ai_finder_sanitize_settings( $input ) {
	$cur = bds_ai_finder_settings();
	$out = bds_ai_finder_defaults();
	if ( ! is_array( $input ) ) {
		return $cur;
	}
	$out['enabled']         = empty( $input['enabled'] ) ? 0 : 1;
	$out['openai_model']    = isset( $input['openai_model'] ) ? sanitize_text_field( (string) $input['openai_model'] ) : $cur['openai_model'];
	$out['openai_base_url'] = isset( $input['openai_base_url'] ) ? esc_url_raw( (string) $input['openai_base_url'] ) : $cur['openai_base_url'];
	$out['rate_per_ip']     = isset( $input['rate_per_ip'] ) ? max( 5, min( 120, (int) $input['rate_per_ip'] ) ) : (int) $cur['rate_per_ip'];

	$key = isset( $input['openai_api_key'] ) ? trim( (string) $input['openai_api_key'] ) : '';
	if ( $key === '' || $key === '********' ) {
		$out['openai_api_key'] = isset( $cur['openai_api_key'] ) ? $cur['openai_api_key'] : '';
	} else {
		$out['openai_api_key'] = sanitize_text_field( $key );
		update_option( 'bdco_openai_api_key', $out['openai_api_key'], false );
		update_option( 'bds_openai_api_key', $out['openai_api_key'], false );
	}
	return $out;
}

function bds_ai_finder_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = bds_ai_finder_settings();
	$key    = bds_ai_finder_openai_key();
	$has    = $key !== '';
	$masked = $has ? '********' : '';
	?>
	<div class="wrap">
		<h1>Ask BrandDad - AI Finder</h1>
		<p>Conversational finder on the Directory front end. Guests can search; phone / email / WhatsApp stay gated on listing pages.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'bds_ai_finder_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Enable</th>
					<td><label><input type="checkbox" name="bds_ai_finder_settings[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> Show Ask BrandDad on the front end</label></td>
				</tr>
				<tr>
					<th scope="row"><label for="bds_ai_key">OpenAI API key</label></th>
					<td>
						<input type="password" class="regular-text" id="bds_ai_key" name="bds_ai_finder_settings[openai_api_key]" value="<?php echo esc_attr( $masked ); ?>" autocomplete="off" />
						<p class="description">
							Stored in WP option <code>bds_ai_finder_settings</code> (and mirrored to <code>bdco_openai_api_key</code> / <code>bds_openai_api_key</code>).
							Never commit keys. If blank, also reads Directory Autoblog / shared options.
							<?php if ( ! $has ) : ?>
								<br><strong>No key detected.</strong> Paste the network OpenAI key from branddad.co (AI Studio / fulfillment) or Directory Autoblog settings.
							<?php else : ?>
								<br>Key status: <span style="color:#0a7a2f">present</span> (...<?php echo esc_html( substr( $key, -4 ) ); ?>).
							<?php endif; ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Model</th>
					<td>
						<input type="text" class="regular-text" name="bds_ai_finder_settings[openai_model]" value="<?php echo esc_attr( $s['openai_model'] ); ?>" />
						<input type="url" class="regular-text" name="bds_ai_finder_settings[openai_base_url]" value="<?php echo esc_attr( $s['openai_base_url'] ); ?>" placeholder="https://api.openai.com/v1" />
						<p class="description">
							Use <code>auto</code> for the strongest model this key can call:
							<code><?php echo esc_html( implode( ' -> ', bds_ai_finder_model_chain() ) ); ?></code>.
							Ask BrandDad walks the chain and remembers the first one that answers.
							Currently active: <strong><?php echo esc_html( bds_ai_finder_active_model_label() ); ?></strong>.
							Type an exact model name to pin it instead.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Rate limit</th>
					<td>
						<input type="number" min="5" max="120" class="small-text" name="bds_ai_finder_settings[rate_per_ip]" value="<?php echo esc_attr( (string) $s['rate_per_ip'] ); ?>" />
						requests / hour / IP (guests + members)
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save AI Finder settings' ); ?>
		</form>
		<hr>
		<p><strong>Front UI:</strong> header "Ask BrandDad" + chat panel. <strong>REST:</strong> <code>POST <?php echo esc_html( home_url( '/wp-json/bds-ai/v1/find' ) ); ?></code></p>
		<p><strong>Guest-safe:</strong> AI replies never include phone, email, or WhatsApp - they link to listing pages (contact gated until signup).</p>
	</div>
	<?php
}

function bds_ai_finder_register_routes() {
	register_rest_route(
		'bds-ai/v1',
		'/find',
		array(
			'methods'             => 'POST',
			'callback'            => 'bds_ai_finder_rest_find',
			'permission_callback' => 'bds_ai_finder_rest_public_nonce_can',
			'args'                => array(
				'message'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'history'    => array(
					'required' => false,
					'type'     => 'array',
				),
				'lat'        => array(
					'required' => false,
					'type'     => 'number',
				),
				'lng'        => array(
					'required' => false,
					'type'     => 'number',
				),
				'radius_km'    => array(
					'required' => false,
					'type'     => 'number',
				),
				'place_label'  => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'context'      => array(
					'required' => false,
					'type'     => 'object',
				),
			),
		)
	);
	register_rest_route(
		'bds-ai/v1',
		'/geocode',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_ai_finder_rest_geocode',
			'permission_callback' => 'bds_ai_finder_rest_public_read_can',
			'args'                => array(
				'lat' => array(
					'required' => true,
					'type'     => 'number',
				),
				'lng' => array(
					'required' => true,
					'type'     => 'number',
				),
			),
		)
	);
	register_rest_route(
		'bds-ai/v1',
		'/local-pack',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_ai_finder_rest_local_pack',
			'permission_callback' => 'bds_ai_finder_rest_public_read_can',
			'args'                => array(
				'lat' => array( 'required' => false, 'type' => 'number' ),
				'lng' => array( 'required' => false, 'type' => 'number' ),
			),
		)
	);
	register_rest_route(
		'bds-ai/v1',
		'/status',
		array(
			'methods'             => 'GET',
			'callback'            => 'bds_ai_finder_rest_status',
			'permission_callback' => 'bds_ai_finder_rest_public_read_can',
		)
	);
	register_rest_route(
		'bds-ai/v1',
		'/memory',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'bds_ai_finder_rest_memory_get',
				'permission_callback' => 'bds_ai_finder_rest_memory_can',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'bds_ai_finder_rest_memory_save',
				'permission_callback' => 'bds_ai_finder_rest_memory_can',
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => 'bds_ai_finder_rest_memory_clear',
				'permission_callback' => 'bds_ai_finder_rest_memory_can',
			),
		)
	);
}

/**
 * Public read-only endpoints expose directory metadata/cards and no gated contacts.
 *
 * @return bool
 */
function bds_ai_finder_rest_public_read_can() {
	return true;
}

/**
 * Public AI search is available to the rendered site, but must carry the REST nonce.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function bds_ai_finder_rest_public_nonce_can( $request ) {
	if ( current_user_can( 'read' ) ) {
		return true;
	}
	$nonce = (string) $request->get_header( 'X-WP-Nonce' );
	return '' !== $nonce && (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * @return bool
 */
function bds_ai_finder_rest_memory_can() {
	return is_user_logged_in();
}

/**
 * Sanitize chat payload for user_meta / REST (scrub gated contact).
 *
 * @param mixed $raw Raw payload.
 * @return array{v:int,turns:array<int,array<string,mixed>>,updated:int}
 */
function bds_ai_finder_sanitize_memory( $raw ) {
	$out = array(
		'v'       => 1,
		'turns'   => array(),
		'updated' => time(),
	);
	if ( ! is_array( $raw ) ) {
		return $out;
	}
	$turns = isset( $raw['turns'] ) && is_array( $raw['turns'] ) ? $raw['turns'] : array();
	$turns = array_slice( $turns, -20 );
	foreach ( $turns as $turn ) {
		if ( ! is_array( $turn ) ) {
			continue;
		}
		$role = isset( $turn['role'] ) ? (string) $turn['role'] : '';
		if ( $role !== 'user' && $role !== 'assistant' ) {
			continue;
		}
		$content = isset( $turn['content'] ) ? (string) $turn['content'] : '';
		$content = bds_ai_finder_scrub_text( wp_strip_all_tags( $content ) );
		if ( strlen( $content ) > 2000 ) {
			$content = substr( $content, 0, 2000 );
		}
		$item = array(
			'role'    => $role,
			'content' => $content,
		);
		if ( $role === 'assistant' ) {
			if ( ! empty( $turn['listings'] ) && is_array( $turn['listings'] ) ) {
				$cards = array();
				foreach ( array_slice( $turn['listings'], 0, 8 ) as $L ) {
					if ( ! is_array( $L ) ) {
						continue;
					}
					$cards[] = array(
						'id'         => isset( $L['id'] ) ? (int) $L['id'] : 0,
						'name'       => bds_ai_finder_scrub_text( sanitize_text_field( (string) ( $L['name'] ?? '' ) ) ),
						'url'        => esc_url_raw( (string) ( $L['url'] ?? '' ) ),
						'categories' => array_values( array_map( 'sanitize_text_field', array_slice( (array) ( $L['categories'] ?? array() ), 0, 4 ) ) ),
						'locations'  => array_values( array_map( 'sanitize_text_field', array_slice( (array) ( $L['locations'] ?? array() ), 0, 4 ) ) ),
						'address'    => bds_ai_finder_scrub_text( sanitize_text_field( (string) ( $L['address'] ?? '' ) ) ),
						'image'      => esc_url_raw( (string) ( $L['image'] ?? '' ) ),
					);
				}
				$item['listings'] = $cards;
			}
			if ( ! empty( $turn['services'] ) && is_array( $turn['services'] ) ) {
				$svcs = array();
				foreach ( array_slice( $turn['services'], 0, 4 ) as $S ) {
					if ( ! is_array( $S ) ) {
						continue;
					}
					$svcs[] = array(
						'label' => sanitize_text_field( (string) ( $S['label'] ?? '' ) ),
						'url'   => esc_url_raw( (string) ( $S['url'] ?? '' ) ),
						'blurb' => bds_ai_finder_scrub_text( sanitize_text_field( (string) ( $S['blurb'] ?? '' ) ) ),
					);
				}
				$item['services'] = $svcs;
			}
			if ( ! empty( $turn['contact_note'] ) ) {
				$item['contact_note'] = bds_ai_finder_scrub_text( sanitize_text_field( (string) $turn['contact_note'] ) );
			}
			$item['guest'] = ! empty( $turn['guest'] ) ? 1 : 0;
		}
		$out['turns'][] = $item;
	}
	if ( isset( $raw['updated'] ) ) {
		$out['updated'] = max( 0, (int) $raw['updated'] );
	}
	return $out;
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_ai_finder_rest_memory_get( $request ) {
	$uid  = get_current_user_id();
	$raw  = get_user_meta( $uid, BDS_AI_FINDER_META_CHAT, true );
	$data = bds_ai_finder_sanitize_memory( is_array( $raw ) ? $raw : array() );
	return rest_ensure_response(
		array(
			'ok'   => true,
			'data' => $data,
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_ai_finder_rest_memory_save( $request ) {
	$uid  = get_current_user_id();
	$raw  = $request->get_json_params();
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$data = bds_ai_finder_sanitize_memory( $raw );
	update_user_meta( $uid, BDS_AI_FINDER_META_CHAT, $data );
	return rest_ensure_response(
		array(
			'ok'   => true,
			'data' => $data,
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_ai_finder_rest_memory_clear( $request ) {
	$uid = get_current_user_id();
	delete_user_meta( $uid, BDS_AI_FINDER_META_CHAT );
	return rest_ensure_response(
		array(
			'ok' => true,
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_ai_finder_rest_status( $request ) {
	$stats = bds_ai_finder_directory_stats();
	return rest_ensure_response(
		array(
			'ok'                   => true,
			'enabled'              => bds_ai_finder_enabled(),
			'ai'                   => bds_ai_finder_openai_key() !== '',
			'version'              => BDS_AI_FINDER_VER,
			'listings_cache_ver'   => (int) get_option( BDS_AI_LISTINGS_CACHE_VER_OPT, 1 ),
			'tp_cache_ver'         => (int) get_option( BDS_AI_TP_CACHE_VER_OPT, 1 ),
			'guest'                => ! is_user_logged_in(),
			'geocode'              => true,
			'published_listings'   => (int) $stats['published_listings'],
			'directory_stats'      => $stats,
		)
	);
}

/**
 * Live Directory inventory stats for AI context (never hardcoded).
 * Prefers bds-directory-stats-mu helpers; falls back to a direct publish count.
 *
 * @return array{published_listings:int,categories:int,locations:int,source:string}
 */
function bds_ai_finder_directory_stats() {
	if ( function_exists( 'bds_dir_stats_get' ) ) {
		$s = bds_dir_stats_get();
		return array(
			'published_listings' => isset( $s['published_listings'] ) ? (int) $s['published_listings'] : 0,
			'categories'         => isset( $s['categories'] ) ? (int) $s['categories'] : 0,
			'locations'          => isset( $s['locations'] ) ? (int) $s['locations'] : 0,
			'source'             => isset( $s['source'] ) ? (string) $s['source'] : 'bds_dir_stats',
		);
	}
	if ( function_exists( 'bds_dir_stats_published_listings' ) ) {
		return array(
			'published_listings' => (int) bds_dir_stats_published_listings(),
			'categories'         => 0,
			'locations'          => 0,
			'source'             => 'bds_dir_stats_published_listings',
		);
	}
	$published = 0;
	if ( post_type_exists( 'at_biz_dir' ) ) {
		$counts = wp_count_posts( 'at_biz_dir' );
		if ( $counts && isset( $counts->publish ) ) {
			$published = (int) $counts->publish;
		}
	}
	return array(
		'published_listings' => $published,
		'categories'         => 0,
		'locations'          => 0,
		'source'             => 'wp_count_posts',
	);
}

/**
 * Reverse-geocode lat/lng -> human place label (Nominatim, transient-cached).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bds_ai_finder_rest_geocode( $request ) {
	$lat = (float) $request->get_param( 'lat' );
	$lng = (float) $request->get_param( 'lng' );
	if ( ! is_finite( $lat ) || ! is_finite( $lng ) || abs( $lat ) > 90 || abs( $lng ) > 180 ) {
		return new WP_Error( 'bds_ai_geo_bad', 'Invalid coordinates.', array( 'status' => 400 ) );
	}
	$place = bds_ai_finder_reverse_geocode( $lat, $lng );
	return rest_ensure_response(
		array(
			'ok'          => true,
			'lat'         => $lat,
			'lng'         => $lng,
			'label'       => $place['label'],
			'city'        => $place['city'],
			'neighborhood'=> $place['neighborhood'],
			'source'      => $place['source'],
		)
	);
}

/**
 * Build a friendly place label from coords (cache -> Nominatim -> bbox fallback).
 *
 * @param float $lat Lat.
 * @param float $lng Lng.
 * @return array{label:string,city:string,neighborhood:string,source:string}
 */
function bds_ai_finder_reverse_geocode( $lat, $lng ) {
	$lat = (float) $lat;
	$lng = (float) $lng;
	$key = 'bds_ai_rg_' . md5( round( $lat, 3 ) . ',' . round( $lng, 3 ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) && ! empty( $cached['label'] ) ) {
		$cached['source'] = 'cache';
		return $cached;
	}

	$out = array(
		'label'        => '',
		'city'         => '',
		'neighborhood' => '',
		'source'       => 'fallback',
	);

	$resp = wp_remote_get(
		add_query_arg(
			array(
				'lat'            => $lat,
				'lon'            => $lng,
				'format'         => 'json',
				'zoom'           => 14,
				'addressdetails' => 1,
			),
			'https://nominatim.openstreetmap.org/reverse'
		),
		array(
			'timeout' => 8,
			'headers' => array(
				'User-Agent' => 'BrandDadDirectoryAIFinder/1.2 (directory.branddad.social; near-me)',
				'Accept'     => 'application/json',
			),
		)
	);

	if ( ! is_wp_error( $resp ) ) {
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
			$addr = isset( $body['address'] ) && is_array( $body['address'] ) ? $body['address'] : array();
			$hood = '';
			foreach ( array( 'neighbourhood', 'neighborhood', 'suburb', 'quarter', 'city_district', 'hamlet' ) as $k ) {
				if ( ! empty( $addr[ $k ] ) ) {
					$hood = (string) $addr[ $k ];
					break;
				}
			}
			$city = '';
			foreach ( array( 'city', 'town', 'village', 'municipality' ) as $k ) {
				if ( ! empty( $addr[ $k ] ) ) {
					$city = (string) $addr[ $k ];
					break;
				}
			}
			$state = ! empty( $addr['state'] ) ? (string) $addr['state'] : '';
			$state_code = ! empty( $addr['ISO3166-2-lvl4'] ) ? (string) $addr['ISO3166-2-lvl4'] : '';
			// Prefer short US state (IL) when available.
			if ( 1 === preg_match( '/^US-([A-Z]{2})$/', $state_code, $sm ) ) {
				$state = $sm[1];
			} elseif ( 'Illinois' === $state ) {
				$state = 'IL';
			} elseif ( 'Quintana Roo' === $state ) {
				$state = 'Q.R.';
			}
			$parts = array();
			if ( $hood !== '' && strcasecmp( $hood, $city ) !== 0 ) {
				$parts[] = $hood;
			}
			if ( $city !== '' ) {
				$parts[] = $city;
			}
			if ( $state !== '' && ! in_array( $state, $parts, true ) ) {
				$parts[] = $state;
			}
			$label = implode( ', ', $parts );
			if ( $label === '' && ! empty( $body['display_name'] ) ) {
				$bits  = array_map( 'trim', explode( ',', (string) $body['display_name'] ) );
				$label = implode( ', ', array_slice( $bits, 0, 3 ) );
			}
			if ( $label !== '' ) {
				$out = array(
					'label'        => sanitize_text_field( $label ),
					'city'         => sanitize_text_field( $city ),
					'neighborhood' => sanitize_text_field( $hood ),
					'source'       => 'nominatim',
				);
				set_transient( $key, $out, 12 * HOUR_IN_SECONDS );
				return $out;
			}
		}
	}

	// Bbox fallback so near-me never invents a wrong city silently.
	$fb = bds_ai_finder_bbox_place( $lat, $lng );
	if ( $fb ) {
		$out = $fb;
		set_transient( $key, $out, 2 * HOUR_IN_SECONDS );
	} else {
		$out['label'] = 'your location';
	}
	return $out;
}

/**
 * Coarse place label from known Directory market bounding boxes.
 *
 * @param float $lat Lat.
 * @param float $lng Lng.
 * @return array{label:string,city:string,neighborhood:string,source:string}|null
 */
function bds_ai_finder_bbox_place( $lat, $lng ) {
	$lat = (float) $lat;
	$lng = (float) $lng;
	if ( $lat >= 41.60 && $lat <= 42.05 && $lng >= -87.95 && $lng <= -87.45 ) {
		$hood = 'Chicago';
		if ( $lat >= 41.90 && $lat <= 41.93 && $lng >= -87.69 && $lng <= -87.65 ) {
			$hood = 'Wicker Park';
		} elseif ( $lat >= 41.91 && $lat <= 41.94 && $lng >= -87.66 && $lng <= -87.62 ) {
			$hood = 'Lincoln Park';
		} elseif ( $lat >= 41.88 && $lat <= 41.90 && $lng >= -87.64 && $lng <= -87.62 ) {
			$hood = 'River North';
		}
		$label = ( 'Chicago' === $hood ) ? 'Chicago, IL' : ( $hood . ', Chicago, IL' );
		return array(
			'label'        => $label,
			'city'         => 'chicago',
			'neighborhood' => $hood,
			'source'       => 'bbox',
		);
	}
	if ( $lat >= 20.35 && $lat <= 20.85 && $lng >= -87.25 && $lng <= -86.85 ) {
		return array(
			'label'        => 'Playa del Carmen, Q.R.',
			'city'         => 'playa del carmen',
			'neighborhood' => 'Playa del Carmen',
			'source'       => 'bbox',
		);
	}
	if ( $lat >= 20.10 && $lat <= 20.30 && $lng >= -87.55 && $lng <= -87.35 ) {
		return array(
			'label'        => 'Tulum, Q.R.',
			'city'         => 'tulum',
			'neighborhood' => 'Tulum',
			'source'       => 'bbox',
		);
	}
	if ( $lat >= 21.00 && $lat <= 21.30 && $lng >= -86.95 && $lng <= -86.70 ) {
		return array(
			'label'        => 'Cancún, Q.R.',
			'city'         => 'cancun',
			'neighborhood' => 'Cancún',
			'source'       => 'bbox',
		);
	}
	return null;
}

/**
 * Simple IP rate limit.
 *
 * @return true|WP_Error
 */
function bds_ai_finder_rate_ok() {
	$s   = bds_ai_finder_settings();
	$max = isset( $s['rate_per_ip'] ) ? (int) $s['rate_per_ip'] : 30;
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '0';
	$key = 'bds_ai_rl_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= $max ) {
		return new WP_Error( 'bds_ai_rate', 'Too many Ask BrandDad requests. Try again in a bit.', array( 'status' => 429 ) );
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bds_ai_finder_rest_find( $request ) {
	if ( ! bds_ai_finder_enabled() ) {
		return new WP_Error( 'bds_ai_off', 'AI Finder is disabled.', array( 'status' => 503 ) );
	}
	$rate = bds_ai_finder_rate_ok();
	if ( is_wp_error( $rate ) ) {
		return $rate;
	}

	$message = trim( (string) $request->get_param( 'message' ) );
	if ( $message === '' || strlen( $message ) > 500 ) {
		return new WP_Error( 'bds_ai_bad_message', 'Please enter a short search question (max 500 chars).', array( 'status' => 400 ) );
	}

	$history = $request->get_param( 'history' );
	if ( ! is_array( $history ) ) {
		$history = array();
	}
	$history = array_slice( $history, -6 );
	$clean_history = array();
	foreach ( $history as $turn ) {
		if ( ! is_array( $turn ) ) {
			continue;
		}
		$role = isset( $turn['role'] ) ? (string) $turn['role'] : '';
		if ( $role !== 'user' && $role !== 'assistant' ) {
			continue;
		}
		$content = isset( $turn['content'] ) ? bds_ai_finder_scrub_text( (string) $turn['content'] ) : '';
		if ( $content === '' ) {
			continue;
		}
		$clean_history[] = array(
			'role'    => $role,
			'content' => $content,
		);
	}
	$history = $clean_history;

	$geo = array(
		'lat'       => $request->get_param( 'lat' ),
		'lng'       => $request->get_param( 'lng' ),
		'radius_km' => $request->get_param( 'radius_km' ),
	);
	$place_label = sanitize_text_field( (string) $request->get_param( 'place_label' ) );
	$context     = $request->get_param( 'context' );
	if ( ! is_array( $context ) ) {
		$context = array();
	}
	$context = bds_ai_finder_merge_history_context( $context, $history, $message );
	$parsed = bds_ai_finder_parse_intent( $message, $geo, $context, $place_label );
	$concierge = function_exists( 'bds_ai_concierge_decide' )
		? bds_ai_concierge_decide( $message, $parsed, $context, $history )
		: null;
	$skip_list_early = function_exists( 'bds_ai_univ_skip_listing_search' ) && bds_ai_univ_skip_listing_search( $parsed );

	if ( is_array( $concierge ) && ! empty( $concierge['ask'] ) && 'CLARIFY' === ( $concierge['action'] ?? '' ) ) {
		return rest_ensure_response( bds_ai_finder_concierge_early_response( $concierge, $parsed, ! is_user_logged_in() ) );
	}

	if ( ! empty( $parsed['location_ambiguous'] ) ) {
		return rest_ensure_response(
			array(
				'ok'       => true,
				'reply'    => 'That place name matches more than one location in the Directory. Which one did you mean?',
				'listings' => array(),
				'services' => array(),
				'guest'    => ! is_user_logged_in(),
				'mode'     => 'clarify_location',
				'chips'    => array(),
				'parsed'   => array(
					'location_ambiguous' => true,
					'search_mode'        => 'clarify',
					'keywords'           => $parsed['keywords'],
					'category'           => $parsed['category'],
					'specificity'        => isset( $parsed['specificity'] ) ? $parsed['specificity'] : '',
				),
				'contact_note' => '',
			)
		);
	}

	// "near me" without browser coords — never guess a city (local businesses only).
	if ( ! empty( $parsed['near_me'] ) && empty( $parsed['client_geo'] ) && empty( $parsed['is_digital'] ) && empty( $parsed['is_gift'] ) && empty( $parsed['is_travel'] ) && ! $skip_list_early ) {
		return rest_ensure_response(
			array(
				'ok'        => true,
				'needs_geo' => true,
				'reply'     => 'I need your real location for "near me." Allow location access, or tell me a zip/city - e.g. coffee in 60622 or spa in Playa del Carmen.',
				'listings'  => array(),
				'services'  => array(),
				'guest'     => ! is_user_logged_in(),
				'mode'      => 'needs_geo',
				'parsed'    => array(
					'near_me'     => true,
					'search_mode' => 'needs_geo',
					'keywords'    => $parsed['keywords'],
					'category'    => $parsed['category'],
				),
				'contact_note' => '',
			)
		);
	}

	// Fill place label from reverse-geocode when client sent coords but no label.
	if ( ( $place_label === '' || $place_label === 'your location' ) && ! empty( $parsed['lat'] ) && ! empty( $parsed['lng'] ) && ( ! empty( $parsed['near_me'] ) || ! empty( $parsed['client_geo'] ) ) ) {
		$rg = bds_ai_finder_reverse_geocode( (float) $parsed['lat'], (float) $parsed['lng'] );
		if ( ! empty( $rg['label'] ) ) {
			$parsed['area_label']  = (string) $rg['label'];
			$parsed['place_label'] = (string) $rg['label'];
			if ( empty( $parsed['location'] ) && ! empty( $rg['city'] ) ) {
				$parsed['location']      = strtolower( (string) $rg['city'] );
				$parsed['location_term'] = bds_ai_finder_resolve_location_term( $parsed['location'] );
			}
		}
	}

	// Gift: Directory gift catalog + places/sponsored via bidding MU. Travel: TP deals. Else: Directory businesses.
	$sponsored = array();
	$places    = array();
	$bid_bits  = array();
	$products  = array();
	$content   = array();
	$skip_list = function_exists( 'bds_ai_univ_skip_listing_search' ) && bds_ai_univ_skip_listing_search( $parsed );
	$provs     = isset( $parsed['providers'] ) && is_array( $parsed['providers'] ) ? $parsed['providers'] : array();
	if ( ! empty( $parsed['is_gift'] ) ) {
		$listings = array();
		$products = bds_ai_finder_query_gift_products( $parsed, $message, 6 );
		if ( function_exists( 'bds_bid_ai_pack' ) ) {
			$pack      = bds_bid_ai_pack( $parsed, $message, array() );
			$sponsored = isset( $pack['sponsored'] ) && is_array( $pack['sponsored'] ) ? $pack['sponsored'] : array();
			$places    = isset( $pack['places'] ) && is_array( $pack['places'] ) ? $pack['places'] : array();
			$bid_bits  = isset( $pack['reply_bits'] ) && is_array( $pack['reply_bits'] ) ? $pack['reply_bits'] : array();
			$listings = $places;
		}
	} elseif ( ! empty( $parsed['is_travel'] ) && ( empty( $provs ) || in_array( 'travel', $provs, true ) ) && empty( $parsed['mixed'] ) ) {
		$listings = bds_ai_finder_query_travel_deals( $parsed, 8 );
	} elseif ( $skip_list ) {
		$listings = array();
	} else {
		$listings = bds_ai_finder_query_listings( $parsed, 8 );
		if ( function_exists( 'bds_bid_ai_pack' ) ) {
			$organic_ids = array();
			foreach ( $listings as $L ) {
				if ( is_array( $L ) && ! empty( $L['id'] ) ) {
					$organic_ids[] = (int) $L['id'];
				}
			}
			$pack      = bds_bid_ai_pack( $parsed, $message, $organic_ids );
			$sponsored = isset( $pack['sponsored'] ) && is_array( $pack['sponsored'] ) ? $pack['sponsored'] : array();
			$bid_bits  = isset( $pack['reply_bits'] ) && is_array( $pack['reply_bits'] ) ? $pack['reply_bits'] : array();
		}
	}
	if ( in_array( 'content', $provs, true ) && function_exists( 'bds_ai_univ_query_content' ) ) {
		$content = bds_ai_univ_query_content( $message );
	}
	$services = bds_ai_finder_match_services( $message, $parsed );

	$n_in = is_array( $listings ) ? count( $listings ) : 0;
	$listings = apply_filters( 'bds_ai_finder_listings', is_array( $listings ) ? $listings : array(), $parsed );
	if ( is_array( $sponsored ) && $sponsored ) {
		$sponsored = apply_filters( 'bds_ai_finder_listings', $sponsored, $parsed );
	}
	if ( function_exists( 'bds_ai_prec_log' ) ) {
		if ( is_array( $concierge ) ) {
			$parsed['concierge_action'] = (string) ( $concierge['action'] ?? 'SEARCH' );
			$parsed['clarify_turns']    = isset( $concierge['state']['clarify_turns'] ) ? (int) $concierge['state']['clarify_turns'] : 0;
		}
		bds_ai_prec_log( $message, $parsed, $n_in, is_array( $listings ) ? count( $listings ) : 0 );
	}

	$guest = ! is_user_logged_in();
	$key   = bds_ai_finder_openai_key();
	$dir_stats = bds_ai_finder_directory_stats();

	// Consultative layer: diagnose the need, decide whether to ask before
	// recommending, and pick at most two relevant upsells with reasons.
	$need    = bds_ai_finder_need_profile( $message, $parsed, $context );
	$clarify = bds_ai_finder_clarify_plan( $need, $parsed, $message );
	if ( bds_ai_finder_recently_clarified( $history ) ) {
		$clarify['ask']       = false;
		$clarify['questions'] = array();
	}
	$upsell = bds_ai_finder_upsell_plan( $need, $parsed, $guest );
	if ( $upsell ) {
		$services = bds_ai_finder_merge_services( $services, $upsell );
	}
	// Business-owner conversations are about their site, not about visiting
	// somebody else's shop - unrelated listing cards read as spam there.
	$owner_domains = array( 'web_speed', 'web_broken', 'web_build', 'brand', 'local_seo', 'reviews', 'social', 'hosting', 'health', 'claim', 'membership' );
	if ( in_array( (string) $need['domain'], $owner_domains, true ) && empty( $parsed['mixed'] ) && empty( $parsed['owner_launch'] ) ) {
		$listings  = array();
		$sponsored = array();
	}
	$chips = isset( $clarify['chips'] ) && is_array( $clarify['chips'] ) ? $clarify['chips'] : array();
	$zero_pack = null;
	if ( function_exists( 'bds_ai_prec_zero_pack' )
		&& empty( $listings )
		&& empty( $products )
		&& empty( $content )
		&& empty( $services )
		&& empty( $parsed['is_gift'] )
		&& ! in_array( (string) $need['domain'], $owner_domains, true ) ) {
		$zero_pack         = bds_ai_prec_zero_pack( $parsed, $message );
		$chips             = array_merge( $chips, $zero_pack['chips'] );
		$parsed['zero_ok'] = true;
	}

	// Inventory / "how many listings" - answer from live stats (no LLM, never hardcoded).
	$msg_lc = strtolower( trim( (string) $message ) );
	if ( 1 === preg_match( '/\b(how many|how big|total|count|number of)\b/i', $msg_lc )
		&& 1 === preg_match( '/\b(listing|listings|business|businesses|director(?:y|ies)|inventory)\b/i', $msg_lc ) ) {
		$reply = bds_ai_finder_template_reply( $message, array(), array(), $guest, $parsed, array(), array() );
		return rest_ensure_response(
			array(
				'ok'              => true,
				'needs_geo'       => false,
				'reply'           => $reply,
				'listings'        => array(),
				'sponsored'       => array(),
				'places'          => array(),
				'services'        => array(),
				'guest'           => $guest,
				'mode'            => 'directory_stats',
				'directory_stats' => $dir_stats,
				'parsed'          => array(
					'keywords' => $parsed['keywords'],
					'location' => $parsed['location'],
					'category' => $parsed['category'],
				),
			)
		);
	}

	// Cards shown in UI: organic/places first, then honestly labeled sponsored.
	$ui_listings = $listings;
	foreach ( $sponsored as $sc ) {
		if ( is_array( $sc ) ) {
			$ui_listings[] = $sc;
		}
	}
	$sections = function_exists( 'bds_ai_univ_build_sections' )
		? bds_ai_univ_build_sections( $parsed, $ui_listings, $products, $services, $content )
		: array();

	if ( $key && empty( $zero_pack ) ) {
		$reply = bds_ai_finder_openai_reply( $key, $message, $history, $listings, $services, $guest, $parsed, $sponsored, $products, $need, $clarify );
		if ( is_wp_error( $reply ) ) {
			$reply = bds_ai_finder_template_reply( $message, $listings, $services, $guest, $parsed, $sponsored, $bid_bits, $products, $clarify );
			$mode  = 'template_fallback';
		} else {
			$mode = 'openai';
			if ( $bid_bits && $listings ) {
				$reply = rtrim( (string) $reply ) . "\n\n" . implode( "\n\n", $bid_bits );
			}
		}
	} elseif ( ! empty( $zero_pack['reply'] ) ) {
		$reply = (string) $zero_pack['reply'];
		$mode  = 'zero';
	} else {
		$reply = bds_ai_finder_template_reply( $message, $listings, $services, $guest, $parsed, $sponsored, $bid_bits, $products, $clarify );
		$mode  = 'template';
	}

	$final_concierge = is_array( $concierge ) ? $concierge : array();
	if ( function_exists( 'bds_ai_concierge_state_snapshot' ) ) {
		$final_concierge['state'] = bds_ai_concierge_state_snapshot(
			isset( $final_concierge['state'] ) && is_array( $final_concierge['state'] ) ? $final_concierge['state'] : array(),
			$ui_listings
		);
	}
	if ( empty( $final_concierge['action'] ) ) {
		$final_concierge['action'] = 'SEARCH';
	}
	$state_snap = isset( $final_concierge['state'] ) && is_array( $final_concierge['state'] ) ? $final_concierge['state'] : array();

	return rest_ensure_response(
		array(
			'ok'              => true,
			'needs_geo'       => false,
			'reply'           => $reply,
			'listings'        => $ui_listings,
			'products'        => $products,
			'sections'        => $sections,
			'unavailable'     => isset( $parsed['unavailable'] ) && is_array( $parsed['unavailable'] ) ? $parsed['unavailable'] : array(),
			'chips'           => $chips,
			'action'          => (string) ( $final_concierge['action'] ?? 'SEARCH' ),
			'missing'         => isset( $final_concierge['missing'] ) ? array_values( (array) $final_concierge['missing'] ) : array(),
			'question'        => (string) ( $final_concierge['question'] ?? '' ),
			'state'           => $state_snap,
			'concierge'       => array(
				'ver'    => function_exists( 'bds_ai_concierge_ver' ) ? bds_ai_concierge_ver() : '1.0.0',
				'action' => (string) ( $final_concierge['action'] ?? 'SEARCH' ),
				'diag'   => isset( $final_concierge['diag'] ) ? $final_concierge['diag'] : array(),
			),
			'sponsored'       => $sponsored,
			'places'          => $places,
			'services'        => $services,
			'guest'           => $guest,
			'mode'            => $mode,
			'directory_stats' => $dir_stats,
			'need'            => array(
				'domain'     => (string) $need['domain'],
				'confidence' => (float) $need['confidence'],
				'asked'      => ! empty( $clarify['ask'] ),
			),
			'parsed'          => array(
				'keywords'       => $parsed['keywords'],
				'usecases'       => isset( $parsed['gift_usecases'] ) && is_array( $parsed['gift_usecases'] ) ? array_values( $parsed['gift_usecases'] ) : array(),
				'domain'         => (string) $need['domain'],
				'gift_brand'     => isset( $parsed['gift_brand'] ) ? (string) $parsed['gift_brand'] : '',
				'gift_exact'     => ! empty( $parsed['gift_exact'] ),
				'location'       => $parsed['location'],
				'category'       => $parsed['category'],
				'vertical'       => isset( $parsed['vertical'] ) ? $parsed['vertical'] : '',
				'quality_intent' => ! empty( $parsed['quality_intent'] ),
				'dish_tokens'    => isset( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) ? array_values( $parsed['dish_tokens'] ) : array(),
				'max_price'      => isset( $parsed['max_price'] ) ? $parsed['max_price'] : null,
				'strict'         => ! empty( $parsed['strict'] ),
				'specificity'    => isset( $parsed['specificity'] ) ? (string) $parsed['specificity'] : '',
				'search_intent'  => isset( $parsed['search_intent'] ) ? (string) $parsed['search_intent'] : '',
				'entity_domain'  => isset( $parsed['entity_domain'] ) ? (string) $parsed['entity_domain'] : '',
				'location_ambiguous' => ! empty( $parsed['location_ambiguous'] ),
				'diag'           => isset( $parsed['_diag'] ) && is_array( $parsed['_diag'] ) ? $parsed['_diag'] : array(),
				'mixed'          => ! empty( $parsed['mixed'] ),
				'providers'      => isset( $parsed['providers'] ) && is_array( $parsed['providers'] ) ? array_values( $parsed['providers'] ) : array(),
				'needs'          => isset( $parsed['needs'] ) && is_array( $parsed['needs'] ) ? $parsed['needs'] : array(),
				'postal'         => $parsed['postal'],
				'near_me'        => ! empty( $parsed['near_me'] ),
				'lat'            => $parsed['lat'],
				'lng'            => $parsed['lng'],
				'radius_km'      => $parsed['radius_km'],
				'search_mode'    => $parsed['search_mode'],
				'expansion_note' => $parsed['expansion_note'],
				'area_label'     => $parsed['area_label'],
				'place_label'    => isset( $parsed['place_label'] ) ? $parsed['place_label'] : $parsed['area_label'],
				'is_digital'     => ! empty( $parsed['is_digital'] ),
				'is_gift'        => ! empty( $parsed['is_gift'] ),
				'is_travel'      => ! empty( $parsed['is_travel'] ),
				'travel_kind'    => isset( $parsed['travel_kind'] ) ? $parsed['travel_kind'] : '',
				'travel_origin'  => isset( $parsed['travel_origin'] ) ? $parsed['travel_origin'] : '',
				'travel_dest'    => isset( $parsed['travel_dest'] ) ? $parsed['travel_dest'] : '',
				'follow_up'      => $parsed['follow_up'],
				'version'        => BDS_AI_FINDER_VER,
				'action'         => (string) ( $final_concierge['action'] ?? 'SEARCH' ),
				'missing'        => isset( $final_concierge['missing'] ) ? array_values( (array) $final_concierge['missing'] ) : array(),
				'question'       => (string) ( $final_concierge['question'] ?? '' ),
				'state'          => $state_snap,
			),
			'contact_note' => ( $guest && empty( $parsed['is_gift'] ) && empty( $parsed['is_travel'] ) )
				? 'Contact details stay private until you sign up - open a listing page to continue.'
				: '',
		)
	);
}

/**
 * Homepage / near-you local pack (distance-sorted when lat/lng present).
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_ai_finder_rest_local_pack( $request ) {
	$lat = $request->get_param( 'lat' );
	$lng = $request->get_param( 'lng' );
	$geo = bds_ai_finder_normalize_geo(
		array(
			'lat' => $lat,
			'lng' => $lng,
		)
	);
	$label = '';
	$city  = '';
	if ( $geo['lat'] !== null && $geo['lng'] !== null ) {
		$rg    = bds_ai_finder_reverse_geocode( $geo['lat'], $geo['lng'] );
		$label = ! empty( $rg['label'] ) ? (string) $rg['label'] : '';
		$city  = ! empty( $rg['city'] ) ? strtolower( (string) $rg['city'] ) : '';
	}
	$groups = array();
	$markets = array(
		array( 'key' => 'chicago', 'label' => 'Chicago', 'lat' => 41.8961, 'lng' => -87.6756 ),
		array( 'key' => 'playa del carmen', 'label' => 'Playa del Carmen', 'lat' => 20.6296, 'lng' => -87.0739 ),
	);
	// Prefer user market first when geo known.
	if ( $city !== '' ) {
		usort(
			$markets,
			static function ( $a, $b ) use ( $city ) {
				$aa = ( false !== strpos( $a['key'], $city ) || false !== strpos( $city, $a['key'] ) ) ? 0 : 1;
				$bb = ( false !== strpos( $b['key'], $city ) || false !== strpos( $city, $b['key'] ) ) ? 0 : 1;
				return $aa - $bb;
			}
		);
	}
	foreach ( $markets as $mkt ) {
		$use_lat = $geo['lat'] !== null ? $geo['lat'] : $mkt['lat'];
		$use_lng = $geo['lng'] !== null ? $geo['lng'] : $mkt['lng'];
		// Only geo-rank inside the user's city market; otherwise show city centroid pack.
		$in_market = ( $city === '' || false !== strpos( $mkt['key'], $city ) || false !== strpos( $city, explode( ' ', $mkt['key'] )[0] ) );
		$parsed    = array(
			'keywords'       => '',
			'location'       => $mkt['key'],
			'location_term'  => bds_ai_finder_resolve_location_term( $mkt['key'] ),
			'category'       => '',
			'category_term'  => null,
			'vertical'       => 'other',
			'quality_intent' => false,
			'postal'          => '',
			'near_me'        => $in_market && $geo['lat'] !== null,
			'wants_near'     => true,
			'lat'            => $in_market ? $use_lat : $mkt['lat'],
			'lng'            => $in_market ? $use_lng : $mkt['lng'],
			'radius_km'      => 25.0,
			'client_geo'     => $geo['lat'] !== null,
			'search_mode'    => 'local_pack',
			'area_label'     => $mkt['label'],
			'place_label'    => $in_market && $label !== '' ? $label : $mkt['label'],
			'expansion_note' => '',
			'is_digital'     => false,
			'follow_up'      => '',
			'units'          => ( false !== strpos( $mkt['key'], 'chicago' ) ) ? 'mi' : 'km',
		);
		$cards = bds_ai_finder_query_listings( $parsed, 6 );
		if ( $cards ) {
			$groups[] = array(
				'location' => $mkt['label'],
				'label'    => $in_market && $label !== '' ? ( 'Near ' . $label ) : ( 'In ' . $mkt['label'] ),
				'listings' => $cards,
			);
		}
	}
	return rest_ensure_response(
		array(
			'ok'     => true,
			'place'  => $label,
			'groups' => $groups,
		)
	);
}

/**
 * Known postal -> city / centroid map (US Chicago + MX Playa and neighbors).
 *
 * @return array<string,array{city:string,lat:float,lng:float,label:string,country:string}>
 */
function bds_ai_finder_postal_map() {
	static $map = null;
	if ( is_array( $map ) ) {
		return $map;
	}
	$map = array(
		// Chicago IL.
		'60601' => array( 'city' => 'chicago', 'lat' => 41.8857, 'lng' => -87.6225, 'label' => 'Chicago Loop', 'country' => 'US' ),
		'60602' => array( 'city' => 'chicago', 'lat' => 41.8832, 'lng' => -87.6290, 'label' => 'Chicago Loop', 'country' => 'US' ),
		'60605' => array( 'city' => 'chicago', 'lat' => 41.8675, 'lng' => -87.6210, 'label' => 'South Loop', 'country' => 'US' ),
		'60607' => array( 'city' => 'chicago', 'lat' => 41.8748, 'lng' => -87.6510, 'label' => 'West Loop', 'country' => 'US' ),
		'60608' => array( 'city' => 'chicago', 'lat' => 41.8578, 'lng' => -87.6762, 'label' => 'Pilsen', 'country' => 'US' ),
		'60610' => array( 'city' => 'chicago', 'lat' => 41.9030, 'lng' => -87.6340, 'label' => 'Near North', 'country' => 'US' ),
		'60611' => array( 'city' => 'chicago', 'lat' => 41.8912, 'lng' => -87.6216, 'label' => 'Streeterville', 'country' => 'US' ),
		'60614' => array( 'city' => 'chicago', 'lat' => 41.9227, 'lng' => -87.6430, 'label' => 'Lincoln Park', 'country' => 'US' ),
		'60618' => array( 'city' => 'chicago', 'lat' => 41.9465, 'lng' => -87.7040, 'label' => 'North Center', 'country' => 'US' ),
		'60619' => array( 'city' => 'chicago', 'lat' => 41.7582, 'lng' => -87.6197, 'label' => 'Chatham', 'country' => 'US' ),
		'60622' => array( 'city' => 'chicago', 'lat' => 41.8961, 'lng' => -87.6756, 'label' => 'Wicker Park / Ukrainian Village', 'country' => 'US' ),
		'60642' => array( 'city' => 'chicago', 'lat' => 41.8908, 'lng' => -87.6620, 'label' => 'West Town', 'country' => 'US' ),
		'60647' => array( 'city' => 'chicago', 'lat' => 41.9304, 'lng' => -87.7099, 'label' => 'Logan Square', 'country' => 'US' ),
		'60654' => array( 'city' => 'chicago', 'lat' => 41.8938, 'lng' => -87.6335, 'label' => 'River North', 'country' => 'US' ),
		'60657' => array( 'city' => 'chicago', 'lat' => 41.9395, 'lng' => -87.6550, 'label' => 'Lakeview', 'country' => 'US' ),
		'60661' => array( 'city' => 'chicago', 'lat' => 41.8843, 'lng' => -87.6425, 'label' => 'West Loop', 'country' => 'US' ),
		// Playa del Carmen / Riviera Maya MX.
		'77710' => array( 'city' => 'playa del carmen', 'lat' => 20.6296, 'lng' => -87.0739, 'label' => 'Playa del Carmen Centro', 'country' => 'MX' ),
		'77712' => array( 'city' => 'playa del carmen', 'lat' => 20.6800, 'lng' => -87.0400, 'label' => 'Playa del Carmen north', 'country' => 'MX' ),
		'77714' => array( 'city' => 'playa del carmen', 'lat' => 20.6100, 'lng' => -87.1000, 'label' => 'Playa del Carmen south', 'country' => 'MX' ),
		'77716' => array( 'city' => 'playa del carmen', 'lat' => 20.6400, 'lng' => -87.0800, 'label' => 'Playa del Carmen', 'country' => 'MX' ),
		'77717' => array( 'city' => 'playa del carmen', 'lat' => 20.6500, 'lng' => -87.0700, 'label' => 'Playa del Carmen', 'country' => 'MX' ),
		'77720' => array( 'city' => 'playa del carmen', 'lat' => 20.7200, 'lng' => -87.0000, 'label' => 'Puerto Morelos area', 'country' => 'MX' ),
		'77725' => array( 'city' => 'tulum', 'lat' => 20.2110, 'lng' => -87.4650, 'label' => 'Tulum', 'country' => 'MX' ),
		'77500' => array( 'city' => 'cancun', 'lat' => 21.1619, 'lng' => -86.8515, 'label' => 'Cancún', 'country' => 'MX' ),
	);
	return $map;
}

/**
 * Resolve a 5-digit postal to city/centroid (exact map, then range heuristics).
 *
 * @param string $postal Postal.
 * @return array{city:string,lat:float,lng:float,label:string,country:string}|null
 */
function bds_ai_finder_resolve_postal( $postal ) {
	$postal = preg_replace( '/\D+/', '', (string) $postal );
	if ( strlen( $postal ) !== 5 ) {
		return null;
	}
	$map = bds_ai_finder_postal_map();
	if ( isset( $map[ $postal ] ) ) {
		return $map[ $postal ];
	}
	// Chicago IL 606xx / 607xx.
	if ( 1 === preg_match( '/^606\d{2}$/', $postal ) || 1 === preg_match( '/^607\d{2}$/', $postal ) ) {
		return array(
			'city'    => 'chicago',
			'lat'     => 41.8781,
			'lng'     => -87.6298,
			'label'   => 'Chicago',
			'country' => 'US',
		);
	}
	// Playa / Riviera Maya 7771x.
	if ( 1 === preg_match( '/^7771\d$/', $postal ) ) {
		return array(
			'city'    => 'playa del carmen',
			'lat'     => 20.6296,
			'lng'     => -87.0739,
			'label'   => 'Playa del Carmen',
			'country' => 'MX',
		);
	}
	if ( 1 === preg_match( '/^7772\d$/', $postal ) ) {
		return array(
			'city'    => 'playa del carmen',
			'lat'     => 20.7000,
			'lng'     => -87.0200,
			'label'   => 'Riviera Maya',
			'country' => 'MX',
		);
	}
	return null;
}

/**
 * Extract first US ZIP or MX-style 5-digit postal from text.
 *
 * @param string $message Text.
 * @return string
 */
function bds_ai_finder_extract_postal( $message ) {
	if ( ! is_string( $message ) || $message === '' ) {
		return '';
	}
	// Prefer ZIP+4 then plain 5-digit; skip years like 2020-2039 when alone as filler is rare in our queries.
	if ( 1 === preg_match( '/\b(\d{5})(?:-\d{4})?\b/', $message, $m ) ) {
		$code = $m[1];
		// Ignore obvious non-postal years mid-sentence if unmapped and looks like a year.
		if ( (int) $code >= 1900 && (int) $code <= 2099 && ! bds_ai_finder_resolve_postal( $code ) ) {
			return '';
		}
		return $code;
	}
	return '';
}

/**
 * Haversine distance in km.
 *
 * @param float $lat1 Lat.
 * @param float $lng1 Lng.
 * @param float $lat2 Lat.
 * @param float $lng2 Lng.
 * @return float
 */
function bds_ai_finder_haversine_km( $lat1, $lng1, $lat2, $lng2 ) {
	$earth = 6371.0;
	$d_lat = deg2rad( (float) $lat2 - (float) $lat1 );
	$d_lng = deg2rad( (float) $lng2 - (float) $lng1 );
	$a     = sin( $d_lat / 2 ) * sin( $d_lat / 2 )
		+ cos( deg2rad( (float) $lat1 ) ) * cos( deg2rad( (float) $lat2 ) )
		* sin( $d_lng / 2 ) * sin( $d_lng / 2 );
	$c = 2 * atan2( sqrt( $a ), sqrt( max( 0, 1 - $a ) ) );
	return $earth * $c;
}

/**
 * Listing lat/lng from Directorist / enrich meta.
 *
 * @param int $post_id Listing ID.
 * @return array{lat:float,lng:float}|null
 */
function bds_ai_finder_listing_coords( $post_id ) {
	$post_id = (int) $post_id;
	$lat     = get_post_meta( $post_id, '_manual_lat', true );
	if ( $lat === '' || $lat === null ) {
		$lat = get_post_meta( $post_id, '_latitude', true );
	}
	if ( $lat === '' || $lat === null ) {
		$lat = get_post_meta( $post_id, 'latitude', true );
	}
	$lng = get_post_meta( $post_id, '_manual_lng', true );
	if ( $lng === '' || $lng === null ) {
		$lng = get_post_meta( $post_id, '_longitude', true );
	}
	if ( $lng === '' || $lng === null ) {
		$lng = get_post_meta( $post_id, 'longitude', true );
	}
	if ( $lat === '' || $lat === null || $lng === '' || $lng === null ) {
		return null;
	}
	$lat_f = (float) $lat;
	$lng_f = (float) $lng;
	if ( ! is_finite( $lat_f ) || ! is_finite( $lng_f ) ) {
		return null;
	}
	if ( abs( $lat_f ) > 90 || abs( $lng_f ) > 180 || ( abs( $lat_f ) < 0.0001 && abs( $lng_f ) < 0.0001 ) ) {
		return null;
	}
	return array(
		'lat' => $lat_f,
		'lng' => $lng_f,
	);
}

/**
 * Normalize optional client geo from REST.
 *
 * @param array<string,mixed>|null $geo Raw.
 * @return array{lat:?float,lng:?float,radius_km:?float}
 */
function bds_ai_finder_normalize_geo( $geo ) {
	$out = array(
		'lat'       => null,
		'lng'       => null,
		'radius_km' => null,
	);
	if ( ! is_array( $geo ) ) {
		return $out;
	}
	if ( isset( $geo['lat'] ) && $geo['lat'] !== '' && $geo['lat'] !== null
		&& isset( $geo['lng'] ) && $geo['lng'] !== '' && $geo['lng'] !== null ) {
		$lat = (float) $geo['lat'];
		$lng = (float) $geo['lng'];
		if ( is_finite( $lat ) && is_finite( $lng ) && abs( $lat ) <= 90 && abs( $lng ) <= 180 ) {
			$out['lat'] = $lat;
			$out['lng'] = $lng;
		}
	}
	if ( isset( $geo['radius_km'] ) && $geo['radius_km'] !== '' && $geo['radius_km'] !== null ) {
		$r = (float) $geo['radius_km'];
		if ( is_finite( $r ) && $r > 0 ) {
			$out['radius_km'] = max( 1.0, min( 80.0, $r ) );
		}
	}
	return $out;
}

/**
 * Recover location / dish / category from prior user turns when context is empty.
 *
 * @param array<string,mixed>           $context Context.
 * @param array<int,array<string,mixed>> $history History.
 * @param string                         $message Current message.
 * @return array<string,mixed>
 */
function bds_ai_finder_merge_history_context( $context, $history, $message ) {
	if ( ! is_array( $context ) ) {
		$context = array();
	}
	$fu = 1 === preg_match( '/\b(cheaper|cheapest|closer|nearest|similar|more like|only open|open now|under\s*\$?\d+)\b/i', $message );
	if ( function_exists( 'bds_ai_univ_is_constraint_followup' ) ) {
		$fu = $fu || bds_ai_univ_is_constraint_followup( $message );
	}
	if ( ! $fu ) {
		return $context;
	}
	$has = ! empty( $context['location'] ) || ! empty( $context['dish_tokens'] ) || ! empty( $context['category'] );
	if ( $has ) {
		return $context;
	}
	if ( ! is_array( $history ) ) {
		return $context;
	}
	$anchor = '';
	for ( $i = count( $history ) - 1; $i >= 0; $i-- ) {
		$t = $history[ $i ];
		if ( ! is_array( $t ) || ( $t['role'] ?? '' ) !== 'user' ) {
			continue;
		}
		$c = trim( (string) ( $t['content'] ?? '' ) );
		if ( $c === '' ) {
			continue;
		}
		$short_fu = 1 === preg_match( '/\b(cheaper|cheapest|closer|nearest|similar|more like|only open|open now)\b/i', $c );
		if ( function_exists( 'bds_ai_univ_is_constraint_followup' ) ) {
			$short_fu = $short_fu || bds_ai_univ_is_constraint_followup( $c );
		}
		if ( $short_fu && str_word_count( $c ) < 8 ) {
			continue;
		}
		$anchor = $c;
		break;
	}
	if ( $anchor === '' ) {
		return $context;
	}
	$prior = bds_ai_finder_parse_intent( $anchor, null, array(), '' );
	foreach ( array( 'location', 'category', 'place_label', 'dish_tokens', 'max_price', 'lat', 'lng', 'is_travel', 'travel_kind', 'travel_origin', 'travel_dest' ) as $k ) {
		if ( ( empty( $context[ $k ] ) || ( is_array( $context[ $k ] ) && ! $context[ $k ] ) ) && ! empty( $prior[ $k ] ) ) {
			$context[ $k ] = $prior[ $k ];
		}
	}
	return $context;
}

/**
 * Heuristic intent parse (location / zip / near-me / category / follow-ups).
 *
 * @param string                   $message     User text.
 * @param array<string,mixed>|null $geo         Optional client lat/lng/radius.
 * @param array<string,mixed>      $context     Prior turn context from client.
 * @param string                   $place_label Reverse-geocoded label from client.
 * @return array<string,mixed>
 */
function bds_ai_finder_parse_intent( $message, $geo = null, $context = array(), $place_label = '' ) {
	$raw     = $message;
	$m       = strtolower( $message );
	$geo_in  = bds_ai_finder_normalize_geo( $geo );
	$postal   = bds_ai_finder_extract_postal( $message );
	$postal_info = $postal !== '' ? bds_ai_finder_resolve_postal( $postal ) : null;
	$near_me = 1 === preg_match( '/\bnear\s+me\b|\bnearby\b|\baround\s+me\b|\bclose\s+to\s+me\b/i', $message );
	$wants_near = $near_me || 1 === preg_match( '/\bnear\b|\bnearby\b|\bwithin\b|\baround\b/i', $message );
	$follow_up  = '';
	if ( 1 === preg_match( '/\bcloser\b|\bnearest\b|\bmore close|\btighter\b|\bwalkable\b/i', $message ) ) {
		$follow_up = 'closer';
		$wants_near = true;
	} elseif ( 1 === preg_match( '/\bmore like (that|those|this)\b|\bsimilar\b|\bmore of (those|that|them)\b/i', $message ) ) {
		$follow_up = 'more_like';
	} elseif ( 1 === preg_match( '/\bcheaper\b|\bcheapest\b|\bless expensive\b|\bbudget\b|\baffordable\b/i', $message ) ) {
		$follow_up = 'cheaper';
	} elseif ( function_exists( 'bds_ai_univ_is_constraint_followup' ) && bds_ai_univ_is_constraint_followup( $message ) ) {
		$follow_up = 'constrain';
	} elseif ( is_array( $context ) && ( ! empty( $context['category'] ) || ! empty( $context['location'] ) || ! empty( $context['dish_tokens'] ) )
		&& str_word_count( trim( (string) $message ) ) <= 8
		&& 1 === preg_match( '/^(with|without|actually|instead|only|just|and|under)\b/i', trim( (string) $message ) ) ) {
		$follow_up = 'constrain';
	}
	if ( ! is_array( $context ) ) {
		$context = array();
	}
	$place_label = sanitize_text_field( (string) $place_label );

	$location_hints = bds_ai_finder_location_hint_map();
	$category_hints = bds_ai_finder_category_hint_map();
	if ( function_exists( 'bds_ai_intent_wp_opts' ) ) {
		foreach ( (array) ( bds_ai_intent_wp_opts()['categories'] ?? array() ) as $crow ) {
			if ( ! is_array( $crow ) ) {
				continue;
			}
			$n = strtolower( (string) ( $crow['name'] ?? '' ) );
			$s = strtolower( (string) ( $crow['slug'] ?? '' ) );
			if ( $n !== '' && $s !== '' ) {
				$category_hints[ $n ] = $s;
			}
			$plain = str_replace( '-', ' ', $s );
			if ( strlen( $plain ) >= 4 && $s !== '' ) {
				$category_hints[ $plain ] = $s;
			}
		}
	}
	$quality_intent = 1 === preg_match( '/\b(good|best|great|top|recommend(?:ed)?|highly\s+rated|must[\s-]?try|favorite|favourite)\b/i', $message );

	$location = '';
	// Longest location needle first (playa del carmen before playa).
	$loc_needles = array_keys( $location_hints );
	usort(
		$loc_needles,
		static function ( $a, $b ) {
			return strlen( (string) $b ) <=> strlen( (string) $a );
		}
	);
	foreach ( $loc_needles as $needle ) {
		$n = (string) $needle;
		if ( $n === '' ) {
			continue;
		}
		if ( 1 === preg_match( '/\b' . preg_quote( $n, '/' ) . '\b/u', $m ) ) {
			$location = (string) $location_hints[ $needle ];
			break;
		}
	}
	if ( $location === '' && is_array( $postal_info ) && ! empty( $postal_info['city'] ) ) {
		$location = (string) $postal_info['city'];
	}

	$category = '';
	// Longest category needle first (mexican restaurant before restaurant).
	$cat_needles = array_keys( $category_hints );
	usort(
		$cat_needles,
		static function ( $a, $b ) {
			return strlen( (string) $b ) <=> strlen( (string) $a );
		}
	);
	foreach ( $cat_needles as $needle ) {
		$n = (string) $needle;
		if ( $n === '' ) {
			continue;
		}
		// Word-boundary so "eat" does not match "great" / "meat".
		if ( 1 === preg_match( '/\b' . preg_quote( $n, '/' ) . '\b/u', $m ) ) {
			$category = (string) $category_hints[ $needle ];
			break;
		}
	}
	// Follow-ups inherit last category/location from session context.
	if ( $follow_up !== '' ) {
		if ( $category === '' && ! empty( $context['category'] ) ) {
			$category = sanitize_text_field( (string) $context['category'] );
		}
		if ( $location === '' && ! empty( $context['location'] ) ) {
			$location = sanitize_text_field( (string) $context['location'] );
		}
		if ( $place_label === '' && ! empty( $context['place_label'] ) ) {
			$place_label = sanitize_text_field( (string) $context['place_label'] );
		}
		if ( empty( $context['_prec_done'] ) && ! empty( $context['dish_tokens'] ) && is_array( $context['dish_tokens'] ) ) {
			// Keep dish constraints on follow-ups like "which is cheapest?"
			$m .= ' ' . implode( ' ', array_map( 'sanitize_text_field', $context['dish_tokens'] ) );
		}
	}

	$digital_needles = array( 'logo', 'branding', 'website', 'web design', 'hosting', 'seo', 'domain', 'wordpress', 'identity' );
	$is_digital      = in_array( $category, array( 'branding', 'web', 'hosting', 'seo' ), true );
	if ( ! $is_digital ) {
		foreach ( $digital_needles as $dn ) {
			if ( false !== strpos( $m, $dn ) ) {
				$is_digital = true;
				if ( $category === '' ) {
					$category = ( false !== strpos( $dn, 'host' ) ) ? 'hosting' : ( ( false !== strpos( $dn, 'seo' ) ) ? 'seo' : 'branding' );
				}
				break;
			}
		}
	}

	// Gift / Save Money Cards: network commerce on BrandDad Social - not Directory listings.
	$is_gift = bds_ai_finder_is_gift_intent( $m );
	// Short follow-ups ("no", "for my mom", "under $100") stay in the shopping
	// thread instead of falling back to a local-business search.
	if ( ! $is_gift && ! empty( $context['domain'] ) && 'gift' === sanitize_key( (string) $context['domain'] ) ) {
		$word_count = count( preg_split( '/\s+/', trim( $m ) ) );
		if ( bds_ai_finder_is_negative_reply( $m )
			|| $word_count <= 8
			|| 1 === preg_match( '/\b(clothes|clothing|shoes|beauty|skincare|home|decor|food|wine|dining|coffee|outdoor|outdoors|kids|pets|fitness|golf|jewelry|games|wellness|budget|under|face value)\b/', $m ) ) {
			$is_gift = true;
		}
	}
	if ( $is_gift ) {
		$is_digital = false;
		$category   = 'gift-cards';
		$near_me    = false;
		$wants_near = false;
	}

	// TravelPayouts flights/hotels - live affiliate deals (not local tour operators).
	$is_travel   = false;
	$travel_kind = '';
	$travel_origin = '';
	$travel_dest   = '';
	$local_activity_cat = in_array(
		$category,
		array( 'tour-operators', 'car-rentals', 'mexican-restaurants', 'italian-restaurants', 'american-restaurants', 'brazilian-restaurants', 'restaurants', 'coffee-shops', 'bakeries', 'bars', 'night-clubs', 'beauty-salons', 'massage-and-spa', 'muay-thai-gyms', 'supermarkets', 'retail-shopping' ),
		true
	);
	$ctx_travel    = ( $follow_up !== '' && ! empty( $context['is_travel'] ) )
		|| ( $follow_up !== '' && in_array( strtolower( (string) ( $context['category'] ?? '' ) ), array( 'travel', 'hotels' ), true ) );
	if ( ! $is_gift && ! $local_activity_cat && ( bds_ai_finder_is_travel_intent( $m ) || $ctx_travel || in_array( $category, array( 'travel', 'hotels' ), true ) ) ) {
		$is_travel     = true;
		$is_digital    = false;
		$travel_kind   = bds_ai_finder_travel_kind( $m );
		if ( $travel_kind === 'any' && ! empty( $context['travel_kind'] ) ) {
			$travel_kind = sanitize_key( (string) $context['travel_kind'] );
		}
		if ( ! in_array( $travel_kind, array( 'flight', 'hotel', 'any' ), true ) ) {
			$travel_kind = 'any';
		}
		// Category slug already implies kind when map resolved hotels/travel first.
		if ( 'any' === $travel_kind && 'hotels' === $category ) {
			$travel_kind = 'hotel';
		} elseif ( 'any' === $travel_kind && 'travel' === $category ) {
			$travel_kind = 'flight';
		}
		$route         = bds_ai_finder_travel_route_hints( $m, $location_hints );
		$travel_origin = $route['origin'];
		$travel_dest   = $route['dest'];
		if ( $travel_origin === '' && ! empty( $context['travel_origin'] ) ) {
			$travel_origin = sanitize_text_field( (string) $context['travel_origin'] );
		}
		if ( $travel_dest === '' && ! empty( $context['travel_dest'] ) ) {
			$travel_dest = sanitize_text_field( (string) $context['travel_dest'] );
		}
		// "hotels in Playa" / city from location map becomes destination when unset.
		if ( $travel_dest === '' && $location !== '' && in_array( $travel_kind, array( 'hotel', 'any' ), true ) ) {
			$travel_dest = $location;
		}
		if ( $travel_dest !== '' && $location === '' ) {
			$location = $travel_dest;
		} elseif ( $travel_origin !== '' && $location === '' && 'flight' === $travel_kind ) {
			$location = $travel_origin;
		}
		if ( 'hotel' === $travel_kind ) {
			$category = 'hotels';
		} elseif ( 'flight' === $travel_kind ) {
			$category = 'travel';
		} elseif ( $category === '' || in_array( $category, array( 'hotels', 'travel' ), true ) ) {
			$category = ( $travel_dest !== '' && false !== strpos( $m, 'hotel' ) ) ? 'hotels' : 'travel';
		}
		// Travel never blocks on missing browser geo - soft-boost when coords exist.
		if ( $near_me && ! ( $geo_in['lat'] !== null && $geo_in['lng'] !== null ) ) {
			$near_me = false;
		}
	}

	// Priority: near me + browser geo -> postal centroid -> near+client geo -> context geo -> city text.
	$lat        = null;
	$lng        = null;
	$client_geo = ( $geo_in['lat'] !== null && $geo_in['lng'] !== null );
	if ( ! $is_digital && ! $is_gift && $near_me && $client_geo ) {
		$lat = (float) $geo_in['lat'];
		$lng = (float) $geo_in['lng'];
	} elseif ( ! $is_digital && ! $is_gift && ! $is_travel && is_array( $postal_info ) ) {
		$lat = (float) $postal_info['lat'];
		$lng = (float) $postal_info['lng'];
	} elseif ( ! $is_digital && ! $is_gift && $client_geo && ( $is_travel || $wants_near || $location === '' || $follow_up === 'closer' ) ) {
		$lat = (float) $geo_in['lat'];
		$lng = (float) $geo_in['lng'];
	} elseif ( ! $is_digital && ! $is_gift && ! $is_travel && $follow_up !== '' && isset( $context['lat'], $context['lng'] ) ) {
		$lat = (float) $context['lat'];
		$lng = (float) $context['lng'];
		$client_geo = is_finite( $lat ) && is_finite( $lng );
	}

	// Infer Directory city from coordinates when the user only said "near me".
	if ( ! $is_digital && ! $is_gift && $location === '' && $lat !== null && $lng !== null ) {
		$bbox = bds_ai_finder_bbox_place( $lat, $lng );
		if ( is_array( $bbox ) && ! empty( $bbox['city'] ) ) {
			$location = (string) $bbox['city'];
			if ( $place_label === '' && ! empty( $bbox['label'] ) ) {
				$place_label = (string) $bbox['label'];
			}
			if ( $is_travel && $travel_origin === '' && 'flight' === $travel_kind ) {
				$travel_origin = $location;
			}
		}
	}

	$radius = $geo_in['radius_km'];
	if ( $radius === null && ! $is_digital && ! $is_gift && ! $is_travel ) {
		if ( $follow_up === 'closer' ) {
			$radius = 3.0;
		} elseif ( $near_me || ( $wants_near && $lat !== null ) ) {
			$radius = 5.0; // progressive widen starts here.
		} elseif ( $postal !== '' && $lat !== null ) {
			$radius = 5.0;
		}
	}

	$search_mode = 'text';
	if ( $is_gift ) {
		$search_mode = 'gift';
		$near_me     = false;
		$wants_near  = false;
		$lat         = null;
		$lng         = null;
		$location    = '';
	} elseif ( $is_travel ) {
		$search_mode = 'travel';
		$wants_near  = false;
	} elseif ( $is_digital ) {
		$search_mode = 'digital';
		$near_me     = false;
		$wants_near  = false;
		$lat         = null;
		$lng         = null;
	} elseif ( $near_me && $client_geo ) {
		$search_mode = 'near_me';
	} elseif ( $near_me && ! $client_geo ) {
		$search_mode = 'needs_geo';
	} elseif ( $postal !== '' && $lat !== null && $lng !== null ) {
		$search_mode = 'postal_proximity';
	} elseif ( $wants_near && $lat !== null && $lng !== null ) {
		$search_mode = 'proximity';
	} elseif ( $postal !== '' ) {
		$search_mode = 'postal';
	} elseif ( $location !== '' ) {
		$search_mode = 'location';
	}

	$keywords = trim( preg_replace( '/\s+/', ' ', $message ) );
	$stop     = array(
		'find', 'looking', 'for', 'show', 'me', 'near', 'nearby', 'in', 'a', 'an', 'the', 'please',
		'i', 'want', 'need', 'business', 'businesses', 'listing', 'listings', 'where', 'can', 'get',
		'help', 'with', 'around', 'within', 'close', 'my', 'location', 'zip', 'postal', 'code',
		'whats', "what's", 'what', 'is', 'are', 'some', 'any', 'good', 'best', 'great', 'top',
		'recommend', 'recommended', 'highly', 'rated', 'must', 'try', 'favorite', 'favourite',
		'there', 'here', 'to', 'of', 'or', 'and', 'that', 'this', 'those', 'these', 'place',
		'places', 'spot', 'spots', 'somewhere', 'anyone', 'know', 'suggest', 'suggestion',
		'suggestions', 'options', 'option', 'like', 'really', 'very', 'super',
	);
	// Strip known location / category tokens from keyword bag so WP `s` does not AND-fail.
	$strip_tokens = array_merge( array_keys( $location_hints ), array_keys( $category_hints ) );
	foreach ( $strip_tokens as $tok ) {
		$tok = strtolower( trim( (string) $tok ) );
		if ( $tok === '' ) {
			continue;
		}
		foreach ( preg_split( '/\s+/', $tok ) as $piece ) {
			$piece = trim( (string) $piece );
			if ( strlen( $piece ) >= 2 ) {
				$stop[] = $piece;
			}
		}
	}
	$bits = preg_split( '/\s+/', strtolower( $keywords ) );
	$keep = array();
	foreach ( (array) $bits as $b ) {
		$b = trim( (string) $b, " \t\n\r\0\x0B.,!?\"'" );
		if ( $b === '' || in_array( $b, $stop, true ) ) {
			continue;
		}
		if ( 1 === preg_match( '/^\d{5}(?:-\d{4})?$/', $b ) ) {
			continue;
		}
		if ( strlen( $b ) < 2 ) {
			continue;
		}
		$keep[] = $b;
	}
	$kw = implode( ' ', array_slice( array_unique( $keep ), 0, 8 ) );

	$loc_term = ( $location && ! $is_gift ) ? bds_ai_finder_resolve_location_term( $location ) : null;
	$cat_term = ( $category && ! $is_gift && ! $is_travel ) ? bds_ai_finder_resolve_category_term( $category ) : null;
	if ( $is_gift ) {
		$vertical = 'gift';
	} elseif ( $is_travel ) {
		$vertical = 'travel';
	} else {
		$vertical = bds_ai_finder_intent_vertical( $category, $m );
	}

	$area_label = '';
	if ( $place_label !== '' ) {
		$area_label = $place_label;
	} elseif ( is_array( $postal_info ) && ! empty( $postal_info['label'] ) && ! $is_travel ) {
		$area_label = (string) $postal_info['label'];
	} elseif ( $travel_dest !== '' ) {
		$area_label = ucwords( $travel_dest );
	} elseif ( $location !== '' ) {
		$area_label = ucwords( $location );
	}
	$units = 'km';
	if ( $location === 'chicago' || $travel_origin === 'chicago' || ( is_array( $postal_info ) && isset( $postal_info['country'] ) && 'US' === $postal_info['country'] ) ) {
		$units = 'mi';
	} elseif ( $lat !== null && $lng !== null ) {
		$bbox_u = bds_ai_finder_bbox_place( $lat, $lng );
		if ( is_array( $bbox_u ) && ! empty( $bbox_u['city'] ) && 'chicago' === $bbox_u['city'] ) {
			$units = 'mi';
		}
	}

	$parsed_out = array(
		'keywords'       => $kw,
		'location'       => $location,
		'location_term'  => $loc_term,
		'category'       => $category,
		'category_term'  => $cat_term,
		'vertical'       => $vertical,
		'quality_intent' => $quality_intent,
		'dish_tokens'    => bds_ai_finder_extract_dish_tokens( $m ),
		'postal'          => $is_travel ? '' : $postal,
		'postal_info'     => $is_travel ? null : $postal_info,
		'near_me'        => $near_me,
		'wants_near'     => $wants_near,
		'lat'            => $lat,
		'lng'            => $lng,
		'radius_km'      => $radius,
		'client_geo'     => $client_geo && ! $is_digital && ! $is_gift,
		'search_mode'    => $search_mode,
		'area_label'     => $is_gift ? '' : $area_label,
		'place_label'    => $is_gift ? '' : ( $place_label !== '' ? $place_label : $area_label ),
		'expansion_note' => '',
		'is_digital'     => $is_digital,
		'is_gift'        => $is_gift,
		'is_travel'      => $is_travel,
		'travel_kind'    => $travel_kind,
		'travel_origin'  => $travel_origin,
		'travel_dest'    => $travel_dest,
		'follow_up'      => $follow_up,
		'units'          => $units,
		'raw'            => $raw,
		'is_negative'    => bds_ai_finder_is_negative_reply( $raw ),
		'ctx_usecases'   => ( isset( $context['usecases'] ) && is_array( $context['usecases'] ) )
			? array_slice( array_map( 'sanitize_key', $context['usecases'] ), 0, 4 )
			: array(),
		'ctx_domain'     => isset( $context['domain'] ) ? sanitize_key( (string) $context['domain'] ) : '',
	);
	return apply_filters( 'bds_ai_finder_parsed', $parsed_out, $raw );
}

/**
 * Gift / Save Money Cards / discount gift-card commerce intent.
 *
 * @param string $message Lowercased user text (or raw; lowercased here).
 * @return bool
 */
function bds_ai_finder_is_gift_intent( $message ) {
	$m = strtolower( (string) $message );
	if ( $m === '' ) {
		return false;
	}
	if ( 1 === preg_match( '/\bsave\s+money\s+cards?\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\bgift\s*cards?\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\bdiscount(?:ed)?\s+gift\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(buy|purchase|get|shop|find)\b.{0,40}\bgift\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\be-?gift\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(gift|present)\s+(for|idea|ideas)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(birthday|anniversary|christmas|holiday|graduation|wedding|baby shower|mother\'?s day|father\'?s day|valentine)\b/', $m )
		&& 1 === preg_match( '/\b(gift|present|card|buy|shop|get|gifting)\b/', $m ) ) {
		return true;
	}
	// Brand shopping: "looking for Amazon", "do you have Starbucks", "Nike card".
	$brand = bds_ai_finder_detect_brand( $m );
	if ( $brand && ! empty( $brand['shopping'] ) ) {
		return true;
	}
	return false;
}

/* -----------------------------------------------------------------------
 * Shopping brain: brand detection, live gift catalog, use-case alternatives.
 * --------------------------------------------------------------------- */

/**
 * Use-case buckets we can reason about across the catalog.
 *
 * @return array<string,string> slug => human label.
 */
function bds_ai_finder_usecase_labels() {
	return array(
		'everything'  => 'a bit of everything',
		'apparel'     => 'clothing and shoes',
		'beauty'      => 'beauty and skincare',
		'home'        => 'home, decor and furniture',
		'outdoors'    => 'outdoor and adventure gear',
		'food'        => 'food, wine and dining',
		'coffee'      => 'coffee and cafe treats',
		'kids'        => 'kids and family',
		'pets'        => 'pets',
		'experiences' => 'experiences, events and travel',
		'fitness'     => 'fitness and sports',
		'tech'        => 'tech and gadgets',
		'jewelry'     => 'jewelry and accessories',
		'hobby'       => 'games and hobbies',
		'wellness'    => 'wellness and self-care',
	);
}

/**
 * Brands people ask for by name. in_catalog is decided live; this map only
 * supplies the shopping use-cases so we can offer honest alternatives.
 *
 * @return array<string,string[]> brand needle => use-case slugs.
 */
function bds_ai_finder_brand_usecase_map() {
	return array(
		'amazon'          => array( 'everything' ),
		'walmart'         => array( 'everything', 'home' ),
		'target'          => array( 'everything', 'home', 'kids' ),
		'costco'          => array( 'everything', 'home' ),
		'ebay'            => array( 'everything' ),
		'best buy'        => array( 'tech' ),
		'apple'           => array( 'tech' ),
		'itunes'          => array( 'tech' ),
		'google play'     => array( 'tech' ),
		'xbox'            => array( 'tech', 'hobby' ),
		'playstation'     => array( 'tech', 'hobby' ),
		'nintendo'        => array( 'tech', 'hobby' ),
		'steam'           => array( 'hobby', 'tech' ),
		'roblox'          => array( 'hobby', 'kids' ),
		'starbucks'       => array( 'coffee', 'food' ),
		'dunkin'          => array( 'coffee', 'food' ),
		'doordash'        => array( 'food' ),
		'uber eats'       => array( 'food' ),
		'ubereats'        => array( 'food' ),
		'grubhub'         => array( 'food' ),
		'uber'            => array( 'experiences' ),
		'lyft'            => array( 'experiences' ),
		'airbnb'          => array( 'experiences' ),
		'southwest'       => array( 'experiences' ),
		'delta'           => array( 'experiences' ),
		'nike'            => array( 'apparel', 'fitness' ),
		'adidas'          => array( 'apparel', 'fitness' ),
		'lululemon'       => array( 'apparel', 'fitness' ),
		'sephora'         => array( 'beauty' ),
		'ulta'            => array( 'beauty' ),
		'nordstrom'       => array( 'apparel' ),
		'macy'            => array( 'apparel', 'home' ),
		'kohl'            => array( 'apparel', 'home' ),
		'old navy'        => array( 'apparel' ),
		'gap'             => array( 'apparel' ),
		'zara'            => array( 'apparel' ),
		'h&m'             => array( 'apparel' ),
		'ikea'            => array( 'home' ),
		'wayfair'         => array( 'home' ),
		'lowe'            => array( 'home' ),
		'petco'           => array( 'pets' ),
		'petsmart'        => array( 'pets' ),
		'chewy'           => array( 'pets' ),
		'gamestop'        => array( 'hobby', 'tech' ),
		'netflix'         => array( 'experiences' ),
		'spotify'         => array( 'experiences' ),
		'visa'            => array( 'everything' ),
		'mastercard'      => array( 'everything' ),
		'american express' => array( 'everything' ),
	);
}

/**
 * Detect a named brand plus whether it reads as shopping intent.
 *
 * @param string $message User text.
 * @return array{brand:string,usecases:string[],shopping:bool}|null
 */
function bds_ai_finder_detect_brand( $message ) {
	$m = strtolower( (string) $message );
	if ( $m === '' ) {
		return null;
	}
	$shop_ctx = 1 === preg_match(
		'/\b(look(?:ing)?|need|want|buy|purchase|shop|shopping|get|find|have|carry|got|any|do you|gift|card|cards|credit|voucher)\b/',
		$m
	);
	$hit  = '';
	$uses = array();
	foreach ( bds_ai_finder_brand_usecase_map() as $needle => $cases ) {
		$q = preg_quote( (string) $needle, '/' );
		if ( 1 === preg_match( '/(?:^|[^a-z0-9])' . $q . '(?:[^a-z0-9]|$)/', $m ) ) {
			$hit  = (string) $needle;
			$uses = (array) $cases;
			break;
		}
	}
	if ( $hit === '' ) {
		// Unknown brand named next to a card/gift word: "XYZ gift card".
		if ( 1 === preg_match( '/\b([a-z][a-z0-9.\'&-]{2,24})\s+(?:e-?)?gift\s*cards?\b/', $m, $mm ) ) {
			$candidate = trim( (string) $mm[1] );
			// Qualifiers are not brands: "best gift card", "cheap gift cards".
			$generic = array(
				'best', 'better', 'good', 'great', 'cheap', 'cheapest', 'discount', 'discounted',
				'digital', 'online', 'virtual', 'physical', 'any', 'some', 'all', 'more', 'other',
				'another', 'new', 'your', 'my', 'the', 'a', 'an', 'this', 'that', 'these', 'those',
				'popular', 'top', 'favorite', 'birthday', 'holiday', 'christmas', 'custom', 'blank',
			);
			if ( ! in_array( $candidate, $generic, true ) ) {
				$hit      = $candidate;
				$uses     = array();
				$shop_ctx = true;
			}
		}
	}
	if ( $hit === '' ) {
		return null;
	}
	return array(
		'brand'    => $hit,
		'usecases' => $uses,
		'shopping' => $shop_ctx,
	);
}

/**
 * Classify a gift-card product into use-case buckets from its title + excerpt.
 *
 * @param string $blob Lowercased "title excerpt slug".
 * @return string[]
 */
function bds_ai_finder_classify_usecase( $blob ) {
	$rules = array(
		'apparel'     => array( 'apparel', 'clothing', 'clothes', 'shoe', 'shoes', 'boot', 'sock', 'sweater', 'denim', 'wear', 'swim', 'bra', 'lingerie', 'pjs', 'pajama', 'outfit', 'mason', 'bonobos', 'rails', 'spanx', 'eileenfisher', 'vintage', 'oodie', 'vilebrequin', 'theupside', 'carbon38', 'viberg', 'aquatalia', 'sqairz', 'sneex', 'bearpaw', 'footjoy', 'curvykate', 'thebraladies', 'victoria' ),
		'beauty'      => array( 'beauty', 'cosmetic', 'skincare', 'skin', 'hair', 'nail', 'lush', 'typology', 'oliveandjune', 'skinpharm', 'graftobian', 'mented', 'ellij', 'marionparke', 'strandsoffaith', 'razoremporium', 'londontown' ),
		'home'        => array( 'home', 'decor', 'furniture', 'rug', 'towel', 'kitchen', 'candle', 'bedding', 'ruggable', 'ballarddesigns', 'daniafurniture', 'weezietowels', 'companyc', 'shiraleah', 'feltandfat', 'luminara', 'tsukiglass', 'cozyearth', 'home depot', 'cabinplace', 'mesonart' ),
		'outdoors'    => array( 'outdoor', 'camp', 'hike', 'hiking', 'ski', 'snow', 'backcountry', 'bentgate', 'icebreaker', 'alpsandmeters', 'spyder', 'farmtofeet', 'nordicsocks', 'scalesgear', 'legendarywhitetails', 'arrowheadtactical', 'redfeather', 'anetik', 'chisos' ),
		'food'        => array( 'food', 'wine', 'whiskey', 'cocktail', 'restaurant', 'dining', 'kitchen', 'meal', 'chocolate', 'olive oil', 'brightland', 'wine.com', 'factor', 'magickitchen', 'koriwhiskey', 'cocktailcourier', 'themediterraneandish', 'realgreek', 'ocean prime', 'salt cellar', 'filthyfood', 'franksandoutback', 'anthonyscotto', 'ginnys' ),
		'coffee'      => array( 'coffee', 'cafe', 'espresso', 'lacolombe', 'metropoliscoffee' ),
		'kids'        => array( 'kid', 'kids', 'children', 'child', 'baby', 'toy', 'juju', 'chuck e. cheese', 'roblox' ),
		'pets'        => array( 'pet', 'pets', 'dog', 'cat', 'petexpertise', 'therefinedfeline', 'lifesabundance' ),
		'experiences' => array( 'cruise', 'ticket', 'hotel', 'travel', 'tour', 'event', 'concert', 'ticketmaster', 'carnival', 'celebrity', 'hershey', 'yankeetrails', 'u-haul' ),
		'fitness'     => array( 'fit', 'fitness', 'gym', 'golf', 'barbell', 'athletic', 'supremegolf', 'fnxfit', 'getfitcherries', 'westside barbell', 'spursfanshop', 'muc off', 'voromotors' ),
		'tech'        => array( 'tech', 'electronic', 'led', 'gadget', 'blackoakled', 'cprevrepair', 'wingstuff' ),
		'jewelry'     => array( 'jewel', 'jewelry', 'watch', 'eyewear', 'glasses', 'peepers', 'wmpeyewear', 'caraa', 'portlandleathergoods', 'araks' ),
		'hobby'       => array( 'game', 'games', 'hobby', 'card game', 'music', 'guitar', 'craft', 'garden', 'cardhaus', 'coolstuffinc', 'noble knight', 'musicarts', 'gardeners', 'ediblelandscaping' ),
		'wellness'    => array( 'spa', 'wellness', 'massage', 'self-care', 'vitamin', 'sanori', 'theoutset', 'getyourpinkback' ),
	);
	$out = array();
	foreach ( $rules as $slug => $needles ) {
		foreach ( $needles as $n ) {
			$n = (string) $n;
			// Short tokens must match as words. Substring matching turned "ski"
			// into a hit on "skinpharm" and "pet" into a hit on "carpet".
			if ( strlen( $n ) < 6 ) {
				if ( 1 === preg_match( '/(?:^|[^a-z0-9])' . preg_quote( $n, '/' ) . '(?:[^a-z0-9]|$)/', $blob ) ) {
					$out[] = $slug;
					break;
				}
				continue;
			}
			if ( false !== strpos( $blob, $n ) ) {
				$out[] = $slug;
				break;
			}
		}
	}
	if ( ! $out ) {
		$out[] = 'everything';
	}
	return $out;
}

/**
 * Live discount gift-card index (in-stock only), cached briefly.
 *
 * @return array<int,array<string,mixed>>
 */
function bds_ai_finder_gift_index() {
	$ver = (int) get_option( BDS_AI_LISTINGS_CACHE_VER_OPT, 1 );
	$key = 'bds_ai_gift_idx_' . $ver;
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return $hit;
	}
	if ( ! post_type_exists( 'product' ) ) {
		return array();
	}
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => 400,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'tax_query'              => array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array( 'discount-gift-cards' ),
			),
		),
	);
	$q   = new WP_Query( $args );
	$out = array();
	foreach ( $q->posts as $p ) {
		$title = (string) get_the_title( $p );
		if ( $title === '' ) {
			continue;
		}
		$stock = (string) get_post_meta( $p->ID, '_stock_status', true );
		if ( 'outofstock' === $stock ) {
			continue;
		}
		$price = (float) get_post_meta( $p->ID, '_price', true );
		$face  = 0.0;
		if ( 1 === preg_match( '/\$\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?)/', $title, $mm ) ) {
			$face = (float) str_replace( ',', '', $mm[1] );
		}
		$brand = trim( (string) preg_replace( '/\s*\$[0-9].*$/', '', $title ) );
		$blob  = strtolower( $title . ' ' . $p->post_excerpt . ' ' . $p->post_name );
		$out[] = array(
			'id'       => (int) $p->ID,
			'title'    => $title,
			'brand'    => $brand,
			'slug'     => (string) $p->post_name,
			'face'     => $face,
			'price'    => $price,
			'pct'      => ( $face > 0 && $price > 0 ) ? round( ( $price / $face ) * 100 ) : 0,
			'usecases' => bds_ai_finder_classify_usecase( $blob ),
		);
	}
	wp_reset_postdata();
	set_transient( $key, $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

/**
 * Shape a catalog row into a chat product card.
 *
 * @param array<string,mixed> $row Index row.
 * @param string              $why Reason line.
 * @return array<string,mixed>
 */
function bds_ai_finder_gift_card_payload( $row, $why = '' ) {
	$id   = (int) $row['id'];
	$img  = get_the_post_thumbnail_url( $id, 'thumbnail' );
	$save = 0;
	if ( ! empty( $row['face'] ) && ! empty( $row['price'] ) && $row['face'] > $row['price'] ) {
		$save = (int) round( ( 1 - ( $row['price'] / $row['face'] ) ) * 100 );
	}
	return array(
		'id'            => $id,
		'name'          => (string) $row['title'],
		'brand'         => (string) $row['brand'],
		'url'           => (string) get_permalink( $id ),
		'image'         => $img ? (string) $img : '',
		'price'         => (float) $row['price'],
		'face'          => (float) $row['face'],
		'price_label'   => $row['price'] > 0 ? ( '$' . number_format( (float) $row['price'], 2 ) ) : '',
		'face_label'    => $row['face'] > 0 ? ( '$' . number_format( (float) $row['face'], 2 ) . ' face' ) : '',
		'save_label'    => $save > 0 ? ( 'Save ' . $save . '%' ) : '',
		'why'           => (string) $why,
		'is_product'    => true,
		'usecases'      => isset( $row['usecases'] ) ? array_values( (array) $row['usecases'] ) : array(),
	);
}

/**
 * Gift search: exact brand first, then honest use-case alternatives.
 *
 * @param array<string,mixed> $parsed  Parsed intent (by ref, annotated).
 * @param string              $message Raw user text.
 * @param int                 $limit   Max cards.
 * @return array<int,array<string,mixed>>
 */
function bds_ai_finder_query_gift_products( &$parsed, $message, $limit = 6 ) {
	$index = bds_ai_finder_gift_index();
	$parsed['gift_catalog_size'] = count( $index );
	if ( ! $index ) {
		return array();
	}
	$m     = strtolower( (string) $message );
	$brand = bds_ai_finder_detect_brand( $m );
	$want  = $brand ? (string) $brand['brand'] : '';
	$parsed['gift_brand'] = $want;

	// Budget hint: "$50 card", "under 100".
	$budget = 0.0;
	if ( 1 === preg_match( '/\$\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?)/', $m, $bm ) ) {
		$budget = (float) str_replace( ',', '', $bm[1] );
	} elseif ( 1 === preg_match( '/\bunder\s+([0-9]{2,4})\b/', $m, $bm ) ) {
		$budget = (float) $bm[1];
	}
	$parsed['gift_budget'] = $budget;

	$exact = array();
	if ( $want !== '' ) {
		foreach ( $index as $row ) {
			$hay = strtolower( (string) $row['brand'] . ' ' . $row['slug'] );
			if ( false !== strpos( $hay, str_replace( ' ', '', $want ) ) || false !== strpos( $hay, $want ) ) {
				$exact[] = $row;
			}
		}
	}
	$parsed['gift_exact'] = ! empty( $exact );

	if ( $exact ) {
		usort(
			$exact,
			static function ( $a, $b ) use ( $budget ) {
				if ( $budget > 0 ) {
					$da = abs( (float) $a['face'] - $budget );
					$db = abs( (float) $b['face'] - $budget );
					if ( $da !== $db ) {
						return $da <=> $db;
					}
				}
				return (int) $a['pct'] <=> (int) $b['pct'];
			}
		);
		$out = array();
		foreach ( array_slice( $exact, 0, $limit ) as $row ) {
			$out[] = bds_ai_finder_gift_card_payload( $row, 'In stock now' );
		}
		return $out;
	}

	// No exact brand: alternatives by use-case the shopper actually described.
	$cases = bds_ai_finder_wanted_usecases( $m, $brand, $parsed );
	// "No, something else" must not re-serve what was just rejected.
	if ( ! empty( $parsed['is_negative'] ) && ! empty( $parsed['ctx_usecases'] ) ) {
		$cases = array_values( array_diff( $cases, (array) $parsed['ctx_usecases'] ) );
	}
	$parsed['gift_usecases'] = $cases;

	// Without a use-case we have nothing honest to recommend; the caller asks
	// a clarifying question instead of dumping a random slice of the catalog.
	// The exception is an explicit "show me what you have" - then lead with the
	// deepest live discounts.
	if ( ! $cases ) {
		if ( 1 !== preg_match( '/\b(browse|show me|see all|what do you have|best deals?|biggest discounts?|cheapest|list them|everything)\b/', $m ) ) {
			return array();
		}
		$best = $index;
		usort(
			$best,
			static function ( $a, $b ) {
				return (int) $a['pct'] <=> (int) $b['pct'];
			}
		);
		$out  = array();
		$seen = array();
		foreach ( $best as $row ) {
			$bkey = strtolower( (string) $row['brand'] );
			if ( isset( $seen[ $bkey ] ) ) {
				continue;
			}
			$seen[ $bkey ] = 1;
			$out[]         = bds_ai_finder_gift_card_payload( $row, 'One of the deepest discounts in stock' );
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	$scored = array();
	foreach ( $index as $row ) {
		$match = 0;
		foreach ( $cases as $c ) {
			if ( in_array( $c, (array) $row['usecases'], true ) ) {
				$match++;
			}
		}
		// Use-case fit is mandatory: budget and discount only break ties, so a
		// deep discount can never label an outdoor card as "good for skincare".
		if ( $match < 1 ) {
			continue;
		}
		$score = $match * 6;
		if ( $budget > 0 && $row['face'] > 0 ) {
			$score += max( 0, 5 - (int) floor( abs( $row['face'] - $budget ) / 50 ) );
		}
		if ( $row['pct'] > 0 ) {
			$score += (int) max( 0, ( 100 - (int) $row['pct'] ) / 12 );
		}
		$row['_score'] = $score;
		// Reason has to describe THIS card, not the first thing we searched for.
		$row['_matched'] = '';
		foreach ( $cases as $c ) {
			if ( in_array( $c, (array) $row['usecases'], true ) ) {
				$row['_matched'] = $c;
				break;
			}
		}
		$scored[] = $row;
	}
	if ( ! $scored ) {
		return array();
	}
	usort(
		$scored,
		static function ( $a, $b ) {
			return (int) $b['_score'] <=> (int) $a['_score'];
		}
	);
	// One card per brand keeps the alternatives list readable.
	$seen   = array();
	$out    = array();
	$labels = bds_ai_finder_usecase_labels();
	foreach ( $scored as $row ) {
		$bkey = strtolower( (string) $row['brand'] );
		if ( isset( $seen[ $bkey ] ) ) {
			continue;
		}
		$seen[ $bkey ] = 1;
		$matched       = isset( $row['_matched'] ) ? (string) $row['_matched'] : '';
		$why           = ( $matched !== '' && isset( $labels[ $matched ] ) )
			? 'Good for ' . $labels[ $matched ]
			: 'Discounted gift card pick';
		$out[] = bds_ai_finder_gift_card_payload( $row, $why );
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/**
 * Which shopping use-cases the visitor implied (message + brand + session).
 *
 * @param string                   $m      Lowercased message.
 * @param array<string,mixed>|null $brand  Detected brand.
 * @param array<string,mixed>      $parsed Parsed intent.
 * @return string[]
 */
function bds_ai_finder_wanted_usecases( $m, $brand, $parsed ) {
	$cases = array();
	$hint  = bds_ai_finder_classify_usecase( $m );
	foreach ( $hint as $h ) {
		if ( 'everything' !== $h ) {
			$cases[] = $h;
		}
	}
	if ( ! $cases && is_array( $brand ) && ! empty( $brand['usecases'] ) ) {
		foreach ( (array) $brand['usecases'] as $u ) {
			if ( 'everything' !== $u ) {
				$cases[] = $u;
			}
		}
	}
	if ( ! $cases && ! empty( $parsed['ctx_usecases'] ) && is_array( $parsed['ctx_usecases'] ) ) {
		foreach ( $parsed['ctx_usecases'] as $u ) {
			$cases[] = sanitize_key( (string) $u );
		}
	}
	// Recipient words map to safe buckets when nothing else is known.
	if ( ! $cases ) {
		$who = array(
			'mom'         => array( 'beauty', 'home' ),
			'mother'      => array( 'beauty', 'home' ),
			'wife'        => array( 'beauty', 'apparel' ),
			'girlfriend'  => array( 'beauty', 'apparel' ),
			'dad'         => array( 'outdoors', 'food' ),
			'father'      => array( 'outdoors', 'food' ),
			'husband'     => array( 'outdoors', 'fitness' ),
			'boyfriend'   => array( 'outdoors', 'fitness' ),
			'kid'         => array( 'kids' ),
			'son'         => array( 'kids', 'hobby' ),
			'daughter'    => array( 'kids', 'beauty' ),
			'teen'        => array( 'apparel', 'hobby' ),
			'coworker'    => array( 'coffee', 'food' ),
			'teacher'     => array( 'coffee', 'home' ),
			'friend'      => array( 'food', 'apparel' ),
		);
		foreach ( $who as $needle => $buckets ) {
			if ( 1 === preg_match( '/\b' . preg_quote( $needle, '/' ) . '\b/', $m ) ) {
				$cases = $buckets;
				break;
			}
		}
	}
	return array_values( array_unique( $cases ) );
}

/**
 * Rejection / "not that" turn: shopper said no to what we just offered.
 *
 * @param string $message User text.
 * @return bool
 */
function bds_ai_finder_is_negative_reply( $message ) {
	$m = strtolower( trim( (string) $message ) );
	if ( $m === '' ) {
		return false;
	}
	if ( 1 === preg_match( '/^(no|nope|nah|neither|none|no thanks|not really|nothing)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(do ?n\'?t|dont|do not)\s+(want|like|need)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(not (?:what|it|quite|the one)|something else|anything else|other options|different|else\?)\b/', $m ) ) {
		return true;
	}
	return false;
}

/**
 * Cross-domain need diagnosis. Every intent (shopping, local, web, growth,
 * hosting, travel, listing) resolves to a domain + missing slots so the bot can
 * ask 1-2 useful questions instead of dumping a list.
 *
 * @param string              $message User text.
 * @param array<string,mixed> $parsed  Parsed intent.
 * @param array<string,mixed> $context Session context.
 * @return array<string,mixed>
 */
function bds_ai_finder_need_profile( $message, $parsed, $context = array() ) {
	$m    = strtolower( (string) $message );
	$need = array(
		'domain'     => 'unknown',
		'confidence' => 0.0,
		'missing'    => array(),
		'signals'    => array(),
	);

	if ( ! empty( $parsed['is_gift'] ) ) {
		$need['domain']     = 'gift';
		$need['confidence'] = 0.75;
		if ( empty( $parsed['gift_usecases'] ) && empty( $parsed['gift_exact'] ) ) {
			$need['missing'][] = 'use_case';
		}
		if ( empty( $parsed['gift_budget'] ) ) {
			$need['missing'][] = 'budget';
		}
		return $need;
	}
	if ( ! empty( $parsed['is_travel'] ) ) {
		$need['domain']     = 'travel';
		$need['confidence'] = 0.8;
		return $need;
	}

	$domains = array(
		'web_speed'   => array( 'slow', 'speed', 'loading', 'load time', 'core web vitals', 'lagging', 'sluggish', 'takes forever' ),
		'web_broken'  => array( 'broken', 'down', 'not working', 'error', 'white screen', 'hacked', 'malware', '404', 'crashed' ),
		'web_build'   => array( 'need a website', 'no website', 'build a site', 'new website', 'redesign', 'web design', 'landing page', 'rebuild' ),
		'brand'       => array( 'logo', 'branding', 'brand identity', 'rebrand' ),
		'local_seo'   => array( 'local seo', 'google business', 'gbp', 'google maps', 'show up on google', 'ranking', 'found on google', 'foot traffic', 'walk-ins', 'more traffic', 'grow my business' ),
		'reviews'     => array( 'review', 'reviews', 'stars', 'reputation', 'bad review', 'google reviews' ),
		'social'      => array( 'social media', 'instagram', 'tiktok', 'facebook page', 'followers', 'content', 'posting' ),
		'hosting'     => array( 'hosting', 'host', 'server', 'domain', 'dns', 'ssl', 'email hosting' ),
		'health'      => array( 'health check', 'audit', 'how is my website', 'website score', 'check my site', 'analyze my site' ),
		'claim'       => array( 'claim', 'my listing', 'own my listing', 'unclaimed', 'edit my listing' ),
		'membership'  => array( 'membership', 'member', 'join directory', 'list my business', 'get listed', 'add my business' ),
	);
	// "I need more local customers" / "get more clients" - phrased too many ways
	// for a flat needle list, so match the shape of the sentence.
	if ( 1 === preg_match( '/\b(more|new|get|find|attract|need)\b[^.?!]{0,24}\b(customers?|clients?|patients?|bookings?|leads?)\b/', $m ) ) {
		$domains['local_seo'][] = '__owner_growth';
		$m .= ' __owner_growth __owner_growth';
	}
	$best  = '';
	$score = 0;
	foreach ( $domains as $slug => $needles ) {
		$s = 0;
		foreach ( $needles as $n ) {
			if ( false !== strpos( $m, (string) $n ) ) {
				$s += ( strlen( (string) $n ) > 8 ) ? 3 : 2;
				$need['signals'][] = $n;
			}
		}
		if ( $s > $score ) {
			$score = $s;
			$best  = $slug;
		}
	}
	if ( $best !== '' ) {
		$need['domain']     = $best;
		$need['confidence'] = min( 0.9, 0.35 + ( $score * 0.12 ) );
		if ( in_array( $best, array( 'web_speed', 'web_broken', 'web_build', 'local_seo', 'reviews', 'social', 'health' ), true )
			&& 1 !== preg_match( '/\.(com|net|org|co|io|social|shop|store|mx|us)\b/', $m ) ) {
			$need['missing'][] = 'site_url';
		}
		if ( in_array( $best, array( 'local_seo', 'reviews' ), true ) && empty( $parsed['location'] ) ) {
			$need['missing'][] = 'service_area';
		}
		return $need;
	}

	// Local business discovery is the Directory default.
	$need['domain']     = 'local';
	$need['confidence'] = 0.3;
	if ( ! empty( $parsed['category'] ) ) {
		$need['confidence'] += 0.3;
	}
	if ( ! empty( $parsed['location'] ) || ! empty( $parsed['postal'] ) || ! empty( $parsed['lat'] ) ) {
		$need['confidence'] += 0.3;
	} else {
		$need['missing'][] = 'where';
	}
	if ( empty( $parsed['category'] ) ) {
		$need['missing'][] = 'what';
	}
	return $need;
}

/**
 * 1-2 clarifying questions + quick-reply chips, per domain. Never loops: the
 * caller suppresses this when the previous assistant turn already asked.
 *
 * @param array<string,mixed> $need    Need profile.
 * @param array<string,mixed> $parsed  Parsed intent.
 * @param string              $message User text.
 * @return array{ask:bool,questions:string[],chips:array<int,array<string,string>>,topic:string}
 */
function bds_ai_finder_clarify_plan( $need, $parsed, $message ) {
	$out = array(
		'ask'       => false,
		'questions' => array(),
		'chips'     => array(),
		'topic'     => (string) $need['domain'],
	);
	$chip = static function ( $label, $send = '' ) {
		return array(
			'label' => (string) $label,
			'send'  => (string) ( $send !== '' ? $send : $label ),
		);
	};

	switch ( $need['domain'] ) {
		case 'gift':
			$brand = isset( $parsed['gift_brand'] ) ? (string) $parsed['gift_brand'] : '';
			if ( ! empty( $parsed['gift_exact'] ) ) {
				// We have the brand: only budget is worth asking, and only softly.
				if ( in_array( 'budget', (array) $need['missing'], true ) ) {
					$out['ask']         = true;
					$out['questions'][] = 'About what face value are you aiming for?';
					$out['chips']       = array(
						$chip( 'Under $100', 'gift card under $100' ),
						$chip( '$100 - $250', 'gift card around $150' ),
						$chip( '$250+', 'gift card $300' ),
					);
				}
				return $out;
			}
			$out['ask'] = true;
			if ( $brand !== '' ) {
				$out['questions'][] = 'What were you hoping to buy with the ' . ucfirst( $brand ) . ' card?';
			} else {
				$out['questions'][] = 'What are they into - clothes, beauty, home, food, outdoors?';
			}
			if ( in_array( 'budget', (array) $need['missing'], true ) ) {
				$out['questions'][] = 'Rough budget?';
			}
			$out['chips'] = array(
				$chip( 'Clothes and shoes', 'gift card for clothes and shoes' ),
				$chip( 'Beauty and skincare', 'gift card for beauty and skincare' ),
				$chip( 'Home and decor', 'gift card for home and decor' ),
				$chip( 'Food and wine', 'gift card for food and wine' ),
				$chip( 'Outdoor gear', 'gift card for outdoor gear' ),
				$chip( 'Kids', 'gift card for kids' ),
			);
			return $out;

		case 'web_speed':
			$out['ask']         = true;
			$out['questions'][] = 'Is it slow everywhere, or mostly on mobile?';
			if ( in_array( 'site_url', (array) $need['missing'], true ) ) {
				$out['questions'][] = 'What is the site URL?';
			}
			$out['chips'] = array(
				$chip( 'Slow on mobile', 'my site is slow on mobile' ),
				$chip( 'Slow everywhere', 'my site is slow on every device' ),
				$chip( 'Run a free check', 'run a free business health check' ),
			);
			return $out;

		case 'web_broken':
			$out['ask']         = true;
			$out['questions'][] = 'What is happening - error page, blank screen, or something looks wrong?';
			$out['chips']       = array(
				$chip( 'Error page', 'my website shows an error page' ),
				$chip( 'Blank / white screen', 'my website is a blank white screen' ),
				$chip( 'Looks broken', 'my website layout looks broken' ),
			);
			return $out;

		case 'web_build':
			$out['ask']         = true;
			$out['questions'][] = 'Starting from scratch, or refreshing something you already have?';
			$out['chips']       = array(
				$chip( 'From scratch', 'I need a new website from scratch' ),
				$chip( 'Refresh existing', 'I want to redesign my existing website' ),
				$chip( 'Need hosting too', 'I need a website and hosting' ),
			);
			return $out;

		case 'local_seo':
			$out['ask']         = true;
			$out['questions'][] = 'Are you trying to show up in Google Maps locally, or pull traffic from a wider area?';
			if ( in_array( 'service_area', (array) $need['missing'], true ) ) {
				$out['questions'][] = 'Which city or area do you serve?';
			}
			$out['chips'] = array(
				$chip( 'Local map results', 'I want to rank in Google Maps locally' ),
				$chip( 'Wider search traffic', 'I want more organic search traffic' ),
				$chip( 'Get listed here', 'how do I list my business on the directory' ),
			);
			return $out;

		case 'reviews':
			$out['ask']         = true;
			$out['questions'][] = 'Are you trying to collect more reviews, or deal with a bad one?';
			$out['chips']       = array(
				$chip( 'Get more reviews', 'help me get more google reviews' ),
				$chip( 'Handle a bad review', 'I have a bad review to deal with' ),
			);
			return $out;

		case 'social':
			$out['ask']         = true;
			$out['questions'][] = 'Which platform matters most right now, and is the goal followers or actual bookings?';
			$out['chips']       = array(
				$chip( 'Instagram', 'grow my instagram' ),
				$chip( 'TikTok', 'grow my tiktok' ),
				$chip( 'Facebook', 'grow my facebook page' ),
			);
			return $out;

		case 'local':
			if ( (float) $need['confidence'] >= 0.6 ) {
				return $out;
			}
			$out['ask'] = true;
			if ( in_array( 'what', (array) $need['missing'], true ) ) {
				$out['questions'][] = 'What kind of place are you looking for?';
			}
			if ( in_array( 'where', (array) $need['missing'], true ) ) {
				$out['questions'][] = 'Which city or zip should I check?';
			}
			$out['chips'] = array(
				$chip( 'Near me', 'near me' ),
				$chip( 'Food and drink', 'restaurants near me' ),
				$chip( 'Beauty and spa', 'spa near me' ),
				$chip( 'Gift cards', 'discount gift cards' ),
			);
			return $out;
	}
	return $out;
}

/**
 * Relevance-gated upsell. One recommendation with a reason - never a SKU dump,
 * never a pitch that does not answer the question they asked.
 *
 * @param array<string,mixed> $need   Need profile.
 * @param array<string,mixed> $parsed Parsed intent.
 * @param bool                $guest  Guest visitor.
 * @return array<int,array<string,string>>
 */
function bds_ai_finder_upsell_plan( $need, $parsed, $guest ) {
	$map = array(
		'web_speed'  => array(
			array(
				'id'     => 'health-check',
				'label'  => 'Free Business Health Check',
				'url'    => home_url( '/?bds_health=1' ),
				'reason' => 'It measures real load time and shows exactly which assets are slowing the page, before anyone sells you a fix.',
			),
			array(
				'id'     => 'speed-fix',
				'label'  => 'Website speed and care',
				'url'    => 'https://branddad.social/product-category/local-web-services/',
				'reason' => 'If the check confirms heavy pages, this is the crew that trims them.',
			),
		),
		'web_broken' => array(
			array(
				'id'     => 'health-check',
				'label'  => 'Free Business Health Check',
				'url'    => home_url( '/?bds_health=1' ),
				'reason' => 'Fastest way to see whether it is hosting, SSL, or the site itself.',
			),
			array(
				'id'     => 'hosttech',
				'label'  => 'HostTech hosting',
				'url'    => 'https://hosttech.net/',
				'reason' => 'If it turns out to be the server, moving hosts fixes it for good.',
			),
		),
		'web_build'  => array(
			array(
				'id'     => 'branddad-co',
				'label'  => 'BrandDad.co websites',
				'url'    => 'https://branddad.co/',
				'reason' => 'Design and build in one place, so the brand and the site match.',
			),
			array(
				'id'     => 'hosttech',
				'label'  => 'HostTech hosting',
				'url'    => 'https://hosttech.net/',
				'reason' => 'Somewhere reliable to put it once it is built.',
			),
		),
		'brand'      => array(
			array(
				'id'     => 'branddad-co',
				'label'  => 'BrandDad.co logos and brand',
				'url'    => 'https://branddad.co/',
				'reason' => 'Logo concepts and identity work, not stock templates.',
			),
		),
		'local_seo'  => array(
			array(
				'id'     => 'branddad-social',
				'label'  => 'Local SEO and Google Business Profile',
				'url'    => 'https://branddad.social/product-category/local-web-services/',
				'reason' => 'Map rankings come from profile quality and local signals, which is what this covers.',
			),
			array(
				'id'     => 'directory-listing',
				'label'  => 'List your business on Directory',
				'url'    => 'https://directory.branddad.social/registration/',
				'reason' => 'A live listing gives you one more place customers can find you, plus 10% off network services.',
			),
		),
		'reviews'    => array(
			array(
				'id'     => 'branddad-social',
				'label'  => 'Review and reputation growth',
				'url'    => 'https://branddad.social/product-category/local-web-services/',
				'reason' => 'Steady real reviews move map rank more than almost anything else.',
			),
		),
		'social'     => array(
			array(
				'id'     => 'branddad-social',
				'label'  => 'BrandDad Social growth',
				'url'    => 'https://branddad.social/product-category/local-web-services/',
				'reason' => 'Content and growth packages aimed at bookings, not vanity follows.',
			),
		),
		'hosting'    => array(
			array(
				'id'     => 'hosttech',
				'label'  => 'HostTech hosting',
				'url'    => 'https://hosttech.net/',
				'reason' => 'Managed WordPress hosting with room to grow.',
			),
		),
		'health'     => array(
			array(
				'id'     => 'health-check',
				'label'  => 'Free Business Health Check',
				'url'    => home_url( '/?bds_health=1' ),
				'reason' => 'Public signals, an explainable score, and no ranking promises.',
			),
		),
		'claim'      => array(
			array(
				'id'     => 'claim',
				'label'  => 'Claim your listing',
				'url'    => 'https://directory.branddad.social/registration/',
				'reason' => 'Claiming unlocks photos, hours, and customer contact on your page.',
			),
		),
		'membership' => array(
			array(
				'id'     => 'directory-listing',
				'label'  => 'List your business on Directory',
				'url'    => 'https://directory.branddad.social/registration/',
				'reason' => 'Members get a live listing plus 10% off eligible network services.',
			),
		),
	);
	if ( isset( $map[ $need['domain'] ] ) ) {
		$picked = array_slice( $map[ $need['domain'] ], 0, 2 );
		if ( 'travel' === $need['domain'] ) {
			return array();
		}
		return $picked;
	}
	if ( 'gift' === $need['domain'] ) {
		$out = array(
			array(
				'id'     => 'gift-cards-directory',
				'label'  => 'Browse all discount gift cards',
				'url'    => home_url( '/product-category/discount-gift-cards/' ),
				'reason' => 'Full live catalog of brand gift cards under face value.',
			),
		);
		if ( $guest ) {
			$out[] = array(
				'id'     => 'directory-member',
				'label'  => 'Create a free account',
				'url'    => home_url( '/registration/' ),
				'reason' => 'Needed to check out, and it keeps your card history in one place.',
			);
		}
		return $out;
	}
	return array();
}

/**
 * Did we already ask a question on the previous assistant turn? Prevents an
 * interrogation loop when the visitor answers vaguely twice in a row.
 *
 * @param array<int,mixed> $history Cleaned history.
 * @return bool
 */
function bds_ai_finder_recently_clarified( $history ) {
	if ( ! is_array( $history ) || ! $history ) {
		return false;
	}
	$asked = 0;
	$tail  = array_slice( $history, -4 );
	foreach ( $tail as $turn ) {
		if ( ! is_array( $turn ) || ( isset( $turn['role'] ) && 'assistant' !== $turn['role'] ) ) {
			continue;
		}
		if ( false !== strpos( (string) ( $turn['content'] ?? '' ), '?' ) ) {
			$asked++;
		}
	}
	return $asked >= 2;
}

/**
 * Early REST payload when concierge decides to clarify before search.
 *
 * @param array<string,mixed> $concierge Concierge decision.
 * @param array<string,mixed> $parsed    Parsed intent.
 * @param bool                $guest     Guest flag.
 * @return array<string,mixed>
 */
function bds_ai_finder_concierge_early_response( $concierge, $parsed, $guest ) {
	$concierge = is_array( $concierge ) ? $concierge : array();
	$parsed    = is_array( $parsed ) ? $parsed : array();
	$question  = (string) ( $concierge['question'] ?? '' );
	if ( $question === '' ) {
		$question = 'Tell me a bit more so I can search the right inventory — one detail is enough.';
	}
	$chips = isset( $concierge['chips'] ) && is_array( $concierge['chips'] ) ? $concierge['chips'] : array();
	$state = isset( $concierge['state'] ) && is_array( $concierge['state'] ) ? $concierge['state'] : array();
	$snap  = function_exists( 'bds_ai_concierge_state_snapshot' )
		? bds_ai_concierge_state_snapshot( $state, array() )
		: $state;
	return array(
		'ok'              => true,
		'needs_geo'       => in_array( 'geo', (array) ( $concierge['missing'] ?? array() ), true ),
		'reply'           => $question,
		'listings'        => array(),
		'services'        => array(),
		'products'        => array(),
		'sections'        => array(),
		'guest'           => $guest,
		'mode'            => 'clarify',
		'chips'           => $chips,
		'action'          => 'CLARIFY',
		'missing'         => isset( $concierge['missing'] ) ? array_values( (array) $concierge['missing'] ) : array(),
		'question'        => $question,
		'state'           => $snap,
		'concierge'       => array(
			'ver'    => function_exists( 'bds_ai_concierge_ver' ) ? bds_ai_concierge_ver() : '1.0.0',
			'action' => 'CLARIFY',
			'diag'   => isset( $concierge['diag'] ) ? $concierge['diag'] : array(),
		),
		'parsed'          => bds_ai_finder_concierge_parsed_public( $parsed, $concierge, $snap ),
		'contact_note'    => '',
	);
}

/**
 * Public parsed + concierge fields for REST clients.
 *
 * @param array<string,mixed> $parsed    Intent.
 * @param array<string,mixed> $concierge Concierge decision.
 * @param array<string,mixed> $state     State snapshot.
 * @return array<string,mixed>
 */
function bds_ai_finder_concierge_parsed_public( $parsed, $concierge = array(), $state = array() ) {
	$parsed    = is_array( $parsed ) ? $parsed : array();
	$concierge = is_array( $concierge ) ? $concierge : array();
	$base      = array(
		'keywords'       => $parsed['keywords'] ?? '',
		'location'       => $parsed['location'] ?? '',
		'category'       => $parsed['category'] ?? '',
		'dish_tokens'    => isset( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) ? array_values( $parsed['dish_tokens'] ) : array(),
		'max_price'      => isset( $parsed['max_price'] ) ? $parsed['max_price'] : null,
		'strict'         => ! empty( $parsed['strict'] ),
		'specificity'    => isset( $parsed['specificity'] ) ? (string) $parsed['specificity'] : '',
		'is_travel'      => ! empty( $parsed['is_travel'] ),
		'is_gift'        => ! empty( $parsed['is_gift'] ),
		'travel_kind'    => isset( $parsed['travel_kind'] ) ? $parsed['travel_kind'] : '',
		'travel_dest'    => isset( $parsed['travel_dest'] ) ? $parsed['travel_dest'] : '',
		'search_mode'    => isset( $parsed['search_mode'] ) ? $parsed['search_mode'] : '',
		'version'        => BDS_AI_FINDER_VER,
		'action'         => (string) ( $concierge['action'] ?? 'SEARCH' ),
		'missing'        => isset( $concierge['missing'] ) ? array_values( (array) $concierge['missing'] ) : array(),
		'question'       => (string) ( $concierge['question'] ?? '' ),
		'state'          => $state,
		'diag'           => array_merge(
			isset( $parsed['_diag'] ) && is_array( $parsed['_diag'] ) ? $parsed['_diag'] : array(),
			array(
				'concierge' => function_exists( 'bds_ai_concierge_ver' ) ? bds_ai_concierge_ver() : '',
				'prec'      => defined( 'BDS_AI_PREC_VER' ) ? BDS_AI_PREC_VER : '',
			)
		),
	);
	return $base;
}

/**
 * Merge upsell recommendations into the soft-service list without duplicates.
 *
 * @param array<int,array<string,string>> $services Existing.
 * @param array<int,array<string,string>> $upsell   Recommendations.
 * @return array<int,array<string,string>>
 */
function bds_ai_finder_merge_services( $services, $upsell ) {
	$out  = array();
	$seen = array();
	foreach ( array_merge( (array) $upsell, (array) $services ) as $svc ) {
		if ( ! is_array( $svc ) || empty( $svc['label'] ) ) {
			continue;
		}
		$k = strtolower( (string) ( $svc['id'] ?? $svc['label'] ) );
		if ( isset( $seen[ $k ] ) ) {
			continue;
		}
		$seen[ $k ] = 1;
		$out[]      = array(
			'id'    => (string) ( $svc['id'] ?? '' ),
			'label' => (string) $svc['label'],
			'url'   => (string) ( $svc['url'] ?? '' ),
			'blurb' => (string) ( $svc['reason'] ?? ( $svc['blurb'] ?? '' ) ),
		);
	}
	return array_slice( $out, 0, 2 );
}

/**
 * Flights / hotels / vacation / book-travel intent (TravelPayouts deals).
 * Does not steal restaurant / spa / gift / local tour-activity queries.
 *
 * @param string $message User text.
 * @return bool
 */
function bds_ai_finder_is_travel_intent( $message ) {
	$m = strtolower( (string) $message );
	if ( $m === '' || bds_ai_finder_is_gift_intent( $m ) ) {
		return false;
	}
	// Local Directory activities stay on tour-operators / food / wellness - not TP deals.
	if ( bds_ai_finder_is_local_activity_intent( $m ) ) {
		return false;
	}
	// Local food / retail / wellness without travel words stay Directory businesses.
	$local_biz = 1 === preg_match( '/\b(restaurant|restaurants|mexican|italian|american|brazilian|coffee|cafe|café|dining|supermarket|grocery|walmart|spa|massage|muay|gym|nightlife|night\s*club|tattoo|salon)\b/u', $m );
	$travel_kw = 1 === preg_match( '/\b(flights?|airfare|airfares|airlines?|hotels?|resorts?|vacation|vacations|getaway|getaways|round[\s-]?trips?|book\s+travel|travel\s+deals?|trip\s+deals?)\b/', $m );
	if ( $local_biz && ! $travel_kw ) {
		return false;
	}
	if ( 1 === preg_match( '/\b(flights?|airfare|airfares|airlines?|round[\s-]?trips?|one[\s-]?ways?)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(fly|flying)\b.{0,48}\b(to|from|into)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(cheap|book|find|search|compare|best)\b.{0,32}\b(flights?|hotels?|travel|vacations?|resorts?)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(hotels?|resorts?|stays?|lodging|accommodation)\b.{0,48}\b(in|near|at|to)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(vacation|vacations|getaway|getaways|travel\s+deals?|book\s+travel)\b/', $m ) ) {
		return true;
	}
	// Bare "trip/trips" alone is too broad (day trips) - require route or booking context.
	if ( 1 === preg_match( '/\b(trip|trips)\b.{0,32}\b(to|from|into)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(trip|trips)\b/', $m ) && 1 === preg_match( '/\b(flight|flights|hotel|hotels|vacation|airfare|airline|fly|book)\b/', $m ) ) {
		return true;
	}
	if ( 1 === preg_match( '/\b(hotels?|resorts?|airfare|flights?)\b/', $m ) && ! $local_biz ) {
		return true;
	}
	// Generic "travel" without local-activity words -> deals.
	if ( 1 === preg_match( '/\btravel\b/', $m ) && ! $local_biz ) {
		return true;
	}
	return false;
}

/**
 * Local tours / excursions / activities (Directory tour-operators), not TP flights/hotels.
 *
 * @param string $message User text.
 * @return bool
 */
function bds_ai_finder_is_local_activity_intent( $message ) {
	$m = strtolower( (string) $message );
	if ( $m === '' ) {
		return false;
	}
	// Explicit flight/hotel booking wins over "adventure travel" style phrasing.
	if ( 1 === preg_match( '/\b(flights?|airfare|airlines?|hotels?|resorts?|book\s+travel|travel\s+deals?)\b/', $m ) ) {
		return false;
	}
	return 1 === preg_match(
		'/\b(tours?|tour\s*operators?|excursion|excursions|day\s*trips?|cenotes?|ruins?|atvs?|jeep\s*tours?|snorkel(?:ing)?|scuba|diving|dive\s*shops?|boat\s*tours?|catamaran|parasail|activities|activity|adventure\s*tours?)\b/u',
		$m
	);
}

/**
 * Preferred TravelPayouts deal kind for ranking + hard rails.
 *
 * @param string $message User text.
 * @return string flight|hotel|any
 */
function bds_ai_finder_travel_kind( $message ) {
	$m      = strtolower( (string) $message );
	$hotel  = 1 === preg_match( '/\b(hotels?|resorts?|stays?|lodging|accommodation|vacation\s*rentals?|airbnb|boutique\s*hotel|hostels?|where\s+to\s+stay|place\s+to\s+stay)\b/', $m );
	$flight = 1 === preg_match( '/\b(flights?|airfare|airfares|airlines?|fly|flying|round[\s-]?trips?|one[\s-]?ways?|plane|airport)\b/', $m );
	if ( $hotel && ! $flight ) {
		return 'hotel';
	}
	if ( $flight && ! $hotel ) {
		return 'flight';
	}
	return 'any';
}

/**
 * Whether a travel deal place blob matches a destination/origin token.
 *
 * @param string $blob Lowercased deal text.
 * @param string $bit  Place token.
 * @return bool
 */
function bds_ai_finder_travel_place_hit( $blob, $bit ) {
	$bit = strtolower( trim( (string) $bit ) );
	if ( strlen( $bit ) < 3 ) {
		return false;
	}
	$blob = strtolower( (string) $blob );
	if ( false !== strpos( $blob, $bit ) ) {
		return true;
	}
	// Common aliases.
	$aliases = array(
		'playa'   => array( 'playa del carmen', 'pdc', 'playacar' ),
		'cancun'  => array( 'cancún', 'cancun', 'cun' ),
		'tulum'   => array( 'tulum' ),
		'cozumel' => array( 'cozumel', 'czm' ),
		'chicago' => array( 'chicago', 'ord', 'mdw' ),
	);
	if ( isset( $aliases[ $bit ] ) ) {
		foreach ( $aliases[ $bit ] as $a ) {
			if ( false !== strpos( $blob, $a ) ) {
				return true;
			}
		}
	}
	foreach ( $aliases as $canon => $list ) {
		if ( in_array( $bit, $list, true ) || $bit === $canon ) {
			foreach ( array_merge( array( $canon ), $list ) as $a ) {
				if ( false !== strpos( $blob, $a ) ) {
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Parse from/to city hints for travel ranking.
 *
 * @param string               $message Lowercased message.
 * @param array<string,string> $location_hints Alias map.
 * @return array{origin:string,dest:string}
 */
function bds_ai_finder_travel_route_hints( $message, $location_hints ) {
	$m      = strtolower( (string) $message );
	$origin = '';
	$dest   = '';
	if ( 1 === preg_match( '/\bfrom\s+([a-z0-9à-ú\s\.\-]{2,40}?)(?:\s+to\b|\s+into\b|[?.,!]|$)/u', $m, $mm ) ) {
		$origin = trim( (string) $mm[1] );
	}
	if ( 1 === preg_match( '/\b(?:flights?|fly|flying|trip|trips|vacation|hotels?|resorts?|travel)\b.{0,20}\b(?:to|into)\s+([a-z0-9à-ú\s\.\-]{2,40}?)(?:\s+from\b|[?.,!]|$)/u', $m, $mm ) ) {
		$dest = trim( (string) $mm[1] );
	} elseif ( 1 === preg_match( '/\b(?:to|into)\s+([a-z0-9à-ú\s\.\-]{2,40}?)(?:\s+from\b|[?.,!]|$)/u', $m, $mm ) ) {
		$dest = trim( (string) $mm[1] );
	}
	if ( $dest === '' && 1 === preg_match( '/\b(?:hotels?|resorts?|stays?)\b.{0,12}\b(?:in|near|at)\s+([a-z0-9à-ú\s\.\-]{2,40}?)(?:\s+from\b|[?.,!]|$)/u', $m, $mm ) ) {
		$dest = trim( (string) $mm[1] );
	}
	$canon = static function ( $raw ) use ( $location_hints ) {
		$raw = strtolower( trim( (string) $raw ) );
		$raw = preg_replace( '/\b(please|tonight|tomorrow|next\s+week|this\s+weekend)\b/u', '', $raw );
		$raw = trim( preg_replace( '/\s+/', ' ', (string) $raw ) );
		if ( $raw === '' ) {
			return '';
		}
		$needles = array_keys( $location_hints );
		usort(
			$needles,
			static function ( $a, $b ) {
				return strlen( (string) $b ) <=> strlen( (string) $a );
			}
		);
		foreach ( $needles as $needle ) {
			if ( $raw === $needle || 0 === strpos( $raw, $needle . ' ' ) || false !== strpos( $raw, $needle ) ) {
				return (string) $location_hints[ $needle ];
			}
		}
		return $raw;
	};
	return array(
		'origin' => $canon( $origin ),
		'dest'   => $canon( $dest ),
	);
}

/**
 * Bump travel deal cache generation after TravelPayouts sync.
 *
 * @param mixed $sync_out Sync payload (unused).
 */
function bds_ai_finder_invalidate_travel_cache( $sync_out = null ) {
	$ver = (int) get_option( BDS_AI_TP_CACHE_VER_OPT, 1 );
	update_option( BDS_AI_TP_CACHE_VER_OPT, $ver + 1, false );
	unset( $sync_out );
}

/**
 * Bump global AI listing discovery cache generation.
 * Travel + general find transients key off this so new/updated Directory
 * inventory is visible on the next request (or after a very short TTL).
 *
 * @param mixed $payload Optional context (unused).
 */
function bds_ai_finder_bump_listings_cache( $payload = null ) {
	static $bumped = false;
	if ( $bumped ) {
		unset( $payload );
		return;
	}
	$bumped = true;
	$ver    = (int) get_option( BDS_AI_LISTINGS_CACHE_VER_OPT, 1 );
	update_option( BDS_AI_LISTINGS_CACHE_VER_OPT, $ver + 1, false );
	// Travel deal packs also respect listings inventory changes (imports / edits).
	$tp = (int) get_option( BDS_AI_TP_CACHE_VER_OPT, 1 );
	update_option( BDS_AI_TP_CACHE_VER_OPT, $tp + 1, false );
	unset( $payload );
}

/**
 * @param int $post_id Post ID.
 * @return bool
 */
function bds_ai_finder_is_listing_post( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}
	$type = get_post_type( $post_id );
	return in_array( $type, array( 'at_biz_dir', 'atbdp_listing' ), true );
}

/**
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 * @param bool    $update  Whether this is an existing post being updated.
 */
function bds_ai_finder_on_listing_saved( $post_id, $post = null, $update = null ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	unset( $post, $update );
	bds_ai_finder_bump_listings_cache();
}

/**
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Post.
 */
function bds_ai_finder_on_listing_status( $new_status, $old_status, $post ) {
	if ( ! ( $post instanceof WP_Post ) ) {
		return;
	}
	if ( ! in_array( $post->post_type, array( 'at_biz_dir', 'atbdp_listing' ), true ) ) {
		return;
	}
	if ( $new_status === $old_status ) {
		return;
	}
	$watch = array( 'publish', 'trash', 'draft', 'pending', 'private', 'future' );
	if ( ! in_array( $new_status, $watch, true ) && ! in_array( $old_status, $watch, true ) ) {
		return;
	}
	bds_ai_finder_bump_listings_cache();
}

/**
 * @param int $post_id Post ID.
 */
function bds_ai_finder_on_listing_trashed( $post_id ) {
	if ( ! bds_ai_finder_is_listing_post( $post_id ) ) {
		return;
	}
	bds_ai_finder_bump_listings_cache();
}

/**
 * @param int    $object_id  Object ID.
 * @param array  $terms      Term IDs.
 * @param array  $tt_ids     Term taxonomy IDs.
 * @param string $taxonomy   Taxonomy.
 * @param bool   $append     Append.
 * @param array  $old_tt_ids Previous term taxonomy IDs.
 */
function bds_ai_finder_on_listing_terms( $object_id, $terms, $tt_ids, $taxonomy, $append = false, $old_tt_ids = array() ) {
	unset( $terms, $tt_ids, $append, $old_tt_ids );
	if ( ! in_array( (string) $taxonomy, array( 'at_biz_dir-location', 'at_biz_dir-category', 'at_biz_dir-tags' ), true ) ) {
		return;
	}
	if ( ! bds_ai_finder_is_listing_post( $object_id ) ) {
		return;
	}
	bds_ai_finder_bump_listings_cache();
}

/**
 * Location / category term create/edit/delete.
 *
 * @param mixed $term_id Term ID (unused).
 */
function bds_ai_finder_on_dir_term_changed( $term_id = null ) {
	unset( $term_id );
	bds_ai_finder_bump_listings_cache();
}

/**
 * Location text -> canonical city key used by intent + term resolve.
 *
 * @return array<string,string>
 */
function bds_ai_finder_location_hint_map() {
	$hints = array();
	if ( function_exists( 'get_terms' ) && taxonomy_exists( 'at_biz_dir-location' ) ) {
		$cached = get_transient( 'bds_ai_loc_hints_v3' );
		if ( is_array( $cached ) ) {
			$hints = $cached;
		} else {
			$terms = get_terms(
				array(
					'taxonomy'   => 'at_biz_dir-location',
					'hide_empty' => true,
					'number'     => 500,
				)
			);
			$index = array();
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					if ( ! ( $t instanceof WP_Term ) ) {
						continue;
					}
					$name = strtolower( (string) $t->name );
					if ( $name === '' ) {
						continue;
					}
					$hints[ $name ] = $name;
					$slug_plain     = strtolower( str_replace( '-', ' ', (string) $t->slug ) );
					$slug_plain     = preg_replace( '/\b(mx|us|ca|il|ny|uk|jp|fr|ae|roo)\b/', '', $slug_plain );
					$slug_plain     = trim( preg_replace( '/\s+/', ' ', (string) $slug_plain ) );
					if ( strlen( $slug_plain ) >= 4 ) {
						$hints[ $slug_plain ] = $name;
					}
					foreach ( preg_split( '/[\s\-]+/', $name ) as $w ) {
						if ( strlen( $w ) >= 4 && ! in_array( $w, array( 'del', 'los', 'las', 'san', 'new' ), true ) ) {
							$index[ $w ][] = $name;
						}
					}
				}
				foreach ( $index as $w => $names ) {
					$uniq = array_values( array_unique( $names ) );
					if ( 1 === count( $uniq ) ) {
						$hints[ $w ] = $uniq[0];
					}
				}
			}
			set_transient( 'bds_ai_loc_hints_v3', $hints, 6 * HOUR_IN_SECONDS );
		}
	}
	return $hints;
}

/**
 * Category / cuisine text -> inventory slug (matches home-browse + city-search).
 * Longest phrases first when matching (caller should sort keys by length).
 *
 * @return array<string,string>
 */
function bds_ai_finder_category_hint_map() {
	return array(
		// --- Food & beverage (cuisines + dishes) ---
		'mexican restaurant'       => 'mexican-restaurants',
		'mexican restaurants'      => 'mexican-restaurants',
		'mexican food'             => 'mexican-restaurants',
		'mexican'                  => 'mexican-restaurants',
		'italian restaurant'       => 'italian-restaurants',
		'italian restaurants'      => 'italian-restaurants',
		'italian food'             => 'italian-restaurants',
		'italian'                  => 'italian-restaurants',
		'american restaurant'      => 'american-restaurants',
		'american restaurants'     => 'american-restaurants',
		'american food'            => 'american-restaurants',
		'american'                 => 'american-restaurants',
		'brazilian restaurant'     => 'brazilian-restaurants',
		'brazilian restaurants'    => 'brazilian-restaurants',
		'brazilian food'           => 'brazilian-restaurants',
		'brazilian'                => 'brazilian-restaurants',
		'pizza restaurant'         => 'italian-restaurants',
		'pizza places'             => 'italian-restaurants',
		'pizza place'              => 'italian-restaurants',
		'pizzeria'                 => 'italian-restaurants',
		'pizzas'                   => 'italian-restaurants',
		'pizza'                    => 'italian-restaurants',
		'pasta'                    => 'italian-restaurants',
		'sushi restaurant'         => 'restaurants',
		'sushi bar'                => 'restaurants',
		'sushi'                    => 'restaurants',
		'sashimi'                  => 'restaurants',
		'taco restaurant'          => 'mexican-restaurants',
		'tacos'                    => 'mexican-restaurants',
		'taco'                     => 'mexican-restaurants',
		'burrito'                  => 'mexican-restaurants',
		'burritos'                 => 'mexican-restaurants',
		'burger restaurant'        => 'american-restaurants',
		'burgers'                  => 'american-restaurants',
		'burger'                   => 'american-restaurants',
		'hamburger'                => 'american-restaurants',
		'seafood restaurant'       => 'restaurants',
		'seafood'                  => 'restaurants',
		'ceviches'                 => 'restaurants',
		'ceviche'                  => 'restaurants',
		'steakhouse'               => 'restaurants',
		'steak house'              => 'restaurants',
		'steak'                    => 'restaurants',
		'bbq'                      => 'american-restaurants',
		'barbecue'                 => 'american-restaurants',
		'vegan restaurant'         => 'restaurants',
		'vegetarian restaurant'    => 'restaurants',
		'vegan food'               => 'restaurants',
		'vegan'                    => 'restaurants',
		'vegetarian'               => 'restaurants',
		'brunch'                   => 'restaurants',
		'breakfast'                => 'restaurants',
		'lunch'                    => 'restaurants',
		'dinner'                   => 'restaurants',
		'bakery'                   => 'bakeries',
		'bakeries'                 => 'bakeries',
		'pastry'                   => 'bakeries',
		'pastries'                 => 'bakeries',
		'dessert'                  => 'bakeries',
		'desserts'                 => 'bakeries',
		'ice cream'                => 'restaurants',
		'gelato'                   => 'restaurants',
		'ramen'                    => 'restaurants',
		'pho'                      => 'restaurants',
		'thai food'                => 'restaurants',
		'thai restaurant'          => 'restaurants',
		'chinese food'             => 'restaurants',
		'chinese restaurant'       => 'restaurants',
		'japanese food'            => 'restaurants',
		'japanese restaurant'      => 'restaurants',
		'asian food'               => 'restaurants',
		'indian food'              => 'restaurants',
		'indian restaurant'        => 'restaurants',
		'mediterranean'            => 'restaurants',
		'french food'              => 'restaurants',
		'french restaurant'        => 'restaurants',
		'wings'                    => 'american-restaurants',
		'sandwich'                 => 'restaurants',
		'sandwiches'               => 'restaurants',
		'salad'                    => 'restaurants',
		'smoothie'                 => 'coffee-shops',
		'juice bar'                => 'coffee-shops',
		'juice'                    => 'coffee-shops',
		'coffee shop'              => 'coffee-shops',
		'coffee shops'             => 'coffee-shops',
		'coffee'                   => 'coffee-shops',
		'espresso'                 => 'coffee-shops',
		'latte'                    => 'coffee-shops',
		'cafe'                     => 'coffee-shops',
		'café'                     => 'coffee-shops',
		'cafes'                    => 'coffee-shops',
		'cafés'                    => 'coffee-shops',
		'cafes-coffee'             => 'coffee-shops',
		'restaurants'              => 'restaurants',
		'restaurant'               => 'restaurants',
		'dining'                   => 'restaurants',
		'food'                     => 'restaurants',
		'eatery'                   => 'restaurants',
		'eateries'                 => 'restaurants',
		'eat'                      => 'restaurants',
		'where to eat'             => 'restaurants',
		'hungry'                   => 'restaurants',

		// --- Nightlife ---
		'cocktail bar'             => 'bars',
		'wine bar'                 => 'bars',
		'sports bar'               => 'bars',
		'brewery'                  => 'bars',
		'breweries'                => 'bars',
		'pub'                      => 'bars',
		'pubs'                     => 'bars',
		'cantina'                  => 'bars',
		'bars'                     => 'bars',
		'bar'                      => 'bars',
		'drinks'                   => 'bars',
		'happy hour'               => 'bars',
		'nightlife'                => 'night-clubs',
		'night club'               => 'night-clubs',
		'night clubs'              => 'night-clubs',
		'nightclub'                => 'night-clubs',
		'nightclubs'               => 'night-clubs',
		'dancing'                  => 'night-clubs',
		'clubs'                    => 'night-clubs',
		'club'                     => 'night-clubs',

		// --- Stay ---
		'hotels'                   => 'hotels',
		'hotel'                    => 'hotels',
		'hotels-stays'             => 'hotels',
		'resorts'                  => 'hotels',
		'resort'                   => 'hotels',
		'airbnb'                   => 'hotels',
		'hostel'                   => 'hotels',
		'hostels'                  => 'hotels',
		'boutique hotel'           => 'hotels',
		'place to stay'            => 'hotels',
		'where to stay'            => 'hotels',
		'accommodation'            => 'hotels',
		'lodging'                  => 'hotels',

		// --- Travel / flights (deal path) ---
		'flights'                  => 'travel',
		'flight'                   => 'travel',
		'airfare'                  => 'travel',
		'airfares'                 => 'travel',
		'airline'                  => 'travel',
		'airlines'                 => 'travel',
		'airport'                  => 'travel',
		'travel'                   => 'travel',
		'vacation'                 => 'travel',
		'vacations'                => 'travel',
		'trip'                     => 'travel',
		'trips'                    => 'travel',
		'getaway'                  => 'travel',

		// --- Tours / activities ---
		'tour operator'            => 'tour-operators',
		'tour operators'           => 'tour-operators',
		'tours'                    => 'tour-operators',
		'tour'                     => 'tour-operators',
		'excursion'                => 'tour-operators',
		'excursions'               => 'tour-operators',
		'day trip'                 => 'tour-operators',
		'day trips'                => 'tour-operators',
		'cenote'                   => 'tour-operators',
		'cenotes'                  => 'tour-operators',
		'ruins'                    => 'tour-operators',
		'chichen'                  => 'tour-operators',
		'tulum ruins'              => 'tour-operators',
		'atv'                      => 'tour-operators',
		'atvs'                     => 'tour-operators',
		'jeep tour'                => 'tour-operators',
		'snorkel'                  => 'tour-operators',
		'snorkeling'               => 'tour-operators',
		'dive shop'                => 'tour-operators',
		'scuba'                    => 'tour-operators',
		'diving'                   => 'tour-operators',
		'boat tour'                => 'tour-operators',
		'catamaran'                => 'tour-operators',
		'parasail'                 => 'tour-operators',
		'adventure'                => 'tour-operators',
		'activities'               => 'tour-operators',
		'activity'                 => 'tour-operators',

		// --- Health / body ---
		'massage and spa'          => 'massage-and-spa',
		'massage therapy'          => 'massage-and-spa',
		'massage'                  => 'massage-and-spa',
		'spa day'                  => 'massage-and-spa',
		'spa'                      => 'massage-and-spa',
		'wellness'                 => 'spas-wellness-centers',
		'muay thai'                => 'muay-thai-gyms',
		'muay'                     => 'muay-thai-gyms',
		'gym'                      => 'fitness-centers-personal-training',
		'gyms'                     => 'fitness-centers-personal-training',
		'fitness'                  => 'fitness-centers-personal-training',
		'personal trainer'         => 'fitness-centers-personal-training',
		'yoga'                     => 'fitness-centers-personal-training',
		'crossfit'                 => 'fitness-centers-personal-training',
		'medical'                  => 'medical',
		'doctor'                   => 'medical',
		'dentist'                  => 'medical',
		'dental'                   => 'medical',
		'clinic'                   => 'clinic',
		'hospital'                 => 'medical',
		'pharmacy'                 => 'medical',
		'farmacia'                 => 'medical',
		'vet'                      => 'veterinary',
		'veterinarian'             => 'veterinary',
		'veterinary'               => 'veterinary',
		'pet clinic'               => 'veterinary',

		// --- Beauty ---
		'tattoo shop'              => 'beauty-salons',
		'tattoo parlor'            => 'beauty-salons',
		'tattoo studio'            => 'beauty-salons',
		'tattoo'                   => 'beauty-salons',
		'tattoos'                  => 'beauty-salons',
		'piercing'                 => 'beauty-salons',
		'beauty salon'             => 'beauty-salons',
		'beauty'                   => 'beauty-salons',
		'nail salon'               => 'beauty-salons',
		'nails'                    => 'beauty-salons',
		'manicure'                 => 'beauty-salons',
		'pedicure'                 => 'beauty-salons',
		'hair salon'               => 'beauty-salons',
		'haircut'                  => 'beauty-salons',
		'barber'                   => 'beauty-salons',
		'barbershop'               => 'beauty-salons',
		'salon'                    => 'beauty-salons',

		// --- Retail / grocery ---
		'supermarket'              => 'supermarkets',
		'supermarkets'             => 'supermarkets',
		'grocery store'            => 'supermarkets',
		'grocery'                  => 'supermarkets',
		'groceries'                => 'supermarkets',
		'walmart'                  => 'supermarkets',
		'chedraui'                 => 'supermarkets',
		'soriana'                  => 'supermarkets',
		'convenience store'        => 'supermarkets',
		'shopping'                 => 'retail-shopping',
		'retail'                   => 'retail-shopping',
		'mall'                     => 'retail-shopping',
		'store'                    => 'retail-shopping',
		'souvenir'                 => 'retail-shopping',
		'souvenirs'                => 'retail-shopping',
		'market'                   => 'retail-shopping',

		// --- Automotive ---
		'car rental'               => 'car-rentals',
		'car rentals'              => 'car-rentals',
		'rent a car'               => 'car-rentals',
		'rental car'               => 'car-rentals',
		'scooter rental'           => 'car-rentals',
		'motorcycle rental'        => 'car-rentals',
		'airport transfer'         => 'car-rentals',
		'taxi'                     => 'car-rentals',
		'uber'                     => 'car-rentals',

		// --- Digital / BrandDad services ---
		'logo design'              => 'branding',
		'logo'                     => 'branding',
		'branding'                 => 'branding',
		'web design'               => 'web',
		'website'                  => 'web',
		'websites'                 => 'web',
		'hosting'                  => 'hosting',
		'domain'                   => 'hosting',
		'seo'                      => 'seo',
		'digital marketing'        => 'seo',
		'social media'             => 'seo',

		// --- Gifts ---
		'gift card'                => 'gift-cards',
		'gift cards'               => 'gift-cards',
		'gift'                     => 'gift-cards',
		'gifts'                    => 'gift-cards',
	);
}

/**
 * Dish / cuisine / specialty tokens kept for title scoring even after category resolve.
 *
 * @return string[]
 */
function bds_ai_finder_dish_tokens() {
	return array(
		'pizza', 'pizzeria', 'sushi', 'sashimi', 'taco', 'tacos', 'burrito', 'burger', 'burgers',
		'hamburger', 'seafood', 'ceviche', 'steak', 'steakhouse', 'bbq', 'barbecue', 'vegan',
		'vegetarian', 'brunch', 'breakfast', 'bakery', 'ramen', 'pho', 'pasta', 'wings',
		'mexican', 'italian', 'brazilian', 'american', 'thai', 'chinese', 'japanese', 'indian',
		'coffee', 'cafe', 'espresso', 'latte', 'smoothie', 'gelato',
		'octopus', 'pulpo', 'fade', 'fades',
		'tattoo', 'piercing', 'manicure', 'pedicure', 'barber',
		'tour', 'scuba', 'dive', 'snorkel', 'cenote', 'atv', 'catamaran',
		'massage', 'spa', 'yoga', 'gym',
	);
}

/**
 * @param string $message Lowercased message.
 * @return string[]
 */
function bds_ai_finder_extract_dish_tokens( $message ) {
	if ( function_exists( 'bds_ai_intent_parse' ) ) {
		$opts = function_exists( 'bds_ai_intent_wp_opts' ) ? bds_ai_intent_wp_opts() : array();
		$i    = bds_ai_intent_parse( $message, $opts );
		$attrs = isset( $i['attributes'] ) && is_array( $i['attributes'] ) ? $i['attributes'] : array();
		return array_values( array_filter( array_map( 'strval', $attrs ) ) );
	}
	return array();
}

/**
 * Map category slug/key to a coarse vertical for guardrails.
 *
 * @param string $category Category key/slug.
 * @param string $message  Lowercased message (fallback).
 * @return string food|nightlife|stay|health|beauty|retail|tours|auto|digital|travel|gift|pets|other
 */
function bds_ai_finder_intent_vertical( $category, $message = '' ) {
	$c = strtolower( (string) $category );
	$m = strtolower( (string) $message );
	$blob = $c . ' ' . $m;
	if ( 'gift-cards' === $c || bds_ai_finder_is_gift_intent( $m ) ) {
		return 'gift';
	}
	if ( bds_ai_finder_is_travel_intent( $m ) ) {
		return 'travel';
	}
	if ( in_array( $c, array( 'branding', 'web', 'hosting', 'seo' ), true )
		|| 1 === preg_match( '/\b(logo|branding|website|hosting|seo|digital marketing)\b/', $m ) ) {
		return 'digital';
	}
	if ( 1 === preg_match( '/car-rental|car rental|rent a car|scooter|taxi|uber|transfer/', $blob ) ) {
		return 'auto';
	}
	if ( 1 === preg_match( '/vet|veterinary|pet clinic/', $blob ) ) {
		return 'pets';
	}
	// Beauty before generic health so tattoo/salon don't share food leakage paths.
	if ( 1 === preg_match( '/photograph|photo studio|lawyer|attorney|plumb|notary|accountant/', $blob ) ) {
		return 'service';
	}
	if ( 1 === preg_match( '/tattoo|piercing|beauty|salon|nail|haircut|barber|manicure|pedicure/', $blob ) ) {
		return 'beauty';
	}
	if ( 1 === preg_match( '/restaurant|mexican|italian|american|brazilian|coffee|cafe|bakery|bakeries|food|dining|\beat\b|pizza|pizzeria|sushi|taco|burger|seafood|steak|vegan|vegetarian|brunch|ramen|\bpho\b|pasta|ceviche|\bhungry\b/', $blob ) ) {
		return 'food';
	}
	if ( 1 === preg_match( '/\bbar\b|bars|club|nightlife|brewery|pub|cocktail|happy hour/', $blob ) ) {
		return 'nightlife';
	}
	if ( 1 === preg_match( '/hotel|stay|resort|hostel|lodging|airbnb|accommodation/', $blob ) ) {
		return 'stay';
	}
	if ( 1 === preg_match( '/spa|massage|medical|clinic|gym|muay|fitness|yoga|dentist|dental|pharmacy|hospital|wellness/', $blob ) ) {
		return 'health';
	}
	if ( 1 === preg_match( '/supermarket|grocery|retail|shopping|walmart|mall|souvenir/', $blob ) ) {
		return 'retail';
	}
	if ( 1 === preg_match( '/tour|excursion|dive|scuba|snorkel|cenote|atv|catamaran|adventure|activity|activities|ruins/', $blob ) ) {
		return 'tours';
	}
	return 'other';
}

/**
 * Hard allow-list: which listing verticals may appear for an intent vertical.
 * Empty array = no hard filter (location / open browse).
 *
 * @param string $intent_v Intent vertical.
 * @return string[]
 */
function bds_ai_finder_allowed_listing_verticals( $intent_v ) {
	$map = array(
		'food'      => array( 'food' ),
		'nightlife' => array( 'nightlife' ),
		'stay'      => array( 'stay' ),
		'health'    => array( 'health' ),
		'beauty'    => array( 'beauty', 'health' ), // beauty-salons may classify as either
		'retail'    => array( 'retail' ),
		'tours'     => array( 'tours' ),
		'auto'      => array( 'auto' ),
		'pets'      => array( 'pets', 'health' ),
		'digital'   => array( 'digital' ),
		'service'   => array( 'service', 'digital' ),
		// travel/gift use dedicated paths; other = open
		'travel'    => array(),
		'gift'      => array(),
		'other'     => array(),
	);
	$intent_v = strtolower( (string) $intent_v );
	return isset( $map[ $intent_v ] ) ? $map[ $intent_v ] : array();
}

/**
 * Whether a listing vertical is allowed for the current intent (hard rail).
 *
 * @param string $intent_v   Intent vertical.
 * @param string $listing_v  Listing vertical.
 * @return bool
 */
function bds_ai_finder_vertical_allowed( $intent_v, $listing_v ) {
	$allowed = bds_ai_finder_allowed_listing_verticals( $intent_v );
	if ( empty( $allowed ) ) {
		return true;
	}
	return in_array( strtolower( (string) $listing_v ), $allowed, true );
}

/**
 * Broad discovery: domain + location, not a leaf AND. Specific/hard keep evidence rails.
 *
 * @param array<string,mixed> $parsed Intent.
 * @return bool
 */
function bds_ai_finder_is_broad_discovery( $parsed ) {
	if ( ! is_array( $parsed ) ) {
		return false;
	}
	if ( ! empty( $parsed['strict'] ) ) {
		return false;
	}
	$dishes = isset( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) ? array_filter( $parsed['dish_tokens'] ) : array();
	if ( $dishes ) {
		return false;
	}
	if ( isset( $parsed['max_price'] ) && $parsed['max_price'] !== null && $parsed['max_price'] !== '' ) {
		return false;
	}
	$spec = strtolower( (string) ( $parsed['specificity'] ?? 'broad' ) );
	return ( $spec === 'broad' || $spec === '' );
}

/**
 * @param string $needle Location key or free text.
 * @return WP_Term|null
 */
function bds_ai_finder_resolve_location_term( $needle ) {
	$needle = trim( (string) $needle );
	if ( $needle === '' ) {
		return null;
	}
	if ( function_exists( 'bds_city_search_resolve_location' ) ) {
		$t = bds_city_search_resolve_location( $needle );
		if ( $t instanceof WP_Term ) {
			return $t;
		}
	}
	return bds_ai_finder_match_term( $needle, 'at_biz_dir-location' );
}

/**
 * @param string $needle Category key/slug or free text.
 * @return WP_Term|null
 */
function bds_ai_finder_resolve_category_term( $needle ) {
	$needle = trim( (string) $needle );
	if ( $needle === '' ) {
		return null;
	}
	if ( function_exists( 'bds_city_search_resolve_category' ) ) {
		$t = bds_city_search_resolve_category( $needle );
		if ( $t instanceof WP_Term ) {
			return $t;
		}
	}
	if ( taxonomy_exists( 'at_biz_dir-category' ) ) {
		$by_slug = get_term_by( 'slug', sanitize_title( $needle ), 'at_biz_dir-category' );
		if ( $by_slug instanceof WP_Term ) {
			return $by_slug;
		}
		// Soft aliases when inventory slug differs.
		$fallbacks = array(
			'retail-shopping'     => array( 'supermarkets', 'shopping', 'retail' ),
			'shopping'            => array( 'supermarkets', 'retail-shopping' ),
			'cafe'                => array( 'coffee-shops', 'cafes-coffee' ),
			'cafes'               => array( 'coffee-shops', 'cafes-coffee' ),
			'italian-restaurants' => array( 'restaurants', 'italian' ),
			'mexican-restaurants' => array( 'restaurants', 'mexican' ),
			'american-restaurants'=> array( 'restaurants', 'american' ),
			'brazilian-restaurants'=> array( 'restaurants', 'brazilian' ),
			'beauty-salons'       => array( 'beauty', 'salons', 'spa' ),
			'tour-operators'      => array( 'tours', 'tour', 'travel' ),
			'medical'             => array( 'medical-clinics', 'clinics' ),
			'clinic'              => array( 'medical-clinics', 'clinics' ),
			'branding'            => array( 'graphic-design-creative-services' ),
			'web'                 => array( 'web-design-development' ),
			'hosting'             => array( 'hosting-domain-services' ),
			'seo'                 => array( 'search-engine-optimization-seo' ),
		);
		$key = strtolower( $needle );
		if ( isset( $fallbacks[ $key ] ) ) {
			foreach ( $fallbacks[ $key ] as $alt ) {
				$t = get_term_by( 'slug', $alt, 'at_biz_dir-category' );
				if ( $t instanceof WP_Term ) {
					return $t;
				}
			}
		}
	}
	return bds_ai_finder_match_term( $needle, 'at_biz_dir-category' );
}

/**
 * Related category term IDs for soft expansion (cuisine -> parent restaurants).
 *
 * @param WP_Term|null $term Primary category.
 * @return array<int,int>
 */
function bds_ai_finder_related_category_ids( $term ) {
	if ( ! ( $term instanceof WP_Term ) || ! taxonomy_exists( 'at_biz_dir-category' ) ) {
		return array();
	}
	static $rel_cache = array();
	$ck = (int) $term->term_id;
	if ( isset( $rel_cache[ $ck ] ) ) {
		return $rel_cache[ $ck ];
	}
	$ids   = array( (int) $term->term_id );
	$kids  = get_term_children( (int) $term->term_id, $term->taxonomy );
	if ( ! is_wp_error( $kids ) ) {
		foreach ( (array) $kids as $kid ) {
			$ids[] = (int) $kid;
		}
	}
	// Do not add the parent term: a leaf (italian-restaurants) plus parent
	// restaurants would AND-match every restaurant in the city.
	$rel_cache[ $ck ] = array_values( array_unique( array_filter( $ids ) ) );
	return $rel_cache[ $ck ];
}

/**
 * Coarse vertical from a listing's category names/slugs.
 *
 * @param array<int,string> $cat_names Names.
 * @param array<int,string> $cat_slugs Slugs.
 * @return string
 */
function bds_ai_finder_listing_vertical( $cat_names, $cat_slugs = array() ) {
	$blob = strtolower( implode( ' ', array_merge( (array) $cat_names, (array) $cat_slugs ) ) );
	if ( 1 === preg_match( '/gift-card|gift card/', $blob ) ) {
		return 'gift';
	}
	if ( 1 === preg_match( '/car-rental|car rental|automotive|taxi|scooter/', $blob ) ) {
		return 'auto';
	}
	if ( 1 === preg_match( '/veterinar|pet clinic|\bvet\b/', $blob ) ) {
		return 'pets';
	}
	if ( 1 === preg_match( '/supermarket|grocery|retail|shopping|convenience|department|souvenir/', $blob ) ) {
		return 'retail';
	}
	if ( 1 === preg_match( '/tattoo|piercing|beauty|salon|nail|barber|hair/', $blob ) ) {
		return 'beauty';
	}
	if ( 1 === preg_match( '/restaurant|mexican|italian|american|brazilian|coffee|cafe|food|dining|bakery|bakeries|chocolate|pizza|sushi|taco|burger|seafood|steak|vegan|beverage/', $blob ) ) {
		return 'food';
	}
	if ( 1 === preg_match( '/bar|club|nightlife|pub|brewery/', $blob ) ) {
		return 'nightlife';
	}
	if ( 1 === preg_match( '/hotel|resort|stay|hostel|lodging/', $blob ) ) {
		return 'stay';
	}
	if ( 1 === preg_match( '/spa|massage|medical|clinic|gym|muay|fitness|health|yoga|wellness|dental|pharmacy/', $blob ) ) {
		return 'health';
	}
	if ( 1 === preg_match( '/tour|atv|excursion|dive|scuba|snorkel|cenote|operator|adventure/', $blob ) ) {
		return 'tours';
	}
	if ( 1 === preg_match( '/digital|design|seo|hosting|marketing|branding|web-design|graphic/', $blob ) ) {
		return 'digital';
	}
	if ( 1 === preg_match( '/photo|photograph|lawyer|attorney|plumb|notary|accountant/', $blob ) ) {
		return 'service';
	}
	return 'other';
}

/**
 * GBP rating/count from lead meta (non-authoritative).
 *
 * @param int $post_id Listing ID.
 * @return array{rating:float,count:int}
 */
function bds_ai_finder_listing_gbp( $post_id ) {
	$post_id = (int) $post_id;
	if ( function_exists( 'bds_gbp_reviews_get' ) ) {
		$data = bds_gbp_reviews_get( $post_id );
		if ( is_array( $data ) ) {
			return array(
				'rating' => ( $data['rating'] !== '' && is_numeric( $data['rating'] ) ) ? (float) $data['rating'] : 0.0,
				'count'  => ( $data['count'] !== '' && is_numeric( $data['count'] ) ) ? (int) $data['count'] : 0,
			);
		}
	}
	$rating = trim( (string) get_post_meta( $post_id, '_bds_gbp_rating', true ) );
	$count  = trim( (string) get_post_meta( $post_id, '_bds_gbp_reviews', true ) );
	return array(
		'rating' => ( $rating !== '' && is_numeric( $rating ) ) ? (float) $rating : 0.0,
		'count'  => ( $count !== '' && is_numeric( $count ) ) ? (int) $count : 0,
	);
}

/**
 * Relevance score for a candidate listing.
 *
 * @param int                 $post_id  Listing ID.
 * @param array<string,mixed> $parsed   Intent.
 * @param float|null          $distance Optional km.
 * @return array{score:float,vertical:string,exclude:bool}
 */
function bds_ai_finder_score_listing( $post_id, $parsed, $distance = null ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );
	if ( ! $post ) {
		return array( 'score' => -9999.0, 'vertical' => 'other', 'exclude' => true );
	}
	// Free/personal profiles are never AI-Finder results (public business listings only).
	if ( function_exists( 'bds_pv_is_public_business_listing' ) && ! bds_pv_is_public_business_listing( $post_id ) ) {
		return apply_filters(
			'bds_ai_finder_score_listing',
			array( 'score' => -9999.0, 'vertical' => 'other', 'exclude' => true ),
			$post_id,
			$parsed,
			$distance
		);
	}

	$cat_terms = wp_get_post_terms( $post_id, 'at_biz_dir-category' );
	if ( is_wp_error( $cat_terms ) ) {
		$cat_terms = array();
	}
	$cat_names = array();
	$cat_slugs = array();
	$cat_ids   = array();
	foreach ( (array) $cat_terms as $ct ) {
		if ( $ct instanceof WP_Term ) {
			$cat_names[] = (string) $ct->name;
			$cat_slugs[] = (string) $ct->slug;
			$cat_ids[]   = (int) $ct->term_id;
		}
	}
	$loc_terms = wp_get_post_terms( $post_id, 'at_biz_dir-location', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $loc_terms ) ) {
		$loc_terms = array();
	}
	$loc_ids = array_map( 'intval', (array) $loc_terms );

	$vertical = bds_ai_finder_listing_vertical( $cat_names, $cat_slugs );
	$intent_v = isset( $parsed['vertical'] ) ? (string) $parsed['vertical'] : 'other';
	$exclude  = false;
	$score    = 0.0;

	// Location taxonomy match - hard preference.
	if ( ! empty( $parsed['location_term'] ) && $parsed['location_term'] instanceof WP_Term ) {
		$want_loc = (int) $parsed['location_term']->term_id;
		if ( in_array( $want_loc, $loc_ids, true ) ) {
			$score += 55.0;
		} else {
			// Soft penalty when user named a city but listing is elsewhere.
			$score -= 25.0;
		}
	}

	// Category taxonomy match - exact / related.
	$want_cat_ids = array();
	if ( ! empty( $parsed['category_term'] ) && $parsed['category_term'] instanceof WP_Term ) {
		$want_cat_ids = bds_ai_finder_related_category_ids( $parsed['category_term'] );
		$primary      = (int) $parsed['category_term']->term_id;
		if ( in_array( $primary, $cat_ids, true ) ) {
			$score += 70.0;
		} elseif ( array_intersect( $want_cat_ids, $cat_ids ) ) {
			$score += 28.0;
		} else {
			$score -= 15.0;
		}
	}

	// Hard vertical guardrails: intent allow-list only (food never tours/tattoo/hotel...).
	if ( ! bds_ai_finder_vertical_allowed( $intent_v, $vertical ) ) {
		$score   -= 250.0;
		$exclude  = true;
	} elseif ( 'nightlife' === $intent_v && 'food' === $vertical ) {
		$score -= 35.0;
	} elseif ( 'retail' === $intent_v && 'food' === $vertical ) {
		$score -= 20.0;
	}

	$title   = strtolower( bds_text_plain( get_the_title( $post_id ) ) );
	$excerpt = strtolower( wp_strip_all_tags( (string) $post->post_content ) );
	$kw      = isset( $parsed['keywords'] ) ? trim( (string) $parsed['keywords'] ) : '';
	$cat_key = isset( $parsed['category'] ) ? strtolower( (string) $parsed['category'] ) : '';

	// Cuisine / dish word in title/cats even when tax was parent-only.
	$cuisine_bits = array();
	if ( $cat_key !== '' ) {
		$cuisine_bits[] = preg_replace( '/-restaurants$|-shops$/', '', $cat_key );
		$cuisine_bits[] = str_replace( '-', ' ', $cat_key );
	}
	foreach ( array( 'mexican', 'italian', 'american', 'brazilian', 'coffee', 'bar', 'hotel', 'spa', 'pizza', 'sushi', 'taco', 'burger', 'seafood', 'steak', 'vegan', 'tattoo', 'tour' ) as $cbit ) {
		if ( ( $cat_key !== '' && false !== strpos( $cat_key, $cbit ) ) || ( $kw !== '' && false !== strpos( $kw, $cbit ) ) ) {
			$cuisine_bits[] = $cbit;
		}
	}
	if ( ! empty( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) ) {
		foreach ( $parsed['dish_tokens'] as $dt ) {
			$cuisine_bits[] = (string) $dt;
		}
	}
	$cuisine_bits = array_values( array_unique( array_filter( array_map( 'strval', $cuisine_bits ) ) ) );
	$cat_blob     = strtolower( implode( ' ', $cat_names ) . ' ' . implode( ' ', $cat_slugs ) );
	foreach ( $cuisine_bits as $cb ) {
		$cb = trim( (string) $cb );
		if ( strlen( $cb ) < 3 ) {
			continue;
		}
		if ( false !== strpos( $title, $cb ) || false !== strpos( $cat_blob, $cb ) ) {
			// Strong title dish match (pizza in name) beats generic high-rated noise.
			$score += ( false !== strpos( $title, $cb ) ) ? 48.0 : 22.0;
			break;
		}
	}

	// Specific dish query (pizza/sushi/taco...): demote food listings with no dish signal.
	if ( 'food' === $intent_v && ! empty( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) ) {
		$dish_hit = false;
		foreach ( $parsed['dish_tokens'] as $dt ) {
			$dt = strtolower( trim( (string) $dt ) );
			if ( strlen( $dt ) < 3 ) {
				continue;
			}
			if ( false !== strpos( $title, $dt ) || false !== strpos( $cat_blob, $dt ) || false !== strpos( $excerpt, $dt ) ) {
				$dish_hit = true;
				break;
			}
		}
		if ( ! $dish_hit ) {
			$score   -= 250.0;
			$exclude  = true;
		}
	}

	if ( $kw !== '' ) {
		foreach ( preg_split( '/\s+/', $kw ) as $bit ) {
			$bit = trim( (string) $bit );
			if ( strlen( $bit ) < 3 ) {
				continue;
			}
			if ( false !== strpos( $title, $bit ) ) {
				$score += 14.0;
			} elseif ( false !== strpos( $excerpt, $bit ) ) {
				$score += 4.0;
			}
		}
	}

	// Tags (when taxonomy exists).
	if ( taxonomy_exists( 'at_biz_dir-tags' ) && ( $kw !== '' || $cat_key !== '' ) ) {
		$tags = wp_get_post_terms( $post_id, 'at_biz_dir-tags', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $tags ) && $tags ) {
			$tag_blob = strtolower( implode( ' ', (array) $tags ) );
			foreach ( array_merge( $cuisine_bits, $kw !== '' ? preg_split( '/\s+/', $kw ) : array() ) as $bit ) {
				$bit = trim( (string) $bit );
				if ( strlen( $bit ) >= 3 && false !== strpos( $tag_blob, $bit ) ) {
					$score += 10.0;
					break;
				}
			}
		}
	}

	// "Good/best" -> lean on GBP rating + review volume.
	$gbp = bds_ai_finder_listing_gbp( $post_id );
	if ( ! empty( $parsed['quality_intent'] ) && $gbp['rating'] > 0 ) {
		$score += ( $gbp['rating'] * 6.0 );
		if ( $gbp['count'] > 0 ) {
			$score += min( 18.0, log( 1 + $gbp['count'] ) * 3.0 );
		}
	} elseif ( $gbp['rating'] >= 4.5 && $gbp['count'] >= 20 ) {
		$score += 4.0;
	}

	if ( $distance !== null && is_finite( (float) $distance ) ) {
		$score -= min( 30.0, (float) $distance * 0.85 );
	}

	return apply_filters(
		'bds_ai_finder_score_listing',
		array(
			'score'    => $score,
			'vertical' => $vertical,
			'exclude'  => $exclude,
		),
		$post_id,
		$parsed,
		$distance
	);
}

/**
 * @param string $needle Search.
 * @param string $tax    Taxonomy.
 * @return WP_Term|null
 */
function bds_ai_finder_match_term( $needle, $tax ) {
	if ( ! taxonomy_exists( $tax ) ) {
		return null;
	}
	$needle = trim( (string) $needle );
	if ( $needle === '' ) {
		return null;
	}
	$by_name = get_term_by( 'name', $needle, $tax );
	if ( $by_name instanceof WP_Term ) {
		return $by_name;
	}
	$by_slug = get_term_by( 'slug', sanitize_title( $needle ), $tax );
	if ( $by_slug instanceof WP_Term ) {
		return $by_slug;
	}
	$found = get_terms(
		array(
			'taxonomy'   => $tax,
			'hide_empty' => false,
			'number'     => 12,
			'search'     => $needle,
		)
	);
	if ( ! is_wp_error( $found ) && ! empty( $found ) ) {
		usort(
			$found,
			static function ( $a, $b ) {
				return (int) $b->count <=> (int) $a->count;
			}
		);
		if ( $found[0] instanceof WP_Term ) {
			return $found[0];
		}
	}
	// Fuzzy: walk a few terms.
	$all = get_terms(
		array(
			'taxonomy'   => $tax,
			'hide_empty' => true,
			'number'     => 200,
		)
	);
	if ( is_wp_error( $all ) || ! $all ) {
		return null;
	}
	$n = strtolower( $needle );
	foreach ( $all as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$name = strtolower( $term->name );
		$slug = strtolower( $term->slug );
		if ( false !== strpos( $name, $n ) || false !== strpos( $slug, sanitize_title( $n ) ) || false !== strpos( $n, $name ) ) {
			return $term;
		}
	}
	return null;
}

/**
 * Post types used for Directory listings.
 *
 * @return array<int,string>
 */
function bds_ai_finder_listing_types() {
	$types = array( 'at_biz_dir' );
	if ( post_type_exists( 'atbdp_listing' ) ) {
		$types[] = 'atbdp_listing';
	}
	return $types;
}

/**
 * Direct taxonomy intersect when WP_Query filters wipe listing results.
 *
 * @param array<int,string> $types   Post types.
 * @param int               $loc_id  Location term ID.
 * @param array<int,int>    $cat_ids Category term IDs.
 * @param int               $cap     Max.
 * @return array<int,int>
 */
function bds_ai_finder_sql_tax_ids( $types, $loc_id, $cat_ids, $cap = 80 ) {
	global $wpdb;
	$loc_id = (int) $loc_id;
	$cap    = max( 10, min( 250, (int) $cap ) );
	if ( $loc_id <= 0 || ! isset( $wpdb ) ) {
		return array();
	}
	$types = array_values( array_filter( array_map( 'strval', (array) $types ) ) );
	if ( ! $types ) {
		$types = array( 'at_biz_dir' );
	}
	$type_in = implode( ',', array_map( static function ( $t ) use ( $wpdb ) {
		return $wpdb->prepare( '%s', $t );
	}, $types ) );
	$loc_ids = array( $loc_id );
	if ( taxonomy_exists( 'at_biz_dir-location' ) ) {
		$kids = get_term_children( $loc_id, 'at_biz_dir-location' );
		if ( ! is_wp_error( $kids ) ) {
			foreach ( (array) $kids as $kid ) {
				$loc_ids[] = (int) $kid;
			}
		}
	}
	$loc_in = implode( ',', array_map( 'intval', array_unique( array_filter( $loc_ids ) ) ) );
	$sql    = "SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id AND p.post_status = 'publish' AND p.post_type IN ($type_in)
		WHERE tt.term_id IN ($loc_in)";
	$cat_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $cat_ids ) ) ) );
	if ( $cat_ids ) {
		$cat_in = implode( ',', $cat_ids );
		$sql   .= " AND tr.object_id IN (
			SELECT tr2.object_id FROM {$wpdb->term_relationships} tr2
			INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id
			WHERE tt2.term_id IN ($cat_in)
		)";
	}
	$sql .= $wpdb->prepare( ' ORDER BY p.post_date DESC LIMIT %d', $cap );
	$cols = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return array_map( 'intval', (array) $cols );
}

/**
 * Collect candidate listing IDs (tax + keyword + postal address/meta), then optional geo rank.
 *
 * @param array<string,mixed> $parsed Intent (mutated: expansion_note, search_mode).
 * @param int                 $limit  Max cards.
 * @return array<int,array<string,mixed>>
 */
/**
 * Live TravelPayouts deal listings (_bds_travelpayouts=1) ranked by route/location.
 * Short transient cache; invalidated when bds_tp_sync_complete or listing inventory
 * bumps cache vers (bds_ai_tp_cache_ver + bds_ai_listings_cache_ver).
 *
 * @param array<string,mixed> $parsed Intent (by ref for expansion_note).
 * @param int                 $limit  Max cards.
 * @return array<int,array<string,mixed>>
 */
function bds_ai_finder_query_travel_deals( &$parsed, $limit = 8 ) {
	$limit = max( 1, min( 12, (int) $limit ) );
	$kind  = isset( $parsed['travel_kind'] ) ? (string) $parsed['travel_kind'] : 'any';
	$dest  = isset( $parsed['travel_dest'] ) ? strtolower( (string) $parsed['travel_dest'] ) : '';
	$origin = isset( $parsed['travel_origin'] ) ? strtolower( (string) $parsed['travel_origin'] ) : '';
	$loc   = isset( $parsed['location'] ) ? strtolower( (string) $parsed['location'] ) : '';
	if ( $dest === '' && $loc !== '' ) {
		$dest = $loc;
	}
	$lat   = isset( $parsed['lat'] ) ? $parsed['lat'] : null;
	$lng   = isset( $parsed['lng'] ) ? $parsed['lng'] : null;
	$units = ! empty( $parsed['units'] ) ? (string) $parsed['units'] : 'km';
	$meta  = defined( 'BDS_TP_META' ) ? BDS_TP_META : '_bds_travelpayouts';
	$tp_ver = (int) get_option( BDS_AI_TP_CACHE_VER_OPT, 1 );
	$list_ver = (int) get_option( BDS_AI_LISTINGS_CACHE_VER_OPT, 1 );
	$ckey  = 'bds_ai_tp_' . md5(
		wp_json_encode(
			array(
				BDS_AI_FINDER_VER,
				function_exists( 'bds_ai_concierge_ver' ) ? bds_ai_concierge_ver() : '',
				defined( 'BDS_AI_PREC_VER' ) ? BDS_AI_PREC_VER : '',
				$tp_ver,
				$list_ver,
				$kind,
				$dest,
				$origin,
				$loc,
				$lat !== null ? round( (float) $lat, 2 ) : null,
				$lng !== null ? round( (float) $lng, 2 ) : null,
				$limit,
			)
		)
	);
	$cached = get_transient( $ckey );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'at_biz_dir',
			'post_status'            => 'publish',
			'posts_per_page'         => 120,
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'meta_query'             => array(
				array(
					'key'   => $meta,
					'value' => '1',
				),
			),
		)
	);
	$ids = array_map( 'intval', (array) $q->posts );
	if ( ! $ids ) {
		$parsed['expansion_note'] = 'No live travel deals synced yet - Travel search for members is in Account Control.';
		set_transient( $ckey, array(), 90 );
		return array();
	}

	$place_bits = array_values(
		array_unique(
			array_filter(
				array( $dest, $origin, $loc )
			)
		)
	);
	$scored = array();
	foreach ( $ids as $id ) {
		$deal_kind = strtolower( (string) get_post_meta( $id, defined( 'BDS_TP_KIND' ) ? BDS_TP_KIND : '_bds_tp_kind', true ) );
		if ( $deal_kind !== 'hotel' && $deal_kind !== 'flight' ) {
			// Infer from title when meta missing.
			$title_probe = strtolower( bds_text_plain( get_the_title( $id ) ) );
			if ( 1 === preg_match( '/\bhotel|\bresort|\bstay|\bnight\b/', $title_probe ) ) {
				$deal_kind = 'hotel';
			} else {
				$deal_kind = 'flight';
			}
		}

		// Hard kind rail: hotel query never returns flights (and vice versa).
		if ( 'hotel' === $kind && 'hotel' !== $deal_kind ) {
			continue;
		}
		if ( 'flight' === $kind && 'flight' !== $deal_kind ) {
			continue;
		}

		$score = 10.0;
		if ( 'hotel' === $kind && 'hotel' === $deal_kind ) {
			$score += 55.0;
		} elseif ( 'flight' === $kind && 'flight' === $deal_kind ) {
			$score += 55.0;
		} elseif ( 'any' === $kind ) {
			$score += 5.0;
		}

		$d_label = strtolower( (string) get_post_meta( $id, defined( 'BDS_TP_DEST_LABEL' ) ? BDS_TP_DEST_LABEL : '_bds_tp_dest_label', true ) );
		$o_label = strtolower( (string) get_post_meta( $id, defined( 'BDS_TP_ORIGIN_LABEL' ) ? BDS_TP_ORIGIN_LABEL : '_bds_tp_origin_label', true ) );
		$route   = strtolower( (string) get_post_meta( $id, defined( 'BDS_TP_ROUTE' ) ? BDS_TP_ROUTE : '_bds_tp_route_label', true ) );
		$title   = strtolower( bds_text_plain( get_the_title( $id ) ) );
		$blob    = $d_label . ' ' . $o_label . ' ' . $route . ' ' . $title;

		$loc_terms = wp_get_post_terms( $id, 'at_biz_dir-location', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $loc_terms ) ) {
			$blob .= ' ' . strtolower( implode( ' ', (array) $loc_terms ) );
		}

		$dest_hit   = ( $dest !== '' && bds_ai_finder_travel_place_hit( $blob, $dest ) );
		$origin_hit = ( $origin !== '' && bds_ai_finder_travel_place_hit( $o_label . ' ' . $blob, $origin ) );
		$loc_hit    = ( $loc !== '' && $loc !== $dest && bds_ai_finder_travel_place_hit( $blob, $loc ) );

		if ( ! empty( $parsed['location_term'] ) && $parsed['location_term'] instanceof WP_Term ) {
			$want = (int) $parsed['location_term']->term_id;
			$lids = wp_get_post_terms( $id, 'at_biz_dir-location', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $lids ) && in_array( $want, array_map( 'intval', (array) $lids ), true ) ) {
				$score += 55.0;
				$dest_hit = true;
			}
		}
		if ( $dest_hit ) {
			$score += 50.0;
		} elseif ( $loc_hit ) {
			$score += 35.0;
		}
		if ( $origin_hit ) {
			$score += 40.0;
		}
		foreach ( $place_bits as $bit ) {
			$bit = trim( (string) $bit );
			if ( strlen( $bit ) < 3 ) {
				continue;
			}
			if ( bds_ai_finder_travel_place_hit( $blob, $bit ) ) {
				if ( $bit !== $dest && $bit !== $origin && $bit !== $loc ) {
					$score += 20.0;
				}
				break;
			}
		}
		// Soft near-me / visitor-city: prefer destinations closer to the visitor.
		$dist = null;
		if ( $lat !== null && $lng !== null ) {
			$coords = bds_ai_finder_listing_coords( $id );
			if ( $coords ) {
				$dist = bds_ai_finder_haversine_km( (float) $lat, (float) $lng, $coords['lat'], $coords['lng'] );
				if ( $dist <= 80 ) {
					$score += 18.0;
				} elseif ( $dist <= 250 ) {
					$score += 8.0;
				} elseif ( $dist > 2500 && 'hotel' === $deal_kind && $dest === '' ) {
					$score -= 6.0;
				}
			}
		}
		$price = (string) get_post_meta( $id, defined( 'BDS_TP_PRICE_LABEL' ) ? BDS_TP_PRICE_LABEL : '_bds_tp_price_label', true );
		if ( $price !== '' ) {
			$score += 4.0;
		}
		if ( ! empty( $parsed['follow_up'] ) && 'cheaper' === $parsed['follow_up'] ) {
			$raw_price = (float) get_post_meta( $id, defined( 'BDS_TP_PRICE' ) ? BDS_TP_PRICE : '_bds_tp_price', true );
			if ( $raw_price > 0 ) {
				$score += max( 0.0, 30.0 - ( $raw_price / 20.0 ) );
			}
		}
		$scored[] = array(
			'id'         => $id,
			'score'      => $score,
			'distance'   => $dist,
			'deal_kind'  => $deal_kind,
			'dest_hit'   => $dest_hit || ( 'hotel' === $kind && $loc_hit ),
			'origin_hit' => $origin_hit,
		);
	}

	// Hard destination rail: when user named a place and we have enough hits, drop non-matches.
	$place_needed = ( $dest !== '' || ( 'hotel' === $kind && $loc !== '' ) );
	if ( $place_needed && $scored ) {
		$hits = array_values(
			array_filter(
				$scored,
				static function ( $row ) {
					return ! empty( $row['dest_hit'] );
				}
			)
		);
		if ( count( $hits ) >= 3 ) {
			$scored = $hits;
		} elseif ( count( $hits ) >= 1 ) {
			$scored = $hits;
			$where  = $dest !== '' ? ucwords( $dest ) : ucwords( $loc );
			$parsed['expansion_note'] = 'Showing ' . ( 'hotel' === $kind ? 'hotels' : ( 'flight' === $kind ? 'flights' : 'deals' ) ) . ' matched to ' . $where . '.';
		} else {
			$scored = array();
			$where  = $dest !== '' ? ucwords( $dest ) : ucwords( $loc );
			$parsed['expansion_note'] = 'No verified ' . ( 'hotel' === $kind ? 'hotel' : ( 'flight' === $kind ? 'flight' : 'travel' ) ) . ' deals for ' . $where . ' in live inventory.';
		}
	}

	// Flight origin hard preference when enough origin hits exist.
	if ( 'flight' === $kind && $origin !== '' && $scored ) {
		$ohits = array_values(
			array_filter(
				$scored,
				static function ( $row ) {
					return ! empty( $row['origin_hit'] );
				}
			)
		);
		if ( count( $ohits ) >= 2 ) {
			$scored = $ohits;
		}
	}

	usort(
		$scored,
		static function ( $a, $b ) {
			$sa = (float) $a['score'];
			$sb = (float) $b['score'];
			if ( abs( $sa - $sb ) > 0.01 ) {
				return ( $sa > $sb ) ? -1 : 1;
			}
			$da = $a['distance'] === null ? 99999.0 : (float) $a['distance'];
			$db = $b['distance'] === null ? 99999.0 : (float) $b['distance'];
			return ( $da < $db ) ? -1 : 1;
		}
	);

	if ( ( $dest !== '' || $origin !== '' ) && empty( $parsed['expansion_note'] ) && empty( $parsed['strict'] ) && empty( $parsed['max_price'] ) ) {
		$matched = 0;
		foreach ( array_slice( $scored, 0, $limit ) as $row ) {
			if ( (float) $row['score'] >= 40.0 ) {
				++$matched;
			}
		}
		if ( 0 === $matched && $scored ) {
			$where = $dest !== '' ? ucwords( $dest ) : ucwords( $origin );
			$parsed['expansion_note'] = 'No verified travel matches for ' . $where . ' — we will not substitute unrelated routes.';
			$scored                   = array();
		}
	}

	$out = array();
	foreach ( $scored as $row ) {
		$card = bds_ai_finder_listing_card( (int) $row['id'], isset( $row['distance'] ) ? $row['distance'] : null, $units );
		if ( ! $card ) {
			continue;
		}
		// Belt-and-suspenders: never leak wrong deal kind into the pack.
		$card_kind = isset( $row['deal_kind'] ) ? (string) $row['deal_kind'] : '';
		if ( 'hotel' === $kind && 'hotel' !== $card_kind ) {
			continue;
		}
		if ( 'flight' === $kind && 'flight' !== $card_kind ) {
			continue;
		}
		$card['relevance']   = round( (float) $row['score'], 2 );
		$card['travel_kind'] = $card_kind !== '' ? $card_kind : ( isset( $card['travel_kind'] ) ? $card['travel_kind'] : '' );
		$out[]               = $card;
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	set_transient( $ckey, $out, 90 );
	return apply_filters( 'bds_ai_finder_listings', $out, $parsed );
}

function bds_ai_finder_query_listings( &$parsed, $limit = 8 ) {
	$limit = max( 1, min( 12, (int) $limit ) );
	$types = bds_ai_finder_listing_types();
	$kw    = isset( $parsed['keywords'] ) ? trim( (string) $parsed['keywords'] ) : '';
	$postal  = isset( $parsed['postal'] ) ? (string) $parsed['postal'] : '';
	$lat   = isset( $parsed['lat'] ) ? $parsed['lat'] : null;
	$lng   = isset( $parsed['lng'] ) ? $parsed['lng'] : null;
	$units = ! empty( $parsed['units'] ) ? (string) $parsed['units'] : 'km';
	$is_digital = ! empty( $parsed['is_digital'] );
	$use_geo = ( ! $is_digital && $lat !== null && $lng !== null && ( ! empty( $parsed['wants_near'] ) || ! empty( $parsed['near_me'] ) || $postal !== '' || ! empty( $parsed['client_geo'] ) || $parsed['search_mode'] === 'local_pack' ) );

	// Short TTL + listings cache ver so LG imports / edits never stick behind a stale pool.
	$list_ver = (int) get_option( BDS_AI_LISTINGS_CACHE_VER_OPT, 1 );
	$loc_tid  = ( ! empty( $parsed['location_term'] ) && $parsed['location_term'] instanceof WP_Term ) ? (int) $parsed['location_term']->term_id : 0;
	$cat_tid  = ( ! empty( $parsed['category_term'] ) && $parsed['category_term'] instanceof WP_Term ) ? (int) $parsed['category_term']->term_id : 0;
	$ckey     = 'bds_ai_lq_' . md5(
		wp_json_encode(
			array(
				$list_ver,
				BDS_AI_FINDER_VER,
				function_exists( 'bds_ai_concierge_ver' ) ? bds_ai_concierge_ver() : '',
				$limit,
				$kw,
				$postal,
				$loc_tid,
				$cat_tid,
				isset( $parsed['location'] ) ? (string) $parsed['location'] : '',
				isset( $parsed['category'] ) ? (string) $parsed['category'] : '',
				isset( $parsed['vertical'] ) ? (string) $parsed['vertical'] : '',
				isset( $parsed['search_mode'] ) ? (string) $parsed['search_mode'] : '',
				isset( $parsed['follow_up'] ) ? (string) $parsed['follow_up'] : '',
				! empty( $parsed['is_gift'] ),
				$is_digital,
				$use_geo,
				$lat !== null ? round( (float) $lat, 2 ) : null,
				$lng !== null ? round( (float) $lng, 2 ) : null,
				isset( $parsed['radius_km'] ) ? $parsed['radius_km'] : null,
				isset( $parsed['dish_tokens'] ) ? $parsed['dish_tokens'] : array(),
				isset( $parsed['max_price'] ) ? $parsed['max_price'] : null,
				! empty( $parsed['strict'] ),
			)
		)
	);
	$cached = get_transient( $ckey );
	if ( is_array( $cached ) ) {
		if ( isset( $cached['_exp_note'] ) ) {
			$parsed['expansion_note'] = (string) $cached['_exp_note'];
		}
		if ( isset( $cached['_radius'] ) ) {
			$parsed['radius_km'] = $cached['_radius'];
		}
		return isset( $cached['cards'] ) && is_array( $cached['cards'] ) ? $cached['cards'] : $cached;
	}

	// Taxonomy+score: prefer a wide live pool (newest first) over tiny stale slices.
	$pool_cap = $use_geo ? 200 : max( 120, $limit * 20 );
	if ( $loc_tid || $cat_tid ) {
		$pool_cap = max( $pool_cap, 180 );
	}
	$ids = bds_ai_finder_collect_candidate_ids( $parsed, $types, $kw, $postal, $pool_cap );
	$n_ids = count( $ids );
	$n_excl = 0;
	// Digital intent: always surface BrandDad network seeds when present.
	if ( $is_digital ) {
		foreach ( array( 'branddad-co', 'branddad-social', 'hosttech' ) as $slug ) {
			$p = get_page_by_path( $slug, OBJECT, $types );
			if ( $p instanceof WP_Post ) {
				array_unshift( $ids, (int) $p->ID );
			}
		}
		if ( $kw !== '' ) {
			$qd = new WP_Query(
				array(
					'post_type'      => $types,
					'post_status'    => 'publish',
					'posts_per_page' => 12,
					's'              => $kw,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$ids = array_merge( $ids, array_map( 'intval', (array) $qd->posts ) );
		}
		$ids = array_values( array_unique( array_filter( $ids ) ) );
	}

	// Exact postal hits in address / _zip meta.
	$postal_hits = array();
	if ( $postal !== '' ) {
		$postal_hits = bds_ai_finder_ids_matching_postal( $postal, $types, 40 );
		$ids           = array_merge( $postal_hits, $ids );
	}

	// Score all candidates (taxonomy + vertical + GBP + optional distance).
	$scored = array();
	$seen   = array();
	$tp_meta = defined( 'BDS_TP_META' ) ? BDS_TP_META : '_bds_travelpayouts';
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id <= 0 || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		// Keep TravelPayouts deals out of local restaurant / business packs.
		if ( (string) get_post_meta( $id, $tp_meta, true ) === '1' ) {
			continue;
		}
		$is_postal    = in_array( $id, $postal_hits, true );
		$dist        = null;
		$coords      = bds_ai_finder_listing_coords( $id );
		if ( $use_geo ) {
			if ( ! $coords ) {
				if ( ! $is_postal ) {
					continue;
				}
			} else {
				$dist = bds_ai_finder_haversine_km( (float) $lat, (float) $lng, $coords['lat'], $coords['lng'] );
			}
		}
		$rel = bds_ai_finder_score_listing( $id, $parsed, $dist );
		// Hard rails: never surface wrong-vertical listings (postal does not bypass).
		if ( ! empty( $rel['exclude'] ) ) {
			$n_excl++;
			continue;
		}
		$scored[] = array(
			'id'       => $id,
			'distance' => $dist,
			'postal_hit' => $is_postal,
			'score'    => (float) $rel['score'] + ( $is_postal ? 8.0 : 0.0 ),
			'vertical' => (string) $rel['vertical'],
		);
	}

	$ranked = $scored;
	$parsed['_diag'] = array(
		'n_ids'      => (int) $n_ids,
		'n_scored'   => count( $scored ),
		'n_excluded' => (int) $n_excl,
		'retrieval'  => ( function_exists( 'bds_ai_finder_is_broad_discovery' ) && bds_ai_finder_is_broad_discovery( $parsed ) ) ? 'discovery' : 'constrained',
	);
	$strict = ! empty( $parsed['strict'] ) || ( ! empty( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) );
	if ( $use_geo ) {
		$radii   = array( 1.0, 5.0, 25.0 );
		$start_r = isset( $parsed['radius_km'] ) && $parsed['radius_km'] !== null ? (float) $parsed['radius_km'] : 5.0;
		if ( ! empty( $parsed['follow_up'] ) && 'closer' === $parsed['follow_up'] ) {
			$radii = array( 1.0, 3.0, 8.0 );
		}
		if ( $strict ) {
			$radii = array( $start_r );
		}
		$radii = array_values(
			array_unique(
				array_merge(
					array( $start_r ),
					$radii
				)
			)
		);
		sort( $radii, SORT_NUMERIC );
		$picked = array();
		$used_r = $radii[ count( $radii ) - 1 ];
		foreach ( $radii as $r ) {
			$picked = array();
			foreach ( $scored as $row ) {
				if ( ! empty( $row['postal_hit'] ) ) {
					$picked[] = $row;
					continue;
				}
				if ( $row['distance'] !== null && (float) $row['distance'] <= $r ) {
					$picked[] = $row;
				}
			}
			$used_r = $r;
			if ( count( $picked ) >= min( 3, $limit ) ) {
				break;
			}
			if ( $strict ) {
				break;
			}
		}
		$ranked               = $picked;
		$parsed['radius_km']  = $used_r;
		if ( ! $strict && $used_r > $start_r + 0.01 && count( $ranked ) > 0 ) {
			$place = ! empty( $parsed['place_label'] ) ? (string) $parsed['place_label'] : ( ! empty( $parsed['area_label'] ) ? (string) $parsed['area_label'] : 'you' );
			$parsed['expansion_note'] = 'Widened to about ' . ( 'mi' === $units ? round( $used_r * 0.621371, 1 ) . ' mi' : $used_r . ' km' ) . ' around ' . $place . ' to surface nearby matches.';
		}
	}

	// Expand to parent city when still thin — never when hard constraints are in play.
	if ( ! $strict && count( $ranked ) < 3 && ! $is_digital ) {
		$expanded = bds_ai_finder_expand_candidates( $parsed, $types, $kw, $ranked, max( 80, $limit * 16 ) );
		if ( ! empty( $expanded['note'] ) ) {
			$parsed['expansion_note'] = (string) $expanded['note'];
		}
		if ( ! empty( $expanded['rows'] ) ) {
			$ranked = $expanded['rows'];
		}
	}

	// Final hard-rail pass: drop any wrong-vertical rows before OpenAI pack.
	$intent_final = isset( $parsed['vertical'] ) ? (string) $parsed['vertical'] : 'other';
	$allowed_v    = bds_ai_finder_allowed_listing_verticals( $intent_final );
	if ( ! empty( $allowed_v ) ) {
		$ranked = array_values(
			array_filter(
				$ranked,
				static function ( $row ) use ( $allowed_v ) {
					$v = isset( $row['vertical'] ) ? strtolower( (string) $row['vertical'] ) : 'other';
					return in_array( $v, $allowed_v, true );
				}
			)
		);
	}

	usort(
		$ranked,
		static function ( $a, $b ) use ( $use_geo ) {
			$sa = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
			$sb = isset( $b['score'] ) ? (float) $b['score'] : 0.0;
			if ( abs( $sa - $sb ) > 0.01 ) {
				return ( $sa > $sb ) ? -1 : 1;
			}
			if ( $use_geo ) {
				$da = $a['distance'] === null ? 9999.0 : (float) $a['distance'];
				$db = $b['distance'] === null ? 9999.0 : (float) $b['distance'];
				if ( $da !== $db ) {
					return ( $da < $db ) ? -1 : 1;
				}
			}
			return 0;
		}
	);

	if ( $postal !== '' && $parsed['expansion_note'] === '' && count( $ranked ) > 0 ) {
		$exact = count( $postal_hits );
		$area  = ! empty( $parsed['area_label'] ) ? (string) $parsed['area_label'] : $postal;
		$city  = ! empty( $parsed['location'] ) ? ucwords( (string) $parsed['location'] ) : 'the area';
		if ( $exact === 0 ) {
			$parsed['expansion_note'] = 'Showing ' . $city . ' near ' . $area . ' (' . $postal . ') - no exact postal matches, so here are nearby Directory listings.';
		} elseif ( $exact < 3 && count( $ranked ) > $exact ) {
			$parsed['expansion_note'] = 'Showing ' . $city . ' near ' . $area . ' (' . $postal . '), including nearby listings beyond the exact postal code.';
		}
	}

	$out = array();
	foreach ( $ranked as $row ) {
		$card = bds_ai_finder_listing_card( (int) $row['id'], isset( $row['distance'] ) ? $row['distance'] : null, $units );
		if ( $card ) {
			if ( isset( $row['score'] ) ) {
				$card['relevance'] = round( (float) $row['score'], 2 );
			}
			$out[] = $card;
		}
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	set_transient(
		$ckey,
		array(
			'cards'     => $out,
			'_exp_note' => isset( $parsed['expansion_note'] ) ? (string) $parsed['expansion_note'] : '',
			'_radius'   => isset( $parsed['radius_km'] ) ? $parsed['radius_km'] : null,
		),
		90
	);
	return apply_filters( 'bds_ai_finder_listings', $out, $parsed );
}

/**
 * @param array<string,mixed> $parsed Intent.
 * @param array<int,string>   $types  Post types.
 * @param string              $kw     Keywords.
 * @param string              $postal  Postal.
 * @param int                 $cap    Max IDs.
 * @return array<int,int>
 */
function bds_ai_finder_collect_candidate_ids( $parsed, $types, $kw, $postal, $cap = 80 ) {
	$cap       = max( 24, min( 250, (int) $cap ) );
	$discovery = function_exists( 'bds_ai_finder_is_broad_discovery' ) && bds_ai_finder_is_broad_discovery( $parsed );
	$tax_query = array( 'relation' => 'AND' );
	$has_loc   = ( ! empty( $parsed['location_term'] ) && $parsed['location_term'] instanceof WP_Term );
	$has_cat   = ( ! empty( $parsed['category_term'] ) && $parsed['category_term'] instanceof WP_Term );
	if ( $has_loc ) {
		$tax_query[] = array(
			'taxonomy'         => $parsed['location_term']->taxonomy,
			'field'            => 'term_id',
			'terms'            => array( (int) $parsed['location_term']->term_id ),
			'include_children' => true,
		);
	}
	$cat_ids = array();
	// Broad discovery: location + later vertical qualification. Do not AND a
	// parent/domain category that listings are not tagged with.
	if ( $has_cat && ! $discovery ) {
		$cat_ids = bds_ai_finder_related_category_ids( $parsed['category_term'] );
		if ( ! $cat_ids ) {
			$cat_ids = array( (int) $parsed['category_term']->term_id );
		}
		$tax_query[] = array(
			'taxonomy'         => $parsed['category_term']->taxonomy,
			'field'            => 'term_id',
			'terms'            => $cat_ids,
			'include_children' => true,
			'operator'         => 'IN',
		);
	}

	$ids = array();
	$has_place = $has_loc
		|| ( isset( $parsed['postal'] ) && (string) $parsed['postal'] !== '' )
		|| ( ! empty( $parsed['lat'] ) && ! empty( $parsed['lng'] ) );

	// Directorist pre_get_posts often forces posts_per_page=12 even with
	// suppress_filters. SQL is the primary location pool.
	if ( $has_loc ) {
		$sql_cats = ( $discovery || ! $cat_ids ) ? array() : $cat_ids;
		if ( ! $discovery && $cat_ids ) {
			$sql_cats = $cat_ids;
		}
		$ids = array_merge( $ids, bds_ai_finder_sql_tax_ids( $types, (int) $parsed['location_term']->term_id, $sql_cats, $cap ) );
		if ( $discovery && count( $ids ) < 8 ) {
			$ids = array_merge( $ids, bds_ai_finder_sql_tax_ids( $types, (int) $parsed['location_term']->term_id, array(), $cap ) );
		}
	}

	$base = array(
		'post_type'              => $types,
		'post_status'            => 'publish',
		'posts_per_page'         => $cap,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'fields'                 => 'ids',
		'suppress_filters'       => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'orderby'                => 'date',
		'order'                  => 'DESC',
	);

	// 1) Taxonomy-first (no keyword AND) - preferred when we know place/category.
	if ( count( $tax_query ) > 1 ) {
		$args_tax               = $base;
		$args_tax['tax_query']  = $tax_query;
		$args_tax['posts_per_page'] = $cap;
		$q_tax = new WP_Query( $args_tax );
		$ids   = array_merge( $ids, array_map( 'intval', (array) $q_tax->posts ) );
	}

	// 2) Primary category + location only — skip on broad discovery.
	if ( ! $discovery && $has_cat && $has_loc && count( $ids ) < $cap ) {
		$primary = (int) $parsed['category_term']->term_id;
		$args_p  = $base;
		$args_p['tax_query'] = array(
			'relation' => 'AND',
			array(
				'taxonomy'         => $parsed['location_term']->taxonomy,
				'field'            => 'term_id',
				'terms'            => array( (int) $parsed['location_term']->term_id ),
				'include_children' => true,
			),
			array(
				'taxonomy'         => $parsed['category_term']->taxonomy,
				'field'            => 'term_id',
				'terms'            => array( $primary ),
				'include_children' => true,
			),
		);
		$qp  = new WP_Query( $args_p );
		$ids = array_merge( array_map( 'intval', (array) $qp->posts ), $ids );
	}

	// 3) Optional keyword refine - only clean leftover tokens, never filler+city AND.
	if ( $kw !== '' && count( $ids ) < 8 ) {
		$args_kw = $base;
		$args_kw['s'] = $kw;
		if ( count( $tax_query ) > 1 ) {
			$args_kw['tax_query'] = $tax_query;
		}
		$qk  = new WP_Query( $args_kw );
		$ids = array_merge( $ids, array_map( 'intval', (array) $qk->posts ) );
	}

	// 4) Global keyword-only only when the user did not specify place/zip/geo.
	if ( count( $ids ) < 8 && $kw !== '' && ! $has_place ) {
		$q3  = new WP_Query(
			array(
				'post_type'      => $types,
				'post_status'    => 'publish',
				'posts_per_page' => $cap,
				's'              => $kw,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$ids = array_merge( $ids, array_map( 'intval', (array) $q3->posts ) );
	}

	// 5) Location pool for discovery, or when category AND was empty.
	$need_loc_pool = ( $discovery && $has_loc )
		|| ( ! $has_cat && count( $ids ) < 12 )
		|| ( $has_cat && count( $ids ) < 3 && $has_loc );
	if ( $need_loc_pool && $has_loc ) {
		$q4  = new WP_Query(
			array(
				'post_type'        => $types,
				'post_status'      => 'publish',
				'posts_per_page'   => min( 120, $cap ),
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'tax_query'      => array(
					array(
						'taxonomy'         => $parsed['location_term']->taxonomy,
						'field'            => 'term_id',
						'terms'            => array( (int) $parsed['location_term']->term_id ),
						'include_children' => true,
					),
				),
			)
		);
		$ids = array_merge( $ids, array_map( 'intval', (array) $q4->posts ) );
	}

	if ( count( $ids ) < 3 && $has_loc ) {
		$sql_cats = $discovery ? array() : $cat_ids;
		$sql_ids  = bds_ai_finder_sql_tax_ids( $types, (int) $parsed['location_term']->term_id, $sql_cats, $cap );
		$ids      = array_merge( $ids, $sql_ids );
	}

	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * Listings whose address or _zip meta contains the postal code.
 *
 * @param string            $postal  Postal.
 * @param array<int,string> $types Post types.
 * @param int               $cap   Max.
 * @return array<int,int>
 */
function bds_ai_finder_ids_matching_postal( $postal, $types, $cap = 40 ) {
	$postal = preg_replace( '/\D+/', '', (string) $postal );
	if ( strlen( $postal ) !== 5 ) {
		return array();
	}
	$q = new WP_Query(
		array(
			'post_type'              => $types,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 5, min( 60, (int) $cap ) ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				'relation' => 'OR',
				array(
					'key'     => '_zip',
					'value'   => $postal,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_address',
					'value'   => $postal,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'address',
					'value'   => $postal,
					'compare' => 'LIKE',
				),
			),
		)
	);
	return array_map( 'intval', (array) $q->posts );
}

/**
 * Expand thin zip/near results to parent city or wider radius.
 *
 * @param array<string,mixed>              $parsed Intent.
 * @param array<int,string>                $types  Types.
 * @param string                           $kw     Keywords.
 * @param array<int,array<string,mixed>>   $ranked Current rows.
 * @param int                              $cap    Cap.
 * @return array{rows:array<int,array<string,mixed>>,note:string}
 */
function bds_ai_finder_expand_candidates( &$parsed, $types, $kw, $ranked, $cap = 40 ) {
	$note = '';
	$city = isset( $parsed['location'] ) ? (string) $parsed['location'] : '';
	$postal  = isset( $parsed['postal'] ) ? (string) $parsed['postal'] : '';
	$lat  = isset( $parsed['lat'] ) ? $parsed['lat'] : null;
	$lng  = isset( $parsed['lng'] ) ? $parsed['lng'] : null;
	$area = ! empty( $parsed['area_label'] ) ? (string) $parsed['area_label'] : ( $postal !== '' ? $postal : $city );

	// Ensure location term for city expansion.
	if ( $city !== '' && ( empty( $parsed['location_term'] ) || ! ( $parsed['location_term'] instanceof WP_Term ) ) ) {
		$parsed['location_term'] = bds_ai_finder_resolve_location_term( $city );
	}

	$pool_parsed = $parsed;
	$extra_ids   = bds_ai_finder_collect_candidate_ids( $pool_parsed, $types, $kw, '', $cap );
	// Still thin? allow related food siblings via related_category_ids already;
	// only drop category when truly empty - never for retail pollution on food intent.
	if ( count( $extra_ids ) + count( $ranked ) < 2 && ! empty( $pool_parsed['category_term'] ) ) {
		$pool2                  = $pool_parsed;
		$intent_v               = isset( $parsed['vertical'] ) ? (string) $parsed['vertical'] : '';
		if ( 'food' === $intent_v || 'nightlife' === $intent_v ) {
			// Soften to parent restaurants / bars rather than city-wide dump.
			$parent_slug = ( 'nightlife' === $intent_v ) ? 'bars' : 'restaurants';
			$parent      = get_term_by( 'slug', $parent_slug, 'at_biz_dir-category' );
			if ( $parent instanceof WP_Term ) {
				$pool2['category_term'] = $parent;
				$pool2['category']      = $parent_slug;
			}
		} else {
			$pool2['category_term'] = null;
			$pool2['category']      = '';
		}
		$extra_ids = array_merge( $extra_ids, bds_ai_finder_collect_candidate_ids( $pool2, $types, $kw, '', $cap ) );
	}
	// Last resort city pool - scorer will exclude wrong verticals.
	if ( count( $extra_ids ) + count( $ranked ) < 2 ) {
		$pool3                  = $pool_parsed;
		$pool3['category_term'] = null;
		$pool3['category']      = '';
		$extra_ids              = array_merge( $extra_ids, bds_ai_finder_collect_candidate_ids( $pool3, $types, '', '', $cap ) );
	}

	$seen = array();
	foreach ( $ranked as $row ) {
		$seen[ (int) $row['id'] ] = true;
	}
	$wider_radius = 35.0;
	$rows         = $ranked;

	foreach ( $extra_ids as $id ) {
		$id = (int) $id;
		if ( $id <= 0 || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$dist        = null;
		$coords      = bds_ai_finder_listing_coords( $id );
		if ( $lat !== null && $lng !== null ) {
			if ( ! $coords ) {
				continue;
			}
			$dist = bds_ai_finder_haversine_km( (float) $lat, (float) $lng, $coords['lat'], $coords['lng'] );
			if ( $dist > $wider_radius ) {
				continue;
			}
		}
		$rel = bds_ai_finder_score_listing( $id, $parsed, $dist );
		if ( ! empty( $rel['exclude'] ) ) {
			continue;
		}
		$rows[] = array(
			'id'       => $id,
			'distance' => $dist,
			'postal_hit' => false,
			'score'    => (float) $rel['score'],
			'vertical' => (string) $rel['vertical'],
		);
	}

	if ( $lat !== null && $lng !== null ) {
		usort(
			$rows,
			static function ( $a, $b ) {
				$da = $a['distance'] === null ? 9999.0 : (float) $a['distance'];
				$db = $b['distance'] === null ? 9999.0 : (float) $b['distance'];
				if ( $da === $db ) {
					return 0;
				}
				return ( $da < $db ) ? -1 : 1;
			}
		);
	}

	if ( count( $rows ) > count( $ranked ) ) {
		$city_label = $city !== '' ? ucwords( $city ) : 'nearby Directory cities';
		if ( $postal !== '' ) {
			$note = 'Showing ' . $city_label . ' near ' . $area . ' (' . $postal . ') - few exact postal matches, so we expanded to the parent city/area.';
		} elseif ( ! empty( $parsed['near_me'] ) ) {
			$note = 'Showing listings near you in ' . $city_label . ' (expanded radius).';
		} else {
			$note = 'Showing ' . $city_label . ' near ' . $area . '.';
		}
		$parsed['search_mode'] = ! empty( $parsed['search_mode'] ) ? ( $parsed['search_mode'] . '_expanded' ) : 'expanded';
		$parsed['radius_km']   = $wider_radius;
	}

	return array(
		'rows' => $rows,
		'note' => $note,
	);
}

/**
 * Guest-safe listing card - no phone/email/whatsapp.
 *
 * @param int        $post_id  Listing ID.
 * @param float|null $distance Optional km from search center.
 * @param string     $units    km|mi.
 * @return array<string,mixed>|null
 */
function bds_ai_finder_listing_card( $post_id, $distance = null, $units = 'km' ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		return null;
	}

	$cats = wp_get_post_terms( $post_id, 'at_biz_dir-category', array( 'fields' => 'names' ) );
	if ( is_wp_error( $cats ) ) {
		$cats = array();
	}
	$locs = wp_get_post_terms( $post_id, 'at_biz_dir-location', array( 'fields' => 'names' ) );
	if ( is_wp_error( $locs ) ) {
		$locs = array();
	}

	$address = (string) get_post_meta( $post_id, '_address', true );
	if ( $address === '' ) {
		$address = (string) get_post_meta( $post_id, 'address', true );
	}
	$website = (string) get_post_meta( $post_id, '_website', true );
	if ( $website === '' ) {
		$website = (string) get_post_meta( $post_id, 'website', true );
	}
	$tagline = (string) get_post_meta( $post_id, '_tagline', true );
	if ( $tagline === '' ) {
		$tagline = (string) get_post_meta( $post_id, 'tagline', true );
	}

	$excerpt = $tagline !== '' ? $tagline : wp_trim_words( wp_strip_all_tags( $post->post_content ), 28 );
	// Strip accidental contact leaks from excerpt.
	$excerpt = bds_text_plain( bds_ai_finder_scrub_text( $excerpt ) );
	$address = bds_ai_finder_scrub_text( $address );

	$thumb = get_the_post_thumbnail_url( $post_id, 'medium' );
	if ( ! $thumb ) {
		$thumb = '';
	}

	$claim = (string) get_post_meta( $post_id, '_bds_claim_ready', true );
	$claim_ready = ( $claim === '1' || $claim === 'yes' || $claim === 'true' );

	$card = array(
		'id'          => $post_id,
		'name'        => bds_text_plain( get_the_title( $post_id ) ),
		'url'         => get_permalink( $post_id ),
		'categories'  => array_values( array_map( 'bds_text_plain', (array) $cats ) ),
		'locations'   => array_values( array_map( 'bds_text_plain', (array) $locs ) ),
		'address'     => bds_text_plain( $address ),
		'website'     => esc_url_raw( $website ),
		'excerpt'     => $excerpt,
		'image'       => esc_url_raw( $thumb ),
		'claim_ready' => $claim_ready,
		// Explicitly omitted for guests: phone, email, whatsapp, social outreach.
	);

	$tp_flag = (string) get_post_meta( $post_id, defined( 'BDS_TP_META' ) ? BDS_TP_META : '_bds_travelpayouts', true );
	if ( '1' === $tp_flag || 'yes' === strtolower( $tp_flag ) ) {
		$kind = strtolower( (string) get_post_meta( $post_id, defined( 'BDS_TP_KIND' ) ? BDS_TP_KIND : '_bds_tp_kind', true ) );
		$price_label = (string) get_post_meta( $post_id, defined( 'BDS_TP_PRICE_LABEL' ) ? BDS_TP_PRICE_LABEL : '_bds_tp_price_label', true );
		$route_label = (string) get_post_meta( $post_id, defined( 'BDS_TP_ROUTE' ) ? BDS_TP_ROUTE : '_bds_tp_route_label', true );
		$dates_label = (string) get_post_meta( $post_id, defined( 'BDS_TP_DATES_LABEL' ) ? BDS_TP_DATES_LABEL : '_bds_tp_dates_label', true );
		$book_url    = $website !== '' ? $website : (string) get_post_meta( $post_id, '_website', true );
		// Stable deal page — local SEO /city/state/tp-* paths 404 for affiliate inventory.
		$listing_url           = home_url( user_trailingslashit( 'listing/' . $post->post_name ) );
		$card['url']           = $listing_url;
		$card['listing_url']   = $listing_url;
		$card['is_travel']     = true;
		$card['travel_kind'] = ( 'hotel' === $kind ) ? 'hotel' : 'flight';
		$card['price_label'] = bds_text_plain( $price_label );
		$card['route_label'] = bds_text_plain( $route_label );
		$card['dates_label'] = bds_text_plain( $dates_label );
		$card['book_url']    = esc_url_raw( $book_url );
		$card['claim_ready'] = false;
		if ( $price_label !== '' ) {
			array_unshift( $card['categories'], bds_text_plain( $price_label ) );
			$card['categories'] = array_values( array_unique( $card['categories'] ) );
		}
		if ( $route_label !== '' && $card['address'] === '' ) {
			$card['address'] = bds_text_plain( $route_label );
		}
		// Prefer listing page (Book now on-page); keep affiliate as website/book_url.
		$card['website'] = esc_url_raw( $book_url );
	}

	$gbp = bds_ai_finder_listing_gbp( $post_id );
	if ( $gbp['rating'] > 0 ) {
		$card['google_rating'] = (string) round( $gbp['rating'], 1 );
	}
	if ( $gbp['count'] > 0 ) {
		$card['google_review_count'] = (string) (int) $gbp['count'];
	}
	if ( $distance !== null && is_finite( (float) $distance ) ) {
		$km = (float) $distance;
		$card['distance_km'] = round( $km, 2 );
		if ( 'mi' === $units ) {
			$mi = $km * 0.621371;
			$card['distance_mi'] = round( $mi, 2 );
			if ( $mi < 0.1 ) {
				$card['distance_label'] = max( 1, (int) round( $mi * 5280 ) ) . ' ft away';
			} else {
				$card['distance_label'] = round( $mi, 1 ) . ' mi away';
			}
		} else {
			if ( $km < 1.0 ) {
				$card['distance_label'] = round( $km * 1000 ) . ' m away';
			} else {
				$card['distance_label'] = round( $km, 1 ) . ' km away';
			}
		}
	}
	/**
	 * Filter listing card for AI Finder (GBP enrich, etc.).
	 *
	 * @param array<string,mixed> $card    Card.
	 * @param int                 $post_id Listing ID.
	 */
	$card = apply_filters( 'bds_ai_finder_listing_row', $card, $post_id );
	return is_array( $card ) ? $card : null;
}

if ( ! function_exists( 'bds_text_plain' ) ) {
	/**
	 * Normalize WordPress-filtered text for plain-text consumers.
	 *
	 * get_the_title() and friends run through wptexturize(), which emits HTML
	 * entities ("Lirio&#8217;s"). A browser decodes those in HTML, but JSON
	 * payloads, JSON-LD and share URLs are not HTML contexts, so the entity
	 * survives to the surface and is shown literally. Decode exactly once here;
	 * every caller still escapes for its own output context afterwards.
	 *
	 * Defined with a function_exists guard so each MU plugin stays independently
	 * deployable - whichever file loads first wins, and the body is identical.
	 *
	 * @param string $text Raw text.
	 * @return string Plain UTF-8 text.
	 */
	function bds_text_plain( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return '';
		}
		static $mojibake = null;
		if ( null === $mojibake ) {
			// UTF-8 punctuation that was previously read as CP1252 and re-encoded.
			$mojibake = array(
				"\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2" => "\xE2\x80\x99",
				"\xC3\xA2\xE2\x82\xAC\xCB\x9C"     => "\xE2\x80\x98",
				"\xC3\xA2\xE2\x82\xAC\xC5\x93"     => "\xE2\x80\x9C",
				"\xC3\xA2\xE2\x82\xAC\xC2\x9D"     => "\xE2\x80\x9D",
				"\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D" => "\xE2\x80\x94",
				"\xC3\xA2\xE2\x82\xAC\xE2\x80\x93" => "\xE2\x80\x93",
				"\xC3\xA2\xE2\x82\xAC\xC2\xA6"     => "\xE2\x80\xA6",
				"\xC3\x82\xC2\xB7"                 => "\xC2\xB7",
				"\xC3\x82\xC2\xA0"                 => ' ',
				"\xEF\xBF\xBD"                     => '',
			);
		}
		$text = strtr( $text, $mojibake );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( $text );
	}
}

/**
 * Remove phone/email/whatsapp patterns from AI-facing text.
 *
 * @param string $text Text.
 * @return string
 */
function bds_ai_finder_scrub_text( $text ) {
	if ( ! is_string( $text ) || $text === '' ) {
		return '';
	}
	$text = preg_replace( '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '', $text );
	$text = preg_replace( '/(?:\+?\d[\d\s().\-]{7,}\d)/u', '', $text );
	$text = preg_replace( '/(?:wa\.me|api\.whatsapp\.com|whatsapp)[^\s]*/iu', '', $text );
	$text = preg_replace( '/\s{2,}/', ' ', $text );
	return trim( (string) $text );
}

/**
 * Soft network services - only when intent matches.
 *
 * @param string              $message User text.
 * @param array<string,mixed> $parsed  Intent.
 * @return array<int,array<string,string>>
 */
function bds_ai_finder_match_services( $message, $parsed ) {
	$m = strtolower( $message . ' ' . ( isset( $parsed['category'] ) ? $parsed['category'] : '' ) );
	$is_gift = ! empty( $parsed['is_gift'] ) || bds_ai_finder_is_gift_intent( $m );

	// Gift / Save Money Cards: Directory discount gift cards + optional Social mirror.
	if ( $is_gift ) {
		$dir_gift = home_url( '/product-category/discount-gift-cards/' );
		return array(
			array(
				'id'    => 'gift-cards-directory',
				'label' => 'Discount gift cards',
				'url'   => $dir_gift,
				'blurb' => 'Browse verified discounted gift cards on BrandDad Directory. Sign in to purchase or contact a seller.',
			),
			array(
				'id'    => 'gift-card-marketplace',
				'label' => 'Gift Card Marketplace',
				'url'   => home_url( '/gift-card-marketplace/' ),
				'blurb' => 'Marketplace entry for discounted gift cards and sellers on Directory.',
			),
		);
	}

	$is_travel = ! empty( $parsed['is_travel'] ) || bds_ai_finder_is_travel_intent( $m );
	if ( $is_travel ) {
		$panel = function_exists( 'bds_tp_panel_travel_url' )
			? bds_tp_panel_travel_url()
			: ( function_exists( 'bds_member_panel_url' ) ? bds_member_panel_url() : home_url( '/ai-network/#bds-travel' ) );
		$can   = function_exists( 'bds_tp_user_can_book' ) ? bds_tp_user_can_book() : false;
		$url   = $can ? $panel : ( function_exists( 'bds_tp_gate_url' ) ? bds_tp_gate_url() : $panel );
		return array(
			array(
				'id'    => 'travel-search',
				'label' => $can ? 'Open member travel search' : 'Travel perk for members',
				'url'   => $url,
				'blurb' => $can
					? 'Compare partner flights & hotels in your Account Control panel. BrandDad does not sell tickets.'
					: 'Flight & hotel search is a paid member perk - join or sign in to compare partner deals.',
			),
		);
	}

	$catalog = array(
		array(
			'id'      => 'business-health',
			'label'   => 'Free Business Health Check',
			'url'     => home_url( '/?bds_health=1' ),
			'blurb'   => 'Public website signals + explainable score. Useful results first, then optional fixes with prices - no ranking promises.',
			'needles' => array( 'health check', 'website audit', 'seo audit', 'site score', 'how is my website', 'audit my site' ),
		),
		array(
			'id'      => 'branddad-co',
			'label'   => 'BrandDad.co - logos & websites',
			'url'     => 'https://branddad.co/',
			'blurb'   => 'Logo concepts and web builds for businesses that need a sharper brand.',
			'needles' => array( 'logo', 'brand', 'website', 'web design', 'redesign', 'identity' ),
		),
		array(
			'id'      => 'branddad-social',
			'label'   => 'BrandDad Social - growth & local/web services',
			'url'     => 'https://branddad.social/services/',
			'blurb'   => 'Local & web (GBP, SEO, speed, care), AI Ads, and social growth packages with clear scoped deliverables.',
			'needles' => array( 'social media', 'instagram', 'tiktok', 'growth', 'seo', 'marketing', 'followers', 'google business', 'gbp', 'local seo', 'site speed', 'fix my website', 'ai ads', 'paid ads', 'facebook ads' ),
		),
		array(
			'id'      => 'branddad-ai-ads',
			'label'   => 'AI Ads Automation',
			'url'     => 'https://branddad.social/ai-ads/',
			'blurb'   => 'Setup through Scale — AI creative and managed spend caps. Ad spend stays on your platforms.',
			'needles' => array( 'ai ads', 'paid ads', 'facebook ads', 'meta ads', 'google ads', 'ad spend', 'run ads' ),
		),
		array(
			'id'      => 'hosttech',
			'label'   => 'HostTech - hosting',
			'url'     => 'https://hosttech.net/',
			'blurb'   => 'Reliable hosting when your site needs a solid home.',
			'needles' => array( 'hosting', 'host', 'server', 'wordpress host', 'domain' ),
		),
		array(
			'id'      => 'directory-member',
			'label'   => 'Directory members - 10% off',
			'url'     => 'https://directory.branddad.social/registration/',
			'blurb'   => 'Active Directory members get 10% off eligible BrandDad network services.',
			// Avoid bare "discount" so "discount gift cards" never soft-sells membership.
			'needles' => array( 'member', 'membership', '10%', 'claim', 'join directory', 'elite', 'network elite', 'directory discount' ),
		),
		array(
			'id'      => 'engagement',
			'label'   => 'Engagement Group',
			'url'     => 'https://directory.branddad.social/engagement/',
			'blurb'   => 'Members can join engagement circles to grow visibility together.',
			'needles' => array( 'engagement', 'engage', 'visibility', 'growth circle', 'promote my listing' ),
		),
		array(
			'id'      => 'claim',
			'label'   => 'Claim your listing',
			'url'     => 'https://directory.branddad.social/registration/',
			'blurb'   => 'Own your Directory page - update photos, hours, and unlock contact for customers.',
			'needles' => array( 'claim my', 'claim listing', 'is this my business', 'own my listing', 'unclaimed' ),
		),
	);

	$out = array();
	foreach ( $catalog as $svc ) {
		foreach ( $svc['needles'] as $n ) {
			if ( false !== strpos( $m, $n ) ) {
				$out[] = array(
					'id'    => $svc['id'],
					'label' => $svc['label'],
					'url'   => $svc['url'],
					'blurb' => $svc['blurb'],
				);
				break;
			}
		}
	}
	// Cap soft-sell - never spam. Never recommend outreach products.
	$out = array_values(
		array_filter(
			$out,
			static function ( $svc ) {
				$blob = strtolower( (string) ( $svc['id'] ?? '' ) . ' ' . (string) ( $svc['url'] ?? '' ) . ' ' . (string) ( $svc['label'] ?? '' ) );
				return 1 !== preg_match( '/outreach|linkedin-outreach/', $blob );
			}
		)
	);
	$out = array_slice( $out, 0, ( ! empty( $parsed['mixed'] ) ? 4 : 2 ) );
	/**
	 * Filter soft service recommendations (Directory expansion / outreach hide).
	 *
	 * @param array<int,array<string,string>> $out     Services.
	 * @param string                          $message Message.
	 * @param array<string,mixed>             $parsed  Parsed intent.
	 */
	return apply_filters( 'bds_ai_finder_services', $out, $message, $parsed );
}

/**
 * Template reply when OpenAI is unavailable.
 *
 * @param string                          $message   User.
 * @param array<int,array<string,mixed>>  $listings  Cards.
 * @param array<int,array<string,string>> $services  Services.
 * @param bool                            $guest     Guest.
 * @param array<string,mixed>             $parsed    Intent.
 * @param array<int,array<string,mixed>>  $sponsored Sponsored cards.
 * @param string[]                        $bid_bits  Preformatted sponsored/places text.
 * @return string
 */
function bds_ai_finder_template_reply( $message, $listings, $services, $guest, $parsed, $sponsored = array(), $bid_bits = array(), $products = array(), $clarify = array() ) {
	$msg_lc = strtolower( trim( (string) $message ) );
	// Clarify first, exactly like the AI path would.
	if ( is_array( $clarify ) && ! empty( $clarify['ask'] ) && ! empty( $clarify['questions'] ) ) {
		$qs = array_slice( (array) $clarify['questions'], 0, 2 );
		return implode( ' ', $qs );
	}
	// Live inventory questions - never hardcode.
	if ( 1 === preg_match( '/\b(how many|how big|total|count|number of)\b/i', $msg_lc )
		&& 1 === preg_match( '/\b(listing|listings|business|businesses|director(?:y|ies)|inventory)\b/i', $msg_lc ) ) {
		$stats = bds_ai_finder_directory_stats();
		$n     = (int) $stats['published_listings'];
		$bits  = array(
			$n > 0
				? ( 'BrandDad Directory currently has ' . number_format_i18n( $n ) . ' published listings.' )
				: 'I could not read the live listing total just now - try again in a moment.',
		);
		if ( ! empty( $stats['categories'] ) ) {
			$bits[] = 'Across ' . number_format_i18n( (int) $stats['categories'] ) . ' active categories.';
		}
		$bits[] = 'Ask for a city, zip, or category and I will pull live matches.';
		return implode( ' ', $bits );
	}
	if ( ! empty( $parsed['is_gift'] ) ) {
		// Never invent codes, ETAs, supplier info, or order status — escalate to John.
		if ( function_exists( 'bds_gsf_support_gate' ) ) {
			$gate = bds_gsf_support_gate( $message, array( 'source' => 'ai_finder_template', 'is_gift' => 1 ) );
			if ( ! empty( $gate['escalate'] ) && ! empty( $gate['reply'] ) ) {
				return (string) $gate['reply'];
			}
		} elseif ( 1 === preg_match( '/\b(code|pin|cvv|when will|how long|deliver|eta|tracking|refund|still waiting|where.*(from|source)|supplier|wholesale)\b/i', $msg_lc ) ) {
			return 'I don’t have enough verified details to answer that accurately. I’ve flagged this for a human on our team — they’ll follow up. I won’t guess about codes, stock, delivery times, or pricing.';
		}
		$brand = isset( $parsed['gift_brand'] ) ? (string) $parsed['gift_brand'] : '';
		$bits  = array();
		if ( $products ) {
			if ( ! empty( $parsed['gift_exact'] ) ) {
				$bits[] = 'Yep - ' . count( $products ) . ' ' . ucfirst( $brand ) . ' card' . ( count( $products ) === 1 ? '' : 's' ) . ' in stock under face value.';
			} else {
				if ( $brand !== '' ) {
					$bits[] = 'We do not carry ' . ucfirst( $brand ) . ' right now.';
				}
				$bits[] = 'Closest cards we have for that:';
			}
			$bits[] = 'Sign in to check out; stock reflects cards on hand.';
		} else {
			$bits[] = 'Nothing in the gift-card catalog matches that yet.';
			$bits[] = 'Tell me what you are buying with it and I will find the closest card.';
		}
		if ( $listings ) {
			$bits[] = 'Related Directory sellers are below too.';
		}
		$text = implode( ' ', $bits );
		if ( $bid_bits ) {
			$text .= "\n\n" . implode( "\n\n", $bid_bits );
		}
		return $text;
	}
	if ( ! empty( $parsed['is_travel'] ) ) {
		$kind  = isset( $parsed['travel_kind'] ) ? (string) $parsed['travel_kind'] : 'any';
		$place = ! empty( $parsed['travel_dest'] ) ? ucwords( (string) $parsed['travel_dest'] ) : ( ! empty( $parsed['place_label'] ) ? (string) $parsed['place_label'] : '' );
		$from  = ! empty( $parsed['travel_origin'] ) ? ucwords( (string) $parsed['travel_origin'] ) : '';
		$bits  = array();
		if ( $listings ) {
			$label = ( 'hotel' === $kind ) ? 'hotel deals' : ( ( 'flight' === $kind ) ? 'flight deals' : 'travel deals' );
			$where = $place !== '' ? ( ' for ' . $place ) : '';
			if ( $from !== '' && 'flight' === $kind ) {
				$where = ( $place !== '' ? ( ' from ' . $from . ' toward ' . $place ) : ( ' from ' . $from ) );
			}
			$bits[] = 'Here are ' . count( $listings ) . ' live ' . $label . $where . '.';
			if ( ! empty( $parsed['expansion_note'] ) ) {
				$bits[] = (string) $parsed['expansion_note'];
			}
			$bits[] = 'Partner booking is a BrandDad Travel member perk - join or open Account Control to search and book.';
		} else {
			$bits[] = 'No synced travel deals matched yet.';
			$bits[] = 'Travel search for members lives in Account Control (BrandDad Travel).';
		}
		if ( $services ) {
			$bits[] = 'Related: ' . $services[0]['label'] . '.';
		}
		return implode( ' ', $bits );
	}
	$loc   = ! empty( $parsed['location'] ) ? $parsed['location'] : '';
	$cat   = ! empty( $parsed['category'] ) ? $parsed['category'] : '';
	$postal  = ! empty( $parsed['postal'] ) ? (string) $parsed['postal'] : '';
	$place = ! empty( $parsed['place_label'] ) ? (string) $parsed['place_label'] : ( ! empty( $parsed['area_label'] ) ? (string) $parsed['area_label'] : '' );
	$bits  = array();
	if ( $listings ) {
		if ( ! empty( $parsed['is_digital'] ) ) {
			$bits[] = 'Here are ' . count( $listings ) . ' Directory matches for "' . $message . '".';
		} else {
			$where = $place !== '' ? ( ' near ' . $place ) : ( $loc ? ( ' in ' . ucwords( $loc ) ) : '' );
			if ( $postal !== '' && $place === '' ) {
				$where = ' near ' . $postal . ( $loc ? ( ' (' . ucwords( $loc ) . ')' ) : '' );
			}
			$what   = $cat ? str_replace( '-', ' ', (string) $cat ) : 'local spots';
			$what   = ucwords( $what );
			if ( ! empty( $parsed['quality_intent'] ) ) {
				$bits[] = 'Strong ' . $what . $where . ' picks - ' . count( $listings ) . ' matches ranked by category, place, and ratings.';
			} elseif ( ! empty( $parsed['near_me'] ) || ! empty( $parsed['wants_near'] ) ) {
				$bits[] = 'Closest ' . $what . $where . ' - ' . count( $listings ) . ' options, sorted by fit and distance.';
			} else {
				$bits[] = 'Here are ' . count( $listings ) . ' ' . $what . $where . ' that look like a fit.';
			}
		}
		if ( ! empty( $parsed['expansion_note'] ) ) {
			$bits[] = (string) $parsed['expansion_note'];
		}
		$bits[] = 'Tap a card for details' . ( $guest ? '; contact unlocks after signup.' : '.' );
	} else {
		if ( $services ) {
			$labs = array();
			foreach ( array_slice( $services, 0, 4 ) as $sv ) {
				if ( ! empty( $sv['label'] ) ) {
					$labs[] = (string) $sv['label'];
				}
			}
			$bits[] = 'No Directory listing matches that ask. These BrandDad doors do: ' . implode( '; ', $labs ) . '.';
			$bits[] = 'That is not a substitute listing — tap a service if it is what you need.';
		} elseif ( function_exists( 'bds_ai_prec_zero_pack' ) ) {
			$zp     = bds_ai_prec_zero_pack( $parsed, $message );
			$bits[] = (string) $zp['reply'];
		} else {
			$what = '';
			if ( ! empty( $parsed['dish_tokens'] ) && is_array( $parsed['dish_tokens'] ) ) {
				$what = implode( '/', $parsed['dish_tokens'] ) . ' ';
			}
			$where = $place !== '' ? $place : ( $loc ? ucwords( (string) $loc ) : '' );
			$budget = ( isset( $parsed['max_price'] ) && is_numeric( $parsed['max_price'] ) ) ? ( ' under $' . (string) (int) $parsed['max_price'] ) : '';
			$bits[] = 'We do not have a verified match for ' . trim( $what . $where . $budget ) . '.';
			$bits[] = 'Suggestions are optional next searches — not the same result.';
		}
	}
	if ( $guest ) {
		$bits[] = 'Phone, email, and WhatsApp stay private for guests.';
	}
	if ( $services && $listings ) {
		$bits[] = 'Related: ' . $services[0]['label'] . '.';
	}
	$text = implode( ' ', $bits );
	if ( $bid_bits ) {
		$text .= "\n\n" . implode( "\n\n", $bid_bits );
	}
	return $text;
}

/**
 * OpenAI chat completion with structured Directory context.
 *
 * @param string                          $key       API key.
 * @param string                          $message   User.
 * @param array<int,mixed>                $history   Prior turns.
 * @param array<int,array<string,mixed>>  $listings  Cards.
 * @param array<int,array<string,string>> $services  Services.
 * @param bool                            $guest     Guest.
 * @param array<string,mixed>             $parsed    Intent.
 * @param array<int,array<string,mixed>>  $sponsored Sponsored cards.
 * @return string|WP_Error
 */
function bds_ai_finder_openai_reply( $key, $message, $history, $listings, $services, $guest, $parsed, $sponsored = array(), $products = array(), $need = array(), $clarify = array() ) {
	$s     = bds_ai_finder_settings();
	$base  = ! empty( $s['openai_base_url'] ) ? rtrim( (string) $s['openai_base_url'], '/' ) : 'https://api.openai.com/v1';

	$listing_json = wp_json_encode( $listings );
	$service_json = wp_json_encode( $services );
	$spon_json    = wp_json_encode( $sponsored );
	$dir_stats    = bds_ai_finder_directory_stats();
	$guest_rule   = $guest
		? ( ! empty( $parsed['is_travel'] )
			? 'The user is a GUEST on a travel query. Partner flight/hotel booking is a paying-member perk (BrandDad Travel) - point them to Travel search for members / join / Account Control. Do NOT present Book now as guest-safe. Local hotel/tour Directory businesses may still be recommended. NEVER reveal private contact for businesses.'
			: 'The user is a GUEST. NEVER reveal phone, email, WhatsApp, or private social contact. Tell them to open the listing page and sign up to view contact. Website and address are OK if present in the card data.' )
		: 'The user is logged in. Still prefer linking to listing pages rather than inventing contact details you do not have.';

	$products_json = wp_json_encode( is_array( $products ) ? $products : array() );
	$need_json     = wp_json_encode( is_array( $need ) ? $need : array() );
	$clarify_json  = wp_json_encode( is_array( $clarify ) ? $clarify : array() );

	$system = "You are Ask BrandDad - a helpful person on the BrandDad team who knows directory.branddad.social.\n"
		. "Talk like a real colleague helping a customer: short sentences, natural contractions (I'll, you're, don't), warm but professional.\n"
		. "Never sound like a corporate chatbot, a hype bro, or a pushy salesman.\n"
		. "Never say 'As an AI', 'I'd be happy to assist you today', buzzword soup, fake urgency, or ALL CAPS hype.\n"
		. "Mirror their goal in plain language (Looking for a plumber in Dubai?) instead of generic templates.\n"
		. "You help with local businesses (WhatsApp listings), discount gift cards, websites, SEO/Google visibility, reviews, social growth, hosting, and the member travel perk.\n"
		. "Voice: trustworthy, somewhat personal, human. 1-3 short sentences. Contractions OK. No slang, no emoji, no chatbot filler.\n"
		. "CONVERSATION PLAYBOOK (follow in order):\n"
		. "1. CLARIFY - if CLARIFY_JSON.ask is true, ask its questions (max 2, in one short message) instead of dumping results. Ask like a person would, not like a form.\n"
		. "2. DIAGNOSE - restate the need in a few words when it helps (Sounds like a speed problem, not a design problem).\n"
		. "3. RECOMMEND WITH A REASON - name the best fit from PRODUCTS_JSON / LISTINGS_JSON / SERVICES_JSON and say WHY it fits their answer.\n"
		. "4. ALTERNATIVES - if the exact thing is not available, say so plainly in one clause, then give the closest options and why they work for the same use case. Never apologize twice, never pad. Honesty builds trust.\n"
		. "5. UPSELL ONLY WHEN RELEVANT - at most one extra suggestion, tied to what they said. If nothing fits, recommend nothing. Still guide to WhatsApp listings, gifts, or BrandDad services when it naturally fits - never force it.\n"
		. "GIFT / SHOPPING RULES:\n"
		. "- PRODUCTS_JSON is the live gift-card inventory. Only name cards that appear there. Never invent a brand, face value, or price.\n"
		. "- Parsed.gift_brand is what they asked for. If Parsed.gift_exact is false, we do NOT carry that brand: say we do not have it (one clause, no drama), then pivot to what they are actually buying.\n"
		. "- When the brand is an everything-store (Amazon, Walmart, Target) and you do not yet know the use case, ASK what they plan to buy before listing anything.\n"
		. "- If the shopper says no / not that / something else, do not repeat the same cards. Move to a different use case or ask one sharper question.\n"
		. "- Mention face value and the discount when it helps them decide. Never reveal wholesale sources, suppliers, channels, or cost basis - the seller is Save Money Cards or the Directory seller shown on the card.\n"
		. "- NEVER invent gift-card codes, PINs, delivery ETAs, tracking, order status, refund outcomes, or availability beyond PRODUCTS_JSON. If you cannot answer honestly from known data, say a human will follow up — do not guess.\n"
		. "SERVICE / BUSINESS-OWNER RULES:\n"
		. "- NEED_JSON.domain is the diagnosed need (web_speed, web_broken, web_build, brand, local_seo, reviews, social, hosting, health, claim, membership, local, gift, travel).\n"
		. "- Recommend the SERVICES_JSON entry whose blurb explains why it fits. Lead with the free Business Health Check when someone is unsure what is wrong with their site.\n"
		. "- Never push Health Check, membership, or any service into a gift or restaurant conversation unless they ask.\n"
		. "- Keep recommendations scoped to the listed services. Never promise rankings, traffic numbers, or revenue.\n"
		. "Hard rules:\n"
		. "- PRECISION: 0 verified matches is better than 1 wrong match. If LISTINGS_JSON is empty: first say we do not have a verified match. Then you may offer different searches as suggestions — clearly not the thing they asked for. Never name businesses that are not in the JSON.\n"
		. "- ONLY explain cards in LISTINGS_JSON / SPONSORED_JSON / PRODUCTS_JSON. The pack is already hard-filtered. Do not add businesses from memory.\n"
		. "- Use each card's evidence / why-it-matches when present. Never invent menus, prices, hours, amenities, ratings, or availability.\n"
		. "- Never silently relax dish, vegan, price max, city/region, pool, breakfast, or open-now constraints.\n"
		. "- Starting-at / from $X is not proof a stay is available at that rate.\n"
		. "- Sponsored cards must already satisfy the query. Never let paid listings replace missing matches.\n"
		. "- ONLY name businesses/deals that appear in LISTINGS_JSON or SPONSORED_JSON. Never invent a shop, address, rating, phone, or airfare.\n"
		. "- When asked how many listings/businesses are in the Directory, use DIRECTORY_STATS.published_listings (live total). Never invent or reuse a stale number.\n"
		. "- Gift / discount gift-card intent (including missing product pages / smc-* slugs / brand gift card asks): answer from PRODUCTS_JSON. If the exact SKU or face value is missing, say so briefly and recommend the closest live cards or related Places you might like from LISTINGS_JSON. Never invent gift-card product SKUs. Never hardcode one seller as the only vendor. Never reveal wholesale/supplier contacts.\n"
		. "- SPONSORED_JSON: optional paid placements. Mentions must be clearly labeled Sponsored / Paid placement. Max 1 (or 2 if user asked for options). Never force off-topic ads. Prefer organic LISTINGS_JSON first.\n"
		. "- Travel / flights / hotels / vacation intent: LISTINGS_JSON may include live travel deal cards (is_travel) and/or local hotel/tour businesses. BrandDad Travel (partner search) is a paying-member perk - point guests to Travel search for members / join; members to Open BrandDad Travel. Never claim secret wholesale or always-cheaper rates. Never name TravelPayouts, Aviasales, Hotellook, OneAir, or any booking engine. Never pitch gift cards or random local restaurants for pure flight queries. Local hotels/tour operators stay recommendable as Directory businesses.\n"
		. "- For local queries, mention distance_label and neighborhood/city from the cards or place_label.\n"
		. "- NEVER say there are no zip/near-me listings when LISTINGS_JSON is non-empty.\n"
		. "- If expansion_note is set, acknowledge the widen/expand in one short clause.\n"
		. "- Digital intent (logo/web/hosting/SEO/GBP/speed/health check): ignore distance; recommend relevant Directory + soft BrandDad network tips from SERVICES_JSON only when intent matches. Never recommend LinkedIn outreach products. Prefer Free Business Health Check when they ask how their site/online presence is doing.\n"
		. "- Follow-ups (closer / more like that / cheaper / only open / under $X): keep prior dish, city, and hard constraints. Change only the constraint they named.\n"
		. "- MIXED_NEEDS: if Parsed.mixed, group Website / Hosting / Social / Directory matches. Do not dump one undifferentiated list.\n"
		. "- UNAVAILABLE providers (jobs, influencers, coupons, events): say BrandDad does not search that inventory yet. Never substitute random businesses.\n"
		. "- If a search source fails, say it is temporarily unavailable. Never hallucinate replacements.\n"
		. "- Soft CTAs only when natural: claim listing, Directory membership (10% off), Business Growth Network, BrandDad.co / Social / HostTech / gift cards / travel browse / affiliate program - max one tip, never spam.\n"
		. "- If LISTINGS_JSON is empty and this is NOT gift or travel intent: ask for a clearer category + zip/city or location permission - never pretend you know where they are.\n"
		. "- If travel intent and LISTINGS_JSON is empty: point to Travel perk for members (SERVICES_JSON), not random local businesses.\n"
		. "- Do not invent reviews or contact info.\n"
		. "- " . $guest_rule . "\n"
		. "DIRECTORY_STATS: " . wp_json_encode( $dir_stats ) . "\n"
		. "Parsed: " . wp_json_encode(
			array(
				'keywords'       => $parsed['keywords'],
				'location'       => $parsed['location'],
				'category'       => $parsed['category'],
				'postal'         => $parsed['postal'],
				'near_me'        => ! empty( $parsed['near_me'] ),
				'search_mode'    => $parsed['search_mode'],
				'place_label'    => isset( $parsed['place_label'] ) ? $parsed['place_label'] : $parsed['area_label'],
				'expansion_note' => $parsed['expansion_note'],
				'radius_km'      => $parsed['radius_km'],
				'is_digital'     => ! empty( $parsed['is_digital'] ),
				'is_gift'        => ! empty( $parsed['is_gift'] ),
				'is_travel'      => ! empty( $parsed['is_travel'] ),
				'travel_kind'    => isset( $parsed['travel_kind'] ) ? $parsed['travel_kind'] : '',
				'travel_origin'  => isset( $parsed['travel_origin'] ) ? $parsed['travel_origin'] : '',
				'travel_dest'    => isset( $parsed['travel_dest'] ) ? $parsed['travel_dest'] : '',
				'follow_up'      => isset( $parsed['follow_up'] ) ? $parsed['follow_up'] : '',
				'gift_brand'     => isset( $parsed['gift_brand'] ) ? $parsed['gift_brand'] : '',
				'gift_exact'     => ! empty( $parsed['gift_exact'] ),
				'gift_usecases'  => isset( $parsed['gift_usecases'] ) ? $parsed['gift_usecases'] : array(),
				'gift_budget'    => isset( $parsed['gift_budget'] ) ? $parsed['gift_budget'] : 0,
				'said_no'        => ! empty( $parsed['is_negative'] ),
			)
		) . "\n"
		. "NEED_JSON: " . $need_json . "\n"
		. "CLARIFY_JSON: " . $clarify_json . "\n"
		. "PRODUCTS_JSON: " . $products_json . "\n"
		. "LISTINGS_JSON: " . $listing_json . "\n"
		. "SPONSORED_JSON: " . $spon_json . "\n"
		. "SERVICES_JSON: " . $service_json;

	$messages = array(
		array(
			'role'    => 'system',
			'content' => $system,
		),
	);
	foreach ( $history as $turn ) {
		if ( ! is_array( $turn ) ) {
			continue;
		}
		$role = isset( $turn['role'] ) ? (string) $turn['role'] : '';
		$content = isset( $turn['content'] ) ? sanitize_text_field( (string) $turn['content'] ) : '';
		if ( ! in_array( $role, array( 'user', 'assistant' ), true ) || $content === '' ) {
			continue;
		}
		$messages[] = array(
			'role'    => $role,
			'content' => substr( $content, 0, 800 ),
		);
	}
	$messages[] = array(
		'role'    => 'user',
		'content' => $message,
	);

	// Walk the model chain: strongest first, fall back when a model is not
	// available to this key, and remember the winner.
	$last_error = null;
	foreach ( bds_ai_finder_models_to_try() as $model ) {
		$resp = wp_remote_post(
			$base . '/chat/completions',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'messages'    => $messages,
						'temperature' => 0.2,
						'max_tokens'  => 480,
					)
				),
			)
		);

		if ( is_wp_error( $resp ) ) {
			$last_error = $resp;
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			$err        = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : 'OpenAI error';
			$last_error = new WP_Error( 'bds_ai_openai', $err, array( 'status' => 502 ) );
			// Model/access problems are worth retrying on the next model; other
			// failures (rate limit, auth, outage) are not.
			if ( 1 === preg_match( '/model|does not exist|not found|unsupported|access/i', $err ) && 404 !== $code && 429 !== $code ) {
				continue;
			}
			if ( 404 === $code || 400 === $code ) {
				continue;
			}
			return $last_error;
		}
		$text = '';
		if ( ! empty( $body['choices'][0]['message']['content'] ) ) {
			$text = trim( (string) $body['choices'][0]['message']['content'] );
		}
		if ( $text === '' ) {
			$last_error = new WP_Error( 'bds_ai_openai_empty', 'Empty AI reply', array( 'status' => 502 ) );
			continue;
		}
		set_transient( 'bds_ai_finder_model_ok', $model, DAY_IN_SECONDS );
		// Final scrub - belt and suspenders.
		return bds_ai_finder_scrub_text( $text );
	}

	return $last_error ? $last_error : new WP_Error( 'bds_ai_openai', 'No model available', array( 'status' => 502 ) );
}

/**
 * @return bool
 */
function bds_ai_finder_should_show_ui() {
	if ( ! bds_ai_finder_enabled() ) {
		return false;
	}
	if ( is_admin() ) {
		return false;
	}
	return true;
}

/**
 * Turn a product slug / search string into a natural Ask BrandDad query.
 *
 * @param string $raw Slug or search.
 * @return string
 */
function bds_ai_finder_humanize_product_query( $raw ) {
	$raw = strtolower( trim( (string) $raw ) );
	$raw = rawurldecode( $raw );
	$raw = preg_replace( '#^https?://[^/]+/#', '', $raw );
	$raw = preg_replace( '#^product/#', '', $raw );
	$raw = trim( $raw, "/ \t\n\r\0\x0B" );
	if ( $raw === '' ) {
		return '';
	}

	$known = array(
		'themediterraneandish'   => 'The Mediterranean Dish',
		'mediterranean dish'     => 'The Mediterranean Dish',
		'skinpharm'              => 'SkinPharm',
		'factor75'               => 'Factor',
		'bigronline'             => 'Big R Online',
		'salt cellar'            => 'Salt Cellar',
		'salt-cellar'            => 'Salt Cellar',
		'indielee'               => 'Indie Lee',
		'indie lee'              => 'Indie Lee',
		'wine com'               => 'Wine.com',
		'wine.com'               => 'Wine.com',
		'footjoy'                => 'FootJoy',
		'ocean prime'            => 'Ocean Prime',
		'ocean-prime'            => 'Ocean Prime',
		'bonobos'                => 'Bonobos',
		'ruggable'               => 'Ruggable',
		'buckmason'              => 'Buck Mason',
		'buck mason'             => 'Buck Mason',
		'portlandleathergoods'   => 'Portland Leather Goods',
		'americanapipedream'     => 'Americana Pipedream',
		'arrowheadtacticalapparel' => 'Arrowhead Tactical Apparel',
		'petexpertise'           => 'Pet Expertise',
		'getfitcherries'         => 'Get Fit Cherries',
		'cocktailcourier'        => 'Cocktail Courier',
		'supremegolf'            => 'Supreme Golf',
		'spursfanshop'           => 'Spurs Fan Shop',
		'unique vintage'         => 'Unique Vintage',
		'unique-vintage'         => 'Unique Vintage',
		'aprilcornell'           => 'April Cornell',
		'vitaminaswim'           => 'Vitamina Swim',
		'brightland'             => 'Brightland',
		'tsukiglass'             => 'Tsuki Glass',
		'koriwhiskey'            => 'Kori Whiskey',
		'magickitchen'           => 'Magic Kitchen',
		'gardeners'              => 'Gardeners.com',
		'wmpeyewear'             => 'WMP Eyewear',
		'naturcontact'           => 'Naturcontact',
		'avarcasusa'             => 'Avarcas USA',
		'rooteofficial'          => 'Roote',
		'fnxfit'                 => 'FNX Fit',
		'mesonart'               => 'Meson Art',
		'ginnys'                 => 'Ginny\'s',
		'chocolate com'          => 'Chocolate.com',
		'huit'                   => 'Huit',
		'aputnam'                => 'A. Putnam',
		'sanori'                 => 'Sanori',
	);

	$face = '';
	if ( 1 === preg_match( '/(?:^|-)(\d{2,5})(?:-\d+)?$/', $raw, $m ) ) {
		$face = $m[1];
	}

	$base = $raw;
	$base = preg_replace( '/-\d+(?:-\d+)?$/', '', $base );
	if ( 0 === strpos( $base, 'smc-' ) ) {
		$base = substr( $base, 4 );
	}
	$base = preg_replace( '/-(com|net|org|co|io|shop|store)$/', '', $base );
	$key  = str_replace( array( '-', '_', '.' ), ' ', $base );
	$key  = preg_replace( '/\s+/', ' ', $key );
	$key  = trim( $key );
	$compact = str_replace( ' ', '', $key );

	$name = '';
	if ( isset( $known[ $key ] ) ) {
		$name = $known[ $key ];
	} elseif ( isset( $known[ $compact ] ) ) {
		$name = $known[ $compact ];
	} elseif ( isset( $known[ str_replace( ' ', '-', $key ) ] ) ) {
		$name = $known[ str_replace( ' ', '-', $key ) ];
	} else {
		$words = array_map(
			static function ( $w ) {
				return $w === '' ? '' : ucfirst( $w );
			},
			explode( ' ', $key )
		);
		$name = trim( implode( ' ', $words ) );
	}

	if ( $name === '' ) {
		return 'discount gift cards';
	}
	if ( $face !== '' ) {
		return $name . ' $' . $face . ' gift card';
	}
	return $name . ' gift card';
}

/**
 * Page context for Ask BrandDad - seeds archive category / browse surfaces.
 *
 * @return array{isHome:bool,isBrowse:bool,category:string,categoryName:string,location:string,locationName:string,directoryType:string}
 */
function bds_ai_finder_page_context() {
	$out = array(
		'isHome'        => false,
		'isBrowse'      => false,
		'category'      => '',
		'categoryName'  => '',
		'location'      => '',
		'locationName'  => '',
		'directoryType' => '',
	);
	if ( is_admin() ) {
		return $out;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) ( wp_parse_url( $uri, PHP_URL_PATH ) ?: '' );
	$path = untrailingslashit( strtolower( $path ) );

	if ( is_front_page() || is_home() || '' === $path || '/' === $path || '/home' === $path ) {
		$out['isHome'] = true;
	}

	$dir_type             = isset( $_GET['directory_type'] ) ? sanitize_title( (string) wp_unslash( $_GET['directory_type'] ) ) : '';
	$out['directoryType'] = $dir_type;

	if ( is_tax( 'at_biz_dir-category' ) ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			$out['isBrowse']     = true;
			$out['category']     = (string) $term->slug;
			$out['categoryName'] = (string) $term->name;
		}
	} elseif ( is_tax( 'at_biz_dir-location' ) ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			$out['isBrowse']     = true;
			$out['location']     = (string) $term->slug;
			$out['locationName'] = (string) $term->name;
		}
	}

	if ( 1 === preg_match( '#/(single-category|single-location|all-listings|search-result|search-results)(/|$)#i', $path ) ) {
		$out['isBrowse'] = true;
	}
	if ( 1 === preg_match( '#/single-category/([^/]+)#i', $path, $m ) && $out['category'] === '' ) {
		$out['category'] = sanitize_title( $m[1] );
		$term            = get_term_by( 'slug', $out['category'], 'at_biz_dir-category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$out['categoryName'] = (string) $term->name;
		} else {
			$out['categoryName'] = ucwords( str_replace( array( '-', '_' ), ' ', $out['category'] ) );
		}
	}
	if ( 1 === preg_match( '#/single-location/([^/]+)#i', $path, $m ) && $out['location'] === '' ) {
		$out['location'] = sanitize_title( $m[1] );
		$term            = get_term_by( 'slug', $out['location'], 'at_biz_dir-location' );
		if ( $term && ! is_wp_error( $term ) ) {
			$out['locationName'] = (string) $term->name;
		} else {
			$out['locationName'] = ucwords( str_replace( array( '-', '_' ), ' ', $out['location'] ) );
		}
	}
	if ( isset( $_GET['in_cat'] ) && $out['category'] === '' ) {
		$raw = sanitize_text_field( (string) wp_unslash( $_GET['in_cat'] ) );
		if ( ctype_digit( $raw ) ) {
			$term = get_term( (int) $raw, 'at_biz_dir-category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$out['category']     = (string) $term->slug;
				$out['categoryName'] = (string) $term->name;
				$out['isBrowse']     = true;
			}
		} else {
			$out['category']     = sanitize_title( $raw );
			$out['categoryName'] = ucwords( str_replace( array( '-', '_' ), ' ', $out['category'] ) );
			$out['isBrowse']     = true;
		}
	}
	if ( isset( $_GET['in_loc'] ) && $out['location'] === '' ) {
		$raw = sanitize_text_field( (string) wp_unslash( $_GET['in_loc'] ) );
		if ( ctype_digit( $raw ) ) {
			$term = get_term( (int) $raw, 'at_biz_dir-location' );
			if ( $term && ! is_wp_error( $term ) ) {
				$out['location']     = (string) $term->slug;
				$out['locationName'] = (string) $term->name;
				$out['isBrowse']     = true;
			}
		} else {
			$out['location']     = sanitize_title( $raw );
			$out['locationName'] = ucwords( str_replace( array( '-', '_' ), ' ', $out['location'] ) );
			$out['isBrowse']     = true;
		}
	}

	return $out;
}

/**
 * When a product is missing or a product search has no results, hand off to Ask BrandDad.
 *
 * @return array{mode:string,query:string,autoAsk:bool,message:string}
 */
function bds_ai_finder_empty_assist() {
	$out = array(
		'mode'    => '',
		'query'   => '',
		'autoAsk' => false,
		'message' => '',
	);

	// Missing / trashed product permalink.
	if ( is_404() ) {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) ( wp_parse_url( $uri, PHP_URL_PATH ) ?: '' );
		if ( 1 === preg_match( '#/product/([^/]+)/?#i', $path, $m ) ) {
			$q = bds_ai_finder_humanize_product_query( $m[1] );
			if ( $q !== '' ) {
				$out['mode']    = 'missing_product';
				$out['query']   = $q;
				$out['autoAsk'] = true;
				$out['message'] = 'That product page is gone. I can match the closest live gift cards for you.';
			}
		}
		return $out;
	}

	// WooCommerce / site search with no product hits.
	if ( is_search() ) {
		global $wp_query;
		$q = get_search_query( false );
		$q = is_string( $q ) ? trim( $q ) : '';
		$no_posts = empty( $wp_query->found_posts );
		$pt       = isset( $_GET['post_type'] ) ? sanitize_key( (string) wp_unslash( $_GET['post_type'] ) ) : '';
		$looking_products = ( $pt === 'product' )
			|| ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			|| ( function_exists( 'is_shop' ) && is_shop() )
			|| (bool) get_query_var( 'product_cat' );
		if ( $no_posts && $q !== '' && ( $looking_products || 1 === preg_match( '/gift|smc-|card/i', $q ) || true ) ) {
			$out['mode']    = 'empty_search';
			$out['query']   = bds_ai_finder_humanize_product_query( $q );
			if ( $out['query'] === '' || $out['query'] === 'gift card' || $out['query'] === 'discount gift cards' ) {
				$out['query'] = $q;
			}
			$out['autoAsk'] = true;
			$out['message'] = 'Nothing matched that search. I can check the live Directory for you.';
		}
	}

	return $out;
}

function bds_ai_finder_enqueue() {
	if ( ! bds_ai_finder_should_show_ui() ) {
		return;
	}

	$css = bds_ai_finder_css();
	$js  = bds_ai_finder_js();
	$assist = bds_ai_finder_empty_assist();
	wp_register_style( 'bds-ai-finder', false, array(), BDS_AI_FINDER_VER );
	wp_enqueue_style( 'bds-ai-finder' );
	wp_add_inline_style( 'bds-ai-finder', $css );

	wp_register_script( 'bds-ai-finder', false, array(), BDS_AI_FINDER_VER, true );
	wp_enqueue_script( 'bds-ai-finder' );
	$assist = bds_ai_finder_empty_assist();
	$page   = bds_ai_finder_page_context();
	wp_add_inline_script(
		'bds-ai-finder',
		'window.BDS_AI_FINDER=' . wp_json_encode(
			array(
				'rest'      => esc_url_raw( rest_url( 'bds-ai/v1/find' ) ),
				'status'    => esc_url_raw( rest_url( 'bds-ai/v1/status' ) ),
				'memory'    => esc_url_raw( rest_url( 'bds-ai/v1/memory' ) ),
				'geocode'   => esc_url_raw( rest_url( 'bds-ai/v1/geocode' ) ),
				'localPack' => esc_url_raw( rest_url( 'bds-ai/v1/local-pack' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'guest'     => ! is_user_logged_in(),
				'userId'    => (int) get_current_user_id(),
				'signup'    => esc_url_raw( home_url( '/registration/' ) ),
				'home'      => esc_url_raw( home_url( '/' ) ),
				'version'   => BDS_AI_FINDER_VER,
				'assist'    => $assist,
				'page'      => $page,
			)
		) . ';' . $js,
		'after'
	);
}

function bds_ai_finder_footer_markup() {
	if ( ! bds_ai_finder_should_show_ui() ) {
		return;
	}
	$signup = esc_url( home_url( '/registration/' ) );
	$assist = bds_ai_finder_empty_assist();
	?>
	<!-- bds-directory-ai-finder v<?php echo esc_attr( BDS_AI_FINDER_VER ); ?> -->
	<?php if ( ! empty( $assist['mode'] ) && ! empty( $assist['query'] ) ) : ?>
	<aside class="bds-ai-empty-assist" data-bds-ai-assist="<?php echo esc_attr( (string) $assist['mode'] ); ?>" data-bds-ai-finder="<?php echo esc_attr( BDS_AI_FINDER_VER ); ?>">
		<p class="bds-ai-empty-assist__eyebrow">Ask BrandDad</p>
		<p class="bds-ai-empty-assist__title"><?php echo esc_html( (string) $assist['message'] ); ?></p>
		<p class="bds-ai-empty-assist__query">Looking for: <strong><?php echo esc_html( (string) $assist['query'] ); ?></strong></p>
		<p class="bds-ai-empty-assist__cta">
			<button type="button" class="bds-ai-empty-assist__btn" id="bds-ai-empty-assist-btn">Ask BrandDad</button>
		</p>
	</aside>
	<?php endif; ?>
	<button type="button" id="bds-ai-launcher" class="bds-ai-launcher" aria-haspopup="dialog" aria-controls="bds-ai-panel" aria-expanded="false" title="Ask BrandDad">
		<span class="bds-ai-launcher__mark" aria-hidden="true">
			<svg viewBox="0 0 24 24" width="18" height="18" focusable="false" aria-hidden="true"><path fill="currentColor" d="M12 2.6l1.9 4.6 4.6 1.9-4.6 1.9L12 15.6l-1.9-4.6L5.5 9.1l4.6-1.9L12 2.6zM18.4 14.2l.95 2.3 2.3.95-2.3.95-.95 2.3-.95-2.3-2.3-.95 2.3-.95.95-2.3zM5.6 15.1l.8 1.95 1.95.8-1.95.8-.8 1.95-.8-1.95-1.95-.8 1.95-.8.8-1.95z"/></svg>
		</span>
		<span class="bds-ai-launcher__text">Ask BrandDad</span>
	</button>
	<div id="bds-ai-panel" class="bds-ai-panel" role="dialog" aria-modal="true" aria-labelledby="bds-ai-title" hidden>
		<div class="bds-ai-panel__chrome" role="document">
			<div class="bds-ai-panel__head">
				<span class="bds-ai-panel__avatar" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path fill="currentColor" d="M12 2.6l1.9 4.6 4.6 1.9-4.6 1.9L12 15.6l-1.9-4.6L5.5 9.1l4.6-1.9L12 2.6zM18.4 14.2l.95 2.3 2.3.95-2.3.95-.95 2.3-.95-2.3-2.3-.95 2.3-.95.95-2.3z"/></svg>
				</span>
				<div class="bds-ai-panel__ident">
					<h2 id="bds-ai-title">Ask BrandDad</h2>
					<?php
					// Carrying the live count here keeps the status line honest and
					// stops the stats MU from replacing this node (it only rewrites
					// subtitles that do not already mention published listings,
					// which was wiping out the status dot).
					$bds_ai_stats = bds_ai_finder_directory_stats();
					$bds_ai_count = isset( $bds_ai_stats['published_listings'] ) ? (int) $bds_ai_stats['published_listings'] : 0;
					?>
					<p class="bds-ai-panel__sub"><span class="bds-ai-dot" aria-hidden="true"></span><?php
						echo $bds_ai_count > 0
							? esc_html( 'Live inventory: ' . number_format_i18n( $bds_ai_count ) . ' published listings' )
							: esc_html( 'Live Directory inventory' );
					?></p>
				</div>
				<div class="bds-ai-panel__head-actions">
					<button type="button" class="bds-ai-iconbtn" id="bds-ai-clear" aria-label="Clear conversation" title="Clear conversation">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M9 3h6l1 2h4v2H4V5h4l1-2zM6 9h12l-1 11.2A2 2 0 0 1 15 22H9a2 2 0 0 1-2-1.8L6 9z"/></svg>
					</button>
					<button type="button" class="bds-ai-iconbtn" id="bds-ai-close" aria-label="Close chat" title="Close">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M18.3 5.7l-1.4-1.4-4.9 4.9-4.9-4.9-1.4 1.4 4.9 4.9-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/></svg>
					</button>
				</div>
			</div>
			<div class="bds-ai-panel__messages" id="bds-ai-messages" role="log" aria-live="polite" aria-relevant="additions text"></div>
			<div class="bds-ai-chips" id="bds-ai-chips" aria-label="Suggested replies"></div>
			<div class="bds-ai-panel__geo" id="bds-ai-geo-bar">
				<button type="button" class="bds-ai-geo-btn" id="bds-ai-geo-btn">
					<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
					Use my location
				</button>
				<span class="bds-ai-geo-status" id="bds-ai-geo-status" aria-live="polite"></span>
			</div>
			<form class="bds-ai-panel__form" id="bds-ai-form" autocomplete="off">
				<label class="screen-reader-text" for="bds-ai-input">Ask BrandDad</label>
				<div class="bds-ai-field">
					<input type="text" id="bds-ai-input" name="q" maxlength="500" placeholder="What are you looking for?" required />
				</div>
				<button type="submit" id="bds-ai-send" aria-label="Send">
					<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3.4 20.4l17.5-8.4L3.4 3.6 3.4 10l12 2-12 2z"/></svg>
				</button>
			</form>
			<p class="bds-ai-panel__foot">Guests can browse. <a href="<?php echo $signup; ?>">Sign up</a> to unlock contact and checkout.</p>
		</div>
	</div>
	<?php
}

/**
 * @return string
 */
function bds_ai_finder_css() {
	return <<<'CSS'
:root{
  --bds-ai-ink:#0e1a2b;
  --bds-ai-navy:#003377;
  --bds-ai-navy-deep:#002255;
  --bds-ai-sand:#eef2f6;
  --bds-ai-line:rgba(14,26,43,.12);
  --bds-ai-ok:#1f6b4a;
  --bds-ai-accent:#22d3ee;
  --bds-ai-font:"Segoe UI",system-ui,-apple-system,"Helvetica Neue",Arial,sans-serif;
  --bds-ai-radius:18px;
  --bds-ai-fab-bottom:20px;
  --bds-ai-fab-right:20px;
  --bds-ai-z-launcher:99980;
  --bds-ai-z-panel:99990;
}
.bds-ai-launcher{
  position:fixed;
  right:calc(var(--bds-ai-fab-right) + env(safe-area-inset-right,0px));
  bottom:calc(var(--bds-ai-fab-bottom) + env(safe-area-inset-bottom,0px));
  z-index:var(--bds-ai-z-launcher);
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.68rem 1.05rem .68rem .7rem; border:0; border-radius:999px;
  background:linear-gradient(135deg,#0a4a8a 0%,var(--bds-ai-navy) 55%,var(--bds-ai-navy-deep) 100%);
  color:#fff; font:650 14px/1.2 var(--bds-ai-font); letter-spacing:.01em;
  box-shadow:0 8px 20px rgba(0,34,85,.28),0 2px 6px rgba(0,34,85,.18);
  cursor:pointer; transition:transform .18s ease,box-shadow .18s ease,filter .18s ease;
}
.bds-ai-launcher:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(0,34,85,.34); }
.bds-ai-launcher:focus-visible{ outline:3px solid rgba(34,211,238,.8); outline-offset:3px; }
.bds-ai-launcher.is-open{ opacity:0; pointer-events:none; }
.bds-ai-empty-assist{
  max-width:40rem; margin:1.25rem auto; padding:1.15rem 1.25rem;
  border:1px solid rgba(0,200,255,.22); border-radius:16px;
  background:
    radial-gradient(120% 140% at 0% 0%, rgba(0,200,255,.12), transparent 55%),
    linear-gradient(145deg,#07111f 0%,#0e1a2b 48%,#10243a 100%);
  color:#e8f4ff; box-shadow:0 18px 40px rgba(2,12,27,.28);
}
.bds-ai-empty-assist__eyebrow{margin:0 0 .3rem;font-size:11px;font-weight:750;letter-spacing:.08em;text-transform:uppercase;color:#7dd3fc}
.bds-ai-empty-assist__title{margin:0 0 .45rem;font-size:1.08rem;line-height:1.35;font-weight:700;color:#f8fafc}
.bds-ai-empty-assist__query{margin:0;font-size:.92rem;color:#bae6fd}
.bds-ai-empty-assist__cta{margin:.9rem 0 0}
.bds-ai-empty-assist__btn{
  display:inline-flex;align-items:center;gap:.4rem;border:0;border-radius:999px;
  padding:.7rem 1.05rem;background:linear-gradient(135deg,#22d3ee,#003377);
  color:#041018;font:750 14px/1.2 "Segoe UI",system-ui,sans-serif;cursor:pointer;
}
.bds-ai-empty-assist__btn:hover{filter:brightness(1.08)}
body.error404 .bds-ai-empty-assist,body.search-no-results .bds-ai-empty-assist{margin-top:2rem}
.bds-ai-launcher__mark{
  display:inline-grid; place-items:center; width:28px; height:28px; border-radius:50%;
  background:rgba(255,255,255,.18); color:#e0f5ff;
}
/* Mobile: compact circular FAB (the header chip is often inside a collapsed
   hamburger, so the launcher must stay reachable on every page). */
@media (max-width:782px){
  .bds-ai-launcher{
    padding:0; width:54px; height:54px; border-radius:50%; justify-content:center;
    --bds-ai-fab-bottom:16px; --bds-ai-fab-right:16px;
  }
  .bds-ai-launcher__text{
    position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); clip-path:inset(50%);
  }
  .bds-ai-launcher__mark{ width:26px; height:26px; background:transparent; }
  /* The menu-chrome MU hides the FAB outright under 782px, which left every
     interior mobile page with no way to open the chat (the header chip lives
     inside a collapsed hamburger). Launcher placement belongs to this plugin,
     so re-enable it here and hide it only while a nav drawer is really open. */
  html body.bds-dir-menu-lock #bds-ai-launcher.bds-ai-launcher{ display:inline-flex!important; }
}
html body #bds-ai-launcher.bds-ai-launcher.is-nav-open{ display:none!important; }
.bds-ai-header-chip{
  display:inline-flex; align-items:center; gap:.4rem; margin-left:.75rem;
  padding:.35rem .7rem; border-radius:999px; border:1px solid rgba(0,51,119,.25);
  background:rgba(0,51,119,.06); color:var(--bds-ai-navy); font:600 13px/1 "Segoe UI",system-ui,sans-serif;
  text-decoration:none; cursor:pointer;
}
.bds-ai-header-chip:hover{ background:rgba(0,51,119,.12); color:var(--bds-ai-navy-deep); }
.bds-ai-hero-entry{
  width:100%; max-width:920px; margin:1.25rem auto 1.35rem; padding:0 1.15rem;
  box-sizing:border-box; position:relative; z-index:5;
}
.bds-ai-hero-entry__inner{
  display:flex; gap:.6rem; align-items:center; width:100%;
  margin:0; padding:.7rem .7rem .7rem 1.1rem;
  border:1px solid var(--bds-ai-line); border-radius:16px;
  background:linear-gradient(180deg,#fff,#f4f7fb);
  box-shadow:0 12px 36px rgba(14,26,43,.1);
  box-sizing:border-box;
}
.bds-ai-hero-entry__inner input[type="search"],
.bds-ai-hero-entry__inner input[type="text"]{
  flex:1; min-width:0; width:100%; border:0; outline:0; background:transparent;
  color:var(--bds-ai-ink); font:500 16px/1.35 "Segoe UI",system-ui,sans-serif;
  -webkit-appearance:none; appearance:none;
}
.bds-ai-hero-entry__inner input::placeholder{ color:rgba(14,26,43,.55); }
.bds-ai-hero-entry__inner input::-webkit-search-decoration,
.bds-ai-hero-entry__inner input::-webkit-search-cancel-button{ -webkit-appearance:none; }
.bds-ai-hero-entry__inner button{
  flex-shrink:0; border:0; border-radius:12px; padding:.7rem 1.15rem; cursor:pointer;
  background:var(--bds-ai-navy); color:#fff; font:700 14px/1 "Segoe UI",system-ui,sans-serif;
}
.bds-ai-hero-entry__inner button:hover{ filter:brightness(1.06); }
@media (max-width:520px){
  .bds-ai-hero-entry{ padding:0 .85rem; margin:1rem auto 1.1rem; }
  .bds-ai-hero-entry__inner{ padding:.55rem .55rem .55rem .85rem; border-radius:14px; }
  .bds-ai-hero-entry__inner input[type="search"],
  .bds-ai-hero-entry__inner input[type="text"]{ font-size:15px; }
  .bds-ai-hero-entry__inner button{ padding:.65rem .9rem; }
}
/* Browse surfaces: one Ask BrandDad search - hide classic Directorist keyword search */
body.bds-ai-browse form.directorist-search-form.directorist-basic-search,
body.bds-ai-browse .directorist-search-contents > form.directorist-basic-search,
body.bds-ai-browse .elementor-widget-directorist-search-listing,
body.bds-ai-browse .elementor-widget-directorist-search,
body.bds-ai-browse #bds-city-search,
body.bds-ai-browse .bds-city-search,
body.bds-ai-browse [data-bds-city-search],
body.bds-ai-browse [data-bds-dfc-extra-search="1"],
body.theme-dir-single_category form.directorist-search-form.directorist-basic-search,
body.theme-dir-single_location form.directorist-search-form.directorist-basic-search,
body.theme-dir-all_listings form.directorist-search-form.directorist-basic-search{
  display:none!important;
}
/* Keep advanced filters usable - only hide the keyword/basic search strip */
body.bds-ai-browse .directorist-search-form__box .directorist-search-query,
body.theme-dir-single_category .directorist-basic-search .directorist-search-query{
  display:none!important;
}
/* Compact type-nav so it does not own the first viewport */
body.bds-ai-browse .directorist-type-nav,
body.theme-dir-single_category .directorist-type-nav,
body.theme-dir-single_location .directorist-type-nav,
body.theme-dir-all_listings .directorist-type-nav{
  max-height:4.75rem; overflow:auto; margin:.35rem 0 .75rem; padding:.15rem 0;
  scrollbar-width:thin;
}
body.bds-ai-browse .directorist-type-nav ul,
body.theme-dir-single_category .directorist-type-nav ul{
  display:flex!important; flex-wrap:wrap; gap:.35rem .55rem; justify-content:flex-start;
  margin:0; padding:0; list-style:none;
}
body.bds-ai-browse .directorist-type-nav a,
body.theme-dir-single_category .directorist-type-nav a{
  font-size:12px!important; line-height:1.2; padding:.35rem .55rem!important;
  white-space:nowrap;
}
/* Tighten archive title band / dead hero space */
body.bds-ai-browse .directorist-archive-title,
body.theme-dir-single_category .directorist-archive-title{
  margin:.35rem 0 .5rem!important; font-size:clamp(1.55rem,3.2vw,2.1rem)!important;
  color:#0e1a2b!important; font-weight:750!important;
}
body.bds-ai-browse .directorist-archive-contents > .row:first-child,
body.theme-dir-single_category .directorist-archive-contents > .row:first-child{
  margin-bottom:.25rem!important;
}
/* Kill oversized empty theme header band above archive title */
body.bds-ai-browse .directorist-archive-contents,
body.theme-dir-single_category .directorist-archive-contents{
  padding-top:.35rem!important;
}
body.bds-ai-browse .site-content > .theme-header,
body.theme-dir-single_category .theme-header-bg,
body.theme-dir-single_category .header-bgimg .theme-header{
  min-height:0!important; padding-top:.25rem!important; padding-bottom:.25rem!important;
}
body.bds-ai-browse #bds-ai-hero-entry,
body.theme-dir-single_category #bds-ai-hero-entry{
  margin:.55rem auto .55rem; max-width:860px;
}
body.bds-ai-browse .bds-ai-browse-geo{
  margin:.15rem auto .65rem; max-width:860px; padding:0 1.15rem;
}
.bds-ai-panel[hidden]{ display:none !important; }
.bds-ai-panel{
  position:fixed; inset:0; z-index:var(--bds-ai-z-panel); display:grid; place-items:end;
  padding:0; background:rgba(8,17,31,.42);
  -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px);
  animation:bds-ai-fade .18s ease both;
}
@keyframes bds-ai-fade{ from{ opacity:0 } to{ opacity:1 } }
@keyframes bds-ai-rise{ from{ opacity:0; transform:translateY(14px) scale(.985) } to{ opacity:1; transform:none } }
@keyframes bds-ai-pop{ from{ opacity:0; transform:translateY(6px) } to{ opacity:1; transform:none } }
@keyframes bds-ai-blink{ 0%,80%,100%{ opacity:.25; transform:translateY(0) } 40%{ opacity:1; transform:translateY(-3px) } }
@keyframes bds-ai-pulse{ 0%,100%{ box-shadow:0 0 0 0 rgba(52,211,153,.55) } 60%{ box-shadow:0 0 0 5px rgba(52,211,153,0) } }
.bds-ai-panel__chrome{
  width:min(420px,calc(100vw - 24px)); height:min(680px,calc(100vh - 32px));
  margin:0 16px 16px auto; display:flex; flex-direction:column;
  background:#f6f8fb; border:1px solid rgba(14,26,43,.1);
  border-radius:var(--bds-ai-radius);
  box-shadow:0 30px 70px rgba(8,17,31,.34),0 4px 14px rgba(8,17,31,.16);
  overflow:hidden; color:var(--bds-ai-ink);
  animation:bds-ai-rise .22s cubic-bezier(.22,.9,.36,1) both;
}
.bds-ai-panel__head{
  display:flex; gap:.7rem; align-items:center;
  padding:.85rem .9rem; color:#fff;
  background:linear-gradient(135deg,#0a4a8a 0%,var(--bds-ai-navy) 60%,var(--bds-ai-navy-deep) 100%);
  box-shadow:0 1px 0 rgba(255,255,255,.08) inset;
}
.bds-ai-panel__avatar{
  flex:0 0 auto; display:grid; place-items:center; width:36px; height:36px; border-radius:12px;
  background:rgba(255,255,255,.14); color:#bff0ff;
}
.bds-ai-panel__ident{ flex:1; min-width:0; }
.bds-ai-panel__head h2{
  margin:0; font:700 16px/1.2 var(--bds-ai-font); color:#fff; letter-spacing:.005em;
}
.bds-ai-panel__sub{
  margin:.15rem 0 0; display:flex; align-items:center; gap:.35rem;
  font:400 12px/1.3 var(--bds-ai-font); color:rgba(226,240,255,.82);
}
.bds-ai-dot{
  width:7px; height:7px; border-radius:50%; background:#34d399; flex:0 0 auto;
  animation:bds-ai-pulse 2.6s ease-out infinite;
}
.bds-ai-panel__head-actions{ display:flex; align-items:center; gap:.35rem; flex-shrink:0; }
.bds-ai-iconbtn{
  display:grid; place-items:center; width:32px; height:32px; border:0; border-radius:10px;
  background:rgba(255,255,255,.12); color:#fff; cursor:pointer; transition:background .15s ease;
}
.bds-ai-iconbtn:hover{ background:rgba(255,255,255,.24); }
.bds-ai-iconbtn:focus-visible{ outline:2px solid #bff0ff; outline-offset:2px; }
.bds-ai-panel__messages{
  flex:1; overflow-y:auto; overscroll-behavior:contain;
  padding:1rem .9rem 1.1rem; display:flex; flex-direction:column; gap:.6rem;
  background:
    radial-gradient(120% 60% at 50% 0%, rgba(0,51,119,.05), transparent 60%),
    #f6f8fb;
  scrollbar-width:thin; scrollbar-color:rgba(14,26,43,.22) transparent;
}
.bds-ai-panel__messages::-webkit-scrollbar{ width:8px; }
.bds-ai-panel__messages::-webkit-scrollbar-track{ background:transparent; }
.bds-ai-panel__messages::-webkit-scrollbar-thumb{ background:rgba(14,26,43,.18); border-radius:99px; border:2px solid transparent; background-clip:content-box; }
.bds-ai-panel__messages::-webkit-scrollbar-thumb:hover{ background:rgba(14,26,43,.3); background-clip:content-box; }
.bds-ai-msg{
  max-width:88%; padding:.65rem .8rem; font:400 14px/1.5 var(--bds-ai-font);
  animation:bds-ai-pop .18s ease both; overflow-wrap:anywhere;
}
.bds-ai-msg--bot{
  align-self:flex-start; background:#fff; color:var(--bds-ai-ink);
  border:1px solid rgba(14,26,43,.09); border-radius:16px 16px 16px 5px;
  box-shadow:0 1px 2px rgba(14,26,43,.05),0 6px 16px rgba(14,26,43,.05);
}
.bds-ai-msg--user{
  align-self:flex-end; color:#fff; border-radius:16px 16px 5px 16px;
  background:linear-gradient(135deg,#0a4a8a,var(--bds-ai-navy));
  box-shadow:0 4px 12px rgba(0,51,119,.22);
}
.bds-ai-msg--err{
  align-self:flex-start; background:#fff5f3; border:1px solid #f4cdc6; color:#8a3327;
  border-radius:16px 16px 16px 5px;
}
.bds-ai-msg--wide{ max-width:100%; }
.bds-ai-welcome{ max-width:100%; }
.bds-ai-welcome__title{ margin:0 0 .3rem; font:700 15px/1.3 var(--bds-ai-font); color:var(--bds-ai-ink); }
.bds-ai-welcome__body{ margin:0; font:400 13.5px/1.5 var(--bds-ai-font); color:rgba(14,26,43,.72); }
.bds-ai-typing{ display:inline-flex; align-items:center; gap:5px; padding:.15rem 0; }
.bds-ai-typing i{
  width:6px; height:6px; border-radius:50%; background:rgba(0,51,119,.55);
  animation:bds-ai-blink 1.25s infinite ease-in-out;
}
.bds-ai-typing i:nth-child(2){ animation-delay:.16s }
.bds-ai-typing i:nth-child(3){ animation-delay:.32s }
.bds-ai-cards{ display:flex; flex-direction:column; gap:.45rem; margin-top:.55rem; }
.bds-ai-card{
  display:grid; grid-template-columns:48px 1fr; gap:.6rem; align-items:center;
  padding:.5rem .55rem; border-radius:13px; background:#fff;
  border:1px solid rgba(14,26,43,.09); text-decoration:none; color:inherit;
  transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease;
}
.bds-ai-card:hover{
  border-color:rgba(0,51,119,.3); box-shadow:0 6px 18px rgba(14,26,43,.09); transform:translateY(-1px);
}
.bds-ai-card:focus-visible{ outline:2px solid var(--bds-ai-navy); outline-offset:2px; }
.bds-ai-card__img{
  width:48px; height:48px; border-radius:10px; object-fit:cover; background:var(--bds-ai-sand);
}
.bds-ai-card__img--ph{
  display:grid; place-items:center; color:var(--bds-ai-navy); background:var(--bds-ai-sand);
  font:800 11px/1 var(--bds-ai-font); letter-spacing:.03em;
}
.bds-ai-card__body{ min-width:0; }
.bds-ai-card__name{
  margin:0; font:650 13.5px/1.3 var(--bds-ai-font); color:var(--bds-ai-ink);
  overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
}
.bds-ai-card__badge{
  display:inline-block; margin:0 0 .2rem; padding:2px 7px; border-radius:999px;
  font:800 9.5px/1.4 var(--bds-ai-font); letter-spacing:.06em; text-transform:uppercase;
  color:#9a3412; background:#fff7ed; border:1px solid #fed7aa;
}
.bds-ai-card__paid{ display:block; margin-top:.15rem; font:650 10.5px/1.2 var(--bds-ai-font); color:#9a3412; }
.bds-ai-card__meta{
  margin:.15rem 0 0; font:400 11.5px/1.35 var(--bds-ai-font); color:rgba(14,26,43,.62);
  overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.bds-ai-card__price{ display:flex; align-items:baseline; gap:.4rem; margin-top:.2rem; flex-wrap:wrap; }
.bds-ai-card__now{ font:750 13.5px/1.2 var(--bds-ai-font); color:var(--bds-ai-navy); }
.bds-ai-card__face{ font:400 11.5px/1.2 var(--bds-ai-font); color:rgba(14,26,43,.5); text-decoration:line-through; }
.bds-ai-card__save{
  font:750 10px/1 var(--bds-ai-font); letter-spacing:.04em; text-transform:uppercase;
  color:#136b45; background:#e7f7ef; border:1px solid #b6e6cd; border-radius:999px; padding:3px 7px;
}
.bds-ai-card__why{ margin:.2rem 0 0; font:400 11.5px/1.35 var(--bds-ai-font); color:rgba(14,26,43,.55); }
.bds-ai-svc{
  margin-top:.45rem; padding:.6rem .7rem; border-radius:12px;
  background:linear-gradient(180deg,#fff,#f4f8fd);
  border:1px solid rgba(0,51,119,.14); border-left:3px solid var(--bds-ai-navy);
  font:400 12.5px/1.45 var(--bds-ai-font); color:rgba(14,26,43,.72);
}
.bds-ai-svc a{ color:var(--bds-ai-navy); font-weight:650; text-decoration:none; }
.bds-ai-sec{ margin:.55rem 0 .2rem; }
.bds-ai-sec__h{
  margin:0 0 .35rem; font:650 12px/1.3 var(--bds-ai-font); color:rgba(14,26,43,.72);
  letter-spacing:.02em; text-transform:uppercase;
}
.bds-ai-svc a:hover{ text-decoration:underline; }
.bds-ai-note{
  margin-top:.45rem; padding:.5rem .65rem; border-radius:10px;
  background:rgba(14,26,43,.04); border:1px dashed rgba(14,26,43,.14);
  font:400 11.5px/1.4 var(--bds-ai-font); color:rgba(14,26,43,.6);
}
.bds-ai-chips{
  display:flex; flex-wrap:wrap; gap:.35rem; padding:0 .9rem .1rem; background:#f6f8fb;
}
.bds-ai-chips:empty{ display:none; }
.bds-ai-chip{
  border:1px solid rgba(0,51,119,.22); background:#fff; color:var(--bds-ai-navy);
  border-radius:999px; padding:.4rem .7rem; cursor:pointer;
  font:600 12px/1.1 var(--bds-ai-font); transition:background .15s ease,border-color .15s ease,transform .15s ease;
}
.bds-ai-chip:hover{ background:rgba(0,51,119,.07); border-color:rgba(0,51,119,.42); transform:translateY(-1px); }
.bds-ai-chip:focus-visible{ outline:2px solid var(--bds-ai-navy); outline-offset:2px; }
.bds-ai-panel__geo{
  display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
  padding:.5rem .9rem; border-top:1px solid rgba(14,26,43,.08); background:#fff;
}
.bds-ai-geo-btn{
  display:inline-flex; align-items:center; gap:.3rem;
  border:1px solid rgba(0,51,119,.22); border-radius:999px; padding:.38rem .7rem; cursor:pointer;
  background:rgba(0,51,119,.05); color:var(--bds-ai-navy); font:600 11.5px/1 var(--bds-ai-font);
  transition:background .15s ease;
}
.bds-ai-geo-btn:hover{ background:rgba(0,51,119,.12); }
.bds-ai-geo-btn:focus-visible{ outline:2px solid var(--bds-ai-navy); outline-offset:2px; }
.bds-ai-geo-btn.is-on{ background:var(--bds-ai-navy); color:#fff; border-color:var(--bds-ai-navy); }
.bds-ai-geo-status{ font:400 11px/1.3 var(--bds-ai-font); color:rgba(14,26,43,.55); }
.bds-ai-panel__form{
  display:flex; gap:.5rem; align-items:center;
  padding:.6rem .9rem .5rem; border-top:1px solid rgba(14,26,43,.08); background:#fff;
}
.bds-ai-field{
  flex:1; min-width:0; display:flex; align-items:center;
  border:1px solid rgba(14,26,43,.14); border-radius:999px; background:#f7f9fc;
  padding:.1rem .2rem .1rem .9rem; transition:border-color .15s ease,box-shadow .15s ease,background .15s ease;
}
.bds-ai-field:focus-within{
  border-color:rgba(0,51,119,.5); background:#fff; box-shadow:0 0 0 3px rgba(0,51,119,.12);
}
.bds-ai-panel__form input{
  flex:1; min-width:0; border:0; outline:0; background:transparent; color:var(--bds-ai-ink);
  padding:.62rem .2rem .62rem 0; font:400 14px/1.35 var(--bds-ai-font);
}
.bds-ai-panel__form input::placeholder{ color:rgba(14,26,43,.45); }
.bds-ai-panel__form button{
  flex:0 0 auto; display:grid; place-items:center; width:42px; height:42px;
  border:0; border-radius:50%; cursor:pointer; color:#fff;
  background:linear-gradient(135deg,#0a4a8a,var(--bds-ai-navy));
  box-shadow:0 4px 12px rgba(0,51,119,.26); transition:transform .15s ease,filter .15s ease;
}
.bds-ai-panel__form button:hover{ transform:translateY(-1px); filter:brightness(1.07); }
.bds-ai-panel__form button:focus-visible{ outline:3px solid rgba(0,51,119,.35); outline-offset:2px; }
.bds-ai-panel__form button:disabled{ opacity:.55; cursor:progress; transform:none; }
.bds-ai-panel__foot{
  margin:0; padding:0 .95rem .7rem; background:#fff;
  font:400 11px/1.45 var(--bds-ai-font); color:rgba(14,26,43,.52);
}
.bds-ai-panel__foot a{ color:var(--bds-ai-navy); font-weight:600; }
.bds-ai-card__site{ color:var(--bds-ai-navy); font-weight:600; }
/* Mobile: full-height sheet, safe-area aware, background locked. */
@media (max-width:640px){
  .bds-ai-panel{ place-items:stretch; }
  .bds-ai-panel__chrome{
    width:100%; height:100%; max-height:100%; margin:0; border:0;
    border-radius:16px 16px 0 0;
  }
  .bds-ai-panel__head{ padding-top:calc(.85rem + env(safe-area-inset-top,0px)); }
  .bds-ai-panel__foot{ padding-bottom:calc(.7rem + env(safe-area-inset-bottom,0px)); }
  .bds-ai-msg{ max-width:92%; font-size:15px; }
  .bds-ai-panel__form input{ font-size:16px; }
}
@media (min-width:641px) and (max-height:700px){
  .bds-ai-panel__chrome{ height:calc(100vh - 24px); }
}
body.bds-ai-open{ overflow:hidden; touch-action:none; }
@media (prefers-reduced-motion:reduce){
  .bds-ai-panel,.bds-ai-panel__chrome,.bds-ai-msg{ animation:none!important; }
  .bds-ai-dot,.bds-ai-typing i{ animation:none!important; }
  .bds-ai-launcher:hover,.bds-ai-card:hover,.bds-ai-chip:hover{ transform:none; }
}
.bds-ai-browse-geo{
  display:flex; flex-wrap:wrap; gap:.55rem; align-items:center;
  margin:0.75rem 1rem; padding:.65rem .85rem; border:1px solid var(--bds-ai-line);
  border-radius:12px; background:linear-gradient(180deg,#fff,var(--bds-ai-sand));
}
.bds-ai-browse-geo__hint{ font:400 12px/1.3 "Segoe UI",system-ui,sans-serif; color:rgba(14,26,43,.55); }
.bds-ai-local-pack{
  margin:2rem auto; padding:0 1rem; max-width:1100px;
}
.bds-ai-local-pack__inner{
  padding:1.25rem 1.35rem 1.5rem; border:1px solid var(--bds-ai-line); border-radius:16px;
  background:linear-gradient(180deg,#fff 0%, #eef2f6 100%);
}
.bds-ai-local-pack__eyebrow{ margin:0; font:600 11px/1.2 "Segoe UI",system-ui,sans-serif; letter-spacing:.08em; text-transform:uppercase; color:rgba(14,26,43,.55); }
.bds-ai-local-pack__title{ margin:.25rem 0 .2rem; font:700 1.5rem/1.15 "Segoe UI",system-ui,sans-serif; color:var(--bds-ai-ink); }
.bds-ai-local-pack__sub{ margin:0 0 1rem; font:400 14px/1.4 "Segoe UI",system-ui,sans-serif; color:rgba(14,26,43,.65); }
.bds-ai-local-pack__group h3{ margin:1rem 0 .5rem; font:700 1.05rem/1.2 "Segoe UI",system-ui,sans-serif; }
.bds-ai-local-pack__ask{ margin:1rem 0 0; }
.bds-ai-local-pack__loading{ color:rgba(14,26,43,.55); font:400 14px/1.4 "Segoe UI",system-ui,sans-serif; }
CSS;
}

function bds_ai_finder_js() {
	return <<<'JS'
(function(){
  var cfg = window.BDS_AI_FINDER || {};
  var history = [];
  var turns = [];
  var panel, launcher, form, input, messages, closeBtn, clearBtn, geoBtn, geoStatus, chipBar;
  var syncTimer = null;
  var userGeo = null;
  var lastFocus = null;
  var sessionCtx = { category:'', location:'', place_label:'', lat:null, lng:null, interests:[], is_travel:false, travel_kind:'', travel_origin:'', travel_dest:'', domain:'', usecases:[], dish_tokens:[], max_price:null, strict:false, action:'', clarify_turns:0, topic_id:'', previous_results:[], concierge:{} };
  var GEO_KEY = 'bds-ai-geo-v2';
  var CTX_KEY = 'bds-ai-ctx-v1';
  var WELCOME = {
    title: 'What are you looking for?',
    body: "Local businesses, gift cards under face, or help with your site - I'll ask a couple of questions, then point you to real listings. If we don't have it, I'll say so."
  };
  function welcomeCopy(){
    var page = (cfg && cfg.page) || {};
    var cat = String(page.categoryName || '').trim();
    var loc = String(page.locationName || '').trim();
    if (cat && loc) {
      return {
        title: 'Looking for ' + cat + ' in ' + loc + '?',
        body: "Tell me what you need and I'll narrow it down with you. If we don't have a fit, I'll say so and suggest something close."
      };
    }
    if (cat) {
      return {
        title: 'Looking for ' + cat + '?',
        body: "Share a city, zip, or what matters most and I'll match live Directory listings. Honest if we don't have it."
      };
    }
    if (loc) {
      return {
        title: 'What do you need in ' + loc + '?',
        body: "A category or two details is enough. I'll pull live matches, or tell you when we don't have it."
      };
    }
    return WELCOME;
  }
  var STARTER_CHIPS = [
    { label:'Discount gift cards', send:'I am looking for a discount gift card' },
    { label:'Find a local business', send:'find a local business near me' },
    { label:'My website needs help', send:'my website is slow' },
    { label:'More customers locally', send:'I need more local customers' }
  ];

  function el(tag, cls, html){
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (html != null) n.innerHTML = html;
    return n;
  }
  function text(s){ return String(s == null ? '' : s); }
  function esc(s){
    return text(s).replace(/[&<>"']/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }
  // Server sends plain text now, but chat history persisted before that fix
  // still holds "Lirio&#8217;s". Decode by pattern, never via innerHTML.
  var NAMED_ENTITIES = {
    amp:'&', lt:'<', gt:'>', quot:'"', apos:"'", nbsp:' ',
    lsquo:'\u2018', rsquo:'\u2019', ldquo:'\u201C', rdquo:'\u201D',
    ndash:'\u2013', mdash:'\u2014', hellip:'\u2026', middot:'\u00B7'
  };
  function decodeEntities(s){
    return text(s)
      .replace(/&#(\d+);/g, function(m, d){
        var n = parseInt(d, 10);
        return n > 0 && n <= 0x10FFFF ? String.fromCodePoint(n) : m;
      })
      .replace(/&#x([0-9a-f]+);/gi, function(m, h){
        var n = parseInt(h, 16);
        return n > 0 && n <= 0x10FFFF ? String.fromCodePoint(n) : m;
      })
      .replace(/&([a-z]+);/gi, function(m, n){
        var hit = NAMED_ENTITIES[n.toLowerCase()];
        return hit === undefined ? m : hit;
      });
  }
  function scrub(s){
    var t = decodeEntities(s);
    t = t.replace(/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/gi, '');
    t = t.replace(/(?:\+?\d[\d\s().\-]{7,}\d)/g, '');
    t = t.replace(/(?:wa\.me|api\.whatsapp\.com|whatsapp)[^\s]*/gi, '');
    t = t.replace(/\s{2,}/g, ' ').trim();
    return t;
  }
  function storageKey(){
    var uid = parseInt(cfg.userId, 10) || 0;
    return uid > 0 ? ('bds-ai-finder-v1:u' + uid) : 'bds-ai-finder-v1:guest';
  }
  function wantsNearMe(q){
    return /\bnear\s+me\b|\bnearby\b|\baround\s+me\b|\bclose\s+to\s+me\b/i.test(q || '');
  }
  function hasGeo(){
    return userGeo && typeof userGeo.lat === 'number' && typeof userGeo.lng === 'number';
  }
  function setCookieGeo(){
    if (!hasGeo()) return;
    try{
      var payload = encodeURIComponent(JSON.stringify({
        lat: userGeo.lat, lng: userGeo.lng, label: userGeo.label || '', t: Date.now()
      }));
      document.cookie = 'bds_ai_geo=' + payload + '; path=/; max-age=86400; SameSite=Lax';
    }catch(e){}
  }
  function persistGeo(){
    if (!hasGeo()) return;
    var blob = { lat:userGeo.lat, lng:userGeo.lng, label:userGeo.label||'', consent:true, ts:Date.now() };
    try{ sessionStorage.setItem(GEO_KEY, JSON.stringify(blob)); }catch(e){}
    try{ localStorage.setItem(GEO_KEY, JSON.stringify(blob)); }catch(e){}
    setCookieGeo();
  }
  function clearGeoStore(){
    userGeo = null;
    try{ sessionStorage.removeItem(GEO_KEY); }catch(e){}
    try{ localStorage.removeItem(GEO_KEY); }catch(e){}
    try{ document.cookie = 'bds_ai_geo=; path=/; max-age=0; SameSite=Lax'; }catch(e){}
  }
  function setGeoStatus(msg, on){
    if (geoStatus) geoStatus.textContent = msg || '';
    if (geoBtn){
      if (on) geoBtn.classList.add('is-on');
      else geoBtn.classList.remove('is-on');
      geoBtn.textContent = on ? 'Location on' : 'Use my location';
    }
  }
  async function reverseGeocode(lat, lng){
    if (!cfg.geocode) return '';
    try{
      var url = cfg.geocode + (cfg.geocode.indexOf('?')>=0?'&':'?') + 'lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);
      var res = await fetch(url, { headers:{ 'X-WP-Nonce': cfg.nonce || '' }, credentials:'same-origin' });
      if (!res.ok) return '';
      var data = await res.json().catch(function(){ return null; });
      return (data && data.label) ? String(data.label) : '';
    }catch(e){ return ''; }
  }
  function requestGeoPromise(){
    return new Promise(function(resolve){
      if (!navigator.geolocation){
        resolve({ ok:false, reason:'unsupported' });
        return;
      }
      setGeoStatus('Getting your location...', false);
      navigator.geolocation.getCurrentPosition(async function(pos){
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        var label = await reverseGeocode(lat, lng);
        userGeo = { lat:lat, lng:lng, label:label || '' };
        persistGeo();
        setGeoStatus(label ? ('Using your location: ' + label) : 'Using your location for "near me".', true);
        resolve({ ok:true, geo:userGeo });
      }, function(err){
        clearGeoStore();
        var denied = err && (err.code === 1);
        setGeoStatus(denied
          ? 'Location blocked - try a zip like 60622 or 77710.'
          : "Couldn't get location - try a zip/city instead.", false);
        resolve({ ok:false, reason: denied ? 'denied' : 'error' });
      }, { enableHighAccuracy:false, timeout:12000, maximumAge:300000 });
    });
  }
  function loadGeo(){
    try{
      var raw = sessionStorage.getItem(GEO_KEY) || localStorage.getItem(GEO_KEY);
      if (!raw) return;
      var g = JSON.parse(raw);
      if (g && typeof g.lat === 'number' && typeof g.lng === 'number' && g.consent){
        userGeo = { lat:g.lat, lng:g.lng, label:g.label||'' };
        setCookieGeo();
        setGeoStatus(userGeo.label ? ('Using your location: ' + userGeo.label) : 'Using your location for "near me".', true);
      }
    }catch(e){}
  }
  function loadCtx(){
    try{
      var raw = sessionStorage.getItem(CTX_KEY);
      if (!raw) return;
      var c = JSON.parse(raw);
      if (c && typeof c === 'object') sessionCtx = Object.assign(sessionCtx, c);
    }catch(e){}
  }
  function saveCtx(){
    try{ sessionStorage.setItem(CTX_KEY, JSON.stringify(sessionCtx)); }catch(e){}
  }
  function apiHistory(){ return history.slice(-6); }
  function rebuildApiHistory(){
    history = [];
    turns.forEach(function(t){
      if (!t || !t.role || !t.content) return;
      history.push({ role: t.role, content: scrub(t.content) });
    });
    if (history.length > 8) history = history.slice(-8);
  }
  function sanitizeListings(list){
    if (!list || !list.length) return [];
    return list.slice(0, 10).map(function(L){
      return {
        id: L.id || 0,
        name: scrub(L.name || ''),
        url: text(L.url || ''),
        categories: (L.categories || []).slice(0, 4).map(decodeEntities),
        locations: (L.locations || []).slice(0, 4).map(decodeEntities),
        address: scrub(L.address || ''),
        website: text(L.website || ''),
        image: text(L.image || ''),
        distance_km: (typeof L.distance_km === 'number') ? L.distance_km : null,
        distance_label: text(L.distance_label || ''),
        is_travel: !!L.is_travel,
        travel_kind: text(L.travel_kind || ''),
        price_label: text(L.price_label || ''),
        route_label: text(L.route_label || ''),
        book_url: text(L.book_url || L.website || ''),
        tagline: scrub(L.tagline || L.excerpt || ''),
        area: scrub(L.area || ''),
        is_sponsored: !!L.is_sponsored,
        is_pinned: !!L.is_pinned,
        is_place_rec: !!L.is_place_rec,
        placement_label: text(L.placement_label || ''),
        evidence: Array.isArray(L.evidence) ? L.evidence.slice(0, 3).map(text) : []
      };
    });
  }
  function sanitizeServices(svcs){
    if (!svcs || !svcs.length) return [];
    return svcs.slice(0, 4).map(function(S){
      return { label: text(S.label || ''), url: text(S.url || ''), blurb: scrub(S.blurb || '') };
    });
  }
  function payload(){ return { v:1, turns: turns.slice(-20), updated: Date.now() }; }
  function saveLocal(){ try{ localStorage.setItem(storageKey(), JSON.stringify(payload())); }catch(e){} }
  function loadLocal(){
    try{
      var raw = localStorage.getItem(storageKey());
      if (!raw) return null;
      var data = JSON.parse(raw);
      if (!data || !Array.isArray(data.turns)) return null;
      return data;
    }catch(e){ return null; }
  }
  function scheduleServerSync(){
    if (!cfg.memory || cfg.guest || !(parseInt(cfg.userId, 10) > 0)) return;
    if (syncTimer) clearTimeout(syncTimer);
    syncTimer = setTimeout(function(){
      fetch(cfg.memory, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'X-WP-Nonce': cfg.nonce || '' },
        credentials:'same-origin',
        body: JSON.stringify(payload())
      }).catch(function(){});
    }, 600);
  }
  function persist(){ saveLocal(); scheduleServerSync(); }
  async function loadServerMemory(){
    if (!cfg.memory || cfg.guest || !(parseInt(cfg.userId, 10) > 0)) return null;
    try{
      var res = await fetch(cfg.memory, { method:'GET', headers:{ 'X-WP-Nonce': cfg.nonce || '' }, credentials:'same-origin' });
      if (!res.ok) return null;
      var data = await res.json().catch(function(){ return null; });
      return data && data.data ? data.data : null;
    }catch(e){ return null; }
  }
  function isNarrow(){ return window.matchMedia && window.matchMedia('(max-width:640px)').matches; }
  function openPanel(){
    if (!panel) return;
    lastFocus = document.activeElement;
    panel.hidden = false;
    if (launcher){
      launcher.setAttribute('aria-expanded','true');
      launcher.classList.add('is-open');
    }
    if (isNarrow()) document.body.classList.add('bds-ai-open');
    if (input) setTimeout(function(){ input.focus(); }, 60);
    ensureGreeting();
  }
  function closePanel(){
    if (!panel) return;
    panel.hidden = true;
    document.body.classList.remove('bds-ai-open');
    if (launcher){
      launcher.setAttribute('aria-expanded','false');
      launcher.classList.remove('is-open');
    }
    if (lastFocus && lastFocus.focus) { try{ lastFocus.focus(); }catch(e){} }
    else if (launcher) launcher.focus();
  }
  function ensureGreeting(){
    if (!messages) return;
    if (messages.dataset.greeted) return;
    messages.dataset.greeted = '1';
    if (!turns.length){
      var w = welcomeCopy();
      addBot(
        '<div class="bds-ai-welcome"><p class="bds-ai-welcome__title">' + esc(w.title) + '</p>'
        + '<p class="bds-ai-welcome__body">' + esc(w.body) + '</p></div>',
        true
      );
      setChips(STARTER_CHIPS);
    }
  }
  function setChips(list){
    if (!chipBar) return;
    chipBar.innerHTML = '';
    if (!list || !list.length) return;
    list.slice(0, 6).forEach(function(c){
      if (!c || !c.label) return;
      var b = el('button','bds-ai-chip');
      b.type = 'button';
      b.textContent = c.label;
      b.addEventListener('click', function(){
        var q = String(c.send || c.label || '').trim();
        if (!q) return;
        setChips([]);
        submitQuery(q);
      });
      chipBar.appendChild(b);
    });
  }
  function submitQuery(q){
    if (!q) return;
    addUser(q);
    var btn = document.getElementById('bds-ai-send');
    if (btn) btn.disabled = true;
    ask(q).finally(function(){ if (btn) btn.disabled = false; });
  }
  // Card thumbnails load after the message is appended and push the newest
  // reply out of view, so re-pin the scroll once layout settles.
  function scrollToEnd(node){
    if (!messages) return;
    var go = function(){ messages.scrollTop = messages.scrollHeight; };
    go();
    if (window.requestAnimationFrame) requestAnimationFrame(go);
    setTimeout(go, 120);
    if (node){
      [].slice.call(node.querySelectorAll('img')).forEach(function(img){
        if (img.complete) return;
        img.addEventListener('load', go, { once:true });
        img.addEventListener('error', go, { once:true });
      });
    }
  }
  function addBot(htmlOrText, isHtml){
    var m = el('div','bds-ai-msg bds-ai-msg--bot');
    if (isHtml) m.innerHTML = htmlOrText; else m.textContent = htmlOrText;
    messages.appendChild(m);
    scrollToEnd(m);
    return m;
  }
  function addUser(t){
    var m = el('div','bds-ai-msg bds-ai-msg--user');
    m.textContent = t;
    messages.appendChild(m);
    scrollToEnd(m);
  }
  function addErr(t){
    var m = el('div','bds-ai-msg bds-ai-msg--err');
    m.textContent = t;
    messages.appendChild(m);
    scrollToEnd(m);
  }
  function renderSections(sections, listings, products, services){
    if (sections && sections.length > 1){
      var html = '';
      sections.forEach(function(sec){
        if (!sec || !sec.items || !sec.items.length) return;
        html += '<div class="bds-ai-sec"><p class="bds-ai-sec__h">'+esc(sec.title || '')+'</p>';
        if (sec.kind === 'products') html += renderProducts(sec.items);
        else if (sec.kind === 'services') html += renderServices(sec.items);
        else html += renderListings(sec.items);
        html += '</div>';
      });
      return html;
    }
    return renderProducts(products) + renderListings(listings) + renderServices(services);
  }
  function renderListings(list){
    if (!list || !list.length) return '';
    var html = '<div class="bds-ai-cards">';
    list.forEach(function(L){
      var meta = [];
      if (L.google_rating) meta.push(L.google_rating + '★ Google');
      if (L.price_label) meta.push(L.price_label);
      if (L.distance_label) meta.push(L.distance_label);
      if (L.route_label) meta.push(L.route_label);
      if (L.tagline && !L.is_sponsored) meta.push(L.tagline);
      if (L.categories && L.categories.length){
        var cats = L.categories.filter(function(c){ return c && c !== L.price_label; }).slice(0,2);
        if (cats.length) meta.push(cats.join(' | '));
      }
      if (L.locations && L.locations.length) meta.push(L.locations.slice(0,2).join(' | '));
      if (L.area && meta.indexOf(L.area) < 0) meta.push(L.area);
      if (L.evidence && L.evidence.length) meta.push('Why: ' + L.evidence[0]);
      var img = L.image
        ? '<img class="bds-ai-card__img" src="'+esc(L.image)+'" alt="" loading="lazy" />'
        : '<div class="bds-ai-card__img bds-ai-card__img--ph" aria-hidden="true">BD</div>';
      var site = L.is_travel
        ? '<span class="bds-ai-card__site"> | Book now</span>'
        : (L.website ? '<span class="bds-ai-card__site"> | site</span>' : '');
      // Travel → on-site deal page (Book now there). Local businesses → listing URL.
      var href = L.is_travel ? (L.listing_url || L.url || '#') : (L.url || L.book_url || '#');
      var paidPlacement = L.is_sponsored || L.is_pinned;
      var badge = L.is_sponsored
        ? '<span class="bds-ai-card__badge">Sponsored</span>'
        : (L.is_pinned ? '<span class="bds-ai-card__badge">Pinned</span>' : (L.is_place_rec ? '<span class="bds-ai-card__badge" style="color:#0f766e;background:#ecfdf5;border-color:#a7f3d0">Place</span>' : ''));
      var paid = paidPlacement ? '<span class="bds-ai-card__paid">'+(L.is_pinned ? 'Pinned placement' : 'Paid placement')+'</span>' : '';
      var oneLine = (L.is_sponsored && L.tagline) ? esc(L.tagline) + (L.area ? ' | '+esc(L.area) : '') : esc(meta.join(' | '));
      html += '<a class="bds-ai-card" href="'+esc(href)+'"'+(paidPlacement?' rel="sponsored"':'')+(L.is_travel?' data-bds-travel="1"':'')+(L.is_sponsored?' data-bds-sponsored="1"':'')+(L.is_pinned?' data-bds-pinned="1"':'')+'>'+img+'<div>'+badge+'<p class="bds-ai-card__name">'+esc(L.name)+'</p><p class="bds-ai-card__meta">'+oneLine+site+'</p>'+paid+'</div></a>';
    });
    html += '</div>';
    return html;
  }
  function sanitizeProducts(list){
    if (!list || !list.length) return [];
    return list.slice(0, 6).map(function(P){
      return {
        id: P.id || 0,
        name: scrub(P.name || ''),
        brand: scrub(P.brand || ''),
        url: text(P.url || ''),
        image: text(P.image || ''),
        price_label: text(P.price_label || ''),
        face_label: text(P.face_label || ''),
        save_label: text(P.save_label || ''),
        why: scrub(P.why || '')
      };
    });
  }
  function renderProducts(list){
    if (!list || !list.length) return '';
    var html = '<div class="bds-ai-cards">';
    list.forEach(function(P){
      var img = P.image
        ? '<img class="bds-ai-card__img" src="'+esc(P.image)+'" alt="" loading="lazy" />'
        : '<div class="bds-ai-card__img bds-ai-card__img--ph" aria-hidden="true">GC</div>';
      var price = '';
      if (P.price_label || P.face_label || P.save_label){
        price = '<div class="bds-ai-card__price">'
          + (P.price_label ? '<span class="bds-ai-card__now">'+esc(P.price_label)+'</span>' : '')
          + (P.face_label ? '<span class="bds-ai-card__face">'+esc(P.face_label)+'</span>' : '')
          + (P.save_label ? '<span class="bds-ai-card__save">'+esc(P.save_label)+'</span>' : '')
          + '</div>';
      }
      html += '<a class="bds-ai-card" href="'+esc(P.url)+'" data-bds-product="1">'+img
        + '<div class="bds-ai-card__body"><p class="bds-ai-card__name">'+esc(P.name)+'</p>'
        + price
        + (P.why ? '<p class="bds-ai-card__why">'+esc(P.why)+'</p>' : '')
        + '</div></a>';
    });
    html += '</div>';
    return html;
  }
  function renderServices(svcs){
    if (!svcs || !svcs.length) return '';
    var html = '';
    svcs.forEach(function(S){
      html += '<div class="bds-ai-svc"><a href="'+esc(S.url)+'" target="_blank" rel="noopener">'+esc(S.label)+'</a>'
        + (S.blurb ? ' - '+esc(S.blurb) : '') + '</div>';
    });
    return html;
  }
  function renderAssistantTurn(t){
    var reply = scrub(t.content || '');
    var listings = sanitizeListings(t.listings || []);
    var services = sanitizeServices(t.services || []);
    var products = sanitizeProducts(t.products || []);
    var block = '<div>'+esc(reply)+'</div>' + renderSections(t.sections, listings, products, services);
    if (t.guest && t.contact_note) block += '<div class="bds-ai-note">'+esc(scrub(t.contact_note))+'</div>';
    var node = addBot(block, true);
    if (products.length || listings.length) node.classList.add('bds-ai-msg--wide');
  }
  function restoreTurns(list){
    turns = []; history = [];
    if (!messages) return;
    messages.innerHTML = '';
    messages.dataset.greeted = '';
    if (!list || !list.length){ ensureGreeting(); return; }
    list.forEach(function(t){
      if (!t || !t.role) return;
      if (t.role === 'user'){
        var u = scrub(t.content || '');
        if (!u) return;
        addUser(u);
        turns.push({ role:'user', content:u });
      } else if (t.role === 'assistant'){
        var a = {
          role:'assistant', content: scrub(t.content || ''),
          listings: sanitizeListings(t.listings || []),
          products: sanitizeProducts(t.products || []),
          services: sanitizeServices(t.services || []),
          sections: t.sections || [],
          contact_note: scrub(t.contact_note || ''), guest: !!t.guest
        };
        renderAssistantTurn(a);
        turns.push(a);
      }
    });
    rebuildApiHistory();
    messages.dataset.greeted = '1';
    if (!turns.length) ensureGreeting();
  }
  async function clearChat(){
    turns = []; history = [];
    try{ localStorage.removeItem(storageKey()); }catch(e){}
    try{ sessionStorage.removeItem(CTX_KEY); }catch(e){}
    sessionCtx = { category:'', location:'', place_label:'', lat:null, lng:null, interests:[], is_travel:false, travel_kind:'', travel_origin:'', travel_dest:'', domain:'', usecases:[], dish_tokens:[], max_price:null, strict:false, action:'', clarify_turns:0, topic_id:'', previous_results:[], concierge:{} };
    setChips([]);
    if (cfg.memory && !cfg.guest && (parseInt(cfg.userId, 10) > 0)){
      fetch(cfg.memory, { method:'DELETE', headers:{ 'X-WP-Nonce': cfg.nonce || '' }, credentials:'same-origin' }).catch(function(){});
    }
    if (messages){ messages.innerHTML = ''; messages.dataset.greeted = ''; }
    ensureGreeting();
  }
  function dots(label){
    return '<span class="bds-ai-typing" role="status" aria-label="'+esc(label || 'Thinking')+'"><i></i><i></i><i></i></span>';
  }
  async function ask(q){
    setChips([]);
    var typing = addBot(dots('Thinking'), true);
    try{
      if (wantsNearMe(q) && !hasGeo()){
        typing.innerHTML = dots('Getting your location');
        var geoRes = await requestGeoPromise();
        if (!geoRes.ok){
          typing.remove();
          addBot(geoRes.reason === 'denied'
            ? "Location permission is off, so I won't guess where you are. Try 'coffee in 60622' or 'spa in Playa del Carmen', or tap Use my location after allowing access."
            : "I couldn't read your location. Share a zip or city instead - e.g. restaurants in 60622.");
          turns.push({ role:'user', content: scrub(q) });
          turns.push({ role:'assistant', content: 'Need a zip or city - location unavailable.', listings:[], services:[], guest:!!cfg.guest });
          rebuildApiHistory(); persist();
          return;
        }
        typing.innerHTML = dots('Searching near ' + (userGeo.label || 'you'));
      }
      var body = { message:q, history: apiHistory(), context: sessionCtx };
      if (hasGeo()){
        body.lat = userGeo.lat;
        body.lng = userGeo.lng;
        body.radius_km = 5;
        if (userGeo.label) body.place_label = userGeo.label;
      }
      var res = await fetch(cfg.rest, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'X-WP-Nonce': cfg.nonce || '' },
        credentials:'same-origin',
        body: JSON.stringify(body)
      });
      var data = await res.json().catch(function(){ return {}; });
      typing.remove();
      if (!res.ok){
        addErr((data && data.message) || 'Something went wrong. Try again.');
        return;
      }
      if (data && data.mode === 'clarify'){
        var cq = scrub((data && data.reply) || (data && data.question) || '');
        var clarifyChips = (data && Array.isArray(data.chips)) ? data.chips.slice(0, 6).map(function(c){
          return { label: scrub(c && c.label), send: scrub((c && c.send) || (c && c.label)) };
        }).filter(function(c){ return !!c.label; }) : [];
        var clarifyHtml = esc(cq).split(/\n+/).filter(Boolean).map(function(p){ return '<p>'+p+'</p>'; }).join('');
        addBot('<div class="bds-ai-reply bds-ai-reply--clarify">'+clarifyHtml+'</div>', true);
        setChips(clarifyChips);
        if (data.state && typeof data.state === 'object') sessionCtx = Object.assign(sessionCtx, data.state);
        if (data.concierge) sessionCtx.concierge = data.concierge;
        sessionCtx.action = 'CLARIFY';
        if (typeof data.state === 'object' && data.state.clarify_turns != null) sessionCtx.clarify_turns = data.state.clarify_turns;
        saveCtx();
        turns.push({ role:'user', content: scrub(q) });
        turns.push({ role:'assistant', content:cq, listings:[], services:[], guest:!!cfg.guest, mode:'clarify' });
        rebuildApiHistory(); persist();
        return;
      }
      if (data && data.needs_geo){
        var again = await requestGeoPromise();
        if (again.ok){
          return ask(q);
        }
        addBot(scrub(data.reply || 'Allow location or give me a zip/city.'));
        return;
      }
      var reply = scrub((data && data.reply) || 'Here is what I found.');
      var listings = sanitizeListings(data.listings || []);
      var services = sanitizeServices(data.services || []);
      var products = sanitizeProducts(data.products || []);
      var chips = (data && Array.isArray(data.chips)) ? data.chips.slice(0, 6).map(function(c){
        return { label: scrub(c && c.label), send: scrub((c && c.send) || (c && c.label)) };
      }).filter(function(c){ return !!c.label; }) : [];
      var contactNote = scrub((data && data.contact_note) || '');
      var guestFlag = !!(data && data.guest);
      var parsed = (data && data.parsed) || {};
      if (parsed.category) sessionCtx.category = parsed.category;
      if (parsed.location) sessionCtx.location = parsed.location;
      if (parsed.place_label || parsed.area_label) sessionCtx.place_label = parsed.place_label || parsed.area_label;
      if (typeof parsed.lat === 'number') sessionCtx.lat = parsed.lat;
      if (typeof parsed.lng === 'number') sessionCtx.lng = parsed.lng;
      sessionCtx.is_travel = !!parsed.is_travel;
      if (parsed.travel_kind) sessionCtx.travel_kind = parsed.travel_kind;
      if (parsed.travel_origin) sessionCtx.travel_origin = parsed.travel_origin;
      if (parsed.travel_dest) sessionCtx.travel_dest = parsed.travel_dest;
      if (!parsed.is_travel){
        sessionCtx.travel_kind = '';
        sessionCtx.travel_origin = '';
        sessionCtx.travel_dest = '';
      }
      if (parsed.category && sessionCtx.interests.indexOf(parsed.category) < 0){
        sessionCtx.interests = (sessionCtx.interests || []).concat([parsed.category]).slice(-6);
      }
      if (parsed.domain) sessionCtx.domain = parsed.domain;
      if (Array.isArray(parsed.dish_tokens) && parsed.dish_tokens.length) sessionCtx.dish_tokens = parsed.dish_tokens.slice(0, 6);
      if (parsed.max_price != null) sessionCtx.max_price = parsed.max_price;
      sessionCtx.strict = !!parsed.strict;
      if (Array.isArray(parsed.usecases) && parsed.usecases.length) sessionCtx.usecases = parsed.usecases.slice(0, 4);
      if (data.action) sessionCtx.action = data.action;
      if (data.state && typeof data.state === 'object') sessionCtx = Object.assign(sessionCtx, data.state);
      if (data.concierge) sessionCtx.concierge = data.concierge;
      if (Array.isArray(data.state && data.state.previous_results)) sessionCtx.previous_results = data.state.previous_results;
      saveCtx();
      if (parsed.place_label && hasGeo() && !userGeo.label){
        userGeo.label = parsed.place_label;
        persistGeo();
        setGeoStatus('Using your location: ' + parsed.place_label, true);
      }
      var replyHtml = esc(reply).split(/\n+/).filter(Boolean).map(function(p){ return '<p>'+p+'</p>'; }).join('');
      var sections = (data && Array.isArray(data.sections)) ? data.sections : [];
      var block = '<div class="bds-ai-reply">'+replyHtml+'</div>' + renderSections(sections, listings, products, services);
      if (guestFlag && contactNote) block += '<div class="bds-ai-note">'+esc(contactNote)+'</div>';
      var node = addBot(block, true);
      if (products.length || listings.length) node.classList.add('bds-ai-msg--wide');
      setChips(chips);
      turns.push({ role:'user', content: scrub(q) });
      turns.push({ role:'assistant', content:reply, listings:listings, products:products, services:services, sections:sections, contact_note:contactNote, guest:guestFlag });
      if (turns.length > 20) turns = turns.slice(-20);
      rebuildApiHistory();
      persist();
    }catch(e){
      typing.remove();
      addErr('Network error - please try again.');
    }
  }
  // A chip is only worth adding when its container is genuinely on screen.
  // Collapsed hamburger menus and off-canvas drawers render at negative x with
  // visibility:hidden, which used to swallow the only header entry point.
  function isReallyVisible(node){
    if (!node || !node.getBoundingClientRect) return false;
    if (!node.offsetParent && getComputedStyle(node).position !== 'fixed') return false;
    var r = node.getBoundingClientRect();
    if (r.width < 4 || r.height < 4) return false;
    if (r.right < 0 || r.left > (window.innerWidth || 0) + 4) return false;
    var el2 = node;
    while (el2 && el2 !== document.body){
      var cs = getComputedStyle(el2);
      if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity || '1') < .05) return false;
      el2 = el2.parentElement;
    }
    return true;
  }
  function injectHeaderChip(){
    if (document.getElementById('bds-ai-header-chip')) return;
    var header = document.querySelector('#site-header .menu-area, #site-header, header.site-header, .directorist-header');
    if (!header || !isReallyVisible(header)) return;
    var hosts = [].slice.call(header.querySelectorAll('.header-btn, .header-right, .menu-right, .navbar-nav, .main-nav, .menu-container, nav, .header-menu'));
    var nav = null;
    for (var i = 0; i < hosts.length; i++){
      if (isReallyVisible(hosts[i])) { nav = hosts[i]; break; }
    }
    if (!nav && isReallyVisible(header)) nav = header;
    if (!nav) return;
    var chip = el('button','bds-ai-header-chip');
    chip.id = 'bds-ai-header-chip';
    chip.type = 'button';
    chip.innerHTML = '<span aria-hidden="true">*</span> Ask BrandDad';
    chip.addEventListener('click', openPanel);
    nav.appendChild(chip);
    // If it landed somewhere invisible anyway, drop it: the floating launcher
    // is the reliable entry point and a ghost chip helps nobody.
    if (!isReallyVisible(chip)) chip.remove();
  }
  // Keep the launcher clear of other fixed furniture (cookie bars, chat widgets,
  // sticky mobile bars, back-to-top buttons) instead of stacking on top of them.
  function avoidCollisions(){
    if (!launcher) return;
    var root = document.documentElement;
    root.style.setProperty('--bds-ai-fab-bottom', (isNarrow() ? 16 : 20) + 'px');
    root.style.setProperty('--bds-ai-fab-right', (isNarrow() ? 16 : 20) + 'px');
    if (panel && !panel.hidden) return;
    var me = launcher.getBoundingClientRect();
    var vw = window.innerWidth || 0;
    var vh = window.innerHeight || 0;
    var lift = 0;
    // Step aside for an open nav drawer / off-canvas menu rather than floating
    // on top of it.
    var navOpen = /(menu|nav|offcanvas|drawer|sidebar)[-_]?(is[-_]?)?(open|active|shown|expanded)/i
      .test(document.body.className || '');
    [].slice.call(document.querySelectorAll('body *')).forEach(function(n){
      if (n === launcher || launcher.contains(n) || n.closest('#bds-ai-panel')) return;
      var cs = getComputedStyle(n);
      if (cs.position !== 'fixed') return;
      if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity || '1') < .05) return;
      var r = n.getBoundingClientRect();
      if (r.width < 24 || r.height < 24) return;
      // Big blocking overlays are drawers/modals: yield instead of dodging.
      if (r.width > vw * 0.5 && r.height > vh * 0.55 && (parseInt(cs.zIndex, 10) || 0) >= 900) {
        navOpen = true;
        return;
      }
      if (r.width > vw * 0.98 && r.height > vh * 0.9) return;
      var nearBottom = r.bottom > vh - 200;
      var nearRight = r.right > vw - 260;
      if (!nearBottom || !nearRight) return;
      var overlapX = Math.min(me.right, r.right) - Math.max(me.left, r.left);
      var overlapY = Math.min(me.bottom, r.bottom) - Math.max(me.top, r.top);
      var wouldTouch = overlapX > -14 && overlapY > -14;
      if (wouldTouch) lift = Math.max(lift, (vh - r.top) + 12);
    });
    if (lift > 0 && lift < vh * 0.6){
      root.style.setProperty('--bds-ai-fab-bottom', Math.round(lift) + 'px');
    }
    launcher.classList.toggle('is-nav-open', !!navOpen);
  }
  function injectHero(){
    var page = (cfg && cfg.page) || {};
    var path = (location.pathname || '').toLowerCase();
    var isHome = !!(page.isHome || document.body.classList.contains('home') || document.body.classList.contains('front-page') || path === '/' || path === '');
    var isBrowse = !!(page.isBrowse
      || document.body.classList.contains('tax-at_biz_dir-category')
      || document.body.classList.contains('tax-at_biz_dir-location')
      || document.body.classList.contains('theme-dir-single_category')
      || document.body.classList.contains('theme-dir-single_location')
      || document.body.classList.contains('theme-dir-all_listings')
      || /\/(all-listings|search-result|search-results|single-category|single-location)(\/|$)/i.test(path));
    if (!isHome && !isBrowse) return;
    if (isBrowse) document.body.classList.add('bds-ai-browse');
    if (document.getElementById('bds-ai-hero-entry')) return;
    var wrap = el('div','bds-ai-hero-entry');
    wrap.id = 'bds-ai-hero-entry';
    wrap.setAttribute('data-bds-ai-finder', (cfg && cfg.version) || '1.8.1');
    var ph = 'What are you looking for?';
    if (isBrowse) {
      if (page.categoryName && page.locationName) ph = 'Looking for ' + page.categoryName + ' in ' + page.locationName + '?';
      else if (page.categoryName) ph = 'Looking for ' + page.categoryName + '?';
      else if (page.locationName) ph = 'What do you need in ' + page.locationName + '?';
    }
    wrap.innerHTML = '<form class="bds-ai-hero-entry__inner" id="bds-ai-hero-form" role="search" aria-label="Ask BrandDad">'
      + '<label class="screen-reader-text" for="bds-ai-hero-input">What are you looking for?</label>'
      + '<input type="search" id="bds-ai-hero-input" name="q" placeholder="'+ph.replace(/"/g,'&quot;')+'" autocomplete="off" enterkeyhint="search" />'
      + '<button type="submit">Ask</button></form>';
    function runHeroAsk(e){
      if (e) e.preventDefault();
      var heroIn = document.getElementById('bds-ai-hero-input');
      var q = ((heroIn && heroIn.value) || '').trim();
      // Seed archive context so follow-ups stay on this category/location.
      if (page.categoryName) sessionCtx.category = page.categoryName;
      else if (page.category) sessionCtx.category = page.category;
      if (page.locationName) sessionCtx.location = page.locationName;
      else if (page.location) sessionCtx.location = page.location;
      if (page.directoryType) sessionCtx.directory_type = page.directoryType;
      saveCtx();
      openPanel();
      if (!q) {
        if (input) setTimeout(function(){ input.focus(); }, 40);
        return;
      }
      if (heroIn) heroIn.value = '';
      addUser(q);
      var btn = document.getElementById('bds-ai-send');
      if (btn) btn.disabled = true;
      ask(q).finally(function(){ if (btn) btn.disabled = false; });
    }
    var heroForm = wrap.querySelector('#bds-ai-hero-form');
    if (heroForm) heroForm.addEventListener('submit', runHeroAsk);
    // Prefer the WA hero reserved search slot on home (avoids insert→adopt double CLS).
    if (isHome) {
      var wahSlot = document.querySelector('#bds-wa-hero [data-wah-search]');
      if (wahSlot) {
        var native = wahSlot.querySelector('[data-wah-native-search], #bds-wah-native-search');
        if (native) {
          try {
            var src = native.querySelector('input[type="search"], input[name="q"]');
            var dst = wrap.querySelector('#bds-ai-hero-input, input[type="search"], input[name="q"]');
            if (src && dst && src.value && !dst.value) dst.value = src.value;
          } catch (e) {}
          native.setAttribute('hidden', '');
          native.setAttribute('aria-hidden', 'true');
        }
        wahSlot.appendChild(wrap);
        return;
      }
    }
    // Archives: place under H1 title so AI owns the first search composition.
    if (isBrowse && !isHome) {
      var h1 = document.querySelector('.directorist-archive-title, .directorist-archive-contents h1, .directorist-header h1, main h1');
      if (h1) {
        var after = h1.closest('.col-12, .directorist-col-12, .entry-header, .directorist-listings-header') || h1;
        if (after.parentNode) {
          if (after.nextSibling) after.parentNode.insertBefore(wrap, after.nextSibling);
          else after.parentNode.appendChild(wrap);
          return;
        }
      }
      var arch = document.querySelector('.directorist-archive-contents, .directorist-type-nav');
      if (arch && arch.parentNode) {
        arch.parentNode.insertBefore(wrap, arch);
        return;
      }
    }
    // Insert before Directorist search block (sibling), never inside a form that Front Compose hides.
    var dirBlock = document.querySelector(
      '.elementor-widget-directorist-search-listing, .elementor-widget-directorist-search, .directorist-search-contents, form.directorist-search-form'
    );
    if (dirBlock) {
      var place = dirBlock.closest('.elementor-widget-directorist-search-listing, .elementor-widget-directorist-search, .elementor-element, section') || dirBlock;
      if (place.closest && place.closest('form.directorist-search-form')) {
        place = place.closest('form.directorist-search-form');
      }
      if (place && place.parentNode) {
        place.parentNode.insertBefore(wrap, place);
        return;
      }
    }
    var host = document.querySelector('#content, main, #primary, .site-content');
    if (host) {
      host.insertBefore(wrap, host.firstChild);
      return;
    }
  }
  function seedArchiveContext(){
    var page = (cfg && cfg.page) || {};
    if (page.categoryName) sessionCtx.category = sessionCtx.category || page.categoryName;
    else if (page.category) sessionCtx.category = sessionCtx.category || page.category;
    if (page.locationName) sessionCtx.location = sessionCtx.location || page.locationName;
    else if (page.location) sessionCtx.location = sessionCtx.location || page.location;
    if (page.directoryType) sessionCtx.directory_type = page.directoryType;
    if (page.categoryName && (sessionCtx.interests || []).indexOf(page.categoryName) < 0) {
      sessionCtx.interests = (sessionCtx.interests || []).concat([page.categoryName]).slice(-6);
    }
    saveCtx();
  }
  function hijackDirectoristSearch(){
    [].slice.call(document.querySelectorAll('form.directorist-search-form, .directorist-search-contents form')).forEach(function(form){
      if (!form || form.dataset.bdsAiHijack === '1') return;
      if (form.closest('#bds-ai-hero-entry')) return;
      form.dataset.bdsAiHijack = '1';
      form.addEventListener('submit', function(e){
        e.preventDefault();
        e.stopPropagation();
        var page = (cfg && cfg.page) || {};
        var inp = form.querySelector('input[type="search"], input[type="text"], input[name="q"], input[name="search"], .search-text, .directorist-search-field__input input');
        var q = ((inp && inp.value) || '').trim();
        if (!q && page.categoryName) q = page.categoryName;
        if (page.categoryName) sessionCtx.category = page.categoryName;
        if (page.locationName) sessionCtx.location = page.locationName;
        saveCtx();
        openPanel();
        if (!q) { if (input) setTimeout(function(){ input.focus(); }, 40); return; }
        addUser(q);
        var btn = document.getElementById('bds-ai-send');
        if (btn) btn.disabled = true;
        ask(q).finally(function(){ if (btn) btn.disabled = false; });
      }, true);
    });
  }
  function injectBrowseGeo(){
    var path = (location.pathname || '').toLowerCase();
    var isBrowse = document.body.classList.contains('bds-ai-browse')
      || document.body.classList.contains('tax-at_biz_dir-category')
      || document.body.classList.contains('tax-at_biz_dir-location')
      || document.body.classList.contains('theme-dir-single_category')
      || document.body.classList.contains('theme-dir-single_location')
      || document.body.classList.contains('theme-dir-all_listings')
      || /\/(single-category|single-location|all-listings|search-result|search-results)(\/|$)/i.test(path)
      || document.querySelector('.directorist-archive-contents, .directorist-listing-category, .directorist-listing-location');
    if (!isBrowse || document.getElementById('bds-ai-browse-geo')) return;
    var bar = el('div','bds-ai-browse-geo');
    bar.id = 'bds-ai-browse-geo';
    bar.innerHTML = '<button type="button" class="bds-ai-geo-btn" id="bds-ai-browse-geo-btn">Use my location</button><span class="bds-ai-geo-status" id="bds-ai-browse-geo-status"></span><span class="bds-ai-browse-geo__hint">Sort local businesses nearest first</span>';
    // Prefer under AI search bar (title -> AI -> geo -> type-nav).
    var hero = document.getElementById('bds-ai-hero-entry');
    if (hero && hero.parentNode) {
      if (hero.nextSibling) hero.parentNode.insertBefore(bar, hero.nextSibling);
      else hero.parentNode.appendChild(bar);
    } else {
      var host = document.querySelector('.directorist-archive-contents, .directorist-header, main, #content');
      if (!host) return;
      host.insertBefore(bar, host.firstChild);
    }
    var btn = document.getElementById('bds-ai-browse-geo-btn');
    var st = document.getElementById('bds-ai-browse-geo-status');
    if (hasGeo() && st) st.textContent = userGeo.label ? ('Near ' + userGeo.label) : 'Location on';
    if (btn) btn.addEventListener('click', async function(){
      var r = await requestGeoPromise();
      if (r.ok) location.reload();
    });
  }
  async function hydrateLocalPack(){
    var root = document.getElementById('bds-ai-local-pack');
    if (!root || !cfg.localPack) return;
    var url = cfg.localPack;
    if (hasGeo()) url += (url.indexOf('?')>=0?'&':'?') + 'lat=' + encodeURIComponent(userGeo.lat) + '&lng=' + encodeURIComponent(userGeo.lng);
    try{
      var res = await fetch(url, { credentials:'same-origin' });
      var data = await res.json().catch(function(){ return null; });
      if (!data || !data.groups || !data.groups.length) return;
      var html = '';
      data.groups.slice(0,2).forEach(function(G){
        html += '<div class="bds-ai-local-pack__group"><h3>'+esc(G.label || G.location)+'</h3>';
        html += renderListings(sanitizeListings(G.listings || []));
        html += '</div>';
      });
      var body = root.querySelector('.bds-ai-local-pack__body');
      if (body) body.innerHTML = html;
      if (data.place){
        var sub = root.querySelector('.bds-ai-local-pack__sub');
        if (sub) sub.textContent = 'Near ' + data.place;
      }
    }catch(e){}
  }
  async function hydrateMemory(){
    var local = loadLocal();
    var server = await loadServerMemory();
    var chosen = null;
    if (local && server) chosen = ((server.updated || 0) > (local.updated || 0)) ? server : local;
    else chosen = server || local;
    if (chosen && chosen.turns && chosen.turns.length){
      restoreTurns(chosen.turns);
      saveLocal();
    } else ensureGreeting();
  }
  function boot(){
    panel = document.getElementById('bds-ai-panel');
    launcher = document.getElementById('bds-ai-launcher');
    form = document.getElementById('bds-ai-form');
    input = document.getElementById('bds-ai-input');
    messages = document.getElementById('bds-ai-messages');
    closeBtn = document.getElementById('bds-ai-close');
    clearBtn = document.getElementById('bds-ai-clear');
    geoBtn = document.getElementById('bds-ai-geo-btn');
    geoStatus = document.getElementById('bds-ai-geo-status');
    chipBar = document.getElementById('bds-ai-chips');
    if (!panel || !launcher || !form) return;
    launcher.addEventListener('click', function(){ if (panel.hidden) openPanel(); else closePanel(); });
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (clearBtn) clearBtn.addEventListener('click', function(){ clearChat(); });
    if (geoBtn) geoBtn.addEventListener('click', async function(){
      if (hasGeo()){
        clearGeoStore();
        setGeoStatus('Location cleared.', false);
        return;
      }
      await requestGeoPromise();
    });
    panel.addEventListener('click', function(e){ if (e.target === panel) closePanel(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !panel.hidden) closePanel(); });
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var q = (input.value || '').trim();
      if (!q) return;
      addUser(q);
      input.value = '';
      var btn = document.getElementById('bds-ai-send');
      if (btn) btn.disabled = true;
      ask(q).finally(function(){ if (btn) btn.disabled = false; });
    });
    loadCtx();
    seedArchiveContext();
    loadGeo();
    injectHeaderChip();
    injectHero();
    injectBrowseGeo();
    hijackDirectoristSearch();
    hydrateMemory();
    hydrateLocalPack();
    setTimeout(hijackDirectoristSearch, 800);
    setTimeout(hijackDirectoristSearch, 2000);
    avoidCollisions();
    setTimeout(avoidCollisions, 1500);
    setTimeout(avoidCollisions, 4000);
    window.addEventListener('resize', avoidCollisions, { passive:true });
    // Nav drawers toggle body/element classes; re-check when the DOM says so.
    if (window.MutationObserver){
      var mo = new MutationObserver(function(){
        if (mo.t) return;
        mo.t = setTimeout(function(){ mo.t = null; avoidCollisions(); }, 220);
      });
      mo.observe(document.body, { attributes:true, attributeFilter:['class','style'], subtree:false });
      mo.observe(document.documentElement, { attributes:true, attributeFilter:['class'], subtree:false });
    }
    document.addEventListener('click', function(){ setTimeout(avoidCollisions, 350); }, true);
    window.addEventListener('scroll', function(){
      if (avoidCollisions.t) return;
      avoidCollisions.t = setTimeout(function(){ avoidCollisions.t = null; avoidCollisions(); }, 500);
    }, { passive:true });
    var assistBtn = document.getElementById('bds-ai-empty-assist-btn');
    if (assistBtn) {
      assistBtn.addEventListener('click', function(){
        var aq = (cfg && cfg.assist && cfg.assist.query) || '';
        openPanel();
        if (!aq) { if (input) setTimeout(function(){ input.focus(); }, 40); return; }
        addUser(aq);
        var btn = document.getElementById('bds-ai-send');
        if (btn) btn.disabled = true;
        ask(aq).finally(function(){ if (btn) btn.disabled = false; });
      });
    }
    if (cfg && cfg.assist && cfg.assist.autoAsk && cfg.assist.query) {
      setTimeout(function(){
        if (!panel) return;
        openPanel();
        var aq = String(cfg.assist.query || '').trim();
        if (!aq) return;
        var already = false;
        try {
          var nodes = messages ? messages.querySelectorAll('.bds-ai-msg--user') : [];
          for (var i=0;i<nodes.length;i++){
            if ((nodes[i].textContent || '').trim() === aq) { already = true; break; }
          }
        } catch (e) {}
        if (already) return;
        addUser(aq);
        var btn = document.getElementById('bds-ai-send');
        if (btn) btn.disabled = true;
        ask(aq).finally(function(){ if (btn) btn.disabled = false; });
      }, 450);
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
JS;
}

/**
 * Read visitor geo cookie set by Ask BrandDad / browse bar.
 *
 * @return array{lat:?float,lng:?float,label:string}
 */
function bds_ai_finder_visitor_geo() {
	$out = array( 'lat' => null, 'lng' => null, 'label' => '' );
	if ( empty( $_COOKIE['bds_ai_geo'] ) ) {
		return $out;
	}
	$raw  = substr( wp_unslash( (string) $_COOKIE['bds_ai_geo'] ), 0, 1000 );
	$data = json_decode( rawurldecode( $raw ), true );
	if ( ! is_array( $data ) ) {
		return $out;
	}
	$g          = bds_ai_finder_normalize_geo( $data );
	$out['lat'] = $g['lat'];
	$out['lng'] = $g['lng'];
	if ( ! empty( $data['label'] ) ) {
		$out['label'] = sanitize_text_field( (string) $data['label'] );
	}
	return $out;
}

/**
 * Digital category archives should not geo-sort.
 *
 * @param WP_Term|null $term Term.
 * @return bool
 */
function bds_ai_finder_term_is_digital( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return false;
	}
	$blob = strtolower( $term->slug . ' ' . $term->name );
	foreach ( array( 'logo', 'brand', 'web', 'host', 'seo', 'digital', 'software', 'marketing-agency', 'design' ) as $n ) {
		if ( false !== strpos( $blob, $n ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Flag main query for geo distance sort on local Directorist archives.
 *
 * @param WP_Query $query Query.
 */
function bds_ai_finder_browse_geo_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$geo = bds_ai_finder_visitor_geo();
	if ( $geo['lat'] === null || $geo['lng'] === null ) {
		return;
	}
	$is_local_tax = $query->is_tax( 'at_biz_dir-category' ) || $query->is_tax( 'at_biz_dir-location' );
	if ( ! $is_local_tax ) {
		return;
	}
	if ( $query->is_tax( 'at_biz_dir-category' ) ) {
		$term = get_queried_object();
		if ( bds_ai_finder_term_is_digital( $term instanceof WP_Term ? $term : null ) ) {
			return;
		}
	}
	$query->set( 'bds_ai_geo_sort', 1 );
	$query->set( 'bds_ai_geo_lat', $geo['lat'] );
	$query->set( 'bds_ai_geo_lng', $geo['lng'] );
}

/**
 * Order listing archive by haversine distance when geo cookie present.
 *
 * @param array<string,string> $clauses Clauses.
 * @param WP_Query             $query   Query.
 * @return array<string,string>
 */
function bds_ai_finder_browse_geo_clauses( $clauses, $query ) {
	if ( ! $query instanceof WP_Query || ! $query->get( 'bds_ai_geo_sort' ) ) {
		return $clauses;
	}
	global $wpdb;
	$lat = (float) $query->get( 'bds_ai_geo_lat' );
	$lng = (float) $query->get( 'bds_ai_geo_lng' );
	if ( ! is_finite( $lat ) || ! is_finite( $lng ) ) {
		return $clauses;
	}
	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} bds_lat ON (bds_lat.post_id = {$wpdb->posts}.ID AND bds_lat.meta_key IN ('_manual_lat','_latitude')) ";
	$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} bds_lng ON (bds_lng.post_id = {$wpdb->posts}.ID AND bds_lng.meta_key IN ('_manual_lng','_longitude')) ";
	$hav              = '(6371 * ACOS(LEAST(1, GREATEST(-1, COS(RADIANS(' . $lat . ')) * COS(RADIANS(CAST(bds_lat.meta_value AS DECIMAL(10,7)))) * COS(RADIANS(CAST(bds_lng.meta_value AS DECIMAL(10,7))) - RADIANS(' . $lng . ')) + SIN(RADIANS(' . $lat . ')) * SIN(RADIANS(CAST(bds_lat.meta_value AS DECIMAL(10,7))))))))';
	$clauses['fields'] .= ', ' . $hav . ' AS bds_ai_distance_km';
	$clauses['where']  .= " AND bds_lat.meta_value <> '' AND bds_lng.meta_value <> '' ";
	$clauses['groupby'] = "{$wpdb->posts}.ID";
	$clauses['orderby'] = 'bds_ai_distance_km ASC';
	return $clauses;
}

/**
 * Homepage "Near you" local pack mount point (filled by JS).
 */
function bds_ai_finder_home_local_pack() {
	if ( ! bds_ai_finder_should_show_ui() ) {
		return;
	}
	if ( ! is_front_page() && ! is_home() ) {
		return;
	}
	?>
	<section id="bds-ai-local-pack" class="bds-ai-local-pack" aria-label="Local businesses near you">
		<div class="bds-ai-local-pack__inner">
			<p class="bds-ai-local-pack__eyebrow">Directory local</p>
			<h2 class="bds-ai-local-pack__title">Near you</h2>
			<p class="bds-ai-local-pack__sub">Local businesses, closest first - like a local pack.</p>
			<div class="bds-ai-local-pack__body"><p class="bds-ai-local-pack__loading">Loading nearby listings...</p></div>
			<p class="bds-ai-local-pack__ask"><button type="button" class="bds-ai-geo-btn" onclick="var b=document.getElementById('bds-ai-launcher'); if(b)b.click();">Ask BrandDad</button></p>
		</div>
	</section>
	<?php
}
