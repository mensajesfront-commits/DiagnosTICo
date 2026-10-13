# Casos de uso

**Estado:** REQUIERE VALIDACIÓN (T-041). Se aprueban junto con el MER en el hito T-044.

Casos de uso de los flujos complejos. Los flujos simples (crear un sector, editar el perfil…) se describen bien en sus historias de usuario (`05_REQUISITOS_FUNCIONALES.md`) y no se repiten aquí.

Cada caso cita sus historias (HU), sus reglas de negocio (RN), las pantallas del wireframe y las tablas de `12_BASE_DE_DATOS.md`. El diagrama de secuencia de responder → enviar → IA → resultado está en `09_FLUJOS_DEL_SISTEMA.md`.

| ID | Caso de uso | Actor principal | Estado del código |
|---|---|---|---|
| CU-001 | Publicar una versión del diagnóstico | Administrador | Pendiente (semana 4) |
| CU-002 | Asignar una medición | Administrador | Pendiente (semanas 4–5) |
| CU-003 | Responder y enviar la medición | Empresa o Colaborador | Pendiente (semana 5) |
| CU-004 | Analizar con la IA cuando algo falla | Sistema | Pendiente (semana 5) |
| CU-005 | Ver el sistema como otra cuenta | Administrador | Pendiente (semana 6) |
| CU-006 | Diagnóstico por WhatsApp | Empresa por WhatsApp | Aplazado |

---

## CU-001 — Publicar una versión del diagnóstico

| Campo | Información |
|---|---|
| Actor | Administrador (permiso `diagnosticos.publicar`) |
| Historias | HU-028, HU-029 |
| Reglas | RN-010, RN-011, RN-012, RN-013 |
| Pantallas | A2.1 (editor), A2.4 (vista previa), A2.1c (publicar) |
| Tablas | `diagnosticos`, `diagnostico_categoria`, `preguntas`, `opciones`, `versiones_diagnostico`, `mediciones` |

**Precondiciones**

- El diagnóstico tiene un borrador (vN).
- El Administrador inició sesión y tiene el permiso.

**Flujo principal**

1. El Administrador abre el borrador en A2.1 y pulsa "Publicar".
2. El sistema revisa los 5 requisitos (RN-012):
   - cada categoría tiene al menos una pregunta;
   - cada opción tiene puntaje;
   - la importancia suma exactamente 100 %;
   - cada pregunta tiene indicación;
   - cada selección múltiple puede llegar a 100 puntos.
3. El sistema muestra A2.1c con los requisitos cumplidos y el campo "Nota de cambios".
4. El Administrador escribe la nota.
5. Si hay mediciones pendientes de la versión anterior, elige qué pasa con ellas: siguen con la anterior, o pasan a la nueva las que aún no empezaron.
6. El Administrador confirma.
7. El sistema, en una transacción:
   - congela el contenido en `versiones_diagnostico.contenido` con el número siguiente (`ContenidoDiagnostico::congelar`);
   - pone el diagnóstico en `publicado` y `version_borrador = null`;
   - mueve las mediciones elegidas a la versión nueva.
8. El sistema muestra "Versión vN publicada".

**Flujos alternos**

- **A1. Falta un requisito:** el botón "Publicar" queda desactivado y la lista dice qué falta (HU-029 CA-002).
- **A2. Sin nota de cambios:** no se publica hasta escribirla (HU-029 CA-003).
- **A3. Primera publicación:** no hay versión anterior ni mediciones que mover; se publica v1.

**Excepciones**

- **E1. Otro Administrador publicó antes:** el sistema avisa y recarga el borrador. **[FUNCIONALIDAD POR DEFINIR]** Cómo se detecta (fecha de actualización o bloqueo).

**Postcondiciones**

- La versión nueva es inmutable (RN-010) y es la única que se ofrece al asignar mediciones.
- Las mediciones y los resultados anteriores siguen apuntando a su versión.

---

## CU-002 — Asignar una medición

| Campo | Información |
|---|---|
| Actor | Administrador (permiso `mediciones.asignar`) |
| Historias | HU-036, HU-037, HU-083 |
| Reglas | RN-014, RN-015, RN-016 |
| Pantallas | A3.1 (ficha de la empresa), A3.1b (asignar), A3.1c (reemplazar o cancelar), A3.1e (correo de aviso) |
| Tablas | `mediciones`, `versiones_diagnostico`, `plantillas_correo`, `solicitudes_medicion` |

**Precondiciones**

- La empresa está activa.
- Su sector tiene un diagnóstico con al menos una versión publicada.

**Flujo principal**

1. En A3.1, el Administrador pulsa "Asignar medición".
2. El sistema muestra A3.1b con los diagnósticos del sector de la empresa. Los borradores aparecen, pero no se pueden elegir (HU-036 CA-004).
3. El Administrador elige el diagnóstico. Si quiere, fija la fecha límite y escribe un mensaje.
4. Ve la vista previa de cómo lo verá la empresa en E1.
5. Deja marcado "Enviar también aviso por correo" y, si quiere, edita el correo en A3.1e.
6. Pulsa "Asignar".
7. El sistema:
   - crea la medición con el número siguiente de la empresa, estado `no_iniciada` y la última versión publicada;
   - si hay una solicitud abierta, la marca `atendida`;
   - pone el correo en la cola (RNF-004).
