import { describe, expect, it } from 'vite-plus/test';
import { documentosLegales, partesConMarcas } from './legales';

describe('textos legales oficiales (L2)', () => {
    it('tienen su fecha de última actualización y el correo de contacto', () => {
        expect(documentosLegales.terminos.actualizacion).toBe(
            '16 de septiembre de 2025',
        );
        expect(documentosLegales.politica.actualizacion).toBe(
            '27 de mayo de 2025',
        );

        for (const doc of Object.values(documentosLegales)) {
            const todo = JSON.stringify(doc);
            expect(todo).toContain('contacto@captter.com');
            // Ya no es un borrador: ningún texto queda marcado como pendiente.
            expect(partesConMarcas(todo).some((parte) => parte.marca)).toBe(
                false,
            );
        }
    });

    it('la política nombra los derechos ARCO y la Ley 1581 de 2012', () => {
        const todo = JSON.stringify(documentosLegales.politica);
        expect(todo).toContain('Oposición (Derechos ARCO)');
        expect(todo).toContain('Ley 1581 de 2012');
    });
});
