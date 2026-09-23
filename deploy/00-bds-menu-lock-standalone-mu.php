<?php
/**
 * Plugin Name: BrandDad Social Menu Lock (standalone)
 * Description: Beautiful hamburger drawer for branddad.social — NEVER REVERT to Elementor popup #50450 / first ugly menus.
 * Version: 1.0.13
 * Author: BrandDad
 *
 * Deploy: branddad.social/wp-content/mu-plugins/00-bds-menu-lock-standalone-mu.php
 * Loads early (00-) so Elementor/theme cannot win. If BDS_Site_Chrome prints the drawer, this MU skips markup/JS.
 * Do not also ship a MU copy of branddad-social-unified-brand.php (plugin already provides it).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_SOCIAL_MENU_LOCK_STANDALONE' ) ) {
	return;
}
define( 'BDS_SOCIAL_MENU_LOCK_STANDALONE', '1.0.13' );

/**
 * @return bool
 */
function bds_sml_is_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( false === strpos( $host, 'branddad.social' ) ) {
		return false;
	}
	if ( false !== strpos( $host, 'directory.' ) || false !== strpos( $host, 'affiliates.' ) ) {
		return false;
	}
	return true;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( bds_sml_is_host() ) {
			$classes[] = 'bds-menu-lock';
		}
		return $classes;
	}
);

/**
 * Strip Elementor popup hamburger widget from rendered header data (DOM removal, not CSS clip).
 *
 * @param array $data Elementor content data.
 * @return array
 */
function bds_sml_strip_legacy_ham_data( $data ) {
	if ( ! bds_sml_is_host() || ! is_array( $data ) ) {
		return $data;
	}
	$walk = static function ( $els ) use ( &$walk ) {
		$out = array();
		foreach ( (array) $els as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$id = isset( $el['id'] ) ? (string) $el['id'] : '';
			if ( $id === 'edb51f5' ) {
				continue;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$el['elements'] = $walk( $el['elements'] );
			}
			$out[] = $el;
		}
		return $out;
	};
	return $walk( $data );
}
add_filter( 'elementor/frontend/builder_content_data', 'bds_sml_strip_legacy_ham_data', 20 );

/**
 * Hard-stop legacy hamburger widget render (Theme Builder headers).
 */
add_action(
	'elementor/frontend/widget/before_render',
	static function ( $widget ) {
		if ( ! bds_sml_is_host() || ! is_object( $widget ) || ! method_exists( $widget, 'get_id' ) ) {
			return;
		}
		if ( (string) $widget->get_id() !== 'edb51f5' ) {
			return;
		}
		$GLOBALS['bds_sml_kill_ham'] = true;
		ob_start();
	},
	1
);
add_action(
	'elementor/frontend/widget/after_render',
	static function ( $widget ) {
		if ( empty( $GLOBALS['bds_sml_kill_ham'] ) ) {
			return;
		}
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_id' ) || (string) $widget->get_id() !== 'edb51f5' ) {
			return;
		}
		$GLOBALS['bds_sml_kill_ham'] = false;
		if ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
	},
	999
);

