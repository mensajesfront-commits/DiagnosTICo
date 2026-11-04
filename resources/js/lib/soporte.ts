/**
 * Correo de soporte de Captter. Sale de `SOPORTE_EMAIL` en `.env`
 * (config('diagnostico.soporte_email')), compartido con todas las páginas
 * (HandleInertiaRequests). Si una página se dibuja sin las props compartidas
 * (por ejemplo, un error antes de llegar a la ruta), usa el oficial.
 */
import { usePage } from '@inertiajs/vue3';

export const CORREO_SOPORTE = 'contacto@captter.com';

export function useCorreoSoporte(): string {
    const soporte = usePage().props.soporte;

    return typeof soporte === 'string' && soporte !== ''
        ? soporte
        : CORREO_SOPORTE;
}
