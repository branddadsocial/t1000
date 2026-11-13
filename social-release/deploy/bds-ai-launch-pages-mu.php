<?php
/**
 * Plugin Name: BrandDad AI Launch Pages (Staged)
 * Description: Consolidated, accessible homepage and services-page experience for the approved BrandDad AI launch plans.
 * Version: 0.3.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_LAUNCH_PAGES_VERSION', '0.3.0' );

/** Public replacement remains fail-closed until the final launch gate. */
function bds_ai_launch_pages_enabled() {
	return defined( 'BDS_AI_LAUNCH_PAGES_ENABLED' ) && true === BDS_AI_LAUNCH_PAGES_ENABLED;
}

/** Return the three deliberately consolidated offers. */
function bds_ai_launch_offers() {
	return array(
		array(
			'slug'    => 'branddad-ai-studio-starter',
			'name'    => 'Starter',
			'price'   => '14.99',
			'credits' => 50,
			'fit'     => 'For a solo business that needs occasional help with writing and marketing ideas.',
			'example' => 'A practical first step when you want useful AI help without a large commitment.',
		),
		array(
			'slug'    => 'branddad-ai-studio-growth',
			'name'    => 'Growth',
			'price'   => '29.99',
			'credits' => 150,
			'fit'     => 'For a growing business that creates content and marketing materials every week.',
			'example' => 'More room for social copy, review replies, marketing plans, and business writing.',
			'popular' => true,
		),
		array(
			'slug'    => 'branddad-ai-studio-pro',
			'name'    => 'Pro',
			'price'   => '49.99',
			'credits' => 400,
			'fit'     => 'For active teams and professionals who use BrandDad AI throughout the month.',
			'example' => 'The largest standard allowance for frequent business and marketing work.',
		),
	);
}

/** Resolve a purchase link only for a genuinely published product. */
function bds_ai_launch_product_url( $slug ) {
	if ( function_exists( 'bds_ai_commercial_checkout_url' ) ) {
		return bds_ai_commercial_checkout_url( $slug );
	}
	$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'product' );
	return $post instanceof WP_Post && 'publish' === $post->post_status ? get_permalink( $post ) : '';
}

/** Render the flagship and three specialist offers without inventing readiness. */
function bds_ai_launch_featured_offer_card( $slug, $primary = false ) {
	$offer = function_exists( 'bds_ai_commercial_offer' ) ? bds_ai_commercial_offer( $slug ) : null;
	if ( ! is_array( $offer ) ) {
		return '';
	}
	$url   = bds_ai_launch_product_url( $slug );
	$audit = esc_url( home_url( '/check-your-website/' ) );
	ob_start();
	?>
	<article class="bdai-solution<?php echo $primary ? ' bdai-solution--primary' : ''; ?>">
		<?php if ( $primary ) : ?><p class="bdai-badge"><?php esc_html_e( 'Flagship plan', 'branddad' ); ?></p><?php endif; ?>
		<h3><?php echo esc_html( $offer['name'] ); ?></h3>
		<p class="bdai-price"><strong><?php echo esc_html( '$' . $offer['monthly'] ); ?></strong><span>/month</span></p>
		<p><?php echo esc_html( $offer['summary'] ); ?></p>
		<ul><?php foreach ( (array) $offer['limits'] as $limit ) : ?><li><?php echo esc_html( $limit ); ?></li><?php endforeach; ?></ul>
		<?php if ( $url ) : ?>
			<a class="bdai-button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Choose this plan', 'branddad' ); ?></a>
		<?php elseif ( $primary ) : ?>
			<a class="bdai-button" href="<?php echo $audit; ?>"><?php esc_html_e( 'Start free AI business audit', 'branddad' ); ?></a>
			<p class="bdai-readiness"><?php esc_html_e( 'Paid checkout opens only after the final delivery and billing tests pass.', 'branddad' ); ?></p>
		<?php else : ?>
			<span class="bdai-soon"><?php esc_html_e( 'Final delivery test in progress', 'branddad' ); ?></span>
		<?php endif; ?>
	</article>
	<?php
	return (string) ob_get_clean();
}

