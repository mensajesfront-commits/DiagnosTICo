<script setup lang="ts">
/**
 * Detalle de un rol en A5.1.
 *
 * - Rol del sistema (A5.1, A5.1b, A5.1e): nombre, descripción y permisos
 *   bloqueados; no se elimina (RN-027).
 * - Rol creado (A5.1c): se editan nombre, descripción, si está activo y los
 *   permisos (HU-050). Se elimina solo si no tiene cuentas (HU-051).
 *
 * Envía (rol creado): PUT /roles/{id} con nombre, descripcion, activo y
 * permisos[]. Los cambios aplican a todas las cuentas con ese rol.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import GrillaPermisos from '@/components/usuarios/GrillaPermisos.vue';
import { rutas } from '@/lib/rutas';
import type { BloquePermisos, Rol } from '@/types/usuarios';

const props = defineProps<{ rol: Rol; bloques: BloquePermisos[] }>();

const emit = defineEmits<{
    asignar: [];
    eliminar: [];
    invitar: [];
    cambiarRol: [cuentaId: number];
}>();

const form = useForm({
    nombre: '',
    descripcion: '',
    activo: true,
    permisos: [] as string[],
});

watch(
    () => props.rol,
    (rol) => {
        form.defaults({
            nombre: rol.nombre,
            descripcion: rol.descripcion ?? '',
            activo: rol.activo,
            permisos: [...rol.permisos],
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

/** Los roles del sistema muestran sus permisos sin poder cambiarlos. */
const permisosDelSistema = computed({
    get: () => props.rol.permisos,
    set: () => {},
});

const puedeEliminar = computed(
    () => !props.rol.del_sistema && props.rol.cuentas_total === 0,
);

const restantes = computed(
    () => props.rol.cuentas_total - props.rol.cuentas.length,
);

function guardar(): void {
    form.put(rutas.roles.actualizar(props.rol.id), { preserveScroll: true });
}
</script>

