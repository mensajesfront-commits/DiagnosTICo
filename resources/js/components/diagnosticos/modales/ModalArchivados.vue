<script setup lang="ts">
/**
 * A2 · Diagnósticos archivados (HU-022). Lista los archivados del sector (o
 * todos) con dos acciones:
 * - "Restaurar": vuelve a publicado y se puede asignar otra vez.
 * - "Eliminar": para siempre, con su historial de versiones. Pide una
 *   segunda confirmación. Solo si ninguna empresa lo respondió: los
 *   resultados guardan la versión con la que se hicieron (RN-010, RN-023).
 */
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import type { DiagnosticoArchivado } from '@/types/diagnosticos';

const props = defineProps<{
    archivados: DiagnosticoArchivado[];
    mostrarSector: boolean;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

/** Fila que pide la segunda confirmación para eliminar. */
const confirmando = ref<number | null>(null);
const ocupado = ref<number | null>(null);

watch(abierto, (valor) => valor || (confirmando.value = null));

const page = usePage();
const error = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.diagnostico,
);

const opciones = (id: number) => ({
    preserveScroll: true,
    onStart: () => (ocupado.value = id),
    onFinish: () => {
        ocupado.value = null;
        confirmando.value = null;
    },
});

function restaurar(d: DiagnosticoArchivado): void {
    router.post(rutas.diagnosticos.restaurar(d.id), {}, opciones(d.id));
}

function eliminar(d: DiagnosticoArchivado): void {
    router.delete(rutas.diagnosticos.eliminarArchivado(d.id), opciones(d.id));
}

const fecha = (iso: string | null) =>
    iso ? diaYMes(fechaLocal(iso.slice(0, 10))) : '';

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Diagnósticos archivados"
        descripcion="No se asignan a nuevas mediciones. Sus mediciones y resultados se conservan."
        ancho="lg"
    >
        <p
            v-if="archivados.length === 0"
            class="py-6 text-center text-sm text-tinta-suave"
        >
            No hay diagnósticos archivados.
        </p>

        <p
            v-if="error"
            class="mb-3 rounded-md bg-aviso-suave px-3 py-2 text-xs text-aviso"
            role="alert"
        >
            {{ error }}
        </p>

        <ul class="flex flex-col divide-y divide-linea">
            <li
                v-for="d in archivados"
                :key="d.id"
                class="flex flex-wrap items-center justify-between gap-3 py-3"
            >
                <div class="min-w-0">
                    <p class="font-medium">{{ d.nombre }}</p>
                    <p class="text-xs text-tinta-suave">
                        <template v-if="mostrarSector && d.sector_nombre"
                            >{{ d.sector_nombre }} ·
                        </template>
                        <template v-if="d.version_publicada"
                            >v{{ d.version_publicada }} ·
                        </template>
                        Archivado el {{ fecha(d.archivado_en) }} ·
                        {{
                            d.mediciones > 0
                                ? `${plural(d.mediciones, 'medición', 'mediciones')} de ${plural(d.empresas, 'empresa', 'empresas')}`
                                : 'Ninguna empresa lo respondió'
                        }}
                    </p>
                </div>

                <div
                    v-if="confirmando === d.id"
                    class="flex flex-wrap items-center gap-2 text-sm"
                    role="alert"
                >
                    <span class="text-aviso">¿Eliminar para siempre?</span>
                    <Boton
                        variante="secundario"
                        tamano="sm"
                        @click="confirmando = null"
                    >
                        No
                    </Boton>
                    <Boton
                        variante="peligro"
                        tamano="sm"
                        :cargando="ocupado === d.id"
                        @click="eliminar(d)"
                    >
                        Sí, eliminar
                    </Boton>
                </div>
                <div v-else class="flex items-center gap-3 text-sm">
                    <button
                        type="button"
                        class="text-marca underline underline-offset-2 hover:text-marca-hover disabled:text-tinta-suave"
                        :disabled="ocupado !== null"
                        @click="restaurar(d)"
                    >
                        Restaurar
                    </button>
                    <button
                        type="button"
                        class="text-tinta underline underline-offset-2 hover:text-aviso disabled:cursor-not-allowed disabled:text-tinta-suave disabled:no-underline"
                        :disabled="d.mediciones > 0 || ocupado !== null"
                        :title="
                            d.mediciones > 0
                                ? 'Tiene mediciones de empresas: se queda archivado para no perder sus resultados.'
                                : undefined
                        "
                        @click="confirmando = d.id"
                    >
                        Eliminar
                    </button>
                </div>
            </li>
        </ul>

        <p
            v-if="archivados.some((d) => d.mediciones > 0)"
            class="mt-3 text-xs text-tinta-suave"
        >
            Los que tienen mediciones no se eliminan: los resultados de esas
            empresas se hicieron con ese diagnóstico.
        </p>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cerrar
            </Boton>
        </template>
    </Modal>
</template>
