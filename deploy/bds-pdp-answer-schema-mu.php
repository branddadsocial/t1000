<?php
/**
 * Plugin Name: BrandDad Social PDP answer schema
 * Description: Visible FAQ + How-To on Social service PDPs, plus FAQPage / HowTo / Article JSON-LD for Squirrly Geo.
 * Version: 1.1.0
 *
 * Does not add Review/AggregateRating (Woo/Squirrly already emit Product).
 * Does not invent traffic, shares, or backlinks.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( defined( 'BDS_PDP_ANSWER_VER' ) ) {
	return;
}
define( 'BDS_PDP_ANSWER_VER', '1.1.0' );

/**
 * Audit slugs Squirrly flagged (outreach 301s to visibility).
 *
 * @return string[]
 */
function bds_pdp_answer_slugs() {
	return array(
		'linkedin-visibility-amplification-system-for-professionals',
		'linkedin-viral-posts-for-professionals',
		'instagram-viral-growth-discovery-system',
		'telegram-growth-engagement-system',
		'facebook-growth-visibility-campaigns',
	);
}

/**
 * @param WC_Product|false $product Product.
 * @return bool
 */
function bds_pdp_answer_is_target( $product = false ) {
	if ( ! $product && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) {
		return false;
	}
	$slug = $product->get_slug();
	if ( in_array( $slug, bds_pdp_answer_slugs(), true ) ) {
		return true;
	}
	$cats = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $cats ) ) {
		return false;
	}
	$want = array( 'linkedin', 'instagram', 'telegram', 'facebook', 'social-growth', 'growth-systems' );
	return (bool) array_intersect( $want, $cats );
}

/**
 * @param string     $slug    Product slug.
 * @param WC_Product $product Product.
 * @return array{topic:string,about:string,faqs:array,steps:array}
 */
