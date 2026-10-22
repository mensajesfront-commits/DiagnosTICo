# Backend

**Estado:** REQUIERE VALIDACIÓN (T-043). Todas las rutas existen y tienen pruebas (14 de octubre de 2026). Falta la revisión de Luis.

Todas las rutas con sesión van con `auth` y con el rol o permiso indicado (matriz en `17_SEGURIDAD.md`). El frontend usa las funciones de Wayfinder (`@/routes/...`); si cambia una URL, se regenera con `php artisan wayfinder:generate --with-form`.

## Rutas por módulo (T-043)

Resumen: cada módulo con su controlador, su protección y sus pantallas. El detalle de cada ruta está en las secciones de abajo.

| Módulo | URL base | Controlador | Protección | Pantallas | Estado |
|---|---|---|---|---|---|
| Acceso | `/login`, `/register`, `/forgot-password`, `/reset-password` | Fortify (`FortifyServiceProvider`, `CreateNewUser`) | Invitado + límite de intentos | L1–L4 | Hecho |
| Ubicaciones | `/ubicaciones/{pais}` | `UbicacionesController` | Pública, 60 por minuto | L2, A6, E11 | Hecho |
| Inicio | `/dashboard`, `/inicio`, `/mi-inicio` | `InicioController`, `DatosInicio`, `DatosInicioEmpresa` | `auth`; cada cuenta a su inicio por rol (T-048) | A1, E1 | Hecho |
| Mi perfil | `/mi-perfil`, `/imagenes/{tipo}/{id}` | `PerfilController` | `auth` | A6, E11 | Hecho |
| Diagnósticos y sectores | `/diagnosticos`, `/sectores` | `DiagnosticosController`, `EditorDiagnosticoController`, `SectoresController` | `diagnosticos.ver` / `diagnosticos.editar` | A2, A2·T, A2b, A2.1, A2.1b–f, A2.1c, A2.2, A2.4, A2.5, A2.7 | Hecho, con el resultado de ejemplo (A2.6) |
| Categorías | `/categorias` | `CategoriasController` | `diagnosticos.ver` / `diagnosticos.editar` | A2.3 | Hecho |
| Usuarios y roles | `/usuarios`, `/roles` | `UsuariosController`, `RolesController` | `usuarios.ver` / `usuarios.gestionar` | A5, A5.1–A5.5 | Hecho |
| Colaboradores | `/colaboradores` | `ColaboradoresController` | `role:Empresa` + cuenta principal | E12 | Hecho |
| Empresas y mediciones | `/empresas`, `/mediciones` | `EmpresasController`, `MedicionesController`, `DatosEmpresas`, `AsignarMedicion` | `empresas.ver`, `empresas.registrar`, `mediciones.asignar` | A3, A3.1, A3.1b–A3.1d, A3.1f, A3.2 | Hecho, con A3.1e (correo), A3.3–A3.5 y las solicitudes de nueva medición (HU-083) |
| Responder medición | `/mi-diagnostico/{id}` | `ResponderController`, `DatosResponder` | `diagnostico.responder` + su empresa | E2–E4 | Hecho |
| Resultados | `/resultados/{id}`, `/resultados/{id}/respuestas`, `/resultados/{id}/pdf` | `ResultadosController`, `DatosResultado` | `resultados.ver`, `resultados.pdf` + su empresa (interna: además `empresas.ver`) | E6, E9, E10, A3.3, A3.5 | Hecho |
| Configuración de la IA | `/configuracion-ia` | `ConfiguracionIaController`, `DatosConfiguracionIa` | `ia.ver` / `ia.editar` | A4, A4.4–A4.7, A4.9 | Hecho |
| Historial | `/historial`, `/empresas/{id}/historial` | `HistorialController`, `DatosHistorial` | E8: `resultados.ver` + su empresa (una cuenta interna recibe 404); A3.4: `empresas.ver` | E8, A3.4 | Hecho. Sale de `DatosEmpresas::ficha` para que coincida con A3.1; lista solo las terminadas con resultado |
| Webhook del bot de WhatsApp | `/webhook/whatsapp` | — | Firma del proveedor, sin sesión | — | **Aplazado** (EP-013). **[FUNCIONALIDAD POR DEFINIR]** Si se retoma, va separado de empresas y mediciones (RN-029) |

