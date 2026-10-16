# Despliegue

**Estado:** NO APLICA en su mayor parte (T-155). El alcance acordado es entregar el repositorio; el sistema **no se despliega** (DEC-006, `03_ALCANCE.md`). Lo que sí aplica es cómo se entrega y cómo lo instalaría NuevasTIC si decide ponerlo en línea.

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
  - `IA_DE_PRUEBA=false`;
  - `MAIL_*` con el servidor SMTP real;
  - `LARAVEL_PDF_CHROME_PATH`;
  - `ADMIN_EMAIL` y `ADMIN_PASSWORD` para la primera cuenta.
- **Rutas de prueba técnica:** `/prueba-tecnica/*` solo existen con `APP_ENV=local`; en producción no se cargan.

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

## 4. Lo que no aplica

- Servidores de staging y producción, dominio y certificado: **NO APLICA** (sin despliegue).
- Despliegue automático desde GitHub Actions: **NO APLICA**. CI solo corre las pruebas.
- **[INFORMACIÓN PENDIENTE]** Si NuevasTIC decide desplegarlo después, debe definir el servidor, el dominio, quién paga OpenAI y quién mantiene el sistema (`21_MANTENIMIENTO.md`).
