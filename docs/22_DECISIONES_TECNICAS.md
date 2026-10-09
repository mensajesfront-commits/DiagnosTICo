# Decisiones técnicas

**Estado:** EN CURSO (T-034)

Cada decisión registra qué se decidió, por qué y qué se descartó. Si una decisión se cambia, no se borra: se marca **REEMPLAZADA** y se agrega una nueva.

| ID | Decisión | Estado |
|---|---|---|
| DEC-001 | Stack: Laravel 13, Vue 3 con Inertia 3, Tailwind 4 y PostgreSQL 18 | Vigente |
| DEC-002 | IA con la API de OpenAI y salida estructurada | Vigente |
| DEC-003 | Gráficas con Chart.js y vue-chartjs | Vigente |
| DEC-004 | PDF con spatie/laravel-pdf, Browsershot y Chromium | Vigente |
| DEC-005 | Monolito modular: una sola aplicación Laravel | Vigente |
| DEC-006 | Entrega en el repositorio, sin despliegue | Vigente |
| DEC-007 | PHP 8.4 fijado en Composer; Pest 5 con PHPUnit 13 | Vigente |
| DEC-008 | Vitest del kit (Vite+) en lugar de Vitest 5 | Vigente |
| DEC-009 | Guzzle 7 en lugar de Guzzle 8 | Vigente |
| DEC-010 | Fuentes IBM Plex servidas desde el proyecto | Vigente |
| DEC-011 | Chromium instalado con Playwright en la imagen de Sail | Vigente |
| DEC-012 | Del kit solo se deja el registro; sin modo oscuro | Vigente |
| DEC-013 | La llamada real a OpenAI se prueba cuando se use la IA | Vigente |
| DEC-014 | Reglas de acceso propias sobre Fortify | Vigente |
| DEC-015 | Registro en dos pasos y actividad económica CIIU | Vigente |
| DEC-016 | País, departamento y ciudad en cascada | Vigente |
| DEC-017 | Eliminar cuentas con 90 días para recuperarlas | Vigente |
| DEC-018 | Catálogo CIIU Rev. 5 A.C. y subsectores por división | Vigente |

---

### DEC-001 — Stack base

- **Decisión:**
  - Laravel 13 para el backend.
  - Vue 3.5 con TypeScript e Inertia 3 para las pantallas.
  - Tailwind CSS 4 para los estilos.
  - PostgreSQL 18 para la base de datos.
  - Se parte del kit de inicio oficial de Laravel para Vue (`laravel/vue-starter-kit`).
- **Por qué:**
  - NuevasTIC exige Laravel, Vue, Tailwind y PostgreSQL.
  - El kit trae resueltos el login, el registro, la recuperación de contraseña y el perfil (L1–L4, A6, E11), además de Reka UI para componentes accesibles.
- **Descartado:** una API REST separada del frontend. Duplica el trabajo de autenticación y de rutas.

### DEC-002 — IA con OpenAI

- **Decisión:**
  - Se usa `openai-php/laravel` 0.21.
  - Cada categoría se analiza con una sola llamada (RN-021).
  - La respuesta se pide con salida estructurada (`response_format: json_schema`, `strict: true`). El esquema está en `App\Services\Ia\AnalizadorDeCategoria::esquema()`.
  - El modelo se elige con `OPENAI_MODEL` en el `.env`.
- **Por qué:**
  - Con salida estructurada, la API garantiza el formato, así que el sistema puede leer la respuesta sin errores.
  - Si el contenido no cumple las reglas (por ejemplo, un puntaje fuera de 0–100), se lanza `RespuestaIaInvalida` y la categoría se reintenta.
- **Pendiente:**
  - **[INFORMACIÓN PENDIENTE]** Falta definir el modelo por defecto y quién paga la clave.
  - La propuesta estima US$0,01 por diagnóstico con el modelo económico.

### DEC-003 — Gráficas con Chart.js

- **Decisión:** Chart.js 4.5 con `vue-chartjs` 5.3 y `chartjs-plugin-annotation` 3.1 para el medidor, el radar y la evolución. Las barras simples se hacen con Tailwind.
- **Por qué:** es gratuita, muy usada y dibuja igual en la pantalla y dentro del PDF (Chromium).
- **Nota:** el canvas no espera a las fuentes web. El radar se dibuja después de `document.fonts.load(...)`; si no, las etiquetas salen con otra letra.

### DEC-004 — PDF con Chromium

