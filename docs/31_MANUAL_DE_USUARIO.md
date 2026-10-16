# Manual de usuario

**Estado:** REQUIERE VALIDACIÓN (T-142, T-163). Lo revisa NuevasTIC con el sistema funcionando. Los nombres de botones son los de la pantalla; entre paréntesis va el código del wireframe.

Hay dos partes:

- **Parte A:** para el equipo de NuevasTIC (Administrador).
- **Parte B:** para las empresas y sus colaboradores.

---

# Parte A · Administrador

## A.1 Entrar

1. Abre el sistema y escribe tu correo y tu contraseña (L1).
2. Si la olvidaste, pulsa **¿Olvidaste tu contraseña?** (L3). Te llega un correo con un enlace para crear una nueva (L4).
3. Entras al **Inicio** (A1).

Las cuentas internas no se registran solas: otro Administrador las invita desde **Usuarios y roles** y llega un correo para crear la contraseña.

## A.2 Preparar un diagnóstico

Antes de medir a una empresa, su sector necesita un diagnóstico **publicado**.

1. Ve a **Diagnósticos** (A2) y elige el sector a la izquierda. Si no existe, pulsa **+ Crear sector**.
2. Pulsa **+ Crear diagnóstico** (A2.5):
   - Ponle nombre.
   - Elige empezar en blanco o copiar otro diagnóstico.
   - Marca las categorías. La importancia se reparte sola y la ajustas después.
3. En el **editor** (A2.1), agrega las preguntas de cada categoría (A2.1b):
   - **Tipo:** abierta, opción única o selección múltiple.
   - **Indicación (obligatoria):** lo que ve la empresa en "Cómo responder".
   - **Opciones:** el puntaje de cada una, de 0 a 100. Si quieres, agrega "Ten en cuenta": una nota para la IA que la empresa no ve.
   - **"Opción que excluye a las demás":** para respuestas como "Ninguna".
   - **"Opción que pide escribir cuál":** para respuestas como "Otro". La empresa escribe cuál.
   - **Abiertas:** el criterio con el que la IA las califica.
4. Pulsa **Importancia** (A2.1c) y reparte el 100 % entre las categorías.
5. Abre la **Vista previa** (A2.4):
   - Muestra lo que falta para publicar.
   - Puedes responder de prueba.
   - **Resultado de ejemplo** (A2.6) muestra cómo quedaría el resultado en el mejor, el medio y el peor caso, con su PDF.
6. Pulsa **Publicar v1** (A2.1d) y escribe la nota de cambios.

> Una versión publicada no cambia. Si editas después, preparas la v2. Las mediciones ya asignadas siguen con su versión.

## A.3 Registrar una empresa

Las empresas pueden registrarse solas (L2). También puedes registrarlas tú:

1. Ve a **Empresas** (A3) y pulsa **Registrar empresa** (A3.2).
2. Completa los datos de la empresa y de su cuenta principal.
3. Si quieres, asígnale de una vez la primera medición.
4. A la cuenta principal le llega un correo para crear su contraseña. Tú nunca escribes contraseñas de otros.

## A.4 Asignar una medición

1. Desde **Empresas**, abre la ficha (A3.1) o pulsa **Asignar medición** (A3.1b).
2. Elige la empresa y el diagnóstico publicado de su sector.
3. Opcional:
   - una **fecha límite**;
   - un **mensaje** para la empresa;
   - **Enviar también aviso por correo**. Con **Ver y editar el correo** (A3.1e) cambias el asunto y el texto. Las variables, como `{nombre_usuario}`, se llenan solas.
4. Pulsa **Asignar medición**.

> Solo hay una medición pendiente por empresa. Si ya tiene una, el sistema te pide confirmar el **reemplazo** (A3.1c).

## A.5 Seguir las mediciones

En **Inicio** (A1) ves todas las mediciones. Filtra con las pestañas Todas, En curso, No iniciadas, Vencidas y Terminadas.

| Estado | Qué puedes hacer |
|---|---|
| No iniciada | **Reenviar aviso** |
| En curso | **Recordatorio** (un correo con el avance) |
| Enviada | Nada: la IA está analizando |
| Vencida | **Nueva fecha** o **Cancelar** |
| Terminada | **Ver resultado** o **Asignar nueva** |

### Solicitudes de nueva medición

Cuando a una empresa se le vence la medición, puede pedir otra. Te llega un correo y, en el Inicio, aparece el aviso **"1 empresa pidió una medición nueva"**. Al pulsarlo vas a **Vencidas**, donde esa empresa va primero.

La atiendes de una de estas formas:

- **Asignar nueva:** abre Asignar medición con la empresa elegida.
- **Nueva fecha:** la misma medición vuelve a estar pendiente, con sus respuestas.
- **Cancelar:** cierra la medición vencida.

En los tres casos la solicitud queda atendida.

## A.6 Ver los resultados

- En la ficha de la empresa (A3.1) ves:
  - el último puntaje y la variación;
  - la **gráfica de evolución**: con **Ver:** eliges el puntaje total o una categoría;
  - la tabla de mediciones.
- **Ver resultado** (A3.3) muestra lo mismo que ve la empresa:
  - el puntaje y el nivel;
  - el radar;
  - por cada categoría, el método de análisis que usó la IA, la observación y la recomendación.
- **Ver respuestas** (A3.5) y **Ver informe PDF**.
- **Ver historial completo** (A3.4).

## A.7 Configurar la IA

Ve a **Configuración IA** (A4). Las instrucciones tienen cuatro partes: contexto, tarea, detalles y ejemplos.

