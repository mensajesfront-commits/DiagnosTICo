# Roadmap

**Estado:** REQUIERE VALIDACIÓN (T-157). Lo que viene después de la entrega del 6 de noviembre de 2026. Las prioridades las propone el equipo y NuevasTIC debe confirmarlas.

## 1. Dónde queda el proyecto en la entrega

- **Historias:** 79 de 84 hechas (`05_REQUISITOS_FUNCIONALES.md`). Las 5 que faltan (HU-071 a HU-075, bot de WhatsApp) están aplazadas.
- **Flujo completo:** registrar la empresa → asignar la medición → responder → análisis de la IA por categoría → resultado, historial y PDF.
- **Configuración:** el Administrador maneja sectores, categorías, diagnósticos con versiones, la IA por capas, las cuentas, los roles y "Ver como".
- **Entrega:** se entrega el repositorio, sin despliegue (DEC-006).

## 2. Antes de usarlo con empresas reales

| Prioridad | Qué | Por qué |
|---|---|---|
| Alta | Cargar los diagnósticos reales de cada sector | Hoy hay datos de ejemplo. El contenido lo entrega NuevasTIC (T-072). |
| Alta | Revisar con NuevasTIC las instrucciones de la IA y las frases de los niveles | Las escribió el equipo (`07_REGLAS_DE_NEGOCIO.md`). |
| Alta | Decidir RN-028 frente a los métodos de análisis (DEC-019) | **[INCONSISTENCIA DETECTADA]** RN-028 excluye FODA y hoy la IA puede usar DOFA. |
| Alta | Definir el servidor, el dominio, el correo SMTP y quién paga OpenAI | Sin esto no se puede poner en línea (`20_DESPLIEGUE.md`). |
| Media | Correo de soporte real (`SOPORTE_EMAIL`) | Lo ve la empresa con una medición vencida. |
| Media | Límite diario de "Probar con un ejemplo" (PA-003) | Controla el gasto en OpenAI. |

## 3. Mejoras propuestas

| Prioridad | Qué | Notas |
|---|---|---|
| Media | Pruebas de navegador automáticas (Playwright) | Hoy los flujos completos se prueban a mano (`19_PRUEBAS.md`). |
| Media | Monitoreo de la cola y alertas por tareas fallidas | `21_MANTENIMIENTO.md`. |
| Media | Verificación del correo al cambiarlo en Mi perfil | Está quitada (DEC-012). |
| Media | Historial de solicitudes de nueva medición | Hoy solo se ve la abierta (HU-083). |
| Baja | Comparar dos mediciones lado a lado | Hoy se ve la variación total y por categoría. |
| Baja | Conservar las estadísticas sin nombre al borrar para siempre una empresa | `30_RIESGOS.md`. |
| Baja | Avisar cuando dos personas de la misma empresa responden a la vez | `08_CASOS_DE_USO.md`. |

## 4. Funcionalidades aplazadas

| Qué | Estado | Qué falta para retomarlo |
|---|---|---|
| Bot de WhatsApp (EP-013, HU-071 a HU-075) | Aplazado por decisión de Cristian | El texto del saludo (PA-001), cómo se configuran las preguntas (PA-002), un número de Meta y un servidor con HTTPS para el webhook (RN-029). |
| Rol Consultor | Existe inactivo (PA-006) | Definir qué empresas ve cada consultor. Necesita una tabla `consultor_empresa` (`12_BASE_DE_DATOS.md`). |
| Rol Usuario | Sin historias | Definir para qué sirve. |
| Análisis FODA y comparación con promedios | Fuera del alcance (RN-028) | Lo decide NuevasTIC. |

## 5. Deuda técnica conocida

- **"Ver como":** si la sesión vence durante la vista, el registro queda sin hora de fin.
- **Pruebas de pantallas:** las pantallas no tienen pruebas de componente; se revisan contra el wireframe.
- **Cronograma:** todavía nombra FODA, 3 etapas de IA y un PDF de 4 páginas (`01_ESTADO_ACTUAL.md`).
