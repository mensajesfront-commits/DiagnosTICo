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
