<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PresentationDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! config('siclin.demo')) {
            throw new \RuntimeException('Los recorridos requieren SICLIN_DEMO en local/testing.');
        }
        if (DB::table('settings')->where('key', 'demo_prepared')->exists()) {
            $this->command?->info('Los recorridos ya están preparados; se conservaron los avances.');

            return;
        }
        DB::transaction(function () {
            $cycle = DB::table('cycles')->where('name', 'like', '%Medicina Interna%')->first();
            if (! $cycle || $cycle->status !== 'abierta') {
                throw new \RuntimeException('Se requiere la convocatoria abierta inicial de demostración.');
            }
            $places = DB::table('places')->where('cycle_id', $cycle->id)->orderBy('id')->pluck('id')->all();
            foreach (['interno2@siclin.test', 'interno3@siclin.test'] as $index => $email) {
                $intern = User::where('email', $email)->firstOrFail();
                DB::table('applications')->insertOrIgnore([
                    'cycle_id' => $cycle->id, 'user_id' => $intern->id,
                    'preferences' => json_encode($index === 0 ? array_reverse($places) : $places),
                    'receipt' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('settings')->insert([
                'key' => 'demo_prepared',
                'value' => json_encode(['prepared_on' => now()->toDateString(), 'cycle_id' => $cycle->id]),
                'updated_at' => now(),
            ]);
            Audit::record('demo.recorridos_preparados', ['convocatoria' => $cycle->id, 'nota' => 'Dos postulaciones ficticias; Ana queda libre para postular durante la presentación.']);
        });
    }
}
