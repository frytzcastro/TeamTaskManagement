#!/bin/sh
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

# Ensure runtime directories exist (empty EFS or fresh bind mount).
mkdir -p \
  storage/app/public \
  storage/app/private \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Prefer writable ownership for php-fpm / queue (www-data). On EFS this may
# require host-side uid 33 alignment; failures are non-fatal if already writable.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force || true


if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Running migrations..."
  php artisan migrate --force
fi

if [ "${APP_ENV:-local}" = "production" ] && [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
