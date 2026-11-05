<script setup lang="ts">
/**
 * Lista de sectores a la izquierda de A2 (HU-010 CA-002): "Todos" con el total
 * de diagnósticos y cada sector con los suyos. Un sector inactivo se marca
 * "○ Inactivo".
 *
 * Si hay muchos sectores, solo la lista baja y sube; el título y
 * "+ Crear sector" quedan fijos.
 */
import { Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { Sector } from '@/types/diagnosticos';
import { usePermisos } from '@/composables/usePermisos';

defineProps<{
    sectores: Sector[];
    /** Sector elegido; null cuando está en "Todos". */
    sectorActualId: number | null;
    totalDiagnosticos: number;
}>();

const { puede } = usePermisos();

defineEmits<{ crear: [] }>();

const claseItem = (activo: boolean) =>
    cn(
        'group flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition-colors',
        activo
            ? 'bg-marca font-medium text-white'
            : 'text-tinta hover:bg-lienzo',
    );

const claseConteo = (activo: boolean, n: number) =>
    cn(
        'min-w-6 shrink-0 rounded-full px-1.5 text-center font-mono text-[11px] font-semibold',
        activo
            ? 'bg-white/20 text-white'
            : n > 0
              ? 'bg-marca-suave text-marca'
              : 'bg-lienzo-oscuro text-tinta-suave',
    );
</script>

<template>
    <nav
        class="flex flex-col rounded-2xl border border-linea bg-white p-3"
        aria-label="Sectores"
    >
        <p
            class="flex shrink-0 items-center justify-between px-3 pt-1 pb-2 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
        >
            Sectores
            <span class="font-mono font-normal normal-case">
                {{ sectores.length }}
            </span>
        </p>
        <ul class="flex min-h-0 flex-col gap-0.5 overflow-y-auto">
            <li>
                <Link
                    :href="rutas.diagnosticos.todos()"
                    :class="claseItem(sectorActualId === null)"
                    :aria-current="sectorActualId === null ? 'page' : undefined"
                >
                    Todos
                    <span
                        :class="
                            claseConteo(
                                sectorActualId === null,
                                totalDiagnosticos,
                            )
                        "
                    >
                        {{ totalDiagnosticos }}
                    </span>
                </Link>
            </li>
            <li v-for="sector in sectores" :key="sector.id">
                <Link
                    :href="rutas.diagnosticos.sector(sector.id)"
                    :class="claseItem(sectorActualId === sector.id)"
                    :aria-current="
                        sectorActualId === sector.id ? 'page' : undefined
                    "
                >
                    <span
                        :class="
                            !sector.activo &&
                            sectorActualId !== sector.id &&
                            'text-tinta-suave'
                        "
                    >
                        {{ sector.nombre }}
                        <span
                            v-if="!sector.activo"
                            class="ml-1 text-xs whitespace-nowrap"
                        >
                            ○ Inactivo
                        </span>
                    </span>
                    <span
                        :class="
                            claseConteo(
                                sectorActualId === sector.id,
                                sector.diagnosticos,
                            )
                        "
                    >
                        {{ sector.diagnosticos }}
                    </span>
                </Link>
            </li>
        </ul>
        <button
            v-if="puede('diagnosticos.editar')"
            type="button"
            class="mt-3 flex w-full shrink-0 items-center justify-center gap-1.5 rounded-lg border border-dashed border-linea-fuerte py-2 text-xs text-tinta transition hover:border-marca hover:bg-marca-suave/50 hover:text-marca"
            @click="$emit('crear')"
        >
            <Plus class="size-3.5" aria-hidden="true" />
            Crear sector
        </button>
    </nav>
</template>
