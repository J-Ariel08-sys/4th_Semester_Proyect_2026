<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClinicalController extends Controller
{
    public static function assignments(): Builder
    {
        $query = DB::table('assignments')->join('users', 'users.id', '=', 'assignments.user_id')->join('places', 'places.id', '=', 'assignments.place_id')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->join('cycles', 'cycles.id', '=', 'assignments.cycle_id')->join('specialties', 'specialties.id', '=', 'cycles.specialty_id')
            ->select('assignments.*', 'users.name as intern', 'hospitals.name as hospital', 'cycles.name as cycle', 'cycles.starts_on', 'cycles.ends_on', 'specialties.name as specialty');
        if (auth()->user()->role === 'interno') {
            $query->where('assignments.user_id', auth()->id());
        }
        if (auth()->user()->role === 'tutor') {
            $query->where('assignments.tutor_id', auth()->id());
        }

        return $query;
    }

    public function index(): View
    {
        $assignments = self::assignments()->orderByDesc('assignments.id')->get();
        $ids = $assignments->pluck('id');
        $records = DB::table('clinical_records')->whereIn('assignment_id', $ids)->orderByDesc('date')->get()->groupBy('assignment_id');
        $incidents = DB::table('incidents')->whereIn('assignment_id', $ids)->orderByDesc('id')->get()->groupBy('assignment_id');

        $swaps = DB::table('swap_requests')->join('assignments as source', 'source.id', '=', 'swap_requests.source_id')->join('assignments as target', 'target.id', '=', 'swap_requests.target_id')->when(auth()->user()->role === 'interno', fn ($q) => $q->where(fn ($sub) => $sub->where('source.user_id', auth()->id())->orWhere('target.user_id', auth()->id())))->when(auth()->user()->role === 'tutor', fn ($q) => $q->whereRaw('1=0'))->select('swap_requests.*', 'target.user_id as target_user_id')->orderByDesc('swap_requests.id')->get();

        $availablePlaces = DB::table('places')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->whereIn('cycle_id', $assignments->pluck('cycle_id'))->select('places.*', 'hospitals.name')->get()->groupBy('cycle_id');

        $clinicalShifts = DB::table('shifts')->join('shift_batches', 'shift_batches.id', '=', 'shifts.batch_id')->join('assignments', 'assignments.id', '=', 'shifts.assignment_id')->join('users', 'users.id', '=', 'assignments.user_id')->where('shift_batches.status', 'aprobada')->when(auth()->user()->role === 'interno', fn ($q) => $q->where('assignments.user_id', auth()->id()))->when(auth()->user()->role === 'tutor', fn ($q) => $q->where('shifts.tutor_id', auth()->id()))->select('shifts.*', 'users.name as intern')->orderByDesc('starts_at')->limit(100)->get();

        return view('clinical', compact('assignments', 'records', 'incidents', 'swaps', 'availablePlaces', 'clinicalShifts'));
    }

    public function record(Request $request, int $assignment): RedirectResponse
    {
        $row = self::assignments()->where('assignments.id', $assignment)->first();
        abort_unless($row, 404);
        $data = $request->validate(['date' => 'required|date|after_or_equal:'.$row->starts_on.'|before_or_equal:'.$row->ends_on.'|before_or_equal:today', 'attendance' => ['required', Rule::in(['presente', 'ausente', 'justificada'])], 'grade' => 'nullable|integer|between:0,100', 'notes' => 'nullable|string|max:2000', 'competencies' => 'nullable|array:conocimiento,procedimientos,comunicacion,profesionalismo', 'competencies.*' => 'required_with:competencies|integer|between:0,100']);
        if (! empty($data['competencies'])) {
            if (count($data['competencies']) !== 4) {
                throw ValidationException::withMessages(['competencies' => 'Completa los cuatro criterios de la rúbrica.']);
            }
            $data['grade'] = (int) round(array_sum($data['competencies']) / 4);
            $data['competencies'] = json_encode($data['competencies']);
        }
        DB::transaction(function () use ($assignment, $data) {
            DB::table('assignments')->where('id', $assignment)->lockForUpdate()->first();
            if (DB::table('clinical_records')->where('assignment_id', $assignment)->where('date', $data['date'])->exists()) {
                throw ValidationException::withMessages(['date' => 'Ya existe un registro para ese día. Las correcciones deben documentarse como incidencia.']);
            }
            DB::table('clinical_records')->insert([...$data, 'assignment_id' => $assignment, 'recorded_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('seguimiento.registrado', ['asignacion' => $assignment, 'fecha' => $data['date']]);
        });

        return back()->with('status', 'Seguimiento registrado.');
    }

    public function incident(Request $request, int $assignment): RedirectResponse
    {
        abort_unless(self::assignments()->where('assignments.id', $assignment)->exists(), 404);
        $data = $request->validate(['type' => ['required', Rule::in(['ausencia', 'permuta', 'reasignacion', 'correccion', 'otra'])], 'description' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($assignment, $data) {
            DB::table('incidents')->insert([...$data, 'assignment_id' => $assignment, 'created_by' => auth()->id(), 'status' => 'abierta', 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('incidencia.abierta', ['asignacion' => $assignment, 'tipo' => $data['type']]);
        });

        return back()->with('status', 'Incidencia enviada a Coordinación.');
    }

    public function resolve(Request $request, int $incident): RedirectResponse
    {
        $data = $request->validate(['resolution' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($incident, $data) {
            $row = DB::table('incidents')->where('id', $incident)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'abierta', 409);
            DB::table('incidents')->where('id', $incident)->update([...$data, 'status' => 'resuelta', 'updated_at' => now()]);
            Audit::record('incidencia.resuelta', ['id' => $incident]);
        });

        return back()->with('status', 'Resolución registrada.');
    }

    public function reassign(Request $request, int $assignment): RedirectResponse
    {
        $data = $request->validate(['place_id' => 'required|exists:places,id', 'reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($assignment, $data) {
            DB::table('users')->orderBy('id')->lockForUpdate()->get();
            $row = DB::table('assignments')->where('id', $assignment)->lockForUpdate()->first();
            abort_unless($row, 404);
            $place = DB::table('places')->where('id', $data['place_id'])->lockForUpdate()->first();
            $cycle = DB::table('cycles')->where('id', $row->cycle_id)->lockForUpdate()->first();
            $hospital = DB::table('hospitals')->where('id', $place->hospital_id)->lockForUpdate()->first();
            $tutor = DB::table('users')->find($place->tutor_id);
            if ($place->cycle_id !== $row->cycle_id || $place->id === $row->place_id || ! $hospital->active || ! $tutor->active || $hospital->agreement_until < $cycle->ends_on) {
                throw ValidationException::withMessages(['place_id' => 'La plaza no es compatible o no tiene convenio vigente.']);
            }
            if (DB::table('clinical_records')->where('assignment_id', $assignment)->exists() || DB::table('shifts')->where('assignment_id', $assignment)->exists() || now()->gte($cycle->starts_on)) {
                throw ValidationException::withMessages(['place_id' => 'Los rotes iniciados o con seguimiento/guardias requieren una nueva planificación por periodos; no se modifica su historial.']);
            }
            if (DB::table('assignments')->where('place_id', $place->id)->count() >= $place->capacity - $place->reserve) {
                throw ValidationException::withMessages(['place_id' => 'La plaza no tiene cupos utilizables.']);
            }
            DB::table('assignments')->where('id', $assignment)->update(['place_id' => $place->id, 'tutor_id' => $place->tutor_id, 'updated_at' => now()]);
            Audit::record('asignacion.reasignada', ['id' => $assignment, 'anterior' => $row->place_id, 'nueva' => $place->id, 'motivo' => $data['reason']]);
        });

        return back()->with('status', 'Reasignación autorizada registrada. Las actas anteriores conservan su versión.');
    }
}
