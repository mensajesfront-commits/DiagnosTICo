# Cambios y versiones

**Estado:** EN CURSO (T-037). Se agrega una sección por semana; lo más nuevo va arriba.

La versión de entrega será `v1.0.0` (T-152). Hasta entonces, los cambios se agrupan por semana del cronograma.

## Semana 4 · del 12 al 18 de octubre de 2026 (en curso)

### Agregado (cierre funcional, 14 de octubre)

- **HU-083 · Atender las solicitudes de nueva medición:**
  - aviso en A1 ("N empresas pidieron una medición nueva"), con esas filas primero en Vencidas y "Asignar nueva";
  - aviso en A3, en la ficha, en A3.4 y en Asignar medición.
  - Asignar otra medición, darle nueva fecha a la vencida o cancelarla deja la solicitud atendida.
- **Opción "Otro" que pide escribir cuál:** se elige en A2.1b (se marca sola si empieza por "Otro"). En E2 aparece "¿Cuál?" y la IA lee "Otro: lo que escribió". Columna `opciones.con_texto`.
- **"Ver:" en la gráfica de evolución** (A3.1, A3.4, E8): puntaje total o una categoría.
- **A5:** la cuenta principal de una empresa dice qué medición pendiente tiene, para el aviso al desactivarla o cambiarle el rol.
- **Avisos de Mi perfil (A6, E11):**
  - cada correo respeta lo que eligió quien lo recibe;
  - correo nuevo "Una empresa envió su diagnóstico";
  - resumen semanal de mediciones pendientes (`mediciones:resumen`, lunes 07:50).
- **Documentos nuevos:**
  - 07 Reglas de negocio;
  - 11 Diseño técnico;
  - 13 API e integraciones;
  - 16 Infraestructura;
  - 19 Pruebas;
  - 20 Despliegue;
  - 21 Mantenimiento;
  - 25 Roadmap;
  - 29 Funcionalidades;
  - 31 Manual de usuario.
- Estado de cada historia actualizado en `05_REQUISITOS_FUNCIONALES.md`.

### Agregado (verificación del correo, 16 de octubre)

- **L2b · Verifica tu correo** (DEC-020):
  - **Captcha** (Cloudflare Turnstile) antes de "Registrar empresa".
  - Al registrarse no se entra al sistema: se abre una ventana para escribir el **código de 6 dígitos** que llega por correo. Vence en 15 minutos, admite 5 intentos y tiene "Reenviar código".
  - Al acertar, lleva a Iniciar sesión con el correo escrito.
  - Si se intenta entrar sin verificar, se abre la misma ventana en L1.
  - Los registros sin verificar se borran a los 7 días (`cuentas:purgar`).
  - **Mi perfil:** cambiar el correo también pide el código, enviado al correo nuevo.

### Agregado (diagnósticos archivados, 16 de octubre)

- **A2 · Archivados (N):** junto al título de la tabla se abre la ventana con los diagnósticos archivados del sector o de todos.
  - **Restaurar** los devuelve a publicados.
  - **Eliminar** los borra para siempre, con su historial de versiones y doble confirmación. Solo se puede si ninguna empresa los respondió, porque esos resultados se hicieron con ese diagnóstico (RN-010, RN-023).

### Agregado (cargo del dueño único, 16 de octubre)

- **El cargo del dueño de la empresa no se repite.** Ningún colaborador puede tener el cargo de la cuenta principal, ni al crearlo o editarlo (E12) ni desde su Mi perfil (E11). Tampoco el dueño puede tomar el cargo de un colaborador (E11, A3.1d). Se compara sin mayúsculas, tildes ni espacios de más (regla `CargoDelDueno`). Los cargos de los demás colaboradores sí se pueden repetir.

### Agregado (términos de uso y política de datos, 16 de octubre)

- **Borrador de los términos de uso y de la política de tratamiento de datos** (Ley 1581 de 2012), escrito con lo que el sistema hace de verdad: qué datos guarda, que las respuestas van a OpenAI sin el nombre de la empresa, Cloudflare en el captcha, los 7 días sin verificar, los 90 días para recuperar una cuenta, "Ver como" y los plazos para consultas y reclamos.
- Solo están en L2: se abren en una ventana sin salir del formulario.
- Lo que NuevasTIC no ha entregado (razón social, NIT, domicilio, vigencia, costo, proveedores) va marcado y resaltado como [INFORMACIÓN PENDIENTE].

