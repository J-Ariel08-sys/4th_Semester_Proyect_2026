<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SwapController extends Controller
{
    public function request(Request $request, int $assignment): RedirectResponse
    {
        $data = $request->validate(['target_id' => 'required|integer|exists:assignments,id', 'reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($assignment, $data) {
            $source = DB::table('assignments')->where('id', $assignment)->lockForUpdate()->first();
            abort_unless($source && $source->user_id === auth()->id(), 404);
            $target = DB::table('assignments')->find($data['target_id']);
            if ($source->id === $target->id || $source->cycle_id !== $target->cycle_id || $source->place_id === $target->place_id) {
                throw ValidationException::withMessages(['target_id' => 'La permuta requiere dos internos de la misma convocatoria en hospitales diferentes.']);
            }
            $this->validateAssignments([$source, $target]);
            DB::table('swap_requests')->insert(['source_id' => $source->id, 'target_id' => $target->id, 'source_place_id' => $source->place_id, 'target_place_id' => $target->place_id, 'reason' => $data['reason'], 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('permuta.solicitada', ['origen' => $source->id, 'destino' => $target->id]);
        });

        return back()->with('status', 'Permuta solicitada. Falta el consentimiento de la otra persona y la aprobación de Coordinación.');
    }

    public function consent(Request $request, int $swap): RedirectResponse
    {
        $data = $request->validate(['accept' => 'required|boolean']);
        DB::transaction(function () use ($swap, $data) {
            $row = DB::table('swap_requests')->where('id', $swap)->lockForUpdate()->first();
            abort_unless($row, 404);
            $target = DB::table('assignments')->find($row->target_id);
            abort_unless($target->user_id === auth()->id(), 403);
            abort_unless($row->status === 'solicitada', 409);
            DB::table('swap_requests')->where('id', $swap)->update(['status' => $data['accept'] ? 'consentida' : 'rechazada', 'consented_at' => $data['accept'] ? now() : null, 'updated_at' => now()]);
            Audit::record('permuta.consentimiento', ['id' => $swap, 'acepta' => (bool) $data['accept']]);
        });

        return back()->with('status', 'Respuesta de permuta registrada.');
    }

    private function validateAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $cycle = DB::table('cycles')->find($assignment->cycle_id);
            $place = DB::table('places')->find($assignment->place_id);
            $hospital = DB::table('hospitals')->find($place->hospital_id);
            $user = DB::table('users')->find($assignment->user_id);
            $tutor = DB::table('users')->find($place->tutor_id);
            if (now()->gte($cycle->starts_on) || ! $user->active || ! $user->eligible || ! $tutor->active || ! $hospital->active || $hospital->agreement_until < $cycle->ends_on || DB::table('clinical_records')->where('assignment_id', $assignment->id)->exists() || DB::table('shifts')->where('assignment_id', $assignment->id)->exists()) {
                throw ValidationException::withMessages(['permuta' => 'Solo se permutan rotes futuros sin seguimiento ni guardias, con internos habilitados y convenios vigentes.']);
            }
        }
    }

    public function approve(int $swap): RedirectResponse
    {
        DB::transaction(function () use ($swap) {
            DB::table('users')->orderBy('id')->lockForUpdate()->get();
            $row = DB::table('swap_requests')->where('id', $swap)->lockForUpdate()->first();
            abort_unless($row && $row->status === 'consentida', 409, 'Se requieren ambos consentimientos.');
            $assignments = DB::table('assignments')->whereIn('id', [$row->source_id, $row->target_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $assignments[$row->source_id];
            $target = $assignments[$row->target_id];
            DB::table('cycles')->where('id', $source->cycle_id)->lockForUpdate()->first();
            DB::table('hospitals')->orderBy('id')->lockForUpdate()->get();
            if ($source->place_id !== $row->source_place_id || $target->place_id !== $row->target_place_id) {
                throw ValidationException::withMessages(['permuta' => 'Las asignaciones cambiaron. Solicita una nueva permuta.']);
            }
            $this->validateAssignments([$source, $target]);
            DB::table('assignments')->where('id', $source->id)->update(['place_id' => $target->place_id, 'tutor_id' => $target->tutor_id, 'updated_at' => now()]);
            DB::table('assignments')->where('id', $target->id)->update(['place_id' => $source->place_id, 'tutor_id' => $source->tutor_id, 'updated_at' => now()]);
            DB::table('swap_requests')->where('id', $swap)->update(['status' => 'aprobada', 'updated_at' => now()]);
            Audit::record('permuta.aprobada', ['id' => $swap, 'origen' => $source->place_id, 'destino' => $target->place_id]);
        });

        return back()->with('status','Permuta aprobada. Se conservan los cupos y el historial de la decisión.');
    }
}
