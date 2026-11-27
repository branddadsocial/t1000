<?php
/**
 * Plugin Name: BrandDad Done-for-You Engagement Catalog
 * Description: Private, fail-closed supplier mapping and reporting schema for optional BDS Network visibility upgrades.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

define( 'BDS_DFY_ENGAGEMENT_CATALOG_VERSION', '0.1.0' );

/**
 * Private supplier cost reference in USD cents.
 *
 * This is not a customer-facing API and must never be rendered into page HTML.
 * Per-thousand costs are recorded exactly as supplied for planning; future
 * fulfillment must replace estimates with provider-confirmed ledger records.
 *
 * @return array<int,array<string,int|string>>
 */
function bds_dfy_supplier_services() {
	return array(
		3335  => array( 'metric' => 'likes_impressions_views', 'cost_basis' => 'per_1000', 'cost_minor' => 200 ),
		10118 => array( 'metric' => 'us_reposts', 'cost_basis' => 'per_1000', 'cost_minor' => 985 ),
		6367  => array( 'metric' => 'saves', 'cost_basis' => 'per_1000', 'cost_minor' => 25 ),
		6118  => array( 'metric' => 'tag_impressions', 'cost_basis' => 'per_1000', 'cost_minor' => 8 ),
		10144 => array( 'metric' => 'reach_impressions', 'cost_basis' => 'per_1000', 'cost_minor' => 8 ),
		5922  => array( 'metric' => 'verified_comments', 'cost_basis' => 'per_1000', 'cost_minor' => 112500 ),
		10119 => array( 'metric' => 'relevant_comments', 'cost_basis' => 'per_1000', 'cost_minor' => 1875 ),
		8751  => array( 'metric' => 'us_audience_growth_standard', 'cost_basis' => 'per_month', 'cost_minor' => 4125 ),
		8752  => array( 'metric' => 'us_audience_growth_pro', 'cost_basis' => 'per_month', 'cost_minor' => 8875 ),
		8753  => array( 'metric' => 'us_audience_growth_premium', 'cost_basis' => 'per_month', 'cost_minor' => 13750 ),
		7124  => array( 'metric' => 'worldwide_audience_growth_50', 'cost_basis' => 'per_month', 'cost_minor' => 6000 ),
		7127  => array( 'metric' => 'worldwide_audience_growth_100', 'cost_basis' => 'per_month', 'cost_minor' => 9000 ),
		6818  => array( 'metric' => 'worldwide_audience_growth_200', 'cost_basis' => 'per_month', 'cost_minor' => 14400 ),
		7125  => array( 'metric' => 'us_uk_audience_growth_50', 'cost_basis' => 'per_month', 'cost_minor' => 7200 ),
		7128  => array( 'metric' => 'us_uk_audience_growth_100', 'cost_basis' => 'per_month', 'cost_minor' => 10200 ),
	);
}

/**
 * Server-owned package map. No automatic provider execution is permitted.
 *
 * `monthly_caps` are internal maximum order quantities, not public promises.
 * USA/UK alternatives are manual fallbacks only; they must not be retried
 * automatically after a primary request may have reached the provider.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_dfy_engagement_catalog() {
	return array(
		'complete-management' => array(
			'name'                => 'Done-for-You Complete Management',
			'ledger_product_slug' => 'done-for-you-ai-management',
			'total_price_minor'   => 39900,
			'add_on_price_minor'  => 0,
			'eligible_posts'      => 20,
			'network_engagement'  => false,
			'execution_mode'      => 'no_provider_orders',
			'monthly_caps'        => array(),
		),
		'essential-visibility' => array(
			'name'               => 'Essential Visibility',
			'ledger_product_slug' => 'done-for-you-ai-management',
			'total_price_minor'  => 59900,
			'add_on_price_minor' => 20000,
			'eligible_posts'     => 12,
			'network_engagement' => true,
			'execution_mode'     => 'manual_review_only',
			'regions'            => array(
				'us_focused' => array( 'primary_service_id' => 8751, 'manual_fallback_service_ids' => array( 7125 ) ),
				'worldwide'  => array( 'primary_service_id' => 7124, 'manual_fallback_service_ids' => array() ),
			),
			'monthly_caps'       => array( 3335 => 1800, 6367 => 300, 6118 => 12000, 10144 => 12000, 10119 => 24 ),
			'us_only_caps'       => array( 10118 => 24 ),
		),
		'growth-visibility' => array(
			'name'               => 'Growth Visibility',
			'ledger_product_slug' => 'done-for-you-ai-management',
			'total_price_minor'  => 69900,
			'add_on_price_minor' => 30000,
			'eligible_posts'     => 20,
			'network_engagement' => true,
			'execution_mode'     => 'manual_review_only',
			'regions'            => array(
				'us_focused' => array( 'primary_service_id' => 8752, 'manual_fallback_service_ids' => array( 7128 ) ),
				'worldwide'  => array( 'primary_service_id' => 7127, 'manual_fallback_service_ids' => array() ),
			),
			'monthly_caps'       => array( 3335 => 5000, 6367 => 1000, 6118 => 50000, 10144 => 50000, 10119 => 60 ),
			'us_only_caps'       => array( 10118 => 60 ),
		),
		'maximum-visibility' => array(
			'name'               => 'Maximum Visibility',
			'ledger_product_slug' => 'done-for-you-ai-management',
			'total_price_minor'  => 84900,
			'add_on_price_minor' => 45000,
			'eligible_posts'     => 30,
			'network_engagement' => true,
			'execution_mode'     => 'manual_review_only',
			'regions'            => array(
				'us_focused' => array( 'primary_service_id' => 8753, 'manual_fallback_service_ids' => array() ),
				'worldwide'  => array( 'primary_service_id' => 6818, 'manual_fallback_service_ids' => array() ),
			),
			'monthly_caps'       => array( 3335 => 12000, 6367 => 2250, 6118 => 150000, 10144 => 150000, 10119 => 150, 5922 => 20 ),
			'us_only_caps'       => array( 10118 => 150 ),
		),
	);
}

/**
 * Estimate the internal supplier ceiling for one reviewed monthly package.
 *
 * The value is planning-only. Actual profitability must use the verified
 * provider-cost ledger after fulfillment.
 *
 * @param string $plan   Server-owned plan key.
 * @param string $region `us_focused` or `worldwide`.
 * @return int|null USD cents, or null when the mapping is invalid.
 */
