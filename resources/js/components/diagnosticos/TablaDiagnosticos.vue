<script setup lang="ts">
/**
 * Tabla de diagnósticos de A2 (un sector) y A2·T (todos). HU-010 CA-004 y
 * HU-011.
 *
 * En "Todos" no se agrupa por sector: la columna "Sector", antes de
 * "Estado", dice a qué sector pertenece cada diagnóstico (`mostrarSector`).
 *
 * Acciones por fila: "Editar ▾" abre un menú con Editar diagnóstico,
 * Duplicar, Archivar (si tiene una versión publicada) o Eliminar (si nunca se
 * publicó) y "Eliminar borrador" (si hay un borrador pendiente sobre la
 * versión publicada). Al lado queda "Vista previa".
 *
 * Selección: la casilla de cada fila elige los diagnósticos que nunca se
 * publicaron, para eliminar varios a la vez (los publicados se archivan, no
 * se eliminan, y su casilla queda desactivada). La casilla del encabezado
 * elige o quita los de la página. Con algo elegido, el pie muestra "N
 * seleccionados · Quitar selección · Eliminar seleccionados" (en el pie, para
 * no cambiar el alto de la tabla ni las filas por página).
 *
 * Paginada: en pantallas grandes muestra las filas que caben en su espacio
 * (la página no se desplaza); en pantallas pequeñas, 10 por página. Vuelve a
 * la página 1 al buscar, filtrar u ordenar.
 */
import { Link, router } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import type { FilaDiagnostico } from '@/types/diagnosticos';

const props = withDefaults(
    defineProps<{
        filas: FilaDiagnostico[];
        /** En "Todos": la columna "Sector", antes de "Estado". */
        mostrarSector?: boolean;
    }>(),
    { mostrarSector: false },
);

/** Ids elegidos para eliminar varios a la vez. */
const seleccion = defineModel<number[]>('seleccion', { default: () => [] });

defineEmits<{
    duplicar: [fila: FilaDiagnostico];
    archivar: [fila: FilaDiagnostico];
    eliminar: [fila: FilaDiagnostico];
    eliminarBorrador: [fila: FilaDiagnostico];
    eliminarSeleccion: [];
}>();

// --- Paginado según el alto disponible --------------------------------------
const contenedor = ref<HTMLElement | null>(null);
const { porPagina, medir, ajustar } = useFilasQueCaben(contenedor);

const { pagina, paginas, total, desde, visibles } = usePaginacion(
    () => props.filas,
    porPagina,
);

// Si la lista pasa de vacía a tener filas, se mide otra vez.
watch(
    () => props.filas.length > 0,
    () => medir(),
);
watch(visibles, ajustar);

const seElimina = (fila: FilaDiagnostico) => fila.version_publicada === null;

const eliminablesDeLaPagina = computed(() =>
    visibles.value.filter(seElimina).map((f) => f.id),
);
const paginaElegida = computed(
    () =>
        eliminablesDeLaPagina.value.length > 0 &&
        eliminablesDeLaPagina.value.every((id) => seleccion.value.includes(id)),
);

function alternarPagina(): void {
    const ids = eliminablesDeLaPagina.value;

    seleccion.value = paginaElegida.value
        ? seleccion.value.filter((id) => !ids.includes(id))
        : [...new Set([...seleccion.value, ...ids])];
}

function alternar(id: number): void {
    seleccion.value = seleccion.value.includes(id)
        ? seleccion.value.filter((x) => x !== id)
        : [...seleccion.value, id];
}

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
    ...(props.mostrarSector ? ['Sector'] : []),
    'Estado',
    'Preguntas',
    'Empresas',
    'Mediciones',
    'Acciones',
]);
</script>