function bds_pdp_answer_pack( $slug, $product ) {
	$title     = wp_strip_all_tags( $product->get_name() );
	$price     = $product->get_price();
	$price_txt = $price ? wp_strip_all_tags( wc_price( $price ) ) : 'the listed price';
	$check     = home_url( '/check-your-website/' );
	$svc       = home_url( '/services/' );
	$packs     = array(
		'linkedin-visibility-amplification-system-for-professionals' => array(
			'topic' => 'LinkedIn visibility for professionals',
			'about' => 'A monthly BrandDad Social system that helps professionals stay visible to the people who already matter in their niche.',
			'faqs'  => array(
				array( 'What is the LinkedIn Visibility Amplification System?', 'It is a monthly BrandDad Social service that builds professional authority on LinkedIn and keeps you visible to a relevant audience.' ),
				array( 'Who is this LinkedIn visibility service for?', 'Consultants, operators, and founders who already have something to say and need a repeatable LinkedIn presence — not vanity metrics.' ),
				array( 'What do I get each month?', 'A clear monthly plan, content and visibility work scoped to professionals in your niche, and a cancel-anytime subscription. Directory members save 10% on eligible BrandDad services.' ),
				array( 'Does BrandDad offer LinkedIn outreach?', 'Yes. BrandDad Social offers scoped LinkedIn visibility and outreach services with clear deliverables.' ),
				array( 'How do I start?', 'Buy on this page, or run a free site check first if you are unsure which growth service fits. Directory members should sign in so the 10% applies at checkout.' ),
			),
			'steps' => array(
				array( 'Confirm this is the bottleneck', 'If LinkedIn is where your buyers already look, start here. If the site itself is the problem, run the free Check your website tool first.' ),
				array( 'Checkout on BrandDad Social', 'Pay the listed monthly price. Directory members save 10% after they sign in — no coupon to invent.' ),
				array( 'We run the monthly visibility plan', 'You get a scoped plan and work that keeps your professional presence active. Cancel anytime from your account.' ),
				array( 'Expand only what works', 'Add LinkedIn posts, other growth services, or Directory membership when you want the next door — not a random bundle.' ),
			),
		),
		'linkedin-viral-posts-for-professionals' => array(
			'topic' => 'LinkedIn posts for professionals',
			'about' => 'Professionally written LinkedIn posts designed to earn attention and credibility — a content service, not a fake-engagement pack.',
			'faqs'  => array(
				array( 'What are BrandDad LinkedIn Viral Posts?', 'A BrandDad Social content service: professionally written LinkedIn posts for people who need credible visibility, not viral gimmicks.' ),
				array( 'Who should buy LinkedIn posts?', 'Professionals who know their offer but do not have time to write consistent, useful LinkedIn posts.' ),
				array( 'Will you guarantee a post goes viral?', 'No. We write for attention and credibility. Virality is not a promise we sell.' ),
				array( 'How is this different from LinkedIn Visibility?', 'Posts are the writing. Visibility is the broader monthly system. Many buyers start with one, then add the other.' ),
				array( 'How do I order?', 'Choose this product, checkout, and we confirm voice and topics. Directory members save 10% when signed in.' ),
			),
			'steps' => array(
				array( 'Pick the post service', 'Use this page if you need written LinkedIn posts. Use LinkedIn Visibility if you want the full monthly system.' ),
				array( 'Checkout', 'Pay the listed price. Sign in as a Directory member for 10% on eligible services.' ),
				array( 'Share voice and topics', 'We write to your professional voice — no generic thought-leadership filler.' ),
				array( 'Publish and decide the next buy', 'Post the work, then add Visibility or another service only if that is the next bottleneck.' ),
			),
		),
		'instagram-viral-growth-discovery-system' => array(
			'topic' => 'Instagram growth and discovery',
			'about' => 'A BrandDad Social Instagram system for discovery and engagement — scoped work, not bought followers.',
			'faqs'  => array(
				array( 'What is the Instagram Viral Growth + Discovery System?', 'A BrandDad Social service that helps a brand get discovered and engaged with on Instagram through a repeatable plan.' ),
				array( 'What kind of Instagram growth is this?', 'This is growth and discovery work built around a repeatable plan.' ),
				array( 'Who is it for?', 'Brands that already have an offer and need Instagram discovery — not a logo factory and not hosting.' ),
				array( 'What happens after I buy?', 'We confirm the account, scope the plan, and run the work. Directory members save 10% on eligible services.' ),
				array( 'Can I start with a site check instead?', 'Yes. If you are not sure Instagram is the bottleneck, use Check your website, then come back to this product or the services catalog.' ),
			),
			'steps' => array(
				array( 'Confirm Instagram is the gap', 'If the website or hosting is the problem, use BrandDad.co or HostTech. This page is Instagram discovery.' ),
				array( 'Buy the system', 'Checkout at the listed price. Directory 10% applies when you are signed in.' ),
				array( 'We run the discovery plan', 'Scoped Instagram work — not a follower pack.' ),
				array( 'Add only the next service you need', 'SMM, ads, or Directory listing if that is the honest next step.' ),
			),
		),
		'telegram-growth-engagement-system' => array(
			'topic' => 'Telegram growth and engagement',
			'about' => 'A BrandDad Social system to grow and activate a Telegram community with a repeatable engagement plan.',
			'faqs'  => array(
				array( 'What is the Telegram Growth & Engagement System?', 'A BrandDad Social service that helps you grow and activate a Telegram community with a repeatable plan — not bought members.' ),
				array( 'Who is it for?', 'Operators who already use Telegram (or should) and need growth plus engagement, not a silent dump of names.' ),
				array( 'Do you promise subscriber counts?', 'No. We sell a system and scoped work with clear deliverables.' ),
				array( 'How do I start?', 'Buy on this page. Directory members save 10% when signed in. Unsure? Check your website or browse all services.' ),
				array( 'Is this the same as Instagram or LinkedIn?', 'No. Each product is one platform. Buy the door that matches the bottleneck.' ),
			),
			'steps' => array(
				array( 'Confirm Telegram is the channel', 'If your buyers are not on Telegram, pick LinkedIn, Instagram, or Facebook instead.' ),
				array( 'Checkout', 'Pay the listed price. Directory members get 10% after sign-in.' ),
				array( 'We run growth and engagement work', 'A repeatable plan for the community you actually have.' ),
				array( 'Keep or add the next door', 'Add SMM or another growth service only when this one is working.' ),
			),
		),
		'facebook-growth-visibility-campaigns' => array(
			'topic' => 'Facebook growth and visibility',
			'about' => 'BrandDad Social campaigns that help a brand stay visible on Facebook to a relevant audience.',
			'faqs'  => array(
				array( 'What are Facebook Growth & Visibility Campaigns?', 'A BrandDad Social service for Facebook visibility and growth, with scoped campaigns built for awareness and engagement.' ),
				array( 'Who should buy this?', 'Brands whose customers still use Facebook and need consistent visibility, not a one-off boost pack.' ),
				array( 'Is this Facebook ads spend?', 'This page is the BrandDad service. Ad-account spend, if any, is separate and never sold as a ranking promise.' ),
				array( 'How do Directory members save?', 'Sign in with an active Directory membership. Eligible services get 10% at checkout automatically.' ),
				array( 'What if I need Instagram or LinkedIn instead?', 'Use those product pages. Do not mash every platform into one cart unless you actually need them.' ),
			),
			'steps' => array(
				array( 'Confirm Facebook is where buyers look', 'If they are on LinkedIn or Instagram, buy that system instead.' ),
				array( 'Checkout on this page', 'Listed price. Directory 10% when signed in.' ),
				array( 'We run the visibility work', 'A campaign plan you can understand.' ),
				array( 'Review and choose the next buy', 'Services catalog, site check, or WhatsApp if you want a human to point you.' ),
			),
		),
	);
	if ( isset( $packs[ $slug ] ) ) {
		$pack = $packs[ $slug ];
	} else {
		$pack = array(
			'topic' => $title,
			'about' => wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_name() ), 36 ),
			'faqs'  => array(
				array( 'What is ' . $title . '?', 'A BrandDad Social marketing service with a clear scope and listed price. It is not a ranking guarantee and not a fake-engagement pack.' ),
				array( 'Who is it for?', 'Businesses that need this specific growth job — not a logo, not hosting, not a WhatsApp Directory listing unless that is a separate door.' ),
				array( 'How much does it cost?', 'The live price on this page is ' . $price_txt . '. Directory members save 10% on eligible services after they sign in.' ),
				array( 'How do I start?', 'Checkout here, or start with Check your website if you are not sure this is the bottleneck.' ),
			),
			'steps' => array(
				array( 'Read the scope on this page', 'Buy only if this is the job you need next.' ),
				array( 'Checkout', 'Pay the listed price. Directory 10% applies when signed in.' ),
				array( 'We fulfill the scoped work', 'Clear deliverables and reviewable next steps.' ),
				array( 'Pick the next honest door', 'More services, Directory, BrandDad.co, or HostTech — only if you need that door.' ),
			),
		);
	}
	$pack['faqs'][] = array(
		'Where else should I go on BrandDad?',
		'Growth services stay on BrandDad Social. Logos and websites are on BrandDad.co. Hosting is on HostTech. WhatsApp listings are on the Directory. Start with Check your website (' . $check . ') or all services (' . $svc . ') if you are choosing.',
	);
	return $pack;
}

