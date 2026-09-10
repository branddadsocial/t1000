<?php
/**
 * Publish one authority blog post for the current BrandDad site.
 * Upload to WP root and hit ?k=BDS_SITE_BLOG_2026
 * Hosts: branddad.social | branddad.co | directory.branddad.social
 */
if ( ! isset( $_GET['k'] ) || 'BDS_SITE_BLOG_2026' !== (string) $_GET['k'] ) {
	http_response_code( 403 );
	header( 'Content-Type: application/json' );
	echo '{"ok":false,"error":"forbidden"}';
	exit;
}

require_once dirname( __FILE__ ) . '/wp-load.php';
header( 'Content-Type: application/json; charset=utf-8' );
nocache_headers();
@set_time_limit( 600 );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
$host = preg_replace( '/^www\./', '', $host );

$catalog = array(
	'branddad.social'           => array(
		'id'               => 'social-visibility-trust-2026-08',
		'title'            => 'Visibility Without Trust Is Just Noise',
		'slug'             => 'visibility-without-trust-is-just-noise',
		'keyword'          => 'professional social media growth',
		'meta_title'       => 'Visibility Without Trust Is Just Noise | BrandDad Social',
		'meta_description' => 'Learn why attention without credibility fails, and how professional social growth builds trust that converts.',
		'category'         => 'Growth',
		'tags'             => array( 'social growth', 'marketing', 'brand trust', 'LinkedIn' ),
		'scene'            => 'A confident founder reviewing a clean professional marketing workspace at dusk, warm amber desk lamp and deep teal shadows, subtle sense of digital reach without screens full of text, premium magazine realism.',
		'content'          => <<<'HTML'
<p>Most businesses do not fail because they are invisible. They fail because they become visible in the wrong way.</p>
<p>A spike of likes. A burst of followers. A campaign that looks busy on a dashboard. Then silence — because the people who saw you never trusted you enough to act.</p>
<p>That gap between attention and trust is where real marketing lives. It is also where BrandDad Social focuses: professional visibility that still looks credible when a serious buyer checks twice.</p>

<h2>Attention is cheap. Credibility is not.</h2>
<p>Anyone can manufacture motion: more impressions, more profile visits, more “engagement.” Fewer teams can manufacture belief — the feeling that your offer is legitimate and worth paying for.</p>
<p>Before you spend on growth, ask:</p>
<ol>
<li>Will this help the right people find us?</li>
<li>Will what they find look coherent and professional?</li>
<li>Is there a clear next step once they arrive?</li>
<li>If something goes wrong, is there a real path to resolve it?</li>
</ol>
<p>Skip those questions and you are buying activity, not marketing.</p>

<h2>The three layers of professional growth</h2>
<h3>1) Presence people can verify</h3>
<p>Prospects check your site, profiles, reviews, and listings. If one channel looks premium and another looks abandoned, buyers fill in the gap — usually negatively. Clean profiles and coherent offers are the proof layer behind every campaign.</p>
<h3>2) Distribution that matches your buyer</h3>
<p>LinkedIn is not Instagram. Local discovery is its own channel. Professional growth means matching channel to intent instead of spraying “go viral” everywhere.</p>
<h3>3) Protection when money moves</h3>
<p>Buyers remember bad checkouts longer than clever creatives. Clear packages, identifiable sellers, and real support paths convert more of the traffic you already paid for.</p>

<h2>Why shortcut growth fails serious buyers</h2>
<p>Promise fast results, deliver vanity metrics, leave the brand looking less trustworthy than before. Serious buyers can smell it. Professional growth should feel boring in the best way: clear packages, honest timelines, and outcomes tied to leads, booked calls, or authority.</p>

