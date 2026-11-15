<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($file, 0, ',', '"', '');
        if ($header !== ['name', 'email', 'matricula', 'academic_average', 'merit', 'eligible']) {
            fclose($file);
            throw ValidationException::withMessages(['file' => 'Cabecera requerida: name,email,matricula,academic_average,merit,eligible']);
        }
        $rows = [];
        $emails = [];
        $matriculas = [];
        $line = 1;
        try {
            while (($values = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($rows) >= 500 || count($values) !== 6) {
                    throw ValidationException::withMessages(['file' => "Fila $line: se requieren 6 columnas; máximo 500 internos."]);
                }
                $data = array_combine($header, $values);
                $data['email'] = strtolower(trim($data['email']));
                $validator = Validator::make($data, ['name' => 'required|string|max:150', 'email' => 'required|email|max:255|unique:users,email', 'matricula' => 'required|string|max:50|unique:users,matricula', 'academic_average' => 'required|numeric|between:0,100', 'merit' => 'required|integer|between:0,100', 'eligible' => 'required|in:0,1']);
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['file' => "Fila $line: ".implode(' ', $validator->errors()->all())]);
                }
                if (isset($emails[$data['email']]) || isset($matriculas[$data['matricula']])) {
                    throw ValidationException::withMessages(['file' => "Fila $line: correo o matrícula repetidos en el archivo."]);
                }
                $emails[$data['email']] = true;
                $matriculas[$data['matricula']] = true;
                $rows[] = $data;
            }
        } finally {
            fclose($file);
        }
        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'El archivo no contiene internos.']);
        }
        DB::transaction(function () use ($rows) {
            foreach ($rows as $data) {
                $user = new User;
                $user->forceFill([...$data, 'role' => 'interno', 'active' => true, 'password' => Str::random(40)])->save();
            }
            Audit::record('nomina.importada', ['cantidad' => count($rows)]);
        });

        return back()->with('status', count($rows).' internos importados. Pueden establecer su contraseña mediante recuperación de cuenta.');
    }
}
