import type { EstadoMedicion } from '@/components/base/EtiquetaEstado.vue';
import type { Nivel } from '@/lib/niveles';

/**
 * E1 · Inicio de la empresa y E2–E4 · Responder (HU-057 a HU-063). Lo arman
 * `DatosInicioEmpresa` y `DatosResponder`. La empresa nunca recibe los
 * puntajes de las opciones ni las indicaciones para la IA.
 */

export type CabeceraMedicion = {
    id: number;
    numero: number;
    diagnostico: string;
    sector: string;
    fecha_limite: string | null;
};

export type PreguntaResponder = {
    ref: string;
    texto: string;
    tipo: 'abierta' | 'opcion_unica' | 'seleccion_multiple';
    indicacion: string;
    obligatoria: boolean;
    /** `exclusiva`: "Ninguna"; al marcarla se desmarcan las demás. */
    opciones: { ref: string; texto: string; exclusiva: boolean }[];
};

export type CategoriaResponder = {
    ref: string;
    nombre: string;
    preguntas: PreguntaResponder[];
};

export type RespuestaGuardada = { opciones: string[]; texto: string };

export type AvanceCategoria = {
    ref: string;
    nombre: string;
    respondidas: number;
    total: number;
    faltan_obligatorias: number;
};

export type Avance = {
    categorias: AvanceCategoria[];
    respondidas: number;
    total: number;
    porcentaje: number;
    /** Índice de la primera categoría con obligatorias sin responder. */
    actual: number;
    completo: boolean;
};

export type PendienteEmpresa = {
    id: number;
    numero: number;
    estado: EstadoMedicion;
    /** Primera medición de la empresa. */
    primera: boolean;
    diagnostico: string;
    categorias: number;
    preguntas: number;
    mensaje: string | null;
    fecha_limite: string | null;
    dias_restantes: number | null;
    avance: {
        respondidas: number;
        total: number;
        porcentaje: number;
        categoria: string | null;
        categoria_numero: number;
    };
    /** HU-082: ya pidió una medición nueva. */
    solicitud_abierta: boolean;
};

export type MedicionTerminada = {
    id: number;
    numero: number;
    fecha: string | null;
    puntaje: number;
    nivel: Exclude<Nivel, 'sin'>;
    variacion: number | null;
};

export type AreaATrabajar = {
    nombre: string;
    puntaje: number;
    nivel: Exclude<Nivel, 'sin'>;
    recomendacion: string | null;
};

/** Props de E1. */
export type InicioEmpresa = {
    nombre: string;
    empresa: { nombre: string; sector: string };
    realizadas: number;
    pendiente: PendienteEmpresa | null;
    ultima: MedicionTerminada | null;
    /** Terminadas, de la más reciente a la primera. */
    mediciones: MedicionTerminada[];
    areas: AreaATrabajar[];
    soporte: string;
};
