<?php
/**
 * Plugin Name: BrandDad Thaddeus LinkedIn Master Inventory
 * Description: Refreshable, queryable asset inventory + review queue for Thaddeus McCollum personal LinkedIn (organic inbound only). Make.com scenario 1717130 consumes this API. No auto-publish.
 * Version: 1.0.1
 *
 * Deploy: public_html/branddad.social/wp-content/mu-plugins/bds-thaddeus-linkedin-inventory-mu.php
 *
 * Public GET:
 *   /wp-json/bds-li/v1/config
 *   /wp-json/bds-li/v1/inventory
 *   /wp-json/bds-li/v1/pick
 *
 * Secret (header X-BDS-LI-Secret or ?secret=) POST/GET:
 *   /wp-json/bds-li/v1/refresh
 *   /wp-json/bds-li/v1/queue
 *   /wp-json/bds-li/v1/validate
 *   /wp-json/bds-li/v1/history
 *
 * Mode: option bds_li_publish_mode = review|automatic (default review).
 * Automatic still requires LinkedIn OAuth in Make — this MU never posts to LinkedIn.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_LI_INV_VER' ) ) {
	return;
}
define( 'BDS_LI_INV_VER', '1.0.1' );
define( 'BDS_LI_INV_NS', 'bds-li/v1' );
define( 'BDS_LI_INV_CACHE', 'bds_li_inventory_cache_v1' );
define( 'BDS_LI_INV_TTL', 4 * HOUR_IN_SECONDS );

add_action( 'rest_api_init', 'bds_li_register_routes' );
add_action( 'admin_menu', 'bds_li_admin_menu' );
add_action( 'admin_init', 'bds_li_admin_init' );

/**
 * @return string
 */
function bds_li_secret() {
	$secret = (string) get_option( 'bds_li_make_secret', '' );
	if ( $secret === '' ) {
		$secret = wp_generate_password( 32, false, false );
		update_option( 'bds_li_make_secret', $secret, false );
	}
	return $secret;
}

/**
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function bds_li_auth( $request ) {
	$secret = bds_li_secret();
	$given  = (string) $request->get_header( 'x-bds-li-secret' );
	if ( $given === '' ) {
		$given = (string) $request->get_param( 'secret' );
	}
	if ( $given === '' || ! hash_equals( $secret, $given ) ) {
		return new WP_Error( 'bds_li_forbidden', 'Invalid LinkedIn inventory secret.', array( 'status' => 401 ) );
	}
	return true;
}

function bds_li_register_routes() {
	$pub = array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
	);
	register_rest_route( BDS_LI_INV_NS, '/config', array_merge( $pub, array( 'callback' => 'bds_li_rest_config' ) ) );
	register_rest_route( BDS_LI_INV_NS, '/inventory', array_merge( $pub, array( 'callback' => 'bds_li_rest_inventory' ) ) );
	register_rest_route(
		BDS_LI_INV_NS,
		'/pick',
		array_merge(
			$pub,
			array(
				'callback' => 'bds_li_rest_pick',
				'args'     => array(
					'pillar'    => array( 'type' => 'string', 'required' => false ),
					'objective' => array( 'type' => 'string', 'required' => false ),
					'audience'  => array( 'type' => 'string', 'required' => false ),
				),
			)
		)
	);

	$priv = array(
		'permission_callback' => 'bds_li_auth',
	);
	register_rest_route(
		BDS_LI_INV_NS,
		'/refresh',
		array_merge( $priv, array( 'methods' => array( 'GET', 'POST' ), 'callback' => 'bds_li_rest_refresh' ) )
	);
	register_rest_route(
		BDS_LI_INV_NS,
		'/queue',
		array(
			array_merge( $priv, array( 'methods' => 'GET', 'callback' => 'bds_li_rest_queue_get' ) ),
			array_merge( $priv, array( 'methods' => 'POST', 'callback' => 'bds_li_rest_queue_post' ) ),
		)
	);
	register_rest_route(
		BDS_LI_INV_NS,
		'/validate',
		array_merge( $priv, array( 'methods' => 'POST', 'callback' => 'bds_li_rest_validate' ) )
	);
	register_rest_route(
		BDS_LI_INV_NS,
		'/history',
		array(
			array_merge( $priv, array( 'methods' => 'GET', 'callback' => 'bds_li_rest_history_get' ) ),
			array_merge( $priv, array( 'methods' => 'POST', 'callback' => 'bds_li_rest_history_post' ) ),
		)
	);
}

/**
 * @return array<string,mixed>
 */
