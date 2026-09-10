<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds readable, SEO/AEO-ready HTML from topic outlines (default)
 * or optional OpenAI-compatible API.
 *
 * Quality bar: ~800–1500 words equivalent, clear H2/H3 structure,
 * practical takeaways, natural keywords, soft product + network CTAs, FAQ.
 * Sell-oriented but trustworthy; first-timer readable; link Woo + BrandDad network when topic fits.
 */
class BDS_AB_Generator {
	public static function build( array $topic ) {
		$settings = BrandDad_Autoblog::settings();
		$html     = '';
		$key      = BrandDad_Autoblog::openai_key();

		if ( ! empty( $settings['prefer_ai'] ) && $key ) {
			$settings['openai_api_key'] = $key;
			$ai = self::from_ai( $topic, $settings );
			if ( ! is_wp_error( $ai ) && is_string( $ai ) && strlen( wp_strip_all_tags( $ai ) ) > 800 ) {
				$html = $ai;
			}
		}

		if ( '' === $html ) {
			$html = self::from_outline( $topic );
		}

		if ( ! empty( $settings['auto_internal_links'] ) ) {
			$html = self::append_related( $html, $topic );
		}

		// Always end with a site-aware product CTA (AI path used to skip this).
		$html = self::ensure_product_cta( $html, $topic );

		return $html;
	}

	/**
	 * Append product CTA block when missing (idempotent).
	 */
	public static function ensure_product_cta( $html, array $topic = array() ) {
		$html = (string) $html;
		if ( '' === trim( $html ) ) {
			return $html;
		}
		if ( false !== strpos( $html, 'bds-ab-cta' ) || false !== strpos( $html, 'data-bds-ab-cta' ) ) {
			return $html;
		}
		$cta = BDS_AB_Products_Map::soft_cta_html( array(), $topic );
		if ( ! $cta ) {
			return $html;
		}
		return rtrim( $html ) . "\n\n" . $cta;
	}

	public static function from_outline( array $topic ) {
		$title    = isset( $topic['title'] ) ? $topic['title'] : 'Growth guide';
		$angle    = isset( $topic['angle'] ) ? $topic['angle'] : '';
		$keyword  = isset( $topic['primary_keyword'] ) ? $topic['primary_keyword'] : '';
		$secs     = isset( $topic['secondary_keywords'] ) && is_array( $topic['secondary_keywords'] ) ? $topic['secondary_keywords'] : array();
		$outline  = isset( $topic['outline'] ) && is_array( $topic['outline'] ) ? $topic['outline'] : array();
		$products = isset( $topic['product_slugs'] ) && is_array( $topic['product_slugs'] ) ? $topic['product_slugs'] : array();
		$cluster  = isset( $topic['cluster'] ) ? $topic['cluster'] : '';
		$intent   = isset( $topic['buyer_intent'] ) ? $topic['buyer_intent'] : 'consideration';

		$parts   = array();
		$parts[] = self::intro_block( $title, $angle, $keyword, $secs, $cluster );
		$parts[] = self::who_this_is_for( $cluster, $keyword, $intent );

		$i         = 0;
		$section_n = count( $outline );
		foreach ( $outline as $section ) {
			$heading = isset( $section['heading'] ) ? $section['heading'] : '';
			$points  = isset( $section['points'] ) && is_array( $section['points'] ) ? $section['points'] : array();
			if ( $heading ) {
				$parts[] = '<h2>' . esc_html( $heading ) . '</h2>';
			}
			$parts[] = '<p>' . esc_html( self::section_lead( $heading, $points, $keyword, $cluster, $i, $section_n ) ) . '</p>';

			if ( $points ) {
				foreach ( $points as $pi => $point ) {
					$parts[] = '<h3>' . esc_html( self::point_heading( $point ) ) . '</h3>';
					$parts[] = '<p>' . esc_html( self::expand_point( $point, $keyword, $cluster, $heading, $pi ) ) . ' '
						. esc_html( self::expand_point_followup( $point, $keyword, $cluster, $pi ) ) . '</p>';
				}
			}

			if ( 1 === $i ) {
				$parts[] = BDS_AB_Products_Map::soft_cta_html( $products, $topic );
			}
			if ( 2 === $i ) {
				$parts[] = self::network_fit_block( $cluster, $keyword, $intent );
			}
			$i++;
		}

		$parts[] = self::takeaways_block( $topic, $outline );
		$parts[] = '<h2>Practical next step</h2>';
		$parts[] = '<p>' . self::closing_html( $topic ) . '</p>';
		if ( BDS_AB_Site::should_mention_directory( isset( $topic['id'] ) ? $topic['id'] : '' ) ) {
			$parts[] = '<p>' . self::closing_directory_html() . '</p>';
		}
		$parts[] = BDS_AB_Products_Map::soft_cta_html( $products, $topic );
		if ( 'decision' === $intent ) {
			$parts[] = BDS_AB_Products_Map::whatsapp_soft_html();
		}
		$parts[] = self::faq_block( $topic );

		return implode( "\n", array_filter( $parts ) );
	}

