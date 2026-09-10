<?php
/**
 * Plugin Name: BrandDad Education → Service Conversion Engine
 * Description: Learning Center tagging, honest DIY vs hire CTAs, course completion, quiz, and privacy-conscious education→service analytics. Teach first; recommend services only when they solve the taught problem.
 * Version: 1.1.2
 * Author: BrandDad
 *
 * Deploy: branddad.social → wp-content/mu-plugins/bds-education-conversion-mu.php
 * Docs: deploy/_education_service_conversion_engine.md
 *
 * Philosophy: Teach honestly first. Recommend services second.
 * Never withhold essential info to force a sale. Never invent DIY impossibility.
 * Never manipulate quiz scores. Never recommend a service just because it exists.
 * Outreach SKUs stay HIDDEN. No suppliers on customer surfaces.
 *
 * v1.1.0 — Hub hero: drop duplicate Books/Courses buttons (paths nav already owns those doors).
 * v1.0.9 — Ecosystem doors: BrandDad.co /get-started/, HostTech /shopping/.
 * v1.0.8 — Learning Center “Check your website” → dedicated /check-your-website/ tool page.
 * v1.0.8 — Academy types + hub strip filter; Explained/glossary stay no-sell; health CTA unchanged.
 * v1.0.7 — Learning Center “Check your website” uses /?bds_health=1 (pretty path 301ed home).
 * v1.0.6 — Learning Center doors to Books and Courses catalogs.
 * v1.0.5 — “A useful week” curriculum on the hub (check → learn → list → hire if useful).
 * v1.0.4 — Learning Center: full BrandDad layout, topic modules, FAQs, blog extras,
 *         Partnero on every hub CTA (site check, Local & Web, AI Ads, Directory, WhatsApp).
 * v1.0.2 — Learning Center “Check your website” entry → Health Check → SEO Audit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_EDU_CONV_VER' ) ) {
	return;
}
define( 'BDS_EDU_CONV_VER', '1.1.2' );
define( 'BDS_EDU_CONV_OPT', 'bds_edu_conv_settings' );
define( 'BDS_EDU_SVC_TAGS_OPT', 'bds_edu_service_tags' );
define( 'BDS_EDU_FUNNEL_OPT', 'bds_edu_funnel_stats' );
define( 'BDS_EDU_TYPE_TAX', 'bds_edu_type' );
define( 'BDS_EDU_TOPIC_TAX', 'bds_edu_topic' );
define( 'BDS_EDU_PROBLEM_TAX', 'bds_edu_problem' );

/* --------------------------------------------------------------------------
 * Settings / defaults
 * -------------------------------------------------------------------------- */

/**
 * @return array<string,mixed>
 */
function bds_edu_defaults() {
	return array(
		'enabled'            => 1,
		'append_ctas'        => 1,
		'append_diy_hire'    => 1,
		'hub_slug'           => 'learning-center',
		'ask_url'            => 'https://wa.me/18729105115?text=' . rawurlencode( 'I have a question after a BrandDad Learning Center guide' ),
		'services_url'       => 'https://branddad.social/services/',
		'analytics'          => 1,
		'ai_suggest'         => 1,
		'hide_outreach'      => 1,
	);
}

/**
 * @return array<string,mixed>
 */
function bds_edu_settings() {
	$s = get_option( BDS_EDU_CONV_OPT, array() );
	if ( ! is_array( $s ) ) {
		$s = array();
	}
	return array_merge( bds_edu_defaults(), $s );
}

/**
 * Preserve Partnero / affiliate ?ref= on Learning Center CTAs.
 *
 * @param string $url Absolute or hash URL.
 * @return string
 */
function bds_edu_po( $url ) {
	$url = (string) $url;
	if ( $url === '' || ( isset( $url[0] ) && '#' === $url[0] ) ) {
		return $url;
	}
	if ( function_exists( 'bds_po_url' ) ) {
		return (string) bds_po_url( $url );
	}
	if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $ref !== '' && false === strpos( $url, 'ref=' ) ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
		}
	}
	return $url;
}

/**
 * Dedicated Health Check tool page (pretty path on Social).
 *
 * @return string
 */
function bds_edu_check_url() {
	if ( function_exists( 'bds_health_entry_url' ) ) {
		return bds_health_entry_url();
	}
	return bds_edu_po( home_url( '/check-your-website/' ) );
}

/**
 * Educational content type → default service recommendation policy.
 *
 * @return array<string,array{label:string,service_mode:string,cta_default:string}>
 */
