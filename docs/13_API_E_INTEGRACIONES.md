# API e integraciones

**Estado:** COMPLETO (T-113). Actualizado el 14 de octubre de 2026.

## 1. ¿Hay una API?

**No hay API pública.** Laravel y Vue se comunican con Inertia (DEC-002): cada pantalla recibe sus datos como props al cargar, y los formularios envían a rutas web normales, con la sesión y el token CSRF. Ningún sistema externo consume datos del sistema.

Para no recargar la página, algunas acciones responden JSON. Usan la misma sesión, el mismo CSRF (`X-XSRF-TOKEN`) y el mismo permiso que la pantalla.

| Método y ruta | Para qué | Permiso | Límite | Respuesta |
|---|---|---|---|---|
| `PUT /mi-diagnostico/{medicion}/respuestas` | Guardar una respuesta mientras se responde (E2) | `diagnostico.responder` y ser de la empresa | 120 por minuto | `{ avance, guardado_en }`; 422 si ya no se puede responder |
| `GET /mediciones/{medicion}/correo` | Datos del correo de aviso para editarlo (A3.1e) | `mediciones.asignar` | — | `{ asunto, cuerpo, para, variables, ultimo_aviso }` |
| `POST /configuracion-ia/vista-previa` | Prompt armado con lo que se está editando (A4) | `ia.ver` | 60 por minuto | `{ ejemplo, … }` con el prompt por partes |
| `POST /configuracion-ia/probar` | "Probar con un ejemplo": una llamada real a la IA (A4) | `ia.editar` | 10 por minuto | El análisis de la categoría de ejemplo |
| `GET /ubicaciones/{pais}` | Departamentos y ciudades de un país (L2, A3.2, Mi perfil) | Ninguno (se usa en el registro) | 60 por minuto | `[{ nombre, ciudades }]` (en caché 1 día) |

Las rutas completas, con su controlador y permiso, están en `15_BACKEND.md`. La protección de cada una, en `17_SEGURIDAD.md`.

## 2. OpenAI

| Qué | Valor |
|---|---|
| Librería | `openai-php/laravel` 0.21 |
| Variables | `OPENAI_API_KEY`, `OPENAI_ORGANIZATION` (opcional), `OPENAI_MODEL`, `OPENAI_REQUEST_TIMEOUT` (30 s) |
| Llamada | `OpenAI::chat()->create()`, una por categoría de cada medición (RN-021) |
| Mensajes | `system`: instrucciones armadas por capas (`ArmadorPrompt`). `user`: la categoría con sus preguntas y respuestas en JSON |
| Formato | `response_format: json_schema` con `strict: true` (`AnalizadorDeCategoria::esquema()`) |
| Validación propia | `AnalizadorDeCategoria::validar()`: una entrada por pregunta y puntajes de las abiertas entre 0 y 100 (acepta `45` o `"45"`) |
| Reintentos | La cola reintenta solo esa categoría: 3 intentos, con 15 s y 60 s de espera |
| Si falla del todo | La categoría queda "fallido", llega el correo `AnalisisFallido` a los administradores y la empresa ve "Reintentar" en E4 |

### Formato de la respuesta

```json
{
  "preguntas": [
    { "id": "p17", "puntaje": null, "observacion": "Tiene su cliente ideal por escrito." },
    { "id": "p18", "puntaje": 45, "observacion": "Publica sin un plan." }
  ],
  "metodo": "DOFA",
  "metodo_motivo": "Mezcla fortalezas internas con amenazas del mercado.",
  "observacion_categoria": "Conoce a su cliente, pero no mide sus canales.",
  "recomendacion": { "titulo": "Mide tus canales", "detalle": "Pon un enlace con UTM en cada red durante un mes." }
}
```

- `puntaje` solo va en las abiertas; en las cerradas llega `null`, porque el sistema ya lo calculó.
- `metodo` es uno de `PESTEL`, `PORTER`, `VRIO`, `DOFA` o `CAME` (DEC-019).

### Sin clave

- Con `IA_DE_PRUEBA=true`, `AnalizadorDePrueba` devuelve textos y puntajes de prueba sin conexión. Sirve para programar; nunca en producción.
- Las pruebas automáticas no llaman a OpenAI (`OpenAI::fake()` o el analizador de prueba).
- `php artisan prueba:ia` hace una llamada real para comprobar la clave y el modelo.

