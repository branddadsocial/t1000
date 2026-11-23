<?php
/**
 * Plugin Name: BrandDad Sales Help
 * Description: A short helper with checked prices and real links. It does not start a new AI engine or change an account.
 * Version: 0.1.8
 * Author: BrandDad
 *
 * Deploy on branddad.social and directory.branddad.social mu-plugins. The
 * Directory copy stays installed for rollback safety, but only Social renders
 * this helper because Directory has its own contextual Concierge.
 * Answers are fixed. Unknown questions get the start card. Nothing is stored.
 *
 * @package BrandDad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checked answers. Prices here are the ones already verified on the live product and plan pages.
 *
 * @return array<string,array{title:string,body:string,href:string,label:string}>
 */
function bds_sales_help_cards() {
	return array(
		'start'   => array(
			'title' => 'Start with the free listing',
			'body'  => 'You can add your business to the Directory for free. You can also try Business Essentials for 7 days. After the trial, that plan is $29.99 a month. Start with the free listing if money is tight. This does not promise customers.',
			'href'  => 'https://directory.branddad.social/add-listing/',
			'label' => 'List my business free',
		),
		'listing' => array(
			'title' => 'Your Directory listing is free',
			'body'  => 'You can start free. Add your business to the Directory so people can find and contact you. You can also try Business Essentials for 7 days. After the trial, that plan is $29.99 a month. It helps with posts and customer replies. We review a new listing before it goes live. This does not promise customers.',
			'href'  => 'https://directory.branddad.social/add-listing/',
			'label' => 'List my business free',
		),
		'trial'   => array(
			'title' => 'Try business help for 7 days',
			'body'  => 'AI Business Essentials is $29.99 a month. The product page includes a 7-day trial. It helps with posts, replies, and everyday writing. You review the work before it is used. Directory membership is not included. It does not promise sales, followers, or a higher search ranking.',
			'href'  => 'https://branddad.social/product/ai-business-essentials/',
			'label' => 'Try it free for 7 days',
		),
		'find'    => array(
			'title' => 'Find a business',
			'body'  => 'Search the Directory by place or by the kind of business you need. Browsing is free. Open a listing to see what it offers and how to contact it.',
			'href'  => 'https://directory.branddad.social/',
			'label' => 'Search the Directory',
		),
		'person'  => array(
			'title' => 'A person can help with custom work',
			'body'  => 'Use the contact form for a custom project, a billing problem, or something this page does not cover. Ordinary plans can be started from their product pages.',
			'href'  => 'https://branddad.social/contact-us/',
			'label' => 'Contact BrandDad',
		),
		'featured' => array(
			'title' => 'Featured is a separate monthly option',
			'body'  => 'Featured placement starts at $9.99 a month. It stays inactive until WooCommerce confirms payment. A basic Directory listing is free and separate. This does not invent rankings.',
			'href'  => 'https://directory.branddad.social/featured-advertising/',
			'label' => 'Get featured',
		),
		'music'    => array(
			'title' => 'BrandDad Music is inquiry only',
			'body'  => 'Send a brief for original business music. BrandDad confirms scope and licensing before any payment or production. The brief does not take payment.',
			'href'  => 'https://branddad.social/#branddad-music',
			'label' => 'Send a music brief',
		),
		'gift'     => array(
			'title' => 'Gift cards are a Directory marketplace',
			'body'  => 'Gift cards on the Directory are a marketplace. BrandDad does not take that payment on Social. A basic listing is free. Featured is a separate monthly option.',
			'href'  => 'https://directory.branddad.social/gift-card-marketplace/',
			'label' => 'See gift cards',
		),
	);
}

/**
 * Pick a checked card from a short question. The question is not saved.
 *
 * @param string $text Question.
 * @return string
 */
