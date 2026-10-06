# Cambios y versiones

**Estado:** EN CURSO (T-037). Se agrega una sección por semana; lo más nuevo va arriba.

La versión de entrega será `v1.0.0` (T-152). Hasta entonces, los cambios se agrupan por semana del cronograma.

## Semana 3 · del 5 al 11 de octubre de 2026 (en curso)

### Agregado

- Pantallas de acceso con el diseño del wireframe: L1 (iniciar sesión), L2 (registrar la empresa), L3 (olvidé mi contraseña) y L4 (nueva contraseña).
- Diagnósticos del Administrador: A2 (un sector), A2·T (todos), A2b (sector vacío), modales de sector (A2.2 a A2.2e), duplicar (A2.7), archivar y eliminar.
- Catálogo de categorías (A2.3) según el wireframe: filtro Todas / En uso / Archivadas, panel para editar, modales de crear y archivar, y «Restaurar».
- Crear diagnóstico (A2.5), en blanco o copiando uno publicado.
- Componentes base de formulario: `Campo`, `Entrada`, `Seleccion`, `AreaTexto`, `CampoContrasena`, `RequisitosContrasena`, `Aviso` y `TarjetaOpcion`.
- `lib/contrasena.ts` (requisitos de RN-001, con pruebas) y `lib/rutas.ts` (URLs propuestas).
- Vistas previas con datos de ejemplo: `/prueba-tecnica/vistas/{vista}` lee `resources/datos-ejemplo/{vista}.json`.
- Documentos `14_FRONTEND.md`, `15_BACKEND.md` y `18_UI_UX.md`.
- Backend mínimo para el registro: tablas `sectores` y `empresas`, roles y permisos, 7 sectores de ejemplo, Administrador inicial (`migrate --seed`) y registro que crea la empresa con su usuario de rol Empresa.
- Inicio provisional en español para revisar el menú de cada rol.
- Logo del sistema en el menú, el acceso y el ícono del navegador. El sistema se llama **Captter**.
- Mi perfil (A6 y E11) conectado: datos personales, datos de la empresa para la cuenta principal, avisos por correo, foto o logo y cambio de contraseña. Se guarda el último acceso.
- Al abrir el sistema (`/`) se entra directo al inicio de sesión.
- Seguridad del acceso completa: cuentas y empresas desactivadas no entran, contraseña fuerte en todos los entornos, mismo aviso en la recuperación, enlace sin vencimiento, límite de intentos, mensajes y correo en español, constancia de los términos (DEC-014).
- Cuentas de demostración (`DemoSeeder`) y diccionario de datos de las tablas existentes.
- Usuarios y roles (A5, A5.1–A5.5): lista de cuentas con búsqueda y filtros, roles del sistema y creados, modales de invitar, desactivar, cambiar y asignar rol, crear y eliminar rol, conectados al backend (invitaciones por correo, desactivar, cambiar y asignar rol, CRUD de roles). "Ver como" llega con A5.4.
- Colaboradores de la empresa (E12, E12.1–E12.3): lista, agregar, datos para compartir, cambiar contraseña, desactivar y reactivar. Conectado al backend (`ColaboradoresController`): solo la cuenta principal entra; cambiar la contraseña o desactivar cierra las sesiones del colaborador.
- Documentos 02 (visión y objetivos), 04 (stakeholders), 06 (requisitos no funcionales), 09 (flujos, con el diagrama de secuencia de la medición) y 10 (arquitectura).

### Cambiado (roles)

- La matriz de roles y permisos sigue el wireframe A5.2: 15 permisos en 6 bloques; Administrador 15, Empresa 5, Colaborador 5 (también edita su perfil). Rol Consultor creado inactivo; los roles guardan descripción, si están activos y si son del sistema. «Colaboradores» del menú se decide por el rol Empresa.

### Cambiado

- `AuthLayout` reemplaza los layouts de acceso del kit.
- Los modales enfocan el primer campo al abrirse.
- Se quitaron las páginas de ajustes del kit y la opción de eliminar la cuenta (RN-004).
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
