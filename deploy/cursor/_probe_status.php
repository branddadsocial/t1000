<?php
/**
 * Classify leftover BrandDad URLs with curl.exe (Windows PHP SSL is unreliable).
 * Allowlisted hosts only. No cookies printed. No charge.
 */
$allowed = array( 'branddad.social', 'www.branddad.social', 'directory.branddad.social' );
$urls    = array_slice( $argv, 1 );
if ( ! $urls ) {
	$urls = array(
		'https://branddad.social/faq/',
		'https://branddad.social/faqs/',
		'https://branddad.social/work-with-us/',
		'https://directory.branddad.social/membership/',
		'https://directory.branddad.social/memberships/',
		'https://directory.branddad.social/sell-gift-card/',
		'https://directory.branddad.social/sell-gift-cards/',
		'https://directory.branddad.social/sell/',
	);
}

foreach ( $urls as $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		continue;
	}
	$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );
	if ( ! in_array( $host, $allowed, true ) ) {
		echo "SKIP host=$host $url\n";
		continue;
	}
	$cmd = 'curl.exe -sS -D - -o NUL --max-redirs 0 --max-time 8 -A '
		. escapeshellarg( 'BrandDadCursorInspect/1' )
		. ' '
		. escapeshellarg( $url );
	$lines = array();
	$code  = 1;
	exec( $cmd, $lines, $code );
	if ( ! $lines ) {
		echo "ABORT exit=$code $url\n";
		continue;
	}
	$status = '';
	$loc    = '';
	foreach ( $lines as $line ) {
		$line = trim( (string) $line );
		if ( '' === $status && 0 === stripos( $line, 'HTTP/' ) ) {
			$status = $line;
		}
		if ( 0 === stripos( $line, 'Location:' ) ) {
			$loc = trim( substr( $line, 9 ) );
		}
	}
	if ( '' === $status ) {
		echo "ABORT exit=$code $url\n";
		continue;
	}
	echo "RESP $status loc=$loc $url\n";
}
