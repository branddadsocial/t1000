<?php
/**
 * Plugin Name: BrandDad Codex Surface Bridge
 * Description: Cursor-owned checkout, geography, email-proof, and dashboard wiring for the Codex backend. Does not charge, send mail, or enable Codex flags.
 * Version: 0.1.3
 * Author: BrandDad
 *
 * Secure design (auth / API / ingestion / payments):
 * - Humans keep the existing WordPress cookie. No second login. Account-link HTML is rendered only when Codex supplies bds_account_link_form().
 * - Email proof fails closed. An email string on the user is not enough. Only allowlisted verification meta is true.
 * - Checkout consent is optional, unselected, and stored as POST bds_directory_membership_consent. Codex writes order meta.
 * - Featured geography is listing post meta only. Billing address and request coordinates are ignored.
 * - Featured SKU creation is manage_woocommerce + nonce, never automatic, and never changes an existing SKU price.
 * - Lead/inquiry/monitor/AI browsers call existing Codex REST routes. This plugin adds no write API and no secrets.
 * - Inquiry form_reference and lead fields are length/allowlist bounded. Honeypot company_website must stay blank.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/bds-codex-bridge-lib.php';

function bds_codex_bridge_hosts() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' ), true );
}

function bds_codex_bridge_is_social() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

function bds_codex_bridge_is_directory() {
	return 'directory.branddad.social' === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
}

/** Fail closed unless a known verification proof is stored on this user. */
function bds_codex_bridge_email_verified( $verified, $user ) {
	if ( true === $verified ) {
		return true;
	}
	$user_id = 0;
	if ( $user instanceof WP_User ) {
		$user_id = (int) $user->ID;
	} elseif ( is_numeric( $user ) ) {
		$user_id = (int) $user;
	}
	if ( $user_id < 1 ) {
		return false;
	}
	$meta = array(
		'_bds_email_verified' => get_user_meta( $user_id, '_bds_email_verified', true ),
		'_email_verified'     => get_user_meta( $user_id, '_email_verified', true ),
		'is_email_verified'   => get_user_meta( $user_id, 'is_email_verified', true ),
	);
	return bds_codex_email_verified_from_meta( $meta );
}
add_filter( 'bds_account_link_email_verified', 'bds_codex_bridge_email_verified', 10, 2 );
add_filter( 'bds_lead_email_verified', 'bds_codex_bridge_email_verified', 10, 2 );

/** Listing geography for Featured billing. Billing country is never read. */
function bds_codex_bridge_featured_geo( $geo, $listing_id ) {
	$listing_id = absint( $listing_id );
	$post       = $listing_id ? get_post( $listing_id ) : null;
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		return is_array( $geo ) ? $geo : array();
	}
	$keys = array( 'country', '_country', 'address_country', 'iso2', 'manual_lat', '_manual_lat', 'latitude', 'map_lat', 'manual_lng', '_manual_lng', 'longitude', 'map_lng' );
	$meta = array();
	foreach ( $keys as $key ) {
		$meta[ $key ] = get_post_meta( $listing_id, $key, true );
	}
	return bds_codex_listing_geo_from_meta( $meta );
}
add_filter( 'bds_featured_listing_geo', 'bds_codex_bridge_featured_geo', 10, 2 );

function bds_codex_bridge_cart_has_essentials() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		$product = isset( $item['data'] ) ? $item['data'] : null;
		$slug    = $product && method_exists( $product, 'get_slug' ) ? (string) $product->get_slug() : '';
		if ( 'ai-business-essentials' === $slug ) {
			return true;
		}
	}
	return false;
}

/** Optional, unselected Directory membership consent on Business Essentials checkout. */
function bds_codex_bridge_checkout_consent() {
	if ( ! bds_codex_bridge_is_social() || ! bds_codex_bridge_cart_has_essentials() ) {
		return;
	}
	$sw = function_exists( 'bds_lang_swap_attrs' );
	echo '<p class="form-row" id="bds-directory-membership-consent">';
	echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">';
	echo '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="bds_directory_membership_consent" value="1" /> ';
	echo '<span' . ( $sw ? bds_lang_swap_attrs( 'Also create a free BrandDad Directory account. This does not list a business or add Directory Pro.', 'También cree una cuenta gratis del directorio BrandDad. Esto no lista un negocio ni agrega Directory Pro.' ) : '' ) . '>';
	echo esc_html__( 'Also create a free BrandDad Directory account. This does not list a business or add Directory Pro.', 'branddad' );
	echo '</span></label></p>';
}
add_action( 'woocommerce_review_order_before_submit', 'bds_codex_bridge_checkout_consent', 20 );

