#!/usr/bin/env bash
# =============================================================================
# Actualiza producción (docs/20_DESPLIEGUE.md).
#
#   ./deploy-production.sh              # código nuevo, sin migraciones
#   ./deploy-production.sh --migrate    # código nuevo + php artisan migrate --force
#   ./deploy-production.sh --branch=main
#
# La primera instalación (APP_KEY, migrate --seed) está en la documentación.
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")"

BRANCH="main"
RUN_MIGRATIONS="false"
for arg in "$@"; do
  case "$arg" in
    --migrate|--migrate=true) RUN_MIGRATIONS="true" ;;
    --branch=*)               BRANCH="${arg#--branch=}" ;;
    *) echo "Opción desconocida: $arg"; exit 1 ;;
  esac
done

DC="docker compose -f docker-compose.prod.yml"
LOGFILE="deploy.log"
log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOGFILE"; }

[ -f .env ] || { echo "Falta el .env (cp .env.production.example .env)"; exit 1; }
WEB_PORT="$(grep -E '^WEB_PORT=' .env | cut -d= -f2 | tr -d '"' || true)"
WEB_PORT="${WEB_PORT:-8080}"

# Si algo falla a mitad de camino, el sitio no se queda en mantenimiento.
on_error() {
  log "ERROR en la línea $1. Saliendo del modo mantenimiento."
  $DC exec -T app php artisan up < /dev/null >> "$LOGFILE" 2>&1 || true
}
trap 'on_error $LINENO' ERR

log "==============================================="
log "DEPLOY · rama: $BRANCH · migraciones: $RUN_MIGRATIONS"
log "==============================================="

if $DC ps --status running --services 2>/dev/null | grep -qx app; then
  log "Modo mantenimiento"
  $DC exec -T app php artisan down --retry=60 < /dev/null >> "$LOGFILE" 2>&1 || true
fi

log "Actualizando el código"
git fetch origin >> "$LOGFILE" 2>&1
git checkout "$BRANCH" >> "$LOGFILE" 2>&1
git pull --ff-only origin "$BRANCH" >> "$LOGFILE" 2>&1

log "Construyendo las imágenes (app y web)"
$DC build app web >> "$LOGFILE" 2>&1

log "Verificando la imagen"
# docker run directo, sin TTY ni stdin y con límite de tiempo (en Kiasmer,
# "compose run" se quedaba colgado aquí y dejaba el sitio en mantenimiento).
timeout 120 docker run --rm --entrypoint php diagnostico-app:latest artisan --version < /dev/null >> "$LOGFILE" 2>&1

if [ "$RUN_MIGRATIONS" = "true" ]; then
  log "Ejecutando migraciones"
  $DC up -d pgsql >> "$LOGFILE" 2>&1
  $DC run --rm -T migrate < /dev/null >> "$LOGFILE" 2>&1
fi

log "Recreando los contenedores"
$DC up -d --no-build --remove-orphans >> "$LOGFILE" 2>&1

log "Esperando a que app y web estén sanos"
for service in app web; do
  for _ in $(seq 1 30); do
    id="$($DC ps -q "$service")"
    status="$(docker inspect -f '{{.State.Health.Status}}' "$id" 2>/dev/null || echo unknown)"
    [ "$status" = "healthy" ] && break
    sleep 5
  done
  log "  $service: $status"
done

log "Saliendo del modo mantenimiento"
$DC exec -T app php artisan up < /dev/null >> "$LOGFILE" 2>&1 || true

log "Eliminando imágenes sin usar (más de 24 h)"
docker image prune -f --filter "until=24h" >> "$LOGFILE" 2>&1 || true

if curl -fsS "http://127.0.0.1:${WEB_PORT}/up" > /dev/null; then
  log "Health check OK (http://127.0.0.1:${WEB_PORT}/up)"
else
  log "AVISO: el health check falló"
  exit 1
fi

log "DEPLOY TERMINADO"
