# Frontend

**Estado:** COMPLETO (T-088). Todas las pantallas del alcance están construidas (14 de octubre de 2026).

Cómo está organizado el frontend y, sobre todo, **qué datos espera cada pantalla**. Esa es la parte que necesita el backend para conectar los controladores.

## Reparto del trabajo

- **Cristian (frontend):** construye las pantallas con datos de ejemplo del wireframe y deja escrito aquí qué props espera cada una.
- **Luis (backend):** crea las rutas y los controladores que entregan esas props, y valida lo que envían los formularios.

Mientras una ruta no exista, la pantalla se revisa en `/prueba-tecnica/vistas/{vista}`. Esa ruta toma los datos de `resources/datos-ejemplo/{vista}.json`; cada JSON dice qué página abrir y con qué props, y son las mismas que debe entregar el controlador.

| Vista previa | Pantalla | Archivo de datos |
|---|---|---|
| `/prueba-tecnica/vistas/a2-abogados` | A2 · Diagnósticos de un sector | `a2-abogados.json` |
| `/prueba-tecnica/vistas/a2-todos` | A2·T · Todos los diagnósticos | `a2-todos.json` |
| `/prueba-tecnica/vistas/a2-talleres` | A2b · Sector sin diagnósticos | `a2-talleres.json` |
| `/prueba-tecnica/vistas/a2-3-categorias` | A2.3 · Catálogo de categorías | `a2-3-categorias.json` |
| `/prueba-tecnica/vistas/a2-5-crear` | A2.5 · Crear diagnóstico | `a2-5-crear.json` |
| `/prueba-tecnica/vistas/a5-usuarios` | A5 · Usuarios (y sus modales) | `a5-usuarios.json` |
| `/prueba-tecnica/vistas/a5-1-roles` | A5.1 · Roles del sistema | `a5-1-roles.json` |
| `/prueba-tecnica/vistas/a5-1c-consultor` | A5.1c · Rol creado e inactivo (Consultor) | `a5-1c-consultor.json` |

Las pantallas de acceso (L1–L4) usan las rutas reales de Fortify y no necesitan vista previa.

## Estructura

```text
resources/js/
├── components/
│   ├── base/          Piezas reutilizables (botón, campo, modal, tabla, menú…)
│   ├── diagnosticos/  Piezas de A2 y sus modales (modales/)
│   ├── categorias/    Modales del catálogo (A2.3b, A2.3c)
│   ├── graficas/      Gráficas de Chart.js
│   └── ui/            Componentes del kit (shadcn-vue / Reka UI). No se editan.
├── layouts/
│   ├── AuthLayout.vue        L1–L4: panel oscuro y formulario
│   └── panel/PanelLayout.vue Pantallas con sesión: menú lateral y contenido
├── lib/
│   ├── contrasena.ts  Requisitos de contraseña (RN-001)
│   ├── menu.ts        Secciones del menú por rol y permiso
│   ├── niveles.ts     Niveles y colores del resultado
│   └── rutas.ts       URLs de las pantallas en un solo lugar
├── pages/             Una página por pantalla del wireframe
└── types/             Tipos de los datos que llegan del backend
```

### Para agregar una pantalla

1. Crea `pages/<modulo>/<Pantalla>.vue` con un comentario arriba: código del wireframe, historia y qué envía.
2. Define sus props con tipos en `types/` y anótalas en este documento.
3. Crea `resources/datos-ejemplo/<vista>.json` con los datos del wireframe y revísala en `/prueba-tecnica/vistas/<vista>`.
4. Usa los componentes de `components/base/` antes de crear uno nuevo.

## Componentes base

- `CampoTelefono`: indicativo del país y número con el largo de ese país (`lib/telefonos.ts`, la misma lista que `App\Support\Telefonos`). Lo usan L2, A3.2, A3.1d, A6 y E11.

| Componente | Para qué |
|---|---|
| `Boton` | Primario, secundario, enlace o peligro. Con `href` es un enlace de Inertia. |
| `Campo` | Etiqueta, ayuda, error y contador de un campo. |
| `Entrada`, `Seleccion`, `AreaTexto` | Controles de formulario con el estilo del wireframe. |
| `CampoContrasena` | Contraseña con el botón "Mostrar / Ocultar". |
| `RequisitosContrasena` | Lista de requisitos que se marca en vivo. |
| `Modal` | Ventana con título, contenido y pie. Al abrir, enfoca el primer campo. |
| `TarjetaOpcion` | Opción grande con explicación ("Reasignar…", "Desactivar…"). |
| `Aviso` | Recuadro de éxito, error o información. |
| `Tarjeta`, `Tabla`, `EncabezadoPagina` | Bloques de página. |
| `Etiqueta`, `EtiquetaNivel`, `EtiquetaEstado` | Etiquetas de estado, nivel y medición. |
| `SelectorCompacto` | Pestañas en una pieza ("Todas · 11 · En curso · 3"). |
| `MenuLateral` | Menú lateral por rol, que oculta las secciones sin permiso. |

## Datos por pantalla

Los tipos exactos están en `resources/js/types/diagnosticos.ts`. Las URLs a las que envían los formularios están en `docs/15_BACKEND.md`.

### L1 · Iniciar sesión (`auth/Login`)

