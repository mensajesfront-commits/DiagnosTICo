# Requisitos funcionales

**Estado:** REQUIERE VALIDACIÓN

Requisitos funcionales (RF), historias de usuario, criterios de aceptación y backlog del Sistema de Diagnóstico de Marketing Digital (NuevasTIC). Se construyeron a partir de las 79 vistas del prototipo y de lo confirmado por Cristian, siguiendo las normas de `Normas.txt`. Nada se inventó: lo que falta se marca **[INFORMACIÓN PENDIENTE]**, **[FUNCIONALIDAD POR DEFINIR]** o **[INCONSISTENCIA DETECTADA]**.

## Convención

- Requisitos: `RF-XXX`. Historias: `HU-XXX`. Épicas: `EP-XXX`. Reglas de negocio: `RN-XXX`. Requisitos no funcionales: `RNF-XXX`. Criterios: `CA-XXX` (numerados dentro de cada historia). Preguntas: `PA-XXX`. Supuestos: `SUP-XXX`.
- Las vistas del prototipo se citan con sus códigos (L1, A2.1, E6…).
- Prioridad: Alta / Media / Baja. Estimación en Story Points (1, 2, 3, 5, 8, 13). MoSCoW solo como apoyo.

---

# 1. Resumen del proyecto

Sistema web para que NuevasTIC mida el nivel de marketing digital de empresas. Las empresas responden un diagnóstico propio de su sector, la IA analiza cada categoría y el sistema entrega un resultado con puntaje, nivel, observaciones, recomendaciones y un PDF. El Administrador de NuevasTIC configura sectores, categorías, diagnósticos, empresas, mediciones, la IA y las cuentas.

Decisiones confirmadas que rigen estas historias: roles Administrador, Empresa y Colaborador (Consultor y Usuario quedan para un posible futuro); sin análisis FODA ni comparación con promedios; contraseña fuerte (8 caracteres, mayúscula, número y carácter especial); análisis de IA en una sola etapa por categoría; estados de medición Enviada y Terminada; medición vencida → la empresa pide otra por correo; el bot de WhatsApp es totalmente separado y queda **aplazado**; el simulador de inversión no existe.

# 2. Actores identificados

| Actor | Descripción | Necesidades principales |
|---|---|---|
| Administrador | Persona de NuevasTIC que configura y supervisa el sistema. | Administrar sectores, categorías, diagnósticos, empresas, mediciones, IA y cuentas; seguir los resultados. |
| Empresa (usuario principal) | Cuenta de la empresa cliente; se registra sola o la crea el Administrador. | Responder el diagnóstico, ver resultados, historial y PDF; gestionar sus colaboradores y sus datos. |
| Colaborador | Cuenta creada por la empresa, con casi los mismos permisos. | Responder y enviar el diagnóstico y ver resultados, historial y PDF; no crea colaboradores ni cambia datos de la empresa. |
| Sistema | Procesos automáticos (cálculo, análisis de IA, publicación, vencimientos). | Producir resultados justos y consistentes sin intervención. |
| Empresa por WhatsApp | Quien conversa con el bot (aplazado). | Hacer un diagnóstico rápido sin cuenta. |
| Consultor, Usuario | Roles posibles a futuro. **[FUNCIONALIDAD POR DEFINIR]** No tienen historias. | — |

# 3. Módulos identificados

```text
Sistema de Diagnóstico de Marketing Digital
│
├── EP-001 Acceso
├── EP-002 Inicio del Administrador
├── EP-003 Sectores
├── EP-004 Catálogo de categorías
├── EP-005 Diagnósticos
├── EP-006 Empresas y mediciones
├── EP-007 Configuración de la IA
├── EP-008 Usuarios y roles
├── EP-009 Mi perfil
├── EP-010 Experiencia de la empresa
├── EP-011 Colaboradores
├── EP-012 Cálculo, IA y tareas automáticas
└── EP-013 Diagnóstico por WhatsApp (aplazado)
```

# 4. Épicas

### EP-001 — Acceso

| Campo | Información |
|---|---|
| ID | EP-001 |
| Nombre | Acceso |
| Descripción | Entrar al sistema, registrarse y recuperar la contraseña. |
| Actor principal | Administrador, Empresa, Colaborador |
| Objetivo | Que cada persona acceda con seguridad a lo que le corresponde. |
| Valor de negocio | Sin acceso seguro no se puede usar ninguna otra función. |
| Historias | 5 |

### EP-002 — Inicio del Administrador

| Campo | Información |
|---|---|
| ID | EP-002 |
| Nombre | Inicio del Administrador |
| Descripción | Ver de un vistazo cómo van las mediciones de todas las empresas. |
| Actor principal | Administrador |
| Objetivo | Saber qué empresas necesitan atención. |
| Valor de negocio | Permite dar seguimiento y recordar a las empresas a tiempo. |
| Historias | 4 |

### EP-003 — Sectores

| Campo | Información |
|---|---|
| ID | EP-003 |
| Nombre | Sectores |
| Descripción | Organizar las empresas y sus diagnósticos por sector. |
| Actor principal | Administrador |
| Objetivo | Mantener los sectores que se ofrecen a las empresas. |
| Valor de negocio | Cada empresa responde un diagnóstico propio de su sector. |
| Historias | 7 |

### EP-004 — Catálogo de categorías

| Campo | Información |
|---|---|
| ID | EP-004 |
| Nombre | Catálogo de categorías |
| Descripción | Mantener la lista común de categorías de los diagnósticos. |
| Actor principal | Administrador |
| Objetivo | Que todos los diagnósticos usen las mismas categorías. |
| Valor de negocio | Resultados comparables y nombres consistentes. |
| Historias | 3 |

### EP-005 — Diagnósticos

| Campo | Información |
|---|---|
| ID | EP-005 |
| Nombre | Diagnósticos |
| Descripción | Armar, revisar y publicar los diagnósticos de cada sector. |
| Actor principal | Administrador |
| Objetivo | Tener cuestionarios correctos y versionados. |
| Valor de negocio | Es el producto central que responden las empresas. |
| Historias | 11 |

### EP-006 — Empresas y mediciones

| Campo | Información |
|---|---|
| ID | EP-006 |
| Nombre | Empresas y mediciones |
| Descripción | Registrar empresas, asignarles mediciones y revisar sus resultados. |
| Actor principal | Administrador |
| Objetivo | Acompañar a cada empresa en su evolución. |
| Valor de negocio | Conecta a las empresas con los diagnósticos y sus resultados. |
| Historias | 11 |

### EP-007 — Configuración de la IA

| Campo | Información |
|---|---|
| ID | EP-007 |
| Nombre | Configuración de la IA |
| Descripción | Definir qué le pide el sistema a la IA al analizar cada categoría. |
| Actor principal | Administrador |
| Objetivo | Que el análisis refleje el criterio de NuevasTIC. |
| Valor de negocio | La calidad de observaciones y recomendaciones depende del prompt. |
| Historias | 6 |

### EP-008 — Usuarios y roles

| Campo | Información |
|---|---|
| ID | EP-008 |
| Nombre | Usuarios y roles |
| Descripción | Administrar quién entra al sistema y qué puede hacer. |
| Actor principal | Administrador |
| Objetivo | Controlar el acceso y los permisos. |
| Valor de negocio | Seguridad y orden en las cuentas. |
| Historias | 9 |

### EP-009 — Mi perfil

| Campo | Información |
|---|---|
| ID | EP-009 |
| Nombre | Mi perfil |
| Descripción | Mantener los datos propios y la contraseña. |
| Actor principal | Administrador, Empresa, Colaborador |
| Objetivo | Que cada persona mantenga sus datos al día. |
| Valor de negocio | Informes correctos y cuentas seguras. |
| Historias | 2 |

### EP-010 — Experiencia de la empresa

| Campo | Información |
|---|---|
| ID | EP-010 |
| Nombre | Experiencia de la empresa |
| Descripción | Responder el diagnóstico y ver los resultados. |
| Actor principal | Empresa (usuario principal o colaborador) |
| Objetivo | Obtener un resultado claro y recomendaciones. |
| Valor de negocio | Es el valor que recibe el cliente final. |
| Historias | 12 |

### EP-011 — Colaboradores

| Campo | Información |
|---|---|
| ID | EP-011 |
| Nombre | Colaboradores |
| Descripción | Crear y administrar cuentas de colaborador de una empresa. |
| Actor principal | Empresa (usuario principal) |
| Objetivo | Que varias personas trabajen el diagnóstico de la empresa. |
| Valor de negocio | Permite que la empresa delegue sin compartir su cuenta. |
| Historias | 4 |

### EP-012 — Cálculo, IA y tareas automáticas

| Campo | Información |
|---|---|
| ID | EP-012 |
| Nombre | Cálculo, IA y tareas automáticas |
| Descripción | Lo que hace el sistema por sí solo: puntajes, análisis, publicación y vencimientos. |
| Actor principal | Sistema |
| Objetivo | Resultados justos, consistentes y publicados a tiempo. |
| Valor de negocio | Sin esto no hay resultado. |
| Historias | 5 |

### EP-013 — Diagnóstico por WhatsApp

| Campo | Información |
|---|---|
| ID | EP-013 |
| Nombre | Diagnóstico por WhatsApp |
| Descripción | Diagnóstico rápido conversando con un bot, separado de la web (APLAZADO). |
| Actor principal | Empresa (por WhatsApp) |
| Objetivo | Hacer un diagnóstico rápido sin cuenta. |
| Valor de negocio | Canal de entrada ligero; aplazado por decisión de Cristian. |
| Historias | 5 |

# 5. Historias de usuario

## Definition of Ready (resumen)

Una historia está **LISTA** si tiene ID, épica, actor, necesidad, beneficio, criterios de aceptación, reglas de negocio, prioridad, dependencias, es pequeña y se puede probar. Si falta algo, queda **NO LISTA** y se explica qué falta.

## Definition of Done (adaptada al proyecto)

- Desarrollo completado e integrado.
- Validaciones implementadas.
- Pruebas realizadas y criterios de aceptación cumplidos.
- Errores corregidos y revisión realizada.
- Pantalla igual al prototipo (cuando existe la vista).
- Documentación actualizada cuando corresponda.

---

## EP-001 — Acceso

## HU-001 — Iniciar sesión

**Épica:** EP-001 — Acceso

**Actor:** Administrador, Empresa o Colaborador

**Prioridad:** Alta — Alta porque sin entrar al sistema no se puede usar ninguna otra función.

**Story Points:** 5 — Flujo con varios roles de destino, cuentas desactivadas y avisos sin revelar datos.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** L1

### Historia de Usuario

> Como administrador, empresa o colaborador, quiero entrar con mi correo y mi contraseña, para llegar a mi inicio según mi rol.

### Descripción

Pantalla de entrada al sistema. El rol y el estado de la cuenta se toman de Usuarios y roles (A5). El colaborador entra con el correo y la contraseña que le dio la empresa.

### Valor de negocio

Permite que cada persona llegue de forma segura a su inicio según su rol.

### Caso de éxito

1. Escribo mi correo y mi contraseña.
2. Pulso “Iniciar sesión”.
3. El sistema valida los datos y me lleva a mi inicio: el Administrador a A1; la empresa y el colaborador a su inicio según el estado (E1, E2, E7 o E7b).

### Criterios de aceptación

#### CA-001 — Ingreso correcto

**Dado** que tengo una cuenta activa y escribo mi correo y contraseña correctos,

**Cuando** pulso "Iniciar sesión",

**Entonces** entro a la pantalla de inicio que corresponde a mi rol.

#### CA-002 — Colaborador inicia sesión

**Dado** que la empresa creó mi cuenta de colaborador con mi correo y una contraseña,

**Cuando** escribo esos datos y pulso "Iniciar sesión",

**Entonces** entro al inicio de mi empresa con el menú de colaborador.

#### CA-003 — Datos incorrectos

**Dado** que el correo o la contraseña no coinciden,

**Cuando** pulso "Iniciar sesión",

**Entonces** veo un aviso rojo bajo el campo que no revela cuál de los dos falló.

#### CA-004 — Cuenta desactivada

**Dado** que mi cuenta está desactivada,

**Cuando** intento iniciar sesión,

**Entonces** no entro y veo un mensaje que me pide escribir al administrador (o a mi empresa, si soy colaborador).

#### CA-005 — Mantener sesión y mostrar contraseña

**Dado** que estoy en la pantalla de inicio de sesión,

**Cuando** marco "Mantener la sesión iniciada en este equipo" o pulso "Mostrar",

**Entonces** la sesión se mantiene en el equipo y la contraseña se muestra u oculta.

### Reglas de negocio

- RN-004 — Una cuenta desactivada no puede iniciar sesión y su información no se borra; se puede reactivar.
- RN-005 — Los avisos de inicio de sesión y recuperación no revelan si un correo está registrado.
- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.

### Validaciones

- Correo y contraseña obligatorios para iniciar sesión.

### Casos alternativos

- Ir a "¿Olvidaste tu contraseña?" desde la pantalla.
- Ir a "Registrar mi empresa" desde la pantalla.

### Casos de error

- Correo o contraseña incorrectos: aviso rojo bajo el campo sin decir cuál falló.
- Cuenta desactivada: no entra y se le pide escribir al administrador o a su empresa.

### Dependencias

- Depende de HU-077 — Crear un colaborador.
- Depende de HU-046 — Ver las cuentas del sistema.

### Requisitos relacionados

- RF-001

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)
- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-077 (creación de colaboradores) y HU-046 (cuentas).

---

## HU-002 — Registrar mi empresa

**Épica:** EP-001 — Acceso

**Actor:** Empresa

**Prioridad:** Alta — Alta porque es la puerta de entrada de las empresas al sistema.

**Story Points:** 5 — Formulario largo con varias reglas y guardado conjunto de empresa y usuario principal.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** L2

### Historia de Usuario

> Como empresa, quiero crear la cuenta de mi empresa yo mismo, para responder el diagnóstico de mi sector.

### Descripción

Formulario público de registro. Solo se ofrecen los sectores que ya existen y están activos.

### Valor de negocio

Permite a una empresa crear su cuenta por sí misma para responder el diagnóstico de su sector.

### Caso de éxito

1. Completo nombre de la empresa, sector, ciudad, país, nombre del usuario, cargo, correo, teléfono, contraseña y confirmación.
2. Acepto los términos de uso y el tratamiento de datos.
3. Pulso “Crear cuenta”.
4. Se guardan la empresa y su usuario principal en una sola operación y entro a E1.

### Criterios de aceptación

#### CA-001 — Registro exitoso

**Dado** que no tengo sesión iniciada y completo todos los campos con datos válidos y acepto los términos,

**Cuando** pulso "Crear cuenta",

**Entonces** se guardan la empresa y su usuario principal y entro al inicio de la empresa.

#### CA-002 — Solo sectores activos

**Dado** que abro el formulario de registro,

**Cuando** despliego la lista de sectores,

**Entonces** solo aparecen sectores activos y no existe la opción "Otro".

#### CA-003 — Correo ya registrado

**Dado** que el correo ya pertenece a una cuenta,

**Cuando** pulso "Crear cuenta",

**Entonces** veo un aviso bajo el campo y no se crea nada.

#### CA-004 — Contraseña inválida

**Dado** que la contraseña no cumple la regla o no coincide con la confirmación,

**Cuando** intento crear la cuenta,

**Entonces** veo un aviso bajo el campo y no se crea la cuenta.

#### CA-005 — Términos sin aceptar

**Dado** que no acepté los términos de uso y el tratamiento de datos,

**Cuando** intento crear la cuenta,

**Entonces** no se puede crear la cuenta.

### Reglas de negocio

- RN-001 — Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- RN-002 — Un correo no puede pertenecer a más de una cuenta.
- RN-003 — Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”.

### Validaciones

- Campos del formulario: nombre de la empresa, sector, ciudad, país, nombre del usuario, cargo, correo, teléfono, contraseña y confirmación.
- Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- La contraseña debe coincidir con su confirmación.
- El correo no puede estar ya registrado.
- Es obligatorio aceptar los términos de uso y el tratamiento de datos.

### Casos alternativos

- Ninguno.

### Casos de error

- Correo ya registrado: aviso bajo el campo y no se crea la cuenta.
- Contraseña que no cumple la regla o no coincide con la confirmación: aviso bajo el campo.
- Sin aceptar los términos no se puede crear la cuenta.

### Dependencias

- Depende de HU-012 — Crear un sector.

### Requisitos relacionados

- RF-002

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)

### Riesgos

- Depende de HU-012: debe existir al menos un sector activo.

---

## HU-003 — Recuperar mi contraseña

**Épica:** EP-001 — Acceso

**Actor:** Administrador, Empresa o Colaborador

**Prioridad:** Alta — Alta porque sin recuperación una persona puede quedar sin acceso.

**Story Points:** 3 — Pantalla simple con mensaje único y envío de correo con reglas de cuenta.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** L3

### Historia de Usuario

> Como administrador, empresa o colaborador, quiero pedir un enlace a mi correo, para volver a entrar si olvidé la contraseña.

### Descripción

Cada persona recupera su propia contraseña. El Administrador no restablece contraseñas de otras cuentas.

### Valor de negocio

Permite recuperar el acceso a quien olvidó su contraseña sin exponer qué correos están registrados.

### Caso de éxito

1. Escribo mi correo y pulso “Enviar enlace”.
2. El sistema muestra “Revisa tu correo”.
3. Recibo un correo con un enlace para crear una contraseña nueva.

### Criterios de aceptación

#### CA-001 — Solicitar enlace

**Dado** que estoy en la pantalla de recuperación y mi cuenta está activa,

**Cuando** escribo mi correo y pulso "Enviar enlace",

**Entonces** veo "Revisa tu correo" y recibo un correo con un enlace para crear una contraseña nueva.

#### CA-002 — Correo no registrado

**Dado** que el correo no existe en el sistema,

**Cuando** pulso "Enviar enlace",

**Entonces** veo el mismo mensaje "Revisa tu correo" y no se envía nada.

#### CA-003 — Cuenta desactivada

**Dado** que mi cuenta está desactivada,

**Cuando** pido el enlace,

**Entonces** veo el mismo mensaje y no recibo el enlace.

#### CA-004 — Volver a iniciar sesión

**Dado** que estoy en la pantalla de recuperación,

**Cuando** elijo volver,

**Entonces** regreso a "Iniciar sesión".

### Reglas de negocio

- RN-004 — Una cuenta desactivada no puede iniciar sesión y su información no se borra; se puede reactivar.
- RN-005 — Los avisos de inicio de sesión y recuperación no revelan si un correo está registrado.
- RN-006 — El enlace de contraseña nueva no vence por tiempo y sirve hasta guardar la contraseña; el Administrador no restablece contraseñas de otras cuentas.

### Validaciones

- El correo es necesario para enviar el enlace.

### Casos alternativos

- Volver a "Iniciar sesión".

### Casos de error

- Correo inexistente: mismo mensaje y no se envía nada.
- Cuenta desactivada: no recibe el enlace.

### Dependencias

- Depende de HU-046 — Ver las cuentas del sistema.

### Requisitos relacionados

- RF-003

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)
- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-046 (cuentas del sistema).

---

## HU-004 — Crear una contraseña nueva

**Épica:** EP-001 — Acceso

**Actor:** Administrador, Empresa o Colaborador

**Prioridad:** Alta — Alta porque completa la recuperación de acceso iniciada en HU-003.

**Story Points:** 3 — Formulario con requisitos en vivo y manejo de enlace ya usado.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** L4

### Historia de Usuario

> Como administrador, empresa o colaborador, quiero escribir una contraseña nueva desde el enlace del correo, para recuperar el acceso a mi cuenta.

### Descripción

Pantalla a la que llega el enlace de HU-003. Muestra los requisitos de la contraseña y los marca en vivo.

### Valor de negocio

Permite fijar una contraseña nueva y segura para recuperar el acceso a la cuenta.

### Caso de éxito

1. Abro el enlace; veo el correo de la cuenta.
2. Escribo la contraseña nueva y su confirmación.
3. Los requisitos se marcan en vivo.
4. Pulso “Guardar contraseña”.
5. Vuelvo a L1 con el aviso verde “Contraseña actualizada”.

### Criterios de aceptación

#### CA-001 — Ver correo de la cuenta

**Dado** que tengo un enlace de recuperación sin usar,

**Cuando** abro el enlace,

**Entonces** veo el correo de la cuenta y los requisitos de la contraseña.

#### CA-002 — Requisitos en vivo

**Dado** que escribo la contraseña nueva y su confirmación,

**Cuando** voy escribiendo,

**Entonces** los requisitos se marcan en vivo según se cumplan.

#### CA-003 — Guardar contraseña

**Dado** que todos los requisitos se cumplen,

**Cuando** pulso "Guardar contraseña",

**Entonces** vuelvo a iniciar sesión con el aviso verde "Contraseña actualizada".

#### CA-004 — Requisitos incompletos

**Dado** que algún requisito no se cumple,

**Cuando** intento pulsar "Guardar contraseña",

**Entonces** el botón no funciona.

#### CA-005 — Enlace ya usado

**Dado** que el enlace ya se usó,

**Cuando** lo abro,

**Entonces** veo "Este enlace ya no es válido" y el botón "Pedir otro enlace", y no puedo cambiar la contraseña.

### Reglas de negocio

- RN-001 — Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- RN-006 — El enlace de contraseña nueva no vence por tiempo y sirve hasta guardar la contraseña; el Administrador no restablece contraseñas de otras cuentas.

### Validaciones

- Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- La contraseña y su confirmación deben coincidir.

### Casos alternativos

- Desde un enlace inválido, "Pedir otro enlace" lleva a la recuperación de contraseña.

### Casos de error

- Enlace ya usado: se muestra "Este enlace ya no es válido".
- Requisito sin cumplir: "Guardar contraseña" no funciona.

### Dependencias

- Depende de HU-003 — Recuperar mi contraseña.

### Requisitos relacionados

- RF-003

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)

### Riesgos

- Depende de HU-003 (enlace de recuperación).

---

## HU-005 — Cerrar sesión

**Épica:** EP-001 — Acceso

**Actor:** Administrador, Empresa o Colaborador

**Prioridad:** Media — Media porque es una función de seguridad sencilla que no bloquea otras historias.

**Story Points:** 2 — Acción simple con regla sobre el botón Atrás.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A6, E11

### Historia de Usuario

> Como administrador, empresa o colaborador, quiero cerrar mi sesión, para que nadie use mi cuenta en este equipo.

### Descripción

“Cerrar sesión” está al final del menú lateral y en el perfil.

### Valor de negocio

Permite proteger la cuenta cerrando la sesión en un equipo.

### Caso de éxito

1. Pulso “Cerrar sesión”.
2. Vuelvo a L1.

### Criterios de aceptación

#### CA-001 — Cerrar sesión

**Dado** que tengo la sesión iniciada,

**Cuando** pulso "Cerrar sesión",

**Entonces** vuelvo a la pantalla de inicio de sesión.

#### CA-002 — Atrás tras cerrar

**Dado** que cerré mi sesión,

**Cuando** uso "Atrás" del navegador,

**Entonces** no veo pantallas internas.

#### CA-003 — Ubicación del botón

**Dado** que estoy dentro del sistema,

**Cuando** reviso el menú lateral y mi perfil,

**Entonces** "Cerrar sesión" está siempre al final del menú lateral y en mi perfil.

#### CA-004 — Sesión mantenida

**Dado** que marqué "Mantener la sesión iniciada",

**Cuando** pulso "Cerrar sesión",

**Entonces** la sesión termina igualmente.

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Atrás del navegador tras cerrar sesión: no se muestran pantallas internas.

### Dependencias

- Depende de HU-001 — Iniciar sesión.

### Requisitos relacionados

- RF-004

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-001.

---

## EP-002 — Inicio del Administrador

## HU-006 — Ver el inicio con indicadores

**Épica:** EP-002 — Inicio del Administrador

**Actor:** Administrador

**Prioridad:** Alta — Alta porque es la primera pantalla del administrador y base del seguimiento.

**Story Points:** 5 — Pantalla con varios indicadores, tabla y paneles resumen.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A1

### Historia de Usuario

> Como administrador, quiero ver indicadores y la lista de mediciones al entrar, para saber qué empresas necesitan atención.

### Descripción

Primera pantalla del Administrador. Registrar empresas y asignar mediciones no se hace aquí, sino en Empresas (A3).

### Valor de negocio

Da al administrador una vista rápida de qué empresas necesitan atención.

### Caso de éxito

1. Entro al sistema.
2. Veo 4 indicadores: empresas registradas, mediciones pendientes, diagnósticos completados y puntaje promedio.
3. Veo la tabla “Mediciones” y, a la derecha, “Empresas por nivel” y “Diagnósticos por sector”.
4. Pulso el nombre de una empresa para ir a su ficha (A3.1).

### Criterios de aceptación

#### CA-001 — Ver indicadores

**Dado** que entré como administrador,

**Cuando** abro el inicio,

**Entonces** veo 4 indicadores: empresas registradas, mediciones pendientes, diagnósticos completados y puntaje promedio.

#### CA-002 — Tabla de mediciones

**Dado** que existen mediciones,

**Cuando** veo la tabla "Mediciones",

**Entonces** muestra empresa, sector, fecha asignada, fecha límite, estado con barra de avance y una acción.

#### CA-003 — Paneles laterales

**Dado** que estoy en el inicio,

**Cuando** miro a la derecha de la tabla,

**Entonces** veo "Empresas por nivel" y "Diagnósticos por sector" con la versión publicada.

#### CA-004 — Ir a la ficha

**Dado** que veo una empresa en la tabla,

**Cuando** pulso su nombre,

**Entonces** voy a la ficha de esa empresa.

#### CA-005 — Sin mediciones

**Dado** que no hay mediciones,

**Cuando** abro el inicio,

**Entonces** la tabla indica que no hay mediciones.

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-001 — Iniciar sesión.
- Depende de HU-033 — Ver la ficha e historial de una empresa.

### Requisitos relacionados

- RF-005

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-001 y HU-033 (ficha de la empresa).

---

## HU-007 — Filtrar las mediciones por estado

**Épica:** EP-002 — Inicio del Administrador

**Actor:** Administrador

**Prioridad:** Alta — Alta porque facilita el seguimiento de las mediciones desde el inicio.

**Story Points:** 3 — Filtros por pestaña sobre una tabla ya existente, con contadores y estados vacíos.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: falta dibujar en el prototipo el estado "Enviada" en las pestañas.

**Vistas del prototipo:** A1, A1b, A1c, A1d, A1e

### Historia de Usuario

> Como administrador, quiero cambiar entre las pestañas Todas, En curso, No iniciadas, Vencidas y Terminadas, para ver solo las mediciones que me interesan.

### Descripción

Las pestañas filtran la tabla de mediciones por estado. Una medición recién enviada y aún en análisis cuenta como “En curso” hasta que se publica su resultado.

### Valor de negocio

Permite al administrador ver solo las mediciones del estado que le interesa.

### Caso de éxito

1. Elijo una pestaña.
2. La tabla muestra solo las mediciones de ese estado, con su contador.

### Criterios de aceptación

#### CA-001 — Filtrar por pestaña

**Dado** que estoy en el inicio del administrador,

**Cuando** elijo una pestaña (Todas, En curso, No iniciadas, Vencidas o Terminadas),

**Entonces** la tabla muestra solo las mediciones de ese estado con su contador.

#### CA-002 — En curso con avance

**Dado** que estoy en la pestaña En curso,

**Cuando** veo la tabla,

**Entonces** se muestra el avance por categorías (por ejemplo 6/10).

#### CA-003 — Vencidas en rojo

**Dado** que estoy en la pestaña Vencidas,

**Cuando** veo la tabla,

**Entonces** aparecen las que pasaron su fecha límite, con la fecha en rojo.

#### CA-004 — Terminadas

**Dado** que estoy en la pestaña Terminadas,

**Cuando** veo la tabla,

**Entonces** se ve la barra completa y la acción "Ver resultado".

#### CA-005 — Medición enviada en análisis

**Dado** que una medición fue enviada y la IA aún la analiza,

**Cuando** veo el estado,

**Entonces** se ve como "Enviada" en el mismo lugar donde se ve el estado general.

#### CA-006 — Pestaña vacía

**Dado** que una pestaña no tiene mediciones,

**Cuando** la elijo,

**Entonces** se muestra un estado vacío.

### Reglas de negocio

- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-006 — Ver el inicio con indicadores.

### Requisitos relacionados

- RF-005

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- El estado "Enviada" aún no está dibujado en el prototipo.
- Depende de HU-006.

---

## HU-008 — Recordar o reenviar el aviso

**Épica:** EP-002 — Inicio del Administrador

**Actor:** Administrador

**Prioridad:** Media — Media porque ayuda al seguimiento pero no bloquea otras historias.

**Story Points:** 3 — Acción que abre un correo para revisar y confirmar, con registro del envío.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: falta validar si el registro de envío se muestra en la ficha de la empresa.

**Vistas del prototipo:** A1b, A1c, A3.1e

### Historia de Usuario

> Como administrador, quiero enviar un recordatorio a una empresa con medición en curso o no iniciada, para que la termine a tiempo.

### Descripción

Las acciones “Recordatorio” (En curso) y “Reenviar aviso” (No iniciadas) abren el correo de la medición para revisarlo antes de enviarlo.

### Valor de negocio

Permite empujar a las empresas a terminar su medición a tiempo con un recordatorio revisado antes de enviarse.

### Caso de éxito

1. Pulso “Recordatorio” o “Reenviar aviso”.
2. Se abre el correo de la medición (A3.1e).
3. Reviso el texto y lo envío.

### Criterios de aceptación

#### CA-001 — Abrir el correo

**Dado** que existe una medición en curso o no iniciada,

**Cuando** pulso "Recordatorio" o "Reenviar aviso",

**Entonces** se abre el correo de la medición con los datos reales de la medición.

#### CA-002 — Enviar tras confirmar

**Dado** que reviso el texto del correo,

**Cuando** confirmo el envío,

**Entonces** el correo se envía en segundo plano.

#### CA-003 — Registro del envío

**Dado** que envié el correo,

**Cuando** termina el envío,

**Entonces** queda registrado cuándo se envió.

#### CA-004 — Cancelar

**Dado** que abrí el correo de la medición,

**Cuando** cancelo,

**Entonces** no se envía nada.

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Cancelar sin enviar el correo.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-037 — Editar el correo de aviso de la medición.

### Requisitos relacionados

- RF-006

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- REQUIERE VALIDACIÓN: si el registro del envío se muestra en la ficha de la empresa.
- Depende de HU-037 (edición del correo).

---

## HU-009 — Dar una nueva fecha a una medición vencida

**Épica:** EP-002 — Inicio del Administrador

**Actor:** Administrador

**Prioridad:** Media — Media porque da salida a las mediciones vencidas y atiende solicitudes de las empresas.

**Story Points:** 3 — Reutiliza la pantalla de asignar medición con la empresa preseleccionada y una regla de reemplazo.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A1d, A3.1b