add_action(
	'wp_head',
	static function () {
		if ( is_admin() || ! bds_sml_is_host() ) {
			return;
		}
		echo '<style id="bds-menu-lock-standalone-css" data-bds-menu-lock="never-revert" data-bds-menu-lock-ver="' . esc_attr( BDS_SOCIAL_MENU_LOCK_STANDALONE ) . '">';
		echo '#bds-menu-toggle{display:none;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;border:1px solid #e2e8f0;background:#fff;color:#0372ff;cursor:pointer;z-index:100050;margin-left:8px;padding:0}';
		echo '#bds-menu-toggle span{display:block;width:18px;height:2px;background:currentColor;border-radius:2px;margin:3px auto}';
		echo '@media(max-width:1024px){#bds-menu-toggle{display:inline-grid}.site-header{position:relative!important}.site-header #bds-menu-toggle{position:absolute!important;right:18px!important;top:50%!important;transform:translateY(-50%)!important;margin:0!important}.site-header .site-navigation{display:none!important}.site-header>a:first-child{max-width:calc(100% - 76px)!important}.site-header>a:first-child img{max-width:100%!important;height:auto!important}}';
		/* Clip legacy Elementor/Dipi triggers at ALL breakpoints — architectural hide, not mobile-only. */
		echo 'body.bds-menu-lock .elementor-nav-menu--dropdown,body.bds-menu-lock .elementor-menu-toggle,body.bds-menu-lock .elementor-element-edb51f5,body.bds-menu-lock .elementor-element-edb51f5 a.elementor-icon,body.bds-menu-lock .dipi_hamburger,body.bds-menu-lock .dipi_hamburger a{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;opacity:0!important;pointer-events:none!important;margin:0!important;padding:0!important;border:0!important}';
		echo '.bds-nav-drawer[hidden]{display:none!important}.bds-nav-backdrop{position:fixed;inset:0;background:rgba(7,11,20,.55);z-index:100060}';
		echo '.bds-nav-panel{position:fixed;top:0;right:0;height:100%;width:min(340px,88vw);z-index:100070;background:#fff;color:#0f172a;border-left:1px solid #e2e8f0;padding:28px 22px 40px;display:flex;flex-direction:column;gap:18px;box-shadow:0 12px 36px rgba(15,23,42,.18);font-family:Manrope,Syne,"Segoe UI",sans-serif}';
		echo '.bds-nav-panel header{display:flex;align-items:center;justify-content:space-between}.bds-nav-panel nav{display:grid;gap:8px;overflow:auto}';
		echo '.bds-nav-panel nav a{display:flex;justify-content:space-between;padding:12px 14px;border-radius:12px;text-decoration:none;color:#0f172a;font-weight:700;background:#f8fafc;border:1px solid #e2e8f0}';
		echo '.bds-nav-close{border:1px solid #e2e8f0;background:#fff;border-radius:999px;width:42px;height:42px;cursor:pointer;font-size:22px;line-height:1}';
		echo '.bds-nav-cta{display:grid;gap:8px;margin-top:auto}.bds-nav-cta a{display:block;text-align:center;padding:12px;border-radius:12px;font-weight:800;text-decoration:none}';
		echo '.bds-nav-cta a.primary{background:#0372ff;color:#fff!important}.bds-nav-cta a.secondary{background:#0f172a;color:#fff!important}';
		echo '.elementor-location-header a[href$="/shop/"],.elementor-location-header a[href*="/shop/?"],.site-header a[href$="/shop/"],.site-header a[href*="/shop/?"],#bds-nav-drawer nav a[href$="/shop/"],#bds-nav-drawer nav a[href*="/shop/?"]{display:none!important}';
		echo '@media(max-width:640px){body.bds-menu-lock .bdsu-contact-dock{right:12px!important;bottom:72px!important;z-index:99990!important}body.bds-menu-lock #bd-lc-root{top:12px!important;right:12px!important;bottom:auto!important;left:auto!important}body.bds-menu-lock #bdsNetworkConnectStrip{position:relative;z-index:100045}}';
		echo '</style>';
	},
	3
);