Los permisos son los 15 de `RolesYPermisosSeeder`. Todos los módulos están construidos; el bot de WhatsApp sigue aplazado.

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
| Sectores de ejemplo | `database/seeders/SectoresSeeder.php` | 6 sectores (se quitó Talleres, 9 de octubre). **[INFORMACIÓN PENDIENTE]** lista real |
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

Ubicaciones (DEC-016): `GET /ubicaciones/{pais}` devuelve los departamentos y ciudades de uno de los 18 países (`app/Support/Ubicaciones.php`, archivos en `resources/ubicaciones/`).

## Inicio según el rol (T-048, hecho)

Fortify redirige a `/dashboard` después del login y del registro. `InicioController` manda a cada cuenta a su inicio. Pruebas en `tests/Feature/InicioTest.php`.

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/dashboard` | `dashboard` | Empresa y Colaborador → `/mi-inicio`; Administrador y roles internos → `/inicio` |
| GET | `/inicio` | `inicio.administrador` | A1: indicadores, mediciones, empresas por nivel y diagnósticos por sector (`DatosInicio`). Una cuenta de empresa que entra aquí vuelve a `/mi-inicio` |
| GET | `/mi-inicio` | `inicio.empresa` | E1. Una cuenta interna que entra aquí vuelve a `/inicio` |


## Mi perfil (ya existe)

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/mi-perfil` | `profile.edit` | A6 / E11 |
| PATCH | `/mi-perfil` | `profile.update` | Guarda datos personales, avisos y, para la cuenta principal, los datos de la empresa (el sector no) |
| PUT | `/mi-perfil/contrasena` | `user-password.update` | Cambia la contraseña con la actual; guarda `contrasena_actualizada_en` |
| POST | `/mi-perfil/foto` | `perfil.foto` | Foto de la cuenta o logo de la empresa |
| GET | `/imagenes/{tipo}/{id}` | `perfil.imagen` | Sirve la foto o el logo a la misma cuenta, su empresa o quien tenga `empresas.ver` / `usuarios.ver` |

Se quitaron las páginas de ajustes del kit (`/settings/profile`, `/settings/security`) y la opción de que cada cuenta se elimine sola: solo el Administrador elimina cuentas (A5, DEC-017). `/settings` redirige a `/mi-perfil`. El "último acceso" se guarda al iniciar sesión (`AppServiceProvider`).

## Diagnósticos y sectores (T-049, T-050)

| Método | URL | Permiso | Qué hace | Envía / devuelve |
|---|---|---|---|---|
| GET | `/diagnosticos` | `diagnosticos.ver` | A2·T: todos los diagnósticos | Props de `diagnosticos/Index` |
| GET | `/diagnosticos?sector={id}` | `diagnosticos.ver` | A2 / A2b: un sector | Props de `diagnosticos/Index` |
| GET | `/diagnosticos/crear?sector={id}` | `diagnosticos.editar` | A2.5 | Props de `diagnosticos/Crear` |
| POST | `/diagnosticos` | `diagnosticos.editar` | Crea el borrador v1 y abre el editor (A2.1) | `nombre` (máx. 60, único en el sector), `sector_id`, `descripcion`, `punto_partida`, `categorias[]`, `copiar_de` |
| GET | `/diagnosticos/{id}/editar` | `diagnosticos.editar` | A2.1 · editor (`?categoria=` parte, `?pregunta=`) | Props de `diagnosticos/Editor` |
| GET | `/diagnosticos/{id}/vista-previa` | `diagnosticos.ver` | A2.4 · vista previa del borrador o de una versión (`?version=`) | Props de `diagnosticos/VistaPrevia` |
| POST | `/diagnosticos/{id}/duplicar` | `diagnosticos.editar` | A2.7: copia en borrador v1 y abre el editor | `nombre` (máx. 60, único en el sector), `sector_id` |
| POST | `/diagnosticos/{id}/archivar` | `diagnosticos.editar` | Archiva un diagnóstico publicado | — |
| DELETE | `/diagnosticos` | `diagnosticos.editar` | Elimina varios a la vez (selección en la tabla). Solo los que nunca se publicaron; si alguno tiene versiones no se elimina ninguno y el error en `ids` dice cuáles archivar | `ids[]` |
| DELETE | `/diagnosticos/{id}` | `diagnosticos.editar` | Elimina un borrador que nunca se publicó | — |
| DELETE | `/diagnosticos/{id}/borrador` | `diagnosticos.editar` | Elimina el borrador pendiente (vN) sin tocar la versión publicada | — |
| POST | `/sectores` | `diagnosticos.editar` | A2.2a: crear. Con división CIIU (elegida, o porque el nombre es exactamente el de una división) le pone todas sus clases como subsectores (DEC-018) | `nombre` (máx. 60, único), `descripcion`, `ciiu_division` (opcional), `activo` |
| PUT | `/sectores/{id}` | `diagnosticos.editar` | A2.2: editar. Cambiar la división cambia los subsectores (los que ya no van se desactivan) | `nombre`, `descripcion`, `ciiu_division` |
| POST | `/sectores/{id}/reasignar` | `diagnosticos.editar` | A2.2b | `sector_destino_id`, `avisar` |
| POST | `/sectores/{id}/desactivar` | `diagnosticos.editar` | A2.2c | — |
| POST | `/sectores/{id}/reactivar` | `diagnosticos.editar` | Reactivar desde A2.2 | — |
| DELETE | `/sectores/{id}` | `diagnosticos.editar` | A2.2e. Solo si no tiene empresas ni mediciones (RN-008). | — |

