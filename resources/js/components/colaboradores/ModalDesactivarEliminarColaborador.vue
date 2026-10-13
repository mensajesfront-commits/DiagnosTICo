<script setup lang="ts">
/**
 * E12 · Desactivar o eliminar un colaborador (HU-079, RN-004, DEC-017).
 *
 * Paso 1 · elegir: "Desactivar" (se puede reactivar desde la lista) o
 * "Eliminar" (sale de la lista). Los desactivados abren directo en
 * "eliminar".
 * Paso 2 · eliminar: aviso de lo que pasa y botón "Eliminar".
 * Paso 3 · confirmar: segunda confirmación con "Sí, eliminar a …". Es con
 * botones y no escribiendo el correo, porque el correo y el nombre se
 * pueden cambiar (pedido del equipo). El Administrador lo puede recuperar
 * durante 90 días; después se borra.
 *
 * Envía: POST /colaboradores/{id}/desactivar o DELETE /colaboradores/{id}.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import TarjetaOpcion from '@/components/base/TarjetaOpcion.vue';
import { rutas } from '@/lib/rutas';
import type { Colaborador } from '@/types/colaboradores';

type Paso = 'elegir' | 'desactivar' | 'eliminar' | 'confirmar';

const props = withDefaults(
    defineProps<{ colaborador: Colaborador; inicio?: 'elegir' | 'eliminar' }>(),
    { inicio: 'elegir' },
);

const abierto = defineModel<boolean>('abierto', { default: false });

const paso = ref<Paso>(props.inicio);
const desactivarForm = useForm({});
const eliminarForm = useForm({});

watch(abierto, (valor) => {
    if (valor) {
        paso.value = props.inicio;
    }
});

const titulo = computed(
    () =>
        ({
            elegir: `¿Qué quieres hacer con ${props.colaborador.nombre}?`,
            desactivar: `¿Desactivar a ${props.colaborador.nombre}?`,
            eliminar: `¿Eliminar a ${props.colaborador.nombre}?`,
            confirmar: '¿Seguro? Esta es la última confirmación',
        })[paso.value],
);

const textoAtras = computed(() =>
    paso.value === 'confirmar' || props.inicio === 'elegir'
        ? 'Atrás'
        : 'Cancelar',
);

function atras(): void {
    if (paso.value === 'confirmar') {
        paso.value = 'eliminar';
    } else if (props.inicio === 'elegir') {
        paso.value = 'elegir';
    } else {
        abierto.value = false;
    }
}

function desactivar(): void {
    desactivarForm.post(rutas.colaboradores.desactivar(props.colaborador.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}

function eliminar(): void {
    eliminarForm.delete(rutas.colaboradores.eliminar(props.colaborador.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="titulo"
        :descripcion="colaborador.correo"
    >
        <div v-if="paso === 'elegir'" class="flex flex-col gap-3">
            <TarjetaOpcion titulo="Desactivar" @elegir="paso = 'desactivar'">
                No podrá iniciar sesión, pero sigue en tu lista y puedes
                reactivarlo cuando quieras.
            </TarjetaOpcion>
            <TarjetaOpcion
                titulo="Eliminar"
                class="hover:border-aviso hover:bg-aviso-suave/40"
                @elegir="paso = 'eliminar'"
            >
                Sale de tu lista y ya no puede entrar. Úsalo si ya no hace parte
                de tu equipo.
            </TarjetaOpcion>
        </div>

        <p v-else-if="paso === 'desactivar'" class="text-sm">
            No podrá iniciar sesión desde ahora. Sus respuestas y resultados se
            conservan, y puedes reactivarlo desde esta misma lista.
        </p>

        <p
            v-else-if="paso === 'eliminar'"
            class="rounded-md bg-aviso-suave px-3 py-2.5 text-sm text-aviso"
        >
            {{ colaborador.nombre }} sale de tu lista y ya no puede entrar. Si
            fue un error, escribe a NuevasTIC en los próximos 90 días para
            recuperarlo; después se borra para siempre.
        </p>

        <div v-else class="flex flex-col gap-2 text-sm">
            <p>
                Vas a eliminar a <strong>{{ colaborador.nombre }}</strong> ({{
                    colaborador.correo
                }}) de tu equipo.
            </p>
            <p class="text-tinta-suave">
                Si solo quieres quitarle el acceso por un tiempo, vuelve atrás y
                elige "Desactivar".
            </p>
        </div>

        <template #pie>
            <Boton
                v-if="paso === 'elegir'"
                variante="secundario"
                @click="abierto = false"
            >
                Cancelar
            </Boton>
            <template v-else>
                <Boton variante="secundario" @click="atras">
                    {{ textoAtras }}
                </Boton>
                <Boton
                    v-if="paso === 'desactivar'"
                    :cargando="desactivarForm.processing"
                    @click="desactivar"
                >
                    Desactivar
                </Boton>
                <Boton
                    v-else-if="paso === 'eliminar'"
                    variante="peligro"
                    @click="paso = 'confirmar'"
                >
                    Eliminar
                </Boton>
                <Boton
                    v-else
                    variante="peligro"
                    :cargando="eliminarForm.processing"
                    @click="eliminar"
                >
                    Sí, eliminar a {{ colaborador.nombre.split(' ')[0] }}
                </Boton>
            </template>
        </template>
    </Modal>
</template>
