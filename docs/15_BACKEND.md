# Backend

**Estado:** EN CURSO. La lista de rutas es una PROPUESTA que REQUIERE VALIDACIÓN (T-043).

La lista sale de lo que necesitan las pantallas ya construidas. **Luis decide los nombres finales.** Si cambia una URL, se actualiza en `resources/js/lib/rutas.ts`, o se reemplaza ese archivo por las funciones de Wayfinder (`@/routes/...`) cuando las rutas existan.

Todas las rutas del Administrador van con `auth` y con el permiso indicado (matriz en `17_SEGURIDAD.md`).

## Acceso (Fortify, ya existen)

| Método | URL | Pantalla | Nota |
|---|---|---|---|
| GET / POST | `/login` | L1 | |
| POST | `/logout` | Menú lateral | |
| GET / POST | `/register` | L2 | Crea la empresa y su usuario con rol Empresa (`CreateNewUser`). |
| GET / POST | `/forgot-password` | L3 | Ajustar el aviso para cumplir RN-005. |
| GET | `/reset-password/{token}` | L4 | |
| GET | `/` | — | Redirige al inicio de sesión, o al inicio de la cuenta si ya entró. |
| POST | `/reset-password` | L4 | |

## Lo que ya existe en el backend (versión mínima, 6 de octubre)

Se hizo desde el frontend para poder probar el registro y el menú de la empresa. **Luis lo revisa en el pull request y puede cambiarlo.**

| Pieza | Archivo | Nota |
|---|---|---|
| Tabla `sectores` | `database/migrations/2026_10_06_000001_create_sectores_table.php` | `nombre` (40, único), `descripcion`, `activo` |
| Tabla `empresas` y columnas nuevas en `users` | `database/migrations/2026_10_06_000002_create_empresas_table.php` | `empresas`: `nombre`, `sector_id`, `ciudad`, `pais`, `activa`. `users`: `empresa_id`, `cargo`, `telefono`, `activo` |
| Modelos | `app/Models/Sector.php`, `app/Models/Empresa.php`, `User::empresa()` | `Sector::activos()` para RN-003 |
| Roles y permisos | `database/seeders/RolesYPermisosSeeder.php` | La matriz de `17_SEGURIDAD.md` (3 roles, 15 permisos) |
| Sectores de ejemplo | `database/seeders/SectoresSeeder.php` | Los 7 del wireframe. **[INFORMACIÓN PENDIENTE]** lista real |
| Administrador inicial | `database/seeders/DatabaseSeeder.php`, `config/diagnostico.php` | `ADMIN_EMAIL` y `ADMIN_PASSWORD` en `.env` |
| Registro | `app/Actions/Fortify/CreateNewUser.php` | Valida los campos de L2 y crea empresa + usuario en una transacción |
| Pruebas | `tests/Feature/Auth/RegistrationTest.php` | Solo sectores activos, registro completo, sector inactivo y términos |

Seguridad del acceso completa (7 de octubre): cuentas y empresas desactivadas, contraseña fuerte, avisos que no revelan correos, enlace sin vencimiento, límite de intentos, mensajes y correo en español y constancia de los términos. Detalle en `17_SEGURIDAD.md`.

| Pieza | Archivo |
|---|---|
| Inicio de sesión que revisa la cuenta y la empresa | `FortifyServiceProvider::configureLogin`, `User::puedeEntrar()` |
| Cierre de sesión si desactivan la cuenta | `app/Http/Middleware/CerrarSesionCuentaInactiva.php` |
| Límite de intentos en registro y recuperación | `app/Http/Middleware/LimitarIntentosAcceso.php` |
| Mismo aviso en "¿Olvidaste tu contraseña?" | `app/Http/Responses/AvisoRecuperacionResponse.php` |
| Contraseña fuerte | `AppServiceProvider` + `app/Rules/TieneMayuscula.php` |
| Correo de recuperación | `FortifyServiceProvider::configureResetEmail` |
| Textos en español | `lang/es/*.php`, `lang/es.json` |
| Pruebas | `tests/Feature/Auth/SeguridadAccesoTest.php` |

