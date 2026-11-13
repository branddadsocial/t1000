# BrandDad video render worker (staged)

This package is the private receiving side of the signed WordPress render adapter. It verifies and queues data-only render requests; it does not execute FFmpeg, call a paid provider, publish videos, or expose a public service by itself.

The package also contains a staged Marky adapter that uploads an already-approved MP4 and creates a private `NEW` review draft for server-configured BrandDad Social accounts. It never schedules or publishes a post.

## Security contract

- One endpoint: `POST /v1/render`.
- HMAC-SHA256 over `timestamp.rawBody`, five-minute clock window, delivery UUID replay rejection, and job UUID idempotency.
- JSON-only request with a 100 KB cap, exact field allowlists, bounded values, one fixed template, and an exact HTTPS WordPress callback host/path.
- Local replay and queue filenames are created only from validated UUIDs. Each job record is the single canonical queue item, created atomically with mode `0600`.
- No arbitrary URL fetching, command execution, file uploads, provider credentials, or renderer process runs in the HTTP server.
- The Marky adapter uses one fixed API origin/version, exact server-configured workspace/integration IDs, a 50 MB MP4 cap, strict response bounds, and `NEW` review status only. The API key is injected by the worker secret manager and is never returned or logged.
- Default bind is `127.0.0.1`. Production requires a TLS reverse proxy/WAF, edge rate limits, network policy, a secret manager, non-root runtime, a read-only root filesystem, explicit state mount, and allowlisted egress.

## Required runtime configuration

Inject these at runtime from the deployment secret/configuration system; never commit values:

- `BDS_VIDEO_RENDER_DISPATCH_SECRET` — dedicated 32+ character dispatch secret shared only with WordPress.
- `BDS_VIDEO_CALLBACK_ALLOWED_HOST` — exact WordPress hostname, without scheme or path.
- `BDS_VIDEO_WORKER_STATE_DIR` — absolute path to an explicit writable state/queue mount.
- `BDS_VIDEO_WORKER_HOST` — optional; defaults to `127.0.0.1`.
- `BDS_VIDEO_WORKER_PORT` — optional; defaults to `8787`.
- `BDS_VIDEO_ALLOW_PUBLIC_BIND` — keep unset. A production container may set exactly `true` only when a TLS reverse proxy, ingress allowlist, and edge rate limit are already in place.

## Local verification

Use a supported Node 20 or 22 runtime:

```text
npm run check
npm test
```

Do not bind the staged server publicly. A separate isolated renderer/queue consumer and signed callback sender are the next implementation layer.
