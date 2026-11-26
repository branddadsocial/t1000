<?php
/**
 * Plugin Name: BrandDad Directory Go Links
 * Description: Native short links /go/{username} to the published listing with Partnero ref. Owner back panel (Directorist dashboard, add-listing, WP listing edit) shows username + copy URL + Partnero field. City SEO permalinks unchanged. Does not replace featured/look-lock/launch/catalog MUs.
 * Version: 0.1.5
 *
 * Deploy: directory.branddad.social wp-content/mu-plugins/000-bds-directory-go-links-mu.php
 *
 * Public: directory.branddad.social/go/{username}?ref= house (or listing owner code).
 * Apex go.branddad.social/{username} is copy-safe in JS; TLS for that host is a panel/AutoSSL job.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_GO_LINKS_VER' ) ) {
	return;
}

define( 'BDS_GO_LINKS_VER', '0.1.5' );
define( 'BDS_GO_LINKS_CPT', 'at_biz_dir' );
define( 'BDS_GO_HOUSE_REF', 'mcdqf8t1iinf' );

add_action( 'init', 'bds_go_rewrite', 8 );
add_filter( 'query_vars', 'bds_go_query_vars' );
add_action( 'template_redirect', 'bds_go_redirect', 0 );
add_action( 'wp_footer', 'bds_go_public_card', 22 );
add_action( 'wp_footer', 'bds_go_owner_front_panels', 28 );
add_action( 'add_meta_boxes', 'bds_go_metabox' );
add_action( 'admin_post_bds_go_save_ref', 'bds_go_handle_save_ref' );
add_filter( 'directorist_dashboard_tabs', 'bds_go_dashboard_tabs', 39 );

/**
 * @return bool
 */
function bds_go_is_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	return ( false !== strpos( $host, 'directory.branddad' ) || 'go.branddad.social' === $host );
}

/**
 * @return string
 */
function bds_go_house_ref() {
	if ( defined( 'BDS_PO_HOUSE_REF' ) && is_string( BDS_PO_HOUSE_REF ) && BDS_PO_HOUSE_REF !== '' ) {
		$h = bds_go_sanitize_ref( BDS_PO_HOUSE_REF );
		if ( $h !== '' ) {
			return $h;
		}
	}
	return BDS_GO_HOUSE_REF;
}

/**
 * @param mixed $raw Raw partner id or pasted referral URL.
 * @return string
 */
function bds_go_sanitize_ref( $raw ) {
	$raw = trim( (string) $raw );
	if ( $raw === '' ) {
		return '';
	}
	if ( preg_match( '#[?&]ref=([a-zA-Z0-9_-]{4,64})#', $raw, $m ) ) {
		$raw = $m[1];
	}
	if ( function_exists( 'bds_po_sanitize_ref' ) ) {
		return (string) bds_po_sanitize_ref( $raw );
	}
	$s = strtolower( $raw );
	return preg_match( '/^[a-z0-9_-]{4,64}$/', $s ) ? $s : '';
}

/**
 * @param int $listing_id Listing.
 * @return string
 */
function bds_go_listing_ref( $listing_id ) {
	$listing_id = (int) $listing_id;
	if ( $listing_id < 1 ) {
		return bds_go_house_ref();
	}
	if ( function_exists( 'bds_vb_listing_ref' ) ) {
		$ref = bds_vb_listing_ref( $listing_id );
		if ( $ref !== '' ) {
			return $ref;
		}
	}
	$ref = bds_go_sanitize_ref( get_post_meta( $listing_id, '_bds_partnero_ref', true ) );
	if ( $ref !== '' ) {
		return $ref;
	}
	$author = (int) get_post_field( 'post_author', $listing_id );
	if ( $author > 0 ) {
		$ref = bds_go_sanitize_ref( get_user_meta( $author, 'bds_partnero_ref', true ) );
		if ( $ref === '' ) {
			$ref = bds_go_sanitize_ref( get_user_meta( $author, '_bds_partnero_ref', true ) );
		}
		if ( $ref !== '' ) {
			return $ref;
		}
	}
	return bds_go_house_ref();
}

/**
 * Listing username is the post_name. Not invented. Not a city path.
 *
 * @param int $listing_id Listing.
 * @return string
 */
function bds_go_username( $listing_id ) {
	$slug = strtolower( (string) get_post_field( 'post_name', (int) $listing_id ) );
	if ( ! preg_match( '/^[a-z0-9](?:[a-z0-9\-]{0,62}[a-z0-9])?$/', $slug ) ) {
		return '';
	}
	if ( in_array( $slug, array( 'tg', 'go', 'admin', 'login', 'wp-login', 'feed', 'page' ), true ) ) {
		return '';
	}
	return $slug;
}

