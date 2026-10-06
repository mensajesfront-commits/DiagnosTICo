# Estado actual

**Estado:** EN CURSO (T-037). Se actualiza al cerrar cada semana.

**Última actualización:** 6 de octubre de 2026, durante la semana 3 (pantallas de acceso y diagnósticos).

## Resumen

El repositorio tiene:

- el proyecto base (Laravel 13 con el kit de Vue);
- el entorno de Sail;
- las librerías del stack;
- la prueba técnica;
- los estilos del wireframe;
- los componentes base;
- la primera documentación.

Desde la semana 3 también tiene las primeras pantallas del negocio (frontend), que por ahora se revisan con datos de ejemplo en `/prueba-tecnica/vistas/{vista}` hasta que existan las rutas del backend.

## Reparto del trabajo

- **Cristian:** frontend y documentación.
- **Luis:** backend (migraciones, modelos, rutas, controladores). Las props y los campos que espera cada pantalla están en `14_FRONTEND.md`, y las rutas propuestas en `15_BACKEND.md`.

## Semana 3: frontend

| Tarea | Qué quedó | Pendiente |
|---|---|---|
| T-051 Pantallas de acceso | L1, L2, L3 y L4 con el diseño del wireframe | — |
| T-046 / T-047 (mínimo) | Tablas de sectores y empresas, roles y permisos, sectores de ejemplo, Administrador inicial y registro de la empresa | Revisión de Luis; RN-001 y RN-005 en el servidor |
| T-052 Menú por rol | `MenuLateral` oculta lo que no tiene permiso (con prueba) | — |
| T-053 Diagnósticos de un sector | A2, A2·T y A2b (`pages/diagnosticos/Index.vue`) | Ruta y controlador (backend) |
| T-054 Sectores | Modales A2.2, A2.2a, A2.2b, A2.2c, A2.2d y A2.2e | Rutas de sectores (backend) |
| T-055 Catálogo de categorías | A2.3, A2.3b y A2.3c | Rutas de categorías (backend) |
| T-056 Crear diagnóstico | A2.5, en blanco o copiando; también duplicar, archivar y eliminar | `POST /diagnosticos` (backend) |
| T-043 Rutas del backend | Propuesta en `15_BACKEND.md` | Que Luis la valide |
| T-066 / T-088 UI/UX y frontend | `18_UI_UX.md` y `14_FRONTEND.md` | Se completan cada semana |

## Semana 2: tareas "En curso"

| Tarea | Qué quedó | Pendiente |
|---|---|---|
| T-005 Definición de terminado | `28_CONVENCIONES_DESARROLLO.md` | Que el equipo la apruebe |
| T-006 Matriz de roles y permisos | `17_SEGURIDAD.md` y el seeder con los 15 permisos de A5.2 | — (quedan por validar «Ver como» y el rol Consultor) |
| T-007 MER | `12_BASE_DE_DATOS.md`, revisado contra todos los wireframes (T-039) | Aprobación del equipo (T-044) |
| T-015 Repositorio | Repositorio en GitHub con el código | Crear `develop` y proteger `main` y `develop` en GitHub |
| T-016 Proyecto y Sail | Kit de Vue + Sail (PHP 8.4, PostgreSQL 18, Mailpit, Chromium) | Construir la imagen en los equipos (ISSUE-009) |
| T-017 Versiones fijas | `composer.lock`, `package-lock.json`, `.nvmrc` (24) | — |
| T-018 Prueba de permisos | Rol, ruta bloqueada y pruebas | — |
| T-019 Prueba de OpenAI | Servicio, comando `prueba:ia` y pruebas con la API simulada | — (la llamada real queda para la semana 5, DEC-013) |
| T-020 WhatsApp | No se hizo: el bot está aplazado | Decidir si se saca del cronograma |
| T-021 Instalar desde el README | README escrito; instalación desde cero probada fuera de Sail | Que Cristian la repita en su equipo con Sail |
| T-022 Tailwind del wireframe | Colores, niveles e IBM Plex en `resources/css/app.css` | — |
| T-023 Componentes base | Menú lateral, layout, modal, selector, botones, etiquetas, tarjetas y tablas | Revisarlos contra el prototipo en `/prueba-tecnica/componentes` |
| T-024 Radar de prueba | `RadarCategorias.vue` en `/prueba-tecnica/graficas` | — |
| T-025 PDF de prueba | PDF con el radar (`prueba:pdf` y `/prueba-tecnica/pdf`) | — |
| T-026 a T-037 Documentación | `CLAUDE.md`, `README.md`, `.env.example` y los documentos de `docs/` del índice | Notas de la reunión T-008 en `03_ALCANCE.md` |
| T-038 Prueba técnica aprobada | Todo menos WhatsApp (aplazado); la llamada real a OpenAI se hace en la semana 5 (DEC-013) | Aprobar el hito |

## Cómo verificar lo hecho

```bash
./vendor/bin/sail artisan test        # 38 pruebas de Pest
./vendor/bin/sail npm test            # pruebas de Vitest
./vendor/bin/sail artisan prueba:pdf  # PDF real en storage/app/private/pruebas/
```

Para ver las pantallas de la prueba técnica, entra como un usuario con rol Administrador a:

- `/prueba-tecnica/componentes`
- `/prueba-tecnica/graficas`

## Bloqueos y decisiones pendientes

- **Bot de WhatsApp:** aplazado. El cronograma todavía lo tiene en las semanas 2, 6 y 7 (`03_ALCANCE.md`).
- **Clave de OpenAI y modelo por defecto:** sin definir; se necesitan en la semana 5 (DEC-013).
- **Contraseña:** RN-001 pide un carácter especial que los wireframes L2 y L4 no muestran (`17_SEGURIDAD.md`).
- **Cronograma desactualizado:** todavía habla de FODA, de 3 etapas de IA y de un PDF de 4 páginas (T-098, T-099, T-113, T-122, T-127). Los requisitos vigentes dicen una sola etapa y un PDF de 3 páginas, sin FODA.
