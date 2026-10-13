/**
 * Datos de "Usuarios y roles" (A5–A5.5) que entrega el backend.
 * Contrato documentado en docs/14_FRONTEND.md.
 */

/** `eliminada`: se puede recuperar durante 90 días (DEC-017). */
export type EstadoCuenta =
    | 'activa'
    | 'desactivada'
    | 'invitacion'
    | 'eliminada';

export type Cuenta = {
    id: number;
    nombre: string;
    correo: string;
    /** Rol de la cuenta (cada cuenta tiene uno, RN-027). */
    rol: string | null;
    /** Nombre de la empresa; null en las cuentas internas. */
    empresa: string | null;
    /** Cuenta principal de su empresa (rol Empresa). */
    es_principal: boolean;
    /** Colaboradores activos de su empresa, para los avisos de A5.3b y A5.5. */
    colaboradores_activos: number;
    /** Medición pendiente de su empresa ("Medición 4 no iniciada (vence el 20 oct)"). */
    medicion_pendiente: string | null;
    estado: EstadoCuenta;
    invitacion_enviada_en: string | null;
    /** La cuenta con la que se entró: no se desactiva ni cambia su rol. */
    es_tuya: boolean;
    /** Cuándo se eliminó; null si no está eliminada. */
    eliminada_en: string | null;
    /** Último día para recuperarla; después se borra para siempre. */
    se_borra_el: string | null;
};

export type PermisoTexto = { nombre: string; texto: string };

/** Uno de los 6 bloques de A5.2 ("Diagnósticos", "Empresas"…). */
export type BloquePermisos = { bloque: string; permisos: PermisoTexto[] };

export type CuentaDelRol = {
    id: number;
    nombre: string;
    /** Empresa o correo, lo que se muestra al lado del nombre. */
    detalle: string;
    es_tuya: boolean;
};

export type Rol = {
    id: number;
    nombre: string;
    descripcion: string | null;
    /** Texto corto de la lista ("Acceso total", "Sin gestionar equipo"). */
    resumen: string;
    activo: boolean;
    /** Administrador, Empresa y Colaborador: no se editan ni se eliminan (RN-027). */
    del_sistema: boolean;
    /** Nombres técnicos de los permisos marcados. */
    permisos: string[];
    /** Aclaraciones por permiso ("solo su empresa", "solo las vinculadas"). */
    notas_permisos: Record<string, string>;
    cuentas_total: number;
    /** Las primeras cuentas con este rol. */
    cuentas: CuentaDelRol[];
    /** Aviso sobre el rol inactivo ("Disponible en la próxima fase…"). */
    aviso: string | null;
};
