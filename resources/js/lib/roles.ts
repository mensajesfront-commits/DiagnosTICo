/**
 * Ícono y colores de cada rol en A5 y A5.1: Administrador (azul), Empresa
 * (verde), Colaborador (celeste), Consultor (ámbar) y los creados (gris).
 */
import { Building2, Eye, Shield, ShieldCheck, Users } from '@lucide/vue';
import type { Component } from 'vue';

export function estiloRol(nombre: string | null): {
    icono: Component;
    suave: string;
} {
    if (nombre === 'Administrador') {
        return { icono: ShieldCheck, suave: 'bg-marca-suave text-marca' };
    }

    if (nombre === 'Empresa') {
        return { icono: Building2, suave: 'bg-exito-suave text-exito' };
    }

    if (nombre === 'Colaborador') {
        return {
            icono: Users,
            suave: 'bg-nivel-camino-suave text-nivel-camino-texto',
        };
    }

    // RN-030: externo que solo mira sus empresas vinculadas.
    if (nombre === 'Consultor') {
        return { icono: Eye, suave: 'bg-alerta-suave text-alerta' };
    }

    return { icono: Shield, suave: 'bg-lienzo-oscuro text-tinta' };
}
