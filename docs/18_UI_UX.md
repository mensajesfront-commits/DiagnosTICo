# UI/UX

**Estado:** EN CURSO (T-066, T-089). Se agregan las pantallas de cada semana.

**[INFORMACIÓN PENDIENTE]** Falta el enlace al prototipo navegable. Los wireframes recibidos son 5 PDF parciales:

- L1–L4, el inicio y los diagnósticos del Administrador, y la experiencia de la empresa (los 3 primeros);
- `selection_3-1` y `selection_3-2` (6 de octubre): catálogo de categorías, Empresas, asignar medición, correo de aviso, registrar empresa, resultado, historial y respuestas vistos por el Administrador, Configuración IA, Usuarios y roles, «Ver como» y Mi perfil.

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
| L2 | Registra tu empresa | `pages/auth/Register.vue` | Hecha. El backend de los campos de empresa va en T-047. |
| L3 | ¿Olvidaste tu contraseña? | `pages/auth/ForgotPassword.vue` | Hecha |
| L4 | Crea una contraseña nueva | `pages/auth/ResetPassword.vue` | Hecha |
| A2 | Diagnósticos de un sector | `pages/diagnosticos/Index.vue` | Hecha (vista previa) |
| A2·T | Todos los diagnósticos | `pages/diagnosticos/Index.vue` | Hecha (vista previa) |
| A2b | Sector sin diagnósticos | `pages/diagnosticos/Index.vue` | Hecha (vista previa) |
| A2.2 / A2.2a | Editar / crear sector | `components/diagnosticos/modales/ModalSector.vue` | Hecha |
| A2.2b | Reasignar empresas | `…/ModalReasignarSector.vue` | Hecha |
| A2.2c | Desactivar / reactivar sector | `…/ModalEstadoSector.vue` | Hecha. Sin wireframe, sale de HU-015. |
| A2.2d / A2.2e | No se puede eliminar / eliminar sector | `…/ModalEliminarSector.vue` | Hecha |
| A2.7 | Duplicar diagnóstico | `…/ModalDuplicarDiagnostico.vue` | Hecha |
| — | Archivar diagnóstico | `…/ModalArchivarDiagnostico.vue` | Hecha. Sin diseño (PA-005). |
| — | Eliminar diagnóstico o borrador | `…/ModalEliminarDiagnostico.vue` | Hecha |
| A2.3 | Catálogo de categorías, con el panel para editar | `pages/categorias/Index.vue`, `components/categorias/PanelEditarCategoria.vue` | Hecha (vista previa) |
| A2.3b | Crear / editar categoría | `components/categorias/ModalCategoria.vue` | Hecha |
| A2.3c | Archivar o eliminar categoría | `components/categorias/ModalRetirarCategoria.vue` | Hecha. Eliminar no tiene wireframe, sale de HU-019. |
| A2.5 | Crear diagnóstico | `pages/diagnosticos/Crear.vue` | Hecha (vista previa) |

## Diferencias con el wireframe

| Pantalla | Diferencia | Motivo |
|---|---|---|
| L2, L4 | La lista de requisitos de la contraseña tiene 5 puntos (agrega "Al menos un carácter especial"); el wireframe muestra 4. | RN-001 lo exige. **[INCONSISTENCIA DETECTADA]**, pendiente de decidir. |
| L1–L4 | El panel oscuro ocupa el 42 % de la pantalla; en el wireframe es más angosto. | Lo pidió el equipo (6 de octubre). |
| L1 | No tiene la "Nota del prototipo" con atajos de entrada. | El wireframe aclara que no es parte del diseño. |
| L2 | El país solo ofrece "Colombia". | **[INFORMACIÓN PENDIENTE]** Falta la lista de países. |
| L2 | Los enlaces de términos y de política apuntan a `#`. | **[INFORMACIÓN PENDIENTE]** Faltan las URL reales. |
| A2.5 | En blanco, se eligen las categorías con casillas y se muestra cuánto vale cada una al empezar. | HU-020 pide elegir las categorías; el wireframe solo muestra la opción de copiar. |
| A2.3 | Si la categoría nunca se respondió, la acción es «Eliminar» en vez de «Archivar». | HU-019 y RN-009. El wireframe solo muestra «Archivar». |
| A2.3 | Al abrir, el panel muestra la primera categoría; con «Cancelar» queda vacío con una indicación. | El wireframe solo muestra el panel con una categoría elegida. |
