<script setup lang="ts">
/**
 * Muestrario de los componentes base (T-023) con datos del wireframe A1, para
 * revisarlos contra el diseño. Solo existe en local.
 */
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import type { EstadoMedicion } from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Modal from '@/components/base/Modal.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import Tabla from '@/components/base/Tabla.vue';
import Tarjeta from '@/components/base/Tarjeta.vue';
import { niveles, ordenNiveles } from '@/lib/niveles';

type Pestana = 'todas' | 'en_curso' | 'no_iniciada' | 'vencida' | 'terminada';

const pestana = ref<Pestana>('todas');
const tipoRespuesta = ref('multiple');
const modalAbierto = ref(false);

const mediciones: {
    id: number;
    empresa: string;
    sector: string;
    asignada: string;
    vence: string;
    estado: EstadoMedicion;
    avance?: { hechas: number; total: number };
}[] = [
    {
        id: 1,
        empresa: 'Hostal Casa Verde',
        sector: 'Alojamientos',
        asignada: '1 sep',
        vence: '20 sep',
        estado: 'vencida',
        avance: { hechas: 3, total: 10 },
    },
    {
        id: 2,
        empresa: 'Clínica Dental Sonríe',
        sector: 'Médicos',
        asignada: '12 sep',
        vence: '30 sep',
        estado: 'en_curso',
        avance: { hechas: 6, total: 10 },
    },
    {
        id: 3,
        empresa: 'Restaurante La Esquina',
        sector: 'Comidas',
        asignada: '20 sep',
        vence: '20 oct',
        estado: 'no_iniciada',
    },
    {
        id: 4,
        empresa: 'Gómez Abogados',
        sector: 'Abogados',
        asignada: '2 sep',
        vence: '29 sep',
        estado: 'terminada',
    },
];

const empresasPorNivel = {
    critico: 5,
    mejorar: 16,
    camino: 11,
    sigue: 3,
    sin: 3,
};
</script>

<template>
    <Head title="Prueba técnica · componentes" />

    <div class="flex flex-col gap-6 p-6">
        <EncabezadoPagina
            titulo="Componentes base"
            descripcion="Muestrario de T-023 con datos de ejemplo del wireframe."
        >
            <Boton variante="secundario">Catálogo de categorías</Boton>
            <Boton>+ Crear diagnóstico</Boton>
        </EncabezadoPagina>

        <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <Tarjeta titulo="Mediciones" sin-relleno>
                <template #acciones>
                    <SelectorCompacto
                        v-model="pestana"
                        etiqueta-accesible="Filtrar mediciones"
                        :opciones="[
                            { valor: 'todas', etiqueta: 'Todas', cantidad: 11 },
                            {
                                valor: 'en_curso',
                                etiqueta: 'En curso',
                                cantidad: 3,
                            },
                            {
                                valor: 'no_iniciada',
                                etiqueta: 'No iniciadas',
                                cantidad: 3,
                            },
                            {
                                valor: 'vencida',
                                etiqueta: 'Vencidas',
                                cantidad: 1,
                            },
                            {
                                valor: 'terminada',
                                etiqueta: 'Terminadas',
                                cantidad: 4,
                            },
                        ]"
                    />
                </template>

                <Tabla
                    :columnas="[
                        { clave: 'empresa', titulo: 'Empresa' },
                        { clave: 'asignada', titulo: 'Asignada' },
                        { clave: 'vence', titulo: 'Vence' },
                        { clave: 'estado', titulo: 'Estado' },
                        {
                            clave: 'accion',
                            titulo: 'Acción',
                            alineacion: 'derecha',
                        },
                    ]"
                    :filas="mediciones"
                >
                    <template #celda-empresa="{ fila }">
                        <p class="font-medium">{{ fila.empresa }}</p>
                        <p class="text-xs text-tinta-suave">
                            {{ fila.sector }}
                        </p>
                    </template>
                    <template #celda-vence="{ fila }">
                        <span
                            :class="
                                fila.estado === 'vencida' &&
                                'font-semibold text-aviso'
                            "
                        >
                            {{ fila.vence }}
                        </span>
                    </template>
                    <template #celda-estado="{ fila }">
                        <EtiquetaEstado
                            :estado="fila.estado"
                            :avance="fila.avance"
                        />
                    </template>
                    <template #celda-accion>
                        <Boton variante="enlace" @click="modalAbierto = true">
                            Recordatorio
                        </Boton>
                    </template>
                </Tabla>
            </Tarjeta>

            <div class="flex flex-col gap-6">
                <Tarjeta
                    titulo="Empresas por nivel"
                    subtitulo="Según su último diagnóstico"
                >
                    <ul class="flex flex-col gap-3 text-sm">
                        <li
                            v-for="nivel in ordenNiveles"
                            :key="nivel"
                            class="grid grid-cols-[1fr_80px_24px] items-center gap-3"
                        >
                            <span>{{ niveles[nivel].nombre }}</span>
                            <span class="h-1.5 rounded-full bg-lienzo-oscuro">
                                <span
                                    :class="[
                                        'block h-1.5 rounded-full',
                                        niveles[nivel].barra,
                                    ]"
                                    :style="{
                                        width: `${(empresasPorNivel[nivel] / 16) * 100}%`,
                                    }"
                                />
                            </span>
                            <span class="text-right">
                                {{ empresasPorNivel[nivel] }}
                            </span>
                        </li>
                    </ul>
                </Tarjeta>

                <Tarjeta titulo="Etiquetas">
                    <div class="flex flex-wrap gap-2">
                        <EtiquetaNivel
                            v-for="nivel in ordenNiveles"
                            :key="nivel"
                            :nivel="nivel"
                        />
                        <Etiqueta tono="exito">✓ v2 publicada</Etiqueta>
                        <Etiqueta tono="alerta"
                            >Borrador · sin publicar</Etiqueta
                        >
                        <Etiqueta tono="aviso">⚠ 2 por corregir</Etiqueta>
                        <EtiquetaEstado estado="enviada" />
                    </div>
                </Tarjeta>
            </div>
        </div>

        <Tarjeta titulo="Botones y selector relleno">
            <div class="flex flex-wrap items-center gap-3">
                <Boton>Agregar pregunta</Boton>
                <Boton variante="secundario">Cancelar</Boton>
                <Boton variante="enlace">Ver empresa</Boton>
                <Boton variante="peligro">Eliminar</Boton>
                <Boton disabled>Publicar versión 3</Boton>
                <Boton cargando>Guardando</Boton>
                <Boton tamano="sm" variante="secundario">Editar</Boton>
            </div>
            <SelectorCompacto
                v-model="tipoRespuesta"
                estilo="relleno"
                class="mt-4 w-full max-w-md"
                etiqueta-accesible="Tipo de respuesta"
                :opciones="[
                    { valor: 'abierta', etiqueta: 'Abierta' },
                    { valor: 'unica', etiqueta: 'Opción única' },
                    { valor: 'multiple', etiqueta: 'Selección múltiple' },
                ]"
            />
        </Tarjeta>

        <Modal
            v-model:abierto="modalAbierto"
            titulo="Reasignar las 9 empresas de Abogados"
            descripcion="Al reasignar, las empresas se llevan sus mediciones."
        >
            <p class="text-sm">Contenido del modal.</p>
            <template #nota>Elige un sector destino</template>
            <template #pie>
                <Boton variante="secundario" @click="modalAbierto = false">
                    Cancelar
                </Boton>
                <Boton disabled>Reasignar 9 empresas</Boton>
            </template>
        </Modal>
    </div>
</template>