/**
 * Public copy URL (Directory host until go. AutoSSL). City permalink unchanged.
 *
 * @param int $listing_id Listing.
 * @return string
 */
function bds_go_copy_url( $listing_id ) {
	$user = bds_go_username( $listing_id );
	if ( $user === '' ) {
		return '';
	}
	$ref = bds_go_listing_ref( $listing_id );
	return 'https://directory.branddad.social/go/' . rawurlencode( $user ) . '?ref=' . rawurlencode( $ref );
}

/**
 * @param int $listing_id Listing.
 * @param int $user_id    Acting user.
 * @return bool
 */
function bds_go_can_manage( $listing_id, $user_id = 0 ) {
	$listing_id = (int) $listing_id;
	$user_id    = $user_id ? (int) $user_id : get_current_user_id();
	if ( $listing_id < 1 || $user_id < 1 ) {
		return false;
	}
	if ( function_exists( 'bds_vb_can_manage' ) ) {
		return (bool) bds_vb_can_manage( $listing_id, $user_id );
	}
	if ( user_can( $user_id, 'edit_post', $listing_id ) ) {
		return true;
	}
	return (int) get_post_field( 'post_author', $listing_id ) === $user_id;
}

/**
 * @return void
 */
function bds_go_rewrite() {
	if ( ! bds_go_is_host() ) {
		return;
	}
	add_rewrite_rule( '^go/(?!tg/?)([a-z0-9](?:[a-z0-9\-]{0,62}[a-z0-9])?)/?$', 'index.php?bds_go_user=$matches[1]', 'top' );
}

/**
 * @param array<int,string> $vars Vars.
 * @return array<int,string>
 */
function bds_go_query_vars( $vars ) {
	$vars[] = 'bds_go_user';
	return $vars;
}

/**
 * 302 published listing. Leaves /go/tg/ and city SEO URLs alone.
 *
 * @return void
 */
function bds_go_redirect() {
	if ( ! bds_go_is_host() ) {
		return;
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$user = '';
	if ( 'go.branddad.social' === $host ) {
		if ( preg_match( '#^/([a-z0-9](?:[a-z0-9\-]{0,62}[a-z0-9])?)/?$#', $path, $m ) ) {
			$user = $m[1];
		}
	} else {
		$qv = get_query_var( 'bds_go_user' );
		if ( is_string( $qv ) && $qv !== '' ) {
			$user = strtolower( $qv );
		} elseif ( preg_match( '#^/go/(?!tg/?)([a-z0-9](?:[a-z0-9\-]{0,62}[a-z0-9])?)/?$#', $path, $m ) ) {
			$user = $m[1];
		}
	}
	if ( $user === '' || in_array( $user, array( 'tg', 'go', 'admin', 'login', 'wp-login', 'feed', 'page' ), true ) ) {
		return;
	}
	$listing = get_page_by_path( $user, OBJECT, BDS_GO_LINKS_CPT );
	if ( ! $listing || 'publish' !== $listing->post_status ) {
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}
	$to = get_permalink( (int) $listing->ID );
	if ( ! is_string( $to ) || $to === '' ) {
		$to = home_url( '/' );
	}
	$to = add_query_arg( 'ref', bds_go_listing_ref( (int) $listing->ID ), $to );
	wp_safe_redirect( $to, 302 );
	exit;
}

/**
 * @return void
 */
function bds_go_css() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo '<style id="bds-go-link-css" data-bds-go="' . esc_attr( BDS_GO_LINKS_VER ) . '">'
		. '.bds-go-link,.bds-go-profile{margin:1rem 0;padding:1rem 1.1rem;border:1px solid #cbd5e1;border-radius:12px;background:#f8fafc;max-width:40rem}'
		. '.bds-go-link h2,.bds-go-profile h2{margin:0 0 .4rem;font:800 1.05rem/1.25 system-ui,sans-serif;color:#0f172a}'
		. '.bds-go-link p,.bds-go-profile p{margin:0 0 .55rem;font:400 14px/1.45 system-ui,sans-serif;color:#334155}'
		. '.bds-go-link code,.bds-go-profile code{font:600 13px/1.4 ui-monospace,monospace;word-break:break-all}'
		. '.bds-go-profile label{display:block;font:700 12px/1.3 system-ui,sans-serif;margin:.4rem 0 .2rem}'
		. '.bds-go-profile input[type=text]{width:100%;max-width:28rem;padding:.45rem .55rem;border:1px solid #94a3b8;border-radius:8px}'
		. '.bds-go-link__copy,.bds-go-profile button{padding:.45rem .85rem;border:0;border-radius:8px;background:#0f766e;color:#fff;font:700 13px/1.2 system-ui,sans-serif;cursor:pointer}'
		. '</style>';
}