<h2>What BrandDad Social is built for</h2>
<p>We help brands build visibility that still holds up under scrutiny — social growth, SEO, and marketing services designed for real operators, not disposable vanity campaigns.</p>
<p><a href="https://branddad.social/services/"><strong>Explore BrandDad Social services →</strong></a></p>
HTML
	),
	'branddad.co'               => array(
		'id'               => 'co-website-logo-first-impression-2026-08',
		'title'            => 'Your Website Is the Handshake After Discovery',
		'slug'             => 'website-is-the-handshake-after-discovery',
		'keyword'          => 'professional website and logo design',
		'meta_title'       => 'Your Website Is the Handshake After Discovery | BrandDad.co',
		'meta_description' => 'Discovery gets the click. Your logo and website earn the trust. Build a destination that converts attention into business.',
		'category'         => 'Web Design',
		'tags'             => array( 'website design', 'logo', 'branding', 'conversion' ),
		'scene'            => 'A refined modern laptop on a walnut desk showing an unreadable but elegant website layout beside a printed brand folder, soft daylight, deep navy and warm wood tones, premium design-studio atmosphere, no readable text.',
		'content'          => <<<'HTML'
<p>Discovery can send people your way. Your website is where they decide if you look real.</p>
<p>A strong logo and a clear site are not decoration. They are the handshake after the first impression — the moment a visitor decides whether to inquire, book, or bounce.</p>

<h2>What a converting site must make obvious</h2>
<ul>
<li>Who you help</li>
<li>What you offer</li>
<li>Why you are credible</li>
<li>What to do next</li>
</ul>
<p>If those four answers are buried under fluff, paid traffic and referrals waste money.</p>

<h2>Logo + website should match the business you sell</h2>
<p>Inconsistent branding is a quiet trust killer. Your Directory listing, social profiles, and site should feel like one company. BrandDad.co focuses on practical design: logos and websites that look intentional on mobile and desktop without agency theater.</p>

<h2>Design for the buyer who checks twice</h2>
<p>Serious buyers open your site on a phone, scan in seconds, and look for proof. Fast load, clean hierarchy, and clear contact paths beat animated gimmicks. Pair a sharp logo with pages that make the offer impossible to misunderstand.</p>

<h2>Build the destination behind your marketing</h2>
<p>Whether leads come from BrandDad Social campaigns, Directory discovery, or word of mouth, they still land somewhere. Make that somewhere worth trusting.</p>
<p><a href="https://branddad.co/get-started/"><strong>Start with BrandDad.co design help →</strong></a></p>
HTML
	),
	'directory.branddad.social' => array(
		'id'               => 'dir-findable-credible-bookable-2026-08',
		'title'            => 'Local Businesses Need Digital Systems, Not Digital Costumes',
		'slug'             => 'local-businesses-need-digital-systems-not-costumes',
		'keyword'          => 'business directory listing for local growth',
		'meta_title'       => 'Local Businesses Need Digital Systems, Not Costumes | BrandDad Directory',
		'meta_description' => 'Be findable, look credible, and make the next step obvious. How BrandDad Directory helps local and digital businesses get discovered.',
		'category'         => 'Local Discovery',
		'tags'             => array( 'business directory', 'local SEO', 'claim listing', 'discovery' ),
		'scene'            => 'A lively independent neighborhood storefront at golden hour with a welcoming open door and people approaching, authentic local-business energy, deep emerald and warm amber light, no signs with readable words.',
		'content'          => <<<'HTML'
<p>A restaurant, clinic, contractor, or studio does not need a 40-slide agency deck. They need to be findable in the right category and place, look legitimate when someone clicks through, and offer a simple path to contact or buy.</p>
<p>That is what BrandDad Directory is for: discovery with a real next step.</p>

<h2>Findable</h2>
<p>Customers search by occasion and place — “near me,” city + category, or plain questions. A complete listing with accurate location, category, and description helps people and search systems connect you with the right intent.</p>
<p><a href="https://directory.branddad.social/">Browse BrandDad Directory</a> or use Ask BrandDad when the need is messy and human.</p>

<h2>Credible</h2>
<p>Prospects check more than one place. Your listing, website, and reviews should tell the same story. Claim your profile so hours, photos, and offers stay current.</p>
<p><a href="https://directory.branddad.social/"><strong>Find and claim your business listing →</strong></a></p>

<h2>Bookable</h2>
<p>Discovery without conversion is a dead end. Make the next action obvious: visit, message (when signed in), book, or buy. Gift cards and digital services on the Directory follow the same idea — clear products, clear sellers, clear checkout rules.</p>

<h2>Grow what already exists</h2>
<p>Optional growth services should amplify a real presence, not replace it with fake buzz. When you are ready to strengthen SEO or social after the listing is solid, explore <a href="https://branddad.social/services/">BrandDad Social</a>. For logo and website polish, see <a href="https://branddad.co/get-started/">BrandDad.co</a>.</p>
<p><a href="https://directory.branddad.social/"><strong>Explore BrandDad Directory →</strong></a></p>
HTML
	),
);

if ( ! isset( $catalog[ $host ] ) ) {
	http_response_code( 409 );
	echo wp_json_encode( array( 'ok' => false, 'error' => 'unsupported_host', 'host' => $host ) );
	exit;
}

$row = $catalog[ $host ];

function bds_site_blog_openai_key() {
	if ( class_exists( 'BrandDad_Autoblog', false ) && is_callable( array( 'BrandDad_Autoblog', 'openai_key' ) ) ) {
		return (string) BrandDad_Autoblog::openai_key();
	}
	foreach ( array( 'bdco_openai_api_key', 'bds_openai_api_key', 'bdco_ai_openai_api_key' ) as $option ) {
		$value = get_option( $option, '' );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}
	foreach ( array( 'BDS_OPENAI_API_KEY', 'BDCO_OPENAI_API_KEY', 'OPENAI_API_KEY' ) as $constant ) {
		if ( defined( $constant ) && constant( $constant ) ) {
			return (string) constant( $constant );
		}
	}
	return '';
}