### Agregado (contraseña nueva más segura, 17 de octubre)

- **La contraseña no puede tener partes del correo.** De "juan.perez1102@empresa.co" no se pueden usar "juan", "perez", "1102" ni "empresa", sin importar mayúsculas ni tildes. Las partes de menos de 3 caracteres no cuentan. Aplica en el registro (L2), la contraseña nueva (L4), Mi perfil y los colaboradores (E12). La lista de requisitos lo marca en vivo ("Sin partes de tu correo").
- **La contraseña nueva debe ser distinta de la anterior** (L4, Mi perfil y editar colaborador). Esto lo revisa solo el servidor.
- El correo de "¿Olvidaste tu contraseña?" ahora dice "Cambia tu contraseña" y su botón, "Cambiar contraseña".

### Cambiado (botones según los permisos, 26 de octubre)

- Cada pantalla oculta los botones que el rol de la cuenta no permite (`usePermisos`): Empresas (lista, ficha y Datos de la cuenta en solo lectura), Inicio, Diagnósticos, catálogo, editor y vista previa, resultados y PDF, Usuarios y roles.
- Corregido: "Publicar versión" ahora pide el permiso "Publicar versiones" (`diagnosticos.publicar`); antes bastaba con "Editar".
- Corregido (A5.1c): al desmarcar "Rol activo" y guardar no pasaba nada ni salía un mensaje. Ahora se muestra el error del servidor.

### Cambiado (administrador principal, teléfono y cuentas que pasan al equipo, 28 de octubre)

- **Administrador principal (A5):** la cuenta del Administrador creada al instalar (columna `users.administrador_principal`; en una base que ya existía, la cuenta de Administrador más antigua) dice "Administrador principal". Ningún otro Administrador le cambia el rol, se lo asigna a otro rol, la desactiva ni la elimina.
- **Teléfono en Mi perfil (A6, E11):** el teléfono nuevo se confirma con un código de 6 dígitos, como el correo. El código llega al correo actual o, si también cambia el correo, al correo nuevo (un solo código para los dos). Hasta confirmarlo se mantiene el anterior. Quitar el teléfono no pide código. Columna `users.telefono_pendiente`.
- **Cuenta de empresa que pasa a un rol interno (A5.5):** deja la empresa (los roles internos no tienen empresa), se borran su contraseña, su cargo y sus avisos, y le llega la invitación para crear una contraseña nueva, como a cualquier cuenta interna. Si era la cuenta principal, la empresa queda desactivada con sus mediciones y resultados. El modal de cambiar o asignar rol lo avisa antes (`App\Support\CambioDeRol`).
- **Dar una cuenta principal (A3.1d):** una empresa sin cuenta principal muestra "Sin cuenta principal" y el botón "Dar una cuenta principal". Puede ser uno de sus colaboradores activos (pasa al rol Empresa) o una cuenta nueva (recibe la invitación para crear su contraseña). Sin cuenta principal la empresa no se reactiva, y sus datos se guardan sin los del usuario. Ruta `POST /empresas/{id}/cuenta-principal` (permiso `empresas.registrar`).

### Cambiado (roles y colaboradores, 26 de octubre)

- **Desactivar un rol (A5.1c)** bloquea a sus cuentas: no entran y se les cierra la sesión hasta que les asignes otro rol. Antes no se podía desactivar un rol con cuentas. Al desmarcar "Rol activo" sale el aviso con cuántas cuentas quedan fuera; en A5 esas cuentas dicen "Rol inactivo: no puede entrar". Nadie desactiva su propio rol.
- **Páginas de cada colaborador (E12.1, E12.3):** la cuenta principal elige qué ve cada colaborador: "Responder el diagnóstico", "Resultados y Mi historial" y "Descargar el informe PDF" (pide también los resultados). La lista de E12 tiene la columna "Puede ver". Los colaboradores que ya existían conservan las tres (migración `paginas_por_colaborador`). Para agregar una página nueva basta con sumarla en `App\Support\PaginasColaborador`.
- **El colaborador no cambia su perfil ni su contraseña (E11):** Mi perfil queda en solo lectura con un aviso; sin "Cambiar contraseña". "¿Olvidaste tu contraseña?" le manda un correo que dice que se la cambia la cuenta principal. El rol Colaborador queda sin permisos; las rutas de Mi perfil ahora piden "Editar su perfil" y "Cambiar su contraseña".
- Corregido (E1): con una medición no iniciada o en curso también salían "Pedir una nueva fecha" y el correo de soporte; ahora solo salen cuando la medición venció (HU-082).
- E1 sin "Responder" muestra "Tu empresa no te dio acceso para responder el diagnóstico."; sin "Resultados" no recibe puntajes.