**Nombre único por sector:** dentro de un mismo sector, dos diagnósticos no pueden llamarse igual, en ningún estado (un archivado también cuenta). En sectores distintos sí se puede repetir. No se miran mayúsculas ni espacios de más, y el nombre se guarda sin espacios de más (`app/Rules/NombreDiagnosticoUnico.php`). Error: "Este sector ya tiene un diagnóstico con ese nombre. Elige otro."

## Editor del diagnóstico (A2.1, hecho)

`EditorDiagnosticoController`; datos de `App\Support\DatosEditor` y estado de `App\Support\EstadoDiagnostico`. Pruebas en `tests/Feature/EditorDiagnosticoTest.php`. Todo con `diagnosticos.editar`, menos la vista previa.

| Método | URL | Qué hace | Envía |
|---|---|---|---|
| POST | `/diagnosticos/{id}/preguntas` | HU-024 · agrega al final de la categoría | `parte_id`, `texto` (máx. 500), `tipo`, `indicacion` (obligatoria, máx. 500), `criterio_ia` (abierta, máx. 1000), `obligatoria`, `opciones[]` (2 a 10: `texto`, `puntaje` 0–100, `ten_en_cuenta` máx. 200) |
| PUT | `/diagnosticos/{id}/preguntas/{pregunta}` | HU-025 · editar; las opciones con `id` conservan su id | Lo mismo, `opciones[].id` |
| DELETE | `/diagnosticos/{id}/preguntas/{pregunta}` | HU-025 · eliminar y renumerar | — |
| PUT | `/diagnosticos/{id}/categorias/{parte}/orden` | HU-023 · nuevo orden | `preguntas[]` (todos los id de la categoría) |
| POST | `/diagnosticos/{id}/categorias` | HU-026 · agregar del catálogo (no archivadas) | `categorias[]`, `reparto` (`mantener`: las nuevas en 0% · `iguales`) |
| GET | `/diagnosticos/{id}/importancia` | A2.1c | — |
| PUT | `/diagnosticos/{id}/importancia` | HU-027 · solo si suma exactamente 100 (hasta 2 decimales) | `importancias{parte_id: valor}` |
| POST | `/diagnosticos/{id}/publicar` | HU-029 · nueva versión congelada; el diagnóstico queda publicado y sin borrador | `nota` (obligatoria, máx. 500), `pendientes` (`dejar` · `pasar`: las no iniciadas o vencidas sin respuestas pasan a la nueva) |

