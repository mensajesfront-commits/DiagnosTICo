import { describe, expect, it } from 'vite-plus/test';
import { filtrarOpciones, normalizar, opcionExacta } from '@/lib/texto';

describe('normalizar', () => {
    it('quita tildes, mayúsculas y espacios de más', () => {
        expect(normalizar('  Bogotá  D.C. ')).toBe('bogota d.c.');
        expect(normalizar('Ñuble')).toBe('nuble');
    });
});

describe('filtrarOpciones', () => {
    const ciudades = ['Medellín', 'Bello', 'Itagüí', 'Santa Fe de Antioquia'];

    it('busca sin tildes y pone primero las que empiezan así', () => {
        expect(filtrarOpciones(ciudades, 'mede')).toEqual(['Medellín']);
        expect(filtrarOpciones(ciudades, 'itagui')).toEqual(['Itagüí']);
        expect(
            filtrarOpciones(['Antioquia', 'Santa Fe de Antioquia'], 'ant'),
        ).toEqual(['Antioquia', 'Santa Fe de Antioquia']);
    });

    it('sin texto devuelve todas', () => {
        expect(filtrarOpciones(ciudades, '')).toHaveLength(4);
    });
});

describe('opcionExacta', () => {
    it('encuentra la opción aunque se escriba sin tilde', () => {
        expect(opcionExacta(['Medellín'], 'medellin')).toBe('Medellín');
        expect(opcionExacta(['Medellín'], 'mede')).toBeUndefined();
    });
});
