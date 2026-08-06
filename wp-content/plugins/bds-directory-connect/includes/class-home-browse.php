<?php
/**
 * Server-rendered home browse section (Popular In + chips).
 *
 * Replaces the hardcoded v1.1.1 markup when present, or injects a fresh section.
 *
 * @package BDS_Directory_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BDS_DC_Home_Browse {

	/**
	 * Hooks.
	 */
	public static function init() {
		// Output a canonical section early; JS relocates it under the hero search.
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 5 );
	}

	/**
	 * Print updated browse markup (JS swaps over the old #bds-home-browse if present).
	 */
	public static function render() {
		if ( is_admin() ) {
			return;
		}
		// Home / front page only.
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}

		$data = BDS_DC_Browse_Data::get();
		echo self::markup( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build HTML for the browse section.
	 *
	 * @param array $data Browse payload.
	 * @return string
	 */
	public static function markup( array $data ) {
		ob_start();
		?>
<section id="bds-home-browse" class="bds-hbrowse" data-bds-home-browse="<?php echo esc_attr( BDS_DC_VERSION ); ?>" data-bds-dc="1" aria-label="Browse Directory">
	<div class="bds-hbrowse__inner">
		<div class="bds-hbrowse__block bds-hbrowse__block--local" data-block="local">
			<header class="bds-hbrowse__head">
				<div>
					<span class="bds-hbrowse__eyebrow">Local businesses</span>
					<h2 class="bds-hbrowse__title">Local near you</h2>
					<p class="bds-hbrowse__support">Pick a place, then a category — coffee, food, nightlife, hotels, spa, and gyms with real listings.</p>
				</div>
			</header>
			<div class="bds-hbrowse__locs" role="group" aria-label="Popular places">
				<span class="bds-hbrowse__locs-label">Popular in</span>
				<div class="bds-hbrowse__loc-row">
					<button type="button" class="bds-hbrowse__loc is-active" data-loc="" aria-pressed="true">All places</button>
					<?php foreach ( (array) $data['locations'] as $loc ) : ?>
						<a class="bds-hbrowse__loc bds-hbrowse__loc--link"
							href="<?php echo esc_url( $loc['url'] ); ?>"
							data-loc="<?php echo esc_attr( $loc['slug'] ); ?>"
							data-label="<?php echo esc_attr( $loc['label'] ); ?>">
							<span><?php echo esc_html( $loc['label'] ); ?></span>
							<span class="bds-hbrowse__loc-count"><?php echo esc_html( (string) (int) $loc['count'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<p class="bds-hbrowse__loc-hint" data-loc-hint>Prefer near-me or a zip? Use Ask BrandDad in the search bar above.</p>
			</div>
			<ul class="bds-hbrowse__chips" data-kind="local">
				<?php foreach ( (array) $data['local'] as $chip ) : ?>
					<li>
						<a class="bds-hbrowse__chip"
							href="<?php echo esc_url( $chip['url'] ); ?>"
							data-slug="<?php echo esc_attr( $chip['slug'] ); ?>"
							data-base-url="<?php echo esc_url( $chip['url'] ); ?>"
							data-combo="<?php echo esc_attr( wp_json_encode( isset( $chip['combo'] ) ? $chip['combo'] : array() ) ); ?>">
							<span class="bds-hbrowse__chip-label"><?php echo esc_html( $chip['label'] ); ?></span>
							<span class="bds-hbrowse__chip-count"><?php echo esc_html( (string) (int) $chip['count'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="bds-hbrowse__block bds-hbrowse__block--digital" data-block="digital">
			<header class="bds-hbrowse__head">
				<div>
					<span class="bds-hbrowse__eyebrow">Anywhere online</span>
					<h2 class="bds-hbrowse__title">Digital services</h2>
					<p class="bds-hbrowse__support">Browse by craft — design, web, social, SEO, hosting — no city required.</p>
				</div>
			</header>
			<ul class="bds-hbrowse__chips" data-kind="digital">
				<?php foreach ( (array) $data['digital'] as $chip ) : ?>
					<li>
						<a class="bds-hbrowse__chip"
							href="<?php echo esc_url( $chip['url'] ); ?>"
							data-slug="<?php echo esc_attr( $chip['slug'] ); ?>">
							<span class="bds-hbrowse__chip-label"><?php echo esc_html( $chip['label'] ); ?></span>
							<span class="bds-hbrowse__chip-count"><?php echo esc_html( (string) (int) $chip['count'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<footer class="bds-hbrowse__foot">
			<a class="bds-hbrowse__foot-link" href="<?php echo esc_url( $data['allListings'] ); ?>">All listings</a>
			<span class="bds-hbrowse__foot-sep" aria-hidden="true">·</span>
			<a class="bds-hbrowse__foot-link" href="<?php echo esc_url( home_url( '/engagement/' ) ); ?>">Engagement group</a>
			<span class="bds-hbrowse__foot-sep" aria-hidden="true">·</span>
			<button type="button" class="bds-hbrowse__foot-link bds-hbrowse__ask" data-bds-ask>Ask BrandDad</button>
		</footer>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}
}
