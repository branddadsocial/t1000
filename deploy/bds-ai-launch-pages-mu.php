<?php
/**
 * Plugin Name: BrandDad AI Launch Pages (Staged)
 * Description: Consolidated, accessible homepage and services-page experience for the approved BrandDad AI launch plans.
 * Version: 0.5.2
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_LAUNCH_PAGES_VERSION', '0.5.2' );

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
		$commercial_offer = function_exists( 'bds_ai_commercial_offer' ) ? bds_ai_commercial_offer( $slug ) : null;
		$commercial_url = bds_ai_commercial_checkout_url( $slug );
		if ( $commercial_url ) {
			return $commercial_url;
		}
		// A cataloged offer that is still staged must remain gated.
		if ( is_array( $commercial_offer ) ) {
			return '';
		}
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
			<a class="bdai-button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $primary ? __( 'Start 7-day free trial', 'branddad' ) : __( 'Choose this plan', 'branddad' ) ); ?></a>
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

/** Explain the review product without promising deletion or platform access. */
function bds_ai_launch_review_support_markup() {
	$case_url = bds_ai_launch_product_url( 'branddad-ai-review-dispute-assistant' );
	$portal_url = esc_url( 'https://ai.branddad.social/d/b2694bae-90ca-40f8-90d3-d8bf18a99fea' );
	ob_start();
	?>
	<section class="bdai-section bdai-review-support" id="branddad-review-support" aria-labelledby="bdai-review-title">
		<p class="bdai-kicker"><?php esc_html_e( 'Google, Airbnb, and Trustpilot', 'branddad' ); ?></p>
		<h2 id="bdai-review-title"><?php esc_html_e( 'A real plan for a review that may break platform rules.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'BrandDad helps you check the policy, organize the facts, and prepare the strongest honest submission the evidence supports. We do not promise removal—the platform makes the final decision.', 'branddad' ); ?></p>
		<div class="bdai-review-grid">
			<article>
				<p class="bdai-review-step"><?php esc_html_e( 'Self-service AI', 'branddad' ); ?></p>
				<h3><?php esc_html_e( 'Prepare the case yourself', 'branddad' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Choose Google, Airbnb, or Trustpilot.', 'branddad' ); ?></li>
					<li><?php esc_html_e( 'Check the review against the platform rules.', 'branddad' ); ?></li>
					<li><?php esc_html_e( 'Build an evidence checklist and dispute draft.', 'branddad' ); ?></li>
					<li><?php esc_html_e( 'Prepare a calm public response when removal is not appropriate.', 'branddad' ); ?></li>
				</ul>
				<a class="bdai-button" href="#branddad-ai-plans"><?php esc_html_e( 'Compare self-service plans', 'branddad' ); ?></a>
			</article>
			<article>
				<p class="bdai-review-step"><?php esc_html_e( 'Done-with-you support', 'branddad' ); ?></p>
				<h3><?php esc_html_e( 'Have BrandDad review and organize it', 'branddad' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'A person checks the AI-prepared case and evidence.', 'branddad' ); ?></li>
					<li><?php esc_html_e( 'We prepare the platform-specific submission and next steps.', 'branddad' ); ?></li>
					<li><?php esc_html_e( 'The authorized owner submits it, or we assist only where the platform permits.', 'branddad' ); ?></li>
					<li><?php esc_html_e( 'No password sharing, impersonation, mass flagging, or removal guarantee.', 'branddad' ); ?></li>
				</ul>
				<?php if ( $case_url ) : ?><a class="bdai-button" href="<?php echo esc_url( $case_url ); ?>"><?php esc_html_e( 'Request case support', 'branddad' ); ?></a><?php endif; ?>
			</article>
		</div>
		<p class="bdai-review-portal"><strong><?php esc_html_e( 'Already a BrandDad customer?', 'branddad' ); ?></strong> <?php esc_html_e( 'Sign in to your private BrandDad AI workspace, open Review Dispute Assistant, and answer the guided case questions. It prepares the letter, short submission, evidence checklist, and next steps for you to review.', 'branddad' ); ?> <a class="bdai-text-link" href="<?php echo $portal_url; ?>"><?php esc_html_e( 'Open my case workspace', 'branddad' ); ?> <span aria-hidden="true">&rarr;</span></a></p>
		<p class="bdai-review-note"><?php esc_html_e( 'BrandDad provides policy-based business support, not legal representation. Do not send passwords, government IDs, medical information, or other sensitive personal data through public chat.', 'branddad' ); ?></p>
	</section>
	<?php
	return (string) ob_get_clean();
}

/** Keep valuable established services discoverable from the AI-first Services page. */
function bds_ai_launch_specialty_services_markup() {
	$services = array(
		array( 'branddad-ai-review-dispute-assistant', 'AI Review Dispute Assistant', 'Policy-based preparation and optional case support for Google, Airbnb, and Trustpilot.' ),
		array( 'branddad-social-media-growth-studio', 'Social Media Growth Studio', 'Content and account support for businesses that want more than self-service drafts.' ),
		array( 'real-influencers-engagements-ig-fb-tiktok-youtube-more', 'Creator & Influencer Campaigns', 'Plan a relevant, disclosed creator campaign around your audience, approved content, and budget.' ),
		array( 'press-release-services', 'Press Release Distribution', 'Turn a real announcement into a professional release and distribution campaign.' ),
		array( 'instagram-boost-services-real-people-sharing-your-post-on-their-story-appear-on-discovery', 'Instagram Story Boost', 'Real-account Story reshares sold with clear package limits and no follower guarantee.' ),
		array( 'imdb-profile-creation-services', 'IMDb Profile Creation', 'Professional submission support for qualified talent and entertainment projects.' ),
		array( 'nyc-times-square-billboard-video-ads', 'Times Square Billboard Video Ads', 'Place an approved short video on a Times Square screen with a defined package.' ),
	);
	$cards = '';
	foreach ( $services as $service ) {
		$url = bds_ai_launch_product_url( $service[0] );
		if ( ! $url ) {
			continue;
		}
		$cards .= '<article><h3>' . esc_html( $service[1] ) . '</h3><p>' . esc_html( $service[2] ) . '</p><a class="bdai-text-link" href="' . esc_url( $url ) . '">' . esc_html__( 'View service', 'branddad' ) . ' <span aria-hidden="true">&rarr;</span></a></article>';
	}
	if ( '' === $cards ) {
		return '';
	}
	return '<section class="bdai-section bdai-specialty" id="branddad-specialty-services" aria-labelledby="bdai-specialty-title"><p class="bdai-kicker">' . esc_html__( 'More ways BrandDad can help', 'branddad' ) . '</p><h2 id="bdai-specialty-title">' . esc_html__( 'Useful specialty services are still here.', 'branddad' ) . '</h2><p>' . esc_html__( 'AI plans are the simple everyday starting point. These established services remain available when you need a specific campaign or hands-on job.', 'branddad' ) . '</p><div class="bdai-specialty-grid">' . $cards . '</div><p class="bdai-review-note">' . esc_html__( 'Every service has its own scope, price, requirements, and delivery method. Results are not guaranteed unless the product terms say otherwise.', 'branddad' ) . '</p></section>';
}

/** Give the Services page three clear doors instead of repeating the homepage journey. */
function bds_ai_launch_service_paths_markup() {
	$creator = esc_url( home_url( '/creator-services/' ) );
	$spotify = esc_url( home_url( '/spotify-promotion/' ) );
	return '<section class="bdai-section bdai-service-paths" id="branddad-service-paths" aria-labelledby="bdai-paths-title">'
		. '<p class="bdai-kicker">' . esc_html__( 'Three simple ways to start', 'branddad' ) . '</p>'
		. '<h2 id="bdai-paths-title">' . esc_html__( 'Choose the kind of help you need.', 'branddad' ) . '</h2>'
		. '<p>' . esc_html__( 'You do not need to understand every tool or service. Start with the closest outcome and BrandDad will guide the next step.', 'branddad' ) . '</p>'
		. '<div class="bdai-path-grid">'
		. '<article><span>01</span><h3>' . esc_html__( 'Use BrandDad AI', 'branddad' ) . '</h3><p>' . esc_html__( 'Write content, answer reviews, improve your marketing, and organize everyday business work yourself.', 'branddad' ) . '</p><a class="bdai-text-link" href="#branddad-ai-solutions">' . esc_html__( 'Compare AI help', 'branddad' ) . ' <span aria-hidden="true">&rarr;</span></a></article>'
		. '<article><span>02</span><h3>' . esc_html__( 'Hire BrandDad', 'branddad' ) . '</h3><p>' . esc_html__( 'Choose a defined service when you want BrandDad to prepare, coordinate, or deliver the work with you.', 'branddad' ) . '</p><a class="bdai-text-link" href="#branddad-specialty-services">' . esc_html__( 'Browse done-for-you services', 'branddad' ) . ' <span aria-hidden="true">&rarr;</span></a></article>'
		. '<article><span>03</span><h3>' . esc_html__( 'Launch a campaign', 'branddad' ) . '</h3><p>' . esc_html__( 'Plan creator content, distribution, music promotion, or a larger connected campaign with a clear scope.', 'branddad' ) . '</p><a class="bdai-text-link" href="' . $creator . '">' . esc_html__( 'Explore creator campaigns', 'branddad' ) . ' <span aria-hidden="true">&rarr;</span></a><a class="bdai-text-link" href="' . $spotify . '">' . esc_html__( 'Explore Spotify promotion', 'branddad' ) . ' <span aria-hidden="true">&rarr;</span></a></article>'
		. '</div></section>';
}

/** Price label that does not call a one-time music commission a monthly plan. */
function bds_ai_launch_price_label( $offer ) {
	$amount = isset( $offer['monthly'] ) ? (string) $offer['monthly'] : '';
	if ( isset( $offer['billing'] ) && 'project' === $offer['billing'] ) {
		return array( '$' . $amount, __( 'project', 'branddad' ) );
	}
	if ( 0 === strpos( (string) $offer['name'], 'Done-for-You' ) || 0 === strpos( (string) $offer['name'], 'BrandDad Music — Monthly' ) ) {
		return array( __( 'from $', 'branddad' ) . $amount, __( '/month', 'branddad' ) );
	}
	return array( '$' . $amount, __( '/month', 'branddad' ) );
}

/** Checkout only when the catalog already allows it. Everything else is an inquiry. */
function bds_ai_launch_offer_action( $slug, $offer ) {
	$url = bds_ai_launch_product_url( $slug );
	if ( $url ) {
		return array( $url, __( 'Choose this plan', 'branddad' ), true );
	}
		$contact = add_query_arg(
		array(
			'subject' => 'BrandDad inquiry: ' . $offer['name'],
		),
		home_url( '/contact-us/' )
	);
	$label = ( isset( $offer['launch_state'] ) && 'quote_only' === $offer['launch_state'] )
		? __( 'Request a scoped quote', 'branddad' )
		: __( 'Ask before checkout', 'branddad' );
	return array( $contact, $label, false );
}

/** Render catalog cards for one category. Skips records the catalog does not contain. */
function bds_ai_launch_catalog_cards( $category ) {
	if ( ! function_exists( 'bds_ai_commercial_catalog' ) ) {
		return '';
	}
	$html = '';
	foreach ( bds_ai_commercial_catalog() as $slug => $offer ) {
		if ( ! is_array( $offer ) || $category !== ( isset( $offer['category'] ) ? $offer['category'] : '' ) ) {
			continue;
		}
		list( $amount, $period ) = bds_ai_launch_price_label( $offer );
		list( $href, $label, $ready ) = bds_ai_launch_offer_action( $slug, $offer );
		$note = $ready
			? __( 'Checkout is available for this published product.', 'branddad' )
			: ( isset( $offer['launch_blocker'] ) ? $offer['launch_blocker'] : __( 'This offer is not open for self-serve checkout.', 'branddad' ) );
		$html .= '<article class="bdai-card" id="' . esc_attr( $slug ) . '">';
		$html .= '<h3>' . esc_html( $offer['name'] ) . '</h3>';
		$html .= '<p class="bdai-price"><strong>' . esc_html( $amount ) . '</strong><span>' . esc_html( $period ) . '</span></p>';
		$html .= '<p>' . esc_html( $offer['summary'] ) . '</p>';
		if ( ! empty( $offer['target_customer'] ) ) {
			$html .= '<p class="bdai-example">' . esc_html( $offer['target_customer'] ) . '</p>';
		}
		$html .= '<ul>';
		foreach ( (array) $offer['limits'] as $limit ) {
			$html .= '<li>' . esc_html( $limit ) . '</li>';
		}
		$html .= '</ul>';
		$html .= '<p class="bdai-readiness">' . esc_html( $note ) . '</p>';
		$html .= '<a class="bdai-button' . ( $ready ? '' : ' bdai-button--ghost' ) . '" href="' . esc_url( $href ) . '">' . esc_html( $label ) . '</a>';
		$html .= '</article>';
	}
	return $html;
}

/** Homepage sections that were missing from the consolidated launch page. */
function bds_ai_launch_revenue_sections() {
	$audit     = esc_url( home_url( '/check-your-website/' ) );
	$contact   = esc_url( home_url( '/contact-us/' ) );
	$directory = esc_url( 'https://directory.branddad.social/add-listing/' );
	$browse    = esc_url( 'https://directory.branddad.social/' );
	$featured  = esc_url( 'https://directory.branddad.social/featured-advertising/' );
	$packages  = bds_ai_launch_catalog_cards( 'complete_package' );
	$music     = bds_ai_launch_catalog_cards( 'music' );
	$seo_url      = bds_ai_launch_product_url( 'ai-local-seo' );
	$social_url   = bds_ai_launch_product_url( 'ai-social-growth' );
	$linkedin_url = bds_ai_launch_product_url( 'ai-linkedin-growth' );
	$seo_href     = esc_url( $seo_url ? $seo_url : home_url( '/services/#ai-local-seo' ) );
	$social_href  = esc_url( $social_url ? $social_url : home_url( '/services/#ai-social-growth' ) );
	$li_href      = esc_url( $linkedin_url ? $linkedin_url : home_url( '/services/#ai-linkedin-growth' ) );
	ob_start();
	?>
	<section class="bdai-section" id="branddad-free-audit" aria-labelledby="bdai-audit-title">
		<p class="bdai-kicker"><?php esc_html_e( 'Free starting point', 'branddad' ); ?></p>
		<h2 id="bdai-audit-title"><?php esc_html_e( 'Start with a free AI business audit.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'Check the public website first. The audit explains what to fix. It does not invent rankings, traffic, or revenue.', 'branddad' ); ?></p>
		<div class="bdai-actions"><a class="bdai-button" href="<?php echo $audit; ?>"><?php esc_html_e( 'Run the free website check', 'branddad' ); ?></a></div>
	</section>
	<?php if ( $packages ) : ?>
	<section class="bdai-section" id="branddad-complete-packages" aria-labelledby="bdai-packages-title">
		<p class="bdai-kicker"><?php esc_html_e( 'Bundled monthly help', 'branddad' ); ?></p>
		<h2 id="bdai-packages-title"><?php esc_html_e( 'Complete AI business packages.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'These bundles show the proposed workload and price. Self-serve checkout stays closed until shared entitlements and fulfillment are verified. Request a quote if you want BrandDad to scope one now.', 'branddad' ); ?></p>
		<div class="bdai-grid bdai-grid--wrap"><?php echo $packages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</section>
	<?php endif; ?>
	<section class="bdai-section" id="branddad-studios" aria-labelledby="bdai-studios-title">
		<p class="bdai-kicker"><?php esc_html_e( 'Specialized studios', 'branddad' ); ?></p>
		<h2 id="bdai-studios-title"><?php esc_html_e( 'SEO, social, LinkedIn, and website chat — priced separately.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'Each studio is sold only when its delivery path is verified. Drafts stay in your review until you approve them.', 'branddad' ); ?></p>
		<div class="bdai-use-grid">
			<article><h3><?php esc_html_e( 'AI SEO Autopilot', 'branddad' ); ?></h3><p><?php esc_html_e( 'Local SEO Starter is the live-candidate check at $19.99. Autopilot at $39.99, Growth at $59.99, and Pro at $99.99 are staged until execution is verified.', 'branddad' ); ?></p><a class="bdai-text-link" href="<?php echo $seo_href; ?>"><?php echo esc_html( $seo_url ? __( 'Subscribe to Local SEO', 'branddad' ) : __( 'See SEO options', 'branddad' ) ); ?></a></article>
			<article><h3><?php esc_html_e( 'AI Social Studio', 'branddad' ); ?></h3><p><?php esc_html_e( 'Social Starter is $14.99 for 12 drafts. Social Growth is $29.99 for 30 drafts. Publishing waits for your approval.', 'branddad' ); ?></p><a class="bdai-text-link" href="<?php echo $social_href; ?>"><?php echo esc_html( $social_url ? __( 'Subscribe to Social Growth', 'branddad' ) : __( 'See social plans', 'branddad' ) ); ?></a></article>
			<article><h3><?php esc_html_e( 'LinkedIn Studio', 'branddad' ); ?></h3><p><?php esc_html_e( 'AI LinkedIn Growth is $29.99 for profile and post drafts. Automated comments run only inside authorized platform limits.', 'branddad' ); ?></p><a class="bdai-text-link" href="<?php echo $li_href; ?>"><?php echo esc_html( $linkedin_url ? __( 'Subscribe to LinkedIn Growth', 'branddad' ) : __( 'See LinkedIn Growth', 'branddad' ) ); ?></a></article>
			<article><h3><?php esc_html_e( 'Website chatbot', 'branddad' ); ?></h3><p><?php esc_html_e( 'The $29.99 chatbot is not open for checkout. Current assistant cost is too high for that public allowance.', 'branddad' ); ?></p><a class="bdai-text-link" href="<?php echo $contact; ?>"><?php esc_html_e( 'Ask about a scoped assistant', 'branddad' ); ?></a></article>
		</div>
	</section>
	<section class="bdai-section" id="branddad-directory-featured" aria-labelledby="bdai-featured-title">
		<p class="bdai-kicker"><?php esc_html_e( 'BrandDad Directory', 'branddad' ); ?></p>
		<h2 id="bdai-featured-title"><?php esc_html_e( 'Free listing. Paid placement when inventory is open.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'Browse and create a basic listing without paying. Featured placement is sponsored and rotates. These monthly prices are the placement targets. This page does not charge for a placement or promise a slot.', 'branddad' ); ?></p>
		<div class="bdai-grid bdai-grid--four">
			<article class="bdai-card"><h3><?php esc_html_e( 'Local Featured', 'branddad' ); ?></h3><p class="bdai-price"><strong>$9.99</strong><span>/month</span></p><p><?php esc_html_e( 'Nearby locations, about 25 km, adjusted for islands, borders, and the business service area.', 'branddad' ); ?></p></article>
			<article class="bdai-card"><h3><?php esc_html_e( 'Regional Featured', 'branddad' ); ?></h3><p class="bdai-price"><strong>$19.99</strong><span>/month</span></p><p><?php esc_html_e( 'Neighboring communities, about 100 km, using the same service-area rules.', 'branddad' ); ?></p></article>
			<article class="bdai-card"><h3><?php esc_html_e( 'National Featured', 'branddad' ); ?></h3><p class="bdai-price"><strong>$39.99</strong><span>/month</span></p><p><?php esc_html_e( 'One country, on relevant category and location pages.', 'branddad' ); ?></p></article>
			<article class="bdai-card"><h3><?php esc_html_e( 'Global Homepage Featured', 'branddad' ); ?></h3><p class="bdai-price"><strong>$99.99</strong><span>/month</span></p><p><?php esc_html_e( 'Premium worldwide placement near Directory search.', 'branddad' ); ?></p></article>
		</div>
		<div class="bdai-actions"><a class="bdai-button" href="<?php echo $directory; ?>"><?php esc_html_e( 'Create a free Directory listing', 'branddad' ); ?></a><a class="bdai-button" href="<?php echo $featured; ?>"><?php esc_html_e( 'Get featured', 'branddad' ); ?></a><a class="bdai-button bdai-button--light" href="<?php echo $browse; ?>"><?php esc_html_e( 'Browse the Directory', 'branddad' ); ?></a></div>
	</section>
	<?php if ( $music ) : ?>
	<section class="bdai-section" id="branddad-music" aria-labelledby="bdai-music-title">
		<p class="bdai-kicker"><?php esc_html_e( 'BrandDad Music for Business', 'branddad' ); ?></p>
		<h2 id="bdai-music-title"><?php esc_html_e( 'Commission original music. Do not generate a fake track.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'Artist: T Dash. Songwriter: Thaddeus McCollum. Production: BrandDad Music. Sales: BrandDad Social. The path is package, creative brief, payment, production, preview, revision, then delivery. Public checkout stays closed until licensing and capacity are confirmed.', 'branddad' ); ?></p>
		<div class="bdai-grid bdai-grid--wrap"><?php echo $music; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</section>
	<?php endif; ?>
	<section class="bdai-section" id="branddad-proof" aria-labelledby="bdai-proof-title">
		<p class="bdai-kicker"><?php esc_html_e( 'Proof', 'branddad' ); ?></p>
		<h2 id="bdai-proof-title"><?php esc_html_e( 'Only claims BrandDad can stand behind.', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'This page does not show star ratings, follower counts, or customer quotes unless they are on file. Ask BrandDad for a reference before you buy a managed service. No plan guarantees sales, rankings, followers, or review removal.', 'branddad' ); ?></p>
	</section>
	<?php
	return (string) ob_get_clean();
}

/** Services page lists the approved catalog without opening checkout early. */
function bds_ai_launch_services_catalog_markup() {
	$groups = array(
		'specialized'      => __( 'Individual monthly AI services', 'branddad' ),
		'complete_package' => __( 'Complete business packages', 'branddad' ),
		'music'            => __( 'BrandDad Music commissions', 'branddad' ),
		'installed_legacy' => __( 'Existing installed offers', 'branddad' ),
	);
	$html = '';
	foreach ( $groups as $category => $title ) {
		$cards = bds_ai_launch_catalog_cards( $category );
		if ( '' === $cards ) {
			continue;
		}
		$html .= '<section class="bdai-section" id="catalog-' . esc_attr( $category ) . '"><h2>' . esc_html( $title ) . '</h2><div class="bdai-grid bdai-grid--wrap">' . $cards . '</div></section>';
	}
	return $html;
}

/** Shared launch markup for previews and the final public replacement. */
function bds_ai_launch_markup( $context = 'home' ) {
	$is_services = 'services' === $context;
	$services = esc_url( home_url( '/services/' ) );
	$contact  = esc_url( home_url( '/contact-us/' ) );
	$audit    = esc_url( home_url( '/check-your-website/' ) );
	$directory = esc_url( 'https://directory.branddad.social/add-listing/' );
	$essentials_url  = bds_ai_launch_product_url( 'ai-business-essentials' );
	$essentials_href = $essentials_url ? esc_url( $essentials_url ) : '';
	ob_start();
	?>
	<div class="bdai-launch" data-version="<?php echo esc_attr( BDS_AI_LAUNCH_PAGES_VERSION ); ?>">
		<section class="bdai-hero" aria-labelledby="bdai-title">
			<p class="bdai-kicker"><?php echo esc_html( $is_services ? __( 'AI tools · done-for-you services · creator campaigns', 'branddad' ) : __( 'Big-business tools. Small-business prices.', 'branddad' ) ); ?></p>
			<h1 id="bdai-title"><?php echo esc_html( $is_services ? __( 'Choose the help your business needs—without agency confusion.', 'branddad' ) : __( 'Your AI-powered business team—without agency prices.', 'branddad' ) ); ?></h1>
			<p><?php echo esc_html( $is_services ? __( 'Use affordable AI yourself, hire BrandDad for a specific job, or build a larger campaign. Every option explains what you receive before you pay.', 'branddad' ) : __( 'Turn rough ideas into social posts, review replies, marketing plans, and clearer business writing. Tell BrandDad AI what you need, review the result, and move your business forward.', 'branddad' ) ); ?></p>
			<p class="bdai-starting"><?php echo esc_html( $is_services ? __( 'Start small, approve the scope, and add more only when it helps.', 'branddad' ) : __( 'Simple monthly plans starting at $14.99.', 'branddad' ) ); ?></p>
			<div class="bdai-actions"><?php if ( $is_services ) : ?><a class="bdai-button" href="#branddad-service-paths"><?php esc_html_e( 'Compare service types', 'branddad' ); ?></a><a class="bdai-button bdai-button--light" href="<?php echo $contact; ?>"><?php esc_html_e( 'Ask BrandDad', 'branddad' ); ?></a><?php else : ?><a class="bdai-button" href="<?php echo $audit; ?>"><?php esc_html_e( 'Start free AI business audit', 'branddad' ); ?></a><?php if ( $essentials_href ) : ?><a class="bdai-button" href="<?php echo $essentials_href; ?>"><?php esc_html_e( 'Subscribe to Business Essentials', 'branddad' ); ?></a><?php endif; ?><a class="bdai-button bdai-button--light" href="#branddad-ai-solutions"><?php esc_html_e( 'Explore AI plans', 'branddad' ); ?></a><?php endif; ?></div>
			<ul class="bdai-trust"><li><?php echo esc_html( $is_services ? __( 'Clear scope before work begins', 'branddad' ) : __( 'No technical skills needed', 'branddad' ) ); ?></li><li><?php echo esc_html( $is_services ? __( 'You approve important actions', 'branddad' ) : __( 'You stay in control', 'branddad' ) ); ?></li><li><?php echo esc_html( $is_services ? __( 'No hidden promises', 'branddad' ) : __( 'Clear monthly limits', 'branddad' ) ); ?></li></ul>
		</section>
		<?php if ( $is_services ) { echo bds_ai_launch_service_paths_markup(); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( ! $is_services ) : ?>
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
		<?php endif; ?>
		<?php echo bds_ai_launch_review_support_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
		<?php if ( ! $is_services ) { echo bds_ai_launch_revenue_sections(); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
		<section class="bdai-final" aria-labelledby="bdai-final-title"><p class="bdai-kicker"><?php esc_html_e( 'Start small. Get more done.', 'branddad' ); ?></p><h2 id="bdai-final-title"><?php esc_html_e( 'Give your business the help it keeps putting off.', 'branddad' ); ?></h2><p><?php esc_html_e( 'Choose an affordable BrandDad AI plan or ask us which option fits your business.', 'branddad' ); ?></p><div class="bdai-actions"><?php if ( $essentials_href ) : ?><a class="bdai-button" href="<?php echo $essentials_href; ?>"><?php esc_html_e( 'Subscribe to Business Essentials', 'branddad' ); ?></a><?php endif; ?><a class="bdai-button<?php echo $essentials_href ? ' bdai-button--light' : ''; ?>" href="#branddad-ai-plans"><?php esc_html_e( 'Compare plans', 'branddad' ); ?></a><?php if ( ! $essentials_href ) : ?><a class="bdai-button bdai-button--light" href="<?php echo $contact; ?>"><?php esc_html_e( 'Ask BrandDad', 'branddad' ); ?></a><?php endif; ?></div></section>
		<?php if ( ! $is_services ) : ?><section class="bdai-directory-cta" aria-labelledby="bdai-directory-title"><div><p class="bdai-kicker"><?php esc_html_e( 'Already running a business?', 'branddad' ); ?></p><h2 id="bdai-directory-title"><?php esc_html_e( 'Claim your free business listing and unlock limited free AI tools.', 'branddad' ); ?></h2><p><?php esc_html_e( 'A verified BrandDad Directory business will receive a small, ongoing AI allowance after the free-access cost and identity checks are complete. Paid enrollment is never automatic.', 'branddad' ); ?></p></div><a class="bdai-button" href="<?php echo $directory; ?>"><?php esc_html_e( 'Claim your free business listing', 'branddad' ); ?></a></section><?php endif; ?>
		<?php if ( 'services' === $context ) { echo bds_ai_launch_services_catalog_markup(); echo bds_ai_launch_specialty_services_markup(); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
	.bdai-review-portal{margin-top:22px;padding:18px;border-radius:12px;background:var(--soft);color:var(--muted);line-height:1.6}.bdai-review-portal .bdai-text-link{margin:0 0 0 6px}
	.bdai-path-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:28px}.bdai-path-grid article{display:flex;flex-direction:column;padding:28px;border:1px solid var(--line);border-radius:18px;background:#fff}.bdai-path-grid article>span{display:inline-flex;width:40px;height:40px;align-items:center;justify-content:center;border-radius:50%;background:#071a33;color:#fff;font-weight:900}.bdai-path-grid h3{margin:18px 0 8px;font-size:24px}.bdai-path-grid p{color:var(--muted);line-height:1.6}.bdai-path-grid .bdai-text-link{margin-top:auto}@media(max-width:800px){.bdai-path-grid{grid-template-columns:1fr}}@media(max-width:520px){.bdai-path-grid article{padding:22px}}
	.bdai-launch{--ink:#0b172a;--muted:#526070;--blue:#0878ed;--line:#dbe5ef;--soft:#f4f8fc;max-width:1180px;margin:0 auto;padding:20px;color:var(--ink);font-family:Arial,sans-serif}.bdai-launch *{box-sizing:border-box}.bdai-hero,.bdai-final{padding:clamp(42px,7vw,86px);border-radius:28px;background:#071a33;color:#fff}.bdai-hero h1{max-width:900px;margin:8px 0 16px;font-size:clamp(38px,6vw,72px);line-height:1.02;letter-spacing:-.045em;color:#fff}.bdai-hero>p:not(.bdai-kicker),.bdai-final>p:not(.bdai-kicker){max-width:760px;font-size:clamp(17px,2vw,21px);line-height:1.55;color:#e6eef7}.bdai-hero .bdai-starting{font-weight:800;color:#fff!important}.bdai-kicker{margin:0;color:#0878ed;font-size:13px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.bdai-hero .bdai-kicker,.bdai-final .bdai-kicker{color:#9ed0ff}.bdai-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}.bdai-button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 20px;border:2px solid var(--blue);border-radius:10px;background:var(--blue);color:#fff!important;font-weight:800;text-decoration:none}.bdai-button:hover,.bdai-button:focus{filter:brightness(.93);transform:translateY(-1px)}.bdai-button--light{background:#fff;color:var(--ink)!important;border-color:#fff}.bdai-button--ghost{background:#fff;color:var(--ink)!important;border-color:var(--blue)}.bdai-grid--wrap,.bdai-grid--four{grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}.bdai-card .bdai-button{margin-top:16px}.bdai-trust{display:flex;flex-wrap:wrap;gap:12px 28px;margin:28px 0 0;padding:20px 0 0;border-top:1px solid rgba(255,255,255,.22);list-style:none}.bdai-trust li{position:relative;padding-left:22px;color:#fff;font-weight:700}.bdai-trust li:before{position:absolute;left:0;content:'✓';color:#9ed0ff}.bdai-section{padding:clamp(38px,6vw,72px) 0}.bdai-section h2,.bdai-final h2,.bdai-directory-cta h2{max-width:820px;margin:8px 0 12px;font-size:clamp(29px,4vw,46px);line-height:1.1;letter-spacing:-.035em;color:var(--ink)}.bdai-final h2{color:#fff}.bdai-section>p:not(.bdai-kicker){max-width:800px;color:var(--muted);font-size:17px;line-height:1.65}.bdai-use-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:30px}.bdai-use-grid article{padding:24px;border:1px solid var(--line);border-radius:16px;background:#fff}.bdai-use-grid span{display:inline-flex;width:38px;height:38px;align-items:center;justify-content:center;border-radius:50%;background:#071a33;color:#fff;font-weight:800}.bdai-use-grid h3{margin:18px 0 8px;font-size:21px}.bdai-use-grid p{margin:0;color:var(--muted);line-height:1.55}.bdai-review-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:28px}.bdai-review-grid article,.bdai-specialty-grid article{display:flex;flex-direction:column;padding:26px;border:1px solid var(--line);border-radius:18px;background:#fff}.bdai-review-grid h3,.bdai-specialty-grid h3{margin:4px 0 10px;color:var(--ink);font-size:23px}.bdai-review-grid ul{padding-left:20px}.bdai-review-grid li,.bdai-specialty-grid p{color:var(--muted);line-height:1.55}.bdai-review-grid .bdai-button{align-self:flex-start;margin-top:auto}.bdai-review-step{margin:0;color:#005ebd!important;font-size:13px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.bdai-review-note{margin-top:18px!important;font-size:14px!important}.bdai-specialty-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:28px}.bdai-specialty-grid .bdai-text-link{margin-top:auto}.bdai-featured-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:20px;align-items:start;margin-top:30px}.bdai-specialists{display:grid;gap:14px}.bdai-solution{display:flex;flex-direction:column;padding:24px;border:1px solid var(--line);border-radius:18px;background:#fff}.bdai-solution--primary{padding:30px;border:2px solid var(--blue);box-shadow:0 18px 42px rgba(8,120,237,.14)}.bdai-solution .bdai-badge{align-self:flex-start;margin:0 0 16px}.bdai-solution h3{margin:0;font-size:25px}.bdai-solution p,.bdai-solution li{color:var(--muted);line-height:1.55}.bdai-solution ul{padding-left:20px}.bdai-solution .bdai-button,.bdai-solution .bdai-soon{margin-top:auto}.bdai-readiness{font-size:13px}.bdai-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:28px}.bdai-card{position:relative;display:flex;flex-direction:column;border:1px solid var(--line);border-radius:18px;padding:26px;background:#fff;box-shadow:0 12px 34px rgba(8,35,65,.08)}.bdai-card--popular{border:2px solid var(--blue);box-shadow:0 18px 42px rgba(8,120,237,.15)}.bdai-badge{align-self:flex-start;margin:-42px 0 16px;padding:7px 12px;border-radius:99px;background:#071a33;color:#fff!important;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.bdai-card h3{margin:0;font-size:22px;color:var(--ink)}.bdai-card p,.bdai-card li{color:var(--muted);line-height:1.55}.bdai-card ul{padding-left:20px;min-height:160px}.bdai-card .bdai-button{margin-top:auto}.bdai-price{display:flex;align-items:baseline;gap:5px}.bdai-price strong{font-size:38px;color:var(--ink)}.bdai-example{padding:14px;border-radius:10px;background:var(--soft);font-size:14px}.bdai-soon{margin-top:auto;padding:13px;border-radius:9px;background:var(--soft);color:var(--muted);font-weight:700;text-align:center}.bdai-credit-note{margin:20px 0 0!important;font-size:14px!important}.bdai-process{padding-left:clamp(22px,5vw,60px);padding-right:clamp(22px,5vw,60px);border-radius:24px;background:var(--soft)}.bdai-process ol{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;margin:30px 0 0;padding:0;list-style:none;counter-reset:steps}.bdai-process li{counter-increment:steps}.bdai-process li:before{display:block;margin-bottom:14px;content:counter(steps);color:var(--blue);font-size:34px;font-weight:900}.bdai-process strong,.bdai-process span{display:block}.bdai-process span{margin-top:8px;color:var(--muted);line-height:1.5}.bdai-split{display:grid;grid-template-columns:1fr 1fr;gap:22px}.bdai-split>div{padding:30px;border-radius:18px;background:var(--soft)}.bdai-split h2{font-size:clamp(26px,3vw,38px)}.bdai-text-link{display:inline-block;margin-top:12px;color:#005ebd;font-weight:800}.bdai-faq details{max-width:860px;border-top:1px solid var(--line);padding:18px 0}.bdai-faq summary{cursor:pointer;font-weight:800}.bdai-faq details p{color:var(--muted);line-height:1.6}.bdai-final{margin:10px 0 32px}.bdai-final p{max-width:700px}.bdai-directory-cta{display:grid;grid-template-columns:1fr auto;gap:28px;align-items:center;margin:20px 0 40px;padding:30px;border-radius:20px;background:var(--soft)}.bdai-directory-cta h2{font-size:clamp(26px,3vw,38px)}.bdai-directory-cta p{color:var(--muted);line-height:1.55}.bdai-center{text-align:center}@media(max-width:900px){.bdai-use-grid,.bdai-process ol,.bdai-specialty-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.bdai-featured-grid,.bdai-grid,.bdai-split,.bdai-directory-cta,.bdai-review-grid{grid-template-columns:1fr}.bdai-card{margin-top:12px}.bdai-card ul{min-height:0}.bdai-directory-cta .bdai-button{width:100%}}@media(max-width:520px){.bdai-launch{padding:10px}.bdai-hero,.bdai-final{padding:34px 22px;border-radius:20px}.bdai-actions,.bdai-actions a{width:100%}.bdai-trust{display:block}.bdai-trust li+li{margin-top:10px}.bdai-use-grid,.bdai-process ol,.bdai-specialty-grid{grid-template-columns:1fr}.bdai-card,.bdai-solution,.bdai-review-grid article,.bdai-specialty-grid article{padding:22px}.bdai-process{padding:32px 22px}}
	</style>
	<?php
}
add_action( 'wp_head', 'bds_ai_launch_styles', 30 );