/** Render one launch offer card. */
function bds_ai_launch_offer_card( $offer ) {
	$url = bds_ai_launch_product_url( $offer['slug'] );
	ob_start();
	?>
	<article class="bdai-card<?php echo ! empty( $offer['popular'] ) ? ' bdai-card--popular' : ''; ?>">
		<?php if ( ! empty( $offer['popular'] ) ) : ?><p class="bdai-badge"><?php esc_html_e( 'Most popular', 'branddad' ); ?></p><?php endif; ?>
		<h3><?php echo esc_html( 'BrandDad AI — ' . $offer['name'] ); ?></h3>
		<p class="bdai-price"><strong><?php echo esc_html( '$' . $offer['price'] ); ?></strong><span>/month</span></p>
		<p><?php echo esc_html( $offer['fit'] ); ?></p>
		<ul>
			<li><?php echo esc_html( number_format_i18n( $offer['credits'] ) . ' BrandDad AI credits each month' ); ?></li>
			<li><?php esc_html_e( 'Access to the complete BrandDad AI workspace', 'branddad' ); ?></li>
			<li><?php esc_html_e( 'Copy, review, strategy, and LinkedIn tools', 'branddad' ); ?></li>
			<li><?php esc_html_e( 'Editable work that you approve before using', 'branddad' ); ?></li>
		</ul>
		<p class="bdai-example"><?php echo esc_html( $offer['example'] ); ?></p>
		<?php if ( $url ) : ?>
			<a class="bdai-button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( 'Choose ' . $offer['name'] ); ?></a>
		<?php else : ?>
			<span class="bdai-soon"><?php esc_html_e( 'Checkout opens after the final billing test', 'branddad' ); ?></span>
		<?php endif; ?>
	</article>
	<?php
	return (string) ob_get_clean();
}

