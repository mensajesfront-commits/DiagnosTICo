<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de "Invitar usuario" (A5). Lleva un enlace para crear la contraseña
 * en la pantalla L4; sirve hasta que la cree (RN-006).
 */
class InvitacionCuenta extends Notification
{
    public function __construct(
        public string $token,
        public string $rol,
        public ?string $mensaje = null,
        public ?string $invitadaPor = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $usuario): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $usuario->getEmailForPasswordReset(),
        ], false));

        $correo = (new MailMessage)
            ->subject('Te invitaron a '.config('app.name'))
            ->greeting("Hola, {$usuario->name}:")
            ->line(($this->invitadaPor ? "{$this->invitadaPor} te invitó" : 'Te invitaron').' a '.config('app.name')." con el rol {$this->rol}.");

        if ($this->mensaje) {
            $correo->line('“'.$this->mensaje.'”');
        }

        return $correo
            ->action('Crear mi contraseña', $url)
            ->line('El enlace sirve hasta que crees tu contraseña.')
            ->salutation('El equipo de NuevasTIC');
    }
}
