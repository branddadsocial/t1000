<?php
/**
 * Plugin Name: BrandDad Directory Free AI Entitlements (Staged)
 * Description: Per-business eligibility and concurrency-safe monthly allowance ledger for verified Directory businesses.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_DIRECTORY_FREE_AI_VERSION', '0.1.0' );

/** Initial per-business monthly allowances. External execution remains disabled. */
function bds_dir_free_ai_allowances() {
	return array(
		'website_audit'    => array( 'label' => 'Website audit', 'limit' => 1 ),
		'social_draft'     => array( 'label' => 'Social post draft', 'limit' => 5 ),
		'local_seo_report' => array( 'label' => 'Local SEO report', 'limit' => 1 ),
		'review_response'  => array( 'label' => 'Review response draft', 'limit' => 5 ),
		'assistant_request'=> array( 'label' => 'AI assistant request', 'limit' => 10 ),
	);
}

/** Verify ownership from authoritative listing data, never request data. */
function bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) {
	$listing_id = absint( $listing_id );
	$user_id    = absint( $user_id );
	$post       = $listing_id ? get_post( $listing_id ) : null;
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status || ! $user_id ) {
		return false;
	}
	$state      = sanitize_key( (string) get_post_meta( $listing_id, '_bds_claim_state', true ) );
	$claimed_by = absint( get_post_meta( $listing_id, '_bds_claimed_by', true ) );
	return in_array( $state, array( 'claimed', 'approved', 'verified' ), true )
		&& $user_id === absint( $post->post_author )
		&& $user_id === $claimed_by;
}

/** Record local eligibility after the existing moderation workflow approves ownership. */
function bds_dir_free_ai_on_claim_approved( $listing_id, $claim_id, $user_id ) {
	if ( ! bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) ) {
		return;
	}
	update_post_meta( absint( $listing_id ), '_bds_free_ai_owner', absint( $user_id ) );
	update_post_meta( absint( $listing_id ), '_bds_free_ai_eligible_at', current_time( 'mysql', true ) );
	update_post_meta( absint( $listing_id ), '_bds_free_ai_claim_id', absint( $claim_id ) );
}
add_action( 'bds_directory_claim_approved', 'bds_dir_free_ai_on_claim_approved', 30, 3 );

/** Stable UTC billing period key. */
function bds_dir_free_ai_period_key( $timestamp = null ) {
	$timestamp = null === $timestamp ? time() : absint( $timestamp );
	return gmdate( 'Y-m', $timestamp );
}

/** Internal option key; no browser-provided strings are accepted. */
function bds_dir_free_ai_ledger_key( $listing_id, $period = '' ) {
	$period = preg_match( '/^\d{4}-\d{2}$/', (string) $period ) ? (string) $period : bds_dir_free_ai_period_key();
	return 'bds_dir_free_ai_' . absint( $listing_id ) . '_' . str_replace( '-', '', $period );
}

/** Acquire a short atomic lock and recover only locks older than 30 seconds. */
function bds_dir_free_ai_lock( $listing_id ) {
	$key = 'bds_dir_free_ai_lock_' . absint( $listing_id );
	if ( add_option( $key, time(), '', false ) ) {
		return $key;
	}
	$created = absint( get_option( $key, 0 ) );
	if ( $created && $created < time() - 30 ) {
		delete_option( $key );
		return add_option( $key, time(), '', false ) ? $key : '';
	}
	return '';
}

/** Release only the exact lock created for this listing. */
function bds_dir_free_ai_unlock( $lock_key ) {
	if ( is_string( $lock_key ) && 0 === strpos( $lock_key, 'bds_dir_free_ai_lock_' ) ) {
		delete_option( $lock_key );
	}
}

