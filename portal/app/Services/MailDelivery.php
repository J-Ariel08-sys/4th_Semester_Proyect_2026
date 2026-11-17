<?php

namespace App\Services;

class MailDelivery
{
    public static function allowed(string $email, string $mode): bool
    {
        if ($mode !== config('siclin.mail_mode') || ! in_array(config('mail.default'), ['smtp', 'array'], true)) {
            return false;
        }
        if ($mode === 'local') {
            return app()->environment(['local', 'testing']) && (config('mail.default') === 'array' || config('mail.mailers.smtp.host') === 'mailpit');
        }
        return $mode === 'external' && (app()->environment('production') || in_array(strtolower($email), config('siclin.mail_recipients'), true));
    }
}
