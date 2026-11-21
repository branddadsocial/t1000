<?php
/**
 * Follow-up consent rules. No WordPress.
 */
define( 'ABSPATH', __DIR__ );
function add_action() {}
require_once __DIR__ . '/../deploy/cursor/bds-follow-consent-mu.php';

function assert_true( $cond, $message ) {
	if ( ! $cond ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

$ok = bds_follow_decision( 'Owner@Example.com', '1', 'listing' );
assert_true( ! empty( $ok['ok'] ) && 'owner@example.com' === $ok['email'] && 'listing' === $ok['source'], 'a checked box and a real email is saved as interested' );
assert_true( 'interested' === $ok['status'], 'new consent starts as interested' );
$no = bds_follow_decision( 'owner@example.com', '', 'help' );
assert_true( empty( $no['ok'] ) && 'consent' === $no['error'], 'no box means no save' );
$bad = bds_follow_decision( 'not-an-email', '1', 'help' );
assert_true( empty( $bad['ok'] ) && 'email' === $bad['error'], 'a broken email is refused' );
$src = bds_follow_decision( 'a@b.co', '1', 'drop-table' );
assert_true( 'help' === $src['source'], 'an unknown source becomes help' );
$trial = bds_follow_message( 'trial' );
assert_true( false !== strpos( $trial['body'], '$29.99' ) && false !== strpos( $trial['body'], '7-day' ), 'the trial note names the real price and trial' );
assert_true( false !== strpos( $trial['body'], 'Directory membership is not included' ), 'the trial note does not grant Directory membership' );
assert_true( false === strpos( strtolower( $trial['body'] ), 'no card' ), 'the trial note does not say no card' );
$listing = bds_follow_message( 'listing' );
assert_true( false !== strpos( $listing['body'], 'free' ), 'the listing note says the listing is free' );
assert_true( 'help' === bds_follow_source_for_path( '/contact-us/' ), 'a normal page stays on the help note' );
assert_true( 'trial' === bds_follow_source_for_path( '/product/ai-business-essentials/' ), 'the essentials page uses the trial note' );
assert_true( 'listing' === bds_follow_source_for_path( '/add-listing/' ), 'add-listing uses the listing note' );
assert_true( 'featured' === bds_follow_source_for_path( '/featured-advertising/' ), 'Featured uses the Featured note' );
assert_true( 'music' === bds_follow_source_for_path( '/#branddad-music' ), 'music uses the brief note' );
assert_true( 'gift' === bds_follow_source_for_path( '/gift-card-marketplace/' ), 'gift cards use the marketplace note' );
$feat_note = bds_follow_message( 'featured' );
assert_true( false !== strpos( $feat_note['body'], '$9.99' ), 'the Featured note names the Local price' );
assert_true( false === strpos( strtolower( $feat_note['body'] ), 'no card' ), 'the Featured note does not say no card' );
$music_note = bds_follow_message( 'music' );
assert_true( false !== strpos( $music_note['body'], 'does not take payment' ), 'the music note does not take payment' );
$src = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-follow-consent-mu.php' );
assert_true( false !== strpos( $src, 'Version: 0.1.4' ), 'follow consent stamps 0.1.4' );
assert_true( false !== strpos( $src, 'Guardar mi correo' ), 'follow submit Spanish is tagged' );
assert_true( false !== strpos( $src, '<span' ) && false !== strpos( $src, 'BrandDad puede escribirme' ), 'checkbox label Spanish is an inner span' );

echo "follow-consent ok\n";