Queda fuera del acceso (lo hace el backend de cada módulo): la redirección según el rol (T-048), que depende de las pantallas A1 y E1.

## Mi perfil (ya existe)

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/mi-perfil` | `profile.edit` | A6 / E11 |
| PATCH | `/mi-perfil` | `profile.update` | Guarda datos personales, avisos y, para la cuenta principal, los datos de la empresa (el sector no) |
| PUT | `/mi-perfil/contrasena` | `user-password.update` | Cambia la contraseña con la actual; guarda `contrasena_actualizada_en` |
| POST | `/mi-perfil/foto` | `perfil.foto` | Foto de la cuenta o logo de la empresa |
| GET | `/imagenes/{tipo}/{id}` | `perfil.imagen` | Sirve la foto o el logo a la misma cuenta, su empresa o quien tenga `empresas.ver` / `usuarios.ver` |

Se quitaron las páginas de ajustes del kit (`/settings/profile`, `/settings/security`) y la opción de eliminar la cuenta: las cuentas se desactivan, no se borran (RN-004). `/settings` redirige a `/mi-perfil`. El "último acceso" se guarda al iniciar sesión (`AppServiceProvider`).

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
| POST | `/categorias/{id}/archivar` | `diagnosticos.editar` | A2.3c: archivar (con respuestas). Se quita de los borradores; las versiones publicadas no cambian. | — |
| POST | `/categorias/{id}/restaurar` | `diagnosticos.editar` | Restaurar una archivada. No se agrega sola a los borradores. | — |

## Usuarios y roles (A5, ya existen)

| Método | URL | Permiso | Qué hace | Envía |
|---|---|---|---|---|
| GET | `/usuarios` | `usuarios.ver` | A5: lista de cuentas | Props de `usuarios/Index` |
| GET | `/usuarios/roles?rol={id}` | `usuarios.ver` | A5.1: roles | Props de `usuarios/Roles` |
| POST | `/usuarios/invitar` | `usuarios.gestionar` | Crea la cuenta interna sin contraseña y envía el enlace (RN-006) | `name`, `email` (único), `rol_id` (activo, interno), `mensaje` |
| POST | `/usuarios/{id}/invitacion` | `usuarios.gestionar` | Reenvía la invitación | — |
| POST | `/usuarios/{id}/desactivar` | `usuarios.gestionar` | A5.3b (RN-004). No sobre la propia cuenta. Si es la cuenta principal, la empresa queda sin acceso | — |
| POST | `/usuarios/{id}/reactivar` | `usuarios.gestionar` | Reactiva | — |
| DELETE | `/usuarios/{id}` | `usuarios.gestionar` | Elimina (DEC-017): marca `deleted_at`, cierra sesiones. Pide `confirmacion` = correo exacto; con la cuenta principal elimina la empresa y sus colaboradores; nunca la propia (403) ni el último Administrador (error en `confirmacion`). Deja en el log solo los id. | — |
| POST | `/usuarios/{id}/recuperar` | `usuarios.gestionar` | Recupera una eliminada (90 días). La principal vuelve con su empresa y sus colaboradores; un colaborador eliminado con su empresa da error en `recuperar`. | — |
| — | `php artisan cuentas:purgar` | Tarea diaria 03:10 | Borra para siempre lo eliminado hace más de 90 días (`config('diagnostico.eliminacion.dias')`). | — |
| PUT | `/usuarios/{id}/rol` | `usuarios.gestionar` | A5.5: reemplaza el rol (RN-027). No sobre la propia cuenta; el rol debe estar activo | `rol_id`, `avisar` |
| POST | `/usuarios/{id}/ver-como` | `usuarios.gestionar` | A5.4: por ahora solo avisa que llega con la pantalla A5.4 (semana 6). No sobre Administradores (403) | — |
| POST | `/roles` | `usuarios.gestionar` | A5.2: crea el rol | `nombre` (único, máx. 40), `descripcion`, `activo`, `permisos[]`, `cuentas[]` |
| PUT | `/roles/{id}` | `usuarios.gestionar` | A5.1c: edita un rol creado (no los del sistema) | `nombre`, `descripcion`, `activo`, `permisos[]` |
| DELETE | `/roles/{id}` | `usuarios.gestionar` | A5.1d: solo roles creados y sin cuentas | — |
| POST | `/roles/{id}/asignar` | `usuarios.gestionar` | A5.5 desde el rol | `cuenta_id`, `avisar` |

Dónde está:

| Pieza | Archivo |
|---|---|
| Rutas | `routes/web.php` (grupos `permission:usuarios.ver` y `permission:usuarios.gestionar`) |
| Controladores | `app/Http/Controllers/UsuariosController.php`, `RolesController.php` |
| Datos para las pantallas | `app/Support/DatosUsuarios.php` |
| Correos | `app/Notifications/InvitacionCuenta.php` (enlace para crear la contraseña), `RolCambiado.php` |
| Invitaciones | `users.password` vacío e `invitacion_enviada_en` (migración `2026_10_07_000003`) |
| Pruebas | `tests/Feature/UsuariosYRolesTest.php` |

Reglas que valida el servidor:

- Nada sobre la propia cuenta: ni desactivarla ni cambiarle el rol (403).
- **Invitar:** solo roles internos y activos.
- **Asignar un rol:**
  - "Colaborador" nunca;
  - "Empresa" solo a cuentas de una empresa;
  - un rol inactivo no se asigna.
- **Roles del sistema:** no se editan ni se eliminan (403). Un rol con cuentas no se elimina ni se desactiva.
- **Desactivar la cuenta principal:** también desactiva su empresa, y reactivarla la reactiva (RN-025).
- **Correos:** se envían al momento, no en cola, para que se vean en Mailpit sin un *worker*. **[FUNCIONALIDAD POR DEFINIR]** Pasarlos a la cola con el resto de correos (RNF-004, semana 5).

## E12 · Colaboradores (hecho)

`ColaboradoresController`, rutas con `role:Empresa`. Pruebas en `tests/Feature/ColaboradoresTest.php`.

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/colaboradores` | `colaboradores.index` | Cuenta principal primero y luego los colaboradores de su empresa, por nombre |
| POST | `/colaboradores` | `colaboradores.store` | Crea la cuenta: rol Colaborador, misma empresa, activa, sin correo (HU-077) |
| PUT | `/colaboradores/{colaborador}/contrasena` | `colaboradores.contrasena` | Contraseña nueva; cierra sus sesiones y anula "Recordarme" (HU-078) |
| POST | `/colaboradores/{colaborador}/desactivar` | `colaboradores.desactivar` | `activo = false` y cierra sus sesiones (HU-079) |
| POST | `/colaboradores/{colaborador}/reactivar` | `colaboradores.reactivar` | `activo = true` |

