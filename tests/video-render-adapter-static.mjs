import assert from "node:assert/strict";
import { readFileSync } from "node:fs";

const source = readFileSync(new URL("../deploy/bds-ai-video-render-adapter-mu.php", import.meta.url), "utf8");

const required = [
  "BDS_VIDEO_RENDER_ENDPOINT",
  "BDS_VIDEO_RENDER_ALLOWED_HOST",
  "BDS_VIDEO_RENDER_DISPATCH_SECRET",
  "'https' !== strtolower",
  "filter_var( $host, FILTER_VALIDATE_IP )",
  "'/v1/render' !==",
  "wp_safe_remote_post",
  "'redirection' => 0",
  "'timeout'     => 8",
  "hash_hmac( 'sha256', $timestamp . '.' . $body",
  "strlen( $body ) > 100000",
  "strlen( $response_body ) > 16384",
  "202 !== (int) wp_remote_retrieve_response_code",
  "providerJobId",
  "function bds_video_render_adapter_text",
  "function_exists( 'mb_substr' )",
  ": substr( $value, 0, $max_length )",
  "add_filter( 'bds_video_dispatch_render'",
];

for (const token of required) {
  assert.ok(source.includes(token), `missing required adapter control: ${token}`);
}

assert.ok(!source.includes("wp_remote_post("), "dispatch must use wp_safe_remote_post");
assert.ok(!source.includes("CURLOPT_SSL_VERIFYPEER"), "TLS verification must not be disabled");
assert.ok(!source.includes("sslverify' => false"), "TLS verification must not be disabled");
assert.ok(!source.includes("BDS_VIDEO_RENDER_DISPATCH_SECRET', '"), "dispatch secrets must not be committed");
assert.ok(!source.includes("unserialize("), "native deserialization must not be used");
assert.ok(!source.includes("error_log("), "provider responses and signed payloads must not be logged");

const opens = (source.match(/{/g) || []).length;
const closes = (source.match(/}/g) || []).length;
assert.equal(opens, closes, "brace count must balance");

console.log("Video render adapter static security controls: PASS");
