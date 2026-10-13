/**
 * A2.1 · Editor del diagnóstico, A2.1c · Editar importancia y A2.4 · Vista
 * previa (HU-023 a HU-029). Lo arman `App\Support\DatosEditor` y
 * `EditorDiagnosticoController`; el estado lo calcula `EstadoDiagnostico`.
 */

export type TipoPregunta = 'abierta' | 'opcion_unica' | 'seleccion_multiple';

export type OpcionEditor = {
    id: number | null;
    texto: string;
    puntaje: number;
    ten_en_cuenta: string | null;
};

export type PreguntaEditor = {
    id: number;
    texto: string;
    tipo: TipoPregunta;
    indicacion: string;
    criterio_ia: string | null;
    obligatoria: boolean;
    /** Lo que le falta ("Falta la indicación de cómo responder"). */
    problemas: string[];
    opciones: OpcionEditor[];
};

export type ParteEditor = {
    /** Id de la categoría dentro del diagnóstico. */
    id: number;
    categoria_id: number;
    nombre: string;
    importancia: number;
    problemas: string[];
    preguntas: PreguntaEditor[];
};

export type Requisito = {
    clave: string;
    texto: string;
    ok: boolean;
    detalle: string | null;
};

export type EstadoEditor = {
    publicable: boolean;
    requisitos: Requisito[];
    completas: number;
    total: number;
    por_corregir: number;
    importancia: number;
};

export type CabeceraEditor = {
    id: number;
    nombre: string;
    sector: { id: number; nombre: string };
    estado: 'borrador' | 'publicado' | 'archivado';
    /** El borrador que se edita (v3). */
    version: number;
    /** Ya hay cambios sin publicar. */
    hay_borrador: boolean;
    ultima_publicada: number | null;
    publicable: boolean;
    guardado_en: string | null;
};

export type VersionHistorial = {
    numero: number;
    estado: 'borrador' | 'publicada' | 'reemplazada';
    publicada_en: string | null;
    nota: string | null;
    empresas: number;
    mediciones: number;
};

export type CategoriaCatalogo = {
    id: number;
    nombre: string;
    archivada: boolean;
    en_diagnostico: boolean;
};

/** Props de A2.1. */
export type DatosEditor = {
    diagnostico: CabeceraEditor;
    partes: ParteEditor[];
    estado: EstadoEditor;
    catalogo: CategoriaCatalogo[];
    versiones: VersionHistorial[];
    /** Empresas con medición pendiente en una versión publicada. */
    pendientes: number;
    parteInicial: number | null;
    preguntaInicial: number | null;
};

/** A2.4 · una categoría como la ve la empresa. */
export type CategoriaVistaPrevia = {
    ref: string;
    parte_id: number | null;
    nombre: string;
    importancia: number;
    problemas: string[];
    preguntas: {
        ref: string;
        id: number;
        texto: string;
        tipo: TipoPregunta;
        indicacion: string;
        obligatoria: boolean;
        problemas: string[];
        opciones: { ref: string; texto: string }[];
    }[];
};
