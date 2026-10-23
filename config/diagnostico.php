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

    /*
    | Días que espera un registro sin verificar el correo (L2b) antes de que
    | `cuentas:purgar` lo borre, con su empresa. Así el correo queda libre.
    */
    'sin_verificar' => [
        'dias' => (int) env('DIAS_REGISTRO_SIN_VERIFICAR', 7),
    ],

    /*
    | Correo de contacto que ve la empresa (E1, medición vencida).
    | [INFORMACIÓN PENDIENTE] NuevasTIC confirma la dirección real.
    */
    'soporte_email' => env('SOPORTE_EMAIL', 'soporte@nuevastic.co'),

    /*
    | Fecha límite de las mediciones (App\Support\Plazos): mínimo de días
    | para responder al asignar o cambiar la fecha, y la zona horaria que se
    | usa si la empresa no tiene cuenta principal. Cada empresa cuenta el día
    | en la zona de su Mi perfil.
    */
    'plazo_minimo_dias' => (int) env('PLAZO_MINIMO_DIAS', 3),
    'zona_horaria' => env('ZONA_HORARIA_POR_DEFECTO', 'America/Bogota'),

    /*
    | Análisis de la IA sin conexión (App\Services\Ia\AnalizadorDePrueba):
    | puntajes y textos de prueba, sin gastar llamadas a OpenAI. Solo para
    | programar y para las pruebas automáticas; en producción va en false.
    */
    'ia_de_prueba' => (bool) env('IA_DE_PRUEBA', false),

];