### Historia de Usuario

> Como administrador, quiero cambiar la fecha límite de una medición vencida, para que la empresa pueda terminarla.

### Descripción

La acción “Nueva fecha” abre Asignar medición con la empresa ya elegida. También es la forma de atender una solicitud de la empresa (HU-083).

### Valor de negocio

Permite reactivar una medición vencida dándole una nueva fecha para que la empresa pueda terminarla.

### Caso de éxito

1. En la pestaña Vencidas pulso “Nueva fecha”.
2. Se abre Asignar medición (A3.1b) con la empresa elegida.
3. Defino la fecha y guardo.

### Criterios de aceptación

#### CA-001 — Abrir Asignar medición

**Dado** que estoy en la pestaña Vencidas,

**Cuando** pulso "Nueva fecha",

**Entonces** se abre Asignar medición con la empresa ya elegida.

#### CA-002 — Guardar nueva fecha

**Dado** que defino la fecha en Asignar medición,

**Cuando** guardo,

**Entonces** la medición vuelve a estar pendiente y sale de la pestaña Vencidas.

#### CA-003 — Reemplazo de la pendiente

**Dado** que la empresa solo puede tener una medición pendiente,

**Cuando** asigno una nueva,

**Entonces** la asignación reemplaza la pendiente.

#### CA-004 — Cancelar

**Dado** que abrí Asignar medición desde una vencida,

**Cuando** cancelo,

**Entonces** la medición sigue vencida.

### Reglas de negocio

- RN-014 — Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa.
- RN-016 — Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra.

### Validaciones

- Debe definirse una fecha para guardar.

### Casos alternativos

- Cancelar deja la medición vencida.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-036 — Asignar una medición.
- Depende de HU-070 — Marcar mediciones vencidas.

### Requisitos relacionados

- RF-007

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-036 (asignar medición) y HU-070 (marcar vencidas).
- También atiende la solicitud de la empresa de HU-083.

---

## EP-003 — Sectores

## HU-010 — Ver los diagnósticos de un sector

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Alta — Alta porque es la pantalla central para gestionar sectores y diagnósticos.

**Story Points:** 5 — Pantalla con lista, resumen y dos tablas con varias acciones.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2

### Historia de Usuario

> Como administrador, quiero elegir un sector y ver sus diagnósticos, su resumen y sus empresas, para administrar ese sector en un solo lugar.

### Descripción

Pantalla A2: lista de sectores a la izquierda y, a la derecha, el detalle del sector elegido.

### Valor de negocio

Permite administrar un sector en un solo lugar viendo sus diagnósticos, resumen y empresas.

### Caso de éxito

1. Elijo un sector en la lista.
2. Veo el “Resumen del sector”, la tabla de diagnósticos y la tabla “Empresas del sector”.
3. Desde arriba llego a “Gestionar categorías” (A2.3) y “+ Crear diagnóstico” (A2.5).

### Criterios de aceptación

#### CA-001 — Elegir un sector

**Dado** que existe al menos un sector,

**Cuando** elijo un sector en la lista,

**Entonces** veo el resumen del sector, la tabla de diagnósticos y la tabla "Empresas del sector".

#### CA-002 — Lista de sectores

**Dado** que estoy en la pantalla de sectores,

**Cuando** veo la lista,

**Entonces** cada sector muestra su número de diagnósticos y existe "+ Crear sector".

#### CA-003 — Resumen del sector

**Dado** que elegí un sector,

**Cuando** veo el resumen,

**Entonces** muestra empresas, mediciones, publicados, borradores, si está activo y mediciones por estado.

#### CA-004 — Tabla de diagnósticos

**Dado** que elegí un sector con diagnósticos,

**Cuando** veo la tabla,

**Entonces** muestra nombre, estado, preguntas, empresas y mediciones, con Editar, Vista previa, Duplicar y Archivar (o Eliminar si es borrador).

#### CA-005 — Empresas del sector

**Dado** que elegí un sector,

**Cuando** veo "Empresas del sector",

**Entonces** muestra diagnóstico asignado, medición y puntaje, con "Ver empresa".

#### CA-006 — Accesos superiores

**Dado** que elegí un sector,

**Cuando** uso los botones de arriba,

**Entonces** llego a "Gestionar categorías" y a "+ Crear diagnóstico".

### Reglas de negocio

- RN-014 — Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa.
- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Un sector sin diagnósticos lleva al estado de sector vacío (HU-012).

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-012 — Crear un sector.

### Requisitos relacionados

- RF-008

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-012.

---

## HU-011 — Ver todos los diagnósticos

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Media — Media porque complementa la vista por sector sin ser indispensable.

**Story Points:** 3 — Vista de lectura agrupada con orden y filtros.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2·T

### Historia de Usuario

> Como administrador, quiero ver los diagnósticos de todos los sectores juntos, para comparar y encontrar uno rápido.

### Descripción

Con “Todos” elegido en A2, los diagnósticos se agrupan por sector.

### Valor de negocio

Permite comparar y encontrar rápido un diagnóstico viendo todos los sectores juntos.

### Caso de éxito

1. Elijo “Todos” en la lista de sectores.
2. Veo los diagnósticos agrupados por sector.
3. Ordeno por sector, edición o nombre y filtro por estado.

### Criterios de aceptación

#### CA-001 — Ver todos agrupados

**Dado** que estoy en la pantalla de sectores,

**Cuando** elijo "Todos",

**Entonces** veo los diagnósticos agrupados por sector.

#### CA-002 — Ordenar y filtrar

**Dado** que veo todos los diagnósticos,

**Cuando** ordeno por sector, edición o nombre y filtro por estado,

**Entonces** la lista se ajusta al orden y filtro elegidos.

#### CA-003 — Resumen general

**Dado** que elegí "Todos",

**Cuando** veo el panel "Resumen general",

**Entonces** muestra los totales de todos los sectores.

#### CA-004 — Filtro sin resultados

**Dado** que aplico un filtro,

**Cuando** no hay coincidencias,

**Entonces** se muestra un estado vacío.

#### CA-005 — Sin editar ni eliminar sector

**Dado** que estoy en la vista "Todos",

**Cuando** busco las opciones del sector,

**Entonces** no existen "Editar sector" ni "Eliminar sector".

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-010 — Ver los diagnósticos de un sector.

### Requisitos relacionados

- RF-008

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-010.

---

## HU-012 — Crear un sector

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Alta — Alta porque sin sectores las empresas no pueden registrarse.

**Story Points:** 3 — Formulario corto en modal con nombre único y estado activo.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.2a, A2b

### Historia de Usuario

> Como administrador, quiero crear un sector nuevo, para ofrecerlo a empresas de un tipo que aún no existe.

### Descripción

Modal “Crear sector” (A2.2a). Al crearlo queda vacío (A2b).

### Valor de negocio

Permite ofrecer un sector nuevo a empresas de un tipo que aún no existe.

### Caso de éxito

1. Pulso “+ Crear sector”.
2. Escribo el nombre, una descripción opcional y elijo si está activo.
3. Pulso crear; el sector aparece vacío con “+ Crear diagnóstico” y “Duplicar uno de otro sector”.

### Criterios de aceptación

#### CA-001 — Crear sector

**Dado** que pulso "+ Crear sector",

**Cuando** escribo el nombre, una descripción opcional, elijo si está activo y pulso crear,

**Entonces** el sector aparece vacío con "+ Crear diagnóstico" y "Duplicar uno de otro sector".

#### CA-002 — Nombre vacío

**Dado** que dejo el nombre vacío,

**Cuando** intento crear,

**Entonces** veo un aviso bajo el campo y no se crea.

#### CA-003 — Nombre repetido

**Dado** que el nombre ya existe,

**Cuando** intento crear,

**Entonces** veo un aviso bajo el campo y no se crea.

#### CA-004 — Sector activo en el registro

**Dado** que creé el sector como activo,

**Cuando** una empresa abre el registro,

**Entonces** el sector aparece entre las opciones.

#### CA-005 — Sector inactivo

**Dado** que creé el sector como inactivo,

**Cuando** una empresa abre el registro,

**Entonces** el sector no se ofrece.

### Reglas de negocio

- RN-003 — Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”.

### Validaciones

- El nombre es obligatorio.
- El nombre no puede repetirse.
- La descripción es opcional.

### Casos alternativos

- Ninguno.

### Casos de error

- Nombre vacío o repetido: aviso bajo el campo y no se crea.

### Dependencias

- Ninguna.

### Requisitos relacionados

- RF-009

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Ninguno identificado.

---

## HU-013 — Editar un sector

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Alta — Alta porque mantiene correctos los sectores que se ofrecen a las empresas.

**Story Points:** 3 — Formulario en modal con conteos y acceso a otras opciones.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.2

### Historia de Usuario

> Como administrador, quiero cambiar el nombre, la descripción o si está activo, para mantener los sectores al día.

### Descripción

Modal “Editar sector” (A2.2). Desde “Otras opciones” se llega a Reasignar, Desactivar y Eliminar.

### Valor de negocio

Permite mantener al día los datos y el estado de los sectores.

### Caso de éxito

1. Pulso “Editar sector”.
2. Veo cuántas empresas, diagnósticos y mediciones tiene.
3. Cambio los datos y pulso “Guardar sector”.

### Criterios de aceptación

#### CA-001 — Ver conteos

**Dado** que elegí un sector,

**Cuando** pulso "Editar sector",

**Entonces** veo cuántas empresas, diagnósticos y mediciones tiene.

#### CA-002 — Guardar cambios

**Dado** que cambié el nombre, la descripción o si está activo,

**Cuando** pulso "Guardar sector",

**Entonces** se guardan los cambios y vuelvo a la pantalla de sectores.

#### CA-003 — Nombre repetido

**Dado** que escribo un nombre que ya existe,

**Cuando** intento guardar,

**Entonces** veo un aviso y no se guarda.

#### CA-004 — Cancelar

**Dado** que abrí el modal de edición,

**Cuando** pulso "Cancelar",

**Entonces** no cambia nada.

#### CA-005 — Otras opciones

**Dado** que estoy en el modal de edición,

**Cuando** abro "Otras opciones",

**Entonces** llego a Reasignar, Desactivar y Eliminar.

### Reglas de negocio

- RN-003 — Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”.
- RN-008 — Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva; las mediciones hechas se conservan.

### Validaciones

- El nombre no puede repetirse.

### Casos alternativos

- "Cancelar" no cambia nada.
- "Otras opciones" lleva a Reasignar, Desactivar y Eliminar.

### Casos de error

- Nombre repetido: aviso y no se guarda.

### Dependencias

- Depende de HU-012 — Crear un sector.

### Requisitos relacionados

- RF-009

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-012.

---

## HU-014 — Reasignar las empresas de un sector

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Media — Media porque es una operación de mantenimiento menos frecuente.

**Story Points:** 3 — Modal con selección, aviso opcional por correo y regla de conservar mediciones.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.2b

### Historia de Usuario

> Como administrador, quiero pasar todas las empresas de un sector a otro, para poder cerrar un sector sin perder empresas.

### Descripción

Modal A2.2b.

### Valor de negocio

Permite cerrar un sector sin perder a sus empresas, pasándolas a otro sector.

### Caso de éxito

1. Elijo el sector nuevo de una lista de sectores activos.
2. Decido si aviso por correo a las empresas.
3. Pulso el botón, que dice cuántas empresas se mueven (por ejemplo “Reasignar 9 empresas”).

### Criterios de aceptación

#### CA-001 — Reasignar empresas

**Dado** que el sector tiene empresas y existe otro sector activo,

**Cuando** elijo el sector nuevo y pulso el botón de reasignar,

**Entonces** todas las empresas pasan al sector nuevo.

#### CA-002 — Solo sectores activos

**Dado** que abro el modal de reasignación,

**Cuando** despliego la lista de sectores,

**Entonces** solo aparecen sectores activos.

#### CA-003 — Botón con conteo

**Dado** que el sector tiene 9 empresas,

**Cuando** veo el botón,

**Entonces** dice "Reasignar 9 empresas".

#### CA-004 — Mediciones conservadas

**Dado** que reasigné las empresas,

**Cuando** reviso sus mediciones,

**Entonces** las ya hechas no cambian y las próximas usan diagnósticos del sector nuevo.

#### CA-005 — Cancelar

**Dado** que abrí el modal,

**Cuando** pulso "Cancelar",

**Entonces** no se mueve ninguna empresa.

### Reglas de negocio

- RN-003 — Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”.
- RN-008 — Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva; las mediciones hechas se conservan.

### Validaciones

- Debe elegirse un sector nuevo entre los activos.

### Casos alternativos

- "Cancelar" no mueve nada.
- Decidir si se avisa por correo a las empresas.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-013 — Editar un sector.

### Requisitos relacionados

- RF-009

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-013.

---

## HU-015 — Desactivar un sector

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es un apoyo de orden que evita borrar datos, pero no bloquea el flujo principal.

**Story Points:** 2 — Un modal de confirmación con un cambio de estado y la opción de reactivar.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.2c

### Historia de Usuario

> Como administrador, quiero dejar de ofrecer un sector sin borrarlo, para conservar su información.

### Descripción

Modal A2.2c.

### Valor de negocio

Permite dejar de ofrecer un sector sin perder la información de sus empresas, diagnósticos y mediciones.

### Caso de éxito

1. Pulso “Desactivar sector” y confirmo.
2. El sector deja de ofrecerse al registrar empresas nuevas.

### Criterios de aceptación

#### CA-001 — Desactivar un sector

**Dado** que tengo un sector activo,

**Cuando** pulso “Desactivar sector” y confirmo,

**Entonces** el sector deja de ofrecerse al registrar empresas nuevas.

#### CA-002 — No aparece en el registro

**Dado** que un sector fue desactivado,

**Cuando** una empresa se registra,

**Entonces** ese sector no aparece entre las opciones.

#### CA-003 — Datos conservados

**Dado** que desactivé un sector con empresas, diagnósticos y mediciones,

**Cuando** reviso su información,

**Entonces** las empresas, diagnósticos y mediciones se conservan.

#### CA-004 — Reactivar el sector

**Dado** que un sector está desactivado,

**Cuando** entro a “Editar sector” y lo reactivo,

**Entonces** el sector vuelve a estar disponible.

### Reglas de negocio

- RN-003 — Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”.
- RN-008 — Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva; las mediciones hechas se conservan.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Reactivar el sector desde “Editar sector”.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-013 — Editar un sector.

### Requisitos relacionados

- RF-009

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-013 (Editar un sector), desde donde se reactiva.

---

## HU-016 — Eliminar un sector

**Épica:** EP-003 — Sectores

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es mantenimiento de la lista y puede resolverse desactivando el sector.

**Story Points:** 3 — Dos modales según el sector tenga datos o no, con una regla que bloquea la eliminación.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.2d, A2.2e

### Historia de Usuario

> Como administrador, quiero borrar un sector que ya no se usa, para mantener la lista limpia.

### Descripción

Dos modales: sector con datos (A2.2d) y sector vacío (A2.2e).

### Valor de negocio

Mantiene limpia la lista de sectores sin poner en riesgo empresas ni mediciones existentes.

### Caso de éxito

1. Pulso “Eliminar sector”.
2. Si está vacío, confirmo y se elimina.
3. Vuelvo a A2 y el sector ya no está.

### Criterios de aceptación

#### CA-001 — Eliminar sector vacío

**Dado** que un sector no tiene empresas ni mediciones,

**Cuando** pulso “Eliminar sector” y confirmo,

**Entonces** el sector se elimina y vuelvo a A2 sin verlo en la lista.

#### CA-002 — Aviso de irreversibilidad

**Dado** que elijo eliminar un sector vacío,

**Cuando** se abre el modal,

**Entonces** se pide confirmación y se avisa que no se puede deshacer.

#### CA-003 — Sector con datos

**Dado** que un sector tiene empresas o mediciones,

**Cuando** pulso “Eliminar sector”,

**Entonces** el modal indica cuántas tiene, ofrece Reasignar o Desactivar y no permite eliminarlo.

### Reglas de negocio

- RN-008 — Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva; las mediciones hechas se conservan.

### Validaciones

- Ninguna específica.

### Casos alternativos

- En un sector con datos, elegir Reasignar o Desactivar en lugar de eliminar.

### Casos de error

- Sector con empresas o mediciones: no se puede eliminar.

### Dependencias

- Depende de HU-013 — Editar un sector.
- Depende de HU-014 — Reasignar las empresas de un sector.
- Depende de HU-015 — Desactivar un sector.

### Requisitos relacionados

- RF-009

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-013, HU-014 y HU-015 (reasignar y desactivar).

---

## EP-004 — Catálogo de categorías

## HU-017 — Ver y editar el catálogo de categorías

**Épica:** EP-004 — Catálogo de categorías

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: los diagnósticos se arman a partir de este catálogo.

**Story Points:** 3 — Pantalla de lectura con conteo de uso y edición simple con validación de nombre.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.3

### Historia de Usuario

> Como administrador, quiero ver todas las categorías y editar su nombre y descripción, para que todos los diagnósticos usen los mismos nombres.

### Descripción

Lista única de categorías. La descripción es el texto que la empresa lee al iniciar cada categoría.

### Valor de negocio

Da una lista única de categorías para que todos los diagnósticos usen los mismos nombres y descripciones.

### Caso de éxito

1. Entro a A2 y pulso “Gestionar categorías”.
2. Veo cada categoría con el número de diagnósticos donde se usa.
3. Edito el nombre o la descripción de una categoría y guardo.

### Criterios de aceptación

#### CA-001 — Acceso al catálogo

**Dado** que estoy en A2,

**Cuando** pulso “Gestionar categorías”,

**Entonces** se abre el catálogo de categorías.

#### CA-002 — Uso por categoría

**Dado** que estoy en el catálogo,

**Cuando** veo la lista,

**Entonces** cada categoría muestra en cuántos diagnósticos se usa.

#### CA-003 — Editar descripción

**Dado** que edito la descripción de una categoría,

**Cuando** guardo,

**Entonces** la empresa ve esa descripción al iniciar la categoría.

#### CA-004 — Nombre vacío o repetido

**Dado** que edito el nombre de una categoría,

**Cuando** lo dejo vacío o igual al de otra y guardo,

**Entonces** se muestra un aviso y no se guarda.

#### CA-005 — Cancelar

**Dado** que estoy editando una categoría,

**Cuando** pulso “Cancelar”,

**Entonces** no se cambia nada.

### Reglas de negocio

- RN-009 — El nombre de una categoría es obligatorio, de máximo 40 caracteres y único; una categoría con respuestas se archiva en lugar de borrarse.

### Validaciones

- El nombre no puede quedar vacío.
- El nombre no puede repetirse.

### Casos alternativos

- “Cancelar” no cambia nada.

### Casos de error

- Nombre vacío o repetido: se avisa y no se guarda.

### Dependencias

- Depende de HU-010 — Ver los diagnósticos de un sector.

### Requisitos relacionados

- RF-010

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-010; agregar o quitar categorías de un diagnóstico se hace en HU-026, no aquí.

---

## HU-018 — Crear una categoría

**Épica:** EP-004 — Catálogo de categorías

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: sin categorías nuevas el catálogo no puede crecer ni adaptarse a los sectores.

**Story Points:** 5 — Formulario con reglas de nombre y selección de diagnósticos que quedan incompletos.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.3b

### Historia de Usuario

> Como administrador, quiero agregar una categoría al catálogo, para medir un tema nuevo.

### Descripción

Modal para crear una categoría y, si quiero, sumarla de una vez a diagnósticos en borrador.

### Valor de negocio

Permite medir un tema nuevo agregando una categoría al catálogo y sumándola a diagnósticos en borrador.

### Caso de éxito

1. Pulso “+ Crear categoría” en el catálogo.
2. Escribo el nombre (y la descripción).
3. Opcionalmente marco los diagnósticos en borrador donde agregarla.
4. Pulso “Crear categoría”.

### Criterios de aceptación

#### CA-001 — Crear categoría

**Dado** que estoy en el catálogo,

**Cuando** escribo un nombre válido y pulso “Crear categoría”,

**Entonces** la categoría se crea en el catálogo.

#### CA-002 — Nombre repetido

**Dado** que existe la categoría “Redes sociales”,

**Cuando** intento crear otra con ese nombre,

**Entonces** se muestra un aviso bajo el campo y no se crea.

#### CA-003 — Nombre vacío o muy largo

**Dado** que estoy creando una categoría,

**Cuando** dejo el nombre vacío o de más de 40 caracteres,

**Entonces** se avisa y no se crea.

#### CA-004 — Agregar a borradores

**Dado** que marco diagnósticos en borrador al crear la categoría,

**Cuando** pulso “Crear categoría”,

**Entonces** la categoría se suma a esos diagnósticos y quedan marcados como incompletos.

#### CA-005 — Versiones publicadas intactas

**Dado** que existen diagnósticos con versión publicada,

**Cuando** creo la categoría,

**Entonces** las versiones publicadas no se modifican y solo puedo elegir diagnósticos en borrador.

### Reglas de negocio

- RN-009 — El nombre de una categoría es obligatorio, de máximo 40 caracteres y único; una categoría con respuestas se archiva en lugar de borrarse.
- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.
- RN-011 — La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%.
- RN-012 — Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos.

### Validaciones

- Nombre obligatorio.
- Nombre de máximo 40 caracteres.
- Nombre único en el catálogo.
- Solo se puede agregar a diagnósticos en borrador.

### Casos alternativos

- Crear la categoría sin agregarla a ningún diagnóstico (la selección es opcional).

### Casos de error

- Nombre vacío, de más de 40 caracteres o repetido: se avisa y no se crea.

### Dependencias

- Depende de HU-017 — Ver y editar el catálogo de categorías.

### Requisitos relacionados

- RF-010

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-017.

---

## HU-019 — Eliminar o archivar una categoría

**Épica:** EP-004 — Catálogo de categorías

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es mantenimiento del catálogo; la creación y edición son más urgentes.

**Story Points:** 5 — Dos comportamientos según tenga respuestas, más el reparto de importancia en los diagnósticos afectados.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.3c

### Historia de Usuario

> Como administrador, quiero quitar una categoría del catálogo, para dejar de usarla sin perder los resultados anteriores.

### Descripción

Si la categoría nunca fue respondida se borra; si ya tiene respuestas se archiva.

### Valor de negocio

Permite retirar una categoría sin perder los resultados anteriores de las empresas.

### Caso de éxito

1. Elijo “Eliminar” o “Archivar” en una categoría.
2. El modal me explica qué pasará según si tiene respuestas.
3. Confirmo.

### Criterios de aceptación

#### CA-001 — Eliminar sin respuestas

**Dado** que una categoría nunca fue respondida,

**Cuando** elijo “Eliminar” y confirmo,

**Entonces** la categoría se elimina del catálogo.

#### CA-002 — Archivar con respuestas

**Dado** que una categoría ya tiene respuestas,

**Cuando** elijo “Archivar” y confirmo,

**Entonces** la categoría se archiva y deja de poder elegirse en diagnósticos nuevos.

#### CA-003 — Resultados anteriores

**Dado** que archivé una categoría con respuestas,

**Cuando** veo resultados ya hechos,

**Entonces** siguen mostrando la categoría.

#### CA-004 — Diagnósticos afectados

**Dado** que la categoría estaba en un diagnóstico,

**Cuando** se elimina o archiva,

**Entonces** la importancia se reparte de nuevo y el diagnóstico queda incompleto hasta ajustarla.

#### CA-005 — Cancelar

**Dado** que estoy en el modal de confirmación,

**Cuando** pulso “Cancelar”,

**Entonces** no se cambia nada.

### Reglas de negocio

- RN-009 — El nombre de una categoría es obligatorio, de máximo 40 caracteres y único; una categoría con respuestas se archiva en lugar de borrarse.
- RN-011 — La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%.

### Validaciones

- Ninguna específica.

### Casos alternativos

- “Cancelar” no cambia nada.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-017 — Ver y editar el catálogo de categorías.
- Depende de HU-027 — Editar la importancia de las categorías.

### Requisitos relacionados

- RF-010

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-017 y HU-027 (ajuste de la importancia).

---

## EP-005 — Diagnósticos

## HU-020 — Crear un diagnóstico

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: sin diagnósticos no hay mediciones; es el producto central.

**Story Points:** 8 — Formulario con varias decisiones: copia de otro diagnóstico, selección de categorías y reparto de porcentajes.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.5

### Historia de Usuario

> Como administrador, quiero crear un diagnóstico nuevo para un sector, para tener el cuestionario que responderán sus empresas.

### Descripción

Formulario para crear un diagnóstico en blanco o copiando otro, eligiendo sus categorías.

### Valor de negocio

Da el cuestionario que responderán las empresas de un sector, en blanco o a partir de otro diagnóstico.

### Caso de éxito

1. Pulso “+ Crear diagnóstico”.
2. Escribo nombre, sector y descripción.
3. Elijo empezar en blanco o copiar otro diagnóstico.
4. Elijo las categorías del catálogo.
5. Defino qué porcentaje del puntaje total vale cada categoría.
6. Pulso “Crear y abrir el editor”.

### Criterios de aceptación

#### CA-001 — Crear en blanco

**Dado** que estoy en A2.5 con nombre, sector y categorías elegidos,

**Cuando** pulso “Crear y abrir el editor”,

**Entonces** se crea el borrador v1 y veo el editor con las categorías sin preguntas.

#### CA-002 — Copiar otro diagnóstico

**Dado** que elijo copiar otro diagnóstico,

**Cuando** creo el diagnóstico,

**Entonces** se crea como borrador v1 y se abre el editor.

#### CA-003 — Importancia por categoría

**Dado** que elegí las categorías del catálogo,

**Cuando** defino el porcentaje de cada una,

**Entonces** queda definida su importancia, que debe sumar 100% para publicar.

#### CA-004 — Falta nombre o sector

**Dado** que no escribí el nombre o no elegí el sector,

**Cuando** intento crear el diagnóstico,

**Entonces** se avisa y no se crea.

### Reglas de negocio

- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.
- RN-011 — La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%.
- RN-012 — Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos.

### Validaciones

- Nombre obligatorio.
- Sector obligatorio.
- La importancia de las categorías debe sumar 100% para poder publicar.

### Casos alternativos

- Empezar en blanco o copiar otro diagnóstico.

### Casos de error

- Falta el nombre o el sector: se avisa y no se crea.

### Dependencias

- Depende de HU-017 — Ver y editar el catálogo de categorías.
- Depende de HU-012 — Crear un sector.

### Requisitos relacionados

- RF-011

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-017 (catálogo) y HU-012 (sectores).

---

## HU-021 — Duplicar un diagnóstico

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: ahorra trabajo, pero se puede crear un diagnóstico desde cero.

**Story Points:** 3 — Formulario corto de dos campos con copia del contenido.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.7

### Historia de Usuario

> Como administrador, quiero hacer una copia de un diagnóstico, para reutilizarlo en otro sector o cambiarlo sin tocar el publicado.

### Descripción

Crea un borrador nuevo con el contenido de otro diagnóstico.

### Valor de negocio

Permite reutilizar un diagnóstico en otro sector o modificarlo sin tocar el publicado.

### Caso de éxito

1. Pulso “Duplicar” en un diagnóstico.
2. Escribo el nombre de la copia y elijo su sector.
3. Pulso “Crear copia y editarla”.

### Criterios de aceptación

#### CA-001 — Crear copia

**Dado** que estoy en A2.7 con un diagnóstico elegido,

**Cuando** escribo el nombre de la copia, elijo el sector y pulso “Crear copia y editarla”,

**Entonces** se crea un borrador en versión 1 y se abre en el editor.

#### CA-002 — Contenido copiado

**Dado** que se creó la copia,

**Cuando** reviso su contenido,

**Entonces** mantiene categorías, preguntas, puntajes e importancia.

#### CA-003 — Sin mediciones ni empresas

**Dado** que el original tiene mediciones y empresas,

**Cuando** se crea la copia,

**Entonces** no se copian mediciones ni empresas.

#### CA-004 — Nombre inválido

**Dado** que estoy en el formulario de copia,

**Cuando** dejo el nombre vacío o de más de 60 caracteres,

**Entonces** se muestra un aviso.

### Reglas de negocio

- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.

### Validaciones

- Nombre de la copia obligatorio.
- Nombre de máximo 60 caracteres.

### Casos alternativos

- Ninguno.

### Casos de error

- Nombre vacío o de más de 60 caracteres: se avisa.

### Dependencias

- Depende de HU-020 — Crear un diagnóstico.

### Requisitos relacionados

- RF-011

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-020.

---

## HU-022 — Archivar o eliminar un diagnóstico

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es mantenimiento de la lista de diagnósticos.

**Story Points:** 3 — Dos acciones según el estado, ambas con confirmación.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: falta diseñar el modal de confirmación en el prototipo.

**Vistas del prototipo:** A2, A2·T

### Historia de Usuario

> Como administrador, quiero sacar un diagnóstico de uso, para que no se asigne más.

### Descripción

Un diagnóstico publicado se archiva; un borrador nunca publicado se elimina.

> REQUIERE VALIDACIÓN: el modal de confirmación no está diseñado en el prototipo.

### Valor de negocio

Permite sacar un diagnóstico de uso sin perder los resultados ya obtenidos.

### Caso de éxito

1. Elijo “Archivar” (publicado) o “Eliminar” (borrador) en la tabla.
2. Confirmo en el modal.
3. El diagnóstico sale de la lista de activos.

### Criterios de aceptación

#### CA-001 — Archivar publicado

**Dado** que un diagnóstico está publicado,

**Cuando** elijo “Archivar” y confirmo,

**Entonces** sale de la lista de activos y deja de ofrecerse para asignar.

#### CA-002 — Eliminar borrador

**Dado** que un diagnóstico es un borrador nunca publicado,

**Cuando** elijo “Eliminar” y confirmo,

**Entonces** el diagnóstico se elimina y sale de la lista.

#### CA-003 — Confirmación previa

**Dado** que elijo archivar o eliminar,

**Cuando** se abre el modal,

**Entonces** se pide confirmación antes de aplicar la acción.

#### CA-004 — Resultados conservados

**Dado** que archivé un diagnóstico con resultados,

**Cuando** consulto resultados anteriores,

**Entonces** no se han perdido.

#### CA-005 — Cancelar

**Dado** que estoy en el modal de confirmación,

**Cuando** pulso “Cancelar”,

**Entonces** no se cambia nada.

### Reglas de negocio

- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.

### Validaciones

- Ninguna específica.

### Casos alternativos

- “Cancelar” no cambia nada.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-010 — Ver los diagnósticos de un sector.
- Depende de HU-029 — Publicar una versión.

### Requisitos relacionados

- RF-011

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- REQUIERE VALIDACIÓN: el modal de confirmación no está diseñado en el prototipo.
- Depende de HU-010 y HU-029.

---

## HU-023 — Ver y ordenar el contenido del diagnóstico

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: es la pantalla base del editor sobre la que se apoyan las demás historias de diagnósticos.

