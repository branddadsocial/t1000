<?php
/**
 * Plugin Name: BrandDad Frontend Polish Early Loader
 * Description: Registers the reviewed frontend and homepage compatibility fixes before legacy cache short-circuits can run.
 * Version: 1.0.0
 * Author: BrandDad
 */

defined( 'ABSPATH' ) || exit;

$bds_frontend_polish_files = array(
	__DIR__ . '/zzz-bds-frontend-polish-mu.php',
	__DIR__ . '/zzz-bds-directory-home-compat-mu.php',
);

foreach ( $bds_frontend_polish_files as $bds_frontend_polish_file ) {
	if ( is_file( $bds_frontend_polish_file ) && is_readable( $bds_frontend_polish_file ) ) {
		require_once $bds_frontend_polish_file;
	}
}

unset( $bds_frontend_polish_file, $bds_frontend_polish_files );
