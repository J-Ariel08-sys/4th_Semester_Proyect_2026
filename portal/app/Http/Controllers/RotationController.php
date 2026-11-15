<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RotationController extends Controller
{
    public function index(): View
    {
        $cycles = DB::table('cycles')->join('specialties', 'specialties.id', '=', 'cycles.specialty_id')->select('cycles.*', 'specialties.name as specialty')->orderByDesc('cycles.id')->get();
        $places = DB::table('places')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->select('places.*', 'hospitals.name as hospital')->get()->groupBy('cycle_id');
        $applications = DB::table('applications')->where('user_id', auth()->id())->get()->keyBy('cycle_id');
        $runs = in_array(auth()->user()->role, ['administrador', 'coordinador']) ? DB::table('optimization_runs')->orderByDesc('id')->limit(30)->get() : collect();
        $studies = DB::table('scenario_studies')->whereNotNull('chosen')->get();

        $internNames = in_array(auth()->user()->role, ['administrador', 'coordinador']) ? DB::table('users')->where('role', 'interno')->pluck('name', 'id') : collect();
        $placeNames = $places->flatten(1)->keyBy('id');

        return view('rotations', compact('cycles', 'places', 'applications', 'runs', 'studies', 'internNames', 'placeNames'));
    }

    public function apply(Request $request, int $cycle): RedirectResponse
    {
        $data = $request->validate(['preferences' => 'required|array|min:1|max:500', 'preferences.*' => 'nullable|integer|distinct']);
        $preferences = array_values(array_filter($data['preferences'], fn ($p) => $p !== null));
        DB::transaction(function () use ($cycle, $preferences) {
            $user = DB::table('users')->where('id', auth()->id())->lockForUpdate()->first();
            $row = DB::table('cycles')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row, 404);
            if (! $user->eligible || ! $user->active || $row->status !== 'abierta' || now()->lt($row->opens_at) || now()->gt($row->closes_at)) {
                throw ValidationException::withMessages(['preferences' => 'No estás habilitado o la postulación está cerrada.']);
            }
            if (! $preferences || DB::table('places')->where('cycle_id', $cycle)->whereIn('id', $preferences)->count() !== count($preferences)) {
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

    public function generate(Request $request, RotationService $service, int $cycle): RedirectResponse
    {
        $data = $request->validate(['study_id' => 'required|exists:scenario_studies,id']);
        $study = DB::table('scenario_studies')->find($data['study_id']);
        abort_unless($study->chosen, 422, 'Primero aprueba una política en escenarios.');
        $id = $service->generate($cycle, DecisionAnalysis::POLICIES[$study->chosen]);
        Audit::record('optimizacion.politica', ['propuesta' => $id, 'estudio' => $study->id, 'alternativa' => $study->chosen]);

        return back()->with('status', "Propuesta #$id generada. Revisa resultados y pendientes antes de aprobar.");
    }

    public function approve(RotationService $service, int $run): RedirectResponse
    {
        $service->approve($run);

        return back()->with('status', 'Asignaciones aprobadas y publicadas.');
    }
}
