export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    /** Rol de la cuenta (Administrador, Empresa o Colaborador); null si no tiene. */
    rol: string | null;
    /** Permisos de la cuenta, para ocultar en el menú lo que no puede abrir. */
    permisos: string[];
};

/** A5.4 · "Ver como" activo (RN-026): para la franja de aviso. */
export type VerComo = {
    /** Cuenta que se está viendo. */
    cuenta: string;
    rol: string | null;
    empresa: string | null;
    /** Quién la está viendo. */
    administrador: string;
};