/**
 * Preserve Partnero on PDP related links.
 *
 * @param string $url URL.
 * @return string
 */
function bds_pdp_answer_po( $url ) {
	$url = (string) $url;
	if ( function_exists( 'bds_po_url' ) ) {
		return (string) bds_po_url( $url );
	}
	if ( function_exists( 'bdsu_po_keep' ) ) {
		return (string) bdsu_po_keep( $url );
	}
	return $url;
}

/**
 * Related money pages for a growth PDP — not a junk drawer.
 *
 * @param string $slug Product slug.
 * @return array<int,array{0:string,1:string}>
 */
function bds_pdp_answer_related( $slug ) {
	$map = array(
		'linkedin-visibility-amplification-system-for-professionals' => array(
			array( 'LinkedIn Content', 'linkedin-viral-posts-for-professionals' ),
			array( 'Instagram Growth', 'instagram-viral-growth-discovery-system' ),
		),
		'linkedin-viral-posts-for-professionals' => array(
			array( 'LinkedIn Visibility', 'linkedin-visibility-amplification-system-for-professionals' ),
			array( 'Facebook Visibility', 'facebook-growth-visibility-campaigns' ),
		),
		'instagram-viral-growth-discovery-system' => array(
			array( 'Facebook Visibility', 'facebook-growth-visibility-campaigns' ),
			array( 'Telegram Growth', 'telegram-growth-engagement-system' ),
		),
		'telegram-growth-engagement-system' => array(
			array( 'Instagram Growth', 'instagram-viral-growth-discovery-system' ),
			array( 'Facebook Visibility', 'facebook-growth-visibility-campaigns' ),
		),
		'facebook-growth-visibility-campaigns' => array(
			array( 'Instagram Growth', 'instagram-viral-growth-discovery-system' ),
			array( 'LinkedIn Visibility', 'linkedin-visibility-amplification-system-for-professionals' ),
		),
	);
	$related = isset( $map[ $slug ] ) ? $map[ $slug ] : array();
	$out     = array();
	foreach ( $related as $row ) {
		$out[] = array( $row[0], bds_pdp_answer_po( home_url( '/product/' . $row[1] . '/' ) ) );
	}
	$out[] = array( 'All services', bds_pdp_answer_po( home_url( '/services/' ) ) );
	$out[] = array( 'Check your website', bds_pdp_answer_po( home_url( '/check-your-website/' ) ) );
	$out[] = array( 'Learning Center', bds_pdp_answer_po( home_url( '/learning-center/' ) ) );
	$out[] = array( 'Directory — 10% off', bds_pdp_answer_po( 'https://directory.branddad.social/registration/?utm_source=branddad_social&utm_medium=pdp&utm_campaign=directory10' ) );
	return $out;
}

