<?php
/**
 * Plugin Name: BrandDad Revenue Sections
 * Description: Adds the missing Social catalog, package, music-brief, and Directory advertising sections. Checkout stays closed unless the commercial catalog already exposes a live product URL.
 * Version: 0.2.5
 * Author: BrandDad
 *
 * Secure design: public music briefs are untrusted text. Fields are allowlisted, length-capped, and stored as private posts. A honeypot and a per-IP transient cap abuse. No card data is collected. Package price is never taken from the browser. Contact `offer` is sanitize_title against the hard-coded catalog plus featured inquiry slugs. Checkout URLs come only from the commercial catalog when a published product exists. The homepage strip only emits Subscribe when that catalog already returns a URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bds_rev_is_social() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

/**
 * Proposed offers. launch_state staged or quote_only cannot check out from this plugin.
 *
 * @return array<int,array<string,string>>
 */
function bds_rev_offers() {
	return array(
		array( 'slug' => 'ai-business-monitoring', 'name' => 'AI Business Monitoring', 'price' => '$9.99/month', 'state' => 'staged', 'who' => 'A business that wants a simple website check.' ),
		array( 'slug' => 'ai-review-assistant', 'name' => 'AI Review Assistant', 'price' => '$9.99/month', 'state' => 'staged', 'who' => 'A business that replies to reviews itself.' ),
		array( 'slug' => 'ai-social-starter', 'name' => 'AI Social Starter', 'price' => '$14.99/month', 'state' => 'staged', 'who' => 'A business posting a small monthly set of drafts.' ),
		array( 'slug' => 'ai-lead-assistant', 'name' => 'AI Lead Assistant', 'price' => '$19.99/month', 'state' => 'catalog', 'who' => 'A business capturing inquiries from one form.' ),
		array( 'slug' => 'ai-local-seo', 'name' => 'AI Local SEO Starter', 'price' => '$19.99/month', 'state' => 'catalog', 'who' => 'One website and one primary location.' ),
		array( 'slug' => 'ai-social-growth', 'name' => 'AI Social Growth', 'price' => '$29.99/month', 'state' => 'catalog', 'who' => 'A business that wants a monthly content calendar.' ),
		array( 'slug' => 'ai-linkedin-growth', 'name' => 'AI LinkedIn Growth', 'price' => '$29.99/month', 'state' => 'staged', 'who' => 'One LinkedIn presence. Automated posting is not included.' ),
		array( 'slug' => 'ai-website-chatbot', 'name' => 'AI Website Chatbot', 'price' => '$29.99/month', 'state' => 'staged', 'who' => 'A site that needs an approved-knowledge assistant.' ),
		array( 'slug' => 'ai-business-essentials', 'name' => 'AI Business Essentials', 'price' => '$29.99/month', 'state' => 'catalog', 'who' => 'The flagship everyday plan.' ),
		array( 'slug' => 'ai-local-seo-autopilot', 'name' => 'AI Local SEO Autopilot', 'price' => '$39.99/month', 'state' => 'staged', 'who' => 'Local SEO execution after Codex confirms the engine.' ),
		array( 'slug' => 'ai-business-essentials-pro', 'name' => 'Business Essentials Pro', 'price' => '$49.99/month', 'state' => 'staged', 'who' => 'A busier business that needs higher draft limits.' ),
		array( 'slug' => 'ai-seo-growth-autopilot', 'name' => 'AI SEO Growth Autopilot', 'price' => '$59.99/month', 'state' => 'staged', 'who' => 'SEO workflows after fulfillment is verified.' ),
		array( 'slug' => 'ai-seo-pro', 'name' => 'AI SEO Pro', 'price' => '$99.99/month', 'state' => 'staged', 'who' => 'The highest proposed SEO allowance.' ),
		array( 'slug' => 'complete-ai-starter', 'name' => 'Complete AI Starter', 'price' => '$79.99/month', 'state' => 'staged', 'who' => '12 social drafts, 2 channels, 1 autoblog, 100 chatbot conversations. Entitlements are proposed.' ),
		array( 'slug' => 'complete-ai-growth', 'name' => 'Complete AI Growth', 'price' => '$149.99/month', 'state' => 'staged', 'who' => '30 social drafts, 4 channels, 2 autoblogs, 500 chatbot conversations. Entitlements are proposed.' ),
		array( 'slug' => 'complete-ai-pro', 'name' => 'Complete AI Pro', 'price' => '$249.99/month', 'state' => 'staged', 'who' => '60 social drafts, 6 channels, 4 autoblogs, 1,500 chatbot conversations. Entitlements are proposed.' ),
		array( 'slug' => 'done-for-you-ai', 'name' => 'Done-for-You AI Management', 'price' => 'From $399/month', 'state' => 'quote_only', 'who' => 'A scoped management agreement. Not a self-serve checkout.' ),
	);
}