function bds_codex_bridge_requested_listing() {
	$raw = isset( $_GET['listing'] ) ? wp_unslash( $_GET['listing'] ) : ( isset( $_POST['_bds_feat_listing_id'] ) ? wp_unslash( $_POST['_bds_feat_listing_id'] ) : '' );
	$id  = absint( $raw );
	if ( $id < 1 && function_exists( 'is_singular' ) && is_singular( 'at_biz_dir' ) ) {
		$id = absint( get_queried_object_id() );
	}
	if ( $id < 1 ) {
		return 0;
	}
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || 'at_biz_dir' !== $post->post_type || 'publish' !== $post->post_status ) {
		return 0;
	}
	return $id;
}

function bds_codex_bridge_featured_product_page() {
	if ( ! bds_codex_bridge_is_directory() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$sku = '';
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_queried_object_id() );
		$sku     = $product && method_exists( $product, 'get_sku' ) ? sanitize_key( (string) $product->get_sku() ) : '';
	}
	if ( ! isset( bds_codex_featured_skus()[ $sku ] ) ) {
		return;
	}
	$listing = bds_codex_bridge_requested_listing();
	$prompt  = function_exists( 'bds_codex_featured_listing_prompt' ) ? bds_codex_featured_listing_prompt( $listing ) : array( 'attached' => false, 'line' => '' );
	if ( ! empty( $prompt['line'] ) ) {
		echo '<p id="bds-feat-listing-note">' . esc_html( (string) $prompt['line'] ) . '</p>';
	}
	if ( $listing < 1 ) {
		return;
	}
	echo '<input type="hidden" name="_bds_feat_listing_id" value="' . esc_attr( (string) $listing ) . '" />';
}
add_action( 'woocommerce_before_add_to_cart_button', 'bds_codex_bridge_featured_product_page', 20 );

function bds_codex_bridge_filter_featured_url( $url, $level = '' ) {
	$listing = bds_codex_bridge_requested_listing();
	return bds_codex_featured_url_with_listing( (string) $url, $listing );
}
add_filter( 'bds_feat_product_permalink', 'bds_codex_bridge_filter_featured_url', 10, 2 );

function bds_codex_bridge_styles() {
	if ( ! bds_codex_bridge_hosts() ) {
		return;
	}
	echo '<style id="bds-codex-bridge-css">#bds-codex-lead,#bds-codex-inquiry,#bds-codex-hub-extra{max-width:720px;margin:24px auto;padding:20px;border:1px solid #dbe5ef;border-radius:16px;background:#fff;color:#0b172a}#bds-codex-lead label,#bds-codex-inquiry label,#bds-codex-hub-extra label{display:flex;flex-direction:column;gap:6px;margin:10px 0}#bds-codex-lead input,#bds-codex-lead select,#bds-codex-inquiry input,#bds-codex-inquiry textarea,#bds-codex-hub-extra input,#bds-codex-hub-extra select{min-height:44px;padding:8px;border:1px solid #dbe5ef;border-radius:8px}#bds-codex-lead button,#bds-codex-inquiry button,#bds-codex-hub-extra button{min-height:44px;margin-top:8px;padding:0 16px;border:0;border-radius:10px;background:#0668d8;color:#fff;font-weight:800}#bds-codex-lead .bds-hp,#bds-codex-inquiry .bds-hp{position:absolute;left:-9999px}</style>';
}
add_action( 'wp_head', 'bds_codex_bridge_styles', 42 );

