<?php

namespace App\Notifications;

use App\Models\Medicion;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de aviso de una medición nueva (HU-036 CA-006, A3.1e) para el
 * usuario principal de la empresa.
 *
 * [FUNCIONALIDAD POR DEFINIR] Editar el asunto y el cuerpo antes de enviar y
 * la plantilla general (A3.1e, `plantillas_correo`) llegan con esa pantalla;
 * por ahora se usa este texto. Se envía al momento, como los demás correos;
 * pasar a la cola con el resto (RNF-004).
 */
class AvisoMedicion extends Notification
{
    public function __construct(public Medicion $medicion) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $usuario): MailMessage
    {
        $m = $this->medicion;
        $diagnostico = $m->version->diagnostico->nombre ?? 'Diagnóstico';

        $correo = (new MailMessage)
            ->subject("Tienes una nueva medición: {$diagnostico}")
            ->greeting("Hola, {$usuario->name}:")
            ->line("NuevasTIC te asignó la medición {$m->numero} con el {$diagnostico}.");

        if ($m->mensaje) {
            $correo->line('“'.$m->mensaje.'”');
        }

        if ($m->fecha_limite) {
            $correo->line('Fecha límite: '.$m->fecha_limite->format('d/m/Y').'.');
        }

        return $correo
            ->action('Ir a mi diagnóstico', url('/mi-inicio'))
            ->line('Puedes responder a tu ritmo: tus respuestas se guardan solas.')
            ->salutation('El equipo de NuevasTIC');
    }
}
