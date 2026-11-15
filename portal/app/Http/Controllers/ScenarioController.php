<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScenarioController extends Controller
{
    public function index(): View
    {
        return view('scenarios', ['studies' => DB::table('scenario_studies')->orderByDesc('id')->paginate(10), 'cycles' => DB::table('cycles')->whereIn('status', ['cerrada', 'publicada'])->get()]);
    }

    public function store(Request $request, DecisionAnalysis $analysis): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'alpha' => 'required|numeric|between:0,1', 'matrix' => 'required|array:A1,A2,A3', 'matrix.*' => 'required|array|size:3', 'matrix.*.*' => 'required|numeric|between:-1000000,1000000']);
        $matrix = [];
        foreach (array_keys(DecisionAnalysis::POLICIES) as $key) {
            $matrix[$key] = array_map('floatval', $data['matrix'][$key] ?? []);
        }
        $result = $analysis->calculate($matrix, (float) $data['alpha']);
        DB::transaction(function () use ($data, $matrix, $result) {
            $id = DB::table('scenario_studies')->insertGetId(['name' => $data['name'], 'alpha' => $data['alpha'], 'matrix' => json_encode($matrix), 'result' => json_encode($result), 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('escenario.calculado', ['id' => $id]);
        });

        return back()->with('status', 'Análisis y sensibilidad calculados.');
    }

    public function choose(Request $request, int $study): RedirectResponse
    {
        $data = $request->validate(['chosen' => ['required', Rule::in(array_keys(DecisionAnalysis::POLICIES))], 'justification' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($study, $data) {
            $row = DB::table('scenario_studies')->where('id', $study)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_if($row->chosen !== null, 409, 'La decisión ya está aprobada; crea otro estudio para revisarla.');
            DB::table('scenario_studies')->where('id', $study)->update([...$data, 'updated_at' => now()]);
            Audit::record('politica.aprobada', ['estudio' => $study, ...$data]);
        });

        return back()->with('status', 'Política aprobada y disponible para la optimización.');
    }

    public function simulate(Request $request, RotationService $optimizer, DecisionAnalysis $analysis): RedirectResponse
    {
        $data = $request->validate(['cycle_id' => 'required|exists:cycles,id', 'name' => 'required|string|max:150', 'alpha' => 'required|numeric|between:0,1']);
        $base = $optimizer->input((int) $data['cycle_id'], DecisionAnalysis::POLICIES['A1']);
        if (! $base['internos']) {
            throw ValidationException::withMessages(['cycle_id' => 'La convocatoria no tiene postulantes.']);
        }
        $matrix = [];
        $evidence = [];
        foreach (DecisionAnalysis::POLICIES as $alternative => $policy) {
            $matrix[$alternative] = [];
            foreach ([1.0, 0.75, 0.5] as $factor) {
                $input = $base;
                $input['politica'] = $policy;
                $input['segundos'] = 1;
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
                $evidence[$alternative][] = ['factor' => $factor, 'asignados' => count($result['asignaciones']), 'pendientes' => count($result['pendientes']), 'primeras' => $result['primerasPreferencias'], 'utilidad' => $utility];
            }
        }
        $result = $analysis->calculate($matrix, (float) $data['alpha']);
        $result['simulation'] = ['cycle_id' => (int) $data['cycle_id'], 'input_hash' => $optimizer->hash($base), 'input' => $base, 'metrics' => $evidence, 'formula' => '100·cobertura + 25·primeras preferencias / habilitados − 10·diferencia de ocupación relativa'];
        DB::transaction(function () use ($data, $matrix, $result) {
            $id = DB::table('scenario_studies')->insertGetId(['name' => $data['name'], 'alpha' => $data['alpha'], 'matrix' => json_encode($matrix), 'result' => json_encode($result), 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('escenario.simulado', ['id' => $id, 'convocatoria' => $data['cycle_id']]);
        });

        return back()->with('status','Se calcularon nueve combinaciones de política y capacidad. Los cupos publicados permanecen vigentes.');
    }
}