function bds_codex_bridge_lead_html() {
	if ( ! bds_codex_bridge_is_social() ) {
		return '';
	}
	$sw    = function_exists( 'bds_lang_swap_attrs' );
	$email = is_user_logged_in() ? (string) wp_get_current_user()->user_email : '';
	$html  = '<form id="bds-codex-lead" hidden data-rest="' . esc_url( rest_url( 'branddad/v1/sales/leads' ) ) . '">';
	$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'Tell BrandDad what you need', 'Diga a BrandDad lo que necesita' ) : '' ) . '>' . esc_html__( 'Tell BrandDad what you need', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'This saves a private request. Email notes are optional and stay off unless you check the second box.', 'Esto guarda una solicitud privada. El correo es opcional y queda apagado salvo que marque la segunda casilla.' );
	}
	$html .= '<p class="bds-hp" aria-hidden="true"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off" /></label></p>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'Email', 'Correo' ) : '' ) . '>' . esc_html__( 'Email', 'branddad' ) . '</span><input type="email" name="email" maxlength="120" required value="' . esc_attr( $email ) . '"' . ( $email ? ' readonly' : '' ) . ' /></label>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'Business type', 'Tipo de negocio' ) : '' ) . '>' . esc_html__( 'Business type', 'branddad' ) . '</span><input name="business_type" maxlength="120" /></label>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'What you need', 'Qué necesita' ) : '' ) . '>' . esc_html__( 'What you need', 'branddad' ) . '</span><input name="primary_need" maxlength="240" /></label>';
	$html .= '<input type="hidden" name="source" value="help" />';
	$html .= '<label><input type="checkbox" name="privacy_ack" value="1" required /> <span' . ( $sw ? bds_lang_swap_attrs( 'I understand BrandDad stores this request to help me choose a plan. It is not a purchase.', 'Entiendo que BrandDad guarda esta solicitud para ayudarme a elegir un plan. No es una compra.' ) : '' ) . '>' . esc_html__( 'I understand BrandDad stores this request to help me choose a plan. It is not a purchase.', 'branddad' ) . '</span></label>';
	$html .= '<label><input type="checkbox" name="email_consent" value="1" /> <span' . ( $sw ? bds_lang_swap_attrs( 'Also send the next-step note by email. Leave this unchecked if you only want the on-page answer.', 'También envíe la nota del siguiente paso por correo. Déjelo sin marcar si solo quiere la respuesta en la página.' ) : '' ) . '>' . esc_html__( 'Also send the next-step note by email. Leave this unchecked if you only want the on-page answer.', 'branddad' ) . '</span></label>';
	$html .= '<button type="submit"' . ( $sw ? bds_lang_swap_attrs( 'Save this request', 'Guardar esta solicitud' ) : '' ) . '>' . esc_html__( 'Save this request', 'branddad' ) . '</button>';
	$html .= '<p class="bds-codex-lead-status" role="status"></p></form>';
	return $html;
}

function bds_codex_bridge_inquiry_html() {
	$ref = isset( $_GET['form'] ) ? sanitize_text_field( wp_unslash( $_GET['form'] ) ) : '';
	if ( ! bds_codex_form_reference_ok( $ref ) ) {
		return '';
	}
	$sw   = function_exists( 'bds_lang_swap_attrs' );
	$html = '<form id="bds-codex-inquiry" data-rest="' . esc_url( rest_url( 'branddad/v1/inquiries/submit' ) ) . '" data-ref="' . esc_attr( $ref ) . '">';
	$html .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'Contact this business', 'Contactar este negocio' ) : '' ) . '>' . esc_html__( 'Contact this business', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'BrandDad hosts this form. It does not text or email the business until that notice is verified.', 'BrandDad aloja este formulario. No envía texto ni correo al negocio hasta verificar ese aviso.' );
	}
	$html .= '<p class="bds-hp" aria-hidden="true"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off" /></label></p>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'Name', 'Nombre' ) : '' ) . '>' . esc_html__( 'Name', 'branddad' ) . '</span><input name="name" maxlength="120" required /></label>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'Email', 'Correo' ) : '' ) . '>' . esc_html__( 'Email', 'branddad' ) . '</span><input type="email" name="email" maxlength="120" /></label>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'Phone', 'Teléfono' ) : '' ) . '>' . esc_html__( 'Phone', 'branddad' ) . '</span><input name="phone" maxlength="40" /></label>';
	$html .= '<label><span' . ( $sw ? bds_lang_swap_attrs( 'Message', 'Mensaje' ) : '' ) . '>' . esc_html__( 'Message', 'branddad' ) . '</span><textarea name="message" minlength="5" maxlength="2000" required></textarea></label>';
	$html .= '<label><input type="checkbox" name="privacy_ack" value="1" required /> <span' . ( $sw ? bds_lang_swap_attrs( 'I understand this message is stored for the business owner.', 'Entiendo que este mensaje se guarda para el dueño del negocio.' ) : '' ) . '>' . esc_html__( 'I understand this message is stored for the business owner.', 'branddad' ) . '</span></label>';
	$html .= '<label><input type="checkbox" name="contact_consent" value="1" required /> <span' . ( $sw ? bds_lang_swap_attrs( 'The business may use these details to reply to this request.', 'El negocio puede usar estos datos para responder esta solicitud.' ) : '' ) . '>' . esc_html__( 'The business may use these details to reply to this request.', 'branddad' ) . '</span></label>';
	$html .= '<button type="submit"' . ( $sw ? bds_lang_swap_attrs( 'Send the request', 'Enviar la solicitud' ) : '' ) . '>' . esc_html__( 'Send the request', 'branddad' ) . '</button>';
	$html .= '<p class="bds-codex-inquiry-status" role="status"></p></form>';
	return $html;
}

