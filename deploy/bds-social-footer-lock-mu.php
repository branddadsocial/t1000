<?php
/**
 * Plugin Name: BrandDad Social Footer Lock
 * Description: Pinned footer for branddad.social — always at the bottom of the page. Strips Elementor/unified/theme footers.
 * Version: 1.1.17
 * Author: BrandDad
 *
 * Deploy: branddad.social wp-content/mu-plugins/bds-social-footer-lock-mu.php
 * Note: Owns footer even if site chrome MU fails to paint. Directory uses bds-directory-footer-lock-mu.php.
 * 1.1.17 — Replace missing Books URL with the live Guides destination.
 * 1.1.16 — Quick links: Hire BrandDad (direct hire page). Copy only; no chrome restyle.
 * 1.1.15 — Lock-footer WhatsApp uses rel="noopener noreferrer" (match helper). Copy only; no chrome restyle.
 * 1.1.14 — WhatsApp footer/NAP links open in a new tab (target=_blank). Copy/behavior only; no chrome restyle.
 * 1.1.13 — Remove Telegram from every footer path; WhatsApp is the primary contact. Copy only; no chrome restyle.
 * 1.1.12 — Buy SMM in @BrandDadSocialAIBot, or message @branddaddi / WhatsApp. Copy only; no chrome restyle.
 * 1.1.11 — Schema graph: Organization makesOffer ↔ Offer ↔ Service (no prices). Homepage FAQPage from visible Q&A. mainEntity stays Organization.
 * 1.1.10 — Schema only: Organization (not LocalBusiness). Public NAP stays; no geo/hours/priceRange. WeWork, no storefront.
 * 1.1.9 — Named BrandDad family doors on the visible .bd-footer (Directory / BrandDad.co / HostTech).
 * 1.1.8 — Affiliate join on the visible .bd-footer (lock footer stays hidden when chrome footer exists).
 * 1.1.7 — Directory affiliate join in footer / network column (copy only; no hero restyle).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_SOCIAL_FOOTER_LOCK_MU' ) ) {
	return;
}
define( 'BDS_SOCIAL_FOOTER_LOCK_MU', true );
define( 'BDS_SOCIAL_FOOTER_LOCK_MU_VER', '1.1.17' );

/** Exact sitewide NAP — keep this string identical on every public page. */
define( 'BDS_SOCIAL_NAP_NAME', 'BrandDad.social' );
define( 'BDS_SOCIAL_NAP_STREET', '220 N. Green Street' );
define( 'BDS_SOCIAL_NAP_CITY', 'Chicago' );
define( 'BDS_SOCIAL_NAP_STATE', 'Illinois' );
define( 'BDS_SOCIAL_NAP_ZIP', '60607' );
/** Public contact is WhatsApp chat (not a call-800 citation). */
define( 'BDS_SOCIAL_NAP_PHONE_RAW', '18729105115' );
define( 'BDS_SOCIAL_NAP_PHONE_DISPLAY', '872-910-5115' );
define( 'BDS_SOCIAL_WA_HREF', 'https://wa.me/18729105115?text=Help%20me%20choose%20a%20BrandDad%20service' );
define( 'BDS_SOCIAL_WA_CTA', 'Send us a message' );
define( 'BDS_SOCIAL_SCHEMA_ORG_ID', 'https://branddad.social#organization' );
define( 'BDS_SOCIAL_SCHEMA_WEBSITE_ID', 'https://branddad.social#WebSite' );
define( 'BDS_SOCIAL_SCHEMA_WEBPAGE_ID', 'https://branddad.social#WebPage' );
define( 'BDS_SOCIAL_SCHEMA_LOGO_ID', 'https://branddad.social#logo' );
define( 'BDS_SOCIAL_SCHEMA_ADDRESS_ID', 'https://branddad.social#address' );
define( 'BDS_SOCIAL_SCHEMA_HOME', 'https://branddad.social/' );
define( 'BDS_SOCIAL_SCHEMA_LOGO', 'https://branddad.social/wp-content/uploads/2023/04/3-1-1.png' );
define( 'BDS_SOCIAL_SCHEMA_EMAIL', 'hello@branddad.social' );
define( 'BDS_SOCIAL_SCHEMA_BRAND', 'BrandDad Social' );

/**
 * @return bool
 */
function bds_social_footer_lock_is_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( false === strpos( $host, 'branddad.social' ) ) {
		return false;
	}
	if ( false !== strpos( $host, 'directory.' ) || false !== strpos( $host, 'affiliates.' ) ) {
		return false;
	}
	return true;
}

/**
 * @return bool
 */
function bds_social_footer_lock_should_run() {
	return bds_social_footer_lock_is_host() && ! is_admin();
}

/**
 * Preserve Partnero ?ref= on sister-site doors.
 *
 * @param string $url URL.
 * @return string
 */
function bds_social_footer_po( $url ) {
	if ( function_exists( 'bds_po_url' ) ) {
		return bds_po_url( $url );
	}
	$ref = '';
	if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( $ref === '' ) {
		$ref = 'mcdqf8t1iinf';
	}
	if ( false === strpos( $url, 'ref=' ) ) {
		$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
	}
	return $url;
}

/**
 * Partnero Directory affiliate join URL (existing program).
 *
 * @return string
 */
function bds_social_aff_join_url() {
	return bds_social_footer_po( 'https://affiliates.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=affiliate_join' );
}

/**
 * Modest converting line for the visible .bd-footer (does not compete with Find my service).
 *
 * @return string
 */
function bds_social_aff_line_html() {
	$url = esc_url( bds_social_aff_join_url() );
	return '<p class="bds-chrome-aff">Refer businesses to the Directory and earn commission. <a href="' . $url . '">Become an affiliate</a></p>';
}

/**
 * Named converting doors for the visible .bd-footer (lock footer stays hidden when chrome exists).
 *
 * @return string
 */
