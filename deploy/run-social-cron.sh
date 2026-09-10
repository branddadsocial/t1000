#!/bin/sh
LOCK=/home/sites/41a/c/c0a8249376/recovery-backups/20260909-codex/social-cron.lock
WP_ROOT=/home/sites/41a/c/c0a8249376/public_html/branddad.social

cd "$WP_ROOT" || exit 1

exec /usr/bin/flock -n "$LOCK" /usr/bin/timeout 240 /usr/local/bin/wp action-scheduler run --batch-size=5 --batches=1 --quiet >/dev/null 2>&1
