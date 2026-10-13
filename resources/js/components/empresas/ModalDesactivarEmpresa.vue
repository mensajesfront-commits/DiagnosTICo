<script setup lang="ts">
/**
 * A3.1f · Desactivar la cuenta de una empresa (HU-035).
 *
 * Dice cuántas mediciones se conservan; el motivo y el aviso por correo son
 * opcionales. Nadie de la empresa (ni sus colaboradores) puede entrar
 * mientras esté desactivada (RN-004, RN-025). Se reactiva desde la misma
 * ficha.
 *
 * Envía: POST /empresas/{id}/desactivar con motivo y avisar.
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';

const props = defineProps<{
    empresa: { id: number; nombre: string };
    /** Mediciones que se conservan (no canceladas). */
    mediciones: number;
    /** Colaboradores activos. */
    colaboradores: number;
    /** Correo del usuario principal, para el aviso. */
    correo: string | null;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({ motivo: '', avisar: false });

watch(abierto, (valor) => {
    if (valor) {
        form.reset();
        form.clearErrors();
    }
});

function desactivar(): void {
    form.post(rutas.empresas.desactivar(props.empresa.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (abierto.value = false),
    });
}

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Desactivar la cuenta de ${empresa.nombre}?`"
    >
        <div class="flex flex-col gap-4 text-sm">
            <p>
                La empresa<template v-if="colaboradores > 0">
                    y sus
                    {{
                        plural(
                            colaboradores,
                            'colaborador activo',
                            'colaboradores activos',
                        )
                    }}</template
                >{{ ' ' }}<strong>no podrán entrar</strong> al sistema. Sus
                {{ plural(mediciones, 'medición', 'mediciones') }} se conservan,
                y puedes reactivarla cuando quieras desde Datos de la cuenta.
            </p>

            <Campo
                etiqueta="Motivo"
                para="desactivar-motivo"
                opcional
                :error="form.errors.motivo"
            >
                <AreaTexto
                    id="desactivar-motivo"
                    v-model="form.motivo"
                    rows="2"
                    maxlength="255"
                />
            </Campo>

            <label v-if="correo" class="flex items-center gap-2">
                <input
                    v-model="form.avisar"
                    type="checkbox"
                    class="size-4 accent-marca"
                />
                Avisar por correo a {{ correo }}
            </label>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton :cargando="form.processing" @click="desactivar">
                Desactivar cuenta
            </Boton>
        </template>
    </Modal>
</template>