function bds_social_eco_html() {
	$dir = esc_url( bds_social_footer_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=directory_hub' ) );
	$co  = esc_url( bds_social_footer_po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=footer' ) );
	$ht  = esc_url( bds_social_footer_po( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=footer' ) );
	$html  = '<div class="bds-chrome-eco" id="bdsChromeEco">';
	$html .= '<p class="bds-chrome-eco-kicker">Also in the BrandDad family</p>';
	$html .= '<div class="bds-chrome-eco-grid">';
	$html .= '<a href="' . $dir . '"><strong>Directory</strong><span>List or claim. Get found on WhatsApp. Members save 10%.</span></a>';
	$html .= '<a href="' . $co . '"><strong>BrandDad.co</strong><span>Websites and logos — get started.</span></a>';
	$html .= '<a href="' . $ht . '"><strong>HostTech</strong><span>Hosting and domains for your site.</span></a>';
	$html .= '</div></div>';
	return $html;
}

/**
 * Canonical public WhatsApp chat URL (chrome / footer / MU).
 *
 * @return string
 */
function bds_social_wa_href() {
	return BDS_SOCIAL_WA_HREF;
}

/**
 * WhatsApp chat link — converting CTA, not a tel: 800 citation.
 *
 * @param string $label Link text.
 * @param string $class Optional class attr.
 * @return string
 */
function bds_social_wa_link_html( $label, $class = '' ) {
	$cls = ( $class !== '' ) ? ' class="' . esc_attr( $class ) . '"' : '';
	return '<a' . $cls . ' href="' . esc_url( bds_social_wa_href() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a>';
}

/**
 * NAP contact line shown in footers (street stays; phone is WhatsApp).
 *
 * @return string
 */
function bds_social_nap_contact_html() {
	return bds_social_wa_link_html( BDS_SOCIAL_WA_CTA . ' · ' . BDS_SOCIAL_NAP_PHONE_DISPLAY );
}

/**
 * Official public NAP block — same strings as JSON-LD. No suite / WeWork claim.
 *
 * @return string
 */
function bds_social_chrome_nap_html() {
	return '<address class="bds-chrome-nap">'
		. esc_html( BDS_SOCIAL_NAP_NAME ) . '<br>'
		. esc_html( BDS_SOCIAL_NAP_STREET ) . '<br>'
		. esc_html( BDS_SOCIAL_NAP_CITY . ', ' . BDS_SOCIAL_NAP_STATE . ' ' . BDS_SOCIAL_NAP_ZIP ) . '<br>'
		. bds_social_nap_contact_html()
		. '</address>';
}

add_action(
	'init',
	static function () {
		if ( ! bds_social_footer_lock_is_host() ) {
			return;
		}
		// Prefer this lock over chrome duplicate footer.
		remove_action( 'wp_footer', array( 'BDS_Site_Chrome', 'footer_markup' ), 3 );
		remove_action( 'wp_footer', array( 'BDS_Site_Chrome', 'footer_place_js' ), 99 );
	},
	30
);

add_action( 'wp_head', 'bds_social_footer_lock_css', 3 );
add_action( 'wp_head', 'bds_social_footer_lock_schema', 8 );
add_action( 'wp_footer', 'bds_social_footer_lock_markup', 2 );
add_action( 'wp_footer', 'bds_social_footer_lock_place_js', 99 );
add_action( 'template_redirect', 'bds_social_footer_lock_buffer_start', -60 );

/**
 * Official sameAs only — URLs proven from the visible footer (not typos / unproven handles).
 *
 * @return array<int,string>
 */
function bds_social_schema_sameas() {
	return array(
		'https://www.facebook.com/branddad.social',
		'https://www.instagram.com/branddadsocial/',
		'https://www.linkedin.com/company/branddadsocial/',
		'https://www.youtube.com/@branddadmedia',
		'https://x.com/branddadtweets',
	);
}

/**
 * Shared PostalAddress — same values as the crawlable footer NAP.
 *
 * @return array<string,mixed>
 */
function bds_social_schema_address_node() {
	return array(
		'@type'           => 'PostalAddress',
		'@id'             => BDS_SOCIAL_SCHEMA_ADDRESS_ID,
		'streetAddress'   => BDS_SOCIAL_NAP_STREET,
		'addressLocality' => BDS_SOCIAL_NAP_CITY,
		'addressRegion'   => BDS_SOCIAL_NAP_STATE,
		'postalCode'      => BDS_SOCIAL_NAP_ZIP,
		'addressCountry'  => 'US',
	);
}

/**
 * Homepage goal-card services only — copy + HTTP 200 URLs from the live homepage.
 * Offers omit price: SKUs are ranges, homepage shows no price.
 *
 * @return array<int,array<string,string>>
 */
function bds_social_schema_home_service_defs() {
	return array(
		array(
			'service_id'  => 'https://branddad.social/growth-systems/#service',
			'offer_id'    => 'https://branddad.social/growth-systems/#offer',
			'name'        => 'Grow social visibility',
			'description' => 'Improve discovery, engagement and consistency across the platforms that matter to your audience.',
			'url'         => 'https://branddad.social/growth-systems/',
			'serviceType' => 'Social growth',
		),
		array(
			'service_id'  => 'https://branddad.social/product/comprehensive-seo-packages-rank-1-on-google/#service',
			'offer_id'    => 'https://branddad.social/product/comprehensive-seo-packages-rank-1-on-google/#offer',
			'name'        => 'Increase search traffic',
			'description' => 'Strengthen your website and local presence so customers can find you when they search.',
			'url'         => 'https://branddad.social/product/comprehensive-seo-packages-rank-1-on-google/',
			'serviceType' => 'SEO & search',
		),
		array(
			'service_id'  => 'https://branddad.social/product/press-release-services/#service',
			'offer_id'    => 'https://branddad.social/product/press-release-services/#offer',
			'name'        => 'Build authority',
			'description' => 'Create credible public signals through PR, professional profiles and high-visibility campaigns.',
			'url'         => 'https://branddad.social/product/press-release-services/',
			'serviceType' => 'PR & authority',
		),
		array(
			'service_id'  => 'https://branddad.social/product/remove-negative-google-reviews/#service',
			'offer_id'    => 'https://branddad.social/product/remove-negative-google-reviews/#offer',
			'name'        => 'Protect reputation',
			'description' => 'Address credibility problems that make prospective customers hesitate or walk away.',
			'url'         => 'https://branddad.social/product/remove-negative-google-reviews/',
			'serviceType' => 'Reputation',
		),
	);
}

/**
 * @return array<int,array<string,mixed>>
 */
function bds_social_schema_home_services() {
	$provider = array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID );
	$out      = array();
	foreach ( bds_social_schema_home_service_defs() as $def ) {
		$out[] = array(
			'@type'            => 'Service',
			'@id'              => $def['service_id'],
			'name'             => $def['name'],
			'description'      => $def['description'],
			'url'              => $def['url'],
			'serviceType'      => $def['serviceType'],
			'provider'         => $provider,
			'offers'           => array( '@id' => $def['offer_id'] ),
			'mainEntityOfPage' => $def['url'],
		);
	}
	return $out;
}

/**
 * Commercial Offers for the four homepage goal cards. No price/availability.
 *
 * @return array<int,array<string,mixed>>
 */
function bds_social_schema_home_offers() {
	$offered_by = array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID );
	$out        = array();
	foreach ( bds_social_schema_home_service_defs() as $def ) {
		$out[] = array(
			'@type'       => 'Offer',
			'@id'         => $def['offer_id'],
			'url'         => $def['url'],
			'itemOffered' => array( '@id' => $def['service_id'] ),
			'offeredBy'   => $offered_by,
		);
	}
	return $out;
}

/**
 * Logo ImageObject — BrandDad Social 3-1-1.png, HTTPS, stable @id (not a hash / Bing leftover).
 *
 * @return array<string,mixed>
 */
function bds_social_schema_logo_node() {
	return array(
		'@type'      => 'ImageObject',
		'@id'        => BDS_SOCIAL_SCHEMA_LOGO_ID,
		'url'        => BDS_SOCIAL_SCHEMA_LOGO,
		'contentUrl' => BDS_SOCIAL_SCHEMA_LOGO,
		'caption'    => BDS_SOCIAL_SCHEMA_BRAND,
	);
}

/**
 * One BrandDad entity: Organization. Not LocalBusiness — WeWork, no walk-in storefront.
 * Public NAP (address + WhatsApp telephone) stays on this same @id. No geo/hours/priceRange.
 *
 * @return array<string,mixed>
 */
function bds_social_schema_org_node() {
	return array(
		'@type'         => 'Organization',
		'@id'           => BDS_SOCIAL_SCHEMA_ORG_ID,
		'name'          => BDS_SOCIAL_SCHEMA_BRAND,
		'alternateName' => BDS_SOCIAL_NAP_NAME,
		'url'           => BDS_SOCIAL_SCHEMA_HOME,
		'telephone'     => '+' . BDS_SOCIAL_NAP_PHONE_RAW,
		'contactPoint'  => array(
			'@type'       => 'ContactPoint',
			'contactType' => 'customer support',
			'telephone'   => '+' . BDS_SOCIAL_NAP_PHONE_RAW,
			'url'         => 'https://wa.me/18729105115',
		),
		'email'         => BDS_SOCIAL_SCHEMA_EMAIL,
		'description'   => 'BrandDad Social — growth systems, PR, SEO, and WhatsApp Business Directory.',
		'logo'          => array( '@id' => BDS_SOCIAL_SCHEMA_LOGO_ID ),
		'image'         => array( '@id' => BDS_SOCIAL_SCHEMA_LOGO_ID ),
		'address'       => bds_social_schema_address_node(),
		'sameAs'        => bds_social_schema_sameas(),
	);
}

/**
 * @return bool
 */
function bds_social_schema_is_home_request() {
	if ( function_exists( 'is_front_page' ) && is_front_page() ) {
		return true;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = (string) wp_parse_url( 'https://branddad.social' . $uri, PHP_URL_PATH );
	return ( $path === '' || $path === '/' || $path === '/index.php' );
}

/**
 * Collapse Squirrly's per-page Organization/WebSite fragments to the sitewide @ids.
 *
 * @param mixed $id ID.
 * @return mixed
 */
function bds_social_schema_normalize_id( $id ) {
	if ( ! is_string( $id ) || $id === '' ) {
		return $id;
	}
	// Use ~ delimiters — the patterns contain # fragments (#organization).
	if ( preg_match( '~^https://(?:www\.)?branddad\.social(?:/[^#]*)?#organization$~i', $id ) ) {
		return BDS_SOCIAL_SCHEMA_ORG_ID;
	}
	if ( preg_match( '~^https://(?:www\.)?branddad\.social(?:/[^#]*)?#WebSite$~i', $id ) ) {
		return BDS_SOCIAL_SCHEMA_WEBSITE_ID;
	}
	if ( preg_match( '~^https://(?:www\.)?branddad\.social/?#WebPage$~i', $id ) ) {
		return BDS_SOCIAL_SCHEMA_WEBPAGE_ID;
	}
	return $id;
}

/**
 * @param mixed $node Node.
 * @return array<int,string>
 */
function bds_social_schema_types( $node ) {
	if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
		return array();
	}
	$t = $node['@type'];
	if ( ! is_array( $t ) ) {
		$t = array( $t );
	}
	$out = array();
	foreach ( $t as $one ) {
		if ( is_string( $one ) && $one !== '' ) {
			$out[] = $one;
		}
	}
	return $out;
}

/**
 * @param array<string,mixed> $node Node.
 * @param string             $want Type.
 * @return bool
 */
function bds_social_schema_is_type( $node, $want ) {
	foreach ( bds_social_schema_types( $node ) as $t ) {
		if ( 0 === strcasecmp( $t, $want ) ) {
			return true;
		}
	}
	return false;
}

/**
 * @param mixed $decoded Decoded JSON-LD.
 * @return array<int,array<string,mixed>>
 */
function bds_social_schema_collect_nodes( $decoded ) {
	$nodes = array();
	if ( ! is_array( $decoded ) ) {
		return $nodes;
	}
	if ( isset( $decoded['@graph'] ) && is_array( $decoded['@graph'] ) ) {
		foreach ( $decoded['@graph'] as $n ) {
			if ( is_array( $n ) ) {
				$nodes[] = $n;
			}
		}
	} elseif ( isset( $decoded['@type'] ) ) {
		$nodes[] = $decoded;
	}
	return $nodes;
}

/**
 * Walk arrays and normalize @id strings.
 *
 * @param mixed $data Data.
 * @return mixed
 */
function bds_social_schema_walk_ids( $data ) {
	if ( is_array( $data ) ) {
		$out = array();
		foreach ( $data as $k => $v ) {
			if ( $k === '@id' && is_string( $v ) ) {
				$out[ $k ] = bds_social_schema_normalize_id( $v );
			} else {
				$out[ $k ] = bds_social_schema_walk_ids( $v );
			}
		}
		return $out;
	}
	return $data;
}

/**
 * Real WP search exists at /?s= — keep SearchAction, use Schema.org-valid query-input.
 *
 * @return array<string,mixed>
 */
function bds_social_schema_search_action() {
	return array(
		'@type'        => 'SearchAction',
		'target'       => array(
			'@type'       => 'EntryPoint',
			'urlTemplate' => 'https://branddad.social/?s={search_term_string}',
		),
		'query-input'  => array(
			'@type'         => 'PropertyValueSpecification',
			'valueRequired' => true,
			'valueName'     => 'search_term_string',
		),
	);
}

/**
 * Merge Squirrly graph + NAP into one entity graph. No FAQ/HowTo/Review spam.
 *
 * @param array<int,array<string,mixed>> $nodes Nodes.
 * @param bool                           $is_home Homepage.
 * @param string                         $page_url Canonical page URL.
 * @param string                         $page_name Page name.
 * @return array<string,mixed>
 */
function bds_social_schema_build_graph( $nodes, $is_home, $page_url, $page_name ) {
	$org     = bds_social_schema_org_node();
	$logo    = bds_social_schema_logo_node();
	$website = array(
		'@type'     => 'WebSite',
		'@id'       => BDS_SOCIAL_SCHEMA_WEBSITE_ID,
		'url'       => BDS_SOCIAL_SCHEMA_HOME,
		'name'      => BDS_SOCIAL_SCHEMA_BRAND,
		'publisher' => array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID ),
		'inLanguage' => 'en-US',
	);
	if ( $is_home ) {
		$website['potentialAction'] = bds_social_schema_search_action();
	}

	$webpage_id = $is_home ? BDS_SOCIAL_SCHEMA_WEBPAGE_ID : rtrim( $page_url, '/' ) . '#WebPage';
	$webpage    = array(
		'@type'      => 'WebPage',
		'@id'        => $webpage_id,
		'url'        => $page_url,
		'name'       => ( $page_name !== '' ? $page_name : BDS_SOCIAL_SCHEMA_BRAND ),
		'isPartOf'   => array( '@id' => BDS_SOCIAL_SCHEMA_WEBSITE_ID ),
		'about'      => array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID ),
		'publisher'  => array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID ),
		'inLanguage' => 'en-US',
	);
	if ( $is_home ) {
		$webpage['primaryImageOfPage'] = array( '@id' => BDS_SOCIAL_SCHEMA_LOGO_ID );
		$webpage['mainEntity']         = array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID );
	}

	$keep = array();
	foreach ( $nodes as $node ) {
		$node = bds_social_schema_walk_ids( $node );
		if ( bds_social_schema_is_type( $node, 'Organization' ) || bds_social_schema_is_type( $node, 'LocalBusiness' ) ) {
			if ( ! empty( $node['description'] ) && is_string( $node['description'] ) ) {
				$org['description'] = $node['description'];
			}
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'WebSite' ) ) {
			if ( ! empty( $node['datePublished'] ) ) {
				$webpage['datePublished'] = $node['datePublished'];
			}
			if ( ! empty( $node['dateModified'] ) ) {
				$webpage['dateModified'] = $node['dateModified'];
			}
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'WebPage' ) ) {
			if ( ! empty( $node['datePublished'] ) ) {
				$webpage['datePublished'] = $node['datePublished'];
			}
			if ( ! empty( $node['dateModified'] ) ) {
				$webpage['dateModified'] = $node['dateModified'];
			}
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'ImageObject' ) ) {
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'Person' ) ) {
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'SearchAction' ) ) {
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'PostalAddress' ) ) {
			continue;
		}
		if ( bds_social_schema_is_type( $node, 'GeoCoordinates' ) ) {
			continue;
		}
		if ( $is_home && bds_social_schema_is_type( $node, 'Service' ) ) {
			continue;
		}
		if ( $is_home && bds_social_schema_is_type( $node, 'Product' ) ) {
			continue;
		}
		if ( $is_home && bds_social_schema_is_type( $node, 'Offer' ) ) {
			continue;
		}
		if ( $is_home && bds_social_schema_is_type( $node, 'OfferCatalog' ) ) {
			continue;
		}
		if ( $is_home && bds_social_schema_is_type( $node, 'AggregateOffer' ) ) {
			continue;
		}
		if ( $is_home && ( bds_social_schema_is_type( $node, 'FAQPage' ) || bds_social_schema_is_type( $node, 'Question' ) || bds_social_schema_is_type( $node, 'Answer' ) ) ) {
			continue;
		}
		$keep[] = $node;
	}

	if ( $is_home ) {
		$website['mainEntityOfPage'] = array( '@id' => BDS_SOCIAL_SCHEMA_WEBPAGE_ID );
		$org['mainEntityOfPage']     = array( '@id' => BDS_SOCIAL_SCHEMA_WEBPAGE_ID );
		$org['makesOffer']           = array();
		foreach ( bds_social_schema_home_offers() as $offer ) {
			$oid = isset( $offer['@id'] ) ? (string) $offer['@id'] : '';
			if ( $oid !== '' ) {
				$org['makesOffer'][] = array( '@id' => $oid );
			}
		}
	}

	$graph   = array( $org, $logo, $website, $webpage );
	$seen_id = array(
		BDS_SOCIAL_SCHEMA_ORG_ID     => true,
		BDS_SOCIAL_SCHEMA_LOGO_ID    => true,
		BDS_SOCIAL_SCHEMA_WEBSITE_ID => true,
		$webpage_id                  => true,
	);
	if ( $is_home ) {
		foreach ( bds_social_schema_home_services() as $svc ) {
			$sid = isset( $svc['@id'] ) ? (string) $svc['@id'] : '';
			if ( $sid !== '' ) {
				$seen_id[ $sid ] = true;
			}
			$graph[] = $svc;
		}
		foreach ( bds_social_schema_home_offers() as $offer ) {
			$oid = isset( $offer['@id'] ) ? (string) $offer['@id'] : '';
			if ( $oid !== '' ) {
				$seen_id[ $oid ] = true;
			}
			$graph[] = $offer;
		}
	}
	foreach ( $keep as $extra ) {
		$eid = isset( $extra['@id'] ) ? (string) $extra['@id'] : '';
		if ( $eid !== '' && isset( $seen_id[ $eid ] ) ) {
			continue;
		}
		if ( $eid !== '' ) {
			$seen_id[ $eid ] = true;
		}
		$graph[] = $extra;
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
	if ( function_exists( 'apply_filters' ) ) {
		/**
		 * Append/adjust graph nodes (FAQPage, Service/Offer) without a second JSON-LD script.
		 *
		 * @param array<string,mixed> $schema  Graph.
		 * @param bool                $is_home Homepage.
		 * @param string              $page_url Canonical URL.
		 */
		$filtered = apply_filters( 'bds_social_schema_graph', $schema, $is_home, $page_url );
		if ( is_array( $filtered ) && isset( $filtered['@graph'] ) && is_array( $filtered['@graph'] ) ) {
			$schema = $filtered;
		}
	}
	return $schema;
}