| Prop | Tipo | Nota |
|---|---|---|
| `status` | `string?` | Aviso de éxito; por ejemplo, "Contraseña actualizada" al volver de L4. |
| `canResetPassword` | `boolean` | Muestra "¿Olvidaste tu contraseña?". |

Envía `email`, `password` y `remember` a Fortify (`POST /login`). Los errores no deben revelar si el correo existe (RN-005).

### L2 · Registra tu empresa (`auth/Register`)

En dos pasos: **1 · Mi empresa** (con "Siguiente") y **2 · Tu usuario** (con "Atrás" y "Registrar empresa"). "Siguiente" revisa los campos del paso 1 en el navegador; si el servidor devuelve un error de un campo del paso 1, la pantalla vuelve a ese paso.

| Prop | Tipo | Nota |
|---|---|---|
| `sectores` | `{ id, nombre, actividades: { id, codigo, nombre }[] }[]` | Solo sectores activos (RN-003), en orden alfabético, cada uno con sus actividades CIIU activas. Sin sector elegido, la actividad no se puede elegir. |
| `paises` | `{ codigo, nombre, division }[]` | Los 18 países de Hispanoamérica (`App\Support\Ubicaciones`). `division` es el nombre de su departamento: Departamento, Estado, Provincia o Región. Los departamentos y ciudades los pide `SelectorUbicacion` a `GET /ubicaciones/{codigo}` (DEC-016). |
| `passwordRules` | `string` | Del kit (atributo `passwordrules`). |

Envía a `POST /register`:

| Campo | Regla esperada |
|---|---|
| `empresa_nombre` | Obligatorio |
| `sector_id` | Obligatorio; debe ser un sector activo |
| `actividad_economica_id` | Obligatorio si el sector tiene actividades; debe ser una actividad activa **de ese sector** |
| `descripcion` | Obligatorio, máximo 300 caracteres |
| `pais` | Obligatorio; uno de los 18 países (por nombre: `Colombia`) |
| `departamento` | Obligatorio; debe ser de ese país |
| `ciudad` | Obligatorio; de la lista o escrita (máx. 255) |
| `name` | Obligatorio |
| `cargo` | Obligatorio |
| `email` | Obligatorio, único (RN-002) |
| `telefono` | Opcional |
| `password`, `password_confirmation` | Contraseña fuerte (RN-001) y confirmada |
| `terminos` | Obligatorio (`accepted`) |

Ya funciona de punta a punta: `Fortify::registerView` entrega `sectores` y `paises`, y `CreateNewUser` crea la empresa y el usuario principal con el rol Empresa en una sola transacción (T-047, versión mínima; ver `15_BACKEND.md`).

### L3 · ¿Olvidaste tu contraseña? (`auth/ForgotPassword`)

| Prop | Tipo | Nota |
|---|---|---|
| `status` | `string?` | Muestra el recuadro "Revisa tu correo". |

Envía `email` a `POST /forgot-password`.

> **Para Luis:** por defecto, Fortify responde "no encontramos ese correo" cuando el correo no existe. RN-005 pide el mismo aviso siempre, exista o no.

### L4 · Crea una contraseña nueva (`auth/ResetPassword`)

| Prop | Tipo | Nota |
|---|---|---|
| `token` | `string` | Del enlace del correo. |
| `email` | `string` | Se muestra: "Para la cuenta …". |
| `passwordRules` | `string?` | Del kit. |

Envía `token`, `email`, `password` y `password_confirmation` a `POST /reset-password`. Si el enlace ya se usó, el error en `email` muestra "Este enlace ya no es válido" con "Pedir otro enlace".

### Menú lateral (`components/base/MenuLateral.vue`)

- Secciones por rol en `lib/menu.ts`, cada una con su `icono` (Lucide).
- En pantallas grandes se cierra y se abre con el botón junto al logo. Cerrado mide 64 px y muestra solo los iconos (el nombre sale en `title` y queda para lectores de pantalla), la inicial de la cuenta (Mi perfil) y el icono de cerrar sesión.
- El estado vive en `composables/useMenuPlegado.ts`: se comparte entre páginas y se guarda en `localStorage` (`captter.menu-plegado`); si el navegador no deja guardar, arranca abierto.
- En el menú del celular (`plegable = false`) siempre se ve completo.

### A1 · Inicio del Administrador (`inicio/Administrador`)

Ruta: `GET /inicio` (`InicioController@administrador`, datos de `App\Support\DatosInicio`). Tipos en `types/inicio.ts`.

| Prop | Tipo | Nota |
|---|---|---|
| `indicadores` | `IndicadoresInicio` | Empresas (y en cuántos sectores), mediciones pendientes por estado, terminadas este mes y puntaje promedio del último resultado de cada empresa |
| `mediciones` | `FilaMedicion[]` | Sin canceladas. Primero vencidas, luego en curso, no iniciadas y terminadas. `estado` es `vencida` también si pasó la fecha límite. `avance` = categorías completas |
| `niveles` | `Record<Nivel, number>` | Empresas por nivel de su último resultado; `sin` = sin diagnóstico |
| `sectores` | `SectorInicio[]` | Sectores activos con empresas y versión publicada |

