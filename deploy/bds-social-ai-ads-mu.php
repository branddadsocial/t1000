<?php
/**
 * Plugin Name: BrandDad AI Ads Automation
 * Description: Customer-connected ad accounts + AI ad creative + campaign automation across Meta, LinkedIn, Pinterest, Google/YouTube, Bing, TikTok, X, Snapchat, Reddit and Yelp. Honest per-network status - never reports spend or "running" without a real API response.
 * Version: 1.0.3
 * Author: BrandDad Social
 *
 * Design rules baked in:
 *  - BrandDad never holds media spend. The customer pays each ad platform directly.
 *    Our SKUs bill for AI creative + campaign automation only.
 *  - Nothing is reported as live/running/spending unless the platform API said so.
 *  - Outreach features stay outside this paid-ads management module.
 *  - Secrets live in wp_options written by an admin, never in source control.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( defined( 'BDS_AAM_VER' ) ) {
	return;
}

define( 'BDS_AAM_VER', '1.0.3' );
define( 'BDS_AAM_CPT', 'bds_ad_campaign' );
define( 'BDS_AAM_GRAPH', 'v21.0' );

/* --------------------------- Network registry --------------------------- */

/**
 * Automation levels (shown verbatim to customers so nothing is oversold):
 *  full          Connect -> AI drafts -> build campaign in the ad account (paused) -> one-click activate.
 *  connect_draft Connect (OAuth) -> AI drafts -> export/push manually. Campaign build lands per-API.
 *  manual        No public self-serve ads API -> AI drafts + guided copy/paste.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_aam_networks() {
	static $nets = null;
	if ( null !== $nets ) {
		return $nets;
	}
	$nets = array(
		'facebook'  => array(
			'label'      => 'Facebook Ads',
			'short'      => 'Facebook',
			'group'      => 'meta',
			'automation' => 'full',
			'tier'       => 'starter',
			'creds'      => array( 'bds_aam_meta_app_id', 'bds_aam_meta_app_secret' ),
			'creds_help' => 'Meta app with Marketing API + ads_management. developers.facebook.com -> My Apps -> Settings -> Basic (App ID + App Secret), then add the Marketing API product.',
			'note'       => 'Campaigns are created paused in your own Meta ad account. You approve before anything spends.',
		),
		'instagram' => array(
			'label'      => 'Instagram Ads',
			'short'      => 'Instagram',
			'group'      => 'meta',
			'automation' => 'full',
			'tier'       => 'starter',
			'creds'      => array( 'bds_aam_meta_app_id', 'bds_aam_meta_app_secret' ),
			'creds_help' => 'Same Meta app as Facebook Ads. Instagram placements run through the linked Meta ad account.',
			'note'       => 'Delivered as Instagram placements on the same Meta ad account - one connection covers both.',
		),
		'linkedin'  => array(
			'label'      => 'LinkedIn Ads',
			'short'      => 'LinkedIn',
			'group'      => 'linkedin',
			'automation' => 'connect_draft',
			'tier'       => 'growth',
			'creds'      => array( 'bds_aam_linkedin_client_id', 'bds_aam_linkedin_client_secret' ),
			'creds_help' => 'LinkedIn app with the Advertising API product approved. linkedin.com/developers -> App -> Auth (Client ID + Secret), Products -> Advertising API.',
			'note'       => 'Sponsored content run through LinkedIn Campaign Manager. Paid advertising only - no automated messaging.',
		),
		'pinterest' => array(
			'label'      => 'Pinterest Ads',
			'short'      => 'Pinterest',
			'group'      => 'pinterest',
			'automation' => 'connect_draft',
			'tier'       => 'growth',
			'creds'      => array( 'bds_aam_pinterest_app_id', 'bds_aam_pinterest_app_secret' ),
			'creds_help' => 'Pinterest app with Ads API access. developers.pinterest.com -> Apps (App ID + Secret), request standard access for ads scopes.',
		),
		'youtube'   => array(
			'label'      => 'YouTube Ads',
			'short'      => 'YouTube',
			'group'      => 'google',
			'automation' => 'connect_draft',
			'tier'       => 'growth',
			'creds'      => array( 'bds_aam_google_client_id', 'bds_aam_google_client_secret', 'bds_aam_google_dev_token' ),
			'creds_help' => 'Runs through Google Ads. Google Cloud OAuth client + an approved Google Ads API developer token.',
			'note'       => 'YouTube video campaigns are managed inside your Google Ads account.',
		),
		'google'    => array(
			'label'      => 'Google Ads',
			'short'      => 'Google',
			'group'      => 'google',
			'automation' => 'connect_draft',
			'tier'       => 'growth',
			'creds'      => array( 'bds_aam_google_client_id', 'bds_aam_google_client_secret', 'bds_aam_google_dev_token' ),
			'creds_help' => 'Google Cloud project OAuth client (Web) + Google Ads API developer token from your MCC (Tools -> API Center). Optional login customer ID for manager access.',
		),
		'bing'      => array(
			'label'      => 'Bing Ads (Microsoft Advertising)',
			'short'      => 'Bing',
			'group'      => 'microsoft',
			'automation' => 'connect_draft',
			'tier'       => 'growth',
			'creds'      => array( 'bds_aam_ms_client_id', 'bds_aam_ms_client_secret', 'bds_aam_ms_dev_token' ),
			'creds_help' => 'Microsoft Entra app registration (Client ID + Secret) + Microsoft Advertising developer token from ads.microsoft.com -> Tools -> Developer settings.',
		),
		'tiktok'    => array(
			'label'      => 'TikTok Ads',
			'short'      => 'TikTok',
			'group'      => 'tiktok',
			'automation' => 'connect_draft',
			'tier'       => 'scale',
			'creds'      => array( 'bds_aam_tiktok_app_id', 'bds_aam_tiktok_app_secret' ),
			'creds_help' => 'TikTok for Business app with Marketing API access. business-api.tiktok.com -> My Apps (App ID + Secret).',
		),
		'x'         => array(
			'label'      => 'X (Twitter) Ads',
			'short'      => 'X',
			'group'      => 'x',
			'automation' => 'connect_draft',
			'tier'       => 'scale',
			'creds'      => array( 'bds_aam_x_client_id', 'bds_aam_x_client_secret' ),
			'creds_help' => 'X developer app (OAuth 2.0 Client ID + Secret). The Ads API additionally requires approved Ads API access on the account.',
		),
		'snapchat'  => array(
			'label'      => 'Snapchat Ads',
			'short'      => 'Snapchat',
			'group'      => 'snapchat',
			'automation' => 'connect_draft',
			'tier'       => 'scale',
			'creds'      => array( 'bds_aam_snap_client_id', 'bds_aam_snap_client_secret' ),
			'creds_help' => 'Snap Business app with Marketing API. business.snapchat.com -> Business Details -> OAuth apps (Client ID + Secret).',
		),
		'reddit'    => array(
			'label'      => 'Reddit Ads',
			'short'      => 'Reddit',
			'group'      => 'reddit',
			'automation' => 'connect_draft',
			'tier'       => 'scale',
			'creds'      => array( 'bds_aam_reddit_client_id', 'bds_aam_reddit_client_secret' ),
			'creds_help' => 'Reddit Ads API app. ads.reddit.com -> Tools -> API access (Client ID + Secret).',
		),
		'yelp'      => array(
			'label'      => 'Yelp Ads',
			'short'      => 'Yelp',
			'group'      => 'yelp',
			'automation' => 'manual',
			'tier'       => 'scale',
			'creds'      => array(),
			'creds_help' => 'Yelp has no public self-serve Ads API. We prepare the campaign and you (or BrandDad, with your permission) apply it in Yelp for Business.',
			'note'       => 'AI builds the budget plan, categories, service areas and ad copy. Launch happens in Yelp for Business.',
		),
	);
	return $nets;
}

/**
 * OAuth endpoint config per provider group.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_aam_oauth_config() {
	return array(
		'meta'      => array(
			'authorize' => 'https://www.facebook.com/' . BDS_AAM_GRAPH . '/dialog/oauth',
			'token'     => 'https://graph.facebook.com/' . BDS_AAM_GRAPH . '/oauth/access_token',
			'scope'     => 'ads_management,ads_read,business_management,pages_show_list,pages_read_engagement,instagram_basic',
			'id_opt'    => 'bds_aam_meta_app_id',
			'sec_opt'   => 'bds_aam_meta_app_secret',
			'token_get' => true,
		),
		'linkedin'  => array(
			'authorize' => 'https://www.linkedin.com/oauth/v2/authorization',
			'token'     => 'https://www.linkedin.com/oauth/v2/accessToken',
			'scope'     => 'r_ads r_ads_reporting rw_ads',
			'id_opt'    => 'bds_aam_linkedin_client_id',
			'sec_opt'   => 'bds_aam_linkedin_client_secret',
		),
		'pinterest' => array(
			'authorize' => 'https://www.pinterest.com/oauth/',
			'token'     => 'https://api.pinterest.com/v5/oauth/token',
			'scope'     => 'ads:read ads:write user_accounts:read',
			'id_opt'    => 'bds_aam_pinterest_app_id',
			'sec_opt'   => 'bds_aam_pinterest_app_secret',
			'basic'     => true,
		),
		'google'    => array(
			'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
			'token'     => 'https://oauth2.googleapis.com/token',
			'scope'     => 'https://www.googleapis.com/auth/adwords',
			'id_opt'    => 'bds_aam_google_client_id',
			'sec_opt'   => 'bds_aam_google_client_secret',
			'extra'     => array( 'access_type' => 'offline', 'prompt' => 'consent' ),
		),
		'microsoft' => array(
			'authorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
			'token'     => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
			'scope'     => 'https://ads.microsoft.com/msads.manage offline_access',
			'id_opt'    => 'bds_aam_ms_client_id',
			'sec_opt'   => 'bds_aam_ms_client_secret',
		),
		'tiktok'    => array(
			'authorize' => 'https://business-api.tiktok.com/portal/auth',
			'token'     => 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/',
			'scope'     => '',
			'id_opt'    => 'bds_aam_tiktok_app_id',
			'sec_opt'   => 'bds_aam_tiktok_app_secret',
			'tiktok'    => true,
		),
		'x'         => array(
			'authorize' => 'https://twitter.com/i/oauth2/authorize',
			'token'     => 'https://api.twitter.com/2/oauth2/token',
			'scope'     => 'tweet.read users.read offline.access',
			'id_opt'    => 'bds_aam_x_client_id',
			'sec_opt'   => 'bds_aam_x_client_secret',
			'pkce'      => true,
		),
		'snapchat'  => array(
			'authorize' => 'https://accounts.snapchat.com/login/oauth2/authorize',
			'token'     => 'https://accounts.snapchat.com/login/oauth2/access_token',
			'scope'     => 'snapchat-marketing-api',
			'id_opt'    => 'bds_aam_snap_client_id',
			'sec_opt'   => 'bds_aam_snap_client_secret',
		),
		'reddit'    => array(
			'authorize' => 'https://www.reddit.com/api/v1/authorize',
			'token'     => 'https://www.reddit.com/api/v1/access_token',
			'scope'     => 'adsread adsedit identity',
			'id_opt'    => 'bds_aam_reddit_client_id',
			'sec_opt'   => 'bds_aam_reddit_client_secret',
			'basic'     => true,
			'extra'     => array( 'duration' => 'permanent' ),
		),
	);
}

/**
 * @param string $net Network key.
 * @return bool True when an admin has saved every credential the network needs.
 */
