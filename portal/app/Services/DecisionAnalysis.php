<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class DecisionAnalysis
{
    public const POLICIES = [
        'A1' => ['pesoPreferencias' => 10, 'pesoMerito' => 8, 'pesoEquilibrio' => 0, 'pesoCambios' => 5],
        'A2' => ['pesoPreferencias' => 2, 'pesoMerito' => 2, 'pesoEquilibrio' => 10, 'pesoCambios' => 5],
        'A3' => ['pesoPreferencias' => 5, 'pesoMerito' => 3, 'pesoEquilibrio' => 8, 'pesoCambios' => 5],
    ];

    /** Costs are converted to utility by u = -cost, without rescaling. Alpha is optimism. */
    public function calculate(array $matrix, float $alpha, string $orientation = 'benefits'): array
    {
        if (! $matrix || count($matrix) > 50 || ! is_finite($alpha) || $alpha < 0 || $alpha > 1 || ! in_array($orientation, ['benefits', 'costs'], true)) {
            throw ValidationException::withMessages(['matrix' => 'Matriz no vacía (máximo 50 alternativas), orientación beneficios/costos y alfa finito entre 0 y 1.']);
        }
        $scenarios = null;
        foreach ($matrix as $name => $row) {
            if (! is_string($name) || trim($name) === '' || strlen($name) > 100 || ! is_array($row) || count($row) < 1 || count($row) > 50 || ($scenarios !== null && array_keys($row) !== $scenarios)) {
                throw ValidationException::withMessages(['matrix' => 'Identifica alternativas y usa las mismas columnas de escenarios en cada fila (1 a 50).']);
            }
            $scenarios = array_keys($row);
            foreach ($row as $value) {
                if (! is_numeric($value) || ! is_finite((float) $value) || abs((float) $value) > 1000000) {
                    throw ValidationException::withMessages(['matrix' => 'Valores finitos entre -1000000 y 1000000.']);
                }
            }
        }
        $utility = [];
        foreach ($matrix as $name => $row) {
            $utility[$name] = array_map(fn ($value) => (float) $value * ($orientation === 'costs' ? -1 : 1), $row);
        }
        $best = [];
        foreach ($scenarios as $scenario) { $best[$scenario] = max(array_column($utility, $scenario)); }
        $scores = []; $regrets = [];
        foreach ($utility as $name => $row) {
            foreach ($row as $scenario => $value) { $regrets[$name][$scenario] = $best[$scenario] - $value; }
            $scores[$name] = ['maximin' => min($row), 'maximax' => max($row), 'laplace' => array_sum($row) / count($row), 'hurwicz' => $alpha * max($row) + (1 - $alpha) * min($row), 'savage' => max($regrets[$name])];
        }
        $winners = [];
        foreach (array_keys(reset($scores)) as $criterion) {
            $values = array_column($scores, $criterion);
            $target = $criterion === 'savage' ? min($values) : max($values);
            $winners[$criterion] = array_keys(array_filter($scores, fn ($row) => abs($row[$criterion] - $target) < 1e-8));
        }
        $sensitivity = [];
        for ($k = 0; $k <= 10; $k++) {
            $a = $k / 10; $values = [];
            foreach ($utility as $name => $row) { $values[$name] = $a * max($row) + (1 - $a) * min($row); }
            $target = max($values);
            $sensitivity[] = ['alpha' => $a, 'alternativas' => array_keys(array_filter($values, fn ($value) => abs($value - $target) < 1e-8)), 'values' => $values];
        }
        return ['orientation' => $orientation, 'conversion' => $orientation === 'costs' ? 'utilidad = −costo (sin reescalado)' : 'utilidad = beneficio', 'scenarios' => $scenarios, 'utility' => $utility, 'scores' => $scores, 'regrets' => $regrets, 'winners' => $winners, 'sensitivity' => $sensitivity];
    }
}
