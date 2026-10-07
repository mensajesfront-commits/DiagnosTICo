<script setup lang="ts">
/**
 * A5.3b · Desactivar o eliminar una cuenta (HU-047, RN-004, DEC-017).
 *
 * Paso 1 · elegir: "Desactivar" (se puede deshacer) o "Eliminar" (para
 * siempre). Las cuentas desactivadas o con invitación pendiente abren
 * directo en "eliminar".
 * Paso 2 · eliminar: hay que escribir el correo exacto de la cuenta; el
 * servidor lo vuelve a revisar. La cuenta sale de la vista y nadie entra,
 * pero se puede recuperar durante 90 días (filtro "Eliminadas"); después se
 * borra para siempre. Si es la cuenta principal de una empresa, se eliminan
 * también la empresa y sus colaboradores.
 *
 * Envía: POST /usuarios/{id}/desactivar (sin campos) o
 * DELETE /usuarios/{id} con confirmacion (el correo).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import TarjetaOpcion from '@/components/base/TarjetaOpcion.vue';
import { rutas } from '@/lib/rutas';
import { avisoCuentaPrincipal } from '@/lib/usuarios';
import type { Cuenta } from '@/types/usuarios';

type Paso = 'elegir' | 'desactivar' | 'eliminar';

const props = withDefaults(
    defineProps<{ cuenta: Cuenta; inicio?: 'elegir' | 'eliminar' }>(),
    { inicio: 'elegir' },
);

const abierto = defineModel<boolean>('abierto', { default: false });

const paso = ref<Paso>(props.inicio);

const desactivarForm = useForm({});
const eliminarForm = useForm({ confirmacion: '' });

watch(abierto, (valor) => {
    if (valor) {
        paso.value = props.inicio;
        eliminarForm.reset();
        eliminarForm.clearErrors();
    }
});

const avisoDesactivar = computed(() =>
    avisoCuentaPrincipal(props.cuenta, 'desactivar'),
);

const detalle = computed(() =>
    [
        props.cuenta.correo,
        `Rol ${props.cuenta.rol ?? 'sin rol'}`,
        props.cuenta.empresa,
    ]
        .filter(Boolean)
        .join(' · '),
);

const titulo = computed(
    () =>
        ({
            elegir: `¿Qué quieres hacer con la cuenta de ${props.cuenta.nombre}?`,
            desactivar: `¿Desactivar la cuenta de ${props.cuenta.nombre}?`,
            eliminar: `¿Eliminar la cuenta de ${props.cuenta.nombre}?`,
        })[paso.value],
);

const coincide = computed(
    () => eliminarForm.confirmacion.trim() === props.cuenta.correo,
);

function desactivar(): void {
    desactivarForm.post(rutas.usuarios.desactivar(props.cuenta.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}

function eliminar(): void {
    eliminarForm.delete(rutas.usuarios.eliminar(props.cuenta.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal v-model:abierto="abierto" :titulo="titulo" :descripcion="detalle">
        <!-- Paso 1 · elegir -->
        <div v-if="paso === 'elegir'" class="flex flex-col gap-3">
            <TarjetaOpcion
                titulo="Desactivar la cuenta"
                @elegir="paso = 'desactivar'"
            >
                No podrá iniciar sesión, pero no se borra nada. Puedes
                reactivarla cuando quieras.
            </TarjetaOpcion>
            <TarjetaOpcion
                titulo="Eliminar la cuenta"
                class="hover:border-aviso hover:bg-aviso-suave/40"
                @elegir="paso = 'eliminar'"
            >
                Sale de la lista y nadie puede entrar. Se puede recuperar
                durante 90 días; después se borra para siempre.
            </TarjetaOpcion>
        </div>

        <!-- Paso 2a · desactivar -->
        <div
            v-else-if="paso === 'desactivar'"
            class="flex flex-col gap-3 text-sm"
        >
            <p>
                No podrá iniciar sesión. Su información no se borra y puedes
                reactivarla desde esta misma lista.
            </p>
            <p
                v-if="avisoDesactivar"
                class="rounded-md bg-alerta-suave px-3 py-2.5 text-alerta"
            >
                ⚠ {{ avisoDesactivar }}
            </p>
        </div>

        <!-- Paso 2b · eliminar -->
        <form
            v-else
            id="form-eliminar-cuenta"
            class="flex flex-col gap-4 text-sm"
            @submit.prevent="coincide && eliminar()"
        >
            <div class="rounded-md bg-aviso-suave px-3 py-2.5 text-aviso">
                <p class="font-medium">
                    Tienes 90 días para recuperarla; después se borra para
                    siempre.
                </p>
                <p class="mt-1">
                    <template v-if="cuenta.es_principal && cuenta.empresa">
                        Es la cuenta principal de {{ cuenta.empresa }}: también
                        se eliminan <strong>la empresa</strong> y
                        <strong>todos sus colaboradores</strong>, y se recuperan
                        juntos.
                    </template>
                    <template v-else>
                        La cuenta sale de la lista y se cierran sus sesiones
                        abiertas.
                    </template>
                    Para recuperarla, filtra por Estado «Eliminadas». Si solo
                    quieres quitarle el acceso, desactívala.
                </p>
            </div>
            <Campo
                obligatorio
                etiqueta="Para confirmar, escribe el correo de la cuenta"
                para="confirmar-correo"
                :error="eliminarForm.errors.confirmacion"
            >
                <template #accion>
                    <span class="font-mono text-xs text-tinta-suave select-all">
                        {{ cuenta.correo }}
                    </span>
                </template>
                <Entrada
                    id="confirmar-correo"
                    v-model="eliminarForm.confirmacion"
                    required
                    autocomplete="off"
                    spellcheck="false"
                    :placeholder="cuenta.correo"
                    :invalida="!!eliminarForm.errors.confirmacion"
                    @paste.prevent
                />
            </Campo>
            <p class="-mt-2 text-xs text-tinta-suave">
                Escríbelo a mano, tal como aparece (no se puede pegar).
            </p>
        </form>

        <template #pie>
            <template v-if="paso === 'elegir'">
                <Boton variante="secundario" @click="abierto = false">
                    Cancelar
                </Boton>
            </template>
            <template v-else>
                <Boton
                    variante="secundario"
                    @click="
                        inicio === 'elegir'
                            ? (paso = 'elegir')
                            : (abierto = false)
                    "
                >
                    {{ inicio === 'elegir' ? 'Atrás' : 'Cancelar' }}
                </Boton>
                <Boton
                    v-if="paso === 'desactivar'"
                    :cargando="desactivarForm.processing"
                    @click="desactivar"
                >
                    Desactivar cuenta
                </Boton>
                <Boton
                    v-else
                    variante="peligro"
                    type="submit"
                    form="form-eliminar-cuenta"
                    :disabled="!coincide"
                    :cargando="eliminarForm.processing"
                >
                    Eliminar cuenta
                </Boton>
            </template>
        </template>
    </Modal>
</template>
