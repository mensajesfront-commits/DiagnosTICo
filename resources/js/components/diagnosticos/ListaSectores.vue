<script setup lang="ts">
/**
 * Lista de sectores a la izquierda de A2 (HU-010 CA-002): "Todos" con el total
 * de diagnósticos y cada sector con los suyos. Un sector inactivo se marca
 * "○ Inactivo".
 */
import { Link } from '@inertiajs/vue3';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { Sector } from '@/types/diagnosticos';

defineProps<{
    sectores: Sector[];
    /** Sector elegido; null cuando está en "Todos". */
    sectorActualId: number | null;
    totalDiagnosticos: number;
}>();

defineEmits<{ crear: [] }>();

const claseItem = (activo: boolean) =>
    cn(
        'flex items-center justify-between rounded-md px-3 py-2 text-sm transition-colors',
        activo
            ? 'bg-marca-suave font-medium text-marca'
            : 'text-tinta hover:bg-lienzo',
    );
</script>

<template>
    <nav
        class="rounded-xl border border-linea bg-white p-3"
        aria-label="Sectores"
    >
        <p class="px-3 pt-1 pb-2 text-xs text-tinta-suave">Sectores</p>
        <ul class="flex flex-col gap-0.5">
            <li>
                <Link
                    :href="rutas.diagnosticos.todos()"
                    :class="claseItem(sectorActualId === null)"
                    :aria-current="sectorActualId === null ? 'page' : undefined"
                >
                    Todos
                    <span class="font-mono text-xs text-tinta-suave">
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
                    <span :class="!sector.activo && 'text-tinta-suave'">
                        {{ sector.nombre }}
                        <span v-if="!sector.activo" class="ml-1 text-xs">
                            ○ Inactivo
                        </span>
                    </span>
                    <span class="font-mono text-xs text-tinta-suave">
                        {{ sector.diagnosticos }}
                    </span>
                </Link>
            </li>
        </ul>
        <button
            type="button"
            class="mt-3 w-full rounded-md border border-dashed border-linea-fuerte py-2 text-xs text-tinta hover:border-marca hover:text-marca"
            @click="$emit('crear')"
        >
            + Crear sector
        </button>
    </nav>
</template>
