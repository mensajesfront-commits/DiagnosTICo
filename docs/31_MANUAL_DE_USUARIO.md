# Manual de usuario

**Estado:** COMPLETO (T-142, T-163), aprobado por NuevasTIC el 5 de noviembre de 2026. Los nombres de botones son los de la pantalla; entre paréntesis va el código del wireframe.

Hay tres partes:

- **Parte A:** para el equipo de NuevasTIC (Administrador).
- **Parte B:** para las empresas y sus colaboradores.
- **Parte C:** para los consultores.

---

# Parte A · Administrador

## A.1 Entrar

1. Abre el sistema y escribe tu correo y tu contraseña (L1).
2. Si la olvidaste, pulsa **¿Olvidaste tu contraseña?** (L3). Te llega un correo con un enlace para crear una nueva (L4). El enlace vence a los **10 minutos**; si se te pasa el tiempo, pide otro.
3. Entras al **Inicio** (A1).

Las cuentas internas no se registran solas: otro Administrador las invita desde **Usuarios y roles** y llega un correo para crear la contraseña. Ese enlace de invitación sirve hasta usarlo.

## A.2 Preparar un diagnóstico

Antes de medir a una empresa, su sector necesita un diagnóstico **publicado**.

1. Ve a **Diagnósticos** (A2) y elige el sector a la izquierda. Si no existe, pulsa **+ Crear sector**.
2. Pulsa **+ Crear diagnóstico** (A2.5):
   - Ponle nombre.
   - Elige empezar en blanco, copiar otro diagnóstico o **pegar el contenido**.
   - En blanco, marca las categorías. La importancia se reparte sola y la ajustas después.
   - **Pegar el contenido:** escribe el diagnóstico con la plantilla (botón **Copiar la plantilla**) y pégalo en el recuadro. Pulsa **Revisar el contenido**: verás cuántas categorías, preguntas y opciones se leyeron y, si algo está mal, el error con su número de línea (un clic te lleva a esa línea). Cuando no haya errores, pulsa **Crear y abrir el editor**: el borrador queda lleno y lo revisas en el editor.
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
   - **Resultado de ejemplo** (A2.6) muestra cómo quedaría el resultado en el mejor, el medio y el peor caso, con sus dos PDF: el completo y el resumen de la empresa.
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
- **Ver respuestas** (A3.5).
- Dos PDF (E10):
  - **Informe completo PDF**: el «Informe de Diagnóstico y Estrategia de Marketing Digital», con portada, ficha técnica, resumen ejecutivo, metodología, resultados, una página por categoría, DOFA, conclusiones, fichas de acción, cronograma, KPI y glosario. En este informe los niveles tienen nombres más formales: «Se puede mejorar» es «Requiere mejora», «Vas en buen camino» es «Desempeño satisfactorio» y «Sigue así» es «Desempeño óptimo».
  - **Resumen para la empresa**: el PDF corto que recibe la empresa. Sirve para entregárselo o revisarlo con ella.
- **Ver historial completo** (A3.4). Ahí también salen las mediciones que vencieron sin responderse y se cancelaron, con las fechas, cuántas preguntas respondió la empresa, el último aviso por correo y quién la asignó y la canceló. Sirve para mostrarle a una empresa que sí recibió el diagnóstico.

## A.7 Configurar la IA

Ve a **Configuración IA** (A4). Las instrucciones tienen cuatro partes: contexto, tarea, detalles y ejemplos.

1. **General:** vale para todas las empresas.
2. **Sector:** para cada parte eliges usar la general, agregar texto al final o reemplazarla.
3. **Empresa:** lo mismo, para una empresa puntual.
4. **Ver instrucciones completas:** muestra el texto final.
5. **Probar con un ejemplo:** analiza una categoría real con lo que escribiste, sin guardar. Gasta una llamada a OpenAI; hay un máximo de 100 pruebas al día entre todos los administradores.
6. Pulsa **Guardar**. Los cambios valen para las mediciones que se analicen desde ahora; los resultados ya publicados no cambian.
7. Si algo sale mal, **Restaurar texto original** vuelve a las instrucciones de fábrica.

