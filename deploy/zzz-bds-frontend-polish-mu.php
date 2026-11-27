<?php
/**
 * Plugin Name: BrandDad Frontend Polish
 * Description: Consolidates public sales surfaces, removes duplicate widgets, and keeps Services, Shop, product, and website-check pages visually focused.
 * Version: 1.0.0
 * Author: BrandDad
 *
 * Secure design:
 * - Public rendering only; no authentication, payment, entitlement, or checkout state changes.
 * - Host and route checks use allowlists. No request value is rendered.
 * - Product links resolve from published WordPress products and are escaped.
 * - Existing forms, payment handlers, nonces, and customer records are untouched.
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_FRONTEND_POLISH_VERSION', '1.0.1' );

/**
 * Render the public Health Check before legacy theme/output-buffer callbacks.
 *
 * This route has no normal WordPress page object. Disabling its legacy renderer
 * therefore produces an empty 200 response, while running that renderer inside
 * the older buffer stack can move the body into <head>. Emit one complete,
 * self-contained document before either path can run.
 */
function bds_fp_render_health_page() {
	if ( ! bds_fp_is_social() || ! function_exists( 'bds_health_is_social_tool_request' ) || ! bds_health_is_social_tool_request() ) {
		return;
	}

	status_header( 200 );
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! headers_sent() ) {
		nocache_headers();
		header( 'X-BDS-Health-Page: recovery-1' );
	}

	$home       = esc_url( home_url( '/' ) );
	$services   = esc_url( home_url( '/services/' ) );
	$essentials = esc_url( home_url( '/product/ai-business-essentials/' ) );
	$directory  = esc_url( 'https://directory.branddad.social/' );
	$widget     = shortcode_exists( 'bds_business_health_check' )
		? do_shortcode( '[bds_business_health_check minimal_header="1"]' )
		: '<p class="bds-fp-health-error">The website check is temporarily unavailable. Please try again shortly.</p>';
	$widget_css = function_exists( 'bds_health_css' ) ? bds_health_css() : '';
	$charset    = get_bloginfo( 'charset' );
	$lang       = get_bloginfo( 'language' );

	echo '<!doctype html><html lang="' . esc_attr( $lang ? $lang : 'en-US' ) . '"><head>';
	echo '<meta charset="' . esc_attr( $charset ? $charset : 'UTF-8' ) . '">';
	echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
	echo '<title>Check your website | BrandDad Social</title>';
	echo '<meta name="description" content="Run a free BrandDad website check and get clear next steps without confusing jargon or scare scores.">';
	echo '<link rel="canonical" href="' . esc_url( home_url( '/check-your-website/' ) ) . '">';
	echo '<style>' . $widget_css . bds_fp_health_page_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</head><body class="bds-fp-health-page">';
	echo '<header class="bds-fp-health-nav"><a class="bds-fp-health-brand" href="' . $home . '" aria-label="BrandDad Social home"><span aria-hidden="true">b</span><strong>BrandDad Social</strong></a>';
	echo '<nav aria-label="Primary"><a href="' . $services . '">Services</a><a href="' . $directory . '">Directory</a><a class="bds-fp-health-nav-cta" href="' . $essentials . '">Get ongoing help</a></nav></header>';
	echo '<main><section class="bds-fp-health-hero"><p class="bds-fp-health-kicker">Free AI website check</p><h1>See what is holding your website back</h1><p>Enter your website address. BrandDad checks public signals and turns them into clear next steps—without confusing jargon, scare scores, or ranking promises.</p></section>';
	echo '<section class="bds-fp-health-tool" aria-label="Website check">' . $widget . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<section class="bds-fp-health-next"><div><p class="bds-fp-health-kicker">Need help after the check?</p><h2>Turn the results into a simple action plan.</h2><p>Start with the lowest-priced service that fits the problem, or use AI Business Essentials for ongoing website, SEO, review, and content help.</p></div><div class="bds-fp-health-actions"><a class="bds-fp-health-button" href="' . $essentials . '">See AI Business Essentials</a><a class="bds-fp-health-button bds-fp-health-button--light" href="' . $services . '">Explore all services</a></div></section>';
	echo '</main><footer><p>© ' . esc_html( gmdate( 'Y' ) ) . ' BrandDad Social. Big-business tools. Small-business prices.</p></footer></body></html>';
	exit;
}
add_action( 'template_redirect', 'bds_fp_render_health_page', -100000 );

