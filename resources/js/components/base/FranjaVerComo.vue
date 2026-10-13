<script setup lang="ts">
/**
 * A5.4 / A5.4b · Franja de "Ver como" (HU-053, HU-054): avisa a quién se está
 * viendo, que es solo de lectura, y tiene "Salir de Ver como".
 *
 * Mientras está activa, `html.ver-como` hace que las páginas de alto fijo
 * descuenten el alto de la franja (resources/css/app.css).
 */
import { router, usePage } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import { rutas } from '@/lib/rutas';

const page = usePage();
const verComo = computed(() => page.props.verComo);

watchEffect(() => {
    document.documentElement.classList.toggle('ver-como', !!verComo.value);
});

const quien = computed(() => {
    const v = verComo.value;

    return v ? `${v.cuenta} (${v.empresa ?? v.rol ?? 'sin rol'})` : '';
});

function salir(): void {
    router.flushAll();
    router.post(rutas.verComo.salir());
}
</script>

<template>
    <div
        v-if="verComo"
        class="sticky top-0 z-50 flex h-(--alto-ver-como) items-center gap-3 bg-[#7a4510] px-4 text-sm text-white"
        role="status"
    >
        <Eye class="size-4 shrink-0" aria-hidden="true" />
        <p class="min-w-0 flex-1 truncate">
            <strong>Estás viendo como {{ quien }} · Solo lectura</strong>
            <span class="ml-3 hidden text-white/85 md:inline">
                Ves lo mismo que su rol permite. Nada de lo que hagas aquí se
                guarda ni se envía.
            </span>
        </p>
        <button
            type="button"
            class="shrink-0 rounded-md bg-white px-3 py-1 text-xs font-semibold text-[#7a4510] hover:bg-white/90"
            @click="salir"
        >
            Salir de Ver como
        </button>
    </div>
</template>
