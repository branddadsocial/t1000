<?php
/**
 * Plugin Name: BrandDad Revenue Sections
 * Description: Adds the missing Social catalog, package, music-brief, and Directory advertising sections. Checkout stays closed unless the commercial catalog already exposes a live product URL.
 * Version: 0.2.43
 * Author: BrandDad
 *
 * Secure design: public music briefs are untrusted text. Fields are allowlisted, length-capped, and stored as private posts. A honeypot and a per-IP transient cap abuse. No card data is collected. Package price is never taken from the browser. Contact `offer` is sanitize_title against the hard-coded catalog plus featured inquiry slugs. Checkout URLs come only from the commercial catalog when a published product exists. The homepage strip only emits Subscribe when that catalog already returns a URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bds_rev_is_social() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

/**
 * Proposed offers. launch_state staged or quote_only cannot check out from this plugin.
 *
 * @return array<int,array<string,string>>
 */
function bds_rev_offers() {
	return array(
		array( 'slug' => 'ai-business-monitoring', 'name' => 'AI Business Monitoring', 'price' => '$9.99/month', 'state' => 'staged', 'who' => 'A business that wants a simple website check.', 'who_es' => 'Un negocio que quiere una revisión simple del sitio.' ),
		array( 'slug' => 'ai-review-assistant', 'name' => 'AI Review Assistant', 'price' => '$9.99/month', 'state' => 'staged', 'who' => 'A business that replies to reviews itself.', 'who_es' => 'Un negocio que responde reseñas por su cuenta.' ),
		array( 'slug' => 'ai-social-starter', 'name' => 'AI Social Starter', 'price' => '$14.99/month', 'state' => 'staged', 'who' => 'A business posting a small monthly set of drafts.', 'who_es' => 'Un negocio que publica un set pequeño de borradores al mes.' ),
		array( 'slug' => 'ai-lead-assistant', 'name' => 'AI Lead Assistant', 'price' => '$19.99/month', 'state' => 'catalog', 'who' => 'A business capturing inquiries from one form.', 'who_es' => 'Un negocio que captura consultas en un formulario.' ),
		array( 'slug' => 'ai-local-seo', 'name' => 'AI Local SEO Starter', 'price' => '$19.99/month', 'state' => 'catalog', 'who' => 'One website and one primary location.', 'who_es' => 'Un sitio y una ubicación principal.' ),
		array( 'slug' => 'ai-social-growth', 'name' => 'AI Social Growth', 'price' => '$29.99/month', 'state' => 'catalog', 'who' => 'A business that wants a monthly content calendar.', 'who_es' => 'Un negocio que quiere un calendario mensual de contenido.' ),
		array( 'slug' => 'ai-linkedin-growth', 'name' => 'AI LinkedIn Growth', 'price' => '$29.99/month', 'state' => 'staged', 'who' => 'One LinkedIn presence. Automated posting is not included.', 'who_es' => 'Una presencia en LinkedIn. No incluye publicación automática.' ),
		array( 'slug' => 'ai-website-chatbot', 'name' => 'AI Website Chatbot', 'price' => '$29.99/month', 'state' => 'staged', 'who' => 'A site that needs an approved-knowledge assistant.', 'who_es' => 'Un sitio que necesita un asistente con información aprobada.' ),
		array( 'slug' => 'ai-business-essentials', 'name' => 'AI Business Essentials', 'price' => '$29.99/month', 'state' => 'catalog', 'who' => 'The flagship everyday plan.', 'who_es' => 'El plan diario principal.' ),
		array( 'slug' => 'ai-local-seo-autopilot', 'name' => 'AI Local SEO Autopilot', 'price' => '$39.99/month', 'state' => 'staged', 'who' => 'Local SEO execution after Codex confirms the engine.', 'who_es' => 'SEO local ejecutado cuando Codex confirme el motor.' ),
		array( 'slug' => 'ai-business-essentials-pro', 'name' => 'Business Essentials Pro', 'price' => '$49.99/month', 'state' => 'staged', 'who' => 'A busier business that needs higher draft limits.', 'who_es' => 'Un negocio más ocupado que necesita más borradores.' ),
		array( 'slug' => 'ai-seo-growth-autopilot', 'name' => 'AI SEO Growth Autopilot', 'price' => '$59.99/month', 'state' => 'staged', 'who' => 'SEO workflows after fulfillment is verified.', 'who_es' => 'Flujos SEO cuando la entrega esté verificada.' ),
		array( 'slug' => 'ai-seo-pro', 'name' => 'AI SEO Pro', 'price' => '$99.99/month', 'state' => 'staged', 'who' => 'The highest proposed SEO allowance.', 'who_es' => 'La mayor asignación SEO propuesta.' ),
		array( 'slug' => 'complete-ai-starter', 'name' => 'Complete AI Starter', 'price' => 'On request', 'state' => 'quote_only', 'who' => '12 social drafts, 2 channels, 1 autoblog, 100 chatbot conversations. Entitlements are proposed. Price is on request after a scoped quote.', 'who_es' => '12 borradores, 2 canales, 1 autoblog, 100 conversaciones de chatbot. Los derechos son propuestos. El precio es bajo petición después de una cotización.' ),
		array( 'slug' => 'complete-ai-growth', 'name' => 'Complete AI Growth', 'price' => 'On request', 'state' => 'quote_only', 'who' => '30 social drafts, 4 channels, 2 autoblogs, 500 chatbot conversations. Entitlements are proposed. Price is on request after a scoped quote.', 'who_es' => '30 borradores, 4 canales, 2 autoblogs, 500 conversaciones. Los derechos son propuestos. El precio es bajo petición después de una cotización.' ),
		array( 'slug' => 'complete-ai-pro', 'name' => 'Complete AI Pro', 'price' => 'On request', 'state' => 'quote_only', 'who' => '60 social drafts, 6 channels, 4 autoblogs, 1,500 chatbot conversations. Entitlements are proposed. Price is on request after a scoped quote.', 'who_es' => '60 borradores, 6 canales, 4 autoblogs, 1,500 conversaciones. Los derechos son propuestos. El precio es bajo petición después de una cotización.' ),
		array( 'slug' => 'done-for-you-ai', 'name' => 'Done-for-You AI Management', 'price' => 'On request', 'state' => 'quote_only', 'who' => 'A scoped management agreement. Price is on request after a written scope. Not a self-serve checkout.', 'who_es' => 'Un acuerdo con alcance escrito. El precio es bajo petición. No es un checkout de autoservicio.' ),
	);
}

function bds_rev_music_packages() {
	return array(
		'original-single' => array( 'Original Single', '$79' ),
		'premium-single'  => array( 'Premium Single + Artwork', '$129' ),
		'ep-3'            => array( '3-Song EP', '$249' ),
		'album-6'         => array( '6-Song Album', '$449' ),
		'album-12'        => array( '12-Song Album', '$799' ),
		'partnership'     => array( 'Monthly Music Partnership', 'From $199' ),
	);
}

function bds_rev_offer_url( $slug ) {
	$url = '';
	if ( function_exists( 'bds_ai_commercial_checkout_url' ) ) {
		$got = bds_ai_commercial_checkout_url( $slug );
		if ( is_string( $got ) && '' !== $got ) {
			$url = $got;
		}
	}
	if ( '' !== $url && function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $url );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $url;
}

/**
 * Complete AI packages and Done-for-You stay quote-only. Public copy has no dollar price.
 *
 * @param string $slug Offer slug.
 * @return bool
 */
function bds_rev_is_quote_offer( $slug ) {
	return in_array(
		(string) $slug,
		array( 'complete-ai-starter', 'complete-ai-growth', 'complete-ai-pro', 'done-for-you-ai', 'done-for-you-ai-management' ),
		true
	);
}

function bds_rev_card( array $offer ) {
	$url   = bds_rev_offer_url( $offer['slug'] );
	$ask   = add_query_arg( 'offer', rawurlencode( $offer['slug'] ), home_url( '/contact-us/' ) );
	$quote = bds_rev_is_quote_offer( $offer['slug'] );
	$html  = '<article class="bds-rev-card"' . ( $quote ? ' id="' . esc_attr( $offer['slug'] ) . '"' : '' ) . '><h3>' . esc_html( $offer['name'] ) . '</h3>';
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	if ( $quote ) {
		$html .= '<p class="bds-rev-price"' . ( $sw ? bds_lang_swap_attrs( 'On request', 'Bajo petición' ) : '' ) . '>' . esc_html__( 'On request', 'branddad' ) . '</p>';
	} else {
		$html .= '<p class="bds-rev-price">' . esc_html( $offer['price'] ) . '</p>';
	}
	if ( ! empty( $offer['who_es'] ) && function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( $offer['who'], $offer['who_es'] );
	} else {
		$html .= '<p>' . esc_html( $offer['who'] ) . '</p>';
	}
	if ( '' !== $url ) {
		$html .= '<a class="bds-rev-button" href="' . esc_url( $url ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Subscribe monthly', 'Suscribirse cada mes' ) : '' ) . '>' . esc_html__( 'Subscribe monthly', 'branddad' ) . '</a>';
	} elseif ( $quote ) {
		$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( $ask ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Request a scoped quote', 'Pedir una cotización' ) : '' ) . '>' . esc_html__( 'Request a scoped quote', 'branddad' ) . '</a>';
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'This is quote-only. Checkout stays closed until BrandDad scopes the work. It does not start a charge.', 'Esto es solo cotización. El checkout permanece cerrado hasta que BrandDad delimite el trabajo. No inicia un cargo.', 'p', 'bds-rev-note' );
		} else {
			$html .= '<p class="bds-rev-note">' . esc_html__( 'This is quote-only. Checkout stays closed until BrandDad scopes the work. It does not start a charge.', 'branddad' ) . '</p>';
		}
	} else {
		$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( $ask ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Ask about this plan', 'Preguntar por este plan' ) : '' ) . '>' . esc_html__( 'Ask about this plan', 'branddad' ) . '</a>';
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( 'Purchase stays closed until fulfillment and billing are verified.', 'La compra permanece cerrada hasta verificar entrega y cobro.', 'p', 'bds-rev-note' );
		} else {
			$html .= '<p class="bds-rev-note">' . esc_html__( 'Purchase stays closed until fulfillment and billing are verified.', 'branddad' ) . '</p>';
		}
	}
	return $html . '</article>';
}

