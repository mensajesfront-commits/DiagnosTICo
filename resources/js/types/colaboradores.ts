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
    /** Área o cargo en la empresa ("Marketing"); null en cuentas antiguas. */
    cargo: string | null;
    /** Páginas que le dio la cuenta principal (claves de PaginaColaborador). */
    paginas: string[];
};

/**
 * Una página que la cuenta principal puede darle a un colaborador (E12.1).
 * La lista viene del servidor (App\Support\PaginasColaborador): si se agrega
 * una página allá, aparece sola aquí.
 */
export type PaginaColaborador = {
    clave: string;
    titulo: string;
    descripcion: string;
    /** Página que hay que marcar también (el PDF necesita los resultados). */
    necesita: string | null;
};

/** Lo que se muestra en E12.2 para compartir; no viene del servidor. */
export type DatosDeAcceso = {
    nombre: string;
    correo: string;
    contrasena: string;
};
