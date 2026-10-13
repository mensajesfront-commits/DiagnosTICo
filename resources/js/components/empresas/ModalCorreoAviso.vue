<script setup lang="ts">
/**
 * "Recordatorio" (en curso) y "Reenviar aviso" (no iniciada) (A1b, A1c,
 * HU-008): abre el correo de la medición (A3.1e) con los datos reales,
 * permite editarlo, enviarse una prueba y enviarlo. Al abrir, pide los
 * datos a GET /mediciones/{id}/correo.
 *
 * El envío va en la cola: la pantalla no se bloquea (HU-037 CA-004). Si no
 * se pudo poner en marcha, se avisa y se puede reintentar (CA-005).
 */
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import EditorCorreo from '@/components/empresas/EditorCorreo.vue';
import type { ValoresCorreo } from '@/lib/correo';
import { haceCuanto } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';

const props = defineProps<{
    medicion: { id: number; numero: number };
    /** "Recordatorio" o "Reenviar aviso". */
    accion: string;
    empresa: string;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

type DatosCorreo = {
    asunto: string;
    cuerpo: string;
    para: { name: string; email: string } | null;
    variables: ValoresCorreo;
    ultimo_aviso: string | null;
};

const datos = ref<DatosCorreo | null>(null);
const cargando = ref(false);
const fallo = ref(false);

const form = useForm({ asunto: '', cuerpo: '', guardar_plantilla: false });
const probando = ref(false);

async function cargar(): Promise<void> {
    cargando.value = true;
    fallo.value = false;

    try {
        const r = await fetch(rutas.mediciones.correo(props.medicion.id), {
            headers: { Accept: 'application/json' },
        });

        if (!r.ok) {
            throw new Error(String(r.status));
        }

        datos.value = (await r.json()) as DatosCorreo;
        form.defaults({
            asunto: datos.value.asunto,
            cuerpo: datos.value.cuerpo,
            guardar_plantilla: false,
        });
        form.reset();
        form.clearErrors();
    } catch {
        fallo.value = true;
    } finally {
        cargando.value = false;
    }
}

// `immediate`: en A1 el modal se crea ya abierto.
watch(abierto, (valor) => valor && void cargar(), { immediate: true });

const para = computed(() =>
    datos.value?.para
        ? `${datos.value.para.email} (${datos.value.para.name} · usuario principal)`
        : '',
);

const errorEnvio = computed(
    () => (form.errors as Record<string, string | undefined>).envio,
);

function enviar(): void {
    form.post(rutas.mediciones.enviarCorreo(props.medicion.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (abierto.value = false),
    });
}

function probar(): void {
    router.post(
        rutas.mediciones.probarCorreo(),
        {
            asunto: form.asunto,
            cuerpo: form.cuerpo,
            variables: datos.value?.variables ?? {},
        },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (probando.value = true),
            onFinish: () => (probando.value = false),
        },
    );
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="xl"
        :titulo="`${accion} · medición ${medicion.numero} de ${empresa}`"
    >
        <p v-if="cargando" class="py-10 text-center text-sm text-tinta-suave">
            Cargando el correo…
        </p>
        <div
            v-else-if="fallo"
            class="flex flex-col items-center gap-3 py-10 text-sm text-tinta-suave"
        >
            No pudimos cargar el correo.
            <Boton variante="secundario" tamano="sm" @click="cargar">
                Reintentar
            </Boton>
        </div>
        <template v-else-if="datos">
            <EditorCorreo
                v-model:asunto="form.asunto"
                v-model:cuerpo="form.cuerpo"
                v-model:guardar-plantilla="form.guardar_plantilla"
                :para="para"
                :valores="datos.variables"
                :errores="form.errors"
            />
            <p
                v-if="errorEnvio"
                class="mt-3 rounded-md bg-aviso-suave px-3 py-2 text-sm text-aviso"
                role="alert"
            >
                {{ errorEnvio }}
            </p>
        </template>

        <template #nota>
            <template v-if="datos?.ultimo_aviso">
                Último aviso: {{ haceCuanto(datos.ultimo_aviso) }}
            </template>
        </template>
        <template #pie>
            <Boton
                variante="secundario"
                :disabled="!datos"
                :cargando="probando"
                @click="probar"
            >
                Enviarme un correo de prueba
            </Boton>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                :disabled="!datos"
                :cargando="form.processing"
                @click="enviar"
            >
                {{ errorEnvio ? 'Reintentar' : 'Enviar' }}
            </Boton>
        </template>
    </Modal>
</template>
