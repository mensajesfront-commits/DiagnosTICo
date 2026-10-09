<script setup lang="ts">
/**
 * Tabla "Mediciones" de A1 (HU-006 CA-002, HU-007): empresa y sector,
 * fechas, estado con su avance por categorías y la acción de cada estado.
 *
 * Paginada con las filas que caben en su espacio (la página no se
 * desplaza). Vuelve a la página 1 al cambiar de pestaña.
 *
 * [FUNCIONALIDAD POR DEFINIR] Las acciones (Recordatorio, Reenviar aviso,
 * Nueva fecha, Cancelar, Ver resultado, Asignar nueva) abren sus modales
 * A1b–A1e, que llegan con Empresas (A3). Por ahora llevan a la ficha de la
 * empresa (A3.1), donde vivirán esas acciones.
 */
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import { diaYMes, fechaLocal, haceCuanto } from '@/lib/fechas';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { FilaMedicion } from '@/types/inicio';

const props = defineProps<{ filas: FilaMedicion[] }>();

const contenedor = ref<HTMLElement | null>(null);
const { porPagina, medir, ajustar } = useFilasQueCaben(contenedor);
const { pagina, paginas, total, desde, visibles } = usePaginacion(
    () => props.filas,
    porPagina,
);

watch(
    () => props.filas.length > 0,
    () => medir(),
);
watch(visibles, ajustar);

const fecha = (texto: string) => diaYMes(fechaLocal(texto));

/** Ancho de la barra de avance, de 0 a 100. */
const porcentaje = (f: FilaMedicion) =>
    f.avance && f.avance.total > 0
        ? Math.round((f.avance.hechas / f.avance.total) * 100)
        : 0;

type Accion = { texto: string; peligro?: boolean };

function acciones(f: FilaMedicion): Accion[] {
    switch (f.estado) {
        case 'vencida':
            return [
                { texto: 'Nueva fecha' },
                { texto: 'Cancelar', peligro: true },
            ];
        case 'en_curso':
            return [{ texto: 'Recordatorio' }];
        case 'no_iniciada':
            return [{ texto: 'Reenviar aviso' }];
        case 'terminada':
            return f.pendiente_numero
                ? [{ texto: 'Ver resultado' }]
                : [{ texto: 'Ver resultado' }, { texto: 'Asignar nueva' }];
        default:
            return [];
    }
}

function nota(f: FilaMedicion): string | null {
    switch (f.estado) {
        case 'vencida':
        case 'en_curso':
            return f.ultimo_avance
                ? `Último avance: ${haceCuanto(f.ultimo_avance)}`
                : 'Sin respuestas todavía';
        case 'enviada':
            return 'La IA está analizando sus respuestas';
        case 'no_iniciada':
            return f.ultimo_aviso
                ? `Último aviso: ${haceCuanto(f.ultimo_aviso)}`
                : 'Sin aviso por correo';
        case 'terminada':
            return f.pendiente_numero
                ? `Tiene la medición ${f.pendiente_numero} en curso`
                : null;
        default:
            return null;
    }
}

const columnas = ['Empresa', 'Asignada', 'Vence', 'Estado', 'Acción'];
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
                        <th
                            v-for="columna in columnas"
                            :key="columna"
                            scope="col"
                            :class="
                                cn(
                                    'px-4 py-2.5 text-xs font-normal text-tinta-suave',
                                    columna === 'Acción' && 'text-right',
                                )
                            "
                        >
                            {{ columna }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="fila in visibles"
                        :key="fila.id"
                        class="border-b border-linea last:border-b-0"
                    >
                        <td class="px-4 py-2.5">
                            <Link
                                :href="rutas.empresas.ver(fila.empresa_id)"
                                class="font-medium hover:text-marca hover:underline"
                            >
                                {{ fila.empresa }}
                            </Link>
                            <p class="text-xs text-tinta-suave">
                                {{ fila.sector }}
                            </p>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            {{ fecha(fila.asignada) }}
                        </td>
                        <td
                            :class="
                                cn(
                                    'px-4 py-2.5 whitespace-nowrap',
                                    fila.estado === 'vencida' &&
                                        'font-medium text-aviso',
                                    !fila.vence && 'text-xs text-tinta-suave',
                                )
                            "
                        >
                            {{
                                fila.vence
                                    ? fecha(fila.vence)
                                    : 'Sin fecha límite'
                            }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span
                                v-if="
                                    fila.estado === 'terminada' &&
                                    fila.resultado
                                "
                                class="flex items-center gap-2"
                            >
                                <strong class="font-mono text-base">
                                    {{ fila.resultado.puntaje }}
                                </strong>
                                <EtiquetaNivel :nivel="fila.resultado.nivel" />
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <EtiquetaEstado
                                    :estado="fila.estado"
                                    :avance="fila.avance ?? undefined"
                                />
                                <span
                                    v-if="fila.avance"
                                    class="h-1.5 w-10 shrink-0 rounded-full bg-lienzo-oscuro"
                                    aria-hidden="true"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'block h-1.5 rounded-full',
                                                fila.estado === 'vencida'
                                                    ? 'bg-aviso'
                                                    : 'bg-marca',
                                            )
                                        "
                                        :style="{
                                            width: `${porcentaje(fila)}%`,
                                        }"
                                    />
                                </span>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <p class="text-sm whitespace-nowrap">
                                <template
                                    v-for="(accion, i) in acciones(fila)"
                                    :key="accion.texto"
                                >
                                    <span
                                        v-if="i > 0"
                                        class="mx-1 text-tinta-suave"
                                        aria-hidden="true"
                                        >·</span
                                    >
                                    <Link
                                        :href="
                                            rutas.empresas.ver(fila.empresa_id)
                                        "
                                        :class="
                                            cn(
                                                'underline underline-offset-2',
                                                accion.peligro
                                                    ? 'text-tinta hover:text-aviso'
                                                    : 'text-marca hover:text-marca-hover',
                                            )
                                        "
                                    >
                                        {{ accion.texto }}
                                    </Link>
                                </template>
                            </p>
                            <p
                                v-if="nota(fila)"
                                class="text-xs text-tinta-suave"
                            >
                                {{ nota(fila) }}
                            </p>
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
        />
    </div>
</template>
