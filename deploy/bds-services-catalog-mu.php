<?php
/**
 * Plugin Name: BrandDad Services Catalog
 * Description: Canonical agency/local service definitions, product page sections, and Health Check recommendations. No rank promises; no fake reviews.
 * Version: 1.2.1
 *
 * Deploy:
 *   branddad.social → wp-content/mu-plugins/bds-services-catalog-mu.php
 *   directory.branddad.social → same (for Health Check → product URLs)
 *
 * v1.0.1 — Partnero ref on Health Check recommendations; live product URL prefer.
 * v1.0.3 — Real per-SKU "What's included" for every service (no more placeholder);
 *          per-SKU "Who it's for" instead of one shared line.
 * v1.0.4 — "How we work" risk-reversal block near the price (honest commitments
 *          only); three objection-handling FAQs added to the common set.
 * v1.1.0 — Make Complete Business Fix the broad multi-issue flagship and add the
 *          published Growth Consultation as the lower-commitment diagnostic path.
 * v1.2.0 — Add delivery, revision, policy, and inspectable sample information at
 *          the buying decision; no invented testimonials or outcome claims.
 * v1.2.1 — Align the website checker with the live $19.99 AI optimizer product.
 *          FormWise plans the work; site changes require a verified connection,
 *          explicit approval, verification, and rollback support.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_SVC_CAT_VER' ) ) {
	return;
}
define( 'BDS_SVC_CAT_VER', '1.2.1' );

/**
 * Canonical service catalog (SKU keys).
 *
 * @return array<string,array<string,mixed>>
 */
