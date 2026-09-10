<?php
/**
 * Plugin Name: BrandDad press media kit
 * Description: Public /media-kit/ (and /press/, /media/) journalist pages. Organization + WebPage schema. No chrome redesign. Four BrandDad sites only.
 * Version: 1.0.0
 *
 * Copy this MU onto each site. Host detection picks copy. Intercepts parse so Social
 * 404→home 301s do not swallow the kit. Does not send journalist email.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_PRESS_MEDIA_KIT_VER' ) ) {
	return;
}
define( 'BDS_PRESS_MEDIA_KIT_VER', '1.0.0' );

/**
 * @return string social|directory|co|hosttech|''
 */
function bds_pmk_site() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/^www\./', '', $host );
	if ( $host === '' && function_exists( 'home_url' ) ) {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
	}
	if ( $host === 'directory.branddad.social' ) {
		return 'directory';
	}
	if ( $host === 'branddad.social' ) {
		return 'social';
	}
	if ( $host === 'branddad.co' ) {
		return 'co';
	}
	if ( $host === 'hosttech.net' ) {
		return 'hosttech';
	}
	return '';
}

/**
 * @return bool
 */
function bds_pmk_is_kit_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$path = strtolower( trim( $path, '/' ) );
	return in_array( $path, array( 'media-kit', 'press', 'media' ), true );
}

/**
 * Skip core parse so 404→homepage plugins never fire.
 *
 * @param bool   $do Whether to parse.
 * @param WP     $wp WP.
 * @param string $extra Extra path.
 * @return bool
 */
function bds_pmk_do_parse_request( $do, $wp, $extra = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	if ( is_admin() || ! bds_pmk_site() || ! bds_pmk_is_kit_path() ) {
		return $do;
	}
	$wp->query_vars = array(
		'bds_media_kit' => '1',
		'error'         => '',
	);
	return false;
}
add_filter( 'do_parse_request', 'bds_pmk_do_parse_request', 0, 3 );

/**
 * Canonical /media-kit/; keep /press/ and /media/ as 200 aliases (not homepage).
 */
function bds_pmk_render() {
	$site = bds_pmk_site();
	if ( $site === '' || empty( $GLOBALS['wp']->query_vars['bds_media_kit'] ) ) {
		return;
	}
	$pack = bds_pmk_pack( $site );
	if ( ! $pack ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/html; charset=UTF-8' );
	nocache_headers();
	$GLOBALS['wp_query']->is_404     = false;
	$GLOBALS['wp_query']->is_page    = true;
	$GLOBALS['wp_query']->is_singular = true;

	add_filter(
		'document_title_parts',
		static function ( $parts ) use ( $pack ) {
			$parts['title'] = $pack['title'];
			return $parts;
		},
		99
	);
	add_action( 'wp_head', 'bds_pmk_meta_and_schema', 8 );

	get_header();
	echo bds_pmk_html( $pack, $site ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	get_footer();
	exit;
}
add_action( 'template_redirect', 'bds_pmk_render', 0 );

/**
 * Title + description + JSON-LD.
 */
function bds_pmk_meta_and_schema() {
	$site = bds_pmk_site();
	$pack = bds_pmk_pack( $site );
	if ( ! $pack ) {
		return;
	}
	$canon = home_url( '/media-kit/' );
	$desc  = $pack['meta'];
	echo '<link rel="canonical" href="' . esc_url( $canon ) . '" />' . "\n";
	echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
	echo '<meta name="robots" content="index,follow,max-image-preview:large" />' . "\n";
	$org  = array(
		'@type'       => 'Organization',
		'@id'         => trailingslashit( home_url( '/' ) ) . '#organization',
		'name'        => $pack['org'],
		'url'         => home_url( '/' ),
		'description' => $pack['one_liner'],
	);
	if ( ! empty( $pack['sameAs'] ) ) {
		$org['sameAs'] = array_values( $pack['sameAs'] );
	}
	$logo = '';
	$lid  = (int) get_theme_mod( 'custom_logo' );
	if ( $lid ) {
		$logo = (string) wp_get_attachment_url( $lid );
	}
	if ( $logo !== '' ) {
		$org['logo'] = $logo;
	}
	$graph = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			$org,
			array(
				'@type'       => 'WebPage',
				'@id'         => $canon . '#webpage',
				'url'         => $canon,
				'name'        => $pack['title'],
				'description' => $desc,
				'isPartOf'    => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
				'about'       => array( '@id' => $org['@id'] ),
				'primaryImageOfPage' => $logo !== '' ? $logo : null,
			),
		),
	);
	if ( $graph['@graph'][1]['primaryImageOfPage'] === null ) {
		unset( $graph['@graph'][1]['primaryImageOfPage'] );
	}
	echo '<script type="application/ld+json" id="bds-press-media-kit-ld">' .
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) .
		'</script>' . "\n";
}

/**
 * @param string $site Site key.
 * @return array<string,mixed>|null
 */
