import type {
    EvolucionCategoria,
    MedicionFicha,
    PendienteFicha,
    PuntoEvolucion,
} from '@/types/empresas';

/**
 * E8 · Mi historial y A3.4 · Historial de una empresa (HU-067, HU-039). Lo
 * arma `App\Support\DatosHistorial`.
 */
export type Historial = {
    empresa: { id: number; nombre: string; sector: string | null };
    pendiente: Omit<PendienteFicha, 'ultimo_aviso'> | null;
    /** Solo las terminadas con resultado, de la más reciente a la primera. */
    mediciones: MedicionFicha[];
    /**
     * Vencidas sin enviarse que después se cancelaron o reemplazaron, de la
     * más reciente a la primera: prueban que la empresa recibió el
     * diagnóstico (10 de noviembre de 2026).
     */
    vencidas: MedicionVencida[];
    /** Cifras de arriba: última, cambio desde la primera y la mejor; null sin mediciones. */
    resumen?: {
        cantidad: number;
        ultima: MedicionFicha;
        primera: MedicionFicha;
        /** Puntos de la última frente a la primera; null con una sola. */
        cambio: number | null;
        mejor: MedicionFicha;
    } | null;
    evolucion: PuntoEvolucion[];
    evolucion_categorias: EvolucionCategoria[];
    /** Lo abre el equipo de NuevasTIC (A3.4). */
    interno: boolean;
};

export type MedicionVencida = {
    id: number;
    numero: number;
    diagnostico: string | null;
    version: number | null;
    /** Fechas "AAAA-MM-DD" en la zona de la empresa. */
    asignada: string | null;
    vence: string | null;
    cancelada: string | null;
    respondidas: number;
    preguntas: number;
    /** Último correo de aviso o recordatorio que salió; null si no salió ninguno. */
    ultimo_aviso: string | null;
    /** Solo en la vista interna (A3.4). */
    asignada_por: string | null;
    cancelada_por: string | null;
};
