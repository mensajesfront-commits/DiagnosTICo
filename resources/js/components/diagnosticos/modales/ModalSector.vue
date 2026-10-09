<script setup lang="ts">
/**
 * A2.2a · Crear sector (HU-012) y A2.2 · Editar sector (HU-013).
 *
 * Envía: nombre (máx. 40, único), descripcion (opcional), ciiu_division
 * (opcional) y, al crear, activo.
 *
 * Subsectores (DEC-018): al elegir una división CIIU, todas sus clases pasan
 * a ser los subsectores del sector. Si el nombre escrito es exactamente el de
 * una división (sin mirar tildes ni mayúsculas), se elige sola.
 * Al editar, "Otras opciones" abre reasignar, desactivar o eliminar; esas
 * opciones no guardan los cambios del formulario.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Combobox from '@/components/base/Combobox.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Modal from '@/components/base/Modal.vue';
import TarjetaOpcion from '@/components/base/TarjetaOpcion.vue';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import type {
    DivisionCiiu,
    ResumenDiagnosticos,
    Sector,
} from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 40;

const props = withDefaults(
    defineProps<{
        /** Sin sector se crea uno nuevo. */
        sector?: Sector | null;
        resumen?: ResumenDiagnosticos | null;
        /** Catálogo CIIU para elegir de dónde salen los subsectores. */
        divisiones?: DivisionCiiu[];
    }>(),
    { sector: null, resumen: null, divisiones: () => [] },
);

const emit = defineEmits<{
    reasignar: [];
    desactivar: [];
    eliminar: [];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const editando = computed(() => !!props.sector);

const form = useForm({
    nombre: '',
    descripcion: '',
    ciiu_division: '',
    activo: true,
});

// --- División CIIU ----------------------------------------------------------
const etiquetaDivision = (d: DivisionCiiu) => `${d.codigo} · ${d.nombre}`;
const opcionesDivision = computed(() => props.divisiones.map(etiquetaDivision));
const divisionElegida = computed(
    () => props.divisiones.find((d) => d.codigo === form.ciiu_division) ?? null,
);
/** Se eligió en la lista: el nombre ya no la cambia. */
const elegidaAMano = ref(false);

const textoDivision = computed({
    get: () =>
        divisionElegida.value ? etiquetaDivision(divisionElegida.value) : '',
    set: (texto: string) => {
        const division = props.divisiones.find(
            (d) => etiquetaDivision(d) === texto,
        );
        form.ciiu_division = division?.codigo ?? '';
        elegidaAMano.value = true;
    },
});

function quitarDivision(): void {
    form.ciiu_division = '';
    elegidaAMano.value = true;
}

// Si el nombre es exactamente el de una división, se elige sola.
watch(
    () => form.nombre,
    (nombre) => {
        if (elegidaAMano.value || props.sector?.ciiu_division) {
            return;
        }

        const buscado = normalizar(nombre);
        form.ciiu_division =
            props.divisiones.find((d) => normalizar(d.nombre) === buscado)
                ?.codigo ?? '';
    },
);

const cambiaDivision = computed(
    () =>
        editando.value &&
        form.ciiu_division !== (props.sector?.ciiu_division ?? ''),
);

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.defaults({
                nombre: props.sector?.nombre ?? '',
                descripcion: props.sector?.descripcion ?? '',
                ciiu_division: props.sector?.ciiu_division ?? '',
                activo: props.sector?.activo ?? true,
            });
            elegidaAMano.value = false;
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

const tieneDatos = computed(
    () =>
        (props.resumen?.empresas ?? 0) > 0 ||
        (props.resumen?.mediciones ?? 0) > 0,
);

function guardar(): void {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    };

    if (props.sector) {
        form.put(rutas.sectores.actualizar(props.sector.id), opciones);
    } else {
        form.post(rutas.sectores.crear(), opciones);
    }
}

