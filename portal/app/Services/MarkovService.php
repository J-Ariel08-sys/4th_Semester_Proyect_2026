<?php

namespace App\Services;

class MarkovService
{
    public const STATES = ['E1', 'E2', 'E3'];

    public const STATE_LABELS = [
        'E1' => 'Normal',
        'E2' => 'Sobredemanda',
        'E3' => 'Contingencia',
    ];

    /**
     * Ergodic baseline transition matrix calibrated for UNIFRANZ La Paz clinical network.
     * Row 0 (E1): P(E3|E1)=0.08 (<0.10 -> Green)
     * Row 1 (E2): P(E3|E2)=0.20 (0.10..0.30 -> Yellow)
     * Row 2 (E3): P(E3|E3)=0.50 (>=0.30 -> Red)
     */
    public const DEFAULT_MATRIX = [
        [0.70, 0.22, 0.08],
        [0.35, 0.45, 0.20],
        [0.15, 0.35, 0.50],
    ];

    public const DEFAULT_A3_PAYOFFS = [90.0, 86.0, 68.0];

    private const MIN_ROW_TRANSITIONS_FOR_EMPIRICAL = 3;

    /**
     * Analyze ergodic Markov chain from ordered monthly state records.
     *
     * @param  array<int, object|array>  $records  Ordered ascending by year_month
     * @param  array<int, float>|null  $a3Payoffs  [E1, E2, E3] payoffs for Policy A3
     */
    public function analyze(array $records, ?array $a3Payoffs = null): array
    {
        $payoffs = $a3Payoffs ?? self::DEFAULT_A3_PAYOFFS;
        $index = ['E1' => 0, 'E2' => 1, 'E3' => 2];

        $counts = [
            [0, 0, 0],
            [0, 0, 0],
            [0, 0, 0],
        ];
        $totalTransitions = 0;

        $normalized = [];
        foreach ($records as $row) {
            $state = is_array($row) ? ($row['state'] ?? null) : ($row->state ?? null);
            $ym = is_array($row)
                ? ($row['period_month'] ?? $row['year_month'] ?? null)
                : ($row->period_month ?? $row->year_month ?? null);
            if (isset($index[$state])) {
                $normalized[] = ['year_month' => $ym, 'state' => $state];
            }
        }

        for ($i = 0; $i < count($normalized) - 1; $i++) {
            $from = $index[$normalized[$i]['state']];
            $to = $index[$normalized[$i + 1]['state']];
            $counts[$from][$to]++;
            $totalTransitions++;
        }

        $matrix = self::DEFAULT_MATRIX;
        $calibratedRows = 0;
        $priorWeight = 3.0;
        for ($i = 0; $i < 3; $i++) {
            $rowSum = array_sum($counts[$i]);
            if ($rowSum > 0) {
                $denom = $rowSum + $priorWeight;
                for ($j = 0; $j < 3; $j++) {
                    $matrix[$i][$j] = round(($counts[$i][$j] + ($priorWeight * self::DEFAULT_MATRIX[$i][$j])) / $denom, 4);
                }
                $matrix[$i][2] = round(1.0 - $matrix[$i][0] - $matrix[$i][1], 4);
                $calibratedRows++;
            }
        }

        $currentState = $normalized ? $normalized[count($normalized) - 1]['state'] : 'E1';
        $currentMonth = $normalized ? $normalized[count($normalized) - 1]['year_month'] : now()->format('Y-m');
        $v = [0.0, 0.0, 0.0];
        $v[$index[$currentState]] = 1.0;

        $forecast = [];
        for ($step = 1; $step <= 6; $step++) {
            $v = $this->multiplyVectorMatrix($v, $matrix);
            $veA3 = $v[0] * $payoffs[0] + $v[1] * $payoffs[1] + $v[2] * $payoffs[2];
            $forecast[] = [
                'mes' => $step,
                'p_e1' => round($v[0], 4),
                'p_e2' => round($v[1], 4),
                'p_e3' => round($v[2], 4),
                'p_e1_pct' => round($v[0] * 100, 1),
                'p_e2_pct' => round($v[1] * 100, 1),
                'p_e3_pct' => round($v[2] * 100, 1),
                've_a3' => round($veA3, 2),
            ];
        }

        $pi = $this->stationaryDistribution($matrix);
        $mfpt = $this->meanFirstPassageToE3($matrix);
        $nextProbE3 = $forecast[0]['p_e3'];
        $semaforo = $this->classifyTrafficLight($nextProbE3);

        return [
            'estado_actual' => $currentState,
            'estado_actual_etiqueta' => self::STATE_LABELS[$currentState],
            'ultimo_mes' => $currentMonth,
            'meses_registrados' => count($normalized),
            'transiciones_observadas' => $totalTransitions,
            'fuente_matriz' => $calibratedRows > 0 ? 'calibrada_bd' : 'matriz_base',
            'filas_calibradas' => $calibratedRows,
            'matriz_p' => $matrix,
            'conteos_nij' => $counts,
            'semaforo' => $semaforo,
            'prediccion_6_meses' => $forecast,
            'estacionaria' => [
                'E1' => round($pi[0], 4),
                'E2' => round($pi[1], 4),
                'E3' => round($pi[2], 4),
                'E1_pct' => round($pi[0] * 100, 1),
                'E2_pct' => round($pi[1] * 100, 1),
                'E3_pct' => round($pi[2] * 100, 1),
            ],
            'tiempo_primer_paso' => [
                'm13' => round($mfpt['m13'], 2),
                'm23' => round($mfpt['m23'], 2),
            ],
        ];
    }

