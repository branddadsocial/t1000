import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-000-staging-lifecycle-truncation-acceptance-mu.php', 'utf8');
const checks = [
  ['version and canonical host fixed', /BDS_STAGING_TRUNCATION_VERSION', '0\.1\.0'/.test(source) && /staging-branddad-social\.stackstaging\.com/.test(source)],
  ['target and batch are fixed and bounded', /TRUNCATION_TARGET', 5000/.test(source) && /TRUNCATION_BATCH', 20/.test(source)],
  ['continued creation is fail-closed after host ceiling', /TRUNCATION_CONTINUE_CREATION', false/.test(source) && /host_execution_ceiling/.test(source)],
  ['host derives from canonical home URL', /wp_parse_url\( home_url\( '\/' \), PHP_URL_HOST \)/.test(source)],
  ['production aggregate is reused with a server period', /WP_REST_Request[\s\S]*set_param\( 'period'[\s\S]*bds_lifecycle_admin_summary/.test(source)],
  ['summary stores only bounded aggregate fields', /unique_customers_with_events[\s\S]*confirmed_gross_minor[\s\S]*refunds_minor[\s\S]*net_confirmed_minor[\s\S]*truncated/.test(source)],
  ['fixture is exact marker-owned non-admin', /bds_stage_acceptance_a[\s\S]*_bds_staging_auth_fixture[\s\S]*! user_can/.test(source)],
  ['baseline must be available and not truncated', /baseline_not_truncated[\s\S]*empty\( \$before\['truncated'\] \)[\s\S]*baseline_unavailable/.test(source)],
  ['persistent phases are create verify cleanup complete', /'phase' => 'create'[\s\S]*'verify'[\s\S]*'cleanup'[\s\S]*save_report/.test(source)],
  ['stale-safe lock serializes batches', /truncation_lock_v1[\s\S]*time\(\) - 90[\s\S]*delete_option\( \$lock \)/.test(source)],
  ['collision check precedes every production-shape record', /event_id\( \$user_id, \$offset \)[\s\S]*event_collision[\s\S]*bds_staging_truncation_create_event/.test(source)],
  ['events are private nonfinancial production-shape records', /truncation_create_event[\s\S]*'post_type'\s*=> 'bds_lifecycle_evt'[\s\S]*'post_status'\s*=> 'private'[\s\S]*_bds_event_type[\s\S]*ai_task_completed[\s\S]*staging_truncation[\s\S]*website_audit/.test(source)],
  ['all events use one fixed monthly GMT date with local correspondence', /'fixed_gmt' => \$period \. '-15 12:00:00'/.test(source) && /get_date_from_gmt[\s\S]*post_date_gmt[\s\S]*post_date/.test(source)],
  ['event ownership requires post user type object and HMAC key', /truncation_event_owned[\s\S]*post_author[\s\S]*_bds_event_user[\s\S]*_bds_event_type[\s\S]*_bds_event_object_type[\s\S]*_bds_event_object_id[\s\S]*hash_equals/.test(source)],
  ['verification requires exactly 5000 unique tracked ids', /target_created[\s\S]*count\( \$state\['ids'\] \)[\s\S]*ids_unique[\s\S]*array_unique/.test(source)],
  ['production truncation flag must be true', /truncation_flag_true[\s\S]*\$after\['truncated'\][\s\S]*truncation_flag_missing/.test(source)],
  ['cleanup works in fixed reverse batches', /while \( ! empty\( \$state\['ids'\] \) && \$removed < BDS_STAGING_TRUNCATION_BATCH \)[\s\S]*array_pop/.test(source)],
  ['cleanup requires exact owned event before hard delete', /truncation_delete_event[\s\S]*truncation_event_owned[\s\S]*wp_delete_post\( absint\( \$event_id \), true \)/.test(source)],
  ['recovery discovery is fixed and capped one beyond target', /truncation_reconcile_events[\s\S]*BDS_STAGING_TRUNCATION_TARGET \+ 1[\s\S]*_bds_event_object_type[\s\S]*staging_truncation/.test(source)],
  ['recovery requires range uniqueness ownership and contiguity', /recovery_range_or_duplicate[\s\S]*recovery_ownership_failed[\s\S]*ksort[\s\S]*recovery_gap/.test(source)],
  ['recovery refuses fewer rows than persisted progress', /count\( \$reconciled \) < absint\( \$state\['next'\]/.test(source)],
  ['completion requires byte-equivalent restored summary', /summary_restored[\s\S]*\( \$state\['before'\] \?\? array\(\) \) === \$restored[\s\S]*summary_not_restored/.test(source)],
  ['state removal is verified before final report', /delete_option\( BDS_STAGING_TRUNCATION_STATE \)[\s\S]*state_removed[\s\S]*save_report/.test(source)],
  ['errors block safely and remain resumable evidence', /catch \( Throwable \$exception \)[\s\S]*'phase'\] = 'blocked'[\s\S]*update_option\( BDS_STAGING_TRUNCATION_STATE/.test(source)],
  ['report contains counts booleans and bounded errors but no ids', /created[\s\S]*remaining[\s\S]*safe_checks[\s\S]*safe_errors/.test(source) && !/'ids' => \$state\['ids'\]/.test(source)],
  ['only the exact report callback advances the runner', /function bds_staging_truncation_report\(\) \{[\s\S]*bds_staging_truncation_run\(\)/.test(source) && !/add_action\( 'init', 'bds_staging_truncation_run'/.test(source)],
  ['route is exact-host GET-only', /truncation_is_host[\s\S]*lifecycle-truncation-acceptance[\s\S]*'methods' => 'GET'/.test(source)],
  ['no request input external call payment customer or mail surface', !/WP_REST_Request \$request|\$_(?:GET|POST|REQUEST|COOKIE|FILES|SERVER)|wp_remote_|wp_mail\(|wc_create_order|wc_create_refund|wp_insert_user|wp_create_user/.test(source)],
];

let failures = 0;
for (const [label, passed] of checks) {
  if (passed) console.log(`PASS: ${label}`);
  else { failures++; console.error(`FAIL: ${label}`); }
}
if (failures) {
  console.error(`\n${failures} lifecycle truncation contract check(s) failed.`);
  process.exit(1);
}
console.log(`\nStaging lifecycle truncation contract passed (${checks.length} assertions).`);