function bds_dfy_estimated_supplier_ceiling_minor( $plan, $region ) {
	$catalog  = bds_dfy_engagement_catalog();
	$services = bds_dfy_supplier_services();
	if ( ! isset( $catalog[ $plan ] ) || ! is_array( $catalog[ $plan ] ) ) {
		return null;
	}
	$row = $catalog[ $plan ];
	if ( empty( $row['network_engagement'] ) ) {
		return 0;
	}
	if ( ! isset( $row['regions'][ $region ]['primary_service_id'] ) ) {
		return null;
	}

	$primary = (int) $row['regions'][ $region ]['primary_service_id'];
	if ( ! isset( $services[ $primary ] ) || 'per_month' !== $services[ $primary ]['cost_basis'] ) {
		return null;
	}
	$cost = (int) $services[ $primary ]['cost_minor'];
	$caps = is_array( $row['monthly_caps'] ?? null ) ? $row['monthly_caps'] : array();
	if ( 'us_focused' === $region && is_array( $row['us_only_caps'] ?? null ) ) {
		$caps += $row['us_only_caps'];
	}
	foreach ( $caps as $service_id => $quantity ) {
		$service_id = (int) $service_id;
		$quantity   = (int) $quantity;
		if ( $quantity < 1 || ! isset( $services[ $service_id ] ) || 'per_1000' !== $services[ $service_id ]['cost_basis'] ) {
			return null;
		}
		$cost += (int) ceil( $quantity * (int) $services[ $service_id ]['cost_minor'] / 1000 );
	}
	return $cost;
}

/**
 * Customer-safe reporting vocabulary. Counts remain zero/unavailable until an
 * authoritative event confirms them; supplier ids never appear in the view.
 *
 * @return array<string,array<string,string>>
 */
function bds_dfy_customer_metric_schema() {
	return array(
		'content_created'       => array( 'label' => 'Content created', 'evidence' => 'BrandDad content record' ),
		'content_published'     => array( 'label' => 'Content published', 'evidence' => 'Connected-platform confirmation' ),
		'campaigns_confirmed'   => array( 'label' => 'Visibility campaigns confirmed', 'evidence' => 'Private delivery ledger' ),
		'assisted_reach'        => array( 'label' => 'Campaign-assisted reach', 'evidence' => 'Verified campaign or platform metric' ),
		'assisted_impressions'  => array( 'label' => 'Campaign-assisted impressions', 'evidence' => 'Verified campaign or platform metric' ),
		'assisted_saves'        => array( 'label' => 'Campaign-assisted saves', 'evidence' => 'Verified campaign or platform metric' ),
		'assisted_reposts'      => array( 'label' => 'Campaign-assisted reposts', 'evidence' => 'Verified campaign or platform metric' ),
		'conversations_handled' => array( 'label' => 'Conversations handled', 'evidence' => 'BrandDad conversation event' ),
		'leads_recorded'        => array( 'label' => 'Leads recorded', 'evidence' => 'BrandDad lead event' ),
	);
}
