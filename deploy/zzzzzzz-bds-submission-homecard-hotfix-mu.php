<?php
/**
 * Plugin Name: BrandDad Submission Homepage Card Hotfix
 * Description: Restores the press-release submission card when the legacy homepage block omits it.
 * Version: 0.1.0
 * Author: BrandDad Social
 */

defined( 'ABSPATH' ) || exit;

function bds_submission_homecard_hotfix() {
	$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	if ( 'branddad.social' !== $host || ! is_front_page() ) {
		return;
	}
	?>
	<script id="bds-submission-homecard-hotfix" data-version="0.1.0">
	(function(){
		function restore(){
			var root=document.getElementById('branddad-ai-help');
			var grid=root&&root.querySelector('.bdai-use-grid');
			if(!grid||grid.querySelector('a[href*="/product/ai-press-release-pack/"]'))return;
			var card=document.createElement('article');card.className='bds-oneshot-card';
			var title=document.createElement('h3');title.textContent='Press Release Submission — One Submission Round';card.appendChild(title);
			var price=document.createElement('p');price.className='bdai-price bds-oneshot-price';
			var amount=document.createElement('strong');amount.textContent='$39.99';price.appendChild(amount);
			var period=document.createElement('span');period.textContent=' one time';price.appendChild(period);card.appendChild(price);
			var summary=document.createElement('p');summary.textContent='Pay once. BrandDad writes one factual release and completes one approved submission round. Paid wire distribution and placement fees are separate; coverage is not guaranteed.';card.appendChild(summary);
			var link=document.createElement('a');link.className='bdai-button';link.href='/product/ai-press-release-pack/?ref=mcdqf8t1iinf';link.textContent='Buy this submission service';card.appendChild(link);
			grid.appendChild(card);
			root.setAttribute('data-bds-submission-homecard','0.1.0');
		}
		if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',restore,{once:true});else restore();
		setTimeout(restore,500);
		setTimeout(restore,1500);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bds_submission_homecard_hotfix', PHP_INT_MAX );
