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

export type PreguntaResultado = {
    ref: string;
    texto: string;
    tipo: 'abierta' | 'opcion_unica' | 'seleccion_multiple';
    /** Opciones elegidas separadas por " · " o el texto; null si no respondió. */
    respuesta: string | null;
    puntaje: number | null;
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
    recomendacion: Recomendacion;
    preguntas: PreguntaResultado[];
};

export type Prioridad = {
    ref: string;
    nombre: string;
    puntaje: number;
    nivel: Exclude<Nivel, 'sin'>;
    recomendacion: Recomendacion;
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
