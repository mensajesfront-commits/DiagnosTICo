import { describe, expect, it } from 'vite-plus/test';
import { revisarContrasena } from '@/lib/contrasena';

const cumplidos = (c: string, conf: string) =>
    revisarContrasena(c, conf)
        .requisitos.filter((r) => r.cumple)
        .map((r) => r.id);

describe('revisarContrasena (RN-001)', () => {
    it('acepta una contraseña que cumple todo', () => {
        expect(revisarContrasena('Clave123!', 'Clave123!').completa).toBe(true);
    });

    it('marca lo que falta', () => {
        expect(cumplidos('clave123', 'clave123')).toEqual([
            'longitud',
            'numero',
            'coinciden',
        ]);
    });

    it('reconoce mayúsculas con tilde y la ñ', () => {
        expect(cumplidos('Ñandú', '')).toContain('mayuscula');
    });

    it('no da por coincidentes dos campos vacíos', () => {
        expect(cumplidos('', '')).not.toContain('coinciden');
    });

    it('cuenta los requisitos cumplidos', () => {
        const { cumplidos, completa } = revisarContrasena('Clave1234', 'otra');
        expect(cumplidos).toBe(3);
        expect(completa).toBe(false);
    });
});
