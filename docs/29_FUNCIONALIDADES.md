# Funcionalidades

**Estado:** COMPLETO (T-087). Lo que hace el sistema en la entrega `v1.0.0` (5 de noviembre de 2026), por rol. Cada función lleva el código de su pantalla y su historia. El detalle técnico está en `11_DISENO_TECNICO.md` y los componentes en `18_UI_UX.md`.

## Roles

| Rol | Quién es | Entra a |
|---|---|---|
| Administrador | Equipo de NuevasTIC | Todo el panel: Inicio, Diagnósticos, Empresas, Configuración IA y Usuarios y roles |
| Empresa | Cuenta principal de cada empresa | Su inicio, su diagnóstico, sus resultados, su historial, su perfil y sus colaboradores |
| Colaborador | Persona que la empresa agrega | Las páginas que la cuenta principal elige para cada uno (responder, resultados, PDF); no gestiona colaboradores ni cambia los datos de la empresa ni su propio perfil (RN-025) |
| Consultor | Persona externa que acompaña empresas (invitada) | Su inicio (C1) y Mis empresas (C2) con sus empresas vinculadas; su ficha, historial, resultados, respuestas y los dos PDF. Solo mira (RN-030) |

## Acceso (todos)

| Función | Pantalla | Historia |
|---|---|---|
| Iniciar sesión, con aviso de cuenta desactivada, de rol inactivo y límite de intentos | L1 | HU-001 |
| Registrar la empresa en dos pasos (cuenta y empresa, con CIIU y ubicación), con captcha, los Términos y Condiciones y la Política de Privacidad oficiales | L2 | HU-002 |
| Verificar el correo con un código antes de entrar | L2b | DEC-020 |
| Recuperar la contraseña (el enlace vence a los 10 minutos) y crear una nueva; crear la primera desde una invitación | L3, L4 | HU-003, HU-004 |
| Cerrar sesión | Menú | HU-005 |
| Editar el perfil, la foto y la contraseña | A6, E11 | HU-055, HU-056 |

## Administrador

### Inicio (A1)

- Cuatro indicadores:
  - empresas registradas;
  - mediciones pendientes;
  - diagnósticos completados este mes;
  - puntaje promedio.
- Tabla de mediciones con las pestañas Todas, En curso, No iniciadas, Vencidas y Terminadas (HU-006, HU-007).
- Acciones según el estado de la medición:
  - "Recordatorio" (en curso) y "Reenviar aviso" (no iniciada), en HU-008;
  - "Nueva fecha" y "Cancelar" (vencida), en HU-009;
  - "Ver resultado" y "Asignar nueva" (terminada).
- Aviso de las empresas que pidieron una medición nueva; esas filas van primero en Vencidas (HU-083).
- Paneles "Empresas por nivel" y "Diagnósticos por sector".

### Diagnósticos (A2)

- **Sectores:**
  - ver los diagnósticos por sector o todos juntos (HU-010, HU-011);
  - crear, editar, reasignar empresas, desactivar y eliminar (HU-012 a HU-016, RN-008).
- **Catálogo de categorías:** crear, editar, archivar, restaurar y eliminar (HU-017 a HU-019, RN-009).
- **Crear y mantener diagnósticos:**
  - crear en blanco o copiando otro (HU-020);
  - duplicar a otro sector (HU-021);
  - archivar y eliminar, también varios a la vez (HU-022).
- **Editor (A2.1):**
  - categorías y preguntas abiertas, de opción única o de selección múltiple;
  - puntaje por opción e indicación para la IA ("Ten en cuenta");
  - opción "Ninguna" que excluye a las demás y opción "Otro" que pide escribir cuál;
  - ordenar las preguntas (HU-023 a HU-026).
- **Importancia** de cada categoría, que suma 100 % (A2.1c, HU-027).
- **Vista previa** con lo que falta para publicar (A2.4, HU-028).
- **Publicar** una versión que ya no cambia (A2.1d, HU-029).
- **Resultado de ejemplo** en el mejor, el medio y el peor caso, con sus dos PDF: el completo y el resumen de la empresa (A2.6, HU-030).

### Empresas (A3)

- Lista con búsqueda y filtros de sector, nivel, cuenta y estado de la medición pendiente; muestra el puntaje, la variación y la medición pendiente (HU-031).
- Registrar una empresa e invitar a su cuenta principal (A3.2, HU-032).
- **Ficha (A3.1):**
  - último puntaje, variación y medición pendiente;
  - gráfica de evolución del total o de una categoría;
  - tabla de mediciones (HU-033).
