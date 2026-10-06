import { describe, expect, it } from 'vite-plus/test';
import { haceCuanto, mesYAnio, momento, ultimoAcceso } from '@/lib/fechas';

const ahora = new Date(2026, 9, 6, 16, 0);

describe('fechas', () => {
    it('dice "hoy" con la hora para un acceso del mismo día', () => {
        expect(momento(new Date(2026, 9, 6, 15, 24).toISOString(), ahora)).toBe(
            'hoy 15:24',
        );
    });

    it('cuenta días, meses y años', () => {
        expect(haceCuanto(new Date(2026, 9, 6).toISOString(), ahora)).toBe(
            'hoy',
        );
        expect(haceCuanto(new Date(2026, 9, 1).toISOString(), ahora)).toBe(
            'hace 5 días',
        );
        expect(haceCuanto(new Date(2026, 7, 1).toISOString(), ahora)).toBe(
            'hace 2 meses',
        );
        expect(haceCuanto(new Date(2025, 8, 1).toISOString(), ahora)).toBe(
            'hace 1 año',
        );
    });

    it('escribe el mes abreviado', () => {
        expect(mesYAnio(new Date(2026, 0, 15).toISOString())).toBe('ene 2026');
        expect(momento(new Date(2026, 9, 3, 9, 5).toISOString(), ahora)).toBe(
            '3 oct, 9:05',
        );
    });

    it('escribe el último acceso como en A5', () => {
        const iso = (d: Date) => d.toISOString();

        expect(ultimoAcceso(null, ahora)).toBe('Nunca');
        expect(ultimoAcceso(iso(new Date(2026, 9, 6, 9, 12)), ahora)).toBe(
            'Hoy, 9:12',
        );
        expect(ultimoAcceso(iso(new Date(2026, 9, 5)), ahora)).toBe('Ayer');
        expect(ultimoAcceso(iso(new Date(2026, 9, 1)), ahora)).toBe(
            'Hace 5 días',
        );
        expect(ultimoAcceso(iso(new Date(2026, 8, 28)), ahora)).toBe('28 sep');
        expect(ultimoAcceso(iso(new Date(2025, 7, 2)), ahora)).toBe(
            '2 ago 2025',
        );
    });
});
