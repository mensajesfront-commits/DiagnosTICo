# Reglas de negocio

**Estado:** REQUIERE VALIDACIÓN (T-086). Las reglas salen de `05_REQUISITOS_FUNCIONALES.md`; si algo no coincide, manda ese documento. NuevasTIC debe confirmar las marcadas al final.

Cada regla dice **qué exige**, **dónde se cumple en el servidor** (la validación que no se puede saltar) y **dónde se ve en pantalla**. La interfaz ayuda, pero la regla vale aunque alguien llame a la ruta directamente (`17_SEGURIDAD.md`).

## Acceso y cuentas

| Regla | Qué exige | Se cumple en | Se ve en | Pruebas |
|---|---|---|---|---|
| RN-001 | Contraseña de mínimo 8 caracteres, con una mayúscula, un número y un carácter especial. | `Password::defaults()` en `AppServiceProvider` y la regla `TieneMayuscula` | L2, L4, A6, E11, E12 (lista de requisitos en vivo, `lib/contrasena.ts`) | `SeguridadAccesoTest`, `contrasena.test.ts` |
| RN-002 | Un correo pertenece a una sola cuenta. | `unique:users,email` en `CreateNewUser`, `DatosEmpresaRequest`, colaboradores e invitaciones | L2, A3.2, A3.1d, E12 | `SeguridadAccesoTest` |
| RN-003 | Al registrar una empresa solo se ofrecen sectores activos; no hay opción "Otro". | `Sector::activos()` y la validación `exists` con `activo = true` | L2, A3.2 | `EmpresasTest` |
| RN-004 | Una cuenta desactivada no entra y no pierde datos; se reactiva. Una cuenta eliminada se recupera durante 90 días y después se borra (DEC-017). | `FortifyServiceProvider` (login), middleware `CerrarSesionCuentaInactiva`, `SoftDeletes` y la tarea `cuentas:purgar` | L1 (modal de cuenta desactivada), A5, A3.1, E12 | `SeguridadAccesoTest`, `UsuariosYRolesTest`, `ColaboradoresTest` |
| RN-005 | Los avisos de inicio de sesión y recuperación no dicen si un correo existe. | `FortifyServiceProvider` y `AvisoRecuperacionResponse` (mismo mensaje en ambos casos) | L1, L3 | `SeguridadAccesoTest` |
| RN-006 | El enlace de contraseña nueva no vence por tiempo y sirve una vez. El Administrador no cambia contraseñas de otras cuentas. | `Invitaciones` (envía el enlace de contraseña nueva) y la ausencia de rutas para cambiar contraseñas ajenas | L4, A5 (invitar), A3.2 | `SeguridadAccesoTest`, `UsuariosYRolesTest` |
| RN-007 | Cada rol ve solo sus secciones; la empresa y sus colaboradores ven solo su empresa. | Middleware `permission:` y `role:` en `routes/web.php`; `deMiEmpresa()` y `puedeVer()` en los controladores | Menú lateral (`lib/menu.ts`) | `RolesYPermisosTest`, `ResponderTest`, `ResultadosTest`, `HistorialTest` |

## Sectores, categorías y diagnósticos

