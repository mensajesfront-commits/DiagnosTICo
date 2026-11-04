<script setup lang="ts">
/**
 * Texto de los términos de uso o de la política de datos (L2). Se usa en la
 * ventana de Crear cuenta. Las marcas
 * [INFORMACIÓN PENDIENTE] se resaltan para que nadie las pase por alto.
 */
import TextoConMarcas from '@/components/legal/TextoConMarcas.vue';
import type { DocumentoLegal } from '@/lib/legales';

defineProps<{ documento: DocumentoLegal }>();
</script>

<template>
    <article class="space-y-5 text-sm leading-relaxed text-tinta">
        <p class="text-xs text-tinta-suave">
            Versión {{ documento.version }} · Vigente desde:
            <TextoConMarcas :texto="documento.vigencia" />
        </p>

        <section v-for="seccion in documento.secciones" :key="seccion.titulo">
            <h3 class="mb-1.5 font-semibold">{{ seccion.titulo }}</h3>

            <p
                v-for="(parrafo, i) in seccion.parrafos ?? []"
                :key="`p${i}`"
                class="mb-1.5"
            >
                <TextoConMarcas :texto="parrafo" />
            </p>

            <ul
                v-if="seccion.lista?.length"
                class="mb-1.5 list-disc space-y-1 pl-5"
            >
                <li v-for="(punto, i) in seccion.lista" :key="`l${i}`">
                    <TextoConMarcas :texto="punto" />
                </li>
            </ul>

            <p
                v-for="(parrafo, i) in seccion.cierre ?? []"
                :key="`c${i}`"
                class="mb-1.5"
            >
                <TextoConMarcas :texto="parrafo" />
            </p>
        </section>
    </article>
</template>
