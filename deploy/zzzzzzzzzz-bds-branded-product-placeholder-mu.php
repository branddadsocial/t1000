<?php
/**
 * Plugin Name: BrandDad Branded Product Placeholder
 * Description: Replaces WooCommerce's generic missing-product image with BrandDad Social branding.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'BDS_BRANDED_PRODUCT_PLACEHOLDER_VERSION' ) ) {
	return;
}

define( 'BDS_BRANDED_PRODUCT_PLACEHOLDER_VERSION', '1.0.0' );

/**
 * @return bool
 */
function bds_branded_product_placeholder_is_social(): bool {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) $_SERVER['HTTP_HOST'] ) ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	return in_array( $host, array( 'branddad.social', 'www.branddad.social' ), true );
}

/**
 * Use an existing BrandDad-owned wordmark for products without assigned artwork.
 * Products with real thumbnails never enter this WooCommerce placeholder filter.
 *
 * @param string $src Existing WooCommerce placeholder URL.
 * @return string
 */
function bds_branded_product_placeholder_src( $src ): string {
	if ( ! bds_branded_product_placeholder_is_social() ) {
		return (string) $src;
	}

	return 'https://branddad.social/wp-content/uploads/2023/04/3-1-1.webp';
}
add_filter( 'woocommerce_placeholder_img_src', 'bds_branded_product_placeholder_src', 999 );

/**
 * Keep the fallback accessible and visually distinct from assigned product art.
 *
 * @param string       $html Existing placeholder markup.
 * @param string|array $size Requested image size.
 * @return string
 */
function bds_branded_product_placeholder_html( $html, $size ): string {
	if ( ! bds_branded_product_placeholder_is_social() ) {
		return (string) $html;
	}

	$src = bds_branded_product_placeholder_src( '' );

	return sprintf(
		'<img src="%1$s" alt="%2$s" class="woocommerce-placeholder wp-post-image bds-product-placeholder" width="600" height="450" loading="lazy" decoding="async" />',
		esc_url( $src ),
		esc_attr__( 'BrandDad Social service', 'branddad' )
	);
}
add_filter( 'woocommerce_placeholder_img', 'bds_branded_product_placeholder_html', 999, 2 );

/**
 * Keep the branded fallback compact on product and catalog cards.
 */
function bds_branded_product_placeholder_styles(): void {
	if ( ! bds_branded_product_placeholder_is_social() ) {
		return;
	}
	?>
	<style id="bds-branded-product-placeholder-css" data-bds-branded-product-placeholder="1.0.0">
	.bds-product-placeholder{box-sizing:border-box;display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:contain;padding:clamp(32px,8vw,76px);border:1px solid #dbe5ef;border-radius:18px;background:linear-gradient(145deg,#fff 0%,#edf6ff 100%)}
	.products .bds-product-placeholder{aspect-ratio:1;padding:clamp(24px,5vw,52px);border-radius:14px}
	@media(max-width:720px){.single-product .bds-product-placeholder{max-height:260px;padding:38px 30px}}
	</style>
	<?php
}
add_action( 'wp_head', 'bds_branded_product_placeholder_styles', 999 );

