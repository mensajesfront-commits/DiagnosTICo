# UI/UX

**Estado:** EN CURSO (T-066, T-089). Se agregan las pantallas de cada semana.

**[INFORMACIÓN PENDIENTE]** Falta el enlace al prototipo navegable. Los wireframes recibidos son 5 PDF parciales:

- L1–L4, el inicio y los diagnósticos del Administrador, y la experiencia de la empresa (los 3 primeros);
- `selection_3-1` y `selection_3-2` (6 de octubre): catálogo de categorías, Empresas, asignar medición, correo de aviso, registrar empresa, resultado, historial y respuestas vistos por el Administrador, Configuración IA, Usuarios y roles, «Ver como» y Mi perfil.

> Los wireframes van a cambiar: con las pruebas se ve qué se puede mejorar (aviso del equipo, 6 de octubre). Cuando llegue una versión nueva, se compara con las pantallas construidas y las diferencias se anotan en la tabla del final.

## Colores

Los colores están en `resources/css/app.css` y salieron de los wireframes. En las clases de Tailwind se usan por nombre (`bg-marca`, `text-tinta-suave`).

| Nombre | Color | Uso |
|---|---|---|
| `marca` | `#2d4a7a` | Botones principales, enlaces, selección |
| `lienzo` | `#f5f4f0` | Fondo de todas las pantallas |
| `tinta` / `tinta-suave` | `#1e2533` / `#5f6470` | Texto principal y secundario |
| `linea` | `#e3e2dd` | Bordes de tarjetas y tablas |
| `menu` / `menu-activo` | `#1e293b` / `#334259` | Menú lateral y su opción activa |
| `aviso` | `#a8362e` | Errores, vencidas, botón de eliminar |

Niveles del resultado (RN-019). Cada nivel tiene un color fuerte (barras y gráficas) y uno suave (fondo de la etiqueta):

| Nivel | Puntaje | Color |
|---|---|---|
| Crítico | 0–29 | `#d85249` |
| Se puede mejorar | 30–59 | `#e39938` |
| Vas en buen camino | 60–79 | `#4a85c7` |
| Sigue así | 80–100 | `#4e9954` |

## Nombre

El sistema se llama **Captter** (6 de octubre). Aparece junto al logo en el menú y en las pantallas de acceso, y en el título de la pestaña (`APP_NAME`).

## Logo

- Aro azul oscuro (`#14154d`) con una «C» gris claro (`#e6e8ea`) y el punto y la señal en verde (`#6ae45b`).
- Componente `resources/js/components/marca/Logo.vue`, en SVG; el tamaño se da con la clase (`size-10`).
- Va en el menú lateral, en el panel de las pantallas de acceso y en la barra superior del celular.
- El ícono de la pestaña del navegador sale del mismo dibujo: `public/favicon.svg`, `favicon.ico` y `apple-touch-icon.png`.

## Tipografía

- **IBM Plex Sans** para todo el texto.
- **IBM Plex Mono** para cifras, puntajes y contadores ("8/40", "2 de 5").
- Se sirven desde el proyecto (`@fontsource`), no desde un CDN (DEC-010).

## Criterios de diseño

- **Formularios:** etiqueta pequeña encima del campo, ayuda gris debajo y contador a la derecha. El error reemplaza a la ayuda, en rojo.
- **Modales:** título, explicación de qué pasará, pie con "Cancelar" a la izquierda y la acción a la derecha. Las acciones que no se deshacen usan el botón rojo y lo dicen ("No se puede deshacer").
- **Acciones bloqueadas:** no se esconden; se explica por qué no se puede y qué hacer ("Eliminar sector · no disponible…").
- **Estados vacíos:** dicen qué falta y ofrecen el siguiente paso ("Este sector todavía no tiene diagnósticos" + botones).
- **Accesibilidad:**
  - campos con `label`;
  - errores con `role="alert"`;
  - el foco va al primer campo al abrir un modal;
  - en las etiquetas, el estado no depende solo del color: llevan símbolo y texto (✓ ○ ◐ ⚠).

## Pantallas construidas