	protected static function intro_block( $title, $angle, $keyword, array $secs, $cluster ) {
		$kw    = $keyword ? $keyword : 'this growth topic';
		$angle = $angle ? $angle : ( 'Here is a practical take on ' . $kw . ' that favors clarity over hype.' );
		$ent   = ! empty( $secs[0] ) ? $secs[0] : self::cluster_entity( $cluster );
		$ent2  = ! empty( $secs[1] ) ? $secs[1] : 'buyer attention';

		$brand = BDS_AB_Site::profile()['brand'];
		$p1 = $angle . ' This ' . $brand . ' guide is written for first-timers and busy operators who want usable steps — not recycled platitudes or thin AI filler.';
		$p2 = 'If you are researching “' . $kw . ',” you are usually balancing three tensions at once: limited time, noisy advice, and pressure to show progress. The sections below separate what actually moves ' . $ent . ' from habits that only look busy — including where DIY still wins and where a packaged BrandDad system earns its keep.';
		$p3 = 'You will get a clear H2/H3 structure, concrete checklists, honest timelines, and soft recommendations tied to ' . $brand . ' (and sister BrandDad sites when they fit). Related ideas like ' . $ent2 . ' show up naturally because buyers do not solve this in isolation.';

		return '<p>' . esc_html( $p1 ) . "</p>\n<p>" . esc_html( $p2 ) . "</p>\n<p>" . esc_html( $p3 ) . '</p>';
	}

	protected static function who_this_is_for( $cluster, $keyword, $intent ) {
		$audience = self::cluster_audience( $cluster );
		$kw       = $keyword ? $keyword : 'this work';
		$html     = "<h2>Who this guide is for</h2>\n";
		$html    .= '<p>' . esc_html( 'This is for ' . $audience . ' who care about ' . $kw . ' enough to run a real process — not chase screenshots. If you want guarantees of overnight #1 rankings, viral posts on demand, or “set and forget” miracles, this is not that article.' ) . '</p>';
		if ( 'decision' === $intent ) {
			$html .= '<p>' . esc_html( 'You are likely past curiosity and evaluating whether to buy help. Use the decision checkpoints below so you can compare DIY capacity against a BrandDad package without getting sold magic.' ) . '</p>';
		} else {
			$html .= '<p>' . esc_html( 'You may still be exploring options. Treat this as an operating brief: run the highest-leverage habits for two weeks, then decide whether execution capacity — not more tips — is the bottleneck.' ) . '</p>';
		}
		return $html;
	}