function bds_rev_music_packages() {
	return array(
		'original-single' => array( 'Original Single', '$79' ),
		'premium-single'  => array( 'Premium Single + Artwork', '$129' ),
		'ep-3'            => array( '3-Song EP', '$249' ),
		'album-6'         => array( '6-Song Album', '$449' ),
		'album-12'        => array( '12-Song Album', '$799' ),
		'partnership'     => array( 'Monthly Music Partnership', 'From $199' ),
	);
}

function bds_rev_offer_url( $slug ) {
	$url = '';
	if ( function_exists( 'bds_ai_commercial_checkout_url' ) ) {
		$got = bds_ai_commercial_checkout_url( $slug );
		if ( is_string( $got ) && '' !== $got ) {
			$url = $got;
		}
	}
	if ( '' !== $url && function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $url );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $url;
}

function bds_rev_card( array $offer ) {
	$url   = bds_rev_offer_url( $offer['slug'] );
	$ask   = add_query_arg( 'offer', rawurlencode( $offer['slug'] ), home_url( '/contact-us/' ) );
	$html  = '<article class="bds-rev-card"><h3>' . esc_html( $offer['name'] ) . '</h3>';
	$html .= '<p class="bds-rev-price">' . esc_html( $offer['price'] ) . '</p>';
	$html .= '<p>' . esc_html( $offer['who'] ) . '</p>';
	if ( '' !== $url ) {
		$html .= '<a class="bds-rev-button" href="' . esc_url( $url ) . '">' . esc_html__( 'Subscribe now', 'branddad' ) . '</a>';
	} else {
		$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( $ask ) . '">' . esc_html__( 'Ask about this plan', 'branddad' ) . '</a>';
		$html .= '<p class="bds-rev-note">' . esc_html__( 'Purchase stays closed until fulfillment and billing are verified.', 'branddad' ) . '</p>';
	}
	return $html . '</article>';
}

function bds_rev_dir_url( $path ) {
	$url = 'https://directory.branddad.social' . $path;
	if ( function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $url );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $url;
}

/**
 * Header "Get Featured" currently points at Directory home. Send that item
 * to the Featured buy page. Leave /get-featured/ (Verified pitch) alone.
 *
 * @param string $title Menu label.
 * @param string $url   Current href.
 * @return string
 */
function bds_rev_menu_featured_url( $title, $url ) {
	if ( 0 !== strcasecmp( trim( wp_strip_all_tags( (string) $title ) ), 'Get Featured' ) ) {
		return (string) $url;
	}
	$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
	$path = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
	if ( 'directory.branddad.social' === $host && '' === trim( $path, '/' ) ) {
		return bds_rev_dir_url( '/featured-advertising/' );
	}
	if ( false !== strpos( (string) $path, 'contact-us' ) ) {
		return bds_rev_dir_url( '/featured-advertising/' );
	}
	return (string) $url;
}

/**
 * "AI Packages" is stored as the homepage. Send it to the services catalog.
 *
 * @param string $title Menu label.
 * @param string $url   Current href.
 * @return string
 */
function bds_rev_menu_packages_url( $title, $url ) {
	if ( 0 !== strcasecmp( trim( wp_strip_all_tags( (string) $title ) ), 'AI Packages' ) ) {
		return (string) $url;
	}
	$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
	$path = trim( (string) wp_parse_url( (string) $url, PHP_URL_PATH ), '/' );
	if ( '' !== $path || ! in_array( $host, array( 'branddad.social', 'www.branddad.social', '' ), true ) ) {
		return (string) $url;
	}
	$dest = home_url( '/services/' );
	if ( function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $dest );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $dest;
}

