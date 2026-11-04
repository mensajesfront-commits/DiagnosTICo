<?php

return [

    /*
    | Cuenta principal del Administrador que crea `php artisan db:seed`
    | (T-046). Se crea sin contraseña: le llega el correo para crearla
    | (RN-006). Se reenvía con `php artisan admin:invitar`.
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@nuevastic.co'),
    ],

    /*
    | Días que una cuenta eliminada se puede recuperar antes de que la tarea
    | diaria `cuentas:purgar` la borre para siempre (DEC-017).
    */
    'eliminacion' => [
        'dias' => (int) env('DIAS_PARA_RECUPERAR_CUENTA', 90),
    ],

    /*
    | Días que espera un registro sin verificar el correo (L2b) antes de que
    | `cuentas:purgar` lo borre, con su empresa. Así el correo queda libre.
    */
    'sin_verificar' => [
        'dias' => (int) env('DIAS_REGISTRO_SIN_VERIFICAR', 7),
    ],

    /*
    | Correo de soporte que ven todas las pantallas, el PDF y los correos.
    | Oficial desde el 4 de noviembre de 2026: contacto@captter.com.
    */
    'soporte_email' => env('SOPORTE_EMAIL', 'contacto@captter.com'),

    /*
    | Fecha límite de las mediciones (App\Support\Plazos): mínimo de días
    | para responder al asignar o cambiar la fecha, y la zona horaria que se
    | usa si la empresa no tiene cuenta principal. Cada empresa cuenta el día
    | en la zona de su Mi perfil.
    */
    'plazo_minimo_dias' => (int) env('PLAZO_MINIMO_DIAS', 3),
    'zona_horaria' => env('ZONA_HORARIA_POR_DEFECTO', 'America/Bogota'),

];
