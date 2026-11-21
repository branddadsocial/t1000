<?php
/**
 * Featured SKU, product_ready, and is_paid grant gates. No live charge.
 */
define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function remove_action() {}
function absint( $n ) {
	return abs( (int) $n );
}
function sanitize_key( $s ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
}
function sanitize_text_field( $s ) {
	return trim( (string) $s );
}
function wc_format_decimal( $n, $dp = 2 ) {
	if ( '' === $n || null === $n ) {
		return '';
	}
	return number_format( (float) $n, (int) $dp, '.', '' );
}

$GLOBALS['bds_wps_meta'] = array();
function wps_sfw_get_meta_data( $id, $key, $single = true ) {
	return $GLOBALS['bds_wps_meta'][ absint( $id ) ][ (string) $key ] ?? '';
}

class BdsFeatProductStub {
	public $id;
	public $sku;
	public $price;
	public function get_id() {
		return $this->id;
	}
	public function get_sku() {
		return $this->sku;
	}
	public function get_price() {
		return $this->price;
	}
}

require dirname( __DIR__ ) . '/deploy/cursor/bds-directory-featured-ads-lib.php';
require dirname( __DIR__ ) . '/deploy/cursor/backend/bds-featured-billing-adapter-mu.php';

$failed = 0;
function grant_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

function bds_feat_stub_product( $sku, $price, $id, $number = 1, $unit = 'month', $enabled = 'yes' ) {
	$product        = new BdsFeatProductStub();
	$product->id    = $id;
	$product->sku   = $sku;
	$product->price = $price;
	$GLOBALS['bds_wps_meta'][ $id ] = array(
		'_wps_sfw_product'               => $enabled,
		'wps_sfw_subscription_number'    => $number,
		'wps_sfw_subscription_interval'  => $unit,
	);
	return $product;
}

$skus = bds_featured_billing_skus();
grant_expect( array( 'local', 'regional', 'national', 'global' ) === array_column( $skus, 'level' ), 'exactly four Featured SKUs are allowlisted' );
grant_expect( '9.99' === $skus['bds-feat-local']['monthly'], 'local SKU stays 9.99' );
grant_expect( '19.99' === $skus['bds-feat-regional']['monthly'], 'regional SKU stays 19.99' );
grant_expect( '39.99' === $skus['bds-feat-national']['monthly'], 'national SKU stays 39.99' );
grant_expect( '99.99' === $skus['bds-feat-global']['monthly'], 'global SKU stays 99.99' );
grant_expect( array() === bds_featured_billing_sku_offer( 'bds-feat-pro' ), 'a Pro SKU is not a Featured SKU' );
grant_expect( array() === bds_featured_billing_sku_offer( '' ), 'an empty SKU is not allowlisted' );
grant_expect( 'bds-feat-local' === bds_feat_product_sku( 'local' ), 'lib SKU for local is bds-feat-local' );
grant_expect( 'local' === ( bds_featured_billing_sku_offer( bds_feat_product_sku( 'local' ) )['level'] ?? '' ), 'lib SKU matches the billing allowlist' );

grant_expect( true === bds_featured_billing_interval_ok( 1, 'month' ), 'monthly interval 1 is accepted' );
grant_expect( false === bds_featured_billing_interval_ok( 12, 'month' ), 'a 12-month interval is refused' );
grant_expect( false === bds_featured_billing_interval_ok( 1, 'year' ), 'a yearly interval is refused' );

$local = bds_feat_stub_product( 'bds-feat-local', '9.99', 17607 );
grant_expect( 'local' === bds_featured_billing_product_level( $local ), 'local SKU maps to the local lane' );
grant_expect( true === bds_featured_billing_product_ready( $local ), 'allowlisted local SKU at 9.99 monthly is ready' );
grant_expect( '' === bds_featured_billing_product_level( false ), 'a missing product has no Featured lane' );
grant_expect( false === bds_featured_billing_product_ready( false ), 'a missing product is not ready' );

$cheap = bds_feat_stub_product( 'bds-feat-local', '6.99', 17608 );
grant_expect( false === bds_featured_billing_product_ready( $cheap ), 'a local SKU at the old activation price is not ready' );
$year = bds_feat_stub_product( 'bds-feat-local', '9.99', 17609, 1, 'year' );
grant_expect( false === bds_featured_billing_product_ready( $year ), 'a yearly Featured product is not ready' );
$off = bds_feat_stub_product( 'bds-feat-global', '99.99', 17610, 1, 'month', 'no' );
grant_expect( false === bds_featured_billing_product_ready( $off ), 'a SKU that is not a subscription product is not ready' );
$wrong_lane = bds_feat_stub_product( 'bds-feat-global', '9.99', 17611 );
grant_expect( false === bds_featured_billing_product_ready( $wrong_lane ), 'global at the local price is not ready' );
$unknown = bds_feat_stub_product( 'bds-activation', '6.99', 17612 );
grant_expect( false === bds_featured_billing_product_ready( $unknown ), 'an activation SKU is not a Featured grant' );

