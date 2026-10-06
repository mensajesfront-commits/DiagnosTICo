# Seguridad

**Estado:** EN CURSO. La matriz de roles y permisos es una PROPUESTA que REQUIERE VALIDACIÓN (T-006).

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

El Administrador puede crear roles nuevos con los permisos que elija (HU-049). Consultor y Usuario son roles posibles a futuro y no se crean ahora.

**[INCONSISTENCIA DETECTADA]** El prototipo usa "Consultor" como ejemplo de rol creado (A5.1c). Ver PA-006.

## Permisos

Según HU-049, el modal "Crear rol" muestra **15 permisos en 6 bloques**. Los nombres exactos salen del wireframe A5.2, que no está en este repositorio.

**[INFORMACIÓN PENDIENTE]** Hay que comparar esta lista con A5.2 y ajustar los textos. Los nombres técnicos que siguen son una **propuesta**. El menú lateral ya usa algunos (`resources/js/lib/menu.ts`).

| Bloque | Permiso (nombre técnico) | Qué permite | Pantallas |
|---|---|---|---|
| 1. Inicio | `inicio.ver` | Ver los indicadores y las mediciones de todas las empresas | A1, A1b–A1e |
| 2. Diagnósticos | `diagnosticos.ver` | Ver sectores, catálogo y diagnósticos | A2, A2·T, A2.3, A2.4, A2.6 |
| | `diagnosticos.editar` | Crear y editar sectores, categorías, diagnósticos, preguntas e importancia; duplicar y archivar | A2.1–A2.3c, A2.5, A2.7 |
| | `diagnosticos.publicar` | Publicar una versión | A2.1c |
| 3. Empresas y mediciones | `empresas.ver` | Ver empresas, su ficha, sus resultados, su historial y sus respuestas | A3, A3.1, A3.3–A3.5 |
| | `empresas.gestionar` | Registrar empresas, editar sus datos, desactivar y reactivar | A3.1d, A3.1f, A3.2 |
| | `mediciones.asignar` | Asignar mediciones, dar nueva fecha, enviar recordatorios, editar el correo y atender solicitudes | A1b–A1d, A3.1b, A3.1e |
| 4. Configuración IA | `ia.configurar` | Editar prompts y ajustes por sector o empresa; ver el prompt completo y probarlo | A4–A4.9 |
| 5. Usuarios y roles | `usuarios.gestionar` | Ver cuentas, desactivar y reactivar | A5, A5.3b |
| | `roles.gestionar` | Crear, editar, eliminar, asignar y quitar roles | A5.1–A5.2, A5.5 |
| | `usuarios.ver-como` | Usar "Ver como" (solo lectura, queda registrado, RN-026) | A5.4, A5.4b |
| 6. Empresa | `diagnostico.responder` | Responder, revisar y enviar el diagnóstico de su empresa | E2–E5 |
| | `resultados.ver` | Ver el resultado, el historial, las respuestas y el PDF de su empresa | E6–E10 |
| | `colaboradores.gestionar` | Crear colaboradores, cambiarles la contraseña, desactivarlos y reactivarlos | E12–E12.3 |
| | `empresa.editar` | Cambiar los datos de la empresa en el perfil | E11 (parte de empresa) |

Para todas las cuentas, sin necesidad de un permiso: iniciar y cerrar sesión, recuperar la contraseña y editar los datos propios y la contraseña propia (A6, E11).

## Matriz por rol

| Permiso | Administrador | Empresa | Colaborador |
|---|:---:|:---:|:---:|
| `inicio.ver` | ✓ | | |
| `diagnosticos.ver` | ✓ | | |
| `diagnosticos.editar` | ✓ | | |
| `diagnosticos.publicar` | ✓ | | |
| `empresas.ver` | ✓ | | |
| `empresas.gestionar` | ✓ | | |
| `mediciones.asignar` | ✓ | | |
| `ia.configurar` | ✓ | | |
| `usuarios.gestionar` | ✓ | | |
| `roles.gestionar` | ✓ | | |
| `usuarios.ver-como` | ✓ | | |
| `diagnostico.responder` | | ✓ | ✓ |
| `resultados.ver` | | ✓ | ✓ |
| `colaboradores.gestionar` | | ✓ | |
| `empresa.editar` | | ✓ | |

El colaborador tiene casi los mismos permisos que la empresa. No puede crear ni desactivar colaboradores ni cambiar los datos de la empresa (RN-025).

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