| Código | Pantalla | Componente | Estado |
|---|---|---|---|
| L1 | Iniciar sesión | `pages/auth/Login.vue` | Hecha |
| L2 | Registra tu empresa | `pages/auth/Register.vue` | Hecha y conectada (dos pasos, DEC-015 y DEC-016) |
| L3 | ¿Olvidaste tu contraseña? | `pages/auth/ForgotPassword.vue` | Hecha |
| L4 | Crea una contraseña nueva | `pages/auth/ResetPassword.vue` | Hecha |
| A1 | Inicio del Administrador: indicadores, mediciones con pestañas, empresas por nivel y diagnósticos por sector | `pages/inicio/Administrador.vue`, `components/inicio/` | Hecha y conectada. todas las acciones conectadas menos "Ver resultado" (espera A3.3) |
| A2 | Diagnósticos de un sector | `pages/diagnosticos/Index.vue` | Hecha y conectada |
| A2·T | Todos los diagnósticos | `pages/diagnosticos/Index.vue` | Hecha y conectada |
| A2b | Sector sin diagnósticos | `pages/diagnosticos/Index.vue` | Hecha y conectada |
| A2.2 / A2.2a | Editar / crear sector | `components/diagnosticos/modales/ModalSector.vue` | Hecha |
| A2.2b | Reasignar empresas | `…/ModalReasignarSector.vue` | Hecha |
| A2.2c | Desactivar / reactivar sector | `…/ModalEstadoSector.vue` | Hecha. Sin wireframe, sale de HU-015. |
| A2.2d / A2.2e | No se puede eliminar / eliminar sector | `…/ModalEliminarSector.vue` | Hecha |
| A2.7 | Duplicar diagnóstico | `…/ModalDuplicarDiagnostico.vue` | Hecha |
| — | Archivar diagnóstico | `…/ModalArchivarDiagnostico.vue` | Hecha. Sin diseño (PA-005). |
| — | Eliminar diagnóstico o borrador | `…/ModalEliminarDiagnostico.vue` | Hecha |
| A2.3 | Catálogo de categorías; editar abre un modal en el centro | `pages/categorias/Index.vue`, `components/categorias/ModalEditarCategoria.vue` | Hecha y conectada |
| A2.3b | Crear / editar categoría | `components/categorias/ModalCategoria.vue` | Hecha |
| A2.3c | Archivar o eliminar categoría | `components/categorias/ModalRetirarCategoria.vue` | Hecha. Eliminar no tiene wireframe, sale de HU-019. |
| A2.5 | Crear diagnóstico | `pages/diagnosticos/Crear.vue` | Hecha y conectada |
| A2.1 | Editor del diagnóstico | `pages/diagnosticos/Editor.vue`, `components/editor/` | Hecha y conectada |
| A2.1b / A2.1e | Agregar / editar pregunta | `components/editor/ModalPregunta.vue` | Hecha |
| A2.1f | Eliminar pregunta | `components/editor/ModalEliminarPregunta.vue` | Hecha |
| A2.1d | Versiones y publicar | `components/editor/ModalVersiones.vue` | Hecha |
| A2.1d | Agregar categoría al diagnóstico | `components/editor/ModalAgregarCategoria.vue` | Hecha |
| A2.1c | Editar importancia | `pages/diagnosticos/Importancia.vue` | Hecha y conectada |
| A2.4 | Vista previa y estado | `pages/diagnosticos/VistaPrevia.vue` | Hecha y conectada ("Resultado de ejemplo" espera A2.6) |
| A3 | Empresas: búsqueda, filtros y tabla paginada | `pages/empresas/Index.vue` | Hecha y conectada |
| A3.1 | Ficha de la empresa: Historial y gráfica de evolución | `pages/empresas/Ver.vue`, `components/graficas/EvolucionPuntaje.vue` | Hecha y conectada ("Ver resultado" espera A3.3) |
| A3.1b | Asignar medición, con vista previa | `pages/mediciones/Asignar.vue`, `components/empresas/BuscadorEmpresa.vue` | Hecha y conectada |
| A3.1e | Correo de aviso: editar con variables, vista previa, prueba y enviar | `components/empresas/EditorCorreo.vue`, `ModalCorreoAviso.vue` | Hecha y conectada |
| A1b, A1c | Recordatorio y Reenviar aviso | `components/empresas/ModalCorreoAviso.vue` | Hecha y conectada |
| A3.1c | Reemplazar la medición pendiente | `components/empresas/ModalReemplazarMedicion.vue` | Hecha |
| — | Cambiar fecha / Cancelar medición | `ModalCambiarFecha.vue`, `ModalCancelarMedicion.vue` | Hechas. Sin wireframe propio, salen de A3.1 y HU-009 |
| A3.1d | Datos de la cuenta | `pages/empresas/Ver.vue`, `components/empresas/CamposEmpresa.vue` | Hecha y conectada |
| A3.1f | Desactivar cuenta de la empresa | `components/empresas/ModalDesactivarEmpresa.vue` | Hecha |
| A3.2 | Registrar empresa | `pages/empresas/Registrar.vue` | Hecha y conectada |
| A5 | Usuarios: lista con búsqueda, filtros y acciones | `pages/usuarios/Index.vue` | Hecha y conectada |
| A5.1, A5.1b, A5.1e | Roles del sistema (Administrador, Empresa, Colaborador) | `pages/usuarios/Roles.vue`, `components/usuarios/PanelRol.vue` | Hecha y conectada |
| A5.1c | Editar un rol creado | `components/usuarios/PanelRol.vue` | Hecha y conectada |
| A5.1d | Eliminar rol | `components/usuarios/ModalEliminarRol.vue` | Hecha |
| A5.2 | Crear rol | `components/usuarios/ModalCrearRol.vue` | Hecha |
| A5.3b | Desactivar o eliminar cuenta | `components/usuarios/ModalDesactivarEliminar.vue` | Hecha y conectada |
| A5.5 | Cambiar o asignar rol | `ModalCambiarRol.vue`, `ModalAsignarRol.vue` | Hecha |
| — | Invitar usuario interno | `components/usuarios/ModalInvitarUsuario.vue` | Hecha. El wireframe no le da código. |
| A6 | Mi perfil del Administrador | `pages/perfil/MiPerfil.vue` | Hecha y conectada |
| E1 | Inicio de la empresa: primera vez, pendiente, en curso, vencida, enviada y sin pendiente | `pages/inicio/Empresa.vue` | Hecha y conectada (los enlaces a resultado, historial y PDF esperan E6–E8) |
| E2 | Responder el diagnóstico | `pages/responder/Responder.vue` | Hecha y conectada |
| E3 | Revisa antes de enviar (y confirmar el envío) | `pages/responder/Revisar.vue` | Hecha y conectada |
| E4 | Diagnóstico enviado | `pages/responder/Enviado.vue` | Hecha (el avance real del análisis espera HU-069) |
| E11 | Mi perfil de la empresa (y del colaborador) | `pages/perfil/MiPerfil.vue` | Hecha y conectada |
| E12 | Colaboradores de la empresa | `pages/colaboradores/Index.vue` | Hecha y conectada |
| E12.1 | Agregar colaborador | `components/colaboradores/ModalAgregarColaborador.vue` | Hecha |
| E12.2 | Comparte estos datos | `components/colaboradores/ModalDatosDeAcceso.vue` | Hecha |
| E12.3 | Editar un colaborador (datos y contraseña) | `components/colaboradores/ModalEditarColaborador.vue` | Hecha y conectada |

