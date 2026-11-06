# Despliegue

**Estado:** el sistema **se despliega** (DEC-006 cambió el 4 de noviembre de 2026; antes solo se entregaba el repositorio). La forma recomendada es con Docker (sección 3), con la misma estructura de producción de Kiasmer: imágenes inmutables, Nginx en un contenedor y un proxy con HTTPS en el servidor.

## 1. Qué se entrega

- El repositorio de GitHub con la rama `main` al día y las pruebas en verde.
- La documentación de `docs/` y el `README.md` con la instalación paso a paso.
- `.env.example` (local) y `.env.production.example` (servidor), con todas las variables y solo marcadores en lugar de claves.

No se entregan claves, cuentas de prueba ni una base de datos con datos reales.

## 2. Flujo de ramas hasta la entrega

```
rama de trabajo (claude/…, feature/…) ──PR + revisión──▶ develop ──PR de cierre──▶ main
```

1. Cada cambio entra a `develop` por *pull request*, con la revisión del otro desarrollador y CI en verde (`28_CONVENCIONES_DESARROLLO.md`).
2. Al cerrar el proyecto, un *pull request* de `develop` a `main` deja la versión de entrega.
3. Se marca la versión con una etiqueta (`v1.0.0`) y se anota en `24_CAMBIOS_Y_VERSIONES.md`.

## 3. Despliegue con Docker (recomendado)

### 3.1 Piezas

| Archivo | Qué es |
|---|---|
| `docker-compose.prod.yml` | Los servicios de producción. El local sigue siendo Sail (`compose.yaml`). |
| `docker/php/Dockerfile.prod` | Dos imágenes: `app` (PHP-FPM 8.4 + Chromium para los PDF) y `web` (Nginx con `public/` y los assets de Vite). |
| `docker/php/entrypoint.prod.sh` | Al arrancar: `storage:link` y `php artisan optimize` (cachea config, rutas, vistas y eventos). |
| `docker/php/php.ini`, `opcache.prod.ini`, `www.conf` | PHP, OPcache (sin revisar archivos) y el pool de PHP-FPM. |
| `docker/nginx/default.prod.conf` | Nginx del contenedor: archivos de `public/` y todo lo demás a `index.php`. |
| `.env.production.example` | Plantilla del `.env` del servidor. |
| `deploy-production.sh` | Actualiza el sistema (sección 3.4). |

| Servicio | Imagen | Para qué |
|---|---|---|
| `app` | `diagnostico-app` | PHP-FPM (Laravel + Inertia). |
| `web` | `diagnostico-web` | Nginx. El único con puerto: `127.0.0.1:${WEB_PORT}` (8200 por defecto). |
| `queue` | `diagnostico-app` | `php artisan queue:work --tries=3`: análisis de la IA, PDF y correos. |
| `scheduler` | `diagnostico-app` | `php artisan schedule:work` (`routes/console.php`). |
| `pgsql` | `postgres:18-alpine` | Base de datos, sin puertos publicados. |
| `migrate`, `artisan` | `diagnostico-app` | Comandos puntuales (perfil `tools`). |

Volúmenes: `pgsql_data` (base de datos) y `storage_data` (`storage/` de Laravel: PDF, logs, sesiones de archivo). Red: `diagnostico`. PHP corre como `www-data`.

Los PDF se generan en `app` y en `queue`: las dos llevan Chromium (`/usr/local/bin/chromium`, instalado con Playwright como en Sail) y Puppeteer en `/opt/browsershot`. Tienen `shm_size: 1gb` porque Chromium falla con los 64 MB por defecto.

### 3.2 Requisitos del servidor

- Docker Engine con el plugin `docker compose`, y `git`.
- Un proxy con HTTPS delante (sección 3.5). Requisitos de memoria y disco: `16_INFRAESTRUCTURA.md`.

### 3.3 Primera instalación

```bash
git clone https://github.com/mensajesfront-commits/DiagnosTICo.git
cd DiagnosTICo
cp .env.production.example .env        # completa lo que está entre < >

# Clave de la aplicación: copia el valor que muestra a APP_KEY en .env
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml run --rm --no-deps artisan key:generate --show

# Base de datos y cuenta principal del Administrador (MAIL_* ya configurado:
# al ADMIN_EMAIL le llega "Crear mi contraseña")
docker compose -f docker-compose.prod.yml up -d pgsql
docker compose -f docker-compose.prod.yml run --rm artisan migrate --seed --force

docker compose -f docker-compose.prod.yml up -d
curl -fsS http://127.0.0.1:8200/up      # debe responder 200
```

Si el correo de "Crear mi contraseña" no llega: `docker compose -f docker-compose.prod.yml run --rm artisan admin:invitar`.

### 3.4 Actualizar a una versión nueva

```bash
./deploy-production.sh              # sin migraciones
./deploy-production.sh --migrate    # con migraciones nuevas
```

El script deja el sitio en mantenimiento, hace `git pull` de `main`, construye las imágenes, verifica que arranquen, corre las migraciones (solo con `--migrate`), recrea los contenedores, espera a que `app` y `web` estén sanos, quita el mantenimiento, borra imágenes viejas y comprueba `/up`. Si algo falla a mitad de camino, quita el mantenimiento. Registro en `deploy.log`.

La cola y el scheduler se recrean con cada despliegue, así que siempre corren el código nuevo (no hace falta `queue:restart`).

Comandos útiles:

