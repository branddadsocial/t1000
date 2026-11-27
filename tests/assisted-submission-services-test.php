<?php
define( 'ABSPATH', __DIR__ );

function add_filter() {}
function add_action() {}
function sanitize_title( $value ) { return strtolower( preg_replace( '/[^a-z0-9-]+/i', '-', trim( (string) $value ) ) ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]+/i', '', (string) $value ) ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $value ) { return (string) $value; }
function esc_attr( $value ) { return esc_html( $value ); }
function is_product() { return false; }

require dirname( __DIR__ ) . '/deploy/bds-assisted-submission-services-mu.php';

$failed = 0;
function submit_expect( $condition, $message ) {
	global $failed;
	if ( ! $condition ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

$services = bds_submit_services();
submit_expect( 4 === count( $services ), 'four bounded submission services exist' );
submit_expect( '$9.99 one time' === $services['branddad-ai-review-dispute-assistant']['price'], 'Airbnb price is preserved' );
submit_expect( '$29.99 one time' === $services['branddad-ai-imdb-submission-pack']['price'], 'IMDb price is preserved' );
submit_expect( '$49.99 one time' === $services['branddad-ai-billboard-creative-pack']['price'], 'billboard price is preserved' );
submit_expect( '$39.99 one time' === $services['branddad-ai-press-release-pack']['price'], 'press price is preserved' );
submit_expect( 'customer_assisted' === $services['branddad-ai-review-dispute-assistant']['mode'], 'Airbnb requires an eligible customer-assisted final submission' );
submit_expect( 'branddad_submits' === $services['branddad-ai-imdb-submission-pack']['mode'], 'BrandDad submits IMDb contribution' );

foreach ( $services as $slug => $service ) {
	$html = bds_submit_product_html( $service );
	submit_expect( false !== stripos( $html, 'submit' ), $slug . ' product copy includes submission' );
	submit_expect( false !== stripos( $html, 'approve' ), $slug . ' product copy requires approval' );
	submit_expect( false !== stripos( $html, 'not guaranteed' ), $slug . ' product copy avoids an outcome guarantee' );
	submit_expect( false === stripos( $html, 'send yourself' ), $slug . ' product copy is not draft-only' );
	submit_expect( false === stripos( $html, 'copy and paste yourself' ), $slug . ' product copy is not self-submission copy' );
}

$legacy = '<h3>AI Press Release Pack</h3><p>Pay once. Describe the announcement. Receive a press release and media pitch emails to send yourself. This is writing help, not a wire distribution buy.</p><a>Buy this AI service</a>';
$fixed  = bds_submit_rewrite_legacy_copy( $legacy );
submit_expect( false !== strpos( $fixed, 'Press Release Submission — One Submission Round' ), 'legacy press title is corrected' );
submit_expect( false !== strpos( $fixed, 'completes one approved submission round' ), 'legacy press card promises bounded submission' );
submit_expect( false === strpos( $fixed, 'send yourself' ), 'legacy self-submission wording is removed' );
submit_expect( false !== strpos( $fixed, 'Buy this submission service' ), 'legacy CTA sells the submission service' );

class BdsSubmitTestProduct {
	private $slug;
	public function __construct( $slug ) { $this->slug = $slug; }
	public function get_slug() { return $this->slug; }
}

class BdsSubmitTestItem {
	public $meta = array();
	public function add_meta_data( $key, $value, $unique = false ) { $this->meta[ $key ] = $value; }
	public function get_meta( $key, $single = true ) { return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : ''; }
}

class BdsSubmitTestOrder {
	public $items = array();
	public $notes = array();
	public function get_items() { return $this->items; }
	public function add_order_note( $note ) { $this->notes[] = $note; }
}

$item = new BdsSubmitTestItem();
bds_submit_order_line_item( $item, 'key', array( 'data' => new BdsSubmitTestProduct( 'branddad-ai-imdb-submission-pack' ) ), null );
submit_expect( 'branddad-ai-imdb-submission-pack' === $item->meta['_bds_submission_service_slug'], 'immutable service slug is recorded' );
submit_expect( 'branddad_submits' === $item->meta['_bds_submission_mode'], 'submission mode is recorded' );
submit_expect( 'awaiting_customer_details' === $item->meta['_bds_submission_status'], 'initial fulfillment status is fail-closed' );
submit_expect( 'yes' === $item->meta['_bds_customer_approval_required'], 'customer approval is required' );
submit_expect( 'no' === $item->meta['_bds_external_fee_included'], 'external fees are not silently included' );

$unrelated = new BdsSubmitTestItem();
bds_submit_order_line_item( $unrelated, 'key', array( 'data' => new BdsSubmitTestProduct( 'unrelated-product' ) ), null );
submit_expect( array() === $unrelated->meta, 'unrelated products receive no submission metadata' );

$order = new BdsSubmitTestOrder();
$order->items[] = $item;
bds_submit_order_created( $order );
submit_expect( 1 === count( $order->notes ), 'staff receives one bounded fulfillment instruction' );
submit_expect( false !== strpos( $order->notes[0], 'Never request a password' ), 'staff note forbids password collection' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} assisted submission tests failed\n" );
	exit( 1 );
}

echo "assisted submission tests passed\n";