**Cómo se arma el prompt final** (A4.9) explica las capas con un ejemplo.

## A.8 Cuentas y roles

En **Usuarios y roles** (A5):

- **Invitar** cuentas internas. Si eliges el rol **Consultor**, la ventana pasa a «Invitar consultor» y puedes marcar en «Empresas que acompaña» las empresas que va a ver.
- **Desactivar o reactivar** una cuenta.
- **Eliminar** una cuenta: se escribe el correo exacto para confirmar y se puede recuperar durante 90 días.
- **Cambiar el rol** de una cuenta. Si es la cuenta principal de una empresa, el sistema avisa qué pasa con sus colaboradores y su medición pendiente.
- **Roles:** crear roles con los permisos que elijas. Los roles Administrador, Empresa, Colaborador y Consultor no se borran.
- **Consultores de una empresa:** también se vinculan después, en la ficha de la empresa (A3.1), pestaña **Consultores** → «Vincular un consultor» → **Vincular**. Ahí mismo, **Quitar** lo desvincula (pide confirmación). Un consultor puede acompañar varias empresas.
- **Ver como:** mira el sistema como lo ve una empresa o un colaborador.
  - Es solo de lectura y queda registrado.
  - Arriba aparece una franja con **Salir de Ver como**.

## A.9 Mi perfil

En **Mi perfil** (A6) cambias tu nombre, tu foto, tu correo y tu contraseña.

---

# Parte B · Empresa y colaboradores

## B.1 Registrarte

1. En la pantalla de entrada pulsa **Registra tu empresa** (L2).
2. **Paso 1 · Mi empresa:** nombre, sector, actividad económica, descripción corta y ubicación.
3. **Paso 2 · Tu usuario:** tus datos, tu correo y una contraseña con al menos 8 caracteres, una mayúscula, un número y un carácter especial. Marca la casilla «Acepto los **Términos y Condiciones** y la **Política de Privacidad**» (cada nombre abre su texto) y pasa el captcha.
4. Te llega un correo con un **código de 6 dígitos**. Escríbelo en la ventana «Verifica tu correo» (L2b). Si no lo verificas en 7 días, el registro se borra.
5. Entra con tu correo y tu contraseña. Cuando NuevasTIC te asigne un diagnóstico, aparecerá en tu inicio y, si así lo eligió, te llegará un correo.

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
- **Por cada categoría:**
  - el puntaje y el nivel;
  - el diagnóstico: qué pasa, por qué y qué le cuesta a la empresa;
  - lo que encontró el método de análisis, por grupos (por ejemplo Fortalezas y Debilidades en DOFA);
  - lo que la empresa ya hace bien;
  - 2 o 3 recomendaciones en orden, cada una con 3 pasos, el plazo y cómo saber si funcionó.

Además:

- **Ver mis respuestas** (E9): lo que respondiste, con una observación por pregunta.
- **Ver informe PDF** (E10): el «Resumen de tu diagnóstico de marketing digital», para guardar, imprimir o compartir. Trae:
  - tu calificación, tu nivel y cómo leer los niveles;
  - tus categorías en barras;
  - **Por dónde empezar**: las acciones de tus 3 categorías más débiles;
  - por cada categoría, de la más urgente a la más fuerte: qué encontró el análisis, por qué importa, lo que haces bien, lo que debes mejorar y qué hacer, con pasos y plazo;
  - los próximos pasos.
- El equipo de NuevasTIC tiene además un informe completo, con la metodología, el DOFA, el plan, el cronograma y los indicadores.

## B.5 Tu historial

En **Mi historial** (E8) ves todas tus mediciones terminadas. Desde la segunda medición aparece la gráfica de evolución; con **Ver:** eliges el total o una categoría.