function bds_aam_net_configured( $net ) {
	$nets = bds_aam_networks();
	if ( ! isset( $nets[ $net ] ) ) {
		return false;
	}
	$creds = $nets[ $net ]['creds'];
	if ( empty( $creds ) ) {
		return false;
	}
	foreach ( $creds as $opt ) {
		if ( '' === trim( (string) get_option( $opt, '' ) ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Customer-facing status for a network. Never optimistic.
 *
 * @param string $net     Network key.
 * @param int    $user_id User.
 * @return array{state:string,label:string}
 */
function bds_aam_net_status( $net, $user_id = 0 ) {
	$nets = bds_aam_networks();
	if ( ! isset( $nets[ $net ] ) ) {
		return array( 'state' => 'unknown', 'label' => 'Unknown' );
	}
	if ( 'manual' === $nets[ $net ]['automation'] ) {
		return array( 'state' => 'manual', 'label' => 'AI drafts ready - launch in platform' );
	}
	if ( ! bds_aam_net_configured( $net ) ) {
		return array( 'state' => 'needs_keys', 'label' => 'Needs API keys' );
	}
	$conn = bds_aam_get_connection( $user_id, $nets[ $net ]['group'] );
	if ( empty( $conn['access_token'] ) ) {
		return array( 'state' => 'ready_to_connect', 'label' => 'Ready to connect' );
	}
	return array( 'state' => 'connected', 'label' => 'Connected' );
}

/* --------------------------- Pricing / plans --------------------------- */

/**
 * Monthly managed-spend ceilings are BrandDad service limits, not money we hold.
 * The customer's card stays on the ad platform.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_aam_plans() {
	return array(
		'setup'   => array(
			'name'      => 'AI Ads Setup',
			'slug'      => 'bd-ai-ads-setup',
			'price'     => 99,
			'billing'   => 'one-time',
			'cap'       => 0,
			'networks'  => array( 'facebook', 'instagram' ),
			'blurb'     => 'One-time onboarding: connect your ad accounts, install/verify tracking, and get your first AI campaign drafts built.',
			'bullets'   => array(
				'Ad account + page/profile connection walkthrough',
				'Pixel / conversion tracking checklist and verification',
				'Business profile intake so the AI knows your offer and audience',
				'First AI campaign drafts (copy, headlines, targeting, budget plan)',
				'Handover call notes and a launch checklist',
			),
		),
		'starter' => array(
			'name'      => 'AI Ads Starter',
			'slug'      => 'bd-ai-ads-starter',
			'price'     => 149,
			'billing'   => 'month',
			'cap'       => 500,
			'networks'  => array( 'facebook', 'instagram' ),
			'blurb'     => 'One network - Facebook + Instagram. AI creative, automated campaign build, ongoing management for up to $500/mo of your ad spend.',
			'bullets'   => array(
				'Meta (Facebook + Instagram) ad account automation',
				'Fresh AI ad copy, headlines and creative briefs every month',
				'Campaigns built automatically in your account, paused until you approve',
				'Performance dashboard with real spend pulled from the platform',
				'Up to $500/mo managed ad spend (paid by you, directly to Meta)',
			),
		),
		'growth'  => array(
			'name'      => 'AI Ads Growth',
			'slug'      => 'bd-ai-ads-growth',
			'price'     => 299,
			'billing'   => 'month',
			'cap'       => 2000,
			'networks'  => array( 'facebook', 'instagram', 'linkedin', 'pinterest', 'youtube', 'google', 'bing' ),
			'net_limit' => 2,
			'blurb'     => 'Meta plus one more network - LinkedIn, Pinterest, YouTube, Google Ads or Bing. AI creative testing across both, up to $2,000/mo managed spend.',
			'bullets'   => array(
				'Everything in Starter',
				'Add a second network: LinkedIn, Pinterest, YouTube, Google Ads or Bing Ads',
				'Multi-variant AI creative testing and monthly refresh',
				'Search-intent ad copy for Google / Bing keywords',
				'Up to $2,000/mo managed ad spend (paid by you, directly to each platform)',
			),
		),
		'scale'   => array(
			'name'      => 'AI Ads Scale',
			'slug'      => 'bd-ai-ads-scale',
			'price'     => 499,
			'billing'   => 'month',
			'cap'       => 5000,
			'networks'  => array( 'facebook', 'instagram', 'linkedin', 'pinterest', 'youtube', 'google', 'bing', 'tiktok', 'x', 'snapchat', 'reddit', 'yelp' ),
			'blurb'     => 'Every supported network, priority automation and the fastest creative refresh cycle. Up to $5,000/mo managed spend.',
			'bullets'   => array(
				'All networks: Meta, LinkedIn, Pinterest, YouTube, Google, Bing, TikTok, X, Snapchat, Reddit, Yelp',
				'Priority automation runs and same-week creative refresh',
				'Cross-network budget guidance based on your real reported results',
				'Search + social + video coverage from one dashboard',
				'Up to $5,000/mo managed ad spend (paid by you, directly to each platform)',
			),
		),
		'spend1k' => array(
			'name'      => 'AI Ads - Extra Spend Tier',
			'slug'      => 'bd-ai-ads-spend-tier',
			'price'     => 99,
			'billing'   => 'month',
			'cap'       => 1000,
			'addon'     => true,
			'networks'  => array(),
			'blurb'     => 'Add $1,000/mo of managed ad spend to any AI Ads plan. Stack as many as you need; above $5,000/mo total we will quote a custom rate.',
			'bullets'   => array(
				'+$1,000/mo managed ad spend capacity',
				'Stackable with Starter, Growth or Scale',
				'Above $5,000/mo total we switch you to a custom quote',
			),
		),
	);
}

/**
 * Resolve the plan a customer is entitled to from their WooCommerce orders.
 *
 * @param int $user_id User.
 * @return array<string,mixed>
 */
function bds_aam_user_plan( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$out     = array(
		'plan'      => '',
		'name'      => '',
		'cap'       => 0,
		'networks'  => array(),
		'net_limit' => 0,
		'setup'     => false,
		'addons'    => 0,
		'orders'    => array(),
	);
	if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
		return $out;
	}

	$cached = get_transient( 'bds_aam_plan_' . $user_id );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$plans = bds_aam_plans();
	$rank  = array( 'starter' => 1, 'growth' => 2, 'scale' => 3 );
	$best  = '';

	$orders = wc_get_orders(
		array(
			'customer_id' => $user_id,
			'status'      => array( 'wc-processing', 'wc-completed' ),
			'limit'       => 40,
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	// Active WooCommerce Subscriptions count even when the parent order has aged out.
	if ( function_exists( 'wcs_get_users_subscriptions' ) ) {
		foreach ( (array) wcs_get_users_subscriptions( $user_id ) as $sub ) {
			if ( is_object( $sub ) && method_exists( $sub, 'has_status' ) && $sub->has_status( array( 'active', 'pending-cancel' ) ) ) {
				$orders[] = $sub;
			}
		}
	}
	foreach ( (array) $orders as $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
			continue;
		}
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			$key = (string) get_post_meta( $product->get_id(), '_bds_aam_plan', true );
			if ( '' === $key ) {
				$sku = (string) $product->get_sku();
				foreach ( $plans as $pk => $p ) {
					if ( $sku === $p['slug'] ) {
						$key = $pk;
						break;
					}
				}
			}
			if ( '' === $key || ! isset( $plans[ $key ] ) ) {
				continue;
			}
			$out['orders'][] = (int) $order->get_id();
			if ( 'setup' === $key ) {
				$out['setup'] = true;
			} elseif ( 'spend1k' === $key ) {
				$out['addons'] += max( 1, (int) $item->get_quantity() );
			} elseif ( isset( $rank[ $key ] ) && ( '' === $best || $rank[ $key ] > $rank[ $best ] ) ) {
				$best = $key;
			}
		}
	}

	if ( '' !== $best ) {
		$out['plan']      = $best;
		$out['name']      = $plans[ $best ]['name'];
		$out['cap']       = (int) $plans[ $best ]['cap'] + ( 1000 * $out['addons'] );
		$out['networks']  = $plans[ $best ]['networks'];
		$out['net_limit'] = isset( $plans[ $best ]['net_limit'] ) ? (int) $plans[ $best ]['net_limit'] : 0;
	} elseif ( $out['setup'] ) {
		$out['plan']     = 'setup';
		$out['name']     = $plans['setup']['name'];
		$out['networks'] = $plans['setup']['networks'];
	}

	set_transient( 'bds_aam_plan_' . $user_id, $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

/**
 * @param string $net  Network key.
 * @param array  $plan Plan from bds_aam_user_plan().
 * @return bool
 */
function bds_aam_plan_allows( $net, $plan ) {
	if ( empty( $plan['plan'] ) ) {
		return false;
	}
	return in_array( $net, (array) $plan['networks'], true );
}

/* --------------------------- Secrets / crypto --------------------------- */

function bds_aam_crypto_key() {
	$seed = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'AUTH_SALT' ) ? AUTH_SALT : '' );
	if ( '' === $seed ) {
		$seed = (string) get_option( 'bds_aam_fallback_seed', '' );
		if ( '' === $seed ) {
			$seed = wp_generate_password( 64, true, true );
			update_option( 'bds_aam_fallback_seed', $seed, false );
		}
	}
	return hash( 'sha256', 'bds-aam|' . $seed, true );
}

/**
 * @param mixed $plain Data.
 * @return string
 */
function bds_aam_encrypt( $plain ) {
	$json = wp_json_encode( $plain );
	if ( ! function_exists( 'openssl_encrypt' ) ) {
		return 'b64:' . base64_encode( $json );
	}
	$iv  = openssl_random_pseudo_bytes( 16 );
	$enc = openssl_encrypt( $json, 'aes-256-cbc', bds_aam_crypto_key(), OPENSSL_RAW_DATA, $iv );
	if ( false === $enc ) {
		return 'b64:' . base64_encode( $json );
	}
	return 'v1:' . base64_encode( $iv . $enc );
}

/**
 * @param string $blob Stored blob.
 * @return array<string,mixed>
 */
function bds_aam_decrypt( $blob ) {
	$blob = (string) $blob;
	if ( '' === $blob ) {
		return array();
	}
	if ( 0 === strpos( $blob, 'b64:' ) ) {
		$out = json_decode( (string) base64_decode( substr( $blob, 4 ) ), true );
		return is_array( $out ) ? $out : array();
	}
	if ( 0 !== strpos( $blob, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
		return array();
	}
	$raw = base64_decode( substr( $blob, 3 ) );
	if ( strlen( $raw ) < 17 ) {
		return array();
	}
	$dec = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', bds_aam_crypto_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
	if ( false === $dec ) {
		return array();
	}
	$out = json_decode( $dec, true );
	return is_array( $out ) ? $out : array();
}

/**
 * @param int    $user_id User.
 * @param string $group   Provider group.
 * @return array<string,mixed>
 */
function bds_aam_get_connection( $user_id, $group ) {
	if ( ! $user_id ) {
		return array();
	}
	$all = bds_aam_decrypt( (string) get_user_meta( $user_id, '_bds_aam_conn', true ) );
	return isset( $all[ $group ] ) && is_array( $all[ $group ] ) ? $all[ $group ] : array();
}

/**
 * @param int    $user_id User.
 * @param string $group   Provider group.
 * @param array  $data    Connection payload (merged).
 * @return void
 */
function bds_aam_set_connection( $user_id, $group, $data ) {
	if ( ! $user_id ) {
		return;
	}
	$all = bds_aam_decrypt( (string) get_user_meta( $user_id, '_bds_aam_conn', true ) );
	if ( ! is_array( $all ) ) {
		$all = array();
	}
	$prev            = isset( $all[ $group ] ) && is_array( $all[ $group ] ) ? $all[ $group ] : array();
	$all[ $group ]   = array_merge( $prev, (array) $data );
	$all[ $group ]['updated'] = time();
	update_user_meta( $user_id, '_bds_aam_conn', bds_aam_encrypt( $all ) );
}

/**
 * @param int    $user_id User.
 * @param string $group   Provider group.
 * @return void
 */
function bds_aam_drop_connection( $user_id, $group ) {
	$all = bds_aam_decrypt( (string) get_user_meta( $user_id, '_bds_aam_conn', true ) );
	unset( $all[ $group ] );
	update_user_meta( $user_id, '_bds_aam_conn', bds_aam_encrypt( $all ) );
}

/**
 * Shared OpenAI key cascade used across BrandDad modules. Never hardcoded.
 *
 * @return string
 */
function bds_aam_openai_key() {
	$direct = trim( (string) get_option( 'bds_aam_openai_key', '' ) );
	if ( '' !== $direct ) {
		return $direct;
	}
	foreach ( array( 'bdco_openai_api_key', 'bds_openai_api_key', 'bds_asm_openai_key' ) as $opt ) {
		$v = trim( (string) get_option( $opt, '' ) );
		if ( '' !== $v ) {
			return $v;
		}
	}
	foreach ( array( 'bds_ai_finder_settings', 'bds_ab_settings', 'bds_ai_ads_settings' ) as $opt ) {
		$arr = get_option( $opt, array() );
		if ( is_array( $arr ) && ! empty( $arr['openai_api_key'] ) ) {
			return trim( (string) $arr['openai_api_key'] );
		}
	}
	if ( defined( 'BDS_OPENAI_API_KEY' ) && BDS_OPENAI_API_KEY ) {
		return (string) BDS_OPENAI_API_KEY;
	}
	return '';
}

/* --------------------------- Bootstrap --------------------------- */

add_action( 'init', 'bds_aam_register_cpt' );
add_action( 'init', 'bds_aam_register_rewrite', 11 );
add_filter( 'query_vars', 'bds_aam_query_vars' );
add_action( 'template_redirect', 'bds_aam_render_route' );
add_action( 'template_redirect', 'bds_aam_buffer_seo', -59 );
add_action( 'rest_api_init', 'bds_aam_register_routes' );
add_action( 'admin_menu', 'bds_aam_admin_menu' );
add_action( 'admin_init', 'bds_aam_register_settings' );
add_action( 'admin_init', 'bds_aam_maybe_seed_products' );

function bds_aam_register_cpt() {
	register_post_type(
		BDS_AAM_CPT,
		array(
			'labels'          => array(
				'name'          => 'AI Ad Campaigns',
				'singular_name' => 'AI Ad Campaign',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'bds-ai-ads',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
		)
	);
}

function bds_aam_register_rewrite() {
	add_rewrite_rule( '^ai-ads/?$', 'index.php?bds_aam=home', 'top' );
	add_rewrite_rule( '^ai-ads/dashboard/?$', 'index.php?bds_aam=dashboard', 'top' );
	add_rewrite_rule( '^ai-ads/connect/?$', 'index.php?bds_aam=connect', 'top' );
	add_rewrite_rule( '^account/ai-ads/?$', 'index.php?bds_aam=dashboard', 'top' );
	if ( (string) get_option( 'bds_aam_rw' ) !== BDS_AAM_VER ) {
		flush_rewrite_rules( false );
		update_option( 'bds_aam_rw', BDS_AAM_VER, false );
	}
}

/**
 * @param array<int,string> $vars Vars.
 * @return array<int,string>
 */
function bds_aam_query_vars( $vars ) {
	$vars[] = 'bds_aam';
	return $vars;
}

/* --------------------------- Woo product seeding --------------------------- */

function bds_aam_maybe_seed_products() {
	if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	if ( (string) get_option( 'bds_aam_seeded' ) === BDS_AAM_VER ) {
		return;
	}
	$report = bds_aam_seed_products();
	update_option( 'bds_aam_seeded', BDS_AAM_VER, false );
	update_option( 'bds_aam_seed_report', $report, false );
}

/**
 * Create/refresh the AI Ads SKUs. Uses WooCommerce Subscriptions when present,
 * otherwise clean simple products labelled per month.
 *
 * @return array<string,mixed>
 */
function bds_aam_seed_products() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array( 'ok' => false, 'error' => 'woocommerce_missing' );
	}
	$subs = class_exists( 'WC_Subscriptions_Product' );
	$out  = array( 'ok' => true, 'subscriptions' => $subs, 'products' => array() );

	$term = term_exists( 'ai-ads-management', 'product_cat' );
	if ( ! $term ) {
		$term = wp_insert_term(
			'AI Ads Management',
			'product_cat',
			array(
				'slug'        => 'ai-ads-management',
				'description' => 'AI-built, automated paid advertising across Meta, LinkedIn, Pinterest, Google, YouTube, Bing, TikTok, X, Snapchat, Reddit and Yelp. Ad budget is paid by you directly to each platform.',
			)
		);
	}
	$cat_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;

	foreach ( bds_aam_plans() as $key => $plan ) {
		$existing = get_page_by_path( $plan['slug'], OBJECT, 'product' );
		if ( ! $existing ) {
			$found = wc_get_product_id_by_sku( $plan['slug'] );
			$existing = $found ? get_post( $found ) : null;
		}
		$product = $existing ? wc_get_product( $existing->ID ) : new WC_Product_Simple();
		if ( ! $product ) {
			$out['products'][ $key ] = array( 'ok' => false, 'error' => 'init_failed' );
			continue;
		}

		$monthly = ( 'month' === $plan['billing'] );
		$name    = $plan['name'] . ( $monthly && ! $subs ? ' - Monthly' : '' );

		$product->set_name( $name );
		$product->set_slug( $plan['slug'] );
		$product->set_sku( $plan['slug'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( (string) $plan['price'] );
		$product->set_price( (string) $plan['price'] );
		$product->set_virtual( true );
		$product->set_sold_individually( empty( $plan['addon'] ) );
		$product->set_short_description( $plan['blurb'] );
		$product->set_description( bds_aam_product_description( $key, $plan ) );
		if ( $cat_id ) {
			$product->set_category_ids( array( $cat_id ) );
		}
		$id = $product->save();

		if ( $monthly && $subs ) {
			// Convert to a real subscription product when WooCommerce Subscriptions is active.
			wp_set_object_terms( $id, 'subscription', 'product_type' );
			update_post_meta( $id, '_subscription_price', (string) $plan['price'] );
			update_post_meta( $id, '_subscription_period', 'month' );
			update_post_meta( $id, '_subscription_period_interval', '1' );
			update_post_meta( $id, '_subscription_length', '0' );
			update_post_meta( $id, '_subscription_sign_up_fee', '0' );
		}
		if ( $monthly && function_exists( 'bds_msub_convert_product' ) ) {
			bds_msub_convert_product( (int) $id, array( 'price' => (string) $plan['price'] ) );
		}

		update_post_meta( $id, '_bds_aam_plan', $key );
		update_post_meta( $id, '_bds_aam_cap', (int) $plan['cap'] );
		update_post_meta( $id, '_bds_aam_networks', (array) $plan['networks'] );
		update_post_meta( $id, '_bds_network_product', '1' );
		update_post_meta( $id, '_bds_company', 'branddad-social' );

		$out['products'][ $key ] = array(
			'ok'    => true,
			'id'    => (int) $id,
			'sku'   => $plan['slug'],
			'price' => $plan['price'],
			'url'   => get_permalink( $id ),
		);
	}

	return $out;
}

/**
 * @param string $key  Plan key.
 * @param array  $plan Plan.
 * @return string
 */
function bds_aam_product_description( $key, $plan ) {
	$nets  = bds_aam_networks();
	$html  = '<p>' . esc_html( $plan['blurb'] ) . '</p>';
	$html .= '<h3>What you get</h3><ul>';
	foreach ( $plan['bullets'] as $b ) {
		$html .= '<li>' . esc_html( $b ) . '</li>';
	}
	$html .= '</ul>';

	if ( ! empty( $plan['networks'] ) ) {
		$labels = array();
		foreach ( $plan['networks'] as $n ) {
			if ( isset( $nets[ $n ] ) ) {
				$labels[] = $nets[ $n ]['short'];
			}
		}
		if ( $labels ) {
			$html .= '<h3>Networks included</h3><p>' . esc_html( implode( ' | ', array_unique( $labels ) ) ) . '</p>';
		}
	}

	$html .= '<h3>How billing works</h3>';
	$html .= '<p><strong>Your ad budget is paid directly to Facebook/Meta, Google, LinkedIn, TikTok and the other platforms using your own card.</strong> '
		. 'BrandDad never holds or resells media spend. This fee covers the AI creative and the campaign automation only.</p>';

	if ( ! empty( $plan['cap'] ) && empty( $plan['addon'] ) ) {
		$html .= '<p>Managed ad spend up to <strong>$' . number_format( (int) $plan['cap'] ) . '/month</strong>. '
			. 'Need more? Add the <em>Extra Spend Tier</em> ($99/mo per additional $1,000), or ask for a custom quote above $5,000/mo.</p>';
	}

	$html .= '<h3>Honest expectations</h3>';
	$html .= '<ul>'
		. '<li>We do not guarantee a specific return, lead volume or cost per result.</li>'
		. '<li>Campaigns are built in <em>your</em> ad account and stay paused until you approve them.</li>'
		. '<li>Reported spend and results come straight from each platform API - we never estimate numbers.</li>'
		. '<li>Networks without an approved API yet are handled as AI drafts you (or we) launch manually. Status is shown per network in your dashboard.</li>'
		. '</ul>';

	$html .= '<p><a href="' . esc_url( home_url( '/ai-ads/' ) ) . '">See the full network list and how the automation works -></a></p>';
	return $html;
}

/* --------------------------- OAuth --------------------------- */

function bds_aam_callback_url() {
	return rest_url( 'bds-aam/v1/oauth/callback' );
}

/**
 * @param string $group   Provider group.
 * @param int    $user_id User.
 * @return string|WP_Error
 */
function bds_aam_authorize_url( $group, $user_id ) {
	$cfg = bds_aam_oauth_config();
	if ( ! isset( $cfg[ $group ] ) ) {
		return new WP_Error( 'bds_aam_group', 'This network does not use OAuth.', array( 'status' => 400 ) );
	}
	$c  = $cfg[ $group ];
	$id = trim( (string) get_option( $c['id_opt'], '' ) );
	if ( '' === $id ) {
		return new WP_Error( 'bds_aam_no_app', 'API credentials are not configured yet for this network.', array( 'status' => 503 ) );
	}

	$state = wp_generate_password( 32, false );
	set_transient(
		'bds_aam_state_' . $state,
		array( 'group' => $group, 'user_id' => (int) $user_id, 'created' => time() ),
		20 * MINUTE_IN_SECONDS
	);

	$args = array(
		'response_type' => 'code',
		'redirect_uri'  => bds_aam_callback_url(),
		'state'         => $state,
	);
	if ( ! empty( $c['tiktok'] ) ) {
		$args = array(
			'app_id'       => $id,
			'redirect_uri' => bds_aam_callback_url(),
			'state'        => $state,
		);
		return $c['authorize'] . '?' . http_build_query( $args );
	}
	$args['client_id'] = $id;
	if ( ! empty( $c['scope'] ) ) {
		$args['scope'] = $c['scope'];
	}
	if ( ! empty( $c['extra'] ) ) {
		$args = array_merge( $args, (array) $c['extra'] );
	}
	if ( ! empty( $c['pkce'] ) ) {
		$verifier = wp_generate_password( 64, false );
		set_transient( 'bds_aam_pkce_' . $state, $verifier, 20 * MINUTE_IN_SECONDS );
		$args['code_challenge']        = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
		$args['code_challenge_method'] = 'S256';
	}
	return $c['authorize'] . '?' . http_build_query( $args );
}

/**
 * @param string $group Provider group.
 * @param string $code  Auth code.
 * @param string $state State.
 * @return array<string,mixed>|WP_Error
 */
function bds_aam_token_exchange( $group, $code, $state ) {
	$cfg = bds_aam_oauth_config();
	if ( ! isset( $cfg[ $group ] ) ) {
		return new WP_Error( 'bds_aam_group', 'Unknown provider.' );
	}
	$c      = $cfg[ $group ];
	$id     = trim( (string) get_option( $c['id_opt'], '' ) );
	$secret = trim( (string) get_option( $c['sec_opt'], '' ) );
	if ( '' === $id || '' === $secret ) {
		return new WP_Error( 'bds_aam_no_app', 'API credentials are not configured.' );
	}

	if ( ! empty( $c['tiktok'] ) ) {
		$resp = wp_remote_post(
			$c['token'],
			array(
				'timeout' => 25,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'app_id' => $id, 'secret' => $secret, 'auth_code' => $code ) ),
			)
		);
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $body ) || empty( $body['data']['access_token'] ) ) {
			return new WP_Error( 'bds_aam_token', 'Token exchange failed.', array( 'body' => $body ) );
		}
		return array( 'access_token' => (string) $body['data']['access_token'], 'raw' => $body['data'] );
	}

	$body = array(
		'grant_type'   => 'authorization_code',
		'code'         => $code,
		'redirect_uri' => bds_aam_callback_url(),
	);
	$headers = array( 'Content-Type' => 'application/x-www-form-urlencoded' );

	if ( ! empty( $c['basic'] ) ) {
		$headers['Authorization'] = 'Basic ' . base64_encode( $id . ':' . $secret );
	} else {
		$body['client_id']     = $id;
		$body['client_secret'] = $secret;
	}
	if ( ! empty( $c['pkce'] ) ) {
		$verifier = get_transient( 'bds_aam_pkce_' . $state );
		if ( $verifier ) {
			$body['code_verifier'] = $verifier;
			$body['client_id']     = $id;
		}
	}

	if ( ! empty( $c['token_get'] ) ) {
		$resp = wp_remote_get( add_query_arg( $body, $c['token'] ), array( 'timeout' => 25 ) );
	} else {
		$resp = wp_remote_post( $c['token'], array( 'timeout' => 25, 'headers' => $headers, 'body' => $body ) );
	}
	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$json = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
	if ( ! is_array( $json ) || empty( $json['access_token'] ) ) {
		return new WP_Error( 'bds_aam_token', 'Token exchange failed.', array( 'status' => wp_remote_retrieve_response_code( $resp ) ) );
	}

	$out = array(
		'access_token'  => (string) $json['access_token'],
		'refresh_token' => isset( $json['refresh_token'] ) ? (string) $json['refresh_token'] : '',
		'expires'       => isset( $json['expires_in'] ) ? time() + (int) $json['expires_in'] : 0,
	);

	// Meta short-lived -> long-lived (60 days).
	if ( 'meta' === $group ) {
		$long = wp_remote_get(
			'https://graph.facebook.com/' . BDS_AAM_GRAPH . '/oauth/access_token?' . http_build_query(
				array(
					'grant_type'        => 'fb_exchange_token',
					'client_id'         => $id,
					'client_secret'     => $secret,
					'fb_exchange_token' => $out['access_token'],
				)
			),
			array( 'timeout' => 25 )
		);
		if ( ! is_wp_error( $long ) ) {
			$lj = json_decode( (string) wp_remote_retrieve_body( $long ), true );
			if ( is_array( $lj ) && ! empty( $lj['access_token'] ) ) {
				$out['access_token'] = (string) $lj['access_token'];
				$out['expires']      = isset( $lj['expires_in'] ) ? time() + (int) $lj['expires_in'] : 0;
			}
		}
	}

	return $out;
}

/* --------------------------- Meta Marketing API --------------------------- */

/**
 * @param string $path   Graph path.
 * @param string $token  Access token.
 * @param array  $args   Query/body args.
 * @param string $method GET|POST.
 * @return array<string,mixed>|WP_Error
 */
function bds_aam_graph( $path, $token, $args = array(), $method = 'GET' ) {
	$url = 'https://graph.facebook.com/' . BDS_AAM_GRAPH . '/' . ltrim( $path, '/' );
	if ( 'GET' === $method ) {
		$args['access_token'] = $token;
		$resp                 = wp_remote_get( add_query_arg( array_map( 'strval', $args ), $url ), array( 'timeout' => 30 ) );
	} else {
		$args['access_token'] = $token;
		$resp                 = wp_remote_post( $url, array( 'timeout' => 30, 'body' => $args ) );
	}
	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$json = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
	if ( ! is_array( $json ) ) {
		return new WP_Error( 'bds_aam_graph', 'Unreadable response from Meta.' );
	}
	if ( isset( $json['error'] ) ) {
		return new WP_Error(
			'bds_aam_graph',
			isset( $json['error']['message'] ) ? (string) $json['error']['message'] : 'Meta API error.',
			array( 'meta' => $json['error'] )
		);
	}
	return $json;
}

/**
 * Pull the ad accounts + pages the customer authorised.
 *
 * @param int $user_id User.
 * @return array<string,mixed>|WP_Error
 */
function bds_aam_meta_sync_assets( $user_id ) {
	$conn = bds_aam_get_connection( $user_id, 'meta' );
	if ( empty( $conn['access_token'] ) ) {
		return new WP_Error( 'bds_aam_conn', 'Connect your Meta account first.' );
	}
	$token = $conn['access_token'];

	$accts = bds_aam_graph( 'me/adaccounts', $token, array( 'fields' => 'id,account_id,name,account_status,currency,amount_spent', 'limit' => 50 ) );
	if ( is_wp_error( $accts ) ) {
		return $accts;
	}
	$pages = bds_aam_graph( 'me/accounts', $token, array( 'fields' => 'id,name,instagram_business_account{id,username}', 'limit' => 50 ) );
	if ( is_wp_error( $pages ) ) {
		$pages = array( 'data' => array() );
	}

	$store = array(
		'ad_accounts' => array(),
		'pages'       => array(),
		'synced'      => time(),
	);
	foreach ( (array) ( $accts['data'] ?? array() ) as $a ) {
		$store['ad_accounts'][] = array(
			'id'       => (string) ( $a['id'] ?? '' ),
			'name'     => (string) ( $a['name'] ?? '' ),
			'currency' => (string) ( $a['currency'] ?? '' ),
			'status'   => (int) ( $a['account_status'] ?? 0 ),
		);
	}
	foreach ( (array) ( $pages['data'] ?? array() ) as $p ) {
		$store['pages'][] = array(
			'id'   => (string) ( $p['id'] ?? '' ),
			'name' => (string) ( $p['name'] ?? '' ),
			'ig'   => isset( $p['instagram_business_account']['id'] ) ? (string) $p['instagram_business_account']['id'] : '',
		);
	}
	if ( empty( $conn['act_id'] ) && ! empty( $store['ad_accounts'][0]['id'] ) ) {
		$store['act_id'] = $store['ad_accounts'][0]['id'];
	}
	if ( empty( $conn['page_id'] ) && ! empty( $store['pages'][0]['id'] ) ) {
		$store['page_id'] = $store['pages'][0]['id'];
	}
	bds_aam_set_connection( $user_id, 'meta', $store );
	return $store;
}

/**
 * Build the campaign in the customer's Meta ad account - always PAUSED.
 *
 * @param int $draft_id CPT id.
 * @return array<string,mixed>|WP_Error
 */
function bds_aam_meta_push( $draft_id ) {
	$draft = get_post( $draft_id );
	if ( ! $draft || BDS_AAM_CPT !== $draft->post_type ) {
		return new WP_Error( 'bds_aam_draft', 'Draft not found.' );
	}
	$user_id = (int) $draft->post_author;
	$conn    = bds_aam_get_connection( $user_id, 'meta' );
	if ( empty( $conn['access_token'] ) || empty( $conn['act_id'] ) ) {
		return new WP_Error( 'bds_aam_conn', 'Connect a Meta ad account first.' );
	}
	$page_id = (string) ( $conn['page_id'] ?? '' );
	if ( '' === $page_id ) {
		return new WP_Error( 'bds_aam_page', 'Select the Facebook Page that should run these ads.' );
	}

	$token = $conn['access_token'];
	$act   = $conn['act_id'];
	$data  = (array) get_post_meta( $draft_id, '_bds_aam_ai', true );
	$brief = (array) get_post_meta( $draft_id, '_bds_aam_brief', true );

	$daily = max( 5, (int) ( $brief['daily_budget'] ?? 10 ) );
	$plan  = bds_aam_user_plan( $user_id );
	if ( $plan['cap'] > 0 && ( $daily * 30 ) > $plan['cap'] ) {
		return new WP_Error(
			'bds_aam_cap',
			sprintf(
				'A $%d/day budget is about $%d/month, above your plan ceiling of $%s/month. Lower the daily budget or add an Extra Spend Tier.',
				$daily,
				$daily * 30,
				number_format( (int) $plan['cap'] )
			)
		);
	}

	$objective = (string) ( $data['objective'] ?? 'OUTCOME_TRAFFIC' );
	$allowed   = array( 'OUTCOME_TRAFFIC', 'OUTCOME_LEADS', 'OUTCOME_SALES', 'OUTCOME_ENGAGEMENT', 'OUTCOME_AWARENESS' );
	if ( ! in_array( $objective, $allowed, true ) ) {
		$objective = 'OUTCOME_TRAFFIC';
	}

	$name = get_the_title( $draft_id );

	$campaign = bds_aam_graph(
		$act . '/campaigns',
		$token,
		array(
			'name'                  => $name,
			'objective'             => $objective,
			'status'                => 'PAUSED',
			'special_ad_categories' => wp_json_encode( array() ),
		),
		'POST'
	);
	if ( is_wp_error( $campaign ) ) {
		return $campaign;
	}
	$campaign_id = (string) ( $campaign['id'] ?? '' );

	$targeting = bds_aam_meta_targeting( $data, $brief );
	$adset     = bds_aam_graph(
		$act . '/adsets',
		$token,
		array(
			'name'              => $name . ' - Ad set',
			'campaign_id'       => $campaign_id,
			'daily_budget'      => (string) ( $daily * 100 ),
			'billing_event'     => 'IMPRESSIONS',
			// LINK_CLICKS works without a verified pixel or lead form, so the first
			// build never fails on tracking the customer has not set up yet.
			'optimization_goal' => 'LINK_CLICKS',
			'bid_strategy'      => 'LOWEST_COST_WITHOUT_CAP',
			'targeting'         => wp_json_encode( $targeting ),
			'status'            => 'PAUSED',
			'start_time'        => gmdate( 'c', time() + HOUR_IN_SECONDS ),
		),
		'POST'
	);
	if ( is_wp_error( $adset ) ) {
		return $adset;
	}
	$adset_id = (string) ( $adset['id'] ?? '' );

	$variants = isset( $data['variants'] ) && is_array( $data['variants'] ) ? $data['variants'] : array();
	if ( ! $variants ) {
		return new WP_Error( 'bds_aam_creative', 'Generate ad copy before pushing.' );
	}
	$link = esc_url_raw( (string) ( $brief['url'] ?? home_url( '/' ) ) );
	$ads  = array();

	foreach ( array_slice( $variants, 0, 3 ) as $i => $v ) {
		$story = array(
			'page_id'   => $page_id,
			'link_data' => array(
				'link'            => $link,
				'message'         => (string) ( $v['primary_text'] ?? '' ),
				'name'            => (string) ( $v['headline'] ?? '' ),
				'description'     => (string) ( $v['description'] ?? '' ),
				'call_to_action'  => array(
					'type'  => bds_aam_meta_cta( (string) ( $v['cta'] ?? '' ) ),
					'value' => array( 'link' => $link ),
				),
			),
		);
		$creative = bds_aam_graph(
			$act . '/adcreatives',
			$token,
			array(
				'name'              => $name . ' - Creative ' . ( $i + 1 ),
				'object_story_spec' => wp_json_encode( $story ),
			),
			'POST'
		);
		if ( is_wp_error( $creative ) ) {
			$ads[] = array( 'ok' => false, 'error' => $creative->get_error_message() );
			continue;
		}
		$ad = bds_aam_graph(
			$act . '/ads',
			$token,
			array(
				'name'     => $name . ' - Ad ' . ( $i + 1 ),
				'adset_id' => $adset_id,
				'creative' => wp_json_encode( array( 'creative_id' => (string) ( $creative['id'] ?? '' ) ) ),
				'status'   => 'PAUSED',
			),
			'POST'
		);
		$ads[] = is_wp_error( $ad )
			? array( 'ok' => false, 'error' => $ad->get_error_message() )
			: array( 'ok' => true, 'id' => (string) ( $ad['id'] ?? '' ) );
	}

	$remote = array(
		'network'     => 'facebook',
		'act_id'      => $act,
		'campaign_id' => $campaign_id,
		'adset_id'    => $adset_id,
		'ads'         => $ads,
		'pushed'      => time(),
	);
	update_post_meta( $draft_id, '_bds_aam_remote', $remote );
	update_post_meta( $draft_id, '_bds_aam_status', 'pushed_paused' );
	bds_aam_log( $draft_id, 'Built in Meta ad account as PAUSED campaign ' . $campaign_id );
	return $remote;
}

/**
 * @param string $cta Free-text CTA from the model.
 * @return string Valid Meta CTA enum.
 */
function bds_aam_meta_cta( $cta ) {
	$map = array(
		'learn more'    => 'LEARN_MORE',
		'shop now'      => 'SHOP_NOW',
		'sign up'       => 'SIGN_UP',
		'book now'      => 'BOOK_TRAVEL',
		'get quote'     => 'GET_QUOTE',
		'contact us'    => 'CONTACT_US',
		'get offer'     => 'GET_OFFER',
		'apply now'     => 'APPLY_NOW',
		'download'      => 'DOWNLOAD',
		'subscribe'     => 'SUBSCRIBE',
		'call now'      => 'CALL_NOW',
		'order now'     => 'ORDER_NOW',
		'send message'  => 'MESSAGE_PAGE',
	);
	$key = strtolower( trim( $cta ) );
	if ( isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}
	$upper = strtoupper( preg_replace( '/[^A-Za-z]+/', '_', $cta ) );
	return in_array( $upper, array_values( $map ), true ) ? $upper : 'LEARN_MORE';
}

/**
 * @param array $data  AI output.
 * @param array $brief Customer brief.
 * @return array<string,mixed>
 */
function bds_aam_meta_targeting( $data, $brief ) {
	$t = array(
		'age_min'       => 18,
		'age_max'       => 65,
		'geo_locations' => array( 'countries' => array( 'US' ) ),
	);
	$tg = isset( $data['targeting'] ) && is_array( $data['targeting'] ) ? $data['targeting'] : array();
	if ( ! empty( $tg['age_min'] ) ) {
		$t['age_min'] = max( 18, min( 65, (int) $tg['age_min'] ) );
	}
	if ( ! empty( $tg['age_max'] ) ) {
		$t['age_max'] = max( (int) $t['age_min'], min( 65, (int) $tg['age_max'] ) );
	}
	$countries = array();
	foreach ( (array) ( $tg['countries'] ?? array() ) as $c ) {
		$c = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) $c ), 0, 2 ) );
		if ( 2 === strlen( $c ) ) {
			$countries[] = $c;
		}
	}
	if ( $countries ) {
		$t['geo_locations'] = array( 'countries' => array_values( array_unique( $countries ) ) );
	}
	return $t;
}