function bds_li_config() {
	$mode = (string) get_option( 'bds_li_publish_mode', 'review' );
	if ( ! in_array( $mode, array( 'review', 'automatic' ), true ) ) {
		$mode = 'review';
	}
	$weights = get_option( 'bds_li_weights', array() );
	if ( ! is_array( $weights ) || ! $weights ) {
		$weights = array(
			'awareness'          => 35,
			'education_diy'      => 25,
			'directory'          => 12,
			'ask_branddad'       => 8,
			'hosttech'           => 8,
			'referral'           => 4,
			'dfy_offer'          => 8,
		);
	}
	return array(
		'version'            => BDS_LI_INV_VER,
		'voice'              => 'thaddeus_mccollum_personal',
		'destination'        => 'thaddeus_mccollum_personal_user_confirmed',
		'linkedin_destination_confirmed_by_user' => true,
		'linkedin_destination_not_company_page' => true,
		'make_linkedin_module_inspected' => true,
		'organic_inbound_only' => true,
		'outreach_allowed'   => false,
		'dms_allowed'        => false,
		'connection_requests_allowed' => false,
		'publish_mode'       => $mode,
		'review_mode'        => ( 'review' === $mode ),
		'automatic_ready'    => false,
		'cadence'            => array(
			'note'     => 'Make scenario 1717130: Daily 09:00–09:01 (interval 900s + restrict). Scenario isPaused=true. Do not treat as automatic publish.',
			'timezone' => 'America/Kentucky/Louisville',
			'make_restrict' => '09:00-09:01 every day',
			'verified_from_make' => true,
		),
		'utm'                => array(
			'source'   => 'thaddeus_linkedin',
			'medium'   => 'organic_linkedin',
			'campaign' => 'bds_li_master',
		),
		'partnero'           => array(
			'preserve_ref' => true,
			'do_not_invent_ref' => true,
			'portal'       => 'https://affiliates.branddad.social/',
			'commission_verified' => '30% lifetime recurring on eligible referrals (Partnero program_commission_structure + Social Earn 30% copy)',
		),
		'weights'            => $weights,
		'banned_path_needles' => bds_li_banned_needles(),
		'make_scenario'      => 1717130,
		'make_org'           => 375589,
		'linkedin_publish_gated' => true,
	);
}

/**
 * @return string[]
 */
function bds_li_banned_needles() {
	return array(
		'linkedin-outreach',
		'outreach-that-books',
		'cold' . '-outreach',
		'bhgift',
		'localhost',
		'127.0.0.1',
		'.local/',
		'staging.',
		'/wp-admin',
		'[link]',
	);
}

/**
 * @param string $url URL.
 * @return bool
 */
function bds_li_url_banned( $url ) {
	$hay = strtolower( (string) $url );
	foreach ( bds_li_banned_needles() as $n ) {
		if ( false !== strpos( $hay, $n ) ) {
			return true;
		}
	}
	return false;
}

/**
 * @param string $url URL.
 * @return string
 */
function bds_li_utm( $url ) {
	$url = (string) $url;
	if ( $url === '' || false !== strpos( $url, 'wa.me/' ) ) {
		return $url;
	}
	if ( false !== strpos( $url, 'utm_source=' ) ) {
		return $url;
	}
	return add_query_arg(
		array(
			'utm_source'   => 'thaddeus_linkedin',
			'utm_medium'   => 'organic_linkedin',
			'utm_campaign' => 'bds_li_master',
		),
		$url
	);
}

/**
 * @return array<int,array<string,mixed>>
 */
