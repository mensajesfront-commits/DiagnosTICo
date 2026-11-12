# Seguridad

**Estado:** COMPLETO. La matriz de roles y permisos sigue el wireframe A5.2 (T-006). Incluye la auditoría QA del 4 de noviembre y los textos legales oficiales.

## Roles

Hay cuatro roles del sistema. Son fijos: no se editan ni se eliminan, y cada cuenta tiene un solo rol (RN-027).

| Rol | Quién es | Cómo se crea |
|---|---|---|
| Administrador | Persona de NuevasTIC | Seeder inicial (T-046); después, desde Usuarios y roles (A5) |
| Empresa | Usuario principal de una empresa cliente | Registro propio (L2) o por el Administrador (A3.2) |
| Colaborador | Persona de la empresa con su propia cuenta | La crea la empresa (E12.1); la empresa define el correo y la contraseña (RN-025) |
| Consultor | Persona externa que acompaña a una o varias empresas (desde el 3 de noviembre) | La invita el Administrador (A5), que le vincula las empresas (A3.1, pestaña Consultores) (RN-030) |

El Administrador puede crear roles nuevos con los permisos que elija (HU-049); cada uno tiene nombre, descripción opcional y si está activo. Un rol con cuentas no se elimina (RN-027).

**Rol Consultor (RN-030, decisión del 3 de noviembre):** persona externa que invita el Administrador. Es **rol del sistema** y está activo, con 5 permisos fijos: ver empresas, ver resultados, PDF, editar su perfil y cambiar su contraseña. **No** tiene "Responder el diagnóstico" ni nada de diagnósticos, mediciones, IA o usuarios. Solo ve las empresas de la tabla `consultor_empresa`; la regla vive en `User::puedeVerEmpresa()` y la usan Empresas (lista y ficha), el historial, los resultados, las respuestas, el PDF y los logos. Entra a su propio inicio (C1), no a A1. Antes (PA-006, 6 de octubre) existía inactivo y editable; quedó cerrado porque, si alguien lo activaba, veía todas las empresas.

Cada rol guarda `descripcion`, `activo` y `del_sistema` (columnas agregadas a la tabla `roles` de spatie). Un rol inactivo no se puede asignar.

## Permisos

Son los **15 permisos en 6 bloques** del wireframe A5.2 (A5.1 "Crear rol" usa la misma lista). La columna "Texto en pantalla" es lo que muestra la casilla.

| Bloque | Permiso (nombre técnico) | Texto en pantalla | Qué permite | Pantallas |
|---|---|---|---|---|
| Diagnósticos | `diagnosticos.ver` | Ver diagnósticos | Ver sectores, catálogo, diagnósticos y vista previa | A2, A2·T, A2.3, A2.4, A2.6 |
| | `diagnosticos.editar` | Editar preguntas e importancia | Crear y editar sectores, categorías, diagnósticos, preguntas e importancia; duplicar y archivar | A2.1–A2.3c, A2.5, A2.7 |
| | `diagnosticos.publicar` | Publicar versiones | Publicar una versión (la ruta pide este permiso además de `diagnosticos.editar`) | A2.1c |
| Empresas | `empresas.ver` | Ver empresas | Ver empresas y su ficha | A3, A3.1 |
| | `empresas.registrar` | Registrar empresas | Registrar empresas, editar sus datos, desactivar y reactivar | A3.1d, A3.1f, A3.2 |
| | `usuarios.gestionar` (también) | — | Eliminar una empresa desactivada, recuperable 90 días (HU-035 CA-007) | A3.1 |
| | `mediciones.asignar` | Asignar mediciones | Asignar mediciones, dar nueva fecha, recordatorios, correo de aviso y solicitudes | A1b–A1d, A3.1b, A3.1e |
| Mediciones y resultados | `diagnostico.responder` | Responder el diagnóstico | Responder, revisar y enviar el diagnóstico | E2–E5 |
| | `resultados.ver` | Ver resultados y respuestas | Ver resultados, historial y respuestas | A3.3–A3.5, E6–E10 |
| | `resultados.pdf` | Descargar PDF | Descargar el PDF del resultado. La empresa y su colaborador reciben siempre el resumen; las cuentas internas y el Consultor, el informe completo o el resumen (RN-024) | E6, E8, E9, A3.3–A3.5 |
| Configuración IA | `ia.ver` | Ver configuración | Ver prompts, ajustes y el prompt final | A4 |
| | `ia.editar` | Editar prompts | Editar prompts y ajustes por sector o empresa; probarlos | A4–A4.9 |
| Usuarios y roles | `usuarios.ver` | Ver usuarios | Ver cuentas y roles | A5 |
| | `usuarios.gestionar` | Gestionar usuarios y roles | Invitar, desactivar, reactivar, cambiar rol, crear y editar roles, "Ver como" | A5.1–A5.5 |
| Su cuenta | `perfil.editar` | Editar su perfil | Cambiar sus datos personales. En el rol Empresa, también los de la empresa | A6, E11 |
| | `contrasena.cambiar` | Cambiar su contraseña | Cambiar su contraseña | A6, E11 |

