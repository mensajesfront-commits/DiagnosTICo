# Infraestructura

**Estado:** COMPLETO (T-154). El sistema no se despliega (DEC-006): se entrega el repositorio. Este documento describe la infraestructura del entorno local y lo que necesitaría quien lo instale en un servidor.

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

## 3. Requisitos para instalarlo en un servidor

Si NuevasTIC decide ponerlo en línea, necesita como mínimo:

| Pieza | Requisito |
|---|---|
| PHP | 8.4 con `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `gd`, `bcmath` |
| Base de datos | PostgreSQL 18 (usa JSONB) |
| Node | 24, para compilar el frontend y para Browsershot |
| Chromium | Instalado en el servidor, con su ruta en `LARAVEL_PDF_CHROME_PATH` |
| Correo | Un servidor SMTP (`MAIL_*`) |
| OpenAI | Clave y modelo (`OPENAI_API_KEY`, `OPENAI_MODEL`) |
| Supervisor o similar | Para mantener vivo `queue:work` |
| Cron | `* * * * * php artisan schedule:run` |
| HTTPS | Certificado; con `APP_URL` en `https://` |

Los pasos están en `20_DESPLIEGUE.md`.

## 4. Archivos que guarda el sistema

| Qué | Dónde | Disco |
|---|---|---|
| Informes PDF | `storage/app/private/informes/` | `local` (privado; se descargan por una ruta con permiso) |
| Fotos de perfil y logos | `storage/app/private/fotos/` y `storage/app/private/logos/` | `local` (se sirven por `/imagenes/{tipo}/{id}`) |
| Registros de errores | `storage/logs/laravel.log` | — |
| Sesiones, caché y cola | Tablas `sessions`, `cache`, `jobs`, `failed_jobs` | PostgreSQL |

## 5. Copias de seguridad

**[INFORMACIÓN PENDIENTE]** No hay servidor, así que no hay copias programadas. Si se instala, hay que respaldar:

- la base de datos (`pg_dump`), que tiene todo lo importante (diagnósticos, versiones congeladas, respuestas y resultados);
- `storage/app/private` (PDF, fotos y logos). Los PDF se pueden volver a generar desde el resultado guardado;
- el archivo `.env` (con las claves), fuera del repositorio.

## 6. Integración continua

GitHub Actions (`.github/workflows/tests.yml`) levanta PostgreSQL 18, instala PHP 8.4 y Node 24 y corre `composer ci:check` en cada *pull request* y en cada *push* a `main` (`27_ENTORNOS.md`).