function bds_rev_dir_url( $path ) {
	$url = 'https://directory.branddad.social' . $path;
	if ( function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $url );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $url;
}

/**
 * Header "Get Featured" currently points at Directory home. Send that item
 * to the Featured buy page. Leave /get-featured/ (Verified pitch) alone.
 *
 * @param string $title Menu label.
 * @param string $url   Current href.
 * @return string
 */
function bds_rev_menu_featured_url( $title, $url ) {
	if ( 0 !== strcasecmp( trim( wp_strip_all_tags( (string) $title ) ), 'Get Featured' ) ) {
		return (string) $url;
	}
	$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
	$path = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
	if ( 'directory.branddad.social' === $host && '' === trim( $path, '/' ) ) {
		return bds_rev_dir_url( '/featured-advertising/' );
	}
	if ( false !== strpos( (string) $path, 'contact-us' ) ) {
		return bds_rev_dir_url( '/featured-advertising/' );
	}
	return (string) $url;
}

/**
 * "AI Packages" is stored as the homepage. Send it to the services catalog.
 *
 * @param string $title Menu label.
 * @param string $url   Current href.
 * @return string
 */
function bds_rev_services_url() {
	$dest = home_url( '/services/' );
	if ( function_exists( 'bds_po_url' ) ) {
		$out = bds_po_url( $dest );
		if ( is_string( $out ) && '' !== $out ) {
			return $out;
		}
	}
	return $dest;
}

/**
 * Homepage and the homepage AI-plans hash are not the catalog.
 *
 * @param string $href Current href.
 * @return string
 */
function bds_rev_packages_href( $href ) {
	$href = (string) $href;
	$host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
	$path = trim( (string) wp_parse_url( $href, PHP_URL_PATH ), '/' );
	$frag = (string) wp_parse_url( $href, PHP_URL_FRAGMENT );
	if ( ! in_array( $host, array( 'branddad.social', 'www.branddad.social', '' ), true ) ) {
		return $href;
	}
	if ( '' !== $path ) {
		return $href;
	}
	if ( '' !== $frag && 'branddad-ai-solutions' !== $frag ) {
		return $href;
	}
	return bds_rev_services_url();
}

function bds_rev_menu_packages_url( $title, $url ) {
	$label = trim( str_replace( array( '→', '&rarr;' ), '', wp_strip_all_tags( (string) $title ) ) );
	if ( 0 !== strcasecmp( $label, 'AI Packages' ) ) {
		return (string) $url;
	}
	return bds_rev_packages_href( $url );
}

