<?php
/**
 * Plugin Name: BrandDad Social Unified Brand
 * Description: Unifies BrandDad Social navigation and visual branding with the BrandDad Directory.
 * Version: 1.11.9
 * Author: BrandDad
 *
 * 1.11.9 — Positive Social recovery copy scrub; restore LinkedIn Outreach Launch / Managed catalog rows.
 * 1.11.8 — Soft redirects: dead vanity paths (/local-seo/, /health-check/, GBP, etc.)
 *          → live product / tool URLs instead of bare homepage.
 * 1.11.7 — /services/ path steps start at 1 (was 0 · Check your site — read as $0/bug).
 * 1.11.6 — Reflect all live Woo SKUs on /services/ hub: Trustpilot, Growth
 *         Consultation, Instagram Story Boost, Creator/Campaign doors. No chrome redesign.
 * 1.11.5 — Service finder quiz: plain-language questions + why-copy (also
 *         shipped as bds-service-finder-quiz-mu.php so live conversion shell
 *         still gets the modal when Unified Brand hub is not rendering).
 * 1.11.4 — Dark-theme contrast for /services/ lanes (#lane-local) — navy text
 *         on dark chrome was unreadable. Preserve layout; invert tokens only.
 * 1.11.3 — Authority tighten: money PDPs on home, converting sister doors
 *         (BrandDad.co /get-started/, HostTech /shopping/), Partnero on strips,
 *         /services/ primary CTA stays on catalog. Shop still OUT.
 * 1.11.2 — Front page always expands [branddad_home_hub] (Elementor/cache leftover).
 * 1.11.1 — Learn dropdown keeps Explained / Guides / Courses / Playbooks. Shop still OUT.
 * 1.11.0 — Home “Check your website” → dedicated /check-your-website/ tool page.
 * 1.10.10 — Learn dropdown: Learning Center / Explained / Guides / Courses / Playbooks. Shop still OUT.
 * 1.10.9 — Home “Check your website” → /?bds_health=1 (pretty path was 301ing to a bare home).
 * 1.10.8 — Learn dropdown: Learning Center / Books / Courses. Shop still OUT of header.
 * 1.10.7 — SMM hub: monthly $175 slug unchanged; +Video $355 sibling. Partnero kept.
 * 1.10.6 — Partnero on home ecosystem doors; Social stays services/learning (not a logo factory).
 * 1.10.5 — Explicit Home in primary header (logo alone is not enough).
 *         Shop stays OUT. Learn / Cart / Account / List stay.
 * 1.10.4 — Shop stays OUT of primary header (sibling chrome 1.2.1 had re-added it).
 *         Learn / Cart / Account stay. Catalog via /services/ cards, PDPs, footer.
 * 1.10.3 — Compact converting header: drop redundant Shop from primary nav
 *         (catalog stays on /services/ cards, PDPs, footer, /shop/ URL).
 *         Partnero on List CTA via add_query_arg; Home dropped (logo).
 * 1.10.2 — Soft-path CTAs: /sign-in /login → Woo My Account; /register →
 *         Directory registration; /ai-ads → services #lane-ai when AI Ads
 *         rewrite is cold; nav adds Learning Center / Cart / Account;
 *         Partnero kept; photo cards from 1.10.0 unchanged.
 * 1.10.1 — /services/ CTA wiring: #lane-local panel → Woo My Account (Social
 *         /ai-network/ 301'd home); quiz Google/search → #lane-local; unwrap
 *         AALinks keyword autolinks; Partnero on quiz + explainer URLs.
 * 1.10.0 — Home + /services/ listing cards: restore tasteful AI product photos
 *         when attachment has _bds_svc_ai_photo; skip GD covers/placeholders;
 *         Partnero kept; no outreach; AI Ads pricing lane unchanged.
 * 1.9.9 — /services/ #lane-local: compact site/social check strip → Health Check or
 *         member panel tools; Partnero ref kept; no outreach; no clutter.
 * 1.9.8 — /services/ hero + lane CTAs: fix ghost/secondary contrast (white-on-white
 *         from .bdsu-primary color:!important); CTA fills #0260d9 + white (≥4.5:1).
 * 1.9.7 — Home + /services/ listing cards: text-only (no product photos/plates) for
 *         uniform conversion; AI Ads tier lane kept; Partnero kept; no outreach.
 * 1.9.6 — /services/ #lane-ai: Setup→Starter→Growth→Scale pricing lane.
 * 1.9.5 — “Check your website” Health Check entry on home + /services/; Partnero kept.
 * 1.9.4 — Home popular-services block: converting headline/subcopy, shop layout,
 *         stronger card CTAs; Partnero ref kept; no outreach; catalog unchanged.
 * 1.9.3 — /services/ conversion layout: problem→paths→featured Local/Web + AI Ads→
 *         more growth; network doors deferred; Partnero ref kept; no outreach.
 * 1.9.2 — catalog Local & Web (13) + AI Ads (5) + growth; Partnero; no outreach.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( defined( 'BDSU_LOADED' ) || function_exists( 'bdsu_site_check_url' ) || function_exists( 'bdsu_shared_menu' ) ) {
	return;
}
define( 'BDSU_LOADED', true );
define( 'BDSU_VER', '1.11.9' );

/** BrandDad support WhatsApp — restore if a privacy buffer rewrote it to registration. */
define( 'BDSU_BRANDDAD_WA', 'https://wa.me/18729105115' );

/** Free site-check entry (Health Check MU). */
function bdsu_site_check_url() {
	if ( function_exists( 'bds_health_entry_url' ) ) {
		return bds_health_entry_url();
	}
	return bdsu_po_keep( home_url( '/check-your-website/' ) );
}

/**
 * Member Account Control — site health / SEO / social tools panel.
 *
 * @return string
 */
function bdsu_panel_tools_url() {
	// Social member tools live on Woo My Account (Health MU dashboard section).
	// /ai-network/ is Directory Account Control — on Social it 301s to home.
	return bdsu_po_keep( home_url( '/my-account/' ) ) . '#bds-site-seo-tools';
}

/**
 * Compact Local & Web lane strip: run public site check OR open member panel tools.
 * No separate public social-profile scanner — panel tools cover site + social guidance.
 *
 * @return string
 */
function bdsu_lane_local_check_html() {
	$check = bdsu_site_check_url();
	$panel = bdsu_panel_tools_url();
	ob_start();
	?>
	<aside class="bdsu-lane-check" id="lane-local-check" aria-label="Check your website or open panel tools">
		<div class="bdsu-lane-check__copy">
			<strong>Not sure which fix you need?</strong>
			<span>Run a free public-signal site check — results point to SEO Audit, Speed, GBP, and other Local &amp; Web services when relevant. Prefer your member panel for site + social tools.</span>
		</div>
		<div class="bdsu-lane-check__actions">
			<a class="bdsu-primary" href="<?php echo esc_url( $check ); ?>">Check your website</a>
			<a class="bdsu-secondary bdsu-lane-check__panel" href="<?php echo esc_url( $panel ); ?>">Open tools in your panel</a>
		</div>
	</aside>
	<?php
	return (string) ob_get_clean();
}

function bdsu_is_main_menu( $args ) {
	$menu = isset( $args->menu ) ? wp_get_nav_menu_object( $args->menu ) : false;
	if ( $menu && 'Main Menu' === $menu->name ) return true;
	return isset( $args->theme_location ) && in_array( $args->theme_location, array( 'menu-1', 'primary', 'header' ), true );
}

