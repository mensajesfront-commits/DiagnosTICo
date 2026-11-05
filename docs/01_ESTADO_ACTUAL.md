# Estado actual

**Estado:** COMPLETO (T-037). Estado del proyecto en la entrega `v1.0.0` (5 de noviembre de 2026). El detalle de cada cambio está en `24_CAMBIOS_Y_VERSIONES.md`.

**Última actualización:** 5 de noviembre de 2026, antes de la entrega.

## Resumen

**El sistema está completo en funcionalidad.** De las 84 historias, 79 están hechas en este repositorio. Las 5 del bot de WhatsApp (HU-071 a HU-075) las terminó otra persona del equipo, fuera de este repositorio (`05_REQUISITOS_FUNCIONALES.md`).

**Se va a desplegar** (cambio del 4 de noviembre; antes solo se entregaba el repositorio). Pasos en `20_DESPLIEGUE.md`.

Lo construido, de punta a punta:

- **Acceso:** iniciar sesión, registrarse, recuperar la contraseña y Mi perfil (L1–L4, A6, E11).
- **Inicio del Administrador:** indicadores, mediciones por estado con sus acciones y solicitudes de nueva medición (A1, HU-083).
- **Diagnósticos:**
  - sectores y catálogo de categorías;
  - editor con versiones;
  - importancia, vista previa y resultado de ejemplo (A2–A2.7).
- **Empresas y mediciones:** lista, ficha, registrar, asignar con correo editable, historial, resultado y respuestas (A3–A3.5).
- **Configuración de la IA** por capas, con prueba real y "Cómo se arma" (A4–A4.9).
- **Usuarios y roles** con "Ver como" de solo lectura (A5–A5.5), y el rol **Consultor**: externo, de solo lectura, solo para las empresas que se le vinculan (HU-085, RN-030).
- **Experiencia de la empresa:**
  - inicio, responder con guardado automático, revisar y enviar;
  - avance del análisis;
  - resultado con el método de análisis;
  - respuestas, PDF, historial y colaboradores (E1–E12).
- **Dos PDF por resultado (RN-024):**
  - la empresa recibe un **resumen corto** con lo que dijo la IA, en orden;
  - el Administrador y el Consultor descargan además el **informe completo**.
- **Textos legales oficiales de Captter** en Crear cuenta (L2) y correo de soporte **contacto@captter.com**.
- **Automático:**
  - cálculo de puntajes y análisis de la IA por categoría con 3 intentos;
  - resultado inmutable y los dos PDF;
  - avisos por correo según Mi perfil;
  - tareas diarias y resumen semanal.

**Pruebas:** 385 de Pest y 47 de Vitest. Todo pasa, igual que Pint, PHPStan, el lint y los tipos del frontend. `composer audit` y `npm audit` sin alertas.

**Auditoría QA (4 de noviembre):** se corrigieron los 14 hallazgos (ERR-01 a ERR-14), cada uno con su prueba en `tests/Feature/AuditoriaQaTest.php`. Entre ellos:
- correos repetidos por mayúsculas;
- enlaces inyectados en los correos;
- cabeceras de seguridad;
- el enlace de recuperación, que ahora vence a los **10 minutos** (la invitación sigue hasta usarla);
- las dependencias con vulnerabilidades.

Detalle en `24_CAMBIOS_Y_VERSIONES.md`.

**Documentación:** los 32 documentos del índice están en COMPLETO. NuevasTIC aprobó los de negocio el 5 de noviembre de 2026 (`00_INDICE.md`).

## Reparto del trabajo

- **Cristian:** frontend y documentación. Revisa cada pantalla contra el wireframe.
- **Luis:** backend. Revisa en el *pull request* el backend que se hizo desde el frontend para conectar las pantallas.

## Qué falta hasta la entrega (6 de noviembre)