- **Decisión:**
  - Se usan `spatie/laravel-pdf` 2.14 con el driver Browsershot 5.4 y `puppeteer` 25.
  - Chart.js y las fuentes se incrustan en el HTML del PDF (`App\Support\RecursosPdf`).
  - La plantilla marca `window.radarListo` y el PDF espera esa marca (`waitUntilReady`).
- **Por qué:**
  - Chromium dibuja el PDF igual que la pantalla, gráficas incluidas.
  - Incrustar los recursos evita depender de internet.
- **Descartado:** DomPDF, porque no ejecuta JavaScript y no dibujaría las gráficas.

### DEC-005 — Monolito modular

- **Decisión:**
  - Una sola aplicación Laravel con una sola base de datos.
  - Los módulos se separan por carpetas (`app/Services/Ia`, `resources/js/components/base`…).
- **Por qué:** menos piezas que coordinar para un equipo de dos personas en 7 semanas.

### DEC-006 — Entrega sin despliegue

- **Decisión:** se entrega el repositorio funcionando en local con Laravel Sail. No se publica en un servidor.
- **Por qué:** es lo acordado en la propuesta técnica (sección 1).

### DEC-007 — PHP 8.4 fijado en Composer; Pest 5

- **Decisión:**
  - `composer.json` exige `php: ^8.4` y fija `config.platform.php = 8.4.1`.
  - Pest 5.3 con PHPUnit 13.
- **Por qué:**
  - Sail corre PHP 8.4, y Pest 5 y PHPUnit 13 lo requieren.
  - Fijar la plataforma hace que `composer.lock` se resuelva siempre para PHP 8.4, aunque alguien ejecute Composer con otra versión.
  - El kit traía PHPUnit 12; se subió a 13 para usar Pest 5, como prevé el documento "Tecnologías del sistema".
- **Consecuencia:** el proyecto no corre con PHP 8.3. Algunas librerías ya usan sintaxis de PHP 8.4.

### DEC-008 — Vitest del kit

- **Decisión:**
  - Las pruebas del frontend usan el Vitest 4.1 que trae Vite+ (`vite-plus`, incluido en el kit).
  - Se corren con `npm test` (`vp test run`).
  - Se escriben en `resources/js/**/*.test.ts` e importan desde `vite-plus/test`.
- **Por qué:** instalar Vitest 5 aparte choca con la versión fijada por Vite+ (conflicto de dependencias de npm).
- **Descartado:** quitar Vite+. El kit lo usa para compilar, formatear y revisar el código.

### DEC-009 — Guzzle 7

- **Decisión:** se usa Guzzle 7.15 en lugar de la 8.2 que traía el kit.
- **Por qué:** `openai-php/laravel` 0.21 exige `guzzlehttp/guzzle ^7.9.3`. Laravel 13 funciona con las dos.

### DEC-010 — Fuentes IBM Plex locales

- **Decisión:**
  - IBM Plex Sans e IBM Plex Mono se instalan con `@fontsource` y se importan en `resources/css/app.css`.
  - Se quitó la carga remota de fuentes del kit (Bunny Fonts).
- **Por qué:**
  - Es la tipografía del wireframe.
  - Así funciona sin conexión, incluso dentro del PDF y en redes que bloquean CDN.

### DEC-011 — Chromium en la imagen de Sail

- **Decisión:**
  - El Dockerfile publicado de Sail (`docker/8.4/Dockerfile`) instala Chromium con `npx playwright@1.56.1 install chromium`.
  - Lo enlaza en `/usr/local/bin/chromium`.
  - El `.env` apunta ahí (`LARAVEL_PDF_CHROME_PATH`).
- **Por qué:**
  - En Ubuntu, el paquete apt de Chromium es un *snap* que no funciona dentro de Docker.
  - Playwright publica Chromium para amd64 y arm64, así que también funciona en Mac con chip Apple.
  - Las librerías del sistema ya las instala la imagen de Sail (`playwright install-deps`).

### DEC-012 — Funciones del kit

- **Decisión:**
  - Del kit se deja solo el **registro**. Se quitan la verificación de correo, el 2FA, las passkeys y la confirmación de contraseña.
  - Se quita también el selector de apariencia (modo oscuro).
- **Por qué:** el diseño no tiene esas funciones. El registro deja la cuenta "lista al instante" (L2) y el wireframe solo tiene modo claro.

