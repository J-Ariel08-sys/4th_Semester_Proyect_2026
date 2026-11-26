<?php

namespace App\Jobs;

use App\Services\MailDelivery;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendSecurityNotice implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public function __construct(public int $userId, public string $mode) {}
    public function handle(): void
    {
        $user = DB::table('users')->find($this->userId);
        if (! $user || ! MailDelivery::allowed($user->email, $this->mode)) {
            return;
        }
        try {
            Mail::send('mail.changed', [], fn ($message) => $message->to($user->email)->subject('SICLIN · Contraseña actualizada'));
        } catch (\Throwable) {
            throw new \RuntimeException('No se pudo entregar la notificación de seguridad.');
        }
    }
}
