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
import {
    Archive,
    ArchiveRestore,
    Building2,
    CalendarDays,
    ClipboardList,
    Lock,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { DiagnosticoArchivado } from '@/types/diagnosticos';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps<{
    archivados: DiagnosticoArchivado[];
    mostrarSector: boolean;
}>();

const { puede } = usePermisos();

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
        :icono="Archive"
        tono="alerta"
    >
        <div
            v-if="archivados.length === 0"
            class="flex flex-col items-center gap-2 py-8 text-center text-sm text-tinta-suave"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-lienzo"
                aria-hidden="true"
            >
                <Archive class="size-6" />
            </span>
            No hay diagnósticos archivados.
        </div>

        <Recuadro v-if="error" tono="peligro" class="mb-3" role="alert">
            {{ error }}
        </Recuadro>

        <ul class="flex flex-col gap-2">
            <li
                v-for="d in archivados"
                :key="d.id"
                :class="
                    cn(
                        'rounded-xl border p-3 transition-colors',
                        confirmando === d.id
                            ? 'border-aviso/40 bg-aviso-suave/40'
                            : 'border-linea',
                    )
                "
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-lienzo-oscuro text-tinta-suave"
                            aria-hidden="true"
                        >
                            <Archive class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-medium">{{ d.nombre }}</p>
                            <p
                                class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-tinta-suave"
                            >
                                <span
                                    v-if="mostrarSector && d.sector_nombre"
                                    class="rounded-full bg-lienzo px-2 py-0.5 text-tinta"
                                >
                                    {{ d.sector_nombre }}
                                </span>
                                <span
                                    v-if="d.version_publicada"
                                    class="rounded-full bg-lienzo px-2 py-0.5"
                                >
                                    v{{ d.version_publicada }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-lienzo px-2 py-0.5"
                                >
                                    <CalendarDays
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    Archivado el {{ fecha(d.archivado_en) }}
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5',
                                            d.mediciones > 0
                                                ? 'bg-marca-suave text-marca'
                                                : 'bg-lienzo',
                                        )
                                    "
                                >
                                    <ClipboardList
                                        v-if="d.mediciones > 0"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    <Building2
                                        v-else
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    {{
                                        d.mediciones > 0
                                            ? `${plural(d.mediciones, 'medición', 'mediciones')} de ${plural(d.empresas, 'empresa', 'empresas')}`
                                            : 'Ninguna empresa lo respondió'
                                    }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="
                            confirmando !== d.id && puede('diagnosticos.editar')
                        "
                        class="flex shrink-0 items-center gap-1.5"
                    >
                        <Boton
                            variante="secundario"
                            tamano="sm"
                            class="gap-1.5"
                            :disabled="ocupado !== null"
                            @click="restaurar(d)"
                        >
                            <ArchiveRestore
                                class="size-3.5"
                                aria-hidden="true"
                            />
                            Restaurar
                        </Boton>
                        <Boton
                            v-if="d.mediciones === 0"
                            variante="secundario"
                            tamano="sm"
                            class="gap-1.5 text-aviso"
                            :disabled="ocupado !== null"
                            @click="confirmando = d.id"
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                            Eliminar
                        </Boton>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-1 text-[11px] text-tinta-suave"
                            title="Tiene mediciones de empresas: se queda archivado para no perder sus resultados."
                        >
                            <Lock class="size-3.5" aria-hidden="true" />
                            No se elimina
                        </span>
                    </div>
                </div>

                <div
                    v-if="confirmando === d.id"
                    class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white px-3 py-2.5"
                    role="alert"
                >
                    <p class="flex items-start gap-2 text-sm">
                        <Trash2
                            class="mt-0.5 size-4 shrink-0 text-aviso"
                            aria-hidden="true"
                        />
                        <span>
                            <strong class="text-aviso">
                                ¿Eliminar para siempre?
                            </strong>
                            <span class="block text-xs text-tinta-suave">
                                Se borra con todo su historial de versiones. No
                                se puede deshacer.
                            </span>
                        </span>
                    </p>
                    <div class="flex gap-2">
                        <Boton
                            variante="secundario"
                            tamano="sm"
                            @click="confirmando = null"
                        >
                            No, conservarlo
                        </Boton>
                        <Boton
                            variante="peligro"
                            tamano="sm"
                            class="gap-1.5"
                            :cargando="ocupado === d.id"
                            @click="eliminar(d)"
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                            Sí, eliminar
                        </Boton>
                    </div>
                </div>
            </li>
        </ul>

        <Recuadro
            v-if="archivados.some((d) => d.mediciones > 0)"
            tono="neutro"
            class="mt-3"
        >
            Los que tienen mediciones no se eliminan: los resultados de esas
            empresas se hicieron con ese diagnóstico.
        </Recuadro>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cerrar
            </Boton>
        </template>
    </Modal>
</template>