## Diferencias con el wireframe

| Pantalla | Diferencia | Motivo |
|---|---|---|
| L2, L4 | La lista de requisitos de la contraseña tiene 5 puntos (agrega "Al menos un carácter especial"); el wireframe muestra 4. | RN-001 lo exige. **[INCONSISTENCIA DETECTADA]**, pendiente de decidir. |
| L1–L4 | El panel oscuro ocupa el 42 % de la pantalla; en el wireframe es más angosto. | Lo pidió el equipo (6 de octubre). |
| L1 | No tiene la "Nota del prototipo" con atajos de entrada. | El wireframe aclara que no es parte del diseño. |
| E12 | "Desactivar" pasa a "Desactivar / Eliminar"; eliminar se confirma con dos botones ("Eliminar" → "Sí, eliminar a…"). Los desactivados tienen "Reactivar · Eliminar". | Pedido por el equipo (8 de octubre, DEC-017): sin escribir el correo, porque se puede cambiar. |
| E12.3 | "Cambiar contraseña" pasa a ser "Editar": nombre, cargo, correo y, opcional, contraseña nueva. | Pedido por el equipo (8 de octubre). El wireframe solo cambia la contraseña. |
| E12, E12.1 | Columna "Cargo" en lugar de "Último acceso"; al agregar un colaborador el cargo es obligatorio (lista con búsqueda: Marketing, Producción… o escrito). | Pedido por el equipo (8 de octubre). |
| A5 | Sin la columna "Último acceso": se guarda en la base (`users.ultimo_acceso_en`) pero no se muestra. "Enviada el…" (invitación) y "Se borra el…" (eliminada) van bajo el Estado. | Pedido por el equipo (8 de octubre). |
| A5, A5.3b | "Desactivar / Eliminar" abre un modal para elegir; eliminar pide escribir el correo exacto. Las desactivadas tienen "Reactivar · Eliminar" y las invitaciones "Reenviar invitación · Eliminar". | Pedido por el equipo (8 de octubre, DEC-017). El wireframe solo tiene "Desactivar". |
| Todas | Los campos obligatorios llevan un asterisco rojo (`Campo obligatorio`); los opcionales siguen con "(opcional)". | Pedido por el equipo (8 de octubre). |
| L1 | Cuenta desactivada y demasiados intentos se avisan en un modal, no bajo el campo. | Pedido por el equipo (7 de octubre). El wireframe no los dibuja. |
| L2 | El registro va en dos pasos (Mi empresa → Tu usuario), con actividad económica CIIU, descripción corta (máx. 300) y cargo obligatorio. | Cambio pedido por el equipo (7 de octubre, DEC-015). El wireframe muestra un solo formulario. |
| L2, A6, E11 | País → departamento → ciudad en listas con búsqueda; la etiqueta del departamento cambia según el país (Estado, Provincia, Región). | Pedido por el equipo (8 de octubre, DEC-016). El wireframe muestra solo ciudad y país. |
| L2 | Los enlaces de términos y de política apuntan a `#`. | **[INFORMACIÓN PENDIENTE]** Faltan las URL reales. |
| A2.5 | En blanco, se eligen las categorías con casillas y se muestra cuánto vale cada una al empezar. | HU-020 pide elegir las categorías; el wireframe solo muestra la opción de copiar. |
| A2.3 | Si la categoría nunca se respondió, la acción es «Eliminar» en vez de «Archivar». | HU-019 y RN-009. El wireframe solo muestra «Archivar». |
| A2.3 | Al abrir, el panel muestra la primera categoría; con «Cancelar» queda vacío con una indicación. | El wireframe solo muestra el panel con una categoría elegida. |
| Todas | Dice «Captter» donde el wireframe dice «Diagnóstico Empresarial». | Nombre del sistema decidido por el equipo (6 de octubre). |
| A6 | La lista de requisitos de la contraseña tiene 5 puntos; el wireframe muestra 3. | RN-001. **[INCONSISTENCIA DETECTADA]** (también en L2 y L4). |
| E11 | Al cambiar el correo se guarda directo, sin enlace de verificación. | La verificación de correo está quitada (DEC-012). **[FUNCIONALIDAD POR DEFINIR]** |
| E11 | El colaborador ve «Información de empresa» bloqueada, con una nota. | RN-025: solo la cuenta principal cambia esos datos. El wireframe solo muestra la cuenta principal. |
| A5 | "Ver como" no aparece en las cuentas de Administrador. | Respuesta del equipo (6 oct): "Ver como" es para revisar lo que ven los otros roles. |
| A5.5 | "Cambiar rol" no ofrece "Colaborador", y a las cuentas internas tampoco "Empresa". | Un colaborador lo crea su empresa (RN-025); una cuenta sin empresa no puede ser Empresa. |
| A5.1c | Con un rol creado que tiene cuentas, "Eliminar rol" queda desactivado con una nota. | HU-051: solo se elimina un rol sin cuentas. |
| E12.1, E12.3 | La contraseña muestra los 4 requisitos de RN-001; el wireframe dice solo "Mínimo 8 caracteres". | RN-001 y HU-077/HU-078 piden contraseña fuerte. |
| E12 | "Desactivar" pide confirmación en un modal. | HU-079 CA-001 ("elijo Desactivar y confirmo"); el wireframe no lo dibuja. |
| E12 | Sin colaboradores, la tabla muestra un mensaje y el botón para crear. | HU-076 CA-004. |
| E12.2 | También se abre después de cambiar una contraseña (E12.3), con "Contraseña cambiada para…". | HU-078: "guardo y comparto la nueva contraseña". |
| A5 | "Quitar rol" (HU-052) no está. | Cada cuenta tiene un solo rol (RN-027); se cambia con "Cambiar rol". **[FUNCIONALIDAD POR DEFINIR]** qué rol queda al "quitar". |
| A3.1, A3.1b, A3.2 | Sin migas "Empresas › …": arriba va la flecha "← Empresas" (o "← Ficha de la empresa"). | Pedido del equipo para volver (A2). Así la página no se desplaza. |
| A3.1d, A3.2 | Agregan actividad económica (subsector CIIU), departamento y descripción corta. Los obligatorios llevan asterisco. | DEC-015, DEC-016 y el pedido del 8 de octubre. |
| A3.1 | Los colaboradores se ven en Datos de la cuenta, al pulsar "N activos" en Estado de la cuenta. "Ver historial completo" no aparece. | HU-033 CA-003 pide verlos; el wireframe solo muestra el número. A3.4 llega con Resultados. |
| A3.1 | La gráfica no tiene el selector "Ver: Puntaje total". | **[FUNCIONALIDAD POR DEFINIR]** Ver por categoría necesita los resultados por categoría (semana 5). |
| A3.1b | Sin "unos 25 minutos" en cada diagnóstico. | **[INFORMACIÓN PENDIENTE]** No hay una regla para estimar el tiempo. |
| A1d | "Nueva fecha" abre un modal en la misma tabla, en vez de llevar a A3.1b. La medición conserva sus respuestas. | Menos pasos. HU-009 CA-002 se cumple igual; A3.1b también ofrece "dale una nueva fecha". |
| A3.1d | Al cambiar el correo no se envía enlace de verificación: se guarda y se cierran sus sesiones. | DEC-012 (verificación quitada). |
| A3.1e | Es un modal sobre A3.1b y no una página aparte; en A1 y la ficha se abre igual para Recordatorio / Reenviar aviso, con el botón "Enviar". | Así A3.1b no pierde lo ya llenado, y HU-008 reutiliza el mismo editor. |
| A2.1, A2.1c, A2.4 | Sin migas "Diagnósticos › …": arriba va la flecha para volver. El estado ("⚠ Incompleto…") va bajo el título si no cabe. | Pedido del equipo para volver (A2). |
| A2.1b | Los obligatorios llevan asterisco; la pregunta abierta tiene "Criterio para calificarla" (opcional). | Pedido del 8 de octubre y RN-013 ("la abierta tiene un único criterio"); el wireframe no dibuja la abierta. |
| A2.1 | Las preguntas también se ordenan con el teclado (flecha arriba o abajo sobre ⋮⋮). | Accesibilidad: arrastrar no sirve sin ratón. |
| A2.1b | La indicación para responder es obligatoria al guardar, pero el editor igual avisa "Falta la indicación" en preguntas copiadas sin ella. | HU-024 CA-003 la exige; el wireframe la muestra como algo por corregir. **[INCONSISTENCIA DETECTADA]** menor: las dos cosas quedan cubiertas. |
| E1 | No muestra "Unos 25 minutos". | **[INFORMACIÓN PENDIENTE]** No hay una regla para estimar el tiempo. |
| E1 (vencida) | En vez de solo "Escribir a NuevasTIC", el botón "Pedir una nueva fecha" registra la solicitud y avisa a los administradores; el correo de soporte queda debajo. | HU-082 pide que la solicitud quede registrada y una a la vez. |
| E2 | Sin la opción "Otro" con texto ("¿Cuál otro canal?"). | **[FUNCIONALIDAD POR DEFINIR]** Necesita un campo en las opciones (A2.1b). |
| E2 | La descripción de la categoría es un texto general ("No hay respuestas buenas o malas…"). | **[INFORMACIÓN PENDIENTE]** La versión congelada no guarda la descripción de cada categoría. |