/**
 * Visible homepage Q&A headings only — must match on-page H2/H3 text.
 *
 * @return array<int,string>
 */
function bds_social_schema_home_faq_headings() {
	return array(
		'What we do here',
		'How to reach us',
		'What you get',
		'What it is not',
		'Need to be found by the right people',
		'Need search, PR or reputation help',
		'Need a logo, site or hosting',
		'Not sure which service fits?',
	);
}

/**
 * @param string $html Fragment.
 * @return string
 */
function bds_social_schema_plain_answer( $html ) {
	$text = wp_strip_all_tags( (string) $html );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( (string) $text );
}

/**
 * Pull FAQ pairs from rendered homepage HTML so schema matches visible answers.
 *
 * @param string $html Full HTML.
 * @return array<int,array{q:string,a:string}>
 */
function bds_social_schema_extract_home_faq( $html ) {
	$items = array();
	if ( ! is_string( $html ) || $html === '' ) {
		return $items;
	}
	foreach ( bds_social_schema_home_faq_headings() as $name ) {
		$q = preg_quote( $name, '~' );
		if ( ! preg_match( '~<h[23][^>]*>\s*' . $q . '\s*</h[23]>\s*<p\b[^>]*>(.*?)</p>~is', $html, $m ) ) {
			continue;
		}
		$text = bds_social_schema_plain_answer( $m[1] );
		if ( $text === '' ) {
			continue;
		}
		$items[] = array(
			'q' => $name,
			'a' => $text,
		);
	}
	return $items;
}

