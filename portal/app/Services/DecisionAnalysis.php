<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class DecisionAnalysis
{
    public const POLICIES = [
        'A1' => ['pesoPreferencias' => 10, 'pesoMerito' => 1, 'pesoEquilibrio' => 0, 'pesoCambios' => 5],
        'A2' => ['pesoPreferencias' => 1, 'pesoMerito' => 10, 'pesoEquilibrio' => 0, 'pesoCambios' => 5],
        'A3' => ['pesoPreferencias' => 5, 'pesoMerito' => 3, 'pesoEquilibrio' => 3, 'pesoCambios' => 5],
    ];

    /** Utilities must share one scale and direction: higher is better. Alpha is optimism. */
    public function calculate(array $matrix, float $alpha): array
    {
        if (array_keys($matrix) !== array_keys(self::POLICIES) || $alpha < 0 || $alpha > 1) {
            throw ValidationException::withMessages(['matrix' => 'Se requieren A1, A2 y A3; alfa entre 0 y 1.']);
        }
        foreach ($matrix as $row) {
            if (count($row) !== 3) {
                throw ValidationException::withMessages(['matrix' => 'Cada alternativa requiere E1, E2 y E3.']);
            }
            foreach ($row as $value) {
                if (! is_numeric($value) || ! is_finite((float) $value) || abs((float) $value) > 1000000) {
                    throw ValidationException::withMessages(['matrix' => 'Utilidades numéricas finitas, entre -1000000 y 1000000.']);
                }
            }
        }
        $best = [];
        for ($j = 0; $j < 3; $j++) {
            $best[$j] = max(array_column($matrix, $j));
        }
        $scores = [];
        $regrets = [];
        foreach ($matrix as $name => $row) {
            $regrets[$name] = array_map(fn ($v, $j) => $best[$j] - $v, $row, array_keys($row));
            $scores[$name] = ['maximin' => min($row), 'maximax' => max($row), 'laplace' => array_sum($row) / 3, 'hurwicz' => $alpha * max($row) + (1 - $alpha) * min($row), 'savage' => max($regrets[$name])];
        }
        $winners = [];
        foreach (array_keys(reset($scores)) as $criterion) {
            $values = array_column($scores, $criterion);
            $target = $criterion === 'savage' ? min($values) : max($values);
            $winners[$criterion] = array_keys(array_filter($scores, fn ($row) => abs($row[$criterion] - $target) < 1e-8));
        }
        $sensitivity = [];
        for ($k = 0; $k <= 10; $k++) {
            $a = $k / 10;
            $values = [];
            foreach ($matrix as $name => $row) {
                $values[$name] = $a * max($row) + (1 - $a) * min($row);
            }
            $max = max($values);
            $sensitivity[] = ['alpha' => $a, 'alternativas' => array_keys(array_filter($values, fn ($v) => abs($v - $max) < 1e-8))];
        }

        return ['scores' => $scores, 'regrets' => $regrets, 'winners' => $winners, 'sensitivity' => $sensitivity];
    }
}
