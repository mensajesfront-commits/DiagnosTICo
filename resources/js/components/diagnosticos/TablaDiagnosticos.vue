<script setup lang="ts">
/**
 * Tabla de diagnósticos de A2 (un sector) y A2·T (todos). HU-010 CA-004 y
 * HU-011.
 *
 * En "Todos" no se agrupa por sector: la columna "Sector", al lado de
 * "Estado", dice a qué sector pertenece cada diagnóstico (`mostrarSector`).
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
import type { FilaDiagnostico } from '@/types/diagnosticos';

const props = withDefaults(
    defineProps<{
        filas: FilaDiagnostico[];
        /** En "Todos": la columna "Sector", al lado de "Estado". */
        mostrarSector?: boolean;
    }>(),
    { mostrarSector: false },
);

defineEmits<{
    duplicar: [fila: FilaDiagnostico];
    archivar: [fila: FilaDiagnostico];
    eliminar: [fila: FilaDiagnostico];
    eliminarBorrador: [fila: FilaDiagnostico];
}>();

function detalle(fila: FilaDiagnostico): string {
    const partes = [
        `${fila.categorias} ${fila.categorias === 1 ? 'categoría' : 'categorías'}`,
    ];

    if (fila.borrador_pendiente && fila.version_publicada) {
        partes.push(`v${fila.version_publicada} publicada`);
    }

    return partes.join(' · ');
}

const columnas = computed(() => [
    'Diagnóstico',
    'Estado',
    ...(props.mostrarSector ? ['Sector'] : []),
    'Preguntas',
    'Empresas',
    'Mediciones',
    'Acciones',
]);
</script>

<template>
    <!-- Baja y sube dentro de su espacio; el encabezado queda fijo arriba. -->
    <div class="overflow-auto">
        <table class="w-full border-collapse text-sm">
            <thead class="sticky top-0 z-10">
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
            <tbody>
                <tr
                    v-for="fila in filas"
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
                    <td v-if="mostrarSector" class="px-4 py-3 align-top">
                        <Link
                            :href="rutas.diagnosticos.sector(fila.sector_id)"
                            class="text-tinta hover:text-marca hover:underline"
                        >
                            {{ fila.sector_nombre }}
                        </Link>
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
