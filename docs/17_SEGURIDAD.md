# Seguridad

**Estado:** EN CURSO. La matriz de roles y permisos sigue el wireframe A5.2 (T-006). La seguridad del acceso (L1–L4) está completa y probada (6 de octubre).

Más adelante se completan:

- login, manejo de secretos y límite de intentos (T-065);
- "Ver como" y desactivación de cuentas (T-138).

## Roles

Hay tres roles del sistema. Son fijos: no se editan ni se eliminan, y cada cuenta tiene un solo rol (RN-027).

| Rol | Quién es | Cómo se crea |
|---|---|---|
| Administrador | Persona de NuevasTIC | Seeder inicial (T-046); después, desde Usuarios y roles (A5) |
| Empresa | Usuario principal de una empresa cliente | Registro propio (L2) o por el Administrador (A3.2) |
| Colaborador | Persona de la empresa con su propia cuenta | La crea la empresa (E12.1); la empresa define el correo y la contraseña (RN-025) |

El Administrador puede crear roles nuevos con los permisos que elija (HU-049); cada uno tiene nombre, descripción opcional y si está activo. Un rol con cuentas no se elimina (RN-027).

**Rol Consultor (PA-006, respondida el 6 de octubre):** existe pero está **inactivo** hasta que se le asigne a alguien; eso se probará más adelante. El seeder lo crea inactivo, con los 6 permisos de A5.2 (ver empresas, solo las vinculadas; responder; ver resultados; PDF; editar su perfil; cambiar su contraseña). No es del sistema: el Administrador lo puede editar o eliminar, y el seeder no pisa esos cambios.

Cada rol guarda `descripcion`, `activo` y `del_sistema` (columnas agregadas a la tabla `roles` de spatie). Un rol inactivo no se puede asignar.

## Permisos

Son los **15 permisos en 6 bloques** del wireframe A5.2 (A5.1 "Crear rol" usa la misma lista). La columna "Texto en pantalla" es lo que muestra la casilla.

| Bloque | Permiso (nombre técnico) | Texto en pantalla | Qué permite | Pantallas |
|---|---|---|---|---|
| Diagnósticos | `diagnosticos.ver` | Ver diagnósticos | Ver sectores, catálogo, diagnósticos y vista previa | A2, A2·T, A2.3, A2.4, A2.6 |
| | `diagnosticos.editar` | Editar preguntas e importancia | Crear y editar sectores, categorías, diagnósticos, preguntas e importancia; duplicar y archivar | A2.1–A2.3c, A2.5, A2.7 |
| | `diagnosticos.publicar` | Publicar versiones | Publicar una versión | A2.1c |
| Empresas | `empresas.ver` | Ver empresas | Ver empresas y su ficha | A3, A3.1 |
| | `empresas.registrar` | Registrar empresas | Registrar empresas, editar sus datos, desactivar y reactivar | A3.1d, A3.1f, A3.2 |
| | `mediciones.asignar` | Asignar mediciones | Asignar mediciones, dar nueva fecha, recordatorios, correo de aviso y solicitudes | A1b–A1d, A3.1b, A3.1e |
| Mediciones y resultados | `diagnostico.responder` | Responder el diagnóstico | Responder, revisar y enviar el diagnóstico | E2–E5 |
| | `resultados.ver` | Ver resultados y respuestas | Ver resultados, historial y respuestas | A3.3–A3.5, E6–E10 |
| | `resultados.pdf` | Descargar PDF | Descargar el informe PDF | E6, A3.3 |
| Configuración IA | `ia.ver` | Ver configuración | Ver prompts, ajustes y el prompt final | A4 |
| | `ia.editar` | Editar prompts | Editar prompts y ajustes por sector o empresa; probarlos | A4–A4.9 |
| Usuarios y roles | `usuarios.ver` | Ver usuarios | Ver cuentas y roles | A5 |
| | `usuarios.gestionar` | Gestionar usuarios y roles | Invitar, desactivar, reactivar, cambiar rol, crear y editar roles, "Ver como" | A5.1–A5.5 |
| Su cuenta | `perfil.editar` | Editar su perfil | Cambiar sus datos personales. En el rol Empresa, también los de la empresa | A6, E11 |
| | `contrasena.cambiar` | Cambiar su contraseña | Cambiar su contraseña | A6, E11 |

