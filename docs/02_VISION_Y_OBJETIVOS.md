# Visión y objetivos

**Estado:** REQUIERE VALIDACIÓN (T-059). Las metas de éxito son una propuesta del equipo y deben confirmarse con NuevasTIC.

**Fuentes:** `01_CONTEXTO_PROYECTO.md`, `03_ALCANCE.md`, `05_REQUISITOS_FUNCIONALES.md` y la propuesta técnica (29 sep 2026).

## Visión

> Que NuevasTIC sepa, con un mismo criterio para todas las empresas de un sector, en qué punto está el marketing digital de cada empresa que acompaña. Que cada empresa entienda su resultado y sepa qué mejorar primero, y que ambos vean cómo evoluciona entre una medición y la siguiente.

El sistema se llama **Captter**.

## El problema que resuelve

| Hoy | Con Captter |
|---|---|
| Cada diagnóstico depende de quién lo haga y de cómo lo pregunte. | Cada sector tiene su diagnóstico publicado; todas sus empresas responden las mismas preguntas con los mismos puntajes (RN-010, RN-014). |
| Es difícil comparar una empresa consigo misma en el tiempo. | Cada medición guarda su resultado inmutable y muestra la variación frente a la anterior (RN-020, RN-023). |
| El análisis y las recomendaciones toman tiempo del equipo. | La IA analiza cada categoría y escribe observaciones y una recomendación concreta (RN-021, RN-022). |
| La empresa recibe el resultado sin un formato fijo. | La empresa ve su resultado en pantalla y recibe un PDF de 3 páginas (RN-024). |

**[INFORMACIÓN PENDIENTE]** Cómo hace hoy NuevasTIC los diagnósticos (encuestas, hojas de cálculo, entrevistas) y cuánto tiempo le toman. Sin ese dato no se puede medir cuánto tiempo ahorra el sistema.

## Objetivo general

Construir y entregar, el **6 de noviembre de 2026**, un sistema web en el que:

- el Administrador de NuevasTIC configura diagnósticos de marketing digital por sector y asigna mediciones;
- las empresas los responden;
- el sistema calcula el puntaje, la IA analiza cada categoría y se publica un resultado con su informe PDF.

Se entrega en un repositorio de GitHub que se instala desde cero con el README.

## Objetivos específicos

Cada objetivo se cumple con una o más épicas de `05_REQUISITOS_FUNCIONALES.md`.

| # | Objetivo | Épicas | Cómo se comprueba |
|---|---|---|---|
| OE-1 | Dar acceso seguro a cada tipo de cuenta, con sus permisos. | EP-001, EP-008, EP-009 | Las pruebas de acceso y de roles pasan (`tests/Feature/Auth`, `RolesYPermisosTest`) |
| OE-2 | Permitir que el Administrador organice sectores, categorías y diagnósticos versionados. | EP-003, EP-004, EP-005 | Un diagnóstico se crea, se edita, se publica como v1 y luego como v2 sin cambiar las mediciones hechas |
| OE-3 | Asignar mediciones a las empresas y hacerles seguimiento. | EP-002, EP-006 | Una medición pasa por No iniciada → En curso → Enviada → Terminada, y una sin responder vence a tiempo |
| OE-4 | Que la empresa responda a su ritmo y reciba su resultado. | EP-010, EP-011 | Las respuestas se guardan solas; después de enviar se ve el resultado, el historial y el PDF |
| OE-5 | Calcular un puntaje justo y analizar con IA cada categoría. | EP-007, EP-012 | El puntaje sigue RN-018 y RN-019; si la IA falla, se reintenta solo esa categoría (RN-021) |
| OE-6 | Entregar un proyecto mantenible. | — | Documentación de `docs/` completa, pruebas automáticas y CI en verde, instalación desde cero con el README |

## Qué no es

- No publica el sistema en un servidor: se entrega el repositorio (`03_ALCANCE.md`).
- No hace análisis FODA ni compara con promedios de otras empresas (RN-028).
- No incluye el bot de WhatsApp, que está aplazado, ni el simulador de inversión.

## Cómo se mide el éxito

### Del proyecto (entrega)

| Indicador | Meta | Dónde se ve |
|---|---|---|
| Historias del alcance terminadas | Las 79 historias web de `03_ALCANCE.md` (84 menos las 5 del bot) | Tablero de Trello |
| Pruebas automáticas | Todas en verde en el último commit de `main` | GitHub Actions |
| Instalación desde cero | Otra persona instala con el README sin ayuda | T-021, T-154 |
| Fidelidad con el diseño | Cada pantalla comparada con su wireframe; diferencias anotadas | `18_UI_UX.md` |

### Del uso del sistema (propuesta, REQUIERE VALIDACIÓN)

Se pueden medir con los datos que el sistema ya guarda:

| Indicador | Cómo se calcula | Meta propuesta |
|---|---|---|
| Mediciones terminadas a tiempo | Terminadas antes de la fecha límite ÷ asignadas | **[INFORMACIÓN PENDIENTE]** |
| Tiempo para responder | Desde "No iniciada" hasta "Enviada" | **[INFORMACIÓN PENDIENTE]** |
| Análisis de IA sin falla | Categorías analizadas al primer intento ÷ total | 95 % o más (propuesta) |
| Mejora entre mediciones | Promedio de la variación del puntaje total (RN-020) | **[INFORMACIÓN PENDIENTE]** |

Las metas marcadas como pendientes las fija NuevasTIC (PA-008).
