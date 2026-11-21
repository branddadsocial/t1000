<?php
/**
 * Plugin Name: BrandDad Follow Consent
 * Description: Saves an email only when the person asks BrandDad to write to them. It does not send mail.
 * Version: 0.1.7
 * Author: BrandDad
 *
 * Ingestion: public form → nonce check → email, consent, and source only.
 * Consent must be the string 1. Email max 120 characters. Unknown fields are ignored.
 * No files, no unserialize, no outbound message. At most 5 saves an hour per network address,
 * and at most 400 saved rows. Admins can read the list. The public response never repeats the email.
 *
 * @package BrandDad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accept a follow-up request. Sending mail is a later shared-backend job.
 *
 * @param string $email   Address.
 * @param string $consent Must be "1".
 * @param string $source  help, trial, listing, or audit.
 * @return array{ok:bool,error?:string,email?:string,source?:string,status?:string}
 */
function bds_follow_decision( $email, $consent, $source ) {
	$email = strtolower( trim( (string) $email ) );
	if ( '1' !== (string) $consent ) {
		return array(
			'ok'    => false,
			'error' => 'consent',
		);
	}
	if ( strlen( $email ) > 120 || ! preg_match( '/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email ) ) {
		return array(
			'ok'    => false,
			'error' => 'email',
		);
	}
	$allowed = array( 'help', 'trial', 'listing', 'audit', 'featured', 'music', 'gift' );
	$source  = strtolower( preg_replace( '/[^a-z]/', '', (string) $source ) );
	if ( ! in_array( $source, $allowed, true ) ) {
		$source = 'help';
	}
	return array(
		'ok'     => true,
		'email'  => $email,
		'source' => $source,
		'status' => 'interested',
	);
}

/**
 * The note Codex can send later. This function does not send mail.
 *
 * @param string $source help, trial, listing, or audit.
 * @return array{subject:string,body:string}
 */
function bds_follow_message( $source ) {
	$notes = array(
		'trial'   => array(
			'subject' => 'Your 7-day Business Essentials trial',
			'body'    => 'AI Business Essentials is $29.99 a month after the 7-day trial. Open the product page and use Start 7-day trial. Directory membership is not included. A Directory listing is free and separate. This does not promise customers. Reply if you want these notes to stop.',
		),
		'listing' => array(
			'subject' => 'Your free Directory listing',
			'body'    => 'A basic Directory listing is free. Add the business, a photo, and a real website. In Mexico, add the business WhatsApp. We review it before it goes live. Pro, Elite, and Featured are optional. Reply if you want these notes to stop.',
		),
		'audit'   => array(
			'subject' => 'Your website check',
			'body'    => 'The free website check explains what to fix. It does not invent rankings or promise customers. When you want help with the work, AI Business Essentials is $29.99 a month with a 7-day trial. Reply if you want these notes to stop.',
		),
		'help'    => array(
			'subject' => 'The next step with BrandDad',
			'body'    => 'A basic Directory listing is free. AI Business Essentials is $29.99 a month after a 7-day trial. Start with the free listing if money is tight. This does not promise customers. Reply if you want these notes to stop.',
		),
		'featured' => array(
			'subject' => 'Featured placement on BrandDad Directory',
			'body'    => 'Featured placement starts at $9.99 a month. It stays inactive until WooCommerce confirms payment. A basic Directory listing is free and separate. This does not invent rankings. Reply if you want these notes to stop.',
		),
		'music'    => array(
			'subject' => 'Your BrandDad Music brief',
			'body'    => 'A music brief does not take payment or start production. BrandDad confirms scope and licensing first. Reply if you want these notes to stop.',
		),
		'gift'     => array(
			'subject' => 'Directory gift cards',
			'body'    => 'Gift cards on the Directory are a marketplace. BrandDad does not take that payment on Social. A basic listing is free. Featured is a separate monthly option. Reply if you want these notes to stop.',
		),
	);
	$source = strtolower( preg_replace( '/[^a-z]/', '', (string) $source ) );
	return isset( $notes[ $source ] ) ? $notes[ $source ] : $notes['help'];
}

/**
 * Page path picks the later email. The path is not stored.
 *
 * @param string $path Request path.
 * @return string
 */
/**
 * Checkout, account, and studio boards already have a next step.
 * A second email-capture box on those surfaces is leftover CX.
 *
 * @param mixed $path Request path.
 * @return bool
 */
function bds_follow_path_ok( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	$top  = strstr( $path, '/', true );
	if ( false === $top ) {
		$top = $path;
	}
	return ! in_array(
		$top,
		array( 'cart', 'checkout', 'my-account', 'account-hub', 'studio-boards' ),
		true
	);
}

function bds_follow_source_for_path( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	if ( false !== strpos( $path, 'ai-business-essentials' ) ) {
		return 'trial';
	}
	if ( false !== strpos( $path, 'add-listing' ) || false !== strpos( $path, 'registration' ) ) {
		return 'listing';
	}
	if ( false !== strpos( $path, 'check-your-website' ) ) {
		return 'audit';
	}
	if ( false !== strpos( $path, 'featured-advertising' ) || false !== strpos( $path, 'featured-' ) ) {
		return 'featured';
	}
	if ( false !== strpos( $path, 'music' ) ) {
		return 'music';
	}
	if ( false !== strpos( $path, 'gift' ) ) {
		return 'gift';
	}
	return 'help';
}

/**
 * @return string
 */
function bds_follow_rate_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return 'bds_flw_' . substr( hash( 'sha256', $ip . '|' . wp_salt( 'nonce' ) ), 0, 20 );
}

