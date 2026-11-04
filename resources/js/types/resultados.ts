import type { Nivel } from '@/lib/niveles';

/**
 * E6 / A3.3 · Resultado y E9 / A3.5 · Mis respuestas (HU-064, HU-065). Lo
 * arma `App\Support\DatosResultado`: puntajes, niveles y variaciones llegan
 * calculados y no cambian (RN-023).
 */

export type CabeceraResultado = {
    id: number;
    numero: number;
    empresa: { id: number; nombre: string };
    sector: string;
    diagnostico: string;
    version: number;
    enviada_en: string | null;
    terminada_en: string | null;
};

export type Recomendacion = { titulo: string; detalle: string };

/** A2.6 · Resultado de ejemplo de un diagnóstico (HU-030). */
export type EjemploResultado = {
    caso: 'mejor' | 'medio' | 'peor';
    /** "Borrador v3" o "Versión 2". */
    etiqueta: string;
    /** URL de la vista previa. */
    volver: string;
    /** URL del PDF de ejemplo (se abre en otra pestaña). */
    pdf: string;
};

/** Método con el que la IA analizó la categoría; null en resultados viejos. */
export type MetodoAnalisis = { clave: string; nombre: string; motivo: string };

export type PreguntaResultado = {
    ref: string;
    texto: string;
    tipo: 'abierta' | 'opcion_unica' | 'seleccion_multiple';
    /** Opciones elegidas separadas por " · " o el texto; null si no respondió. */
    respuesta: string | null;
    puntaje: number | null;
    /** Opcional: dato extra que no cuenta en el puntaje (no viene en resultados viejos). */
    extra?: boolean;
    observacion: string | null;
};

export type CategoriaResultado = {
    ref: string;
    nombre: string;
    importancia: number;
    puntaje: number;
    nivel: Exclude<Nivel, 'sin'>;
    /** Puntaje en la medición anterior; null en la primera. */
    anterior: number | null;
    variacion: number | null;
    observacion: string;
    metodo: MetodoAnalisis | null;
    recomendacion: Recomendacion;
    preguntas: PreguntaResultado[];
};

export type Prioridad = {
    ref: string;
    nombre: string;
    puntaje: number;
    nivel: Exclude<Nivel, 'sin'>;
    recomendacion: Recomendacion;
    metodo: MetodoAnalisis | null;
};

export type Resultado = {
    puntaje: number;
    nivel: Exclude<Nivel, 'sin'>;
    frase: string;
    variacion: number | null;
    anterior: { numero: number; fecha: string; puntaje: number } | null;
    publicado_en: string;
    tiene_pdf: boolean;
    categorias: CategoriaResultado[];
    /** Las 3 categorías más débiles con su recomendación. */
    prioridades: Prioridad[];
};
