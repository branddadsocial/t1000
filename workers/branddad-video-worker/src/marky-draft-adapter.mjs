import { lstatSync, readFileSync, realpathSync } from "node:fs";
import { extname, isAbsolute, relative, resolve, sep } from "node:path";

const MARKY_API_ORIGIN = "https://api.mymarky.ai";
const MARKY_API_VERSION = "2026-11-03";
const MAX_VIDEO_BYTES = 50 * 1024 * 1024;
const MAX_RESPONSE_BYTES = 32 * 1024;
const MAX_POST_BODY_BYTES = 100 * 1024;
const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const DRAFT_JOB_FIELDS = new Set(["jobId", "mediaPath", "caption", "platformCaptions"]);
const PLATFORMS = new Set(["facebook", "instagram", "instagramStory", "linkedIn", "tiktok", "youtube"]);
const CAPTION_LIMITS = { facebook: 63_206, instagram: 2_200, instagramStory: 2_200, linkedIn: 3_000, tiktok: 2_200, youtube: 5_000 };

export class MarkyDraftError extends Error {
  constructor(code) {
    super(code);
    this.name = "MarkyDraftError";
    this.code = code;
  }
}

function fail(code) {
  throw new MarkyDraftError(code);
}

function isPlainObject(value) {
  return value !== null && typeof value === "object" && !Array.isArray(value) && Object.getPrototypeOf(value) === Object.prototype;
}

function validHttpsUrl(value) {
  if (typeof value !== "string" || value.length > 2_048) return false;
  try {
    const url = new URL(value);
    return url.protocol === "https:" && url.username === "" && url.password === "";
  } catch {
    return false;
  }
}

function normalizeConfig(config) {
  if (!isPlainObject(config)
      || typeof config.apiKey !== "string"
      || !config.apiKey.startsWith("mk_live_")
      || config.apiKey.length < 32
      || !UUID_PATTERN.test(String(config.workspaceId || ""))
      || !isAbsolute(String(config.outputRoot || ""))
      || !validHttpsUrl(config.destinationUrl)
      || !Array.isArray(config.targets)
      || config.targets.length < 1
      || config.targets.length > 10) {
    fail("draft_adapter_unavailable");
  }
  const seenIds = new Set();
  const targets = config.targets.map((target) => {
    if (!isPlainObject(target) || !UUID_PATTERN.test(String(target.integrationId || "")) || !PLATFORMS.has(target.platform)
        || seenIds.has(target.integrationId)) {
      fail("draft_adapter_unavailable");
    }
    seenIds.add(target.integrationId);
    return { integrationId: target.integrationId, platform: target.platform };
  });
  const destination = new URL(config.destinationUrl);
  if (destination.search || destination.hash) fail("draft_adapter_unavailable");
  return { ...config, targets };
}

function readApprovedVideo(mediaPath, outputRoot) {
  if (typeof mediaPath !== "string" || !isAbsolute(mediaPath) || !isAbsolute(outputRoot)) fail("invalid_video_path");
  const root = realpathSync(resolve(outputRoot));
  const requestedSource = resolve(mediaPath);
  const requestedStat = lstatSync(requestedSource);
  if (!requestedStat.isFile() || requestedStat.isSymbolicLink()) fail("invalid_video_file");
  const source = realpathSync(requestedSource);
  const pathFromRoot = relative(root, source);
  if (pathFromRoot === "" || pathFromRoot === ".." || pathFromRoot.startsWith(`..${sep}`) || isAbsolute(pathFromRoot)
      || extname(source).toLowerCase() !== ".mp4") {
    fail("invalid_video_path");
  }
  if (requestedStat.size < 1 || requestedStat.size > MAX_VIDEO_BYTES) fail("invalid_video_file");
  return readFileSync(source);
}

async function readCappedJson(response) {
  const declaredLength = Number(response.headers.get("content-length") || 0);
  if (!Number.isFinite(declaredLength) || declaredLength < 0 || declaredLength > MAX_RESPONSE_BYTES || !response.body
      || !String(response.headers.get("content-type") || "").toLowerCase().startsWith("application/json")) {
    fail("invalid_marky_response");
  }
  const reader = response.body.getReader();
  const chunks = [];
  let bytes = 0;
  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    bytes += value.byteLength;
    if (bytes > MAX_RESPONSE_BYTES) {
      await reader.cancel();
      fail("invalid_marky_response");
    }
    chunks.push(value);
  }
  const body = Buffer.concat(chunks.map((chunk) => Buffer.from(chunk))).toString("utf8");
  try {
    const parsed = JSON.parse(body);
    if (!isPlainObject(parsed)) fail("invalid_marky_response");
    return parsed;
  } catch (error) {
    if (error instanceof MarkyDraftError) throw error;
    fail("invalid_marky_response");
  }
}

