# Requisitos no funcionales

**Estado:** REQUIERE VALIDACIÓN (T-062). Las metas numéricas de rendimiento son una **propuesta** del equipo; NuevasTIC debe confirmarlas (PA-008).

Cómo debe comportarse el sistema, además de lo que hace. RNF-001 a RNF-006 vienen de `05_REQUISITOS_FUNCIONALES.md`, sección 7; desde RNF-007 se agregan aquí. Cada requisito dice cómo se comprueba.

## Resumen

| ID | Categoría | Requisito | Estado |
|---|---|---|---|
| RNF-001 | Seguridad | Contraseña fuerte y avisos que no revelan correos | Cumplido y probado |
| RNF-002 | Seguridad | Acceso por rol; empresa y colaborador solo ven su empresa | Cumplido en lo construido |
| RNF-003 | Usabilidad | Respuestas guardadas al momento; se retoma donde se quedó | Por construir (semana 5) |
| RNF-004 | Rendimiento | Correos y análisis de la IA en segundo plano | Por construir (semana 5) |
| RNF-005 | Auditoría | Registro de quién usó "Ver como" y cuándo | Por construir (semana 6) |
| RNF-006 | Seguridad | Mensajes del bot verificados de Meta | APLAZADO con el bot |
| RNF-007 | Compatibilidad | Versiones fijas del stack | Cumplido |
| RNF-008 | Compatibilidad | Navegadores y tamaños de pantalla | Propuesta |
| RNF-009 | Rendimiento | Tiempos de respuesta | Propuesta |
| RNF-010 | Seguridad | Sesión y secretos | Cumplido |
| RNF-011 | Privacidad | Tratamiento de datos personales | Parcial |
| RNF-012 | Accesibilidad | Uso con teclado, lector de pantalla y sin depender del color | Cumplido en lo construido |
| RNF-013 | Idioma | Todo en español | Cumplido |
| RNF-014 | Mantenibilidad | Pruebas, formato y análisis estático en cada cambio | Cumplido |
| RNF-015 | Confiabilidad | La IA puede fallar sin perder datos | Por construir (semana 5) |
| RNF-016 | Instalación | Se instala desde cero con el README | Cumplido |

## Detalle

### RNF-001 · Seguridad del acceso

- Contraseña de mínimo 8 caracteres, con una mayúscula, un número y un carácter especial, en todos los entornos (RN-001).
- El inicio de sesión y la recuperación no revelan si un correo está registrado (RN-005).
- Máximo 5 intentos por minuto en iniciar sesión, registrarse y recuperar la contraseña.
- **Se comprueba con:** `tests/Feature/Auth/SeguridadAccesoTest.php`. Detalle en `17_SEGURIDAD.md`.

### RNF-002 · Acceso por rol

- Cada ruta se protege en el servidor con `role:` o `permission:`; ocultar en el menú no basta.
- La empresa y el colaborador solo ven datos de su empresa (RN-007).
- **Se comprueba con:** `PermisosTest`, `PerfilTest` y las pruebas de cada módulo.

### RNF-003 · Guardado al momento

- Cada respuesta del diagnóstico se guarda sola al contestarla. Si la empresa sale, vuelve a la misma pregunta.
- **Se comprueba con:** una prueba de E3 que cierra la sesión a mitad y la retoma.

### RNF-004 · Trabajo en segundo plano

- Los correos y el análisis de la IA van a la cola de Laravel (`jobs`). La pantalla no espera a OpenAI ni al servidor de correo.
- La empresa ve la pantalla de espera (E5) y puede salir (HU-063).
- **Se comprueba con:** pruebas con `Queue::fake()` y `Mail::fake()`.

### RNF-005 · Auditoría de "Ver como"

- Cada uso queda en `registros_ver_como` con quién, a qué cuenta, el inicio y el fin (RN-026).

### RNF-007 · Versiones fijas

| Pieza | Versión | Dónde se fija |
|---|---|---|
| PHP | 8.4 | `composer.json` (`config.platform.php`) |
| Laravel | 13 | `composer.lock` |
| Node | 24 | `.nvmrc` |
| PostgreSQL | 18 | `compose.yaml` |
| Paquetes de PHP y JavaScript | Exactas | `composer.lock`, `package-lock.json` |

