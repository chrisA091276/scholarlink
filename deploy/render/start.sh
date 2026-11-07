#!/bin/sh
set -eu

cd /var/www/html
mode="${1:-web}"
case "$mode" in
    web|worker|schedule) ;;
    *) echo 'Usage: start.sh [web|worker|schedule]' >&2; exit 1 ;;
esac

if [ -z "${APP_KEY:-}" ]; then
    echo 'Set APP_KEY in Render Environment before starting the service.' >&2
    exit 1
fi

export PORT="${PORT:-10000}"
case "$PORT" in
    ''|*[!0-9]*) echo 'PORT must be a number.' >&2; exit 1 ;;
esac
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo 'PORT must be between 1 and 65535.' >&2
    exit 1
fi

# Persistent disks mount at runtime, so their directories must be created here.
mkdir -p storage/app/private storage/app/public storage/fonts \
    storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache

php artisan config:clear --no-interaction

# Only the web service may apply migrations. Workers/cron never race it.
if [ "$mode" = web ] && [ "${RUN_MIGRATIONS:-false}" = true ]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:cache --no-interaction
php artisan view:cache --no-interaction
if [ ! -e public/storage ]; then
    php artisan storage:link --no-interaction
fi
chown -R www-data:www-data storage bootstrap/cache

case "$mode" in
    web) exec apache2-foreground ;;
    worker) exec php artisan queue:work --sleep=3 --tries=3 --timeout=60 ;;
    schedule) exec php artisan schedule:run --no-interaction ;;
esac
