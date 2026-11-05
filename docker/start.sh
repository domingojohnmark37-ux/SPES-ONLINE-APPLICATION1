#!/bin/sh
set -eu

PORT="${PORT:-3000}"
export PORT

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

php artisan storage:link --force
migration_attempt=1
until php artisan migrate --force; do
    if [ "$migration_attempt" -ge 10 ]; then
        echo "Database migrations failed after 10 attempts." >&2
        exit 1
    fi

    echo "Database is not ready for migrations (attempt $migration_attempt/10); retrying in 5 seconds." >&2
    migration_attempt=$((migration_attempt + 1))
    sleep 5
done
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

exec supervisord -c /etc/supervisord.conf