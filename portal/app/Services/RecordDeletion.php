<?php

namespace App\Services;

use App\Http\Controllers\ManagementController;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordDeletion
{
    public function delete(string $resource, int $id, string $reason): void
    {
        $table = ManagementController::definitions()[$resource]['table'];
        DB::transaction(function () use ($resource, $table, $id, $reason) {
            if ($table === 'users') {
                DB::table('users')->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get();
            }
            $record = DB::table($table)->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($record, 404);
            if (in_array($resource, ['internos', 'tutores'], true)) {
                abort_unless($record->role === ($resource === 'internos' ? 'interno' : 'tutor'), 404);
            }
            if ($resource === 'habilitaciones') {
                abort_if($record->role === 'administrador', 403);
            }
            $changes = ['deleted_at' => now(), 'updated_at' => now()];
            if ($table === 'users') {
                if ($record->id === auth()->id()) {
                    throw ValidationException::withMessages(['eliminar' => 'No puedes eliminar la cuenta con la que estás trabajando.']);
                }
                if ($record->role === 'administrador' && $record->active && DB::table('users')->whereNull('deleted_at')->where('active', true)->where('role', 'administrador')->count() <= 1) {
                    throw ValidationException::withMessages(['eliminar' => 'Debe conservarse al menos un administrador activo.']);
                }
                $changes += ['active' => false, 'eligible' => false, 'access_approved_at' => null, ...AccountAccess::revoke($record)];
            } elseif (in_array($table, ['hospitals', 'cohorts'], true)) {
                $changes['active'] = false;
            } elseif ($table === 'cycles') {
                if ($record->status !== 'publicada') {
                    $changes['status'] = 'eliminada';
                }
                DB::table('optimization_runs')->where('cycle_id', $id)->whereIn('execution_status', ['en_cola', 'ejecutando'])->update(['execution_status' => 'cancelada', 'diagnostic' => 'Convocatoria eliminada.', 'updated_at' => now()]);
            }
            DB::table($table)->where('id', $id)->update($changes);
            $snapshot = array_diff_key((array) $record, array_flip(['password', 'remember_token']));
            Audit::record('gestion.eliminada', ['recurso' => $resource, 'id' => $id, 'motivo' => $reason, 'tipo' => 'logica', 'registro' => $snapshot]);
        });
    }
}
