import assert from "node:assert/strict";
import { createHmac, randomUUID } from "node:crypto";
import { existsSync, mkdtempSync, rmSync, utimesSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import test from "node:test";
import { FileStateStore, WorkerError, verifyDispatch } from "../src/worker-core.mjs";

const signingKey = ["unit", "test", "dispatch", "key", "material", "not", "production"].join("-");
const callbackAllowedHost = "social.example.test";

class MemoryReplayStore {
  constructor() { this.seen = new Set(); }
  async claim(delivery) {
    if (this.seen.has(delivery)) return false;
    this.seen.add(delivery);
    return true;
  }
}

function payload(overrides = {}) {
  return {
    jobId: randomUUID(),
    projectId: randomUUID(),
    projectVersion: 1,
    template: "branddad_motion_v1",
    durationSeconds: 15,
    aspectRatio: "9:16",
    language: "en",
    sourceSummary: "A factual BrandDad video.",
    scenes: [{ durationSeconds: 15, onScreenText: "BrandDad", narration: "", visualDirection: "Navy background" }],
    callbackUrl: `https://${callbackAllowedHost}/wp-json/bds-video/v1/webhooks/render`,
    maxProviderCost: 0.25,
    reservedCredits: 1,
    ...overrides,
  };
}

function signedRequest(value, { timestamp = Math.floor(Date.now() / 1000), delivery = randomUUID() } = {}) {
  const rawBody = Buffer.from(JSON.stringify(value));
  const signature = createHmac("sha256", signingKey).update(`${timestamp}.`).update(rawBody).digest("hex");
  return {
    rawBody,
    headers: { "x-bds-timestamp": String(timestamp), "x-bds-delivery": delivery, "x-bds-signature": signature },
    secret: signingKey,
    callbackAllowedHost,
  };
}

async function expectWorkerError(action, status, code) {
  await assert.rejects(action, (error) => error instanceof WorkerError && error.status === status && error.code === code);
}

test("accepts a correctly signed, bounded payload", async () => {
  const request = signedRequest(payload());
  const result = await verifyDispatch({ ...request, replayStore: new MemoryReplayStore() });
  assert.equal(result.template, "branddad_motion_v1");
});

test("rejects body tampering", async () => {
  const request = signedRequest(payload());
  request.rawBody = Buffer.from(request.rawBody.toString("utf8").replace("BrandDad", "Tampered"));
  await expectWorkerError(() => verifyDispatch({ ...request, replayStore: new MemoryReplayStore() }), 401, "invalid_signature");
});

test("rejects an oversized body before authentication or parsing", async () => {
  const request = signedRequest(payload());
  request.rawBody = Buffer.alloc(100_001, 0x61);
  await expectWorkerError(() => verifyDispatch({ ...request, replayStore: new MemoryReplayStore() }), 413, "payload_too_large");
});

test("rejects stale and replayed deliveries", async () => {
  const replayStore = new MemoryReplayStore();
  const stale = signedRequest(payload(), { timestamp: Math.floor(Date.now() / 1000) - 301 });
  await expectWorkerError(() => verifyDispatch({ ...stale, replayStore }), 401, "invalid_signature");
  const request = signedRequest(payload());
  await verifyDispatch({ ...request, replayStore });
  await expectWorkerError(() => verifyDispatch({ ...request, replayStore }), 409, "replayed_delivery");
});

test("rejects unknown fields, prototype keys, and callback host changes", async () => {
  const unknown = signedRequest(payload({ unexpected: true }));
  await expectWorkerError(() => verifyDispatch({ ...unknown, replayStore: new MemoryReplayStore() }), 400, "invalid_payload");
  const prototypeKey = payload();
  const prototypeBody = JSON.parse(JSON.stringify(prototypeKey));
  Object.defineProperty(prototypeBody, "__proto__", { value: { polluted: true }, enumerable: true });
  const prototypeRequest = signedRequest(prototypeBody);
  await expectWorkerError(() => verifyDispatch({ ...prototypeRequest, replayStore: new MemoryReplayStore() }), 400, "invalid_payload");
  const callback = signedRequest(payload({ callbackUrl: "https://attacker.example/wp-json/bds-video/v1/webhooks/render" }));
  await expectWorkerError(() => verifyDispatch({ ...callback, replayStore: new MemoryReplayStore() }), 400, "invalid_payload");
  assert.equal({}.polluted, undefined);
});

test("filesystem store atomically rejects replay and deduplicates jobs", async () => {
  const directory = mkdtempSync(join(tmpdir(), "bds-video-worker-"));
  try {
    const store = new FileStateStore(directory);
    const delivery = randomUUID();
    assert.equal(await store.claim(delivery, Math.floor(Date.now() / 1000)), true);
    assert.equal(await store.claim(delivery, Math.floor(Date.now() / 1000)), false);
    const deliveryPath = join(store.deliveriesDirectory, `${delivery}.json`);
    const oldTime = new Date(Date.now() - 25 * 60 * 60 * 1_000);
    utimesSync(deliveryPath, oldTime, oldTime);
    assert.deepEqual(store.pruneDeliveries(), { scanned: 1, removed: 1 });
    assert.equal(existsSync(deliveryPath), false);
    const renderPayload = payload();
    const first = store.getOrCreateJob(renderPayload);
    const second = store.getOrCreateJob(renderPayload);
    assert.equal(first.created, true);
    assert.equal(second.created, false);
    assert.equal(second.providerJobId, first.providerJobId);
    assert.throws(
      () => store.getOrCreateJob({ ...renderPayload, sourceSummary: "Conflicting retry" }),
      (error) => error instanceof WorkerError && error.status === 409 && error.code === "job_payload_mismatch",
    );
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});