8. La empresa ve la medición pendiente en E1 y recibe el correo.

**Flujos alternos**

- **A1. Ya hay una medición pendiente (RN-014):** el sistema avisa que la nueva la reemplaza (A3.1c). Si confirma, la anterior queda `cancelada` con `reemplazada_por` apuntando a la nueva.
- **A2. Sin aviso por correo:** se asigna igual; la empresa la ve al entrar.
- **A3. "Guardar como plantilla" en A3.1e:** el texto reemplaza la plantilla general `aviso_medicion`.
- **A4. Cancelar sin reemplazar (A3.1c):** la pendiente queda `cancelada` y no se crea otra.

**Excepciones**

- **E1. El sector no tiene versión publicada:** no hay diagnósticos elegibles y el botón "Asignar" queda desactivado.
- **E2. Falla el envío del correo:** la medición queda creada; el correo se reintenta en la cola.

**Postcondiciones**

- La empresa tiene una sola medición pendiente.

---

## CU-003 — Responder y enviar la medición

| Campo | Información |
|---|---|
| Actor | Empresa (cuenta principal) o Colaborador (permiso `diagnostico.responder`) |
| Historias | HU-058, HU-060, HU-061, HU-062, HU-063, HU-082 |
| Reglas | RN-013, RN-015, RN-016, RN-017 |
| Pantallas | E1, E2 (bienvenida), E3 (responder), E4 (revisar), E4c (confirmar envío), E5 (espera) |
| Tablas | `mediciones`, `respuestas`, `solicitudes_medicion` |

**Precondiciones**

- La cuenta y su empresa están activas.
- La empresa tiene una medición `no_iniciada` o `en_curso`.

**Flujo principal**

1. En E1, la persona pulsa "Iniciar diagnóstico" (o "Continuar").
2. El sistema pone la medición `en_curso` y muestra E3 con las categorías de la versión congelada.
3. La persona responde. Cada respuesta se guarda al momento en `respuestas` con su `pregunta_ref` y quién respondió (RNF-003).
4. Puede salir con "Guardar y salir" y volver luego: sigue donde iba (HU-060 CA-005).
5. Con todo respondido, pulsa "Revisar antes de enviar" y ve E4.
6. Pulsa "Enviar diagnóstico". El aviso de E4c dice que no podrá cambiar las respuestas.
7. Confirma con "Sí, enviar diagnóstico".
8. El sistema:
   - pone la medición `enviada` con `enviada_en`;
   - calcula los puntajes de las preguntas cerradas (RN-018);
   - pone en la cola el análisis de cada categoría (CU-004).
9. La persona ve E5 "Estamos analizando tus respuestas".

**Flujos alternos**

- **A1. Dos personas de la misma empresa responden a la vez:** cada respuesta se guarda por pregunta; gana la última que se guardó. **[FUNCIONALIDAD POR DEFINIR]** ¿Se avisa que otra persona está respondiendo?
- **A2. "Todavía no" en E4c:** vuelve a E4 sin enviar (HU-062 CA-004).
- **A3. Pregunta obligatoria sin responder:** E4 la marca y no deja enviar.

**Excepciones**

- **E1. La medición venció (RN-016):** la tarea diaria la pone `vencida` y ya no se puede responder. E1 muestra "Pedir una nueva medición". Al pedirla, se crea una solicitud `abierta` (solo una a la vez) y se avisa al Administrador por correo, que sigue con CU-002.
- **E2. Desactivan la cuenta o la empresa mientras responde:** la sesión se cierra (`CerrarSesionCuentaInactiva`); lo guardado se conserva.

**Postcondiciones**

- Las respuestas ya no se pueden cambiar (RN-017).

---

## CU-004 — Analizar con la IA cuando algo falla

| Campo | Información |
|---|---|
| Actor | Sistema (cola de trabajos) |
| Historias | HU-063, HU-068, HU-069, HU-081, HU-084 |
| Reglas | RN-018, RN-019, RN-020, RN-021, RN-022, RN-023, RN-024 |
| Pantallas | E5 (espera), E6 (resultado), A1 (aviso de falla) |
| Tablas | `analisis_categoria`, `respuestas`, `resultados`, `prompts`, `mediciones` |

**Precondiciones**

- La medición está `enviada`.
- Hay una fila en `analisis_categoria` por cada categoría, en estado `pendiente`.

**Flujo principal**

