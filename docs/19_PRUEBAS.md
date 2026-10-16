# Pruebas

**Estado:** COMPLETO (T-119). Cifras del 14 de octubre de 2026: **245 pruebas de Pest** (1680 comprobaciones) y **35 de Vitest**. Todas pasan.

## 1. Cómo se corren

| Qué | Comando | Dónde |
|---|---|---|
| Backend (Pest) | `php artisan test` | `tests/Feature`, `tests/Unit` |
| Un archivo o un caso | `php artisan test tests/Feature/ResponderTest.php` o `--filter="Otro"` | — |
| Frontend (Vitest) | `npm test` | `resources/js/lib/*.test.ts` |
| Formato y análisis | `composer lint`, `composer types:check`, `npm run check`, `npm run types:check` | — |
| Todo lo de CI | `composer ci:check` | `.github/workflows/tests.yml` |

En Sail se antepone `./vendor/bin/sail`.

## 2. Cómo están hechas

- **Base de datos real.** Las pruebas usan PostgreSQL (la base `testing`) con `RefreshDatabase`, igual que CI. Así se prueban los JSONB y las consultas tal como corren de verdad.
- **Sin servicios externos.**
  - **IA:** `OpenAI::fake()`, o `AnalizadorDePrueba` con `IA_DE_PRUEBA=true`.
  - **PDF:** `Pdf::fake()` en todas las pruebas (`tests/Pest.php`); Chromium no se abre.
  - **Correo:** `Notification::fake()` y `MAIL_MAILER=array`.
  - **Cola:** `QUEUE_CONNECTION=sync`, o `Queue::fake()` cuando se quiere ver qué se mandó a la cola.
- **Pantallas con Inertia.** Se comprueba el componente y las props que recibe (`assertInertia`), no el HTML.
- **Permisos.** Cada módulo tiene casos de "otro rol no entra" y "otra empresa no ve" (RN-007).
- **Nombres en español.** Cada caso dice lo que comprueba (`it('no deja marcar "Ninguna" junto con otras opciones')`).

## 3. Pruebas del backend por módulo

"Casos" cuenta los bloques `it()`. Algunos corren con varios datos (`->with()`), por eso el total de Pest es mayor que la suma.

| Archivo | Casos | Qué cubre |
|---|---|---|
| `Auth/AuthenticationTest`, `PasswordResetTest`, `RegistrationTest` | 19 | Pruebas del kit de Laravel adaptadas: entrar, salir, recuperar y registrarse |
| `Auth/SeguridadAccesoTest` | 15 | RN-001 a RN-006: contraseña fuerte, cuenta desactivada, avisos que no revelan cuentas, límites de intentos, enlace de contraseña nueva |
| `CatalogoCiiuTest` | 9 | Catálogo CIIU y actividades por sector |
| `DiagnosticosYCatalogoTest` | 13 | A2: sectores (crear, reasignar, desactivar, eliminar), categorías (RN-009), crear, duplicar, archivar y eliminar diagnósticos |
| `EditorDiagnosticoTest` | 16 | A2.1: preguntas, opciones, "Ninguna", "Otro", orden, importancia, estado (RN-012) y publicar una versión congelada |
| `ResultadoEjemploTest` | 5 | HU-030: mejor, medio y peor caso con el mismo cálculo |
| `EmpresasTest` | 11 | A3: lista, registrar con invitación, editar, desactivar y asignar (RN-014) |
| `CorreoAvisoTest` | 9 | A3.1e: plantilla, variables, correo de prueba y recordatorio (HU-008) |
| `SolicitudesMedicionTest` | 7 | HU-083: aviso en A1, la lista, la ficha y Asignar; se atiende al asignar, dar nueva fecha o cancelar |
| `InicioTest` | 7 | A1: indicadores, pestañas, orden de la tabla y vencidas por fecha |
| `ResponderTest` | 9 | E1–E4: inicio, responder, avance, enviar, vencidas, solicitud, "Ninguna" y "Otro" |
| `ResultadosTest` | 9 | Cálculo (RN-018), análisis por categoría, variación, prompt por capas, falla y reintento, permisos de E6/E9 y PDF |
| `HistorialTest` | 2 | E8 y A3.4: terminadas, evolución total y por categoría, pendiente |
| `ConfiguracionIaTest` | 6 | A4: guardar por capas, quitar un ajuste, vista previa, probar y permisos |
| `UsuariosYRolesTest` | 21 | A5: lista, invitar, desactivar, eliminar y recuperar, cambiar rol, roles (RN-027) y medición pendiente de la cuenta principal |
| `RolesYPermisosTest` | 6 | Seeder de roles y permisos y protección de rutas |
| `VerComoTest` | 5 | RN-026: solo lectura, registro, salir y no sobre administradores |
| `ColaboradoresTest` | 14 | E12 y RN-025 |
| `PerfilTest` | 13 | A6 y E11: datos, contraseña, foto y datos de la empresa |
| `AvisosPerfilTest` | 5 | Los avisos por correo respetan Mi perfil; resumen semanal |
| `ModeloDatosTest` | 3 | Relaciones y restricciones del MER |
| `PruebaTecnica/*` | 19 | Permisos, gráficas, PDF y la respuesta de la IA (formato, puntajes, método) |
| `Unit/NivelesTest` | 2 | RN-019 en los límites (29/30, 59/60, 79/80) |

## 4. Pruebas del frontend

| Archivo | Qué cubre |
|---|---|
| `contrasena.test.ts` | Requisitos de RN-001 en vivo |
| `fechas.test.ts` | Fechas en español y "hace N días" |
| `paginacion.test.ts` | Paginación y filas que caben |
| `menu.test.ts` | Menú por rol y permiso |
| `usuarios.test.ts` | Avisos al desactivar o cambiar el rol de una cuenta principal; búsqueda sin tildes |
| `correo.test.ts` | Variables del correo de aviso |
| `ia.test.ts` | Errores de "Probar con un ejemplo" |
| `texto.test.ts` | Utilidades de texto |

Las pantallas no tienen pruebas de componente. Se revisan en el navegador contra el wireframe (definición de terminado, `28_CONVENCIONES_DESARROLLO.md`).

## 5. Pruebas manuales

Antes de cada *pull request* se recorre la pantalla en el navegador a 1440×900 y 1366×768, con estas comprobaciones:

- **Página fija:** no se desplaza en pantallas grandes.
- **Consola limpia:** sin errores en la consola del navegador.
- **SSR:** con `npm run dev`, la página carga sin "document is not defined".
- **Flujo completo de una medición:** asignar → responder → enviar → `queue:work` → resultado → PDF.
- **IA real:** `php artisan prueba:ia` y "Probar con un ejemplo" en A4, con clave. Cada uno gasta una llamada.

## 6. Cobertura de reglas

Cada regla de negocio tiene al menos una prueba, salvo:

- **RN-028:** depende del texto que escribe la IA; no se puede comprobar con una prueba automática.
- **RN-029:** el bot de WhatsApp está aplazado.

El detalle de qué prueba cubre cada regla está en `07_REGLAS_DE_NEGOCIO.md`.

## 7. Pendiente

- **[FUNCIONALIDAD POR DEFINIR]** Pruebas de navegador automáticas (por ejemplo, Playwright) para los flujos completos. Hoy se hacen a mano.
- **[INFORMACIÓN PENDIENTE]** Pruebas de aceptación con NuevasTIC: quién las hace y con qué empresas.