function bds_sales_help_match( $text ) {
	$text = strtolower( (string) $text );
	if ( '' === trim( $text ) ) {
		return 'start';
	}
	if ( false !== strpos( $text, 'person' ) || false !== strpos( $text, 'persona' ) || false !== strpos( $text, 'human' ) || false !== strpos( $text, 'humano' ) || false !== strpos( $text, 'custom' ) || false !== strpos( $text, 'bill' ) || false !== strpos( $text, 'factura' ) ) {
		return 'person';
	}
	if ( false !== strpos( $text, 'featured' ) || false !== strpos( $text, 'destacad' ) ) {
		return 'featured';
	}
	if ( false !== strpos( $text, 'music' ) || false !== strpos( $text, 'musica' ) || false !== strpos( $text, 'música' ) ) {
		return 'music';
	}
	if ( false !== strpos( $text, 'gift' ) || false !== strpos( $text, 'regalo' ) ) {
		return 'gift';
	}
	$owns = false !== strpos( $text, 'own' ) || false !== strpos( $text, 'my business' ) || false !== strpos( $text, 'my shop' ) || false !== strpos( $text, 'mi negocio' ) || false !== strpos( $text, 'mi tienda' );
	if ( ! $owns && ( false !== strpos( $text, 'find' ) || false !== strpos( $text, 'looking' ) || false !== strpos( $text, 'near' ) || false !== strpos( $text, 'cerca' ) || false !== strpos( $text, 'plumber' ) || false !== strpos( $text, 'plomero' ) || false !== strpos( $text, 'search' ) || false !== strpos( $text, 'buscar' ) ) ) {
		return 'find';
	}
	if ( false !== strpos( $text, 'list' ) || false !== strpos( $text, 'listar' ) || false !== strpos( $text, 'directory' ) || false !== strpos( $text, 'directorio' ) || false !== strpos( $text, 'negocio' ) || false !== strpos( $text, 'barber' ) || false !== strpos( $text, 'shop' ) ) {
		return 'listing';
	}
	if ( false !== strpos( $text, 'trial' ) || false !== strpos( $text, 'prueba' ) || false !== strpos( $text, 'probar' ) || false !== strpos( $text, '29' ) || false !== strpos( $text, 'post' ) || false !== strpos( $text, 'essentials' ) ) {
		return 'trial';
	}
	return 'start';
}

/**
 * Spanish questions get the same links and the same prices, in Spanish.
 *
 * @param string $text Question.
 * @return string en or es.
 */
function bds_sales_help_lang( $text ) {
	$text = strtolower( (string) $text );
	foreach ( array( 'negocio', 'listar', 'buscar', 'plomero', 'prueba', 'probar', 'gratis', 'cerca', 'necesito', 'destacad', 'musica', 'música', 'regalo' ) as $word ) {
		if ( false !== strpos( $text, $word ) ) {
			return 'es';
		}
	}
	return 'en';
}

/**
 * @param string $key  Card key.
 * @param string $lang en or es.
 * @return array{title:string,body:string,href:string,label:string}
 */
function bds_sales_help_card( $key, $lang ) {
	$cards = bds_sales_help_cards();
	$key   = isset( $cards[ $key ] ) ? $key : 'start';
	if ( 'es' !== $lang ) {
		return $cards[ $key ];
	}
	$es = array(
		'start'   => array(
			'title' => 'Empiece con el listado gratis',
			'body'  => 'Puede agregar su negocio al directorio gratis. También puede probar Business Essentials por 7 días. Después, ese plan cuesta $29.99 al mes. Esto no promete clientes.',
			'href'  => 'https://directory.branddad.social/add-listing/',
			'label' => 'Listar mi negocio gratis',
		),
		'listing' => array(
			'title' => 'El listado del directorio es gratis',
			'body'  => 'Agregue su negocio para que lo encuentren y lo contacten. Revisamos el listado antes de publicarlo. También puede probar Business Essentials por 7 días. Después cuesta $29.99 al mes. Esto no promete clientes.',
			'href'  => 'https://directory.branddad.social/add-listing/',
			'label' => 'Listar mi negocio gratis',
		),
		'trial'   => array(
			'title' => 'Pruebe la ayuda por 7 días',
			'body'  => 'AI Business Essentials cuesta $29.99 al mes. La página del producto incluye 7 días de prueba. Usted revisa el trabajo antes de usarlo. La membresía del directorio no está incluida. No promete ventas ni una mejor posición en la búsqueda.',
			'href'  => 'https://branddad.social/product/ai-business-essentials/',
			'label' => 'Pruébelo 7 días',
		),
		'find'    => array(
			'title' => 'Encuentre un negocio',
			'body'  => 'Busque en el directorio por lugar o por tipo de negocio. Ver es gratis. Abra el listado para ver qué ofrece y cómo contactarlo.',
			'href'  => 'https://directory.branddad.social/',
			'label' => 'Buscar en el directorio',
		),
		'person'  => array(
			'title' => 'Una persona puede ayudar con un trabajo especial',
			'body'  => 'Use el formulario para un proyecto especial, un problema de cobro, o algo que esta página no cubre. Los planes normales se empiezan en su página de producto.',
			'href'  => 'https://branddad.social/contact-us/',
			'label' => 'Escribir a BrandDad',
		),
		'featured' => array(
			'title' => 'Featured es una opción mensual aparte',
			'body'  => 'Featured empieza en $9.99 al mes. Permanece inactivo hasta que WooCommerce confirme el pago. Un listado básico es gratis y está aparte. Esto no inventa rankings.',
			'href'  => 'https://directory.branddad.social/featured-advertising/',
			'label' => 'Salir destacado',
		),
		'music'    => array(
			'title' => 'BrandDad Music es solo consulta',
			'body'  => 'Envíe un brief para música original de negocio. BrandDad confirma alcance y licencias antes de cualquier pago o producción. El brief no cobra.',
			'href'  => 'https://branddad.social/#branddad-music',
			'label' => 'Enviar un brief de música',
		),
		'gift'     => array(
			'title' => 'Las tarjetas de regalo son un marketplace',
			'body'  => 'Las tarjetas de regalo del directorio son un marketplace. BrandDad no cobra ese pago en Social. Un listado básico es gratis. Featured es una opción mensual aparte.',
			'href'  => 'https://directory.branddad.social/gift-card-marketplace/',
			'label' => 'Ver tarjetas de regalo',
		),
	);
	return $es[ $key ];
}

