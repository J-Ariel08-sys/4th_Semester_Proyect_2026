<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DecisionAnalysis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Los datos de demostración solo se cargan en local o testing.');
        }
        if (DB::table('users')->exists()) {
            $this->command?->warn('La base ya contiene usuarios. No se reemplazaron datos.');

            return;
        }
        DB::transaction(function () {
            $password = Hash::make('SiclinDemo2026!');
            $users = [];
            foreach ([['Admin Demo', 'admin', 'administrador'], ['Coordinación Demo', 'coordinador', 'coordinador'], ['Dra. Lucía Demo', 'tutor', 'tutor'], ['Dr. Mateo Demo', 'tutor2', 'tutor'], ['Ana Demo', 'interno', 'interno'], ['Diego Demo', 'interno2', 'interno'], ['Valeria Demo', 'interno3', 'interno']] as $i => $row) {
                $user = new User;
                $user->forceFill(['name' => $row[0], 'email' => $row[1].'@siclin.test', 'password' => $password, 'role' => $row[2], 'active' => true, 'eligible' => $row[2] === 'interno', 'academic_average' => 90 - $i, 'merit' => 90 - $i, 'matricula' => $row[2] === 'interno' ? 'DEMO-'.$i : null])->save();
                $users[$row[1]] = $user->id;
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
}
