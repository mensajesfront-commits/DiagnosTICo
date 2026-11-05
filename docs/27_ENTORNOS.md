# Entornos

**Estado:** COMPLETO (T-032).

Los dos desarrolladores usan el mismo **entorno local** con Laravel Sail. El sistema se va a desplegar (DEC-006 cambió el 4 de noviembre): el entorno de producción se describe en `20_DESPLIEGUE.md`. **[INFORMACIÓN PENDIENTE]** Servidor y dominio.

## Entorno local con Sail

`compose.yaml` levanta cinco servicios:

| Servicio | Imagen | Puerto en el equipo | Para qué |
|---|---|---|---|
| `laravel.test` | `sail-8.4/app` (construida desde `docker/8.4/Dockerfile`) | `80` (`APP_PORT`) y `5173` (`VITE_PORT`) | PHP 8.4, Node 24, Composer y Chromium. Corre la aplicación, las colas y Vite. |
| `pgsql` | `postgres:18-alpine` | `5432` (`FORWARD_DB_PORT`) | Base de datos. Al arrancar por primera vez crea también la base `testing` (`docker/pgsql/create-testing-database.sql`). |
| `mailpit` | `axllent/mailpit` | `1025` para SMTP y `8025` para la web | Atrapa todos los correos. Se revisan en `http://localhost:8025`. |
| `queue` | `sail-8.4/app` | — | Corre la cola (`queue:work`): el análisis de la IA, los dos PDF y los correos. |
| `scheduler` | `sail-8.4/app` | — | Corre las tareas programadas (`schedule:work`): vencer mediciones, purgar cuentas y el resumen semanal. |

En Windows, si un puerto cae en un rango que reserva el sistema, Docker no lo puede abrir: se cambia en el `.env` (ISSUE-011).

Los datos de PostgreSQL viven en el volumen `sail-pgsql`. Con `./vendor/bin/sail down -v` se borran.

### Comandos frecuentes

```bash
./vendor/bin/sail up -d                 # levantar
./vendor/bin/sail artisan migrate       # migraciones
./vendor/bin/sail npm run dev           # Vite con recarga
./vendor/bin/sail restart queue scheduler # la cola y las tareas programadas ya arrancan con up; reiniciar tras cambiar PHP
./vendor/bin/sail logs -f queue         # ver qué hace la cola (análisis de la IA, correos)
./vendor/bin/sail artisan test          # pruebas del backend
./vendor/bin/sail down                  # apagar
```

Para no escribir `./vendor/bin/sail` cada vez, se puede crear el alias `alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'`.

## Variables por entorno

Todas están explicadas en `.env.example`.

| Variable | Local con Sail | Pruebas automáticas (`phpunit.xml`) |
|---|---|---|
| `DB_CONNECTION` / `DB_HOST` | `pgsql` / `pgsql` | La misma conexión, con la base `testing` |
| `MAIL_MAILER` | `smtp` hacia `mailpit:1025` | `array` (no envía) |
| `QUEUE_CONNECTION` | `database` | `sync` |
| `OPENAI_API_KEY` | La de cada desarrollador | No se usa: las pruebas simulan OpenAI (`OpenAI::fake()`) |
| `LARAVEL_PDF_CHROME_PATH` | `/usr/local/bin/chromium` | No se usa: las pruebas simulan el PDF (`Pdf::fake()`) |

## Correo

- En local, todos los correos van a Mailpit y no salen a internet.
- Quien instale el sistema en otro lugar pone su servidor SMTP en `MAIL_*`.

## OpenAI

- Cada desarrollador pone su clave en `OPENAI_API_KEY` y el modelo en `OPENAI_MODEL`.
- `php artisan prueba:ia` hace una llamada real para comprobar que todo funciona (gasta una llamada).
- Sin clave no se puede analizar: no hay modo de prueba en la aplicación. Las pruebas automáticas usan `Tests\Support\AnalizadorFalso` y no gastan saldo.
- El análisis corre en la cola. El servicio `queue` de `compose.yaml` arranca solo con `sail up`; sin él, una medición enviada se queda en "Estamos analizando". Con la cola corriendo tarda 1 o 2 minutos (una llamada por categoría).
- En producción la clave y el saldo los aporta NuevasTIC, con el modelo `gpt-4.1-mini` (23 de octubre de 2026).

## Captcha (Cloudflare Turnstile)

- En local se pueden dejar vacías `TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY`: el sistema usa las llaves de prueba de Cloudflare, que siempre aprueban.
- En el servidor van las llaves reales, que ya existen (4 de noviembre de 2026). Sin ellas, nadie se registra.

## Producción

El sistema se va a desplegar (DEC-006 cambió el 4 de noviembre). Las variables de producción y los pasos están en `20_DESPLIEGUE.md`. **[INFORMACIÓN PENDIENTE]** Servidor y dominio.

## WhatsApp

El bot lo terminó otra persona del equipo, fuera de este repositorio (4 de noviembre de 2026). Su entorno no se documenta aquí.

## Integración continua

GitHub Actions (`.github/workflows/tests.yml`) corre en cada *pull request* y en cada *push* a `main`. Usa PHP 8.4, Node 24 (`.nvmrc`) y un servicio `postgres:18-alpine`, y ejecuta:

1. `composer setup`
2. `composer ci:check`: formato, tipos de TypeScript, pruebas de Vitest, Pint, PHPStan y pruebas de Pest.
