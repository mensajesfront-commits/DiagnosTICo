<script setup lang="ts">
/**
 * A5.2 · Crear rol (HU-049).
 *
 * Nombre (obligatorio, único), descripción opcional, si está activo, los 15
 * permisos en 6 bloques y, opcionalmente, a qué cuentas asignarlo. Cada
 * cuenta tiene un solo rol: asignarlo reemplaza el actual (RN-027).
 *
 * Envía: POST /roles con nombre, descripcion, activo, permisos[] y cuentas[].
 */
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import GrillaPermisos from '@/components/usuarios/GrillaPermisos.vue';
import { rutas } from '@/lib/rutas';
import { avisoCuentaPrincipal, coincide } from '@/lib/usuarios';
import type { BloquePermisos, Cuenta } from '@/types/usuarios';

const props = defineProps<{
    bloques: BloquePermisos[];
    /** Cuentas a las que se puede asignar (no la propia). */
    cuentas: Cuenta[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{
    nombre: string;
    descripcion: string;
    activo: boolean;
    permisos: string[];
    cuentas: number[];
}>({ nombre: '', descripcion: '', activo: true, permisos: [], cuentas: [] });

const busqueda = ref('');

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.reset();
            form.clearErrors();
            busqueda.value = '';
        }
    },
    { immediate: true },
);

const elegidas = computed(() =>
    props.cuentas.filter((c) => form.cuentas.includes(c.id)),
);

const sugerencias = computed(() =>
    busqueda.value.trim() === ''
        ? []
        : props.cuentas
              .filter((c) => !form.cuentas.includes(c.id) && !c.es_tuya)
              .filter((c) => coincide(c, busqueda.value))
              .slice(0, 5),
);

const avisos = computed(() =>
    elegidas.value
        .map((c) => avisoCuentaPrincipal(c, 'cambiar'))
        .filter((a): a is string => a !== null),
);

function agregar(cuenta: Cuenta): void {
    form.cuentas = [...form.cuentas, cuenta.id];
    busqueda.value = '';
}

function quitar(id: number): void {
    form.cuentas = form.cuentas.filter((c) => c !== id);
}

function crear(): void {
    form.post(rutas.roles.crear(), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Crear rol"
        descripcion="Define qué puede hacer y, si quieres, a quién se lo das ahora."
        ancho="xl"
    >
        <form
            id="form-crear-rol"
            class="flex flex-col gap-5"
            @submit.prevent="crear"
        >
            <div class="grid items-start gap-4 sm:grid-cols-[1fr_1.4fr_auto]">
                <Campo
                    obligatorio
                    etiqueta="Nombre del rol"
                    para="rol-nombre"
                    :error="form.errors.nombre"
                >
                    <Entrada
                        id="rol-nombre"
                        v-model="form.nombre"
                        required
                        maxlength="40"
                        :invalida="!!form.errors.nombre"
                    />
                </Campo>
                <Campo
                    etiqueta="Descripción"
                    para="rol-descripcion"
                    opcional
                    :error="form.errors.descripcion"
                >
                    <Entrada id="rol-descripcion" v-model="form.descripcion" />
                </Campo>
                <label class="flex items-center gap-2 pt-7 text-sm">
                    <input
                        v-model="form.activo"
                        type="checkbox"
                        class="size-4 accent-marca"
                    />
                    Rol activo
                </label>
            </div>

            <GrillaPermisos
                v-model="form.permisos"
                :bloques="bloques"
                prefijo="crear"
            />
            <p v-if="form.errors.permisos" class="-mt-3 text-xs text-aviso">
                {{ form.errors.permisos }}
            </p>

            <div class="flex flex-col gap-2 border-t border-linea pt-4">
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <label for="rol-cuentas" class="text-sm font-semibold">
                        Asignar a cuentas
                        <span class="font-normal text-tinta-suave"
                            >(opcional)</span
                        >
                    </label>
                    <p class="text-xs text-tinta-suave">
                        Cada cuenta tiene un solo rol: asignarlo reemplaza el
                        actual.
                    </p>
                </div>
                <div
                    class="flex flex-wrap items-center gap-2 rounded-md border border-linea-fuerte bg-white px-2 py-1.5 focus-within:border-marca"
                >
                    <span
                        v-for="cuenta in elegidas"
                        :key="cuenta.id"
                        class="inline-flex items-center gap-1.5 rounded-full bg-marca-suave px-3 py-1 text-xs"
                    >
                        {{ cuenta.nombre }}
                        <span class="text-tinta-suave">
                            · hoy: {{ cuenta.rol ?? 'sin rol' }}
                        </span>
                        <button
                            type="button"
                            class="text-tinta-suave hover:text-aviso"
                            :aria-label="`Quitar a ${cuenta.nombre}`"
                            @click="quitar(cuenta.id)"
                        >
                            ×
                        </button>
                    </span>
                    <input
                        id="rol-cuentas"
                        v-model="busqueda"
                        type="search"
                        placeholder="Buscar por nombre o correo…"
                        class="min-w-48 flex-1 border-0 bg-transparent px-1 py-1 text-sm outline-none"
                        autocomplete="off"
                    />
                </div>
                <ul
                    v-if="sugerencias.length > 0"
                    class="rounded-md border border-linea bg-white text-sm shadow-sm"
                >
                    <li v-for="cuenta in sugerencias" :key="cuenta.id">
                        <button
                            type="button"
                            class="flex w-full justify-between gap-3 px-3 py-2 text-left hover:bg-lienzo"
                            @click="agregar(cuenta)"
                        >
                            <span>
                                {{ cuenta.nombre }}
                                <span class="text-xs text-tinta-suave">
                                    {{ cuenta.correo }}
                                </span>
                            </span>
                            <span class="text-xs text-tinta-suave">
                                {{ cuenta.rol
                                }}{{
                                    cuenta.empresa ? ` · ${cuenta.empresa}` : ''
                                }}
                            </span>
                        </button>
                    </li>
                </ul>
                <p
                    v-for="aviso in avisos"
                    :key="aviso"
                    class="rounded-md bg-alerta-suave px-3 py-2.5 text-sm text-alerta"
                >
                    ⚠ {{ aviso }}
                </p>
            </div>
        </form>

        <template #nota>
            Podrás asignarlo después desde el rol con "+ Asignar a una cuenta".
        </template>
        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-crear-rol"
                :disabled="form.nombre.trim() === ''"
                :cargando="form.processing"
            >
                Crear rol
            </Boton>
        </template>
    </Modal>
</template>
