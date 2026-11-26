<?php

namespace App\Services;

class OperationsDecisionService
{
    public const POLICY_DESCRIPTIONS = [
        'A1' => 'Equitativa balanceada',
        'A2' => 'Concentración en 3.er nivel',
        'A3' => 'Distribución flexible con brigadas rotatorias de refuerzo',
    ];

    public const BASE_PAYOFFS = [
        'A1' => ['E1' => 90.0, 'E2' => 68.0, 'E3' => 40.0],
        'A2' => ['E1' => 82.0, 'E2' => 74.0, 'E3' => 48.0],
        'A3' => ['E1' => 90.0, 'E2' => 86.0, 'E3' => 68.0],
    ];

    /**
     * Build the 3x3 payoff matrix, Maximin evaluation, and Expected Value using Markov stationary probabilities.
     *
     * @param  array<string, float>  $stationary  ['E1' => ..., 'E2' => ..., 'E3' => ...]
     * @param  array<string, mixed>  $queueing  Output from QueueingService::compareHospital()
     * @param  array<string, int>  $demandContext  ['plazas_ofertadas', 'cupos_utilizables', 'cupos_reserva', 'postulantes', 'internos_habilitados']
     */
    public function evaluate(array $stationary, array $queueing, array $demandContext): array
    {
        $p1 = (float) ($stationary['E1'] ?? 0.485);
        $p2 = (float) ($stationary['E2'] ?? 0.338);
        $p3 = (float) ($stationary['E3'] ?? 0.177);

        // Reflect real queueing E3 improvement as contextual metadata while keeping 0-100 scale
        $e3Sin = $queueing['escenarios']['E3']['sin_internos'] ?? null;
        $e3Con = $queueing['escenarios']['E3']['con_internos'] ?? null;

        $rows = [];
        $bestPolicy = null;
        $bestMaximin = -PHP_FLOAT_MAX;

        foreach (self::BASE_PAYOFFS as $code => $payoffs) {
            $minValue = min($payoffs['E1'], $payoffs['E2'], $payoffs['E3']);
            $ve = ($p1 * $payoffs['E1']) + ($p2 * $payoffs['E2']) + ($p3 * $payoffs['E3']);

            if ($minValue > $bestMaximin) {
                $bestMaximin = $minValue;
                $bestPolicy = $code;
            }

            $rows[$code] = [
                'codigo' => $code,
                'nombre' => self::POLICY_DESCRIPTIONS[$code],
                'E1' => $payoffs['E1'],
                'E2' => $payoffs['E2'],
                'E3' => $payoffs['E3'],
                'peor_escenario' => $minValue,
                'valor_esperado' => round($ve, 2),
                'recomendada' => false,
            ];
        }

        if ($bestPolicy !== null) {
            $rows[$bestPolicy]['recomendada'] = true;
        }

        $offered = max(0, (int) ($demandContext['plazas_ofertadas'] ?? 0));
        $reserve = max(0, (int) ($demandContext['cupos_reserva'] ?? 0));
        $brigadePct = $offered > 0 ? round(($reserve / $offered) * 100, 1) : 15.0;

        return [
            'criterio' => 'Maximin (Wald)',
            'escala' => '0 a 100',
            'politica_recomendada' => $bestPolicy,
            'recomendacion' => $bestPolicy ? $rows[$bestPolicy] : null,
            'matriz' => $rows,
            'porcentaje_brigadas' => $brigadePct,
            'contexto_demanda' => $demandContext,
            'metricas_sprint2' => [
                'e3_rho_sin_internos_pct' => $e3Sin['rho_pct'] ?? null,
                'e3_rho_con_internos_pct' => $e3Con['rho_pct'] ?? null,
                'e3_wq_sin_internos_min' => $e3Sin['wq_minutes'] ?? null,
                'e3_wq_con_internos_min' => $e3Con['wq_minutes'] ?? null,
            ],
        ];
    }
}