- Pestañas Todas · En curso · No iniciadas · Vencidas · Terminadas con su contador (HU-007); "Enviada" cuenta como en curso.
- Página fija en pantallas grandes; la tabla va paginada con las filas que caben (`useFilasQueCaben` en `lib/paginacion.ts`, que también usa A2).
- Acciones: "Nueva fecha" (A1d) y "Cancelar" abren su modal en la misma tabla (`ModalCambiarFecha`, `ModalCancelarMedicion`); "Recordatorio" (A1b) y "Reenviar aviso" (A1c) abren el correo de la medición (`ModalCorreoAviso`, A3.1e); "Asignar nueva" (A1e) abre A3.1b con la empresa elegida.
- "Ver resultado" abre A3.3 (HU-038).
- Si alguna empresa pidió una medición nueva (HU-083), junto al título aparece el aviso; lleva a Vencidas, donde esas filas van primero con "Asignar nueva".
- "Último aviso" es `mediciones.ultimo_aviso_en`: al asignar con aviso, con Recordatorio o con Reenviar aviso.

### A2 / A2·T / A2b · Diagnósticos (`diagnosticos/Index`)

Ruta: `GET /diagnosticos` (Todos) y `GET /diagnosticos?sector={id}`.

| Prop | Tipo | Nota |
|---|---|---|
| `sectores` | `Sector[]` | Todos los sectores, activos e inactivos, con su número de diagnósticos activos. |
| `sector` | `Sector \| null` | El sector elegido; `null` en "Todos". |
| `resumen` | `ResumenDiagnosticos` | Del sector o general. En "Todos" incluye `sectores_activos`. Con sector, también `mediciones_por_estado` (todas las mediciones) para el modal de reasignar. |
| `diagnosticos` | `FilaDiagnostico[]` | Del sector, o de todos. Sin archivados. |

Comportamiento:

- **Página fija** en pantallas grandes (`lg`): ocupa el alto de la ventana y no se desplaza.
  - **La tabla de diagnósticos va paginada** (Todos y cada sector): muestra las filas que caben en su espacio, con "Mostrando 1–9 de 31 · Página 1 de 4", "Anterior" y "Siguiente" (`components/base/Paginacion.vue`, `lib/paginacion.ts`). En pantallas pequeñas, 10 por página. Al buscar, filtrar u ordenar vuelve a la página 1.
  - La lista de sectores baja y sube en su espacio.
- **Empresas del sector:** se quitó de A2 para ganar espacio. El resumen del sector tiene "Ver las N empresas en Empresas", que lleva a `/empresas?sector={id}` (A3). Los datos (diagnóstico asignado, medición y puntaje) siguen en `DatosDiagnosticos::empresas()` y el tipo `EmpresaDelSector`, para usarlos en A3.
- En pantallas pequeñas la página se desplaza normal.
- **"Todos":** no agrupa por sector; la columna "Sector", antes de "Estado", dice de qué sector es cada diagnóstico (enlace al sector).
- **Acciones por fila:** "Editar ▾" abre un menú con Editar diagnóstico, Duplicar, Archivar o Eliminar, y Eliminar borrador cuando corresponde. Al lado queda "Vista previa".
- **Eliminar varios:** cada fila tiene una casilla; solo se pueden elegir los diagnósticos que nunca se publicaron (los publicados se archivan). La casilla del encabezado elige los de la página. La selección se mantiene al cambiar de página. Con algo elegido, el pie muestra "N seleccionados · Quitar selección · Eliminar seleccionados", y el modal `ModalEliminarVarios` pide la segunda confirmación ("Sí, eliminar N diagnósticos"). Envía `DELETE /diagnosticos` con `ids[]`.
- **Búsqueda:** filtra los diagnósticos por nombre en el navegador, sin mirar tildes ni mayúsculas (`lib/texto.ts`). Funciona en un sector y en "Todos", junto con el orden y el filtro de estado.

**Volver:** A2.3 y A2.5 tienen arriba del título la flecha "← Diagnósticos" (`EncabezadoPagina`, prop `volver`). Desde A2.5 vuelve al sector del que se vino.

**Subsectores del sector (DEC-018):** el modal de crear o editar sector (`ModalSector`, A2.2a / A2.2) tiene "Subsectores · división CIIU", un `Combobox` con las 87 divisiones (prop `divisionesCiiu` de A2). Si el nombre escrito es exactamente el de una división, se elige sola. Envía `ciiu_division`. Cada `Sector` trae `ciiu_division` y `subsectores` (cuántos activos).

### A2.3 · Catálogo de categorías (`categorias/Index`)

Ruta: `GET /categorias`.

| Prop | Tipo | Nota |
|---|---|---|
| `categorias` | `Categoria[]` | Activas y archivadas. Cada una trae `diagnosticos` (activos donde se usa), `preguntas`, `versiones_publicadas`, `borradores` (nombres de los borradores que la usan) y `tiene_respuestas`, que decide entre "Archivar" y "Eliminar" (RN-009). |
| `totalDiagnosticos` | `number` | Diagnósticos activos en total, para "13 de 13". |
| `borradores` | `DiagnosticoBorrador[]` | Diagnósticos en borrador (con `version`), para agregarles una categoría nueva (HU-018 CA-004). |