/**
 * Flip a pushed campaign live/paused. Enforces the plan spend ceiling.
 *
 * @param int    $draft_id Draft.
 * @param string $status   ACTIVE|PAUSED.
 * @return array<string,mixed>|WP_Error
 */
function bds_aam_meta_set_status( $draft_id, $status ) {
	$status = 'ACTIVE' === strtoupper( $status ) ? 'ACTIVE' : 'PAUSED';
	$remote = (array) get_post_meta( $draft_id, '_bds_aam_remote', true );
	if ( empty( $remote['campaign_id'] ) ) {
		return new WP_Error( 'bds_aam_remote', 'This campaign has not been built in an ad account yet.' );
	}
	$user_id = (int) get_post_field( 'post_author', $draft_id );
	$conn    = bds_aam_get_connection( $user_id, 'meta' );
	if ( empty( $conn['access_token'] ) ) {
		return new WP_Error( 'bds_aam_conn', 'Reconnect your Meta account.' );
	}
	if ( 'ACTIVE' === $status ) {
		$plan = bds_aam_user_plan( $user_id );
		if ( empty( $plan['plan'] ) || 'setup' === $plan['plan'] ) {
			return new WP_Error( 'bds_aam_plan', 'An active AI Ads plan is required to launch campaigns.' );
		}
	}
	$res = bds_aam_graph( (string) $remote['campaign_id'], $conn['access_token'], array( 'status' => $status ), 'POST' );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	update_post_meta( $draft_id, '_bds_aam_status', 'ACTIVE' === $status ? 'active' : 'paused' );
	bds_aam_log( $draft_id, 'Campaign status set to ' . $status . ' via Meta API' );
	return array( 'ok' => true, 'status' => $status );
}