**Story Points:** 5 — Editor con lista de categorías, estados, preguntas y reordenamiento por arrastre que se conserva.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.1

### Historia de Usuario

> Como administrador, quiero ver las categorías y preguntas del diagnóstico y ordenarlas, para organizar lo que verá la empresa.

### Descripción

Editor del diagnóstico: categorías a la izquierda, preguntas de la categoría elegida al centro.

### Valor de negocio

Permite revisar y organizar el contenido que verá la empresa, con claridad sobre qué falta corregir.

### Caso de éxito

1. Abro un diagnóstico con “Editar”.
2. Elijo una categoría y veo sus preguntas con tipo, indicación y opciones.
3. Arrastro ⋮⋮ para cambiar el orden de las preguntas.

### Criterios de aceptación

#### CA-001 — Ver categorías y preguntas

**Dado** que abro un diagnóstico con “Editar”,

**Cuando** elijo una categoría,

**Entonces** veo sus preguntas con tipo, indicación y opciones.

#### CA-002 — Estado de cada categoría

**Dado** que estoy en el editor,

**Cuando** veo la lista de categorías,

**Entonces** cada una muestra importancia, número de preguntas y “Falta corregir” o “Completa”.

#### CA-003 — Versión y estado

**Dado** que estoy en el editor,

**Cuando** miro la parte de arriba,

**Entonces** veo la versión y el estado, por ejemplo “Borrador v3 · Incompleto · 2 por corregir”.

#### CA-004 — Reordenar preguntas

**Dado** que veo las preguntas de una categoría,

**Cuando** arrastro ⋮⋮ para cambiar el orden,

**Entonces** el nuevo orden se conserva al volver a abrir el diagnóstico.

### Reglas de negocio

- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-020 — Crear un diagnóstico.

### Requisitos relacionados

- RF-012

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-020.

---

## HU-024 — Agregar una pregunta

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: las preguntas son el contenido esencial del diagnóstico.

**Story Points:** 8 — Modal con tres tipos de pregunta, puntajes por opción e indicaciones con límites.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.1e

### Historia de Usuario

> Como administrador, quiero agregar una pregunta a una categoría, para medir algo nuevo.

### Descripción

Modal para crear una pregunta abierta, de opción única o de selección múltiple. En cada respuesta puedo escribir una indicación para la IA.

### Valor de negocio

Permite medir algo nuevo con preguntas abiertas, de opción única o de selección múltiple, con indicaciones para la IA.

### Caso de éxito

1. Pulso “+ Agregar pregunta”.
2. Elijo el tipo y escribo la pregunta y la “Indicación para responder”.
3. En opción única y selección múltiple escribo las opciones con su puntaje de 0 a 100 y, si quiero, una indicación para la IA por opción (“Ten en cuenta”).
4. En una abierta escribo un solo criterio para calificarla (0–100).
5. Pulso “Agregar pregunta”.

### Criterios de aceptación

#### CA-001 — Elegir el tipo

**Dado** que pulso “+ Agregar pregunta”,

**Cuando** elijo el tipo,

**Entonces** puedo escoger abierta, opción única o selección múltiple.

#### CA-002 — Opciones con puntaje

**Dado** que creo una pregunta de opción única o selección múltiple,

**Cuando** escribo las opciones,

**Entonces** cada una tiene un puntaje de 0 a 100 y puedo agregar más opciones.

#### CA-003 — Indicación obligatoria

**Dado** que no escribí la “Indicación para responder”,

**Cuando** intento agregar la pregunta,

**Entonces** se avisa y no se agrega.

#### CA-004 — Indicación por respuesta

**Dado** que estoy creando una pregunta de opción única,

**Cuando** escribo “Ten en cuenta” en una opción,

**Entonces** esa indicación solo se usará en el análisis cuando la empresa elija esa opción.

#### CA-005 — Pregunta abierta

**Dado** que elijo el tipo abierta,

**Cuando** escribo su criterio de calificación,

**Entonces** tiene un único criterio de 0 a 100.

#### CA-006 — Pregunta agregada

**Dado** que completé los datos requeridos,

**Cuando** pulso “Agregar pregunta”,

**Entonces** aparece al final de la categoría.

### Reglas de negocio

- RN-013 — Pregunta: abierta, opción única o selección múltiple; opciones con puntaje 0–100; la indicación para responder es obligatoria; “Ten en cuenta” por respuesta es opcional (máx. 200 caracteres), no lo ve la empresa y solo se usa si esa respuesta fue elegida; la abierta tiene un único criterio de calificación; texto abierto de máximo 1000 caracteres.
- RN-012 — Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos.

### Validaciones

- “Indicación para responder” obligatoria.
- Puntaje de cada opción entre 0 y 100.
- “Ten en cuenta” opcional, máximo 200 caracteres.
- Debe haber opciones con puntaje (en opción única y selección múltiple).
- La pregunta abierta tiene un solo criterio de calificación (0–100).

### Casos alternativos

- Marcar la respuesta como obligatoria (opcional).

### Casos de error

- Sin indicación para responder o sin opciones con puntaje: se avisa y no se agrega.

### Dependencias

- Depende de HU-023 — Ver y ordenar el contenido del diagnóstico.

### Requisitos relacionados

- RF-013

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-023.

---

## HU-025 — Editar o eliminar una pregunta

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: sin poder corregir preguntas no se puede dejar el diagnóstico listo para publicar.

**Story Points:** 3 — Reutiliza el modal de HU-024 y agrega eliminar con confirmación.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.1f

### Historia de Usuario

> Como administrador, quiero cambiar o borrar una pregunta, para corregir el diagnóstico.

### Descripción

Mismo modal que HU-024 con los datos actuales; permite eliminar con confirmación.

### Valor de negocio

Permite corregir el diagnóstico cambiando o quitando preguntas sin afectar versiones ya publicadas.

### Caso de éxito

1. Pulso editar en una pregunta.
2. Cambio sus datos, incluidas las indicaciones por respuesta, y guardo.
3. O pulso “Eliminar pregunta” y confirmo.

### Criterios de aceptación

#### CA-001 — Abrir con datos actuales

**Dado** que el diagnóstico está en borrador,

**Cuando** pulso editar en una pregunta,

**Entonces** el modal abre con sus datos actuales.

#### CA-002 — Guardar cambios

**Dado** que cambié los datos de la pregunta, incluidas las indicaciones por respuesta,

**Cuando** guardo,

**Entonces** la pregunta queda actualizada y se mantienen las reglas de HU-024.

#### CA-003 — Eliminar pregunta

**Dado** que estoy editando una pregunta,

**Cuando** pulso “Eliminar pregunta” y confirmo,

**Entonces** la pregunta se elimina.

#### CA-004 — Versiones publicadas intactas

**Dado** que existen versiones publicadas,

**Cuando** edito o elimino una pregunta,

**Entonces** los cambios solo afectan al borrador.

#### CA-005 — Cancelar

**Dado** que estoy en el modal,

**Cuando** pulso “Cancelar”,

**Entonces** no se cambia nada.

### Reglas de negocio

- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.
- RN-013 — Pregunta: abierta, opción única o selección múltiple; opciones con puntaje 0–100; la indicación para responder es obligatoria; “Ten en cuenta” por respuesta es opcional (máx. 200 caracteres), no lo ve la empresa y solo se usa si esa respuesta fue elegida; la abierta tiene un único criterio de calificación; texto abierto de máximo 1000 caracteres.

### Validaciones

- Se mantienen las reglas de HU-024 al editar.

### Casos alternativos

- “Cancelar” no cambia nada.
- Eliminar la pregunta en lugar de editarla, con confirmación.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-024 — Agregar una pregunta.

### Requisitos relacionados

- RF-013

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-024.

---

## HU-026 — Agregar categorías al diagnóstico

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: el diagnóstico ya nace con categorías; esto es una ampliación.

**Story Points:** 3 — Modal de selección con reparto automático de importancia.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.1d

### Historia de Usuario

> Como administrador, quiero agregar categorías del catálogo a este diagnóstico, para ampliar lo que se mide.

### Descripción

Modal con las categorías del catálogo; las que ya están se marcan.

### Valor de negocio

Permite ampliar lo que mide un diagnóstico tomando categorías del catálogo.

### Caso de éxito

1. Pulso “+ Agregar categoría”.
2. Marco las categorías a sumar.
3. Pulso “Agregar”.

### Criterios de aceptación

#### CA-001 — Categorías ya incluidas

**Dado** que abro “+ Agregar categoría”,

**Cuando** veo la lista del catálogo,

**Entonces** las ya incluidas se marcan “En este diagnóstico”.

#### CA-002 — Categorías archivadas

**Dado** que existen categorías archivadas,

**Cuando** veo la lista,

**Entonces** no se pueden elegir.

#### CA-003 — Reparto de importancia

**Dado** que marco categorías y pulso “Agregar”,

**Cuando** se agregan,

**Entonces** la importancia se reparte en partes iguales.

#### CA-004 — Queda incompleto

**Dado** que agregué categorías,

**Cuando** reviso el estado,

**Entonces** el diagnóstico queda incompleto hasta que cada categoría tenga al menos 1 pregunta y el total sea 100%.

#### CA-005 — Categoría no encontrada

**Dado** que no está la categoría que busco,

**Cuando** pulso “¿No está? Crear en el catálogo”,

**Entonces** me lleva a A2.3b.

### Reglas de negocio

- RN-009 — El nombre de una categoría es obligatorio, de máximo 40 caracteres y único; una categoría con respuestas se archiva en lugar de borrarse.
- RN-011 — La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%.
- RN-012 — Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos.

### Validaciones

- Las categorías archivadas no se pueden elegir.

### Casos alternativos

- “¿No está? Crear en el catálogo” lleva a A2.3b.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-018 — Crear una categoría.
- Depende de HU-023 — Ver y ordenar el contenido del diagnóstico.

### Requisitos relacionados

- RF-012

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-018 y HU-023.

---

## HU-027 — Editar la importancia de las categorías

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: el puntaje total depende de esta ponderación.

**Story Points:** 3 — Formulario con suma en vivo y bloqueo de guardado.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.1b

### Historia de Usuario

> Como administrador, quiero cambiar el porcentaje de cada categoría, para que pesen más los temas importantes para el sector.

### Descripción

La importancia de cada categoría es su porcentaje del puntaje total y es propia de cada diagnóstico.

### Valor de negocio

Permite que pesen más los temas importantes para el sector, cuidando que el total sea 100%.

### Caso de éxito

1. Pulso “Editar importancia”.
2. Cambio el porcentaje de cada categoría; el total se calcula en vivo.
3. Pulso “Guardar”.

### Criterios de aceptación

#### CA-001 — Total en vivo

**Dado** que pulso “Editar importancia”,

**Cuando** cambio el porcentaje de una categoría,

**Entonces** el total se calcula en vivo.

#### CA-002 — Guardar con 100%

**Dado** que el total es 100%,

**Cuando** pulso “Guardar”,

**Entonces** se guardan los porcentajes.

#### CA-003 — Total distinto de 100

**Dado** que edito la importancia y el total es 90%,

**Cuando** intento guardar,

**Entonces** el botón está desactivado y se indica cuánto falta.

#### CA-004 — Repartir en partes iguales

**Dado** que estoy editando la importancia,

**Cuando** pulso “Repartir en partes iguales”,

**Entonces** todas las categorías quedan con el mismo porcentaje.

### Reglas de negocio

- RN-011 — La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%.

### Validaciones

- La suma debe ser exactamente 100%.

### Casos alternativos

- Ninguno.

### Casos de error

- Total distinto de 100%: “Guardar” no se activa.

### Dependencias

- Depende de HU-023 — Ver y ordenar el contenido del diagnóstico.

### Requisitos relacionados

- RF-012

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-023.

---

## HU-028 — Ver la vista previa y el estado

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Prioridad Alta: evita publicar versiones incorrectas, que luego no se pueden cambiar.

**Story Points:** 5 — Pantalla de revisión con cinco requisitos, lista de correcciones con navegación y cuestionario de solo vista.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.4

### Historia de Usuario

> Como administrador, quiero ver el diagnóstico como lo verá la empresa y qué le falta, para publicarlo sin errores.

### Descripción

Pantalla de revisión con los requisitos para publicar y la pestaña “Cuestionario”.

### Valor de negocio

Permite revisar el diagnóstico como lo verá la empresa y saber qué falta para publicarlo sin errores.

### Caso de éxito

1. Abro “Vista previa”.
2. Reviso los requisitos y las categorías por corregir.
3. Pulso “Corregir” para ir a la pregunta con el problema.
4. En “Cuestionario” veo las preguntas como las ve la empresa.

### Criterios de aceptación

#### CA-001 — Requisitos para publicar

**Dado** que abro “Vista previa”,

**Cuando** reviso los requisitos,

**Entonces** se muestran los 5 requisitos con su estado.

#### CA-002 — Categorías por corregir

**Dado** que hay categorías con problemas,

**Cuando** veo la lista,

**Entonces** aparecen con el motivo y el botón “Corregir”.

#### CA-003 — Ir a corregir

**Dado** que veo una categoría por corregir,

**Cuando** pulso “Corregir”,

**Entonces** voy a la pregunta con el problema.

#### CA-004 — Cuestionario sin guardar

**Dado** que estoy en la pestaña “Cuestionario”,

**Cuando** respondo preguntas,

**Entonces** las preguntas se ven como las ve la empresa y lo que respondo no se guarda.

#### CA-005 — Resultado de ejemplo

**Dado** que estoy en la vista previa,

**Cuando** busco el resultado de ejemplo,

**Entonces** hay acceso a él (HU-030).

### Reglas de negocio

- RN-012 — Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos.

### Validaciones

- Cada categoría tiene al menos 1 pregunta.
- Cada opción tiene puntaje.
- La importancia suma 100%.
- Todas las preguntas tienen indicación.
- Las selecciones múltiples pueden llegar a 100 puntos.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-023 — Ver y ordenar el contenido del diagnóstico.

### Requisitos relacionados

- RF-014

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-023; enlaza con HU-030 (resultado de ejemplo).

---

## HU-029 — Publicar una versión

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Alta — Alta porque sin publicar no hay diagnóstico que asignar a las empresas.

**Story Points:** 5 — Flujo con varias reglas: requisitos, nota obligatoria, versionado automático y decisión sobre mediciones pendientes.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.1c

### Historia de Usuario

> Como administrador, quiero publicar el borrador como una versión nueva, para que las empresas respondan la versión corregida.

### Descripción

Publica el borrador como versión inmutable con nota de cambios.

### Valor de negocio

Permite que las empresas respondan una versión corregida del diagnóstico sin alterar los resultados anteriores.

### Caso de éxito

1. Pulso “Publicar”.
2. Escribo la nota de cambios.
3. Elijo qué pasa con las mediciones pendientes de la versión anterior.
4. Confirmo.

### Criterios de aceptación

#### CA-001 — Publicar con nota

**Dado** que el borrador cumple los 5 requisitos,

**Cuando** escribo la nota de cambios y confirmo,

**Entonces** se crea la versión siguiente, queda publicada y la anterior pasa a reemplazada.

#### CA-002 — Publicar bloqueado

**Dado** que al borrador le falta algún requisito,

**Cuando** entro a la vista de publicación,

**Entonces** el botón “Publicar” aparece desactivado.

#### CA-003 — Nota obligatoria

**Dado** que estoy en el paso de publicar,

**Cuando** intento confirmar sin nota de cambios,

**Entonces** no se publica hasta que escriba la nota.

#### CA-004 — Mediciones pendientes

**Dado** que hay mediciones pendientes de la versión anterior,

**Cuando** elijo dejarlas con la anterior o pasarlas a la nueva si aún no empezaron,

**Entonces** cada medición queda con la versión que elegí.

#### CA-005 — Historial de versiones

**Dado** que el diagnóstico tiene versiones,

**Cuando** consulto el historial,

**Entonces** veo borrador, publicada (con fecha y empresas que la usan) y reemplazada.

#### CA-006 — Versión inmutable

**Dado** que una versión está publicada,

**Cuando** intento editarla,

**Entonces** no se puede modificar.

### Reglas de negocio

- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.
- RN-012 — Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos.

### Validaciones

- La nota de cambios es obligatoria.
- Deben cumplirse los 5 requisitos para publicar.

### Casos alternativos

- “Publicar” permanece desactivado mientras falte un requisito.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-028 — Ver la vista previa y el estado.

### Requisitos relacionados

- RF-014

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-028 (revisión de requisitos del borrador).

---

## HU-030 — Ver un resultado de ejemplo

**Épica:** EP-005 — Diagnósticos

**Actor:** Administrador

**Prioridad:** Baja — Baja porque es una ayuda de revisión que no bloquea la publicación.

**Story Points:** 3 — Pantalla de lectura con tres casos de muestra y acceso al formato del PDF.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A2.6, A2.6b, A2.6c

### Historia de Usuario

> Como administrador, quiero ver cómo se vería el resultado con datos de muestra, para revisar el diagnóstico antes de publicarlo.

### Descripción

Tres casos de muestra con la misma forma que el resultado real, incluidas las observaciones por pregunta y las recomendaciones.

### Valor de negocio

Permite revisar cómo se verá el diagnóstico para la empresa antes de publicarlo.

### Caso de éxito

1. Desde la vista previa abro “Resultado de ejemplo”.
2. Cambio entre caso medio, mejor y peor.
3. Puedo ver el formato del PDF.
4. Vuelvo con “← Volver a la vista previa”.

### Criterios de aceptación

#### CA-001 — Abrir ejemplo

**Dado** que estoy en la vista previa,

**Cuando** abro “Resultado de ejemplo”,

**Entonces** veo un resultado con datos de muestra.

#### CA-002 — Cambiar de caso

**Dado** que veo el resultado de ejemplo,

**Cuando** cambio entre caso medio, mejor y peor,

**Entonces** se muestra cada caso, el mejor cerca del máximo y el peor cerca del mínimo.

#### CA-003 — Misma forma que el real

**Dado** que veo un caso de muestra,

**Cuando** reviso el contenido,

**Entonces** se ve igual que el resultado real, con observaciones por pregunta y recomendaciones.

#### CA-004 — Ver formato PDF

**Dado** que estoy en el resultado de ejemplo,

**Cuando** elijo ver el formato del PDF,

**Entonces** llego al formato del PDF.

#### CA-005 — Volver

**Dado** que estoy en el resultado de ejemplo,

**Cuando** pulso “← Volver a la vista previa”,

**Entonces** regreso a la vista previa.

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Volver a la vista previa con “← Volver a la vista previa”.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-028 — Ver la vista previa y el estado.
- Depende de HU-064 — Ver mi resultado.

### Requisitos relacionados

- RF-015

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-028, HU-064 (resultado real) y HU-066 (formato PDF).

---

## EP-006 — Empresas y mediciones

## HU-031 — Ver la lista de empresas

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Alta — Alta porque es la puerta de entrada a todo el trabajo con empresas.

**Story Points:** 3 — Lista paginada con buscador y filtros, sin reglas complejas.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3

### Historia de Usuario

> Como administrador, quiero buscar y filtrar las empresas registradas, para encontrar una rápido.

### Descripción

Lista paginada con buscador y filtros.

### Valor de negocio

Permite encontrar rápido una empresa para consultarla o asignarle una medición.

### Caso de éxito

1. Entro a Empresas.
2. Busco por nombre o filtro por sector, nivel o estado.
3. Abro “Ver empresa” o “Asignar medición”.

### Criterios de aceptación

#### CA-001 — Buscar por nombre

**Dado** que estoy en Empresas,

**Cuando** busco por nombre,

**Entonces** la lista muestra las empresas que coinciden.

#### CA-002 — Filtrar

**Dado** que estoy en la lista,

**Cuando** filtro por sector, nivel o estado,

**Entonces** solo veo las empresas que cumplen el filtro.

#### CA-003 — Datos de cada fila

**Dado** que la lista tiene empresas,

**Cuando** reviso una fila,

**Entonces** veo sector, última medición, puntaje y nivel.

#### CA-004 — Acciones por fila

**Dado** que veo una fila,

**Cuando** elijo “Ver empresa” o “Asignar medición”,

**Entonces** llego a la ficha o a asignar medición.

#### CA-005 — Paginación

**Dado** que hay muchas empresas,

**Cuando** navego la lista,

**Entonces** las empresas se muestran paginadas.

#### CA-006 — Sin resultados

**Dado** que la búsqueda o filtro no coincide con nada,

**Cuando** se muestra la lista,

**Entonces** aparece un mensaje de lista vacía.

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Sin resultados: se muestra un mensaje de lista vacía.

### Dependencias

- Depende de HU-032 — Registrar una empresa.

### Requisitos relacionados

- RF-016

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-032 y de las pantallas HU-033 y HU-036 a las que enlaza.

---

## HU-032 — Registrar una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Alta — Alta porque es la vía alterna de alta de empresas por el Administrador.

**Story Points:** 5 — Formulario con datos de empresa y usuario, correo único, correo de contraseña y asignación opcional de medición.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.2

### Historia de Usuario

> Como administrador, quiero registrar una empresa y su usuario principal, para que empiece a responder sin crearse la cuenta sola.

### Descripción

Alternativa al registro propio (HU-002).

### Valor de negocio

Permite dar de alta a una empresa que no se registra sola y dejarla lista para responder.

### Caso de éxito

1. Pulso “Registrar empresa”.
2. Completo los datos de la empresa y del usuario principal.
3. Opcionalmente asigno la primera medición.
4. Pulso “Registrar”.

### Criterios de aceptación

#### CA-001 — Registro exitoso

**Dado** que completo los datos de la empresa y del usuario principal,

**Cuando** pulso “Registrar”,

**Entonces** la empresa y su usuario principal quedan registrados.

#### CA-002 — Correo para contraseña

**Dado** que se registró el usuario principal,

**Cuando** termina el registro,

**Entonces** el usuario recibe un correo para crear su contraseña y yo no la escribo.

#### CA-003 — Primera medición opcional

**Dado** que estoy registrando una empresa,

**Cuando** decido asignar la primera medición en el mismo paso,

**Entonces** la medición queda asignada junto con el registro.

#### CA-004 — Correo duplicado

**Dado** que el correo ya está registrado,

**Cuando** pulso “Registrar”,

**Entonces** se avisa y no se crea la empresa.

#### CA-005 — Solo sectores activos

**Dado** que elijo el sector de la empresa,

**Cuando** abro las opciones,

**Entonces** solo aparecen sectores activos.

### Reglas de negocio

- RN-002 — Un correo no puede pertenecer a más de una cuenta.
- RN-003 — Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”.
- RN-006 — El enlace de contraseña nueva no vence por tiempo y sirve hasta guardar la contraseña; el Administrador no restablece contraseñas de otras cuentas.
- RN-014 — Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa.

### Validaciones

- El correo no puede pertenecer a otra cuenta.
- Solo se pueden elegir sectores activos.
- La primera medición es opcional.

### Casos alternativos

- Ninguno.

### Casos de error

- Correo ya registrado: se avisa y no se crea.

### Dependencias

- Depende de HU-004 — Crear una contraseña nueva.
- Depende de HU-012 — Crear un sector.

### Requisitos relacionados

- RF-017

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)
- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-004 (crear contraseña) y HU-012 (sectores).

---

## HU-033 — Ver la ficha e historial de una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Alta — Alta porque es la vista central para acompañar a cada empresa.

**Story Points:** 5 — Pantalla con gráfica, variación, colaboradores, lista de mediciones y varios accesos a otras pantallas.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.1

### Historia de Usuario

> Como administrador, quiero ver el puntaje, la variación y la evolución de una empresa, para seguir su progreso.

### Descripción

Ficha con el último puntaje, la variación, la gráfica y la lista de mediciones.

### Valor de negocio

Permite seguir el progreso de una empresa en un solo lugar.

### Caso de éxito

1. Abro una empresa desde la lista.
2. Veo su último puntaje, variación y gráfica de evolución.
3. Abro una medición para ver su resultado o historial.
4. Puedo ir a “Datos de la cuenta” o a “Asignar medición”.

### Criterios de aceptación

#### CA-001 — Ver puntaje y evolución

**Dado** que abro una empresa con mediciones,

**Cuando** veo su ficha,

**Entonces** aparecen el último puntaje, la variación y la gráfica de evolución.

#### CA-002 — Una sola medición

**Dado** que la empresa tiene una sola medición,

**Cuando** veo la ficha,

**Entonces** no se muestra variación.

#### CA-003 — Colaboradores

**Dado** que la empresa tiene colaboradores,

**Cuando** reviso la ficha,

**Entonces** veo su nombre y usuario en solo lectura.

#### CA-004 — Abrir una medición

**Dado** que veo la lista de mediciones,

**Cuando** abro una medición,

**Entonces** llego a su resultado o a su historial.

#### CA-005 — Accesos de la ficha

**Dado** que estoy en la ficha,

**Cuando** uso la pestaña “Datos de la cuenta” o “Asignar medición”,

**Entonces** llego a esa pantalla.

### Reglas de negocio

- RN-020 — La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-031 — Ver la lista de empresas.

### Requisitos relacionados

- RF-016

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-031; enlaza a HU-038 y HU-039.

---

## HU-034 — Editar los datos de la cuenta de una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Media — Media porque corrige datos pero no bloquea el flujo principal.

**Story Points:** 3 — Formulario de edición con una regla de correo único y datos de solo lectura.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.1d

### Historia de Usuario

> Como administrador, quiero corregir los datos de la empresa y de su usuario principal, para mantenerlos al día.

### Descripción

Pestaña “Datos de la cuenta” de la ficha.

### Valor de negocio

Mantiene al día los datos de la empresa y de su usuario principal.

### Caso de éxito

1. Abro “Datos de la cuenta”.
2. Edito empresa, sector, ciudad, país, tamaño o datos del usuario principal.
3. Guardo.

### Criterios de aceptación

#### CA-001 — Editar y guardar

**Dado** que estoy en “Datos de la cuenta”,

**Cuando** edito empresa, sector, ciudad, país, tamaño o datos del usuario principal y guardo,

**Entonces** los cambios quedan guardados.

#### CA-002 — Cambio de sector

**Dado** que cambio el sector de la empresa,

**Cuando** guardo,

**Entonces** las mediciones ya hechas no se modifican.

#### CA-003 — Correo en uso

**Dado** que escribo un correo usado por otra cuenta,

**Cuando** guardo,

**Entonces** se avisa y no se guarda el cambio.

#### CA-004 — Información de la cuenta

**Dado** que veo la pestaña,

**Cuando** reviso su contenido,

**Entonces** veo estado de la cuenta, fecha de registro, último acceso y número de mediciones.

#### CA-005 — Acciones disponibles

**Dado** que veo las acciones de la cuenta,

**Cuando** las reviso,

**Entonces** están “Ver como esta empresa”, “Asignar medición” y “Desactivar cuenta”, sin restablecer contraseña.

### Reglas de negocio

- RN-002 — Un correo no puede pertenecer a más de una cuenta.
- RN-006 — El enlace de contraseña nueva no vence por tiempo y sirve hasta guardar la contraseña; el Administrador no restablece contraseñas de otras cuentas.
- RN-008 — Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva; las mediciones hechas se conservan.

### Validaciones

- El correo no puede estar usado por otra cuenta.

### Casos alternativos

- Ninguno.

### Casos de error

- Correo ya usado por otra cuenta: se avisa.

### Dependencias

- Depende de HU-033 — Ver la ficha e historial de una empresa.

### Requisitos relacionados

- RF-017

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-033.

---

## HU-035 — Desactivar o reactivar la cuenta de una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Media — Media porque es una acción de gestión de cuentas de uso ocasional.

**Story Points:** 3 — Modal con confirmación, motivo y aviso opcionales y efecto sobre los colaboradores.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.1f

### Historia de Usuario

> Como administrador, quiero desactivar la cuenta de una empresa, para impedir que entre sin borrar su información.

### Descripción

Modal de desactivación con motivo opcional y aviso por correo.

### Valor de negocio

Permite impedir el acceso de una empresa sin perder su información.

### Caso de éxito

1. Pulso “Desactivar cuenta”.
2. Veo cuántas mediciones se conservan; escribo un motivo (opcional) y elijo si aviso por correo.
3. Confirmo.

### Criterios de aceptación

#### CA-001 — Desactivar con confirmación

**Dado** que pulso “Desactivar cuenta”,

**Cuando** confirmo en el modal,

**Entonces** la cuenta queda desactivada y su información no se borra.

#### CA-002 — Mediciones conservadas

**Dado** que se abre el modal,

**Cuando** lo reviso,

**Entonces** indica cuántas mediciones se conservan.

#### CA-003 — Motivo y aviso opcionales

**Dado** que estoy en el modal,

**Cuando** dejo vacío el motivo y no marco el aviso por correo,

**Entonces** puedo desactivar igualmente.

#### CA-004 — Colaboradores desactivados

**Dado** que desactivo la empresa,

**Cuando** un colaborador intenta iniciar sesión,

**Entonces** no puede entrar.

#### CA-005 — Empresa sin acceso

**Dado** que la empresa está desactivada,

**Cuando** intenta iniciar sesión,

**Entonces** no puede entrar.

#### CA-006 — Reactivar

**Dado** que la cuenta está desactivada,

**Cuando** uso la misma ficha para reactivarla,

**Entonces** la empresa puede volver a entrar.

### Reglas de negocio

- RN-004 — Una cuenta desactivada no puede iniciar sesión y su información no se borra; se puede reactivar.
- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.

### Validaciones

- El motivo y el aviso por correo son opcionales.

### Casos alternativos

- Para reactivar se usa la misma ficha.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-033 — Ver la ficha e historial de una empresa.

### Requisitos relacionados

- RF-017

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-033.

---

## HU-036 — Asignar una medición

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Alta — Alta porque sin asignar mediciones las empresas no responden diagnósticos.

**Story Points:** 8 — Flujo con varias reglas: diagnóstico publicado del sector, reemplazo de pendiente, vista previa y aviso por correo.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.1b

### Historia de Usuario

> Como administrador, quiero asignar a una empresa una medición de un diagnóstico publicado, para que lo responda y ver su evolución.

### Descripción

Modal para crear una medición pendiente, con fecha límite y mensaje opcionales.

### Valor de negocio

Permite poner a una empresa a responder un diagnóstico y medir su evolución.

### Caso de éxito

1. Elijo la empresa y un diagnóstico publicado de su sector.
2. Opcionalmente fijo fecha límite y escribo un mensaje.
3. Veo cómo lo verá la empresa en su inicio.
4. Opcionalmente envío el aviso por correo (HU-037).
5. Pulso “Asignar”.

### Criterios de aceptación

#### CA-001 — Asignar medición

**Dado** que elijo una empresa y un diagnóstico publicado de su sector,

**Cuando** pulso “Asignar”,

**Entonces** se crea una medición pendiente para la empresa.

#### CA-002 — Ya hay una pendiente

**Dado** que la empresa tiene una medición pendiente,

**Cuando** asigno otra,

