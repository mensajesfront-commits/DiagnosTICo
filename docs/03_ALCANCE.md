# Alcance

**Estado:** REQUIERE VALIDACIÓN (T-029)

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

En total son 13 épicas y 84 historias, con 330 puntos estimados.

## Qué no entra

| Qué | Por qué |
|---|---|
| Publicar el sistema en un servidor | Se entrega el repositorio funcionando en local (Sail) con un README para instalarlo. `20_DESPLIEGUE.md` lo marcará NO APLICA. |
| Análisis FODA y comparación con promedios | Decisión confirmada (RN-028). La propuesta técnica todavía los menciona (ver INC-001 en `05_REQUISITOS_FUNCIONALES.md`). |
| Roles Consultor y Usuario | Quedan para un posible futuro. |
| Simulador de inversión | No existe en el diseño. |
| Bot de WhatsApp (HU-071 a HU-075) | **Aplazado** por decisión de Cristian. Ver la sección siguiente. |

## Bot de WhatsApp: REQUIERE VALIDACIÓN

La propuesta técnica y el cronograma (T-020, T-125, T-135, T-150) incluyen el bot, pero `05_REQUISITOS_FUNCIONALES.md` lo deja **aplazado**. Si se retoma, el bot:

- es totalmente separado de la web: no tiene cuentas, mediciones ni PDF (RN-029);
- hace como máximo 10 preguntas configurables, solo de texto;
- retoma la conversación desde la última pregunta;
- califica con los puntajes de las opciones, sin IA.

Falta definir el texto del saludo y cómo se configuran las preguntas (PA-001 y PA-002).

**[INCONSISTENCIA DETECTADA]** El cronograma todavía tiene tareas del bot en las semanas 2, 6 y 7. Hay que decidir si se sacan o se mantienen como opcionales.

## Supuestos del alcance

- La clave de la API de OpenAI y su saldo los aporta NuevasTIC. **[INFORMACIÓN PENDIENTE]** Falta acordarlo (propuesta técnica, sección 5).
- El contenido real de los diagnósticos (preguntas, opciones y puntajes por sector) lo entrega NuevasTIC en la semana 4 (T-072).
