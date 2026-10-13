# Contexto del proyecto

**Estado:** EN CURSO (T-028)

**Fuentes:** "Propuesta técnica de desarrollo" (29 sep 2026), `05_REQUISITOS_FUNCIONALES.md` y los wireframes.

## Quién lo pide

**NuevasTIC** acompaña a las empresas en su marketing digital. El contacto de soporte que aparece en los wireframes es `soporte@nuevastic.co`.

**[INFORMACIÓN PENDIENTE]** Falta la descripción oficial de NuevasTIC: a qué se dedica, cuántas empresas atiende y quién es la persona responsable del proyecto. Debe tomarse de la descripción del proyecto que entregó NuevasTIC.

## El problema

NuevasTIC necesita saber en qué punto está el marketing digital de cada empresa que acompaña, con un criterio igual para todas las de un mismo sector, y ver cómo evoluciona cada una con el tiempo.

**[INFORMACIÓN PENDIENTE]** Falta saber cómo lo hace hoy (encuestas, hojas de cálculo, entrevistas) y cuánto tiempo le toma. Eso sirve para medir el éxito en `02_VISION_Y_OBJETIVOS.md`.

## La solución

Un sistema web con dos lados:

- **El Administrador de NuevasTIC:**
  - Organiza las empresas por sector.
  - Arma un diagnóstico por sector con categorías (por ejemplo: análisis del mercado, presencia en línea, contenido) y les da a las categorías una importancia que suma 100%.
  - Publica versiones del diagnóstico y asigna mediciones a las empresas, con fecha límite.
  - Configura cómo analiza la IA.
  - Administra las cuentas.
- **La empresa (y sus colaboradores):**
  - Responde el diagnóstico a su ritmo; las respuestas se guardan solas.
  - Revisa y envía.
  - Recibe su resultado: puntaje de 0 a 100, nivel, puntaje por categoría, observaciones y recomendaciones de la IA, y un informe PDF de 3 páginas.
  - Puede repetir la medición y ver su evolución.

Los cuatro niveles del resultado son:

| Nivel | Puntaje |
|---|---|
| Crítico | 0–29 |
| Se puede mejorar | 30–59 |
| Vas en buen camino | 60–79 |
| Sigue así | 80–100 |

## Decisiones confirmadas

Tomadas de `05_REQUISITOS_FUNCIONALES.md`:

- **Roles:**
  - Hay tres: Administrador, Empresa y Colaborador.
  - Consultor y Usuario quedan para un posible futuro.
- **Análisis de la IA:**
  - Se hace en una sola etapa por cada categoría.
  - No hay análisis FODA ni comparación con promedios.
- **Estados de la medición:**
  - Son cinco: No iniciada, En curso, Enviada, Terminada y Vencida.
  - Si una medición vence, la empresa pide otra por correo.
- **Bot de WhatsApp:** es totalmente separado de la web y queda **aplazado**.
- **Simulador de inversión:** no existe.

## Tecnologías exigidas

NuevasTIC exige Laravel, Vue.js, Tailwind CSS y PostgreSQL. El resto del stack está en `22_DECISIONES_TECNICAS.md` y en `../CLAUDE.md`.

## Qué se entrega

Un repositorio en GitHub con:

- el código;
- las instrucciones de instalación;
- la base de datos con datos iniciales;
- las pruebas automáticas;
- esta documentación.

No incluye publicar el sistema en un servidor (ver `03_ALCANCE.md`).