- **Borrador (RN-010):** cada cambio va al borrador. Si el diagnóstico está publicado y no tiene borrador, el primer cambio abre el siguiente (`version_borrador` = última + 1). Las versiones publicadas no se tocan. Uno archivado no se edita.
- **Requisitos para publicar (RN-012):** cada categoría con al menos 1 pregunta; cada pregunta de opciones con al menos 2 opciones con puntaje; la importancia suma 100%; toda pregunta con indicación; cada selección múltiple puede llegar a 100. Si falta alguno, publicar responde un error en `nota`.
- La pregunta, las preguntas de otro diagnóstico o una parte de otro diagnóstico dan 404.

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
| POST | `/usuarios/{id}/ver-como` | `usuarios.gestionar` | A5.4: empieza "Ver como" (`VerComoController@iniciar`) y lleva al inicio de esa cuenta. No sobre Administradores ni sobre la propia (403) | — |
| POST | `/ver-como/salir` | `auth` | Sale de "Ver como" y vuelve a donde empezó (A5 o A3.1) | — |
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
- **Correos:** la invitación y el aviso de cambio de rol se envían al momento, no en cola: son pocos y la pantalla confirma que salieron.

## E12 · Colaboradores (hecho)

`ColaboradoresController`, rutas con `role:Empresa`. Pruebas en `tests/Feature/ColaboradoresTest.php`.

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/colaboradores` | `colaboradores.index` | Cuenta principal primero y luego los colaboradores de su empresa, por nombre |
| POST | `/colaboradores` | `colaboradores.store` | Crea la cuenta: rol Colaborador, misma empresa, activa, sin correo (HU-077) |
| PUT | `/colaboradores/{colaborador}` | `colaboradores.actualizar` | Edita nombre, cargo, correo y, si se envía, la contraseña; si cambia el correo o la contraseña, cierra sus sesiones y anula "Recordarme" (HU-078) |
| POST | `/colaboradores/{colaborador}/desactivar` | `colaboradores.desactivar` | `activo = false` y cierra sus sesiones (HU-079) |
| POST | `/colaboradores/{colaborador}/reactivar` | `colaboradores.reactivar` | `activo = true` |
| DELETE | `/colaboradores/{colaborador}` | `colaboradores.eliminar` | Elimina (soft delete, DEC-017): cierra sus sesiones; el Administrador lo recupera en 90 días desde A5 |

- Solo la **cuenta principal** con empresa (`User::esPrincipal()`); un colaborador, un Administrador o una cuenta Empresa sin empresa reciben 403.
- Un colaborador de **otra empresa** da 404; la propia cuenta principal, 403.
- Correo en minúsculas y único (RN-002); contraseña con `Password::default()` (RN-001), sin confirmación.
- La contraseña nunca vuelve al navegador: E12.2 muestra lo que la empresa escribió.

## Empresas y mediciones (A3, hecho)

`EmpresasController` y `MedicionesController`; datos de `App\Support\DatosEmpresas` y la regla de asignar en `App\Services\Mediciones\AsignarMedicion`. Pruebas en `tests/Feature/EmpresasTest.php`.

| Método | URL | Nombre | Permiso | Qué hace |
|---|---|---|---|---|
| GET | `/empresas` | `empresas.index` | `empresas.ver` | A3 · lista |
| GET | `/empresas/{empresa}` | `empresas.ver` | `empresas.ver` | A3.1 · ficha |
| GET | `/empresas/registrar` | `empresas.crear` | `empresas.registrar` | A3.2 |
| POST | `/empresas` | `empresas.guardar` | `empresas.registrar` | Crea la empresa y su usuario principal (rol Empresa, sin contraseña) y le envía la invitación (`Invitaciones::enviar`, RN-006). Si se pide, asigna la primera medición en la misma transacción |
| PUT | `/empresas/{empresa}` | `empresas.actualizar` | `empresas.registrar` | A3.1d · datos de la empresa y del usuario principal. Cambiar el sector no toca las mediciones; si cambia el correo, cierra sus sesiones |
| POST | `/empresas/{empresa}/desactivar` | `empresas.desactivar` | `empresas.registrar` | A3.1f · empresa y cuenta principal inactivas, cierra las sesiones de todos sus usuarios; motivo y aviso (`EmpresaDesactivada`) opcionales |
| POST | `/empresas/{empresa}/reactivar` | `empresas.reactivar` | `empresas.registrar` | Vuelve a activar empresa y cuenta principal |
| GET | `/mediciones/asignar` | `mediciones.crear` | `mediciones.asignar` | A3.1b (`?empresa=`) |
| POST | `/mediciones` | `mediciones.guardar` | `mediciones.asignar` | Asigna. Si hay una pendiente, sin `reemplazar` responde un error en `reemplazar`; con `reemplazar` la cancela y la nueva toma su número (A3.1c) |
| POST | `/mediciones/{medicion}/cancelar` | `mediciones.cancelar` | `mediciones.asignar` | Cancela la pendiente (no enviada) |
| PUT | `/mediciones/{medicion}/fecha` | `mediciones.fecha` | `mediciones.asignar` | Nueva fecha (HU-009); una vencida vuelve a en curso o no iniciada |
| GET | `/mediciones/{medicion}/correo` | `mediciones.correo` | `mediciones.asignar` | JSON para A3.1e: para, asunto, cuerpo, valores de las variables y último aviso |
| POST | `/mediciones/{medicion}/correo` | `mediciones.correo.enviar` | `mediciones.asignar` | Recordatorio / Reenviar aviso (HU-008): guarda el texto en la medición (y, con `guardar_plantilla`, como plantilla) y lo envía |
| POST | `/mediciones/correo/prueba` | `mediciones.correo.prueba` | `mediciones.asignar`, 10 por minuto | Correo de prueba solo a quien lo pide (HU-037 CA-003) |

Reglas de `AsignarMedicion` (RN-014):

- Solo diagnósticos **publicados** del **sector de la empresa**, y solo a empresas activas.
- Una pendiente por empresa (`no_iniciada`, `en_curso`, `enviada`, `vencida`). Reemplazar la marca `cancelada`, guarda `reemplazada_por` y da por atendidas sus solicitudes abiertas.
- La medición nueva toma el número de la que reemplaza, o el siguiente.
- Con `aviso`, envía el correo (`EnviarAviso`): si no sale, la medición queda asignada igual y la pantalla avisa que use "Reenviar aviso".

Correo de aviso (A3.1e):

- `App\Support\CorreoAviso`: plantilla general (`plantillas_correo`, clave `aviso_medicion`; sin fila, el texto del wireframe), el texto de cada medición y el relleno de variables.
- `App\Services\Mediciones\EnviarAviso`: lo envía al usuario principal y guarda `ultimo_aviso_en`.
- `AvisoMedicion` va en la **cola** (`ShouldQueue`, 3 intentos): la pantalla no espera al servidor de correo (HU-037 CA-004, CA-005). En local hay que tener corriendo `queue:work`.

Tarea (HU-070): `mediciones:vencer` corre cada hora (a los :05) y marca `vencida` las no iniciadas o en curso cuya fecha límite ya pasó en la zona horaria de la empresa, la de su cuenta principal en Mi perfil (`App\Support\Plazos`; `schedule:work` en local). Al asignar o cambiar la fecha, la más cercana que se acepta es dentro de 3 días (`PLAZO_MINIMO_DIAS`).

## Responder el diagnóstico (E1–E4, hecho)

`ResponderController`; cuestionario y avance en `App\Support\DatosResponder`; inicio de la empresa en `App\Support\DatosInicioEmpresa` (`InicioController@empresa`). Pruebas en `tests/Feature/ResponderTest.php`. Todo con `diagnostico.responder` y solo para la empresa dueña de la medición (de otra empresa, 404).

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/mi-diagnostico/{medicion}` | `responder.mostrar` | E2 (`?categoria=` índice). Sin puntajes ni "Ten en cuenta" (RN-013). Si ya se envió, va a E4; si venció o se canceló, al inicio |
| PUT | `/mi-diagnostico/{medicion}/respuestas` | `responder.guardar` | JSON. Guarda una respuesta (`pregunta_ref`, `opciones[]` o `texto` máx. 1000); vacía la borra. Pasa la medición a `en_curso`. Devuelve `guardado_en` y el avance. 120 por minuto |
| GET | `/mi-diagnostico/{medicion}/revisar` | `responder.revisar` | E3 |
| POST | `/mi-diagnostico/{medicion}/enviar` | `responder.enviar` | Todas las obligatorias respondidas → `enviada` y `enviada_en`, y manda a la cola `AnalizarMedicion`. Después no se cambia nada (RN-017) |
| GET | `/mi-diagnostico/{medicion}/enviado` | `responder.enviado` | E4 con `analisis` (total, terminadas, fallidas). Si ya terminó, va al resultado |
| POST | `/mi-diagnostico/{medicion}/reintentar` | `responder.reintentar` | HU-063 CA-004: vuelve a mandar solo las categorías fallidas, con 3 intentos nuevos. 5 por minuto |
| POST | `/mi-diagnostico/{medicion}/solicitud` | `responder.solicitar` | HU-082: solo si venció; una solicitud abierta por empresa; avisa por correo a los administradores (`NuevaMedicionSolicitada`). 5 por minuto |

