<?php
/**
 * Plugin Name: BrandDad Directory Owner Utility Strip
 * Description: Keeps the signed-in Directory credit notice compact and usable across devices.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'BDS_DIRECTORY_OWNER_STRIP_VERSION' ) ) {
	return;
}

define( 'BDS_DIRECTORY_OWNER_STRIP_VERSION', '1.0.0' );

/**
 * @return bool
 */
function bds_directory_owner_strip_is_directory(): bool {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) $_SERVER['HTTP_HOST'] ) ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	return in_array( $host, array( 'directory.branddad.social', 'www.directory.branddad.social' ), true );
}

/**
 * Restyle only the existing account-specific credit notice. The notice text,
 * balance values, destinations, and visibility rules remain authoritative.
 */
function bds_directory_owner_strip_styles(): void {
	if ( ! bds_directory_owner_strip_is_directory() || is_admin() ) {
		return;
	}
	?>
	<style id="bds-directory-owner-strip-css" data-bds-directory-owner-strip="1.0.0">
	#bds-nav-fix-wallet{box-sizing:border-box;display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:8px 16px;width:min(1180px,calc(100% - 28px));max-width:1180px;margin:8px auto;padding:10px 14px;border:1px solid #d9e5f1;border-radius:14px;background:linear-gradient(135deg,#f8fbff 0%,#eef6ff 100%);box-shadow:0 8px 24px rgba(15,42,74,.08);color:#17324f}
	#bds-nav-fix-wallet h2{margin:0;color:#10243d;font-size:15px;line-height:1.25;white-space:nowrap}
	#bds-nav-fix-wallet p{margin:0;color:#52677d;font-size:12px;line-height:1.45}
	#bds-nav-fix-wallet p:last-child{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px}
	#bds-nav-fix-wallet a{display:inline-flex;min-height:34px;align-items:center;justify-content:center;padding:6px 10px;border:1px solid #bfd3e8;border-radius:9px;background:#fff;color:#0668d8;font-weight:800;text-decoration:none;white-space:nowrap}
	#bds-nav-fix-wallet a:hover,#bds-nav-fix-wallet a:focus-visible{border-color:#0668d8;box-shadow:0 0 0 3px rgba(6,104,216,.12)}
	@media(max-width:720px){
		#bds-nav-fix-wallet{grid-template-columns:1fr;width:calc(100% - 20px);margin:6px 10px;padding:10px 12px;gap:6px}
		#bds-nav-fix-wallet h2{white-space:normal}
		#bds-nav-fix-wallet p:first-of-type{display:-webkit-box;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2}
		#bds-nav-fix-wallet p:last-child{justify-content:flex-start;gap:6px}
		#bds-nav-fix-wallet a{min-height:32px;padding:5px 9px;font-size:12px}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'bds_directory_owner_strip_styles', 999 );