/** Normalize and clean an allowance ledger. */
function bds_dir_free_ai_read_ledger( $listing_id, $period = '' ) {
	$key    = bds_dir_free_ai_ledger_key( $listing_id, $period );
	$stored = get_option( $key, array() );
	$ledger = is_array( $stored ) ? $stored : array();
	$ledger['used']    = isset( $ledger['used'] ) && is_array( $ledger['used'] ) ? $ledger['used'] : array();
	$ledger['pending'] = isset( $ledger['pending'] ) && is_array( $ledger['pending'] ) ? $ledger['pending'] : array();
	$now = time();
	foreach ( $ledger['pending'] as $token => $reservation ) {
		if ( ! is_array( $reservation ) || absint( $reservation['expires_at'] ?? 0 ) <= $now ) {
			unset( $ledger['pending'][ $token ] );
		}
	}
	return $ledger;
}

/** Summarize used, pending, and remaining units for one business. */
function bds_dir_free_ai_status( $listing_id, $user_id ) {
	if ( ! bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) ) {
		return new WP_Error( 'not_eligible', 'This business does not have verified free AI access.' );
	}
	$ledger = bds_dir_free_ai_read_ledger( $listing_id );
	$status = array();
	foreach ( bds_dir_free_ai_allowances() as $tool => $definition ) {
		$used    = absint( $ledger['used'][ $tool ] ?? 0 );
		$pending = 0;
		foreach ( $ledger['pending'] as $reservation ) {
			if ( $tool === ( $reservation['tool'] ?? '' ) ) {
				$pending += absint( $reservation['units'] ?? 0 );
			}
		}
		$status[ $tool ] = array(
			'label'     => $definition['label'],
			'limit'     => absint( $definition['limit'] ),
			'used'      => $used,
			'pending'   => $pending,
			'remaining' => max( 0, absint( $definition['limit'] ) - $used - $pending ),
		);
	}
	return array( 'period' => bds_dir_free_ai_period_key(), 'listing_id' => absint( $listing_id ), 'tools' => $status );
}

/**
 * Reserve allowance before a job starts. Commit only after a delivered result.
 *
 * @return string|WP_Error Opaque reservation token or a stable error.
 */
function bds_dir_free_ai_reserve( $listing_id, $user_id, $tool, $units = 1 ) {
	$listing_id = absint( $listing_id );
	$user_id    = absint( $user_id );
	$tool       = sanitize_key( $tool );
	$units      = max( 1, absint( $units ) );
	$allowances = bds_dir_free_ai_allowances();
	if ( ! isset( $allowances[ $tool ] ) ) {
		return new WP_Error( 'unknown_tool', 'This free AI tool is not available.' );
	}
	if ( ! bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) ) {
		return new WP_Error( 'not_eligible', 'This business does not have verified free AI access.' );
	}
	$lock = bds_dir_free_ai_lock( $listing_id );
	if ( ! $lock ) {
		return new WP_Error( 'busy', 'Another request is being processed. Please try again.' );
	}
	try {
		$ledger  = bds_dir_free_ai_read_ledger( $listing_id );
		$used    = absint( $ledger['used'][ $tool ] ?? 0 );
		$pending = 0;
		foreach ( $ledger['pending'] as $reservation ) {
			if ( $tool === ( $reservation['tool'] ?? '' ) ) {
				$pending += absint( $reservation['units'] ?? 0 );
			}
		}
		if ( $used + $pending + $units > absint( $allowances[ $tool ]['limit'] ) ) {
			return new WP_Error( 'allowance_exhausted', 'This business has used its monthly free allowance for this tool.' );
		}
		$token = wp_generate_uuid4();
		$ledger['pending'][ $token ] = array(
			'tool'       => $tool,
			'units'      => $units,
			'user_id'    => $user_id,
			'created_at' => time(),
			'expires_at' => time() + ( 20 * MINUTE_IN_SECONDS ),
		);
		update_option( bds_dir_free_ai_ledger_key( $listing_id ), $ledger, false );
		return $token;
	} finally {
		bds_dir_free_ai_unlock( $lock );
	}
}