	/**
	 * Natural cross-site paragraph when cluster fits (SEO → site+hosting, LinkedIn → personal site, etc.).
	 */
	protected static function network_fit_block( $cluster, $keyword, $intent ) {
		$kw = $keyword ? $keyword : 'this work';

		switch ( $cluster ) {
			case 'seo':
				$html  = '<h2>Website and hosting fit (before you scale SEO)</h2>';
				$html .= '<p>SEO packages work best when the destination is clear: a credible site with target pages worth ranking. If you still need a premium AI-built website, start on '
					. BDS_AB_Products_Map::link_html( 'bdco-ai-websites', 'BrandDad.co (sites from about $199)', 'seo_website' )
					. '. When that site needs a home, '
					. BDS_AB_Products_Map::link_html( 'hosttech-hosting', 'HostTech hosting', 'seo_hosting' )
					. ' keeps the stack simple. Then pair with a focused '
					. BDS_AB_Products_Map::link_html( 'comprehensive-seo-packages-rank-1-on-google', 'SEO Growth Package', 'seo_package' )
					. ' instead of buying links into a thin placeholder.</p>';
				return $html;

			case 'linkedin':
				$html  = '<h2>Personal brand beyond the feed</h2>';
				$html .= '<p>Strong LinkedIn habits still benefit from a clean home base prospects can trust after the DM. If your offer page looks dated, a premium AI website on '
					. BDS_AB_Products_Map::link_html( 'bdco-ai-websites', 'branddad.co', 'li_website' )
					. ' pairs well with '
					. BDS_AB_Products_Map::link_html( 'linkedin-viral-posts-for-professionals', 'LinkedIn post systems', 'li_posts' )
					. ' and outreach packages on BrandDad Social. Directory members save 10% on eligible services via '
					. BDS_AB_Products_Map::link_html( 'branddad-directory', 'directory.branddad.social', 'li_dir' )
					. '.</p>';
				return $html;

			case 'pr-authority':
				$html  = '<h2>Authority assets that work together</h2>';
				$html .= '<p>Press, billboards, and credit pages convert better when the brand looks finished. Pair PR moments with a logo or logo+website bundle on '
					. BDS_AB_Products_Map::link_html( 'bdco-logo-website-bundles', 'BrandDad.co', 'pr_bundle' )
					. ', then keep discovery moving with '
					. BDS_AB_Products_Map::link_html( 'press-release-services', 'press release distribution', 'pr_press' )
					. ' when you have real news.</p>';
				return $html;

			case 'websites':
			case 'hosting':
				$html  = '<h2>Where BrandDad fits in the stack</h2>';
				$html .= '<p>BrandDad.co handles logos and premium AI websites; '
					. BDS_AB_Products_Map::link_html( 'hosttech-hosting', 'HostTech', 'web_host' )
					. ' hosts them; BrandDad Social grows the audience (SEO, LinkedIn, social). Start where the bottleneck is — design, hosting, or demand — not all three at once.</p>';
				return $html;

			case 'instagram-growth':
			case 'facebook-growth':
			case 'influencers-smm':
				$html  = '<h2>Creative identity + consistent shipping</h2>';
				$html .= '<p>Discovery systems work harder when the brand looks intentional. If you need a fast logo refresh, see '
					. BDS_AB_Products_Map::link_html( 'bdco-ai-logos', 'AI logos on BrandDad.co', 'smm_logo' )
					. '. For week-to-week posting without feast-or-famine bursts, use '
					. BDS_AB_Products_Map::link_html( 'social-media-management', 'Social Media Management', 'smm_mgmt' )
					. ' on BrandDad Social. Active Directory members get 10% off eligible services.</p>';
				return $html;

			case 'reputation':
				$html  = '<h2>Trust stack: reviews, site, and Directory</h2>';
				$html .= '<p>Review process protects trust; a clear website and consistent local presence reinforce it. Handle harmful reviews with a calm process (and specialist help when appropriate), keep the site credible via '
					. BDS_AB_Products_Map::link_html( 'bdco-ai-websites', 'BrandDad.co', 'rep_web' )
					. ', and unlock 10% off eligible BrandDad services through the '
					. BDS_AB_Products_Map::link_html( 'branddad-directory', 'BrandDad Directory', 'rep_dir' )
					. '.</p>';
				return $html;

			default:
				if ( 'decision' === $intent ) {
					return '<p>When you are ready to buy, match the package to the bottleneck in “' . esc_html( $kw ) . '” — not a cart of unused extras. Soft product links in this guide point to BrandDad Social and sister sites only when they fit.</p>';
				}
				return '';
		}
	}

