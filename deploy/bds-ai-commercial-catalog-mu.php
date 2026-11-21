<?php
/**
 * Plugin Name: BrandDad AI Commercial Catalog (Staged)
 * Description: One fail-closed source of truth for BrandDad AI pricing, limits, delivery readiness, and checkout eligibility.
 * Version: 0.4.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_COMMERCIAL_CATALOG_VERSION', '0.4.0' );

/**
 * Authoritative BrandDad AI commercial catalog.
 *
 * `launch_state` is deliberately fail-closed:
 * - launch_candidate: fulfillment exists, but the final billing lifecycle must pass.
 * - staged: price and scope are approved for testing, not public checkout.
 * - quote_only: requires a reviewed scope before sale.
 * - live_legacy: preserves an existing public offer and customer agreement.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_ai_commercial_catalog() {
	return array(
		'branddad-ai-studio-starter' => array(
			'name' => 'BrandDad AI Studio — Starter', 'monthly' => '14.99', 'customer_credits' => 50,
			'category' => 'ai_studio', 'launch_state' => 'launch_candidate', 'trial_days' => 0,
			'formwise_plan_id' => '6aeeb1bb-f28a-4577-a874-6d7b514fecb4',
			'summary' => 'Self-service BrandDad AI tools for occasional business and marketing work.',
			'limits' => array( 'One customer workspace', '50 monthly customer credits', 'Human review before use' ),
		),
		'branddad-ai-studio-growth' => array(
			'name' => 'BrandDad AI Studio — Growth', 'monthly' => '29.99', 'customer_credits' => 150,
			'category' => 'ai_studio', 'launch_state' => 'launch_candidate', 'trial_days' => 0,
			'formwise_plan_id' => '333b8f03-fa63-4afe-bb3f-dc530c4d4dc6',
			'summary' => 'More monthly capacity for businesses using BrandDad AI every week.',
			'limits' => array( 'One customer workspace', '150 monthly customer credits', 'Human review before use' ),
		),
		'branddad-ai-studio-pro' => array(
			'name' => 'BrandDad AI Studio — Pro', 'monthly' => '49.99', 'customer_credits' => 400,
			'category' => 'ai_studio', 'launch_state' => 'launch_candidate', 'trial_days' => 0,
			'formwise_plan_id' => 'd3bd5e69-63b6-4eb3-80fc-f1cce851e4db',
			'summary' => 'The largest standard allowance for frequent AI-assisted business work.',
			'limits' => array( 'One customer workspace', '400 monthly customer credits', 'Human review before use' ),
		),
		'ai-business-monitoring' => array(
			'name' => 'AI Business Monitoring', 'monthly' => '9.99', 'customer_credits' => 0,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'Simple website availability and important-change monitoring for one business website.',
			'limits' => array( 'One public website', 'Daily availability check', 'One monthly summary', 'Email alerts for confirmed failures' ),
		),
		'ai-review-assistant' => array(
			'name' => 'AI Review Assistant', 'monthly' => '9.99', 'customer_credits' => 60,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'Professional review-response drafts that the business reviews before posting.',
			'limits' => array( 'Up to 15 response drafts each month', 'Manual approval required', 'No automatic posting' ),
		),
		'branddad-ai-review-dispute-assistant' => array(
			'name' => 'Airbnb Review Appeal Assistant — One Review', 'price' => '9.99', 'customer_credits' => 0,
			'category' => 'one_time_service', 'launch_state' => 'staged', 'trial_days' => 0, 'billing' => 'one_time',
			'summary' => 'Answer guided questions about one Airbnb review and receive an honest, policy-based message to review and copy into Airbnb.',
			'limits' => array( 'One Airbnb review case', 'One guided AI case package', 'Evidence checklist', 'Long and short copy-and-paste drafts', 'Public-response fallback', 'No removal guarantee' ),
			'launch_blocker' => 'Public checkout opens after the one-case payment, entitlement, execution, refund, and result-page acceptance test passes.',
		),
		'ai-social-starter' => array(
			'name' => 'AI Social Starter', 'monthly' => '14.99', 'customer_credits' => 60,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'A small monthly set of editable social content drafts for one business.',
			'limits' => array( '12 social post drafts each month', 'One brand profile', 'Approval before publishing', 'No premium video generation' ),
		),
		'ai-local-seo' => array(
			'name' => 'AI Local SEO', 'monthly' => '19.99', 'customer_credits' => 60,
			'category' => 'specialized', 'launch_state' => 'launch_candidate', 'trial_days' => 0,
			'formwise_plan_id' => '03035ef5-d1bf-4bc8-ba5d-af7a1d1fe6d5',
			'summary' => 'A monthly local-search health check with clear improvement suggestions.',
			'limits' => array( 'One website and one primary location', 'One monthly local SEO report', 'Recommendations only', 'No ranking guarantee' ),
		),
		'ai-lead-assistant' => array(
			'name' => 'AI Lead Assistant', 'monthly' => '19.99', 'customer_credits' => 80,
			'category' => 'specialized', 'launch_state' => 'launch_candidate', 'trial_days' => 0,
			'formwise_plan_id' => '7bdea82b-30a7-44de-ab37-eb90c68d05aa',
			'summary' => 'Lead capture, email notifications, and editable inquiry summaries for one business.',
			'limits' => array( 'One lead form', 'Up to 50 captured leads each month', 'Up to 20 AI summaries', 'No paid messaging included' ),
		),
		'ai-social-growth' => array(
			'name' => 'AI Social Growth', 'monthly' => '29.99', 'customer_credits' => 150,
			'category' => 'specialized', 'launch_state' => 'launch_candidate', 'trial_days' => 0,
			'formwise_plan_id' => '8d1f997d-91a2-409c-92e0-ed5c1f193dac',
			'summary' => 'A larger monthly content plan for businesses that post consistently.',
			'limits' => array( '30 social post drafts each month', 'Monthly content calendar', 'One brand profile', 'Approval before publishing' ),
		),
		'ai-linkedin-growth' => array(
			'name' => 'AI LinkedIn Growth', 'monthly' => '29.99', 'customer_credits' => 150,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'LinkedIn profile and content support for one professional or business page.',
			'limits' => array( 'One profile analysis each month', '12 post drafts each month', 'One monthly content plan', 'No engagement guarantee' ),
		),
		'ai-website-chatbot' => array(
			'name' => 'AI Website Chatbot', 'monthly' => '29.99', 'customer_credits' => 0,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'A branded website assistant grounded in approved business information.',
			'limits' => array( 'One website', 'Up to 50 approved knowledge items', 'Visitor-reply allowance pending cost optimization', 'No automatic outbound messaging' ),
			'launch_blocker' => 'Observed assistant runs are too expensive for a sustainable public allowance at this price.',
		),
		'ai-business-essentials' => array(
			'name' => 'AI Business Essentials', 'monthly' => '29.99', 'customer_credits' => 75,
			'category' => 'flagship', 'launch_state' => 'launch_candidate', 'trial_days' => 7, 'featured' => true,
			'formwise_plan_id' => 'f8eb4556-49af-484a-8a44-66da41e265f6',
			'summary' => 'Affordable AI help for business health, social content, local SEO, and professional review-response drafts.',
			'limits' => array( 'One business profile', 'One business health summary each month', 'Up to 10 social media post drafts', 'One local SEO report', 'Up to 5 professional review-response drafts', '75 BrandDad AI credits each month' ),
			'trial_target_days' => 7,
		),
		'ai-sales-assistant-plus-new-plan' => array(
			'name' => 'AI Sales Assistant Plus', 'monthly' => '39.99', 'customer_credits' => 0,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'A branded assistant that answers approved questions and helps collect qualified inquiries.',
			'limits' => array( 'One website', 'Up to 75 approved knowledge items', 'Reply allowance pending cost optimization', 'Human-reviewed setup', 'No automatic sales promises' ),
			'launch_blocker' => 'Reply cost and the automated knowledge-update process require further testing.',
		),
		'ai-business-essentials-pro' => array(
			'name' => 'AI Business Essentials Pro', 'monthly' => '49.99', 'customer_credits' => 175,
			'category' => 'flagship', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'Higher allowances and follow-up support for an active small business.',
			'limits' => array( 'One website and business profile', 'Expanded monitoring', 'Two monthly SEO checks', '24 social post drafts', '25 review-response drafts', 'Lead summaries and approved follow-up drafts', 'One monthly activity report' ),
		),
		'ai-business-automation' => array(
			'name' => 'AI Business Automation', 'monthly' => '49.99', 'customer_credits' => 0,
			'category' => 'custom', 'launch_state' => 'quote_only', 'trial_days' => 0,
			'summary' => 'A scoped automation service priced after the workflow, integrations, and usage are reviewed.',
			'limits' => array( 'Starts at $49.99 per month', 'Setup quoted separately', 'Written scope and usage limits required' ),
		),
		'branddad-ai-sales-basic' => array(
			'name' => 'BrandDad AI Sales Assistant — Basic', 'monthly' => '39.00', 'setup' => '99.00', 'customer_credits' => 0,
			'category' => 'installed_legacy', 'launch_state' => 'live_legacy', 'trial_days' => 0,
			'summary' => 'Existing installed assistant offer preserved for current customers and agreements.',
			'limits' => array( 'One website', 'Up to 25 approved FAQ items', 'One lead form', 'Existing agreement controls fulfillment' ),
		),
		'branddad-ai-sales-plus' => array(
			'name' => 'BrandDad AI Sales Assistant — Plus', 'monthly' => '79.00', 'setup' => '199.00', 'customer_credits' => 0,
			'category' => 'installed_legacy', 'launch_state' => 'live_legacy', 'trial_days' => 0,
			'summary' => 'Existing higher-capacity installed assistant offer preserved for current customers and agreements.',
			'limits' => array( 'One website', 'Up to 75 approved knowledge items', 'Up to two supported integrations', 'Existing agreement controls fulfillment' ),
		),
		'branddad-custom-automation' => array(
			'name' => 'BrandDad Custom Business Automation', 'monthly' => '149.00', 'setup' => '499.00', 'customer_credits' => 0,
			'category' => 'installed_legacy', 'launch_state' => 'live_legacy', 'trial_days' => 0,
			'summary' => 'Existing custom automation offer preserved; final scope and price require review.',
			'limits' => array( 'Starts at the displayed price', 'Written scope required', 'Additional usage and integrations quoted separately' ),
		),
		'ai-local-seo-autopilot' => array(
			'name' => 'AI Local SEO Autopilot', 'monthly' => '39.99', 'customer_credits' => 0,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'target_customer' => 'A local business that wants a recurring local-search workflow for one location.',
			'summary' => 'Proposed monthly local SEO workflow. Public checkout stays closed until execution and reporting are verified.',
			'limits' => array( 'One website and one primary location', 'Recurring local SEO checks', 'Recommendations only', 'No ranking guarantee' ),
			'launch_blocker' => 'Shared SEO execution is owned by the backend and is not verified for this price.',
		),
		'ai-seo-growth-autopilot' => array(
			'name' => 'AI SEO Growth Autopilot', 'monthly' => '59.99', 'customer_credits' => 0,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'target_customer' => 'A business that wants ongoing SEO drafts and a publishing schedule.',
			'summary' => 'Proposed SEO growth plan. Rankings, backlinks, and publishing are not shown as live results.',
			'limits' => array( 'Content planning', 'Draft approval before publishing', 'Citation and backlink opportunity workflows', 'No ranking or backlink guarantee' ),
			'launch_blocker' => 'Autoblogging and Search Console execution are not verified for self-serve sale.',
		),
		'ai-seo-pro' => array(
			'name' => 'AI SEO Pro', 'monthly' => '99.99', 'customer_credits' => 0,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'target_customer' => 'A business that needs a larger reviewed SEO workload.',
			'summary' => 'Proposed higher-capacity SEO plan. Checkout stays closed until entitlements and cost are verified.',
			'limits' => array( 'Expanded SEO workflow', 'Technical SEO reporting when connected', 'Human approval before publishing', 'No ranking guarantee' ),
			'launch_blocker' => 'Usage cost and backend entitlements are not verified.',
		),
		'complete-ai-starter' => array(
			'name' => 'Complete AI Starter', 'monthly' => '79.99', 'customer_credits' => 0,
			'category' => 'complete_package', 'launch_state' => 'quote_only', 'trial_days' => 0,
			'target_customer' => 'A small business that wants one bundled monthly workload instead of separate tools.',
			'summary' => 'Proposed bundle. These allowances are targets, not a live entitlement.',
			'limits' => array( '12 social post drafts', '2 social channels', '1 autoblog draft workflow', 'Basic SEO automation', '100 website chatbot conversations', 'Lead capture', 'Monthly reporting' ),
			'launch_blocker' => 'Shared entitlements, chatbot cost, and publishing permissions are not verified.',
		),
		'complete-ai-growth' => array(
			'name' => 'Complete AI Growth', 'monthly' => '149.99', 'customer_credits' => 0,
			'category' => 'complete_package', 'launch_state' => 'quote_only', 'trial_days' => 0,
			'target_customer' => 'A business posting regularly across more than one channel.',
			'summary' => 'Proposed growth bundle. Messaging and booking allowances stay quoted until the shared backend confirms them.',
			'limits' => array( '30 social post drafts', '4 social channels', '2 autoblog draft workflows', '500 chatbot conversations', 'Limited WhatsApp when connected', '1 booking integration', 'Weekly reporting' ),
			'launch_blocker' => 'WhatsApp, booking, and publishing integrations are not verified for this bundle.',
		),
		'complete-ai-pro' => array(
			'name' => 'Complete AI Pro', 'monthly' => '249.99', 'customer_credits' => 0,
			'category' => 'complete_package', 'launch_state' => 'quote_only', 'trial_days' => 0,
			'target_customer' => 'An active business that needs a larger reviewed monthly workload.',
			'summary' => 'Proposed pro bundle. Higher messaging and booking limits are not activated for sale.',
			'limits' => array( '60 social post drafts', '6 social channels', '4 autoblog draft workflows', '1,500 chatbot conversations', 'Up to 3 booking integrations', 'Weekly reporting' ),
			'launch_blocker' => 'Entitlements and provider costs are not verified.',
		),
		'done-for-you-ai-management' => array(
			'name' => 'Done-for-You AI Management', 'monthly' => '399.00', 'customer_credits' => 0,
			'category' => 'complete_package', 'launch_state' => 'quote_only', 'trial_days' => 0,
			'target_customer' => 'A business that wants BrandDad to operate an agreed monthly scope.',
			'summary' => 'Starts at $399 per month after a written scope. This is not an instant checkout product.',
			'limits' => array( 'Written scope required', 'You approve publishing and outbound messages', 'Price can increase with channels, locations, and usage' ),
			'launch_blocker' => 'Each engagement needs a reviewed scope before payment.',
		),
		'music-original-single' => array(
			'name' => 'BrandDad Music — Original Single', 'monthly' => '79.00', 'customer_credits' => 0,
			'category' => 'music', 'launch_state' => 'quote_only', 'billing' => 'project',
			'target_customer' => 'A business that wants one original commissioned song.',
			'summary' => 'Commissioned original music. Artist: T Dash. Songwriter: Thaddeus McCollum. Production: BrandDad Music. Sales: BrandDad Social.',
			'limits' => array( 'One original single', 'Creative brief required', 'Usage rights confirmed before delivery', 'Not a self-serve music generator' ),
			'launch_blocker' => 'Licensing terms and production capacity must be confirmed before public sale.',
		),
		'music-premium-single' => array(
			'name' => 'BrandDad Music — Premium Single + Artwork', 'monthly' => '129.00', 'customer_credits' => 0,
			'category' => 'music', 'launch_state' => 'quote_only', 'billing' => 'project',
			'target_customer' => 'A business that wants a single plus artwork.',
			'summary' => 'Commissioned single with artwork. Credits stay T Dash, Thaddeus McCollum, BrandDad Music, and BrandDad Social.',
			'limits' => array( 'One original single', 'Artwork included', 'Revision terms confirmed in the brief', 'Release only with customer approval' ),
			'launch_blocker' => 'Artwork licensing and production capacity must be confirmed before public sale.',
		),
		'music-ep-3' => array(
			'name' => 'BrandDad Music — 3-Song EP', 'monthly' => '249.00', 'customer_credits' => 0,
			'category' => 'music', 'launch_state' => 'quote_only', 'billing' => 'project',
			'target_customer' => 'A business that wants a short commissioned release.',
			'summary' => 'Three original songs produced for the business after a creative brief.',
			'limits' => array( '3 original songs', 'Preview before final delivery', 'Usage rights in the agreement' ),
			'launch_blocker' => 'Licensing and schedule must be confirmed before public sale.',
		),
		'music-album-6' => array(
			'name' => 'BrandDad Music — 6-Song Album', 'monthly' => '449.00', 'customer_credits' => 0,
			'category' => 'music', 'launch_state' => 'quote_only', 'billing' => 'project',
			'target_customer' => 'A business commissioning a mid-length original album.',
			'summary' => 'Six original songs. This is done-for-you production, not automated generation.',
			'limits' => array( '6 original songs', 'Brief, preview, and revision steps', 'Delivery timeline confirmed per order' ),
			'launch_blocker' => 'Licensing and schedule must be confirmed before public sale.',
		),
		'music-album-12' => array(
			'name' => 'BrandDad Music — 12-Song Album', 'monthly' => '799.00', 'customer_credits' => 0,
			'category' => 'music', 'launch_state' => 'quote_only', 'billing' => 'project',
			'target_customer' => 'A business commissioning a full original album.',
			'summary' => 'Twelve original songs with a written production agreement.',
			'limits' => array( '12 original songs', 'Brief, preview, and revision steps', 'Usage rights confirmed before delivery' ),
			'launch_blocker' => 'Licensing and schedule must be confirmed before public sale.',
		),
		'music-monthly-partnership' => array(
			'name' => 'BrandDad Music — Monthly Partnership', 'monthly' => '199.00', 'customer_credits' => 0,
			'category' => 'music', 'launch_state' => 'quote_only', 'billing' => 'monthly',
			'target_customer' => 'A business that wants an ongoing music partnership.',
			'summary' => 'Starts at $199 per month after the usage, release, and revision scope are written down.',
			'limits' => array( 'Starts at $199 per month', 'Scope confirmed before billing', 'No automatic public release' ),
			'launch_blocker' => 'Recurring music fulfillment is not connected to checkout.',
		),
	);
}

/** Return one catalog record without accepting arbitrary fallback data. */
function bds_ai_commercial_offer( $slug ) {
	$catalog = bds_ai_commercial_catalog();
	$slug    = sanitize_title( (string) $slug );
	return isset( $catalog[ $slug ] ) ? $catalog[ $slug ] : null;
}