function bds_site_blog_generate_image( $post_id, $title, $scene ) {
	$key = bds_site_blog_openai_key();
	if ( '' === $key ) {
		return new WP_Error( 'missing_key', 'OpenAI key unavailable' );
	}
	$settings = get_option( 'bds_ab_settings', array() );
	$model    = is_array( $settings ) && ! empty( $settings['image_model'] ) ? (string) $settings['image_model'] : 'gpt-image-1';
	$base     = is_array( $settings ) && ! empty( $settings['openai_base_url'] ) ? rtrim( (string) $settings['openai_base_url'], '/' ) : 'https://api.openai.com/v1';
	$prompt   = 'Editorial photograph for a premium BrandDad blog. 4:3 friendly landscape. '
		. $scene . ' '
		. 'No text, letters, logos, watermarks, UI, charts. Photoreal, cinematic, professional.';
	$body     = array(
		'model' => $model,
		'prompt'=> $prompt,
		'n'     => 1,
		'size'  => '1536x1024',
	);
	$response = wp_remote_post(
		$base . '/images/generations',
		array(
			'timeout' => 180,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code || empty( $data['data'][0] ) ) {
		return new WP_Error( 'openai_image', 'Image generation failed (' . $code . ')' );
	}
	$item = $data['data'][0];
	$bytes = '';
	if ( ! empty( $item['b64_json'] ) ) {
		$bytes = base64_decode( (string) $item['b64_json'] );
	} elseif ( ! empty( $item['url'] ) ) {
		$img = wp_remote_get( (string) $item['url'], array( 'timeout' => 120 ) );
		if ( ! is_wp_error( $img ) ) {
			$bytes = (string) wp_remote_retrieve_body( $img );
		}
	}
	if ( ! $bytes ) {
		return new WP_Error( 'openai_image_empty', 'Empty image bytes' );
	}
	$tmp = wp_tempnam( 'bds-site-blog.png' );
	file_put_contents( $tmp, $bytes );
	$file_array = array(
		'name'     => 'bds-site-blog-' . $post_id . '-' . time() . '.png',
		'tmp_name' => $tmp,
	);
	$att_id = media_handle_sideload( $file_array, $post_id, $title );
	if ( is_wp_error( $att_id ) ) {
		@unlink( $tmp );
		return $att_id;
	}
	set_post_thumbnail( $post_id, (int) $att_id );
	update_post_meta( $post_id, '_bds_ab_image_source', 'openai' );
	return (int) $att_id;
}

$existing = get_page_by_path( $row['slug'], OBJECT, 'post' );
if ( $existing ) {
	$post_id = (int) $existing->ID;
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_title'   => $row['title'],
			'post_content' => $row['content'],
			'post_excerpt' => $row['meta_description'],
			'post_status'  => 'publish',
		)
	);
	$state = 'updated';
} else {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $row['title'],
			'post_name'    => $row['slug'],
			'post_content' => $row['content'],
			'post_excerpt' => $row['meta_description'],
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		echo wp_json_encode( array( 'ok' => false, 'error' => $post_id->get_error_message() ) );
		exit;
	}
	$post_id = (int) $post_id;
	$state   = 'created';
}

$topic = array(
	'id'               => $row['id'],
	'title'            => $row['title'],
	'primary_keyword'  => $row['keyword'],
	'meta_title'       => $row['meta_title'],
	'meta_description' => $row['meta_description'],
	'category'         => $row['category'],
	'tags'             => $row['tags'],
);
if ( class_exists( 'BDS_AB_SEO', false ) ) {
	BDS_AB_SEO::apply( $post_id, $topic );
} else {
	update_post_meta( $post_id, '_yoast_wpseo_title', $row['meta_title'] );
	update_post_meta( $post_id, '_yoast_wpseo_metadesc', $row['meta_description'] );
	update_post_meta( $post_id, 'rank_math_title', $row['meta_title'] );
	update_post_meta( $post_id, 'rank_math_description', $row['meta_description'] );
	$cat = term_exists( $row['category'], 'category' );
	if ( ! $cat ) {
		$cat = wp_insert_term( $row['category'], 'category' );
	}
	if ( ! is_wp_error( $cat ) ) {
		wp_set_post_categories( $post_id, array( (int) ( is_array( $cat ) ? $cat['term_id'] : $cat ) ) );
	}
	wp_set_post_tags( $post_id, $row['tags'], false );
}

$thumb_id    = (int) get_post_thumbnail_id( $post_id );
$image_error = '';
if ( ! $thumb_id ) {
	$image = bds_site_blog_generate_image( $post_id, $row['title'], $row['scene'] );
	if ( is_wp_error( $image ) ) {
		$image_error = $image->get_error_message();
	} else {
		$thumb_id = (int) $image;
	}
}

$out = array(
	'ok'           => ( 'publish' === get_post_status( $post_id ) ),
	'host'         => $host,
	'state'        => $state,
	'post_id'      => $post_id,
	'title'        => get_the_title( $post_id ),
	'url'          => get_permalink( $post_id ),
	'featured_id'  => $thumb_id,
	'featured_url' => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '',
	'image_error'  => $image_error,
);
if ( $out['ok'] && is_file( __FILE__ ) ) {
	@unlink( __FILE__ );
	$out['self_deleted'] = ! file_exists( __FILE__ );
}
echo wp_json_encode( $out );
exit;