/** Return standalone Health Check page styles. */
function bds_fp_health_page_css() {
	return 'html{box-sizing:border-box}*,*:before,*:after{box-sizing:inherit}body.bds-fp-health-page{margin:0;background:#f7f9fc;color:#0f172a;font-family:Manrope,system-ui,-apple-system,"Segoe UI",sans-serif}.bds-fp-health-nav{display:flex;align-items:center;justify-content:space-between;gap:24px;min-height:76px;padding:12px max(22px,calc((100vw - 1180px)/2));background:#07192d;color:#fff}.bds-fp-health-brand,.bds-fp-health-nav nav,.bds-fp-health-nav a{display:flex;align-items:center}.bds-fp-health-brand{gap:10px;color:#fff;text-decoration:none}.bds-fp-health-brand span{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:#fff;color:#0b74ff;font-size:27px;font-weight:950}.bds-fp-health-nav nav{gap:22px}.bds-fp-health-nav nav a{color:#dceafb;font-weight:750;text-decoration:none}.bds-fp-health-nav .bds-fp-health-nav-cta{min-height:42px;padding:0 15px;border-radius:10px;background:#fff;color:#0759c7}.bds-fp-health-hero{padding:clamp(62px,9vw,112px) 22px 28px;background:radial-gradient(800px 360px at 10% 0%,rgba(11,116,255,.28),transparent 60%),linear-gradient(145deg,#07192d,#0d3155);color:#fff;text-align:center}.bds-fp-health-hero h1{max-width:920px;margin:8px auto 20px;color:#fff;font-size:clamp(2.45rem,6vw,5rem);line-height:.98;letter-spacing:-.045em}.bds-fp-health-hero>p:not(.bds-fp-health-kicker){max-width:720px;margin:0 auto;color:#d9e9fb;font-size:clamp(1.05rem,2vw,1.25rem);line-height:1.65}.bds-fp-health-kicker{margin:0 0 10px;color:#3f83c8;font-size:.78rem;font-weight:900;letter-spacing:.11em;text-transform:uppercase}.bds-fp-health-hero .bds-fp-health-kicker{color:#76b9ff}.bds-fp-health-tool{max-width:980px;margin:0 auto;padding:30px 22px 18px}.bds-fp-health-tool .bds-health{margin:0 auto}.bds-fp-health-next{display:flex;align-items:center;justify-content:space-between;gap:34px;max-width:1136px;margin:28px auto 70px;padding:clamp(28px,5vw,50px);border-radius:24px;background:#e8f2ff}.bds-fp-health-next h2{max-width:700px;margin:0 0 12px;font-size:clamp(1.9rem,4vw,3rem);line-height:1.05;letter-spacing:-.035em}.bds-fp-health-next>div>p:not(.bds-fp-health-kicker){max-width:700px;color:#475569;line-height:1.65}.bds-fp-health-actions{display:flex;flex:0 0 auto;flex-direction:column;gap:10px}.bds-fp-health-button{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 18px;border:1px solid #0759c7;border-radius:12px;background:#0759c7;color:#fff;font-weight:850;text-decoration:none}.bds-fp-health-button--light{background:#fff;color:#0759c7}body.bds-fp-health-page footer{padding:28px 22px;background:#07192d;color:#c9d9ea;text-align:center}@media(max-width:760px){.bds-fp-health-nav{min-height:68px}.bds-fp-health-nav nav>a:not(.bds-fp-health-nav-cta){display:none}.bds-fp-health-brand strong{font-size:.95rem}.bds-fp-health-next{display:block;margin:22px 16px 50px}.bds-fp-health-actions{margin-top:22px}.bds-fp-health-button{width:100%}}';
}

