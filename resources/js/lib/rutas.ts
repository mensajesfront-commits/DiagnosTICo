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
        editar: (id: number, parte?: number, pregunta?: number) =>
            `/diagnosticos/${id}/editar` +
            (parte
                ? `?categoria=${parte}${pregunta ? `&pregunta=${pregunta}` : ''}`
                : ''),
        vistaPrevia: (id: number, version?: number) =>
            `/diagnosticos/${id}/vista-previa${version ? `?version=${version}` : ''}`,
        preguntas: (id: number) => `/diagnosticos/${id}/preguntas`,
        pregunta: (id: number, pregunta: number) =>
            `/diagnosticos/${id}/preguntas/${pregunta}`,
        ordenar: (id: number, parte: number) =>
            `/diagnosticos/${id}/categorias/${parte}/orden`,
        agregarCategorias: (id: number) => `/diagnosticos/${id}/categorias`,
        importancia: (id: number) => `/diagnosticos/${id}/importancia`,
        publicar: (id: number) => `/diagnosticos/${id}/publicar`,
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
        crear: () => '/empresas/registrar',
        guardar: () => '/empresas',
        actualizar: (id: number) => `/empresas/${id}`,
        desactivar: (id: number) => `/empresas/${id}/desactivar`,
        reactivar: (id: number) => `/empresas/${id}/reactivar`,
    },
    mediciones: {
        asignar: (empresaId?: number) =>
            empresaId
                ? `/mediciones/asignar?empresa=${empresaId}`
                : '/mediciones/asignar',
        guardar: () => '/mediciones',
        cancelar: (id: number) => `/mediciones/${id}/cancelar`,
        fecha: (id: number) => `/mediciones/${id}/fecha`,
        correo: (id: number) => `/mediciones/${id}/correo`,
        enviarCorreo: (id: number) => `/mediciones/${id}/correo`,
        probarCorreo: () => '/mediciones/correo/prueba',
    },
    responder: {
        mostrar: (id: number, categoria?: number) =>
            `/mi-diagnostico/${id}${categoria !== undefined ? `?categoria=${categoria}` : ''}`,
        guardar: (id: number) => `/mi-diagnostico/${id}/respuestas`,
        revisar: (id: number) => `/mi-diagnostico/${id}/revisar`,
        enviar: (id: number) => `/mi-diagnostico/${id}/enviar`,
        enviado: (id: number) => `/mi-diagnostico/${id}/enviado`,
        solicitar: (id: number) => `/mi-diagnostico/${id}/solicitud`,
        reintentar: (id: number) => `/mi-diagnostico/${id}/reintentar`,
    },
    ia: {
        index: (
            alcance: 'general' | 'sector' | 'empresa' = 'general',
            id?: number,
        ) =>
            alcance === 'general'
                ? '/configuracion-ia'
                : `/configuracion-ia?alcance=${alcance}${id ? `&${alcance}=${id}` : ''}`,
        comoSeArma: () => '/configuracion-ia/como-se-arma',
        guardar: () => '/configuracion-ia',
        quitar: () => '/configuracion-ia/ajuste',
        vistaPrevia: () => '/configuracion-ia/vista-previa',
        probar: () => '/configuracion-ia/probar',
    },
    historial: {
        mio: () => '/historial',
        empresa: (id: number) => `/empresas/${id}/historial`,
    },
    resultados: {
        ver: (id: number) => `/resultados/${id}`,
        respuestas: (id: number, categoria?: string) =>
            `/resultados/${id}/respuestas${categoria ? `?categoria=${categoria}` : ''}`,
        pdf: (id: number) => `/resultados/${id}/pdf`,
    },
};
