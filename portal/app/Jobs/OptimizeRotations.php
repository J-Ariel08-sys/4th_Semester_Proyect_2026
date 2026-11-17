<?php

namespace App\Jobs;

use App\Services\RotationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class OptimizeRotations implements ShouldQueue
{
    use Queueable;
    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(public int $runId) {}

    public function handle(RotationService $service): void
    {
        $run = DB::transaction(function () {
            $run = DB::table('optimization_runs')->where('id', $this->runId)->lockForUpdate()->first();
            if (! $run || $run->execution_status !== 'en_cola') { return null; }
            DB::table('optimization_runs')->where('id', $run->id)->update(['execution_status' => 'ejecutando', 'started_at' => now(), 'attempts' => $run->attempts + 1, 'updated_at' => now()]);
            return $run;
        });
        if (! $run) { return; }
        $start = hrtime(true);
        try {
            $input = json_decode($run->input, true, flags: JSON_THROW_ON_ERROR);
            $result = $service->evaluate($input, $run->request_id);
            $eligible = count(array_filter($input['internos'], fn ($intern) => $intern['habilitado']));
            $capacity = array_sum(array_map(fn ($place) => $place['capacidad'] - $place['reserva'], $input['plazas']));
            $diagnostic = $capacity < $eligible ? 'CAPACIDAD_INSUFICIENTE_COMPROBADA' : (count($result['pendientes']) ? 'SIN_SOLUCION_COMPLETA_EN_EL_LIMITE' : 'MEJOR_SOLUCION_ENCONTRADA');
            DB::table('optimization_runs')->where('id', $run->id)->where('execution_status', 'ejecutando')->update(['result' => json_encode($result, JSON_THROW_ON_ERROR), 'execution_status' => 'completada', 'plan_status' => 'calculado', 'status' => 'propuesta', 'duration_ms' => (int) ((hrtime(true) - $start) / 1000000), 'diagnostic' => $diagnostic, 'finished_at' => now(), 'updated_at' => now()]);
        } catch (\Throwable) {
            DB::table('optimization_runs')->where('id', $run->id)->where('execution_status', 'ejecutando')->update(['execution_status' => 'fallida', 'diagnostic' => 'CALCULO_FALLIDO_O_RESPUESTA_INVALIDA: revisa los datos y reintenta.', 'finished_at' => now(), 'updated_at' => now()]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        DB::table('optimization_runs')->where('id', $this->runId)->whereIn('execution_status', ['en_cola', 'ejecutando'])->update(['execution_status' => 'fallida', 'diagnostic' => 'Worker interrumpido. Reintento autorizado disponible.', 'updated_at' => now()]);
    }
}
