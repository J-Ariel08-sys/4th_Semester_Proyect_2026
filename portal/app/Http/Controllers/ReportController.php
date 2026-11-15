<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(): View
    {
        $manager = in_array(auth()->user()->role, ['administrador', 'coordinador']);
        $cycles = DB::table('cycles')->where('status', 'publicada')->get();
        $reports = DB::table('reports')->join('cycles', 'cycles.id', '=', 'reports.cycle_id')->select('reports.*', 'cycles.name as cycle')->when(! $manager, fn ($q) => $q->whereIn('reports.cycle_id', ClinicalController::assignments()->select('assignments.cycle_id')))->orderByDesc('reports.id')->get();
        $assignments = ClinicalController::assignments()->get();

        $occupancy = $manager ? DB::table('places')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->join('cycles', 'cycles.id', '=', 'places.cycle_id')->select('places.*', 'hospitals.name as hospital', 'cycles.name as cycle')->get()->map(function ($place) {
            $place->occupied = DB::table('assignments')->where('place_id', $place->id)->count();

            return $place;
        }) : collect();
        $records = DB::table('clinical_records')->whereIn('assignment_id', $assignments->pluck('id'))->get()->groupBy('assignment_id');

        return view('reports', compact('reports', 'cycles', 'manager', 'assignments', 'occupancy', 'records'));
    }

    public static function qr(string $url): string
    {
        return (new QRCode(new QROptions(['outputInterface' => QRMarkupSVG::class, 'outputBase64' => true])))->render($url);
    }

    public function create(int $cycle): RedirectResponse
    {
        DB::transaction(function () use ($cycle) {
            $row = DB::table('cycles')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row && $row->status === 'publicada', 422, 'Solo se emiten actas de convocatorias publicadas.');
            $assignments = ClinicalController::assignments()->where('assignments.cycle_id', $cycle)->orderBy('users.name')->get();
            abort_unless($assignments->count() > 0, 422, 'No hay asignaciones aprobadas.');
            $token = (string) Str::uuid();
            $snapshot = ['convocatoria' => $row->name, 'emitida' => now()->toIso8601String(), 'asignaciones' => $assignments->map(fn ($a) => ['interno' => $a->intern, 'hospital' => $a->hospital, 'especialidad' => $a->specialty, 'inicio' => $a->starts_on, 'fin' => $a->ends_on])->all()];
            $qr = self::qr(route('reports.verify', $token));
            $pdf = Pdf::loadView('pdf', compact('snapshot', 'token', 'qr'))->setPaper('a4')->output();
            $path = 'reports/'.$token.'.pdf';
            Storage::disk('local')->put($path, $pdf);
            DB::table('reports')->insert(['cycle_id' => $cycle, 'token' => $token, 'snapshot' => json_encode($snapshot), 'sha256' => hash('sha256', $pdf), 'path' => $path, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('acta.emitida', ['token' => $token]);
        });

        return back()->with('status', 'Acta PDF generada con verificación QR e historial de versión.');
    }

    public function download(string $token): Response
    {
        $report = DB::table('reports')->where('token', $token)->first();
        abort_unless($report, 404);
        abort_unless(in_array(auth()->user()->role, ['administrador', 'coordinador']), 403, 'Las actas consolidadas son de uso de Coordinación. Consulta tu libreta individual.');

        return Storage::disk('local')->download($report->path, 'SICLIN-acta-'.$report->id.'.pdf');
    }

    public function verify(string $token): View
    {
        $report = DB::table('reports')->where('token', $token)->first();
        abort_unless($report, 404);

        // Public verification reveals document metadata only, never the intern roster.
        return view('verify', ['report' => $report, 'valid' => Storage::disk('local')->exists($report->path) && hash_equals($report->sha256, hash('sha256', Storage::disk('local')->get($report->path)))]);
    }

    public function receipt(string $token): View
    {
        $application = DB::table('applications')->where('receipt', $token)->first();
        abort_unless($application, 404);
        abort_unless($application->user_id === auth()->id() || in_array(auth()->user()->role, ['administrador', 'coordinador']), 403);
        $cycle = DB::table('cycles')->find($application->cycle_id);
        $preferences = collect(json_decode($application->preferences, true))->map(fn ($id) => DB::table('places')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->where('places.id', $id)->value('hospitals.name'));

        return view('receipt', ['application' => $application, 'cycle' => $cycle, 'preferences' => $preferences, 'qr' => self::qr(route('applications.receipt', $token))]);
    }
}