**Entonces** se me avisa que la nueva reemplaza a la pendiente antes de confirmar.

#### CA-003 — Datos opcionales

**Dado** que estoy en el modal,

**Cuando** dejo vacíos la fecha límite y el mensaje,

**Entonces** puedo asignar igualmente.

#### CA-004 — Borradores no elegibles

**Dado** que abro la lista de diagnósticos,

**Cuando** veo los borradores,

**Entonces** aparecen pero no se pueden elegir.

#### CA-005 — Vista de la empresa

**Dado** que completé la asignación,

**Cuando** abro la vista previa,

**Entonces** veo cómo lo verá la empresa en su inicio.

#### CA-006 — Aviso por correo

**Dado** que quiero avisar a la empresa,

**Cuando** elijo enviar el aviso y lo edito antes,

**Entonces** el aviso se envía con mi texto.

### Reglas de negocio

- RN-014 — Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa.
- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.

### Validaciones

- Solo se eligen diagnósticos publicados del sector de la empresa.
- La fecha límite y el mensaje son opcionales.
- Solo puede haber una medición pendiente por empresa.

### Casos alternativos

- Si ya hay una medición pendiente, se avisa que la nueva la reemplaza.
- El envío del aviso por correo es opcional.

### Casos de error

- Los borradores aparecen pero no se pueden elegir.

### Dependencias

- Depende de HU-029 — Publicar una versión.
- Depende de HU-031 — Ver la lista de empresas.

### Requisitos relacionados

- RF-018

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-029 (diagnóstico publicado), HU-031 y HU-037 (aviso por correo).

---

## HU-037 — Editar el correo de aviso de la medición

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Media — Media porque mejora la comunicación pero la medición puede asignarse sin editar el correo.

**Story Points:** 5 — Plantilla con variables, vista previa con datos reales, prueba y envío en segundo plano.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.1e

### Historia de Usuario

> Como administrador, quiero ver y editar el correo que recibe la empresa, para que el aviso sea claro.

### Descripción

Plantilla con variables y envío en segundo plano.

### Valor de negocio

Permite que el aviso que recibe la empresa sea claro y correcto.

### Caso de éxito

1. Abro el correo de la medición.
2. Edito el texto con las variables disponibles.
3. Veo cómo le llega con los datos reales.
4. Opcionalmente me envío una prueba.
5. Pulso “Enviar”.

### Criterios de aceptación

#### CA-001 — Editar con variables

**Dado** que abro el correo de la medición,

**Cuando** edito el texto,

**Entonces** puedo usar variables como empresa, diagnóstico y fecha límite.

#### CA-002 — Vista previa real

**Dado** que edité el correo,

**Cuando** abro la vista previa,

**Entonces** veo cómo le llega con los datos reales.

#### CA-003 — Correo de prueba

**Dado** que quiero revisar el correo,

**Cuando** me envío una prueba,

**Entonces** recibo el correo de prueba.

#### CA-004 — Envío sin bloqueo

**Dado** que pulso “Enviar”,

**Cuando** se envía el correo,

**Entonces** la pantalla no se bloquea mientras se envía.

#### CA-005 — Fallo y reintento

**Dado** que el envío falla,

**Cuando** se muestra el resultado,

**Entonces** se avisa y puedo reintentar.

### Reglas de negocio

- Ninguna específica.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Enviarme una prueba antes de enviar el correo.

### Casos de error

- Si el envío falla, se avisa y se puede reintentar.

### Dependencias

- Depende de HU-036 — Asignar una medición.

### Requisitos relacionados

- RF-006

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-036.

---

## HU-038 — Ver el resultado de una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Alta — Alta porque es la forma de revisar el resultado que recibe el cliente.

**Story Points:** 3 — Pantalla de solo lectura que reutiliza el resultado de la empresa, con accesos a respuestas y PDF.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.3

### Historia de Usuario

> Como administrador, quiero ver el resultado de una medición de una empresa, para acompañarla y explicarle su diagnóstico.

### Descripción

Vista de solo lectura del mismo resultado que ve la empresa.

### Valor de negocio

Permite acompañar a la empresa y explicarle su diagnóstico viendo lo mismo que ella.

### Caso de éxito

1. Abro el resultado desde la ficha o desde el inicio.
2. Reviso puntaje, nivel, radar, tabla, detalle por categoría y recomendaciones.
3. Puedo ver las respuestas o descargar el PDF.

### Criterios de aceptación

#### CA-001 — Ver resultado

**Dado** que la medición está terminada,

**Cuando** abro el resultado desde la ficha o desde el inicio,

**Entonces** veo puntaje, nivel, radar, tabla, detalle por categoría y recomendaciones.

#### CA-002 — Igual que la empresa

**Dado** que reviso el resultado,

**Cuando** lo comparo con el que ve la empresa,

**Entonces** es el mismo, sin análisis FODA.

#### CA-003 — Ir a las respuestas

**Dado** que estoy en el resultado,

**Cuando** elijo ver las respuestas,

**Entonces** llego a las respuestas de la empresa.

#### CA-004 — Descargar PDF

**Dado** que estoy en el resultado,

**Cuando** pulso descargar el PDF,

**Entonces** se descarga el PDF del resultado.

### Reglas de negocio

- RN-023 — El resultado se publica directamente, sin revisión del Administrador, y queda guardado e inmutable.
- RN-028 — El resultado no incluye análisis FODA ni comparación con promedios.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-064 — Ver mi resultado.

### Requisitos relacionados

- RF-019

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-064 (resultado de la empresa), HU-040 y HU-066.

---

## HU-039 — Ver el historial de una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Media — Media porque complementa la ficha y repite el historial que ya ve la empresa.

**Story Points:** 3 — Pantalla de lectura con gráfica y accesos a resultado, respuestas y reasignación.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.4

### Historia de Usuario

> Como administrador, quiero ver todas las mediciones de una empresa, para ver cómo ha evolucionado.

### Descripción

Mismo historial que ve la empresa.

### Valor de negocio

Permite ver cómo ha evolucionado una empresa a lo largo de sus mediciones.

### Caso de éxito

1. Abro el historial de una empresa.
2. Veo la gráfica desde la 2.ª medición.
3. Abro el resultado o las respuestas de una medición, o la reasigno.

### Criterios de aceptación

#### CA-001 — Ver historial

**Dado** que abro el historial de una empresa,

**Cuando** lo reviso,

**Entonces** veo lo mismo que ve la empresa.

#### CA-002 — Gráfica desde la 2.ª

**Dado** que la empresa tiene una o más mediciones,

**Cuando** veo el historial,

**Entonces** la gráfica aparece desde la 2.ª medición.

#### CA-003 — Resultado y respuestas

**Dado** que veo una medición del historial,

**Cuando** la abro,

**Entonces** llego a su resultado y a sus respuestas.

#### CA-004 — Reasignar

**Dado** que veo una medición,

**Cuando** elijo reasignarla,

**Entonces** se abre la asignación de medición.

### Reglas de negocio

- RN-020 — La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-067 — Ver mi historial.

### Requisitos relacionados

- RF-019

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-067 (historial de la empresa) y HU-036 (reasignar).

---

## HU-040 — Ver las respuestas de una empresa

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Media — Media porque apoya la explicación del resultado sin ser indispensable para obtenerlo.

**Story Points:** 2 — Pantalla simple de solo lectura con puntaje por categoría.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A3.5

### Historia de Usuario

> Como administrador, quiero leer lo que respondió una empresa, para entender su puntaje.

### Descripción

Respuestas en solo lectura por categoría.

### Valor de negocio

Permite entender el puntaje de una empresa leyendo lo que respondió.

### Caso de éxito

1. Abro “Ver respuestas” desde el resultado o historial.
2. Reviso cada categoría.
3. Puedo descargar el PDF o volver al resultado.

### Criterios de aceptación

#### CA-001 — Ver por categoría

**Dado** que abro “Ver respuestas”,

**Cuando** reviso cada categoría,

**Entonces** las respuestas se ven por categoría y en solo lectura.

#### CA-002 — Puntaje por categoría

**Dado** que veo una categoría,

**Cuando** reviso su contenido,

**Entonces** se muestra su puntaje.

#### CA-003 — Descargar PDF

**Dado** que estoy en las respuestas,

**Cuando** pulso descargar el PDF,

**Entonces** se descarga el PDF.

#### CA-004 — Volver al resultado

**Dado** que estoy en las respuestas,

**Cuando** elijo volver,

**Entonces** regreso al resultado.

### Reglas de negocio

- RN-017 — Después de enviar el diagnóstico no se pueden cambiar las respuestas.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Volver al resultado.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-038 — Ver el resultado de una empresa.

### Requisitos relacionados

- RF-019

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-038.

---

## HU-083 — Atender las solicitudes de nueva medición

**Épica:** EP-006 — Empresas y mediciones

**Actor:** Administrador

**Prioridad:** Media — Es Media porque cierra el ciclo de la solicitud de la empresa.

**Story Points:** 3 — Correo de aviso y reutilización de Asignar medición, con cierre de la solicitud.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: falta dibujar la pantalla en el prototipo

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como administrador, quiero ver cuando una empresa pide una nueva medición porque la suya venció, para asignarle otra.

### Descripción

Cuando una medición vence, la empresa puede pedir una nueva (HU-082). El Administrador recibe la solicitud y la atiende asignando otra medición.

> La solicitud le llega al Administrador por correo. Pendiente de dibujar en el prototipo.

### Valor de negocio

Permite al Administrador atender a tiempo a las empresas cuya medición venció.

### Caso de éxito

1. Recibo un correo que indica qué empresa pidió una nueva medición.
2. Abro Asignar medición con la empresa ya elegida (HU-036).
3. Asigno la nueva medición.

### Criterios de aceptación

#### CA-001 — Aviso por correo

**Dado** que una empresa pidió una nueva medición,

**Cuando** se envía la solicitud,

**Entonces** el Administrador recibe un correo con la empresa que la pidió.

#### CA-002 — Asignar desde la solicitud

**Dado** que atiendo la solicitud,

**Cuando** abro Asignar medición,

**Entonces** la empresa ya viene elegida y puedo asignar una medición nueva.

#### CA-003 — Solicitud cerrada

**Dado** que asigné la nueva medición,

**Cuando** termina la asignación,

**Entonces** la solicitud deja de estar abierta.

#### CA-004 — No atender

**Dado** que decido no atender la solicitud,

**Cuando** no asigno ninguna medición,

**Entonces** la medición vencida queda como está.

### Reglas de negocio

- RN-016 — Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra.
- RN-014 — Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa.

### Validaciones

- Una empresa no puede enviar otra solicitud mientras la anterior siga abierta.

### Casos alternativos

- Si decido no atenderla, la medición vencida queda como está.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-036 — Asignar una medición.
- Depende de HU-082 — Pedir una nueva medición cuando la mía venció.
- Depende de HU-070 — Marcar mediciones vencidas.

### Requisitos relacionados

- RF-034

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- SIN VISTA: pendiente de dibujar en el prototipo.
- Depende de HU-036, HU-082 y HU-070.

---

## EP-007 — Configuración de la IA

## HU-041 — Editar las instrucciones generales de la IA

**Épica:** EP-007 — Configuración de la IA

**Actor:** Administrador

**Prioridad:** Alta — Alta porque la calidad de las observaciones y recomendaciones depende de estas instrucciones.

**Story Points:** 5 — Pantalla con varios elementos de prompt, alcance (general, sector, empresa), panel informativo y guardar o descartar.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A4

### Historia de Usuario

> Como administrador, quiero editar las instrucciones generales con las que la IA analiza cada categoría, para que analice como NuevasTIC quiere.

### Descripción

Pantalla “Analizar categoría”. El prompt tiene contexto, tarea, detalles y restricciones, y ejemplos (pocos); el formato de la respuesta es fijo y no se edita.

### Valor de negocio

Permite que el análisis de la IA refleje el criterio de NuevasTIC.

### Caso de éxito

1. Abro “Configuración IA”.
2. Edito los elementos del prompt.
3. Veo los datos que se insertan solos, como {sector} o {categoria}.
4. Elijo a quién se aplica: todas las empresas, un sector (HU-042) o una empresa (HU-043).
5. Pulso “Guardar prompt”.

### Criterios de aceptación

#### CA-001 — Menú sin fases

**Dado** que inicié sesión como Administrador,

**Cuando** veo el menú lateral,

**Entonces** aparece “Configuración IA” sin submenú de fases.

#### CA-002 — Elementos del prompt

**Dado** que abro “Configuración IA”,

**Cuando** reviso el prompt,

**Entonces** veo contexto, tarea, detalles y restricciones, y ejemplos, y los datos que se insertan solos como {sector} o {categoria}.

#### CA-003 — Formato fijo

**Dado** que reviso el formato de la respuesta,

**Cuando** intento editarlo,

**Entonces** se muestra pero no se puede editar.

#### CA-004 — Guardar prompt

**Dado** que edité el prompt y elegí a quién se aplica,

**Cuando** pulso “Guardar prompt”,

**Entonces** los cambios quedan guardados.

#### CA-005 — Descartar

**Dado** que edité el prompt,

**Cuando** pulso “Descartar”,

**Entonces** todo queda como estaba.

#### CA-006 — Panel de información

**Dado** que estoy en la pantalla,

**Cuando** miro el panel derecho,

**Entonces** veo qué prompt se usa, cuántos sectores y empresas tienen ajuste, cuándo aplica y la última edición.

### Reglas de negocio

- RN-021 — El análisis de IA es una sola etapa por categoría; si falla, se reintenta solo esa categoría hasta 3 veces y luego se avisa por correo al Administrador.
- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- El formato de la respuesta es fijo y no se edita.

### Casos alternativos

- “Descartar” deja todo como estaba.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-042 — Ajustar el prompt para un sector.
- Depende de HU-043 — Ajustar el prompt para una empresa.
- Depende de HU-080 — Entender cómo se arma el prompt final.

### Requisitos relacionados

- RF-020

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-042, HU-043 y HU-080.

---

## HU-042 — Ajustar el prompt para un sector

**Épica:** EP-007 — Configuración de la IA

**Actor:** Administrador

**Prioridad:** Media — Media porque refina el prompt general, que ya funciona por sí solo.

**Story Points:** 5 — Tres modos por elemento (usar, agregar, reemplazar), vista previa, aviso de empresas con ajuste propio y quitar ajuste.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A4.5

### Historia de Usuario

> Como administrador, quiero ajustar el prompt solo para un sector, para que la IA entienda sus particularidades.

### Descripción

Para cada elemento del prompt se elige Usar general, Agregar o Reemplazar.

### Valor de negocio

Permite que la IA entienda las particularidades de cada sector.

### Caso de éxito

1. Elijo “Solo un sector” y el sector.
2. En cada elemento elijo Usar general, Agregar o Reemplazar y escribo mi texto.
3. Guardo el ajuste.

### Criterios de aceptación

#### CA-001 — Elegir sector

**Dado** que elijo “Solo un sector” y el sector,

**Cuando** veo la pantalla,

**Entonces** veo a cuántas empresas aplica el ajuste.

#### CA-002 — Usar, agregar o reemplazar

**Dado** que edito un elemento del prompt,

**Cuando** elijo Usar general, Agregar o Reemplazar,

**Entonces** se muestra la vista previa descrita; Agregar suma mi texto al final del general y Reemplazar lo sustituye.

#### CA-003 — Empresas con ajuste propio

**Dado** que hay empresas del sector con ajuste propio,

**Cuando** preparo el ajuste,

**Entonces** se avisa cuáles son y ellas se saltan el del sector.

#### CA-004 — Guardar ajuste

**Dado** que escribí mis ajustes,

**Cuando** guardo,

**Entonces** el ajuste queda guardado para el sector.

#### CA-005 — Quitar ajuste

**Dado** que el sector tiene un ajuste,

**Cuando** lo quito,

**Entonces** las empresas vuelven al prompt general.

### Reglas de negocio

- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Quitar el ajuste para que las empresas vuelvan al prompt general.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-041 — Editar las instrucciones generales de la IA.

### Requisitos relacionados

- RF-021

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-041.

---

## HU-043 — Ajustar el prompt para una empresa

**Épica:** EP-007 — Configuración de la IA

**Actor:** Administrador

**Prioridad:** Baja — Prioridad Baja: es un refinamiento opcional sobre el prompt del sector, que ya funciona sin él.

**Story Points:** 5 — Flujo con tres opciones por elemento, herencia de capas y la posibilidad de quitar el ajuste.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A4.6

### Historia de Usuario

> Como administrador, quiero ajustar el prompt solo para una empresa, para tener en cuenta su caso particular.

### Descripción

El texto base es el del sector, que hereda del general.

### Valor de negocio

Permite que el análisis de la IA refleje el caso particular de una empresa sin afectar a las demás.

### Caso de éxito

1. Elijo “Solo una empresa” y la empresa.
2. En cada elemento elijo Usar sector, Agregar o Reemplazar.
3. Guardo el ajuste.

### Criterios de aceptación

#### CA-001 — Ver texto base del sector

**Dado** que soy Administrador en la configuración del prompt,

**Cuando** elijo “Solo una empresa” y la empresa,

**Entonces** veo el texto base del sector que se hereda del general.

#### CA-002 — Elegir cómo ajustar cada elemento

**Dado** que seleccioné una empresa,

**Cuando** elijo Usar sector, Agregar o Reemplazar en un elemento,

**Entonces** el elemento queda configurado con la opción elegida.

#### CA-003 — Guardar el ajuste

**Dado** que configuré los elementos de la empresa,

**Cuando** guardo el ajuste,

**Entonces** el ajuste queda aplicado a las mediciones que se respondan después de guardar.

#### CA-004 — Quitar el ajuste

**Dado** que la empresa tiene un ajuste propio,

**Cuando** quito el ajuste,

**Entonces** la empresa vuelve al prompt de su sector.

#### CA-005 — Mediciones ya enviadas

**Dado** que existen mediciones ya enviadas,

**Cuando** guardo un ajuste de empresa,

**Entonces** las mediciones ya enviadas no cambian.

### Reglas de negocio

- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Quitar el ajuste para que la empresa vuelva al prompt de su sector.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-041 — Editar las instrucciones generales de la IA.
- Depende de HU-042 — Ajustar el prompt para un sector.

### Requisitos relacionados

- RF-021

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-041 y HU-042 (prompt general y ajuste del sector).

---

## HU-044 — Ver el prompt completo

**Épica:** EP-007 — Configuración de la IA

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: ayuda a verificar la configuración de la IA pero no es indispensable para operar.

**Story Points:** 3 — Modal de lectura con varias capas, marcas de color y copia de texto, sin guardar datos.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A4.4

### Historia de Usuario

> Como administrador, quiero leer el prompt final como lo recibe la IA con las preguntas y respuestas de una empresa, para revisar que se entienda.

### Descripción

Modal con una empresa y categoría de ejemplo. Muestra todas las capas del prompt.

### Valor de negocio

Permite al Administrador revisar que el prompt final se entienda tal como lo recibe la IA antes de confiar en sus resultados.

### Caso de éxito

1. Pulso “Ver prompt completo”.
2. Elijo una empresa y una categoría de ejemplo.
3. Leo el prompt en “Lectura” o en “Texto exacto”.
4. Opcionalmente copio el texto exacto.

### Criterios de aceptación

#### CA-001 — Elegir ejemplo

**Dado** que abrí “Ver prompt completo”,

**Cuando** elijo una empresa y una categoría de ejemplo,

**Entonces** veo el prompt final con todas las capas.

#### CA-002 — Datos automáticos

**Dado** que estoy leyendo el prompt,

**Cuando** el prompt contiene datos automáticos,

**Entonces** se ven reemplazados y subrayados.

#### CA-003 — Marcas de color

**Dado** que estoy leyendo el prompt,

**Cuando** una parte viene del sector, de la empresa o de una respuesta,

**Entonces** se marca con color y el texto general va sin color.

#### CA-004 — Respuestas de la empresa

**Dado** que el prompt incluye preguntas con respuestas,

**Cuando** leo esa capa,

**Entonces** las respuestas se ven como citas e indican si se califican por puntaje o las califica la IA.

#### CA-005 — Copiar texto exacto

**Dado** que estoy en la vista “Texto exacto”,

**Cuando** pulso “Copiar texto exacto”,

**Entonces** se copia el texto literal del prompt.

### Reglas de negocio

- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Alternar entre la vista “Lectura” y “Texto exacto”.
- Copiar el texto exacto es opcional.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-041 — Editar las instrucciones generales de la IA.

### Requisitos relacionados

- RF-022

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-041 (prompt general).

---

## HU-045 — Probar el prompt con un ejemplo

**Épica:** EP-007 — Configuración de la IA

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: reduce el riesgo al editar el prompt pero no bloquea el uso del sistema.

**Story Points:** 5 — Incluye una llamada a la IA con resultado completo sin guardar, manejo de error y aviso de consumo.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: definir el límite de pruebas por día.

**Vistas del prototipo:** A4.7

### Historia de Usuario

> Como administrador, quiero probar el prompt con un caso real sin guardar, para ver el resultado antes de cambiarlo para todos.

### Descripción

Una sola prueba, por categoría, que muestra lo que devolvería la IA.

> REQUIERE VALIDACIÓN: límite de pruebas por día.

### Valor de negocio

Permite ver el resultado que daría la IA con un caso real antes de cambiar el prompt para todos.

### Caso de éxito

1. Pulso “Probar con un ejemplo”.
2. Elijo la empresa y la categoría.
3. Veo observación por pregunta, puntaje de las abiertas, observación de la categoría y recomendación.

### Criterios de aceptación

#### CA-001 — Probar con un ejemplo

**Dado** que abrí “Probar con un ejemplo”,

**Cuando** elijo la empresa y la categoría,

**Entonces** veo observación por pregunta, puntaje de las abiertas, observación de la categoría y recomendación.

#### CA-002 — Prueba única

**Dado** que estoy en la pantalla de prueba,

**Cuando** reviso las opciones,

**Entonces** hay una única prueba para “Analizar categoría”.

#### CA-003 — No guarda nada

**Dado** que ejecuté una prueba,

**Cuando** termina la prueba,

**Entonces** no se guarda nada ni se afectan resultados.

#### CA-004 — Aviso de consumo

**Dado** que voy a ejecutar la prueba,

**Cuando** veo la pantalla,

**Entonces** se avisa que consume una llamada a la IA.

#### CA-005 — Falla de la IA

**Dado** que ejecuté una prueba,

**Cuando** la IA falla,

**Entonces** se muestra el error y no se guarda nada.

### Reglas de negocio

- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Si la IA falla, se muestra el error y no se guarda nada.

### Dependencias

- Depende de HU-041 — Editar las instrucciones generales de la IA.

### Requisitos relacionados

- RF-022

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- REQUIERE VALIDACIÓN: límite de pruebas por día.
- Depende de HU-041 y de la IA.

---

## HU-080 — Entender cómo se arma el prompt final

**Épica:** EP-007 — Configuración de la IA

**Actor:** Administrador

**Prioridad:** Baja — Es Baja porque es informativa y no bloquea la configuración.

**Story Points:** 2 — Pantalla informativa de solo lectura.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A4.9

### Historia de Usuario

> Como administrador, quiero ver un esquema de cómo se arma el prompt final, para entender de dónde sale cada parte antes de editar.

### Descripción

Pantalla informativa (“Cómo se arma el prompt”) con las capas y la regla de lo más específico.

### Valor de negocio

Ayuda al Administrador a entender de dónde sale cada parte del prompt antes de editarlo.

### Caso de éxito

1. Abro “¿Cómo se arma?” desde Configuración IA.
2. Leo el esquema de capas.
3. Vuelvo a la configuración.

### Criterios de aceptación

#### CA-001 — Capas del prompt

**Dado** que soy Administrador y abro Cómo se arma desde Configuración IA,

**Cuando** se muestra la pantalla,

**Entonces** veo las capas: general, sector, empresa, preguntas con respuestas y Ten en cuenta, y formato fijo.

#### CA-002 — Qué aporta cada capa

**Dado** que veo el esquema,

**Cuando** leo cada capa,

**Entonces** se explica qué aporta.

#### CA-003 — Solo lectura

**Dado** que estoy en la pantalla,

**Cuando** la reviso,

**Entonces** no puedo editar nada.

#### CA-004 — Volver

**Dado** que terminé de leer,

**Cuando** vuelvo,

**Entonces** regreso a la configuración.

### Reglas de negocio

- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Vuelvo a la configuración.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-041 — Editar las instrucciones generales de la IA.

### Requisitos relacionados

- RF-020

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-041.

---

## EP-008 — Usuarios y roles

## HU-046 — Ver las cuentas del sistema

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es la base de la gestión de accesos, aunque no genera valor directo al cliente final.

**Story Points:** 3 — Pantalla de lectura con búsqueda y filtros por rol y estado.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5

### Historia de Usuario

> Como administrador, quiero ver todas las cuentas con su rol y estado, para saber quién tiene acceso.

### Descripción

Lista de cuentas con búsqueda y filtros.

### Valor de negocio

Da al Administrador visibilidad de quién tiene acceso al sistema, con qué rol y en qué estado.

### Caso de éxito

1. Entro a “Usuarios y roles”.
2. Busco o filtro por rol y estado.
3. Reviso nombre, correo, rol, empresa asociada, estado y último acceso.

### Criterios de aceptación

#### CA-001 — Ver la lista

**Dado** que soy Administrador,

**Cuando** entro a “Usuarios y roles”,

**Entonces** veo nombre, correo, rol, empresa asociada, estado y último acceso de cada cuenta.

#### CA-002 — Buscar

**Dado** que estoy en la lista de cuentas,

**Cuando** escribo en la búsqueda,

**Entonces** la lista muestra solo las cuentas que coinciden.

#### CA-003 — Filtrar

**Dado** que estoy en la lista de cuentas,

**Cuando** filtro por rol y estado,

**Entonces** la lista muestra solo las cuentas con ese rol y estado.

#### CA-004 — Mi propia cuenta

**Dado** que veo mi fila,

**Cuando** reviso las acciones,

**Entonces** dice “Tu cuenta · Mi perfil” en lugar de “Desactivar”.

#### CA-005 — Sin restablecer contraseña

**Dado** que reviso las acciones de una cuenta,

**Cuando** busco restablecer contraseña,

**Entonces** no existe esa acción.

### Reglas de negocio

- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-002 — Registrar mi empresa.
- Depende de HU-032 — Registrar una empresa.

### Requisitos relacionados

- RF-023

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-002 y HU-032 (cuentas de empresa).

---

## HU-047 — Desactivar o reactivar una cuenta

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es necesario para controlar el acceso, pero no bloquea el flujo principal.

**Story Points:** 3 — Modal de confirmación con explicación del efecto y acción inversa de reactivar.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.3b

### Historia de Usuario

> Como administrador, quiero desactivar la cuenta de una persona, para quitarle el acceso sin borrar su información.

### Descripción

Modal de confirmación.

### Valor de negocio

Permite quitar el acceso a una persona sin perder su información y devolvérselo después si hace falta.

### Caso de éxito

1. Pulso “Desactivar” en una cuenta.
2. Leo qué pasa con la empresa si es su única cuenta.
3. Confirmo.
4. Para volver a darle acceso uso “Reactivar”.

### Criterios de aceptación

#### CA-001 — Desactivar con confirmación

**Dado** que estoy en la lista de cuentas,

**Cuando** pulso “Desactivar” y confirmo,

**Entonces** la cuenta queda desactivada y su información no se borra.

#### CA-002 — Efecto sobre la empresa

**Dado** que pulsé “Desactivar” en una cuenta,

**Cuando** se abre el modal,

**Entonces** explica qué pasa con la empresa si es su única cuenta.

#### CA-003 — Sin acceso

**Dado** que una cuenta está desactivada,

**Cuando** la persona intenta iniciar sesión,

**Entonces** no puede entrar.

#### CA-004 — Reactivar

**Dado** que una cuenta está desactivada,

**Cuando** pulso “Reactivar” desde la lista,

**Entonces** la cuenta vuelve a tener acceso.

#### CA-005 — Mi propia cuenta

**Dado** que veo mi propia cuenta,

**Cuando** reviso las acciones,

**Entonces** no existe la acción de desactivar.

### Reglas de negocio

- RN-004 — Una cuenta desactivada no puede iniciar sesión y su información no se borra; se puede reactivar.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Reactivar la cuenta para devolver el acceso.

### Casos de error

- No se puede desactivar la propia cuenta.

### Dependencias

- Depende de HU-046 — Ver las cuentas del sistema.

### Requisitos relacionados

- RF-023

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-046.

---

## HU-048 — Ver los roles del sistema

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: aclara los permisos para administrar el acceso, pero es solo de consulta.

**Story Points:** 3 — Pantallas de lectura de tres roles con permisos fijos y cuentas asociadas.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.1, A5.1b, A5.1e

### Historia de Usuario

> Como administrador, quiero ver los permisos de los roles del sistema, para saber qué puede hacer cada uno.

### Descripción

Roles con permisos fijos: Administrador, Empresa y Colaborador.

### Valor de negocio

Permite saber qué puede hacer cada rol del sistema y quién lo tiene.

### Caso de éxito

1. Entro a “Roles”.
2. Elijo un rol.
3. Veo sus permisos y las cuentas que lo tienen.

### Criterios de aceptación

#### CA-001 — Ver los roles

**Dado** que entré a “Roles”,

**Cuando** reviso la lista,

**Entonces** veo Administrador, Empresa y Colaborador.

#### CA-002 — Permisos fijos

**Dado** que elegí un rol del sistema,

**Cuando** veo sus permisos,

**Entonces** son fijos y no se pueden editar ni eliminar.

#### CA-003 — Cuentas del rol

**Dado** que elegí un rol,

**Cuando** reviso el detalle,

**Entonces** veo las cuentas que lo tienen.

#### CA-004 — Rol Colaborador

**Dado** que elegí el rol Colaborador,

**Cuando** veo el detalle,

**Entonces** muestra sus permisos de empresa y lo que no puede hacer.

### Reglas de negocio

- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.
- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.
- RN-027 — Los roles del sistema (Administrador, Empresa, Colaborador) son fijos; cada cuenta tiene un solo rol; solo se elimina un rol sin cuentas.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-046 — Ver las cuentas del sistema.
- Depende de HU-076 — Ver los colaboradores de mi empresa.

### Requisitos relacionados

- RF-024

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-046 y HU-076.

---

## HU-049 — Crear un rol

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Baja — Prioridad Baja: los roles del sistema cubren la operación actual.

**Story Points:** 5 — Formulario con permisos agrupados en 6 bloques, asignación opcional a cuentas y validaciones.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: confirmar si “Consultor” es un rol creable o solo a futuro.

**Vistas del prototipo:** A5.2

### Historia de Usuario

> Como administrador, quiero crear un rol nuevo con los permisos que elija, para dar acceso a otras personas sin cambiar el código.

### Descripción

Modal para crear un rol con permisos agrupados.