function bds_codex_hub_html() {
	if ( ! bds_codex_bridge_hosts() || ! is_user_logged_in() ) {
		return '';
	}
	$sw     = function_exists( 'bds_lang_swap_attrs' );
	$nonce  = wp_create_nonce( 'wp_rest' );
	$dest   = bds_codex_bridge_is_social() ? 'directory.branddad.social' : 'branddad.social';
	$link   = function_exists( 'bds_account_link_form' ) ? bds_account_link_form( $dest, '/account-hub/' ) : '';
	$html   = '<section id="bds-codex-hub-extra" data-nonce="' . esc_attr( $nonce ) . '" data-rest="' . esc_url( rest_url( 'branddad/v1' ) ) . '">';
	$html  .= '<h2' . ( $sw ? bds_lang_swap_attrs( 'Your BrandDad tools', 'Sus herramientas BrandDad' ) : '' ) . '>' . esc_html__( 'Your BrandDad tools', 'branddad' ) . '</h2>';
	if ( function_exists( 'bds_lang_pair' ) ) {
		$html .= bds_lang_pair( 'These panels read the live account APIs. Empty means that backend is off or not installed yet. They do not invent a balance.', 'Estos paneles leen las APIs de la cuenta. Vacío significa que ese backend está apagado o aún no está instalado. No inventan un saldo.' );
	}
	$html .= $link;
	$html .= '<div data-bds-codex="status"></div>';
	$html .= '<div data-bds-codex="trial"></div>';
	$html .= '<form data-bds-codex="onboarding"><label>Business name <input name="business_name" maxlength="120" /></label><label>Website <input name="website_url" maxlength="200" /></label><label><input type="checkbox" name="authorization_confirmed" value="1" /> I own or manage this website and allow availability checks.</label><button type="submit">Save onboarding</button></form>';
	$html .= '<form data-bds-codex="monitor"><button type="submit" name="check" value="1">Check my website</button></form>';
	$html .= '<form data-bds-codex="ai"><select name="tool"></select><button type="submit">Start this task</button><p data-bds-codex="ai-status"></p></form>';
	$html .= '<div data-bds-codex="inquiries"></div></section>';
	return $html;
}