En la Empresa y el Colaborador, los permisos de "Mediciones y resultados" valen **solo para su empresa** (RN-007).

Lo que no tiene casilla en A5.2 se decide por el rol:

- **Inicio del Administrador (A1):** cualquier rol interno (que no sea Empresa ni Colaborador). Lo que muestra depende de sus permisos.
- **Colaboradores (E12):** solo el rol Empresa (RN-025, HU-076).
- **"Ver como" (A5.4):** lo usa quien tiene `usuarios.gestionar`, en solo lectura y con registro (RN-026). Sirve para **comprobar que cada rol ve solo las pantallas que se le asignaron**, así que se usa sobre cuentas de Empresa, Colaborador, Consultor y cualquier rol creado, **no sobre cuentas de Administrador** (respuesta del equipo, 6 de octubre). **[INCONSISTENCIA DETECTADA]** El wireframe A5.4b muestra "Ver como" sobre un Administrador; queda fuera según esta respuesta.

Para todas las cuentas, sin permiso: iniciar y cerrar sesión y recuperar la contraseña.

## Matriz por rol

Los roles del sistema tienen permisos fijos (RN-027). Así los muestra A5.2:

| Permiso | Administrador | Empresa | Colaborador |
|---|:---:|:---:|:---:|
| `diagnosticos.ver` | ✓ | | |
| `diagnosticos.editar` | ✓ | | |
| `diagnosticos.publicar` | ✓ | | |
| `empresas.ver` | ✓ | | |
| `empresas.registrar` | ✓ | | |
| `mediciones.asignar` | ✓ | | |
| `diagnostico.responder` | ✓ | ✓ | ✓ |
| `resultados.ver` | ✓ | ✓ | ✓ |
| `resultados.pdf` | ✓ | ✓ | ✓ |
| `ia.ver` | ✓ | | |
| `ia.editar` | ✓ | | |
| `usuarios.ver` | ✓ | | |
| `usuarios.gestionar` | ✓ | | |
| `perfil.editar` | ✓ | ✓ | ✓ |
| `contrasena.cambiar` | ✓ | ✓ | ✓ |
| **Total** | **15** | **5** | **5** |

- El Administrador tiene los 15, también "Responder el diagnóstico", aunque no responde por ninguna empresa (no tiene `empresa_id`).
- El colaborador **sí edita su propio perfil** (su nombre, su teléfono…), respuesta del equipo del 6 de octubre. Lo que no puede es cambiar los datos de la empresa (RN-025): eso lo decide el rol Empresa, no el permiso. **[INCONSISTENCIA DETECTADA]** A5.2 muestra al Colaborador con 4 permisos, sin "Editar su perfil"; manda la respuesta del equipo.

Dónde está: `database/seeders/RolesYPermisosSeeder.php` (con prueba en `tests/Feature/RolesYPermisosTest.php`) y el menú en `resources/js/lib/menu.ts`.

## Reglas que se aplican en el servidor

