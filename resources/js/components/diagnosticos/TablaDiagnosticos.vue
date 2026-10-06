<script setup lang="ts">
/**
 * Tabla de diagnósticos de A2 (un sector) y A2·T (todos, agrupados por
 * sector). HU-010 CA-004 y HU-011.
 *
 * Acciones por fila: Editar y Vista previa; Duplicar; Archivar si tiene una
 * versión publicada o Eliminar si nunca se publicó; y "Eliminar borrador" si
 * hay un borrador pendiente sobre la versión publicada.
 */
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import { rutas } from '@/lib/rutas';
import type { FilaDiagnostico, Sector } from '@/types/diagnosticos';

const props = withDefaults(
    defineProps<{
        filas: FilaDiagnostico[];
        /** Agrupa por sector (vista "Todos"). */
        agrupar?: boolean;
        /** Muestra el sector bajo el nombre (en "Todos" sin agrupar). */
        mostrarSector?: boolean;
        /** En "Todos", los sectores para mostrar también los que no tienen diagnósticos. */
        sectores?: Sector[];
    }>(),
    { agrupar: false, mostrarSector: false, sectores: () => [] },
);

defineEmits<{
    duplicar: [fila: FilaDiagnostico];
    archivar: [fila: FilaDiagnostico];
    eliminar: [fila: FilaDiagnostico];
    eliminarBorrador: [fila: FilaDiagnostico];
}>();

type Grupo = { id: number; nombre: string; filas: FilaDiagnostico[] };

const grupos = computed<Grupo[]>(() => {
    if (!props.agrupar) {
        return [{ id: 0, nombre: '', filas: props.filas }];
    }

    const porSector = new Map<number, Grupo>();

    for (const fila of props.filas) {
        const grupo = porSector.get(fila.sector_id) ?? {
            id: fila.sector_id,
            nombre: fila.sector_nombre,
            filas: [],
        };
        grupo.filas.push(fila);
        porSector.set(fila.sector_id, grupo);
    }

    // Los sectores sin diagnósticos también aparecen, con "Ver sector".
    for (const sector of props.sectores) {
        if (!porSector.has(sector.id) && sector.diagnosticos === 0) {
            porSector.set(sector.id, {
                id: sector.id,
                nombre: sector.nombre,
                filas: [],
            });
        }
    }

    return [...porSector.values()];
});

function detalle(fila: FilaDiagnostico): string {
    const categorias = `${fila.categorias} ${fila.categorias === 1 ? 'categoría' : 'categorías'}`;

    const partes = [categorias];

    if (fila.borrador_pendiente && fila.version_publicada) {
        partes.push(`v${fila.version_publicada} publicada`);
    }

    if (props.mostrarSector) {
        partes.unshift(fila.sector_nombre);
    }

    return partes.join(' · ');
}

const columnas = [
    'Diagnóstico',
    'Estado',
    'Preguntas',
    'Empresas',
    'Mediciones',
    'Acciones',
];
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-y border-linea bg-[#f9f8f4] text-left">
                    <th
                        v-for="columna in columnas"
                        :key="columna"
                        scope="col"
                        class="px-4 py-2.5 text-xs font-normal text-tinta-suave"
                    >
                        {{ columna }}
                    </th>
                </tr>
            </thead>
            <tbody v-for="grupo in grupos" :key="grupo.id">
                <tr v-if="agrupar" class="border-b border-linea bg-lienzo/70">
                    <th
                        scope="rowgroup"
                        :colspan="columnas.length"
                        class="px-4 py-2 text-left text-xs font-semibold"
                    >
                        <span class="flex items-center justify-between">
                            <span>
                                {{ grupo.nombre }}
                                <span class="ml-1 font-normal text-tinta-suave">
                                    {{ grupo.filas.length }}
                                    {{
                                        grupo.filas.length === 1
                                            ? 'diagnóstico'
                                            : 'diagnósticos'
                                    }}
                                </span>
                            </span>
                            <Link
                                v-if="grupo.filas.length === 0"
                                :href="rutas.diagnosticos.sector(grupo.id)"
                                class="font-normal text-marca underline"
                            >
                                Ver sector
                            </Link>
                        </span>
                    </th>
                </tr>
                <tr
                    v-for="fila in grupo.filas"
                    :key="fila.id"
                    class="border-b border-linea last:border-b-0"
                >
                    <td class="px-4 py-3 align-top">
                        <p class="max-w-56 font-medium">{{ fila.nombre }}</p>
                        <p class="text-xs text-tinta-suave">
                            {{ detalle(fila) }}
                        </p>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="flex flex-col items-start gap-1">
                            <Etiqueta
                                v-if="fila.version_publicada"
                                tono="exito"
                            >
                                Publicado · v{{ fila.version_publicada }}
                            </Etiqueta>
                            <Etiqueta v-else tono="alerta">
                                Borrador · sin publicar
                            </Etiqueta>
                            <Etiqueta v-if="fila.borrador_pendiente">
                                ✎ Borrador v{{ fila.borrador_pendiente }}
                                pendiente
                            </Etiqueta>
                        </div>
                    </td>
                    <td class="px-4 py-3 align-top font-mono">
                        {{ fila.preguntas }}
                    </td>
                    <td class="px-4 py-3 align-top font-mono">
                        {{ fila.empresas }}
                    </td>
                    <td class="px-4 py-3 align-top font-mono">
                        {{ fila.mediciones }}
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="flex gap-2">
                            <Boton
                                tamano="sm"
                                variante="secundario"
                                class="border-marca text-marca"
                                :href="rutas.diagnosticos.editar(fila.id)"
                            >
                                Editar
                            </Boton>
                            <Boton
                                tamano="sm"
                                variante="secundario"
                                :href="rutas.diagnosticos.vistaPrevia(fila.id)"
                            >
                                Vista previa
                            </Boton>
                        </div>
                        <div
                            class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs text-tinta-suave"
                        >
                            <button
                                type="button"
                                class="text-tinta underline hover:text-marca"
                                @click="$emit('duplicar', fila)"
                            >
                                Duplicar
                            </button>
                            <span aria-hidden="true">·</span>
                            <button
                                v-if="fila.version_publicada"
                                type="button"
                                class="text-tinta underline hover:text-marca"
                                @click="$emit('archivar', fila)"
                            >
                                Archivar
                            </button>
                            <button
                                v-else
                                type="button"
                                class="text-tinta underline hover:text-aviso"
                                @click="$emit('eliminar', fila)"
                            >
                                Eliminar
                            </button>
                            <template v-if="fila.borrador_pendiente">
                                <span aria-hidden="true">·</span>
                                <button
                                    type="button"
                                    class="text-tinta underline hover:text-aviso"
                                    @click="$emit('eliminarBorrador', fila)"
                                >
                                    Eliminar borrador
                                </button>
                            </template>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
