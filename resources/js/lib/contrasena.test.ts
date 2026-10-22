import { describe, expect, it } from 'vite-plus/test';
import {
    parteDelCorreoEn,
    partesDelCorreo,
    repiteLaAnterior,
    revisarContrasena,
} from '@/lib/contrasena';

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

    it('sin confirmación no pide que coincidan (E12.1, E12.3)', () => {
        const estado = revisarContrasena('Mesa-47-Sol', '', {
            conConfirmacion: false,
        });
        expect(estado.requisitos.map((r) => r.id)).not.toContain('coinciden');
        expect(estado.completa).toBe(true);
    });
});

describe('partes del correo en la contraseña', () => {
    it('saca las partes del correo, sin las de menos de 3 caracteres', () => {
        expect(partesDelCorreo('Juan.Perez1102@Empresa.co')).toEqual([
            'juanperez1102',
            'juan',
            'perez1102',
            'perez',
            '1102',
            'empresa',
        ]);
        expect(partesDelCorreo('cristian20andres04@gmail.com')).toEqual([
            'cristian20andres04',
            'cristian',
            'andres',
            'gmail',
        ]);
    });

    it('rechaza la contraseña con una parte del correo, sin importar mayúsculas ni tildes', () => {
        const correo = 'juan.perez1102@empresa.co';
        expect(parteDelCorreoEn('Clave-1102!', correo)).toBe('1102');
        expect(parteDelCorreoEn('PÉREZ-2026!', correo)).toBe('perez');
        expect(parteDelCorreoEn('Mesa-47-Sol', correo)).toBeNull();
    });

    it('muestra el requisito solo cuando hay correo', () => {
        const ids = (correo: string) =>
            revisarContrasena('Mesa-47-Sol', 'Mesa-47-Sol', {
                correo,
            }).requisitos.map((r) => r.id);
        expect(ids('')).not.toContain('correo');
        expect(ids('laura@laesquina.co')).toContain('correo');
        expect(
            revisarContrasena('Laura-47-Sol', 'Laura-47-Sol', {
                correo: 'laura@laesquina.co',
            }).completa,
        ).toBe(false);
    });
});

describe('nada igual a la contraseña actual (Mi perfil)', () => {
    it('rechaza 4 caracteres seguidos de la actual, sin importar mayúsculas', () => {
        expect(repiteLaAnterior('MESA-99-Luna', 'Mesa-47-Sol')).toBe(true);
        expect(repiteLaAnterior('Luna-a-47-S!', 'Mesa-47-Sol')).toBe(true);
        expect(repiteLaAnterior('Cielo#2026', 'Mesa-47-Sol')).toBe(false);
    });

    it('muestra el requisito solo si se escribió la actual', () => {
        const ids = (anterior: string) =>
            revisarContrasena('Cielo#2026', 'Cielo#2026', {
                anterior,
            }).requisitos.map((r) => r.id);
        expect(ids('')).not.toContain('anterior');
        expect(ids('Mesa-47-Sol')).toContain('anterior');
    });
});
