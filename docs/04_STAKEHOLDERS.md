# Stakeholders

**Estado:** REQUIERE VALIDACIÓN (T-060). Falta confirmar los nombres y los canales de contacto de NuevasTIC.

Quién tiene interés en el sistema, qué espera de él y cómo se le informa.

## Mapa

| Stakeholder | Tipo | Qué espera | Influencia | Interés |
|---|---|---|---|---|
| **NuevasTIC** (cliente) | Patrocinador | Un sistema que mida el marketing digital de sus empresas con un criterio común, entregado a tiempo y sin costos de licencias | Alta: define el alcance, las preguntas de cada sector y aprueba la entrega | Alto |
| **Administrador** (equipo de NuevasTIC) | Usuario | Configurar sectores, categorías, diagnósticos e IA sin tocar código; asignar mediciones y ver el avance de todas las empresas | Alta: sus pantallas son la mitad del sistema | Alto |
| **Empresa** (cuenta principal) | Usuario | Un diagnóstico claro, que se pueda pausar; un resultado que entienda y recomendaciones concretas; ver su evolución | Media: su experiencia define si el diagnóstico se completa | Alto |
| **Colaborador** de la empresa | Usuario | Ayudar a responder con su propia cuenta y ver los resultados | Baja | Medio |
| **Cristian Andrés Penagos Simanca** | Equipo (frontend y documentación) | Pantallas fieles al wireframe, contratos de datos claros con el backend | Alta en el diseño de la interfaz | Alto |
| **Luis Carlos Sánchez Muñoz** | Equipo (backend) | Reglas de negocio bien definidas, modelo de datos aprobado, pruebas | Alta en las reglas y los datos | Alto |
| **Evaluadores de la entrega** | Revisores | Probar el sistema (empezando por el acceso), revisar la base de datos y la documentación | Alta: aprueban o no la entrega | Alto |
| **OpenAI** | Proveedor externo | — (servicio de IA que se paga por uso) | Media: si falla, el análisis se reintenta (RN-021) | — |
| **Servidor de correo** | Proveedor externo | — (avisos de medición, recuperación de contraseña, resultado listo) | Media | — |

**[INFORMACIÓN PENDIENTE]**:

- Quién es la persona responsable del proyecto en NuevasTIC.
- Quiénes son los evaluadores de la entrega y qué criterios usan.

## Qué espera cada uno, en detalle

### NuevasTIC

- Entrega el **6 de noviembre de 2026** en un repositorio de GitHub, sin publicarlo en un servidor (`03_ALCANCE.md`).
- Las tecnologías exigidas: Laravel, Vue.js, Tailwind CSS y PostgreSQL.
- Costo de licencias cero. El único costo es la API de OpenAI, por uso (propuesta técnica, sección 5).
- **Aporta:**
  - las preguntas, opciones y puntajes de cada sector (T-072);
  - la clave de OpenAI (**[INFORMACIÓN PENDIENTE]**, `03_ALCANCE.md`);
  - las respuestas a las preguntas abiertas de `05_REQUISITOS_FUNCIONALES.md`, sección 14.

### Administrador

- Configurar sin código: diagnósticos, importancia, prompts de la IA, roles.
- Saber en un vistazo qué mediciones están pendientes, en curso o vencidas (A1).
- Comprobar lo que ve cada rol con "Ver como" (RN-026).

### Empresa y colaborador

- Un registro corto (L2) y una cuenta lista al instante.
- Responder a su ritmo, con las respuestas guardadas solas (RNF-003).
- Un resultado con puntaje, nivel, observaciones y recomendaciones, y un PDF.
- Que sus datos solo los vea su empresa y NuevasTIC (RN-007).

### Evaluadores

- Que el acceso sea seguro: contraseña fuerte, cuentas desactivadas, avisos que no revelan correos, límite de intentos (`17_SEGURIDAD.md`).
- Poder revisar la base de datos: MER, diccionario de datos y una copia (`12_BASE_DE_DATOS.md`).
- Pruebas automáticas que se puedan correr en vivo (`php artisan test`).

## Comunicación

| Con quién | Canal | Qué se comparte | Frecuencia |
|---|---|---|---|
| Entre Cristian y Luis | Trello, pull requests de GitHub | Tareas, revisiones de código, contratos de datos (`14_FRONTEND.md`, `15_BACKEND.md`) | Diaria |
| NuevasTIC | Reuniones (T-008) y correo | Avance, preguntas abiertas, validaciones | **[INFORMACIÓN PENDIENTE]** |
| Evaluadores | Presentación y repositorio | Demostración, documentación, pruebas | En las entregas |
