<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\MarkovService;
use App\Services\OperationsDecisionService;
use App\Services\QueueingService;
use App\Services\RotationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationsResearchController extends Controller
{
    public function __construct(
        private readonly QueueingService $queueing,
        private readonly MarkovService $markov,
        private readonly OperationsDecisionService $decisions,
    ) {}

    public function buildPayload(?int $hospitalId = null, ?int $cycleId = null): array
    {
        $hospitals = DB::table('hospitals')
            ->whereNull('deleted_at')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $selectedHospital = $hospitalId
            ? $hospitals->firstWhere('id', $hospitalId)
            : $hospitals->first();

        if (! $selectedHospital) {
            $selectedHospital = (object) [
                'id' => null,
                'name' => 'Red Hospitalaria UNIFRANZ La Paz (referencia)',
                'tutors_count' => 3,
                'lambda_e1' => 8.0,
                'lambda_e2' => 10.0,
                'lambda_e3' => 11.0,
                'mu_without_interns' => 4.0,
                'mu_with_interns' => 5.0,
            ];
        }

        $enabledInterns = DB::table('users')
            ->whereNull('deleted_at')
            ->where('role', 'interno')
            ->where('active', true)
            ->where('eligible', true)
            ->count();

        $placesQuery = DB::table('places')->whereNull('deleted_at');
        if ($cycleId) {
            $placesQuery->where('cycle_id', $cycleId);
        }
        $places = $placesQuery->get();

        $offeredCapacity = (int) $places->sum('capacity');
        $reserveCapacity = (int) $places->sum('reserve');
        $usableCapacity = max(0, $offeredCapacity - $reserveCapacity);

        $hospitalCapacity = $selectedHospital->id
            ? (int) $places->where('hospital_id', $selectedHospital->id)->sum('capacity')
            : $offeredCapacity;

        $applicationsCount = DB::table('applications')
            ->when($cycleId, fn ($q) => $q->where('cycle_id', $cycleId))
            ->count();

        $monthlyStates = DB::table('monthly_network_states')
            ->orderBy('period_month')
            ->get();

        $colas = $this->queueing->compareHospital($selectedHospital, $enabledInterns, $hospitalCapacity);
        $markov = $this->markov->analyze($monthlyStates->all());
        $decisiones = $this->decisions->evaluate(
            $markov['estacionaria'],
            $colas,
            [
                'plazas_ofertadas' => $offeredCapacity,
                'cupos_utilizables' => $usableCapacity,
                'cupos_reserva' => $reserveCapacity,
                'postulantes' => $applicationsCount,
                'internos_habilitados' => $enabledInterns,
            ]
        );

        return [
            'generado_en' => now()->toIso8601String(),
            'hospitales_disponibles' => $hospitals->map(fn ($h) => [
                'id' => $h->id,
                'name' => $h->name,
                'tutors_count' => (int) ($h->tutors_count ?? 3),
            ])->values()->all(),
            'resumen_bd' => [
                'hospitales_activos' => $hospitals->count(),
                'internos_habilitados' => $enabledInterns,
                'plazas_ofertadas' => $offeredCapacity,
                'cupos_reserva' => $reserveCapacity,
                'postulaciones' => $applicationsCount,
                'meses_markov' => $monthlyStates->count(),
            ],
            'colas' => $colas,
            'decisiones' => $decisiones,
            'markov' => $markov,
            'historial_mensual' => $monthlyStates->reverse()->take(12)->values()->map(fn ($row) => [
                'id' => $row->id,
                'year_month' => $row->period_month,
                'state' => $row->state,
                'label' => MarkovService::STATE_LABELS[$row->state] ?? $row->state,
                'notes' => $row->notes,
            ])->all(),
        ];
    }

    public function index(Request $request): View
    {
        $hospitalId = $request->integer('hospital_id') ?: null;
        $cycleId = $request->integer('cycle_id') ?: null;
        $io = $this->buildPayload($hospitalId, $cycleId);

        $optimizableCycles = DB::table('cycles')
            ->whereNull('cycles.deleted_at')
            ->join('specialties', 'specialties.id', '=', 'cycles.specialty_id')
            ->whereIn('cycles.status', ['cerrada', 'publicada'])
            ->where('cycles.starts_on', '>', now()->toDateString())
            ->select('cycles.*', 'specialties.name as specialty')
            ->orderBy('cycles.starts_on')
            ->get();

        return view('operations-research', compact('io', 'optimizableCycles'));
    }

    public function dashboardApi(Request $request): JsonResponse
    {
        $hospitalId = $request->integer('hospital_id') ?: null;
        $cycleId = $request->integer('cycle_id') ?: null;

        return response()->json($this->buildPayload($hospitalId, $cycleId));
    }

    public function storeMonthlyState(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'state' => ['required', Rule::in(MarkovService::STATES)],
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($data) {
            $existing = DB::table('monthly_network_states')
                ->where('period_month', $data['year_month'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                DB::table('monthly_network_states')
                    ->where('id', $existing->id)
                    ->update([
                        'state' => $data['state'],
                        'notes' => $data['notes'] ?? null,
                        'recorded_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('monthly_network_states')->insert([
                    'period_month' => $data['year_month'],
                    'state' => $data['state'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Audit::record('markov.cierre_mensual', [
                'mes' => $data['year_month'],
                'estado' => $data['state'],
            ]);
        });

        return back()->with('status', "Cierre mensual {$data['year_month']} ({$data['state']}) registrado. Matriz de Markov y semáforo actualizados.");
    }

    public function applyPolicyA3(Request $request, RotationService $rotationService, DecisionAnalysis $analysis, ?int $cycle = null): RedirectResponse
    {
        $cycleId = $cycle ?? $request->integer('cycle_id');
        $row = $cycleId ? DB::table('cycles')->whereNull('deleted_at')->find($cycleId) : null;
        abort_unless($row && in_array($row->status, ['cerrada', 'publicada'], true) && now()->lt($row->starts_on), 409, 'La convocatoria debe estar cerrada o publicada y no haber iniciado.');

        $matrix = [
            'A1' => ['E1' => 90, 'E2' => 68, 'E3' => 40],
            'A2' => ['E1' => 82, 'E2' => 74, 'E3' => 48],
            'A3' => ['E1' => 90, 'E2' => 86, 'E3' => 68],
        ];
        $result = $analysis->calculate($matrix, 0.5, 'benefits');
        $result['source'] = 'Matriz Maximin (Sprint 3 · Investigación Operativa)';

        $studyId = DB::transaction(function () use ($row, $matrix, $result) {
            return DB::table('scenario_studies')->insertGetId([
                'name' => 'Política A3 Maximin · '.$row->name,
                'alpha' => 0.500,
                'matrix' => json_encode($matrix),
                'result' => json_encode($result),
                'execution_status' => 'completada',
                'chosen' => 'A3',
                'justification' => 'Política A3 recomendada por criterio Maximin (peor escenario 68/100) con reserva para Brigadas Rotatorias de Contingencia.',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $runId = $rotationService->generate($row->id, DecisionAnalysis::POLICIES['A3']);
        Audit::record('optimizacion.politica_a3_maximin', [
            'propuesta' => $runId,
            'estudio' => $studyId,
            'convocatoria' => $row->id,
            'alternativa' => 'A3',
        ]);

        return redirect()->to(route('rotations').'#propuestas')
            ->with('status', "Política A3 (Maximin) aplicada. Propuesta #$runId encolada con reserva de Brigadas Rotatorias de Contingencia.");
    }
}