function bds_li_hubs() {
	return array(
		array(
			'id' => 'hub-social-home', 'type' => 'hub', 'pillar' => 'ecosystem', 'site' => 'social',
			'title' => 'BrandDad Social', 'url' => 'https://branddad.social/', 'cta' => 'See how BrandDad Social helps with visibility and trust',
			'offer' => 'Services, Learning Center, Health Check', 'status' => 'active',
		),
		array(
			'id' => 'hub-services', 'type' => 'hub', 'pillar' => 'dfy', 'site' => 'social',
			'title' => 'BrandDad Social services', 'url' => 'https://branddad.social/services/', 'cta' => 'Pick a scoped bottleneck',
			'offer' => 'Local & Web, AI Ads, growth systems, and LinkedIn services', 'status' => 'active',
		),
		array(
			'id' => 'hub-health', 'type' => 'tool', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Check your website', 'url' => 'https://branddad.social/check-your-website/', 'cta' => 'Run a free public-signal site check',
			'offer' => 'Free Health Check — not ads, not a listing', 'status' => 'active',
		),
		array(
			'id' => 'hub-ai-ads', 'type' => 'hub', 'pillar' => 'ads', 'site' => 'social',
			'title' => 'AI Ads', 'url' => 'https://branddad.social/ai-ads/', 'cta' => 'See how AI Ads management works',
			'offer' => 'Management fees on BrandDad; ad spend paid by you to the networks', 'status' => 'active',
		),
		array(
			'id' => 'hub-learn', 'type' => 'learn', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Learning Center', 'url' => 'https://branddad.social/learning-center/', 'cta' => 'Learn first, then decide',
			'offer' => 'Explained, guides, courses, playbooks', 'status' => 'active',
		),
		array(
			'id' => 'hub-guides', 'type' => 'learn', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Guides', 'url' => 'https://branddad.social/guides/', 'cta' => 'Read a practical guide',
			'offer' => 'DIY how-tos', 'status' => 'active',
		),
		array(
			'id' => 'hub-explained', 'type' => 'learn', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Explained', 'url' => 'https://branddad.social/explained/', 'cta' => 'Get a plain-English explanation',
			'offer' => 'Concept explainers', 'status' => 'active',
		),
		array(
			'id' => 'hub-courses', 'type' => 'learn', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Courses', 'url' => 'https://branddad.social/courses/', 'cta' => 'Browse courses',
			'offer' => 'Structured courses', 'status' => 'active',
		),
		array(
			'id' => 'hub-books', 'type' => 'learn', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Playbooks', 'url' => 'https://branddad.social/books/', 'cta' => 'See playbooks',
			'offer' => 'Paid playbooks after checkout — full books are not pasted publicly', 'status' => 'active',
		),
		array(
			'id' => 'hub-assess', 'type' => 'learn', 'pillar' => 'diy', 'site' => 'social',
			'title' => 'Assessments', 'url' => 'https://branddad.social/assessments/', 'cta' => 'Take an assessment',
			'offer' => 'Self-checks', 'status' => 'active',
		),
		array(
			'id' => 'hub-directory', 'type' => 'directory', 'pillar' => 'directory', 'site' => 'directory',
			'title' => 'BrandDad Directory', 'url' => 'https://directory.branddad.social/', 'cta' => 'Browse or list a business',
			'offer' => 'WhatsApp business directory; members save 10% on eligible BrandDad services', 'status' => 'active',
		),
		array(
			'id' => 'hub-listings', 'type' => 'directory', 'pillar' => 'directory', 'site' => 'directory',
			'title' => 'Directory listings', 'url' => 'https://directory.branddad.social/all-listings/', 'cta' => 'Browse live listings',
			'offer' => 'Published listings only — never invent menus or ratings', 'status' => 'active',
		),
		array(
			'id' => 'hub-add-listing', 'type' => 'directory', 'pillar' => 'directory', 'site' => 'directory',
			'title' => 'List your business', 'url' => 'https://directory.branddad.social/add-listing/', 'cta' => 'List or claim your business',
			'offer' => 'Directory listing', 'status' => 'active',
		),
		array(
			'id' => 'hub-membership', 'type' => 'directory', 'pillar' => 'directory', 'site' => 'directory',
			'title' => 'Directory membership', 'url' => 'https://directory.branddad.social/membership/', 'cta' => 'See membership',
			'offer' => '10% off eligible network services', 'status' => 'active',
		),
		array(
			'id' => 'hub-ask', 'type' => 'tool', 'pillar' => 'ask', 'site' => 'directory',
			'title' => 'Ask BrandDad', 'url' => 'https://directory.branddad.social/', 'cta' => 'Ask BrandDad on the Directory',
			'offer' => 'On-site Ask BrandDad chat (not a invented /ask URL)', 'status' => 'active',
		),
		array(
			'id' => 'hub-co', 'type' => 'hub', 'pillar' => 'brand', 'site' => 'co',
			'title' => 'BrandDad.co AI Design Studio', 'url' => 'https://branddad.co/get-started/', 'cta' => 'Start a logo or website brief',
			'offer' => 'Logos and websites live on branddad.co', 'status' => 'active',
		),
		array(
			'id' => 'hub-logos', 'type' => 'hub', 'pillar' => 'brand', 'site' => 'co',
			'title' => 'Logo design', 'url' => 'https://branddad.co/product-category/logo-design/', 'cta' => 'Browse logo options',
			'offer' => 'AI logos', 'status' => 'active',
		),
		array(
			'id' => 'hub-websites', 'type' => 'hub', 'pillar' => 'brand', 'site' => 'co',
			'title' => 'Web design', 'url' => 'https://branddad.co/product-category/web-design/', 'cta' => 'Browse website options',
			'offer' => 'Premium AI-made sites', 'status' => 'active',
		),
		array(
			'id' => 'hub-host-shop', 'type' => 'hub', 'pillar' => 'hosting', 'site' => 'hosttech',
			'title' => 'HostTech hosting', 'url' => 'https://hosttech.net/shopping/', 'cta' => 'Compare HostTech plans',
			'offer' => 'Hosting and domains — HostTech, not Social', 'status' => 'active',
		),
		array(
			'id' => 'hub-domains', 'type' => 'hub', 'pillar' => 'hosting', 'site' => 'hosttech',
			'title' => 'HostTech domains', 'url' => 'https://hosttech.net/domains/', 'cta' => 'Register a domain',
			'offer' => 'Domains', 'status' => 'active',
		),
		array(
			'id' => 'hub-find', 'type' => 'tool', 'pillar' => 'hosting', 'site' => 'hosttech',
			'title' => 'HostTech plan guide', 'url' => 'https://hosttech.net/find/', 'cta' => 'Find a sane hosting plan',
			'offer' => 'Question-led plan guide', 'status' => 'active',
		),
		array(
			'id' => 'hub-affiliate', 'type' => 'referral', 'pillar' => 'referral', 'site' => 'affiliate',
			'title' => 'BrandDad Affiliate Program', 'url' => 'https://affiliates.branddad.social/', 'cta' => 'Join the affiliate program',
			'offer' => '30% lifetime commission on eligible referrals (Partnero)', 'status' => 'active',
		),
		array(
			'id' => 'hub-gift', 'type' => 'hub', 'pillar' => 'ecosystem', 'site' => 'social',
			'title' => 'Gift cards', 'url' => 'https://branddad.social/gift-cards/', 'cta' => 'See BrandDad gift cards',
			'offer' => 'Gift cards — no supplier names', 'status' => 'active',
		),
		array(
			'id' => 'hub-contact', 'type' => 'hub', 'pillar' => 'ecosystem', 'site' => 'social',
			'title' => 'Contact BrandDad Social', 'url' => 'https://branddad.social/contact-us/', 'cta' => 'Contact us',
			'offer' => 'Human inbound', 'status' => 'active',
		),
	);
}