	protected static function section_lead( $heading, array $points, $keyword, $cluster, $index, $total ) {
		$focus = $heading ? $heading : $keyword;
		$count = count( $points );
		$ctx   = self::cluster_context( $cluster );
		$ord   = $index + 1;

		if ( $count > 0 ) {
			return 'Section ' . $ord . ' of ' . max( 1, $total ) . ' digs into “' . $focus . '.” In practice, ' . $ctx . ' usually breaks this into ' . $count . ' moves you can assign to a person or calendar block. Skim the headings, then execute the one that removes your biggest bottleneck this week.';
		}
		return 'Under “' . $focus . ',” keep the standard simple: useful to a specific buyer, easy to act on, and honest about timelines. ' . ucfirst( $ctx ) . ' rewards clarity more than volume.';
	}

	protected static function point_heading( $point ) {
		$point = trim( (string) $point );
		if ( '' === $point ) {
			return 'Key move';
		}
		return rtrim( $point, '.' );
	}

	protected static function expand_point( $point, $keyword, $cluster, $heading, $pi ) {
		$kw   = $keyword ? $keyword : 'your primary goal';
		$ctx  = self::cluster_context( $cluster );
		$hash = abs( crc32( strtolower( $point . '|' . $kw . '|' . $heading ) ) );
		$bank = array(
			'Start by writing “' . $point . '” as a one-line operating rule, not a vague intention. For ' . $kw . ', that means naming the audience, the action, and the proof you will check within seven days. ' . ucfirst( $ctx ) . ' improves when the rule is specific enough that two teammates would execute it the same way.',
			'Most teams fail “' . $point . '” because they optimize for looking productive. Tie this move to ' . $kw . ' with one leading metric — saves, replies, qualified conversations, branded search, or booked calls — then ignore vanity screenshots for a full cycle.',
			'Treat “' . $point . '” as a design constraint. If the draft feels spammy when read aloud, rewrite until it sounds like a professional talking to another professional. That tone filter alone improves ' . $kw . ' more than another tip list.',
			'Document what you tried under “' . $point . '” so the next iteration is faster. Growth systems compound through notes, not memory — especially when you are juggling ' . $ctx . ' alongside other channels.',
			'Make “' . $point . '” assignable: owner, deadline, and definition of done. Without that, ' . $kw . ' stays a slide in a strategy deck. BrandDad’s bias is simple — ship the smallest honest version this week, then tighten.',
			'Pressure-test “' . $point . '” against a real buyer scenario. Would a skeptical prospect understand why this matters for ' . $kw . ' in under thirty seconds? If not, clarify the promise before you scale distribution.',
		);
		return $bank[ $hash % count( $bank ) ];
	}

	protected static function expand_point_followup( $point, $keyword, $cluster, $pi ) {
		$kw   = $keyword ? $keyword : 'this work';
		$hash = abs( crc32( strtolower( 'fu|' . $point . '|' . $kw . '|' . $pi ) ) );
		$bank = array(
			'Practical check: can you show evidence of progress in fourteen days without inventing vanity metrics? If not, shrink the scope until the answer is yes.',
			'Common failure mode: stacking three new tactics at once. Run this move alone long enough to learn, then add complexity.',
			'When capacity is the real constraint, this is usually where a focused BrandDad package beats another DIY sprint — especially if pipeline or search visibility depends on consistency.',
			'Keep language human. If a sentence could belong on any brand’s blog after a find/replace, rewrite it until a client in your niche would recognize themselves.',
			'Capture one example (good or bad) from your last thirty days. Specific artifacts beat abstract advice when you revisit ' . $kw . ' next month.',
			'Share the draft with one peer who will disagree. Early friction prevents public posts that teach everything and invite nothing.',
		);
		return $bank[ $hash % count( $bank ) ];
	}

