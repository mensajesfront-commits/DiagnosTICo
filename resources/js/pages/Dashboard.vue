<script setup lang="ts">
/**
 * Inicio provisional (A1 para el Administrador, E1 para la empresa).
 *
 * [FUNCIONALIDAD POR DEFINIR] Las pantallas reales se construyen en la
 * semana 5 (A1) y la semana 5–6 (E1). Mientras tanto, esta página solo da la
 * bienvenida para poder revisar el menú de cada rol.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const page = usePage();
const nombre = computed(() => page.props.auth.user?.name ?? '');
const esAdministrador = computed(() => page.props.auth.rol === 'Administrador');
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoPagina
            :titulo="`Hola, ${nombre}`"
            :descripcion="
                esAdministrador
                    ? 'Aquí verás las mediciones de todas las empresas.'
                    : 'Aquí verás el diagnóstico de tu empresa y tu resultado.'
            "
        />

        <p
            class="rounded-xl border border-dashed border-linea-fuerte bg-white p-6 text-sm text-tinta-suave"
        >
            Esta pantalla está en construcción. Usa el menú de la izquierda para
            ver las secciones de tu cuenta.
        </p>
    </div>
</template>