### Cambiado (Usuarios y roles fija, 26 de octubre)

- **A5 · Usuarios:** la página queda fija sin importar cuántas cuentas haya. La tabla muestra las filas que caben en la pantalla y el paginado de Empresas (`useFilasQueCaben`), en lugar de 12 por página con desplazamiento.
- **A5.2 · Roles:** también fija; la lista de roles y el panel del rol bajan cada uno en su espacio.
- Se quitó la ruta pequeña "Usuarios y roles" de arriba, que repetía el título.

### Cambiado (decisiones de NuevasTIC, 23 de octubre)

- **Preguntas opcionales:** respondidas son un dato extra. La IA las lee (van marcadas como `dato_extra`), pero no cuentan en el puntaje; el resultado las muestra como "Dato extra".
- **"Probar con un ejemplo":** 100 pruebas al día entre todos los administradores (PA-003). La ventana dice cuántas quedan.
- **Al borrar para siempre una empresa**, sus mediciones terminadas se conservan sin nombre para estadísticas (`App\Support\Anonimizar`, migración `2026_10_23_000002`). Se borran el texto libre de las respuestas, el prompt guardado, el mensaje de la asignación y el PDF; se conservan sector, versión, fechas, puntajes y niveles. Las mediciones sin terminar se borran.
- RN-028 ajustada: la IA puede usar DOFA y los demás métodos. Frases de los cuatro niveles, lista de sectores, carácter especial de la contraseña y resumen semanal (lunes 7:50 a. m.) confirmados.
- `.env.example`: `OPENAI_MODEL="gpt-4.1-mini"`, el modelo que paga NuevasTIC.

### Agregado (nada igual a la contraseña actual, 22 de octubre)

- **En Mi perfil, la contraseña nueva no puede repetir partes de la actual:** ningún trozo de 4 caracteres seguidos o más, sin importar mayúsculas ni tildes ("Mesa-47-Sol" → "Mesa-99-Luna" no pasa). La lista de requisitos lo marca en vivo ("Nada igual a la contraseña actual"). Regla `SinPartesDeLaAnterior`.
- Al recuperar la contraseña (L4) y cuando la empresa cambia la de un colaborador no se conocen las partes de la anterior, porque está cifrada. Ahí, y también en Mi perfil, la nueva **no puede ser ninguna de las últimas 5**, aunque cambien mayúsculas y minúsculas (tabla `contrasenas_anteriores`, `HistorialContrasenas`). L4 lo avisa debajo de los requisitos. Las cuentas de antes de este cambio empiezan su historial con la próxima contraseña.

### Agregado (plazo de las mediciones, 22 de octubre)

- **Mínimo 3 días para responder.** Al asignar una medición (A3.1b, A3.2, ficha) o darle nueva fecha, la fecha límite más cercana que se acepta es dentro de 3 días. El calendario no deja elegir antes y el servidor lo valida. Se cambia con `PLAZO_MINIMO_DIAS`.
- **La fecha límite se cuenta en la hora de cada empresa:** vale hasta las 11:59 p. m. en la zona horaria de su cuenta principal (Mi perfil). La tarea `mediciones:vencer` corre cada hora para que cada empresa venza a su medianoche. E1 dice "Vence hoy a las 11:59 p. m.".

### Corregido (hora del sistema, 22 de octubre)

- El sistema contaba el día en hora UTC: en Colombia, una medición con fecha límite de hoy dejaba de poderse responder a las 7 p. m., y después de las 7 p. m. no se podía elegir "hoy" como fecha. Ahora usa la hora de cada empresa.
- La limpieza de cuentas (03:10) y el resumen semanal (lunes 07:50) corrían en hora UTC; ahora corren en hora de Colombia (`ZONA_HORARIA_POR_DEFECTO`).

### Corregido