	protected static function takeaways_block( array $topic, array $outline ) {
		$kw   = isset( $topic['primary_keyword'] ) ? $topic['primary_keyword'] : 'this topic';
		$html = "<h2>Key takeaways</h2>\n";
		$html .= '<p>' . esc_html( 'If you only remember five things about ' . $kw . ', make them these:' ) . '</p>';
		$html .= "<ul>\n";
		$html .= '<li>' . esc_html( 'Optimize for a leading metric you can influence this week — not vanity screenshots.' ) . "</li>\n";
		$html .= '<li>' . esc_html( 'One clear promise beats five clever tips; confusion kills distribution.' ) . "</li>\n";
		$html .= '<li>' . esc_html( 'Consistency on fewer channels outperforms sporadic genius posts.' ) . "</li>\n";
		$html .= '<li>' . esc_html( 'DIY while you have stories and time; buy a system when execution capacity is the bottleneck.' ) . "</li>\n";
		$html .= '<li>' . esc_html( 'Ignore guarantees of overnight #1 rankings or manufactured virality — process compounds; fairy tales do not.' ) . "</li>\n";
		$html .= '</ul>';

		$heads = array();
		foreach ( $outline as $section ) {
			if ( ! empty( $section['heading'] ) ) {
				$heads[] = $section['heading'];
			}
			if ( count( $heads ) >= 4 ) {
				break;
			}
		}
		if ( $heads ) {
			$html .= '<p>' . esc_html( 'Use the sections above as a checklist: ' . implode( '; ', $heads ) . '.' ) . '</p>';
		}
		return $html;
	}

	protected static function closing_html( array $topic ) {
		$kw     = isset( $topic['primary_keyword'] ) ? $topic['primary_keyword'] : 'this topic';
		$intent = isset( $topic['buyer_intent'] ) ? $topic['buyer_intent'] : 'consideration';
		$slugs  = isset( $topic['product_slugs'] ) && is_array( $topic['product_slugs'] ) ? $topic['product_slugs'] : array();
		$first  = ! empty( $slugs[0] ) ? $slugs[0] : '';

		$brand = BDS_AB_Site::profile()['brand'];
		if ( 'decision' === $intent ) {
			$text = 'If you already know ' . $kw . ' is a bottleneck, pick one clear path, run it for a full cycle, and measure leading indicators weekly. ' . $brand . ' sells focused deliverables across the BrandDad network with visible scope.';
			if ( $first ) {
				$text .= ' A natural next look: ';
				return esc_html( $text ) . BDS_AB_Products_Map::link_html( $first, BDS_AB_Products_Map::label( $first ), 'close_' . sanitize_title( $first ) ) . '.';
			}
			return esc_html( $text );
		}
		$text = 'Start with the highest-leverage habit above for two weeks. If execution time is the real constraint, use a ' . $brand . ' package that matches the outcome you actually need — not a bundle of unused extras.';
		if ( $first ) {
			$text .= ' Explore ';
			return esc_html( $text ) . BDS_AB_Products_Map::link_html( $first, BDS_AB_Products_Map::label( $first ), 'close_' . sanitize_title( $first ) )
				. esc_html( ' when you are ready to stop DIY-stalling.' );
		}
		return esc_html( $text );
	}

	protected static function closing_directory_html() {
		return esc_html( 'Active BrandDad Directory members get 10% off eligible services across BrandDad Social, BrandDad.co, and HostTech. If you are not a member yet, join at ' )
			. BDS_AB_Products_Map::link_html( 'branddad-directory', 'directory.branddad.social', 'close_directory' )
			. esc_html( ' before checkout.' );
	}

