<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    public function index(): View
    {
        if (auth()->user()->role === 'administrador') {
            return view('admin-dashboard', ['users' => DB::table('users')->whereNull('deleted_at')->count(), 'pending' => DB::table('users')->whereNull('deleted_at')->whereNull('activated_at')->count(), 'approvalPending' => DB::table('users')->whereNull('deleted_at')->where('role', '!=', 'administrador')->whereNull('access_approved_at')->count(), 'failed' => DB::table('audit_logs')->where('action', 'sesion.fallida')->where('created_at', '>=', now()->subDay())->count()]);
        }
        $manager = (auth()->user()->managesAcademics());
        $assignments = ClinicalController::assignments()->get();
        $stats = [
            'Rotes asignados' => $assignments->count(),
            $manager ? 'Postulaciones abiertas' : 'Guardias por confirmar' => $manager
                ? DB::table('cycles')->where('status', 'abierta')->where('opens_at', '<=', now())->where('closes_at', '>=', now())->count()
                : DB::table('shifts')->join('shift_batches', 'shift_batches.id', '=', 'shifts.batch_id')->where('shift_batches.status', 'aprobada')->whereIn('assignment_id', $assignments->pluck('id'))->where('performed', false)->count(),
            'Incidencias abiertas' => DB::table('incidents')->where('status', 'abierta')->whereIn('assignment_id', $assignments->pluck('id'))->count(),
        ];
        $actions = match (auth()->user()->role) {
            'coordinador' => [
                ['Postulación y rotes', 'rotations', 'Revisar y publicar asignaciones.'],
                ['Escenarios', 'scenarios', 'Comparar políticas de asignación.'],
                ['Seguimiento clínico', 'clinical', 'Revisar evaluaciones e incidencias.'],
            ],
            'tutor' => [
                ['Mis internos', 'clinical', 'Registrar asistencia y notas.'],
                ['Disponibilidad y guardias', 'shifts', 'Indicar disponibilidad y ver turnos.'],
                ['Mis reportes', 'reports', 'Descargar libretas.'],
            ],
            default => [
                ['Postulaciones', 'rotations', 'Elegir plazas y ver comprobantes.'],
                ['Mis rotes y solicitudes', 'clinical', 'Ver tu seguimiento o enviar una solicitud.'],
                ['Mi libreta', 'reports', 'Consultar resultados y descargar documentos.'],
            ],
        };

        $notifications = auth()->user()->notifications()->limit(8)->get();

        return view('dashboard', compact('manager', 'assignments', 'stats', 'actions', 'notifications'));
    }

    public function audit(): View
    {
        return view('audit', ['entries' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')->select('audit_logs.*', 'users.name')->when(request()->boolean('access'), fn ($q) => $q->where('action', 'like', 'sesion.%'))->orderByDesc('audit_logs.id')->paginate(40)]);
    }

    public function readNotification(string $notification): RedirectResponse
    {
        $row = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $row->markAsRead();

        return back()->with('status', 'Notificación marcada como leída.');
    }
}
