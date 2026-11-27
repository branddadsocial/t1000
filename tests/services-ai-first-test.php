<?php

define( 'ABSPATH', __DIR__ );

function add_shortcode() {}
function add_filter() {}
function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) );
}
function sanitize_title( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9\-]/i', '-', (string) $value ) );
}
function get_posts() {
	return array();
}
function get_permalink( $id ) {
	return 'https://branddad.social/?p=' . (int) $id;
}

require dirname( __DIR__ ) . '/deploy/bds-services-catalog-mu.php';

$failures = 0;

function bds_ai_first_assert( $condition, $label ) {
	global $failures;
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	++$failures;
	echo "FAIL: {$label}\n";
}

$seo = bds_svc_recommend_for_issues( array( 'seo_meta', 'seo_headings', 'no_schema' ) );
bds_ai_first_assert( ! empty( $seo ), 'SEO recommendations exist' );
bds_ai_first_assert( 'ai-local-seo' === $seo[0]['key'], 'AI Local SEO is the first SEO recommendation' );
bds_ai_first_assert( 19.99 === $seo[0]['price'], 'AI Local SEO keeps the approved 19.99 price' );
bds_ai_first_assert( ! in_array( 'website-seo-audit', array_column( $seo, 'key' ), true ), 'legacy 179 SEO audit is not recommended' );

$performance = bds_svc_recommend_for_issues( array( 'slow_ttfb', 'large_assets' ) );
bds_ai_first_assert( 'website-speed-optimization' === $performance[0]['key'], 'AI Website Improvement Plan is first for performance signals' );
bds_ai_first_assert( 19.99 === $performance[0]['price'], 'AI Website Improvement Plan keeps the approved 19.99 price' );

$social = bds_svc_recommend_for_issues( array( 'social_incomplete', 'social_inconsistent' ) );
bds_ai_first_assert( 'branddad-ai-studio-starter' === $social[0]['key'], 'AI Studio Starter is first for profile-copy gaps' );
bds_ai_first_assert( 14.99 === $social[0]['price'], 'AI Studio Starter keeps the approved 14.99 price' );

$critical = bds_svc_recommend_for_issues( array( 'site_down', 'https_fail' ) );
bds_ai_first_assert( 'fix-my-website' === $critical[0]['key'], 'critical technical work remains a hands-on service' );

if ( 0 !== $failures ) {
	exit( 1 );
}

echo "\nAI-first service recommendation behavior passed.\n";
