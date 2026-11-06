#!/bin/sh
# Arranque de los contenedores de producción (app, queue, scheduler).
# La configuración llega por env_file (.env del servidor): se cachea al iniciar
# para que PHP-FPM la lea sin depender de las variables de entorno.
set -e

php artisan storage:link --force --no-interaction || echo "AVISO: storage:link falló"

php artisan config:clear --no-interaction
php artisan optimize --no-interaction

exec "$@"
