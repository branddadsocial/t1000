<?php
/**
 * Plugin Name: BrandDad Cross-Site Account Link
 * Description: Single-use signed account handoff between BrandDad Social and BrandDad Directory.
 * Version: 0.1.2
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_ACCOUNT_LINK_VERSION', '0.1.2' );

function bds_account_link_hosts() {
	return array(
		'branddad.social'           => 'https://branddad.social',
		'directory.branddad.social' => 'https://directory.branddad.social',
	);
}

function bds_account_link_current_host() {
	$actual    = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$candidate = strtolower( sanitize_text_field( (string) apply_filters( 'bds_account_link_current_host_candidate', $actual ) ) );
	$hosts     = bds_account_link_hosts();
	return isset( $hosts[ $candidate ] ) ? $candidate : $actual;
}

function bds_account_link_secret_ready() {
	return defined( 'BDS_ACCOUNT_LINK_SECRET' ) && is_string( BDS_ACCOUNT_LINK_SECRET ) && strlen( BDS_ACCOUNT_LINK_SECRET ) >= 32;
}

/**
 * Cross-site sign-in is safe only after the source site has verified ownership
 * of the email address. The registration system must opt in through this
 * filter after its verification workflow succeeds; the default is fail-closed.
 */
function bds_account_link_email_verified( $user ) {
	if ( ! $user instanceof WP_User ) {
		return false;
	}
	return true === apply_filters( 'bds_account_link_email_verified', false, $user );
}

function bds_account_link_b64url_encode( $value ) {
	return rtrim( strtr( base64_encode( (string) $value ), '+/', '-_' ), '=' );
}

function bds_account_link_b64url_decode( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
		return false;
	}
	$padding = strlen( $value ) % 4;
	if ( $padding ) {
		$value .= str_repeat( '=', 4 - $padding );
	}
	return base64_decode( strtr( $value, '-_', '+/' ), true );
}

/** Build a five-minute, purpose-bound handoff token. */
function bds_account_link_create_token( $user_id, $audience ) {
	$hosts    = bds_account_link_hosts();
	$issuer   = bds_account_link_current_host();
	$audience = strtolower( (string) $audience );
	$user     = get_user_by( 'id', absint( $user_id ) );
	if ( ! bds_account_link_secret_ready() || ! isset( $hosts[ $issuer ], $hosts[ $audience ] ) || $issuer === $audience || ! $user instanceof WP_User || ! is_email( $user->user_email ) || ! bds_account_link_email_verified( $user ) ) {
		return new WP_Error( 'link_unavailable', 'Account linking is not available.' );
	}
	$now = time();
	$payload = array(
		'v'            => 1,
		'purpose'      => 'branddad_account_link',
		'jti'          => wp_generate_uuid4(),
		'iss'          => $issuer,
		'aud'          => $audience,
		'source_uid'   => absint( $user->ID ),
		'email'        => strtolower( sanitize_email( $user->user_email ) ),
		'email_hash'   => hash( 'sha256', strtolower( sanitize_email( $user->user_email ) ) ),
		'display_name' => sanitize_text_field( $user->display_name ),
		'iat'          => $now,
		'exp'          => $now + 300,
	);
	$body = bds_account_link_b64url_encode( wp_json_encode( $payload ) );
	$sig  = bds_account_link_b64url_encode( hash_hmac( 'sha256', $body, BDS_ACCOUNT_LINK_SECRET, true ) );
	return $body . '.' . $sig;
}

