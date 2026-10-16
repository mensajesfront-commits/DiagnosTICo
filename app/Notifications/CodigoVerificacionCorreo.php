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
    /**
     * `motivo`: "registro" (L2b), "correo" (correo nuevo en Mi perfil) o
     * "contrasena" (olvidó la contraseña en Mi perfil).
     */
    public function __construct(public string $codigo, public string $nombre = '', public string $motivo = 'registro') {}

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
            ->line(match ($this->motivo) {
                'correo' => 'Escribe este código en Mi perfil para confirmar que este es tu nuevo correo:',
                'contrasena' => 'Escribe este código en Mi perfil para crear una contraseña nueva:',
                default => 'Escribe este código en la ventana de verificación para activar la cuenta de tu empresa:',
            })
            ->line(new HtmlString(
                '<p style="font-size:30px;font-weight:700;letter-spacing:8px;text-align:center;margin:18px 0;font-family:monospace">'.e($this->codigo).'</p>'
            ))
            ->line('Vence en '.CodigoVerificacion::MINUTOS.' minutos. Nunca te lo pediremos por teléfono ni por chat.')
            ->line(match ($this->motivo) {
                'correo' => 'Si no pediste el cambio, ignora este correo: tu cuenta sigue con el correo anterior.',
                'contrasena' => 'Si no lo pediste, ignora este correo y cambia tu contraseña: alguien con tu sesión abierta lo intentó.',
                default => 'Si no te registraste, ignora este correo: la cuenta no se activará.',
            })
            ->salutation('El equipo de NuevasTIC');
    }
}
