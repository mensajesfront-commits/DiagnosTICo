# Cambios y versiones

**Estado:** COMPLETO (T-037) hasta la versión `v1.1.0`. Lo más nuevo va arriba; los cambios siguientes se anotan como una versión nueva.

La versión de entrega es `v1.0.0` (T-152); hasta ella, los cambios se agrupan por semana del cronograma. La versión que corre en el servidor es `v1.1.0`.

| Versión | Fecha | Commit en `main` | Qué es |
|---|---|---|---|
| `v1.1.0` | 6 de noviembre de 2026 | `c419a43` | Lo que se despliega en https://diagnostico.captter.com: `v1.0.0` más el despliegue con Docker |
| `v1.0.0` | 5 de noviembre de 2026 | `2838f4a` | La versión de entrega del proyecto |

Las etiquetas no se mueven: un cambio nuevo lleva una versión nueva (`v1.1.1` para una corrección, `v1.2.0` para algo nuevo).

## Después de v1.1.0 · mejoras pedidas por el equipo (10 de noviembre de 2026, en curso)

### Agregado · A2.5 «Pegar el contenido» (HU-020 CA-005)

- Tercera forma de crear un diagnóstico, junto a «En blanco» y «Copiar uno publicado»: se pega todo el diagnóstico escrito en una plantilla de texto y el borrador se crea lleno.
- **Plantilla:**
  - `CATEGORÍA:` con el nombre del catálogo e `IMPORTANCIA:` opcional; sin importancia en ninguna categoría, se reparte en partes iguales.
  - Cada pregunta con `PREGUNTA:`, `TIPO:` (Abierta, Opción única o Selección múltiple), `OBLIGATORIA:`, `INDICACIÓN:` y, en las abiertas, `CRITERIO:`.
  - Las opciones van debajo de `OPCIONES:` como `- puntaje | texto`, con `OTRO`, `NINGUNA` y `TEN EN CUENTA: …`.
  - Las etiquetas no distinguen mayúsculas ni tildes, y un texto puede ocupar varias líneas.
- **Revisar antes de crear:** `POST /diagnosticos/plantilla/revisar` lee el texto sin guardar nada y devuelve:
  - lo que entendió;
  - los errores que impiden crearlo (categoría fuera del catálogo, tipo desconocido, puntaje fuera de 0–100, límites del editor);
  - los avisos de lo que falta para publicar (RN-012).
  Cada uno lleva su número de línea.
- **Código:**
  - `App\Services\Diagnosticos\LectorPlantilla` convierte el texto en el mismo contenido que usa «Copiar» (`ContenidoDiagnostico::aplicar`).
  - Los avisos salen de `EstadoDiagnostico`, igual que en el editor.
- Pruebas en `tests/Feature/PlantillaDiagnosticoTest.php` (9). Docs 05, 14, 15, 18, 19, 29 y 31.
- No se tocó nada del correo.

### Agregado · las vencidas canceladas salen en el historial (HU-067 CA-006, HU-039 CA-005)

- Antes, una medición cancelada desaparecía del historial. Ahora, si había vencido sin enviarse, sale en E8 y A3.4 entre las terminadas, por fecha, como «Vencida sin responder · cancelada». Sirve de prueba si la empresa dice que no recibió el diagnóstico.
- Muestra cuándo se asignó, la fecha límite, cuándo se canceló, cuántas preguntas se respondieron y el último correo de aviso. En A3.4, además, quién la asignó y quién la canceló.
- **Base de datos:** migración `2026_11_10_000001`, con dos columnas nuevas en `mediciones`:
  - `estado_al_cancelar`, que guardan «Cancelar medición» y «Reemplazar»;
  - `cancelada_por`.
  Las canceladas antes de esta migración cuentan como vencidas si su fecha límite ya había pasado ese día.
- Las canceladas antes de vencer no salen. Las cifras de arriba y la gráfica siguen contando solo las terminadas.
- Código: `DatosHistorial::vencidas`. Pruebas en `HistorialTest` (3). Docs 05, 12, 14, 19 y 31.
- **Al actualizar el servidor:** `./deploy-production.sh --migrate`, porque hay una migración nueva.

### Corregido · la IA y las abiertas opcionales (ISSUE-013)

- El análisis de una categoría fallaba cuando la empresa respondía una pregunta abierta **opcional**. La IA, siguiendo el criterio «No se califica», devolvía puntaje `null` y el sistema rechazaba la respuesta y reintentaba hasta fallar.
- Ahora a la opcional se le pide no calificarla (`ArmadorPrompt::preguntas`) y `AnalizadorDeCategoria::validar` la acepta sin puntaje; la obligatoria sigue necesitando su puntaje.
- El texto de fábrica de la Tarea (paso 2) también lo dice. Si en A4 se guardaron instrucciones generales propias, conservan su texto, pero la corrección funciona igual, porque va en cada pregunta y en la validación.
- Pruebas nuevas en `ResultadosTest` (2).

## v1.1.0 · despliegue en el servidor (6 de noviembre de 2026)