function bds_pmk_pack( $site ) {
	$po = static function ( $url ) {
		if ( function_exists( 'bds_po_url' ) ) {
			return (string) bds_po_url( $url );
		}
		return (string) $url;
	};
	$packs = array(
		'social'    => array(
			'org'       => 'BrandDad Social',
			'title'     => 'Media kit — BrandDad Social',
			'meta'      => 'Facts for journalists about BrandDad Social: marketing and visibility services, Learning Center, and site checks. No invented stats.',
			'one_liner' => 'BrandDad Social sells marketing, visibility, SEO, and related services for businesses — plus free education in the Learning Center.',
			'sameAs'    => array(
				'https://www.facebook.com/branddad',
				'https://x.com/branddadtweets',
			),
			'cta'       => array(
				array( 'Find a service', $po( home_url( '/services/' ) ) ),
				array( 'Check your website', $po( home_url( '/check-your-website/' ) ) ),
				array( 'Learning Center', $po( home_url( '/learning-center/' ) ) ),
			),
			'facts'     => array(
				'What it is' => 'A live WordPress shop at branddad.social. Customers buy one-time fixes and monthly retainers (visibility, SEO, ads setup, site care). Payment options shown at checkout include card, PayPal, Klarna, and crypto where the product allows.',
				'What it is not' => 'Not a news publisher. Not HostTech hosting. Not BrandDad.co logos. Sister sites are linked in the footer.',
				'Proof we will stand behind' => 'Public product pages with listed USD prices. A free Check your website tool. Learning Center guides. We do not publish review scores, traffic counts, or “as seen in” claims here unless a journalist can click a source on this site.',
				'Story angles (true)' => 'Small operators buying visibility work without a full in-house team; LinkedIn visibility and outreach; honest site-check before a paid SEO package.',
			),
			'contact'   => 'Use the contact / checkout paths on this site. Do not invent a press phone number.',
			'logo_note' => 'Use the live header logo on branddad.social. Do not swap in other BrandDad logos.',
		),
		'directory' => array(
			'org'       => 'BrandDad Social Directory',
			'title'     => 'Media kit — BrandDad Directory',
			'meta'      => 'Facts for journalists about directory.branddad.social: local listings, claim, WhatsApp contact, membership, BrandDad Select, and Newsroom.',
			'one_liner' => 'A live local business directory: list or claim a profile, message on WhatsApp, and join for member perks on BrandDad services.',
			'sameAs'    => array(),
			'cta'       => array(
				array( 'List or claim', $po( home_url( '/' ) ) ),
				array( 'Newsroom', $po( home_url( '/newsroom/' ) ) ),
				array( 'Select methodology', $po( home_url( '/select/methodology/' ) ) ),
			),
			'facts'     => array(
				'What it is' => 'directory.branddad.social. Listings should look like real businesses (photo, location URL, verified contact when we have it). Guests see WhatsApp-first contact. Travel booking is a member perk teaser, not a public flight engine.',
				'What it is not' => 'Not a paid “featured” award sold as journalism. BrandDad Select is editorial recognition and is not for sale.',
				'Proof we will stand behind' => 'Public listing URLs in /city/state/business/ form when location tax is set. /newsroom/ for submitted press material (moderated). /select/ explains Select. We do not invent listing counts, rankings, or menus.',
				'Story angles (true)' => 'Local discovery with WhatsApp; claiming an imported profile; membership 10% on eligible BrandDad services; Select vs paid Featured (they are different).',
			),
			'contact'   => 'WhatsApp chips on live listings. Newsroom submissions follow on-site labels.',
			'logo_note' => 'Use the Directory header logo. Do not use HostTech or .co marks as if they were this product.',
		),
		'co'        => array(
			'org'       => 'BrandDad.co',
			'title'     => 'Media kit — BrandDad.co',
			'meta'      => 'Facts for journalists about branddad.co: AI logos, websites, bundles, and Get started. Organization schema only — no fake storefront NAP.',
			'one_liner' => 'BrandDad.co sells logos, websites, and bundles. Start at Get started — not a walk-in shop.',
			'sameAs'    => array(
				'https://www.instagram.com/branddadco/',
				'https://www.linkedin.com/company/branddad-co/',
			),
			'cta'       => array(
				array( 'Get started', $po( home_url( '/get-started/' ) ) ),
				array( 'Logos', $po( home_url( '/' ) ) ),
			),
			'facts'     => array(
				'What it is' => 'branddad.co — WooCommerce catalog for logos, websites, and bundles. Checkout is live; we do not quote unpublished SKUs here.',
				'What it is not' => 'Not HostTech. Not the Directory. Header is logos / websites / get-started; sister sites sit in the footer.',
				'Proof we will stand behind' => 'Public product and Get started pages. Schema is Organization (WeWork-style, no invented street hours). We do not invent customer counts.',
				'Story angles (true)' => 'Owners who need a logo and site in one place; AI-assisted design studio on Get started; network doors in the footer only.',
			),
			'contact'   => 'Use on-site contact / Get started. No invented press hotline.',
			'logo_note' => 'Use the BrandDad.co header logo only.',
		),
		'hosttech'  => array(
			'org'       => 'HostTech',
			'title'     => 'Media kit — HostTech',
			'meta'      => 'Facts for journalists about hosttech.net: web hosting and domains. Public plan prices as listed in the shop. No invented datacenter tour.',
			'one_liner' => 'HostTech sells web hosting and domains at hosttech.net.',
			'sameAs'    => array(
				'https://www.facebook.com/HostTech.net',
			),
			'cta'       => array(
				array( 'Shop hosting', $po( home_url( '/shopping/' ) ) ),
				array( 'Contact', $po( home_url( '/contact/' ) ) ),
			),
			'facts'     => array(
				'What it is' => 'hosttech.net. Live Woo plans: Starter, Growth, Business, Pro (monthly prices as shown on product pages). Domain registration is a separate product when listed.',
				'What it is not' => 'Not BrandDad Social marketing. Not logo design. Network links belong in the site footer only.',
				'Proof we will stand behind' => 'Public /shopping/ and product URLs. Support via the Contact page (forms and listed chat doors). We do not invent uptime SLAs, datacenter addresses, or phone numbers that are not on Contact.',
				'Story angles (true)' => 'Straightforward hosting SKUs with listed monthly prices; domains next to hosting; a small brand that does not pretend to be a hyperscale cloud.',
			),
			'contact'   => 'https://hosttech.net/contact/',
			'logo_note' => 'Use the HostTech header logo. Do not use BrandDad Social marks on HostTech stories.',
		),
	);
	return isset( $packs[ $site ] ) ? $packs[ $site ] : null;
}

