<?php

namespace Database\Seeders;

use App\Http\Controllers\ClinicalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SwapController;
use App\Jobs\ComputeScenario;
use App\Jobs\ComputeShifts;
use App\Jobs\OptimizeRotations;
use App\Models\User;
use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use App\Services\ScenarioService;
use App\Services\ShiftPlanner;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpandedDemoSeeder extends Seeder
{
    public const MARKER = 'demo_expanded_v1';

    /** @return array<string, array{name: string, role: string}> */
    public static function accounts(): array
    {
        $accounts = [];
        $surnames = ['Paredes', 'Mendoza', 'Quiroga', 'Morales', 'Suárez', 'Salazar', 'Vargas', 'Ramos', 'Guzmán', 'Castro', 'Herrera', 'Cabrera', 'Navarro', 'Delgado', 'Ortiz', 'Cruz'];
        foreach (['Sofía', 'Gabriel', 'Mariana', 'Andrés', 'Paula', 'Nicolás', 'Daniela', 'Sebastián', 'Fernanda', 'Joaquín', 'Isabel', 'Tomás', 'Renata', 'Emilio', 'Victoria', 'Samuel'] as $index => $name) {
            $accounts[sprintf('interno.demo%02d@siclin.test', $index + 1)] = ['name' => $name.' '.$surnames[$index], 'role' => 'interno'];
        }
        $accounts['interno.pendiente@siclin.test'] = ['name' => 'Alex Pendiente', 'role' => 'interno'];
        $tutorSurnames = ['Montaño', 'Peñaranda', 'Gutiérrez'];
        foreach (['Dra. Elena', 'Dr. Rafael', 'Dra. Inés'] as $index => $name) {
            $accounts['tutor.demo'.($index + 1).'@siclin.test'] = ['name' => $name.' '.$tutorSurnames[$index], 'role' => 'tutor'];
        }

        return $accounts;
    }

    public function run(): void
    {
        if (! config('siclin.demo') || ! app()->environment(['local', 'testing'])
            || (app()->environment('local') && (config('database.default') !== 'pgsql'
                || config('database.connections.pgsql.database') !== 'siclin_demo'
                || config('database.connections.pgsql.username') !== 'siclin_demo'))) {
            throw new \RuntimeException('La ampliación requiere la instancia aislada de demostración.');
        }
        $previousUser = Auth::user();
        try {
            DB::transaction(function () {
                $coordinator = User::where('email', 'coordinador@siclin.test')->lockForUpdate()->firstOrFail();
                if (DB::table('settings')->where('key', self::MARKER)->exists()) {
                    $this->command?->info('La ampliación ya está cargada; se conservaron sus avances.');

                    return;
                }
                Auth::setUser($coordinator);
                $this->populate($coordinator);
            });
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetUser();
            }
        }
        $disk = Storage::disk('local');
        $accounts = collect(json_decode($disk->exists('demo-accounts.json') ? $disk->get('demo-accounts.json') : '[]', true))->keyBy('email');
        foreach (self::accounts() as $email => $data) {
            if (! $accounts->has($email)) {
                $accounts->put($email, ['email' => $email, 'password' => 'SiclinDemo2026!', 'role' => $data['role']]);
            }
        }
        $disk->put('demo-accounts.json', json_encode($accounts->values()->all(), JSON_PRETTY_PRINT));
        chmod($disk->path('demo-accounts.json'), 0600);
    }

    private function insert(string $table, array $data): int
    {
        return DB::table($table)->insertGetId([...$data, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function populate(User $coordinator): void
    {
        $cohorts = [];
        foreach (['Cohorte A · Trayectoria clínica', 'Cohorte B · Planificación'] as $name) {
            $cohorts[] = $this->insert('cohorts', ['name' => $name, 'entry_year' => now()->year, 'active' => true]);
        }
        $specialties = DB::table('specialties')->pluck('id', 'name');
        foreach ($cohorts as $cohort) {
            foreach ($specialties as $specialty) {
                $this->insert('academic_requirements', ['cohort_id' => $cohort, 'specialty_id' => $specialty, 'min_days' => 21, 'required_blocks' => 2]);
            }
        }
        $interns = [];
        $newTutors = [];
        foreach (self::accounts() as $email => $data) {
            if (User::where('email', $email)->exists()) {
                throw new \RuntimeException('Ya existe una cuenta reservada para la ampliación: '.$email.'. No se reemplazó.');
            }
            $isIntern = $data['role'] === 'interno';
            $isPending = $email === 'interno.pendiente@siclin.test';
            $index = count($interns);
            $user = new User;
            $user->forceFill([
                ...$data, 'email' => $email, 'password' => 'SiclinDemo2026!', 'activated_at' => now(), 'access_approved_at' => $isPending ? null : now(), 'active' => true,
                'eligible' => $isIntern && ! $isPending, 'academic_average' => $isIntern ? 95 - $index : 0,
                'merit' => $isIntern ? 90 - $index : 0, 'supervision_limit' => 8,
                'cohort_id' => $isIntern && ! $isPending ? $cohorts[$index < 12 ? 0 : 1] : null,
                'matricula' => $isIntern ? sprintf('INT-LP-%02d', $index + 1) : null,
            ])->save();
            if ($isIntern && ! $isPending) {
                $interns[] = $user;
            } elseif (! $isIntern) {
                $newTutors[] = $user;
            }
        }
        $prepared = json_decode(DB::table('settings')->where('key', 'demo_prepared')->value('value') ?? '{}', true);
        $hospitals = DB::table('places')->where('cycle_id', $prepared['cycle_id'] ?? null)->orderBy('id')->pluck('hospital_id')->unique()->values()->all();
        if (count($hospitals) !== 2) {
            throw new \RuntimeException('Se requieren las dos sedes de la convocatoria inicial para ampliar la demo.');
        }
        foreach (['Centro Clínico C', 'Hospital Docente D', 'Centro E · convenio vencido'] as $index => $name) {
            $hospitals[] = $this->insert('hospitals', [
                'name' => $name, 'active' => $index !== 2,
                'agreement_until' => ($index === 2 ? now()->subMonth() : now()->addYear())->toDateString(),
            ]);
        }
        $tutors = [User::where('email', 'tutor@siclin.test')->firstOrFail(), User::where('email', 'tutor2@siclin.test')->firstOrFail(), ...$newTutors];
        $group = array_slice($interns, 0, 12);
        $reserveGroup = array_slice($interns, 12);
        $cycles = [];
        $cycles['historico'] = $this->cycle('Archivo · Salud Pública', $specialties['Salud Pública'], $cohorts[0], -60, -31, 'cerrada');
        $cycles['seguimiento'] = $this->cycle('Seguimiento activo · Cirugía', $specialties['Cirugía'], $cohorts[0], -7, 14, 'cerrada');
        $cycles['publicado'] = $this->cycle('Rotes publicados · Ginecología', $specialties['Ginecología y Obstetricia'], $cohorts[0], 20, 40, 'cerrada');
        $cycles['oferta'] = $this->cycle('Oferta abierta · Pediatría', $specialties['Pediatría'], null, 45, 74, 'abierta');
        $cycles['revision'] = $this->cycle('Propuesta para revisar · Medicina Interna', $specialties['Medicina Interna'], $cohorts[0], 80, 109, 'cerrada');
        $cycles['escasez'] = $this->cycle('Cupos insuficientes · Salud Pública', $specialties['Salud Pública'], $cohorts[1], 115, 144, 'cerrada');
        $cycles['borrador'] = $this->cycle('Preparación · Cirugía', $specialties['Cirugía'], $cohorts[1], 150, 179, 'borrador');
        $places = [];
        foreach ($cycles as $key => $cycle) {
            $capacities = $key === 'escasez' ? [2, 2] : [7, 5, 4];
            $cycleTutors = $key === 'publicado' ? [$newTutors[1], $newTutors[2], $newTutors[0]] : $tutors;
            $places[$key] = [];
            foreach ($capacities as $index => $capacity) {
                $places[$key][] = $this->insert('places', ['cycle_id' => $cycle, 'hospital_id' => $hospitals[$index], 'tutor_id' => $cycleTutors[$index]->id, 'capacity' => $capacity, 'reserve' => 1]);
            }
            $applicants = match ($key) {
                'oferta' => array_slice($group, 0, 6), 'escasez' => $reserveGroup, 'borrador' => [], default => $group,
            };
            foreach ($applicants as $index => $intern) {
                $preferences = $places[$key];
                $preferred = $index % count($preferences);
                $preferences = [...array_slice($preferences, $preferred), ...array_slice($preferences, 0, $preferred)];
                $this->insert('applications', ['cycle_id' => $cycle, 'user_id' => $intern->id, 'preferences' => json_encode($preferences), 'receipt' => (string) Str::uuid()]);
            }
        }
        $runs = [];
        foreach (['historico', 'seguimiento', 'publicado', 'revision', 'escasez'] as $key) {
            $runs[$key] = $this->rotation($cycles[$key], $coordinator->id, $key === 'revision' ? 'A3' : 'A1');
            if (in_array($key, ['historico', 'seguimiento'], true)) {
                $this->retrospectivePublication($cycles[$key], $runs[$key]);
            } elseif ($key === 'publicado') {
                DB::table('optimization_runs')->where('id', $runs[$key])->update(['plan_status' => 'en_revision']);
                app(RotationService::class)->approve($runs[$key], false);
                app(RotationService::class)->approve($runs[$key]);
            } elseif ($key === 'revision') {
                DB::table('optimization_runs')->where('id', $runs[$key])->update(['plan_status' => 'en_revision']);
            }
        }
        $studies = [];
        foreach (['revision', 'escasez', 'publicado'] as $key) {
            $studies[$key] = $this->scenario($cycles[$key], $coordinator->id, $key === 'escasez' ? null : ($key === 'revision' ? 'A3' : 'A1'));
        }
        $matrix = ['A1' => ['E1' => 4, 'E2' => 12], 'A2' => ['E1' => 7, 'E2' => 8], 'A3' => ['E1' => 6, 'E2' => 10]];
        $this->insert('scenario_studies', ['name' => 'Ejercicio manual de costos y sensibilidad', 'matrix' => json_encode($matrix), 'result' => json_encode(app(DecisionAnalysis::class)->calculate($matrix, 0.5, 'costs')), 'alpha' => 0.5, 'created_by' => $coordinator->id]);
        $this->clinicalHistory($cycles['seguimiento'], $cycles['historico']);
        $this->shifts($cycles['seguimiento'], $coordinator->id);
        $this->swap($cycles['publicado'], $coordinator);
        foreach (['historico', 'seguimiento', 'publicado'] as $key) {
            app(ReportController::class)->create($cycles[$key]);
        }
        foreach ([$group[0], $group[1], $tutors[0], User::where('email', 'interno.clinico@siclin.test')->firstOrFail()] as $user) {
            Auth::setUser($user);
            app(ReportController::class)->personal();
        }
        Auth::setUser($coordinator);
        $manifest = ['prepared_on' => now()->toDateString(), 'cycles' => $cycles, 'runs' => $runs, 'studies' => $studies, 'interns' => array_map(fn (User $user) => $user->id, $interns)];
        DB::table('settings')->insert(['key' => self::MARKER, 'value' => json_encode($manifest), 'updated_at' => now()]);
        Audit::record('demo.ampliacion_cargada', ['cycles' => $cycles, 'nota' => 'Datos ficticios; cálculos y documentos generados con los servicios PHP. Los periodos pasados/en curso son fixtures retrospectivos.']);
        $this->command?->info('Ampliación creada: gestión, siete convocatorias, cálculos, seguimiento y documentos PDF.');
    }

    private function cycle(string $name, int $specialty, ?int $cohort, int $start, int $end, string $status): int
    {
        return $this->insert('cycles', [
            'name' => $name, 'specialty_id' => $specialty, 'cohort_id' => $cohort,
            'starts_on' => now()->addDays($start)->toDateString(), 'ends_on' => now()->addDays($end)->toDateString(),
            'opens_at' => in_array($status, ['abierta', 'borrador'], true) ? now()->subDay() : now()->addDays(min(-5, $start - 14)),
            'closes_at' => in_array($status, ['abierta', 'borrador'], true) ? now()->addDays(10) : now()->addDays(min(-1, $start - 8)), 'status' => $status,
        ]);
    }

    private function rotation(int $cycle, int $coordinator, string $policy): int
    {
        $service = app(RotationService::class);
        $input = $service->input($cycle, DecisionAnalysis::POLICIES[$policy]);
        $run = $this->insert('optimization_runs', ['cycle_id' => $cycle, 'created_by' => $coordinator, 'input' => json_encode($input), 'input_hash' => $service->hash($input), 'request_id' => (string) Str::uuid(), 'result' => '{}', 'status' => 'propuesta', 'execution_status' => 'en_cola', 'plan_status' => 'borrador']);
        (new OptimizeRotations($run))->handle($service);
        if (DB::table('optimization_runs')->where('id', $run)->value('execution_status') !== 'completada') {
            throw new \RuntimeException('Falló el cálculo real de la convocatoria '.$cycle);
        }

        return $run;
    }

    /** Retrospective fixtures use validated solver results; production publication remains restricted to future periods. */
    private function retrospectivePublication(int $cycle, int $run): void
    {
        $result = json_decode(DB::table('optimization_runs')->where('id', $run)->value('result'), true);
        if ($result['pendientes'] !== []) {
            throw new \RuntimeException('El ejemplo retrospectivo requiere una propuesta completa.');
        }
        foreach ($result['asignaciones'] as $assignment) {
            $this->insert('assignments', ['cycle_id' => $cycle, 'run_id' => $run, 'user_id' => $assignment['internoId'], 'place_id' => $assignment['plazaId'], 'tutor_id' => DB::table('places')->where('id', $assignment['plazaId'])->value('tutor_id'), 'status' => 'aprobada']);
        }
        DB::table('cycles')->where('id', $cycle)->update(['status' => 'publicada']);
        DB::table('optimization_runs')->where('id', $run)->update(['status' => 'aprobada', 'plan_status' => 'publicado']);
    }

    private function scenario(int $cycle, int $coordinator, ?string $policy): int
    {
        $input = ['base' => app(RotationService::class)->input($cycle, DecisionAnalysis::POLICIES['A1']), 'parameters' => ['cycle_id' => $cycle, 'alpha' => 0.5, 'factors' => [1, 0.75, 0.5], 'limits' => [1, 1, 1], 'unavailable' => []]];
        $cycleName = DB::table('cycles')->where('id', $cycle)->value('name');
        $study = $this->insert('scenario_studies', ['name' => 'Escenarios: '.$cycleName, 'matrix' => '{}', 'result' => '{}', 'input' => json_encode($input), 'execution_status' => 'en_cola', 'alpha' => 0.5, 'chosen' => $policy, 'justification' => $policy ? 'Política seleccionada según criterios de cobertura, preferencias y balance hospitalario.' : null, 'created_by' => $coordinator]);
        (new ComputeScenario($study))->handle(app(ScenarioService::class));
        if (DB::table('scenario_studies')->where('id', $study)->value('execution_status') !== 'completada') {
            throw new \RuntimeException('Falló el cálculo de escenarios.');
        }

        return $study;
    }

    private function clinicalHistory(int $current, int $historical): void
    {
        foreach ([$current => 3, $historical => 2] as $cycle => $days) {
            foreach (DB::table('assignments')->where('cycle_id', $cycle)->orderBy('id')->get() as $index => $assignment) {
                for ($day = 1; $day <= $days; $day++) {
                    $attendance = $day === 2 && $index % 5 === 0 ? 'justificada' : ($day === 1 && $index % 7 === 0 ? 'ausente' : 'presente');
                    $data = ['date' => now()->subDays($cycle === $current ? $day : 31 + $day)->toDateString(), 'attendance' => $attendance, 'notes' => 'Registro de asistencia: actividad académica supervisada.'];
                    if ($attendance === 'presente') {
                        $data['competencies'] = ['conocimiento' => 75 + $index, 'procedimientos' => 70 + $index, 'comunicacion' => 85, 'profesionalismo' => 90];
                    }
                    app(ClinicalController::class)->record(Request::create('/', 'POST', $data), $assignment->id);
                }
                if ($cycle === $current && $index < 4) {
                    $this->insert('incidents', ['assignment_id' => $assignment->id, 'created_by' => $assignment->user_id, 'type' => $index % 2 ? 'correccion' : 'ausencia', 'description' => 'Solicitud de revisión del registro académico #'.($index + 1), 'status' => $index < 2 ? 'abierta' : 'resuelta', 'resolution' => $index < 2 ? null : 'Revisado con el tutor; observación aclarada sin modificar el historial.']);
                }
            }
        }
    }

    private function shifts(int $cycle, int $coordinator): void
    {
        $slots = [];
        $assignments = DB::table('assignments')->where('cycle_id', $cycle)->orderBy('id')->get();
        foreach ($assignments->pluck('tutor_id')->unique() as $tutor) {
            $this->insert('availabilities', ['tutor_id' => $tutor, 'starts_at' => now()->subDays(7)->startOfDay(), 'ends_at' => now()->addDays(14)->endOfDay()]);
        }
        foreach ($assignments->take(3) as $index => $assignment) {
            $slots[] = ['assignment_id' => $assignment->id, 'starts_at' => now()->subDays(3 - $index)->setTime(8, 0)->toDateTimeString(), 'ends_at' => now()->subDays(3 - $index)->setTime(14, 0)->toDateTimeString()];
        }
        $planner = app(ShiftPlanner::class);
        $snapshot = $planner->snapshot($slots, 12);
        $batch = $this->insert('shift_batches', ['name' => 'Guardias de Cirugía', 'rest_hours' => 12, 'created_by' => $coordinator, 'input' => json_encode(['slots' => $slots, 'snapshot' => $snapshot]), 'input_hash' => hash('sha256', json_encode($snapshot)), 'execution_status' => 'en_cola']);
        (new ComputeShifts($batch))->handle($planner);
        app(ShiftController::class)->approve($planner, $batch);
        foreach (DB::table('shifts')->where('batch_id', $batch)->orderBy('id')->limit(2)->pluck('id') as $shift) {
            app(ShiftController::class)->performed($shift);
        }
    }

    private function swap(int $cycle, User $coordinator): void
    {
        $assignments = DB::table('assignments')->where('cycle_id', $cycle)->orderBy('id')->get();
        $source = $assignments->first();
        $target = $assignments->first(fn ($row) => $row->place_id !== $source->place_id);
        Auth::setUser(User::findOrFail($source->user_id));
        app(SwapController::class)->request(Request::create('/', 'POST', ['target_id' => $target->id, 'reason' => 'Intercambio solicitado por compatibilidad de traslado.']), $source->id);
        $swap = DB::table('swap_requests')->where('source_id', $source->id)->value('id');
        Auth::setUser(User::findOrFail($target->user_id));
        app(SwapController::class)->consent(Request::create('/', 'POST', ['accept' => 1]), $swap);
        Auth::setUser($coordinator);
    }
}
