<script setup lang="ts">
/**
 * E12 · Colaboradores (HU-076 a HU-079, RN-025).
 *
 * Solo la cuenta principal de la empresa entra aquí (rol Empresa). Ve a las
 * personas de su empresa: ella misma primero ("Cuenta principal · Tú") y
 * luego los colaboradores, con "Cambiar contraseña" y "Desactivar" o
 * "Reactivar". "+ Agregar colaborador" abre E12.1; al crear o cambiar una
 * contraseña se abre E12.2 con los datos para compartir.
 *
 * Props: empresa (nombre) y colaboradores (la cuenta principal primero).
 * Contrato en docs/14_FRONTEND.md.
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import ModalAgregarColaborador from '@/components/colaboradores/ModalAgregarColaborador.vue';
import ModalCambiarContrasena from '@/components/colaboradores/ModalCambiarContrasena.vue';
import ModalDatosDeAcceso from '@/components/colaboradores/ModalDatosDeAcceso.vue';
import ModalDesactivarColaborador from '@/components/colaboradores/ModalDesactivarColaborador.vue';
import { rutas } from '@/lib/rutas';
import { iniciales } from '@/lib/usuarios';
import { cn } from '@/lib/utils';
import type { Colaborador, DatosDeAcceso } from '@/types/colaboradores';

const props = defineProps<{
    empresa: string;
    colaboradores: Colaborador[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Colaboradores', href: '/colaboradores' }],
    },
});

const sinColaboradores = computed(() =>
    props.colaboradores.every((c) => c.es_principal),
);

const modalAgregar = ref(false);
const modalContrasena = ref(false);
const modalDesactivar = ref(false);
const modalDatos = ref(false);

const elegido = ref<Colaborador | null>(null);
const datos = ref<DatosDeAcceso | null>(null);
const esCambio = ref(false);

function mostrarDatos(nuevos: DatosDeAcceso, cambio: boolean): void {
    datos.value = nuevos;
    esCambio.value = cambio;
    modalDatos.value = true;
}

// La contraseña solo vive mientras E12.2 está abierto.
function alCerrarDatos(abierto: boolean): void {
    if (!abierto) {
        datos.value = null;
    }
}

function cambiarContrasena(colaborador: Colaborador): void {
    elegido.value = colaborador;
    modalContrasena.value = true;
}

function desactivar(colaborador: Colaborador): void {
    elegido.value = colaborador;
    modalDesactivar.value = true;
}

const reactivando = ref<number | null>(null);

function reactivar(colaborador: Colaborador): void {
    router.post(
        rutas.colaboradores.reactivar(colaborador.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (reactivando.value = colaborador.id),
            onFinish: () => (reactivando.value = null),
        },
    );
}

const enlace =
    'text-sm text-marca underline underline-offset-2 hover:text-marca-hover disabled:opacity-50';
</script>

<template>
    <Head title="Colaboradores" />

    <div class="flex flex-col gap-5 p-6">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h1 class="text-2xl font-semibold">Colaboradores</h1>
                <p class="mt-1 text-sm text-tinta-suave">
                    Personas de {{ empresa }} que pueden entrar con su propia
                    cuenta. Tú creas el usuario y la contraseña y se los
                    compartes.
                </p>
            </div>
            <Boton @click="modalAgregar = true">+ Agregar colaborador</Boton>
        </header>

        <section
            class="grid gap-5 rounded-xl border border-linea bg-white p-5 text-sm sm:grid-cols-2"
        >
            <div>
                <h2 class="font-semibold">Un colaborador puede</h2>
                <p class="mt-1 text-tinta-suave">
                    Responder y enviar diagnósticos, ver el historial y los
                    resultados, descargar el informe en PDF y cambiar su propia
                    contraseña.
                </p>
            </div>
            <div>
                <h2 class="font-semibold">Un colaborador no puede</h2>
                <p class="mt-1 text-tinta-suave">
                    Agregar o desactivar colaboradores ni cambiar los datos de
                    la empresa. Eso solo lo hace la cuenta principal.
                </p>
            </div>
        </section>

        <section class="rounded-xl border border-linea bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-linea text-left">
                            <th
                                v-for="columna in [
                                    'Persona',
                                    'Cargo',
                                    'Rol',
                                    'Estado',
                                ]"
                                :key="columna"
                                scope="col"
                                class="px-5 py-3 text-xs font-normal text-tinta-suave"
                            >
                                {{ columna }}
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-right text-xs font-normal text-tinta-suave"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="colaborador in colaboradores"
                            :key="colaborador.id"
                            class="border-b border-linea last:border-b-0"
                        >
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span
                                        aria-hidden="true"
                                        class="grid size-9 shrink-0 place-items-center rounded-full bg-marca-suave text-xs font-semibold text-marca"
                                    >
                                        {{ iniciales(colaborador.nombre) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p
                                            :class="
                                                cn(
                                                    'font-medium',
                                                    !colaborador.activo &&
                                                        'font-normal text-tinta-suave',
                                                )
                                            "
                                        >
                                            {{ colaborador.nombre }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-tinta-suave"
                                        >
                                            {{ colaborador.correo }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                {{ colaborador.cargo ?? '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                {{
                                    colaborador.es_principal
                                        ? 'Cuenta principal · Tú'
                                        : 'Colaborador'
                                }}
                            </td>
                            <td class="px-5 py-3">
                                <Etiqueta
                                    v-if="colaborador.activo"
                                    tono="exito"
                                >
                                    ✓ Activo
                                </Etiqueta>
                                <Etiqueta v-else>○ Desactivado</Etiqueta>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <span
                                    v-if="colaborador.es_principal"
                                    class="text-tinta-suave"
                                >
                                    Cuenta de la empresa
                                </span>
                                <span
                                    v-else-if="colaborador.activo"
                                    class="inline-flex items-center gap-x-1.5"
                                >
                                    <button
                                        type="button"
                                        :class="enlace"
                                        @click="cambiarContrasena(colaborador)"
                                    >
                                        Cambiar contraseña
                                    </button>
                                    <span class="text-tinta-suave">·</span>
                                    <button
                                        type="button"
                                        :class="enlace"
                                        @click="desactivar(colaborador)"
                                    >
                                        Desactivar
                                    </button>
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    :class="enlace"
                                    :disabled="reactivando === colaborador.id"
                                    @click="reactivar(colaborador)"
                                >
                                    Reactivar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- HU-076 CA-004: lista vacía con el botón para crear -->
            <div
                v-if="sinColaboradores"
                class="flex flex-col items-center gap-3 border-t border-linea px-5 py-10 text-center"
            >
                <p class="font-medium">Todavía no tienes colaboradores</p>
                <p class="max-w-md text-sm text-tinta-suave">
                    Agrega a las personas de tu equipo que van a responder el
                    diagnóstico contigo. Cada una entra con su propio correo y
                    contraseña.
                </p>
                <Boton variante="secundario" @click="modalAgregar = true">
                    + Agregar colaborador
                </Boton>
            </div>
        </section>

        <p class="text-xs text-tinta-suave">
            Su usuario es su correo. Desactivar un acceso lo bloquea de
            inmediato; sus respuestas y resultados se conservan.
        </p>
    </div>

    <ModalAgregarColaborador
        v-model:abierto="modalAgregar"
        @creado="mostrarDatos($event, false)"
    />
    <template v-if="elegido">
        <ModalCambiarContrasena
            v-model:abierto="modalContrasena"
            :colaborador="elegido"
            @cambiada="mostrarDatos($event, true)"
        />
        <ModalDesactivarColaborador
            v-model:abierto="modalDesactivar"
            :colaborador="elegido"
        />
    </template>
    <ModalDatosDeAcceso
        v-if="datos"
        v-model:abierto="modalDatos"
        :datos="datos"
        :cambio="esCambio"
        @update:abierto="alCerrarDatos"
    />
</template>