- **Teléfono (L2, A3.2, A3.1d, A6, E11):** se elige el indicativo del país y el campo solo acepta los dígitos de ese país (Colombia 10, Argentina 11…). Se guarda como "+57 3001234567" y el servidor valida el largo (`TelefonoValido`, `App\Support\Telefonos`).
- **Informe PDF:** las etiquetas largas del radar se cortaban en el borde; ahora se parten en dos líneas. La página 1 suma "Tus categorías por nivel" y lo más fuerte y lo más débil, y la página 3 lista, después de las 3 prioridades, las demás categorías con su recomendación.
- A1 decía "Tiene la medición N en curso" aunque la pendiente no estuviera iniciada.

### Agregado (métodos de análisis y resultado de ejemplo, 14 de octubre)

- **La IA elige el método** de cada categoría según las respuestas (PESTEL, 5 Fuerzas de Porter, VRIO, DOFA o CAME) y el resultado y el PDF muestran cuál usó y por qué (DEC-019).
- **A2.6 · Resultado de ejemplo** (HU-030): mejor caso, caso medio y peor caso con el mismo cálculo que un resultado real, y el formato del PDF.
- La IA acepta el puntaje de las abiertas como `45.0` o `"45"` y el error dice qué devolvió si no la califica.

### Agregado (opción "Ninguna", 13 de octubre)

- En selección múltiple, una opción puede excluir a las demás ("Ninguna", "Nada"): al marcarla se desmarcan las otras, y el servidor no las acepta juntas. Columna `opciones.exclusiva`.

### Agregado ("Ver como", 13 de octubre)

- **A5.4 · "Ver como"** (HU-053, HU-054): desde Usuarios (A5) o la ficha de la empresa (A3.1), el Administrador ve el sistema como otra cuenta, con su menú y sus datos, en solo lectura, con la franja de aviso y "Salir de Ver como". Queda registrado quién lo usó y cuándo (RN-026).

### Agregado (historial, 13 de octubre)

- **E8 · Mi historial** (HU-067) y **A3.4 · Historial de una empresa** (HU-039): la pendiente, la evolución desde la 2.ª medición y las mediciones terminadas con resultado, respuestas y PDF. "Ver historial completo" en E1 y A3.1.

### Agregado (configuración de la IA, 13 de octubre)

- **A4 · Configuración de la IA** (HU-041 a HU-043): instrucciones generales con los datos que se insertan solos y "Restaurar texto original"; ajuste por sector y por empresa con usar, agregar o reemplazar; quitar ajuste.
- **A4.4 · Instrucciones completas** (HU-044) con el origen de cada parte, y **A4.7 · Probar con un ejemplo** (HU-045) sin guardar nada.
- **A4.9 · Cómo se arma el prompt final** (HU-080).
- El prompt reemplaza `{sector}`, `{contexto_sector}`, `{categoria}` y `{respuestas_categoria}`.

### Agregado (análisis de la IA y resultados, 13 de octubre)

- **Cálculo de puntajes** (HU-068): única, múltiple con tope 100, abiertas por la IA, promedio por categoría y total ponderado; nivel y variación contra la medición anterior.
- **Análisis con la IA por categoría** (HU-069, HU-081): prompt por capas (general, sector, empresa), "Ten en cuenta" solo de lo elegido, recomendación con título y detalle, 3 intentos por categoría y correo a los administradores si falla.
- **Publicar el resultado** (HU-084): inmutable, medición "Terminada", correo a la empresa y PDF de 3 páginas guardado.
- **E4** con avance real y "Reintentar esas categorías"; **E6 / A3.3 · Resultado**, **E9 / A3.5 · Mis respuestas** y descarga del **PDF**. Enlaces desde E1, A1 y A3.1.
- `IA_DE_PRUEBA` en `.env.example` para recorrer el flujo sin clave de OpenAI.

### Cambiado

- `recomendacion` de la IA pasa de texto a `{ titulo, detalle }`; los `id` de las preguntas son su `ref` ("p12").

### Agregado (la empresa responde, 13 de octubre)

- **E1 · Inicio de la empresa** con la medición pendiente en cada estado, el último resultado, la evolución y las áreas a trabajar.
- **E2 · Responder:** categorías, preguntas, guardado automático de cada respuesta y "Guardar y salir"; al volver sigue donde iba.
- **E3 · Revisar antes de enviar** y **E4 · Enviado**.
- **Pedir una nueva fecha** cuando la medición venció (HU-082): una solicitud a la vez, con aviso por correo a los administradores.
- `SOPORTE_EMAIL` en `.env.example`.