### Costo

Cada medición hace una llamada por categoría (normalmente 10). En desarrollo se usó `gpt-4.1-nano` con un saldo de prueba de 5 USD. **[INFORMACIÓN PENDIENTE]** El modelo y quién paga en producción los decide NuevasTIC.

## 3. Correo (SMTP)

En local todo va a Mailpit (`27_ENTORNOS.md`). En otro servidor se configura `MAIL_*` en el `.env`.

| Correo | Cuándo sale | Para quién | En cola |
|---|---|---|---|
| `InvitacionCuenta` | Al invitar una cuenta (A5) o registrar una empresa (A3.2) | La cuenta nueva | No |
| Restablecer contraseña (Fortify) | Al pedirlo en L3 | Quien lo pide, si existe | No |
| `AvisoMedicion` | Al asignar con aviso, al recordar o reenviar (A3.1e, A1) | Cuenta principal de la empresa | Sí |
| `NuevaMedicionSolicitada` | La empresa pide una nueva medición (HU-082) | Administradores activos | Sí |
| `DiagnosticoEnviado` | Una empresa envía su diagnóstico | Administradores activos con el aviso encendido | Sí |
| `ResumenSemanal` | Lunes a las 07:50 (`mediciones:resumen`) | Administradores que lo encendieron | Sí |
| `ResultadoListo` | Al publicar el resultado | Cuentas activas de la empresa con el aviso encendido | Sí |
| `AnalisisFallido` | Una categoría agotó los 3 intentos | Administradores activos con el aviso encendido | Sí |
| `EmpresaDesactivada` | Al desactivar una empresa | Sus cuentas | No |
| `RolCambiado` | Al cambiar el rol de una cuenta | Esa cuenta | No |
| `SectorReasignado` | Al pasar las empresas a otro sector | Las empresas movidas | No |

**Preferencias (Mi perfil, A6 y E11).** Cada cuenta enciende o apaga sus avisos y cada correo lo respeta (`User::quiereAviso`):

- **Administrador:** "Cuando una empresa envía su diagnóstico", "Cuando falla el análisis de la IA" y "Resumen semanal" (apagado al empezar).
- **Empresa y colaborador:** "Cuando me asignen una medición" y "Cuando mi resultado esté listo".

Si la empresa apagó el aviso de mediciones nuevas, la medición se asigna igual y A3.1 ofrece "Reenviar aviso". Los recordatorios que pide el Administrador salen siempre.

Los que van en cola necesitan `php artisan queue:work` (en local con `QUEUE_CONNECTION=database`). Los demás se envían al momento.

## 4. Chromium (PDF)

- `spatie/laravel-pdf` con Browsershot abre Chromium sin interfaz y convierte una vista Blade (`resources/views/pdf/informe.blade.php`) en PDF.
- Variables: `LARAVEL_PDF_CHROME_PATH` y `LARAVEL_PDF_NO_SANDBOX`.
- Chromium trabaja sin internet: Chart.js, las fuentes y el logo se incrustan en el HTML (`RecursosPdf`).
- El PDF se genera en la cola al publicar y se guarda en `storage/app/private`. Si falta, se genera al descargarlo.

## 5. Catálogos externos (archivos del repositorio)

| Catálogo | Fuente | Dónde está | Uso |
|---|---|---|---|
| CIIU Rev. 5 A.C. | DANE (DEC-018) | `resources/ciiu/` | Actividad económica de la empresa (L2, A3.2) |
| Países, departamentos y ciudades | `countries-states-cities-database` | `resources/ubicaciones/{ISO}.json` (los genera `php artisan ubicaciones:generar`) | Ubicación de la empresa |

No se consultan en línea: están en el repositorio y no cambian mientras el sistema corre.

## 6. WhatsApp (aplazado)

El bot de WhatsApp (EP-013, HU-071 a HU-075) está aplazado (`03_ALCANCE.md`). No hay código ni variables. Si se retoma:

- el webhook iría en `/webhook/whatsapp`, protegido con la firma del proveedor y sin sesión;
- sus tablas irían separadas de `empresas` y `mediciones` (RN-029).
