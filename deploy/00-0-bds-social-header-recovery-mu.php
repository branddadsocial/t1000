<?php
/**
 * Plugin Name: BrandDad Social Header Recovery
 * Description: Single authoritative responsive header and menu for branddad.social.
 * Version: 2.0.0
 * Author: BrandDad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BDS_SOCIAL_HEADER_RECOVERY' ) ) {
	return;
}
define( 'BDS_SOCIAL_HEADER_RECOVERY', '2.0.0' );

/* Stop the older menu-lock MU from painting a second drawer. */
if ( ! defined( 'BDS_SOCIAL_MENU_LOCK_STANDALONE' ) ) {
	define( 'BDS_SOCIAL_MENU_LOCK_STANDALONE', BDS_SOCIAL_HEADER_RECOVERY );
}

function bds_header_recovery_is_social() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( bds_header_recovery_is_social() ) {
			$classes[] = 'bds-header-recovered';
		}
		return $classes;
	}
);

function bds_header_recovery_links() {
	return array(
		'Home'             => home_url( '/' ),
		'Services'         => home_url( '/services/' ),
		'Creator Services' => home_url( '/creator-services/' ),
		'Learn'            => home_url( '/learning-center/' ),
		'Blog'             => home_url( '/blog/' ),
		'Contact'          => home_url( '/contact-us/' ),
	);
}