/**
 * @return void
 */
function bds_follow_handle() {
	if ( ! isset( $_POST['bds_follow_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_follow_nonce'] ) ), 'bds_follow_consent' ) ) {
		bds_follow_redirect( 'no' );
	}
	$key = bds_follow_rate_key();
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		bds_follow_redirect( 'no' );
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	$row = bds_follow_decision(
		isset( $_POST['bds_follow_email'] ) ? (string) wp_unslash( $_POST['bds_follow_email'] ) : '',
		isset( $_POST['bds_follow_consent'] ) ? (string) wp_unslash( $_POST['bds_follow_consent'] ) : '',
		isset( $_POST['bds_follow_source'] ) ? (string) wp_unslash( $_POST['bds_follow_source'] ) : ''
	);
	if ( empty( $row['ok'] ) ) {
		bds_follow_redirect( 'no' );
	}
	$all = get_option( 'bds_follow_consent_v1', array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}
	$all[ $row['email'] ] = array(
		'email'   => $row['email'],
		'source'  => $row['source'],
		'status'  => 'interested',
		'saved'   => gmdate( 'c' ),
		'consent' => 1,
	);
	if ( count( $all ) > 400 ) {
		$all = array_slice( $all, -400, null, true );
	}
	update_option( 'bds_follow_consent_v1', $all, false );
	bds_follow_redirect( 'saved' );
}

/**
 * @param string $result saved or no.
 * @return void
 */
function bds_follow_return_url() {
	$home = home_url( '/' );
	$back = wp_get_referer();
	if ( ! is_string( $back ) || '' === $back ) {
		return $home;
	}
	$safe = wp_validate_redirect( $back, $home );
	if ( ! is_string( $safe ) || '' === $safe ) {
		return $home;
	}
	$path = strtolower( (string) wp_parse_url( $safe, PHP_URL_PATH ) );
	if ( 0 === strpos( $path, '/wp-admin' ) || 0 === strpos( $path, '/wp-login.php' ) ) {
		return $home;
	}
	return $safe;
}

function bds_follow_redirect( $result ) {
	wp_safe_redirect( add_query_arg( 'bds_follow', 'saved' === $result ? 'saved' : 'no', bds_follow_return_url() ) );
	exit;
}
add_action( 'admin_post_nopriv_bds_follow_consent', 'bds_follow_handle' );
add_action( 'admin_post_bds_follow_consent', 'bds_follow_handle' );

/**
 * @return void
 */