function bds_svc_catalog() {
	$social = 'https://branddad.social';
	return array(
		'complete-business-fix'              => array(
			'slug'     => 'complete-business-fix',
			'title'    => 'BrandDad Complete Business Fix',
			'price'    => 499,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/complete-business-fix/',
			'blurb'    => 'A scoped package of the highest-priority website, profile, local SEO, and conversion repairs found in the audit.',
			'issues'   => array( 'gbp_incomplete', 'nap_inconsistent', 'slow_ttfb', 'large_assets', 'seo_meta', 'seo_headings', 'thin_content', 'weak_cta', 'no_contact', 'confusing_offer' ),
			'secure_intake' => true,
		),
		'business-growth-consultation-60'    => array(
			'slug'     => 'business-growth-consultation-60',
			'title'    => 'Business Growth Consultation (60 min)',
			'price'    => 149,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/business-growth-consultation-60/',
			'blurb'    => 'A focused 60-minute diagnosis with a practical priority list before you commit to implementation.',
			'issues'   => array( 'confusing_offer', 'local_visibility', 'weak_cta' ),
		),
		'gbp-setup-optimization'            => array(
			'slug'     => 'gbp-setup-optimization',
			'title'    => 'Google Business Profile Setup & Optimization',
			'price'    => 129,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/gbp-setup-optimization/',
			'blurb'    => 'Claim-ready GBP fields, categories, photos guidance, and consistency checks — no ranking promises.',
			'issues'   => array( 'gbp_incomplete', 'gbp_missing', 'nap_inconsistent' ),
		),
		'website-speed-optimization'        => array(
			'slug'     => 'website-speed-optimization',
			'title'    => 'AI Website Improvement Plan',
			'price'    => 19.99,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/website-speed-optimization/',
			'blurb'    => 'AI analyzes the public signals, explains the highest-value fixes, and prepares a prioritized plan. Connect WordPress and approve supported changes before BrandDad applies and verifies them. Hosting, CDN, paid-plugin, and custom-code work is quoted separately.',
			'issues'   => array( 'slow_ttfb', 'no_compression', 'large_assets', 'perf_poor' ),
			'secure_intake' => true,
		),
		'fix-my-website'                     => array(
			'slug'     => 'fix-my-website',
			'title'    => 'Fix My Website',
			'price'    => 49,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/fix-my-website/',
			'blurb'    => 'Starting at $49 for a scoped fix. Secure intake for access — never paste passwords into normal forms.',
			'issues'   => array( 'site_down', 'https_fail', 'broken_critical', 'form_broken' ),
			'secure_intake' => true,
		),
		'website-seo-audit'                 => array(
			'slug'     => 'website-seo-audit',
			'title'    => 'Website SEO Audit',
			'price'    => 179,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/website-seo-audit/',
			'blurb'    => 'Explainable technical + on-page audit with prioritized fixes. We do not promise Google rankings.',
			'issues'   => array( 'seo_meta', 'seo_headings', 'no_sitemap', 'no_robots', 'no_schema', 'thin_content' ),
		),
		'website-seo-fixes'                 => array(
			'slug'     => 'website-seo-fixes',
			'title'    => 'Website SEO Fixes',
			'price'    => 249,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/website-seo-fixes/',
			'blurb'    => 'Implement prioritized SEO fixes from an audit (yours or ours). No ranking guarantees.',
			'issues'   => array( 'seo_fixes_needed' ),
		),
		'local-seo-management-starter'      => array(
			'slug'     => 'local-seo-management-starter',
			'title'    => 'Local SEO Management — Starter',
			'price'    => 199,
			'type'     => 'subscription',
			'period'   => 'month',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/local-seo-management-starter/',
			'blurb'    => '$199/mo — GBP hygiene, citations cadence, monthly reporting. No fake reviews.',
			'issues'   => array( 'local_visibility', 'citations_weak' ),
		),
		'local-seo-management-growth'       => array(
			'slug'     => 'local-seo-management-growth',
			'title'    => 'Local SEO Management — Growth',
			'price'    => 349,
			'type'     => 'subscription',
			'period'   => 'month',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/local-seo-management-growth/',
			'blurb'    => '$349/mo — Starter plus content/local page support and deeper citation work.',
			'issues'   => array( 'local_visibility', 'citations_weak', 'local_content' ),
		),
		'google-review-growth-setup'        => array(
			'slug'     => 'google-review-growth-setup',
			'title'    => 'Google Review Growth System — Setup',
			'price'    => 99,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/google-review-growth-setup/',
			'blurb'    => 'Ethical ask-flow for real customers. We never filter “only happy” reviews to Google.',
			'issues'   => array( 'few_reviews', 'no_review_ask' ),
		),
		'google-review-growth-monthly'      => array(
			'slug'     => 'google-review-growth-monthly',
			'title'    => 'Google Review Growth System — Monthly',
			'price'    => 79,
			'type'     => 'subscription',
			'period'   => 'month',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/google-review-growth-monthly/',
			'blurb'    => '$79/mo monitoring + gentle ask cadence. Real reviews only.',
			'issues'   => array( 'few_reviews', 'review_velocity' ),
		),
		'business-directory-distribution'   => array(
			'slug'     => 'business-directory-distribution',
			'title'    => 'Business Directory Distribution',
			'price'    => 149,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/business-directory-distribution/',
			'blurb'    => 'Submit/claim across relevant directories. BrandDad Directory is part of the ecosystem — not the only listing we place.',
			'issues'   => array( 'directory_gap', 'unclaimed_listing' ),
		),
		'website-conversion-makeover'       => array(
			'slug'     => 'website-conversion-makeover',
			'title'    => 'Website Conversion Makeover',
			'price'    => 199,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/website-conversion-makeover/',
			'blurb'    => 'Clarity, CTA, trust, and form path improvements — not a full redesign (see BrandDad.co for builds).',
			'issues'   => array( 'weak_cta', 'no_contact', 'confusing_offer', 'form_friction' ),
		),
		'social-profile-optimization-bundle'=> array(
			'slug'     => 'social-profile-optimization-bundle',
			'title'    => 'Social Profile Optimization Bundle',
			'price'    => 149,
			'type'     => 'simple',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/social-profile-optimization-bundle/',
			'blurb'    => 'Multi-platform profile polish (bio, links, visuals, consistency). Extends beyond LinkedIn-only packages.',
			'issues'   => array( 'social_incomplete', 'social_inconsistent' ),
		),
		'monthly-website-care'              => array(
			'slug'     => 'monthly-website-care',
			'title'    => 'Monthly Website Care',
			'price'    => 69,
			'type'     => 'subscription',
			'period'   => 'month',
			'cat'      => 'local-web-services',
			'url'      => $social . '/product/monthly-website-care/',
			'blurb'    => '$69/mo updates + uptime peek + minor fixes (≤30 min). Larger work is quoted or Fix My Website.',
			'issues'   => array( 'care_needed', 'outdated_cms', 'plugin_risk' ),
		),
	);
}

