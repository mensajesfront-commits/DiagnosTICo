/**
 * Datos de "Colaboradores" (E12–E12.3) que entrega el backend a la cuenta
 * principal de la empresa. Contrato documentado en docs/14_FRONTEND.md.
 */

export type Colaborador = {
    id: number;
    nombre: string;
    correo: string;
    /** La cuenta principal (rol Empresa): va primero y no tiene acciones. */
    es_principal: boolean;
    activo: boolean;
    /** Fecha ISO del último inicio de sesión; null si nunca entró. */
    ultimo_acceso: string | null;
};

/** Lo que se muestra en E12.2 para compartir; no viene del servidor. */
export type DatosDeAcceso = {
    nombre: string;
    correo: string;
    contrasena: string;
};
