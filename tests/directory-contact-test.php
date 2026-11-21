<?php
/**
 * Listing contact choice. The support number is never used as a business number.
 */
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function untrailingslashit( $s ) { return rtrim( (string) $s, "/\\\\" ); }
require 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-qa-repair-mu.php';

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

assert_same( 'https://wa.me/5219981234567', bds_dqa_listing_contact_url( '+52 1 998 123 4567', '' ), 'a real WhatsApp becomes wa.me' );
assert_same( '', bds_dqa_listing_contact_url( '18729105115', '' ), 'the BrandDad support number is never a listing contact' );
assert_same( 'https://example.com', bds_dqa_listing_contact_url( '18729105115', 'https://example.com' ), 'a real website remains when the stored number is the support number' );
assert_same( 'https://example.com/menu', bds_dqa_listing_contact_url( '', 'https://example.com/menu' ), 'a real website is the contact when WhatsApp is missing' );
assert_same( '', bds_dqa_listing_contact_url( '123', 'javascript:alert(1)' ), 'a short number and a script URL are refused' );
assert_same( '', bds_dqa_listing_contact_url( '', 'example.com' ), 'a website without http is refused' );
$hero = file_get_contents( 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-front-compose-mu.php' );
assert_same( true, false !== strpos( $hero, 'Find a business. See what it offers. Contact it.' ), 'the English homepage line stays' );
assert_same( true, false !== strpos( $hero, 'Listarlo es gratis' ) && false !== strpos( $hero, 'bds_lang_pair' ), 'Spanish hero copy is tagged for the switcher' );
assert_same( true, false !== strpos( $hero, 'Agregue su negocio' ) && false !== strpos( $hero, 'bds_lang_pair' ), 'Spanish owner-band copy is tagged for the switcher' );
assert_same( true, false !== strpos( $hero, 'bds-home-cta-mid' ) && false !== strpos( $hero, 'bds-home-cta-bottom' ), 'the homepage CTA bands stay' );
assert_same( true, false !== strpos( $hero, '1.5.35' ), 'compose keeps one homepage H1' );
assert_same( 1, substr_count( $hero, '<h1 class="bds-wah__title"' ), 'the homepage template still has one hero H1' );
assert_same( true, false !== strpos( $hero, 'min-height:44px' ), 'homepage hero buttons meet the phone tap size' );
$card = '<article class="bdd-experience"><p class="bdd-eyebrow">First listing</p><h3>Activation</h3><p><strong>$6.99 one-time.</strong> Publish one business listing with no monthly plan required.</p><div class="bdd-experience__actions"><a href="https://directory.branddad.social/membership-checkout/">Publish</a></div></article>';
$fixed = bds_dqa_rewrite_reported_html( $card );
assert_same( true, false !== strpos( $fixed, 'Free listing' ), 'the Activation card becomes a free listing' );
assert_same( true, false === strpos( $fixed, 'Publish one business listing with no monthly plan required.' ), 'the required-$6.99 sentence is removed' );
assert_same( true, false !== strpos( $fixed, '/add-listing/' ), 'the Activation card points at add-listing' );
$lorem = bds_dqa_rewrite_reported_html( '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>' );
assert_same( true, false === strpos( $lorem, 'Lorem ipsum' ), 'placeholder footer copy is replaced' );
$travel = bds_dqa_surface_next( 'flights' );
assert_same( true, false !== strpos( $travel, 'does not sell tickets' ), 'travel says BrandDad does not sell tickets' );
assert_same( true, false !== strpos( $travel, 'List my business free' ), 'travel still offers the free listing' );
assert_same( true, false === strpos( $travel, 'TravelPayouts' ), 'travel does not name a booking engine' );
assert_same( '', bds_dqa_surface_next( 'help' ), 'help does not get the travel band' );
assert_same( '', bds_dqa_surface_next( '' ), 'the homepage does not get the travel band' );
require 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-member-desk-mu.php';
assert_same( true, false !== strpos( bds_desk_next_copy( false, 'publish' ), 'already has its one listing' ), 'a used free listing says what to do with it' );
assert_same( true, false !== strpos( bds_desk_next_copy( false, 'pending' ), 'in review' ), 'a listing in review says it is not public yet' );
assert_same( true, false !== strpos( bds_desk_next_copy( true, '' ), 'A basic listing is free' ), 'an account that can still list is told to add one' );
assert_same( true, false !== strpos( bds_desk_next_copy_es( false, 'publish' ), 'ya tiene su listado' ), 'Spanish next-step copy exists for a used listing' );
$desk_src = file_get_contents( 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-member-desk-mu.php' );
assert_same( true, false !== strpos( $desk_src, '1.0.4' ), 'desk stamps 1.0.4' );
$heads = bds_dqa_rewrite_reported_html( '<h1>About Us</h1><h1 class="theme-section-title__title">About BrandDad Social</h1>' );
assert_same( true, false === strpos( $heads, '<h1>About Us</h1>' ), 'Directory about keeps one H1' );
assert_same( true, false !== strpos( $heads, 'About BrandDad Social' ), 'the real Directory about heading stays' );
$contact = bds_dqa_rewrite_reported_html( '<h1 class="elementor-heading-title">Contact Us</h1><h1>Contact Info</h1>' );
assert_same( true, 1 === substr_count( strtolower( $contact ), '<h1' ), 'Directory contact keeps one H1' );
$qa_src = file_get_contents( 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-directory-qa-repair-mu.php' );
assert_same( true, false !== strpos( $qa_src, '1.0.21' ), 'QA rewrite version' );
assert_same( true, false !== strpos( $qa_src, 'bds_dqa_pair' ), 'QA Spanish is tagged for the switcher' );
assert_same( true, false === strpos( $qa_src, "echo '<p>Abra el perfil" ), 'browse Spanish is not a stacked extra paragraph' );
assert_same( true, false === strpos( $qa_src, "echo '<p>Pruebe un nombre" ), 'empty-search Spanish is not a stacked extra paragraph' );
assert_same( true, false === strpos( $qa_src, "echo '<p>Busque un negocio y use" ), 'help Spanish is not a stacked extra paragraph' );
$huge = str_repeat( 'x', 400000 ) . '<h1 class="elementor-heading-title">Contact Us</h1><h1>Contact Info</h1>' . str_repeat( 'x', 200000 );
$huge_out = bds_dqa_demote_duplicate_h1( $huge );
assert_same( true, 1 === substr_count( strtolower( $huge_out ), '<h1' ), 'a huge contact page still keeps one H1' );
assert_same( true, false !== strpos( $huge_out, '<!-- bds-dqa-h1:1.0.21 -->' ), 'a huge contact page records the H1 rewrite' );
$home = bds_dqa_demote_duplicate_h1( '<h1 id="bdd-title">Find What You Need Near You—Fast.</h1><h1 class="bds-wah__title">Find a business. See what it offers. Contact it.</h1>' );
assert_same( true, false !== strpos( $home, '<h1 id="bdd-title">Find What You Need Near You—Fast.</h1>' ), 'the current Directory Experience hero stays the homepage H1' );
assert_same( true, false === strpos( $home, '<h1 class="bds-wah__title">' ), 'the retired WhatsApp hero is not another H1' );
$demo_modal = '<main>Events</main><div class="user-refund-modal" id="dcl-claim-modal"><div class="overlay"></div><div class="content"><form><textarea name="payment_details"></textarea></form></div></div><script>window.keepMe=true;</script>';
$events_clean = bds_dqa_strip_public_refund_modal( $demo_modal, '/all-listings/', 'events' );
assert_same( true, false === strpos( $events_clean, 'dcl-claim-modal' ) && false !== strpos( $events_clean, 'window.keepMe' ), 'Events removes only the demo refund modal' );
$jobs_clean = bds_dqa_strip_public_refund_modal( $demo_modal, '/all-listings/', 'jobs-careers' );
assert_same( true, false === strpos( $jobs_clean, 'payment_details' ), 'Jobs removes the demo refund form' );
$classifieds_clean = bds_dqa_strip_public_refund_modal( $demo_modal, '/all-listings/', 'classifieds' );
assert_same( true, false === strpos( $classifieds_clean, 'dcl-claim-modal' ), 'Classifieds removes the demo refund modal' );
assert_same( $demo_modal, bds_dqa_strip_public_refund_modal( $demo_modal, '/dashboard/', 'events' ), 'account and workflow pages are untouched' );
assert_same( $demo_modal, bds_dqa_strip_public_refund_modal( $demo_modal, '/all-listings/', 'businesses' ), 'ordinary business browse remains untouched' );
$gift_src = file_get_contents( 'C:/Users/iamtd/Documents/Codex/branddad-seoestore/deploy/bds-gift-slider-slots-mu.php' );
assert_same( true, false !== strpos( $gift_src, 'if ( ! bds_gss_is_marketplace()' ), 'the gift slider renders only on its marketplace' );
assert_same( true, false !== strpos( $gift_src, 'Gift inventory never renders on the Directory homepage.' ), 'the gift slider documents the homepage exclusion' );

echo "directory contact ok\n";
