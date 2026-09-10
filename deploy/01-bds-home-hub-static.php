<?php
/**
 * Plugin Name: BrandDad Social home hub static render
 * Description: Expands [branddad_home_hub] without calling bdsu_home_hub() (that path 500s the front).
 * Version: 1.2.9
 *
 * 1.2.9 — Remove legacy negative framing from
 *         hero + growth lane + cards; keep positioning positive.
 * 1.2.8 — Growth lane: add LinkedIn Outreach — Launch / Managed cards.
 * 1.2.7 — Conversion copy: one homepage job (check the site). No restyle.
 * 1.2.6 — Drop homepage/hub payment-routing notes; keep crypto notices on products/checkout.
 * 1.2.5 — /services/: expand hub AFTER wpautop (was mangling WhatsApp CTA HTML);
 *         Local & Web pick strip + prices on cards; WA links target=_blank.
 * 1.2.4 — /services/ path steps start at 1 (was 0 · Check your site — looked like $0).
 * 1.2.3 — Homepage sells results: I-Need chips, priced cards, Learn vs hire, honest sister doors.
 * 1.2.2 — Final CTA: white headline on navy + solid WhatsApp button (wpautop-safe).
 * 1.2.1 — Dark-mode #lane-local contrast + hide theme Services H1 when static hub present.
 * 1.2.0 — Services hub: #lane-local / #lane-ai / #lane-more so LC and home jumps
 *         are not dead ends; dark-theme contrast; full real catalog (no invented SKUs).
 *         Still does not call bdsu_home_hub() (that path 500s the front).
 * 1.1.0 — Authority tighten: featured money PDPs, Partnero, converting sister doors,
 *         Learn door, quiz button (unified JS), no duplicate dead CTAs.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserve Partnero / affiliate ?ref= on hub URLs.
 *
 * @param string $url Absolute URL.
 * @return string
 */