- Solo la **cuenta principal** con empresa (`User::esPrincipal()`); un colaborador, un Administrador o una cuenta Empresa sin empresa reciben 403.
- Un colaborador de **otra empresa** da 404; la propia cuenta principal, 403.
- Correo en minúsculas y único (RN-002); contraseña con `Password::default()` (RN-001), sin confirmación.
- La contraseña nunca vuelve al navegador: E12.2 muestra lo que la empresa escribió.

## Otras secciones del menú (rutas previstas)

| URL | Pantalla | Semana |
|---|---|---|
| `/empresas`, `/empresas?sector={id}`, `/empresas/{id}` | A3, A3.1 | 4–5 |
| `/configuracion-ia` | A4 | 5 |
| `/historial` | E8 | 6 |

## Respuestas después de una acción

Después de crear, editar o eliminar, el controlador redirige (`back()`, o al editor cuando corresponda) y deja un mensaje con el mismo mecanismo que ya usa el kit:

```php
Inertia::flash('toast', ['type' => 'success', 'message' => 'Sector creado.']);
```

`type` puede ser `success`, `info`, `warning` o `error`. El frontend lo muestra como notificación (`resources/js/lib/flashToast.ts`).

Los errores de validación llegan con el nombre del campo (`nombre`, `sector_id`…) y se muestran bajo el campo.
