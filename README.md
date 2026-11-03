# Captter · Diagnóstico de marketing digital

Sistema de Diagnóstico de Marketing Digital para **NuevasTIC**. Las empresas responden un diagnóstico de su sector, la IA analiza cada categoría y el sistema entrega un puntaje, un nivel, recomendaciones y un informe PDF. El Administrador configura sectores, categorías, diagnósticos, empresas, mediciones, la IA y las cuentas.

- **Stack:** Laravel 13 · Vue 3 + Inertia 3 · Tailwind CSS 4 · PostgreSQL 18 · OpenAI.
- **Equipo:** Cristian Andrés Penagos Simanca (frontend) y Luis Carlos Sánchez Muñoz (backend).
- **Documentación:** [`docs/00_INDICE.md`](docs/00_INDICE.md). Las reglas para trabajar en el repositorio están en [`CLAUDE.md`](CLAUDE.md).

## Requisitos

Con Sail (recomendado) solo necesitas:

- **Docker:** Docker Desktop en Windows o macOS, o Docker Engine en Linux. En Windows se usa WSL 2 y el proyecto se clona **dentro** de WSL.
- **Git.**

Sail trae todo lo demás dentro de los contenedores: PHP 8.4, Composer, Node 24, PostgreSQL 18, Mailpit y Chromium.