| Qué | Quién | Notas |
|---|---|---|
| ~~Unir a `develop` la rama `claude/zealous-babbage-7s6joa` (PR #11)~~ | Luis | **Hecho** el 5 de noviembre. El último commit (logotipo en los PDF, despliegue y bot) va en un PR nuevo. Después de actualizar: `php artisan migrate` (3 migraciones del 4 de noviembre) |
| ~~Recorrer el flujo completo con la clave real de OpenAI~~ | Cristian | **Hecho**: funciona (confirmado el 5 de noviembre) |
| ~~Revisar a ojo los dos PDF~~ | Cristian | **Hecho** el 4 de noviembre |
| Desplegar en el servidor | Equipo | `20_DESPLIEGUE.md`. **[INFORMACIÓN PENDIENTE]** Servidor y dominio |
| Revisión pantalla por pantalla (diseño, textos y funciones) | Cristian | Los ajustes se anotan en `18_UI_UX.md` |
| Instalar desde cero con el README en un equipo limpio (T-021) | Equipo | Con Sail |
| ~~PR de `develop` a `main` y etiqueta `v1.0.0` (T-152)~~ | Equipo | **Hecho** el 5 de noviembre: PR #13 y etiqueta `v1.0.0` sobre `main` |
| ~~Cerrar los documentos EN CURSO y validar los REQUIERE VALIDACIÓN (T-160)~~ | Equipo y NuevasTIC | **Hecho** el 5 de noviembre |

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
- El bot de WhatsApp lo hace otra persona del equipo, fuera de este repositorio. **Terminado** (confirmado el 4 de noviembre).
- El resumen semanal se queda como está: los lunes a las 7:50 a. m.

## Confirmado el 4 y 5 de noviembre

- **Llaves reales de Cloudflare Turnstile:** ya existen. Van solo en el `.env` del servidor.
- **Logotipo de NuevasTIC:** es el símbolo del Login (aro azul con la "C" y el punto verde). Ya va en la portada de los dos PDF (`pdf/logo.blade.php`).
- **Textos legales:** los Términos y Condiciones y la Política de Privacidad son los oficiales de la página de Captter y es todo lo que entregó la empresa. **[INCONSISTENCIA DETECTADA]** No coinciden del todo con el sistema (pagos, cookies, OpenAI y Cloudflare sin nombrar); queda anotado en docs/17 por si la empresa los ajusta después.
- **Diagnósticos reales:** la empresa los carga a mano.
- **Bot de WhatsApp:** terminado, fuera de este repositorio. **[INFORMACIÓN PENDIENTE]** Si se conecta con este sistema y cómo.
- **Instrucciones de la IA:** las de fábrica las escribió el equipo; NuevasTIC las ajusta cuando quiera en A4 (permiso «Editar prompts»). Los cambios solo aplican a los análisis nuevos, y cada análisis guarda el texto que se usó. El texto de fábrica está en `App\Services\Ia\ArmadorPrompt::GENERAL`.

## Pendiente con NuevasTIC

- **[INFORMACIÓN PENDIENTE]** El servidor y el dominio donde se despliega.
- **Cronograma desactualizado:** todavía habla de FODA, de 3 etapas de IA y de un PDF de 4 páginas (T-098, T-099, T-113, T-122, T-127). Lo vigente es una etapa por categoría y los dos PDF de RN-024.

## Cómo verificar lo hecho

```bash
./vendor/bin/sail artisan test        # 385 pruebas de Pest
./vendor/bin/sail npm test            # 47 pruebas de Vitest
./vendor/bin/sail composer ci:check   # todo lo que corre CI
```

**Para recorrer el flujo completo** hace falta la clave de OpenAI en `.env` (`OPENAI_API_KEY` y `OPENAI_MODEL`) y la cola corriendo (el servicio `queue` de `compose.yaml`). No hay modo sin IA: cada medición gasta una llamada por categoría.

## Semanas anteriores

| Semana | Qué quedó |
|---|---|
| 1–2 (21 sep–4 oct) | Proyecto base, Sail, versiones fijas, prueba técnica (permisos, gráficas, PDF), estilos y componentes base, documentación inicial |
| 3 (5–11 oct) | MER migrado completo, datos iniciales, acceso, Mi perfil, diagnósticos, sectores y categorías (A2), usuarios y roles (A5), colaboradores (E12) |
| 4 (12–18 oct) | A1, empresas y mediciones (A3), editor de diagnósticos, la empresa responde (E1–E4), IA y resultados, configuración de la IA, historial, "Ver como", métodos de análisis, resultado de ejemplo, HU-083, opción "Otro", avisos de Mi perfil y los documentos que faltaban |
| 5–7 (19 oct–4 nov) | Rol Consultor, plantilla de correo de Captter, informe PDF reestructurado y resumen corto para la empresa, auditoría QA con sus 14 correcciones, enlace de recuperación de 10 minutos, dependencias sin alertas (vite-plus 1.5), textos legales oficiales y correo de soporte |
