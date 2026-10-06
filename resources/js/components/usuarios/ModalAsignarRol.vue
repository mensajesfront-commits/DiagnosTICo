<script setup lang="ts">
/**
 * A5.5 · Asignar un rol a una cuenta que ya existe (HU-052). Su rol actual se
 * reemplaza (RN-027). Para alguien del equipo sin cuenta, "Invitar usuario".
 *
 * Envía: POST /roles/{id}/asignar con cuenta_id y avisar.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import { avisoCuentaPrincipal, coincide } from '@/lib/usuarios';
import type { Cuenta, Rol } from '@/types/usuarios';

const props = defineProps<{
    rol: Pick<Rol, 'id' | 'nombre'>;
    /** Cuentas que todavía no tienen este rol (sin la propia). */
    cuentas: Cuenta[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const MOSTRAR = 3;

const busqueda = ref('');
const form = useForm<{ cuenta_id: number | null; avisar: boolean }>({
    cuenta_id: null,
    avisar: true,
});

watch(
    abierto,
    (valor) => {
        if (valor) {
            busqueda.value = '';
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

const coincidentes = computed(() =>
    props.cuentas.filter((c) => coincide(c, busqueda.value)),
);
const visibles = computed(() => coincidentes.value.slice(0, MOSTRAR));

const elegida = computed(
    () => props.cuentas.find((c) => c.id === form.cuenta_id) ?? null,
);

const aviso = computed(() => {
    if (!elegida.value) {
        return null;
    }

    const principal = avisoCuentaPrincipal(elegida.value, 'cambiar');

    return principal
        ? `${elegida.value.nombre} dejará de ser ${elegida.value.rol}. ${principal}`
        : null;
});

function asignar(): void {
    form.post(rutas.roles.asignar(props.rol.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Asignar el rol ${rol.nombre}`"
        ancho="lg"
    >
        <template #descripcion>
            Elige una cuenta que ya existe. Su rol actual se reemplaza por
            {{ rol.nombre }}. ¿Es alguien del equipo sin cuenta? Usa
            <Link
                :href="rutas.usuarios.lista()"
                class="text-marca underline underline-offset-2"
            >
                Invitar usuario</Link
            >.
        </template>

        <form
            id="form-asignar-rol"
            class="flex flex-col gap-3"
            @submit.prevent="asignar"
        >
            <label for="asignar-buscar" class="text-xs font-medium">
                Buscar cuenta
            </label>
            <Entrada
                id="asignar-buscar"
                v-model="busqueda"
                type="search"
                placeholder="Nombre o correo…"
                autocomplete="off"
            />
            <p class="-mt-1 text-xs text-tinta-suave">
                Mostrando {{ visibles.length }} de {{ coincidentes.length }}
                <template v-if="coincidentes.length > MOSTRAR">
                    · escribe para buscar
                </template>
            </p>

            <fieldset class="flex flex-col gap-2">
                <legend class="sr-only">Cuenta</legend>
                <label
                    v-for="cuenta in visibles"
                    :key="cuenta.id"
                    :class="[
                        'flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm',
                        form.cuenta_id === cuenta.id
                            ? 'border-marca bg-marca-suave/40 ring-1 ring-marca'
                            : 'border-linea-fuerte hover:border-marca',
                    ]"
                >
                    <input
                        v-model="form.cuenta_id"
                        type="radio"
                        name="asignar-cuenta"
                        :value="cuenta.id"
                        class="size-4 accent-marca"
                    />
                    <span class="flex-1">
                        <span class="block font-medium">{{
                            cuenta.nombre
                        }}</span>
                        <span class="block text-xs text-tinta-suave">
                            {{ cuenta.correo }}
                        </span>
                    </span>
                    <span class="text-right text-xs text-tinta-suave">
                        Rol actual: {{ cuenta.rol ?? 'sin rol'
                        }}{{ cuenta.empresa ? ` · ${cuenta.empresa}` : '' }}
                    </span>
                </label>
                <p
                    v-if="visibles.length === 0"
                    class="py-4 text-center text-sm text-tinta-suave"
                >
                    Ninguna cuenta coincide con la búsqueda.
                </p>
            </fieldset>

            <p
                v-if="aviso"
                class="rounded-md bg-alerta-suave px-3 py-2.5 text-sm text-alerta"
            >
                ⚠ {{ aviso }}
            </p>

            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.avisar"
                    type="checkbox"
                    class="size-4 accent-marca"
                />
                Avisarle por correo del cambio de rol
            </label>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-asignar-rol"
                :disabled="form.cuenta_id === null"
                :cargando="form.processing"
            >
                Asignar rol
            </Boton>
        </template>
    </Modal>
</template>
