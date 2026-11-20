import fs from 'node:fs';
import process from 'node:process';

const source = fs.readFileSync('deploy/bds-ai-execution-mu.php', 'utf8');

// Snapshot verified read-only against the published BrandDad Social FormWise
// workspace on 2026-11-19. A FormWise editor changing a published question,
// enum option, workflow, or App must deliberately update this contract and the
// WordPress allowlist together.
const required = [
  ['Copy and Reviews flow id', '0cc9b6ff-309a-49f7-9a47-dd66c926208a'],
  ['SEO Action Planner flow id', '2e9921a0-5996-4bc9-a053-49fab3a7fc58'],
  ['Website Improvement Planner flow id', '3d3deeff-cc55-43a3-b96e-4969df4296cc'],
  ['Directory Listing Studio flow id', '4ada4e24-ac55-4a56-be18-113107cc8741'],
  ['LinkedIn Profile Analyzer flow id', '56b1aa4f-5598-48d0-8a42-2bc6c41d791a'],
  ['AI Studio App id', '571f6f9c-7f7a-4b44-9e32-5d5b2fe11c6e'],
  ['Copy source field', 'provide_the_source_details_'],
  ['Copy audience field', 'who_is_the_intended_audience_'],
  ['Copy tone field', 'what_tone_should_the_copy_use_'],
  ['Copy action field', 'what_action_should_the_reader_take_'],
  ['SEO page field', 'which_page_and_goal_are_you_working_on_'],
  ['SEO observation field', 'what_did_the_website_check_observe_'],
  ['SEO performance field', 'what_search_performance_data_can_you_share_'],
  ['SEO focus field', 'which_seo_focus_should_the_plan_use_'],
  ['Website help field', 'what_kind_of_website_help_is_needed_'],
  ['Website page field', 'what_public_page_and_goal_should_we_focus_on_'],
  ['Website observation field', 'what_did_you_observe_'],
  ['Listing name field', 'what_is_the_business_name_'],
  ['Listing description field', 'what_does_the_business_do_'],
  ['Listing location field', 'where_does_the_business_operate_'],
  ['Listing contact field', 'what_contact_details_and_hours_are_approved_for_the_listing_'],
  ['Listing category field', 'what_categories_or_specialties_seem_relevant_'],
  ['Listing language field', 'which_output_language_do_you_want_'],
  ['Listing facts field', 'what_factual_details_must_not_be_changed_or_invented_'],
  ['LinkedIn profile field', 'paste_the_linkedin_profile_text_you_are_authorized_to_analyze_'],
  ['LinkedIn audience field', 'who_should_this_profile_attract_'],
  ['LinkedIn goal field', 'what_is_the_primary_linkedin_goal_'],
  ['LinkedIn tone field', 'what_tone_should_the_profile_use_'],
];

const exactEnumValues = [
  'Social caption',
  'Customer review response',
  'Clear and professional',
  'Friendly and conversational',
  'Confident and persuasive',
  'Calm and empathetic',
  'Local SEO',
  'Repair',
  'Speed',
  'Conversion',
  'Ongoing care',
  'English',
  'Spanish',
  'English and Spanish',
  'Generate qualified leads',
  'Build professional authority',
  'Find a role',
  'Grow a relevant network',
  'Promote services',
  'Professional and direct',
  'Warm and approachable',
  'Expert and authoritative',
  'Bold and energetic',
];

let failures = 0;
for (const [label, value] of required) {
  if (source.includes(value)) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

for (const value of exactEnumValues) {
  if (source.includes(`'${value}'`)) console.log(`PASS: enum ${value}`);
  else { failures += 1; console.error(`FAIL: enum ${value}`); }
}

const providerChecks = [
  ['flow endpoint is fixed to FormWise API v1', /https:\/\/builder\.formwise\.ai\/api\/v1\/flows\/' \. rawurlencode\( \$tool\['remote_id'\] \) \. '\/run/],
  ['App endpoint is fixed to FormWise API v1', /https:\/\/builder\.formwise\.ai\/api\/v1\/apps\/' \. rawurlencode\( \$tool\['remote_id'\] \) \. '\/conversation/],
  ['flow payload sends keyed inputs', /\$body = array\( 'inputs' => \$inputs \)/],
  ['App payload sends only the validated message', /\$body = array\( 'message' => \(string\) \( \$inputs\['message'\] \?\? '' \) \)/],
  ['provider auth uses a server-side bearer key', /'Authorization' => 'Bearer ' \. trim\( \(string\) BDS_FORMWISE_EXECUTION_KEY \)/],
];

for (const [label, pattern] of providerChecks) {
  if (pattern.test(source)) console.log(`PASS: ${label}`);
  else { failures += 1; console.error(`FAIL: ${label}`); }
}

if (failures) {
  console.error(`\n${failures} published FormWise contract check(s) failed.`);
  process.exit(1);
}

console.log('\nPublished FormWise contract passed.');
