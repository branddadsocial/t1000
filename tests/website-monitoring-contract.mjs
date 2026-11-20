import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-website-monitoring-mu.php', 'utf8');
const report = fs.readFileSync('deploy/bds-customer-status-api-mu.php', 'utf8');
const lifecycle = fs.readFileSync('deploy/bds-lifecycle-events-mu.php', 'utf8');

const checks = [
  ['monitor is versioned', /BDS_WEBSITE_MONITORING_VERSION', '0\.1\.0'/.test(source)],
  ['execution defaults off', /BDS_WEBSITE_MONITORING_ENABLED[\s\S]*true === BDS_WEBSITE_MONITORING_ENABLED/.test(source)],
  ['monitoring requires an active paid allowlisted plan', /ai-business-essentials/.test(source) && /'paid' === \$mode[\s\S]*in_array\( \$slug, \$allowed/.test(source)],
  ['destination comes only from customer onboarding state', /bds_onboarding_state/.test(source) && !/get_param\( 'url'/.test(source)],
  ['customer must attest ownership or permission before monitoring', /authorization_confirmed[\s\S]*authorization_required[\s\S]*own, manage, or have permission/.test(source)],
  ['URL credentials IP literals and nonstandard ports are rejected', /isset\( \$parts\['user'\] \)[\s\S]*isset\( \$parts\['pass'\] \)/.test(source) && /filter_var\( \$host, FILTER_VALIDATE_IP \)/.test(source) && /443 !== \$port[\s\S]*80 !== \$port/.test(source)],
  ['all resolved A and AAAA addresses must be public', /dns_get_record/.test(source) && /FILTER_FLAG_NO_PRIV_RANGE \| FILTER_FLAG_NO_RES_RANGE/.test(source) && /foreach \( array_unique\( \$ips \)/.test(source)],
  ['DNS is revalidated inside every locked check', /bds_monitoring_lock[\s\S]*try \{[\s\S]*bds_monitoring_safe_url/.test(source)],
  ['fetch uses WordPress safe HTTP with no redirects', /wp_safe_remote_get/.test(source) && /'redirection'\s*=>\s*0/.test(source)],
  ['fetch time and response size are bounded', /'timeout'\s*=>\s*10/.test(source) && /'limit_response_size'\s*=>\s*65536/.test(source)],
  ['response body is hashed but never retained', /hash\( 'sha256', \$body \)/.test(source) && !/update_user_meta\([^\n]*\$body/.test(source)],
  ['monitor sends no cookie authorization email or message', !/'Authorization'/.test(source) && !/'Cookie'/.test(source) && !/wp_mail\(/.test(source)],
  ['manual checks are limited to one per hour', /_bds_monitor_manual_requested[\s\S]*HOUR_IN_SECONDS/.test(source)],
  ['customer check lock has stale recovery', /bds_monitor_[\s\S]*time\(\) - 120/.test(source)],
  ['lost entitlement disables future queueing', /inactive_plan[\s\S]*delete_user_meta\( \$user_id, '_bds_monitor_enabled'/.test(source)],
  ['profile URL changes require explicit reconnect', /needs_reconnect[\s\S]*website_changed/.test(source)],
  ['scheduler processes bounded batches with continuation', /'number' => 20/.test(source) && /bds_monitoring_batch_continue/.test(source) && /time\(\) - 300/.test(source)],
  ['monthly customer evidence is bounded to 24 periods', /_bds_monitor_monthly_v1/.test(source) && /array_slice\( \$all, -24/.test(source)],
  ['customer report uses actual monitor counters', /bds_monitoring_customer_summary/.test(report)],
  ['monitor checks emit internal lifecycle evidence', /bds_website_monitor_checked/.test(source) && /website_monitor_check/.test(lifecycle)],
  ['all REST routes require authenticated permission callback', (source.match(/'permission_callback' => 'bds_monitoring_permission'/g) || []).length === 3],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} website monitoring contract check(s) failed.`);
  process.exit(1);
}
console.log('\nWebsite monitoring contract passed.');