Para instalarlo sin Docker, ver [Instalación sin Sail](#instalación-sin-sail).

## Instalación con Sail

### 1. Clonar el repositorio

```bash
git clone https://github.com/mensajesfront-commits/DiagnosTICo.git
cd DiagnosTICo
```

### 2. Crear el archivo de entorno

```bash
cp .env.example .env
```

Abre `.env` y completa por lo menos estas variables; las demás ya sirven para Sail:

- `OPENAI_API_KEY` y `OPENAI_MODEL`, para el análisis de la IA.
- `MAIL_FROM_ADDRESS`, el remitente de los correos.

Cada variable tiene un comentario que explica para qué sirve.

### 3. Instalar las dependencias de PHP

Todavía no existe `vendor/bin/sail`, así que se usa un contenedor temporal de Composer con PHP 8.4:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install
```

### 4. Construir y levantar los contenedores

```bash
./vendor/bin/sail build      # solo la primera vez: tarda varios minutos (instala Chromium)
./vendor/bin/sail up -d
```

Al terminar deben estar corriendo `laravel.test`, `pgsql` y `mailpit`. Lo compruebas con `./vendor/bin/sail ps`.

### 5. Preparar la aplicación

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

### 6. Abrir el sistema

| Qué | Dónde |
|---|---|
| Sistema | http://localhost |
| Correos (Mailpit) | http://localhost:8025 |

Mientras programas, usa `./vendor/bin/sail npm run dev` en lugar de `npm run build` para que las pantallas se recarguen solas.

### 7. Colas y tareas programadas

El envío de correos y el análisis de la IA corren en segundo plano. `./vendor/bin/sail up -d` levanta solos dos servicios para eso (`compose.yaml`):

- `queue`: procesa la cola (`queue:work`). Sin él, un diagnóstico enviado se queda en "Estamos analizando".
- `scheduler`: tareas programadas (`schedule:work`): mediciones vencidas, cuentas por borrar y el resumen semanal.

Si cambias código de PHP, reinícialos para que lo tomen: `./vendor/bin/sail restart queue scheduler`. Para ver qué están haciendo: `./vendor/bin/sail logs -f queue`.

## Comprobar la instalación

```bash
./vendor/bin/sail artisan test                          # pruebas del backend (Pest)
./vendor/bin/sail npm test                              # pruebas del frontend (Vitest)
./vendor/bin/sail exec laravel.test chromium --version  # Chromium para los PDF
./vendor/bin/sail artisan prueba:pdf                    # genera storage/app/private/pruebas/prueba-radar.pdf
./vendor/bin/sail artisan prueba:ia                     # una llamada real a OpenAI (necesita la clave)
```

`migrate --seed` crea los roles con sus permisos, los sectores de ejemplo y la **cuenta principal** del Administrador, con el correo de `ADMIN_EMAIL` en `.env` (NuevasTIC lo elige al lanzar el proyecto). La cuenta se crea **sin contraseña**: a ese correo le llega "Crear mi contraseña", así nadie más la conoce. En local el correo cae en Mailpit (<http://localhost:8025>). Si el correo falla, la terminal muestra el enlace; para reenviarlo: `./vendor/bin/sail artisan admin:invitar`. Ningún otro Administrador desactiva ni elimina esta cuenta. El seeder se puede correr de nuevo sin duplicar nada (`./vendor/bin/sail artisan db:seed`).

- **Cuenta de empresa:** regístrate en `/register`. Queda con el rol Empresa y su menú.
- **Cuenta de Administrador:** abre el correo "Crear mi contraseña" en Mailpit, crea la contraseña y entra. Las pantallas de la prueba técnica y las vistas previas (`/prueba-tecnica/*`) solo existen con `APP_ENV=local`.

## Cuentas de prueba

`migrate --seed` no crea cuentas de demostración (se quitaron el 8 de octubre de 2026). Cada persona crea las suyas: las empresas se registran en `/register`, los colaboradores los crea su empresa en Colaboradores y las cuentas internas se invitan desde Usuarios y roles.

### Datos de demostración (opcional, solo en local)

Para ver las pantallas con volumen hay un seeder aparte, que `db:seed` no llama:

```bash
./vendor/bin/sail artisan db:seed --class=DiagnosticoEjemploSeeder
./vendor/bin/sail artisan db:seed --class=DiagnosticoProgramacionSeeder
./vendor/bin/sail artisan db:seed --class=DatosDemoSeeder
```

Crea 20 empresas (sectores Comidas y Programación y consultoría informática) con 1 a 4 mediciones terminadas cada una. Sus resultados se calculan igual que uno real, y los textos de la IA son de ejemplo. Algunas empresas tienen además una medición en curso, sin empezar o vencida.

También crea una empresa con 6 colaboradores y una consultora con 10 empresas vinculadas. Todas las cuentas terminan en `captter-demo.test` y usan la contraseña `Demo-Captter-2026`:

| Cuenta | Correo |
|---|---|
| Empresa con 6 colaboradores (Panadería La Espiga) | `gerencia@panaderia-la-espiga.captter-demo.test` |
| Un colaborador de La Espiga | `camila.restrepo@la-espiga.captter-demo.test` |
| Consultora con 10 empresas | `valeria.rios@captter-demo.test` |
| Otra empresa | `gerencia@<nombre-de-la-empresa>.captter-demo.test` (por ejemplo, `gerencia@codigo-andino.captter-demo.test`) |

Volver a correrlo borra lo anterior de la demostración y lo crea de nuevo. Para solo borrarlo:

```bash
./vendor/bin/sail artisan tinker --execute 'Database\Seeders\DatosDemoSeeder::limpiar();'
```

No lo uses en una base con datos reales.

## Copia de la base de datos

```bash
./vendor/bin/sail exec pgsql pg_dump -U sail -d diagnostico > captter.sql
```

El diccionario de datos y el MER están en `docs/12_BASE_DE_DATOS.md`.

## Revisiones de código

Lo mismo que corre GitHub Actions en cada pull request:

```bash
./vendor/bin/sail composer ci:check
```

Por partes:

| Revisión | Comando |
|---|---|
| Formato PHP | `composer lint` |
| Análisis estático PHP | `composer types:check` |
| Formato y lint del frontend | `npm run check:fix` |
| Tipos de TypeScript | `npm run types:check` |

## Instalación sin Sail

Necesitas en tu equipo:

| Programa | Versión |
|---|---|
| PHP | 8.4, con las extensiones `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip` e `intl` |
| Composer | 2 |
| Node.js | 24 (está en `.nvmrc`; con nvm: `nvm use`) |
| PostgreSQL | 18 |
| Chrome o Chromium | Cualquier versión reciente |

Pasos:

1. Crea en PostgreSQL una base para el sistema y otra llamada `testing` para las pruebas.
2. En `.env`, cambia:
   - `DB_HOST=127.0.0.1`, con el usuario y la clave de tu PostgreSQL;
   - `MAIL_MAILER=log`, para que los correos vayan a `storage/logs`;
   - `LARAVEL_PDF_CHROME_PATH`, con la ruta de tu Chrome o Chromium.
3. Ejecuta `composer setup`, que instala las dependencias, genera la clave, migra y compila el frontend.
4. Ejecuta `composer dev`, que levanta el servidor, la cola, los logs y Vite. El sistema abre en http://localhost:8000.

## Problemas comunes

Están explicados, con su solución, en [`docs/23_PROBLEMAS_CONOCIDOS.md`](docs/23_PROBLEMAS_CONOCIDOS.md):

| Síntoma | Ver |
|---|---|
| "Could not authenticate against github.com" al instalar con Composer | ISSUE-002 |
| "requires php ^8.4" | ISSUE-003 |
| El PDF falla con "Target closed" | ISSUE-007 |
| El puerto 80 o el 5432 ya están ocupados | Cambia `APP_PORT` o `FORWARD_DB_PORT` en `.env` |
