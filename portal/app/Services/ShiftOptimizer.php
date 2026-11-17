<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/** Deterministic bounded constraint search, maximizing coverage before normalized load. */
class ShiftOptimizer
{
    public const VERSION = 'php-shift-search/1';

    public function solve(array $input): array
    {
        $slots = $input['turnos'] ?? null;
        $loads = (array) ($input['cargaPreviaMinutos'] ?? []);
        $available = $input['disponibilidadMinutos'] ?? [];
        $rest = $input['descansoHoras'] ?? -1;
        $seconds = $input['segundos'] ?? 0;
        if (! is_array($slots) || count($slots) > 100 || ! is_int($rest) || $rest < 0 || $rest > 48 || ! is_int($seconds) || $seconds < 1 || $seconds > 60) {
            throw ValidationException::withMessages(['guardias' => 'Datos inválidos para la planificación de guardias.']);
        }
        $ids = [];
        foreach ($slots as $slot) {
            if (! isset($slot['id'], $slot['internoId'], $slot['inicio'], $slot['fin'], $slot['tutoresPermitidos']) || isset($ids[$slot['id']]) || $slot['fin'] <= $slot['inicio'] || $slot['fin'] - $slot['inicio'] > 86400 || ! is_array($slot['tutoresPermitidos']) || array_diff($slot['tutoresPermitidos'], array_keys($loads))) {
                throw ValidationException::withMessages(['guardias' => 'Turnos duplicados, duración inválida o tutores desconocidos.']);
            }
            $ids[$slot['id']] = true;
        }
        foreach ($loads as $load) { if (! is_numeric($load) || ! is_finite((float) $load) || $load < 0) { throw ValidationException::withMessages(['guardias' => 'Carga previa inválida.']); } }
        usort($slots, fn ($a, $b) => [count($a['tutoresPermitidos']), $a['inicio'], $a['id']] <=> [count($b['tutoresPermitidos']), $b['inicio'], $b['id']]);
        $best = array_fill_keys(array_column($slots, 'id'), null); $bestCount = -1; $bestCost = PHP_FLOAT_MAX; $nodes = 0; $limited = false;
        $started = hrtime(true); $deadline = $started + $seconds * 1_000_000_000;
        $nodeLimit = (int) config('siclin.shift_search_nodes', 100000);
        $search = function (int $index, array $selected, array $busy, array $currentLoads, int $count) use (&$search, &$best, &$bestCount, &$bestCost, &$nodes, &$limited, $slots, $rest, $available, $deadline, $nodeLimit): void {
            if (++$nodes > $nodeLimit || hrtime(true) > $deadline) { $limited = true; return; }
            if ($count + count($slots) - $index < $bestCount) { return; }
            if ($index === count($slots)) {
                $cost = 0.0;
                foreach ($currentLoads as $tutor => $minutes) { $cost += $minutes ** 2 / max(1, $available[$tutor] ?? 1); }
                if ($count > $bestCount || ($count === $bestCount && $cost < $bestCost - 1e-8)) { $best = $selected; $bestCount = $count; $bestCost = $cost; }
                return;
            }
            $slot = $slots[$index]; $minutes = ($slot['fin'] - $slot['inicio']) / 60;
            $tutors = $slot['tutoresPermitidos'];
            usort($tutors, fn ($a, $b) => [($currentLoads[$a] + $minutes) / max(1, $available[$a] ?? 1), (string) $a] <=> [($currentLoads[$b] + $minutes) / max(1, $available[$b] ?? 1), (string) $b]);
            foreach ($tutors as $tutor) {
                foreach ($busy as $previous) {
                    if (((string) $previous['tutor'] === (string) $tutor || $previous['internoId'] === $slot['internoId']) && $slot['inicio'] < $previous['fin'] + $rest * 3600 && $previous['inicio'] < $slot['fin'] + $rest * 3600) { continue 2; }
                }
                $nextLoads = $currentLoads; $nextLoads[$tutor] += $minutes;
                $nextSelected = $selected; $nextSelected[$slot['id']] = (string) $tutor;
                $search($index + 1, $nextSelected, [...$busy, [...$slot, 'tutor' => $tutor]], $nextLoads, $count + 1);
                if ($limited) { return; }
            }
            $selected[$slot['id']] = null;
            $search($index + 1, $selected, $busy, $currentLoads, $count);
        };
        $search(0, [], [], $loads, 0);
        $turns = [];
        foreach ($input['turnos'] as $slot) { $turns[] = ['turnoId' => $slot['id'], 'tutorId' => $best[$slot['id']] ?? null]; }
        return ['hardScore' => 0, 'mediumScore' => max(0, $bestCount) - count($slots), 'softScore' => -($bestCost === PHP_FLOAT_MAX ? 0 : $bestCost), 'turnos' => $turns, 'engine' => self::VERSION, 'nodes' => $nodes, 'search_complete' => ! $limited, 'duration_ms' => (int) ((hrtime(true) - $started) / 1000000)];
    }
}
