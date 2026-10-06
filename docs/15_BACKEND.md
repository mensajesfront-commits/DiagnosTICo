# Backend

**Estado:** EN CURSO. La lista de rutas es una PROPUESTA que REQUIERE VALIDACIÓN (T-043).

La lista sale de lo que necesitan las pantallas ya construidas. **Luis decide los nombres finales.** Si cambia una URL, se actualiza en `resources/js/lib/rutas.ts`, o se reemplaza ese archivo por las funciones de Wayfinder (`@/routes/...`) cuando las rutas existan.

Todas las rutas del Administrador van con `auth` y con el permiso indicado (matriz en `17_SEGURIDAD.md`).

## Acceso (Fortify, ya existen)

| Método | URL | Pantalla | Nota |
|---|---|---|---|
| GET / POST | `/login` | L1 | |
| POST | `/logout` | Menú lateral | |
| GET / POST | `/register` | L2 | Falta agregar los campos de empresa (T-047). Ver `14_FRONTEND.md`. |
| GET / POST | `/forgot-password` | L3 | Ajustar el aviso para cumplir RN-005. |
| GET | `/reset-password/{token}` | L4 | |
| POST | `/reset-password` | L4 | |

## Diagnósticos y sectores (T-049, T-050)

| Método | URL | Permiso | Qué hace | Envía / devuelve |
|---|---|---|---|---|
| GET | `/diagnosticos` | `diagnosticos.ver` | A2·T: todos los diagnósticos | Props de `diagnosticos/Index` |
| GET | `/diagnosticos?sector={id}` | `diagnosticos.ver` | A2 / A2b: un sector | Props de `diagnosticos/Index` |
| GET | `/diagnosticos/crear?sector={id}` | `diagnosticos.editar` | A2.5 | Props de `diagnosticos/Crear` |
| POST | `/diagnosticos` | `diagnosticos.editar` | Crea el borrador v1 y redirige al editor | `nombre`, `sector_id`, `descripcion`, `punto_partida`, `categorias[]`, `copiar_de` |
| GET | `/diagnosticos/{id}/editar` | `diagnosticos.editar` | A2.1 (semana 4) | |
| GET | `/diagnosticos/{id}/vista-previa` | `diagnosticos.ver` | A2.4 (semana 4) | |
| POST | `/diagnosticos/{id}/duplicar` | `diagnosticos.editar` | A2.7: copia en borrador v1 y abre el editor | `nombre` (máx. 60), `sector_id` |
| POST | `/diagnosticos/{id}/archivar` | `diagnosticos.editar` | Archiva un diagnóstico publicado | — |
| DELETE | `/diagnosticos/{id}` | `diagnosticos.editar` | Elimina un borrador que nunca se publicó | — |
| DELETE | `/diagnosticos/{id}/borrador` | `diagnosticos.editar` | Elimina el borrador pendiente (vN) sin tocar la versión publicada | — |
| POST | `/sectores` | `diagnosticos.editar` | A2.2a: crear | `nombre` (máx. 40, único), `descripcion`, `activo` |
| PUT | `/sectores/{id}` | `diagnosticos.editar` | A2.2: editar | `nombre`, `descripcion` |
| POST | `/sectores/{id}/reasignar` | `diagnosticos.editar` | A2.2b | `sector_destino_id`, `avisar` |
| POST | `/sectores/{id}/desactivar` | `diagnosticos.editar` | A2.2c | — |
| POST | `/sectores/{id}/reactivar` | `diagnosticos.editar` | Reactivar desde A2.2 | — |
| DELETE | `/sectores/{id}` | `diagnosticos.editar` | A2.2e. Solo si no tiene empresas ni mediciones (RN-008). | — |

## Catálogo de categorías (T-049)

| Método | URL | Permiso | Qué hace | Envía |
|---|---|---|---|---|
| GET | `/categorias` | `diagnosticos.ver` | A2.3 | Props de `categorias/Index` |
| POST | `/categorias` | `diagnosticos.editar` | A2.3b: crear | `nombre` (máx. 40, único), `descripcion`, `diagnosticos[]` (borradores) |
| PUT | `/categorias/{id}` | `diagnosticos.editar` | Editar nombre o descripción | `nombre`, `descripcion` |
| DELETE | `/categorias/{id}` | `diagnosticos.editar` | A2.3c: eliminar (nunca respondida) | — |
| POST | `/categorias/{id}/archivar` | `diagnosticos.editar` | A2.3c: archivar (con respuestas) | — |

## Otras secciones del menú (rutas previstas)

| URL | Pantalla | Semana |
|---|---|---|
| `/empresas`, `/empresas?sector={id}`, `/empresas/{id}` | A3, A3.1 | 4–5 |
| `/configuracion-ia` | A4 | 5 |
| `/usuarios` | A5 | 6 |
| `/historial` | E8 | 6 |
| `/colaboradores` | E12 | 6 |

## Respuestas después de una acción

Después de crear, editar o eliminar, el controlador redirige (`back()`, o al editor cuando corresponda) y deja un mensaje con el mismo mecanismo que ya usa el kit:

```php
Inertia::flash('toast', ['type' => 'success', 'message' => 'Sector creado.']);
```

`type` puede ser `success`, `info`, `warning` o `error`. El frontend lo muestra como notificación (`resources/js/lib/flashToast.ts`).

Los errores de validación llegan con el nombre del campo (`nombre`, `sector_id`…) y se muestran bajo el campo.
