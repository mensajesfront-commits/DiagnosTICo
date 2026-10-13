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
    estado: EstadoMedicionInicio;
    /** Categorías completas: "6/10". */
    avance: { hechas: number; total: number } | null;
    /** ISO de la última respuesta guardada. */
    ultimo_avance: string | null;
    /** ISO del último aviso por correo. */
    ultimo_aviso: string | null;
    resultado: { puntaje: number; nivel: Exclude<Nivel, 'sin'> } | null;
    /** En terminadas: número de la medición pendiente de la empresa. */
    pendiente_numero: number | null;
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
