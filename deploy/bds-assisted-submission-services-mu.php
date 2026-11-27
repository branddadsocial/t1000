<?php
/**
 * Plugin Name: BrandDad Assisted Submission Services
 * Description: Defines the bounded submission scope and WooCommerce fulfillment metadata for BrandDad's one-time submission services.
 * Version: 0.2.2
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_ASSISTED_SUBMISSION_VERSION', '0.2.2' );

/**
 * Public scope is allowlisted here; prices and service identity never come from the browser.
 *
 * @return array<string,array<string,mixed>>
 */
function bds_submit_services() {
	return array(
		'branddad-ai-review-dispute-assistant' => array(
			'name'        => 'Airbnb Review Appeal — One Assisted Submission',
			'price'       => '$9.99 one time',
			'mode'        => 'customer_assisted',
			'short'       => 'BrandDad prepares one policy-based review-removal request and assists while you submit it from your eligible Airbnb account. You stay signed in and approve the final submission. Airbnb decides; removal is not guaranteed.',
			'included'    => array(
				'One Airbnb review-removal request',
				'Policy and evidence review using facts you can verify',
				'Long and short request versions plus a public-reply fallback',
				'One assisted submission session; you stay signed in and perform the final submit action',
			),
			'limitations' => 'Never send a password, government ID, booking ID, medical information, or unrelated private information. Airbnb makes the final decision.',
		),
		'ai-imdb-submission-pack' => array(
			'name'        => 'IMDb Submission Service — One Contribution',
			'price'       => '$29.99 one time',
			'mode'        => 'branddad_submits',
			'short'       => 'Send your accurate credits and public source links. BrandDad prepares and submits one eligible IMDb contribution after you approve the facts. IMDb decides; acceptance, a page, or a credit is not guaranteed.',
			'included'    => array(
				'One eligible IMDb contribution',
				'Organization of accurate credits and verifiable public sources',
				'Customer approval before submission',
				'One evidence correction for the same contribution if IMDb requests clarification',
			),
			'limitations' => 'IMDb controls eligibility and the final decision. BrandDad cannot guarantee acceptance, a page, or a credit.',
		),
		'ai-billboard-creative-pack' => array(
			'name'        => 'Billboard Vendor Submission — One Inquiry',
			'price'       => '$49.99 one time',
			'mode'        => 'branddad_submits',
			'short'       => 'BrandDad prepares your short billboard concept and submits one customer-approved availability and pricing inquiry to a selected screen vendor. Screen inventory, production, and media-buy costs are separate.',
			'included'    => array(
				'One focused billboard concept with 8-second and 15-second copy',
				'One approved vendor inquiry',
				'Customer approval of the vendor and message before submission',
				'Any vendor response or reference BrandDad receives',
			),
			'limitations' => 'This price does not include screen inventory, production, media buying, or placement. Availability, price, and placement are not guaranteed.',
		),
		'ai-press-release-pack' => array(
			'name'        => 'Press Release Submission — One Submission Round',
			'price'       => '$39.99 one time',
			'mode'        => 'branddad_submits',
			'short'       => 'BrandDad writes one factual press release and completes one customer-approved submission round to a mutually approved no-fee destination or a small targeted media-pitch list. Paid wire fees are separate.',
			'included'    => array(
				'One factual press release and short media pitch',
				'One customer-approved submission round',
				'One mutually approved no-fee destination or a small targeted media-pitch list',
				'Any submission confirmation or reference BrandDad receives',
			),
			'limitations' => 'Paid wire distribution and placement fees are separate. Publication, pickup, coverage, ranking, traffic, and sales are not guaranteed.',
		),
	);
}

/** Return one allowlisted service or null. */
function bds_submit_service( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$services = bds_submit_services();
	return isset( $services[ $slug ] ) ? $services[ $slug ] : null;
}

/** Keep the public product heading consistent with the purchased submission scope. */
function bds_submit_product_title( $title, $post_id = 0 ) {
	if ( is_admin() || ! $post_id || 'product' !== (string) get_post_type( $post_id ) ) {
		return $title;
	}
	$service = bds_submit_service( (string) get_post_field( 'post_name', $post_id ) );
	return is_array( $service ) ? $service['name'] : $title;
}
add_filter( 'the_title', 'bds_submit_product_title', PHP_INT_MAX, 2 );

