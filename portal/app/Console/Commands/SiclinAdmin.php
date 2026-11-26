<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiclinAdmin extends Command
{
    protected $signature = 'siclin:admin {email} {--name=Administrador SICLIN}';
    protected $description = 'Crear el primer administrador y guardar su clave en un archivo privado';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('role', 'administrador')->where('active', true)->exists() || User::where('email', $email)->exists()) {
            $this->error('Se requiere un correo válido y que no exista un administrador activo ni esa cuenta.');
            return self::FAILURE;
        }
        $password = Str::password(24);
        $user = new User;
        $user->forceFill(['name' => $this->option('name'), 'email' => $email, 'password' => $password, 'role' => 'administrador', 'active' => true, 'activated_at' => now()])->save();
        Storage::disk('local')->put('bootstrap-admin.json', json_encode(['email' => $email, 'password' => $password], JSON_PRETTY_PRINT));
        chmod(Storage::disk('local')->path('bootstrap-admin.json'), 0600);
        $this->info('Cuenta creada. Retira storage/app/private/bootstrap-admin.json y cambia su contraseña.');
        return self::SUCCESS;
    }
}