/**
 * Real spend/impressions straight from Meta. Returns empty when unknown - never estimated.
 *
 * @param int $user_id User.
 * @return array<string,mixed>
 */
function bds_aam_meta_insights( $user_id ) {
	$conn = bds_aam_get_connection( $user_id, 'meta' );
	if ( empty( $conn['access_token'] ) || empty( $conn['act_id'] ) ) {
		return array( 'available' => false, 'rows' => array() );
	}
	$cache_key = 'bds_aam_ins_' . $user_id;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$res = bds_aam_graph(
		$conn['act_id'] . '/insights',
		$conn['access_token'],
		array(
			'level'       => 'campaign',
			'fields'      => 'campaign_name,campaign_id,spend,impressions,clicks,ctr,cpc',
			'date_preset' => 'this_month',
			'limit'       => 50,
		)
	);
	if ( is_wp_error( $res ) ) {
		$out = array( 'available' => false, 'error' => $res->get_error_message(), 'rows' => array() );
		set_transient( $cache_key, $out, 5 * MINUTE_IN_SECONDS );
		return $out;
	}
	$rows  = array();
	$total = 0.0;
	foreach ( (array) ( $res['data'] ?? array() ) as $r ) {
		$spend   = (float) ( $r['spend'] ?? 0 );
		$total  += $spend;
		$rows[]  = array(
			'campaign'    => (string) ( $r['campaign_name'] ?? '' ),
			'campaign_id' => (string) ( $r['campaign_id'] ?? '' ),
			'spend'       => $spend,
			'impressions' => (int) ( $r['impressions'] ?? 0 ),
			'clicks'      => (int) ( $r['clicks'] ?? 0 ),
			'ctr'         => (float) ( $r['ctr'] ?? 0 ),
			'cpc'         => (float) ( $r['cpc'] ?? 0 ),
		);
	}
	$out = array( 'available' => true, 'rows' => $rows, 'spend_mtd' => $total, 'synced' => time() );
	set_transient( $cache_key, $out, 15 * MINUTE_IN_SECONDS );
	return $out;
}

/* --------------------------- AI creative --------------------------- */

/**
 * @param int   $draft_id Draft id (0 for preview).
 * @param array $brief    Customer brief.
 * @param string $network Network key.
 * @return array<string,mixed>|WP_Error
 */
