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
    | Contraseña de las cuentas de demostración (DemoSeeder). Sin valor, el
    | seeder genera una y la muestra en la terminal.
    */
    'demo' => [
        'password' => env('DEMO_PASSWORD'),
    ],

];
