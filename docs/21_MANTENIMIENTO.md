# Mantenimiento

**Estado:** COMPLETO (T-156). Guía para quien siga con el sistema después de la entrega (6 de noviembre de 2026).

**[INFORMACIÓN PENDIENTE]** NuevasTIC no ha definido quién mantiene el sistema después de la práctica. Este documento sirve para que esa persona o equipo empiece sin depender de los autores.

## 1. Tareas frecuentes

### Agregar o cambiar un diagnóstico

No requiere programar. Se hace en Diagnósticos (A2):

- se crea un borrador o se edita;
- se publica una versión nueva;
- las mediciones ya hechas conservan su versión (RN-010).

### Cambiar cómo analiza la IA

No requiere programar. Se hace en Configuración IA (A4) con las capas general, sector y empresa. "Probar con un ejemplo" muestra el resultado antes de guardar, pero gasta una llamada a OpenAI.

### Cambiar el modelo de OpenAI

1. Cambiar `OPENAI_MODEL` en el `.env`.
2. Ejecutar `php artisan config:clear`.
3. Comprobar con `php artisan prueba:ia`.

El modelo debe aceptar la salida estructurada (`json_schema` estricto).

### Una medición quedó "Enviada" y no avanza

1. Comprobar que la cola esté corriendo: `php artisan queue:work`.
2. Ver si alguna categoría falló: tabla `analisis_categoria`, estado `fallido`. También llega el correo `AnalisisFallido` a los administradores.
3. La empresa puede pulsar "Reintentar" en E4. También se puede reintentar desde la consola:

   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```

### No se marcan las vencidas o no se purgan las cuentas eliminadas

Falta el *scheduler*. En local se corre `php artisan schedule:work`; en un servidor, el cron de `schedule:run`. Para correr cada tarea a mano:

```bash
php artisan mediciones:vencer
php artisan cuentas:purgar
php artisan mediciones:resumen
```

### Falla un PDF

El resultado sigue publicado. El PDF se genera de nuevo al descargarlo. Si sigue fallando:

1. Revisar `storage/logs/laravel.log`.
2. Comprobar Chromium con `php artisan prueba:pdf`.
3. Revisar que `LARAVEL_PDF_CHROME_PATH` apunte a Chromium.

### Olvidaron la contraseña del Administrador

1. Pedir el enlace en "¿Olvidaste tu contraseña?" (L3).
2. Si el correo no funciona, cambiarla desde la consola:

   ```bash
   php artisan tinker
   >>> App\Models\User::where('email', 'admin@…')->first()->forceFill(['password' => Hash::make('…')])->save();
   ```

## 2. Actualizar dependencias

| Qué | Cómo | Cuidado |
|---|---|---|
| PHP (Composer) | `composer update` y luego `php artisan test` | PHP está fijado en 8.4 (`config.platform.php`) |
| JavaScript (npm) | `npm update` y luego `npm test` y `npm run build` | Revisar Inertia, Vue y Chart.js entre versiones mayores |
| Imagen de Sail | `./vendor/bin/sail build --no-cache` | Vuelve a instalar Chromium |

Antes de subir, debe pasar `composer ci:check`.

## 3. Registros y monitoreo

- **Errores:** `storage/logs/laravel.log`.
- **Tareas fallidas de la cola:** `php artisan queue:failed`.
- **"Ver como":** queda registrado en la tabla `registros_ver_como` (quién, a qué cuenta, desde y hasta).
- **Correos:** en local, Mailpit (`http://localhost:8025`).

**[FUNCIONALIDAD POR DEFINIR]** No hay monitoreo ni alertas automáticas. En un servidor convendría avisar cuando la cola se detenga o crezca `failed_jobs`.

## 4. Datos

- **Respaldo:** `pg_dump` de la base y una copia de `storage/app/private` (`16_INFRAESTRUCTURA.md`).
- **Cuentas eliminadas:** se borran solas a los 90 días (se cambia con la variable `DIAS_PARA_RECUPERAR_CUENTA`, `config/diagnostico.php`).
- **Resultados:** no se borran ni se editan (RN-023). Para corregir uno, se asigna una medición nueva.
- Al borrar para siempre una empresa, sus mediciones terminadas se conservan sin nombre para las estadísticas (decisión de NuevasTIC, 23 de octubre de 2026; `App\Support\Anonimizar`).

## 5. Dónde cambiar cada cosa en el código

| Cambio | Archivo |
|---|---|
| Rangos o frases de los niveles | `app/Support/Niveles.php` y `resources/js/lib/niveles.ts` (mismas claves) |
| Métodos de análisis de la IA | `app/Support/MetodosAnalisis.php` y el esquema de `AnalizadorDeCategoria` |
| Instrucciones de la IA por defecto | `ArmadorPrompt::GENERAL` (lo que se edita en A4 queda en la tabla `prompts`) |
| Plantilla del PDF | `resources/views/pdf/informe.blade.php` |
| Correos | `app/Notifications/*` |
| Colores del wireframe | `resources/css/app.css` |
| Menú por rol | `resources/js/lib/menu.ts` (y el permiso en `routes/web.php`) |
| Permisos de cada rol | `database/seeders/RolesYPermisosSeeder.php` y la matriz de `17_SEGURIDAD.md` |

Cada cambio sigue `28_CONVENCIONES_DESARROLLO.md`: rama, pruebas, *pull request* con revisión y documento actualizado.
