# Países, departamentos y ciudades

Archivos `{ISO}.json` de los 18 países de Hispanoamérica (DEC-016): una lista de departamentos (o estados, provincias o regiones), cada uno con sus ciudades.

```json
[{ "nombre": "Antioquia", "ciudades": ["Abejorral", "Abriaquí", "…"] }]
```

## Fuente y licencia

Los datos vienen de [countries-states-cities-database](https://github.com/dr5hn/countries-states-cities-database) (archivo `json/countries+states+cities.json`, descargado el 8 de octubre de 2026), publicada con la licencia **Open Database License (ODbL) 1.0**. Esta carpeta es una base derivada y se comparte con la misma licencia.

## Cambios frente a la fuente

- Solo los países de `App\Support\Ubicaciones::PAISES`.
- Nombres en inglés pasados al español ("Havana" → "La Habana", "Mexico City" → "Ciudad de México", "Ameca Municipality" → "Ameca"…). La lista está en `app/Console/Commands/GenerarUbicaciones.php`.
- Sin las regiones de desarrollo de República Dominicana (no son provincias).
- Orden alfabético sin que las tildes manden al final.

## Regenerar

```bash
curl -L -o storage/app/csc.json https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries%2Bstates%2Bcities.json
php artisan ubicaciones:generar storage/app/csc.json
rm storage/app/csc.json
```

**[INFORMACIÓN PENDIENTE]** Las listas públicas no traen todos los municipios o corregimientos; por eso la ciudad también se puede escribir.
