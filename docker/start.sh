#!/bin/sh
set -eu

PORT="${PORT:-10000}"
export PORT

envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

php artisan storage:link --force || true
if [ "${RUN_MIGRATIONS:-true}" != "false" ]; then
    php artisan migrate --force
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisord.conf