function bds_rev_fix_featured_menu( $items ) {
	if ( ! is_array( $items ) || is_admin() || ! bds_rev_is_social() ) {
		return $items;
	}
	foreach ( $items as $item ) {
		if ( ! is_object( $item ) ) {
			continue;
		}
		$title = isset( $item->title ) ? (string) $item->title : '';
		$url   = isset( $item->url ) ? (string) $item->url : '';
		$item->url = bds_rev_menu_packages_url( $title, bds_rev_menu_featured_url( $title, $url ) );
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'bds_rev_fix_featured_menu', 20 );

/**
 * Live launch copy still sends Featured to Contact. Point that one button at the buy page.
 *
 * @param string $html Untrusted page HTML already rendered by WordPress.
 * @return string
 */
function bds_rev_rewrite_featured_ask( $html ) {
	$html = (string) $html;
	if ( false === strpos( $html, 'Ask about Featured placement' ) ) {
		return $html;
	}
	$dest = esc_url( bds_rev_dir_url( '/featured-advertising/' ) );
	$out  = preg_replace_callback(
		'#<a([^>]*?)href="(?:https://(?:www\.)?branddad\.social)?/contact-us/[^"]*"([^>]*)>Ask about Featured placement</a>#',
		static function ( $m ) use ( $dest ) {
			return '<a' . $m[1] . 'href="' . $dest . '"' . $m[2] . '>Get featured</a>';
		},
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_label_link( $html, $label, $href_pattern, $dest, $new_label ) {
	$html = (string) $html;
	$dest = esc_url( (string) $dest );
	if ( '' === $dest || false === strpos( $html, $label ) ) {
		return $html;
	}
	$out = preg_replace_callback(
		'~<a([^>]*?)href="' . $href_pattern . '"([^>]*)>' . preg_quote( $label, '~' ) . '</a>~',
		static function ( $m ) use ( $dest, $new_label ) {
			return '<a' . $m[1] . 'href="' . $dest . '"' . $m[2] . '>' . esc_html( $new_label ) . '</a>';
		},
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_choose_plan( $html ) {
	$html = (string) $html;
	if ( false === strpos( $html, 'Choose this plan' ) ) {
		return $html;
	}
	$out = preg_replace(
		'#(<a\b[^>]*href="https://(?:www\.)?branddad\.social/product/(?:ai-lead-assistant|ai-social-growth|ai-local-seo|ai-business-essentials)/[^"]*"[^>]*)>Choose this plan</a>#',
		'$1>Subscribe</a>',
		$html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_conversion_ctas( $html ) {
	$html   = bds_rev_rewrite_featured_ask( $html );
	$html   = bds_rev_rewrite_choose_plan( $html );
	$pairs  = array(
		array( 'See social plans', 'ai-social-growth', 'Subscribe to Social Growth', 'ai-social-growth' ),
		array( 'See SEO options', 'ai-local-seo', 'Subscribe to Local SEO', 'ai-local-seo' ),
	);
	foreach ( $pairs as $pair ) {
		$url = bds_rev_offer_url( $pair[1] );
		if ( '' === $url ) {
			continue;
		}
		$hash    = preg_quote( $pair[3], '~' );
		$pattern = '(?:https://(?:www\.)?branddad\.social)?/services/#' . $hash;
		$html    = bds_rev_rewrite_label_link( $html, $pair[0], $pattern, $url, $pair[2] );
	}
	return $html;
}

function bds_rev_rewrite_featured_ask_content( $content ) {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return $content;
	}
	return bds_rev_rewrite_conversion_ctas( $content );
}
add_filter( 'the_content', 'bds_rev_rewrite_featured_ask_content', 1100 );

function bds_rev_product_strip( $content ) {
	if ( ! bds_rev_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'id="bds-rev-home-strip"' ) ) {
		return $content;
	}
	return $content . bds_rev_home_strip();
}
add_filter( 'the_content', 'bds_rev_product_strip', 1001 );

function bds_rev_featured_inquiry_offers() {
	return array(
		array( 'slug' => 'featured-local', 'name' => 'Local Featured', 'price' => '$9.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants nearby Directory placement.' ),
		array( 'slug' => 'featured-regional', 'name' => 'Regional Featured', 'price' => '$19.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants a wider regional lane.' ),
		array( 'slug' => 'featured-national', 'name' => 'National Featured', 'price' => '$39.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants nationwide Directory placement.' ),
		array( 'slug' => 'featured-global', 'name' => 'Global Homepage Featured', 'price' => '$99.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants the Directory homepage lane.' ),
	);
}

function bds_rev_split_offer_cards() {
	$live = '';
	$ask  = '';
	foreach ( bds_rev_offers() as $offer ) {
		if ( '' !== bds_rev_offer_url( $offer['slug'] ) ) {
			$live .= bds_rev_card( $offer );
		} else {
			$ask .= bds_rev_card( $offer );
		}
	}
	return array( $live, $ask );
}

function bds_rev_markup() {
	$audit     = esc_url( home_url( '/check-your-website/' ) );
	$directory = esc_url( bds_rev_dir_url( '/add-listing/' ) );
	$featured  = esc_url( bds_rev_dir_url( '/featured-advertising/' ) );
	$browse    = esc_url( bds_rev_dir_url( '/' ) );
	list( $live, $ask ) = bds_rev_split_offer_cards();
	$music = bds_rev_music_options_html();
	ob_start();
	?>
	<div class="bds-rev" id="bds-revenue-sections">
		<section aria-labelledby="bds-rev-audit">
			<h2 id="bds-rev-audit"><?php esc_html_e( 'Start with a free AI business audit.', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'The website check explains what to fix first. It does not invent rankings or promise revenue.', 'branddad' ); ?></p>
			<a class="bds-rev-button" href="<?php echo $audit; ?>"><?php esc_html_e( 'Run the free audit', 'branddad' ); ?></a>
		</section>
		<?php if ( '' !== $live ) : ?>
		<section aria-labelledby="bds-rev-live">
			<h2 id="bds-rev-live"><?php esc_html_e( 'Plans you can buy now', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'These products are already published. Subscribe opens the live checkout. Directory members keep their existing 10% where the product allows it.', 'branddad' ); ?></p>
			<div class="bds-rev-grid"><?php echo $live; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>
		<?php endif; ?>
		<section aria-labelledby="bds-rev-directory">
			<h2 id="bds-rev-directory"><?php esc_html_e( 'Free Directory listing and featured placement', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'Create or claim a listing, then request Featured: Local $9.99, Regional $19.99, National $39.99, or Global Homepage $99.99 per month. Checkout opens only when that Directory product is already for sale.', 'branddad' ); ?></p>
			<a class="bds-rev-button" href="<?php echo $directory; ?>"><?php esc_html_e( 'List a business', 'branddad' ); ?></a>
			<a class="bds-rev-button" href="<?php echo $featured; ?>"><?php esc_html_e( 'Get featured', 'branddad' ); ?></a>
			<a class="bds-rev-button bds-rev-button--quiet" href="<?php echo $browse; ?>"><?php esc_html_e( 'See the Directory', 'branddad' ); ?></a>
		</section>
		<section aria-labelledby="bds-rev-music">
			<h2 id="bds-rev-music"><?php esc_html_e( 'BrandDad Music for Business', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'Original music commissioned for a business. Artist T Dash. Songwriter Thaddeus McCollum. Production by BrandDad Music. Sold by BrandDad Social. This form requests a brief. It does not take payment or start production.', 'branddad' ); ?></p>
			<?php echo bds_rev_music_form( $music ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php if ( '' !== $ask ) : ?>
		<section aria-labelledby="bds-rev-catalog">
			<h2 id="bds-rev-catalog"><?php esc_html_e( 'Upcoming plans — inquiry only', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'These prices are proposed. Ask about a plan and we confirm fulfillment before any checkout opens.', 'branddad' ); ?></p>
			<div class="bds-rev-grid"><?php echo $ask; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function bds_rev_home_strip() {
	$html  = '<section class="bds-rev" id="bds-rev-home-strip">';
	$html .= '<h2>' . esc_html__( 'Buy a live plan or get featured', 'branddad' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Subscribe opens only for products already for sale. Featured is a Directory request until that Woo product exists. Music stays a brief, not a sale.', 'branddad' ) . '</p><p>';
	foreach ( bds_rev_offers() as $offer ) {
		$url = bds_rev_offer_url( $offer['slug'] );
		if ( '' === $url ) {
			continue;
		}
		$label = sprintf(
			/* translators: %s: live plan name */
			__( 'Subscribe to %s', 'branddad' ),
			$offer['name']
		);
		$html .= '<a class="bds-rev-button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	$html .= '<a class="bds-rev-button" href="' . esc_url( bds_rev_dir_url( '/featured-advertising/' ) ) . '">' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( bds_rev_dir_url( '/add-listing/' ) ) . '">' . esc_html__( 'List a business', 'branddad' ) . '</a>';
	$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( home_url( '/services/' ) ) . '">' . esc_html__( 'See all services', 'branddad' ) . '</a>';
	$html .= '</p></section>';
	return $html;
}

function bds_rev_inject_before_final( $content, $block ) {
	$pos = strpos( $content, '<section class="bdai-final"' );
	if ( false !== $pos ) {
		return substr( $content, 0, $pos ) . $block . substr( $content, $pos );
	}
	return $content . $block;
}

function bds_rev_music_options_html( $packages = null ) {
	if ( ! is_array( $packages ) ) {
		$packages = bds_rev_music_packages();
	}
	$html = '';
	foreach ( $packages as $slug => $pack ) {
		if ( ! is_array( $pack ) || ! isset( $pack[0], $pack[1] ) ) {
			continue;
		}
		$html .= '<option value="' . esc_attr( (string) $slug ) . '">' . esc_html( $pack[0] . ' — ' . $pack[1] ) . '</option>';
	}
	return $html;
}

function bds_rev_offer_by_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}
	foreach ( array_merge( bds_rev_offers(), bds_rev_featured_inquiry_offers() ) as $offer ) {
		if ( $offer['slug'] === $slug ) {
			return $offer;
		}
	}
	return null;
}

function bds_rev_music_form( $options = '' ) {
	if ( is_array( $options ) || '' === $options ) {
		$options = bds_rev_music_options_html( is_array( $options ) ? $options : null );
	}
	$status = isset( $_GET['bds_music'] ) ? sanitize_key( wp_unslash( $_GET['bds_music'] ) ) : '';
	$note   = 'saved' === $status
		? __( 'Your brief was received. BrandDad will confirm scope and licensing before any payment or production.', 'branddad' )
		: '';
	ob_start();
	if ( '' !== $note ) {
		echo '<p role="status">' . esc_html( $note ) . '</p>';
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'bds_music_brief', 'bds_music_nonce' ); ?>
		<input type="hidden" name="action" value="bds_music_brief" />
		<p class="bds-rev-hp" aria-hidden="true"><label>Company website <input type="text" name="bds_company_url" tabindex="-1" autocomplete="off" /></label></p>
		<p><label><?php esc_html_e( 'Package', 'branddad' ); ?><select name="package" required><?php echo $options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select></label></p>
		<p><label><?php esc_html_e( 'Your name', 'branddad' ); ?><input name="customer_name" maxlength="80" required /></label></p>
		<p><label><?php esc_html_e( 'Email', 'branddad' ); ?><input name="email" type="email" maxlength="120" required /></label></p>
		<p><label><?php esc_html_e( 'Genre', 'branddad' ); ?><input name="genre" maxlength="80" /></label></p>
		<p><label><?php esc_html_e( 'Mood', 'branddad' ); ?><input name="mood" maxlength="80" /></label></p>
		<p><label><?php esc_html_e( 'Business description', 'branddad' ); ?><textarea name="business" maxlength="800" required></textarea></label></p>
		<p><label><?php esc_html_e( 'Customer story', 'branddad' ); ?><textarea name="story" maxlength="800"></textarea></label></p>
		<p><label><?php esc_html_e( 'Usage rights you need', 'branddad' ); ?><textarea name="usage" maxlength="400"></textarea></label></p>
		<button class="bds-rev-button" type="submit"><?php esc_html_e( 'Send the creative brief', 'branddad' ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}

function bds_rev_append_content( $content ) {
	if ( ! bds_rev_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( is_page( 'services' ) ) {
		if ( false !== strpos( $content, 'id="bds-revenue-sections"' ) ) {
			return $content;
		}
		return bds_rev_inject_before_final( $content, bds_rev_markup() );
	}
	if ( is_page( 'check-your-website' ) ) {
		if ( false !== strpos( $content, 'id="bds-rev-home-strip"' ) ) {
			return $content;
		}
		return $content . bds_rev_home_strip();
	}
	if ( ! is_front_page() ) {
		return $content;
	}
	$block = '';
	if ( false === strpos( $content, 'id="bds-rev-home-strip"' ) ) {
		$block .= bds_rev_home_strip();
	}
	if ( false !== strpos( $content, 'id="branddad-music"' ) && false === strpos( $content, 'id="bds-music-brief"' ) ) {
		$block .= '<section class="bds-rev" id="bds-music-brief"><h2>' . esc_html__( 'Send a music brief', 'branddad' ) . '</h2><p>' . esc_html__( 'This saves a private request. It does not take payment or start production.', 'branddad' ) . '</p>' . bds_rev_music_form() . '</section>';
	}
	if ( '' === $block ) {
		return $content;
	}
	return bds_rev_inject_before_final( $content, $block );
}
add_filter( 'the_content', 'bds_rev_append_content', 1000 );

function bds_rev_404_cta() {
	if ( ! bds_rev_is_social() || is_admin() || ! is_404() ) {
		return;
	}
	echo bds_rev_home_strip(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_404_cta', 24 );

/**
 * Secure design (untrusted GET): `offer` is sanitize_title, then matched to the
 * hard-coded catalog slug list. Unknown values print nothing. Name/price are
 * esc_html. No checkout, no storage, no raw query echo.
 */
function bds_rev_offer_note_html( $slug ) {
	$offer = bds_rev_offer_by_slug( $slug );
	if ( ! $offer ) {
		return '';
	}
	$url   = bds_rev_offer_url( $offer['slug'] );
	$note  = '<p class="bds-rev-note" id="bds-rev-offer-note">';
	if ( '' !== $url ) {
		$note .= esc_html(
			sprintf(
				/* translators: 1: plan name, 2: listed price */
				__( 'You asked about %1$s (%2$s). Checkout is already open for this plan.', 'branddad' ),
				$offer['name'],
				$offer['price']
			)
		);
		$note .= ' <a class="bds-rev-button" href="' . esc_url( $url ) . '">' . esc_html__( 'Subscribe now', 'branddad' ) . '</a>';
	} else {
		$note .= esc_html(
			sprintf(
				/* translators: 1: plan name, 2: listed price */
				__( 'You asked about %1$s (%2$s). This page is an inquiry. It does not start checkout.', 'branddad' ),
				$offer['name'],
				$offer['price']
			)
		);
	}
	$note .= '</p>';
	return $note;
}

function bds_rev_contact_offer_note( $content ) {
	if ( ! bds_rev_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() || ! is_page( 'contact-us' ) ) {
		return $content;
	}
	$slug = isset( $_GET['offer'] ) ? sanitize_title( wp_unslash( $_GET['offer'] ) ) : '';
	$note = bds_rev_offer_note_html( $slug );
	if ( '' === $note || false !== strpos( $content, 'id="bds-rev-offer-note"' ) ) {
		return $content;
	}
	return $note . $content;
}
add_filter( 'the_content', 'bds_rev_contact_offer_note', 20 );

function bds_rev_styles() {
	if ( ! bds_rev_is_social() ) {
		return;
	}
	echo '<style id="bds-rev-css">.bds-rev{max-width:1180px;margin:0 auto;padding:12px 20px 28px;color:#0b172a}.bds-rev h2{font-size:clamp(28px,4vw,42px);line-height:1.1;letter-spacing:-.03em}.bds-rev-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.bds-rev-card{display:flex;flex-direction:column;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff}.bds-rev-price{font-size:22px;font-weight:800}.bds-rev-button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;margin:8px 8px 0 0;padding:0 16px;border-radius:10px;background:#0878ed;color:#fff;font-weight:800;text-decoration:none}.bds-rev-button--quiet{background:#071a33}.bds-rev-note{color:#526070;font-size:13px}.bds-rev form p{display:flex;flex-direction:column;gap:6px;max-width:640px}.bds-rev input,.bds-rev select,.bds-rev textarea{min-height:44px;padding:8px;border:1px solid #dbe5ef;border-radius:8px}.bds-rev textarea{min-height:96px}.bds-rev-hp{position:absolute;left:-9999px}.bds-rev-card .bds-rev-button{margin-top:auto}@media(max-width:800px){.bds-rev-grid{grid-template-columns:1fr}.bds-rev{padding:8px 12px 24px}}</style>';
}
add_action( 'wp_head', 'bds_rev_styles', 40 );

function bds_rev_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'bds_music_' . md5( $ip . '|' . wp_salt( 'auth' ) );
}

function bds_rev_handle_music() {
	if ( ! bds_rev_is_social() ) {
		wp_die( esc_html__( 'This form is not available here.', 'branddad' ), 404 );
	}
	if ( ! isset( $_POST['bds_music_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_music_nonce'] ) ), 'bds_music_brief' ) ) {
		wp_die( esc_html__( 'The form expired. Reload the page and try again.', 'branddad' ), 403 );
	}
	if ( ! empty( $_POST['bds_company_url'] ) ) {
		wp_safe_redirect( home_url( '/?bds_music=saved' ) );
		exit;
	}
	$key = bds_rev_client_key();
	if ( (int) get_transient( $key ) >= 5 ) {
		wp_die( esc_html__( 'Too many briefs were sent. Try again later.', 'branddad' ), 429 );
	}
	$packages = bds_rev_music_packages();
	$package  = isset( $_POST['package'] ) ? sanitize_key( wp_unslash( $_POST['package'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$name     = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$business = isset( $_POST['business'] ) ? sanitize_textarea_field( wp_unslash( $_POST['business'] ) ) : '';
	if ( ! isset( $packages[ $package ] ) || ! is_email( $email ) || '' === $name || '' === $business ) {
		wp_die( esc_html__( 'Check the package, name, email, and business description.', 'branddad' ), 400 );
	}
	$body = implode(
		"\n\n",
		array(
			'Package: ' . $packages[ $package ][0] . ' (' . $packages[ $package ][1] . ')',
			'Name: ' . $name,
			'Email: ' . $email,
			'Genre: ' . sanitize_text_field( wp_unslash( $_POST['genre'] ?? '' ) ),
			'Mood: ' . sanitize_text_field( wp_unslash( $_POST['mood'] ?? '' ) ),
			'Business: ' . $business,
			'Story: ' . sanitize_textarea_field( wp_unslash( $_POST['story'] ?? '' ) ),
			'Usage: ' . sanitize_textarea_field( wp_unslash( $_POST['usage'] ?? '' ) ),
			'Payment: not collected. Production: not started.',
		)
	);
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'bds_music_brief',
			'post_status'  => 'private',
			'post_title'   => 'Music brief ' . $package,
			'post_content' => $body,
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		wp_die( esc_html__( 'The brief could not be saved. Try again later.', 'branddad' ), 500 );
	}
	update_post_meta( (int) $post_id, '_bds_music_email', $email );
	update_post_meta( (int) $post_id, '_bds_music_package', $package );
	set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	wp_safe_redirect( wp_get_referer() ? add_query_arg( 'bds_music', 'saved', wp_get_referer() ) : home_url( '/?bds_music=saved' ) );
	exit;
}
add_action( 'admin_post_bds_music_brief', 'bds_rev_handle_music' );
add_action( 'admin_post_nopriv_bds_music_brief', 'bds_rev_handle_music' );

function bds_rev_register_brief_type() {
	if ( ! bds_rev_is_social() ) {
		return;
	}
	register_post_type(
		'bds_music_brief',
		array(
			'label'           => 'Music briefs',
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
		)
	);
}
add_action( 'init', 'bds_rev_register_brief_type' );
