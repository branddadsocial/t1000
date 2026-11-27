<?php
$source = file_get_contents( dirname( __DIR__ ) . '/deploy/zzzzzzz-bds-submission-homecard-hotfix-mu.php' );
$checks = array(
	'version is pinned' => false !== strpos( $source, 'Version: 0.1.0' ),
	'exact Social host is required' => false !== strpos( $source, "'branddad.social' !== \$host" ),
	'homepage is required' => false !== strpos( $source, '! is_front_page()' ),
	'existing live product link prevents duplicates' => false !== strpos( $source, "grid.querySelector('a[href*=\"/product/ai-press-release-pack/\"]')" ),
	'live press-release path is used' => false !== strpos( $source, "link.href='/product/ai-press-release-pack/" ),
	'trusted DOM nodes avoid HTML injection sinks' => false === strpos( $source, 'innerHTML' ),
	'customer-facing price remains 39.99 one time' => false !== strpos( $source, "amount.textContent='$39.99'" ) && false !== strpos( $source, "period.textContent=' one time'" ),
);
foreach ( $checks as $label => $ok ) {
	echo ( $ok ? 'PASS: ' : 'FAIL: ' ) . $label . PHP_EOL;
	if ( ! $ok ) {
		exit( 1 );
	}
}
echo 'Submission homepage-card hotfix tests passed.' . PHP_EOL;