add_action(
	'wp_footer',
	static function () {
		if ( is_admin() || ! bds_sml_is_host() ) {
			return;
		}
		// After chrome (pri 5). Only skip if chrome actually printed the drawer — class_exists alone was a dead skip.
		if ( did_action( 'bds_chrome_drawer_printed' ) ) {
			return;
		}
		$po = static function ( $url ) {
			if ( function_exists( 'bds_po_url' ) ) {
				return bds_po_url( $url );
			}
			if ( ! empty( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $ref !== '' && false === strpos( $url, 'ref=' ) ) {
					$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'ref=' . rawurlencode( $ref );
				}
			}
			return $url;
		};
		$list = add_query_arg(
			array(
				'redirect_to'  => 'https://directory.branddad.social/add-listing/',
				'utm_source'   => 'branddad_social',
				'utm_medium'   => 'nav',
			),
			'https://directory.branddad.social/registration/'
		);
		$links = array(
			array( 'Home', $po( 'https://branddad.social/' ) ),
			array( 'Services', $po( 'https://branddad.social/services/' ) ),
			array( 'Learn', $po( 'https://branddad.social/learning-center/' ) ),
			array( 'Explained', $po( 'https://branddad.social/explained/' ) ),
			array( 'Guides', $po( 'https://branddad.social/guides/' ) ),
			array( 'Courses', $po( 'https://branddad.social/courses/' ) ),
			array( 'Playbooks', $po( 'https://branddad.social/guides/' ) ),
			array( 'Directory', $po( 'https://directory.branddad.social/?utm_source=branddad_social&utm_medium=nav&utm_campaign=directory_hub' ) ),
			array( 'List Your Business', $po( $list ) ),
			array( 'Logos & Websites', $po( 'https://branddad.co/?utm_source=branddad_social&utm_medium=nav' ) ),
			array( 'Hosting', $po( 'https://hosttech.net/?utm_source=branddad_social&utm_medium=nav' ) ),
			array( 'Blog', $po( 'https://branddad.social/blog/' ) ),
			array( 'About', $po( 'https://branddad.social/about-us/' ) ),
			array( 'Contact', $po( 'https://branddad.social/contact-us/' ) ),
			array( 'Cart', $po( 'https://branddad.social/cart/' ) ),
			array( 'Account', $po( 'https://branddad.social/my-account/' ) ),
			array( 'Affiliate Program', $po( 'https://affiliates.branddad.social/' ) ),
		);
		echo '<div id="bds-nav-drawer" class="bds-nav-drawer" hidden data-bds-menu-lock="never-revert"><div class="bds-nav-backdrop" data-bds-nav-close tabindex="-1"></div><div class="bds-nav-panel" role="dialog" aria-modal="true" aria-label="Site menu"><header><strong>BrandDad</strong><button type="button" class="bds-nav-close" data-bds-nav-close aria-label="Close menu">&times;</button></header><nav>';
		foreach ( $links as $link ) {
			echo '<a href="' . esc_url( $link[1] ) . '">' . esc_html( $link[0] ) . '<b>&rarr;</b></a>';
		}
		echo '</nav><div class="bds-nav-cta"><a class="primary" href="' . esc_url( $po( 'https://branddad.social/services/' ) ) . '">Browse services</a><a class="secondary" href="https://wa.me/18729105115?text=Help%20me%20choose%20a%20BrandDad%20service">WhatsApp</a></div></div></div>';
		?>
		<script id="bds-menu-lock-standalone-js" data-bds-menu-lock="never-revert">
		(function(){
		  function killLegacyHam(){
		    document.querySelectorAll('.elementor-element-edb51f5,.dipi_hamburger').forEach(function(el){
		      try{ if(el&&el.parentNode) el.parentNode.removeChild(el); }catch(err){}
		    });
		    document.querySelectorAll('a[href*="popup:open"][href*="50450"],a[href*="popup%3Aopen"][href*="50450"]').forEach(function(a){
		      try{ a.setAttribute('data-bds-legacy-ham','1'); a.removeAttribute('href'); a.style.display='none'; }catch(err){}
		    });
		  }
		  function ensure(){
		    if(document.getElementById('bds-menu-toggle')) { killLegacyHam(); return; }
		    var header=document.querySelector('.elementor-location-header .e-con-inner,.elementor-location-header .e-con,.elementor-location-header,.site-header');
		    if(!header) return;
		    var btn=document.createElement('button');
		    btn.type='button'; btn.id='bds-menu-toggle';
		    btn.setAttribute('aria-label','Open menu');
		    btn.setAttribute('aria-controls','bds-nav-drawer');
		    btn.setAttribute('aria-expanded','false');
		    btn.innerHTML='<span></span><span></span><span></span>';
		    var ham=document.querySelector('.elementor-element-edb51f5,.dipi_hamburger');
		    if(ham && ham.parentNode) ham.parentNode.insertBefore(btn, ham);
		    else header.appendChild(btn);
		    killLegacyHam();
		  }
		  function openNav(e){
		    if(e){e.preventDefault();e.stopPropagation();}
		    var d=document.getElementById('bds-nav-drawer'); if(!d) return;
		    d.hidden=false; document.body.style.overflow='hidden';
		    var t=document.getElementById('bds-menu-toggle'); if(t) t.setAttribute('aria-expanded','true');
		  }
		  function closeNav(){
		    var d=document.getElementById('bds-nav-drawer'); if(!d) return;
		    d.hidden=true; document.body.style.overflow='';
		    var t=document.getElementById('bds-menu-toggle'); if(t) t.setAttribute('aria-expanded','false');
		  }
		  document.addEventListener('click', function(e){
		    var ham=e.target.closest('#bds-menu-toggle,.elementor-element-edb51f5 a.elementor-icon,.elementor-element-edb51f5,.dipi_hamburger,a[href*="popup:open"],a[href*="popup%3Aopen"]');
		    if(ham){ openNav(e); return; }
		    if(e.target.closest('[data-bds-nav-close], .bds-nav-backdrop')) closeNav();
		    if(e.target.closest('#bds-nav-drawer nav a')) closeNav();
		  }, true);
		  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeNav(); });
		  function stripShop(){
		    var legacyDestinations={
		      'Services':'https://branddad.social/services/',
		      'Growth Systems':'https://branddad.social/growth-systems/',
		      'LinkedIn':'https://branddad.social/product/linkedin-visibility-amplification-system-for-professionals/',
		      'Reputation Management':'https://branddad.social/product/remove-negative-google-reviews/',
		      'Authority & PR':'https://branddad.social/product/press-release-services/',
		      'SEO Packages':'https://branddad.social/product/comprehensive-seo-packages-rank-1-on-google/',
		      'Web & Branding':'https://branddad.co/get-started/?utm_source=branddad_social&utm_medium=nav'
		    };
		    document.querySelectorAll('.elementor-location-header a, .site-header a, #bds-nav-drawer nav a').forEach(function(a){
		      var t=(a.textContent||'').replace(/→|&rarr;|\s+/g,' ').trim();
		      if(/^Shop$/i.test(t)){ var li=a.closest('li'); if(li) li.parentNode.removeChild(li); else a.parentNode.removeChild(a); }
		      if(legacyDestinations[t] && (!a.getAttribute('href') || /#$/.test(a.getAttribute('href')))){
		        a.setAttribute('href',legacyDestinations[t]);
		      }
		    });
		  }
		  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', function(){ ensure(); stripShop(); });
		  else { ensure(); stripShop(); }
		  setTimeout(function(){ ensure(); stripShop(); }, 400); setTimeout(function(){ ensure(); stripShop(); }, 1200);
		})();
		</script>
		<?php
	},
	20
);