function bds_follow_form() {
	if ( is_admin() ) {
		return;
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	if ( ! in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true ) ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! bds_follow_path_ok( $path ) ) {
		return;
	}
	$note = isset( $_GET['bds_follow'] ) ? sanitize_key( wp_unslash( $_GET['bds_follow'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$sw = function_exists( 'bds_lang_swap_attrs' );
	echo '<style id="bds-follow-css">#bds-follow-consent{max-width:720px;margin:18px auto;padding:16px 18px;border-top:1px solid #dbe5ef}#bds-follow-consent input[type="email"]{min-height:44px;padding:8px;width:100%;max-width:420px;box-sizing:border-box}#bds-follow-consent button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 14px;border:0;border-radius:10px;background:#0668d8;color:#fff;font-weight:800}#bds-follow-consent button:focus-visible,#bds-follow-consent input:focus-visible{outline:3px solid #0668d8;outline-offset:2px}</style>';
	echo '<form id="bds-follow-consent" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="bds_follow_consent">';
	wp_nonce_field( 'bds_follow_consent', 'bds_follow_nonce' );
	echo '<p><label for="bds-follow-email"' . ( $sw ? bds_lang_swap_attrs( 'Email me the next step', 'Envíeme el siguiente paso' ) : '' ) . '>Email me the next step</label></p>';
	echo '<p><input id="bds-follow-email" name="bds_follow_email" type="email" maxlength="120" required autocomplete="email"></p>';
	$source = bds_follow_source_for_path( $path );
	echo '<input type="hidden" name="bds_follow_source" value="' . esc_attr( $source ) . '">';
	echo '<p><label><input type="checkbox" name="bds_follow_consent" value="1" required> <span' . ( $sw ? bds_lang_swap_attrs( 'BrandDad may email me about this. I can tell you to stop.', 'BrandDad puede escribirme sobre esto. Puedo pedir que pare.' ) : '' ) . '>BrandDad may email me about this. I can tell you to stop.</span></label></p>';
	echo '<p><button type="submit"' . ( $sw ? bds_lang_swap_attrs( 'Save my email', 'Guardar mi correo' ) : '' ) . '>Save my email</button></p>';
	if ( 'saved' === $note ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			echo bds_lang_pair( 'Saved. BrandDad has not sent a message yet.', 'Guardado. BrandDad aún no ha enviado un mensaje.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<p>Saved. BrandDad has not sent a message yet.</p>';
		}
	} elseif ( 'no' === $note ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			echo bds_lang_pair( 'That email was not saved. Check the box if you want mail.', 'Ese correo no se guardó. Marque la casilla si quiere correo.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<p>That email was not saved. Check the box if you want mail.</p>';
		}
	}
	echo '</form>';
}
add_action( 'wp_footer', 'bds_follow_form', 51 );

/**
 * @return void
 */
function bds_follow_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$all = get_option( 'bds_follow_consent_v1', array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}
	echo '<div class="wrap"><h1>Follow-up consent</h1>';
	echo '<p>These people asked for email. This screen does not send it.</p>';
	foreach ( array( 'help', 'trial', 'listing', 'audit', 'featured', 'music', 'gift' ) as $source ) {
		$note = bds_follow_message( $source );
		echo '<h2>' . esc_html( $note['subject'] ) . '</h2><p>' . esc_html( $note['body'] ) . '</p>';
	}
	echo '<p>' . esc_html( (string) count( $all ) ) . ' saved.</p><ul>';
	foreach ( array_slice( $all, -50, null, true ) as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		echo '<li>' . esc_html( (string) ( $row['email'] ?? '' ) ) . ' · ' . esc_html( (string) ( $row['source'] ?? '' ) ) . ' · ' . esc_html( (string) ( $row['status'] ?? '' ) ) . '</li>';
	}
	echo '</ul></div>';
}

/**
 * @return void
 */
function bds_follow_admin_menu() {
	add_management_page( 'Follow-up consent', 'Follow-up consent', 'manage_options', 'bds-follow-consent', 'bds_follow_admin' );
}
add_action( 'admin_menu', 'bds_follow_admin_menu' );