1. Por cada categoría, un trabajo de la cola arma el prompt por capas (RN-022): general, sector y empresa, más las preguntas con sus respuestas y el formato fijo.
2. Envía el prompt a OpenAI y guarda el texto exacto en `analisis_categoria.prompt`.
3. Recibe la respuesta, revisa que cumpla el formato y la guarda en `respuesta_ia`, con estado `terminado`.
4. Copia la observación de cada pregunta y el puntaje de las abiertas a `respuestas`.
5. Cuando todas las categorías terminan, el sistema:
   - calcula el puntaje de cada categoría, el total ponderado, el nivel y la variación (RN-018 a RN-020);
   - crea el resultado y lo publica sin revisión (RN-023);
   - genera el PDF una vez y lo guarda (RN-024);
   - pone la medición `terminada`.
6. La empresa ve E6 y recibe el aviso.

**Flujos alternos: las fallas**

- **A1. OpenAI no responde, da error o se agota el tiempo:**
  - se suma 1 a `intentos` y se reintenta solo esa categoría;
  - las que ya terminaron no se repiten (RN-021).
- **A2. La respuesta no cumple el formato fijo** (JSON roto, falta un campo, un puntaje fuera de 0–100): cuenta como falla y se reintenta igual que A1.
- **A3. Se agotan los 3 intentos:**
  - la categoría queda `fallido`;
  - se avisa por correo al Administrador (si tiene activo "Cuando falla el análisis de la IA" en Mi perfil);
  - la medición sigue `enviada`, la empresa sigue viendo E5 y las demás categorías conservan su resultado.
- **A4. El Administrador reintenta desde A1:** la categoría fallida vuelve a `pendiente` con `intentos = 0`, y sigue el flujo principal.

**Excepciones**

- **E1. Falta la clave de OpenAI o no es válida:** todas las categorías fallan y se sigue A3. **[INFORMACIÓN PENDIENTE]** La clave del proyecto (DEC-013).
- **E2. Falla la generación del PDF (Chromium):** el resultado se publica igual; el PDF se reintenta en la cola. **[FUNCIONALIDAD POR DEFINIR]** Qué ve la empresa en "Descargar PDF" mientras tanto.

**Postcondiciones**

- El resultado guarda el puntaje, el nivel, la variación, el análisis y el prompt usado. No cambia después (RN-023).

---

## CU-005 — Ver el sistema como otra cuenta

| Campo | Información |
|---|---|
| Actor | Administrador (permiso `usuarios.gestionar`) |
| Historias | HU-053, HU-054 |
| Reglas | RN-007, RN-026 |
| Pantallas | A5 (usuarios), A3.1 (ficha de la empresa), A5.4 (franja "Estás viendo el sistema como…") |
| Tablas | `registros_ver_como` |

**Precondiciones**

- La cuenta a mirar no es de un Administrador (hoy la ruta ya devuelve 403 en ese caso).

**Flujo principal**

1. En A5 o en A3.1, el Administrador pulsa "Ver como".
2. El sistema crea una fila en `registros_ver_como` con `inicio` y guarda en la sesión a quién está mirando.
3. Muestra las pantallas de esa cuenta con la franja "Estás viendo el sistema como …".
4. El Administrador navega como lo haría esa cuenta.
5. Pulsa "Salir de esta vista".
6. El sistema guarda `fin`, quita la marca de la sesión y vuelve a A5.

**Flujos alternos**

- **A1. Intenta guardar algo** (responder, enviar, editar el perfil): el servidor lo rechaza mientras dure "Ver como" y muestra "Solo lectura" (RN-026). Ocultar los botones no basta.
- **A2. Cierra la pestaña o la sesión vence:** `fin` queda `null`. **[FUNCIONALIDAD POR DEFINIR]** Cerrar los registros abiertos al cerrar la sesión.

**Excepciones**

- **E1. La cuenta a mirar se desactiva o se elimina mientras tanto:** el sistema sale de la vista y avisa.

**Postcondiciones**

- Queda registrado quién miró qué cuenta, cuándo y por cuánto tiempo.
- Los datos de la cuenta no cambian.

---

## CU-006 — Diagnóstico por WhatsApp (aplazado)

| Campo | Información |
|---|---|
| Actor | Empresa por WhatsApp (sin cuenta) |
| Historias | HU-071 a HU-075 |
| Reglas | RN-029, RNF-006 |
| Pantallas | Ninguna del panel, salvo la configuración de las preguntas del bot |
| Tablas | **[FUNCIONALIDAD POR DEFINIR]** Separadas de `empresas` y `mediciones` |

**[FUNCIONALIDAD POR DEFINIR]** Está aplazado. Se deja el caso para no perder lo que ya se sabe.

**Flujo principal**

1. La empresa escribe al número de WhatsApp de NuevasTIC.
2. El webhook recibe el mensaje y verifica que venga firmado por Meta (RNF-006).
3. El bot hace hasta 10 preguntas configuradas, solo texto.
4. Si la conversación se corta, al volver retoma desde la última pregunta. Solo se reinicia si la empresa lo pide.
5. Con todo respondido, el bot envía el resultado por WhatsApp. No hay cuenta, medición ni PDF (RN-029).

**Excepciones**

- **E1. Mensaje sin firma válida:** se descarta sin responder.
