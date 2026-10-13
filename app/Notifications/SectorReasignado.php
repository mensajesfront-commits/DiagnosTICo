<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a la empresa cuando el Administrador la pasa a otro sector (A2.2b,
 * casilla "Avisar a las empresas").
 */
class SectorReasignado extends Notification
{
    use Queueable;

    public function __construct(private readonly string $anterior, private readonly string $nuevo) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu empresa cambió de sector en '.config('app.name'))
            ->line("NuevasTIC movió tu empresa del sector «{$this->anterior}» al sector «{$this->nuevo}».")
            ->line('Tus mediciones y resultados se conservan. Los próximos diagnósticos serán los de tu nuevo sector.')
            ->action('Entrar a '.config('app.name'), url('/'))
            ->salutation('El equipo de NuevasTIC');
    }
}
