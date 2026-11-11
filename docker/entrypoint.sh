#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "SignalGrid needs APP_KEY. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

if [ "${DB_CONNECTION:-}" != "pgsql" ] || [ -z "${DB_URL:-}" ]; then
    echo "SignalGrid on Vercel needs DB_CONNECTION=pgsql and DB_URL." >&2
    exit 1
fi

php artisan package:discover --ansi --no-interaction
php artisan migrate --force --no-interaction
php artisan signalgrid:ensure-workspace --no-interaction

exec frankenphp run --config /etc/frankenphp/Caddyfile
