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

| Prop | Tipo | Nota |
|---|---|---|
| `sectores` | `{ id, nombre }[]` | Solo sectores activos (RN-003), en orden alfabético. |
| `paises` | `string[]` | Por ahora `['Colombia']`. **[INFORMACIÓN PENDIENTE]** lista de países. |
| `passwordRules` | `string` | Del kit (atributo `passwordrules`). |

Envía a `POST /register`:

| Campo | Regla esperada |
|---|---|
| `empresa_nombre` | Obligatorio |
| `sector_id` | Obligatorio; debe ser un sector activo |
| `ciudad` | Obligatorio |
| `pais` | Obligatorio |
| `name` | Obligatorio |
| `cargo` | Opcional |
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

Envía a `POST /diagnosticos`: `nombre` (máx. 60), `sector_id`, `descripcion` (opcional), `punto_partida` (`blanco` | `copia`), `categorias[]` (en blanco) y `copiar_de` (en copia). El backend crea el borrador v1 y redirige al editor (A2.1).

## Pantallas sin wireframe en los PDF recibidos

Se construyeron a partir de las historias y con el mismo estilo. Hay que compararlas con el prototipo cuando esté a mano.

- **A2.2c** · Desactivar sector (HU-015).
- **Archivar diagnóstico** (HU-022): no tiene diseño (PA-005).
- **Eliminar una categoría nunca respondida** (HU-019): el wireframe A2.3 solo muestra archivar.
