<script setup lang="ts">
/**
 * A1 · Inicio del Administrador (provisional, T-048).
 *
 * [FUNCIONALIDAD POR DEFINIR] Los indicadores y las mediciones de A1 se
 * construyen en la semana 5. Mientras tanto, solo da la bienvenida.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import { administrador } from '@/routes/inicio';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: administrador() }],
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
            descripcion="Aquí verás las mediciones de todas las empresas."
        />

        <p
            class="rounded-xl border border-dashed border-linea-fuerte bg-white p-6 text-sm text-tinta-suave"
        >
            Esta pantalla (A1) está en construcción. Usa el menú de la izquierda
            para ver las secciones de tu cuenta.
        </p>
    </div>
</template>
