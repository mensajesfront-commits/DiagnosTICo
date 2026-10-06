# Convenciones de desarrollo

**Estado:** EN CURSO (T-005, T-033)

## Definición de terminado

Una tarea de desarrollo está **terminada** cuando cumple todo esto:

1. **Tiene prueba.**
   - Las reglas y las rutas tienen al menos una prueba de Pest (`tests/Feature` o `tests/Unit`). Toda ruta protegida prueba que bloquea a quien no tiene permiso.
   - La lógica del frontend que decide algo (filtros, sumas, validaciones) tiene una prueba de Vitest (`resources/js/**/*.test.ts`).
2. **Pasa las revisiones automáticas:** `composer ci:check` termina sin errores y GitHub Actions queda en verde.
3. **Fue revisada en un pull request** por la otra persona del equipo antes de unirse a `develop`.
4. **Se probó contra el wireframe:**
   - La pantalla se compara con su código del prototipo (A2.1, E6…): textos, orden, estados vacíos y mensajes de error.
   - Las diferencias aceptadas se anotan en el pull request.
5. **El documento quedó actualizado:**
   - Si la tarea cambia una regla, una tabla, una variable de entorno o una decisión, se actualiza el documento de `docs/` correspondiente en el mismo pull request.
   - Se agrega la línea en `24_CAMBIOS_Y_VERSIONES.md`.

Una tarea de documentación está terminada cuando:

- tiene el estado arriba (COMPLETO o REQUIERE VALIDACIÓN);
- no inventa nada: lo que falta va marcado como **[INFORMACIÓN PENDIENTE]**;
- está enlazada en `00_INDICE.md`.

## Ramas

| Rama | Para qué | Reglas |
|---|---|---|
| `main` | Lo entregable | Protegida. Solo recibe merges desde `develop` en los hitos. |
| `develop` | Integración del trabajo de la semana | Protegida. Solo recibe pull requests revisados y con CI en verde. |
| `tarea/T-XXX-descripcion-corta` | Una tarea del cronograma | Sale de `develop` y vuelve por pull request. Ejemplo: `tarea/T-051-pantallas-acceso`. |
| `arreglo/descripcion-corta` | Corrección de un error | Igual que las ramas de tarea. |

**[INFORMACIÓN PENDIENTE]** La rama `develop` y la protección de `main` y `develop` se configuran en GitHub (T-015, a cargo de Luis).

## Commits

- En español, en imperativo y con la primera línea de 70 caracteres como máximo. Por ejemplo: `Agregar modal de crear sector (A2.2a)`.
- Si hace falta, cuerpo explicando el porqué y la tarea: `Tarea T-054.`
- Un commit hace una sola cosa. No se mezclan formato y lógica.

## Pull requests

- **Título:** `T-XXX · Descripción` (por ejemplo, `T-051 · Pantallas de acceso L1–L4`).
- **Descripción:**
  - qué cambia;
  - qué pantallas del wireframe toca;
  - cómo se probó;
  - qué documentos se actualizaron;
  - diferencias con el wireframe, si las hay.
- Lo aprueba la otra persona. No se une un pull request con CI en rojo.
- Después de unirlo, se mueve la tarjeta de Trello y se cambia el estado en el cronograma.

## Nombres

| Qué | Convención | Ejemplo |
|---|---|---|
| Modelos y clases PHP | Español, singular, PascalCase | `Medicion`, `AnalizadorDeCategoria` |
| Tablas | Español, plural, snake_case y sin tildes | `mediciones`, `diagnostico_categoria` |
| Columnas | Español, snake_case | `fecha_limite`, `archivado_en` |
| Rutas (URL) | Español, kebab-case | `/configuracion-ia`, `/prueba-tecnica/graficas` |
| Nombres de ruta | Español, con puntos | `prueba-tecnica.pdf` |
| Permisos | `recurso.accion` | `diagnosticos.publicar` |
| Componentes Vue | Español, PascalCase | `EtiquetaNivel.vue`, `MenuLateral.vue` |
| Páginas Inertia | Carpeta del módulo + nombre | `pages/diagnosticos/Editor.vue` |
| Variables y funciones TS | Español, camelCase | `filtrarMenu`, `fuentesListas` |
| Pruebas Pest | Frase en español | `it('bloquea la ruta para el rol Empresa')` |

El código que genera el kit de Laravel (autenticación y ajustes) conserva sus nombres en inglés. No se traduce para no romper las actualizaciones del kit.

Todo componente de pantalla lleva un comentario con el código del wireframe que implementa.

## Formato y revisión de código

| Herramienta | Qué revisa | Comando |
|---|---|---|
| **Pint** | Formato de PHP (configuración en `pint.json`) | `composer lint` / `composer lint:check` |
| **PHPStan + Larastan** | Errores de tipos en PHP, nivel 7 (`phpstan.neon`) | `composer types:check` |
| **Oxfmt + Oxlint** (vía Vite+) | Formato y errores de TypeScript y Vue. Hacen el trabajo de ESLint y Prettier. | `npm run check:fix` |
| **vue-tsc** | Tipos de TypeScript en los componentes | `npm run types:check` |

**[INCONSISTENCIA DETECTADA]** El cronograma (T-033) y la propuesta nombran ESLint y Prettier, pero el kit actual de Laravel trae Vite+, que usa Oxlint y Oxfmt para lo mismo. Se usan los del kit.

## Pruebas

- **Backend:** Pest 5, en `tests/`. Las pruebas de Feature usan una base PostgreSQL de pruebas (`testing`) que se limpia en cada prueba.
- **Frontend:** Vitest, en archivos `*.test.ts` junto al código.
- **Servicios externos:**
  - OpenAI se simula con `OpenAI::fake()` y los PDF con `Pdf::fake()`.
  - Las llamadas reales se prueban a mano con `php artisan prueba:ia` y `php artisan prueba:pdf`.