- **Se puede responder** si está no iniciada o en curso y no pasó la fecha límite (RN-016).
- El correo de contacto de E1 sale de `SOPORTE_EMAIL` (**[INFORMACIÓN PENDIENTE]** la dirección real).

## Configuración de la IA (A4, HU-041 a HU-045, HU-080, hecho)

`ConfiguracionIaController`; datos de la pantalla en `App\Support\DatosConfiguracionIa`; el armado en `App\Services\Ia\ArmadorPrompt`. Pruebas en `tests/Feature/ConfiguracionIaTest.php`.

| Método | URL | Nombre | Permiso | Qué hace |
|---|---|---|---|---|
| GET | `/configuracion-ia` | `ia.index` | `ia.ver` | A4. `?alcance=sector&sector=3` (A4.5) o `?alcance=empresa&empresa=8` (A4.6) |
| GET | `/configuracion-ia/como-se-arma` | `ia.como-se-arma` | `ia.ver` | A4.9, solo lectura |
| POST | `/configuracion-ia/vista-previa` | `ia.vista-previa` | `ia.ver` | JSON. A4.4: el prompt final de un ejemplo (`medicion_id`, `categoria_ref`) con el `borrador` sin guardar; cada trozo con su origen (`general`, `sector`, `empresa`) y si es un dato insertado. 60 por minuto |
| PUT | `/configuracion-ia` | `ia.guardar` | `ia.editar` | Guarda la capa: general (contexto y tarea obligatorios) o el ajuste de un sector o empresa (`modos` usar · agregar · reemplazar; texto obligatorio si no es "usar"). Máx. 4000 caracteres por elemento |
| DELETE | `/configuracion-ia/ajuste` | `ia.quitar` | `ia.editar` | Quita el ajuste de un sector o una empresa |
| PUT | `/configuracion-ia/revision` | `revision.cambiar` | `ia.editar` | Enciende o apaga "Revisar antes de publicar" (tabla `ajustes`) |
| GET | `/revision/{medicion}` | `revision.ver` | `ia.editar` | El resultado sin publicar, en la misma pantalla de E6 (`PublicarResultado::vistaPrevia`) |
| POST | `/revision/{medicion}/aprobar` | `revision.aprobar` | `ia.editar` | Publica el resultado y guarda quién lo aprobó (`resultados.aprobado_por`) |
| POST | `/revision/{medicion}/rehacer` | `revision.rehacer` | `ia.editar` | Rehace el análisis de una categoría (`categoria`) o de todas; vuelve a quedar por revisar al terminar |
| POST | `/configuracion-ia/probar` | `ia.probar` | `ia.editar` | JSON. A4.7: analiza el ejemplo con el borrador y devuelve lo que diría la IA. No guarda nada; si la IA falla, 422 con el error. 10 por minuto |

