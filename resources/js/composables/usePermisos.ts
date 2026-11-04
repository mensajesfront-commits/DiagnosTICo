/**
 * Permisos de la cuenta para mostrar u ocultar botones y acciones.
 *
 * Una cuenta ve solo lo que su rol le permite: si tiene "Ver empresas" pero
 * no "Asignar mediciones", no ve "Asignar medición". El servidor bloquea las
 * rutas igual (docs/17_SEGURIDAD.md); ocultar aquí es para no mostrar lo
 * que no se puede usar.
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type Permiso =
    | 'diagnosticos.ver'
    | 'diagnosticos.editar'
    | 'diagnosticos.publicar'
    | 'empresas.ver'
    | 'empresas.registrar'
    | 'mediciones.asignar'
    | 'diagnostico.responder'
    | 'resultados.ver'
    | 'resultados.pdf'
    | 'ia.ver'
    | 'ia.editar'
    | 'usuarios.ver'
    | 'usuarios.gestionar'
    | 'perfil.editar'
    | 'contrasena.cambiar';

export function usePermisos() {
    const page = usePage();
    const permisos = computed(
        () => new Set<string>(page.props.auth?.permisos ?? []),
    );

    /** La cuenta tiene el permiso. */
    const puede = (permiso: Permiso): boolean => permisos.value.has(permiso);

    return { puede };
}
