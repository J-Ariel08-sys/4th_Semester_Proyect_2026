<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentPublished extends Notification
{
    public function __construct(public string $cycle) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('SICLIN · Asignación de rote publicada')->greeting('Hola, '.$notifiable->name)->line('Coordinación publicó tu asignación en '.$this->cycle.'.')->action('Consultar mi libreta', route('reports'))->line('Ingresa con tu cuenta para consultar el hospital y las fechas.');
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Asignación publicada', 'message' => 'Revisa tu rote en '.$this->cycle.'.', 'url' => route('reports')];
    }
}
