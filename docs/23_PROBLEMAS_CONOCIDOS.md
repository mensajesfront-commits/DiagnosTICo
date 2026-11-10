# Problemas conocidos

**Estado:** COMPLETO (T-035). Problemas conocidos en la entrega `v1.0.0`.

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
| ISSUE-009 | La imagen de Sail no se pudo construir en el entorno de Claude | Resuelto en los equipos del equipo |
| ISSUE-010 | `vue-tsc` 3.3.13 y 3.3.14 dan errores falsos en `MiPerfil.vue` | Resuelto (fijado en 3.3.10) |
| ISSUE-011 | Docker Desktop en Windows no abre un puerto ("ports are not available") | Resuelto |
| ISSUE-012 | `git pull` se detiene por cambios locales en `package-lock.json` | Resuelto |
| ISSUE-013 | El análisis de la IA falla cuando la empresa responde una abierta opcional | Resuelto (10 de noviembre de 2026) |

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
- **Solución:** usar el Vitest que trae Vite+ (DEC-008). Desde el 4 de noviembre, Vite+ 1.5 trae Vitest 5.1.
- **Nota:** `vue-tsc` 3.3.13 y 3.3.14 dan errores falsos en `MiPerfil.vue` ("Property 'value' does not exist on type 'InertiaForm'"). Queda fijo en 3.3.10 (DEC-008).

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
- **Resuelto:** la imagen se construye en el equipo de Cristian con Sail, y ahí se generaron y revisaron los dos PDF (4 de noviembre de 2026).

### ISSUE-010 — `vue-tsc` 3.3.13 y 3.3.14 con errores falsos

- **Síntoma:** `npm run types:check` dice "Property 'value' does not exist on type 'InertiaForm'" en `resources/js/pages/perfil/MiPerfil.vue`, aunque el código está bien.
- **Causa:** un error de esas versiones de `vue-tsc`.
- **Solución:** `vue-tsc` queda fijo en **3.3.10** en `package.json` (DEC-008). Se puede subir cuando una versión nueva pase `npm run types:check`.

### ISSUE-011 — Docker Desktop en Windows no abre un puerto

- **Síntoma:** `sail up` falla con "ports are not available: exposing port TCP 0.0.0.0:54329 … /forwards/expose returned unexpected status: 500".
- **Causa:** Windows reserva rangos de puertos para Hyper-V y WSL. Si `FORWARD_DB_PORT` (u otro puerto del `.env`) cae en uno, Docker no puede usarlo.
- **Cómo verlo:** en PowerShell, `netsh interface ipv4 show excludedportrange protocol=tcp`.
- **Solución:** usar en el `.env` un puerto fuera de esos rangos (por ejemplo `FORWARD_DB_PORT=5433`) y correr `./vendor/bin/sail down` y `./vendor/bin/sail up -d`. Otra opción, en PowerShell como administrador: `net stop winnat` y `net start winnat`. Laravel sigue usando `DB_PORT=5432` dentro de Docker.

### ISSUE-012 — `git pull` se detiene por `package-lock.json`

- **Síntoma:** "Your local changes to the following files would be overwritten by merge: package-lock.json".
- **Causa:** `npm install` reescribe `package-lock.json` en el equipo.
- **Solución:** `git checkout -- package-lock.json` y después `git pull`. El archivo bueno viene en la rama.

### ISSUE-013 — La IA falla con las abiertas opcionales

- **Síntoma:** en local y en el servidor, el análisis de una categoría se queda en reintentos y termina en «falló» cuando la empresa respondió una pregunta abierta opcional. En el registro: «La IA no calificó la pregunta abierta …».
- **Causa:** a la IA se le pedía calificar toda abierta de 0 a 100, pero el criterio de la opcional dice «No se califica», y la IA devolvía puntaje `null`. `AnalizadorDeCategoria::validar` exigía un número en toda abierta y rechazaba la respuesta, aunque `PublicarResultado` no usa ese puntaje (la opcional no cuenta, decisión del 23 de octubre).
- **Solución:** la abierta opcional (la que va con `dato_extra`) lleva la instrucción «No la califiques: devuelve puntaje null» y `validar` la acepta sin puntaje. La abierta obligatoria sigue necesitando su puntaje de 0 a 100. Pruebas en `ResultadosTest`.
