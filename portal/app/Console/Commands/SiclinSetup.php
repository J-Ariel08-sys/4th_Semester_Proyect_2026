<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SiclinSetup extends Command
{
    protected $signature = 'siclin:setup {--demo : Cargar datos ficticios si la base está vacía}';

    protected $description = 'Preparar SICLIN sin eliminar datos existentes';

    public function handle(): int
    {
        if ($this->option('demo') && ! app()->environment(['local', 'testing'])) {
            $this->error('La demostración solo está permitida en local/testing.');

            return self::FAILURE;
        }
        if (! config('app.key')) {
            $this->error('Configura APP_KEY con scripts/init-env.py antes de iniciar.');
            return self::FAILURE;
        }
        if ($this->call('migrate', ['--force' => true]) !== 0) {
            return self::FAILURE;
        }
        if ($this->option('demo')) {
            $this->call('db:seed', ['--force' => true]);
        }
        $this->info('SICLIN preparado. Portal: '.config('app.url'));

        return self::SUCCESS;
    }
}
