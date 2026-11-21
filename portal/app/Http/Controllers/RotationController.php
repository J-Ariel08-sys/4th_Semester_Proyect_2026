<?php

namespace App\Http\Controllers;

use App\Jobs\OptimizeRotations;
use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RotationController extends Controller
{
    public function index(): View
    {
        $cycles = DB::table('cycles')->whereNull('cycles.deleted_at')->join('specialties', 'specialties.id', '=', 'cycles.specialty_id')->select('cycles.*', 'specialties.name as specialty')->orderByDesc('cycles.id')->get();
        $today = now()->toDateString();
        $upcomingCycles = $cycles->where('starts_on', '>=', $today)->sortBy('starts_on');
        $pastCycles = $cycles->where('starts_on', '<', $today);
        $places = DB::table('places')->whereNull('places.deleted_at')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->select('places.*', 'hospitals.name as hospital')->get()->groupBy('cycle_id');
        $applications = DB::table('applications')->where('user_id', auth()->id())->get()->keyBy('cycle_id');
        $runs = (auth()->user()->managesAcademics()) ? DB::table('optimization_runs')->orderByDesc('id')->limit(30)->get() : collect();
        $studies = DB::table('scenario_studies')->whereNotNull('chosen')->get();

        $internNames = (auth()->user()->managesAcademics()) ? DB::table('users')->whereNull('deleted_at')->where('role', 'interno')->pluck('name', 'id') : collect();
        $placeNames = $places->flatten(1)->keyBy('id');

        $received = auth()->user()->managesAcademics() ? DB::table('applications')->join('users', 'users.id', '=', 'applications.user_id')->select('applications.*', 'users.name')->orderByDesc('applications.id')->get() : collect();

        return view('rotations', compact('received', 'cycles', 'upcomingCycles', 'pastCycles', 'places', 'applications', 'runs', 'studies', 'internNames', 'placeNames'));
    }

    public function apply(Request $request, int $cycle): RedirectResponse
    {
        $data = $request->validate(['preferences' => 'required|array|min:1|max:500', 'preferences.*' => 'nullable|integer|distinct']);
        $preferences = array_values(array_filter($data['preferences'], fn ($p) => $p !== null));
        DB::transaction(function () use ($cycle, $preferences) {
            $user = DB::table('users')->where('id', auth()->id())->lockForUpdate()->first();
            $row = DB::table('cycles')->whereNull('deleted_at')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row, 404);
            if (($row->cohort_id && ($row->cohort_id !== $user->cohort_id || ! DB::table('cohorts')->where('id', $row->cohort_id)->whereNull('deleted_at')->where('active', true)->exists())) || ! $user->eligible || ! $user->active || $row->status !== 'abierta' || now()->lt($row->opens_at) || now()->gte($row->closes_at) || now()->gte($row->starts_on)) {
                throw ValidationException::withMessages(['preferences' => 'No estás habilitado o la postulación está cerrada.']);
            }
            if (! $preferences || DB::table('places')->whereNull('deleted_at')->where('cycle_id', $cycle)->whereIn('id', $preferences)->count() !== count($preferences)) {
                throw ValidationException::withMessages(['preferences' => 'Selecciona plazas distintas de esta convocatoria.']);
            }
            if (DB::table('applications')->where('cycle_id', $cycle)->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['preferences' => 'Tu postulación ya fue registrada y está cerrada a cambios.']);
            }
            DB::table('applications')->insert(['cycle_id' => $cycle, 'user_id' => $user->id, 'preferences' => json_encode($preferences), 'receipt' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('postulacion.registrada', ['convocatoria' => $cycle]);
        });

        return back()->with('status', 'Postulación registrada. Conserva tu comprobante.');
    }

    public function exceptional(Request $request, int $cycle): RedirectResponse
    {
        $data = $request->validate(['user_id' => 'required|integer|exists:users,id', 'preferences' => 'required|array|min:1|max:500', 'preferences.*' => 'required|integer|distinct', 'reason' => 'required|string|min:20|max:2000']);
        DB::transaction(function () use ($cycle, $data) {
            $user = DB::table('users')->where('id', $data['user_id'])->lockForUpdate()->first();
            $row = DB::table('cycles')->whereNull('deleted_at')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row && $row->status === 'cerrada' && now()->lt($row->starts_on), 409, 'La excepción requiere un bloque futuro con postulaciones cerradas.');
            if ($user->role !== 'interno' || ! $user->active || ! $user->eligible || ! $user->access_approved_at || ($row->cohort_id && $row->cohort_id !== $user->cohort_id) || DB::table('places')->whereNull('deleted_at')->where('cycle_id', $cycle)->whereIn('id', $data['preferences'])->count() !== count($data['preferences'])) {
                throw ValidationException::withMessages(['preferences' => 'Interno u opciones incompatibles.']);
            }
            $old = DB::table('applications')->where('cycle_id', $cycle)->where('user_id', $user->id)->lockForUpdate()->first();
            if ($old) {
                DB::table('application_versions')->insert(['application_id' => $old->id, 'preferences' => $old->preferences, 'receipt' => $old->receipt, 'changed_by' => auth()->id(), 'reason' => $data['reason'], 'created_at' => $old->created_at]);
                DB::table('applications')->where('id', $old->id)->update(['preferences' => json_encode(array_values($data['preferences'])), 'receipt' => (string) Str::uuid(), 'updated_at' => now()]);
            } else {
                DB::table('applications')->insert(['cycle_id' => $cycle, 'user_id' => $user->id, 'preferences' => json_encode(array_values($data['preferences'])), 'receipt' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
            }
            Audit::record('postulacion.excepcion', ['convocatoria' => $cycle, 'usuario' => $user->id, 'motivo' => $data['reason']]);
        });

        return back()->with('status', 'Excepción registrada con comprobante nuevo e historial. Recalcula las propuestas afectadas.');
    }

    public function generate(Request $request, RotationService $service, int $cycle): RedirectResponse
    {
        $data = $request->validate(['study_id' => 'required|exists:scenario_studies,id']);
        $study = DB::table('scenario_studies')->find($data['study_id']);
        abort_unless($study->chosen, 422, 'Primero aprueba una política en escenarios.');
        $id = $service->generate($cycle, DecisionAnalysis::POLICIES[$study->chosen]);
        Audit::record('optimizacion.politica', ['propuesta' => $id, 'estudio' => $study->id, 'alternativa' => $study->chosen]);

        return back()->with('status', "Ejecución #$id registrada. Consulta su estado; el cálculo se procesa en la cola.");
    }

    public function status(int $run): JsonResponse
    {
        $row = DB::table('optimization_runs')->where('id', $run)->first(['id', 'execution_status', 'plan_status', 'diagnostic', 'duration_ms']);
        abort_unless($row, 404);

        return response()->json($row);
    }

    public function review(int $run): RedirectResponse
    {
        $changed = DB::table('optimization_runs')->where('id', $run)->where('execution_status', 'completada')->where('plan_status', 'calculado')->update(['plan_status' => 'en_revision', 'updated_at' => now()]);
        abort_unless($changed, 409);
        Audit::record('plan.en_revision', ['id' => $run]);

        return back()->with('status', 'Propuesta en revisión. Verifica asignaciones y conflictos.');
    }

    public function cancel(int $run): RedirectResponse
    {
        $changed = DB::table('optimization_runs')->where('id', $run)->whereIn('execution_status', ['en_cola', 'ejecutando'])->update(['execution_status' => 'cancelada', 'diagnostic' => 'Cancelada por Coordinación.', 'updated_at' => now()]);
        abort_unless($changed, 409);
        Audit::record('optimizacion.cancelada', ['id' => $run]);

        return back()->with('status', 'Ejecución cancelada; sus resultados no se publicarán.');
    }

    public function retry(int $run): RedirectResponse
    {
        DB::transaction(function () use ($run) {
            $row = DB::table('optimization_runs')->where('id', $run)->lockForUpdate()->first();
            abort_unless($row && $row->execution_status === 'fallida', 409);
            DB::table('optimization_runs')->where('id', $run)->update(['execution_status' => 'en_cola', 'diagnostic' => null, 'updated_at' => now()]);
            OptimizeRotations::dispatch($run)->onQueue('optimization')->afterCommit();
            Audit::record('optimizacion.reintentada', ['id' => $run]);
        });

        return back()->with('status', 'Ejecución encolada nuevamente con la misma instantánea.');
    }

    public function publish(RotationService $service, int $run): RedirectResponse
    {
        $service->approve($run, true);

        return back()->with('status', 'Asignaciones publicadas.');
    }

    public function approve(RotationService $service, int $run): RedirectResponse
    {
        $service->approve($run, false);

        return back()->with('status', 'Plan aprobado. Ya puedes publicarlo tras verificar su vigencia.');
    }
}
