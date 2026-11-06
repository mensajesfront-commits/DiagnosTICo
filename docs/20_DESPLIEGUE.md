# Despliegue

**Estado:** COMPLETO (T-155). Cómo se entrega y cómo se despliega. El sistema **se va a desplegar** (DEC-006 cambió el 4 de noviembre). Se publica en **https://diagnostico.captter.com** con Docker Compose (DEC-024).

## 1. Qué se entrega

- El repositorio de GitHub con la rama `main` al día y las pruebas en verde.
- La documentación de `docs/` y el `README.md` con la instalación paso a paso.
- El archivo `.env.example` con todas las variables y solo marcadores en lugar de claves.

No se entregan claves, cuentas de prueba ni una base de datos con datos reales.

## 2. Flujo de ramas hasta la entrega

```
rama de trabajo (claude/…, feature/…) ──PR + revisión──▶ develop ──PR de cierre──▶ main
```

1. Cada cambio entra a `develop` por *pull request*, con la revisión del otro desarrollador y CI en verde (`28_CONVENCIONES_DESARROLLO.md`).
2. Al cerrar el proyecto, un *pull request* de `develop` a `main` deja la versión de entrega.
3. Se marca la versión con una etiqueta (`v1.0.0`) y se anota en `24_CAMBIOS_Y_VERSIONES.md`.

**Hecho para la entrega:** PR #11 a #15 a `develop`, PR #13 a `main` y la etiqueta `v1.0.0` sobre `main` (commit `2838f4a`, 5 de noviembre de 2026).

## 3. Desplegar en el servidor (Docker)

Requisitos del servidor: `16_INFRAESTRUCTURA.md`. Decisión: DEC-024.

```
Internet ──https──▶ Apache del servidor (SSL de diagnostico.captter.com)
                      │ ProxyPass a http://127.0.0.1:8200
                      ▼
┌──────────────── docker-compose.prod.yml (red "interna") ────────────────┐
│ nginx :80 ──fastcgi──▶ app (PHP 8.4-FPM :9000)                          │
│ queue (queue:work) · scheduler (schedule:work)   ← misma imagen que app │
│ postgres (18) · redis (caché y cola)            ← sin puertos al exterior│
└──────────────────────────────────────────────────────────────────────────┘
```

| Archivo | Para qué |
|---|---|
| `docker-compose.prod.yml` | Los seis servicios, los volúmenes (`storage`, `postgres`, `redis`) y el puerto `127.0.0.1:${NGINX_PORT:-8200}:80` |
| `docker/php/Dockerfile.prod` | Imagen de `app`, `queue` y `scheduler`: PHP 8.4-FPM, extensiones, Node 24, Chromium, `composer install --no-dev` y `npm run build` |
| `docker/php/php.ini`, `docker/php/www.conf` | OPcache, límites de subida y el pool de php-fpm (`clear_env = no` para que PHP vea las variables) |
| `docker/php/entrypoint.sh` | Al arrancar corre `php artisan optimize` con las variables del servidor. No corre migraciones |
| `docker/nginx/Dockerfile.prod`, `docker/nginx/default.conf` | Nginx con `public/` copiado de la imagen de la aplicación |
| `.env.production.example` | Plantilla del `.env` del servidor, solo con marcadores |
| `.dockerignore` | Deja fuera de la imagen el `.env`, `vendor`, `node_modules`, `tests` y `docs` |

Necesita Docker con Compose 2.17 o más reciente (Nginx toma los archivos de la imagen `app` con `additional_contexts`).

### Primer despliegue

```bash
git clone https://github.com/mensajesfront-commits/DiagnosTICo.git
cd DiagnosTICo
git checkout v1.0.0                          # o main

cp .env.production.example .env
chmod 600 .env
nano .env                                    # llena todo lo que está entre < >

# Contraseñas de PostgreSQL y Redis (una para cada una):
openssl rand -hex 24

# APP_KEY: se genera una vez y se copia en el .env (APP_KEY=base64:...)
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan key:generate --show

docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --seed --force
docker compose -f docker-compose.prod.yml ps   # todo en running / healthy
```

`db:seed` crea los roles, los catálogos y la cuenta principal del Administrador (`ADMIN_EMAIL`); a ese correo le llega "Crear mi contraseña". Si no llega: `docker compose -f docker-compose.prod.yml exec app php artisan admin:invitar`.

### Apache delante

Apache ya tiene el SSL de `diagnostico.captter.com` y apunta al puerto 8200. El VirtualHost `:443` debe tener, como mínimo:

```apache
ProxyPreserveHost On
RequestHeader set X-Forwarded-Proto "https"
RequestHeader set X-Forwarded-Port "443"
ProxyPass        / http://127.0.0.1:8200/
ProxyPassReverse / http://127.0.0.1:8200/
```

