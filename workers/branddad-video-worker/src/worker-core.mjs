import { createHash, createHmac, randomUUID, timingSafeEqual } from "node:crypto";
import { lstatSync, mkdirSync, opendirSync, readFileSync, unlinkSync, writeFileSync } from "node:fs";
import { isIP } from "node:net";
import { isAbsolute, join, resolve } from "node:path";

export const MAX_BODY_BYTES = 100_000;
export const MAX_CLOCK_SKEW_SECONDS = 300;

const TOP_LEVEL_FIELDS = new Set([
  "jobId",
  "projectId",
  "projectVersion",
  "template",
  "durationSeconds",
  "aspectRatio",
  "language",
  "sourceSummary",
  "scenes",
  "callbackUrl",
  "maxProviderCost",
  "reservedCredits",
]);
const SCENE_FIELDS = new Set(["durationSeconds", "onScreenText", "narration", "visualDirection"]);
const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const UUID_V4_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const SIGNATURE_PATTERN = /^[0-9a-f]{64}$/;
const LANGUAGE_PATTERN = /^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/;
const PROVIDER_JOB_PATTERN = /^bds_[0-9a-f]{32}$/;
const SHA256_PATTERN = /^[0-9a-f]{64}$/;

export class WorkerError extends Error {
  constructor(status, code) {
    super(code);
    this.name = "WorkerError";
    this.status = status;
    this.code = code;
  }
}

function reject(status, code) {
  throw new WorkerError(status, code);
}

function isPlainObject(value) {
  return value !== null && typeof value === "object" && !Array.isArray(value) && Object.getPrototypeOf(value) === Object.prototype;
}

function hasOnlyFields(value, allowed) {
  return Object.keys(value).every((field) => allowed.has(field));
}

function boundedString(value, maxLength, { pattern, allowEmpty = true } = {}) {
  if (typeof value !== "string" || value.length > maxLength || (!allowEmpty && value.length === 0)) {
    return false;
  }
  return !pattern || pattern.test(value);
}

function boundedNumber(value, min, max) {
  return typeof value === "number" && Number.isFinite(value) && value >= min && value <= max;
}

function boundedInteger(value, min, max) {
  return Number.isInteger(value) && value >= min && value <= max;
}

function isAsciiLetterOrDigit(character) {
  const code = character.charCodeAt(0);
  return (code >= 48 && code <= 57) || (code >= 65 && code <= 90) || (code >= 97 && code <= 122);
}

function isDnsHostname(value) {
  if (typeof value !== "string" || value.length < 4 || value.length > 253 || isIP(value) !== 0) return false;
  const labels = value.split(".");
  if (labels.length < 2) return false;
  for (const label of labels) {
    if (label.length < 1 || label.length > 63 || !isAsciiLetterOrDigit(label[0]) || !isAsciiLetterOrDigit(label.at(-1))) return false;
    for (const character of label) {
      if (character !== "-" && !isAsciiLetterOrDigit(character)) return false;
    }
  }
  const topLevelDomain = labels.at(-1);
  return topLevelDomain.length >= 2 && [...topLevelDomain].every((character) => {
    const code = character.charCodeAt(0);
    return (code >= 65 && code <= 90) || (code >= 97 && code <= 122);
  });
}

function validateCallbackUrl(rawUrl, allowedHost) {
  if (!boundedString(rawUrl, 2048, { allowEmpty: false })
      || !isDnsHostname(allowedHost)) {
    return false;
  }
  try {
    const url = new URL(rawUrl);
    return url.protocol === "https:"
      && url.hostname.toLowerCase() === allowedHost.toLowerCase()
      && url.port === ""
      && url.username === ""
      && url.password === ""
      && url.pathname === "/wp-json/bds-video/v1/webhooks/render"
      && url.search === ""
      && url.hash === "";
  } catch {
    return false;
  }
}

export function validateRenderPayload(value, callbackAllowedHost) {
  if (!isPlainObject(value) || !hasOnlyFields(value, TOP_LEVEL_FIELDS) || Object.keys(value).length !== TOP_LEVEL_FIELDS.size) {
    reject(400, "invalid_payload");
  }
  if (!boundedString(value.jobId, 36, { pattern: UUID_PATTERN, allowEmpty: false })
      || !boundedString(value.projectId, 36, { pattern: UUID_PATTERN, allowEmpty: false })
      || !boundedInteger(value.projectVersion, 1, 100_000)
      || value.template !== "branddad_motion_v1"
      || ![15, 30, 60, 90].includes(value.durationSeconds)
      || !["9:16", "16:9", "1:1", "4:5"].includes(value.aspectRatio)
      || !boundedString(value.language, 12, { pattern: LANGUAGE_PATTERN, allowEmpty: false })
      || !boundedString(value.sourceSummary, 5_000)
      || !validateCallbackUrl(value.callbackUrl, callbackAllowedHost)
      || !boundedNumber(value.maxProviderCost, 0, 1_000)
      || !boundedInteger(value.reservedCredits, 0, 100_000)
      || !Array.isArray(value.scenes)
      || value.scenes.length > 50) {
    reject(400, "invalid_payload");
  }
  let totalSceneSeconds = 0;
  for (const scene of value.scenes) {
    if (!isPlainObject(scene) || !hasOnlyFields(scene, SCENE_FIELDS) || Object.keys(scene).length !== SCENE_FIELDS.size
        || !boundedInteger(scene.durationSeconds, 1, 90)
        || !boundedString(scene.onScreenText, 500)
        || !boundedString(scene.narration, 1_200)
        || !boundedString(scene.visualDirection, 1_200)) {
      reject(400, "invalid_payload");
    }
    totalSceneSeconds += scene.durationSeconds;
  }
  if (totalSceneSeconds > 300) {
    reject(400, "invalid_payload");
  }
  return value;
}

