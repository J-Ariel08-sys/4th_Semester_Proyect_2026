<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AssignmentPublished;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RotationService
{
    public function input(int $cycleId, array $policy): array
    {
        $cycle = DB::table('cycles')->find($cycleId);
        abort_unless($cycle, 404);
        $places = DB::table('places')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->join('users', 'users.id', '=', 'places.tutor_id')
            ->where('cycle_id', $cycleId)->orderBy('places.id')->select('places.*', 'hospitals.active as hospital_active', 'hospitals.agreement_until', 'users.active as tutor_active', 'users.supervision_limit')->get();
        $places = $places->map(function ($place) use ($cycle) {
            $occupied = DB::table('assignments')->join('cycles', 'cycles.id', '=', 'assignments.cycle_id')->where('assignments.tutor_id', $place->tutor_id)->where('assignments.cycle_id', '!=', $cycle->id)->where('cycles.starts_on', '<=', $cycle->ends_on)->where('cycles.ends_on', '>=', $cycle->starts_on)->count();
            $place->remaining_supervision = max(0, $place->supervision_limit - $occupied);
            $place->capacity = min($place->capacity, $place->remaining_supervision);
            $place->reserve = min($place->reserve, $place->capacity);
            return $place;
        });
        $applications = DB::table('users')->leftJoin('applications', function ($join) use ($cycleId) {
            $join->on('applications.user_id', '=', 'users.id')->where('applications.cycle_id', $cycleId);
        })->where('users.role','interno')->where(function ($query) use ($cycle) {
            $query->whereNotNull('applications.id');
            if ($cycle->cohort_id) { $query->orWhere('users.cohort_id', $cycle->cohort_id); }
        })->orderBy('users.id')->select('applications.id as application_id', 'applications.preferences', 'users.id as user_id', 'users.active', 'users.eligible', 'users.merit', 'users.academic_average', 'users.cohort_id')->get();
        $requirements = DB::table('academic_requirements')->where('specialty_id', $cycle->specialty_id)->get()->keyBy('cohort_id');
        $interns = $applications->map(function ($a) use ($cycle, $requirements) {
            $overlap = DB::table('assignments')->join('cycles', 'cycles.id', '=', 'assignments.cycle_id')->where('assignments.user_id', $a->user_id)->where('assignments.status', 'aprobada')->where('cycles.id', '!=', $cycle->id)->where('cycles.starts_on', '<=', $cycle->ends_on)->where('cycles.ends_on', '>=', $cycle->starts_on)->exists();

            $requirement = $requirements->get($a->cohort_id);
            $duration = \Carbon\Carbon::parse($cycle->starts_on)->diffInDays(\Carbon\Carbon::parse($cycle->ends_on)) + 1;
            $alreadyPlanned = DB::table('assignments')->join('cycles','cycles.id','=','assignments.cycle_id')->where('assignments.user_id',$a->user_id)->where('cycles.specialty_id',$cycle->specialty_id)->where('cycles.id','!=',$cycle->id)->count();
            $trajectory = (! $cycle->cohort_id || $a->cohort_id === $cycle->cohort_id) && (! $requirement || ($duration >= $requirement->min_days && $alreadyPlanned < $requirement->required_blocks));
            return ['id' => (string) $a->user_id, 'habilitado' => (bool) ($a->active && $a->eligible && $a->application_id && $trajectory && ! $overlap), 'merito' => (int) round(0.7 * $a->academic_average + 0.3 * $a->merit), 'preferencias' => array_map('strval', json_decode($a->preferences ?? '[]', true)), 'plazaAnterior' => (($previous = DB::table('assignments')->where('cycle_id', $cycle->id)->where('user_id', $a->user_id)->value('place_id')) !== null ? (string) $previous : null)];
        })->all();

        return ['academic_version' => ['cycle' => [$cycle->starts_on, $cycle->ends_on, $cycle->specialty_id, $cycle->cohort_id], 'requirements' => $requirements->map(fn ($r) => [$r->cohort_id, $r->min_days, $r->required_blocks])->values()->all()], 'tutores' => $places->mapWithKeys(fn ($p) => [(string) $p->tutor_id => (int) $p->remaining_supervision])->all(), 'plazas' => $places->map(fn ($p) => ['id' => (string) $p->id, 'tutorId' => (string) $p->tutor_id, 'capacidad' => ($p->hospital_active && $p->tutor_active && $p->agreement_until >= $cycle->ends_on) ? (int) $p->capacity : 0, 'reserva' => ($p->hospital_active && $p->tutor_active && $p->agreement_until >= $cycle->ends_on) ? (int) $p->reserve : 0])->all(), 'internos' => $interns, 'politica' => $policy, 'segundos' => config('siclin.solver_seconds')];
    }

    private function assertEditable(object $cycle): void
    {
        $ids = DB::table('assignments')->where('cycle_id', $cycle->id)->pluck('id');
        if (now()->gte($cycle->starts_on) || DB::table('clinical_records')->whereIn('assignment_id', $ids)->exists() || DB::table('shifts')->whereIn('assignment_id', $ids)->exists()) {
            throw ValidationException::withMessages(['convocatoria' => 'La planificación solo se modifica antes del inicio y sin seguimiento ni guardias vinculadas.']);
        }
    }

    public function hash(array $input): string
    {
        return hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
    }

    public function generate(int $cycleId, array $policy): int
    {
        $cycle = DB::table('cycles')->find($cycleId);
        abort_unless($cycle && in_array($cycle->status, ['cerrada', 'publicada'], true), 409, 'Cierra las postulaciones antes de optimizar.');
        $this->assertEditable($cycle);
        $input = $this->input($cycleId, $policy);
        if (! $input['internos']) {
            throw ValidationException::withMessages(['cycle_id' => 'No hay postulaciones para calcular.']);
        }
        return DB::transaction(function () use ($cycleId, $input) {
            DB::table('cycles')->where('id', $cycleId)->lockForUpdate()->first();
            $existing = DB::table('optimization_runs')->where('cycle_id', $cycleId)->whereIn('execution_status', ['en_cola', 'ejecutando'])->first();
            if ($existing) { return $existing->id; }
            $id = DB::table('optimization_runs')->insertGetId(['cycle_id' => $cycleId, 'created_by' => auth()->id(), 'input' => json_encode($input), 'result' => '{}', 'input_hash' => $this->hash($input), 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'execution_status' => 'en_cola', 'plan_status' => 'borrador', 'status' => 'propuesta', 'created_at' => now(), 'updated_at' => now()]);
            \App\Jobs\OptimizeRotations::dispatch($id)->onQueue('optimization')->afterCommit();
            Audit::record('optimizacion.encolada', ['id' => $id]);
            return $id;
        });
    }

    public function evaluate(array $input, ?string $requestId = null): array
    {
        $response = app(RotationOptimizer::class)->solve($input);
        $this->validateResult($input, $response);

        return $response;
    }

    public function validateResult(array $input, mixed $result): void
    {
        if (! is_array($result) || ($result['hardScore'] ?? -1) !== 0 || ! is_array($result['asignaciones'] ?? null) || ! is_array($result['pendientes'] ?? null)) {
            throw ValidationException::withMessages(['motor' => 'El motor devolvió un resultado inválido.']);
        }
        $interns = collect($input['internos'])->keyBy('id');
        $places = collect($input['plazas'])->keyBy('id');
        $seen = [];
        $counts = [];
        $tutorCounts = [];
        foreach ($result['asignaciones'] as $a) {
            $id = $a['internoId'] ?? null;
            $place = $a['plazaId'] ?? null;
            if (! $interns->has($id) || ! $places->has($place) || ! $interns[$id]['habilitado'] || isset($seen[$id])) {
                throw ValidationException::withMessages(['motor' => 'Asignación incompatible o duplicada.']);
            }
            if (isset($interns[$id]['plazasPermitidas']) && ! in_array((string) $place, $interns[$id]['plazasPermitidas'], true)) { throw ValidationException::withMessages(['motor' => 'Trayectoria incompatible.']); }
            if (isset($places[$place]['tutorId'])) {
                $tutor = $places[$place]['tutorId'];
                $tutorCounts[$tutor] = ($tutorCounts[$tutor] ?? 0) + 1;
                if ($tutorCounts[$tutor] > $input['tutores'][$tutor]) { throw ValidationException::withMessages(['motor' => 'Límite de supervisión excedido.']); }
            }
            $seen[$id] = true;
            $counts[$place] = ($counts[$place] ?? 0) + 1;
            if ($counts[$place] > $places[$place]['capacidad'] - $places[$place]['reserva']) {
                throw ValidationException::withMessages(['motor' => 'La propuesta supera los cupos utilizables.']);
            }
        }
        foreach ($result['pendientes'] as $a) {
            $id = $a['internoId'] ?? null;
            if (! $interns->has($id) || isset($seen[$id])) {
                throw ValidationException::withMessages(['motor' => 'Listado de pendientes inválido.']);
            } $seen[$id] = true;
        }
        if (count($seen) !== $interns->count()) {
            throw ValidationException::withMessages(['motor' => 'La propuesta no incluye a todos los postulantes.']);
        }
    }

    public function approve(int $runId, bool $publish = true): void
    {
        DB::transaction(function () use ($runId, $publish) {
            // Lock users in a stable order: concurrent approvals for other cycles cannot double-book an intern.
            DB::table('users')->orderBy('id')->lockForUpdate()->get();
            $run = DB::table('optimization_runs')->where('id', $runId)->lockForUpdate()->first();
            abort_unless($run, 404);
            $cycle = DB::table('cycles')->where('id', $run->cycle_id)->lockForUpdate()->first();
            abort_unless($run->status === 'propuesta' && $run->execution_status === 'completada' && in_array($cycle->status, ['cerrada', 'publicada'], true), 409, 'La propuesta ya fue procesada.');
            $this->assertEditable($cycle);
            DB::table('hospitals')->orderBy('id')->lockForUpdate()->get();
            $original = json_decode($run->input, true);
            $current = $this->input($cycle->id, $original['politica']);
            if ($this->hash($current) !== $run->input_hash) {
                throw ValidationException::withMessages(['propuesta' => 'Los datos cambiaron: genera una nueva propuesta.']);
            }
            $result = json_decode($run->result, true);
            $this->validateResult($current, $result);
            if (DB::table('assignments')->where('cycle_id', $cycle->id)->whereNotIn('user_id', array_column($result['asignaciones'], 'internoId'))->exists() || count($result['pendientes']) > 0) {
                throw ValidationException::withMessages(['propuesta' => 'Resuelve los pendientes antes de publicar la convocatoria completa.']);
            }
            if (! $publish) {
                abort_unless($run->plan_status === 'en_revision', 409, 'Pasa primero la propuesta a revisión.');
                DB::table('optimization_runs')->where('id', $runId)->update(['plan_status' => 'aprobado', 'updated_at' => now()]);
                Audit::record('plan.aprobado', ['id' => $runId]);
                return;
            }
            abort_unless($run->plan_status === 'aprobado', 409, 'Aprueba la propuesta antes de publicarla.');
            foreach ($result['asignaciones'] as $a) {
                $place = DB::table('places')->find($a['plazaId']);
                $previous = DB::table('assignments')->where('cycle_id', $cycle->id)->where('user_id', $a['internoId'])->first();
                if ($previous) { AssignmentHistory::capture($previous, 'Nueva versión publicada'); }
                DB::table('assignments')->updateOrInsert(['cycle_id' => $cycle->id, 'user_id' => $a['internoId']], ['place_id' => $place->id, 'tutor_id' => $place->tutor_id, 'run_id' => $runId, 'status' => 'aprobada', 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('optimization_runs')->where('cycle_id', $cycle->id)->update(['status' => 'descartada', 'plan_status' => 'sustituido', 'updated_at' => now()]);
            DB::table('optimization_runs')->where('id', $runId)->update(['status' => 'aprobada', 'plan_status' => 'publicado']);
            DB::table('cycles')->where('id', $cycle->id)->update(['status' => 'publicada', 'updated_at' => now()]);
            Audit::record('optimizacion.aprobada', ['id' => $runId]);
            DB::afterCommit(function () use ($result, $cycle) {
                foreach (User::whereIn('id', array_column($result['asignaciones'], 'internoId'))->get() as $user) {
                    try {
                        $user->notify(new AssignmentPublished($cycle->name));
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            });
        });
    }
}
