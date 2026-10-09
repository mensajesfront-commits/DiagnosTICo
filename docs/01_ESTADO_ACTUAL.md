# Estado actual

**Estado:** EN CURSO (T-037). Se actualiza al cerrar cada semana.

**Última actualización:** 8 de octubre de 2026, al cerrar las tareas "En curso" de la semana 3.

## Resumen

El repositorio tiene:

- el proyecto base (Laravel 13 con el kit de Vue), Sail y las librerías del stack;
- la prueba técnica (permisos, gráficas y PDF; la llamada real a OpenAI espera la clave);
- los estilos del wireframe y los componentes base;
- **el modelo de datos completo**: todas las tablas del MER con migración, modelo y fábrica;
- **pantallas conectadas al backend**:
  - acceso (L1–L4);
  - Mi perfil (A6, E11);
  - diagnósticos, sectores y categorías (A2);
  - usuarios y roles (A5);
  - colaboradores (E12);
- la redirección al inicio de cada rol (A1 y E1 todavía son pantallas de bienvenida);
- la documentación de las semanas 2 y 3.

Pruebas: 140 de Pest, más las de Vitest. Todo pasa.

## Reparto del trabajo

- **Cristian:** frontend y documentación.
- **Luis:** backend (migraciones, modelos, rutas, controladores). Las props y los campos que espera cada pantalla están en `14_FRONTEND.md`, y las rutas en `15_BACKEND.md`.

Parte del backend de la semana 3 se hizo desde el frontend para conectar las pantallas. **Luis lo revisa en el pull request.**

## Semana 3: tareas "En curso" del cronograma

| Tarea | Qué quedó | Estado |
|---|---|---|
| T-040 Diccionario de datos | Todas las tablas en `12_BASE_DE_DATOS.md`: tipo, si es obligatorio y un ejemplo | Hecho |
| T-041 Casos de uso | `08_CASOS_DE_USO.md`: publicar, asignar, responder, IA con fallas, Ver como y bot | Hecho; se aprueba en T-044 |
| T-042 Diagrama de secuencia | `09_FLUJOS_DEL_SISTEMA.md` (responder → enviar → cola → IA → resultado) | Hecho |
| T-043 Rutas por módulo | `15_BACKEND.md`: módulo, controlador, permiso, pantalla y estado; incluye el webhook (aplazado) | Hecho; Luis lo valida |
| T-045 Migraciones | Todas las tablas del MER (`2026_10_08_000004` a `000006`) | Hecho |
| T-046 Datos iniciales | 10 categorías del wireframe, niveles (`App\Support\Niveles`), sectores y CIIU | Hecho |
| T-048 Redirección por rol | `/dashboard` → A1 (`/inicio`) o E1 (`/mi-inicio`) | Hecho |
| T-049 / T-050 Backend de A2 | Diagnósticos, sectores y categorías conectados | Hecho; el editor A2.1 es de la semana 4 |
| T-058 Fábricas | Empresa, categoría, diagnóstico (con categorías y publicado) y medición | Hecho |
| T-061 Requisitos ↔ historias | Matriz RF → HU y tabla HU → RF con estado en `05_REQUISITOS_FUNCIONALES.md` | Hecho |
| T-064 Tablas con ejemplo y JSONB | `12_BASE_DE_DATOS.md` | Hecho; el formato de `respuesta_ia` se confirma con la clave de OpenAI |
| T-067 Riesgos | `30_RIESGOS.md` (R-001 a R-012) | Hecho |
| T-068 Glosario | Migración, seeder, factory, modelo, rol, permiso, middleware, ruta, controlador, componente, soft delete, cola | Hecho |
| T-069 Estado y cambios | Este documento y `24_CAMBIOS_Y_VERSIONES.md` | Hecho |
| T-019 Prueba de OpenAI | Servicio y pruebas con la API simulada | **Bloqueado:** falta la clave (DEC-013, R-002) |
| T-038 Hito: prueba técnica aprobada | Todo menos la llamada real a OpenAI y WhatsApp (aplazado) | Lo aprueba el equipo |
| T-044 Hito: MER y casos de uso aprobados | MER migrado y casos de uso escritos | Lo aprueba el equipo |
| T-070 Hito: base documental inicial | Contexto, alcance, requisitos, arquitectura y los documentos de la semana 3 | Lo aprueba el equipo |

Los hitos (T-038, T-044, T-070) no son código: se marcan como hechos cuando el equipo revisa y aprueba en la reunión.

## Semana 3: pantallas (frontend)

| Tarea | Qué quedó |
|---|---|
| T-051 Pantallas de acceso | L1, L2 (dos pasos, CIIU, ubicación en cascada), L3 y L4, con modales de cuenta desactivada o eliminada y de demasiados intentos |
| T-052 Menú por rol | `MenuLateral` oculta lo que no tiene permiso; el servidor también lo protege |
| T-053 a T-056 Diagnósticos | A2, A2·T, A2b, sectores (A2.2–A2.2e), categorías (A2.3–A2.3c), crear (A2.5) y duplicar (A2.7) |
| A5 Usuarios y roles | Lista, roles, invitar, desactivar o eliminar (con 90 días para recuperar), cambiar y asignar rol |
| E12 Colaboradores | Agregar con cargo, editar (con contraseña), desactivar, reactivar y eliminar |
| T-042, T-059, T-060, T-062, T-063 Documentos | 09, 02, 04, 06 y 10 |
| T-066 / T-088 UI/UX y frontend | `18_UI_UX.md` y `14_FRONTEND.md`, se completan cada semana |

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
./vendor/bin/sail artisan test        # 140 pruebas de Pest
./vendor/bin/sail npm test            # pruebas de Vitest
./vendor/bin/sail artisan prueba:pdf  # PDF real en storage/app/private/pruebas/
```

Para ver las pantallas de la prueba técnica, entra como un usuario con rol Administrador a:

- `/prueba-tecnica/componentes`
- `/prueba-tecnica/graficas`

## Bloqueos y decisiones pendientes

- **Bot de WhatsApp:** aplazado. El cronograma todavía lo tiene en las semanas 2, 6 y 7 (`03_ALCANCE.md`).
- **Clave de OpenAI y modelo por defecto:** sin definir; bloquean T-019 y se necesitan en la semana 5 (DEC-013, R-002).
- **Datos de NuevasTIC:** lista real de sectores y códigos CIIU, y confirmar la eliminación de cuentas con 90 días (DEC-017).
- T-046 habla de 6 sectores: el equipo quitó «Talleres» (9 de octubre) y el seeder queda con 6 (Abogados, Alojamientos, Comidas, Inmobiliarias, Médicos y Turismo). El wireframe todavía muestra Talleres en sus ejemplos.
- **Contraseña:** RN-001 pide un carácter especial que los wireframes L2 y L4 no muestran (`17_SEGURIDAD.md`).
- **Cronograma desactualizado:** todavía habla de FODA, de 3 etapas de IA y de un PDF de 4 páginas (T-098, T-099, T-113, T-122, T-127). Los requisitos vigentes dicen una sola etapa y un PDF de 3 páginas, sin FODA.