/**
 * Map Health Check issue codes → service keys (ordered, capped later).
 *
 * @param string[] $issue_codes Codes.
 * @return array<int,array<string,mixed>>
 */
function bds_svc_recommend_for_issues( $issue_codes ) {
	$issue_codes = array_values( array_unique( array_map( 'sanitize_key', (array) $issue_codes ) ) );
	$cat         = bds_svc_catalog();
	$scored      = array();
	foreach ( $cat as $key => $svc ) {
		$hits = array_intersect( $issue_codes, (array) $svc['issues'] );
		if ( ! $hits ) {
			continue;
		}
		// Outreach / cold-contact never recommended.
		if ( false !== strpos( $key, 'outreach' ) ) {
			continue;
		}
		$url = function_exists( 'bds_svc_live_url' ) ? bds_svc_live_url( $key ) : '';
		if ( $url === '' ) {
			$url = (string) $svc['url'];
		}
		if ( function_exists( 'bds_po_url' ) ) {
			$url = bds_po_url( $url );
		}
		$scored[] = array(
			'key'    => $key,
			'title'  => $svc['title'],
			'url'    => $url,
			'price'  => $svc['price'],
			'period' => isset( $svc['period'] ) ? $svc['period'] : '',
			'blurb'  => $svc['blurb'],
			'why'    => 'Addresses: ' . implode( ', ', array_map( 'bds_svc_issue_label', $hits ) ) . '.',
			'hits'   => count( $hits ),
		);
	}
	usort(
		$scored,
		static function ( $a, $b ) {
			return (int) $b['hits'] - (int) $a['hits'];
		}
	);
	// Cap — no spam upsells.
	return array_slice( $scored, 0, 4 );
}

/**
 * @param string $code Issue code.
 * @return string
 */
function bds_svc_issue_label( $code ) {
	$map = array(
		'gbp_incomplete'      => 'incomplete Google Business Profile',
		'gbp_missing'         => 'missing Google Business Profile signals',
		'nap_inconsistent'    => 'name/address/phone consistency',
		'slow_ttfb'           => 'slow server response',
		'no_compression'      => 'missing compression',
		'large_assets'        => 'heavy page assets',
		'perf_poor'           => 'weak performance signals',
		'site_down'           => 'site availability',
		'https_fail'          => 'HTTPS / certificate issues',
		'broken_critical'     => 'critical broken links',
		'form_broken'         => 'broken contact form',
		'seo_meta'            => 'title/meta gaps',
		'seo_headings'        => 'heading structure',
		'no_sitemap'          => 'missing sitemap',
		'no_robots'           => 'robots.txt gaps',
		'no_schema'           => 'missing structured data',
		'thin_content'        => 'thin on-page content',
		'seo_fixes_needed'    => 'known SEO fixes',
		'local_visibility'    => 'local visibility gaps',
		'citations_weak'      => 'weak directory presence',
		'local_content'       => 'local content gaps',
		'few_reviews'         => 'few public reviews',
		'no_review_ask'       => 'no review ask path',
		'review_velocity'     => 'low review velocity',
		'directory_gap'       => 'directory coverage gaps',
		'unclaimed_listing'   => 'unclaimed BrandDad listing',
		'weak_cta'            => 'weak calls to action',
		'no_contact'          => 'missing contact path',
		'confusing_offer'     => 'unclear offer',
		'form_friction'       => 'form friction',
		'social_incomplete'   => 'incomplete social profiles',
		'social_inconsistent' => 'inconsistent social branding',
		'care_needed'         => 'ongoing site care',
		'outdated_cms'        => 'outdated software signals',
		'plugin_risk'         => 'plugin/theme risk signals',
	);
	$code = sanitize_key( $code );
	return isset( $map[ $code ] ) ? $map[ $code ] : str_replace( '_', ' ', $code );
}

/**
 * Product page body shortcode: [bds_service_page sku="website-seo-audit"]
 *
 * @param array<string,string> $atts Attributes.
 * @return string
 */