En la Empresa y el Colaborador, los permisos de "Mediciones y resultados" valen **solo para su empresa** (RN-007). En el Consultor, "Ver empresas" y los de resultados valen **solo para sus empresas vinculadas** (RN-030); de otra, 404. Vincular o quitar consultores pide `usuarios.gestionar`.

Lo que no tiene casilla en A5.2 se decide por el rol:

- **Inicio del Administrador (A1):** cualquier rol interno (que no sea Empresa ni Colaborador). Lo que muestra depende de sus permisos.
- **Colaboradores (E12):** solo el rol Empresa (RN-025, HU-076).
- **"Ver como" (A5.4):** lo usa quien tiene `usuarios.gestionar`, en solo lectura y con registro (RN-026). Sirve para **comprobar que cada rol ve solo las pantallas que se le asignaron**, así que se usa sobre cuentas de Empresa, Colaborador, Consultor y cualquier rol creado, **no sobre cuentas de Administrador** (respuesta del equipo, 6 de octubre). **[INCONSISTENCIA DETECTADA]** El wireframe A5.4b muestra "Ver como" sobre un Administrador; queda fuera según esta respuesta. Tampoco se usa sobre la propia cuenta. El bloqueo de escritura y el registro los hace el middleware `VerComo` (docs/15_BACKEND.md).

Para todas las cuentas, sin permiso: iniciar y cerrar sesión y recuperar la contraseña.

## Matriz por rol

Los roles del sistema tienen permisos fijos (RN-027). Así los muestra A5.2:

| Permiso | Administrador | Empresa | Colaborador | Consultor |
|---|:---:|:---:|:---:|:---:|
| `diagnosticos.ver` | ✓ | | |  |
| `diagnosticos.editar` | ✓ | | |  |
| `diagnosticos.publicar` | ✓ | | |  |
| `empresas.ver` | ✓ | | | ✓ (sus empresas) |
| `empresas.registrar` | ✓ | | |  |
| `mediciones.asignar` | ✓ | | |  |
| `diagnostico.responder` | ✓ | ✓ | elige su empresa |  |
| `resultados.ver` | ✓ | ✓ | elige su empresa | ✓ (sus empresas) |
| `resultados.pdf` | ✓ | ✓ | elige su empresa | ✓ (sus empresas) |
| `ia.ver` | ✓ | | |  |
| `ia.editar` | ✓ | | |  |
| `usuarios.ver` | ✓ | | |  |
| `usuarios.gestionar` | ✓ | | |  |
| `perfil.editar` | ✓ | ✓ | | ✓ |
| `contrasena.cambiar` | ✓ | ✓ | | ✓ |
| **Total** | **15** | **5** | **0 en el rol** | **5** |