### Agregado (editor de diagnósticos, 13 de octubre)

- **A2.1 · Editor:** categorías con su estado, preguntas que se ordenan arrastrando (o con el teclado), agregar, editar y eliminar preguntas (A2.1b, A2.1e, A2.1f) con opciones, puntajes, indicación y "Ten en cuenta" para la IA.
- **Agregar categoría** del catálogo, manteniendo los pesos o repartiendo en partes iguales.
- **A2.1c · Editar importancia:** total en vivo, guardar solo con 100%.
- **A2.4 · Vista previa y estado:** los 5 requisitos para publicar, categorías por corregir con "Corregir" y el cuestionario como lo ve la empresa.
- **Versiones y publicar (A2.1d):** historial, nota de cambios obligatoria y qué hacer con las mediciones pendientes. Cada cambio a un diagnóstico publicado abre el siguiente borrador.
- Crear y duplicar un diagnóstico ahora abren el editor.

### Agregado (correo de aviso y vencidas, 13 de octubre)

- **A3.1e · Correo de aviso:** asunto y mensaje con variables, vista previa con los datos reales, "Enviarme un correo de prueba" y "Guardar como plantilla". Se abre desde A3.1b.
- **Recordatorio y Reenviar aviso** (A1b, A1c, HU-008) en el Inicio y en la ficha; se guarda y se muestra el último aviso (`mediciones.ultimo_aviso_en`).
- El correo de aviso va en la cola (no bloquea la pantalla y se reintenta).
- **Tarea diaria `mediciones:vencer`** (HU-070): las mediciones sin enviar que pasaron su fecha límite quedan vencidas.
- Corregido: "Nueva fecha" desde el Inicio abría el modal sin la fecha actual.

## Semana 3 · del 5 al 11 de octubre de 2026

### Agregado

- Pantallas de acceso con el diseño del wireframe: L1 (iniciar sesión), L2 (registrar la empresa), L3 (olvidé mi contraseña) y L4 (nueva contraseña).
- Diagnósticos del Administrador: A2 (un sector), A2·T (todos), A2b (sector vacío), modales de sector (A2.2 a A2.2e), duplicar (A2.7), archivar y eliminar.
- Catálogo de categorías (A2.3) según el wireframe: filtro Todas / En uso / Archivadas, panel para editar, modales de crear y archivar, y «Restaurar».
- Crear diagnóstico (A2.5), en blanco o copiando uno publicado.
- Componentes base de formulario: `Campo`, `Entrada`, `Seleccion`, `AreaTexto`, `CampoContrasena`, `RequisitosContrasena`, `Aviso` y `TarjetaOpcion`.
- `lib/contrasena.ts` (requisitos de RN-001, con pruebas) y `lib/rutas.ts` (URLs propuestas).
- Vistas previas con datos de ejemplo: `/prueba-tecnica/vistas/{vista}` lee `resources/datos-ejemplo/{vista}.json`.
- Documentos `14_FRONTEND.md`, `15_BACKEND.md` y `18_UI_UX.md`.
- Backend mínimo para el registro: tablas `sectores` y `empresas`, roles y permisos, 7 sectores de ejemplo (luego 6, sin Talleres), Administrador inicial (`migrate --seed`) y registro que crea la empresa con su usuario de rol Empresa.
- Inicio provisional en español para revisar el menú de cada rol.
- Logo del sistema en el menú, el acceso y el ícono del navegador. El sistema se llama **Captter**.
- Mi perfil (A6 y E11) conectado: datos personales, datos de la empresa para la cuenta principal, avisos por correo, foto o logo y cambio de contraseña. Se guarda el último acceso.
- Al abrir el sistema (`/`) se entra directo al inicio de sesión.
- Seguridad del acceso completa: cuentas y empresas desactivadas no entran, contraseña fuerte en todos los entornos, mismo aviso en la recuperación, enlace sin vencimiento, límite de intentos, mensajes y correo en español, constancia de los términos (DEC-014).
- Diccionario de datos de las tablas del acceso.
- Usuarios y roles (A5, A5.1–A5.5): lista de cuentas con búsqueda y filtros, roles del sistema y creados, modales de invitar, desactivar, cambiar y asignar rol, crear y eliminar rol, conectados al backend (invitaciones por correo, desactivar, cambiar y asignar rol, CRUD de roles). "Ver como" llega con A5.4.
- Colaboradores de la empresa (E12, E12.1–E12.3): lista, agregar, datos para compartir, cambiar contraseña, desactivar y reactivar. Conectado al backend (`ColaboradoresController`): solo la cuenta principal entra; cambiar la contraseña o desactivar cierra las sesiones del colaborador.
- Documentos 02 (visión y objetivos), 04 (stakeholders), 06 (requisitos no funcionales), 09 (flujos, con el diagrama de secuencia de la medición) y 10 (arquitectura).