La tabla usa todo el ancho y va paginada, 10 categorías por página (vuelve a la 1 al cambiar el filtro). La categoría se edita en un modal en el centro (`ModalEditarCategoria`) que envía `nombre` y `descripcion`; Cada categoría tiene "Archivar" siempre y "Eliminar" solo si nadie la ha respondido (en la fila y en el modal de editar); los dos abren A2.3c (`ModalRetirarCategoria`, prop `accion`). Crear (A2.3b) y archivar (A2.3c) abren un modal. Las archivadas tienen "Restaurar".

### A2.5 · Crear diagnóstico (`diagnosticos/Crear`)

Ruta: `GET /diagnosticos/crear?sector={id}`.

| Prop | Tipo | Nota |
|---|---|---|
| `sectores` | `Sector[]` | Solo activos. |
| `sectorId` | `number \| null` | Sector preseleccionado si se viene desde A2. |
| `categorias` | `{ id, nombre }[]` | Categorías activas del catálogo. |
| `publicados` | `DiagnosticoPublicado[]` | Última versión publicada de cada diagnóstico, de todos los sectores, con los nombres de sus categorías. |

Envía a `POST /diagnosticos`: `nombre` (máx. 60), `sector_id`, `descripcion` (opcional), `punto_partida` (`blanco` | `copia`), `categorias[]` (en blanco) y `copiar_de` (en copia). El backend crea el borrador v1 y abre el editor (A2.1).

### A3 · Empresas (`empresas/Index`)

Ruta: `GET /empresas` (`EmpresasController@index`, datos de `App\Support\DatosEmpresas::lista()`). Tipos en `types/empresas.ts`.

| Prop | Tipo | Nota |
|---|---|---|
| `empresas` | `FilaEmpresa[]` | Por nombre. Último puntaje y nivel (`sin` si no tiene), variación frente a la anterior (RN-020), mediciones terminadas, la pendiente con su estado y avance, y si la cuenta está activa |
| `sectores` | `{ id, nombre }[]` | Para el filtro |
| `sectorInicial` | `number \| null` | `?sector=` desde A2 ("Ver en Empresas") |

- Buscar por nombre (sin tildes ni mayúsculas) y filtros Sector, Nivel, Cuenta y Medición (No iniciada, En curso —incluye Enviada—, Vencida, Sin medición pendiente).
- Página fija; la tabla va paginada con las filas que caben. Sin resultados, "Quitar filtros".
- Arriba, "Asignar medición" (A3.1b) y "+ Registrar empresa" (A3.2).

### A3.1 · Ficha de la empresa (`empresas/Ver`)

Ruta: `GET /empresas/{empresa}` (`EmpresasController@ver`, `DatosEmpresas::ficha()` + `opciones()`). Props en el tipo `FichaEmpresa`.

- **Historial:** tarjetas Último puntaje, Variación (desde la 2.ª) y Medición pendiente; gráfica `graficas/EvolucionPuntaje.vue` (línea con las franjas de nivel y el puntaje sobre cada punto); tabla de mediciones paginada; a la derecha la pendiente con "Cambiar fecha" y "Cancelar medición", y "Asignar otra medición" (formulario corto; si hay pendiente, confirma el reemplazo con `ModalReemplazarMedicion`, A3.1c).
- **Datos de la cuenta (A3.1d):** `components/empresas/CamposEmpresa.vue` (el mismo de A3.2), "Estado de la cuenta" (estado, registro, último acceso, mediciones y colaboradores en solo lectura) y "Acciones": Ver como, Asignar medición, Desactivar cuenta (`ModalDesactivarEmpresa`, A3.1f) o Reactivar. `?pestana=cuenta` abre esta pestaña.
- **Consultores:** "Próximamente" (rol aplazado).
- La pendiente muestra su último aviso y "Enviar recordatorio" (en curso) o "Reenviar aviso" (`ModalCorreoAviso`).
- "Ver resultado" abre A3.3 (HU-038). La gráfica tiene "Ver:" para el puntaje total o una categoría (`graficas/SelectorEvolucion.vue`).
- Si la empresa pidió una medición nueva (HU-083), la tarjeta de la pendiente lo avisa.

### A3.2 · Registrar empresa (`empresas/Registrar`)

Ruta: `GET /empresas/registrar`; envía `POST /empresas`. Prop `opciones: OpcionesEmpresa` (sectores con sus actividades CIIU y diagnósticos publicados, los 18 países y los rangos de empleados).

- Campos de la empresa (con actividad CIIU y país → departamento → ciudad, DEC-015 y DEC-016) y del usuario principal, sin contraseña: le llega el enlace para crearla (RN-006).
- "Asignar la primera medición al registrar" (opcional): diagnóstico publicado del sector y fecha límite.
- A la derecha, "Qué pasa al registrar".

### A3.1b · Asignar medición (`mediciones/Asignar`)

Ruta: `GET /mediciones/asignar` (con `?empresa=` llega elegida); envía `POST /mediciones` con `empresa_id`, `diagnostico_id`, `fecha_limite`, `mensaje`, `aviso` y `reemplazar`.

| Prop | Tipo | Nota |
|---|---|---|
| `empresas` | `EmpresaAsignable[]` | Solo activas; con su pendiente (id, estado, vence, diagnóstico, si tiene respuestas) y el número que tendrá la nueva |
| `diagnosticos` | `Record<sectorId, { publicados, borradores }>` | Los borradores se ven pero no se eligen (CA-004) |
| `empresaInicial` | `number \| null` | |

