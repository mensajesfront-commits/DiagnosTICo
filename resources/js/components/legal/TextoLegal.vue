<script setup lang="ts">
/**
 * Términos y Condiciones o Política de Privacidad (L2), textos oficiales de
 * Captter. Se usa en la ventana de Crear cuenta. Las secciones de nivel 2 y
 * 3 (1.1, 1.1.1) van con un título más pequeño. Si un texto trae una marca
 * como [INFORMACIÓN PENDIENTE], se resalta.
 */
import TextoConMarcas from '@/components/legal/TextoConMarcas.vue';
import type { DocumentoLegal } from '@/lib/legales';

defineProps<{ documento: DocumentoLegal }>();
</script>

<template>
    <article class="space-y-5 text-sm leading-relaxed text-tinta">
        <p class="text-xs text-tinta-suave">
            Última actualización: {{ documento.actualizacion }}.
        </p>

        <p v-for="(parrafo, i) in documento.introduccion" :key="`i${i}`">
            <TextoConMarcas :texto="parrafo" />
        </p>

        <section
            v-for="seccion in documento.secciones"
            :key="seccion.titulo"
            :class="(seccion.nivel ?? 1) > 1 && '-mt-2'"
        >
            <h3 v-if="(seccion.nivel ?? 1) === 1" class="mb-1.5 font-semibold">
                {{ seccion.titulo }}
            </h3>
            <h4
                v-else
                :class="[
                    'mb-1 font-medium',
                    seccion.nivel === 3 && 'pl-3 text-tinta-suave',
                ]"
            >
                {{ seccion.titulo }}
            </h4>

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
