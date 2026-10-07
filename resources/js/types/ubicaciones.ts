/** País que se ofrece en las listas (App\Support\Ubicaciones). */
export type Pais = {
    codigo: string;
    nombre: string;
    /** Cómo se llama su división: Departamento, Estado, Provincia o Región. */
    division: string;
};

/** Lo que devuelve GET /ubicaciones/{pais}. */
export type Departamento = { nombre: string; ciudades: string[] };
