<?php
/**
 * Plugin Name: BrandDad Nav Fix
 * Description: Experience look on every Directory page (not only home). Leftover theme headers and WhatsApp green are retired. Menus stay converting: Directory Businesses dropdown holds Events/Jobs/Classifieds; Social hides duplicate Home/AI Packages. Does not overwrite featured 0.2.54, look-lock 0.1.45, catalog 0.4.0, or launch 0.5.26.
 * Version: 0.1.11
 * Author: BrandDad Social
 *
 * Deploy: wp-content/mu-plugins/000-bds-nav-fix-mu.php on Social and Directory.
 *
 * Secure design (ingestion of host HTML only):
 * - Primitive: output buffer + wp_nav_menu_objects + GET template_redirect. No new REST.
 *   No POST. No Stripe. No FormWise. No BoxCoin keys. No deposit form.
 * - AuthN: existing WordPress cookie session only. No new passwords, JWT, or MFA.
 * - AuthZ: HTTP_HOST allowlist. go.branddad.social is a hard no-op so short-link 302s stay intact.
 *   Wallet numbers print only when is_user_logged_in() and get_current_user_id() match the
 *   existing bds_crypto_get_balance / bds_crypto_get_held helpers (current user). Platform
 *   wallet is never printed. Guests get doors, not a balance.
 * - Ingestion: rewrites fixed menu labels/ids on WordPress-rendered HTML. Size cap 8MB.
 *   Destinations are hardcoded HTTPS allowlist. Request bytes never enter the regex.
 *   Travel/flights redirects compare REQUEST_URI path to a hardcoded map; query is ignored.
 * - Output: Social AI Packages → https://branddad.social/services/ only. Never bds_po_url
 *   (that helper can restamp the catalog onto the homepage). Directory disabled rows →
 *   /all-listings/ and /featured-advertising/. CSS hides leftover theme header when Experience is present.
 * - Secrets: none. Fail closed: unknown hosts do nothing; non-allowlisted rewrite dests are skipped.
 *
 * Threat sketch: [Browser] -> [WP HTML] -> [allowlisted hrefs]. Residual: cached HTML until this MU is live.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_NAV_FIX_VERSION', '0.1.11' );

function bds_nav_fix_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/[\r\n\x00\t].*/', '', $host );
	$host = preg_replace( '/:\d+$/', '', (string) $host );
	return is_string( $host ) ? $host : '';
}

function bds_nav_fix_is_go() {
	return in_array( bds_nav_fix_host(), array( 'go.branddad.social', 'www.go.branddad.social' ), true );
}

function bds_nav_fix_is_social() {
	return in_array( bds_nav_fix_host(), array( 'branddad.social', 'www.branddad.social' ), true );
}

function bds_nav_fix_is_directory() {
	return in_array( bds_nav_fix_host(), array( 'directory.branddad.social', 'www.directory.branddad.social' ), true );
}

function bds_nav_fix_dest_ok( $url, $prefix ) {
	$url = (string) $url;
	if ( 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}
	return 0 === strpos( $url, (string) $prefix );
}

function bds_nav_fix_services_url() {
	return 'https://branddad.social/services/';
}

function bds_nav_fix_dir_url( $path ) {
	$path = (string) $path;
	if ( '' === $path || '/' !== substr( $path, 0, 1 ) ) {
		return 'https://directory.branddad.social/';
	}
	return 'https://directory.branddad.social' . $path;
}

function bds_nav_fix_retired_product_map() {
	return array(
		'linkedin-viral-posts-for-professionals'                    => 'https://branddad.social/product/ai-linkedin-growth/',
		'linkedin-visibility-amplification-system-for-professionals' => 'https://branddad.social/product/ai-linkedin-growth/',
		'social-media-management'                                    => 'https://branddad.social/product/ai-social-growth/',
		'comprehensive-seo-packages-rank-1-on-google'                => 'https://branddad.social/product/ai-local-seo/',
	);
}

function bds_nav_fix_product_dest_ok( $url ) {
	$url  = (string) $url;
	$path = (string) parse_url( $url, PHP_URL_PATH );
	if ( ! bds_nav_fix_dest_ok( $url, 'https://branddad.social/product/' ) ) {
		return false;
	}
	return (bool) preg_match( '#^/product/(ai-linkedin-growth|ai-social-growth|ai-local-seo)/$#', $path );
}

function bds_nav_fix_rewrite_product_href( $href ) {
	$href = (string) $href;
	$host = strtolower( (string) parse_url( $href, PHP_URL_HOST ) );
	$path = (string) parse_url( $href, PHP_URL_PATH );
	if ( ! in_array( $host, array( 'branddad.social', 'www.branddad.social', '' ), true ) ) {
		return $href;
	}
	if ( ! preg_match( '#^/product/([a-z0-9-]+)/?$#', $path, $m ) ) {
		return $href;
	}
	$map = bds_nav_fix_retired_product_map();
	if ( ! isset( $map[ $m[1] ] ) ) {
		return $href;
	}
	$dest = $map[ $m[1] ];
	if ( ! bds_nav_fix_product_dest_ok( $dest ) ) {
		return $href;
	}
	return $dest;
}

function bds_nav_fix_href_is_dead( $href ) {
	$href = trim( (string) $href );
	if ( '' === $href || '#' === $href || '/#' === $href ) {
		return true;
	}
	$host = strtolower( (string) parse_url( $href, PHP_URL_HOST ) );
	$path = trim( (string) parse_url( $href, PHP_URL_PATH ), '/' );
	$frag = (string) parse_url( $href, PHP_URL_FRAGMENT );
	if ( ! in_array( $host, array( 'branddad.social', 'www.branddad.social', '' ), true ) ) {
		return false;
	}
	if ( '' !== $path ) {
		return false;
	}
	return ( '' === $frag || 'branddad-ai-solutions' === $frag );
}