/** Commit a reservation only when the caller delivered a usable result. */
function bds_dir_free_ai_commit( $listing_id, $user_id, $token ) {
	$listing_id = absint( $listing_id );
	$user_id    = absint( $user_id );
	$token      = sanitize_text_field( (string) $token );
	if ( ! bds_dir_free_ai_listing_eligible( $listing_id, $user_id ) || ! wp_is_uuid( $token ) ) {
		return false;
	}
	$lock = bds_dir_free_ai_lock( $listing_id );
	if ( ! $lock ) {
		return false;
	}
	try {
		$ledger      = bds_dir_free_ai_read_ledger( $listing_id );
		$reservation = $ledger['pending'][ $token ] ?? null;
		if ( ! is_array( $reservation ) || $user_id !== absint( $reservation['user_id'] ?? 0 ) ) {
			return false;
		}
		$tool = sanitize_key( $reservation['tool'] ?? '' );
		if ( ! isset( bds_dir_free_ai_allowances()[ $tool ] ) ) {
			return false;
		}
		$ledger['used'][ $tool ] = absint( $ledger['used'][ $tool ] ?? 0 ) + absint( $reservation['units'] ?? 0 );
		unset( $ledger['pending'][ $token ] );
		update_option( bds_dir_free_ai_ledger_key( $listing_id ), $ledger, false );
		return true;
	} finally {
		bds_dir_free_ai_unlock( $lock );
	}
}

/** Release a failed job without charging the business allowance. */
function bds_dir_free_ai_release( $listing_id, $user_id, $token ) {
	$listing_id = absint( $listing_id );
	$user_id    = absint( $user_id );
	$token      = sanitize_text_field( (string) $token );
	if ( ! wp_is_uuid( $token ) ) {
		return false;
	}
	$lock = bds_dir_free_ai_lock( $listing_id );
	if ( ! $lock ) {
		return false;
	}
	try {
		$ledger      = bds_dir_free_ai_read_ledger( $listing_id );
		$reservation = $ledger['pending'][ $token ] ?? null;
		if ( ! is_array( $reservation ) || $user_id !== absint( $reservation['user_id'] ?? 0 ) ) {
			return false;
		}
		unset( $ledger['pending'][ $token ] );
		update_option( bds_dir_free_ai_ledger_key( $listing_id ), $ledger, false );
		return true;
	} finally {
		bds_dir_free_ai_unlock( $lock );
	}
}

/** Owner-only status block for a Directory dashboard or listing-management page. */
function bds_dir_free_ai_status_shortcode( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$atts       = shortcode_atts( array( 'listing_id' => 0 ), $atts, 'bds_directory_free_ai_status' );
	$listing_id = absint( $atts['listing_id'] );
	$user_id    = get_current_user_id();
	$status     = bds_dir_free_ai_status( $listing_id, $user_id );
	if ( is_wp_error( $status ) ) {
		return '';
	}
	ob_start();
	?>
	<section class="bds-dir-free-ai" aria-labelledby="bds-dir-free-ai-title">
		<h2 id="bds-dir-free-ai-title"><?php esc_html_e( 'Your Free BrandDad AI Tools', 'branddad' ); ?></h2>
		<p><?php esc_html_e( 'These limits belong to this verified business and refresh monthly. Failed jobs do not use your allowance.', 'branddad' ); ?></p>
		<ul>
			<?php foreach ( $status['tools'] as $tool ) : ?>
				<li><strong><?php echo esc_html( $tool['label'] ); ?></strong> — <?php echo esc_html( sprintf( '%d of %d remaining', $tool['remaining'], $tool['limit'] ) ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p><em><?php esc_html_e( 'Tool execution stays unavailable until the final cost and access tests pass.', 'branddad' ); ?></em></p>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'bds_directory_free_ai_status', 'bds_dir_free_ai_status_shortcode' );