function bds_codex_bridge_footer() {
	if ( ! bds_codex_bridge_hosts() ) {
		return;
	}
	$path = strtolower( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ) );
	$path = trim( $path, '/' );
	if ( bds_codex_bridge_is_social() && in_array( $path, array( '', 'services', 'contact-us', 'help', 'check-your-website' ), true ) ) {
		echo bds_codex_bridge_lead_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( isset( $_GET['form'] ) ) {
		echo bds_codex_bridge_inquiry_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	$nonce = wp_create_nonce( 'wp_rest' );
	echo '<script id="bds-codex-bridge-js">';
	echo 'window.bdsCodexBridge=' . wp_json_encode( array( 'nonce' => $nonce, 'rest' => rest_url( 'branddad/v1' ) ) ) . ';';
	echo <<<'JS'
(function(){
  var cfg=window.bdsCodexBridge||{};
  function headers(extra){
    var h={'Content-Type':'application/json','X-WP-Nonce':cfg.nonce||''};
    if(extra){for(var k in extra){h[k]=extra[k];}}
    return h;
  }
  function show(el,text){if(el){el.textContent=text||'';}}
  var lead=document.getElementById('bds-codex-lead');
  if(lead){
    fetch((lead.getAttribute('data-rest')||''),{method:'OPTIONS',headers:headers()}).then(function(r){
      if(r.status===404||r.status===503){return;}
      lead.hidden=false;
    }).catch(function(){});
    lead.addEventListener('submit',function(ev){
      ev.preventDefault();
      if((lead.company_website&&lead.company_website.value)||!lead.privacy_ack.checked){return;}
      var body={email:lead.email.value,business_type:lead.business_type.value,primary_need:lead.primary_need.value,source:lead.source.value,privacy_ack:true,email_consent:!!lead.email_consent.checked};
      fetch(lead.getAttribute('data-rest'),{method:'POST',credentials:'same-origin',headers:headers(),body:JSON.stringify(body)}).then(function(r){return r.json().then(function(j){return {ok:r.ok,j:j,status:r.status};});}).then(function(res){
        var rec=res.j&&res.j.recommendation&&res.j.recommendation.url?res.j.recommendation.url:'';
        var msg=res.ok?'Saved.':(res.status===503?'Not open yet.':'Could not save.');
        if(res.j&&res.j.email_confirmation_required){msg='Saved. Sign in and opt in again after your email is verified.';}
        show(lead.querySelector('.bds-codex-lead-status'),msg+(rec?' Next: '+rec:''));
      }).catch(function(){show(lead.querySelector('.bds-codex-lead-status'),'Could not save.');});
    });
  }
  var inq=document.getElementById('bds-codex-inquiry');
  if(inq){
    inq.addEventListener('submit',function(ev){
      ev.preventDefault();
      if((inq.company_website&&inq.company_website.value)||!inq.privacy_ack.checked||!inq.contact_consent.checked){return;}
      var key=(window.crypto&&crypto.randomUUID)?crypto.randomUUID().replace(/-/g,'').slice(0,32):('k'+Date.now()+'xxxxxxxx'.replace(/x/g,function(){return Math.floor(Math.random()*16).toString(16);}));
      var body={form_reference:inq.getAttribute('data-ref'),name:inq.name.value,email:inq.email.value,phone:inq.phone.value,message:inq.message.value,privacy_ack:true,contact_consent:true,company_website:''};
      fetch(inq.getAttribute('data-rest'),{method:'POST',credentials:'same-origin',headers:headers({'Idempotency-Key':key}),body:JSON.stringify(body)}).then(function(r){return r.json().then(function(j){return {ok:r.ok,j:j};});}).then(function(res){
        show(inq.querySelector('.bds-codex-inquiry-status'),res.ok?'Received. The business was not emailed from this page.':'Could not send.');
      }).catch(function(){show(inq.querySelector('.bds-codex-inquiry-status'),'Could not send.');});
    });
  }
  var hub=document.getElementById('bds-codex-hub-extra');
  if(!hub){return;}
  var root=hub.getAttribute('data-rest')||cfg.rest;
  function get(path){return fetch(root+path,{credentials:'same-origin',headers:headers()}).then(function(r){return r.ok?r.json():null;});}
  get('/account/status').then(function(j){
    if(!j){return;}
    var a=j.access||{};
    var el=hub.querySelector('[data-bds-codex="status"]');
    if(el){el.textContent=(a.mode||'none')+(a.plan_slug?' · '+a.plan_slug:'');}
  });
  get('/trial').then(function(j){
    if(!j){return;}
    var el=hub.querySelector('[data-bds-codex="trial"]');
    if(!el){return;}
    var line='Trial '+(j.status||'off');
    if(j.status==='eligible'){line='7-day trial available. Directory membership is not included.';}
    else if(j.active){line='Trial active'+(j.days_remaining?(' · '+j.days_remaining+' days left'):'')+'. Directory membership is not included.';}
    else if(j.status==='paid'){line='AI Business Essentials is active.';}
    else if(j.status==='consumed'){line='This account already used the 7-day trial.';}
    el.textContent=line;
  });
  get('/ai/tools').then(function(j){
    var sel=hub.querySelector('[data-bds-codex="ai"] select');
    if(!sel||!j||!j.tools){return;}
    j.tools.forEach(function(t){var o=document.createElement('option');o.value=t.key;o.textContent=t.label+(t.credit_cost?' · '+t.credit_cost+' credits':'');sel.appendChild(o);});
  });
  var onb=hub.querySelector('[data-bds-codex="onboarding"]');
  if(onb){
    var version=0;
    get('/onboarding').then(function(j){if(!j){return;}version=j.version||0;if(j.profile){if(onb.business_name){onb.business_name.value=j.profile.business_name||'';}if(onb.website_url){onb.website_url.value=j.profile.website_url||'';}}});
    onb.addEventListener('submit',function(ev){
      ev.preventDefault();
      fetch(root+'/onboarding',{method:'POST',credentials:'same-origin',headers:headers(),body:JSON.stringify({version:version,profile:{business_name:onb.business_name.value,website_url:onb.website_url.value}})}).then(function(r){if(r.status===409){location.reload();return;}if(onb.authorization_confirmed&&onb.authorization_confirmed.checked){return fetch(root+'/monitor/site',{method:'POST',credentials:'same-origin',headers:headers(),body:JSON.stringify({enabled:true,authorization_confirmed:true})});}});
    });
  }
  var mon=hub.querySelector('[data-bds-codex="monitor"]');
  if(mon){
    mon.addEventListener('submit',function(ev){
      ev.preventDefault();
      fetch(root+'/monitor/site/check',{method:'POST',credentials:'same-origin',headers:headers()}).then(function(r){
        if(r.status===429){alert('Already requested this hour.');}
        else if(r.status===422){alert('Add and authorize your website first.');}
      });
    });
  }
  var ai=hub.querySelector('[data-bds-codex="ai"]');
  if(ai){
    ai.addEventListener('submit',function(ev){
      ev.preventDefault();
      var key=(window.crypto&&crypto.randomUUID)?crypto.randomUUID().replace(/-/g,''):('job'+Date.now());
      fetch(root+'/ai/jobs',{method:'POST',credentials:'same-origin',headers:headers({'Idempotency-Key':key}),body:JSON.stringify({tool:ai.tool.value,inputs:{}})}).then(function(r){return r.json().then(function(j){show(hub.querySelector('[data-bds-codex="ai-status"]'),j.status||('HTTP '+r.status));});});
    });
  }
})();
JS;
	echo '</script>';
}
add_action( 'wp_footer', 'bds_codex_bridge_footer', 28 );

function bds_codex_sku_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$skus   = bds_codex_featured_skus();
	$notice = '';
	if ( isset( $_POST['bds_codex_sku_create'] ) ) {
		check_admin_referer( 'bds_codex_sku_create' );
		$notice = bds_codex_sku_create_missing();
	}
	echo '<div class="wrap"><h1>Featured SKUs</h1>';
	if ( $notice ) {
		echo '<p>' . esc_html( $notice ) . '</p>';
	}
	echo '<p>Creates only missing Featured products. Existing SKUs and prices are left alone. This page does not take a payment.</p><ul>';
	foreach ( $skus as $sku => $row ) {
		$pid = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $sku ) : 0;
		echo '<li>' . esc_html( $sku . ' $' . $row['monthly'] . ' — ' . ( $pid ? 'exists #' . $pid : 'missing' ) ) . '</li>';
	}
	echo '</ul><form method="post">';
	wp_nonce_field( 'bds_codex_sku_create' );
	echo '<p><button class="button button-primary" name="bds_codex_sku_create" value="1">Create missing Featured products</button></p></form></div>';
}