- El Administrador tiene los 15, también "Responder el diagnóstico", aunque no responde por ninguna empresa (no tiene `empresa_id`).
- **Colaborador (cambio del 26 de octubre):** el rol no trae permisos. La cuenta principal elige, para cada colaborador, qué páginas ve: "Responder el diagnóstico", "Resultados y Mi historial" y "Descargar el informe PDF" (este último pide también los resultados). Se guardan como permisos directos de su cuenta (`App\Support\PaginasColaborador`); para dar una página nueva se agrega ahí su permiso. Inicio y Mi perfil siempre los ve.
- El colaborador **no** edita su perfil ni cambia su contraseña: Mi perfil queda en solo lectura y "¿Olvidaste tu contraseña?" le envía un correo que dice que se la cambia la cuenta principal (sin enlace). Esto reemplaza la respuesta del 6 de octubre.
- Al pasar un colaborador a otro rol (A5.5) pierde las páginas que le dio su empresa.
- **Administrador principal:** la cuenta creada al instalar (`users.administrador_principal`). Nadie le quita el rol Administrador, ni la desactiva ni la elimina (`CambioDeRol::permitido`, `UsuariosController`). Es la de `ADMIN_EMAIL` (la define NuevasTIC al lanzar). Solo ella se elimina, desde Mi perfil, eligiendo a otro Administrador activo como nueva principal (`PerfilController::eliminarCuenta`).
- **Cuenta de empresa que pasa a un rol interno:** deja la empresa, se borra su contraseña y recibe la invitación para crear otra (RN-006). Si era la cuenta principal, la empresa queda desactivada (`App\Support\CambioDeRol`) y no se reactiva hasta darle otra cuenta principal en A3.1d: una cuenta nueva con invitación (`EmpresasController::cuentaPrincipal`, permiso `empresas.registrar`).
- **Colaboradores:** no cambian de rol por ningún camino (A5.5, crear rol, cuenta principal de la empresa). Solo los desactivan o eliminan el Administrador o la cuenta principal de su empresa (`CambioDeRol::permitido`).
- **Rol desactivado:** sus cuentas no entran (mismo modal que una cuenta desactivada) y si tienen la sesión abierta se cierra en la siguiente petición, hasta que les asignen otro rol. Nadie desactiva su propio rol.

Dónde está: `database/seeders/RolesYPermisosSeeder.php` (con prueba en `tests/Feature/RolesYPermisosTest.php`), las páginas del colaborador en `app/Support/PaginasColaborador.php` y el menú en `resources/js/lib/menu.ts`. Mi perfil: `routes/settings.php` pide `perfil.editar` para cambiar datos, foto o correo y `contrasena.cambiar` para la contraseña.

**Botones según los permisos (26 de octubre de 2026):** cada pantalla oculta los botones y acciones que el rol no permite (`resources/js/composables/usePermisos.ts`). Por ejemplo, con solo "Ver empresas" no aparecen "Registrar empresa", "Asignar medición", "Desactivar cuenta" ni "Ver como", y Datos de la cuenta queda de solo lectura. Sin "Editar" no aparecen crear, editar, archivar ni eliminar en Diagnósticos y el catálogo; sin "Publicar versiones" no aparece "Publicar"; sin "Descargar PDF" no aparece el PDF; sin "Gestionar usuarios y roles" la lista de usuarios y los roles son de solo lectura. El servidor bloquea las rutas igual.

## Reglas que se aplican en el servidor

