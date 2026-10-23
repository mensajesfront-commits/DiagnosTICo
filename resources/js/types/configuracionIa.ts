/**
 * A4 · Configuración de la IA, etapa "Analizar categoría" (HU-041 a HU-045).
 * Lo arma `App\Support\DatosConfiguracionIa`.
 */

export type ElementoPrompt = 'contexto' | 'tarea' | 'detalles' | 'ejemplos';

export type Alcance = 'general' | 'sector' | 'empresa';

/** "usar" deja el texto de la capa anterior; "agregar" suma al final; "reemplazar" lo sustituye. */
export type Modo = 'usar' | 'agregar' | 'reemplazar';

export type Textos = Record<ElementoPrompt, string>;

export type Ajuste = {
    modos: Record<ElementoPrompt, Modo>;
    textos: Textos;
};

export type SectorIa = {
    id: number;
    nombre: string;
    empresas: number;
    con_ajuste: boolean;
};

export type EmpresaIa = {
    id: number;
    nombre: string;
    sector_id: number;
    sector: string | null;
    con_ajuste: boolean;
};

export type EjemploIa = {
    medicion_id: number;
    numero: number;
    empresa_id: number;
    empresa: string;
    sector_id: number | null;
    sector: string;
    categorias: { ref: string; nombre: string }[];
};

export type VariableIa = {
    codigo: string;
    nombre: string;
    elemento: ElementoPrompt;
};

export type ConfiguracionIa = {
    alcance: Alcance;
    general: { textos: Textos; originales: Textos };
    sector:
        | (SectorIa & { empresas_con_ajuste: string[]; ajuste: Ajuste | null })
        | null;
    empresa:
        | (EmpresaIa & { sector_con_ajuste: boolean; ajuste: Ajuste | null })
        | null;
    /** Lo que da "usar": el general (sector) o general + sector (empresa). */
    base: Textos;
    origen_base: Record<ElementoPrompt, 'general' | 'sector'>;
    ultima_edicion: { fecha: string; por: string | null } | null;
    sectores: SectorIa[];
    empresas: EmpresaIa[];
    resumen: { sectores_con_ajuste: number; empresas_con_ajuste: number };
    variables: VariableIa[];
    formato: { texto: string; esquema: string };
    ejemplos: EjemploIa[];
};

/** Cambios sin guardar de la capa que se edita (vista previa y prueba). */
export type Borrador = {
    alcance: Alcance;
    id: number | null;
    modos: Record<ElementoPrompt, Modo>;
    textos: Textos;
};

export type PiezaPrompt = {
    texto: string;
    origen: 'general' | 'sector' | 'empresa';
    /** Dato que el sistema insertó solo ({sector}, {categoria}…). */
    dato: boolean;
};

export type PreguntaPrompt = {
    id: string;
    tipo: 'abierta' | 'opcion_unica' | 'seleccion_multiple';
    texto: string;
    respuesta: string;
    puntaje: number | null;
    ten_en_cuenta?: string;
    criterio?: string;
};

export type VistaPrevia = {
    ejemplo: { empresa: string; sector: string; categoria: string };
    partes: {
        elemento: ElementoPrompt;
        titulo: string;
        piezas: PiezaPrompt[];
    }[];
    preguntas: PreguntaPrompt[];
    formato: string;
    texto_exacto: string;
    palabras: number;
};

export type ResultadoPrueba = {
    preguntas: PreguntaPrompt[];
    resultado: {
        preguntas: {
            id: string;
            puntaje: number | null;
            observacion: string;
        }[];
        metodo: string;
        metodo_motivo: string;
        observacion_categoria: string;
        recomendacion: { titulo: string; detalle: string };
    };
    /** Pruebas que quedan hoy (100 al día entre todos, PA-003). */
    restantes: number;
};
