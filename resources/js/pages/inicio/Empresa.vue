<script setup lang="ts">
/**
 * E1 · Inicio de la empresa y del colaborador (provisional, T-048).
 *
 * [FUNCIONALIDAD POR DEFINIR] La medición pendiente, el resultado y el
 * historial de E1 llegan en las semanas 5 y 6. Mientras tanto, solo da la
 * bienvenida.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import { empresa } from '@/routes/inicio';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: empresa() }],
    },
});

const page = usePage();
const nombre = computed(() => page.props.auth.user?.name ?? '');
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoPagina
            :titulo="`Hola, ${nombre}`"
            descripcion="Aquí verás el diagnóstico de tu empresa y tu resultado."
        />

        <p
            class="rounded-xl border border-dashed border-linea-fuerte bg-white p-6 text-sm text-tinta-suave"
        >
            Esta pantalla (E1) está en construcción. Usa el menú de la izquierda
            para ver las secciones de tu cuenta.
        </p>
    </div>
</template>
