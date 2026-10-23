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
    evolucion: PuntoEvolucion[];
    evolucion_categorias: EvolucionCategoria[];
    /** Lo abre el equipo de NuevasTIC (A3.4). */
    interno: boolean;
};