export async function verifyDispatch({ rawBody, headers, secret, callbackAllowedHost, replayStore, now = Date.now() }) {
  if (!Buffer.isBuffer(rawBody) || rawBody.length === 0 || rawBody.length > MAX_BODY_BYTES) {
    reject(rawBody?.length > MAX_BODY_BYTES ? 413 : 400, rawBody?.length > MAX_BODY_BYTES ? "payload_too_large" : "invalid_payload");
  }
  if (typeof secret !== "string" || secret.length < 32 || !replayStore || typeof replayStore.claim !== "function") {
    reject(503, "worker_unavailable");
  }
  const timestamp = String(headers["x-bds-timestamp"] || "");
  const delivery = String(headers["x-bds-delivery"] || "");
  const signature = String(headers["x-bds-signature"] || "").toLowerCase();
  if (!/^\d{10}$/.test(timestamp) || !UUID_V4_PATTERN.test(delivery) || !SIGNATURE_PATTERN.test(signature)) {
    reject(401, "invalid_signature");
  }
  if (Math.abs(Math.floor(now / 1000) - Number(timestamp)) > MAX_CLOCK_SKEW_SECONDS) {
    reject(401, "invalid_signature");
  }
  const expected = createHmac("sha256", secret).update(`${timestamp}.`).update(rawBody).digest();
  const supplied = Buffer.from(signature, "hex");
  if (supplied.length !== expected.length || !timingSafeEqual(expected, supplied)) {
    reject(401, "invalid_signature");
  }
  if (!(await replayStore.claim(delivery, Number(timestamp)))) {
    reject(409, "replayed_delivery");
  }
  let decoded;
  try {
    decoded = JSON.parse(rawBody.toString("utf8"));
  } catch {
    reject(400, "invalid_payload");
  }
  return validateRenderPayload(decoded, callbackAllowedHost);
}

export class FileStateStore {
  constructor(stateDirectory) {
    if (typeof stateDirectory !== "string" || !isAbsolute(stateDirectory)) {
      throw new Error("BDS_VIDEO_WORKER_STATE_DIR must be an absolute path");
    }
    this.root = resolve(stateDirectory);
    this.deliveriesDirectory = join(this.root, "deliveries");
    this.jobsDirectory = join(this.root, "jobs");
    for (const directory of [this.root, this.deliveriesDirectory, this.jobsDirectory]) {
      mkdirSync(directory, { recursive: true, mode: 0o700 });
    }
  }

  async claim(delivery, timestamp) {
    if (!UUID_V4_PATTERN.test(delivery) || !boundedInteger(timestamp, 1, 9_999_999_999)) {
      return false;
    }
    const recordPath = join(this.deliveriesDirectory, `${delivery}.json`);
    try {
      writeFileSync(recordPath, JSON.stringify({ delivery, timestamp }), { encoding: "utf8", flag: "wx", mode: 0o600 });
      return true;
    } catch (error) {
      if (error && error.code === "EEXIST") {
        return false;
      }
      throw error;
    }
  }

  pruneDeliveries(now = Date.now(), maxEntries = 1_000) {
    const cutoff = now - 24 * 60 * 60 * 1_000;
    let scanned = 0;
    let removed = 0;
    const directory = opendirSync(this.deliveriesDirectory);
    try {
      let entry;
      while (scanned < maxEntries && (entry = directory.readSync()) !== null) {
        if (!entry.isFile() || !entry.name.endsWith(".json") || !UUID_V4_PATTERN.test(entry.name.slice(0, -5))) continue;
        scanned += 1;
        const recordPath = join(this.deliveriesDirectory, entry.name);
        try {
          const stat = lstatSync(recordPath);
          if (stat.isFile() && !stat.isSymbolicLink() && stat.mtimeMs < cutoff) {
            unlinkSync(recordPath);
            removed += 1;
          }
        } catch (error) {
          if (!error || error.code !== "ENOENT") throw error;
        }
      }
    } finally {
      directory.closeSync();
    }
    return { scanned, removed };
  }

  getOrCreateJob(payload) {
    if (!payload || !UUID_PATTERN.test(payload.jobId)) {
      throw new Error("validated jobId required");
    }
    const jobPath = join(this.jobsDirectory, `${payload.jobId}.json`);
    const payloadHash = createHash("sha256").update(JSON.stringify(payload)).digest("hex");
    const readExisting = () => {
      const record = JSON.parse(readFileSync(jobPath, "utf8"));
      if (!isPlainObject(record) || !PROVIDER_JOB_PATTERN.test(String(record.providerJobId || ""))
          || !SHA256_PATTERN.test(String(record.payloadHash || ""))) {
        throw new Error("invalid stored job record");
      }
      if (!timingSafeEqual(Buffer.from(record.payloadHash, "hex"), Buffer.from(payloadHash, "hex"))) {
        throw new WorkerError(409, "job_payload_mismatch");
      }
      return { providerJobId: record.providerJobId, created: false };
    };
    try {
      return readExisting();
    } catch (error) {
      if (!error || error.code !== "ENOENT") {
        throw error;
      }
    }
    const providerJobId = `bds_${randomUUID().replaceAll("-", "")}`;
    const record = { providerJobId, payloadHash, status: "queued", queuedAt: new Date().toISOString(), payload };
    const serialized = JSON.stringify(record);
    try {
      writeFileSync(jobPath, serialized, { encoding: "utf8", flag: "wx", mode: 0o600 });
      return { providerJobId, created: true };
    } catch (error) {
      if (error && error.code === "EEXIST") {
        return readExisting();
      }
      throw error;
    }
  }
}
