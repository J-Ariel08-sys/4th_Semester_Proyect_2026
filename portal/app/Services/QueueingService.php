<?php

namespace App\Services;

class QueueingService
{
    public const OVERLOAD_THRESHOLD = 0.85;

    public const SCENARIOS = [
        'E1' => ['label' => 'E1 · Normal', 'column' => 'lambda_e1', 'default_lambda' => 8.0],
        'E2' => ['label' => 'E2 · Sobredemanda', 'column' => 'lambda_e2', 'default_lambda' => 10.0],
        'E3' => ['label' => 'E3 · Contingencia', 'column' => 'lambda_e3', 'default_lambda' => 11.0],
    ];

    /**
     * Exact M/M/s (Erlang-C) steady-state metrics.
     */
    public function solve(int $servers, float $lambda, float $mu): array
    {
        $s = max(1, $servers);
        $lambda = max(0.01, $lambda);
        $mu = max(0.01, $mu);

        $a = $lambda / $mu;
        $rho = $lambda / ($s * $mu);
        $rhoPct = round($rho * 100, 2);

        if ($rho >= 1.0) {
            return [
                's' => $s,
                'lambda' => round($lambda, 2),
                'mu' => round($mu, 2),
                'a' => round($a, 4),
                'rho' => round($rho, 4),
                'rho_pct' => $rhoPct,
                'estable' => false,
                'sobrecarga' => true,
                'p0' => 0.0,
                'p_wait' => 1.0,
                'lq' => null,
                'l' => null,
                'wq_hours' => null,
                'wq_minutes' => null,
                'w_hours' => null,
                'w_minutes' => null,
            ];
        }

        $sum = 0.0;
        $term = 1.0;
        for ($n = 0; $n < $s; $n++) {
            if ($n > 0) {
                $term *= $a / $n;
            }
            $sum += $term;
        }

        // a^s / s!
        $asOverSFact = $term * ($a / $s);
        $tail = $asOverSFact / (1.0 - $rho);
        $p0 = 1.0 / ($sum + $tail);
        $pWait = $tail * $p0;

        $lq = ($pWait * $rho) / (1.0 - $rho);
        $l = $lq + $a;
        $wqHours = $lq / $lambda;
        $wqMinutes = $wqHours * 60.0;
        $wHours = $wqHours + (1.0 / $mu);
        $wMinutes = $wHours * 60.0;

        return [
            's' => $s,
            'lambda' => round($lambda, 2),
            'mu' => round($mu, 2),
            'a' => round($a, 4),
            'rho' => round($rho, 4),
            'rho_pct' => $rhoPct,
            'estable' => true,
            'sobrecarga' => $rho >= self::OVERLOAD_THRESHOLD,
            'p0' => round($p0, 6),
            'p_wait' => round($pWait, 4),
            'lq' => round($lq, 2),
            'l' => round($l, 2),
            'wq_hours' => round($wqHours, 4),
            'wq_minutes' => round($wqMinutes, 2),
            'w_hours' => round($wHours, 4),
            'w_minutes' => round($wMinutes, 2),
        ];
    }

    /**
     * Compare "Sin Internos" (mu_0) vs "Con Internos" (mu_1) across E1, E2, E3 for a hospital.
     */
    public function compareHospital(object $hospital, int $enabledInterns = 0, int $offeredCapacity = 0): array
    {
        $s = max(1, (int) ($hospital->tutors_count ?? 3));
        $mu0 = (float) ($hospital->mu_without_interns ?? 4.0);
        $mu1 = (float) ($hospital->mu_with_interns ?? 5.0);

        $escenarios = [];
        foreach (self::SCENARIOS as $code => $meta) {
            $col = $meta['column'];
            $lambda = (float) ($hospital->$col ?? $meta['default_lambda']);

            $sin = $this->solve($s, $lambda, $mu0);
            $con = $this->solve($s, $lambda, $mu1);

            $reductionMinutes = null;
            $reductionPct = null;
            if ($sin['estable'] && $con['estable'] && $sin['wq_minutes'] !== null && $con['wq_minutes'] !== null) {
                $reductionMinutes = round($sin['wq_minutes'] - $con['wq_minutes'], 2);
                $reductionPct = $sin['wq_minutes'] > 0
                    ? round((($sin['wq_minutes'] - $con['wq_minutes']) / $sin['wq_minutes']) * 100, 1)
                    : 0.0;
            }

            $escenarios[$code] = [
                'codigo' => $code,
                'nombre' => $meta['label'],
                'lambda' => round($lambda, 2),
                'sin_internos' => $sin,
                'con_internos' => $con,
                'reduccion_wq_min' => $reductionMinutes,
                'reduccion_wq_pct' => $reductionPct,
            ];
        }

        return [
            'hospital_id' => $hospital->id ?? null,
            'hospital' => $hospital->name ?? 'Hospital de referencia',
            's_tutores' => $s,
            'mu_sin_internos' => round($mu0, 2),
            'mu_con_internos' => round($mu1, 2),
            'internos_habilitados' => $enabledInterns,
            'capacidad_ofertada' => $offeredCapacity,
            'umbral_sobrecarga_pct' => (int) round(self::OVERLOAD_THRESHOLD * 100),
            'escenarios' => $escenarios,
        ];
    }
}
