# Diseño técnico

**Estado:** COMPLETO (T-071). Describe el código tal como quedó el 14 de octubre de 2026.

`10_ARQUITECTURA.md` explica las capas y el mapa de carpetas. Aquí se baja un nivel: qué clase hace qué, cómo se arman los datos de cada pantalla y cómo funcionan los procesos con más lógica (publicar, responder, analizar y calcular).

## 1. Patrón de cada pantalla

Todas las pantallas siguen el mismo camino:

```
Ruta (routes/web.php, con su permiso)
  └─ Controlador (app/Http/Controllers)        valida y autoriza
       ├─ Servicio (app/Services/…)             reglas que cambian datos
       └─ Datos* (app/Support/Datos*.php)       arma las props de la pantalla
            └─ Inertia::render('carpeta/Pagina', props)
                 └─ resources/js/pages/carpeta/Pagina.vue   solo muestra
```

- **Controladores delgados.** Validan con `$request->validate()` o un Form Request (`app/Http/Requests`), comprueban que el registro sea de la empresa de quien pide y delegan.
- **`Datos*` arma las props.** Cada clase devuelve arreglos con la forma exacta del tipo de TypeScript de la pantalla (`resources/js/types/*.ts`). Los puntajes, niveles, variaciones y avances salen ya calculados (regla de reparto de `10_ARQUITECTURA.md`).
- **Servicios para las reglas.** Lo que cambia datos con varias reglas va en `app/Services` y se prueba solo: `AsignarMedicion`, `ContenidoDiagnostico`, `PublicarResultado`.
- **Respuestas.** Después de guardar, el controlador redirige con un aviso (`Inertia::flash('toast', …)`). Las acciones que no cambian de página (guardar una respuesta, la vista previa del prompt) responden JSON.

| Clase de datos | Pantallas | Tipo de TypeScript |
|---|---|---|
| `DatosInicio` | A1 | `types/inicio.ts` |
| `DatosInicioEmpresa` | E1 | `types/inicioEmpresa.ts` |
| `DatosDiagnosticos` | A2, A2·T, A2b, A2.3, A2.5 | `types/diagnosticos.ts` |
| `DatosEditor` | A2.1, A2.1c, A2.4 | `types/editor.ts` |
| `DatosEmpresas` | A3, A3.1, A3.2, A3.1b | `types/empresas.ts` |
| `DatosHistorial` | E8, A3.4 | `types/historial.ts` |
| `DatosResponder` | E2, E3, E4 | `types/responder.ts` |
| `DatosResultado` | E6, A3.3, E9, A3.5, PDF | `types/resultados.ts` |
| `DatosConfiguracionIa` | A4, A4.9 | `types/configuracionIa.ts` |
| `DatosUsuarios` | A5, A5.1 | `types/usuarios.ts` |

## 2. Clases por módulo

### Acceso y cuentas

| Clase | Responsabilidad |
|---|---|
| `FortifyServiceProvider` | Inicio de sesión con el aviso que no revela si el correo existe (RN-005), cuenta desactivada (RN-004), límite de intentos y vistas L1–L4. |
| `Actions\Fortify\CreateNewUser` | Registro de la empresa (L2): crea la empresa y su cuenta principal en una transacción. |
| `Support\Invitaciones` | Envía el enlace para crear la contraseña (RN-006). |
| `Middleware\CerrarSesionCuentaInactiva` | Cierra la sesión si desactivaron la cuenta o su empresa. |
| `Middleware\LimitarIntentosAcceso` | 5 intentos por minuto en L2, L3 y L4. |
| `Console\Commands\PurgarCuentas` | Borra para siempre las cuentas eliminadas hace más de 90 días (DEC-017). |

### Diagnósticos

| Clase | Responsabilidad |
|---|---|
| `Services\Diagnosticos\ContenidoDiagnostico` | Convierte un diagnóstico en un arreglo (`congelar`), lo aplica a otro (`aplicar`, `copiar` para duplicar) y reparte la importancia (`repartir`). |
| `Support\EstadoDiagnostico` | Calcula qué le falta para publicar (RN-012): categorías sin preguntas, preguntas sin indicación, importancia que no suma 100 y múltiples que no llegan a 100. |
| `EditorDiagnosticoController` | Preguntas, opciones, orden, importancia y publicar. Al publicar crea la versión congelada. |
| `Services\Resultados\ResultadoEjemplo` | Resultado de ejemplo (HU-030) con el mismo cálculo que uno real. |

### Mediciones

