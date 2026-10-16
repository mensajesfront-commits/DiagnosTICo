<?php

namespace App\Notifications;

use App\Services\Acceso\CodigoVerificacion;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * L2b · El código de 6 dígitos para verificar el correo (DEC-020). Sin
 * enlaces: la persona lo escribe en la ventana del registro.
 */
class CodigoVerificacionCorreo extends Notification
{
    /** `cambio`: confirma el correo nuevo de Mi perfil, no un registro. */
    public function __construct(public string $codigo, public string $nombre = '', public bool $cambio = false) {}

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
            ->subject("{$this->codigo} es tu código de ".config('app.name'))
            ->greeting('Hola, '.($this->nombre !== '' ? $this->nombre : ($notifiable->name ?? '')).':')
            ->line($this->cambio
                ? 'Escribe este código en Mi perfil para confirmar que este es tu nuevo correo:'
                : 'Escribe este código en la ventana de verificación para activar la cuenta de tu empresa:')
            ->line(new HtmlString(
                '<p style="font-size:30px;font-weight:700;letter-spacing:8px;text-align:center;margin:18px 0;font-family:monospace">'.e($this->codigo).'</p>'
            ))
            ->line('Vence en '.CodigoVerificacion::MINUTOS.' minutos. Nunca te lo pediremos por teléfono ni por chat.')
            ->line($this->cambio
                ? 'Si no pediste el cambio, ignora este correo: tu cuenta sigue con el correo anterior.'
                : 'Si no te registraste, ignora este correo: la cuenta no se activará.')
            ->salutation('El equipo de NuevasTIC');
    }
}
