<script setup lang="ts">
/**
 * A3.1 · Ficha de la empresa (HU-033) y A3.1d · Datos de la cuenta (HU-034).
 *
 * Encabezado: iniciales, nombre, sector, "Cuenta activa desde…" y las pestañas
 * Historial, Datos de la cuenta y Consultores (HU-085: solo quien gestiona
 * usuarios; vincula o quita consultores, PanelConsultores).
 *
 * Historial:
 * - Último puntaje con su nivel, variación frente a la anterior (RN-020; no
 *   hay en la primera) y la medición pendiente.
 * - Gráfica de la evolución con las franjas de nivel.
 * - Tabla de mediciones, paginada con las filas que caben. Incluye, por
 *   fecha, las vencidas que se cancelaron o reemplazaron (`vencidas`): cuándo
 *   venció y se canceló, cuántas preguntas respondió y, al pasar el cursor,
 *   el último aviso por correo y quién la asignó y la canceló (10 de
 *   noviembre de 2026).
 * - A la derecha, la medición pendiente con "Cambiar fecha" y "Cancelar
 *   medición" (A3.1c), y "Asignar otra medición": el formulario corto; si ya
 *   hay una pendiente, pide confirmar el reemplazo (A3.1c). "Abrir el
 *   formulario completo" lleva a A3.1b.
 *
 * Datos de la cuenta: datos de la empresa y de su usuario principal, el
 * estado de la cuenta con sus colaboradores en solo lectura (HU-033 CA-003)
 * y las acciones "Ver como esta empresa", "Asignar medición" y "Desactivar
 * cuenta" (A3.1f) o "Reactivar cuenta". Una empresa desactivada se puede
 * eliminar (solo con usuarios.gestionar, ModalEliminarEmpresa): se recupera
 * 90 días desde A5. No existe "restablecer contraseña"
 * (RN-006). Si la empresa se quedó sin cuenta principal (pasó al equipo de
 * NuevasTIC), "Dar una cuenta principal" abre ModalCuentaPrincipal; sin ella
 * no se reactiva.
 *
 * La pendiente también tiene "Enviar recordatorio" o "Reenviar aviso" con su
 * último aviso (A3.1e, HU-008).
 *
 * "Ver resultado" abre A3.3 (HU-038) y "Ver historial completo", A3.4
 * (HU-039).
 */
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarPlus,
    ChartLine,
    ClipboardList,
    FileDown,
    Store,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import CamposEmpresa from '@/components/empresas/CamposEmpresa.vue';
import DatosEmpresaLectura from '@/components/empresas/DatosEmpresaLectura.vue';
import type { FormularioEmpresa } from '@/components/empresas/CamposEmpresa.vue';
import ModalCambiarFecha from '@/components/empresas/ModalCambiarFecha.vue';
import ModalCancelarMedicion from '@/components/empresas/ModalCancelarMedicion.vue';
import ModalCorreoAviso from '@/components/empresas/ModalCorreoAviso.vue';
import ModalCuentaPrincipal from '@/components/empresas/ModalCuentaPrincipal.vue';
import ModalDesactivarEmpresa from '@/components/empresas/ModalDesactivarEmpresa.vue';
import ModalEliminarEmpresa from '@/components/empresas/ModalEliminarEmpresa.vue';
import ModalReemplazarMedicion from '@/components/empresas/ModalReemplazarMedicion.vue';
import PanelConsultores from '@/components/empresas/PanelConsultores.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import EvolucionPuntaje from '@/components/graficas/EvolucionPuntaje.vue';
import SelectorEvolucion from '@/components/graficas/SelectorEvolucion.vue';
import {
    diaYMes,
    fechaLocal,
    haceCuanto,
    mesYAnio,
    momento,
} from '@/lib/fechas';
import { usePermisos } from '@/composables/usePermisos';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { FichaEmpresa, MedicionFicha } from '@/types/empresas';
import type { MedicionVencida } from '@/types/historial';

const props = withDefaults(
    defineProps<
        FichaEmpresa & {
            /** Vencidas que se cancelaron o reemplazaron (DatosHistorial). */
            vencidas?: MedicionVencida[];
        }
    >(),
    { vencidas: () => [] },
);

const { puede } = usePermisos();

/** El consultor vuelve a "Mis empresas" (C2): no tiene la lista A3. */
const esConsultor = computed(() => usePage().props.auth.rol === 'Consultor');

/**
 * La columna de la derecha (medición pendiente y Asignar) solo si tiene algo:
 * sin ella, el historial ocupa todo el ancho (el consultor sin pendiente).
 */
const columnaLateral = computed(
    () =>
        puede('mediciones.asignar') ||
        (props.pendiente !== null && !esConsultor.value),
);

// "Ver:" de la gráfica: el puntaje total o el de una categoría.
const verCategoria = ref('');
const puntosEvolucion = computed(
    () =>
        props.evolucion_categorias.find((c) => c.nombre === verCategoria.value)
            ?.puntos ?? props.evolucion,
);

type Pestana = 'historial' | 'cuenta' | 'consultores';

// Sale de la URL de Inertia y no de `window`: con SSR se arma en Node.
const pestanaUrl = new URLSearchParams(usePage().url.split('?')[1] ?? '').get(
    'pestana',
);
const pestana = ref<Pestana>(
    pestanaUrl === 'cuenta' ||
        (pestanaUrl === 'consultores' && props.consultores !== null)
        ? (pestanaUrl as Pestana)
        : 'historial',
);

