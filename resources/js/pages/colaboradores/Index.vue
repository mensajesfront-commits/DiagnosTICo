<script setup lang="ts">
/**
 * E12 · Colaboradores (HU-076 a HU-079, RN-025).
 *
 * Solo la cuenta principal de la empresa entra aquí (rol Empresa). Ve a las
 * personas de su empresa: ella misma primero ("Cuenta principal · Tú") y
 * luego los colaboradores, con "Editar" (datos y contraseña, E12.3) y
 * "Desactivar / Eliminar" (doble confirmación con botones) o
 * "Reactivar". "+ Agregar colaborador" abre E12.1; al crear o cambiar una
 * contraseña se abre E12.2 con los datos para compartir.
 *
 * Cada colaborador ve solo las páginas que la cuenta principal le marca
 * (columna "Puede ver") y no cambia su perfil ni su contraseña.
 *
 * Diseño: cada persona es una tarjeta (la cuenta principal primero, con
 * fondo de marca) con su cargo, su estado, lo que puede ver en chips y
 * sus acciones. Filtro Todos / Activos / Desactivados con conteos.
 *
 * Props: empresa (nombre), colaboradores (la cuenta principal primero) y
 * paginas (las que se le pueden dar a un colaborador).
 * Contrato en docs/14_FRONTEND.md.
 */
import { Head, router } from '@inertiajs/vue3';
import {
    Ban,
    ChartColumn,
    CircleCheck,
    ClipboardPen,
    Crown,
    FileDown,
    House,
    LayoutGrid,
    Pencil,
    RotateCcw,
    Trash2,
    UserPlus,
    Users,
    UserX,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import ModalAgregarColaborador from '@/components/colaboradores/ModalAgregarColaborador.vue';
import ModalEditarColaborador from '@/components/colaboradores/ModalEditarColaborador.vue';
import ModalDatosDeAcceso from '@/components/colaboradores/ModalDatosDeAcceso.vue';
import ModalDesactivarEliminarColaborador from '@/components/colaboradores/ModalDesactivarEliminarColaborador.vue';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import { iniciales } from '@/lib/usuarios';
import { cn } from '@/lib/utils';
import type {
    Colaborador,
    DatosDeAcceso,
    PaginaColaborador,
} from '@/types/colaboradores';

const props = defineProps<{
    empresa: string;
    colaboradores: Colaborador[];
    paginas: PaginaColaborador[];
}>();

/** Ícono de cada página; las nuevas que agregue el servidor usan el genérico. */
const iconos: Record<string, Component> = {
    'diagnostico.responder': ClipboardPen,
    'resultados.ver': ChartColumn,
    'resultados.pdf': FileDown,
};

const puedeVer = (colaborador: Colaborador) =>
    props.paginas.filter((p) => colaborador.paginas.includes(p.clave));

type Filtro = 'todos' | 'activos' | 'desactivados';
const filtro = ref<Filtro>('todos');

const otros = computed(() =>
    props.colaboradores.filter((c) => !c.es_principal),
);
const activos = computed(() => otros.value.filter((c) => c.activo).length);
const desactivados = computed(() => otros.value.length - activos.value);

const filtros = computed(() => [
    { clave: 'todos' as const, nombre: 'Todos', cantidad: otros.value.length },
    { clave: 'activos' as const, nombre: 'Activos', cantidad: activos.value },
    {
        clave: 'desactivados' as const,
        nombre: 'Desactivados',
        cantidad: desactivados.value,
    },
]);

/** La cuenta principal siempre se ve; el filtro aplica a los colaboradores. */
const visibles = computed(() =>
    props.colaboradores.filter(
        (c) =>
            c.es_principal ||
            filtro.value === 'todos' ||
            (filtro.value === 'activos') === c.activo,
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Colaboradores', href: '/colaboradores' }],
    },
});

const sinColaboradores = computed(() =>
    props.colaboradores.every((c) => c.es_principal),
);

const modalAgregar = ref(false);
const modalEditar = ref(false);
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

function editar(colaborador: Colaborador): void {
    elegido.value = colaborador;
    modalEditar.value = true;
}

// E12.2 solo si se cambió la contraseña; si no, basta el aviso.
function alGuardar(nuevos: DatosDeAcceso | null): void {
    if (nuevos) {
        mostrarDatos(nuevos, true);
    }
}

const inicioModal = ref<'elegir' | 'eliminar'>('elegir');