/** Shared launch markup for previews and the final public replacement. */
function bds_ai_launch_markup( $context = 'home' ) {
	$services = esc_url( home_url( '/services/' ) );
	$contact  = esc_url( home_url( '/contact-us/' ) );
	$audit    = esc_url( home_url( '/check-your-website/' ) );
	$directory = esc_url( 'https://directory.branddad.social/add-listing/' );
	ob_start();
	?>
	<div class="bdai-launch" data-version="<?php echo esc_attr( BDS_AI_LAUNCH_PAGES_VERSION ); ?>">
		<section class="bdai-hero" aria-labelledby="bdai-title">
			<p class="bdai-kicker"><?php esc_html_e( 'Big-business tools. Small-business prices.', 'branddad' ); ?></p>
			<h1 id="bdai-title"><?php esc_html_e( 'Your AI-powered business team—without agency prices.', 'branddad' ); ?></h1>
			<p><?php esc_html_e( 'Turn rough ideas into social posts, review replies, marketing plans, and clearer business writing. Tell BrandDad AI what you need, review the result, and move your business forward.', 'branddad' ); ?></p>
			<p class="bdai-starting"><?php esc_html_e( 'Simple monthly plans starting at $14.99.', 'branddad' ); ?></p>
			<div class="bdai-actions"><a class="bdai-button" href="<?php echo $audit; ?>"><?php esc_html_e( 'Start free AI business audit', 'branddad' ); ?></a><a class="bdai-button bdai-button--light" href="#branddad-ai-solutions"><?php esc_html_e( 'Explore AI plans', 'branddad' ); ?></a></div>
			<ul class="bdai-trust"><li><?php esc_html_e( 'No technical skills needed', 'branddad' ); ?></li><li><?php esc_html_e( 'You stay in control', 'branddad' ); ?></li><li><?php esc_html_e( 'Clear monthly limits', 'branddad' ); ?></li></ul>
		</section>
		<section class="bdai-section" id="branddad-ai-help" aria-labelledby="bdai-help-title">
			<p class="bdai-kicker"><?php esc_html_e( 'Less staring. More doing.', 'branddad' ); ?></p>
			<h2 id="bdai-help-title"><?php esc_html_e( 'If marketing feels like another full-time job, start here.', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'BrandDad AI gives you one easy place to get help with the business work that usually gets delayed, rushed, or left unfinished.', 'branddad' ); ?></p>
			<div class="bdai-use-grid">
				<article><span aria-hidden="true">01</span><h3><?php esc_html_e( 'Write faster', 'branddad' ); ?></h3><p><?php esc_html_e( 'Create social captions, business introductions, emails, offers, and other everyday copy.', 'branddad' ); ?></p></article>
				<article><span aria-hidden="true">02</span><h3><?php esc_html_e( 'Answer reviews', 'branddad' ); ?></h3><p><?php esc_html_e( 'Turn a rough reply into a calm, professional response you can edit and approve.', 'branddad' ); ?></p></article>
				<article><span aria-hidden="true">03</span><h3><?php esc_html_e( 'Plan your marketing', 'branddad' ); ?></h3><p><?php esc_html_e( 'Get a clear starting strategy instead of guessing what to say or promote next.', 'branddad' ); ?></p></article>
				<article><span aria-hidden="true">04</span><h3><?php esc_html_e( 'Improve your presence', 'branddad' ); ?></h3><p><?php esc_html_e( 'Strengthen your LinkedIn profile and present your business more clearly online.', 'branddad' ); ?></p></article>
			</div>
		</section>
		<section class="bdai-section" id="branddad-ai-solutions" aria-labelledby="bdai-solutions-title">
			<p class="bdai-kicker"><?php esc_html_e( 'One useful place to start', 'branddad' ); ?></p>
			<h2 id="bdai-solutions-title"><?php esc_html_e( 'Make AI Business Essentials your everyday business helper.', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'The flagship plan is designed to combine the small jobs businesses need every month: content, review replies, basic website and SEO checks, lead capture, and a simple activity report.', 'branddad' ); ?></p>
			<div class="bdai-featured-grid">
				<?php echo bds_ai_launch_featured_offer_card( 'ai-business-essentials', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="bdai-specialists">
					<?php echo bds_ai_launch_featured_offer_card( 'ai-lead-assistant' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo bds_ai_launch_featured_offer_card( 'ai-social-growth' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo bds_ai_launch_featured_offer_card( 'ai-local-seo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</section>
		<section class="bdai-section" id="branddad-ai-plans" aria-labelledby="bdai-plans-title">
			<p class="bdai-kicker"><?php esc_html_e( 'Prefer self-service AI?', 'branddad' ); ?></p>
			<h2 id="bdai-plans-title"><?php esc_html_e( 'Choose an AI Studio credit plan.', 'branddad' ); ?></h2>
			<p><?php esc_html_e( 'AI Studio is separate from Business Essentials. Every Studio plan opens the same workspace; the difference is how many monthly credits you receive.', 'branddad' ); ?></p>
			<div class="bdai-grid"><?php foreach ( bds_ai_launch_offers() as $offer ) { echo bds_ai_launch_offer_card( $offer ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<p class="bdai-credit-note"><?php esc_html_e( 'Different tools can use different numbers of credits. Your workspace shows your allowance, and BrandDad AI never publishes or sends work without your review.', 'branddad' ); ?></p>
		</section>
		<section class="bdai-section bdai-process" aria-labelledby="bdai-process-title">
			<p class="bdai-kicker"><?php esc_html_e( 'Easy from the first day', 'branddad' ); ?></p>
			<h2 id="bdai-process-title"><?php esc_html_e( 'You do not need to learn complicated software.', 'branddad' ); ?></h2>
			<ol><li><strong><?php esc_html_e( 'Choose a plan.', 'branddad' ); ?></strong><span><?php esc_html_e( 'Pick the monthly credit amount that fits your workload.', 'branddad' ); ?></span></li><li><strong><?php esc_html_e( 'Open your BrandDad workspace.', 'branddad' ); ?></strong><span><?php esc_html_e( 'Your business tools are organized in one private place.', 'branddad' ); ?></span></li><li><strong><?php esc_html_e( 'Explain what you need.', 'branddad' ); ?></strong><span><?php esc_html_e( 'Use normal words. BrandDad AI guides the work.', 'branddad' ); ?></span></li><li><strong><?php esc_html_e( 'Review and use it.', 'branddad' ); ?></strong><span><?php esc_html_e( 'Edit anything you want before you post, send, or publish.', 'branddad' ); ?></span></li></ol>
		</section>
		<section class="bdai-section bdai-split">
			<div><p class="bdai-kicker"><?php esc_html_e( 'Self-service AI', 'branddad' ); ?></p><h2><?php esc_html_e( 'Work faster while staying in control.', 'branddad' ); ?></h2><p><?php esc_html_e( 'BrandDad AI helps with drafts, analysis, and recommendations. You decide what gets published, sent, or acted on.', 'branddad' ); ?></p></div>
			<div><p class="bdai-kicker"><?php esc_html_e( 'Need us to do more?', 'branddad' ); ?></p><h2><?php esc_html_e( 'Hands-on help is available separately.', 'branddad' ); ?></h2><p><?php esc_html_e( 'Custom chatbots, sales assistants, managed marketing, and business automation are scoped based on what your business actually needs.', 'branddad' ); ?></p><a class="bdai-text-link" href="<?php echo $contact; ?>"><?php esc_html_e( 'Talk to BrandDad about done-for-you help', 'branddad' ); ?></a></div>
		</section>
		<section class="bdai-section bdai-faq" aria-labelledby="bdai-faq-title"><p class="bdai-kicker"><?php esc_html_e( 'Straight answers', 'branddad' ); ?></p><h2 id="bdai-faq-title"><?php esc_html_e( 'Questions before you choose', 'branddad' ); ?></h2><details><summary><?php esc_html_e( 'What is a BrandDad AI credit?', 'branddad' ); ?></summary><p><?php esc_html_e( 'A credit is a usage unit inside the BrandDad workspace. Different tools and requests can use different amounts. Your plan shows the monthly allowance clearly.', 'branddad' ); ?></p></details><details><summary><?php esc_html_e( 'Will BrandDad AI post or send things automatically?', 'branddad' ); ?></summary><p><?php esc_html_e( 'Not in these self-service plans. You review and approve the work before using it. Connected automation is a separate service with its own setup and permissions.', 'branddad' ); ?></p></details><details><summary><?php esc_html_e( 'Can I cancel?', 'branddad' ); ?></summary><p><?php esc_html_e( 'Yes. Cancellation stops future renewals under the checkout terms. Your access follows the status of your subscription.', 'branddad' ); ?></p></details><details><summary><?php esc_html_e( 'Does this guarantee sales or followers?', 'branddad' ); ?></summary><p><?php esc_html_e( 'No. BrandDad AI helps you complete useful work more efficiently, but it cannot guarantee sales, rankings, followers, or revenue.', 'branddad' ); ?></p></details></section>
		<section class="bdai-final" aria-labelledby="bdai-final-title"><p class="bdai-kicker"><?php esc_html_e( 'Start small. Get more done.', 'branddad' ); ?></p><h2 id="bdai-final-title"><?php esc_html_e( 'Give your business the help it keeps putting off.', 'branddad' ); ?></h2><p><?php esc_html_e( 'Choose an affordable BrandDad AI plan or ask us which option fits your business.', 'branddad' ); ?></p><div class="bdai-actions"><a class="bdai-button" href="#branddad-ai-plans"><?php esc_html_e( 'Compare plans', 'branddad' ); ?></a><a class="bdai-button bdai-button--light" href="<?php echo $contact; ?>"><?php esc_html_e( 'Ask BrandDad', 'branddad' ); ?></a></div></section>
		<section class="bdai-directory-cta" aria-labelledby="bdai-directory-title"><div><p class="bdai-kicker"><?php esc_html_e( 'Already running a business?', 'branddad' ); ?></p><h2 id="bdai-directory-title"><?php esc_html_e( 'Claim your free business listing and unlock limited free AI tools.', 'branddad' ); ?></h2><p><?php esc_html_e( 'A verified BrandDad Directory business will receive a small, ongoing AI allowance after the free-access cost and identity checks are complete. Paid enrollment is never automatic.', 'branddad' ); ?></p></div><a class="bdai-button" href="<?php echo $directory; ?>"><?php esc_html_e( 'Claim your free business listing', 'branddad' ); ?></a></section>
		<?php if ( 'services' !== $context ) : ?><p class="bdai-center"><a class="bdai-button bdai-button--light" href="<?php echo $services; ?>"><?php esc_html_e( 'Browse all BrandDad services', 'branddad' ); ?></a></p><?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/** Replace only the intended page content after the explicit enable flag. */
function bds_ai_launch_replace_content( $content ) {
	if ( ! bds_ai_launch_pages_enabled() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( is_front_page() ) {
		return bds_ai_launch_markup( 'home' );
	}
	if ( is_page( 'services' ) ) {
		return bds_ai_launch_markup( 'services' );
	}
	return $content;
}
add_filter( 'the_content', 'bds_ai_launch_replace_content', 999 );

/** Private manager-only preview while the launch flag is disabled. */
function bds_ai_launch_preview_shortcode( $atts ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	$atts = shortcode_atts( array( 'context' => 'home' ), $atts, 'bds_ai_launch_preview' );
	return bds_ai_launch_markup( 'services' === $atts['context'] ? 'services' : 'home' );
}
add_shortcode( 'bds_ai_launch_preview', 'bds_ai_launch_preview_shortcode' );

/** Scoped, accessible visual system. */
function bds_ai_launch_styles() {
	if ( ! bds_ai_launch_pages_enabled() && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<style id="bds-ai-launch-css">
	.bdai-launch{--ink:#0b172a;--muted:#526070;--blue:#0878ed;--line:#dbe5ef;--soft:#f4f8fc;max-width:1180px;margin:0 auto;padding:20px;color:var(--ink);font-family:Arial,sans-serif}.bdai-launch *{box-sizing:border-box}.bdai-hero,.bdai-final{padding:clamp(42px,7vw,86px);border-radius:28px;background:#071a33;color:#fff}.bdai-hero h1{max-width:900px;margin:8px 0 16px;font-size:clamp(38px,6vw,72px);line-height:1.02;letter-spacing:-.045em;color:#fff}.bdai-hero>p:not(.bdai-kicker),.bdai-final>p:not(.bdai-kicker){max-width:760px;font-size:clamp(17px,2vw,21px);line-height:1.55;color:#e6eef7}.bdai-hero .bdai-starting{font-weight:800;color:#fff!important}.bdai-kicker{margin:0;color:#0878ed;font-size:13px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.bdai-hero .bdai-kicker,.bdai-final .bdai-kicker{color:#9ed0ff}.bdai-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}.bdai-button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 20px;border:2px solid var(--blue);border-radius:10px;background:var(--blue);color:#fff!important;font-weight:800;text-decoration:none}.bdai-button:hover,.bdai-button:focus{filter:brightness(.93);transform:translateY(-1px)}.bdai-button--light{background:#fff;color:var(--ink)!important;border-color:#fff}.bdai-trust{display:flex;flex-wrap:wrap;gap:12px 28px;margin:28px 0 0;padding:20px 0 0;border-top:1px solid rgba(255,255,255,.22);list-style:none}.bdai-trust li{position:relative;padding-left:22px;color:#fff;font-weight:700}.bdai-trust li:before{position:absolute;left:0;content:'✓';color:#9ed0ff}.bdai-section{padding:clamp(38px,6vw,72px) 0}.bdai-section h2,.bdai-final h2,.bdai-directory-cta h2{max-width:820px;margin:8px 0 12px;font-size:clamp(29px,4vw,46px);line-height:1.1;letter-spacing:-.035em;color:var(--ink)}.bdai-final h2{color:#fff}.bdai-section>p:not(.bdai-kicker){max-width:800px;color:var(--muted);font-size:17px;line-height:1.65}.bdai-use-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:30px}.bdai-use-grid article{padding:24px;border:1px solid var(--line);border-radius:16px;background:#fff}.bdai-use-grid span{display:inline-flex;width:38px;height:38px;align-items:center;justify-content:center;border-radius:50%;background:#071a33;color:#fff;font-weight:800}.bdai-use-grid h3{margin:18px 0 8px;font-size:21px}.bdai-use-grid p{margin:0;color:var(--muted);line-height:1.55}.bdai-featured-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:20px;align-items:start;margin-top:30px}.bdai-specialists{display:grid;gap:14px}.bdai-solution{display:flex;flex-direction:column;padding:24px;border:1px solid var(--line);border-radius:18px;background:#fff}.bdai-solution--primary{padding:30px;border:2px solid var(--blue);box-shadow:0 18px 42px rgba(8,120,237,.14)}.bdai-solution .bdai-badge{align-self:flex-start;margin:0 0 16px}.bdai-solution h3{margin:0;font-size:25px}.bdai-solution p,.bdai-solution li{color:var(--muted);line-height:1.55}.bdai-solution ul{padding-left:20px}.bdai-solution .bdai-button,.bdai-solution .bdai-soon{margin-top:auto}.bdai-readiness{font-size:13px}.bdai-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:28px}.bdai-card{position:relative;display:flex;flex-direction:column;border:1px solid var(--line);border-radius:18px;padding:26px;background:#fff;box-shadow:0 12px 34px rgba(8,35,65,.08)}.bdai-card--popular{border:2px solid var(--blue);box-shadow:0 18px 42px rgba(8,120,237,.15)}.bdai-badge{align-self:flex-start;margin:-42px 0 16px;padding:7px 12px;border-radius:99px;background:#071a33;color:#fff!important;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.bdai-card h3{margin:0;font-size:22px;color:var(--ink)}.bdai-card p,.bdai-card li{color:var(--muted);line-height:1.55}.bdai-card ul{padding-left:20px;min-height:160px}.bdai-card .bdai-button{margin-top:auto}.bdai-price{display:flex;align-items:baseline;gap:5px}.bdai-price strong{font-size:38px;color:var(--ink)}.bdai-example{padding:14px;border-radius:10px;background:var(--soft);font-size:14px}.bdai-soon{margin-top:auto;padding:13px;border-radius:9px;background:var(--soft);color:var(--muted);font-weight:700;text-align:center}.bdai-credit-note{margin:20px 0 0!important;font-size:14px!important}.bdai-process{padding-left:clamp(22px,5vw,60px);padding-right:clamp(22px,5vw,60px);border-radius:24px;background:var(--soft)}.bdai-process ol{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;margin:30px 0 0;padding:0;list-style:none;counter-reset:steps}.bdai-process li{counter-increment:steps}.bdai-process li:before{display:block;margin-bottom:14px;content:counter(steps);color:var(--blue);font-size:34px;font-weight:900}.bdai-process strong,.bdai-process span{display:block}.bdai-process span{margin-top:8px;color:var(--muted);line-height:1.5}.bdai-split{display:grid;grid-template-columns:1fr 1fr;gap:22px}.bdai-split>div{padding:30px;border-radius:18px;background:var(--soft)}.bdai-split h2{font-size:clamp(26px,3vw,38px)}.bdai-text-link{display:inline-block;margin-top:12px;color:#005ebd;font-weight:800}.bdai-faq details{max-width:860px;border-top:1px solid var(--line);padding:18px 0}.bdai-faq summary{cursor:pointer;font-weight:800}.bdai-faq details p{color:var(--muted);line-height:1.6}.bdai-final{margin:10px 0 32px}.bdai-final p{max-width:700px}.bdai-directory-cta{display:grid;grid-template-columns:1fr auto;gap:28px;align-items:center;margin:20px 0 40px;padding:30px;border-radius:20px;background:var(--soft)}.bdai-directory-cta h2{font-size:clamp(26px,3vw,38px)}.bdai-directory-cta p{color:var(--muted);line-height:1.55}.bdai-center{text-align:center}@media(max-width:900px){.bdai-use-grid,.bdai-process ol{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.bdai-featured-grid,.bdai-grid,.bdai-split,.bdai-directory-cta{grid-template-columns:1fr}.bdai-card{margin-top:12px}.bdai-card ul{min-height:0}.bdai-directory-cta .bdai-button{width:100%}}@media(max-width:520px){.bdai-launch{padding:10px}.bdai-hero,.bdai-final{padding:34px 22px;border-radius:20px}.bdai-actions,.bdai-actions a{width:100%}.bdai-trust{display:block}.bdai-trust li+li{margin-top:10px}.bdai-use-grid,.bdai-process ol{grid-template-columns:1fr}.bdai-card,.bdai-solution{padding:22px}.bdai-process{padding:32px 22px}}
	</style>
	<?php
}
add_action( 'wp_head', 'bds_ai_launch_styles', 30 );

