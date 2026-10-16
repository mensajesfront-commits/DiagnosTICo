import { describe, expect, it } from 'vitest';
import { indicativoDe, separar, unir } from './telefonos';

describe('teléfonos', () => {
    it('Colombia lleva 10 dígitos y Argentina 11', () => {
        expect(indicativoDe('CO').digitos).toBe(10);
        expect(indicativoDe('AR').digitos).toBe(11);
    });

    it('separa el indicativo del número', () => {
        expect(separar('+57 300 123 4567')).toEqual({
            pais: 'CO',
            numero: '3001234567',
        });
        expect(separar('+591 71234567')).toEqual({
            pais: 'BO',
            numero: '71234567',
        });
    });

    it('sin indicativo usa el país por defecto', () => {
        expect(separar('300-123-4567', 'MX')).toEqual({
            pais: 'MX',
            numero: '3001234567',
        });
        expect(separar('')).toEqual({ pais: 'CO', numero: '' });
    });

    it('une el indicativo y el número; sin número queda vacío', () => {
        expect(unir('AR', '91123456789')).toBe('+54 91123456789');
        expect(unir('CO', '')).toBe('');
    });
});
