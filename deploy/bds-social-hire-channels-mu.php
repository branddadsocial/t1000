<?php
/**
 * Plugin Name: BrandDad Social hire channels
 * Description: Centralized external hiring platforms (Upwork/Fiverr/Freelancer/Contra) as a secondary path. BrandDad Social stays the company and primary CTA. Upwork is the founder’s personal profile; Fiverr, Freelancer, and Contra are BrandDad Social shops.
 * Version: 1.0.4
 *
 * Deploy: branddad.social wp-content/mu-plugins/bds-social-hire-channels-mu.php
 *
 * Secure design (ingestion / admin URLs):
 * - Trust boundary: Settings form, manage_options only, nonce + capability.
 * - URLs: https only, host allowlist, max length 500. Never fetched server-side (no SSRF).
 * - Stored as JSON via wp_json_encode / json_decode. No unserialize.
 * - Public output: esc_url / esc_html / esc_attr only. Outbound rel=noopener noreferrer.
 * - Analytics: gtag marketplace_click — no names, emails, or other PII.
 * - Compliance: BrandDad-origin visitors may choose a marketplace. Do not steer
 *   marketplace-originated clients to pay BrandDad directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_HIRE_VER' ) ) {
	return;
}
define( 'BDS_HIRE_VER', '1.0.4' );
define( 'BDS_HIRE_OPT', 'bds_hire_channels_v1' );
define( 'BDS_HIRE_SLUG', 'hire-branddad' );
define( 'BDS_HIRE_MAX_URL', 500 );
define( 'BDS_HIRE_MAX_PLATFORMS', 16 );
define( 'BDS_HIRE_MAX_SERVICE_MAP', 80 );

/**
 * Social host only (not Directory).
 */
function bds_hire_is_social() {
	$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$host = preg_replace( '/:\d+$/', '', $host );
	if ( false !== strpos( $host, 'directory.branddad.social' ) ) {
		return false;
	}
	return ( 'branddad.social' === $host || 'www.branddad.social' === $host );
}

/**
 * Partnero ref= preserved on first-party URLs.
 *
 * @param string $url URL.
 * @return string
 */
function bds_hire_po( $url ) {
	$url = (string) $url;
	if ( function_exists( 'bdsu_po_keep' ) ) {
		return (string) bdsu_po_keep( $url );
	}
	if ( function_exists( 'bds_po_url' ) ) {
		return (string) bds_po_url( $url );
	}
	if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
		if ( $ref !== '' && false === strpos( $url, 'ref=' ) ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
		}
	}
	return $url;
}

/**
 * Hosts we will ever link out to. Add here when a new platform is approved.
 *
 * @return string[]
 */
function bds_hire_allowed_hosts() {
	return array(
		'upwork.com',
		'www.upwork.com',
		'fiverr.com',
		'www.fiverr.com',
		'freelancer.com',
		'www.freelancer.com',
		'contra.com',
		'www.contra.com',
		'peopleperhour.com',
		'www.peopleperhour.com',
		'comeup.com',
		'www.comeup.com',
		'seoclerks.com',
		'www.seoclerks.com',
		'kwork.com',
		'www.kwork.com',
	);
}

/**
 * Product slugs that reasonably map to freelance marketplaces.
 * Reputation-removal, billboard, IMDb, Instagram unblock, and AI Ads retainers stay BrandDad-direct only.
 *
 * @return string[]
 */
function bds_hire_fit_slugs() {
	return array(
		'gbp-setup-optimization',
		'website-speed-optimization',
		'fix-my-website',
		'website-seo-audit',
		'website-seo-fixes',
		'local-seo-management-starter',
		'local-seo-management-growth',
		'website-conversion-makeover',
		'social-profile-optimization-bundle',
		'monthly-website-care',
		'linkedin-visibility-amplification-system-for-professionals',
		'linkedin-viral-posts-for-professionals',
		'instagram-viral-growth-discovery-system',
		'facebook-growth-visibility-campaigns',
		'telegram-growth-engagement-system',
		'social-media-management',
		'social-media-management-video',
		'comprehensive-seo-packages-rank-1-on-google',
		'business-growth-consultation-60',
	);
}

/**
 * Related BrandDad services for cross-sell (first-party only).
 *
 * @param string $slug Product slug.
 * @return array<int, array{0:string,1:string}>
 */
function bds_hire_related( $slug ) {
	$map = array(
		'gbp-setup-optimization' => array(
			array( 'website-seo-audit', 'Website SEO Audit' ),
			array( 'google-review-growth-setup', 'Review Growth Setup' ),
		),
		'website-seo-audit' => array(
			array( 'website-seo-fixes', 'SEO Fixes' ),
			array( 'gbp-setup-optimization', 'GBP Setup' ),
		),
		'website-seo-fixes' => array(
			array( 'comprehensive-seo-packages-rank-1-on-google', 'SEO Growth Packages' ),
			array( 'website-conversion-makeover', 'Conversion Makeover' ),
		),
		'comprehensive-seo-packages-rank-1-on-google' => array(
			array( 'social-media-management', 'Social Media Management' ),
			array( 'website-seo-audit', 'Website SEO Audit' ),
		),
		'social-media-management' => array(
			array( 'instagram-viral-growth-discovery-system', 'Instagram Growth' ),
			array( 'linkedin-visibility-amplification-system-for-professionals', 'LinkedIn Visibility' ),
		),
		'instagram-viral-growth-discovery-system' => array(
			array( 'social-media-management', 'Social Media Management' ),
			array( 'facebook-growth-visibility-campaigns', 'Facebook Visibility' ),
		),
		'linkedin-visibility-amplification-system-for-professionals' => array(
			array( 'linkedin-viral-posts-for-professionals', 'LinkedIn Content' ),
			array( 'social-profile-optimization-bundle', 'Social Profile Bundle' ),
		),
		'website-conversion-makeover' => array(
			array( 'website-seo-audit', 'Website SEO Audit' ),
			array( 'fix-my-website', 'Fix My Website' ),
		),
		'business-growth-consultation-60' => array(
			array( 'website-seo-audit', 'Website SEO Audit' ),
			array( 'social-media-management', 'Social Media Management' ),
		),
	);
	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}
	return array(
		array( 'website-seo-audit', 'Website SEO Audit' ),
		array( 'social-media-management', 'Social Media Management' ),
	);
}

/**
 * Default registry. Only confirmed public URLs are active.
 *
 * @return array<string, mixed>
 */
