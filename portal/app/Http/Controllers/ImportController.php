<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\{Audit, EmailPolicy};
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends Controller
{
    private const HEADER = ['name', 'email', 'matricula', 'academic_average', 'merit', 'eligible'];

    public function template(): Response
    {
        return response(implode(',', self::HEADER)."\nInterno Demo,persona@example.test,DEMO-001,85,70,1\n", 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="nomina.csv"']);
    }

    public function preview(Request $request): View
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($file, 0, ',', '"', '');
        if ($header !== self::HEADER) {
            fclose($file);
            throw ValidationException::withMessages(['file' => 'Cabecera requerida: '.implode(',', self::HEADER)]);
        }
        $rows = []; $errors = []; $emails = []; $matriculas = []; $line = 1;
        try {
            while (($values = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $line++;
                if ($line > 501) { throw ValidationException::withMessages(['file' => 'Máximo 500 filas por importación.']); }
                if ($values === [null]) { continue; }
                if (count($values) !== count(self::HEADER)) { $errors[$line] = 'Se requieren seis columnas.'; continue; }
                $data = array_combine(self::HEADER, $values);
                $data['email'] = strtolower(trim($data['email']));
                $data['matricula'] = trim($data['matricula']);
                $validator = Validator::make($data, ['name' => 'required|string|max:150', 'email' => 'required|email|max:255', 'matricula' => 'required|string|max:50', 'academic_average' => 'required|numeric|between:0,100', 'merit' => 'required|integer|between:0,100', 'eligible' => 'required|in:0,1']);
                if ($validator->fails()) { $errors[$line] = implode(' ', $validator->errors()->all()); continue; }
                try { EmailPolicy::validate($data['email']); } catch (ValidationException) { $errors[$line] = 'Dominio de correo no permitido.'; continue; }
                if (isset($emails[$data['email']]) || isset($matriculas[$data['matricula']])) { $errors[$line] = 'Correo o matrícula repetidos dentro del archivo.'; continue; }
                $existing = DB::table('users')->where('email', $data['email'])->first();
                $admin = $request->user()->role === 'administrador';
                if (($admin && $existing) || (! $admin && (! $existing || $existing->role !== 'interno'))) { $errors[$line] = $admin ? 'Ya existe esa cuenta.' : 'Administración debe crear primero la cuenta de interno.'; continue; }
                if (DB::table('users')->where('matricula', $data['matricula'])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) { $errors[$line] = 'Matrícula utilizada por otra cuenta.'; continue; }
                $emails[$data['email']] = true; $matriculas[$data['matricula']] = true;
                $rows[$line] = $data;
            }
        } finally { fclose($file); }
        $request->session()->put('import_preview', ['rows' => $rows, 'role' => $request->user()->role, 'user' => $request->user()->id, 'expires' => now()->addMinutes(15)->timestamp]);
        return view('import-preview', compact('rows', 'errors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $preview = $request->session()->get('import_preview');
        abort_unless($preview && $preview['user'] === $request->user()->id && $preview['role'] === $request->user()->role && $preview['expires'] > now()->timestamp && count($preview['rows']) > 0, 422, 'Genera una vista previa vigente con filas válidas.');
        DB::transaction(function () use ($preview) {
            DB::table('users')->orderBy('id')->lockForUpdate()->get();
            foreach ($preview['rows'] as $data) {
                $existing = User::where('email', $data['email'])->first();
                if ($preview['role'] === 'administrador') {
                    if ($existing) { throw ValidationException::withMessages(['file' => 'Los datos cambiaron. Repite la vista previa.']); }
                    $user = new User;
                    $user->forceFill(['name' => $data['name'], 'email' => $data['email'], 'role' => 'interno', 'active' => true, 'password' => Str::random(64)])->save();
                } else {
                    abort_unless($existing && $existing->role === 'interno', 409);
                    if (DB::table('users')->where('matricula', $data['matricula'])->where('id', '!=', $existing->id)->exists()) { throw ValidationException::withMessages(['file' => 'Matrícula duplicada. Repite la vista previa.']); }
                    $existing->forceFill(collect($data)->only(['matricula', 'academic_average', 'merit', 'eligible'])->all())->save();
                }
            }
            Audit::record('nomina.importada', ['cantidad' => count($preview['rows']), 'ambito' => $preview['role']]);
        });
        $request->session()->forget('import_preview');
        return redirect()->route('management', $request->user()->role === 'administrador' ? 'usuarios' : 'internos')->with('status', count($preview['rows']).' filas válidas guardadas. Las cuentas nuevas se activan desde Recuperar acceso → Activar mi cuenta nueva.');
    }
}