/** Return the normalized request host. */
function bds_fp_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/[\r\n\x00\t].*/', '', $host );
	$host = preg_replace( '/:\d+$/', '', (string) $host );
	return is_string( $host ) ? $host : '';
}

function bds_fp_is_social() {
	return in_array( bds_fp_host(), array( 'branddad.social', 'www.branddad.social' ), true );
}

function bds_fp_is_directory() {
	return in_array( bds_fp_host(), array( 'directory.branddad.social', 'www.directory.branddad.social' ), true );
}

/** Resolve only a published WooCommerce product URL. */
function bds_fp_product_url( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return '';
	}
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return '';
	}
	return (string) get_permalink( $post );
}

/** Render one concise, accessible service card. */
function bds_fp_service_card( array $service ) {
	$url = ! empty( $service['url'] ) ? (string) $service['url'] : bds_fp_product_url( isset( $service['slug'] ) ? $service['slug'] : '' );
	if ( '' === $url ) {
		return '';
	}
	$price = isset( $service['price'] ) ? (string) $service['price'] : '';
	$label = isset( $service['label'] ) ? (string) $service['label'] : 'View service';
	$html  = '<article class="bds-fp-card">';
	if ( ! empty( $service['eyebrow'] ) ) {
		$html .= '<p class="bds-fp-eyebrow">' . esc_html( $service['eyebrow'] ) . '</p>';
	}
	$html .= '<h3>' . esc_html( isset( $service['name'] ) ? $service['name'] : '' ) . '</h3>';
	if ( '' !== $price ) {
		$html .= '<p class="bds-fp-price">' . esc_html( $price ) . '</p>';
	}
	$html .= '<p>' . esc_html( isset( $service['summary'] ) ? $service['summary'] : '' ) . '</p>';
	$html .= '<a class="bds-fp-link" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span aria-hidden="true">&rarr;</span></a>';
	return $html . '</article>';
}