/** Render bounded product copy with no external-action promises beyond the approved scope. */
function bds_submit_product_html( array $service, $short = false ) {
	if ( $short ) {
		return '<p>' . esc_html( $service['short'] ) . '</p>';
	}
	$html  = '<div class="bds-submission-product" data-bds-submission-version="' . esc_attr( BDS_ASSISTED_SUBMISSION_VERSION ) . '">';
	$html .= '<p><strong>' . esc_html( $service['price'] . ' for one submission service.' ) . '</strong></p>';
	$html .= '<p>' . esc_html( $service['short'] ) . '</p>';
	$html .= '<h2>' . esc_html__( 'What is included', 'branddad' ) . '</h2><ul>';
	foreach ( (array) $service['included'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul><h2>' . esc_html__( 'How it works', 'branddad' ) . '</h2><ol>';
	$html .= '<li>' . esc_html__( 'Pay once and send the truthful facts and public evidence needed for this one case.', 'branddad' ) . '</li>';
	$html .= '<li>' . esc_html__( 'BrandDad prepares the material and checks the destination requirements.', 'branddad' ) . '</li>';
	$html .= '<li>' . esc_html__( 'You review and approve the final material before anything is submitted.', 'branddad' ) . '</li>';
	$html .= '<li>' . esc_html__( 'BrandDad completes the included submission and records any confirmation available. Airbnb uses an assisted session in which you perform the final submit action.', 'branddad' ) . '</li>';
	$html .= '</ol><p><strong>' . esc_html( $service['limitations'] ) . '</strong></p>';
	$html .= '<p>' . esc_html__( 'BrandDad never needs your password. Any third-party fee requires separate approval and payment.', 'branddad' ) . '</p></div>';
	return $html;
}

/** Replace the four exact product descriptions, including legacy draft-only wording. */
function bds_submit_product_copy( $content ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $content;
	}
	$product_id = get_queried_object_id();
	$slug       = (string) get_post_field( 'post_name', $product_id );
	$service    = bds_submit_service( $slug );
	if ( ! is_array( $service ) ) {
		return $content;
	}
	return bds_submit_product_html( $service, 'woocommerce_short_description' === current_filter() );
}
add_filter( 'the_content', 'bds_submit_product_copy', PHP_INT_MAX );
add_filter( 'woocommerce_short_description', 'bds_submit_product_copy', PHP_INT_MAX );

/**
 * Correct cards rendered by older homepage/services plugins during the transition.
 * Exact text replacement keeps unrelated content untouched.
 */
function bds_submit_rewrite_legacy_copy( $content ) {
	$replacements = array(
		'AI Review Appeal — One Case' => 'Airbnb Review Appeal — One Assisted Submission',
		'Pay once. Answer a short form. Receive a policy-based appeal, a short version, and a public reply to copy and send yourself. Removal is not guaranteed.' => 'Pay once. Send the review and evidence. BrandDad prepares the policy-based request and assists while you submit it from your eligible Airbnb account. You approve the final submission. Airbnb decides; removal is not guaranteed.',
		'AI IMDb Submission Pack' => 'IMDb Submission Service — One Contribution',
		'Pay once. Send your credits and links. Receive a bio, credits list, and the exact text to paste into IMDb. IMDb decides; a listing is not guaranteed.' => 'Pay once. Send accurate credits and public source links. BrandDad prepares and submits one eligible IMDb contribution after you approve the facts. IMDb decides; acceptance is not guaranteed.',
		'AI Billboard Creative Pack' => 'Billboard Vendor Submission — One Inquiry',
		'Pay once. Receive 8-second and 15-second scripts, on-screen copy, and an outreach email for a screen vendor. This does not buy Times Square inventory.' => 'Pay once. BrandDad prepares the short creative and submits one approved availability and pricing inquiry to a selected screen vendor. Screen inventory and media-buy costs are separate.',
		'AI Press Release Pack' => 'Press Release Submission — One Submission Round',
		'Pay once. Describe the announcement. Receive a press release and media pitch emails to send yourself. This is writing help, not a wire distribution buy.' => 'Pay once. BrandDad writes one factual release and completes one approved submission round. Paid wire distribution and placement fees are separate; coverage is not guaranteed.',
		'Buy this AI service' => 'Buy this submission service',
		'You submit the message yourself. Airbnb makes the final decision, and removal is not guaranteed.' => 'BrandDad assists while you submit from your eligible Airbnb account. You stay signed in and perform the final submit action. Airbnb makes the final decision, and removal is not guaranteed.',
		'BrandDad prepares the message; you review and submit it.' => 'BrandDad prepares the request and assists with submission; you stay signed in, approve it, and perform the final submit action.',
	);
	return str_replace( array_keys( $replacements ), array_values( $replacements ), (string) $content );
}
add_filter( 'the_content', 'bds_submit_rewrite_legacy_copy', PHP_INT_MAX - 1 );

/**
 * Repair the legacy homepage cards that are injected after the_content filters.
 * All matches and destinations are exact, server-owned constants.
 */
function bds_submit_home_legacy_patch() {
	if ( is_admin() || ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
		return;
	}
	$copy = array(
		'AI Review Appeal — One Case' => 'Airbnb Review Appeal — One Assisted Submission',
		'Pay once. Answer a short form. Receive a policy-based appeal, a short version, and a public reply to copy and send yourself. Removal is not guaranteed.' => 'Pay once. Send the review and evidence. BrandDad prepares the policy-based request and assists while you submit it from your eligible Airbnb account. You approve the final submission. Airbnb decides; removal is not guaranteed.',
		'AI IMDb Submission Pack' => 'IMDb Submission Service — One Contribution',
		'Pay once. Send your credits and links. Receive a bio, credits list, and the exact text to paste into IMDb. IMDb decides; a listing is not guaranteed.' => 'Pay once. Send accurate credits and public source links. BrandDad prepares and submits one eligible IMDb contribution after you approve the facts. IMDb decides; acceptance is not guaranteed.',
		'AI Billboard Creative Pack' => 'Billboard Vendor Submission — One Inquiry',
		'Pay once. Receive 8-second and 15-second scripts, on-screen copy, and an outreach email for a screen vendor. This does not buy Times Square inventory.' => 'Pay once. BrandDad prepares the short creative and submits one approved availability and pricing inquiry to a selected screen vendor. Screen inventory and media-buy costs are separate.',
		'AI Press Release Pack' => 'Press Release Submission — One Submission Round',
		'Pay once. Describe the announcement. Receive a press release and media pitch emails to send yourself. This is writing help, not a wire distribution buy.' => 'Pay once. BrandDad writes one factual release and completes one approved submission round. Paid wire distribution and placement fees are separate; coverage is not guaranteed.',
		'Buy this AI service' => 'Buy this submission service',
	);
	?>
	<script id="bds-assisted-submission-home-patch" data-version="<?php echo esc_attr( BDS_ASSISTED_SUBMISSION_VERSION ); ?>">
	(function(){
		var copy=<?php echo wp_json_encode( $copy ); ?>;
		function patch(){
			var root=document.getElementById('branddad-ai-help');
			if(!root)return;
			var walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);
			var node;
			while((node=walker.nextNode())){
				Object.keys(copy).forEach(function(from){
					if(node.nodeValue.indexOf(from)!==-1)node.nodeValue=node.nodeValue.split(from).join(copy[from]);
				});
			}
			root.setAttribute('data-bds-submission-copy',<?php echo wp_json_encode( BDS_ASSISTED_SUBMISSION_VERSION ); ?>);
		}
		if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',patch,{once:true});else patch();
		setTimeout(patch,500);
		setTimeout(patch,1500);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bds_submit_home_legacy_patch', PHP_INT_MAX );

/** Store a bounded, immutable fulfillment contract on each eligible order line. */
function bds_submit_order_line_item( $item, $cart_item_key, $values, $order ) {
	$product = isset( $values['data'] ) && is_object( $values['data'] ) ? $values['data'] : null;
	if ( ! $product || ! is_callable( array( $product, 'get_slug' ) ) ) {
		return;
	}
	$slug    = sanitize_title( (string) $product->get_slug() );
	$service = bds_submit_service( $slug );
	if ( ! is_array( $service ) || ! is_object( $item ) || ! is_callable( array( $item, 'add_meta_data' ) ) ) {
		return;
	}
	$item->add_meta_data( '_bds_submission_service_slug', $slug, true );
	$item->add_meta_data( '_bds_submission_mode', sanitize_key( (string) $service['mode'] ), true );
	$item->add_meta_data( '_bds_submission_status', 'awaiting_customer_details', true );
	$item->add_meta_data( '_bds_customer_approval_required', 'yes', true );
	$item->add_meta_data( '_bds_external_fee_included', 'no', true );
}
add_action( 'woocommerce_checkout_create_order_line_item', 'bds_submit_order_line_item', 10, 4 );

/** Resolve an order line to a server-owned product slug. */
function bds_submit_order_item_slug( $item ) {
	if ( ! is_object( $item ) ) {
		return '';
	}
	if ( is_callable( array( $item, 'get_meta' ) ) ) {
		$stored = sanitize_title( (string) $item->get_meta( '_bds_submission_service_slug', true ) );
		if ( '' !== $stored ) {
			return $stored;
		}
	}
	if ( ! is_callable( array( $item, 'get_product' ) ) ) {
		return '';
	}
	$product = $item->get_product();
	return is_object( $product ) && is_callable( array( $product, 'get_slug' ) )
		? sanitize_title( (string) $product->get_slug() )
		: '';
}

/**
 * Return the paid fulfillment work represented by an order.
 *
 * Done-for-You remains quote-first. Its task is eligible only when staff created
 * the Woo order in admin after agreeing the written scope; browser checkout can
 * never turn that quote-only catalog record into a task.
 *
 * @return array<string,string> Slug => task label.
 */
function bds_submit_order_fulfillment_services( $order ) {
	if ( ! is_object( $order ) || ! is_callable( array( $order, 'get_items' ) ) ) {
		return array();
	}
	$services    = bds_submit_services();
	$created_via = is_callable( array( $order, 'get_created_via' ) ) ? sanitize_key( (string) $order->get_created_via() ) : '';
	$found       = array();
	foreach ( (array) $order->get_items() as $item ) {
		$slug = bds_submit_order_item_slug( $item );
		if ( isset( $services[ $slug ] ) ) {
			$found[ $slug ] = (string) $services[ $slug ]['name'];
			continue;
		}
		if ( 'done-for-you-ai-management' === $slug && 'admin' === $created_via ) {
			$found[ $slug ] = 'Done-for-You AI Management — accepted written scope';
		}
	}
	return $found;
}

/** A durable, HPOS-independent idempotency claim for one task per paid order. */
function bds_submit_staff_task_claim_key( $order_id ) {
	return 'bds_submit_task_' . substr( hash( 'sha256', 'v1|order|' . absint( $order_id ) ), 0, 40 );
}

/**
 * Create one private staff fulfillment task only after Woo confirms payment.
 *
 * The persistent option claim is atomic in WordPress and remains after success,
 * so duplicate gateway notifications and overlapping status hooks cannot create
 * a second note. Order metadata stores the resulting note id for staff/audits.
 */
function bds_submit_maybe_create_paid_staff_task( $order_or_id ) {
	$order = is_object( $order_or_id ) ? $order_or_id : ( function_exists( 'wc_get_order' ) ? wc_get_order( absint( $order_or_id ) ) : null );
	if ( ! is_object( $order )
		|| ! is_callable( array( $order, 'get_id' ) )
		|| ! is_callable( array( $order, 'is_paid' ) )
		|| ! is_callable( array( $order, 'add_order_note' ) )
		|| ! is_callable( array( $order, 'get_meta' ) )
		|| ! is_callable( array( $order, 'update_meta_data' ) )
		|| ! is_callable( array( $order, 'save' ) )
		|| ! $order->is_paid() ) {
		return false;
	}

	$order_id = absint( $order->get_id() );
	$services = bds_submit_order_fulfillment_services( $order );
	if ( ! $order_id || empty( $services ) || '' !== (string) $order->get_meta( '_bds_submission_staff_task_note_id', true ) ) {
		return false;
	}

	$claim_key = bds_submit_staff_task_claim_key( $order_id );
	if ( ! add_option( $claim_key, gmdate( DATE_ATOM ), '', false ) ) {
		return false;
	}

	$note = 'BrandDad paid fulfillment task: ' . implode( '; ', array_values( $services ) ) . '. Payment is confirmed. ';
	if ( isset( $services['done-for-you-ai-management'] ) ) {
		$note .= 'Verify the accepted written scope, complete onboarding, and schedule only the agreed work. ';
	}
	if ( count( array_intersect_key( $services, bds_submit_services() ) ) > 0 ) {
		$note .= 'Request only the necessary facts and public evidence, prepare the submission, obtain customer approval, then submit once within the purchased scope. Never request a password. Third-party fees are not included.';
	}

	$note_id = $order->add_order_note( trim( $note ), false, false );
	if ( ! $note_id ) {
		delete_option( $claim_key );
		return false;
	}

	$order->update_meta_data( '_bds_submission_staff_task_note_id', absint( $note_id ) );
	$order->update_meta_data( '_bds_submission_staff_task_created_at', gmdate( DATE_ATOM ) );
	$order->update_meta_data( '_bds_submission_staff_task_version', BDS_ASSISTED_SUBMISSION_VERSION );
	$order->save();
	do_action( 'bds_submission_staff_task_created', $order_id, absint( $note_id ), array_keys( $services ) );
	return true;
}

add_action( 'woocommerce_payment_complete', 'bds_submit_maybe_create_paid_staff_task', 20, 1 );
add_action( 'woocommerce_order_status_processing', 'bds_submit_maybe_create_paid_staff_task', 20, 1 );
add_action( 'woocommerce_order_status_completed', 'bds_submit_maybe_create_paid_staff_task', 20, 1 );
