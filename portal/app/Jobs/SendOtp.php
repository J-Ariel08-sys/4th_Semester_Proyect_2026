<?php

namespace App\Jobs;

use App\Services\MailDelivery;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendOtp implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 25;
    public array $backoff = [10, 30];

    public function __construct(public string $challengeId, public string $code, public string $mode) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $row = DB::table('otp_challenges')->where('id', $this->challengeId)->lockForUpdate()->first();
            if (! $row || ! $row->user_id || $row->revoked_at || $row->verified_at || $row->consumed_at || $row->sent_at || Carbon::parse($row->expires_at)->lte(now())) {
                return;
            }
            $user = DB::table('users')->where('id', $row->user_id)->first();
            if (! $user?->active || ! MailDelivery::allowed($user->email, $this->mode)) {
                return;
            }
            try {
                Mail::send('mail.otp', ['code' => $this->code, 'purpose' => $row->purpose, 'minutes' => max(1, (int) floor(now()->diffInSeconds(Carbon::parse($row->expires_at)) / 60))], function ($message) use ($user, $row) {
                    $message->to($user->email)->subject('SICLIN · Código de '.($row->purpose === 'activation' ? 'activación' : 'recuperación'));
                });
            } catch (\Throwable) {
                throw new \RuntimeException('No se pudo entregar el correo al transporte configurado.');
            }
            DB::table('otp_challenges')->where('id', $row->id)->update(['sent_at' => now()]);
        }, 3);
    }
}