### DEC-013 — Llamada real a OpenAI más adelante

- **Decisión:** la prueba técnica de la IA (T-019) se da por cumplida con el servicio, el comando `prueba:ia` y las pruebas con la API simulada. La llamada real con una clave se hace cuando se construya el análisis de la IA (semana 5).
- **Por qué:** el equipo todavía no usa la clave de OpenAI; el código ya está listo para recibirla en `.env` (`OPENAI_API_KEY`, `OPENAI_MODEL`).

### DEC-014 — Reglas de acceso propias sobre Fortify

- **Decisión:**
  - La contraseña fuerte (RN-001) es la misma en todos los entornos. Se quitó la regla del kit para producción (12 caracteres y revisión de filtraciones), que RN-001 no pide.
  - El enlace de contraseña nueva no vence por tiempo (RN-006): `expire` de un año en `config/auth.php`.
  - El inicio de sesión revisa que la cuenta y su empresa estén activas, y "¿Olvidaste tu contraseña?" responde siempre lo mismo (RN-005).
  - Se agregaron las traducciones al español en `lang/es` en lugar de instalar un paquete de traducciones.
- **Por qué:** las reglas de negocio piden algo distinto a lo que trae Fortify por defecto, y los evaluadores van a probar el acceso.

### DEC-015 — Registro en dos pasos y actividad económica CIIU

- **Decisión:**
  - El registro (L2) tiene dos pasos: «Mi empresa» y «Tu usuario».
  - La empresa elige su **actividad económica** de la clasificación **CIIU Rev. 4 A.C.** (la que usa la DIAN en el RUT), filtrada por el sector elegido. Tabla `actividades_economicas`; cada sector tiene sus códigos. **Actualizado por DEC-018:** ahora es la CIIU Rev. 5 A.C. del DANE.
  - La **descripción corta** (máximo 300 caracteres) y el **cargo** son obligatorios.
- **Por qué:** lo pidió el equipo (7 de octubre de 2026). El CIIU es la clasificación oficial en Colombia, así que la empresa la reconoce de su RUT. La descripción le da contexto a la IA, y el cargo dice quién pide el acceso.
- **Pendiente:** **[INFORMACIÓN PENDIENTE]** NuevasTIC debe confirmar qué códigos van en cada sector. Un sector sin actividades no pide la actividad.

### DEC-016 — País, departamento y ciudad en cascada

- **Decisión:**
  - Se elige primero el **país** (los 18 de Hispanoamérica), luego su **departamento** (se llama Estado, Provincia o Región según el país) y por último la **ciudad**. En las tres listas se puede escribir para buscar, sin importar tildes ni mayúsculas.
  - El país y el departamento deben ser de la lista (el servidor lo revisa). La **ciudad** se puede escribir aunque no esté en la lista, pero solo eligiendo a propósito "Usar «…»" (desde 3 letras); un texto a medias ("c") no se guarda. El servidor pide al menos 2 letras.
  - Se usa en el registro (L2) y en Mi perfil (A6 y E11). Nueva columna `departamento` en `empresas` y `users`.
  - Los datos vienen de countries-states-cities-database (licencia ODbL), en `resources/ubicaciones/`. Se cargan por país desde `GET /ubicaciones/{pais}`, sin guardarlos en la base de datos.
- **Por qué:** lo pidió el equipo (8 de octubre de 2026). Escribir evita buscar en listas largas (México tiene más de 9.000 localidades). Las listas públicas no traen todos los municipios, y por eso la ciudad queda libre.
- **Pendiente:** España y Guinea Ecuatorial quedan por fuera (respuesta del equipo: solo Hispanoamérica).

### DEC-017 — Eliminar cuentas con 90 días para recuperarlas

- **Decisión:**
  - En Usuarios y roles (A5), "Desactivar / Eliminar" abre un modal con las dos opciones. Desactivar sigue igual (RN-004).
  - **Eliminar** pide **escribir a mano el correo exacto** de la cuenta; el servidor lo vuelve a comparar.
  - La cuenta eliminada **sale de la vista** y nadie puede entrar con ella, pero **sigue en la base de datos 90 días** (`deleted_at`). En ese tiempo el Administrador la recupera desde el filtro Estado «Eliminadas» → "Recuperar". Si la persona intenta entrar con la contraseña correcta, ve hasta qué fecha puede pedir que la recuperen.
  - Pasados los 90 días, la tarea diaria `cuentas:purgar` (03:10) la **borra para siempre**: la fila, su rol, sus sesiones, su foto y el logo de la empresa.
  - Si es la **cuenta principal** de una empresa, se eliminan también la empresa y sus colaboradores, y se recuperan juntos. Un colaborador eliminado con su empresa no se recupera solo.
  - No se puede eliminar la propia cuenta ni el **último Administrador**.
  - En el log queda una constancia con los id (cuenta, empresa y quién la eliminó), sin datos personales.