const pestanas = computed(() => [
    { valor: 'historial' as const, etiqueta: 'Historial' },
    {
        valor: 'cuenta' as const,
        etiqueta: puede('empresas.registrar')
            ? 'Datos de la cuenta'
            : 'Datos de la empresa',
    },
    ...(props.consultores !== null
        ? [{ valor: 'consultores' as const, etiqueta: 'Consultores' }]
        : []),
]);

const fecha = (texto: string | null) =>
    texto ? diaYMes(fechaLocal(texto)) : '—';

const activaDesde = computed(() =>
    props.empresa.registrada_en ? mesYAnio(props.empresa.registrada_en) : null,
);

/** "Panadería El Trigal" → "PE", para el círculo del encabezado. */
const iniciales = computed(
    () =>
        props.empresa.nombre
            .split(/\s+/)
            .filter((p) => p.length > 2 || /^[A-ZÁÉÍÓÚÑ]/.test(p))
            .slice(0, 2)
            .map((p) => p.charAt(0).toUpperCase())
            .join('') || props.empresa.nombre.charAt(0).toUpperCase(),
);

const terminadas = computed(
    () => props.mediciones.filter((m) => m.estado === 'terminada').length,
);

const colaboradoresActivos = computed(
    () => props.colaboradores.filter((c) => c.activo).length,
);

// --- Tabla de mediciones -------------------------------------------------------
const contenedor = ref<HTMLElement | null>(null);
const { porPagina, medir, ajustar } = useFilasQueCaben(contenedor, {
    movil: 6,
    minimo: 2,
});
/** Mediciones y vencidas canceladas juntas, de la más reciente a la primera. */
type FilaTabla =
    | { tipo: 'medicion'; clave: string; fecha: string; m: MedicionFicha }
    | { tipo: 'vencida'; clave: string; fecha: string; v: MedicionVencida };

const filas = computed<FilaTabla[]>(() =>
    [
        ...props.mediciones.map((m): FilaTabla => ({
            tipo: 'medicion',
            clave: `m${m.id}`,
            fecha: m.terminada ?? m.asignada ?? '',
            m,
        })),
        ...props.vencidas.map((v): FilaTabla => ({
            tipo: 'vencida',
            clave: `v${v.id}`,
            fecha: v.cancelada ?? v.vence ?? '',
            v,
        })),
    ].sort((a, b) => b.fecha.localeCompare(a.fecha)),
);

/** Lo que dice la fila de una vencida al pasar el cursor. */
const detalleVencida = (v: MedicionVencida) =>
    [
        `Asignada el ${fecha(v.asignada)}${v.asignada_por ? ` por ${v.asignada_por}` : ''}`,
        `venció el ${fecha(v.vence)}`,
        `cancelada el ${fecha(v.cancelada)}${v.cancelada_por ? ` por ${v.cancelada_por}` : ''}`,
        v.ultimo_aviso
            ? `aviso por correo el ${fecha(v.ultimo_aviso)}`
            : 'sin aviso por correo',
    ].join(' · ');

const { pagina, paginas, total, desde, visibles } = usePaginacion(
    () => filas.value,
    porPagina,
);

watch(pestana, (p) => p === 'historial' && medir());
watch(visibles, ajustar);

// --- Modales -------------------------------------------------------------------
const modalFecha = ref(false);
const modalCancelar = ref(false);
const modalDesactivar = ref(false);
const modalEliminar = ref(false);
const modalReemplazar = ref(false);
const modalCorreo = ref(false);