- **Etiqueta `v1.1.0` sobre `main`** (commit `c419a43`, después de los PR #16 a #19). La crea el equipo en GitHub (Releases → *Draft a new release*). `v1.0.0` no se mueve: sigue marcando lo entregado el 5 de noviembre.
- **Al actualizar el servidor:** no hay migraciones nuevas desde `v1.0.0`. La primera instalación sigue `20_DESPLIEGUE.md`; las siguientes, `./deploy-production.sh`.
- **PR #18:** el puerto por defecto de `web` pasa a 8200 y `APP_URL` a `https://diagnostico.captter.com` en `.env.production.example`. **PR #19:** `develop` a `main`.

- **PR #17 a `main`** (Luis Carlos): estructura de producción igual a la de Kiasmer (DEC-024).
  - `docker-compose.prod.yml`: `app`, `web` (solo en `127.0.0.1:${WEB_PORT}`), `queue`, `scheduler`, `pgsql` y los comandos `migrate` y `artisan`.
  - `docker/php/Dockerfile.prod` con las imágenes `app` (PHP-FPM 8.4, Chromium y Puppeteer) y `web` (Nginx), más `entrypoint.prod.sh`, `php.ini`, `opcache.prod.ini`, `www.conf` y `docker/nginx/default.prod.conf`.
  - `deploy-production.sh`, `.env.production.example` y `.dockerignore`.
  - `trustProxies` en `bootstrap/app.php`; docs/20 con la instalación, la actualización y los VirtualHost de Apache y Nginx.
- **En el PR #16:** `URL::forceScheme('https')` cuando `APP_URL` empieza por `https://`, y docs/16, docs/22 (DEC-024), docs/27 y README alineados con el PR #17.
- **Servidor:** https://diagnostico.captter.com. Apache tiene el SSL y reenvía a `127.0.0.1:8200`, así que el `.env` del servidor lleva `WEB_PORT=8200` (el valor de `.env.production.example` desde el PR #18).
- Una primera versión de estos archivos, escrita en el PR #16, se quitó a favor de la del PR #17, que sí se construyó y probó.

## v1.0.0 · versión de entrega (5 de noviembre de 2026)

- `develop` se unió a `main` con el PR #13 (commit `2838f4a`), después de #11 y #12. CI en verde.
- Trae todo lo de las secciones de abajo:
  - rediseño de las pantallas;
  - rol Consultor;
  - auditoría QA con sus 14 correcciones;
  - dos PDF por resultado;
  - textos legales oficiales de Captter y correo de soporte contacto@captter.com.
- Al actualizar un equipo o el servidor: `composer install`, `npm install`, `php artisan migrate` (3 migraciones del 4 de noviembre) y `npm run build`.
- Etiqueta `v1.0.0` sobre `main` (commit `2838f4a`), creada por el equipo el 5 de noviembre.
- **Documentación cerrada:** los 32 documentos quedaron en COMPLETO. NuevasTIC aprobó los de negocio el 5 de noviembre (02 a 08, 18, 25 y 31).
- **Documentación revisada a fondo (5 de noviembre):** cada documento se puso al día con lo hecho después del 14 de octubre:
  - el Consultor: EP-014, HU-085 y HU-086, RN-030, CU-007, C1 y C2, y una parte nueva del manual;
  - los dos PDF y el logotipo;
  - la auditoría QA: middlewares, cabeceras y enlaces de 10 minutos;
  - las tablas y columnas nuevas en docs/12 (`consultor_empresa`, `invitaciones_tokens`, `pdf_resumen_ruta`, verificación del correo);
  - las pruebas por archivo;
  - las decisiones DEC-021 a DEC-023;
  - los problemas ISSUE-010 a ISSUE-012;
  - el despliegue, el bot terminado y los totales (14 épicas, 86 historias, 340 puntos).

## Semana 4 · del 12 al 18 de octubre de 2026 (en curso)

### Cambiado (cierre del 4 de noviembre)

- **Logotipo de NuevasTIC en los PDF:** el símbolo del Login (aro azul con la "C" y el punto verde) va en la portada del informe completo y en la cabecera del resumen (`pdf/logo.blade.php`), en lugar del nombre en texto.
- **Se despliega:** DEC-006 cambió. CLAUDE.md, docs/20, docs/22 y docs/25 lo dicen. **[INFORMACIÓN PENDIENTE]** Servidor y dominio.
- **Bot de WhatsApp terminado** fuera de este repositorio. HU-071 a HU-075 pasan a "Hecha fuera de este repositorio" en docs/05.
- **docs/01:** las llaves de Turnstile ya existen, los PDF están revisados y los textos legales son todo lo que entregó la empresa.

### Cambiado (correo de soporte, 4 de noviembre)

- El correo de soporte es **contacto@captter.com**: `SOPORTE_EMAIL` en `.env.example` y el valor por defecto en `config/diagnostico.php`.
- Las pantallas ya no lo tienen escrito. Lo leen de `SOPORTE_EMAIL`, que se comparte con todas las páginas (`HandleInertiaRequests`, `useCorreoSoporte`):
  - panel de acceso (L1–L4), cuenta desactivada y páginas de error;
  - inicio de la empresa (E1) y responder;
  - el PDF y el correo de "empresa desactivada".

### Cambiado (L2 · textos legales oficiales, 4 de noviembre)

- Crear cuenta muestra los **Términos y Condiciones** (16 de septiembre de 2025) y la **Política de Privacidad** (27 de mayo de 2025) oficiales de Captter, copiados tal cual en `resources/js/lib/legales.ts`. Reemplazan el borrador anterior.
- La casilla dice "Acepto los Términos y Condiciones y la Política de Privacidad". La ventana muestra la fecha de "Última actualización" y las subsecciones (1.1, 1.1.1) con su propio título.
- **[INCONSISTENCIA DETECTADA]** Los textos hablan de pagos, cookies de analítica y datos que el sistema no pide, y no nombran a OpenAI ni a Cloudflare. Detalle en docs/17. La empresa decide si los ajusta.

### Agregado (E10 · PDF corto para la empresa, 4 de noviembre)

Un empresario no va a leer un informe de 36 páginas (RN-024 y HU-066 actualizadas).

- **Resumen para la empresa** (`pdf/resumen.blade.php`):
  - calificación, nivel, cómo leer los niveles y las categorías en barras;
  - «Por dónde empezar»;
  - por categoría, de la más urgente a la más fuerte: qué encontramos, por qué importa, lo que ya haces bien, lo que debes mejorar y qué hacer (pasos y plazo);
  - próximos pasos.
  - Copia lo que la IA ya guardó; no la vuelve a llamar. Usa los nombres de nivel de la pantalla. Con 6 categorías sale de unas 6 páginas.
- **Quién recibe cuál:**
  - la empresa y su colaborador reciben siempre el resumen, aunque pidan el completo en la URL;
  - el Administrador y el Consultor descargan el completo por defecto y el resumen con `?tipo=resumen`;
  - A3.3 tiene los botones «Resumen para la empresa» e «Informe completo PDF»; A2.6, «Ver resumen de la empresa».
- **Guardado:** columna `resultados.pdf_resumen_ruta` (migración `2026_11_04_000003`). `GenerarInformePdf` genera los dos al publicar, y la descarga genera el que falte. Al anonimizar se borran los dos.

### Agregado (cierre funcional, 14 de octubre)

- **HU-083 · Atender las solicitudes de nueva medición:**
  - aviso en A1 ("N empresas pidieron una medición nueva"), con esas filas primero en Vencidas y "Asignar nueva";
  - aviso en A3, en la ficha, en A3.4 y en Asignar medición.
  - Asignar otra medición, darle nueva fecha a la vencida o cancelarla deja la solicitud atendida.
- **Opción "Otro" que pide escribir cuál:** se elige en A2.1b (se marca sola si empieza por "Otro"). En E2 aparece "¿Cuál?" y la IA lee "Otro: lo que escribió". Columna `opciones.con_texto`.
- **"Ver:" en la gráfica de evolución** (A3.1, A3.4, E8): puntaje total o una categoría.
- **A5:** la cuenta principal de una empresa dice qué medición pendiente tiene, para el aviso al desactivarla o cambiarle el rol.
- **Avisos de Mi perfil (A6, E11):**
  - cada correo respeta lo que eligió quien lo recibe;
  - correo nuevo "Una empresa envió su diagnóstico";
  - resumen semanal de mediciones pendientes (`mediciones:resumen`, lunes 07:50).
- **Documentos nuevos:**
  - 07 Reglas de negocio;
  - 11 Diseño técnico;
  - 13 API e integraciones;
  - 16 Infraestructura;
  - 19 Pruebas;
  - 20 Despliegue;
  - 21 Mantenimiento;
  - 25 Roadmap;
  - 29 Funcionalidades;
  - 31 Manual de usuario.
- Estado de cada historia actualizado en `05_REQUISITOS_FUNCIONALES.md`.

### Agregado (verificación del correo, 16 de octubre)

- **L2b · Verifica tu correo** (DEC-020):
  - **Captcha** (Cloudflare Turnstile) antes de "Registrar empresa".
  - Al registrarse no se entra al sistema: se abre una ventana para escribir el **código de 6 dígitos** que llega por correo. Vence en 15 minutos, admite 5 intentos y tiene "Reenviar código".
  - Al acertar, lleva a Iniciar sesión con el correo escrito.
  - Si se intenta entrar sin verificar, se abre la misma ventana en L1.
  - Los registros sin verificar se borran a los 7 días (`cuentas:purgar`).
  - **Mi perfil:** cambiar el correo también pide el código, enviado al correo nuevo.

### Agregado (diagnósticos archivados, 16 de octubre)

- **A2 · Archivados (N):** junto al título de la tabla se abre la ventana con los diagnósticos archivados del sector o de todos.
  - **Restaurar** los devuelve a publicados.
  - **Eliminar** los borra para siempre, con su historial de versiones y doble confirmación. Solo se puede si ninguna empresa los respondió, porque esos resultados se hicieron con ese diagnóstico (RN-010, RN-023).

### Agregado (cargo del dueño único, 16 de octubre)

- **El cargo del dueño de la empresa no se repite.** Ningún colaborador puede tener el cargo de la cuenta principal, ni al crearlo o editarlo (E12) ni desde su Mi perfil (E11). Tampoco el dueño puede tomar el cargo de un colaborador (E11, A3.1d). Se compara sin mayúsculas, tildes ni espacios de más (regla `CargoDelDueno`). Los cargos de los demás colaboradores sí se pueden repetir.

### Agregado (términos de uso y política de datos, 16 de octubre)

- **Borrador de los términos de uso y de la política de tratamiento de datos** (Ley 1581 de 2012), escrito con lo que el sistema hace de verdad: qué datos guarda, que las respuestas van a OpenAI sin el nombre de la empresa, Cloudflare en el captcha, los 7 días sin verificar, los 90 días para recuperar una cuenta, "Ver como" y los plazos para consultas y reclamos.
- Solo están en L2: se abren en una ventana sin salir del formulario.
- Lo que NuevasTIC no ha entregado (razón social, NIT, domicilio, vigencia, costo, proveedores) va marcado y resaltado como [INFORMACIÓN PENDIENTE].

### Agregado (contraseña nueva más segura, 17 de octubre)

- **La contraseña no puede tener partes del correo.** De "juan.perez1102@empresa.co" no se pueden usar "juan", "perez", "1102" ni "empresa", sin importar mayúsculas ni tildes. Las partes de menos de 3 caracteres no cuentan. Aplica en el registro (L2), la contraseña nueva (L4), Mi perfil y los colaboradores (E12). La lista de requisitos lo marca en vivo ("Sin partes de tu correo").
- **La contraseña nueva debe ser distinta de la anterior** (L4, Mi perfil y editar colaborador). Esto lo revisa solo el servidor.
- El correo de "¿Olvidaste tu contraseña?" ahora dice "Cambia tu contraseña" y su botón, "Cambiar contraseña".

### Cambiado (botones según los permisos, 26 de octubre)

- Cada pantalla oculta los botones que el rol de la cuenta no permite (`usePermisos`): Empresas (lista, ficha y Datos de la cuenta en solo lectura), Inicio, Diagnósticos, catálogo, editor y vista previa, resultados y PDF, Usuarios y roles.
- Corregido: "Publicar versión" ahora pide el permiso "Publicar versiones" (`diagnosticos.publicar`); antes bastaba con "Editar".
- Corregido (A5.1c): al desmarcar "Rol activo" y guardar no pasaba nada ni salía un mensaje. Ahora se muestra el error del servidor.

### Corregido (auditoría QA, 4 de noviembre)

Hallazgos de la auditoría QA (informe aparte). Cada corrección tiene su prueba en `tests/Feature/AuditoriaQaTest.php`.

- **ERR-02 · Correos repetidos por mayúsculas:** se podía invitar `SOFIA@ElTrigal.co` aunque existiera `sofia@eltrigal.co`.
  - El middleware `NormalizarCorreo` pasa el campo `email` a minúsculas antes de validar, y `User` lo guarda así.
  - Índice único `lower(email)`: la migración `2026_11_04_000001` se detiene y lista los correos repetidos si ya los hay.
- **ERR-01 · Error 500 con un correo muy largo en el login:** las llaves de los dos contadores de intentos (`login` y el de Fortify, `LimitadorLogin`) usan un hash del correo; `cache.key` es de 255 caracteres.
- **ERR-03 · Enlaces inyectados en los correos:** un nombre como `[Haz clic](https://…)` se volvía un enlace real. Los correos usan `App\Notifications\Correo`, que escapa el Markdown de cada línea y del saludo.
- **ERR-06 · Cabeceras de seguridad:** `CabecerasSeguridad` manda `X-Frame-Options`, `frame-ancestors`, `nosniff`, `Referrer-Policy`, `Permissions-Policy` y, con HTTPS, HSTS.
- **ERR-04 · Sectores y categorías repetidos por mayúsculas:** regla `NombreUnico`, que no mira mayúsculas ni espacios de más (como los diagnósticos).
- **ERR-05 · Mensajes en inglés:** `lang/es/validation.php` tiene ya todas las reglas de Laravel y nombres para `empresas.*`, `opciones.*` y `paginas.*`. "El :attribute elegido" pasó a "El valor elegido en :attribute", sin género.
- **ERR-12 · Rol inactivo:** recibe su propio mensaje ("Su cuenta está activa, pero su rol… está inactivo") y el modal se titula "Rol inactivo", en vez de "cuenta desactivada".
- **ERR-14 · PDF:** si una fuente o una gráfica falla, el informe se imprime igual en vez de esperar 30 s hasta agotarse (`window.radarListo` en `finally`).
- **ERR-11 · Accesibilidad:** listas `<dl>` bien formadas en A1, A3.1 y C1. Las filas inactivas de A5 y de roles van en gris (`grayscale`) en vez de bajarles la opacidad, que dejaba el texto sin contraste.
- **ERR-10 · Consultas repetidas:** `/categorias` pasó de 55 a 13 consultas, la ficha de 42 a 24 y Asignar medición de 46 a 23.
- **ERR-13 · Datos de demostración:** `DatosDemoSeeder` les pone actividad económica a las empresas.
- **ERR-08 · Dependencias:**
  - `symfony/mime` 8.1.10 (CVE-2026-45070);
  - vite 8.3.5, puppeteer, concurrently, shell-quote, undici y zod actualizados. `npm audit --omit=dev`: 0 alertas.
  - `vite-plus` 1.5.0 (Vitest 5.1), `vue-tsc` 3.3.10 y `undici` 7.30.1; `glob` y `minimatch` subidos con `overrides`. `npm audit`: 0 alertas, también en desarrollo (DEC-008).
- **ERR-07 · Despliegue:** en `.env.example` y en `docs/20` se documentan `APP_ENV=production`, `APP_DEBUG=false` e `INERTIA_DEVTOOLS_ENABLED=false`.
- **ERR-09 · Enlace de recuperación de un año:** por decisión del 4 de noviembre, RN-006 cambia.
  - El enlace de "¿Olvidaste tu contraseña?" vence a los **10 minutos** (`AUTH_PASSWORD_RESET_EXPIRE`).
  - El de la invitación sigue sirviendo hasta usarlo: va en su propia tabla, `invitaciones_tokens`, con el broker `invitaciones`.
  - El middleware `ElegirEnlaceContrasena` hace que L4 acepte los dos.
  - Al crear la contraseña se borran los dos enlaces de la cuenta.
  - La migración `2026_11_04_000002` pasa a la tabla nueva las invitaciones que ya estaban pendientes.
  - L3 y el correo de recuperación dicen "vence a los 10 minutos".

### Corregido (A3.3 · cambiar de medición conserva la pestaña, 4 de noviembre)

- **El problema:** en el resultado, al elegir otra medición (M1, M2… o las flechas) estando en "Categorías" o "Recomendaciones", la página volvía a "Resumen".
- **La solución:** el enlace lleva la pestaña en la URL (`?pestana=recomendaciones`) y, en "Categorías", también la categoría abierta (`&categoria=c9`) si existe en la otra medición. `Resultado.vue` las lee al abrir.

### Cambiado (E6 / A3.3 · pestaña Recomendaciones, 3 de noviembre)

- **Agrupadas por prioridad:** "Atender primero" (Crítico), "Después" (Se puede mejorar) y "Para mantener" (Vas en buen camino o Sigue así), cada grupo con cuántas categorías tiene. El número de cada tarjeta sigue el orden de prioridad.
- **Una sola tarjeta por categoría:** franja arriba del color del nivel, la recomendación sin un recuadro adentro (`TarjetaRecomendacion` con `plana`) y abajo "Ver el análisis…" como pie de la tarjeta.
- **"Copiar plan":** copia al portapapeles el plan en texto (empresa, medición, grupos, cada recomendación con sus pasos, plazo y cómo saber si funcionó) para pegarlo en un correo o chat.

### Cambiado (E6 / A3.3 · "Mapa" de las categorías, 3 de noviembre)

- **Vértices numerados** en un círculo del color del nivel de cada categoría, en vez de los nombres largos que se cortaban (`RadarCategorias` con `colores`; sin ellos se ve como antes).
- **Anillos con significado:** marcan dónde empieza cada nivel (30, 60, 80 y 100) con un tono de su color, y la leyenda lo dice.
- **Lista debajo** con el número, el nombre completo (hasta dos líneas), la variación y el puntaje; al tocar una categoría se abre su análisis, igual que en "Barras".
- Al pasar sobre un punto, el globo dice el nombre de la categoría.

### Cambiado (A3.4 · historial visto por el Consultor y el Administrador, 3 de noviembre)

- **"Por categoría"** junto a "Mediciones" en la tarjeta de la derecha: cada categoría de su primera a su última medición ("M1 30 → M4 95"), con el cambio en puntos y una barra del color del nivel, de la que más bajó a la que más subió. Al tocar una, la gráfica muestra esa categoría. Lo calcula `DatosHistorial::cambiosPorCategoria()`; solo en la vista interna.
- La gráfica dice "Evolución del puntaje total" (antes "de tu calificación") en la vista interna.
- La medición vencida ya no le pide al consultor "Cámbiale la fecha o asígnale otra" (no puede): "NuevasTIC puede darle otra fecha."

### Cambiado (A6 · Mi perfil del Consultor, 3 de noviembre)

- **"Empresas que acompañas"** en la columna izquierda: cuántas tiene y las primeras 5 con enlace a su ficha; "Ver las N en Mis empresas" si son más.
- **Sin "Avisos por correo":** los tres avisos internos (empresa envía, falla la IA, resumen semanal) solo se le envían al Administrador, así que al consultor sus interruptores no hacían nada. `OpcionesPerfil::avisosPara()` no le da ninguno. **[FUNCIONALIDAD POR DEFINIR]** Correos propios del consultor (por ejemplo, "resultado nuevo de una empresa que acompaño"); por ahora lo ve en "Qué atender".
- Chip "Externo" en vez de "NuevasTIC" y, en Rol, "Lo asigna el equipo de NuevasTIC."

### Cambiado (A3.5 / E9 · Respuestas, 3 de noviembre)

- **Puntaje de cada pregunta con el color de su nivel** (rojo, naranja, azul o verde). El nivel lo agrega el servidor al leer (`DatosResultado::conNivelPorPregunta()`); lo guardado no cambia (RN-023).
- **Variación de la categoría** junto a su nivel: "+18 vs. la anterior".
- **Vista interna (Administrador y Consultor):** filtro "Todas · Para mejorar · Sin responder" (solo los que tienen preguntas) y, en la lista de categorías, "N para mejorar" (preguntas en Crítico o Se puede mejorar). Al filtrar, cada pregunta conserva su número.

### Cambiado (A3.3 · resultado visto por el Consultor y el Administrador, 3 de noviembre)

- **Pasar de una medición a otra** sin volver a la ficha: junto a las pestañas, "M1 51 · M2 47…" con el color del nivel y flechas anterior / siguiente (`DatosResultado::mediciones()`, solo en la vista interna).
- **Textos en tercera persona** en la vista interna: "Qué le conviene hacer", "Sus N categorías", "Es su primera medición". La frase del nivel se aclara como "el mensaje que lee la empresa". La empresa sigue viendo su texto de siempre.
- **Menú:** en un resultado queda marcado "Mis empresas" (Consultor) o "Empresas" (Administrador) (`ItemMenu.tambien`).

### Cambiado (A3.1 · ficha de la empresa para el Consultor, 3 de noviembre)

- **Historial a todo el ancho:** al consultor no le sale la columna de la medición pendiente, porque ya está arriba en "Medición N pendiente".
- **"Datos de la empresa"** (antes "Datos de la cuenta" con el formulario deshabilitado): para quien no puede editar (`empresas.registrar`), los datos se ven como texto en `DatosEmpresaLectura.vue`: descripción, sector, actividad económica, ubicación, empleados, teléfono y sitio web (con enlace), y el contacto principal con su correo y teléfono. Sin asteriscos ni ayudas de edición.
- **PDF en cada fila** del historial de la ficha, junto a "Ver resultado", para quien tiene `resultados.pdf`.

### Cambiado (C1 · diseño del inicio del Consultor, 3 de noviembre)

- **Saludo** con la fecha y una frase: "N empresas necesitan tu atención hoy" o "Tus empresas están al día".
- **Cuatro cifras:** empresas (y cuántas con resultado), puntaje promedio con su nivel, cuántas mejoraron y cuántas bajaron en su última medición, y mediciones pendientes (con las vencidas).
- **"Qué atender"** a la izquierda (con "Todo al día" dentro si no hay avisos) y debajo **"Resultados recientes"**: el último resultado de cada empresa, del más nuevo, con su variación.
- **A la derecha:** "Cómo están por nivel" (barra y conteo) y "Próximas fechas límite" (las 5 que vienen; las vencidas ya están en "Qué atender").
- Todo se calcula en `DatosInicioConsultor::resumen()`; el inicio ya no recibe la lista completa de empresas.

### Cambiado (C2 · diseño de Mis empresas, 3 de noviembre)

- **Filtros** "Todas · Con medición pendiente · Bajaron · Sin resultado", con su cantidad (solo los que tienen empresas), y **orden** por nombre, puntaje más alto, más bajo o "Bajaron primero". El buscador sigue desde 7 empresas.
- **Tarjeta nueva:** franja del color del nivel, variación en puntos, línea de evolución, última medición con su fecha, la medición pendiente con su fecha límite (en rojo si venció), "Desactivada" si aplica y el enlace "Último resultado".
- `DatosEmpresas::lista()` le agrega al consultor `evolucion`, `ultima`, `vence` y `vencida`; A3 no cambia.

### Agregado (C2 · Mis empresas del Consultor, 3 de noviembre)

- **"Mis empresas"** en el menú del Consultor (`empresas/MisEmpresas.vue`, en `/empresas`): las tarjetas de sus empresas, con el buscador desde 7 empresas. Reemplaza la redirección a su inicio.
- **C1 · Inicio del Consultor:** queda con el resumen y "Qué atender" (o "Todo al día" si no hay avisos). Sin botón "Ver mis empresas": ya está en el menú.
- La ficha (A3.1) vuelve con "← Mis empresas".

### Cambiado (A3.1 / A3.4 / E8 · ancho de la ficha y "Ver:" de la evolución, 3 de noviembre)

- **A3.1:** la columna de la derecha (medición pendiente y Asignar) solo aparece si tiene algo. Al consultor sin medición pendiente el historial le ocupa todo el ancho.
- **"Ver:" de la gráfica de evolución** (A3.1, A3.4, E8): va en su propia fila, abajo a la derecha del título, con ancho fijo. Un nombre de categoría largo ya no lo baja ni cambia los tamaños.
- **Gráfica de evolución:** más espacio arriba para que la etiqueta "100" no se corte.
- **A3.4:** al consultor la etiqueta dice "Vista del consultor" (antes, "Vista del Administrador").

### Agregado (C1 · "Qué atender" y sin "Empresas" para el Consultor, 3 de noviembre)

- **"Qué atender"** en el inicio del Consultor (HU-086 CA-005), calculado en `App\Support\DatosInicioConsultor::avisos()`:
  - orden: medición vencida, puntaje que bajó, sin empezar hace 7 días o más y resultado nuevo (últimos 7 días);
  - cada aviso abre el resultado o la ficha; se ven 4 y "Ver los N avisos" muestra el resto.
- **Sin "Empresas" en el menú del Consultor:** sus empresas ya están en su inicio. `ItemMenu.sinRoles` en `lib/menu.ts`; `/empresas` lo redirige a su inicio y la ficha vuelve con "← Inicio".
- **Buscador en "Mis empresas"** con 7 empresas o más (nombre o sector).
- **Pruebas:** 3 casos nuevos en `ConsultorTest.php` y uno en `menu.test.ts`.

### Corregido (tablas paginadas · filas que caben, 3 de noviembre)

- **El problema:** las tablas que se paginan según el espacio (A1, A2, A3, A3.1, A5 y E8) calculaban cuántas filas caben con el alto de la fila **más alta**. En A5, la fila del Administrador principal tiene tres líneas, así que la lista mostraba 5 cuentas y dejaba un espacio vacío abajo.
- **Corrección:** `useFilasQueCaben` (`lib/paginacion.ts`) mide ahora con el alto **típico** de las filas (la mediana). Si en alguna página no caben, porque toca una fila más alta, `ajustar()` quita una, como antes.
- **Resultado:** en A5, a 1626 × 990 pasa de 5 a 7 filas por página y a 1440 × 900 a 6, sin que la tabla se desborde. Lo que queda abajo es menos que una fila.

### Agregado (datos de demostración con volumen, 3 de noviembre)

- **`database/seeders/DatosDemoSeeder.php`:** solo para desarrollo local, no lo llama `DatabaseSeeder`. Crea:
  - 20 empresas en Comidas y Programación y consultoría informática, cada una con su cuenta principal;
  - de 1 a 4 mediciones terminadas por empresa, con respuestas guardadas y resultados calculados con `PublicarResultado::calcular`, igual que uno real. Los textos de la IA son de ejemplo, sin llamarla;
  - en algunas empresas, además, una medición en curso, sin empezar o vencida;
  - 6 colaboradores en Panadería La Espiga, uno desactivado;
  - una consultora (Valeria Ríos) con 10 empresas vinculadas.
- **Las cuentas** terminan en `captter-demo.test` y usan la contraseña `DatosDemoSeeder::CLAVE`. Los comandos y los correos de prueba están en el README.
- **Se puede repetir:** borra lo anterior de la demostración y lo crea de nuevo. `DatosDemoSeeder::limpiar()` solo lo borra.
- **Pruebas:** `tests/Feature/DatosDemoTest.php`.

### Corregido (E8 / A3.4 · gráfica de evolución, 3 de noviembre)

- **El problema:** en Mi historial y en el historial de la empresa, con varias mediciones, la gráfica dejaba los puntos amontonados arriba a la izquierda y sin línea. El contenedor crece después de crear la gráfica, y Chart.js terminaba la animación de entrada con las posiciones del tamaño inicial.
- **Corrección:** `EvolucionPuntaje.vue` ya no anima la entrada (`animation: false`). Se dibuja directamente con el tamaño real y se ajusta igual al cambiar de tamaño.
- No se había visto porque ninguna empresa de prueba tenía más de una medición. Apareció con los datos de demostración.

### Corregido (E3 → E2 · "Ir a la pregunta" lleva a la pregunta, 3 de noviembre)

- **El problema:** en "Revisa antes de enviar", "Ir a la pregunta" y "Completar" abrían la categoría desde arriba, y la persona tenía que buscar cuál le faltaba.
- **Ahora abre la categoría y baja a la primera pregunta obligatoria sin responder:**
  - la pregunta queda arriba de la pantalla, debajo de la barra fija (también en el celular);
  - se resalta con borde rojo y la etiqueta "Falta esta pregunta obligatoria";
  - el resalte se quita al responderla o a los 6 segundos;
  - el foco queda en su primer campo (la opción marcada, la primera opción o el texto), para responder con el teclado sin buscar.
- **Si la persona prefiere menos movimiento,** el desplazamiento es instantáneo (`prefers-reduced-motion`).
- **Cómo funciona:**
  - el avance de cada categoría trae `primera_faltante` (`DatosResponder::avance`);
  - el enlace lleva `?categoria=N&pregunta=ref` (`rutas.responder.mostrar`);
  - el servidor solo acepta la pregunta si es de esa categoría (`preguntaInicial`).
- **Pruebas:** dos casos nuevos en `ResponderTest`.

### Agregado (rol Consultor con empresas vinculadas, 3 de noviembre)

En la presentación se decidió incluir el rol Consultor, que existía inactivo (PA-006). Decisiones del equipo: es externo y se le invita, solo mira, no toca diagnósticos, lo vincula el Administrador y puede tener una o varias empresas. Historias nuevas HU-085 y HU-086 (EP-014) y regla RN-030 en `05`.

- **Seguridad, lo más importante:** antes, si alguien activaba el rol desde A5, el consultor veía **todas** las empresas, porque "solo las vinculadas" era solo un texto. Ahora:
  - la tabla `consultor_empresa` guarda las empresas de cada consultor;
  - `User::puedeVerEmpresa()` decide en el servidor, y la usan Empresas (lista y ficha), el historial, los resultados, las respuestas, el PDF y los logos;
  - de una empresa no vinculada, la respuesta es 404.
- **Rol:** pasa a ser del sistema y activo, con 5 permisos fijos: ver empresas, resultados, PDF, su perfil y su contraseña. Pierde "Responder el diagnóstico". La migración lo ajusta si ya existía, y el seeder lo crea así en una base nueva.
- **C1 · Inicio del Consultor** (`inicio/Consultor.vue`, nuevo):
  - saludo y tres datos: empresas, cuántas tienen resultado y mediciones pendientes;
  - una tarjeta por empresa con el anillo del puntaje, el nivel, la variación, las mediciones terminadas, la pendiente, y los botones "Ver empresa" e "Historial";
  - ya no entra a A1, que muestra todas las empresas.
- **A3.1 · Pestaña "Consultores"** (`PanelConsultores.vue`, solo con `usuarios.gestionar`):
  - reemplaza "Próximamente";
  - consultores vinculados (empresas que acompaña, desde cuándo, invitación pendiente) con "Quitar" y confirmación;
  - "Vincular un consultor", enlace para invitar uno nuevo y "Qué ve un consultor".
- **A5 · Invitar usuario:** al elegir Consultor, el título pasa a "Invitar consultor" y aparece "Empresas que acompaña", con buscador y casillas (opcional).
- **Al cambiarle el rol** a un consultor, pierde sus vínculos.
- **A5:**
  - el Consultor tiene ícono propio (ojo, ámbar);
  - la nota de roles del sistema lo incluye;
  - los roles creados e inactivos dicen "Inactivo" en vez de "Próxima fase".
- **Pruebas:** `tests/Feature/ConsultorTest.php` (13 casos). Se ajustaron las de roles que suponían al Consultor inactivo y editable.
- **Docs actualizados:** 01, 03, 05, 12, 14, 17, 25 y 29.

### Cambiado (repaso a fondo de modales con formularios largos, 3 de noviembre)

- **Nuevo componente `components/base/TituloSeccion.vue`:** encabezado de sección con ícono, título y una línea de ayuda. Lo usan los modales de esta entrada.
- **Nuevo `lib/paginasColaborador.ts` (`iconoPagina`):** el ícono de cada página de colaborador. Lo comparten E12 y sus modales.
- **E12.1 Agregar y E12.3 Editar colaborador:**
  - el modal es más ancho;
  - secciones "Datos de la persona" (nombre y cargo lado a lado), "Contraseña" y "Páginas que puede ver";
  - en Editar, "Cambiar su contraseña" es un interruptor dentro de una tarjeta.
- **Selector de páginas:** cada página es una tarjeta con su ícono y una marca a la derecha. La que depende de otra muestra un candado mientras falta la otra. El grupo se nombra con `aria-labelledby`, no con `legend`.
- **E12.2 Comparte estos datos:**
  - el usuario y la contraseña van en una credencial, cada uno con su botón de copiar;
  - "Copiar datos" sigue copiando los dos juntos.
- **A2.1d Versiones:**
  - el historial es una línea de tiempo: círculo con el número de versión (verde la publicada, ámbar el borrador) y una tarjeta con estado, fecha, uso y nota;
  - los requisitos para publicar usan íconos de cumplido o falta;
  - "Publicar" lleva ícono.
- **A5.1 Invitar usuario:** secciones "Datos de la persona", "Rol" y "Mensaje". El aviso del correo lleva ícono y "Enviar invitación" también.
- **Sin cambios, porque ya estaban bien:** A5.2 Crear rol, A4.7 Probar con un ejemplo y A4.6 Instrucciones completas.
- El backend no cambia.

### Cambiado (A2.1c · diseño de Editar importancia, 3 de noviembre)

- **Encabezado:**
  - lleva ícono, "Volver al editor" y el diagnóstico con su sector debajo del título;
  - "Cancelar" y "Guardar importancia" llevan ícono.
- **Resumen arriba:**
  - el total va en un anillo de color: verde si suma 100, rojo si se pasa y naranja si falta;
  - una etiqueta dice "Suma exacta", "Sobran N puntos" o "Faltan N puntos", junto al número de categorías cambiadas;
  - botones "Deshacer cambios" (nuevo: vuelve a los valores guardados sin salir) y "Repartir en partes iguales".
- **Categorías:**
  - cada una es una tarjeta con número, nombre, el campo en % y una barra deslizable;
  - las cambiadas se resaltan con "Antes 20 → 25".
- **A la derecha, tres tarjetas:**
  - qué significa la importancia, con el ejemplo en un recuadro;
  - el efecto del cambio, con ícono por punto;
  - los cuatro niveles fijos con su color y rango.
- La validación (exactamente 100 %) y el backend no cambian.

### Agregado (páginas de error con el diseño de Captter, 3 de noviembre)

- **Nueva página `resources/js/pages/errores/Error.vue`** para 403, 404, 500 y 503, en vez de la página blanca de Laravel:
  - logo, ícono por tipo de error, "ERROR 404", un título y una explicación en lenguaje sencillo;
  - botones "Volver" e "Ir al inicio" (lleva a `/`, que manda al login o al inicio de cada rol);
  - enlace a soporte con el asunto ya escrito.
- **Textos de cada error:**
  - **403:** "No tienes acceso a esta página".
  - **404:** "No encontramos esta página".
  - **500:** "Algo salió mal de nuestro lado".
  - **503:** "Estamos en mantenimiento".
- **El 500 solo se cambia con `APP_DEBUG=false`.** Mientras se programa sigue saliendo el detalle del error.
- **419 (página expirada):** no muestra una pantalla. Vuelve a la página anterior con el aviso "La página estuvo abierta mucho tiempo. Inténtalo de nuevo".
- **Peticiones JSON:** siguen recibiendo el error en JSON.
- Se arma en `bootstrap/app.php` (`withExceptions → respond`). La página no usa el menú ni la sesión, porque en un 404 Laravel no la abre.
- **Pruebas:** `tests/Feature/PaginasErrorTest.php` (6 casos).
- [INFORMACIÓN PENDIENTE] El correo de soporte falta confirmarlo con NuevasTIC.

### Cambiado (L1 · diseño de Demasiados intentos y Cuenta desactivada, 3 de noviembre)

- **Demasiados intentos:**
  - la cuenta regresiva va en un anillo que se vacía durante el minuto de la pausa, junto a "Vuelve a intentarlo en un momento" y la nota de que «Iniciar sesión» se activa solo cuando termine;
  - tres consejos con ícono: el correo bien escrito, las mayúsculas, y crear una contraseña nueva sin esperar;
  - "Crear contraseña nueva" lleva ícono.
- **Cuenta desactivada o eliminada:**
  - el mensaje del servidor no cambia (`auth.desactivada`);
  - una tarjeta de soporte ("¿Crees que es un error?") muestra el correo de NuevasTIC;
  - el botón nuevo "Escribir a soporte" abre el correo con el asunto ya escrito ("Cuenta desactivada en Captter" o "Cuenta eliminada en Captter"). El correo de soporte sigue como [INFORMACIÓN PENDIENTE].
- En el celular, los botones del pie de las dos ventanas ocupan todo el ancho.
- El backend no cambia.

### Cambiado (L2b · diseño de Verifica tu correo, 3 de noviembre)

- **El código se ve en 6 casillas:**
  - la casilla donde va el siguiente dígito queda resaltada con un cursor;
  - las llenas toman el color de la marca;
  - con un código incorrecto, todas se ponen rojas.
- **Sigue siendo un solo campo** (`#codigo`, invisible encima de las casillas). Pegar el código, el autocompletado del celular (`one-time-code`), el envío automático al llegar a 6 dígitos y los lectores de pantalla funcionan igual que antes.
- **Textos:**
  - "Vence en 15 minutos. Revisa también la carpeta de spam" queda bajo el campo, con ícono de reloj, y deja de ser un recuadro aparte;
  - el aviso del servidor ("Te enviamos un código nuevo") va en un recuadro verde con ícono;
  - el correo largo ya no se corta a la mitad.
- **Botones:**
  - "Verificar" lleva ícono;
  - "Reenviar código" es un botón dentro de una franja "¿No te llegó?"; mientras espera muestra un anillo que se vacía con los 60 segundos.
- Lo mismo aplica en Mi perfil al confirmar un correo o teléfono nuevo. El backend no cambia.

### Cambiado (L4 · diseño de Crea una contraseña nueva, 3 de noviembre)

- **Encabezado:** lleva la descripción "Elige una contraseña segura que no hayas usado antes". La cuenta aparece en un recuadro con ícono ("Para la cuenta").
- **Formulario:**
  - el aviso de las últimas 5 contraseñas lleva ícono;
  - "Guardar contraseña" lleva ícono, y debajo un candado con "Se activa cuando cumplas los N requisitos";
  - al final, "Volver a iniciar sesión".
- **Enlace vencido o ya usado:** el formulario se cambia por una vista de error con ícono rojo, "Este enlace ya no es válido", la explicación (ya se usó o se pidió otro después; solo funciona el último) y el botón "Pedir otro enlace".
- **Corregido:** el texto bajo "Este enlace ya no es válido" ahora es fijo. Antes mostraba el mensaje del servidor, que repetía el título o, si el correo no existía, decía "te enviamos un enlace".
- **Corregido:** "Se activa cuando cumplas los N requisitos" decía 5 aunque la lista tenía 6. Ahora también cuenta "Sin partes de tu correo".

### Cambiado (L3 · diseño de ¿Olvidaste tu contraseña?, 3 de noviembre)

- **Formulario:**
  - el correo lleva una nota con ícono: "El enlace no vence por tiempo: sirve hasta que cambies la contraseña" (RN-006);
  - "Enviar enlace" lleva ícono;
  - "Volver a iniciar sesión" va con flecha, bajo una línea.
- **Al enviar,** el formulario se cambia por una vista de confirmación:
  - ícono de correo en verde y "Revisa tu correo", con el mismo texto que no afirma que la cuenta exista (RN-005);
  - tres pasos numerados que citan el asunto y el botón reales del correo ("Cambia tu contraseña de Captter" y "Cambiar contraseña");
  - nota de spam: si se pide otro enlace, solo funciona el último;
  - botones "Reenviar enlace" (reenvía con el mismo formulario, que queda oculto) y "Usar otro correo" (vuelve al formulario).
- El backend no cambia.

### Cambiado (L1–L4 · diseño de Iniciar sesión y Registro, 3 de noviembre)

- **Layout de acceso (`AuthLayout.vue`), usado por L1 a L4:**
  - el panel oscuro tiene un fondo con brillos y puntos, una etiqueta "Diagnóstico de marketing digital" y el titular más grande;
  - los tres pasos (responder, recibir el resultado, medir la evolución) van con ícono, título y una línea;
  - sin barra de desplazamiento visible (clase `sin-barra`), aunque la página sigue bajando con la rueda, el dedo o el teclado;
  - el formulario va en una tarjeta blanca con sombra;
  - en el celular, la marca va centrada arriba y la ayuda abajo con ícono.
- **L1 Iniciar sesión:** el botón lleva ícono. "Registrar mi empresa" pasa a ser un botón secundario de ancho completo, bajo un separador "¿Aún no tienes cuenta?".
- **L2 Registro:**
  - indicador de pasos con ícono (empresa, usuario); el paso hecho se marca con ✓ verde y el actual queda resaltado;
  - "Siguiente: tu usuario →", "← Atrás" y "✓ Registrar empresa" con íconos;
  - la casilla de términos va en un recuadro que se resalta al marcarla y se pone rojo si falta.
- L3 y L4 toman el nuevo layout sin más cambios. La lógica, la validación y el backend no cambian.

### Cambiado (A6 / E11 · diseño de Mi perfil, 3 de noviembre)

- **Encabezado:** lleva ícono. El aviso de solo lectura (colaborador o rol sin permiso) va en un recuadro de alerta.
- **Tarjeta de identidad:**
  - franja de marca arriba, con la foto o el logo encima;
  - chips con el rol (ícono y color de `estiloRol`) y el sector, o "NuevasTIC" en las cuentas internas;
  - "Cambiar foto" o "Cambiar logo" como botón con ícono;
  - en A6, el último acceso y la fecha de creación de la cuenta como dos datos al pie.
- **Tarjetas aparte con ícono:**
  - Seguridad (solo E11), con la última actualización de la contraseña y "Cambiar contraseña" a lo ancho;
  - Sesión activa, con "Cerrar sesión";
  - Cuenta principal (solo A6), con borde rojo y "Eliminar mi cuenta" como botón rojo.
- **En escritorio**, la columna izquierda queda fija al bajar.
- **Formulario:**
  - cada sección (Información personal, Información de empresa, Avisos o Preferencias) tiene un encabezado con ícono y una línea que explica para qué es;
  - los avisos van en una lista con borde;
  - la nota "Algunos datos solo puede modificarlos el equipo de NuevasTIC" pasó a la sección de empresa.
- **Barra de guardar:** un punto verde indica que no hay cambios y uno ámbar que hay cambios sin guardar. Con cambios, la barra queda fija abajo para tener "Guardar cambios" siempre a mano.
- "Cambiar contraseña" (A6) tiene encabezado con ícono. El backend no cambia.

### Cambiado (E12 · diseño de Colaboradores, 3 de noviembre)

- **Encabezado:** lleva ícono, y el botón "Agregar colaborador" tiene ícono.
- **Qué puede y qué no puede hacer un colaborador:** dos tarjetas, una con ícono verde y otra con ícono rojo.
- **Las personas son tarjetas en vez de una tabla** (en el celular ya no hay que deslizar de lado):
  - la cuenta principal va primero, con fondo de marca y el chip "Cuenta principal · Tú";
  - cada tarjeta lleva iniciales, nombre, correo y estado (Activo o Desactivado); el cargo solo se muestra si existe;
  - "Puede ver" muestra las páginas como chips con ícono, o "Solo Inicio" si no tiene ninguna;
  - acciones con ícono: Editar y "Desactivar o eliminar"; en las desactivadas, Reactivar y Eliminar en rojo. Las desactivadas tienen borde punteado.
- **Filtro** Todos / Activos / Desactivados, con conteos. La cuenta principal siempre se ve.
- **Lista vacía:** tarjeta punteada con ícono junto a la cuenta principal (HU-076 CA-004).
- **Nota final:** va en un recuadro y dice cuántos colaboradores activos hay.
- Los modales E12.1 a E12.3 y el backend no cambian.

### Cambiado (E9 / A3.5 · diseño de Mis respuestas, 3 de noviembre)

- **Encabezado:**
  - ícono, título y chips con la empresa, el sector, el diagnóstico y la fecha de envío;
  - etiqueta "Solo lectura" junto a "Ver informe PDF".
- **Categorías:** cada una es una tarjeta con su puntaje, una barra del color de su nivel, el nombre del nivel y cuántas preguntas tiene. En el celular se deslizan de lado y, al elegir una, la pantalla baja a sus preguntas.
- **Cabecera de la categoría:** "Categoría N de M", el nivel, la importancia, cuántas preguntas se respondieron y el puntaje grande sobre 100.
- **Preguntas como tarjetas** (`PreguntaResultado` con `tarjeta`):
  - número, tipo de pregunta con ícono y puntaje en una píldora ("Dato extra" o "· IA" según el caso);
  - las opciones elegidas como chips, y las respuestas abiertas como cita;
  - la nota de la IA en un recuadro con ícono.
- **Navegación:** abajo, botones a la categoría anterior y a la siguiente.
- **E6:** la pestaña Categorías usa el mismo estilo de pregunta, en forma de lista.
- Sin cambios en el backend: los puntajes y niveles siguen llegando calculados.

### Cambiado (E3 · diseño de Revisa antes de enviar, 3 de noviembre)

- **Resumen arriba:**
  - un anillo con el avance: el porcentaje o, si está completo, ✓ en verde;
  - tres cifras: categorías completas, preguntas respondidas y obligatorias pendientes (en rojo si falta alguna, en verde si no).
- **Aviso grande:**
  - con ícono, «Faltan N preguntas obligatorias» y el botón «Ir a la pregunta»;
  - o «Todo listo» en verde.
- **Tus categorías:**
  - siempre a la vista en tarjetas, con su estado (✓ verde o ! rojo), cuántas van respondidas y una barra;
  - «Completar» o «Revisar» con ícono.
  - Antes, completo, había que abrir «Ver detalle por categoría».
- **Qué pasa al enviar:** tres pasos con ícono (Envías → La IA las analiza → Te avisamos).
- **Barra de abajo:**
  - «Volver» y «Enviar diagnóstico» con ícono, y «Todo listo para enviar» en verde cuando se puede;
  - en el celular cabe en un renglón.
- **Confirmación de envío:** «No podrás cambiar tus respuestas» en un recuadro de alerta, y el botón con ícono.

### Cambiado (E2 · diseño de Responder, 3 de noviembre)

- **Barra de arriba (también E3 y E4):**
  - el avance va en una barra más gruesa con el porcentaje en una píldora;
  - «Guardado 10:42» va en una píldora verde con ícono (en el celular solo el ícono y la hora);
  - «Guardar y salir» es un botón con ícono.
  - Mide 61 px de alto (antes 57) y la columna de categorías se ajusta a esa altura.
- **Columna de categorías:**
  - un anillo con el avance total y «N de M categorías completas» (solo en pantallas grandes);
  - cada categoría con su número o ✓, su conteo y una barra de avance (verde si está completa, ámbar si va a medias);
  - la elegida va en azul;
  - «Revisar antes de enviar» es un botón con ícono al final.
- **Cabecera de la categoría:** una tarjeta con «Categoría N de M», el nombre, cuántas preguntas y cuántas obligatorias tiene, su barra de respondidas y el aviso «No hay respuestas buenas o malas» con ícono.
- **Barra de abajo:**
  - anterior y siguiente con flechas;
  - en el medio, un punto por categoría (el actual más largo, en verde las completas) y cuántas van respondidas;
  - en la última categoría, «Revisar antes de enviar» con ícono.
- Las preguntas y las opciones no cambian: ya tenían el diseño de lectura fácil (letra grande, opciones con borde).

### Cambiado (A2.6 · diseño del resultado de ejemplo, 3 de noviembre)

- **Franja «Datos de muestra»** arriba de las pestañas, con borde punteado ámbar y un ícono:
  - explica que no se guarda ni llama a la IA y qué caso se ve («Ahora ves el caso medio: respuestas intermedias»);
  - «Mejor caso», «Caso medio» y «Peor caso» son tarjetas con ícono. Antes eran tres botones pequeños junto a las pestañas.
- La versión va en una píldora, «Ver formato del PDF» lleva ícono y la nota del final lleva un ícono de información.
- **Corregido:** sin borrador pendiente, la pantalla decía «Borrador v1» aunque el diagnóstico ya tuviera versiones publicadas. Ahora dice «Versión N», la última publicada, porque su contenido es el mismo. Con borrador sigue diciendo «Borrador vN» (`ResultadoEjemploController::numeroYEtiqueta`, con prueba).

### Cambiado (A5 · diseño de Usuarios y roles, 3 de noviembre)

- **Encabezado:** ícono, y las pestañas «Usuarios» y «Roles» con ícono. La elegida va en azul.
- **A5 · Usuarios:**
  - accesos rápidos por estado con su conteo (Todas, Activas, Invitaciones, Desactivadas, Eliminadas), como en Empresas; reemplazan el filtro «Estado»;
  - búsqueda con lupa y filtro de rol en una tarjeta;
  - cada cuenta con un círculo de iniciales del color de su rol (Administrador azul, Empresa verde, Colaborador celeste, los creados gris) y «Tú» en la propia;
  - el rol en una píldora;
  - la empresa con ícono, o «Equipo NuevasTIC» en las internas;
  - el estado con un punto de color;
  - las acciones son botones con ícono: Ver como, Cambiar rol, Desactivar / Eliminar, Reactivar, Reenviar invitación, Recuperar, Eliminar.
- **A5.1 · Roles:**
  - cada rol con su ícono y color, el número de cuentas en una píldora y un candado si es del sistema; el elegido va en azul;
  - el detalle empieza con una cabecera (ícono, nombre, descripción y píldoras «Del sistema», «Activo» o «Inactivo» y el número de cuentas);
  - en los roles del sistema ya no se muestran el nombre y la descripción en campos bloqueados;
  - «Rol activo» es un interruptor;
  - los permisos van en tarjetas por bloque, con ícono y «2/3»;
  - las cuentas van con iniciales;
  - los botones llevan ícono.
- `resources/js/lib/roles.ts` (nuevo): ícono y color de cada rol.

### Cambiado (A4 · diseño de Configuración IA, 3 de noviembre)

- **Encabezado:** ícono, y el estado de los cambios en una píldora (verde «Sin cambios pendientes», ámbar «Cambios sin guardar»). «Descartar» y «Guardar» llevan ícono.
- **A quién se aplica:** «Todas las empresas», «Solo un sector» y «Solo una empresa» son tres tarjetas con ícono y una línea de explicación. La explicación de la etapa va en una franja con ícono.
- **Datos que se insertan solos:** cada dato en una tarjeta con su código resaltado y «En uso» o «Sin usar» en una píldora.
- **Contexto, Tarea, Detalles y Ejemplos:**
  - número en un círculo azul y un ícono por parte;
  - «Incluye {…}» o «Sin {…}» en una píldora verde o ámbar;
  - «Restaurar texto original» con ícono.
  - En los ajustes de sector o empresa, «Usar general», «Agregar» y «Reemplazar» son tarjetas con ícono.
- **Formato de la respuesta:** «Fijo» con candado.
- **Panel derecho:** cada tarjeta lleva ícono. «¿Quieres ver cómo queda?» va resaltada y «Quitar el ajuste» tiene borde ámbar.
- **A4.9 · Cómo se arma el prompt final:**
  - las cinco capas en una línea de pasos con ícono;
  - la etiqueta dice quién la escribe: azul tú, verde el sistema con tus datos, gris fija;
  - termina en «= Prompt final que lee la IA»;
  - el panel derecho lleva íconos y viñetas de color.

### Corregido (A3.1e · el correo se salía de la ventana, 3 de noviembre)

- Con un correo largo en «Para», la vista previa empujaba la ventana y salía una barra horizontal. Ahora:
  - las dos columnas no pasan de su mitad;
  - «Para» baja de línea en lugar de cortarse;
  - la ventana del correo es más ancha (`ancho="2xl"`, nuevo en `Modal`, hasta 72 rem).
- Aplica en Asignar medición (A3.1b) y en Enviar recordatorio / Reenviar aviso.

### Cambiado (las demás ventanas del sistema, 3 de noviembre)

- **Las 35 ventanas que faltaban llevan ícono en el título**, con el color de la acción:
  - azul: crear, editar, ver o probar;
  - ámbar: desactivar, reemplazar o quitar un ajuste;
  - rojo: eliminar o cancelar;
  - verde: reactivar o datos listos.
- **Cuáles son:**
  - editor (agregar categoría, versiones, agregar o editar pregunta, eliminar pregunta);
  - sectores (desactivar o reactivar, reasignar, eliminar, eliminar varios diagnósticos);
  - empresas (cuenta principal, desactivar, reemplazar, cambiar fecha o cancelar medición);
  - usuarios y roles; colaboradores; Configuración IA (probar, instrucciones completas, quitar ajuste, descartar cambios);
  - Mi perfil (cambiar contraseña, eliminar mi cuenta);
  - inicio de sesión (verificar correo, cuenta desactivada, demasiados intentos);
  - documentos legales del registro;
  - «¿Enviar tu diagnóstico?» (E3).
- **Avisos:** 23 avisos dentro de esas ventanas pasan al componente `Recuadro`, con su ícono. Se quitaron los «⚠» que quedaban repetidos junto al ícono.
- **Plurales:** las ventanas de sector decían «1 empresas, 1 diagnósticos y 1 mediciones» y «Reasignar las 1 empresas». Ahora usan `plural()` (nuevo, en `resources/js/lib/texto.ts`).

### Cambiado (ventanas de diagnósticos y categorías, A3.1b y A3.2 · diseño, 3 de noviembre)

- **Ventana base (`Modal`):** acepta un ícono junto al título, con color según la acción: azul para crear o editar, ámbar para archivar, rojo para eliminar. Lleva esquinas más redondeadas y el pie en gris claro. Lo usan todas las ventanas del sistema.
- **`Recuadro` (nuevo componente base):** avisos dentro de ventanas y formularios con su ícono: información, alerta, peligro, éxito o nota.
- **Categorías:**
  - **A2.3b · Crear categoría:** los borradores se marcan en tarjetas con su sector y versión.
  - **Editar categoría:** tres cifras (diagnósticos que la usan, preguntas y versiones publicadas), avisos en recuadros, y «Archivar…» y «Eliminar…» con ícono.
  - **A2.3c · Archivar / Eliminar:** ícono ámbar o rojo; lo que no cambia, lo que se quita de los borradores y «No se puede deshacer» van en recuadros.
- **Sectores:** **A2.2a · Crear sector:**
  - al elegir la división CIIU se muestra en una tarjeta con sus subsectores;
  - «Sector activo» es un interruptor que explica qué pasa en cada caso.
  - Al editar, las cifras del sector van en tarjetas.
- **Diagnósticos:**
  - **Archivados:**
    - cada diagnóstico va en una tarjeta con sector, versión, fecha y uso en píldoras;
    - «Restaurar» y «Eliminar» llevan ícono;
    - los que tienen mediciones muestran «No se elimina» con candado;
    - la segunda confirmación para eliminar aparece dentro de la tarjeta, en rojo, con «No, conservarlo» y «Sí, eliminar».
  - **Archivar:** cuántas empresas y mediciones lo usan, y «Se conserva todo» en verde.
  - **A2.7 · Duplicar:**
    - una tarjeta con el diagnóstico de origen;
    - aviso si se elige otro sector;
    - el nombre que propone es «Nombre (copia)»; antes le agregaba el sector y se cortaba a los 60 caracteres.
  - **Eliminar borrador:** «Se elimina» (el borrador) y «Se conserva» (la versión publicada con sus empresas y mediciones), lado a lado.
- **A3.1b · Asignar medición:**
  - cuatro pasos numerados (empresa, diagnóstico, fecha y mensaje, aviso por correo);
  - diagnósticos en tarjetas, y los borradores con candado;
  - avisos en recuadros;
  - el aviso por correo con interruptor;
  - la vista previa con el estilo del inicio de la empresa (E1).
- **A3.1e · Ver y editar el correo:**
  - «Para» en una tarjeta, variables en chips y «Guardar como plantilla» con interruptor;
  - «Así le llega a la empresa» con la misma cara que el correo real (banda azul «CAPTTER.», tarjeta blanca y pie).
- **A3.2 · Registrar empresa:**
  - secciones con ícono (empresa, usuario principal, primera medición);
  - la primera medición se activa con un interruptor y el diagnóstico se elige en tarjetas;
  - «Qué pasa al registrar» como línea de pasos.

### Cambiado (A2.4 · diseño de la vista previa y estado, 2 de noviembre)

- **Encabezado:**
  - «Vista previa» con ícono sobre el nombre;
  - el estado (borrador completo o incompleto, o versión publicada en solo lectura) y el sector en píldoras;
  - «Editar» y «Publicar versión N» llevan ícono y bajan de línea en el celular.
- **Estado del diagnóstico:**
  - un anillo con las categorías completas («6/6»), verde si está listo para publicar y ámbar si no, con «Listo para publicar» o «Faltan N requisitos»;
  - cada requisito en su recuadro con ✓ o !, y el detalle debajo;
  - las categorías por corregir en tarjetas con «Ver aquí» (abre esa categoría en el cuestionario) y «Corregir en el editor».
- **Cuestionario:**
  - «Cuestionario» y «Resultado de ejemplo» en pestañas con ícono;
  - las categorías en chips numerados con ⚠ si tienen algo por corregir; antes era una lista desplegable;
  - una cabecera con la categoría y cuántas preguntas se respondieron de prueba.
- **Preguntas con el mismo estilo de E2:**
  - número en un círculo, que se vuelve ✓ al responder;
  - «Obligatoria», y «Cómo responder» en un recuadro;
  - opciones grandes con su círculo o casilla. Son controles reales: se usan con teclado y lector de pantalla, y «Ninguna» desmarca las demás.
- **Navegación:** «Categoría anterior» y «Siguiente categoría» al final.

### Cambiado (RN-012 · la selección múltiple suma exactamente 100, 2 de noviembre)

- **Antes:** una selección múltiple solo debía «poder llegar a 100». Si sumaba más (por ejemplo 158), se aceptaba en verde y el puntaje de la pregunta se cortaba en 100. Así, marcar todas daba lo mismo que marcar solo algunas.
- **Ahora:** con todas marcadas debe sumar **exactamente 100**. No cuenta la opción que excluye a las demás («Ninguna»), porque no se marca junto a otras. Decisión de Cristian, 2 de noviembre.
  - `EstadoDiagnostico`: con menos de 100 sigue diciendo «suma solo 80»; con más de 100 dice «suma 110: se pasa de 100». En los dos casos no deja publicar.
  - Ventana de la pregunta (A2.1b / A2.1e): «Suma con todas marcadas» en verde si da 100, en ámbar si faltan puntos y en rojo si se pasa, con cuántos puntos faltan o sobran.
  - La tarjeta de la pregunta en A2.1 muestra el mismo aviso.
- **Datos de prueba:** siete preguntas sumaban 110 (por la opción «Otro») o 90. Se ajustaron los puntajes en `DiagnosticoProgramacionSeeder` y `DiagnosticoEjemploSeeder`. Si el diagnóstico de prueba ya existe, `php artisan db:seed --class=DiagnosticoProgramacionSeeder` publica la versión siguiente con los puntajes nuevos. Las versiones ya publicadas no cambian (RN-010).
- Documentos actualizados: 05, 07, 08 y 15.

### Cambiado (A2.1 · diseño del editor de diagnóstico, 2 de noviembre)

- **Encabezado:**
  - el sector, «Versiones», el estado («Listo para publicar» o «Incompleto») y «Borrador guardado» van en píldoras con ícono;
  - «Vista previa y estado» y «Publicar versión N» llevan ícono;
  - en el celular los botones bajan de línea en lugar de salirse de la pantalla.
- **Categorías:**
  - conteo «N de M completas»;
  - cada categoría muestra ✓ o el número de cosas por corregir en un círculo, su importancia con una barra y sus preguntas;
  - la elegida se resalta en azul;
  - el total de importancia lleva una barra verde (o roja si no suma 100%);
  - «Editar importancia» y «Agregar categoría» llevan ícono.
- **Cabecera de las preguntas:** una tarjeta con la categoría, su importancia, sus preguntas y «Arrastra el número para ordenar». Al final de la lista hay un botón «Agregar otra pregunta a…».
- **Tarjeta de pregunta:**
  - número en un círculo (es el asa para arrastrar);
  - tipo con ícono y «Obligatoria / Opcional» en píldoras;
  - «Cómo responder» en un recuadro;
  - cada opción con su letra, su puntaje en color (verde 100, azul desde 50, ámbar por debajo, gris 0) y una barra;
  - la nota «Ten en cuenta» de la opción se ve debajo, en una línea;
  - el criterio de la IA de las abiertas va en un recuadro.
- **A2.1b / A2.1e · Ventana de la pregunta:**
  - arriba, el texto y la indicación lado a lado; luego el tipo y «Obligatoria»;
  - cada opción va en su tarjeta, con el texto a lo ancho (antes se cortaba), el puntaje, subir/bajar/quitar y, justo debajo, su «Ten en cuenta» para la IA (antes estaba en otra columna);
  - «Opción que excluye a las demás» y «Opción que pide escribir cuál» ya no se salen de su columna;
  - en el celular, el texto de la opción ocupa toda la fila.

### Cambiado (A2.3 · Catálogo de categorías y A2.5 · Crear diagnóstico, 2 de noviembre)

- **A2.3 · Catálogo de categorías:**
  - Arriba, tres cifras: categorías en uso, archivadas y preguntas en total.
  - El texto de ayuda va en una franja con ícono.
  - Búsqueda por nombre con lupa (nueva) y filtro Todas / En uso / Archivadas en chips con conteo.
  - Cada fila tiene un ícono y la descripción de la categoría bajo el nombre. La columna «Se usa en» muestra «N de M diagnósticos» con una barra. El estado va en una píldora.
  - Editar, Archivar y Eliminar son botones con ícono (con su nombre al pasar el mouse y para lectores de pantalla). «Restaurar» es un botón con ícono.
- **A2.5 · Crear diagnóstico:**
  - El formulario va en dos pasos numerados: «1 · Datos del diagnóstico» y «2 · Punto de partida».
  - El punto de partida se elige con dos tarjetas («En blanco» y «Copiar uno publicado»).
  - Las categorías se marcan en recuadros, con el conteo «N de M elegidas» y «Elegir todas» / «Quitar todas» (nuevo).
  - La importancia inicial se ve en una franja verde. La copia muestra un resumen con ícono y sus categorías en píldoras.
  - «Qué pasa al crearlo» es una línea de pasos numerados.

### Cambiado (A2 · diseño de Diagnósticos, 2 de noviembre)

- **Encabezado de la tabla:** ícono, título y conteo («1 diagnóstico · 1 empresa»). «Archivados», «Editar sector» y «Eliminar sector» llevan ícono y dejan de ser enlaces subrayados.
- **Búsqueda con lupa y filtro de estado en chips** con su conteo (Todos, Publicados, Borradores), ahora también dentro de un sector. «Ordenar por» queda solo en «Todos».
- **Tabla:**
  - ícono verde (publicado) o ámbar (borrador) y, bajo el nombre, sus categorías y preguntas;
  - el sector en una etiqueta;
  - el estado en píldoras;
  - una columna «Uso» con las empresas y las mediciones, que reemplaza las tres columnas de números;
  - «Vista previa» pasa a un botón con un ojo. Antes, en «Todos», se cortaba por el borde derecho.
- **Sectores:** el elegido se resalta en azul y cada conteo va en una píldora. «Crear sector» lleva ícono.
- **Resumen:** las cuatro cifras van en tarjetas con ícono y el estado del sector en una etiqueta. «Última medición de cada empresa» es una barra apilada con su leyenda.

### Cambiado (A3.1 · diseño de la ficha de la empresa, 2 de noviembre)

- **Encabezado:** círculo con las iniciales, el sector en una etiqueta con ícono y el estado de la cuenta con un punto verde o gris.
- **Cifras de arriba** en tres tarjetas: el último puntaje en un anillo con su nivel, la variación en grande y en color, y la medición pendiente con su ícono.
- **Tabla de mediciones:** «Medición N», el puntaje en un anillo (o un círculo punteado si no tiene) y la variación en una píldora de color.
- La gráfica sin mediciones muestra un estado vacío con ícono. Las tarjetas de la medición pendiente y de «Asignar otra medición» llevan ícono en el título.
- **Corregido:** en el celular «Consultores · Próximamente» se salía por la derecha; ahora baja de línea.

### Cambiado (A3 · diseño de Empresas, 2 de noviembre)

- **Accesos rápidos** con su conteo (Todas, En curso, Vencidas, Sin diagnóstico, Desactivadas): aplican el filtro con un toque y se marcan cuando está activo.
- **Búsqueda y filtros** en una tarjeta, con ícono de lupa, «N de M empresas» y «Quitar filtros».
- **Filas:**
  - un círculo con las iniciales y el sector bajo el nombre (ya no hay columna de sector);
  - el último puntaje en un anillo con su nivel, o un círculo punteado si no tiene;
  - la variación en una píldora de color;
  - la cuenta con un punto verde o gris; las desactivadas se ven en gris;
  - «Ver ›» en lugar del enlace subrayado.
- **Corregido:** en el celular la página se ensanchaba a 925 px por el texto `sr-only` de «Ver» dentro de la tabla con scroll. El `<main>` de `PanelLayout` ahora recorta en horizontal (`overflow-x-clip`) también en el celular.

### Cambiado (A1 · diseño del inicio del Administrador, 2 de noviembre)

- **Encabezado:** la fecha de hoy.
- **Indicadores con ícono y color:**
  - «Mediciones pendientes» lleva una barra de sin iniciar, en curso y vencidas;
  - «Puntaje promedio» lleva un anillo del color de su nivel.
- **Franja «Requiere tu atención»** cuando hay mediciones vencidas o empresas que pidieron una nueva (HU-083): lleva a la pestaña Vencidas. Reemplaza el aviso junto al título de la tabla.
- **Empresas por nivel:** una barra de colores con su leyenda.
- **Diagnósticos por sector:** el nombre completo (antes se cortaba), un punto verde o naranja y el número de empresas debajo; toda la fila lleva al sector.
- **Corregido:** en el celular la página medía 654 px y se salía hacia la derecha por la tabla; ahora la tabla se desliza dentro de su recuadro.

### Cambiado (E1 · diseño del inicio de la empresa, 2 de noviembre)

- **Encabezado:** saludo más grande y la fecha de hoy.
- **Medición pendiente**, en una tarjeta destacada:
  - con fondo azul suave (rojizo si venció);
  - un anillo con el porcentaje respondido, o un ícono si no ha empezado o se está analizando;
  - datos con íconos (categorías y preguntas, avance guardado, moverse entre categorías);
  - a la derecha, una cuenta regresiva grande («5 días»), naranja con 3 días o menos y roja el último día o vencida, con el botón principal a todo el ancho.
- **Cómo funciona:** tres pasos con íconos unidos por una línea punteada.
- **Último resultado:** anillo más grande, variación en una píldora de color y botones «Ver resultado completo» e «Informe PDF».
- **Áreas a trabajar:** tarjetas con borde y barra del color del nivel.
- **Mis mediciones:** lista con anillo, nivel y variación, como en Mi historial.

### Cambiado (E8 / A3.4 · diseño del historial, 2 de noviembre)

- **Arriba, cuatro cifras:**
  - última calificación con su anillo y nivel;
  - cambio desde la primera medición (+97 pts, en verde o rojo);
  - mejor calificación;
  - mediciones terminadas.
  - Las arma `DatosHistorial` en `resumen`.
- **Medición pendiente:** franja azul con ícono, estado, fecha y su botón.
- **Gráfica de evolución:** más grande, al lado de la lista en pantallas anchas. Sin datos, muestra un ícono y una explicación.
- **Mediciones terminadas:** la tabla pasa a ser una lista con anillo, nivel, «Más reciente» y la variación en una píldora de color. «Ver resultado» es un botón; respuestas y PDF son íconos con su nombre para lectores de pantalla. En el celular los botones van en su propia línea.
- `useFilasQueCaben` acepta qué contar como fila (`fila`, `encabezado`), para paginar tarjetas además de tablas.
- La gráfica de evolución deja espacio arriba para la etiqueta de un puntaje cerca de 100.

### Cambiado (E6 / A3.3 · diseño del resultado, 2 de noviembre)

- **Resumen:**
  - un encabezado con el anillo grande, el nivel, la frase y la variación;
  - una barra con cuántas categorías hay en cada nivel;
  - las tarjetas «Lo que más urge» y «Lo más fuerte».
  - Debajo, «Qué hacer ahora» (número de color según el nivel, pasos y plazo) y «Tus categorías» en barras de colores o en el mapa (radar), con un selector. Todo lleva al análisis de la categoría.
- **Categorías:** la tabla pasa a ser una lista de tarjetas con el puntaje grande y una barra de color. El detalle tiene un encabezado con anillo, nivel, importancia y variación. En el celular, al elegir una categoría la pantalla baja sola al detalle.
- **Recomendaciones:** borde y número del color del nivel de cada categoría.
- **Datos nuevos:** `DatosResultado::pagina()` agrega `por_nivel` y `mas_fuerte` (los cálculos siguen en el backend).
- **Corregido:** en las pantallas fijas del panel quedaba un espacio vacío debajo del menú y toda la página bajaba. Lo causaban los textos para lectores de pantalla (`sr-only`, en posición absoluta) dentro de recuadros con scroll propio. El `<main>` de `PanelLayout` ahora es `relative` con `lg:overflow-clip`.

### Cambiado (E4 · espera del análisis, 2 de noviembre)

- Anillo de avance con el porcentaje analizado en el centro. Mientras trabaja, un halo y un arco giran a su alrededor; al terminar se pone verde con un check y aparece «Ver mi resultado».
- Lista de categorías en vivo, cada una con su estado: lista ✓, analizando (girando), en espera o con error. El servidor la manda en `analisis.categorias`.
- «Mientras esperas»: seis datos sobre lo que hace la IA, que cambian cada 7 segundos.
- Las animaciones se apagan si el sistema pide reducir el movimiento (`motion-safe`).

### Cambiado (diseño de los correos, 2 de noviembre)

- Todos los correos usan el tema `captter` (`resources/views/vendor/mail/html/themes/captter.css`, activado en `config/mail.php`). Antes usaban la plantilla gris genérica de Laravel. Ahora tienen:
  - encabezado azul con el nombre CAPTTER y «Diagnóstico de marketing digital · NuevasTIC»;
  - tarjeta blanca con una franja verde arriba y botón grande en el azul de la marca;
  - pie que explica por qué llega el correo.
- El código de verificación (registro, cambio de correo y de teléfono en Mi perfil) va en una caja destacada con letras grandes.
- Sin imágenes en el encabezado, porque Gmail a veces las bloquea; todo funciona con tablas y estilos en línea, también en el celular.

### Corregido (informe y análisis de prueba, 2 de noviembre)

- **Sin IA de prueba en la aplicación:** se quitó `IA_DE_PRUEBA`. El análisis usa siempre OpenAI; el analizador falso pasó a `tests/Support/AnalizadorFalso.php` y solo lo usan las pruebas automáticas, para no gastar saldo. Un resultado publicado antes en modo de prueba conserva sus textos «Prueba:» (RN-023); para tener uno real hay que asignar una medición nueva.
- **Comparación y priorización:** la justificación ya no repite el impacto en todas las filas. Las categorías en Crítico o Requiere mejora dicen que requieren intervención y por qué; las demás, que basta con mantener lo logrado.
- **Tres recomendaciones prioritarias:** ahora explican qué son, marcan «para seguir mejorando» si la categoría ya está bien y no se parten entre dos páginas.

### Agregado (diagnóstico de prueba para software, 2 de noviembre)

- `php artisan db:seed --class=DiagnosticoProgramacionSeeder` publica «Diagnóstico de prueba · Software y apps» en el sector «Programación y consultoría informática»; si el sector no existe, lo crea con las actividades CIIU de la división 62.
- Trae 6 categorías del catálogo (Presencia en línea, Análisis del cliente, Contenido y publicaciones, Análisis de la competencia, Herramientas de automatización y Medición de KPIs) y 30 preguntas de los tres tipos, pensadas para una empresa que vende una app, por ejemplo una que ayuda a organizar el tiempo según los hobbies y las responsabilidades.
- Las respuestas «No sé» llevan un «Ten en cuenta» para que la IA lo marque como información faltante, y las abiertas llevan criterio de calificación. Sirve para probar el análisis y el informe PDF.
- No es un diagnóstico de NuevasTIC y no lo carga la instalación. `DiagnosticoEjemploSeeder` y este usan el trait `CreaDiagnosticoPublicado`.
- La respuesta más baja de cada pregunta cerrada lleva una nota «Ten en cuenta» para la IA (25 en total), por ejemplo «No miden ningún dato: la primera recomendación debe ser empezar a medir». Las dos preguntas de selección múltiple que no tienen una respuesta de cero quedan sin nota.
- Si el diagnóstico ya estaba cargado, al correr de nuevo el seeder se agregan las notas que falten y se publica la versión siguiente (la publicada no se toca, RN-010). Las mediciones pendientes sin respuestas pasan a la versión nueva.

### Cambiado (informe PDF según la propuesta de reestructuración, 2 de noviembre)

Fase 1 de la «Propuesta de reestructuración del Informe de Diagnóstico y Estrategia de Marketing Digital»: todo lo que sale de las respuestas y del análisis de la IA.

- **Estructura nueva:**
  - portada corporativa;
  - 1 Ficha técnica y alcance (datos de la empresa, fuentes y lo que quedó fuera);
  - 2 Resumen ejecutivo;
  - 3 Metodología y criterios (fórmula con el valor exacto antes de redondear, rúbrica de las abiertas, controles de calidad de la IA, métodos y limitaciones);
  - 4 Resultados generales (barras y radar);
  - 5 Comparación y priorización (con el criterio de desempate);
  - 6 Diagnóstico por categoría con plantilla uniforme (resultado, diagnóstico, impacto, hallazgos con todos los grupos del método, fortalezas y debilidades, información pendiente, recomendaciones y evidencia);
  - 7 DOFA consolidado;
  - 8 Conclusiones;
  - 9 Plan estratégico con fichas de acción;
  - 10 Cronograma por semanas;
  - 11 KPI;
  - 12 Próximos pasos y anexos.
- **Diseño:** azul #172B46, naranja #F28C28, Montserrat e Inter incrustadas (`@fontsource/montserrat`, `@fontsource/inter`), colores de nivel de la propuesta.
- **Nombres formales de nivel solo en el PDF:** Crítico, Requiere mejora, Desempeño satisfactorio y Desempeño óptimo (`Niveles::NOMBRES_INFORME`). La pantalla conserva los del wireframe.
- **IA:**
  - cada categoría trae además impacto, debilidades, oportunidades, amenazas, información faltante y, por recomendación, la ficha de acción (problema, objetivo, rol responsable, recursos, semanas, KPI con fórmula, línea base, meta, frecuencia y fuente, y evidencia);
  - instrucciones nuevas: no inventar datos ni metas, y distinguir actividad de resultado.
- **Resultado de ejemplo:** ya no dice «con la IA real, aquí va…»; el PDF de ejemplo lo avisa en la portada y en la ficha técnica.
- **Datos:** `App\Support\DatosInforme` arma todo (prioridades, empates, aporte, DOFA, fichas, cronograma y KPI).
- **Resultados ya publicados:** se ven con lo que tienen; lo que no traen sale como «Por definir» o «Sin información suficiente».
- **[FUNCIONALIDAD POR DEFINIR] Fase 2 de la propuesta:**
  - evidencias de canales (capturas, enlaces);
  - análisis de competencia con datos verificados;
  - perfil y objetivos de marketing;
  - seguimiento de acciones entre mediciones;
  - revisión humana antes de publicar;
  - mejoras al cuestionario.
  Lo último lo hace el Administrador en A2.1, sin cambiar código.
- **[INFORMACIÓN PENDIENTE]** Logotipo de NuevasTIC para la portada: mientras tanto va el nombre en texto.

### Cambiado (informe PDF completo, 2 de noviembre)

- El PDF deja de ser un resumen de 3 páginas y pasa a ser un informe que se entiende sin la pantalla:
  - **Portada:** puntaje, nivel, frase y contenido.
  - **1 · Cómo leer este informe:** qué mide el diagnóstico, cómo se calcula el puntaje (con un ejemplo con los datos de la empresa), qué significa cada nivel, cómo hizo el análisis la IA y los 5 métodos (PESTEL, Porter, VRIO, DOFA, CAME) con sus grupos y en qué categorías se usó cada uno.
  - **2 · Resumen de resultados:** el puntaje en palabras, las categorías por nivel, el radar y la tabla, cada uno con cómo leerlo. La tabla suma el aporte al total y la prioridad.
  - **3 · Análisis por categoría:** una página por categoría con importancia, aporte, variación, qué significa su nivel, el diagnóstico, el método y lo que encontró por grupos, lo que ya se hace bien, las recomendaciones con pasos, plazo e indicador, y las respuestas con lo que dijo la IA de cada una.
  - **4 · Plan de acción:** todas las recomendaciones en tres partes (Primero, Después, Más adelante), con casilla para marcar y espacio para el responsable.
  - **5 · Próximos pasos y glosario.**
- Encabezado y "Página N de M" en cada hoja (`pdf/encabezado.blade.php`, `pdf/pie.blade.php`), márgenes más amplios.
- Textos nuevos: `MetodosAnalisis::EXPLICACION_GRUPOS` y `Niveles::EN_CATEGORIA`.

### Cambiado (análisis de la IA más completo, 2 de noviembre)

- **Cada categoría trae ahora**, en E6, A3.3, el resultado de ejemplo (A2.6) y "Probar" (A4.7):
  - el **diagnóstico** en 2 o 3 frases: qué pasa, por qué y qué le cuesta a la empresa;
  - **lo que encontró el método**, por sus grupos (DOFA: Fortalezas, Oportunidades, Debilidades, Amenazas; PESTEL, Porter, VRIO y CAME con los suyos);
  - **lo que ya hace bien** (0 a 2 frases);
  - **2 o 3 recomendaciones en orden**, cada una con 3 pasos, plazo y "Cómo saber si funcionó".
- `AnalizadorDeCategoria` pide y valida el formato nuevo: descarta grupos de otro método y reintenta si no hay hallazgos o recomendaciones con pasos.
- Los resultados publicados antes no cambian (RN-023): se siguen viendo con la observación y una recomendación.

### Corregido (29 de octubre)

- **Barra de arriba de E2–E4:** al marcar una respuesta, "✓ Guardado 8:25 p. m." cambiaba a "Guardando…", que es más corto, y la barra de avance con el porcentaje y la categoría se estiraba y volvía a encogerse. Ahora ese aviso tiene un ancho fijo y nada se mueve al guardar.
- **El análisis ya no se queda esperando:** `compose.yaml` tiene los servicios `queue` (`queue:work`) y `scheduler` (`schedule:work`), que arrancan con `sail up`. Antes había que dejarlos corriendo en otra terminal; si no, un diagnóstico enviado se quedaba en "Estamos analizando".
- **E4 · Espera del análisis:** dice "Suele tardar 1 o 2 minutos" (antes "menos de un minuto"), muestra hace cuánto se envió y, pasados 3 minutos, avisa que tarda más de lo normal, que puede salir porque le llegará un correo, y el correo de soporte.
- **Aviso de guardado sin parpadeo (E2–E4):** ya no pasa a "Guardando…" y vuelve en cada respuesta. Se queda "✓ Guardado 10:42 p. m." y solo cambia la hora cuando cambia el minuto. "Guardando…" sale solo si todavía no se ha guardado nada.

### Cambiado (responder el diagnóstico más fácil de leer, 28 de octubre)

- **E2 · Responder:** el cuestionario usa casi todo el ancho (hasta 64rem, antes 48rem); la pregunta va en 18 px y las opciones en 16 px; cada opción es un recuadro grande con borde (también las de "Marca todas las que apliquen"), con el foco del teclado visible; "Cómo responder" en un recuadro; número de la pregunta en un círculo que pasa a ✓ y la marca "Respondida". Pedido del equipo para leer mejor, también con baja visión.
- **E3 · Revisa antes de enviar:** el mismo diseño que E2: hasta 64rem de ancho, nombres de categoría en 18 px, avance en 16 px y botones grandes "Completar" o "Revisar" en cada categoría.
- **E4 · Diagnóstico enviado:** mismo estilo: tarjeta más ancha (48rem), título de 30 px, pasos en recuadros con círculos grandes, barra de avance más gruesa y botón "Ir a mi inicio".
- **Barra de arriba (E2–E4) en celular:** el avance pasa a un segundo renglón y ya no se monta sobre "Guardado" ni "Guardar y salir".
- **Diagnóstico de ejemplo:** `php artisan db:seed --class=DiagnosticoEjemploSeeder` publica en el sector Comidas un diagnóstico de EJEMPLO con 3 categorías ("Presencia en línea" con 7 preguntas de los tres tipos). No es de NuevasTIC y no lo carga la instalación.

### Cambiado (administrador principal, teléfono y cuentas que pasan al equipo, 28 de octubre)

- **Administrador principal (A5):** la cuenta del Administrador creada al instalar (columna `users.administrador_principal`; en una base que ya existía, la cuenta de Administrador más antigua) dice "Administrador principal". Ningún otro Administrador le cambia el rol, se lo asigna a otro rol, la desactiva ni la elimina. Es la cuenta de `ADMIN_EMAIL`, que define NuevasTIC al lanzar el proyecto (el seeder la marca aunque ya exista). Solo ella se elimina a sí misma, desde Mi perfil ("Eliminar mi cuenta"): elige a otro Administrador activo como nueva cuenta principal y escribe su correo; se puede recuperar durante 90 días.
- **Los colaboradores no cambian de rol:** ni con "Cambiar rol", ni con "Asignar a una cuenta", ni al crear un rol con cuentas, ni como cuenta principal de su empresa. Solo los desactivan o eliminan el Administrador (A5) o la cuenta principal de su empresa (E12). Las listas para asignar rol ya no los ofrecen (`CambioDeRol::permitido`).
- **Contraseña de la cuenta principal:** el seeder crea la cuenta de `ADMIN_EMAIL` **sin contraseña** y le envía "Crear mi contraseña" (RN-006): nadie más la conoce. Si el correo falla, la terminal muestra el enlace. `php artisan admin:invitar` lo reenvía (`App\Support\AdministradorPrincipal`). Se quitó `ADMIN_PASSWORD` del `.env`.
- **Teléfono en Mi perfil (A6, E11):** el teléfono nuevo se confirma con un código de 6 dígitos, como el correo. El código llega al correo actual o, si también cambia el correo, al correo nuevo (un solo código para los dos). Hasta confirmarlo se mantiene el anterior. Quitar el teléfono no pide código. Columna `users.telefono_pendiente`.
- **Cuenta de empresa que pasa a un rol interno (A5.5):** deja la empresa (los roles internos no tienen empresa), se borran su contraseña, su cargo y sus avisos, y le llega la invitación para crear una contraseña nueva, como a cualquier cuenta interna. Si era la cuenta principal, la empresa queda desactivada con sus mediciones y resultados. El modal de cambiar o asignar rol lo avisa antes (`App\Support\CambioDeRol`).
- **Dar una cuenta principal (A3.1d):** una empresa sin cuenta principal muestra "Sin cuenta principal" y el botón "Dar una cuenta principal". Es una cuenta nueva, que recibe la invitación para crear su contraseña. Sin cuenta principal la empresa no se reactiva, y sus datos se guardan sin los del usuario. Ruta `POST /empresas/{id}/cuenta-principal` (permiso `empresas.registrar`).

### Cambiado (roles y colaboradores, 26 de octubre)

- **Desactivar un rol (A5.1c)** bloquea a sus cuentas: no entran y se les cierra la sesión hasta que les asignes otro rol. Antes no se podía desactivar un rol con cuentas. Al desmarcar "Rol activo" sale el aviso con cuántas cuentas quedan fuera; en A5 esas cuentas dicen "Rol inactivo: no puede entrar". Nadie desactiva su propio rol.
- **Páginas de cada colaborador (E12.1, E12.3):** la cuenta principal elige qué ve cada colaborador: "Responder el diagnóstico", "Resultados y Mi historial" y "Descargar el informe PDF" (pide también los resultados). La lista de E12 tiene la columna "Puede ver". Los colaboradores que ya existían conservan las tres (migración `paginas_por_colaborador`). Para agregar una página nueva basta con sumarla en `App\Support\PaginasColaborador`.
- **El colaborador no cambia su perfil ni su contraseña (E11):** Mi perfil queda en solo lectura con un aviso; sin "Cambiar contraseña". "¿Olvidaste tu contraseña?" le manda un correo que dice que se la cambia la cuenta principal. El rol Colaborador queda sin permisos; las rutas de Mi perfil ahora piden "Editar su perfil" y "Cambiar su contraseña".
- Corregido (E1): con una medición no iniciada o en curso también salían "Pedir una nueva fecha" y el correo de soporte; ahora solo salen cuando la medición venció (HU-082).
- E1 sin "Responder" muestra "Tu empresa no te dio acceso para responder el diagnóstico."; sin "Resultados" no recibe puntajes.

### Cambiado (Usuarios y roles fija, 26 de octubre)

- **A5 · Usuarios:** la página queda fija sin importar cuántas cuentas haya. La tabla muestra las filas que caben en la pantalla y el paginado de Empresas (`useFilasQueCaben`), en lugar de 12 por página con desplazamiento.
- **A5.2 · Roles:** también fija; la lista de roles y el panel del rol bajan cada uno en su espacio.
- Se quitó la ruta pequeña "Usuarios y roles" de arriba, que repetía el título.

### Cambiado (decisiones de NuevasTIC, 23 de octubre)

- **Preguntas opcionales:** respondidas son un dato extra. La IA las lee (van marcadas como `dato_extra`), pero no cuentan en el puntaje; el resultado las muestra como "Dato extra".
- **"Probar con un ejemplo":** 100 pruebas al día entre todos los administradores (PA-003). La ventana dice cuántas quedan.
- **Al borrar para siempre una empresa**, sus mediciones terminadas se conservan sin nombre para estadísticas (`App\Support\Anonimizar`, migración `2026_10_23_000002`). Se borran el texto libre de las respuestas, el prompt guardado, el mensaje de la asignación y el PDF; se conservan sector, versión, fechas, puntajes y niveles. Las mediciones sin terminar se borran.
- RN-028 ajustada: la IA puede usar DOFA y los demás métodos. Frases de los cuatro niveles, lista de sectores, carácter especial de la contraseña y resumen semanal (lunes 7:50 a. m.) confirmados.
- `.env.example`: `OPENAI_MODEL="gpt-4.1-mini"`, el modelo que paga NuevasTIC.

### Agregado (nada igual a la contraseña actual, 22 de octubre)

- **En Mi perfil, la contraseña nueva no puede repetir partes de la actual:** ningún trozo de 4 caracteres seguidos o más, sin importar mayúsculas ni tildes ("Mesa-47-Sol" → "Mesa-99-Luna" no pasa). La lista de requisitos lo marca en vivo ("Nada igual a la contraseña actual"). Regla `SinPartesDeLaAnterior`.
- Al recuperar la contraseña (L4) y cuando la empresa cambia la de un colaborador no se conocen las partes de la anterior, porque está cifrada. Ahí, y también en Mi perfil, la nueva **no puede ser ninguna de las últimas 5**, aunque cambien mayúsculas y minúsculas (tabla `contrasenas_anteriores`, `HistorialContrasenas`). L4 lo avisa debajo de los requisitos. Las cuentas de antes de este cambio empiezan su historial con la próxima contraseña.

### Agregado (plazo de las mediciones, 22 de octubre)

- **Mínimo 3 días para responder.** Al asignar una medición (A3.1b, A3.2, ficha) o darle nueva fecha, la fecha límite más cercana que se acepta es dentro de 3 días. El calendario no deja elegir antes y el servidor lo valida. Se cambia con `PLAZO_MINIMO_DIAS`.
- **La fecha límite se cuenta en la hora de cada empresa:** vale hasta las 11:59 p. m. en la zona horaria de su cuenta principal (Mi perfil). La tarea `mediciones:vencer` corre cada hora para que cada empresa venza a su medianoche. E1 dice "Vence hoy a las 11:59 p. m.".

### Corregido (hora del sistema, 22 de octubre)

- El sistema contaba el día en hora UTC: en Colombia, una medición con fecha límite de hoy dejaba de poderse responder a las 7 p. m., y después de las 7 p. m. no se podía elegir "hoy" como fecha. Ahora usa la hora de cada empresa.
- La limpieza de cuentas (03:10) y el resumen semanal (lunes 07:50) corrían en hora UTC; ahora corren en hora de Colombia (`ZONA_HORARIA_POR_DEFECTO`).

### Corregido

- **Teléfono (L2, A3.2, A3.1d, A6, E11):** se elige el indicativo del país y el campo solo acepta los dígitos de ese país (Colombia 10, Argentina 11…). Se guarda como "+57 3001234567" y el servidor valida el largo (`TelefonoValido`, `App\Support\Telefonos`).
- **Informe PDF:** las etiquetas largas del radar se cortaban en el borde; ahora se parten en dos líneas. La página 1 suma "Tus categorías por nivel" y lo más fuerte y lo más débil, y la página 3 lista, después de las 3 prioridades, las demás categorías con su recomendación.
- A1 decía "Tiene la medición N en curso" aunque la pendiente no estuviera iniciada.

### Agregado (métodos de análisis y resultado de ejemplo, 14 de octubre)

- **La IA elige el método** de cada categoría según las respuestas (PESTEL, 5 Fuerzas de Porter, VRIO, DOFA o CAME) y el resultado y el PDF muestran cuál usó y por qué (DEC-019).
- **A2.6 · Resultado de ejemplo** (HU-030): mejor caso, caso medio y peor caso con el mismo cálculo que un resultado real, y el formato del PDF.
- La IA acepta el puntaje de las abiertas como `45.0` o `"45"` y el error dice qué devolvió si no la califica.

### Agregado (opción "Ninguna", 13 de octubre)

- En selección múltiple, una opción puede excluir a las demás ("Ninguna", "Nada"): al marcarla se desmarcan las otras, y el servidor no las acepta juntas. Columna `opciones.exclusiva`.

### Agregado ("Ver como", 13 de octubre)

- **A5.4 · "Ver como"** (HU-053, HU-054): desde Usuarios (A5) o la ficha de la empresa (A3.1), el Administrador ve el sistema como otra cuenta, con su menú y sus datos, en solo lectura, con la franja de aviso y "Salir de Ver como". Queda registrado quién lo usó y cuándo (RN-026).

### Agregado (historial, 13 de octubre)

- **E8 · Mi historial** (HU-067) y **A3.4 · Historial de una empresa** (HU-039): la pendiente, la evolución desde la 2.ª medición y las mediciones terminadas con resultado, respuestas y PDF. "Ver historial completo" en E1 y A3.1.

### Agregado (configuración de la IA, 13 de octubre)

- **A4 · Configuración de la IA** (HU-041 a HU-043): instrucciones generales con los datos que se insertan solos y "Restaurar texto original"; ajuste por sector y por empresa con usar, agregar o reemplazar; quitar ajuste.
- **A4.4 · Instrucciones completas** (HU-044) con el origen de cada parte, y **A4.7 · Probar con un ejemplo** (HU-045) sin guardar nada.
- **A4.9 · Cómo se arma el prompt final** (HU-080).
- El prompt reemplaza `{sector}`, `{contexto_sector}`, `{categoria}` y `{respuestas_categoria}`.

### Agregado (análisis de la IA y resultados, 13 de octubre)

- **Cálculo de puntajes** (HU-068): única, múltiple con tope 100, abiertas por la IA, promedio por categoría y total ponderado; nivel y variación contra la medición anterior.
- **Análisis con la IA por categoría** (HU-069, HU-081): prompt por capas (general, sector, empresa), "Ten en cuenta" solo de lo elegido, recomendación con título y detalle, 3 intentos por categoría y correo a los administradores si falla.
- **Publicar el resultado** (HU-084): inmutable, medición "Terminada", correo a la empresa y PDF de 3 páginas guardado.
- **E4** con avance real y "Reintentar esas categorías"; **E6 / A3.3 · Resultado**, **E9 / A3.5 · Mis respuestas** y descarga del **PDF**. Enlaces desde E1, A1 y A3.1.
- `IA_DE_PRUEBA` en `.env.example` para recorrer el flujo sin clave de OpenAI.

### Cambiado

- `recomendacion` de la IA pasa de texto a `{ titulo, detalle }`; los `id` de las preguntas son su `ref` ("p12").

### Agregado (la empresa responde, 13 de octubre)

- **E1 · Inicio de la empresa** con la medición pendiente en cada estado, el último resultado, la evolución y las áreas a trabajar.
- **E2 · Responder:** categorías, preguntas, guardado automático de cada respuesta y "Guardar y salir"; al volver sigue donde iba.
- **E3 · Revisar antes de enviar** y **E4 · Enviado**.
- **Pedir una nueva fecha** cuando la medición venció (HU-082): una solicitud a la vez, con aviso por correo a los administradores.
- `SOPORTE_EMAIL` en `.env.example`.

### Agregado (editor de diagnósticos, 13 de octubre)

- **A2.1 · Editor:** categorías con su estado, preguntas que se ordenan arrastrando (o con el teclado), agregar, editar y eliminar preguntas (A2.1b, A2.1e, A2.1f) con opciones, puntajes, indicación y "Ten en cuenta" para la IA.
- **Agregar categoría** del catálogo, manteniendo los pesos o repartiendo en partes iguales.
- **A2.1c · Editar importancia:** total en vivo, guardar solo con 100%.
- **A2.4 · Vista previa y estado:** los 5 requisitos para publicar, categorías por corregir con "Corregir" y el cuestionario como lo ve la empresa.
- **Versiones y publicar (A2.1d):** historial, nota de cambios obligatoria y qué hacer con las mediciones pendientes. Cada cambio a un diagnóstico publicado abre el siguiente borrador.
- Crear y duplicar un diagnóstico ahora abren el editor.

### Agregado (correo de aviso y vencidas, 13 de octubre)

- **A3.1e · Correo de aviso:** asunto y mensaje con variables, vista previa con los datos reales, "Enviarme un correo de prueba" y "Guardar como plantilla". Se abre desde A3.1b.
- **Recordatorio y Reenviar aviso** (A1b, A1c, HU-008) en el Inicio y en la ficha; se guarda y se muestra el último aviso (`mediciones.ultimo_aviso_en`).
- El correo de aviso va en la cola (no bloquea la pantalla y se reintenta).
- **Tarea diaria `mediciones:vencer`** (HU-070): las mediciones sin enviar que pasaron su fecha límite quedan vencidas.
- Corregido: "Nueva fecha" desde el Inicio abría el modal sin la fecha actual.

## Semana 3 · del 5 al 11 de octubre de 2026

### Agregado

- Pantallas de acceso con el diseño del wireframe: L1 (iniciar sesión), L2 (registrar la empresa), L3 (olvidé mi contraseña) y L4 (nueva contraseña).
- Diagnósticos del Administrador: A2 (un sector), A2·T (todos), A2b (sector vacío), modales de sector (A2.2 a A2.2e), duplicar (A2.7), archivar y eliminar.
- Catálogo de categorías (A2.3) según el wireframe: filtro Todas / En uso / Archivadas, panel para editar, modales de crear y archivar, y «Restaurar».
- Crear diagnóstico (A2.5), en blanco o copiando uno publicado.
- Componentes base de formulario: `Campo`, `Entrada`, `Seleccion`, `AreaTexto`, `CampoContrasena`, `RequisitosContrasena`, `Aviso` y `TarjetaOpcion`.
- `lib/contrasena.ts` (requisitos de RN-001, con pruebas) y `lib/rutas.ts` (URLs propuestas).
- Vistas previas con datos de ejemplo: `/prueba-tecnica/vistas/{vista}` lee `resources/datos-ejemplo/{vista}.json`.
- Documentos `14_FRONTEND.md`, `15_BACKEND.md` y `18_UI_UX.md`.
- Backend mínimo para el registro: tablas `sectores` y `empresas`, roles y permisos, 7 sectores de ejemplo (luego 6, sin Talleres), Administrador inicial (`migrate --seed`) y registro que crea la empresa con su usuario de rol Empresa.
- Inicio provisional en español para revisar el menú de cada rol.
- Logo del sistema en el menú, el acceso y el ícono del navegador. El sistema se llama **Captter**.
- Mi perfil (A6 y E11) conectado: datos personales, datos de la empresa para la cuenta principal, avisos por correo, foto o logo y cambio de contraseña. Se guarda el último acceso.
- Al abrir el sistema (`/`) se entra directo al inicio de sesión.
- Seguridad del acceso completa: cuentas y empresas desactivadas no entran, contraseña fuerte en todos los entornos, mismo aviso en la recuperación, enlace sin vencimiento, límite de intentos, mensajes y correo en español, constancia de los términos (DEC-014).
- Diccionario de datos de las tablas del acceso.
- Usuarios y roles (A5, A5.1–A5.5): lista de cuentas con búsqueda y filtros, roles del sistema y creados, modales de invitar, desactivar, cambiar y asignar rol, crear y eliminar rol, conectados al backend (invitaciones por correo, desactivar, cambiar y asignar rol, CRUD de roles). "Ver como" llega con A5.4.
- Colaboradores de la empresa (E12, E12.1–E12.3): lista, agregar, datos para compartir, cambiar contraseña, desactivar y reactivar. Conectado al backend (`ColaboradoresController`): solo la cuenta principal entra; cambiar la contraseña o desactivar cierra las sesiones del colaborador.
- Documentos 02 (visión y objetivos), 04 (stakeholders), 06 (requisitos no funcionales), 09 (flujos, con el diagrama de secuencia de la medición) y 10 (arquitectura).

### Agregado (Empresas, 11 de octubre)

- **A3 · Empresas:** lista con búsqueda, filtros (sector, nivel, cuenta y medición) y paginado por filas que caben.
- **A3.1 · Ficha:** último puntaje, variación, gráfica de evolución con franjas de nivel, mediciones, la pendiente con "Cambiar fecha" y "Cancelar medición", y "Asignar otra medición".
- **A3.1d · Datos de la cuenta** y **A3.1f · Desactivar / Reactivar** (nadie de la empresa entra; se conservan sus mediciones).
- **A3.2 · Registrar empresa:** la empresa y su usuario principal, con invitación para crear la contraseña y la primera medición opcional.
- **A3.1b · Asignar medición** con buscador, borradores visibles pero bloqueados, vista previa "Así lo verá la empresa", aviso por correo y **A3.1c** para reemplazar la pendiente.
- A1: "Nueva fecha", "Cancelar" y "Asignar nueva" conectadas.
- Backend: `EmpresasController`, `MedicionesController`, `DatosEmpresas`, `AsignarMedicion`, `Invitaciones`, notificaciones `AvisoMedicion` y `EmpresaDesactivada`.
- El número de medición es único por empresa solo entre las no canceladas (índice parcial): la que reemplaza toma el mismo número.

### Agregado (modelo de datos y backend de A2, 8 de octubre)

- Migraciones, modelos y fábricas de todo el MER (T-045, T-058): categorías, diagnósticos, sus categorías con importancia, preguntas, opciones, versiones congeladas en JSONB, mediciones, respuestas, análisis de la IA, resultados, solicitudes de medición, prompts, plantilla de correo y registro de "Ver como".
- Datos iniciales (T-046): las 10 categorías del wireframe (`CategoriasSeeder`) y los niveles de RN-019 en `App\Support\Niveles`.
- Backend de A2 conectado (T-049, T-050): diagnósticos (listar, crear en blanco o copiando, duplicar, archivar, eliminar el borrador), sectores (crear, editar, reasignar con aviso por correo, desactivar, reactivar, eliminar) y catálogo de categorías (crear, editar, archivar, restaurar, eliminar). Pruebas en `tests/Feature/DiagnosticosYCatalogoTest.php`.
- Redirección después del login según el rol (T-048): `/dashboard` lleva a A1 (`/inicio`) o a E1 (`/mi-inicio`). A1 y E1 son pantallas de bienvenida hasta las semanas 5 y 6.
- Documentos: `08_CASOS_DE_USO.md` (T-041) y `30_RIESGOS.md` (T-067). Diccionario de datos de todas las tablas, cada tabla con un ejemplo y los campos JSONB (T-040, T-064). Rutas por módulo en `15_BACKEND.md` (T-043). Tabla historia → requisito en `05_REQUISITOS_FUNCIONALES.md` (T-061). Términos técnicos nuevos en el glosario (T-068).

### Cambiado (Diagnósticos, A2)

- La página queda fija; solo bajan y suben la lista de sectores, la tabla de diagnósticos y la de empresas, cada una en su espacio.
- Barra de búsqueda de diagnósticos por nombre.
- Dentro de un mismo sector, dos diagnósticos no pueden tener el mismo nombre (sin mirar mayúsculas ni espacios de más).
- "Todos los diagnósticos" ya no agrupa por sector: tiene la columna "Sector" antes de "Estado".
- Catálogo de categorías: «Archivar» está siempre disponible (en la fila y en el modal de editar); «Eliminar» aparece además cuando ninguna empresa ha respondido la categoría.
- Zona horaria en Mi perfil: ahora ofrece las 45 zonas oficiales (IANA) de los 18 países, como «México · Tijuana (UTC−7)», en vez de 5. Si una cuenta tenía una zona de antes que ya no está (Europe/Madrid), se conserva.
- El menú lateral se puede cerrar y abrir (botón junto al logo): cerrado queda una barra angosta con los iconos de cada sección (el nombre sale al pasar el ratón), la inicial de la cuenta y «Cerrar sesión». Se recuerda en el navegador. En el celular el menú sigue igual.
- **A1 · Inicio del Administrador** (HU-006, HU-007): 4 indicadores (empresas registradas, mediciones pendientes, diagnósticos completados este mes y puntaje promedio), tabla «Mediciones» con pestañas por estado, avance por categorías y paginado, «Empresas por nivel» y «Diagnósticos por sector». Las acciones de la tabla llevan por ahora a la ficha de la empresa.
- El nombre del sistema se muestra en mayúsculas, «CAPTTER», junto al logo (menú, acceso y barra del celular).
- El registro (L2) cabe en la pantalla sin bajar, en los dos pasos, desde 1280 × 720: nombre de la empresa y sector lado a lado, sin los títulos «Mi empresa» y «Tu usuario» (ya los dice la barra de pasos), descripción de 2 líneas y menos espacio entre campos.
- El nombre del sector admite hasta 60 caracteres (antes 40), para que quepan todas las divisiones CIIU.
- Nombres cortos en el catálogo CIIU (71 divisiones y 88 clases, del Excel del equipo); se guarda también el nombre oficial del DANE. Los subsectores existentes toman el nombre corto.
- Catálogo CIIU Rev. 5 A.C. del DANE en la base (87 divisiones, 544 clases). Al crear o editar un sector se elige su división CIIU y sus clases pasan a ser los subsectores; si el nombre es exactamente el de una división, se elige sola. Los 6 sectores iniciales pasan a la Rev. 5 (DEC-018).
- Se quitó el sector «Talleres» (y sus 6 actividades CIIU) de los datos iniciales: quedan 6 sectores, como dice T-046.
- Eliminar varios diagnósticos a la vez: casillas en la tabla, "Eliminar seleccionados" y confirmación. Solo los que nunca se publicaron (`DELETE /diagnosticos`).
- Se quitó "Empresas del sector" de A2 para ganar espacio; queda para la lista de Empresas filtrada por sector (A3). El resumen del sector enlaza a ella.
- Listas paginadas en lugar de bajar y subir: todos los diagnósticos y los de cada sector (las filas que caben en la pantalla) y el catálogo de categorías (10 por página). Componente `Paginacion` y `lib/paginacion.ts`, con pruebas.
- Catálogo de categorías (A2.3) y Crear diagnóstico (A2.5): flecha "← Diagnósticos" arriba del título para volver, en lugar de la ruta pequeña "Diagnósticos › …". En Crear vuelve al sector del que se vino.
- Catálogo de categorías (A2.3): "Editar" abre un modal en el centro en lugar del panel de la derecha; la tabla usa todo el ancho.
- Duplicar, Archivar y Eliminar pasan a un menú dentro de "Editar ▾" para ahorrar espacio.

### Cambiado (registro)

- El registro (L2) va en dos pasos: Mi empresa y Tu usuario. Se agregan la actividad económica (códigos CIIU por sector, tabla `actividades_economicas`) y la descripción corta de máximo 300 caracteres; el cargo pasa a ser obligatorio (DEC-015).

### Cambiado (ubicación)

- País → departamento → ciudad en el registro y en Mi perfil, con búsqueda al escribir. Los 18 países de Hispanoamérica; la ciudad se puede escribir si no está en la lista. Nueva columna `departamento`, ruta `GET /ubicaciones/{pais}`, componentes `Combobox` y `SelectorUbicacion`, comando `ubicaciones:generar` (DEC-016).

### Agregado (eliminar cuentas)

- El Administrador puede eliminar cuentas desde Usuarios y roles: "Desactivar / Eliminar" con segunda confirmación escribiendo el correo exacto. La cuenta sale de la vista pero se puede recuperar durante 90 días (filtro «Eliminadas» → "Recuperar"); después la tarea diaria `cuentas:purgar` la borra para siempre. La cuenta principal se elimina y se recupera con su empresa y sus colaboradores; nunca la propia ni el último Administrador (DEC-017).

### Cambiado (colaboradores)

- La empresa puede eliminar colaboradores ("Desactivar / Eliminar", doble confirmación con botones); el Administrador los recupera en 90 días.
- "Cambiar contraseña" de un colaborador pasa a ser "Editar": nombre, cargo, correo y, si se quiere, contraseña nueva.
- Al agregar un colaborador se pide su cargo (Marketing, Producción…, o escrito). La tabla de Colaboradores muestra el cargo y ya no el último acceso.

### Quitado

- La columna "Último acceso" de Usuarios y roles (A5). El dato se sigue guardando en `users.ultimo_acceso_en`.
- Las cuentas de demostración (`DemoSeeder`, `DEMO_PASSWORD`). El equipo trabaja con cuentas creadas por ellos mismos.

### Cambiado (Mi perfil de la empresa)

- E11 muestra y deja cambiar a la cuenta principal la actividad económica (subsector) y la descripción corta; el colaborador las ve bloqueadas.

### Cambiado (formularios)

- Asterisco rojo en los campos obligatorios de todos los formularios.
- La ciudad escrita a mano solo se guarda eligiendo "Usar «…»"; un texto a medias ya no se acepta.

### Cambiado (inicio de sesión)

- Cuenta o empresa desactivada: con la contraseña correcta, L1 muestra el modal "Su cuenta ha sido desactivada. Diríjase a Captter para saber más detalles."; con la contraseña equivocada sigue el mensaje genérico (RN-005). También se muestra si la desactivan con la sesión abierta.
- Después de 5 intentos fallidos en un minuto, L1 muestra el modal "Demasiados intentos" con la cuenta regresiva y desactiva el botón hasta que pase.

### Cambiado (roles)

- La matriz de roles y permisos sigue el wireframe A5.2: 15 permisos en 6 bloques; Administrador 15, Empresa 5, Colaborador 5 (también edita su perfil). Rol Consultor creado inactivo; los roles guardan descripción, si están activos y si son del sistema. «Colaboradores» del menú se decide por el rol Empresa.

### Cambiado

- `AuthLayout` reemplaza los layouts de acceso del kit.
- Los modales enfocan el primer campo al abrirse.
- Se quitaron las páginas de ajustes del kit y la opción de eliminar la cuenta (RN-004).
- El fondo del menú lateral llega hasta abajo en páginas largas.

## Semana 2 · del 28 de septiembre al 4 de octubre de 2026 (cerrada el 5 de octubre)

### Agregado

- Proyecto creado con el kit de inicio de Laravel para Vue: Laravel 13, Inertia 3, Vue 3.5, Tailwind 4 y Reka UI.
- Laravel Sail con PHP 8.4, PostgreSQL 18, Mailpit y Chromium (Dockerfile publicado en `docker/8.4`).
- Versiones fijas: `composer.lock`, `package-lock.json`, `.nvmrc` (Node 24) y PHP 8.4 en `config.platform`.
- Librerías del stack:
  - spatie/laravel-permission, openai-php/laravel, spatie/laravel-pdf con Browsershot y Pest 5;
  - Chart.js, vue-chartjs, chartjs-plugin-annotation, vue-draggable-plus y puppeteer;
  - las fuentes IBM Plex.
- Prueba técnica:
  - ruta bloqueada por rol;
  - análisis de una categoría con OpenAI (`prueba:ia`);
  - radar de Chart.js;
  - PDF con el radar (`prueba:pdf`).
- Estilos del wireframe en Tailwind y componentes base en `resources/js/components/base/`.
- Vitest configurado (`npm test`) y CI con PHP 8.4, Node 24 y PostgreSQL 18.
- Documentación inicial: `CLAUDE.md`, `README.md`, `.env.example` y los documentos de `docs/` marcados en el índice.

### Cambiado

- Guzzle 8 → 7, que es la versión que exige openai-php/laravel (DEC-009).
- PHPUnit 12 → 13, que es la versión que exige Pest 5 (DEC-007).
- El menú lateral del kit se reemplazó por el del wireframe (`MenuLateral.vue` y `PanelLayout.vue`).

### Quitado

Funciones del kit que no están en el diseño (DEC-012):

- verificación de correo;
- 2FA;
- passkeys;
- confirmación de contraseña;
- modo oscuro (apariencia).

## Semana 1 · del 21 al 27 de septiembre de 2026

- Wireframes, mapas de flujo y prototipo navegable: 77 vistas, 55 pantallas y 22 modales (T-001). Fuera del repositorio.