- Buscador de empresa (`components/empresas/BuscadorEmpresa.vue`) con su sector y su pendiente.
- "Así lo verá la empresa": la tarjeta de su inicio con el mensaje y la fecha (CA-005).
- Con pendiente: aviso, y al asignar `ModalReemplazarMedicion` (A3.1c). Si está vencida, también "dale una nueva fecha" (HU-009).
- "Ver y editar el correo" abre A3.1e en un modal; "Guardar correo" lo deja para esta medición (`correo_asunto`, `correo_cuerpo`, `guardar_plantilla` en el POST). Prop `plantilla: { asunto, cuerpo }` y `empresas[].principal` para "Para:" y `{nombre_usuario}`.

### A3.1e · Correo de aviso de la medición (`components/empresas/EditorCorreo.vue`)

- Para (bloqueado), Asunto y Mensaje con las variables `{nombre_usuario}`, `{empresa}`, `{diagnostico}`, `{numero_medicion}`, `{mensaje_admin}` y `{fecha_limite}`: los botones las insertan donde está el cursor.
- "Así le llega a la empresa": vista previa con los datos reales; `lib/correo.ts` rellena igual que `App\Support\CorreoAviso` (con pruebas en `correo.test.ts`).
- "Guardar como plantilla para las próximas mediciones" y "Enviarme un correo de prueba" (solo a quien lo pide).
- Se usa en A3.1b (modal "Correo de aviso de la medición", con Volver y Guardar correo) y en `ModalCorreoAviso` (Recordatorio / Reenviar aviso desde A1 y la ficha, con Cancelar y Enviar). Este pide los datos a `GET /mediciones/{id}/correo`.
- Se diferencia del wireframe en que es un modal y no una página aparte: así A3.1b no pierde lo que ya se llenó.

### A2.1 · Editor del diagnóstico (`diagnosticos/Editor`)

Ruta: `GET /diagnosticos/{id}/editar`. Props en el tipo `DatosEditor` (`types/editor.ts`).

- Arriba: nombre, sector, "Borrador vN · Versiones" (abre `ModalVersiones`), el estado (enlace a A2.4), "Vista previa y estado" y "Publicar versión N".
- Izquierda: categorías con ✓ o ⚠N, importancia y número de preguntas; total de importancia, "Editar importancia" (A2.1c) y "+ Agregar categoría" (`ModalAgregarCategoria`).
- Derecha: las preguntas de la categoría (`components/editor/TarjetaPregunta.vue`). Se ordenan arrastrando ⋮⋮ o con flecha arriba/abajo sobre el asa; el orden se guarda al soltar.
- `ModalPregunta` (A2.1b agregar, A2.1e editar): tipo, opciones con puntaje (subir, bajar, quitar), suma máxima en vivo, indicación, "Qué debe tener en cuenta la IA" por opción con "Así lo verá la IA", criterio de la abierta y "obligatoria". `ModalEliminarPregunta` (A2.1f).
- `?categoria=` y `?pregunta=` (desde "Corregir") abren esa categoría y resaltan la pregunta.

### A2.1c · Editar importancia (`diagnosticos/Importancia`)

Ruta: `GET /diagnosticos/{id}/importancia`; envía `PUT` con `importancias{parte_id: valor}`. Total en vivo, "Guardar" solo con 100% exacto, cambios resaltados ("12 → 14") y "Repartir en partes iguales" (la última se lleva el redondeo, como el servidor).

### A2.4 · Vista previa y estado (`diagnosticos/VistaPrevia`)

Ruta: `GET /diagnosticos/{id}/vista-previa` (`?version=` para una publicada, en solo lectura). Izquierda: categorías completas, los 5 requisitos y las categorías por corregir con "Corregir". Derecha: el cuestionario como lo ve la empresa, una categoría a la vez; las respuestas no se guardan.

- La pestaña "Resultado de ejemplo" abre A2.6 (HU-030).

### E1 · Inicio de la empresa (`inicio/Empresa`)

Ruta: `GET /mi-inicio`. Props en el tipo `InicioEmpresa` (`types/responder.ts`).

- La medición pendiente según su estado: primera vez, no iniciada (mensaje del equipo, fecha límite y días que quedan), en curso (avance y "Continuar diagnóstico"), vencida ("Pedir una nueva fecha" y el correo de soporte; "Nueva medición solicitada" si ya la pidió) o enviada. Sin pendiente: "No tienes diagnósticos pendientes".
- Primera vez: "Cómo funciona" en 3 pasos. Con resultados: el último (anillo `graficas/AnilloPuntaje.vue`, nivel, variación y `graficas/MiniEvolucion.vue`) y "Tus 3 áreas a trabajar" o "Mis mediciones".
- "Ver resultado" abre E6, "Ver informe PDF" descarga el PDF y "Ver historial completo" abre E8.

### E2–E4 · Responder, revisar y enviado (`responder/Responder`, `responder/Revisar`, `responder/Enviado`)

Usan `layouts/ResponderLayout.vue` (sin menú lateral) y `components/responder/BarraResponder.vue` (avance, "✓ Guardado 10:42" y "Guardar y salir").