function bds_svc_page_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'sku' => '' ), $atts, 'bds_service_page' );
	$key  = sanitize_title( (string) $atts['sku'] );
	$cat  = bds_svc_catalog();
	if ( $key === '' || empty( $cat[ $key ] ) ) {
		// Try current product slug.
		if ( function_exists( 'is_product' ) && is_product() ) {
			$key = (string) get_post_field( 'post_name', get_queried_object_id() );
		}
	}
	if ( empty( $cat[ $key ] ) ) {
		return '';
	}
	$s     = $cat[ $key ];
	$price = isset( $s['price'] ) ? (float) $s['price'] : 0;
	$per   = ! empty( $s['period'] ) ? '/' . $s['period'] : '';
	$faq   = bds_svc_faq_for( $key );
	$inc   = bds_svc_includes_for( $key );
	$who   = bds_svc_who_for( $key );
	$proc  = bds_svc_process_for( $key );
	$decision = bds_svc_decision_details( $key );

	ob_start();
	?>
	<div class="bds-svc-page" data-sku="<?php echo esc_attr( $key ); ?>">
		<section class="bds-svc-block">
			<h2>The problem</h2>
			<p><?php echo esc_html( bds_svc_problem_for( $key ) ); ?></p>
		</section>
		<section class="bds-svc-block">
			<h2>Our approach</h2>
			<p><?php echo esc_html( $s['blurb'] ); ?></p>
		</section>
		<section class="bds-svc-block">
			<h2>What's included</h2>
			<ul><?php foreach ( $inc as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?></ul>
		</section>
		<section class="bds-svc-block">
			<h2>Who it's for</h2>
			<p><?php echo esc_html( $who ); ?></p>
		</section>
		<section class="bds-svc-block bds-svc-decision" aria-labelledby="bds-svc-before-you-buy">
			<h2 id="bds-svc-before-you-buy">Before you buy</h2>
			<dl>
				<div><dt>Delivery target</dt><dd><?php echo esc_html( $decision['delivery'] ); ?></dd></div>
				<div><dt>Final deliverable</dt><dd><?php echo esc_html( $decision['deliverable'] ); ?></dd></div>
				<div><dt>Revisions</dt><dd><?php echo esc_html( $decision['revisions'] ); ?></dd></div>
			</dl>
			<?php if ( ! empty( $decision['sample'] ) ) : ?>
				<details><summary>Preview the deliverable structure</summary><p><?php echo esc_html( $decision['sample'] ); ?></p></details>
			<?php endif; ?>
			<p class="bds-svc-policy-links"><a href="<?php echo esc_url( home_url( '/refund_returns/' ) ); ?>">Refund policy</a> · <a href="<?php echo esc_url( home_url( '/terms-conditions/' ) ); ?>">Terms</a> · <a href="https://branddad.dayschedule.com/bdsbooking">Talk before buying</a></p>
		</section>
		<section class="bds-svc-block">
			<h2>Process</h2>
			<ol><?php foreach ( $proc as $step ) : ?><li><?php echo esc_html( $step ); ?></li><?php endforeach; ?></ol>
		</section>
		<section class="bds-svc-block bds-svc-price">
			<h2>Investment</h2>
			<p><strong><?php echo esc_html( '$' . number_format( $price, 0 ) . $per ); ?></strong>
			<?php if ( ! empty( $s['secure_intake'] ) ) : ?>
				— after purchase you’ll get a <em>secure</em> intake link for access details (never paste passwords into this page).
			<?php endif; ?>
			</p>
		</section>
		<section class="bds-svc-block bds-svc-trust">
			<h2>How we work</h2>
			<ul><?php foreach ( bds_svc_trust_lines( $key ) as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?></ul>
		</section>
		<?php if ( $faq ) : ?>
		<section class="bds-svc-block">
			<h2>FAQ</h2>
			<?php foreach ( $faq as $qa ) : ?>
				<details><summary><?php echo esc_html( $qa['q'] ); ?></summary><p><?php echo esc_html( $qa['a'] ); ?></p></details>
			<?php endforeach; ?>
		</section>
		<?php endif; ?>
		<section class="bds-svc-block bds-svc-cta">
			<p><a class="button" href="#bds-svc-buy">Get started</a>
			<a class="button button-secondary" href="<?php echo esc_url( function_exists( 'bds_health_entry_url' ) ? bds_health_entry_url() : 'https://branddad.social/check-your-website/' ); ?>">Run a free site check first</a></p>
		</section>
	</div>
	<style>
	.bds-svc-page{max-width:720px;margin:1.5rem auto;line-height:1.55}
	.bds-svc-block{margin:0 0 1.75rem}
	.bds-svc-block h2{font-size:1.25rem;margin:0 0 .5rem}
	.bds-svc-block details{margin:.4rem 0;padding:.5rem .75rem;border:1px solid rgba(0,0,0,.08);border-radius:6px}
	.bds-svc-trust{background:rgba(0,0,0,.03);border-radius:10px;padding:1rem 1.25rem}
	.bds-svc-trust ul{margin:.25rem 0 0;padding-left:1.1rem}
	.bds-svc-trust li{margin:.3rem 0}
	.bds-svc-cta .button{margin-right:.5rem;margin-bottom:.5rem}
	.bds-svc-decision{background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:1.1rem 1.25rem}
	.bds-svc-decision dl{display:grid;gap:.75rem;margin:.75rem 0}
	.bds-svc-decision dl div{display:grid;grid-template-columns:minmax(120px,.42fr) 1fr;gap:1rem}
	.bds-svc-decision dt{font-weight:800;color:#0f172a}.bds-svc-decision dd{margin:0;color:#334155}
	.bds-svc-decision summary{cursor:pointer;font-weight:800}.bds-svc-policy-links{margin:.9rem 0 0}
	@media(max-width:560px){.bds-svc-decision dl div{grid-template-columns:1fr;gap:.15rem}}
	</style>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'bds_service_page', 'bds_svc_page_shortcode' );

/**
 * Auto-append service template on catalog products missing rich content.
 *
 * @param string $content Content.
 * @return string
 */
function bds_svc_maybe_append_template( $content ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$slug = (string) get_post_field( 'post_name', get_the_ID() );
	$cat  = bds_svc_catalog();
	if ( empty( $cat[ $slug ] ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'bds-svc-page' ) || false !== strpos( $content, '[bds_service_page' ) ) {
		return $content;
	}
	return $content . "\n" . bds_svc_page_shortcode( array( 'sku' => $slug ) );
}
add_filter( 'the_content', 'bds_svc_maybe_append_template', 24 );

/**
 * Decision details buyers need before checkout. Copy is deliberately concrete
 * and does not claim outcomes outside BrandDad's control.
 *
 * @param string $key SKU.
 * @return array{delivery:string,deliverable:string,revisions:string,sample:string}
 */
function bds_svc_decision_details( $key ) {
	$details = array(
		'delivery'    => 'A firm date is confirmed after intake; most one-time services are delivered within 5–10 business days after we receive everything required.',
		'deliverable' => 'The completed work plus a written change log or handoff summary in your BrandDad dashboard.',
		'revisions'   => 'Reasonable corrections needed to match the written scope are included. New requests are quoted before extra work begins.',
		'sample'      => '',
	);
	$overrides = array(
		'website-seo-audit' => array(
			'deliverable' => 'A written, prioritized audit covering technical, on-page, speed, metadata, crawlability, and conversion findings.',
			'sample'      => 'Executive summary → issue and evidence → why it matters → priority and effort → recommended fix → DIY or implementation path → final action sequence.',
		),
		'website-seo-fixes' => array(
			'deliverable' => 'Implemented agreed fixes, before/after verification, a change log, and handoff notes.',
		),
		'website-speed-optimization' => array(
			'delivery'    => 'Your AI improvement plan is prepared after intake. Supported implementation starts only after you connect WordPress and approve the exact proposed changes.',
			'deliverable' => 'A prioritized plan with evidence, recommended changes, acceptance checks, and a clear split between supported automatic fixes and work that requires a separate quote. Approved supported fixes include before/after verification and rollback on failure.',
			'revisions'   => 'One regenerated plan is included when the original plan missed information supplied in the intake. New implementation work outside the supported action list is quoted separately.',
			'sample'      => 'Issue and evidence → impact → recommended change → risk → approval required → acceptance check → implementation or escalation path.',
		),
		'fix-my-website' => array(
			'delivery'    => 'Diagnosis begins after secure access and scope confirmation. Small fixes target 2–5 business days; larger work is quoted before it starts.',
			'deliverable' => 'The agreed repair, verification that the affected path works, and a short explanation of the root cause.',
		),
		'business-growth-consultation-60' => array(
			'delivery'    => 'The live 60-minute session is booked at a time you choose; the written priority summary follows within 2 business days.',
			'deliverable' => 'A focused diagnosis and written priority list showing what to do now, later, and not at all.',
			'revisions'   => 'One clarification round on the written summary is included within 7 days.',
			'sample'      => 'Current bottleneck → evidence → priority actions → owner and effort → recommended sequence → services only where relevant.',
		),
	);
	if ( isset( $overrides[ $key ] ) ) {
		$details = array_merge( $details, $overrides[ $key ] );
	}
	$cat = bds_svc_catalog();
	if ( ! empty( $cat[ $key ]['period'] ) && 'month' === $cat[ $key ]['period'] ) {
		$details['delivery'] = 'Kickoff timing is confirmed after intake; monthly work and reporting follow the cadence stated in the written scope.';
		$details['deliverable'] = 'The recurring work listed on this page plus the monthly status or reporting included in your plan.';
	}
	return $details;
}

/** @param string $key SKU. @return string */
function bds_svc_problem_for( $key ) {
	$map = array(
		'gbp-setup-optimization'             => 'Your Google Business Profile is incomplete, inconsistent, or hard for customers to trust — so local discovery suffers.',
		'website-speed-optimization'         => 'Your site has a measurable performance gap. Start with a clear AI improvement plan, then approve only the supported changes you want BrandDad to apply and verify.',
		'fix-my-website'                      => 'Something on the site is broken or blocking sales. You need a scoped fix with safe access handling.',
		'website-seo-audit'                  => 'You don’t know which technical and on-page gaps matter most. You need an explainable audit — not ranking promises.',
		'website-seo-fixes'                  => 'You already know (or just learned) what to fix. You need implementation, not another vague report.',
		'local-seo-management-starter'       => 'Local presence decays without steady GBP hygiene and citation care.',
		'local-seo-management-growth'        => 'You need ongoing local work plus content support beyond basic hygiene.',
		'google-review-growth-setup'         => 'You have too few real Google reviews and no clean ask-flow for happy customers — without gaming the system.',
		'google-review-growth-monthly'       => 'Review asks need a steady, ethical cadence after setup.',
		'business-directory-distribution'    => 'You’re missing from directories customers and partners still use. BrandDad Directory helps, but it’s not the only place that matters.',
		'website-conversion-makeover'        => 'Traffic arrives but the page doesn’t convert — unclear offer, weak CTA, or friction — and you don’t need a full redesign.',
		'social-profile-optimization-bundle' => 'Profiles look unfinished or inconsistent across platforms, which weakens trust before anyone clicks your site.',
		'monthly-website-care'               => 'Sites drift: updates, small breaks, and quiet downtime. You need light monthly care with clear limits.',
	);
	return isset( $map[ $key ] ) ? $map[ $key ] : 'A focused BrandDad service for a specific online presence gap.';
}

/** @param string $key SKU. @return string[] */
function bds_svc_includes_for( $key ) {
	$defaults = array(
		'Written scope confirmation after intake',
		'Status updates in your BrandDad dashboard',
		'Honest deliverable — no fake reviews or ranking guarantees',
	);
	$extra = array(
		'gbp-setup-optimization' => array(
			'Primary and secondary category selection with rationale',
			'Every profile field completed to Google’s guidelines',
			'NAP consistency check against your site and top directories',
			'Photo brief — what to shoot and at what size',
			'Services / products list drafted',
			'A one-page “keep it healthy” checklist',
		),
		'website-speed-optimization' => array(
			'Public-signal performance diagnosis',
			'Prioritized AI improvement plan in plain language',
			'Supported-versus-quoted implementation split',
			'Risk and rollback guidance for every proposed change',
			'Acceptance checks you can understand before approving',
			'Optional secure WordPress connection for supported fixes',
			'Before/after verification for any approved applied fix',
		),
		'fix-my-website' => array(
			'Secure access intake (no plaintext password forms)',
			'Diagnosis of the reported issue with root cause',
			'Scoped fix, or a clear fixed-price quote if out of range',
			'Confirmation the rest of the site still works after the fix',
		),
		'website-seo-audit' => array(
			'Prioritized issue list, each with the WHY and rough effort / impact',
			'Core Web Vitals snapshot',
			'On-page review of your top money pages',
			'Titles and meta gap list',
			'A recommended fix sequence (DIY vs SEO Fixes vs Speed)',
		),
		'website-seo-fixes' => array(
			'Implementation of the agreed fix list — titles, meta, headings, internal links, schema, redirects',
			'Before / after check on each item',
			'A change log of everything touched',
			'Re-crawl to confirm nothing broke',
			'Handover notes',
		),
		'local-seo-management-starter' => array(
			'Monthly GBP audit and fixes (hours, categories, attributes, posts)',
			'A steady citation cadence each month',
			'Duplicate-listing checks',
			'GBP Posts published each month',
			'Monthly report: calls, direction requests, and search terms',
		),
		'local-seo-management-growth' => array(
			'Everything in Starter',
			'One local / service page drafted or improved each month',
			'Deeper citation building and cleanup',
			'Basic on-page fixes to those pages',
			'Expanded monthly report with page-level movement',
		),
		'google-review-growth-setup' => array(
			'Real-customer ask flow wired to your Google profile (email / SMS / QR / link)',
			'Explicit policy: no filtering only-happy reviews to Google',
			'Timing and wording templates',
			'A staff one-pager on when and how to ask',
		),
		'google-review-growth-monthly' => array(
			'New-review monitoring and alerts',
			'Scheduled ask sends to recent customers you upload',
			'Response drafts for new reviews (you approve before posting)',
			'Monthly review count and rating trend',
			'No incentivised or fake reviews — ever',
		),
		'business-directory-distribution' => array(
			'A shortlist of directories that actually matter for your industry and city',
			'Submit or claim on each with consistent NAP',
			'BrandDad Directory set up as one of them — not the only one',
			'Logins and a handoff sheet you keep',
		),
		'website-conversion-makeover' => array(
			'Heuristic review of one key page — offer clarity, hierarchy, CTA, trust, friction',
			'A prioritized change list with the reasoning',
			'Rewritten headline, subhead and primary CTA',
			'Form / steps reduction plan',
			'Not a full visual redesign — builds live on BrandDad.co',
		),
		'social-profile-optimization-bundle' => array(
			'Rewritten bio / about for each platform you use',
			'Consistent name, handle, category and link across profiles',
			'Link-in-bio / primary-link recommendation',
			'Profile and cover image specs with crop guidance',
			'A before / after sheet',
		),
		'monthly-website-care' => array(
			'Monthly core / plugin / theme updates with a pre-update snapshot',
			'Uptime check and broken-link scan',
			'Minor fixes up to 30 minutes each month',
			'Larger work quoted separately or via Fix My Website',
			'A monthly one-line status',
		),
	);
	return array_merge( isset( $extra[ $key ] ) ? $extra[ $key ] : array(), $defaults );
}

/** @param string $key SKU. @return string */
function bds_svc_who_for( $key ) {
	$map = array(
		'gbp-setup-optimization'             => 'Local businesses that rely on Google Maps and “near me” searches to get found.',
		'website-speed-optimization'         => 'Owners who want an affordable, specific improvement plan before granting access or paying for custom technical work.',
		'fix-my-website'                     => 'Anyone with a specific broken thing — a form, a page, an error — that needs fixing now.',
		'website-seo-audit'                  => 'Owners who want to know exactly what to fix before spending on SEO.',
		'website-seo-fixes'                  => 'Anyone holding a fix list (ours or theirs) who needs it implemented, not re-explained.',
		'local-seo-management-starter'       => 'Local businesses that need someone keeping their Google presence tidy every month.',
		'local-seo-management-growth'        => 'Local businesses in competitive areas that need content behind the local work.',
		'google-review-growth-setup'         => 'Businesses with too few Google reviews and no clean way to ask for them.',
		'google-review-growth-monthly'       => 'Businesses that want a steady, compliant review flow without having to think about it.',
		'business-directory-distribution'    => 'Businesses missing from the directories their customers and partners still check.',
		'website-conversion-makeover'        => 'Sites that get traffic but not enough enquiries, where a full rebuild is overkill.',
		'social-profile-optimization-bundle' => 'Anyone whose social profiles look unfinished or say different things on each platform.',
		'monthly-website-care'               => 'Owners who want a safe pair of hands on updates and small fixes, with clear limits.',
	);
	return isset( $map[ $key ] ) ? $map[ $key ] : 'Local and online businesses that want clear, ethical help — not vanity metrics or scare tactics.';
}

/** @param string $key SKU. @return string[] */
function bds_svc_process_for( $key ) {
	$base = array(
		'Purchase and complete intake',
		'We confirm scope and move to In Progress',
		'You get updates; Waiting on Customer if we need something',
		'Completed (or Recurring/Active for monthly plans)',
	);
	if ( $key === 'fix-my-website' ) {
		array_unshift( $base, 'Use the secure intake link for credentials — never email passwords in plain text if avoidable' );
	}
	return $base;
}

/**
 * Risk-reversal / "how we work" lines shown near the price. Honest commitments
 * only — restates what the rest of the page already promises, in one place at
 * the buy decision.
 *
 * @param string $key SKU.
 * @return string[]
 */
function bds_svc_trust_lines( $key ) {
	$cat   = bds_svc_catalog();
	$lines = array(
		'Written scope before any work starts — what is included, and what is not.',
		'Revisions until the deliverable matches that scope.',
		'Reporting comes straight from the platform. We do not estimate or invent numbers.',
		'Progress is visible in your BrandDad dashboard, start to finish.',
	);
	if ( ! empty( $cat[ $key ]['period'] ) && 'month' === $cat[ $key ]['period'] ) {
		$lines[] = 'Month to month — cancel any time and the next month simply does not bill.';
	}
	if ( ! empty( $cat[ $key ]['secure_intake'] ) ) {
		$lines[] = 'Access details are handled through a secure intake link, never a normal form.';
	}
	$lines[] = 'A real person on WhatsApp at 312-428-7008 — usually same day.';
	return $lines;
}

/**
 * @param string $key SKU.
 * @return array<int,array{q:string,a:string}>
 */
function bds_svc_faq_for( $key ) {
	$common = array(
		array(
			'q' => 'What do you need from me to start?',
			'a' => 'Your site or profile URL, a short answer on what success looks like, and any access noted on this page. We send a brief intake right after purchase.',
		),
		array(
			'q' => 'How long does it take?',
			'a' => 'We give you a firm timeline in the written scope before work begins — most one-time services land within one to two weeks of receiving what we need.',
		),
		array(
			'q' => 'Can I talk to someone before I buy?',
			'a' => 'Yes. Message us on WhatsApp at 312-428-7008, or run the free site check first and we will point you to the right service.',
		),
		array(
			'q' => 'Do you guarantee Google rankings?',
			'a' => 'No. We improve foundations and report measurable signals. Rankings depend on competition and Google.',
		),
		array(
			'q' => 'Do you post fake reviews?',
			'a' => 'Never. Review growth means helping real customers leave honest feedback.',
		),
	);
	if ( $key === 'fix-my-website' ) {
		$common[] = array(
			'q' => 'How do I share login access?',
			'a' => 'After purchase you get a secure intake. Do not put passwords in the normal checkout notes field.',
		);
	}
	if ( $key === 'business-directory-distribution' ) {
		$common[] = array(
			'q' => 'Is BrandDad Directory the only listing?',
			'a' => 'No. It’s part of a broader distribution plan across relevant directories.',
		);
	}
	if ( $key === 'website-conversion-makeover' ) {
		$common[] = array(
			'q' => 'Is this a full website redesign?',
			'a' => 'No. It’s conversion-focused changes. Full builds live on BrandDad.co.',
		);
	}
	$cat = bds_svc_catalog();
	if ( ! empty( $cat[ $key ]['period'] ) && 'month' === $cat[ $key ]['period'] ) {
		$common[] = array(
			'q' => 'Is this billed monthly?',
			'a' => 'Yes. Checkout charges the listed price each month until you cancel. Setup/one-time siblings (if any) stay a separate one-time purchase.',
		);
	}
	return $common;
}

/**
 * Resolve live product URL for a SKU if Woo product exists on this site.
 *
 * @param string $key SKU key / slug.
 * @return string
 */
function bds_svc_live_url( $key ) {
	$cat = bds_svc_catalog();
	if ( empty( $cat[ $key ] ) ) {
		return '';
	}
	if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
		// Prefer post_name match.
	}
	$posts = get_posts(
		array(
			'name'           => $cat[ $key ]['slug'],
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $posts ) {
		return get_permalink( (int) $posts[0] );
	}
	return (string) $cat[ $key ]['url'];
}

