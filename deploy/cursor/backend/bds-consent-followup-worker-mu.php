<?php
/**
 * Plugin Name: BrandDad Consent Follow-up Worker
 * Description: Default-off, consent-checked email delivery for the existing BrandDad follow-up list.
 * Version: 0.1.1
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_CONSENT_FOLLOWUP_VERSION', '0.1.1' );

/** Delivery is deliberately unavailable until a verified sender and kill switch are configured. */
function bds_follow_worker_ready() {
	return defined( 'BDS_FOLLOWUP_ENABLED' )
		&& true === BDS_FOLLOWUP_ENABLED
		&& defined( 'BDS_FOLLOWUP_FROM_EMAIL' )
		&& is_email( BDS_FOLLOWUP_FROM_EMAIL );
}

function bds_follow_worker_lock() {
	$key = 'bds_follow_worker_lock';
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	if ( absint( get_option( $key, 0 ) ) < time() - 300 ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

function bds_follow_worker_unlock( $key ) {
	if ( 'bds_follow_worker_lock' === $key ) {
		delete_option( $key );
	}
}

/** Normalize only the four sources accepted by Cursor's consent form. */
function bds_follow_worker_source( $source ) {
	$source = sanitize_key( (string) $source );
	return in_array( $source, array( 'help', 'trial', 'listing', 'audit' ), true ) ? $source : 'help';
}

/** Fresh explicit consent is required immediately before queueing and sending. */
function bds_follow_worker_row_eligible( $row, $now = null ) {
	$now = null === $now ? time() : absint( $now );
	if ( ! is_array( $row ) || 1 !== absint( $row['consent'] ?? 0 ) || 'interested' !== ( $row['status'] ?? '' ) || ! is_email( $row['email'] ?? '' ) ) {
		return false;
	}
	$saved = strtotime( (string) ( $row['saved'] ?? '' ) );
	return false !== $saved && $saved <= $now + 300 && $saved >= $now - ( 30 * DAY_IN_SECONDS );
}

/** Stop sales follow-up after the person already has an active paid AI plan. */
function bds_follow_worker_has_converted( $email ) {
	$user = get_user_by( 'email', sanitize_email( $email ) );
	return $user instanceof WP_User && function_exists( 'bds_ai_exec_active_plan' ) && ! empty( bds_ai_exec_active_plan( $user->ID ) );
}

/** Create an opaque unsubscribe token. Only its SHA-256 hash is retained. */
function bds_follow_worker_ensure_unsubscribe_token( &$row ) {
	if ( ! empty( $row['unsubscribe_hash'] ) && ! empty( $row['unsubscribe_token_pending'] ) ) {
		return (string) $row['unsubscribe_token_pending'];
	}
	$token = bin2hex( random_bytes( 24 ) );
	$row['unsubscribe_hash'] = hash( 'sha256', $token );
	$row['unsubscribe_token_pending'] = $token;
	return $token;
}

/** Update exactly one row while preserving newly submitted rows and opt-outs. */
function bds_follow_worker_update_row( $key, $changes, $remove = array(), $expected_saved = '', $expected_status = '' ) {
	$all = get_option( 'bds_follow_consent_v1', array() );
	if ( ! is_array( $all ) || ! isset( $all[ $key ] ) || ! is_array( $all[ $key ] ) ) {
		return false;
	}
	$current = $all[ $key ];
	if ( $expected_saved && ! hash_equals( (string) ( $current['saved'] ?? '' ), (string) $expected_saved ) ) {
		return false;
	}
	if ( $expected_status && ! hash_equals( (string) ( $current['status'] ?? '' ), (string) $expected_status ) ) {
		return false;
	}
	$next_status = (string) ( $changes['status'] ?? $current['status'] ?? '' );
	if ( ( 0 === absint( $current['consent'] ?? 0 ) || 'unsubscribed' === ( $current['status'] ?? '' ) ) && 'unsubscribed' !== $next_status ) {
		return false;
	}
	$all[ $key ] = array_merge( $current, is_array( $changes ) ? $changes : array() );
	foreach ( (array) $remove as $field ) {
		unset( $all[ $key ][ sanitize_key( $field ) ] );
	}
	return update_option( 'bds_follow_consent_v1', $all, false ) || $all[ $key ] === $current;
}

/** Token is placed in a URL; the stored row contains only its hash after successful delivery. */
function bds_follow_worker_unsubscribe_url( $token ) {
	return add_query_arg(
		array(
			'action' => 'bds_follow_unsubscribe',
			'token'  => rawurlencode( $token ),
		),
		admin_url( 'admin-post.php' )
	);
}

/** Use only server-verified destinations. Empty checkout links are omitted. */
function bds_follow_worker_destination_url( $source ) {
	if ( 'listing' === $source ) {
		return 'https://directory.branddad.social/add-listing/';
	}
	return function_exists( 'bds_ai_commercial_checkout_url' ) ? (string) bds_ai_commercial_checkout_url( 'ai-business-essentials' ) : '';
}

/** Send one consented message. The authoritative copy remains Cursor's bds_follow_message(). */
function bds_follow_worker_send( $email, $source, $unsubscribe_token ) {
	if ( ! bds_follow_worker_ready() || ! function_exists( 'bds_follow_message' ) ) {
		return false;
	}
	$email  = sanitize_email( $email );
	$source = bds_follow_worker_source( $source );
	if ( ! is_email( $email ) ) {
		return false;
	}
	$message = bds_follow_message( $source );
	if ( ! is_array( $message ) || empty( $message['subject'] ) || empty( $message['body'] ) ) {
		return false;
	}
	$destination = bds_follow_worker_destination_url( $source );
	$unsubscribe = bds_follow_worker_unsubscribe_url( $unsubscribe_token );
	$body = sanitize_textarea_field( $message['body'] );
	if ( $destination && wp_http_validate_url( $destination ) ) {
		$body .= "\n\nNext step: " . esc_url_raw( $destination );
	}
	$body .= "\n\nStop these emails: " . esc_url_raw( $unsubscribe );
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'From: BrandDad <' . sanitize_email( BDS_FOLLOWUP_FROM_EMAIL ) . '>',
		'List-Unsubscribe: <' . esc_url_raw( $unsubscribe ) . '>',
		'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
	);
	return wp_mail( $email, sanitize_text_field( $message['subject'] ), $body, $headers );
}

/** Process a small bounded batch; one accepted mail ends this simple sequence. */
function bds_follow_worker_run() {
	if ( ! bds_follow_worker_ready() ) {
		return;
	}
	$lock = bds_follow_worker_lock();
	if ( ! $lock ) {
		return;
	}
	try {
		$all = get_option( 'bds_follow_consent_v1', array() );
		$all = is_array( $all ) ? $all : array();
		$processed = 0;
		$now = time();
		foreach ( $all as $key => $candidate ) {
			if ( $processed >= 20 || ! is_array( $candidate ) ) {
				continue;
			}
			$email = sanitize_email( $candidate['email'] ?? $key );
			if ( 'sending' === ( $candidate['status'] ?? '' ) ) {
				$last_attempt = strtotime( (string) ( $candidate['last_attempt'] ?? '' ) );
				if ( false !== $last_attempt && $last_attempt < $now - HOUR_IN_SECONDS ) {
					bds_follow_worker_update_row( $key, array( 'status' => 'delivery_review', 'stopped_at' => gmdate( DATE_ATOM ) ), array(), (string) ( $candidate['saved'] ?? '' ) );
				}
				continue;
			}
			if ( ! bds_follow_worker_row_eligible( $candidate, $now ) ) {
				if ( 'interested' === ( $candidate['status'] ?? '' ) && 1 === absint( $candidate['consent'] ?? 0 ) ) {
					$saved = strtotime( (string) ( $candidate['saved'] ?? '' ) );
					if ( false === $saved || $saved < $now - ( 30 * DAY_IN_SECONDS ) ) {
						bds_follow_worker_update_row( $key, array( 'status' => 'consent_expired', 'stopped_at' => gmdate( DATE_ATOM ) ), array(), (string) ( $candidate['saved'] ?? '' ) );
					}
				}
				continue;
			}
			if ( bds_follow_worker_has_converted( $email ) ) {
				bds_follow_worker_update_row( $key, array( 'status' => 'converted', 'stopped_at' => gmdate( DATE_ATOM ) ), array(), (string) ( $candidate['saved'] ?? '' ) );
				continue;
			}
			$next_attempt = strtotime( (string) ( $candidate['next_attempt'] ?? '' ) );
			if ( false !== $next_attempt && $next_attempt > $now ) {
				continue;
			}
			$attempts = absint( $candidate['attempts'] ?? 0 );
			if ( $attempts >= 3 ) {
				bds_follow_worker_update_row( $key, array( 'status' => 'failed', 'stopped_at' => gmdate( DATE_ATOM ) ), array(), (string) ( $candidate['saved'] ?? '' ) );
				continue;
			}
			// Re-read consent immediately before the side effect.
			$current = get_option( 'bds_follow_consent_v1', array() );
			$current = is_array( $current ) && isset( $current[ $key ] ) ? $current[ $key ] : null;
			if ( ! bds_follow_worker_row_eligible( $current, time() ) ) {
				continue;
			}
			$delivery = $current;
			$token = bds_follow_worker_ensure_unsubscribe_token( $delivery );
			$prepared = bds_follow_worker_update_row(
				$key,
				array(
					'status'                    => 'sending',
					'attempts'                  => $attempts + 1,
					'last_attempt'              => gmdate( DATE_ATOM ),
					'unsubscribe_hash'          => $delivery['unsubscribe_hash'],
					'unsubscribe_token_pending' => $token,
				),
				array(),
				(string) ( $candidate['saved'] ?? '' ),
				'interested'
			);
			if ( ! $prepared ) {
				continue;
			}
			$sent  = bds_follow_worker_send( $email, bds_follow_worker_source( $candidate['source'] ?? '' ), $token );
			$processed++;
			if ( $sent ) {
				bds_follow_worker_update_row( $key, array( 'status' => 'sent', 'sent_at' => gmdate( DATE_ATOM ) ), array( 'unsubscribe_token_pending', 'next_attempt' ), (string) ( $candidate['saved'] ?? '' ) );
			} else {
				bds_follow_worker_update_row( $key, array( 'status' => 'interested', 'next_attempt' => gmdate( DATE_ATOM, $now + min( DAY_IN_SECONDS, HOUR_IN_SECONDS * ( 2 ** $attempts ) ) ) ), array(), (string) ( $candidate['saved'] ?? '' ) );
			}
		}
	} finally {
		bds_follow_worker_unlock( $lock );
	}
}
add_action( 'bds_follow_worker_run', 'bds_follow_worker_run' );

function bds_follow_worker_schedule() {
	if ( bds_follow_worker_ready() && ! wp_next_scheduled( 'bds_follow_worker_run' ) ) {
		wp_schedule_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'hourly', 'bds_follow_worker_run' );
	}
}
add_action( 'init', 'bds_follow_worker_schedule' );

