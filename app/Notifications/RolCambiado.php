<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso por correo del cambio de rol (A5.5, "Avisarle por correo").
 */
class RolCambiado extends Notification
{
    public function __construct(public string $rolNuevo) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu rol en '.config('app.name').' cambió')
            ->greeting('Hola:')
            ->line("Ahora tu cuenta tiene el rol {$this->rolNuevo}.")
            ->line('Lo que ves en el sistema puede cambiar desde tu próximo inicio de sesión.')
            ->action('Entrar a '.config('app.name'), url('/login'))
            ->salutation('El equipo de NuevasTIC');
    }
}