- **Datos que se insertan solos:** `{sector}`, `{contexto_sector}` (la descripción del sector), `{categoria}` y `{respuestas_categoria}` (en el texto queda "las preguntas con sus respuestas, que van en el mensaje siguiente"; las respuestas siempre se envían aparte, en JSON).
- El sector que se usa es el del diagnóstico de la medición.
- Los cambios aplican a los análisis que se hagan después de guardar; cada análisis guarda su prompt exacto en `analisis_categoria.prompt`.
- **[INFORMACIÓN PENDIENTE]** El límite de pruebas por día (HU-045 "REQUIERE VALIDACIÓN"). Por ahora solo hay 10 por minuto.

## Análisis de la IA y resultado (HU-068, HU-069, HU-081, HU-084, hecho)

Pruebas en `tests/Feature/ResultadosTest.php` y `tests/Feature/PruebaTecnica/AnalisisIaTest.php`.

1. **`App\Jobs\AnalizarMedicion`** (al enviar): calcula el puntaje de las cerradas en `respuestas.puntaje` (`App\Services\Resultados\CalculadoraPuntajes`, RN-018), crea un `analisis_categoria` pendiente por categoría y manda cada uno a la cola.
2. **`App\Jobs\AnalizarCategoria`** (una etapa por categoría, RN-021): arma el prompt (`App\Services\Ia\ArmadorPrompt`), lo guarda en `analisis_categoria.prompt`, llama al `Analizador` y guarda la respuesta, las observaciones y el puntaje de las abiertas. `tries = 3` con espera de 15 y 60 s. Tras el tercer fallo queda `fallido` y avisa a los administradores (`AnalisisFallido`); las demás categorías conservan su análisis.
3. **`App\Services\Resultados\PublicarResultado`** (después de cada categoría): cuando todas están terminadas, con la fila de la medición bloqueada para publicar una sola vez, consolida puntajes, nivel (RN-019) y variación contra la medición anterior de la empresa (RN-020), guarda `resultados` (inmutable, RN-023), pasa la medición a `terminada`, avisa a las cuentas activas de la empresa (`ResultadoListo`) y manda `GenerarInformePdf`.
4. **`App\Jobs\GenerarInformePdf`**: `App\Services\Resultados\InformePdf` arma `resources/views/pdf/informe.blade.php` (3 páginas, RN-024) y lo guarda en `storage/app/private/informes/{empresa}/informe_{empresa}_m{n}.pdf` (`resultados.pdf_ruta`). Si falla, el resultado queda publicado y se regenera al descargarlo.

