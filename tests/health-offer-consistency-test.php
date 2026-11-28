<?php
define( 'ABSPATH', __DIR__ . '/' );

$filters = array();
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['filters'][ $hook ] = array( $callback, $priority, $accepted_args );
}

class WP_REST_Response {
	private $data;
	public function __construct( $data ) { $this->data = $data; }
	public function get_data() { return $this->data; }
	public function set_data( $data ) { $this->data = $data; }
}

class BDS_Health_Test_Request {
	private $route;
	public function __construct( $route ) { $this->route = $route; }
	public function get_route() { return $this->route; }
}

require dirname( __DIR__ ) . '/deploy/zzzzzzzzz-bds-health-offer-consistency-mu.php';

function bds_health_offer_test_assert( $condition, $label ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
	echo "PASS: {$label}\n";
}

$legacy = new WP_REST_Response(
	array(
		'score' => 85,
		'recommendations' => array(
			array(
				'key' => 'website-speed-optimization',
				'title' => 'Website Speed Optimization',
				'price' => 149,
				'url' => 'https://branddad.social/product/website-speed-optimization/',
				'why' => 'Addresses: weak performance signals.',
			),
		),
	)
);

$result = bds_health_offer_consistency_response( $legacy, null, new BDS_Health_Test_Request( '/bds-health/v1/check' ) );
$data   = $result->get_data();
$offer  = $data['recommendations'][0];
bds_health_offer_test_assert( 'AI Website Improvement Plan' === $offer['title'], 'legacy title is replaced' );
bds_health_offer_test_assert( 19.99 === $offer['price'], 'legacy price is replaced' );
bds_health_offer_test_assert( false !== strpos( $offer['url'], 'branddad-smartsites-website-optimizer' ), 'canonical product URL is used' );
bds_health_offer_test_assert( 'Addresses: weak performance signals.' === $offer['why'], 'diagnostic reason is preserved' );

$other = new WP_REST_Response( array( 'recommendations' => array( array( 'key' => 'ai-local-seo', 'title' => 'AI Local SEO Starter', 'price' => 19.99 ) ) ) );
bds_health_offer_consistency_response( $other, null, new BDS_Health_Test_Request( '/bds-health/v1/check' ) );
bds_health_offer_test_assert( 'AI Local SEO Starter' === $other->get_data()['recommendations'][0]['title'], 'unrelated recommendation is unchanged' );

$wrong_route = new WP_REST_Response( array( 'recommendations' => array( array( 'key' => 'website-speed-optimization', 'title' => 'Website Speed Optimization', 'price' => 149 ) ) ) );
bds_health_offer_consistency_response( $wrong_route, null, new BDS_Health_Test_Request( '/branddad/v1/catalog' ) );
bds_health_offer_test_assert( 149 === $wrong_route->get_data()['recommendations'][0]['price'], 'unrelated REST route is unchanged' );

echo "Health offer consistency test passed.\n";

