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
