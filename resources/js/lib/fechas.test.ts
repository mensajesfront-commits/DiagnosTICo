import { describe, expect, it } from 'vite-plus/test';
import { haceCuanto, mesYAnio, momento } from '@/lib/fechas';

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
});
