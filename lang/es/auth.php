<?php

return [
    // RN-005: el mismo mensaje para correo inexistente, contraseña equivocada
    // o cuenta desactivada.
    'failed' => 'El correo o la contraseña no son correctos.',
    'password' => 'La contraseña no es correcta.',
    'throttle' => 'Demasiados intentos. Vuelve a intentarlo en :seconds segundos.',
    // Solo se muestra si el correo y la contraseña son correctos (L1, modal).
    'eliminada' => 'Su cuenta fue eliminada. Puede recuperarla hasta el :fecha escribiendo a Captter; después se borra para siempre.',
    'desactivada' => 'Su cuenta ha sido desactivada. Diríjase a Captter para saber más detalles.',
    // La cuenta está activa, pero su rol no (A5.1c). Antes decía "cuenta desactivada" (ERR-12).
    'rol_inactivo' => 'Su cuenta está activa, pero su rol en Captter está inactivo por ahora. Diríjase a Captter para que lo activen o le asignen otro.',
];