function bds_nav_fix_social_html( $html ) {
	$html = (string) $html;
	if ( '' === $html ) {
		return $html;
	}
	$dest = bds_nav_fix_services_url();
	if ( function_exists( 'esc_url' ) ) {
		$dest = esc_url( $dest );
	}
	if ( ! bds_nav_fix_dest_ok( $dest, 'https://branddad.social/services/' ) ) {
		return $html;
	}
	$html = str_replace(
		array(
			'href="https://branddad.social/">AI Packages</a>',
			'href="https://www.branddad.social/">AI Packages</a>',
			'href="https://branddad.social/" aria-current="page">AI Packages</a>',
			'href="https://www.branddad.social/" aria-current="page">AI Packages</a>',
		),
		'href="' . $dest . '">AI Packages</a>',
		$html
	);
	$out = preg_replace_callback(
		'/(<li\b[^>]*id="menu-item-53631"[^>]*>\s*<a\b[^>]*href=")([^"]+)("[^>]*>AI Packages<\/a>)/',
		static function ( $m ) use ( $dest ) {
			if ( bds_nav_fix_href_is_dead( $m[2] ) ) {
				return $m[1] . $dest . $m[3];
			}
			return $m[0];
		},
		$html,
		1
	);
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace_callback(
		'/(<a\b[^>]*href=")([^"]+)("[^>]*data-bds-package-link[^>]*>AI Packages)/',
		static function ( $m ) use ( $dest ) {
			if ( bds_nav_fix_href_is_dead( $m[2] ) ) {
				return $m[1] . $dest . $m[3];
			}
			return $m[0];
		},
		$html
	);
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace_callback(
		'/(<a\b[^>]*data-bds-package-link[^>]*href=")([^"]+)("[^>]*>AI Packages)/',
		static function ( $m ) use ( $dest ) {
			if ( bds_nav_fix_href_is_dead( $m[2] ) ) {
				return $m[1] . $dest . $m[3];
			}
			return $m[0];
		},
		$html
	);
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace(
		'/(id="menu-item-53631"[^>]*\bclass="[^"]*)\bcurrent-menu-item\s*/',
		'$1',
		$html,
		1
	);
	$html = is_string( $out ) ? $out : $html;
	$html = bds_nav_fix_social_services_door( $html );
	$out  = preg_replace(
		'/(id="menu-item-53631"[^>]*\bclass="[^"]*)\bmenu-item-home\s*/',
		'$1',
		$html,
		1
	);
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace(
		'/(<li\b[^>]*id="menu-item-53631"[^>]*>\s*<a\b[^>]*)\s+aria-current="page"/',
		'$1',
		$html,
		1
	);
	$html = is_string( $out ) ? $out : $html;
	foreach ( bds_nav_fix_retired_product_map() as $slug => $dest ) {
		if ( ! bds_nav_fix_product_dest_ok( $dest ) ) {
			continue;
		}
		if ( function_exists( 'esc_url' ) ) {
			$dest = esc_url( $dest );
		}
		$slug = preg_replace( '/[^a-z0-9-]/', '', (string) $slug );
		if ( '' === $slug ) {
			continue;
		}
		$out = preg_replace(
			'~(href=")(?:https://(?:www\.)?branddad\.social)?/product/' . $slug . '/?(?:\?[^"]*)?(")~',
			'$1' . $dest . '$2',
			$html
		);
		$html = is_string( $out ) ? $out : $html;
	}
	$html = bds_nav_fix_social_bar( $html );
	$html = bds_nav_fix_services_jump( $html );
	$html = bds_nav_fix_hub_perks( $html );
	$html = bds_nav_fix_inject_wallet( $html );
	$html = bds_nav_fix_social_strip_gifts( $html );
	$html = bds_nav_fix_strip_health( $html );
	$html = bds_nav_fix_social_on_subject( $html );
	return $html;
}

function bds_nav_fix_strip_health( $html ) {
	$html = (string) $html;
	if ( '' === $html ) {
		return $html;
	}
	if ( false === strpos( $html, 'bds-health' ) && false === strpos( $html, 'bdsHealthEsc' ) ) {
		return $html;
	}
	$out = preg_replace( '#<style\b[^>]*id="bds-health-inline-css"[^>]*>[\s\S]*?</style>#i', '', $html );
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace( '#<script\b[^>]*id="bds-health-boot-js"[^>]*>[\s\S]*?</script>#i', '', $html );
	$html = is_string( $out ) ? $out : $html;
	if ( false !== strpos( $html, 'bdsHealthEsc' ) ) {
		$out  = preg_replace( '#<script\b[^>]*>[\s\S]*?bdsHealthEsc[\s\S]*?</script>#i', '', $html );
		$html = is_string( $out ) ? $out : $html;
	}
	return $html;
}

function bds_nav_fix_social_on_subject( $html ) {
	$html = (string) $html;
	if ( '' === $html ) {
		return $html;
	}
	$html = str_replace( ' — plus Directory member savings across the BrandDad network.', '.', $html );
	$html = str_replace( ' &mdash; plus Directory member savings across the BrandDad network.', '.', $html );
	return $html;
}

function bds_nav_fix_esc( $url ) {
	if ( function_exists( 'esc_url' ) ) {
		return esc_url( (string) $url );
	}
	return (string) $url;
}

function bds_nav_fix_gift_social_url() {
	$fallback = bds_nav_fix_esc( 'https://directory.branddad.social/gift-card-marketplace/' );
	if ( bds_nav_fix_dest_ok( $fallback, 'https://directory.branddad.social/gift-card-marketplace/' ) ) {
		return $fallback;
	}
	return '';
}

function bds_nav_fix_social_strip_gifts( $html ) {
	$html = (string) $html;
	if ( '' === $html ) {
		return $html;
	}
	$out = preg_replace( '#<li[^>]*>\s*<a\b[^>]*href="[^"]*(?:/gift-cards/|/gift-card-marketplace/|/sell-gift-card/|/discount-gift-cards/)[^"]*"[^>]*>[\s\S]*?</a>\s*</li>#i', '', $html );
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace( '#<a\b[^>]*href="[^"]*(?:/gift-cards/|/gift-card-marketplace/|/sell-gift-card/|/discount-gift-cards/)[^"]*"[^>]*>[\s\S]*?</a>#i', '', $html );
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_gift_member_url() {
	$dest = 'https://directory.branddad.social/sign-in/?redirect_to=' . rawurlencode( 'https://directory.branddad.social/gift-card-marketplace/' );
	$u    = bds_nav_fix_esc( $dest );
	if ( bds_nav_fix_dest_ok( $u, 'https://directory.branddad.social/sign-in/' ) ) {
		return $u;
	}
	return bds_nav_fix_gift_social_url();
}

function bds_nav_fix_sell_gift_url() {
	$dest = 'https://directory.branddad.social/sign-in/?redirect_to=' . rawurlencode( 'https://directory.branddad.social/sell-gift-card/' );
	$u    = bds_nav_fix_esc( $dest );
	if ( bds_nav_fix_dest_ok( $u, 'https://directory.branddad.social/sign-in/' ) ) {
		return $u;
	}
	return bds_nav_fix_gift_member_url();
}

function bds_nav_fix_request_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$uri = preg_replace( '/[\r\n\x00\t].*/', '', $uri );
	$path = (string) parse_url( $uri, PHP_URL_PATH );
	return strtolower( trim( (string) $path, '/' ) );
}