/**
 * Add or replace one FAQPage node in the existing graph. Does not change WebPage.mainEntity.
 *
 * @param array<string,mixed> $schema Graph.
 * @param string              $html   Rendered HTML.
 * @return array<string,mixed>
 */
function bds_social_schema_append_home_faqpage( $schema, $html ) {
	if ( ! is_array( $schema ) || empty( $schema['@graph'] ) || ! is_array( $schema['@graph'] ) ) {
		return $schema;
	}
	$items = bds_social_schema_extract_home_faq( $html );
	if ( count( $items ) < 1 ) {
		return $schema;
	}
	$faq_id = rtrim( BDS_SOCIAL_SCHEMA_HOME, '/' ) . '/#faq';
	$main   = array();
	$i      = 1;
	foreach ( $items as $item ) {
		$main[] = array(
			'@type'          => 'Question',
			'@id'            => $faq_id . '-q' . $i,
			'name'           => $item['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['a'],
			),
		);
		++$i;
	}
	$faq = array(
		'@type'      => 'FAQPage',
		'@id'        => $faq_id,
		'url'        => BDS_SOCIAL_SCHEMA_HOME,
		'inLanguage' => 'en-US',
		'isPartOf'   => array( '@id' => BDS_SOCIAL_SCHEMA_WEBPAGE_ID ),
		'about'      => array( '@id' => BDS_SOCIAL_SCHEMA_ORG_ID ),
		'mainEntity' => $main,
	);
	$out       = array();
	$replaced  = false;
	foreach ( $schema['@graph'] as $node ) {
		if ( is_array( $node ) && bds_social_schema_is_type( $node, 'FAQPage' ) ) {
			$out[]     = $faq;
			$replaced  = true;
			continue;
		}
		if ( is_array( $node ) && bds_social_schema_is_type( $node, 'Question' ) ) {
			continue;
		}
		$out[] = $node;
	}
	if ( ! $replaced ) {
		$out[] = $faq;
	}
	$schema['@graph'] = $out;
	return $schema;
}

/**
 * Organization JSON-LD fallback (same @id as the merged graph) if the buffer cannot merge Squirrly.
 */