/**
 * @param array  $pack Pack.
 * @param string $site Site.
 * @return string
 */
function bds_pmk_html( $pack, $site ) {
	$home = esc_url( home_url( '/' ) );
	ob_start();
	?>
	<main id="bds-media-kit" class="bds-media-kit" style="max-width:44rem;margin:2rem auto;padding:0 1.25rem 3rem;line-height:1.55">
		<p style="font-size:0.85rem"><a href="<?php echo $home; ?>">Home</a> · Media kit</p>
		<h1><?php echo esc_html( $pack['title'] ); ?></h1>
		<p><strong><?php echo esc_html( $pack['one_liner'] ); ?></strong></p>
		<p>This page is for reporters and partners. It does not invent awards, traffic, or reviews. It covers this site only.</p>
		<p>
			<?php
			foreach ( $pack['cta'] as $i => $cta ) {
				if ( $i ) {
					echo ' ';
				}
				echo '<a class="button" style="display:inline-block;margin:0.25rem 0.5rem 0.25rem 0;padding:0.55rem 0.9rem" href="' . esc_url( $cta[1] ) . '">' . esc_html( $cta[0] ) . '</a>';
			}
			?>
		</p>
		<?php foreach ( $pack['facts'] as $h => $body ) : ?>
			<h2><?php echo esc_html( $h ); ?></h2>
			<p><?php echo esc_html( $body ); ?></p>
		<?php endforeach; ?>
		<h2>Boilerplate (short)</h2>
		<p><?php echo esc_html( $pack['org'] ); ?> is part of the BrandDad family of sites (BrandDad Social, Directory, BrandDad.co, HostTech). Each site sells its own products. Do not merge them into one company story.</p>
		<h2>Logo</h2>
		<p><?php echo esc_html( $pack['logo_note'] ); ?></p>
		<h2>Contact</h2>
		<p><?php echo esc_html( $pack['contact'] ); ?></p>
		<p style="font-size:0.9rem;opacity:0.85">Sister sites (links also appear in each brand footer):
			<a href="https://branddad.social/">BrandDad Social</a> ·
			<a href="https://directory.branddad.social/">Directory</a> ·
			<a href="https://branddad.co/">BrandDad.co</a> ·
			<a href="https://hosttech.net/">HostTech</a>
		</p>
		<p data-bds-pmk="<?php echo esc_attr( BDS_PRESS_MEDIA_KIT_VER . '-' . $site ); ?>"></p>
	</main>
	<?php
	return (string) ob_get_clean();
}

/**
 * Hint for XML sitemaps that support extra URLs via robots or Yoast — we also expose REST for ops.
 */
function bds_pmk_rest() {
	register_rest_route(
		'bds-media-kit/v1',
		'/ping',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'bds_pmk_rest_ping_can',
			'callback'            => static function () {
				return array(
					'ver'  => BDS_PRESS_MEDIA_KIT_VER,
					'site' => bds_pmk_site(),
					'url'  => home_url( '/media-kit/' ),
				);
			},
		)
	);
}
add_action( 'rest_api_init', 'bds_pmk_rest' );

/**
 * The ping endpoint is public and read-only; it exposes only version/site metadata.
 *
 * @return bool
 */
function bds_pmk_rest_ping_can() {
	return true;
}
