<?php
namespace App\Services;

class ScenarioService
{
    public function calculate(array $base, array $data): array
    {
        $optimizer = app(RotationService::class);
        $analysis = app(DecisionAnalysis::class);
        $matrix = [];
        $evidence = [];
        foreach (DecisionAnalysis::POLICIES as $alternative => $policy) {
            $matrix[$alternative] = [];
            foreach (($data['factors']) as $scenarioIndex => $factor) {
                $input = $base;
                $input['politica'] = $policy;
                $input['segundos'] = min(10,config('siclin.solver_seconds'));
                foreach ($input['tutores'] ?? [] as $tutor => $limit) { $input['tutores'][$tutor] = in_array((string)$tutor,array_map('strval',$data['unavailable'][$scenarioIndex] ?? []),true) ? 0 : (int) floor($limit*($data['limits'][$scenarioIndex] ?? 1)); }
                foreach ($input['plazas'] as &$place) {
                    $place['capacidad'] = (int) floor($place['capacidad'] * $factor);
                    $place['reserva'] = min($place['reserva'], $place['capacidad']);
                } unset($place);
                $result = $optimizer->evaluate($input);
                $eligible = max(1, count(array_filter($input['internos'], fn ($i) => $i['habilitado'])));
                $occupancy = [];
                foreach ($input['plazas'] as $p) {
                    $available = $p['capacidad'] - $p['reserva'];
                    if ($available > 0) {
                        $occupancy[] = ($result['ocupacion'][$p['id']] ?? 0) / $available;
                    }
                }
                $imbalance = count($occupancy) > 1 ? max($occupancy) - min($occupancy) : 0;
                $utility = round(100 * count($result['asignaciones']) / $eligible + 25 * $result['primerasPreferencias'] / $eligible - 10 * $imbalance, 4);
                $matrix[$alternative][] = $utility;
                $evidence[$alternative][] = ['factor' => $factor, 'supervision_factor'=>$data['limits'][$scenarioIndex] ?? 1, 'duration_ms'=>$result['duration_ms'], 'asignados' => count($result['asignaciones']), 'pendientes' => count($result['pendientes']), 'primeras' => $result['primerasPreferencias'], 'diferencia_ocupacion' => $imbalance, 'utilidad' => $utility];
            }
        }
        $result = $analysis->calculate($matrix, (float) $data['alpha']);
        $result['simulation'] = ['engine'=>RotationOptimizer::VERSION, 'parameters'=>$data, 'cycle_id' => (int) $data['cycle_id'], 'input_hash' => $optimizer->hash($base), 'input' => $base, 'metrics' => $evidence, 'semantics' => 'Políticas con reasignación permitida después de observar capacidad; no planes fijos anticipados.', 'formula' => '100·cobertura + 25·primeras preferencias / habilitados − 10·diferencia de ocupación relativa'];
        return ['matrix' => $matrix, 'result' => $result];
    }
}
