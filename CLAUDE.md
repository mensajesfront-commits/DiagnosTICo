# CLAUDE.md — Sistema de Diagnóstico de Marketing Digital

Guía para quien trabaje en este repositorio, sea una persona o Claude. Antes de cambiar algo, lee `docs/00_INDICE.md` y el documento del tema.

## Qué es

Es un sistema web para que NuevasTIC mida el nivel de marketing digital de las empresas:

- Cada empresa responde el diagnóstico de su sector.
- La IA analiza cada categoría.
- La empresa recibe un puntaje, un nivel, observaciones, recomendaciones y un PDF.
- El Administrador configura los sectores, las categorías, los diagnósticos, las empresas, las mediciones, la IA y las cuentas.

- **Equipo:** Cristian Andrés Penagos Simanca (frontend) y Luis Carlos Sánchez Muñoz (backend).
- **Plazo:** del 21 de septiembre al 6 de noviembre de 2026. Se entrega el repositorio; el sistema no se despliega.
- **Fuente de verdad del alcance:** `docs/05_REQUISITOS_FUNCIONALES.md`. Si otro documento lo contradice, manda ese.

## Stack

| Pieza | Versión | Para qué |
|---|---|---|
| PHP | 8.4 | Fijado en `composer.json` (`config.platform.php`). |
| Laravel | 13 | Backend, colas, correos, tareas programadas. |
| Inertia | 3 | Une Laravel y Vue sin una API aparte. |
| Vue | 3.5 + TypeScript | Pantallas, en `resources/js/pages`. |
| Tailwind CSS | 4 | Estilos. Los colores del wireframe están en `resources/css/app.css`. |
| Reka UI | 2 | Componentes accesibles del kit (diálogos, menús). |
| PostgreSQL | 18 | Base de datos. Usa JSONB para versiones congeladas y respuestas de la IA. |
| spatie/laravel-permission | 8.3 | Roles y permisos. |
| openai-php/laravel | 0.21 | Análisis de la IA. |
| spatie/laravel-pdf + Browsershot | 2.14 / 5.4 | PDF con Chromium. |
| Chart.js + vue-chartjs | 4.5 / 5.3 | Gráficas. |
| Pest | 5 | Pruebas del backend. |
| Vitest | 4.1 (incluido en Vite+) | Pruebas del frontend. |
| Node | 24 (`.nvmrc`) | Compilar el frontend y correr Browsershot. |

## Comandos

En Sail, antepón `./vendor/bin/sail` a cada comando (por ejemplo, `./vendor/bin/sail artisan test`).

| Para | Comando |
|---|---|
| Levantar el entorno | `./vendor/bin/sail up -d` |
| Pruebas del backend | `php artisan test` |
| Pruebas del frontend | `npm test` |
| Formato PHP | `composer lint` (Pint) |
| Análisis estático PHP | `composer types:check` (PHPStan / Larastan) |
| Formato y lint del frontend | `npm run check:fix` |
| Tipos del frontend | `npm run types:check` |
| Todo lo que corre CI | `composer ci:check` |
| Compilar el frontend | `npm run build` (o `npm run dev` mientras se programa) |

## Reglas del proyecto

1. **El idioma del dominio es el español.** Modelos, tablas, variables, componentes y textos de pantalla van en español, sin tildes en los identificadores (`Medicion`, `diagnosticos`, `EtiquetaNivel.vue`). El código del kit de Laravel conserva sus nombres en inglés.
2. **Los cálculos viven en el backend.** Laravel entrega los puntajes, el nivel y la variación ya calculados. Vue solo los muestra (regla de reparto en `docs/10_ARQUITECTURA.md`).
3. **Permisos en el servidor.** Toda ruta se protege con middleware de rol o permiso. Ocultar algo en el menú no basta. Matriz en `docs/17_SEGURIDAD.md`.
4. **Las versiones publicadas no se tocan.** Un diagnóstico publicado y un resultado publicado son inmutables (RN-010 y RN-023).
5. **Sin claves en el código.** Las claves van en `.env`; `.env.example` lleva solo placeholders.
6. **Nada se inventa.** Lo que no esté definido se marca **[INFORMACIÓN PENDIENTE]**, **[FUNCIONALIDAD POR DEFINIR]** o **[INCONSISTENCIA DETECTADA]**, en el documento o en un comentario.
7. **Cada pantalla se llama por su código del wireframe** (L1, A2.1, E6…), en la tarea de Trello, en el pull request y en el comentario del componente.
8. **Antes de terminar una tarea**, cumple la definición de terminado de `docs/28_CONVENCIONES_DESARROLLO.md`: pruebas, revisión en pull request, comparación con el wireframe y documento actualizado.

## Dónde está cada cosa

| Qué | Dónde |
|---|---|
| Componentes base (botón, tarjeta, modal, tabla, menú…) | `resources/js/components/base/` |
| Gráficas | `resources/js/components/graficas/` |
| Niveles y colores para el frontend | `resources/js/lib/niveles.ts` |
| Menú lateral por rol | `resources/js/lib/menu.ts` |
| Layout del panel | `resources/js/layouts/panel/PanelLayout.vue` |
| Servicios de la IA | `app/Services/Ia/` |
| Plantillas PDF | `resources/views/pdf/` |
| Prueba técnica (solo local) | `routes/prueba-tecnica.php`, `/prueba-tecnica/*` |
| Docker de Sail (PHP 8.4 + Chromium) | `docker/8.4/Dockerfile` |
| Documentación | `docs/` |