function bds_codex_sku_create_missing() {
	if ( ! bds_codex_bridge_is_directory() || ! class_exists( 'WC_Product_Simple' ) ) {
		return 'WooCommerce is not available on Directory.';
	}
	if ( ! function_exists( 'bds_featured_billing_sync' ) ) {
		return 'Deploy the Featured billing adapter first. Products were not created.';
	}
	$created = 0;
	$skipped = 0;
	foreach ( bds_codex_featured_skus() as $sku => $row ) {
		$existing = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $sku ) : 0;
		if ( $existing > 0 ) {
			$skipped++;
			continue;
		}
		$product = new WC_Product_Simple();
		$product->set_name( $row['name'] );
		$product->set_sku( $sku );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( $row['monthly'] );
		$product->set_price( $row['monthly'] );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product_id = $product->save();
		if ( ! $product_id ) {
			continue;
		}
		update_post_meta( $product_id, '_wps_sfw_product', 'yes' );
		update_post_meta( $product_id, 'wps_sfw_subscription_number', '1' );
		update_post_meta( $product_id, 'wps_sfw_subscription_interval', 'month' );
		update_post_meta( $product_id, 'wps_sfw_subscription_expiry_number', '' );
		update_post_meta( $product_id, 'wps_sfw_subscription_expiry_interval', '' );
		update_post_meta( $product_id, 'wps_sfw_subscription_initial_signup_price', '0' );
		update_post_meta( $product_id, 'wps_sfw_subscription_free_trial_number', '0' );
		update_post_meta( $product_id, 'wps_sfw_subscription_free_trial_interval', 'day' );
		$created++;
	}
	return 'Created ' . $created . ', left existing ' . $skipped . '.';
}

