<?php
/**
 * Plugin Name: BrandDad Done-for-You Promotion Catalog
 * Description: Fail-closed package limits, even scheduling, and reporting vocabulary for optional official promotion management.
 * Version: 0.3.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_ENGAGEMENT_CATALOG_VERSION', '0.3.0' );

/**
 * The retired supplier-panel catalog deliberately resolves to an empty map.
 * Kept only as a compatibility function for callers from the earlier design.
 *
 * @return array<int,array<string,mixed>>
 */
function bds_dfy_supplier_services() {
	return array();
}

/**
 * Spread a fixed number of slots through a service period using the center of
 * equal-width buckets. This avoids front-loading and duplicate days.
 *
 * @param int $slots Number of scheduled actions.
 * @param int $days  Length of the service period.
 * @return int[] One-based day numbers.
 */
function bds_dfy_even_schedule_days( $slots, $days = 30 ) {
	$slots = absint( $slots );
	$days  = absint( $days );
	if ( $slots < 1 || $days < 1 || $slots > $days ) {
		return array();
	}
	$out = array();
	for ( $index = 0; $index < $slots; $index++ ) {
		$day = 1 + (int) floor( ( ( $index + 0.5 ) * $days ) / $slots );
		$out[] = min( $days, $day );
	}
	return array_values( array_unique( $out ) );
}

/**
 * Server-owned package map. There are no panel service ids or generic provider
 * orders. Promotion requires an authorized official platform or a documented,
 * customer-approved creator agreement.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_dfy_engagement_catalog() {
	return array(
		'complete-management' => array(
			'name'                     => 'Done-for-You Complete Management',
			'ledger_product_slug'      => 'done-for-you-ai-management',
			'total_price_minor'        => 39900,
			'add_on_price_minor'       => 0,
			'content_posts'            => 20,
			'promotion_slots'          => 0,
			'execution_mode'           => 'qualification_only',
			'official_platform_only'   => true,
			'media_budget_included'    => false,
			'creator_costs_included'   => false,
		),
		'promotion-starter' => array(
			'name'                     => 'Promotion Starter',
			'ledger_product_slug'      => 'done-for-you-ai-management',
			'total_price_minor'        => 44900,
			'add_on_price_minor'       => 5000,
			'content_posts'            => 20,
			'promotion_slots'          => 4,
			'execution_mode'           => 'official_api_only_after_authorization',
			'official_platform_only'   => true,
			'media_budget_included'    => false,
			'creator_costs_included'   => false,
		),
		'promotion-growth' => array(
			'name'                     => 'Promotion Growth',
			'ledger_product_slug'      => 'done-for-you-ai-management',
			'total_price_minor'        => 49900,
			'add_on_price_minor'       => 10000,
			'content_posts'            => 20,
			'promotion_slots'          => 8,
			'execution_mode'           => 'official_api_only_after_authorization',
			'official_platform_only'   => true,
			'media_budget_included'    => false,
			'creator_costs_included'   => false,
		),
		'promotion-pro' => array(
			'name'                     => 'Promotion Pro',
			'ledger_product_slug'      => 'done-for-you-ai-management',
			'total_price_minor'        => 59900,
			'add_on_price_minor'       => 20000,
			'content_posts'            => 20,
			'promotion_slots'          => 12,
			'execution_mode'           => 'official_api_only_after_authorization',
			'official_platform_only'   => true,
			'media_budget_included'    => false,
			'creator_costs_included'   => false,
		),
	);
}

/** Return the server-owned content and promotion schedule for one package. */
function bds_dfy_package_schedule( $plan, $days = 30 ) {
	$catalog = bds_dfy_engagement_catalog();
	$plan    = sanitize_key( (string) $plan );
	if ( ! isset( $catalog[ $plan ] ) ) {
		return null;
	}
	$row = $catalog[ $plan ];
	return array(
		'period_days'    => absint( $days ),
		'content_days'   => bds_dfy_even_schedule_days( $row['content_posts'], $days ),
		'promotion_days' => bds_dfy_even_schedule_days( $row['promotion_slots'], $days ),
	);
}

/**
 * Retained compatibility helper. Promotion media costs are deliberately not
 * estimated because customer-approved platform spend is separate.
 */
function bds_dfy_estimated_supplier_ceiling_minor( $plan, $region ) {
	$catalog = bds_dfy_engagement_catalog();
	$plan    = sanitize_key( (string) $plan );
	if ( ! isset( $catalog[ $plan ] ) ) {
		return null;
	}
	return 0 === (int) $catalog[ $plan ]['promotion_slots'] ? 0 : null;
}

/** Customer-safe reporting vocabulary with paid and organic metrics separated. */
function bds_dfy_customer_metric_schema() {
	return array(
		'content_created'       => array( 'label' => 'Content created', 'evidence' => 'BrandDad content record' ),
		'content_published'     => array( 'label' => 'Content published', 'evidence' => 'Connected-platform confirmation' ),
		'organic_reach'         => array( 'label' => 'Organic reach', 'evidence' => 'Connected-platform organic metric' ),
		'organic_impressions'   => array( 'label' => 'Organic impressions', 'evidence' => 'Connected-platform organic metric' ),
		'paid_reach'            => array( 'label' => 'Paid reach', 'evidence' => 'Official advertising account metric' ),
		'paid_impressions'      => array( 'label' => 'Paid impressions', 'evidence' => 'Official advertising account metric' ),
		'paid_clicks'           => array( 'label' => 'Paid clicks', 'evidence' => 'Official advertising account metric' ),
		'paid_media_spend'      => array( 'label' => 'Paid media spend', 'evidence' => 'Official advertising account billing metric' ),
		'creator_deliverables'  => array( 'label' => 'Creator deliverables completed', 'evidence' => 'Approved campaign record' ),
		'conversations_handled' => array( 'label' => 'Conversations handled', 'evidence' => 'BrandDad conversation event' ),
		'leads_recorded'        => array( 'label' => 'Leads recorded', 'evidence' => 'BrandDad lead event' ),
	);
}
