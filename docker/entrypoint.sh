#!/bin/sh
set -e
cd /var/www/html

# Solo el contenedor principal prepara el entorno (evita carreras con el scheduler)
if [ "$1" = "php-fpm" ]; then
    [ -d vendor ] || composer install --no-interaction --optimize-autoloader
    [ -f .env ] || cp .env.example .env
    grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
    chown -R www-data:www-data storage bootstrap/cache
    php artisan migrate --force
fi

exec "$@"
