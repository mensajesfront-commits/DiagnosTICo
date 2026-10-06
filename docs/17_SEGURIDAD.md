# Seguridad

**Estado:** EN CURSO. La matriz de roles y permisos ya sigue el wireframe A5.2 (T-006, 6 de octubre).

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

1. **Cada ruta lleva su middleware** (`role:` o `permission:`). Los alias están registrados en `bootstrap/app.php`. El menú lateral oculta lo que no se puede abrir (T-052), pero eso no reemplaza el bloqueo del servidor.
2. **La empresa y el colaborador solo ven datos de su propia empresa** (RN-007). Además del permiso, cada consulta se filtra por `empresa_id`. Lo harán las *policies* de Laravel en las semanas 3 a 6.
3. **Cuenta desactivada:** no puede iniciar sesión y sus datos se conservan (RN-004).
4. **Los avisos no revelan si un correo existe** en el inicio de sesión ni en la recuperación de la contraseña (RN-005).
5. **Contraseña fuerte:** mínimo 8 caracteres, una mayúscula, un número y un carácter especial (RN-001). **[INCONSISTENCIA DETECTADA]** Los wireframes L2 y L4 muestran solo 4 requisitos y no incluyen el carácter especial. Hay que decidir si se agrega a la pantalla o si se quita de la regla.
6. **El Administrador no restablece contraseñas de otras cuentas** (RN-006). El documento "Tecnologías del sistema" todavía menciona "restablecer contraseña (A5.3)". El cronograma ya lo descartó (T-012, T-123).

## Comprobado en la prueba técnica

- `tests/Feature/PruebaTecnica/PermisosTest.php` comprueba que `/prueba-tecnica/solo-administrador` responde 200 al rol Administrador, 403 al rol Empresa y redirige al login a quien no entró.
- El rol y los permisos de la cuenta se comparten con las pantallas (`auth.rol` y `auth.permisos` en `HandleInertiaRequests`).

## Secretos

- Las claves (OpenAI, correo, base de datos) van solo en `.env`, que no se sube al repositorio.
- `.env.example` lleva placeholders y un comentario por variable.
- Antes de entregar se revisa que ninguna clave haya quedado en el código (T-147).
