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

];
