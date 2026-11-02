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

    /**
     * El código grande, en una caja destacada. Con tabla y estilos en línea
     * para que Gmail y Outlook lo muestren igual.
     */
    public static function caja(string $codigo): string
    {
        return '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:8px 0 22px">'
            .'<tr><td align="center" style="background-color:#e5eff9;border:2px dashed #9fb4d3;border-radius:10px;padding:18px 12px">'
            .'<div style="font-size:12px;color:#5f6470;letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">Tu código</div>'
            .'<div style="font-family:\'Courier New\',Courier,monospace;font-size:34px;font-weight:700;letter-spacing:10px;color:#14154d">'.e($codigo).'</div>'
            .'</td></tr></table>';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->codigo} es tu código de ".config('app.name'))
            ->greeting('Hola, '.($this->nombre !== '' ? $this->nombre : ($notifiable->name ?? '')).':')
            ->line($this->cambio
                ? 'Escribe este código en Mi perfil para confirmar que este es tu nuevo correo:'
                : 'Escribe este código en la ventana de verificación para activar la cuenta de tu empresa:')
            ->line(new HtmlString(self::caja($this->codigo)))
            ->line('Vence en '.CodigoVerificacion::MINUTOS.' minutos. Nunca te lo pediremos por teléfono ni por chat.')
            ->line($this->cambio
                ? 'Si no pediste el cambio, ignora este correo: tu cuenta sigue con el correo anterior.'
                : 'Si no te registraste, ignora este correo: la cuenta no se activará.')
            ->salutation('El equipo de NuevasTIC');
    }
}
