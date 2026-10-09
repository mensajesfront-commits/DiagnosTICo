# Catálogo CIIU Rev. 5 A.C.

- **Fuente:** DANE, *Clasificación Industrial Internacional Uniforme de todas las actividades económicas, Revisión 5 Adaptada para Colombia (CIIU Rev. 5 A.C.)*, estructura detallada. Es el archivo `CIIU_5_AC_Estructura_detallada.xlsx`, tal como se descargó.
- **`ciiu-rev5-ac.json`:** el mismo contenido en JSON, que es lo que carga `CiiuSeeder`. Tiene 22 secciones, 87 divisiones y 544 clases. Los nombres solo se limpiaron de espacios de más.
- **Uso en el sistema:** un sector se puede asociar a una división (dos dígitos). Al hacerlo, todas las clases de esa división (cuatro dígitos) pasan a ser sus subsectores, es decir, las actividades económicas que se eligen en el registro (DEC-018).

Si el DANE publica una versión nueva, se reemplaza el Excel, se vuelve a generar el JSON (una fila por sección, división y clase; columnas «Categoría», «Código» y «Título») y se corre `php artisan db:seed --class=CiiuSeeder`.