function bdsu_shared_menu( $items, $args ) {
	if ( ! bdsu_is_main_menu( $args ) ) return $items;
	$list = add_query_arg(
		array(
			'redirect_to'   => 'https://directory.branddad.social/add-listing/',
			'utm_source'    => 'branddad_social',
			'utm_medium'    => 'nav',
			'utm_campaign'  => 'directory10',
		),
		'https://directory.branddad.social/registration/'
	);
	$li = static function ( $label, $url, $class = '' ) {
		$classes = 'menu-item menu-item-type-custom menu-item-object-custom bdsu-menu-item';
		if ( $class ) {
			$classes .= ' ' . $class;
		}
		return '<li class="' . esc_attr( $classes ) . '"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	};
	$more_items = array(
		array( 'Blog', bdsu_po_keep( 'https://branddad.social/blog/' ) ),
		array( 'About', bdsu_po_keep( 'https://branddad.social/about-us/' ) ),
		array( 'Contact', bdsu_po_keep( 'https://branddad.social/contact-us/' ) ),
		array( 'Logos & Websites', bdsu_po_keep( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=nav' ) ),
		array( 'Hosting', bdsu_po_keep( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=nav' ) ),
		array( 'Earn 30%', bdsu_po_keep( 'https://affiliates.branddad.social/' ) ),
	);
	$sub = '';
	foreach ( $more_items as $m ) {
		$sub .= $li( $m[0], $m[1] );
	}
	$learn_sub  = $li( 'Learning Center', bdsu_po_keep( 'https://branddad.social/learning-center/' ) );
	$learn_sub .= $li( 'Explained', bdsu_po_keep( 'https://branddad.social/explained/' ) );
	$learn_sub .= $li( 'Guides', bdsu_po_keep( 'https://branddad.social/guides/' ) );
	$learn_sub .= $li( 'Courses', bdsu_po_keep( 'https://branddad.social/courses/' ) );
	$learn_sub .= $li( 'Playbooks', bdsu_po_keep( 'https://branddad.social/books/' ) );
	$out  = $li( 'Home', bdsu_po_keep( 'https://branddad.social/' ) );
	$out .= $li( 'Services', bdsu_po_keep( 'https://branddad.social/services/' ) );
	$out .= '<li class="menu-item menu-item-has-children menu-item-type-custom menu-item-object-custom bdsu-menu-item bdsu-learn"><a href="' . esc_url( bdsu_po_keep( 'https://branddad.social/learning-center/' ) ) . '">Learn</a><ul class="sub-menu">' . $learn_sub . '</ul></li>';
	$out .= $li( 'Directory', bdsu_po_keep( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=nav&utm_campaign=directory10' ), 'bdsu-directory' );
	$out .= '<li class="menu-item menu-item-has-children menu-item-type-custom menu-item-object-custom bdsu-menu-item bdsu-more"><a href="#">More</a><ul class="sub-menu">' . $sub . '</ul></li>';
	$out .= $li( 'Cart', bdsu_po_keep( 'https://branddad.social/cart/' ) );
	$out .= $li( 'Account', bdsu_po_keep( 'https://branddad.social/my-account/' ) );
	$out .= $li( 'List Your Business', bdsu_po_keep( $list ), 'bdsu-list-business' );
	return $out;
}
add_filter( 'wp_nav_menu_items', 'bdsu_shared_menu', 999, 2 );

/** Compact BrandDad network doors — used on /services and home (not in hero). */
function bdsu_network_doors_html( $context = 'services' ) {
	$utm = 'utm_source=branddad_social&utm_medium=' . rawurlencode( $context );
	$doors = array(
		array(
			'label' => 'Need a website?',
			'blurb' => 'Logos and premium websites live on BrandDad.co — not here.',
			'cta'   => 'Start a logo or website →',
			'url'   => bdsu_po_keep( 'https://branddad.co/get-started/?' . $utm ),
		),
		array(
			'label' => 'Need hosting?',
			'blurb' => 'Domains and hosting live on HostTech — where the site lives.',
			'cta'   => 'Shop HostTech plans →',
			'url'   => bdsu_po_keep( 'https://hosttech.net/shopping/?' . $utm ),
		),
		array(
			'label' => 'Get found on WhatsApp',
			'blurb' => 'Directory is the WhatsApp business listing. Members save 10% here.',
			'cta'   => 'Open Directory →',
			'url'   => bdsu_po_keep( 'https://directory.branddad.social/?' . $utm . '&utm_campaign=directory10' ),
		),
	);
	ob_start();
	?>
	<section class="bdsu-network-doors" id="bdNetworkDoors">
		<div class="bdsu-section-intro">
			<span>BrandDad network</span>
			<h2>Need something else?</h2>
			<p>Same family. Pick the door that fits — logos, hosting, or Directory savings.</p>
		</div>
		<div class="bdsu-ecosystem-grid bdsu-network-grid">
			<?php foreach ( $doors as $door ) : ?>
				<article>
					<h3><?php echo esc_html( $door['label'] ); ?></h3>
					<p><?php echo esc_html( $door['blurb'] ); ?></p>
					<a href="<?php echo esc_url( $door['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $door['cta'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Compact internal links to the growth PDPs worth citing (home + /services/).
 *
 * @return string
 */
function bdsu_money_pdp_strip_html( $context = 'services' ) {
	$p = static function ( $slug ) {
		return bdsu_po_keep( home_url( '/product/' . $slug . '/' ) );
	};
	$items = array(
		array( 'LinkedIn Visibility', 'Monthly professional authority for the people who already matter.', $p( 'linkedin-visibility-amplification-system-for-professionals' ) ),
		array( 'LinkedIn Content', 'Written posts for credibility, not gimmicks.', $p( 'linkedin-viral-posts-for-professionals' ) ),
		array( 'Instagram Growth', 'Discovery and engagement around your brand.', $p( 'instagram-viral-growth-discovery-system' ) ),
		array( 'Telegram Growth', 'Grow and activate a real community.', $p( 'telegram-growth-engagement-system' ) ),
		array( 'Facebook Visibility', 'Scoped campaigns built for awareness and engagement.', $p( 'facebook-growth-visibility-campaigns' ) ),
	);
	ob_start();
	?>
	<section class="bdsu-money-strip" id="bdsu-growth-systems" data-bdsu-context="<?php echo esc_attr( $context ); ?>">
		<div class="bdsu-section-intro">
			<span>Start with one growth system</span>
			<h2>LinkedIn, Instagram, Telegram, Facebook — buy the bottleneck.</h2>
		</div>
		<div class="bdsu-money-strip__grid">
			<?php foreach ( $items as $item ) : ?>
				<a href="<?php echo esc_url( $item[2] ); ?>">
					<strong><?php echo esc_html( $item[0] ); ?></strong>
					<span><?php echo esc_html( $item[1] ); ?></span>
					<b>View pricing &amp; buy →</b>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function bdsu_logo_alt( $attr, $attachment ) {
	if ( 171 === (int) $attachment->ID || false !== stripos( (string) ( $attr['alt'] ?? '' ), 'Bing' ) ) {
		$attr['alt'] = 'BrandDad Social — business growth, marketing, PR and SEO';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'bdsu_logo_alt', 20, 2 );

function bdsu_body_class( $classes ) { $classes[] = 'branddad-unified'; return $classes; }
add_filter( 'body_class', 'bdsu_body_class' );

function bdsu_styles() {
	$css = '
	:root{--branddad-blue:#0372ff;--branddad-blue-dark:#005ed6;--branddad-navy:#0f172a;--branddad-muted:#64748b;--branddad-light:#eff6ff}
	body.branddad-unified{color:var(--branddad-navy)}
	body.branddad-unified .elementor-location-header .e-con[data-settings*="fixed"]{background:#fff!important;border-bottom:1px solid #e2e8f0!important;box-shadow:0 8px 28px rgba(15,23,42,.08)!important}
	body.branddad-unified .elementor-location-header .elementor-nav-menu--main .elementor-item{color:var(--branddad-navy)!important;font-weight:650!important;font-size:15px!important}
	body.branddad-unified .elementor-location-header .elementor-nav-menu--main .elementor-item:hover,
	body.branddad-unified .elementor-location-header .elementor-nav-menu--main .elementor-item.elementor-item-active{color:var(--branddad-blue)!important}
	body.branddad-unified .elementor-location-header .bdsu-directory>a{color:var(--branddad-blue)!important;font-weight:800!important}
	body.branddad-unified .elementor-location-header .bdsu-affiliate>a{color:#059669!important;font-weight:800!important}
	body.branddad-unified .elementor-location-header .bdsu-list-business>a{background:var(--branddad-blue)!important;color:#fff!important;border-radius:10px!important;padding:11px 16px!important}
	body.branddad-unified .elementor-location-header .elementor-nav-menu--main>ul{flex-wrap:nowrap}
	body.branddad-unified .elementor-location-header .elementor-nav-menu--main .menu-item>a{white-space:nowrap}
	body.branddad-unified .elementor-location-header .bdsu-more .sub-menu,
	body.branddad-unified .elementor-location-header .bdsu-learn .sub-menu{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:8px;box-shadow:0 16px 40px rgba(15,23,42,.12);min-width:200px}
	body.branddad-unified .elementor-location-header .bdsu-more .sub-menu a,
	body.branddad-unified .elementor-location-header .bdsu-learn .sub-menu a{white-space:nowrap;font-weight:650}
	body.branddad-unified .elementor-location-header a[href$="/shop/"],
	body.branddad-unified .elementor-location-header a[href*="/shop/?"]{display:none!important}
	body.branddad-unified .elementor-location-header .elementor-widget-button{display:none!important}
	body.branddad-unified .elementor-button,
	body.branddad-unified .button,
	body.branddad-unified button[type="submit"],
	body.branddad-unified .single_add_to_cart_button{border-radius:10px!important}
	body.branddad-unified .elementor-button:not(.elementor-button-link),
	body.branddad-unified .single_add_to_cart_button{background:var(--branddad-blue)!important;color:#fff!important;box-shadow:0 8px 20px rgba(3,114,255,.22)!important}
	body.branddad-unified .elementor-button:hover,
	body.branddad-unified .single_add_to_cart_button:hover{background:var(--branddad-blue-dark)!important;transform:translateY(-1px)}
	body.branddad-unified h1,body.branddad-unified h2,body.branddad-unified h3{letter-spacing:-.025em;color:var(--branddad-navy)}
	body.branddad-unified .woocommerce div.product{max-width:1240px;margin-inline:auto}
	body.branddad-unified .woocommerce div.product .summary{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:28px;box-shadow:0 16px 40px rgba(15,23,42,.07)}
	body.branddad-unified .woocommerce div.product p.price{color:var(--branddad-blue)!important;font-weight:800}
	body.branddad-unified footer{border-top:1px solid #e2e8f0}
	.bdsu-member-strip{background:var(--branddad-navy);color:#fff;text-align:center;padding:10px 20px;font-size:14px;line-height:1.5}
	.bdsu-member-strip a{color:#93c5fd;font-weight:700;text-decoration:none;margin-left:8px}
	@media(max-width:1024px){body.branddad-unified .elementor-location-header .e-con[data-settings*="fixed"]{box-shadow:0 5px 20px rgba(15,23,42,.08)!important}}
	';
	wp_register_style( 'bdsu-brand', false, array(), BDSU_VER );
	wp_enqueue_style( 'bdsu-brand' );
	wp_add_inline_style( 'bdsu-brand', $css );
}
add_action( 'wp_enqueue_scripts', 'bdsu_styles', 99 );

function bdsu_member_strip() {
	if ( is_admin() ) return;
	$join = bdsu_po_keep( 'https://directory.branddad.social/registration/?utm_source=branddad_social&utm_medium=member_strip&utm_campaign=directory10' );
	echo '<div class="bdsu-member-strip">BrandDad Directory members save 10% on eligible BrandDad Social services. <a href="' . esc_url( $join ) . '">Join the Directory →</a></div>';
}
add_action( 'wp_footer', 'bdsu_member_strip', 2 );

function bdsu_affiliate_promo() {
	if ( is_admin() ) return;
	static $shown = false;
	if ( $shown ) return;
	$shown = true;
	?>
	<section class="bdsu-affiliate-promo">
		<div><span>BrandDad Affiliate Program</span><h2>Refer businesses once. Earn 30% recurring.</h2><p>Earn commission on eligible BrandDad Directory subscriptions for as long as your referred customer remains active and paying.</p></div>
		<div class="bdsu-affiliate-benefits"><b>Free to join</b><b>Personal referral link</b><b>Recurring earnings</b></div>
		<a href="<?php echo esc_url( bdsu_po_keep( 'https://affiliates.branddad.social/' ) ); ?>">Become a BrandDad Affiliate</a>
	</section>
	<?php
}
add_action( 'wp_footer', 'bdsu_affiliate_promo', 1 );

function bdsu_start_buffer() {
	if ( is_admin() || wp_doing_ajax() || is_feed() ) return;
	ob_start( 'bdsu_clean_output' );
}
add_action( 'template_redirect', 'bdsu_start_buffer', 1 );

function bdsu_clean_output( $html ) {
	$replace = array(
		'GET SERICES' => 'VIEW SERVICES',
		'Get Serices' => 'View Services',
		'lorem ipsum' => '',
		'Lorem ipsum' => '',
		'http://intagram.com/branddadsocial' => 'https://www.instagram.com/branddadsocial/',
	);
	$html = strtr( $html, $replace );

	// Scrub leftover outreach CTAs / copy from cached Elementor or old embeds.
	$html = preg_replace(
		'#<article\b[^>]*class="[^"]*bdsu-service-card[^"]*"[^>]*>[\s\S]*?(?:LinkedIn\s+Outreach|linkedin-outreach-networking)[\s\S]*?</article>#i',
		'',
		$html
	);
	$html = preg_replace(
		'#<a\b[^>]*href=(["\'])[^"\']*linkedin-outreach[^"\']*\1[^>]*>[\s\S]*?</a>#i',
		'',
		$html
	);
	$html = preg_replace( '/\bDone[- ]for[- ]You LinkedIn Outreach\b/i', 'LinkedIn Visibility', $html );
	$html = preg_replace( '/\bLinkedIn Outreach\b/i', 'LinkedIn Visibility', $html );

	// Prefer static MU (01-bds-home-hub-static.php). Calling bdsu_home_hub() on the front has 500'd Social.
	if ( false !== strpos( $html, '[branddad_home_hub]' ) && function_exists( 'bds_home_static_html' ) ) {
		$hub = bds_home_static_html();
		if ( is_string( $hub ) && $hub !== '' ) {
			$html = str_replace( '[branddad_home_hub]', $hub, $html );
		}
	} elseif ( false !== strpos( $html, '[branddad_home_hub]' ) && function_exists( 'bdsu_home_hub' ) && is_admin() ) {
		$hub = bdsu_home_hub();
		$html = str_replace( array( '<p>[branddad_home_hub]</p>', '[branddad_home_hub]' ), $hub, $html );
	}

	// Unwrap SEO auto-links (AALinks a.aalmanual) that turn keywords like "LinkedIn"
	// inside hub copy into the Visibility PDP — wrong destination from AI Ads / lane copy.
	$html = preg_replace_callback(
		'#<a\b[^>]*\bclass="[^"]*\baal(?:manual)?\b[^"]*"[^>]*>([\s\S]*?)</a>#i',
		static function ( $m ) {
			$text = trim( wp_strip_all_tags( $m[1] ) );
			if ( 1 === preg_match( '/^(LinkedIn|Google|Facebook|Instagram|Telegram|SEO|WhatsApp)$/i', $text ) ) {
				return $m[1];
			}
			return $m[0];
		},
		$html
	);

	// Restore BrandDad support WhatsApp when a contact-gate mistakenly rewrote it to /registration/.
	$wa = BDSU_BRANDDAD_WA . '?text=' . rawurlencode( 'Help me choose a BrandDad service' );
	$html = preg_replace_callback(
		'/<a\b([^>]*\bhref=(["\'])([^"\']*\/(?:registration|login-registration)\/?[^"\']*)\2[^>]*)>([\s\S]*?)<\/a>/i',
		static function ( $m ) use ( $wa ) {
			$text = wp_strip_all_tags( $m[4] );
			if ( 1 !== preg_match( '/whatsapp|ask branddad|chat on whatsapp/i', $text ) ) {
				return $m[0];
			}
			$attrs = preg_replace( '/\bhref=(["\'])[^"\']*\1/i', 'href="' . esc_url( $wa ) . '" target="_blank" rel="noopener"', $m[1], 1 );
			return '<a' . $attrs . '>' . $m[4] . '</a>';
		},
		$html
	);

	return $html;
}

/**
 * Soft path aliases that used to 200-home or 403.
 */
function bdsu_soft_path_redirects() {
	if ( is_admin() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
	$path = untrailingslashit( strtolower( $path ) );
	$map  = array(
		'/gift-cards'   => 'https://directory.branddad.social/gift-card-marketplace/',
		'/affiliate'    => 'https://affiliates.branddad.social/',
		'/ask-branddad' => home_url( '/services/' ),
		'/login'        => home_url( '/my-account/' ),
		'/sign-in'      => home_url( '/my-account/' ),
		'/register'     => 'https://directory.branddad.social/registration/',
		'/registration' => 'https://directory.branddad.social/registration/',
		'/faq'               => home_url( '/contact-us/' ),
		'/contact'           => home_url( '/contact-us/' ),
		'/terms'             => home_url( '/terms-conditions/' ),
		'/terms-of-service'  => home_url( '/terms-conditions/' ),
		'/refund-policy'     => home_url( '/refund_returns/' ),
		'/refunds'           => home_url( '/refund_returns/' ),
		'/returns'           => home_url( '/refund_returns/' ),
		'/ai-network'        => home_url( '/my-account/' ) . '#bds-site-seo-tools',
		// Vanity / retired paths that otherwise 301 to bare home.
		'/health-check'                    => home_url( '/check-your-website/' ),
		'/creator'                         => home_url( '/creator-services/' ),
		'/consult'                         => home_url( '/contact-us/' ),
		'/consultation'                    => home_url( '/contact-us/' ),
		'/google-business-profile-setup'   => home_url( '/product/gbp-setup-optimization/' ),
		'/website-audit'                   => home_url( '/product/website-seo-audit/' ),
		'/website-fix'                     => home_url( '/product/fix-my-website/' ),
		'/local-seo'                       => home_url( '/product/local-seo-management-starter/' ),
		'/local-seo-monthly'               => home_url( '/product/local-seo-management-starter/' ),
		'/social-media-management'         => home_url( '/product/social-media-management/' ),
		'/linkedin-ghostwriting'           => home_url( '/product/linkedin-viral-posts-for-professionals/' ),
	);
	// /ai-ads/ is owned by bds-social-ai-ads-mu.php. Only alias to #lane-ai if that MU is absent.
	if ( ! function_exists( 'bds_aam_render_route' ) ) {
		$map['/ai-ads'] = home_url( '/services/' ) . '#lane-ai';
	}
	if ( isset( $map[ $path ] ) ) {
		$to = $map[ $path ];
		// Keep Partnero / affiliate ?ref= across soft redirects to sister hosts.
		if ( function_exists( 'bds_po_url' ) ) {
			$to = bds_po_url( $to );
		} elseif ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $ref !== '' && false === strpos( $to, 'ref=' ) ) {
				$to .= ( false === strpos( $to, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
			}
		}
		if ( 0 === strpos( $to, 'https://' ) && false === strpos( $to, home_url( '/' ) ) ) {
			wp_redirect( $to, 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- sister BrandDad hosts
			exit;
		}
		wp_safe_redirect( $to, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'bdsu_soft_path_redirects', 0 );

/**
 * Preserve Partnero / affiliate ?ref= on outbound URLs.
 *
 * @param string $url Absolute URL.
 * @return string
 */
function bdsu_po_keep( $url ) {
	$url = (string) $url;
	if ( function_exists( 'bds_po_url' ) ) {
		return (string) bds_po_url( $url );
	}
	if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $ref !== '' && false === strpos( $url, 'ref=' ) ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
		}
	}
	return $url;
}

/**
 * Whether a product thumbnail is a tasteful AI service photo (show on hub cards).
 *
 * @param int $thumb_id Attachment ID.
 * @return bool
 */
function bdsu_is_ai_service_photo( $thumb_id ) {
	$thumb_id = (int) $thumb_id;
	if ( $thumb_id <= 0 ) {
		return false;
	}
	if ( get_post_meta( $thumb_id, '_bds_svc_ai_photo', true ) === '1' ) {
		return true;
	}
	// Never promote GD catalog covers / placeholders on listing cards.
	if ( get_post_meta( $thumb_id, '_bds_svc_catalog_cover', true ) === '1' ) {
		return false;
	}
	$url = (string) wp_get_attachment_url( $thumb_id );
	if ( $url === '' || 1 === preg_match( '/woocommerce-placeholder|wc-placeholder|bds-svc-cover/i', $url ) ) {
		return false;
	}
	return false;
}

/**
 * Service card (home + /services/ lanes).
 * Shows AI product photos when available; otherwise clean text card.
 *
 * @param string $slug        Product slug.
 * @param string $title       Title.
 * @param string $description Short description.
 * @param string $group       Group label.
 * @return string
 */
function bdsu_service_product( $slug, $title, $description, $group ) {
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $post ) {
		return '';
	}
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : false;
	$price   = $product ? $product->get_price_html() : '';
	$url     = bdsu_po_keep( get_permalink( $post ) );
	$cta     = 'View pricing & buy';
	$fit     = bdsu_group_fit( $group );
	$media   = '';
	$thumb_id = (int) get_post_thumbnail_id( $post );
	if ( bdsu_is_ai_service_photo( $thumb_id ) ) {
		$img = get_the_post_thumbnail(
			$post,
			'medium_large',
			array(
				'loading' => 'lazy',
				'alt'     => $title,
				'class'   => 'bdsu-card-img',
			)
		);
		if ( $img ) {
			$media = '<a class="bdsu-card-media" href="' . esc_url( $url ) . '">' . $img . '</a>';
		}
	}
	$card_class = 'bdsu-service-card' . ( $media ? ' bdsu-service-card--photo' : ' bdsu-service-card--text' );
	return '<article class="' . esc_attr( $card_class ) . '" data-group="' . esc_attr( $group ) . '" data-slug="' . esc_attr( $slug ) . '">'
		. $media
		. '<div class="bdsu-card-body">'
		. '<div class="bdsu-card-tag">' . esc_html( $group ) . '</div>'
		. '<h3>' . esc_html( $title ) . '</h3>'
		. '<p>' . esc_html( $description ) . '</p>'
		. '<div class="bdsu-card-fit"><strong>Best for:</strong> ' . esc_html( $fit ) . '</div>'
		. '<div class="bdsu-card-bottom"><div class="bdsu-card-price">'
		. wp_kses_post( $price ?: 'See pricing' )
		. '</div><a class="bdsu-card-button" href="' . esc_url( $url ) . '">' . esc_html( $cta ) . '</a></div>'
		. '</div></article>';
}

/**
 * AI Ads converting tier lane — Setup → Starter → Growth → Scale (+ Extra add-on).
 *
 * @return string
 */
function bdsu_ai_ads_lane_html() {
	$tiers = array(
		array(
			'key'      => 'setup',
			'slug'     => 'bd-ai-ads-setup',
			'name'     => 'Setup',
			'price'    => '$99',
			'period'   => 'one-time',
			'cap'      => 'Onboarding',
			'blurb'    => 'Connect accounts, verify tracking, get first AI campaign drafts.',
			'includes' => array(
				'Ad account connection walkthrough',
				'Pixel / conversion checklist',
				'First AI campaign drafts',
				'Launch checklist',
			),
			'cta'      => 'Start with Setup',
			'featured' => false,
		),
		array(
			'key'      => 'starter',
			'slug'     => 'bd-ai-ads-starter',
			'name'     => 'Starter',
			'price'    => '$149',
			'period'   => '/mo',
			'cap'      => '≤ $500/mo ad spend',
			'blurb'    => 'Meta (Facebook + Instagram) AI creative and management.',
			'includes' => array(
				'Meta automation',
				'Monthly AI creative refresh',
				'Campaigns paused until you approve',
				'Spend dashboard',
			),
			'cta'      => 'Choose Starter',
			'featured' => false,
		),
		array(
			'key'      => 'growth',
			'slug'     => 'bd-ai-ads-growth',
			'name'     => 'Growth',
			'price'    => '$299',
			'period'   => '/mo',
			'cap'      => '≤ $2,000/mo ad spend',
			'blurb'    => 'Meta plus one more network with creative testing.',
			'includes' => array(
				'Everything in Starter',
				'Second ad network (Google or similar)',
				'Multi-variant creative testing',
				'Search-intent copy when needed',
			),
			'cta'      => 'Choose Growth',
			'featured' => true,
		),
		array(
			'key'      => 'scale',
			'slug'     => 'bd-ai-ads-scale',
			'name'     => 'Scale',
			'price'    => '$499',
			'period'   => '/mo',
			'cap'      => '≤ $5,000/mo ad spend',
			'blurb'    => 'Every supported network. Priority automation and refresh.',
			'includes' => array(
				'All networks supported',
				'Priority automation runs',
				'Cross-network budget guidance',
				'Social + search + video coverage',
			),
			'cta'      => 'Choose Scale',
			'featured' => false,
		),
	);

	$html = '<div class="bdsu-ai-path" aria-hidden="true"><span>1 · Setup</span><i></i><span>2 · Starter</span><i></i><span>3 · Growth</span><i></i><span>4 · Scale</span></div>';
	$html .= '<div class="bdsu-ai-tiers">';
	foreach ( $tiers as $tier ) {
		$post = get_page_by_path( $tier['slug'], OBJECT, 'product' );
		if ( ! $post ) {
			continue;
		}
		$url   = bdsu_po_keep( get_permalink( $post ) );
		$feat  = ! empty( $tier['featured'] ) ? ' is-featured' : '';
		$badge = ! empty( $tier['featured'] ) ? '<div class="bdsu-ai-badge">Most chosen</div>' : '';
		$lis   = '';
		foreach ( $tier['includes'] as $inc ) {
			$lis .= '<li>' . esc_html( $inc ) . '</li>';
		}
		$html .= '<article class="bdsu-ai-tier' . $feat . '" data-slug="' . esc_attr( $tier['slug'] ) . '" data-tier="' . esc_attr( $tier['key'] ) . '">'
			. $badge
			. '<header><span class="bdsu-ai-step">' . esc_html( $tier['name'] ) . '</span>'
			. '<div class="bdsu-ai-price"><strong>' . esc_html( $tier['price'] ) . '</strong><em>' . esc_html( $tier['period'] ) . '</em></div>'
			. '<div class="bdsu-ai-cap">' . esc_html( $tier['cap'] ) . '</div></header>'
			. '<p>' . esc_html( $tier['blurb'] ) . '</p>'
			. '<ul>' . $lis . '</ul>'
			. '<a class="bdsu-ai-cta" href="' . esc_url( $url ) . '">' . esc_html( $tier['cta'] ) . '</a>'
			. '</article>';
	}
	$html .= '</div>';

	$extra = get_page_by_path( 'bd-ai-ads-spend-tier', OBJECT, 'product' );
	if ( $extra ) {
		$ex_url = bdsu_po_keep( get_permalink( $extra ) );
		$html  .= '<aside class="bdsu-ai-addon"><div><strong>Need more spend capacity?</strong><span>Extra Spend adds +$1,000/mo managed capacity for $99/mo. Stackable. Your ad spend stays on the platforms.</span></div><a href="' . esc_url( $ex_url ) . '">Add Extra Spend →</a></aside>';
	}
	$html .= '<p class="bdsu-ai-note">Management fees are BrandDad pricing. Ad spend is paid by you directly to Meta / Google / etc.</p>';
	return $html;
}

/**
 * Render product cards for a subset of catalog rows.
 *
 * @param array<int,array{0:string,1:string,2:string,3:string}> $rows Catalog rows.
 * @return string
 */
function bdsu_services_cards_html( $rows ) {
	$cards = '';
	foreach ( $rows as $service ) {
		$cards .= bdsu_service_product( $service[0], $service[1], $service[2], $service[3] );
	}
	return $cards;
}

function bdsu_group_fit( $group ) {
	$fits = array(
		'Local & Web'    => 'Local businesses and site owners needing clear next fixes',
		'AI Ads'         => 'Brands ready for managed paid ads with spend caps',
		'LinkedIn'       => 'Professionals and B2B brands',
		'Social Growth'  => 'Brands that need more discovery',
		'Management'     => 'Busy teams needing content consistency',
		'SEO'            => 'Businesses building search traffic',
		'Authority & PR' => 'Brands building credibility',
		'Reputation'     => 'Businesses protecting customer trust',
		'Consulting'     => 'Owners who want a clear diagnosis before buying more',
	);
	return $fits[ $group ] ?? 'Brands ready to grow';
}

/**
 * Canonical /services/ hub catalog — Local & Web (13) + AI Ads (5) + growth lanes.
 * LinkedIn Outreach SKUs (Launch / Managed) are part of the catalog.
 *
 * @return array<int,array{0:string,1:string,2:string,3:string}>
 */
function bdsu_services_catalog_rows() {
	return array(
		// —— Local & Web (13) ——
		array( 'gbp-setup-optimization', 'GBP Setup & Optimization', 'Claim-ready Google Business Profile fields, categories, and consistency checks — no ranking promises.', 'Local & Web' ),
		array( 'website-speed-optimization', 'Website Speed Optimization', 'Practical performance fixes for measurable speed signals. Hosting upgrades are quoted separately.', 'Local & Web' ),
		array( 'fix-my-website', 'Fix My Website', 'Scoped website repairs starting at $49. Secure intake for access — never paste passwords into normal forms.', 'Local & Web' ),
		array( 'website-seo-audit', 'Website SEO Audit', 'Explainable technical and on-page audit with prioritized fixes. We do not promise Google rankings.', 'Local & Web' ),
		array( 'website-seo-fixes', 'Website SEO Fixes', 'Implement prioritized SEO fixes from an audit (yours or ours). No ranking guarantees.', 'Local & Web' ),
		array( 'local-seo-management-starter', 'Local SEO — Starter', '$199/mo GBP hygiene, citations cadence, and monthly reporting. Real reviews only.', 'Local & Web' ),
		array( 'local-seo-management-growth', 'Local SEO — Growth', '$349/mo Starter plus local content support and deeper citation work.', 'Local & Web' ),
		array( 'google-review-growth-setup', 'Review Growth — Setup', 'Ethical ask-flow so real customers leave reviews. We never filter “only happy” reviews to Google.', 'Local & Web' ),
		array( 'google-review-growth-monthly', 'Review Growth — Monthly', '$79/mo monitoring and gentle ask cadence. Real reviews only.', 'Local & Web' ),
		array( 'business-directory-distribution', 'Directory Distribution', 'Submit and claim across relevant directories. BrandDad Directory is part of the mix — not the only listing.', 'Local & Web' ),
		array( 'website-conversion-makeover', 'Conversion Makeover', 'Clarity, CTA, trust, and form-path improvements — not a full redesign (see BrandDad.co for builds).', 'Local & Web' ),
		array( 'social-profile-optimization-bundle', 'Social Profile Bundle', 'Multi-platform profile polish: bio, links, visuals, and consistency across networks.', 'Local & Web' ),
		array( 'monthly-website-care', 'Monthly Website Care', '$69/mo updates, uptime peek, and minor fixes (≤30 min). Larger work is quoted or Fix My Website.', 'Local & Web' ),
		// —— AI Ads (5) ——
		array( 'bd-ai-ads-setup', 'AI Ads Setup', 'One-time onboarding: connect ad accounts, verify tracking, and get your first AI campaign drafts.', 'AI Ads' ),
		array( 'bd-ai-ads-starter', 'AI Ads Starter', 'Meta (Facebook + Instagram) AI creative and management for up to $500/mo of your ad spend.', 'AI Ads' ),
		array( 'bd-ai-ads-growth', 'AI Ads Growth', 'Meta plus one more network, creative testing, up to $2,000/mo managed spend.', 'AI Ads' ),
		array( 'bd-ai-ads-scale', 'AI Ads Scale', 'Every supported network, priority automation, up to $5,000/mo managed spend.', 'AI Ads' ),
		array( 'bd-ai-ads-spend-tier', 'AI Ads Extra Spend', 'Add $1,000/mo managed spend capacity to any AI Ads plan. Stackable; custom quote above $5k.', 'AI Ads' ),
		// —— Growth / social / PR ——
		array( 'linkedin-visibility-amplification-system-for-professionals', 'LinkedIn Visibility', 'Build authority and consistent professional visibility with a clear LinkedIn growth system.', 'LinkedIn' ),
		array( 'linkedin-viral-posts-for-professionals', 'LinkedIn Content', 'Expert-written posts designed to earn attention, credibility and engagement.', 'LinkedIn' ),
		array( 'linkedin-outreach-launch-service', 'LinkedIn Outreach — Launch', 'One-time setup: ICP, intent-signal targeting, dedicated sender profiles, message sequences. Campaign live in about a week.', 'LinkedIn' ),
		array( 'linkedin-outreach-managed', 'LinkedIn Outreach — Managed', 'Done-for-you monthly outreach to buyers showing intent. Replies handled to a booked call. Your account stays clean.', 'LinkedIn' ),
		array( 'instagram-viral-growth-discovery-system', 'Instagram Growth', 'Increase discovery and build a more active audience around your brand.', 'Social Growth' ),
		array( 'facebook-growth-visibility-campaigns', 'Facebook Visibility', 'Reach more real people with campaigns built for awareness and engagement.', 'Social Growth' ),
		array( 'telegram-growth-engagement-system', 'Telegram Growth', 'Grow and activate a Telegram community with a repeatable engagement system.', 'Social Growth' ),
		array( 'social-media-management', 'Social Media Management', '$175/mo content, engagement and account support without managing it all yourself.', 'Management' ),
		array( 'social-media-management-video', 'Social Media Management + Video', '$355/mo same monthly plan plus weekly Reel-style videos.', 'Management' ),
		array( 'real-influencers-engagements-ig-fb-tiktok-youtube-more', 'Influencer Engagement', 'Earn targeted attention through real creator and influencer engagement.', 'Management' ),
		array( 'comprehensive-seo-packages-rank-1-on-google', 'SEO Growth Packages', 'Ongoing SEO foundations and improvements. We do not promise Google rankings.', 'SEO' ),
		array( 'press-release-services', 'Press Release Distribution', 'Turn announcements into credible media assets and wider online visibility.', 'Authority & PR' ),
		array( 'imdb-profile-creation-services', 'IMDb Profile Creation', 'Build a professional IMDb presence for qualified talent and entertainment projects.', 'Authority & PR' ),
		array( 'nyc-times-square-billboard-video-ads', 'Times Square Billboard', 'Put your brand, launch or announcement on a New York City Times Square screen.', 'Authority & PR' ),
		array( 'remove-negative-google-reviews', 'Google Review Assistance', 'Get expert help assessing and addressing qualifying harmful Google reviews.', 'Reputation' ),
		array( 'remove-negative-airbnb-reviews', 'Airbnb Review Assistance', 'Get professional support for qualifying harmful or policy-violating Airbnb reviews.', 'Reputation' ),
		array( 'trustpilot-review-assistance', 'Trustpilot Review Assistance', 'Support for qualifying Trustpilot reputation issues — assessed case by case.', 'Reputation' ),
		array( 'unblock-your-website-url-domain-from-instagram', 'Instagram Domain Unblock', 'Get help restoring an eligible website or domain blocked from Instagram sharing.', 'Reputation' ),
		array( 'instagram-boost-services-real-people-sharing-your-post-on-their-story-appear-on-discovery', 'Instagram Story Boost', 'Scoped Story visibility boosts when discovery on Stories is the bottleneck.', 'Social Growth' ),
		array( 'business-growth-consultation-60', 'Business Growth Consultation', '$149 / 60 min diagnostic with a BrandDad consultant — diagnose first, no hard sell.', 'Consulting' ),
	);
}

function bdsu_services_hub() {
	$services = bdsu_services_catalog_rows();
	$local    = array();
	$more     = array();
	foreach ( $services as $row ) {
		if ( 'Local & Web' === $row[3] ) {
			$local[] = $row;
		} elseif ( 'AI Ads' !== $row[3] ) {
			$more[] = $row;
		}
	}
	ob_start();
	?>
	<div class="bdsu-services-page bdsu-services-convert" data-bdsu-hub="<?php echo esc_attr( BDSU_VER ); ?>">
		<section class="bdsu-services-hero">
			<div class="bdsu-eyebrow">BrandDad Social · Clear-scope services</div>
			<h1>Stuck on visibility, traffic, or trust?</h1>
			<p>Pick the bottleneck. Buy a scoped fix with transparent starting prices — local &amp; web, AI-managed ads, growth systems, creator content, or a $149 consult.</p>
			<div class="bdsu-hero-actions">
				<a class="bdsu-primary" href="#lane-local">Shop Local &amp; Web</a>
				<a class="bdsu-secondary" href="<?php echo esc_url( bdsu_site_check_url() ); ?>">Check your website</a>
				<button class="bdsu-secondary bdsu-quiz-open" type="button">Find my best fit</button>
			</div>
			<div class="bdsu-trust-row"><span>✓ Free public-signal site check</span><span>✓ Directory members: 10% off eligible services</span><span>✓ WhatsApp before you buy</span></div>
		</section>

		<section class="bdsu-outcome-row" aria-label="Choose your path">
			<a href="<?php echo esc_url( bdsu_site_check_url() ); ?>"><strong>1 · Check your site</strong><span>Free explainable score → honest service suggestions</span><b>Run site check →</b></a>
			<a href="#lane-local" data-bdsu-jump="lane-local"><strong>2 · Fix the foundation</strong><span>GBP, SEO, speed, reviews, site care</span><b>Browse Local &amp; Web →</b></a>
			<a href="#lane-ai" data-bdsu-jump="lane-ai"><strong>3 · Run paid ads</strong><span>Setup → Starter → Growth → Scale</span><b>Browse AI Ads →</b></a>
			<a href="#lane-more" data-bdsu-jump="lane-more"><strong>4 · Grow visibility</strong><span>Social, LinkedIn, PR, reputation</span><b>Browse growth →</b></a>
			<a href="#lane-creator" data-bdsu-jump="lane-creator"><strong>5 · Creator &amp; consult</strong><span>UGC, campaigns, $149 growth consult</span><b>Creator path →</b></a>
		</section>

		<?php echo bdsu_money_pdp_strip_html( 'services' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<section class="bdsu-sales-proof" aria-label="Why BrandDad">
			<div><strong>Scoped offers</strong><span>Each product states deliverables — not open-ended agency fluff.</span></div>
			<div><strong>Buy only what you need</strong><span>Start with one service. Expand when the next step is obvious.</span></div>
			<div><strong>Human before checkout</strong><span>Message WhatsApp if you are unsure which path fits.</span></div>
		</section>

		<section id="lane-local" class="bdsu-service-section bdsu-lane">
			<div class="bdsu-section-heading">
				<div>
					<span>Most common starting point</span>
					<h2>Local &amp; Web</h2>
					<p>Foundation fixes for Google Business Profile, SEO, speed, reviews, and ongoing care — 13 clear options.</p>
				</div>
				<a href="https://wa.me/18729105115?text=Help%20me%20choose%20a%20Local%20%26%20Web%20service">Ask which one →</a>
			</div>
			<?php echo bdsu_lane_local_check_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="bdsu-service-grid"><?php echo bdsu_services_cards_html( $local ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>

		<section id="lane-ai" class="bdsu-service-section bdsu-lane bdsu-lane--ai">
			<div class="bdsu-section-heading">
				<div>
					<span>Paid acquisition path</span>
					<h2>AI Ads — clear tiers, one CTA each</h2>
					<p>Start with Setup once. Then pick the monthly plan that matches your ad spend. Ad spend stays on the platforms — you only pay BrandDad the management fee.</p>
				</div>
				<a href="<?php echo esc_url( bdsu_po_keep( home_url( '/ai-ads/' ) ) ); ?>">How AI Ads works →</a>
			</div>
			<?php echo bdsu_ai_ads_lane_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>

		<section id="lane-more" class="bdsu-service-section bdsu-lane">
			<div class="bdsu-section-heading">
				<div>
					<span>Also available</span>
					<h2>Social, LinkedIn, SEO, PR &amp; reputation</h2>
					<p>Filter to compare.</p>
				</div>
				<a href="<?php echo esc_url( bdsu_po_keep( home_url( '/contact-us/' ) ) ); ?>">Custom plan?</a>
			</div>
			<div class="bdsu-filter-bar" aria-label="Filter growth services">
				<button class="is-active" type="button" data-bdsu-filter="all">All growth</button>
				<button type="button" data-bdsu-filter="LinkedIn">LinkedIn</button>
				<button type="button" data-bdsu-filter="Social Growth">Social Growth</button>
				<button type="button" data-bdsu-filter="Management">Management</button>
				<button type="button" data-bdsu-filter="SEO">SEO</button>
				<button type="button" data-bdsu-filter="Authority & PR">Authority &amp; PR</button>
				<button type="button" data-bdsu-filter="Reputation">Reputation</button>
				<button type="button" data-bdsu-filter="Consulting">Consulting</button>
			</div>
			<div class="bdsu-service-grid" id="service-plans"><?php echo bdsu_services_cards_html( $more ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<div class="bdsu-no-results" hidden>No services match this filter.</div>
		</section>

		<section id="lane-creator" class="bdsu-service-section bdsu-lane">
			<div class="bdsu-section-heading">
				<div>
					<span>Creator content &amp; consulting</span>
					<h2>Need creative production — or a clear diagnosis first?</h2>
					<p>BrandDad builds creator campaigns and offers a $149 growth consultation. Clients work with BrandDad; we handle talent and delivery.</p>
				</div>
				<a href="<?php echo esc_url( bdsu_po_keep( home_url( '/start-a-campaign/' ) ) ); ?>">Start a campaign →</a>
			</div>
			<div class="bdsu-outcome-row" aria-label="Creator and consulting doors">
				<a href="<?php echo esc_url( bdsu_po_keep( home_url( '/creator-services/' ) ) ); ?>"><strong>Creator Services</strong><span>UGC, on-location, distribution, BrandDad 360 — from pricing tables</span><b>View packages →</b></a>
				<a href="<?php echo esc_url( bdsu_po_keep( home_url( '/start-a-campaign/' ) ) ); ?>"><strong>Campaign wizard</strong><span>Tell us the goal — we recommend the right creative path</span><b>Get a recommendation →</b></a>
				<a href="<?php echo esc_url( bdsu_po_keep( home_url( '/product/business-growth-consultation-60/' ) ) ); ?>"><strong>Growth Consultation</strong><span>$149 / 60 min — diagnose first, no hard sell</span><b>Book consult →</b></a>
				<a href="<?php echo esc_url( bdsu_po_keep( 'https://directory.branddad.social/invite-branddad/?utm_source=branddad_social&utm_medium=services&utm_campaign=verified' ) ); ?>"><strong>Invite BrandDad</strong><span>Directory Verified Visit applications (editorial, not ads)</span><b>Invite →</b></a>
			</div>
		</section>

		<section class="bdsu-after-purchase">
			<div class="bdsu-section-intro"><span>What happens after you buy</span><h2>Checkout → details → delivery</h2></div>
			<div class="bdsu-process-grid">
				<article><b>1</b><h3>Choose &amp; pay</h3><p>Pick the package on the product page and complete secure checkout.</p></article>
				<article><b>2</b><h3>Send access &amp; goals</h3><p>We collect the links and details needed to start correctly.</p></article>
				<article><b>3</b><h3>Work begins</h3><p>The team follows the stated scope and messages you if anything is unclear.</p></article>
			</div>
		</section>

		<section class="bdsu-service-faq">
			<div>
				<span>Questions before buying</span>
				<h2>Choose with confidence</h2>
				<p>Unusual situation? Message WhatsApp before checkout.</p>
				<a href="https://wa.me/18729105115?text=I%20have%20a%20question%20before%20buying%20a%20BrandDad%20service">Ask on WhatsApp</a>
			</div>
			<div class="bdsu-faq-list">
				<details open><summary>Which service should I start with?</summary><p>Start with the obstacle closest to revenue: Local &amp; Web if GBP/site/SEO is weak, AI Ads if you are ready to spend on paid acquisition, growth services if discovery is the gap, or reputation if trust is the blocker.</p></details>
				<details><summary>Are results guaranteed?</summary><p>No responsible provider can guarantee a ranking, follower count, press hit, or sales number. Each product page lists the work included.</p></details>
				<details><summary>Do you offer LinkedIn outreach?</summary><p>Yes — LinkedIn Outreach comes as a one-time Launch setup or a done-for-you Managed monthly plan. See the LinkedIn services for scope and pricing.</p></details>
				<details><summary>How does the Directory member discount work?</summary><p>Eligible BrandDad Directory members get 10% off qualifying services. Contact support if it does not show at checkout.</p></details>
				<details><summary>Can I combine services?</summary><p>Yes. Use WhatsApp or the contact form so we can recommend a sensible order and avoid overlap.</p></details>
			</div>
		</section>

		<?php echo bdsu_network_doors_html( 'services' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php bdsu_affiliate_promo(); ?>
		<section class="bdsu-directory-cta">
			<div>
				<span>Already listed in BrandDad Directory?</span>
				<h2>Members save 10% on eligible BrandDad Social services.</h2>
			</div>
			<a href="<?php echo esc_url( bdsu_po_keep( 'https://directory.branddad.social/registration/?utm_source=branddad_social&utm_medium=services&utm_campaign=directory10' ) ); ?>">Join the Directory — 10% off</a>
		</section>
		<div class="bdsu-sticky-help">
			<span><strong>Ready to buy?</strong> Or get a 2-minute recommendation.</span>
			<a href="https://wa.me/18729105115?text=Help%20me%20choose%20the%20right%20BrandDad%20service">Ask BrandDad</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'branddad_services_hub', 'bdsu_services_hub' );

function bdsu_services_script() {
	if ( ! is_page( 'services' ) ) {
		return;
	}
	wp_register_script( 'bdsu-services-filter', '', array(), BDSU_VER, true );
	wp_enqueue_script( 'bdsu-services-filter' );
	wp_add_inline_script(
		'bdsu-services-filter',
		"document.addEventListener('click',function(e){var jump=e.target.closest('[data-bdsu-jump]');if(jump){var id=jump.getAttribute('data-bdsu-jump');var el=document.getElementById(id);if(el){e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'})}return}var b=e.target.closest('[data-bdsu-filter]');if(!b)return;var f=b.getAttribute('data-bdsu-filter');var root=b.closest('.bdsu-lane')||document;var cards=root.querySelectorAll('.bdsu-service-card');if(!cards.length)return;root.querySelectorAll('.bdsu-filter-bar button').forEach(function(x){x.classList.toggle('is-active',x.getAttribute('data-bdsu-filter')===f)});var shown=0;cards.forEach(function(c){var on=f==='all'||c.getAttribute('data-group')===f;c.hidden=!on;if(on)shown++});var n=root.querySelector('.bdsu-no-results');if(n)n.hidden=shown>0;});"
	);
}
add_action( 'wp_enqueue_scripts', 'bdsu_services_script', 100 );

function bdsu_upgrade_services_page() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'bdsu_services_hub_v1' ) ) return;
	$page = get_page_by_path( 'services' );
	if ( ! $page ) return;
	update_option( 'bdsu_services_page_backup', array( 'content' => $page->post_content, 'elementor_data' => get_post_meta( $page->ID, '_elementor_data', true ), 'time' => current_time( 'mysql', true ) ), false );
	wp_update_post( array( 'ID' => $page->ID, 'post_content' => '[branddad_services_hub]' ) );
	delete_post_meta( $page->ID, '_elementor_data' );
	delete_post_meta( $page->ID, '_elementor_edit_mode' );
	update_option( 'bdsu_services_hub_v1', 1, false );
}
add_action( 'admin_init', 'bdsu_upgrade_services_page', 20 );

function bdsu_home_hub() {
	$featured = array(
		array( 'linkedin-visibility-amplification-system-for-professionals', 'LinkedIn Visibility', 'Monthly professional authority — stay visible to the people who already matter.', 'LinkedIn' ),
		array( 'linkedin-viral-posts-for-professionals', 'LinkedIn Content', 'Expert-written posts for attention and credibility.', 'LinkedIn' ),
		array( 'instagram-viral-growth-discovery-system', 'Instagram Growth', 'Discovery and engagement around your brand.', 'Social Growth' ),
		array( 'telegram-growth-engagement-system', 'Telegram Growth', 'Grow and activate a Telegram community with a repeatable engagement plan.', 'Social Growth' ),
		array( 'facebook-growth-visibility-campaigns', 'Facebook Visibility', 'Stay visible to a relevant Facebook audience with scoped campaigns.', 'Social Growth' ),
		array( 'website-seo-audit', 'Website SEO Audit', 'Explainable technical and on-page audit with prioritized fixes — no ranking promises.', 'Local & Web' ),
	);
	$cards = '';
	foreach ( $featured as $service ) $cards .= bdsu_service_product( $service[0], $service[1], $service[2], $service[3] );
	$posts = get_posts( array( 'numberposts' => 3, 'post_status' => 'publish', 'post_type' => 'post' ) );
	$blog = '';
	if ( $posts ) {
		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post );
			$excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ?: $post->post_content ), 22 );
			$thumb = get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy', 'alt' => get_the_title( $post ) ) );
			$media = $thumb ? '<a class="bdsu-blog-media" href="' . esc_url( $permalink ) . '">' . $thumb . '</a>' : '';
			$blog .= '<article class="bdsu-blog-card">' . $media . '<span>' . esc_html( get_the_date( 'M j, Y', $post ) ) . '</span><h3><a href="' . esc_url( $permalink ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3><p>' . esc_html( $excerpt ) . '</p><a class="bdsu-read" href="' . esc_url( $permalink ) . '">Read article →</a></article>';
		}
	} else {
		$blog = '<div class="bdsu-blog-empty"><p>New guides are on the way. Autoblog will fill this section when posts publish.</p><a href="https://branddad.social/blog/">View blog</a></div>';
	}
	do_action( 'bd_home_blog_rendered' );
	ob_start(); ?>
	<div class="bdsu-home-page" data-bdsu-hub="<?php echo esc_attr( BDSU_VER ); ?>">
		<section class="bdsu-home-hero"><div class="bdsu-home-copy"><div class="bdsu-eyebrow">Marketing systems built around your next goal</div><h1>Grow your brand. Get discovered. Turn attention into revenue.</h1><p>BrandDad Social is local/web/AI ads services and learning — LinkedIn, SEO, reputation and PR. Logos and websites live on BrandDad.co. Hosting lives on HostTech.</p><div class="bdsu-hero-actions"><a class="bdsu-primary" href="<?php echo esc_url( bdsu_site_check_url() ); ?>">Check your website</a><a class="bdsu-secondary" href="<?php echo esc_url( bdsu_po_keep( home_url( '/services/' ) ) ); ?>">Explore Marketing Services</a><button class="bdsu-secondary bdsu-quiz-open" type="button">Find My Best Service</button></div><div class="bdsu-trust-row"><span>✓ Free site check</span><span>✓ Clear pricing</span><span>✓ 10% member savings on eligible services</span></div></div><div class="bdsu-home-panel"><span>What do you want to do?</span><a href="<?php echo esc_url( bdsu_site_check_url() ); ?>">Check your website status <b>→</b></a><a href="<?php echo esc_url( bdsu_po_keep( home_url( '/services/' ) ) ); ?>">Buy a marketing service <b>→</b></a><a href="#" class="bdsu-quiz-open">Get a personalized recommendation <b>→</b></a><a href="<?php echo esc_url( bdsu_po_keep( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=home' ) ); ?>">Find businesses in the Directory <b>→</b></a></div></section>
		<section class="bdsu-ecosystem"><div class="bdsu-section-intro"><span>Choose your next step</span><h2>Pick the BrandDad door that fits.</h2><p>Growth services live here. Logos, hosting, and WhatsApp Directory are one honest click away.</p></div><div class="bdsu-ecosystem-grid"><article><b>01</b><h3>Growth services</h3><p>LinkedIn, social growth, SEO, reputation, PR, and management — buy only what you need.</p><a href="<?php echo esc_url( bdsu_po_keep( home_url( '/services/' ) ) ); ?>">Shop services →</a></article><article><b>02</b><h3>Need a website?</h3><p>Logos and premium websites live on BrandDad.co — not here.</p><a href="<?php echo esc_url( bdsu_po_keep( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=home' ) ); ?>" target="_blank" rel="noopener">Start a logo or website →</a></article><article><b>03</b><h3>Need hosting?</h3><p>Domains and hosting live on HostTech — where the site lives.</p><a href="<?php echo esc_url( bdsu_po_keep( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=home' ) ); ?>" target="_blank" rel="noopener">Shop HostTech plans →</a></article><article><b>04</b><h3>Get found on WhatsApp</h3><p>Directory is the WhatsApp business listing. Members save 10% on eligible services here.</p><a href="<?php echo esc_url( bdsu_po_keep( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=home&utm_campaign=directory10' ) ); ?>" target="_blank" rel="noopener">Open Directory →</a></article></div></section>
		<?php bdsu_affiliate_promo(); ?>
		<section class="bdsu-home-services bdsu-home-services--shop" aria-labelledby="bdsu-home-services-title">
			<div class="bdsu-section-heading">
				<div>
					<span>Featured services</span>
					<h2 id="bdsu-home-services-title">Clear scope. Clear pricing. Start with one.</h2>
					<p>LinkedIn, Instagram, Telegram, and Facebook growth systems — plus the SEO audit that follows a site check.</p>
				</div>
				<div class="bdsu-section-actions">
					<a class="bdsu-primary" href="<?php echo esc_url( bdsu_po_keep( home_url( '/services/' ) ) ); ?>">View full catalog</a>
					<a class="bdsu-secondary" href="<?php echo esc_url( bdsu_po_keep( home_url( '/services/' ) ) . '#lane-local' ); ?>">Start here</a>
				</div>
			</div>
			<div class="bdsu-service-grid"><?php echo $cards; ?></div>
			<p class="bdsu-home-services-foot"><a href="<?php echo esc_url( bdsu_po_keep( home_url( '/services/' ) ) ); ?>">See all services &amp; pricing →</a></p>
		</section>
		<section class="bdsu-how"><div><span>How BrandDad works</span><h2>A connected path from visibility to growth</h2></div><ol><li><b>1</b><div><h3>Build your presence</h3><p>Create a directory profile customers can discover and contact through WhatsApp.</p></div></li><li><b>2</b><div><h3>Choose your growth system</h3><p>Buy the service that matches your current bottleneck instead of a random bundle.</p></div></li><li><b>3</b><div><h3>Expand what works</h3><p>Upgrade visibility, add marketing support and use member savings as you grow.</p></div></li></ol></section>
		<section class="bdsu-home-blog"><div class="bdsu-section-heading"><div><span>Business insights</span><h2>Practical ideas for visibility and sales</h2></div><a href="https://branddad.social/blog/">Visit the blog</a></div><div class="bdsu-blog-grid"><?php echo $blog; ?></div></section>
		<section class="bdsu-final-cta"><div><span>Not sure what to buy?</span><h2>Tell us your goal and we’ll point you in the right direction.</h2></div><a href="https://wa.me/18729105115?text=Help%20me%20choose%20the%20right%20BrandDad%20service">Chat on WhatsApp</a></section>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'branddad_home_hub', 'bdsu_home_hub' );

function bdsu_upgrade_home_page() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'bdsu_home_hub_v2' ) ) {
		return;
	}
	$page_id = (int) get_option( 'page_on_front' );
	$page    = $page_id ? get_post( $page_id ) : false;
	if ( ! $page ) {
		return;
	}
	update_option(
		'bdsu_home_page_backup',
		array(
			'content'        => $page->post_content,
			'elementor_data' => get_post_meta( $page->ID, '_elementor_data', true ),
			'time'           => current_time( 'mysql', true ),
		),
		false
	);
	wp_update_post( array( 'ID' => $page->ID, 'post_content' => '[branddad_home_hub]' ) );
	delete_post_meta( $page->ID, '_elementor_data' );
	delete_post_meta( $page->ID, '_elementor_edit_mode' );
	delete_post_meta( $page->ID, '_elementor_css' );
	delete_post_meta( $page->ID, '_elementor_template_type' );
	update_post_meta( $page->ID, '_wp_page_template', 'default' );
	update_option( 'bdsu_home_hub_v1', 1, false );
	update_option( 'bdsu_home_hub_v2', 1, false );
}
add_action( 'admin_init', 'bdsu_upgrade_home_page', 21 );

function bdsu_about_hub() {
	return '<div class="bdsu-standard-page"><section class="bdsu-standard-hero"><div class="bdsu-eyebrow">About BrandDad Social</div><h1>Growth should feel focused—not scattered.</h1><p>BrandDad Social provides practical marketing services designed to help brands become easier to find, trust and buy from. Our separate WhatsApp-first Directory gives businesses another path to discovery.</p><div class="bdsu-hero-actions"><a class="bdsu-primary" href="https://branddad.social/services/">Explore Marketing Services</a><a class="bdsu-secondary" href="https://directory.branddad.social/">Explore the Directory</a></div></section><section class="bdsu-story-grid"><article><span>Our purpose</span><h2>Help serious businesses turn visibility into opportunity.</h2><p>Traffic alone is not enough. BrandDad Social focuses on the marketing systems businesses need next: social growth, SEO, reputation, PR, websites and branding.</p></article><div class="bdsu-values"><div><b>01</b><h3>Clear next steps</h3><p>Visitors should always know what to buy, what it costs and where it leads.</p></div><div><b>02</b><h3>Focused services</h3><p>Every offer should solve a recognizable marketing or visibility problem.</p></div><div><b>03</b><h3>Connected discovery</h3><p>Businesses can use BrandDad Directory to get found and BrandDad Social to grow.</p></div></div></section><section class="bdsu-ecosystem bdsu-about-ecosystem"><div class="bdsu-section-intro"><span>Two connected BrandDad destinations</span><h2>Marketing services here. Business discovery in the Directory.</h2></div><div class="bdsu-ecosystem-grid"><article><h3>Buy marketing services</h3><p>Choose focused support for social media, LinkedIn, SEO, reputation, PR, websites and branding.</p><a href="https://branddad.social/services/">Explore services →</a></article><article><h3>Get a recommendation</h3><p>Tell us your goal and get help choosing the right service or a custom combination.</p><a href="https://wa.me/18729105115?text=Help%20me%20choose%20a%20BrandDad%20marketing%20service">Ask on WhatsApp →</a></article><article><h3>Join the Directory</h3><p>Create a WhatsApp-first business presence customers can discover and contact.</p><a href="https://directory.branddad.social/add-listing/">List your business →</a></article></div></section><section class="bdsu-final-cta"><div><span>Ready to grow?</span><h2>Choose a service or talk to us about your goal.</h2></div><a href="https://wa.me/18729105115?text=I%20want%20to%20grow%20with%20BrandDad">Chat on WhatsApp</a></section></div>';
}
add_shortcode( 'branddad_about_hub', 'bdsu_about_hub' );

function bdsu_contact_hub() {
	$message = '';
	if ( isset( $_POST['bdsu_contact_submit'], $_POST['bdsu_contact_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bdsu_contact_nonce'] ) ), 'bdsu_contact' ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['bdsu_name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['bdsu_email'] ?? '' ) );
		$whatsapp = sanitize_text_field( wp_unslash( $_POST['bdsu_whatsapp'] ?? '' ) );
		$website = esc_url_raw( wp_unslash( $_POST['bdsu_website'] ?? '' ) );
		$goal = sanitize_text_field( wp_unslash( $_POST['bdsu_goal'] ?? '' ) );
		$budget = sanitize_text_field( wp_unslash( $_POST['bdsu_budget'] ?? '' ) );
		$details = sanitize_textarea_field( wp_unslash( $_POST['bdsu_details'] ?? '' ) );
		if ( $name && is_email( $email ) && $goal && $details ) {
			$body = "Name: $name\nEmail: $email\nWhatsApp: $whatsapp\nWebsite: $website\nGoal: $goal\nBudget: $budget\n\nDetails:\n$details";
			$sent = wp_mail( get_option( 'admin_email' ), 'New BrandDad service inquiry: ' . $goal, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
			$message = $sent ? '<div class="bdsu-form-success">Thank you. Your request was sent and the BrandDad team will follow up.</div>' : '<div class="bdsu-form-error">Your request could not be sent. Please use the WhatsApp option instead.</div>';
		} else $message = '<div class="bdsu-form-error">Please complete your name, email, goal and project details.</div>';
	}
	ob_start(); ?>
	<div class="bdsu-standard-page bdsu-contact-page"><section class="bdsu-standard-hero"><div class="bdsu-eyebrow">Contact BrandDad Social</div><h1>Tell us what you want to grow.</h1><p>Choose your goal and give us enough context to recommend the right service, package or next step.</p><div class="bdsu-hero-actions"><a class="bdsu-primary" href="https://wa.me/18729105115?text=I%20need%20help%20choosing%20a%20BrandDad%20service">Chat on WhatsApp</a><a class="bdsu-secondary" href="https://branddad.social/services/">Browse Services First</a></div></section><section class="bdsu-contact-wrap"><aside><span>Fastest option</span><h2>Need an answer now?</h2><p>Message BrandDad on WhatsApp for help choosing a service or discussing a custom request.</p><a href="https://wa.me/18729105115">+1 872-910-5115</a><hr><h3>Good reasons to contact us</h3><ul><li>You need help choosing a package</li><li>You want a custom growth plan</li><li>You have a website or reputation issue</li><li>You want to combine multiple services</li></ul></aside><div class="bdsu-contact-form"><h2>Request a recommendation</h2><?php echo wp_kses_post( $message ); ?><form method="post"><?php wp_nonce_field( 'bdsu_contact', 'bdsu_contact_nonce' ); ?><div class="bdsu-form-grid"><label>Your name *<input required name="bdsu_name" type="text" autocomplete="name"></label><label>Email address *<input required name="bdsu_email" type="email" autocomplete="email"></label><label>WhatsApp number<input name="bdsu_whatsapp" type="tel" autocomplete="tel" placeholder="Include country code"></label><label>Website<input name="bdsu_website" type="url" autocomplete="url" placeholder="https://"></label><label>What do you need? *<select required name="bdsu_goal"><option value="">Choose a goal</option><option>Social media growth</option><option>LinkedIn growth</option><option>SEO and search visibility</option><option>Reputation or review help</option><option>PR and authority</option><option>Website, hosting or branding</option><option>Directory listing or promotion</option><option>Custom plan</option></select></label><label>Estimated budget<select name="bdsu_budget"><option value="">Not sure yet</option><option>Under $250</option><option>$250–$999</option><option>$1,000–$2,499</option><option>$2,500–$4,999</option><option>$5,000+</option></select></label><label class="bdsu-full">Tell us about your goal *<textarea required name="bdsu_details" rows="6" placeholder="What are you trying to achieve, and what is getting in the way?"></textarea></label></div><button type="submit" name="bdsu_contact_submit" value="1">Send My Request</button><p class="bdsu-privacy-note">Your information is used to respond to this request. It is not automatically added to a marketing list.</p></form></div></section></div>
	<?php return ob_get_clean();
}
add_shortcode( 'branddad_contact_hub', 'bdsu_contact_hub' );

function bdsu_upgrade_standard_pages() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$pages = array( 'about-us' => array( '[branddad_about_hub]', 'bdsu_about_hub_v1' ), 'contact-us' => array( '[branddad_contact_hub]', 'bdsu_contact_hub_v1' ) );
	foreach ( $pages as $slug => $config ) {
		if ( get_option( $config[1] ) ) continue;
		$page = get_page_by_path( $slug ); if ( ! $page ) continue;
		update_option( $config[1] . '_backup', array( 'content' => $page->post_content, 'elementor_data' => get_post_meta( $page->ID, '_elementor_data', true ), 'time' => current_time( 'mysql', true ) ), false );
		wp_update_post( array( 'ID' => $page->ID, 'post_content' => $config[0] ) );
		delete_post_meta( $page->ID, '_elementor_data' ); delete_post_meta( $page->ID, '_elementor_edit_mode' ); update_option( $config[1], 1, false );
	}
}
add_action( 'admin_init', 'bdsu_upgrade_standard_pages', 22 );

function bdsu_product_value_box() {
	echo '<div class="bdsu-product-value"><strong>Directory members save 10%</strong><span>Active BrandDad Directory members get 10% off eligible services across BrandDad Social, BrandDad.co, and HostTech. If the discount does not apply at checkout, message us with your Directory profile.</span><a href="https://directory.branddad.social/" target="_blank" rel="noopener">Join or verify at directory.branddad.social →</a></div>';
}
add_action( 'woocommerce_single_product_summary', 'bdsu_product_value_box', 25 );

function bdsu_service_styles() { ?>
	<style>
	/* 1.9.3 conversion hub */
	.bdsu-services-convert{--bdsu-blue:#0260d9;--bdsu-navy:#0f172a;--bdsu-muted:#64748b;--bdsu-line:#e2e8f0;--bdsu-soft:#eff6ff;background:linear-gradient(180deg,#f1f5f9 0%,#f8fafc 28%,#f8fafc 100%);min-height:100vh;padding-bottom:88px}
	.bdsu-services-convert .bdsu-services-hero{max-width:920px;margin:0 auto;padding:96px 24px 40px;text-align:center}
	.bdsu-services-convert .bdsu-services-hero h1{font-size:clamp(36px,5.2vw,58px);line-height:1.08;max-width:820px;margin:14px auto 16px;color:var(--bdsu-navy);letter-spacing:-.02em}
	.bdsu-services-convert .bdsu-services-hero>p{font-size:18px;line-height:1.65;color:#475569;max-width:640px;margin:0 auto 26px}
	/* High-contrast secondary CTA — blue fill + white (survives global .bdsu-primary color:!important) */
	.bdsu-services-convert .bdsu-primary--ghost,
	.bdsu-services-convert .bdsu-primary--ghost:link,
	.bdsu-services-convert .bdsu-primary--ghost:visited{
		background:var(--bdsu-blue)!important;color:#fff!important;border:2px solid var(--bdsu-blue)!important;box-shadow:0 8px 20px rgba(2,96,217,.22)
	}
	.bdsu-services-convert .bdsu-primary--ghost:hover{background:#024fbf!important;border-color:#024fbf!important;filter:none}
	.bdsu-services-convert .bdsu-hero-actions .bdsu-secondary{
		background:#fff!important;color:#0f172a!important;border:2px solid #0f172a!important;box-shadow:none
	}
	.bdsu-services-convert .bdsu-hero-actions .bdsu-secondary:hover{background:#f8fafc!important;border-color:#0260d9!important;color:#0260d9!important}
	.bdsu-services-convert .bdsu-filter-bar button{
		border:2px solid #334155;background:#fff;color:#0f172a;padding:10px 14px;border-radius:999px;font-weight:800;cursor:pointer
	}
	.bdsu-services-convert .bdsu-filter-bar button:hover,
	.bdsu-services-convert .bdsu-filter-bar button.is-active{border-color:var(--bdsu-blue);background:var(--bdsu-blue);color:#fff}
	.bdsu-services-convert .bdsu-outcome-row{max-width:1120px;margin:0 auto 28px;padding:0 24px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
	.bdsu-services-convert .bdsu-outcome-row>a{background:#fff;border:1px solid var(--bdsu-line);border-radius:14px;padding:20px 18px;display:grid;gap:6px;text-decoration:none;box-shadow:0 8px 24px rgba(15,23,42,.04);transition:.18s ease}
	.bdsu-services-convert .bdsu-outcome-row>a:hover{border-color:#93c5fd;transform:translateY(-2px);box-shadow:0 14px 32px rgba(3,114,255,.1)}
	.bdsu-services-convert .bdsu-outcome-row strong{font-size:17px;color:var(--bdsu-navy)}
	.bdsu-services-convert .bdsu-outcome-row span{color:var(--bdsu-muted);font-size:14px;line-height:1.45}
	.bdsu-services-convert .bdsu-outcome-row b{color:var(--bdsu-blue);font-size:13px;margin-top:6px}
	.bdsu-services-convert .bdsu-sales-proof{max-width:1120px;margin:0 auto 56px;padding:20px 22px;background:var(--bdsu-navy);border-radius:16px;display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
	.bdsu-services-convert .bdsu-lane{scroll-margin-top:96px;padding-top:8px;padding-bottom:48px}
	.bdsu-services-convert .bdsu-lane-check{max-width:1120px;margin:0 auto 22px;padding:18px 20px;border:1px solid #bfdbfe;border-radius:14px;background:linear-gradient(135deg,#eff6ff 0%,#fff 55%);display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}
	.bdsu-services-convert .bdsu-lane-check__copy{display:grid;gap:4px;max-width:640px}
	.bdsu-services-convert .bdsu-lane-check__copy strong{color:var(--bdsu-navy);font-size:16px}
	.bdsu-services-convert .bdsu-lane-check__copy span{color:#475569;font-size:14px;line-height:1.5}
	.bdsu-services-convert .bdsu-lane-check__actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
	.bdsu-services-convert .bdsu-lane-check__actions .bdsu-primary{padding:12px 16px;font-size:14px}
	.bdsu-services-convert .bdsu-lane-check__actions .bdsu-secondary{padding:11px 15px;font-size:14px;background:#fff!important;color:#0f172a!important;border:2px solid #0f172a!important}
	.bdsu-services-convert .bdsu-lane-check__actions .bdsu-secondary:hover{border-color:var(--bdsu-blue)!important;color:var(--bdsu-blue)!important}
	.bdsu-services-convert .bdsu-lane--ai{background:linear-gradient(180deg,#eff6ff 0%,#f8fafc 55%,transparent);border:1px solid #dbeafe;border-radius:20px;margin:0 12px 12px;padding:28px 16px 40px;max-width:1180px;margin-left:auto;margin-right:auto}
	.bdsu-services-convert .bdsu-ai-path{max-width:720px;margin:0 auto 22px;display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap;color:#1e3a5f;font-size:13px;font-weight:700}
	.bdsu-services-convert .bdsu-ai-path i{width:28px;height:2px;background:#93c5fd;display:inline-block;border-radius:2px}
	.bdsu-services-convert .bdsu-ai-tiers{max-width:1120px;margin:0 auto;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;align-items:stretch}
	.bdsu-services-convert .bdsu-ai-tier{position:relative;background:#fff;border:1px solid var(--bdsu-line);border-radius:16px;padding:22px 18px 18px;display:flex;flex-direction:column;gap:12px;box-shadow:0 8px 24px rgba(15,23,42,.04)}
	.bdsu-services-convert .bdsu-ai-tier.is-featured{border-color:#0260d9;box-shadow:0 14px 36px rgba(2,96,217,.14);transform:translateY(-4px)}
	.bdsu-services-convert .bdsu-ai-badge{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:var(--bdsu-blue);color:#fff;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;padding:5px 10px;border-radius:999px;white-space:nowrap}
	.bdsu-services-convert .bdsu-ai-step{display:inline-block;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--bdsu-blue);margin-bottom:8px}
	.bdsu-services-convert .bdsu-ai-price{display:flex;align-items:baseline;gap:6px;color:var(--bdsu-navy)}
	.bdsu-services-convert .bdsu-ai-price strong{font-size:34px;letter-spacing:-.03em;line-height:1}
	.bdsu-services-convert .bdsu-ai-price em{font-style:normal;color:var(--bdsu-muted);font-size:14px;font-weight:600}
	.bdsu-services-convert .bdsu-ai-cap{margin-top:6px;font-size:13px;font-weight:700;color:#0f172a;background:#eff6ff;border-radius:8px;padding:7px 10px;display:inline-block}
	.bdsu-services-convert .bdsu-ai-tier>p{margin:0;color:#475569;font-size:14px;line-height:1.5}
	.bdsu-services-convert .bdsu-ai-tier ul{margin:0;padding:0 0 0 18px;color:#334155;font-size:13.5px;line-height:1.45;flex:1}
	.bdsu-services-convert .bdsu-ai-tier li{margin:0 0 7px}
	.bdsu-services-convert .bdsu-ai-cta{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:13px 14px;border-radius:10px;background:var(--bdsu-blue);color:#fff!important;font-weight:800;text-decoration:none;font-size:14px;margin-top:4px}
	.bdsu-services-convert .bdsu-ai-tier.is-featured .bdsu-ai-cta{background:var(--bdsu-navy)}
	.bdsu-services-convert .bdsu-ai-cta:hover{filter:brightness(1.05)}
	.bdsu-services-convert .bdsu-ai-addon{max-width:1120px;margin:18px auto 0;padding:16px 18px;border-radius:14px;border:1px dashed #93c5fd;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px}
	.bdsu-services-convert .bdsu-ai-addon strong{display:block;color:var(--bdsu-navy);font-size:15px;margin-bottom:4px}
	.bdsu-services-convert .bdsu-ai-addon span{color:var(--bdsu-muted);font-size:13.5px;line-height:1.45}
	.bdsu-services-convert .bdsu-ai-addon a{flex-shrink:0;font-weight:800;color:var(--bdsu-blue);text-decoration:none;white-space:nowrap}
	.bdsu-services-convert .bdsu-ai-note{max-width:1120px;margin:14px auto 0;color:#64748b;font-size:13px;line-height:1.5;text-align:center}
	.bdsu-services-convert .bdsu-service-card{background:#fff;border:1px solid var(--bdsu-line);border-radius:16px;overflow:hidden;display:flex;flex-direction:column;min-height:0;box-shadow:0 8px 28px rgba(15,23,42,.05);transition:.18s ease}
	.bdsu-services-convert .bdsu-service-card:hover{transform:translateY(-3px);box-shadow:0 16px 40px rgba(15,23,42,.1);border-color:#bfdbfe}
	.bdsu-services-convert .bdsu-service-card--text .bdsu-card-img,.bdsu-services-convert .bdsu-service-card--text .bdsu-card-media{display:none!important}
	.bdsu-services-convert .bdsu-service-card--photo .bdsu-card-media{display:block;aspect-ratio:16/10;overflow:hidden;background:#e2e8f0}
	.bdsu-services-convert .bdsu-service-card--photo .bdsu-card-img{width:100%;height:100%;object-fit:cover;display:block}
	.bdsu-services-convert .bdsu-card-body{padding:22px 22px 20px;display:flex;flex-direction:column;flex:1;gap:0;min-height:100%}
	.bdsu-services-convert .bdsu-service-card .bdsu-card-tag{padding:0;margin:0;font-size:12px;font-weight:800;color:var(--bdsu-blue);text-transform:uppercase;letter-spacing:.07em}
	.bdsu-services-convert .bdsu-service-card h3{font-size:20px;margin:10px 0 8px;line-height:1.25;color:var(--bdsu-navy)}
	.bdsu-services-convert .bdsu-card-body>p{color:var(--bdsu-muted);line-height:1.55;font-size:14.5px;margin:0 0 12px}
	.bdsu-services-convert .bdsu-card-fit{color:#475569;font-size:13px;line-height:1.45;padding:12px 0 14px;border-top:1px solid #f1f5f9;margin-top:auto;white-space:normal;overflow:visible}
	.bdsu-services-convert .bdsu-card-fit strong{color:var(--bdsu-navy);font-weight:750}
	.bdsu-services-convert .bdsu-card-bottom{padding:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
	.bdsu-services-convert .bdsu-card-price{font-weight:800;color:var(--bdsu-navy);font-size:16px}
	.bdsu-services-convert .bdsu-card-button{padding:11px 14px;font-size:13.5px;white-space:nowrap}
	.bdsu-services-convert .bdsu-network-doors{margin-top:24px}
	.bdsu-services-convert .bdsu-sticky-help{bottom:78px}
	@media(max-width:1100px){.bdsu-services-convert .bdsu-ai-tiers{grid-template-columns:repeat(2,minmax(0,1fr))}.bdsu-services-convert .bdsu-ai-tier.is-featured{transform:none}}
	@media(max-width:900px){.bdsu-services-convert .bdsu-outcome-row,.bdsu-services-convert .bdsu-sales-proof{grid-template-columns:1fr}.bdsu-services-convert .bdsu-lane--ai{margin-inline:8px}.bdsu-services-convert .bdsu-ai-addon{flex-direction:column;align-items:flex-start}.bdsu-services-convert .bdsu-lane-check{align-items:flex-start}}
	@media(max-width:620px){.bdsu-services-convert .bdsu-services-hero{padding-top:72px}.bdsu-services-convert .bdsu-hero-actions .bdsu-primary,.bdsu-services-convert .bdsu-hero-actions .bdsu-secondary,.bdsu-services-convert .bdsu-lane-check__actions .bdsu-primary,.bdsu-services-convert .bdsu-lane-check__actions .bdsu-secondary{width:100%}.bdsu-services-convert .bdsu-lane-check__actions{width:100%}.bdsu-services-convert .bdsu-card-bottom{flex-direction:column;align-items:stretch}.bdsu-services-convert .bdsu-card-button{width:100%;text-align:center}.bdsu-services-convert .bdsu-ai-tiers{grid-template-columns:1fr}.bdsu-services-convert .bdsu-sticky-help{bottom:70px}}
	/* 1.9.4 home featured services shop */
	.bdsu-home-services--shop{max-width:1180px;margin:0 auto;padding:88px 24px 24px}
	.bdsu-home-services--shop .bdsu-section-heading{align-items:flex-end;margin-bottom:28px;gap:24px}
	.bdsu-home-services--shop .bdsu-section-heading h2{font-size:clamp(28px,4vw,44px);line-height:1.12;letter-spacing:-.02em;max-width:640px;margin:8px 0 0;color:#0f172a}
	.bdsu-home-services--shop .bdsu-section-heading p{color:#64748b;font-size:16px;line-height:1.6;margin:12px 0 0;max-width:560px}
	.bdsu-home-services--shop .bdsu-section-actions{display:flex;flex-wrap:wrap;gap:10px;flex-shrink:0}
	.bdsu-home-services--shop .bdsu-section-actions .bdsu-primary,.bdsu-home-services--shop .bdsu-section-actions .bdsu-secondary{padding:12px 16px;font-size:14px}
	.bdsu-home-services--shop .bdsu-service-grid{gap:20px}
	.bdsu-home-services--shop .bdsu-service-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;min-height:0;box-shadow:0 8px 28px rgba(15,23,42,.05);transition:.18s ease}
	.bdsu-home-services--shop .bdsu-service-card:hover{transform:translateY(-3px);box-shadow:0 16px 40px rgba(15,23,42,.1);border-color:#bfdbfe}
	.bdsu-home-services--shop .bdsu-service-card--text .bdsu-card-img,.bdsu-home-services--shop .bdsu-service-card--text .bdsu-card-media{display:none!important}
	.bdsu-home-services--shop .bdsu-service-card--photo .bdsu-card-media{display:block;aspect-ratio:16/10;overflow:hidden;background:#e2e8f0}
	.bdsu-home-services--shop .bdsu-service-card--photo .bdsu-card-img{width:100%;height:100%;object-fit:cover;display:block}
	.bdsu-home-services--shop .bdsu-card-body{padding:22px 22px 20px;display:flex;flex-direction:column;flex:1;min-height:100%}
	.bdsu-home-services--shop .bdsu-card-tag{padding:0;margin:0;font-size:12px;font-weight:800;color:#0372ff;text-transform:uppercase;letter-spacing:.07em}
	.bdsu-home-services--shop .bdsu-service-card h3{font-size:20px;margin:10px 0 8px;line-height:1.25;color:#0f172a}
	.bdsu-home-services--shop .bdsu-card-body>p{color:#64748b;line-height:1.55;font-size:14.5px;margin:0 0 12px}
	.bdsu-home-services--shop .bdsu-card-fit{color:#475569;font-size:13px;line-height:1.45;padding:12px 0 14px;border-top:1px solid #f1f5f9;margin-top:auto;white-space:normal;overflow:visible}
	.bdsu-home-services--shop .bdsu-card-fit strong{color:#0f172a;font-weight:750}
	.bdsu-home-services--shop .bdsu-card-bottom{padding:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
	.bdsu-home-services--shop .bdsu-card-price{font-weight:800;color:#0f172a;font-size:16px}
	.bdsu-home-services--shop .bdsu-card-button{padding:11px 14px;font-size:13.5px;white-space:nowrap}
	.bdsu-home-services-foot{margin:28px 0 0;text-align:center}
	.bdsu-home-services-foot a{color:#0372ff;font-weight:800;text-decoration:none;font-size:15px}
	.bdsu-home-services-foot a:hover{text-decoration:underline}
	.bdsu-money-strip{max-width:1180px;margin:0 auto 48px;padding:0 24px}
	.bdsu-money-strip .bdsu-section-intro{max-width:760px;margin:0 auto 22px;text-align:center}
	.bdsu-money-strip__grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
	.bdsu-money-strip__grid a{display:grid;gap:6px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;text-decoration:none;min-height:100%}
	.bdsu-money-strip__grid a:hover{border-color:#93c5fd;transform:translateY(-2px)}
	.bdsu-money-strip__grid strong{color:#0f172a;font-size:15px}
	.bdsu-money-strip__grid span{color:#64748b;font-size:13px;line-height:1.45}
	.bdsu-money-strip__grid b{color:#0372ff;font-size:13px;margin-top:auto}
	@media(max-width:900px){.bdsu-money-strip__grid{grid-template-columns:1fr 1fr}}
	@media(max-width:620px){.bdsu-money-strip__grid{grid-template-columns:1fr}}
	@media(max-width:900px){.bdsu-home-services--shop .bdsu-section-heading{align-items:flex-start;flex-direction:column}}
	@media(max-width:620px){.bdsu-home-services--shop{padding-top:64px}.bdsu-home-services--shop .bdsu-section-actions{width:100%}.bdsu-home-services--shop .bdsu-section-actions .bdsu-primary,.bdsu-home-services--shop .bdsu-section-actions .bdsu-secondary{flex:1;text-align:center;justify-content:center}.bdsu-home-services--shop .bdsu-card-bottom{flex-direction:column;align-items:stretch}.bdsu-home-services--shop .bdsu-card-button{width:100%;text-align:center}}
	.bdsu-network-doors{background:#f1f5f9;padding:64px 24px;margin:0 0 8px}.bdsu-network-doors .bdsu-section-intro{max-width:720px;margin:0 auto 28px;text-align:center}.bdsu-network-doors .bdsu-section-intro h2{font-size:clamp(28px,4vw,42px);margin:8px 0}.bdsu-network-doors .bdsu-section-intro p{font-size:17px;color:#64748b;margin:0}.bdsu-network-grid{max-width:1140px;margin:auto;display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.bdsu-network-grid article{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:22px}.bdsu-network-grid h3{font-size:22px;margin:0 0 8px;color:#0f172a}.bdsu-network-grid p{color:#64748b;line-height:1.55;margin:0 0 14px}.bdsu-network-grid a{color:#0372ff;font-weight:800;text-decoration:none}.bdsu-ecosystem .bdsu-ecosystem-grid{grid-template-columns:repeat(4,1fr)}
	.bdsu-outcome-row>a{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;display:grid;gap:5px;text-decoration:none;transition:.2s}.bdsu-outcome-row>a:hover{border-color:#93c5fd;transform:translateY(-3px)}.bdsu-outcome-row>a strong{font-size:18px;color:#0f172a}.bdsu-outcome-row>a span{color:#64748b}.bdsu-outcome-row>a b{color:#0372ff;font-size:13px;margin-top:8px}.bdsu-sales-proof{max-width:1132px;margin:-34px auto 64px;padding:22px 24px;background:#0f172a;border-radius:18px;display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.bdsu-sales-proof>div{display:grid;gap:6px}.bdsu-sales-proof strong{color:#fff}.bdsu-sales-proof span{color:#cbd5e1;font-size:14px;line-height:1.5}.bdsu-section-heading p{color:#64748b;margin:8px 0 0}.bdsu-filter-bar{display:flex;gap:9px;flex-wrap:wrap;margin:0 0 24px}.bdsu-filter-bar button{border:1px solid #cbd5e1;background:#fff;color:#334155;padding:10px 14px;border-radius:999px;font-weight:750;cursor:pointer}.bdsu-filter-bar button:hover,.bdsu-filter-bar button.is-active{border-color:#0372ff;background:#0372ff;color:#fff}.bdsu-service-card[hidden]{display:none!important}.bdsu-card-fit{color:#475569;font-size:13px;padding:12px 0 18px;border-top:1px solid #f1f5f9}.bdsu-card-fit strong{color:#0f172a}.bdsu-no-results{text-align:center;padding:30px;color:#64748b}.bdsu-after-purchase{max-width:1132px;margin:80px auto 0;padding:0 24px}.bdsu-process-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.bdsu-process-grid article{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:26px}.bdsu-process-grid b{width:42px;height:42px;display:grid;place-items:center;background:#eff6ff;color:#0372ff;border-radius:50%}.bdsu-process-grid h3{font-size:23px}.bdsu-process-grid p{color:#64748b;line-height:1.65}.bdsu-service-faq{max-width:1132px;margin:80px auto 0;padding:42px;background:#fff;border:1px solid #e2e8f0;border-radius:22px;display:grid;grid-template-columns:.75fr 1.25fr;gap:42px}.bdsu-service-faq>div:first-child>span{color:#0372ff;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.bdsu-service-faq h2{font-size:40px;margin:8px 0}.bdsu-service-faq>div:first-child p{color:#64748b}.bdsu-service-faq>div:first-child a{color:#0372ff;font-weight:800}.bdsu-faq-list{display:grid;gap:10px}.bdsu-faq-list details{border:1px solid #e2e8f0;border-radius:12px;padding:16px}.bdsu-faq-list summary{font-weight:800;color:#0f172a;cursor:pointer}.bdsu-faq-list p{color:#64748b;line-height:1.6;margin-bottom:0}.bdsu-sticky-help{position:sticky;bottom:16px;z-index:50;max-width:760px;margin:42px auto 0;background:#fff;border:1px solid #bfdbfe;box-shadow:0 16px 40px rgba(15,23,42,.16);border-radius:14px;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;gap:18px}.bdsu-sticky-help span{color:#475569}.bdsu-sticky-help strong{color:#0f172a}.bdsu-sticky-help a{background:#0372ff;color:#fff;border-radius:9px;padding:10px 14px;text-decoration:none;font-weight:800;white-space:nowrap}@media(max-width:900px){.bdsu-sales-proof,.bdsu-process-grid,.bdsu-network-grid{grid-template-columns:1fr}.bdsu-sales-proof,.bdsu-service-faq{margin-inline:24px}.bdsu-service-faq{grid-template-columns:1fr}}@media(max-width:620px){.bdsu-sticky-help{margin-inline:14px;align-items:flex-start;flex-direction:column}.bdsu-sticky-help a{width:100%;text-align:center}.bdsu-service-faq{padding:26px}}
	.bdsu-services-page,.bdsu-home-page{background:#f8fafc;min-height:100vh;padding-bottom:72px}.page:has(.bdsu-services-page) .page-header,.page:has(.bdsu-home-page) .page-header{display:none}.bdsu-services-hero{max-width:1180px;margin:0 auto;padding:110px 24px 64px;text-align:center}.bdsu-eyebrow,.bdsu-section-heading span,.bdsu-directory-cta span,.bdsu-section-intro>span,.bdsu-how>div>span,.bdsu-final-cta span{color:#0372ff;font-weight:800;text-transform:uppercase;letter-spacing:.09em;font-size:13px}.bdsu-services-hero h1{font-size:clamp(42px,6vw,72px);line-height:1.02;max-width:900px;margin:16px auto}.bdsu-services-hero>p{font-size:20px;line-height:1.65;color:#475569;max-width:780px;margin:0 auto 30px}.bdsu-hero-actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}.bdsu-primary,.bdsu-secondary,.bdsu-card-button,.bdsu-directory-cta>a,.bdsu-final-cta>a{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:10px;font-weight:800;text-decoration:none}.bdsu-primary,.bdsu-card-button,.bdsu-directory-cta>a,.bdsu-final-cta>a{background:#0372ff;color:#fff}.bdsu-secondary{background:#fff;color:#0f172a;border:1px solid #cbd5e1}.bdsu-trust-row{display:flex;justify-content:center;gap:24px;flex-wrap:wrap;color:#475569;margin-top:26px;font-size:14px}.bdsu-outcome-row{max-width:1120px;margin:0 auto 64px;padding:0 24px;display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.bdsu-outcome-row>div{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;display:grid;gap:5px}.bdsu-outcome-row strong{font-size:18px;color:#0f172a}.bdsu-outcome-row span{color:#64748b}.bdsu-service-section,.bdsu-home-services,.bdsu-home-blog{max-width:1180px;margin:auto;padding:0 24px}.bdsu-section-heading{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:24px}.bdsu-section-heading h2{font-size:clamp(30px,4vw,46px);margin:6px 0 0}.bdsu-section-heading>a{color:#0372ff;font-weight:800}.bdsu-service-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}	.bdsu-service-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:0;display:flex;flex-direction:column;min-height:300px;box-shadow:0 10px 30px rgba(15,23,42,.05);transition:.2s;overflow:hidden}.bdsu-service-card:hover{transform:translateY(-4px);box-shadow:0 18px 42px rgba(15,23,42,.1)}.bdsu-card-media,.bdsu-card-img--ph{aspect-ratio:16/9;overflow:hidden;background:linear-gradient(135deg,#eff6ff,#e2e8f0)}.bdsu-card-media img,.bdsu-card-img{width:100%;height:100%;object-fit:cover;display:block}.bdsu-card-img--ph{display:grid;place-items:center}.bdsu-card-img--ph span{font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#0372ff}.bdsu-service-card>.bdsu-card-tag,.bdsu-service-card>h3,.bdsu-service-card>p,.bdsu-service-card>.bdsu-card-fit,.bdsu-service-card>.bdsu-card-bottom{padding-left:24px;padding-right:24px}.bdsu-service-card>.bdsu-card-tag{padding-top:18px}.bdsu-service-card>.bdsu-card-bottom{padding-bottom:24px}.bdsu-card-tag{font-size:12px;font-weight:800;color:#0372ff;text-transform:uppercase;letter-spacing:.07em}.bdsu-service-card h3{font-size:24px;margin:12px 0 10px}.bdsu-service-card p{color:#64748b;line-height:1.65;margin:0 0 24px}.bdsu-card-bottom{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:12px}.bdsu-card-price{font-size:16px;font-weight:800;color:#0f172a}.bdsu-card-price del{font-size:13px;color:#94a3b8}.bdsu-card-button{padding:10px 14px;font-size:14px;white-space:nowrap}.bdsu-directory-cta{max-width:1132px;margin:64px auto 0;background:#0f172a;color:#fff;border-radius:20px;padding:34px;display:flex;justify-content:space-between;align-items:center;gap:24px}.bdsu-directory-cta h2{color:#fff;margin:8px 0 0;max-width:760px}.bdsu-directory-cta>a{white-space:nowrap}.bdsu-services-page+*{display:none!important}.bdsu-home-hero{max-width:1180px;margin:auto;padding:120px 24px 82px;display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,.7fr);gap:48px;align-items:center}.bdsu-home-copy h1{font-size:clamp(46px,6vw,78px);line-height:1.02;margin:16px 0 22px;max-width:850px}.bdsu-home-copy>p{font-size:20px;line-height:1.65;color:#475569;max-width:760px}.bdsu-home-copy .bdsu-hero-actions,.bdsu-home-copy .bdsu-trust-row{justify-content:flex-start}.bdsu-home-panel{background:#0f172a;color:#fff;border-radius:22px;padding:28px;box-shadow:0 24px 60px rgba(15,23,42,.2)}.bdsu-home-panel>span{color:#93c5fd;font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:12px}.bdsu-home-panel a{display:flex;justify-content:space-between;gap:20px;color:#fff;text-decoration:none;font-weight:750;padding:20px 0;border-bottom:1px solid #334155}.bdsu-home-panel a:last-child{border:0}.bdsu-home-panel b{color:#60a5fa}.bdsu-ecosystem{background:#fff;padding:80px 24px}.bdsu-section-intro{max-width:760px;margin:0 auto 34px;text-align:center}.bdsu-section-intro h2{font-size:clamp(34px,5vw,54px);margin:8px 0}.bdsu-section-intro p{font-size:18px;color:#64748b}.bdsu-ecosystem-grid{max-width:1140px;margin:auto;display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.bdsu-ecosystem-grid article{border:1px solid #e2e8f0;border-radius:18px;padding:28px}.bdsu-ecosystem-grid b{color:#0372ff}.bdsu-ecosystem-grid h3{font-size:30px;margin:24px 0 10px}.bdsu-ecosystem-grid p{color:#64748b;line-height:1.65}.bdsu-ecosystem-grid a,.bdsu-read{color:#0372ff;font-weight:800;text-decoration:none}.bdsu-home-services,.bdsu-home-blog{padding-top:80px}.bdsu-how{max-width:1132px;margin:80px auto 0;padding:48px;background:#fff;border:1px solid #e2e8f0;border-radius:22px;display:grid;grid-template-columns:.8fr 1.2fr;gap:50px}.bdsu-how h2{font-size:clamp(34px,4vw,50px);margin:8px 0}.bdsu-how ol{list-style:none;padding:0;margin:0;display:grid;gap:26px}.bdsu-how li{display:flex;gap:18px}.bdsu-how li>b{width:42px;height:42px;flex:0 0 42px;border-radius:50%;display:grid;place-items:center;background:#eff6ff;color:#0372ff}.bdsu-how h3{margin:0 0 5px}.bdsu-how p{margin:0;color:#64748b}.bdsu-blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.bdsu-blog-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:26px}.bdsu-blog-card>span{color:#64748b;font-size:13px}.bdsu-blog-card h3{font-size:23px}.bdsu-blog-card h3 a{color:#0f172a;text-decoration:none}.bdsu-blog-card p{color:#64748b;line-height:1.65}.bdsu-final-cta{max-width:1132px;margin:80px auto 0;background:#0f172a;color:#fff;border-radius:22px;padding:42px;display:flex;align-items:center;justify-content:space-between;gap:30px}.bdsu-final-cta h2{color:#fff;margin:8px 0 0;max-width:780px}.bdsu-final-cta>a{white-space:nowrap}@media(max-width:1100px){.bdsu-ecosystem .bdsu-ecosystem-grid{grid-template-columns:repeat(2,1fr)}.bdsu-ecosystem-grid article:last-child{grid-column:auto}}@media(max-width:900px){.bdsu-service-grid,.bdsu-ecosystem-grid,.bdsu-blog-grid,.bdsu-ecosystem .bdsu-ecosystem-grid{grid-template-columns:repeat(2,1fr)}.bdsu-outcome-row{grid-template-columns:1fr}.bdsu-directory-cta,.bdsu-how,.bdsu-final-cta{margin-inline:24px}.bdsu-home-hero,.bdsu-how{grid-template-columns:1fr}.bdsu-home-panel{max-width:620px}}@media(max-width:620px){.bdsu-services-hero,.bdsu-home-hero{padding-top:80px}.bdsu-service-grid,.bdsu-ecosystem-grid,.bdsu-blog-grid,.bdsu-ecosystem .bdsu-ecosystem-grid,.bdsu-network-grid{grid-template-columns:1fr}.bdsu-section-heading,.bdsu-directory-cta,.bdsu-final-cta{align-items:flex-start;flex-direction:column}.bdsu-card-bottom{align-items:flex-start;flex-direction:column}.bdsu-card-button{width:100%}.bdsu-how{padding:28px}}
	/* 1.10.0 — text cards stay image-free; photo cards show AI media */
	.bdsu-service-card--text .bdsu-card-img,
	.bdsu-service-card--text .bdsu-card-media{display:none!important;height:0!important;min-height:0!important;padding:0!important;margin:0!important;border:0!important}
	.bdsu-service-card--photo .bdsu-card-media{display:block!important;aspect-ratio:16/10;overflow:hidden;background:#e2e8f0}
	.bdsu-service-card--photo .bdsu-card-img{display:block!important;width:100%;height:100%;object-fit:cover}
	.bdsu-service-card--text{min-height:0!important}
	.bdsu-service-card--text .bdsu-card-body{padding:22px!important;display:flex!important;flex-direction:column!important;flex:1!important;height:100%}
	.bdsu-service-card--text .bdsu-card-fit{white-space:normal!important;overflow:visible!important;text-overflow:clip!important;-webkit-line-clamp:unset!important;display:block!important;line-height:1.45!important}
	.bdsu-service-card--text .bdsu-card-bottom{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:0!important}
	.bdsu-service-card--text .bdsu-card-button{white-space:nowrap}
	/* 1.9.4 home shop wins over legacy .bdsu-home-services padding */
	.bdsu-home-page .bdsu-home-services--shop{padding:88px 24px 24px}
	@media(max-width:620px){.bdsu-home-page .bdsu-home-services--shop{padding-top:64px}.bdsu-home-page .bdsu-home-services--shop .bdsu-section-actions{width:100%}.bdsu-home-page .bdsu-home-services--shop .bdsu-section-actions .bdsu-primary,.bdsu-home-page .bdsu-home-services--shop .bdsu-section-actions .bdsu-secondary{flex:1;justify-content:center}.bdsu-home-page .bdsu-home-services--shop .bdsu-card-bottom,.bdsu-service-card--text .bdsu-card-bottom{flex-direction:column;align-items:stretch}.bdsu-home-page .bdsu-home-services--shop .bdsu-card-button,.bdsu-service-card--text .bdsu-card-button{width:100%;text-align:center}}
	</style>
<?php }
add_action( 'wp_head', 'bdsu_service_styles', 100 );

function bdsu_standard_styles() { ?>
	<style>
	.bdsu-primary:not(.bdsu-primary--ghost),.bdsu-primary:not(.bdsu-primary--ghost):link,.bdsu-primary:not(.bdsu-primary--ghost):visited,.bdsu-primary:not(.bdsu-primary--ghost):hover,.bdsu-card-button,.bdsu-card-button:link,.bdsu-card-button:visited,.bdsu-card-button:hover,.bdsu-directory-cta>a,.bdsu-directory-cta>a:visited,.bdsu-final-cta>a,.bdsu-final-cta>a:visited,.bdsu-sticky-help>a,.bdsu-sticky-help>a:visited,body.branddad-unified .elementor-location-header .bdsu-list-business>a,body.branddad-unified .elementor-location-header .bdsu-list-business>a:visited{color:#fff!important}.bdsu-primary:not(.bdsu-primary--ghost) *,.bdsu-card-button *,.bdsu-directory-cta>a *,.bdsu-final-cta>a *,.bdsu-sticky-help>a *,.bdsu-list-business>a *{color:#fff!important}
	/* Lane / shop CTAs must stay readable even if theme link styles intervene */
	.bdsu-primary--ghost,.bdsu-primary--ghost:link,.bdsu-primary--ghost:visited,.bdsu-primary--ghost:hover{background:#0260d9!important;color:#fff!important;border:2px solid #0260d9!important}
	.bdsu-hero-actions .bdsu-secondary,.bdsu-hero-actions .bdsu-secondary:link,.bdsu-hero-actions .bdsu-secondary:visited{background:#fff!important;color:#0f172a!important;border:2px solid #0f172a!important}
	.bdsu-filter-bar button{border:2px solid #334155!important;background:#fff!important;color:#0f172a!important}
	.bdsu-filter-bar button:hover,.bdsu-filter-bar button.is-active{border-color:#0260d9!important;background:#0260d9!important;color:#fff!important}
	.bdsu-services-convert .bdsu-primary:not(.bdsu-primary--ghost),.bdsu-services-convert .bdsu-card-button{background:#0260d9!important}
	/* Dark chrome: keep the services layout, fix black-on-black in #lane-local and siblings */
	html[data-bds-theme="dark"] .bdsu-services-convert{--bdsu-navy:#e8eef7;--bdsu-muted:#94a3b8;--bdsu-line:#243044;--bdsu-soft:#13233d}
	html[data-bds-theme="dark"] .bdsu-services-page,html[data-bds-theme="dark"] .bdsu-services-convert,html[data-bds-theme="dark"] #lane-local,html[data-bds-theme="dark"] .bdsu-lane,html[data-bds-theme="dark"] .bdsu-service-section{color:#e8eef7}
	html[data-bds-theme="dark"] #lane-local .bdsu-section-heading h2,html[data-bds-theme="dark"] .bdsu-lane .bdsu-section-heading h2,html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-section-heading h2{color:#e8eef7!important}
	html[data-bds-theme="dark"] #lane-local .bdsu-section-heading p,html[data-bds-theme="dark"] .bdsu-lane .bdsu-section-heading p,html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-lane-check__copy span{color:#94a3b8!important}
	html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-lane-check{background:linear-gradient(135deg,#13233d 0%,#121a2b 55%);border-color:#243044}
	html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-lane-check__copy strong{color:#e8eef7!important}
	html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-lane-check__actions .bdsu-secondary{background:#121a2b!important;color:#e8eef7!important;border-color:#94a3b8!important}
	html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-service-card,html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-ai-tier{background:#151e31;border-color:#243044}
	html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-service-card h3,html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-card-price,html[data-bds-theme="dark"] .bdsu-services-convert .bdsu-ai-price strong{color:#e8eef7}
	html[data-bds-theme="dark"] .bds-edu-hub,html[data-bds-theme="dark"] .bds-edu-hub h1,html[data-bds-theme="dark"] .bds-edu-hub h2,html[data-bds-theme="dark"] .bds-edu-hub h3{color:#e8eef7}
	html[data-bds-theme="dark"] .bds-edu-hub p,html[data-bds-theme="dark"] .bds-edu-hub span{color:#94a3b8}
	body.page-id-158 .page-header,body.page-id-124 .page-header{display:none!important}.bdsu-product-value{display:grid;gap:7px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px;margin:18px 0}.bdsu-product-value strong{color:#0f172a}.bdsu-product-value span{color:#475569;font-size:14px;line-height:1.5}.bdsu-product-value a{color:#0372ff;font-weight:800;text-decoration:none;font-size:14px}
	.bdsu-standard-page{background:#f8fafc;min-height:100vh;padding-bottom:80px}.page:has(.bdsu-standard-page) .page-header{display:none}.bdsu-standard-hero{max-width:1040px;margin:auto;padding:120px 24px 72px;text-align:center}.bdsu-standard-hero h1{font-size:clamp(44px,6vw,72px);line-height:1.04;margin:16px auto;max-width:900px}.bdsu-standard-hero>p{font-size:20px;line-height:1.7;color:#475569;max-width:800px;margin:0 auto 28px}.bdsu-story-grid{max-width:1130px;margin:0 auto 70px;padding:0 24px;display:grid;grid-template-columns:1fr 1fr;gap:24px}.bdsu-story-grid>article{background:#0f172a;color:#fff;border-radius:22px;padding:38px}.bdsu-story-grid>article span{color:#93c5fd;font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:12px}.bdsu-story-grid>article h2{color:#fff;font-size:40px}.bdsu-story-grid>article p{color:#cbd5e1;line-height:1.7}.bdsu-values{display:grid;gap:14px}.bdsu-values>div{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:22px}.bdsu-values b{color:#0372ff}.bdsu-values h3{margin:8px 0}.bdsu-values p{margin:0;color:#64748b}.bdsu-about-ecosystem{padding-top:70px}.bdsu-contact-wrap{max-width:1120px;margin:auto;padding:0 24px;display:grid;grid-template-columns:.75fr 1.25fr;gap:24px}.bdsu-contact-wrap>aside,.bdsu-contact-form{border-radius:22px;padding:34px}.bdsu-contact-wrap>aside{background:#0f172a;color:#fff}.bdsu-contact-wrap>aside>span{color:#93c5fd;font-weight:800;text-transform:uppercase;font-size:12px;letter-spacing:.08em}.bdsu-contact-wrap>aside h2,.bdsu-contact-wrap>aside h3{color:#fff}.bdsu-contact-wrap>aside p,.bdsu-contact-wrap>aside li{color:#cbd5e1;line-height:1.65}.bdsu-contact-wrap>aside>a{color:#93c5fd;font-size:20px;font-weight:800}.bdsu-contact-wrap>aside hr{border:0;border-top:1px solid #334155;margin:30px 0}.bdsu-contact-form{background:#fff;border:1px solid #e2e8f0;box-shadow:0 16px 40px rgba(15,23,42,.06)}.bdsu-contact-form h2{font-size:34px;margin-top:0}.bdsu-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.bdsu-form-grid label{display:grid;gap:7px;font-weight:700;color:#334155}.bdsu-form-grid input,.bdsu-form-grid select,.bdsu-form-grid textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:12px;background:#fff}.bdsu-form-grid input:focus,.bdsu-form-grid select:focus,.bdsu-form-grid textarea:focus{outline:2px solid #bfdbfe;border-color:#0372ff}.bdsu-full{grid-column:1/-1}.bdsu-contact-form button{margin-top:18px;background:#0372ff;color:#fff;border:0;border-radius:10px;padding:14px 20px;font-weight:800}.bdsu-privacy-note{font-size:12px;color:#64748b}.bdsu-form-success,.bdsu-form-error{padding:13px;border-radius:9px;margin-bottom:16px}.bdsu-form-success{background:#ecfdf5;border:1px solid #10b981}.bdsu-form-error{background:#fef2f2;border:1px solid #ef4444}@media(max-width:800px){.bdsu-story-grid,.bdsu-contact-wrap{grid-template-columns:1fr}.bdsu-standard-hero{padding-top:80px}}@media(max-width:560px){.bdsu-form-grid{grid-template-columns:1fr}.bdsu-full{grid-column:auto}}
	</style>
<?php }
add_action( 'wp_head', 'bdsu_standard_styles', 101 );

function bdsu_growth_advisor() {
	if ( is_admin() ) return;
	?>
	<div class="bdsu-contact-dock" aria-label="BrandDad help options">
		<div class="bdsu-contact-menu" id="bdsu-contact-menu" hidden>
			<strong>How can BrandDad help?</strong><span>Get a recommendation or message us.</span>
			<button type="button" class="bdsu-contact-choice bdsu-quiz-open">Find my best service <b>→</b></button>
			<a href="https://affiliates.branddad.social/">Earn 30% recurring <b>→</b></a>
			<a href="https://wa.me/18729105115?text=Help%20me%20choose%20a%20BrandDad%20service">WhatsApp <b>→</b></a>
			<a href="https://www.facebook.com/branddad.social" target="_blank" rel="noopener">Facebook <b>→</b></a>
			<a href="https://www.instagram.com/branddadsocial/" target="_blank" rel="noopener">Instagram <b>→</b></a>
		</div>
		<button type="button" class="bdsu-contact-toggle" aria-expanded="false" aria-controls="bdsu-contact-menu"><span>Need help?</span><b>💬</b></button>
	</div>
	<div class="bdsu-quiz-modal" id="bdsu-growth-quiz" hidden>
		<div class="bdsu-quiz-backdrop" data-bdsu-close></div>
		<section class="bdsu-quiz-card" role="dialog" aria-modal="true" aria-labelledby="bdsu-quiz-title">
			<button type="button" class="bdsu-quiz-close" data-bdsu-close aria-label="Close">×</button>
			<div class="bdsu-quiz-intro"><span>BrandDad Growth Advisor</span><h2 id="bdsu-quiz-title">Find the service that fits you.</h2><p>Answer five quick questions. We’ll recommend the most useful next step—without collecting your information.</p></div>
			<form class="bdsu-quiz-form">
				<label>Who are you?<select name="role" required><option value="">Choose one</option><option value="business">Business owner</option><option value="creator">Creator or influencer</option><option value="professional">Professional or consultant</option><option value="artist">Artist, musician or entertainer</option><option value="organization">Organization or nonprofit</option></select></label>
				<label>What is your main goal?<select name="goal" required><option value="">Choose one</option><option value="visibility">Reach more people</option><option value="leads">Get leads or customers</option><option value="search">Rank higher on Google</option><option value="reputation">Improve or protect my reputation</option><option value="consistency">Post consistently</option><option value="launch">Promote a launch or announcement</option></select></label>
				<label>Which platform matters most?<select name="platform" required><option value="">Choose one</option><option value="instagram">Instagram</option><option value="facebook">Facebook</option><option value="linkedin">LinkedIn</option><option value="video">TikTok or YouTube</option><option value="google">Google or my website</option><option value="multiple">Several platforms</option></select></label>
				<label>What is getting in the way?<select name="challenge" required><option value="">Choose one</option><option value="website">I need or need to improve a website</option><option value="following">My following is too small</option><option value="engagement">My engagement is too low</option><option value="time">I do not have time to manage it</option><option value="reviews">Reviews or reputation problems</option><option value="unsure">I am not sure what I need</option></select></label>
				<label>What budget feels comfortable?<select name="budget" required><option value="">Choose one</option><option value="starter">Under $250</option><option value="growth">$250–$999</option><option value="pro">$1,000–$2,499</option><option value="scale">$2,500+</option></select></label>
				<button type="submit" class="bdsu-quiz-submit">Show My Recommendation</button>
			</form>
			<div class="bdsu-quiz-result" hidden></div>
		</section>
	</div>
	<?php
}
add_action( 'wp_footer', 'bdsu_growth_advisor', 80 );

function bdsu_growth_advisor_assets() {
	if ( is_admin() ) return;
	?>
	<style>
	.bdsu-affiliate-promo{max-width:1180px;margin:70px auto 24px;padding:38px;background:linear-gradient(135deg,#052e2b,#0f172a);color:#fff;border-radius:22px;display:grid;grid-template-columns:1.3fr .8fr auto;gap:30px;align-items:center;box-shadow:0 20px 55px rgba(15,23,42,.16)}.bdsu-affiliate-promo span{color:#6ee7b7;font-size:12px;font-weight:850;text-transform:uppercase;letter-spacing:.09em}.bdsu-affiliate-promo h2{color:#fff!important;font-size:clamp(28px,4vw,42px);margin:7px 0}.bdsu-affiliate-promo p{color:#cbd5e1;margin:0;line-height:1.6}.bdsu-affiliate-benefits{display:grid;gap:8px}.bdsu-affiliate-benefits b{color:#d1fae5}.bdsu-affiliate-promo>a{background:#10b981;color:#fff!important;text-decoration:none;padding:14px 18px;border-radius:10px;font-weight:850;text-align:center;white-space:nowrap}.bdsu-contact-dock{position:fixed;right:22px;bottom:22px;z-index:99997;display:grid;justify-items:end;gap:10px}.bdsu-contact-toggle{border:0!important;border-radius:999px!important;background:#0372ff!important;color:#fff!important;box-shadow:0 14px 34px rgba(3,114,255,.32);padding:11px 14px 11px 18px!important;display:flex;align-items:center;gap:10px;font-weight:800;cursor:pointer}.bdsu-contact-toggle span,.bdsu-contact-toggle b{color:#fff!important}.bdsu-contact-toggle b{width:34px;height:34px;background:#fff2;border-radius:50%;display:grid;place-items:center}.bdsu-contact-menu{width:min(320px,calc(100vw - 32px));background:#fff;border:1px solid #dbeafe;border-radius:17px;padding:18px;box-shadow:0 22px 60px rgba(15,23,42,.2);display:grid;gap:8px}.bdsu-contact-menu[hidden]{display:none}.bdsu-contact-menu>strong{font-size:18px;color:#0f172a}.bdsu-contact-menu>span{font-size:13px;color:#64748b;margin-bottom:5px}.bdsu-contact-menu a,.bdsu-contact-choice{width:100%;box-sizing:border-box;border:1px solid #e2e8f0!important;background:#fff!important;color:#0f172a!important;border-radius:10px!important;padding:12px 13px!important;text-decoration:none;display:flex;justify-content:space-between;font-weight:750;cursor:pointer}.bdsu-contact-menu a:hover,.bdsu-contact-choice:hover{border-color:#60a5fa!important;background:#eff6ff!important}.bdsu-contact-menu b{color:#0372ff}.bdsu-quiz-modal{position:fixed;inset:0;z-index:99999;display:grid;place-items:center;padding:20px}.bdsu-quiz-modal[hidden]{display:none}.bdsu-quiz-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.72);backdrop-filter:blur(4px)}.bdsu-quiz-card{position:relative;width:min(760px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:22px;padding:34px;box-shadow:0 28px 90px rgba(0,0,0,.3)}.bdsu-quiz-close{position:absolute;right:16px;top:14px;border:0!important;background:#f1f5f9!important;color:#334155!important;border-radius:50%!important;width:38px;height:38px;font-size:25px;cursor:pointer}.bdsu-quiz-intro>span{color:#0372ff;font-size:12px;font-weight:850;text-transform:uppercase;letter-spacing:.09em}.bdsu-quiz-intro h2{font-size:clamp(30px,5vw,45px);margin:8px 44px 8px 0}.bdsu-quiz-intro p{color:#64748b;line-height:1.6}.bdsu-quiz-form{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-top:24px}.bdsu-quiz-form label{display:grid;gap:7px;color:#334155;font-weight:750}.bdsu-quiz-form select{width:100%;padding:13px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#0f172a}.bdsu-quiz-submit{grid-column:1/-1;border:0!important;border-radius:10px!important;padding:14px 18px!important;background:#0372ff!important;color:#fff!important;font-weight:850;cursor:pointer}.bdsu-quiz-result{background:#eff6ff;border:1px solid #bfdbfe;border-radius:16px;padding:24px;margin-top:22px}.bdsu-quiz-result h3{font-size:28px;margin:0 0 9px}.bdsu-quiz-result p{color:#475569;line-height:1.65}.bdsu-result-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.bdsu-result-actions a{padding:12px 15px;border-radius:9px;background:#0372ff;color:#fff!important;text-decoration:none;font-weight:800}.bdsu-result-actions a:last-child{background:#fff;color:#0f172a!important;border:1px solid #cbd5e1}@media(max-width:850px){.bdsu-affiliate-promo{grid-template-columns:1fr;margin-inline:20px}.bdsu-affiliate-benefits{grid-template-columns:repeat(3,1fr)}}@media(max-width:650px){.bdsu-affiliate-benefits{grid-template-columns:1fr}.bdsu-quiz-form{grid-template-columns:1fr}.bdsu-quiz-submit{grid-column:auto}.bdsu-quiz-card{padding:27px 20px}.bdsu-contact-dock{right:14px;bottom:14px}.bdsu-contact-toggle span{display:none}}
	</style>
	<script>
	document.addEventListener('DOMContentLoaded',function(){
		var modal=document.getElementById('bdsu-growth-quiz'),menu=document.getElementById('bdsu-contact-menu'),toggle=document.querySelector('.bdsu-contact-toggle');
		function openQuiz(){if(!modal)return;modal.hidden=false;document.body.style.overflow='hidden';if(menu)menu.hidden=true;if(toggle)toggle.setAttribute('aria-expanded','false')}
		function closeQuiz(){if(!modal)return;modal.hidden=true;document.body.style.overflow=''}
		if(toggle)toggle.addEventListener('click',function(){menu.hidden=!menu.hidden;toggle.setAttribute('aria-expanded',String(!menu.hidden))});
		document.addEventListener('click',function(e){if(e.target.closest('.bdsu-quiz-open')){e.preventDefault();openQuiz()}if(e.target.closest('[data-bdsu-close]'))closeQuiz()});
		document.addEventListener('keydown',function(e){if(e.key==='Escape')closeQuiz()});
		var form=document.querySelector('.bdsu-quiz-form');if(!form)return;
		function bdsuPo(url){try{var u=new URL(url,location.origin);if(!u.searchParams.get('ref')){var m=location.search.match(/[?&]ref=([^&]+)/);var ref=m?decodeURIComponent(m[1]):'';if(!ref){var c=document.cookie.match(/(?:^|; )partnero_partner=([^;]+)/);if(c)ref=decodeURIComponent(c[1]);}if(ref)u.searchParams.set('ref',ref);}return u.toString();}catch(err){return url;}}
		form.addEventListener('submit',function(e){e.preventDefault();var d=new FormData(form),goal=d.get('goal'),platform=d.get('platform'),challenge=d.get('challenge'),budget=d.get('budget');var title='Social Media Management',desc='A consistent multi-platform plan will give you the clearest next step.',url='https://branddad.social/product/social-media-management/';
			if(challenge==='website'){title='Website SEO Audit';desc='Start with an explainable site audit, then choose fixes, speed, or conversion work — no ranking promises.';url='https://branddad.social/product/website-seo-audit/'}
			else if(goal==='leads'&&(budget==='pro'||budget==='scale')){title='AI Ads Setup';desc='Connect ad accounts, verify tracking, and get AI campaign drafts before you scale paid spend.';url='https://branddad.social/product/bd-ai-ads-setup/'}
			else if(goal==='search'||platform==='google'){title='Local & Web Services';desc='GBP, SEO audit/fixes, speed, and care packages for businesses that need stronger search and site foundations.';url='https://branddad.social/services/#lane-local'}
			else if(goal==='reputation'||challenge==='reviews'){title='Review Growth Setup';desc='Build an ethical ask-flow for real Google reviews, or choose reputation assistance if you have harmful reviews.';url='https://branddad.social/product/google-review-growth-setup/'}
			else if(platform==='linkedin'){title='LinkedIn Visibility';desc='Build professional authority and a repeatable path to conversations.';url='https://branddad.social/product/linkedin-visibility-amplification-system-for-professionals/'}
			else if(platform==='instagram'||challenge==='following'||challenge==='engagement'){title='Instagram Growth';desc='Improve discovery and engagement with a focused Instagram growth system.';url='https://branddad.social/product/instagram-viral-growth-discovery-system/'}
			else if(platform==='facebook'){title='Facebook Visibility';desc='Reach more relevant people and turn Facebook into a stronger awareness channel.';url='https://branddad.social/product/facebook-growth-visibility-campaigns/'}
			else if(goal==='launch'){title=budget==='scale'?'Times Square Billboard':'Press Release Distribution';desc='Turn your launch into a credible announcement that earns attention beyond your existing audience.';url=budget==='scale'?'https://branddad.social/product/nyc-times-square-billboard-video-ads/':'https://branddad.social/product/press-release-services/'}
			else if(goal==='consistency'||challenge==='time'){title='Social Media Management';desc='Get a repeatable content and engagement system without managing every task yourself.';url='https://branddad.social/product/social-media-management/'}
			url=bdsuPo(url);
			var result=document.querySelector('.bdsu-quiz-result');result.innerHTML='<span>Your recommended starting point</span><h3>'+title+'</h3><p>'+desc+'</p><div class="bdsu-result-actions"><a href="'+url+'">View Recommended Service</a><a href="https://wa.me/18729105115?text=I%20completed%20the%20BrandDad%20quiz%20and%20want%20help%20with%20'+encodeURIComponent(title)+'">Confirm on WhatsApp</a></div>';result.hidden=false;result.scrollIntoView({behavior:'smooth',block:'nearest'});
		});
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'bdsu_growth_advisor_assets', 90 );