| Clase | Responsabilidad |
|---|---|
| `Services\Mediciones\AsignarMedicion` | Asigna una medición (RN-014): reemplaza la pendiente si se confirma, numera, guarda el correo y atiende la solicitud abierta (HU-083). |
| `Services\Mediciones\EnviarAviso` | Envía el correo de aviso o recordatorio y guarda cuándo salió. |
| `Support\CorreoAviso` | Plantilla del correo y sus variables. |
| `Models\SolicitudMedicion` | Solicitudes de nueva medición: `abiertasDe()` y `atender()`. |
| `Console\Commands\VencerMediciones` | Marca como vencidas las mediciones que pasaron su fecha (HU-070). |

### Responder

| Clase | Responsabilidad |
|---|---|
| `Support\DatosResponder` | Cuestionario de la versión congelada sin puntajes ni "Ten en cuenta" (RN-013), respuestas y avance. `respondible()` decide si se puede guardar (RN-016, RN-017). |
| `ResponderController` | Guarda cada respuesta (JSON), envía, reintenta una categoría fallida y crea la solicitud de nueva medición (HU-082). |

### IA y resultado

| Clase | Responsabilidad |
|---|---|
| `Jobs\AnalizarMedicion` | Calcula las preguntas cerradas y manda una tarea por categoría a la cola. |
| `Jobs\AnalizarCategoria` | Arma el prompt, llama al analizador y guarda la respuesta. Tres intentos (15 s y 60 s de espera); después queda fallida y avisa (`AnalisisFallido`). |
| `Services\Ia\ArmadorPrompt` | Arma el prompt por capas (RN-022) con las variables `{sector}`, `{contexto_sector}`, `{categoria}` y `{respuestas_categoria}`. |
| `Services\Ia\Analizador` | Interfaz con dos versiones: `AnalizadorDeCategoria` (OpenAI, salida JSON estricta) y `AnalizadorDePrueba` (sin conexión, `IA_DE_PRUEBA=true`). |
| `Services\Resultados\CalculadoraPuntajes` | Funciones puras del cálculo (RN-018) y el texto de cada respuesta. |
| `Services\Resultados\PublicarResultado` | Consolida y publica el resultado una sola vez, avisa a la empresa y manda a generar el PDF. |
| `Jobs\GenerarInformePdf` e `InformePdf` | PDF de 3 páginas con Chromium (RN-024). |
| `Support\Niveles` y `Support\MetodosAnalisis` | Niveles con su frase (RN-019) y los cinco métodos de análisis (DEC-019). |

### Ver como

| Clase | Responsabilidad |
|---|---|
| `Middleware\VerComo` | En cada petición cambia el usuario por la cuenta que se mira y bloquea todo lo que no sea GET (RN-026). |
| `Support\SesionVerComo` | Guarda en la sesión quién mira a quién y abre o cierra el registro en `registros_ver_como`. |

## 3. Procesos

### 3.1 Publicar un diagnóstico

1. `EstadoDiagnostico::calcular()` revisa el borrador. Si hay problemas, no se publica.
2. `ContenidoDiagnostico::congelar()` copia categorías, importancia, preguntas y opciones a un arreglo. Las referencias son estables (`c{id}`, `p{id}`, `o{id}`).
3. Se crea una fila en `versiones_diagnostico` con ese arreglo en `contenido` (JSONB) y el número de versión siguiente.
4. Desde ahí, las mediciones apuntan a esa versión. Cambiar el borrador no toca lo publicado (RN-010).

### 3.2 Responder

1. `GET /mi-diagnostico/{medicion}` comprueba que la medición sea de la empresa y que se pueda responder.
2. Cada cambio hace `PUT /mi-diagnostico/{medicion}/respuestas` con una sola pregunta. El servidor valida que la pregunta sea de esa versión, que las opciones existan y que la excluyente ("Ninguna") no vaya con otras. Guarda en `respuestas` y devuelve el avance.
3. Las escritas (y el "¿Cuál?" de la opción "Otro") se guardan a los 0,9 s de dejar de escribir.
4. `POST …/enviar` exige todas las obligatorias, pasa la medición a "Enviada" y despacha `AnalizarMedicion`.

### 3.3 Analizar y publicar

```
AnalizarMedicion
  ├─ puntaje de las cerradas → respuestas.puntaje
  └─ por cada categoría: analisis_categoria (pendiente) → AnalizarCategoria
                                                        ├─ ArmadorPrompt::armar()
                                                        ├─ Analizador::analizar()  (JSON estricto)
                                                        ├─ guarda respuesta_ia
                                                        └─ PublicarResultado::siTermino()
                                                              (bloquea la medición; solo publica
                                                               cuando todas están "terminado")
```