function desactivarOEliminar(
    colaborador: Colaborador,
    inicio: 'elegir' | 'eliminar' = 'elegir',
): void {
    elegido.value = colaborador;
    inicioModal.value = inicio;
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

const accion =
    'inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-linea bg-white px-3 text-sm font-medium hover:bg-lienzo focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none disabled:opacity-50';
</script>

<template>
    <Head title="Colaboradores" />

    <div class="flex flex-col gap-5 p-4 sm:p-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex items-start gap-3">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                    aria-hidden="true"
                >
                    <Users class="size-5" />
                </span>
                <div class="max-w-xl">
                    <h1 class="text-2xl font-semibold">Colaboradores</h1>
                    <p class="mt-1 text-sm text-tinta-suave">
                        Personas de {{ empresa }} que pueden entrar con su
                        propia cuenta. Tú creas el usuario y la contraseña y se
                        los compartes.
                    </p>
                </div>
            </div>
            <Boton class="shrink-0 self-start" @click="modalAgregar = true">
                <UserPlus class="size-4" aria-hidden="true" />
                Agregar colaborador
            </Boton>
        </header>

        <section class="grid gap-3 sm:grid-cols-2" aria-label="Qué puede hacer">
            <div
                class="flex items-start gap-3 rounded-2xl border border-linea bg-white p-4"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-exito-suave text-exito"
                    aria-hidden="true"
                >
                    <CircleCheck class="size-4.5" />
                </span>
                <div class="text-sm">
                    <h2 class="font-semibold">Un colaborador puede</h2>
                    <p class="mt-1 text-tinta-suave">
                        Ver las páginas que tú le marques: responder el
                        diagnóstico, ver los resultados y el historial o
                        descargar el informe en PDF.
                    </p>
                </div>
            </div>
            <div
                class="flex items-start gap-3 rounded-2xl border border-linea bg-white p-4"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-aviso-suave text-aviso"
                    aria-hidden="true"
                >
                    <Ban class="size-4.5" />
                </span>
                <div class="text-sm">
                    <h2 class="font-semibold">Un colaborador no puede</h2>
                    <p class="mt-1 text-tinta-suave">
                        Agregar o desactivar colaboradores, cambiar los datos de
                        la empresa ni cambiar su perfil o su contraseña. Eso
                        solo lo hace la cuenta principal.
                    </p>
                </div>
            </div>
        </section>

        <section aria-labelledby="titulo-personas" class="flex flex-col gap-3">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <h2 id="titulo-personas" class="font-semibold">
                    Personas con acceso
                </h2>
                <div
                    v-if="!sinColaboradores"
                    class="flex flex-wrap gap-2"
                    role="group"
                    aria-label="Filtrar colaboradores"
                >
                    <button
                        v-for="f in filtros"
                        :key="f.clave"
                        type="button"
                        :aria-pressed="filtro === f.clave"
                        :class="
                            cn(
                                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                filtro === f.clave
                                    ? 'border-marca bg-marca text-white'
                                    : 'border-linea bg-white hover:bg-lienzo',
                            )
                        "
                        @click="filtro = f.clave"
                    >
                        {{ f.nombre }}
                        <span
                            :class="
                                cn(
                                    'rounded-full px-1.5 text-xs',
                                    filtro === f.clave
                                        ? 'bg-white/20'
                                        : 'bg-lienzo-oscuro',
                                )
                            "
                            >{{ f.cantidad }}</span
                        >
                    </button>
                </div>
            </div>

            <ul class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                <li
                    v-for="colaborador in visibles"
                    :key="colaborador.id"
                    :class="
                        cn(
                            'flex flex-col rounded-2xl border bg-white',
                            colaborador.es_principal
                                ? 'border-marca/30 bg-linear-to-br from-marca-suave/70 to-white'
                                : colaborador.activo
                                  ? 'border-linea'
                                  : 'border-dashed border-linea-fuerte bg-lienzo/50',
                        )
                    "
                >
                    <div class="flex flex-1 flex-col gap-4 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span
                                aria-hidden="true"
                                :class="
                                    cn(
                                        'grid size-11 shrink-0 place-items-center rounded-full text-sm font-semibold',
                                        colaborador.activo
                                            ? colaborador.es_principal
                                                ? 'bg-marca text-white'
                                                : 'bg-marca-suave text-marca'
                                            : 'bg-lienzo-oscuro text-tinta-suave',
                                    )
                                "
                            >
                                {{ iniciales(colaborador.nombre) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3
                                    :class="
                                        cn(
                                            'truncate font-semibold',
                                            !colaborador.activo &&
                                                'text-tinta-suave',
                                        )
                                    "
                                >
                                    {{ colaborador.nombre }}
                                </h3>
                                <p class="truncate text-sm text-tinta-suave">
                                    {{ colaborador.correo }}
                                </p>
                            </div>
                            <span
                                v-if="colaborador.activo"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-exito-suave px-2.5 py-1 text-xs font-medium text-exito"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-exito"
                                    aria-hidden="true"
                                />
                                Activo
                            </span>
                            <span
                                v-else
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-lienzo-oscuro px-2.5 py-1 text-xs font-medium text-tinta-suave"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-nivel-sin"
                                    aria-hidden="true"
                                />
                                Desactivado
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-1.5 text-xs">
                            <span
                                v-if="colaborador.es_principal"
                                class="inline-flex items-center gap-1 rounded-full bg-marca px-2.5 py-1 font-medium text-white"
                            >
                                <Crown class="size-3.5" aria-hidden="true" />
                                Cuenta principal · Tú
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-lienzo-oscuro px-2.5 py-1 font-medium text-tinta"
                            >
                                Colaborador
                            </span>
                            <span
                                v-if="colaborador.cargo"
                                class="rounded-full bg-white px-2.5 py-1 text-tinta-suave ring-1 ring-linea"
                            >
                                Cargo:
                                <span class="font-medium text-tinta">{{
                                    colaborador.cargo
                                }}</span>
                            </span>
                        </div>

                        <div>
                            <p
                                class="mb-1.5 text-xs font-medium text-tinta-suave"
                            >
                                Puede ver
                            </p>
                            <ul class="flex flex-wrap gap-1.5">
                                <li
                                    v-if="colaborador.es_principal"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs ring-1 ring-marca/30"
                                >
                                    <LayoutGrid
                                        class="size-3.5 text-marca"
                                        aria-hidden="true"
                                    />
                                    Todo
                                </li>
                                <template v-else>
                                    <li
                                        v-for="p in puedeVer(colaborador)"
                                        :key="p.clave"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs ring-1 ring-linea"
                                    >
                                        <component
                                            :is="iconos[p.clave] ?? LayoutGrid"
                                            class="size-3.5 text-marca"
                                            aria-hidden="true"
                                        />
                                        {{ p.titulo }}
                                    </li>
                                    <li
                                        v-if="!puedeVer(colaborador).length"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs ring-1 ring-linea"
                                    >
                                        <House
                                            class="size-3.5 text-tinta-suave"
                                            aria-hidden="true"
                                        />
                                        Solo Inicio
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <div
                        :class="
                            cn(
                                'flex flex-wrap items-center gap-2 rounded-b-2xl border-t px-4 py-3 sm:px-5',
                                colaborador.es_principal
                                    ? 'border-marca/20'
                                    : 'border-linea bg-lienzo/40',
                            )
                        "
                    >
                        <p
                            v-if="colaborador.es_principal"
                            class="text-sm text-tinta-suave"
                        >
                            Tus datos se cambian en Mi perfil.
                        </p>
                        <template v-else-if="colaborador.activo">
                            <button
                                type="button"
                                :class="accion"
                                @click="editar(colaborador)"
                            >
                                <Pencil class="size-3.5" aria-hidden="true" />
                                Editar
                            </button>
                            <button
                                type="button"
                                :class="accion"
                                @click="desactivarOEliminar(colaborador)"
                            >
                                <UserX class="size-3.5" aria-hidden="true" />
                                Desactivar o eliminar
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                :class="accion"
                                :disabled="reactivando === colaborador.id"
                                @click="reactivar(colaborador)"
                            >
                                <RotateCcw
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                Reactivar
                            </button>
                            <button
                                type="button"
                                :class="
                                    cn(
                                        accion,
                                        'text-aviso hover:bg-aviso-suave',
                                    )
                                "
                                @click="
                                    desactivarOEliminar(colaborador, 'eliminar')
                                "
                            >
                                <Trash2 class="size-3.5" aria-hidden="true" />
                                Eliminar
                            </button>
                        </template>
                    </div>
                </li>

                <!-- HU-076 CA-004: lista vacía con el botón para crear -->
                <li
                    v-if="sinColaboradores"
                    class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-linea-fuerte bg-white px-5 py-8 text-center"
                >
                    <span
                        class="flex size-11 items-center justify-center rounded-xl bg-marca-suave text-marca"
                        aria-hidden="true"
                    >
                        <UserPlus class="size-5" />
                    </span>
                    <p class="font-semibold">Todavía no tienes colaboradores</p>
                    <p class="max-w-sm text-sm text-tinta-suave">
                        Agrega a las personas de tu equipo que van a responder
                        el diagnóstico contigo. Cada una entra con su propio
                        correo y contraseña.
                    </p>
                    <Boton variante="secundario" @click="modalAgregar = true">
                        <UserPlus class="size-4" aria-hidden="true" />
                        Agregar colaborador
                    </Boton>
                </li>

                <li
                    v-else-if="visibles.length === 1"
                    class="flex items-center justify-center rounded-2xl border border-dashed border-linea-fuerte bg-white px-5 py-8 text-center text-sm text-tinta-suave"
                >
                    {{
                        filtro === 'activos'
                            ? 'No hay colaboradores activos.'
                            : 'No hay colaboradores desactivados.'
                    }}
                </li>
            </ul>
        </section>

        <Recuadro tono="neutro">
            Su usuario es su correo. Desactivar un acceso lo bloquea de
            inmediato; sus respuestas y resultados se conservan.
            <template v-if="!sinColaboradores">
                Hoy tienes
                {{
                    plural(
                        activos,
                        'colaborador activo',
                        'colaboradores activos',
                    )
                }}.
            </template>
        </Recuadro>
    </div>

    <ModalAgregarColaborador
        v-model:abierto="modalAgregar"
        :paginas="paginas"
        @creado="mostrarDatos($event, false)"
    />
    <template v-if="elegido">
        <ModalEditarColaborador
            v-model:abierto="modalEditar"
            :colaborador="elegido"
            :paginas="paginas"
            @guardado="alGuardar"
        />
        <ModalDesactivarEliminarColaborador
            v-model:abierto="modalDesactivar"
            :colaborador="elegido"
            :inicio="inicioModal"
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