### Agregado (Empresas, 11 de octubre)

- **A3 · Empresas:** lista con búsqueda, filtros (sector, nivel, cuenta y medición) y paginado por filas que caben.
- **A3.1 · Ficha:** último puntaje, variación, gráfica de evolución con franjas de nivel, mediciones, la pendiente con "Cambiar fecha" y "Cancelar medición", y "Asignar otra medición".
- **A3.1d · Datos de la cuenta** y **A3.1f · Desactivar / Reactivar** (nadie de la empresa entra; se conservan sus mediciones).
- **A3.2 · Registrar empresa:** la empresa y su usuario principal, con invitación para crear la contraseña y la primera medición opcional.
- **A3.1b · Asignar medición** con buscador, borradores visibles pero bloqueados, vista previa "Así lo verá la empresa", aviso por correo y **A3.1c** para reemplazar la pendiente.
- A1: "Nueva fecha", "Cancelar" y "Asignar nueva" conectadas.
- Backend: `EmpresasController`, `MedicionesController`, `DatosEmpresas`, `AsignarMedicion`, `Invitaciones`, notificaciones `AvisoMedicion` y `EmpresaDesactivada`.
- El número de medición es único por empresa solo entre las no canceladas (índice parcial): la que reemplaza toma el mismo número.

### Agregado (modelo de datos y backend de A2, 8 de octubre)

- Migraciones, modelos y fábricas de todo el MER (T-045, T-058): categorías, diagnósticos, sus categorías con importancia, preguntas, opciones, versiones congeladas en JSONB, mediciones, respuestas, análisis de la IA, resultados, solicitudes de medición, prompts, plantilla de correo y registro de "Ver como".
- Datos iniciales (T-046): las 10 categorías del wireframe (`CategoriasSeeder`) y los niveles de RN-019 en `App\Support\Niveles`.
- Backend de A2 conectado (T-049, T-050): diagnósticos (listar, crear en blanco o copiando, duplicar, archivar, eliminar el borrador), sectores (crear, editar, reasignar con aviso por correo, desactivar, reactivar, eliminar) y catálogo de categorías (crear, editar, archivar, restaurar, eliminar). Pruebas en `tests/Feature/DiagnosticosYCatalogoTest.php`.
- Redirección después del login según el rol (T-048): `/dashboard` lleva a A1 (`/inicio`) o a E1 (`/mi-inicio`). A1 y E1 son pantallas de bienvenida hasta las semanas 5 y 6.
- Documentos: `08_CASOS_DE_USO.md` (T-041) y `30_RIESGOS.md` (T-067). Diccionario de datos de todas las tablas, cada tabla con un ejemplo y los campos JSONB (T-040, T-064). Rutas por módulo en `15_BACKEND.md` (T-043). Tabla historia → requisito en `05_REQUISITOS_FUNCIONALES.md` (T-061). Términos técnicos nuevos en el glosario (T-068).

### Cambiado (Diagnósticos, A2)