function otraOpcion(opcion: 'reasignar' | 'desactivar' | 'eliminar'): void {
    abierto.value = false;

    if (opcion === 'reasignar') {
        emit('reasignar');
    } else if (opcion === 'desactivar') {
        emit('desactivar');
    } else {
        emit('eliminar');
    }
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            editando ? `Editar sector · ${sector?.nombre}` : 'Crear sector'
        "
        :descripcion="
            editando
                ? undefined
                : 'Un sector agrupa empresas parecidas y los diagnósticos que se le asignan. Los campos son obligatorios salvo los marcados como opcionales.'
        "
        ancho="lg"
    >
        <form
            id="form-sector"
            class="flex flex-col gap-4"
            @submit.prevent="guardar"
        >
            <Campo
                obligatorio
                etiqueta="Nombre del sector"
                para="sector-nombre"
                :error="form.errors.nombre"
                :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                :ayuda="
                    editando
                        ? 'Es el nombre que verán las empresas al registrarse.'
                        : 'Es el nombre que verán las empresas al registrarse. No puede repetirse.'
                "
            >
                <Entrada
                    id="sector-nombre"
                    v-model="form.nombre"
                    required
                    :maxlength="MAXIMO_NOMBRE"
                    :invalida="!!form.errors.nombre"
                />
            </Campo>

            <div v-if="editando && sector" class="text-sm">
                <p class="text-xs font-medium">Estado</p>
                <p class="mt-1.5 flex flex-wrap items-center gap-2">
                    <Etiqueta v-if="sector.activo" tono="exito"
                        >● Activo</Etiqueta
                    >
                    <Etiqueta v-else>○ Inactivo</Etiqueta>
                    <span class="text-xs">
                        {{
                            sector.activo
                                ? 'Se ofrece al registrar empresas nuevas.'
                                : 'No se ofrece al registrar empresas nuevas.'
                        }}
                    </span>
                    <Boton
                        v-if="!sector.activo"
                        tamano="sm"
                        variante="secundario"
                        @click="otraOpcion('desactivar')"
                    >
                        Reactivar sector
                    </Boton>
                </p>
                <p v-if="sector.activo" class="mt-1 text-xs text-tinta-suave">
                    Para dejar de ofrecerlo usa «Desactivar sector» abajo. Un
                    sector desactivado aparece como «○ Inactivo» en la lista de
                    sectores y se reactiva desde aquí con «Reactivar sector».
                </p>
            </div>

            <Campo
                etiqueta="Subsectores · división CIIU"
                para="sector-division"
                opcional
                :error="form.errors.ciiu_division"
                ayuda="Busca por código o nombre (CIIU Rev. 5 A.C. del DANE). Sus clases serán las actividades económicas que elige la empresa al registrarse."
            >
                <div class="flex items-center gap-2">
                    <Combobox
                        id="sector-division"
                        v-model="textoDivision"
                        class="flex-1"
                        :opciones="opcionesDivision"
                        placeholder="Ej.: 56 o comidas"
                        :invalida="!!form.errors.ciiu_division"
                    />
                    <Boton
                        v-if="form.ciiu_division"
                        tamano="sm"
                        variante="secundario"
                        @click="quitarDivision"
                    >
                        Quitar
                    </Boton>
                </div>
            </Campo>

            <p
                v-if="divisionElegida && (!editando || cambiaDivision)"
                class="-mt-2 rounded-md bg-marca-suave px-3 py-2 text-xs"
            >
                Se agregarán sus
                <strong>{{ divisionElegida.clases }} subsectores</strong>.
                <template v-if="editando">
                    Los subsectores que no sean de esta división se desactivan;
                    las empresas que ya los tienen los conservan.
                </template>
            </p>
            <p
                v-else-if="editando && sector"
                class="-mt-2 text-xs text-tinta-suave"
            >
                Tiene {{ sector.subsectores }} subsectores activos.
                <template v-if="!form.ciiu_division && sector.ciiu_division">
                    Al quitar la división se conservan.
                </template>
            </p>

            <Campo
                etiqueta="Descripción"
                para="sector-descripcion"
                opcional
                ayuda="Solo la ve el administrador."
                :error="form.errors.descripcion"
            >
                <AreaTexto
                    id="sector-descripcion"
                    v-model="form.descripcion"
                    rows="2"
                />
            </Campo>

            <label v-if="!editando" class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.activo"
                    type="checkbox"
                    class="size-4 accent-marca"
                />
                <span class="font-medium">Sector activo</span>
                <span class="text-xs text-tinta-suave">
                    Se ofrece al registrar empresas nuevas
                </span>
            </label>

            <p
                v-if="!editando"
                class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
            >
                Al crearlo queda sin diagnósticos. Después puedes crear su
                primer diagnóstico o duplicar uno de otro sector.
            </p>

            <template v-if="editando && sector && resumen">
                <p
                    class="flex flex-wrap justify-between gap-2 rounded-md bg-lienzo px-3 py-2 text-xs"
                >
                    <span>
                        <strong>{{ resumen.empresas }}</strong> empresas ·
                        <strong>{{ sector.diagnosticos }}</strong> diagnósticos
                        · <strong>{{ resumen.mediciones }}</strong> mediciones
                    </span>
                    <span class="text-tinta-suave">
                        {{ resumen.publicados }} publicados ·
                        {{ resumen.borradores }} borrador
                    </span>
                </p>

                <div class="flex flex-col gap-2">
                    <p class="text-xs">
                        <span class="font-medium">Otras opciones</span>
                        <span class="text-tinta-suave">
                            · guarda antes tus cambios: estas opciones no los
                            conservan
                        </span>
                    </p>
                    <TarjetaOpcion
                        v-if="resumen.empresas > 0"
                        :titulo="`Reasignar las ${resumen.empresas} empresas a otro sector`"
                        @elegir="otraOpcion('reasignar')"
                    >
                        Al reasignar, las empresas se llevan sus mediciones. Si
                        {{ sector.nombre }} queda sin empresas ni mediciones,
                        podrás eliminarlo.
                    </TarjetaOpcion>
                    <TarjetaOpcion
                        v-if="sector.activo"
                        titulo="Desactivar sector"
                        @elegir="otraOpcion('desactivar')"
                    >
                        Conserva todo y deja de ofrecerse al registrar. Puedes
                        reactivarlo cuando quieras.
                    </TarjetaOpcion>
                    <TarjetaOpcion
                        :titulo="
                            tieneDatos
                                ? 'Eliminar sector · no disponible'
                                : 'Eliminar sector'
                        "
                        :deshabilitada="tieneDatos"
                        @elegir="otraOpcion('eliminar')"
                    >
                        <template v-if="tieneDatos">
                            Tiene {{ resumen.empresas }} empresas. Reasígnalas o
                            desactiva el sector. Si queda sin empresas ni
                            mediciones, podrás eliminarlo.
                        </template>
                        <template v-else>
                            No tiene empresas ni mediciones. No se puede
                            deshacer.
                        </template>
                    </TarjetaOpcion>
                </div>
            </template>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton type="submit" form="form-sector" :cargando="form.processing">
                {{ editando ? 'Guardar cambios' : 'Crear sector' }}
            </Boton>
        </template>
    </Modal>
</template>