/** Locate a row by constant-time comparison of opaque token hashes. */
function bds_follow_worker_find_unsubscribe( $token, $all ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{48}$/', $token ) || ! is_array( $all ) ) {
		return '';
	}
	$hash = hash( 'sha256', $token );
	foreach ( $all as $key => $row ) {
		if ( is_array( $row ) && ! empty( $row['unsubscribe_hash'] ) && hash_equals( (string) $row['unsubscribe_hash'], $hash ) ) {
			return (string) $key;
		}
	}
	return '';
}

function bds_follow_worker_unsubscribe_rate_limit() {
	$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$key = 'bds_follow_unsub_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 24 );
	$n   = absint( get_transient( $key ) );
	if ( $n >= 30 ) {
		return false;
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	return true;
}

/** GET shows confirmation; POST or RFC 8058 one-click POST performs the opt-out. */
function bds_follow_worker_unsubscribe() {
	if ( ! bds_follow_worker_unsubscribe_rate_limit() ) {
		wp_die( esc_html__( 'Too many requests. Please try again later.', 'branddad' ), '', array( 'response' => 429 ) );
	}
	$raw_token = isset( $_POST['token'] ) ? $_POST['token'] : ( $_GET['token'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$token = sanitize_text_field( wp_unslash( $raw_token ) );
	$all   = get_option( 'bds_follow_consent_v1', array() );
	$key   = bds_follow_worker_find_unsubscribe( $token, $all );
	if ( '' === $key ) {
		wp_die( esc_html__( 'This unsubscribe link is invalid or has expired.', 'branddad' ), '', array( 'response' => 400 ) );
	}
	$is_post = 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) );
	$one_click = isset( $_POST['List-Unsubscribe'] ) && 'One-Click' === sanitize_text_field( wp_unslash( $_POST['List-Unsubscribe'] ) );
	$confirmed = isset( $_POST['confirm'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['confirm'] ) );
	if ( $is_post && ( $one_click || $confirmed ) ) {
		$updated = bds_follow_worker_update_row(
			$key,
			array( 'status' => 'unsubscribed', 'consent' => 0, 'unsubscribed_at' => gmdate( DATE_ATOM ) ),
			array( 'unsubscribe_hash', 'unsubscribe_token_pending', 'next_attempt' )
		);
		if ( ! $updated ) {
			wp_die( esc_html__( 'Your unsubscribe request could not be saved. Please try again.', 'branddad' ), '', array( 'response' => 503 ) );
		}
		do_action( 'bds_follow_unsubscribed', absint( $all[ $key ]['lead_id'] ?? 0 ) );
		wp_die( esc_html__( 'You are unsubscribed from BrandDad follow-up emails.', 'branddad' ), '', array( 'response' => 200 ) );
	}
	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	$action = add_query_arg( array( 'action' => 'bds_follow_unsubscribe', 'token' => rawurlencode( $token ) ), admin_url( 'admin-post.php' ) );
	?>
	<!doctype html><html><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title><?php esc_html_e( 'Stop BrandDad emails', 'branddad' ); ?></title></head>
	<body><h1><?php esc_html_e( 'Stop BrandDad emails?', 'branddad' ); ?></h1><form method="post" action="<?php echo esc_url( $action ); ?>"><input type="hidden" name="confirm" value="1"><button type="submit"><?php esc_html_e( 'Unsubscribe', 'branddad' ); ?></button></form></body></html>
	<?php
	exit;
}
add_action( 'admin_post_nopriv_bds_follow_unsubscribe', 'bds_follow_worker_unsubscribe' );
add_action( 'admin_post_bds_follow_unsubscribe', 'bds_follow_worker_unsubscribe' );
