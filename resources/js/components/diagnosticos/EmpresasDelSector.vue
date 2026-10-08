<script setup lang="ts">
/**
 * "Empresas del sector" en A2 (HU-010 CA-005): diagnóstico asignado,
 * medición y puntaje de cada empresa, con "Ver empresa".
 */
import { Link } from '@inertiajs/vue3';
import { rutas } from '@/lib/rutas';
import type { EmpresaDelSector, Sector } from '@/types/diagnosticos';

defineProps<{
    sector: Sector;
    empresas: EmpresaDelSector[];
    /** Total de empresas del sector (la lista puede traer solo las primeras). */
    total: number;
    /** El sector no tiene empresas ni mediciones: se puede eliminar. */
    sePuedeEliminar: boolean;
}>();

defineEmits<{ desactivar: [] }>();

const estados: Record<string, string> = {
    no_iniciada: 'No iniciada',
    en_curso: 'En curso',
    enviada: 'Enviada',
    terminada: 'Terminada',
    vencida: 'Vencida',
};

function textoMedicion(empresa: EmpresaDelSector): string {
    if (!empresa.medicion) {
        return 'Sin medición';
    }

    const { numero, estado, avance } = empresa.medicion;
    const partes = [`Medición ${numero}`, estados[estado] ?? estado];

    if (avance && estado !== 'terminada') {
        partes.push(`${avance.hechas}/${avance.total}`);
    }

    return partes.join(' · ');
}
</script>

<template>
    <section class="rounded-xl border border-linea bg-white">
        <header
            class="flex flex-wrap items-baseline justify-between gap-2 px-5 pt-4 pb-3"
        >
            <h2 class="text-sm font-semibold">
                Empresas del sector
                <span
                    v-if="total === 0"
                    class="ml-2 font-normal text-tinta-suave"
                >
                    Todavía no hay empresas en {{ sector.nombre }}. Aparecen
                    aquí cuando una empresa se registra con este sector.
                </span>
            </h2>
            <Link
                v-if="total > 0"
                :href="rutas.empresas.lista(sector.id)"
                class="text-xs text-marca underline"
            >
                Ver las {{ total }} en Empresas
            </Link>
        </header>

        <div v-if="empresas.length > 0" class="max-h-52 overflow-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="sticky top-0 bg-white">
                    <tr class="border-y border-linea text-left">
                        <th
                            v-for="columna in [
                                'Empresa',
                                'Diagnóstico asignado',
                                'Medición',
                                'Puntaje',
                                '',
                            ]"
                            :key="columna"
                            scope="col"
                            class="px-5 py-2 text-xs font-normal text-tinta-suave"
                        >
                            {{ columna }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="empresa in empresas"
                        :key="empresa.id"
                        class="border-b border-linea last:border-b-0"
                    >
                        <td class="px-5 py-2.5 font-medium">
                            {{ empresa.nombre }}
                        </td>
                        <td class="px-5 py-2.5 text-tinta-suave">
                            {{ empresa.diagnostico ?? '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-tinta-suave">
                            {{ textoMedicion(empresa) }}
                        </td>
                        <td class="px-5 py-2.5 font-mono">
                            {{ empresa.puntaje ?? '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right">
                            <Link
                                :href="rutas.empresas.ver(empresa.id)"
                                class="text-xs text-marca underline"
                            >
                                Ver empresa
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p
            class="mx-5 mt-3 mb-4 rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
        >
            <template v-if="sePuedeEliminar">
                Este sector se puede <strong>eliminar</strong> porque no tiene
                empresas ni mediciones. Si prefieres conservarlo sin ofrecerlo
                al registrar, puedes
                <button
                    type="button"
                    class="text-marca underline"
                    @click="$emit('desactivar')"
                >
                    desactivarlo</button
                >.
            </template>
            <template v-else>
                Solo se asignan diagnósticos <strong>publicados</strong> del
                sector de la empresa. Duplicar crea un borrador nuevo. Archivar
                lo saca de nuevas asignaciones y conserva los resultados (se
                puede deshacer). Eliminar solo borra borradores.
            </template>
        </p>
    </section>
</template>