function bds_rev_fix_featured_menu( $items ) {
	if ( ! is_array( $items ) || is_admin() || ! bds_rev_is_social() ) {
		return $items;
	}
	foreach ( $items as $item ) {
		if ( ! is_object( $item ) ) {
			continue;
		}
		$title = isset( $item->title ) ? (string) $item->title : '';
		$url   = isset( $item->url ) ? (string) $item->url : '';
		$item->url = bds_rev_menu_packages_url( $title, bds_rev_menu_featured_url( $title, $url ) );
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'bds_rev_fix_featured_menu', 20 );

/**
 * Live launch copy still sends Featured to Contact. Point that one button at the buy page.
 *
 * @param string $html Untrusted page HTML already rendered by WordPress.
 * @return string
 */
function bds_rev_rewrite_featured_ask( $html ) {
	$html = (string) $html;
	if ( false === strpos( $html, 'Ask about Featured placement' ) ) {
		return $html;
	}
	$dest = esc_url( bds_rev_dir_url( '/featured-advertising/' ) );
	$out  = preg_replace_callback(
		'#<a([^>]*?)href="(?:https://(?:www\.)?branddad\.social)?/contact-us/[^"]*"([^>]*)>Ask about Featured placement</a>#',
		static function ( $m ) use ( $dest ) {
			return '<a' . $m[1] . 'href="' . $dest . '"' . $m[2] . '>Get featured</a>';
		},
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_label_link( $html, $label, $href_pattern, $dest, $new_label ) {
	$html = (string) $html;
	$dest = esc_url( (string) $dest );
	if ( '' === $dest || false === strpos( $html, $label ) ) {
		return $html;
	}
	$out = preg_replace_callback(
		'~<a([^>]*?)href="' . $href_pattern . '"([^>]*)>' . preg_quote( $label, '~' ) . '</a>~',
		static function ( $m ) use ( $dest, $new_label ) {
			return '<a' . $m[1] . 'href="' . $dest . '"' . $m[2] . '>' . esc_html( $new_label ) . '</a>';
		},
		$html,
		1
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_choose_plan( $html ) {
	$html = (string) $html;
	if ( false === strpos( $html, 'Choose this plan' ) ) {
		return $html;
	}
	$out = preg_replace(
		'#(<a\b[^>]*href="https://(?:www\.)?branddad\.social/product/(?:ai-lead-assistant|ai-social-growth|ai-local-seo|ai-business-essentials)/[^"]*"[^>]*)>Choose this plan</a>#',
		'$1>Subscribe monthly</a>',
		$html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_subscribe_now( $html ) {
	$html = (string) $html;
	if ( false === strpos( $html, 'Subscribe now' ) && false === strpos( $html, 'Suscribirse ahora' ) ) {
		return $html;
	}
	$out = preg_replace(
		'#(<a\b[^>]*href="[^"]*ai-business-essentials[^"]*"[^>]*data-bds-copy-en=")Subscribe now(")#',
		'$1Start 7-day trial$2',
		$html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="[^"]*ai-business-essentials[^"]*"[^>]*data-bds-copy-es=")Suscribirse ahora(")#',
		'$1Pruébelo 7 días$2',
		is_string( $out ) ? $out : $html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="[^"]*ai-business-essentials[^"]*"[^>]*)>Subscribe now</a>#',
		'$1>Start 7-day trial</a>',
		is_string( $out ) ? $out : $html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="(?:https://(?:www\.)?branddad\.social)?/product/[^"]*"[^>]*data-bds-copy-en=")Subscribe now(")#',
		'$1Subscribe monthly$2',
		is_string( $out ) ? $out : $html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="(?:https://(?:www\.)?branddad\.social)?/product/[^"]*"[^>]*data-bds-copy-es=")Suscribirse ahora(")#',
		'$1Suscribirse cada mes$2',
		is_string( $out ) ? $out : $html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="(?:https://(?:www\.)?branddad\.social)?/product/[^"]*"[^>]*)>Subscribe now</a>#',
		'$1>Subscribe monthly</a>',
		is_string( $out ) ? $out : $html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_see_plans( $html ) {
	$html = (string) $html;
	if ( false === strpos( $html, 'See plans' ) && false === strpos( $html, 'Ver planes' ) ) {
		return $html;
	}
	$out = preg_replace(
		'#data-bds-copy-en="See plans"(\s+data-bds-copy-es=")Ver planes"#',
		'data-bds-copy-en="See membership plans"$1Ver planes de membresía"',
		$html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="[^"]*(?:select-your-plan|registration|\#bds-mt-plans)[^"]*"[^>]*)>See plans</a>#',
		'$1>See membership plans</a>',
		is_string( $out ) ? $out : $html
	);
	return is_string( $out ) ? $out : $html;
}

/**
 * Leftover launch cards still print Complete AI / DFY dollar prices. Hide them.
 *
 * @param string $html Page HTML.
 * @return string
 */
function bds_rev_rewrite_quote_article( $html, $slug ) {
	$html    = (string) $html;
	$pattern = '#(<article\b[^>]*\bid="' . preg_quote( (string) $slug, '#' ) . '"[^>]*>)(.*?)(</article>)#is';
	$out     = preg_replace_callback(
		$pattern,
		static function ( $m ) {
			$inner = $m[2];
			$price = '<p class="bdai-price"><strong>On request</strong></p>';
			if ( function_exists( 'bds_lang_swap_attrs' ) ) {
				$price = '<p class="bdai-price"><strong' . bds_lang_swap_attrs( 'On request', 'Bajo petición' ) . '>On request</strong></p>';
			}
			$inner = preg_replace( '#<p class="bdai-price">.*?</p>#is', $price, $inner );
			$inner = preg_replace( '#<p class="bds-rev-price"[^>]*>.*?</p>#is', '<p class="bds-rev-price">On request</p>', is_string( $inner ) ? $inner : $m[2] );
			$inner = is_string( $inner ) ? $inner : $m[2];
			$inner = str_replace(
				array(
					'Starts at $399 per month after a written scope. This is not an instant checkout product.',
					'Quote-only after a written scope. This is not an instant checkout product.',
					'Desde $399 al mes después de un alcance escrito. No es un producto de checkout inmediato.',
					'Solo cotización después de un alcance escrito. No es un producto de checkout inmediato.',
					'Price can increase with channels, locations, and usage',
					'Scope can change with channels, locations, and usage',
					'El precio puede subir con canales, ubicaciones y uso',
					'El alcance puede cambiar con canales, ubicaciones y uso',
					'$79.99/month',
					'$79.99/mes',
					'$79.99',
					'$149.99/month',
					'$149.99/mes',
					'$149.99',
					'$249.99/month',
					'$249.99/mes',
					'$249.99',
					'Desde $399 al mes',
					'Desde $399',
					'$399.00',
					'From $399/month',
					'from $399.00',
					'from $399/month',
					'From $399',
					'from $399',
					'$399',
				),
				array(
					'Quote-only after a written scope. This is not an instant checkout product.',
					'Quote-only after a written scope. This is not an instant checkout product.',
					'Solo cotización después de un alcance escrito. No es un producto de checkout inmediato.',
					'Solo cotización después de un alcance escrito. No es un producto de checkout inmediato.',
					'Scope can change with channels, locations, and usage',
					'Scope can change with channels, locations, and usage',
					'El alcance puede cambiar con canales, ubicaciones y uso',
					'El alcance puede cambiar con canales, ubicaciones y uso',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
				),
				$inner
			);
			$inner = str_replace(
				array( 'Ask before checkout', 'Ask about this plan' ),
				'Request a scoped quote',
				$inner
			);
			$inner = str_replace(
				array( 'Pregunte antes de pagar', 'Preguntar por este plan' ),
				'Pedir una cotización',
				$inner
			);
			return $m[1] . $inner . $m[3];
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_quote_packages( $html ) {
	$html = (string) $html;
	foreach ( array( 'complete-ai-starter', 'complete-ai-growth', 'complete-ai-pro', 'done-for-you-ai-management', 'done-for-you-ai' ) as $slug ) {
		$html = bds_rev_rewrite_quote_article( $html, $slug );
	}
	$html = str_replace(
		'These bundles show the proposed workload and price. Self-serve checkout stays closed until shared entitlements and fulfillment are verified. Request a quote if you want BrandDad to scope one now.',
		'These bundles show the proposed workload. Request a scoped quote. Self-serve checkout stays closed until BrandDad scopes the work.',
		$html
	);
	$html = str_replace(
		'Estos paquetes muestran la carga y el precio propuesto. El checkout permanece cerrado hasta verificar entregas y derechos. Pida una cotización si quiere que BrandDad lo delimite ahora.',
		'Estos paquetes muestran la carga propuesta. Pida una cotización. El checkout permanece cerrado hasta que BrandDad delimite el trabajo.',
		$html
	);
	$html = str_replace(
		'Bigger plans cost more, and a basic Directory listing is free.',
		'Complete AI packages are quoted separately, and a basic Directory listing is free.',
		$html
	);
	$html = str_replace(
		'Los planes más grandes cuestan más, y un listado básico del directorio es gratis.',
		'Los paquetes Complete AI se cotizan aparte, y un listado básico del directorio es gratis.',
		$html
	);
	$html = str_replace(
		'Bigger packages. Ask before you buy.',
		'Complete AI packages. Request a scoped quote.',
		$html
	);
	$html = str_replace(
		'Paquetes más grandes. Pregunte antes de comprar.',
		'Paquetes Complete AI. Pida una cotización.',
		$html
	);
	if ( false !== strpos( $html, 'id="branddad-complete-packages"' ) ) {
		$stripped = preg_replace( '#<section\b[^>]*\bid="bds-rev-quote-packages"[^>]*>.*?</section>#is', '', $html );
		if ( is_string( $stripped ) ) {
			$html = $stripped;
		}
	}
	$out = preg_replace(
		'#(<a\b[^>]*href="[^"]*offer=(?:complete-ai-starter|complete-ai-growth|complete-ai-pro|done-for-you-ai)[^"]*"[^>]*)>Ask before checkout</a>#',
		'$1>Request a scoped quote</a>',
		$html
	);
	$out = preg_replace(
		'#(<a\b[^>]*href="[^"]*offer=(?:complete-ai-starter|complete-ai-growth|complete-ai-pro|done-for-you-ai)[^"]*"[^>]*)>Ask about this plan</a>#',
		'$1>Request a scoped quote</a>',
		is_string( $out ) ? $out : $html
	);
	return is_string( $out ) ? $out : $html;
}

function bds_rev_rewrite_conversion_ctas( $html ) {
	$html   = bds_rev_rewrite_featured_ask( $html );
	$html   = bds_rev_rewrite_choose_plan( $html );
	$html   = bds_rev_rewrite_subscribe_now( $html );
	$html   = bds_rev_rewrite_see_plans( $html );
	$html   = bds_rev_rewrite_quote_packages( $html );
	$pairs  = array(
		array( 'See social plans', 'ai-social-growth', 'Subscribe to Social Growth', 'ai-social-growth' ),
		array( 'See SEO options', 'ai-local-seo', 'Subscribe to Local SEO', 'ai-local-seo' ),
	);
	foreach ( $pairs as $pair ) {
		$url = bds_rev_offer_url( $pair[1] );
		if ( '' === $url ) {
			continue;
		}
		$hash    = preg_quote( $pair[3], '~' );
		$pattern = '(?:https://(?:www\.)?branddad\.social)?/services/#' . $hash;
		$html    = bds_rev_rewrite_label_link( $html, $pair[0], $pattern, $url, $pair[2] );
	}
	return $html;
}

function bds_rev_rewrite_featured_ask_content( $content ) {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return $content;
	}
	return bds_rev_rewrite_conversion_ctas( $content );
}
add_filter( 'the_content', 'bds_rev_rewrite_featured_ask_content', 1100 );

/**
 * Plain product answers for plans that already have a published price.
 * Staged plans are omitted so a product page cannot sell work that is not ready.
 *
 * @param string $slug Product slug.
 * @return string
 */
function bds_rev_product_plain( $slug ) {
	$pages = array(
		'ai-business-essentials' => array(
			'en' => array(
				'Business help for $29.99 a month',
				'You get help with social posts, customer replies, one simple website check, and a short monthly summary.',
				'You say what the business does. You read the draft before anything is posted or sent.',
				'$29.99 a month. This product includes a 7-day trial. The checkout page shows the trial terms.',
				'It does not post for you, buy ads, or promise customers. A Directory listing is free and separate.',
			),
			'es' => array(
				'Ayuda de negocio por $29.99 al mes',
				'Recibe ayuda con publicaciones, respuestas a clientes, una revisión simple del sitio y un resumen mensual corto.',
				'Diga qué hace el negocio. Lea el borrador antes de publicar o enviar nada.',
				'$29.99 al mes. Este producto incluye 7 días de prueba. El checkout muestra los términos.',
				'No publica por usted, no compra anuncios ni promete clientes. Un listado del directorio es gratis y aparte.',
			),
		),
		'ai-social-growth'       => array(
			'en' => array(
				'Social post drafts for $29.99 a month',
				'You get up to 30 social post drafts a month and a simple content calendar for one business.',
				'You read every draft. Nothing is posted until you approve it.',
				'$29.99 a month. Use the subscribe button on this page.',
				'It does not promise followers or sales. It does not include ads.',
			),
			'es' => array(
				'Borradores de publicaciones por $29.99 al mes',
				'Hasta 30 borradores al mes y un calendario simple para un negocio.',
				'Lea cada borrador. Nada se publica hasta que usted apruebe.',
				'$29.99 al mes. Use el botón de suscribirse en esta página.',
				'No promete seguidores ni ventas. No incluye anuncios.',
			),
		),
		'ai-lead-assistant'      => array(
			'en' => array(
				'Collect customer questions for $19.99 a month',
				'You get one form, email when someone writes, and short summaries of those questions.',
				'You put the form on your site. You decide who to answer.',
				'$19.99 a month. The plan covers up to 50 questions and 20 summaries.',
				'It does not include paid text messages, and it does not promise customers.',
			),
			'es' => array(
				'Reúna preguntas de clientes por $19.99 al mes',
				'Un formulario, correo cuando alguien escribe y resúmenes cortos de esas preguntas.',
				'Ponga el formulario en su sitio. Usted decide a quién responder.',
				'$19.99 al mes. El plan cubre hasta 50 preguntas y 20 resúmenes.',
				'No incluye mensajes de texto de pago ni promete clientes.',
			),
		),
		'ai-local-seo'           => array(
			'en' => array(
				'Search suggestions for $19.99 a month',
				'You get one monthly check of one website and one main location, plus a list of what to improve.',
				'You read the list. You choose what to change.',
				'$19.99 a month. Use the subscribe button on this page.',
				'It does not promise a higher ranking, and it does not change your site by itself.',
			),
			'es' => array(
				'Sugerencias de búsqueda por $19.99 al mes',
				'Una revisión mensual de un sitio y una ubicación principal, más una lista de qué mejorar.',
				'Lea la lista. Usted elige qué cambiar.',
				'$19.99 al mes. Use el botón de suscribirse en esta página.',
				'No promete un mejor ranking y no cambia el sitio por sí solo.',
			),
		),
	);
	$slug = sanitize_title( (string) $slug );
	if ( ! isset( $pages[ $slug ] ) ) {
		return '';
	}
	$rows  = $pages[ $slug ];
	$heads = array(
		array( 'What this is', 'Qué es esto' ),
		array( 'What you get', 'Qué recibe' ),
		array( 'What you do', 'Qué hace usted' ),
		array( 'What it costs', 'Cuánto cuesta' ),
		array( 'What is not included', 'Qué no incluye' ),
	);
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$html  = '<section id="bds-rev-plain" aria-label="What this plan is">';
	foreach ( $heads as $i => $head ) {
		$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( $head[0], $head[1] ) : '' ) . '>' . esc_html( $head[0] ) . '</h2>';
		if ( function_exists( 'bds_lang_pair' ) ) {
			$html .= bds_lang_pair( $rows['en'][ $i ], $rows['es'][ $i ] );
		} else {
			$html .= '<p>' . esc_html( $rows['en'][ $i ] ) . '</p>';
		}
	}
	$cancel_en = 'You can cancel future renewals under the checkout terms.';
	$cancel_es = 'Puede cancelar renovaciones futuras según los términos del checkout.';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( $cancel_en, $cancel_es );
	} else {
		$html .= '<p>' . esc_html__( 'You can cancel future renewals under the checkout terms.', 'branddad' ) . '</p>';
	}
	$html .= bds_rev_product_buy_link( $slug );
	$html .= '</section>';
	return $html;
}

/**
 * A visible subscribe link for a plan that is already for sale.
 * The product id comes from the published catalog product, never from the request.
 *
 * @param string $slug Product slug.
 * @return string
 */
function bds_rev_product_buy_link( $slug ) {
	if ( ! function_exists( 'bds_ai_commercial_offer' ) || ! function_exists( 'get_page_by_path' ) || ! function_exists( 'home_url' ) ) {
		return '';
	}
	$offer = bds_ai_commercial_offer( $slug );
	if ( ! is_array( $offer ) || ! in_array( $offer['launch_state'], array( 'launch_candidate', 'live_legacy' ), true ) ) {
		return '';
	}
	$post = get_page_by_path( sanitize_title( (string) $slug ), OBJECT, 'product' );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return '';
	}
	$trial    = 7 === (int) ( $offer['trial_days'] ?? 0 );
	$label    = $trial ? __( 'Start 7-day trial', 'branddad' ) : __( 'Subscribe monthly', 'branddad' );
	$label_es = $trial ? 'Empiece la prueba de 7 días' : 'Suscribirse cada mes';
	$href     = add_query_arg( 'add-to-cart', (int) $post->ID, home_url( '/checkout/' ) );
	$sw       = function_exists( 'bds_lang_swap_attrs' );
	return '<p><a class="button" href="' . esc_url( $href ) . '"' . ( $sw ? bds_lang_swap_attrs( $label, $label_es ) : '' ) . '>' . esc_html( $label ) . '</a></p>';
}

function bds_rev_product_strip( $content ) {
	if ( ! bds_rev_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $content;
	}
	$slug = function_exists( 'get_post_field' ) ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
	$plain = bds_rev_product_plain( $slug );
	if ( false !== strpos( $content, 'id="bds-rev-plain"' ) ) {
		$plain = '';
	}
	if ( false !== strpos( $content, 'id="bds-rev-home-strip"' ) ) {
		return $content . $plain;
	}
	return $content . $plain . bds_rev_home_strip();
}
add_filter( 'the_content', 'bds_rev_product_strip', 1001 );

function bds_rev_featured_inquiry_offers() {
	return array(
		array( 'slug' => 'featured-local', 'name' => 'Local Featured', 'price' => '$9.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants nearby Directory placement.', 'who_es' => 'Un listado reclamado que quiere aparecer cerca en el directorio.' ),
		array( 'slug' => 'featured-regional', 'name' => 'Regional Featured', 'price' => '$19.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants a wider regional lane.', 'who_es' => 'Un listado reclamado que quiere un carril regional más amplio.' ),
		array( 'slug' => 'featured-national', 'name' => 'National Featured', 'price' => '$39.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants nationwide Directory placement.', 'who_es' => 'Un listado reclamado que quiere aparecer a nivel nacional.' ),
		array( 'slug' => 'featured-global', 'name' => 'Global Homepage Featured', 'price' => '$99.99/month', 'state' => 'staged', 'who' => 'A claimed listing that wants the Directory homepage lane.', 'who_es' => 'Un listado reclamado que quiere el carril de la portada del directorio.' ),
	);
}

function bds_rev_split_offer_cards() {
	$live  = '';
	$ask   = '';
	$quote = '';
	foreach ( bds_rev_offers() as $offer ) {
		if ( '' !== bds_rev_offer_url( $offer['slug'] ) ) {
			$live .= bds_rev_card( $offer );
		} elseif ( bds_rev_is_quote_offer( $offer['slug'] ) ) {
			$quote .= bds_rev_card( $offer );
		} else {
			$ask .= bds_rev_card( $offer );
		}
	}
	return array( $live, $ask, $quote );
}

function bds_rev_markup() {
	$audit     = esc_url( home_url( '/check-your-website/' ) );
	$directory = esc_url( bds_rev_dir_url( '/add-listing/' ) );
	$featured  = esc_url( bds_rev_dir_url( '/featured-advertising/' ) );
	$browse    = esc_url( bds_rev_dir_url( '/' ) );
	list( $live, $ask, $quote ) = bds_rev_split_offer_cards();
	$music = bds_rev_music_options_html();
	ob_start();
	?>
	<div class="bds-rev" id="bds-revenue-sections">
		<section aria-labelledby="bds-rev-audit">
			<?php $sw = function_exists( 'bds_lang_swap_attrs' ); ?>
			<h2 id="bds-rev-audit"<?php echo $sw ? bds_lang_swap_attrs( 'Start with a free AI business audit.', 'Empiece con una auditoría de negocio gratis.' ) : ''; ?>><?php esc_html_e( 'Start with a free AI business audit.', 'branddad' ); ?></h2>
			<?php
			if ( function_exists( 'bds_lang_pair' ) ) {
				echo bds_lang_pair( 'The website check explains what to fix first. It does not invent rankings or promise revenue.', 'La revisión del sitio explica qué corregir primero. No inventa rankings ni promete ingresos.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>' . esc_html__( 'The website check explains what to fix first. It does not invent rankings or promise revenue.', 'branddad' ) . '</p>';
			}
			?>
			<a class="bds-rev-button" href="<?php echo $audit; ?>"<?php echo $sw ? bds_lang_swap_attrs( 'Run the free audit', 'Hacer la auditoría gratis' ) : ''; ?>><?php esc_html_e( 'Run the free audit', 'branddad' ); ?></a>
		</section>
		<?php if ( '' !== $live ) : ?>
		<section aria-labelledby="bds-rev-live">
			<h2 id="bds-rev-live"<?php echo $sw ? bds_lang_swap_attrs( 'Plans you can buy now', 'Planes que puede comprar ahora' ) : ''; ?>><?php esc_html_e( 'Plans you can buy now', 'branddad' ); ?></h2>
			<?php
			if ( function_exists( 'bds_lang_pair' ) ) {
				echo bds_lang_pair( 'These products are already published. Subscribe opens the live checkout. A paid Directory plan keeps its existing 10% where the product allows it. A free listing does not include that price.', 'Estos productos ya están publicados. Subscribe abre el checkout en vivo. Un plan de pago del directorio conserva el 10% donde el producto lo permite. El listado gratis no incluye ese precio.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>' . esc_html__( 'These products are already published. Subscribe opens the live checkout. A paid Directory plan keeps its existing 10% where the product allows it. A free listing does not include that price.', 'branddad' ) . '</p>';
			}
			?>
			<div class="bds-rev-grid"><?php echo $live; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>
		<?php endif; ?>
		<section aria-labelledby="bds-rev-directory">
			<h2 id="bds-rev-directory"<?php echo function_exists( 'bds_lang_swap_attrs' ) ? bds_lang_swap_attrs( 'Free Directory listing and featured placement', 'Listado gratis y espacio destacado' ) : ''; ?>>Free Directory listing and featured placement</h2>
			<?php
			if ( function_exists( 'bds_lang_pair' ) ) {
				echo bds_lang_pair( 'A basic listing is free. Featured is separate: Local $9.99, Regional $19.99, National $39.99, or Global Homepage $99.99 per month. Checkout opens only when that Directory product is already for sale.', 'Un listado básico es gratis. Featured es aparte: Local $9.99, Regional $19.99, National $39.99 o Global Homepage $99.99 al mes. El checkout abre solo si ese producto ya está a la venta.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>A basic listing is free. Featured is separate: Local $9.99, Regional $19.99, National $39.99, or Global Homepage $99.99 per month. Checkout opens only when that Directory product is already for sale.</p>';
			}
			$sw = function_exists( 'bds_lang_swap_attrs' );
			?>
			<a class="bds-rev-button" href="<?php echo $directory; ?>"<?php echo $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : ''; ?>>List my business free</a>
			<a class="bds-rev-button" href="<?php echo $featured; ?>"<?php echo $sw ? bds_lang_swap_attrs( 'Get featured', 'Salir destacado' ) : ''; ?>>Get featured</a>
			<a class="bds-rev-button bds-rev-button--quiet" href="<?php echo $browse; ?>"<?php echo $sw ? bds_lang_swap_attrs( 'See the Directory', 'Ver el directorio' ) : ''; ?>>See the Directory</a>
		</section>
		<?php if ( '' !== $quote && ! function_exists( 'bds_ai_launch_catalog_cards' ) ) : ?>
		<section aria-labelledby="bds-rev-quote" id="bds-rev-quote-packages">
			<h2 id="bds-rev-quote"<?php echo $sw ? bds_lang_swap_attrs( 'Complete AI packages — scoped quote', 'Paquetes Complete AI — cotización' ) : ''; ?>><?php esc_html_e( 'Complete AI packages — scoped quote', 'branddad' ); ?></h2>
			<?php
			if ( function_exists( 'bds_lang_pair' ) ) {
				echo bds_lang_pair( 'Complete AI Starter, Complete AI Growth, Complete AI Pro, and Done-for-You AI Management are on request. Request a scoped quote. These cards do not show a price or start checkout.', 'Complete AI Starter, Complete AI Growth, Complete AI Pro y Done-for-You AI Management son bajo petición. Pida una cotización. Estas tarjetas no muestran un precio ni inician checkout.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>' . esc_html__( 'Complete AI Starter, Complete AI Growth, Complete AI Pro, and Done-for-You AI Management are on request. Request a scoped quote. These cards do not show a price or start checkout.', 'branddad' ) . '</p>';
			}
			?>
			<div class="bds-rev-grid"><?php echo $quote; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>
		<?php endif; ?>
		<section aria-labelledby="bds-rev-music">
			<h2 id="bds-rev-music"<?php echo $sw ? bds_lang_swap_attrs( 'BrandDad Music for Business', 'BrandDad Music para negocios' ) : ''; ?>><?php esc_html_e( 'BrandDad Music for Business', 'branddad' ); ?></h2>
			<?php
			if ( function_exists( 'bds_lang_pair' ) ) {
				echo bds_lang_pair( 'Original music commissioned for a business. Artist T Dash. Songwriter Thaddeus McCollum. Production by BrandDad Music. Sold by BrandDad Social. This form requests a brief. It does not take payment or start production.', 'Música original encargada para un negocio. Artista T Dash. Autor Thaddeus McCollum. Producción BrandDad Music. Venta BrandDad Social. Este formulario pide un brief. No cobra ni empieza la producción.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>' . esc_html__( 'Original music commissioned for a business. Artist T Dash. Songwriter Thaddeus McCollum. Production by BrandDad Music. Sold by BrandDad Social. This form requests a brief. It does not take payment or start production.', 'branddad' ) . '</p>';
			}
			?>
			<?php echo bds_rev_music_form( $music ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php if ( '' !== $ask ) : ?>
		<section aria-labelledby="bds-rev-catalog">
			<h2 id="bds-rev-catalog"<?php echo $sw ? bds_lang_swap_attrs( 'Upcoming plans — inquiry only', 'Planes próximos — solo consulta' ) : ''; ?>><?php esc_html_e( 'Upcoming plans — inquiry only', 'branddad' ); ?></h2>
			<?php
			if ( function_exists( 'bds_lang_pair' ) ) {
				echo bds_lang_pair( 'These prices are proposed. Ask about a plan and we confirm fulfillment before any checkout opens.', 'Estos precios son propuestos. Pregunte por un plan y confirmamos la entrega antes de abrir checkout.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>' . esc_html__( 'These prices are proposed. Ask about a plan and we confirm fulfillment before any checkout opens.', 'branddad' ) . '</p>';
			}
			?>
			<div class="bds-rev-grid"><?php echo $ask; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function bds_rev_home_strip() {
	$GLOBALS['bds_rev_strip_on'] = true;
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$html  = '<section class="bds-rev" id="bds-rev-home-strip">';
	$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'Buy a live plan or get featured', 'Compre un plan en vivo o salga destacado' ) : '' ) . '>' . esc_html__( 'Buy a live plan or get featured', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Subscribe opens only for products already for sale. Featured is a Directory request until that Woo product exists. Music stays a brief, not a sale.', 'Subscribe abre solo si el producto ya está a la venta. Featured es una solicitud del directorio hasta que exista ese producto Woo. La música sigue siendo un brief, no una venta.' );
	} else {
		$html .= '<p>' . esc_html__( 'Subscribe opens only for products already for sale. Featured is a Directory request until that Woo product exists. Music stays a brief, not a sale.', 'branddad' ) . '</p>';
	}
	$html .= '<p>';
	foreach ( bds_rev_offers() as $offer ) {
		$url = bds_rev_offer_url( $offer['slug'] );
		if ( '' === $url ) {
			continue;
		}
		if ( 'ai-business-essentials' === $offer['slug'] ) {
			$label    = __( 'Try it free for 7 days', 'branddad' );
			$label_es = 'Pruébelo 7 días';
		} else {
			$label    = sprintf( __( 'Subscribe to %s', 'branddad' ), $offer['name'] );
			$label_es = sprintf( 'Suscribirse a %s', $offer['name'] );
		}
		$html .= '<a class="bds-rev-button" href="' . esc_url( $url ) . '"' . ( $sw ? bds_lang_swap_attrs( $label, $label_es ) : '' ) . '>' . esc_html( $label ) . '</a>';
	}
	$html .= '<a class="bds-rev-button" href="' . esc_url( bds_rev_dir_url( '/featured-advertising/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Get featured', 'Salir destacado' ) : '' ) . '>' . esc_html__( 'Get featured', 'branddad' ) . '</a>';
	$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( bds_rev_dir_url( '/add-listing/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a>';
	$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( home_url( '/services/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'See all services', 'Ver todos los servicios' ) : '' ) . '>' . esc_html__( 'See all services', 'branddad' ) . '</a>';
	$html .= '</p></section>';
	return $html;
}

function bds_rev_inject_before_final( $content, $block ) {
	$pos = strpos( $content, '<section class="bdai-final"' );
	if ( false !== $pos ) {
		return substr( $content, 0, $pos ) . $block . substr( $content, $pos );
	}
	return $content . $block;
}

function bds_rev_music_options_html( $packages = null ) {
	if ( ! is_array( $packages ) ) {
		$packages = bds_rev_music_packages();
	}
	$html = '';
	foreach ( $packages as $slug => $pack ) {
		if ( ! is_array( $pack ) || ! isset( $pack[0], $pack[1] ) ) {
			continue;
		}
		$html .= '<option value="' . esc_attr( (string) $slug ) . '">' . esc_html( $pack[0] . ' — ' . $pack[1] ) . '</option>';
	}
	return $html;
}

function bds_rev_offer_by_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( 'done-for-you-ai-management' === $slug ) {
		$slug = 'done-for-you-ai';
	}
	if ( '' === $slug ) {
		return null;
	}
	foreach ( array_merge( bds_rev_offers(), bds_rev_featured_inquiry_offers() ) as $offer ) {
		if ( $offer['slug'] === $slug ) {
			return $offer;
		}
	}
	return null;
}

function bds_rev_music_form( $options = '' ) {
	if ( is_array( $options ) || '' === $options ) {
		$options = bds_rev_music_options_html( is_array( $options ) ? $options : null );
	}
	$status = isset( $_GET['bds_music'] ) ? sanitize_key( wp_unslash( $_GET['bds_music'] ) ) : '';
	$note   = 'saved' === $status
		? __( 'Your brief was received. BrandDad will confirm scope and licensing before any payment or production.', 'branddad' )
		: '';
	ob_start();
	if ( '' !== $note ) {
		if ( function_exists( 'bds_lang_pair' ) ) {
			echo bds_lang_pair( $note, 'Recibimos su brief. BrandDad confirmará alcance y licencias antes de cualquier pago o producción.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<p role="status">' . esc_html( $note ) . '</p>';
		}
	}
	$sw = function_exists( 'bds_lang_swap_attrs' );
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'bds_music_brief', 'bds_music_nonce' ); ?>
		<input type="hidden" name="action" value="bds_music_brief" />
		<p class="bds-rev-hp" aria-hidden="true"><label>Company website <input type="text" name="bds_company_url" tabindex="-1" autocomplete="off" /></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Package', 'Paquete' ) : ''; ?>><?php esc_html_e( 'Package', 'branddad' ); ?></span><select name="package" required><?php echo $options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Your name', 'Su nombre' ) : ''; ?>><?php esc_html_e( 'Your name', 'branddad' ); ?></span><input name="customer_name" maxlength="80" required /></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Email', 'Correo' ) : ''; ?>><?php esc_html_e( 'Email', 'branddad' ); ?></span><input name="email" type="email" maxlength="120" required /></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Genre', 'Género' ) : ''; ?>><?php esc_html_e( 'Genre', 'branddad' ); ?></span><input name="genre" maxlength="80" /></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Mood', 'Ánimo' ) : ''; ?>><?php esc_html_e( 'Mood', 'branddad' ); ?></span><input name="mood" maxlength="80" /></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Business description', 'Descripción del negocio' ) : ''; ?>><?php esc_html_e( 'Business description', 'branddad' ); ?></span><textarea name="business" maxlength="800" required></textarea></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Customer story', 'Historia del cliente' ) : ''; ?>><?php esc_html_e( 'Customer story', 'branddad' ); ?></span><textarea name="story" maxlength="800"></textarea></label></p>
		<p><label><span<?php echo $sw ? bds_lang_swap_attrs( 'Usage rights you need', 'Derechos de uso que necesita' ) : ''; ?>><?php esc_html_e( 'Usage rights you need', 'branddad' ); ?></span><textarea name="usage" maxlength="400"></textarea></label></p>
		<button class="bds-rev-button" type="submit"<?php echo $sw ? bds_lang_swap_attrs( 'Send the creative brief', 'Enviar el brief creativo' ) : ''; ?>><?php esc_html_e( 'Send the creative brief', 'branddad' ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}

/**
 * One next step at the end of a guide, the contact page, or the account page.
 * The Essentials link appears only when that product is actually for sale.
 *
 * @return string
 */
function bds_rev_guide_next() {
	$trial = bds_rev_offer_url( 'ai-business-essentials' );
	$html  = '<section class="bds-rev" id="bds-rev-guide-next" aria-label="What to do next">';
	$html .= '<style>#bds-rev-guide-next a{display:inline-flex;align-items:center;min-height:44px;padding:10px 14px;margin:4px 4px 0 0}@media (max-width:480px){#bds-rev-guide-next{padding:16px;box-sizing:border-box}#bds-rev-guide-next a{display:flex;width:100%;box-sizing:border-box;justify-content:center}}</style>';
	$sw = function_exists( 'bds_lang_swap_attrs' );
	$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'What to do next', 'Qué hacer ahora' ) : '' ) . '>' . esc_html__( 'What to do next', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'A Directory listing is free. Business help starts at $29.99 a month, with a 7-day trial on AI Business Essentials. This page does not promise customers.', 'Un listado básico es gratis. La ayuda empieza en $29.99 al mes, con 7 días de prueba en AI Business Essentials. Esta página no promete clientes.', 'p', '' );
	} else {
		$html .= '<p>' . esc_html__( 'A Directory listing is free. Business help starts at $29.99 a month, with a 7-day trial on AI Business Essentials. This page does not promise customers.', 'branddad' ) . '</p>';
	}
	$html .= '<p>';
	if ( '' !== $trial ) {
		$html .= '<a class="bds-rev-button" href="' . esc_url( $trial ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Try it free for 7 days', 'Pruébelo 7 días' ) : '' ) . '>' . esc_html__( 'Try it free for 7 days', 'branddad' ) . '</a>';
	}
	$html .= '<a class="bds-rev-button" href="' . esc_url( bds_rev_dir_url( '/add-listing/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a>';
	$html .= '<a class="bds-rev-button bds-rev-button--quiet" href="' . esc_url( home_url( '/check-your-website/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Check my website', 'Revise mi sitio' ) : '' ) . '>' . esc_html__( 'Check my website', 'branddad' ) . '</a>';
	$html .= '</p></section>';
	return $html;
}

/**
 * Contact and account pages often skip the main content filter.
 *
 * @return void
 */
/**
 * Pages that do not already end in a product, the services catalog, or checkout.
 *
 * @param string $path Request path.
 * @return bool
 */
function bds_rev_path_needs_guide( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	if ( '' === $path || 'services' === $path || 'check-your-website' === $path || 'cart' === $path || 'checkout' === $path || 'shop' === $path || 0 === strpos( $path, 'checkout/' ) ) {
		return false;
	}
	if ( 0 === strpos( $path, 'product/' ) ) {
		return false;
	}
	if ( 'my-account' === $path || 0 === strpos( $path, 'my-account/' ) ) {
		return false;
	}
	if ( 0 === strpos( $path, 'product-category/' ) || 0 === strpos( $path, 'category/' ) || 0 === strpos( $path, 'blog/' ) ) {
		return true;
	}
	$top = strstr( $path, '/', true );
	if ( false === $top ) {
		$top = $path;
	}
	return in_array(
		$top,
		array(
			'help',
			'contact-us',
			'creator-services',
			'spotify-promotion',
			'ai-ads',
			'about',
			'about-us',
			'blog',
			'affiliate-program',
			'learning-center',
			'start-a-campaign',
			'seo',
			'privacy-policy',
			'terms',
			'terms-conditions',
			'refunds',
			'refund-returns',
			'refund_returns',
			'cookie-policy',
			'press',
		),
		true
	);
}

function bds_rev_guide_footer() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	if ( ! bds_rev_path_needs_guide( bds_rev_request_path() ) ) {
		return;
	}
	echo bds_rev_guide_next(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_guide_footer', 27 );

/**
 * An empty cart should offer a real next step. A cart that already has items is left alone.
 *
 * @return void
 */
function bds_rev_empty_cart_footer() {
	if ( ! bds_rev_is_social() || is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart || ! WC()->cart->is_empty() ) {
		return;
	}
	echo bds_rev_guide_next(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_empty_cart_footer', 27 );

/** The shop archive should sell live plans. It does not start a charge. */
function bds_rev_shop_footer() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	$path = bds_rev_request_path();
	$shop = 'shop' === $path || ( function_exists( 'is_shop' ) && is_shop() );
	if ( ! $shop ) {
		return;
	}
	echo bds_rev_home_strip(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_shop_footer', 26 );

/**
 * After checkout, the next step is the free listing. This block does not start another charge.
 *
 * @return string
 */
function bds_rev_onboard_html() {
	$trial = bds_rev_offer_url( 'ai-business-essentials' );
	$html  = '<section class="bds-rev" id="bds-onboard" aria-label="What to do next">';
	$html .= '<style>#bds-onboard{max-width:720px;margin:18px auto;padding:18px 20px;box-sizing:border-box}#bds-onboard a{display:inline-flex;align-items:center;min-height:44px;padding:10px 14px;margin:4px 8px 0 0}#bds-onboard ol{padding-left:1.2rem}@media (max-width:480px){#bds-onboard{padding:16px}#bds-onboard a{display:flex;width:100%;margin:8px 0;justify-content:center;box-sizing:border-box}}</style>';
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'What to do next', 'Qué hacer ahora' ) : '' ) . '>' . esc_html__( 'What to do next', 'branddad' ) . '</h2><ol>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'Start the 7-day trial on AI Business Essentials when you want business help. Checkout shows the terms. This page does not start another charge.', 'Empiece la prueba de 7 días de AI Business Essentials si quiere ayuda. El checkout muestra los términos. Esta página no inicia otro cargo.', 'li' );
		$html .= bds_lang_pair( 'Tell BrandDad what the business does, then read every draft before it is used. The trial does not promise customers.', 'Diga a BrandDad qué hace el negocio y lea cada borrador antes de usarlo. La prueba no promete clientes.', 'li' );
		$html .= bds_lang_pair( 'A Directory listing is free and separate. Directory membership is not included. Add one business if you want people to find it. Cancel future renewals from this account.', 'Un listado del directorio es gratis y está aparte. La membresía del directorio no está incluida. Agregue un negocio si quiere que lo encuentren. Cancele renovaciones futuras desde esta cuenta.', 'li' );
	} else {
		$html .= '<li>' . esc_html__( 'Start the 7-day trial on AI Business Essentials when you want business help. Checkout shows the terms. This page does not start another charge.', 'branddad' ) . '</li>';
		$html .= '<li>' . esc_html__( 'Tell BrandDad what the business does, then read every draft before it is used. The trial does not promise customers.', 'branddad' ) . '</li>';
		$html .= '<li>' . esc_html__( 'A Directory listing is free and separate. Directory membership is not included. Add one business if you want people to find it. Cancel future renewals from this account.', 'branddad' ) . '</li>';
	}
	$html .= '</ol><p>';
	if ( '' !== $trial ) {
		$html .= '<a class="bds-rev-button" href="' . esc_url( $trial ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Try it free for 7 days', 'Pruébelo 7 días' ) : '' ) . '>' . esc_html__( 'Try it free for 7 days', 'branddad' ) . '</a>';
	}
	$html .= '<a class="bds-rev-button" href="' . esc_url( bds_rev_dir_url( '/add-listing/' ) ) . '"' . ( $sw ? bds_lang_swap_attrs( 'List my business free', 'Listar mi negocio gratis' ) : '' ) . '>' . esc_html__( 'List my business free', 'branddad' ) . '</a>';
	$html .= '</p></section>';
	return $html;
}

/**
 * The account area gets the onboarding screen. Other pages keep the shorter guide.
 *
 * @param string $path Request path.
 * @return bool
 */
function bds_rev_path_needs_onboard( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	return 'my-account' === $path || 0 === strpos( $path, 'my-account/' );
}

/**
 * @return void
 */
function bds_rev_onboard_footer() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	if ( ! bds_rev_path_needs_onboard( bds_rev_request_path() ) ) {
		return;
	}
	echo bds_rev_onboard_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_onboard_footer', 27 );

/**
 * After checkout, the next step is the trial screen. This block does not start another charge.
 *
 * @return string
 */
function bds_rev_after_order_html() {
	return bds_rev_onboard_html();
}

/**
 * Thank-you pages use /checkout/order-received/. The rest of checkout is left alone.
 *
 * @param string $path Request path.
 * @return bool
 */
function bds_rev_is_order_received( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	return 0 === strpos( $path, 'checkout/order-received' );
}

/**
 * @return void
 */
function bds_rev_after_order_footer() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	if ( ! bds_rev_is_order_received( bds_rev_request_path() ) ) {
		return;
	}
	echo bds_rev_after_order_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_after_order_footer', 27 );

function bds_rev_append_content( $content ) {
	if ( ! bds_rev_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( is_page( 'services' ) ) {
		if ( false !== strpos( $content, 'id="bds-revenue-sections"' ) ) {
			return $content;
		}
		return bds_rev_inject_before_final( $content, bds_rev_markup() );
	}
	if ( is_page( 'check-your-website' ) ) {
		if ( false !== strpos( $content, 'id="bds-rev-home-strip"' ) ) {
			return $content;
		}
		return $content . bds_rev_home_strip();
	}
	if ( function_exists( 'is_singular' ) && is_singular( 'post' ) ) {
		if ( false !== strpos( $content, 'id="bds-rev-guide-next"' ) ) {
			return $content;
		}
		return $content . bds_rev_guide_next();
	}
	if ( ! is_front_page() ) {
		return $content;
	}
	$block = '';
	if ( false === strpos( $content, 'id="bds-rev-home-strip"' ) ) {
		$block .= bds_rev_home_strip();
	}
	if ( false !== strpos( $content, 'id="branddad-music"' ) && false === strpos( $content, 'id="bds-music-brief"' ) ) {
		$sw     = function_exists( 'bds_lang_swap_attrs' );
		$block .= '<section class="bds-rev" id="bds-music-brief"><h2' . ( $sw ? bds_lang_swap_attrs( 'Send a music brief', 'Enviar un brief de música' ) : '' ) . '>' . esc_html__( 'Send a music brief', 'branddad' ) . '</h2>';
		$block .= function_exists( 'bds_lang_pair' )
			? bds_lang_pair( 'This saves a private request. It does not take payment or start production.', 'Guarda una solicitud privada. No cobra ni empieza la producción.' )
			: ( '<p>' . esc_html__( 'This saves a private request. It does not take payment or start production.', 'branddad' ) . '</p>' );
		$block .= bds_rev_music_form() . '</section>';
	}
	if ( '' === $block ) {
		return $content;
	}
	return bds_rev_inject_before_final( $content, $block );
}
add_filter( 'the_content', 'bds_rev_append_content', 1000 );

function bds_rev_request_path() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return trim( $path, '/' );
}

function bds_rev_404_cta() {
	if ( ! bds_rev_is_social() || is_admin() || ! is_404() ) {
		return;
	}
	echo bds_rev_home_strip(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_404_cta', 24 );

function bds_rev_audit_footer() {
	if ( ! bds_rev_is_social() || is_admin() || ! empty( $GLOBALS['bds_rev_strip_on'] ) ) {
		return;
	}
	if ( 'check-your-website' !== bds_rev_request_path() ) {
		return;
	}
	echo bds_rev_home_strip(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'bds_rev_audit_footer', 26 );

/**
 * Dead Social URLs go to a page that can sell or take a brief.
 *
 * @param string $path Request path without slashes.
 * @return string
 */
function bds_rev_alias_dest( $path ) {
	$path = strtolower( trim( (string) $path, '/' ) );
	if ( 'pricing' === $path || 'price' === $path ) {
		return bds_rev_services_url();
	}
	if ( 'cookie-policy' === $path || 'cookies' === $path ) {
		return home_url( '/privacy-policy/' );
	}
	if ( 'sitemap' === $path || 'site-map' === $path ) {
		return bds_rev_services_url();
	}
	if ( in_array( $path, array( 'faqs', 'frequently-asked-questions', 'how-it-works' ), true ) ) {
		return home_url( '/#bdai-faq-title' );
	}
	if ( in_array( $path, array( 'support', 'help-center' ), true ) ) {
		return home_url( '/contact-us/' );
	}
	if ( 'careers' === $path || 'career' === $path ) {
		return home_url( '/contact-us/' );
	}
	if ( 'team' === $path ) {
		return home_url( '/about-us/' );
	}
	if ( 'jobs' === $path ) {
		return 'https://directory.branddad.social/all-listings/?directory_type=jobs-careers';
	}
	if ( 'classifieds' === $path ) {
		return 'https://directory.branddad.social/all-listings/?directory_type=classifieds';
	}
	if ( 'get-featured' === $path || 'advertise-with-us' === $path ) {
		return 'https://directory.branddad.social/featured-advertising/';
	}
	if ( in_array( $path, array( 'trial', 'start-trial', 'try', 'try-it-free', 'free-trial', 'essentials', 'business-essentials', 'ai-essentials' ), true ) ) {
		return home_url( '/product/ai-business-essentials/' );
	}
	if ( 'get-started' === $path || 'subscribe' === $path ) {
		return bds_rev_services_url();
	}
	if ( 'membership' === $path || 'plans' === $path ) {
		return bds_rev_services_url();
	}
	if ( 'signin' === $path ) {
		return home_url( '/my-account/' );
	}
	if ( 'create-account' === $path ) {
		return 'https://directory.branddad.social/registration/';
	}
	if ( 'quote' === $path || 'inquiry' === $path ) {
		return home_url( '/contact-us/' );
	}
	if ( 'press-kit' === $path ) {
		return home_url( '/press/' );
	}
	if ( 'legal' === $path ) {
		return home_url( '/terms-conditions/' );
	}
	if ( 'upgrade' === $path ) {
		return bds_rev_services_url();
	}
	if ( 'billing' === $path ) {
		return home_url( '/my-account/' );
	}
	if ( 'donate' === $path ) {
		return home_url( '/contact-us/' );
	}
	if ( 'shipping' === $path ) {
		return home_url( '/refund_returns/' );
	}
	if ( 'sign-up' === $path ) {
		return 'https://directory.branddad.social/registration/';
	}
	if ( 'store' === $path ) {
		return home_url( '/shop/' );
	}
	if ( in_array( $path, array( 'partners', 'investors', 'newsletter' ), true ) ) {
		return home_url( '/contact-us/' );
	}
	if ( 'affiliates' === $path ) {
		return 'https://affiliates.branddad.social/program';
	}
	if ( 'signup' === $path ) {
		return 'https://directory.branddad.social/registration/';
	}
	if ( 'claim' === $path ) {
		return 'https://directory.branddad.social/claim-your-business-listing-branddad-directory/';
	}
	if ( 'music' === $path || 'branddad-music' === $path ) {
		return home_url( '/#branddad-music' );
	}
	if ( in_array( $path, array( 'ai-packages', 'packages', 'complete-packages' ), true ) ) {
		$base = bds_rev_services_url();
		return $base . ( false === strpos( $base, '#' ) ? '#branddad-complete-packages' : '' );
	}
	if ( 'ai-seo' === $path ) {
		return bds_rev_services_url();
	}
	if ( in_array( $path, array( 'ai-local-seo-autopilot', 'ai-seo-growth-autopilot', 'ai-seo-pro' ), true ) ) {
		$url = add_query_arg( 'offer', $path, home_url( '/contact-us/' ) );
		if ( function_exists( 'bds_po_url' ) ) {
			$out = bds_po_url( $url );
			if ( is_string( $out ) && '' !== $out ) {
				return $out;
			}
		}
		return $url;
	}
	if ( 'dfy' === $path ) {
		$path = 'done-for-you-ai';
	}
	if ( in_array( $path, array( 'complete-ai-starter', 'complete-ai-growth', 'complete-ai-pro', 'done-for-you-ai' ), true ) ) {
		$url = add_query_arg( 'offer', $path, home_url( '/contact-us/' ) );
		if ( function_exists( 'bds_po_url' ) ) {
			$out = bds_po_url( $url );
			if ( is_string( $out ) && '' !== $out ) {
				return $out;
			}
		}
		return $url;
	}
	return '';
}

function bds_rev_pricing_redirect() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	$dest = bds_rev_alias_dest( bds_rev_request_path() );
	if ( '' === $dest ) {
		return;
	}
	wp_safe_redirect( $dest, 301 );
	exit;
}
add_action( 'template_redirect', 'bds_rev_pricing_redirect', 0 );

/**
 * Social leftover doors that already live on Directory.
 *
 * @param mixed $hosts Allowed hosts.
 * @return array<int,string>
 */
function bds_rev_allowed_redirect_hosts( $hosts ) {
	$hosts = is_array( $hosts ) ? $hosts : array();
	if ( bds_rev_is_social() ) {
		$hosts[] = 'directory.branddad.social';
		$hosts[] = 'affiliates.branddad.social';
	}
	return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'bds_rev_allowed_redirect_hosts' );

/**
 * Theme "About Us" / "Contact us" H1 sits above the real page heading.
 *
 * @param string $html HTML.
 * @return string
 */
function bds_rev_strip_theme_entry_title_h1( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return is_string( $html ) ? $html : '';
	}
	if ( substr_count( strtolower( $html ), '<h1' ) < 2 ) {
		return $html;
	}
	return preg_replace(
		'/<h1\b([^>]*\bentry-title\b[^>]*)>(.*?)<\/h1>/is',
		'<p$1 hidden>$2</p>',
		$html,
		1
	);
}

/**
 * @return void
 */
function bds_rev_heading_buffer_start() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return;
	}
	if ( function_exists( 'is_front_page' ) && is_front_page() ) {
		return;
	}
	if ( function_exists( 'is_page' ) && is_page( 'services' ) ) {
		return;
	}
	ob_start( 'bds_rev_strip_theme_entry_title_h1' );
}
add_action( 'template_redirect', 'bds_rev_heading_buffer_start', 1 );

/**
 * Home, services, and contact still render leftover launch Complete AI prices until File Manager deploys this MU.
 *
 * @return void
 */
function bds_rev_quote_buffer_start() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return;
	}
	$path = bds_rev_request_path();
	if ( '' !== $path && 'services' !== $path && 'contact-us' !== $path ) {
		return;
	}
	ob_start( 'bds_rev_rewrite_quote_packages' );
}
add_action( 'template_redirect', 'bds_rev_quote_buffer_start', 2 );

function bds_rev_package_link_script() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	$dest = wp_json_encode( bds_rev_services_url() );
	$feat = wp_json_encode( bds_rev_dir_url( '/featured-advertising/' ) );
	$line = wp_json_encode( __( 'Your business has enough expenses. Getting help shouldn\'t cost a fortune.', 'branddad' ) );
	echo '<script id="bds-rev-package-link">(function(){var dest=' . $dest . ';var feat=' . $feat . ';var line=' . $line . ';';
	echo 'if(dest){var nodes=document.querySelectorAll("a[data-bds-package-link],#menu-item-53631 > a");for(var i=0;i<nodes.length;i++){var href=nodes[i].getAttribute("href")||"";if(href==="https://branddad.social/"||href==="https://www.branddad.social/"||href==="https://branddad.social/#branddad-ai-solutions"||href==="https://www.branddad.social/#branddad-ai-solutions"){nodes[i].setAttribute("href",dest);}}}';
	echo 'if(feat){var item=document.querySelector("#menu-item-41544 > a");var now=item?item.getAttribute("href")||"":"";if(item&&(/https:\\/\\/directory\\.branddad\\.social\\/?(?:\\?|$)/.test(now)||/\\/contact-us\\/?(?:\\?|$)/.test(now))){item.setAttribute("href",feat);}';
	echo 'document.querySelectorAll("a").forEach(function(a){if((a.textContent||"").replace(/\\s+/g," ").trim()!=="Get Featured")return;var href=a.getAttribute("href")||"";if(/https:\\/\\/directory\\.branddad\\.social\\/?(?:\\?|$)/.test(href)){a.setAttribute("href",feat);}});}';
	echo 'var homes=[],other=false;document.querySelectorAll("h1").forEach(function(h){if(/^\\s*home\\s*$/i.test(h.textContent||""))homes.push(h);else other=true;});';
	echo 'homes.forEach(function(h,i){if(other||i>0){var p=document.createElement("p");p.hidden=true;p.textContent="Home";if(h.parentNode)h.parentNode.replaceChild(p,h);}else if(line){h.textContent=line;}});';
	echo 'if(!document.querySelector("h1")){var raw=(document.title||"").split(/\\s+[|\\u2013\\u2014-]\\s+/)[0].trim();if(raw){var h=document.createElement("h1");h.className="entry-title";h.textContent=raw;var main=document.querySelector("main,#content,.site-content,.site-main")||document.body;main.insertBefore(h,main.firstChild);}}';
	echo '})();</script>';
}
add_action( 'wp_footer', 'bds_rev_package_link_script', 40 );

/**
 * Secure design (untrusted GET): `offer` is sanitize_title, then matched to the
 * hard-coded catalog slug list. Unknown values print nothing. Name/price are
 * esc_html. Quote-only Complete AI / DFY notes never print a dollar amount.
 * No checkout, no storage, no raw query echo.
 */
function bds_rev_offer_note_html( $slug ) {
	$offer = bds_rev_offer_by_slug( $slug );
	if ( ! $offer ) {
		return '';
	}
	$url   = bds_rev_offer_url( $offer['slug'] );
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$quote = bds_rev_is_quote_offer( $offer['slug'] );
	$note  = '<div class="bds-rev-note" id="bds-rev-offer-note">';
	if ( '' !== $url ) {
		$en = sprintf( 'You asked about %1$s (%2$s). Checkout is already open for this plan.', $offer['name'], $offer['price'] );
		$es = sprintf( 'Preguntó por %1$s (%2$s). El checkout ya está abierto para este plan.', $offer['name'], $offer['price'] );
		if ( function_exists( 'bds_lang_pair' ) ) {
			$note .= bds_lang_pair( $en, $es );
		} else {
			$note .= '<p>' . esc_html( $en ) . '</p>';
		}
		$note .= ' <a class="bds-rev-button" href="' . esc_url( $url ) . '"' . ( $sw ? bds_lang_swap_attrs( 'Subscribe monthly', 'Suscribirse cada mes' ) : '' ) . '>' . esc_html__( 'Subscribe monthly', 'branddad' ) . '</a>';
	} elseif ( $quote ) {
		$en = sprintf( 'You asked about %1$s. This is quote-only. Request a scoped quote on this page. Checkout stays closed until BrandDad scopes the work. It does not start a charge.', $offer['name'] );
		$es = sprintf( 'Preguntó por %1$s. Esto es solo cotización. Pida una cotización en esta página. El checkout permanece cerrado hasta que BrandDad delimite el trabajo. No inicia un cargo.', $offer['name'] );
		if ( function_exists( 'bds_lang_pair' ) ) {
			$note .= bds_lang_pair( $en, $es );
		} else {
			$note .= '<p>' . esc_html( $en ) . '</p>';
		}
	} else {
		$en = sprintf( 'You asked about %1$s (%2$s). This page is an inquiry. It does not start checkout.', $offer['name'], $offer['price'] );
		$es = sprintf( 'Preguntó por %1$s (%2$s). Esta página es una consulta. No inicia checkout.', $offer['name'], $offer['price'] );
		if ( function_exists( 'bds_lang_pair' ) ) {
			$note .= bds_lang_pair( $en, $es );
		} else {
			$note .= '<p>' . esc_html( $en ) . '</p>';
		}
	}
	$note .= '</div>';
	return $note;
}

function bds_rev_contact_offer_note( $content ) {
	if ( ! bds_rev_is_social() || is_admin() || ! in_the_loop() || ! is_main_query() || ! is_page( 'contact-us' ) ) {
		return $content;
	}
	$slug = isset( $_GET['offer'] ) ? sanitize_title( wp_unslash( $_GET['offer'] ) ) : '';
	$note = bds_rev_offer_note_html( $slug );
	if ( '' === $note || false !== strpos( $content, 'id="bds-rev-offer-note"' ) ) {
		return $content;
	}
	$GLOBALS['bds_rev_offer_note_printed'] = true;
	return $note . $content;
}
add_filter( 'the_content', 'bds_rev_contact_offer_note', 20 );

/**
 * Elementor contact pages can skip the_content. Print the offer note in the footer
 * and move it above the form so inquiry still has a next step.
 *
 * @return void
 */
function bds_rev_contact_offer_footer() {
	if ( ! bds_rev_is_social() || is_admin() ) {
		return;
	}
	if ( 'contact-us' !== bds_rev_request_path() ) {
		return;
	}
	if ( ! empty( $GLOBALS['bds_rev_offer_note_printed'] ) ) {
		return;
	}
	$slug = isset( $_GET['offer'] ) ? sanitize_title( wp_unslash( $_GET['offer'] ) ) : '';
	$note = bds_rev_offer_note_html( $slug );
	if ( '' === $note ) {
		return;
	}
	echo $note; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<script id="bds-rev-offer-move">(function(){var n=document.getElementById("bds-rev-offer-note");if(!n)return;var m=document.querySelector("main,#content,.site-content,.site-main")||document.body;if(m&&n.parentNode!==m){m.insertBefore(n,m.firstChild);}})();</script>';
}
add_action( 'wp_footer', 'bds_rev_contact_offer_footer', 26 );

function bds_rev_styles() {
	if ( ! bds_rev_is_social() ) {
		return;
	}
	echo '<style id="bds-rev-css">.bds-rev{max-width:1180px;margin:0 auto;padding:12px 20px 28px;color:#0b172a}.bds-rev h2{font-size:clamp(28px,4vw,42px);line-height:1.1;letter-spacing:-.03em}.bds-rev-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.bds-rev-card{display:flex;flex-direction:column;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff}.bds-rev-price{font-size:22px;font-weight:800}.bds-rev-button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;margin:8px 8px 0 0;padding:0 16px;border-radius:10px;background:#0668d8;color:#fff;font-weight:800;text-decoration:none}.bds-rev-button--quiet{background:#071a33}.bds-rev-note{color:#526070;font-size:13px}.bds-rev form p{display:flex;flex-direction:column;gap:6px;max-width:640px}.bds-rev input,.bds-rev select,.bds-rev textarea{min-height:44px;padding:8px;border:1px solid #dbe5ef;border-radius:8px}.bds-rev textarea{min-height:96px}.bds-rev-hp{position:absolute;left:-9999px}.bds-rev-card .bds-rev-button{margin-top:auto}.bds-rev-button:focus-visible,.bds-rev a:focus-visible,#bds-onboard a:focus-visible{outline:3px solid #0668d8;outline-offset:2px}@media(max-width:800px){.bds-rev-grid{grid-template-columns:1fr}.bds-rev{padding:8px 12px 24px}}</style>';
}
add_action( 'wp_head', 'bds_rev_styles', 40 );

function bds_rev_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'bds_music_' . md5( $ip . '|' . wp_salt( 'auth' ) );
}

function bds_rev_handle_music() {
	if ( ! bds_rev_is_social() ) {
		wp_die( esc_html__( 'This form is not available here.', 'branddad' ), 404 );
	}
	if ( ! isset( $_POST['bds_music_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_music_nonce'] ) ), 'bds_music_brief' ) ) {
		wp_die( esc_html__( 'The form expired. Reload the page and try again.', 'branddad' ), 403 );
	}
	if ( ! empty( $_POST['bds_company_url'] ) ) {
		wp_safe_redirect( home_url( '/?bds_music=saved' ) );
		exit;
	}
	$key = bds_rev_client_key();
	if ( (int) get_transient( $key ) >= 5 ) {
		wp_die( esc_html__( 'Too many briefs were sent. Try again later.', 'branddad' ), 429 );
	}
	$packages = bds_rev_music_packages();
	$package  = isset( $_POST['package'] ) ? sanitize_key( wp_unslash( $_POST['package'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$name     = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$business = isset( $_POST['business'] ) ? sanitize_textarea_field( wp_unslash( $_POST['business'] ) ) : '';
	if ( ! isset( $packages[ $package ] ) || ! is_email( $email ) || '' === $name || '' === $business ) {
		wp_die( esc_html__( 'Check the package, name, email, and business description.', 'branddad' ), 400 );
	}
	$body = implode(
		"\n\n",
		array(
			'Package: ' . $packages[ $package ][0] . ' (' . $packages[ $package ][1] . ')',
			'Name: ' . $name,
			'Email: ' . $email,
			'Genre: ' . sanitize_text_field( wp_unslash( $_POST['genre'] ?? '' ) ),
			'Mood: ' . sanitize_text_field( wp_unslash( $_POST['mood'] ?? '' ) ),
			'Business: ' . $business,
			'Story: ' . sanitize_textarea_field( wp_unslash( $_POST['story'] ?? '' ) ),
			'Usage: ' . sanitize_textarea_field( wp_unslash( $_POST['usage'] ?? '' ) ),
			'Payment: not collected. Production: not started.',
		)
	);
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'bds_music_brief',
			'post_status'  => 'private',
			'post_title'   => 'Music brief ' . $package,
			'post_content' => $body,
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		wp_die( esc_html__( 'The brief could not be saved. Try again later.', 'branddad' ), 500 );
	}
	update_post_meta( (int) $post_id, '_bds_music_email', $email );
	update_post_meta( (int) $post_id, '_bds_music_package', $package );
	set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	wp_safe_redirect( home_url( '/?bds_music=saved' ) );
	exit;
}
add_action( 'admin_post_bds_music_brief', 'bds_rev_handle_music' );
add_action( 'admin_post_nopriv_bds_music_brief', 'bds_rev_handle_music' );

function bds_rev_register_brief_type() {
	if ( ! bds_rev_is_social() ) {
		return;
	}
	register_post_type(
		'bds_music_brief',
		array(
			'label'           => 'Music briefs',
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
		)
	);
}
add_action( 'init', 'bds_rev_register_brief_type' );
