# Roadmap

**Estado:** COMPLETO (T-157), aprobado por NuevasTIC el 5 de noviembre de 2026. Lo que viene después de la entrega del 6 de noviembre de 2026.

## 1. Dónde queda el proyecto en la entrega

- **Historias:** 79 de 84 hechas en este repositorio (`05_REQUISITOS_FUNCIONALES.md`). Las 5 del bot de WhatsApp (HU-071 a HU-075) las terminó otra persona del equipo, fuera de este repositorio.
- **Flujo completo:** registrar la empresa → asignar la medición → responder → análisis de la IA por categoría → resultado, historial y PDF.
- **Configuración:** el Administrador maneja sectores, categorías, diagnósticos con versiones, la IA por capas, las cuentas, los roles y "Ver como".
- **Entrega:** se entrega el repositorio y **el sistema se va a desplegar** (DEC-006 cambió el 4 de noviembre; pasos en `20_DESPLIEGUE.md`).

## 2. Antes de usarlo con empresas reales

| Prioridad | Qué | Por qué |
|---|---|---|
| Alta | Cargar los diagnósticos reales de cada sector | NuevasTIC los genera con IA y los carga a mano en la aplicación cuando se lance (T-072). Confirmado el 4 de noviembre. |
| Alta | Revisar con NuevasTIC las instrucciones de la IA | Las escribió el equipo. Las frases de los niveles ya están aprobadas. |
| Alta | Definir el servidor, el dominio y el correo SMTP | Sin esto no se puede poner en línea (`20_DESPLIEGUE.md`). Las llaves reales de Turnstile ya existen (4 de noviembre). OpenAI lo paga NuevasTIC, con gpt-4.1-mini. |

## 3. Mejoras propuestas (después de desplegar)

| Prioridad | Qué | Notas |
|---|---|---|
| Media | Pruebas de navegador automáticas (Playwright) | Hoy los flujos completos se prueban a mano (`19_PRUEBAS.md`). |
| Media | Monitoreo de la cola y alertas por tareas fallidas | `21_MANTENIMIENTO.md`. |
| Media | Verificación del correo al cambiarlo en Mi perfil | Está quitada (DEC-012). |
| Media | Historial de solicitudes de nueva medición | Hoy solo se ve la abierta (HU-083). |
| Baja | Comparar dos mediciones lado a lado | Hoy se ve la variación total y por categoría. |
| Baja | Pantalla de estadísticas con las mediciones sin nombre | Ya se conservan al borrar una empresa; falta mostrarlas. |
| Baja | Avisar cuando dos personas de la misma empresa responden a la vez | `08_CASOS_DE_USO.md`. |

## 4. Funcionalidades aplazadas

El bot de WhatsApp (EP-013, HU-071 a HU-075) ya no está aplazado: lo terminó otra persona del equipo, fuera de este repositorio (4 de noviembre de 2026).

| Qué | Estado | Qué falta para retomarlo |
|---|---|---|
| Rol Usuario | Sin historias | Definir para qué sirve. |
| Comparación con promedios | Fuera del alcance (RN-028) | Lo decide NuevasTIC. |

## 5. Deuda técnica conocida

- **"Ver como":** si la sesión vence durante la vista, el registro queda sin hora de fin.
- **Pruebas de pantallas:** las pantallas no tienen pruebas de componente; se revisan contra el wireframe.
- **Cronograma:** todavía nombra FODA, 3 etapas de IA y un PDF de 4 páginas (`01_ESTADO_ACTUAL.md`).
