# Catálogo CIIU Rev. 5 A.C.

- **Fuente:** DANE, *Clasificación Industrial Internacional Uniforme de todas las actividades económicas, Revisión 5 Adaptada para Colombia (CIIU Rev. 5 A.C.)*, estructura detallada. Es el archivo `CIIU_5_AC_Estructura_detallada.xlsx`, tal como se descargó.
- **Nombres cortos:** `CIIU_5_AC_Estructura_detallada_divisiones_cortas.xlsx` es el mismo archivo con los mismos códigos, pero con nombres más cortos para mostrar en el sistema (lo preparó el equipo, 9 de octubre de 2026). Cambia 71 divisiones y 88 clases; las demás son iguales al oficial.
- **`ciiu-rev5-ac.json`:** lo que carga `CiiuSeeder`. Tiene 22 secciones, 87 divisiones y 544 clases. Cada división y clase lleva `nombre` (el corto, que se muestra) y `nombre_oficial` (el del DANE). Los nombres solo se limpiaron de espacios de más.
- **Uso en el sistema:** un sector se puede asociar a una división (dos dígitos). Al hacerlo, todas las clases de esa división (cuatro dígitos) pasan a ser sus subsectores, es decir, las actividades económicas que se eligen en el registro (DEC-018).

Si el DANE publica una versión nueva, se reemplazan los dos Excel, se vuelve a generar el JSON (una fila por sección, división y clase; columnas «Categoría», «Código» y «Título») y se corre `php artisan db:seed --class=CiiuSeeder`.
