import assert from "node:assert/strict";
import { randomUUID } from "node:crypto";
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import test from "node:test";
import { createMarkyReviewDraft, MarkyDraftError } from "../src/marky-draft-adapter.mjs";

function fixture() {
  const root = mkdtempSync(join(tmpdir(), "bds-marky-draft-"));
  const outputRoot = join(root, "output");
  mkdirSync(outputRoot, { recursive: true });
  const mediaPath = join(outputRoot, "approved.mp4");
  writeFileSync(mediaPath, Buffer.from("small-test-video"));
  const workspaceId = randomUUID();
  const targets = [
    { integrationId: randomUUID(), platform: "instagram" },
    { integrationId: randomUUID(), platform: "facebook" },
  ];
  const apiKey = ["mk", "live", "unit", "test", "key", "material", "not", "production"].join("_");
  return {
    root,
    config: { apiKey, workspaceId, outputRoot, destinationUrl: "https://branddad.social/", targets },
    job: {
      jobId: randomUUID(),
      mediaPath,
      caption: "Fix the path. Then scale the content.",
      platformCaptions: {
        instagram: "Fix the path. Then scale the content. #BrandDad",
        facebook: "Your marketing should lead somewhere useful. Explore BrandDad Social.",
      },
    },
  };
}

function jsonResponse(body, status = 201) {
  return new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } });
}

test("uploads an approved MP4 and creates a NEW review draft only", async () => {
  const data = fixture();
  const calls = [];
  const postId = randomUUID();
  const fetchImpl = async (url, options) => {
    calls.push({ url, options });
    return calls.length === 1
      ? jsonResponse({ original_url: "https://cdn.example.test/approved.mp4" })
      : jsonResponse({ id: postId, status: "NEW" });
  };
  try {
    const result = await createMarkyReviewDraft({ config: data.config, job: data.job, fetchImpl });
    assert.deepEqual(result, { postId, mediaUrl: "https://cdn.example.test/approved.mp4", status: "NEW" });
    assert.equal(calls.length, 2);
    assert.match(calls[0].url, /^https:\/\/api\.mymarky\.ai\/api\/businesses\/[0-9a-f-]+\/media$/);
    assert.equal(calls[0].options.redirect, "error");
    const postBody = JSON.parse(calls[1].options.body);
    assert.equal(postBody.status, "NEW");
    assert.equal(postBody.scheduled_publish_time, undefined);
    assert.equal(postBody.metadata.approval_mode, "required");
    assert.deepEqual(postBody.restrict_publish_to_integration_ids, data.config.targets.map((target) => target.integrationId));
    assert.equal(postBody.platform_overrides.length, 2);
  } finally {
    rmSync(data.root, { recursive: true, force: true });
  }
});

test("rejects a media path outside the configured output root", async () => {
  const data = fixture();
  const outsidePath = join(data.root, "outside.mp4");
  writeFileSync(outsidePath, Buffer.from("outside"));
  try {
    await assert.rejects(
      () => createMarkyReviewDraft({ config: data.config, job: { ...data.job, mediaPath: outsidePath }, fetchImpl: async () => jsonResponse({}) }),
      (error) => error instanceof MarkyDraftError && error.code === "invalid_video_path",
    );
  } finally {
    rmSync(data.root, { recursive: true, force: true });
  }
});

test("rejects invalid targets before reading or sending media", async () => {
  const data = fixture();
  try {
    await assert.rejects(
      () => createMarkyReviewDraft({ config: { ...data.config, targets: [{ integrationId: "attacker", platform: "instagram" }] }, job: data.job }),
      (error) => error instanceof MarkyDraftError && error.code === "draft_adapter_unavailable",
    );
  } finally {
    rmSync(data.root, { recursive: true, force: true });
  }
});

test("rejects unknown manifest fields before reading or sending media", async () => {
  const data = fixture();
  try {
    await assert.rejects(
      () => createMarkyReviewDraft({ config: data.config, job: { ...data.job, publishNow: true } }),
      (error) => error instanceof MarkyDraftError && error.code === "invalid_draft_job",
    );
  } finally {
    rmSync(data.root, { recursive: true, force: true });
  }
});