```bash
docker compose -f docker-compose.prod.yml ps                       # estado
docker compose -f docker-compose.prod.yml logs -f queue            # logs de la cola
docker compose -f docker-compose.prod.yml run --rm artisan <cmd>   # cualquier comando artisan
docker compose -f docker-compose.prod.yml exec app php artisan prueba:pdf   # prueba que Chromium genera PDF
```

### 3.5 Proxy con HTTPS delante

El servidor usa **Apache** con el SSL (el ejemplo de Nginx queda por si cambia). Los dos apuntan a `127.0.0.1:8200` y le pasan `X-Forwarded-Proto` para que Laravel arme los enlaces con `https://`. Laravel acepta esas cabeceras (`trustProxies` en `bootstrap/app.php`); es seguro porque el contenedor solo escucha en `127.0.0.1`.

**Apache** (módulos `proxy`, `proxy_http`, `headers`, `ssl`):

```apache
<VirtualHost *:443>
    ServerName diagnostico.captter.com

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/diagnostico.captter.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/diagnostico.captter.com/privkey.pem

    ProxyPreserveHost On
    RequestHeader set X-Forwarded-Proto "https"
    RequestHeader set X-Forwarded-Port "443"
    ProxyPass        / http://127.0.0.1:8200/
    ProxyPassReverse / http://127.0.0.1:8200/
    LimitRequestBody 20971520
</VirtualHost>

<VirtualHost *:80>
    ServerName diagnostico.captter.com
    Redirect permanent / https://diagnostico.captter.com/
</VirtualHost>
```

**Nginx:**

```nginx
server {
    listen 443 ssl;
    server_name diagnostico.captter.com;

    ssl_certificate     /etc/letsencrypt/live/diagnostico.captter.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/diagnostico.captter.com/privkey.pem;

    client_max_body_size 20M;

    location / {
        proxy_pass http://127.0.0.1:8200;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-Port  443;
        proxy_read_timeout 120s;
    }
}

server {
    listen 80;
    server_name diagnostico.captter.com;
    return 301 https://$host$request_uri;
}
```

El certificado se puede sacar con Certbot (`certbot --apache` o `certbot --nginx`).

### 3.6 Cola y caché: base de datos o Redis

Hoy la cola, la caché y las sesiones usan PostgreSQL (`QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`): menos piezas y suficiente para el volumen esperado. **[INFORMACIÓN PENDIENTE]** Si NuevasTIC prefiere Redis:

1. Agregar a `docker-compose.prod.yml` un servicio `redis` (`redis:7-alpine`, volumen propio, sin puertos), como en Kiasmer.
2. En `.env`: `REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis` y, si se quiere, `SESSION_DRIVER=redis`.
3. La imagen no trae la extensión de Redis: agregar `pecl install redis && docker-php-ext-enable redis` en la etapa `base` de `docker/php/Dockerfile.prod` (o usar `REDIS_CLIENT=predis` con `composer require predis/predis`).

## 4. Variables y seguridad en producción

- **Obligatorias:** `APP_KEY`, `APP_URL` con `https://`, `DB_PASSWORD`, `OPENAI_API_KEY` y `OPENAI_MODEL`, `MAIL_*` con el SMTP real, `ADMIN_EMAIL`, `TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY`. Plantilla: `.env.production.example`.
- **Rutas de prueba técnica:** `/prueba-tecnica/*` solo existen con `APP_ENV=local`; en producción no se cargan.
- **Seguridad del entorno (auditoría QA del 4 de noviembre, ERR-06/07):**
  - `APP_ENV=production` y `APP_DEBUG=false`: con `local`, `/_inertia/devtools` deja leer a cualquiera las peticiones recientes con sus datos, y un error muestra el código y el SQL.
  - `INERTIA_DEVTOOLS_ENABLED=false`.
  - Con HTTPS: `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`. El sistema ya manda `X-Frame-Options`, `nosniff`, `Referrer-Policy` y, con HTTPS, `Strict-Transport-Security` (`CabecerasSeguridad`).
  - Antes de publicar: `composer audit` y `npm audit --omit=dev` sin alertas altas.
- OpenAI lo paga NuevasTIC, con gpt-4.1-mini.
- Las llaves reales de Cloudflare Turnstile ya existen (4 de noviembre). Van solo en el `.env` del servidor, nunca en el repositorio.
- Dominio: `https://diagnostico.captter.com`, con Apache y el SSL en el servidor. **[INFORMACIÓN PENDIENTE]** Quién mantiene el sistema (`21_MANTENIMIENTO.md`).
- Despliegue automático desde GitHub Actions: **NO APLICA** por ahora. CI solo corre las pruebas.

## 5. Sin Docker (alternativa)

Si el servidor no tiene Docker: PHP 8.4 con `pdo_pgsql`, `intl`, `zip`, `gd`, `bcmath`, `pcntl`; Node 24; PostgreSQL 18; Chromium.

```bash
git clone https://github.com/mensajesfront-commits/DiagnosTICo.git
cd DiagnosTICo
cp .env.production.example .env        # DB_HOST y LARAVEL_PDF_CHROME_PATH según el servidor
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
npm ci
npm run build
php artisan optimize
```

- **Cola:** un servicio de Supervisor con `php artisan queue:work --tries=3`.
- **Tareas programadas:** el cron `* * * * * php artisan schedule:run`.
- **Actualizar:** `php artisan down`, `git pull`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `npm ci && npm run build`, `php artisan optimize`, `php artisan queue:restart`, `php artisan up`.