function bds_nav_fix_social_redirect_dest( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	if ( in_array( $path, array( 'travel', 'flights' ), true ) ) {
		$dest = 'https://directory.branddad.social/all-listings/?directory_type=travel';
		if ( bds_nav_fix_dest_ok( $dest, 'https://directory.branddad.social/all-listings/' ) ) {
			return $dest;
		}
	}
	if ( in_array( $path, array( 'gift-cards', 'gift-card', 'gifts' ), true ) ) {
		$dest = 'https://directory.branddad.social/sign-in/?redirect_to=' . rawurlencode( 'https://directory.branddad.social/gift-card-marketplace/' );
		if ( bds_nav_fix_dest_ok( $dest, 'https://directory.branddad.social/sign-in/' ) ) {
			return $dest;
		}
	}
	return '';
}

function bds_nav_fix_allowed_redirect_hosts( $hosts ) {
	if ( ! is_array( $hosts ) ) {
		$hosts = array();
	}
	if ( ! in_array( 'directory.branddad.social', $hosts, true ) ) {
		$hosts[] = 'directory.branddad.social';
	}
	return $hosts;
}

function bds_nav_fix_maybe_redirect() {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return;
	}
	if ( ! bds_nav_fix_is_social() ) {
		return;
	}
	$dest = bds_nav_fix_social_redirect_dest( bds_nav_fix_request_path() );
	if ( '' === $dest ) {
		return;
	}
	if ( function_exists( 'wp_safe_redirect' ) ) {
		wp_safe_redirect( $dest, 302 );
		exit;
	}
}

function bds_nav_fix_social_bar( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'id="bds-nav-fix-bar"' ) ) {
		return $html;
	}
	$svc   = bds_nav_fix_esc( bds_nav_fix_services_url() );
	$feat  = bds_nav_fix_esc( 'https://directory.branddad.social/featured-advertising/' );
	$list  = bds_nav_fix_esc( 'https://directory.branddad.social/add-listing/' );
	$acct  = bds_nav_fix_esc( 'https://branddad.social/account-hub/' );
	$help  = bds_nav_fix_esc( 'https://branddad.social/contact-us/' );
	if ( ! bds_nav_fix_dest_ok( $svc, 'https://branddad.social/services/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $feat, 'https://directory.branddad.social/featured-advertising/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $acct, 'https://branddad.social/account-hub/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $list, 'https://directory.branddad.social/add-listing/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $help, 'https://branddad.social/contact-us/' ) ) {
		return $html;
	}
	$links = '<a href="' . $svc . '">Services</a>'
		. '<a href="' . $feat . '">Get Featured</a>'
		. '<a href="' . $acct . '">Account</a>'
		. '<a href="' . $help . '">Contact</a>'
		. '<a class="bds-nav-list-free" href="' . $list . '">List my business free</a>';
	$bar = '<nav id="bds-nav-fix-bar" aria-label="BrandDad menu">'
		. '<div class="bds-nav-fix-desktop">' . $links . '</div>'
		. '<details class="bds-nav-fix-mobile"><summary aria-label="Open BrandDad menu">Menu</summary><div>' . $links . '</div></details>'
		. '</nav>'
		. '<script id="bds-nav-fix-place">(function(){function p(){var n=document.getElementById("bds-nav-fix-bar"),h=document.getElementById("site-header");if(n&&h&&n.parentNode!==h)h.appendChild(n)}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",p,{once:true});else p()})();</script>';
	$out = preg_replace( '/<body[^>]*>/i', '$0' . $bar, $html, 1 );
	return is_string( $out ) ? $out : ( $bar . $html );
}