<template>
    <section
        class="flex flex-col gap-5 rounded-xl border border-linea bg-white p-6"
    >
        <p
            v-if="!rol.activo"
            class="flex gap-2 rounded-md bg-alerta-suave px-4 py-3 text-sm text-alerta"
        >
            <span aria-hidden="true">ⓘ</span>
            <span>
                {{
                    rol.aviso ??
                    'Este rol está inactivo: no se puede asignar a ninguna cuenta hasta que lo actives.'
                }}
            </span>
        </p>

        <!-- Rol del sistema: datos bloqueados -->
        <div v-if="rol.del_sistema" class="grid gap-4 sm:grid-cols-2">
            <Campo
                etiqueta="Nombre del rol"
                para="rol-sistema-nombre"
                ayuda="Rol del sistema: el nombre no se cambia."
            >
                <div class="relative">
                    <Entrada
                        id="rol-sistema-nombre"
                        :model-value="rol.nombre"
                        disabled
                        class="bg-lienzo-oscuro pr-10 text-tinta-suave"
                    />
                    <Lock
                        class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-tinta-suave"
                        aria-hidden="true"
                    />
                </div>
            </Campo>
            <Campo
                etiqueta="Descripción"
                para="rol-sistema-descripcion"
                ayuda="Fija para este rol."
            >
                <div class="relative">
                    <Entrada
                        id="rol-sistema-descripcion"
                        :model-value="rol.descripcion ?? ''"
                        disabled
                        class="bg-lienzo-oscuro pr-10 text-tinta-suave"
                    />
                    <Lock
                        class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-tinta-suave"
                        aria-hidden="true"
                    />
                </div>
            </Campo>
        </div>

        <!-- Rol creado: se edita -->
        <div
            v-else
            class="grid items-start gap-4 sm:grid-cols-[1fr_1.4fr_auto]"
        >
            <Campo
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
            v-if="rol.del_sistema"
            v-model="permisosDelSistema"
            :bloques="bloques"
            :notas="rol.notas_permisos"
            bloqueada
            prefijo="sistema"
        >
            <template #ayuda>
                <p class="-mt-2 text-xs text-tinta-suave">
                    Permisos fijos del rol {{ rol.nombre }}: los roles del
                    sistema no cambian sus permisos.
                </p>
            </template>
        </GrillaPermisos>
        <GrillaPermisos
            v-else
            v-model="form.permisos"
            :bloques="bloques"
            :notas="rol.notas_permisos"
            prefijo="editar"
        >
            <template #ayuda>
                <p class="-mt-2 text-xs text-tinta-suave">
                    Marca lo que puede hacer este rol. Las cuentas con este rol
                    solo ven las secciones permitidas.
                </p>
            </template>
        </GrillaPermisos>

        <!-- Cuentas con este rol -->
        <div class="flex flex-col gap-3 border-t border-linea pt-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold">
                    Cuentas con este rol · {{ rol.cuentas_total }}
                </h3>
                <Boton
                    variante="secundario"
                    tamano="sm"
                    :disabled="!rol.activo"
                    @click="emit('asignar')"
                >
                    + Asignar a una cuenta
                </Boton>
            </div>

            <ul v-if="rol.cuentas.length > 0" class="text-sm">
                <li
                    v-for="cuenta in rol.cuentas"
                    :key="cuenta.id"
                    class="grid grid-cols-[minmax(8rem,12rem)_1fr_auto] items-center gap-3 border-b border-linea py-2.5"
                >
                    <span class="font-medium">{{ cuenta.nombre }}</span>
                    <span class="truncate text-tinta-suave">
                        {{ cuenta.detalle }}
                    </span>
                    <span
                        v-if="cuenta.es_tuya"
                        class="text-xs text-tinta-suave"
                    >
                        Tu cuenta
                    </span>
                    <button
                        v-else
                        type="button"
                        class="text-sm text-marca hover:underline"
                        @click="emit('cambiarRol', cuenta.id)"
                    >
                        Cambiar rol…
                    </button>
                </li>
            </ul>
            <p v-else class="text-sm text-tinta-suave">
                Ninguna cuenta tiene este rol todavía.<template
                    v-if="!rol.activo"
                >
                    Se podrá asignar cuando el rol esté activo.</template
                >
            </p>

            <p class="text-xs text-tinta-suave">
                <template v-if="restantes > 0">
                    Mostrando {{ rol.cuentas.length }} de
                    {{ rol.cuentas_total }}.
                </template>
                <template v-if="rol.nombre === 'Administrador'">
                    Para sumar a otra persona del equipo de NuevasTIC, usa
                    <button
                        type="button"
                        class="text-marca underline underline-offset-2"
                        @click="emit('invitar')"
                    >
                        Invitar usuario</button
                    >. También puedes asignar este rol a una cuenta que ya
                    existe.
                </template>
                <template v-else-if="rol.nombre === 'Empresa'">
                    Las cuentas de empresa se crean al registrar la empresa.
                    <Link
                        :href="rutas.usuarios.lista('Empresa')"
                        class="text-marca underline underline-offset-2"
                    >
                        Ver todas en Usuarios
                    </Link>
                </template>
                <template v-else-if="rol.nombre === 'Colaborador'">
                    Los colaboradores los crea cada empresa desde su menú; aquí
                    solo puedes verlos o desactivarlos.
                    <Link
                        :href="rutas.usuarios.lista('Colaborador')"
                        class="text-marca underline underline-offset-2"
                    >
                        Ver todas en Usuarios
                    </Link>
                </template>
            </p>
        </div>

        <!-- Pie -->
        <div
            class="flex flex-wrap items-center gap-3 border-t border-linea pt-5"
        >
            <template v-if="rol.del_sistema">
                <Boton variante="secundario" disabled>Eliminar rol</Boton>
                <span class="text-sm text-tinta-suave">
                    Los roles del sistema no se eliminan.
                </span>
            </template>
            <template v-else>
                <Boton
                    variante="secundario"
                    class="border-aviso text-aviso hover:bg-aviso-suave"
                    :disabled="!puedeEliminar"
                    @click="emit('eliminar')"
                >
                    Eliminar rol
                </Boton>
                <span v-if="!puedeEliminar" class="text-xs text-tinta-suave">
                    Tiene cuentas: pásalas a otro rol para poder eliminarlo.
                </span>
                <span class="flex-1" />
                <Boton
                    variante="secundario"
                    :disabled="!form.isDirty"
                    @click="form.reset()"
                >
                    Cancelar
                </Boton>
                <Boton
                    :disabled="!form.isDirty || form.nombre.trim() === ''"
                    :cargando="form.processing"
                    @click="guardar"
                >
                    Guardar rol
                </Boton>
            </template>
        </div>
    </section>
</template>