add_action(
	'wp_head',
	static function () {
		if ( is_admin() || ! bds_header_recovery_is_social() ) {
			return;
		}
		?>
		<style id="bds-social-header-recovery-css" data-bds-header-recovery="2.0.0">
		body.bds-header-recovered #site-header.site-header{box-sizing:border-box!important;width:min(100%,1200px)!important;max-width:1200px!important;min-height:92px!important;margin:0 auto!important;padding:14px 24px!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:24px!important;background:#fff!important;position:relative!important;z-index:100040!important}
		body.bds-header-recovered #site-header .site-branding{display:flex!important;align-items:center!important;flex:0 1 auto!important;min-width:0!important;margin:0!important;padding:0!important}
		body.bds-header-recovered #site-header .custom-logo-link{display:flex!important;align-items:center!important;max-width:340px!important;margin:0!important;padding:0!important}
		body.bds-header-recovered #site-header img.custom-logo{display:block!important;width:auto!important;height:auto!important;max-width:100%!important;max-height:58px!important;object-fit:contain!important}
		body.bds-header-recovered #site-header .site-navigation{display:flex!important;align-items:center!important;justify-content:flex-end!important;flex:1 1 auto!important;margin:0!important;padding:0!important}
		body.bds-header-recovered #site-header .site-navigation>ul{display:flex!important;align-items:center!important;justify-content:flex-end!important;gap:4px!important;list-style:none!important;margin:0!important;padding:0!important}
		body.bds-header-recovered #site-header .site-navigation>ul>li{display:block!important;margin:0!important;padding:0!important}
		body.bds-header-recovered #site-header .site-navigation>ul>li>a{display:block!important;padding:11px 12px!important;border-radius:9px!important;color:#172033!important;font-family:Manrope,"Segoe UI",sans-serif!important;font-size:15px!important;font-weight:700!important;line-height:1.2!important;text-decoration:none!important;white-space:nowrap!important}
		body.bds-header-recovered #site-header .site-navigation>ul>li>a:hover,body.bds-header-recovered #site-header .site-navigation>ul>li>a:focus-visible{background:#eef6ff!important;color:#0372ff!important;outline:none!important}
		#bds-menu-toggle{display:none;align-items:center;justify-content:center;width:46px;height:46px;flex:0 0 46px;border-radius:12px;border:1px solid #d9e2ee;background:#fff;color:#0372ff;cursor:pointer;padding:0;box-shadow:0 4px 16px rgba(15,23,42,.08)}
		#bds-menu-toggle span{display:block;width:20px;height:2px;margin:3px auto;background:currentColor;border-radius:2px}
		#bds-recovery-drawer[hidden]{display:none!important}.bds-recovery-backdrop{position:fixed;inset:0;z-index:100060;background:rgba(7,11,20,.58)}
		.bds-recovery-panel{position:fixed;top:0;right:0;z-index:100070;box-sizing:border-box;width:min(380px,90vw);height:100%;padding:24px 20px 30px;display:flex;flex-direction:column;gap:18px;background:#fff;color:#172033;box-shadow:-14px 0 40px rgba(15,23,42,.22);font-family:Manrope,"Segoe UI",sans-serif}
		.bds-recovery-panel header{display:flex;align-items:center;justify-content:space-between;gap:16px}.bds-recovery-panel header strong{font-size:20px}.bds-recovery-close{width:44px;height:44px;border:1px solid #d9e2ee;border-radius:999px;background:#fff;color:#172033;font-size:25px;line-height:1;cursor:pointer}
		.bds-recovery-panel nav{display:grid;gap:8px;overflow:auto}.bds-recovery-panel nav a{display:flex;align-items:center;justify-content:space-between;padding:13px 14px;border:1px solid #e2e8f0;border-radius:11px;background:#f8fafc;color:#172033!important;font-weight:750;text-decoration:none}.bds-recovery-panel nav a:hover,.bds-recovery-panel nav a:focus-visible{border-color:#87bfff;background:#eef6ff;color:#0372ff!important;outline:none}
		.bds-recovery-cta{display:grid;gap:9px;margin-top:auto}.bds-recovery-cta a{display:block;padding:13px;border-radius:11px;text-align:center;text-decoration:none;font-weight:800}.bds-recovery-cta .primary{background:#0372ff;color:#fff!important}.bds-recovery-cta .secondary{background:#101b31;color:#fff!important}
		body.bds-header-recovered .elementor-menu-toggle,body.bds-header-recovered .elementor-element-edb51f5,body.bds-header-recovered .dipi_hamburger{display:none!important}
		@media(max-width:1100px){body.bds-header-recovered #site-header.site-header{min-height:78px!important;padding:10px 18px!important;gap:14px!important}body.bds-header-recovered #site-header .custom-logo-link{max-width:min(330px,calc(100vw - 105px))!important}body.bds-header-recovered #site-header img.custom-logo{max-height:52px!important}body.bds-header-recovered #site-header .site-navigation{display:none!important}#bds-menu-toggle{display:inline-grid!important}}
		@media(max-width:640px){body.bds-header-recovered #site-header.site-header{min-height:68px!important;padding:8px 14px!important}body.bds-header-recovered #site-header .custom-logo-link{max-width:min(245px,calc(100vw - 88px))!important}body.bds-header-recovered #site-header img.custom-logo{max-height:44px!important}#bds-menu-toggle{width:44px;height:44px;flex-basis:44px}body.bds-header-recovered #bdsNetworkConnectStrip{overflow-x:hidden!important}}
		</style>
		<?php
	},
	1
);

add_action(
	'wp_footer',
	static function () {
		if ( is_admin() || ! bds_header_recovery_is_social() ) {
			return;
		}
		$links = bds_header_recovery_links();
		?>
		<div id="bds-recovery-drawer" hidden data-bds-header-recovery="2.0.0">
			<div class="bds-recovery-backdrop" data-bds-recovery-close></div>
			<aside class="bds-recovery-panel" role="dialog" aria-modal="true" aria-label="Site menu">
				<header><strong>BrandDad Social</strong><button type="button" class="bds-recovery-close" data-bds-recovery-close aria-label="Close menu">&times;</button></header>
				<nav aria-label="Mobile menu">
					<?php foreach ( $links as $label => $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?><span aria-hidden="true">&rarr;</span></a>
					<?php endforeach; ?>
				</nav>
				<div class="bds-recovery-cta"><a class="primary" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">Find the right service</a><a class="secondary" href="https://wa.me/18729105115?text=Help%20me%20choose%20a%20BrandDad%20service">Send us a message</a></div>
			</aside>
		</div>
		<script id="bds-social-header-recovery-js" data-bds-header-recovery="2.0.0">
		(function(){
		  var links=<?php echo wp_json_encode( $links ); ?>;
		  function normalize(){
		    var header=document.querySelector('#site-header.site-header,.site-header');
		    if(!header) return;
		    var nav=header.querySelector('.site-navigation');
		    if(nav && !nav.dataset.bdsRecovered){
		      var ul=document.createElement('ul'); ul.className='menu bds-recovery-desktop-menu';
		      Object.keys(links).forEach(function(label){var li=document.createElement('li');var a=document.createElement('a');a.href=links[label];a.textContent=label;li.appendChild(a);ul.appendChild(li);});
		      nav.replaceChildren(ul); nav.dataset.bdsRecovered='1'; nav.setAttribute('aria-label','Main menu');
		    }
		    if(!document.getElementById('bds-menu-toggle')){
		      var button=document.createElement('button');button.type='button';button.id='bds-menu-toggle';button.setAttribute('aria-label','Open menu');button.setAttribute('aria-controls','bds-recovery-drawer');button.setAttribute('aria-expanded','false');button.innerHTML='<span></span><span></span><span></span>';header.appendChild(button);
		    }
		    document.querySelectorAll('.elementor-element-edb51f5,.dipi_hamburger').forEach(function(el){el.remove();});
		  }
		  function setOpen(open){var drawer=document.getElementById('bds-recovery-drawer');var button=document.getElementById('bds-menu-toggle');if(!drawer)return;drawer.hidden=!open;document.body.style.overflow=open?'hidden':'';if(button)button.setAttribute('aria-expanded',open?'true':'false');if(open){var close=drawer.querySelector('.bds-recovery-close');if(close)close.focus();}else if(button){button.focus();}}
		  document.addEventListener('click',function(e){if(e.target.closest('#bds-menu-toggle')){e.preventDefault();setOpen(true);return;}if(e.target.closest('[data-bds-recovery-close]')||e.target.closest('#bds-recovery-drawer nav a'))setOpen(false);},true);
		  document.addEventListener('keydown',function(e){if(e.key==='Escape')setOpen(false);});
		  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',normalize);else normalize();
		  new MutationObserver(function(){if(!document.getElementById('bds-menu-toggle'))normalize();}).observe(document.documentElement,{childList:true,subtree:true});
		})();
		</script>
		<?php
	},
	1
);

