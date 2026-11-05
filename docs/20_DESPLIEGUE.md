# Despliegue

**Estado:** COMPLETO (T-155). Cómo se entrega y cómo se despliega. El sistema **se va a desplegar** (DEC-006 cambió el 4 de noviembre). **[INFORMACIÓN PENDIENTE]** El servidor y el dominio.

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

## 3. Si NuevasTIC lo pone en un servidor

Requisitos del servidor: `16_INFRAESTRUCTURA.md`. Pasos:

```bash
git clone https://github.com/mensajesfront-commits/DiagnosTICo.git
cd DiagnosTICo
cp .env.example .env            # APP_ENV=production, APP_DEBUG=false, APP_URL=https://…
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
npm ci
npm run build                   # o npm run build:ssr para usar SSR
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Después:

- **Cola:** un servicio de Supervisor con `php artisan queue:work --tries=3`.
- **Tareas programadas:** el cron `* * * * * php artisan schedule:run`.
- **Variables obligatorias en producción:**
  - `OPENAI_API_KEY` y `OPENAI_MODEL`;
  - `MAIL_*` con el servidor SMTP real;
  - `LARAVEL_PDF_CHROME_PATH`;
  - `ADMIN_EMAIL` para la cuenta principal del Administrador (la elige NuevasTIC). Configura `MAIL_*` antes de `db:seed`: a ese correo le llega "Crear mi contraseña". Si no llega, `php artisan admin:invitar` lo reenvía o muestra el enlace.
- **Rutas de prueba técnica:** `/prueba-tecnica/*` solo existen con `APP_ENV=local`; en producción no se cargan.
- **Seguridad del entorno (auditoría QA del 4 de noviembre, ERR-06/07):**
  - `APP_ENV=production` y `APP_DEBUG=false`: con `local`, `/_inertia/devtools` deja leer a cualquiera las peticiones recientes con sus datos, y un error muestra el código y el SQL.
  - `INERTIA_DEVTOOLS_ENABLED=false`.
  - Con HTTPS: `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`. El sistema ya manda `X-Frame-Options`, `nosniff`, `Referrer-Policy` y, con HTTPS, `Strict-Transport-Security` (`CabecerasSeguridad`).
  - Antes de publicar: `composer audit` y `npm audit --omit=dev` sin alertas altas.

### Actualizar a una versión nueva

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize
php artisan queue:restart
php artisan up
```

## 4. Despliegue (decisión del 4 de noviembre de 2026)

El sistema **se va a desplegar** (DEC-006 cambió). La sección 3 tiene los pasos.

- **[INFORMACIÓN PENDIENTE]** El servidor, el dominio, el certificado HTTPS y quién mantiene el sistema (`21_MANTENIMIENTO.md`).
- OpenAI lo paga NuevasTIC, con gpt-4.1-mini.
- Las llaves reales de Cloudflare Turnstile ya existen (4 de noviembre). Van solo en el `.env` del servidor (`TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY`), nunca en el repositorio.
- Despliegue automático desde GitHub Actions: **NO APLICA** por ahora. CI solo corre las pruebas.