    public function classifyTrafficLight(float $probE3): array
    {
        if ($probE3 < 0.10) {
            return [
                'nivel' => 'verde',
                'css_class' => 'semaforo-verde',
                'prob_e3' => round($probE3, 4),
                'prob_e3_pct' => round($probE3 * 100, 1),
                'titulo' => 'Verde · Operación normal bajo política A3',
                'accion' => 'La probabilidad de contingencia es menor al 10 %. Mantener la distribución estándar y el monitoreo mensual.',
                'emergencia_habilitada' => false,
            ];
        }

        if ($probE3 < 0.30) {
            return [
                'nivel' => 'amarillo',
                'css_class' => 'semaforo-amarillo',
                'prob_e3' => round($probE3, 4),
                'prob_e3_pct' => round($probE3 * 100, 1),
                'titulo' => 'Amarillo · Pre-activación de brigadas rotatorias',
                'accion' => 'La probabilidad de contingencia se ubica entre 10 % y 29.9 %. Preparar brigadas rotatorias de refuerzo y revisar reservas de cupo.',
                'emergencia_habilitada' => false,
            ];
        }

        return [
            'nivel' => 'rojo',
            'css_class' => 'semaforo-rojo',
            'prob_e3' => round($probE3, 4),
            'prob_e3_pct' => round($probE3 * 100, 1),
            'titulo' => 'Rojo · Alerta crítica de contingencia',
            'accion' => 'La probabilidad de contingencia alcanza o supera el 30 %. Habilitar reasignación automática de emergencia y brigadas.',
            'emergencia_habilitada' => true,
        ];
    }

    private function multiplyVectorMatrix(array $v, array $p): array
    {
        $out = [0.0, 0.0, 0.0];
        for ($j = 0; $j < 3; $j++) {
            $out[$j] = $v[0] * $p[0][$j] + $v[1] * $p[1][$j] + $v[2] * $p[2][$j];
        }

        return $out;
    }

    /**
     * Exact stationary distribution π satisfying πP = π and Σπ_i = 1.
     */
    public function stationaryDistribution(array $p): array
    {
        // Solve 3x3 linear system:
        // (p00 - 1)pi0 + p10*pi1 + p20*pi2 = 0
        // p01*pi0 + (p11 - 1)pi1 + p21*pi2 = 0
        // pi0 + pi1 + pi2 = 1
        $a = [
            [$p[0][0] - 1.0, $p[1][0], $p[2][0], 0.0],
            [$p[0][1], $p[1][1] - 1.0, $p[2][1], 0.0],
            [1.0, 1.0, 1.0, 1.0],
        ];

        for ($col = 0; $col < 3; $col++) {
            $pivot = $col;
            for ($row = $col + 1; $row < 3; $row++) {
                if (abs($a[$row][$col]) > abs($a[$pivot][$col])) {
                    $pivot = $row;
                }
            }
            [$a[$col], $a[$pivot]] = [$a[$pivot], $a[$col]];

            $div = $a[$col][$col];
            if (abs($div) < 1e-12) {
                return [1 / 3, 1 / 3, 1 / 3];
            }
            for ($k = $col; $k < 4; $k++) {
                $a[$col][$k] /= $div;
            }
            for ($row = 0; $row < 3; $row++) {
                if ($row === $col) {
                    continue;
                }
                $factor = $a[$row][$col];
                for ($k = $col; $k < 4; $k++) {
                    $a[$row][$k] -= $factor * $a[$col][$k];
                }
            }
        }

        return [$a[0][3], $a[1][3], $a[2][3]];
    }

    /**
     * Exact Mean First Passage Time to E3 (index 2):
     * (1 - p11) m13 - p12 m23 = 1
     * -p21 m13 + (1 - p22) m23 = 1
     */
    public function meanFirstPassageToE3(array $p): array
    {
        $a = 1.0 - $p[0][0];
        $b = -$p[0][1];
        $c = -$p[1][0];
        $d = 1.0 - $p[1][1];

        $det = ($a * $d) - ($b * $c);
        if (abs($det) < 1e-12) {
            return ['m13' => 0.0, 'm23' => 0.0];
        }

        $m13 = ($d - $b) / $det;
        $m23 = ($a - $c) / $det;

        return ['m13' => $m13, 'm23' => $m23];
    }
}