function bds_edu_content_type_map() {
	$map = array(
		'simple-guide'      => array( 'label' => 'Simple Guide', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'guide'             => array( 'label' => 'Guide', 'service_mode' => 'primary_secondary', 'cta_default' => 'moderate' ),
		'playbook'          => array( 'label' => 'Playbook', 'service_mode' => 'primary_secondary', 'cta_default' => 'moderate' ),
		'course'            => array( 'label' => 'Course', 'service_mode' => 'primary_secondary', 'cta_default' => 'moderate' ),
		'lesson'            => array( 'label' => 'Lesson', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'glossary'          => array( 'label' => 'Glossary', 'service_mode' => 'none', 'cta_default' => 'none' ),
		'algorithm-update'  => array( 'label' => 'Algorithm update', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'resource'          => array( 'label' => 'Resource', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'explained'         => array( 'label' => 'BrandDad Explained', 'service_mode' => 'none', 'cta_default' => 'none' ),
		'why-happening'     => array( 'label' => 'Why is this happening?', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'learning-path'     => array( 'label' => 'Learning path', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'assessment'        => array( 'label' => 'Assessment', 'service_mode' => 'subtle', 'cta_default' => 'subtle' ),
		'kit'               => array( 'label' => 'Kit', 'service_mode' => 'moderate', 'cta_default' => 'moderate' ),
	);
	return apply_filters( 'bds_edu_content_type_map', $map );
}

/**
 * DIY type definitions (A/B/C) → CTA strength.
 *
 * @return array<string,array{label:string,cta_strength:string,blurb:string}>
 */
function bds_edu_diy_types() {
	return array(
		'A' => array(
			'label'        => 'Easy DIY',
			'cta_strength' => 'subtle',
			'blurb'        => 'Most people can do this themselves. We teach the steps clearly — help is optional.',
		),
		'B' => array(
			'label'        => 'DIY possible; help may save time',
			'cta_strength' => 'moderate',
			'blurb'        => 'You can do this yourself with a clear process. Hiring help mainly saves time and reduces guesswork.',
		),
		'C' => array(
			'label'        => 'Complex / ongoing / technical',
			'cta_strength' => 'strong',
			'blurb'        => 'Still learnable — but time, expertise, consistency, monitoring, and scale often make professional help worthwhile.',
		),
	);
}

/**
 * Central tag dimensions for services + education.
 *
 * @return array<string,string[]>
 */
function bds_edu_tag_dimensions() {
	return array(
		'topic'         => array( 'seo', 'local-seo', 'social-media', 'instagram', 'linkedin', 'facebook', 'telegram', 'website', 'hosting', 'directory', 'reputation', 'pr', 'ads', 'branding', 'conversion' ),
		'problem'       => array( 'low-reach', 'not-ranking', 'slow-site', 'few-reviews', 'weak-cta', 'incomplete-gbp', 'broken-site', 'inconsistent-profiles', 'low-visibility', 'ad-waste', 'negative-reviews' ),
		'platform'      => array( 'google', 'instagram', 'linkedin', 'facebook', 'telegram', 'youtube', 'tiktok', 'wordpress', 'whatsapp' ),
		'business_type' => array( 'local', 'online', 'professional', 'creator', 'ecommerce', 'service-business' ),
		'skill'         => array( 'writing', 'technical', 'design', 'analytics', 'strategy', 'ops' ),
		'difficulty'    => array( 'easy', 'moderate', 'advanced' ),
		'intent'        => array( 'learn', 'diagnose', 'implement', 'hire', 'compare' ),
		'funnel_stage'  => array( 'awareness', 'consideration', 'decision', 'retention' ),
		'guide_cat'     => array( 'foundations', 'local', 'social', 'website', 'reputation', 'ads' ),
		'course_cat'    => array( 'foundations', 'local-seo', 'social-growth', 'website-ops' ),
	);
}

/**
 * Default service → tag map (central; admin-editable via option overlay).
 * Never includes outreach.
 *
 * @return array<string,array<string,string[]>>
 */
function bds_edu_default_service_tags() {
	return array(
		'gbp-setup-optimization' => array(
			'topic' => array( 'local-seo', 'directory' ), 'problem' => array( 'incomplete-gbp', 'low-visibility' ),
			'platform' => array( 'google' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'local' ), 'course_cat' => array( 'local-seo' ),
		),
		'website-seo-audit' => array(
			'topic' => array( 'seo', 'website' ), 'problem' => array( 'not-ranking' ),
			'platform' => array( 'google', 'wordpress' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'diagnose' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'foundations', 'website' ), 'course_cat' => array( 'foundations' ),
		),
		'website-seo-fixes' => array(
			'topic' => array( 'seo', 'website' ), 'problem' => array( 'not-ranking' ),
			'platform' => array( 'google', 'wordpress' ), 'difficulty' => array( 'advanced' ), 'intent' => array( 'implement', 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'website' ), 'course_cat' => array( 'foundations' ),
		),
		'comprehensive-seo-packages-rank-1-on-google' => array(
			'topic' => array( 'seo' ), 'problem' => array( 'not-ranking' ),
			'platform' => array( 'google' ), 'difficulty' => array( 'advanced' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'foundations' ), 'course_cat' => array( 'foundations' ),
		),
		'local-seo-management-starter' => array(
			'topic' => array( 'local-seo' ), 'problem' => array( 'not-ranking', 'low-visibility' ),
			'platform' => array( 'google' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'local' ), 'course_cat' => array( 'local-seo' ),
		),
		'local-seo-management-growth' => array(
			'topic' => array( 'local-seo' ), 'problem' => array( 'not-ranking', 'low-visibility' ),
			'platform' => array( 'google' ), 'difficulty' => array( 'advanced' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'local' ), 'course_cat' => array( 'local-seo' ),
		),
		'website-speed-optimization' => array(
			'topic' => array( 'website', 'hosting' ), 'problem' => array( 'slow-site' ),
			'platform' => array( 'wordpress' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'website' ),
		),
		'fix-my-website' => array(
			'topic' => array( 'website' ), 'problem' => array( 'broken-site' ),
			'platform' => array( 'wordpress' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'website' ),
		),
		'monthly-website-care' => array(
			'topic' => array( 'website' ), 'problem' => array( 'broken-site' ),
			'platform' => array( 'wordpress' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'retention' ), 'guide_cat' => array( 'website' ),
		),
		'website-conversion-makeover' => array(
			'topic' => array( 'conversion', 'website' ), 'problem' => array( 'weak-cta' ),
			'platform' => array( 'wordpress' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'website' ),
		),
		'social-profile-optimization-bundle' => array(
			'topic' => array( 'social-media' ), 'problem' => array( 'inconsistent-profiles', 'low-reach' ),
			'platform' => array( 'instagram', 'linkedin', 'facebook' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'social' ), 'course_cat' => array( 'social-growth' ),
		),
		'instagram-viral-growth-discovery-system' => array(
			'topic' => array( 'instagram', 'social-media' ), 'problem' => array( 'low-reach' ),
			'platform' => array( 'instagram' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'social' ), 'course_cat' => array( 'social-growth' ),
		),
		'facebook-growth-visibility-campaigns' => array(
			'topic' => array( 'facebook', 'social-media' ), 'problem' => array( 'low-reach', 'low-visibility' ),
			'platform' => array( 'facebook' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'social' ),
		),
		'telegram-growth-engagement-system' => array(
			'topic' => array( 'telegram', 'social-media' ), 'problem' => array( 'low-reach' ),
			'platform' => array( 'telegram' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'social' ),
		),
		'social-media-management' => array(
			'topic' => array( 'social-media' ), 'problem' => array( 'low-reach' ),
			'platform' => array( 'instagram', 'facebook', 'linkedin' ), 'difficulty' => array( 'advanced' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'social' ), 'course_cat' => array( 'social-growth' ),
		),
		'linkedin-visibility-amplification-system-for-professionals' => array(
			'topic' => array( 'linkedin' ), 'problem' => array( 'low-visibility', 'low-reach' ),
			'platform' => array( 'linkedin' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'social' ),
		),
		'linkedin-viral-posts-for-professionals' => array(
			'topic' => array( 'linkedin' ), 'problem' => array( 'low-reach' ),
			'platform' => array( 'linkedin' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'social' ),
		),
		'business-directory-distribution' => array(
			'topic' => array( 'directory', 'local-seo' ), 'problem' => array( 'low-visibility' ),
			'platform' => array( 'google', 'whatsapp' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'local', 'directory' ),
		),
		'google-review-growth-setup' => array(
			'topic' => array( 'reputation', 'local-seo' ), 'problem' => array( 'few-reviews' ),
			'platform' => array( 'google' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'reputation', 'local' ),
		),
		'google-review-growth-monthly' => array(
			'topic' => array( 'reputation' ), 'problem' => array( 'few-reviews' ),
			'platform' => array( 'google' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'retention' ), 'guide_cat' => array( 'reputation' ),
		),
		'bd-ai-ads-setup' => array(
			'topic' => array( 'ads' ), 'problem' => array( 'ad-waste', 'low-visibility' ),
			'platform' => array( 'facebook', 'instagram' ), 'difficulty' => array( 'moderate' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'ads' ),
		),
		'bd-ai-ads-starter' => array(
			'topic' => array( 'ads' ), 'problem' => array( 'ad-waste' ),
			'platform' => array( 'facebook', 'instagram' ), 'difficulty' => array( 'advanced' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'ads' ),
		),
		'press-release-services' => array(
			'topic' => array( 'pr' ), 'problem' => array( 'low-visibility' ),
			'difficulty' => array( 'moderate' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'reputation' ),
		),
		// Ecosystem doors (not Woo on Social, but routable).
		'bdco-ai-websites' => array(
			'topic' => array( 'website', 'branding' ), 'problem' => array( 'weak-cta', 'broken-site' ),
			'difficulty' => array( 'advanced' ), 'intent' => array( 'hire' ),
			'funnel_stage' => array( 'decision' ), 'guide_cat' => array( 'website' ),
		),
		'hosttech-hosting' => array(
			'topic' => array( 'hosting', 'website' ), 'problem' => array( 'slow-site' ),
			'difficulty' => array( 'easy' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'consideration' ), 'guide_cat' => array( 'website' ),
		),
		'branddad-directory' => array(
			'topic' => array( 'directory', 'local-seo' ), 'problem' => array( 'low-visibility' ),
			'platform' => array( 'whatsapp' ), 'difficulty' => array( 'easy' ), 'intent' => array( 'implement' ),
			'funnel_stage' => array( 'awareness' ), 'guide_cat' => array( 'local', 'directory' ),
		),
	);
}

/**
 * @return array<string,array<string,string[]>>
 */
function bds_edu_service_tags_all() {
	$base = bds_edu_default_service_tags();
	$over = get_option( BDS_EDU_SVC_TAGS_OPT, array() );
	if ( ! is_array( $over ) ) {
		$over = array();
	}
	foreach ( $over as $slug => $tags ) {
		if ( ! is_array( $tags ) ) {
			continue;
		}
		$slug = sanitize_title( (string) $slug );
		if ( bds_edu_is_outreach_slug( $slug ) ) {
			continue;
		}
		$base[ $slug ] = $tags;
	}
	return $base;
}

/**
 * @param string $slug Product slug.
 * @return bool
 */
function bds_edu_is_outreach_slug( $slug ) {
	$slug = strtolower( (string) $slug );
	return ( false !== strpos( $slug, 'outreach' ) || false !== strpos( $slug, 'cold' . '-outreach' ) );
}

/**
 * Cross-ecosystem routing doors.
 *
 * @return array<string,array{label:string,url:string,when:string[]}>
 */
function bds_edu_ecosystem_routes() {
	$routes = array(
		'website'   => array(
			'label' => 'BrandDad.co — logos & websites',
			'url'   => 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=learning_center&utm_campaign=edu_route',
			'when'  => array( 'website', 'branding', 'weak-cta', 'broken-site' ),
		),
		'hosting'   => array(
			'label' => 'HostTech — hosting & performance',
			'url'   => 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=learning_center&utm_campaign=edu_route',
			'when'  => array( 'hosting', 'slow-site' ),
		),
		'directory' => array(
			'label' => 'BrandDad Directory — local visibility',
			'url'   => 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=learning_center&utm_campaign=edu_route',
			'when'  => array( 'directory', 'local-seo', 'low-visibility', 'incomplete-gbp' ),
		),
		'smm'       => array(
			'label' => 'Social Media Growth Services — join Directory to unlock',
			'url'   => 'https://branddad.social/social-media-growth/?utm_source=learning_center&utm_medium=edu_route&utm_campaign=smm_preview',
			'when'  => array( 'social-media', 'instagram', 'linkedin', 'facebook', 'low-reach' ),
		),
		'marketing' => array(
			'label' => 'BrandDad Social — growth services',
			'url'   => 'https://branddad.social/services/?utm_source=learning_center&utm_medium=edu_route',
			'when'  => array( 'social-media', 'seo', 'ads', 'reputation', 'pr', 'low-reach', 'not-ranking' ),
		),
	);
	foreach ( $routes as &$route ) {
		$route['url'] = bds_edu_po( $route['url'] );
	}
	unset( $route );
	return $routes;
}

/**
 * Resolve a service slug to a public URL (Woo product, catalog, or ecosystem).
 *
 * @param string $slug Slug.
 * @return array{slug:string,title:string,url:string}|null
 */
function bds_edu_resolve_service( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug || bds_edu_is_outreach_slug( $slug ) ) {
		return null;
	}

	$eco = array(
		'bdco-ai-websites'  => array( 'title' => 'BrandDad.co websites', 'url' => 'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=learning_center' ),
		'hosttech-hosting'  => array( 'title' => 'HostTech hosting', 'url' => 'https://hosttech.net/shopping/?utm_source=branddad_social&utm_medium=learning_center' ),
		'branddad-directory'=> array( 'title' => 'BrandDad Directory', 'url' => 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=learning_center' ),
	);
	if ( isset( $eco[ $slug ] ) ) {
		$url = $eco[ $slug ]['url'];
		if ( function_exists( 'bds_po_url' ) ) {
			$url = bds_po_url( $url );
		}
		return array( 'slug' => $slug, 'title' => $eco[ $slug ]['title'], 'url' => $url );
	}

	if ( function_exists( 'bds_svc_catalog' ) ) {
		$cat = bds_svc_catalog();
		if ( ! empty( $cat[ $slug ] ) ) {
			$url = $cat[ $slug ]['url'];
			if ( function_exists( 'bds_po_url' ) ) {
				$url = bds_po_url( $url );
			}
			return array(
				'slug'  => $slug,
				'title' => (string) $cat[ $slug ]['title'],
				'url'   => $url,
			);
		}
	}

	if ( function_exists( 'bdsu_services_catalog_rows' ) ) {
		foreach ( bdsu_services_catalog_rows() as $row ) {
			if ( $row[0] === $slug ) {
				$url = 'https://branddad.social/product/' . $slug . '/';
				if ( function_exists( 'bds_po_url' ) ) {
					$url = bds_po_url( $url );
				}
				return array( 'slug' => $slug, 'title' => $row[1], 'url' => $url );
			}
		}
	}

	if ( function_exists( 'wc_get_product_id_by_sku' ) || post_type_exists( 'product' ) ) {
		$p = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $p ) {
			$url = get_permalink( $p );
			if ( function_exists( 'bds_po_url' ) ) {
				$url = bds_po_url( $url );
			}
			return array( 'slug' => $slug, 'title' => get_the_title( $p ), 'url' => $url );
		}
	}

	$url = 'https://branddad.social/product/' . $slug . '/';
	if ( function_exists( 'bds_po_url' ) ) {
		$url = bds_po_url( $url );
	}
	return array(
		'slug'  => $slug,
		'title' => ucwords( str_replace( '-', ' ', $slug ) ),
		'url'   => $url,
	);
}

/**
 * List recommendable services (labels for admin selects).
 *
 * @return array<string,string> slug => title
 */
function bds_edu_service_choices() {
	$out = array();
	foreach ( array_keys( bds_edu_service_tags_all() ) as $slug ) {
		if ( bds_edu_is_outreach_slug( $slug ) ) {
			continue;
		}
		$r = bds_edu_resolve_service( $slug );
		if ( $r ) {
			$out[ $slug ] = $r['title'];
		}
	}
	asort( $out, SORT_NATURAL | SORT_FLAG_CASE );
	return $out;
}

/* --------------------------------------------------------------------------
 * Taxonomies + hub rewrite
 * -------------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		$s = bds_edu_settings();
		if ( empty( $s['enabled'] ) ) {
			return;
		}

		$types = bds_edu_content_type_map();
		register_taxonomy(
			BDS_EDU_TYPE_TAX,
			array( 'post', 'page' ),
			array(
				'labels'            => array(
					'name'          => 'Education types',
					'singular_name' => 'Education type',
				),
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => array( 'slug' => 'learn-type' ),
			)
		);

		register_taxonomy(
			BDS_EDU_TOPIC_TAX,
			array( 'post', 'page', 'product' ),
			array(
				'labels'            => array( 'name' => 'Education topics', 'singular_name' => 'Education topic' ),
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'learn-topic' ),
			)
		);

		register_taxonomy(
			BDS_EDU_PROBLEM_TAX,
			array( 'post', 'page', 'product' ),
			array(
				'labels'            => array( 'name' => 'Problems solved', 'singular_name' => 'Problem' ),
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'learn-problem' ),
			)
		);

		// Seed type terms (v2 adds Academy types).
		if ( ! get_option( 'bds_edu_type_terms_v2' ) ) {
			foreach ( $types as $slug => $row ) {
				if ( ! term_exists( $slug, BDS_EDU_TYPE_TAX ) ) {
					wp_insert_term( $row['label'], BDS_EDU_TYPE_TAX, array( 'slug' => $slug ) );
				}
			}
			update_option( 'bds_edu_type_terms_v1', 1, false );
			update_option( 'bds_edu_type_terms_v2', 1, false );
		}
	},
	20
);

/* --------------------------------------------------------------------------
 * Content meta helpers
 * -------------------------------------------------------------------------- */

/**
 * @param int $post_id Post ID.
 * @return array<string,mixed>
 */
function bds_edu_get_meta( $post_id ) {
	$post_id = (int) $post_id;
	$diy     = (string) get_post_meta( $post_id, '_bds_diy_type', true );
	if ( ! in_array( $diy, array( 'A', 'B', 'C' ), true ) ) {
		$diff = (string) get_post_meta( $post_id, '_bds_diy_difficulty', true );
		if ( 'easy' === $diff ) {
			$diy = 'A';
		} elseif ( 'advanced' === $diff ) {
			$diy = 'C';
		} elseif ( 'moderate' === $diff ) {
			$diy = 'B';
		} else {
			$diy = '';
		}
	}
	$sec = get_post_meta( $post_id, '_bds_secondary_services', true );
	if ( is_string( $sec ) ) {
		$sec = array_filter( array_map( 'sanitize_title', preg_split( '/[\s,]+/', $sec ) ) );
	}
	if ( ! is_array( $sec ) ) {
		$sec = array();
	}

	return array(
		'intent'           => sanitize_key( (string) get_post_meta( $post_id, '_bds_edu_intent', true ) ),
		'diy_difficulty'   => sanitize_key( (string) get_post_meta( $post_id, '_bds_diy_difficulty', true ) ),
		'diy_type'         => $diy,
		'primary_problem'  => sanitize_title( (string) get_post_meta( $post_id, '_bds_primary_problem', true ) ),
		'primary_service'  => sanitize_title( (string) get_post_meta( $post_id, '_bds_primary_service', true ) ),
		'secondary'        => array_values( array_unique( array_map( 'sanitize_title', $sec ) ) ),
		'no_service'       => (int) get_post_meta( $post_id, '_bds_no_service_rec', true ),
		'cta_strength'     => sanitize_key( (string) get_post_meta( $post_id, '_bds_cta_strength', true ) ),
		'next_resource'    => absint( get_post_meta( $post_id, '_bds_next_resource', true ) ),
		'next_course'      => absint( get_post_meta( $post_id, '_bds_next_course', true ) ),
		'next_service'     => sanitize_title( (string) get_post_meta( $post_id, '_bds_next_service', true ) ),
	);
}

/**
 * Effective CTA strength for a post.
 *
 * @param int $post_id Post ID.
 * @return string none|subtle|moderate|strong
 */
function bds_edu_effective_cta_strength( $post_id ) {
	$m = bds_edu_get_meta( $post_id );
	if ( ! empty( $m['no_service'] ) ) {
		return 'none';
	}
	if ( $m['cta_strength'] && in_array( $m['cta_strength'], array( 'none', 'subtle', 'moderate', 'strong' ), true ) ) {
		return $m['cta_strength'];
	}
	if ( $m['diy_type'] && isset( bds_edu_diy_types()[ $m['diy_type'] ] ) ) {
		return bds_edu_diy_types()[ $m['diy_type'] ]['cta_strength'];
	}
	$terms = wp_get_post_terms( $post_id, BDS_EDU_TYPE_TAX, array( 'fields' => 'slugs' ) );
	if ( ! is_wp_error( $terms ) && $terms ) {
		$map = bds_edu_content_type_map();
		foreach ( $terms as $slug ) {
			if ( isset( $map[ $slug ]['cta_default'] ) ) {
				return $map[ $slug ]['cta_default'];
			}
		}
	}
	return 'subtle';
}

/**
 * Services that genuinely match this content (rule: tag/problem overlap + explicit meta).
 *
 * @param int $post_id Post ID.
 * @return array<int,array{slug:string,title:string,url:string,why:string}>
 */
function bds_edu_match_services( $post_id ) {
	$m = bds_edu_get_meta( $post_id );
	if ( ! empty( $m['no_service'] ) ) {
		return array();
	}

	$out = array();
	$seen = array();

	$add = static function ( $slug, $why ) use ( &$out, &$seen ) {
		if ( ! $slug || isset( $seen[ $slug ] ) || bds_edu_is_outreach_slug( $slug ) ) {
			return;
		}
		$r = bds_edu_resolve_service( $slug );
		if ( ! $r ) {
			return;
		}
		$seen[ $slug ] = true;
		$out[]         = array(
			'slug'  => $r['slug'],
			'title' => $r['title'],
			'url'   => $r['url'],
			'why'   => $why,
		);
	};

	if ( $m['primary_service'] ) {
		$add( $m['primary_service'], 'Primary related service for this topic.' );
	}
	foreach ( $m['secondary'] as $slug ) {
		$add( $slug, 'Secondary related service.' );
	}
	if ( $m['next_service'] ) {
		$add( $m['next_service'], 'Recommended next service after learning.' );
	}

	// Tag / problem overlap — only if it solves the taught problem.
	$problems = wp_get_post_terms( $post_id, BDS_EDU_PROBLEM_TAX, array( 'fields' => 'slugs' ) );
	$topics   = wp_get_post_terms( $post_id, BDS_EDU_TOPIC_TAX, array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $problems ) ) {
		$problems = array();
	}
	if ( is_wp_error( $topics ) ) {
		$topics = array();
	}
	if ( $m['primary_problem'] ) {
		$problems[] = $m['primary_problem'];
	}
	$problems = array_unique( array_filter( $problems ) );
	$topics   = array_unique( array_filter( $topics ) );

	if ( $problems || $topics ) {
		foreach ( bds_edu_service_tags_all() as $slug => $tags ) {
			if ( isset( $seen[ $slug ] ) ) {
				continue;
			}
			$svc_problems = isset( $tags['problem'] ) ? (array) $tags['problem'] : array();
			$svc_topics   = isset( $tags['topic'] ) ? (array) $tags['topic'] : array();
			$phits        = array_intersect( $problems, $svc_problems );
			$thits        = array_intersect( $topics, $svc_topics );
			// Require a problem hit when a primary problem is set; otherwise topic+problem soft match.
			if ( $problems && ! $phits ) {
				continue;
			}
			if ( ! $phits && ! $thits ) {
				continue;
			}
			if ( ! $phits && count( $thits ) < 1 ) {
				continue;
			}
			$why = $phits
				? 'Helps with: ' . implode( ', ', array_map( 'bds_edu_humanize', $phits ) ) . '.'
				: 'Related topic: ' . implode( ', ', array_map( 'bds_edu_humanize', $thits ) ) . '.';
			$add( $slug, $why );
			if ( count( $out ) >= 4 ) {
				break;
			}
		}
	}

	return array_slice( $out, 0, 4 );
}

/**
 * @param string $slug Slug.
 * @return string
 */
function bds_edu_humanize( $slug ) {
	return ucwords( str_replace( '-', ' ', (string) $slug ) );
}

/* --------------------------------------------------------------------------
 * CTA components (reusable)
 * -------------------------------------------------------------------------- */

/**
 * @param array<string,mixed> $args Args.
 * @return string
 */
function bds_edu_render_ctas( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_id'   => 0,
			'strength'  => '',
			'context'   => 'article',
			'services'  => null,
			'show_learn'=> true,
			'show_help' => true,
			'show_dfy'  => true,
			'show_ask'  => true,
		)
	);
	$post_id  = (int) $args['post_id'];
	$strength = $args['strength'] ? $args['strength'] : bds_edu_effective_cta_strength( $post_id );
	if ( 'none' === $strength ) {
		return '';
	}

	$services = is_array( $args['services'] ) ? $args['services'] : bds_edu_match_services( $post_id );
	$primary  = $services ? $services[0] : null;
	$meta     = $post_id ? bds_edu_get_meta( $post_id ) : array();
	$settings = bds_edu_settings();

	$learn_url = '';
	if ( ! empty( $meta['next_resource'] ) ) {
		$learn_url = get_permalink( (int) $meta['next_resource'] );
	} elseif ( ! empty( $meta['next_course'] ) ) {
		$learn_url = get_permalink( (int) $meta['next_course'] );
	} else {
		$learn_url = home_url( '/' . sanitize_title( $settings['hub_slug'] ) . '/' );
	}

	$ask_url = ! empty( $settings['ask_url'] ) ? $settings['ask_url'] : 'https://directory.branddad.social/';
	$ask_url   = bds_edu_po( $ask_url );
	$learn_url = bds_edu_po( $learn_url );

	$show_help = ! empty( $args['show_help'] ) && $primary && in_array( $strength, array( 'subtle', 'moderate', 'strong' ), true );
	$show_dfy  = ! empty( $args['show_dfy'] ) && $primary && in_array( $strength, array( 'moderate', 'strong' ), true );
	$show_ask  = ! empty( $args['show_ask'] );
	$show_learn= ! empty( $args['show_learn'] );

	// Type A: only subtle help + keep learning.
	if ( 'subtle' === $strength ) {
		$show_dfy = false;
	}

	ob_start();
	?>
	<aside class="bds-edu-cta" data-bds-edu-cta="<?php echo esc_attr( $strength ); ?>" data-bds-edu-post="<?php echo esc_attr( (string) $post_id ); ?>">
		<p class="bds-edu-cta__eyebrow">What do you want to do next?</p>
		<div class="bds-edu-cta__grid">
			<?php if ( $show_learn ) : ?>
			<a class="bds-edu-cta__card bds-edu-cta__card--learn" href="<?php echo esc_url( $learn_url ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="keep_learning">
				<strong>Keep learning</strong>
				<span>Want to do this yourself? Continue with guides, checklists, or the next lesson.</span>
			</a>
			<?php endif; ?>
			<?php if ( $show_help && $primary ) : ?>
			<a class="bds-edu-cta__card bds-edu-cta__card--help" href="<?php echo esc_url( $primary['url'] ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="get_help" data-bds-edu-svc="<?php echo esc_attr( $primary['slug'] ); ?>">
				<strong>Get help</strong>
				<span><?php echo esc_html( $primary['title'] ); ?> — <?php echo esc_html( $primary['why'] ); ?></span>
			</a>
			<?php endif; ?>
			<?php if ( $show_dfy && $primary ) : ?>
			<a class="bds-edu-cta__card bds-edu-cta__card--dfy" href="<?php echo esc_url( $primary['url'] ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="have_us_do_it" data-bds-edu-svc="<?php echo esc_attr( $primary['slug'] ); ?>">
				<strong>Have us do it</strong>
				<span>Rather have BrandDad handle the work so you can run the business?</span>
			</a>
			<?php endif; ?>
			<?php if ( $show_ask ) : ?>
			<a class="bds-edu-cta__card bds-edu-cta__card--ask" href="<?php echo esc_url( $ask_url ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="not_sure">
				<strong>Not sure?</strong>
				<span>Ask BrandDad — we’ll help pick the next educational step or service that fits.</span>
			</a>
			<?php endif; ?>
		</div>
		<?php if ( 'subtle' === $strength && $primary ) : ?>
			<p class="bds-edu-cta__subtle">Need help? <a href="<?php echo esc_url( $primary['url'] ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="subtle_help" data-bds-edu-svc="<?php echo esc_attr( $primary['slug'] ); ?>">BrandDad can assist</a> — optional, not required.</p>
		<?php endif; ?>
	</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * DIY vs Hire section.
 *
 * @param array<string,mixed> $args Args.
 * @return string
 */
function bds_edu_render_diy_hire( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_id'  => 0,
			'services' => null,
		)
	);
	$post_id  = (int) $args['post_id'];
	$meta     = bds_edu_get_meta( $post_id );
	$diy      = $meta['diy_type'] ? $meta['diy_type'] : 'B';
	$types    = bds_edu_diy_types();
	$services = is_array( $args['services'] ) ? $args['services'] : bds_edu_match_services( $post_id );
	$primary  = $services ? $services[0] : null;

	ob_start();
	?>
	<section class="bds-edu-diyhire" data-bds-edu-diy="<?php echo esc_attr( $diy ); ?>">
		<h2>Should I do this myself or hire someone?</h2>
		<p><?php echo esc_html( $types[ $diy ]['blurb'] ); ?></p>
		<div class="bds-edu-diyhire__cols">
			<div>
				<h3>DIY makes sense when</h3>
				<ul>
					<li>The task is small or clearly scoped</li>
					<li>You have time to learn and iterate</li>
					<li>You enjoy the work</li>
					<li>Technical complexity is low</li>
					<li>Mistakes have limited consequences</li>
				</ul>
			</div>
			<div>
				<h3>Professional help may make sense when</h3>
				<ul>
					<li>Time is limited</li>
					<li>The project is large or ongoing</li>
					<li>Specialized expertise is needed</li>
					<li>Consistency and monitoring matter</li>
					<li>The business depends heavily on the outcome</li>
				</ul>
			</div>
		</div>
		<p class="bds-edu-diyhire__honest"><strong>Yes — you can do this yourself.</strong> Below is how. If you’d rather spend that time running the business, BrandDad can help — only when it solves this problem.</p>
		<?php if ( $primary && 'A' !== $diy ) : ?>
			<p><a class="bds-edu-btn" href="<?php echo esc_url( $primary['url'] ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="diy_hire_service" data-bds-edu-svc="<?php echo esc_attr( $primary['slug'] ); ?>">Service: <?php echo esc_html( $primary['title'] ); ?></a>
			<span class="bds-edu-diyhire__or">or</span>
			<a href="#bds-edu-keep-learning">keep learning</a></p>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Course completion: Learn → Implement → Advance → Hire.
 *
 * @param array<string,mixed> $args Args.
 * @return string
 */
function bds_edu_render_course_complete( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_id'   => get_the_ID(),
			'title'     => '',
			'implement' => '',
			'advance'   => '',
			'hire'      => '',
		)
	);
	$post_id = (int) $args['post_id'];
	$meta    = bds_edu_get_meta( $post_id );
	$title   = $args['title'] ? $args['title'] : get_the_title( $post_id );
	$svcs    = bds_edu_match_services( $post_id );
	$hire    = $args['hire'] ? $args['hire'] : ( $svcs ? $svcs[0]['url'] : bds_edu_settings()['services_url'] );
	$impl    = $args['implement'];
	if ( ! $impl && ! empty( $meta['next_resource'] ) ) {
		$impl = get_permalink( (int) $meta['next_resource'] );
	}
	$adv = $args['advance'];
	if ( ! $adv && ! empty( $meta['next_course'] ) ) {
		$adv = get_permalink( (int) $meta['next_course'] );
	}
	$hub = home_url( '/' . sanitize_title( bds_edu_settings()['hub_slug'] ) . '/' );

	ob_start();
	?>
	<section class="bds-edu-complete" data-bds-edu-event-view="course_complete">
		<p class="bds-edu-cta__eyebrow">Course complete</p>
		<h2>Great — you now understand <?php echo esc_html( $title ); ?>.</h2>
		<p>Choose your next path. Learning first; hiring only if it saves real time or risk.</p>
		<ol class="bds-edu-complete__path">
			<li><strong>Learn</strong> — done.</li>
			<li><strong>Implement</strong> — <a href="<?php echo esc_url( $impl ? $impl : $hub ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="implement">Put it into practice yourself</a></li>
			<li><strong>Advance</strong> — <a href="<?php echo esc_url( $adv ? $adv : $hub ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="advance">Continue learning</a></li>
			<li><strong>Hire</strong> — <a href="<?php echo esc_url( $hire ); ?>" data-bds-edu-event="cta_click" data-bds-edu-cta-type="hire">Have BrandDad help</a></li>
		</ol>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Marketing assessment quiz — educational recs first; honest scores.
 *
 * @return string
 */
function bds_edu_render_quiz() {
	ob_start();
	?>
	<div class="bds-edu-quiz" id="bds-edu-quiz" data-bds-edu-quiz="1">
		<p class="bds-edu-cta__eyebrow">Honest self-check</p>
		<h2>Business marketing assessment</h2>
		<p>Score yourself 1–5 on each area. Scores are calculated from your answers only — we do not adjust them to sell services.</p>
		<form class="bds-edu-quiz__form">
			<?php
			$axes = array(
				'seo'        => 'SEO / search visibility',
				'social'     => 'Social media presence',
				'conversion' => 'Website conversion',
				'visibility' => 'Brand / directory visibility',
			);
			foreach ( $axes as $key => $label ) :
				?>
				<label><?php echo esc_html( $label ); ?>
					<select name="<?php echo esc_attr( $key ); ?>" required>
						<option value="">Choose…</option>
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<option value="<?php echo (int) $i; ?>"><?php echo (int) $i; ?> — <?php echo esc_html( 1 === $i ? 'Needs work' : ( 5 === $i ? 'Strong' : 'OK' ) ); ?></option>
						<?php endfor; ?>
					</select>
				</label>
			<?php endforeach; ?>
			<button type="submit" class="bds-edu-btn">Show my results</button>
		</form>
		<div class="bds-edu-quiz__out" hidden></div>
	</div>
	<?php
	return (string) ob_get_clean();
}

add_shortcode(
	'bds_edu_ctas',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'post_id' => get_the_ID(), 'strength' => '' ), $atts, 'bds_edu_ctas' );
		return bds_edu_render_ctas( array( 'post_id' => (int) $atts['post_id'], 'strength' => $atts['strength'] ) );
	}
);
add_shortcode(
	'bds_edu_diy_hire',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'post_id' => get_the_ID() ), $atts, 'bds_edu_diy_hire' );
		return bds_edu_render_diy_hire( array( 'post_id' => (int) $atts['post_id'] ) );
	}
);
add_shortcode(
	'bds_edu_course_complete',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'post_id' => get_the_ID(), 'title' => '' ), $atts, 'bds_edu_course_complete' );
		return bds_edu_render_course_complete( array( 'post_id' => (int) $atts['post_id'], 'title' => $atts['title'] ) );
	}
);
add_shortcode( 'bds_edu_quiz', 'bds_edu_render_quiz' );
add_shortcode(
	'bds_learning_center',
	static function () {
		return bds_edu_render_hub();
	}
);

