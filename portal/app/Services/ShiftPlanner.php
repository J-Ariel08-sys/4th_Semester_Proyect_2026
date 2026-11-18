<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftPlanner
{
    private function prepare(array $slots, int $restHours, ?int $excludeBatch = null): array
    {
        $approved = DB::table('shifts')->join('shift_batches', 'shift_batches.id', '=', 'shifts.batch_id')->join('assignments', 'assignments.id', '=', 'shifts.assignment_id')->where('shift_batches.status', 'aprobada')->when($excludeBatch, fn ($q) => $q->where('shifts.batch_id', '!=', $excludeBatch))->select('shifts.*', 'assignments.user_id', 'shift_batches.rest_hours')->get()->map(fn ($s) => (array) $s)->all();
        $prepared = [];
        $loads = [];
        $weekStart = CarbonImmutable::parse(min(array_column($slots, 'starts_at')))->startOfWeek();
        $weekEnd = CarbonImmutable::parse(max(array_column($slots, 'ends_at')))->endOfWeek();
        foreach ($slots as $index => $slot) {
            $assignment = DB::table('assignments')->whereNull('cycles.deleted_at')->whereNull('places.deleted_at')->join('cycles', 'cycles.id', '=', 'assignments.cycle_id')->join('places', 'places.id', '=', 'assignments.place_id')->join('users', 'users.id', '=', 'assignments.user_id')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->where('assignments.id', $slot['assignment_id'])->select('assignments.*', 'cycles.starts_on', 'cycles.ends_on', 'cycles.specialty_id', 'cycles.status as cycle_status', 'users.eligible', 'places.hospital_id', 'users.active', 'hospitals.active as hospital_active', 'hospitals.agreement_until')->first();
            $start = CarbonImmutable::parse($slot['starts_at']);
            $end = CarbonImmutable::parse($slot['ends_at']);
            if (! $assignment || ! $assignment->active || ! $assignment->eligible || $assignment->cycle_status !== 'publicada' || $assignment->status !== 'aprobada' || ! $assignment->hospital_active || $assignment->agreement_until < $assignment->ends_on || $start->lt(CarbonImmutable::parse($assignment->starts_on)) || $end->gt(CarbonImmutable::parse($assignment->ends_on)->endOfDay()) || $end->lte($start) || $start->diffInHours($end) > 24) {
                throw ValidationException::withMessages(['slots' => 'Guardia fuera del rote, de más de 24 horas o asignación inactiva.']);
            }
            $tutors = DB::table('users')->whereNull('deleted_at')->whereNotNull('access_approved_at')->where('role', 'tutor')->where('active', true)->whereIn('id', DB::table('places')->join('cycles', 'cycles.id', '=', 'places.cycle_id')->whereNull('places.deleted_at')->whereNull('cycles.deleted_at')->where('places.hospital_id', $assignment->hospital_id)->where('cycles.specialty_id', $assignment->specialty_id)->where('cycles.starts_on', '<=', $start->toDateString())->where('cycles.ends_on', '>=', $end->toDateString())->select('places.tutor_id'))->orderBy('id')->pluck('id')->all();
            $allowed = [];
            foreach ($tutors as $tutor) {
                if (! isset($loads[$tutor])) {
                    $loads[$tutor] = (int) array_sum(array_map(fn ($s) => (int) $s['tutor_id'] === $tutor && CarbonImmutable::parse($s['starts_at'])->betweenIncluded($weekStart, $weekEnd) ? CarbonImmutable::parse($s['starts_at'])->diffInMinutes(CarbonImmutable::parse($s['ends_at'])) : 0, $approved));
                }
                $available = DB::table('availabilities')->where('tutor_id', $tutor)->where('starts_at', '<=', $start->toDateTimeString())->where('ends_at', '>=', $end->toDateTimeString())->exists();
                if ($available && ! $this->conflicts($approved, $start, $end, $restHours, 'tutor_id', $tutor) && ! $this->conflicts($approved, $start, $end, $restHours, 'user_id', $assignment->user_id)) {
                    $allowed[] = (string) $tutor;
                }
            }
            $prepared[] = ['id' => (string) $index, 'assignment_id' => (int) $slot['assignment_id'], 'user_id' => $assignment->user_id, 'starts_at' => $start->toDateTimeString(), 'ends_at' => $end->toDateTimeString(), 'inicio' => $start->timestamp, 'fin' => $end->timestamp, 'allowed' => $allowed];
        }

        $availableMinutes = [];
        foreach (array_keys($loads) as $tutor) {
            $intervals = DB::table('availabilities')->where('tutor_id', $tutor)->where('starts_at', '<', $weekEnd)->where('ends_at', '>', $weekStart)->orderBy('starts_at')->get();
            $endPrevious = $weekStart->timestamp;
            $minutes = 0;
            foreach ($intervals as $interval) {
                $start = max($weekStart->timestamp, $endPrevious, CarbonImmutable::parse($interval->starts_at)->timestamp);
                $end = min($weekEnd->timestamp, CarbonImmutable::parse($interval->ends_at)->timestamp);
                $minutes += max(0, $end - $start) / 60;
                $endPrevious = max($endPrevious, $end);
            }
            $availableMinutes[$tutor] = $minutes;
        }

        return [$prepared, $loads, $availableMinutes];
    }

    public function plan(array $slots, int $restHours, ?int $excludeBatch = null): array
    {
        return $this->calculateSnapshot($this->snapshot($slots, $restHours, $excludeBatch))['slots'];
    }

    public function snapshot(array $slots, int $restHours, ?int $excludeBatch = null): array
    {
        [$prepared,$loads,$availableMinutes] = $this->prepare($slots, $restHours, $excludeBatch);
        $input = ['turnos' => array_map(fn ($s) => ['id' => $s['id'], 'internoId' => (string) $s['user_id'], 'inicio' => $s['inicio'], 'fin' => $s['fin'], 'tutoresPermitidos' => $s['allowed']], $prepared), 'cargaPreviaMinutos' => $loads, 'disponibilidadMinutos' => $availableMinutes, 'descansoHoras' => $restHours, 'segundos' => 2];

        return compact('prepared', 'input');
    }

    public function calculateSnapshot(array $snapshot): array
    {
        $prepared = $snapshot['prepared'];
        $result = app(ShiftOptimizer::class)->solve($snapshot['input']);
        if (! is_array($result) || ($result['hardScore'] ?? -1) !== 0 || ! is_array($result['turnos'] ?? null) || count($result['turnos']) !== count($prepared)) {
            throw ValidationException::withMessages(['guardias' => 'Respuesta inválida del planificador.']);
        }
        $byId = [];
        foreach ($result['turnos'] as $row) {
            $id = $row['turnoId'] ?? null;
            if (! is_scalar($id) || ! array_key_exists($id, $prepared) || isset($byId[$id]) || ! array_key_exists('tutorId', $row)) {
                throw ValidationException::withMessages(['guardias' => 'Turnos duplicados o desconocidos en la respuesta.']);
            }
            $byId[$id] = $row;
        }
        $planned = [];
        foreach ($prepared as $s) {
            $tutor = $byId[$s['id']]['tutorId'];
            $planned[] = ['assignment_id' => $s['assignment_id'], 'user_id' => $s['user_id'], 'starts_at' => $s['starts_at'], 'ends_at' => $s['ends_at'], 'tutor_id' => $tutor === null ? null : (int) $tutor, 'reason' => $tutor === null ? 'Sin cobertura factible en esta propuesta. Revisa disponibilidad y descansos.' : null];
        }

        return ['slots' => $planned, 'diagnostic' => collect($result)->except('turnos')->all()];
    }

    public function validatePlan(array $slots, int $restHours, ?int $excludeBatch = null, bool $requireComplete = true): void
    {
        [$prepared] = $this->prepare($slots, $restHours, $excludeBatch);
        $accepted = [];
        foreach ($prepared as $index => $s) {
            $tutor = $slots[$index]['tutor_id'];
            if ($tutor === null && ! $requireComplete) {
                continue;
            }
            $start = CarbonImmutable::parse($s['starts_at']);
            $end = CarbonImmutable::parse($s['ends_at']);
            if ($tutor === null || ! in_array((string) $tutor, $s['allowed'], true) || $this->conflicts($accepted, $start, $end, $restHours, 'user_id', $s['user_id']) || $this->conflicts($accepted, $start, $end, $restHours, 'tutor_id', (int) $tutor)) {
                throw ValidationException::withMessages(['guardias' => 'Hay pendientes o conflictos de disponibilidad, carga o descanso. Genera una nueva propuesta.']);
            }
            $accepted[] = [...$s, 'tutor_id' => (int) $tutor, 'rest_hours' => $restHours];
        }
    }

    private function conflicts(array $busy, CarbonImmutable $start, CarbonImmutable $end, int $rest, string $field, int $id): bool
    {
        foreach ($busy as $s) {
            $effectiveRest = max($rest, (int) ($s['rest_hours'] ?? 0));
            if ((int) $s[$field] === $id && $start->lt(CarbonImmutable::parse($s['ends_at'])->addHours($effectiveRest)) && $end->addHours($effectiveRest)->gt(CarbonImmutable::parse($s['starts_at']))) {
                return true;
            }
        }

        return false;
    }
}
