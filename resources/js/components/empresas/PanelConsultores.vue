<script setup lang="ts">
/**
 * A3.1 · Pestaña "Consultores" (HU-085, RN-030).
 *
 * Los consultores que acompañan a la empresa: solo miran sus resultados, su
 * historial y el PDF; no responden ni tocan diagnósticos. Quien gestiona
 * usuarios vincula uno de la lista (cuentas con el rol Consultor) o lo quita,
 * con una confirmación. Un consultor puede acompañar varias empresas.
 * Si no hay consultores, se invitan desde Usuarios y roles.
 */
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Eye,
    Link2,
    MailQuestion,
    Unlink,
    UserCheck,
    UserPlus,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import { iniciales } from '@/lib/usuarios';
import type { ConsultoresEmpresa } from '@/types/empresas';

const props = defineProps<{
    empresa: { id: number; nombre: string };
    consultores: ConsultoresEmpresa;
}>();

const form = useForm<{ consultor_id: number | '' }>({ consultor_id: '' });

function vincular(): void {
    form.post(rutas.empresas.consultores.vincular(props.empresa.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

type Vinculado = ConsultoresEmpresa['vinculados'][number];

const quitando = ref<Vinculado | null>(null);
const procesando = ref(false);
const modalQuitar = computed({
    get: () => quitando.value !== null,
    set: (v) => {
        if (!v) {
            quitando.value = null;
        }
    },
});

function quitar(): void {
    if (!quitando.value) {
        return;
    }

    router.delete(
        rutas.empresas.consultores.quitar(props.empresa.id, quitando.value.id),
        {
            preserveScroll: true,
            onStart: () => (procesando.value = true),
            onFinish: () => {
                procesando.value = false;
                quitando.value = null;
            },
        },
    );
}

const puede = [
    'Ver la ficha, el historial y la evolución',
    'Ver cada resultado y las respuestas',
    'Descargar el informe en PDF',
];
</script>

<template>
    <div
        class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[1fr_320px]"
    >
        <section
            class="flex flex-col rounded-2xl border border-linea bg-white lg:max-h-full lg:min-h-0"
            aria-labelledby="titulo-consultores"
        >
            <header
                class="flex shrink-0 items-start gap-3 border-b border-linea bg-linear-to-br from-marca-suave/70 to-white px-5 py-4"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                    aria-hidden="true"
                >
                    <UserCheck class="size-5" />
                </span>
                <div>
                    <h2 id="titulo-consultores" class="font-semibold">
                        Consultores de {{ empresa.nombre }}
                    </h2>
                    <p class="text-sm text-tinta-suave">
                        {{
                            consultores.vinculados.length === 0
                                ? 'Todavía no tiene consultores.'
                                : plural(
                                      consultores.vinculados.length,
                                      'consultor la acompaña',
                                      'consultores la acompañan',
                                  )
                        }}
                    </p>
                </div>
            </header>

            <ul
                v-if="consultores.vinculados.length > 0"
                class="flex flex-col gap-2 p-4 lg:min-h-0 lg:overflow-y-auto"
            >
                <li
                    v-for="c in consultores.vinculados"
                    :key="c.id"
                    class="flex flex-col gap-3 rounded-xl border border-linea p-3 sm:flex-row sm:items-center"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span
                            class="grid size-10 shrink-0 place-items-center rounded-full bg-marca-suave text-sm font-semibold text-marca"
                            aria-hidden="true"
                        >
                            {{ iniciales(c.nombre) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ c.nombre }}</p>
                            <p class="truncate text-sm text-tinta-suave">
                                {{ c.correo }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-lienzo px-2 py-0.5 text-tinta-suave"
                                >
                                    <Building2
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    {{
                                        plural(
                                            c.empresas,
                                            'empresa',
                                            'empresas',
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="c.vinculado_en"
                                    class="rounded-full bg-lienzo px-2 py-0.5 text-tinta-suave"
                                >
                                    Desde el
                                    {{ diaYMes(fechaLocal(c.vinculado_en)) }}
                                </span>
                                <span
                                    v-if="c.invitacion_pendiente"
                                    class="inline-flex items-center gap-1 rounded-full bg-alerta-suave px-2 py-0.5 text-alerta"
                                >
                                    <MailQuestion
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    Aún no crea su contraseña
                                </span>
                                <span
                                    v-else-if="!c.activo"
                                    class="rounded-full bg-lienzo-oscuro px-2 py-0.5 text-tinta-suave"
                                >
                                    Desactivado
                                </span>
                            </div>
                        </div>
                    </div>
                    <Boton
                        variante="secundario"
                        tamano="sm"
                        class="shrink-0 self-start text-aviso sm:self-center"
                        @click="quitando = c"
                    >
                        <Unlink class="size-3.5" aria-hidden="true" />
                        Quitar
                    </Boton>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center gap-2 px-5 py-10 text-center"
            >
                <span
                    class="flex size-11 items-center justify-center rounded-xl bg-lienzo text-tinta-suave"
                    aria-hidden="true"
                >
                    <UserCheck class="size-5" />
                </span>
                <p class="font-medium">Sin consultores</p>
                <p class="max-w-sm text-sm text-tinta-suave">
                    Vincula un consultor para que pueda ver los resultados de
                    esta empresa.
                </p>
            </div>
        </section>

        <aside class="flex flex-col gap-4">
            <section class="rounded-2xl border border-linea bg-white p-5">
                <h3 class="flex items-center gap-2 text-sm font-semibold">
                    <Link2 class="size-4 text-marca" aria-hidden="true" />
                    Vincular un consultor
                </h3>
                <form
                    v-if="consultores.disponibles.length > 0"
                    class="mt-3 flex flex-col gap-3"
                    @submit.prevent="vincular"
                >
                    <label for="vincular-consultor" class="sr-only">
                        Consultor
                    </label>
                    <Seleccion
                        id="vincular-consultor"
                        v-model="form.consultor_id"
                        :invalida="!!form.errors.consultor_id"
                    >
                        <option value="" disabled>Elige un consultor</option>
                        <option
                            v-for="c in consultores.disponibles"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.nombre }} · {{ c.correo }}
                        </option>
                    </Seleccion>
                    <p
                        v-if="form.errors.consultor_id"
                        class="text-xs text-aviso"
                        role="alert"
                    >
                        {{ form.errors.consultor_id }}
                    </p>
                    <Boton
                        type="submit"
                        :disabled="form.consultor_id === ''"
                        :cargando="form.processing"
                    >
                        <Link2 class="size-4" aria-hidden="true" />
                        Vincular
                    </Boton>
                </form>
                <p v-else class="mt-2 text-sm text-tinta-suave">
                    No hay consultores sin vincular a esta empresa.
                </p>
                <Link
                    :href="rutas.usuarios.lista()"
                    class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-marca hover:text-marca-hover"
                >
                    <UserPlus class="size-4" aria-hidden="true" />
                    Invitar un consultor nuevo
                </Link>
            </section>

            <section
                class="rounded-2xl border border-linea bg-white p-5 text-sm"
            >
                <h3 class="flex items-center gap-2 font-semibold">
                    <Eye class="size-4 text-marca" aria-hidden="true" />
                    Qué ve un consultor
                </h3>
                <ul class="mt-2 flex flex-col gap-1.5 text-tinta-suave">
                    <li v-for="p in puede" :key="p">· {{ p }}</li>
                </ul>
                <p class="mt-3 rounded-xl bg-lienzo px-3 py-2 text-xs">
                    Solo mira: no responde el diagnóstico, no asigna mediciones
                    ni cambia datos. Tampoco ve las empresas que no tiene
                    vinculadas.
                </p>
            </section>
        </aside>
    </div>

    <Modal
        v-model:abierto="modalQuitar"
        titulo="Quitar consultor"
        :icono="Unlink"
        tono="peligro"
        ancho="sm"
    >
        <p v-if="quitando" class="text-sm">
            <strong>{{ quitando.nombre }}</strong> dejará de ver la ficha y los
            resultados de <strong>{{ empresa.nombre }}</strong
            >. Su cuenta sigue activa y puedes volver a vincularlo cuando
            quieras.
        </p>
        <template #pie>
            <Boton variante="secundario" @click="modalQuitar = false">
                Cancelar
            </Boton>
            <Boton variante="peligro" :cargando="procesando" @click="quitar">
                <Unlink class="size-4" aria-hidden="true" />
                Quitar consultor
            </Boton>
        </template>
    </Modal>
</template>
