#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

run_as_app_user() {
    if [ "$(id -u)" = "0" ]; then
        gosu www-data env HOME=/tmp/composer "$@"
    else
        "$@"
    fi
}

mkdir -p \
    storage/app \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /tmp/composer

if [ "$(id -u)" = "0" ]; then
    [ -f .env ] && chown www-data:www-data .env || true
    chown -R www-data:www-data storage bootstrap/cache /tmp/composer || true
    chmod -R ug+rwX storage bootstrap/cache || true
fi

if [ "${COMPOSER_INSTALL:-true}" = "true" ] && [ -f composer.json ]; then
    echo "Installing Composer dependencies..."

    run_as_app_user git config --global --add safe.directory /var/www/html || true

    run_as_app_user env XDEBUG_MODE=off composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -f artisan ]; then
    run_as_app_user php artisan package:discover --ansi || true

    if [ ! -e public/storage ] && [ ! -L public/storage ]; then
        run_as_app_user php artisan storage:link || true
    fi
fi

exec docker-php-entrypoint "$@"
