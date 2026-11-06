# Visión y objetivos

**Estado:** COMPLETO (T-059), aprobado por NuevasTIC el 5 de noviembre de 2026, incluidas las metas de éxito.

**Fuentes:** `01_CONTEXTO_PROYECTO.md`, `03_ALCANCE.md`, `05_REQUISITOS_FUNCIONALES.md` y la propuesta técnica (29 sep 2026).

## Visión

> Que NuevasTIC sepa, con un mismo criterio para todas las empresas de un sector, en qué punto está el marketing digital de cada empresa que acompaña. Que cada empresa entienda su resultado y sepa qué mejorar primero, y que ambos vean cómo evoluciona entre una medición y la siguiente.

El sistema se llama **Captter**.

## El problema que resuelve

| Hoy | Con Captter |
|---|---|
| Cada diagnóstico depende de quién lo haga y de cómo lo pregunte. | Cada sector tiene su diagnóstico publicado; todas sus empresas responden las mismas preguntas con los mismos puntajes (RN-010, RN-014). |
| Es difícil comparar una empresa consigo misma en el tiempo. | Cada medición guarda su resultado inmutable y muestra la variación frente a la anterior (RN-020, RN-023). |
| El análisis y las recomendaciones toman tiempo del equipo. | La IA analiza cada categoría y escribe un diagnóstico, lo que encontró con el método elegido y 2 o 3 recomendaciones con pasos, plazo e indicador (RN-021, RN-022). |
| La empresa recibe el resultado sin un formato fijo. | La empresa ve su resultado en pantalla y recibe un resumen PDF corto con lo que dijo la IA. El equipo de NuevasTIC y el Consultor tienen además el informe PDF completo (RN-024). |

**[INFORMACIÓN PENDIENTE]** Cómo hace hoy NuevasTIC los diagnósticos (encuestas, hojas de cálculo, entrevistas) y cuánto tiempo le toman. Sin ese dato no se puede medir cuánto tiempo ahorra el sistema.

## Objetivo general

Construir y entregar, el **6 de noviembre de 2026**, un sistema web en el que:

- el Administrador de NuevasTIC configura diagnósticos de marketing digital por sector y asigna mediciones;
- las empresas los responden;
- el sistema calcula el puntaje, la IA analiza cada categoría y se publica un resultado con sus dos PDF (resumen para la empresa e informe completo).

Se entrega en un repositorio de GitHub que se instala desde cero con el README, y se despliega en un servidor (cambio del 4 de noviembre, DEC-006). **[INFORMACIÓN PENDIENTE]** Servidor y dominio.

## Objetivos específicos

Cada objetivo se cumple con una o más épicas de `05_REQUISITOS_FUNCIONALES.md`.

| # | Objetivo | Épicas | Cómo se comprueba |
|---|---|---|---|
| OE-1 | Dar acceso seguro a cada tipo de cuenta, con sus permisos. | EP-001, EP-008, EP-009 | Las pruebas de acceso y de roles pasan (`tests/Feature/Auth`, `RolesYPermisosTest`) |
| OE-2 | Permitir que el Administrador organice sectores, categorías y diagnósticos versionados. | EP-003, EP-004, EP-005 | Un diagnóstico se crea, se edita, se publica como v1 y luego como v2 sin cambiar las mediciones hechas |
| OE-3 | Asignar mediciones a las empresas y hacerles seguimiento. | EP-002, EP-006 | Una medición pasa por No iniciada → En curso → Enviada → Terminada, y una sin responder vence a tiempo |
| OE-4 | Que la empresa responda a su ritmo y reciba su resultado. | EP-010, EP-011 | Las respuestas se guardan solas; después de enviar se ve el resultado, el historial y el PDF |
| OE-7 | Que un consultor externo acompañe a sus empresas sin poder cambiar nada (3 de noviembre). | EP-014 | El Consultor ve solo las empresas vinculadas; otra empresa le da 404 y las acciones se rechazan (`ConsultorTest`) |
| OE-5 | Calcular un puntaje justo y analizar con IA cada categoría. | EP-007, EP-012 | El puntaje sigue RN-018 y RN-019; si la IA falla, se reintenta solo esa categoría (RN-021) |
| OE-6 | Entregar un proyecto mantenible. | — | Documentación de `docs/` completa, pruebas automáticas y CI en verde, instalación desde cero con el README |

## Qué no es

- No compara con promedios de otras empresas (RN-028). La IA puede usar DOFA y otros métodos para analizar cada categoría.
- No incluye el bot de WhatsApp, que lo hizo otra persona del equipo fuera de este repositorio, ni el simulador de inversión.

## Cómo se mide el éxito

### Del proyecto (entrega)

| Indicador | Meta | Dónde se ve |
|---|---|---|
| Historias del alcance terminadas | Las 81 historias web de `03_ALCANCE.md` (86 menos las 5 del bot). **Cumplido** en la entrega `v1.0.0` | `05_REQUISITOS_FUNCIONALES.md`, sección 8 |
| Pruebas automáticas | Todas en verde en el último commit de `main`. **Cumplido:** 385 de Pest y 47 de Vitest | GitHub Actions |
| Instalación desde cero | Otra persona instala con el README sin ayuda | T-021, T-154 |
| Fidelidad con el diseño | Cada pantalla comparada con su wireframe; diferencias anotadas | `18_UI_UX.md` |

### Del uso del sistema (aprobado por NuevasTIC el 5 de noviembre de 2026)

Se pueden medir con los datos que el sistema ya guarda:

| Indicador | Cómo se calcula | Meta propuesta |
|---|---|---|
| Mediciones terminadas a tiempo | Terminadas antes de la fecha límite ÷ asignadas | **[INFORMACIÓN PENDIENTE]** |
| Tiempo para responder | Desde "No iniciada" hasta "Enviada" | **[INFORMACIÓN PENDIENTE]** |
| Análisis de IA sin falla | Categorías analizadas al primer intento ÷ total | 95 % o más (propuesta) |
| Mejora entre mediciones | Promedio de la variación del puntaje total (RN-020) | **[INFORMACIÓN PENDIENTE]** |

Las metas marcadas como pendientes las fija NuevasTIC (PA-008).
