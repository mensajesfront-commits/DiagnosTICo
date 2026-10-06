/**
 * Datos que el backend entrega a las pantallas de Diagnósticos (A2, A2·T,
 * A2b, A2.3, A2.5). Contrato completo en docs/14_FRONTEND.md.
 */

export type Sector = {
    id: number;
    nombre: string;
    descripcion: string | null;
    activo: boolean;
    /** Diagnósticos activos (no archivados) del sector. */
    diagnosticos: number;
};

export type MedicionesPorEstado = {
    en_curso: number;
    no_iniciada: number;
    vencida: number;
    terminada: number;
};

export type ResumenDiagnosticos = {
    empresas: number;
    mediciones: number;
    publicados: number;
    borradores: number;
    /** Última medición de cada empresa, contada por estado. */
    ultima_medicion: MedicionesPorEstado;
    /** Solo en "Todos": sectores activos. */
    sectores_activos?: number;
};

export type EstadoDiagnostico = 'publicado' | 'borrador';

export type FilaDiagnostico = {
    id: number;
    nombre: string;
    sector_id: number;
    sector_nombre: string;
    categorias: number;
    preguntas: number;
    empresas: number;
    mediciones: number;
    /** "publicado" si tiene alguna versión publicada; "borrador" si nunca se publicó. */
    estado: EstadoDiagnostico;
    /** Número de la última versión publicada (v2); null si nunca se publicó. */
    version_publicada: number | null;
    /** Número del borrador en curso sobre una versión publicada (v3); null si no hay. */
    borrador_pendiente: number | null;
    /** Fecha ISO de la última edición, para ordenar en "Todos". */
    actualizado_en: string;
};

export type EstadoMedicion =
    | 'no_iniciada'
    | 'en_curso'
    | 'enviada'
    | 'terminada'
    | 'vencida';

export type EmpresaDelSector = {
    id: number;
    nombre: string;
    /** "Diagnóstico general v2". */
    diagnostico: string | null;
    medicion: {
        numero: number;
        estado: EstadoMedicion;
        avance: { hechas: number; total: number } | null;
    } | null;
    /** Puntaje del último resultado; null si no tiene. */
    puntaje: number | null;
};

export type Categoria = {
    id: number;
    nombre: string;
    descripcion: string | null;
    /** Diagnósticos donde se usa. */
    diagnosticos: number;
    /** true si alguna empresa ya la respondió: se archiva en vez de borrarse (RN-009). */
    tiene_respuestas: boolean;
    archivada: boolean;
};

export type DiagnosticoBorrador = {
    id: number;
    nombre: string;
    sector_nombre: string;
};

export type DiagnosticoPublicado = {
    id: number;
    nombre: string;
    sector_id: number;
    sector_nombre: string;
    version: number;
    preguntas: number;
    categorias: string[];
};
