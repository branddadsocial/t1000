import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-sales-assistant-acceptance-mu.php', 'utf8');

const checks = [
  ['version is explicit', /BDS_STAGING_SALES_ACCEPTANCE_VERSION', '0\.1\.1'/.test(source)],
  ['canonical TLS staging host is fixed', /BDS_STAGING_SALES_ACCEPTANCE_HOST', 'staging-branddad-social\.stackstaging\.com'/.test(source)],
  ['host derives from configured home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
  ['execution is one-time report gated', /sales_acceptance_pending[\s\S]*! get_option\( BDS_STAGING_SALES_ACCEPTANCE_REPORT, false \)/.test(source)],
  ['dummy credential is visibly non-production', /staging-sales-acceptance-only-not-a-provider-credential/.test(source)],
  ['enable key and cap exist only while run is pending', /if \( bds_staging_sales_acceptance_pending\(\) \)[\s\S]*BDS_SALES_ASSISTANT_ENABLED[\s\S]*BDS_SALES_ASSISTANT_DAILY_LIMIT[\s\S]*BDS_FORMWISE_EXECUTION_KEY/.test(source)],
  ['daily acceptance cap is exactly three', /BDS_SALES_ASSISTANT_DAILY_LIMIT', 3/.test(source)],
  ['all FormWise traffic is intercepted while pending', /builder\.formwise\.ai[\s\S]*staging_sales_provider_blocked/.test(source) && /add_filter\( 'pre_http_request', 'bds_staging_sales_acceptance_http', 1, 3 \)/.test(source)],
  ['provider endpoint uses fixed production agent id', /BDS_SALES_ASSISTANT_AGENT_ID[\s\S]*\/conversation/.test(source)],
  ['provider mock requires active request', /empty\( \$state\['active'\] \)[\s\S]*staging_sales_provider_blocked/.test(source)],
  ['provider request validates auth content type and bounds', /Bearer[\s\S]*Content-Type[\s\S]*30 ===[\s\S]*0 ===[\s\S]*65536 ===/.test(source)],
  ['provider prompt constraints are validated', /server-verified offers and prices[\s\S]*never invent a discount[\s\S]*Customer question/.test(source)],
  ['provider prompt must not contain offer URLs', /false === strpos\( \$message, '\"url\"' \)/.test(source)],
  ['mock reply contains hostile HTML and links', /<b>Simple help<\/b>[\s\S]*evil\.example[\s\S]*phish\.example/.test(source)],
  ['report is boolean allowlist plus bounded errors', /\$safe_checks\[ \$key \] = true ===[\s\S]*array_slice\( \(array\) \$errors, 0, 12 \)[\s\S]*sanitize_key/.test(source)],
  ['report does not expose tokens ids prompts keys or URLs', !/'session'\s*=>|'conversation'\s*=>|'url'\s*=>|'prompt'\s*=>|'key'\s*=>/.test(source.match(/function bds_staging_sales_acceptance_save_report[\s\S]*?\n}/)?.[0] || '')],
  ['fixture product must be exact marked 29.99 product', /ai-business-essentials[\s\S]*_bds_staging_fixture[\s\S]*29\.99/.test(source)],
  ['runner uses anonymous context and documentation IP', /wp_set_current_user\( 0 \)[\s\S]*BDS_STAGING_SALES_ACCEPTANCE_IP/.test(source)],
  ['owned and attacker origins are tested', /branddad\.social[\s\S]*owned_origin_allowed[\s\S]*attacker\.example[\s\S]*unowned_origin_denied/.test(source)],
  ['request-size denial occurs before provider call', /CONTENT_LENGTH'\] = '20000'[\s\S]*request_too_large[\s\S]*0 === absint\( \$http_state\['calls'\]/.test(source)],
  ['public catalog requires exact flagship price and authoritative checkout host', /catalog_grounded[\s\S]*29\.99[\s\S]*catalog_checkout_authoritative[\s\S]*BDS_STAGING_SALES_ACCEPTANCE_HOST/.test(source)],
  ['SEO recommendation is first with authoritative saleable link', /seo_offer_ranked_first[\s\S]*ai-local-seo[\s\S]*seo_offer_saleable[\s\S]*BDS_STAGING_SALES_ACCEPTANCE_HOST/.test(source)],
  ['fallback checkout must be server saleable', /fallback_offer_saleable[\s\S]*BDS_STAGING_SALES_ACCEPTANCE_HOST/.test(source)],
  ['hostile reply links and HTML are asserted removed', /hostile_reply_links_removed[\s\S]*stripos[\s\S]*hostile_reply_html_removed/.test(source)],
  ['session validity tamper expiry and client binding are tested', /session_token_valid[\s\S]*session_token_tamper_denied[\s\S]*session_token_expiry_denied[\s\S]*session_token_client_bound/.test(source)],
  ['server-side continuation is tested', /conversation_continues_server_side[\s\S]*continuations/.test(source)],
  ['lead and social recommendations have authoritative links', /second_reply_grounded[\s\S]*ai-lead-assistant[\s\S]*for_sale[\s\S]*BDS_STAGING_SALES_ACCEPTANCE_HOST/.test(source) && /third_reply_grounded[\s\S]*ai-social-growth[\s\S]*for_sale[\s\S]*BDS_STAGING_SALES_ACCEPTANCE_HOST/.test(source)],
  ['fourth request is denied before a provider call', /daily_cap_denies_fourth[\s\S]*rate_limited[\s\S]*daily_cap_stops_provider_call/.test(source)],
  ['per-client minute cap is tested', /set_transient\( \$rate_key, 10[\s\S]*client_minute_cap_denies/.test(source)],
  ['exact provider call and continuation counts are required', /exact_provider_call_count[\s\S]*3 === absint[\s\S]*2 === absint/.test(source)],
  ['rate state is snapshotted and restored', /\$rate_before[\s\S]*\$daily_before[\s\S]*rate_state_restored/.test(source)],
  ['conversation transients are exact and removed', /bds_sales_conv_[\s\S]*array_unique\( \$conversation_keys \)[\s\S]*delete_transient/.test(source)],
  ['server globals and user context are restored', /\$server_before[\s\S]*server_state_restored[\s\S]*wp_set_current_user\( \$original_user \)/.test(source)],
  ['stale-safe lock is removed', /bds_staging_sales_assistant_lock_v1[\s\S]*time\(\) - 600[\s\S]*delete_option\( \$lock \)/.test(source)],
  ['completion requires all 31 checks', /31 === count\( \$checks \) && 31 === count\( array_filter\( \$checks \) \)/.test(source)],
  ['route is exact-host gated and GET-only', /sales_acceptance_is_host[\s\S]*\/staging\/sales-assistant-acceptance[\s\S]*'methods'\s*=>\s*'GET'/.test(source)],
  ['helper accepts no caller request or file input', !/function bds_staging_sales_acceptance_report\(\s*WP_REST_Request|\$_(?:GET|POST|REQUEST|COOKIE|FILES)\b/.test(source)],
  ['helper creates no customer objects', !/wp_insert_post|wp_insert_user|wp_create_user|wc_create_order|wc_create_refund/.test(source)],
  ['helper sends no email or gateway action', !/wp_mail\(|payment_complete\(|process_payment\(|WC_Gateway/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} staging sales-assistant acceptance contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging sales-assistant acceptance contract passed (${checks.length} assertions).`);
