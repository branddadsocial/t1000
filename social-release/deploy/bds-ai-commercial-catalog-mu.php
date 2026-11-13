<?php
/**
 * Plugin Name: BrandDad AI Commercial Catalog (Staged)
 * Description: One fail-closed source of truth for BrandDad AI pricing, limits, delivery readiness, and checkout eligibility.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_AI_COMMERCIAL_CATALOG_VERSION', '0.1.0' );

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
		'ai-social-starter' => array(
			'name' => 'AI Social Starter', 'monthly' => '14.99', 'customer_credits' => 60,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'A small monthly set of editable social content drafts for one business.',
			'limits' => array( '12 social post drafts each month', 'One brand profile', 'Approval before publishing', 'No premium video generation' ),
		),
		'ai-local-seo' => array(
			'name' => 'AI Local SEO', 'monthly' => '19.99', 'customer_credits' => 60,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'A monthly local-search health check with clear improvement suggestions.',
			'limits' => array( 'One website and one primary location', 'One monthly local SEO report', 'Recommendations only', 'No ranking guarantee' ),
		),
		'ai-lead-assistant' => array(
			'name' => 'AI Lead Assistant', 'monthly' => '19.99', 'customer_credits' => 80,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
			'summary' => 'Lead capture, email notifications, and editable inquiry summaries for one business.',
			'limits' => array( 'One lead form', 'Up to 50 captured leads each month', 'Up to 20 AI summaries', 'No paid messaging included' ),
		),
		'ai-social-growth' => array(
			'name' => 'AI Social Growth', 'monthly' => '29.99', 'customer_credits' => 150,
			'category' => 'specialized', 'launch_state' => 'staged', 'trial_days' => 0,
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
			'category' => 'flagship', 'launch_state' => 'staged', 'trial_days' => 0, 'featured' => true,
			'summary' => 'The practical BrandDad bundle for content, reviews, leads, monitoring, and monthly business visibility.',
			'limits' => array( 'One website and business profile', 'Basic website monitoring', 'One monthly SEO health check', '12 social post drafts', '10 review-response drafts', 'One lead form and email notifications', 'One monthly activity report' ),
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

