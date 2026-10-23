import type { Nivel } from '@/lib/niveles';

/** A1 · Inicio del Administrador (HU-006, HU-007). Lo arma `DatosInicio`. */

/** Estado que se muestra: "vencida" también si pasó la fecha límite. */
export type EstadoMedicionInicio =
    | 'no_iniciada'
    | 'en_curso'
    | 'enviada'
    | 'terminada'
    | 'vencida';

export type IndicadoresInicio = {
    empresas: number;
    /** Sectores que tienen al menos una empresa ("en 6 sectores"). */
    sectores_con_empresas: number;
    pendientes: number;
    pendientes_por_estado: {
        no_iniciada: number;
        en_curso: number;
        vencida: number;
    };
    /** Mediciones terminadas este mes. */
    completados_mes: number;
    /** Promedio del último resultado de cada empresa; null si no hay. */
    puntaje_promedio: number | null;
};

export type FilaMedicion = {
    id: number;
    numero: number;
    empresa_id: number;
    empresa: string;
    sector: string;
    /** Fechas "AAAA-MM-DD". */
    asignada: string;
    vence: string | null;
    /** "Nueva fecha": la más cercana que se acepta (3 días, hora de la empresa). */
    fecha_minima: string;
    estado: EstadoMedicionInicio;
    /** Categorías completas: "6/10". */
    avance: { hechas: number; total: number } | null;
    /** ISO de la última respuesta guardada. */
    ultimo_avance: string | null;
    /** ISO del último aviso por correo. */
    ultimo_aviso: string | null;
    resultado: { puntaje: number; nivel: Exclude<Nivel, 'sin'> } | null;
    /** En terminadas: la medición pendiente de la empresa, si tiene. */
    pendiente: {
        numero: number;
        estado: 'no_iniciada' | 'en_curso' | 'enviada';
    } | null;
    /** En vencidas: ISO de la solicitud de medición nueva abierta (HU-083). */
    solicitud: string | null;
};

export type SectorInicio = {
    id: number;
    nombre: string;
    empresas: number;
    /** Diagnósticos publicados del sector. */
    publicados: number;
    /** Versión publicada cuando hay un solo diagnóstico ("v2"). */
    version: number | null;
};