/**
 * Public listing card — bios copy URL. Does not replace city permalink.
 *
 * @return void
 */
function bds_go_public_card() {
	if ( is_admin() || ! bds_go_is_host() || ! is_singular( BDS_GO_LINKS_CPT ) ) {
		return;
	}
	$id  = (int) get_queried_object_id();
	$url = bds_go_copy_url( $id );
	if ( $url === '' ) {
		return;
	}
	$ref  = bds_go_listing_ref( $id );
	$user = bds_go_username( $id );
	$host = 'directory.branddad.social/go/' . $user . '?ref=' . $ref;
	bds_go_css();
	echo '<aside id="bds-go-link" class="bds-go-link" data-bds-go="' . esc_attr( BDS_GO_LINKS_VER ) . '">';
	echo '<h2>BrandDad link</h2>';
	echo '<p>Short link for bios. It opens this Directory profile. City and listing pages stay as they were.</p>';
	echo '<p class="bds-go-link__url"><code>' . esc_html( $host ) . '</code></p>';
	echo '<p class="bds-go-link__num">Affiliate number <strong>' . esc_html( $ref ) . '</strong></p>';
	echo '<p><button type="button" class="bds-go-link__copy" data-bds-go-copy="' . esc_url( $url ) . '">Copy short link</button></p>';
	echo '</aside>';
	bds_go_copy_js();
}

/**
 * Clipboard helper. Allows directory /go/ and future go. host only.
 *
 * @return void
 */
function bds_go_copy_js() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo '<script id="bds-go-link-js">(function(){'
		. 'function ok(u){return /^https:\\/\\/go\\.branddad\\.social\\/[a-z0-9](?:[a-z0-9\\-]{0,62}[a-z0-9])?(?:\\?ref=[a-z0-9_\\-]{2,32})?$/.test(u)||/^https:\\/\\/directory\\.branddad\\.social\\/go\\/[a-z0-9](?:[a-z0-9\\-]{0,62}[a-z0-9])?(?:\\?ref=[a-z0-9_\\-]{2,32})?$/.test(u);}'
		. 'function done(){var n=document.getElementById("bds-go-copy-status");if(n)n.textContent="Copied.";}'
		. 'document.addEventListener("click",function(e){var t=e.target;if(!t||!t.getAttribute)return;var url=t.getAttribute("data-bds-go-copy")||"";if(!ok(url))return;e.preventDefault();'
		. 'if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(url).then(done).catch(function(){window.prompt("Copy link",url);done();});}'
		. 'else{window.prompt("Copy link",url);done();}});'
		. '})();</script>';
}

/**
 * Owner surfaces: Directorist add/edit listing + dashboard band.
 *
 * @return void
 */