> REQUIERE VALIDACIÓN: el prototipo muestra un ejemplo de rol “Consultor”; confirmar si se mantiene como rol creable o solo a futuro.

### Valor de negocio

Permite dar acceso a otras personas con permisos a la medida sin depender de cambios en el código.

### Caso de éxito

1. Pulso “+ Crear rol”.
2. Escribo nombre y descripción, y marco los permisos.
3. Opcionalmente lo asigno a cuentas existentes.
4. Pulso “Crear rol”.

### Criterios de aceptación

#### CA-001 — Crear un rol

**Dado** que pulsé “+ Crear rol”,

**Cuando** escribo el nombre, marco permisos y pulso “Crear rol”,

**Entonces** el rol se crea y se abre el rol nuevo.

#### CA-002 — Datos que pide

**Dado** que estoy en el modal de crear rol,

**Cuando** reviso el formulario,

**Entonces** pide nombre, descripción opcional, si está activo y permisos en 6 bloques con contador de 15.

#### CA-003 — Asignar a cuentas

**Dado** que estoy creando un rol,

**Cuando** lo asigno a cuentas existentes,

**Entonces** esas cuentas pasan a tener ese rol en lugar del actual.

#### CA-004 — Nombre vacío o repetido

**Dado** que estoy creando un rol,

**Cuando** dejo el nombre vacío o repetido,

**Entonces** se avisa y el rol no se crea.

### Reglas de negocio

- RN-027 — Los roles del sistema (Administrador, Empresa, Colaborador) son fijos; cada cuenta tiene un solo rol; solo se elimina un rol sin cuentas.

### Validaciones

- El nombre es obligatorio.
- El nombre no puede repetirse.
- La descripción es opcional.

### Casos alternativos

- Asignar el rol a cuentas existentes es opcional.

### Casos de error

- Nombre vacío o repetido: se avisa.

### Dependencias

- Depende de HU-048 — Ver los roles del sistema.

### Requisitos relacionados

- RF-024

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- REQUIERE VALIDACIÓN: confirmar si el rol “Consultor” del prototipo se mantiene como rol creable o solo a futuro.
- Depende de HU-048.

---

## HU-050 — Editar un rol

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Baja — Prioridad Baja: depende de que existan roles creados, que es una función opcional.

**Story Points:** 3 — Formulario de casillas con efecto sobre todas las cuentas del rol.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.1c

### Historia de Usuario

> Como administrador, quiero cambiar los permisos de un rol creado, para ajustar lo que puede hacer.

### Descripción

Solo roles creados; los del sistema no se editan.

### Valor de negocio

Permite ajustar lo que puede hacer un rol creado y que el cambio llegue a todas sus cuentas.

### Caso de éxito

1. Abro un rol creado.
2. Cambio los permisos.
3. Pulso “Guardar rol”.

### Criterios de aceptación

#### CA-001 — Cambiar permisos

**Dado** que abrí un rol creado,

**Cuando** marco o desmarco permisos en las casillas,

**Entonces** los cambios quedan listos para guardar.

#### CA-002 — Guardar rol

**Dado** que cambié los permisos,

**Cuando** pulso “Guardar rol”,

**Entonces** los cambios aplican a todas las cuentas con ese rol.

#### CA-003 — Rol inactivo

**Dado** que un rol está inactivo,

**Cuando** intento asignarlo a una cuenta,

**Entonces** no se puede asignar.

#### CA-004 — Rol del sistema

**Dado** que el rol es del sistema,

**Cuando** intento editarlo,

**Entonces** no se puede editar.

### Reglas de negocio

- RN-027 — Los roles del sistema (Administrador, Empresa, Colaborador) son fijos; cada cuenta tiene un solo rol; solo se elimina un rol sin cuentas.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Un rol inactivo no se puede asignar.

### Dependencias

- Depende de HU-049 — Crear un rol.

### Requisitos relacionados

- RF-024

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-049.

---

## HU-051 — Eliminar un rol

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Baja — Prioridad Baja: es de mantenimiento y no afecta la operación.

**Story Points:** 2 — Acción simple con confirmación y una condición de bloqueo.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.1d

### Historia de Usuario

> Como administrador, quiero eliminar un rol que ya no se usa, para mantener la lista limpia.

### Descripción

Solo roles creados y sin cuentas.

### Valor de negocio

Mantiene limpia la lista de roles eliminando los que ya no se usan.

### Caso de éxito

1. Pulso “Eliminar rol”.
2. Confirmo que no se puede deshacer.

### Criterios de aceptación

#### CA-001 — Eliminar un rol sin cuentas

**Dado** que un rol creado no tiene cuentas,

**Cuando** pulso “Eliminar rol” y confirmo,

**Entonces** el rol se elimina.

#### CA-002 — Confirmación

**Dado** que pulsé “Eliminar rol”,

**Cuando** se abre la confirmación,

**Entonces** se indica que no se puede deshacer.

#### CA-003 — Rol con cuentas

**Dado** que un rol tiene cuentas,

**Cuando** intento eliminarlo,

**Entonces** no se puede eliminar.

#### CA-004 — Rol del sistema

**Dado** que el rol es del sistema,

**Cuando** intento eliminarlo,

**Entonces** no se puede eliminar.

### Reglas de negocio

- RN-027 — Los roles del sistema (Administrador, Empresa, Colaborador) son fijos; cada cuenta tiene un solo rol; solo se elimina un rol sin cuentas.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Si el rol tiene cuentas, no se puede eliminar.

### Dependencias

- Depende de HU-049 — Crear un rol.

### Requisitos relacionados

- RF-024

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-049.

---

## HU-052 — Asignar o quitar un rol

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: es la forma de aplicar los roles a las cuentas.

**Story Points:** 5 — Flujo con varias reglas: reemplazo de rol, aviso por empresa sin usuario principal y aviso opcional por correo.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.5, A5.1

### Historia de Usuario

> Como administrador, quiero darle un rol a una cuenta existente o quitárselo, para cambiar lo que esa persona puede hacer.

### Descripción

Asignar reemplaza el rol actual.

### Valor de negocio

Permite cambiar lo que una persona puede hacer dándole o quitándole un rol de forma controlada.

### Caso de éxito

1. Pulso “Asignar rol”.
2. Elijo la cuenta; veo su rol actual y que será reemplazado.
3. Opcionalmente aviso por correo.
4. Confirmo.

### Criterios de aceptación

#### CA-001 — Ver rol actual

**Dado** que pulsé “Asignar rol”,

**Cuando** elijo la cuenta,

**Entonces** veo su rol actual y que será reemplazado.

#### CA-002 — Asignar con confirmación

**Dado** que elegí cuenta y rol,

**Cuando** confirmo,

**Entonces** el nuevo rol reemplaza al actual.

#### CA-003 — Avisar por correo

**Dado** que estoy asignando un rol,

**Cuando** marco la opción de avisar por correo,

**Entonces** se avisa a la persona por correo.

#### CA-004 — Empresa sin usuario principal

**Dado** que la cuenta es de una empresa,

**Cuando** la paso a otro rol,

**Entonces** se avisa que la empresa se queda sin usuario principal.

#### CA-005 — Quitar rol

**Dado** que veo la lista de cuentas de un rol,

**Cuando** uso “Quitar rol” en una cuenta,

**Entonces** se le quita el rol a esa cuenta.

### Reglas de negocio

- RN-027 — Los roles del sistema (Administrador, Empresa, Colaborador) son fijos; cada cuenta tiene un solo rol; solo se elimina un rol sin cuentas.

### Validaciones

- Ninguna específica.

### Casos alternativos

- El aviso por correo es opcional.

### Casos de error

- Si una cuenta de empresa pasa a otro rol, se avisa que la empresa se queda sin usuario principal.

### Dependencias

- Depende de HU-046 — Ver las cuentas del sistema.

### Requisitos relacionados

- RF-024

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)
- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-046.

---

## HU-053 — Ver el sistema como una empresa

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Media — Prioridad Media: mejora el soporte a las empresas, pero no es parte del flujo central.

**Story Points:** 5 — Modo de solo lectura que replica la experiencia de la empresa, con franja de aviso y registro de uso.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.4

### Historia de Usuario

> Como administrador, quiero ver el sistema como lo ve una empresa, para ayudarla cuando tiene dudas.

### Descripción

Modo de solo lectura con franja de aviso.

### Valor de negocio

Permite al Administrador ayudar a una empresa viendo exactamente lo que ella ve, sin riesgo de cambiar sus datos.

### Caso de éxito

1. Pulso “Ver como esta empresa”.
2. Veo el sistema como ella, con la franja “Estás viendo el sistema como …”.
3. Pulso “Salir de esta vista”.

### Criterios de aceptación

#### CA-001 — Entrar a ver como empresa

**Dado** que estoy en la ficha de una empresa,

**Cuando** pulso “Ver como esta empresa”,

**Entonces** veo el sistema como ella con la franja “Estás viendo el sistema como …”.

#### CA-002 — Solo lectura

**Dado** que estoy viendo como la empresa,

**Cuando** intento enviar respuestas o cambiar datos,

**Entonces** no se puede.

#### CA-003 — Salir de la vista

**Dado** que estoy viendo como la empresa,

**Cuando** pulso “Salir de esta vista”,

**Entonces** vuelvo a Usuarios (A5).

#### CA-004 — Registro

**Dado** que usé “Ver como”,

**Cuando** entro a esa vista,

**Entonces** queda registrado quién lo usó y cuándo.

### Reglas de negocio

- RN-026 — “Ver como” es solo de lectura y queda registrado quién lo usó y cuándo.
- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Salir de la vista con “Salir de esta vista”.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-046 — Ver las cuentas del sistema.

### Requisitos relacionados

- RF-025

### Requisitos no funcionales relacionados

- RNF-005 (Auditoría)
- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-046.

---

## HU-054 — Ver el sistema como otro administrador

**Épica:** EP-008 — Usuarios y roles

**Actor:** Administrador

**Prioridad:** Baja — Prioridad Baja: es una variante de apoyo a HU-053 para revisión de permisos.

**Story Points:** 3 — Reutiliza el modo de solo lectura de HU-053 con el menú de cada rol.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A5.4b

### Historia de Usuario

> Como administrador, quiero ver el sistema como lo ve otra cuenta con otro rol, para revisar que sus permisos sean correctos.

### Descripción

Igual que HU-053 pero con el menú del otro rol.

### Valor de negocio

Permite revisar que los permisos de otro rol sean correctos viendo su menú tal como lo ve esa cuenta.

### Caso de éxito

1. Elijo “Ver como” en una cuenta.
2. Veo el inicio con el menú de su rol.
3. Salgo con “Salir de esta vista”.

### Criterios de aceptación

#### CA-001 — Entrar a ver como otra cuenta

**Dado** que estoy en la lista de cuentas,

**Cuando** elijo “Ver como” en una cuenta,

**Entonces** veo el inicio con el menú de su rol.

#### CA-002 — Franja de aviso

**Dado** que estoy viendo como otra cuenta,

**Cuando** reviso la pantalla,

**Entonces** aparece la misma franja de aviso.

#### CA-003 — Solo lectura

**Dado** que estoy viendo como otra cuenta,

**Cuando** intento cambiar datos,

**Entonces** no se puede.

#### CA-004 — Salir

**Dado** que estoy viendo como otra cuenta,

**Cuando** pulso “Salir de esta vista”,

**Entonces** salgo de esa vista.

### Reglas de negocio

- RN-026 — “Ver como” es solo de lectura y queda registrado quién lo usó y cuándo.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Salir de la vista con “Salir de esta vista”.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-053 — Ver el sistema como una empresa.

### Requisitos relacionados

- RF-025

### Requisitos no funcionales relacionados

- RNF-005 (Auditoría)
- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-053.

---

## EP-009 — Mi perfil

## HU-055 — Editar mi perfil (Administrador)

**Épica:** EP-009 — Mi perfil

**Actor:** Administrador

**Prioridad:** Baja — Prioridad Baja: es de mantenimiento personal y no bloquea otras funciones.

**Story Points:** 3 — Formulario con varios campos, avisos y cambio de contraseña con regla fuerte.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A6

### Historia de Usuario

> Como administrador, quiero editar mis datos, avisos y seguridad, para mantener mi cuenta al día.

### Descripción

Pantalla de perfil del Administrador.

### Valor de negocio

Permite al Administrador mantener sus datos, avisos y contraseña al día y su cuenta segura.

### Caso de éxito

1. Abro “Mi perfil”.
2. Cambio mis datos, mis avisos o mi contraseña.
3. Pulso “Guardar cambios”.

### Criterios de aceptación

#### CA-001 — Cambiar mis datos

**Dado** que abrí “Mi perfil”,

**Cuando** cambio nombre, teléfono, ciudad, país, zona horaria o foto y pulso “Guardar cambios”,

**Entonces** los datos quedan guardados.

#### CA-002 — Elegir avisos

**Dado** que estoy en “Mi perfil”,

**Cuando** elijo qué avisos recibo y guardo,

**Entonces** mis avisos quedan según lo elegido.

#### CA-003 — Cambiar contraseña

**Dado** que pulsé “Cambiar contraseña”,

**Cuando** ingreso la actual y una nueva que cumple la regla,

**Entonces** la contraseña se cambia.

#### CA-004 — Descartar cambios

**Dado** que modifiqué datos,

**Cuando** pulso “Descartar cambios”,

**Entonces** no se guarda nada.

#### CA-005 — Contraseña incorrecta o débil

**Dado** que estoy cambiando la contraseña,

**Cuando** la actual es incorrecta o la nueva no cumple la regla,

**Entonces** se avisa y no se cambia.

### Reglas de negocio

- RN-001 — Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.

### Validaciones

- Contraseña nueva: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- La contraseña actual debe ser correcta.

### Casos alternativos

- “Descartar cambios” no guarda nada.

### Casos de error

- Contraseña actual incorrecta: se avisa.
- Contraseña nueva que no cumple la regla: se avisa.

### Dependencias

- Depende de HU-001 — Iniciar sesión.

### Requisitos relacionados

- RF-026

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)

### Riesgos

- Depende de HU-001.

---

## HU-056 — Editar mi perfil (Empresa o Colaborador)

**Épica:** EP-009 — Mi perfil

**Actor:** Empresa o Colaborador

**Prioridad:** Baja — Prioridad Baja: es de mantenimiento personal y no bloquea el diagnóstico.

**Story Points:** 5 — Formulario con permisos distintos según empresa o colaborador, sector bloqueado, avisos y contraseña fuerte.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E11

### Historia de Usuario

> Como empresa o colaborador, quiero editar mis datos y mi contraseña, para que estén correctos en mis informes y mantener mi acceso seguro.

### Descripción

La empresa edita sus datos y los de la empresa; el colaborador edita solo sus propios datos y su contraseña.

### Valor de negocio

Permite que los datos de la empresa o del colaborador estén correctos en los informes y que su acceso se mantenga seguro.

### Caso de éxito

1. Abro “Mi perfil”.
2. Cambio mis datos, avisos o contraseña.
3. Guardo.

### Criterios de aceptación

#### CA-001 — Empresa edita sus datos

**Dado** que soy usuario de empresa en “Mi perfil”,

**Cuando** cambio los datos de mi usuario, de la empresa, ciudad, país o logo y guardo,

**Entonces** los datos quedan guardados.

#### CA-002 — Sector no editable

**Dado** que soy empresa en “Mi perfil”,

**Cuando** reviso el sector,

**Entonces** no se puede cambiar.

#### CA-003 — Colaborador limitado

**Dado** que soy colaborador en “Mi perfil”,

**Cuando** abro mi perfil,

**Entonces** solo veo y edito mis datos personales y mi contraseña.

#### CA-004 — Cambiar contraseña y avisos

**Dado** que estoy en “Mi perfil”,

**Cuando** cambio mi contraseña con una que cumple la regla y elijo mis avisos,

**Entonces** los cambios se guardan.

#### CA-005 — Contraseña incorrecta o débil

**Dado** que estoy cambiando la contraseña,

**Cuando** la actual es incorrecta o la nueva no cumple la regla,

**Entonces** se avisa y no se cambia.

### Reglas de negocio

- RN-001 — Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.
- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.

### Validaciones

- Contraseña nueva: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- La contraseña actual debe ser correcta.

### Casos alternativos

- Ninguno.

### Casos de error

- Contraseña actual incorrecta: se avisa.
- Contraseña nueva que no cumple la regla: se avisa.

### Dependencias

- Depende de HU-001 — Iniciar sesión.
- Depende de HU-077 — Crear un colaborador.

### Requisitos relacionados

- RF-026

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)
- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-001 y HU-077.

---

## EP-010 — Experiencia de la empresa

## HU-057 — Ver mi inicio la primera vez

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Es la primera pantalla que ve toda empresa nueva y condiciona el arranque del diagnóstico.

**Story Points:** 2 — Pantalla informativa de lectura con un botón y menú.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E1

### Historia de Usuario

> Como empresa o colaborador, quiero ver qué debo hacer cuando entro por primera vez, para empezar el primer diagnóstico.

### Descripción

Inicio de una empresa sin mediciones respondidas.

### Valor de negocio

Orienta a la empresa nueva sobre qué hacer para empezar su primer diagnóstico.

### Caso de éxito

1. Entro por primera vez.
2. Veo la tarjeta “Tu primer diagnóstico” y “Cómo funciona” en 3 pasos.
3. Pulso “Iniciar diagnóstico”.

### Criterios de aceptación

#### CA-001 — Tarjeta de primer diagnóstico

**Dado** que entro por primera vez sin mediciones respondidas,

**Cuando** abro mi inicio,

**Entonces** veo categorías, preguntas, tiempo aproximado y el botón “Iniciar diagnóstico”.

#### CA-002 — Cómo funciona

**Dado** que estoy en mi inicio de primera vez,

**Cuando** reviso la pantalla,

**Entonces** veo “Cómo funciona” en 3 pasos.

#### CA-003 — Menú de usuario principal

**Dado** que soy el usuario principal,

**Cuando** miro el menú,

**Entonces** veo Inicio, Mi historial, Colaboradores, mi empresa (perfil) y Cerrar sesión.

#### CA-004 — Menú de colaborador

**Dado** que soy colaborador,

**Cuando** miro el menú,

**Entonces** no veo la opción Colaboradores.

### Reglas de negocio

- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-001 — Iniciar sesión.
- Depende de HU-002 — Registrar mi empresa.

### Requisitos relacionados

- RF-027

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-001 y HU-002.

---

## HU-058 — Ver mi medición pendiente

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Es el punto de entrada para responder el diagnóstico asignado.

**Story Points:** 3 — Pantalla de lectura con varios datos y accesos a otras pantallas.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E2

### Historia de Usuario

> Como empresa o colaborador, quiero ver la medición asignada con su mensaje y fecha límite, para responderla a tiempo.

### Descripción

Inicio con una medición pendiente.

### Valor de negocio

Permite responder a tiempo la medición asignada conociendo el mensaje y la fecha límite.

### Caso de éxito

1. Entro a mi inicio.
2. Leo el mensaje y la fecha límite.
3. Pulso “Iniciar diagnóstico” o “Continuar”.

### Criterios de aceptación

#### CA-001 — Ver medición pendiente

**Dado** que tengo una medición pendiente,

**Cuando** entro a mi inicio,

**Entonces** veo el número de medición, el mensaje del administrador, la fecha límite, categorías, preguntas y que puedo pausar.

#### CA-002 — Iniciar o continuar

**Dado** que tengo una medición pendiente,

**Cuando** pulso “Iniciar diagnóstico” o “Continuar”,

**Entonces** llego a la pantalla de responder el diagnóstico.

#### CA-003 — Resultado más reciente

**Dado** que tengo una medición terminada anterior,

**Cuando** reviso mi inicio,

**Entonces** veo mi resultado más reciente con “Ver resultado completo”.

#### CA-004 — Medición vencida

**Dado** que mi medición venció,

**Cuando** entro a mi inicio,

**Entonces** no puedo responderla y veo cómo pedir una nueva.

### Reglas de negocio

- RN-014 — Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa.
- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.
- RN-016 — Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Si la medición venció, no se puede responder y se indica cómo pedir una nueva.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-036 — Asignar una medición.

### Requisitos relacionados

- RF-027

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-036 (asignación) y HU-082 (solicitud de nueva medición).

---

## HU-059 — Ver mi inicio sin medición pendiente

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Media — Mantiene a la empresa informada entre mediciones, aunque no bloquea el diagnóstico.

**Story Points:** 3 — Pantalla de lectura con dos variantes según el número de mediciones.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E7, E7b

### Historia de Usuario

> Como empresa o colaborador, quiero ver mi último resultado cuando no tengo nada pendiente, para saber cómo voy.

### Descripción

Inicio sin pendientes, con o sin variación según el número de mediciones.

### Valor de negocio

Muestra a la empresa cómo va su último resultado cuando no tiene nada pendiente.

### Caso de éxito

1. Entro a mi inicio.
2. Veo que no tengo diagnósticos pendientes y mi último resultado.
3. Voy al resultado, al historial o al PDF.

### Criterios de aceptación

#### CA-001 — Sin pendientes

**Dado** que no tengo medición pendiente,

**Cuando** entro a mi inicio,

**Entonces** veo que no tengo diagnósticos pendientes y mi último resultado.

#### CA-002 — Con variación

**Dado** que tengo 2 o más mediciones,

**Cuando** miro mi inicio,

**Entonces** veo la variación y la lista de mediciones.

#### CA-003 — Una sola medición

**Dado** que tengo solo 1 medición,

**Cuando** miro mi inicio,

**Entonces** no veo variación.

#### CA-004 — Accesos

**Dado** que estoy en mi inicio sin pendientes,

**Cuando** uso los accesos,

**Entonces** llego al resultado, al historial y al PDF.

### Reglas de negocio

- RN-020 — La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Con una sola medición no se muestra variación.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-064 — Ver mi resultado.
- Depende de HU-067 — Ver mi historial.

### Requisitos relacionados

- RF-027

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-064 y HU-067.

---

## HU-060 — Responder el diagnóstico

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Es la acción central de la empresa: sin respuestas no hay resultado.

**Story Points:** 8 — Pantalla con varios tipos de pregunta, autoguardado, avance, navegación entre categorías y reanudación.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E3

### Historia de Usuario

> Como empresa o colaborador, quiero responder las preguntas por categorías y que se guarde solo, para hacerlo a mi ritmo sin perder lo que escribí.

### Descripción

Pantalla de respuestas con avance y autoguardado.

### Valor de negocio

Permite responder el diagnóstico a su ritmo sin perder lo escrito.

### Caso de éxito

1. Pulso “Iniciar diagnóstico”.
2. Respondo las preguntas de una categoría.
3. Me muevo entre categorías.
4. Pulso “Guardar y salir” o sigo hasta “Revisar antes de enviar”.

### Criterios de aceptación

#### CA-001 — Estado de categorías

**Dado** que estoy respondiendo mi diagnóstico,

**Cuando** miro el panel izquierdo,

**Entonces** veo cada categoría como completa, actual o pendiente.

#### CA-002 — Indicación por pregunta

**Dado** que estoy en una categoría,

**Cuando** leo una pregunta,

**Entonces** veo “Cómo responder” y su tipo.

#### CA-003 — Autoguardado

**Dado** que respondo una pregunta,

**Cuando** termino de responder,

**Entonces** la respuesta se guarda al momento y veo “Guardado automáticamente”.

#### CA-004 — Guardar y salir

**Dado** que respondí parte del diagnóstico,

**Cuando** pulso “Guardar y salir”,

**Entonces** vuelvo al inicio y al regresar sigo donde iba.

#### CA-005 — Retomar

**Dado** que respondí 3 categorías y salí con “Guardar y salir”,

**Cuando** vuelvo a entrar y pulso “Continuar”,

**Entonces** veo mis respuestas guardadas y sigo en la categoría donde iba.

#### CA-006 — Revisar antes de enviar

**Dado** que estoy respondiendo mi diagnóstico,

**Cuando** pulso “Revisar antes de enviar”,

**Entonces** llego a la pantalla de revisión.

### Reglas de negocio

- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.
- RN-016 — Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra.

### Validaciones

- Texto abierto: máximo 1000 caracteres.

### Casos alternativos

- Si salgo, al volver continúo donde iba.

### Casos de error

- Una medición vencida no se puede responder.

### Dependencias

- Depende de HU-058 — Ver mi medición pendiente.

### Requisitos relacionados

- RF-028

### Requisitos no funcionales relacionados

- RNF-003 (Usabilidad)

### Riesgos

- Depende de HU-058.

---

## HU-061 — Revisar antes de enviar

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Protege la calidad del resultado al exigir que todo esté respondido.

**Story Points:** 3 — Pantalla de revisión con estados y botón que se activa según condición.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E4, E4b

### Historia de Usuario

> Como empresa o colaborador, quiero ver qué categorías tengo completas y cuáles no, para no enviar algo incompleto.

### Descripción

Pantalla de revisión.

### Valor de negocio

Evita enviar un diagnóstico incompleto al mostrar qué falta.

### Caso de éxito

1. Llego a la revisión.
2. Veo el estado de cada categoría y las preguntas que faltan.
3. Pulso “Completar” en una categoría incompleta o “Enviar diagnóstico”.

### Criterios de aceptación

#### CA-001 — Estado por categoría

**Dado** que llego a la revisión,

**Cuando** miro la pantalla,

**Entonces** veo el estado de cada categoría y cuántas preguntas faltan.

#### CA-002 — Con faltantes

**Dado** que hay categorías incompletas,

**Cuando** reviso la pantalla,

**Entonces** “Enviar diagnóstico” está desactivado y cada categoría incompleta tiene “Completar”.

#### CA-003 — Completar categoría

**Dado** que una categoría está incompleta,

**Cuando** pulso “Completar”,

**Entonces** voy a esa categoría para responder lo que falta.

#### CA-004 — Todo completo

**Dado** que todas las categorías están completas,

**Cuando** reviso la pantalla,

**Entonces** puedo pulsar “Enviar diagnóstico”.

### Reglas de negocio

- RN-017 — Después de enviar el diagnóstico no se pueden cambiar las respuestas.

### Validaciones

- Se debe responder todo para enviar.

### Casos alternativos

- Ninguno.

### Casos de error

- Si falta algo, “Enviar diagnóstico” está desactivado.

### Dependencias

- Depende de HU-060 — Responder el diagnóstico.

### Requisitos relacionados

- RF-029

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-060.

---

## HU-062 — Enviar el diagnóstico

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Es el paso que dispara el análisis y la entrega del resultado.

**Story Points:** 3 — Ventana de confirmación con un cambio de estado y el inicio del análisis.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E4c

### Historia de Usuario

> Como empresa o colaborador, quiero confirmar el envío de mi diagnóstico, para recibir mi resultado.

### Descripción

Modal de confirmación; al enviar empieza el análisis.

### Valor de negocio

Da a la empresa certeza de que confirma el envío sabiendo que no podrá cambiar sus respuestas.

### Caso de éxito

1. Pulso “Enviar diagnóstico”.
2. Leo que no podré cambiar las respuestas.
3. Pulso “Sí, enviar diagnóstico”.
4. Paso a la pantalla de espera.

### Criterios de aceptación

#### CA-001 — Aviso de confirmación

**Dado** que todas las categorías están completas,

**Cuando** pulso “Enviar diagnóstico”,

**Entonces** el aviso me dice que no podré cambiar las respuestas.

#### CA-002 — Confirmar envío

**Dado** que veo el aviso de confirmación,

**Cuando** pulso “Sí, enviar diagnóstico”,

**Entonces** paso a la pantalla de espera.

#### CA-003 — Estado enviada

**Dado** que completé todas las categorías,

**Cuando** confirmo el envío,

**Entonces** la medición queda “Enviada” y empieza el análisis.

#### CA-004 — Todavía no

**Dado** que veo el aviso de confirmación,

**Cuando** pulso “Todavía no”,

**Entonces** vuelvo a la revisión.

### Reglas de negocio

- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.
- RN-017 — Después de enviar el diagnóstico no se pueden cambiar las respuestas.
- RN-021 — El análisis de IA es una sola etapa por categoría; si falla, se reintenta solo esa categoría hasta 3 veces y luego se avisa por correo al Administrador.

### Validaciones

- Ninguna específica.

### Casos alternativos

- “Todavía no” vuelve a la revisión.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-061 — Revisar antes de enviar.

### Requisitos relacionados

- RF-029

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-061 y de HU-069 para el análisis.

---

## HU-063 — Esperar el análisis

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Cierra el flujo de envío y evita dudas mientras se genera el resultado.

**Story Points:** 5 — Pantalla de avance automático con dos pasos, correo y manejo de fallos con reintento.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E5

### Historia de Usuario

> Como empresa o colaborador, quiero ver que mi diagnóstico se está analizando, para saber que no tengo que hacer nada más.

### Descripción

Pantalla de avance con dos pasos: puntajes de opción única y múltiple, y análisis por categoría.

### Valor de negocio

Tranquiliza a la empresa mostrando que el análisis avanza y que no debe hacer nada más.

### Caso de éxito

1. Veo el avance del análisis.
2. Puedo esperar o salir al inicio.
3. Cuando termina, paso al resultado o recibo un correo.

### Criterios de aceptación

#### CA-001 — Avance del análisis

**Dado** que mi medición está “Enviada”,

**Cuando** miro la pantalla de espera,

**Entonces** veo el avance del análisis y se actualiza sola.

#### CA-002 — Paso al resultado

**Dado** que el análisis termina mientras estoy en la pantalla,

**Cuando** finaliza,

**Entonces** paso al resultado.

#### CA-003 — Salir y esperar

**Dado** que el análisis sigue en curso,

**Cuando** salgo al inicio,

**Entonces** cuando esté listo me llega un correo.

#### CA-004 — Categoría fallida

**Dado** que alguna categoría no se pudo analizar tras los reintentos,

**Cuando** reviso la pantalla,

**Entonces** veo “No pudimos terminar el análisis de algunas categorías” con la opción de reintentar solo esas.

### Reglas de negocio

- RN-021 — El análisis de IA es una sola etapa por categoría; si falla, se reintenta solo esa categoría hasta 3 veces y luego se avisa por correo al Administrador.
- RN-023 — El resultado se publica directamente, sin revisión del Administrador, y queda guardado e inmutable.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Puedo esperar o salir al inicio y recibir un correo al terminar.

### Casos de error

- Si algunas categorías no se pudieron analizar tras los reintentos, se muestra el aviso; al reintentar solo se repiten esas y el administrador es avisado.

### Dependencias

- Depende de HU-062 — Enviar el diagnóstico.
- Depende de HU-069 — Analizar cada categoría con la IA.

### Requisitos relacionados

- RF-030

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-062 y HU-069.

---

## HU-064 — Ver mi resultado

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Es el valor final que recibe el cliente.

**Story Points:** 8 — Pantalla rica con puntaje, radar, tabla, detalle por categoría, recomendaciones y variación.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E6

### Historia de Usuario

> Como empresa o colaborador, quiero ver mi puntaje, nivel, radar, detalle por categoría y recomendaciones, para saber qué mejorar primero.