/** Verify signature, exact host pair, purpose, timestamps, email, and replay id. */
function bds_account_link_verify_token( $token ) {
	if ( ! bds_account_link_secret_ready() || ! is_string( $token ) || strlen( $token ) > 4096 || 1 !== substr_count( $token, '.' ) ) {
		return new WP_Error( 'invalid_link', 'This account link is invalid.' );
	}
	list( $body, $provided ) = explode( '.', $token, 2 );
	$expected = bds_account_link_b64url_encode( hash_hmac( 'sha256', $body, BDS_ACCOUNT_LINK_SECRET, true ) );
	if ( ! hash_equals( $expected, $provided ) ) {
		return new WP_Error( 'invalid_link', 'This account link is invalid.' );
	}
	$json = bds_account_link_b64url_decode( $body );
	$data = is_string( $json ) ? json_decode( $json, true ) : null;
	$hosts = bds_account_link_hosts();
	$current = bds_account_link_current_host();
	$now = time();
	if ( ! is_array( $data )
		|| 1 !== absint( $data['v'] ?? 0 )
		|| 'branddad_account_link' !== ( $data['purpose'] ?? '' )
		|| ! wp_is_uuid( (string) ( $data['jti'] ?? '' ) )
		|| ! isset( $hosts[ $data['iss'] ?? '' ], $hosts[ $data['aud'] ?? '' ] )
		|| $current !== ( $data['aud'] ?? '' )
		|| $current === ( $data['iss'] ?? '' )
		|| absint( $data['iat'] ?? 0 ) > $now + 30
		|| absint( $data['iat'] ?? 0 ) < $now - 330
		|| absint( $data['exp'] ?? 0 ) < $now
		|| absint( $data['exp'] ?? 0 ) > $now + 330
		|| ! is_email( $data['email'] ?? '' )
		|| ! hash_equals( hash( 'sha256', strtolower( sanitize_email( $data['email'] ) ) ), (string) ( $data['email_hash'] ?? '' ) ) ) {
		return new WP_Error( 'invalid_link', 'This account link is invalid or expired.' );
	}
	return $data;
}

function bds_account_link_audit( $action, $user_id, $source_host ) {
	$rows = get_option( 'bds_account_link_audit_v1', array() );
	$rows = is_array( $rows ) ? $rows : array();
	$rows[] = array(
		'action' => sanitize_key( $action ),
		'user_id' => absint( $user_id ),
		'source' => sanitize_text_field( $source_host ),
		'at' => gmdate( DATE_ATOM ),
	);
	update_option( 'bds_account_link_audit_v1', array_slice( $rows, -500 ), false );
}

/** Source: authenticated POST returns an auto-submitting cross-site POST form. */
function bds_account_link_start() {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'Please sign in first.', 'branddad' ), '', array( 'response' => 401 ) );
	}
	check_admin_referer( 'bds_account_link_start' );
	$hosts = bds_account_link_hosts();
	$destination = strtolower( sanitize_text_field( wp_unslash( $_POST['destination'] ?? '' ) ) );
	$current = bds_account_link_current_host();
	if ( ! isset( $hosts[ $destination ] ) || $destination === $current ) {
		wp_die( esc_html__( 'The destination is not allowed.', 'branddad' ), '', array( 'response' => 422 ) );
	}
	$token = bds_account_link_create_token( get_current_user_id(), $destination );
	if ( is_wp_error( $token ) ) {
		wp_die( esc_html( $token->get_error_message() ), '', array( 'response' => 503 ) );
	}
	$return_path = sanitize_text_field( wp_unslash( $_POST['return_path'] ?? '/account-hub/' ) );
	if ( ! in_array( $return_path, array( '/account-hub/', '/my-account/', '/add-listing/' ), true ) ) {
		$return_path = '/account-hub/';
	}
	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	$action = $hosts[ $destination ] . '/wp-admin/admin-post.php';
	?>
	<!doctype html><html><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title><?php esc_html_e( 'Connecting your BrandDad account', 'branddad' ); ?></title></head>
	<body><p><?php esc_html_e( 'Connecting your BrandDad account securely…', 'branddad' ); ?></p>
	<form id="bds-account-link" method="post" action="<?php echo esc_url( $action ); ?>">
		<input type="hidden" name="action" value="bds_account_link_consume">
		<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
		<input type="hidden" name="return_path" value="<?php echo esc_attr( $return_path ); ?>">
		<noscript><button type="submit"><?php esc_html_e( 'Continue', 'branddad' ); ?></button></noscript>
	</form><script>document.getElementById('bds-account-link').submit();</script></body></html>
	<?php
	exit;
}
add_action( 'admin_post_bds_account_link_start', 'bds_account_link_start' );