Sin `X-Forwarded-Proto`, Laravel cree que la petición llegó por http: las cookies seguras y la cabecera HSTS dependen de eso. Requiere `mod_proxy`, `mod_proxy_http` y `mod_headers`. Laravel confía en ese proxy (`trustProxies` en `bootstrap/app.php`) y, como `APP_URL` empieza por `https://`, arma todos los enlaces en https.

### Variables del `.env` del servidor

La plantilla es `.env.production.example`. Las claves reales se escriben **solo** en el `.env` del servidor: nunca en el repositorio, en un chat ni en un correo.

| Variable | Valor |
|---|---|
| `APP_ENV` / `APP_DEBUG` / `INERTIA_DEVTOOLS_ENABLED` | `production` / `false` / `false` (ERR-06/07) |
| `APP_URL` | `https://diagnostico.captter.com` |
| `APP_KEY` | La que da `key:generate --show`. No se cambia después: cifra las sesiones |
| `NGINX_PORT` | `8200` |
| `DB_HOST` / `DB_PASSWORD` | `postgres` / una clave aleatoria. PostgreSQL crea la base con ella la primera vez |
| `REDIS_HOST` / `REDIS_PASSWORD` | `redis` / una clave aleatoria |
| `CACHE_STORE` / `QUEUE_CONNECTION` | `redis` / `redis` |
| `SESSION_DRIVER` | `database` (el sistema borra sesiones de la tabla `sessions`) |
| `SESSION_SECURE_COOKIE` / `SESSION_ENCRYPT` | `true` / `true` |
| `OPENAI_API_KEY` / `OPENAI_MODEL` | La clave de NuevasTIC / `gpt-4.1-mini` |
| `CAPTCHA_ACTIVO`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | `true` y las llaves reales; sin ellas nadie se registra |
| `MAIL_*` | El SMTP real |
| `ADMIN_EMAIL` | La cuenta principal del Administrador (la elige NuevasTIC) |
| `SOPORTE_EMAIL` | `contacto@captter.com` |
| `AUTH_PASSWORD_RESET_EXPIRE` | `10` |
| `LARAVEL_PDF_CHROME_PATH` / `LARAVEL_PDF_NO_SANDBOX` | `/usr/bin/chromium` / `true` |

Otras reglas:

- **Rutas de prueba técnica:** `/prueba-tecnica/*` solo existen con `APP_ENV=local`; en producción no se cargan.
- **Antes de publicar:** `composer audit` y `npm audit` sin alertas altas (en `v1.0.0`, los dos en 0).
- Si se cambia el `.env`: `docker compose -f docker-compose.prod.yml up -d` (recrea los contenedores y vuelve a cachear la configuración).

### Actualizar a una versión nueva

```bash
git pull                                      # o git checkout <etiqueta nueva>
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml exec app php artisan down
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan up
```

`up -d` cambia los contenedores por los de la imagen nueva; la cola termina el trabajo en curso antes de apagarse (`stop_grace_period`). Los PDF, logos, fotos y logs quedan en el volumen `storage`.

### Comandos útiles

```bash
docker compose -f docker-compose.prod.yml logs -f queue        # análisis de la IA, PDF y correos
docker compose -f docker-compose.prod.yml exec app php artisan prueba:ia   # una llamada real a OpenAI
docker compose -f docker-compose.prod.yml exec app php artisan queue:failed
docker compose -f docker-compose.prod.yml exec postgres pg_dump -U diagnostico diagnostico > respaldo.sql
```

### Sin Docker (alternativa)

Si algún día se instala directo en un servidor con PHP 8.4, Node 24 y Chromium:

```bash
cp .env.production.example .env   # con DB_HOST, REDIS_HOST y LARAVEL_PDF_CHROME_PATH del servidor
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
npm ci && npm run build
php artisan optimize
```

Con Supervisor para `php artisan queue:work --tries=3 --timeout=120` y el cron `* * * * * php artisan schedule:run`.

## 4. Despliegue (decisión del 4 de noviembre de 2026)

El sistema **se va a desplegar** (DEC-006 cambió). La sección 3 tiene los pasos.

- Dominio: **diagnostico.captter.com**. Apache del servidor tiene el certificado SSL y reenvía a `127.0.0.1:8200` (5 de noviembre).
- Archivos de producción: `docker-compose.prod.yml`, `docker/php/Dockerfile.prod` y `docker/nginx/Dockerfile.prod` (DEC-024).
- **[INFORMACIÓN PENDIENTE]** Quién mantiene el sistema (`21_MANTENIMIENTO.md`).
- OpenAI lo paga NuevasTIC, con gpt-4.1-mini.
- Las llaves reales de Cloudflare Turnstile ya existen (4 de noviembre). Van solo en el `.env` del servidor (`TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY`), nunca en el repositorio.
- Despliegue automático desde GitHub Actions: **NO APLICA** por ahora. CI solo corre las pruebas.
