/**
 * Niveles del resultado (RN-019) y colores del wireframe para las gráficas.
 *
 * El backend calcula el nivel y lo entrega ya resuelto (regla de reparto en
 * docs/10_ARQUITECTURA.md); aquí solo se decide cómo se muestra.
 */

export type Nivel = 'critico' | 'mejorar' | 'camino' | 'sigue' | 'sin';

export type InfoNivel = {
    nombre: string;
    rango: string;
    /** Clases de Tailwind para la etiqueta (fondo suave + texto). */
    etiqueta: string;
    /** Clase de Tailwind para barras y puntos (color fuerte). */
    barra: string;
    /** Color fuerte en hexadecimal, para Chart.js. */
    color: string;
};

export const niveles: Record<Nivel, InfoNivel> = {
    critico: {
        nombre: 'Crítico',
        rango: '0–29',
        etiqueta: 'bg-nivel-critico-suave text-nivel-critico-texto',
        barra: 'bg-nivel-critico',
        color: '#d85249',
    },
    mejorar: {
        nombre: 'Se puede mejorar',
        rango: '30–59',
        etiqueta: 'bg-nivel-mejorar-suave text-nivel-mejorar-texto',
        barra: 'bg-nivel-mejorar',
        color: '#e39938',
    },
    camino: {
        nombre: 'Vas en buen camino',
        rango: '60–79',
        etiqueta: 'bg-nivel-camino-suave text-nivel-camino-texto',
        barra: 'bg-nivel-camino',
        color: '#4a85c7',
    },
    sigue: {
        nombre: 'Sigue así',
        rango: '80–100',
        etiqueta: 'bg-nivel-sigue-suave text-nivel-sigue-texto',
        barra: 'bg-nivel-sigue',
        color: '#4e9954',
    },
    sin: {
        nombre: 'Sin diagnóstico',
        rango: '—',
        etiqueta: 'bg-lienzo-oscuro text-tinta-suave',
        barra: 'bg-nivel-sin',
        color: '#c9c8c3',
    },
};

/** Orden en que se muestran los niveles en leyendas y paneles. */
export const ordenNiveles: Nivel[] = [
    'critico',
    'mejorar',
    'camino',
    'sigue',
    'sin',
];

export const colores = {
    marca: '#2d4a7a',
    tinta: '#1e2533',
    tintaSuave: '#5f6470',
    linea: '#e3e2dd',
    lienzo: '#f5f4f0',
};