### Descripción

Resultado publicado de una medición terminada.

### Valor de negocio

Muestra a la empresa su puntaje, nivel y recomendaciones para saber qué mejorar primero.

### Caso de éxito

1. Abro mi resultado.
2. Veo el puntaje total, el nivel y la variación.
3. Reviso el radar, la tabla y el detalle por categoría.
4. Veo las 3 recomendaciones para las categorías más débiles.

### Criterios de aceptación

#### CA-001 — Puntaje y nivel

**Dado** que mi medición está “Terminada”,

**Cuando** abro mi resultado,

**Entonces** veo el puntaje total de 0 a 100, el nivel y una frase que lo explica.

#### CA-002 — Variación

**Dado** que tengo 2 o más mediciones,

**Cuando** abro mi resultado,

**Entonces** veo la variación.

#### CA-003 — Primera medición

**Dado** que tengo solo 1 medición,

**Cuando** abro mi resultado,

**Entonces** no veo variación.

#### CA-004 — Radar y tabla

**Dado** que abro mi resultado,

**Cuando** reviso la pantalla,

**Entonces** veo el radar y la tabla con importancia, puntaje, nivel y variación.

#### CA-005 — Detalle por categoría

**Dado** que abro el detalle de una categoría,

**Cuando** lo reviso,

**Entonces** veo cada pregunta, mi respuesta y la observación de la IA, más la observación y recomendación de la categoría.

#### CA-006 — Categorías más débiles

**Dado** que abro mi resultado,

**Cuando** reviso las recomendaciones,

**Entonces** se destacan las 3 categorías más débiles, cada una con una recomendación.

### Reglas de negocio

- RN-018 — Puntaje: opción única = puntaje de la opción; selección múltiple = suma con tope 100; abiertas = 0–100 calificadas por la IA; categoría = promedio de sus preguntas; total = promedio ponderado por la importancia.
- RN-019 — Niveles fijos: Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino 60–79, Sigue así 80–100.
- RN-020 — La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición.
- RN-023 — El resultado se publica directamente, sin revisión del Administrador, y queda guardado e inmutable.
- RN-028 — El resultado no incluye análisis FODA ni comparación con promedios.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Puedo ir a “Ver mis respuestas” y “Descargar PDF”.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-084 — Publicar el resultado.
- Depende de HU-068 — Calcular los puntajes.

### Requisitos relacionados

- RF-031

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-084 y HU-068.

---

## HU-065 — Ver mis respuestas

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Media — Aporta transparencia, pero no bloquea el resto del flujo.

**Story Points:** 2 — Pantalla de solo lectura con puntaje por categoría y dos accesos.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E9

### Historia de Usuario

> Como empresa o colaborador, quiero leer lo que respondí en una medición, para recordar en qué basé mi resultado.

### Descripción

Respuestas en solo lectura por categoría.

### Valor de negocio

Permite recordar en qué se basó el resultado al leer las propias respuestas.

### Caso de éxito

1. Abro “Ver mis respuestas”.
2. Reviso cada categoría.
3. Descargo el PDF o vuelvo al resultado.

### Criterios de aceptación

#### CA-001 — Respuestas por categoría

**Dado** que abro “Ver mis respuestas”,

**Cuando** reviso la pantalla,

**Entonces** veo mis respuestas por categoría y en solo lectura.

#### CA-002 — Puntaje por categoría

**Dado** que estoy viendo mis respuestas,

**Cuando** reviso cada categoría,

**Entonces** se muestra el puntaje de cada categoría.

#### CA-003 — Descargar PDF

**Dado** que estoy viendo mis respuestas,

**Cuando** pulso descargar el PDF,

**Entonces** se descarga el PDF.

#### CA-004 — Volver al resultado

**Dado** que estoy viendo mis respuestas,

**Cuando** pulso volver,

**Entonces** regreso al resultado.

### Reglas de negocio

- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.
- RN-017 — Después de enviar el diagnóstico no se pueden cambiar las respuestas.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-064 — Ver mi resultado.

### Requisitos relacionados

- RF-031

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-064.

---

## HU-066 — Descargar el informe en PDF

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Alta — Facilita compartir el resultado, aunque la consulta en pantalla ya cubre lo esencial.

**Story Points:** 3 — Descarga de un archivo ya guardado, con reintento si falló la generación.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E10

### Historia de Usuario

> Como empresa o colaborador, quiero descargar mi resultado en PDF, para compartirlo con mi equipo.

### Descripción

El PDF se genera al terminar el análisis y se guarda; descargarlo no vuelve a llamar a la IA.

### Valor de negocio

Permite compartir el resultado con el equipo mediante un informe descargable.

### Caso de éxito

1. Pulso “Descargar PDF”.
2. Se descarga el archivo guardado.

### Criterios de aceptación

#### CA-001 — Contenido del PDF

**Dado** que mi medición está “Terminada”,

**Cuando** descargo el PDF,

**Entonces** tiene 3 páginas: portada y puntaje, puntaje por categoría y recomendaciones.

#### CA-002 — Radar y fecha

**Dado** que descargo el PDF,

**Cuando** lo abro,

**Entonces** incluye el radar y la fecha de la medición.

#### CA-003 — Archivo guardado

**Dado** que el PDF ya fue generado,

**Cuando** pulso “Descargar PDF”,

**Entonces** se descarga el archivo guardado.

#### CA-004 — Reintento

**Dado** que el PDF no se pudo generar,

**Cuando** pulso de nuevo,

**Entonces** puedo intentar la descarga otra vez.

### Reglas de negocio

- RN-024 — PDF de 3 páginas (portada y puntaje, puntaje por categoría, recomendaciones), generado una vez al terminar el análisis y guardado; no vuelve a llamar a la IA.
- RN-028 — El resultado no incluye análisis FODA ni comparación con promedios.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Si el PDF no se pudo generar, se puede intentar de nuevo.

### Casos de error

- El PDF no se pudo generar.

### Dependencias

- Depende de HU-084 — Publicar el resultado.

### Requisitos relacionados

- RF-032

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-084.

---

## HU-067 — Ver mi historial

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Media — Da seguimiento a la evolución, pero es de consulta.

**Story Points:** 3 — Pantalla de consulta con gráfica y lista con tres accesos por medición.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E8

### Historia de Usuario

> Como empresa o colaborador, quiero ver todas mis mediciones y cómo he evolucionado, para ver si he mejorado.

### Descripción

Historial de solo consulta.

### Valor de negocio

Permite ver todas las mediciones y la evolución para saber si la empresa ha mejorado.

### Caso de éxito

1. Abro “Mi historial”.
2. Veo la gráfica y la lista.
3. Abro el resultado, las respuestas o el PDF de una medición.

### Criterios de aceptación

#### CA-001 — Gráfica de evolución

**Dado** que tengo 2 o más mediciones,

**Cuando** abro “Mi historial”,

**Entonces** veo la gráfica con las franjas de nivel.

#### CA-002 — Una sola medición

**Dado** que tengo solo 1 medición,

**Cuando** abro “Mi historial”,

**Entonces** la gráfica no aparece.

#### CA-003 — Datos por medición

**Dado** que abro “Mi historial”,

**Cuando** reviso la lista,

**Entonces** cada medición muestra fecha, puntaje, nivel y variación.

#### CA-004 — Accesos por medición

**Dado** que veo una medición en la lista,

**Cuando** reviso sus opciones,

**Entonces** ofrece “Ver resultado”, “Ver mis respuestas” y “Descargar PDF”.

#### CA-005 — Sin iniciar aquí

**Dado** que estoy en “Mi historial”,

**Cuando** reviso la pantalla,

**Entonces** no aparece “Iniciar diagnóstico”.

### Reglas de negocio

- RN-019 — Niveles fijos: Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino 60–79, Sigue así 80–100.
- RN-020 — La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-064 — Ver mi resultado.

### Requisitos relacionados

- RF-033

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-064.

---

## HU-082 — Pedir una nueva medición cuando la mía venció

**Épica:** EP-010 — Experiencia de la empresa

**Actor:** Empresa (usuario principal o colaborador)

**Prioridad:** Media — Es Media porque resuelve un caso puntual pero evita que la empresa quede bloqueada.

**Story Points:** 3 — Botón de solicitud con control de solicitud repetida y cambio de estado en el inicio.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: falta dibujar la pantalla en el prototipo

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como empresa o colaborador, quiero pedir al administrador que me asigne otra medición cuando la mía venció, para poder responder el diagnóstico.

### Descripción

Una medición vencida no se puede responder; la empresa envía una solicitud.

> En el inicio de la empresa, la medición vencida muestra además que se solicitó un nuevo diagnóstico. Pendiente de dibujar en el prototipo.

### Valor de negocio

Permite a la empresa seguir con su diagnóstico cuando su medición venció.

### Caso de éxito

1. Veo que mi medición venció.
2. Pulso el botón para pedir una nueva.
3. Veo la confirmación de que la solicitud fue enviada; mi medición pasa a mostrarse como “Vencida · Nueva medición solicitada”.

### Criterios de aceptación

#### CA-001 — Opción de pedir otra

**Dado** que mi medición está vencida,

**Cuando** entro a mi inicio,

**Entonces** veo la opción de pedir una nueva.

#### CA-002 — Solicitud enviada

**Dado** que pulso el botón de pedir una nueva,

**Cuando** se envía,

**Entonces** veo la confirmación y el Administrador la recibe por correo.

#### CA-003 — Estado en el inicio

**Dado** que ya envié la solicitud,

**Cuando** vuelvo a mi inicio,

**Entonces** la medición muestra Vencida · Nueva medición solicitada y no solo Vencida.

#### CA-004 — No se responde

**Dado** que mi medición está vencida,

**Cuando** intento responderla,

**Entonces** no puedo.

#### CA-005 — Solicitud en espera

**Dado** que ya envié una solicitud,

**Cuando** intento enviar otra,

**Entonces** se me indica que está en espera.

### Reglas de negocio

- RN-016 — Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra.
- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.

### Validaciones

- Una sola solicitud abierta a la vez.

### Casos alternativos

- Ninguno.

### Casos de error

- Si ya envié una solicitud, se me indica que está en espera.

### Dependencias

- Depende de HU-070 — Marcar mediciones vencidas.
- Depende de HU-083 — Atender las solicitudes de nueva medición.

### Requisitos relacionados

- RF-034

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- SIN VISTA: pendiente de dibujar en el prototipo el estado Vencida · Nueva medición solicitada.
- Depende de HU-070 y HU-083.

---

## EP-011 — Colaboradores

## HU-076 — Ver los colaboradores de mi empresa

**Épica:** EP-011 — Colaboradores

**Actor:** Empresa (usuario principal)

**Prioridad:** Media — Es Media porque es la base para crear, cambiar contraseña y desactivar colaboradores.

**Story Points:** 2 — Pantalla de lectura con lista y accesos a otras acciones.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E12

### Historia de Usuario

> Como empresa (usuario principal), quiero ver la lista de mis colaboradores, para saber quién tiene acceso a mi empresa.

### Descripción

Lista con estado de cada colaborador. Solo el usuario principal ve esta sección.

### Valor de negocio

Permite a la empresa saber quién tiene acceso a su cuenta.

### Caso de éxito

1. Entro a “Colaboradores”.
2. Veo nombre, correo y estado de cada uno.
3. Elijo crear, cambiar contraseña o desactivar.

### Criterios de aceptación

#### CA-001 — Solo usuario principal

**Dado** que inicié sesión,

**Cuando** reviso el menú,

**Entonces** solo el usuario principal ve la sección Colaboradores.

#### CA-002 — Lista de colaboradores

**Dado** que soy usuario principal y entro a Colaboradores,

**Cuando** se muestra la lista,

**Entonces** veo nombre, correo y estado de cada colaborador.

#### CA-003 — Accesos a acciones

**Dado** que veo la lista,

**Cuando** elijo un colaborador,

**Entonces** tengo accesos a crear, cambiar contraseña y desactivar.

#### CA-004 — Lista vacía

**Dado** que no tengo colaboradores,

**Cuando** entro a Colaboradores,

**Entonces** veo la lista vacía con el botón para crear.

### Reglas de negocio

- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.
- RN-007 — Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Sin colaboradores: se muestra la lista vacía con el botón para crear.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-002 — Registrar mi empresa.

### Requisitos relacionados

- RF-035

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-002.

---

## HU-077 — Crear un colaborador

**Épica:** EP-011 — Colaboradores

**Actor:** Empresa (usuario principal)

**Prioridad:** Media — Es Media porque habilita el trabajo en equipo pero la empresa puede operar sola.

**Story Points:** 3 — Formulario de tres campos con regla de contraseña fuerte y control de correo duplicado.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E12.1, E12.2

### Historia de Usuario

> Como empresa (usuario principal), quiero crear la cuenta de un colaborador con su correo y una contraseña, para que trabaje en el diagnóstico de mi empresa.

### Descripción

La empresa define el usuario (correo) y la contraseña y se los comparte a la persona.

### Valor de negocio

Permite a la empresa dar acceso a una persona de su equipo sin compartir su propia cuenta.

### Caso de éxito

1. Pulso “Crear colaborador”.
2. Escribo nombre, correo y contraseña.
3. Pulso “Crear”.
4. Veo la confirmación con los datos que debo compartir (E12.2).

### Criterios de aceptación

#### CA-001 — Crear colaborador

**Dado** que soy usuario principal,

**Cuando** creo un colaborador con nombre, correo y contraseña válidos,

**Entonces** aparece en la lista como activo y puede iniciar sesión con esos datos.

#### CA-002 — Rol Colaborador

**Dado** que creé la cuenta,

**Cuando** reviso su rol,

**Entonces** tiene el rol Colaborador.

#### CA-003 — Datos para compartir

**Dado** que pulso Crear con datos válidos,

**Cuando** se guarda,

**Entonces** veo la confirmación con los datos que debo compartir.

#### CA-004 — Correo ya registrado

**Dado** que el correo ya está registrado,

**Cuando** intento crear el colaborador,

**Entonces** se me avisa y no se crea.

#### CA-005 — Contraseña débil

**Dado** que escribo una contraseña que no cumple la regla,

**Cuando** pulso Crear,

**Entonces** se me avisa.

### Reglas de negocio

- RN-001 — Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- RN-002 — Un correo no puede pertenecer a más de una cuenta.
- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.

### Validaciones

- Nombre, correo y contraseña obligatorios.
- Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- El correo no puede estar ya registrado.

### Casos alternativos

- Ninguno.

### Casos de error

- Correo ya registrado: se avisa y no se crea.
- Contraseña que no cumple la regla: se avisa.

### Dependencias

- Depende de HU-076 — Ver los colaboradores de mi empresa.

### Requisitos relacionados

- RF-035

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)

### Riesgos

- Depende de HU-076.
- El colaborador entra con HU-001.

---

## HU-078 — Cambiar la contraseña de un colaborador

**Épica:** EP-011 — Colaboradores

**Actor:** Empresa (usuario principal)

**Prioridad:** Media — Es Media porque evita que un colaborador quede sin acceso.

**Story Points:** 2 — Formulario de un solo campo con la regla de contraseña fuerte.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E12.3

### Historia de Usuario

> Como empresa (usuario principal), quiero cambiar la contraseña de un colaborador, para recuperar su acceso si la olvidó.

### Descripción

La empresa define una contraseña nueva y se la comparte.

### Valor de negocio

Permite recuperar el acceso de un colaborador que olvidó su contraseña.

### Caso de éxito

1. Elijo “Cambiar contraseña” en un colaborador.
2. Escribo la nueva contraseña.
3. Guardo y comparto la nueva contraseña.

### Criterios de aceptación

#### CA-001 — Definir contraseña nueva

**Dado** que soy usuario principal y elijo Cambiar contraseña en un colaborador,

**Cuando** escribo una contraseña válida y guardo,

**Entonces** la contraseña queda cambiada y puedo compartirla.

#### CA-002 — Cumple la regla

**Dado** que escribo una contraseña nueva,

**Cuando** guardo,

**Entonces** debe cumplir la regla de contraseña fuerte.

#### CA-003 — Ingreso con la nueva contraseña

**Dado** que cambié la contraseña,

**Cuando** el colaborador inicia sesión,

**Entonces** entra con la nueva contraseña.

#### CA-004 — Contraseña débil

**Dado** que escribo una contraseña que no cumple la regla,

**Cuando** guardo,

**Entonces** se me avisa.

### Reglas de negocio

- RN-001 — Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.
- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.

### Validaciones

- Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial.

### Casos alternativos

- Ninguno.

### Casos de error

- Contraseña que no cumple la regla: se avisa.

### Dependencias

- Depende de HU-077 — Crear un colaborador.

### Requisitos relacionados

- RF-035

### Requisitos no funcionales relacionados

- RNF-001 (Seguridad)

### Riesgos

- Depende de HU-077.

---

## HU-079 — Desactivar o reactivar un colaborador

**Épica:** EP-011 — Colaboradores

**Actor:** Empresa (usuario principal)

**Prioridad:** Baja — Es Baja porque es una acción ocasional que no bloquea el uso diario.

**Story Points:** 2 — Acción de desactivar con confirmación y su inversa, sin formularios.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E12

### Historia de Usuario

> Como empresa (usuario principal), quiero desactivar la cuenta de un colaborador, para quitarle el acceso sin borrar su información.

### Descripción

Desactivar impide iniciar sesión; se puede reactivar.

### Valor de negocio

Permite quitar el acceso a un colaborador sin perder su información.

### Caso de éxito

1. Elijo “Desactivar” en un colaborador y confirmo.
2. Para volver a darle acceso uso “Reactivar”.

### Criterios de aceptación

#### CA-001 — Desactivar

**Dado** que soy usuario principal,

**Cuando** elijo Desactivar en un colaborador y confirmo,

**Entonces** el colaborador queda desactivado.

#### CA-002 — Sin ingreso

**Dado** que un colaborador está desactivado,

**Cuando** intenta iniciar sesión,

**Entonces** no puede entrar.

#### CA-003 — Reactivar

**Dado** que un colaborador está desactivado,

**Cuando** uso Reactivar,

**Entonces** vuelve a tener acceso.

#### CA-004 — Solo usuario principal

**Dado** que soy colaborador,

**Cuando** reviso mis opciones,

**Entonces** no tengo la acción de desactivar.

### Reglas de negocio

- RN-004 — Una cuenta desactivada no puede iniciar sesión y su información no se borra; se puede reactivar.
- RN-025 — Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-076 — Ver los colaboradores de mi empresa.

### Requisitos relacionados

- RF-035

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- Depende de HU-076.

---

## EP-012 — Cálculo, IA y tareas automáticas

## HU-068 — Calcular los puntajes

**Épica:** EP-012 — Cálculo, IA y tareas automáticas

**Actor:** Sistema

**Prioridad:** Alta — Sin cálculo no hay resultado: es la base de la medición.

**Story Points:** 8 — Varias reglas de cálculo encadenadas: tipos de pregunta, promedios, ponderación, nivel y variación.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E6

### Historia de Usuario

> Como sistema, quiero calcular el puntaje de cada categoría y el total, para que el resultado sea justo y siempre igual.

### Descripción

Cálculo de puntajes con las respuestas y las calificaciones de la IA.

### Valor de negocio

Garantiza que el puntaje sea justo y siempre igual para todas las empresas.

### Caso de éxito

1. Calculo el puntaje de las preguntas de opción única y múltiple.
2. Recibo la calificación de las abiertas (HU-069).
3. Promedio por categoría.
4. Calculo el total ponderado y el nivel.
5. Calculo la variación contra la medición anterior.

### Criterios de aceptación

#### CA-001 — Puntaje por tipo de pregunta

**Dado** que la medición está “Enviada”,

**Cuando** se calculan las preguntas,

**Entonces** opción única usa el puntaje de la opción y selección múltiple suma las marcadas con tope 100.

#### CA-002 — Promedio por categoría

**Dado** que hay puntajes de todas las preguntas de una categoría,

**Cuando** se calcula la categoría,

**Entonces** su puntaje es el promedio de sus preguntas, incluidas las abiertas calificadas por la IA.

#### CA-003 — Total ponderado

**Dado** que todas las categorías tienen puntaje,

**Cuando** se calcula el total,

**Entonces** pondera cada categoría por su porcentaje definido en el diagnóstico.

#### CA-004 — Nivel

**Dado** que el total ponderado es 62,

**Cuando** se calcula el nivel,

**Entonces** el nivel es “Vas en buen camino”.

#### CA-005 — Variación

**Dado** que la empresa tiene una medición anterior,

**Cuando** se calcula la variación,

**Entonces** es contra la medición anterior de la misma empresa.

#### CA-006 — Categoría sin calificación

**Dado** que falta la calificación de una categoría,

**Cuando** se intenta calcular el total,

**Entonces** no se calcula hasta tenerla.

### Reglas de negocio

- RN-011 — La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%.
- RN-018 — Puntaje: opción única = puntaje de la opción; selección múltiple = suma con tope 100; abiertas = 0–100 calificadas por la IA; categoría = promedio de sus preguntas; total = promedio ponderado por la importancia.
- RN-019 — Niveles fijos: Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino 60–79, Sigue así 80–100.
- RN-020 — La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Si falta la calificación de una categoría, no se calcula el total hasta tenerla.

### Dependencias

- Depende de HU-069 — Analizar cada categoría con la IA.

### Requisitos relacionados

- RF-036

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-069 (calificación de abiertas por la IA).

---

## HU-069 — Analizar cada categoría con la IA

**Épica:** EP-012 — Cálculo, IA y tareas automáticas

**Actor:** Sistema

**Prioridad:** Alta — Alimenta el contenido del resultado que recibe la empresa.

**Story Points:** 8 — Integración con la IA, formato fijo, reintentos por categoría y aviso al administrador.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E5, A4

### Historia de Usuario

> Como sistema, quiero enviar a la IA las respuestas de cada categoría en una sola etapa, para obtener observaciones, calificaciones y recomendaciones.

### Descripción

Una etapa, “Analizar categoría”, que se ejecuta una vez por categoría.

### Valor de negocio

Obtiene de la IA las observaciones, calificaciones y recomendaciones de cada categoría.

### Caso de éxito

1. Armo el prompt de la categoría (HU-081).
2. Envío la categoría a la IA.
3. Recibo una observación por pregunta, el puntaje 0–100 de cada abierta, una observación de la categoría y una recomendación.
4. Repito para cada categoría.

### Criterios de aceptación

#### CA-001 — Una vez por categoría

**Dado** que la medición está “Enviada”,

**Cuando** se analiza,

**Entonces** cada categoría se analiza una vez.

#### CA-002 — Contenido de la respuesta

**Dado** que se analiza una categoría,

**Cuando** se recibe la respuesta,

**Entonces** incluye observación por pregunta, puntaje en abiertas, observación de la categoría y recomendación.

#### CA-003 — Reintento

**Dado** que una categoría falla o responde fuera del formato fijo,

**Cuando** se reintenta,

**Entonces** se repite solo esa categoría, hasta 3 veces.

#### CA-004 — Reintentos agotados

**Dado** que una categoría falla,

**Cuando** fallan los 3 reintentos,

**Entonces** se avisa al administrador por correo y las demás categorías conservan su resultado.

### Reglas de negocio

- RN-021 — El análisis de IA es una sola etapa por categoría; si falla, se reintenta solo esa categoría hasta 3 veces y luego se avisa por correo al Administrador.
- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Si una categoría falla o la respuesta no viene en el formato fijo, se reintenta solo esa, hasta 3 veces.
- Tras 3 fallos se avisa al administrador por correo y la empresa ve el aviso en la pantalla de espera.

### Dependencias

- Depende de HU-081 — Armar el prompt de cada categoría.
- Depende de HU-068 — Calcular los puntajes.

### Requisitos relacionados

- RF-037

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-081 (armado del prompt), HU-041 (prompt general) y de la IA como servicio externo.

---

## HU-070 — Marcar mediciones vencidas

**Épica:** EP-012 — Cálculo, IA y tareas automáticas

**Actor:** Sistema

**Prioridad:** Media — Mantiene el seguimiento al día, sin afectar el flujo principal de respuesta.

**Story Points:** 2 — Tarea diaria con una sola regla de fecha.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A1d

### Historia de Usuario

> Como sistema, quiero revisar cada día las fechas límite, para que el Administrador vea las vencidas.

### Descripción

Tarea programada diaria.

### Valor de negocio

Permite que el Administrador vea cada día qué mediciones vencieron.

### Caso de éxito

1. Cada día reviso las mediciones sin enviar.
2. Marco como vencidas las que pasaron su fecha límite.

### Criterios de aceptación

#### CA-001 — Marcar vencidas

**Dado** que hay mediciones sin enviar con fecha límite pasada,

**Cuando** corre la revisión diaria,

**Entonces** se marcan como vencidas.

#### CA-002 — Pestaña Vencidas

**Dado** que una medición fue marcada como vencida,

**Cuando** el Administrador revisa su panel,

**Entonces** aparece en la pestaña Vencidas.

#### CA-003 — No se puede responder

**Dado** que una medición está vencida,

**Cuando** la empresa intenta responderla,

**Entonces** no puede seguir respondiéndola.

#### CA-004 — Dentro de plazo

**Dado** que una medición sin enviar aún no pasó su fecha límite,

**Cuando** corre la revisión diaria,

**Entonces** no se marca como vencida.

### Reglas de negocio

- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.
- RN-016 — Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-036 — Asignar una medición.

### Requisitos relacionados

- RF-007

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-036 y HU-007 (pestaña Vencidas).

---

## HU-081 — Armar el prompt de cada categoría

**Épica:** EP-012 — Cálculo, IA y tareas automáticas

**Actor:** Sistema

**Prioridad:** Alta — Es Alta porque de este prompt depende la calidad del análisis.

**Story Points:** 5 — Combina varias capas y reglas de especificidad, con respuestas elegidas.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** A4.4

### Historia de Usuario

> Como sistema, quiero armar el prompt de cada categoría con sus capas, para que la IA reciba instrucciones y respuestas correctas.

### Descripción

Combina las instrucciones generales con los ajustes de sector y empresa, las preguntas y respuestas, y el formato fijo.

### Valor de negocio

Garantiza que la IA reciba instrucciones y respuestas correctas para cada categoría.

### Caso de éxito

1. Tomo las instrucciones generales.
2. Aplico el ajuste del sector y luego el de la empresa si existen.
3. Agrego las preguntas con las respuestas de la empresa y el “Ten en cuenta” de cada respuesta elegida.
4. Agrego el formato fijo.

### Criterios de aceptación

#### CA-001 — Capas en orden

**Dado** que existe el prompt general,

**Cuando** se arma el prompt de una categoría,

**Entonces** incluye todas las capas en el orden general, sector, empresa, preguntas con respuestas y formato fijo.

#### CA-002 — Lo más específico

**Dado** que hay ajuste de sector y de empresa,

**Cuando** se arma el prompt,

**Entonces** se usa lo más específico: empresa, sector, general.

#### CA-003 — Ten en cuenta solo si se eligió

**Dado** que algunas respuestas no fueron elegidas,

**Cuando** se arma el prompt,

**Entonces** el Ten en cuenta de esas respuestas no aparece.

#### CA-004 — Coincide con lo que ve el Administrador

**Dado** que el Administrador revisa el prompt completo,

**Cuando** lo compara con el prompt armado,

**Entonces** coinciden (HU-044).

### Reglas de negocio

- RN-022 — El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores.
- RN-013 — Pregunta: abierta, opción única o selección múltiple; opciones con puntaje 0–100; la indicación para responder es obligatoria; “Ten en cuenta” por respuesta es opcional (máx. 200 caracteres), no lo ve la empresa y solo se usa si esa respuesta fue elegida; la abierta tiene un único criterio de calificación; texto abierto de máximo 1000 caracteres.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-041 — Editar las instrucciones generales de la IA.
- Depende de HU-042 — Ajustar el prompt para un sector.
- Depende de HU-043 — Ajustar el prompt para una empresa.
- Depende de HU-024 — Agregar una pregunta.

### Requisitos relacionados

- RF-037

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-041, HU-042, HU-043 y HU-024.

---

## HU-084 — Publicar el resultado

**Épica:** EP-012 — Cálculo, IA y tareas automáticas

**Actor:** Sistema

**Prioridad:** Alta — Es Alta porque es el paso que convierte el análisis en el resultado que ve el cliente.

**Story Points:** 8 — Flujo con varias reglas: consolidación, cambio de estado, versión inmutable, PDF, correo y reintento.

**MoSCoW:** Must

**Estado:** Pendiente

**Definition of Ready:** LISTA

**Vistas del prototipo:** E6, E10

### Historia de Usuario

> Como sistema, quiero publicar y guardar el resultado cuando termina el análisis, para que la empresa lo vea y no cambie.

### Descripción

Consolida, publica y guarda una versión inmutable.

### Valor de negocio

Entrega a la empresa un resultado final que no cambia y le avisa cuando está listo.

### Caso de éxito

1. Consolido promedios por categoría y total.
2. Cambio la medición a “Terminada”.
3. Guardo la versión del resultado.
4. Genero y guardo el PDF de 3 páginas sin volver a llamar a la IA.
5. Aviso a la empresa por correo.

### Criterios de aceptación

#### CA-001 — Medición terminada

**Dado** que todas las categorías están analizadas,

**Cuando** termina el análisis,

**Entonces** la medición pasa de Enviada a Terminada.

#### CA-002 — Versión inmutable

**Dado** que se publica el resultado,

**Cuando** se guarda,

**Entonces** la versión del resultado queda guardada y no cambia.

#### CA-003 — PDF guardado

**Dado** que se publicó el resultado,

**Cuando** se genera el PDF de 3 páginas,

**Entonces** queda guardado sin volver a llamar a la IA.

#### CA-004 — Aviso a la empresa

**Dado** que el resultado está publicado,

**Cuando** termina la publicación,

**Entonces** la empresa recibe un correo.

#### CA-005 — Fallo del PDF

**Dado** que el PDF falla,

**Cuando** se publica el resultado,

**Entonces** el resultado se publica igual y el PDF se reintenta.

### Reglas de negocio

- RN-023 — El resultado se publica directamente, sin revisión del Administrador, y queda guardado e inmutable.
- RN-024 — PDF de 3 páginas (portada y puntaje, puntaje por categoría, recomendaciones), generado una vez al terminar el análisis y guardado; no vuelve a llamar a la IA.
- RN-015 — Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida.
- RN-018 — Puntaje: opción única = puntaje de la opción; selección múltiple = suma con tope 100; abiertas = 0–100 calificadas por la IA; categoría = promedio de sus preguntas; total = promedio ponderado por la importancia.
- RN-010 — Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Si el PDF falla, el resultado se publica igual y el PDF se reintenta.

### Dependencias

- Depende de HU-069 — Analizar cada categoría con la IA.
- Depende de HU-068 — Calcular los puntajes.

### Requisitos relacionados

- RF-038

### Requisitos no funcionales relacionados

