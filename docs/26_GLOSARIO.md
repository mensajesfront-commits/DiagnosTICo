# Glosario

**Estado:** EN CURSO (T-036). Se completa cada semana con los términos nuevos.

## Términos del negocio

| Término | Qué significa |
|---|---|
| **Sector** | Grupo de empresas del mismo rubro (Abogados, Comidas, Turismo…). Cada sector tiene sus propios diagnósticos. Una empresa pertenece a un solo sector y no lo cambia sola. |
| **Categoría** | Tema que se evalúa dentro del diagnóstico (Análisis del mercado, Presencia en línea, Recursos humanos…). Sale de un catálogo común para que todos los diagnósticos usen los mismos nombres. Nombre de máximo 40 caracteres y único. |
| **Diagnóstico** | Cuestionario de un sector, organizado por categorías, con preguntas y opciones con puntaje. Un sector puede tener varios (por ejemplo, "general" y "rápido"). |
| **Pregunta** | Puede ser abierta (la califica la IA de 0 a 100), de opción única o de selección múltiple (los puntajes se suman, con tope de 100). Lleva una indicación obligatoria para responder. |
| **Indicación** | Texto debajo de la pregunta que explica cómo responder ("Piensa en tu cliente más frecuente…"). |
| **Ten en cuenta** | Nota opcional por opción, de máximo 200 caracteres, que la empresa no ve. Se agrega al prompt de la IA solo si esa opción fue elegida. |
| **Importancia** | Porcentaje que pesa cada categoría en el puntaje total. Es propio de cada diagnóstico y debe sumar exactamente 100%. |
| **Borrador** | Versión del diagnóstico que se está editando. No se puede asignar. |
| **Versión** | Copia publicada e inmutable de un diagnóstico (v1, v2, v3…), con su nota de cambios. Las mediciones usan siempre una versión publicada. |
| **Medición** | Una vez que una empresa responde un diagnóstico: se asigna con una versión, una fecha límite opcional y un mensaje. Se numera por empresa (Medición 1, 2, 3…). Solo puede haber una pendiente por empresa. |
| **Estado de la medición** | No iniciada → En curso → Enviada (la IA está analizando) → Terminada (resultado publicado). Vencida si pasó la fecha límite sin enviarse. |
| **Puntaje** | De 0 a 100. Por pregunta: el de la opción, la suma de las opciones con tope de 100, o la calificación de la IA. Por categoría: el promedio de sus preguntas. Total: el promedio ponderado por la importancia. |
| **Nivel** | Lectura del puntaje: Crítico (0–29), Se puede mejorar (30–59), Vas en buen camino (60–79), Sigue así (80–100). |
| **Variación** | Diferencia del puntaje frente a la medición anterior de la misma empresa. Existe desde la 2.ª medición. |
| **Resultado** | Lo que recibe la empresa al terminar el análisis: puntaje, nivel, puntaje por categoría, observaciones, recomendaciones y PDF. Se publica sin revisión y no cambia. |
| **Prompt** | Instrucciones que el sistema le envía a la IA para analizar una categoría. Se arma por capas: general, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo. |
| **Colaborador** | Persona de la empresa con su propia cuenta, creada por el usuario principal. Puede responder y ver resultados, pero no gestiona colaboradores ni cambia los datos de la empresa. |
| **Ver como** | Función del Administrador para ver el sistema como otra cuenta, en solo lectura. Queda registrado quién lo usó y cuándo. |

## Términos técnicos

| Término | Qué significa |
|---|---|
| **Sail** | Entorno de Docker de Laravel. Levanta PHP 8.4, PostgreSQL 18, Mailpit y Chromium iguales en los dos equipos. |
| **Inertia** | Pieza que une Laravel y Vue: el controlador entrega los datos y abre la pantalla Vue sin una API aparte. |
| **Wayfinder** | Genera funciones de TypeScript con las rutas de Laravel (`import { login } from '@/routes'`) para no escribir URLs a mano. |
| **JSONB** | Tipo de columna de PostgreSQL que guarda JSON. Se usa para las versiones congeladas y las respuestas de la IA. |
| **Salida estructurada** | Modo de la API de OpenAI que obliga a responder con un JSON que cumple un esquema. |
| **Browsershot** | Librería que maneja Chromium desde PHP para convertir HTML en PDF. |
| **Mailpit** | Servidor de correo de prueba: atrapa los correos para verlos en `http://localhost:8025` sin enviarlos. |
| **Pest / Vitest** | Herramientas de pruebas automáticas del backend y del frontend. |
| **Pint / Oxlint** | Formateadores y revisores de código: Pint para PHP y Oxlint con Oxfmt (vía Vite+) para TypeScript y Vue. |
| **Migración** | Archivo de PHP que crea o cambia una tabla de la base de datos (`database/migrations/`). Se corre con `php artisan migrate` y así las dos personas tienen la misma estructura. |
| **Seeder** | Archivo que carga datos iniciales en la base (`database/seeders/`): roles y permisos, sectores, actividades CIIU, las 10 categorías y el Administrador inicial. Se corre con `php artisan db:seed`. |
| **Factory** | Receta para crear datos falsos en las pruebas (`database/factories/`), por ejemplo una empresa o un diagnóstico publicado. |
| **Modelo** | Clase de PHP que representa una tabla (`app/Models/`): `Empresa` para `empresas`, `Medicion` para `mediciones`. |
| **Rol** | Tipo de cuenta: Administrador, Empresa o Colaborador (y los que se creen en A5.1). Cada cuenta tiene uno solo y define qué menú ve (RN-027). Se maneja con spatie/laravel-permission. |
| **Permiso** | Acción concreta que un rol puede hacer, como `diagnosticos.ver` o `usuarios.gestionar`. Son 15 (A5.2). El servidor los revisa en cada ruta; ocultar un botón no basta. |
| **Middleware** | Filtro que corre antes del controlador: revisa la sesión, el rol o el permiso (`role:Empresa`, `permission:diagnosticos.editar`) o el límite de intentos. |
| **Ruta** | Dirección de la aplicación (`/diagnosticos`) unida al controlador que la atiende. Están en `routes/web.php`; la lista por módulo está en `15_BACKEND.md`. |
| **Controlador** | Clase de PHP (`app/Http/Controllers/`) que recibe la petición de una ruta, valida, guarda y responde con una pantalla o una redirección. |
| **Componente** | Pieza de pantalla de Vue reutilizable (`.vue`), como `Boton.vue` o `Modal.vue`. Los base están en `resources/js/components/base/`; una página es un componente completo en `resources/js/pages/`. |
| **Soft delete** | Borrado "suave": la fila no se borra, se marca con `deleted_at` y deja de verse. Se usa al eliminar cuentas para poder recuperarlas en 90 días (DEC-017). |
| **Cola** | Lista de trabajos que se hacen en segundo plano (correos, análisis de la IA) para no hacer esperar a la pantalla (RNF-004). |