function bds_codex_sku_admin_menu() {
	if ( ! bds_codex_bridge_is_directory() ) {
		return;
	}
	add_management_page( 'Featured SKUs', 'Featured SKUs', 'manage_woocommerce', 'bds-featured-skus', 'bds_codex_sku_admin_page' );
}
add_action( 'admin_menu', 'bds_codex_sku_admin_menu' );

/**
 * Shop-manager inspect only. Booleans from defined() / gateway enabled. No keys, no reconnect, no charge.
 * Auth: WordPress cookie + manage_woocommerce. No new session. Missing cap prints nothing.
 */
function bds_codex_bridge_ops_snapshot() {
	$stripe = false;
	if ( class_exists( 'WC_Payment_Gateways' ) ) {
		$gateways = WC_Payment_Gateways::instance()->payment_gateways();
		$stripe   = isset( $gateways['stripe'] ) && is_object( $gateways['stripe'] ) && 'yes' === (string) $gateways['stripe']->enabled;
	}
	$hooks = 0;
	if ( function_exists( 'wc_get_webhooks' ) ) {
		$rows  = wc_get_webhooks( array( 'status' => 'active', 'limit' => 20 ) );
		$hooks = is_array( $rows ) ? count( $rows ) : 0;
	}
	$flag = static function ( $name ) {
		return defined( $name ) && true === constant( $name );
	};
	$key = static function ( $name ) {
		return defined( $name ) && '' !== trim( (string) constant( $name ) );
	};
	return array(
		'stripe_gateway'       => $stripe,
		'woo_webhooks'         => $hooks > 0,
		'formwise_exec'        => $key( 'BDS_FORMWISE_EXECUTION_KEY' ),
		'formwise_prov'        => $key( 'BDS_FORMWISE_PROVISIONING_KEY' ),
		'followup_flag'        => $flag( 'BDS_FOLLOWUP_ENABLED' ),
		'leads_flag'           => $flag( 'BDS_LEAD_CAPTURE_ENABLED' ),
		'inquiries_flag'       => $flag( 'BDS_CUSTOMER_INQUIRIES_ENABLED' ),
		'monitor_flag'         => $flag( 'BDS_WEBSITE_MONITORING_ENABLED' ),
		'sales_assistant_flag' => $flag( 'BDS_SALES_ASSISTANT_ENABLED' ),
		'entitlements_flag'    => $flag( 'BDS_AI_ENTITLEMENTS_ENABLED' ),
	);
}

function bds_codex_bridge_ops_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	if ( ! function_exists( 'bds_hub_ops_inspect_view' ) ) {
		$lib = __DIR__ . '/bds-customer-hub-lib.php';
		if ( is_readable( $lib ) ) {
			require_once $lib;
		}
	}
	$view = function_exists( 'bds_hub_ops_inspect_view' ) ? bds_hub_ops_inspect_view( bds_codex_bridge_ops_snapshot() ) : array( 'state' => 'owner_blocked', 'lines' => array() );
	echo '<div class="wrap"><h1>BrandDad billing inspect</h1>';
	echo '<p>Read-only. This page does not reconnect Stripe, print a secret, enable a flag, or take a payment.</p>';
	echo '<p><strong>State: ' . esc_html( (string) $view['state'] ) . '</strong></p><ul>';
	foreach ( $view['lines'] as $line ) {
		echo '<li>' . esc_html( (string) $line ) . '</li>';
	}
	echo '</ul></div>';
}

function bds_codex_bridge_ops_menu() {
	if ( ! bds_codex_bridge_hosts() ) {
		return;
	}
	add_management_page( 'Billing inspect', 'Billing inspect', 'manage_woocommerce', 'bds-billing-inspect', 'bds_codex_bridge_ops_page' );
}
add_action( 'admin_menu', 'bds_codex_bridge_ops_menu' );