function bds_go_owner_front_panels() {
	if ( is_admin() || ! bds_go_is_host() || ! is_user_logged_in() ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = strtolower( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
	$ids  = array();
	if ( preg_match( '#/(add-listing|edit-listing)(/|$)#', $path ) ) {
		$lid = absint( $_GET['listing_id'] ?? ( $_GET['post_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $lid > 0 && bds_go_can_manage( $lid ) ) {
			$ids[] = $lid;
		}
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	if ( ! $ids ) {
		return;
	}
	bds_go_css();
	bds_go_copy_js();
	foreach ( $ids as $id ) {
		bds_go_profile_panel( $id, 'front' );
	}
}

/**
 * @param array<string,mixed> $tabs Tabs.
 * @return array<string,mixed>
 */
function bds_go_dashboard_tabs( $tabs ) {
	if ( ! is_array( $tabs ) ) {
		$tabs = array();
	}
	$tabs['bds_profile_link'] = array(
		'title'            => 'Profile short link',
		'icon'             => 'las la-link',
		'content_callback' => 'bds_go_dashboard_tab_content',
		'priority'         => 18,
	);
	return $tabs;
}

/**
 * @return void
 */
function bds_go_dashboard_tab_content() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$mine = get_posts(
		array(
			'post_type'      => BDS_GO_LINKS_CPT,
			'author'         => get_current_user_id(),
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'posts_per_page' => 20,
			'fields'         => 'ids',
		)
	);
	bds_go_css();
	bds_go_copy_js();
	if ( ! is_array( $mine ) || ! $mine ) {
		echo '<p>List or claim a business first. Your short link is tied to that listing username.</p>';
		return;
	}
	foreach ( $mine as $id ) {
		bds_go_profile_panel( (int) $id, 'dashboard' );
	}
}

/**
 * @return void
 */
function bds_go_metabox() {
	add_meta_box(
		'bds-go-profile',
		'Directory short link',
		'bds_go_metabox_render',
		BDS_GO_LINKS_CPT,
		'side',
		'high'
	);
}

/**
 * @param WP_Post $post Post.
 * @return void
 */
function bds_go_metabox_render( $post ) {
	if ( ! $post || ! bds_go_can_manage( (int) $post->ID ) ) {
		echo '<p>You can only manage the short link on a listing you own.</p>';
		return;
	}
	bds_go_css();
	bds_go_copy_js();
	bds_go_profile_panel( (int) $post->ID, 'admin' );
}

/**
 * Listing-profile panel: username (read-only), copy URL, Partnero field.
 *
 * @param int    $listing_id Listing.
 * @param string $ctx        front|dashboard|admin.
 * @return void
 */
function bds_go_profile_panel( $listing_id, $ctx = 'front' ) {
	$listing_id = (int) $listing_id;
	if ( $listing_id < 1 || ! bds_go_can_manage( $listing_id ) ) {
		return;
	}
	$user = bds_go_username( $listing_id );
	$url  = bds_go_copy_url( $listing_id );
	$ref  = bds_go_listing_ref( $listing_id );
	$perm = get_permalink( $listing_id );
	$act  = admin_url( 'admin-post.php' );
	echo '<section class="bds-go-profile" data-bds-go-profile="' . esc_attr( (string) $listing_id ) . '" data-bds-go-ctx="' . esc_attr( $ctx ) . '">';
	echo '<h2>Your Directory profile link</h2>';
	echo '<p>This short link opens <strong>' . esc_html( get_the_title( $listing_id ) ) . '</strong>. Username is the listing slug. City SEO URLs stay separate.</p>';
	echo '<p><label>Listing username</label><code>' . esc_html( $user !== '' ? $user : '(not a public username yet)' ) . '</code></p>';
	if ( $url !== '' ) {
		$host = 'directory.branddad.social/go/' . $user . '?ref=' . $ref;
		echo '<p><label>Copy URL</label><code>' . esc_html( $host ) . '</code></p>';
		echo '<p>Affiliate number <strong>' . esc_html( $ref ) . '</strong></p>';
		echo '<p><button type="button" data-bds-go-copy="' . esc_url( $url ) . '">Copy short link</button> <span id="bds-go-copy-status" role="status"></span></p>';
	}
	if ( is_string( $perm ) && $perm !== '' ) {
		echo '<p><a href="' . esc_url( $perm ) . '">Open listing (city SEO URL)</a></p>';
	}
	echo '<form method="post" action="' . esc_url( $act ) . '">';
	echo '<input type="hidden" name="action" value="bds_go_save_ref">';
	echo '<input type="hidden" name="listing_id" value="' . esc_attr( (string) $listing_id ) . '">';
	wp_nonce_field( 'bds_go_save_ref_' . $listing_id );
	echo '<label for="bds-go-ref-' . esc_attr( (string) $listing_id ) . '">Partnero / affiliate number</label>';
	echo '<input id="bds-go-ref-' . esc_attr( (string) $listing_id ) . '" type="text" name="bds_go_ref" value="' . esc_attr( $ref ) . '" maxlength="120" autocomplete="off" spellcheck="false" placeholder="your code or paste your referral link">';
	echo '<p><button type="submit">Save affiliate number</button></p>';
	echo '<p>House default is <code>' . esc_html( bds_go_house_ref() ) . '</code> until you save your own. Find it in the Affiliate portal.</p>';
	echo '</form></section>';
}

/**
 * Owner-only Partnero save. Listing id from POST, authZ against session user.
 *
 * @return void
 */
function bds_go_handle_save_ref() {
	if ( ! is_user_logged_in() ) {
		wp_die( 'Sign in required.', 'Forbidden', array( 'response' => 403 ) );
	}
	$listing_id = absint( $_POST['listing_id'] ?? 0 );
	check_admin_referer( 'bds_go_save_ref_' . $listing_id );
	if ( ! bds_go_can_manage( $listing_id ) ) {
		wp_die( 'You can only set the referral code on a listing you own.', 'Forbidden', array( 'response' => 403 ) );
	}
	$raw = isset( $_POST['bds_go_ref'] ) ? wp_unslash( (string) $_POST['bds_go_ref'] ) : '';
	if ( function_exists( 'bds_vb_save_ref' ) ) {
		bds_vb_save_ref( $listing_id, $raw, get_current_user_id() );
	} else {
		$ref = bds_go_sanitize_ref( $raw );
		if ( $ref === '' ) {
			delete_post_meta( $listing_id, '_bds_partnero_ref' );
		} else {
			update_post_meta( $listing_id, '_bds_partnero_ref', $ref );
		}
	}
	$back = wp_get_referer();
	if ( ! is_string( $back ) || $back === '' ) {
		$back = admin_url( 'post.php?post=' . $listing_id . '&action=edit' );
	}
	wp_safe_redirect( $back );
	exit;
}
