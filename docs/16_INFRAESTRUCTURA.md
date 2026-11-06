# Infraestructura

**Estado:** COMPLETO (T-154). Describe la infraestructura del entorno local y lo que necesita el servidor. El sistema se va a desplegar (DEC-006 cambió el 4 de noviembre); los pasos están en `20_DESPLIEGUE.md`. En producción corre con Docker Compose en https://diagnostico.captter.com (DEC-024).

## 1. Entorno local (Sail)

```
            Navegador
                │  http://localhost (80)   ·   Vite :5173 (npm run dev)
                ▼
┌──────────────────────── Docker (red "sail") ────────────────────────┐
│ laravel.test  (sail-8.4/app, docker/8.4/Dockerfile)                  │
│   PHP 8.4 · Composer · Node 24 · Chromium                            │
│   artisan serve · queue:work · schedule:work · npm run dev           │
│        │                           │                                 │
│        ▼                           ▼                                 │
│ pgsql (postgres:18-alpine)     mailpit (SMTP :1025, web :8025)       │
│   bases: laravel y testing                                           │
└──────────────────────────────────────────────────────────────────────┘
                │
                ▼  HTTPS
           API de OpenAI
```

| Servicio | Imagen | Puertos | Datos |
|---|---|---|---|
| `laravel.test` | `sail-8.4/app` | `APP_PORT` (80), `VITE_PORT` (5173) | El código se monta desde la carpeta del proyecto |
| `pgsql` | `postgres:18-alpine` | `FORWARD_DB_PORT` (5432) | Volumen `sail-pgsql` |
| `mailpit` | `axllent/mailpit` | 1025 (SMTP), 8025 (web) | En memoria |

Los comandos y las variables por entorno están en `27_ENTORNOS.md`.

## 2. Procesos que deben correr

| Proceso | Comando | Sin él… |
|---|---|---|
| Web | `php artisan serve` (en Sail lo arranca Supervisor) o un servidor web con PHP-FPM | No hay sistema |
| Cola | `php artisan queue:work` | Las mediciones enviadas no se analizan, no se publica el resultado ni se genera el PDF, y no salen los correos en cola |
| Tareas programadas | `php artisan schedule:work` (local) o un cron con `php artisan schedule:run` cada minuto | No se marcan las vencidas (`mediciones:vencer`, cada hora a los :05, según la medianoche de cada empresa), no se purgan las cuentas eliminadas (`cuentas:purgar`, 03:10) ni sale el resumen semanal (`mediciones:resumen`, lunes 07:50) |
| Frontend | `npm run build` una vez (o `npm run dev` mientras se programa) | No cargan las pantallas |
| SSR (opcional) | `npm run build:ssr` y `php artisan inertia:start-ssr` | Las páginas se arman solo en el navegador |

## 3. Producción (Docker Compose)

Se publica en **https://diagnostico.captter.com** (DEC-024), con la misma estructura de producción de Kiasmer (PR #17). Apache del servidor tiene el SSL y reenvía a `127.0.0.1:8200`, donde escucha el servicio `web` (`WEB_PORT=8200`).

```
Internet ──https──▶ Apache (SSL) ──▶ 127.0.0.1:${WEB_PORT}
                                         │
┌──────────── docker-compose.prod.yml (red "diagnostico") ────────────┐
│ web (Nginx, target "web") ──fastcgi──▶ app (PHP 8.4-FPM)             │
│ app · queue · scheduler · migrate · artisan  (target "app")          │
│   PHP 8.4-FPM · Chromium (Playwright) · Puppeteer · assets de Vite   │
│ pgsql (postgres:18-alpine)                                           │
└──────────────────────────────────────────────────────────────────────┘
                │
                ▼  HTTPS
     OpenAI · Cloudflare Turnstile · SMTP
```

| Servicio | Imagen | Puerto | Datos |
|---|---|---|---|
| `web` | `diagnostico-web` (Nginx) | `127.0.0.1:${WEB_PORT}` (8200) → 80 | `public/` y `public/build` dentro de la imagen; `storage_data` en solo lectura |
| `app` | `diagnostico-app` (PHP 8.4-FPM) | — | Volumen `storage_data` |
| `queue` | `diagnostico-app` | — | `queue:work --tries=3 --sleep=1 --max-time=3600` |
| `scheduler` | `diagnostico-app` | — | `schedule:work` |
| `pgsql` | `postgres:18-alpine` | — | Volumen `pgsql_data` |
| `migrate`, `artisan` | `diagnostico-app` | — | Comandos puntuales (perfil `tools`; los usa `deploy-production.sh`) |

`app`, `queue` y `scheduler` llevan Chromium (`/usr/local/bin/chromium`) con `shm_size: 1gb`, porque los PDF se generan tanto desde la web como desde la cola.

Lo que necesita el servidor:

| Pieza | Requisito |
|---|---|
| Docker | Docker Engine con el plugin `docker compose`, y `git` |
| Apache | El certificado de `diagnostico.captter.com`, `mod_proxy`, `mod_proxy_http` y `mod_headers`, con `X-Forwarded-Proto: https` (`20_DESPLIEGUE.md`) |
| Correo | Un servidor SMTP (`MAIL_*`) |
| OpenAI | Clave y modelo (`OPENAI_API_KEY`, `OPENAI_MODEL=gpt-4.1-mini`), que paga NuevasTIC |
| Cloudflare Turnstile | Llaves reales del captcha del registro; sin ellas nadie se registra |
| Salida a internet | Hacia OpenAI, Cloudflare y el SMTP |

PHP, PostgreSQL, Node y Chromium vienen en las imágenes: no se instalan en el servidor. Redis no se usa por ahora (`20_DESPLIEGUE.md` explica cómo pasar a él).

## 4. Archivos que guarda el sistema

| Qué | Dónde | Disco |
|---|---|---|
| Informes PDF (el completo y el resumen de cada resultado) | `storage/app/private/informes/{empresa}/` | `local` (privado; se descargan por una ruta con permiso) |
| Fotos de perfil y logos | `storage/app/private/fotos/` y `storage/app/private/logos/` | `local` (se sirven por `/imagenes/{tipo}/{id}`) |
| Registros de errores | `storage/logs/laravel.log` (en producción, `laravel-AAAA-MM-DD.log` por día, 14 días) | — |
| Sesiones, caché y cola | Tablas `sessions`, `cache`, `jobs`, `failed_jobs` (también en producción) | PostgreSQL |

## 5. Copias de seguridad

**[INFORMACIÓN PENDIENTE]** Quién programa las copias y cada cuánto. En producción hay que respaldar:

- la base de datos (`docker compose -f docker-compose.prod.yml exec pgsql pg_dump -U diagnostico diagnostico`), que tiene todo lo importante (diagnósticos, versiones congeladas, respuestas y resultados);
- el volumen `storage_data` (`storage/app/private`: PDF, fotos y logos). Los PDF se pueden volver a generar desde el resultado guardado;
- el archivo `.env` (con las claves), fuera del repositorio.

## 6. Integración continua

GitHub Actions (`.github/workflows/tests.yml`) levanta PostgreSQL 18, instala PHP 8.4 y Node 24 y corre `composer ci:check` en cada *pull request* y en cada *push* a `main` (`27_ENTORNOS.md`).