	protected static function faq_block( array $topic ) {
		$kw      = isset( $topic['primary_keyword'] ) ? $topic['primary_keyword'] : 'this';
		$secs    = isset( $topic['secondary_keywords'] ) && is_array( $topic['secondary_keywords'] ) ? $topic['secondary_keywords'] : array();
		$q2      = ! empty( $secs[0] ) ? $secs[0] : 'related tactics';
		$q3      = ! empty( $secs[1] ) ? $secs[1] : 'content distribution';
		$cluster = isset( $topic['cluster'] ) ? $topic['cluster'] : '';
		$year    = (int) gmdate( 'Y' );
		if ( $year < 2026 ) {
			$year = 2026;
		}

		$html  = "<h2>FAQs</h2>\n";
		$html .= '<h3>Is “' . esc_html( $kw ) . '” still worth focusing on in ' . esc_html( (string) $year ) . '?</h3>';
		$html .= '<p>' . esc_html( 'Yes — if you treat it as a system with leading metrics. Platforms and SERPs change UI, but buyer attention still follows clarity, relevance, and consistency. Thin AI sludge that restates the same tips without an operating process does not age well in search or AI answer engines.' ) . '</p>';

		$html .= '<h3>How does this relate to ' . esc_html( $q2 ) . '?</h3>';
		$html .= '<p>' . esc_html( 'They support the same outcome from different angles. Use one primary motion for about 30 days before stacking extras like ' . $q3 . ', or you will not know what worked. Search engines and AI overviews prefer pages that answer a specific question with structure — which is why this guide uses clear headings and practical FAQs.' ) . '</p>';

		$html .= '<h3>How long until I should expect signal?</h3>';
		$html .= '<p>' . esc_html( self::timeline_answer( $cluster ) ) . '</p>';

		$html .= '<h3>Can BrandDad help execute this?</h3>';
		$html .= '<p>' . esc_html( 'Yes. BrandDad Social sells focused packages (social growth, LinkedIn, SEO, PR, reputation, and more). Sister sites cover logos and premium AI websites (branddad.co) and hosting (hosttech.net). Soft product links in this article point to relevant options when they fit — not as a hard sell. Active BrandDad Directory members get 10% off eligible services.' ) . '</p>';

		$html .= '<h3>Will this help with AI answer engines as well as Google?</h3>';
		$html .= '<p>' . esc_html( 'Pages that are specific, well-structured, and trustworthy are easier for both classic search and AI overviews to cite. We avoid keyword stuffing, fake guarantees, and generic filler — the same traits that make content worth finishing for a human reader.' ) . '</p>';

		return $html;
	}

	protected static function timeline_answer( $cluster ) {
		$map = array(
			'linkedin'           => 'Expect leading indicators (profile visits, replies, conversation quality) within 2–4 weeks of consistent posting and outreach. Pipeline usually lags the habit change.',
			'instagram-growth'   => 'Saves, shares, and watch-time improvements can show in days; Explore-style distribution is uneven and should be judged over several posting cycles, not one lucky post.',
			'seo'                => 'Meaningful SEO movement is measured in weeks to months. Judge early work by crawl/index health, target-page relevance, and authority progress — not overnight #1 screenshots.',
			'facebook-growth'    => 'Conversation density and reach quality can shift within a couple of weeks if creative and prompting improve. Boosts on weak posts will not fix a weak idea.',
			'telegram'           => 'View-through and forward rates often respond within days of useful posts; subscriber growth compounds slower and depends on invite/collab loops.',
			'reputation'         => 'Response quality can improve trust immediately. Policy-based review issues take longer and never justify fake “guaranteed deletion” promises.',
			'pr-authority'       => 'Coverage and branded-search lift depend on newsworthiness and distribution. Treat PR as a moment asset paired with ongoing SEO, not a one-shot hack.',
			'influencers-smm'    => 'Attention quality shows up in profile visits and actions within a campaign window; vanity spikes without niche fit rarely translate to demand.',
			'websites'           => 'A finished site can launch in days once inputs are clear; SEO and demand generation still need their own cycles after launch.',
			'hosting'            => 'Hosting is infrastructure — the win is uptime and a clean handoff so marketing work is not blocked by DNS confusion.',
		);
		return isset( $map[ $cluster ] )
			? $map[ $cluster ]
			: 'Look for leading indicators in 2–4 weeks of consistent execution. Lagging outcomes (revenue, rankings, durable reach) take longer — plan a full cycle before declaring victory or failure.';
	}