1. **General:** vale para todas las empresas.
2. **Sector:** para cada parte eliges usar la general, agregar texto al final o reemplazarla.
3. **Empresa:** lo mismo, para una empresa puntual.
4. **Ver instrucciones completas:** muestra el texto final.
5. **Probar con un ejemplo:** analiza una categoría real con lo que escribiste, sin guardar. Gasta una llamada a OpenAI.
6. Pulsa **Guardar**. Los cambios valen para las mediciones que se analicen desde ahora.

**Cómo se arma el prompt final** (A4.9) explica las capas con un ejemplo.

## A.8 Cuentas y roles

En **Usuarios y roles** (A5):

- **Invitar** cuentas internas.
- **Desactivar o reactivar** una cuenta.
- **Eliminar** una cuenta: se escribe el correo exacto para confirmar y se puede recuperar durante 90 días.
- **Cambiar el rol** de una cuenta. Si es la cuenta principal de una empresa, el sistema avisa qué pasa con sus colaboradores y su medición pendiente.
- **Roles:** crear roles con los permisos que elijas. Los roles Administrador, Empresa y Colaborador no se borran.
- **Ver como:** mira el sistema como lo ve una empresa o un colaborador.
  - Es solo de lectura y queda registrado.
  - Arriba aparece una franja con **Salir de Ver como**.

## A.9 Mi perfil

En **Mi perfil** (A6) cambias tu nombre, tu foto, tu correo y tu contraseña.

---

# Parte B · Empresa y colaboradores

## B.1 Registrarte

1. En la pantalla de entrada pulsa **Registra tu empresa** (L2).
2. **Paso 1:** tus datos y una contraseña con al menos 8 caracteres, una mayúscula, un número y un carácter especial.
3. **Paso 2:** los datos de la empresa: sector, actividad económica, ubicación y número de empleados.
4. Entras a tu inicio. Cuando NuevasTIC te asigne un diagnóstico, aparecerá ahí y, si así lo eligió, te llegará un correo.

## B.2 Responder el diagnóstico

1. En tu **inicio** (E1) pulsa **Iniciar diagnóstico**, o **Continuar diagnóstico** si ya empezaste.
2. Responde por categorías (E2):
   - **Elige una:** marca una opción.
   - **Marca todas las que apliquen:** marca varias. Si eliges "Ninguna", se desmarcan las otras.
   - **Escribe:** hasta 1000 caracteres.
   - **"Otro":** escribe cuál en el campo **¿Cuál?**.
3. Cada respuesta se guarda sola. Arriba ves **✓ Guardado** con la hora. Puedes pulsar **Guardar y salir** y seguir otro día.
4. Al terminar pulsa **Revisar antes de enviar** (E3). Ahí ves qué preguntas obligatorias faltan.
5. Pulsa **Enviar diagnóstico** y confirma.

> Después de enviar ya no puedes cambiar las respuestas.

## B.3 Esperar el resultado

En la pantalla de diagnóstico enviado (E4) ves el avance del análisis, categoría por categoría. Suele tardar unos minutos.

- Te llega un correo cuando el resultado está listo.
- Si una categoría no se pudo analizar, aparece **Reintentar**.

## B.4 Tu resultado

En el resultado (E6) ves:

- **Puntaje total y nivel:**
  - Crítico: 0–29.
  - Se puede mejorar: 30–59.
  - Vas en buen camino: 60–79.
  - Sigue así: 80–100.
- **Variación:** cuánto subiste o bajaste frente a tu medición anterior, desde la segunda medición.
- **Radar** con todas las categorías.
- **Por cada categoría:** el puntaje, el método con el que se analizó, una observación y una recomendación.

Además:

- **Ver mis respuestas** (E9): lo que respondiste, con una observación por pregunta.
- **Ver informe PDF** (E10): un informe de 3 páginas para guardar o compartir.

## B.5 Tu historial

En **Mi historial** (E8) ves todas tus mediciones terminadas. Desde la segunda medición aparece la gráfica de evolución; con **Ver:** eliges el total o una categoría.

## B.6 Si tu medición venció

Una medición vencida ya no se puede responder. En tu inicio pulsa **Pedir una nueva fecha**: el equipo de NuevasTIC recibe la solicitud y te asigna otra. Solo puedes tener una solicitud abierta a la vez.

## B.7 Colaboradores (solo la cuenta principal)

En **Colaboradores** (E12) agregas a las personas de tu empresa que también pueden responder y ver resultados.

1. Pulsa **Agregar colaborador**.
2. Escribe su nombre, cargo, correo y una contraseña.
3. El sistema te muestra los datos de acceso para que se los compartas (E12.2).
4. Puedes editar, desactivar, reactivar o eliminar a cada colaborador.

Los colaboradores no agregan a otros colaboradores ni cambian los datos de la empresa.

## B.8 Mi perfil

En **Mi perfil** (E11) cambias tus datos, tu foto y tu contraseña. La cuenta principal también cambia los datos y el logo de la empresa.

---

## Preguntas frecuentes

- **No me llegó el correo.** Revisa la carpeta de spam. Si no está, pide a NuevasTIC que lo reenvíe.
  > **[INFORMACIÓN PENDIENTE]** Correo de soporte real de NuevasTIC (`SOPORTE_EMAIL`).
- **¿Puedo responder desde el celular?** Sí. Las pantallas se adaptan al ancho del celular.
- **¿Quién ve mis respuestas?** Tu empresa, tus colaboradores y el equipo de NuevasTIC. Ninguna otra empresa (RN-007).