- RNF-004 (Rendimiento)

### Riesgos

- Depende de HU-069 y HU-068.

---

## EP-013 — Diagnóstico por WhatsApp

## HU-071 — Configurar las preguntas del bot

**Épica:** EP-013 — Diagnóstico por WhatsApp

**Actor:** Administrador

**Prioridad:** Alta — Es Alta porque sin preguntas configuradas el bot no puede iniciar ninguna conversación.

**Story Points:** 5 — Gestión completa de hasta 10 preguntas de tres tipos, con orden y puntajes por opción.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: aplazada; falta definir y diseñar la sección del bot en el panel (sin vista en el prototipo).

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como administrador, quiero escribir las preguntas del bot desde el panel, para cambiarlas sin tocar el código.

### Descripción

Configuración de hasta 10 preguntas del bot.

> SIN VISTA EN EL PROTOTIPO: falta diseñar la sección del bot en el panel.

### Valor de negocio

Permite al Administrador cambiar las preguntas del bot sin depender de un desarrollador.

### Caso de éxito

1. Entro a la sección del bot.
2. Creo, edito, ordeno o elimino preguntas.
3. Guardo.

### Criterios de aceptación

#### CA-001 — Crear pregunta

**Dado** que soy Administrador en la sección del bot y hay menos de 10 preguntas,

**Cuando** creo una pregunta abierta, de opción única o de selección múltiple y guardo,

**Entonces** la pregunta queda en la lista del bot.

#### CA-002 — Puntaje en opciones

**Dado** que creo una pregunta de opción única o de selección múltiple,

**Cuando** defino sus opciones,

**Entonces** cada opción lleva su puntaje.

#### CA-003 — Editar, ordenar y eliminar

**Dado** que ya existen preguntas del bot,

**Cuando** edito, cambio el orden o elimino una y guardo,

**Entonces** la lista refleja los cambios.

#### CA-004 — Límite de 10

**Dado** que ya hay 10 preguntas,

**Cuando** intento agregar otra,

**Entonces** no se permite agregar más.

#### CA-005 — Cambios en conversaciones nuevas

**Dado** que guardé cambios en las preguntas,

**Cuando** una empresa inicia una conversación nueva,

**Entonces** el bot usa las preguntas actualizadas.

### Reglas de negocio

- RN-029 — El diagnóstico por WhatsApp es totalmente separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas configurables; solo texto; retoma desde la última pregunta y solo se reinicia si la empresa lo pide.

### Validaciones

- Máximo 10 preguntas.
- Cada pregunta es abierta, de opción única o de selección múltiple.
- Las opciones llevan puntaje.

### Casos alternativos

- Ninguno.

### Casos de error

- Si ya hay 10 preguntas, no se pueden agregar más.

### Dependencias

- Ninguna.

### Requisitos relacionados

- RF-039

### Requisitos no funcionales relacionados

- RNF-002 (Seguridad)

### Riesgos

- SIN VISTA EN EL PROTOTIPO: falta diseñar la sección del bot en el panel.
- Historia aplazada por decisión del cliente.

---

## HU-072 — Empezar el diagnóstico por WhatsApp

**Épica:** EP-013 — Diagnóstico por WhatsApp

**Actor:** Empresa (por WhatsApp)

**Prioridad:** Alta — Es Alta porque es la puerta de entrada de todo el diagnóstico por WhatsApp.

**Story Points:** 2 — Conversación corta: saludo, explicación y petición del nombre de la empresa.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: aplazada; falta definir el texto exacto del saludo del bot.

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como empresa (por WhatsApp), quiero escribir al número de WhatsApp y que el bot me guíe, para hacer un diagnóstico rápido sin crear una cuenta.

### Descripción

El bot saluda, explica qué va a hacer y pide el nombre de la empresa.

> REQUIERE VALIDACIÓN: texto exacto del saludo.

### Valor de negocio

Permite a una empresa iniciar un diagnóstico rápido por chat sin crear una cuenta.

### Caso de éxito

1. Escribo al número.
2. El bot me saluda y explica en una línea qué hará.
3. Escribo el nombre de mi empresa.

### Criterios de aceptación

#### CA-001 — Saludo y explicación

**Dado** que existen preguntas configuradas,

**Cuando** escribo al número de WhatsApp,

**Entonces** el bot me saluda y explica en una línea qué va a hacer.

#### CA-002 — Pide nombre de empresa

**Dado** que el bot ya me saludó,

**Cuando** termina el mensaje de explicación,

**Entonces** el bot me pide el nombre de mi empresa.

#### CA-003 — Sin cuenta

**Dado** que no tengo cuenta en la web,

**Cuando** inicio la conversación,

**Entonces** el bot no me pide cuenta ni inicio de sesión.

### Reglas de negocio

- RN-029 — El diagnóstico por WhatsApp es totalmente separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas configurables; solo texto; retoma desde la última pregunta y solo se reinicia si la empresa lo pide.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Ninguno.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-071 — Configurar las preguntas del bot.

### Requisitos relacionados

- RF-040

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- REQUIERE VALIDACIÓN: texto exacto del saludo.
- Depende de HU-071 (preguntas configuradas).
- Historia aplazada por decisión del cliente.

---

## HU-073 — Responder las preguntas del bot

**Épica:** EP-013 — Diagnóstico por WhatsApp

**Actor:** Empresa (por WhatsApp)

**Prioridad:** Alta — Es Alta porque es el núcleo del diagnóstico por WhatsApp.

**Story Points:** 5 — Flujo con tres tipos de respuesta, validación de entradas y guardado de cada respuesta.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: aplazada; falta definir el saludo del bot (HU-072) y la sección de preguntas del bot en el panel (HU-071).

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como empresa (por WhatsApp), quiero responder una pregunta a la vez, para completar el diagnóstico desde el chat.

### Descripción

Las preguntas llegan una a una.

### Valor de negocio

Permite completar el diagnóstico desde el chat respondiendo una pregunta a la vez.

### Caso de éxito

1. Recibo una pregunta.
2. Respondo.
3. El bot pasa a la siguiente.

### Criterios de aceptación

#### CA-001 — Preguntas una a una

**Dado** que ya empecé la conversación,

**Cuando** respondo una pregunta,

**Entonces** el bot pasa a la siguiente.

#### CA-002 — Opción única

**Dado** que la pregunta es de opción única,

**Cuando** respondo,

**Entonces** lo hago con botones o lista.

#### CA-003 — Selección múltiple

**Dado** que la pregunta es de selección múltiple,

**Cuando** respondo con números separados por comas,

**Entonces** el bot acepta mi respuesta.

#### CA-004 — Pregunta abierta

**Dado** que la pregunta es abierta,

**Cuando** escribo texto libre,

**Entonces** el bot acepta mi respuesta.

#### CA-005 — Respuesta inválida

**Dado** que respondo algo que el bot no entiende, por ejemplo un número que no existe,

**Cuando** envío la respuesta,

**Entonces** el bot me lo dice y repite la pregunta.

#### CA-006 — Respuesta guardada

**Dado** que respondí una pregunta,

**Cuando** el bot pasa a la siguiente,

**Entonces** mi respuesta queda guardada en la conversación.

### Reglas de negocio

- RN-029 — El diagnóstico por WhatsApp es totalmente separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas configurables; solo texto; retoma desde la última pregunta y solo se reinicia si la empresa lo pide.

### Validaciones

- Opción única: botones o lista.
- Selección múltiple: números separados por comas.
- Abierta: texto libre.

### Casos alternativos

- Ninguno.

### Casos de error

- Si respondo algo que el bot no entiende, me lo dice y repite la pregunta.

### Dependencias

- Depende de HU-072 — Empezar el diagnóstico por WhatsApp.

### Requisitos relacionados

- RF-040

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-072.
- Historia aplazada por decisión del cliente.

---

## HU-074 — Retomar la conversación

**Épica:** EP-013 — Diagnóstico por WhatsApp

**Actor:** Empresa (por WhatsApp)

**Prioridad:** Media — Es Media porque mejora la comodidad pero el diagnóstico funciona sin ella.

**Story Points:** 3 — Retomar desde la última pregunta y reiniciar solo a pedido, con confirmación.

**MoSCoW:** Could

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: aplazada; falta definir el saludo del bot (HU-072) y la sección de preguntas del bot en el panel (HU-071), de los que depende la conversación.

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como empresa (por WhatsApp), quiero seguir desde la última pregunta si dejé de responder, para no empezar de nuevo.

### Descripción

La conversación se reinicia solo si la empresa lo pide.

### Valor de negocio

Evita que la empresa tenga que empezar de nuevo si deja la conversación a medias.

### Caso de éxito

1. Vuelvo a escribir.
2. El bot sigue desde la última pregunta sin responder.
3. Si pido volver al inicio, el bot confirma y empieza de nuevo.

### Criterios de aceptación

#### CA-001 — Retomar

**Dado** que tengo una conversación empezada,

**Cuando** vuelvo a escribir,

**Entonces** el bot sigue desde la última pregunta sin responder.

#### CA-002 — Sin reinicio automático

**Dado** que pasó mucho tiempo sin responder,

**Cuando** vuelvo a escribir,

**Entonces** la conversación no se reinició sola.

#### CA-003 — Reinicio a pedido

**Dado** que pido volver al inicio,

**Cuando** el bot lo confirma,

**Entonces** empieza de nuevo desde el saludo.

#### CA-004 — Nuevo diagnóstico al terminar

**Dado** que terminé el diagnóstico,

**Cuando** pido empezar otro,

**Entonces** el bot inicia una conversación nueva.

### Reglas de negocio

- RN-029 — El diagnóstico por WhatsApp es totalmente separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas configurables; solo texto; retoma desde la última pregunta y solo se reinicia si la empresa lo pide.

### Validaciones

- Ninguna específica.

### Casos alternativos

- Al terminar, puedo pedir empezar otro diagnóstico.
- Puedo pedir volver al inicio y el bot confirma antes de empezar de nuevo.

### Casos de error

- Ninguno.

### Dependencias

- Depende de HU-073 — Responder las preguntas del bot.

### Requisitos relacionados

- RF-040

### Requisitos no funcionales relacionados

- Ninguno.

### Riesgos

- Depende de HU-073.
- Historia aplazada por decisión del cliente.

---

## HU-075 — Recibir el resultado por WhatsApp

**Épica:** EP-013 — Diagnóstico por WhatsApp

**Actor:** Empresa (por WhatsApp)

**Prioridad:** Alta — Es Alta porque es el valor final que la empresa recibe del diagnóstico por WhatsApp.

**Story Points:** 5 — Análisis de respuestas, resultado en texto, manejo de fallo y verificación de mensajes de Meta.

**MoSCoW:** Should

**Estado:** Pendiente

**Definition of Ready:** NO LISTA: aplazada; falta definir el saludo y las preguntas del bot (HU-071 y HU-072), de los que depende el resultado.

**Vistas del prototipo:** Sin vista en el prototipo

### Historia de Usuario

> Como empresa (por WhatsApp), quiero recibir mi puntaje, nivel y recomendaciones en el chat, para saber cómo estoy al terminar.

### Descripción

La IA analiza las respuestas y el bot envía el resultado como texto.

### Valor de negocio

Entrega a la empresa su puntaje, nivel y recomendaciones al terminar, dentro del mismo chat.

### Caso de éxito

1. Termino la última pregunta.
2. El sistema analiza las respuestas.
3. Recibo puntaje general, nivel y recomendaciones breves.

### Criterios de aceptación

#### CA-001 — Resultado en el chat

**Dado** que respondí todas las preguntas,

**Cuando** el sistema termina de analizar mis respuestas,

**Entonces** recibo puntaje general, nivel y recomendaciones breves.

#### CA-002 — Solo texto

**Dado** que recibo el resultado,

**Cuando** leo el mensaje,

**Entonces** no incluye PDF ni enlaces.

#### CA-003 — Mensajes verificados

**Dado** que llega un mensaje al bot,

**Cuando** el sistema lo recibe,

**Entonces** solo lo acepta si viene verificado de Meta.

#### CA-004 — Fallo del análisis

**Dado** que el análisis falla,

**Cuando** el sistema lo detecta,

**Entonces** el bot me avisa.

### Reglas de negocio

- RN-029 — El diagnóstico por WhatsApp es totalmente separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas configurables; solo texto; retoma desde la última pregunta y solo se reinicia si la empresa lo pide.

### Validaciones

- Solo texto: no se envía PDF ni enlaces.

### Casos alternativos

- Ninguno.

### Casos de error

- Si el análisis falla, el bot lo avisa.

### Dependencias

- Depende de HU-073 — Responder las preguntas del bot.

### Requisitos relacionados

- RF-040

### Requisitos no funcionales relacionados

- RNF-006 (Seguridad)

### Riesgos

- Depende de HU-073.
- Depende de la integración con Meta (verificación de mensajes).
- Historia aplazada por decisión del cliente.

---

# 6. Reglas de negocio

| ID | Regla | Historias |
|---|---|---|
| RN-001 | Contraseña fuerte: mínimo 8 caracteres, una mayúscula, un número y un carácter especial. | HU-002, HU-004, HU-055, HU-056, HU-077, HU-078 |
| RN-002 | Un correo no puede pertenecer a más de una cuenta. | HU-002, HU-032, HU-034, HU-077 |
| RN-003 | Solo se ofrecen sectores activos al registrar empresas; no existe la opción “Otro”. | HU-002, HU-012, HU-013, HU-014, HU-015, HU-032 |
| RN-004 | Una cuenta desactivada no puede iniciar sesión y su información no se borra; se puede reactivar. | HU-001, HU-003, HU-035, HU-047, HU-079 |
| RN-005 | Los avisos de inicio de sesión y recuperación no revelan si un correo está registrado. | HU-001, HU-003 |
| RN-006 | El enlace de contraseña nueva no vence por tiempo y sirve hasta guardar la contraseña; el Administrador no restablece contraseñas de otras cuentas. | HU-003, HU-004, HU-032, HU-034 |
| RN-007 | Cada rol ve solo las secciones que le corresponden; empresa y colaborador ven solo la información de su propia empresa. | HU-001, HU-046, HU-048, HU-053, HU-056, HU-057, HU-065, HU-076 |
| RN-008 | Un sector con empresas o mediciones no se elimina: se reasigna o se desactiva; las mediciones hechas se conservan. | HU-013, HU-014, HU-015, HU-016, HU-034 |
| RN-009 | El nombre de una categoría es obligatorio, de máximo 40 caracteres y único; una categoría con respuestas se archiva en lugar de borrarse. | HU-017, HU-018, HU-019, HU-026 |
| RN-010 | Un diagnóstico nace como borrador v1; las versiones publicadas son inmutables y los resultados guardan la versión con la que se hicieron. | HU-010, HU-018, HU-020, HU-021, HU-022, HU-023, HU-025, HU-029, HU-084 |
| RN-011 | La importancia de las categorías de un diagnóstico es un porcentaje propio de ese diagnóstico y debe sumar exactamente 100%. | HU-018, HU-019, HU-020, HU-026, HU-027, HU-068 |
| RN-012 | Para publicar: cada categoría tiene al menos 1 pregunta, cada opción tiene puntaje, la importancia suma 100%, toda pregunta tiene indicación y las selecciones múltiples pueden llegar a 100 puntos. | HU-018, HU-020, HU-024, HU-026, HU-028, HU-029 |
| RN-013 | Pregunta: abierta, opción única o selección múltiple; opciones con puntaje 0–100; la indicación para responder es obligatoria; “Ten en cuenta” por respuesta es opcional (máx. 200 caracteres), no lo ve la empresa y solo se usa si esa respuesta fue elegida; la abierta tiene un único criterio de calificación; texto abierto de máximo 1000 caracteres. | HU-024, HU-025, HU-081 |
| RN-014 | Solo hay una medición pendiente por empresa y solo se asignan diagnósticos publicados del sector de la empresa. | HU-009, HU-010, HU-032, HU-036, HU-058, HU-083 |
| RN-015 | Estados de la medición: No iniciada, En curso, Enviada (mientras la IA analiza), Terminada (resultado publicado) y Vencida. | HU-007, HU-036, HU-058, HU-060, HU-062, HU-070, HU-082, HU-084 |
| RN-016 | Una medición vencida no se puede responder; la empresa pide una nueva por correo al Administrador (una solicitud abierta a la vez) y él asigna otra. | HU-009, HU-058, HU-060, HU-070, HU-082, HU-083 |
| RN-017 | Después de enviar el diagnóstico no se pueden cambiar las respuestas. | HU-040, HU-061, HU-062, HU-065 |
| RN-018 | Puntaje: opción única = puntaje de la opción; selección múltiple = suma con tope 100; abiertas = 0–100 calificadas por la IA; categoría = promedio de sus preguntas; total = promedio ponderado por la importancia. | HU-064, HU-068, HU-084 |
| RN-019 | Niveles fijos: Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino 60–79, Sigue así 80–100. | HU-064, HU-067, HU-068 |
| RN-020 | La variación se calcula contra la medición anterior de la misma empresa y solo existe desde la 2.ª medición. | HU-033, HU-039, HU-059, HU-064, HU-067, HU-068 |
| RN-021 | El análisis de IA es una sola etapa por categoría; si falla, se reintenta solo esa categoría hasta 3 veces y luego se avisa por correo al Administrador. | HU-041, HU-062, HU-063, HU-069 |
| RN-022 | El prompt se arma con capas: instrucciones generales, ajuste del sector, ajuste de la empresa, preguntas con respuestas y formato fijo; se usa siempre lo más específico y los ajustes aplican a mediciones posteriores. | HU-041, HU-042, HU-043, HU-044, HU-045, HU-069, HU-080, HU-081 |
| RN-023 | El resultado se publica directamente, sin revisión del Administrador, y queda guardado e inmutable. | HU-038, HU-063, HU-064, HU-084 |
| RN-024 | PDF de 3 páginas (portada y puntaje, puntaje por categoría, recomendaciones), generado una vez al terminar el análisis y guardado; no vuelve a llamar a la IA. | HU-066, HU-084 |
| RN-025 | Colaboradores: sin límite; casi los mismos permisos que la empresa, salvo crear o desactivar colaboradores y cambiar datos de la empresa; la empresa define su correo y contraseña y los comparte; se desactivan junto con la empresa. | HU-035, HU-048, HU-056, HU-076, HU-077, HU-078, HU-079 |
| RN-026 | “Ver como” es solo de lectura y queda registrado quién lo usó y cuándo. | HU-053, HU-054 |
| RN-027 | Los roles del sistema (Administrador, Empresa, Colaborador) son fijos; cada cuenta tiene un solo rol; solo se elimina un rol sin cuentas. | HU-048, HU-049, HU-050, HU-051, HU-052 |
| RN-028 | El resultado no incluye análisis FODA ni comparación con promedios. | HU-038, HU-064, HU-066 |
| RN-029 | El diagnóstico por WhatsApp es totalmente separado de la web: sin cuentas, mediciones ni PDF; máximo 10 preguntas configurables; solo texto; retoma desde la última pregunta y solo se reinicia si la empresa lo pide. | HU-071, HU-072, HU-073, HU-074, HU-075 |

# 7. Requisitos no funcionales relacionados

Estos RNF se desprenden de las historias. El detalle, con los RNF que se agregaron desde RNF-007 y las metas propuestas, está en `06_REQUISITOS_NO_FUNCIONALES.md` (REQUIERE VALIDACIÓN, PA-008).

| ID | Categoría | Requisito | Historias |
|---|---|---|---|
| RNF-001 | Seguridad | Las contraseñas cumplen la regla de contraseña fuerte (RN-001) y los avisos no revelan correos registrados (RN-005). | HU-001, HU-002, HU-003, HU-004, HU-032, HU-055, HU-056, HU-077, HU-078 |
| RNF-002 | Seguridad | El acceso a cada pantalla y dato depende del rol; empresa y colaborador solo acceden a su empresa (RN-007). | HU-001, HU-005, HU-006, HU-010, HU-031, HU-033, HU-038, HU-039, HU-040, HU-043, HU-044, HU-046, HU-047, HU-048, HU-049, HU-050, HU-051, HU-052, HU-053, HU-054, HU-056, HU-057, HU-058, HU-059, HU-064, HU-065, HU-067, HU-071, HU-076, HU-079 |
| RNF-003 | Usabilidad | Las respuestas del diagnóstico se guardan al momento y se puede retomar donde se quedó. | HU-060 |
| RNF-004 | Rendimiento | El envío de correos y el análisis de la IA se hacen en segundo plano sin bloquear la pantalla. | HU-003, HU-008, HU-032, HU-036, HU-037, HU-045, HU-052, HU-062, HU-063, HU-069, HU-082, HU-083, HU-084 |
| RNF-005 | Auditoría | Queda registrado quién usó “Ver como” y cuándo. | HU-053, HU-054 |
| RNF-006 | Seguridad | Los mensajes del bot de WhatsApp solo se aceptan si vienen verificados de Meta (APLAZADO). | HU-075 |

# 8. Matriz de trazabilidad

Requisito → Épica → Historia → Criterio de aceptación.

| Requisito | Épica | Historia | Criterio | Cobertura |
|---|---|---|---|---|
| RF-001 — Iniciar sesión | EP-001 | HU-001 | HU-001: CA-001–CA-005 | Completa |
| RF-002 — Registrar mi empresa | EP-001 | HU-002 | HU-002: CA-001–CA-005 | Completa |
| RF-003 — Recuperar y crear contraseña | EP-001 | HU-003, HU-004 | HU-003: CA-001–CA-004; HU-004: CA-001–CA-005 | Completa |
| RF-004 — Cerrar sesión | EP-001 | HU-005 | HU-005: CA-001–CA-004 | Completa |
| RF-005 — Panel de mediciones del Administrador | EP-002 | HU-006, HU-007 | HU-006: CA-001–CA-005; HU-007: CA-001–CA-006 | Completa |
| RF-006 — Avisos por correo de la medición | EP-002, EP-006 | HU-008, HU-037 | HU-008: CA-001–CA-004; HU-037: CA-001–CA-005 | Completa |
| RF-007 — Seguimiento de mediciones vencidas | EP-002, EP-012 | HU-009, HU-070 | HU-009: CA-001–CA-004; HU-070: CA-001–CA-004 | Completa |
| RF-008 — Consultar sectores y sus diagnósticos | EP-003 | HU-010, HU-011 | HU-010: CA-001–CA-006; HU-011: CA-001–CA-005 | Completa |
| RF-009 — Gestionar sectores | EP-003 | HU-012, HU-013, HU-014, HU-015, HU-016 | HU-012: CA-001–CA-005; HU-013: CA-001–CA-005; HU-014: CA-001–CA-005; HU-015: CA-001–CA-004; HU-016: CA-001–CA-003 | Completa |
| RF-010 — Gestionar el catálogo de categorías | EP-004 | HU-017, HU-018, HU-019 | HU-017: CA-001–CA-005; HU-018: CA-001–CA-005; HU-019: CA-001–CA-005 | Completa |
| RF-011 — Crear, duplicar y retirar diagnósticos | EP-005 | HU-020, HU-021, HU-022 | HU-020: CA-001–CA-004; HU-021: CA-001–CA-004; HU-022: CA-001–CA-005 | Completa |
| RF-012 — Editar el contenido del diagnóstico | EP-005 | HU-023, HU-026, HU-027 | HU-023: CA-001–CA-004; HU-026: CA-001–CA-005; HU-027: CA-001–CA-004 | Completa |
| RF-013 — Gestionar preguntas | EP-005 | HU-024, HU-025 | HU-024: CA-001–CA-006; HU-025: CA-001–CA-005 | Completa |
| RF-014 — Revisar y publicar versiones | EP-005 | HU-028, HU-029 | HU-028: CA-001–CA-005; HU-029: CA-001–CA-006 | Completa |
| RF-015 — Resultado de ejemplo | EP-005 | HU-030 | HU-030: CA-001–CA-005 | Completa |
| RF-016 — Consultar empresas | EP-006 | HU-031, HU-033 | HU-031: CA-001–CA-006; HU-033: CA-001–CA-005 | Completa |
| RF-017 — Registrar, editar y desactivar empresas | EP-006 | HU-032, HU-034, HU-035 | HU-032: CA-001–CA-005; HU-034: CA-001–CA-005; HU-035: CA-001–CA-006 | Completa |
| RF-018 — Asignar mediciones | EP-006 | HU-036 | HU-036: CA-001–CA-006 | Completa |
| RF-019 — Consultar resultados de una empresa | EP-006 | HU-038, HU-039, HU-040 | HU-038: CA-001–CA-004; HU-039: CA-001–CA-004; HU-040: CA-001–CA-004 | Completa |
| RF-020 — Configurar las instrucciones generales de la IA | EP-007 | HU-041, HU-080 | HU-041: CA-001–CA-006; HU-080: CA-001–CA-004 | Completa |
| RF-021 — Ajustar el prompt por sector o empresa | EP-007 | HU-042, HU-043 | HU-042: CA-001–CA-005; HU-043: CA-001–CA-005 | Completa |
| RF-022 — Revisar y probar el prompt | EP-007 | HU-044, HU-045 | HU-044: CA-001–CA-005; HU-045: CA-001–CA-005 | Completa |
| RF-023 — Gestionar cuentas | EP-008 | HU-046, HU-047 | HU-046: CA-001–CA-005; HU-047: CA-001–CA-005 | Completa |
| RF-024 — Gestionar roles | EP-008 | HU-048, HU-049, HU-050, HU-051, HU-052 | HU-048: CA-001–CA-004; HU-049: CA-001–CA-004; HU-050: CA-001–CA-004; HU-051: CA-001–CA-004; HU-052: CA-001–CA-005 | Completa |
| RF-025 — Ver el sistema como otra cuenta | EP-008 | HU-053, HU-054 | HU-053: CA-001–CA-004; HU-054: CA-001–CA-004 | Completa |
| RF-026 — Editar mi perfil | EP-009 | HU-055, HU-056 | HU-055: CA-001–CA-005; HU-056: CA-001–CA-005 | Completa |
| RF-027 — Inicio de la empresa | EP-010 | HU-057, HU-058, HU-059 | HU-057: CA-001–CA-004; HU-058: CA-001–CA-004; HU-059: CA-001–CA-004 | Completa |
| RF-028 — Responder el diagnóstico | EP-010 | HU-060 | HU-060: CA-001–CA-006 | Completa |
| RF-029 — Revisar y enviar el diagnóstico | EP-010 | HU-061, HU-062 | HU-061: CA-001–CA-004; HU-062: CA-001–CA-004 | Completa |
| RF-030 — Esperar el análisis | EP-010 | HU-063 | HU-063: CA-001–CA-004 | Completa |
| RF-031 — Consultar el resultado y las respuestas | EP-010 | HU-064, HU-065 | HU-064: CA-001–CA-006; HU-065: CA-001–CA-004 | Completa |
| RF-032 — Descargar el informe en PDF | EP-010 | HU-066 | HU-066: CA-001–CA-004 | Completa |
| RF-033 — Consultar el historial | EP-010 | HU-067 | HU-067: CA-001–CA-005 | Completa |
| RF-034 — Solicitar y atender una nueva medición | EP-006, EP-010 | HU-082, HU-083 | HU-082: CA-001–CA-005; HU-083: CA-001–CA-004 | Completa |
| RF-035 — Gestionar colaboradores | EP-011 | HU-076, HU-077, HU-078, HU-079 | HU-076: CA-001–CA-004; HU-077: CA-001–CA-005; HU-078: CA-001–CA-004; HU-079: CA-001–CA-004 | Completa |
| RF-036 — Calcular puntajes y niveles | EP-012 | HU-068 | HU-068: CA-001–CA-006 | Completa |
| RF-037 — Analizar cada categoría con la IA | EP-012 | HU-069, HU-081 | HU-069: CA-001–CA-004; HU-081: CA-001–CA-004 | Completa |
| RF-038 — Publicar el resultado | EP-012 | HU-084 | HU-084: CA-001–CA-005 | Completa |
| RF-039 — Configurar el bot de WhatsApp | EP-013 | HU-071 | HU-071: CA-001–CA-005 | Completa (aplazada) |
| RF-040 — Diagnóstico por WhatsApp | EP-013 | HU-072, HU-073, HU-074, HU-075 | HU-072: CA-001–CA-003; HU-073: CA-001–CA-006; HU-074: CA-001–CA-004; HU-075: CA-001–CA-004 | Completa (aplazada) |

## Cobertura de vistas del prototipo

| Vista | Historias |
|---|---|
| L1 | HU-001 |
| L2 | HU-002 |
| L3 | HU-003 |
| L4 | HU-004 |
| A1 | HU-006, HU-007 |
| A1b | HU-007, HU-008 |
| A1c | HU-007, HU-008 |
| A1d | HU-007, HU-009, HU-070 |
| A1e | HU-007 |
| A2 | HU-010, HU-022 |
| A2.1 | HU-023 |
| A2.1b | HU-027 |
| A2.1c | HU-029 |
| A2.1d | HU-026 |
| A2.1e | HU-024 |
| A2.1f | HU-025 |
| A2.2 | HU-013 |
| A2.2a | HU-012 |
| A2.2b | HU-014 |
| A2.2c | HU-015 |
| A2.2d | HU-016 |
| A2.2e | HU-016 |
| A2.3 | HU-017 |
| A2.3b | HU-018 |
| A2.3c | HU-019 |
| A2.4 | HU-028 |
| A2.5 | HU-020 |
| A2.6 | HU-030 |
| A2.6b | HU-030 |
| A2.6c | HU-030 |
| A2.7 | HU-021 |
| A2b | HU-012 |
| A2·T | HU-011, HU-022 |
| A3 | HU-031 |
| A3.1 | HU-033 |
| A3.1b | HU-009, HU-036 |
| A3.1d | HU-034 |
| A3.1e | HU-008, HU-037 |
| A3.1f | HU-035 |
| A3.2 | HU-032 |
| A3.3 | HU-038 |
| A3.4 | HU-039 |
| A3.5 | HU-040 |
| A4 | HU-041, HU-069 |
| A4.4 | HU-044, HU-081 |
| A4.5 | HU-042 |
| A4.6 | HU-043 |
| A4.7 | HU-045 |
| A4.9 | HU-080 |
| A5 | HU-046 |
| A5.1 | HU-048, HU-052 |
| A5.1b | HU-048 |
| A5.1c | HU-050 |
| A5.1d | HU-051 |
| A5.1e | HU-048 |
| A5.2 | HU-049 |
| A5.3b | HU-047 |
| A5.4 | HU-053 |
| A5.4b | HU-054 |
| A5.5 | HU-052 |
| A6 | HU-005, HU-055 |
| E1 | HU-057 |
| E2 | HU-058 |
| E3 | HU-060 |
| E4 | HU-061 |
| E4b | HU-061 |
| E4c | HU-062 |
| E5 | HU-063, HU-069 |
| E6 | HU-064, HU-068, HU-084 |
| E7 | HU-059 |
| E7b | HU-059 |
| E8 | HU-067 |
| E9 | HU-065 |
| E10 | HU-066, HU-084 |
| E11 | HU-005, HU-056 |
| E12 | HU-076, HU-079 |
| E12.1 | HU-077 |
| E12.2 | HU-077 |
| E12.3 | HU-078 |

