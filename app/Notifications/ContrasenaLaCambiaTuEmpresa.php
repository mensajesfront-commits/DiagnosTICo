<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * L3 · Un colaborador pidió "¿Olvidaste tu contraseña?". No recibe el enlace:
 * su contraseña la cambia la cuenta principal de su empresa (E12.3). La
 * pantalla muestra el mismo aviso de siempre (RN-005).
 */
class ContrasenaLaCambiaTuEmpresa extends Notification
{
    public function __construct(public ?string $empresa) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quien = $this->empresa ? "la cuenta principal de {$this->empresa}" : 'la cuenta principal de tu empresa';

        return (new MailMessage)
            ->subject('Tu contraseña de '.config('app.name'))
            ->greeting('Hola:')
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
            ->line("Tu cuenta es de colaborador: la contraseña te la cambia {$quien}. Pídesela a esa persona.")
            ->line('Si no pediste el cambio, ignora este correo: tu contraseña sigue igual.')
            ->salutation('El equipo de NuevasTIC');
    }
}
