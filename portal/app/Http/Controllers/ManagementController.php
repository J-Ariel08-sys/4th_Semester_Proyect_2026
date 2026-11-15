<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagementController extends Controller
{
    public static function definitions(): array
    {
        return [
            'usuarios' => ['table' => 'users', 'title' => 'Usuarios y permisos', 'fields' => ['name' => 'Nombre completo', 'email' => 'Correo', 'role' => 'Rol', 'matricula' => 'Matrícula', 'academic_average' => 'Promedio académico (0–100)', 'merit' => 'Méritos normalizados (0–100)', 'eligible' => 'Habilitado académicamente', 'active' => 'Cuenta activa', 'password' => 'Contraseña (mínimo 12 caracteres)']],
            'hospitales' => ['table' => 'hospitals', 'title' => 'Hospitales y convenios', 'fields' => ['name' => 'Nombre', 'agreement_until' => 'Convenio válido hasta', 'active' => 'Habilitado']],
            'especialidades' => ['table' => 'specialties', 'title' => 'Especialidades', 'fields' => ['name' => 'Nombre']],
            'convocatorias' => ['table' => 'cycles', 'title' => 'Convocatorias de rotación', 'fields' => ['name' => 'Nombre', 'specialty_id' => 'Especialidad', 'starts_on' => 'Inicio del rote', 'ends_on' => 'Fin del rote', 'opens_at' => 'Apertura de postulaciones', 'closes_at' => 'Cierre de postulaciones']],
            'plazas' => ['table' => 'places', 'title' => 'Cupos hospitalarios', 'fields' => ['cycle_id' => 'Convocatoria', 'hospital_id' => 'Hospital', 'tutor_id' => 'Tutor responsable', 'capacity' => 'Capacidad autorizada', 'reserve' => 'Cupos de reserva']],
        ];
    }

    private function definition(string $resource): array
    {
        abort_unless(isset(self::definitions()[$resource]), 404);
        if ($resource === 'usuarios') {
            abort_unless(auth()->user()->role === 'administrador', 403);
        }

        return self::definitions()[$resource];
    }

    public function index(string $resource, ?int $id = null): View
    {
        $definition = $this->definition($resource);
        $rows = DB::table($definition['table'])->orderByDesc('id')->paginate(20);
        $edit = $id ? DB::table($definition['table'])->find($id) : null;
        if ($id) {
            abort_unless($edit, 404);
        }
        $choices = [
            'role' => ['administrador' => 'Administrador', 'coordinador' => 'Coordinador', 'tutor' => 'Tutor', 'interno' => 'Interno'],
            'specialty_id' => DB::table('specialties')->pluck('name', 'id')->all(),
            'cycle_id' => DB::table('cycles')->where('starts_on', '>', now()->toDateString())->pluck('name', 'id')->all(),
            'hospital_id' => DB::table('hospitals')->where('active', true)->pluck('name', 'id')->all(),
            'tutor_id' => DB::table('users')->where('role', 'tutor')->where('active', true)->pluck('name', 'id')->all(),
        ];

        return view('management', compact('definition', 'resource', 'rows', 'edit', 'choices'));
    }

    public function save(Request $request, string $resource, ?int $id = null): RedirectResponse
    {
        $definition = $this->definition($resource);
        $rules = match ($resource) {
            'usuarios' => ['name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)], 'role' => ['required', Rule::in(['administrador', 'coordinador', 'tutor', 'interno'])], 'matricula' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($id)], 'academic_average' => 'required|numeric|between:0,100', 'merit' => 'required|integer|between:0,100', 'eligible' => 'boolean', 'active' => 'boolean', 'password' => [$id ? 'nullable' : 'required', 'string', 'min:12', 'max:255']],
            'hospitales' => ['name' => ['required', 'string', 'max:150', Rule::unique('hospitals')->ignore($id)], 'agreement_until' => 'required|date', 'active' => 'boolean'],
            'especialidades' => ['name' => ['required', 'string', 'max:150', Rule::unique('specialties')->ignore($id)]],
            'convocatorias' => ['name' => 'required|string|max:150', 'specialty_id' => 'required|exists:specialties,id', 'starts_on' => 'required|date', 'ends_on' => 'required|date|after_or_equal:starts_on', 'opens_at' => 'required|date', 'closes_at' => 'required|date|after:opens_at|before:starts_on'],
            'plazas' => ['cycle_id' => 'required|exists:cycles,id', 'hospital_id' => 'required|exists:hospitals,id', 'tutor_id' => 'required|exists:users,id', 'capacity' => 'required|integer|between:0,500', 'reserve' => 'required|integer|min:0|lte:capacity'],
        };
        if ($resource === 'usuarios') {
            $request->merge(['email' => strtolower((string) $request->input('email'))]);
        }
        $data = $request->validate($rules);
        foreach (['active', 'eligible'] as $field) {
            if (array_key_exists($field, $rules)) {
                $data[$field] = $request->boolean($field);
            }
        }
        DB::transaction(function () use ($definition, $data, $resource, $id) {
            $existing = $id ? DB::table($definition['table'])->where('id', $id)->lockForUpdate()->first() : null;
            if ($id) {
                abort_unless($existing, 404);
            }
            if ($resource === 'usuarios') {
                $data['email'] = strtolower($data['email']);
                if ($id === auth()->id() && (! $data['active'] || $data['role'] !== 'administrador')) {
                    throw ValidationException::withMessages(['role' => 'No puedes desactivar tu propia cuenta o quitarte el rol administrador.']);
                }
                if ($existing && $existing->role !== $data['role']) {
                    throw ValidationException::withMessages(['role' => 'El rol de una cuenta existente es fijo para preservar el historial. Crea otra cuenta si corresponde.']);
                }
                if (! empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }
            }
            if ($resource === 'convocatorias' && $existing && $existing->status !== 'borrador') {
                throw ValidationException::withMessages(['name' => 'Solo puedes editar convocatorias en borrador.']);
            }
            if ($resource === 'plazas') {
                $cycle = DB::table('cycles')->where('id', $data['cycle_id'])->lockForUpdate()->first();
                if (! in_array($cycle->status, ['borrador', 'cerrada', 'publicada'], true) || now()->gte($cycle->starts_on) || ($existing && ($existing->cycle_id !== (int) $data['cycle_id'] || $existing->hospital_id !== (int) $data['hospital_id'] || $existing->tutor_id !== (int) $data['tutor_id']))) {
                    throw ValidationException::withMessages(['cycle_id' => 'Ajusta capacidad y reserva antes del inicio, con postulaciones cerradas o en borrador. No cambies hospital, convocatoria ni tutor de una plaza existente.']);
                }
                if ($id && DB::table('assignments')->where('place_id', $id)->count() > (int) $data['capacity'] - (int) $data['reserve']) {
                    throw ValidationException::withMessages(['capacity' => 'Primero redistribuye los internos: la nueva capacidad no puede dejar sobrecupos publicados.']);
                }
                $hospital = DB::table('hospitals')->find($data['hospital_id']);
                $tutor = DB::table('users')->find($data['tutor_id']);
                if (! $hospital->active || $hospital->agreement_until < $cycle->ends_on || ! $tutor->active || $tutor->role !== 'tutor') {
                    throw ValidationException::withMessages(['hospital_id' => 'Verifica convenio vigente, hospital activo y tutor activo.']);
                }
                if (DB::table('places')->where('cycle_id', $cycle->id)->where('hospital_id', $hospital->id)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                    throw ValidationException::withMessages(['hospital_id' => 'Ya existe un cupo para este hospital en la convocatoria.']);
                }
                if (DB::table('places')->join('cycles', 'cycles.id', '=', 'places.cycle_id')->where('places.hospital_id', $hospital->id)->where('cycles.specialty_id', $cycle->specialty_id)->where('cycles.id', '!=', $cycle->id)->where('cycles.starts_on', '<=', $cycle->ends_on)->where('cycles.ends_on', '>=', $cycle->starts_on)->exists()) {
                    throw ValidationException::withMessages(['cycle_id' => 'Existe otra oferta del mismo hospital y especialidad con fechas superpuestas.']);
                }
            }
            $data['updated_at'] = now();
            if ($id) {
                DB::table($definition['table'])->where('id', $id)->update($data);
            } else {
                $id = DB::table($definition['table'])->insertGetId([...$data, 'created_at' => now()]);
            }
            Audit::record('gestion.guardada', ['recurso' => $resource, 'id' => $id]);
        });

        return redirect()->route('management', $resource)->with('status', 'Registro guardado.');
    }

    public function transition(Request $request, int $cycle): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['abierta', 'cerrada'])]]);
        DB::transaction(function () use ($cycle, $data) {
            $row = DB::table('cycles')->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($row, 404);
            $expected = $data['status'] === 'abierta' ? 'borrador' : 'abierta';
            abort_unless($row->status === $expected, 409, 'Transición de convocatoria inválida.');
            if ($data['status'] === 'abierta' && ! DB::table('places')->where('cycle_id', $cycle)->exists()) {
                throw ValidationException::withMessages(['status' => 'Configura al menos una plaza antes de abrir.']);
            }
            DB::table('cycles')->where('id', $cycle)->update(['status' => $data['status'], 'updated_at' => now()]);
            Audit::record('convocatoria.estado', ['id' => $cycle, 'estado' => $data['status']]);
        });

        return back()->with('status', 'Estado actualizado.');
    }
}