function bds_aam_ai_generate( $draft_id, $brief, $network = 'facebook' ) {
	$key = bds_aam_openai_key();
	if ( '' === $key ) {
		return new WP_Error( 'bds_aam_ai', 'AI is not configured yet. An administrator needs to add the OpenAI key.' );
	}
	$nets  = bds_aam_networks();
	$label = isset( $nets[ $network ] ) ? $nets[ $network ]['label'] : 'Facebook Ads';

	$guidance = array(
		'google' => 'Google Ads Search: give 8 headlines of max 30 characters and 3 descriptions of max 90 characters, plus 12 keyword ideas grouped into 2 ad groups, plus negative keyword suggestions.',
		'bing'   => 'Microsoft Advertising Search: same shape as Google Ads Search - 8 headlines (30 chars), 3 descriptions (90 chars), keyword ideas and negatives.',
		'youtube' => 'YouTube video ads: give a 5-second hook, a 30-second script outline, an end-card line, and a companion banner headline.',
		'linkedin' => 'LinkedIn Sponsored Content: professional B2B tone, intro text under 150 characters, headline under 70 characters, and job-title/industry/company-size targeting suggestions. Write a public ad only - never a direct message or connection request.',
		'pinterest' => 'Pinterest Ads: vertical pin creative brief, keyword-rich title under 100 characters, description under 500 characters, and interest targeting.',
		'tiktok' => 'TikTok Ads: native-feeling hook in the first 2 seconds, a spoken script, on-screen text beats, and trending-format suggestions. No fake claims.',
		'x'      => 'X Ads: promoted post copy under 280 characters, 3 variants, and follower/interest targeting.',
		'snapchat' => 'Snapchat Ads: vertical full-screen creative brief, 34-character headline, swipe-up CTA and audience.',
		'reddit' => 'Reddit Ads: non-salesy title under 300 characters, honest body copy, and relevant subreddit targeting suggestions. Respect subreddit rules.',
		'yelp'   => 'Yelp Ads: business highlights, category and service-area selection, budget guidance and a Call-to-Action button suggestion.',
	);
	$extra = isset( $guidance[ $network ] ) ? $guidance[ $network ] : 'Meta (Facebook + Instagram) ads: 3 variants with primary text under 125 characters, headline under 40 characters and a short description.';

	$system = 'You are a senior paid-media strategist writing real, compliant ad campaigns. '
		. 'Never invent statistics, testimonials, awards, review counts or guaranteed results. '
		. 'Never promise a specific ROI, ranking or lead volume. '
		. 'Avoid claims that would fail Meta, Google or LinkedIn ad policy (no before/after health claims, no personal attributes, no "you" targeting of sensitive traits). '
		. 'This is paid advertising only - write public ads, never direct messages or connection requests. '
		. 'Return strict JSON only.';

	$schema = '{"campaign_name":string,"objective":one of OUTCOME_TRAFFIC|OUTCOME_LEADS|OUTCOME_SALES|OUTCOME_ENGAGEMENT|OUTCOME_AWARENESS,'
		. '"strategy":string,"variants":[{"primary_text":string,"headline":string,"description":string,"cta":string,"angle":string}],'
		. '"targeting":{"countries":[string],"age_min":int,"age_max":int,"interests":[string],"notes":string},'
		. '"budget":{"daily_usd":number,"monthly_usd":number,"rationale":string},'
		. '"keywords":[string],"negative_keywords":[string],"platform_extras":string,"image_brief":string,'
		. '"policy_notes":string}';

	$user = "Network: {$label}\n"
		. "Network-specific requirements: {$extra}\n\n"
		. 'Business: ' . sanitize_text_field( (string) ( $brief['business'] ?? '' ) ) . "\n"
		. 'Website / landing page: ' . esc_url_raw( (string) ( $brief['url'] ?? '' ) ) . "\n"
		. 'What they sell / the offer: ' . sanitize_textarea_field( (string) ( $brief['offer'] ?? '' ) ) . "\n"
		. 'Target customer: ' . sanitize_textarea_field( (string) ( $brief['audience'] ?? '' ) ) . "\n"
		. 'Locations: ' . sanitize_text_field( (string) ( $brief['geo'] ?? '' ) ) . "\n"
		. 'Goal: ' . sanitize_text_field( (string) ( $brief['goal'] ?? 'more customers' ) ) . "\n"
		. 'Tone: ' . sanitize_text_field( (string) ( $brief['tone'] ?? 'clear and friendly' ) ) . "\n"
		. 'Daily budget (USD): ' . (int) ( $brief['daily_budget'] ?? 15 ) . "\n"
		. 'Things to avoid: ' . sanitize_textarea_field( (string) ( $brief['avoid'] ?? '' ) ) . "\n\n"
		. "Produce 3 distinct creative angles. Respond with JSON matching this shape exactly:\n" . $schema;

	$resp = wp_remote_post(
		'https://api.openai.com/v1/chat/completions',
		array(
			'timeout' => 90,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'model'           => (string) get_option( 'bds_aam_model', 'gpt-4o-mini' ),
					'temperature'     => 0.8,
					'response_format' => array( 'type' => 'json_object' ),
					'messages'        => array(
						array( 'role' => 'system', 'content' => $system ),
						array( 'role' => 'user', 'content' => $user ),
					),
				)
			),
		)
	);
	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$json = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
	$text = isset( $json['choices'][0]['message']['content'] ) ? (string) $json['choices'][0]['message']['content'] : '';
	if ( '' === $text ) {
		return new WP_Error( 'bds_aam_ai', 'The AI did not return any copy. Try again in a moment.' );
	}
	$data = json_decode( $text, true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'bds_aam_ai', 'The AI response could not be read.' );
	}
	$data['network']      = $network;
	$data['generated_at'] = time();

	if ( $draft_id ) {
		update_post_meta( $draft_id, '_bds_aam_ai', $data );
		update_post_meta( $draft_id, '_bds_aam_status', 'ready' );
		bds_aam_log( $draft_id, 'AI generated ' . count( (array) ( $data['variants'] ?? array() ) ) . ' creative variants for ' . $label );
	}
	return $data;
}

/**
 * @param int    $draft_id Draft.
 * @param string $msg      Message.
 * @return void
 */
function bds_aam_log( $draft_id, $msg ) {
	$log   = (array) get_post_meta( $draft_id, '_bds_aam_log', true );
	$log[] = array( 't' => time(), 'm' => (string) $msg );
	if ( count( $log ) > 40 ) {
		$log = array_slice( $log, -40 );
	}
	update_post_meta( $draft_id, '_bds_aam_log', $log );
}

/* --------------------------- REST --------------------------- */

function bds_aam_register_routes() {
	$ns = 'bds-aam/v1';

	register_rest_route(
		$ns,
		'/status',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'bds_aam_rest_public_status_can',
			'callback'            => 'bds_aam_rest_status',
		)
	);
	register_rest_route(
		$ns,
		'/oauth/start',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_oauth_start',
		)
	);
	register_rest_route(
		$ns,
		'/oauth/callback',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'bds_aam_rest_oauth_callback_can',
			'callback'            => 'bds_aam_rest_oauth_callback',
		)
	);
	register_rest_route(
		$ns,
		'/disconnect',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_disconnect',
		)
	);
	register_rest_route(
		$ns,
		'/assets',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_assets',
		)
	);
	register_rest_route(
		$ns,
		'/select',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_select',
		)
	);
	register_rest_route(
		$ns,
		'/draft',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_draft',
		)
	);
	register_rest_route(
		$ns,
		'/push',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_push',
		)
	);
	register_rest_route(
		$ns,
		'/campaign-status',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_campaign_status',
		)
	);
	register_rest_route(
		$ns,
		'/dashboard',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'bds_aam_rest_customer_can',
			'callback'            => 'bds_aam_rest_dashboard',
		)
	);
	register_rest_route(
		$ns,
		'/seed',
		array(
			'methods'             => 'POST',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'callback'            => function () {
				delete_option( 'bds_aam_seeded' );
				return rest_ensure_response( bds_aam_seed_products() );
			},
		)
	);
}

/**
 * Public status exposes only feature availability and product URLs.
 *
 * @return bool
 */
function bds_aam_rest_public_status_can() {
	return true;
}

/**
 * Customer endpoints need a real WordPress capability check; subscribers keep read.
 *
 * @return bool
 */
function bds_aam_rest_customer_can() {
	return current_user_can( 'read' );
}

/**
 * OAuth providers redirect without a WP nonce, so gate callbacks on server-issued state.
 *
 * @param WP_REST_Request $req Request.
 * @return bool
 */
function bds_aam_rest_oauth_callback_can( $req ) {
	$state = (string) $req->get_param( 'state' );
	return '' !== $state && is_array( get_transient( 'bds_aam_state_' . $state ) );
}

/**
 * @return WP_REST_Response
 */
function bds_aam_rest_status() {
	$nets = array();
	foreach ( bds_aam_networks() as $key => $n ) {
		$nets[ $key ] = array(
			'label'          => $n['label'],
			'automation'     => $n['automation'],
			'tier'           => $n['tier'],
			'api_configured' => bds_aam_net_configured( $key ),
		);
	}
	$plans = array();
	foreach ( bds_aam_plans() as $key => $p ) {
		$pid            = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $p['slug'] ) : 0;
		$plans[ $key ] = array(
			'name'    => $p['name'],
			'price'   => $p['price'],
			'billing' => $p['billing'],
			'cap'     => $p['cap'],
			'sku'     => $p['slug'],
			'url'     => $pid ? get_permalink( $pid ) : '',
		);
	}
	return rest_ensure_response(
		array(
			'ok'       => true,
			'version'  => BDS_AAM_VER,
			'enabled'  => '0' !== (string) get_option( 'bds_aam_enabled', '1' ),
			'ai'       => '' !== bds_aam_openai_key(),
			'page'     => home_url( '/ai-ads/' ),
			'dashboard'=> home_url( '/ai-ads/dashboard/' ),
			'networks' => $nets,
			'plans'    => $plans,
			'note'     => 'Ad budget is paid by the customer directly to each ad platform. BrandDad bills for AI creative and automation only.',
		)
	);
}

/**
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function bds_aam_rest_oauth_start( $req ) {
	$net  = sanitize_key( (string) $req->get_param( 'network' ) );
	$nets = bds_aam_networks();
	if ( ! isset( $nets[ $net ] ) ) {
		return new WP_Error( 'bds_aam_net', 'Unknown network.', array( 'status' => 400 ) );
	}
	$url = bds_aam_authorize_url( $nets[ $net ]['group'], get_current_user_id() );
	if ( is_wp_error( $url ) ) {
		return $url;
	}
	return rest_ensure_response( array( 'ok' => true, 'url' => $url ) );
}

/**
 * @param WP_REST_Request $req Request.
 * @return void
 */
function bds_aam_rest_oauth_callback( $req ) {
	$state = (string) $req->get_param( 'state' );
	$code  = (string) $req->get_param( 'code' );
	$dash  = home_url( '/ai-ads/dashboard/' );
	$meta  = get_transient( 'bds_aam_state_' . $state );

	if ( ! is_array( $meta ) || empty( $meta['group'] ) ) {
		wp_safe_redirect( add_query_arg( 'bds_aam_msg', 'state', $dash ) );
		exit;
	}
	delete_transient( 'bds_aam_state_' . $state );

	if ( '' === $code ) {
		wp_safe_redirect( add_query_arg( 'bds_aam_msg', 'denied', $dash ) );
		exit;
	}

	$tokens = bds_aam_token_exchange( (string) $meta['group'], $code, $state );
	if ( is_wp_error( $tokens ) ) {
		wp_safe_redirect( add_query_arg( 'bds_aam_msg', 'tokenfail', $dash ) );
		exit;
	}
	bds_aam_set_connection( (int) $meta['user_id'], (string) $meta['group'], $tokens );
	if ( 'meta' === $meta['group'] ) {
		bds_aam_meta_sync_assets( (int) $meta['user_id'] );
	}
	wp_safe_redirect( add_query_arg( 'bds_aam_msg', 'connected', $dash ) );
	exit;
}

/**
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function bds_aam_rest_disconnect( $req ) {
	$group = sanitize_key( (string) $req->get_param( 'group' ) );
	bds_aam_drop_connection( get_current_user_id(), $group );
	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * @return WP_REST_Response|WP_Error
 */
function bds_aam_rest_assets() {
	$res = bds_aam_meta_sync_assets( get_current_user_id() );
	return is_wp_error( $res ) ? $res : rest_ensure_response( array( 'ok' => true, 'assets' => $res ) );
}

