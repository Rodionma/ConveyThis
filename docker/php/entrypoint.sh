#!/bin/sh
set -e

READY_FLAG=storage/framework/.docker-ready

if [ "$1" = "php-fpm" ]; then
    rm -f "$READY_FLAG"

    [ -f .env ] || cp .env.example .env

    if [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

    echo "Waiting for MySQL and running migrations..."
    until php artisan migrate --force; do
        sleep 3
    done
    php artisan config:clear

    chown -R www-data:www-data storage bootstrap/cache
    touch "$READY_FLAG"
else
    echo "Waiting for the app container to finish setup..."
    until [ -f "$READY_FLAG" ]; do sleep 2; done
fi

exec "$@"