- **Opción excluyente ("Ninguna"):** en E2 y A2.4, marcarla desmarca las demás y marcar otra la desmarca. En A2.1b se elige en "Opción que excluye a las demás"; si una opción se llama "Ninguna", "Ninguno" o "Nada", queda elegida sola.
- **E2:** categorías a la izquierda (✓ completa, ● a medias, número si no empezó); preguntas con "Cómo responder", "Obligatoria" y el tipo. Cada respuesta se guarda al momento con `fetch` (PUT JSON con el token de la cookie `XSRF-TOKEN`); las escritas, al dejar de escribir. Abajo, anterior y siguiente; en la última, "Revisar antes de enviar". La URL guarda la categoría (`?categoria=`).
- **E3:** con faltantes, la lista con "Completar" y el aviso; completo, "N/N categorías completas" y "Todo listo". "Enviar diagnóstico" pide confirmar.
- **E4:** los dos pasos con su estado real ("2 de 10" categorías). Se recarga cada 5 s; cuando se publica el resultado, el servidor la lleva a E6. Si una categoría falló tras 3 intentos: "No pudimos terminar el análisis de algunas categorías" y "Reintentar esas categorías".
- La opción "Otro" (`con_texto`): al elegirla aparece "¿Cuál?" (máx. 255), que se guarda al dejar de escribir; si se cambia de opción, el texto se borra.

### A4 · Configuración de la IA (`configuracion-ia/Index`)

Ruta: `GET /configuracion-ia`. Props en `ConfiguracionIa` (`types/configuracionIa.ts`).

- Una sola página con los tres alcances de la etapa "Analizar categoría": **Todas las empresas** (instrucciones generales, A4), **Solo un sector** (A4.5) y **Solo una empresa** (A4.6, con buscador). Cambiar de alcance con cambios sin guardar pide confirmación.
- Cada elemento (contexto, tarea, detalles, ejemplos) en `components/ia/ElementoPrompt.vue`: en lo general, el texto con "Restaurar texto original" y qué datos automáticos usa; en un ajuste, "Usar general / Usar sector", "Agregar" o "Reemplazar" con la vista de cómo lo leerá la IA. El formato de la respuesta se ve fijo, con "Ver detalle técnico".
- Panel derecho: qué instrucciones se usan (y de qué capa sale cada elemento), "Probar con un ejemplo" (`ModalProbarPrompt`, A4.7), "Ver instrucciones completas" (`ModalPromptCompleto`, A4.4: Lectura con el origen marcado por color y Texto exacto con "Copiar"), cuándo aplica, última edición y "Quitar ajuste" (`ModalQuitarAjuste`).
- Sin `ia.editar` todo se ve en solo lectura y no aparecen Guardar, Probar ni Quitar.
- Las llamadas JSON usan `enviarJson` de `lib/ia.ts`.

### A4.9 · Cómo se arma el prompt final (`configuracion-ia/ComoSeArma`)

Las cinco capas con lo que aporta cada una, las indicaciones por pregunta y dónde pedir un FODA o PESTEL. Solo lectura.

### A5.4 · "Ver como" (`components/base/FranjaVerComo.vue`)

- Con el dato compartido `verComo`, `PanelLayout` y `ResponderLayout` muestran arriba la franja "Estás viendo como … · Solo lectura" con "Salir de Ver como" (`POST /ver-como/salir`). En el menú lateral, "Cerrar sesión" pasa a ser "Salir de Ver como".
- La franja pone la clase `ver-como` en `<html>`: `resources/css/app.css` descuenta su alto (`--alto-ver-como`) en las páginas de alto fijo y en el menú, para que no haya scroll de página.
- E1 apaga "Iniciar diagnóstico" y "Pedir una nueva fecha" con "No disponible en Ver como". En el resto, el servidor rechaza cualquier cambio y avisa.

### E8 / A3.4 · Historial (`historial/Historial`)

Rutas: `GET /historial` (la empresa) y `GET /empresas/{id}/historial` (el Administrador, `interno`). Props en `Historial` (`types/historial.ts`).

- La medición pendiente: la empresa la continúa si está en curso o ve el avance si se envió; no se inicia aquí (HU-067 CA-005). El Administrador puede cambiar la fecha o cancelarla (los mismos modales de A3.1) y "Asignar medición".
- La gráfica de evolución (`graficas/EvolucionPuntaje.vue`) desde la 2.ª medición terminada.
- La tabla de terminadas, paginada con las filas que caben, con "Ver resultado", "Ver mis respuestas" y "Ver informe PDF".
- Se llega desde "Ver historial completo" en E1 y en A3.1, y desde "Mi historial" en el menú.

### E6 / A3.3 · Resultado (`resultados/Resultado`)

Ruta: `GET /resultados/{medicion}`. Props: `medicion` (`CabeceraResultado`), `resultado` (`Resultado`, en `types/resultados.ts`) e `interno` (lo abre el equipo de NuevasTIC: la flecha vuelve a la ficha A3.1 y no a Inicio).

- Pestañas, para caber sin scroll: **Resumen** (anillo, nivel, frase, variación vs. la medición anterior, escala de niveles, las 3 recomendaciones de las categorías más débiles y el radar actual vs. anterior), **Categorías** (tabla con importancia, puntaje, nivel y "vs. anterior"; al elegir una fila, el detalle con cada pregunta en `components/resultados/PreguntaResultado.vue`, la observación y la recomendación de la categoría) y **Recomendaciones** (todas, de la más débil a la más fuerte).
- "Ver mis respuestas" (E9) y "Ver informe PDF" (`Boton` con `archivo`: enlace normal, sin Inertia, para descargar).

