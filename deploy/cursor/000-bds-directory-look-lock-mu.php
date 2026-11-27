<?php
/**
 * Plugin Name: BrandDad Directory Look Lock
 * Description: Invalidates stale Directory HTML-cache keys before the early reader can serve a broken homepage shell.
 * Version: 0.1.46
 * Author: BrandDad Social
 *
 * Secure design:
 * - Identity: none. Runs on public Directory requests only.
 * - AuthZ: HTTP_HOST allowlist. No user input selects a path.
 * - Storage: public generated HTML under WP_CONTENT_DIR/cache only. Rename to a sibling backup dir, no unlink, no wildcards, no request-provided names.
 * - Output: Directory HTML. Rewrites cached compose crit CSS in <head> so first paint is the 7am white/blue Experience, not leftover WhatsApp green. Does not hide theme wrappers. Morning HTML-cache keys may be renamed back from sibling backups.
 * - Ingestion: buffer rewrites fixed allowlisted selectors on host-generated HTML only. Size cap. No request bytes in the regex.
 * - Secrets: none.
 * - Must load as 000-* so it runs before aaa-bd-eco-early-html.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_DIR_LOOK_LOCK_VERSION', '0.1.46' );
define( 'BDS_DIR_LOOK_LOCK_GEN', 'prod1124menu1' );
define( 'BDS_DIR_LOOK_LOCK_BACKUP', '.bds-look-lock-20261123-v10' );

function bds_dir_look_lock_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/[\r\n\x00\t].*/', '', $host );
	$host = preg_replace( '/:\d+$/', '', (string) $host );
	return is_string( $host ) ? $host : '';
}

function bds_dir_look_lock_is_directory() {
	return in_array( bds_dir_look_lock_host(), array( 'directory.branddad.social', 'www.directory.branddad.social' ), true );
}

function bds_dir_look_lock_morning_gens() {
	return array(
		'prod1119convh1',
		'prod0823lh4',
		'prod0823lh3',
		'prod0823lh1',
		'prod0821lcp10',
		'prod0821lcp10b',
		'prod1112',
	);
}

function bds_dir_look_lock_broken_gens() {
	return array(
		'prod1123look8',
		'prod1123look7',
		'prod1123look6',
		'prod1123look5',
		'prod1123look4',
		'prod1123look3',
		'prod1123look2',
		'prod1123look1',
	);
}

function bds_dir_look_lock_gens() {
	return array_values( array_unique( array_merge( array( BDS_DIR_LOOK_LOCK_GEN ), bds_dir_look_lock_morning_gens(), bds_dir_look_lock_broken_gens() ) ) );
}

function bds_dir_look_lock_backup_dirs() {
	return array(
		'.bds-look-lock-20261123-v8',
		'.bds-look-lock-20261123-v7',
		'.bds-look-lock-20261123-v6',
		'.bds-look-lock-20261123-v5',
		'.bds-look-lock-20261123-v4',
		'.bds-look-lock-20261123-v3',
		'.bds-look-lock-20261123-v2',
		'.bds-look-lock-20261123-v1',
	);
}

function bds_dir_look_lock_names_for( $gens ) {
	$names = array();
	if ( ! is_array( $gens ) ) {
		return $names;
	}
	foreach ( array( 'directory.branddad.social', 'www.directory.branddad.social' ) as $host ) {
		foreach ( $gens as $gen ) {
			if ( ! is_string( $gen ) || $gen === '' ) {
				continue;
			}
			$names[] = hash( 'sha256', $host . '|/|' . $gen ) . '.html';
		}
	}
	return array_values( array_unique( $names ) );
}

function bds_dir_look_lock_home_names() {
	return bds_dir_look_lock_names_for( bds_dir_look_lock_gens() );
}

function bds_dir_look_lock_hash_name( $name ) {
	return 1 === preg_match( '/^[a-f0-9]{64}\.html$/', (string) $name );
}

function bds_dir_look_lock_name_ok( $name ) {
	return bds_dir_look_lock_hash_name( $name )
		&& in_array( (string) $name, bds_dir_look_lock_home_names(), true );
}

function bds_dir_look_lock_norm( $path ) {
	return rtrim( str_replace( '\\', '/', (string) $path ), '/' );
}

function bds_dir_look_lock_cache_dir() {
	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		return '';
	}
	$dir = bds_dir_look_lock_norm( WP_CONTENT_DIR ) . '/cache/bd-eco-html';
	if ( false !== strpos( $dir, '..' ) || ! is_dir( $dir ) ) {
		return '';
	}
	$real = realpath( $dir );
	if ( false !== $real ) {
		$cache        = bds_dir_look_lock_norm( $real );
		$content_real = realpath( WP_CONTENT_DIR );
		if ( false !== $content_real ) {
			$prefix = bds_dir_look_lock_norm( $content_real ) . '/cache';
			if ( 0 === strpos( $cache, $prefix ) ) {
				return $cache;
			}
		}
	}
	if ( 1 === preg_match( '#/cache/bd-eco-html$#', $dir ) ) {
		return $dir;
	}
	return '';
}

/**
 * Put morning homepage HTML back if today's lock moved it into a sibling backup.
 *
 * @return void
 */