# 9. Product Backlog

| ID | Épica | Historia | Prioridad | Story Points | Estado |
|---|---|---|---|---:|---|
| HU-001 | EP-001 | Iniciar sesión | Alta | 5 | Pendiente |
| HU-002 | EP-001 | Registrar mi empresa | Alta | 5 | Pendiente |
| HU-003 | EP-001 | Recuperar mi contraseña | Alta | 3 | Pendiente |
| HU-004 | EP-001 | Crear una contraseña nueva | Alta | 3 | Pendiente |
| HU-005 | EP-001 | Cerrar sesión | Media | 2 | Pendiente |
| HU-006 | EP-002 | Ver el inicio con indicadores | Alta | 5 | Pendiente |
| HU-007 | EP-002 | Filtrar las mediciones por estado | Alta | 3 | Pendiente |
| HU-008 | EP-002 | Recordar o reenviar el aviso | Media | 3 | Pendiente |
| HU-009 | EP-002 | Dar una nueva fecha a una medición vencida | Media | 3 | Pendiente |
| HU-010 | EP-003 | Ver los diagnósticos de un sector | Alta | 5 | Pendiente |
| HU-011 | EP-003 | Ver todos los diagnósticos | Media | 3 | Pendiente |
| HU-012 | EP-003 | Crear un sector | Alta | 3 | Pendiente |
| HU-013 | EP-003 | Editar un sector | Alta | 3 | Pendiente |
| HU-014 | EP-003 | Reasignar las empresas de un sector | Media | 3 | Pendiente |
| HU-015 | EP-003 | Desactivar un sector | Media | 2 | Pendiente |
| HU-016 | EP-003 | Eliminar un sector | Media | 3 | Pendiente |
| HU-017 | EP-004 | Ver y editar el catálogo de categorías | Alta | 3 | Pendiente |
| HU-018 | EP-004 | Crear una categoría | Alta | 5 | Pendiente |
| HU-019 | EP-004 | Eliminar o archivar una categoría | Media | 5 | Pendiente |
| HU-020 | EP-005 | Crear un diagnóstico | Alta | 8 | Pendiente |
| HU-021 | EP-005 | Duplicar un diagnóstico | Media | 3 | Pendiente |
| HU-022 | EP-005 | Archivar o eliminar un diagnóstico | Media | 3 | Pendiente |
| HU-023 | EP-005 | Ver y ordenar el contenido del diagnóstico | Alta | 5 | Pendiente |
| HU-024 | EP-005 | Agregar una pregunta | Alta | 8 | Pendiente |
| HU-025 | EP-005 | Editar o eliminar una pregunta | Alta | 3 | Pendiente |
| HU-026 | EP-005 | Agregar categorías al diagnóstico | Media | 3 | Pendiente |
| HU-027 | EP-005 | Editar la importancia de las categorías | Alta | 3 | Pendiente |
| HU-028 | EP-005 | Ver la vista previa y el estado | Alta | 5 | Pendiente |
| HU-029 | EP-005 | Publicar una versión | Alta | 5 | Pendiente |
| HU-030 | EP-005 | Ver un resultado de ejemplo | Baja | 3 | Pendiente |
| HU-031 | EP-006 | Ver la lista de empresas | Alta | 3 | Pendiente |
| HU-032 | EP-006 | Registrar una empresa | Alta | 5 | Pendiente |
| HU-033 | EP-006 | Ver la ficha e historial de una empresa | Alta | 5 | Pendiente |
| HU-034 | EP-006 | Editar los datos de la cuenta de una empresa | Media | 3 | Pendiente |
| HU-035 | EP-006 | Desactivar o reactivar la cuenta de una empresa | Media | 3 | Pendiente |
| HU-036 | EP-006 | Asignar una medición | Alta | 8 | Pendiente |
| HU-037 | EP-006 | Editar el correo de aviso de la medición | Media | 5 | Pendiente |
| HU-038 | EP-006 | Ver el resultado de una empresa | Alta | 3 | Pendiente |
| HU-039 | EP-006 | Ver el historial de una empresa | Media | 3 | Pendiente |
| HU-040 | EP-006 | Ver las respuestas de una empresa | Media | 2 | Pendiente |
| HU-041 | EP-007 | Editar las instrucciones generales de la IA | Alta | 5 | Pendiente |
| HU-042 | EP-007 | Ajustar el prompt para un sector | Media | 5 | Pendiente |
| HU-043 | EP-007 | Ajustar el prompt para una empresa | Baja | 5 | Pendiente |
| HU-044 | EP-007 | Ver el prompt completo | Media | 3 | Pendiente |
| HU-045 | EP-007 | Probar el prompt con un ejemplo | Media | 5 | Pendiente |
| HU-046 | EP-008 | Ver las cuentas del sistema | Media | 3 | Pendiente |
| HU-047 | EP-008 | Desactivar o reactivar una cuenta | Media | 3 | Pendiente |
| HU-048 | EP-008 | Ver los roles del sistema | Media | 3 | Pendiente |
| HU-049 | EP-008 | Crear un rol | Baja | 5 | Pendiente |
| HU-050 | EP-008 | Editar un rol | Baja | 3 | Pendiente |
| HU-051 | EP-008 | Eliminar un rol | Baja | 2 | Pendiente |
| HU-052 | EP-008 | Asignar o quitar un rol | Media | 5 | Pendiente |
| HU-053 | EP-008 | Ver el sistema como una empresa | Media | 5 | Pendiente |
| HU-054 | EP-008 | Ver el sistema como otro administrador | Baja | 3 | Pendiente |
| HU-055 | EP-009 | Editar mi perfil (Administrador) | Baja | 3 | Pendiente |
| HU-056 | EP-009 | Editar mi perfil (Empresa o Colaborador) | Baja | 5 | Pendiente |
| HU-057 | EP-010 | Ver mi inicio la primera vez | Alta | 2 | Pendiente |
| HU-058 | EP-010 | Ver mi medición pendiente | Alta | 3 | Pendiente |
| HU-059 | EP-010 | Ver mi inicio sin medición pendiente | Media | 3 | Pendiente |
| HU-060 | EP-010 | Responder el diagnóstico | Alta | 8 | Pendiente |
| HU-061 | EP-010 | Revisar antes de enviar | Alta | 3 | Pendiente |
| HU-062 | EP-010 | Enviar el diagnóstico | Alta | 3 | Pendiente |
| HU-063 | EP-010 | Esperar el análisis | Alta | 5 | Pendiente |
| HU-064 | EP-010 | Ver mi resultado | Alta | 8 | Pendiente |
| HU-065 | EP-010 | Ver mis respuestas | Media | 2 | Pendiente |
| HU-066 | EP-010 | Descargar el informe en PDF | Alta | 3 | Pendiente |
| HU-067 | EP-010 | Ver mi historial | Media | 3 | Pendiente |
| HU-068 | EP-012 | Calcular los puntajes | Alta | 8 | Pendiente |
| HU-069 | EP-012 | Analizar cada categoría con la IA | Alta | 8 | Pendiente |
| HU-070 | EP-012 | Marcar mediciones vencidas | Media | 2 | Pendiente |
| HU-071 | EP-013 | Configurar las preguntas del bot | Alta | 5 | Pendiente |
| HU-072 | EP-013 | Empezar el diagnóstico por WhatsApp | Alta | 2 | Pendiente |
| HU-073 | EP-013 | Responder las preguntas del bot | Alta | 5 | Pendiente |
| HU-074 | EP-013 | Retomar la conversación | Media | 3 | Pendiente |
| HU-075 | EP-013 | Recibir el resultado por WhatsApp | Alta | 5 | Pendiente |
| HU-076 | EP-011 | Ver los colaboradores de mi empresa | Media | 2 | Pendiente |
| HU-077 | EP-011 | Crear un colaborador | Media | 3 | Pendiente |
| HU-078 | EP-011 | Cambiar la contraseña de un colaborador | Media | 2 | Pendiente |
| HU-079 | EP-011 | Desactivar o reactivar un colaborador | Baja | 2 | Pendiente |
| HU-080 | EP-007 | Entender cómo se arma el prompt final | Baja | 2 | Pendiente |
| HU-081 | EP-012 | Armar el prompt de cada categoría | Alta | 5 | Pendiente |
| HU-082 | EP-010 | Pedir una nueva medición cuando la mía venció | Media | 3 | Pendiente |
| HU-083 | EP-006 | Atender las solicitudes de nueva medición | Media | 3 | Pendiente |
| HU-084 | EP-012 | Publicar el resultado | Alta | 8 | Pendiente |

# 10. Priorización

Ordenadas por prioridad y luego por dependencias (primero las que no dependen de otras).

| ID | Historia | Épica | Prioridad | Story Points | MoSCoW | Dependencia |
|---|---|---|---|---:|---|---|
| HU-001 | Iniciar sesión | EP-001 | Alta | 5 | Must | HU-077, HU-046 |
| HU-002 | Registrar mi empresa | EP-001 | Alta | 5 | Must | HU-012 |
| HU-003 | Recuperar mi contraseña | EP-001 | Alta | 3 | Must | HU-046 |
| HU-010 | Ver los diagnósticos de un sector | EP-003 | Alta | 5 | Must | HU-012 |
| HU-012 | Crear un sector | EP-003 | Alta | 3 | Must | Ninguna |
| HU-031 | Ver la lista de empresas | EP-006 | Alta | 3 | Must | HU-032 |
| HU-038 | Ver el resultado de una empresa | EP-006 | Alta | 3 | Must | HU-064 |
| HU-041 | Editar las instrucciones generales de la IA | EP-007 | Alta | 5 | Must | HU-042, HU-043, HU-080 |
| HU-064 | Ver mi resultado | EP-010 | Alta | 8 | Must | HU-084, HU-068 |
| HU-066 | Descargar el informe en PDF | EP-010 | Alta | 3 | Should | HU-084 |
| HU-068 | Calcular los puntajes | EP-012 | Alta | 8 | Must | HU-069 |
| HU-071 | Configurar las preguntas del bot | EP-013 | Alta | 5 | Should | Ninguna |
| HU-004 | Crear una contraseña nueva | EP-001 | Alta | 3 | Must | HU-003 |
| HU-006 | Ver el inicio con indicadores | EP-002 | Alta | 5 | Must | HU-001, HU-033 |
| HU-013 | Editar un sector | EP-003 | Alta | 3 | Must | HU-012 |
| HU-017 | Ver y editar el catálogo de categorías | EP-004 | Alta | 3 | Must | HU-010 |
| HU-033 | Ver la ficha e historial de una empresa | EP-006 | Alta | 5 | Must | HU-031 |
| HU-057 | Ver mi inicio la primera vez | EP-010 | Alta | 2 | Must | HU-001, HU-002 |
| HU-069 | Analizar cada categoría con la IA | EP-012 | Alta | 8 | Must | HU-081, HU-068 |
| HU-072 | Empezar el diagnóstico por WhatsApp | EP-013 | Alta | 2 | Should | HU-071 |
| HU-007 | Filtrar las mediciones por estado | EP-002 | Alta | 3 | Must | HU-006 |
| HU-018 | Crear una categoría | EP-004 | Alta | 5 | Must | HU-017 |
| HU-020 | Crear un diagnóstico | EP-005 | Alta | 8 | Must | HU-017, HU-012 |
| HU-032 | Registrar una empresa | EP-006 | Alta | 5 | Must | HU-004, HU-012 |
| HU-073 | Responder las preguntas del bot | EP-013 | Alta | 5 | Should | HU-072 |
| HU-084 | Publicar el resultado | EP-012 | Alta | 8 | Must | HU-069, HU-068 |
| HU-023 | Ver y ordenar el contenido del diagnóstico | EP-005 | Alta | 5 | Must | HU-020 |
| HU-075 | Recibir el resultado por WhatsApp | EP-013 | Alta | 5 | Should | HU-073 |
| HU-024 | Agregar una pregunta | EP-005 | Alta | 8 | Must | HU-023 |
| HU-027 | Editar la importancia de las categorías | EP-005 | Alta | 3 | Must | HU-023 |
| HU-028 | Ver la vista previa y el estado | EP-005 | Alta | 5 | Must | HU-023 |
| HU-025 | Editar o eliminar una pregunta | EP-005 | Alta | 3 | Must | HU-024 |
| HU-029 | Publicar una versión | EP-005 | Alta | 5 | Must | HU-028 |
| HU-081 | Armar el prompt de cada categoría | EP-012 | Alta | 5 | Must | HU-041, HU-042, HU-043, HU-024 |
| HU-036 | Asignar una medición | EP-006 | Alta | 8 | Must | HU-029, HU-031 |
| HU-058 | Ver mi medición pendiente | EP-010 | Alta | 3 | Must | HU-036 |
| HU-060 | Responder el diagnóstico | EP-010 | Alta | 8 | Must | HU-058 |
| HU-061 | Revisar antes de enviar | EP-010 | Alta | 3 | Must | HU-060 |
| HU-062 | Enviar el diagnóstico | EP-010 | Alta | 3 | Must | HU-061 |
| HU-063 | Esperar el análisis | EP-010 | Alta | 5 | Must | HU-062, HU-069 |
| HU-008 | Recordar o reenviar el aviso | EP-002 | Media | 3 | Should | HU-037 |
| HU-009 | Dar una nueva fecha a una medición vencida | EP-002 | Media | 3 | Should | HU-036, HU-070 |
| HU-039 | Ver el historial de una empresa | EP-006 | Media | 3 | Should | HU-067 |
| HU-059 | Ver mi inicio sin medición pendiente | EP-010 | Media | 3 | Should | HU-064, HU-067 |
| HU-005 | Cerrar sesión | EP-001 | Media | 2 | Should | HU-001 |
| HU-011 | Ver todos los diagnósticos | EP-003 | Media | 3 | Should | HU-010 |
| HU-022 | Archivar o eliminar un diagnóstico | EP-005 | Media | 3 | Should | HU-010, HU-029 |
| HU-040 | Ver las respuestas de una empresa | EP-006 | Media | 2 | Should | HU-038 |
| HU-042 | Ajustar el prompt para un sector | EP-007 | Media | 5 | Should | HU-041 |
| HU-044 | Ver el prompt completo | EP-007 | Media | 3 | Should | HU-041 |
| HU-045 | Probar el prompt con un ejemplo | EP-007 | Media | 5 | Should | HU-041 |
| HU-065 | Ver mis respuestas | EP-010 | Media | 2 | Should | HU-064 |
| HU-067 | Ver mi historial | EP-010 | Media | 3 | Should | HU-064 |
| HU-076 | Ver los colaboradores de mi empresa | EP-011 | Media | 2 | Should | HU-002 |
| HU-014 | Reasignar las empresas de un sector | EP-003 | Media | 3 | Should | HU-013 |
| HU-015 | Desactivar un sector | EP-003 | Media | 2 | Should | HU-013 |
| HU-019 | Eliminar o archivar una categoría | EP-004 | Media | 5 | Should | HU-017, HU-027 |
| HU-034 | Editar los datos de la cuenta de una empresa | EP-006 | Media | 3 | Should | HU-033 |
| HU-035 | Desactivar o reactivar la cuenta de una empresa | EP-006 | Media | 3 | Should | HU-033 |
| HU-077 | Crear un colaborador | EP-011 | Media | 3 | Should | HU-076 |
| HU-016 | Eliminar un sector | EP-003 | Media | 3 | Should | HU-013, HU-014, HU-015 |
| HU-021 | Duplicar un diagnóstico | EP-005 | Media | 3 | Should | HU-020 |
| HU-046 | Ver las cuentas del sistema | EP-008 | Media | 3 | Should | HU-002, HU-032 |
| HU-074 | Retomar la conversación | EP-013 | Media | 3 | Could | HU-073 |
| HU-078 | Cambiar la contraseña de un colaborador | EP-011 | Media | 2 | Should | HU-077 |
| HU-026 | Agregar categorías al diagnóstico | EP-005 | Media | 3 | Should | HU-018, HU-023 |
| HU-047 | Desactivar o reactivar una cuenta | EP-008 | Media | 3 | Should | HU-046 |
| HU-048 | Ver los roles del sistema | EP-008 | Media | 3 | Should | HU-046, HU-076 |
| HU-052 | Asignar o quitar un rol | EP-008 | Media | 5 | Should | HU-046 |
| HU-053 | Ver el sistema como una empresa | EP-008 | Media | 5 | Should | HU-046 |
| HU-037 | Editar el correo de aviso de la medición | EP-006 | Media | 5 | Should | HU-036 |
| HU-070 | Marcar mediciones vencidas | EP-012 | Media | 2 | Should | HU-036 |
| HU-082 | Pedir una nueva medición cuando la mía venció | EP-010 | Media | 3 | Should | HU-070, HU-083 |
| HU-083 | Atender las solicitudes de nueva medición | EP-006 | Media | 3 | Should | HU-036, HU-082, HU-070 |
| HU-055 | Editar mi perfil (Administrador) | EP-009 | Baja | 3 | Could | HU-001 |
| HU-056 | Editar mi perfil (Empresa o Colaborador) | EP-009 | Baja | 5 | Could | HU-001, HU-077 |
| HU-080 | Entender cómo se arma el prompt final | EP-007 | Baja | 2 | Could | HU-041 |
| HU-043 | Ajustar el prompt para una empresa | EP-007 | Baja | 5 | Could | HU-041, HU-042 |
| HU-079 | Desactivar o reactivar un colaborador | EP-011 | Baja | 2 | Could | HU-076 |
| HU-030 | Ver un resultado de ejemplo | EP-005 | Baja | 3 | Could | HU-028, HU-064 |
| HU-049 | Crear un rol | EP-008 | Baja | 5 | Could | HU-048 |
| HU-054 | Ver el sistema como otro administrador | EP-008 | Baja | 3 | Could | HU-053 |
| HU-050 | Editar un rol | EP-008 | Baja | 3 | Could | HU-049 |
| HU-051 | Eliminar un rol | EP-008 | Baja | 2 | Could | HU-049 |

# 11. Dependencias

Cadenas principales:

```text
HU-012 Crear un sector ──► HU-002 Registrar mi empresa ──► HU-001 Iniciar sesión
HU-017/018 Categorías ──► HU-020 Crear diagnóstico ──► HU-023..027 Contenido ──► HU-028 Vista previa ──► HU-029 Publicar versión
HU-029 ──► HU-036 Asignar medición ──► HU-058 Ver medición ──► HU-060 Responder ──► HU-061 Revisar ──► HU-062 Enviar
HU-062 ──► HU-069 Analizar con IA ──► HU-068 Calcular puntajes ──► HU-084 Publicar resultado ──► HU-064 Ver resultado ──► HU-066 PDF
HU-041 Prompt general ──► HU-042/043 Ajustes ──► HU-081 Armar el prompt ──► HU-069
HU-077 Crear colaborador ──► HU-001 (el colaborador entra) ──► HU-056 Perfil
HU-070 Marcar vencidas ──► HU-082 Pedir nueva medición ──► HU-083 Atender la solicitud ──► HU-036
```

Dependencia por historia: ver la sección “Dependencias” de cada una y la columna “Dependencia” de la priorización.

# 12. Propuesta de Sprints

**[CAPACIDAD DEL EQUIPO PENDIENTE]** No se conoce la capacidad del equipo, así que no se arman Sprints con puntos. Se propone el orden por fases, tomando las semanas del cronograma del proyecto como referencia:

### Fase 1 — Base (semana 3)

- Historias: HU-001, HU-002, HU-003, HU-004, HU-005, HU-010, HU-011, HU-012, HU-013, HU-014, HU-015, HU-016, HU-017, HU-018, HU-019, HU-020, HU-021
- Story Points totales: 64

### Fase 2 — Diagnósticos y empresas (semana 4)

- Historias: HU-022, HU-023, HU-024, HU-025, HU-026, HU-027, HU-028, HU-029, HU-031, HU-032, HU-034
- Story Points totales: 46

### Fase 3 — Mediciones, IA y experiencia de la empresa (semana 5)

- Historias: HU-006, HU-007, HU-008, HU-009, HU-033, HU-036, HU-037, HU-041, HU-042, HU-043, HU-044, HU-045, HU-057, HU-058, HU-060, HU-061, HU-062, HU-063, HU-069, HU-070, HU-080, HU-081, HU-082, HU-083
- Story Points totales: 102

### Fase 4 — Resultados, cuentas y complementarias (semana 6)

- Historias: HU-030, HU-035, HU-038, HU-039, HU-040, HU-046, HU-047, HU-048, HU-049, HU-050, HU-051, HU-052, HU-053, HU-054, HU-055, HU-056, HU-059, HU-064, HU-065, HU-066, HU-067, HU-068, HU-076, HU-077, HU-078, HU-079, HU-084
- Story Points totales: 98

### Aplazadas — Diagnóstico por WhatsApp

- Historias: HU-071, HU-072, HU-073, HU-074, HU-075
- Story Points totales: 20
- Se retoman más adelante por decisión de Cristian.

> Las historias nuevas HU-076 a HU-084 se ubicaron en la semana que ya tenían asignada según su pantalla o función; si el cronograma cambia, esta propuesta debe revisarse.

# 13. Riesgos y bloqueadores

| Riesgo | Historias afectadas | Impacto | Recomendación |
|---|---|---|---|
| El bot de WhatsApp no tiene pantalla ni saludo definidos y está aplazado | HU-071 a HU-075 | No se puede desarrollar ni probar | Retomarlo con diseño y texto del saludo antes de planificarlo |
| La solicitud de nueva medición no tiene vista dibujada | HU-058, HU-070, HU-082, HU-083 | Dos historias no listas; flujo de vencidas incompleto | Dibujar el aviso en el inicio de la empresa y el correo al Administrador |
| El estado “Enviada” no aparece en el inicio del Administrador | HU-007, HU-062, HU-063 | El Administrador no distingue enviadas de terminadas | Agregar “Enviada” a las pestañas y a la columna de estado del prototipo |
| La IA es una dependencia externa y puede fallar | HU-045, HU-063, HU-069, HU-081 | Resultados incompletos o demorados | Mantener los 3 reintentos por categoría y el aviso por correo al Administrador |
| El rol “Consultor” aparece como ejemplo en el prototipo pero es un rol futuro | HU-048, HU-049, HU-050 | Confusión sobre qué roles existen | Cambiar el ejemplo del prototipo o confirmar que se mantiene como rol creable |
| Los colaboradores no se ven en la ficha de la empresa del Administrador | HU-033, HU-035 | Criterio sin pantalla | Agregar la lista de colaboradores (nombre y usuario) a la ficha |
| El modal de archivar o eliminar un diagnóstico no está diseñado | HU-022 | Historia no lista | Diseñar el modal de confirmación |
| No está definido el límite de pruebas de IA por día | HU-045 | Costo de llamadas a la IA | Definir un límite con NuevasTIC |
| Documentos del proyecto desactualizados (plan de trabajo, Trello, cronograma) | Todas | Contradicciones entre documentos | Actualizarlos con las decisiones confirmadas |
| Los RNF no están documentados | Todas | Sin metas medibles de rendimiento, seguridad y disponibilidad | Completar `docs/06_REQUISITOS_NO_FUNCIONALES.md` |

# 14. Preguntas abiertas

- **PA-001:** ¿Cuál es el texto exacto del saludo del bot de WhatsApp? (HU-072, aplazada)
- **PA-002:** ¿Cuándo se retoma el bot y cómo será la sección del bot en el panel? (HU-071 a HU-075)
- **PA-003:** ¿Cuántas pruebas de IA por día puede hacer el Administrador? (HU-045)
- **PA-004:** ¿El recordatorio enviado se registra y se muestra en la ficha de la empresa? (HU-008)
- **PA-005:** ¿Cómo es el modal de confirmación al archivar o eliminar un diagnóstico? (HU-022)
- **PA-006:** ¿El rol “Consultor” de ejemplo del prototipo (A5.1c) se mantiene o se cambia? (HU-049, HU-050)
  - **Respondida (6 de octubre):** se mantiene como rol **inactivo** hasta que se le asigne a alguien; se probará más adelante. Ver `17_SEGURIDAD.md`.
- **PA-007:** ¿Cuál es la capacidad del equipo por Sprint? (propuesta de Sprints)
- **PA-008:** ¿Cuáles son las metas medibles de rendimiento, disponibilidad y accesibilidad? (RNF)
- **PA-009:** ¿Cómo se ve la solicitud de nueva medición en el inicio de la empresa y cómo es el correo al Administrador? (HU-082, HU-083)

# 15. INFORMACIÓN FALTANTE Y SUPUESTOS

**Información faltante**

- **[INFORMACIÓN PENDIENTE]** Requisitos no funcionales con metas medibles (PA-008).
- **[INFORMACIÓN PENDIENTE]** Capacidad del equipo para organizar Sprints (PA-007).
- **[INFORMACIÓN PENDIENTE]** Diseño y textos del bot de WhatsApp (PA-001, PA-002).
- **[FUNCIONALIDAD POR DEFINIR]** Rol Usuario; historial de solicitudes de nueva medición.
- **Definido (6 de octubre):** el rol Consultor existe inactivo (PA-006). “Ver como” sirve para revisar lo que ve cada rol y se usa sobre Empresa, Colaborador, Consultor y roles creados, no sobre Administradores. El Colaborador puede editar su propio perfil.

**Supuestos**

- **SUP-001:** Las prioridades de HU-076 a HU-084 las propuso Claude por indicación de Cristian (“defínelo tú”); deben validarse.
- **SUP-002:** Los Story Points son una estimación inicial por análisis, no del equipo; deben re-estimarse.
- **SUP-003:** Una empresa tiene como máximo una solicitud de nueva medición abierta a la vez.
- **SUP-004:** Las tareas automáticas se redactan con el actor “Sistema” porque representan necesidades del negocio (resultados justos, publicados y a tiempo), no tareas técnicas.
- **SUP-005:** Los RNF y la clasificación MoSCoW se desprendieron de las historias y del prototipo; no existían en la documentación.

# 16. Inconsistencias detectadas

| ID | Problema | Impacto | Recomendación |
|---|---|---|---|
| INC-001 | **[INCONSISTENCIA DETECTADA]** El plan de trabajo, el Trello y el cronograma todavía mencionan Usuario, Consultor, FODA y el bot de WhatsApp con IA. | Contradicen las decisiones confirmadas. | Actualizarlos (no se modificaron). |
| INC-002 | **[INCONSISTENCIA DETECTADA]** El prototipo muestra un rol de ejemplo “Consultor” (A5.1c) y no dibuja el estado “Enviada”. | El prototipo no refleja lo confirmado. | Ajustar el prototipo. |
| INC-003 | Resueltas con Cristian: FODA fuera; estados Enviada y Terminada; contraseña con número; vencida → solicitud de nueva medición; WhatsApp separado y aplazado; Consultor y Usuario futuros. | — | Mantener este documento como fuente. |

# 17. Revisión INVEST

Todas las historias son independientes en lo posible, negociables y aportan valor. Las siguientes necesitan atención:

| Historia | Story Points | Observación |
|---|---:|---|
| HU-007 — Filtrar las mediciones por estado | 3 | Estimable/Testable limitada: falta dibujar en el prototipo el estado "Enviada" en las pestañas. |
| HU-008 — Recordar o reenviar el aviso | 3 | Estimable/Testable limitada: falta validar si el registro de envío se muestra en la ficha de la empresa. |
| HU-020 — Crear un diagnóstico | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-022 — Archivar o eliminar un diagnóstico | 3 | Estimable/Testable limitada: falta diseñar el modal de confirmación en el prototipo. |
| HU-024 — Agregar una pregunta | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-036 — Asignar una medición | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-045 — Probar el prompt con un ejemplo | 5 | Estimable/Testable limitada: definir el límite de pruebas por día. |
| HU-049 — Crear un rol | 5 | Estimable/Testable limitada: confirmar si “Consultor” es un rol creable o solo a futuro. |
| HU-060 — Responder el diagnóstico | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-064 — Ver mi resultado | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-068 — Calcular los puntajes | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-069 — Analizar cada categoría con la IA | 8 | Small: es grande (8); revisar si conviene dividirla |
| HU-071 — Configurar las preguntas del bot | 5 | Estimable/Testable limitada: aplazada; falta definir y diseñar la sección del bot en el panel (sin vista en el prototipo). |
| HU-072 — Empezar el diagnóstico por WhatsApp | 2 | Estimable/Testable limitada: aplazada; falta definir el texto exacto del saludo del bot. |
| HU-073 — Responder las preguntas del bot | 5 | Estimable/Testable limitada: aplazada; falta definir el saludo del bot (HU-072) y la sección de preguntas del bot en el panel (HU-071). |
| HU-074 — Retomar la conversación | 3 | Estimable/Testable limitada: aplazada; falta definir el saludo del bot (HU-072) y la sección de preguntas del bot en el panel (HU-071), de los que depende la conversación. |
| HU-075 — Recibir el resultado por WhatsApp | 5 | Estimable/Testable limitada: aplazada; falta definir el saludo y las preguntas del bot (HU-071 y HU-072), de los que depende el resultado. |
| HU-082 — Pedir una nueva medición cuando la mía venció | 3 | Estimable/Testable limitada: falta dibujar la pantalla en el prototipo |
| HU-083 — Atender las solicitudes de nueva medición | 3 | Estimable/Testable limitada: falta dibujar la pantalla en el prototipo |
| HU-084 — Publicar el resultado | 8 | Small: es grande (8); revisar si conviene dividirla |

# 18. Revisión final de cobertura

### Requisitos cubiertos

Los 40 requisitos funcionales (RF-001 a RF-040) tienen al menos una historia. Las 79 vistas del prototipo tienen al menos una historia, salvo el bot de WhatsApp y las solicitudes de nueva medición, que no tienen vista.

### Requisitos parcialmente cubiertos

Ninguno. Las historias HU-071 a HU-075 (bot) cubren sus requisitos pero están **aplazadas**.

### Requisitos sin historia

Ninguno.

### Historias duplicadas o sin requisito

Ninguna historia sin requisito. Las historias del Administrador y de la empresa que muestran el mismo resultado (HU-038/039/040 y HU-064/065/067) se mantienen separadas porque son actores y pantallas distintas.

### Resumen final del backlog

- **Total de Épicas:** 13
- **Total de Historias:** 84
- **Total de Story Points:** 330
- **Historias de prioridad Alta:** 40
- **Historias de prioridad Media:** 34
- **Historias de prioridad Baja:** 10
- **Historias bloqueadas:** 0
- **Historias pendientes de información (NO LISTA):** 12 (HU-007, HU-008, HU-022, HU-045, HU-049, HU-071, HU-072, HU-073, HU-074, HU-075, HU-082, HU-083)
- **Requisitos sin cobertura:** 0
