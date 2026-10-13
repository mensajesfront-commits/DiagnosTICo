# Riesgos

**Estado:** EN CURSO (T-067). Se revisa cada semana junto con `01_ESTADO_ACTUAL.md`.

Riesgos del proyecto: qué puede salir mal, qué tan probable es, cuánto afecta y qué se hace para evitarlo. Formato: R-XXX.

- **Probabilidad e impacto:** Alta, Media o Baja.
- **Estado:** Abierto (puede pasar), Mitigado (hay medidas y se vigila), Cerrado (ya no aplica) u Ocurrido (pasó y se está manejando).

| ID | Riesgo | Probabilidad | Impacto | Estado | Responsable |
|---|---|---|---|---|---|
| R-001 | El costo de OpenAI sube más de lo previsto | Media | Media | Abierto | Luis |
| R-002 | No hay clave de OpenAI a tiempo para probar la IA | Alta | Alta | Ocurrido | Ambos |
| R-003 | La IA responde fuera del formato o de forma poco útil | Media | Alta | Abierto | Luis |
| R-004 | El bot de WhatsApp cambia o se retoma tarde | Media | Media | Mitigado | Ambos |
| R-005 | No alcanza el tiempo para las 79 vistas en 7 semanas | Alta | Alta | Abierto | Ambos |
| R-006 | El PDF depende de Chromium | Media | Media | Mitigado | Luis |
| R-007 | NuevasTIC tarda en confirmar datos pendientes | Alta | Media | Abierto | Cristian |
| R-008 | El alcance cambia durante el desarrollo | Alta | Media | Ocurrido | Ambos |
| R-009 | Se borran datos de una empresa por error | Baja | Alta | Mitigado | Luis |
| R-010 | Una sola persona conoce cada parte del código | Media | Media | Abierto | Ambos |
| R-011 | La instalación falla en el equipo de quien recibe el repositorio | Media | Alta | Mitigado | Ambos |
| R-012 | Una clave o dato privado llega al repositorio | Baja | Alta | Mitigado | Ambos |

---

### R-001 — Costo de OpenAI

- **Qué puede pasar:**
  - cada medición hace una llamada por categoría (hasta 10), más los reintentos (RN-021);
  - con muchas empresas, o con "Probar con un ejemplo" en A4, el gasto crece.
- **Medidas:**
  - modelo económico por defecto, configurable en `.env` (`OPENAI_MODEL`, DEC-002);
  - máximo 3 intentos por categoría;
  - solo se reintenta la categoría que falló;
  - el PDF no vuelve a llamar a la IA (RN-024);
  - fijar un límite de gasto en la cuenta de OpenAI.
- **[INFORMACIÓN PENDIENTE]** Presupuesto mensual que acepta NuevasTIC y quién paga la cuenta.

### R-002 — Clave de OpenAI

- **Qué pasó:** la prueba técnica de la IA (T-019) no se pudo terminar porque no hay clave (DEC-013).
- **Impacto:**
  - el hito T-038 (prueba técnica aprobada) queda abierto;
  - el formato de `analisis_categoria.respuesta_ia` sigue siendo una propuesta.
- **Medidas:**
  - el resto de la prueba técnica está hecho (permisos, gráficas y PDF);
  - el servicio de la IA se programa con una respuesta falsa en las pruebas, para no depender de la clave.
- **Siguiente paso:** NuevasTIC o el equipo crean la clave y la ponen en el `.env` local. Nunca va en el chat ni en el repositorio.

### R-003 — Respuestas de la IA

- **Qué puede pasar:**
  - JSON roto, campos que faltan o puntajes fuera de 0–100;
  - observaciones genéricas que no le sirven a la empresa.
- **Medidas:**
  - formato fijo con salida estructurada;
  - validar la respuesta antes de guardarla;
  - si no cumple, cuenta como falla y se reintenta (CU-004);
  - el prompt por capas permite ajustar por sector y por empresa (RN-022);
  - "Probar con un ejemplo" en A4 antes de cambiar el prompt.

### R-004 — Bot de WhatsApp