function bds_home_static_po( $url ) {
	$url = (string) $url;
	if ( function_exists( 'bdsu_po_keep' ) ) {
		return (string) bdsu_po_keep( $url );
	}
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

function bds_home_static_html() {
	$check = bds_home_static_po( function_exists( 'bdsu_site_check_url' ) ? bdsu_site_check_url() : home_url( '/check-your-website/' ) );
	$svc   = bds_home_static_po( home_url( '/services/' ) );
	$learn = bds_home_static_po( home_url( '/learning-center/' ) );
	$dir   = bds_home_static_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=home&utm_campaign=directory10' );
	$co    = bds_home_static_po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=home' );
	$ht    = bds_home_static_po( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=home' );
	$wa    = 'https://wa.me/18729105115?text=' . rawurlencode( 'Help me choose the right BrandDad service' );
	$p     = static function ( $slug ) {
		return bds_home_static_po( home_url( '/product/' . $slug . '/' ) );
	};
	ob_start();
	?>
	<style id="bds-home-static-css">
	body.branddad-unified .page-header{display:none}
	.bdsu-home-page{background:#f8fafc;min-height:70vh;padding-bottom:72px;color:#0f172a;font-family:Manrope,Syne,"Segoe UI",sans-serif}
	.bdsu-home-hero{max-width:1180px;margin:0 auto;padding:88px 24px 40px;display:grid;grid-template-columns:1.3fr .9fr;gap:28px;align-items:center}
	.bdsu-eyebrow{color:#0372ff;font-weight:800;text-transform:uppercase;letter-spacing:.09em;font-size:13px}
	.bdsu-home-hero h1{font-size:clamp(36px,5vw,56px);line-height:1.08;margin:12px 0 16px;letter-spacing:-.03em}
	.bdsu-home-copy>p{font-size:18px;line-height:1.65;color:#475569;max-width:560px}
	.bdsu-hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin:22px 0 14px}
	.bdsu-primary,.bdsu-secondary{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:10px;font-weight:800;text-decoration:none;border:0;cursor:pointer;font:inherit}
	.bdsu-primary{background:#0372ff;color:#fff!important}
	.bdsu-secondary{background:#fff;color:#0f172a!important;border:2px solid #0f172a}
	.bdsu-trust-row{display:flex;flex-wrap:wrap;gap:14px;color:#64748b;font-size:14px;font-weight:700}
	.bdsu-home-panel{background:#0f172a;color:#e2e8f0;border-radius:18px;padding:22px;display:grid;gap:10px}
	.bdsu-home-panel span{font-weight:800;color:#fff}
	.bdsu-home-panel a,.bdsu-home-panel button{color:#93c5fd;text-decoration:none;font-weight:700;display:flex;justify-content:space-between;padding:10px 0;border:0;border-top:1px solid rgba(148,163,184,.2);background:transparent;cursor:pointer;font:inherit;width:100%;text-align:left}
	.bdsu-need{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0 0}
	.bdsu-need a{display:inline-flex;align-items:center;padding:10px 14px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#0f172a!important;font-weight:800;font-size:13px;text-decoration:none}
	.bdsu-need a:hover{border-color:#0372ff;color:#0372ff!important}
	.bdsu-price{display:block;margin:8px 0 4px;font-weight:800;color:#0f172a}
	.bdsu-best{display:block;color:#64748b;font-size:13px;margin-bottom:8px}
	.bdsu-ecosystem,.bdsu-how,.bdsu-final-cta{max-width:1180px;margin:0 auto;padding:28px 24px}
	.bdsu-ecosystem-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
	.bdsu-ecosystem-grid article,.bdsu-how ol li{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px}
	.bdsu-ecosystem-grid b{color:#0372ff}
	.bdsu-ecosystem-grid a{color:#0372ff;font-weight:800;text-decoration:none}
	.bdsu-how ol{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;list-style:none;margin:18px 0 0;padding:0}
	.bdsu-final-cta{display:flex;justify-content:space-between;gap:16px;align-items:center;background:#0f172a;color:#fff;border-radius:18px;margin:24px auto 0}
	.bdsu-final-cta h2{color:#fff!important;margin:8px 0 0;font-size:clamp(22px,3vw,32px);line-height:1.2}
	.bdsu-final-cta>p,.bdsu-final-cta-actions{margin:0!important;display:flex;align-items:center}
	.bdsu-final-cta p:empty{display:none!important;margin:0!important;padding:0!important}
	.bdsu-final-cta a,.bdsu-final-cta>a,.bdsu-final-cta p>a,.bdsu-final-cta-btn{
		display:inline-flex!important;align-items:center;justify-content:center;gap:0;
		background:#0372ff!important;color:#fff!important;padding:14px 20px;border-radius:10px;
		font-weight:800;text-decoration:none!important;white-space:nowrap;line-height:1.2;border:0
	}
	.bdsu-hero-actions button.bdsu-quiz-open{background:#0372ff!important;color:#fff!important;border:0!important}
	@media(max-width:900px){.bdsu-home-hero,.bdsu-ecosystem-grid,.bdsu-how ol{grid-template-columns:1fr}.bdsu-final-cta{flex-direction:column;align-items:flex-start}.bdsu-need a{width:100%;justify-content:center}}
	html[data-bds-theme="dark"] .bdsu-home-page{background:#0b1220;color:#e8eef7}
	html[data-bds-theme="dark"] .bdsu-home-page h1,html[data-bds-theme="dark"] .bdsu-home-page h2,html[data-bds-theme="dark"] .bdsu-home-page h3{color:#e8eef7}
	html[data-bds-theme="dark"] .bdsu-final-cta h2{color:#fff!important}
	html[data-bds-theme="dark"] .bdsu-home-copy>p,html[data-bds-theme="dark"] .bdsu-trust-row{color:#94a3b8}
	html[data-bds-theme="dark"] .bdsu-ecosystem-grid article,html[data-bds-theme="dark"] .bdsu-how ol li{background:#151e31;border-color:#243044}
	html[data-bds-theme="dark"] .bdsu-secondary{background:#121a2b;color:#e8eef7!important;border-color:#94a3b8}
	</style>
	<div class="bdsu-home-page" data-bdsu-hub="static-1.2.7">
		<section class="bdsu-home-hero">
			<div class="bdsu-home-copy">
				<div class="bdsu-eyebrow">Start here · BrandDad Social</div>
				<h1>Get more customers.</h1>
				<p>We can teach you, or do the job. Ads, search, reviews, and social live here. Logos and sites are on BrandDad.co. Hosting is HostTech. WhatsApp listings are the Directory.</p>
				<div class="bdsu-hero-actions">
					<a class="bdsu-primary" href="<?php echo esc_url( $check ); ?>" data-bds-home-cta="hero" data-bds-cta-label="check_website">Check your website free</a>
					<a class="bdsu-secondary" href="<?php echo esc_url( $svc ); ?>#lane-local" data-bds-home-cta="hero" data-bds-cta-label="hire_us">Let us do it</a>
					<button class="bdsu-secondary bdsu-quiz-open" type="button" data-bds-home-cta="hero" data-bds-cta-label="quiz">I don’t know yet</button>
				</div>
				<nav class="bdsu-need" aria-label="What do you need?">
					<a href="<?php echo esc_url( $svc ); ?>#lane-ai" data-bds-home-cta="need" data-bds-cta-label="customers">I need customers</a>
					<a href="<?php echo esc_url( $co ); ?>" data-bds-home-cta="need" data-bds-cta-label="website">I need a website</a>
					<a href="<?php echo esc_url( $ht ); ?>" data-bds-home-cta="need" data-bds-cta-label="hosting">I need hosting</a>
					<a href="<?php echo esc_url( $svc ); ?>#lane-more" data-bds-home-cta="need" data-bds-cta-label="social">I need social</a>
					<a href="<?php echo esc_url( $learn ); ?>" data-bds-home-cta="need" data-bds-cta-label="learn">I want to learn</a>
					<a href="<?php echo esc_url( $dir ); ?>" data-bds-home-cta="need" data-bds-cta-label="list">List my business</a>
				</nav>
				<div class="bdsu-trust-row"><span>✓ Free site check</span><span>✓ Real prices on the next page</span><span>✓ Directory members save 10% on eligible work</span></div>
			</div>
			<div class="bdsu-home-panel">
				<span>Fastest next step</span>
				<a href="<?php echo esc_url( $check ); ?>" data-bds-home-cta="panel" data-bds-cta-label="check">See if your site is helping you <b>→</b></a>
				<a href="<?php echo esc_url( $svc ); ?>" data-bds-home-cta="panel" data-bds-cta-label="hire">Hire BrandDad for a scoped job <b>→</b></a>
				<a href="<?php echo esc_url( $learn ); ?>" data-bds-home-cta="panel" data-bds-cta-label="learn">Learn it yourself (guides, books, courses) <b>→</b></a>
				<a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer" data-bds-home-cta="panel" data-bds-cta-label="whatsapp">Ask on WhatsApp if you are stuck <b>→</b></a>
			</div>
		</section>
		<section class="bdsu-ecosystem">
			<div class="bdsu-section-intro"><span class="bdsu-eyebrow">One family · four jobs</span><h2>Pick the door that matches the problem.</h2><p>Growth, logos, hosting, and WhatsApp listings each have their own store. Choose the one you need.</p></div>
			<div class="bdsu-ecosystem-grid">
				<article><b>Social</b><h3>Grow</h3><p>Ads, SEO, reviews, and social work. You buy a job with a price — not a vague promise.</p><a href="<?php echo esc_url( $svc ); ?>" data-bds-home-cta="doors" data-bds-cta-label="social">Shop growth →</a></article>
				<article><b>BrandDad.co</b><h3>Look like a real brand</h3><p>Logos from $19. Websites from $199. Bundles from $199. Built on BrandDad.co — not here.</p><a href="<?php echo esc_url( $co ); ?>" data-bds-home-cta="doors" data-bds-cta-label="co">Start a logo or website →</a></article>
				<article><b>HostTech</b><h3>A home for the site</h3><p>Hosting from $9.99/mo plus domains. That store is HostTech. Header there is hosting only.</p><a href="<?php echo esc_url( $ht ); ?>" data-bds-home-cta="doors" data-bds-cta-label="hosting">Shop hosting →</a></article>
				<article><b>Directory</b><h3>Get found on WhatsApp</h3><p>List free. Paid plans start at $6.99 lifetime or $9.99/mo. Members save 10% here on eligible services.</p><a href="<?php echo esc_url( $dir ); ?>" data-bds-home-cta="doors" data-bds-cta-label="directory">Open Directory →</a></article>
			</div>
		</section>
		<section class="bdsu-ecosystem" id="bds-home-offers">
			<div class="bdsu-section-intro"><span class="bdsu-eyebrow">Start with one job</span><h2>Clear result. Real price. Next step is buy or learn.</h2><p>Every card below is a scoped job with a published price. Start with a purchase or a course — not a vague package.</p></div>
			<div class="bdsu-ecosystem-grid">
				<article><b>Local &amp; Web</b><h3>Get found on Google Maps</h3><p>We set up your Google Business Profile the right way. We do not promise #1 rankings.</p><span class="bdsu-price">$129 one-time</span><span class="bdsu-best">Best for: a shop people search for nearby.</span><a href="<?php echo esc_url( $p( 'gbp-setup-optimization' ) ); ?>" data-bds-home-cta="cards" data-bds-cta-label="gbp">Buy GBP setup →</a></article>
				<article><b>Local &amp; Web</b><h3>Know what is broken</h3><p>A written SEO audit with a fix list. You can hire us after, or do the list yourself.</p><span class="bdsu-price">$179 one-time</span><span class="bdsu-best">Best for: “I have a site, but nobody finds it.”</span><a href="<?php echo esc_url( $p( 'website-seo-audit' ) ); ?>" data-bds-home-cta="cards" data-bds-cta-label="audit">Buy the audit →</a></article>
				<article><b>Local &amp; Web</b><h3>Fix a stuck website</h3><p>Scoped repairs. Never paste passwords into a normal form — we send a secure intake.</p><span class="bdsu-price">From $49</span><span class="bdsu-best">Best for: broken pages, forms, or small bugs.</span><a href="<?php echo esc_url( $p( 'fix-my-website' ) ); ?>" data-bds-home-cta="cards" data-bds-cta-label="fix">Buy a fix →</a></article>
				<article><b>AI Ads</b><h3>Run ads without guessing</h3><p>Setup once, then a monthly plan. You still pay Meta for the ads. We charge the management fee.</p><span class="bdsu-price">Setup $99 · Starter $149/mo</span><span class="bdsu-best">Best for: you have a site and a budget to test ads.</span><a href="<?php echo esc_url( $p( 'bd-ai-ads-setup' ) ); ?>" data-bds-home-cta="cards" data-bds-cta-label="aiads">Start AI Ads →</a></article>
				<article><b>Learn</b><h3>Certificate course</h3><p>Local SEO Foundations. Buy once, take lessons, pass the quiz at 80%.</p><span class="bdsu-price">$49 one-time</span><span class="bdsu-best">Best for: you want to do the work yourself.</span><a href="<?php echo esc_url( bds_home_static_po( home_url( '/product/local-seo-foundations/' ) ) ); ?>" data-bds-home-cta="cards" data-bds-cta-label="course">Enroll →</a></article>
				<article><b>Learn</b><h3>Honest SEO Primer (book)</h3><p>A short paid book. No fake student counts. No ranking promises.</p><span class="bdsu-price">$19 one-time</span><span class="bdsu-best">Best for: a cheap first lesson before hiring.</span><a href="<?php echo esc_url( bds_home_static_po( home_url( '/product/honest-seo-primer/' ) ) ); ?>" data-bds-home-cta="cards" data-bds-cta-label="book">Buy the book →</a></article>
			</div>
			<p style="margin:16px 0 0"><a href="<?php echo esc_url( $svc ); ?>" data-bds-home-cta="cards" data-bds-cta-label="all_services">See all services →</a> · <a href="<?php echo esc_url( bds_home_static_po( home_url( '/courses/' ) ) ); ?>">Courses →</a> · <a href="<?php echo esc_url( $learn ); ?>">Free Learning Center →</a></p>
		</section>
		<section class="bdsu-how"><div><span class="bdsu-eyebrow">How to use BrandDad</span><h2>Three honest steps. No scare tactics.</h2></div><ol><li><b>1</b><div><h3>See where you are</h3><p>Run the free website check. Then list your business on the Directory so people can message you.</p></div></li><li><b>2</b><div><h3>Learn it or hire it</h3><p>Guides and courses teach the job. Services do the job. Pick one path. You can switch later.</p></div></li><li><b>3</b><div><h3>Add only what you need</h3><p>Website on BrandDad.co. Hosting on HostTech. Ads and SEO here. Member 10% applies on eligible items.</p></div></li></ol></section>
		<section class="bdsu-final-cta"><div><span class="bdsu-eyebrow">Stuck?</span><h2>Tell us the goal in plain words. We will point to a real product — or a free guide.</h2></div><div class="bdsu-final-cta-actions"><a class="bdsu-final-cta-btn" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer" data-bds-home-cta="bottom" data-bds-cta-label="whatsapp">Chat on WhatsApp</a></div></section>
		<script>(function(){document.addEventListener('click',function(ev){var a=ev.target&&ev.target.closest?ev.target.closest('[data-bds-home-cta]'):null;if(!a||typeof gtag!=='function')return;try{gtag('event','cta_click',{cta_placement:a.getAttribute('data-bds-home-cta')||'',cta_label:a.getAttribute('data-bds-cta-label')||'',link_url:a.href||'',engagement:true});}catch(e){}});})();</script>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * One service card — real SKU only.
 *
 * @param callable $p     Slug → URL.
 * @param string   $cat   Lane label.
 * @param string   $title Title.
 * @param string   $copy  Honest scope.
 * @param string   $slug  Product slug.
 * @param string   $note  Optional extra (e.g. crypto-only).
 * @return string
 */
function bds_svc_static_card( $p, $cat, $title, $copy, $slug, $note = '', $price = '' ) {
	$url   = esc_url( $p( $slug ) );
	$html  = '<article class="bds-svc-card"><a class="bds-svc-card__hit" href="' . $url . '">';
	$html .= '<b>' . esc_html( $cat ) . '</b><h3>' . esc_html( $title ) . '</h3>';
	if ( $price !== '' ) {
		$html .= '<strong class="bds-svc-card__price">' . esc_html( $price ) . '</strong>';
	}
	$html .= '<p>' . esc_html( $copy );
	if ( $note !== '' ) {
		$html .= ' <em>' . esc_html( $note ) . '</em>';
	}
	$html .= '</p><span class="bds-svc-card__cta">View &amp; buy →</span></a></article>';
	return $html;
}

function bds_services_static_html() {
	$po    = 'bds_home_static_po';
	$check = $po( home_url( '/check-your-website/' ) );
	$learn = $po( home_url( '/learning-center/' ) );
	$ai    = $po( home_url( '/ai-ads/' ) );
	$dir   = $po( 'https://directory.branddad.social/registration/?utm_source=branddad_social&utm_medium=services&utm_campaign=directory10' );
	$co    = $po( 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=services' );
	$ht    = $po( 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=services' );
	$p     = static function ( $slug ) use ( $po ) {
		return $po( home_url( '/product/' . $slug . '/' ) );
	};
	$wa    = 'https://wa.me/18729105115?text=' . rawurlencode( 'Help me choose the right BrandDad service' );
	$crypto = 'Crypto checkout only — card and PayPal are not accepted.';
	ob_start();
	?>
	<style id="bds-svc-static-css">
	.bdsu-services-static{background:#f8fafc;color:#0f172a;font-family:Manrope,Syne,"Segoe UI",sans-serif;padding:0 0 72px}
	.page:has(.bdsu-services-static) .page-header{display:none}
	.page:has(.bdsu-services-static) .entry-header,.page:has(.bdsu-services-static) .page-title,.page:has(.bdsu-services-static) h1.entry-title{display:none!important}
	.bdsu-services-static .hero{max-width:1180px;margin:0 auto;padding:88px 24px 28px;text-align:center}
	.bdsu-services-static h1{font-size:clamp(36px,5vw,56px);line-height:1.08;margin:12px 0 16px;letter-spacing:-.03em;color:#0f172a}
	.bdsu-services-static h2,.bdsu-services-static h3{color:#0f172a}
	.bdsu-services-static .hero>p{font-size:18px;line-height:1.65;color:#475569;max-width:720px;margin:0 auto 22px}
	.bdsu-services-static .acts{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
	.bdsu-services-static .pri,.bdsu-services-static .sec{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:10px;font-weight:800;text-decoration:none}
	.bdsu-services-static .pri{background:#0372ff;color:#fff!important}
	.bdsu-services-static .sec{background:#fff;color:#0f172a!important;border:2px solid #0f172a}
	.bdsu-services-static .paths{max-width:1180px;margin:0 auto;padding:8px 24px 18px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
	.bdsu-services-static .paths a{display:block;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;text-decoration:none;color:#0f172a}
	.bdsu-services-static .paths strong{display:block;font-size:15px;margin-bottom:6px}
	.bdsu-services-static .paths span{display:block;color:#64748b;font-size:13px;line-height:1.45;margin-bottom:8px}
	.bdsu-services-static .paths b{color:#0372ff;font-size:13px}
	.bdsu-services-static .lane{max-width:1180px;margin:0 auto;padding:18px 24px 8px}
	.bdsu-services-static .lane-h{margin:0 0 12px}
	.bdsu-services-static .lane-h span{color:#0372ff;font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:12px}
	.bdsu-services-static .lane-h p{color:#475569;margin:6px 0 0;max-width:62ch}
	.bdsu-services-static .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,360px));gap:14px;justify-content:start;align-items:stretch}
	.bdsu-services-static article{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px}
	.bdsu-services-static article b{color:#0372ff;font-size:12px;text-transform:uppercase;letter-spacing:.08em}
	.bdsu-services-static article p{color:#475569;line-height:1.5}
	.bdsu-services-static article a,.bdsu-services-static .bds-svc-card__cta{color:#0372ff;font-weight:800;text-decoration:none}
	.bdsu-services-static .bds-svc-card{padding:0;overflow:hidden}
	.bdsu-services-static .bds-svc-card__hit{display:block;padding:20px;text-decoration:none;color:inherit;height:100%;box-sizing:border-box}
	.bdsu-services-static .bds-svc-card__hit:hover{background:#f8fbff}
	.bdsu-services-static .bds-svc-card__price{display:block;margin:0 0 8px;color:#0f172a;font-size:18px;font-weight:800}
	.bdsu-services-static .doors{max-width:1180px;margin:12px auto 0;padding:12px 24px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
	.bdsu-services-static .final{max-width:1180px;margin:24px auto 0;padding:28px 24px;background:#0f172a;color:#fff;border-radius:18px;display:flex;justify-content:space-between;gap:16px;align-items:center}
	.bdsu-services-static .final a{background:#0372ff;color:#fff!important;padding:14px 20px;border-radius:10px;font-weight:800;text-decoration:none}
	@media(max-width:900px){.bdsu-services-static .grid,.bdsu-services-static .doors,.bdsu-services-static .paths{grid-template-columns:1fr}.bdsu-services-static .final{flex-direction:column;align-items:flex-start}.bdsu-services-static .hero{padding-top:72px}}
	html[data-bds-theme="dark"] .bdsu-services-static{background:#0b1220;color:#e8eef7}
	html[data-bds-theme="dark"] .bdsu-services-static h1,html[data-bds-theme="dark"] .bdsu-services-static h2,html[data-bds-theme="dark"] .bdsu-services-static h3,html[data-bds-theme="dark"] .bdsu-services-static .lane-h h2,html[data-bds-theme="dark"] .bdsu-services-static .lane-h span,html[data-bds-theme="dark"] .bdsu-services-static article h3,html[data-bds-theme="dark"] .bdsu-services-static article strong,html[data-bds-theme="dark"] .bdsu-services-static .paths strong{color:#e8eef7!important}
	html[data-bds-theme="dark"] .bdsu-services-static .hero>p,html[data-bds-theme="dark"] .bdsu-services-static .lane-h p,html[data-bds-theme="dark"] .bdsu-services-static article p,html[data-bds-theme="dark"] .bdsu-services-static .paths span{color:#94a3b8!important}
	html[data-bds-theme="dark"] .bdsu-services-static article,html[data-bds-theme="dark"] .bdsu-services-static .doors article,html[data-bds-theme="dark"] .bdsu-services-static .paths a{background:#151e31;border-color:#243044;color:#e8eef7}
	html[data-bds-theme="dark"] .bdsu-services-static .sec{background:#121a2b;color:#e8eef7!important;border-color:#94a3b8}
	html[data-bds-theme="dark"] .bdsu-services-static .lane-h span,html[data-bds-theme="dark"] .bdsu-services-static article b,html[data-bds-theme="dark"] .bdsu-services-static .paths b,html[data-bds-theme="dark"] .bdsu-services-static article a{color:#60a5fa!important}
	</style>
	<div class="bdsu-services-static" data-bdsu-hub="services-static-1.2.9">
		<section class="hero">
			<div class="bdsu-eyebrow" style="color:#0372ff;font-weight:800;text-transform:uppercase;letter-spacing:.09em;font-size:13px">BrandDad Social · Clear-scope services</div>
			<h1>Stuck on visibility, traffic, or trust?</h1>
			<p>Pick the bottleneck. Buy a scoped fix — local &amp; web, AI ads, or growth systems. Clear scope, upfront pricing.</p>
			<div class="acts">
				<a class="pri" href="#lane-local">Shop Local &amp; Web</a>
				<a class="sec" href="<?php echo esc_url( $check ); ?>">Check your website</a>
				<a class="sec" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer">Ask on WhatsApp</a>
			</div>
		</section>
		<section class="paths" aria-label="Choose your path">
			<a href="<?php echo esc_url( $check ); ?>"><strong>1 · Check your site</strong><span>Free explainable score → honest next step</span><b>Run site check →</b></a>
			<a href="#lane-local"><strong>2 · Fix the foundation</strong><span>GBP, SEO, speed, reviews, site care</span><b>Browse Local &amp; Web →</b></a>
			<a href="#lane-ai"><strong>3 · Run paid ads</strong><span>Setup → Starter → Growth → Scale</span><b>Browse AI Ads →</b></a>
			<a href="#lane-more"><strong>4 · Grow visibility</strong><span>Social, LinkedIn, PR, reputation</span><b>Browse growth →</b></a>
		</section>
		<section class="lane" id="lane-local">
			<div class="lane-h"><span>Most common starting point</span><h2>Local &amp; Web</h2><p>Foundation fixes for Google Business Profile, SEO, speed, reviews, and ongoing care. Buy only the job you need.</p></div>
			<div class="bds-svc-pick" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:14px 16px;margin:0 0 14px">
				<p style="margin:0 0 10px;color:#0f172a;font-size:14px;font-weight:700">Not sure what to buy? Start here:</p>
				<div style="display:flex;flex-wrap:wrap;gap:8px">
					<a style="display:inline-flex;padding:10px 12px;border-radius:999px;background:#fff;border:1px solid #bfdbfe;color:#0f172a;font-weight:800;font-size:13px;text-decoration:none" href="<?php echo esc_url( $p( 'gbp-setup-optimization' ) ); ?>">GBP Setup · $129</a>
					<a style="display:inline-flex;padding:10px 12px;border-radius:999px;background:#fff;border:1px solid #bfdbfe;color:#0f172a;font-weight:800;font-size:13px;text-decoration:none" href="<?php echo esc_url( $p( 'website-seo-audit' ) ); ?>">SEO Audit · $179</a>
					<a style="display:inline-flex;padding:10px 12px;border-radius:999px;background:#fff;border:1px solid #bfdbfe;color:#0f172a;font-weight:800;font-size:13px;text-decoration:none" href="<?php echo esc_url( $p( 'fix-my-website' ) ); ?>">Fix My Website · from $49</a>
					<a style="display:inline-flex;padding:10px 12px;border-radius:999px;background:#fff;border:1px solid #bfdbfe;color:#0f172a;font-weight:800;font-size:13px;text-decoration:none" href="<?php echo esc_url( $p( 'local-seo-management-starter' ) ); ?>">Local SEO · $199/mo</a>
					<a style="display:inline-flex;padding:10px 12px;border-radius:999px;background:#0372ff;border:1px solid #0372ff;color:#fff;font-weight:800;font-size:13px;text-decoration:none" href="https://wa.me/18729105115?text=<?php echo rawurlencode( 'Help me choose a Local & Web service on BrandDad Social' ); ?>" target="_blank" rel="noopener noreferrer">Ask on WhatsApp →</a>
				</div>
			</div>
			<div class="grid">
				<?php
				echo bds_svc_static_card( $p, 'Local & Web', 'GBP Setup & Optimization', 'Claim-ready Google Business Profile fields, categories, and consistency checks — no ranking promises.', 'gbp-setup-optimization', '', 'From $129' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Website Speed Optimization', 'Practical performance fixes for measurable speed signals. Hosting upgrades are quoted separately.', 'website-speed-optimization', '', 'From $149' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Fix My Website', 'Scoped website repairs. Secure intake for access — never paste passwords into normal forms.', 'fix-my-website', '', 'From $49' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Website SEO Audit', 'Explainable technical and on-page audit with prioritized fixes. We do not promise Google rankings.', 'website-seo-audit', '', 'From $179' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Website SEO Fixes', 'Implement prioritized SEO fixes from an audit (yours or ours). No ranking guarantees.', 'website-seo-fixes', '', 'From $249' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Local SEO — Starter', 'GBP hygiene, citations cadence, and monthly reporting. Real reviews only.', 'local-seo-management-starter', '', '$199/mo' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Local SEO — Growth', 'Starter plus local content support and deeper citation work.', 'local-seo-management-growth', '', '$349/mo' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Review Growth — Setup', 'Ethical ask-flow so real customers leave reviews. We never filter “only happy” reviews to Google.', 'google-review-growth-setup', '', 'From $99' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Review Growth — Monthly', 'Monitoring and gentle ask cadence. Real reviews only.', 'google-review-growth-monthly', '', '$79/mo' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Directory Distribution', 'Submit and claim across relevant directories. BrandDad Directory is part of the mix — not the only listing.', 'business-directory-distribution', '', 'See pricing' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Conversion Makeover', 'Clarity, CTA, trust, and form-path improvements — not a full redesign (see BrandDad.co for builds).', 'website-conversion-makeover', '', 'See pricing' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Social Profile Bundle', 'Multi-platform profile polish: bio, links, visuals, and consistency across networks.', 'social-profile-optimization-bundle', '', 'See pricing' );
				echo bds_svc_static_card( $p, 'Local & Web', 'Monthly Website Care', 'Updates, uptime peek, and minor fixes (≤30 min). Larger work is quoted or Fix My Website.', 'monthly-website-care', '', '$69/mo' );
				?>
			</div>
		</section>
		<section class="lane" id="lane-ai">
			<div class="lane-h"><span>Paid acquisition path</span><h2>AI Ads — clear tiers</h2><p>Start with Setup once. Then pick the monthly plan that matches your ad spend. Ad spend stays on the platforms.</p></div>
			<div class="grid">
				<?php
				echo bds_svc_static_card( $p, 'AI Ads', 'AI Ads Setup', 'One-time onboarding: connect ad accounts, verify tracking, and get your first AI campaign drafts.', 'bd-ai-ads-setup' );
				echo bds_svc_static_card( $p, 'AI Ads', 'AI Ads Starter', 'Meta (Facebook + Instagram) AI creative and management for up to $500/mo of your ad spend.', 'bd-ai-ads-starter' );
				echo bds_svc_static_card( $p, 'AI Ads', 'AI Ads Growth', 'Meta plus one more network, creative testing, up to $2,000/mo managed spend.', 'bd-ai-ads-growth' );
				echo bds_svc_static_card( $p, 'AI Ads', 'AI Ads Scale', 'Every supported network, priority automation, up to $5,000/mo managed spend.', 'bd-ai-ads-scale' );
				echo bds_svc_static_card( $p, 'AI Ads', 'AI Ads Extra Spend', 'Add $1,000/mo managed spend capacity to any AI Ads plan. Stackable; custom quote above $5k.', 'bd-ai-ads-spend-tier' );
				?>
				<article><b>AI Ads</b><h3>How AI Ads works</h3><p>Setup → Starter → Growth → Scale. Ad spend stays on the platforms — you only pay BrandDad the management fee.</p><a href="<?php echo esc_url( $ai ); ?>">See AI Ads plans →</a></article>
			</div>
		</section>
		<section class="lane" id="lane-more">
			<div class="lane-h" id="bds-growth-systems"><span>Also available</span><h2>Social, LinkedIn, SEO, PR &amp; reputation</h2><p>Signal-based targeting and real engagement.</p></div>
			<div class="grid">
				<?php
				echo bds_svc_static_card( $p, 'LinkedIn', 'LinkedIn Visibility', 'Monthly professional authority — stay visible to the people who already matter.', 'linkedin-visibility-amplification-system-for-professionals' );
				echo bds_svc_static_card( $p, 'LinkedIn', 'LinkedIn Content', 'Expert-written posts for attention and credibility.', 'linkedin-viral-posts-for-professionals' );
				echo bds_svc_static_card( $p, 'LinkedIn', 'LinkedIn Outreach — Launch', 'One-time setup: ICP, intent-signal targeting, dedicated sender profiles, message sequences. Campaign live in about a week.', 'linkedin-outreach-launch-service', '', 'From $750' );
				echo bds_svc_static_card( $p, 'LinkedIn', 'LinkedIn Outreach — Managed', 'Done-for-you monthly outreach to buyers showing intent. Replies handled to a booked call. Your account stays clean.', 'linkedin-outreach-managed', '', '$1,250/mo' );
				echo bds_svc_static_card( $p, 'Social Growth', 'Instagram Growth', 'Discovery and engagement around your brand.', 'instagram-viral-growth-discovery-system' );
				echo bds_svc_static_card( $p, 'Social Growth', 'Telegram Growth', 'Grow and activate a Telegram community with a repeatable engagement plan.', 'telegram-growth-engagement-system' );
				echo bds_svc_static_card( $p, 'Social Growth', 'Facebook Visibility', 'Stay visible to a relevant Facebook audience with scoped campaigns.', 'facebook-growth-visibility-campaigns' );
				echo bds_svc_static_card( $p, 'Management', 'Social Media Management', '$175/mo content, engagement and account support without managing it all yourself.', 'social-media-management', $crypto );
				echo bds_svc_static_card( $p, 'Management', 'Social Media Management + Video', '$355/mo same monthly plan plus weekly Reel-style videos.', 'social-media-management-video', $crypto );
				echo bds_svc_static_card( $p, 'Management', 'Influencer Engagement', 'Earn targeted attention through real creator and influencer engagement.', 'real-influencers-engagements-ig-fb-tiktok-youtube-more' );
				echo bds_svc_static_card( $p, 'SEO', 'SEO Growth Packages', 'Ongoing SEO foundations and improvements. We do not promise Google rankings.', 'comprehensive-seo-packages-rank-1-on-google' );
				echo bds_svc_static_card( $p, 'Authority & PR', 'Press Release Distribution', 'Turn announcements into credible media assets and wider online visibility.', 'press-release-services' );
				echo bds_svc_static_card( $p, 'Authority & PR', 'IMDb Profile Creation', 'Build a professional IMDb presence for qualified talent and entertainment projects.', 'imdb-profile-creation-services' );
				echo bds_svc_static_card( $p, 'Authority & PR', 'Times Square Billboard', 'Put your brand, launch or announcement on a New York City Times Square screen.', 'nyc-times-square-billboard-video-ads' );
				echo bds_svc_static_card( $p, 'Reputation', 'Google Review Assistance', 'Get expert help assessing and addressing qualifying harmful Google reviews.', 'remove-negative-google-reviews' );
				echo bds_svc_static_card( $p, 'Reputation', 'Airbnb Review Assistance', 'Get professional support for qualifying harmful or policy-violating Airbnb reviews.', 'remove-negative-airbnb-reviews' );
				echo bds_svc_static_card( $p, 'Reputation', 'Instagram Domain Unblock', 'Get help restoring an eligible website or domain blocked from Instagram sharing.', 'unblock-your-website-url-domain-from-instagram' );
				?>
			</div>
		</section>
		<p style="max-width:1180px;margin:8px auto 0;padding:0 24px"><a href="<?php echo esc_url( $learn ); ?>" style="color:#0372ff;font-weight:800;text-decoration:none">Learning Center — playbooks &amp; courses →</a></p>
		<section class="doors">
			<article><h3>Need a website?</h3><p>Logos and premium websites live on BrandDad.co — not here.</p><a href="<?php echo esc_url( $co ); ?>" target="_blank" rel="noopener noreferrer">Start a logo or website →</a></article>
			<article><h3>Need hosting?</h3><p>Domains and hosting live on HostTech — where the site lives.</p><a href="<?php echo esc_url( $ht ); ?>" target="_blank" rel="noopener noreferrer">Shop HostTech plans →</a></article>
			<article><h3>Get found on WhatsApp</h3><p>Directory members save 10% on eligible BrandDad Social services.</p><a href="<?php echo esc_url( $dir ); ?>" target="_blank" rel="noopener noreferrer">Join the Directory — 10% off →</a></article>
		</section>
		<section class="final"><div><span style="color:#93c5fd;font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:12px">Not sure what to buy?</span><h2 style="color:#fff;margin:8px 0 0">Tell us your goal and we’ll point you in the right direction.</h2></div><a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a></section>
	</div>
	<?php
	return ob_get_clean();
}

add_action(
	'init',
	static function () {
		add_shortcode( 'branddad_services_hub', 'bds_services_static_html' );
	},
	99
);

add_filter(
	'the_content',
	static function ( $content ) {
		if ( false === strpos( (string) $content, '[branddad_services_hub]' ) ) {
			return $content;
		}
		return str_replace( array( '<p>[branddad_services_hub]</p>', '[branddad_services_hub]' ), bds_services_static_html(), (string) $content );
	},
	12
);

add_filter( 'body_class', static function ( $classes ) {
	if ( is_front_page() || is_page( 'services' ) ) {
		$classes[] = 'branddad-unified';
	}
	return $classes;
} );

add_filter(
	'the_content',
	static function ( $content ) {
		if ( false === strpos( (string) $content, '[branddad_home_hub]' ) ) {
			return $content;
		}
		return str_replace( array( '<p>[branddad_home_hub]</p>', '[branddad_home_hub]' ), bds_home_static_html(), (string) $content );
	},
	12
);

add_action(
	'template_redirect',
	static function () {
		if ( is_admin() || wp_doing_ajax() || is_feed() ) {
			return;
		}
		ob_start(
			static function ( $html ) {
				if ( false !== strpos( $html, '[branddad_home_hub]' ) ) {
					$html = str_replace( array( '<p>[branddad_home_hub]</p>', '[branddad_home_hub]' ), bds_home_static_html(), $html );
				}
				if ( false !== strpos( $html, '[branddad_services_hub]' ) ) {
					$html = str_replace( array( '<p>[branddad_services_hub]</p>', '[branddad_services_hub]' ), bds_services_static_html(), $html );
				}
				return $html;
			}
		);
	},
	99
);