- **Por qué:** lo pidió el equipo (8 de octubre de 2026): limpiar cuentas y atender solicitudes de borrado de datos (Ley 1581 de 2012), dando 90 días por si la empresa se arrepiente.
- **Colaboradores (E12):** la cuenta principal de la empresa también elimina a sus colaboradores, con la misma espera de 90 días. Ahí la doble confirmación es con **dos botones** ("Eliminar" → "Sí, eliminar a…") en lugar de escribir el correo, porque el correo y el nombre del colaborador se pueden cambiar.
- **Ojo:** mientras dure la espera, el correo sigue ocupado: no se puede registrar ni invitar otra cuenta con él.
- **Pendiente:** cuando existan mediciones y resultados, decidir si se borran con la empresa o se conservan anonimizados para las estadísticas. La cantidad de días se cambia con `DIAS_PARA_RECUPERAR_CUENTA`.

### DEC-018 — Catálogo CIIU Rev. 5 A.C. y subsectores por división

- **Decisión:**
  - Se carga en la base el catálogo oficial **CIIU Rev. 5 A.C. del DANE** (22 secciones, 87 divisiones, 544 clases) desde `resources/ciiu/` (el Excel original y su JSON). Tablas `ciiu_divisiones` y `ciiu_clases`; lo carga `CiiuSeeder`.
  - Un sector puede asociarse a una **división** (dos dígitos, columna `sectores.ciiu_division`). Al hacerlo, **todas las clases** de esa división (cuatro dígitos) pasan a ser sus **subsectores**: las actividades económicas que la empresa elige en el registro (L2) y en Mi perfil (E11).
  - En crear y editar sector (A2.2a, A2.2) hay un campo "Subsectores · división CIIU" con búsqueda por código o nombre. Si el **nombre del sector es exactamente el de una división** (sin mirar tildes ni mayúsculas), se elige sola.
  - El nombre del sector admite hasta **60 caracteres** (antes 40), para que quepan los nombres cortos de todas las divisiones (el más largo tiene 54).
  - Al cambiar de división, los subsectores que ya no son de la nueva se **desactivan**, no se borran, porque puede haber empresas que los usen. Al quitar la división se conservan.
  - Los 6 sectores iniciales conservan sus nombres (Abogados, Alojamientos, Comidas, Inmobiliarias, Médicos y Turismo); no toman el nombre de su división (decisión del equipo, 9 de octubre de 2026).
  - Los 6 sectores iniciales pasan a la Rev. 5: Alojamientos → 55, Comidas → 56, Inmobiliarias → 68, Turismo → 79, Médicos → 86 y Abogados → solo la clase 6910 (la división 69 incluye contabilidad). Los códigos de la Rev. 4 que ya no existen (6820, 5514, 5519, 5520) quedan desactivados.
  - **Nombres cortos:** cada división y clase guarda el nombre corto (el que se muestra en el sistema, preparado por el equipo, por ejemplo «Comidas y bebidas») y el oficial del DANE («Actividades de servicios de comidas y bebidas»). Al crear un sector se reconocen los dos. Los subsectores ya creados toman el nombre corto al correr `CiiuSeeder`.
- **Por qué:** lo pidió el equipo (9 de octubre de 2026). Así un sector nuevo trae sus subsectores sin escribirlos a mano, y los códigos y nombres salen del archivo oficial (no se inventan). Se descartó la tabla CIIU Rev. 4 armada aparte porque se extrajo de un texto y 203 nombres traían pegados los códigos de grupo.
- **Ojo:**
  - En la Rev. 5 algunos códigos cambiaron de significado. Por ejemplo, 8691 era "apoyo diagnóstico" y ahora es "intermediación para los servicios de salud". Una empresa que ya tenía uno de esos códigos queda con el nombre nuevo.
  - **[INFORMACIÓN PENDIENTE]** Confirmar con NuevasTIC si el RUT de la DIAN ya usa la Rev. 5 o sigue con la Rev. 4.