**Analizador.** `App\Services\Ia\Analizador` tiene dos implementaciones: `AnalizadorDeCategoria` (OpenAI con salida estructurada estricta; valida una entrada por pregunta y abiertas de 0 a 100) y `AnalizadorDePrueba` (`IA_DE_PRUEBA=true`, sin conexión; también en las pruebas).

**Prompt por capas (RN-022).** General (fila `prompts` con `alcance = general`; si no existe, `ArmadorPrompt::GENERAL`), luego el ajuste del sector del diagnóstico y el de la empresa: en cada elemento (contexto, tarea, detalles, ejemplos) `usar` deja el anterior, `agregar` suma al final y `reemplazar` lo sustituye. Luego las preguntas respondidas con su respuesta, el puntaje de las cerradas, el "Ten en cuenta" solo de las opciones elegidas y el criterio de las abiertas (JSON, mensaje aparte), y el formato fijo.

**Método de análisis (DEC-019).** La IA elige PESTEL, PORTER, VRIO, DOFA o CAME según las respuestas y lo devuelve en `metodo` y `metodo_motivo`; se guarda en `por_categoria[].metodo`.

**Cálculo (RN-018).** Única: el puntaje de la opción; múltiple: la suma con tope 100; abierta: la IA. Categoría: promedio redondeado de sus preguntas respondidas. Total: promedio ponderado por importancia de los puntajes de categoría ya redondeados (los que ve la empresa). **[FUNCIONALIDAD POR DEFINIR]** Una opcional sin responder no entra al promedio (no cuenta como 0); una categoría sin ninguna respondida vale 0 y no llama a la IA.

