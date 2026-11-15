<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\ShiftPlanner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index(): View
    {
        $manager = in_array(auth()->user()->role, ['administrador', 'coordinador']);
        $assignments = ClinicalController::assignments()->get();
        $tutors = DB::table('users')->where('role', 'tutor')->where('active', true)->get();
        $availability = DB::table('availabilities')->join('users', 'users.id', '=', 'availabilities.tutor_id')->when(! $manager, fn ($q) => $q->where('tutor_id', auth()->id()))->select('availabilities.*', 'users.name')->orderBy('starts_at')->get();
        $shifts = DB::table('shifts')->join('shift_batches', 'shift_batches.id', '=', 'shifts.batch_id')->join('assignments', 'assignments.id', '=', 'shifts.assignment_id')->join('users as intern', 'intern.id', '=', 'assignments.user_id')->leftJoin('users as tutor', 'tutor.id', '=', 'shifts.tutor_id')
            ->when(! $manager, fn ($q) => $q->where('shift_batches.status', 'aprobada')->where(auth()->user()->role === 'interno' ? 'assignments.user_id' : 'shifts.tutor_id', auth()->id()))
            ->select('shifts.*', 'shift_batches.name as batch', 'shift_batches.status', 'intern.name as intern', 'tutor.name as tutor')->orderByDesc('shifts.starts_at')->get();
        $batches = $manager ? DB::table('shift_batches')->orderByDesc('id')->get() : collect();

        return view('shifts', compact('assignments', 'tutors', 'availability', 'shifts', 'batches', 'manager'));
    }

    public function availability(Request $request): RedirectResponse
    {
        $data = $request->validate(['tutor_id' => 'required|exists:users,id', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at']);
        abort_unless(auth()->user()->role !== 'tutor' || (int) $data['tutor_id'] === auth()->id(), 403);
        DB::transaction(function () use ($data) {
            $tutor = DB::table('users')->where('id', $data['tutor_id'])->lockForUpdate()->first();
            abort_unless($tutor->role === 'tutor' && $tutor->active, 422);
            DB::table('availabilities')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('disponibilidad.registrada', ['tutor' => $tutor->id]);
        });

        return back()->with('status', 'Disponibilidad registrada.');
    }

    public function generate(Request $request, ShiftPlanner $planner): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'rest_hours' => 'required|integer|between:0,48', 'slots' => 'required|array|min:1|max:100', 'slots.*.assignment_id' => 'required|integer|exists:assignments,id', 'slots.*.starts_at' => 'required|date', 'slots.*.ends_at' => 'required|date']);
        $planned = $planner->plan($data['slots'], (int) $data['rest_hours']);
        DB::transaction(function () use ($data, $planned) {
            $id = DB::table('shift_batches')->insertGetId(['name' => $data['name'], 'rest_hours' => $data['rest_hours'], 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($planned as $slot) {
                unset($slot['user_id']);
                DB::table('shifts')->insert([...$slot, 'batch_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
            Audit::record('guardias.propuestas', ['lote' => $id]);
        });

        return back()->with('status', 'Calendario propuesto. Revisa los turnos pendientes y la carga antes de aprobar.');
    }

    public function approve(ShiftPlanner $planner, int $batch): RedirectResponse
    {
        DB::transaction(function () use ($planner, $batch) {
            DB::table('users')->orderBy('id')->lockForUpdate()->get();
            $row = DB::table('shift_batches')->where('id', $batch)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'propuesta', 409);
            $slots = DB::table('shifts')->where('batch_id', $batch)->orderBy('starts_at')->orderBy('assignment_id')->get()->map(fn ($s) => (array) $s)->all();
            $planner->validatePlan($slots, $row->rest_hours, $batch);
            DB::table('shift_batches')->where('id', $batch)->update(['status' => 'aprobada', 'updated_at' => now()]);
            Audit::record('guardias.aprobadas', ['lote' => $batch]);
        });

        return back()->with('status', 'Calendario aprobado.');
    }

    public function performed(int $shift): RedirectResponse
    {
        DB::transaction(function () use ($shift) {
            $row = DB::table('shifts')->where('id', $shift)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless(auth()->user()->role !== 'tutor' || $row->tutor_id === auth()->id(), 403);
            abort_unless(DB::table('shift_batches')->where('id', $row->batch_id)->where('status', 'aprobada')->exists() && now()->gte($row->ends_at), 422, 'Solo se confirma una guardia aprobada y finalizada.');
            abort_if($row->performed, 409, 'Esta guardia ya fue registrada.');
            DB::table('shifts')->where('id', $shift)->update(['performed' => true, 'updated_at' => now()]);
            Audit::record('guardia.cumplida', ['id' => $shift]);
        });

        return back()->with('status', 'Cumplimiento de guardia registrado.');
    }
}