function bds_social_footer_lock_schema() {
	if ( ! bds_social_footer_lock_should_run() ) {
		return;
	}
	$schema           = bds_social_schema_org_node();
	$schema['@context'] = 'https://schema.org';
	echo '<script type="application/ld+json" id="bds-social-nap-schema">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}

/**
 * CSS: kill competitors + pin footer to page bottom.
 */
function bds_social_footer_lock_css() {
	if ( ! bds_social_footer_lock_should_run() ) {
		return;
	}
	$ver = esc_attr( BDS_SOCIAL_FOOTER_LOCK_MU_VER );
	echo '<style id="bds-social-footer-lock-css" data-bds-footer-lock="' . $ver . '">'
		. 'footer.site-footer:not(#bdsSocialFooterLock),'
		. '.elementor-location-footer,'
		. 'footer.elementor-location-footer,'
		. '#colophon:not(#bdsSocialFooterLock),'
		. '.bds-site-footer:not(#bdsSocialFooterLock),'
		. '.bds-unified-footer,'
		. '#bdNetworkPromoFooter,'
		. '#bdsDirFooterLock{display:none!important;height:0!important;max-height:0!important;overflow:hidden!important;margin:0!important;padding:0!important;border:0!important;visibility:hidden!important;pointer-events:none!important}'
		. 'html{height:100%}'
		. 'body{min-height:100vh;min-height:100dvh;display:flex!important;flex-direction:column!important}'
		. 'body > #page, body > .site, body > #wrapper, body > .elementor, body > #content, body > main{flex:1 0 auto;width:100%}'
		. '#bdsSocialFooterLock{'
		. 'display:block!important;visibility:visible!important;pointer-events:auto!important;'
		. 'position:relative!important;left:auto!important;right:auto!important;bottom:auto!important;top:auto!important;'
		. 'float:none!important;clear:both!important;width:100%!important;max-width:none!important;'
		. 'margin:auto 0 0 0!important;margin-top:auto!important;flex:0 0 auto!important;order:9999!important;'
		. 'z-index:40;background:linear-gradient(180deg,#0b1220,#070b14);color:#e2e8f0;'
		. 'border-top:1px solid rgba(148,163,184,.18);font-family:Manrope,Syne,"Segoe UI",sans-serif'
		. '}'
		. '#bdsSocialFooterLock a{color:#cbd5e1;text-decoration:none}'
		. '#bdsSocialFooterLock a:hover{color:#fff}'
		. '#bdsSocialFooterLock .bds-sfl-inner{max-width:1100px;margin:0 auto;padding:48px 22px 24px;display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:28px}'
		. '#bdsSocialFooterLock h3{margin:0 0 12px;color:#fff;font-size:13px;letter-spacing:.06em;text-transform:uppercase;font-weight:800}'
		. '#bdsSocialFooterLock p{margin:0 0 12px;color:#94a3b8;line-height:1.6;max-width:340px}'
		. '#bdsSocialFooterLock ul{list-style:none;margin:0;padding:0;display:grid;gap:10px}'
		. '#bdsSocialFooterLock .bds-sfl-social{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}'
		. '#bdsSocialFooterLock .bds-sfl-social a{width:36px;height:36px;border-radius:999px;border:1px solid rgba(226,232,240,.28);display:grid;place-items:center;color:#fff;font-size:12px;font-weight:800}'
		. '#bdsSocialFooterLock .bds-sfl-nap{font-style:normal;display:block;margin:0 0 12px;color:#94a3b8;line-height:1.6;max-width:340px}'
		. '#bdsSocialFooterLock .bds-sfl-aff{max-width:1100px;margin:0 auto;padding:0 22px 16px;color:#cbd5e1;font-size:14px;line-height:1.55}'
		. '#bdsSocialFooterLock .bds-sfl-aff a{color:#93c5fd;font-weight:800}'
		. '#bdsSocialFooterLock .bds-sfl-bottom{max-width:1100px;margin:0 auto;padding:14px 22px 28px;border-top:1px solid rgba(148,163,184,.16);display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;color:#94a3b8;font-size:13px}'
		. '#bdsSocialFooterLock .bds-sfl-bottom a{color:#93c5fd;font-weight:700}'
		. '@media (max-width:800px){#bdsSocialFooterLock .bds-sfl-inner{grid-template-columns:1fr 1fr}}'
		. '@media (max-width:520px){#bdsSocialFooterLock .bds-sfl-inner{grid-template-columns:1fr}}'
		/* Visible chrome footer: official NAP + heading contrast (explicit bugfix; do not restyle the site). */
		. '.bd-footer h3{color:#b7c1d2!important}'
		. '.bd-footer .bds-chrome-nap{font-style:normal;display:block;margin:0 0 18px;color:#b7c1d2!important;line-height:1.65;max-width:340px}'
		. '.bd-footer .bds-chrome-nap a{display:inline!important;padding:0!important;color:#d5deeb!important}'
		. '.bd-footer .bds-chrome-aff{margin:0 0 12px;color:#b7c1d2;font-size:14px;line-height:1.5}'
		. '.bd-footer .bds-chrome-aff a{color:#93c5fd!important;font-weight:800;text-decoration:none}'
		. '.bd-footer-bottom .bds-chrome-aff{margin:0}'
		. '.bd-footer .bds-chrome-eco{margin:28px 0 0;padding:22px 0 8px;border-top:1px solid #2a3851}'
		. '.bd-footer .bds-chrome-eco-kicker{margin:0 0 12px;color:#8fc5ff;font-size:13px;letter-spacing:.1em;text-transform:uppercase;font-weight:800}'
		. '.bd-footer .bds-chrome-eco-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}'
		. '.bd-footer .bds-chrome-eco-grid a{display:block!important;padding:14px 16px!important;border:1px solid #2a3851;border-radius:10px;background:#0a1528;color:#d5deeb!important;text-decoration:none;line-height:1.4}'
		. '.bd-footer .bds-chrome-eco-grid a:hover{border-color:#3BA3FF;color:#fff!important}'
		. '.bd-footer .bds-chrome-eco-grid strong{display:block;color:#fff;font-size:15px;margin-bottom:4px}'
		. '.bd-footer .bds-chrome-eco-grid span{display:block;color:#b7c1d2;font-size:13px;line-height:1.45;font-weight:500}'
		. '@media(max-width:800px){.bd-footer .bds-chrome-eco-grid{grid-template-columns:1fr}}'
		. '</style>';
}

/**
 * Locked footer markup (clean — no emoji promo).
 */
function bds_social_footer_lock_markup() {
	if ( ! bds_social_footer_lock_should_run() ) {
		return;
	}
	$year = esc_html( gmdate( 'Y' ) );
	$po   = 'bds_social_footer_po';
	?>
	<!-- BDS_SOCIAL_FOOTER_LOCK_MU v<?php echo esc_html( BDS_SOCIAL_FOOTER_LOCK_MU_VER ); ?> -->
	<footer id="bdsSocialFooterLock" class="bds-site-footer bds-social-footer-lock" role="contentinfo" data-bds-footer-lock="<?php echo esc_attr( BDS_SOCIAL_FOOTER_LOCK_MU_VER ); ?>">
		<div class="bds-sfl-inner">
			<div>
				<h3>BrandDad Social</h3>
				<p>Focused marketing systems for visibility, reputation, and growth — plus Directory member savings across the BrandDad network.</p>
				<div class="bds-sfl-social" aria-label="BrandDad social profiles">
					<a href="https://www.facebook.com/branddad.social" target="_blank" rel="noopener" aria-label="Facebook">Fb</a>
					<a href="https://www.instagram.com/branddadsocial/" target="_blank" rel="noopener" aria-label="Instagram">Ig</a>
					<a href="https://www.youtube.com/@branddadmedia" target="_blank" rel="noopener" aria-label="YouTube">Yt</a>
					<a href="https://www.linkedin.com/company/branddadsocial/" target="_blank" rel="noopener" aria-label="LinkedIn">In</a>
				</div>
			</div>
			<div>
				<h3>Quick links</h3>
				<ul>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/' ) ); ?>">Home</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/services/' ) ); ?>">Services</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/check-your-website/' ) ); ?>">Check your website</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/product/linkedin-visibility-amplification-system-for-professionals/' ) ); ?>">LinkedIn Visibility</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/learning-center/' ) ); ?>">Learning Center</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/guides/' ) ); ?>">Guides</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/courses/' ) ); ?>">Courses</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/shop/' ) ); ?>">All products</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/cart/' ) ); ?>">Cart</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/my-account/' ) ); ?>">Account</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/blog/' ) ); ?>">Blog</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/about-us/' ) ); ?>">About</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/hire-branddad/' ) ); ?>">Hire BrandDad</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/contact-us/' ) ); ?>">Contact</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.social/feedback/' ) ); ?>" data-bds-fb-link="1">Feedback &amp; roadmap</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://affiliates.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=affiliate_join' ) ); ?>">Become an affiliate</a></li>
				</ul>
			</div>
			<div>
				<h3>BrandDad family</h3>
				<ul>
					<li><a href="<?php echo esc_url( $po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=directory_hub' ) ); ?>">Directory — list, claim, WhatsApp, 10% off</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://directory.branddad.social/registration/?redirect_to=https%3A%2F%2Fdirectory.branddad.social%2Fadd-listing%2F&utm_source=branddad_social&utm_medium=footer' ) ); ?>">List your business</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=footer' ) ); ?>">BrandDad.co — websites &amp; logos</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=footer' ) ); ?>">HostTech — hosting &amp; domains</a></li>
					<li><a href="<?php echo esc_url( $po( 'https://affiliates.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=affiliate_join' ) ); ?>">Become an affiliate</a></li>
					<li><a href="<?php echo esc_url( BDS_SOCIAL_WA_HREF ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp — primary contact</a></li>
				</ul>
			</div>
			<div>
				<h3>Contact</h3>
				<address class="bds-sfl-nap">
					<?php echo esc_html( BDS_SOCIAL_NAP_NAME ); ?><br>
					<?php echo esc_html( BDS_SOCIAL_NAP_STREET ); ?><br>
					<?php echo esc_html( BDS_SOCIAL_NAP_CITY . ', ' . BDS_SOCIAL_NAP_STATE . ' ' . BDS_SOCIAL_NAP_ZIP ); ?><br>
					<?php echo bds_social_nap_contact_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</address>
				<ul>
					<li><a href="mailto:hello@branddad.social">hello@branddad.social</a></li>
					<li><a href="https://branddad.dayschedule.com/bdsbooking" target="_blank" rel="noopener">Book a call</a></li>
					<li><a href="https://branddad.social/privacy-policy/">Privacy</a></li>
					<li><a href="https://branddad.social/terms-conditions/">Terms</a></li>
				</ul>
			</div>
		</div>
		<p class="bds-sfl-aff">Refer businesses to the Directory and earn commission. <a href="<?php echo esc_url( $po( 'https://affiliates.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=affiliate_join' ) ); ?>">Become an affiliate</a></p>
		<div class="bds-sfl-bottom">
			<span>© <?php echo $year; ?> BrandDad Social</span>
			<span>Directory members save 10% on eligible services. <a href="<?php echo esc_url( $po( 'https://directory.branddad.social/registration/?utm_source=branddad_social&utm_medium=footer&utm_campaign=join' ) ); ?>">Join the Directory</a></span>
		</div>
	</footer>
	<?php
}

