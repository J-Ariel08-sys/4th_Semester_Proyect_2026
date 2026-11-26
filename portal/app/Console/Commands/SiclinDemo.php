<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\ClinicalDemoSeeder;
use Database\Seeders\ExpandedDemoSeeder;
use Database\Seeders\PresentationDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SiclinDemo extends Command
{
    private const DEMO_PASSWORD = 'SiclinDemo2026!';

    private const DEMO_ACCOUNTS = [
        ['admin@siclin.test', 'administrador'],
        ['coordinador@siclin.test', 'coordinador'],
        ['tutor@siclin.test', 'tutor'],
        ['tutor2@siclin.test', 'tutor'],
        ['interno@siclin.test', 'interno'],
        ['interno2@siclin.test', 'interno'],
        ['interno3@siclin.test', 'interno'],
        ['interno.clinico@siclin.test', 'interno'],
    ];

    protected $signature = 'siclin:demo {--reset : Reiniciar exclusivamente la base aislada de demostración}';

    protected $description = 'Preparar los recorridos de los cuatro roles en la instancia siclin-demo';

    public function handle(): int
    {
        if (! app()->environment('local') || ! config('siclin.demo')
            || config('database.default') !== 'pgsql'
            || config('database.connections.pgsql.database') !== 'siclin_demo'
            || config('database.connections.pgsql.username') !== 'siclin_demo') {
            $this->error('Solo se permite en la instancia local SICLIN_DEMO con base y usuario siclin_demo.');

            return self::FAILURE;
        }

        if ($this->option('reset') && $this->call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        if ($this->call('siclin:setup', ['--demo' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        foreach ([ClinicalDemoSeeder::class, PresentationDemoSeeder::class] as $seeder) {
            if ($this->call('db:seed', ['--class' => $seeder, '--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        if (! $this->refreshDemoCredentials()) {
            return self::FAILURE;
        }
        if ($this->call('db:seed', ['--class' => ExpandedDemoSeeder::class, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        $this->info('Demo preparada. Consulta /ayuda con cada rol; claves en el archivo privado demo-accounts.json.');

        return self::SUCCESS;
    }

    private function refreshDemoCredentials(): bool
    {
        $accounts = [];
        foreach (self::DEMO_ACCOUNTS as [$email, $role]) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("No se encontró la cuenta demo {$email}; reinicia la demo para reconstruir los datos iniciales.");

                return false;
            }
            $user->forceFill([
                'password' => self::DEMO_PASSWORD,
                'activated_at' => $user->activated_at ?? now(),
                'role' => $role,
                'active' => true,
            ])->save();
            $accounts[] = ['email' => $email, 'password' => self::DEMO_PASSWORD, 'role' => $role];
        }
        $disk = Storage::disk('local');
        $disk->put('demo-accounts.json', json_encode($accounts, JSON_PRETTY_PRINT));
        chmod($disk->path('demo-accounts.json'), 0600);

        return true;
    }
}
