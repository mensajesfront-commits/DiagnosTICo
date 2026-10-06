/**
 * Textos de fechas como los muestran los wireframes: "hoy 15:24",
 * "hace 2 meses", "ene 2026". La zona horaria es la del navegador.
 */

// Se arma a mano para que el texto sea igual en todos los navegadores
// (Intl escribe "ene de 2026" o "3 de oct" según la versión).
const MESES = [
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic',
];

const dosDigitos = (n: number) => String(n).padStart(2, '0');
const hora = (f: Date) => `${f.getHours()}:${dosDigitos(f.getMinutes())}`;

/** "hoy 15:24", "ayer 9:12" o "3 oct, 9:12". */
export function momento(iso: string, ahora: Date = new Date()): string {
    const fecha = new Date(iso);
    const dias = diasEntre(fecha, ahora);

    if (dias === 0) {
        return `hoy ${hora(fecha)}`;
    }

    if (dias === 1) {
        return `ayer ${hora(fecha)}`;
    }

    return `${fecha.getDate()} ${MESES[fecha.getMonth()]}, ${hora(fecha)}`;
}

/** "ene 2026". */
export function mesYAnio(iso: string): string {
    const fecha = new Date(iso);

    return `${MESES[fecha.getMonth()]} ${fecha.getFullYear()}`;
}

/** "hoy", "hace 5 días", "hace 2 meses", "hace 1 año". */
export function haceCuanto(iso: string, ahora: Date = new Date()): string {
    const dias = diasEntre(new Date(iso), ahora);

    if (dias <= 0) {
        return 'hoy';
    }

    if (dias < 30) {
        return dias === 1 ? 'ayer' : `hace ${dias} días`;
    }

    const meses = Math.floor(dias / 30);

    if (meses < 12) {
        return meses === 1 ? 'hace 1 mes' : `hace ${meses} meses`;
    }

    const anios = Math.floor(meses / 12);

    return anios === 1 ? 'hace 1 año' : `hace ${anios} años`;
}

/**
 * "Último acceso" de A5: "Hoy, 9:12", "Ayer", "Hace 5 días", "30 sep" o
 * "Nunca".
 */
export function ultimoAcceso(
    iso: string | null,
    ahora: Date = new Date(),
): string {
    if (!iso) {
        return 'Nunca';
    }

    const fecha = new Date(iso);
    const dias = diasEntre(fecha, ahora);

    if (dias <= 0) {
        return `Hoy, ${hora(fecha)}`;
    }

    if (dias === 1) {
        return 'Ayer';
    }

    if (dias < 7) {
        return `Hace ${dias} días`;
    }

    return diaYMes(fecha, ahora);
}

/** "29 sep", con el año si no es el actual ("2 ago 2025"). */
export function diaYMes(fecha: Date, ahora: Date = new Date()): string {
    const texto = `${fecha.getDate()} ${MESES[fecha.getMonth()]}`;

    return fecha.getFullYear() === ahora.getFullYear()
        ? texto
        : `${texto} ${fecha.getFullYear()}`;
}

function diasEntre(desde: Date, hasta: Date): number {
    const inicio = new Date(
        desde.getFullYear(),
        desde.getMonth(),
        desde.getDate(),
    );
    const fin = new Date(
        hasta.getFullYear(),
        hasta.getMonth(),
        hasta.getDate(),
    );

    return Math.round((fin.getTime() - inicio.getTime()) / 86_400_000);
}
