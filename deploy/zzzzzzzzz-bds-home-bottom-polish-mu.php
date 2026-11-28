<?php
/**
 * Plugin Name: BrandDad Conversion Funnel Polish
 * Description: Polishes the creator campaign CTA and private recommendation form on the Social homepage and Services page.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_HOME_BOTTOM_POLISH_VERSION' ) ) {
	return;
}

define( 'BDS_HOME_BOTTOM_POLISH_VERSION', '1.2.0' );

/**
 * @return bool
 */
function bds_home_bottom_polish_should_render() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) $_SERVER['HTTP_HOST'] ) ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	if ( ! in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true ) ) {
		return false;
	}

	$is_home = function_exists( 'is_front_page' ) && is_front_page();
	$is_services = function_exists( 'is_page' ) && is_page( 'services' );

	return $is_home || $is_services;
}

/**
 * Print a late, tightly scoped style layer without changing lead handling.
 */
function bds_home_bottom_polish_styles() {
	if ( ! bds_home_bottom_polish_should_render() ) {
		return;
	}
	?>
	<style id="bds-home-bottom-polish-css" data-bds-home-bottom-polish="1.2.0">
	#bds-hire-home-line{max-width:1180px;margin:14px auto 24px;padding:0 20px;text-align:center}
	#bds-hire-home-line .bds-hire-a--text{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:0 16px;border:1px solid #dbe5ef;border-radius:999px;background:#f8fbff;color:#18324f;text-decoration:none;font-weight:800}
	#bds-hire-home-line .bds-hire-a--text:hover,#bds-hire-home-line .bds-hire-a--text:focus-visible{border-color:#0b83f6;color:#0668d8;background:#fff}
	#bds-bv-creator-strip{box-sizing:border-box;max-width:1180px;margin:28px auto;padding:clamp(26px,4vw,48px);border:1px solid #173c67;border-radius:24px;background:linear-gradient(135deg,#071a33 0%,#0d2f57 100%);box-shadow:0 22px 52px rgba(7,26,51,.18);color:#fff;overflow:hidden}
	#bds-bv-creator-strip h2{max-width:760px;margin:0 0 12px;color:#fff;font-size:clamp(28px,4vw,46px);line-height:1.08;letter-spacing:-.035em}
	#bds-bv-creator-strip>p:not(:last-child){max-width:720px;margin:0;color:#dbeafe;font-size:clamp(16px,2vw,19px);line-height:1.65}
	#bds-bv-creator-strip>p:last-child{display:flex;flex-wrap:wrap;gap:12px;margin:24px 0 0}
	#bds-bv-creator-strip .button{display:inline-flex;min-height:50px;align-items:center;justify-content:center;margin:0;padding:0 18px;border:2px solid #1188ff;border-radius:12px;background:#0878ed;color:#fff;text-decoration:none;font-weight:850;line-height:1.2;box-shadow:none}
	#bds-bv-creator-strip .button:hover,#bds-bv-creator-strip .button:focus-visible{background:#fff;border-color:#fff;color:#071a33}
	#bds-bv-creator-strip .button-secondary{border-color:#8bbce9;background:transparent;color:#fff}
	#bds-codex-lead{box-sizing:border-box;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px;max-width:1120px;margin:32px auto 56px;padding:clamp(24px,4vw,44px);border:1px solid #dbe5ef;border-radius:24px;background:linear-gradient(180deg,#fff 0%,#f8fbff 100%);box-shadow:0 20px 48px rgba(15,42,74,.12);color:#10243d;overflow:hidden}
	#bds-codex-lead h2,#bds-codex-lead>p,#bds-codex-lead>.bds-hp,#bds-codex-lead>label:nth-of-type(3),#bds-codex-lead>label:nth-of-type(4),#bds-codex-lead>label:nth-of-type(5),#bds-codex-lead>button,#bds-codex-lead>.bds-codex-lead-status{grid-column:1/-1}
	#bds-codex-lead h2{margin:0;color:#0c2340;font-size:clamp(28px,4vw,42px);line-height:1.1;letter-spacing:-.035em}
	#bds-codex-lead>p[data-bds-copy]{max-width:720px;margin:-6px 0 4px;color:#53657a;font-size:16px;line-height:1.6}
	#bds-codex-lead>label{display:flex;flex-direction:column;gap:8px;margin:0;color:#18324f;font-size:14px;font-weight:800;line-height:1.4}
	#bds-codex-lead>label>input[type="email"],#bds-codex-lead>label>input[type="text"],#bds-codex-lead>label>input:not([type]){box-sizing:border-box;width:100%;min-height:50px;margin:0;padding:10px 13px;border:1px solid #bfd0e2;border-radius:10px;background:#fff;color:#10243d;font:inherit;font-weight:500;outline:none}
	#bds-codex-lead>label>input:focus{border-color:#0878ed;box-shadow:0 0 0 3px rgba(8,120,237,.14)}
	#bds-codex-lead>label:nth-of-type(4),#bds-codex-lead>label:nth-of-type(5){display:grid;grid-template-columns:22px minmax(0,1fr);gap:11px;align-items:start;padding:14px 16px;border:1px solid #dbe5ef;border-radius:12px;background:#fff;color:#44566c;font-weight:600}
	#bds-codex-lead>label:nth-of-type(4)>input,#bds-codex-lead>label:nth-of-type(5)>input{display:block!important;width:20px!important;height:20px!important;min-height:20px!important;margin:1px 0 0!important;padding:0!important;accent-color:#0878ed}
	#bds-codex-lead>button{justify-self:start;min-height:50px;margin:2px 0 0;padding:0 22px;border:0;border-radius:11px;background:#0878ed;color:#fff;font-size:15px;font-weight:850;box-shadow:0 10px 22px rgba(8,120,237,.22);cursor:pointer}
	#bds-codex-lead>button:hover,#bds-codex-lead>button:focus-visible{background:#075fb8}
	#bds-codex-lead>.bds-codex-lead-status{min-height:0;margin:0;color:#18324f;font-weight:700}
	@media(max-width:720px){
		#bds-hire-home-line{padding:0 14px}
		#bds-hire-home-line .bds-hire-a--text{width:100%;box-sizing:border-box}
		#bds-bv-creator-strip{margin:22px 14px;padding:24px 18px;border-radius:18px}
		#bds-bv-creator-strip>p:last-child{display:grid;grid-template-columns:1fr}
		#bds-bv-creator-strip .button{width:100%;box-sizing:border-box}
		#bds-codex-lead{grid-template-columns:1fr;margin:24px 14px 42px;padding:24px 18px;border-radius:18px}
		#bds-codex-lead h2,#bds-codex-lead>p,#bds-codex-lead>label,#bds-codex-lead>button,#bds-codex-lead>.bds-codex-lead-status{grid-column:1}
		#bds-codex-lead>button{width:100%;justify-self:stretch}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'bds_home_bottom_polish_styles', 999 );

/**
 * The legacy freelance block can print its identical style tag twice when the
 * homepage renders both of its existing placements. Keep the first stylesheet
 * and remove only later duplicate nodes; content and links remain untouched.
 */
function bds_home_bottom_polish_dedupe_legacy_style() {
	if ( ! bds_home_bottom_polish_should_render() ) {
		return;
	}
	?>
	<script id="bds-home-bottom-polish-dedupe" data-bds-home-bottom-polish="1.2.0">
	(function(){var n=document.querySelectorAll('#bds-hire-css');for(var i=1;i<n.length;i++){n[i].remove();}})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bds_home_bottom_polish_dedupe_legacy_style', 999 );