- La página queda fija; solo bajan y suben la lista de sectores, la tabla de diagnósticos y la de empresas, cada una en su espacio.
- Barra de búsqueda de diagnósticos por nombre.
- Dentro de un mismo sector, dos diagnósticos no pueden tener el mismo nombre (sin mirar mayúsculas ni espacios de más).
- "Todos los diagnósticos" ya no agrupa por sector: tiene la columna "Sector" antes de "Estado".
- Catálogo de categorías: «Archivar» está siempre disponible (en la fila y en el modal de editar); «Eliminar» aparece además cuando ninguna empresa ha respondido la categoría.
- Zona horaria en Mi perfil: ahora ofrece las 45 zonas oficiales (IANA) de los 18 países, como «México · Tijuana (UTC−7)», en vez de 5. Si una cuenta tenía una zona de antes que ya no está (Europe/Madrid), se conserva.
- El menú lateral se puede cerrar y abrir (botón junto al logo): cerrado queda una barra angosta con los iconos de cada sección (el nombre sale al pasar el ratón), la inicial de la cuenta y «Cerrar sesión». Se recuerda en el navegador. En el celular el menú sigue igual.
- **A1 · Inicio del Administrador** (HU-006, HU-007): 4 indicadores (empresas registradas, mediciones pendientes, diagnósticos completados este mes y puntaje promedio), tabla «Mediciones» con pestañas por estado, avance por categorías y paginado, «Empresas por nivel» y «Diagnósticos por sector». Las acciones de la tabla llevan por ahora a la ficha de la empresa.
- El nombre del sistema se muestra en mayúsculas, «CAPTTER», junto al logo (menú, acceso y barra del celular).
- El registro (L2) cabe en la pantalla sin bajar, en los dos pasos, desde 1280 × 720: nombre de la empresa y sector lado a lado, sin los títulos «Mi empresa» y «Tu usuario» (ya los dice la barra de pasos), descripción de 2 líneas y menos espacio entre campos.
- El nombre del sector admite hasta 60 caracteres (antes 40), para que quepan todas las divisiones CIIU.
- Nombres cortos en el catálogo CIIU (71 divisiones y 88 clases, del Excel del equipo); se guarda también el nombre oficial del DANE. Los subsectores existentes toman el nombre corto.
- Catálogo CIIU Rev. 5 A.C. del DANE en la base (87 divisiones, 544 clases). Al crear o editar un sector se elige su división CIIU y sus clases pasan a ser los subsectores; si el nombre es exactamente el de una división, se elige sola. Los 6 sectores iniciales pasan a la Rev. 5 (DEC-018).
- Se quitó el sector «Talleres» (y sus 6 actividades CIIU) de los datos iniciales: quedan 6 sectores, como dice T-046.
- Eliminar varios diagnósticos a la vez: casillas en la tabla, "Eliminar seleccionados" y confirmación. Solo los que nunca se publicaron (`DELETE /diagnosticos`).
- Se quitó "Empresas del sector" de A2 para ganar espacio; queda para la lista de Empresas filtrada por sector (A3). El resumen del sector enlaza a ella.
- Listas paginadas en lugar de bajar y subir: todos los diagnósticos y los de cada sector (las filas que caben en la pantalla) y el catálogo de categorías (10 por página). Componente `Paginacion` y `lib/paginacion.ts`, con pruebas.
- Catálogo de categorías (A2.3) y Crear diagnóstico (A2.5): flecha "← Diagnósticos" arriba del título para volver, en lugar de la ruta pequeña "Diagnósticos › …". En Crear vuelve al sector del que se vino.
- Catálogo de categorías (A2.3): "Editar" abre un modal en el centro en lugar del panel de la derecha; la tabla usa todo el ancho.
- Duplicar, Archivar y Eliminar pasan a un menú dentro de "Editar ▾" para ahorrar espacio.

### Cambiado (registro)

- El registro (L2) va en dos pasos: Mi empresa y Tu usuario. Se agregan la actividad económica (códigos CIIU por sector, tabla `actividades_economicas`) y la descripción corta de máximo 300 caracteres; el cargo pasa a ser obligatorio (DEC-015).

### Cambiado (ubicación)

- País → departamento → ciudad en el registro y en Mi perfil, con búsqueda al escribir. Los 18 países de Hispanoamérica; la ciudad se puede escribir si no está en la lista. Nueva columna `departamento`, ruta `GET /ubicaciones/{pais}`, componentes `Combobox` y `SelectorUbicacion`, comando `ubicaciones:generar` (DEC-016).

### Agregado (eliminar cuentas)

- El Administrador puede eliminar cuentas desde Usuarios y roles: "Desactivar / Eliminar" con segunda confirmación escribiendo el correo exacto. La cuenta sale de la vista pero se puede recuperar durante 90 días (filtro «Eliminadas» → "Recuperar"); después la tarea diaria `cuentas:purgar` la borra para siempre. La cuenta principal se elimina y se recupera con su empresa y sus colaboradores; nunca la propia ni el último Administrador (DEC-017).

### Cambiado (colaboradores)

