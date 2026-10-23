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

## Decisiones de NuevasTIC (23 de octubre de 2026)

- La IA puede usar DOFA, PESTEL y los demás métodos: RN-028 se ajustó.
- Se aprueban las frases de los cuatro niveles.
- Preguntas opcionales: sin responder no se tienen en cuenta; respondidas son un dato extra que la IA lee, pero no cuentan en el puntaje.
- "Probar con un ejemplo": 100 pruebas al día entre todos los administradores (PA-003).
- Al borrar para siempre una empresa, sus mediciones terminadas se conservan sin nombre para estadísticas.
- La lista de sectores, actividades y categorías es la real.
- La contraseña sigue pidiendo carácter especial (RN-001).
- Los diagnósticos los genera NuevasTIC con IA y los carga a mano cuando se lance la aplicación.
- OpenAI lo paga NuevasTIC, con el modelo gpt-4.1-mini.
- El bot de WhatsApp lo hace otra persona del equipo, fuera de este repositorio.

## Pendiente con NuevasTIC

- **[INFORMACIÓN PENDIENTE]** El correo de soporte (`SOPORTE_EMAIL`).
- **[INFORMACIÓN PENDIENTE]** El día y la hora del resumen semanal (hoy, los lunes a las 7:50 a. m.).
- **[INFORMACIÓN PENDIENTE]** Las llaves reales de Cloudflare Turnstile (el captcha del registro).
- **[INFORMACIÓN PENDIENTE]** Los datos del responsable en la política de datos (razón social, NIT, domicilio, vigencia).
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
