<?php
/**
 * Plugin Name: BrandDad AI Launch Configuration
 * Description: Enables the approved Social conversion pages without enabling paid AI entitlements.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Public sales copy and the free-audit path are ready. Checkout remains governed
// independently by each catalog launch state and WooCommerce product status.
if ( ! defined( 'BDS_AI_LAUNCH_PAGES_ENABLED' ) ) {
	define( 'BDS_AI_LAUNCH_PAGES_ENABLED', true );
}

