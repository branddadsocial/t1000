import assert from "node:assert/strict";
import { readFileSync } from "node:fs";

const source = readFileSync(new URL("../deploy/bds-ai-video-studio-mu.php", import.meta.url), "utf8");

const required = [
  "BDS_VIDEO_STUDIO_ENABLED",
  "BDS_VIDEO_PROVIDER_ENABLED",
  "bds_video_tenant_key",
  "tenant_key=%s AND owner_user_id=%d",
  "idempotency_hash",
  "hash_hmac( 'sha256'",
  "hash_equals( $expected, $signature )",
  "abs( time() - (int) $timestamp ) > 300",
  "adapter_unavailable",
  "approval_mode",
  "register_rest_route( 'bds-video/v1'",
  "wp_create_nonce( 'wp_rest' )",
  "credentials:'same-origin'",
  "X-WP-Nonce",
  "textContent=",
  "data.projects.map(renderProject)",
  "project.scenes||[]",
  "bds_video_monthly_credit_cap",
  "SELECT GET_LOCK(%s, 3)",
  "SELECT RELEASE_LOCK(%s)",
  "credit_limit_reached",
  "entry_type' => 'refund'",
  "register_rest_route( 'bds-video/v1', '/usage'",
  "parent_project_id",
  "bds_video_rest_create_version",
  "bds_video_rest_list_jobs",
  "Create editable version",
];

for (const token of required) {
  assert.ok(source.includes(token), `missing required control: ${token}`);
}

assert.ok(!source.includes("unserialize("), "untrusted native deserialization must not be introduced");
assert.ok(!source.includes("BDS_VIDEO_WEBHOOK_SECRET', '"), "webhook secrets must not be committed");
assert.ok(!source.includes("BDS_VIDEO_PROVIDER_ENABLED', true"), "provider execution must default off");
assert.ok(!source.includes("BDS_VIDEO_PUBLISHING_ENABLED', true"), "publishing must default off");
assert.ok(!source.includes(".innerHTML"), "project data must not be rendered through innerHTML");
assert.ok(!source.includes("document.write"), "document.write must not be used");
assert.ok(source.includes("'scenes'          => $scenes"), "project responses must include the stored scene plan");

const opens = (source.match(/{/g) || []).length;
const closes = (source.match(/}/g) || []).length;
assert.equal(opens, closes, "brace count must balance");

console.log("Video Studio static security controls: PASS");
