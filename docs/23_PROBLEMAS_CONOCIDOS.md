# Problemas conocidos

**Estado:** EN CURSO (T-035)

Problemas que aparecieron al instalar o al programar, con su solución. Formato: ISSUE-XXX.

| ID | Problema | Estado |
|---|---|---|
| ISSUE-001 | `composer create-project laravel/vue-starter-kit` instala una versión vieja (Laravel 12) | Resuelto |
| ISSUE-002 | Composer no puede descargar zips de GitHub ("Could not authenticate against github.com") | Resuelto |
| ISSUE-003 | Pest 5 no se instala con PHP 8.3 | Resuelto |
| ISSUE-004 | `npm run build` falla al descargar la fuente del kit (Bunny Fonts) | Resuelto |
| ISSUE-005 | Conflicto de npm al instalar Vitest 5 | Resuelto |
| ISSUE-006 | Conflicto de Guzzle al instalar `openai-php/laravel` | Resuelto |
| ISSUE-007 | Chromium se cierra dentro de Docker ("Target closed") | Resuelto |
| ISSUE-008 | Las etiquetas del radar salen con otra letra | Resuelto |
| ISSUE-009 | La imagen de Sail no se pudo construir en el entorno de Claude | Abierto |

---

### ISSUE-001 — El kit instala Laravel 12

- **Síntoma:** `composer create-project laravel/vue-starter-kit` trae Laravel 12, Inertia 2 y Tailwind 3.
- **Causa:** la última etiqueta publicada del kit (v1.0.2) es vieja. Lo actual está en la rama `main`.
- **Solución:** crear el proyecto desde la rama principal, con `composer create-project laravel/vue-starter-kit app dev-main`, o usar `laravel new` con el instalador oficial. Después hay que elegir las funciones con `php artisan install:features` (ver DEC-012).

### ISSUE-002 — Composer y los zips de GitHub

- **Síntoma:** `composer install` termina con "Could not authenticate against github.com".
- **Causa:** la red bloquea las descargas de `api.github.com`, por ejemplo detrás de un proxy corporativo, o se superó el límite de descargas anónimas.
- **Solución:**
  - Opción 1: crear un token de GitHub y ejecutar `composer config -g github-oauth.github.com <TOKEN>`.
  - Opción 2: instalar con `composer install --prefer-source`, que descarga con `git clone`.
  - Un paquete sin fuente (`phpstan/phpstan`) debe estar en la caché de Composer o necesita el token.

### ISSUE-003 — Pest 5 y PHP 8.3

- **Síntoma:** "pestphp/pest requires php ^8.4".
- **Solución:** usar PHP 8.4 (Sail ya lo trae). El proyecto fija PHP 8.4 en `composer.json` (DEC-007).

### ISSUE-004 — La fuente del kit en el build

- **Síntoma:** `npm run build` falla con "Failed to fetch https://fonts.bunny.net/... 403".
- **Solución:** se quitó la fuente remota del kit y se usan las fuentes IBM Plex locales (DEC-010).

### ISSUE-005 — Vitest 5 y Vite+

- **Síntoma:** `npm install -D vitest@5` falla con ERESOLVE, porque `vite-plus` pide `vitest@4.1.11`.
- **Solución:** usar el Vitest que trae Vite+ (DEC-008).

### ISSUE-006 — Guzzle y OpenAI

- **Síntoma:** al instalar `openai-php/laravel`, Composer dice que `guzzlehttp/guzzle` está fijado en 8.2.0.
- **Solución:** `composer require openai-php/laravel:^0.21 -W`, que baja Guzzle a 7.x (DEC-009).

### ISSUE-007 — Chromium en Docker

- **Síntoma:** al generar un PDF, Browsershot falla con `TargetCloseError: Protocol error (Target.createTarget): Target closed`.
- **Causas posibles:**
  - Faltan librerías del sistema que Chromium carga al abrir la página (NSS: `libsoftokn3`, `libfreeblpriv3`…).
  - `/dev/shm` es muy chico.
  - Se ejecuta como root con el *sandbox* activo.
- **Solución:**
  - En Sail, la imagen instala las dependencias con `playwright install-deps` (DEC-011).
  - Si se ejecuta en otro contenedor: instalar las dependencias, usar `--shm-size=1g` y poner `LARAVEL_PDF_NO_SANDBOX=true`.

### ISSUE-008 — Letra de las etiquetas del radar

- **Síntoma:** en el PDF, y a veces en la pantalla, las etiquetas del radar salen con letra serif.
- **Causa:** Chart.js dibuja en un canvas antes de que termine de cargar IBM Plex Mono.
- **Solución:** dibujar después de `document.fonts.load("10px 'IBM Plex Mono'")`. Ya está aplicado en `RadarCategorias.vue` y en `resources/views/pdf/prueba-radar.blade.php`.

### ISSUE-009 — Imagen de Sail sin construir en el entorno de Claude

- **Qué pasa:** el entorno en la nube donde trabajó Claude bloquea los repositorios de paquetes que usa la imagen de Sail (launchpad para PHP 8.4, nodesource). Por eso la imagen no se pudo construir ahí.
- **Cómo se verificó lo demás:**
  - Las pruebas, PHPStan y la generación real del PDF se corrieron con PHP 8.4 en contenedores de Docker y PostgreSQL 18 (`postgres:18-alpine`).
  - El PDF se generó con Chromium 141.
- **Pendiente:** construir la imagen con `./vendor/bin/sail build` en el equipo de Luis o de Cristian y confirmar que `chromium --version` funciona dentro del contenedor (T-021).
