<script setup lang="ts">
/**
 * Detalle de un rol en A5.1.
 *
 * - Rol del sistema (A5.1, A5.1b, A5.1e): nombre, descripción y permisos
 *   bloqueados; no se elimina (RN-027).
 * - Rol creado (A5.1c): se editan nombre, descripción, si está activo y los
 *   permisos (HU-050). Se elimina solo si no tiene cuentas (HU-051).
 *   Desactivarlo bloquea a sus cuentas hasta que tengan otro rol; el propio
 *   rol no se desactiva.
 *
 * Envía (rol creado): PUT /roles/{id} con nombre, descripcion, activo y
 * permisos[]. Los cambios aplican a todas las cuentas con ese rol.
 *
 * Diseño: arriba una cabecera con el ícono del rol, su nombre, su
 * descripción y píldoras (del sistema, activo o inactivo, cuentas). Las
 * cuentas van con un círculo de iniciales y los botones con ícono.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { Lock, Plus, RefreshCw, Save, Trash2, Users } from '@lucide/vue';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Interruptor from '@/components/base/Interruptor.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import GrillaPermisos from '@/components/usuarios/GrillaPermisos.vue';
import { usePermisos } from '@/composables/usePermisos';
import { estiloRol } from '@/lib/roles';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { BloquePermisos, Rol } from '@/types/usuarios';

const props = defineProps<{ rol: Rol; bloques: BloquePermisos[] }>();

const { puede } = usePermisos();

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

const iniciales = (nombre: string) =>
    nombre
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase())
        .join('');

function guardar(): void {
    form.put(rutas.roles.actualizar(props.rol.id), { preserveScroll: true });
}
</script>

<template>
    <section
        class="flex flex-col gap-5 rounded-2xl border border-linea bg-white p-6"
    >
        <header
            class="-mx-6 -mt-6 flex flex-wrap items-center gap-4 rounded-t-2xl border-b border-linea bg-linear-to-br from-marca-suave/70 to-white px-6 py-5"
        >
            <span
                :class="
                    cn(
                        'flex size-12 shrink-0 items-center justify-center rounded-xl',
                        estiloRol(rol.nombre).suave,
                    )
                "
                aria-hidden="true"
            >
                <component :is="estiloRol(rol.nombre).icono" class="size-6" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="text-lg leading-tight font-semibold">
                    {{ rol.nombre }}
                </h2>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    {{ rol.descripcion || rol.resumen }}
                </p>
            </div>
            <div class="flex flex-wrap gap-1.5 text-xs">
                <span
                    v-if="rol.del_sistema"
                    class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 font-medium text-tinta-suave shadow-sm"
                >
                    <Lock class="size-3" aria-hidden="true" />
                    Del sistema
                </span>
                <span
                    :class="
                        cn(
                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-medium',
                            rol.activo
                                ? 'bg-exito-suave text-exito'
                                : 'bg-alerta-suave text-alerta',
                        )
                    "
                >
                    <span
                        :class="
                            cn(
                                'size-1.5 rounded-full',
                                rol.activo ? 'bg-exito' : 'bg-alerta',
                            )
                        "
                        aria-hidden="true"
                    />
                    {{ rol.activo ? 'Activo' : 'Inactivo' }}
                </span>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 font-medium shadow-sm"
                >
                    <Users class="size-3" aria-hidden="true" />
                    {{ rol.cuentas_total }}
                    {{ rol.cuentas_total === 1 ? 'cuenta' : 'cuentas' }}
                </span>
            </div>
        </header>

        <Recuadro v-if="!rol.activo" tono="alerta">
            {{
                rol.aviso ??
                'Este rol está inactivo: no se puede asignar a ninguna cuenta hasta que lo actives.'
            }}
        </Recuadro>

        <!-- Rol creado: se edita (con usuarios.gestionar) -->
        <fieldset
            v-if="!rol.del_sistema"
            :disabled="!puede('usuarios.gestionar')"
            class="grid items-start gap-4 sm:grid-cols-[1fr_1.4fr_auto]"
        >
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
            <div class="flex flex-col gap-1 pt-6">
                <div class="rounded-xl border border-linea px-3 py-0.5">
                    <Interruptor id="rol-activo" v-model="form.activo">
                        Rol activo
                    </Interruptor>
                </div>
                <!-- Desactivarlo bloquea a sus cuentas (RolesController). -->
                <p
                    v-if="!form.activo && rol.activo && rol.cuentas_total > 0"
                    class="max-w-56 text-xs text-aviso"
                >
                    {{
                        rol.cuentas_total === 1
                            ? 'Al guardar, la cuenta con este rol no podrá entrar hasta que le asignes otro rol.'
                            : `Al guardar, las ${rol.cuentas_total} cuentas con este rol no podrán entrar hasta que les asignes otro rol.`
                    }}
                </p>
                <p v-if="form.errors.activo" class="text-xs text-aviso">
                    {{ form.errors.activo }}
                </p>
            </div>
        </fieldset>

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
            :bloqueada="!puede('usuarios.gestionar')"
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
                    v-if="puede('usuarios.gestionar')"
                    variante="secundario"
                    tamano="sm"
                    class="gap-1.5"
                    :disabled="!rol.activo"
                    @click="emit('asignar')"
                >
                    <Plus class="size-3.5" aria-hidden="true" />
                    Asignar a una cuenta
                </Boton>
            </div>

            <ul
                v-if="rol.cuentas.length > 0"
                class="flex flex-col gap-2 text-sm"
            >
                <li
                    v-for="cuenta in rol.cuentas"
                    :key="cuenta.id"
                    class="flex flex-wrap items-center gap-3 rounded-xl border border-linea px-3 py-2.5"
                >
                    <span
                        :class="
                            cn(
                                'flex size-8 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                                estiloRol(rol.nombre).suave,
                            )
                        "
                        aria-hidden="true"
                    >
                        {{ iniciales(cuenta.nombre) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium">
                            {{ cuenta.nombre }}
                        </span>
                        <span class="block truncate text-xs text-tinta-suave">
                            {{ cuenta.detalle }}
                        </span>
                    </span>
                    <span
                        v-if="cuenta.es_tuya"
                        class="rounded-full bg-marca-suave px-2 py-0.5 text-[11px] font-medium text-marca"
                    >
                        Tu cuenta
                    </span>
                    <span
                        v-else-if="cuenta.es_admin_principal"
                        class="rounded-full bg-lienzo-oscuro px-2 py-0.5 text-[11px] font-medium text-tinta-suave"
                    >
                        Administrador principal
                    </span>
                    <!-- Los colaboradores no cambian de rol (RN-025). -->
                    <button
                        v-else-if="
                            puede('usuarios.gestionar') &&
                            rol.nombre !== 'Colaborador'
                        "
                        type="button"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-tinta-suave transition hover:bg-lienzo hover:text-marca"
                        @click="emit('cambiarRol', cuenta.id)"
                    >
                        <RefreshCw class="size-3.5" aria-hidden="true" />
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
                <template
                    v-if="
                        rol.nombre === 'Administrador' &&
                        puede('usuarios.gestionar')
                    "
                >
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
                    Los colaboradores los crea cada empresa desde su menú y no
                    cambian de rol; aquí solo puedes verlos, desactivarlos o
                    eliminarlos.
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
            v-if="puede('usuarios.gestionar')"
            class="flex flex-wrap items-center gap-3 border-t border-linea pt-5"
        >
            <template v-if="rol.del_sistema">
                <Boton variante="secundario" class="gap-1.5" disabled>
                    <Trash2 class="size-4" aria-hidden="true" />
                    Eliminar rol
                </Boton>
                <span class="text-sm text-tinta-suave">
                    Los roles del sistema no se eliminan.
                </span>
            </template>
            <template v-else>
                <Boton
                    variante="secundario"
                    class="gap-1.5 border-aviso text-aviso hover:bg-aviso-suave"
                    :disabled="!puedeEliminar"
                    @click="emit('eliminar')"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
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
                    class="gap-1.5"
                    :disabled="!form.isDirty || form.nombre.trim() === ''"
                    :cargando="form.processing"
                    @click="guardar"
                >
                    <Save class="size-4" aria-hidden="true" />
                    Guardar rol
                </Boton>
            </template>
        </div>
    </section>
</template>