- El método con el que la IA analizó cada categoría se ve con `components/resultados/EtiquetaMetodo.vue` (en las prioridades, el detalle y las recomendaciones; el motivo, al pasar el cursor).
- **A2.6 · Resultado de ejemplo (HU-030):** la misma página con la prop `ejemplo` (`caso`, `etiqueta`, `volver`, `pdf`): "Así se vería el resultado", el selector Mejor caso / Caso medio / Peor caso junto a las pestañas, "Volver a la vista previa" y "Ver formato del PDF" (otra pestaña). Se abre desde "Resultado de ejemplo" en A2.4.

### E9 / A3.5 · Mis respuestas (`resultados/Respuestas`)

Ruta: `GET /resultados/{medicion}/respuestas` (`?categoria=c3` abre esa). Categorías con su puntaje a la izquierda y, a la derecha, las preguntas con la respuesta, el puntaje ("· IA" en las abiertas) y la observación de la IA. Solo lectura.

### A5 · Usuarios y roles: pestaña Usuarios (`usuarios/Index`)

Ruta: `GET /usuarios` (ya existe, `UsuariosController`; permiso `usuarios.ver`). Admite `?rol=Empresa` para entrar ya filtrado.

| Prop | Tipo | Nota |
|---|---|---|
| `cuentas` | `Cuenta[]` | **Todas** las cuentas. La búsqueda, los filtros y las páginas de 12 se hacen en la pantalla. Cada una trae `rol`, `empresa`, `es_principal`, `colaboradores_activos`, `medicion_pendiente` (texto), `estado` (`activa`, `desactivada`, `invitacion`, `eliminada`), `eliminada_en`, `se_borra_el`, `invitacion_enviada_en` y `es_tuya` |
| `roles` | `{ id, nombre, descripcion, activo, del_sistema, aviso }[]` | Para el filtro, "Invitar usuario" y "Cambiar rol" |

Tipos exactos en `resources/js/types/usuarios.ts`.

Acciones por fila:
- **La propia cuenta:** "Tu cuenta · Mi perfil".
- **Invitación pendiente:** "Reenviar invitación".
- **"Ver como":** no aparece en cuentas de Administrador.
- **"Cambiar rol":** no aparece en colaboradores.
- **"Desactivar" o "Reactivar".**

No existe "restablecer contraseña" (RN-006).

### A5.1 · Usuarios y roles: pestaña Roles (`usuarios/Roles`)

Ruta: `GET /usuarios/roles?rol={id}` (ya existe, `RolesController`; permiso `usuarios.ver`).

| Prop | Tipo | Nota |
|---|---|---|
| `roles` | `Rol[]` | Cada uno con `descripcion`, `resumen` (texto corto de la lista), `activo`, `del_sistema`, `permisos` (nombres técnicos), `notas_permisos` ("solo su empresa"), `cuentas_total`, `cuentas` (las primeras, con `detalle` y `es_tuya`) y `aviso` (texto si está inactivo) |
| `bloques` | `BloquePermisos[]` | Los 15 permisos de A5.2 en 6 bloques. Salen de `RolesYPermisosSeeder::PERMISOS` |
| `cuentas` | `Cuenta[]` | Todas, para "+ Asignar a una cuenta", "Cambiar rol…" y "Crear rol" |
| `rolId` | `number \| null` | Rol abierto al entrar; sin él, el primero |

Modales (todos en `components/usuarios/`):

| Pantalla | Componente | Envía |
|---|---|---|
| Invitar usuario | `ModalInvitarUsuario` | `POST /usuarios/invitar`: `name`, `email`, `rol_id`, `mensaje` |
| A5.3b | `ModalDesactivarEliminar` | Paso 1: elegir. Desactivar: `POST /usuarios/{id}/desactivar`. Eliminar: `DELETE /usuarios/{id}` con `confirmacion` (el correo exacto; no se puede pegar). Las eliminadas solo se ven con el filtro «Eliminadas», con "Se borra el…" y "Recuperar" (`POST /usuarios/{id}/recuperar`) |
| A5.5 (desde una cuenta) | `ModalCambiarRol` | `PUT /usuarios/{id}/rol`: `rol_id`, `avisar` |
| A5.5 (desde un rol) | `ModalAsignarRol` | `POST /roles/{id}/asignar`: `cuenta_id`, `avisar` |
| A5.2 | `ModalCrearRol` | `POST /roles`: `nombre`, `descripcion`, `activo`, `permisos[]`, `cuentas[]` |
| A5.1c | `PanelRol` | `PUT /roles/{id}`: `nombre`, `descripcion`, `activo`, `permisos[]` |
| A5.1d | `ModalEliminarRol` | `DELETE /roles/{id}` |

Sin modal: "Reactivar" (`POST /usuarios/{id}/reactivar`), "Reenviar invitación" (`POST /usuarios/{id}/invitacion`) y "Ver como" (`POST /usuarios/{id}/ver-como`, A5.4).