/**
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function bds_aam_rest_select( $req ) {
	$user_id = get_current_user_id();
	$data    = array();
	foreach ( array( 'act_id', 'page_id' ) as $f ) {
		$v = (string) $req->get_param( $f );
		if ( '' !== $v ) {
			$data[ $f ] = sanitize_text_field( $v );
		}
	}
	if ( $data ) {
		bds_aam_set_connection( $user_id, 'meta', $data );
	}
	return rest_ensure_response( array( 'ok' => true, 'selected' => $data ) );
}

/**
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function bds_aam_rest_draft( $req ) {
	$user_id = get_current_user_id();
	$net     = sanitize_key( (string) $req->get_param( 'network' ) );
	$nets    = bds_aam_networks();
	if ( ! isset( $nets[ $net ] ) ) {
		return new WP_Error( 'bds_aam_net', 'Pick a network.', array( 'status' => 400 ) );
	}

	$brief = array(
		'business'     => sanitize_text_field( (string) $req->get_param( 'business' ) ),
		'url'          => esc_url_raw( (string) $req->get_param( 'url' ) ),
		'offer'        => sanitize_textarea_field( (string) $req->get_param( 'offer' ) ),
		'audience'     => sanitize_textarea_field( (string) $req->get_param( 'audience' ) ),
		'geo'          => sanitize_text_field( (string) $req->get_param( 'geo' ) ),
		'goal'         => sanitize_text_field( (string) $req->get_param( 'goal' ) ),
		'tone'         => sanitize_text_field( (string) $req->get_param( 'tone' ) ),
		'avoid'        => sanitize_textarea_field( (string) $req->get_param( 'avoid' ) ),
		'daily_budget' => max( 1, (int) $req->get_param( 'daily_budget' ) ),
	);
	if ( '' === $brief['business'] ) {
		return new WP_Error( 'bds_aam_brief', 'Add your business name so the AI knows who it is writing for.', array( 'status' => 400 ) );
	}

	$draft_id = wp_insert_post(
		array(
			'post_type'   => BDS_AAM_CPT,
			'post_status' => 'publish',
			'post_author' => $user_id,
			'post_title'  => $brief['business'] . ' - ' . $nets[ $net ]['short'] . ' - ' . gmdate( 'M j' ),
		),
		true
	);
	if ( is_wp_error( $draft_id ) ) {
		return $draft_id;
	}
	update_post_meta( $draft_id, '_bds_aam_brief', $brief );
	update_post_meta( $draft_id, '_bds_aam_network', $net );
	update_post_meta( $draft_id, '_bds_aam_status', 'generating' );

	$ai = bds_aam_ai_generate( $draft_id, $brief, $net );
	if ( is_wp_error( $ai ) ) {
		update_post_meta( $draft_id, '_bds_aam_status', 'error' );
		bds_aam_log( $draft_id, 'AI error: ' . $ai->get_error_message() );
		return $ai;
	}
	return rest_ensure_response( array( 'ok' => true, 'id' => (int) $draft_id, 'ai' => $ai ) );
}

/**
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function bds_aam_rest_push( $req ) {
	$id = (int) $req->get_param( 'id' );
	if ( ! bds_aam_owns_draft( $id ) ) {
		return new WP_Error( 'bds_aam_perm', 'Not your campaign.', array( 'status' => 403 ) );
	}
	$net  = (string) get_post_meta( $id, '_bds_aam_network', true );
	$nets = bds_aam_networks();
	$auto = isset( $nets[ $net ] ) ? $nets[ $net ]['automation'] : 'manual';
	if ( 'full' !== $auto ) {
		return new WP_Error(
			'bds_aam_manual',
			'Automatic campaign build is live for Meta (Facebook + Instagram) today. For ' . ( $nets[ $net ]['label'] ?? 'this network' )
			. ' your drafts are ready to export and launch - we will switch on the direct push as soon as that API is approved on your account.',
			array( 'status' => 409 )
		);
	}
	$res = bds_aam_meta_push( $id );
	return is_wp_error( $res ) ? $res : rest_ensure_response( array( 'ok' => true, 'remote' => $res ) );
}

/**
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function bds_aam_rest_campaign_status( $req ) {
	$id = (int) $req->get_param( 'id' );
	if ( ! bds_aam_owns_draft( $id ) ) {
		return new WP_Error( 'bds_aam_perm', 'Not your campaign.', array( 'status' => 403 ) );
	}
	$res = bds_aam_meta_set_status( $id, (string) $req->get_param( 'status' ) );
	return is_wp_error( $res ) ? $res : rest_ensure_response( $res );
}

/**
 * @param int $id Draft id.
 * @return bool
 */
function bds_aam_owns_draft( $id ) {
	$post = get_post( $id );
	if ( ! $post || BDS_AAM_CPT !== $post->post_type ) {
		return false;
	}
	return current_user_can( 'manage_options' ) || (int) $post->post_author === get_current_user_id();
}

/**
 * @return WP_REST_Response
 */
function bds_aam_rest_dashboard() {
	return rest_ensure_response( bds_aam_dashboard_payload( get_current_user_id() ) );
}

/**
 * @param int $user_id User.
 * @return array<string,mixed>
 */
function bds_aam_dashboard_payload( $user_id ) {
	$plan = bds_aam_user_plan( $user_id );
	$nets = array();
	foreach ( bds_aam_networks() as $key => $n ) {
		$st           = bds_aam_net_status( $key, $user_id );
		$nets[ $key ] = array(
			'label'      => $n['label'],
			'automation' => $n['automation'],
			'state'      => $st['state'],
			'status'     => $st['label'],
			'in_plan'    => bds_aam_plan_allows( $key, $plan ),
			'group'      => $n['group'],
			'note'       => isset( $n['note'] ) ? $n['note'] : '',
		);
	}
	$drafts = get_posts(
		array(
			'post_type'      => BDS_AAM_CPT,
			'author'         => $user_id,
			'posts_per_page' => 30,
			'post_status'    => 'publish',
		)
	);
	$list = array();
	foreach ( $drafts as $d ) {
		$remote = (array) get_post_meta( $d->ID, '_bds_aam_remote', true );
		$list[] = array(
			'id'      => (int) $d->ID,
			'title'   => get_the_title( $d ),
			'network' => (string) get_post_meta( $d->ID, '_bds_aam_network', true ),
			'status'  => (string) get_post_meta( $d->ID, '_bds_aam_status', true ),
			'remote'  => ! empty( $remote['campaign_id'] ) ? array( 'campaign_id' => $remote['campaign_id'] ) : null,
			'created' => get_post_time( 'c', true, $d ),
		);
	}
	$conn = bds_aam_get_connection( $user_id, 'meta' );
	return array(
		'ok'        => true,
		'plan'      => $plan,
		'networks'  => $nets,
		'campaigns' => $list,
		'meta'      => array(
			'connected'   => ! empty( $conn['access_token'] ),
			'act_id'      => (string) ( $conn['act_id'] ?? '' ),
			'page_id'     => (string) ( $conn['page_id'] ?? '' ),
			'ad_accounts' => (array) ( $conn['ad_accounts'] ?? array() ),
			'pages'       => (array) ( $conn['pages'] ?? array() ),
		),
		'insights'  => bds_aam_meta_insights( $user_id ),
	);
}

/* --------------------------- Front end --------------------------- */

function bds_aam_path() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
	return untrailingslashit( strtolower( $path ) );
}

/**
 * Inner output buffer (inside footer-lock) so Squirrly homepage title/canonical cannot stick on /ai-ads/.
 *
 * @return void
 */
function bds_aam_buffer_seo() {
	if ( '0' === (string) get_option( 'bds_aam_enabled', '1' ) ) {
		return;
	}
	$path = bds_aam_path();
	if ( '/ai-ads' !== $path && '/ai-ads/dashboard' !== $path && '/ai-ads/connect' !== $path && '/account/ai-ads' !== $path ) {
		return;
	}
	ob_start( 'bds_aam_seo_rewrite_html' );
}

/**
 * @param string $html HTML.
 * @return string
 */
function bds_aam_seo_rewrite_html( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return is_string( $html ) ? $html : '';
	}
	$path  = bds_aam_path();
	$title = ( '/ai-ads/dashboard' === $path || '/ai-ads/connect' === $path || '/account/ai-ads' === $path )
		? 'AI Ads dashboard | BrandDad Social'
		: 'AI Ads | BrandDad Social';
	$canon = ( '/ai-ads/dashboard' === $path || '/ai-ads/connect' === $path || '/account/ai-ads' === $path )
		? home_url( '/ai-ads/dashboard/' )
		: home_url( '/ai-ads/' );
	$next  = preg_replace( '#<title>[^<]*</title>#i', '<title>' . esc_html( $title ) . '</title>', $html, 1 );
	if ( is_string( $next ) ) {
		$html = $next;
	}
	$next = preg_replace(
		'#<link\s+rel=["\']canonical["\'][^>]*>#i',
		'<link rel="canonical" href="' . esc_url( $canon ) . '" />',
		$html
	);
	return is_string( $next ) ? $next : $html;
}

function bds_aam_render_route() {
	$route = get_query_var( 'bds_aam' );
	if ( ! $route ) {
		// Soft path so /ai-ads/ works before/without rewrite flush (otherwise WP 200s the homepage).
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
		$path = untrailingslashit( strtolower( $path ) );
		if ( '/ai-ads/dashboard' === $path || '/account/ai-ads' === $path ) {
			$route = 'dashboard';
		} elseif ( '/ai-ads/connect' === $path ) {
			$route = 'connect';
		} elseif ( '/ai-ads' === $path ) {
			$route = 'home';
		}
	}
	if ( ! $route ) {
		return;
	}
	if ( '0' === (string) get_option( 'bds_aam_enabled', '1' ) ) {
		return;
	}
	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404        = false;
		$wp_query->is_home       = false;
		$wp_query->is_front_page = false;
		$wp_query->is_page       = true;
		$wp_query->is_singular   = true;
	}
	status_header( 200 );
	nocache_headers();
	$title = 'dashboard' === $route || 'connect' === $route
		? 'AI Ads dashboard | BrandDad Social'
		: 'AI Ads | BrandDad Social';
	$canon = 'dashboard' === $route || 'connect' === $route
		? home_url( '/ai-ads/dashboard/' )
		: home_url( '/ai-ads/' );
	$set_title = static function () use ( $title ) {
		return $title;
	};
	add_filter( 'wp_title', $set_title, 99999 );
	add_filter( 'pre_get_document_title', $set_title, 99999 );
	add_filter( 'wpseo_title', $set_title, 99999 );
	add_filter( 'rank_math/frontend/title', $set_title, 99999 );
	add_filter(
		'get_canonical_url',
		static function () use ( $canon ) {
			return $canon;
		},
		99999
	);
	get_header();
	echo '<div class="bds-aam-wrap">';
	echo bds_aam_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( 'dashboard' === $route || 'connect' === $route ) {
		bds_aam_render_dashboard();
	} else {
		bds_aam_render_home();
	}
	echo '</div>';
	get_footer();
	exit;
}