/** Build the authoritative, conversion-focused Services page. */
function bds_fp_services_content() {
	$plans = array(
		array( 'slug' => 'ai-business-essentials', 'name' => 'AI Business Essentials', 'price' => '$29.99/month', 'eyebrow' => 'Best place to start', 'summary' => 'Everyday help with social content, review replies, website checks, lead capture, and a simple monthly summary.', 'label' => 'Start 7-day trial' ),
		array( 'slug' => 'ai-lead-assistant', 'name' => 'AI Lead Assistant', 'price' => '$19.99/month', 'summary' => 'Capture inquiries, organize what people need, and prepare clearer follow-up drafts.', 'label' => 'View plan' ),
		array( 'slug' => 'ai-social-growth', 'name' => 'AI Social Growth', 'price' => '$29.99/month', 'summary' => 'Create a steady supply of useful social content in your business voice.', 'label' => 'View plan' ),
		array( 'slug' => 'ai-local-seo', 'name' => 'AI Local SEO Starter', 'price' => '$19.99/month', 'summary' => 'Find practical local-search improvements without confusing reports or ranking promises.', 'label' => 'View plan' ),
		array( 'slug' => 'ai-linkedin-growth', 'name' => 'AI LinkedIn Growth', 'price' => '$29.99/month', 'summary' => 'Plan stronger LinkedIn posts and professional conversations with clear usage limits.', 'label' => 'View plan' ),
		array( 'slug' => 'ai-website-chatbot', 'name' => 'AI Website Chatbot', 'price' => '$29.99/month', 'summary' => 'Answer common questions, collect leads, and hand complicated conversations to a person.', 'label' => 'View plan' ),
	);
	$studio = array(
		array( 'slug' => 'branddad-ai-studio-starter', 'name' => 'AI Studio Starter', 'price' => '$14.99/month', 'summary' => '50 monthly BrandDad credits for occasional writing, planning, and business help.', 'label' => 'Choose Starter' ),
		array( 'slug' => 'branddad-ai-studio-growth', 'name' => 'AI Studio Growth', 'price' => '$29.99/month', 'eyebrow' => 'Most popular', 'summary' => '150 monthly BrandDad credits for businesses that create content every week.', 'label' => 'Choose Growth' ),
		array( 'slug' => 'branddad-ai-studio-pro', 'name' => 'AI Studio Pro', 'price' => '$49.99/month', 'summary' => '400 monthly BrandDad credits for frequent use across business and marketing work.', 'label' => 'Choose Pro' ),
	);
	$specialty = array(
		array( 'slug' => 'branddad-ai-review-dispute-assistant', 'name' => 'AI Review Dispute Assistant', 'price' => '$9.99 one time', 'summary' => 'Describe one Airbnb review case and receive a policy-based appeal draft, shorter version, evidence checklist, and public-reply fallback.', 'label' => 'Prepare one case' ),
		array( 'slug' => 'branddad-ai-imdb-submission-pack', 'name' => 'AI IMDb Submission Pack', 'price' => '$29.99 one time', 'summary' => 'Organize the facts and submission materials for an eligible IMDb update. Approval is not guaranteed.', 'label' => 'View service' ),
		array( 'slug' => 'branddad-ai-billboard-creative-pack', 'name' => 'AI Billboard Creative Pack', 'price' => '$29.99 one time', 'summary' => 'Prepare a focused billboard concept, message, and production-ready creative direction.', 'label' => 'View service' ),
		array( 'slug' => 'branddad-ai-press-release-pack', 'name' => 'AI Press Release Pack', 'price' => '$29.99 one time', 'summary' => 'Turn a real announcement into a clear press-release draft and media-ready supporting copy.', 'label' => 'View service' ),
	);
	$web = array();
	if ( function_exists( 'bds_svc_catalog' ) ) {
		foreach ( (array) bds_svc_catalog() as $row ) {
			if ( ! is_array( $row ) || empty( $row['slug'] ) || empty( $row['title'] ) || empty( $row['blurb'] ) ) {
				continue;
			}
			$url = bds_fp_product_url( $row['slug'] );
			if ( '' === $url ) {
				continue;
			}
			$web[] = array(
				'url'     => $url,
				'name'    => $row['title'],
				'summary' => $row['blurb'],
				'label'   => 'View service',
			);
		}
	}

	$render = static function ( $items ) {
		$out = '';
		foreach ( $items as $item ) {
			$out .= bds_fp_service_card( $item );
		}
		return $out;
	};

	$plans_html     = $render( $plans );
	$studio_html    = $render( $studio );
	$specialty_html = $render( $specialty );
	$web_html       = $render( $web );
	$contact        = esc_url( home_url( '/contact-us/' ) );
	$directory      = esc_url( 'https://directory.branddad.social/add-listing/' );

	return '<main class="bds-fp-services" data-bds-frontend-polish="' . esc_attr( BDS_FRONTEND_POLISH_VERSION ) . '">'
		. '<section class="bds-fp-hero"><p class="bds-fp-eyebrow">Affordable AI and hands-on business help</p><h1>Choose what you need. See the price. Start without the agency runaround.</h1><p>Tell us the result you want: more leads, better content, stronger local visibility, a clearer website, or help with one specific job. Every service explains what you receive before you pay.</p><div class="bds-fp-actions"><a class="bds-fp-button" href="#bds-fp-plans">Compare monthly plans</a><button class="bds-fp-button bds-fp-button--light" type="button" data-bds-open-assistant>Ask BrandDad AI</button></div><ul><li>Plain-language guidance</li><li>Clear limits and pricing</li><li>You approve important work</li></ul></section>'
		. '<section class="bds-fp-section"><p class="bds-fp-eyebrow">Start with the outcome</p><h2>What would help your business most right now?</h2><div class="bds-fp-paths"><a href="#bds-fp-plans"><strong>Bring in and follow up with leads</strong><span>Lead capture, replies, and simple follow-up help.</span></a><a href="#bds-fp-plans"><strong>Keep marketing consistent</strong><span>Social content, LinkedIn help, and everyday business writing.</span></a><a href="#bds-fp-web"><strong>Improve my website or local visibility</strong><span>Website fixes, SEO help, Google profile support, and monitoring.</span></a><a href="#bds-fp-specialty"><strong>Handle one specific project</strong><span>Review appeals, press materials, creative packs, and specialist work.</span></a></div></section>'
		. ( '' !== $plans_html ? '<section class="bds-fp-section" id="bds-fp-plans"><p class="bds-fp-eyebrow">Monthly business help</p><h2>Useful help every month—starting at $19.99.</h2><p>AI Business Essentials is the simplest all-around choice. Pick a specialist plan only when that is the main job you need done.</p><div class="bds-fp-grid">' . $plans_html . '</div></section>' : '' )
		. ( '' !== $studio_html ? '<section class="bds-fp-section bds-fp-section--soft"><p class="bds-fp-eyebrow">Self-service workspace</p><h2>Use BrandDad AI when you need it.</h2><p>Choose a credit plan, see the cost before each tool runs, and review every result before you use it.</p><div class="bds-fp-grid">' . $studio_html . '</div></section>' : '' )
		. ( '' !== $specialty_html ? '<section class="bds-fp-section" id="bds-fp-specialty"><p class="bds-fp-eyebrow">One-time help</p><h2>Pay for one focused job.</h2><p>These services solve a defined problem without starting a monthly subscription.</p><div class="bds-fp-grid">' . $specialty_html . '</div></section>' : '' )
		. ( '' !== $web_html ? '<section class="bds-fp-section bds-fp-section--soft" id="bds-fp-web"><p class="bds-fp-eyebrow">Website and local growth</p><h2>Fix the parts customers see first.</h2><p>Choose a clearly scoped service. Larger or unusual work is confirmed before payment.</p><div class="bds-fp-grid">' . $web_html . '</div></section>' : '' )
		. '<section class="bds-fp-final"><div><p class="bds-fp-eyebrow">Still not sure?</p><h2>Tell BrandDad what the business does and what is not working.</h2><p>We will point you to the lowest-priced suitable option—or tell you when you do not need to buy anything yet.</p></div><div class="bds-fp-actions"><button class="bds-fp-button" type="button" data-bds-open-assistant>Ask BrandDad AI</button><a class="bds-fp-button bds-fp-button--light" href="' . $contact . '">Contact BrandDad</a></div></section>'
		. '<section class="bds-fp-directory"><div><p class="bds-fp-eyebrow">Already own a business?</p><h2>List it in BrandDad Directory free.</h2><p>Help nearby customers find the business and contact it. A basic listing does not start a paid plan.</p></div><a class="bds-fp-button" href="' . $directory . '">List my business free</a></section>'
		. '</main>';
}

