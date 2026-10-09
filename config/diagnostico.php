<?php

return [

    /*
    | Cuenta inicial del Administrador que crea `php artisan db:seed` (T-046).
    | Sin contraseña, el seeder genera una y la muestra en la terminal.
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@nuevastic.co'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    | Días que una cuenta eliminada se puede recuperar antes de que la tarea
    | diaria `cuentas:purgar` la borre para siempre (DEC-017).
    */
    'eliminacion' => [
        'dias' => (int) env('DIAS_PARA_RECUPERAR_CUENTA', 90),
    ],

];