function bds_aam_css() {
	return '<style>
.bds-aam-wrap{max-width:1120px;margin:0 auto;padding:32px 18px 64px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#0f172a;line-height:1.6}
.bds-aam-wrap h1{font-size:2.4rem;line-height:1.15;margin:0 0 12px}
.bds-aam-wrap h2{font-size:1.55rem;margin:40px 0 14px}
.bds-aam-wrap h3{font-size:1.1rem;margin:0 0 8px}
.bds-aam-lede{font-size:1.12rem;color:#334155;max-width:760px}
.bds-aam-note{background:#f1f5f9;border-left:4px solid #2563eb;padding:14px 16px;border-radius:0 8px 8px 0;margin:22px 0;font-size:.98rem}
.bds-aam-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));margin:18px 0}
.bds-aam-card{border:1px solid #e2e8f0;border-radius:12px;padding:18px;background:#fff}
.bds-aam-price{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));margin:22px 0}
.bds-aam-tier{border:1px solid #e2e8f0;border-radius:14px;padding:22px;background:#fff;display:flex;flex-direction:column}
.bds-aam-tier.feat{border-color:#2563eb;box-shadow:0 8px 30px rgba(37,99,235,.12)}
.bds-aam-tier .amt{font-size:2.1rem;font-weight:700;margin:6px 0 2px}
.bds-aam-tier .per{color:#64748b;font-size:.9rem}
.bds-aam-tier ul{padding-left:18px;margin:14px 0;font-size:.94rem;color:#334155}
.bds-aam-tier li{margin:6px 0}
.bds-aam-btn{display:inline-block;background:#2563eb;color:#fff!important;padding:11px 18px;border-radius:9px;text-decoration:none;font-weight:600;border:0;cursor:pointer;font-size:.96rem}
.bds-aam-btn.ghost{background:#fff;color:#2563eb!important;border:1px solid #2563eb}
.bds-aam-btn[disabled]{opacity:.5;cursor:not-allowed}
.bds-aam-chip{display:inline-block;font-size:.76rem;font-weight:600;padding:3px 9px;border-radius:999px;margin:2px 4px 2px 0}
.chip-connected{background:#dcfce7;color:#166534}
.chip-needs_keys{background:#fef3c7;color:#92400e}
.chip-ready_to_connect{background:#dbeafe;color:#1e40af}
.chip-manual{background:#ede9fe;color:#5b21b6}
.bds-aam-table{width:100%;border-collapse:collapse;margin:14px 0;font-size:.94rem}
.bds-aam-table th,.bds-aam-table td{text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;vertical-align:top}
.bds-aam-table th{background:#f8fafc;font-weight:600}
.bds-aam-form label{display:block;font-weight:600;font-size:.9rem;margin:12px 0 4px}
.bds-aam-form input,.bds-aam-form textarea,.bds-aam-form select{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:.95rem;font-family:inherit}
.bds-aam-form textarea{min-height:72px}
.bds-aam-out{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:10px;font-size:.86rem;overflow:auto;max-height:460px}
.bds-aam-meter{height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:8px 0}
.bds-aam-meter i{display:block;height:100%;background:#2563eb}
.bds-aam-msg{padding:12px 14px;border-radius:9px;margin:14px 0;font-size:.95rem}
.bds-aam-msg.ok{background:#dcfce7;color:#166534}
.bds-aam-msg.err{background:#fee2e2;color:#991b1b}
</style>';
}

function bds_aam_render_home() {
	$plans = bds_aam_plans();
	$nets  = bds_aam_networks();
	?>
	<h1>Automated social &amp; search ads, built by AI</h1>
	<p class="bds-aam-lede">Connect the ad accounts you already own. BrandDad's AI writes the creative, builds the campaigns
	and keeps them fresh - you approve before a single dollar is spent.</p>

	<div class="bds-aam-note">
		<strong>How the money works:</strong> your ad budget is paid <em>directly to Facebook/Meta, Google, LinkedIn, TikTok</em>
		and the rest, on your own card, inside your own account. BrandDad never holds, marks up or resells media spend.
		Our fee covers the AI creative and the campaign automation.
	</div>

	<h2>Networks we automate</h2>
	<table class="bds-aam-table">
		<thead><tr><th>Network</th><th>What the automation does</th><th>Included from</th></tr></thead>
		<tbody>
		<?php
		$auto_label = array(
			'full'          => 'Connect -> AI creative -> campaign built in your account (paused) -> one-click launch',
			'connect_draft' => 'Connect -> AI creative, targeting and budget plan -> export and launch',
			'manual'        => 'AI creative, categories and budget plan -> launch inside the platform',
		);
		$tier_label = array( 'starter' => 'Starter', 'growth' => 'Growth', 'scale' => 'Scale' );
		foreach ( $nets as $key => $n ) {
			$st = bds_aam_net_status( $key, get_current_user_id() );
			echo '<tr><td><strong>' . esc_html( $n['label'] ) . '</strong><br>'
				. '<span class="bds-aam-chip chip-' . esc_attr( $st['state'] ) . '">' . esc_html( $st['label'] ) . '</span></td>'
				. '<td>' . esc_html( $auto_label[ $n['automation'] ] ) . ( ! empty( $n['note'] ) ? '<br><small>' . esc_html( $n['note'] ) . '</small>' : '' ) . '</td>'
				. '<td>' . esc_html( $tier_label[ $n['tier'] ] ?? '-' ) . '</td></tr>';
		}
		?>
		</tbody>
	</table>
	<p><small>Status is read live from our configuration - "Needs API keys" means that platform's developer app is still being
	approved, and your campaigns for it arrive as ready-to-launch drafts in the meantime. We never show a network as connected
	when it is not.</small></p>

	<h2>Pricing</h2>
	<p class="bds-aam-lede">Flat monthly management. No percentage of spend, no minimum commitment, cancel any time.</p>
	<div class="bds-aam-price">
		<?php
		foreach ( array( 'setup', 'starter', 'growth', 'scale' ) as $key ) {
			$p   = $plans[ $key ];
			$pid = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $p['slug'] ) : 0;
			$url = $pid ? get_permalink( $pid ) : home_url( '/shop/' );
			echo '<div class="bds-aam-tier' . ( 'growth' === $key ? ' feat' : '' ) . '">';
			echo '<h3>' . esc_html( $p['name'] ) . '</h3>';
			echo '<div class="amt">$' . esc_html( number_format( (int) $p['price'] ) ) . '</div>';
			echo '<div class="per">' . esc_html( 'month' === $p['billing'] ? 'per month' : 'one-time' ) . '</div>';
			echo '<ul>';
			foreach ( $p['bullets'] as $b ) {
				echo '<li>' . esc_html( $b ) . '</li>';
			}
			echo '</ul>';
			echo '<p style="margin-top:auto"><a class="bds-aam-btn" href="' . esc_url( $url ) . '">Get ' . esc_html( $p['name'] ) . '</a></p>';
			echo '</div>';
		}
		?>
	</div>
	<?php
	$addon = $plans['spend1k'];
	$aid   = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $addon['slug'] ) : 0;
	?>
	<div class="bds-aam-card">
		<h3>Need to spend more than your plan allows?</h3>
		<p><strong>Extra Spend Tier - $99/month per additional $1,000</strong> of managed ad spend. Stack as many as you need.
		Above $5,000/month total we will put together a custom quote so you are not overpaying for volume.</p>
		<?php if ( $aid ) : ?>
			<p><a class="bds-aam-btn ghost" href="<?php echo esc_url( get_permalink( $aid ) ); ?>">Add spend capacity</a></p>
		<?php endif; ?>
	</div>

	<h2>Why we price it this way</h2>
	<div class="bds-aam-grid">
		<div class="bds-aam-card"><h3>You keep control of the money</h3>
		<p>Your card stays on your Meta, Google and LinkedIn accounts. If you pause with us, your ad accounts, pixels,
		audiences and campaign history are still yours.</p></div>
		<div class="bds-aam-card"><h3>Flat fee, not a percentage</h3>
		<p>Percentage-of-spend pricing rewards an agency for spending more of your money. A flat fee means the incentive is
		to make the spend work, not to inflate it.</p></div>
		<div class="bds-aam-card"><h3>Spend tiers, not surprise bills</h3>
		<p>Each plan covers a managed-spend ceiling. When you outgrow it you add a spend tier - no renegotiation, no
		retroactive charges.</p></div>
		<div class="bds-aam-card"><h3>No guarantees we can't keep</h3>
		<p>We will not promise a cost per lead or a return multiple. You get real numbers from the platform APIs and honest
		recommendations on top of them.</p></div>
	</div>

	<h2>How it runs</h2>
	<ol>
		<li><strong>Connect</strong> - authorise BrandDad on the ad accounts you already own. Revoke any time from the dashboard.</li>
		<li><strong>Brief</strong> - a short form about your offer, customer and budget.</li>
		<li><strong>AI drafts</strong> - copy, headlines, CTAs, targeting and a budget plan per network.</li>
		<li><strong>Build</strong> - on Meta we create the campaign, ad set and ads in your account, <em>paused</em>. On other networks you get an export ready to paste in.</li>
		<li><strong>You approve</strong> - one click to launch. Nothing spends until you say so.</li>
		<li><strong>Ongoing</strong> - fresh creative each cycle plus real reported spend and results in your dashboard.</li>
	</ol>

	<p style="margin-top:28px">
		<a class="bds-aam-btn" href="<?php echo esc_url( home_url( '/ai-ads/dashboard/' ) ); ?>">Open your AI Ads dashboard</a>
		&nbsp;
		<a class="bds-aam-btn ghost" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">See all BrandDad services</a>
	</p>
	<?php
}

function bds_aam_render_dashboard() {
	if ( ! is_user_logged_in() ) {
		echo '<h1>AI Ads dashboard</h1><p class="bds-aam-lede">Sign in to connect your ad accounts and see your campaigns.</p>';
		echo '<p><a class="bds-aam-btn" href="' . esc_url( wp_login_url( home_url( '/ai-ads/dashboard/' ) ) ) . '">Sign in</a> '
			. '<a class="bds-aam-btn ghost" href="' . esc_url( home_url( '/ai-ads/' ) ) . '">See plans</a></p>';
		return;
	}

	$user_id = get_current_user_id();
	$data    = bds_aam_dashboard_payload( $user_id );
	$plan    = $data['plan'];
	$msg     = isset( $_GET['bds_aam_msg'] ) ? sanitize_key( wp_unslash( $_GET['bds_aam_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	echo '<h1>Your AI Ads dashboard</h1>';

	if ( 'connected' === $msg ) {
		echo '<div class="bds-aam-msg ok">Account connected. Pick the ad account and page below, then generate your first drafts.</div>';
	} elseif ( 'tokenfail' === $msg ) {
		echo '<div class="bds-aam-msg err">The platform rejected the connection. Try again, or contact us if it keeps failing.</div>';
	} elseif ( 'denied' === $msg ) {
		echo '<div class="bds-aam-msg err">Connection cancelled - no access was granted.</div>';
	} elseif ( 'state' === $msg ) {
		echo '<div class="bds-aam-msg err">That connection link expired. Please start the connection again.</div>';
	}

	// Plan card.
	echo '<div class="bds-aam-card">';
	if ( empty( $plan['plan'] ) ) {
		echo '<h3>No active AI Ads plan</h3><p>You can still connect accounts and generate AI drafts. A plan is required to '
			. 'build and launch campaigns automatically.</p>'
			. '<p><a class="bds-aam-btn" href="' . esc_url( home_url( '/ai-ads/' ) ) . '">See plans</a></p>';
	} else {
		echo '<h3>' . esc_html( $plan['name'] ) . '</h3>';
		if ( $plan['cap'] > 0 ) {
			$spend = isset( $data['insights']['spend_mtd'] ) ? (float) $data['insights']['spend_mtd'] : 0.0;
			$pct   = $plan['cap'] > 0 ? min( 100, (int) round( ( $spend / $plan['cap'] ) * 100 ) ) : 0;
			echo '<p>Managed ad spend ceiling: <strong>$' . esc_html( number_format( (int) $plan['cap'] ) ) . '/month</strong>';
			if ( ! empty( $plan['addons'] ) ) {
				echo ' <small>(includes ' . (int) $plan['addons'] . ' extra spend tier' . ( $plan['addons'] > 1 ? 's' : '' ) . ')</small>';
			}
			echo '</p>';
			if ( ! empty( $data['insights']['available'] ) ) {
				echo '<div class="bds-aam-meter"><i style="width:' . (int) $pct . '%"></i></div>';
				echo '<p><small>$' . esc_html( number_format( $spend, 2 ) ) . ' spent this month, reported by Meta.</small></p>';
			} else {
				echo '<p><small>Spend appears here once a connected platform reports it. We do not estimate numbers.</small></p>';
			}
		}
	}
	echo '</div>';

	// Networks.
	echo '<h2>Your networks</h2><table class="bds-aam-table"><thead><tr><th>Network</th><th>Status</th><th>In your plan</th><th></th></tr></thead><tbody>';
	foreach ( $data['networks'] as $key => $n ) {
		echo '<tr><td><strong>' . esc_html( $n['label'] ) . '</strong>'
			. ( $n['note'] ? '<br><small>' . esc_html( $n['note'] ) . '</small>' : '' ) . '</td>';
		echo '<td><span class="bds-aam-chip chip-' . esc_attr( $n['state'] ) . '">' . esc_html( $n['status'] ) . '</span></td>';
		echo '<td>' . ( $n['in_plan'] ? 'Yes' : '<small>Upgrade to add</small>' ) . '</td>';
		echo '<td>';
		if ( 'ready_to_connect' === $n['state'] ) {
			echo '<button class="bds-aam-btn" data-aam-connect="' . esc_attr( $key ) . '">Connect</button>';
		} elseif ( 'connected' === $n['state'] ) {
			echo '<button class="bds-aam-btn ghost" data-aam-disconnect="' . esc_attr( $n['group'] ) . '">Disconnect</button>';
		} elseif ( 'needs_keys' === $n['state'] ) {
			echo '<small>Our developer app for this network is pending approval. Drafts still work.</small>';
		} else {
			echo '<small>Drafts + guided launch</small>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';

	// Meta asset pickers.
	if ( ! empty( $data['meta']['connected'] ) ) {
		echo '<div class="bds-aam-card"><h3>Meta ad account &amp; page</h3>';
		echo '<div class="bds-aam-form"><label for="aam-act">Ad account</label><select id="aam-act">';
		foreach ( $data['meta']['ad_accounts'] as $a ) {
			echo '<option value="' . esc_attr( $a['id'] ) . '"' . selected( $a['id'], $data['meta']['act_id'], false ) . '>'
				. esc_html( $a['name'] . ' (' . $a['id'] . ')' ) . '</option>';
		}
		echo '</select>';
		echo '<label for="aam-page">Facebook Page (also used for Instagram placements)</label><select id="aam-page">';
		foreach ( $data['meta']['pages'] as $p ) {
			echo '<option value="' . esc_attr( $p['id'] ) . '"' . selected( $p['id'], $data['meta']['page_id'], false ) . '>'
				. esc_html( $p['name'] ) . '</option>';
		}
		echo '</select>';
		echo '<p style="margin-top:12px"><button class="bds-aam-btn" id="aam-save-assets">Save selection</button> '
			. '<button class="bds-aam-btn ghost" id="aam-resync">Re-sync from Meta</button></p></div></div>';
	}

	// Brief form.
	echo '<h2>Generate campaign drafts</h2><div class="bds-aam-card bds-aam-form">';
	echo '<label for="aam-net">Network</label><select id="aam-net">';
	foreach ( $data['networks'] as $key => $n ) {
		echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $n['label'] ) . '</option>';
	}
	echo '</select>';
	echo '<label for="aam-business">Business name</label><input id="aam-business" type="text" placeholder="Your business">';
	echo '<label for="aam-url">Landing page URL</label><input id="aam-url" type="url" placeholder="https://">';
	echo '<label for="aam-offer">What are you advertising?</label><textarea id="aam-offer" placeholder="The product, service or offer, and what makes it worth clicking."></textarea>';
	echo '<label for="aam-audience">Who is the customer?</label><textarea id="aam-audience" placeholder="Who buys this, what problem they have."></textarea>';
	echo '<label for="aam-geo">Locations</label><input id="aam-geo" type="text" placeholder="US, or specific cities">';
	echo '<label for="aam-goal">Goal</label><input id="aam-goal" type="text" placeholder="Leads, sales, bookings, traffic">';
	echo '<label for="aam-tone">Tone</label><input id="aam-tone" type="text" placeholder="Friendly and direct">';
	echo '<label for="aam-budget">Daily budget (USD)</label><input id="aam-budget" type="number" min="1" value="15">';
	echo '<label for="aam-avoid">Anything to avoid?</label><textarea id="aam-avoid" placeholder="Claims, words or competitors to stay away from."></textarea>';
	echo '<p style="margin-top:14px"><button class="bds-aam-btn" id="aam-generate">Generate AI drafts</button></p>';
	echo '<div id="aam-result"></div></div>';

	// Campaign list.
	echo '<h2>Your campaigns</h2>';
	if ( empty( $data['campaigns'] ) ) {
		echo '<p>No campaigns yet. Generate your first drafts above.</p>';
	} else {
		echo '<table class="bds-aam-table"><thead><tr><th>Campaign</th><th>Network</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
		$labels = array(
			'ready'         => 'AI drafts ready',
			'generating'    => 'Generating...',
			'pushed_paused' => 'Built in ad account - paused',
			'active'        => 'Live (confirmed by platform)',
			'paused'        => 'Paused',
			'error'         => 'Needs attention',
		);
		foreach ( $data['campaigns'] as $c ) {
			$nl = $data['networks'][ $c['network'] ]['label'] ?? $c['network'];
			echo '<tr><td>' . esc_html( $c['title'] ) . '</td><td>' . esc_html( $nl ) . '</td>';
			echo '<td>' . esc_html( $labels[ $c['status'] ] ?? $c['status'] ) . '</td><td>';
			if ( 'ready' === $c['status'] ) {
				echo '<button class="bds-aam-btn" data-aam-push="' . (int) $c['id'] . '">Build in ad account</button> ';
			}
			if ( in_array( $c['status'], array( 'pushed_paused', 'paused' ), true ) ) {
				echo '<button class="bds-aam-btn" data-aam-launch="' . (int) $c['id'] . '">Launch</button> ';
			}
			if ( 'active' === $c['status'] ) {
				echo '<button class="bds-aam-btn ghost" data-aam-pause="' . (int) $c['id'] . '">Pause</button> ';
			}
			echo '<a class="bds-aam-btn ghost" href="' . esc_url( add_query_arg( 'aam_view', (int) $c['id'], home_url( '/ai-ads/dashboard/' ) ) ) . '">View copy</a>';
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	// Inline viewer.
	$view = isset( $_GET['aam_view'] ) ? (int) $_GET['aam_view'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $view && bds_aam_owns_draft( $view ) ) {
		$ai = (array) get_post_meta( $view, '_bds_aam_ai', true );
		echo '<h2>' . esc_html( get_the_title( $view ) ) . '</h2>';
		echo '<div class="bds-aam-out">' . esc_html( wp_json_encode( $ai, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</div>';
	}

	// Reported results.
	if ( ! empty( $data['insights']['available'] ) && ! empty( $data['insights']['rows'] ) ) {
		echo '<h2>Reported results this month</h2>';
		echo '<table class="bds-aam-table"><thead><tr><th>Campaign</th><th>Spend</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>CPC</th></tr></thead><tbody>';
		foreach ( $data['insights']['rows'] as $r ) {
			echo '<tr><td>' . esc_html( $r['campaign'] ) . '</td>'
				. '<td>$' . esc_html( number_format( $r['spend'], 2 ) ) . '</td>'
				. '<td>' . esc_html( number_format( $r['impressions'] ) ) . '</td>'
				. '<td>' . esc_html( number_format( $r['clicks'] ) ) . '</td>'
				. '<td>' . esc_html( number_format( $r['ctr'], 2 ) ) . '%</td>'
				. '<td>$' . esc_html( number_format( $r['cpc'], 2 ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><small>Numbers come straight from the Meta Marketing API. Networks without a live API connection show no numbers rather than guesses.</small></p>';
	}

	echo '<div class="bds-aam-note">Ad budget is charged by each platform to your own payment method. '
		. 'BrandDad\'s fee covers AI creative and automation only.</div>';

	bds_aam_dashboard_js();
}

function bds_aam_dashboard_js() {
	$nonce = wp_create_nonce( 'wp_rest' );
	$root  = esc_url_raw( rest_url( 'bds-aam/v1/' ) );
	?>
	<script>
	(function(){
		var ROOT = <?php echo wp_json_encode( $root ); ?>;
		var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
		function api(path, body, method){
			return fetch(ROOT + path, {
				method: method || (body ? 'POST' : 'GET'),
				headers: {'Content-Type':'application/json','X-WP-Nonce':NONCE},
				credentials:'same-origin',
				body: body ? JSON.stringify(body) : undefined
			}).then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); });
		}
		function say(el, text, bad){
			el.innerHTML = '<div class="bds-aam-msg ' + (bad ? 'err' : 'ok') + '"></div>';
			el.firstChild.textContent = text;
		}
		document.querySelectorAll('[data-aam-connect]').forEach(function(b){
			b.addEventListener('click', function(){
				b.disabled = true;
				api('oauth/start?network=' + encodeURIComponent(b.dataset.aamConnect)).then(function(r){
					if (r.ok && r.j.url) { window.location.href = r.j.url; }
					else { b.disabled = false; alert((r.j && r.j.message) || 'Could not start the connection.'); }
				});
			});
		});
		document.querySelectorAll('[data-aam-disconnect]').forEach(function(b){
			b.addEventListener('click', function(){
				if (!confirm('Disconnect this account? Your campaigns stay in the ad account.')) return;
				api('disconnect', {group:b.dataset.aamDisconnect}).then(function(){ location.reload(); });
			});
		});
		var save = document.getElementById('aam-save-assets');
		if (save) save.addEventListener('click', function(){
			save.disabled = true;
			api('select', {act_id:(document.getElementById('aam-act')||{}).value, page_id:(document.getElementById('aam-page')||{}).value})
				.then(function(){ location.reload(); });
		});
		var resync = document.getElementById('aam-resync');
		if (resync) resync.addEventListener('click', function(){
			resync.disabled = true; resync.textContent = 'Syncing...';
			api('assets', {}).then(function(){ location.reload(); });
		});
		var gen = document.getElementById('aam-generate');
		if (gen) gen.addEventListener('click', function(){
			var out = document.getElementById('aam-result');
			var v = function(id){ var e = document.getElementById(id); return e ? e.value : ''; };
			if (!v('aam-business')) { say(out, 'Add your business name first.', true); return; }
			gen.disabled = true; gen.textContent = 'Writing your ads...';
			api('draft', {
				network: v('aam-net'), business: v('aam-business'), url: v('aam-url'),
				offer: v('aam-offer'), audience: v('aam-audience'), geo: v('aam-geo'),
				goal: v('aam-goal'), tone: v('aam-tone'), avoid: v('aam-avoid'),
				daily_budget: v('aam-budget')
			}).then(function(r){
				gen.disabled = false; gen.textContent = 'Generate AI drafts';
				if (!r.ok) { say(out, (r.j && r.j.message) || 'Generation failed.', true); return; }
				out.innerHTML = '<div class="bds-aam-msg ok">Drafts ready. Reloading...</div>';
				setTimeout(function(){ location.href = location.pathname + '?aam_view=' + r.j.id; }, 900);
			});
		});
		function act(sel, path, payload){
			document.querySelectorAll(sel).forEach(function(b){
				b.addEventListener('click', function(){
					b.disabled = true; var old = b.textContent; b.textContent = 'Working...';
					api(path, payload(b)).then(function(r){
						if (!r.ok) { b.disabled = false; b.textContent = old; alert((r.j && r.j.message) || 'That did not work.'); return; }
						location.reload();
					});
				});
			});
		}
		act('[data-aam-push]', 'push', function(b){ return {id: parseInt(b.dataset.aamPush,10)}; });
		act('[data-aam-launch]', 'campaign-status', function(b){ return {id: parseInt(b.dataset.aamLaunch,10), status:'ACTIVE'}; });
		act('[data-aam-pause]', 'campaign-status', function(b){ return {id: parseInt(b.dataset.aamPause,10), status:'PAUSED'}; });
	})();
	</script>
	<?php
}

/* --------------------------- Admin --------------------------- */

function bds_aam_admin_menu() {
	add_menu_page(
		'AI Ads Automation',
		'AI Ads',
		'manage_options',
		'bds-ai-ads',
		'bds_aam_render_admin',
		'dashicons-megaphone',
		56
	);
}

function bds_aam_register_settings() {
	$opts = array( 'bds_aam_enabled', 'bds_aam_model', 'bds_aam_openai_key' );
	foreach ( bds_aam_networks() as $n ) {
		foreach ( $n['creds'] as $c ) {
			$opts[] = $c;
		}
	}
	$opts[] = 'bds_aam_google_login_customer_id';
	foreach ( array_unique( $opts ) as $opt ) {
		register_setting( 'bds_aam', $opt, array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
	}
}

function bds_aam_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['bds_aam_seed'] ) && check_admin_referer( 'bds_aam_seed' ) ) {
		delete_option( 'bds_aam_seeded' );
		$report = bds_aam_seed_products();
		update_option( 'bds_aam_seed_report', $report, false );
		echo '<div class="notice notice-success"><p>Products seeded/refreshed.</p></div>';
	}

	echo '<div class="wrap"><h1>AI Ads Automation <small>v' . esc_html( BDS_AAM_VER ) . '</small></h1>';
	echo '<p>Customer-connected ad accounts, AI creative and campaign automation. '
		. 'Ad spend is always paid by the customer to the platform - this module never touches media budget.</p>';

	echo '<h2>Status</h2><table class="widefat striped" style="max-width:900px"><thead><tr>'
		. '<th>Network</th><th>Automation</th><th>Credentials</th></tr></thead><tbody>';
	foreach ( bds_aam_networks() as $key => $n ) {
		$ok = bds_aam_net_configured( $key );
		echo '<tr><td><strong>' . esc_html( $n['label'] ) . '</strong></td><td>' . esc_html( $n['automation'] ) . '</td><td>'
			. ( empty( $n['creds'] ) ? '<em>No API - manual launch</em>' : ( $ok ? '<span style="color:#166534">Configured</span>' : '<span style="color:#92400e">Missing</span>' ) )
			. '<br><small>' . esc_html( $n['creds_help'] ) . '</small></td></tr>';
	}
	echo '</tbody></table>';

	echo '<p><strong>Redirect URI to register on every platform:</strong> <code>' . esc_html( bds_aam_callback_url() ) . '</code></p>';
	echo '<p>OpenAI key: ' . ( '' !== bds_aam_openai_key() ? '<span style="color:#166534">present</span>' : '<span style="color:#92400e">missing</span>' ) . '</p>';

	echo '<form method="post" action="options.php">';
	settings_fields( 'bds_aam' );
	echo '<table class="form-table" role="presentation">';
	bds_aam_field( 'bds_aam_enabled', 'Enabled (1/0)' );
	bds_aam_field( 'bds_aam_model', 'OpenAI model', 'gpt-4o-mini' );
	bds_aam_field( 'bds_aam_openai_key', 'OpenAI key override', '', true );
	$seen = array();
	foreach ( bds_aam_networks() as $n ) {
		foreach ( $n['creds'] as $c ) {
			if ( isset( $seen[ $c ] ) ) {
				continue;
			}
			$seen[ $c ] = 1;
			bds_aam_field( $c, ucwords( str_replace( array( 'bds_aam_', '_' ), array( '', ' ' ), $c ) ), '', ( false !== strpos( $c, 'secret' ) || false !== strpos( $c, 'token' ) ) );
		}
	}
	bds_aam_field( 'bds_aam_google_login_customer_id', 'Google Ads login customer ID (optional)' );
	echo '</table>';
	submit_button();
	echo '</form>';

	echo '<h2>WooCommerce SKUs</h2>';
	$report = get_option( 'bds_aam_seed_report', array() );
	if ( ! empty( $report['products'] ) ) {
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Plan</th><th>SKU</th><th>Price</th><th>URL</th></tr></thead><tbody>';
		foreach ( $report['products'] as $k => $p ) {
			echo '<tr><td>' . esc_html( $k ) . '</td><td>' . esc_html( $p['sku'] ?? '' ) . '</td><td>'
				. esc_html( isset( $p['price'] ) ? '$' . $p['price'] : '' ) . '</td><td>'
				. ( ! empty( $p['url'] ) ? '<a href="' . esc_url( $p['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $p['url'] ) . '</a>' : '-' )
				. '</td></tr>';
		}
		echo '</tbody></table>';
		if ( isset( $report['subscriptions'] ) ) {
			echo '<p><small>WooCommerce Subscriptions ' . ( $report['subscriptions'] ? 'detected - monthly plans are real subscriptions.' : 'not installed - monthly plans are simple products labelled "Monthly".' ) . '</small></p>';
		}
	}
	echo '<form method="post">';
	wp_nonce_field( 'bds_aam_seed' );
	echo '<p><button class="button button-primary" name="bds_aam_seed" value="1">Seed / refresh AI Ads products</button></p>';
	echo '</form>';

	echo '<h2>Guardrails in force</h2><ul style="list-style:disc;margin-left:20px">'
		. '<li>Campaigns are always created <strong>PAUSED</strong>; a customer action is required to launch.</li>'
		. '<li>Launch is blocked when the daily budget exceeds the plan\'s monthly managed-spend ceiling.</li>'
		. '<li>Spend and results are only ever shown when a platform API returned them.</li>'
		. '<li>Networks without configured credentials report "Needs API keys" - never "connected".</li>'
		. '<li>Paid ads only. This module has no direct-message, connection-request or lead-scraping functionality, and must never be used to revive one.</li>'
		. '</ul></div>';
}

/**
 * @param string $opt         Option name.
 * @param string $label       Label.
 * @param string $placeholder Placeholder.
 * @param bool   $secret      Mask the value.
 * @return void
 */
function bds_aam_field( $opt, $label, $placeholder = '', $secret = false ) {
	$val = (string) get_option( $opt, '' );
	echo '<tr><th scope="row"><label for="' . esc_attr( $opt ) . '">' . esc_html( $label ) . '</label></th><td>';
	if ( $secret && '' !== $val ) {
		echo '<input type="password" id="' . esc_attr( $opt ) . '" name="' . esc_attr( $opt ) . '" value="' . esc_attr( $val ) . '" class="regular-text" autocomplete="off"> <em>saved</em>';
	} else {
		echo '<input type="' . ( $secret ? 'password' : 'text' ) . '" id="' . esc_attr( $opt ) . '" name="' . esc_attr( $opt ) . '" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off">';
	}
	echo '</td></tr>';
}
