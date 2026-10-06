# Cambios y versiones

**Estado:** EN CURSO (T-037). Se agrega una sección por semana; lo más nuevo va arriba.

La versión de entrega será `v1.0.0` (T-152). Hasta entonces, los cambios se agrupan por semana del cronograma.

## Semana 3 · del 5 al 11 de octubre de 2026 (en curso)

### Agregado

- Pantallas de acceso con el diseño del wireframe: L1 (iniciar sesión), L2 (registrar la empresa), L3 (olvidé mi contraseña) y L4 (nueva contraseña).
- Diagnósticos del Administrador: A2 (un sector), A2·T (todos), A2b (sector vacío), modales de sector (A2.2 a A2.2e), duplicar (A2.7), archivar y eliminar.
- Catálogo de categorías (A2.3) con sus modales de crear, editar, eliminar o archivar.
- Crear diagnóstico (A2.5), en blanco o copiando uno publicado.
- Componentes base de formulario: `Campo`, `Entrada`, `Seleccion`, `AreaTexto`, `CampoContrasena`, `RequisitosContrasena`, `Aviso` y `TarjetaOpcion`.
- `lib/contrasena.ts` (requisitos de RN-001, con pruebas) y `lib/rutas.ts` (URLs propuestas).
- Vistas previas con datos de ejemplo: `/prueba-tecnica/vistas/{vista}` lee `resources/datos-ejemplo/{vista}.json`.
- Documentos `14_FRONTEND.md`, `15_BACKEND.md` y `18_UI_UX.md`.

### Cambiado

- `AuthLayout` reemplaza los layouts de acceso del kit.
- Los modales enfocan el primer campo al abrirse.
- El fondo del menú lateral llega hasta abajo en páginas largas.

## Semana 2 · del 28 de septiembre al 4 de octubre de 2026 (cerrada el 5 de octubre)

### Agregado

- Proyecto creado con el kit de inicio de Laravel para Vue: Laravel 13, Inertia 3, Vue 3.5, Tailwind 4 y Reka UI.
- Laravel Sail con PHP 8.4, PostgreSQL 18, Mailpit y Chromium (Dockerfile publicado en `docker/8.4`).
- Versiones fijas: `composer.lock`, `package-lock.json`, `.nvmrc` (Node 24) y PHP 8.4 en `config.platform`.
- Librerías del stack:
  - spatie/laravel-permission, openai-php/laravel, spatie/laravel-pdf con Browsershot y Pest 5;
  - Chart.js, vue-chartjs, chartjs-plugin-annotation, vue-draggable-plus y puppeteer;
  - las fuentes IBM Plex.
- Prueba técnica:
  - ruta bloqueada por rol;
  - análisis de una categoría con OpenAI (`prueba:ia`);
  - radar de Chart.js;
  - PDF con el radar (`prueba:pdf`).
- Estilos del wireframe en Tailwind y componentes base en `resources/js/components/base/`.
- Vitest configurado (`npm test`) y CI con PHP 8.4, Node 24 y PostgreSQL 18.
- Documentación inicial: `CLAUDE.md`, `README.md`, `.env.example` y los documentos de `docs/` marcados en el índice.

### Cambiado

- Guzzle 8 → 7, que es la versión que exige openai-php/laravel (DEC-009).
- PHPUnit 12 → 13, que es la versión que exige Pest 5 (DEC-007).
- El menú lateral del kit se reemplazó por el del wireframe (`MenuLateral.vue` y `PanelLayout.vue`).

### Quitado

Funciones del kit que no están en el diseño (DEC-012):

- verificación de correo;
- 2FA;
- passkeys;
- confirmación de contraseña;
- modo oscuro (apariencia).

## Semana 1 · del 21 al 27 de septiembre de 2026

- Wireframes, mapas de flujo y prototipo navegable: 77 vistas, 55 pantallas y 22 modales (T-001). Fuera del repositorio.
