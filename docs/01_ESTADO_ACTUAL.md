# Estado actual

**Estado:** EN CURSO (T-037). Se actualiza al cerrar cada semana.

**Última actualización:** 5 de octubre de 2026, al cierre de la semana 2 (entorno y prueba técnica).

## Resumen

El repositorio tiene:

- el proyecto base (Laravel 13 con el kit de Vue);
- el entorno de Sail;
- las librerías del stack;
- la prueba técnica;
- los estilos del wireframe;
- los componentes base;
- la primera documentación.

Todavía no hay migraciones ni pantallas del negocio. Eso empieza en la semana 3.

## Semana 2: tareas "En curso"

| Tarea | Qué quedó | Pendiente |
|---|---|---|
| T-005 Definición de terminado | `28_CONVENCIONES_DESARROLLO.md` | Que el equipo la apruebe |
| T-006 Matriz de roles y permisos | `17_SEGURIDAD.md` (propuesta de 15 permisos en 6 bloques) | Compararla con el wireframe A5.2 |
| T-007 MER | `12_BASE_DE_DATOS.md` (borrador en Mermaid) | Revisión T-039 y aprobación T-044 |
| T-015 Repositorio | Repositorio en GitHub con el código | Crear `develop` y proteger `main` y `develop` en GitHub |
| T-016 Proyecto y Sail | Kit de Vue + Sail (PHP 8.4, PostgreSQL 18, Mailpit, Chromium) | Construir la imagen en los equipos (ISSUE-009) |
| T-017 Versiones fijas | `composer.lock`, `package-lock.json`, `.nvmrc` (24) | — |
| T-018 Prueba de permisos | Rol, ruta bloqueada y pruebas | — |
| T-019 Prueba de OpenAI | Servicio, comando `prueba:ia` y pruebas con la API simulada | Correr `php artisan prueba:ia` con una clave real |
| T-020 WhatsApp | No se hizo: el bot está aplazado | Decidir si se saca del cronograma |
| T-021 Instalar desde el README | README escrito; instalación desde cero probada fuera de Sail | Que Cristian la repita en su equipo con Sail |
| T-022 Tailwind del wireframe | Colores, niveles e IBM Plex en `resources/css/app.css` | — |
| T-023 Componentes base | Menú lateral, layout, modal, selector, botones, etiquetas, tarjetas y tablas | Revisarlos contra el prototipo en `/prueba-tecnica/componentes` |
| T-024 Radar de prueba | `RadarCategorias.vue` en `/prueba-tecnica/graficas` | — |
| T-025 PDF de prueba | PDF con el radar (`prueba:pdf` y `/prueba-tecnica/pdf`) | — |
| T-026 a T-037 Documentación | `CLAUDE.md`, `README.md`, `.env.example` y los documentos de `docs/` del índice | Notas de la reunión T-008 en `03_ALCANCE.md` |
| T-038 Prueba técnica aprobada | Todo menos WhatsApp (aplazado) y la llamada real a OpenAI | Correr `prueba:ia` con una clave real y aprobar el hito |

## Cómo verificar lo hecho

```bash
./vendor/bin/sail artisan test        # 38 pruebas de Pest
./vendor/bin/sail npm test            # pruebas de Vitest
./vendor/bin/sail artisan prueba:pdf  # PDF real en storage/app/private/pruebas/
```

Para ver las pantallas de la prueba técnica, entra como un usuario con rol Administrador a:

- `/prueba-tecnica/componentes`
- `/prueba-tecnica/graficas`

## Bloqueos y decisiones pendientes

- **Bot de WhatsApp:** aplazado. El cronograma todavía lo tiene en las semanas 2, 6 y 7 (`03_ALCANCE.md`).
- **Clave de OpenAI y modelo por defecto:** sin definir.
- **Contraseña:** RN-001 pide un carácter especial que los wireframes L2 y L4 no muestran (`17_SEGURIDAD.md`).
- **Cronograma desactualizado:** todavía habla de FODA, de 3 etapas de IA y de un PDF de 4 páginas (T-098, T-099, T-113, T-122, T-127). Los requisitos vigentes dicen una sola etapa y un PDF de 3 páginas, sin FODA.