| Método | URL | Nombre | Qué hace |
|---|---|---|---|
| GET | `/diagnosticos/{id}/resultado-ejemplo` | `diagnosticos.resultado-ejemplo` | A2.6 (HU-030), permiso `diagnosticos.ver`: `?caso=` (mejor, medio o peor) y `?version=N` (sin ella, el borrador). Respuestas de muestra calculadas con `PublicarResultado::calcular`; no llama a la IA ni guarda nada (`ResultadoEjemploController`, `ResultadoEjemplo`) |
| GET | `/diagnosticos/{id}/resultado-ejemplo/pdf` | `diagnosticos.resultado-ejemplo.pdf` | "Ver formato del PDF": el mismo informe con los datos de ejemplo, en el navegador. 20 por minuto |
| GET | `/resultados/{medicion}` | `resultados.ver` | E6 / A3.3. Si la medición está enviada, la empresa va a E4; si no hay resultado, 404 |
| GET | `/resultados/{medicion}/respuestas` | `resultados.respuestas` | E9 / A3.5 (`?categoria=c3`) |
| GET | `/resultados/{medicion}/pdf` | `resultados.pdf` | Descarga el PDF guardado; si falta, lo genera con lo guardado (no llama a la IA). Si no se puede, vuelve con un aviso. 20 por minuto |

La empresa y su colaborador solo ven su empresa (de otra, 404). Una cuenta interna necesita además `empresas.ver`.

## "Ver como" (A5.4, HU-053, HU-054, hecho)

- `App\Http\Middleware\VerComo` (grupo `web`, después de `CerrarSesionCuentaInactiva`): la sesión sigue siendo del Administrador; en cada petición, la cuenta vista pasa a ser el usuario (menú, permisos y datos de su rol).
- **Solo lectura (RN-026):** toda petición que no sea GET o HEAD se rechaza, salvo salir y cerrar sesión. Inertia vuelve atrás con el aviso "Estás en \"Ver como\": es solo de lectura y no se guardó nada."; las llamadas JSON reciben 403 con ese mensaje.
- **Registro (RN-026):** `registros_ver_como` guarda quién, a qué cuenta, el inicio y el fin. Se cierra al salir, al cerrar sesión o si el Administrador pierde `usuarios.gestionar` (la vista termina sola).
- El estado vive en la sesión (`App\Support\SesionVerComo`) y se comparte con el frontend como `verComo` para la franja.
- Pruebas en `tests/Feature/VerComoTest.php`.

## Solicitudes de nueva medición (HU-082, HU-083, hecho)

- `POST /mi-diagnostico/{medicion}/solicitud` (`responder.solicitar`, 5 por minuto): la empresa pide otra medición cuando la suya venció. Una abierta a la vez (`solicitudes_medicion`); llega `NuevaMedicionSolicitada` a los administradores.
- `SolicitudMedicion::abiertasDe()` alimenta los avisos de A1 (`solicitudes` y `solicitud` en cada vencida), A3, la ficha, A3.4 y A3.1b.
- `SolicitudMedicion::atender()` la cierra al asignar otra medición (`AsignarMedicion`), al darle nueva fecha o al cancelar la vencida (`MedicionesController`).
- Pruebas en `tests/Feature/SolicitudesMedicionTest.php`.

## Avisos por correo de Mi perfil (hecho)

Cada notificación mira la preferencia de quien la recibe con `User::quiereAviso($clave)` (`OpcionesPerfil::AVISOS_INTERNOS` y `AVISOS_EMPRESA`):

| Clave | Correo | Dónde se mira |
|---|---|---|
| `empresa_envia` | `DiagnosticoEnviado` | `via()` de la notificación |
| `ia_falla` | `AnalisisFallido` | `via()` de la notificación |
| `resumen_semanal` | `ResumenSemanal` (comando `mediciones:resumen`, lunes 07:50) | El comando y `via()` |
| `medicion_asignada` | `AvisoMedicion` al asignar | `EnviarAviso::enviar(alAsignar: true)`; los recordatorios salen siempre |
| `resultado_listo` | `ResultadoListo` | `via()` de la notificación |

Pruebas en `tests/Feature/AvisosPerfilTest.php`.

## Respuestas después de una acción

Después de crear, editar o eliminar, el controlador redirige (`back()`, o al editor cuando corresponda) y deja un mensaje con el mismo mecanismo que ya usa el kit:

```php
Inertia::flash('toast', ['type' => 'success', 'message' => 'Sector creado.']);
```

`type` puede ser `success`, `info`, `warning` o `error`. El frontend lo muestra como notificación (`resources/js/lib/flashToast.ts`).

Los errores de validación llegan con el nombre del campo (`nombre`, `sector_id`…) y se muestran bajo el campo.
