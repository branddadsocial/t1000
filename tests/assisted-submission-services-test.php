<?php
define( 'ABSPATH', __DIR__ );

function add_filter() {}
$submit_actions = array();
function add_action( $hook, $callback ) { global $submit_actions; $submit_actions[ $hook ] = $callback; }
function sanitize_title( $value ) { return strtolower( preg_replace( '/[^a-z0-9-]+/i', '-', trim( (string) $value ) ) ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]+/i', '', (string) $value ) ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $value ) { return (string) $value; }
function esc_attr( $value ) { return esc_html( $value ); }
function is_product() { return false; }
function absint( $value ) { return abs( (int) $value ); }
function do_action() {}

$submit_options = array();
function add_option( $key, $value ) { global $submit_options; if ( array_key_exists( $key, $submit_options ) ) { return false; } $submit_options[ $key ] = $value; return true; }
function delete_option( $key ) { global $submit_options; unset( $submit_options[ $key ] ); return true; }

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
	private $product;
	public function __construct( $product = null ) { $this->product = $product; }
	public function add_meta_data( $key, $value, $unique = false ) { $this->meta[ $key ] = $value; }
	public function get_meta( $key, $single = true ) { return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : ''; }
	public function get_product() { return $this->product; }
}

class BdsSubmitTestOrder {
	public $items = array();
	public $notes = array();
	public $meta = array();
	public $paid = false;
	public $created_via = 'checkout';
	private $id;
	public function __construct( $id = 1001 ) { $this->id = $id; }
	public function get_id() { return $this->id; }
	public function get_items() { return $this->items; }
	public function add_order_note( $note ) { $this->notes[] = $note; return count( $this->notes ); }
	public function is_paid() { return $this->paid; }
	public function get_created_via() { return $this->created_via; }
	public function get_meta( $key, $single = true ) { return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : ''; }
	public function update_meta_data( $key, $value ) { $this->meta[ $key ] = $value; }
	public function save() { return $this->id; }
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

$order = new BdsSubmitTestOrder( 1001 );
$order->items[] = $item;
submit_expect( false === bds_submit_maybe_create_paid_staff_task( $order ), 'unpaid checkout creates no staff task' );
submit_expect( 0 === count( $order->notes ), 'unpaid order contains no fulfillment note' );
submit_expect( '' === (string) $order->get_meta( '_bds_submission_staff_task_note_id', true ), 'unpaid order has no task marker' );

$order->paid = true;
submit_expect( true === bds_submit_maybe_create_paid_staff_task( $order ), 'confirmed payment creates the staff task' );
submit_expect( 1 === count( $order->notes ), 'confirmed payment creates exactly one fulfillment note' );
submit_expect( false !== strpos( $order->notes[0], 'Never request a password' ), 'staff note forbids password collection' );
submit_expect( 1 === absint( $order->get_meta( '_bds_submission_staff_task_note_id', true ) ), 'paid task note id is recorded' );
submit_expect( false === bds_submit_maybe_create_paid_staff_task( $order ), 'duplicate payment notification is idempotent' );
submit_expect( 1 === count( $order->notes ), 'duplicate payment notification creates no second task' );

$manual_quote = new BdsSubmitTestOrder( 1002 );
$manual_quote->paid = true;
$manual_quote->created_via = 'admin';
$manual_quote->items[] = new BdsSubmitTestItem( new BdsSubmitTestProduct( 'done-for-you-ai-management' ) );
submit_expect( true === bds_submit_maybe_create_paid_staff_task( $manual_quote ), 'paid admin order for accepted Done-for-You quote creates a task' );
submit_expect( 1 === count( $manual_quote->notes ), 'accepted quote produces one paid fulfillment task' );
submit_expect( false !== strpos( $manual_quote->notes[0], 'accepted written scope' ), 'quote task names the accepted scope boundary' );

$public_quote = new BdsSubmitTestOrder( 1003 );
$public_quote->paid = true;
$public_quote->created_via = 'checkout';
$public_quote->items[] = new BdsSubmitTestItem( new BdsSubmitTestProduct( 'done-for-you-ai-management' ) );
submit_expect( false === bds_submit_maybe_create_paid_staff_task( $public_quote ), 'browser checkout cannot activate quote-only fulfillment' );
submit_expect( 0 === count( $public_quote->notes ), 'quote-only browser order creates no staff task' );

$service_order_id = 2000;
foreach ( array_keys( $services ) as $service_slug ) {
	$service_order_id++;
	$service_item = new BdsSubmitTestItem( new BdsSubmitTestProduct( $service_slug ) );
	$service_order = new BdsSubmitTestOrder( $service_order_id );
	$service_order->items[] = $service_item;
	submit_expect( false === bds_submit_maybe_create_paid_staff_task( $service_order ), $service_slug . ' creates no task before payment' );
	$service_order->paid = true;
	submit_expect( true === bds_submit_maybe_create_paid_staff_task( $service_order ), $service_slug . ' creates one task after payment' );
	submit_expect( 1 === count( $service_order->notes ), $service_slug . ' has exactly one paid task' );
	submit_expect( false !== strpos( $service_order->notes[0], $services[ $service_slug ]['name'] ), $service_slug . ' task names the purchased service' );
	submit_expect( false === bds_submit_maybe_create_paid_staff_task( $service_order ), $service_slug . ' ignores a duplicate paid event' );
	submit_expect( 1 === count( $service_order->notes ), $service_slug . ' remains exactly one task after replay' );
}

submit_expect( ! isset( $submit_actions['woocommerce_checkout_order_created'] ), 'checkout creation is not a fulfillment trigger' );
submit_expect( 'bds_submit_maybe_create_paid_staff_task' === $submit_actions['woocommerce_payment_complete'], 'payment completion is a fulfillment trigger' );
submit_expect( 'bds_submit_maybe_create_paid_staff_task' === $submit_actions['woocommerce_order_status_processing'], 'paid processing status is a recovery trigger' );
submit_expect( 'bds_submit_maybe_create_paid_staff_task' === $submit_actions['woocommerce_order_status_completed'], 'paid completed status is a recovery trigger' );

if ( $failed ) {
	fwrite( STDERR, "{$failed} assisted submission tests failed\n" );
	exit( 1 );
}

echo "assisted submission tests passed\n";