| Regla | Qué exige | Se cumple en | Se ve en | Pruebas |
|---|---|---|---|---|
| RN-008 | Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva. | `SectoresController::destroy` | A2.2c, A2.2d, A2.2e | `DiagnosticosYCatalogoTest` |
| RN-009 | Nombre de categoría obligatorio, de máximo 40 caracteres y único. Una categoría con respuestas se archiva en lugar de borrarse. | `CategoriasController` | A2.3, A2.3c | `DiagnosticosYCatalogoTest` |
| RN-010 | Un diagnóstico nace como borrador v1. Una versión publicada no cambia y cada resultado guarda la versión con la que se hizo. No hay dos diagnósticos con el mismo nombre en un sector. | `ContenidoDiagnostico::congelar()` guarda la versión en JSONB (`versiones_diagnostico.contenido`); las mediciones apuntan a esa versión | A2, A2.1, A2.5 | `EditorDiagnosticoTest`, `DiagnosticosYCatalogoTest` |
| RN-011 | La importancia de las categorías de un diagnóstico suma exactamente 100 %. | `EstadoDiagnostico` (no deja publicar) y `ContenidoDiagnostico::repartir()` | A2.1c | `EditorDiagnosticoTest` |
| RN-012 | Para publicar: cada categoría con al menos una pregunta, cada opción con puntaje, importancia en 100 %, toda pregunta con indicación y las selecciones múltiples capaces de llegar a 100. | `EstadoDiagnostico::calcular()` y `EditorDiagnosticoController::publicar` | A2.1 (lista de problemas), A2.4 | `EditorDiagnosticoTest` |
| RN-013 | Tipos de pregunta: abierta, opción única o selección múltiple. Opciones de 0 a 100. "Ten en cuenta" (máx. 200) no lo ve la empresa. La abierta tiene un criterio y hasta 1000 caracteres. | Validación de `EditorDiagnosticoController` y `ResponderController::guardar`; `DatosResponder` no envía puntajes ni "Ten en cuenta" | A2.1b, E2 | `EditorDiagnosticoTest`, `ResponderTest` |

## Mediciones

| Regla | Qué exige | Se cumple en | Se ve en | Pruebas |
|---|---|---|---|---|
| RN-014 | Una sola medición pendiente por empresa; solo se asignan diagnósticos publicados de su sector. | `AsignarMedicion` (pide `reemplazar` si ya hay una) | A3.1b, A3.1c, A3.1 | `EmpresasTest` |
| RN-015 | Estados: No iniciada, En curso, Enviada, Terminada y Vencida. | Columna `mediciones.estado`, `DatosEmpresas::estadoVisible()` y `DatosInicio` | `EtiquetaEstado` en A1, A3, E1, E8 | `InicioTest` |
| RN-016 | Una medición vencida no se responde. La empresa pide una nueva (una solicitud abierta a la vez) y el Administrador la atiende. | `DatosResponder::respondible()`, `ResponderController::solicitar`, `SolicitudMedicion::atender()` | E1, A1, A3, A3.1, A3.1b | `ResponderTest`, `SolicitudesMedicionTest` |
| RN-017 | Después de enviar no se cambian las respuestas. | `DatosResponder::respondible()` rechaza guardar si la medición no está no iniciada o en curso | E3 (confirmación), E9 (solo lectura) | `ResponderTest` |

## Cálculo, IA y resultado

| Regla | Qué exige | Se cumple en | Se ve en | Pruebas |
|---|---|---|---|---|
| RN-018 | Única: puntaje de la opción. Múltiple: suma con tope 100. Abierta: 0–100 de la IA. Categoría: promedio de sus preguntas. Total: promedio ponderado por la importancia. | `CalculadoraPuntajes` y `PublicarResultado::calcular()` | E6, A3.3, PDF | `ResultadosTest`, `AnalisisIaTest` |
| RN-019 | Niveles fijos: Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino 60–79, Sigue así 80–100. | `App\Support\Niveles` (servidor) y `lib/niveles.ts` (solo colores y nombres) | `EtiquetaNivel`, gráficas, PDF | `NivelesTest` |
| RN-020 | La variación se calcula contra la medición anterior de la misma empresa y existe desde la 2.ª. | `PublicarResultado::anterior()` y `DatosEmpresas::ultimoResultado()` | E1, E6, E8, A3, A3.1 | `ResultadosTest`, `HistorialTest` |
| RN-021 | La IA analiza una categoría por vez, en una sola etapa. Si falla, se reintenta solo esa categoría hasta 3 veces y luego se avisa al Administrador. | `AnalizarCategoria` (`$tries = 3`, `failed()` → `AnalisisFallido`) | E4 ("Reintentar") | `ResultadosTest` |
| RN-022 | El prompt se arma por capas: general, sector, empresa, preguntas con respuestas y formato fijo. Lo más específico manda y los cambios valen para las mediciones siguientes. | `ArmadorPrompt` | A4, A4 "Cómo se arma" | `ConfiguracionIaTest`, `AnalisisIaTest` |
| RN-023 | El resultado se publica sin revisión y no cambia. | `PublicarResultado` crea el resultado una vez; no hay rutas para editarlo | E6, A3.3 | `ResultadosTest` |
| RN-024 | PDF de 3 páginas, generado una vez al terminar y guardado. No vuelve a llamar a la IA. | `GenerarInformePdf` e `InformePdf`; `ResultadosController::pdf` lo regenera desde el resultado guardado si falta | E6, E8, A3.3 | `ResultadosTest` (con `Pdf::fake()`) |
| RN-028 | El resultado no incluye FODA ni comparación con promedios. | Esquema de respuesta de `AnalizadorDeCategoria` | E6, PDF | — |