function validateDraftJob(job, targets) {
  if (!isPlainObject(job) || Object.keys(job).length !== DRAFT_JOB_FIELDS.size
      || !Object.keys(job).every((field) => DRAFT_JOB_FIELDS.has(field))
      || !UUID_PATTERN.test(String(job.jobId || "")) || typeof job.mediaPath !== "string"
      || typeof job.caption !== "string" || job.caption.length < 1 || job.caption.length > 2_200
      || !isPlainObject(job.platformCaptions || {})) {
    fail("invalid_draft_job");
  }
  const allowedPlatforms = new Set(targets.map((target) => target.platform));
  for (const [platform, caption] of Object.entries(job.platformCaptions)) {
    if (!allowedPlatforms.has(platform) || typeof caption !== "string" || caption.length < 1 || caption.length > CAPTION_LIMITS[platform]) {
      fail("invalid_draft_job");
    }
  }
}

export async function createMarkyReviewDraft({ config, job, fetchImpl = fetch }) {
  const safeConfig = normalizeConfig(config);
  validateDraftJob(job, safeConfig.targets);
  const video = readApprovedVideo(job.mediaPath, safeConfig.outputRoot);
  const commonHeaders = {
    Authorization: `Bearer ${safeConfig.apiKey}`,
    "Marky-Version": MARKY_API_VERSION,
  };
  const mediaForm = new FormData();
  mediaForm.append("file", new Blob([video], { type: "video/mp4" }), `branddad-${job.jobId}.mp4`);
  let mediaResponse;
  try {
    mediaResponse = await fetchImpl(`${MARKY_API_ORIGIN}/api/businesses/${encodeURIComponent(safeConfig.workspaceId)}/media`, {
      method: "POST",
      headers: commonHeaders,
      body: mediaForm,
      redirect: "error",
      signal: AbortSignal.timeout(30_000),
    });
  } catch {
    fail("marky_upload_failed");
  }
  if (!mediaResponse.ok || (mediaResponse.status !== 200 && mediaResponse.status !== 201)) fail("marky_upload_failed");
  const media = await readCappedJson(mediaResponse);
  if (!validHttpsUrl(media.original_url)) fail("invalid_marky_response");

  const platformOverrides = safeConfig.targets.map((target) => ({
    platform: target.platform,
    caption: job.platformCaptions[target.platform] || job.caption,
    media_urls: [media.original_url],
    ...(target.platform === "facebook" ? { link: safeConfig.destinationUrl } : {}),
  }));
  const postBody = {
    caption: job.caption,
    platform_overrides: platformOverrides,
    restrict_publish_to_integration_ids: safeConfig.targets.map((target) => target.integrationId),
    media_urls: [media.original_url],
    status: "NEW",
    link: safeConfig.destinationUrl,
    metadata: {
      media_type: "animated-video",
      format: "build-in-public",
      hook: "bold-claim",
      topic: "branddad-video-studio",
      created_by: "branddad-video-autopilot",
      video_job_id: job.jobId,
      approval_mode: "required",
    },
  };
  const encodedPostBody = JSON.stringify(postBody);
  if (Buffer.byteLength(encodedPostBody) > MAX_POST_BODY_BYTES) fail("invalid_draft_job");
  let postResponse;
  try {
    postResponse = await fetchImpl(`${MARKY_API_ORIGIN}/api/businesses/${encodeURIComponent(safeConfig.workspaceId)}/posts`, {
      method: "POST",
      headers: { ...commonHeaders, "Content-Type": "application/json" },
      body: encodedPostBody,
      redirect: "error",
      signal: AbortSignal.timeout(10_000),
    });
  } catch {
    fail("marky_draft_failed");
  }
  if (!postResponse.ok || (postResponse.status !== 200 && postResponse.status !== 201)) fail("marky_draft_failed");
  const post = await readCappedJson(postResponse);
  if (!UUID_PATTERN.test(String(post.id || "")) || post.status !== "NEW") fail("invalid_marky_response");
  return { postId: post.id, mediaUrl: media.original_url, status: "NEW" };
}
