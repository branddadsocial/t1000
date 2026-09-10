#!/bin/sh
WORKER=/home/sites/41a/c/c0a8249376/recovery-backups/20260909-codex/run-social-cron.sh

/usr/bin/nohup /usr/bin/sh "$WORKER" >/dev/null 2>&1 &
exit 0
