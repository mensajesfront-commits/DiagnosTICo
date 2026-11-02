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
        class="flex min-h-0 flex-col rounded-2xl border border-linea bg-white p-5"
    >
        <h2 class="shrink-0 text-base font-semibold">
            Diagnósticos por sector
        </h2>
        <p class="shrink-0 text-xs text-tinta-suave">
            Versión publicada y empresas de cada sector
        </p>

        <p v-if="sectores.length === 0" class="mt-3 text-sm text-tinta-suave">
            No hay sectores activos.
        </p>

        <ul v-else class="mt-2 min-h-0 overflow-y-auto">
            <li
                v-for="sector in sectores"
                :key="sector.id"
                class="border-b border-linea last:border-b-0"
            >
                <Link
                    :href="rutas.diagnosticos.sector(sector.id)"
                    class="flex items-center gap-3 rounded-lg px-1 py-2.5 hover:bg-lienzo"
                >
                    <span
                        :class="[
                            'size-2.5 shrink-0 rounded-full',
                            sector.version || sector.publicados > 1
                                ? 'bg-nivel-sigue'
                                : 'bg-nivel-mejorar',
                        ]"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm leading-snug font-medium">{{
                            sector.nombre
                        }}</span>
                        <span class="block text-xs text-tinta-suave">
                            {{ sector.empresas }}
                            {{ sector.empresas === 1 ? 'empresa' : 'empresas' }}
                        </span>
                    </span>
                    <Etiqueta v-if="sector.version" tono="exito">
                        v{{ sector.version }} publicada
                    </Etiqueta>
                    <Etiqueta v-else-if="sector.publicados > 1" tono="exito">
                        {{ sector.publicados }} publicados
                    </Etiqueta>
                    <Etiqueta v-else tono="alerta">Sin publicar</Etiqueta>
                </Link>
            </li>
        </ul>
    </section>
</template>
