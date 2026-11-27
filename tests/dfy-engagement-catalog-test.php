<?php

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/deploy/bds-dfy-engagement-catalog-mu.php';

$catalog  = bds_dfy_engagement_catalog();
$services = bds_dfy_supplier_services();
$metrics  = bds_dfy_customer_metric_schema();
$all_ids  = array_keys( $services );
$expected = array( 3335, 10118, 6367, 6118, 10144, 5922, 10119, 8751, 8752, 8753, 7124, 7127, 6818, 7125, 7128 );
sort( $all_ids );
sort( $expected );

$checks = array(
	'catalog version'         => '0.1.0' === BDS_DFY_ENGAGEMENT_CATALOG_VERSION,
	'base has no engagement'  => false === $catalog['complete-management']['network_engagement'] && 39900 === $catalog['complete-management']['total_price_minor'] && 'no_provider_orders' === $catalog['complete-management']['execution_mode'],
	'optional totals'         => 59900 === $catalog['essential-visibility']['total_price_minor'] && 69900 === $catalog['growth-visibility']['total_price_minor'] && 84900 === $catalog['maximum-visibility']['total_price_minor'],
	'optional add-ons'        => 20000 === $catalog['essential-visibility']['add_on_price_minor'] && 30000 === $catalog['growth-visibility']['add_on_price_minor'] && 45000 === $catalog['maximum-visibility']['add_on_price_minor'],
	'post caps'               => 12 === $catalog['essential-visibility']['eligible_posts'] && 20 === $catalog['growth-visibility']['eligible_posts'] && 30 === $catalog['maximum-visibility']['eligible_posts'],
	'all supplier ids mapped' => $expected === $all_ids,
	'us primary progression'  => 8751 === $catalog['essential-visibility']['regions']['us_focused']['primary_service_id'] && 8752 === $catalog['growth-visibility']['regions']['us_focused']['primary_service_id'] && 8753 === $catalog['maximum-visibility']['regions']['us_focused']['primary_service_id'],
	'world progression'       => 7124 === $catalog['essential-visibility']['regions']['worldwide']['primary_service_id'] && 7127 === $catalog['growth-visibility']['regions']['worldwide']['primary_service_id'] && 6818 === $catalog['maximum-visibility']['regions']['worldwide']['primary_service_id'],
	'fallbacks manual only'   => array( 7125 ) === $catalog['essential-visibility']['regions']['us_focused']['manual_fallback_service_ids'] && array( 7128 ) === $catalog['growth-visibility']['regions']['us_focused']['manual_fallback_service_ids'],
	'no automatic execution'  => 'manual_review_only' === $catalog['essential-visibility']['execution_mode'] && 'manual_review_only' === $catalog['growth-visibility']['execution_mode'] && 'manual_review_only' === $catalog['maximum-visibility']['execution_mode'],
	'ledger product retained' => 'done-for-you-ai-management' === $catalog['complete-management']['ledger_product_slug'] && 'done-for-you-ai-management' === $catalog['maximum-visibility']['ledger_product_slug'],
	'cost ceilings sensible'  => 0 === bds_dfy_estimated_supplier_ceiling_minor( 'complete-management', 'us_focused' ) && 4754 === bds_dfy_estimated_supplier_ceiling_minor( 'essential-visibility', 'us_focused' ) && 6605 === bds_dfy_estimated_supplier_ceiling_minor( 'essential-visibility', 'worldwide' ) && 10873 === bds_dfy_estimated_supplier_ceiling_minor( 'growth-visibility', 'us_focused' ) && 10938 === bds_dfy_estimated_supplier_ceiling_minor( 'growth-visibility', 'worldwide' ) && 21287 === bds_dfy_estimated_supplier_ceiling_minor( 'maximum-visibility', 'us_focused' ) && 21789 === bds_dfy_estimated_supplier_ceiling_minor( 'maximum-visibility', 'worldwide' ),
	'invalid mapping closed'  => null === bds_dfy_estimated_supplier_ceiling_minor( 'unknown', 'us_focused' ) && null === bds_dfy_estimated_supplier_ceiling_minor( 'growth-visibility', 'unknown' ),
	'honest metric labels'    => isset( $metrics['content_published'], $metrics['campaigns_confirmed'], $metrics['assisted_reach'], $metrics['leads_recorded'] ) && false !== strpos( $metrics['assisted_reach']['label'], 'Campaign-assisted' ),
);

$failed = array_keys( array_filter( $checks, static function ( $passed ) { return ! $passed; } ) );
if ( $failed ) {
	fwrite( STDERR, 'Failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

echo 'Done-for-You engagement catalog checks passed: ' . count( $checks ) . PHP_EOL;
