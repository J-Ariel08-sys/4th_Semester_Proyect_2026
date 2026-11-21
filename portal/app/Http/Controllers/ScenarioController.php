<?php

namespace App\Http\Controllers;

use App\Jobs\ComputeScenario;
use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use App\Services\ScenarioWorkbook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ScenarioController extends Controller
{
    public function index(): View
    {
        return view('scenarios', ['tutors' => DB::table('users')->where('role', 'tutor')->where('active', true)->get(), 'studies' => DB::table('scenario_studies')->orderByDesc('id')->paginate(10), 'cycles' => DB::table('cycles')->whereNull('deleted_at')->whereIn('status', ['cerrada', 'publicada'])->get()]);
    }

    public function store(Request $request, DecisionAnalysis $analysis): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'alpha' => 'required|numeric|between:0,1', 'orientation' => ['sometimes', Rule::in(['benefits', 'costs'])], 'matrix_json' => 'nullable|json|max:100000', 'matrix' => 'required_without:matrix_json|array|max:50', 'matrix.*' => 'required|array|min:1|max:50', 'matrix.*.*' => 'required|numeric|between:-1000000,1000000']);
        $matrix = ! empty($data['matrix_json']) ? json_decode($data['matrix_json'], true) : $data['matrix'];
        if (! is_array($matrix)) {
            throw ValidationException::withMessages(['matrix' => 'Se requiere un objeto de alternativas y escenarios.']);
        }
        $this->calculate($data, $matrix, $analysis, 'Ejercicio ingresado por Coordinación.');

        return back()->with('status', 'Análisis y sensibilidad calculados.');
    }

    public function import(Request $request, ScenarioWorkbook $workbook, DecisionAnalysis $analysis): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'source' => 'required|string|max:500',
            'alpha' => 'required|numeric|between:0,1',
            'orientation' => ['required', Rule::in(['benefits', 'costs'])],
            'file' => 'required|file|extensions:xlsx|max:2048',
        ]);
        $id = $this->calculate($data, $workbook->matrix($request->file('file')->getRealPath()), $analysis, $data['source']);

        return redirect()->to(route('scenarios').'#estudio-'.$id)->with('status', 'Excel importado. Los criterios ya están calculados.');
    }

    public function template(ScenarioWorkbook $workbook): Response
    {
        return $this->spreadsheet($workbook->template(), 'SICLIN-plantilla-escenarios.xlsx');
    }

    public function export(int $study, string $format, ScenarioWorkbook $workbook): Response
    {
        $row = DB::table('scenario_studies')->where('execution_status', 'completada')->find($study);
        abort_unless($row, 404);
        $sections = $workbook->results($row, json_decode($row->result, true));
        $filename = 'SICLIN-escenario-'.$study;

        if ($format === 'pdf') {
            return Pdf::loadView('scenario-pdf', ['sections' => $sections])->setPaper('a4', 'landscape')->download($filename.'.pdf');
        }

        return $this->spreadsheet($workbook->export($sections), $filename.'.xlsx');
    }

    private function spreadsheet(string $content, string $filename): Response
    {
        return response($content)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    private function calculate(array $data, array $matrix, DecisionAnalysis $analysis, string $source): int
    {
        $result = $analysis->calculate($matrix, (float) $data['alpha'], $data['orientation'] ?? 'benefits');
        $result['source'] = $source;

        return DB::transaction(function () use ($data, $matrix, $result) {
            $id = DB::table('scenario_studies')->insertGetId(['name' => $data['name'], 'alpha' => $data['alpha'], 'matrix' => json_encode($matrix), 'result' => json_encode($result), 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('escenario.calculado', ['id' => $id]);

            return $id;
        });
    }

    public function choose(Request $request, int $study): RedirectResponse
    {
        $data = $request->validate(['chosen' => ['required', Rule::in(array_keys(DecisionAnalysis::POLICIES))], 'justification' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($study, $data) {
            $row = DB::table('scenario_studies')->where('id', $study)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless(array_key_exists($data['chosen'], json_decode($row->matrix, true)), 422, 'La alternativa no figura en la matriz.');
            abort_unless($row->execution_status === 'completada', 409);
            abort_if($row->chosen !== null, 409, 'La decisión ya está aprobada; crea otro estudio para revisarla.');
            DB::table('scenario_studies')->where('id', $study)->update([...$data, 'updated_at' => now()]);
            Audit::record('politica.aprobada', ['estudio' => $study, ...$data]);
        });

        return back()->with('status', 'Política aprobada y disponible para la optimización.');
    }

    public function status(int $study): JsonResponse
    {
        $row = DB::table('scenario_studies')->find($study);
        abort_unless($row, 404);

        return response()->json(['execution_status' => $row->execution_status, 'plan_status' => $row->chosen ? 'política elegida' : 'simulación', 'diagnostic' => $row->diagnostic]);
    }

    public function simulate(Request $request, RotationService $optimizer, DecisionAnalysis $analysis): RedirectResponse
    {
        $data = $request->validate(['cycle_id' => 'required|exists:cycles,id', 'name' => 'required|string|max:150', 'alpha' => 'required|numeric|between:0,1', 'factors' => 'sometimes|array|size:3', 'factors.*' => 'required|numeric|between:0,1', 'limits' => 'sometimes|array|size:3', 'limits.*' => 'required|numeric|between:0,1', 'unavailable' => 'sometimes|array|max:3', 'unavailable.*' => 'array|max:500', 'unavailable.*.*' => 'integer|exists:users,id']);
        $cycle = DB::table('cycles')->whereNull('deleted_at')->find($data['cycle_id']);
        abort_unless($cycle && in_array($cycle->status, ['cerrada', 'publicada']), 409);
        $base = $optimizer->input((int) $data['cycle_id'], DecisionAnalysis::POLICIES['A1']);
        if (! $base['internos']) {
            throw ValidationException::withMessages(['cycle_id' => 'La convocatoria no tiene postulantes.']);
        }
        DB::transaction(function () use ($data, $base) {
            $input = ['base' => $base, 'parameters' => ['cycle_id' => (int) $data['cycle_id'], 'alpha' => (float) $data['alpha'], 'factors' => array_map('floatval', $data['factors'] ?? [1, 0.75, 0.5]), 'limits' => array_map('floatval', $data['limits'] ?? [1, 1, 1]), 'unavailable' => $data['unavailable'] ?? []]];
            $id = DB::table('scenario_studies')->insertGetId(['name' => $data['name'], 'alpha' => $data['alpha'], 'matrix' => '{}', 'result' => '{}', 'input' => json_encode($input), 'execution_status' => 'en_cola', 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            ComputeScenario::dispatch($id)->onQueue('optimization')->afterCommit();
            Audit::record('escenario.encolado', ['id' => $id]);
        });

        return back()->with('status', 'Simulación encolada. Consulta su progreso para revisar las nueve combinaciones.');
    }
}
