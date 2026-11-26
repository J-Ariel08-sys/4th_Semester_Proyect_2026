<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use SplQueue;

/** Successive shortest augmenting paths: maximum coverage, then minimum cost. */
class RotationOptimizer
{
    public const VERSION = 'php-min-cost-flow/1';

    private array $graph = [];
    private array $edges = [];

    private function edge(int $from, int $to, int $capacity, int $cost): int
    {
        $id = count($this->edges);
        $this->edges[] = ['to' => $to, 'capacity' => $capacity, 'cost' => $cost];
        $this->edges[] = ['to' => $from, 'capacity' => 0, 'cost' => -$cost];
        $this->graph[$from][] = $id;
        $this->graph[$to][] = $id + 1;
        return $id;
    }

    public function solve(array $input): array
    {
        $started = hrtime(true);
        $this->validate($input);
        $this->graph = []; $this->edges = [];
        $source = 0; $next = 1; $internNodes = []; $placeNodes = []; $tutorNodes = []; $choices = [];
        $policy = $input['politica'];
        foreach ($input['internos'] as $intern) { $internNodes[$intern['id']] = $next++; }
        foreach ($input['plazas'] as $place) { $placeNodes[$place['id']] = $next++; }
        foreach ($input['tutores'] ?? [] as $tutor => $capacity) { $tutorNodes[$tutor] = $next++; }
        $sink = $next++;
        $eligible = 0;
        foreach ($input['internos'] as $intern) {
            if (! $intern['habilitado']) { continue; }
            $eligible++;
            $this->edge($source, $internNodes[$intern['id']], 1, 0);
            foreach ($input['plazas'] as $place) {
                if (isset($intern['plazasPermitidas']) && ! in_array($place['id'], $intern['plazasPermitidas'], true)) { continue; }
                $rank = array_search($place['id'], $intern['preferencias'], true);
                $satisfaction = $rank === false ? 0 : intdiv(100 * (count($intern['preferencias']) - $rank), count($intern['preferencias']));
                $utility = $satisfaction * $policy['pesoPreferencias'] + intdiv($satisfaction * $intern['merito'] * $policy['pesoMerito'], 100);
                if (($intern['plazaAnterior'] ?? null) !== null && $intern['plazaAnterior'] !== $place['id']) { $utility -= 100 * $policy['pesoCambios']; }
                $choices[$intern['id']][$place['id']] = $this->edge($internNodes[$intern['id']], $placeNodes[$place['id']], 1, -$utility);
            }
        }
        foreach ($input['plazas'] as $place) {
            $capacity = $place['capacidad'] - $place['reserva'];
            $target = isset($place['tutorId']) ? $tutorNodes[$place['tutorId']] : $sink;
            for ($unit = 1; $unit <= min($capacity, $eligible); $unit++) {
                $cost = intdiv($policy['pesoEquilibrio'] * $unit * $unit * 100, max(1, $capacity)) - intdiv($policy['pesoEquilibrio'] * ($unit - 1) ** 2 * 100, max(1, $capacity));
                $this->edge($placeNodes[$place['id']], $target, 1, $cost);
            }
        }
        foreach ($input['tutores'] ?? [] as $tutor => $capacity) {
            for ($unit=1; $unit<=min($capacity,$eligible); $unit++) {
                $cost=intdiv($policy['pesoEquilibrio']*$unit*$unit*100,max(1,$capacity))-intdiv($policy['pesoEquilibrio']*($unit-1)**2*100,max(1,$capacity));
                $this->edge($tutorNodes[$tutor],$sink,1,$cost);
            }
        }
        $flow = 0; $totalCost = 0; $timedOut = false;
        $deadline = $started + $input['segundos'] * 1_000_000_000;
        while ($flow < $eligible) {
            $distance = array_fill(0, $next, PHP_INT_MAX); $parent = array_fill(0, $next, -1); $queued = array_fill(0, $next, false);
            $distance[$source] = 0; $queue = new SplQueue; $queue->enqueue($source); $queued[$source] = true;
            while (! $queue->isEmpty()) {
                if (hrtime(true) > $deadline) { $timedOut = true; break 2; }
                $node = $queue->dequeue(); $queued[$node] = false;
                foreach ($this->graph[$node] ?? [] as $id) {
                    $edge = $this->edges[$id];
                    if ($edge['capacity'] > 0 && $distance[$edge['to']] > $distance[$node] + $edge['cost']) {
                        $distance[$edge['to']] = $distance[$node] + $edge['cost']; $parent[$edge['to']] = $id;
                        if (! $queued[$edge['to']]) { $queue->enqueue($edge['to']); $queued[$edge['to']] = true; }
                    }
                }
            }
            if ($parent[$sink] === -1) { break; }
            for ($node = $sink; $node !== $source; $node = $this->edges[$parent[$node] ^ 1]['to']) {
                $this->edges[$parent[$node]]['capacity']--; $this->edges[$parent[$node] ^ 1]['capacity']++;
            }
            $flow++; $totalCost += $distance[$sink];
        }
        $assignments = []; $pending = []; $occupancy = array_fill_keys(array_column($input['plazas'], 'id'), 0); $first = 0;
        foreach ($input['internos'] as $intern) {
            $assigned = false;
            foreach ($choices[$intern['id']] ?? [] as $place => $edgeId) {
                if ($this->edges[$edgeId]['capacity'] === 0) {
                    $rank = array_search((string) $place, $intern['preferencias'], true);
                    $assignments[] = ['internoId' => $intern['id'], 'plazaId' => (string) $place, 'preferencia' => $rank === false ? null : $rank + 1];
                    $occupancy[$place]++; $first += (int) ($rank === 0); $assigned = true; break;
                }
            }
            if (! $assigned) { $pending[] = ['internoId' => $intern['id'], 'motivo' => ! $intern['habilitado'] ? 'NO_HABILITADO_O_INCOMPATIBLE' : ($timedOut ? 'SIN_SOLUCION_COMPLETA_EN_EL_LIMITE' : 'CAPACIDAD_COMPATIBLE_INSUFICIENTE')]; }
        }
        return ['estado' => $pending ? 'PROPUESTA_CON_PENDIENTES' : 'PROPUESTA_COMPLETA', 'requiereAprobacion' => true, 'hardScore' => 0, 'mediumScore' => $flow - $eligible, 'softScore' => -$totalCost, 'asignaciones' => $assignments, 'pendientes' => $pending, 'ocupacion' => $occupancy, 'capacidadUtilizable' => array_sum(array_map(fn ($p) => $p['capacidad'] - $p['reserva'], $input['plazas'])), 'primerasPreferencias' => $first, 'engine' => self::VERSION, 'search_complete' => ! $timedOut, 'duration_ms' => (int) ((hrtime(true) - $started) / 1000000)];
    }

