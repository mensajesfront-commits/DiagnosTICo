/**
 * URLs de los módulos que usa el frontend (Diagnósticos, Usuarios y roles…).
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
        eliminarVarios: () => '/diagnosticos',
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
        restaurar: (id: number) => `/categorias/${id}/restaurar`,
    },
    usuarios: {
        lista: (rol?: string) =>
            rol ? `/usuarios?rol=${encodeURIComponent(rol)}` : '/usuarios',
        roles: (rolId?: number) =>
            rolId ? `/usuarios/roles?rol=${rolId}` : '/usuarios/roles',
        invitar: () => '/usuarios/invitar',
        reenviarInvitacion: (id: number) => `/usuarios/${id}/invitacion`,
        desactivar: (id: number) => `/usuarios/${id}/desactivar`,
        reactivar: (id: number) => `/usuarios/${id}/reactivar`,
        eliminar: (id: number) => `/usuarios/${id}`,
        recuperar: (id: number) => `/usuarios/${id}/recuperar`,
        cambiarRol: (id: number) => `/usuarios/${id}/rol`,
        verComo: (id: number) => `/usuarios/${id}/ver-como`,
    },
    roles: {
        crear: () => '/roles',
        actualizar: (id: number) => `/roles/${id}`,
        eliminar: (id: number) => `/roles/${id}`,
        asignar: (id: number) => `/roles/${id}/asignar`,
    },
    colaboradores: {
        lista: () => '/colaboradores',
        crear: () => '/colaboradores',
        actualizar: (id: number) => `/colaboradores/${id}`,
        desactivar: (id: number) => `/colaboradores/${id}/desactivar`,
        reactivar: (id: number) => `/colaboradores/${id}/reactivar`,
        eliminar: (id: number) => `/colaboradores/${id}`,
    },
    empresas: {
        lista: (sectorId?: number) =>
            sectorId ? `/empresas?sector=${sectorId}` : '/empresas',
        ver: (id: number) => `/empresas/${id}`,
    },
};
