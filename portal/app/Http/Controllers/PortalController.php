<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    public function index(): View
    {
        $manager = in_array(auth()->user()->role, ['administrador', 'coordinador']);
        $assignments = ClinicalController::assignments()->get();
        $stats = ['Rotes asignados' => $assignments->count(), 'Convocatorias abiertas' => DB::table('cycles')->where('status', 'abierta')->count(), 'Hospitales habilitados' => DB::table('hospitals')->where('active', true)->count(), 'Incidencias abiertas' => DB::table('incidents')->where('status', 'abierta')->whereIn('assignment_id', $assignments->pluck('id'))->count()];
        $cycles = DB::table('cycles')->orderByDesc('id')->limit(5)->get();

        $notifications = auth()->user()->notifications()->limit(8)->get();

        return view('dashboard', compact('manager', 'assignments', 'stats', 'cycles', 'notifications'));
    }

    public function audit(): View
    {
        return view('audit', ['entries' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')->select('audit_logs.*', 'users.name')->orderByDesc('audit_logs.id')->paginate(40)]);
    }

    public function readNotification(string $notification): RedirectResponse
    {
        $row = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $row->markAsRead();

        return back()->with('status', 'Notificación marcada como leída.');
    }
}
