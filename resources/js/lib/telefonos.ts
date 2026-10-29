/**
 * Indicativos de los países de la ubicación (`App\Support\Ubicaciones`) y
 * cuántos dígitos lleva el número sin el indicativo. La misma lista está en
 * `App\Support\Telefonos`, que valida en el servidor.
 *
 * El teléfono se guarda como "+57 3001234567".
 */
export type Indicativo = {
    pais: string;
    nombre: string;
    codigo: string;
    digitos: number;
};

export const INDICATIVOS: Indicativo[] = [
    { pais: 'AR', nombre: 'Argentina', codigo: '+54', digitos: 11 },
    { pais: 'BO', nombre: 'Bolivia', codigo: '+591', digitos: 8 },
    { pais: 'CL', nombre: 'Chile', codigo: '+56', digitos: 9 },
    { pais: 'CO', nombre: 'Colombia', codigo: '+57', digitos: 10 },
    { pais: 'CR', nombre: 'Costa Rica', codigo: '+506', digitos: 8 },
    { pais: 'CU', nombre: 'Cuba', codigo: '+53', digitos: 8 },
    { pais: 'EC', nombre: 'Ecuador', codigo: '+593', digitos: 9 },
    { pais: 'SV', nombre: 'El Salvador', codigo: '+503', digitos: 8 },
    { pais: 'GT', nombre: 'Guatemala', codigo: '+502', digitos: 8 },
    { pais: 'HN', nombre: 'Honduras', codigo: '+504', digitos: 8 },
    { pais: 'MX', nombre: 'México', codigo: '+52', digitos: 10 },
    { pais: 'NI', nombre: 'Nicaragua', codigo: '+505', digitos: 8 },
    { pais: 'PA', nombre: 'Panamá', codigo: '+507', digitos: 8 },
    { pais: 'PY', nombre: 'Paraguay', codigo: '+595', digitos: 9 },
    { pais: 'PE', nombre: 'Perú', codigo: '+51', digitos: 9 },
    { pais: 'DO', nombre: 'República Dominicana', codigo: '+1', digitos: 10 },
    { pais: 'UY', nombre: 'Uruguay', codigo: '+598', digitos: 8 },
    { pais: 'VE', nombre: 'Venezuela', codigo: '+58', digitos: 10 },
];

export const PAIS_INICIAL = 'CO';

export const indicativoDe = (pais: string): Indicativo =>
    INDICATIVOS.find((i) => i.pais === pais) ??
    INDICATIVOS.find((i) => i.pais === PAIS_INICIAL)!;

/** País (ISO) a partir del nombre que guarda la ubicación ("Colombia"). */
export const paisPorNombre = (nombre: string | null | undefined) =>
    INDICATIVOS.find((i) => i.nombre === nombre)?.pais ?? null;

/**
 * "+57 300 123 4567" → { pais: 'CO', numero: '3001234567' }. Busca el
 * indicativo más largo que coincida; sin "+", el número queda con el país
 * por defecto.
 */
export function separar(
    valor: string | null | undefined,
    paisPorDefecto = PAIS_INICIAL,
): { pais: string; numero: string } {
    const texto = (valor ?? '').trim();

    if (texto.startsWith('+')) {
        const digitos = texto.replace(/\D/g, '');
        const encontrado = [...INDICATIVOS]
            .sort((a, b) => b.codigo.length - a.codigo.length)
            .find((i) => digitos.startsWith(i.codigo.slice(1)));

        if (encontrado) {
            return {
                pais: encontrado.pais,
                numero: digitos.slice(encontrado.codigo.length - 1),
            };
        }
    }

    return { pais: paisPorDefecto, numero: texto.replace(/\D/g, '') };
}

/** { pais: 'CO', numero: '3001234567' } → "+57 3001234567"; vacío si no hay número. */
export const unir = (pais: string, numero: string): string =>
    numero === '' ? '' : `${indicativoDe(pais).codigo} ${numero}`;