| # | Regla | Cómo se cumple | Prueba |
|---|---|---|---|
| 1 | **Cada ruta lleva su middleware** (`role:` o `permission:`). El menú oculta lo que no se puede abrir (T-052), pero eso no reemplaza el bloqueo del servidor. | Alias en `bootstrap/app.php` | `PermisosTest` |
| 2 | **La empresa y el colaborador solo ven datos de su empresa** (RN-007), y el Consultor solo las vinculadas (RN-030). Además del permiso, cada consulta se filtra por empresa; otra empresa da 404. | `User::puedeVerEmpresa()`, `deMiEmpresa()` y `puedeVer()` en los controladores de empresas, historial y resultados | `PerfilTest` |
| 3 | **Cuenta desactivada no entra** (RN-004), tampoco las cuentas de una empresa desactivada (RN-025). Si las desactivan con la sesión abierta, la sesión se cierra en la siguiente petición. Con la contraseña correcta, L1 muestra el modal "Su cuenta ha sido desactivada…" (`ModalCuentaDesactivada`). | `User::puedeEntrar()`, `Fortify::authenticateUsing` y el middleware `CerrarSesionCuentaInactiva` | `SeguridadAccesoTest` |
| 4 | **Los avisos no revelan si un correo existe** (RN-005): el login responde "El correo o la contraseña no son correctos." cuando el correo no existe o la contraseña está mal, también en cuentas desactivadas. El aviso de cuenta desactivada solo lo ve quien escribe la contraseña correcta (decisión del equipo, 7 de octubre); "¿Olvidaste tu contraseña?" responde siempre "Si el correo está registrado, te enviamos un enlace…". | `lang/es/auth.php`, `AvisoRecuperacionResponse` | `SeguridadAccesoTest` |
| 5 | **Contraseña fuerte en todos los entornos** (RN-001): mínimo 8 caracteres, una mayúscula, un número y un carácter especial. Se aplica al registrarse, al crear la contraseña nueva y al cambiarla en Mi perfil. Tampoco puede tener partes del correo (de 3 o más caracteres) ni ser igual a la anterior. En Mi perfil tampoco puede repetir 4 caracteres seguidos de la actual; al recuperarla o en los colaboradores no se pueden leer sus partes (está cifrada). En todos los casos la nueva no puede ser ninguna de las últimas 5, sin importar mayúsculas: se guardan en minúsculas cifradas con bcrypt (`contrasenas_anteriores`), nunca en texto. | `Password::defaults` en `AppServiceProvider` + reglas `TieneMayuscula`, `SinDatosDelCorreo` y `DistintaDeLaAnterior` | `SeguridadAccesoTest`, `SinDatosDelCorreoTest`, `ColaboradoresTest` |
| 6 | **Enlaces de contraseña** (RN-006, cambio del 4 de noviembre): el de "¿Olvidaste tu contraseña?" vence a los **10 minutos**; el de una invitación sirve hasta usarlo. Cada uno va en su tabla (`password_reset_tokens` e `invitaciones_tokens`), pedir otro invalida el anterior y guardar la contraseña borra los dos. | `config/auth.php` (brokers `users` e `invitaciones`), `ElegirEnlaceContrasena`, `Invitaciones` | `SeguridadAccesoTest`, `AuditoriaQaTest` |
| 7 | **Límite de intentos:** 5 por minuto en iniciar sesión (por correo e IP; al pasarse, L1 muestra el modal "Demasiados intentos" con la cuenta regresiva y el botón desactivado, `ModalAccesoBloqueado`), registrarse, pedir el enlace y crear la contraseña nueva (por IP). | Limitador `login` de Fortify y middleware `LimitarIntentosAcceso` | `SeguridadAccesoTest`, `AuthenticationTest` |
| 8 | **Un correo, una cuenta** (RN-002); el correo se guarda y se compara en minúsculas y sin espacios. | `NormalizarCorreo` (antes de validar), regla `unique`, `lowercase_usernames` de Fortify e índice único `lower(email)` | `SeguridadAccesoTest`, `AuditoriaQaTest` |
| 9 | **Constancia de los términos:** se guarda cuándo se aceptaron (`users.terminos_aceptados_en`). | `CreateNewUser` | `SeguridadAccesoTest` |
| 10 | **El Administrador no restablece contraseñas de otras cuentas** (RN-006). No existe esa ruta; las cuentas internas se invitan y la persona crea su contraseña. | `UsuariosController::invitar` | `UsuariosYRolesTest` |
| 13 | **Usuarios y roles:** nada sobre la propia cuenta (ni desactivarla ni eliminarla); eliminar pide el correo exacto, no deja sin Administrador al sistema, deja la cuenta 90 días recuperable antes de borrarla para siempre y, si es la cuenta principal, elimina también la empresa y sus colaboradores (DEC-017); los roles del sistema no se editan ni se eliminan; un rol con cuentas no se elimina; los inactivos no se asignan (RN-027). | `UsuariosController`, `RolesController` | `UsuariosYRolesTest` |
| 14 | **Colaboradores (E12):** solo la cuenta principal crea, cambia la contraseña, desactiva o reactiva, y solo a colaboradores de su empresa (otra empresa: 404). Cambiar la contraseña o desactivar cierra las sesiones del colaborador. Es la única pantalla donde una cuenta define la contraseña de otra (RN-025, HU-078). También elige las páginas que ve cada colaborador; el colaborador no cambia su contraseña ni la recupera por correo. | `ColaboradoresController`, `PaginasColaborador`, `User::sendPasswordResetNotification` | `ColaboradoresTest`, `PerfilTest` |
| 11 | **Contraseñas cifradas** con bcrypt (`hashed` en el modelo); nunca se guardan ni se muestran en texto. La excepción es E12.2: muestra una sola vez la contraseña que la empresa acaba de escribir, desde el navegador; el servidor no la devuelve. | `User::casts()` | — |
| 12 | **Protección CSRF y sesión nueva al entrar**, de Laravel e Inertia. | Middleware `web` | — |
| 15 | **Cabeceras de seguridad** en todas las respuestas: `X-Frame-Options: SAMEORIGIN`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, con HTTPS HSTS y una `Content-Security-Policy` (ERR-06). Fuera de desarrollo la CSP es `script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'`: los scripts solo salen del propio sitio (los compila Vite), así un XSS no ejecutaría scripts ajenos ni en línea. En desarrollo solo lleva `frame-ancestors 'self'`, porque Vite recarga en caliente con scripts en línea. | `CabecerasSeguridad` | `AuditoriaQaTest` |
| 15b | **Asignación masiva cerrada:** cada modelo declara sus campos editables con `#[Fillable([...])]`; los campos internos (estados, fechas como `terminada_en` o `archivado_en`) solo se escriben con `forceFill`. Fuera de producción, pasar un campo fuera de la lista a `create`/`update` lanza un error en vez de descartarlo en silencio. | Modelos, `AppServiceProvider` | toda la suite |
| 16 | **Los correos no arman enlaces con los datos:** el nombre de una empresa o un motivo con Markdown se muestra como texto (ERR-03). | `Notifications\Correo::escapar()` | `AuditoriaQaTest` |
| 17 | **Un correo muy largo no rompe el login:** las llaves de los contadores de intentos usan un hash del correo (ERR-01). | `LimitadorLogin` y `LimitarIntentosAcceso` | `AuditoriaQaTest` |
| 18 | **Rol inactivo:** una cuenta activa con su rol inactivo no entra y recibe su propio mensaje (ERR-12). | `FortifyServiceProvider` | `AuditoriaQaTest` |

