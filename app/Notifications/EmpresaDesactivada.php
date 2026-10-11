<?php

namespace App\Notifications;

use App\Models\Empresa;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso opcional al desactivar una empresa (A3.1f, HU-035).
 */
class EmpresaDesactivada extends Notification
{
    public function __construct(public Empresa $empresa, public ?string $motivo = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $correo = (new MailMessage)
            ->subject('Tu cuenta en '.config('app.name').' fue desactivada')
            ->line("La cuenta de {$this->empresa->nombre} fue desactivada: tú y tus colaboradores no podrán entrar por ahora.");

        if ($this->motivo) {
            $correo->line('Motivo: '.$this->motivo);
        }

        return $correo
            ->line('Tus mediciones y resultados se conservan. Escribe a soporte@nuevastic.co si tienes preguntas.')
            ->salutation('El equipo de NuevasTIC');
    }
}