Todo está conectado al backend. `medicion_pendiente` dice la medición pendiente de la cuenta principal de una empresa ("medición 4 no iniciada (vence el 20 de octubre)").

### A6 / E11 · Mi perfil (`perfil/MiPerfil`)

Ruta: `GET /mi-perfil` (ya existe, `PerfilController`). Una sola pantalla para todas las cuentas: con `empresa = null` se ve como A6 (Administrador y roles internos); con empresa, como E11.

| Prop | Tipo | Nota |
|---|---|---|
| `usuario` | objeto | `name`, `email`, `cargo`, `telefono`, `pais`, `departamento`, `ciudad`, `zona_horaria`, `idioma`, `avisos` (clave → sí/no), `foto_url`, `ultimo_acceso_en`, `creado_en`, `contrasena_actualizada_en` |
| `rol` | `string \| null` | Se muestra bloqueado en A6. |
| `empresa` | objeto o `null` | `nombre`, `sector`, `actividad_economica_id` (subsector), `descripcion`, `pais`, `departamento`, `ciudad`, `sitio_web`, `numero_empleados` |
| `editaEmpresa` | `boolean` | Solo la cuenta principal (rol Empresa) cambia los datos de la empresa (RN-025). |
| `opciones` | objeto | `actividades` (las CIIU del sector de la empresa: `{ id, codigo, nombre }[]`, vacío en A6), `paises` (como en L2), `zonas`, `idiomas`, `empleados` y `avisos` (clave → texto), de `app/Support/OpcionesPerfil.php` |

Envía:

- `PATCH /mi-perfil`: los datos de arriba; `empresa{…}` solo si `editaEmpresa`. La actividad debe ser del sector de la empresa (obligatoria si el sector tiene actividades) y la descripción es obligatoria, de máximo 300 caracteres (DEC-015).
- `PUT /mi-perfil/contrasena`: `current_password`, `password`, `password_confirmation`. En A6 va en línea; en E11, en un modal.
- `POST /mi-perfil/foto`: `foto` (imagen de hasta 2 MB). La cuenta principal de una empresa cambia el **logo** de la empresa; las demás cuentas, su **foto**. Las imágenes se sirven por `/imagenes/{usuario|empresa}/{id}`, sin publicar la carpeta de archivos.

### E12 · Colaboradores (`colaboradores/Index`)

Ruta: `GET /colaboradores` (ya existe, `ColaboradoresController`; middleware `role:Empresa`). Solo entra la cuenta principal de la empresa (rol Empresa, HU-076 CA-001); un colaborador recibe 403. Vista previa: `/prueba-tecnica/vistas/e12-colaboradores` y `e12-sin-colaboradores`.

| Prop | Tipo | Nota |
|---|---|---|
| `empresa` | `string` | Nombre de la empresa, para el texto de arriba |
| `colaboradores` | `Colaborador[]` | Las cuentas de la empresa: la principal primero, luego los colaboradores por nombre. Cada una con `id`, `nombre`, `correo`, `cargo`, `es_principal` y `activo` |

Tipos exactos en `resources/js/types/colaboradores.ts`. Si solo llega la cuenta principal, la pantalla muestra la lista vacía con el botón para crear (HU-076 CA-004).

Envía (todo con `colaborador` de la misma empresa; si no, 404):

- `POST /colaboradores` (E12.1): `name`, `cargo` (obligatorio; lista de sugerencias en `lib/cargos.ts` o escrito), `email` y `password`. Correo único (RN-002) y contraseña fuerte (RN-001); no se pide confirmación porque la empresa la escribe y la comparte. Crea la cuenta con `empresa_id` de la empresa y rol **Colaborador**, activa, sin enviar correo. Redirige con `back()`; la pantalla abre E12.2 con lo que se escribió. **El servidor no devuelve la contraseña.**
- `PUT /colaboradores/{id}` (E12.3, "Editar"): `name`, `cargo`, `email` (único) y `password` (vacía si no se cambia; si va, RN-001). Si cambia el correo o la contraseña, **cierra las sesiones abiertas** del colaborador. Con contraseña nueva, la pantalla abre E12.2 para compartirla.
- `POST /colaboradores/{id}/desactivar` y `/reactivar` (HU-079): cambian `activo`. Nunca sobre la cuenta principal (403).
- `DELETE /colaboradores/{id}` (DEC-017): elimina el colaborador (sale de la lista, el Administrador lo recupera en 90 días). Sin campos: la doble confirmación es con dos botones en la pantalla, porque el correo y el nombre se pueden cambiar.

Modales (en `components/colaboradores/`): `ModalAgregarColaborador` (E12.1), `ModalDatosDeAcceso` (E12.2), `ModalEditarColaborador` (E12.3: datos y contraseña) y `ModalDesactivarEliminarColaborador` (elegir → desactivar, o eliminar → "Eliminar" → "Sí, eliminar a…").

## Pantallas sin wireframe en los PDF recibidos

Se construyeron a partir de las historias y con el mismo estilo. Hay que compararlas con el prototipo cuando esté a mano.

- **A2.2c** · Desactivar sector (HU-015).
- **Archivar diagnóstico** (HU-022): no tiene diseño (PA-005).
- **Eliminar una categoría nunca respondida** (HU-019): el wireframe A2.3 solo muestra archivar.