<template>
    <div class="flex flex-col">
        <div
            ref="contenedor"
            class="min-h-0 flex-1 overflow-x-auto overflow-y-hidden"
        >
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-y border-linea bg-[#f9f8f4] text-left">
                        <th scope="col" class="w-10 py-2.5 pr-0 pl-4">
                            <input
                                type="checkbox"
                                class="size-4 accent-marca disabled:opacity-40"
                                aria-label="Elegir los borradores de esta página"
                                :checked="paginaElegida"
                                :disabled="eliminablesDeLaPagina.length === 0"
                                @change="alternarPagina"
                            />
                        </th>
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
                        v-for="fila in visibles"
                        :key="fila.id"
                        :class="[
                            'border-b border-linea last:border-b-0',
                            seleccion.includes(fila.id) && 'bg-marca-suave',
                        ]"
                    >
                        <td class="w-10 py-3 pr-0 pl-4 align-top">
                            <input
                                type="checkbox"
                                class="mt-0.5 size-4 accent-marca disabled:opacity-40"
                                :aria-label="`Elegir ${fila.nombre}`"
                                :title="
                                    seElimina(fila)
                                        ? undefined
                                        : 'Está publicado: se archiva, no se elimina'
                                "
                                :checked="seleccion.includes(fila.id)"
                                :disabled="!seElimina(fila)"
                                @change="alternar(fila.id)"
                            />
                        </td>
                        <td class="px-4 py-3 align-top">
                            <p class="min-w-44 font-medium">
                                {{ fila.nombre }}
                            </p>
                            <p class="text-xs text-tinta-suave">
                                {{ detalle(fila) }}
                            </p>
                        </td>
                        <td v-if="mostrarSector" class="px-4 py-3 align-top">
                            <Link
                                :href="
                                    rutas.diagnosticos.sector(fila.sector_id)
                                "
                                class="text-tinta hover:text-marca hover:underline"
                            >
                                {{ fila.sector_nombre }}
                            </Link>
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
                                    Borrador
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
                                <DropdownMenu :modal="false">
                                    <DropdownMenuTrigger as-child>
                                        <Boton
                                            tamano="sm"
                                            variante="secundario"
                                            class="gap-1 border-marca text-marca"
                                            :aria-label="`Editar ${fila.nombre}`"
                                        >
                                            Editar
                                            <ChevronDown class="size-3.5" />
                                        </Boton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="start"
                                        class="min-w-44 bg-white text-tinta"
                                    >
                                        <DropdownMenuItem
                                            @select="
                                                router.visit(
                                                    rutas.diagnosticos.editar(
                                                        fila.id,
                                                    ),
                                                )
                                            "
                                        >
                                            Editar diagnóstico
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @select="$emit('duplicar', fila)"
                                        >
                                            Duplicar
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            v-if="fila.version_publicada"
                                            @select="$emit('archivar', fila)"
                                        >
                                            Archivar
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-else
                                            class="text-aviso focus:text-aviso"
                                            @select="$emit('eliminar', fila)"
                                        >
                                            Eliminar
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="fila.borrador_pendiente"
                                            class="text-aviso focus:text-aviso"
                                            @select="
                                                $emit('eliminarBorrador', fila)
                                            "
                                        >
                                            Eliminar borrador
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <Boton
                                    tamano="sm"
                                    variante="secundario"
                                    :href="
                                        rutas.diagnosticos.vistaPrevia(fila.id)
                                    "
                                >
                                    Vista previa
                                </Boton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Paginacion
            v-model:pagina="pagina"
            class="shrink-0"
            :desde="desde"
            :cantidad="visibles.length"
            :total="total"
            :paginas="paginas"
        >
            <template v-if="seleccion.length > 0">
                <span class="text-sm font-medium text-marca" role="status">
                    {{
                        seleccion.length === 1
                            ? '1 seleccionado'
                            : `${seleccion.length} seleccionados`
                    }}
                </span>
                <Boton
                    variante="secundario"
                    tamano="sm"
                    @click="seleccion = []"
                >
                    Quitar selección
                </Boton>
                <Boton
                    variante="peligro"
                    tamano="sm"
                    @click="$emit('eliminarSeleccion')"
                >
                    Eliminar seleccionados
                </Boton>
            </template>
        </Paginacion>
    </div>
</template>