/** Destination: consume once, reuse or create the matching local account, then sign in. */
function bds_account_link_consume() {
	$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
	$data  = bds_account_link_verify_token( $token );
	if ( is_wp_error( $data ) ) {
		wp_die( esc_html( $data->get_error_message() ), '', array( 'response' => 403 ) );
	}
	$replay_key = 'bds_account_link_used_' . hash( 'sha256', (string) $data['jti'] );
	if ( ! add_option( $replay_key, absint( $data['exp'] ), '', false ) ) {
		wp_die( esc_html__( 'This account link has already been used.', 'branddad' ), '', array( 'response' => 409 ) );
	}
	$email = strtolower( sanitize_email( $data['email'] ) );
	$user  = get_user_by( 'email', $email );
	if ( is_user_logged_in() && ( ! $user instanceof WP_User || get_current_user_id() !== absint( $user->ID ) ) ) {
		wp_die( esc_html__( 'Sign out of the other BrandDad account before linking this one.', 'branddad' ), '', array( 'response' => 409 ) );
	}
	if ( ! $user instanceof WP_User ) {
		$base = sanitize_user( strtok( $email, '@' ), true );
		$base = $base ? $base : 'branddad-member';
		$login = $base;
		$suffix = 1;
		while ( username_exists( $login ) && $suffix < 1000 ) {
			$login = $base . '-' . $suffix++;
		}
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => sanitize_text_field( $data['display_name'] ?? '' ),
				'role'         => 'subscriber',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			delete_option( $replay_key );
			wp_die( esc_html__( 'The linked account could not be created.', 'branddad' ), '', array( 'response' => 500 ) );
		}
		$user = get_user_by( 'id', $user_id );
	}
	if ( ! $user instanceof WP_User ) {
		delete_option( $replay_key );
		wp_die( esc_html__( 'The linked account could not be loaded.', 'branddad' ), '', array( 'response' => 500 ) );
	}
	update_user_meta( $user->ID, '_bds_linked_source', sanitize_text_field( $data['iss'] ) );
	update_user_meta( $user->ID, '_bds_linked_source_uid', absint( $data['source_uid'] ) );
	update_user_meta( $user->ID, '_bds_linked_email_hash', sanitize_text_field( $data['email_hash'] ) );
	update_user_meta( $user->ID, '_bds_linked_at', gmdate( DATE_ATOM ) );
	bds_account_link_audit( 'linked', $user->ID, $data['iss'] );
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, false, is_ssl() );
	$return_path = sanitize_text_field( wp_unslash( $_POST['return_path'] ?? '/account-hub/' ) );
	if ( ! in_array( $return_path, array( '/account-hub/', '/my-account/', '/add-listing/' ), true ) ) {
		$return_path = '/account-hub/';
	}
	wp_safe_redirect( home_url( $return_path ) );
	exit;
}
add_action( 'admin_post_nopriv_bds_account_link_consume', 'bds_account_link_consume' );
add_action( 'admin_post_bds_account_link_consume', 'bds_account_link_consume' );

/** Cursor-facing helper for a deliberate user-initiated handoff button. */
function bds_account_link_form( $destination, $return_path = '/account-hub/', $label = 'Continue with my BrandDad account' ) {
	$user = wp_get_current_user();
	if ( ! is_user_logged_in() || ! bds_account_link_secret_ready() || ! bds_account_link_email_verified( $user ) ) {
		return '';
	}
	return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
		. '<input type="hidden" name="action" value="bds_account_link_start">'
		. wp_nonce_field( 'bds_account_link_start', '_wpnonce', true, false )
		. '<input type="hidden" name="destination" value="' . esc_attr( $destination ) . '">'
		. '<input type="hidden" name="return_path" value="' . esc_attr( $return_path ) . '">'
		. '<button type="submit" class="button">' . esc_html( $label ) . '</button></form>';
}

function bds_account_link_cleanup_replays() {
	global $wpdb;
	$now = time();
	$keys = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'bds_account_link_used_%' LIMIT 500" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $keys as $key ) {
		if ( absint( get_option( $key, 0 ) ) < $now ) {
			delete_option( $key );
		}
	}
}
add_action( 'bds_account_link_cleanup_replays', 'bds_account_link_cleanup_replays' );

function bds_account_link_schedule_cleanup() {
	if ( ! wp_next_scheduled( 'bds_account_link_cleanup_replays' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bds_account_link_cleanup_replays' );
	}
}
add_action( 'init', 'bds_account_link_schedule_cleanup' );
