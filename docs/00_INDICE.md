# Índice de la documentación

**Estado:** EN CURSO (T-027)

Lista de los documentos del proyecto con su estado. Cada documento se escribe en la semana del cronograma que le corresponde. Al cerrar el proyecto, todos deben quedar en COMPLETO o NO APLICA (T-160).

**Estados**

- **COMPLETO:** terminado y revisado.
- **EN CURSO:** tiene contenido y sigue en trabajo.
- **REQUIERE VALIDACIÓN:** escrito, pero falta que NuevasTIC o el equipo lo confirmen.
- **PENDIENTE:** todavía no existe el archivo.
- **NO APLICA:** no corresponde a este proyecto.

| N.º | Documento | Estado | Semana | Tarea |
|---|---|---|---|---|
| 00 | [Índice](00_INDICE.md) | EN CURSO | 2 | T-027 |
| 01 | [Contexto del proyecto](01_CONTEXTO_PROYECTO.md) | EN CURSO | 2 | T-028 |
| 01 | [Estado actual](01_ESTADO_ACTUAL.md) | EN CURSO | todas | T-037 |
| 02 | Visión y objetivos (`02_VISION_Y_OBJETIVOS.md`) | PENDIENTE | 3 | T-059 |
| 03 | [Alcance](03_ALCANCE.md) | REQUIERE VALIDACIÓN | 2 | T-029 |
| 04 | Stakeholders (`04_STAKEHOLDERS.md`) | PENDIENTE | 3 | T-060 |
| 05 | [Requisitos funcionales](05_REQUISITOS_FUNCIONALES.md) | REQUIERE VALIDACIÓN | 2 | T-003, T-061 |
| 06 | Requisitos no funcionales (`06_REQUISITOS_NO_FUNCIONALES.md`) | PENDIENTE | 3 | T-062 |
| 07 | Reglas de negocio (`07_REGLAS_DE_NEGOCIO.md`) | PENDIENTE | 4–6 | T-086 |
| 08 | Casos de uso (`08_CASOS_DE_USO.md`) | PENDIENTE | 3 | T-041 |
| 09 | Flujos del sistema (`09_FLUJOS_DEL_SISTEMA.md`) | PENDIENTE | 3 | T-042 |
| 10 | Arquitectura (`10_ARQUITECTURA.md`) | PENDIENTE | 3 | T-063 |
| 11 | Diseño técnico (`11_DISENO_TECNICO.md`) | PENDIENTE | 4–6 | T-071 |
| 12 | [Base de datos](12_BASE_DE_DATOS.md) | EN CURSO | 2–3 | T-007, T-040 |
| 13 | API e integraciones (`13_API_E_INTEGRACIONES.md`) | PENDIENTE | 5 | T-113 |
| 14 | Frontend (`14_FRONTEND.md`) | PENDIENTE | 4 | T-088 |
| 15 | Backend (`15_BACKEND.md`) | PENDIENTE | 3 | T-043 |
| 16 | Infraestructura (`16_INFRAESTRUCTURA.md`) | PENDIENTE | 7 | T-154 |
| 17 | [Seguridad](17_SEGURIDAD.md) | EN CURSO | 2–3 | T-006, T-065 |
| 18 | UI/UX (`18_UI_UX.md`) | PENDIENTE | 3 | T-066 |
| 19 | Pruebas (`19_PRUEBAS.md`) | PENDIENTE | 6–7 | T-119 |
| 20 | Despliegue (`20_DESPLIEGUE.md`) | PENDIENTE (casi todo NO APLICA) | 7 | T-155 |
| 21 | Mantenimiento (`21_MANTENIMIENTO.md`) | PENDIENTE | 7 | T-156 |
| 22 | [Decisiones técnicas](22_DECISIONES_TECNICAS.md) | EN CURSO | 2 | T-034 |
| 23 | [Problemas conocidos](23_PROBLEMAS_CONOCIDOS.md) | EN CURSO | 2 | T-035 |
| 24 | [Cambios y versiones](24_CAMBIOS_Y_VERSIONES.md) | EN CURSO | todas | T-037 |
| 25 | Roadmap (`25_ROADMAP.md`) | PENDIENTE | 7 | T-157 |
| 26 | [Glosario](26_GLOSARIO.md) | EN CURSO | 2 | T-036 |
| 27 | [Entornos](27_ENTORNOS.md) | EN CURSO | 2 | T-032 |
| 28 | [Convenciones de desarrollo](28_CONVENCIONES_DESARROLLO.md) | EN CURSO | 2 | T-005, T-033 |
| 29 | Funcionalidades (`29_FUNCIONALIDADES.md`) | PENDIENTE | 4 | T-087 |
| 30 | Riesgos (`30_RIESGOS.md`) | PENDIENTE | 3 | T-067 |
| 31 | Manual de usuario (`31_MANUAL_DE_USUARIO.md`) | PENDIENTE | 6–7 | T-142, T-163 |

Los documentos PENDIENTE no tienen archivo todavía, por eso no llevan enlace. Se crean en la semana indicada.

## Otros archivos de la raíz

| Archivo | Para qué |
|---|---|
| [`../README.md`](../README.md) | Requisitos e instalación paso a paso. |
| [`../CLAUDE.md`](../CLAUDE.md) | Stack, comandos y reglas del proyecto. |
| [`../.env.example`](../.env.example) | Variables de entorno, con un comentario en cada una. |

## Notas

- **[INCONSISTENCIA DETECTADA]** El cronograma pone dos documentos con el número 01 (`01_CONTEXTO_PROYECTO.md` y `01_ESTADO_ACTUAL.md`). Se mantienen así porque son los nombres que usan las tareas. Si se renumera, hay que actualizar las tareas que los citan.
- **[INFORMACIÓN PENDIENTE]** T-026 pide copiar al repositorio la "base de documentación" (`CLAUDE.md`, `.claude/` y `docs/`). Esa base no llegó al repositorio. Los documentos se crearon con los nombres del cronograma, y la carpeta `.claude/` no se creó porque no se sabe qué debía contener.
