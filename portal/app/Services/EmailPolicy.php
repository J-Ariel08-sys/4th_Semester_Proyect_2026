<?php
namespace App\Services;

use Illuminate\Validation\ValidationException;

class EmailPolicy
{
    public static function validate(string $email): void
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        $allowed = config('siclin.allowed_domains');
        if ($allowed && ! in_array($domain, $allowed, true) && ! (app()->environment(['local', 'testing']) && str_ends_with($domain, '.test'))) {
            throw ValidationException::withMessages(['email' => 'El dominio no está permitido por la configuración institucional.']);
        }
    }
}
