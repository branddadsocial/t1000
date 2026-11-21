<?php
/**
 * Plugin Name: BrandDad Language Switch
 * Description: English / Español control for BrandDad Social and Directory. One language on screen. No Google Translate. Charge currency stays USD.
 * Version: 0.1.3
 * Author: BrandDad
 *
 * Deploy on branddad.social and directory.branddad.social mu-plugins.
 * Do not deploy to HostTech (English-only lock).
 *
 * Secure design:
 * - Not auth. Preference cookie only. Values allowlisted to en|es.
 * - GET bd_lang is allowlisted the same way. Unknown values stay English.
 * - Cookie is not HttpOnly so the first paint can hide the other language. SameSite=Lax. Path /. Domain .branddad.social when the host is that network.
 * - No file upload, no SQL, no outbound fetch, no payment or account change.
 * - Does not run Google Translate on checkout or anywhere else.
 *
 * @package BrandDad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_LANG_SWITCH_VER', '0.1.3' );

/**
 * Social and Directory only.
 *
 * @return bool
 */
function bds_lang_switch_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	return false !== strpos( $host, 'branddad.social' );
}

/**
 * @return array<int,string>
 */
function bds_lang_allowed() {
	return array( 'en', 'es' );
}

/**
 * Allowlisted language. Unknown input becomes English.
 *
 * @param mixed $raw Raw GET or cookie.
 * @return string
 */
function bds_lang_sanitize( $raw ) {
	$lang = preg_replace( '/[^a-z]/', '', strtolower( (string) $raw ) );
	return in_array( $lang, bds_lang_allowed(), true ) ? $lang : 'en';
}

/**
 * Current public language. Cookie or allowlisted GET. Default English.
 *
 * @return string
 */
function bds_lang_current() {
	if ( isset( $_GET['bd_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return bds_lang_sanitize( wp_unslash( $_GET['bd_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( isset( $_COOKIE['bd_lang'] ) ) {
		return bds_lang_sanitize( wp_unslash( $_COOKIE['bd_lang'] ) );
	}
	return 'en';
}

/**
 * Persist an allowlisted language.
 *
 * @param string $lang en or es.
 * @return void
 */
function bds_lang_persist( $lang ) {
	$lang = bds_lang_sanitize( $lang );
	$opts = array(
		'expires'  => time() + ( defined( 'YEAR_IN_SECONDS' ) ? YEAR_IN_SECONDS : 31536000 ),
		'path'     => '/',
		'samesite' => 'Lax',
		'secure'   => function_exists( 'is_ssl' ) ? is_ssl() : false,
		'httponly' => false,
	);
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( false !== strpos( $host, 'branddad.social' ) ) {
		$opts['domain'] = '.branddad.social';
	}
	if ( function_exists( 'setcookie' ) ) {
		setcookie( 'bd_lang', $lang, $opts );
		setcookie( 'bd_lc_manual', '1', $opts );
	}
	$_COOKIE['bd_lang']      = $lang;
	$_COOKIE['bd_lc_manual'] = '1';
}

/**
 * Attributes that swap a single node's text. Keeps one H1 in the HTML.
 *
 * @param string $en English.
 * @param string $es Spanish.
 * @return string
 */
function bds_lang_swap_attrs( $en, $es ) {
	return ' data-bds-copy-en="' . esc_attr( $en ) . '" data-bds-copy-es="' . esc_attr( $es ) . '"';
}

/**
 * Converting nav labels only. Unknown titles stay English.
 *
 * @param mixed $title Menu title.
 * @return string Spanish label or empty.
 */
function bds_lang_converting_menu_es( $title ) {
	$label = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $title ) ) );
	$map   = array(
		'Get Featured'             => 'Salir destacado',
		'Get featured'             => 'Salir destacado',
		'List my business'         => 'Listar mi negocio',
		'List my business free'    => 'Listar mi negocio gratis',
		'See plans'                => 'Ver planes',
		'See membership plans'     => 'Ver planes de membresía',
		'How to claim'             => 'Cómo reclamar',
		'Claim a listing'          => 'Reclamar un listado',
		'Try it free for 7 days'   => 'Pruébelo 7 días',
		'Start 7-day trial'        => 'Empezar prueba de 7 días',
		'Subscribe monthly'        => 'Suscribirse cada mes',
		'Search the Directory'     => 'Buscar en el directorio',
		'Get featured'             => 'Salir destacado',
	);
	return isset( $map[ $label ] ) ? $map[ $label ] : '';
}

/**
 * Put swap attributes on converting menu links, not on a parent wrapping other controls.
 *
 * @param array<string,string> $atts Link attributes.
 * @param mixed                $item Menu item.
 * @return array<string,string>
 */
function bds_lang_menu_link_attrs( $atts, $item ) {
	if ( ! bds_lang_switch_host() || is_admin() || ! is_array( $atts ) ) {
		return $atts;
	}
	$title = ( is_object( $item ) && isset( $item->title ) ) ? $item->title : '';
	$es    = bds_lang_converting_menu_es( $title );
	if ( '' === $es ) {
		return $atts;
	}
	$atts['data-bds-copy-en'] = trim( wp_strip_all_tags( (string) $title ) );
	$atts['data-bds-copy-es'] = $es;
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'bds_lang_menu_link_attrs', 20, 2 );

/**
 * Two nodes. CSS/JS shows one language. Default visible node is English.
 *
 * @param string $en    English.
 * @param string $es    Spanish.
 * @param string $tag   Tag name.
 * @param string $class Class attribute.
 * @return string
 */