/** Resolve checkout only when the exact product is public and the catalog permits sale. */
function bds_ai_commercial_checkout_url( $slug ) {
	$offer = bds_ai_commercial_offer( $slug );
	if ( ! $offer || ! in_array( $offer['launch_state'], array( 'launch_candidate', 'live_legacy' ), true ) ) {
		return '';
	}
	$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'product' );
	return $post instanceof WP_Post && 'publish' === $post->post_status ? get_permalink( $post ) : '';
}

/** Keep the approved recurring Social Growth CTA explicit after legacy payment filters run. */
function bds_ai_commercial_monthly_button_text( $text, $product = null ) {
	$pid = is_object( $product ) && method_exists( $product, 'get_id' ) ? (int) $product->get_id() : 0;
	if ( $pid < 1 || 'yes' !== (string) get_post_meta( $pid, '_wps_sfw_product', true ) ) {
		return $text;
	}
	$slug  = (string) get_post_field( 'post_name', $pid );
	$offer = bds_ai_commercial_offer( $slug );
	if ( ! is_array( $offer ) || ! in_array( $offer['launch_state'], array( 'launch_candidate', 'live_legacy' ), true ) ) {
		return $text;
	}
	return __( 'Subscribe monthly', 'branddad' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'bds_ai_commercial_monthly_button_text', PHP_INT_MAX, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', 'bds_ai_commercial_monthly_button_text', PHP_INT_MAX, 2 );

