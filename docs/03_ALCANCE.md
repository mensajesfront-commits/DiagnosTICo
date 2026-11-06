# Alcance

**Estado:** COMPLETO (T-029), aprobado por NuevasTIC el 5 de noviembre de 2026.

**Fuentes:**

- Lo que confirmó Cristian: `05_REQUISITOS_FUNCIONALES.md`, secciones 1 y 15.
- La propuesta técnica (29 sep 2026).
- La reunión con NuevasTIC (T-008).

**[INFORMACIÓN PENDIENTE]** Las notas de la reunión T-008 no están en el repositorio. Hay que agregarlas aquí: qué se confirmó del bot, quién entrega las preguntas de cada sector y quién entrega el prompt base.

## Qué entra

| Módulo | Pantallas | Historias |
|---|---|---|
| Acceso | L1–L4 | HU-001 a HU-005 |
| Inicio del Administrador | A1, A1b–A1e | HU-006 a HU-009 |
| Sectores | A2.2, A2.2a–e, A2b | HU-012 a HU-016 |
| Catálogo de categorías | A2.3, A2.3b, A2.3c | HU-017 a HU-019 |
| Diagnósticos (editor, importancia, versiones, vista previa, resultado de ejemplo) | A2, A2·T, A2.1–A2.7 | HU-010, HU-011, HU-020 a HU-030 |
| Empresas y mediciones | A3, A3.1–A3.5 | HU-031 a HU-040, HU-083 |
| Configuración de la IA | A4–A4.9 | HU-041 a HU-045, HU-080 |
| Usuarios y roles, "Ver como" | A5–A5.5 | HU-046 a HU-054 |
| Mi perfil | A6, E11 | HU-055, HU-056 |
| Experiencia de la empresa | E1–E10 | HU-057 a HU-067, HU-082 |
| Colaboradores | E12–E12.3 | HU-076 a HU-079 |
| Cálculo, IA y tareas automáticas | — | HU-068 a HU-070, HU-081, HU-084 |
| Consultor (desde el 3 de noviembre) | C1, C2 y A3–A3.5 en solo lectura | HU-085, HU-086 |

En total son 14 épicas y 86 historias, con 340 puntos estimados. Las 81 de la web están hechas en este repositorio; las 5 del bot (EP-013) se hicieron fuera.

## Qué no entra

| Qué | Por qué |
|---|---|
| Publicar el sistema en un servidor | **Cambió el 4 de noviembre de 2026: sí se despliega** (DEC-006). Pasos en `20_DESPLIEGUE.md`. **[INFORMACIÓN PENDIENTE]** Servidor y dominio. |
| Análisis FODA y comparación con promedios | Decisión confirmada (RN-028). La propuesta técnica todavía los menciona (ver INC-001 en `05_REQUISITOS_FUNCIONALES.md`). |
| Rol Usuario | Queda para un posible futuro. El rol Consultor **sí entra** desde el 3 de noviembre, solo de lectura (EP-014, RN-030). |
| Simulador de inversión | No existe en el diseño. |
| Bot de WhatsApp (HU-071 a HU-075) | **Terminado fuera de este repositorio** por otra persona del equipo (confirmado el 4 de noviembre de 2026). Ver la sección siguiente. |

## Bot de WhatsApp: terminado fuera de este repositorio

Otra persona del equipo lo terminó (confirmado el 4 de noviembre de 2026). No está en este repositorio. **[INFORMACIÓN PENDIENTE]** Si se conecta con este sistema y cómo. Según `05_REQUISITOS_FUNCIONALES.md`, el bot:

- es totalmente separado de la web: no tiene cuentas, mediciones ni PDF (RN-029);
- hace como máximo 10 preguntas configurables, solo de texto;
- retoma la conversación desde la última pregunta;
- califica con los puntajes de las opciones, sin IA.

El texto del saludo y cómo se configuran las preguntas (PA-001 y PA-002) los definió el bot.

**[INCONSISTENCIA DETECTADA]** El cronograma todavía tiene tareas del bot en las semanas 2, 6 y 7. Ya no aplican a este repositorio.

## Supuestos del alcance

- La clave de la API de OpenAI y su saldo los aporta NuevasTIC, con el modelo gpt-4.1-mini (acordado el 23 de octubre de 2026).
- El contenido real de los diagnósticos (preguntas, opciones y puntajes por sector) lo genera NuevasTIC con IA y lo carga a mano en la aplicación (confirmado el 4 de noviembre de 2026).