## Colaboradores, roles y "Ver como"

| Regla | Qué exige | Se cumple en | Se ve en | Pruebas |
|---|---|---|---|---|
| RN-025 | Colaboradores sin límite, con casi los permisos de la empresa (no crean colaboradores ni cambian los datos de la empresa). Se desactivan con la empresa. Ningún colaborador puede tener el cargo del dueño (cuenta principal), comparado sin mayúsculas, tildes ni espacios de más. | Rol `Colaborador` del seeder, `role:Empresa` en las rutas de E12 y `CerrarSesionCuentaInactiva`; regla `CargoDelDueno` | E12, E11 | `ColaboradoresTest`, `PerfilTest` |
| RN-026 | "Ver como" es solo de lectura y queda registrado. | Middleware `VerComo` (bloquea todo lo que no sea GET) y tabla `registros_ver_como` | Franja de aviso (`FranjaVerComo`) | `VerComoTest` |
| RN-027 | Los roles Administrador, Empresa y Colaborador son fijos; cada cuenta tiene un rol; un rol solo se elimina si no tiene cuentas. | `RolesController` y `UsuariosController::cambiarRol` | A5, A5.2–A5.5 | `UsuariosYRolesTest` |

## WhatsApp (aplazado)

| Regla | Qué exige | Estado |
|---|---|---|
| RN-029 | El diagnóstico por WhatsApp va separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas. | No se construyó: el bot está aplazado (`03_ALCANCE.md`). |

## Decisiones del equipo que amplían las reglas

- **Solicitud de nueva medición (HU-083):** se atiende asignando otra medición, dándole nueva fecha a la vencida o cancelándola. Las tres cierran la solicitud.
- **Opción "Otro" (E2):** una opción por pregunta puede pedir que la empresa escriba cuál. Lo que escribe va a la IA como "Otro: …"; el puntaje es el de la opción.
- **Métodos de análisis (DEC-019):** la IA elige por categoría PESTEL, Porter, VRIO, DOFA o CAME según las respuestas, y el resultado muestra cuál usó.

## Por confirmar con NuevasTIC

- **[INCONSISTENCIA DETECTADA]** RN-028 dice "sin FODA", pero DEC-019 deja que la IA analice con DOFA (y otros métodos) y lo muestre. El equipo lo dejó así por pedido de Cristian. NuevasTIC debe decidir si se mantiene.
- **[INFORMACIÓN PENDIENTE]** Las frases de cada nivel (`Niveles::FRASES`) las escribió el equipo.
- **[INFORMACIÓN PENDIENTE]** Una pregunta opcional sin responder no cuenta en el promedio de su categoría. Falta confirmar que así lo quiere NuevasTIC.
- **[INFORMACIÓN PENDIENTE]** El límite por día de "Probar con un ejemplo" en A4 (hoy solo hay límite por minuto).
