/**
 * URLs del módulo de Diagnósticos que usa el frontend.
 *
 * [INFORMACIÓN PENDIENTE] Las rutas del backend todavía no existen (T-049,
 * T-050, T-074…). Estas son las URLs propuestas en docs/15_BACKEND.md. Cuando
 * Luis las cree, se reemplazan por las funciones que genera Wayfinder
 * (`@/routes/...`) y se borra este archivo.
 */

export const rutas = {
    diagnosticos: {
        todos: () => '/diagnosticos',
        sector: (sectorId: number) => `/diagnosticos?sector=${sectorId}`,
        crear: (sectorId?: number) =>
            sectorId
                ? `/diagnosticos/crear?sector=${sectorId}`
                : '/diagnosticos/crear',
        guardar: () => '/diagnosticos',
        editar: (id: number) => `/diagnosticos/${id}/editar`,
        vistaPrevia: (id: number) => `/diagnosticos/${id}/vista-previa`,
        duplicar: (id: number) => `/diagnosticos/${id}/duplicar`,
        archivar: (id: number) => `/diagnosticos/${id}/archivar`,
        eliminar: (id: number) => `/diagnosticos/${id}`,
        eliminarBorrador: (id: number) => `/diagnosticos/${id}/borrador`,
    },
    sectores: {
        crear: () => '/sectores',
        actualizar: (id: number) => `/sectores/${id}`,
        reasignar: (id: number) => `/sectores/${id}/reasignar`,
        desactivar: (id: number) => `/sectores/${id}/desactivar`,
        reactivar: (id: number) => `/sectores/${id}/reactivar`,
        eliminar: (id: number) => `/sectores/${id}`,
    },
    categorias: {
        catalogo: () => '/categorias',
        crear: () => '/categorias',
        actualizar: (id: number) => `/categorias/${id}`,
        eliminar: (id: number) => `/categorias/${id}`,
        archivar: (id: number) => `/categorias/${id}/archivar`,
    },
    empresas: {
        lista: (sectorId?: number) =>
            sectorId ? `/empresas?sector=${sectorId}` : '/empresas',
        ver: (id: number) => `/empresas/${id}`,
    },
};