/**
 * Curated Learning Center topic modules (always on the hub — not padding).
 *
 * @return array<int,array{kicker:string,title:string,blurb:string,learn:string[],cta:string,url:string,hire:string}>
 */
function bds_edu_hub_topics() {
	$svc   = bds_edu_po( home_url( '/services/' ) );
	$local = bds_edu_po( home_url( '/services/' ) . '#lane-local' );
	$ai    = bds_edu_po( home_url( '/services/' ) . '#lane-ai' );
	$dir   = bds_edu_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=learning_center&utm_campaign=edu_topics' );
	$check = bds_edu_check_url();
	return array(
		array(
			'kicker' => 'Local visibility',
			'title'  => 'Google Business Profile & local search',
			'blurb'  => 'Most local customers start on Google. Completing categories, photos, hours, and reviews is learnable — messy NAP data and weak GBP are where help pays off.',
			'learn'  => array( 'Claim and complete GBP', 'Match name / address / phone everywhere', 'Ask for reviews the honest way' ),
			'cta'    => 'Start with a free site check',
			'url'    => $check,
			'hire'   => 'If you want it done: Local & Web →',
			'hire_url' => $local,
		),
		array(
			'kicker' => 'Website SEO',
			'title'  => 'Titles, speed, and pages that can rank',
			'blurb'  => 'SEO is not a mystery package. You can fix titles, headings, and slow pages yourself. An audit is useful when you cannot tell what is actually blocking rankings.',
			'learn'  => array( 'One clear intent per page', 'Fix crawl errors and thin pages first', 'Speed is a ranking and conversion issue' ),
			'cta'    => 'Check your website',
			'url'    => $check,
			'hire'   => 'See Website SEO Audit →',
			'hire_url' => bds_edu_po( home_url( '/product/website-seo-audit/' ) ),
		),
		array(
			'kicker' => 'Social',
			'title'  => 'Profiles that match the business',
			'blurb'  => 'Inconsistent bios, missing links, and random posting waste reach. Polish the profile first; posting cadence second. Growth services only after the basics exist.',
			'learn'  => array( 'Same name, link, and offer on every profile', 'A bio that states who you help', 'A posting cadence you can keep' ),
			'cta'    => 'Browse social services',
			'url'    => $svc,
			'hire'   => 'Social Profile Optimization →',
			'hire_url' => bds_edu_po( home_url( '/product/social-profile-optimization-bundle/' ) ),
		),
		array(
			'kicker' => 'Conversion',
			'title'  => 'A website that asks for the next step',
			'blurb'  => 'Traffic without a clear CTA is a leak. You can rewrite headlines and buttons yourself. Hire a makeover when the whole funnel (trust, offer, form) is unclear.',
			'learn'  => array( 'One primary action above the fold', 'Proof near the ask (reviews, examples)', 'Remove competing buttons' ),
			'cta'    => 'See conversion help',
			'url'    => bds_edu_po( home_url( '/product/website-conversion-makeover/' ) ),
			'hire'   => 'Browse Local & Web →',
			'hire_url' => $local,
		),
		array(
			'kicker' => 'Directory',
			'title'  => 'Get found by people nearby',
			'blurb'  => 'A complete BrandDad Directory listing is a visibility asset — WhatsApp, hours, and a real business page. Members also save 10% on eligible services.',
			'learn'  => array( 'List the business with a real WhatsApp', 'Keep categories honest', 'Use the listing as a trust page' ),
			'cta'    => 'Open the Directory',
			'url'    => $dir,
			'hire'   => 'List your business →',
			'hire_url' => bds_edu_po( 'https://directory.branddad.social/registration/?redirect_to=' . rawurlencode( 'https://directory.branddad.social/add-listing/' ) . '&utm_source=branddad_social&utm_medium=learning_center' ),
		),
		array(
			'kicker' => 'AI Ads',
			'title'  => 'Paid reach after the foundations',
			'blurb'  => 'Ads amplify what already works. If the site, offer, and tracking are weak, spend leaks. Learn the funnel first — then consider AI Ads when you have something worth amplifying.',
			'learn'  => array( 'Landing page must match the ad', 'Start small and measure', 'Do not buy ads to fix a broken site' ),
			'cta'    => 'See AI Ads plans',
			'url'    => $ai,
			'hire'   => 'Check the site first →',
			'hire_url' => $check,
		),
	);
}

