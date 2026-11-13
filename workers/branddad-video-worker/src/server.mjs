import { createServer } from "node:http";
import { FileStateStore, MAX_BODY_BYTES, WorkerError, verifyDispatch } from "./worker-core.mjs";

const host = process.env.BDS_VIDEO_WORKER_HOST || "127.0.0.1";
const port = Number(process.env.BDS_VIDEO_WORKER_PORT || 8787);
const secret = process.env.BDS_VIDEO_RENDER_DISPATCH_SECRET || "";
const callbackAllowedHost = process.env.BDS_VIDEO_CALLBACK_ALLOWED_HOST || "";
const stateDirectory = process.env.BDS_VIDEO_WORKER_STATE_DIR || "";
const allowPublicBind = process.env.BDS_VIDEO_ALLOW_PUBLIC_BIND === "true";
const loopbackHosts = new Set(["127.0.0.1", "::1", "localhost"]);

if (!Number.isInteger(port) || port < 1 || port > 65_535 || secret.length < 32 || !callbackAllowedHost || !stateDirectory
    || (!loopbackHosts.has(host) && !allowPublicBind)) {
  throw new Error("Video worker configuration is incomplete");
}

const store = new FileStateStore(stateDirectory);
store.pruneDeliveries();
const rateBuckets = new Map();

function send(response, status, body) {
  const encoded = JSON.stringify(body);
  response.writeHead(status, {
    "Content-Type": "application/json; charset=utf-8",
    "Content-Length": Buffer.byteLength(encoded),
    "Cache-Control": "no-store",
    "X-Content-Type-Options": "nosniff",
  });
  response.end(encoded);
}

function takeRateLimit(key, now = Date.now()) {
  const windowStart = Math.floor(now / 60_000) * 60_000;
  const current = rateBuckets.get(key);
  if (!current || current.windowStart !== windowStart) {
    if (!current && rateBuckets.size >= 10_000) return false;
    rateBuckets.set(key, { windowStart, count: 1 });
    return true;
  }
  current.count += 1;
  return current.count <= 60;
}

function readBody(request) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let bytes = 0;
    let tooLarge = false;
    request.on("data", (chunk) => {
      bytes += chunk.length;
      if (bytes > MAX_BODY_BYTES) {
        tooLarge = true;
        chunks.length = 0;
        return;
      }
      if (!tooLarge) chunks.push(chunk);
    });
    request.on("end", () => tooLarge
      ? reject(new WorkerError(413, "payload_too_large"))
      : resolve(Buffer.concat(chunks)));
    request.on("error", () => reject(new WorkerError(400, "invalid_request")));
  });
}

const server = createServer(async (request, response) => {
  const requestUrl = new URL(request.url || "/", "http://worker.invalid");
  const remoteAddress = request.socket.remoteAddress || "unknown";
  if (request.method === "GET" && requestUrl.pathname === "/healthz" && requestUrl.search === "") {
    return send(response, 200, { status: "ok" });
  }
  if (request.method !== "POST" || requestUrl.pathname !== "/v1/render" || requestUrl.search !== "") {
    return send(response, 404, { error: "not_found" });
  }
  if (!takeRateLimit(remoteAddress)) {
    return send(response, 429, { error: "rate_limited" });
  }
  if (!String(request.headers["content-type"] || "").toLowerCase().startsWith("application/json")) {
    return send(response, 415, { error: "unsupported_media_type" });
  }
  const declaredLength = Number(request.headers["content-length"] || 0);
  if (!Number.isFinite(declaredLength) || declaredLength < 0 || declaredLength > MAX_BODY_BYTES) {
    request.resume();
    return send(response, 413, { error: "payload_too_large" });
  }
  try {
    const rawBody = await readBody(request);
    const payload = await verifyDispatch({
      rawBody,
      headers: request.headers,
      secret,
      callbackAllowedHost,
      replayStore: store,
    });
    const job = store.getOrCreateJob(payload);
    console.log(JSON.stringify({ event: job.created ? "render_queued" : "render_idempotent", jobId: payload.jobId, providerJobId: job.providerJobId }));
    return send(response, 202, { providerJobId: job.providerJobId });
  } catch (error) {
    const status = error instanceof WorkerError ? error.status : 500;
    const code = error instanceof WorkerError ? error.code : "internal_error";
    console.warn(JSON.stringify({ event: "render_rejected", code, status }));
    return send(response, status, { error: code });
  }
});

server.requestTimeout = 10_000;
server.headersTimeout = 5_000;
server.keepAliveTimeout = 5_000;
server.maxHeadersCount = 32;
server.maxRequestsPerSocket = 100;

setInterval(() => {
  const oldestAllowedWindow = Math.floor(Date.now() / 60_000) * 60_000 - 60_000;
  for (const [key, bucket] of rateBuckets) {
    if (bucket.windowStart < oldestAllowedWindow) rateBuckets.delete(key);
  }
}, 60_000).unref();

setInterval(() => {
  try {
    store.pruneDeliveries();
  } catch {
    console.warn(JSON.stringify({ event: "replay_cleanup_failed" }));
  }
}, 10 * 60_000).unref();

server.listen(port, host, () => {
  console.log(JSON.stringify({ event: "worker_listening", host, port }));
});
