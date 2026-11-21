<?php
/**
 * Plugin Name: BrandDad Conversion Events
 * Description: Counts a few named browser clicks. It does not record a person, a payment, or a page address.
 * Version: 0.1.1
 * Author: BrandDad
 *
 * Ingestion: one event name from a fixed list. Extra fields are ignored.
 * Counts live in one option. A network address is used only for a short rate limit and is not stored.
 *
 * @package BrandDad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $name Event name.
 * @return bool
 */
function bds_conv_allow( $name ) {
	return in_array(
		(string) $name,
		array(
			'page_view',
			'primary_cta_click',
			'sales_assistant_engaged',
			'signup_started',
		),
		true
	);
}

/**
 * @return void
 */
function bds_conv_record() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bds_conv_event' ) ) {
		wp_send_json_error( array( 'error' => 'nonce' ), 403 );
	}
	$name = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
	if ( ! bds_conv_allow( $name ) ) {
		wp_send_json_error( array( 'error' => 'event' ), 400 );
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$key = 'bds_cnv_' . substr( hash( 'sha256', $ip . '|' . wp_salt( 'nonce' ) ), 0, 20 );
	$n   = (int) get_transient( $key );
	if ( $n >= 30 ) {
		wp_send_json_success( array( 'ignored' => 1 ) );
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	$counts = get_option( 'bds_conv_counts_v1', array() );
	if ( ! is_array( $counts ) ) {
		$counts = array();
	}
	$counts[ $name ] = (int) ( $counts[ $name ] ?? 0 ) + 1;
	update_option( 'bds_conv_counts_v1', $counts, false );
	wp_send_json_success( array( 'ok' => 1 ) );
}
add_action( 'wp_ajax_bds_conv_event', 'bds_conv_record' );
add_action( 'wp_ajax_nopriv_bds_conv_event', 'bds_conv_record' );

/**
 * @return void
 */
function bds_conv_footer() {
	if ( is_admin() ) {
		return;
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	if ( ! in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true ) ) {
		return;
	}
	$cfg = array(
		'url'   => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'bds_conv_event' ),
	);
	echo '<script id="bds-conv-events">window.BDS_CONV=' . wp_json_encode( $cfg ) . ';';
	echo '(function(){var c=window.BDS_CONV||{};function send(name){if(!c.url)return;var b=new URLSearchParams();b.set("action","bds_conv_event");b.set("nonce",c.nonce||"");b.set("event",name);if(navigator.sendBeacon){navigator.sendBeacon(c.url,b);}else{fetch(c.url,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:b,credentials:"same-origin",keepalive:true});}}send("page_view");document.addEventListener("click",function(e){var a=e.target&&e.target.closest?e.target.closest("a"):null;if(!a)return;var href=a.getAttribute("href")||"";var cls=a.className||"";var name="";if(a.closest&&a.closest("#bds-sales-help")){name="sales_assistant_engaged";}else if(/registration|add-listing|\\/claim/.test(href)){name="signup_started";}else if(/\\bbdai-button\\b|\\bbds-feat-buy\\b|\\bbds-hcta__btn\\b|\\bbds-wah__btn\\b|\\bbds-dtd__btn--list\\b|\\bbds-rev-button\\b/.test(cls)||(a.getAttribute&&a.getAttribute("data-bds-home-cta"))){name="primary_cta_click";}if(name){send(name);}},true);})();</script>';
}
add_action( 'wp_footer', 'bds_conv_footer', 52 );

/**
 * @return void
 */
function bds_conv_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$counts = get_option( 'bds_conv_counts_v1', array() );
	if ( ! is_array( $counts ) ) {
		$counts = array();
	}
	echo '<div class="wrap"><h1>Conversion clicks</h1>';
	echo '<p>These are browser clicks. They are not payments, trials, or new accounts.</p><ul>';
	foreach ( array( 'page_view', 'primary_cta_click', 'sales_assistant_engaged', 'signup_started' ) as $name ) {
		echo '<li>' . esc_html( $name ) . ': ' . esc_html( (string) (int) ( $counts[ $name ] ?? 0 ) ) . '</li>';
	}
	echo '</ul></div>';
}

/**
 * @return void
 */
function bds_conv_admin_menu() {
	add_management_page( 'Conversion clicks', 'Conversion clicks', 'manage_options', 'bds-conv-events', 'bds_conv_admin' );
}
add_action( 'admin_menu', 'bds_conv_admin_menu' );