/**
 * @return array<int,array<string,mixed>>
 */
function bds_li_woo_assets() {
	$out = array();
	if ( ! function_exists( 'wc_get_products' ) ) {
		return $out;
	}
	$ids = wc_get_products(
		array(
			'status' => 'publish',
			'limit'  => 80,
			'return' => 'ids',
		)
	);
	foreach ( (array) $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		$slug = $p->get_slug();
		$url  = get_permalink( $id );
		if ( bds_li_url_banned( $url ) || bds_li_url_banned( $slug ) ) {
			continue;
		}
		$price = $p->get_price();
		$out[] = array(
			'id'     => 'woo-' . $id,
			'type'   => 'product',
			'pillar' => 'dfy',
			'site'   => 'social',
			'title'  => $p->get_name(),
			'slug'   => $slug,
			'url'    => $url,
			'cta'    => 'See the scoped offer',
			'offer'  => wp_strip_all_tags( (string) $p->get_short_description() ),
			'price'  => is_numeric( $price ) ? (string) $price : '',
			'status' => 'active',
		);
	}
	return $out;
}

/**
 * @return array<int,array<string,mixed>>
 */
function bds_li_learn_assets() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	$out = array();
	foreach ( $posts as $post ) {
		$url = get_permalink( $post );
		if ( bds_li_url_banned( $url ) || bds_li_url_banned( $post->post_name ) ) {
			continue;
		}
		$out[] = array(
			'id'     => 'post-' . $post->ID,
			'type'   => 'learn',
			'pillar' => 'diy',
			'site'   => 'social',
			'title'  => get_the_title( $post ),
			'slug'   => $post->post_name,
			'url'    => $url,
			'cta'    => 'Read the guide',
			'offer'  => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ), 24 ),
			'status' => 'active',
			'modified' => get_the_modified_date( 'c', $post ),
		);
	}
	return $out;
}