- **Qué puede pasar:** que el bot vuelva al alcance tarde, o que cambien las reglas de la API de Meta.
- **Medidas:**
  - está aplazado (EP-013);
  - va totalmente separado de la web (RN-029): sus tablas y rutas no tocan empresas ni mediciones, así que retomarlo no rompe lo hecho;
  - el webhook queda anotado en `15_BACKEND.md`.

### R-005 — Tiempos

- **Qué puede pasar:** son 79 vistas, la IA y el PDF, con dos personas, del 21 de septiembre al 6 de noviembre de 2026.
- **Medidas:**
  - cronograma por semanas con hitos;
  - componentes base reutilizables;
  - lo aplazado se marca y no se programa (bot, rol Consultor);
  - lo que se hizo antes de tiempo (acceso, A2, A5, E12) libera las semanas 4 a 6.
- **Señal de alerta:** dos semanas seguidas con tareas "En curso" sin cerrar.

### R-006 — Dependencia de Chromium

- **Qué puede pasar:**
  - el PDF necesita Chromium y Node (Browsershot);
  - si faltan o cambia su versión, no se genera el PDF.
- **Medidas:**
  - Chromium instalado en la imagen de Sail (DEC-011, `docker/8.4/Dockerfile`);
  - el error "Target closed" ya está resuelto (ISSUE-007);
  - el PDF se genera una vez y se guarda (RN-024);
  - si falla, el resultado se publica igual y el PDF se reintenta (CU-004).
- **Pendiente:** ISSUE-009 (la imagen no se pudo construir en el entorno de Claude). En el equipo del equipo sí funciona.

### R-007 — Datos pendientes de NuevasTIC

- **Qué falta confirmar:**
  - la lista real de sectores;
  - los códigos CIIU de cada sector;
  - los textos de los correos;
  - el presupuesto de la IA;
  - la eliminación de cuentas (DEC-017).
- **Medidas:**
  - nada se inventa: se marca **[INFORMACIÓN PENDIENTE]** y se usan datos de ejemplo cargados por seeders, fáciles de cambiar.

### R-008 — Cambios de alcance

- **Qué pasó:** durante la semana 3 se agregaron:
  - el registro en dos pasos con actividad CIIU (DEC-015);
  - la ubicación en cascada (DEC-016);
  - la eliminación de cuentas con 90 días (DEC-017);
  - el cargo de los colaboradores.
- **Medidas:**
  - cada cambio queda como decisión en `22_DECISIONES_TECNICAS.md` y en `24_CAMBIOS_Y_VERSIONES.md`;
  - `05_REQUISITOS_FUNCIONALES.md` sigue siendo la fuente de verdad del alcance.

### R-009 — Borrar datos por error

- **Medidas:**
  - **sectores y categorías con datos:** se desactivan o se archivan (RN-008, RN-009);
  - **versiones y resultados publicados:** no se tocan (RN-010, RN-023);
  - **eliminar una cuenta:** pide el correo exacto en A5 y dos botones en E12, y se puede recuperar durante 90 días (DEC-017).
- **[FUNCIONALIDAD POR DEFINIR]** Al borrar para siempre una empresa se borran sus mediciones; falta decidir si se conservan sin nombre para las estadísticas.

### R-010 — Conocimiento repartido

- **Qué puede pasar:** Cristian conoce el frontend y Luis el backend; si uno falta, el otro se atrasa.
- **Medidas:**
  - todo cambio pasa por pull request con revisión del otro;
  - la documentación de `docs/` se actualiza en cada tarea;
  - `CLAUDE.md` resume el stack y las reglas.

### R-011 — Instalación en otro equipo

- **Qué puede pasar:** el sistema no se despliega; se entrega el repositorio, así que quien lo reciba debe poder instalarlo.
- **Medidas:**
  - README paso a paso;
  - Sail con PHP 8.4 y Chromium;
  - `.env.example` comentado;
  - seeders con el Administrador inicial;
  - CI que corre las pruebas en cada pull request (`composer ci:check`).

### R-012 — Claves en el repositorio

- **Medidas:**
  - las claves van solo en `.env`, que no se sube;
  - `.env.example` lleva solo marcadores;
  - el Administrador inicial sale de `ADMIN_EMAIL` y `ADMIN_PASSWORD`;
  - ya no hay cuentas de demostración con contraseñas conocidas.