/**
 * @param WC_Product $product Product.
 * @return string
 */
function bds_pdp_answer_html( $product ) {
	$pack     = bds_pdp_answer_pack( $product->get_slug(), $product );
	$url      = get_permalink( $product->get_id() );
	$share_li = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url );
	$share_fb = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
	$share_x  = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $product->get_name() );
	ob_start();
	?>
	<section class="bds-pdp-answer" id="bds-pdp-answer" data-bds-pdp-answer="<?php echo esc_attr( BDS_PDP_ANSWER_VER ); ?>">
		<div class="bds-pdp-answer__intro">
			<span>What this page answers</span>
			<h2><?php echo esc_html( $pack['topic'] ); ?></h2>
			<p><?php echo esc_html( $pack['about'] ); ?></p>
		</div>
		<div class="bds-pdp-answer__howto">
			<h2>How to start</h2>
			<ol>
				<?php foreach ( $pack['steps'] as $i => $step ) : ?>
					<li>
						<b><?php echo esc_html( (string) ( $i + 1 ) ); ?></b>
						<div>
							<h3><?php echo esc_html( $step[0] ); ?></h3>
							<p><?php echo esc_html( $step[1] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<div class="bds-pdp-answer__faq">
			<h2>Questions this service answers</h2>
			<?php foreach ( $pack['faqs'] as $faq ) : ?>
				<details>
					<summary><?php echo esc_html( $faq[0] ); ?></summary>
					<div><?php echo wp_kses_post( wpautop( $faq[1] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
		<div class="bds-pdp-answer__next">
			<a class="bds-pdp-answer__buy" href="<?php echo esc_url( bds_pdp_answer_po( $url ) ); ?>">Buy this service</a>
		</div>
		<nav class="bds-pdp-answer__related" aria-label="Related BrandDad pages">
			<span>Related next steps</span>
			<?php foreach ( bds_pdp_answer_related( $product->get_slug() ) as $rel ) : ?>
				<a href="<?php echo esc_url( $rel[1] ); ?>"><?php echo esc_html( $rel[0] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="bds-pdp-answer__share">
			<span>Share this page</span>
			<a href="<?php echo esc_url( $share_li ); ?>" target="_blank" rel="noopener">LinkedIn</a>
			<a href="<?php echo esc_url( $share_fb ); ?>" target="_blank" rel="noopener">Facebook</a>
			<a href="<?php echo esc_url( $share_x ); ?>" target="_blank" rel="noopener">X</a>
		</div>
	</section>
	<style id="bds-pdp-answer-css">
	.bds-pdp-answer{margin:28px 0 8px;padding:22px 20px;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;color:#0f172a;font-family:Manrope,Syne,"Segoe UI",sans-serif}
	.bds-pdp-answer h2{margin:0 0 10px;font-size:22px;letter-spacing:-.02em}
	.bds-pdp-answer h3{margin:0 0 6px;font-size:16px}
	.bds-pdp-answer__intro>span,.bds-pdp-answer__share>span{color:#0372ff;font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:12px}
	.bds-pdp-answer__intro p,.bds-pdp-answer li p{color:#475569;line-height:1.6}
	.bds-pdp-answer__howto ol{list-style:none;margin:0;padding:0;display:grid;gap:10px}
	.bds-pdp-answer__howto li{display:grid;grid-template-columns:36px 1fr;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px}
	.bds-pdp-answer__howto b{width:36px;height:36px;border-radius:999px;background:#0372ff;color:#fff;display:grid;place-items:center}
	.bds-pdp-answer__faq{margin-top:18px;display:grid;gap:8px}
	.bds-pdp-answer__faq details{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px 14px}
	.bds-pdp-answer__faq summary{font-weight:800;cursor:pointer}
	.bds-pdp-answer__next,.bds-pdp-answer__share,.bds-pdp-answer__related{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:16px}
	.bds-pdp-answer__next a,.bds-pdp-answer__share a,.bds-pdp-answer__related a{font-weight:800;text-decoration:none}
	.bds-pdp-answer__buy{background:#0372ff;color:#fff!important;padding:12px 16px;border-radius:10px}
	.bds-pdp-answer__share a,.bds-pdp-answer__related a{color:#0372ff}
	.bds-pdp-answer__related>span,.bds-pdp-answer__share>span{color:#0372ff;font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:12px;width:100%}
	</style>
	<?php
	return ob_get_clean();
}

function bds_pdp_answer_render() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product || ! bds_pdp_answer_is_target( $product ) ) {
		return;
	}
	echo bds_pdp_answer_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_single_product_summary', 'bds_pdp_answer_render', 8 );

function bds_pdp_answer_schema() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product || ! bds_pdp_answer_is_target( $product ) ) {
		return;
	}
	$pack  = bds_pdp_answer_pack( $product->get_slug(), $product );
	$url   = get_permalink( $product->get_id() );
	$faqs  = array();
	foreach ( $pack['faqs'] as $faq ) {
		$faqs[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $faq[0] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $faq[1] ),
			),
		);
	}
	$steps = array();
	foreach ( $pack['steps'] as $i => $step ) {
		$steps[] = array(
			'@type'    => 'HowToStep',
			'position' => $i + 1,
			'name'     => wp_strip_all_tags( $step[0] ),
			'text'     => wp_strip_all_tags( $step[1] ),
			'url'      => $url . '#bds-pdp-answer',
		);
	}
	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type'      => 'FAQPage',
				'@id'        => $url . '#bds-faq',
				'url'        => $url,
				'mainEntity' => $faqs,
			),
			array(
				'@type'       => 'HowTo',
				'@id'         => $url . '#bds-howto',
				'name'        => 'How to start ' . wp_strip_all_tags( $product->get_name() ),
				'description' => $pack['about'],
				'url'         => $url,
				'step'        => $steps,
			),
			array(
				'@type'            => 'Article',
				'@id'              => $url . '#bds-article',
				'headline'         => wp_strip_all_tags( $product->get_name() ),
				'description'      => $pack['about'],
				'about'            => $pack['topic'],
				'mainEntityOfPage' => $url,
				'author'           => array( '@type' => 'Organization', 'name' => 'BrandDad Social', 'url' => home_url( '/' ) ),
				'publisher'        => array( '@type' => 'Organization', 'name' => 'BrandDad Social', 'url' => home_url( '/' ) ),
				'dateModified'     => get_the_modified_date( 'c', $product->get_id() ),
				'datePublished'    => get_the_date( 'c', $product->get_id() ),
			),
		),
	);
	echo '<script type="application/ld+json" id="bds-pdp-answer-ld">' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'bds_pdp_answer_schema', 92 );