function bds_nav_fix_services_jump( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'id="bds-svc-jump"' ) || false === strpos( $html, 'bdai-launch' ) ) {
		return $html;
	}
	$hire    = bds_nav_fix_esc( 'https://branddad.social/hire-branddad/' );
	$creator = bds_nav_fix_esc( 'https://branddad.social/creator-services/' );
	$spotify = bds_nav_fix_esc( 'https://branddad.social/spotify-promotion/' );
	$list    = bds_nav_fix_esc( 'https://directory.branddad.social/add-listing/' );
	if ( ! bds_nav_fix_dest_ok( $hire, 'https://branddad.social/hire-branddad/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $creator, 'https://branddad.social/creator-services/' ) ) {
		return $html;
	}
	$spot_html = '';
	if ( bds_nav_fix_dest_ok( $spotify, 'https://branddad.social/spotify-promotion/' ) ) {
		$spot_html = '<a href="' . $spotify . '">Spotify promotion</a>';
	}
	$list_html = '';
	if ( bds_nav_fix_dest_ok( $list, 'https://directory.branddad.social/add-listing/' ) ) {
		$list_html = '<a class="bds-nav-list-free" href="' . $list . '">List my business free</a>';
	}
	$jump = '<nav id="bds-svc-jump" aria-label="On this page">'
		. '<a href="#branddad-ai-solutions">AI plans</a>'
		. '<a href="#branddad-specialty-services">Specialty services</a>'
		. '<a href="' . $hire . '">Hire BrandDad</a>'
		. '<a href="' . $creator . '">Creator campaigns</a>'
		. $spot_html
		. $list_html
		. '</nav>';
	$out  = preg_replace( '/<div class="bdai-launch"[^>]*>/', '$0' . $jump, $html, 1 );
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_hub_perks( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'id="bds-nav-fix-perks"' ) || false === strpos( $html, 'id="bds-account-hub"' ) ) {
		return $html;
	}
	$dash    = bds_nav_fix_esc( 'https://directory.branddad.social/dashboard/' );
	$acct    = bds_nav_fix_esc( 'https://directory.branddad.social/my-account/' );
	$list    = bds_nav_fix_esc( 'https://directory.branddad.social/all-listings/' );
	$aff     = bds_nav_fix_esc( 'https://affiliates.branddad.social/program' );
	if ( ! bds_nav_fix_dest_ok( $dash, 'https://directory.branddad.social/dashboard/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $acct, 'https://directory.branddad.social/my-account/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $list, 'https://directory.branddad.social/all-listings/' ) ) {
		return $html;
	}
	$aff_html = '';
	if ( bds_nav_fix_dest_ok( $aff, 'https://affiliates.branddad.social/' ) ) {
		$aff_html = ' <a href="' . $aff . '">Affiliate portal</a>';
	}
	$block = '<section id="bds-nav-fix-perks"><h2>Member tools</h2><p>This hub is Social orders, billing, and AI drafts. Directory listings and member tools stay on Directory. Sponsored bids and pins use in-account credit on a listing you own. There is no public /wallet/ or /bids/ page, and this screen does not invent a balance.</p>'
		. '<p><a href="' . $dash . '">Directory dashboard</a> <a href="' . $acct . '">My account</a> <a href="' . $list . '">Promote a listing</a>' . $aff_html . '</p></section>';
	$out   = preg_replace( '/<div class="bds-hub" id="bds-account-hub">/', '$0' . $block, $html, 1 );
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_wallet_html() {
	if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
		return '';
	}
	if ( ! function_exists( 'get_current_user_id' ) || ! function_exists( 'bds_crypto_get_balance' ) ) {
		return '';
	}
	$uid = (int) get_current_user_id();
	if ( $uid < 1 ) {
		return '';
	}
	$bal  = (float) bds_crypto_get_balance( $uid );
	$held = function_exists( 'bds_crypto_get_held' ) ? (float) bds_crypto_get_held( $uid ) : 0.0;
	$dash = bds_nav_fix_esc( 'https://directory.branddad.social/dashboard/' );
	$list = bds_nav_fix_esc( 'https://directory.branddad.social/all-listings/' );
	if ( ! bds_nav_fix_dest_ok( $dash, 'https://directory.branddad.social/dashboard/' ) ) {
		return '';
	}
	if ( ! bds_nav_fix_dest_ok( $list, 'https://directory.branddad.social/all-listings/' ) ) {
		return '';
	}
	$bal_s  = number_format( $bal, 2, '.', '' );
	$held_s = number_format( $held, 2, '.', '' );
	return '<section id="bds-nav-fix-wallet"><h2>In-account credit</h2><p>Available ' . $bal_s . '. Held for open bids ' . $held_s . '. This is Directory credit for sponsoring or pinning a listing you own. It is not a bank account. This screen does not take a deposit.</p><p><a href="' . $dash . '">My listings</a> <a href="' . $list . '">See placement on listings</a></p></section>';
}

function bds_nav_fix_inject_wallet( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'id="bds-nav-fix-wallet"' ) ) {
		return $html;
	}
	$block = bds_nav_fix_wallet_html();
	if ( '' === $block ) {
		return $html;
	}
	if ( false !== strpos( $html, 'id="bds-nav-fix-perks"' ) ) {
		$out = preg_replace( '/<section id="bds-nav-fix-perks">/', $block . '<section id="bds-nav-fix-perks">', $html, 1 );
		return is_string( $out ) ? $out : $html;
	}
	if ( false !== strpos( $html, 'id="bds-account-hub"' ) ) {
		$out = preg_replace( '/<div class="bds-hub" id="bds-account-hub">/', '$0' . $block, $html, 1 );
		return is_string( $out ) ? $out : $html;
	}
	if ( false !== strpos( $html, 'woocommerce-account' ) || false !== strpos( $html, 'directorist-user-dashboard' ) ) {
		$out = preg_replace( '/<div class="woocommerce">/', '$0' . $block, $html, 1 );
		if ( is_string( $out ) && $out !== $html ) {
			return $out;
		}
		$out = preg_replace( '/<body[^>]*>/i', '$0' . $block, $html, 1 );
		return is_string( $out ) ? $out : $html;
	}
	return $html;
}

