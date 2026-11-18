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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(): View
    {
        $manager = (auth()->user()->managesAcademics());
        $cycles = DB::table('cycles')->whereNull('deleted_at')->where('status', 'publicada')->get();
        $reports = DB::table('reports')->join('cycles', 'cycles.id', '=', 'reports.cycle_id')->select('reports.*', 'cycles.name as cycle')
            ->when(! auth()->user()->isAdministrator(), fn ($query) => $query->where(function ($query) use ($manager) {
                $query->where('reports.user_id', auth()->id());
                if ($manager) {
                    $query->orWhereNull('reports.user_id');
                }
            }))->orderByDesc('reports.id')->get();
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
            $row = DB::table('cycles')->whereNull('deleted_at')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row && $row->status === 'publicada', 422, 'Solo se emiten actas de convocatorias publicadas.');
            $assignments = ClinicalController::assignments()->where('assignments.cycle_id', $cycle)->orderBy('users.name')->get();
            abort_unless($assignments->count() > 0, 422, 'No hay asignaciones aprobadas.');
            $token = (string) Str::uuid();
            $snapshot = ['version' => $assignments->first()->run_id, 'convocatoria' => $row->name, 'emitida' => now()->toIso8601String(), 'asignaciones' => $assignments->map(fn ($a) => ['interno' => $a->intern, 'hospital' => $a->hospital, 'especialidad' => $a->specialty, 'inicio' => $a->starts_on, 'fin' => $a->ends_on])->all()];
            $qr = self::qr(route('reports.verify', $token));
            $pdf = Pdf::loadView('pdf', compact('snapshot', 'token', 'qr'))->setPaper('a4')->output();
            $path = 'reports/'.$token.'.pdf';
            Storage::disk('local')->put($path, $pdf);
            DB::table('reports')->insert(['cycle_id' => $cycle, 'run_id' => $assignments->first()->run_id, 'token' => $token, 'snapshot' => json_encode($snapshot), 'sha256' => hash('sha256', $pdf), 'path' => $path, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('acta.emitida', ['token' => $token]);
        });

        return back()->with('status', 'Acta PDF generada con verificación QR e historial de versión.');
    }

    public function csv(): Response
    {
        $assignments = ClinicalController::assignments()->orderBy('assignments.id')->get();

        return response()->streamDownload(function () use ($assignments) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Asignación', 'Versión', 'Interno', 'Especialidad', 'Hospital', 'Inicio', 'Fin', 'Asistencias', 'Registros', 'Promedio'], ',', '"', '');
            foreach ($assignments as $row) {
                $records = DB::table('clinical_records')->where('assignment_id', $row->id)->get();
                $values = [$row->id, $row->run_id, $row->intern, $row->specialty, $row->hospital, $row->starts_on, $row->ends_on, $records->where('attendance', 'presente')->count(), $records->count(), $records->avg('grade')];
                $values = array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, $values);
                fputcsv($stream, $values, ',', '"', '');
            }
            fclose($stream);
        }, 'SICLIN-cumplimiento.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function personal(): RedirectResponse
    {
        $assignments = ClinicalController::assignments()->orderBy('assignments.id')->get();
        abort_unless($assignments->count(), 422, 'No hay asignaciones publicadas en tu ámbito.');
        $token = (string) Str::uuid();
        $snapshot = ['convocatoria' => 'Libreta individual · '.auth()->user()->name, 'version' => $assignments->pluck('run_id')->unique()->implode(', '), 'emitida' => now()->toIso8601String(), 'asignaciones' => $assignments->map(fn ($row) => ['interno' => $row->intern, 'hospital' => $row->hospital, 'especialidad' => $row->specialty, 'inicio' => $row->starts_on, 'fin' => $row->ends_on, 'seguimiento' => DB::table('clinical_records')->where('assignment_id', $row->id)->orderBy('date')->get()->all(), 'guardias' => DB::table('shifts')->join('shift_batches', 'shift_batches.id', '=', 'shifts.batch_id')->where('shifts.assignment_id', $row->id)->where('shift_batches.status', 'aprobada')->select('shifts.starts_at', 'shifts.ends_at', 'shifts.performed')->get()->all()])->all()];
        $qr = self::qr(route('reports.verify', $token));
        $pdf = Pdf::loadView('pdf', compact('snapshot', 'token', 'qr'))->setPaper('a4')->output();
        $path = 'reports/'.$token.'.pdf';
        Storage::disk('local')->put($path, $pdf);
        DB::table('reports')->insert(['cycle_id' => $assignments->first()->cycle_id, 'run_id' => $assignments->first()->run_id, 'user_id' => auth()->id(), 'token' => $token, 'snapshot' => json_encode($snapshot), 'sha256' => hash('sha256', $pdf), 'path' => $path, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        Audit::record('libreta.emitida', ['documento' => $token]);

        return back()->with('status', 'Documento de tu ámbito emitido y disponible para descargar.');
    }

    public function download(string $token): Response
    {
        abort_unless(Str::isUuid($token), 404);
        $report = DB::table('reports')->where('token', $token)->first();
        abort_unless($report, 404);
        Gate::authorize('download-report', $report);

        return Storage::disk('local')->download($report->path, 'SICLIN-acta-'.$report->id.'.pdf');
    }

    public function verify(string $token): View
    {
        abort_unless(Str::isUuid($token), 404);
        $report = DB::table('reports')->where('token', $token)->first();
        abort_unless($report, 404);

        // Public verification reveals document metadata only, never the intern roster.
        return view('verify', ['report' => $report, 'valid' => Storage::disk('local')->exists($report->path) && hash_equals($report->sha256, hash('sha256', Storage::disk('local')->get($report->path)))]);
    }

    public function receipt(string $token): View
    {
        abort_unless(Str::isUuid($token), 404);
        $application = DB::table('applications')->where('receipt', $token)->first();
        if (! $application) {
            $application = DB::table('application_versions')->join('applications', 'applications.id', '=', 'application_versions.application_id')->where('application_versions.receipt', $token)->select('applications.cycle_id', 'applications.user_id', 'application_versions.*')->first();
        }
        abort_unless($application, 404);
        abort_unless($application->user_id === auth()->id() || (auth()->user()->managesAcademics()), 403);
        $cycle = DB::table('cycles')->find($application->cycle_id);
        $preferences = collect(json_decode($application->preferences, true))->map(fn ($id) => DB::table('places')->join('hospitals', 'hospitals.id', '=', 'places.hospital_id')->where('places.id', $id)->value('hospitals.name'));

        return view('receipt', ['application' => $application, 'cycle' => $cycle, 'preferences' => $preferences, 'qr' => self::qr(route('applications.receipt', $token))]);
    }
}
