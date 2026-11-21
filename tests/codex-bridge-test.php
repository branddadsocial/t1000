<?php
require dirname( __DIR__ ) . '/deploy/cursor/bds-codex-bridge-lib.php';

$failed = 0;
function bridge_expect( $cond, $message ) {
	global $failed;
	if ( ! $cond ) {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

bridge_expect( false === bds_codex_consent_accepted( '' ), 'empty consent is off' );
bridge_expect( false === bds_codex_consent_accepted( '0' ), 'zero consent is off' );
bridge_expect( true === bds_codex_consent_accepted( '1' ), '1 is accepted' );
bridge_expect( true === bds_codex_consent_accepted( 'yes' ), 'yes is accepted' );

$geo = bds_codex_listing_geo_from_meta(
	array(
		'country'    => 'mx',
		'manual_lat' => '20.6296',
		'manual_lng' => '-87.0739',
		'billing_country' => 'US',
	)
);
bridge_expect( 'MX' === $geo['country'], 'ISO country is uppercased from listing meta' );
bridge_expect( abs( $geo['lat'] - 20.6296 ) < 0.0001, 'listing latitude is used' );
bridge_expect( abs( $geo['lng'] + 87.0739 ) < 0.0001, 'listing longitude is used' );

$empty = bds_codex_listing_geo_from_meta( array( 'address' => 'Playa del Carmen, Mexico' ) );
bridge_expect( '' === $empty['country'], 'address prose is not inferred as a country' );

$bad = bds_codex_listing_geo_from_meta( array( 'manual_lat' => '999', 'country' => 'MEX' ) );
bridge_expect( null === $bad['lat'], 'out of range latitude is dropped' );
bridge_expect( '' === $bad['country'], 'non ISO country is dropped' );

bridge_expect(
	'https://directory.branddad.social/product/featured-local/?listing=44' === bds_codex_featured_url_with_listing( 'https://directory.branddad.social/product/featured-local/', 44 ),
	'listing query is appended'
);
bridge_expect(
	'https://x.example/p?a=1&listing=9' === bds_codex_featured_url_with_listing( 'https://x.example/p?a=1', 9 ),
	'listing query joins existing query'
);
bridge_expect(
	'https://x.example/p' === bds_codex_featured_url_with_listing( 'https://x.example/p', 0 ),
	'zero listing stays off the URL'
);

bridge_expect( false === bds_codex_email_verified_from_meta( array( 'user_email' => 'a@b.c' ) ), 'email string is not proof' );
bridge_expect( true === bds_codex_email_verified_from_meta( array( '_bds_email_verified' => 'yes' ) ), 'BrandDad verified meta is proof' );
bridge_expect( false === bds_codex_email_verified_from_meta( array( 'email_verified' => 'yes' ) ), 'generic email_verified key is not trusted' );

$lead = bds_codex_lead_payload_ok(
	array(
		'email'         => 'owner@example.com',
		'privacy_ack'   => true,
		'business_type' => 'cafe',
		'primary_need'  => 'customers',
		'source'        => 'help',
	)
);
bridge_expect( true === $lead['ok'], 'valid lead payload is accepted' );
$no_privacy = bds_codex_lead_payload_ok( array( 'email' => 'owner@example.com', 'source' => 'help' ) );
bridge_expect( 'privacy' === $no_privacy['error'], 'privacy ack is required' );

bridge_expect( true === bds_codex_form_reference_ok( 'abcdef0123456789abcd' ), 'opaque form reference length is accepted' );
bridge_expect( false === bds_codex_form_reference_ok( 'short' ), 'short form reference is refused' );
bridge_expect( false === bds_codex_form_reference_ok( 'abcdef0123456789/<script>' ), 'unsafe form reference is refused' );

$skus = bds_codex_featured_skus();
bridge_expect( '9.99' === $skus['bds-feat-local']['monthly'], 'local featured price is 9.99' );
bridge_expect( '99.99' === $skus['bds-feat-global']['monthly'], 'global featured price is 99.99' );

$bridge_mu = file_get_contents( dirname( __DIR__ ) . '/deploy/cursor/bds-codex-bridge-mu.php' );
bridge_expect( false !== strpos( $bridge_mu, 'Version: 0.1.3' ), 'bridge MU version is 0.1.3' );
bridge_expect( false !== strpos( $bridge_mu, 'j.access' ), 'hub status reads access.mode from the live account API' );
bridge_expect( false !== strpos( $bridge_mu, 'Directory membership is not included' ), 'trial hub copy does not grant Directory' );
bridge_expect( false !== strpos( $bridge_mu, 'bds-billing-inspect' ), 'shop-manager billing inspect is registered' );
bridge_expect( false === strpos( $bridge_mu, 'echo BDS_FORMWISE' ), 'inspect page does not echo FormWise constants' );

$feat_prompt = bds_codex_featured_listing_prompt( 0 );
bridge_expect( false === $feat_prompt['attached'], 'zero listing is not attached' );
bridge_expect( false !== strpos( $feat_prompt['line'], 'stays inactive' ), 'missing listing does not imply a live lane' );
$feat_ok = bds_codex_featured_listing_prompt( 17607 );
bridge_expect( true === $feat_ok['attached'] && false !== strpos( $feat_ok['line'], '17607' ), 'digit listing id is named in copy' );
bridge_expect( false === strpos( json_encode( $feat_ok ), 'pk_' ), 'listing prompt does not print a Stripe key' );

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed\n" );
	exit( 1 );
}
echo "codex bridge tests passed\n";