- Editar los datos, y desactivar o reactivar la cuenta (A3.1d, A3.1f, HU-034, HU-035).
- **Asignar medición (A3.1b, HU-036):**
  - con fecha límite, mensaje y correo de aviso editable con variables (A3.1e, HU-037);
  - reemplazar, cambiar de fecha o cancelar la pendiente (A3.1c).
- Atender las solicitudes de nueva medición (HU-083).
- Ver el resultado, el historial y las respuestas de cada empresa (A3.3, A3.4, A3.5, HU-038 a HU-040).
- Descargar el informe PDF completo o el resumen que recibe la empresa (A3.3, RN-024).
- Vincular y quitar consultores en la pestaña Consultores (A3.1, HU-085).

### Configuración de la IA (A4)

- Instrucciones generales por elemento: contexto, tarea, detalles y ejemplos (HU-041).
- Ajuste por sector y por empresa: usar, agregar o reemplazar cada elemento (HU-042, HU-043).
- Ver el prompt completo que recibe la IA (HU-044).
- Probar con un ejemplo real antes de guardar (HU-045).
- "Cómo se arma el prompt final" (A4.9, HU-080).

### Usuarios y roles (A5)

- Lista de cuentas con búsqueda y filtros (HU-046).
- Invitar cuentas internas y consultores (con sus empresas); desactivar, reactivar, eliminar y recuperar cuentas (HU-047, HU-085).
- Roles del sistema y roles creados: crear, editar permisos y eliminar (HU-048 a HU-051).
- Cambiar o asignar el rol de una cuenta (HU-052).
- "Ver como" una empresa, un colaborador u otra cuenta que no sea Administrador, solo de lectura y con registro (A5.4, HU-053, HU-054).

## Consultor

| Función | Pantalla | Historia |
|---|---|---|
| Inicio con sus empresas, cifras y «Qué atender» | C1 | HU-086 |
| Mis empresas: una tarjeta por empresa, con filtros y buscador | C2 | HU-086 |
| Ver la ficha, el historial, los resultados y las respuestas de sus empresas, solo de lectura | A3.1, A3.3–A3.5 | HU-086 |
| Descargar el informe completo o el resumen de la empresa | A3.3 | HU-086, RN-024 |

## Empresa y colaborador

| Función | Pantalla | Historia |
|---|---|---|
| Inicio: primera vez, medición pendiente, en curso, enviada, vencida o sin pendiente, con el último resultado y las 3 áreas más débiles | E1 | HU-057 a HU-059 |
| Pedir una medición nueva cuando la suya venció | E1 | HU-082 |
| Responder por categorías, con guardado automático y avance | E2 | HU-060 |
| Opción "Otro": escribir cuál | E2 | — |
| Revisar antes de enviar y enviar | E3 | HU-061, HU-062 |
| Ver el avance del análisis y reintentar una categoría fallida | E4 | HU-063 |
| Resultado: puntaje, nivel, variación, radar, por categoría con el método de análisis, observaciones y recomendaciones | E6 | HU-064 |
| Ver sus respuestas con la observación de la IA | E9 | HU-065 |
| Descargar el informe PDF: el resumen con lo que dijo la IA, en orden | E10 | HU-066 |
| Historial con la evolución total o por categoría | E8 | HU-067 |
| Colaboradores: agregar, editar, desactivar, reactivar y eliminar (solo la empresa) | E12 | HU-076 a HU-079 |

## Sistema (automático)

| Función | Cuándo | Historia |
|---|---|---|
| Calcular los puntajes de las preguntas cerradas | Al enviar | HU-068 |
| Analizar cada categoría con la IA, con 3 intentos | En la cola, después de enviar | HU-069, HU-081 |
| Publicar el resultado, avisar a la empresa y generar los dos PDF | Cuando terminan todas las categorías | HU-084 |
| Marcar las mediciones vencidas | Cada hora, a la medianoche de cada empresa | HU-070 |
| Borrar para siempre las cuentas eliminadas hace más de 90 días y los registros sin verificar a los 7 días | Cada día a las 03:10 | DEC-017, DEC-020 |
| Resumen semanal de mediciones pendientes a los administradores que lo encendieron | Cada lunes a las 07:50 | A6 |
| Avisos por correo según lo que cada cuenta eligió en Mi perfil | Al enviar, al asignar, al publicar y si falla la IA | A6, E11 |

## Lo que no hace

- **Bot de WhatsApp (HU-071 a HU-075):** terminado por otra persona del equipo, fuera de este repositorio (4 de noviembre de 2026).
- **Comparación con promedios:** fuera del alcance (RN-028). La IA sí analiza con DOFA y otros métodos, y el informe completo agrupa su DOFA (DEC-019).
- **Despliegue:** se va a desplegar (DEC-006 cambió el 4 de noviembre; `20_DESPLIEGUE.md`).
