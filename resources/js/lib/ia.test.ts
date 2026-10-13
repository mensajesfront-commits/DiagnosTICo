import { describe, expect, it } from 'vitest';
import { variablesUsadas } from '@/lib/ia';

describe('variablesUsadas', () => {
    it('encuentra los datos que se insertan solos', () => {
        expect(
            variablesUsadas(
                'Sector {sector}, categoría "{categoria}" y {sector}.',
            ),
        ).toEqual(['sector', 'categoria', 'sector']);
    });

    it('ignora llaves que no son códigos', () => {
        expect(variablesUsadas('Responde en {JSON} o { sector }')).toEqual([]);
    });
});
