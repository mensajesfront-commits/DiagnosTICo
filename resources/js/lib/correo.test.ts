import { describe, expect, it } from 'vite-plus/test';
import { fechaCorreo, parrafos, rellenar } from '@/lib/correo';

describe('rellenar', () => {
    it('cambia las variables y quita las líneas vacías del mensaje', () => {
        const texto = rellenar(
            'Hola {nombre_usuario},\n\n{mensaje_admin}\n\nFecha: {fecha_limite}.',
            {
                nombre_usuario: 'Laura',
                mensaje_admin: '',
                fecha_limite: 'sin fecha límite',
            },
        );

        expect(texto).toBe('Hola Laura,\n\nFecha: sin fecha límite.');
    });

    it('deja como están las variables que no conoce', () => {
        expect(rellenar('{otra} {empresa}', { empresa: 'X' })).toBe('{otra} X');
    });
});

describe('parrafos', () => {
    it('separa por líneas en blanco', () => {
        expect(parrafos('Uno\n\n  \nDos\ntres')).toEqual(['Uno', 'Dos\ntres']);
    });
});

describe('fechaCorreo', () => {
    it('escribe la fecha larga en español', () => {
        expect(fechaCorreo('2026-10-20')).toBe('20 de octubre de 2026');
        expect(fechaCorreo('')).toBe('sin fecha límite');
    });
});
