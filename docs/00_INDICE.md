# Índice de la documentación

**Estado:** COMPLETO (T-027). Todos los documentos quedaron cerrados para la entrega `v1.0.0` (5 de noviembre de 2026).

Lista de los documentos del proyecto con su estado. Cada documento se escribió en la semana del cronograma que le correspondía. Al cerrar el proyecto, todos debían quedar en COMPLETO o NO APLICA (T-160): se cumplió el 5 de noviembre de 2026, con la versión `v1.0.0`.

**Por dónde empezar:**

- **Qué es y en qué quedó:** `01_CONTEXTO_PROYECTO.md` y `01_ESTADO_ACTUAL.md`.
- **Qué hace el sistema:** `05_REQUISITOS_FUNCIONALES.md` (fuente de verdad), `29_FUNCIONALIDADES.md` y `31_MANUAL_DE_USUARIO.md`.
- **Cómo instalarlo y desplegarlo:** `../README.md`, `27_ENTORNOS.md` y `20_DESPLIEGUE.md`.
- **Qué cambió y cuándo:** `24_CAMBIOS_Y_VERSIONES.md`.

**Estados**

- **COMPLETO:** terminado y revisado.
- **EN CURSO:** tiene contenido y sigue en trabajo.
- **REQUIERE VALIDACIÓN:** escrito, pero falta que NuevasTIC o el equipo lo confirmen. (Ya no queda ninguno.)
- **PENDIENTE:** todavía no existe el archivo. (Ya no queda ninguno.)
- **NO APLICA:** no corresponde a este proyecto.

| N.º | Documento | Estado | Semana | Tarea |
|---|---|---|---|---|
| 00 | [Índice](00_INDICE.md) | COMPLETO | 2 | T-027 |
| 01 | [Contexto del proyecto](01_CONTEXTO_PROYECTO.md) | COMPLETO | 2 | T-028 |
| 01 | [Estado actual](01_ESTADO_ACTUAL.md) | COMPLETO | todas | T-037 |
| 02 | [Visión y objetivos](02_VISION_Y_OBJETIVOS.md) | COMPLETO | 3 | T-059 |
| 03 | [Alcance](03_ALCANCE.md) | COMPLETO | 2 | T-029 |
| 04 | [Stakeholders](04_STAKEHOLDERS.md) | COMPLETO | 3 | T-060 |
| 05 | [Requisitos funcionales](05_REQUISITOS_FUNCIONALES.md) | COMPLETO | 2 | T-003, T-061 |
| 06 | [Requisitos no funcionales](06_REQUISITOS_NO_FUNCIONALES.md) | COMPLETO | 3 | T-062 |
| 07 | [Reglas de negocio](07_REGLAS_DE_NEGOCIO.md) | COMPLETO | 4–6 | T-086 |
| 08 | [Casos de uso](08_CASOS_DE_USO.md) | COMPLETO | 3 | T-041 |
| 09 | [Flujos del sistema](09_FLUJOS_DEL_SISTEMA.md) | COMPLETO | 3 | T-042 |
| 10 | [Arquitectura](10_ARQUITECTURA.md) | COMPLETO | 3 | T-063 |
| 11 | [Diseño técnico](11_DISENO_TECNICO.md) | COMPLETO | 4–6 | T-071 |
| 12 | [Base de datos](12_BASE_DE_DATOS.md) | COMPLETO | 2–3 | T-007, T-040, T-064 |
| 13 | [API e integraciones](13_API_E_INTEGRACIONES.md) | COMPLETO | 5 | T-113 |
| 14 | [Frontend](14_FRONTEND.md) | COMPLETO | 3–6 | T-088 |
| 15 | [Backend](15_BACKEND.md) | COMPLETO | 3 | T-043 |
| 16 | [Infraestructura](16_INFRAESTRUCTURA.md) | COMPLETO | 7 | T-154 |
| 17 | [Seguridad](17_SEGURIDAD.md) | COMPLETO | 2–3 | T-006, T-065 |
| 18 | [UI/UX](18_UI_UX.md) | COMPLETO | 3–6 | T-066, T-089 |
| 19 | [Pruebas](19_PRUEBAS.md) | COMPLETO | 6–7 | T-119 |
| 20 | [Despliegue](20_DESPLIEGUE.md) | COMPLETO | 7 | T-155 |
| 21 | [Mantenimiento](21_MANTENIMIENTO.md) | COMPLETO | 7 | T-156 |
| 22 | [Decisiones técnicas](22_DECISIONES_TECNICAS.md) | COMPLETO | 2 | T-034 |
| 23 | [Problemas conocidos](23_PROBLEMAS_CONOCIDOS.md) | COMPLETO | 2 | T-035 |
| 24 | [Cambios y versiones](24_CAMBIOS_Y_VERSIONES.md) | COMPLETO | todas | T-037 |
| 25 | [Roadmap](25_ROADMAP.md) | COMPLETO | 7 | T-157 |
| 26 | [Glosario](26_GLOSARIO.md) | COMPLETO | 2 | T-036 |
| 27 | [Entornos](27_ENTORNOS.md) | COMPLETO | 2 | T-032 |
| 28 | [Convenciones de desarrollo](28_CONVENCIONES_DESARROLLO.md) | COMPLETO | 2 | T-005, T-033 |
| 29 | [Funcionalidades](29_FUNCIONALIDADES.md) | COMPLETO | 4 | T-087 |
| 30 | [Riesgos](30_RIESGOS.md) | COMPLETO | 3 | T-067 |
| 31 | [Manual de usuario](31_MANUAL_DE_USUARIO.md) | COMPLETO | 6–7 | T-142, T-163 |

Todos los documentos tienen archivo y quedaron en COMPLETO para la entrega `v1.0.0` (5 de noviembre de 2026). Los de negocio los aprobó NuevasTIC ese día. Las marcas **[INFORMACIÓN PENDIENTE]** que quedan dentro de algunos documentos (por ejemplo, quién mantiene el sistema) son datos que faltan, no revisiones pendientes.

## Otros archivos de la raíz

| Archivo | Para qué |
|---|---|
| [`../README.md`](../README.md) | Requisitos e instalación paso a paso. |
| [`../CLAUDE.md`](../CLAUDE.md) | Stack, comandos y reglas del proyecto. |
| [`../.env.example`](../.env.example) | Variables de entorno, con un comentario en cada una. Solo marcadores: las claves reales van en el `.env` de cada equipo o del servidor, que no se sube. |

## Notas

- **[INCONSISTENCIA DETECTADA]** El cronograma pone dos documentos con el número 01 (`01_CONTEXTO_PROYECTO.md` y `01_ESTADO_ACTUAL.md`). Se mantienen así porque son los nombres que usan las tareas. Si se renumera, hay que actualizar las tareas que los citan.
- **[INFORMACIÓN PENDIENTE]** T-026 pide copiar al repositorio la "base de documentación" (`CLAUDE.md`, `.claude/` y `docs/`). Esa base no llegó al repositorio. Los documentos se crearon con los nombres del cronograma, y la carpeta `.claude/` no se creó porque no se sabe qué debía contener.
