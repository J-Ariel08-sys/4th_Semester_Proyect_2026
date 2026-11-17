<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Solo se permite en local/testing.');
        }
        if (DB::table('cycles')->where('name', 'DEMO · Seguimiento clínico en curso')->exists()) {
            $this->command?->info('El ejemplo clínico ya existe.');

            return;
        }
        $coordinator = User::where('email', 'coordinador@siclin.test')->first();
        $tutor = User::where('email', 'tutor@siclin.test')->first();
        if (! $coordinator || ! $tutor) {
            throw new \RuntimeException('Ejecuta primero siclin:setup --demo.');
        }
        Auth::setUser($coordinator);
        DB::transaction(function () use ($coordinator, $tutor) {
            if (User::where('email','interno.clinico@siclin.test')->exists()) { throw new \RuntimeException('La cuenta clínica ya existe; no se reemplazó.'); }
            $plainPassword=\Illuminate\Support\Str::password(24);
            $user = new User;
            $user->forceFill(['name' => 'Camila Clínica Demo', 'email' => 'interno.clinico@siclin.test', 'password' => $plainPassword, 'activated_at' => now(), 'role' => 'interno', 'eligible' => true, 'active' => true, 'academic_average' => 85, 'merit' => 85, 'matricula' => 'DEMO-CLINICA'])->save();
            $cycle = DB::table('cycles')->insertGetId(['name' => 'DEMO · Seguimiento clínico en curso', 'specialty_id' => DB::table('specialties')->where('name', 'Cirugía')->value('id'), 'starts_on' => now()->subDays(3)->toDateString(), 'ends_on' => now()->addDays(2)->toDateString(), 'opens_at' => now()->subDays(14), 'closes_at' => now()->subDays(8), 'status' => 'publicada', 'created_at' => now(), 'updated_at' => now()]);
            $place = DB::table('places')->insertGetId(['cycle_id' => $cycle, 'hospital_id' => DB::table('hospitals')->orderBy('id')->value('id'), 'tutor_id' => $tutor->id, 'capacity' => 2, 'reserve' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $input = ['plazas' => [['id' => (string) $place, 'capacidad' => 2, 'reserva' => 1]], 'internos' => [['id' => (string) $user->id, 'habilitado' => true, 'merito' => 85, 'preferencias' => [(string) $place], 'plazaAnterior' => null]], 'politica' => DecisionAnalysis::POLICIES['A3'], 'segundos' => 1];
            $service = app(RotationService::class);
            $result = $service->evaluate($input);
            if (count($result['asignaciones']) !== 1) {
                throw new \RuntimeException('No se pudo calcular la asignación de demostración.');
            }
            $run = DB::table('optimization_runs')->insertGetId(['cycle_id' => $cycle, 'created_by' => $coordinator->id, 'input' => json_encode($input), 'result' => json_encode($result), 'input_hash' => $service->hash($input), 'status' => 'aprobada', 'plan_status' => 'publicado', 'created_at' => now(), 'updated_at' => now()]);
            $assignment = DB::table('assignments')->insertGetId(['cycle_id' => $cycle, 'user_id' => $user->id, 'place_id' => $place, 'tutor_id' => $tutor->id, 'run_id' => $run, 'status' => 'aprobada', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('availabilities')->insert(['tutor_id' => $tutor->id, 'starts_at' => now()->subDays(3)->startOfDay(), 'ends_at' => now()->addDays(2)->endOfDay(), 'created_at' => now(), 'updated_at' => now()]);
            $batch = DB::table('shift_batches')->insertGetId(['name' => 'DEMO · Guardia finalizada para registrar', 'rest_hours' => 12, 'status' => 'aprobada', 'created_by' => $coordinator->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('shifts')->insert(['batch_id' => $batch, 'assignment_id' => $assignment, 'tutor_id' => $tutor->id, 'starts_at' => now()->subHours(10), 'ends_at' => now()->subHours(4), 'performed' => false, 'created_at' => now(), 'updated_at' => now()]);
            $disk=\Illuminate\Support\Facades\Storage::disk('local');
            $accounts=json_decode($disk->get('demo-accounts.json') ?? '[]',true);
            $accounts[]=['email'=>$user->email,'password'=>$plainPassword,'role'=>'interno'];
            $disk->put('demo-accounts.json',json_encode($accounts,JSON_PRETTY_PRINT));
            chmod($disk->path('demo-accounts.json'),0600);
            Audit::record('demo.clinica_cargada', ['convocatoria' => $cycle, 'nota' => 'Datos ficticios retrospectivos para pruebas; no son registros institucionales.']);
        });
        $this->command?->info('Ejemplo clínico creado. Interno: interno.clinico@siclin.test; tutor: tutor@siclin.test; clave privada en storage/app/private/demo-accounts.json');
    }
}
