import type { EstadoMedicion } from '@/components/base/EtiquetaEstado.vue';
import type { Nivel } from '@/lib/niveles';
import type { Pais } from '@/types/ubicaciones';

/**
 * A3 · Empresas, A3.1 · Ficha, A3.2 · Registrar y A3.1b · Asignar medición
 * (HU-031 a HU-036). Lo arma `App\Support\DatosEmpresas`; puntajes, niveles,
 * variación y avance llegan calculados.
 */

export type Avance = { hechas: number; total: number };

/** Medición pendiente (no iniciada, en curso, enviada o vencida). */
export type PendienteResumen = {
    numero: number;
    estado: EstadoMedicion;
    avance: Avance | null;
    /** ISO de la solicitud de medición nueva abierta (HU-083). */
    solicitud: string | null;
};

/** Fila de A3. */
export type FilaEmpresa = {
    id: number;
    nombre: string;
    sector_id: number;
    sector: string | null;
    puntaje: number | null;
    nivel: Nivel;
    variacion: number | null;
    /** Mediciones terminadas. */
    mediciones: number;
    pendiente: PendienteResumen | null;
    activa: boolean;
};

export type SectorFiltro = { id: number; nombre: string };

/** Diagnóstico publicado que se puede asignar (RN-014). */
export type DiagnosticoAsignable = {
    id: number;
    nombre: string;
    version: number;
    categorias: number;
    preguntas: number;
};

export type OpcionesEmpresa = {
    sectores: {
        id: number;
        nombre: string;
        activo: boolean;
        actividades: { id: number; codigo: string; nombre: string }[];
        diagnosticos: DiagnosticoAsignable[];
    }[];
    paises: Pais[];
    empleados: string[];
};

export type DatosEmpresa = {
    id: number;
    nombre: string;
    descripcion: string | null;
    sector_id: number;
    sector: string | null;
    actividad_economica_id: number | null;
    pais: string;
    departamento: string | null;
    ciudad: string;
    telefono: string | null;
    sitio_web: string | null;
    numero_empleados: string | null;
    activa: boolean;
    /** ISO. */
    desactivada_en: string | null;
    motivo_desactivacion: string | null;
    registrada_en: string | null;
    registrada_por: string | null;
};

export type UsuarioPrincipal = {
    id: number;
    name: string;
    cargo: string | null;
    email: string;
    telefono: string | null;
    /** Todavía no crea su contraseña. */
    invitacion_pendiente: boolean;
    ultimo_acceso: string | null;
};

export type PendienteFicha = PendienteResumen & {
    id: number;
    diagnostico: string | null;
    version: number | null;
    /** Fechas "AAAA-MM-DD". */
    asignada: string | null;
    vence: string | null;
    tiene_respuestas: boolean;
    /** ISO del último aviso por correo. */
    ultimo_aviso: string | null;
};

export type MedicionFicha = {
    id: number;
    numero: number;
    asignada: string | null;
    terminada: string | null;
    version: number | null;
    diagnostico: string | null;
    estado: EstadoMedicion;
    puntaje: number | null;
    nivel: Exclude<Nivel, 'sin'> | null;
    /** Frente a la terminada anterior (RN-020); null en la primera. */
    variacion: number | null;
};

export type PuntoEvolucion = { numero: number; fecha: string; puntaje: number };

/** Evolución de una categoría, para "Ver:" de la gráfica. */
export type EvolucionCategoria = { nombre: string; puntos: PuntoEvolucion[] };

export type Colaborador = {
    id: number;
    name: string;
    email: string;
    cargo: string | null;
    activo: boolean;
};

/** Props de A3.1. */
export type FichaEmpresa = {
    empresa: DatosEmpresa;
    principal: UsuarioPrincipal | null;
    ultimo: {
        numero: number;
        puntaje: number;
        nivel: Exclude<Nivel, 'sin'>;
    } | null;
    variacion: number | null;
    pendiente: PendienteFicha | null;
    mediciones: MedicionFicha[];
    evolucion: PuntoEvolucion[];
    evolucion_categorias: EvolucionCategoria[];
    colaboradores: Colaborador[];
    diagnosticos: DiagnosticoAsignable[];
    opciones: OpcionesEmpresa;
};

/** Empresa que se ofrece en A3.1b. */
export type EmpresaAsignable = {
    id: number;
    nombre: string;
    sector_id: number;
    sector: string | null;
    pendiente: {
        id: number;
        numero: number;
        estado: EstadoMedicion;
        vence: string | null;
        diagnostico: string | null;
        version: number | null;
        tiene_respuestas: boolean;
    } | null;
    /** ISO de la solicitud de medición nueva abierta (HU-083). */
    solicitud: string | null;
    /** Número que tendrá la nueva medición. */
    siguiente: number;
    /** Usuario principal: "Para:" y {nombre_usuario} del correo. */
    principal: { name: string; email: string } | null;
};

export type DiagnosticosDelSector = {
    publicados: DiagnosticoAsignable[];
    borradores: { id: number; nombre: string }[];
};
