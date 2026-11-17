<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    public function index(): View
    {
        if (auth()->user()->role === 'administrador') { return view('admin-dashboard', ['users' => DB::table('users')->count(), 'pending' => DB::table('users')->whereNull('activated_at')->count(), 'failed' => DB::table('audit_logs')->where('action','sesion.fallida')->where('created_at','>=',now()->subDay())->count()]); }
        $manager = (auth()->user()->role === 'coordinador');
        $assignments = ClinicalController::assignments()->get();
        $stats = ['Rotes asignados' => $assignments->count(), 'Convocatorias abiertas' => DB::table('cycles')->where('status', 'abierta')->count(), 'Hospitales habilitados' => DB::table('hospitals')->where('active', true)->count(), 'Incidencias abiertas' => DB::table('incidents')->where('status', 'abierta')->whereIn('assignment_id', $assignments->pluck('id'))->count()];
        $cycles = DB::table('cycles')->orderByDesc('id')->limit(5)->get();

        $notifications = auth()->user()->notifications()->limit(8)->get();

        return view('dashboard', compact('manager', 'assignments', 'stats', 'cycles', 'notifications'));
    }

    public function audit(): View
    {
        return view('audit', ['entries' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')->select('audit_logs.*', 'users.name')->when(request()->boolean('access'), fn ($q) => $q->where('action','like','sesion.%'))->orderByDesc('audit_logs.id')->paginate(40)]);
    }

    public function readNotification(string $notification): RedirectResponse
    {
        $row = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $row->markAsRead();

        return back()->with('status', 'Notificación marcada como leída.');
    }
}