/**
 * @return bool
 */
function bds_sales_help_on_host() {
	if ( is_admin() ) {
		return false;
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

/**
 * Checkout, account, and studio boards already have their own next step.
 * A second helper on those surfaces is a duplicate assistant.
 *
 * @param mixed $path Request path.
 * @return bool
 */
function bds_sales_help_path_ok( $path ) {
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

/**
 * @return string
 */
function bds_sales_help_question() {
	if ( ! isset( $_GET['bds_help_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$q = wp_strip_all_tags( (string) wp_unslash( $_GET['bds_help_q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( function_exists( 'mb_substr' ) ) {
		return (string) mb_substr( $q, 0, 160 );
	}
	return (string) substr( $q, 0, 160 );
}

/**
 * @return void
 */
function bds_sales_help_footer() {
	if ( ! bds_sales_help_on_host() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) ( function_exists( 'wp_parse_url' ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) ) : '';
	if ( ! bds_sales_help_path_ok( $path ) ) {
		return;
	}
	$question = bds_sales_help_question();
	$card     = bds_sales_help_card( bds_sales_help_match( $question ), bds_sales_help_lang( $question ) );
	echo bds_sales_help_markup( $card ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * The next-step link stays visible. The question form stays inside the details.
 *
 * @param array{title:string,body:string,href:string,label:string} $card Card.
 * @return string
 */
function bds_sales_help_markup( array $card ) {
	$key = 'start';
	foreach ( array( 'en', 'es' ) as $lang ) {
		foreach ( array_keys( bds_sales_help_cards() ) as $try ) {
			$probe = bds_sales_help_card( $try, $lang );
			if ( $probe['title'] === $card['title'] || ( $probe['href'] === $card['href'] && $probe['label'] === $card['label'] ) ) {
				$key = $try;
				break 2;
			}
		}
	}
	$en   = bds_sales_help_card( $key, 'en' );
	$es   = bds_sales_help_card( $key, 'es' );
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html  = '<section id="bds-sales-help" aria-label="Help me choose">';
	$html .= '<style>#bds-sales-help{position:static;background:#fff;border-top:1px solid #d9e2dc;padding:10px 14px}#bds-sales-help form{display:flex;gap:8px;flex-wrap:wrap}#bds-sales-help input{flex:1 1 180px;min-height:44px;padding:8px}#bds-sales-help button,#bds-sales-help a{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 14px;border:0;border-radius:10px;background:#0668d8;color:#fff;font-weight:800;text-decoration:none}#bds-sales-help a:focus-visible,#bds-sales-help button:focus-visible,#bds-sales-help summary:focus-visible{outline:3px solid #0668d8;outline-offset:2px}@media (prefers-reduced-motion:reduce){#bds-sales-help{position:static}}</style>';
	$html .= '<p><a href="' . esc_url( $en['href'] ) . '"' . ( $sw ? bds_lang_swap_attrs( $en['label'], $es['label'] ) : '' ) . '>' . esc_html( $en['label'] ) . '</a></p>';
	$html .= '<details><summary' . ( $sw ? bds_lang_swap_attrs( 'Help me choose', 'Ayúdeme a elegir' ) : '' ) . '>Help me choose</summary>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( $en['body'], $es['body'] );
	} else {
		$html .= '<p>' . esc_html( $card['body'] ) . '</p>';
	}
	$html .= '<form method="get"><label for="bds-help-q"' . ( $sw ? bds_lang_swap_attrs( 'Ask in a few words', 'Pregunte en pocas palabras' ) : '' ) . '>Ask in a few words</label> ';
	$html .= '<input id="bds-help-q" name="bds_help_q" maxlength="160" placeholder="I own a shop and need customers"' . ( $sw ? bds_lang_swap_attrs( 'I own a shop and need customers', 'Tengo un negocio y necesito clientes' ) : '' ) . '> ';
	$html .= '<button type="submit"' . ( $sw ? bds_lang_swap_attrs( 'Show the next step', 'Mostrar el siguiente paso' ) : '' ) . '>Show the next step</button></form>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'BrandDad. These answers use checked prices. This box does not change your account.', 'BrandDad. Estas respuestas usan precios verificados. Esta caja no cambia su cuenta.' );
	} else {
		$html .= '<p>BrandDad. These answers use checked prices. This box does not change your account.</p>';
	}
	$html .= '</details></section>';
	return $html;
}
add_action( 'wp_footer', 'bds_sales_help_footer', 50 );
