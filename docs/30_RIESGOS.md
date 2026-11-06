# Riesgos

**Estado:** COMPLETO (T-067). Riesgos en la entrega `v1.0.0`.

Riesgos del proyecto: qué puede salir mal, qué tan probable es, cuánto afecta y qué se hace para evitarlo. Formato: R-XXX.

- **Probabilidad e impacto:** Alta, Media o Baja.
- **Estado:** Abierto (puede pasar), Mitigado (hay medidas y se vigila), Cerrado (ya no aplica) u Ocurrido (pasó y se está manejando).

| ID | Riesgo | Probabilidad | Impacto | Estado | Responsable |
|---|---|---|---|---|---|
| R-001 | El costo de OpenAI sube más de lo previsto | Media | Media | Abierto | Luis |
| R-002 | No hay clave de OpenAI a tiempo para probar la IA | Alta | Alta | Cerrado (flujo verificado con la clave real el 5 de noviembre) | Ambos |
| R-003 | La IA responde fuera del formato o de forma poco útil | Media | Alta | Abierto | Luis |
| R-004 | El bot de WhatsApp cambia o se retoma tarde | Media | Media | Cerrado (terminado fuera de este repositorio) | Ambos |
| R-005 | No alcanza el tiempo para las 79 vistas en 7 semanas | Alta | Alta | Cerrado (entrega `v1.0.0` el 5 de noviembre) | Ambos |
| R-006 | El PDF depende de Chromium | Media | Media | Mitigado | Luis |
| R-007 | NuevasTIC tarda en confirmar datos pendientes | Alta | Media | Mitigado (aprobó la documentación el 5 de noviembre; faltan servidor y dominio) | Cristian |
| R-008 | El alcance cambia durante el desarrollo | Alta | Media | Ocurrido | Ambos |
| R-009 | Se borran datos de una empresa por error | Baja | Alta | Mitigado | Luis |
| R-010 | Una sola persona conoce cada parte del código | Media | Media | Abierto | Ambos |
| R-011 | La instalación falla en el equipo de quien recibe el repositorio | Media | Alta | Mitigado | Ambos |
| R-012 | Una clave o dato privado llega al repositorio | Baja | Alta | Mitigado | Ambos |
| R-013 | El despliegue se decidió al final (4 de noviembre) | Alta | Media | Abierto | Ambos |

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
- OpenAI lo paga NuevasTIC, con `gpt-4.1-mini` (23 de octubre de 2026). "Probar con un ejemplo" tiene un límite de 100 al día. **[INFORMACIÓN PENDIENTE]** El presupuesto mensual.

### R-002 — Clave de OpenAI

- **Qué pasó:** la prueba técnica de la IA (T-019) no se pudo terminar porque no hay clave (DEC-013).
- **Impacto:**
  - el hito T-038 (prueba técnica aprobada) queda abierto;
  - el formato de `analisis_categoria.respuesta_ia` sigue siendo una propuesta.
- **Medidas:**
  - el resto de la prueba técnica está hecho (permisos, gráficas y PDF);
  - el servicio de la IA se programa con una respuesta falsa en las pruebas, para no depender de la clave.
- **Cierre:** el flujo completo con la clave real se verificó el 5 de noviembre de 2026. La clave va solo en el `.env`; nunca en el chat ni en el repositorio.

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
  - se aplazó y después lo terminó otra persona del equipo, fuera de este repositorio (4 de noviembre de 2026);
  - va totalmente separado de la web (RN-029), así que no toca empresas ni mediciones.

### R-005 — Tiempos

- **Qué puede pasar:** son 79 vistas, la IA y el PDF, con dos personas, del 21 de septiembre al 6 de noviembre de 2026.
- **Medidas:**
  - cronograma por semanas con hitos;
  - componentes base reutilizables;
  - lo aplazado se marcó y no se programó (el bot, que después se hizo aparte); el rol Consultor entró el 3 de noviembre;
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

- **Ya confirmado:** la lista real de sectores, actividades y categorías (23 de octubre), quién paga la IA, los textos legales y el correo de soporte (4 de noviembre) y la documentación (5 de noviembre).
- **Qué falta confirmar:**
  - el servidor y el dominio para desplegar;
  - el presupuesto mensual de la IA;
  - quién mantiene el sistema después de la entrega (`21_MANTENIMIENTO.md`).
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
- Al borrar para siempre una empresa, sus mediciones terminadas se conservan sin nombre para las estadísticas (decisión de NuevasTIC, 23 de octubre de 2026; `App\Support\Anonimizar`).

### R-010 — Conocimiento repartido

- **Qué puede pasar:** Cristian conoce el frontend y Luis el backend; si uno falta, el otro se atrasa.
- **Medidas:**
  - todo cambio pasa por pull request con revisión del otro;
  - la documentación de `docs/` se actualiza en cada tarea;
  - `CLAUDE.md` resume el stack y las reglas.

### R-011 — Instalación en otro equipo

- **Qué puede pasar:** quien reciba el repositorio, o el servidor donde se despliegue (DEC-006 cambió el 4 de noviembre), debe poder instalarlo sin ayuda.
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
  - el Administrador inicial sale de `ADMIN_EMAIL` y crea su contraseña con el correo de invitación;
  - ya no hay cuentas de demostración con contraseñas conocidas.

### R-013 — Despliegue decidido al final

- **Qué puede pasar:** el sistema se va a desplegar (DEC-006 cambió el 4 de noviembre), pero todavía no hay servidor ni dominio. Si se publica con la configuración local, quedan abiertos los huecos que encontró la auditoría QA.
- **Medidas:**
  - pasos y variables de producción en `20_DESPLIEGUE.md`: `APP_ENV=production`, `APP_DEBUG=false`, `INERTIA_DEVTOOLS_ENABLED=false`, HTTPS con cookies seguras, SMTP real, llaves de Turnstile y la cola con Supervisor;
  - las cabeceras de seguridad ya están en el código (`CabecerasSeguridad`);
  - `composer audit` y `npm audit` en 0 en la entrega.
- **[INFORMACIÓN PENDIENTE]** Servidor, dominio y quién mantiene el sistema.
