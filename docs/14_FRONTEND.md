# Frontend

**Estado:** EN CURSO (T-088). Se completa con cada pantalla nueva.

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
│   └── rutas.ts       URLs previstas mientras no existen las rutas reales
├── pages/             Una página por pantalla del wireframe
└── types/             Tipos de los datos que llegan del backend
```

### Para agregar una pantalla

1. Crea `pages/<modulo>/<Pantalla>.vue` con un comentario arriba: código del wireframe, historia y qué envía.
2. Define sus props con tipos en `types/` y anótalas en este documento.
3. Crea `resources/datos-ejemplo/<vista>.json` con los datos del wireframe y revísala en `/prueba-tecnica/vistas/<vista>`.
4. Usa los componentes de `components/base/` antes de crear uno nuevo.

## Componentes base

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

### A2 / A2·T / A2b · Diagnósticos (`diagnosticos/Index`)

Ruta prevista: `GET /diagnosticos` (Todos) y `GET /diagnosticos?sector={id}`.

| Prop | Tipo | Nota |
|---|---|---|
| `sectores` | `Sector[]` | Todos los sectores, activos e inactivos, con su número de diagnósticos activos. |
| `sector` | `Sector \| null` | El sector elegido; `null` en "Todos". |
| `resumen` | `ResumenDiagnosticos` | Del sector o general. En "Todos" incluye `sectores_activos`. Con sector, también `mediciones_por_estado` (todas las mediciones) para el modal de reasignar. |
| `diagnosticos` | `FilaDiagnostico[]` | Del sector, o de todos. Sin archivados. |
| `empresas` | `EmpresaDelSector[]` | Solo con sector. Las primeras N; el total sale de `resumen.empresas`. |

### A2.3 · Catálogo de categorías (`categorias/Index`)

Ruta prevista: `GET /categorias`.

| Prop | Tipo | Nota |
|---|---|---|
| `categorias` | `Categoria[]` | Activas y archivadas. Cada una trae `diagnosticos` (activos donde se usa), `preguntas`, `versiones_publicadas`, `borradores` (nombres de los borradores que la usan) y `tiene_respuestas`, que decide entre "Archivar" y "Eliminar" (RN-009). |
| `totalDiagnosticos` | `number` | Diagnósticos activos en total, para "13 de 13". |
| `borradores` | `DiagnosticoBorrador[]` | Diagnósticos en borrador (con `version`), para agregarles una categoría nueva (HU-018 CA-004). |

La categoría se edita en el panel de la derecha (`PanelEditarCategoria`) y envía `nombre` y `descripcion`. Crear (A2.3b) y archivar (A2.3c) abren un modal. Las archivadas tienen "Restaurar".

### A2.5 · Crear diagnóstico (`diagnosticos/Crear`)

Ruta prevista: `GET /diagnosticos/crear?sector={id}`.

| Prop | Tipo | Nota |
|---|---|---|
| `sectores` | `Sector[]` | Solo activos. |
| `sectorId` | `number \| null` | Sector preseleccionado si se viene desde A2. |
| `categorias` | `{ id, nombre }[]` | Categorías activas del catálogo. |
| `publicados` | `DiagnosticoPublicado[]` | Última versión publicada de cada diagnóstico, de todos los sectores, con los nombres de sus categorías. |

Envía a `POST /diagnosticos`: `nombre` (máx. 60), `sector_id`, `descripcion` (opcional), `punto_partida` (`blanco` | `copia`), `categorias[]` (en blanco) y `copiar_de` (en copia). El backend crea el borrador v1; mientras no exista el editor (A2.1, semana 4) vuelve a A2 con un aviso.

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

Todo está conectado al backend. `medicion_pendiente` llega vacío hasta que exista la tabla de mediciones.

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
