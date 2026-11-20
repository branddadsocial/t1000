import fs from 'node:fs';
import process from 'node:process';
const source = fs.readFileSync('deploy/bds-000-staging-lifecycle-boundary-acceptance-mu.php','utf8');
const checks = [
 ['version and canonical host fixed', /BDS_STAGING_BOUNDARY_VERSION', '0\.1\.0'/.test(source) && /staging-branddad-social\.stackstaging\.com/.test(source)],
 ['host derives from home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
 ['one-time report and stale lock gate execution', /boundary_pending[\s\S]*! get_option[\s\S]*boundary_lock_v1[\s\S]*time\(\) - 600/.test(source)],
 ['report is 15 booleans plus bounded errors', /\$safe_checks\[ \$key \] = true ===[\s\S]*array_slice\( \(array\) \$errors, 0, 8/.test(source)],
 ['fixture is exact marker-owned non-admin', /bds_stage_acceptance_a[\s\S]*_bds_staging_auth_fixture[\s\S]*! user_can/.test(source)],
 ['UTC immutable start end prior next are explicit', /DateTimeZone\( 'UTC' \)[\s\S]*Y-m-01 00:00:00[\s\S]*Y-m-t 23:59:59[\s\S]*-1 second[\s\S]*\+1 second/.test(source)],
 ['production aggregate is reused for current UTC period', /set_param\( 'period', gmdate\( 'Y-m' \) \)[\s\S]*bds_lifecycle_admin_summary/.test(source)],
 ['all event keys are collision checked before writes', /no_event_collision[\s\S]*bds_staging_boundary_event_id[\s\S]*event_collision/.test(source)],
 ['four private nonfinancial lifecycle events are created', /bds_lifecycle_record\( 'ai_task_completed'[\s\S]*utc_boundary/.test(source)],
 ['both GMT and corresponding local dates are written/read', /get_date_from_gmt[\s\S]*post_date_gmt[\s\S]*post_date[\s\S]*clean_post_cache/.test(source)],
 ['exact two-event monthly delta is required', /exact_two_event_delta[\s\S]*2 === \$after_count - \$before_count/.test(source)],
 ['financial aggregates must remain unchanged', /financial_aggregates_unchanged[\s\S]*confirmed_gross_minor[\s\S]*refunds_minor[\s\S]*net_confirmed_minor/.test(source)],
 ['cleanup requires exact user type object and HMAC key', /boundary_delete_event[\s\S]*_bds_event_user[\s\S]*_bds_event_type[\s\S]*_bds_event_object_type[\s\S]*_bds_event_object_id[\s\S]*hash_equals/.test(source)],
 ['summary and user context are restored', /summary_restored[\s\S]*wp_set_current_user\( \$original_user \)/.test(source)],
 ['completion requires all 15 checks', /15 === count\( \$checks \) && 15 === count\( array_filter\( \$checks \) \)/.test(source)],
 ['route is exact-host GET-only', /boundary_is_host[\s\S]*lifecycle-boundary-acceptance[\s\S]*'methods' => 'GET'/.test(source)],
 ['no request input external call payment or customer creation', !/WP_REST_Request \$request|\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)|wp_remote_|wp_mail\(|wc_create_order|wc_create_refund|wp_insert_user|wp_create_user/.test(source)],
];
let failures=0;for(const [label,passed] of checks){if(passed)console.log(`PASS: ${label}`);else{failures++;console.error(`FAIL: ${label}`)}}
if(failures){console.error(`\n${failures} lifecycle boundary contract check(s) failed.`);process.exit(1)}
console.log(`\nStaging lifecycle boundary contract passed (${checks.length} assertions).`);