Si una medición venció sin que la enviaras y NuevasTIC la canceló, también aparece en la lista, marcada «Vencida sin responder · cancelada», con sus fechas y cuántas preguntas alcanzaste a responder.

## B.6 Si tu medición venció

Una medición vencida ya no se puede responder. En tu inicio pulsa **Pedir una nueva fecha**: el equipo de NuevasTIC recibe la solicitud y te asigna otra. Solo puedes tener una solicitud abierta a la vez.

## B.7 Colaboradores (solo la cuenta principal)

En **Colaboradores** (E12) agregas a las personas de tu empresa que también pueden responder y ver resultados.

1. Pulsa **Agregar colaborador**.
2. Escribe su nombre, cargo, correo y una contraseña, y elige qué páginas ve: **Responder el diagnóstico**, **Resultados y Mi historial** y **Descargar el informe PDF** (este último, solo junto con los resultados).
3. El sistema te muestra los datos de acceso para que se los compartas (E12.2).
4. Puedes editar a cada colaborador (sus datos, su contraseña y sus páginas), desactivarlo, reactivarlo o eliminarlo.

Los colaboradores no agregan a otros colaboradores ni cambian los datos de la empresa.

## B.8 Mi perfil

En **Mi perfil** (E11) cambias tus datos, tu foto y tu contraseña. La cuenta principal también cambia los datos y el logo de la empresa. Si cambias tu correo o tu teléfono, te llega un código para confirmarlo; hasta entonces sigues entrando con el anterior.

Los colaboradores ven Mi perfil en solo lectura: sus datos y su contraseña los cambia la cuenta principal (E12.3).

---

# Parte C · Consultor

El consultor es una persona externa a NuevasTIC que acompaña a una o varias empresas. **Solo mira:** no responde diagnósticos ni cambia datos.

## C.1 Entrar

1. NuevasTIC te invita y te llega un correo para crear tu contraseña (el enlace sirve hasta usarlo).
2. Entra con tu correo y tu contraseña (L1). Llegas a tu **Inicio** (C1).
3. Si todavía no tienes empresas, verás un aviso: el equipo de NuevasTIC te las vincula.

## C.2 Tu inicio (C1)

- Cifras de tus empresas: cuántas son, el puntaje promedio, cuántas mejoraron o bajaron y las mediciones pendientes.
- **Qué atender**, de lo más urgente a lo menos: mediciones vencidas, puntajes que bajaron, mediciones sin empezar hace una semana y resultados nuevos.
- Los resultados recientes y las próximas fechas límite.

## C.3 Mis empresas (C2)

- Una tarjeta por empresa con el último puntaje, el nivel, la variación y la medición pendiente.
- **Ver empresa** abre su ficha; **Historial** y **Último resultado** van directo.
- Puedes filtrar y ordenar; con 7 empresas o más aparece un buscador.

## C.4 Resultados y PDF

En el resultado de una empresa (A3.3) ves lo mismo que el equipo de NuevasTIC, y dos botones:

- **Informe completo PDF:** el informe de diagnóstico con todo el detalle (metodología, DOFA, plan, cronograma y KPI).
- **Resumen para la empresa:** el PDF corto que recibe la empresa, para entregárselo o revisarlo con ella.

Si abres una empresa que no tienes vinculada, verás «No encontramos esta página».

---

## Preguntas frecuentes

- **No me llegó el correo.** Revisa la carpeta de spam. Si no está, escribe a **contacto@captter.com** para que te lo reenvíen.
- **¿Puedo responder desde el celular?** Sí. Las pantallas se adaptan al ancho del celular.
- **¿Quién ve mis respuestas?** Tu empresa, tus colaboradores, el equipo de NuevasTIC y los consultores que NuevasTIC vincule a tu empresa. Ninguna otra empresa (RN-007, RN-030).
- **El enlace para cambiar la contraseña ya no sirve.** Vence a los 10 minutos. Pide otro en «¿Olvidaste tu contraseña?»; solo sirve el último.