| # | Regla | Cómo se cumple | Prueba |
|---|---|---|---|
| 1 | **Cada ruta lleva su middleware** (`role:` o `permission:`). El menú oculta lo que no se puede abrir (T-052), pero eso no reemplaza el bloqueo del servidor. | Alias en `bootstrap/app.php` | `PermisosTest` |
| 2 | **La empresa y el colaborador solo ven datos de su empresa** (RN-007). Además del permiso, cada consulta se filtra por `empresa_id`. | Mi perfil y sus imágenes ya lo hacen; el resto, con *policies* al crear cada módulo | `PerfilTest` |
| 3 | **Cuenta desactivada no entra** (RN-004), tampoco las cuentas de una empresa desactivada (RN-025). Si las desactivan con la sesión abierta, la sesión se cierra en la siguiente petición. Con la contraseña correcta, L1 muestra el modal "Su cuenta ha sido desactivada…" (`ModalCuentaDesactivada`). | `User::puedeEntrar()`, `Fortify::authenticateUsing` y el middleware `CerrarSesionCuentaInactiva` | `SeguridadAccesoTest` |
| 4 | **Los avisos no revelan si un correo existe** (RN-005): el login responde "El correo o la contraseña no son correctos." cuando el correo no existe o la contraseña está mal, también en cuentas desactivadas. El aviso de cuenta desactivada solo lo ve quien escribe la contraseña correcta (decisión del equipo, 7 de octubre); "¿Olvidaste tu contraseña?" responde siempre "Si el correo está registrado, te enviamos un enlace…". | `lang/es/auth.php`, `AvisoRecuperacionResponse` | `SeguridadAccesoTest` |
| 5 | **Contraseña fuerte en todos los entornos** (RN-001): mínimo 8 caracteres, una mayúscula, un número y un carácter especial. Se aplica al registrarse, al crear la contraseña nueva y al cambiarla en Mi perfil. | `Password::defaults` en `AppServiceProvider` + regla `TieneMayuscula` | `SeguridadAccesoTest` |
| 6 | **Enlace de contraseña nueva sin vencimiento por tiempo** (RN-006): sirve hasta guardar la contraseña; pedir otro invalida el anterior. | `config/auth.php` (`expire` de un año) | `SeguridadAccesoTest` |
| 7 | **Límite de intentos:** 5 por minuto en iniciar sesión (por correo e IP; al pasarse, L1 muestra el modal "Demasiados intentos" con la cuenta regresiva y el botón desactivado, `ModalAccesoBloqueado`), registrarse, pedir el enlace y crear la contraseña nueva (por IP). | Limitador `login` de Fortify y middleware `LimitarIntentosAcceso` | `SeguridadAccesoTest`, `AuthenticationTest` |
| 8 | **Un correo, una cuenta** (RN-002); el correo se guarda y se compara en minúsculas. | Regla `unique` y `lowercase_usernames` de Fortify | `SeguridadAccesoTest` |
| 9 | **Constancia de los términos:** se guarda cuándo se aceptaron (`users.terminos_aceptados_en`). | `CreateNewUser` | `SeguridadAccesoTest` |
| 10 | **El Administrador no restablece contraseñas de otras cuentas** (RN-006). No existe esa ruta; las cuentas internas se invitan y la persona crea su contraseña. | `UsuariosController::invitar` | `UsuariosYRolesTest` |
| 13 | **Usuarios y roles:** nada sobre la propia cuenta; los roles del sistema no se editan ni se eliminan; un rol con cuentas no se elimina; los inactivos no se asignan (RN-027). | `UsuariosController`, `RolesController` | `UsuariosYRolesTest` |
| 14 | **Colaboradores (E12):** solo la cuenta principal crea, cambia la contraseña, desactiva o reactiva, y solo a colaboradores de su empresa (otra empresa: 404). Cambiar la contraseña o desactivar cierra las sesiones del colaborador. Es la única pantalla donde una cuenta define la contraseña de otra (RN-025, HU-078). | `ColaboradoresController` | `ColaboradoresTest` |
| 11 | **Contraseñas cifradas** con bcrypt (`hashed` en el modelo); nunca se guardan ni se muestran en texto. La excepción es E12.2: muestra una sola vez la contraseña que la empresa acaba de escribir, desde el navegador; el servidor no la devuelve. | `User::casts()` | — |
| 12 | **Protección CSRF y sesión nueva al entrar**, de Laravel e Inertia. | Middleware `web` | — |

Todos los mensajes salen en español (`lang/es/`), y el correo de recuperación también (`FortifyServiceProvider::configureResetEmail`).

### Antes de una presentación o de producción

- `APP_DEBUG=false`. Con `true`, un error muestra código y datos de la base, como la pantalla de error de Laravel.
- `APP_ENV=production`. Así no existen las rutas `/prueba-tecnica/*` y el `DemoSeeder` no corre.
- Con HTTPS: `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`.
- Claves (`APP_KEY`, base de datos, correo, OpenAI) solo en `.env`.

**[INFORMACIÓN PENDIENTE]** Los términos de uso y la política de tratamiento de datos no tienen URL todavía (L2 apunta a `#`).

## Comprobado en la prueba técnica

- `tests/Feature/PruebaTecnica/PermisosTest.php` comprueba que `/prueba-tecnica/solo-administrador` responde 200 al rol Administrador, 403 al rol Empresa y redirige al login a quien no entró.
- El rol y los permisos de la cuenta se comparten con las pantallas (`auth.rol` y `auth.permisos` en `HandleInertiaRequests`).

## Secretos

- Las claves (OpenAI, correo, base de datos) van solo en `.env`, que no se sube al repositorio.
- `.env.example` lleva placeholders y un comentario por variable.
- Antes de entregar se revisa que ninguna clave haya quedado en el código (T-147).