Todos los mensajes salen en español (`lang/es/`), y el correo de recuperación también (`FortifyServiceProvider::configureResetEmail`).

### Antes de una presentación o de producción

- `APP_DEBUG=false`. Con `true`, un error muestra código y datos de la base, como la pantalla de error de Laravel.
- `APP_ENV=production`. Así no existen las rutas `/prueba-tecnica/*`.
- `INERTIA_DEVTOOLS_ENABLED=false`. Con `local`, `/_inertia/devtools` deja leer las peticiones recientes con sus datos (ERR-07).
- Con HTTPS: `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`.
- Claves (`APP_KEY`, base de datos, correo, OpenAI, Turnstile) solo en `.env`.

**Auditoría QA (4 de noviembre de 2026):** se corrigieron los 14 hallazgos (ERR-01 a ERR-14). Los de seguridad están en las reglas 6, 8 y 15 a 18. Cada uno tiene su prueba en `tests/Feature/AuditoriaQaTest.php` y el detalle está en `24_CAMBIOS_Y_VERSIONES.md`. `composer audit` y `npm audit` quedaron en 0.

**Términos y Condiciones y Política de Privacidad:** son los textos oficiales de Captter, entregados el 4 de noviembre de 2026 y copiados tal cual en `resources/js/lib/legales.ts`. Los Términos son del 16 de septiembre de 2025 y la Política, del 27 de mayo de 2025. Se abren en una ventana desde el registro (L2), y al aceptarlos se guarda la fecha. Contacto en los dos: contacto@captter.com.

