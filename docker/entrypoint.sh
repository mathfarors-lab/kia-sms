#!/bin/sh
set -e

: "${PORT:=8080}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-enabled/default

cd /var/www/html

php artisan config:clear

# Retry migrations — the managed database can take a few seconds to accept
# connections right after the platform (re)starts it alongside this container.
attempt=0
until php artisan migrate --force; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 10 ]; then
        echo "Database did not become ready after 10 attempts — exiting."
        exit 1
    fi
    echo "Database not ready yet (attempt $attempt/10) — retrying in 3s..."
    sleep 3
done

php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