- **Una categoría sin respuestas** (todas opcionales) no llama a la IA y vale 0.
- **Si la IA falla** tres veces, la categoría queda "fallido", se avisa a los administradores y la empresa ve "Reintentar" en E4. Las demás conservan su análisis.
- **Publicar** escribe `resultados` con el total, el nivel, la variación y `por_categoria` (JSONB). Ese campo copia los textos, las respuestas, las observaciones, el método y la recomendación, así el resultado no depende de cambios futuros (RN-023).

### 3.4 Cálculo (RN-018)

| Qué | Fórmula | Dónde |
|---|---|---|
| Opción única | Puntaje de la opción elegida | `CalculadoraPuntajes::cerrada` |
| Selección múltiple | Suma de las elegidas, con tope 100 | `CalculadoraPuntajes::cerrada` |
| Abierta | Puntaje 0–100 que da la IA según el criterio | `PublicarResultado::calcular` |
| Categoría | Promedio redondeado de sus preguntas respondidas | `CalculadoraPuntajes::categoria` |
| Total | Promedio ponderado por la importancia de cada categoría | `CalculadoraPuntajes::total` |
| Nivel | Tramo de RN-019 | `Niveles::de` |
| Variación | Total actual menos el de la medición terminada anterior | `PublicarResultado::anterior` |

### 3.5 Prompt por capas (RN-022)

1. Instrucciones generales (tabla `prompts`, alcance general; si no hay, las de `ArmadorPrompt::GENERAL`).
2. Ajuste del sector (usar, agregar o reemplazar cada elemento).
3. Ajuste de la empresa (igual).
4. Preguntas con sus respuestas, el "Ten en cuenta" de las opciones elegidas y el criterio de las abiertas.
5. Formato fijo de salida (no se edita).

La salida se valida contra `AnalizadorDeCategoria::esquema()`. Si no lo cumple, se lanza `RespuestaIaInvalida` y la cola reintenta.

## 4. Formatos JSON guardados

Están completos, con ejemplo, en `12_BASE_DE_DATOS.md`. Resumen:

| Campo | Contenido |
|---|---|
| `versiones_diagnostico.contenido` | `{ categorias: [{ ref, nombre, importancia, preguntas: [{ ref, texto, tipo, indicacion, criterio_ia, obligatoria, opciones: [{ ref, texto, puntaje, ten_en_cuenta, exclusiva, con_texto }] }] }] }` |
| `respuestas.opciones_elegidas` | Lista de referencias de opción (`["o12"]`). El texto de la abierta o del "¿Cuál?" va en `respuestas.texto`. |
| `analisis_categoria.respuesta_ia` | `{ preguntas: [{ id, puntaje, observacion }], metodo, metodo_motivo, observacion_categoria, recomendacion: { titulo, detalle } }` |
| `resultados.por_categoria` | Lista con `ref`, `nombre`, `importancia`, `puntaje`, `nivel`, `anterior`, `variacion`, `observacion`, `metodo`, `recomendacion` y `preguntas`. |

## 5. Concurrencia y fallas

| Caso | Qué pasa |
|---|---|
| Dos categorías terminan a la vez | `PublicarResultado` bloquea la fila de la medición (`lockForUpdate`) y comprueba que no exista resultado: se publica una vez. |
| Dos personas de la empresa responden a la vez | Cada respuesta se guarda por pregunta; queda la última. |
| Falla el PDF | El resultado queda publicado. `GenerarInformePdf` reintenta 3 veces y, si sigue faltando, se genera al descargarlo. |
| Falla el correo de aviso al asignar | La medición queda asignada sin `ultimo_aviso_en` y la pantalla ofrece "Reenviar aviso". |
| Dos administradores asignan a la vez | `AsignarMedicion` corre en una transacción, bloquea la pendiente (`lockForUpdate`) y pide `reemplazar` si ya existe una. |

## 6. Frontend

- **Páginas** en `resources/js/pages`, una por pantalla del wireframe, con el código en el comentario inicial.
- **Componentes base** en `components/base` (botón, modal, tabla, etiquetas, paginación, menú) y por módulo en `components/<módulo>`.
- **Lógica sin pantalla** en `resources/js/lib` (contraseña, fechas, paginación, menú, usuarios, IA), con pruebas de Vitest al lado (`*.test.ts`).
- **Rutas:** `lib/rutas.ts` reúne las URL; Wayfinder genera `resources/js/actions` y `resources/js/routes` desde Laravel.
- **SSR:** las páginas se arman también en Node. No se usa `window` ni `document` al montar; la URL sale de `usePage().url`.
- **Páginas fijas:** en pantallas grandes la página no se desplaza (`lg:h-dvh lg:overflow-hidden`); las tablas se paginan con las filas que caben (`useFilasQueCaben`).