    public function validate(array $input): void
    {
        $fail = fn () => throw ValidationException::withMessages(['motor' => 'Datos inválidos para el motor de asignación.']);
        if (! isset($input['plazas'], $input['internos'], $input['politica'], $input['segundos']) || ! is_array($input['plazas']) || ! is_array($input['internos']) || count($input['plazas']) > 500 || count($input['internos']) > 500 || count($input['internos']) * count($input['plazas']) > 100000 || ! is_int($input['segundos']) || $input['segundos'] < 1 || $input['segundos'] > 60) { $fail(); }
        foreach (['pesoPreferencias', 'pesoMerito', 'pesoEquilibrio', 'pesoCambios'] as $weight) {
            if (! isset($input['politica'][$weight]) || ! is_int($input['politica'][$weight]) || $input['politica'][$weight] < 0 || $input['politica'][$weight] > 100) { $fail(); }
        }
        $ids = []; $users = [];
        foreach ($input['tutores'] ?? [] as $id => $capacity) { if (! is_int($capacity) || $capacity < 0 || $capacity > 1000) { $fail(); } }
        foreach ($input['plazas'] as $place) {
            if (! is_string($place['id'] ?? null) || $place['id'] === '' || isset($ids[$place['id']]) || ! is_int($place['capacidad'] ?? null) || ! is_int($place['reserva'] ?? null) || $place['capacidad'] < 0 || $place['capacidad'] > 1000 || $place['reserva'] < 0 || $place['reserva'] > $place['capacidad'] || (isset($place['tutorId']) && ! array_key_exists($place['tutorId'], $input['tutores'] ?? []))) { $fail(); }
            $ids[$place['id']] = true;
        }
        foreach ($input['internos'] as $intern) {
            if (! is_string($intern['id'] ?? null) || $intern['id'] === '' || isset($users[$intern['id']]) || ! is_bool($intern['habilitado'] ?? null) || ! is_int($intern['merito'] ?? null) || $intern['merito'] < 0 || $intern['merito'] > 100 || ! is_array($intern['preferencias'] ?? null) || count(array_unique($intern['preferencias'])) !== count($intern['preferencias']) || array_diff($intern['preferencias'], array_keys($ids)) || (isset($intern['plazasPermitidas']) && (! is_array($intern['plazasPermitidas']) || array_diff($intern['plazasPermitidas'], array_keys($ids))))) { $fail(); }
            $users[$intern['id']] = true;
        }
    }
}