/**
 * Learning Center hub markup.
 *
 * @return string
 */
function bds_edu_render_hub() {
	$settings = bds_edu_settings();
	$check    = bds_edu_po( add_query_arg( 'bds_health', '1', home_url( '/' ) ) );
	$svc      = bds_edu_po( ! empty( $settings['services_url'] ) ? $settings['services_url'] : home_url( '/services/' ) );
	$local    = bds_edu_po( home_url( '/services/' ) . '#lane-local' );
	$ai       = bds_edu_po( home_url( '/services/' ) . '#lane-ai' );
	$dir      = bds_edu_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=learning_center&utm_campaign=edu_hub' );
	$wa       = bds_edu_po( ! empty( $settings['ask_url'] ) ? $settings['ask_url'] : ( 'https://wa.me/18729105115?text=' . rawurlencode( 'I have a question after a BrandDad Learning Center guide' ) ) );
	$blog     = bds_edu_po( home_url( '/blog/' ) );
	$books    = bds_edu_po( home_url( '/books/' ) );
	$courses  = bds_edu_po( home_url( '/courses/' ) );
	$topics   = bds_edu_hub_topics();

	$q = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 36,
			'tax_query'              => array(
				array(
					'taxonomy' => BDS_EDU_TYPE_TAX,
					'operator' => 'EXISTS',
				),
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);
	$tagged_ids = array();
	if ( $q->have_posts() ) {
		$tagged_ids = wp_list_pluck( $q->posts, 'ID' );
	}
	$more = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 9,
			'post__not_in'           => $tagged_ids,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$faqs = array(
		array(
			'q' => 'Where are books and certificate courses?',
			'a' => 'Books are one-time purchases at /books/. Certificate courses are one-time enrollments at /courses/ — lessons, an ID-scored quiz, and a verifiable certificate. Free Learning Center guides stay free.',
		),
		array(
			'q' => 'Do I have to buy a service to use the Learning Center?',
			'a' => 'No. Guides are written to be usable on their own. Services show up only when they solve the same problem the guide taught — never as a requirement.',
		),
		array(
			'q' => 'What does the free website check actually do?',
			'a' => 'It reads public signals (title, speed clues, mobile, basic SEO) and points to the next honest step — often a DIY fix, sometimes a Website SEO Audit or Local & Web work. It is not a fake score.',
		),
		array(
			'q' => 'When should I DIY vs hire BrandDad?',
			'a' => 'DIY if the steps are clear and you have a few hours. Hire when the work is ongoing (rankings, ads, reviews), technical, or costing you leads every week you wait.',
		),
		array(
			'q' => 'What is the difference between Directory and BrandDad Social?',
			'a' => 'Directory is where customers find and WhatsApp a local business. Social is where you buy growth work (SEO, social, ads, reputation). Directory members save 10% on eligible Social services.',
		),
		array(
			'q' => 'Should I buy ads before the website is ready?',
			'a' => 'Usually no. Fix the page, offer, and tracking first. AI Ads is for amplifying something that already converts — not for hiding a slow or confusing site.',
		),
		array(
			'q' => 'Where do logos and hosting live?',
			'a' => 'Logos and premium websites are on BrandDad.co. Hosting is on HostTech. This Learning Center stays focused on marketing, SEO, and visibility — with doors to those sites when the topic calls for it.',
		),
	);

	$paths = array(
		array( 'Check your website', 'Free public-signal scan. Results name the real next fix.', $check, 'Start free →' ),
		array( 'Books', 'One-time educational guides on SEO, local, social, and conversion.', $books, 'Browse books →' ),
		array( 'Courses', 'Enroll once, take lessons, pass a quiz, earn a verifiable certificate.', $courses, 'Browse courses →' ),
		array( 'Local & Web', 'GBP, SEO, speed, and conversion — buy only the job you need.', $local, 'See Local & Web →' ),
		array( 'AI Ads', 'Paid reach after the site and offer can convert.', $ai, 'See AI Ads →' ),
		array( 'Directory', 'Get found locally. Members save 10% on eligible services.', $dir, 'Open Directory →' ),
		array( 'WhatsApp', 'Ask a human when the guide is not enough.', $wa, 'Message BrandDad →' ),
	);

	ob_start();
	?>
	<div class="bds-edu-hub" data-bds-edu-hub="<?php echo esc_attr( BDS_EDU_CONV_VER ); ?>">
		<header class="bds-edu-hub__hero">
			<p class="bds-edu-hub__eyebrow">BrandDad Learning Center</p>
			<h1>Learn the work. Hire only if it helps.</h1>
			<p>Guides, playbooks, and an honest assessment — then optional BrandDad services when professional help actually solves the problem you just learned.</p>
			<div class="bds-edu-hub__actions">
				<a class="bds-edu-hub__btn" href="<?php echo esc_url( $check ); ?>">Check your website</a>
				<a class="bds-edu-hub__btn bds-edu-hub__btn--ghost" href="#bds-edu-quiz-anchor">Take the honest assessment</a>
				<a class="bds-edu-hub__btn bds-edu-hub__btn--ghost" href="<?php echo esc_url( $svc ); ?>">Browse services</a>
			</div>
			<ul class="bds-edu-hub__trust">
				<li>Free site check</li>
				<li>DIY steps first</li>
				<li>No outreach upsells</li>
			</ul>
		</header>
		<?php echo apply_filters( 'bds_edu_hub_after_hero', '', $check, $books, $courses ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<nav class="bds-edu-hub__paths" aria-label="Useful next steps">
			<?php foreach ( $paths as $path ) : ?>
				<a href="<?php echo esc_url( $path[2] ); ?>">
					<strong><?php echo esc_html( $path[0] ); ?></strong>
					<span><?php echo esc_html( $path[1] ); ?></span>
					<b><?php echo esc_html( $path[3] ); ?></b>
				</a>
			<?php endforeach; ?>
		</nav>

		<section class="bds-edu-hub__how" aria-label="How this works">
			<div>
				<p class="bds-edu-hub__eyebrow">How this works</p>
				<h2>Three honest steps.</h2>
				<p>We teach the real work. We do not withhold steps to force a sale. Services appear only when they match the problem.</p>
			</div>
			<ol>
				<li><b>01</b><div><strong>Learn</strong><span>Read a guide or take the assessment. Know what “good” looks like before you buy.</span></div></li>
				<li><b>02</b><div><strong>Check</strong><span>Run the free website check or finish a Directory listing. See the gap in public signals, not a sales script.</span></div></li>
				<li><b>03</b><div><strong>Hire if useful</strong><span>Local &amp; Web, AI Ads, or WhatsApp help — only when DIY would cost more time than it saves.</span></div></li>
			</ol>
		</section>

		<section class="bds-edu-hub__week" aria-label="A useful week of work">
			<div class="bds-edu-hub__intro">
				<p class="bds-edu-hub__eyebrow">A useful week</p>
				<h2>Do this in order. Buy nothing until step four.</h2>
			</div>
			<ol>
				<li><a href="<?php echo esc_url( $check ); ?>"><b>Day 1</b><strong>Run the free website check</strong><span>Public signals only — titles, speed clues, mobile. Not a fake score.</span></a></li>
				<li><a href="#bds-edu-topics"><b>Day 2–3</b><strong>Read one topic module</strong><span>GBP / SEO / social / conversion — pick the leak you actually have.</span></a></li>
				<li><a href="<?php echo esc_url( $dir ); ?>"><b>Day 4</b><strong>Complete a Directory listing</strong><span>WhatsApp + honest categories. Members save 10% if you later hire.</span></a></li>
				<li><a href="<?php echo esc_url( $local ); ?>"><b>Day 5+</b><strong>Hire only if DIY is costing leads</strong><span>Local &amp; Web, then AI Ads if the page can convert. WhatsApp if you are stuck.</span></a></li>
			</ol>
		</section>

		<section class="bds-edu-hub__modules" id="bds-edu-topics">
			<div class="bds-edu-hub__intro">
				<p class="bds-edu-hub__eyebrow">Start with a topic</p>
				<h2>What to learn, then what to do.</h2>
				<p>Each module is a real path: the skill, the DIY bar, and the BrandDad door if you would rather not do it yourself.</p>
			</div>
			<div class="bds-edu-hub__modgrid">
				<?php foreach ( $topics as $mod ) : ?>
					<article>
						<span><?php echo esc_html( $mod['kicker'] ); ?></span>
						<h3><?php echo esc_html( $mod['title'] ); ?></h3>
						<p><?php echo esc_html( $mod['blurb'] ); ?></p>
						<ul>
							<?php foreach ( $mod['learn'] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="bds-edu-hub__modctas">
							<a href="<?php echo esc_url( $mod['url'] ); ?>"><?php echo esc_html( $mod['cta'] ); ?></a>
							<a class="is-quiet" href="<?php echo esc_url( $mod['hire_url'] ); ?>"><?php echo esc_html( $mod['hire'] ); ?></a>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="bds-edu-hub__guides">
			<div class="bds-edu-hub__intro">
				<p class="bds-edu-hub__eyebrow">Guides &amp; playbooks</p>
				<h2>Read the work, then decide.</h2>
			</div>
			<div class="bds-edu-hub__grid">
				<?php if ( $q->have_posts() ) : ?>
					<?php
					while ( $q->have_posts() ) :
						$q->the_post();
						$types = wp_get_post_terms( get_the_ID(), BDS_EDU_TYPE_TAX, array( 'fields' => 'names' ) );
						$type  = ( ! is_wp_error( $types ) && $types ) ? $types[0] : 'Guide';
						$plink = bds_edu_po( get_permalink() );
						?>
						<article>
							<span><?php echo esc_html( $type ); ?></span>
							<h3><a href="<?php echo esc_url( $plink ); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() ), 28 ) ); ?></p>
							<a class="bds-edu-hub__read" href="<?php echo esc_url( $plink ); ?>">Read guide →</a>
						</article>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p>Tagged guides are still landing. Use the topic modules above or the <a href="<?php echo esc_url( $blog ); ?>">blog</a> meanwhile.</p>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( $more->have_posts() ) : ?>
		<section class="bds-edu-hub__guides bds-edu-hub__guides--more">
			<div class="bds-edu-hub__intro">
				<p class="bds-edu-hub__eyebrow">From the blog</p>
				<h2>More to read.</h2>
			</div>
			<div class="bds-edu-hub__grid">
				<?php
				while ( $more->have_posts() ) :
					$more->the_post();
					$plink = bds_edu_po( get_permalink() );
					?>
					<article>
						<span><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
						<h3><a href="<?php echo esc_url( $plink ); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() ), 24 ) ); ?></p>
						<a class="bds-edu-hub__read" href="<?php echo esc_url( $plink ); ?>">Read →</a>
					</article>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</div>
			<p class="bds-edu-hub__moreblog"><a href="<?php echo esc_url( $blog ); ?>">All blog posts →</a></p>
		</section>
		<?php endif; ?>

		<section class="bds-edu-hub__faq">
			<div>
				<p class="bds-edu-hub__eyebrow">Straight answers</p>
				<h2>Before you buy anything.</h2>
			</div>
			<div class="bds-edu-hub__faqlist">
				<?php foreach ( $faqs as $faq ) : ?>
					<details>
						<summary><?php echo esc_html( $faq['q'] ); ?></summary>
						<p><?php echo esc_html( $faq['a'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<div id="bds-edu-quiz-anchor" class="bds-edu-hub__quizwrap"><?php echo bds_edu_render_quiz(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<section class="bds-edu-hub__final">
			<div>
				<p class="bds-edu-hub__eyebrow">Optional next step</p>
				<h2>Still stuck after the guide?</h2>
				<p>Check the site, browse Local &amp; Web / AI Ads, list the business, or message us. None of these are required to keep learning.</p>
			</div>
			<div class="bds-edu-hub__finalbtns">
				<a class="bds-edu-hub__btn" href="<?php echo esc_url( $check ); ?>">Check your website</a>
				<a class="bds-edu-hub__btn bds-edu-hub__btn--light" href="<?php echo esc_url( $svc ); ?>">Browse services</a>
				<a class="bds-edu-hub__btn bds-edu-hub__btn--light" href="<?php echo esc_url( $dir ); ?>">Directory</a>
				<a class="bds-edu-hub__btn bds-edu-hub__btn--light" href="<?php echo esc_url( $wa ); ?>">WhatsApp</a>
			</div>
		</section>
	</div>
	<?php
	return (string) ob_get_clean();
}

/* --------------------------------------------------------------------------
 * Front: append CTAs / DIY-hire on educational posts
 * -------------------------------------------------------------------------- */

add_filter(
	'the_content',
	static function ( $content ) {
		$s = bds_edu_settings();
		if ( empty( $s['enabled'] ) || empty( $s['append_ctas'] ) ) {
			return $content;
		}
		if ( ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		$terms  = wp_get_post_terms( $post_id, BDS_EDU_TYPE_TAX, array( 'fields' => 'slugs' ) );
		$meta   = bds_edu_get_meta( $post_id );
		$is_edu = ( ! is_wp_error( $terms ) && $terms ) || $meta['diy_type'] || $meta['primary_service'] || $meta['primary_problem'] || ! empty( $meta['no_service'] );
		if ( ! $is_edu ) {
			return $content;
		}
		if ( false !== strpos( $content, 'data-bds-edu-cta=' ) || false !== strpos( $content, 'bds-edu-diyhire' ) ) {
			return $content;
		}

		$extra = '';
		$map   = bds_edu_content_type_map();
		$type  = ( ! is_wp_error( $terms ) && $terms ) ? $terms[0] : '';
		$mode  = ( $type && isset( $map[ $type ]['service_mode'] ) ) ? $map[ $type ]['service_mode'] : 'primary_secondary';
		if ( get_post_meta( $post_id, '_bds_learn_hub', true ) ) {
			return $content;
		}
		if ( ! empty( $meta['no_service'] ) || in_array( $type, array( 'glossary', 'explained' ), true ) ) {
			$mode = 'none';
		}

		if ( ! empty( $s['append_diy_hire'] ) && in_array( $meta['diy_type'], array( 'B', 'C' ), true ) && 'none' !== $mode && false === strpos( $content, 'bds-edu-diyhire' ) ) {
			$extra .= "\n" . bds_edu_render_diy_hire( array( 'post_id' => $post_id ) );
		}

		if ( 'none' !== $mode && 'none' !== bds_edu_effective_cta_strength( $post_id ) ) {
			$extra .= "\n" . bds_edu_render_ctas( array( 'post_id' => $post_id ) );
		}

		// Ecosystem door when relevant (not on no-service / glossary).
		if ( 'none' !== $mode ) {
			$routes  = bds_edu_ecosystem_routes();
			$t_slugs = wp_get_post_terms( $post_id, BDS_EDU_TOPIC_TAX, array( 'fields' => 'slugs' ) );
			$p_slugs = wp_get_post_terms( $post_id, BDS_EDU_PROBLEM_TAX, array( 'fields' => 'slugs' ) );
			if ( is_wp_error( $t_slugs ) ) {
				$t_slugs = array();
			}
			if ( is_wp_error( $p_slugs ) ) {
				$p_slugs = array();
			}
			$needles = array_filter( array_merge( $t_slugs, $p_slugs, array( $meta['primary_problem'] ) ) );
			$doors   = array();
			foreach ( $routes as $key => $route ) {
				if ( array_intersect( $needles, $route['when'] ) ) {
					$doors[] = '<a href="' . esc_url( $route['url'] ) . '" data-bds-edu-event="cta_click" data-bds-edu-cta-type="eco_' . esc_attr( $key ) . '">' . esc_html( $route['label'] ) . '</a>';
				}
			}
			if ( $doors ) {
				$extra .= '<p class="bds-edu-eco">Also in the BrandDad network: ' . implode( ' · ', array_slice( $doors, 0, 3 ) ) . '</p>';
			}
			$extra .= '<span id="bds-edu-keep-learning"></span>';
		}

		return $extra ? ( $content . $extra ) : $content;
	},
	36
);

/* --------------------------------------------------------------------------
 * Assets + analytics (privacy-conscious)
 * -------------------------------------------------------------------------- */

/**
 * Whether front assets should load on this request.
 *
 * @return bool
 */
function bds_edu_should_enqueue() {
	if ( is_admin() ) {
		return false;
	}
	if ( defined( 'BDS_ACAD_RENDERING' ) && BDS_ACAD_RENDERING ) {
		return true;
	}
	$s = bds_edu_settings();
	if ( empty( $s['enabled'] ) ) {
		return false;
	}
	$hub = sanitize_title( $s['hub_slug'] );
	if ( is_page( $hub ) ) {
		return true;
	}
	if ( ! is_singular( array( 'post', 'page' ) ) ) {
		return false;
	}
	$post_id = get_queried_object_id();
	$terms   = wp_get_post_terms( $post_id, BDS_EDU_TYPE_TAX, array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && $terms ) {
		return true;
	}
	$meta = bds_edu_get_meta( $post_id );
	if ( $meta['diy_type'] || $meta['primary_service'] || $meta['primary_problem'] || ! empty( $meta['no_service'] ) ) {
		return true;
	}
	$content = (string) get_post_field( 'post_content', $post_id );
	return (bool) preg_match( '/\[bds_(edu_|learning_center)/', $content );
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! bds_edu_should_enqueue() ) {
			return;
		}
		$s = bds_edu_settings();
		wp_register_style( 'bds-edu-conv', false, array(), BDS_EDU_CONV_VER );
		wp_enqueue_style( 'bds-edu-conv' );
		wp_add_inline_style(
			'bds-edu-conv',
			'.bds-edu-cta,.bds-edu-diyhire,.bds-edu-complete,.bds-edu-quiz{max-width:820px;margin:2rem auto;padding:1.25rem 1.1rem;border:1px solid rgba(15,23,42,.1);border-radius:14px;background:linear-gradient(180deg,#fff,#f8fafc);font-family:Manrope,system-ui,sans-serif;line-height:1.55}'
			. '.bds-edu-cta__eyebrow,.bds-edu-hub .bds-edu-cta__eyebrow{text-transform:uppercase;letter-spacing:.08em;font-size:.72rem;font-weight:800;color:#0260d9;margin:0 0 .4rem}'
			. '.bds-edu-cta__grid{display:grid;gap:.75rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-top:.75rem}'
			. '.bds-edu-cta__card{display:block;padding:.9rem 1rem;border-radius:12px;border:1px solid rgba(15,23,42,.12);text-decoration:none;color:#0f172a;background:#fff}'
			. '.bds-edu-cta__card strong{display:block;margin-bottom:.25rem}'
			. '.bds-edu-cta__card span{font-size:.92rem;color:#475569}'
			. '.bds-edu-cta__card--help{border-color:#93c5fd}.bds-edu-cta__card--dfy{border-color:#0260d9}'
			. '.bds-edu-cta__subtle{margin:.85rem 0 0;font-size:.95rem;color:#334155}'
			. '.bds-edu-diyhire__cols{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}'
			. '.bds-edu-diyhire__honest{margin-top:1rem}.bds-edu-diyhire__or{margin:0 .4rem;color:#64748b}'
			. '.bds-edu-btn{display:inline-block;margin:.35rem .35rem .35rem 0;padding:.65rem 1rem;border-radius:10px;background:#0260d9;color:#fff!important;text-decoration:none;font-weight:800;border:0;cursor:pointer}'
			. '.bds-edu-btn--ghost{background:#fff;color:#0f172a!important;border:2px solid #0f172a}'
			. '.bds-edu-quiz label{display:block;margin:0 0 .75rem;font-weight:700}'
			. '.bds-edu-quiz select{width:100%;max-width:360px;padding:.55rem .7rem;border-radius:8px;border:1px solid rgba(15,23,42,.15)}'
			. '.bds-edu-quiz__out{margin-top:1rem}.bds-edu-complete__path{padding-left:1.1rem}'
			. '.bds-edu-eco{max-width:820px;margin:1rem auto;font-size:.95rem;color:#475569}'
			. '.page:has(.bds-edu-hub) .page-header,body:has(.bds-edu-hub) .page-header{display:none!important}'
			. '.bds-edu-hub{--edu-blue:#0260d9;--edu-navy:#0f172a;--edu-muted:#64748b;max-width:none;margin:0;padding:0 0 88px;border:0;border-radius:0;background:linear-gradient(180deg,#f1f5f9 0%,#f8fafc 32%,#f8fafc 100%);font-family:Manrope,system-ui,sans-serif;color:var(--edu-navy);line-height:1.55}'
			. '.bds-edu-hub__eyebrow{text-transform:uppercase;letter-spacing:.09em;font-size:13px;font-weight:800;color:var(--edu-blue);margin:0 0 .4rem}'
			. '.bds-edu-hub__hero{max-width:920px;margin:0 auto;padding:96px 24px 28px;text-align:center}'
			. '.bds-edu-hub__hero h1{font-size:clamp(36px,5.2vw,58px);line-height:1.08;margin:12px auto 16px;letter-spacing:-.02em;color:var(--edu-navy)}'
			. '.bds-edu-hub__hero>p{font-size:18px;line-height:1.65;color:#475569;max-width:640px;margin:0 auto 26px}'
			. '.bds-edu-hub__actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}'
			. '.bds-edu-hub__btn{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:10px;font-weight:800;text-decoration:none;background:var(--edu-blue);color:#fff!important;border:2px solid var(--edu-blue);box-shadow:0 8px 20px rgba(2,96,217,.22)}'
			. '.bds-edu-hub__btn--ghost{background:#fff!important;color:#0f172a!important;border:2px solid #0f172a;box-shadow:none}'
			. '.bds-edu-hub__btn--ghost:hover{border-color:var(--edu-blue);color:var(--edu-blue)!important}'
			. '.bds-edu-hub__btn--light{background:#fff!important;color:var(--edu-navy)!important;border-color:#fff}'
			. '.bds-edu-hub__trust{display:flex;justify-content:center;gap:22px;flex-wrap:wrap;list-style:none;padding:0;margin:24px 0 0;color:#475569;font-size:14px;font-weight:650}'
			. '.bds-edu-hub__trust li:before{content:"✓ ";color:var(--edu-blue);font-weight:800}'
			. '.bds-edu-hub__acad{max-width:1120px;margin:0 auto 36px}'
			. '.bds-edu-hub__paths{max-width:1120px;margin:0 auto 36px;padding:0 24px;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}'
			. '.bds-edu-hub__paths>a{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 16px;display:grid;gap:6px;text-decoration:none;box-shadow:0 8px 24px rgba(15,23,42,.04);transition:.18s ease}'
			. '.bds-edu-hub__paths>a:hover{border-color:#93c5fd;transform:translateY(-2px)}'
			. '.bds-edu-hub__paths strong{font-size:16px;color:var(--edu-navy)}'
			. '.bds-edu-hub__paths span{color:var(--edu-muted);font-size:13.5px;line-height:1.45}'
			. '.bds-edu-hub__paths b{color:var(--edu-blue);font-size:13px;margin-top:4px}'
			. '.bds-edu-hub__how{max-width:1120px;margin:0 auto 56px;padding:36px 28px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;display:grid;grid-template-columns:.8fr 1.2fr;gap:36px}'
			. '.bds-edu-hub__how h2{font-size:clamp(28px,4vw,42px);margin:8px 0 10px;letter-spacing:-.02em}'
			. '.bds-edu-hub__how>div>p{color:#64748b;margin:0}'
			. '.bds-edu-hub__how ol{list-style:none;margin:0;padding:0;display:grid;gap:18px}'
			. '.bds-edu-hub__how li{display:flex;gap:14px}'
			. '.bds-edu-hub__how li>b{width:42px;height:42px;flex:0 0 42px;border-radius:50%;display:grid;place-items:center;background:#eff6ff;color:var(--edu-blue)}'
			. '.bds-edu-hub__how strong{display:block;margin-bottom:4px}'
			. '.bds-edu-hub__how span{color:#64748b;font-size:14.5px}'
			. '.bds-edu-hub__week{max-width:1120px;margin:0 auto 56px}'
			. '.bds-edu-hub__week ol{list-style:none;margin:0;padding:0 24px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}'
			. '.bds-edu-hub__week a{display:grid;gap:6px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px 18px;text-decoration:none;min-height:100%;box-shadow:0 8px 24px rgba(15,23,42,.04)}'
			. '.bds-edu-hub__week a:hover{border-color:#93c5fd;transform:translateY(-2px)}'
			. '.bds-edu-hub__week b{color:#0260d9;font-size:12px;letter-spacing:.08em;text-transform:uppercase}'
			. '.bds-edu-hub__week strong{color:#0f172a;font-size:16px}'
			. '.bds-edu-hub__week span{color:#64748b;font-size:13.5px;line-height:1.45}'
			. '.bds-edu-hub__intro{max-width:720px;margin:0 auto 28px;text-align:center;padding:0 24px}'
			. '.bds-edu-hub__intro h2{font-size:clamp(28px,4vw,44px);margin:8px 0 10px;letter-spacing:-.02em}'
			. '.bds-edu-hub__intro p{color:#64748b;margin:0}'
			. '.bds-edu-hub__modules{margin:0 0 56px}'
			. '.bds-edu-hub__modgrid{max-width:1120px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}'
			. '.bds-edu-hub__modgrid article{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:22px;display:flex;flex-direction:column;box-shadow:0 8px 28px rgba(15,23,42,.05)}'
			. '.bds-edu-hub__modgrid span{font-size:12px;font-weight:800;color:var(--edu-blue);text-transform:uppercase;letter-spacing:.07em}'
			. '.bds-edu-hub__modgrid h3{font-size:20px;margin:10px 0 8px;line-height:1.25}'
			. '.bds-edu-hub__modgrid>article>p{color:#64748b;font-size:14.5px;margin:0 0 12px}'
			. '.bds-edu-hub__modgrid ul{margin:0 0 16px;padding:0 0 0 18px;color:#334155;font-size:14px;flex:1}'
			. '.bds-edu-hub__modgrid li{margin:0 0 6px}'
			. '.bds-edu-hub__modctas{display:grid;gap:8px;margin:auto 0 0}'
			. '.bds-edu-hub__modctas a{color:var(--edu-blue);font-weight:800;text-decoration:none;font-size:14px}'
			. '.bds-edu-hub__modctas a.is-quiet{color:#475569;font-weight:650}'
			. '.bds-edu-hub__guides{margin:0 0 56px}'
			. '.bds-edu-hub__grid{display:grid;gap:16px;grid-template-columns:repeat(3,minmax(0,1fr));margin:0 auto;max-width:1120px;padding:0 24px}'
			. '.bds-edu-hub__grid article{padding:22px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;display:flex;flex-direction:column;box-shadow:0 8px 28px rgba(15,23,42,.04)}'
			. '.bds-edu-hub__grid span{font-size:12px;font-weight:800;color:var(--edu-blue);text-transform:uppercase;letter-spacing:.07em}'
			. '.bds-edu-hub__grid h3{font-size:20px;margin:10px 0 8px;line-height:1.3}'
			. '.bds-edu-hub__grid h3 a{color:var(--edu-navy);text-decoration:none}'
			. '.bds-edu-hub__grid p{color:#64748b;font-size:14.5px;line-height:1.55;margin:0 0 14px;flex:1}'
			. '.bds-edu-hub__read{color:var(--edu-blue);font-weight:800;text-decoration:none;font-size:14px}'
			. '.bds-edu-hub__moreblog{text-align:center;margin:22px 0 0}'
			. '.bds-edu-hub__moreblog a{color:var(--edu-blue);font-weight:800;text-decoration:none}'
			. '.bds-edu-hub__faq{max-width:1120px;margin:0 auto 48px;padding:42px 28px;background:#fff;border:1px solid #e2e8f0;border-radius:22px;display:grid;grid-template-columns:.75fr 1.25fr;gap:32px}'
			. '.bds-edu-hub__faq h2{font-size:clamp(28px,4vw,40px);margin:8px 0}'
			. '.bds-edu-hub__faqlist{display:grid;gap:10px}'
			. '.bds-edu-hub__faqlist details{border:1px solid #e2e8f0;border-radius:12px;padding:16px}'
			. '.bds-edu-hub__faqlist summary{font-weight:800;color:var(--edu-navy);cursor:pointer}'
			. '.bds-edu-hub__faqlist p{color:#64748b;line-height:1.6;margin:10px 0 0}'
			. '.bds-edu-hub__quizwrap{max-width:820px;margin:0 auto 48px;padding:0 24px}'
			. '.bds-edu-hub .bds-edu-quiz{max-width:none;margin:0}'
			. '.bds-edu-hub__final{max-width:1120px;margin:0 auto;padding:36px 28px;background:var(--edu-navy);color:#fff;border-radius:22px;display:flex;align-items:center;justify-content:space-between;gap:24px}'
			. '.bds-edu-hub__final h2{color:#fff;margin:8px 0 10px;font-size:clamp(26px,4vw,36px)}'
			. '.bds-edu-hub__final p{color:#cbd5e1;margin:0}'
			. '.bds-edu-hub__finalbtns{display:flex;flex-wrap:wrap;gap:10px;flex-shrink:0}'
			. '@media(max-width:1100px){.bds-edu-hub__paths{grid-template-columns:repeat(2,minmax(0,1fr))}.bds-edu-hub__modgrid,.bds-edu-hub__grid,.bds-edu-hub__week ol{grid-template-columns:repeat(2,minmax(0,1fr))}}'
			. '@media(max-width:800px){.bds-edu-hub__hero{padding-top:72px}.bds-edu-hub__how,.bds-edu-hub__faq,.bds-edu-hub__final{grid-template-columns:1fr;margin-inline:16px;flex-direction:column;align-items:flex-start}.bds-edu-hub__paths,.bds-edu-hub__modgrid,.bds-edu-hub__grid,.bds-edu-hub__week ol{grid-template-columns:1fr}.bds-edu-hub__actions .bds-edu-hub__btn{width:100%}.bds-edu-hub__finalbtns{width:100%}.bds-edu-hub__finalbtns a{flex:1;text-align:center}}'
		);

		$quiz_seo  = bds_edu_po( home_url( '/product/website-seo-audit/' ) );
		$quiz_soc  = bds_edu_po( home_url( '/product/social-profile-optimization-bundle/' ) );
		$quiz_conv = bds_edu_po( home_url( '/product/website-conversion-makeover/' ) );
		$quiz_dir  = bds_edu_po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=learning_center&utm_campaign=edu_quiz' );

		wp_register_script( 'bds-edu-conv', false, array(), BDS_EDU_CONV_VER, true );
		wp_enqueue_script( 'bds-edu-conv' );
		$ajax = admin_url( 'admin-ajax.php' );
		$post = is_singular() ? (int) get_queried_object_id() : 0;
		wp_add_inline_script(
			'bds-edu-conv',
			'(function(){var POST=' . (int) $post . ',AJAX=' . wp_json_encode( $ajax ) . ',ON=' . ( ! empty( $s['analytics'] ) ? 1 : 0 ) . ';'
			. 'function sid(){try{var k="bds_edu_sid";var v=localStorage.getItem(k);if(!v){v="e"+Math.random().toString(36).slice(2)+Date.now().toString(36);localStorage.setItem(k,v);}return v;}catch(e){return"";}}'
			. 'function track(ev,extra){if(!ON)return;extra=extra||{};var body=new FormData();body.append("action","bds_edu_track");body.append("event",ev);body.append("post_id",String(extra.post_id||POST||0));body.append("cta_type",extra.cta_type||"");body.append("svc",extra.svc||"");body.append("sid",sid());body.append("nonce",' . wp_json_encode( wp_create_nonce( 'bds_edu_track' ) ) . ');try{navigator.sendBeacon?navigator.sendBeacon(AJAX,body):fetch(AJAX,{method:"POST",body:body,credentials:"same-origin",keepalive:true});}catch(e){}}'
			. 'if(POST)track("edu_view");'
			. 'document.addEventListener("click",function(e){var a=e.target.closest("[data-bds-edu-event]");if(!a)return;track(a.getAttribute("data-bds-edu-event")||"cta_click",{cta_type:a.getAttribute("data-bds-edu-cta-type")||"",svc:a.getAttribute("data-bds-edu-svc")||"",post_id:a.closest("[data-bds-edu-post]")&&a.closest("[data-bds-edu-post]").getAttribute("data-bds-edu-post")});});'
			. 'var q=document.querySelector(".bds-edu-quiz__form");if(q){q.addEventListener("submit",function(e){e.preventDefault();var f=new FormData(q);var axes=["seo","social","conversion","visibility"];var scores={};var edu=[];var svc=[];axes.forEach(function(k){scores[k]=parseInt(f.get(k),10)||0;});'
			. 'function band(n){return n>=4?"Strong":(n===3?"Moderate":"Needs improvement");}'
			. 'if(scores.seo<=2){edu.push("Read a Local SEO / Google visibility guide first.");svc.push({t:"Website SEO Audit",u:' . wp_json_encode( $quiz_seo ) . '});}else if(scores.seo===3){edu.push("Review a DIY SEO checklist before buying packages.");}'
			. 'if(scores.social<=2){edu.push("Start with social profile polish and a posting cadence guide.");svc.push({t:"Social Profile Optimization Bundle",u:' . wp_json_encode( $quiz_soc ) . '});}else if(scores.social===3){edu.push("Study a content strategy playbook — consistency beats random posting.");}'
			. 'if(scores.conversion<=2){edu.push("Work through a website conversion checklist (clarity, CTA, trust).");svc.push({t:"Website Conversion Makeover",u:' . wp_json_encode( $quiz_conv ) . '});}'
			. 'if(scores.visibility<=2){edu.push("Claim/complete your BrandDad Directory listing and GBP basics.");svc.push({t:"BrandDad Directory",u:' . wp_json_encode( $quiz_dir ) . '});}'
			. 'if(!edu.length)edu.push("You are in good shape — keep learning advanced topics or help someone else.");'
			. 'var html="<h3>Your scores (unadjusted)</h3><ul>";axes.forEach(function(k){html+="<li><strong>"+k+":</strong> "+scores[k]+"/5 — "+band(scores[k])+"</li>";});html+="</ul><h3>Learn next</h3><ul>"+edu.map(function(x){return "<li>"+x+"</li>";}).join("")+"</ul>";'
			. 'if(svc.length){html+="<h3>Optional help (only if useful)</h3><ul>"+svc.map(function(s){return "<li><a href=\\""+s.u+"\\" data-bds-edu-event=\\"cta_click\\" data-bds-edu-cta-type=\\"quiz_service\\" data-bds-edu-svc=\\"quiz\\">"+s.t+"</a></li>";}).join("")+"</ul><p>Services are optional. Your scores were not changed to promote them.</p>";}'
			. 'var out=document.querySelector(".bds-edu-quiz__out");out.hidden=false;out.innerHTML=html;track("quiz_complete",{cta_type:"assessment"});out.scrollIntoView({behavior:"smooth",block:"nearest"});});}'
			. '})();'
		);
	},
	40
);

add_action( 'wp_ajax_bds_edu_track', 'bds_edu_track_ajax' );
add_action( 'wp_ajax_nopriv_bds_edu_track', 'bds_edu_track_ajax' );

/**
 * Privacy-conscious funnel counters (no emails, no IPs stored).
 */
function bds_edu_track_ajax() {
	$s = bds_edu_settings();
	if ( empty( $s['analytics'] ) ) {
		wp_send_json_success( array( 'ok' => 1, 'off' => 1 ) );
	}
	if ( ! check_ajax_referer( 'bds_edu_track', 'nonce', false ) ) {
		wp_send_json_error( array( 'e' => 'nonce' ), 403 );
	}
	$event = sanitize_key( (string) ( $_POST['event'] ?? '' ) );
	$allowed = array( 'edu_view', 'cta_click', 'quiz_complete', 'service_view', 'add_to_cart', 'purchase' );
	if ( ! in_array( $event, $allowed, true ) ) {
		wp_send_json_error( array( 'e' => 'event' ), 400 );
	}
	$post_id  = absint( $_POST['post_id'] ?? 0 );
	$cta_type = sanitize_key( (string) ( $_POST['cta_type'] ?? '' ) );
	$svc      = sanitize_title( (string) ( $_POST['svc'] ?? '' ) );
	if ( bds_edu_is_outreach_slug( $svc ) ) {
		$svc = '';
	}
	// Anonymous session id — truncated hash only.
	$sid = substr( hash( 'sha256', (string) ( $_POST['sid'] ?? '' ) . wp_salt( 'nonce' ) ), 0, 12 );

	$stats = get_option( BDS_EDU_FUNNEL_OPT, array() );
	if ( ! is_array( $stats ) ) {
		$stats = array();
	}
	$day = gmdate( 'Y-m-d' );
	if ( ! isset( $stats[ $day ] ) || ! is_array( $stats[ $day ] ) ) {
		$stats[ $day ] = array();
	}
	$key = $event . '|' . $post_id . '|' . $cta_type . '|' . $svc;
	if ( ! isset( $stats[ $day ][ $key ] ) ) {
		$stats[ $day ][ $key ] = array( 'n' => 0, 'sids' => array() );
	}
	$stats[ $day ][ $key ]['n']++;
	if ( $sid && count( $stats[ $day ][ $key ]['sids'] ) < 50 ) {
		$stats[ $day ][ $key ]['sids'][ $sid ] = 1;
	}
	// Keep ~45 days.
	$keys = array_keys( $stats );
	sort( $keys );
	while ( count( $keys ) > 45 ) {
		$drop = array_shift( $keys );
		unset( $stats[ $drop ] );
	}
	update_option( BDS_EDU_FUNNEL_OPT, $stats, false );
	wp_send_json_success( array( 'ok' => 1 ) );
}

// Woo hooks for later funnel stages (Social only; no PII).
add_action(
	'woocommerce_add_to_cart',
	static function () {
		// Front JS cannot always fire; server-side bump without product→edu join if unknown.
		$stats = get_option( BDS_EDU_FUNNEL_OPT, array() );
		if ( ! is_array( $stats ) ) {
			$stats = array();
		}
		$day = gmdate( 'Y-m-d' );
		if ( ! isset( $stats[ $day ] ) ) {
			$stats[ $day ] = array();
		}
		$k = 'add_to_cart|0||';
		if ( ! isset( $stats[ $day ][ $k ] ) ) {
			$stats[ $day ][ $k ] = array( 'n' => 0, 'sids' => array() );
		}
		$stats[ $day ][ $k ]['n']++;
		update_option( BDS_EDU_FUNNEL_OPT, $stats, false );
	},
	20
);

add_action(
	'woocommerce_thankyou',
	static function ( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$stats = get_option( BDS_EDU_FUNNEL_OPT, array() );
		if ( ! is_array( $stats ) ) {
			$stats = array();
		}
		$day = gmdate( 'Y-m-d' );
		if ( ! isset( $stats[ $day ] ) ) {
			$stats[ $day ] = array();
		}
		$k = 'purchase|0||';
		if ( ! isset( $stats[ $day ][ $k ] ) ) {
			$stats[ $day ][ $k ] = array( 'n' => 0, 'sids' => array() );
		}
		$stats[ $day ][ $k ]['n']++;
		update_option( BDS_EDU_FUNNEL_OPT, $stats, false );
	},
	20
);

/* --------------------------------------------------------------------------
 * Admin UI
 * -------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes',
	static function () {
		foreach ( array( 'post', 'page' ) as $pt ) {
			add_meta_box(
				'bds_edu_conv',
				'Education → Service conversion',
				'bds_edu_metabox_render',
				$pt,
				'side',
				'high'
			);
		}
		add_meta_box(
			'bds_edu_svc_tags',
			'Service education tags',
			'bds_edu_product_tags_metabox',
			'product',
			'side',
			'default'
		);
	}
);

/**
 * @param WP_Post $post Post.
 */
function bds_edu_metabox_render( $post ) {
	wp_nonce_field( 'bds_edu_meta_save', 'bds_edu_meta_nonce' );
	$m     = bds_edu_get_meta( $post->ID );
	$svcs  = bds_edu_service_choices();
	$probs = bds_edu_tag_dimensions()['problem'];
	$sug   = bds_edu_suggest_services_for_post( $post->ID );
	?>
	<p><label>Educational Intent<br>
		<select name="bds_edu_intent" style="width:100%">
			<option value="">—</option>
			<?php foreach ( array( 'teach', 'diagnose', 'implement', 'update' ) as $v ) : ?>
				<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $m['intent'], $v ); ?>><?php echo esc_html( ucfirst( $v ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label>DIY Difficulty<br>
		<select name="bds_diy_difficulty" style="width:100%">
			<option value="">—</option>
			<?php foreach ( array( 'easy', 'moderate', 'advanced' ) as $v ) : ?>
				<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $m['diy_difficulty'], $v ); ?>><?php echo esc_html( ucfirst( $v ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label>DIY Type (A/B/C)<br>
		<select name="bds_diy_type" style="width:100%">
			<option value="">— auto from difficulty —</option>
			<?php foreach ( bds_edu_diy_types() as $k => $row ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $m['diy_type'], $k ); ?>><?php echo esc_html( $k . ' — ' . $row['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label>Primary Problem<br>
		<select name="bds_primary_problem" style="width:100%">
			<option value="">—</option>
			<?php foreach ( $probs as $p ) : ?>
				<option value="<?php echo esc_attr( $p ); ?>" <?php selected( $m['primary_problem'], $p ); ?>><?php echo esc_html( bds_edu_humanize( $p ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label>Primary related service<br>
		<select name="bds_primary_service" style="width:100%">
			<option value="">—</option>
			<?php foreach ( $svcs as $slug => $title ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $m['primary_service'], $slug ); ?>><?php echo esc_html( $title ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label>Secondary services (slugs, comma-separated)<br>
		<input type="text" name="bds_secondary_services" value="<?php echo esc_attr( implode( ', ', $m['secondary'] ) ); ?>" style="width:100%" />
	</label></p>
	<p><label><input type="checkbox" name="bds_no_service_rec" value="1" <?php checked( ! empty( $m['no_service'] ) ); ?> /> No service recommendation</label></p>
	<p><label>CTA Strength<br>
		<select name="bds_cta_strength" style="width:100%">
			<option value="">— auto —</option>
			<?php foreach ( array( 'none', 'subtle', 'moderate', 'strong' ) as $v ) : ?>
				<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $m['cta_strength'], $v ); ?>><?php echo esc_html( ucfirst( $v ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label>Next resource (post ID)<br>
		<input type="number" name="bds_next_resource" value="<?php echo esc_attr( (string) $m['next_resource'] ); ?>" style="width:100%" />
	</label></p>
	<p><label>Next course (post ID)<br>
		<input type="number" name="bds_next_course" value="<?php echo esc_attr( (string) $m['next_course'] ); ?>" style="width:100%" />
	</label></p>
	<p><label>Recommended service (override)<br>
		<select name="bds_next_service" style="width:100%">
			<option value="">—</option>
			<?php foreach ( $svcs as $slug => $title ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $m['next_service'], $slug ); ?>><?php echo esc_html( $title ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<?php if ( $sug ) : ?>
		<p><em>AI/rule suggestions (editable):</em><br>
		<?php foreach ( $sug as $row ) : ?>
			<code><?php echo esc_html( $row['slug'] ); ?></code> — <?php echo esc_html( $row['why'] ); ?><br>
		<?php endforeach; ?>
		</p>
	<?php endif; ?>
	<p class="description">Shortcodes: <code>[bds_edu_ctas]</code> <code>[bds_edu_diy_hire]</code> <code>[bds_edu_course_complete]</code> <code>[bds_edu_quiz]</code></p>
	<?php
}

/**
 * Product-side tag editor stores into option overlay by product slug.
 *
 * @param WP_Post $post Product.
 */
function bds_edu_product_tags_metabox( $post ) {
	wp_nonce_field( 'bds_edu_prod_tags', 'bds_edu_prod_tags_nonce' );
	$slug = $post->post_name;
	$all  = bds_edu_service_tags_all();
	$cur  = isset( $all[ $slug ] ) ? $all[ $slug ] : array();
	$dims = bds_edu_tag_dimensions();
	foreach ( $dims as $dim => $choices ) {
		$val = isset( $cur[ $dim ] ) ? (array) $cur[ $dim ] : array();
		echo '<p><strong>' . esc_html( $dim ) . '</strong><br>';
		echo '<input type="text" name="bds_edu_prod_tag[' . esc_attr( $dim ) . ']" value="' . esc_attr( implode( ', ', $val ) ) . '" style="width:100%" placeholder="' . esc_attr( implode( ', ', array_slice( $choices, 0, 4 ) ) ) . '" /></p>';
	}
	echo '<p class="description">Comma-separated tags. Saved to the central service tag map (not customer-visible).</p>';
}

add_action(
	'save_post',
	static function ( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['bds_edu_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_edu_meta_nonce'] ) ), 'bds_edu_meta_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$fields = array(
			'bds_edu_intent'          => '_bds_edu_intent',
			'bds_diy_difficulty'      => '_bds_diy_difficulty',
			'bds_diy_type'            => '_bds_diy_type',
			'bds_primary_problem'     => '_bds_primary_problem',
			'bds_primary_service'     => '_bds_primary_service',
			'bds_cta_strength'        => '_bds_cta_strength',
			'bds_next_service'        => '_bds_next_service',
		);
		foreach ( $fields as $req => $meta ) {
			$val = isset( $_POST[ $req ] ) ? sanitize_text_field( wp_unslash( $_POST[ $req ] ) ) : '';
			if ( in_array( $meta, array( '_bds_primary_service', '_bds_next_service' ), true ) && bds_edu_is_outreach_slug( $val ) ) {
				$val = '';
			}
			update_post_meta( $post_id, $meta, $val );
		}
		$sec = isset( $_POST['bds_secondary_services'] ) ? sanitize_text_field( wp_unslash( $_POST['bds_secondary_services'] ) ) : '';
		$sec_arr = array_filter( array_map( 'sanitize_title', preg_split( '/[\s,]+/', $sec ) ) );
		$sec_arr = array_values( array_filter( $sec_arr, static function ( $s ) {
			return ! bds_edu_is_outreach_slug( $s );
		} ) );
		update_post_meta( $post_id, '_bds_secondary_services', $sec_arr );
		update_post_meta( $post_id, '_bds_no_service_rec', empty( $_POST['bds_no_service_rec'] ) ? 0 : 1 );
		update_post_meta( $post_id, '_bds_next_resource', absint( $_POST['bds_next_resource'] ?? 0 ) );
		update_post_meta( $post_id, '_bds_next_course', absint( $_POST['bds_next_course'] ?? 0 ) );

		// Sync difficulty ↔ DIY type.
		$diff = (string) get_post_meta( $post_id, '_bds_diy_difficulty', true );
		$diy  = (string) get_post_meta( $post_id, '_bds_diy_type', true );
		if ( $diff && ! $diy ) {
			$map = array( 'easy' => 'A', 'moderate' => 'B', 'advanced' => 'C' );
			if ( isset( $map[ $diff ] ) ) {
				update_post_meta( $post_id, '_bds_diy_type', $map[ $diff ] );
			}
		}
	}
);

add_action(
	'save_post_product',
	static function ( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['bds_edu_prod_tags_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_edu_prod_tags_nonce'] ) ), 'bds_edu_prod_tags' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$slug = get_post_field( 'post_name', $post_id );
		if ( ! $slug || bds_edu_is_outreach_slug( $slug ) ) {
			return;
		}
		$raw = isset( $_POST['bds_edu_prod_tag'] ) && is_array( $_POST['bds_edu_prod_tag'] ) ? wp_unslash( $_POST['bds_edu_prod_tag'] ) : array();
		$tags = array();
		foreach ( $raw as $dim => $str ) {
			$dim = sanitize_key( $dim );
			$parts = array_filter( array_map( 'sanitize_title', preg_split( '/[\s,]+/', (string) $str ) ) );
			if ( $parts ) {
				$tags[ $dim ] = array_values( $parts );
			}
		}
		$over = get_option( BDS_EDU_SVC_TAGS_OPT, array() );
		if ( ! is_array( $over ) ) {
			$over = array();
		}
		$over[ $slug ] = $tags;
		update_option( BDS_EDU_SVC_TAGS_OPT, $over, false );
	}
);

/**
 * Rule-based (AI-optional) service suggestions for admin.
 *
 * @param int $post_id Post ID.
 * @return array<int,array{slug:string,why:string}>
 */
function bds_edu_suggest_services_for_post( $post_id ) {
	$s = bds_edu_settings();
	if ( empty( $s['ai_suggest'] ) ) {
		return array();
	}
	$title = strtolower( get_the_title( $post_id ) . ' ' . wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
	$hints = array(
		'instagram' => 'instagram-viral-growth-discovery-system',
		'reach'     => 'social-profile-optimization-bundle',
		'seo'       => 'website-seo-audit',
		'ranking'   => 'website-seo-audit',
		'google business' => 'gbp-setup-optimization',
		'gbp'       => 'gbp-setup-optimization',
		'review'    => 'google-review-growth-setup',
		'speed'     => 'website-speed-optimization',
		'hosting'   => 'hosttech-hosting',
		'directory' => 'branddad-directory',
		'linkedin'  => 'linkedin-visibility-amplification-system-for-professionals',
		'conversion'=> 'website-conversion-makeover',
		'ads'       => 'bd-ai-ads-setup',
	);
	$out = array();
	foreach ( $hints as $needle => $slug ) {
		if ( false !== strpos( $title, $needle ) && ! bds_edu_is_outreach_slug( $slug ) ) {
			$out[] = array( 'slug' => $slug, 'why' => 'Keyword hint: ' . $needle );
		}
		if ( count( $out ) >= 3 ) {
			break;
		}
	}
	return $out;
}

add_action(
	'admin_menu',
	static function () {
		add_submenu_page(
			'edit.php',
			'Education conversion',
			'Education conversion',
			'manage_options',
			'bds-edu-conversion',
			'bds_edu_admin_page'
		);
	}
);

function bds_edu_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['bds_edu_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bds_edu_settings_nonce'] ) ), 'bds_edu_settings' ) ) {
		$cur = bds_edu_settings();
		$cur['enabled']         = empty( $_POST['enabled'] ) ? 0 : 1;
		$cur['append_ctas']     = empty( $_POST['append_ctas'] ) ? 0 : 1;
		$cur['append_diy_hire'] = empty( $_POST['append_diy_hire'] ) ? 0 : 1;
		$cur['analytics']       = empty( $_POST['analytics'] ) ? 0 : 1;
		$cur['ai_suggest']      = empty( $_POST['ai_suggest'] ) ? 0 : 1;
		$cur['hub_slug']        = sanitize_title( (string) ( $_POST['hub_slug'] ?? 'learning-center' ) );
		update_option( BDS_EDU_CONV_OPT, $cur, false );
		echo '<div class="updated"><p>Saved.</p></div>';
	}
	$s     = bds_edu_settings();
	$stats = get_option( BDS_EDU_FUNNEL_OPT, array() );
	?>
	<div class="wrap">
		<h1>Education → Service conversion</h1>
		<p><strong>Teach honestly first. Recommend services second.</strong> Outreach stays hidden. No hard-coded ads in every article — relationships are tagged centrally.</p>
		<form method="post">
			<?php wp_nonce_field( 'bds_edu_settings', 'bds_edu_settings_nonce' ); ?>
			<table class="form-table">
				<tr><th>Enabled</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> On</label></td></tr>
				<tr><th>Auto-append CTAs</th><td><label><input type="checkbox" name="append_ctas" value="1" <?php checked( ! empty( $s['append_ctas'] ) ); ?> /> On educational posts</label></td></tr>
				<tr><th>DIY vs Hire block</th><td><label><input type="checkbox" name="append_diy_hire" value="1" <?php checked( ! empty( $s['append_diy_hire'] ) ); ?> /> For DIY types B/C</label></td></tr>
				<tr><th>Analytics</th><td><label><input type="checkbox" name="analytics" value="1" <?php checked( ! empty( $s['analytics'] ) ); ?> /> Privacy-conscious funnel counters</label></td></tr>
				<tr><th>Suggest services</th><td><label><input type="checkbox" name="ai_suggest" value="1" <?php checked( ! empty( $s['ai_suggest'] ) ); ?> /> Show rule-based suggestions in editor</label></td></tr>
				<tr><th>Hub slug</th><td><input name="hub_slug" value="<?php echo esc_attr( $s['hub_slug'] ); ?>" /></td></tr>
			</table>
			<?php submit_button( 'Save settings' ); ?>
		</form>
		<h2>Funnel snapshot (recent days)</h2>
		<p>edu_view → cta_click → add_to_cart / purchase. No emails or IPs stored.</p>
		<pre style="max-height:320px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px"><?php echo esc_html( wp_json_encode( array_slice( $stats ?: array( 'empty' => true ), -7, 7, true ), JSON_PRETTY_PRINT ) ); ?></pre>
		<p>Shortcodes: <code>[bds_learning_center]</code> <code>[bds_edu_quiz]</code> <code>[bds_edu_ctas]</code> <code>[bds_edu_diy_hire]</code> <code>[bds_edu_course_complete]</code></p>
		<p>Hub: <a href="<?php echo esc_url( home_url( '/' . $s['hub_slug'] . '/' ) ); ?>"><?php echo esc_html( home_url( '/' . $s['hub_slug'] . '/' ) ); ?></a></p>
	</div>
	<?php
}

/* --------------------------------------------------------------------------
 * Ensure hub page exists (idempotent)
 * -------------------------------------------------------------------------- */

add_action(
	'admin_init',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_option( 'bds_edu_hub_ready_v1' ) ) {
			return;
		}
		$slug = sanitize_title( bds_edu_settings()['hub_slug'] );
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$id = wp_insert_post(
				array(
					'post_title'   => 'Learning Center',
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => "[bds_learning_center]\n\n<p>BrandDad teaches the real work first. Services are optional — only when they solve the problem you just learned about.</p>",
				),
				true
			);
			if ( ! is_wp_error( $id ) ) {
				update_option( 'bds_edu_hub_page_id', (int) $id, false );
			}
		} else {
			update_option( 'bds_edu_hub_page_id', (int) $page->ID, false );
			if ( false === strpos( (string) $page->post_content, '[bds_learning_center]' ) ) {
				wp_update_post(
					array(
						'ID'           => $page->ID,
						'post_content' => "[bds_learning_center]\n\n" . $page->post_content,
					)
				);
			}
		}
		update_option( 'bds_edu_hub_ready_v1', 1, false );
		flush_rewrite_rules( false );
	},
	40
);
