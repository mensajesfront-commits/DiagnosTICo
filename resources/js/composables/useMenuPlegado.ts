import { ref } from 'vue';

/**
 * El menú lateral se puede plegar a una barra con iconos. Se recuerda en el
 * navegador (por persona y equipo) y se comparte entre páginas. Si el
 * navegador no deja guardar, arranca abierto.
 */
const CLAVE = 'captter.menu-plegado';

function leer(): boolean {
    try {
        return localStorage.getItem(CLAVE) === '1';
    } catch {
        return false;
    }
}

const plegado = ref(typeof window !== 'undefined' && leer());

export function useMenuPlegado() {
    function alternar(): void {
        plegado.value = !plegado.value;

        try {
            localStorage.setItem(CLAVE, plegado.value ? '1' : '0');
        } catch {
            // Sin almacenamiento: solo dura mientras no se recargue.
        }
    }

    return { plegado, alternar };
}