function bds_lang_pair( $en, $es, $tag = 'p', $class = '' ) {
	$tag   = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $tag ) );
	$tag   = $tag ? $tag : 'p';
	$class = is_string( $class ) ? $class : '';
	$attr  = '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '';
	return '<' . $tag . $attr . ' data-bds-copy="en" lang="en">' . esc_html( $en ) . '</' . $tag . '>'
		. '<' . $tag . $attr . ' data-bds-copy="es" lang="es">' . esc_html( $es ) . '</' . $tag . '>';
}

/**
 * @return void
 */
function bds_lang_capture_get() {
	if ( ! bds_lang_switch_host() || is_admin() ) {
		return;
	}
	if ( ! isset( $_GET['bd_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	bds_lang_persist( wp_unslash( $_GET['bd_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( function_exists( 'wp_safe_redirect' ) && function_exists( 'remove_query_arg' ) ) {
		wp_safe_redirect( remove_query_arg( 'bd_lang' ), 302 );
		exit;
	}
}
add_action( 'template_redirect', 'bds_lang_capture_get', 0 );

/**
 * @return void
 */
function bds_lang_head() {
	if ( ! bds_lang_switch_host() || is_admin() ) {
		return;
	}
	$ver = esc_attr( BDS_LANG_SWITCH_VER );
	echo '<style id="bds-lang-switch-css" data-bds-lang-switch="' . $ver . '">'
		. 'html:not([data-bds-lang="es"]) [data-bds-copy="es"]{display:none!important}'
		. 'html[data-bds-lang="es"] [data-bds-copy="en"]{display:none!important}'
		. '#bds-lang-switch{position:fixed;top:12px;right:12px;z-index:1200;display:flex;gap:0;border:1px solid rgba(15,23,42,.16);border-radius:999px;overflow:hidden;background:rgba(255,255,255,.94);box-shadow:0 8px 24px rgba(15,23,42,.12);font:700 12px/1 Segoe UI,system-ui,sans-serif}'
		. '#bds-lang-switch button{border:0;background:transparent;color:#0f172a;padding:8px 11px;cursor:pointer;min-height:36px}'
		. '#bds-lang-switch button[aria-pressed="true"]{background:#0372ff;color:#fff}'
		. 'body.admin-bar #bds-lang-switch{top:46px}'
		. '@media (max-width:782px){body.admin-bar #bds-lang-switch{top:56px}}'
		. '</style>' . "\n";
	echo '<script id="bds-lang-switch-boot" data-bds-lang-switch="' . $ver . '">(function(){try{var q=(location.search.match(/[?&]bd_lang=([^&]*)/)||[])[1];var m=document.cookie.match(/(?:^|; )bd_lang=([^;]*)/);var lang=q?decodeURIComponent(q.replace(/\\+/g," ")):(m?decodeURIComponent(m[1]):"en");lang=String(lang).toLowerCase().replace(/[^a-z]/g,"");if(lang!=="es")lang="en";document.documentElement.setAttribute("data-bds-lang",lang);if(lang==="es")document.documentElement.setAttribute("lang","es");}catch(e){}})();</script>' . "\n";
}
add_action( 'wp_head', 'bds_lang_head', 0 );

/**
 * @return void
 */
function bds_lang_switch_markup() {
	if ( ! bds_lang_switch_host() || is_admin() ) {
		return;
	}
	echo '<div id="bds-lang-switch" data-bds-lang-switch="' . esc_attr( BDS_LANG_SWITCH_VER ) . '" role="group" aria-label="Language">';
	echo '<button type="button" data-bds-lang-pick="en" aria-pressed="true">EN</button>';
	echo '<button type="button" data-bds-lang-pick="es" aria-pressed="false">ES</button>';
	echo '</div>';
	echo '<script id="bds-lang-switch-js">(function(){function cookieSet(n,v){try{document.cookie=n+"="+encodeURIComponent(v)+";path=/;max-age=31536000;SameSite=Lax;domain=.branddad.social";}catch(e){try{document.cookie=n+"="+encodeURIComponent(v)+";path=/;max-age=31536000;SameSite=Lax";}catch(err){}}}function apply(lang){if(lang!=="es")lang="en";document.documentElement.setAttribute("data-bds-lang",lang);document.documentElement.setAttribute("lang",lang==="es"?"es":"en");cookieSet("bd_lang",lang);cookieSet("bd_lc_manual","1");document.querySelectorAll("[data-bds-copy-en]").forEach(function(el){var t=el.getAttribute(lang==="es"?"data-bds-copy-es":"data-bds-copy-en");if(!t)return;if(el.tagName==="INPUT"||el.tagName==="TEXTAREA")el.setAttribute("placeholder",t);else el.textContent=t;});document.querySelectorAll("[data-bds-copy]").forEach(function(el){el.hidden=el.getAttribute("data-bds-copy")!==lang;});document.querySelectorAll("[data-bds-lang-pick]").forEach(function(btn){btn.setAttribute("aria-pressed",btn.getAttribute("data-bds-lang-pick")===lang?"true":"false");});}var root=document.getElementById("bds-lang-switch");if(!root)return;root.addEventListener("click",function(e){var btn=e.target.closest("[data-bds-lang-pick]");if(!btn)return;apply(btn.getAttribute("data-bds-lang-pick"));});apply(document.documentElement.getAttribute("data-bds-lang")||"en");})();</script>';
}
add_action( 'wp_footer', 'bds_lang_switch_markup', 5 );
