# Entornos

**Estado:** EN CURSO (T-032)

El sistema solo tiene **entorno local**, porque no se despliega (DEC-006). Los dos desarrolladores usan el mismo entorno con Laravel Sail.

## Entorno local con Sail

`compose.yaml` levanta tres servicios:

| Servicio | Imagen | Puerto en el equipo | Para qué |
|---|---|---|---|
| `laravel.test` | `sail-8.4/app` (construida desde `docker/8.4/Dockerfile`) | `80` (`APP_PORT`) y `5173` (`VITE_PORT`) | PHP 8.4, Node 24, Composer y Chromium. Corre la aplicación, las colas y Vite. |
| `pgsql` | `postgres:18-alpine` | `5432` (`FORWARD_DB_PORT`) | Base de datos. Al arrancar por primera vez crea también la base `testing` (`docker/pgsql/create-testing-database.sql`). |
| `mailpit` | `axllent/mailpit` | `1025` para SMTP y `8025` para la web | Atrapa todos los correos. Se revisan en `http://localhost:8025`. |

Los datos de PostgreSQL viven en el volumen `sail-pgsql`. Con `./vendor/bin/sail down -v` se borran.

### Comandos frecuentes

```bash
./vendor/bin/sail up -d                 # levantar
./vendor/bin/sail artisan migrate       # migraciones
./vendor/bin/sail npm run dev           # Vite con recarga
./vendor/bin/sail artisan queue:work    # cola (correos e IA en segundo plano)
./vendor/bin/sail artisan schedule:work # tareas programadas (vencimientos)
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
- **[INFORMACIÓN PENDIENTE]** Falta saber quién aporta la clave y el saldo para el desarrollo (propuesta técnica, sección 5).

## WhatsApp (aplazado)

El bot está aplazado (`03_ALCANCE.md`). Si se retoma, se documentan aquí:

- el número de prueba de Meta;
- el túnel (Cloudflare Tunnel o ngrok) para que el webhook llegue al equipo;
- las variables, sin claves reales.

## Integración continua

GitHub Actions (`.github/workflows/tests.yml`) corre en cada *pull request* y en cada *push* a `main`. Usa PHP 8.4, Node 24 (`.nvmrc`) y un servicio `postgres:18-alpine`, y ejecuta:

1. `composer setup`
2. `composer ci:check`: formato, tipos de TypeScript, pruebas de Vitest, Pint, PHPStan y pruebas de Pest.
