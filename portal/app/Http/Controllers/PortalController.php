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
                ['Postulación y rotes', 'rotations', 'Revisa postulaciones, calcula una propuesta y publica las asignaciones.'],
                ['Escenarios y decisiones', 'scenarios', 'Compara políticas antes de elegir cómo distribuir los cupos.'],
                ['Seguimiento clínico', 'clinical', 'Consulta asistencia y evaluaciones; resuelve solicitudes.'],
            ],
            'tutor' => [
                ['Mis internos', 'clinical', 'Registra asistencia, evalúa competencias y confirma guardias realizadas.'],
                ['Disponibilidad y guardias', 'shifts', 'Indica cuándo puedes supervisar y consulta tu calendario.'],
                ['Mis reportes', 'reports', 'Genera la libreta de los internos bajo tu supervisión.'],
            ],
            default => [
                ['Oferta y postulación', 'rotations', 'Ordena tus preferencias y conserva tu comprobante.'],
                ['Mis rotes y solicitudes', 'clinical', 'Consulta tu seguimiento y registra una solicitud.'],
                ['Mi libreta y documentos', 'reports', 'Revisa tus resultados y descarga tus documentos.'],
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
