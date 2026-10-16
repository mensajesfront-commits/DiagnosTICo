<script setup lang="ts">
/**
 * L2 · Términos de uso y política de tratamiento de datos, en página pública
 * (/terminos-de-uso y /politica-de-datos) para enlazarlos desde fuera del
 * registro. El texto está en `lib/legales.ts`; solo se desplaza el recuadro.
 */
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import TextoLegal from '@/components/legal/TextoLegal.vue';
import { documentosLegales } from '@/lib/legales';
import { login } from '@/routes';

defineOptions({ layout: { ancho: 'lg' } });

const props = defineProps<{ documento: 'terminos' | 'politica' }>();

const texto = computed(() => documentosLegales[props.documento]);
</script>

<template>
    <Head :title="texto.titulo" />

    <Link
        :href="login()"
        class="mb-3 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
    >
        <ArrowLeft class="size-4" aria-hidden="true" />
        Iniciar sesión
    </Link>

    <h1 class="text-2xl font-semibold">{{ texto.titulo }}</h1>
    <p class="mt-1 mb-4 text-sm text-tinta-suave">{{ texto.resumen }}</p>

    <div
        class="max-h-[65vh] overflow-y-auto rounded-xl border border-linea bg-white px-5 py-4"
    >
        <TextoLegal :documento="texto" />
    </div>
</template>