### RNF-008 · Navegadores y pantallas (propuesta)

- Las dos últimas versiones de Chrome, Edge, Firefox y Safari.
- Funciona desde 360 px de ancho, como un celular, sin desplazamiento horizontal. El menú lateral se vuelve un botón en pantallas pequeñas.
- El diseño de referencia es de 1440 px, el de los wireframes.

### RNF-009 · Tiempos de respuesta (propuesta)

| Qué | Meta propuesta | Condición |
|---|---|---|
| Abrir una pantalla | Menos de 2 s | En el equipo local con Sail |
| Guardar una respuesta (RNF-003) | Menos de 1 s | — |
| Análisis completo de una medición | Menos de 5 min | Depende de OpenAI; el avance se ve en E5 |
| Generar el PDF | Menos de 30 s | Una sola vez por resultado (RN-024) |

**[INFORMACIÓN PENDIENTE]** Cuántas empresas y mediciones simultáneas se esperan. Sin ese dato no se fija una meta de carga.

### RNF-010 · Sesión y secretos

- Las claves (base de datos, correo, OpenAI) solo van en `.env`; `.env.example` lleva solo placeholders.
- Las contraseñas se guardan cifradas con bcrypt.
- La sesión dura 120 minutos sin actividad (`SESSION_LIFETIME`). Se cierra si desactivan la cuenta.
- En una presentación o en producción: `APP_DEBUG=false`. Con HTTPS, además: `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`.

### RNF-011 · Datos personales

- Al registrarse, la empresa acepta los términos y la política de tratamiento de datos, y se guarda cuándo (`users.terminos_aceptados_en`).
- La foto y el logo se guardan en una carpeta privada y solo los ve la misma cuenta, su empresa o el Administrador.
- Las cuentas se desactivan (RN-004) y, si hace falta, el Administrador las elimina con sus datos, confirmando con el correo exacto (DEC-017). Sirve para atender una solicitud de borrado (Ley 1581 de 2012, de protección de datos personales).
- **[INFORMACIÓN PENDIENTE]**:
  - el texto y la URL de los términos y de la política de datos;
  - el procedimiento formal de NuevasTIC para recibir y responder una solicitud de borrado (plazos, quién la aprueba, si hay datos que deban conservarse por ley).

### RNF-012 · Accesibilidad

- Los campos tienen etiqueta (`label`), y los errores se anuncian con `role="alert"`.
- Se puede usar con teclado. Al abrir un modal, el foco va al primer campo.
- El estado no depende solo del color: las etiquetas llevan símbolo y texto (✓ ○ ◐ ⚠).
- **[INFORMACIÓN PENDIENTE]** Si se pide cumplir un nivel concreto, como WCAG 2.1 AA.

### RNF-013 · Idioma

- Las pantallas, los mensajes de error, los correos y el PDF van en español (`lang/es`). Los identificadores del dominio también, sin tildes (`28_CONVENCIONES_DESARROLLO.md`).

### RNF-014 · Mantenibilidad

- Cada pull request corre en GitHub Actions (`composer ci:check`) las pruebas de Pest y Vitest, Pint, y el lint y los tipos del frontend.
- El análisis estático de PHP (PHPStan) se corre antes de subir, con `composer types:check`. **[FUNCIONALIDAD POR DEFINIR]** Agregarlo también a la CI (T-057).
- Ningún cambio se une a `develop` sin revisión (`28_CONVENCIONES_DESARROLLO.md`).

### RNF-015 · Fallas de la IA

- Si una categoría falla o responde fuera del formato, se reintenta solo esa, hasta 3 veces (RN-021).
- Las respuestas y los puntajes ya calculados no se pierden. Tras 3 fallos, se avisa al Administrador por correo.
- La IA devuelve JSON con un esquema fijo, y el sistema lo valida antes de guardarlo.

### RNF-016 · Instalación

- Otra persona instala el sistema desde cero, solo con el README, usando Laravel Sail (Docker).
- `php artisan migrate --seed` deja los roles, los sectores y el Administrador listos.
- **Se comprueba con:** T-021 y T-154.

### No aplica

- **Disponibilidad y respaldos de un servidor:** el sistema no se publica (`03_ALCANCE.md`). Se entrega el comando para sacar una copia de la base de datos (`12_BASE_DE_DATOS.md`).
