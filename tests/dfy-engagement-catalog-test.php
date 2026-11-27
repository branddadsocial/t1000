<?php

define( 'ABSPATH', __DIR__ . '/' );

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
	}
}

require dirname( __DIR__ ) . '/deploy/bds-dfy-engagement-catalog-mu.php';

$catalog  = bds_dfy_engagement_catalog();
$services = bds_dfy_supplier_services();
$metrics  = bds_dfy_customer_metric_schema();
$starter  = bds_dfy_package_schedule( 'promotion-starter' );
$growth   = bds_dfy_package_schedule( 'promotion-growth' );
$pro      = bds_dfy_package_schedule( 'promotion-pro' );
$base     = bds_dfy_package_schedule( 'complete-management' );

$checks = array(
	'catalog version'          => '0.2.0' === BDS_DFY_ENGAGEMENT_CATALOG_VERSION,
	'panel catalog retired'    => array() === $services,
	'base plan'                => 39900 === $catalog['complete-management']['total_price_minor'] && 20 === $catalog['complete-management']['content_posts'] && 0 === $catalog['complete-management']['promotion_slots'] && 'qualification_only' === $catalog['complete-management']['execution_mode'],
	'promotion totals'         => 59900 === $catalog['promotion-starter']['total_price_minor'] && 69900 === $catalog['promotion-growth']['total_price_minor'] && 84900 === $catalog['promotion-pro']['total_price_minor'],
	'promotion add-ons'        => 20000 === $catalog['promotion-starter']['add_on_price_minor'] && 30000 === $catalog['promotion-growth']['add_on_price_minor'] && 45000 === $catalog['promotion-pro']['add_on_price_minor'],
	'promotion slots'          => 4 === $catalog['promotion-starter']['promotion_slots'] && 8 === $catalog['promotion-growth']['promotion_slots'] && 12 === $catalog['promotion-pro']['promotion_slots'],
	'content stays consistent' => 20 === $catalog['promotion-starter']['content_posts'] && 20 === $catalog['promotion-growth']['content_posts'] && 20 === $catalog['promotion-pro']['content_posts'],
	'official only'            => $catalog['promotion-starter']['official_platform_only'] && $catalog['promotion-growth']['official_platform_only'] && $catalog['promotion-pro']['official_platform_only'],
	'authorization required'   => 'official_api_only_after_authorization' === $catalog['promotion-starter']['execution_mode'] && 'official_api_only_after_authorization' === $catalog['promotion-growth']['execution_mode'] && 'official_api_only_after_authorization' === $catalog['promotion-pro']['execution_mode'],
	'budgets stay separate'    => ! $catalog['promotion-starter']['media_budget_included'] && ! $catalog['promotion-growth']['creator_costs_included'] && ! $catalog['promotion-pro']['media_budget_included'],
	'base schedule'            => 20 === count( $base['content_days'] ) && array() === $base['promotion_days'],
	'starter schedule'         => array( 4, 12, 19, 27 ) === $starter['promotion_days'],
	'growth schedule'          => array( 2, 6, 10, 14, 17, 21, 25, 29 ) === $growth['promotion_days'],
	'pro schedule'             => array( 2, 4, 7, 9, 12, 14, 17, 19, 22, 24, 27, 29 ) === $pro['promotion_days'],
	'schedules are unique'     => count( $pro['content_days'] ) === count( array_unique( $pro['content_days'] ) ) && count( $pro['promotion_days'] ) === count( array_unique( $pro['promotion_days'] ) ),
	'invalid schedule closed'  => array() === bds_dfy_even_schedule_days( 31, 30 ) && null === bds_dfy_package_schedule( 'unknown' ),
	'cost helper closed'       => 0 === bds_dfy_estimated_supplier_ceiling_minor( 'complete-management', 'unused' ) && null === bds_dfy_estimated_supplier_ceiling_minor( 'promotion-growth', 'unused' ) && null === bds_dfy_estimated_supplier_ceiling_minor( 'unknown', 'unused' ),
	'honest metric labels'     => isset( $metrics['organic_reach'], $metrics['paid_reach'], $metrics['paid_media_spend'], $metrics['creator_deliverables'], $metrics['leads_recorded'] ) && ! isset( $metrics['campaigns_confirmed'], $metrics['assisted_reach'] ),
);

$failed = array_keys( array_filter( $checks, static function ( $passed ) { return ! $passed; } ) );
if ( $failed ) {
	fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

echo 'Done-for-You promotion catalog checks passed: ' . count( $checks ) . PHP_EOL;
