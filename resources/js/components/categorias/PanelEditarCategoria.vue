<script setup lang="ts">
/**
 * A2.3 · Panel lateral "Editar categoría" (HU-017 CA-003 a CA-005).
 *
 * Envía: nombre (máx. 40, único) y descripcion. El cambio se ve en todos los
 * diagnósticos, también en las versiones publicadas y en los resultados y PDF
 * anteriores, porque el nombre vive en el catálogo.
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import { rutas } from '@/lib/rutas';
import type { Categoria } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 40;

const props = defineProps<{
    categoria: Categoria;
    /** Diagnósticos activos en total ("13 de 13"). */
    totalDiagnosticos: number;
}>();

const emit = defineEmits<{ cerrar: []; retirar: [] }>();

const form = useForm({ nombre: '', descripcion: '' });

watch(
    () => props.categoria,
    (categoria) => {
        form.defaults({
            nombre: categoria.nombre,
            descripcion: categoria.descripcion ?? '',
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

function guardar(): void {
    form.put(rutas.categorias.actualizar(props.categoria.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form
        class="flex flex-col gap-4 rounded-xl border border-linea bg-white p-5 xl:min-h-[640px]"
        @submit.prevent="guardar"
    >
        <h2 class="text-base font-semibold">
            Editar categoría · {{ categoria.nombre }}
        </h2>

        <Campo
            obligatorio
            etiqueta="Nombre"
            para="editar-nombre"
            ayuda="No puede repetirse en el catálogo."
            :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
            :error="form.errors.nombre"
        >
            <Entrada
                id="editar-nombre"
                v-model="form.nombre"
                required
                :maxlength="MAXIMO_NOMBRE"
                :invalida="!!form.errors.nombre"
            />
        </Campo>

        <Campo
            etiqueta="Descripción para la empresa"
            para="editar-descripcion"
            ayuda="Se muestra al iniciar la categoría en el diagnóstico."
            :error="form.errors.descripcion"
        >
            <AreaTexto
                id="editar-descripcion"
                v-model="form.descripcion"
                rows="3"
            />
        </Campo>

        <p class="rounded-md bg-marca-suave px-3 py-2.5 text-sm">
            <strong>
                Usada en {{ categoria.diagnosticos }} de
                {{ totalDiagnosticos }} diagnósticos.
            </strong>
            Para agregarla o quitarla de un diagnóstico, abre ese diagnóstico en
            el editor.
        </p>

        <p
            v-if="categoria.diagnosticos > 0"
            class="rounded-md bg-alerta-suave px-3 py-2.5 text-xs"
        >
            <strong class="text-alerta">
                ⚠ El cambio de nombre o descripción se verá en todas partes:
            </strong>
            en los {{ categoria.diagnosticos }} diagnósticos<template
                v-if="categoria.versiones_publicadas > 0"
            >
                (también en las {{ categoria.versiones_publicadas }} versiones
                publicadas)</template
            >
            y en los resultados e informes PDF anteriores.
        </p>

        <p class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave">
            <template v-if="categoria.tiene_respuestas">
                <strong class="text-tinta">Al archivar:</strong> deja de poder
                agregarse a diagnósticos y se conserva en los resultados
                anteriores. Puedes restaurarla desde el filtro «Archivadas».
            </template>
            <template v-else>
                <strong class="text-tinta">Al eliminar:</strong> ninguna empresa
                la ha respondido, así que se borra del catálogo.
            </template>
        </p>

        <div class="mt-auto flex flex-wrap items-center gap-3 pt-2">
            <Boton variante="secundario" @click="emit('retirar')">
                {{ categoria.tiene_respuestas ? 'Archivar…' : 'Eliminar…' }}
            </Boton>
            <span class="flex-1" />
            <Boton variante="secundario" @click="emit('cerrar')">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                :disabled="!form.isDirty"
                :cargando="form.processing"
            >
                Guardar cambios
            </Boton>
        </div>
    </form>
</template>