function reactivar(): void {
    router.post(
        rutas.empresas.reactivar(props.empresa.id),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

function verComo(): void {
    if (props.principal) {
        router.post(rutas.usuarios.verComo(props.principal.id));
    }
}

// --- Asignar otra medición (formulario corto de A3.1b) -----------------------
/** Fecha límite más cercana: 3 días, en la hora de la empresa (servidor). */
const hoy = computed(() => props.fecha_minima);

const asignacion = useForm({
    empresa_id: props.empresa.id,
    diagnostico_id: (props.diagnosticos.length === 1
        ? props.diagnosticos[0].id
        : '') as number | '',
    fecha_limite: '',
    mensaje: '',
    aviso: false,
    reemplazar: false,
});

const diagnosticoElegido = computed(
    () =>
        props.diagnosticos.find((d) => d.id === asignacion.diagnostico_id) ??
        null,
);

function asignar(reemplazar = false): void {
    if (props.pendiente && !reemplazar) {
        modalReemplazar.value = true;

        return;
    }

    asignacion.reemplazar = reemplazar;
    asignacion
        .transform((d) => ({
            ...d,
            fecha_limite: d.fecha_limite || null,
            mensaje: d.mensaje.trim() || null,
        }))
        .post(rutas.mediciones.guardar(), {
            preserveScroll: true,
            onSuccess: () => {
                modalReemplazar.value = false;
                asignacion.reset('fecha_limite', 'mensaje', 'aviso');
            },
        });
}

// --- Datos de la cuenta (A3.1d) ----------------------------------------------
const datosIniciales = (): FormularioEmpresa => ({
    nombre: props.empresa.nombre,
    sector_id: props.empresa.sector_id,
    actividad_economica_id: props.empresa.actividad_economica_id ?? '',
    descripcion: props.empresa.descripcion ?? '',
    pais: props.empresa.pais,
    departamento: props.empresa.departamento ?? '',
    ciudad: props.empresa.ciudad,
    telefono: props.empresa.telefono ?? '',
    sitio_web: props.empresa.sitio_web ?? '',
    numero_empleados: props.empresa.numero_empleados ?? '',
    usuario_nombre: props.principal?.name ?? '',
    cargo: props.principal?.cargo ?? '',
    email: props.principal?.email ?? '',
    usuario_telefono: props.principal?.telefono ?? '',
});

const form = useForm(datosIniciales());

watch(
    () => [props.empresa, props.principal],
    () => {
        form.defaults(datosIniciales());
        form.reset();
    },
);

function guardar(): void {
    form.transform((d) => ({
        ...d,
        actividad_economica_id: d.actividad_economica_id || null,
    })).put(rutas.empresas.actualizar(props.empresa.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

const verColaboradores = ref(false);
const modalCuenta = ref(false);

const enlace = 'text-marca underline underline-offset-2 hover:text-marca-hover';
</script>

<template>
    <Head :title="empresa.nombre" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="flex shrink-0 flex-wrap items-end justify-between gap-4">
            <div>
                <Link
                    :href="rutas.empresas.lista()"
                    class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{ esConsultor ? 'Mis empresas' : 'Empresas' }}
                </Link>
                <div class="flex items-center gap-4">
                    <span
                        :class="
                            cn(
                                'flex size-14 shrink-0 items-center justify-center rounded-2xl text-lg font-semibold',
                                empresa.activa
                                    ? 'bg-marca text-white'
                                    : 'bg-lienzo-oscuro text-tinta-suave',
                            )
                        "
                        aria-hidden="true"
                        >{{ iniciales }}</span
                    >
                    <div class="min-w-0">
                        <h1 class="text-2xl leading-tight font-semibold">
                            {{ empresa.nombre }}
                        </h1>
                        <p
                            class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-tinta-suave"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-0.5 ring-1 ring-linea"
                            >
                                <Store class="size-3.5" aria-hidden="true" />
                                {{ empresa.sector ?? 'Sin sector' }}
                            </span>
                            <span
                                v-if="!empresa.activa"
                                class="inline-flex items-center gap-1.5 font-medium text-aviso"
                            >
                                <span
                                    class="size-2 rounded-full bg-aviso"
                                    aria-hidden="true"
                                />
                                Cuenta desactivada
                            </span>
                            <span
                                v-else-if="activaDesde"
                                class="inline-flex items-center gap-1.5"
                            >
                                <span
                                    class="size-2 rounded-full bg-nivel-sigue"
                                    aria-hidden="true"
                                />
                                Cuenta activa desde {{ activaDesde }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-1">
                <SelectorCompacto
                    v-model="pestana"
                    :opciones="pestanas"
                    etiqueta-accesible="Secciones de la ficha"
                />
            </div>
        </header>

        <!-- Historial (A3.1) -->
        <div
            v-if="pestana === 'historial'"
            :class="
                cn(
                    'grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-rows-[minmax(0,1fr)] lg:items-stretch',
                    columnaLateral && 'lg:grid-cols-[1fr_300px]',
                )
            "
        >
            <div class="flex flex-col gap-4 lg:min-h-0">
                <div class="grid shrink-0 gap-3 sm:grid-cols-3">
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white p-4"
                    >
                        <AnilloPuntaje
                            v-if="ultimo"
                            :puntaje="ultimo.puntaje"
                            :nivel="ultimo.nivel"
                            :tamano="60"
                        />
                        <span
                            v-else
                            class="flex size-14 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-linea-fuerte text-tinta-suave"
                            aria-hidden="true"
                            >–</span
                        >
                        <dl class="min-w-0">
                            <dt class="text-xs font-medium text-tinta-suave">
                                Último puntaje
                            </dt>
                            <dd class="mt-1">
                                <EtiquetaNivel
                                    :nivel="ultimo?.nivel ?? 'sin'"
                                />
                            </dd>
                            <dd
                                v-if="ultimo"
                                class="mt-1 text-xs text-tinta-suave"
                            >
                                Medición {{ ultimo.numero }}
                            </dd>
                        </dl>
                    </div>
                    <dl class="rounded-2xl border border-linea bg-white p-4">
                        <dt
                            class="flex items-center gap-1.5 text-xs font-medium text-tinta-suave"
                        >
                            <TrendingUp class="size-4" aria-hidden="true" />
                            Variación vs. medición anterior
                        </dt>
                        <dd
                            v-if="variacion !== null"
                            :class="
                                cn(
                                    'mt-1 font-mono text-3xl font-semibold',
                                    variacion > 0
                                        ? 'text-nivel-sigue-texto'
                                        : variacion < 0
                                          ? 'text-nivel-critico-texto'
                                          : 'text-tinta',
                                )
                            "
                        >
                            {{ variacion > 0 ? '+' : '' }}{{ variacion }}
                            <span
                                class="font-sans text-sm font-normal text-tinta-suave"
                                >pts</span
                            >
                        </dd>
                        <dd v-else class="mt-1 text-sm text-tinta-suave">
                            Aparece desde la 2.ª medición.
                        </dd>
                    </dl>
                    <div
                        class="flex items-center gap-3 rounded-2xl border border-linea bg-white p-4"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-11 shrink-0 items-center justify-center rounded-xl',
                                    pendiente?.estado === 'vencida'
                                        ? 'bg-nivel-critico-suave text-nivel-critico-texto'
                                        : pendiente
                                          ? 'bg-marca-suave text-marca'
                                          : 'bg-lienzo-oscuro text-tinta-suave',
                                )
                            "
                            aria-hidden="true"
                        >
                            <ClipboardList class="size-5" />
                        </span>
                        <dl class="min-w-0">
                            <dt class="text-xs font-medium text-tinta-suave">
                                {{
                                    pendiente
                                        ? `Medición ${pendiente.numero} pendiente`
                                        : 'Medición pendiente'
                                }}
                            </dt>
                            <dd
                                v-if="pendiente"
                                class="mt-1 flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                            >
                                <EtiquetaEstado
                                    :estado="pendiente.estado"
                                    :avance="pendiente.avance ?? undefined"
                                />
                                {{
                                    pendiente.vence
                                        ? `vence ${fecha(pendiente.vence)}`
                                        : 'sin fecha límite'
                                }}
                            </dd>
                            <dd v-else class="mt-1 text-sm text-tinta-suave">
                                Ninguna · {{ terminadas }}
                                {{
                                    terminadas === 1
                                        ? 'terminada'
                                        : 'terminadas'
                                }}
                            </dd>
                        </dl>
                    </div>
                </div>

                <section
                    class="flex shrink-0 flex-col rounded-2xl border border-linea bg-white px-5 py-4"
                >
                    <!-- "Ver:" va en su propia fila, a la derecha: así el
                         título largo de una categoría no mueve nada. -->
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="min-w-0 truncate text-base font-semibold">
                            {{
                                verCategoria
                                    ? `Evolución de ${verCategoria}`
                                    : 'Evolución del puntaje total'
                            }}
                        </h2>
                        <Link
                            v-if="puede('resultados.ver')"
                            :href="rutas.historial.empresa(empresa.id)"
                            :class="cn(enlace, 'shrink-0 text-xs')"
                        >
                            Ver historial completo
                        </Link>
                    </div>
                    <SelectorEvolucion
                        v-model="verCategoria"
                        class="mt-1.5 self-end"
                        :categorias="evolucion_categorias"
                    />
                    <EvolucionPuntaje
                        v-if="evolucion.length > 0"
                        :key="verCategoria"
                        class="mt-2 h-44"
                        :puntos="puntosEvolucion"
                    />
                    <p
                        v-else
                        class="mt-2 flex h-24 items-center justify-center gap-2 rounded-xl bg-lienzo text-sm text-tinta-suave"
                    >
                        <ChartLine
                            class="size-5 text-marca/60"
                            aria-hidden="true"
                        />
                        La gráfica aparece con el primer resultado.
                    </p>
                </section>

                <section
                    class="flex flex-col rounded-2xl border border-linea bg-white lg:min-h-0 lg:flex-1"
                >
                    <p
                        v-if="filas.length === 0"
                        class="flex flex-1 items-center justify-center px-5 py-8 text-center text-sm text-tinta-suave"
                    >
                        Todavía no tiene mediciones.
                    </p>
                    <template v-else>
                        <div
                            ref="contenedor"
                            class="min-h-0 flex-1 overflow-x-auto overflow-y-hidden"
                        >
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-linea bg-lienzo/60 text-left"
                                    >
                                        <th
                                            v-for="c in [
                                                'Medición',
                                                'Fecha',
                                                'Versión',
                                                'Puntaje y nivel',
                                                'Variación',
                                                '',
                                            ]"
                                            :key="c"
                                            scope="col"
                                            class="px-4 py-2.5 text-xs font-medium text-tinta-suave"
                                        >
                                            {{ c }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template
                                        v-for="fila in visibles"
                                        :key="fila.clave"
                                    >
                                        <!-- Vencida y cancelada: prueba de que se asignó -->
                                        <tr
                                            v-if="fila.tipo === 'vencida'"
                                            class="border-b border-linea last:border-b-0"
                                            :title="detalleVencida(fila.v)"
                                        >
                                            <td
                                                class="px-4 py-2.5 font-semibold whitespace-nowrap"
                                            >
                                                Medición {{ fila.v.numero }}
                                            </td>
                                            <td
                                                class="px-4 py-2.5 whitespace-nowrap"
                                            >
                                                Venció {{ fecha(fila.v.vence) }}
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-tinta-suave"
                                                :title="
                                                    fila.v.diagnostico ??
                                                    undefined
                                                "
                                            >
                                                {{
                                                    fila.v.version
                                                        ? `v${fila.v.version}`
                                                        : '—'
                                                }}
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span
                                                    class="flex items-center gap-2.5"
                                                >
                                                    <span
                                                        class="flex size-9 items-center justify-center rounded-full border-2 border-dashed border-linea-fuerte text-tinta-suave"
                                                        aria-hidden="true"
                                                        >–</span
                                                    >
                                                    <span
                                                        class="rounded-full bg-aviso-suave px-2 py-0.5 text-xs font-medium whitespace-nowrap text-aviso"
                                                        >Vencida · cancelada
                                                        {{
                                                            fecha(
                                                                fila.v
                                                                    .cancelada,
                                                            )
                                                        }}</span
                                                    >
                                                </span>
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-tinta-suave"
                                            >
                                                —
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-right text-xs whitespace-nowrap text-tinta-suave"
                                            >
                                                Respondió
                                                {{ fila.v.respondidas }} de
                                                {{ fila.v.preguntas }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-else
                                            class="border-b border-linea last:border-b-0"
                                        >
                                            <td
                                                class="px-4 py-2.5 font-semibold whitespace-nowrap"
                                            >
                                                Medición {{ fila.m.numero }}
                                            </td>
                                            <td
                                                class="px-4 py-2.5 whitespace-nowrap"
                                            >
                                                {{
                                                    fila.m.terminada
                                                        ? fecha(
                                                              fila.m.terminada,
                                                          )
                                                        : `Asignada ${fecha(fila.m.asignada)}`
                                                }}
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-tinta-suave"
                                                :title="
                                                    fila.m.diagnostico ??
                                                    undefined
                                                "
                                            >
                                                {{
                                                    fila.m.version
                                                        ? `v${fila.m.version}`
                                                        : '—'
                                                }}
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span
                                                    class="flex items-center gap-2.5"
                                                >
                                                    <AnilloPuntaje
                                                        v-if="
                                                            fila.m.puntaje !==
                                                                null &&
                                                            fila.m.nivel
                                                        "
                                                        :puntaje="
                                                            fila.m.puntaje
                                                        "
                                                        :nivel="fila.m.nivel"
                                                        :tamano="36"
                                                    />
                                                    <span
                                                        v-else
                                                        class="flex size-9 items-center justify-center rounded-full border-2 border-dashed border-linea-fuerte text-tinta-suave"
                                                        aria-hidden="true"
                                                        >–</span
                                                    >
                                                    <EtiquetaNivel
                                                        v-if="fila.m.nivel"
                                                        :nivel="fila.m.nivel"
                                                    />
                                                    <EtiquetaEstado
                                                        v-else
                                                        :estado="fila.m.estado"
                                                    />
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span
                                                    v-if="
                                                        fila.m.variacion !==
                                                        null
                                                    "
                                                    :class="
                                                        cn(
                                                            'rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                                                            fila.m.variacion > 0
                                                                ? 'bg-nivel-sigue-suave text-nivel-sigue-texto'
                                                                : fila.m
                                                                        .variacion <
                                                                    0
                                                                  ? 'bg-nivel-critico-suave text-nivel-critico-texto'
                                                                  : 'bg-lienzo-oscuro text-tinta-suave',
                                                        )
                                                    "
                                                    >{{
                                                        fila.m.variacion > 0
                                                            ? `▲ +${fila.m.variacion}`
                                                            : fila.m.variacion <
                                                                0
                                                              ? `▼ ${fila.m.variacion}`
                                                              : '= 0'
                                                    }}</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-tinta-suave"
                                                    >—</span
                                                >
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-right whitespace-nowrap"
                                            >
                                                <button
                                                    v-if="
                                                        pendiente &&
                                                        fila.m.id ===
                                                            pendiente.id &&
                                                        fila.m.estado !==
                                                            'enviada' &&
                                                        puede(
                                                            'mediciones.asignar',
                                                        )
                                                    "
                                                    type="button"
                                                    :class="enlace"
                                                    @click="modalFecha = true"
                                                >
                                                    Cambiar fecha
                                                </button>
                                                <Link
                                                    v-else-if="
                                                        fila.m.estado ===
                                                            'terminada' &&
                                                        puede('resultados.ver')
                                                    "
                                                    :href="
                                                        rutas.resultados.ver(
                                                            fila.m.id,
                                                        )
                                                    "
                                                    :class="enlace"
                                                >
                                                    Ver resultado
                                                </Link>
                                                <a
                                                    v-if="
                                                        fila.m.estado ===
                                                            'terminada' &&
                                                        puede('resultados.pdf')
                                                    "
                                                    :href="
                                                        rutas.resultados.pdf(
                                                            fila.m.id,
                                                        )
                                                    "
                                                    class="ml-3 inline-flex size-7 items-center justify-center rounded-md align-middle text-tinta-suave hover:bg-lienzo hover:text-marca"
                                                    :aria-label="`Descargar el informe completo de la medición ${fila.m.numero}`"
                                                    title="Descargar informe completo (PDF)"
                                                >
                                                    <FileDown
                                                        class="size-4"
                                                        aria-hidden="true"
                                                    />
                                                </a>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <Paginacion
                            v-model:pagina="pagina"
                            class="shrink-0"
                            :desde="desde"
                            :cantidad="visibles.length"
                            :total="total"
                            :paginas="paginas"
                        >
                            <span class="text-xs text-tinta-suave">
                                La empresa ve este mismo historial en "Mi
                                historial".
                            </span>
                        </Paginacion>
                    </template>
                </section>
            </div>

            <div
                v-if="columnaLateral"
                class="flex flex-col gap-4 lg:min-h-0 lg:overflow-y-auto"
            >
                <section
                    v-if="pendiente"
                    class="shrink-0 rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h2
                            class="flex items-center gap-2 text-base font-semibold"
                        >
                            <ClipboardList
                                class="size-5 text-marca"
                                aria-hidden="true"
                            />
                            Medición {{ pendiente.numero }}
                        </h2>
                        <EtiquetaEstado
                            :estado="pendiente.estado"
                            :avance="pendiente.avance ?? undefined"
                        />
                    </div>
                    <p class="mt-2 text-xs text-tinta-suave">
                        {{ pendiente.diagnostico }} · v{{ pendiente.version }}
                    </p>
                    <p class="text-xs text-tinta-suave">
                        Asignada el {{ fecha(pendiente.asignada) }} ·
                        <span
                            :class="
                                pendiente.estado === 'vencida' &&
                                'font-medium text-aviso'
                            "
                        >
                            {{
                                pendiente.vence
                                    ? `vence ${fecha(pendiente.vence)}`
                                    : 'sin fecha límite'
                            }}
                        </span>
                    </p>
                    <p
                        v-if="pendiente.solicitud"
                        class="mt-3 rounded-md bg-aviso-suave px-3 py-2 text-xs text-aviso"
                        role="status"
                    >
                        <strong
                            >Pidió una medición nueva
                            {{ haceCuanto(pendiente.solicitud) }}.</strong
                        >
                        Asígnale otra abajo o dale una nueva fecha.
                    </p>
                    <p
                        v-if="pendiente.estado === 'enviada'"
                        class="mt-3 text-xs text-tinta-suave"
                    >
                        La IA está analizando sus respuestas.
                    </p>
                    <div
                        v-else-if="puede('mediciones.asignar')"
                        class="mt-3 grid grid-cols-2 gap-2"
                    >
                        <Boton
                            variante="secundario"
                            class="px-2"
                            @click="modalFecha = true"
                        >
                            Cambiar fecha
                        </Boton>
                        <Boton
                            variante="secundario"
                            class="px-2"
                            @click="modalCancelar = true"
                        >
                            Cancelar medición
                        </Boton>
                    </div>
                    <p
                        v-if="pendiente.estado !== 'enviada'"
                        class="mt-3 flex flex-wrap items-center justify-between gap-x-2 text-xs text-tinta-suave"
                    >
                        <span>
                            {{
                                pendiente.ultimo_aviso
                                    ? `Último aviso: ${haceCuanto(pendiente.ultimo_aviso)}`
                                    : 'Sin aviso por correo'
                            }}
                        </span>
                        <button
                            v-if="puede('mediciones.asignar')"
                            type="button"
                            :class="enlace"
                            @click="modalCorreo = true"
                        >
                            {{
                                pendiente.estado === 'en_curso'
                                    ? 'Enviar recordatorio'
                                    : 'Reenviar aviso'
                            }}
                        </button>
                    </p>
                </section>

                <section
                    v-if="puede('mediciones.asignar')"
                    class="shrink-0 rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <h2 class="flex items-center gap-2 text-base font-semibold">
                        <CalendarPlus
                            class="size-5 text-marca"
                            aria-hidden="true"
                        />
                        {{
                            pendiente
                                ? 'Asignar otra medición'
                                : 'Asignar medición'
                        }}
                    </h2>
                    <p
                        v-if="!empresa.activa"
                        class="mt-2 text-xs text-tinta-suave"
                    >
                        La cuenta está desactivada. Reactívala en Datos de la
                        cuenta para asignarle mediciones.
                    </p>
                    <form
                        v-else
                        class="mt-1 flex flex-col gap-3"
                        novalidate
                        @submit.prevent="asignar()"
                    >
                        <p v-if="pendiente" class="text-xs text-tinta-suave">
                            Solo puede haber una medición pendiente por empresa:
                            la nueva reemplaza a la medición
                            {{ pendiente.numero }}.
                        </p>
                        <Campo
                            obligatorio
                            etiqueta="Diagnóstico"
                            para="ficha-diagnostico"
                            :ayuda="
                                diagnosticos.length === 0
                                    ? `El sector ${empresa.sector} no tiene diagnósticos publicados.`
                                    : `Diagnósticos publicados del sector ${empresa.sector}.`
                            "
                            :error="asignacion.errors.diagnostico_id"
                        >
                            <Seleccion
                                id="ficha-diagnostico"
                                v-model="asignacion.diagnostico_id"
                                :disabled="diagnosticos.length === 0"
                            >
                                <option value="" disabled>
                                    Selecciona el diagnóstico
                                </option>
                                <option
                                    v-for="d in diagnosticos"
                                    :key="d.id"
                                    :value="d.id"
                                >
                                    {{ d.nombre }} · v{{ d.version }}
                                </option>
                            </Seleccion>
                        </Campo>
                        <Campo
                            etiqueta="Fecha límite"
                            para="ficha-fecha"
                            opcional
                            :error="asignacion.errors.fecha_limite"
                        >
                            <Entrada
                                id="ficha-fecha"
                                v-model="asignacion.fecha_limite"
                                type="date"
                                :min="hoy"
                            />
                        </Campo>
                        <Campo
                            etiqueta="Mensaje para la empresa"
                            para="ficha-mensaje"
                            opcional
                            :error="asignacion.errors.mensaje"
                        >
                            <AreaTexto
                                id="ficha-mensaje"
                                v-model="asignacion.mensaje"
                                rows="2"
                                maxlength="500"
                            />
                        </Campo>
                        <label class="flex items-center gap-2 text-xs">
                            <input
                                v-model="asignacion.aviso"
                                type="checkbox"
                                class="size-4 accent-marca"
                            />
                            Enviar también aviso por correo
                        </label>
                        <Boton
                            type="submit"
                            variante="secundario"
                            :disabled="!diagnosticoElegido"
                            :cargando="
                                asignacion.processing && !modalReemplazar
                            "
                        >
                            {{
                                pendiente
                                    ? `Reemplazar medición ${pendiente.numero}`
                                    : 'Asignar medición'
                            }}
                        </Boton>
                        <Link
                            :href="rutas.mediciones.asignar(empresa.id)"
                            :class="`${enlace} text-center text-xs`"
                        >
                            Abrir el formulario completo
                        </Link>
                    </form>
                </section>
            </div>
        </div>

        <!-- Consultores (HU-085) -->
        <PanelConsultores
            v-else-if="pestana === 'consultores' && consultores"
            :empresa="empresa"
            :consultores="consultores"
        />

        <!-- Datos de la cuenta (A3.1d) -->
        <div
            v-else
            class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[1fr_300px] lg:grid-rows-[minmax(0,1fr)]"
        >
            <DatosEmpresaLectura
                v-if="!puede('empresas.registrar')"
                class="lg:max-h-full lg:overflow-y-auto"
                :empresa="empresa"
                :principal="principal"
                :opciones="opciones"
            />
            <form
                v-else
                class="flex flex-col gap-4 rounded-2xl border border-linea bg-white p-5 lg:max-h-full lg:overflow-y-auto"
                novalidate
                @submit.prevent="guardar"
            >
                <fieldset
                    :disabled="!puede('empresas.registrar')"
                    class="contents"
                >
                    <CamposEmpresa
                        v-model:form="form"
                        prefijo="cuenta"
                        :opciones="opciones"
                        :errores="form.errors"
                        :sector-actual="empresa.sector_id"
                        :sin-cuenta="!principal"
                    />
                </fieldset>
                <p
                    class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                >
                    Cambiar el sector no modifica las mediciones ya hechas: las
                    próximas se asignan con diagnósticos del sector nuevo.
                </p>
                <div class="mt-auto flex justify-end gap-3 pt-2">
                    <Boton
                        variante="secundario"
                        :disabled="!form.isDirty || form.processing"
                        @click="form.reset()"
                    >
                        Descartar cambios
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

            <aside class="flex flex-col gap-4 lg:max-h-full lg:overflow-y-auto">
                <section
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <h2 class="font-semibold">Estado de la cuenta</h2>
                    <dl class="mt-3 flex flex-col">
                        <div
                            class="flex items-center justify-between gap-3 border-b border-linea py-2"
                        >
                            <dt class="text-tinta-suave">Estado</dt>
                            <dd>
                                <Etiqueta v-if="!empresa.activa" tono="aviso">
                                    ⊘ Desactivada
                                </Etiqueta>
                                <Etiqueta v-else-if="!principal" tono="alerta">
                                    Sin cuenta principal
                                </Etiqueta>
                                <Etiqueta
                                    v-else-if="principal?.invitacion_pendiente"
                                    tono="alerta"
                                >
                                    ✉ Invitación pendiente
                                </Etiqueta>
                                <Etiqueta v-else tono="exito">
                                    ✓ Activa
                                </Etiqueta>
                            </dd>
                        </div>
                        <div
                            class="flex justify-between gap-3 border-b border-linea py-2"
                        >
                            <dt class="text-tinta-suave">Registrada</dt>
                            <dd class="text-right">
                                {{
                                    empresa.registrada_en
                                        ? diaYMes(
                                              new Date(empresa.registrada_en),
                                          )
                                        : '—'
                                }}
                                ·
                                {{
                                    empresa.registrada_por
                                        ? `por ${empresa.registrada_por}`
                                        : 'por la empresa'
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between gap-3 border-b border-linea py-2"
                        >
                            <dt class="text-tinta-suave">Último acceso</dt>
                            <dd>
                                {{
                                    principal?.ultimo_acceso
                                        ? momento(principal.ultimo_acceso)
                                        : 'Nunca'
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between gap-3 border-b border-linea py-2"
                        >
                            <dt class="text-tinta-suave">Mediciones</dt>
                            <dd>
                                {{ terminadas }}
                                {{ terminadas === 1 ? 'hecha' : 'hechas' }}
                                <template v-if="pendiente">
                                    · 1 pendiente</template
                                >
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 pt-2">
                            <dt class="text-tinta-suave">Colaboradores</dt>
                            <dd>
                                <button
                                    v-if="colaboradores.length > 0"
                                    type="button"
                                    :class="enlace"
                                    :aria-expanded="verColaboradores"
                                    @click="
                                        verColaboradores = !verColaboradores
                                    "
                                >
                                    {{ colaboradoresActivos }}
                                    {{
                                        colaboradoresActivos === 1
                                            ? 'activo'
                                            : 'activos'
                                    }}<template
                                        v-if="
                                            colaboradores.length >
                                            colaboradoresActivos
                                        "
                                    >
                                        ·
                                        {{
                                            colaboradores.length -
                                            colaboradoresActivos
                                        }}
                                        desactivado</template
                                    >
                                </button>
                                <span v-else class="text-tinta-suave">
                                    Ninguno
                                </span>
                            </dd>
                        </div>
                    </dl>
                    <ul
                        v-if="verColaboradores"
                        class="mt-2 flex flex-col gap-2 rounded-md bg-lienzo px-3 py-2 text-xs"
                    >
                        <li v-for="c in colaboradores" :key="c.id">
                            <p class="font-medium">
                                {{ c.name }}
                                <span
                                    v-if="!c.activo"
                                    class="font-normal text-tinta-suave"
                                >
                                    · desactivado
                                </span>
                            </p>
                            <p class="break-all text-tinta-suave">
                                {{ c.email }}
                            </p>
                        </li>
                    </ul>
                    <p
                        v-if="!empresa.activa && empresa.motivo_desactivacion"
                        class="mt-3 rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                    >
                        Motivo: {{ empresa.motivo_desactivacion }}
                    </p>
                </section>

                <!-- La cuenta principal pasó al equipo de NuevasTIC. -->
                <section
                    v-if="!principal"
                    class="flex flex-col gap-3 rounded-xl border border-alerta/40 bg-alerta-suave p-5 text-sm"
                >
                    <h2 class="font-semibold text-alerta">
                        Sin cuenta principal
                    </h2>
                    <p class="text-alerta">
                        Nadie puede entrar por esta empresa. Dale una cuenta
                        nueva: le llega un correo para crear su contraseña.
                    </p>
                    <Boton
                        v-if="puede('empresas.registrar')"
                        @click="modalCuenta = true"
                    >
                        Dar una cuenta principal
                    </Boton>
                </section>

                <section
                    v-if="
                        puede('usuarios.gestionar') ||
                        puede('mediciones.asignar') ||
                        puede('empresas.registrar')
                    "
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <h2 class="font-semibold">Acciones</h2>
                    <div class="mt-3 flex flex-col gap-2">
                        <Boton
                            v-if="
                                principal &&
                                empresa.activa &&
                                puede('usuarios.gestionar')
                            "
                            variante="secundario"
                            @click="verComo"
                        >
                            Ver como esta empresa
                        </Boton>
                        <Boton
                            v-if="empresa.activa && puede('mediciones.asignar')"
                            variante="secundario"
                            :href="rutas.mediciones.asignar(empresa.id)"
                        >
                            Asignar medición
                        </Boton>
                        <template v-if="puede('empresas.registrar')">
                            <Boton
                                v-if="empresa.activa"
                                variante="secundario"
                                @click="modalDesactivar = true"
                            >
                                Desactivar cuenta
                            </Boton>
                            <Boton
                                v-else
                                :disabled="!principal"
                                @click="reactivar"
                            >
                                Reactivar cuenta
                            </Boton>
                        </template>
                        <Boton
                            v-if="
                                !empresa.activa && puede('usuarios.gestionar')
                            "
                            variante="peligro"
                            @click="modalEliminar = true"
                        >
                            Eliminar empresa
                        </Boton>
                    </div>
                    <p
                        v-if="puede('empresas.registrar')"
                        class="mt-3 text-xs text-tinta-suave"
                    >
                        {{
                            empresa.activa
                                ? 'Desactivar impide entrar a la empresa; sus mediciones y resultados se conservan.'
                                : !principal
                                  ? 'Para reactivarla, primero dale una cuenta principal.'
                                  : puede('usuarios.gestionar')
                                    ? 'Al reactivarla, la empresa y sus colaboradores vuelven a entrar. Si no se va a usar más, elimínala: se puede recuperar durante 90 días.'
                                    : 'Al reactivarla, la empresa y sus colaboradores vuelven a entrar.'
                        }}
                    </p>
                </section>
            </aside>
        </div>
    </div>

    <template v-if="pendiente">
        <ModalCambiarFecha
            v-model:abierto="modalFecha"
            :medicion="pendiente"
            :empresa="empresa.nombre"
        />
        <ModalCorreoAviso
            v-model:abierto="modalCorreo"
            :medicion="pendiente"
            :accion="
                pendiente.estado === 'en_curso'
                    ? 'Recordatorio'
                    : 'Reenviar aviso'
            "
            :empresa="empresa.nombre"
        />
        <ModalCancelarMedicion
            v-model:abierto="modalCancelar"
            :medicion="pendiente"
            :empresa="empresa.nombre"
        />
        <ModalReemplazarMedicion
            v-if="diagnosticoElegido"
            v-model:abierto="modalReemplazar"
            :empresa="empresa.nombre"
            :actual="pendiente"
            :nueva="{
                diagnostico: diagnosticoElegido.nombre,
                version: diagnosticoElegido.version,
                fecha_limite: asignacion.fecha_limite,
            }"
            :procesando="asignacion.processing"
            @confirmar="asignar(true)"
        />
    </template>
    <ModalCuentaPrincipal
        v-if="!principal"
        v-model:abierto="modalCuenta"
        :empresa="empresa"
    />
    <ModalEliminarEmpresa
        v-if="!empresa.activa && puede('usuarios.gestionar')"
        v-model:abierto="modalEliminar"
        :empresa="empresa"
        :principal="principal?.name ?? null"
        :mediciones="terminadas"
        :colaboradores="colaboradores.length"
    />
    <ModalDesactivarEmpresa
        v-model:abierto="modalDesactivar"
        :empresa="empresa"
        :mediciones="mediciones.length"
        :colaboradores="colaboradoresActivos"
        :correo="principal?.email ?? null"
    />
</template>