function bds_hire_defaults() {
	$fit = bds_hire_fit_slugs();
	$empty_map = array();
	foreach ( $fit as $slug ) {
		$empty_map[ $slug ] = '';
	}
	return array(
		'founder'  => 'Thaddeus McCollum',
		'company'  => 'BrandDad Social',
		'page_path'=> '/' . BDS_HIRE_SLUG . '/',
		'platforms'=> array(
			'upwork' => array(
				'id'        => 'upwork',
				'label'     => 'Upwork',
				'cta'       => 'Hire Thaddeus on Upwork',
				'listed_as' => 'founder',
				'active'    => 1,
				'profile'   => 'https://www.upwork.com/freelancers/~014731e7bb2c74fba3',
				'utm'       => 0,
				'services'  => $empty_map,
			),
			'fiverr' => array(
				'id'        => 'fiverr',
				'label'     => 'Fiverr',
				'cta'       => 'BrandDad Social on Fiverr',
				'listed_as' => 'company',
				'active'    => 1,
				'profile'   => 'https://www.fiverr.com/branddadsocial',
				'utm'       => 0,
				'services'  => $empty_map,
			),
			'freelancer' => array(
				'id'        => 'freelancer',
				'label'     => 'Freelancer',
				'cta'       => 'BrandDad Social on Freelancer',
				'listed_as' => 'company',
				'active'    => 1,
				'profile'   => 'https://www.freelancer.com/u/branddadsocial',
				'utm'       => 0,
				'services'  => $empty_map,
			),
			'contra' => array(
				'id'        => 'contra',
				'label'     => 'Contra',
				'cta'       => 'BrandDad Social on Contra',
				'listed_as' => 'company',
				'active'    => 1,
				'profile'   => 'https://contra.com/brand_dad_social_rk6j1i1d',
				'utm'       => 0,
				'services'  => $empty_map,
			),
		),
	);
}

/**
 * Force live profile URLs + current button labels if an older option is stored.
 */
