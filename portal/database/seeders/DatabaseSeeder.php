<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DecisionAnalysis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'SiclinDemo2026!';

    private const DEMO_USERS = [
        ['Admin Demo', 'admin', 'administrador'],
        ['Coordinación Demo', 'coordinador', 'coordinador'],
        ['Dra. Lucía Demo', 'tutor', 'tutor'],
        ['Dr. Mateo Demo', 'tutor2', 'tutor'],
        ['Ana Demo', 'interno', 'interno'],
        ['Diego Demo', 'interno2', 'interno'],
        ['Valeria Demo', 'interno3', 'interno'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Los datos de demostración solo se cargan en local o testing.');
        }
        if (DB::table('users')->exists()) {
            $accounts = [];
            foreach (self::DEMO_USERS as $row) {
                $email = $row[1].'@siclin.test';
                $user = User::where('email', $email)->first();
                if (! $user) {
                    continue;
                }
                $user->forceFill([
                    'password' => self::DEMO_PASSWORD,
                    'activated_at' => $user->activated_at ?? now(),
                    'role' => $row[2],
                    'active' => true,
                ])->save();
                $accounts[] = ['email' => $email, 'password' => self::DEMO_PASSWORD, 'role' => $row[2]];
            }
            if ($accounts !== [] && ! app()->environment('testing')) {
                $this->writeDemoAccounts($accounts);
            }
            $this->command?->warn('Se restauraron las credenciales de las cuentas demo existentes; los demás datos se conservaron.');

            return;
        }
        DB::transaction(function () {
            $accounts = [];
            $users = [];
            foreach (self::DEMO_USERS as $i => $row) {
                $plainPassword = self::DEMO_PASSWORD;
                $accounts[] = ['email' => $row[1].'@siclin.test', 'password' => $plainPassword, 'role' => $row[2]];
                $user = new User;
                $user->forceFill(['name' => $row[0], 'email' => $row[1].'@siclin.test', 'password' => $plainPassword, 'activated_at' => now(), 'role' => $row[2], 'active' => true, 'eligible' => $row[2] === 'interno', 'academic_average' => 90 - $i, 'merit' => 90 - $i, 'matricula' => $row[2] === 'interno' ? 'DEMO-'.$i : null])->save();
                $users[$row[1]] = $user->id;
            }
            if (! app()->environment('testing')) {
                $this->writeDemoAccounts($accounts);
            }
            foreach (['Medicina Interna', 'Cirugía', 'Pediatría', 'Ginecología y Obstetricia', 'Salud Pública'] as $name) {
                DB::table('specialties')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
            $hospitals = [];
            foreach (['Hospital de Enseñanza A (ficticio)', 'Hospital de Enseñanza B (ficticio)'] as $name) {
                $hospitals[] = DB::table('hospitals')->insertGetId(['name' => $name, 'agreement_until' => now()->addYear()->toDateString(), 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            $cycle = DB::table('cycles')->insertGetId(['name' => 'Convocatoria de demostración · Medicina Interna', 'specialty_id' => DB::table('specialties')->value('id'), 'starts_on' => now()->addDays(7)->toDateString(), 'ends_on' => now()->addDays(37)->toDateString(), 'opens_at' => now()->subDay(), 'closes_at' => now()->addDays(5), 'status' => 'abierta', 'created_at' => now(), 'updated_at' => now()]);
            foreach ($hospitals as $i => $hospital) {
                DB::table('places')->insert(['cycle_id' => $cycle, 'hospital_id' => $hospital, 'tutor_id' => $users[$i ? 'tutor2' : 'tutor'], 'capacity' => 3, 'reserve' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            $matrix = ['A1' => [90, 60, 20], 'A2' => [75, 65, 40], 'A3' => [80, 70, 60]];
            DB::table('scenario_studies')->insert(['name' => 'Ejemplo didáctico · valores hipotéticos', 'matrix' => json_encode($matrix), 'alpha' => 0.5, 'result' => json_encode((new DecisionAnalysis)->calculate($matrix, 0.5)), 'created_by' => $users['coordinador'], 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    private function writeDemoAccounts(array $accounts): void
    {
        $disk = Storage::disk('local');
        $disk->put('demo-accounts.json', json_encode($accounts, JSON_PRETTY_PRINT));
        chmod($disk->path('demo-accounts.json'), 0600);
    }
}
