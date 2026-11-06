#!/bin/sh
# Arranque de los contenedores app, queue y scheduler.
# Cachea la configuración, las rutas, las vistas y los eventos con las
# variables del .env del servidor. Las migraciones NO corren solas: se lanzan a
# mano (docs/20_DESPLIEGUE.md) para no tocar la base sin querer.
set -e

if [ -z "$APP_KEY" ]; then
    echo "Falta APP_KEY en el .env. Genérala con:"
    echo "  docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show"
fi

if [ "${OPTIMIZAR_AL_ARRANCAR:-true}" = "true" ] && [ -n "$APP_KEY" ]; then
    php artisan optimize --no-interaction || echo "Aviso: php artisan optimize falló; se sigue sin caché."
fi

exec "$@"