grant_expect( false === bds_featured_billing_cart_owner_ok( false, 17596, true ), 'signed-out checkout cannot attach a listing' );
grant_expect( false === bds_featured_billing_cart_owner_ok( true, 0, true ), 'a missing listing id cannot enter the cart' );
grant_expect( false === bds_featured_billing_cart_owner_ok( true, 17596, false ), 'a listing the shopper does not own cannot enter the cart' );
grant_expect( true === bds_featured_billing_cart_owner_ok( true, 17596, true ), 'a signed-in owner may attach their listing' );
grant_expect( false === bds_featured_billing_listing_owned( 17596, 42 ), 'listing ownership stays closed without the Directory eligibility helper' );

$one = array( array( 'level' => 'local', 'monthly' => '9.99', 'quantity' => 1, 'subtotal' => '9.99', 'listing_id' => 17596, 'owner_id' => 42 ) );
grant_expect( $one[0] === bds_featured_billing_single_item_context( $one ), 'exactly one Featured line becomes the order context' );
grant_expect( array() === bds_featured_billing_single_item_context( array() ), 'an order with no Featured SKU has no grant context' );
grant_expect( array() === bds_featured_billing_single_item_context( array( $one[0], $one[0] ) ), 'two Featured lines fail closed' );
grant_expect( true === bds_featured_billing_price_matches_context( $one[0] ), 'quantity 1 at the catalog monthly matches' );
$qty = $one[0];
$qty['quantity'] = 2;
grant_expect( false === bds_featured_billing_price_matches_context( $qty ), 'quantity 2 does not match a single monthly grant' );
$undercut = $one[0];
$undercut['subtotal'] = '8.00';
grant_expect( false === bds_featured_billing_price_matches_context( $undercut ), 'a discounted subtotal does not match the catalog monthly' );

$geo  = array( 'country' => 'MX', 'lat' => 20.63, 'lng' => -87.07 );
$now  = 1700000000;
$end  = $now + 86400;
$ctx  = $one[0];
grant_expect( 'unpaid_or_inactive' === bds_featured_billing_grant_block_reason( false, 99, 'active', true, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'an unpaid order cannot activate a grant' );
grant_expect( false === bds_featured_billing_grant_may_activate( false, 99, 'active', true, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'is_paid false keeps the grant closed' );
grant_expect( 'unpaid_or_inactive' === bds_featured_billing_grant_block_reason( true, 99, 'on-hold', true, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'an inactive subscription cannot activate a grant' );
grant_expect( 'unpaid_or_inactive' === bds_featured_billing_grant_block_reason( true, 0, 'active', true, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'a missing subscription id cannot activate a grant' );
grant_expect( 'ownership_or_sku' === bds_featured_billing_grant_block_reason( true, 99, 'active', false, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'an unlinked order cannot activate a grant' );
grant_expect( 'ownership_or_sku' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, array(), 1, 'month', 42, true, $geo, $end, $now ), 'a missing Featured SKU cannot activate a grant' );
grant_expect( 'ownership_or_sku' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, $ctx, 1, 'year', 42, true, $geo, $end, $now ), 'a non-monthly subscription cannot activate a grant' );
grant_expect( 'ownership_or_sku' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, $ctx, 1, 'month', 41, true, $geo, $end, $now ), 'a payer who is not the listing owner cannot activate a grant' );
grant_expect( 'ownership_or_sku' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, $ctx, 1, 'month', 42, false, $geo, $end, $now ), 'an unowned listing cannot activate a grant' );
grant_expect( 'missing_geo' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, $ctx, 1, 'month', 42, true, array(), $end, $now ), 'missing listing geography cannot activate a grant' );
grant_expect( 'stale_period' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, $ctx, 1, 'month', 42, true, $geo, $now, $now ), 'a next-payment date that is not in the future cannot activate a grant' );
grant_expect( '' === bds_featured_billing_grant_block_reason( true, 99, 'active', true, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'a paid monthly allowlisted SKU with owner, listing, and geo may activate' );
grant_expect( true === bds_featured_billing_grant_may_activate( true, 99, 'active', true, $ctx, 1, 'month', 42, true, $geo, $end, $now ), 'grant_may_activate is true only on the paid allowlisted path' );

$src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/backend/bds-featured-billing-adapter-mu.php' );
grant_expect( false !== strpos( $src, "remove_action( 'woocommerce_order_status_processing', 'bds_feat_activate_paid_order'" ), 'legacy processing activation is disabled' );
grant_expect( false !== strpos( $src, "remove_action( 'woocommerce_order_status_completed', 'bds_feat_activate_paid_order'" ), 'legacy completed activation is disabled' );
grant_expect( false === strpos( $src, 'sk_live' ) && false === strpos( $src, 'pk_live' ), 'the adapter does not hardcode a Stripe key' );
grant_expect( '0.1.2' === BDS_FEATURED_BILLING_VERSION, 'featured billing adapter stamps 0.1.2' );
grant_expect( ! defined( 'BDS_FORMWISE_EXECUTION_KEY' ), 'tests do not invent a FormWise key' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "featured grant tests passed\n";
