<script setup lang="ts">
/**
 * "Diagnósticos por sector" de A1 (HU-006 CA-003): cada sector activo con
 * sus empresas y la versión publicada de su diagnóstico. Si hay muchos
 * sectores, la lista baja y sube en su espacio.
 */
import { Link } from '@inertiajs/vue3';
import Etiqueta from '@/components/base/Etiqueta.vue';
import { rutas } from '@/lib/rutas';
import type { SectorInicio } from '@/types/inicio';

defineProps<{ sectores: SectorInicio[] }>();
</script>

<template>
    <section
        class="flex min-h-0 flex-col rounded-xl border border-linea bg-white p-4"
    >
        <h2 class="shrink-0 text-sm font-semibold">Diagnósticos por sector</h2>

        <p v-if="sectores.length === 0" class="mt-3 text-sm text-tinta-suave">
            No hay sectores activos.
        </p>

        <ul v-else class="mt-2 min-h-0 overflow-y-auto">
            <li
                v-for="sector in sectores"
                :key="sector.id"
                class="flex items-center justify-between gap-2 border-b border-linea py-2.5 text-sm last:border-b-0"
            >
                <Link
                    :href="rutas.diagnosticos.sector(sector.id)"
                    class="min-w-0 truncate font-medium hover:text-marca hover:underline"
                >
                    {{ sector.nombre }}
                </Link>
                <span class="flex shrink-0 items-center gap-2">
                    <span class="text-xs text-tinta-suave">
                        {{ sector.empresas }}
                        {{ sector.empresas === 1 ? 'empresa' : 'empresas' }}
                    </span>
                    <Etiqueta v-if="sector.version" tono="exito">
                        ✓ v{{ sector.version }} publicada
                    </Etiqueta>
                    <Etiqueta v-else-if="sector.publicados > 1" tono="exito">
                        ✓ {{ sector.publicados }} publicados
                    </Etiqueta>
                    <Etiqueta v-else tono="alerta">Sin publicar</Etiqueta>
                </span>
            </li>
        </ul>
    </section>
</template>
