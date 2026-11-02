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
 * versión publicada). Al lado, el ojo abre la "Vista previa".
 *
 * Cada fila: un ícono verde (publicado) o ámbar (borrador), el nombre con sus
 * categorías y preguntas, el estado en píldoras y el "Uso" (empresas y
 * mediciones).
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
import { ChevronDown, Eye, FileCheck2, FilePen } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import { usePermisos } from '@/composables/usePermisos';
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

const { puede } = usePermisos();

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
    return [
        `${fila.categorias} ${fila.categorias === 1 ? 'categoría' : 'categorías'}`,
        `${fila.preguntas} ${fila.preguntas === 1 ? 'pregunta' : 'preguntas'}`,
    ].join(' · ');
}

const columnas = computed(() => [
    'Diagnóstico',
    ...(props.mostrarSector ? ['Sector'] : []),
    'Estado',
    'Uso',
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
                    <tr class="border-y border-linea bg-lienzo/60 text-left">
                        <th
                            v-if="puede('diagnosticos.editar')"
                            scope="col"
                            class="w-10 py-2.5 pr-0 pl-4"
                        >
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
                            class="px-4 py-2.5 text-xs font-medium text-tinta-suave"
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
                            'border-b border-linea transition-colors last:border-b-0',
                            seleccion.includes(fila.id)
                                ? 'bg-marca-suave'
                                : 'hover:bg-lienzo/40',
                        ]"
                    >
                        <td
                            v-if="puede('diagnosticos.editar')"
                            class="w-10 py-3 pr-0 pl-4 align-middle"
                        >
                            <input
                                type="checkbox"
                                class="size-4 accent-marca disabled:opacity-40"
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
                        <td class="px-4 py-3">
                            <div class="flex min-w-52 items-center gap-3">
                                <span
                                    :class="[
                                        'flex size-9 shrink-0 items-center justify-center rounded-xl',
                                        fila.version_publicada
                                            ? 'bg-exito-suave text-exito'
                                            : 'bg-alerta-suave text-alerta',
                                    ]"
                                    aria-hidden="true"
                                >
                                    <FileCheck2
                                        v-if="fila.version_publicada"
                                        class="size-4"
                                    />
                                    <FilePen v-else class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="leading-snug font-medium">
                                        {{ fila.nombre }}
                                    </p>
                                    <p class="text-xs text-tinta-suave">
                                        {{ detalle(fila) }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td v-if="mostrarSector" class="px-4 py-3">
                            <Link
                                :href="
                                    rutas.diagnosticos.sector(fila.sector_id)
                                "
                                class="inline-block max-w-52 rounded-lg bg-lienzo px-2.5 py-1 text-xs leading-snug text-tinta transition hover:bg-marca-suave hover:text-marca"
                            >
                                {{ fila.sector_nombre }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col items-start gap-1">
                                <Etiqueta
                                    v-if="fila.version_publicada"
                                    tono="exito"
                                    class="rounded-full"
                                >
                                    ● Publicado · v{{ fila.version_publicada }}
                                </Etiqueta>
                                <Etiqueta
                                    v-else
                                    tono="alerta"
                                    class="rounded-full"
                                >
                                    ● Borrador
                                </Etiqueta>
                                <Etiqueta
                                    v-if="fila.borrador_pendiente"
                                    class="rounded-full"
                                >
                                    ✎ Borrador v{{ fila.borrador_pendiente }}
                                    pendiente
                                </Etiqueta>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs whitespace-nowrap">
                            <p>
                                <span class="font-mono font-semibold">{{
                                    fila.empresas
                                }}</span>
                                {{
                                    fila.empresas === 1 ? 'empresa' : 'empresas'
                                }}
                            </p>
                            <p class="text-tinta-suave">
                                <span class="font-mono">{{
                                    fila.mediciones
                                }}</span>
                                {{
                                    fila.mediciones === 1
                                        ? 'medición'
                                        : 'mediciones'
                                }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <DropdownMenu
                                    v-if="puede('diagnosticos.editar')"
                                    :modal="false"
                                >
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
                                <Link
                                    :href="
                                        rutas.diagnosticos.vistaPrevia(fila.id)
                                    "
                                    class="flex size-8 items-center justify-center rounded-lg border border-linea text-tinta-suave transition hover:border-marca hover:text-marca"
                                    :title="`Vista previa de ${fila.nombre}`"
                                    :aria-label="`Vista previa de ${fila.nombre}`"
                                >
                                    <Eye class="size-4" aria-hidden="true" />
                                </Link>
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
