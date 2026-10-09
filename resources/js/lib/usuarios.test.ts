import { describe, expect, it } from 'vite-plus/test';
import { avisoCuentaPrincipal, coincide, iniciales } from '@/lib/usuarios';

const laura = {
    nombre: 'Laura Gómez',
    correo: 'laura@laesquina.co',
    empresa: 'Restaurante La Esquina',
    es_principal: true,
    colaboradores_activos: 2,
    medicion_pendiente: 'Medición 4 no iniciada (vence el 20 oct)',
};

describe('avisoCuentaPrincipal', () => {
    it('avisa al desactivar la cuenta principal (A5.3b)', () => {
        expect(avisoCuentaPrincipal(laura, 'desactivar')).toBe(
            'Es la cuenta principal de Restaurante La Esquina: la empresa y sus 2 colaboradores activos se quedan sin acceso hasta que la reactives. Tiene la Medición 4 no iniciada (vence el 20 oct): quedará en pausa.',
        );
    });

    it('avisa al cambiar el rol de la cuenta principal (A5.5)', () => {
        expect(
            avisoCuentaPrincipal(
                {
                    ...laura,
                    colaboradores_activos: 0,
                    medicion_pendiente: null,
                },
                'cambiar',
            ),
        ).toBe(
            'Laura Gómez es la cuenta principal de Restaurante La Esquina: si cambia de rol, la empresa se queda sin acceso para responder.',
        );
    });

    it('no avisa en cuentas que no son principales', () => {
        expect(
            avisoCuentaPrincipal({ ...laura, es_principal: false }, 'cambiar'),
        ).toBeNull();
    });
});

describe('coincide', () => {
    it('busca por nombre o correo sin tildes ni mayúsculas', () => {
        expect(coincide(laura, 'gomez')).toBe(true);
        expect(coincide(laura, 'LAESQUINA')).toBe(true);
        expect(coincide(laura, 'rojas')).toBe(false);
        expect(coincide(laura, '  ')).toBe(true);
    });
});

describe('iniciales', () => {
    it('toma la primera letra de las dos primeras palabras', () => {
        expect(iniciales('Laura Gómez')).toBe('LG');
        expect(iniciales('ángela de la Torre')).toBe('ÁD');
        expect(iniciales('Diego')).toBe('D');
    });
});