/**
 * @return array<string,mixed>
 */
function bds_li_directory_facts() {
	$cached = get_transient( 'bds_li_dir_facts' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$resp = wp_remote_get(
		'https://directory.branddad.social/wp-json/bds-stats/v1/summary',
		array( 'timeout' => 12, 'redirection' => 2 )
	);
	$facts = array(
		'source' => 'https://directory.branddad.social/wp-json/bds-stats/v1/summary',
		'ok'     => false,
	);
	if ( ! is_wp_error( $resp ) && (int) wp_remote_retrieve_response_code( $resp ) === 200 ) {
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( is_array( $body ) && ! empty( $body['ok'] ) ) {
			$facts = array(
				'source'              => 'https://directory.branddad.social/wp-json/bds-stats/v1/summary',
				'ok'                  => true,
				'published_listings'  => isset( $body['published_listings'] ) ? (int) $body['published_listings'] : 0,
				'categories'          => isset( $body['categories'] ) ? (int) $body['categories'] : 0,
				'locations'           => isset( $body['locations'] ) ? (int) $body['locations'] : 0,
				'never_invent'        => array( 'menus', 'ratings', 'BEST', 'rankings' ),
			);
		}
	}
	set_transient( 'bds_li_dir_facts', $facts, 30 * MINUTE_IN_SECONDS );
	return $facts;
}

/**
 * @param bool $force Force rebuild.
 * @return array<string,mixed>
 */
function bds_li_build_inventory( $force = false ) {
	if ( ! $force ) {
		$cached = get_transient( BDS_LI_INV_CACHE );
		if ( is_array( $cached ) && ! empty( $cached['assets'] ) ) {
			$cached['cache'] = 'hit';
			return $cached;
		}
	}
	$assets = array();
	foreach ( array_merge( bds_li_hubs(), bds_li_woo_assets(), bds_li_learn_assets() ) as $row ) {
		$row['url'] = isset( $row['url'] ) ? bds_li_utm( $row['url'] ) : '';
		if ( $row['url'] === '' || bds_li_url_banned( $row['url'] ) ) {
			$row['status'] = 'inactive';
		}
		$assets[] = $row;
	}
	$active = 0;
	foreach ( $assets as $a ) {
		if ( ( $a['status'] ?? '' ) === 'active' ) {
			++$active;
		}
	}
	$payload = array(
		'ok'              => true,
		'generated_at'    => gmdate( 'c' ),
		'ttl_seconds'     => BDS_LI_INV_TTL,
		'refresh_policy'  => 'Intelligent: 4h cache; Directory stats 30m; force via /refresh. Not a full crawl per post.',
		'counts'          => array(
			'total'  => count( $assets ),
			'active' => $active,
			'hubs'   => count( bds_li_hubs() ),
		),
		'directory_facts' => bds_li_directory_facts(),
		'config'          => bds_li_config(),
		'assets'          => $assets,
		'cache'           => 'miss',
	);
	set_transient( BDS_LI_INV_CACHE, $payload, BDS_LI_INV_TTL );
	return $payload;
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_config( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	$c = bds_li_config();
	$c['secret_configured'] = (string) get_option( 'bds_li_make_secret', '' ) !== '';
	return rest_ensure_response( $c );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_inventory( $request ) {
	$force = (string) $request->get_param( 'force' ) === '1';
	return rest_ensure_response( bds_li_build_inventory( $force ) );
}

/**
 * Weighted pick without crawling.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_pick( $request ) {
	$inv     = bds_li_build_inventory( false );
	$history = get_option( 'bds_li_history', array() );
	$recent  = array();
	if ( is_array( $history ) ) {
		foreach ( array_slice( $history, -40 ) as $h ) {
			if ( ! empty( $h['asset_id'] ) ) {
				$recent[] = $h['asset_id'];
			}
		}
	}
	$want_pillar = sanitize_key( (string) $request->get_param( 'pillar' ) );
	$objective   = sanitize_key( (string) $request->get_param( 'objective' ) );
	if ( $objective === '' ) {
		$objective = bds_li_weighted_objective();
	}
	$pool = array();
	foreach ( $inv['assets'] as $a ) {
		if ( ( $a['status'] ?? '' ) !== 'active' ) {
			continue;
		}
		if ( $want_pillar && ( $a['pillar'] ?? '' ) !== $want_pillar ) {
			continue;
		}
		if ( in_array( $a['id'], $recent, true ) && count( $inv['assets'] ) > 8 ) {
			continue;
		}
		$pool[] = $a;
	}
	if ( ! $pool ) {
		$pool = array_values(
			array_filter(
				$inv['assets'],
				static function ( $a ) {
					return ( $a['status'] ?? '' ) === 'active';
				}
			)
		);
	}
	$asset = $pool ? $pool[ array_rand( $pool ) ] : null;
	$include_link = ! in_array( $objective, array( 'awareness' ), true );
	if ( 'awareness' === $objective && wp_rand( 1, 100 ) <= 70 ) {
		$include_link = false;
	}
	$campaign_id = 'bdsli-' . gmdate( 'Ymd' ) . '-' . wp_generate_password( 8, false, false );
	return rest_ensure_response(
		array(
			'ok'           => (bool) $asset,
			'campaign_id'  => $campaign_id,
			'objective'    => $objective,
			'include_link' => $include_link,
			'audience'     => sanitize_text_field( (string) $request->get_param( 'audience' ) ) ?: 'owners_operators',
			'asset'        => $asset,
			'directory_facts' => $inv['directory_facts'],
			'publish_mode' => bds_li_config()['publish_mode'],
			'voice_rules'  => array(
				'person' => 'Thaddeus McCollum, first person, practical, no hype',
				'never'  => array( 'invented URLs', 'testimonials', 'earnings stories', 'ranking promises', 'supplier names', 'outreach/DMs', '[LINK]', 'localhost' ),
			),
		)
	);
}

/**
 * @return string
 */
function bds_li_weighted_objective() {
	$w = bds_li_config()['weights'];
	$sum = 0;
	foreach ( $w as $v ) {
		$sum += (int) $v;
	}
	$r = wp_rand( 1, max( 1, $sum ) );
	$run = 0;
	foreach ( $w as $k => $v ) {
		$run += (int) $v;
		if ( $r <= $run ) {
			return (string) $k;
		}
	}
	return 'awareness';
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_refresh( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	delete_transient( BDS_LI_INV_CACHE );
	delete_transient( 'bds_li_dir_facts' );
	$inv = bds_li_build_inventory( true );
	$inv['cache'] = 'forced';
	return rest_ensure_response( $inv );
}

/**
 * @param array<string,mixed> $draft Draft.
 * @return array{ok:bool,errors:string[],normalized:array}
 */
function bds_li_validate_draft( $draft ) {
	$errors = array();
	$text   = isset( $draft['post'] ) ? (string) $draft['post'] : (string) ( $draft['body'] ?? '' );
	$url    = isset( $draft['url'] ) ? (string) $draft['url'] : '';
	$low    = strtolower( $text . ' ' . $url );
	$needles = array( '[link]', 'localhost', '127.0.0.1', 'bhgift', 't.me/bhgift', 'i made $', 'testimonial', 'guaranteed ranking', 'guaranteed followers' );
	foreach ( $needles as $n ) {
		if ( false !== strpos( $low, $n ) ) {
			$errors[] = 'forbidden_string:' . $n;
		}
	}
	if ( $url !== '' ) {
		if ( bds_li_url_banned( $url ) ) {
			$errors[] = 'banned_url';
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$ok_hosts = array(
			'branddad.social', 'directory.branddad.social', 'branddad.co', 'hosttech.net',
			'affiliates.branddad.social', 'www.branddad.social',
		);
		if ( ! is_string( $host ) || ! in_array( strtolower( $host ), $ok_hosts, true ) ) {
			$errors[] = 'url_host_not_allowlisted';
		}
		$inv = bds_li_build_inventory( false );
		$known = false;
		$bare  = strtok( $url, '?' );
		foreach ( $inv['assets'] as $a ) {
			$au = isset( $a['url'] ) ? strtok( $a['url'], '?' ) : '';
			if ( $au && ( $au === $bare || untrailingslashit( $au ) === untrailingslashit( $bare ) ) ) {
				$known = true;
				break;
			}
		}
		if ( ! $known ) {
			$errors[] = 'url_not_in_inventory';
		}
	}
	if ( strlen( $text ) < 80 ) {
		$errors[] = 'post_too_short';
	}
	if ( strlen( $text ) > 2900 ) {
		$errors[] = 'post_too_long';
	}
	$hash = hash( 'sha256', strtolower( preg_replace( '/\s+/', ' ', $text ) ) );
	$history = get_option( 'bds_li_history', array() );
	$queue   = get_option( 'bds_li_queue', array() );
	foreach ( array_merge( is_array( $history ) ? $history : array(), is_array( $queue ) ? $queue : array() ) as $row ) {
		if ( ! empty( $row['body_hash'] ) && hash_equals( (string) $row['body_hash'], $hash ) ) {
			$errors[] = 'duplicate_body';
			break;
		}
		if ( ! empty( $row['campaign_id'] ) && ! empty( $draft['campaign_id'] ) && $row['campaign_id'] === $draft['campaign_id'] && ( $row['status'] ?? '' ) !== 'failed' ) {
			$errors[] = 'duplicate_campaign_id';
			break;
		}
	}
	return array(
		'ok'         => empty( $errors ),
		'errors'     => $errors,
		'body_hash'  => $hash,
		'normalized' => $draft,
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_validate( $request ) {
	$draft = $request->get_json_params();
	if ( ! is_array( $draft ) ) {
		$draft = $request->get_params();
	}
	return rest_ensure_response( bds_li_validate_draft( $draft ) );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_queue_get( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	$q = get_option( 'bds_li_queue', array() );
	return rest_ensure_response( array( 'ok' => true, 'count' => is_array( $q ) ? count( $q ) : 0, 'items' => $q ) );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_queue_post( $request ) {
	$draft = $request->get_json_params();
	if ( ! is_array( $draft ) ) {
		$draft = $request->get_params();
	}
	$idem = isset( $draft['idempotency_key'] ) ? (string) $draft['idempotency_key'] : (string) ( $draft['campaign_id'] ?? '' );
	$q    = get_option( 'bds_li_queue', array() );
	if ( ! is_array( $q ) ) {
		$q = array();
	}
	if ( $idem !== '' ) {
		foreach ( $q as $row ) {
			if ( ( $row['idempotency_key'] ?? '' ) === $idem || ( $row['campaign_id'] ?? '' ) === $idem ) {
				return rest_ensure_response( array( 'ok' => true, 'deduped' => true, 'item' => $row, 'publish' => false ) );
			}
		}
	}
	$check = bds_li_validate_draft( $draft );
	if ( ! $check['ok'] ) {
		return rest_ensure_response( array( 'ok' => false, 'publish' => false, 'errors' => $check['errors'] ) );
	}
	$mode = bds_li_config()['publish_mode'];
	$item = array(
		'queued_at'        => gmdate( 'c' ),
		'status'           => 'review',
		'publish'          => false,
		'publish_mode'     => $mode,
		'campaign_id'      => (string) ( $draft['campaign_id'] ?? ( 'bdsli-' . wp_generate_password( 10, false, false ) ) ),
		'idempotency_key'  => $idem,
		'body_hash'        => $check['body_hash'],
		'draft'            => $draft,
	);
	array_unshift( $q, $item );
	$q = array_slice( $q, 0, 250 );
	update_option( 'bds_li_queue', $q, false );
	return rest_ensure_response(
		array(
			'ok'      => true,
			'deduped' => false,
			'item'    => $item,
			'publish' => false,
			'note'    => 'REVIEW MODE: do not call LinkedIn create-post. Automatic mode is a config flip only after human approval + verified personal LinkedIn connection.',
		)
	);
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_history_get( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	$h = get_option( 'bds_li_history', array() );
	return rest_ensure_response( array( 'ok' => true, 'count' => is_array( $h ) ? count( $h ) : 0, 'items' => $h ) );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bds_li_rest_history_post( $request ) {
	$row = $request->get_json_params();
	if ( ! is_array( $row ) ) {
		$row = $request->get_params();
	}
	$row['logged_at'] = gmdate( 'c' );
	if ( empty( $row['published'] ) ) {
		$row['published'] = false;
	}
	$h = get_option( 'bds_li_history', array() );
	if ( ! is_array( $h ) ) {
		$h = array();
	}
	$cid = (string) ( $row['campaign_id'] ?? '' );
	if ( $cid !== '' ) {
		foreach ( $h as $existing ) {
			if ( ( $existing['campaign_id'] ?? '' ) === $cid && ! empty( $existing['published'] ) ) {
				return rest_ensure_response( array( 'ok' => true, 'deduped' => true, 'note' => 'already logged published — skip LinkedIn retry' ) );
			}
		}
	}
	array_unshift( $h, $row );
	update_option( 'bds_li_history', array_slice( $h, 0, 400 ), false );
	return rest_ensure_response( array( 'ok' => true, 'deduped' => false ) );
}

function bds_li_admin_menu() {
	add_management_page(
		'Thaddeus LinkedIn queue',
		'Thaddeus LinkedIn',
		'manage_options',
		'bds-li-queue',
		'bds_li_admin_page'
	);
}

function bds_li_admin_init() {
	register_setting( 'bds_li', 'bds_li_publish_mode' );
}

function bds_li_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['bds_li_mode'] ) && check_admin_referer( 'bds_li_mode' ) ) {
		$mode = sanitize_key( wp_unslash( $_POST['bds_li_mode'] ) );
		if ( in_array( $mode, array( 'review', 'automatic' ), true ) ) {
			update_option( 'bds_li_publish_mode', $mode, false );
			echo '<div class="updated"><p>Mode saved. Make.com must still gate LinkedIn on this value — automatic does not post by itself.</p></div>';
		}
	}
	$mode  = bds_li_config()['publish_mode'];
	$queue = get_option( 'bds_li_queue', array() );
	$secret = bds_li_secret();
	echo '<div class="wrap"><h1>Thaddeus LinkedIn (personal, organic)</h1>';
	echo '<p>Secret (Make HTTP header <code>X-BDS-LI-Secret</code>): <code>' . esc_html( $secret ) . '</code></p>';
	echo '<form method="post">';
	wp_nonce_field( 'bds_li_mode' );
	echo '<p>Publish mode: <select name="bds_li_mode">';
	echo '<option value="review"' . selected( $mode, 'review', false ) . '>review</option>';
	echo '<option value="automatic"' . selected( $mode, 'automatic', false ) . '>automatic</option>';
	echo '</select> <button class="button">Save</button></p></form>';
	echo '<p>Queue count: ' . esc_html( is_array( $queue ) ? (string) count( $queue ) : '0' ) . '</p>';
	echo '<ol>';
	if ( is_array( $queue ) ) {
		foreach ( array_slice( $queue, 0, 30 ) as $item ) {
			$d = isset( $item['draft'] ) && is_array( $item['draft'] ) ? $item['draft'] : array();
			echo '<li><strong>' . esc_html( (string) ( $item['campaign_id'] ?? '' ) ) . '</strong> ';
			echo esc_html( (string) ( $d['hook'] ?? '' ) ) . '<br><pre style="white-space:pre-wrap;max-width:900px">';
			echo esc_html( (string) ( $d['post'] ?? '' ) );
			echo '</pre></li>';
		}
	}
	echo '</ol></div>';
}
