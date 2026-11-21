<?php
/**
 * Plugin Name: BrandDad Customer Surfaces
 * Description: Account hub, membership status, free-AI allowance display, studio boards, and the Partnero affiliate door. Does not charge, invent balances, or publish.
 * Version: 0.2.8
 * Author: BrandDad
 *
 * Secure design: every account block uses the current WordPress user. Music briefs are limited to that user's posts. Affiliate money stays in Partnero. Studio buttons do not call providers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/bds-customer-hub-lib.php';

function bds_hub_is_allowed_host() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true );
}

function bds_hub_styles() {
	if ( ! bds_hub_is_allowed_host() ) {
		return;
	}
	echo '<style id="bds-hub-css">.bds-hub{max-width:980px;margin:24px auto;padding:8px 16px;color:#0b172a}.bds-hub h2{font-size:clamp(26px,4vw,36px);line-height:1.15}.bds-hub section{margin:28px 0;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff}.bds-hub a,.bds-hub button{display:inline-flex;align-items:center;min-height:44px;margin:8px 8px 0 0;padding:0 14px;border-radius:10px;background:#0668d8;color:#fff;font-weight:800;text-decoration:none}.bds-hub button[disabled]{background:#e8eef5;color:#526070;border:0}.bds-hub li{margin:6px 0}.bds-hub a:focus-visible,.bds-hub button:focus-visible,#bds-hub-dashboard a:focus-visible{outline:3px solid #0668d8;outline-offset:2px}#bds-hub-dashboard{max-width:980px;margin:24px auto;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff;color:#0b172a}</style>';
}
add_action( 'wp_head', 'bds_hub_styles', 40 );

function bds_hub_membership_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	if ( ! is_user_logged_in() ) {
		$register = function_exists( 'bds_mt_upgrade_url' ) ? bds_mt_upgrade_url() : home_url( '/registration/' );
		$list     = 'https://directory.branddad.social/add-listing/';
		if ( function_exists( 'bds_po_url' ) ) {
			$out = bds_po_url( $list );
			if ( is_string( $out ) && '' !== $out ) {
				$list = $out;
			}
		}
		$sw   = function_exists( 'bds_lang_swap_attrs' );
		$html = '<section id="bds-membership-status"><h2' . ( $sw ? bds_lang_swap_attrs( 'Directory membership', 'Membresía del directorio' ) : '' ) . '>Directory membership</h2>';
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'A basic listing is free for one business. Sign in to see the plan already on your account. The free listing does not include the 10% member price.', 'Un listado básico es gratis para un negocio. Entre para ver el plan de su cuenta. El listado gratis no incluye el 10% de miembro.' );
		} else {
			$html .= '<p>A basic listing is free for one business. Sign in to see the plan already on your account. The free listing does not include the 10% member price.</p>';
		}
		$html .= '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Sign in', 'Entrar' ) : '' ) . '>Sign in</a> <a href="' . esc_url( $list ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>List my business free</a> <a href="' . esc_url( $register ) . '"' . ( $sw ? bds_lang_swap_attrs( 'See plans', 'Ver planes' ) : '' ) . '>See plans</a></section>';
		return $html;
	}
	$tier = function_exists( 'bds_mt_user_tier' ) ? (string) bds_mt_user_tier() : 'free';
	$caps = function_exists( 'bds_mt_caps_for' ) ? bds_mt_caps_for( $tier ) : array();
	$view = bds_hub_membership_view( $tier, is_array( $caps ) ? $caps : array() );
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-membership-status"><h2' . ( $sw ? bds_lang_swap_attrs( 'Directory membership', 'Membresía del directorio' ) : '' ) . '>Directory membership</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Current record: ' . $view['label'] . '.', 'Registro actual: ' . $view['label'] . '.' );
	} else {
		$html .= '<p>Current record: ' . esc_html( $view['label'] ) . '.</p>';
	}
	if ( $view['included'] ) {
		$html .= '<ul>';
		foreach ( $view['included'] as $line ) {
			$html .= '<li>' . esc_html( $line ) . '</li>';
		}
		$html .= '</ul>';
	} elseif ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'This tier record does not list extra publishing or discount tools.', 'Este plan no lista herramientas extra de publicación o descuento.' );
	} else {
		$html .= '<p>This tier record does not list extra publishing or discount tools.</p>';
	}
	if ( $view['upgrade'] && function_exists( 'bds_mt_upgrade_url' ) ) {
		$html .= '<a href="' . esc_url( bds_mt_upgrade_url() ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Review upgrade options', 'Ver opciones de mejora' ) : '' ) . '>Review upgrade options</a>';
	}
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Cancellation and card changes stay in the existing billing account. This screen does not change them.', 'Cancelaciones y cambios de tarjeta siguen en la cuenta de cobro existente. Esta pantalla no las cambia.' );
	} else {
		$html .= '<p>Cancellation and card changes stay in the existing billing account. This screen does not change them.</p>';
	}
	$html .= '</section>';
	return $html;
}

function bds_hub_compare_html() {
	if ( ! bds_hub_is_allowed_host() || ! function_exists( 'bds_mt_entitlements' ) ) {
		return '';
	}
	$rows = bds_hub_membership_compare( bds_mt_entitlements() );
	if ( ! $rows ) {
		return '';
	}
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-membership-compare"><h2' . ( $sw ? bds_lang_swap_attrs( 'Plan comparison', 'Comparación de planes' ) : '' ) . '>Plan comparison</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'These rows come from the existing tier record. This screen does not change a price.', 'Estas filas salen del registro de planes existente. Esta pantalla no cambia un precio.' );
	} else {
		$html .= '<p>These rows come from the existing tier record. This screen does not change a price.</p>';
	}
	$html .= '<ul>';
	foreach ( $rows as $row ) {
		$detail = $row['included'] ? implode( ', ', $row['included'] ) : 'no extra tools in this tier record';
		$html  .= '<li><strong>' . esc_html( $row['label'] ) . '</strong>: ' . esc_html( $detail ) . '</li>';
	}
	return $html . '</ul></section>';
}

function bds_hub_saved_ids() {
	$ids = get_user_meta( get_current_user_id(), '_bds_saved_listing_ids', true );
	return bds_hub_saved_list( is_array( $ids ) ? $ids : array() );
}

function bds_hub_saved_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$sw = function_exists( 'bds_lang_swap_attrs' );
	if ( ! is_user_logged_in() ) {
		$html = '<section id="bds-saved"><h2' . ( $sw ? bds_lang_swap_attrs( 'Saved businesses', 'Negocios guardados' ) : '' ) . '>Saved businesses</h2>';
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'Sign in to save a business to this account. Guests do not get a saved list.', 'Entre para guardar un negocio en esta cuenta. Los invitados no tienen lista guardada.' );
		} else {
			$html .= '<p>Sign in to save a business to this account. Guests do not get a saved list.</p>';
		}
		return $html . '</section>';
	}
	$html = '<section id="bds-saved"><h2' . ( $sw ? bds_lang_swap_attrs( 'Saved businesses', 'Negocios guardados' ) : '' ) . '>Saved businesses</h2>';
	$ids  = bds_hub_saved_ids();
	if ( ! $ids ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'No businesses are saved on this account.', 'No hay negocios guardados en esta cuenta.' );
		} else {
			$html .= '<p>No businesses are saved on this account.</p>';
		}
		return $html . '</section>';
	}
	$html .= '<ul>';
	foreach ( $ids as $listing_id ) {
		$post = get_post( $listing_id );
		if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
			continue;
		}
		$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	return $html . '</ul></section>';
}

function bds_hub_free_ai_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$sw = function_exists( 'bds_lang_swap_attrs' );
	if ( ! is_user_logged_in() ) {
		$html = '<section id="bds-free-ai"><h2' . ( $sw ? bds_lang_swap_attrs( 'Free Directory AI', 'IA gratis del directorio' ) : '' ) . '>Free Directory AI</h2>';
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'Sign in with the account that claimed the business. No allowance is shown for a guest.', 'Entre con la cuenta que reclamó el negocio. Un invitado no ve un saldo.' );
		} else {
			$html .= '<p>Sign in with the account that claimed the business. No allowance is shown for a guest.</p>';
		}
		return $html . '</section>';
	}
	$status = null;
	if ( function_exists( 'bds_dir_free_ai_status' ) ) {
		$listings = get_posts(
			array(
				'post_type'      => 'at_biz_dir',
				'author'         => get_current_user_id(),
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'fields'         => 'ids',
			)
		);
		foreach ( $listings as $listing_id ) {
			$candidate = bds_dir_free_ai_status( (int) $listing_id, get_current_user_id() );
			if ( is_array( $candidate ) ) {
				$status = $candidate;
				break;
			}
		}
	}
	$view = bds_hub_free_ai_view( $status );
	$html = '<section id="bds-free-ai"><h2' . ( $sw ? bds_lang_swap_attrs( 'Free Directory AI', 'IA gratis del directorio' ) : '' ) . '>Free Directory AI</h2>';
	if ( 'unavailable' === $view['state'] ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( $view['lines'][0] . ' Tool runs stay off until the shared meter is connected.', $view['lines'][0] . ' Las herramientas siguen apagadas hasta conectar el medidor compartido.' );
		} else {
			$html .= '<p>' . esc_html( $view['lines'][0] ) . ' Tool runs stay off until the shared meter is connected.</p>';
		}
		return $html . '</section>';
	}
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Period ' . $view['period'] . '. These counts come from the allowance ledger. This screen does not run the tools.', 'Periodo ' . $view['period'] . '. Estos números salen del libro de saldo. Esta pantalla no ejecuta las herramientas.' );
	} else {
		$html .= '<p>Period ' . esc_html( $view['period'] ) . '. These counts come from the allowance ledger. This screen does not run the tools.</p>';
	}
	$html .= '<ul>';
	foreach ( $view['lines'] as $line ) {
		$html .= '<li>' . esc_html( $line ) . '</li>';
	}
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= '</ul>' . bds_lang_pair( 'Upgrade prompts use the existing plan page. Nothing here starts a subscription.', 'Las mejoras usan la página de planes existente. Nada aquí inicia una suscripción.' ) . '</section>';
	} else {
		$html .= '</ul><p>Upgrade prompts use the existing plan page. Nothing here starts a subscription.</p></section>';
	}
	return $html;
}

function bds_hub_studio_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-studios"><h2' . ( $sw ? bds_lang_swap_attrs( 'SEO, Social, and LinkedIn', 'SEO, redes y LinkedIn' ) : '' ) . '>SEO, Social, and LinkedIn</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'These boards are inquiry only. They do not start checkout or publish.', 'Estos tableros son solo consulta. No inician checkout ni publican.' );
	} else {
		$html .= '<p>These boards are inquiry only. They do not start checkout or publish.</p>';
	}
	foreach ( bds_hub_studio_cards() as $card ) {
		$title_es = isset( $card['title_es'] ) ? $card['title_es'] : $card['title'];
		$html    .= '<article id="bds-studio-' . esc_attr( $card['id'] ) . '"><h3' . ( $sw ? bds_lang_swap_attrs( $card['title'], $title_es ) : '' ) . '>' . esc_html( $card['title'] ) . '</h3>';
		if ( isset( $card['detail_es'] ) && function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( $card['detail'], $card['detail_es'] );
		} else {
			$html .= '<p>' . esc_html( $card['detail'] ) . '</p>';
		}
		$html .= '<button type="button" disabled aria-disabled="true"' . ( $sw ? bds_lang_swap_attrs( 'Not connected', 'No conectado' ) : '' ) . '>Not connected</button></article>';
	}
	$html .= '</section>';
	return $html;
}

function bds_hub_affiliate_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$portal = defined( 'BDS_AFF_PORTAL' ) ? BDS_AFF_PORTAL : 'https://affiliates.branddad.social/';
	// The live Partnero content filter rewrites the host and then leaves any path
	// glued to ?ref=. The portal root is the URL that rewrite keeps intact.
	// Do not call bds_po_url here; that filter adds the ref once.
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-affiliate"><h2' . ( $sw ? bds_lang_swap_attrs( 'Affiliate program', 'Programa de afiliados' ) : '' ) . '>Affiliate program</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( bds_hub_affiliate_copy(), 'Los enlaces, conversiones, comisiones y pagos se muestran en el portal Partnero existente. Esta página no calcula un porcentaje ni un saldo.' );
	} else {
		$html .= '<p>' . esc_html( bds_hub_affiliate_copy() ) . '</p>';
	}
	$html .= '<a href="' . esc_url( $portal ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Open the portal', 'Abrir el portal' ) : '' ) . '>Open the portal</a></section>';
	return $html;
}

function bds_hub_music_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$rows = array();
	if ( is_user_logged_in() && post_type_exists( 'bds_music_brief' ) ) {
		$posts = get_posts(
			array(
				'post_type'      => 'bds_music_brief',
				'author'         => get_current_user_id(),
				'post_status'    => array( 'private', 'draft' ),
				'posts_per_page' => 20,
			)
		);
		foreach ( $posts as $post ) {
			if ( (int) $post->post_author !== get_current_user_id() ) {
				continue;
			}
			$rows[] = array( 'title' => get_the_title( $post ) );
		}
	}
	$view = bds_hub_music_rows( $rows );
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-music-orders"><h2' . ( $sw ? bds_lang_swap_attrs( 'Music briefs', 'Briefs de música' ) : '' ) . '>Music briefs</h2>';
	if ( ! $view ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'No brief is saved on this account. A brief does not take payment or start production.', 'No hay un brief guardado en esta cuenta. Un brief no cobra ni empieza producción.' );
		} else {
			$html .= '<p>No brief is saved on this account. A brief does not take payment or start production.</p>';
		}
		return $html . '</section>';
	}
	$html .= '<ul>';
	foreach ( $view as $row ) {
		$html .= '<li>' . esc_html( $row['title'] . ' — ' . $row['status'] ) . '</li>';
	}
	$html .= '</ul></section>';
	return $html;
}

function bds_hub_is_directory() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return 'directory.branddad.social' === $host;
}

function bds_hub_action_url( array $action ) {
	$host = isset( $action['host'] ) ? (string) $action['host'] : '';
	$path = isset( $action['path'] ) ? (string) $action['path'] : '/';
	if ( 'directory' === $host ) {
		$url = 'https://directory.branddad.social' . $path;
	} else {
		$url = 'https://branddad.social' . $path;
	}
	if ( function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $url );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $url;
}

function bds_hub_next_html() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$surface = bds_hub_is_directory() ? 'directory' : 'social';
	$sw      = function_exists( 'bds_lang_swap_attrs' );
	$html    = '<section id="bds-hub-next"><h2' . ( $sw ? bds_lang_swap_attrs( 'Do this next', 'Haga esto ahora' ) : '' ) . '>Do this next</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'These doors already exist. This screen does not change a price.', 'Estas puertas ya existen. Esta pantalla no cambia un precio.' );
	} else {
		$html .= '<p>These doors already exist. This screen does not change a price.</p>';
	}
	$html .= '<p>';
	foreach ( bds_hub_next_actions( $surface ) as $action ) {
		$es    = isset( $action['label_es'] ) ? $action['label_es'] : $action['label'];
		$html .= '<a href="' . esc_url( bds_hub_action_url( $action ) ) . '"' . ( $sw ? bds_lang_swap_attrs( $action['label'], $es ) : '' ) . '>' . esc_html( $action['label'] ) . '</a>';
	}
	return $html . '</p></section>';
}

function bds_hub_trial_html() {
	if ( ! bds_hub_is_allowed_host() || ! function_exists( 'bds_hub_trial_view' ) ) {
		return '';
	}
	$state = ( is_user_logged_in() && function_exists( 'bds_ai_trial_state' ) ) ? bds_ai_trial_state( get_current_user_id() ) : null;
	$view  = bds_hub_trial_view( $state );
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$html  = '<section id="bds-hub-trial"><h2' . ( $sw ? bds_lang_swap_attrs( 'AI Business Essentials trial', 'Prueba de AI Business Essentials' ) : '' ) . '>' . esc_html__( 'AI Business Essentials trial', 'branddad' ) . '</h2>';
	$html .= '<p>' . esc_html( $view['line'] ) . '</p>';
	if ( ! empty( $view['cta'] ) ) {
		$html .= '<p><a href="' . esc_url( bds_hub_action_url( array( 'host' => 'social', 'path' => '/product/ai-business-essentials/' ) ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Start 7-day trial', 'Empezar prueba de 7 días' ) : '' ) . '>' . esc_html__( 'Start 7-day trial', 'branddad' ) . '</a></p>';
	}
	return $html . '</section>';
}

function bds_hub_ops_html() {
	if ( ! bds_hub_is_allowed_host() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'bds_hub_ops_inspect_view' ) ) {
		return '';
	}
	$snap = function_exists( 'bds_codex_bridge_ops_snapshot' ) ? bds_codex_bridge_ops_snapshot() : array();
	$view = bds_hub_ops_inspect_view( is_array( $snap ) ? $snap : array() );
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-hub-ops"><h2' . ( $sw ? bds_lang_swap_attrs( 'Billing inspect', 'Inspección de cobro' ) : '' ) . '>' . esc_html__( 'Billing inspect', 'branddad' ) . '</h2>';
	$html .= '<p>' . esc_html( 'State: ' . $view['state'] ) . '</p><ul>';
	foreach ( $view['lines'] as $line ) {
		$html .= '<li>' . esc_html( (string) $line ) . '</li>';
	}
	return $html . '</ul></section>';
}

function bds_hub_account_shortcode() {
	if ( ! bds_hub_is_allowed_host() ) {
		return '';
	}
	$extra = function_exists( 'bds_codex_hub_html' ) ? bds_codex_hub_html() : '';
	return '<div class="bds-hub" id="bds-account-hub">' . bds_hub_next_html() . bds_hub_trial_html() . bds_hub_ops_html() . bds_hub_membership_html() . bds_hub_compare_html() . bds_hub_saved_html() . bds_hub_free_ai_html() . $extra . bds_hub_studio_html() . bds_hub_affiliate_html() . bds_hub_music_html() . '</div>';
}
add_shortcode( 'bds_account_hub', 'bds_hub_account_shortcode' );
add_shortcode( 'bds_membership_status', 'bds_hub_membership_html' );
add_shortcode( 'bds_affiliate_landing', 'bds_hub_affiliate_html' );
add_shortcode( 'bds_studio_boards', 'bds_hub_studio_html' );

function bds_hub_request_path() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return trim( $path, '/' );
}

function bds_hub_dashboard_html() {
	if ( ! function_exists( 'bds_hub_dashboard_copy' ) ) {
		return '';
	}
	$copy = bds_hub_dashboard_copy();
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<section id="bds-hub-dashboard"><h2' . ( $sw ? bds_lang_swap_attrs( $copy['heading'], 'Su panel BrandDad' ) : '' ) . '>' . esc_html( $copy['heading'] ) . '</h2><ul>';
	$es   = array(
		'This screen does not start a charge.' => 'Esta pantalla no inicia un cargo.',
		'A basic Directory listing is free. Featured is a separate monthly option.' => 'Un listado básico es gratis. Featured es una opción mensual aparte.',
		'SEO, Social, and LinkedIn boards stay inquiry only until Codex connects execution.' => 'Los tableros de SEO, redes y LinkedIn siguen en consulta hasta que Codex conecte la ejecución.',
	);
	foreach ( $copy['lines'] as $line ) {
		$line_es = isset( $es[ $line ] ) ? $es[ $line ] : $line;
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( $line, $line_es, 'li' );
		} else {
			$html .= '<li>' . esc_html( $line ) . '</li>';
		}
	}
	$html .= '</ul><p>';
	$html .= '<a href="' . esc_url( 'https://directory.branddad.social/add-listing/' ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a> ';
	$html .= '<a href="' . esc_url( 'https://directory.branddad.social/featured-advertising/' ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Get featured', 'Salir destacado' ) : '' ) . '>' . esc_html__( 'Get featured', 'branddad' ) . '</a> ';
	$html .= '<a href="' . esc_url( home_url( '/studio-boards/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Open studio boards', 'Abrir tableros' ) : '' ) . '>' . esc_html__( 'Open studio boards', 'branddad' ) . '</a>';
	return $html . '</p></section>';
}

function bds_hub_dashboard_footer() {
	if ( ! bds_hub_is_allowed_host() || is_admin() ) {
		return;
	}
	$path = bds_hub_request_path();
	if ( 'dashboard' !== $path && 0 !== strpos( $path, 'dashboard/' ) ) {
		return;
	}
	echo bds_hub_dashboard_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_hub_dashboard_footer', 24 );

function bds_hub_register_pages() {
	if ( ! bds_hub_is_allowed_host() || ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$pages = array(
		'account-hub'       => array( 'Account', '[bds_account_hub]' ),
		'affiliate-program' => array( 'Affiliate program', '[bds_affiliate_landing]' ),
		'studio-boards'     => array( 'Studios', '[bds_studio_boards]' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_name'    => $slug,
				'post_title'   => $page[0],
				'post_content' => $page[1],
			)
		);
	}
}
add_action( 'admin_init', 'bds_hub_register_pages' );

function bds_hub_save_button() {
	if ( ! bds_hub_is_allowed_host() || ! is_singular( 'at_biz_dir' ) ) {
		return;
	}
	$listing_id = get_queried_object_id();
	if ( $listing_id < 1 ) {
		return;
	}
	$sw = function_exists( 'bds_lang_swap_attrs' );
	if ( ! is_user_logged_in() ) {
		echo '<p id="bds-save-listing"' . ( $sw ? bds_lang_swap_attrs( 'Sign in to save this business on your account.', 'Entre para guardar este negocio en su cuenta.' ) : '' ) . '>Sign in to save this business on your account.</p>';
		return;
	}
	echo '<form id="bds-save-listing" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'bds_hub_save_listing' );
	echo '<input type="hidden" name="action" value="bds_hub_save_listing" />';
	echo '<input type="hidden" name="listing_id" value="' . (int) $listing_id . '" />';
	$saved = in_array( $listing_id, bds_hub_saved_ids(), true );
	echo '<input type="hidden" name="save_action" value="' . esc_attr( $saved ? 'remove' : 'save' ) . '" />';
	$label    = $saved ? 'Remove from saved' : 'Save this business';
	$label_es = $saved ? 'Quitar de guardados' : 'Guardar este negocio';
	echo '<button type="submit"' . ( $sw ? bds_lang_swap_attrs( $label, $label_es ) : '' ) . '>' . esc_html( $label ) . '</button></form>';
}
add_action( 'wp_footer', 'bds_hub_save_button', 30 );

function bds_hub_handle_save() {
	if ( ! is_user_logged_in() || ! bds_hub_is_allowed_host() ) {
		wp_die( esc_html__( 'Sign in to save a business.', 'branddad' ), 403 );
	}
	check_admin_referer( 'bds_hub_save_listing' );
	$listing_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
	$action     = isset( $_POST['save_action'] ) ? sanitize_key( wp_unslash( $_POST['save_action'] ) ) : '';
	$post       = get_post( $listing_id );
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
		exit;
	}
	$result = bds_hub_saved_change( bds_hub_saved_ids(), $listing_id, $action );
	if ( ! empty( $result['ok'] ) ) {
		update_user_meta( get_current_user_id(), '_bds_saved_listing_ids', $result['ids'] );
	}
	wp_safe_redirect( get_permalink( $post ) );
	exit;
}
add_action( 'admin_post_bds_hub_save_listing', 'bds_hub_handle_save' );