**[INCONSISTENCIA DETECTADA]** Los textos oficiales son de la plataforma Captter en general y no coinciden del todo con este sistema de diagnóstico. No se cambian aquí; la empresa decide si los ajusta:

- **Pagos:** los Términos hablan casi solo de pagos con Openpay y PSE, y la Política nombra datos de pago y "proveedores de servicios de pago". Este sistema no cobra ni procesa pagos.
- **Datos que no se piden:** la Política nombra dirección física, NIT, Certificado de Cámara de Comercio, cédula, ubicación del dispositivo, búsquedas y transacciones. El sistema no los pide.
- **Proveedores que no se nombran:** las respuestas se analizan con OpenAI (Estados Unidos) y el registro usa Cloudflare Turnstile. La Ley 1581 pide informar la transmisión de datos a otros países; la Política no la menciona.
- **Cookies:** la Política nombra cookies de analítica y de personalización. El sistema solo usa las necesarias: sesión, protección de formularios y "Recordarme".
- **Uso del sistema:** los Términos no hablan del diagnóstico, de que la IA puede equivocarse, de "Ver como" ni de los plazos de borrado (7 días sin verificar el correo, 90 días para recuperar una cuenta).

## Comprobado en la prueba técnica

- `tests/Feature/PruebaTecnica/PermisosTest.php` comprueba que `/prueba-tecnica/solo-administrador` responde 200 al rol Administrador, 403 al rol Empresa y redirige al login a quien no entró.
- El rol y los permisos de la cuenta se comparten con las pantallas (`auth.rol` y `auth.permisos` en `HandleInertiaRequests`).

## Secretos

- Las claves (OpenAI, correo, base de datos, Turnstile) van solo en `.env`, que no se sube al repositorio (`.gitignore`).
- `.env.example` lleva placeholders y un comentario por variable.
- Antes de entregar se revisa que ninguna clave haya quedado en el código (T-147).

## Verificación del correo (L2b, DEC-020)

- **Captcha:** el registro pide el token de Cloudflare Turnstile y el servidor lo confirma con `siteverify` (`App\Support\Captcha`). Sin token válido, la cuenta no se crea.
- **Código:**
  - es de 6 dígitos y se guarda cifrado (`users.codigo_verificacion`);
  - vence en 15 minutos y admite 5 intentos;
  - pedir otro reemplaza al anterior.
- **A quién se le envía:** solo al correo guardado en la sesión de quien se registró o de quien escribió la contraseña correcta. Nadie puede pedir ni probar códigos de otra cuenta.
- **Límites:** verificar, 10 por minuto; reenviar, 3 por minuto.
- **Inicio de sesión:** sin verificar, Fortify rechaza la entrada con `correo_sin_verificar` y abre la ventana. Solo pasa con la contraseña correcta (RN-005).