function bds_nav_fix_social_services_door( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'id="menu-item-bds-svc"' ) ) {
		return $html;
	}
	$dest = bds_nav_fix_services_url();
	if ( function_exists( 'esc_url' ) ) {
		$dest = esc_url( $dest );
	}
	if ( ! bds_nav_fix_dest_ok( $dest, 'https://branddad.social/services/' ) ) {
		return $html;
	}
	$li  = '<li id="menu-item-bds-svc" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-bds-svc"><a href="' . $dest . '">Services</a></li> ';
	$out = preg_replace( '/<li id="menu-item-51042"/', $li . '<li id="menu-item-51042"', $html, 1 );
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_directory_html( $html ) {
	$html = (string) $html;
	if ( '' === $html ) {
		return $html;
	}
	$all  = bds_nav_fix_dir_url( '/all-listings/' );
	$feat = bds_nav_fix_dir_url( '/featured-advertising/' );
	if ( function_exists( 'esc_url' ) ) {
		$all  = esc_url( $all );
		$feat = esc_url( $feat );
	}
	if ( ! bds_nav_fix_dest_ok( $all, 'https://directory.branddad.social/all-listings/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $feat, 'https://directory.branddad.social/featured-advertising/' ) ) {
		return $html;
	}
	$out = preg_replace(
		'/<span class="bdd-nav-menu__disabled"[^>]*>Online &amp; Worldwide[^<]*<\/span>/',
		'<a href="' . $all . '">Online &amp; Worldwide</a>',
		$html
	);
	$html = is_string( $out ) ? $out : $html;
	$out  = preg_replace(
		'/<span class="bdd-nav-menu__disabled"[^>]*>Featured Businesses[^<]*<\/span>/',
		'<a href="' . $feat . '">Featured Businesses</a>',
		$html
	);
	$html = is_string( $out ) ? $out : $html;
	$html = bds_nav_fix_fold_dir_nav( $html );
	$html = bds_nav_fix_dir_svc_door( $html );
	$html = bds_nav_fix_dir_member_doors( $html );
	$html = bds_nav_fix_home_welcome( $html );
	$html = bds_nav_fix_hub_perks( $html );
	$html = bds_nav_fix_inject_wallet( $html );
	$html = bds_nav_fix_strip_health( $html );
	return $html;
}

function bds_nav_fix_fold_dir_nav( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'bds-nav-fix-fold' ) ) {
		return $html;
	}
	$events = bds_nav_fix_dir_url( '/all-listings/?directory_type=events' );
	$jobs   = bds_nav_fix_dir_url( '/all-listings/?directory_type=jobs-careers' );
	$class  = bds_nav_fix_dir_url( '/all-listings/?directory_type=classifieds' );
	if ( function_exists( 'esc_url' ) ) {
		$events = esc_url( $events );
		$jobs   = esc_url( $jobs );
		$class  = esc_url( $class );
	}
	if ( ! bds_nav_fix_dest_ok( $events, 'https://directory.branddad.social/all-listings/' ) ) {
		return $html;
	}
	$fold = '<a class="bds-nav-fix-fold" href="' . $events . '">Events</a>'
		. '<a class="bds-nav-fix-fold" href="' . $jobs . '">Jobs</a>'
		. '<a class="bds-nav-fix-fold" href="' . $class . '">Classifieds</a>';
	$out  = preg_replace(
		'/(<div class="bdd-nav-menu__panel">[\s\S]{0,1500}?>Explore Locations<\/a>)/',
		'$1' . $fold,
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_dir_svc_door( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'bds-nav-fix-svc' ) ) {
		return $html;
	}
	$svc = bds_nav_fix_services_url();
	if ( function_exists( 'esc_url' ) ) {
		$svc = esc_url( $svc );
	}
	if ( ! bds_nav_fix_dest_ok( $svc, 'https://branddad.social/services/' ) ) {
		return $html;
	}
	$out = preg_replace(
		'/(<div class="bdd-nav-menu__panel bdd-nav-menu__panel--right">)/',
		'$1<a class="bds-nav-fix-svc" href="' . $svc . '">AI &amp; marketing</a>',
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_dir_member_doors( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'class="bds-nav-fix-perk"' ) ) {
		return $html;
	}
	$gift   = bds_nav_fix_gift_member_url();
	$travel = bds_nav_fix_esc( bds_nav_fix_dir_url( '/all-listings/?directory_type=travel' ) );
	$dash   = bds_nav_fix_esc( bds_nav_fix_dir_url( '/dashboard/' ) );
	$acct   = bds_nav_fix_esc( bds_nav_fix_dir_url( '/my-account/' ) );
	$list   = bds_nav_fix_esc( bds_nav_fix_dir_url( '/all-listings/' ) );
	if ( '' === $gift ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $travel, 'https://directory.branddad.social/all-listings/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $dash, 'https://directory.branddad.social/dashboard/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $acct, 'https://directory.branddad.social/my-account/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $list, 'https://directory.branddad.social/all-listings/' ) ) {
		return $html;
	}
	$bits = '<a class="bds-nav-fix-perk bds-gift-member-perk" href="' . $gift . '">Member gift cards</a>'
		. '<a class="bds-nav-fix-perk" href="' . $travel . '">Travel stays</a>'
		. '<a class="bds-nav-fix-perk" href="' . $dash . '">My dashboard</a>'
		. '<a class="bds-nav-fix-perk" href="' . $acct . '">My account</a>'
		. '<a class="bds-nav-fix-perk" href="' . $list . '">Promote a listing</a>';
	if ( false !== strpos( $html, 'bds-nav-fix-svc' ) ) {
		$out = preg_replace( '/(<a class="bds-nav-fix-svc"[^>]*>AI &amp; marketing<\/a>)/', '$1' . $bits, $html, 1 );
		return is_string( $out ) ? $out : $html;
	}
	$out = preg_replace(
		'/(<div class="bdd-nav-menu__panel bdd-nav-menu__panel--right">)/',
		'$1' . $bits,
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_icon( $kind ) {
	$map = array(
		'store'     => 'M4 10h16v10H4zM4 10l2-5h12l2 5M9 14h6',
		'cal'       => 'M5 7h14v13H5zM5 11h14M9 5v4M15 5v4',
		'job'       => 'M8 7V5h8v2M4 7h16v12H4z',
		'tag'       => 'M4 12l8 8 8-8-8-8H4z',
		'megaphone' => 'M5 10v4h3l8 4V6L8 10H5z',
		'chat'      => 'M5 6h14v10H8l-3 4V6z',
		'food'      => 'M8 3v18M6 3h4M16 4v7a3 3 0 01-3 3v7',
		'tooth'     => 'M8 5c1-2 7-2 8 0 0 4-2 6-2 10h-4c0-4-2-6-2-10z',
		'home'      => 'M4 12l8-7 8 7v8H4z',
		'bed'       => 'M4 18V11h16v7M4 14h16M7 11V8h5v3',
		'spark'     => 'M12 3l1.4 6.6L20 11l-6.6 1.4L12 19l-1.4-6.6L4 11l6.6-1.4z',
		'spa'       => 'M12 20s6-4 6-9a6 6 0 10-12 0c0 5 6 9 6 9z',
		'map'       => 'M4 6l6-2 4 2 6-2v14l-6 2-4-2-6 2z',
		'glass'     => 'M8 4h8l-1 8H9L8 4zM12 12v8M9 20h6',
		'car'       => 'M5 13l2-5h10l2 5M5 13h14v4H5z',
		'gym'       => 'M3 9h3v6H3zM18 9h3v6h-3zM6 11h12v2H6z',
		'pin'       => 'M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11z',
	);
	$d = isset( $map[ $kind ] ) ? $map[ $kind ] : $map['store'];
	return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $d . '"/></svg>';
}

function bds_nav_fix_cat_kind( $href ) {
	$href = strtolower( (string) $href );
	if ( false !== strpos( $href, 'restaurant' ) || false !== strpos( $href, 'baker' ) || false !== strpos( $href, 'coffee' ) || false !== strpos( $href, 'cafe' ) ) {
		return 'food';
	}
	if ( false !== strpos( $href, 'dentist' ) || false !== strpos( $href, 'dental' ) ) {
		return 'tooth';
	}
	if ( false !== strpos( $href, 'real-estate' ) ) {
		return 'home';
	}
	if ( false !== strpos( $href, 'hotel' ) || false !== strpos( $href, 'resort' ) ) {
		return 'bed';
	}
	if ( false !== strpos( $href, 'beauty' ) || false !== strpos( $href, 'salon' ) || false !== strpos( $href, 'hair' ) ) {
		return 'spark';
	}
	if ( false !== strpos( $href, 'spa' ) || false !== strpos( $href, 'massage' ) ) {
		return 'spa';
	}
	if ( false !== strpos( $href, 'tour' ) || false !== strpos( $href, 'travel' ) || false !== strpos( $href, 'adventure' ) ) {
		return 'map';
	}
	if ( false !== strpos( $href, 'bar' ) || false !== strpos( $href, 'nightlife' ) ) {
		return 'glass';
	}
	if ( false !== strpos( $href, 'car' ) || false !== strpos( $href, 'automotive' ) ) {
		return 'car';
	}
	if ( false !== strpos( $href, 'gym' ) ) {
		return 'gym';
	}
	if ( false !== strpos( $href, 'digital' ) || false !== strpos( $href, 'marketing' ) || false !== strpos( $href, 'graphic' ) || false !== strpos( $href, 'creative' ) ) {
		return 'megaphone';
	}
	return 'store';
}

function bds_nav_fix_home_welcome( $html ) {
	$html = (string) $html;
	if ( '' === $html || false !== strpos( $html, 'bds-nav-fix-home' ) || false === strpos( $html, 'id="bdd-experiences-title"' ) ) {
		return $html;
	}
	$digital = bds_nav_fix_dir_url( '/single-category/digital-marketing/?directory_type=digital-services' );
	$design  = bds_nav_fix_dir_url( '/single-category/graphic-design-creative-services/?directory_type=digital-services' );
	$groups  = bds_nav_fix_dir_url( '/all-listings/?directory_type=whatsapp-groups' );
	$chan    = bds_nav_fix_dir_url( '/all-listings/?directory_type=whatsapp-groups&bds_gshape=channel' );
	$svc     = bds_nav_fix_services_url();
	if ( function_exists( 'esc_url' ) ) {
		$digital = esc_url( $digital );
		$design  = esc_url( $design );
		$groups  = esc_url( $groups );
		$chan    = esc_url( $chan );
		$svc     = esc_url( $svc );
	}
	if ( ! bds_nav_fix_dest_ok( $digital, 'https://directory.branddad.social/single-category/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $groups, 'https://directory.branddad.social/all-listings/' ) ) {
		return $html;
	}
	if ( ! bds_nav_fix_dest_ok( $svc, 'https://branddad.social/services/' ) ) {
		$svc = '';
	}
	$html = str_replace(
		array(
			'Find a restaurant, shop, professional, or local service.',
			'See concerts, classes, markets, meetups, and other events near you.',
			'Look for local, remote, or flexible work.',
			'Buy, sell, or discover useful items near you.',
			'class="bdd-experience-grid"',
		),
		array(
			'Find a restaurant, shop, creator, agency, or digital service — nearby or worldwide.',
			'See concerts, classes, markets, livestreams, and creator events.',
			'Look for local, remote, creator, or flexible work.',
			'Buy, sell, or discover useful items nearby or online.',
			'class="bdd-experience-grid bds-nav-fix-home"',
		),
		$html
	);
	$kinds = array(
		'Businesses'       => 'store',
		'Events'           => 'cal',
		'Jobs &amp; Careers' => 'job',
		'Classifieds'      => 'tag',
	);
	foreach ( $kinds as $title => $kind ) {
		$out = preg_replace(
			'/(<article class="bdd-experience">\s*)(<h3>' . preg_quote( $title, '/' ) . '<\/h3>)/',
			'$1<span class="bdd-experience__icon" aria-hidden="true">' . bds_nav_fix_icon( $kind ) . '</span>$2',
			$html,
			1
		);
		$html = is_string( $out ) ? $out : $html;
	}
	$extra  = '<article class="bdd-experience"><span class="bdd-experience__icon" aria-hidden="true">' . bds_nav_fix_icon( 'megaphone' ) . '</span><h3>Creators &amp; digital</h3><p>Find influencers, agencies, social managers, and online businesses.</p><div class="bdd-experience__actions"><a href="' . $digital . '">Browse digital →</a>';
	$extra .= ( '' !== $svc ) ? '<a href="' . $svc . '">Get growth help</a>' : '';
	$extra .= '</div></article>';
	$extra .= '<article class="bdd-experience"><span class="bdd-experience__icon" aria-hidden="true">' . bds_nav_fix_icon( 'chat' ) . '</span><h3>WhatsApp groups &amp; channels</h3><p>Join local and worldwide groups, or follow broadcast channels.</p><div class="bdd-experience__actions"><a href="' . $groups . '">Find groups →</a><a href="' . $chan . '">Find channels</a></div></article>';
	$out = preg_replace_callback(
		'/(<div class="bdd-experience-grid bds-nav-fix-home">[\s\S]{0,9000}<\/article>)\s*<\/div>/',
		static function ( $m ) use ( $extra ) {
			return $m[1] . $extra . '</div>';
		},
		$html,
		1
	);
	$html = is_string( $out ) ? $out : $html;
	if ( false === strpos( $html, 'data-bds-nav-fix-cats' ) && false !== strpos( $html, 'bdd-category-grid' ) ) {
		$lead = '<a class="bdd-category" href="' . $digital . '"><span class="bdd-category__icon" aria-hidden="true">' . bds_nav_fix_icon( 'megaphone' ) . '</span><span><strong>Digital Marketing</strong><small>Creators, ads, and online growth</small></span></a>'
			. '<a class="bdd-category" href="' . $design . '"><span class="bdd-category__icon" aria-hidden="true">' . bds_nav_fix_icon( 'spark' ) . '</span><span><strong>Creative services</strong><small>Design, brands, and digital work</small></span></a>';
		$out  = preg_replace(
			'/<div class="bdd-category-grid">/',
			'<div class="bdd-category-grid" data-bds-nav-fix-cats="1">' . $lead,
			$html,
			1
		);
		$html = is_string( $out ) ? $out : $html;
	}
	$out = preg_replace_callback(
		'/<a class="bdd-category" href="([^"]+)">\s*<span class="bdd-category__icon"[^>]*>[\s\S]*?<\/span>/',
		static function ( $m ) {
			$kind = bds_nav_fix_cat_kind( $m[1] );
			return '<a class="bdd-category" href="' . $m[1] . '"><span class="bdd-category__icon" aria-hidden="true">' . bds_nav_fix_icon( $kind ) . '</span>';
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_nav_fix_recolor_html( $html ) {
	$html = str_replace(
		array(
			'#25d366',
			'#25D366',
			'#12a854',
			'#128c7e',
			'#075e54',
			'#00a884',
			'#0f9f6e',
			'#0F9F6E',
			'#0b7a55',
			'#0B7A55',
			'#12b37c',
			'#12B37C',
			'#065f46',
			'#044833',
			'#063528',
		),
		array(
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#10243a',
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#0668d8',
			'#10243a',
			'#10243a',
			'#10243a',
		),
		(string) $html
	);
	return str_replace(
		array( '15,159,110', '15, 159, 110' ),
		array( '6,104,216', '6, 104, 216' ),
		$html
	);
}

function bds_nav_fix_html( $html ) {
	$html = (string) $html;
	if ( '' === $html || strlen( $html ) > 8000000 ) {
		return $html;
	}
	if ( bds_nav_fix_is_social() ) {
		$html = bds_nav_fix_social_html( $html );
	} elseif ( bds_nav_fix_is_directory() ) {
		$html = bds_nav_fix_directory_html( $html );
	} else {
		return $html;
	}
	$html = bds_nav_fix_recolor_html( $html );
	$css = '';
	if ( bds_nav_fix_is_directory() ) {
		$css = bds_nav_fix_directory_css();
	} elseif ( bds_nav_fix_is_social() ) {
		$css = bds_nav_fix_social_css();
	}
	if ( '' !== $css ) {
		$tag = '<style id="bds-nav-fix">' . $css . '</style>';
		if ( false !== strpos( $html, 'id="bds-nav-fix"' ) ) {
			$out  = preg_replace( '/<style id="bds-nav-fix">.*?<\/style>/is', $tag, $html, 1 );
			$html = is_string( $out ) ? $out : $html;
		} else {
			$out  = preg_replace( '/<head[^>]*>/i', '$0' . $tag, $html, 1 );
			$html = is_string( $out ) ? $out : ( $tag . $html );
		}
	}
	if ( false === strpos( $html, 'id="bds-nav-fix-mark"' ) ) {
		$mark = '<style id="bds-nav-fix-mark">/* nav-fix ' . BDS_NAV_FIX_VERSION . '-hard */</style>';
		$out  = preg_replace( '/<head[^>]*>/i', '$0' . $mark, $html, 1 );
		$html = is_string( $out ) ? $out : ( $mark . $html );
	}
	return $html;
}

function bds_nav_fix_menu_objects( $items ) {
	if ( ! is_array( $items ) || is_admin() || ! bds_nav_fix_is_social() ) {
		return $items;
	}
	$dest = bds_nav_fix_services_url();
	foreach ( $items as $item ) {
		if ( ! is_object( $item ) || ! isset( $item->title, $item->url ) ) {
			continue;
		}
		$label = strtolower( trim( str_replace( array( '→', '&rarr;' ), '', wp_strip_all_tags( (string) $item->title ) ) ) );
		$item->url = bds_nav_fix_rewrite_product_href( (string) $item->url );
		if ( 'ai packages' !== $label ) {
			continue;
		}
		if ( bds_nav_fix_href_is_dead( (string) $item->url ) ) {
			$item->url = $dest;
		}
		if ( isset( $item->classes ) && is_array( $item->classes ) ) {
			$item->classes = array_values(
				array_diff( $item->classes, array( 'current-menu-item', 'menu-item-home' ) )
			);
		}
	}
	return $items;
}

function bds_nav_fix_directory_css() {
	$hide = 'display:none!important;height:0!important;max-height:0!important;min-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important;visibility:hidden!important;pointer-events:none!important';
	return 'html,body{background:#fff!important;background-image:none!important;color:#10243a!important}'
		. 'html{--color-primary:#0668d8;--primary-color:#0668d8;--theme-color:#0668d8;--directorist-color-primary:#0668d8;--bhb-accent:#0668d8;--dtd-accent:#0668d8;--wav-accent:#0668d8;--bh-accent:#0668d8;--gsl-accent:#0668d8}'
		. 'html body header#site-header,html body header#site-header.menu-area,html body header.menu-area,html body .menu-area.sticky-top{' . $hide . '}'
		. 'body:has(header.bdd-header:not(#bds-look-header)) #bds-look-header{' . $hide . '}'
		. '#bds-wa-hero,.bds-wah,#bds-wav-hero,.bds-wav,#bds-dir-types,.bds-dtd,#bds-home-browse,.bds-hbrowse,#bds-home-cta-mid,#bds-home-cta-bottom,.bds-hcta,.bds-gcg-cta,#bdsNetworkConnectStrip,#bdsFbGroupBar,#bds-follow-consent,.branddad-home-ecosystem,.branddad-ecosystem-grid,.bds-health,#bds-health,.bds-health-tools{' . $hide . '}'
		. '.bdd-header,.bdd-header .bdd-header__inner,.bdd-header .bdd-nav,.bdd-nav-menu,.bdd-add-menu,.bdd-header__actions{overflow:visible!important}'
		. '.bdd-header .bdd-nav{flex-wrap:nowrap!important;justify-content:flex-start!important;gap:0;overflow:visible!important}'
		. '.bdd-header .bdd-nav>a,.bdd-header .bdd-nav summary{padding:0 6px;white-space:nowrap}'
		. '.bdd-nav-menu{position:relative;z-index:40}'
		. '.bdd-nav details[open]>.bdd-nav-menu__panel,.bdd-nav-menu[open]>.bdd-nav-menu__panel{display:grid!important;z-index:80!important}'
		. '.bdd-add-menu[open]>nav{display:grid!important;z-index:80!important}'
		. '.bdd-experience__icon,.bdd-category__icon{display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:10px;background:#e8f1fc;color:#0668d8;flex:0 0 32px}'
		. '.bdd-experience h3{display:flex;align-items:center;gap:10px;margin:0 0 8px}'
		. '.bdd-category{display:flex;align-items:center;gap:12px}'
		. '.bdd-header .bdd-nav>a[href*="directory_type=events"],.bdd-header .bdd-nav>a[href*="jobs-careers"],.bdd-header .bdd-nav>a[href*="directory_type=classifieds"]{display:none!important}'
		. '.bdd-nav-menu__disabled{display:none!important}'
		. '.branddad-whatsapp-button,.bds-wah__btn,body.bds-dpo .bds-wah__btn,.directorist-btn-success,.bds-dtd__btn--list,.bds-health__run,.bds-hbrowse__grow-form button,.bds-wav__btn{background:#0668d8!important;background-image:none!important;border-color:#0668d8!important;color:#fff!important}'
		. '.bdd-search__submit,.directorist-search-form .directorist-btn,.directorist-btn-primary,.theme-btn{background:#0668d8!important;border-color:#0668d8!important;color:#fff!important}'
		. '.bds-wa-chip{background:rgba(6,104,216,.12)!important;color:#10243a!important}'
		. '.bds-wa-chip::before{background:#0668d8!important}'
		. 'body:has(footer.bdd-footer) #bdsDirFooterLock,body:has(footer.bdd-footer) footer.bds-dir-footer-lock{' . $hide . '}'
		. '#bds-nav-fix-perks,#bds-nav-fix-wallet{margin:16px 0;padding:16px;border:1px solid #d7e3f4;border-radius:16px;background:#fff;color:#10243a}';
}

function bds_nav_fix_social_css() {
	return 'html body #site-header{display:flex!important;visibility:visible!important;align-items:center;flex-wrap:nowrap;gap:12px 24px;background:#fff!important;position:sticky;top:0;z-index:400!important;overflow:visible!important;min-height:72px;padding:8px clamp(16px,3vw,40px);box-sizing:border-box;border-bottom:1px solid #e2e8f0;box-shadow:0 8px 28px rgba(15,23,42,.06)}'
		. 'html body #site-header .custom-logo,html body #site-header .custom-logo-link img{max-width:180px!important;height:auto!important;width:auto!important}'
		. '#bds-nav-fix-bar{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-left:auto;padding:0;background:transparent;border:0;position:static;z-index:1}'
		. '#bds-nav-fix-bar .bds-nav-fix-desktop{display:flex;align-items:center;justify-content:flex-end;gap:6px 16px}'
		. '#bds-nav-fix-bar a{display:inline-flex;align-items:center;min-height:44px;padding:0 4px;color:#17324d;font-weight:750;text-decoration:none;white-space:nowrap}'
		. '#bds-nav-fix-bar a:hover,#bds-nav-fix-bar a:focus-visible{color:#0668d8}'
		. '#bds-nav-fix-bar a.bds-nav-list-free,#bds-svc-jump a.bds-nav-list-free{padding:0 14px;border-radius:10px;background:#0668d8;color:#fff}'
		. '#bds-nav-fix-bar .bds-nav-fix-mobile{display:none;position:relative}'
		. '#bds-nav-fix-bar .bds-nav-fix-mobile summary{display:flex;align-items:center;justify-content:center;min-width:48px;min-height:44px;padding:0 12px;border:1px solid #cbd5e1;border-radius:10px;color:#17324d;font-weight:800;cursor:pointer;list-style:none}'
		. '#bds-nav-fix-bar .bds-nav-fix-mobile summary::-webkit-details-marker{display:none}'
		. '#bds-nav-fix-bar .bds-nav-fix-mobile>div{position:absolute;right:0;top:calc(100% + 10px);display:grid;min-width:240px;padding:10px;border:1px solid #dbe4ef;border-radius:14px;background:#fff;box-shadow:0 20px 50px rgba(15,23,42,.16)}'
		. '#bds-nav-fix-bar .bds-nav-fix-mobile>div a{padding:0 12px;border-radius:8px}'
		. '#bds-svc-jump{display:flex;flex-wrap:wrap;gap:8px 14px;margin:0 0 18px;padding:12px 14px;border:1px solid #d7e3f4;border-radius:12px;background:#f4f8fc}'
		. '#bds-svc-jump a{display:inline-flex;align-items:center;min-height:44px;color:#0668d8;font-weight:800;text-decoration:none}'
		. '#bds-nav-fix-perks,#bds-nav-fix-wallet{margin:16px 0;padding:16px;border:1px solid #d7e3f4;border-radius:16px;background:#fff}'
		. 'body:has(#bds-nav-fix-bar) #site-header .site-navigation{display:none!important}'
		. '#bds-gift-save-copy,.bds-gcg-cta,a[href*="/gift-cards/"],a[href*="gift-card-marketplace"],a[href*="sell-gift-card"],a[href*="discount-gift-cards"]{display:none!important}'
		. 'body.home .bds-health,body.home #bds-health,body.home .bds-health-tools{display:none!important}'
		. 'html body #menu-item-1055,html body #menu-item-53631,html body #menu-item-53230,html body #menu-item-53581,html body #menu-item-51042{display:none!important}'
		. 'html body #menu-item-bds-svc,html body #menu-item-41544,html body #menu-item--91011,html body #menu-item-570{display:flex!important}'
		. '#menu-main-menu{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;max-width:100%;overflow:visible}'
		. '#menu-main-menu>li>a{white-space:nowrap}'
		. '@media(max-width:900px){html body #site-header{min-height:64px;padding:8px 14px}html body #site-header .custom-logo,html body #site-header .custom-logo-link img{max-width:150px!important}#bds-nav-fix-bar .bds-nav-fix-desktop{display:none}#bds-nav-fix-bar .bds-nav-fix-mobile{display:block}}';
}

function bds_nav_fix_print_css() {
	if ( is_admin() ) {
		return;
	}
	if ( bds_nav_fix_is_directory() ) {
		echo '<style id="bds-nav-fix">' . bds_nav_fix_directory_css() . '</style>';
	} elseif ( bds_nav_fix_is_social() ) {
		echo '<style id="bds-nav-fix">' . bds_nav_fix_social_css() . '</style>';
	}
	if ( bds_nav_fix_is_social() || bds_nav_fix_is_directory() ) {
		echo '<style id="bds-nav-fix-mark">/* nav-fix ' . BDS_NAV_FIX_VERSION . '-hard */</style>';
	}
}

function bds_nav_fix_buffer_start() {
	if ( is_admin() ) {
		return;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return;
	}
	if ( ! bds_nav_fix_is_social() && ! bds_nav_fix_is_directory() ) {
		return;
	}
	ob_start( 'bds_nav_fix_html' );
}

if ( ! bds_nav_fix_is_go() && function_exists( 'add_action' ) ) {
	add_filter( 'wp_nav_menu_objects', 'bds_nav_fix_menu_objects', 99 );
	add_filter( 'allowed_redirect_hosts', 'bds_nav_fix_allowed_redirect_hosts' );
	add_action( 'wp_head', 'bds_nav_fix_print_css', 99 );
	add_action( 'template_redirect', 'bds_nav_fix_maybe_redirect', 1 );
	add_action( 'template_redirect', 'bds_nav_fix_buffer_start', 20 );
	if ( 'cli' !== PHP_SAPI && ( bds_nav_fix_is_social() || bds_nav_fix_is_directory() ) ) {
		ob_start( 'bds_nav_fix_html' );
	}
}

/* bds-nav-fix-eof-0111-pad */
