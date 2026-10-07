# Flujos del sistema

**Estado:** EN CURSO (T-042). El flujo de la medición (sección 1) es el **diseño** que se implementa en la semana 5; los flujos de acceso (sección 3) ya están construidos.

Los diagramas están en Mermaid; GitHub los dibuja solos. Los nombres de clases y trabajos (`AnalizarCategoria`, `PublicarResultado`…) son una **propuesta** para el backend.

## 1. De responder a ver el resultado

Es el flujo principal del sistema: la empresa responde, envía, la IA analiza cada categoría en segundo plano y el resultado se publica sin revisión (RN-023).

```mermaid
sequenceDiagram
    autonumber
    actor E as Empresa (E3–E5)
    participant V as Vue + Inertia
    participant L as Laravel
    participant BD as PostgreSQL
    participant C as Cola (jobs)
    participant IA as OpenAI
    participant M as Correo

    Note over E,BD: Responder (E3) · RNF-003
    loop Cada pregunta
        E->>V: Elige o escribe la respuesta
        V->>L: Guarda la respuesta
        L->>BD: respuestas (pregunta_ref, opción o texto)
        L-->>V: Guardada
    end

    Note over E,C: Revisar y enviar (E4) · RN-017
    E->>V: "Sí, enviar diagnóstico"
    V->>L: Enviar medición
    L->>BD: medición → Enviada · respuestas bloqueadas
    L->>L: Calcula opción única y múltiple (RN-018)
    L->>C: Encola AnalizarCategoria × N categorías
    L-->>V: Pantalla de espera (E5)

    Note over C,IA: Análisis en segundo plano · RN-021, RN-022
    par Una por categoría
        C->>L: AnalizarCategoria(categoría)
        L->>BD: Lee respuestas y prompts (general, sector, empresa)
        L->>L: Arma el prompt por capas (HU-081)
        L->>IA: Prompt + formato JSON fijo
        alt Respuesta válida
            IA-->>L: Observación por pregunta, puntaje de abiertas, observación y recomendación
            L->>BD: analisis_categoria → terminado
        else Falla o formato inválido
            IA-->>L: Error
            L->>C: Reintenta solo esa categoría (hasta 3 veces)
            opt 3 fallos
                L->>BD: analisis_categoria → fallido
                L->>M: Aviso al Administrador
            end
        end
    end

    Note over L,M: Publicar (HU-084) · RN-023, RN-024
    C->>L: PublicarResultado (cuando no queda ninguna pendiente)
    L->>L: Promedio por categoría, total ponderado, nivel y variación (RN-018 a RN-020)
    L->>BD: resultados (inmutable) · medición → Terminada
    L->>L: Genera el PDF de 3 páginas (Chromium)
    L->>BD: Guarda la ruta del PDF
    L->>M: "Tu resultado está listo"
    V->>L: E5 consulta el avance
    L-->>V: Terminada
    V-->>E: Resultado (E6)
```

Puntos clave:

- **Nada bloquea la pantalla (RNF-004).** El envío solo cambia el estado y encola trabajos; la empresa puede salir de E5.
- **Cada categoría es independiente.** Si una falla, las demás conservan su análisis (HU-069 CA-004).
- **El PDF se genera una sola vez** con lo guardado, sin volver a llamar a la IA (RN-024).
- **[FUNCIONALIDAD POR DEFINIR]** Qué ve la empresa si una categoría queda fallida tras 3 intentos. El wireframe A4 dice: "la categoría queda 'pendiente de análisis' y el resultado se recalcula al reintentar". Falta decidir si el resultado se publica sin esa categoría o espera a que el Administrador la reintente.

## 2. Estados de la medición

```mermaid
stateDiagram-v2
    [*] --> NoIniciada: El Administrador asigna (A3.1b)
    NoIniciada --> EnCurso: La empresa responde la primera pregunta
    EnCurso --> Enviada: "Enviar diagnóstico" (E4)
    Enviada --> Terminada: Resultado publicado (HU-084)
    NoIniciada --> Vencida: Pasa la fecha límite (tarea diaria, HU-070)
    EnCurso --> Vencida: Pasa la fecha límite
    NoIniciada --> Cancelada: El Administrador la cancela o la reemplaza (A3.1c)
    EnCurso --> Cancelada: Se reemplaza por otra
    Vencida --> [*]: La empresa pide otra (HU-082) y se asigna una nueva
    Terminada --> [*]
    Cancelada --> [*]
```

- Solo hay una medición pendiente por empresa (RN-014). Asignar otra reemplaza la pendiente.
- "Cancelada" viene de los wireframes A3.1c; está en la revisión del MER (`12_BASE_DE_DATOS.md`).

## 3. Acceso (construido)

### Iniciar sesión (L1)

```mermaid
sequenceDiagram
    actor U as Persona
    participant L as Laravel (Fortify)
    participant BD as PostgreSQL

    U->>L: Correo y contraseña
    L->>L: ¿Más de 5 intentos en 1 minuto?
    alt Bloqueado
        L-->>U: Modal "Demasiados intentos" con la cuenta regresiva
    else
        L->>BD: Busca la cuenta (correo en minúsculas)
        alt No existe o contraseña errónea
            L-->>U: "El correo o la contraseña no son correctos." (RN-005)
        else Contraseña correcta, pero cuenta o empresa desactivada
            L-->>U: Modal "Su cuenta ha sido desactivada…" (RN-004, RN-025)
        else Todo bien
            L->>BD: ultimo_acceso_en = ahora
            L-->>U: Inicio de su cuenta
        end
    end
```

### Registrar la empresa (L2)

```mermaid
sequenceDiagram
    actor U as Empresa nueva
    participant L as Laravel
    participant BD as PostgreSQL

    U->>L: Datos de la empresa y del usuario, contraseña, términos
    L->>L: Valida: sector activo (RN-003), correo único (RN-002), contraseña fuerte (RN-001), términos aceptados
    alt Hay errores
        L-->>U: Errores en español, bajo cada campo
    else
        L->>BD: Transacción: empresa + usuario + rol Empresa + terminos_aceptados_en
        L-->>U: Sesión iniciada · entra a su inicio
    end
```

### Recuperar la contraseña (L3 y L4)

```mermaid
sequenceDiagram
    actor U as Persona
    participant L as Laravel
    participant M as Correo

    U->>L: Correo (L3)
    opt El correo está registrado
        L->>M: Enlace para crear una contraseña nueva
    end
    L-->>U: Siempre: "Si el correo está registrado, te enviamos un enlace…" (RN-005)
    U->>L: Abre el enlace y escribe la contraseña nueva (L4)
    L->>L: Enlace válido (no vence por tiempo, RN-006) y contraseña fuerte (RN-001)
    L-->>U: Vuelve a L1 con "Tu contraseña se actualizó"
```

## 4. Tareas programadas (semana 5)

| Tarea | Cuándo | Qué hace |
|---|---|---|
| Marcar vencidas (HU-070) | Cada día | Pasa a "Vencida" las mediciones sin enviar cuya fecha límite ya pasó |
| Resumen semanal | Cada semana | Envía al Administrador las mediciones pendientes, si lo activó en Mi perfil (A6) |

**[INFORMACIÓN PENDIENTE]** La hora de la tarea diaria y el día del resumen semanal.