	protected static function cluster_audience( $cluster ) {
		$map = array(
			'linkedin'           => 'founders, consultants, and B2B operators',
			'instagram-growth'   => 'brand and creator teams who need discovery without guesswork',
			'seo'                => 'small-business owners evaluating SEO packages',
			'facebook-growth'    => 'page owners tired of paying for weak boosts',
			'telegram'           => 'brands building owned-audience channels',
			'reputation'         => 'local and service businesses protecting Google presence',
			'pr-authority'       => 'brands and talent who need credible public proof',
			'influencers-smm'    => 'teams buying attention without buying empty noise',
			'websites'           => 'founders who need a credible site without a six-month agency timeline',
			'logos'              => 'owners who need a mark that looks intentional on day one',
			'bundles'            => 'cold-start brands shipping identity and a site together',
			'branding'           => 'small businesses refreshing how they show up online',
			'ai-design'          => 'operators who want AI speed without generic output',
			'studio'             => 'buyers navigating BrandDad.co’s AI design studio',
			'hosting'            => 'owners launching or moving a site who want simple hosting',
			'domains'            => 'people connecting a domain without DNS panic',
			'migration'          => 'site owners moving WordPress hosts carefully',
			'infra'              => 'buyers who care what sits under their hosting brand',
			'ai-guide'           => 'people picking a hosting plan without upsell fog',
			'reliability'        => 'operators who want boring backup and uptime habits',
			'email'              => 'businesses setting up domain email realistically',
			'stack'              => 'founders assembling hosting, site, and growth in the right order',
			'membership'         => 'BrandDad Directory members and joiners',
			'listings'           => 'professionals who want directory profiles that get inquiries',
			'network'            => 'buyers mapping BrandDad Social, .co, HostTech, and Directory',
			'credibility'        => 'local professionals building honest online proof',
		);
		return isset( $map[ $cluster ] ) ? $map[ $cluster ] : 'growth-minded operators';
	}

	protected static function cluster_context( $cluster ) {
		$map = array(
			'linkedin'           => 'professional networking on LinkedIn',
			'instagram-growth'   => 'Instagram discovery and content distribution',
			'seo'                => 'search visibility work',
			'facebook-growth'    => 'Facebook page reach and engagement',
			'telegram'           => 'Telegram channel growth',
			'reputation'         => 'review and reputation management',
			'pr-authority'       => 'PR and authority building',
			'influencers-smm'    => 'influencer and social management work',
			'websites'           => 'website and brand identity work',
			'hosting'            => 'hosting and site infrastructure',
		);
		return isset( $map[ $cluster ] ) ? $map[ $cluster ] : 'modern growth work';
	}

	protected static function cluster_entity( $cluster ) {
		$map = array(
			'linkedin'           => 'LinkedIn engagement',
			'instagram-growth'   => 'Instagram Explore reach',
			'seo'                => 'organic search visibility',
			'facebook-growth'    => 'Facebook reach',
			'telegram'           => 'Telegram readership',
			'reputation'         => 'Google review trust',
			'pr-authority'       => 'brand authority',
			'influencers-smm'    => 'creator-driven attention',
			'websites'           => 'website conversion clarity',
			'hosting'            => 'site reliability',
		);
		return isset( $map[ $cluster ] ) ? $map[ $cluster ] : 'growth outcomes';
	}

	protected static function append_related( $html, array $topic ) {
		$cluster = isset( $topic['cluster'] ) ? $topic['cluster'] : '';
		if ( ! $cluster ) {
			return $html;
		}
		$q     = BDS_AB_Topics::all();
		$links = array();
		foreach ( $q as $t ) {
			if ( empty( $t['published_post_id'] ) || empty( $t['cluster'] ) || $t['cluster'] !== $cluster ) {
				continue;
			}
			if ( ! empty( $topic['id'] ) && ! empty( $t['id'] ) && $t['id'] === $topic['id'] ) {
				continue;
			}
			$pid = (int) $t['published_post_id'];
			$rp  = $pid ? get_post( $pid ) : null;
			if ( ! $rp || 'post' !== $rp->post_type || 'publish' !== $rp->post_status ) {
				continue;
			}
			$url = get_permalink( $rp );
			// Related guides must stay on blog posts — never Woo products.
			if ( ! $url || preg_match( '#/(product|product-category|shop)(/|$)#i', $url ) ) {
				continue;
			}
			$links[] = '<li><a href="' . esc_url( $url ) . '">' . esc_html( $t['title'] ) . '</a></li>';
			if ( count( $links ) >= 3 ) {
				break;
			}
		}
		if ( ! $links ) {
			return $html;
		}
		$html .= "\n<h2>Related BrandDad guides</h2>\n<ul>" . implode( '', $links ) . '</ul>';
		return $html;
	}

