import { lstatSync, readFileSync, realpathSync } from "node:fs";
import { extname, isAbsolute, relative, resolve, sep } from "node:path";
import { createMarkyReviewDraft, MarkyDraftError } from "./marky-draft-adapter.mjs";

const MAX_MANIFEST_BYTES = 64 * 1024;
const MAX_TARGETS_JSON_BYTES = 4 * 1024;

function fail(message) {
  throw new MarkyDraftError(message);
}

function readManifest(manifestPath, outputRoot) {
  if (!isAbsolute(manifestPath) || !isAbsolute(outputRoot) || extname(manifestPath).toLowerCase() !== ".json") fail("invalid_manifest_path");
  const root = realpathSync(resolve(outputRoot));
  const requestedManifest = resolve(manifestPath);
  const stat = lstatSync(requestedManifest);
  if (!stat.isFile() || stat.isSymbolicLink() || stat.size < 2 || stat.size > MAX_MANIFEST_BYTES) fail("invalid_manifest_file");
  const source = realpathSync(requestedManifest);
  const pathFromRoot = relative(root, source);
  if (pathFromRoot === "" || pathFromRoot === ".." || pathFromRoot.startsWith(`..${sep}`) || isAbsolute(pathFromRoot)) fail("invalid_manifest_path");
  try {
    const manifest = JSON.parse(readFileSync(source, "utf8"));
    if (manifest === null || typeof manifest !== "object" || Array.isArray(manifest)) fail("invalid_manifest_file");
    return manifest;
  } catch (error) {
    if (error instanceof MarkyDraftError) throw error;
    fail("invalid_manifest_file");
  }
}

function readTargets(rawTargets) {
  if (typeof rawTargets !== "string" || Buffer.byteLength(rawTargets) > MAX_TARGETS_JSON_BYTES) fail("invalid_targets_config");
  try {
    const targets = JSON.parse(rawTargets);
    if (!Array.isArray(targets)) fail("invalid_targets_config");
    return targets;
  } catch (error) {
    if (error instanceof MarkyDraftError) throw error;
    fail("invalid_targets_config");
  }
}

async function main() {
  if (process.argv.length !== 3) fail("usage_error");
  const outputRoot = process.env.BDS_VIDEO_OUTPUT_ROOT || "";
  const config = {
    apiKey: process.env.MARKY_API_KEY || "",
    workspaceId: process.env.BDS_MARKY_WORKSPACE_ID || "",
    outputRoot,
    destinationUrl: process.env.BDS_BRANDDAD_DESTINATION_URL || "https://branddad.social/",
    targets: readTargets(process.env.BDS_MARKY_TARGETS_JSON || ""),
  };
  const job = readManifest(process.argv[2], outputRoot);
  const result = await createMarkyReviewDraft({ config, job });
  process.stdout.write(`${JSON.stringify({ postId: result.postId, status: result.status })}\n`);
}

main().catch((error) => {
  const code = error instanceof MarkyDraftError ? error.code : "draft_tool_failed";
  process.stderr.write(`${JSON.stringify({ error: code })}\n`);
  process.exitCode = 1;
});