- La empresa puede eliminar colaboradores ("Desactivar / Eliminar", doble confirmación con botones); el Administrador los recupera en 90 días.
- "Cambiar contraseña" de un colaborador pasa a ser "Editar": nombre, cargo, correo y, si se quiere, contraseña nueva.
- Al agregar un colaborador se pide su cargo (Marketing, Producción…, o escrito). La tabla de Colaboradores muestra el cargo y ya no el último acceso.

### Quitado

- La columna "Último acceso" de Usuarios y roles (A5). El dato se sigue guardando en `users.ultimo_acceso_en`.
- Las cuentas de demostración (`DemoSeeder`, `DEMO_PASSWORD`). El equipo trabaja con cuentas creadas por ellos mismos.

### Cambiado (Mi perfil de la empresa)

- E11 muestra y deja cambiar a la cuenta principal la actividad económica (subsector) y la descripción corta; el colaborador las ve bloqueadas.

### Cambiado (formularios)

- Asterisco rojo en los campos obligatorios de todos los formularios.
- La ciudad escrita a mano solo se guarda eligiendo "Usar «…»"; un texto a medias ya no se acepta.

### Cambiado (inicio de sesión)

- Cuenta o empresa desactivada: con la contraseña correcta, L1 muestra el modal "Su cuenta ha sido desactivada. Diríjase a Captter para saber más detalles."; con la contraseña equivocada sigue el mensaje genérico (RN-005). También se muestra si la desactivan con la sesión abierta.
- Después de 5 intentos fallidos en un minuto, L1 muestra el modal "Demasiados intentos" con la cuenta regresiva y desactiva el botón hasta que pase.

### Cambiado (roles)

- La matriz de roles y permisos sigue el wireframe A5.2: 15 permisos en 6 bloques; Administrador 15, Empresa 5, Colaborador 5 (también edita su perfil). Rol Consultor creado inactivo; los roles guardan descripción, si están activos y si son del sistema. «Colaboradores» del menú se decide por el rol Empresa.

### Cambiado

- `AuthLayout` reemplaza los layouts de acceso del kit.
- Los modales enfocan el primer campo al abrirse.
- Se quitaron las páginas de ajustes del kit y la opción de eliminar la cuenta (RN-004).
- El fondo del menú lateral llega hasta abajo en páginas largas.

## Semana 2 · del 28 de septiembre al 4 de octubre de 2026 (cerrada el 5 de octubre)

### Agregado

- Proyecto creado con el kit de inicio de Laravel para Vue: Laravel 13, Inertia 3, Vue 3.5, Tailwind 4 y Reka UI.
- Laravel Sail con PHP 8.4, PostgreSQL 18, Mailpit y Chromium (Dockerfile publicado en `docker/8.4`).
- Versiones fijas: `composer.lock`, `package-lock.json`, `.nvmrc` (Node 24) y PHP 8.4 en `config.platform`.
- Librerías del stack:
  - spatie/laravel-permission, openai-php/laravel, spatie/laravel-pdf con Browsershot y Pest 5;
  - Chart.js, vue-chartjs, chartjs-plugin-annotation, vue-draggable-plus y puppeteer;
  - las fuentes IBM Plex.
- Prueba técnica:
  - ruta bloqueada por rol;
  - análisis de una categoría con OpenAI (`prueba:ia`);
  - radar de Chart.js;
  - PDF con el radar (`prueba:pdf`).
- Estilos del wireframe en Tailwind y componentes base en `resources/js/components/base/`.
- Vitest configurado (`npm test`) y CI con PHP 8.4, Node 24 y PostgreSQL 18.
- Documentación inicial: `CLAUDE.md`, `README.md`, `.env.example` y los documentos de `docs/` marcados en el índice.

### Cambiado

- Guzzle 8 → 7, que es la versión que exige openai-php/laravel (DEC-009).
- PHPUnit 12 → 13, que es la versión que exige Pest 5 (DEC-007).
- El menú lateral del kit se reemplazó por el del wireframe (`MenuLateral.vue` y `PanelLayout.vue`).

### Quitado

Funciones del kit que no están en el diseño (DEC-012):

- verificación de correo;
- 2FA;
- passkeys;
- confirmación de contraseña;
- modo oscuro (apariencia).

## Semana 1 · del 21 al 27 de septiembre de 2026

- Wireframes, mapas de flujo y prototipo navegable: 77 vistas, 55 pantallas y 22 modales (T-001). Fuera del repositorio.