	protected static function from_ai( array $topic, array $settings ) {
		$prompt = self::ai_prompt( $topic );
		$body   = array(
			'model'       => $settings['openai_model'],
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => self::ai_system_prompt(),
				),
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'temperature' => 0.65,
		);

		$endpoint = trailingslashit( $settings['openai_base_url'] ) . 'chat/completions';
		$resp     = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 90,
				'headers' => array(
					'Authorization' => 'Bearer ' . $settings['openai_api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$code = wp_remote_retrieve_response_code( $resp );
		$data = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( $code < 200 || $code >= 300 || empty( $data['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'bds_ab_ai', 'AI generation failed' );
		}
		$content = $data['choices'][0]['message']['content'];
		$content = preg_replace( '#^```html\s*|\s*```$#', '', trim( $content ) );
		return wp_kses_post( $content );
	}

	protected static function ai_system_prompt() {
		$p     = BDS_AB_Site::profile();
		$brand = $p['brand'];
		$voice = $p['voice'];
		$dir   = BDS_AB_Site::should_mention_directory( 'ai-' . $p['id'] )
			? 'Mention BrandDad Directory + 10% off eligible services once when natural.'
			: 'Do NOT mention Directory 10% in this article (rotation).';
		return "You write SEO-first, sell-oriented but trustworthy guides for {$brand}. Audience: first-timers and busy operators. Brand voice: {$voice}

Rules:
- Target 900–1400 words. Worth finishing. Unique to {$brand} — do not sound like a cross-posted clone of another BrandDad site.
- Use HTML only: <p>, <h2>, <h3>, <ul>, <li>, <a>, <aside>. No markdown. Image alt text if you include <img> (usually skip images).
- Put the primary_keyword in the first paragraph naturally; use secondary keywords in H2/H3 where natural. No keyword stuffing or thin slurry.
- Structure: intro → who it is for → outline H2s with H3s → key takeaways → practical next step → FAQ (4–5 Qs).
- Soft CTAs only. Never guarantee #1 rankings, virality, or review deletion. No plagiarized guru tone.
- When product_links are provided, weave 2–4 natural <a href> links. Prefer mid-article + closing.
- Soft network upsells when relevant: branddad.social (growth/SEO), branddad.co (logos/websites), hosttech.net (hosting), directory.branddad.social.
- {$dir}
- Optional soft WhatsApp CTA once if buyer_intent is decision: https://wa.me/18729105115
- Keep URLs exactly as provided (they already include UTM). Do not invent products or prices beyond what is given.";
	}

	protected static function ai_prompt( array $topic ) {
		$products = array();
		foreach ( BDS_AB_Products_Map::offer_slugs_for_topic( $topic ) as $slug ) {
			$products[] = array(
				'slug'  => $slug,
				'label' => BDS_AB_Products_Map::label( $slug ),
				'url'   => BDS_AB_Products_Map::resolve_url( $slug, 'ai_' . sanitize_title( $slug ) ),
				'site'  => BDS_AB_Products_Map::site( $slug ),
			);
		}
		return wp_json_encode(
			array(
				'title'              => $topic['title'] ?? '',
				'primary_keyword'    => $topic['primary_keyword'] ?? '',
				'secondary_keywords' => $topic['secondary_keywords'] ?? array(),
				'angle'              => $topic['angle'] ?? '',
				'buyer_intent'       => $topic['buyer_intent'] ?? 'consideration',
				'cluster'            => $topic['cluster'] ?? '',
				'outline'            => $topic['outline'] ?? array(),
				'product_links'      => $products,
				'directory_note'     => 'Mention once that BrandDad Directory members get 10% off eligible services (use the directory URL from product_links).',
				'network_note'       => 'Link branddad.co / hosttech.net only when the outline topic honestly involves websites, logos, branding, or hosting.',
				'linkedin_angle'     => $topic['linkedin']['hook'] ?? '',
			)
		);
	}
}
