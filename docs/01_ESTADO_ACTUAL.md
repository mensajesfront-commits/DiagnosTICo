# Estado actual

**Estado:** EN CURSO (T-037). Se actualiza al cerrar cada semana. El detalle de cada cambio está en `24_CAMBIOS_Y_VERSIONES.md`.

**Última actualización:** 14 de octubre de 2026 (semana 4).

## Resumen

**El sistema está completo en funcionalidad.** De las 84 historias, 79 están hechas y las 5 del bot de WhatsApp (HU-071 a HU-075) están aplazadas (`05_REQUISITOS_FUNCIONALES.md`).

Lo construido, de punta a punta:

- **Acceso:** iniciar sesión, registrarse, recuperar la contraseña y Mi perfil (L1–L4, A6, E11).
- **Inicio del Administrador:** indicadores, mediciones por estado con sus acciones y solicitudes de nueva medición (A1, HU-083).
- **Diagnósticos:**
  - sectores y catálogo de categorías;
  - editor con versiones;
  - importancia, vista previa y resultado de ejemplo (A2–A2.7).
- **Empresas y mediciones:** lista, ficha, registrar, asignar con correo editable, historial, resultado y respuestas (A3–A3.5).
- **Configuración de la IA** por capas, con prueba real y "Cómo se arma" (A4–A4.9).
- **Usuarios y roles** con "Ver como" de solo lectura (A5–A5.5).
- **Experiencia de la empresa:**
  - inicio, responder con guardado automático, revisar y enviar;
  - avance del análisis;
  - resultado con el método de análisis;
  - respuestas, PDF, historial y colaboradores (E1–E12).
- **Automático:**
  - cálculo de puntajes y análisis de la IA por categoría con 3 intentos;
  - resultado inmutable y PDF de 3 páginas;
  - avisos por correo según Mi perfil;
  - tareas diarias y resumen semanal.

**Pruebas:** 245 de Pest y 35 de Vitest. Todo pasa, igual que Pint, PHPStan, el lint y los tipos del frontend.

**Documentación:** todos los documentos del índice tienen archivo. Los que dicen REQUIERE VALIDACIÓN esperan la revisión de NuevasTIC o del equipo (`00_INDICE.md`).

## Reparto del trabajo

- **Cristian:** frontend y documentación. Revisa cada pantalla contra el wireframe.
- **Luis:** backend. Revisa en el *pull request* el backend que se hizo desde el frontend para conectar las pantallas.

## Qué falta hasta la entrega (6 de noviembre)

| Qué | Quién | Notas |
|---|---|---|
| Revisión pantalla por pantalla (diseño, textos y funciones) | Cristian | Los ajustes se anotan en `18_UI_UX.md` |
| Unir a `develop` los cambios de la rama de trabajo | Luis revisa el PR | CI en verde |
| Instalar desde cero con el README en un equipo limpio (T-021) | Equipo | Con Sail |
| PR de `develop` a `main` y etiqueta `v1.0.0` (T-152) | Equipo | `20_DESPLIEGUE.md` |
| Cerrar los documentos EN CURSO y validar los REQUIERE VALIDACIÓN (T-160) | Equipo y NuevasTIC | `00_INDICE.md` |

## Decisiones pendientes con NuevasTIC

- **[INCONSISTENCIA DETECTADA]** RN-028 (sin FODA) frente a DEC-019, que deja que la IA use DOFA y otros métodos.
- **[INFORMACIÓN PENDIENTE]** Para que NuevasTIC lo confirme:
  - las frases de cada nivel;
  - las preguntas opcionales sin responder, que no cuentan en el promedio;
  - el límite diario de "Probar con un ejemplo" (PA-003);
  - el correo de soporte (`SOPORTE_EMAIL`);
  - el día y la hora del resumen semanal.
- **[INFORMACIÓN PENDIENTE]** Para que NuevasTIC lo entregue:
  - los diagnósticos reales de cada sector (T-072);
  - quién paga OpenAI y con qué modelo.
- **Bot de WhatsApp:** sigue aplazado. Hay que decidir si se saca del cronograma (`03_ALCANCE.md`).
- **Cronograma desactualizado:** todavía habla de FODA, de 3 etapas de IA y de un PDF de 4 páginas (T-098, T-099, T-113, T-122, T-127). Lo vigente es una etapa por categoría y un PDF de 3 páginas.

## Cómo verificar lo hecho

```bash
./vendor/bin/sail artisan test        # 245 pruebas de Pest
./vendor/bin/sail npm test            # 35 pruebas de Vitest
./vendor/bin/sail composer ci:check   # todo lo que corre CI
```

**Para recorrer el flujo completo sin clave de OpenAI**, pon `IA_DE_PRUEBA=true` en `.env` y deja corriendo `./vendor/bin/sail artisan queue:work`.

## Semanas anteriores

| Semana | Qué quedó |
|---|---|
| 1–2 (21 sep–4 oct) | Proyecto base, Sail, versiones fijas, prueba técnica (permisos, gráficas, PDF), estilos y componentes base, documentación inicial |
| 3 (5–11 oct) | MER migrado completo, datos iniciales, acceso, Mi perfil, diagnósticos, sectores y categorías (A2), usuarios y roles (A5), colaboradores (E12) |
| 4 (12–18 oct) | A1, empresas y mediciones (A3), editor de diagnósticos, la empresa responde (E1–E4), IA y resultados, configuración de la IA, historial, "Ver como", métodos de análisis, resultado de ejemplo, HU-083, opción "Otro", avisos de Mi perfil y los documentos que faltaban |