/**
 * Force footer to the absolute end of document.body.
 */
function bds_social_footer_lock_place_js() {
	if ( ! bds_social_footer_lock_should_run() ) {
		return;
	}
	?>
	<script id="bds-social-footer-lock-place">
	(function(){
	  var NAP_HTML=<?php echo wp_json_encode( bds_social_chrome_nap_html() ); ?>;
	  var NAP_STREET=<?php echo wp_json_encode( BDS_SOCIAL_NAP_STREET ); ?>;
	  var AFF_HTML=<?php echo wp_json_encode( bds_social_aff_line_html() ); ?>;
	  var AFF_HREF=<?php echo wp_json_encode( bds_social_aff_join_url() ); ?>;
	  var ECO_HTML=<?php echo wp_json_encode( bds_social_eco_html() ); ?>;
	  var ECO_DIR=<?php echo wp_json_encode( bds_social_footer_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=directory_hub' ) ); ?>;
	  var ECO_CO=<?php echo wp_json_encode( bds_social_footer_po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=footer' ) ); ?>;
	  var ECO_HT=<?php echo wp_json_encode( bds_social_footer_po( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=footer' ) ); ?>;
	  function paintChromeNap(){
	    var foots=document.querySelectorAll('.bd-footer');
	    if(!foots.length) return;
	    for(var i=0;i<foots.length;i++){
	      var foot=foots[i];
	      var wrap=document.createElement('div');
	      wrap.innerHTML=NAP_HTML;
	      var node=wrap.firstChild;
	      if(!node) continue;
	      var existing=foot.querySelector('.bds-chrome-nap');
	      if(existing){
	        existing.parentNode.replaceChild(node, existing);
	        continue;
	      }
	      if((foot.textContent||'').indexOf(NAP_STREET)!==-1 && (foot.textContent||'').indexOf('1-800-464-2513')===-1) continue;
	      var p=foot.querySelector('.bd-footer-grid > div p')||foot.querySelector('p');
	      if(p&&p.parentNode){
	        if(p.nextSibling) p.parentNode.insertBefore(node,p.nextSibling);
	        else p.parentNode.appendChild(node);
	      } else {
	        var inner=foot.querySelector('.bd-footer-inner')||foot;
	        inner.insertBefore(node, inner.firstChild);
	      }
	    }
	  }
	  function paintChromeAff(){
	    var foots=document.querySelectorAll('.bd-footer');
	    if(!foots.length) return;
	    for(var i=0;i<foots.length;i++){
	      var foot=foots[i];
	      var company=null;
	      var hs=foot.querySelectorAll('h4');
	      for(var h=0;h<hs.length;h++){
	        if(/^Company$/i.test((hs[h].textContent||'').trim())){ company=hs[h].parentNode; break; }
	      }
	      if(company && !company.querySelector('a[href*="affiliates.branddad.social"]')){
	        var a=document.createElement('a');
	        a.href=AFF_HREF;
	        a.textContent='Become an affiliate';
	        company.appendChild(a);
	      }
	      if(!foot.querySelector('.bds-chrome-aff')){
	        var bottom=foot.querySelector('.bd-footer-bottom');
	        if(bottom && bottom.parentNode){
	          var wrap=document.createElement('div');
	          wrap.innerHTML=AFF_HTML;
	          var node=wrap.firstChild;
	          if(node) bottom.parentNode.insertBefore(node, bottom);
	        }
	      }
	    }
	  }
	  function addCompanyDoor(company, href, label){
	    if(!company) return;
	    var a=document.createElement('a');
	    a.href=href;
	    a.textContent=label;
	    if(String(href).indexOf('wa.me/')!==-1){
	      a.target='_blank';
	      a.rel='noopener noreferrer';
	    }
	    company.appendChild(a);
	  }
	  function ensureWaBlank(root){
	    if(!root) return;
	    var links=root.querySelectorAll('a[href*="wa.me/"]');
	    for(var i=0;i<links.length;i++){
	      if(!links[i].target) links[i].target='_blank';
	      if(!links[i].rel) links[i].rel='noopener noreferrer';
	    }
	  }
	  function scrubFooterTelegram(foot){
	    if(!foot) return;
	    var links=foot.querySelectorAll('a[href*="t.me/"],a[href*="telegram.me/"]');
	    for(var i=0;i<links.length;i++){
	      var a=links[i];
	      var li=a.closest?a.closest('li'):null;
	      if(li&&foot.contains(li)) li.remove();
	      else if(a.parentNode) a.parentNode.removeChild(a);
	    }
	  }
	  function paintChromeEco(){
	    var foots=document.querySelectorAll('.bd-footer');
	    if(!foots.length) return;
	    for(var i=0;i<foots.length;i++){
	      var foot=foots[i];
	      scrubFooterTelegram(foot);
	      ensureWaBlank(foot);
	      var company=null;
	      var hs=foot.querySelectorAll('h4');
	      for(var h=0;h<hs.length;h++){
	        if(/^Company$/i.test((hs[h].textContent||'').trim())){ company=hs[h].parentNode; break; }
	      }
	      if(company){
	        if(!company.querySelector('a[href*="directory.branddad.social"]')) addCompanyDoor(company, ECO_DIR, 'Directory — list, claim, 10% off');
	        if(!company.querySelector('a[href*="branddad.co"]')) addCompanyDoor(company, ECO_CO, 'BrandDad.co — websites & logos');
	        if(!company.querySelector('a[href*="hosttech.net"]')) addCompanyDoor(company, ECO_HT, 'HostTech — hosting & domains');
	        if(!company.querySelector('a[href*="wa.me/18729105115"]')) addCompanyDoor(company, <?php echo wp_json_encode( BDS_SOCIAL_WA_HREF ); ?>, 'WhatsApp — primary contact');
	      }
	      if(!foot.querySelector('.bds-chrome-eco')){
	        var bottom=foot.querySelector('.bd-footer-bottom');
	        var wrap=document.createElement('div');
	        wrap.innerHTML=ECO_HTML;
	        var node=wrap.firstChild;
	        if(node && bottom && bottom.parentNode) bottom.parentNode.insertBefore(node, bottom);
	        else if(node){
	          var inner=foot.querySelector('.bd-footer-inner')||foot;
	          inner.appendChild(node);
	        }
	      }
	    }
	  }
	  function place(){
	    var foot=document.getElementById('bdsSocialFooterLock');
	    if(!foot || !document.body) return;
	    var all=document.querySelectorAll('#bdsSocialFooterLock');
	    for(var i=1;i<all.length;i++){ try{ all[i].parentNode.removeChild(all[i]); }catch(e){} }
	    foot=document.getElementById('bdsSocialFooterLock');
	    if(!foot) return;
	    document.body.appendChild(foot);
	    paintChromeNap();
	    paintChromeAff();
	    paintChromeEco();
	    var chrome=document.querySelector('.bd-footer');
	    [].slice.call(document.querySelectorAll('footer, .elementor-location-footer, .bds-unified-footer, #colophon')).forEach(function(el){
	      if(el.id==='bdsSocialFooterLock') return;
	      var cls=(el.className||'')+'';
	      if(/blockquote-footer|testimonial/i.test(cls)) return;
	      // Conversion-shell .bd-footer is the customer-visible footer — keep it.
	      if((' '+cls+' ').indexOf(' bd-footer ')!==-1) return;
	      el.style.setProperty('display','none','important');
	      el.setAttribute('data-bds-footer-suppressed','1');
	    });
	    if(chrome){
	      foot.style.setProperty('display','none','important');
	    }
	  }
	  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', place);
	  else place();
	  setTimeout(place, 300);
	  setTimeout(place, 1000);
	  setTimeout(place, 2500);
	})();
	</script>
	<?php
}

/**
 * @param string $html HTML.
 * @return string
 */
function bds_social_footer_lock_buffer( $html ) {
	if ( ! is_string( $html ) || $html === '' || ! bds_social_footer_lock_is_host() ) {
		return is_string( $html ) ? $html : '';
	}
	$orig = $html;
	$res  = array(
		'#<footer\b[^>]*class="[^"]*elementor-location-footer[^"]*"[^>]*>.*?</footer>#is',
		'#<div\b[^>]*class="[^"]*elementor-location-footer[^"]*"[^>]*>.*?</div>#is',
		'#<footer\b(?![^>]*\bid=["\']bdsSocialFooterLock["\'])[^>]*class="[^"]*site-footer[^"]*"[^>]*>.*?</footer>#is',
		'#<footer\b[^>]*class="[^"]*bds-unified-footer[^"]*"[^>]*>.*?</footer>#is',
		'#<div\b[^>]*class="[^"]*bds-unified-footer[^"]*"[^>]*>.*?</div>#is',
	);
	foreach ( $res as $re ) {
		$next = preg_replace( $re, '', $html );
		if ( is_string( $next ) ) {
			$html = $next;
		}
	}
	$html = bds_social_align_unofficial_street( $html );
	$html = bds_social_rewrite_public_800( $html );
	$html = bds_social_inject_chrome_nap( $html );
	$html = bds_social_inject_chrome_aff( $html );
	$html = bds_social_inject_chrome_eco( $html );
	$out  = bds_social_schema_rewrite_jsonld( $html );
	return ( is_string( $out ) && $out !== '' ) ? $out : $orig;
}

/**
 * Body copy must use the same official street as schema / footer NAP.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_social_align_unofficial_street( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return is_string( $html ) ? $html : '';
	}
	$official = BDS_SOCIAL_NAP_STREET;
	$token    = '<!--BDS_NAP_STREET-->';
	// Undo a prior prefix doubling, then park the official form so shorter variants cannot rematch it.
	$html     = str_replace( '220 N. Green Streetreet', $official, $html );
	$html     = str_replace( $official, $token, $html );
	$map      = array(
		'220 N. Green St'    => $official,
		'220 N Green Street' => $official,
		'220 N Green St'     => $official,
	);
	foreach ( $map as $from => $to ) {
		if ( $from !== $to ) {
			$html = str_replace( $from, $to, $html );
		}
	}
	return str_replace( $token, $official, $html );
}

/**
 * Public surfaces: 800 is retired. WhatsApp chat CTA matches visible NAP / schema.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_social_rewrite_public_800( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return is_string( $html ) ? $html : '';
	}
	if ( false === strpos( $html, '1-800-464-2513' ) && false === strpos( $html, '18004642513' ) ) {
		return $html;
	}

	$wa = esc_url( bds_social_wa_href() );

	$html = str_replace(
		'<span>Chicago · 1-800-464-2513</span>',
		'<span>Chicago · <a class="bdh-link" href="' . $wa . '" target="_blank" rel="noopener noreferrer">Send us a message</a></span>',
		$html
	);

	$reach_new = '220 N. Green Street, Chicago, IL. <a class="bdh-link" href="' . $wa . '" target="_blank" rel="noopener noreferrer">Send us a message on WhatsApp</a> (872-910-5115). We work from a WeWork — by appointment, not a walk-in storefront.';
	$reach_old = array(
		'220 N. Green Street, Chicago, IL. Call <a class="bdh-link" href="tel:+18004642513">1-800-464-2513</a>. We work from a WeWork — by appointment, WhatsApp or phone, not a walk-in storefront.',
		'220 N Green St, Chicago, IL. Call <a class="bdh-link" href="tel:+18004642513">1-800-464-2513</a>. We work from a WeWork — by appointment, WhatsApp or phone, not a walk-in storefront.',
	);
	foreach ( $reach_old as $from ) {
		$html = str_replace( $from, $reach_new, $html );
	}

	$html = preg_replace(
		'#<a\b([^>]*)href=(["\'])tel:\+?18004642513\2([^>]*)>\s*1-800-464-2513\s*</a>#i',
		'<a href="' . $wa . '" target="_blank" rel="noopener noreferrer"$1$3>' . esc_html( BDS_SOCIAL_WA_CTA . ' · ' . BDS_SOCIAL_NAP_PHONE_DISPLAY ) . '</a>',
		$html
	);
	if ( ! is_string( $html ) ) {
		return '';
	}

	$html = str_replace( 'href="tel:+18004642513"', 'href="' . $wa . '"', $html );
	$html = str_replace( "href='tel:+18004642513'", 'href="' . $wa . '"', $html );
	$html = str_replace( '1-800-464-2513', BDS_SOCIAL_WA_CTA . ' · ' . BDS_SOCIAL_NAP_PHONE_DISPLAY, $html );
	$html = str_replace( '+18004642513', '+' . BDS_SOCIAL_NAP_PHONE_RAW, $html );

	return $html;
}

/**
 * Put official NAP into the customer-visible conversion-shell footer.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_social_inject_chrome_nap( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'bd-footer' ) ) {
		return is_string( $html ) ? $html : '';
	}
	$nap = bds_social_chrome_nap_html();
	$out = preg_replace_callback(
		'#(<footer\b[^>]*class="[^"]*\bbd-footer\b[^"]*"[^>]*>)(.*?)(</footer>)#is',
		static function ( $m ) use ( $nap ) {
			$inner = $m[2];
			if ( false !== strpos( $inner, 'bds-chrome-nap' ) ) {
				$replaced = preg_replace( '#<address class="bds-chrome-nap">.*?</address>#is', $nap, $inner, 1 );
				if ( is_string( $replaced ) ) {
					$inner = $replaced;
				}
				return $m[1] . $inner . $m[3];
			}
			if ( false !== strpos( $inner, BDS_SOCIAL_NAP_STREET ) && false === strpos( $inner, '1-800-464-2513' ) ) {
				return $m[0];
			}
			if ( preg_match( '#<p\b[^>]*>.*?</p>#is', $inner, $pm, PREG_OFFSET_CAPTURE ) ) {
				$pos   = $pm[0][1] + strlen( $pm[0][0] );
				$inner = substr( $inner, 0, $pos ) . $nap . substr( $inner, $pos );
				return $m[1] . $inner . $m[3];
			}
			if ( false !== strpos( $inner, 'bd-footer-bottom' ) ) {
				$inner = preg_replace( '#(<div class="bd-footer-bottom")#', $nap . '$1', $inner, 1 );
				return $m[1] . $inner . $m[3];
			}
			return $m[1] . $inner . $nap . $m[3];
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

/**
 * Modest Directory affiliate join on the visible conversion-shell footer.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_social_inject_chrome_aff( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'bd-footer' ) ) {
		return is_string( $html ) ? $html : '';
	}
	$line = bds_social_aff_line_html();
	$href = esc_url( bds_social_aff_join_url() );
	$out  = preg_replace_callback(
		'#(<footer\b[^>]*class="[^"]*\bbd-footer\b[^"]*"[^>]*>)(.*?)(</footer>)#is',
		static function ( $m ) use ( $line, $href ) {
			$inner = $m[2];
			if ( false === strpos( $inner, 'affiliates.branddad.social' ) ) {
				$inner = preg_replace(
					'#(<a href="https://branddad\.social/contact-us/">Contact</a>)#i',
					'$1<a href="' . $href . '">Become an affiliate</a>',
					$inner,
					1
				);
			}
			if ( false === strpos( $inner, 'bds-chrome-aff' ) && false !== strpos( $inner, 'bd-footer-bottom' ) ) {
				$inner = preg_replace( '#(<div class="bd-footer-bottom")#', $line . '$1', $inner, 1 );
			}
			return $m[1] . $inner . $m[3];
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

/**
 * Named sister-site doors on the customer-visible conversion-shell footer.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_social_inject_chrome_eco( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'bd-footer' ) ) {
		return is_string( $html ) ? $html : '';
	}
	$band = bds_social_eco_html();
	$dir  = esc_url( bds_social_footer_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=footer&utm_campaign=directory_hub' ) );
	$co   = esc_url( bds_social_footer_po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=footer' ) );
	$ht   = esc_url( bds_social_footer_po( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=footer' ) );
	$out  = preg_replace_callback(
		'#(<footer\b[^>]*class="[^"]*\bbd-footer\b[^"]*"[^>]*>)(.*?)(</footer>)#is',
		static function ( $m ) use ( $band, $dir, $co, $ht ) {
			$inner = preg_replace_callback(
				'#<li\b[^>]*>.*?</li>#is',
				static function ( $li ) {
					return preg_match( '#href=(["\'])https?://(?:t\.me|telegram\.me)/#i', $li[0] ) ? '' : $li[0];
				},
				$m[2]
			);
			$inner = preg_replace( '#<a\b[^>]*href=(["\'])https?://(?:t\.me|telegram\.me)/[^"\']+\1[^>]*>.*?</a>#is', '', $inner );
			if ( false === strpos( $inner, 'directory.branddad.social' ) ) {
				$inner = preg_replace(
					'#(<a href="https://branddad\.social/contact-us/[^"]*">Contact</a>)#i',
					'$1<a href="' . $dir . '">Directory — list, claim, 10% off</a><a href="' . $co . '">BrandDad.co — websites &amp; logos</a><a href="' . $ht . '">HostTech — hosting &amp; domains</a>',
					$inner,
					1
				);
			}
			if ( false === strpos( $inner, 'wa.me/18729105115' ) ) {
				$inner = preg_replace(
					'#(<a href="https://branddad\.social/contact-us/[^"]*">Contact</a>)#i',
					'$1<a href="' . esc_url( BDS_SOCIAL_WA_HREF ) . '" target="_blank" rel="noopener noreferrer">WhatsApp — primary contact</a>',
					$inner,
					1
				);
			}
			if ( false === strpos( $inner, 'bds-chrome-eco' ) && false !== strpos( $inner, 'bd-footer-bottom' ) ) {
				$inner = preg_replace( '#(<div class="bd-footer-bottom")#', $band . '$1', $inner, 1 );
			}
			return $m[1] . $inner . $m[3];
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

/**
 * Collapse Squirrly + NAP JSON-LD into one @graph. Visual HTML unchanged.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_social_schema_rewrite_jsonld( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'application/ld+json' ) ) {
		return is_string( $html ) ? $html : '';
	}

	$orig  = $html;
	$nodes = array();
	$count = 0;
	$html  = preg_replace_callback(
		'#<script\b([^>]*)\btype=(["\'])application/ld\+json\2([^>]*)>(.*?)</script>#is',
		static function ( $m ) use ( &$nodes, &$count ) {
			++$count;
			$raw = trim( html_entity_decode( $m[4], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			if ( $raw !== '' ) {
				$decoded = json_decode( $raw, true );
				if ( is_array( $decoded ) ) {
					foreach ( bds_social_schema_collect_nodes( $decoded ) as $n ) {
						$nodes[] = $n;
					}
				}
			}
			return '<!--BDS_SCHEMA_SLOT_' . $count . '-->';
		},
		$html
	);
	if ( $count < 1 || ! is_string( $html ) ) {
		return $orig;
	}

	$is_home  = bds_social_schema_is_home_request();
	$page_url = BDS_SOCIAL_SCHEMA_HOME;
	if ( function_exists( 'wp_get_canonical_url' ) ) {
		$canon = wp_get_canonical_url();
		if ( is_string( $canon ) && $canon !== '' ) {
			$page_url = $canon;
		}
	}
	if ( ! $is_home && function_exists( 'get_permalink' ) ) {
		$perma = get_permalink();
		if ( is_string( $perma ) && $perma !== '' ) {
			$page_url = $perma;
		}
	}

	$page_name = '';
	if ( preg_match( '#<title>(.*?)</title>#is', $html, $tm ) ) {
		$page_name = trim( html_entity_decode( wp_strip_all_tags( $tm[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	$graph = bds_social_schema_build_graph( $nodes, $is_home, $page_url, $page_name );
	if ( $is_home && function_exists( 'bds_social_schema_append_home_faqpage' ) ) {
		$graph = bds_social_schema_append_home_faqpage( $graph, $html );
	}
	$json  = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$tag   = '<script type="application/ld+json" id="bds-social-nap-schema">' . $json . '</script>';

	$html = str_replace( '<!--BDS_SCHEMA_SLOT_1-->', $tag, $html );
	if ( $count > 1 ) {
		for ( $i = 2; $i <= $count; $i++ ) {
			$html = str_replace( '<!--BDS_SCHEMA_SLOT_' . $i . '-->', '', $html );
		}
	}
	return $html;
}

/**
 * Start buffer.
 */
function bds_social_footer_lock_buffer_start() {
	if ( ! bds_social_footer_lock_should_run() || wp_doing_ajax() || is_feed() ) {
		return;
	}
	ob_start( 'bds_social_footer_lock_buffer' );
}