function bds_dir_look_lock_restore() {
	if ( ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	$cache = bds_dir_look_lock_cache_dir();
	if ( '' === $cache ) {
		return;
	}
	$morning = bds_dir_look_lock_names_for( bds_dir_look_lock_morning_gens() );
	foreach ( bds_dir_look_lock_backup_dirs() as $dir_name ) {
		if ( ! preg_match( '/^\.bds-look-lock-20261123-v[1-8]$/', $dir_name ) ) {
			continue;
		}
		$backup = $cache . '/' . $dir_name;
		$backup_real = realpath( $backup );
		if ( false === $backup_real || ! is_dir( $backup_real ) ) {
			continue;
		}
		if ( 0 !== strpos( bds_dir_look_lock_norm( $backup_real ), $cache . '/' ) ) {
			continue;
		}
		foreach ( $morning as $name ) {
			if ( ! bds_dir_look_lock_name_ok( $name ) ) {
				continue;
			}
			$source = bds_dir_look_lock_norm( $backup_real ) . '/' . $name . '.bak';
			$dest   = $cache . '/' . $name;
			if ( is_link( $source ) || is_link( $dest ) || ! is_file( $source ) ) {
				continue;
			}
			if ( is_file( $dest ) ) {
				if ( ! bds_dir_look_lock_html_is_green( $dest ) ) {
					continue;
				}
				bds_dir_look_lock_move_aside( $dest );
				if ( is_file( $dest ) ) {
					continue;
				}
			}
			@rename( $source, $dest );
		}
	}
}

/**
 * Retire today's broken homepage cache objects only.
 *
 * @return void
 */
function bds_dir_look_lock_retire() {
	if ( ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	$cache = bds_dir_look_lock_cache_dir();
	if ( '' === $cache ) {
		return;
	}
	$backup = $cache . '/' . BDS_DIR_LOOK_LOCK_BACKUP;
	if ( ! is_dir( $backup ) && ! @mkdir( $backup, 0755, true ) ) {
		return;
	}
	$backup_real = realpath( $backup );
	if ( false === $backup_real || 0 !== strpos( bds_dir_look_lock_norm( $backup_real ), $cache . '/' ) ) {
		return;
	}
	foreach ( bds_dir_look_lock_names_for( bds_dir_look_lock_broken_gens() ) as $name ) {
		if ( ! bds_dir_look_lock_name_ok( $name ) ) {
			continue;
		}
		$source = $cache . '/' . $name;
		$dest   = bds_dir_look_lock_norm( $backup_real ) . '/' . $name . '.bak';
		if ( is_link( $source ) || is_link( $dest ) || ! is_file( $source ) || is_file( $dest ) ) {
			continue;
		}
		@rename( $source, $dest );
	}
}

function bds_dir_look_lock_html_is_green( $path ) {
	if ( ! is_string( $path ) || ! is_file( $path ) || is_link( $path ) ) {
		return false;
	}
	$fh = fopen( $path, 'rb' );
	if ( false === $fh ) {
		return false;
	}
	$buf = fread( $fh, 131072 );
	fclose( $fh );
	if ( ! is_string( $buf ) || '' === $buf ) {
		return false;
	}
	if ( false !== strpos( $buf, 'Find What You Need Near You' ) ) {
		return false;
	}
	return ( false !== strpos( $buf, 'WhatsApp Business Directory' ) )
		|| ( false !== strpos( $buf, 'Find a business. See what it offers. Contact it.' ) );
}

function bds_dir_look_lock_move_aside( $source ) {
	$cache = bds_dir_look_lock_cache_dir();
	if ( '' === $cache || ! is_string( $source ) || is_link( $source ) || ! is_file( $source ) ) {
		return;
	}
	$name = basename( $source );
	if ( ! bds_dir_look_lock_hash_name( $name ) ) {
		return;
	}
	if ( bds_dir_look_lock_norm( $source ) !== $cache . '/' . $name ) {
		return;
	}
	$backup = $cache . '/' . BDS_DIR_LOOK_LOCK_BACKUP;
	if ( ! is_dir( $backup ) && ! @mkdir( $backup, 0755, true ) ) {
		return;
	}
	$backup_real = realpath( $backup );
	if ( false === $backup_real || 0 !== strpos( bds_dir_look_lock_norm( $backup_real ), $cache . '/' ) ) {
		return;
	}
	$dest = bds_dir_look_lock_norm( $backup_real ) . '/' . $name . '.bak';
	if ( is_link( $dest ) || is_file( $dest ) ) {
		return;
	}
	@rename( $source, $dest );
}

function bds_dir_look_lock_retire_green() {
	if ( ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	$cache = bds_dir_look_lock_cache_dir();
	if ( '' === $cache ) {
		return;
	}
	$found = glob( $cache . '/*.html' );
	if ( ! is_array( $found ) ) {
		return;
	}
	foreach ( $found as $source ) {
		$name = basename( (string) $source );
		if ( ! bds_dir_look_lock_hash_name( $name ) ) {
			continue;
		}
		$source = $cache . '/' . $name;
		if ( ! bds_dir_look_lock_html_is_green( $source ) ) {
			continue;
		}
		bds_dir_look_lock_move_aside( $source );
	}
}

function bds_dir_look_lock_request_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$uri = preg_replace( '/[\r\n\x00\t].*/', '', $uri );
	$path = parse_url( $uri, PHP_URL_PATH );
	return is_string( $path ) ? strtolower( trim( $path, '/' ) ) : '';
}

function bds_dir_look_lock_is_home() {
	return bds_dir_look_lock_is_directory() && '' === bds_dir_look_lock_request_path();
}

function bds_dir_look_lock_keep_live_pages() {
	if ( ! bds_dir_look_lock_is_directory() || ! function_exists( 'remove_action' ) ) {
		return;
	}
	if ( 'all-locations' !== bds_dir_look_lock_request_path() ) {
		return;
	}
	remove_action( 'template_redirect', 'bds_feat_leftover_alias', 8 );
}

function bds_dir_look_lock_qs( $name ) {
	$name = (string) $name;
	if ( ! in_array( $name, array( 'directory_type', 'bds_gshape' ), true ) ) {
		return '';
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$uri  = preg_replace( '/[\r\n\x00\t].*/', '', $uri );
	$query = parse_url( $uri, PHP_URL_QUERY );
	if ( ! is_string( $query ) || '' === $query ) {
		return '';
	}
	$args = array();
	if ( function_exists( 'wp_parse_str' ) ) {
		wp_parse_str( $query, $args );
	} else {
		parse_str( $query, $args );
	}
	if ( ! isset( $args[ $name ] ) || ! is_scalar( $args[ $name ] ) ) {
		return '';
	}
	$value = substr( (string) $args[ $name ], 0, 80 );
	return function_exists( 'sanitize_key' ) ? sanitize_key( $value ) : strtolower( preg_replace( '/[^a-z0-9_-]/', '', $value ) );
}

function bds_dir_look_lock_print_arch() {
	if ( is_admin() || ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	$arch = '';
	if ( 'search-result' === bds_dir_look_lock_request_path() ) {
		$arch = 'search';
	}
	$type  = strtolower( preg_replace( '/[^a-z0-9\-]/', '', bds_dir_look_lock_qs( 'directory_type' ) ) );
	$shape = strtolower( preg_replace( '/[^a-z]/', '', bds_dir_look_lock_qs( 'bds_gshape' ) ) );
	if ( 'whatsapp-groups' === $type ) {
		$arch = 'channel' === $shape ? 'channel' : 'group';
	}
	if ( '' === $arch ) {
		return;
	}
	echo '<script id="bds-look-arch">document.documentElement.setAttribute("data-bds-arch","' . esc_attr( $arch ) . '");</script>';
}

function bds_dir_look_lock_css() {
	return 'html,body.home{background:#fff!important;background-image:none!important;color:#10243a!important}'
		. 'body.home .bdd-hero,body.home .bdd-hero .bdd-hero__copy,body.home #bdd-title{display:block!important;visibility:visible!important;opacity:1!important;height:auto!important;max-height:none!important;overflow:visible!important;clip:auto!important;clip-path:none!important;position:static!important;left:auto!important;top:auto!important;width:auto!important;transform:none!important}'
		. 'body.home .bdd-header{background:#fff!important;visibility:visible!important}'
		. 'body.home #bds-dfc-gift,body.home #bds-dfc-gift-place-js,body.home #bds-wa-hero,body.home .bds-wah,body.home .bds-wah__kicker,body.home .bds-wah__title,body.home .bds-wah__lead,body.home .bds-wah__actions,body.home .bds-wah__inner,body.home .bds-wah__search,body.home #bds-wav-hero,body.home .bds-wav,body.home #bds-dir-types,body.home .bds-dtd,body.home #bds-home-browse,body.home .bds-hbrowse,body.home #bds-home-cta-mid,body.home #bds-home-cta-bottom,body.home .bds-hcta,body.home .bds-gcg-cta,body.home #bds-ai-local-pack,body.home #bdsNetworkConnectStrip,body.home #bdsDirFooterLock,body.home footer.bds-dir-footer-lock,body.home #bds-follow-consent{display:none!important;background:#fff!important;min-height:0!important;height:0!important;max-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
		. 'body.home .bdd-header .bdd-nav{flex-wrap:nowrap;justify-content:flex-start;gap:0}'
		. 'body.home .bdd-header .bdd-nav>a,body.home .bdd-header .bdd-nav summary{padding:0 4px;white-space:nowrap}'
		. '#bdsNetworkConnectStrip,#bdsFbGroupBar,#bds-follow-consent{display:none!important;min-height:0!important;height:0!important;max-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
		. '#bds-wa-hero,.bds-wah,#bds-wav-hero,.bds-wav,#bds-dir-types,.bds-dtd,#bds-home-browse,.bds-hbrowse,#bds-home-cta-mid,#bds-home-cta-bottom,.bds-hcta,.bds-gcg-cta{display:none!important;background:#fff!important;min-height:0!important;height:0!important;max-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
		. 'html{--color-primary:#0668d8;--primary-color:#0668d8;--theme-color:#0668d8;--directorist-color-primary:#0668d8;--e-global-color-primary:#0668d8}'
		. '.bdd-search__submit,.directorist-search-form .directorist-btn,.directorist-search-contents .directorist-btn,.directorist-listing-single .directorist-btn,.directorist-btn-primary,.directorist-btn-dark,.directorist-btn-success,.theme-btn,.bds-wah__btn,.bds-hcta__btn,.bds-dtd__btn{background:#0668d8!important;border-color:#0668d8!important;color:#fff!important}'
		. '.bds-facet__chip{background:#fff!important;border-color:#0668d8!important;color:#10243a!important}'
		. '.bds-facet__chip.is-on,.bds-facet__chip.is-active,.bds-facet__chip[aria-pressed="true"]{background:#0668d8!important;border-color:#0668d8!important;color:#fff!important}'
		. '.bds-facet a,.bds-facet__clear,.bds-facet__empty,.bds-facet__empty a,.bds-facet__chip a,.directorist-archive-contents a,.directorist-taxonomy-list a{color:#0668d8!important}'
		. 'body:has(footer.bdd-footer) #bdsDirFooterLock,body:has(footer.bdd-footer) footer.bds-dir-footer-lock{display:none!important;min-height:0!important;height:0!important;max-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
		. 'body:has(footer.bdd-footer) #bds-feat-door-ctas,body:has(#bdsDirFooterLock) #bds-feat-door-ctas,.bds-hbrowse__foot{display:none!important;min-height:0!important;height:0!important;max-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
		. '.bdd-header .bdd-nav{flex-wrap:nowrap;justify-content:flex-start;gap:0}'
		. '.bdd-header .bdd-nav>a,.bdd-header .bdd-nav summary{padding:0 4px;white-space:nowrap}'
		. 'body:has(.bdd-header) #site-header,body:has(.bdd-header) .menu-area{display:none!important;height:0!important;overflow:hidden!important}'
		. 'body:has(header.bdd-header:not(#bds-look-header)) #bds-look-header{display:none!important;height:0!important;max-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
		. '.directorist-search-form .directorist-btn,.directorist-search-form button[type="submit"],.directorist-search-form input[type="submit"]{background:#0668d8!important;border-color:#0668d8!important;color:#fff!important}'
		. '.bdd-search .bdd-field--gkind,.bdd-search .bdd-field--gplace{display:none}'
		. '.bdd-search.is-bds-groups .bdd-field--gkind,.bdd-search.is-bds-groups .bdd-field--gplace{display:flex!important;flex-direction:column;gap:6px;min-height:44px;color:#10243a;font-weight:600}'
		. 'body.home footer.bdd-footer,body.home #bds-featured-lanes{display:block!important;visibility:visible!important;height:auto!important;max-height:none!important;overflow:visible!important;min-height:0}'
		. 'body.home .bdd-search .bdd-field--wa{display:flex!important;flex-direction:row!important;align-items:center;gap:8px;min-height:44px;grid-column:1/-1;flex:1 1 100%;width:100%;color:#10243a;font-weight:600}'
		. 'body.home .bdd-search .bdd-field--wa input[type="checkbox"]{width:18px!important;height:18px!important;max-width:18px!important;min-width:18px!important;flex:0 0 18px!important;margin:0;accent-color:#0668d8}'
		. 'body.home .bdd-search .bdd-field--gkind,body.home .bdd-search .bdd-field--gplace{display:none}'
		. 'body.home .bdd-search.is-bds-groups .bdd-field--gkind,body.home .bdd-search.is-bds-groups .bdd-field--gplace{display:flex!important;flex-direction:column;gap:6px;min-height:44px;color:#10243a;font-weight:600}'
		. 'body.home .bdd-search.is-bds-groups .bdd-field--wa{display:none!important}'
		. 'body.home [data-bdd-nearby] .bdd-discovery-grid:has(> .bdd-section-panel),body.home [data-bdd-nearby-events] .bdd-discovery-grid:has(> .bdd-section-panel),body.home .is-bds-nearby-empty .bdd-discovery-grid{display:block!important;grid-template-columns:1fr!important;width:100%}'
		. 'body.home [data-bdd-nearby] .bdd-discovery-grid > .bdd-section-panel,body.home [data-bdd-nearby-events] .bdd-discovery-grid > .bdd-section-panel{display:flex!important;flex-direction:row;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px 24px;width:100%!important;max-width:none!important;grid-column:1/-1;box-sizing:border-box}'
		. 'body.home [data-bdd-nearby] .bdd-discovery-grid > .bdd-section-panel p,body.home [data-bdd-nearby-events] .bdd-discovery-grid > .bdd-section-panel p{flex:1 1 16rem;margin:0;max-width:none!important}'
		. '@media(max-width:900px){body.home .bdd-search{display:flex!important;flex-direction:column!important;gap:12px}body.home .bdd-search .bdd-field,body.home .bdd-search__submit,body.home .bdd-hero__secondary .bdd-button,body.home .bdd-owner-card__actions .bdd-button{width:100%;min-height:48px;box-sizing:border-box}body.home .bdd-owner-card__actions,body.home .bdd-hero__secondary{display:flex;flex-direction:column;gap:8px}body.home [data-bdd-nearby] .bdd-discovery-grid > .bdd-section-panel,body.home [data-bdd-nearby-events] .bdd-discovery-grid > .bdd-section-panel{flex-direction:column;align-items:flex-start}}';
}

function bds_dir_look_lock_early_markup() {
	return '<style id="bds-dir-look-lock-early">' . bds_dir_look_lock_css() . '</style>';
}

function bds_dir_look_lock_markup() {
	$css = '<style id="bds-dir-look-lock">' . bds_dir_look_lock_css() . '</style>';
	$js  = '<script id="bds-dir-look-lock-js" src="/wp-content/mu-plugins/000-bds-directory-look-lock.js?ver=' . rawurlencode( BDS_DIR_LOOK_LOCK_VERSION ) . '"></script>';
	return $css . $js;
}

function bds_dir_look_lock_unpark_experience( $html ) {
	$rewritten = preg_replace_callback(
		'/<([a-z0-9]+)([^>]*\bbdd-hero\b[^>]*)>/i',
		static function ( $m ) {
			return preg_replace( '/display\s*:\s*none\s*!important/i', 'display:block!important', $m[0] );
		},
		$html
	);
	return is_string( $rewritten ) ? $rewritten : $html;
}

function bds_dir_look_lock_kill_green_crit( $html ) {
	$crit = '<style id="bds-home-crit">#bds-wa-hero,.bds-wah{display:none!important;min-height:0!important;height:0!important;overflow:hidden!important;background:#fff!important}body.home .bdd-hero,body.home #bdd-title{display:block!important;visibility:visible!important}</style>';
	$out  = preg_replace( '/<style id="bds-home-crit">.*?<\/style>/is', $crit, $html, 1 );
	if ( is_string( $out ) ) {
		$html = $out;
	}
	$html = str_replace(
		'body.home #bds-wa-hero,body.home .bds-wah{min-height:min(92vh,1100px);box-sizing:border-box}',
		'body.home #bds-wa-hero,body.home .bds-wah{display:none!important;min-height:0!important;height:0!important;overflow:hidden!important;background:#fff!important}',
		$html
	);
	return $html;
}

function bds_dir_look_lock_hide_wa_section( $html ) {
	if ( ! preg_match( '/<section\b[^>]*\bid=["\']bds-wa-hero["\'][^>]*>/i', $html, $m ) ) {
		return $html;
	}
	$tag = $m[0];
	if ( false !== stripos( $tag, 'hidden' ) ) {
		return $html;
	}
	$hidden = preg_replace( '/^<section/i', '<section hidden aria-hidden="true"', $tag, 1 );
	if ( ! is_string( $hidden ) || $hidden === $tag ) {
		return $html;
	}
	return str_replace( $tag, $hidden, $html );
}

function bds_dir_look_lock_nav_href_plain( $href ) {
	return str_replace( '&amp;', '&', (string) $href );
}

function bds_dir_look_lock_nav_href_ok( $href ) {
	$href = bds_dir_look_lock_nav_href_plain( $href );
	if ( '' === $href || strlen( $href ) > 400 || false !== strpos( $href, '..' ) ) {
		return false;
	}
	if ( 1 === preg_match( '/[\s<>"\'\\\\]/', $href ) ) {
		return false;
	}
	if ( 0 === strpos( $href, '/' ) && 0 !== strpos( $href, '//' ) ) {
		return 1 === preg_match( '#^/[A-Za-z0-9/._~-]*(\?[A-Za-z0-9&=._~%-]*)?$#', $href );
	}
	return 1 === preg_match( '#^https://directory\.branddad\.social/[A-Za-z0-9/._~-]*(\?[A-Za-z0-9&=._~%-]*)?$#', $href );
}

function bds_dir_look_lock_nav_href_html( $href ) {
	return htmlspecialchars( bds_dir_look_lock_nav_href_plain( $href ), ENT_QUOTES, 'UTF-8' );
}

function bds_dir_look_lock_nav_door_link( $href, $en, $es ) {
	return '<a href="' . bds_dir_look_lock_nav_href_html( $href ) . '"' . bds_dir_look_lock_copy_attr( $en, $es ) . '>' . $en . '</a>';
}

function bds_dir_look_lock_rewrite_leftover_copy( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	$html = str_replace(
		'This does not include a business listing.',
		'A basic listing is free. Featured is a separate monthly option.',
		$html
	);
	$ch = false !== strpos( $html, 'data-bds-arch="channel"' ) || false !== strpos( $html, 'data-bds-arch","channel"' );
	$gr = false !== strpos( $html, 'data-bds-arch="group"' ) || false !== strpos( $html, 'data-bds-arch","group"' )
		|| false !== strpos( $html, 'Choose a city first. Counts show real published businesses you can open next in Whatsapp Groups.' );
	if ( ! $ch && ! $gr ) {
		return $html;
	}
	$title = $ch ? 'Broadcast channels' : 'WhatsApp groups';
	$html  = str_replace( 'All Listings | BrandDad Social Directory', $title . ' | BrandDad Directory', $html );
	$html  = str_replace( '>All Listings', '>' . $title, $html );
	$html  = str_replace( '>No listings found.', $ch ? '>No broadcast channels found.' : '>No WhatsApp groups found.', $html );
	$html  = str_replace(
		'Choose a city first. Counts show real published businesses you can open next in Whatsapp Groups.',
		$ch ? 'No broadcast channels listed here yet. List yours free.' : 'No WhatsApp groups listed here yet. List yours free.',
		$html
	);
	$html  = str_replace(
		'No businesses match these filters yet.',
		$ch ? 'No channels match these filters yet.' : 'No groups match these filters yet.',
		$html
	);
	return $html;
}

function bds_dir_look_lock_inject_nav_doors( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	$out = preg_replace_callback(
		'/<a\s+href="([^"]*directory_type=classifieds)"([^>]*)>(Classifieds|Clasificados|Post a Classified|Publicar un clasificado)<\/a>(?!\s*<a[^>]+directory_type=whatsapp-groups)/i',
		static function ( $m ) {
			$href = bds_dir_look_lock_nav_href_plain( $m[1] );
			if ( ! bds_dir_look_lock_nav_href_ok( $href ) ) {
				return $m[0];
			}
			$groups = str_replace( 'directory_type=classifieds', 'directory_type=whatsapp-groups', $href );
			if ( ! bds_dir_look_lock_nav_href_ok( $groups ) ) {
				return $m[0];
			}
			$sep      = false !== strpos( $groups, '?' ) ? '&' : '?';
			$channels = $groups . $sep . 'bds_gshape=channel';
			if ( ! bds_dir_look_lock_nav_href_ok( $channels ) ) {
				return $m[0];
			}
			$is_add = false !== strpos( $href, 'add-listing' );
			if ( $is_add ) {
				return $m[0]
					. bds_dir_look_lock_nav_door_link( $groups, 'List a WhatsApp group', 'Listar un grupo de WhatsApp' )
					. bds_dir_look_lock_nav_door_link( $channels, 'List a broadcast channel', 'Listar un canal de difusión' );
			}
			return $m[0]
				. bds_dir_look_lock_nav_door_link( $groups, 'WhatsApp groups', 'Grupos de WhatsApp' )
				. bds_dir_look_lock_nav_door_link( $channels, 'Broadcast channels', 'Canales de difusión' );
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_dir_look_lock_buffer( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	if ( strlen( $html ) > 8000000 ) {
		return $html;
	}
	$html = bds_dir_look_lock_unpark_experience( $html );
	$html = bds_dir_look_lock_kill_green_crit( $html );
	$html = bds_dir_look_lock_hide_wa_section( $html );
	$html = bds_dir_look_lock_inject_nav_doors( $html );
	$html = bds_dir_look_lock_rewrite_leftover_copy( $html );
	$early = bds_dir_look_lock_early_markup();
	if ( false !== strpos( $html, 'id="bds-dir-look-lock-early"' ) ) {
		$replaced = preg_replace( '/<style id="bds-dir-look-lock-early">.*?<\/style>/is', $early, $html, 1 );
		$html     = is_string( $replaced ) ? $replaced : $html;
	} elseif ( 1 === preg_match( '/<head[^>]*>/i', $html ) ) {
		$out  = preg_replace( '/<head[^>]*>/i', '$0' . $early, $html, 1 );
		$html = is_string( $out ) ? $out : $early . $html;
	} else {
		$html = $early . $html;
	}
	$ver = rawurlencode( BDS_DIR_LOOK_LOCK_VERSION );
	$js  = preg_replace( '/000-bds-directory-look-lock\.js\?ver=[^"\']+/', '000-bds-directory-look-lock.js?ver=' . $ver, $html, 1 );
	if ( is_string( $js ) ) {
		$html = $js;
	}
	if ( false !== strpos( $html, 'id="bds-dir-look-lock-js"' ) ) {
		return $html;
	}
	$mark = bds_dir_look_lock_markup();
	if ( false !== stripos( $html, '</head>' ) ) {
		$out = preg_replace( '/<\/head>/i', $mark . '</head>', $html, 1 );
		return is_string( $out ) ? $out : $html . $mark;
	}
	if ( false !== stripos( $html, '</body>' ) ) {
		$out = preg_replace( '/<\/body>/i', $mark . '</body>', $html, 1 );
		return is_string( $out ) ? $out : $html . $mark;
	}
	return $html . $mark;
}

function bds_dir_look_lock_copy_attr( $en, $es ) {
	$esc = function_exists( 'esc_attr' ) ? 'esc_attr' : static function ( $v ) {
		return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
	};
	return ' data-bds-copy-en="' . $esc( $en ) . '" data-bds-copy-es="' . $esc( $es ) . '"';
}

function bds_dir_look_lock_header_html() {
	if ( ! function_exists( 'home_url' ) || ! function_exists( 'esc_url' ) || ! function_exists( 'esc_attr' ) ) {
		return '';
	}
	$home     = esc_url( home_url( '/' ) );
	$list     = esc_url( home_url( '/all-listings/' ) );
	$cats     = esc_url( home_url( '/all-categories/' ) );
	$locs     = esc_url( home_url( '/all-locations/' ) );
	$events   = esc_url( home_url( '/all-listings/?directory_type=events' ) );
	$jobs     = esc_url( home_url( '/all-listings/?directory_type=jobs-careers' ) );
	$class    = esc_url( home_url( '/all-listings/?directory_type=classifieds' ) );
	$groups   = esc_url( home_url( '/all-listings/?directory_type=whatsapp-groups' ) );
	$channels = esc_url( home_url( '/all-listings/?directory_type=whatsapp-groups&bds_gshape=channel' ) );
	$add      = esc_url( home_url( '/add-listing/' ) );
	$addev    = esc_url( home_url( '/add-listing/?directory_type=events' ) );
	$addjob   = esc_url( home_url( '/add-listing/?directory_type=jobs-careers' ) );
	$addcl    = esc_url( home_url( '/add-listing/?directory_type=classifieds' ) );
	$addg     = esc_url( home_url( '/add-listing/?directory_type=whatsapp-groups' ) );
	$addch    = esc_url( home_url( '/add-listing/?directory_type=whatsapp-groups&bds_gshape=channel' ) );
	$claim    = esc_url( home_url( '/claim-your-business-listing-branddad-directory/' ) );
	$how      = esc_url( home_url( '/' ) ) . '#bdd-how-title';
	$plans    = esc_url( home_url( '/select-your-plan/' ) ) . '#bdd-current-plans';
	$dash     = esc_url( home_url( '/dashboard/' ) );
	$help     = esc_url( home_url( '/contact-us/' ) );
	$signin   = esc_url( home_url( '/sign-in/' ) );
	$search   = $home . '#bdd-search';
	$c        = 'bds_dir_look_lock_copy_attr';
	return '<header id="bds-look-header" class="bdd-header" aria-label="BrandDad Directory">'
		. '<div class="bdd-shell bdd-header__inner">'
		. '<a class="bdd-brand" href="' . $home . '" aria-label="BrandDad Directory home"><span class="bdd-brand__mark" aria-hidden="true">BD</span><span class="bdd-brand__name">BrandDad <strong>Directory</strong></span></a>'
		. '<nav class="bdd-nav" aria-label="Primary navigation">'
		. '<details class="bdd-nav-menu"><summary' . $c( 'Businesses', 'Negocios' ) . '>Businesses</summary><div class="bdd-nav-menu__panel">'
		. '<a href="' . $list . '"' . $c( 'Find a Business', 'Encontrar un negocio' ) . '>Find a Business</a>'
		. '<a href="' . $cats . '"' . $c( 'Browse Categories', 'Ver categorías' ) . '>Browse Categories</a>'
		. '<a href="' . $locs . '"' . $c( 'Explore Locations', 'Explorar ubicaciones' ) . '>Explore Locations</a>'
		. '<span class="bdd-nav-menu__disabled" aria-disabled="true">Online &amp; Worldwide</span>'
		. '<span class="bdd-nav-menu__disabled" aria-disabled="true">Featured Businesses</span>'
		. '</div></details>'
		. '<a href="' . $events . '"' . $c( 'Events', 'Eventos' ) . '>Events</a>'
		. '<a href="' . $jobs . '"' . $c( 'Jobs', 'Empleos' ) . '>Jobs</a>'
		. '<a href="' . $class . '"' . $c( 'Classifieds', 'Clasificados' ) . '>Classifieds</a>'
		. '<a href="' . $groups . '"' . $c( 'WhatsApp groups', 'Grupos de WhatsApp' ) . '>WhatsApp groups</a>'
		. '<a href="' . $channels . '"' . $c( 'Broadcast channels', 'Canales de difusión' ) . '>Broadcast channels</a>'
		. '<details class="bdd-nav-menu"><summary' . $c( 'For Businesses', 'Para negocios' ) . '>For Businesses</summary><div class="bdd-nav-menu__panel bdd-nav-menu__panel--right">'
		. '<a href="' . $add . '"' . $c( 'Add Your Business', 'Agregar tu negocio' ) . '>Add Your Business</a>'
		. '<a href="' . $claim . '"' . $c( 'Claim Your Business', 'Reclamar tu negocio' ) . '>Claim Your Business</a>'
		. '<a href="' . $how . '"' . $c( 'How It Works', 'Cómo funciona' ) . '>How It Works</a>'
		. '<a href="' . $plans . '"' . $c( 'Plans & Features', 'Planes y funciones' ) . '>Plans &amp; Features</a>'
		. '<a href="' . $dash . '"' . $c( 'Business Dashboard', 'Panel del negocio' ) . '>Business Dashboard</a>'
		. '<a href="' . $help . '"' . $c( 'Help Center', 'Centro de ayuda' ) . '>Help Center</a>'
		. '</div></details></nav>'
		. '<div class="bdd-header__actions">'
		. '<a class="bdd-icon-link" href="' . $search . '" aria-label="Search">⌕</a>'
		. '<a class="bdd-icon-link" href="' . $signin . '" aria-label="Sign in">○</a>'
		. '<details class="bdd-add-menu"><summary class="bdd-button bdd-button--small"' . $c( 'Add Listing', 'Agregar listado' ) . '>Add Listing</summary><nav aria-label="Add listing options">'
		. '<a href="' . $add . '"' . $c( 'Add a Business', 'Agregar un negocio' ) . '>Add a Business</a>'
		. '<a href="' . $addev . '"' . $c( 'Post an Event', 'Publicar un evento' ) . '>Post an Event</a>'
		. '<a href="' . $addjob . '"' . $c( 'Post a Job', 'Publicar un empleo' ) . '>Post a Job</a>'
		. '<a href="' . $addcl . '"' . $c( 'Post a Classified', 'Publicar un clasificado' ) . '>Post a Classified</a>'
		. '<a href="' . $addg . '"' . $c( 'List a WhatsApp group', 'Listar un grupo de WhatsApp' ) . '>List a WhatsApp group</a>'
		. '<a href="' . $addch . '"' . $c( 'List a broadcast channel', 'Listar un canal de difusión' ) . '>List a broadcast channel</a>'
		. '</nav></details>'
		. '<details class="bdd-mobile-menu"><summary aria-label="Open navigation">☰</summary><nav aria-label="Mobile navigation">'
		. '<details class="bdd-mobile-menu__group"><summary' . $c( 'Businesses', 'Negocios' ) . '>Businesses</summary><div>'
		. '<a href="' . $list . '"' . $c( 'Find a Business', 'Encontrar un negocio' ) . '>Find a Business</a>'
		. '<a href="' . $cats . '"' . $c( 'Browse Categories', 'Ver categorías' ) . '>Browse Categories</a>'
		. '<a href="' . $locs . '"' . $c( 'Explore Locations', 'Explorar ubicaciones' ) . '>Explore Locations</a>'
		. '</div></details>'
		. '<a href="' . $events . '"' . $c( 'Events', 'Eventos' ) . '>Events</a>'
		. '<a href="' . $jobs . '"' . $c( 'Jobs', 'Empleos' ) . '>Jobs</a>'
		. '<a href="' . $class . '"' . $c( 'Classifieds', 'Clasificados' ) . '>Classifieds</a>'
		. '<a href="' . $groups . '"' . $c( 'WhatsApp groups', 'Grupos de WhatsApp' ) . '>WhatsApp groups</a>'
		. '<a href="' . $channels . '"' . $c( 'Broadcast channels', 'Canales de difusión' ) . '>Broadcast channels</a>'
		. '<details class="bdd-mobile-menu__group"><summary' . $c( 'For Businesses', 'Para negocios' ) . '>For Businesses</summary><div>'
		. '<a href="' . $add . '"' . $c( 'Add Your Business', 'Agregar tu negocio' ) . '>Add Your Business</a>'
		. '<a href="' . $claim . '"' . $c( 'Claim Your Business', 'Reclamar tu negocio' ) . '>Claim Your Business</a>'
		. '<a href="' . $how . '"' . $c( 'How It Works', 'Cómo funciona' ) . '>How It Works</a>'
		. '<a href="' . $plans . '"' . $c( 'Plans & Features', 'Planes y funciones' ) . '>Plans &amp; Features</a>'
		. '<a href="' . $dash . '"' . $c( 'Business Dashboard', 'Panel del negocio' ) . '>Business Dashboard</a>'
		. '<a href="' . $help . '"' . $c( 'Help Center', 'Centro de ayuda' ) . '>Help Center</a>'
		. '</div></details>'
		. '<a href="' . $signin . '"' . $c( 'Sign In', 'Entrar' ) . '>Sign In</a>'
		. '<strong class="bdd-mobile-menu__label"' . $c( 'Add a listing', 'Agregar un listado' ) . '>Add a listing</strong>'
		. '<a href="' . $add . '"' . $c( 'Add a Business', 'Agregar un negocio' ) . '>Add a Business</a>'
		. '<a href="' . $addev . '"' . $c( 'Post an Event', 'Publicar un evento' ) . '>Post an Event</a>'
		. '<a href="' . $addjob . '"' . $c( 'Post a Job', 'Publicar un empleo' ) . '>Post a Job</a>'
		. '<a href="' . $addcl . '"' . $c( 'Post a Classified', 'Publicar un clasificado' ) . '>Post a Classified</a>'
		. '<a href="' . $addg . '"' . $c( 'List a WhatsApp group', 'Listar un grupo de WhatsApp' ) . '>List a WhatsApp group</a>'
		. '<a href="' . $addch . '"' . $c( 'List a broadcast channel', 'Listar un canal de difusión' ) . '>List a broadcast channel</a>'
		. '</nav></details></div></div></header>';
}

function bds_dir_look_lock_print_header() {
	static $printed = false;
	if ( $printed || is_admin() || ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	$html = bds_dir_look_lock_header_html();
	if ( '' === $html ) {
		return;
	}
	$printed = true;
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function bds_dir_look_lock_print_css() {
	if ( is_admin() || ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	echo '<style id="bds-dir-look-lock">' . bds_dir_look_lock_css() . '</style>';
}

function bds_dir_look_lock_print_js() {
	if ( is_admin() || ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	echo '<script id="bds-dir-look-lock-js" src="/wp-content/mu-plugins/000-bds-directory-look-lock.js?ver=' . rawurlencode( BDS_DIR_LOOK_LOCK_VERSION ) . '"></script>';
}

/**
 * Start the cosmetic rewrite only after WordPress has resolved the request.
 *
 * The previous MU-load buffer wrapped every later output buffer. On the
 * Directory homepage that allowed independently composed fragments to flush
 * in the wrong order, producing a cached document with body markup inside the
 * head and, on uncached requests, an empty response. Starting here keeps this
 * rewrite inside the legacy composer while still seeing the complete page.
 *
 * @return void
 */
function bds_dir_look_lock_buffer_start() {
	if ( is_admin() || ! bds_dir_look_lock_is_directory() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return;
	}
	ob_start( 'bds_dir_look_lock_buffer' );
}

if ( bds_dir_look_lock_is_directory() ) {
	if ( ! defined( 'BD_ECO_HTML_CACHE_GEN' ) ) {
		define( 'BD_ECO_HTML_CACHE_GEN', BDS_DIR_LOOK_LOCK_GEN );
	}
	bds_dir_look_lock_restore();
	bds_dir_look_lock_retire();
	bds_dir_look_lock_retire_green();
	bds_dir_look_lock_restore();
	if ( function_exists( 'add_action' ) ) {
		add_action( 'template_redirect', 'bds_dir_look_lock_buffer_start', -9000 );
		add_action( 'wp_head', 'bds_dir_look_lock_print_css', 3 );
		add_action( 'wp_head', 'bds_dir_look_lock_print_arch', 4 );
		add_action( 'wp_body_open', 'bds_dir_look_lock_print_header', 1 );
		add_action( 'wp_footer', 'bds_dir_look_lock_print_header', 1 );
		add_action( 'wp_footer', 'bds_dir_look_lock_print_js', 5 );
		add_action( 'template_redirect', 'bds_dir_look_lock_keep_live_pages', 7 );
	}
}