/** Replace only the two intentionally consolidated public pages. */
function bds_fp_filter_content( $content ) {
	if ( ! bds_fp_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( is_page( 'services' ) ) {
		return bds_fp_services_content();
	}
	if ( is_page( 'check-your-website' ) ) {
		$tool = shortcode_exists( 'bds_business_health_check' ) ? do_shortcode( '[bds_business_health_check minimal_header="1"]' ) : '';
		return '<main class="bds-fp-services bds-fp-check"><section class="bds-fp-hero"><p class="bds-fp-eyebrow">Free website check</p><h1>Find the website problems most likely to cost you customers.</h1><p>Enter your public website address. BrandDad checks practical signals and explains what to fix first in plain language. No ranking promises and no scare score.</p></section><section class="bds-fp-section">' . $tool . '</section><section class="bds-fp-final"><div><h2>Want help fixing what the check finds?</h2><p>Review the results first. Then choose a clearly scoped service only if it makes sense.</p></div><a class="bds-fp-button" href="' . esc_url( home_url( '/services/#bds-fp-web' ) ) . '">See website services</a></section></main>';
	}
	return $content;
}
add_filter( 'the_content', 'bds_fp_filter_content', PHP_INT_MAX );

/** Remove overlapping footer widgets and late content appenders on polished pages. */
function bds_fp_consolidate_hooks() {
	if ( ! bds_fp_is_social() || is_admin() ) {
		return;
	}
	remove_action( 'wp_footer', 'bds_sales_help_footer', 50 );
	remove_action( 'wp_footer', 'bds_follow_form', 51 );
	if ( is_front_page() || is_page( 'services' ) || is_page( 'check-your-website' ) ) {
		remove_filter( 'the_content', 'bds_rev_append_content', 1000 );
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		remove_action( 'wp_footer', 'bds_rev_shop_footer', 26 );
	}
}
add_action( 'wp', 'bds_fp_consolidate_hooks', PHP_INT_MAX );

/** Replace Woo's visible placeholder card with the real BrandDad mark. */
function bds_fp_product_image_fallback( $html, $product ) {
	if ( ! bds_fp_is_social() || ! is_string( $html ) || false === stripos( $html, 'placeholder' ) || ! is_object( $product ) ) {
		return $html;
	}
	$logo_id = (int) get_theme_mod( 'custom_logo', 0 );
	$src     = $logo_id > 0 ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	if ( ! is_string( $src ) || '' === $src ) {
		return $html;
	}
	return '<img class="bds-fp-product-fallback" src="' . esc_url( $src ) . '" alt="' . esc_attr( (string) $product->get_name() ) . '" loading="lazy" />';
}
add_filter( 'woocommerce_product_get_image', 'bds_fp_product_image_fallback', 999, 2 );

/** Exact public wording repairs for Directory templates outside normal page content. */
function bds_fp_directory_text_buffer( $html ) {
	if ( ! is_string( $html ) || '' === $html || strlen( $html ) > 8000000 ) {
		return $html;
	}
	$html = str_replace( 'Buy, Sell Discover Locally', 'Buy, Sell &amp; Discover Locally', $html );
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
	if ( '/add-listing/' === $path || '/add-listing' === $path ) {
		$html = preg_replace( '/<h1([^>]*)>\s*Registration\s*<\/h1>/i', '<h1$1>Add Your Listing</h1>', $html, 1 );
	}
	return is_string( $html ) ? $html : '';
}

function bds_fp_directory_buffer_start() {
	if ( ! bds_fp_is_directory() || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'bds_fp_directory_text_buffer' );
}
add_action( 'template_redirect', 'bds_fp_directory_buffer_start', 1000 );

/** Shared polished styling and the single-assistant opener. */
function bds_fp_styles() {
	if ( is_admin() || ( ! bds_fp_is_social() && ! bds_fp_is_directory() ) ) {
		return;
	}
	if ( bds_fp_is_social() ) {
		$css = ':root{--bds-fp-ink:#10243a;--bds-fp-muted:#52677d;--bds-fp-blue:#0668d8;--bds-fp-line:#dbe5ef;--bds-fp-soft:#f4f8fc}'
			. '#bds-svc-jump,#bds-rev-home-strip,#bds-music-brief,#bds-dir-signup,#bds-sales-help,#bds-follow-consent,#bdsNetworkConnectStrip,#bds-formwise-sales,#bds-social-seomi,#bds-hire-foot,.bdsu-growth-quiz,.bdsu-contact-dock,#bdsu-contact-dock{display:none!important}'
			. 'body.single-product #bds-join-benefits,body.single-product .bds-oneshot-buybox,body.single-product .bds-oneshot-note,body.single-product .upsells.products,body.single-product .related.products{display:none!important}'
			. 'body.single-product .site-main{max-width:1180px;margin:0 auto;padding:clamp(20px,4vw,54px) 20px}body.single-product div.product{display:grid;grid-template-columns:minmax(280px,.9fr) minmax(320px,1.1fr);gap:clamp(28px,5vw,72px);align-items:start}body.single-product div.product .summary{width:auto!important;float:none!important}body.single-product div.product .woocommerce-product-gallery{width:auto!important;float:none!important}body.single-product .product_title{font-size:clamp(2rem,4vw,3.3rem)!important;line-height:1.06;color:var(--bds-fp-ink)!important}body.single-product .single_add_to_cart_button{min-height:50px;border-radius:12px!important;padding:0 22px!important;background:var(--bds-fp-blue)!important}body.single-product .woocommerce-tabs{grid-column:1/-1;border-top:1px solid var(--bds-fp-line);padding-top:28px}'
			. 'body.woocommerce-shop #bds-blog-find,body.post-type-archive-product #bds-blog-find{display:none!important}body.woocommerce-shop ul.products,body.post-type-archive-product ul.products{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}body.woocommerce-shop ul.products:before,body.woocommerce-shop ul.products:after{display:none!important}body.woocommerce-shop ul.products li.product,body.post-type-archive-product ul.products li.product{width:auto!important;margin:0!important;padding:18px!important;border:1px solid var(--bds-fp-line);border-radius:18px;background:#fff;box-shadow:0 12px 32px rgba(15,23,42,.06)}.bds-fp-product-fallback{width:100%!important;aspect-ratio:4/3!important;object-fit:contain!important;padding:34px!important;box-sizing:border-box;background:var(--bds-fp-soft)!important;border-radius:12px}'
			. '.bds-fp-services{max-width:1180px;margin:0 auto;padding:0 22px 72px;color:var(--bds-fp-ink);font-family:Manrope,system-ui,-apple-system,"Segoe UI",sans-serif}.bds-fp-services *{box-sizing:border-box}.bds-fp-hero{margin:0 calc(50% - 50vw) 0;padding:clamp(64px,8vw,112px) max(22px,calc((100vw - 1180px)/2 + 22px));background:radial-gradient(900px 500px at 10% -10%,rgba(6,104,216,.28),transparent 58%),linear-gradient(145deg,#07192d,#0d3155);color:#fff}.bds-fp-hero h1{max-width:900px;margin:8px 0 20px;color:#fff!important;font-size:clamp(2.5rem,6vw,5rem);line-height:.98;letter-spacing:-.045em}.bds-fp-hero>p:not(.bds-fp-eyebrow){max-width:760px;color:#d9e9fb;font-size:clamp(1.05rem,1.8vw,1.3rem);line-height:1.65}.bds-fp-hero ul{display:flex;flex-wrap:wrap;gap:10px 24px;margin:24px 0 0;padding:0;list-style:none;color:#d9e9fb}.bds-fp-hero li:before{content:"✓";margin-right:8px;color:#74c0fc;font-weight:900}.bds-fp-eyebrow{margin:0 0 10px;color:#0b65c2;font-size:.78rem;font-weight:850;letter-spacing:.11em;text-transform:uppercase}.bds-fp-hero .bds-fp-eyebrow{color:#8ec8ff}.bds-fp-section{padding:clamp(54px,7vw,88px) 0}.bds-fp-section--soft{margin:0 calc(50% - 50vw);padding-left:max(22px,calc((100vw - 1180px)/2 + 22px));padding-right:max(22px,calc((100vw - 1180px)/2 + 22px));background:var(--bds-fp-soft)}.bds-fp-section h2,.bds-fp-final h2,.bds-fp-directory h2{margin:0 0 14px;color:var(--bds-fp-ink)!important;font-size:clamp(2rem,4vw,3.2rem);line-height:1.06;letter-spacing:-.035em}.bds-fp-section>p:not(.bds-fp-eyebrow){max-width:760px;color:var(--bds-fp-muted);font-size:1.08rem;line-height:1.7}.bds-fp-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin-top:32px}.bds-fp-card{display:flex;flex-direction:column;min-height:100%;padding:26px;border:1px solid var(--bds-fp-line);border-radius:20px;background:#fff;box-shadow:0 14px 34px rgba(15,23,42,.06)}.bds-fp-card h3{margin:0 0 12px;color:var(--bds-fp-ink)!important;font-size:1.35rem;line-height:1.2}.bds-fp-card>p:not(.bds-fp-eyebrow):not(.bds-fp-price){color:var(--bds-fp-muted);line-height:1.65}.bds-fp-price{margin:0 0 10px;color:var(--bds-fp-blue);font-size:1.15rem;font-weight:850}.bds-fp-link{margin-top:auto;padding-top:18px;color:var(--bds-fp-blue)!important;font-weight:850;text-decoration:none}.bds-fp-paths{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:28px}.bds-fp-paths a{display:flex;flex-direction:column;gap:6px;padding:22px;border:1px solid var(--bds-fp-line);border-radius:16px;color:var(--bds-fp-ink)!important;text-decoration:none;background:#fff}.bds-fp-paths a:hover{border-color:#88b9ed;box-shadow:0 12px 28px rgba(15,23,42,.07)}.bds-fp-paths span{color:var(--bds-fp-muted)}.bds-fp-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}.bds-fp-button{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 20px;border:1px solid var(--bds-fp-blue);border-radius:12px;background:var(--bds-fp-blue);color:#fff!important;font:800 1rem/1 Manrope,system-ui,sans-serif;text-decoration:none;cursor:pointer}.bds-fp-button--light{background:#fff;color:var(--bds-fp-blue)!important}.bds-fp-hero .bds-fp-button--light{border-color:rgba(255,255,255,.7);background:transparent;color:#fff!important}.bds-fp-final,.bds-fp-directory{display:flex;align-items:center;justify-content:space-between;gap:28px;margin-top:42px;padding:clamp(28px,5vw,52px);border-radius:24px}.bds-fp-final{background:#eaf3fd}.bds-fp-directory{background:#07192d;color:#fff}.bds-fp-directory h2{color:#fff!important}.bds-fp-directory p{color:#d9e9fb}.bds-fp-check .bds-health{display:block!important;height:auto!important;max-height:none!important;overflow:visible!important}'
			. '@media(max-width:900px){body.single-product div.product{grid-template-columns:1fr}.bds-fp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}body.woocommerce-shop ul.products,body.post-type-archive-product ul.products{grid-template-columns:repeat(2,minmax(0,1fr))}.bds-fp-final,.bds-fp-directory{align-items:flex-start;flex-direction:column}}@media(max-width:620px){.bds-fp-services{padding-left:16px;padding-right:16px}.bds-fp-hero{padding-left:20px;padding-right:20px}.bds-fp-grid,.bds-fp-paths,body.woocommerce-shop ul.products,body.post-type-archive-product ul.products{grid-template-columns:1fr}.bds-fp-button{width:100%}.bds-fp-hero h1{font-size:clamp(2.35rem,13vw,3.7rem)}}';
		echo '<style id="bds-frontend-polish-css" data-version="' . esc_attr( BDS_FRONTEND_POLISH_VERSION ) . '">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'bds_fp_styles', PHP_INT_MAX );

function bds_fp_script() {
	if ( ! bds_fp_is_social() || is_admin() ) {
		return;
	}
	?>
	<script id="bds-frontend-polish-js">
	(function(){
	  document.addEventListener('click',function(e){
	    var trigger=e.target&&e.target.closest&&e.target.closest('[data-bds-open-assistant]');
	    if(!trigger)return;
	    var launcher=document.getElementById('bds-sales-preview-launcher');
	    if(launcher){e.preventDefault();launcher.click();}
	  });
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bds_fp_script', 99 );

