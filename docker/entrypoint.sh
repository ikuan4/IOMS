#!/bin/sh
set -eu

# Run only pending migrations (safe to run on every container start)
echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] entrypoint: running 'php artisan migrate --force'" >&2
php artisan migrate --force

# Hand off to the actual container command (e.g. the web server)
exec "$@"