function bds_hire_upgrade_labels() {
	if ( ! bds_hire_is_social() ) {
		return;
	}
	$saved = get_option( BDS_HIRE_OPT, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$def = bds_hire_defaults();
	if ( ! get_option( 'bds_hire_labels_102' ) ) {
		if ( empty( $saved['platforms'] ) || ! is_array( $saved['platforms'] ) ) {
			$saved = $def;
		} else {
			foreach ( array( 'upwork', 'fiverr' ) as $id ) {
				if ( empty( $saved['platforms'][ $id ] ) || ! is_array( $saved['platforms'][ $id ] ) ) {
					$saved['platforms'][ $id ] = $def['platforms'][ $id ];
					continue;
				}
				$saved['platforms'][ $id ]['cta']       = $def['platforms'][ $id ]['cta'];
				$saved['platforms'][ $id ]['listed_as'] = $def['platforms'][ $id ]['listed_as'];
				$saved['platforms'][ $id ]['profile']   = $def['platforms'][ $id ]['profile'];
				$saved['platforms'][ $id ]['active']    = 1;
				$saved['platforms'][ $id ]['utm']       = 0;
			}
		}
		update_option( BDS_HIRE_OPT, $saved, false );
		update_option( 'bds_hire_labels_102', 1, false );
	}
	if ( get_option( 'bds_hire_labels_103' ) ) {
		return;
	}
	$saved = get_option( BDS_HIRE_OPT, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	if ( empty( $saved['platforms'] ) || ! is_array( $saved['platforms'] ) ) {
		$saved = $def;
	} else {
		foreach ( array( 'freelancer', 'contra' ) as $id ) {
			$saved['platforms'][ $id ] = $def['platforms'][ $id ];
		}
	}
	update_option( BDS_HIRE_OPT, $saved, false );
	update_option( 'bds_hire_labels_103', 1, false );
	$page = get_page_by_path( BDS_HIRE_SLUG );
	if ( $page instanceof WP_Post ) {
		update_post_meta( (int) $page->ID, '_yoast_wpseo_title', 'Hire BrandDad | BrandDad Social, Upwork, Fiverr, Freelancer, or Contra' );
		update_post_meta( (int) $page->ID, '_yoast_wpseo_metadesc', 'BrandDad Social is the company. Hire us directly, hire founder Thaddeus McCollum on Upwork, or hire BrandDad Social on Fiverr, Freelancer, or Contra.' );
	}
}

function bds_hire_sanitize_url( $raw ) {
	$raw = trim( (string) $raw );
	if ( $raw === '' ) {
		return '';
	}
	if ( strlen( $raw ) > BDS_HIRE_MAX_URL ) {
		return '';
	}
	$url = esc_url_raw( $raw, array( 'https' ) );
	if ( $url === '' ) {
		return '';
	}
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( $host === '' || ! in_array( $host, bds_hire_allowed_hosts(), true ) ) {
		return '';
	}
	return $url;
}

/**
 * Merge saved option over defaults. Inactive / empty-profile platforms stay hidden in public views.
 *
 * @return array<string, mixed>
 */
function bds_hire_config() {
	$defaults = bds_hire_defaults();
	$saved    = get_option( BDS_HIRE_OPT, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$out = $defaults;
	if ( ! empty( $saved['founder'] ) ) {
		$out['founder'] = sanitize_text_field( (string) $saved['founder'] );
	}
	if ( ! empty( $saved['company'] ) ) {
		$out['company'] = sanitize_text_field( (string) $saved['company'] );
	}
	if ( empty( $saved['platforms'] ) || ! is_array( $saved['platforms'] ) ) {
		return $out;
	}
	$n = 0;
	foreach ( $saved['platforms'] as $id => $row ) {
		if ( $n >= BDS_HIRE_MAX_PLATFORMS ) {
			break;
		}
		$id = sanitize_key( (string) $id );
		if ( $id === '' || ! is_array( $row ) ) {
			continue;
		}
		$base = isset( $defaults['platforms'][ $id ] ) ? $defaults['platforms'][ $id ] : array(
			'id'        => $id,
			'label'     => ucfirst( $id ),
			'cta'       => 'View on ' . ucfirst( $id ),
			'listed_as' => 'founder',
			'active'    => 0,
			'profile'   => '',
			'utm'       => 1,
			'services'  => array(),
		);
		$base['label']     = sanitize_text_field( (string) ( $row['label'] ?? $base['label'] ) );
		$base['cta']       = sanitize_text_field( (string) ( $row['cta'] ?? $base['cta'] ) );
		$base['listed_as'] = ( isset( $defaults['platforms'][ $id ]['listed_as'] ) && 'company' === $defaults['platforms'][ $id ]['listed_as'] ) ? 'company' : 'founder';
		$base['active']  = empty( $row['active'] ) ? 0 : 1;
		$base['profile'] = bds_hire_sanitize_url( (string) ( $row['profile'] ?? '' ) );
		$base['utm']     = isset( $row['utm'] ) ? ( empty( $row['utm'] ) ? 0 : 1 ) : 1;
		$svc             = array();
		$src             = ( isset( $row['services'] ) && is_array( $row['services'] ) ) ? $row['services'] : array();
		$i               = 0;
		foreach ( $src as $slug => $href ) {
			if ( $i >= BDS_HIRE_MAX_SERVICE_MAP ) {
				break;
			}
			$slug = sanitize_title( (string) $slug );
			if ( $slug === '' ) {
				continue;
			}
			$svc[ $slug ] = bds_hire_sanitize_url( (string) $href );
			++$i;
		}
		if ( empty( $svc ) && isset( $defaults['platforms'][ $id ]['services'] ) ) {
			$svc = $defaults['platforms'][ $id ]['services'];
		}
		$base['services']     = $svc;
		$out['platforms'][ $id ] = $base;
		++$n;
	}
	return $out;
}

/**
 * Platforms that may be shown: active + valid profile URL.
 *
 * @return array<string, array<string, mixed>>
 */
function bds_hire_active_platforms() {
	$out = array();
	foreach ( bds_hire_config()['platforms'] as $id => $row ) {
		if ( empty( $row['active'] ) || empty( $row['profile'] ) ) {
			continue;
		}
		$out[ $id ] = $row;
	}
	return $out;
}

/**
 * Destination for a platform + optional BrandDad service slug.
 *
 * @param array<string, mixed> $platform Platform row.
 * @param string               $service  Product slug or empty.
 * @return string
 */
function bds_hire_dest( $platform, $service = '' ) {
	$service = sanitize_title( (string) $service );
	if ( $service !== '' && ! empty( $platform['services'][ $service ] ) ) {
		return (string) $platform['services'][ $service ];
	}
	return (string) ( $platform['profile'] ?? '' );
}

/**
 * Append conservative attribution when the platform allows query strings.
 *
 * @param string $url      Destination.
 * @param string $platform Platform id.
 * @param string $place    CTA location.
 * @param string $service  Service slug.
 * @return string
 */
function bds_hire_attr_url( $url, $platform, $place, $service ) {
	$url = (string) $url;
	if ( $url === '' ) {
		return '';
	}
	$row = bds_hire_config()['platforms'][ $platform ] ?? array();
	if ( empty( $row['utm'] ) ) {
		return $url;
	}
	return add_query_arg(
		array(
			'utm_source'   => 'branddad_social',
			'utm_medium'   => 'hire',
			'utm_campaign' => sanitize_key( $platform ),
			'utm_content'  => sanitize_key( $place ),
		),
		$url
	);
}

/**
 * Current page path for analytics (no query, no PII).
 *
 * @return string
 */
function bds_hire_source_page() {
	$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
	$path = strtolower( $path );
	$path = preg_replace( '#[^a-z0-9/_-]#', '', $path );
	return $path !== '' ? $path : '/';
}

/**
 * Hire page URL (first-party).
 *
 * @return string
 */
function bds_hire_page_url() {
	return bds_hire_po( home_url( '/' . BDS_HIRE_SLUG . '/' ) );
}

/**
 * Request path without query.
 *
 * @return string
 */
function bds_hire_req_path() {
	$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
	return untrailingslashit( strtolower( $path ) );
}

/**
 * Marketplace outbound <a>.
 *
 * @param array<string, mixed> $platform Platform.
 * @param string               $place    CTA location.
 * @param string               $service  Service slug.
 * @param string               $variant  Class variant: secondary|text.
 * @return string
 */
function bds_hire_link_html( $platform, $place, $service = '', $variant = 'secondary' ) {
	$id   = sanitize_key( (string) ( $platform['id'] ?? '' ) );
	$dest = bds_hire_dest( $platform, $service );
	if ( $id === '' || $dest === '' ) {
		return '';
	}
	$href  = bds_hire_attr_url( $dest, $id, $place, $service );
	$label = (string) ( $platform['cta'] ?? $platform['label'] );
	$class = ( 'text' === $variant ) ? 'bds-hire-a bds-hire-a--text' : 'bds-hire-a bds-hire-a--sec';
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $href ) . '" target="_blank" rel="noopener noreferrer"'
		. ' data-bds-hire="1"'
		. ' data-platform="' . esc_attr( $id ) . '"'
		. ' data-place="' . esc_attr( $place ) . '"'
		. ' data-service="' . esc_attr( $service ) . '"'
		. ' data-dest="' . esc_attr( $dest ) . '">'
		. '<span class="bds-hire-mark" aria-hidden="true">' . esc_html( strtoupper( substr( (string) $platform['label'], 0, 1 ) ) ) . '</span>'
		. esc_html( $label )
		. '</a>';
}

/**
 * Shared CSS — Social tokens, no third-party widgets.
 */
function bds_hire_css() {
	return '<style id="bds-hire-css">'
		. '.bds-hire{--navy:#0c172d;--blue:#0b83f6;--muted:#667085;--line:#dfe5ec;--soft:#f5f8fc;font-family:Arial,sans-serif;color:var(--navy)}'
		. '.bds-hire a{text-decoration:none}'
		. '.bds-hire-kicker{color:var(--blue);font-size:12px;font-weight:850;letter-spacing:.12em;text-transform:uppercase}'
		. '.bds-hire h2,.bds-hire-page h1{letter-spacing:-.03em;margin:8px 0 10px}'
		. '.bds-hire p{color:var(--muted);line-height:1.6;margin:0 0 12px}'
		. '.bds-hire-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:14px 0 6px}'
		. '.bds-hire-a{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:0 16px;border-radius:10px;font-weight:800;font-size:14px}'
		. '.bds-hire-a--pri{background:var(--navy);color:#fff;border:2px solid var(--navy)}'
		. '.bds-hire-a--pri:hover{background:#13233f}'
		. '.bds-hire-a--sec{background:#fff;color:var(--navy);border:2px solid var(--line)}'
		. '.bds-hire-a--sec:hover{border-color:var(--blue);color:var(--blue)}'
		. '.bds-hire-a--text{min-height:40px;padding:0 8px;border:0;background:transparent;color:#334155;font-weight:700}'
		. '.bds-hire-a--text:hover{color:var(--blue)}'
		. '.bds-hire-mark{width:18px;height:18px;border-radius:4px;background:#e8eef6;color:#0c172d;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:900}'
		. '.bds-hire-note{font-size:13px;color:var(--muted);margin-top:8px}'
		. '.bds-hire-band{border:1px solid var(--line);border-radius:14px;background:var(--soft);padding:18px 20px;margin:18px 0}'
		. 'body:has(.bds-hire-page) .entry-header,body:has(.bds-hire-page) h1.entry-title,body:has(.bds-hire-page) .elementor-page-title{display:none!important}'
		. '.bds-hire-page{max-width:860px;margin:0 auto;padding:36px 24px 72px}'
		. '.bds-hire-page h1{font-size:clamp(28px,4vw,42px);color:var(--navy)}'
		. '.bds-hire-page h2{font-size:22px;margin-top:28px}'
		. '.bds-hire-page h3{font-size:17px;margin:18px 0 8px}'
		. '.bds-hire-lead{font-size:18px;color:#334155;max-width:62ch}'
		. '.bds-hire-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:18px 0}'
		. '.bds-hire-card{border:1px solid var(--line);border-radius:14px;padding:18px;background:#fff}'
		. '.bds-hire-card b{color:var(--blue);font-size:12px;letter-spacing:.08em;text-transform:uppercase}'
		. '.bds-hire-faq details{border-top:1px solid var(--line);padding:12px 0}'
		. '.bds-hire-faq summary{cursor:pointer;font-weight:800;color:var(--navy);min-height:44px;display:flex;align-items:center}'
		. '.bds-hire-related{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}'
		. '.bds-hire-related a{display:inline-flex;align-items:center;min-height:40px;padding:0 12px;border-radius:8px;border:1px solid var(--line);color:var(--navy);font-weight:700;font-size:13px;background:#fff}'
		. '#bds-hire-foot{border-top:1px solid rgba(148,163,184,.16);padding:16px 22px 8px;max-width:1100px;margin:0 auto}'
		. '#bds-hire-foot .bds-hire-kicker,#bds-hire-foot h3{color:#e2e8f0}'
		. '#bds-hire-foot p,#bds-hire-foot .bds-hire-note{color:#94a3b8}'
		. '#bds-hire-foot h3{margin:6px 0 8px;font-size:15px}'
		. '#bds-hire-foot .bds-hire-a--pri{background:#fff;color:#0c172d;border-color:#fff}'
		. '#bds-hire-foot .bds-hire-a--sec{background:transparent;color:#e2e8f0;border-color:rgba(226,232,240,.28)}'
		. '#bds-hire-foot .bds-hire-mark{background:rgba(255,255,255,.12);color:#fff}'
		. '.bds-hire-founder{display:grid;grid-template-columns:1fr;gap:12px;margin:22px 0;padding:20px;border:1px solid var(--line);border-radius:14px;background:#fff}'
		. 'html[data-bds-theme="dark"] .bds-hire-band,html[data-bds-theme="dark"] .bds-hire-card,html[data-bds-theme="dark"] .bds-hire-founder{background:#111827;border-color:#1f2937}'
		. 'html[data-bds-theme="dark"] .bds-hire,html[data-bds-theme="dark"] .bds-hire-page h1,html[data-bds-theme="dark"] .bds-hire h2,html[data-bds-theme="dark"] .bds-hire-faq summary{color:#e5e7eb}'
		. 'html[data-bds-theme="dark"] .bds-hire p,html[data-bds-theme="dark"] .bds-hire-lead{color:#94a3b8}'
		. 'html[data-bds-theme="dark"] .bds-hire-a--sec{background:#111827;color:#e5e7eb;border-color:#334155}'
		. '@media(max-width:720px){.bds-hire-grid{grid-template-columns:1fr}.bds-hire-a{width:100%}}'
		. '</style>';
}

/**
 * Secondary marketplace band (never the primary CTA).
 *
 * @param string $place   CTA location.
 * @param string $service Service slug.
 * @param bool   $primary Include BrandDad primary button.
 * @return string
 */
function bds_hire_band_html( $place, $service = '', $primary = true ) {
	$plats = bds_hire_active_platforms();
	if ( ! $plats ) {
		return '';
	}
	$hire = bds_hire_page_url();
	$svc  = $service !== '' ? home_url( '/product/' . sanitize_title( $service ) . '/' ) : home_url( '/services/' );
	$html  = '<aside class="bds-hire bds-hire-band" data-bds-hire-band="' . esc_attr( BDS_HIRE_VER ) . '" data-place="' . esc_attr( $place ) . '">';
	$html .= '<div class="bds-hire-kicker">Another way to hire</div>';
	$html .= '<h2>Prefer a freelance marketplace?</h2>';
	$html .= '<p>You can also hire through a marketplace you already use. Upwork is founder Thaddeus McCollum’s freelancer profile. Fiverr, Freelancer, and Contra are BrandDad Social shops. BrandDad Social remains the company either way.</p>';
	$html .= '<div class="bds-hire-actions">';
	if ( $primary ) {
		$html .= '<a class="bds-hire-a bds-hire-a--pri" href="' . esc_url( bds_hire_po( $svc ) ) . '">Get started with BrandDad</a>';
	}
	foreach ( $plats as $row ) {
		$html .= bds_hire_link_html( $row, $place, $service );
	}
	$html .= '</div>';
	$html .= '<p class="bds-hire-note">If you already started on a marketplace, finish there. These links are for BrandDad visitors who prefer that checkout — not a request to leave an existing marketplace order.</p>';
	$html .= '<p class="bds-hire-note"><a class="bds-hire-a bds-hire-a--text" href="' . esc_url( $hire ) . '">How hiring works →</a></p>';
	$html .= '</aside>';
	return $html;
}

/**
 * Footer hire strip.
 *
 * @return string
 */
function bds_hire_footer_html() {
	$plats = bds_hire_active_platforms();
	if ( ! $plats ) {
		return '';
	}
	$html  = '<div id="bds-hire-foot" class="bds-hire" data-bds-hire-foot="' . esc_attr( BDS_HIRE_VER ) . '">';
	$html .= '<div class="bds-hire-kicker">Alternative hiring channels</div>';
	$html .= '<h3>Hire BrandDad</h3>';
	$html .= '<p>Work with BrandDad Social directly, hire founder Thaddeus McCollum on Upwork, or hire BrandDad Social on Fiverr, Freelancer, or Contra. These are not BrandDad-owned platforms.</p>';
	$html .= '<div class="bds-hire-actions">';
	$html .= '<a class="bds-hire-a bds-hire-a--pri" href="' . esc_url( bds_hire_page_url() ) . '" data-bds-hire="1" data-platform="branddad" data-place="footer" data-service="" data-dest="' . esc_attr( bds_hire_page_url() ) . '">Direct — Hire BrandDad</a>';
	foreach ( $plats as $row ) {
		$html .= bds_hire_link_html( $row, 'footer', '', 'secondary' );
	}
	$html .= '</div></div>';
	return $html;
}

/**
 * Founder block — no invented credentials, no invented photo.
 *
 * @return string
 */
function bds_hire_founder_html() {
	$cfg = bds_hire_config();
	$html  = '<section class="bds-hire bds-hire-founder" id="bds-founder">';
	$html .= '<div class="bds-hire-kicker">Founder</div>';
	$html .= '<h2>' . esc_html( $cfg['founder'] ) . '</h2>';
	$html .= '<p><strong>Founder, BrandDad</strong> — BrandDad Social is the company. ' . esc_html( $cfg['founder'] ) . ' is the founder. His Upwork profile is under his name. Fiverr, Freelancer, and Contra list BrandDad Social.</p>';
	$html .= '<p>BrandDad was created to help businesses and creators with focused digital growth: social visibility, SEO and local search, websites and branding through the BrandDad network, and clear-scope services with visible deliverables.</p>';
	$html .= '<div class="bds-hire-actions">';
	$html .= '<a class="bds-hire-a bds-hire-a--pri" href="' . esc_url( bds_hire_po( home_url( '/services/' ) ) ) . '">Browse BrandDad services</a>';
	$html .= '<a class="bds-hire-a bds-hire-a--sec" href="' . esc_url( bds_hire_page_url() ) . '">Work with BrandDad your way</a>';
	$html .= '</div></section>';
	return $html;
}

/**
 * Full Hire BrandDad page (SEO + conversion).
 *
 * @return string
 */
function bds_hire_page_html() {
	$cfg   = bds_hire_config();
	$plats = bds_hire_active_platforms();
	$svc   = bds_hire_po( home_url( '/services/' ) );
	$con   = bds_hire_po( home_url( '/contact-us/' ) );
	$co    = bds_hire_po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=hire' );
	$check = bds_hire_po( home_url( '/check-your-website/' ) );
	$about = bds_hire_po( home_url( '/about-us/' ) );

	$html  = '<article class="bds-hire bds-hire-page" data-bds-hire-page="' . esc_attr( BDS_HIRE_VER ) . '">';
	$html .= '<div class="bds-hire-kicker">Hire BrandDad</div>';
	$html .= '<h1>Work with BrandDad your way</h1>';
	$html .= '<p class="bds-hire-lead">Need marketing, social growth, SEO, websites, branding, AI ads, or another digital service? Work directly with BrandDad Social — or hire on a marketplace you already use.</p>';
	$html .= '<p><strong>BrandDad Social is the company.</strong> ' . esc_html( $cfg['founder'] ) . ' is the founder. Upwork lists him by name. Fiverr, Freelancer, and Contra list the BrandDad Social shop.</p>';

	$html .= '<div class="bds-hire-actions">';
	$html .= '<a class="bds-hire-a bds-hire-a--pri" href="' . esc_url( $svc ) . '">Get started with BrandDad</a>';
	$html .= '<a class="bds-hire-a bds-hire-a--sec" href="' . esc_url( $con ) . '">Talk to us first</a>';
	$html .= '</div>';

	$html .= '<h2>How working with BrandDad works</h2>';
	$html .= '<div class="bds-hire-grid">';
	$html .= '<div class="bds-hire-card"><b>Direct (primary)</b><h3>Buy or contact BrandDad Social</h3><p>Pick a scoped service, check out on this site, or message us. Directory members save 10% on eligible BrandDad services. This is the default path.</p><a class="bds-hire-a bds-hire-a--pri" href="' . esc_url( $svc ) . '">Browse services</a></div>';
	$html .= '<div class="bds-hire-card"><b>Marketplace (optional)</b><h3>Hire on a platform you already trust</h3><p>Upwork is Thaddeus McCollum’s freelancer profile. Fiverr, Freelancer, and Contra are BrandDad Social shops. Use those links only if you prefer that checkout — BrandDad is still the business doing the work.</p></div>';
	$html .= '</div>';

	if ( $plats ) {
		$html .= '<h2>Freelance marketplaces we maintain</h2>';
		$html .= '<p>Only active platforms with a real public profile are shown. These are third-party marketplaces, not BrandDad-owned products.</p>';
		$html .= '<div class="bds-hire-actions">';
		foreach ( $plats as $row ) {
			$html .= bds_hire_link_html( $row, 'hire_page', '' );
		}
		$html .= '</div>';
		$html .= '<p class="bds-hire-note">Already in a conversation or order on a marketplace? Stay on that platform for communication and payment. We will not ask you to move an existing marketplace job off-platform.</p>';
	}

	$html .= '<h2>Service categories</h2>';
	$html .= '<p>Most BrandDad Social work is sold here as a clear-scope product. Logos and full website builds also live on BrandDad.co. Hosting is on HostTech.</p>';
	$html .= '<div class="bds-hire-related" aria-label="BrandDad service categories">';
	$doors = array(
		array( 'Local &amp; web', home_url( '/services/#lane-local' ) ),
		array( 'AI Ads', home_url( '/ai-ads/' ) ),
		array( 'Social &amp; LinkedIn', home_url( '/services/#lane-more' ) ),
		array( 'Check your website', $check ),
		array( 'Logos &amp; websites', $co ),
		array( 'Growth consult', home_url( '/product/business-growth-consultation-60/' ) ),
	);
	foreach ( $doors as $d ) {
		$html .= '<a href="' . esc_url( bds_hire_po( $d[1] ) ) . '">' . $d[0] . '</a>';
	}
	$html .= '</div>';

	$html .= bds_hire_founder_html();
	$html .= '<p><a href="' . esc_url( $about ) . '">More about BrandDad Social →</a></p>';

	$html .= '<h2>Frequently asked questions</h2>';
	$html .= '<div class="bds-hire-faq">';
	$html .= '<details open><summary>Whose name is on Upwork vs the other shops?</summary><p>Upwork is founder Thaddeus McCollum’s personal freelancer profile. Fiverr, Freelancer, and Contra are BrandDad Social sellers. You are still hiring BrandDad’s work either way.</p></details>';
	$html .= '<details><summary>Should I buy here or on a marketplace?</summary><p>If you are comfortable checking out on BrandDad Social, that is the simplest path — full catalog, Directory member pricing on eligible services, and direct support. Use a marketplace only if you prefer that platform’s checkout and protections.</p></details>';
	$html .= '<details><summary>I found BrandDad on Upwork, Fiverr, or Freelancer. Can I pay on this website instead?</summary><p>No. If the relationship started on a marketplace, keep communication and payment on that marketplace. Their rules require it, and it protects both sides.</p></details>';
	$html .= '<details><summary>Do marketplace listings match every BrandDad service?</summary><p>No. Marketplace shops may not list every SKU. The buttons still appear on every BrandDad product so you can hire the same company on a platform you already use. If that marketplace does not sell this exact package, pick the closest listed offer or buy here.</p></details>';
	$html .= '<details><summary>Is BrandDad just a collection of gigs?</summary><p>No. BrandDad Social is the primary business and website. Marketplaces are extra doors for customers who already trust those platforms.</p></details>';
	$html .= '</div>';

	$html .= '<div class="bds-hire-actions" style="margin-top:28px">';
	$html .= '<a class="bds-hire-a bds-hire-a--pri" href="' . esc_url( $svc ) . '">Get started with BrandDad</a>';
	$html .= '<a class="bds-hire-a bds-hire-a--sec" href="' . esc_url( $con ) . '">Contact BrandDad</a>';
	$html .= '</div></article>';
	return $html;
}

/**
 * Hire page SEO tags.
 */
function bds_hire_seo_head() {
	if ( ! bds_hire_is_hire_request() ) {
		return;
	}
	$title = 'Hire BrandDad | BrandDad Social, Upwork, Fiverr, Freelancer, or Contra';
	$desc  = 'BrandDad Social is the company. Hire us directly, hire founder Thaddeus McCollum on Upwork, or hire BrandDad Social on Fiverr, Freelancer, or Contra.';
	$canon = home_url( '/' . BDS_HIRE_SLUG . '/' );
	$og    = 'https://branddad.social/wp-content/uploads/2023/04/3-1-1.png';
	echo '<link rel="canonical" href="' . esc_url( $canon ) . '" />' . "\n";
	echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $canon ) . '" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $og ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary" />' . "\n";
	$person_same = array( home_url( '/' ) );
	$org_same    = array();
	foreach ( bds_hire_active_platforms() as $row ) {
		$profile = (string) ( $row['profile'] ?? '' );
		if ( $profile === '' ) {
			continue;
		}
		if ( ( $row['listed_as'] ?? 'founder' ) === 'company' ) {
			$org_same[] = $profile;
		} else {
			$person_same[] = $profile;
		}
	}
	$graph = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type'       => 'WebPage',
				'@id'         => $canon . '#page',
				'url'         => $canon,
				'name'        => $title,
				'description' => $desc,
				'isPartOf'    => array( '@id' => 'https://branddad.social#website' ),
				'about'       => array( '@id' => 'https://branddad.social#organization' ),
			),
			array(
				'@type'       => 'Organization',
				'@id'         => 'https://branddad.social#organization',
				'name'        => 'BrandDad Social',
				'url'         => 'https://branddad.social/',
				'founder'     => array( '@id' => 'https://branddad.social/hire-branddad/#thaddeus' ),
			),
			array(
				'@type'       => 'Person',
				'@id'         => 'https://branddad.social/hire-branddad/#thaddeus',
				'name'        => 'Thaddeus McCollum',
				'jobTitle'    => 'Founder',
				'worksFor'    => array( '@id' => 'https://branddad.social#organization' ),
				'sameAs'      => array_values( array_unique( $person_same ) ),
			),
			array(
				'@type'      => 'FAQPage',
				'mainEntity' => array(
					array(
						'@type' => 'Question',
						'name'  => 'Whose name is on Upwork vs Fiverr?',
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => 'Upwork is founder Thaddeus McCollum’s personal freelancer profile. Fiverr is the BrandDad Social seller. You are still hiring BrandDad’s work either way.',
						),
					),
					array(
						'@type' => 'Question',
						'name'  => 'If I found BrandDad on a marketplace, can I pay on this website instead?',
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => 'No. If the relationship started on a marketplace, keep communication and payment on that marketplace.',
						),
					),
				),
			),
		),
	);
	if ( $org_same ) {
		$graph['@graph'][1]['sameAs'] = array_values( array_unique( $org_same ) );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/**
 * @return bool
 */
function bds_hire_is_hire_request() {
	if ( is_page( BDS_HIRE_SLUG ) ) {
		return true;
	}
	return ( '/' . BDS_HIRE_SLUG === bds_hire_req_path() );
}

/**
 * Document title for the hire page.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function bds_hire_title_parts( $parts ) {
	if ( bds_hire_is_hire_request() ) {
		$parts['title'] = 'Hire BrandDad';
	}
	return $parts;
}

/**
 * Create the WP page once so sitemaps / editors can see it.
 */
function bds_hire_ensure_page() {
	if ( ! bds_hire_is_social() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_page_by_path( BDS_HIRE_SLUG ) ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_title'   => 'Hire BrandDad',
			'post_name'    => BDS_HIRE_SLUG,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[bds_hire_page]',
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return;
	}
	update_post_meta( (int) $id, '_yoast_wpseo_title', 'Hire BrandDad | BrandDad Social, Upwork, Fiverr, Freelancer, or Contra' );
	update_post_meta( (int) $id, '_yoast_wpseo_metadesc', 'BrandDad Social is the company. Hire us directly, hire founder Thaddeus McCollum on Upwork, or hire BrandDad Social on Fiverr, Freelancer, or Contra.' );
	update_post_meta( (int) $id, '_yoast_wpseo_canonical', home_url( '/' . BDS_HIRE_SLUG . '/' ) );
}

/**
 * Front routing: aliases 301; missing page still renders.
 */
function bds_hire_route() {
	if ( is_admin() || ! bds_hire_is_social() ) {
		return;
	}
	$path    = bds_hire_req_path();
	$aliases = array( '/work-with-us', '/hire', '/hire-us', '/work-with-branddad' );
	if ( in_array( $path, $aliases, true ) ) {
		$to = bds_hire_page_url();
		$q  = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
		if ( $q !== '' ) {
			$to .= ( false === strpos( $to, '?' ) ? '?' : '&' ) . $q;
		}
		wp_safe_redirect( $to, 301 );
		exit;
	}
	if ( '/' . BDS_HIRE_SLUG !== $path ) {
		return;
	}
	if ( get_page_by_path( BDS_HIRE_SLUG ) ) {
		return;
	}
	status_header( 200 );
	nocache_headers();
	global $wp_query;
	if ( $wp_query ) {
		$wp_query->is_404  = false;
		$wp_query->is_page = true;
	}
	add_filter( 'document_title_parts', 'bds_hire_title_parts' );
	get_header();
	echo bds_hire_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo bds_hire_page_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	get_footer();
	exit;
}

/**
 * Replace shortcode / thin page body with the real hire article.
 *
 * @param string $content Post content.
 * @return string
 */
function bds_hire_filter_content( $content ) {
	if ( ! bds_hire_is_social() || is_admin() ) {
		return $content;
	}
	if ( is_page( BDS_HIRE_SLUG ) ) {
		return bds_hire_css() . bds_hire_page_html();
	}
	if ( is_page( 'about-us' ) && is_main_query() && in_the_loop() ) {
		return $content . bds_hire_css() . bds_hire_founder_html();
	}
	if ( is_page( 'services' ) && is_main_query() && in_the_loop() ) {
		return $content . bds_hire_css() . bds_hire_band_html( 'services_hub', '', true );
	}
	return $content;
}

/**
 * Product-page marketplace band — every Social product.
 */
function bds_hire_product_band() {
	if ( ! bds_hire_is_social() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return;
	}
	$slug = (string) $product->get_slug();
	echo bds_hire_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo bds_hire_band_html( 'product', $slug, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$rel = bds_hire_related( $slug );
	if ( $rel ) {
		echo '<div class="bds-hire"><div class="bds-hire-kicker">Also from BrandDad</div><div class="bds-hire-related">';
		foreach ( $rel as $row ) {
			echo '<a href="' . esc_url( bds_hire_po( home_url( '/product/' . $row[0] . '/' ) ) ) . '">' . esc_html( $row[1] ) . '</a>';
		}
		echo '</div></div>';
	}
}

/**
 * Homepage one-liner under the sell band — points to Hire page, not a button pile.
 */
function bds_hire_home_line() {
	if ( ! bds_hire_is_social() || is_admin() ) {
		return;
	}
	$path = untrailingslashit( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ) );
	if ( $path !== '' && $path !== '/' ) {
		return;
	}
	echo bds_hire_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p id="bds-hire-home-line" class="bds-hire" hidden><a class="bds-hire-a bds-hire-a--text" href="' . esc_url( bds_hire_page_url() ) . '">Prefer a freelance marketplace? Work with BrandDad your way →</a></p>';
}

/**
 * Footer strip + nav/drawer inject + analytics. DOM move only (HTML already escaped).
 */
function bds_hire_footer_boot() {
	if ( ! bds_hire_is_social() || is_admin() ) {
		return;
	}
	echo bds_hire_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo bds_hire_footer_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$hire = bds_hire_page_url();
	$src  = bds_hire_source_page();
	?>
	<script id="bds-hire-boot">
	(function(){
	  var hire=<?php echo wp_json_encode( $hire ); ?>;
	  var src=<?php echo wp_json_encode( $src ); ?>;
	  var foot=document.getElementById('bdsSocialFooterLock');
	  var strip=document.getElementById('bds-hire-foot');
	  if(foot&&strip&&strip.parentNode!==foot){
	    var bottom=foot.querySelector('.bds-sfl-bottom, .bds-footer-bottom');
	    if(bottom) foot.insertBefore(strip, bottom);
	    else foot.appendChild(strip);
	  }
	  var ql=foot?foot.querySelector('ul'):null;
	  if(ql&&hire&&!ql.querySelector('a[href*="hire-branddad"]')){
	    var about=null;
	    ql.querySelectorAll('a').forEach(function(a){ if((a.textContent||'').trim()==='About') about=a; });
	    if(about&&about.parentNode){
	      var li=document.createElement('li');
	      var a=document.createElement('a');
	      a.href=hire; a.textContent='Hire BrandDad';
	      li.appendChild(a);
	      if(about.parentNode.nextSibling) about.parentNode.parentNode.insertBefore(li, about.parentNode.nextSibling);
	      else about.parentNode.parentNode.appendChild(li);
	    }
	  }
	  var nav=document.querySelector('#bds-nav-drawer nav');
	  if(nav&&hire&&!nav.querySelector('a[href*="hire-branddad"]')){
	    var after=null;
	    nav.querySelectorAll('a').forEach(function(a){ if((a.textContent||'').replace(/\s+/g,' ').indexOf('About')===0) after=a; });
	    if(after&&after.parentNode){
	      var n=document.createElement('a');
	      n.href=hire; n.innerHTML='Hire BrandDad<b>→</b>';
	      if(after.nextSibling) after.parentNode.insertBefore(n, after.nextSibling);
	      else after.parentNode.appendChild(n);
	    }
	  }
	  var home=document.getElementById('bds-soc-home-sell');
	  var line=document.getElementById('bds-hire-home-line');
	  if(home&&line){
	    line.hidden=false;
	    if(home.parentNode) home.parentNode.insertBefore(line, home.nextSibling);
	  }
	  document.addEventListener('click',function(ev){
	    var a=ev.target&&ev.target.closest?ev.target.closest('[data-bds-hire]'):null;
	    if(!a||typeof gtag!=='function') return;
	    try{
	      gtag('event','marketplace_click',{
	        platform:a.getAttribute('data-platform')||'',
	        source_page:src,
	        service:a.getAttribute('data-service')||'',
	        cta_location:a.getAttribute('data-place')||'',
	        destination:a.getAttribute('data-dest')||a.href||'',
	        engagement:true
	      });
	    }catch(e){}
	  },true);
	})();
	</script>
	<?php
}

/**
 * Add Hire BrandDad under More → after About. Shop stays out of the header.
 *
 * @param string $items Menu HTML.
 * @param mixed  $args  Args.
 * @return string
 */
function bds_hire_menu_items( $items, $args ) {
	if ( ! bds_hire_is_social() || false !== strpos( $items, 'hire-branddad' ) ) {
		return $items;
	}
	$li = '<li class="menu-item menu-item-type-custom menu-item-object-custom bdsu-menu-item"><a href="' . esc_url( bds_hire_page_url() ) . '">Hire BrandDad</a></li>';
	if ( false !== strpos( $items, '>About</a></li>' ) ) {
		return str_replace( '>About</a></li>', '>About</a></li>' . $li, $items );
	}
	return $items;
}

/**
 * Admin: Settings → Hire channels.
 */
function bds_hire_admin_menu() {
	if ( ! bds_hire_is_social() ) {
		return;
	}
	add_options_page(
		'Hire channels',
		'Hire channels',
		'manage_options',
		'bds-hire-channels',
		'bds_hire_admin_render'
	);
}

/**
 * Save + render admin.
 */
function bds_hire_admin_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cfg = bds_hire_config();
	if ( isset( $_POST['bds_hire_save'] ) && check_admin_referer( 'bds_hire_save', 'bds_hire_nonce' ) ) {
		$next = bds_hire_defaults();
		$next['founder'] = sanitize_text_field( wp_unslash( $_POST['founder'] ?? $next['founder'] ) );
		$next['company'] = sanitize_text_field( wp_unslash( $_POST['company'] ?? $next['company'] ) );
		$posted          = isset( $_POST['platforms'] ) && is_array( $_POST['platforms'] ) ? wp_unslash( $_POST['platforms'] ) : array();
		foreach ( $next['platforms'] as $id => $row ) {
			$p = isset( $posted[ $id ] ) && is_array( $posted[ $id ] ) ? $posted[ $id ] : array();
			$next['platforms'][ $id ]['label']   = sanitize_text_field( (string) ( $p['label'] ?? $row['label'] ) );
			$next['platforms'][ $id ]['cta']     = sanitize_text_field( (string) ( $p['cta'] ?? $row['cta'] ) );
			$next['platforms'][ $id ]['active']  = empty( $p['active'] ) ? 0 : 1;
			$next['platforms'][ $id ]['utm']     = empty( $p['utm'] ) ? 0 : 1;
			$next['platforms'][ $id ]['profile'] = bds_hire_sanitize_url( (string) ( $p['profile'] ?? '' ) );
			if ( $next['platforms'][ $id ]['active'] && $next['platforms'][ $id ]['profile'] === '' ) {
				$next['platforms'][ $id ]['active'] = 0;
			}
			$svc_in = isset( $p['services'] ) && is_array( $p['services'] ) ? $p['services'] : array();
			foreach ( bds_hire_fit_slugs() as $slug ) {
				$next['platforms'][ $id ]['services'][ $slug ] = bds_hire_sanitize_url( (string) ( $svc_in[ $slug ] ?? '' ) );
			}
		}
		update_option( BDS_HIRE_OPT, $next, false );
		$cfg = $next;
		echo '<div class="updated"><p>Hire channels saved. Inactive platforms and empty URLs stay hidden on the site.</p></div>';
	}
	echo '<div class="wrap"><h1>Hire channels</h1>';
	echo '<p>BrandDad Social stays the company and primary checkout. <strong>Upwork</strong> is founder Thaddeus McCollum’s personal freelancer profile. <strong>Fiverr</strong> is the BrandDad Social seller (<code>/branddadsocial</code>). Only <code>https</code> URLs on approved hosts are stored. Leave a URL blank rather than guessing. A platform with no profile URL cannot be activated.</p>';
	echo '<p>Public hire page: <a href="' . esc_url( home_url( '/' . BDS_HIRE_SLUG . '/' ) ) . '">/' . esc_html( BDS_HIRE_SLUG ) . '/</a></p>';
	echo '<form method="post">';
	wp_nonce_field( 'bds_hire_save', 'bds_hire_nonce' );
	echo '<table class="form-table"><tr><th>Founder name</th><td><input class="regular-text" name="founder" value="' . esc_attr( $cfg['founder'] ) . '" maxlength="80"></td></tr>';
	echo '<tr><th>Company</th><td><input class="regular-text" name="company" value="' . esc_attr( $cfg['company'] ) . '" maxlength="80"></td></tr></table>';
	foreach ( $cfg['platforms'] as $id => $row ) {
		echo '<h2>' . esc_html( $row['label'] ) . '</h2>';
		echo '<table class="form-table">';
		echo '<tr><th>Display name</th><td><input class="regular-text" name="platforms[' . esc_attr( $id ) . '][label]" value="' . esc_attr( $row['label'] ) . '" maxlength="40"></td></tr>';
		echo '<tr><th>Button label</th><td><input class="regular-text" name="platforms[' . esc_attr( $id ) . '][cta]" value="' . esc_attr( $row['cta'] ) . '" maxlength="80"></td></tr>';
		echo '<tr><th>Profile URL</th><td><input class="large-text" name="platforms[' . esc_attr( $id ) . '][profile]" value="' . esc_attr( $row['profile'] ) . '" placeholder="https://…" maxlength="500">';
		if ( 'freelancer' === $id && $row['profile'] === '' ) {
			echo '<p class="description">TODO: paste the public Freelancer.com profile URL when you have it. Do not invent <code>/u/…</code>.</p>';
		}
		if ( 'upwork' === $id ) {
			echo '<p class="description">Public freelancer profile is under Thaddeus McCollum — not the BrandDad Social shop name.</p>';
		}
		if ( 'fiverr' === $id ) {
			echo '<p class="description">BrandDad Social seller handle <code>/branddadsocial</code>. Paste a specific gig URL per service below only when that gig is public.</p>';
		}
		echo '</td></tr>';
		echo '<tr><th>Active</th><td><label><input type="checkbox" name="platforms[' . esc_attr( $id ) . '][active]" value="1"' . checked( ! empty( $row['active'] ), true, false ) . '> Show when a valid profile URL exists</label></td></tr>';
		echo '<tr><th>UTM params</th><td><label><input type="checkbox" name="platforms[' . esc_attr( $id ) . '][utm]" value="1"' . checked( ! empty( $row['utm'] ), true, false ) . '> Append utm_source=branddad_social (uncheck if a platform breaks the URL)</label></td></tr>';
		echo '</table>';
		echo '<details><summary>Specific service listing URLs (optional — blank uses the profile)</summary><table class="widefat striped"><thead><tr><th>BrandDad service</th><th>Marketplace listing URL</th></tr></thead><tbody>';
		foreach ( bds_hire_fit_slugs() as $slug ) {
			$val = isset( $row['services'][ $slug ] ) ? $row['services'][ $slug ] : '';
			echo '<tr><td><code>' . esc_html( $slug ) . '</code></td><td><input class="large-text" name="platforms[' . esc_attr( $id ) . '][services][' . esc_attr( $slug ) . ']" value="' . esc_attr( $val ) . '" maxlength="500"></td></tr>';
		}
		echo '</tbody></table></details>';
	}
	echo '<p><button type="submit" class="button button-primary" name="bds_hire_save" value="1">Save hire channels</button></p>';
	echo '</form></div>';
}

add_action( 'init', 'bds_hire_upgrade_labels', 4 );
add_action( 'admin_init', 'bds_hire_ensure_page', 30 );
add_action( 'admin_menu', 'bds_hire_admin_menu' );
add_action( 'template_redirect', 'bds_hire_route', 1 );
add_action( 'wp_head', 'bds_hire_seo_head', 4 );
add_filter( 'document_title_parts', 'bds_hire_title_parts' );
add_filter( 'the_content', 'bds_hire_filter_content', 20 );
add_action( 'woocommerce_single_product_summary', 'bds_hire_product_band', 36 );
add_action( 'wp_footer', 'bds_hire_home_line', 14 );
add_action( 'wp_footer', 'bds_hire_footer_boot', 20 );
add_filter( 'wp_nav_menu_items', 'bds_hire_menu_items', 1001, 2 );
add_shortcode( 'bds_hire_page', 'bds_hire_page_html' );
add_shortcode(
	'bds_hire_band',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'place' => 'shortcode', 'service' => '' ), $atts, 'bds_hire_band' );
		return bds_hire_css() . bds_hire_band_html( sanitize_key( $atts['place'] ), sanitize_title( $atts['service'] ), true );
	}
);
